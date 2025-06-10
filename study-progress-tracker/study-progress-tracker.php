<?php
/**
 * Plugin Name: 学習進捗管理システム
 * Plugin URI: https://yoursite.com/study-progress-tracker
 * Description: 行政書士試験などの資格試験学習の進捗を管理するプラグイン
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * License: GPL v2 or later
 * Text Domain: study-progress-tracker
 * Domain Path: /languages
 */

// 直接アクセスを防止
if (!defined('ABSPATH')) {
    exit;
}

// プラグインの定数を定義
define('SPT_VERSION', '1.0.0');
define('SPT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SPT_PLUGIN_FILE', __FILE__);

// プラグインのメインクラス
class StudyProgressTracker {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        // プラグインの初期化
        add_action('init', array($this, 'init'));
        
        // アクティベーション・デアクティベーション
        register_activation_hook(SPT_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(SPT_PLUGIN_FILE, array($this, 'deactivate'));
        
        // 必要なファイルを読み込み
        $this->includes();
        
        // スクリプトとスタイルの登録
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Ajax ハンドラーの登録
        add_action('wp_ajax_progress_tracker_toggle_completion', array($this, 'ajax_toggle_completion'));
        add_action('wp_ajax_progress_tracker_toggle_item_completion', array($this, 'ajax_toggle_item_completion'));
        add_action('wp_ajax_progress_tracker_reset_progress', array($this, 'ajax_reset_progress'));
    }
    
    public function init() {
        // 言語ファイルの読み込み
        load_plugin_textdomain('study-progress-tracker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    private function includes() {
        // 管理画面
        if (is_admin()) {
            require_once SPT_PLUGIN_PATH . 'includes/class-admin.php';
            new SPT_Admin();
        }
        
        // ショートコード
        require_once SPT_PLUGIN_PATH . 'includes/class-shortcodes.php';
        new SPT_Shortcodes();
        
        // ウィジェット
        require_once SPT_PLUGIN_PATH . 'includes/class-widget.php';
        add_action('widgets_init', function() {
            register_widget('SPT_Progress_Widget');
        });
    }
    
    public function enqueue_scripts() {
        // フロントエンド用CSS
        wp_enqueue_style(
            'spt-frontend',
            SPT_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            SPT_VERSION
        );
        
        // フロントエンド用JS (ログインユーザーのみ)
        if (is_user_logged_in()) {
            wp_enqueue_script(
                'spt-frontend',
                SPT_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                SPT_VERSION,
                true
            );
            
            // Ajax用の設定
            wp_localize_script('spt-frontend', 'progress_tracker', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('progress_tracker_nonce'),
                'first_check_color' => get_option('progress_tracker_check_settings', array('first_check_color' => '#e6f7e6'))['first_check_color'],
                'second_check_color' => get_option('progress_tracker_check_settings', array('second_check_color' => '#ffebcc'))['second_check_color']
            ));
        }
    }
    
    public function admin_enqueue_scripts($hook) {
        // 管理画面用のスクリプトとスタイル
        if (strpos($hook, 'progress-tracker') !== false) {
            wp_enqueue_style(
                'spt-admin',
                SPT_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                SPT_VERSION
            );
            
            wp_enqueue_script(
                'spt-admin',
                SPT_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                SPT_VERSION,
                true
            );
        }
    }
    
