<?php
/**
 * Plugin Name: 行政書士の道 - マインドマップ
 * Description: 行政書士試験対策用のインタラクティブマインドマップ機能
 * Version: 2.0.0
 * Author: 行政書士の道開発チーム
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// プラグインの定数定義
define('GYOSEI_MINDMAP_VERSION', '2.0.0');
define('GYOSEI_MINDMAP_PATH', plugin_dir_path(__FILE__));
define('GYOSEI_MINDMAP_URL', plugin_dir_url(__FILE__));

// 基底クラス: 基本機能のみ
class GyoseiMindMap {
    
    protected static $instance = null;
    protected $features_loaded = array();
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // 基本ショートコード
        add_shortcode('mindmap', array($this, 'mindmap_shortcode'));
        
        // 基本Ajax処理のみ
        add_action('wp_ajax_get_node_details', array($this, 'ajax_get_node_details'));
        add_action('wp_ajax_nopriv_get_node_details', array($this, 'ajax_get_node_details'));
        add_action('wp_ajax_update_node_progress', array($this, 'ajax_update_node_progress'));
        
        // 管理画面用Ajax
        add_action('wp_ajax_save_mindmap_data', array($this, 'ajax_save_mindmap_data'));
        add_action('wp_ajax_delete_mindmap_data', array($this, 'ajax_delete_mindmap_data'));
        
        // プラグイン有効化/無効化
        register_activation_hook(__FILE__, array($this, 'plugin_activate'));
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivate'));
        
        // 拡張機能の読み込み
        $this->load_extensions();
    }
    
    // 拡張機能の読み込み
    private function load_extensions() {
        // Phase 2: 検索・詳細機能
        if (file_exists(GYOSEI_MINDMAP_PATH . 'gyosei-mindmap-phase2.php')) {
            require_once GYOSEI_MINDMAP_PATH . 'gyosei-mindmap-phase2.php';
            $this->features_loaded[] = 'phase2';
        }
        
        // Phase 3A: ユーザー管理機能
        if (file_exists(GYOSEI_MINDMAP_PATH . 'user-admin.php')) {
            require_once GYOSEI_MINDMAP_PATH . 'user-admin.php';
            $this->features_loaded[] = 'phase3a';
        }
        
        // Phase 3B: コミュニティ機能
        if (file_exists(GYOSEI_MINDMAP_PATH . 'COMMUNITY.php')) {
            require_once GYOSEI_MINDMAP_PATH . 'COMMUNITY.php';
            $this->features_loaded[] = 'community';
        }
        
        // サンプルデータ
        if (file_exists(GYOSEI_MINDMAP_PATH . 'data/sample-data.php')) {
            require_once GYOSEI_MINDMAP_PATH . 'data/sample-data.php';
        }
    }
    
    public function init() {
        $this->register_post_types();
        $this->create_basic_tables();
    }
    
    // カスタム投稿タイプの登録
    public function register_post_types() {
        $args = array(
            'labels' => array(
                'name' => 'マインドマップ',
                'singular_name' => 'マインドマップ',
                'add_new' => '新規追加',
                'add_new_item' => '新しいマインドマップを追加',
                'edit_item' => 'マインドマップを編集',
                'all_items' => '全てのマインドマップ',
                'view_item' => 'マインドマップを表示'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title', 'editor'),
            'capability_type' => 'post'
        );
        
        register_post_type('gyosei_mindmap', $args);
    }
    
    // 基本テーブルのみ作成（拡張テーブルは各クラスで作成）
    protected function create_basic_tables() {
        // 基本テーブルのみ作成
        // 拡張テーブルは各Phase クラスで作成
    }
    
    public function enqueue_scripts() {
        // 基本CSS
        wp_enqueue_style(
            'gyosei-mindmap-css',
            GYOSEI_MINDMAP_URL . 'assets/mindmap.css',
            array(),
            GYOSEI_MINDMAP_VERSION
        );
        
        // 基本JavaScript
        wp_enqueue_script(
            'gyosei-mindmap-js',
            GYOSEI_MINDMAP_URL . 'assets/mindmap.js',
            array('jquery'),
            GYOSEI_MINDMAP_VERSION,
            true
        );
        
        // 読み込まれた機能に応じて追加CSS/JSを読み込み
        $this->enqueue_extension_assets();
        
        // データをJavaScriptに渡す
        wp_localize_script('gyosei-mindmap-js', 'mindmapData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mindmap_nonce'),
            'sampleData' => $this->get_sample_data(),
            'currentUser' => is_user_logged_in() ? get_current_user_id() : 0,
            'featuresLoaded' => $this->features_loaded
        ));
    }
    
    // 拡張機能のアセット読み込み
    private function enqueue_extension_assets() {
        if (in_array('phase2', $this->features_loaded)) {
            wp_enqueue_style(
                'gyosei-mindmap-phase2-css',
                GYOSEI_MINDMAP_URL . 'assets/mindmap-phase2.css',
                array('gyosei-mindmap-css'),
                GYOSEI_MINDMAP_VERSION
            );
            
            wp_enqueue_script(
                'gyosei-mindmap-phase2-js',
                GYOSEI_MINDMAP_URL . 'assets/mindmap-phase2.js',
                array('gyosei-mindmap-js'),
                GYOSEI_MINDMAP_VERSION,
                true
            );
        }
        
        if (in_array('phase3a', $this->features_loaded)) {
            wp_enqueue_style(
                'gyosei-mindmap-phase3-css',
                GYOSEI_MINDMAP_URL . 'assets/mindmap-phase3.css',
                array('gyosei-mindmap-phase2-css'),
                GYOSEI_MINDMAP_VERSION
            );
            
            wp_enqueue_script(
                'gyosei-mindmap-phase3-js',
                GYOSEI_MINDMAP_URL . 'assets/USER-ADMIN.js',
                array('gyosei-mindmap-phase2-js'),
                GYOSEI_MINDMAP_VERSION,
                true
            );
        }
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'gyosei-mindmap') !== false) {
            wp_enqueue_style(
                'gyosei-mindmap-admin-css',
                GYOSEI_MINDMAP_URL . 'assets/admin.css',
                array(),
                GYOSEI_MINDMAP_VERSION
            );
        }
    }
    
    // 管理画面メニューの追加
    public function add_admin_menu() {
        add_menu_page(
            '行政書士の道 - マインドマップ',
            'マインドマップ',
            'manage_options',
            'gyosei-mindmap',
            array($this, 'admin_page'),
            'dashicons-networking',
            30
        );
        
        add_submenu_page(
            'gyosei-mindmap',
            'マインドマップ一覧',
            '一覧',
            'manage_options',
            'gyosei-mindmap',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'gyosei-mindmap',
            '設定',
            '設定',
            'manage_options',
            'gyosei-mindmap-settings',
            array($this, 'admin_page_settings')
        );
        
        add_submenu_page(
            'gyosei-mindmap',
            '使い方',
            '使い方',
            'manage_options',
            'gyosei-mindmap-help',
            array($this, 'admin_page_help')
        );
    }
    
    // 基本ショートコード
    public function mindmap_shortcode($atts) {
        $atts = shortcode_atts(array(
            'data' => 'gyosei',
            'title' => '行政法',
            'width' => '100%',
            'height' => '400px',
            'search' => 'false',
            'details' => 'false',
            'draggable' => 'false',
            'editable' => 'false',
            'custom_id' => '',
            'community' => 'false'
        ), $atts);
        
        $unique_id = 'mindmap-' . uniqid();
        
        // 機能レベルの判定
        $phase_level = $this->determine_phase_level($atts);
        
        // クラス名の決定
        $container_classes = array('mindmap-container');
        
        if ($phase_level >= 2) {
            $container_classes[] = 'mindmap-phase2';
        }
        
        if ($phase_level >= 3) {
            $container_classes[] = 'mindmap-phase3a';
        }
        
        if ($atts['community'] === 'true') {
            $container_classes[] = 'mindmap-community';
        }
        
        if ($atts['editable'] === 'true') {
            $container_classes[] = 'editable';
        }
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>" 
             data-mindmap-id="<?php echo esc_attr($unique_id); ?>"
             data-custom-id="<?php echo esc_attr($atts['custom_id']); ?>"
             data-phase-level="<?php echo esc_attr($phase_level); ?>">
            
            <?php echo $this->render_header($atts, $unique_id); ?>
            <?php echo $this->render_canvas($atts, $unique_id); ?>
            <?php echo $this->render_loading(); ?>
            
        </div>
        
        <?php
        // 機能レベルに応じてモーダルを追加
        if ($phase_level >= 2 && $atts['details'] === 'true') {
            echo $this->render_details_modal($unique_id);
        }
        
        return ob_get_clean();
    }
    
    // フェーズレベルの判定
    private function determine_phase_level($atts) {
        $level = 1; // 基本レベル
        
        // Phase 2 機能が使われているか
        if ($atts['search'] === 'true' || $atts['details'] === 'true' || $atts['draggable'] === 'true') {
            $level = 2;
        }
        
        // Phase 3 機能が使われているか
        if ($atts['editable'] === 'true' || $atts['custom_id'] || is_user_logged_in()) {
            $level = 3;
        }
        
        return $level;
    }
    
    // ヘッダー部分のレンダリング
    private function render_header($atts, $unique_id) {
        ob_start();
        ?>
        <div class="mindmap-header">
            <h3 class="mindmap-title"><?php echo esc_html($atts['title']); ?></h3>
            <div class="mindmap-controls">
                
                <?php if ($atts['search'] === 'true' && in_array('phase2', $this->features_loaded)): ?>
                <div class="mindmap-search-container">
                    <input type="text" class="mindmap-search" placeholder="ノードを検索...">
                    <button class="mindmap-btn mindmap-search-btn">🔍</button>
                    <button class="mindmap-btn mindmap-search-clear" style="display:none;">✕</button>
                </div>
                <?php endif; ?>
                
                <button class="mindmap-btn" data-action="zoom-in">🔍+</button>
                <button class="mindmap-btn" data-action="zoom-out">🔍-</button>
                <button class="mindmap-btn" data-action="reset">⚪</button>
                <button class="mindmap-btn" data-action="fullscreen">⛶</button>
                <button class="mindmap-theme-toggle" data-action="toggle-theme">🌙</button>
                
                <?php if (is_user_logged_in() && in_array('phase3a', $this->features_loaded)): ?>
                <div class="mindmap-user-controls">
                    <button class="mindmap-btn" data-action="user-maps">📁 マイマップ</button>
                    <button class="mindmap-btn" data-action="create-map">➕ 新規作成</button>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // キャンバス部分のレンダリング
    private function render_canvas($atts, $unique_id) {
        ob_start();
        ?>
        <div class="mindmap-canvas" 
             id="<?php echo esc_attr($unique_id); ?>"
             style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;"
             data-mindmap-type="<?php echo esc_attr($atts['data']); ?>"
             data-search="<?php echo esc_attr($atts['search']); ?>"
             data-details="<?php echo esc_attr($atts['details']); ?>"
             data-draggable="<?php echo esc_attr($atts['draggable']); ?>"
             data-editable="<?php echo esc_attr($atts['editable']); ?>">
        </div>
        <?php
        return ob_get_clean();
    }
    
    // ローディング表示
    private function render_loading() {
        return '<div class="mindmap-loading"><span>マインドマップを読み込み中...</span></div>';
    }
    
    // 詳細モーダル（基本版）
    private function render_details_modal($unique_id) {
        ob_start();
        ?>
        <div class="mindmap-modal" id="mindmap-modal-<?php echo esc_attr($unique_id); ?>" style="display: none;">
            <div class="mindmap-modal-overlay"></div>
            <div class="mindmap-modal-content">
                <div class="mindmap-modal-header">
                    <h3 class="mindmap-modal-title"></h3>
                    <button class="mindmap-modal-close">✕</button>
                </div>
                <div class="mindmap-modal-body">
                    <div class="mindmap-node-description"></div>
                    <?php if (is_user_logged_in()): ?>
                    <div class="mindmap-study-controls">
                        <h4>学習管理</h4>
                        <div class="mindmap-progress-controls">
                            <label>進捗率:</label>
                            <input type="range" class="mindmap-progress-slider" min="0" max="100" step="5">
                            <span class="mindmap-progress-value">0%</span>
                        </div>
                        <button class="mindmap-save-progress">進捗を保存</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Ajax処理: ノード詳細取得
    public function ajax_get_node_details() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        $node_id = sanitize_text_field($_POST['node_id']);
        $map_type = sanitize_text_field($_POST['map_type']);
        
        $node_details = $this->get_node_details($node_id, $map_type);
        
        if ($node_details) {
            wp_send_json_success($node_details);
        } else {
            wp_send_json_error('Node not found');
        }
    }
    
    // Ajax処理: 進捗更新
    public function ajax_update_node_progress() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        $node_id = sanitize_text_field($_POST['node_id']);
        $progress = intval($_POST['progress']);
        $status = sanitize_text_field($_POST['status'] ?? 'not-started');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('ログインが必要です');
        }
        
        $progress_data = array(
            'progress' => $progress,
            'status' => $status,
            'notes' => $notes,
            'updated' => current_time('mysql')
        );
        
        update_user_meta($user_id, "mindmap_progress_{$node_id}", $progress_data);
        wp_send_json_success('Progress saved');
    }
    
    // 管理画面Ajax処理（基本版）
    public function ajax_save_mindmap_data() {
        check_ajax_referer('mindmap_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        // 基本的な保存処理
        wp_send_json_success('保存されました');
    }
    
    public function ajax_delete_mindmap_data() {
        check_ajax_referer('mindmap_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        $id = intval($_POST['id']);
        $result = wp_delete_post($id, true);
        
        if ($result) {
            wp_send_json_success('削除されました');
        } else {
            wp_send_json_error('削除に失敗しました');
        }
    }
    
    // ユーティリティメソッド
    protected function get_node_details($node_id, $map_type) {
        $sample_data = $this->get_sample_data();
        
        if (!isset($sample_data[$map_type])) {
            return false;
        }
        
        $nodes = $sample_data[$map_type]['nodes'];
        foreach ($nodes as $node) {
            if ($node['id'] === $node_id) {
                // ユーザーの進捗情報を追加
                $user_id = get_current_user_id();
                if ($user_id) {
                    $user_progress = get_user_meta($user_id, "mindmap_progress_{$node_id}", true);
                    if ($user_progress) {
                        $node['progress'] = $user_progress['progress'];
                        $node['status'] = $user_progress['status'];
                        $node['notes'] = $user_progress['notes'] ?? '';
                    }
                }
                return $node;
            }
        }
        
        return false;
    }
    
    public function get_sample_data() {
        if (class_exists('GyoseiMindMapSampleData')) {
            return GyoseiMindMapSampleData::get_all_data();
        }
        
        // フォールバック
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
    
    // プラグイン有効化
    public function plugin_activate() {
        $this->register_post_types();
        $this->create_basic_tables();
        flush_rewrite_rules();
        
        // デフォルト設定
        add_option('gyosei_mindmap_default_width', '100%');
        add_option('gyosei_mindmap_default_height', '400px');
        add_option('gyosei_mindmap_enable_search', 1);
        add_option('gyosei_mindmap_enable_details', 1);
    }
    
    // プラグイン無効化
    public function plugin_deactivate() {
        flush_rewrite_rules();
    }
    
    // 管理画面ページ
    public function admin_page() {
        include_once GYOSEI_MINDMAP_PATH . 'admin/admin-page.php';
    }
    
    public function admin_page_settings() {
        include_once GYOSEI_MINDMAP_PATH . 'admin/settings-page.php';
    }
    
    public function admin_page_help() {
        include_once GYOSEI_MINDMAP_PATH . 'admin/help-page.php';
    }
}

// プラグインの初期化
GyoseiMindMap::get_instance();