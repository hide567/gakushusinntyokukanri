<?php
/**
 * 行政書士の道 - マインドマップ Phase 3-B
 * コミュニティ機能（マップ共有・評価・学習グループ）
 */

class GyoseiMindMapPhase3B extends GyoseiMindMapPhase3A {
    
    public function __construct() {
        parent::__construct();
        
        // Phase 3-B の追加機能
        add_action('wp_ajax_rate_map', array($this, 'ajax_rate_map'));
        add_action('wp_ajax_nopriv_rate_map', array($this, 'ajax_rate_map'));
        add_action('wp_ajax_add_map_comment', array($this, 'ajax_add_map_comment'));
        add_action('wp_ajax_nopriv_add_map_comment', array($this, 'ajax_add_map_comment'));
        add_action('wp_ajax_get_map_comments', array($this, 'ajax_get_map_comments'));
        add_action('wp_ajax_nopriv_get_map_comments', array($this, 'ajax_get_map_comments'));
        add_action('wp_ajax_create_study_group', array($this, 'ajax_create_study_group'));
        add_action('wp_ajax_join_study_group', array($this, 'ajax_join_study_group'));
        add_action('wp_ajax_get_study_groups', array($this, 'ajax_get_study_groups'));
        add_action('wp_ajax_follow_user', array($this, 'ajax_follow_user'));
        add_action('wp_ajax_get_user_activity', array($this, 'ajax_get_user_activity'));
        
        // 追加テーブル作成
        register_activation_hook(__FILE__, array($this, 'create_community_tables'));
        
        // ショートコード追加
        add_shortcode('mindmap_community', array($this, 'community_page_shortcode'));
        add_shortcode('mindmap_leaderboard', array($this, 'leaderboard_shortcode'));
    }
    
    // コミュニティ機能用テーブル作成
    public function create_community_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // マップ評価テーブル
        $ratings_table = $wpdb->prefix . 'gyosei_map_ratings';
        $sql_ratings = "CREATE TABLE $ratings_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            map_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            rating tinyint(1) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY map_user (map_id, user_id),
            KEY map_id (map_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // コメントテーブル
        $comments_table = $wpdb->prefix . 'gyosei_map_comments';
        $sql_comments = "CREATE TABLE $comments_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            map_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            comment_text text NOT NULL,
            parent_id mediumint(9) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY map_id (map_id),
            KEY user_id (user_id),
            KEY parent_id (parent_id)
        ) $charset_collate;";
        
