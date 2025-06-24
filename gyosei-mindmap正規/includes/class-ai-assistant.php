<?php
/**
 * 行政書士の道 - AI学習支援クラス
 * 弱点分析・学習計画自動生成・チャットボット機能
 * File: includes/class-ai-assistant.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class GyoseiAIAssistant {
    
    private $learning_patterns_table;
    private $recommendations_table;
    private $chat_sessions_table;
    private $weakness_analysis_table;
    
    public function __construct() {
        global $wpdb;
        $this->learning_patterns_table = $wpdb->prefix . 'gyosei_learning_patterns';
        $this->recommendations_table = $wpdb->prefix . 'gyosei_ai_recommendations';
        $this->chat_sessions_table = $wpdb->prefix . 'gyosei_chat_sessions';
        $this->weakness_analysis_table = $wpdb->prefix . 'gyosei_weakness_analysis';
        
        // Ajax フック
        add_action('wp_ajax_analyze_weaknesses', array($this, 'ajax_analyze_weaknesses'));
        add_action('wp_ajax_generate_study_plan', array($this, 'ajax_generate_study_plan'));
        add_action('wp_ajax_get_study_recommendations', array($this, 'ajax_get_study_recommendations'));
        add_action('wp_ajax_chat_with_ai', array($this, 'ajax_chat_with_ai'));
        add_action('wp_ajax_get_learning_insights', array($this, 'ajax_get_learning_insights'));
        add_action('wp_ajax_update_learning_pattern', array($this, 'ajax_update_learning_pattern'));
        add_action('wp_ajax_get_adaptive_content', array($this, 'ajax_get_adaptive_content'));
        
        // ショートコード
        add_shortcode('ai_assistant', array($this, 'ai_assistant_shortcode'));
        add_shortcode('weakness_analyzer', array($this, 'weakness_analyzer_shortcode'));
        add_shortcode('study_planner', array($this, 'study_planner_shortcode'));
    }
    
    /**
     * AIテーブル作成
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // 学習パターンテーブル
        $learning_patterns_table = $wpdb->prefix . 'gyosei_learning_patterns';
        $sql1 = "CREATE TABLE $learning_patterns_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            learning_style varchar(50) NOT NULL,
            preferred_times text,
            session_duration int DEFAULT 30,
            difficulty_preference varchar(20) DEFAULT 'medium',
            focus_areas text,
            learning_speed float DEFAULT 1.0,
            retention_rate float DEFAULT 0.7,
            mistake_patterns text,
            strengths text,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        // AI推奨テーブル
        $recommendations_table = $wpdb->prefix . 'gyosei_ai_recommendations';
        $sql2 = "CREATE TABLE $recommendations_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            recommendation_type varchar(50) NOT NULL,
            content text NOT NULL,
            priority int DEFAULT 5,
            reason text,
            map_id bigint(20),
            node_id varchar(100),
            is_read tinyint(1) DEFAULT 0,
            is_applied tinyint(1) DEFAULT 0,
            expires_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY recommendation_type (recommendation_type),
            KEY priority (priority),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // チャットセッションテーブル
        $chat_sessions_table = $wpdb->prefix . 'gyosei_chat_sessions';
        $sql3 = "CREATE TABLE $chat_sessions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            session_id varchar(100) NOT NULL,
            message_type varchar(20) NOT NULL,
            message_content text NOT NULL,
            ai_response text,
            context_data text,
            satisfaction_rating int,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // 弱点分析テーブル
        $weakness_analysis_table = $wpdb->prefix . 'gyosei_weakness_analysis';
        $sql4 = "CREATE TABLE $weakness_analysis_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            analysis_date datetime DEFAULT CURRENT_TIMESTAMP,
            weak_areas text NOT NULL,
            improvement_suggestions text,
            focus_priority text,
            estimated_study_time int,
            next_analysis_date datetime,
            progress_since_last float DEFAULT 0,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY analysis_date (analysis_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
        dbDelta($sql3);
        dbDelta($sql4);
    }
    
    /**
     * Ajax: 弱点分析実行
     */
    public function ajax_analyze_weaknesses() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $analysis_result = $this->perform_weakness_analysis($user_id);
        
        if ($analysis_result) {
            wp_send_json_success($analysis_result);
        } else {
            wp_send_json_error('Analysis failed');
        }
    }
    
    /**
     * 弱点分析実行
     */
    private function perform_weakness_analysis($user_id) {
        global $wpdb;
        
        // ユーザーの学習データを取得
        $progress_data = $wpdb->get_results($wpdb->prepare(
            "SELECT up.*, m.category 
             FROM {$wpdb->prefix}gyosei_user_progress up
             JOIN {$wpdb->prefix}gyosei_mindmaps m ON up.map_id = m.id
             WHERE up.user_id = %d",
            $user_id
        ));
        
        if (empty($progress_data)) {
            return array(
                'weak_areas' => array(),
                'suggestions' => array('まずは基礎的なマップから学習を始めましょう'),
                'priority' => 'beginner'
            );
        }
        
        $analysis = $this->analyze_learning_data($progress_data);
        
        // 分析結果をデータベースに保存
        $wpdb->insert(
            $this->weakness_analysis_table,
            array(
                'user_id' => $user_id,
                'weak_areas' => json_encode($analysis['weak_areas']),
                'improvement_suggestions' => json_encode($analysis['suggestions']),
                'focus_priority' => json_encode($analysis['priority_areas']),
                'estimated_study_time' => $analysis['estimated_time'],
                'next_analysis_date' => date('Y-m-d H:i:s', strtotime('+1 week'))
            ),
            array('%d', '%s', '%s', '%s', '%d', '%s')
        );
        
        return $analysis;
    }
    
    /**
     * 学習データ分析
     */
    private function analyze_learning_data($progress_data) {
        $category_stats = array();
        $weak_areas = array();
        $suggestions = array();
        $total_nodes = count($progress_data);
        
        // カテゴリ別統計を計算
        foreach ($progress_data as $progress) {
            $category = $progress->category;
            
            if (!isset($category_stats[$category])) {
                $category_stats[$category] = array(
                    'total' => 0,
                    'completed' => 0,
                    'avg_progress' => 0,
                    'avg_mastery' => 0,
                    'study_time' => 0
                );
            }
            
            $category_stats[$category]['total']++;
            $category_stats[$category]['avg_progress'] += $progress->progress_percent;
            $category_stats[$category]['avg_mastery'] += $progress->mastery_level;
            $category_stats[$category]['study_time'] += $progress->total_study_time;
            
            if ($progress->status === 'completed') {
                $category_stats[$category]['completed']++;
            }
        }
        
        // 平均値を計算
        foreach ($category_stats as $category => &$stats) {
            $stats['avg_progress'] = $stats['avg_progress'] / $stats['total'];
            $stats['avg_mastery'] = $stats['avg_mastery'] / $stats['total'];
            $stats['completion_rate'] = ($stats['completed'] / $stats['total']) * 100;
        }
        
        // 弱点を特定
        foreach ($category_stats as $category => $stats) {
            if ($stats['avg_progress'] < 50 || $stats['completion_rate'] < 30) {
                $weak_areas[] = array(
                    'category' => $category,
                    'progress' => round($stats['avg_progress']),
                    'completion_rate' => round($stats['completion_rate']),
                    'severity' => $this->calculate_weakness_severity($stats)
                );
            }
        }
        
        // 改善提案を生成
        $suggestions = $this->generate_improvement_suggestions($weak_areas, $category_stats);
        
        // 優先度の高い分野を特定
        $priority_areas = $this->identify_priority_areas($weak_areas);
        
        return array(
            'weak_areas' => $weak_areas,
            'suggestions' => $suggestions,
            'priority_areas' => $priority_areas,
            'category_stats' => $category_stats,
            'estimated_time' => $this->estimate_study_time($weak_areas),
            'overall_score' => $this->calculate_overall_score($category_stats)
        );
    }
    
    /**
     * Ajax: 学習計画生成
     */
    public function ajax_generate_study_plan() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $target_date = sanitize_text_field($_POST['target_date'] ?? '');
        $daily_study_time = intval($_POST['daily_study_time'] ?? 60);
        $focus_areas = array_map('sanitize_text_field', $_POST['focus_areas'] ?? array());
        
        $study_plan = $this->generate_personalized_study_plan($user_id, $target_date, $daily_study_time, $focus_areas);
        
        wp_send_json_success($study_plan);
    }
    
    /**
     * パーソナライズド学習計画生成
     */
    private function generate_personalized_study_plan($user_id, $target_date, $daily_minutes, $focus_areas) {
        // ユーザーの弱点分析結果を取得
        $weakness_analysis = $this->get_latest_weakness_analysis($user_id);
        
        // 学習パターンを取得
        $learning_pattern = $this->get_user_learning_pattern($user_id);
        
        // 目標日までの日数を計算
        $days_until_target = $target_date ? $this->calculate_days_until($target_date) : 90;
        $total_study_time = $days_until_target * $daily_minutes;
        
        // 各分野の学習時間を配分
        $time_allocation = $this->allocate_study_time($weakness_analysis, $focus_areas, $total_study_time);
        
        // 週次スケジュールを生成
        $weekly_schedule = $this->generate_weekly_schedule($time_allocation, $daily_minutes, $learning_pattern);
        
        // マイルストーンを設定
        $milestones = $this->generate_milestones($days_until_target, $focus_areas);
        
        return array(
            'plan_overview' => array(
                'target_date' => $target_date,
                'total_days' => $days_until_target,
                'daily_minutes' => $daily_minutes,
                'total_hours' => round($total_study_time / 60, 1)
            ),
            'time_allocation' => $time_allocation,
            'weekly_schedule' => $weekly_schedule,
            'milestones' => $milestones,
            'adaptive_tips' => $this->generate_adaptive_tips($learning_pattern),
            'progress_tracking' => $this->setup_progress_tracking($user_id)
        );
    }
    
    /**
     * Ajax: AIチャット
     */
    public function ajax_chat_with_ai() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
        }
        
        $message = sanitize_textarea_field($_POST['message']);
        $session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $context = sanitize_text_field($_POST['context'] ?? '');
        
        if (empty($session_id)) {
            $session_id = $this->generate_chat_session_id();
        }
        
        $ai_response = $this->process_ai_chat($user_id, $message, $session_id, $context);
        
        wp_send_json_success(array(
            'response' => $ai_response,
            'session_id' => $session_id
        ));
    }
    
    /**
     * AIチャット処理
     */
    private function process_ai_chat($user_id, $message, $session_id, $context) {
        global $wpdb;
        
        // ユーザーのメッセージを保存
        $wpdb->insert(
            $this->chat_sessions_table,
            array(
                'user_id' => $user_id,
                'session_id' => $session_id,
                'message_type' => 'user',
                'message_content' => $message,
                'context_data' => $context
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        // AIレスポンスを生成
        $ai_response = $this->generate_ai_response($user_id, $message, $context);
        
        // AIレスポンスを保存
        $wpdb->insert(
            $this->chat_sessions_table,
            array(
                'user_id' => $user_id,
                'session_id' => $session_id,
                'message_type' => 'ai',
                'message_content' => $message,
                'ai_response' => $ai_response
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );
        
        return $ai_response;
    }
    
    /**
     * AI応答生成（シンプルなルールベース）
     */
    private function generate_ai_response($user_id, $message, $context) {
        $message_lower = strtolower($message);
        
        // キーワードベースの応答
        if (strpos($message_lower, '弱点') !== false || strpos($message_lower, '苦手') !== false) {
            return $this->get_weakness_advice($user_id);
        }
        
        if (strpos($message_lower, '計画') !== false || strpos($message_lower, 'スケジュール') !== false) {
            return $this->get_study_plan_advice($user_id);
        }
        
        if (strpos($message_lower, '行政法') !== false) {
            return $this->get_subject_advice('gyosei');
        }
        
        if (strpos($message_lower, '民法') !== false) {
            return $this->get_subject_advice('minpo');
        }
        
        if (strpos($message_lower, '憲法') !== false) {
            return $this->get_subject_advice('kenpou');
        }
        
        if (strpos($message_lower, 'やる気') !== false || strpos($message_lower, 'モチベーション') !== false) {
            return $this->get_motivation_advice($user_id);
        }
        
        // デフォルト応答
        return "ご質問ありがとうございます。行政書士試験の学習について、弱点分析や学習計画、各科目の勉強法など、どのような点でサポートが必要でしょうか？";
    }
    
    /**
     * AIアシスタントショートコード
     */
    public function ai_assistant_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'full', // full, chat, analyzer, planner
            'height' => '500px'
        ), $atts);
        
        if (!is_user_logged_in()) {
            return '<p>AI学習支援機能を利用するには<a href="' . wp_login_url() . '">ログイン</a>が必要です。</p>';
        }
        
        ob_start();
        ?>
        <div class="gyosei-ai-assistant" style="height: <?php echo esc_attr($atts['height']); ?>">
            <div class="ai-assistant-tabs">
                <button class="ai-tab active" data-tab="chat">💬 AIチャット</button>
                <button class="ai-tab" data-tab="analyzer">📊 弱点分析</button>
                <button class="ai-tab" data-tab="planner">📅 学習計画</button>
            </div>
            
            <!-- AIチャットタブ -->
            <div id="ai-chat-tab" class="ai-tab-content active">
                <div class="chat-container">
                    <div id="chat-messages" class="chat-messages">
                        <div class="ai-message">
                            <div class="message-content">
                                こんにちは！行政書士試験の学習をサポートするAIアシスタントです。
                                学習の悩みや質問があれば何でもお聞かせください。
                            </div>
                        </div>
                    </div>
                    <div class="chat-input-area">
                        <input type="text" id="chat-input" placeholder="メッセージを入力してください...">
                        <button id="send-chat">送信</button>
                    </div>
                </div>
            </div>
            
            <!-- 弱点分析タブ -->
            <div id="ai-analyzer-tab" class="ai-tab-content">
                <div class="analyzer-container">
                    <button id="run-analysis" class="btn-primary">弱点分析を実行</button>
                    <div id="analysis-results" class="analysis-results">
                        <!-- 分析結果がここに表示されます -->
                    </div>
                </div>
            </div>
            
            <!-- 学習計画タブ -->
            <div id="ai-planner-tab" class="ai-tab-content">
                <div class="planner-container">
                    <form id="study-plan-form">
                        <div class="form-group">
                            <label>目標試験日</label>
                            <input type="date" name="target_date">
                        </div>
                        <div class="form-group">
                            <label>1日の学習時間（分）</label>
                            <input type="number" name="daily_study_time" value="60" min="15" max="480">
                        </div>
                        <div class="form-group">
                            <label>重点分野</label>
                            <div class="checkbox-group">
                                <label><input type="checkbox" name="focus_areas[]" value="gyosei"> 行政法</label>
                                <label><input type="checkbox" name="focus_areas[]" value="minpo"> 民法</label>
                                <label><input type="checkbox" name="focus_areas[]" value="kenpou"> 憲法</label>
                                <label><input type="checkbox" name="focus_areas[]" value="shoken"> 商法・会社法</label>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">学習計画を生成</button>
                    </form>
                    <div id="study-plan-results">
                        <!-- 学習計画がここに表示されます -->
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // AIアシスタント初期化
            initAIAssistant();
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * ヘルパーメソッド群
     */
    private function calculate_weakness_severity($stats) {
        $progress_weight = 0.4;
        $completion_weight = 0.4;
        $mastery_weight = 0.2;
        
        $severity_score = 
            (100 - $stats['avg_progress']) * $progress_weight +
            (100 - $stats['completion_rate']) * $completion_weight +
            (100 - $stats['avg_mastery'] * 10) * $mastery_weight;
        
        if ($severity_score > 70) return 'high';
        if ($severity_score > 40) return 'medium';
        return 'low';
    }
    
    private function generate_improvement_suggestions($weak_areas, $category_stats) {
        $suggestions = array();
        
        foreach ($weak_areas as $area) {
            switch ($area['category']) {
                case 'gyosei':
                    $suggestions[] = '行政法は判例の理解が重要です。具体的な事例から学習を始めましょう。';
                    break;
                case 'minpo':
                    $suggestions[] = '民法は体系的な理解が必要です。総則から順番に学習することをお勧めします。';
                    break;
                case 'kenpou':
                    $suggestions[] = '憲法は基本的人権と統治機構を関連付けて学習しましょう。';
                    break;
                default:
                    $suggestions[] = 'この分野は基礎から丁寧に復習することをお勧めします。';
            }
        }
        
        return $suggestions;
    }
    
    private function generate_chat_session_id() {
        return 'chat_' . time() . '_' . wp_generate_password(8, false);
    }
    
    private function get_weakness_advice($user_id) {
        // 最新の弱点分析結果に基づくアドバイス
        return "弱点分析を行い、苦手分野を特定してピンポイントで学習することが効果的です。";
    }
    
    private function get_study_plan_advice($user_id) {
        return "効果的な学習計画のためには、目標設定と定期的な見直しが重要です。";
    }
    
    private function get_subject_advice($subject) {
        $advice = array(
            'gyosei' => '行政法は実務に直結する重要科目です。行政手続法から始めることをお勧めします。',
            'minpo' => '民法は全ての法律の基礎となります。まずは総則をしっかりと理解しましょう。',
            'kenpou' => '憲法は日本の最高法規です。基本的人権から学習を始めるとよいでしょう。'
        );
        
        return $advice[$subject] ?? '体系的な学習を心がけましょう。';
    }
    
    private function get_motivation_advice($user_id) {
        return "継続は力なり！小さな目標を達成していくことで、モチベーションを維持できます。";
    }
}