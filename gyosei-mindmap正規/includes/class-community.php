<?php
/**
 * 行政書士の道 - コミュニティ機能クラス
 * マップ共有・評価・コメントシステム
 * File: includes/class-community.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class GyoseiCommunity {
    
    private $comments_table;
    private $likes_table;
    private $follows_table;
    private $groups_table;
    private $group_members_table;
    
    public function __construct() {
        global $wpdb;
        $this->comments_table = $wpdb->prefix . 'gyosei_map_comments';
        $this->likes_table = $wpdb->prefix . 'gyosei_map_likes';
        $this->follows_table = $wpdb->prefix . 'gyosei_user_follows';
        $this->groups_table = $wpdb->prefix . 'gyosei_study_groups';
        $this->group_members_table = $wpdb->prefix . 'gyosei_group_members';
        
        // Ajax フック
        add_action('wp_ajax_get_community_maps', array($this, 'ajax_get_community_maps'));
        add_action('wp_ajax_nopriv_get_community_maps', array($this, 'ajax_get_community_maps'));
        add_action('wp_ajax_like_map', array($this, 'ajax_like_map'));
        add_action('wp_ajax_add_comment', array($this, 'ajax_add_comment'));
        add_action('wp_ajax_get_comments', array($this, 'ajax_get_comments'));
        add_action('wp_ajax_follow_user', array($this, 'ajax_follow_user'));
        add_action('wp_ajax_create_study_group', array($this, 'ajax_create_study_group'));
        add_action('wp_ajax_join_study_group', array($this, 'ajax_join_study_group'));
        add_action('wp_ajax_get_study_groups', array($this, 'ajax_get_study_groups'));
        add_action('wp_ajax_share_progress', array($this, 'ajax_share_progress'));
        add_action('wp_ajax_get_leaderboard', array($this, 'ajax_get_leaderboard'));
        
        // ショートコード
        add_shortcode('community_maps', array($this, 'community_maps_shortcode'));
        add_shortcode('study_groups', array($this, 'study_groups_shortcode'));
        add_shortcode('leaderboard', array($this, 'leaderboard_shortcode'));
    }
    
    /**
     * コミュニティテーブル作成
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // コメントテーブル
        $comments_table = $wpdb->prefix . 'gyosei_map_comments';
        $sql1 = "CREATE TABLE $comments_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            map_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            parent_id bigint(20) DEFAULT 0,
            content text NOT NULL,
            rating int DEFAULT 0,
            is_helpful tinyint(1) DEFAULT 0,
            helpful_count int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY map_id (map_id),
            KEY user_id (user_id),
            KEY parent_id (parent_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // いいねテーブル
        $likes_table = $wpdb->prefix . 'gyosei_map_likes';
        $sql2 = "CREATE TABLE $likes_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            map_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_map (user_id, map_id),
            KEY map_id (map_id)
        ) $charset_collate;";
        
        // フォローテーブル
        $follows_table = $wpdb->prefix . 'gyosei_user_follows';
        $sql3 = "CREATE TABLE $follows_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            follower_id bigint(20) NOT NULL,
            following_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY follow_pair (follower_id, following_id),
            KEY follower_id (follower_id),
            KEY following_id (following_id)
        ) $charset_collate;";
        
        // 学習グループテーブル
        $groups_table = $wpdb->prefix . 'gyosei_study_groups';
        $sql4 = "CREATE TABLE $groups_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            creator_id bigint(20) NOT NULL,
            category varchar(100) DEFAULT 'general',
            max_members int DEFAULT 50,
            is_private tinyint(1) DEFAULT 0,
            join_code varchar(20),
            study_schedule text,
            target_exam_date date,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY creator_id (creator_id),
            KEY category (category),
            KEY join_code (join_code)
        ) $charset_collate;";
        
        // グループメンバーテーブル
        $group_members_table = $wpdb->prefix . 'gyosei_group_members';
        $sql5 = "CREATE TABLE $group_members_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            group_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            role varchar(20) DEFAULT 'member',
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_active datetime DEFAULT CURRENT_TIMESTAMP,
            contribution_score int DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY group_user (group_id, user_id),
            KEY group_id (group_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);
        dbDelta($sql5);
    }
    
    /**
     * Ajax: コミュニティマップ取得
     */
    public function ajax_get_community_maps() {
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 12);
        $category = sanitize_text_field($_POST['category'] ?? '');
        $sort = sanitize_text_field($_POST['sort'] ?? 'newest');
        $search = sanitize_text_field($_POST['search'] ?? '');
        
        $maps = $this->get_community_maps($page, $per_page, $category, $sort, $search);
        wp_send_json_success($maps);
    }
    
    /**
     * コミュニティマップ取得
     */
    public function get_community_maps($page = 1, $per_page = 12, $category = '', $sort = 'newest', $search = '') {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        $where_conditions = array('m.is_public = 1');
        $params = array();
        
        if (!empty($category)) {
            $where_conditions[] = 'm.category = %s';
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $where_conditions[] = '(m.title LIKE %s OR m.description LIKE %s)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // ソート条件
        $order_clause = '';
        switch ($sort) {
            case 'popular':
                $order_clause = 'ORDER BY (m.likes_count * 0.5 + m.views_count * 0.3 + m.downloads_count * 0.2) DESC';
                break;
            case 'most_liked':
                $order_clause = 'ORDER BY m.likes_count DESC';
                break;
            case 'most_viewed':
                $order_clause = 'ORDER BY m.views_count DESC';
                break;
            case 'newest':
            default:
                $order_clause = 'ORDER BY m.created_at DESC';
                break;
        }
        
        $sql = "SELECT m.*, u.display_name as creator_name, u.user_nicename as creator_slug,
                       COUNT(DISTINCT c.id) as comment_count,
                       AVG(c.rating) as average_rating
                FROM {$wpdb->prefix}gyosei_mindmaps m
                LEFT JOIN {$wpdb->users} u ON m.creator_id = u.ID
                LEFT JOIN {$this->comments_table} c ON m.id = c.map_id
                WHERE {$where_clause}
                GROUP BY m.id
                {$order_clause}
                LIMIT %d OFFSET %d";
        
        $params[] = $per_page;
        $params[] = $offset;
        
        $maps = $wpdb->get_results($wpdb->prepare($sql, $params));
        
        // 各マップのデータを整理
        $formatted_maps = array();
        foreach ($maps as $map) {
            $map_data = json_decode($map->map_data, true);
            $node_count = count($map_data['nodes'] ?? array());
            
            $formatted_maps[] = array(
                'id' => $map->id,
                'title' => $map->title,
                'description' => $map->description,
                'category' => $map->category,
                'node_count' => $node_count,
                'creator_name' => $map->creator_name,
                'creator_slug' => $map->creator_slug,
                'likes_count' => $map->likes_count,
                'views_count' => $map->views_count,
                'comment_count' => $map->comment_count,
                'average_rating' => round($map->average_rating, 1),
                'created_at' => $map->created_at,
                'preview_url' => $this->get_map_preview_url($map->id),
                'tags' => $this->parse_tags($map->tags)
            );
        }
        
        // 総数を取得
        $count_sql = "SELECT COUNT(DISTINCT m.id)
                      FROM {$wpdb->prefix}gyosei_mindmaps m
                      WHERE {$where_clause}";
        
        $total_count = $wpdb->get_var($wpdb->prepare($count_sql, array_slice($params, 0, -2)));
        
        return array(
            'maps' => $formatted_maps,
            'pagination' => array(
                'current_page' => $page,
                'per_page' => $per_page,
                'total_count' => $total_count,
                'total_pages' => ceil($total_count / $per_page)
            )
        );
    }
    
    /**
     * Ajax: いいね機能
     */
    public function ajax_like_map() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $map_id = intval($_POST['map_id']);
        $action = sanitize_text_field($_POST['action_type']); // 'like' or 'unlike'
        
        global $wpdb;
        
        if ($action === 'like') {
            $result = $wpdb->insert(
                $this->likes_table,
                array('map_id' => $map_id, 'user_id' => $user_id),
                array('%d', '%d')
            );
            
            if ($result) {
                // いいね数をインクリメント
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}gyosei_mindmaps SET likes_count = likes_count + 1 WHERE id = %d",
                    $map_id
                ));
                
                $new_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT likes_count FROM {$wpdb->prefix}gyosei_mindmaps WHERE id = %d",
                    $map_id
                ));
                
                wp_send_json_success(array('likes_count' => $new_count, 'user_liked' => true));
            }
        } else {
            $result = $wpdb->delete(
                $this->likes_table,
                array('map_id' => $map_id, 'user_id' => $user_id),
                array('%d', '%d')
            );
            
            if ($result) {
                // いいね数をデクリメント
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}gyosei_mindmaps SET likes_count = GREATEST(likes_count - 1, 0) WHERE id = %d",
                    $map_id
                ));
                
                $new_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT likes_count FROM {$wpdb->prefix}gyosei_mindmaps WHERE id = %d",
                    $map_id
                ));
                
                wp_send_json_success(array('likes_count' => $new_count, 'user_liked' => false));
            }
        }
        
        wp_send_json_error('Failed to update like status');
    }
    
    /**
     * Ajax: コメント追加
     */
    public function ajax_add_comment() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $map_id = intval($_POST['map_id']);
        $content = sanitize_textarea_field($_POST['content']);
        $rating = intval($_POST['rating'] ?? 0);
        $parent_id = intval($_POST['parent_id'] ?? 0);
        
        if (empty($content)) {
            wp_send_json_error('Comment content is required');
        }
        
        global $wpdb;
        $result = $wpdb->insert(
            $this->comments_table,
            array(
                'map_id' => $map_id,
                'user_id' => $user_id,
                'parent_id' => $parent_id,
                'content' => $content,
                'rating' => $rating
            ),
            array('%d', '%d', '%d', '%s', '%d')
        );
        
        if ($result) {
            $comment_id = $wpdb->insert_id;
            $comment = $this->get_comment_details($comment_id);
            wp_send_json_success($comment);
        }
        
        wp_send_json_error('Failed to add comment');
    }
    
    /**
     * Ajax: コメント取得
     */
    public function ajax_get_comments() {
        $map_id = intval($_POST['map_id']);
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 10);
        
        $comments = $this->get_map_comments($map_id, $page, $per_page);
        wp_send_json_success($comments);
    }
    
    /**
     * マップのコメント取得
     */
    public function get_map_comments($map_id, $page = 1, $per_page = 10) {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        $comments = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name, u.user_nicename
             FROM {$this->comments_table} c
             LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
             WHERE c.map_id = %d AND c.parent_id = 0
             ORDER BY c.created_at DESC
             LIMIT %d OFFSET %d",
            $map_id, $per_page, $offset
        ));
        
        $formatted_comments = array();
        foreach ($comments as $comment) {
            $replies = $this->get_comment_replies($comment->id);
            
            $formatted_comments[] = array(
                'id' => $comment->id,
                'content' => $comment->content,
                'rating' => $comment->rating,
                'user_name' => $comment->display_name,
                'user_slug' => $comment->user_nicename,
                'helpful_count' => $comment->helpful_count,
                'created_at' => $comment->created_at,
                'replies' => $replies
            );
        }
        
        return $formatted_comments;
    }
    
    /**
     * コメントの返信取得
     */
    private function get_comment_replies($parent_id) {
        global $wpdb;
        
        $replies = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name, u.user_nicename
             FROM {$this->comments_table} c
             LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
             WHERE c.parent_id = %d
             ORDER BY c.created_at ASC",
            $parent_id
        ));
        
        $formatted_replies = array();
        foreach ($replies as $reply) {
            $formatted_replies[] = array(
                'id' => $reply->id,
                'content' => $reply->content,
                'user_name' => $reply->display_name,
                'user_slug' => $reply->user_nicename,
                'created_at' => $reply->created_at
            );
        }
        
        return $formatted_replies;
    }
    
    /**
     * Ajax: ユーザーフォロー
     */
    public function ajax_follow_user() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $follower_id = get_current_user_id();
        if (!$follower_id) {
            wp_send_json_error('User not logged in');
        }
        
        $following_id = intval($_POST['user_id']);
        $action = sanitize_text_field($_POST['action_type']); // 'follow' or 'unfollow'
        
        if ($follower_id === $following_id) {
            wp_send_json_error('Cannot follow yourself');
        }
        
        global $wpdb;
        
        if ($action === 'follow') {
            $result = $wpdb->insert(
                $this->follows_table,
                array('follower_id' => $follower_id, 'following_id' => $following_id),
                array('%d', '%d')
            );
        } else {
            $result = $wpdb->delete(
                $this->follows_table,
                array('follower_id' => $follower_id, 'following_id' => $following_id),
                array('%d', '%d')
            );
        }
        
        if ($result) {
            $following_count = $this->get_following_count($following_id);
            wp_send_json_success(array(
                'following' => ($action === 'follow'),
                'followers_count' => $following_count
            ));
        }
        
        wp_send_json_error('Failed to update follow status');
    }
    
    /**
     * Ajax: 学習グループ作成
     */
    public function ajax_create_study_group() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $name = sanitize_text_field($_POST['name']);
        $description = sanitize_textarea_field($_POST['description']);
        $category = sanitize_text_field($_POST['category']);
        $max_members = intval($_POST['max_members'] ?? 50);
        $is_private = isset($_POST['is_private']) ? 1 : 0;
        $target_exam_date = sanitize_text_field($_POST['target_exam_date'] ?? '');
        
        if (empty($name)) {
            wp_send_json_error('Group name is required');
        }
        
        // 参加コード生成（プライベートグループの場合）
        $join_code = $is_private ? $this->generate_join_code() : null;
        
        global $wpdb;
        $result = $wpdb->insert(
            $this->groups_table,
            array(
                'name' => $name,
                'description' => $description,
                'creator_id' => $user_id,
                'category' => $category,
                'max_members' => $max_members,
                'is_private' => $is_private,
                'join_code' => $join_code,
                'target_exam_date' => $target_exam_date ?: null
            ),
            array('%s', '%s', '%d', '%s', '%d', '%d', '%s', '%s')
        );
        
        if ($result) {
            $group_id = $wpdb->insert_id;
            
            // 作成者をグループに追加（管理者として）
            $wpdb->insert(
                $this->group_members_table,
                array(
                    'group_id' => $group_id,
                    'user_id' => $user_id,
                    'role' => 'admin'
                ),
                array('%d', '%d', '%s')
            );
            
            wp_send_json_success(array(
                'group_id' => $group_id,
                'join_code' => $join_code,
                'group_url' => $this->get_group_url($group_id)
            ));
        }
        
        wp_send_json_error('Failed to create group');
    }
    
    /**
     * Ajax: 学習グループ参加
     */
    public function ajax_join_study_group() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $group_id = intval($_POST['group_id'] ?? 0);
        $join_code = sanitize_text_field($_POST['join_code'] ?? '');
        
        global $wpdb;
        
        // グループ情報を取得
        $group = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->groups_table} WHERE id = %d",
            $group_id
        ));
        
        if (!$group) {
            wp_send_json_error('Group not found');
        }
        
        // プライベートグループの場合は参加コードを確認
        if ($group->is_private && $group->join_code !== $join_code) {
            wp_send_json_error('Invalid join code');
        }
        
        // 既に参加済みかチェック
        $existing_member = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->group_members_table} WHERE group_id = %d AND user_id = %d",
            $group_id, $user_id
        ));
        
        if ($existing_member) {
            wp_send_json_error('Already a member of this group');
        }
        
        // メンバー数制限チェック
        $current_members = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->group_members_table} WHERE group_id = %d",
            $group_id
        ));
        
        if ($current_members >= $group->max_members) {
            wp_send_json_error('Group is full');
        }
        
        // グループに参加
        $result = $wpdb->insert(
            $this->group_members_table,
            array(
                'group_id' => $group_id,
                'user_id' => $user_id,
                'role' => 'member'
            ),
            array('%d', '%d', '%s')
        );
        
        if ($result) {
            wp_send_json_success(array(
                'group_name' => $group->name,
                'group_url' => $this->get_group_url($group_id)
            ));
        }
        
        wp_send_json_error('Failed to join group');
    }
    
    /**
     * Ajax: 学習グループ一覧取得
     */
    public function ajax_get_study_groups() {
        $category = sanitize_text_field($_POST['category'] ?? '');
        $search = sanitize_text_field($_POST['search'] ?? '');
        $user_groups_only = isset($_POST['user_groups_only']);
        
        $groups = $this->get_study_groups($category, $search, $user_groups_only);
        wp_send_json_success($groups);
    }
    
    /**
     * 学習グループ一覧取得
     */
    public function get_study_groups($category = '', $search = '', $user_groups_only = false) {
        global $wpdb;
        
        $where_conditions = array();
        $params = array();
        
        if ($user_groups_only) {
            $user_id = get_current_user_id();
            if (!$user_id) return array();
            
            $where_conditions[] = 'gm.user_id = %d';
            $params[] = $user_id;
        }
        
        if (!empty($category)) {
            $where_conditions[] = 'g.category = %s';
            $params[] = $category;
        }
        
        if (!empty($search)) {
            $where_conditions[] = '(g.name LIKE %s OR g.description LIKE %s)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        
        $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
        
        $join_clause = $user_groups_only ? 
            "INNER JOIN {$this->group_members_table} gm ON g.id = gm.group_id" :
            "LEFT JOIN {$this->group_members_table} gm ON g.id = gm.group_id";
        
        $sql = "SELECT g.*, 
                       u.display_name as creator_name,
                       COUNT(DISTINCT gm.user_id) as member_count,
                       MAX(gm.last_active) as last_activity
                FROM {$this->groups_table} g
                LEFT JOIN {$wpdb->users} u ON g.creator_id = u.ID
                {$join_clause}
                {$where_clause}
                GROUP BY g.id
                ORDER BY g.created_at DESC";
        
        $groups = empty($params) ? $wpdb->get_results($sql) : $wpdb->get_results($wpdb->prepare($sql, $params));
        
        $formatted_groups = array();
        foreach ($groups as $group) {
            $formatted_groups[] = array(
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'category' => $group->category,
                'creator_name' => $group->creator_name,
                'member_count' => $group->member_count,
                'max_members' => $group->max_members,
                'is_private' => $group->is_private,
                'target_exam_date' => $group->target_exam_date,
                'last_activity' => $group->last_activity,
                'created_at' => $group->created_at,
                'group_url' => $this->get_group_url($group->id)
            );
        }
        
        return $formatted_groups;
    }
    
    /**
     * Ajax: 進捗共有
     */
    public function ajax_share_progress() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $achievement_type = sanitize_text_field($_POST['achievement_type']);
        $achievement_data = sanitize_textarea_field($_POST['achievement_data']);
        $share_to_groups = array_map('intval', $_POST['share_to_groups'] ?? array());
        
        // 進捗データを処理・保存
        $achievement_id = $this->save_achievement($user_id, $achievement_type, $achievement_data);
        
        if ($achievement_id) {
            // 指定されたグループに共有
            foreach ($share_to_groups as $group_id) {
                $this->share_achievement_to_group($achievement_id, $group_id);
            }
            
            wp_send_json_success('Progress shared successfully');
        }
        
        wp_send_json_error('Failed to share progress');
    }
    
    /**
     * Ajax: リーダーボード取得
     */
    public function ajax_get_leaderboard() {
        $type = sanitize_text_field($_POST['type'] ?? 'weekly');
        $category = sanitize_text_field($_POST['category'] ?? 'all');
        
        $leaderboard = $this->get_leaderboard($type, $category);
        wp_send_json_success($leaderboard);
    }
    
    /**
     * リーダーボード取得
     */
    public function get_leaderboard($type = 'weekly', $category = 'all') {
        global $wpdb;
        
        $date_condition = '';
        switch ($type) {
            case 'daily':
                $date_condition = 'AND DATE(up.last_studied) = CURDATE()';
                break;
            case 'weekly':
                $date_condition = 'AND up.last_studied >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
                break;
            case 'monthly':
                $date_condition = 'AND up.last_studied >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
                break;
            case 'all_time':
            default:
                $date_condition = '';
                break;
        }
        
        $category_condition = '';
        if ($category !== 'all') {
            $category_condition = 'AND m.category = %s';
        }
        
        $sql = "SELECT u.ID, u.display_name, u.user_nicename,
                       COUNT(DISTINCT up.node_id) as completed_nodes,
                       SUM(up.total_study_time) as total_study_time,
                       AVG(up.mastery_level) as avg_mastery,
                       COUNT(DISTINCT m.id) as maps_studied,
                       SUM(CASE WHEN up.status = 'completed' THEN 1 ELSE 0 END) as achievements
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->prefix}gyosei_user_progress up ON u.ID = up.user_id
                INNER JOIN {$wpdb->prefix}gyosei_mindmaps m ON up.map_id = m.id
                WHERE 1=1 {$date_condition} {$category_condition}
                GROUP BY u.ID
                ORDER BY (completed_nodes * 0.3 + total_study_time * 0.2 + avg_mastery * 0.3 + achievements * 0.2) DESC
                LIMIT 50";
        
        $params = array();
        if ($category !== 'all') {
            $params[] = $category;
        }
        
        $results = empty($params) ? $wpdb->get_results($sql) : $wpdb->get_results($wpdb->prepare($sql, $params));
        
        $leaderboard = array();
        $rank = 1;
        foreach ($results as $user) {
            $leaderboard[] = array(
                'rank' => $rank++,
                'user_id' => $user->ID,
                'user_name' => $user->display_name,
                'user_slug' => $user->user_nicename,
                'completed_nodes' => $user->completed_nodes,
                'study_time' => $user->total_study_time,
                'avg_mastery' => round($user->avg_mastery, 1),
                'maps_studied' => $user->maps_studied,
                'achievements' => $user->achievements,
                'score' => round($user->completed_nodes * 0.3 + $user->total_study_time * 0.2 + $user->avg_mastery * 0.3 + $user->achievements * 0.2, 1)
            );
        }
        
        return $leaderboard;
    }
    
    /**
     * コミュニティマップショートコード
     */
    public function community_maps_shortcode($atts) {
        $atts = shortcode_atts(array(
            'per_page' => 12,
            'category' => '',
            'show_search' => 'true',
            'show_filters' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <div id="community-maps-container" class="gyosei-community-maps">
            <?php if ($atts['show_search'] === 'true'): ?>
            <div class="community-search-bar">
                <input type="text" id="community-search" placeholder="マップを検索...">
                <button id="community-search-btn">検索</button>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_filters'] === 'true'): ?>
            <div class="community-filters">
                <select id="community-category-filter">
                    <option value="">全カテゴリ</option>
                    <option value="gyosei">行政法</option>
                    <option value="minpo">民法</option>
                    <option value="kenpou">憲法</option>
                    <option value="shoken">商法・会社法</option>
                </select>
                
                <select id="community-sort-filter">
                    <option value="newest">新着順</option>
                    <option value="popular">人気順</option>
                    <option value="most_liked">いいね数順</option>
                    <option value="most_viewed">閲覧数順</option>
                </select>
            </div>
            <?php endif; ?>
            
            <div id="community-maps-grid" class="maps-grid">
                <!-- マップカードがここに動的に読み込まれます -->
            </div>
            
            <div id="community-pagination" class="pagination">
                <!-- ページネーションがここに表示されます -->
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // コミュニティマップの初期化
            initCommunityMaps();
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * 学習グループショートコード
     */
    public function study_groups_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_user_groups' => 'false',
            'category' => ''
        ), $atts);
        
        ob_start();
        ?>
        <div id="study-groups-container" class="gyosei-study-groups">
            <div class="groups-header">
                <h3>学習グループ</h3>
                <?php if (is_user_logged_in()): ?>
                <button id="create-group-btn" class="btn-primary">新しいグループを作成</button>
                <?php endif; ?>
            </div>
            
            <div class="groups-filters">
                <input type="text" id="groups-search" placeholder="グループを検索...">
                <select id="groups-category">
                    <option value="">全カテゴリ</option>
                    <option value="gyosei">行政法</option>
                    <option value="minpo">民法</option>
                    <option value="kenpou">憲法</option>
                    <option value="general">一般</option>
                </select>
                <?php if (is_user_logged_in()): ?>
                <label>
                    <input type="checkbox" id="my-groups-only"> 参加中のグループのみ
                </label>
                <?php endif; ?>
            </div>
            
            <div id="groups-list" class="groups-list">
                <!-- グループリストがここに表示されます -->
            </div>
        </div>
        
        <!-- グループ作成モーダル -->
        <?php if (is_user_logged_in()): ?>
        <div id="create-group-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3>新しい学習グループを作成</h3>
                <form id="create-group-form">
                    <div class="form-group">
                        <label>グループ名 *</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>説明</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>カテゴリ</label>
                        <select name="category">
                            <option value="general">一般</option>
                            <option value="gyosei">行政法</option>
                            <option value="minpo">民法</option>
                            <option value="kenpou">憲法</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>最大メンバー数</label>
                        <input type="number" name="max_members" value="50" min="2" max="200">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_private"> プライベートグループ（参加コードが必要）
                        </label>
                    </div>
                    <div class="form-group">
                        <label>目標試験日</label>
                        <input type="date" name="target_exam_date">
                    </div>
                    <button type="submit" class="btn-primary">グループを作成</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }
    
    /**
     * リーダーボードショートコード
     */
    public function leaderboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'weekly',
            'category' => 'all',
            'show_filters' => 'true'
        ), $atts);
        
        ob_start();
        ?>
        <div id="leaderboard-container" class="gyosei-leaderboard">
            <div class="leaderboard-header">
                <h3>学習ランキング</h3>
                <?php if ($atts['show_filters'] === 'true'): ?>
                <div class="leaderboard-filters">
                    <select id="leaderboard-type">
                        <option value="daily">今日</option>
                        <option value="weekly" selected>今週</option>
                        <option value="monthly">今月</option>
                        <option value="all_time">全期間</option>
                    </select>
                    <select id="leaderboard-category">
                        <option value="all">全科目</option>
                        <option value="gyosei">行政法</option>
                        <option value="minpo">民法</option>
                        <option value="kenpou">憲法</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
            
            <div id="leaderboard-table">
                <!-- リーダーボードテーブルがここに表示されます -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * ヘルパーメソッド
     */
    private function get_map_preview_url($map_id) {
        return home_url("/mindmap-preview/{$map_id}");
    }
    
    private function parse_tags($tags_string) {
        if (empty($tags_string)) return array();
        return array_filter(array_map('trim', explode(',', $tags_string)));
    }
    
    private function get_following_count($user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->follows_table} WHERE following_id = %d",
            $user_id
        ));
    }
    
    private function generate_join_code() {
        return strtoupper(wp_generate_password(8, false));
    }
    
    private function get_group_url($group_id) {
        return home_url("/study-group/{$group_id}");
    }
    
    private function get_comment_details($comment_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, u.display_name, u.user_nicename
             FROM {$this->comments_table} c
             LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID
             WHERE c.id = %d",
            $comment_id
        ));
    }
    
    private function save_achievement($user_id, $type, $data) {
        // 実績データの保存処理
        // 今後の実装で詳細化
        return true;
    }
    
    private function share_achievement_to_group($achievement_id, $group_id) {
        // グループへの実績共有処理
        // 今後の実装で詳細化
        return true;
    }
}