        // 学習グループテーブル
        $groups_table = $wpdb->prefix . 'gyosei_study_groups';
        $sql_groups = "CREATE TABLE $groups_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            group_name varchar(255) NOT NULL,
            group_description text,
            creator_id bigint(20) NOT NULL,
            is_public tinyint(1) DEFAULT 1,
            max_members int DEFAULT 50,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY creator_id (creator_id),
            KEY is_public (is_public)
        ) $charset_collate;";
        
        // グループメンバーテーブル
        $group_members_table = $wpdb->prefix . 'gyosei_group_members';
        $sql_group_members = "CREATE TABLE $group_members_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            group_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            role varchar(20) DEFAULT 'member',
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY group_user (group_id, user_id),
            KEY group_id (group_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // ユーザーフォローテーブル
        $follows_table = $wpdb->prefix . 'gyosei_user_follows';
        $sql_follows = "CREATE TABLE $follows_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            follower_id bigint(20) NOT NULL,
            following_id bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY follower_following (follower_id, following_id),
            KEY follower_id (follower_id),
            KEY following_id (following_id)
        ) $charset_collate;";
        
        // アクティビティログテーブル
        $activity_table = $wpdb->prefix . 'gyosei_user_activity';
        $sql_activity = "CREATE TABLE $activity_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            activity_type varchar(50) NOT NULL,
            activity_data text,
            target_id mediumint(9),
            target_type varchar(50),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY activity_type (activity_type),
            KEY target_id (target_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_ratings);
        dbDelta($sql_comments);
        dbDelta($sql_groups);
        dbDelta($sql_group_members);
        dbDelta($sql_follows);
        dbDelta($sql_activity);
    }
    
    // 拡張されたマインドマップショートコード
    public function mindmap_shortcode($atts) {
        $atts = shortcode_atts(array(
            'data' => 'gyosei',
            'title' => '行政法',
            'width' => '100%',
            'height' => '400px',
            'search' => 'true',
            'details' => 'true',
            'draggable' => 'false',
            'editable' => 'false',
            'custom_id' => '',
            'community' => 'true',
            'show_rating' => 'true',
            'show_comments' => 'true'
        ), $atts);
        
        $output = parent::mindmap_shortcode($atts);
        
        // コミュニティ機能を追加
        if ($atts['community'] === 'true' && $atts['custom_id']) {
            $output .= $this->render_community_features($atts['custom_id'], $atts);
        }
        
        return $output;
    }
    
    // コミュニティ機能のレンダリング
    private function render_community_features($map_id, $atts) {
        $map_stats = $this->get_map_stats($map_id);
        $current_user_rating = $this->get_user_rating($map_id, get_current_user_id());
        
        ob_start();
        ?>
        <div class="mindmap-community-section" data-map-id="<?php echo esc_attr($map_id); ?>">
            
            <?php if ($atts['show_rating'] === 'true'): ?>
            <!-- 評価セクション -->
            <div class="mindmap-rating-section">
                <div class="rating-display">
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star <?php echo $i <= $map_stats['avg_rating'] ? 'filled' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <div class="rating-info">
                        <span class="rating-score"><?php echo number_format($map_stats['avg_rating'], 1); ?></span>
                        <span class="rating-count">(<?php echo $map_stats['rating_count']; ?>件の評価)</span>
                    </div>
                </div>
                
                <?php if (is_user_logged_in()): ?>
                <div class="user-rating">
                    <span>あなたの評価:</span>
                    <div class="rating-input" data-current-rating="<?php echo $current_user_rating; ?>">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button class="star-btn <?php echo $i <= $current_user_rating ? 'selected' : ''; ?>" 
                                data-rating="<?php echo $i; ?>">★</button>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_comments'] === 'true'): ?>
            <!-- コメントセクション -->
            <div class="mindmap-comments-section">
                <div class="comments-header">
                    <h4>コメント <span class="comment-count">(<?php echo $map_stats['comment_count']; ?>)</span></h4>
                    <?php if (is_user_logged_in()): ?>
                    <button class="btn btn-primary btn-sm" data-action="add-comment">💬 コメント追加</button>
                    <?php endif; ?>
                </div>
                
                <?php if (is_user_logged_in()): ?>
                <div class="comment-form" style="display: none;">
                    <textarea class="comment-input" placeholder="コメントを入力してください..." rows="3"></textarea>
                    <div class="comment-actions">
                        <button class="btn btn-primary btn-sm" data-action="submit-comment">投稿</button>
                        <button class="btn btn-secondary btn-sm" data-action="cancel-comment">キャンセル</button>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="comments-list">
                    <!-- コメントはAjaxで動的に読み込み -->
                </div>
            </div>
            <?php endif; ?>
            
            <!-- 共有・フォロー機能 -->
            <div class="mindmap-social-actions">
                <button class="btn btn-outline share-btn" data-action="share-map">
                    🔗 共有
                </button>
                
                <?php 
                $map_data = $this->get_custom_map_data($map_id);
                if ($map_data && is_user_logged_in() && $map_data['author_id'] != get_current_user_id()): 
                ?>
                <button class="btn btn-outline follow-btn" data-action="follow-author" 
                        data-author-id="<?php echo $map_data['author_id']; ?>">
                    👤 作成者をフォロー
                </button>
                <?php endif; ?>
                
                <button class="btn btn-outline" data-action="add-to-group">
                    👥 グループに追加
                </button>
            </div>
        </div>
        
        <!-- コメント詳細モーダル -->
        <div class="comments-modal" id="comments-modal-<?php echo esc_attr($map_id); ?>" style="display: none;">
            <div class="mindmap-modal-overlay"></div>
            <div class="mindmap-modal-content">
                <div class="mindmap-modal-header">
                    <h3 class="mindmap-modal-title">コメント一覧</h3>
                    <button class="mindmap-modal-close">✕</button>
                </div>
                <div class="mindmap-modal-body">
                    <div class="comments-container">
                        <!-- コメント一覧がここに表示される -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 学習グループモーダル -->
        <div class="study-groups-modal" id="study-groups-modal-<?php echo esc_attr($map_id); ?>" style="display: none;">
            <div class="mindmap-modal-overlay"></div>
            <div class="mindmap-modal-content">
                <div class="mindmap-modal-header">
                    <h3 class="mindmap-modal-title">学習グループ</h3>
                    <button class="mindmap-modal-close">✕</button>
                </div>
                <div class="mindmap-modal-body">
                    <div class="groups-tabs">
                        <button class="tab-btn active" data-tab="my-groups">参加中</button>
                        <button class="tab-btn" data-tab="public-groups">公開グループ</button>
                        <button class="tab-btn" data-tab="create-group">新規作成</button>
                    </div>
                    
                    <div class="tab-content" id="my-groups">
                        <div class="groups-list">
                            <!-- 参加中のグループ一覧 -->
                        </div>
                    </div>
                    
                    <div class="tab-content" id="public-groups" style="display: none;">
                        <div class="groups-search">
                            <input type="text" placeholder="グループを検索..." class="group-search-input">
                        </div>
                        <div class="groups-list">
                            <!-- 公開グループ一覧 -->
                        </div>
                    </div>
                    
                    <div class="tab-content" id="create-group" style="display: none;">
                        <form class="create-group-form">
                            <div class="form-group">
                                <label for="group-name">グループ名</label>
                                <input type="text" id="group-name" name="group_name" required>
                            </div>
                            <div class="form-group">
                                <label for="group-description">説明</label>
                                <textarea id="group-description" name="group_description" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_public" value="1" checked>
                                    公開グループにする
                                </label>
                            </div>
                            <div class="form-group">
                                <label for="max-members">最大メンバー数</label>
                                <input type="number" id="max-members" name="max_members" value="50" min="2" max="200">
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">グループを作成</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // コミュニティページのショートコード
    public function community_page_shortcode($atts) {
        $atts = shortcode_atts(array(
            'view' => 'dashboard',
            'per_page' => 12
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>コミュニティ機能を利用するにはログインが必要です。</p>';
        }
        
        ob_start();
        ?>
        <div class="mindmap-community-page">
            <div class="community-nav">
                <button class="nav-btn active" data-view="dashboard">ダッシュボード</button>
                <button class="nav-btn" data-view="following">フォロー中</button>
                <button class="nav-btn" data-view="popular">人気マップ</button>
                <button class="nav-btn" data-view="groups">学習グループ</button>
                <button class="nav-btn" data-view="activity">アクティビティ</button>
            </div>
            
            <div class="community-content">
                <div class="view-content" id="dashboard-view">
                    <!-- ダッシュボード内容 -->
                    <div class="dashboard-widgets">
                        <div class="widget">
                            <h3>最近の活動</h3>
                            <div class="recent-activity"></div>
                        </div>
                        <div class="widget">
                            <h3>おすすめマップ</h3>
                            <div class="recommended-maps"></div>
                        </div>
                        <div class="widget">
                            <h3>学習統計</h3>
                            <div class="study-stats"></div>
                        </div>
                    </div>
                </div>
                
                <div class="view-content" id="following-view" style="display: none;">
                    <!-- フォロー中のユーザーの活動 -->
                </div>
                
                <div class="view-content" id="popular-view" style="display: none;">
                    <!-- 人気マップ一覧 -->
                </div>
                
                <div class="view-content" id="groups-view" style="display: none;">
                    <!-- 学習グループ管理 -->
                </div>
                
                <div class="view-content" id="activity-view" style="display: none;">
                    <!-- アクティビティログ -->
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // リーダーボードショートコード
    public function leaderboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'monthly',
            'limit' => 10,
            'metric' => 'progress'
        ), $atts);
        
        $leaderboard_data = $this->get_leaderboard_data($atts['type'], $atts['metric'], $atts['limit']);
        
        ob_start();
        ?>
        <div class="mindmap-leaderboard">
            <div class="leaderboard-header">
                <h3>ランキング</h3>
                <div class="leaderboard-filters">
                    <select class="period-select" data-current="<?php echo esc_attr($atts['type']); ?>">
                        <option value="weekly">週間</option>
                        <option value="monthly" selected>月間</option>
                        <option value="all-time">全期間</option>
                    </select>
                    <select class="metric-select" data-current="<?php echo esc_attr($atts['metric']); ?>">
                        <option value="progress">学習進捗</option>
                        <option value="maps_created">作成マップ数</option>
                        <option value="community_score">コミュニティスコア</option>
                    </select>
                </div>
            </div>
            
            <div class="leaderboard-list">
                <?php foreach ($leaderboard_data as $index => $user): ?>
                <div class="leaderboard-item rank-<?php echo $index + 1; ?>">
                    <div class="rank-number"><?php echo $index + 1; ?></div>
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo get_avatar($user['user_id'], 40); ?>
                        </div>
                        <div class="user-details">
                            <div class="user-name"><?php echo esc_html($user['display_name']); ?></div>
                            <div class="user-score"><?php echo esc_html($user['score']); ?> pts</div>
                        </div>
                    </div>
                    <div class="user-badge">
                        <?php echo $this->get_user_badge($user['user_id']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Ajax: マップ評価
    public function ajax_rate_map() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        $map_id = intval($_POST['map_id']);
        $rating = intval($_POST['rating']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('ログインが必要です');
        }
        
        if ($rating < 1 || $rating > 5) {
            wp_send_json_error('無効な評価値です');
        }
        
        global $wpdb;
        $ratings_table = $wpdb->prefix . 'gyosei_map_ratings';
        
        // 既存の評価を更新または新規作成
        $result = $wpdb->replace(
            $ratings_table,
            array(
                'map_id' => $map_id,
                'user_id' => $user_id,
                'rating' => $rating
            ),
            array('%d', '%d', '%d')
        );
        
        if ($result) {
            // アクティビティを記録
            $this->log_user_activity($user_id, 'rate_map', array(
                'map_id' => $map_id,
                'rating' => $rating
            ));
            
            // 更新された統計を取得
            $stats = $this->get_map_stats($map_id);
            
            wp_send_json_success(array(
                'message' => '評価を保存しました',
                'stats' => $stats
            ));
        } else {
            wp_send_json_error('評価の保存に失敗しました');
        }
    }
    
    // Ajax: コメント追加
    public function ajax_add_map_comment() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        $map_id = intval($_POST['map_id']);
        $comment_text = sanitize_textarea_field($_POST['comment_text']);
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error('ログインが必要です');
        }
        
        if (empty(trim($comment_text))) {
            wp_send_json_error('コメントを入力してください');
        }
        
        global $wpdb;
        $comments_table = $wpdb->prefix . 'gyosei_map_comments';
        
        $result = $wpdb->insert(
            $comments_table,
            array(
                'map_id' => $map_id,
                'user_id' => $user_id,
                'comment_text' => $comment_text,
                'parent_id' => $parent_id
            ),
            array('%d', '%d', '%s', '%d')
        );
        
        if ($result) {
            $comment_id = $wpdb->insert_id;
            
            // アクティビティを記録
            $this->log_user_activity($user_id, 'comment_map', array(
                'map_id' => $map_id,
                'comment_id' => $comment_id
            ));
            
            // コメント情報を取得
            $comment_data = $this->get_comment_data($comment_id);
            
            wp_send_json_success(array(
                'message' => 'コメントを投稿しました',
                'comment' => $comment_data
            ));
        } else {
            wp_send_json_error('コメントの投稿に失敗しました');
        }
    }
    
    // Ajax: コメント取得
    public function ajax_get_map_comments() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        $map_id = intval($_POST['map_id']);
        $page = intval($_POST['page'] ?? 1);
        $per_page = 10;
        
        $comments = $this->get_map_comments($map_id, $page, $per_page);
        
        wp_send_json_success($comments);
    }
    
    // Ajax: 学習グループ作成
    public function ajax_create_study_group() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('ログインが必要です');
        }
        
        $user_id = get_current_user_id();
        $group_name = sanitize_text_field($_POST['group_name']);
        $group_description = sanitize_textarea_field($_POST['group_description']);
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $max_members = intval($_POST['max_members']) ?: 50;
        
        if (empty(trim($group_name))) {
            wp_send_json_error('グループ名を入力してください');
        }
        
        global $wpdb;
        $groups_table = $wpdb->prefix . 'gyosei_study_groups';
        $members_table = $wpdb->prefix . 'gyosei_group_members';
        
        // グループ作成
        $result = $wpdb->insert(
            $groups_table,
            array(
                'group_name' => $group_name,
                'group_description' => $group_description,
                'creator_id' => $user_id,
                'is_public' => $is_public,
                'max_members' => $max_members
            ),
            array('%s', '%s', '%d', '%d', '%d')
        );
        
        if ($result) {
            $group_id = $wpdb->insert_id;
            
            // 作成者をグループに追加（管理者として）
            $wpdb->insert(
                $members_table,
                array(
                    'group_id' => $group_id,
                    'user_id' => $user_id,
                    'role' => 'admin'
                ),
                array('%d', '%d', '%s')
            );
            
            // アクティビティを記録
            $this->log_user_activity($user_id, 'create_group', array(
                'group_id' => $group_id,
                'group_name' => $group_name
            ));
            
            wp_send_json_success(array(
                'group_id' => $group_id,
                'message' => 'グループが作成されました'
            ));
        } else {
            wp_send_json_error('グループの作成に失敗しました');
        }
    }
    
    // Ajax: 学習グループ参加
    public function ajax_join_study_group() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('ログインが必要です');
        }
        
        $user_id = get_current_user_id();
        $group_id = intval($_POST['group_id']);
        
        // グループの存在確認
        global $wpdb;
        $groups_table = $wpdb->prefix . 'gyosei_study_groups';
        $members_table = $wpdb->prefix . 'gyosei_group_members';
        
        $group = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $groups_table WHERE id = %d",
            $group_id
        ));
        
        if (!$group) {
            wp_send_json_error('グループが見つかりません');
        }
        
        // 既に参加済みかチェック
        $existing_member = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $members_table WHERE group_id = %d AND user_id = %d",
            $group_id, $user_id
        ));
        
        if ($existing_member) {
            wp_send_json_error('既にこのグループに参加しています');
        }
        
        // メンバー数制限チェック
        $current_members = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $members_table WHERE group_id = %d",
            $group_id
        ));
        
        if ($current_members >= $group->max_members) {
            wp_send_json_error('グループのメンバー数が上限に達しています');
        }
        
        // グループに参加
        $result = $wpdb->insert(
            $members_table,
            array(
                'group_id' => $group_id,
                'user_id' => $user_id,
                'role' => 'member'
            ),
            array('%d', '%d', '%s')
        );
        
        if ($result) {
            // アクティビティを記録
            $this->log_user_activity($user_id, 'join_group', array(
                'group_id' => $group_id,
                'group_name' => $group->group_name
            ));
            
            wp_send_json_success('グループに参加しました');
        } else {
            wp_send_json_error('グループへの参加に失敗しました');
        }
    }
    
    // Ajax: 学習グループ一覧取得
    public function ajax_get_study_groups() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        $type = sanitize_text_field($_POST['type'] ?? 'public');
        $user_id = get_current_user_id();
        
        global $wpdb;
        $groups_table = $wpdb->prefix . 'gyosei_study_groups';
        $members_table = $wpdb->prefix . 'gyosei_group_members';
        
        switch ($type) {
            case 'my-groups':
                if (!$user_id) {
                    wp_send_json_error('ログインが必要です');
                }
                
                $groups = $wpdb->get_results($wpdb->prepare(
                    "SELECT g.*, gm.role, 
                            (SELECT COUNT(*) FROM $members_table WHERE group_id = g.id) as member_count
                     FROM $groups_table g 
                     JOIN $members_table gm ON g.id = gm.group_id 
                     WHERE gm.user_id = %d 
                     ORDER BY gm.joined_at DESC",
                    $user_id
                ));
                break;
                
            case 'public':
                $groups = $wpdb->get_results(
                    "SELECT g.*, u.display_name as creator_name,
                            (SELECT COUNT(*) FROM $members_table WHERE group_id = g.id) as member_count
                     FROM $groups_table g 
                     LEFT JOIN {$wpdb->users} u ON g.creator_id = u.ID 
                     WHERE g.is_public = 1 
                     ORDER BY g.created_at DESC 
                     LIMIT 50"
                );
                break;
                
            default:
                wp_send_json_error('無効なタイプです');
        }
        
        wp_send_json_success($groups);
    }
    
    // Ajax: ユーザーフォロー
    public function ajax_follow_user() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('ログインが必要です');
        }
        
        $follower_id = get_current_user_id();
        $following_id = intval($_POST['following_id']);
        
        if ($follower_id === $following_id) {
            wp_send_json_error('自分自身をフォローすることはできません');
        }
        
        global $wpdb;
        $follows_table = $wpdb->prefix . 'gyosei_user_follows';
        
        // 既にフォロー済みかチェック
        $existing_follow = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $follows_table WHERE follower_id = %d AND following_id = %d",
            $follower_id, $following_id
        ));
        
        if ($existing_follow) {
            // アンフォロー
            $wpdb->delete(
                $follows_table,
                array(
                    'follower_id' => $follower_id,
                    'following_id' => $following_id
                ),
                array('%d', '%d')
            );
            
            wp_send_json_success(array(
                'action' => 'unfollowed',
                'message' => 'フォローを解除しました'
            ));
        } else {
            // フォロー
            $result = $wpdb->insert(
                $follows_table,
                array(
                    'follower_id' => $follower_id,
                    'following_id' => $following_id
                ),
                array('%d', '%d')
            );
            
            if ($result) {
                // アクティビティを記録
                $this->log_user_activity($follower_id, 'follow_user', array(
                    'following_id' => $following_id
                ));
                
                wp_send_json_success(array(
                    'action' => 'followed',
                    'message' => 'フォローしました'
                ));
            } else {
                wp_send_json_error('フォローに失敗しました');
            }
        }
    }
    
    // Ajax: ユーザーアクティビティ取得
    public function ajax_get_user_activity() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        $user_id = intval($_POST['user_id'] ?? get_current_user_id());
        $page = intval($_POST['page'] ?? 1);
        $per_page = 20;
        
        if (!$user_id) {
            wp_send_json_error('無効なユーザーIDです');
        }
        
        $activities = $this->get_user_activities($user_id, $page, $per_page);
        
        wp_send_json_success($activities);
    }
    
    // マップ統計を取得
    private function get_map_stats($map_id) {
        global $wpdb;
        
        $ratings_table = $wpdb->prefix . 'gyosei_map_ratings';
        $comments_table = $wpdb->prefix . 'gyosei_map_comments';
        
        // 評価統計
        $rating_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as count, AVG(rating) as avg_rating 
             FROM $ratings_table WHERE map_id = %d",
            $map_id
        ));
        
        // コメント数
        $comment_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $comments_table WHERE map_id = %d",
            $map_id
        ));
        
        return array(
            'rating_count' => $rating_stats->count ?: 0,
            'avg_rating' => $rating_stats->avg_rating ?: 0,
            'comment_count' => $comment_count ?: 0
        );
    }
    
    // ユーザーの評価を取得
    private function get_user_rating($map_id, $user_id) {
        if (!$user_id) return 0;
        
        global $wpdb;
        $ratings_table = $wpdb->prefix . 'gyosei_map_ratings';
        
        $rating = $wpdb->get_var($wpdb->prepare(
            "SELECT rating FROM $ratings_table WHERE map_id = %d AND user_id = %d",
            $map_id, $user_id
        ));
        
        return $rating ?: 0;
    }
    
    // コメントデータを取得
    private function get_comment_data($comment_id) {
        global $wpdb;
        $comments_table = $wpdb->prefix . 'gyosei_map_comments';
        
        $comment = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, u.display_name, u.user_email 
             FROM $comments_table c 
             LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID 
             WHERE c.id = %d",
            $comment_id
        ));
        
        if ($comment) {
            return array(
                'id' => $comment->id,
                'text' => $comment->comment_text,
                'author_name' => $comment->display_name,
                'author_avatar' => get_avatar_url($comment->user_email, 40),
                'created_at' => $comment->created_at,
                'parent_id' => $comment->parent_id
            );
        }
        
        return null;
    }
    
    // マップのコメント一覧を取得
    private function get_map_comments($map_id, $page = 1, $per_page = 10) {
        global $wpdb;
        $comments_table = $wpdb->prefix . 'gyosei_map_comments';
        
        $offset = ($page - 1) * $per_page;
        
        $comments = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name, u.user_email 
             FROM $comments_table c 
             LEFT JOIN {$wpdb->users} u ON c.user_id = u.ID 
             WHERE c.map_id = %d 
             ORDER BY c.created_at DESC 
             LIMIT %d OFFSET %d",
            $map_id, $per_page, $offset
        ));
        
        $formatted_comments = array();
        foreach ($comments as $comment) {
            $formatted_comments[] = array(
                'id' => $comment->id,
                'text' => $comment->comment_text,
                'author_name' => $comment->display_name,
                'author_avatar' => get_avatar_url($comment->user_email, 40),
                'created_at' => $comment->created_at,
                'parent_id' => $comment->parent_id
            );
        }
        
        return $formatted_comments;
    }
    
    // ユーザーアクティビティを取得
    private function get_user_activities($user_id, $page = 1, $per_page = 20) {
        global $wpdb;
        $activity_table = $wpdb->prefix . 'gyosei_user_activity';
        
        $offset = ($page - 1) * $per_page;
        
        $activities = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $activity_table 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $user_id, $per_page, $offset
        ));
        
        $formatted_activities = array();
        foreach ($activities as $activity) {
            $formatted_activities[] = array(
                'id' => $activity->id,
                'type' => $activity->activity_type,
                'data' => json_decode($activity->activity_data, true),
                'target_id' => $activity->target_id,
                'target_type' => $activity->target_type,
                'created_at' => $activity->created_at,
                'description' => $this->format_activity_description($activity)
            );
        }
        
        return $formatted_activities;
    }
    
    // リーダーボードデータを取得
    private function get_leaderboard_data($period, $metric, $limit) {
        global $wpdb;
        
        // 期間の設定
        $date_condition = '';
        switch ($period) {
            case 'weekly':
                $date_condition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'monthly':
                $date_condition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'all-time':
            default:
                $date_condition = '';
                break;
        }
        
        // メトリックに応じたクエリ
        switch ($metric) {
            case 'progress':
                // 学習進捗スコア
                $query = $wpdb->prepare(
                    "SELECT u.ID as user_id, u.display_name, 
                            COALESCE(SUM(CAST(JSON_EXTRACT(um.meta_value, '$.progress') AS UNSIGNED)), 0) as score
                     FROM {$wpdb->users} u
                     LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key LIKE 'mindmap_progress_%'
                     GROUP BY u.ID, u.display_name
                     ORDER BY score DESC
                     LIMIT %d",
                    $limit
                );
                break;
                
            case 'maps_created':
                // 作成マップ数
                $maps_table = $wpdb->prefix . 'gyosei_custom_maps';
                $query = $wpdb->prepare(
                    "SELECT u.ID as user_id, u.display_name, COUNT(m.id) as score
                     FROM {$wpdb->users} u
                     LEFT JOIN $maps_table m ON u.ID = m.user_id $date_condition
                     GROUP BY u.ID, u.display_name
                     ORDER BY score DESC
                     LIMIT %d",
                    $limit
                );
                break;
                
            case 'community_score':
                // コミュニティスコア（評価 + コメント + フォロワー）
                $ratings_table = $wpdb->prefix . 'gyosei_map_ratings';
                $comments_table = $wpdb->prefix . 'gyosei_map_comments';
                $follows_table = $wpdb->prefix . 'gyosei_user_follows';
                
                $query = $wpdb->prepare(
                    "SELECT u.ID as user_id, u.display_name,
                            (COALESCE(r.rating_count, 0) * 2 + 
                             COALESCE(c.comment_count, 0) * 3 + 
                             COALESCE(f.follower_count, 0) * 5) as score
                     FROM {$wpdb->users} u
                     LEFT JOIN (
                         SELECT user_id, COUNT(*) as rating_count 
                         FROM $ratings_table 
                         WHERE 1=1 $date_condition 
                         GROUP BY user_id
                     ) r ON u.ID = r.user_id
                     LEFT JOIN (
                         SELECT user_id, COUNT(*) as comment_count 
                         FROM $comments_table 
                         WHERE 1=1 $date_condition 
                         GROUP BY user_id
                     ) c ON u.ID = c.user_id
                     LEFT JOIN (
                         SELECT following_id, COUNT(*) as follower_count 
                         FROM $follows_table 
                         WHERE 1=1 $date_condition 
                         GROUP BY following_id
                     ) f ON u.ID = f.following_id
                     ORDER BY score DESC
                     LIMIT %d",
                    $limit
                );
                break;
                
            default:
                return array();
        }
        
        return $wpdb->get_results($query, ARRAY_A);
    }
    
    // ユーザーバッジを取得
    private function get_user_badge($user_id) {
        // ユーザーの実績に基づいてバッジを判定
        $maps_created = $this->get_user_maps_count($user_id);
        $community_score = $this->get_user_community_score($user_id);
        
        if ($maps_created >= 10 && $community_score >= 100) {
            return '🏆 マスター';
        } elseif ($maps_created >= 5 || $community_score >= 50) {
            return '⭐ エキスパート';
        } elseif ($maps_created >= 1 || $community_score >= 10) {
            return '🌟 アクティブ';
        } else {
            return '🌱 ビギナー';
        }
    }
    
    // ユーザーアクティビティをログに記録
    private function log_user_activity($user_id, $activity_type, $activity_data = array()) {
        global $wpdb;
        $activity_table = $wpdb->prefix . 'gyosei_user_activity';
        
        $wpdb->insert(
            $activity_table,
            array(
                'user_id' => $user_id,
                'activity_type' => $activity_type,
                'activity_data' => json_encode($activity_data),
                'target_id' => $activity_data['map_id'] ?? null,
                'target_type' => 'mindmap'
            ),
            array('%d', '%s', '%s', '%d', '%s')
        );
    }
    
    // アクティビティの説明文を生成
    private function format_activity_description($activity) {
        $data = json_decode($activity->activity_data, true);
        
        switch ($activity->activity_type) {
            case 'create_map':
                return 'マインドマップ「' . ($data['title'] ?? '') . '」を作成しました';
            case 'rate_map':
                return 'マインドマップに' . $data['rating'] . 'つ星の評価をしました';
            case 'comment_map':
                return 'マインドマップにコメントしました';
            case 'follow_user':
                return 'ユーザーをフォローしました';
            case 'create_group':
                return '学習グループ「' . ($data['group_name'] ?? '') . '」を作成しました';
            case 'join_group':
                return '学習グループ「' . ($data['group_name'] ?? '') . '」に参加しました';
            default:
                return 'アクティビティが記録されました';
        }
    }
    
    // ユーザーの作成マップ数を取得
    private function get_user_maps_count($user_id) {
        global $wpdb;
        $maps_table = $wpdb->prefix . 'gyosei_custom_maps';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $maps_table WHERE user_id = %d",
            $user_id
        )) ?: 0;
    }
    
    // ユーザーのコミュニティスコアを取得
    private function get_user_community_score($user_id) {
        global $wpdb;
        
        $ratings_table = $wpdb->prefix . 'gyosei_map_ratings';
        $comments_table = $wpdb->prefix . 'gyosei_map_comments';
        $follows_table = $wpdb->prefix . 'gyosei_user_follows';
        
        $rating_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $ratings_table WHERE user_id = %d",
            $user_id
        )) ?: 0;
        
        $comment_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $comments_table WHERE user_id = %d",
            $user_id
        )) ?: 0;
        
        $follower_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $follows_table WHERE following_id = %d",
            $user_id
        )) ?: 0;
        
        return ($rating_count * 2) + ($comment_count * 3) + ($follower_count * 5);
    }
    
    // カスタムマップデータを取得（拡張版）
    protected function get_custom_map_data($custom_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gyosei_custom_maps';
        
        $map = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, u.display_name as author_name 
             FROM $table_name m 
             LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID 
             WHERE m.id = %d",
            $custom_id
        ));
        
        if ($map) {
            // 公開マップまたは自分のマップのみアクセス可能
            if ($map->is_public || (is_user_logged_in() && $map->user_id == get_current_user_id())) {
                return array(
                    'title' => $map->map_title,
                    'data' => json_decode($map->map_data, true),
                    'settings' => json_decode($map->map_settings, true),
                    'author_id' => $map->user_id,
                    'author_name' => $map->author_name,
                    'is_public' => $map->is_public,
                    'created_at' => $map->created_at
                );
            }
        }
        
        return false;
    }
}

// Phase 3-B の初期化
if (!class_exists('GyoseiMindMapPhase3A')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>行政書士の道 マインドマップ: Phase 3-A が必要です。</p></div>';
    });
} else {
    new GyoseiMindMapPhase3B();
}
?>