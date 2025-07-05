<?php
/**
 * Plugin Name: 学習進捗管理システム（修正版）
 * Plugin URI: https://yoursite.com/study-progress-tracker
 * Description: フロントエンド中心の学習進捗管理プラグイン
 * Version: 2.1.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: study-progress-tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

// プラグインの定数を定義
define('SPT_VERSION', '2.1.0');
define('SPT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPT_PLUGIN_PATH', plugin_dir_path(__FILE__));

class StudyProgressTracker {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // 管理画面
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        }
        
        // フロントエンド
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('study_progress', array($this, 'render_progress_shortcode'));
        add_shortcode('exam_countdown', array($this, 'render_countdown_shortcode'));
        
        // Ajax handlers
        add_action('wp_ajax_spt_toggle_progress', array($this, 'ajax_toggle_progress'));
        add_action('wp_ajax_nopriv_spt_toggle_progress', array($this, 'ajax_toggle_progress'));
        add_action('wp_ajax_spt_reset_progress', array($this, 'ajax_reset_progress'));
        add_action('wp_ajax_nopriv_spt_reset_progress', array($this, 'ajax_reset_progress'));
    }
    
    public function init() {
        load_plugin_textdomain('study-progress-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // デバッグモード時のログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('StudyProgressTracker: プラグイン初期化完了');
        }
    }
    
    public function activate() {
        // デフォルト設定
        $default_subjects = array(
            'constitutional' => '憲法',
            'administrative' => '行政法', 
            'civil' => '民法',
            'commercial' => '商法・会社法'
        );
        
        $default_structure = array();
        foreach ($default_subjects as $key => $name) {
            $default_structure[$key] = array(
                'chapters' => 10,
                'sections_per_chapter' => 3,
                'items_per_section' => 5,
                'color' => '#4CAF50'
            );
        }
        
        $default_settings = array(
            'first_check_color' => '#e6f7e6',
            'second_check_color' => '#ffebcc',
            'exam_date' => '',
            'exam_title' => '試験'
        );
        
        if (!get_option('spt_subjects')) {
            update_option('spt_subjects', $default_subjects);
        }
        if (!get_option('spt_structure')) {
            update_option('spt_structure', $default_structure);
        }
        if (!get_option('spt_settings')) {
            update_option('spt_settings', $default_settings);
        }
        if (!get_option('spt_progress')) {
            update_option('spt_progress', array());
        }
        
        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('StudyProgressTracker: プラグイン有効化完了');
        }
    }
    
    // 管理画面メニュー
    public function add_admin_menu() {
        add_menu_page(
            __('学習進捗管理', 'study-progress-tracker'),
            __('学習進捗管理', 'study-progress-tracker'),
            'manage_options',
            'study-progress-tracker',
            array($this, 'render_admin_page'),
            'dashicons-welcome-learn-more',
            30
        );
    }
    
    // 管理画面の表示
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // POST処理
        $this->handle_admin_post();
        
        $subjects = get_option('spt_subjects', array());
        $structure = get_option('spt_structure', array());
        $settings = get_option('spt_settings', array());
        $progress = get_option('spt_progress', array());
        
        include SPT_PLUGIN_PATH . 'templates/admin-page.php';
    }
    
    // 管理画面POST処理
    private function handle_admin_post() {
        if (!isset($_POST['spt_nonce']) || !wp_verify_nonce($_POST['spt_nonce'], 'spt_admin')) {
            return;
        }
        
        // 科目の追加
        if (isset($_POST['add_subject'])) {
            $key = sanitize_key($_POST['subject_key']);
            $name = sanitize_text_field($_POST['subject_name']);
            $chapters = intval($_POST['chapters']);
            $sections = intval($_POST['sections_per_chapter']);
            $items = intval($_POST['items_per_section']);
            $color = sanitize_hex_color($_POST['color']);
            
            if ($key && $name && $chapters > 0) {
                $subjects = get_option('spt_subjects', array());
                $structure = get_option('spt_structure', array());
                
                $subjects[$key] = $name;
                $structure[$key] = array(
                    'chapters' => $chapters,
                    'sections_per_chapter' => $sections,
                    'items_per_section' => $items,
                    'color' => $color
                );
                
                update_option('spt_subjects', $subjects);
                update_option('spt_structure', $structure);
                
                add_settings_error('spt_messages', 'subject_added', '科目を追加しました。', 'success');
            }
        }
        
        // 科目の削除
        if (isset($_POST['delete_subject'])) {
            $key = sanitize_key($_POST['delete_subject']);
            
            $subjects = get_option('spt_subjects', array());
            $structure = get_option('spt_structure', array());
            $progress = get_option('spt_progress', array());
            
            unset($subjects[$key]);
            unset($structure[$key]);
            unset($progress[$key]);
            
            update_option('spt_subjects', $subjects);
            update_option('spt_structure', $structure);
            update_option('spt_progress', $progress);
            
            add_settings_error('spt_messages', 'subject_deleted', '科目を削除しました。', 'success');
        }
        
        // 設定の保存
        if (isset($_POST['save_settings'])) {
            $settings = array(
                'first_check_color' => sanitize_hex_color($_POST['first_check_color']),
                'second_check_color' => sanitize_hex_color($_POST['second_check_color']),
                'exam_date' => sanitize_text_field($_POST['exam_date']),
                'exam_title' => sanitize_text_field($_POST['exam_title'])
            );
            
            update_option('spt_settings', $settings);
            add_settings_error('spt_messages', 'settings_saved', '設定を保存しました。', 'success');
        }
        
        // 進捗のリセット
        if (isset($_POST['reset_progress']) && isset($_POST['confirm_reset'])) {
            $subject = sanitize_key($_POST['reset_subject']);
            $progress = get_option('spt_progress', array());
            
            if ($subject === 'all') {
                $progress = array();
            } else {
                unset($progress[$subject]);
            }
            
            update_option('spt_progress', $progress);
            add_settings_error('spt_messages', 'progress_reset', '進捗をリセットしました。', 'success');
        }
    }
    
    // フロントエンド スクリプト
    public function enqueue_scripts() {
        // CSSの読み込み
        wp_enqueue_style(
            'spt-frontend', 
            SPT_PLUGIN_URL . 'assets/css/frontend.css', 
            array(), 
            SPT_VERSION
        );
        
        // JavaScriptの読み込み
        wp_enqueue_script(
            'spt-frontend', 
            SPT_PLUGIN_URL . 'assets/js/frontend.js', 
            array('jquery'), 
            SPT_VERSION, 
            true
        );
        
        // データの受け渡し
        $settings = get_option('spt_settings', array());
        
        $localize_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spt_frontend'),
            'first_check_color' => $settings['first_check_color'] ?? '#e6f7e6',
            'second_check_color' => $settings['second_check_color'] ?? '#ffebcc',
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        );
        
        wp_localize_script('spt-frontend', 'spt_data', $localize_data);
        
        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('StudyProgressTracker: フロントエンドスクリプト読み込み完了');
        }
    }
    
    // 管理画面 スクリプト
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'study-progress-tracker') === false) {
            return;
        }
        
        wp_enqueue_style('spt-admin', SPT_PLUGIN_URL . 'assets/css/admin.css', array(), SPT_VERSION);
        wp_enqueue_script('spt-admin', SPT_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SPT_VERSION, true);
    }
    
    // 進捗表示ショートコード
    public function render_progress_shortcode($atts) {
        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('StudyProgressTracker: ショートコード [study_progress] 実行開始');
        }
        
        $atts = shortcode_atts(array(
            'subject' => '',
            'interactive' => 'yes',
            'style' => 'default'
        ), $atts);
        
        $subjects = get_option('spt_subjects', array());
        $structure = get_option('spt_structure', array());
        $progress = get_option('spt_progress', array());
        $settings = get_option('spt_settings', array());
        
        // データの検証
        if (empty($subjects)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('StudyProgressTracker: 科目データが空です');
            }
            return '<div class="spt-error">科目が登録されていません。管理画面で科目を追加してください。</div>';
        }
        
        // 特定科目のみ表示
        if (!empty($atts['subject'])) {
            $subject_keys = array_map('trim', explode(',', $atts['subject']));
            $filtered_subjects = array();
            foreach ($subject_keys as $key) {
                if (isset($subjects[$key])) {
                    $filtered_subjects[$key] = $subjects[$key];
                }
            }
            $subjects = $filtered_subjects;
            
            if (empty($subjects)) {
                return '<div class="spt-error">指定された科目が見つかりません。</div>';
            }
        }
        
        $interactive = ($atts['interactive'] === 'yes');
        
        // デバッグ情報
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('StudyProgressTracker: 表示科目数=' . count($subjects) . ', インタラクティブ=' . ($interactive ? 'true' : 'false'));
        }
        
        // テンプレートの読み込み
        ob_start();
        
        // テンプレートファイルの存在確認
        $template_path = SPT_PLUGIN_PATH . 'templates/progress-display.php';
        if (!file_exists($template_path)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('StudyProgressTracker: テンプレートファイルが見つかりません: ' . $template_path);
            }
            return '<div class="spt-error">テンプレートファイルが見つかりません。</div>';
        }
        
        try {
            include $template_path;
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('StudyProgressTracker: テンプレート読み込みエラー: ' . $e->getMessage());
            }
            return '<div class="spt-error">テンプレートの読み込みに失敗しました。</div>';
        }
        
        $content = ob_get_clean();
        
        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('StudyProgressTracker: ショートコード出力サイズ=' . strlen($content) . ' bytes');
        }
        
        return $content;
    }
    
    // カウントダウンショートコード
    public function render_countdown_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => '',
            'date' => ''
        ), $atts);
        
        $settings = get_option('spt_settings', array());
        $exam_date = !empty($atts['date']) ? $atts['date'] : ($settings['exam_date'] ?? '');
        $exam_title = !empty($atts['title']) ? $atts['title'] : ($settings['exam_title'] ?? '試験');
        
        if (empty($exam_date)) {
            return '<div class="spt-countdown-error">試験日が設定されていません。</div>';
        }
        
        $exam_timestamp = strtotime($exam_date);
        if ($exam_timestamp === false) {
            return '<div class="spt-countdown-error">無効な日付形式です。</div>';
        }
        
        $today = current_time('timestamp');
        $days_left = floor(($exam_timestamp - $today) / (60 * 60 * 24));
        
        if ($days_left < 0) {
            return '<div class="spt-countdown post-exam">' . esc_html($exam_title) . 'は終了しました。</div>';
        }
        
        return '<div class="spt-countdown">' . esc_html($exam_title) . 'まであと <span class="spt-countdown-days">' . esc_html($days_left) . '</span> 日</div>';
    }
    
    // Ajax: 進捗切り替え
    public function ajax_toggle_progress() {
        // nonce検証
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'spt_frontend')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('StudyProgressTracker: nonce検証失敗');
            }
            wp_send_json_error(array('message' => 'セキュリティ検証に失敗しました'));
            return;
        }
        
        $subject = sanitize_key($_POST['subject'] ?? '');
        $chapter = intval($_POST['chapter'] ?? 0);
        $section = intval($_POST['section'] ?? 0);
        $item = intval($_POST['item'] ?? 0);
        $level = intval($_POST['level'] ?? 0);
        
        // パラメータ検証
        if (empty($subject) || $chapter <= 0 || $section <= 0 || $item <= 0) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('StudyProgressTracker: 無効なパラメータ: ' . json_encode($_POST));
            }
            wp_send_json_error(array('message' => '無効なパラメータです'));
            return;
        }
        
        $progress = get_option('spt_progress', array());
        
        // 進捗データの更新
        if (!isset($progress[$subject])) {
            $progress[$subject] = array();
        }
        if (!isset($progress[$subject][$chapter])) {
            $progress[$subject][$chapter] = array();
        }
        if (!isset($progress[$subject][$chapter][$section])) {
            $progress[$subject][$chapter][$section] = array();
        }
        
        if ($level > 0) {
            $progress[$subject][$chapter][$section][$item] = $level;
        } else {
            unset($progress[$subject][$chapter][$section][$item]);
            if (empty($progress[$subject][$chapter][$section])) {
                unset($progress[$subject][$chapter][$section]);
                if (empty($progress[$subject][$chapter])) {
                    unset($progress[$subject][$chapter]);
                    if (empty($progress[$subject])) {
                        unset($progress[$subject]);
                    }
                }
            }
        }
        
        // データベースに保存
        $updated = update_option('spt_progress', $progress);
        
        if (!$updated) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('StudyProgressTracker: データベース更新失敗');
            }
            wp_send_json_error(array('message' => 'データベースの更新に失敗しました'));
            return;
        }
        
        // 進捗率を計算
        $percent = $this->calculate_progress_percent($subject, $progress);
        
        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("StudyProgressTracker: 進捗更新完了 - 科目:{$subject}, レベル:{$level}, 進捗率:{$percent}%");
        }
        
        wp_send_json_success(array(
            'percent' => $percent,
            'saved' => true,
            'message' => '保存しました'
        ));
    }
    
    // Ajax: 進捗リセット
    public function ajax_reset_progress() {
        // nonce検証
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'spt_frontend')) {
            wp_send_json_error(array('message' => 'セキュリティ検証に失敗しました'));
            return;
        }
        
        $subject = sanitize_key($_POST['subject'] ?? '');
        
        if (empty($subject)) {
            wp_send_json_error(array('message' => '科目が指定されていません'));
            return;
        }
        
        $progress = get_option('spt_progress', array());
        
        unset($progress[$subject]);
        $updated = update_option('spt_progress', $progress);
        
        if (!$updated) {
            wp_send_json_error(array('message' => 'データベースの更新に失敗しました'));
            return;
        }
        
        // デバッグログ
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("StudyProgressTracker: 進捗リセット完了 - 科目:{$subject}");
        }
        
        wp_send_json_success(array('message' => '進捗をリセットしました'));
    }
    
    // 進捗率計算
    private function calculate_progress_percent($subject, $progress) {
        $structure = get_option('spt_structure', array());
        
        if (!isset($structure[$subject])) {
            return 0;
        }
        
        $chapters = intval($structure[$subject]['chapters'] ?? 0);
        $sections = intval($structure[$subject]['sections_per_chapter'] ?? 0);
        $items = intval($structure[$subject]['items_per_section'] ?? 0);
        
        $total_items = $chapters * $sections * $items;
        $completed_items = 0;
        
        if (isset($progress[$subject]) && is_array($progress[$subject])) {
            foreach ($progress[$subject] as $chapter_progress) {
                if (is_array($chapter_progress)) {
                    foreach ($chapter_progress as $section_progress) {
                        if (is_array($section_progress)) {
                            $completed_items += count($section_progress);
                        }
                    }
                }
            }
        }
        
        return $total_items > 0 ? min(100, ceil(($completed_items / $total_items) * 100)) : 0;
    }
    
    // デバッグ用のヘルパー関数
    public function debug_info() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $subjects = get_option('spt_subjects', array());
        $structure = get_option('spt_structure', array());
        $progress = get_option('spt_progress', array());
        $settings = get_option('spt_settings', array());
        
        echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">';
        echo '<h4>StudyProgressTracker デバッグ情報</h4>';
        echo '<p><strong>科目数:</strong> ' . count($subjects) . '</p>';
        echo '<p><strong>構造データ:</strong> ' . count($structure) . '</p>';
        echo '<p><strong>進捗データ:</strong> ' . count($progress) . '</p>';
        echo '<p><strong>設定データ:</strong> ' . count($settings) . '</p>';
        echo '<p><strong>プラグインURL:</strong> ' . SPT_PLUGIN_URL . '</p>';
        echo '<p><strong>プラグインパス:</strong> ' . SPT_PLUGIN_PATH . '</p>';
        echo '</div>';
    }
}

// プラグイン初期化
StudyProgressTracker::get_instance();

// デバッグ用ショートコード（開発時のみ）
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_shortcode('spt_debug', function() {
        ob_start();
        StudyProgressTracker::get_instance()->debug_info();
        return ob_get_clean();
    });
}