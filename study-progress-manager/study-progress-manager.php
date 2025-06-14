<?php
/*
Plugin Name: 学習進捗管理システム
Plugin URI: https://your-site.com/
Description: 行政書士試験などの学習進捗を管理するためのプラグインです。章・節・項の階層構造で学習状況を視覚的に管理できます。
Version: 1.0.0
Author: あなたの名前
*/

// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// プラグインの定数定義
define('SPM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SPM_VERSION', '1.0.0');

class StudyProgressManager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Ajax処理の追加
        add_action('wp_ajax_save_progress', array($this, 'ajax_save_progress'));
        add_action('wp_ajax_nopriv_save_progress', array($this, 'ajax_save_progress'));
        add_action('wp_ajax_load_subjects', array($this, 'ajax_load_subjects'));
        add_action('wp_ajax_nopriv_load_subjects', array($this, 'ajax_load_subjects'));
        add_action('wp_ajax_load_subject_progress', array($this, 'ajax_load_subject_progress'));
        add_action('wp_ajax_nopriv_load_subject_progress', array($this, 'ajax_load_subject_progress'));
        
        add_shortcode('study_progress', array($this, 'shortcode_display'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // 初期化処理
    }
    
    // プラグイン有効化時の処理
    public function activate() {
        $this->create_tables();
        $this->set_default_options();
    }
    
    // データベーステーブル作成
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 科目テーブル
        $table_subjects = $wpdb->prefix . 'study_subjects';
        $sql_subjects = "CREATE TABLE $table_subjects (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            subject_key varchar(50) NOT NULL,
            subject_name varchar(100) NOT NULL,
            total_chapters int(11) DEFAULT 0,
            progress_color varchar(7) DEFAULT '#4CAF50',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY subject_key (subject_key)
        ) $charset_collate;";
        
        // 章テーブル
        $table_chapters = $wpdb->prefix . 'study_chapters';
        $sql_chapters = "CREATE TABLE $table_chapters (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            subject_key varchar(50) NOT NULL,
            chapter_number int(11) NOT NULL,
            chapter_title varchar(200) NOT NULL,
            total_sections int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY subject_key (subject_key)
        ) $charset_collate;";
        
        // 節テーブル
        $table_sections = $wpdb->prefix . 'study_sections';
        $sql_sections = "CREATE TABLE $table_sections (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            subject_key varchar(50) NOT NULL,
            chapter_number int(11) NOT NULL,
            section_number int(11) NOT NULL,
            section_title varchar(200) NOT NULL,
            total_items int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY subject_chapter (subject_key, chapter_number)
        ) $charset_collate;";
        
        // 項テーブル
        $table_items = $wpdb->prefix . 'study_items';
        $sql_items = "CREATE TABLE $table_items (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            subject_key varchar(50) NOT NULL,
            chapter_number int(11) NOT NULL,
            section_number int(11) NOT NULL,
            item_number int(11) NOT NULL,
            item_title varchar(200) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY subject_chapter_section (subject_key, chapter_number, section_number)
        ) $charset_collate;";
        
        // 学習進捗テーブル
        $table_progress = $wpdb->prefix . 'study_progress';
        $sql_progress = "CREATE TABLE $table_progress (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            subject_key varchar(50) NOT NULL,
            chapter_number int(11) NOT NULL,
            section_number int(11) NOT NULL,
            item_number int(11) NOT NULL,
            understanding_level tinyint(1) DEFAULT 0,
            mastery_level tinyint(1) DEFAULT 0,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_progress (user_id, subject_key, chapter_number, section_number, item_number),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_subjects);
        dbDelta($sql_chapters);
        dbDelta($sql_sections);
        dbDelta($sql_items);
        dbDelta($sql_progress);
    }
    
    // デフォルト設定
    private function set_default_options() {
        // デフォルト科目の挿入
        global $wpdb;
        
        $default_subjects = array(
            array('kenpo', '憲法', 3, '#2196F3'),
            array('gyosei', '行政法', 7, '#4CAF50'),
            array('minpo', '民法', 6, '#FF9800'),
            array('shoho', '商法・会社法', 2, '#9C27B0')
        );
        
        $table_subjects = $wpdb->prefix . 'study_subjects';
        
        foreach ($default_subjects as $subject) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_subjects WHERE subject_key = %s",
                $subject[0]
            ));
            
            if ($existing == 0) {
                $wpdb->insert(
                    $table_subjects,
                    array(
                        'subject_key' => $subject[0],
                        'subject_name' => $subject[1],
                        'total_chapters' => $subject[2],
                        'progress_color' => $subject[3]
                    )
                );
            }
        }
    }
    
    // スタイルとスクリプトの読み込み
    public function enqueue_scripts() {
        wp_enqueue_style('spm-style', SPM_PLUGIN_URL . 'assets/style.css', array(), SPM_VERSION);
        wp_enqueue_script('spm-script', SPM_PLUGIN_URL . 'assets/script.js', array('jquery'), SPM_VERSION, true);
        
        wp_localize_script('spm-script', 'spm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spm_nonce')
        ));
    }
    
    // 管理画面のスタイルとスクリプト
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'study-progress') !== false) {
            wp_enqueue_style('spm-admin-style', SPM_PLUGIN_URL . 'assets/admin-style.css', array(), SPM_VERSION);
            wp_enqueue_script('spm-admin-script', SPM_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), SPM_VERSION, true);
            
            wp_localize_script('spm-admin-script', 'spm_admin_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('spm_admin_nonce')
            ));
        }
    }
    
    // 管理画面メニューの追加
    public function add_admin_menu() {
        add_menu_page(
            '学習進捗管理',
            '学習進捗管理',
            'manage_options',
            'study-progress-manager',
            array($this, 'admin_page'),
            'dashicons-chart-bar',
            30
        );
        
        add_submenu_page(
            'study-progress-manager',
            '科目管理',
            '科目管理',
            'manage_options',
            'study-progress-subjects',
            array($this, 'subjects_page')
        );
        
        add_submenu_page(
            'study-progress-manager',
            '科目構造設定',
            '科目構造設定',
            'manage_options',
            'study-progress-structure',
            array($this, 'structure_page')
        );
        
        add_submenu_page(
            'study-progress-manager',
            '進捗管理',
            '進捗管理',
            'manage_options',
            'study-progress-admin',
            array($this, 'progress_admin_page')
        );
    }
    
    // 管理画面メインページ
    public function admin_page() {
        include SPM_PLUGIN_PATH . 'includes/admin-main.php';
    }
    
    // 科目管理ページ
    public function subjects_page() {
        include SPM_PLUGIN_PATH . 'includes/admin-subjects.php';
    }
    
    // 構造設定ページ
    public function structure_page() {
        include SPM_PLUGIN_PATH . 'includes/admin-structure.php';
    }
    
    // 進捗管理ページ
    public function progress_admin_page() {
        include SPM_PLUGIN_PATH . 'includes/admin-progress.php';
    }
    
    // Ajax: 進捗保存
    public function ajax_save_progress() {
        // nonce検証
        if (!wp_verify_nonce($_POST['nonce'], 'smp_nonce')) {
            wp_die(json_encode(array('success' => false, 'message' => 'セキュリティチェックに失敗しました。')));
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'message' => 'ログインが必要です。')));
        }
        
        $progress_data = json_decode(stripslashes($_POST['progress_data']), true);
        
        if (!$progress_data) {
            wp_die(json_encode(array('success' => false, 'message' => '進捗データが無効です。')));
        }
        
        global $wpdb;
        $table_progress = $wpdb->prefix . 'study_progress';
        
        foreach ($progress_data as $key => $data) {
            $wpdb->replace(
                $table_progress,
                array(
                    'user_id' => $user_id,
                    'subject_key' => sanitize_text_field($data['subject_key']),
                    'chapter_number' => intval($data['chapter']),
                    'section_number' => intval($data['section']),
                    'item_number' => intval($data['item']),
                    'understanding_level' => $data['understanding'] ? 1 : 0,
                    'mastery_level' => $data['mastery'] ? 1 : 0
                )
            );
        }
        
        wp_die(json_encode(array('success' => true, 'message' => '進捗を保存しました。')));
    }
    
    // Ajax: 科目データ読み込み
    public function ajax_load_subjects() {
        global $wpdb;
        
        $subjects = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}study_subjects ORDER BY id");
        wp_die(json_encode(array('success' => true, 'data' => $subjects)));
    }
    
    // Ajax: 科目進捗読み込み
    public function ajax_load_subject_progress() {
        if (!wp_verify_nonce($_POST['nonce'], 'spm_nonce')) {
            wp_die(json_encode(array('success' => false, 'message' => 'セキュリティチェックに失敗しました。')));
        }
        
        $user_id = get_current_user_id();
        $subject_key = sanitize_text_field($_POST['subject_key']);
        
        if (!$user_id) {
            wp_die(json_encode(array('success' => false, 'message' => 'ログインが必要です。')));
        }
        
        global $wpdb;
        
        // 科目構造データ取得
        $chapters = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}study_chapters WHERE subject_key = %s ORDER BY chapter_number",
            $subject_key
        ));
        
        $structure = array('chapters' => array());
        
        foreach ($chapters as $chapter) {
            $sections = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}study_sections WHERE subject_key = %s AND chapter_number = %d ORDER BY section_number",
                $subject_key, $chapter->chapter_number
            ));
            
            $chapter_data = array(
                'chapter_number' => $chapter->chapter_number,
                'chapter_title' => $chapter->chapter_title,
                'sections' => array()
            );
            
            foreach ($sections as $section) {
                $items = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}study_items WHERE subject_key = %s AND chapter_number = %d AND section_number = %d ORDER BY item_number",
                    $subject_key, $chapter->chapter_number, $section->section_number
                ));
                
                $section_data = array(
                    'section_number' => $section->section_number,
                    'section_title' => $section->section_title,
                    'items' => array()
                );
                
                foreach ($items as $item) {
                    $section_data['items'][] = array(
                        'item_number' => $item->item_number,
                        'item_title' => $item->item_title
                    );
                }
                
                $chapter_data['sections'][] = $section_data;
            }
            
            $structure['chapters'][] = $chapter_data;
        }
        
        // 進捗データ取得
        $progress = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}study_progress WHERE user_id = %d AND subject_key = %s",
            $user_id, $subject_key
        ));
        
        $progress_data = array();
        foreach ($progress as $item) {
            $key = $item->chapter_number . '-' . $item->section_number . '-' . $item->item_number;
            $progress_data[$key] = array(
                'understanding' => (bool) $item->understanding_level,
                'mastery' => (bool) $item->mastery_level
            );
        }
        
        wp_die(json_encode(array(
            'success' => true, 
            'data' => array(
                'structure' => $structure,
                'progress' => $progress_data
            )
        )));
    }
    
    // ショートコード表示
    public function shortcode_display($atts) {
        $atts = shortcode_atts(array(
            'subject' => '',
            'mode' => 'full' // full, compact, summary
        ), $atts);
        
        ob_start();
        include SPM_PLUGIN_PATH . 'includes/shortcode-display.php';
        return ob_get_clean();
    }
    
    // プラグイン無効化時の処理
    public function deactivate() {
        // 必要に応じてクリーンアップ処理
    }
}

// プラグインインスタンス化
new StudyProgressManager();