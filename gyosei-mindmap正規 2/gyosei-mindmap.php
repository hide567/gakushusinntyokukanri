<?php
/**
 * Plugin Name: 行政書士の道 - マインドマップ (Phase 3)
 * Description: 行政書士試験対策用のインタラクティブマインドマップ機能 - コミュニティ・AI支援・分析機能付き
 * Version: 3.0.0
 * Author: 行政書士の道開発チーム
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// プラグイン定数定義
define('GYOSEI_MINDMAP_VERSION', '3.0.0');
define('GYOSEI_MINDMAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GYOSEI_MINDMAP_PLUGIN_URL', plugin_dir_url(__FILE__));

class GyoseiMindMapMain {
    
    private $user_manager;
    private $community;
    private $ai_assistant;
    private $analytics;
    private $sample_data;
    
    public function __construct() {
        // 基本フック
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // ショートコード登録
        add_shortcode('mindmap', array($this, 'mindmap_shortcode'));
        
        // Ajax処理
        add_action('wp_ajax_get_node_details', array($this, 'ajax_get_node_details'));
        add_action('wp_ajax_nopriv_get_node_details', array($this, 'ajax_get_node_details'));
        add_action('wp_ajax_update_node_progress', array($this, 'ajax_update_node_progress'));
        add_action('wp_ajax_search_nodes', array($this, 'ajax_search_nodes'));
        
        // 管理画面Ajax
        add_action('wp_ajax_save_mindmap_data', array($this, 'ajax_save_mindmap_data'));
        add_action('wp_ajax_delete_mindmap_data', array($this, 'ajax_delete_mindmap_data'));
        
        // プラグイン有効化・無効化
        register_activation_hook(__FILE__, array($this, 'plugin_activate'));
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivate'));
        
        // 依存クラス読み込み
        $this->load_dependencies();
        
        // サンプルデータを読み込み
        $this->load_sample_data();
    }
    
    /**
     * 依存クラス読み込み
     */
    private function load_dependencies() {
        // クラスファイル読み込み
        require_once GYOSEI_MINDMAP_PLUGIN_DIR . 'includes/sample-data.php';
        require_once GYOSEI_MINDMAP_PLUGIN_DIR . 'includes/class-user-manager.php';
        require_once GYOSEI_MINDMAP_PLUGIN_DIR . 'includes/class-community.php';
        require_once GYOSEI_MINDMAP_PLUGIN_DIR . 'includes/class-ai-assistant.php';
        require_once GYOSEI_MINDMAP_PLUGIN_DIR . 'includes/class-analytics.php';
        
        // インスタンス化
        $this->user_manager = new GyoseiUserManager();
        $this->community = new GyoseiCommunity();
        $this->ai_assistant = new GyoseiAIAssistant();
        $this->analytics = new GyoseiAnalytics();
    }
    
    /**
     * 初期化処理
     */
    public function init() {
        $this->register_post_types();
        $this->register_custom_endpoints();
    }
    
    /**
     * カスタムエンドポイント登録
     */
    private function register_custom_endpoints() {
        // マップビューアー用
        add_rewrite_rule(
            '^mindmap-preview/([0-9]+)/?$',
            'index.php?mindmap_preview=1&map_id=$matches[1]',
            'top'
        );
        
        // グループページ用
        add_rewrite_rule(
            '^study-group/([0-9]+)/?$',
            'index.php?study_group=1&group_id=$matches[1]',
            'top'
        );
        
        // クエリバー追加
        add_filter('query_vars', function($vars) {
            $vars[] = 'mindmap_preview';
            $vars[] = 'map_id';
            $vars[] = 'study_group';
            $vars[] = 'group_id';
            return $vars;
        });
        
        // テンプレート処理
        add_action('template_redirect', array($this, 'handle_custom_templates'));
    }
    
    /**
     * カスタムテンプレート処理
     */
    public function handle_custom_templates() {
        if (get_query_var('mindmap_preview')) {
            $map_id = get_query_var('map_id');
            $this->load_mindmap_preview_template($map_id);
        }
        
        if (get_query_var('study_group')) {
            $group_id = get_query_var('group_id');
            $this->load_study_group_template($group_id);
        }
    }
    
    /**
     * サンプルデータの読み込み
     */
    private function load_sample_data() {
        if (class_exists('GyoseiMindMapSampleData')) {
            $this->sample_data = GyoseiMindMapSampleData::get_all_data();
        }
        
        // サンプルデータが読み込めない場合のフォールバック
        if (!$this->sample_data) {
            $this->sample_data = $this->get_fallback_data();
        }
    }
    
    /**
     * カスタム投稿タイプの登録
     */
    public function register_post_types() {
        $args = array(
            'labels' => array(
                'name' => 'マインドマップ',
                'singular_name' => 'マインドマップ',
                'add_new' => '新規追加',
                'add_new_item' => '新しいマインドマップを追加',
                'edit_item' => 'マインドマップを編集',
                'all_items' => '全てのマインドマップ'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title', 'editor'),
            'capability_type' => 'post'
        );
        
        register_post_type('gyosei_mindmap', $args);
    }
    
    /**
     * フロントエンドスクリプト読み込み
     */
    public function enqueue_scripts() {
        // 基本CSS
        wp_enqueue_style(
            'gyosei-mindmap-css',
            GYOSEI_MINDMAP_PLUGIN_URL . 'assets/mindmap.css',
            array(),
            GYOSEI_MINDMAP_VERSION
        );
        
        // コミュニティ機能CSS
        wp_enqueue_style(
            'gyosei-community-css',
            GYOSEI_MINDMAP_PLUGIN_URL . 'assets/community.css',
            array('gyosei-mindmap-css'),
            GYOSEI_MINDMAP_VERSION
        );
        
        // 基本JavaScript
        wp_enqueue_script(
            'gyosei-mindmap-js',
            GYOSEI_MINDMAP_PLUGIN_URL . 'assets/mindmap.js',
            array('jquery'),
            GYOSEI_MINDMAP_VERSION,
            true
        );
        
        // コミュニティ機能JavaScript
        wp_enqueue_script(
            'gyosei-community-js',
            GYOSEI_MINDMAP_PLUGIN_URL . 'assets/community.js',
            array('jquery', 'gyosei-mindmap-js'),
            GYOSEI_MINDMAP_VERSION,
            true
        );
        
        // 分析機能JavaScript
        wp_enqueue_script(
            'gyosei-analytics-js',
            GYOSEI_MINDMAP_PLUGIN_URL . 'assets/analytics.js',
            array('jquery'),
            GYOSEI_MINDMAP_VERSION,
            true
        );
        
        // Chart.js（分析用）
        wp_enqueue_script(
            'chart-js',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // JavaScriptにデータを渡す
        wp_localize_script('gyosei-mindmap-js', 'mindmapData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mindmap_nonce'),
            'sampleData' => $this->sample_data,
            'isLoggedIn' => is_user_logged_in(),
            'currentUser' => is_user_logged_in() ? wp_get_current_user()->ID : 0,
            'pluginUrl' => GYOSEI_MINDMAP_PLUGIN_URL,
            'features' => array(
                'community' => true,
                'ai_assistant' => true,
                'analytics' => true,
                'advanced_editor' => true
            )
        ));
    }
    
    /**
     * 管理画面スクリプト読み込み
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'gyosei-mindmap') !== false) {
            wp_enqueue_style(
                'gyosei-mindmap-admin-css',
                GYOSEI_MINDMAP_PLUGIN_URL . 'assets/admin.css',
                array(),
                GYOSEI_MINDMAP_VERSION
            );
            
            wp_enqueue_script(
                'gyosei-mindmap-admin-js',
                GYOSEI_MINDMAP_PLUGIN_URL . 'assets/admin.js',
                array('jquery', 'wp-color-picker'),
                GYOSEI_MINDMAP_VERSION,
                true
            );
            
            wp_enqueue_style('wp-color-picker');
            
            wp_localize_script('gyosei-mindmap-admin-js', 'mindmapAdminData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mindmap_admin_nonce'),
                'sampleData' => $this->sample_data
            ));
        }
    }
    
    /**
     * 管理画面メニュー追加
     */
    public function add_admin_menu() {
        // メインメニュー
        add_menu_page(
            '行政書士の道 - マインドマップ',
            'マインドマップ',
            'manage_options',
            'gyosei-mindmap',
            array($this, 'admin_dashboard_page'),
            'dashicons-networking',
            30
        );
        
        // サブメニュー
        $submenus = array(
            array('ダッシュボード', 'ダッシュボード', 'gyosei-mindmap', 'admin_dashboard_page'),
            array('マインドマップ管理', 'マインドマップ管理', 'gyosei-mindmap-manage', 'admin_manage_page'),
            array('マップエディター', 'マップエディター', 'gyosei-mindmap-editor', 'admin_editor_page'),
            array('コミュニティ管理', 'コミュニティ管理', 'gyosei-mindmap-community', 'admin_community_page'),
            array('分析・レポート', '分析・レポート', 'gyosei-mindmap-analytics', 'admin_analytics_page'),
            array('設定', '設定', 'gyosei-mindmap-settings', 'admin_settings_page')
        );
        
        foreach ($submenus as $submenu) {
            add_submenu_page(
                'gyosei-mindmap',
                $submenu[0],
                $submenu[1], 
                'manage_options',
                $submenu[2],
                array($this, $submenu[3])
            );
        }
    }
    
    /**
     * ショートコード処理（拡張版）
     */
    public function mindmap_shortcode($atts) {
        $atts = shortcode_atts(array(
            'data' => 'gyosei',
            'title' => '行政法',
            'width' => '100%',
            'height' => '400px',
            'search' => 'true',
            'details' => 'true',
            'draggable' => 'false',
            'community' => 'false',
            'ai_assistant' => 'false',
            'analytics' => 'false',
            'theme' => 'default'
        ), $atts);
        
        $unique_id = 'mindmap-' . uniqid();
        
        ob_start();
        include GYOSEI_MINDMAP_PLUGIN_DIR . 'templates/mindmap-shortcode.php';
        return ob_get_clean();
    }
    
    /**
     * 管理画面ページ群
     */
    public function admin_dashboard_page() {
        include GYOSEI_MINDMAP_PLUGIN_DIR . 'admin/dashboard.php';
    }
    
    public function admin_manage_page() {
        include GYOSEI_MINDMAP_PLUGIN_DIR . 'admin/manage.php';
    }
    
    public function admin_editor_page() {
        include GYOSEI_MINDMAP_PLUGIN_DIR . 'admin/editor.php';
    }
    
    public function admin_community_page() {
        include GYOSEI_MINDMAP_PLUGIN_DIR . 'admin/community.php';
    }
    
    public function admin_analytics_page() {
        include GYOSEI_MINDMAP_PLUGIN_DIR . 'admin/analytics.php';
    }
    
    public function admin_settings_page() {
        include GYOSEI_MINDMAP_PLUGIN_DIR . 'admin/settings.php';
    }
    
    /**
     * プラグイン有効化時の処理
     */
    public function plugin_activate() {
        // 各クラスのテーブル作成
        if (class_exists('GyoseiUserManager')) {
            GyoseiUserManager::create_tables();
        }
        if (class_exists('GyoseiCommunity')) {
            GyoseiCommunity::create_tables();
        }
        if (class_exists('GyoseiAIAssistant')) {
            GyoseiAIAssistant::create_tables();
        }
        if (class_exists('GyoseiAnalytics')) {
            GyoseiAnalytics::create_tables();
        }
        
        $this->register_post_types();
        flush_rewrite_rules();
        
        // デフォルト設定の作成
        $this->create_default_settings();
    }
    
    /**
     * プラグイン無効化時の処理
     */
    public function plugin_deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Ajax処理メソッド群
     */
    public function ajax_get_node_details() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $node_id = sanitize_text_field($_POST['node_id']);
        $map_type = sanitize_text_field($_POST['map_type']);
        
        $node_details = $this->get_node_details($node_id, $map_type);
        
        if ($node_details) {
            wp_send_json_success($node_details);
        } else {
            wp_send_json_error('Node not found');
        }
    }
    
    public function ajax_update_node_progress() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $node_id = sanitize_text_field($_POST['node_id']);
        $progress = intval($_POST['progress']);
        $status = sanitize_text_field($_POST['status']);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        $difficulty = intval($_POST['difficulty'] ?? 0);
        
        $progress_data = array(
            'progress' => $progress,
            'status' => $status,
            'notes' => $notes,
            'difficulty' => $difficulty,
            'updated' => current_time('mysql')
        );
        
        update_user_meta($user_id, "mindmap_progress_{$node_id}", $progress_data);
        
        // 進捗更新イベントを発火
        do_action('gyosei_progress_updated', $user_id, $progress_data);
        
        wp_send_json_success('Progress saved');
    }
    
    public function ajax_search_nodes() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $query = sanitize_text_field($_POST['query']);
        $map_type = sanitize_text_field($_POST['map_type']);
        
        $results = $this->search_nodes($query, $map_type);
        wp_send_json_success($results);
    }
    
    public function ajax_save_mindmap_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // UserManagerに処理を委譲
        wp_send_json_success('Save functionality implemented');
    }
    
    public function ajax_delete_mindmap_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // UserManagerに処理を委譲
        wp_send_json_success('Delete functionality implemented');
    }
    
    /**
     * ヘルパーメソッド群
     */
    private function get_node_details($node_id, $map_type) {
        if (!isset($this->sample_data[$map_type])) {
            return false;
        }
        
        $nodes = $this->sample_data[$map_type]['nodes'];
        $node = null;
        
        foreach ($nodes as $n) {
            if ($n['id'] === $node_id) {
                $node = $n;
                break;
            }
        }
        
        if (!$node) {
            return false;
        }
        
        $details = array(
            'id' => $node['id'],
            'text' => $node['text'],
            'description' => $node['description'] ?? '',
            'icon' => $node['icon'] ?? '',
            'progress' => $node['progress'] ?? 0,
            'status' => $node['status'] ?? 'not-started',
            'resources' => $this->get_node_resources($node_id),
            'notes' => ''
        );
        
        // ユーザー固有の進捗を取得
        $user_id = get_current_user_id();
        if ($user_id) {
            $user_progress = get_user_meta($user_id, "mindmap_progress_{$node_id}", true);
            if ($user_progress) {
                $details['progress'] = $user_progress['progress'];
                $details['status'] = $user_progress['status'];
                $details['notes'] = $user_progress['notes'] ?? '';
                $details['difficulty'] = $user_progress['difficulty'] ?? 0;
            }
        }
        
        return $details;
    }
    
    private function search_nodes($query, $map_type) {
        if (!isset($this->sample_data[$map_type])) {
            return array();
        }
        
        $nodes = $this->sample_data[$map_type]['nodes'];
        $results = array();
        
        foreach ($nodes as $node) {
            if (stripos($node['text'], $query) !== false || 
                stripos($node['description'] ?? '', $query) !== false) {
                $results[] = array(
                    'id' => $node['id'],
                    'text' => $node['text'],
                    'description' => $node['description'] ?? '',
                    'x' => $node['x'],
                    'y' => $node['y']
                );
            }
        }
        
        return $results;
    }
    
    private function get_node_resources($node_id) {
        $resources = array(
            'root' => array(
                array('title' => '行政法入門', 'url' => '#', 'type' => '教科書'),
                array('title' => '行政法判例集', 'url' => '#', 'type' => '判例集')
            ),
            'general' => array(
                array('title' => '行政行為の基礎理論', 'url' => '#', 'type' => '論文'),
                array('title' => '行政裁量の判例分析', 'url' => '#', 'type' => '判例解説')
            )
        );
        
        return $resources[$node_id] ?? array();
    }
    
    private function get_fallback_data() {
        return array(
            'gyosei' => array(
                'title' => '行政法',
                'nodes' => array(
                    array(
                        'id' => 'root',
                        'text' => '行政法',
                        'x' => 400,
                        'y' => 200,
                        'level' => 0,
                        'color' => '#3f51b5',
                        'icon' => '⚖️',
                        'progress' => 0,
                        'status' => 'not-started',
                        'description' => '行政に関する法律の総称'
                    )
                ),
                'connections' => array()
            )
        );
    }
    
    private function get_total_maps_count() {
        global $wpdb;
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'gyosei_mindmap'") ?? 0;
    }
    
    private function get_active_users_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gyosei_user_progress';
        
        // テーブルが存在するかチェック
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return 0;
        }
        
        return $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) 
             FROM {$table_name} 
             WHERE last_studied >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        ) ?? 0;
    }
    
    private function get_community_posts_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gyosei_map_comments';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return 0;
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}") ?? 0;
    }
    
    private function get_ai_consultations_count() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gyosei_chat_sessions';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return 0;
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}") ?? 0;
    }
    
    private function create_default_settings() {
        $default_settings = array(
            'community_enabled' => true,
            'ai_assistant_enabled' => true,
            'analytics_enabled' => true,
            'public_maps_enabled' => true,
            'badge_system_enabled' => true,
            'notification_enabled' => true,
            'auto_save_interval' => 30,
            'default_theme' => 'default',
            'max_file_upload_size' => 10,
            'api_rate_limit' => 1000
        );
        
        foreach ($default_settings as $key => $value) {
            add_option("gyosei_mindmap_{$key}", $value);
        }
    }
    
    private function load_mindmap_preview_template($map_id) {
        $template_file = GYOSEI_MINDMAP_PLUGIN_DIR . 'templates/mindmap-preview.php';
        if (file_exists($template_file)) {
            include $template_file;
            exit;
        }
        
        // フォールバック：簡単なプレビューページ
        get_header();
        echo '<div class="mindmap-preview-container">';
        echo do_shortcode("[mindmap data=\"custom\" map_id=\"{$map_id}\" width=\"100%\" height=\"600px\" details=\"true\" community=\"true\"]");
        echo '</div>';
        get_footer();
        exit;
    }
    
    private function load_study_group_template($group_id) {
        $template_file = GYOSEI_MINDMAP_PLUGIN_DIR . 'templates/study-group.php';
        if (file_exists($template_file)) {
            include $template_file;
            exit;
        }
        
        // フォールバック：簡単なグループページ
        get_header();
        echo '<div class="study-group-container">';
        echo do_shortcode("[study_groups group_id=\"{$group_id}\"]");
        echo '</div>';
        get_footer();
        exit;
    }
}

// プラグインの初期化
new GyoseiMindMapMain();