    // Ajax: 節の完了状態を切り替え
    public function ajax_toggle_completion() {
        check_ajax_referer('progress_tracker_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('ログインが必要です。', 'study-progress-tracker')));
            return;
        }
        
        $subject_key = sanitize_key($_POST['subject']);
        $chapter_id = intval($_POST['chapter']);
        $section_id = intval($_POST['section']);
        $check_level = intval($_POST['check_level']);
        
        // 進捗データを取得
        $progress_data = get_option('progress_tracker_progress', array());
        
        // 科目データがない場合は初期化
        if (!isset($progress_data[$subject_key])) {
            $progress_data[$subject_key] = array(
                'chapters' => array(),
                'percent' => 0
            );
        }
        
        // 章データがない場合は初期化
        if (!isset($progress_data[$subject_key]['chapters'][$chapter_id])) {
            $progress_data[$subject_key]['chapters'][$chapter_id] = array();
        }
        
        // 進捗状態を更新
        if ($check_level > 0) {
            $progress_data[$subject_key]['chapters'][$chapter_id][$section_id] = $check_level;
        } else {
            unset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]);
            if (empty($progress_data[$subject_key]['chapters'][$chapter_id])) {
                unset($progress_data[$subject_key]['chapters'][$chapter_id]);
            }
        }
        
        // 進捗率を再計算
        $this->recalculate_progress($subject_key, $progress_data);
        
        // データを保存
        update_option('progress_tracker_progress', $progress_data);
        
        // 章の状態を確認
        $chapter_data = $this->get_chapter_status($subject_key, $chapter_id, $progress_data);
        
        wp_send_json_success(array(
            'percent' => $progress_data[$subject_key]['percent'],
            'chapter_completed' => $chapter_data['completed'],
            'chapter_mastered' => $chapter_data['mastered']
        ));
    }
    
    // Ajax: 項の完了状態を切り替え
    public function ajax_toggle_item_completion() {
        check_ajax_referer('progress_tracker_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('ログインが必要です。', 'study-progress-tracker')));
            return;
        }
        
        $subject_key = sanitize_key($_POST['subject']);
        $chapter_id = intval($_POST['chapter']);
        $section_id = intval($_POST['section']);
        $item_id = intval($_POST['item']);
        $check_level = intval($_POST['check_level']);
        
        // 進捗データを取得
        $progress_data = get_option('progress_tracker_progress', array());
        
        // データ構造を初期化
        if (!isset($progress_data[$subject_key])) {
            $progress_data[$subject_key] = array('chapters' => array(), 'percent' => 0);
        }
        if (!isset($progress_data[$subject_key]['chapters'][$chapter_id])) {
            $progress_data[$subject_key]['chapters'][$chapter_id] = array();
        }
        if (!isset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id])) {
            $progress_data[$subject_key]['chapters'][$chapter_id][$section_id] = array('items' => array());
        }
        
        // 項の進捗状態を更新
        if ($check_level > 0) {
            $progress_data[$subject_key]['chapters'][$chapter_id][$section_id]['items'][$item_id] = $check_level;
        } else {
            unset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]['items'][$item_id]);
            if (empty($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]['items'])) {
                unset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]);
                if (empty($progress_data[$subject_key]['chapters'][$chapter_id])) {
                    unset($progress_data[$subject_key]['chapters'][$chapter_id]);
                }
            }
        }
        
        // 進捗率を再計算
        $this->recalculate_progress($subject_key, $progress_data);
        
        // データを保存
        update_option('progress_tracker_progress', $progress_data);
        
        // 章と節の状態を確認
        $chapter_data = $this->get_chapter_status($subject_key, $chapter_id, $progress_data);
        $section_data = $this->get_section_status($subject_key, $chapter_id, $section_id, $progress_data);
        
        wp_send_json_success(array(
            'percent' => $progress_data[$subject_key]['percent'],
            'chapter_completed' => $chapter_data['completed'],
            'chapter_mastered' => $chapter_data['mastered'],
            'section_completed' => $section_data['completed'],
            'section_mastered' => $section_data['mastered']
        ));
    }
    
    // Ajax: 進捗リセット
    public function ajax_reset_progress() {
        check_ajax_referer('progress_tracker_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('ログインが必要です。', 'study-progress-tracker')));
            return;
        }
        
        $subject_key = sanitize_key($_POST['subject']);
        
        // 進捗データを取得
        $progress_data = get_option('progress_tracker_progress', array());
        
        // 指定科目のデータをリセット
        if (isset($progress_data[$subject_key])) {
            $progress_data[$subject_key] = array(
                'chapters' => array(),
                'percent' => 0
            );
            update_option('progress_tracker_progress', $progress_data);
        }
        
        wp_send_json_success();
    }
    
    // 進捗率を再計算
    private function recalculate_progress($subject_key, &$progress_data) {
        $chapter_structure = get_option('progress_tracker_chapters', array());
        $total_items = 0;
        $completed_count = 0;
        
        if (isset($chapter_structure[$subject_key]['chapters'])) {
            foreach ($chapter_structure[$subject_key]['chapters'] as $chapter_id => $chapter_data) {
                if (isset($chapter_data['section_data'])) {
                    foreach ($chapter_data['section_data'] as $section_id => $section_data) {
                        if (isset($section_data['item_data'])) {
                            $total_items += count($section_data['item_data']);
                            
                            if (isset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]['items'])) {
                                $completed_count += count($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]['items']);
                            }
                        } else {
                            // 旧形式
                            $total_items++;
                            if (isset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id])) {
                                $completed_count++;
                            }
                        }
                    }
                } else {
                    // 旧形式
                    $total_items += $chapter_data['sections'];
                    if (isset($progress_data[$subject_key]['chapters'][$chapter_id])) {
                        $completed_count += count($progress_data[$subject_key]['chapters'][$chapter_id]);
                    }
                }
            }
        }
        
        $percent = ($total_items > 0) ? min(100, ceil(($completed_count / $total_items) * 100)) : 0;
        $progress_data[$subject_key]['percent'] = $percent;
    }
    
    // 章の状態を取得
    private function get_chapter_status($subject_key, $chapter_id, $progress_data) {
        $chapter_structure = get_option('progress_tracker_chapters', array());
        $chapter_completed = false;
        $chapter_mastered = false;
        
        if (isset($chapter_structure[$subject_key]['chapters'][$chapter_id])) {
            $chapter_info = $chapter_structure[$subject_key]['chapters'][$chapter_id];
            $total_sections = isset($chapter_info['section_data']) ? count($chapter_info['section_data']) : $chapter_info['sections'];
            
            if (isset($progress_data[$subject_key]['chapters'][$chapter_id])) {
                $completed_sections = count($progress_data[$subject_key]['chapters'][$chapter_id]);
                $chapter_completed = $completed_sections == $total_sections;
                
                // 習得レベルの確認
                $mastered_count = 0;
                foreach ($progress_data[$subject_key]['chapters'][$chapter_id] as $section => $data) {
                    if (is_numeric($data) && $data >= 2) {
                        $mastered_count++;
                    } elseif (is_array($data) && isset($data['items'])) {
                        $all_mastered = true;
                        $total_items = isset($chapter_info['section_data'][$section]['item_data']) ? 
                            count($chapter_info['section_data'][$section]['item_data']) : 0;
                        $mastered_items = 0;
                        
                        foreach ($data['items'] as $item_level) {
                            if ($item_level >= 2) $mastered_items++;
                        }
                        
                        if ($total_items > 0 && $mastered_items == $total_items) {
                            $mastered_count++;
                        }
                    }
                }
                $chapter_mastered = $mastered_count == $total_sections;
            }
        }
        
        return array('completed' => $chapter_completed, 'mastered' => $chapter_mastered);
    }
    
    // 節の状態を取得
    private function get_section_status($subject_key, $chapter_id, $section_id, $progress_data) {
        $chapter_structure = get_option('progress_tracker_chapters', array());
        $section_completed = false;
        $section_mastered = false;
        
        if (isset($chapter_structure[$subject_key]['chapters'][$chapter_id]['section_data'][$section_id])) {
            $section_info = $chapter_structure[$subject_key]['chapters'][$chapter_id]['section_data'][$section_id];
            
            if (isset($section_info['item_data'])) {
                $total_items = count($section_info['item_data']);
                
                if (isset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]['items'])) {
                    $completed_items = count($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]['items']);
                    $section_completed = $completed_items == $total_items;
                    
                    $mastered_items = 0;
                    foreach ($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]['items'] as $item_level) {
                        if ($item_level >= 2) $mastered_items++;
                    }
                    $section_mastered = $mastered_items == $total_items;
                }
            }
        }
        
        return array('completed' => $section_completed, 'mastered' => $section_mastered);
    }
    
    // アクティベーション処理
    public function activate() {
        // デフォルトオプションの設定
        $default_subjects = array(
            'constitutional' => '憲法',
            'administrative' => '行政法',
            'civil' => '民法',
            'commercial' => '商法・会社法'
        );
        
        $default_chapters = array(
            'constitutional' => array('total' => 15, 'chapters' => array(), 'color' => '#4CAF50'),
            'administrative' => array('total' => 15, 'chapters' => array(), 'color' => '#4CAF50'),
            'civil' => array('total' => 20, 'chapters' => array(), 'color' => '#4CAF50'),
            'commercial' => array('total' => 10, 'chapters' => array(), 'color' => '#4CAF50')
        );
        
        $default_settings = array(
            'first_check_color' => '#e6f7e6',
            'second_check_color' => '#ffebcc'
        );
        
        // オプションがない場合のみ設定
        if (!get_option('progress_tracker_subjects')) {
            update_option('progress_tracker_subjects', $default_subjects);
        }
        if (!get_option('progress_tracker_chapters')) {
            update_option('progress_tracker_chapters', $default_chapters);
        }
        if (!get_option('progress_tracker_check_settings')) {
            update_option('progress_tracker_check_settings', $default_settings);
        }
        
        // リライトルールをフラッシュ
        flush_rewrite_rules();
    }
    
    // デアクティベーション処理
    public function deactivate() {
        // リライトルールをフラッシュ
        flush_rewrite_rules();
    }
}

// プラグインのインスタンスを初期化
StudyProgressTracker::get_instance();