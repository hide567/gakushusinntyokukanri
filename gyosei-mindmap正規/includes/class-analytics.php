<?php
/**
 * 行政書士の道 - 統計・分析クラス
 * 進捗可視化・レポート生成・達成度バッジシステム
 * File: includes/class-analytics.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class GyoseiAnalytics {
    
    private $analytics_table;
    private $badges_table;
    private $user_badges_table;
    private $achievements_table;
    
    public function __construct() {
        global $wpdb;
        $this->analytics_table = $wpdb->prefix . 'gyosei_analytics';
        $this->badges_table = $wpdb->prefix . 'gyosei_badges';
        $this->user_badges_table = $wpdb->prefix . 'gyosei_user_badges';
        $this->achievements_table = $wpdb->prefix . 'gyosei_achievements';
        
        // Ajax フック
        add_action('wp_ajax_get_progress_report', array($this, 'ajax_get_progress_report'));
        add_action('wp_ajax_get_analytics_dashboard', array($this, 'ajax_get_analytics_dashboard'));
        add_action('wp_ajax_generate_study_report', array($this, 'ajax_generate_study_report'));
        add_action('wp_ajax_get_user_badges', array($this, 'ajax_get_user_badges'));
        add_action('wp_ajax_track_activity', array($this, 'ajax_track_activity'));
        add_action('wp_ajax_get_comparative_stats', array($this, 'ajax_get_comparative_stats'));
        
        // 自動バッジ判定
        add_action('gyosei_progress_updated', array($this, 'check_badge_eligibility'), 10, 2);
        add_action('gyosei_map_completed', array($this, 'check_completion_badges'), 10, 2);
        
        // ショートコード
        add_shortcode('progress_dashboard', array($this, 'progress_dashboard_shortcode'));
        add_shortcode('analytics_chart', array($this, 'analytics_chart_shortcode'));
        add_shortcode('user_badges', array($this, 'user_badges_shortcode'));
    }
    
    /**
     * 分析テーブル作成
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // 分析データテーブル
        $analytics_table = $wpdb->prefix . 'gyosei_analytics';
        $sql1 = "CREATE TABLE $analytics_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data text,
            map_id bigint(20),
            node_id varchar(100),
            session_duration int DEFAULT 0,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event_type (event_type),
            KEY timestamp (timestamp),
            KEY map_id (map_id)
        ) $charset_collate;";
        
        // バッジマスターテーブル
        $badges_table = $wpdb->prefix . 'gyosei_badges';
        $sql2 = "CREATE TABLE $badges_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            badge_key varchar(100) NOT NULL UNIQUE,
            name varchar(255) NOT NULL,
            description text,
            icon varchar(50),
            category varchar(50) DEFAULT 'general',
            rarity varchar(20) DEFAULT 'common',
            criteria text NOT NULL,
            points int DEFAULT 10,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category (category),
            KEY rarity (rarity)
        ) $charset_collate;";
        
        // ユーザーバッジテーブル
        $user_badges_table = $wpdb->prefix . 'gyosei_user_badges';
        $sql3 = "CREATE TABLE $user_badges_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            badge_id bigint(20) NOT NULL,
            earned_at datetime DEFAULT CURRENT_TIMESTAMP,
            progress_data text,
            is_featured tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY user_badge (user_id, badge_id),
            KEY user_id (user_id),
            KEY badge_id (badge_id),
            KEY earned_at (earned_at)
        ) $charset_collate;";
        
        // 達成記録テーブル
        $achievements_table = $wpdb->prefix . 'gyosei_achievements';
        $sql4 = "CREATE TABLE $achievements_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            achievement_type varchar(50) NOT NULL,
            achievement_data text,
            points_earned int DEFAULT 0,
            milestone_level int DEFAULT 1,
            achieved_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY achievement_type (achievement_type),
            KEY achieved_at (achieved_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);
        
        // デフォルトバッジの作成
        self::create_default_badges();
    }
    
    /**
     * デフォルトバッジ作成
     */
    private static function create_default_badges() {
        global $wpdb;
        
        $badges_table = $wpdb->prefix . 'gyosei_badges';
        
        $default_badges = array(
            array(
                'badge_key' => 'first_login',
                'name' => 'はじめの一歩',
                'description' => '初回ログインを達成',
                'icon' => '🎯',
                'category' => 'milestone',
                'rarity' => 'common',
                'criteria' => json_encode(array('type' => 'login_count', 'value' => 1)),
                'points' => 5
            ),
            array(
                'badge_key' => 'first_map_complete',
                'name' => 'マップマスター',
                'description' => '初めてマップを完了',
                'icon' => '🗺️',
                'category' => 'study',
                'rarity' => 'common',
                'criteria' => json_encode(array('type' => 'maps_completed', 'value' => 1)),
                'points' => 20
            ),
            array(
                'badge_key' => 'study_streak_7',
                'name' => '7日連続',
                'description' => '7日間連続で学習',
                'icon' => '🔥',
                'category' => 'consistency',
                'rarity' => 'uncommon',
                'criteria' => json_encode(array('type' => 'study_streak', 'value' => 7)),
                'points' => 50
            ),
            array(
                'badge_key' => 'gyosei_master',
                'name' => '行政法マスター',
                'description' => '行政法のすべてのマップを完了',
                'icon' => '⚖️',
                'category' => 'subject',
                'rarity' => 'rare',
                'criteria' => json_encode(array('type' => 'category_complete', 'value' => 'gyosei')),
                'points' => 100
            ),
            array(
                'badge_key' => 'speed_learner',
                'name' => 'スピードラーナー',
                'description' => '1日で5つ以上のノードを完了',
                'icon' => '⚡',
                'category' => 'achievement',
                'rarity' => 'uncommon',
                'criteria' => json_encode(array('type' => 'daily_nodes', 'value' => 5)),
                'points' => 30
            ),
            array(
                'badge_key' => 'community_helper',
                'name' => 'コミュニティヘルパー',
                'description' => '10個以上のコメントを投稿',
                'icon' => '🤝',
                'category' => 'community',
                'rarity' => 'uncommon',
                'criteria' => json_encode(array('type' => 'comments_posted', 'value' => 10)),
                'points' => 40
            )
        );
        
        foreach ($default_badges as $badge) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $badges_table WHERE badge_key = %s",
                $badge['badge_key']
            ));
            
            if (!$existing) {
                $wpdb->insert($badges_table, $badge);
            }
        }
    }
    
    /**
     * Ajax: 進捗レポート取得
     */
    public function ajax_get_progress_report() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $period = sanitize_text_field($_POST['period'] ?? 'week'); // week, month, quarter, year
        $category = sanitize_text_field($_POST['category'] ?? 'all');
        
        $report = $this->generate_progress_report($user_id, $period, $category);
        wp_send_json_success($report);
    }
    
    /**
     * 進捗レポート生成
     */
    private function generate_progress_report($user_id, $period, $category) {
        global $wpdb;
        
        $date_condition = $this->get_date_condition($period);
        $category_condition = ($category !== 'all') ? 
            $wpdb->prepare('AND m.category = %s', $category) : '';
        
        // 基本統計
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT up.node_id) as nodes_studied,
                SUM(CASE WHEN up.status = 'completed' THEN 1 ELSE 0 END) as nodes_completed,
                AVG(up.progress_percent) as avg_progress,
                SUM(up.total_study_time) as total_study_time,
                AVG(up.mastery_level) as avg_mastery
             FROM {$wpdb->prefix}gyosei_user_progress up
             JOIN {$wpdb->prefix}gyosei_mindmaps m ON up.map_id = m.id
             WHERE up.user_id = %d {$date_condition} {$category_condition}",
            $user_id
        ));
        
        // 日別進捗データ
        $daily_progress = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(up.last_studied) as study_date,
                COUNT(DISTINCT up.node_id) as nodes_studied,
                SUM(up.total_study_time) as study_time
             FROM {$wpdb->prefix}gyosei_user_progress up
             JOIN {$wpdb->prefix}gyosei_mindmaps m ON up.map_id = m.id
             WHERE up.user_id = %d {$date_condition} {$category_condition}
             GROUP BY DATE(up.last_studied)
             ORDER BY study_date",
            $user_id
        ));
        
        // カテゴリ別統計
        $category_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                m.category,
                COUNT(DISTINCT up.node_id) as total_nodes,
                SUM(CASE WHEN up.status = 'completed' THEN 1 ELSE 0 END) as completed_nodes,
                AVG(up.progress_percent) as avg_progress
             FROM {$wpdb->prefix}gyosei_user_progress up
             JOIN {$wpdb->prefix}gyosei_mindmaps m ON up.map_id = m.id
             WHERE up.user_id = %d {$date_condition}
             GROUP BY m.category",
            $user_id
        ));
        
        // 学習ストリーク計算
        $study_streak = $this->calculate_study_streak($user_id);
        
        return array(
            'period' => $period,
            'category' => $category,
            'summary' => array(
                'nodes_studied' => $stats->nodes_studied ?? 0,
                'nodes_completed' => $stats->nodes_completed ?? 0,
                'completion_rate' => $stats->nodes_studied > 0 ? 
                    round(($stats->nodes_completed / $stats->nodes_studied) * 100, 1) : 0,
                'avg_progress' => round($stats->avg_progress ?? 0, 1),
                'total_study_time' => $stats->total_study_time ?? 0,
                'avg_mastery' => round($stats->avg_mastery ?? 0, 1),
                'study_streak' => $study_streak
            ),
            'daily_progress' => $daily_progress,
            'category_breakdown' => $category_stats,
            'achievements' => $this->get_recent_achievements($user_id, $period),
            'insights' => $this->generate_insights($stats, $daily_progress, $category_stats)
        );
    }
    
    /**
     * Ajax: 分析ダッシュボード取得
     */
    public function ajax_get_analytics_dashboard() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $dashboard_data = $this->build_analytics_dashboard($user_id);
        wp_send_json_success($dashboard_data);
    }
    
    /**
     * 分析ダッシュボード構築
     */
    private function build_analytics_dashboard($user_id) {
        return array(
            'overview' => $this->get_overview_stats($user_id),
            'charts' => array(
                'progress_trend' => $this->get_progress_trend_data($user_id),
                'category_distribution' => $this->get_category_distribution($user_id),
                'study_time_pattern' => $this->get_study_time_pattern($user_id),
                'mastery_heatmap' => $this->get_mastery_heatmap($user_id)
            ),
            'recent_activity' => $this->get_recent_activity($user_id),
            'badges' => $this->get_user_badge_summary($user_id),
            'goals' => $this->get_goal_progress($user_id),
            'recommendations' => $this->get_personalized_recommendations($user_id)
        );
    }
    
    /**
     * Ajax: ユーザーバッジ取得
     */
    public function ajax_get_user_badges() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $badges = $this->get_user_badges($user_id);
        wp_send_json_success($badges);
    }
    
    /**
     * ユーザーバッジ取得
     */
    private function get_user_badges($user_id) {
        global $wpdb;
        
        $earned_badges = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, ub.earned_at, ub.is_featured
             FROM {$this->badges_table} b
             JOIN {$this->user_badges_table} ub ON b.id = ub.badge_id
             WHERE ub.user_id = %d
             ORDER BY ub.earned_at DESC",
            $user_id
        ));
        
        $available_badges = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*
             FROM {$this->badges_table} b
             WHERE b.is_active = 1 
             AND b.id NOT IN (
                 SELECT badge_id FROM {$this->user_badges_table} WHERE user_id = %d
             )
             ORDER BY b.points ASC",
            $user_id
        ));
        
        // 各バッジの進捗を計算
        $badge_progress = array();
        foreach ($available_badges as $badge) {
            $progress = $this->calculate_badge_progress($user_id, $badge);
            $badge_progress[$badge->id] = $progress;
        }
        
        return array(
            'earned' => $earned_badges,
            'available' => $available_badges,
            'progress' => $badge_progress,
            'total_points' => array_sum(array_column($earned_badges, 'points')),
            'stats' => array(
                'total_earned' => count($earned_badges),
                'common' => count(array_filter($earned_badges, function($b) { return $b->rarity === 'common'; })),
                'uncommon' => count(array_filter($earned_badges, function($b) { return $b->rarity === 'uncommon'; })),
                'rare' => count(array_filter($earned_badges, function($b) { return $b->rarity === 'rare'; })),
                'legendary' => count(array_filter($earned_badges, function($b) { return $b->rarity === 'legendary'; }))
            )
        );
    }
    
    /**
     * バッジ進捗計算
     */
    private function calculate_badge_progress($user_id, $badge) {
        $criteria = json_decode($badge->criteria, true);
        $current_value = 0;
        $target_value = $criteria['value'];
        
        global $wpdb;
        
        switch ($criteria['type']) {
            case 'maps_completed':
                $current_value = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT map_id) 
                     FROM {$wpdb->prefix}gyosei_user_progress 
                     WHERE user_id = %d AND status = 'completed'",
                    $user_id
                ));
                break;
                
            case 'study_streak':
                $current_value = $this->calculate_study_streak($user_id);
                break;
                
            case 'daily_nodes':
                $current_value = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) 
                     FROM {$wpdb->prefix}gyosei_user_progress 
                     WHERE user_id = %d AND DATE(last_studied) = CURDATE() AND status = 'completed'",
                    $user_id
                ));
                break;
                
            case 'category_complete':
                $current_value = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT up.map_id)
                     FROM {$wpdb->prefix}gyosei_user_progress up
                     JOIN {$wpdb->prefix}gyosei_mindmaps m ON up.map_id = m.id
                     WHERE up.user_id = %d AND m.category = %s AND up.status = 'completed'",
                    $user_id, $criteria['value']
                ));
                $target_value = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT id) FROM {$wpdb->prefix}gyosei_mindmaps WHERE category = %s",
                    $criteria['value']
                ));
                break;
        }
        
        return array(
            'current' => $current_value,
            'target' => $target_value,
            'percentage' => $target_value > 0 ? min(100, ($current_value / $target_value) * 100) : 0
        );
    }
    
    /**
     * バッジ獲得チェック
     */
    public function check_badge_eligibility($user_id, $progress_data) {
        global $wpdb;
        
        $available_badges = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*
             FROM {$this->badges_table} b
             WHERE b.is_active = 1 
             AND b.id NOT IN (
                 SELECT badge_id FROM {$this->user_badges_table} WHERE user_id = %d
             )",
            $user_id
        ));
        
        foreach ($available_badges as $badge) {
            $progress = $this->calculate_badge_progress($user_id, $badge);
            
            if ($progress['percentage'] >= 100) {
                $this->award_badge($user_id, $badge->id);
            }
        }
    }
    
    /**
     * バッジ授与
     */
    private function award_badge($user_id, $badge_id) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->user_badges_table,
            array(
                'user_id' => $user_id,
                'badge_id' => $badge_id
            ),
            array('%d', '%d')
        );
        
        if ($result) {
            // 通知やポイント付与などの処理
            do_action('gyosei_badge_earned', $user_id, $badge_id);
        }
    }
    
    /**
     * 進捗ダッシュボードショートコード
     */
    public function progress_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'full', // full, mini, chart
            'period' => 'week',
            'height' => '600px'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>進捗ダッシュボードを利用するには<a href="' . wp_login_url() . '">ログイン</a>が必要です。</p>';
        }
        
        ob_start();
        ?>
        <div class="gyosei-progress-dashboard" style="height: <?php echo esc_attr($atts['height']); ?>">
            <div class="dashboard-header">
                <h3>学習進捗ダッシュボード</h3>
                <div class="dashboard-controls">
                    <select id="dashboard-period">
                        <option value="week" <?php selected($atts['period'], 'week'); ?>>今週</option>
                        <option value="month" <?php selected($atts['period'], 'month'); ?>>今月</option>
                        <option value="quarter" <?php selected($atts['period'], 'quarter'); ?>>四半期</option>
                        <option value="year" <?php selected($atts['period'], 'year'); ?>>年間</option>
                    </select>
                    <button id="refresh-dashboard" class="btn-secondary">更新</button>
                </div>
            </div>
            
            <div class="dashboard-content">
                <div class="stats-cards">
                    <div class="stat-card" data-stat="progress">
                        <div class="stat-icon">📊</div>
                        <div class="stat-info">
                            <div class="stat-value" id="overall-progress">-</div>
                            <div class="stat-label">総合進捗</div>
                        </div>
                    </div>
                    
                    <div class="stat-card" data-stat="streak">
                        <div class="stat-icon">🔥</div>
                        <div class="stat-info">
                            <div class="stat-value" id="study-streak">-</div>
                            <div class="stat-label">連続学習日数</div>
                        </div>
                    </div>
                    
                    <div class="stat-card" data-stat="time">
                        <div class="stat-icon">⏱️</div>
                        <div class="stat-info">
                            <div class="stat-value" id="study-time">-</div>
                            <div class="stat-label">学習時間</div>
                        </div>
                    </div>
                    
                    <div class="stat-card" data-stat="badges">
                        <div class="stat-icon">🏆</div>
                        <div class="stat-info">
                            <div class="stat-value" id="badge-count">-</div>
                            <div class="stat-label">獲得バッジ</div>
                        </div>
                    </div>
                </div>
                
                <div class="dashboard-charts">
                    <div class="chart-container">
                        <h4>進捗トレンド</h4>
                        <canvas id="progress-trend-chart"></canvas>
                    </div>
                    
                    <div class="chart-container">
                        <h4>科目別分布</h4>
                        <canvas id="category-distribution-chart"></canvas>
                    </div>
                </div>
                
                <div class="dashboard-activities">
                    <div class="recent-achievements">
                        <h4>最近の達成</h4>
                        <div id="recent-achievements-list">
                            <!-- 最近の達成がここに表示されます -->
                        </div>
                    </div>
                    
                    <div class="upcoming-goals">
                        <h4>目標まであと少し</h4>
                        <div id="upcoming-goals-list">
                            <!-- 目標進捗がここに表示されます -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // ダッシュボード初期化
            initProgressDashboard();
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * ヘルパーメソッド群
     */
    private function get_date_condition($period) {
        switch ($period) {
            case 'week':
                return 'AND up.last_studied >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
            case 'month':
                return 'AND up.last_studied >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
            case 'quarter':
                return 'AND up.last_studied >= DATE_SUB(NOW(), INTERVAL 3 MONTH)';
            case 'year':
                return 'AND up.last_studied >= DATE_SUB(NOW(), INTERVAL 1 YEAR)';
            default:
                return '';
        }
    }
    
    private function calculate_study_streak($user_id) {
        global $wpdb;
        
        $recent_studies = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT DATE(last_studied) 
             FROM {$wpdb->prefix}gyosei_user_progress 
             WHERE user_id = %d AND last_studied IS NOT NULL 
             ORDER BY DATE(last_studied) DESC LIMIT 30",
            $user_id
        ));
        
        if (empty($recent_studies)) {
            return 0;
        }
        
        $streak = 0;
        $current_date = date('Y-m-d');
        
        foreach ($recent_studies as $study_date) {
            if ($study_date === $current_date || 
                $study_date === date('Y-m-d', strtotime($current_date . ' -' . $streak . ' day'))) {
                $streak++;
                $current_date = date('Y-m-d', strtotime($current_date . ' -1 day'));
            } else {
                break;
            }
        }
        
        return $streak;
    }
    
    private function get_overview_stats($user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(DISTINCT map_id) as total_maps,
                COUNT(DISTINCT node_id) as total_nodes,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_nodes,
                AVG(progress_percent) as avg_progress,
                SUM(total_study_time) as total_study_time,
                AVG(mastery_level) as avg_mastery
             FROM {$wpdb->prefix}gyosei_user_progress 
             WHERE user_id = %d",
            $user_id
        ));
    }
    
    private function generate_insights($stats, $daily_progress, $category_stats) {
        $insights = array();
        
        // 学習時間の洞察
        if ($stats->total_study_time > 0) {
            $avg_daily_time = $stats->total_study_time / max(1, count($daily_progress));
            if ($avg_daily_time < 30) {
                $insights[] = array(
                    'type' => 'suggestion',
                    'message' => '1日の学習時間を増やすことで、より効果的な学習が期待できます。'
                );
            }
        }
        
        // 進捗の洞察
        if ($stats->avg_progress < 50) {
            $insights[] = array(
                'type' => 'encouragement',
                'message' => '継続的な学習で着実に進歩しています。この調子で頑張りましょう！'
            );
        }
        
        return $insights;
    }
}