<?php
/**
 * Plugin Name: 学習進捗管理システム（柔軟構造版）
 * Plugin URI: https://yoursite.com/study-progress-tracker
 * Description: 柔軟な構造設定が可能な学習進捗管理プラグイン
 * Version: 3.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: study-progress-tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

// プラグインの定数を定義
define('SPT_VERSION', '3.0.0');
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
        
        // 新しいAjax handlers
        add_action('wp_ajax_spt_add_chapter', array($this, 'ajax_add_chapter'));
        add_action('wp_ajax_spt_add_section', array($this, 'ajax_add_section'));
        add_action('wp_ajax_spt_add_item', array($this, 'ajax_add_item'));
        add_action('wp_ajax_spt_update_name', array($this, 'ajax_update_name'));
        add_action('wp_ajax_spt_delete_element', array($this, 'ajax_delete_element'));
    }
    
    public function init() {
        load_plugin_textdomain('study-progress-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        // 新しいデータ構造
        $default_subjects = array(
            'constitutional' => '憲法',
            'administrative' => '行政法', 
            'civil' => '民法'
        );
        
        // 新しい柔軟構造のサンプル
        $default_structure = array(
            'constitutional' => array(
                'color' => '#4CAF50',
                'chapters' => array(
                    1 => array(
                        'name' => '人権総論',
                        'sections' => array(
                            1 => array(
                                'name' => '基本的人権の意義',
                                'items' => array(
                                    1 => '人権の概念',
                                    2 => '人権の性質',
                                    3 => '人権の主体'
                                )
                            ),
                            2 => array(
                                'name' => '人権の享有主体',
                                'items' => array(
                                    1 => '自然人',
                                    2 => '法人',
                                    3 => '外国人'
                                )
                            )
                        )
                    ),
                    2 => array(
                        'name' => '包括的基本権',
                        'sections' => array(
                            1 => array(
                                'name' => '生命・自由・幸福追求権',
                                'items' => array(
                                    1 => '生命権',
                                    2 => '自由権',
                                    3 => '幸福追求権'
                                )
                            )
                        )
                    )
                )
            )
        );
        
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
        
        $subjects = get_option('spt_subjects', array());
        $structure = get_option('spt_structure', array());
        $progress = get_option('spt_progress', array());
        $settings = get_option('spt_settings', array());
        
        include SPT_PLUGIN_PATH . 'templates/admin-page-flexible.php';
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
            $color = sanitize_hex_color($_POST['color']);
            
            if ($key && $name) {
                $subjects = get_option('spt_subjects', array());
                $structure = get_option('spt_structure', array());
                
                $subjects[$key] = $name;
                $structure[$key] = array(
                    'color' => $color,
                    'chapters' => array()
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
    }
    
    // フロントエンド スクリプト
    public function enqueue_scripts() {
        wp_enqueue_style('spt-frontend', SPT_PLUGIN_URL . 'assets/css/frontend-flexible.css', array(), SPT_VERSION);
        wp_enqueue_script('spt-frontend', SPT_PLUGIN_URL . 'assets/js/frontend-flexible.js', array('jquery'), SPT_VERSION, true);
        
        $settings = get_option('spt_settings', array());
        
        $localize_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spt_frontend'),
            'first_check_color' => $settings['first_check_color'] ?? '#e6f7e6',
            'second_check_color' => $settings['second_check_color'] ?? '#ffebcc',
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        );
        
        wp_localize_script('spt-frontend', 'spt_data', $localize_data);
    }
    
    // 管理画面 スクリプト
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'study-progress-tracker') === false) {
            return;
        }
        
        wp_enqueue_style('spt-admin', SPT_PLUGIN_URL . 'assets/css/admin-flexible.css', array(), SPT_VERSION);
        wp_enqueue_script('spt-admin', SPT_PLUGIN_URL . 'assets/js/admin-flexible.js', array('jquery'), SPT_VERSION, true);
        
        wp_localize_script('spt-admin', 'spt_admin_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spt_admin_ajax')
        ));
    }
    
    // 進捗表示ショートコード
    public function render_progress_shortcode($atts) {
        $atts = shortcode_atts(array(
            'subject' => '',
            'interactive' => 'yes',
            'style' => 'default'
        ), $atts);
        
        $subjects = get_option('spt_subjects', array());
        $structure = get_option('spt_structure', array());
        $progress = get_option('spt_progress', array());
        $settings = get_option('spt_settings', array());
        
        if (empty($subjects)) {
            return '<div class="spt-error">科目が登録されていません。</div>';
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
        }
        
        $interactive = ($atts['interactive'] === 'yes');
        
        ob_start();
        include SPT_PLUGIN_PATH . 'templates/progress-display-flexible.php';
        return ob_get_clean();
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
    
    // Ajax: 進捗切り替え（新構造対応）
    public function ajax_toggle_progress() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'spt_frontend')) {
            wp_send_json_error(array('message' => 'セキュリティ検証に失敗しました'));
            return;
        }
        
        $subject = sanitize_key($_POST['subject'] ?? '');
        $chapter = intval($_POST['chapter'] ?? 0);
        $section = intval($_POST['section'] ?? 0);
        $item = intval($_POST['item'] ?? 0);
        $level = intval($_POST['level'] ?? 0);
        
        if (empty($subject) || $chapter <= 0 || $section <= 0 || $item <= 0) {
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
            // 空配列の削除
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
        
        update_option('spt_progress', $progress);
        
        // 進捗率を計算
        $percent = $this->calculate_progress_percent($subject, $progress);
        
        wp_send_json_success(array(
            'percent' => $percent,
            'message' => '保存しました'
        ));
    }
    
    // Ajax: 進捗リセット
    public function ajax_reset_progress() {
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
        update_option('spt_progress', $progress);
        
        wp_send_json_success(array('message' => '進捗をリセットしました'));
    }
    
    // Ajax: 章追加
    public function ajax_add_chapter() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'spt_admin_ajax')) {
            wp_send_json_error();
            return;
        }
        
        $subject = sanitize_key($_POST['subject'] ?? '');
        $chapter_name = sanitize_text_field($_POST['chapter_name'] ?? '');
        
        if (empty($subject) || empty($chapter_name)) {
            wp_send_json_error();
            return;
        }
        
        $structure = get_option('spt_structure', array());
        
        if (!isset($structure[$subject])) {
            wp_send_json_error();
            return;
        }
        
        // 新しい章IDを取得
        $chapter_id = 1;
        if (!empty($structure[$subject]['chapters'])) {
            $chapter_id = max(array_keys($structure[$subject]['chapters'])) + 1;
        }
        
        $structure[$subject]['chapters'][$chapter_id] = array(
            'name' => $chapter_name,
            'sections' => array()
        );
        
        update_option('spt_structure', $structure);
        
        wp_send_json_success(array(
            'chapter_id' => $chapter_id,
            'chapter_name' => $chapter_name
        ));
    }
    
    // Ajax: 節追加
    public function ajax_add_section() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'spt_admin_ajax')) {
            wp_send_json_error();
            return;
        }
        
        $subject = sanitize_key($_POST['subject'] ?? '');
        $chapter = intval($_POST['chapter'] ?? 0);
        $section_name = sanitize_text_field($_POST['section_name'] ?? '');
        
        if (empty($subject) || $chapter <= 0 || empty($section_name)) {
            wp_send_json_error();
            return;
        }
        
        $structure = get_option('spt_structure', array());
        
        if (!isset($structure[$subject]['chapters'][$chapter])) {
            wp_send_json_error();
            return;
        }
        
        // 新しい節IDを取得
        $section_id = 1;
        if (!empty($structure[$subject]['chapters'][$chapter]['sections'])) {
            $section_id = max(array_keys($structure[$subject]['chapters'][$chapter]['sections'])) + 1;
        }
        
        $structure[$subject]['chapters'][$chapter]['sections'][$section_id] = array(
            'name' => $section_name,
            'items' => array()
        );
        
        update_option('spt_structure', $structure);
        
        wp_send_json_success(array(
            'section_id' => $section_id,
            'section_name' => $section_name
        ));
    }
    
    // Ajax: 項追加
    public function ajax_add_item() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'spt_admin_ajax')) {
            wp_send_json_error();
            return;
        }
        
        $subject = sanitize_key($_POST['subject'] ?? '');
        $chapter = intval($_POST['chapter'] ?? 0);
        $section = intval($_POST['section'] ?? 0);
        $item_name = sanitize_text_field($_POST['item_name'] ?? '');
        
        if (empty($subject) || $chapter <= 0 || $section <= 0 || empty($item_name)) {
            wp_send_json_error();
            return;
        }
        
        $structure = get_option('spt_structure', array());
        
        if (!isset($structure[$subject]['chapters'][$chapter]['sections'][$section])) {
            wp_send_json_error();
            return;
        }
        
        // 新しい項IDを取得
        $item_id = 1;
        if (!empty($structure[$subject]['chapters'][$chapter]['sections'][$section]['items'])) {
            $item_id = max(array_keys($structure[$subject]['chapters'][$chapter]['sections'][$section]['items'])) + 1;
        }
        
        $structure[$subject]['chapters'][$chapter]['sections'][$section]['items'][$item_id] = $item_name;
        
        update_option('spt_structure', $structure);
        
        wp_send_json_success(array(
            'item_id' => $item_id,
            'item_name' => $item_name
        ));
    }
    
    // Ajax: 名称更新
    public function ajax_update_name() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'spt_admin_ajax')) {
            wp_send_json_error();
            return;
        }
        
        $subject = sanitize_key($_POST['subject'] ?? '');
        $type = sanitize_key($_POST['type'] ?? '');
        $chapter = intval($_POST['chapter'] ?? 0);
        $section = intval($_POST['section'] ?? 0);
        $item = intval($_POST['item'] ?? 0);
        $new_name = sanitize_text_field($_POST['new_name'] ?? '');
        
        if (empty($subject) || empty($type) || empty($new_name)) {
            wp_send_json_error();
            return;
        }
        
        $structure = get_option('spt_structure', array());
        
        if (!isset($structure[$subject])) {
            wp_send_json_error();
            return;
        }
        
        switch ($type) {
            case 'chapter':
                if (isset($structure[$subject]['chapters'][$chapter])) {
                    $structure[$subject]['chapters'][$chapter]['name'] = $new_name;
                }
                break;
            case 'section':
                if (isset($structure[$subject]['chapters'][$chapter]['sections'][$section])) {
                    $structure[$subject]['chapters'][$chapter]['sections'][$section]['name'] = $new_name;
                }
                break;
            case 'item':
                if (isset($structure[$subject]['chapters'][$chapter]['sections'][$section]['items'][$item])) {
                    $structure[$subject]['chapters'][$chapter]['sections'][$section]['items'][$item] = $new_name;
                }
                break;
        }
        
        update_option('spt_structure', $structure);
        wp_send_json_success();
    }
    
    // Ajax: 要素削除
    public function ajax_delete_element() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'spt_admin_ajax')) {
            wp_send_json_error();
            return;
        }
        
        $subject = sanitize_key($_POST['subject'] ?? '');
        $type = sanitize_key($_POST['type'] ?? '');
        $chapter = intval($_POST['chapter'] ?? 0);
        $section = intval($_POST['section'] ?? 0);
        $item = intval($_POST['item'] ?? 0);
        
        if (empty($subject) || empty($type)) {
            wp_send_json_error();
            return;
        }
        
        $structure = get_option('spt_structure', array());
        $progress = get_option('spt_progress', array());
        
        switch ($type) {
            case 'chapter':
                unset($structure[$subject]['chapters'][$chapter]);
                if (isset($progress[$subject][$chapter])) {
                    unset($progress[$subject][$chapter]);
                }
                break;
            case 'section':
                unset($structure[$subject]['chapters'][$chapter]['sections'][$section]);
                if (isset($progress[$subject][$chapter][$section])) {
                    unset($progress[$subject][$chapter][$section]);
                }
                break;
            case 'item':
                unset($structure[$subject]['chapters'][$chapter]['sections'][$section]['items'][$item]);
                if (isset($progress[$subject][$chapter][$section][$item])) {
                    unset($progress[$subject][$chapter][$section][$item]);
                }
                break;
        }
        
        update_option('spt_structure', $structure);
        update_option('spt_progress', $progress);
        wp_send_json_success();
    }
    
    // 進捗率計算（新構造対応）
    private function calculate_progress_percent($subject, $progress) {
        $structure = get_option('spt_structure', array());
        
        if (!isset($structure[$subject]['chapters'])) {
            return 0;
        }
        
        $total_items = 0;
        $completed_items = 0;
        
        foreach ($structure[$subject]['chapters'] as $chapter_id => $chapter_data) {
            if (!empty($chapter_data['sections'])) {
                foreach ($chapter_data['sections'] as $section_id => $section_data) {
                    if (!empty($section_data['items'])) {
                        $total_items += count($section_data['items']);
                        
                        if (isset($progress[$subject][$chapter_id][$section_id])) {
                            $completed_items += count($progress[$subject][$chapter_id][$section_id]);
                        }
                    }
                }
            }
        }
        
        return $total_items > 0 ? min(100, ceil(($completed_items / $total_items) * 100)) : 0;
    }
}

// プラグイン初期化
StudyProgressTracker::get_instance();