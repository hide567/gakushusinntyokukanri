<?php
/**
 * 行政書士の道 - ユーザー管理クラス
 * 進捗管理・マインドマップ保存・学習履歴
 * File: includes/class-user-manager.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class GyoseiUserManager {
    
    private $progress_table;
    private $mindmaps_table;
    private $learning_sessions_table;
    private $user_settings_table;
    
    public function __construct() {
        global $wpdb;
        $this->progress_table = $wpdb->prefix . 'gyosei_user_progress';
        $this->mindmaps_table = $wpdb->prefix . 'gyosei_mindmaps';
        $this->learning_sessions_table = $wpdb->prefix . 'gyosei_learning_sessions';
        $this->user_settings_table = $wpdb->prefix . 'gyosei_user_settings';
        
        // Ajax フック
        add_action('wp_ajax_save_user_progress', array($this, 'ajax_save_user_progress'));
        add_action('wp_ajax_get_user_progress', array($this, 'ajax_get_user_progress'));
        add_action('wp_ajax_save_mindmap', array($this, 'ajax_save_mindmap'));
        add_action('wp_ajax_load_user_mindmap', array($this, 'ajax_load_user_mindmap'));
        add_action('wp_ajax_delete_mindmap', array($this, 'ajax_delete_mindmap'));
        add_action('wp_ajax_start_learning_session', array($this, 'ajax_start_learning_session'));
        add_action('wp_ajax_end_learning_session', array($this, 'ajax_end_learning_session'));
        add_action('wp_ajax_update_user_settings', array($this, 'ajax_update_user_settings'));
        
        // フック処理
        add_action('gyosei_progress_updated', array($this, 'on_progress_updated'), 10, 2);
        add_action('wp_login', array($this, 'on_user_login'), 10, 2);
    }
    
    /**
     * テーブル作成
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // ユーザー進捗テーブル
        $progress_table = $wpdb->prefix . 'gyosei_user_progress';
        $sql1 = "CREATE TABLE $progress_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            map_id bigint(20) DEFAULT NULL,
            node_id varchar(100) NOT NULL,
            progress_percent int DEFAULT 0,
            status varchar(20) DEFAULT 'not-started',
            mastery_level float DEFAULT 0,
            difficulty_rating int DEFAULT 0,
            notes text,
            total_study_time int DEFAULT 0,
            last_studied datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_node (user_id, node_id),
            KEY user_id (user_id),
            KEY map_id (map_id),
            KEY status (status),
            KEY last_studied (last_studied)
        ) $charset_collate;";
        
        // ユーザーマインドマップテーブル
        $mindmaps_table = $wpdb->prefix . 'gyosei_mindmaps';
        $sql2 = "CREATE TABLE $mindmaps_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            creator_id bigint(20) NOT NULL,
            category varchar(100) DEFAULT 'custom',
            map_data longtext NOT NULL,
            is_public tinyint(1) DEFAULT 0,
            is_template tinyint(1) DEFAULT 0,
            tags text,
            likes_count int DEFAULT 0,
            views_count int DEFAULT 0,
            downloads_count int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY creator_id (creator_id),
            KEY category (category),
            KEY is_public (is_public),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // 学習セッションテーブル
        $learning_sessions_table = $wpdb->prefix . 'gyosei_learning_sessions';
        $sql3 = "CREATE TABLE $learning_sessions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            session_id varchar(100) NOT NULL,
            map_id bigint(20),
            node_id varchar(100),
            start_time datetime DEFAULT CURRENT_TIMESTAMP,
            end_time datetime,
            duration int DEFAULT 0,
            nodes_studied int DEFAULT 0,
            nodes_completed int DEFAULT 0,
            session_data text,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY start_time (start_time)
        ) $charset_collate;";
        
        // ユーザー設定テーブル
        $user_settings_table = $wpdb->prefix . 'gyosei_user_settings';
        $sql4 = "CREATE TABLE $user_settings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            setting_key varchar(100) NOT NULL,
            setting_value longtext,
            PRIMARY KEY (id),
            UNIQUE KEY user_setting (user_id, setting_key),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);
        
        // デフォルトマインドマップの作成
        self::create_default_mindmaps();
    }
    
    /**
     * デフォルトマインドマップ作成
     */
    private static function create_default_mindmaps() {
        global $wpdb;
        
        $mindmaps_table = $wpdb->prefix . 'gyosei_mindmaps';
        
        // サンプルデータを取得
        if (class_exists('GyoseiMindMapSampleData')) {
            $sample_data = GyoseiMindMapSampleData::get_all_data();
            
            foreach ($sample_data as $category => $data) {
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $mindmaps_table WHERE category = %s AND is_template = 1",
                    $category
                ));
                
                if (!$existing) {
                    $wpdb->insert(
                        $mindmaps_table,
                        array(
                            'title' => $data['title'],
                            'description' => $data['description'],
                            'creator_id' => 1, // 管理者
                            'category' => $category,
                            'map_data' => json_encode($data),
                            'is_public' => 1,
                            'is_template' => 1,
                            'tags' => $category
                        ),
                        array('%s', '%s', '%d', '%s', '%s', '%d', '%d', '%s')
                    );
                }
            }
        }
    }
    
    /**
     * Ajax: ユーザー進捗保存
     */
    public function ajax_save_user_progress() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $node_id = sanitize_text_field($_POST['node_id']);
        $progress = intval($_POST['progress'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? 'not-started');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        $difficulty = intval($_POST['difficulty'] ?? 0);
        $study_time = intval($_POST['study_time'] ?? 0);
        $map_id = intval($_POST['map_id'] ?? 0);
        
        $result = $this->save_user_progress($user_id, $node_id, $progress, $status, $notes, $difficulty, $study_time, $map_id);
        
        if ($result) {
            wp_send_json_success('Progress saved successfully');
        } else {
            wp_send_json_error('Failed to save progress');
        }
    }
    
    /**
     * ユーザー進捗保存
     */
    public function save_user_progress($user_id, $node_id, $progress, $status, $notes = '', $difficulty = 0, $study_time = 0, $map_id = 0) {
        global $wpdb;
        
        // 既存の進捗を取得
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->progress_table} WHERE user_id = %d AND node_id = %s",
            $user_id, $node_id
        ));
        
        $mastery_level = $this->calculate_mastery_level($progress, $difficulty, $status);
        
        if ($existing) {
            // 更新
            $total_study_time = $existing->total_study_time + $study_time;
            
            $result = $wpdb->update(
                $this->progress_table,
                array(
                    'progress_percent' => $progress,
                    'status' => $status,
                    'mastery_level' => $mastery_level,
                    'difficulty_rating' => $difficulty,
                    'notes' => $notes,
                    'total_study_time' => $total_study_time,
                    'last_studied' => current_time('mysql')
                ),
                array('user_id' => $user_id, 'node_id' => $node_id),
                array('%d', '%s', '%f', '%d', '%s', '%d', '%s'),
                array('%d', '%s')
            );
        } else {
            // 新規作成
            $result = $wpdb->insert(
                $this->progress_table,
                array(
                    'user_id' => $user_id,
                    'map_id' => $map_id,
                    'node_id' => $node_id,
                    'progress_percent' => $progress,
                    'status' => $status,
                    'mastery_level' => $mastery_level,
                    'difficulty_rating' => $difficulty,
                    'notes' => $notes,
                    'total_study_time' => $study_time,
                    'last_studied' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%d', '%s', '%f', '%d', '%s', '%d', '%s')
            );
        }
        
        if ($result !== false) {
            // 進捗更新イベントを発火
            do_action('gyosei_progress_updated', $user_id, array(
                'node_id' => $node_id,
                'progress' => $progress,
                'status' => $status,
                'mastery_level' => $mastery_level
            ));
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Ajax: ユーザー進捗取得
     */
    public function ajax_get_user_progress() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $node_id = sanitize_text_field($_POST['node_id'] ?? '');
        $map_id = intval($_POST['map_id'] ?? 0);
        
        if ($node_id) {
            $progress = $this->get_user_node_progress($user_id, $node_id);
        } else {
            $progress = $this->get_user_map_progress($user_id, $map_id);
        }
        
        wp_send_json_success($progress);
    }
    
    /**
     * ユーザーのノード進捗取得
     */
    public function get_user_node_progress($user_id, $node_id) {
        global $wpdb;
        
        $progress = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->progress_table} WHERE user_id = %d AND node_id = %s",
            $user_id, $node_id
        ));
        
        if ($progress) {
            return array(
                'progress' => $progress->progress_percent,
                'status' => $progress->status,
                'mastery_level' => $progress->mastery_level,
                'difficulty_rating' => $progress->difficulty_rating,
                'notes' => $progress->notes,
                'study_time' => $progress->total_study_time,
                'last_studied' => $progress->last_studied
            );
        }
        
        return array(
            'progress' => 0,
            'status' => 'not-started',
            'mastery_level' => 0,
            'difficulty_rating' => 0,
            'notes' => '',
            'study_time' => 0,
            'last_studied' => null
        );
    }
    
    /**
     * ユーザーのマップ全体進捗取得
     */
    public function get_user_map_progress($user_id, $map_id = 0) {
        global $wpdb;
        
        $where_condition = $map_id > 0 ? 
            $wpdb->prepare('WHERE user_id = %d AND map_id = %d', $user_id, $map_id) :
            $wpdb->prepare('WHERE user_id = %d', $user_id);
        
        $progress_data = $wpdb->get_results(
            "SELECT * FROM {$this->progress_table} {$where_condition} ORDER BY last_studied DESC"
        );
        
        $formatted_progress = array();
        foreach ($progress_data as $progress) {
            $formatted_progress[$progress->node_id] = array(
                'progress' => $progress->progress_percent,
                'status' => $progress->status,
                'mastery_level' => $progress->mastery_level,
                'difficulty_rating' => $progress->difficulty_rating,
                'notes' => $progress->notes,
                'study_time' => $progress->total_study_time,
                'last_studied' => $progress->last_studied
            );
        }
        
        return $formatted_progress;
    }
    
    /**
     * Ajax: マインドマップ保存
     */
    public function ajax_save_mindmap() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? 'custom');
        $map_data = $_POST['map_data']; // JSON文字列
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $tags = sanitize_text_field($_POST['tags'] ?? '');
        $map_id = intval($_POST['map_id'] ?? 0);
        
        // JSONデータの検証
        $decoded_data = json_decode($map_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('Invalid map data format');
        }
        
        if ($map_id > 0) {
            // 更新
            $result = $this->update_mindmap($map_id, $user_id, $title, $description, $category, $map_data, $is_public, $tags);
        } else {
            // 新規作成
            $result = $this->create_mindmap($user_id, $title, $description, $category, $map_data, $is_public, $tags);
        }
        
        if ($result) {
            wp_send_json_success(array('map_id' => $result));
        } else {
            wp_send_json_error('Failed to save mindmap');
        }
    }
    
    /**
     * マインドマップ作成
     */
    public function create_mindmap($user_id, $title, $description, $category, $map_data, $is_public, $tags) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->mindmaps_table,
            array(
                'title' => $title,
                'description' => $description,
                'creator_id' => $user_id,
                'category' => $category,
                'map_data' => $map_data,
                'is_public' => $is_public,
                'tags' => $tags
            ),
            array('%s', '%s', '%d', '%s', '%s', '%d', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * マインドマップ更新
     */
    public function update_mindmap($map_id, $user_id, $title, $description, $category, $map_data, $is_public, $tags) {
        global $wpdb;
        
        // 所有者チェック
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT creator_id FROM {$this->mindmaps_table} WHERE id = %d",
            $map_id
        ));
        
        if (!$existing || $existing->creator_id != $user_id) {
            return false;
        }
        
        $result = $wpdb->update(
            $this->mindmaps_table,
            array(
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'map_data' => $map_data,
                'is_public' => $is_public,
                'tags' => $tags
            ),
            array('id' => $map_id),
            array('%s', '%s', '%s', '%s', '%d', '%s'),
            array('%d')
        );
        
        return $result !== false ? $map_id : false;
    }
    
    /**
     * Ajax: ユーザーマインドマップ読み込み
     */
    public function ajax_load_user_mindmap() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $map_id = intval($_POST['map_id']);
        $user_id = get_current_user_id();
        
        $mindmap = $this->get_mindmap($map_id, $user_id);
        
        if ($mindmap) {
            wp_send_json_success($mindmap);
        } else {
            wp_send_json_error('Mindmap not found');
        }
    }
    
    /**
     * マインドマップ取得
     */
    public function get_mindmap($map_id, $user_id = 0) {
        global $wpdb;
        
        $where_condition = "WHERE m.id = %d";
        $params = array($map_id);
        
        if ($user_id > 0) {
            $where_condition .= " AND (m.is_public = 1 OR m.creator_id = %d)";
            $params[] = $user_id;
        } else {
            $where_condition .= " AND m.is_public = 1";
        }
        
        $mindmap = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, u.display_name as creator_name
             FROM {$this->mindmaps_table} m
             LEFT JOIN {$wpdb->users} u ON m.creator_id = u.ID
             {$where_condition}",
            $params
        ));
        
        if ($mindmap) {
            // マップデータをデコード
            $map_data = json_decode($mindmap->map_data, true);
            
            // ユーザーの進捗データを統合
            if ($user_id > 0) {
                $progress_data = $this->get_user_map_progress($user_id, $map_id);
                
                if (!empty($map_data['nodes'])) {
                    foreach ($map_data['nodes'] as &$node) {
                        if (isset($progress_data[$node['id']])) {
                            $node = array_merge($node, $progress_data[$node['id']]);
                        }
                    }
                }
            }
            
            return array(
                'id' => $mindmap->id,
                'title' => $mindmap->title,
                'description' => $mindmap->description,
                'category' => $mindmap->category,
                'creator_name' => $mindmap->creator_name,
                'is_public' => $mindmap->is_public,
                'tags' => $mindmap->tags,
                'map_data' => $map_data,
                'created_at' => $mindmap->created_at,
                'updated_at' => $mindmap->updated_at
            );
        }
        
        return false;
    }
    
    /**
     * Ajax: マインドマップ削除
     */
    public function ajax_delete_mindmap() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $map_id = intval($_POST['map_id']);
        
        $result = $this->delete_mindmap($map_id, $user_id);
        
        if ($result) {
            wp_send_json_success('Mindmap deleted successfully');
        } else {
            wp_send_json_error('Failed to delete mindmap');
        }
    }
    
    /**
     * マインドマップ削除
     */
    public function delete_mindmap($map_id, $user_id) {
        global $wpdb;
        
        // 所有者チェック
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT creator_id FROM {$this->mindmaps_table} WHERE id = %d",
            $map_id
        ));
        
        if (!$existing || $existing->creator_id != $user_id) {
            return false;
        }
        
        // 関連進捗データも削除
        $wpdb->delete(
            $this->progress_table,
            array('map_id' => $map_id),
            array('%d')
        );
        
        // マインドマップ削除
        $result = $wpdb->delete(
            $this->mindmaps_table,
            array('id' => $map_id),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Ajax: 学習セッション開始
     */
    public function ajax_start_learning_session() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $map_id = intval($_POST['map_id'] ?? 0);
        $node_id = sanitize_text_field($_POST['node_id'] ?? '');
        
        $session_id = $this->start_learning_session($user_id, $map_id, $node_id);
        
        if ($session_id) {
            wp_send_json_success(array('session_id' => $session_id));
        } else {
            wp_send_json_error('Failed to start session');
        }
    }
    
    /**
     * 学習セッション開始
     */
    public function start_learning_session($user_id, $map_id = 0, $node_id = '') {
        global $wpdb;
        
        $session_id = 'session_' . time() . '_' . wp_generate_password(8, false);
        
        $result = $wpdb->insert(
            $this->learning_sessions_table,
            array(
                'user_id' => $user_id,
                'session_id' => $session_id,
                'map_id' => $map_id ?: null,
                'node_id' => $node_id ?: null,
                'start_time' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s', '%s')
        );
        
        return $result ? $session_id : false;
    }
    
    /**
     * Ajax: 学習セッション終了
     */
    public function ajax_end_learning_session() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $nodes_studied = intval($_POST['nodes_studied'] ?? 0);
        $nodes_completed = intval($_POST['nodes_completed'] ?? 0);
        $session_data = $_POST['session_data'] ?? '';
        
        $result = $this->end_learning_session($user_id, $session_id, $nodes_studied, $nodes_completed, $session_data);
        
        if ($result) {
            wp_send_json_success('Session ended successfully');
        } else {
            wp_send_json_error('Failed to end session');
        }
    }
    
    /**
     * 学習セッション終了
     */
    public function end_learning_session($user_id, $session_id, $nodes_studied = 0, $nodes_completed = 0, $session_data = '') {
        global $wpdb;
        
        // セッション情報を取得
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->learning_sessions_table} 
             WHERE user_id = %d AND session_id = %s AND end_time IS NULL",
            $user_id, $session_id
        ));
        
        if (!$session) {
            return false;
        }
        
        // 学習時間を計算
        $start_time = strtotime($session->start_time);
        $end_time = time();
        $duration = $end_time - $start_time;
        
        $result = $wpdb->update(
            $this->learning_sessions_table,
            array(
                'end_time' => current_time('mysql'),
                'duration' => $duration,
                'nodes_studied' => $nodes_studied,
                'nodes_completed' => $nodes_completed,
                'session_data' => $session_data
            ),
            array('id' => $session->id),
            array('%s', '%d', '%d', '%d', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * ヘルパーメソッド
     */
    private function calculate_mastery_level($progress, $difficulty, $status) {
        $base_mastery = $progress / 100;
        
        // 難易度による調整
        $difficulty_factor = 1 + ($difficulty - 5) * 0.1;
        
        // ステータスによる調整
        $status_factor = 1;
        switch ($status) {
            case 'completed':
                $status_factor = 1.2;
                break;
            case 'in-progress':
                $status_factor = 1.0;
                break;
            case 'not-started':
                $status_factor = 0.8;
                break;
        }
        
        $mastery = $base_mastery * $difficulty_factor * $status_factor;
        
        return min(1.0, max(0.0, $mastery));
    }
    
    /**
     * 進捗更新時のイベント処理
     */
    public function on_progress_updated($user_id, $progress_data) {
        // バッジチェックやその他の処理
        do_action('gyosei_check_achievements', $user_id, $progress_data);
    }
    
    /**
     * ユーザーログイン時の処理
     */
    public function on_user_login($user_login, $user) {
        // ログイン記録やその他の処理
        $this->update_user_setting($user->ID, 'last_login', current_time('mysql'));
    }
    
    /**
     * ユーザー設定更新
     */
    public function update_user_setting($user_id, $setting_key, $setting_value) {
        global $wpdb;
        
        $wpdb->replace(
            $this->user_settings_table,
            array(
                'user_id' => $user_id,
                'setting_key' => $setting_key,
                'setting_value' => $setting_value
            ),
            array('%d', '%s', '%s')
        );
    }
    
    /**
     * ユーザー設定取得
     */
    public function get_user_setting($user_id, $setting_key, $default = null) {
        global $wpdb;
        
        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM {$this->user_settings_table} 
             WHERE user_id = %d AND setting_key = %s",
            $user_id, $setting_key
        ));
        
        return $value !== null ? $value : $default;
    }
}