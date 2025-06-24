<?php
/**
 * Plugin Name: 行政書士の道 - マインドマップ (完全版)
 * Description: 行政書士試験対策用のインタラクティブマインドマップ機能
 * Version: 3.1.0
 * Author: 行政書士の道開発チーム
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// プラグイン定数定義
define('GYOSEI_MINDMAP_VERSION', '3.1.0');
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
        add_action('wp_ajax_save_mindmap_admin', array($this, 'ajax_save_mindmap_admin'));
        add_action('wp_ajax_delete_mindmap_admin', array($this, 'ajax_delete_mindmap_admin'));
        add_action('wp_ajax_duplicate_mindmap', array($this, 'ajax_duplicate_mindmap'));
        add_action('wp_ajax_clear_mindmap_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_export_all_data', array($this, 'ajax_export_all_data'));
        add_action('wp_ajax_import_all_data', array($this, 'ajax_import_all_data'));
        
        // 通知重複問題修正
        add_action('admin_notices', array($this, 'fix_admin_notices'), 1);
        
        // プラグイン有効化・無効化
        register_activation_hook(__FILE__, array($this, 'plugin_activate'));
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivate'));
        
        // 初期化を早期に実行
        add_action('plugins_loaded', array($this, 'load_dependencies'));
    }
    
    /**
     * 管理画面通知の重複問題修正
     */
    public function fix_admin_notices() {
        // 現在のページがプラグインの管理画面かチェック
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'gyosei-mindmap') !== false) {
            // プラグイン管理画面では既存の通知を一時的に削除
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
            
            // 必要最小限の通知のみ再登録
            add_action('admin_notices', array($this, 'show_plugin_notices'));
        }
    }
    
    /**
     * プラグイン専用通知表示
     */
    public function show_plugin_notices() {
        // エラーや重要な通知のみ表示
        if (isset($_GET['message']) && $_GET['message'] === 'updated') {
            echo '<div class="notice notice-success is-dismissible"><p>設定を更新しました。</p></div>';
        }
        
        if (isset($_GET['error']) && $_GET['error'] === 'failed') {
            echo '<div class="notice notice-error is-dismissible"><p>操作に失敗しました。</p></div>';
        }
    }
    
    /**
     * 依存クラス読み込み
     */
    public function load_dependencies() {
        // サンプルデータを最初に読み込み
        $this->load_sample_data();
        
        // 管理画面ページファイルを読み込み
        if (is_admin()) {
            require_once GYOSEI_MINDMAP_PLUGIN_DIR . 'includes/admin-manage-page.php';
            require_once GYOSEI_MINDMAP_PLUGIN_DIR . 'includes/admin-settings-page.php';
        }
        
        // ファイル存在チェック付きで順次読み込み
        $files = array(
            'includes/class-user-manager.php',
            'includes/class-community.php',
            'includes/class-ai-assistant.php',
            'includes/class-analytics.php'
        );
        
        foreach ($files as $file) {
            $file_path = GYOSEI_MINDMAP_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                error_log("GyoseiMindMap: Missing file - {$file}");
            }
        }
        
        // クラス存在チェック付きでインスタンス化
        if (class_exists('GyoseiUserManager')) {
            $this->user_manager = new GyoseiUserManager();
        }
        if (class_exists('GyoseiCommunity')) {
            $this->community = new GyoseiCommunity();
        }
        if (class_exists('GyoseiAIAssistant')) {
            $this->ai_assistant = new GyoseiAIAssistant();
        }
        if (class_exists('GyoseiAnalytics')) {
            $this->analytics = new GyoseiAnalytics();
        }
    }
    
    /**
     * 初期化処理
     */
    public function init() {
        $this->register_post_types();
        $this->register_custom_endpoints();
    }
    
    /**
     * サンプルデータの読み込み
     */
    private function load_sample_data() {
        $sample_file = GYOSEI_MINDMAP_PLUGIN_DIR . 'data/sample-data.php';
        if (file_exists($sample_file)) {
            require_once $sample_file;
        }
        
        if (class_exists('GyoseiMindMapSampleData')) {
            $this->sample_data = GyoseiMindMapSampleData::get_all_data();
        }
        
        // サンプルデータが読み込めない場合のフォールバック
        if (!$this->sample_data) {
            $this->sample_data = $this->get_fallback_data();
        }
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
        // CSS読み込み
        wp_enqueue_style(
            'gyosei-mindmap-css',
            GYOSEI_MINDMAP_PLUGIN_URL . 'assets/mindmap.css',
            array(),
            GYOSEI_MINDMAP_VERSION
        );
        
        // Phase2 CSS
        if (file_exists(GYOSEI_MINDMAP_PLUGIN_DIR . 'assets/mindmap-phase2.css')) {
            wp_enqueue_style(
                'gyosei-mindmap-phase2-css',
                GYOSEI_MINDMAP_PLUGIN_URL . 'assets/mindmap-phase2.css',
                array('gyosei-mindmap-css'),
                GYOSEI_MINDMAP_VERSION
            );
        }
        
        // JavaScript読み込み
        wp_enqueue_script(
            'gyosei-mindmap-js',
            GYOSEI_MINDMAP_PLUGIN_URL . 'assets/mindmap.js',
            array('jquery'),
            GYOSEI_MINDMAP_VERSION,
            true
        );
        
        // Phase2 JavaScript
        if (file_exists(GYOSEI_MINDMAP_PLUGIN_DIR . 'assets/mindmap-phase2.js')) {
            wp_enqueue_script(
                'gyosei-mindmap-phase2-js',
                GYOSEI_MINDMAP_PLUGIN_URL . 'assets/mindmap-phase2.js',
                array('jquery', 'gyosei-mindmap-js'),
                GYOSEI_MINDMAP_VERSION,
                true
            );
        }
        
        // JavaScriptにデータを渡す
        wp_localize_script('gyosei-mindmap-js', 'mindmapData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mindmap_nonce'),
            'sampleData' => $this->sample_data ?: array(),
            'isLoggedIn' => is_user_logged_in(),
            'currentUser' => is_user_logged_in() ? wp_get_current_user()->ID : 0,
            'pluginUrl' => GYOSEI_MINDMAP_PLUGIN_URL,
            'features' => array(
                'community' => class_exists('GyoseiCommunity'),
                'ai_assistant' => class_exists('GyoseiAIAssistant'),
                'analytics' => class_exists('GyoseiAnalytics'),
                'advanced_editor' => true
            )
        ));
    }
    
    /**
     * 管理画面スクリプト読み込み
     */
    public function admin_enqueue_scripts($hook) {
        // プラグインの管理画面でのみ読み込み
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
                array('jquery', 'wp-color-picker', 'jquery-ui-sortable'),
                GYOSEI_MINDMAP_VERSION,
                true
            );
            
            wp_enqueue_style('wp-color-picker');
            
            wp_localize_script('gyosei-mindmap-admin-js', 'mindmapAdminData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mindmap_admin_nonce'),
                'sampleData' => $this->sample_data ?: array(),
                'pluginUrl' => GYOSEI_MINDMAP_PLUGIN_URL
            ));
        }
    }
    
    /**
     * 管理画面メニュー追加
     */
    public function add_admin_menu() {
        add_menu_page(
            '行政書士の道 - マインドマップ',
            'マインドマップ',
            'manage_options',
            'gyosei-mindmap',
            array($this, 'admin_dashboard_page'),
            'dashicons-networking',
            30
        );
        
        add_submenu_page(
            'gyosei-mindmap',
            'ダッシュボード',
            'ダッシュボード', 
            'manage_options',
            'gyosei-mindmap',
            array($this, 'admin_dashboard_page')
        );
        
        add_submenu_page(
            'gyosei-mindmap',
            'マインドマップ管理',
            'マップ管理',
            'manage_options',
            'gyosei-mindmap-manage',
            'gyosei_mindmap_admin_manage_page'
        );
        
        add_submenu_page(
            'gyosei-mindmap',
            '設定',
            '設定',
            'manage_options',
            'gyosei-mindmap-settings',
            'gyosei_mindmap_admin_settings_page'
        );
    }
    
    /**
     * ショートコード処理
     */
    public function mindmap_shortcode($atts) {
        $atts = shortcode_atts(array(
            'data' => 'gyosei',
            'title' => '',
            'width' => '100%',
            'height' => '400px',
            'search' => 'false',
            'details' => 'false',
            'draggable' => 'false',
            'community' => 'false',
            'ai_assistant' => 'false',
            'analytics' => 'false',
            'theme' => 'default',
            'map_id' => 0
        ), $atts);
        
        $unique_id = 'mindmap-' . uniqid();
        $map_data = null;
        
        // データソースの決定
        if ($atts['map_id'] > 0 && $this->user_manager) {
            $map_data = $this->user_manager->get_mindmap($atts['map_id'], get_current_user_id());
            if ($map_data && !empty($map_data['map_data'])) {
                $atts['title'] = $map_data['title'];
                $map_data = $map_data['map_data'];
            }
        } else {
            // サンプルデータを使用
            if (isset($this->sample_data[$atts['data']])) {
                $map_data = $this->sample_data[$atts['data']];
                if (empty($atts['title'])) {
                    $atts['title'] = $map_data['title'] ?? '無題のマップ';
                }
            }
        }
        
        if (!$map_data) {
            return '<div class="mindmap-error">マップデータが見つかりませんでした。利用可能: ' . implode(', ', array_keys($this->sample_data)) . '</div>';
        }
        
        // テンプレートファイルを使用
        ob_start();
        include GYOSEI_MINDMAP_PLUGIN_DIR . 'templates/mindmap-shortcode.php';
        return ob_get_clean();
    }
    
    /**
     * 管理画面ダッシュボードページ
     */
    public function admin_dashboard_page() {
        $stats = array(
            'total_maps' => $this->get_total_maps_count(),
            'active_users' => $this->get_active_users_count(),
            'community_posts' => $this->get_community_posts_count(),
            'ai_consultations' => $this->get_ai_consultations_count()
        );
        
        ?>
        <div class="wrap gyosei-admin-container">
            <div class="gyosei-admin-header">
                <h1>行政書士の道 - マインドマップ管理</h1>
                <div class="header-actions">
                    <span>Version <?php echo GYOSEI_MINDMAP_VERSION; ?></span>
                </div>
            </div>
            
            <div class="gyosei-admin-stats">
                <div class="gyosei-stat-card">
                    <span class="gyosei-stat-number"><?php echo number_format($stats['total_maps']); ?></span>
                    <span class="gyosei-stat-label">総マップ数</span>
                </div>
                <div class="gyosei-stat-card">
                    <span class="gyosei-stat-number"><?php echo number_format($stats['active_users']); ?></span>
                    <span class="gyosei-stat-label">アクティブユーザー</span>
                </div>
                <div class="gyosei-stat-card">
                    <span class="gyosei-stat-number"><?php echo number_format($stats['community_posts']); ?></span>
                    <span class="gyosei-stat-label">コミュニティ投稿</span>
                </div>
                <div class="gyosei-stat-card">
                    <span class="gyosei-stat-number"><?php echo number_format($stats['ai_consultations']); ?></span>
                    <span class="gyosei-stat-label">AI相談件数</span>
                </div>
            </div>
            
            <div class="gyosei-dashboard">
                <div class="gyosei-dashboard-main">
                    <h2>使用方法とショートコード</h2>
                    <div style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        <h3>基本的な使用方法</h3>
                        <p><strong>基本ショートコード:</strong></p>
                        <div class="shortcode-example">
                            <code class="shortcode-code">[mindmap data="gyosei" title="行政法" height="500px"]</code>
                            <button class="button copy-shortcode">コピー</button>
                        </div>
                        
                        <p><strong>詳細機能付き:</strong></p>
                        <div class="shortcode-example">
                            <code class="shortcode-code">[mindmap data="gyosei" details="true" search="true"]</code>
                            <button class="button copy-shortcode">コピー</button>
                        </div>
                        
                        <p><strong>利用可能なデータタイプ:</strong></p>
                        <ul>
                            <li><code>gyosei</code> - 行政法</li>
                            <li><code>minpo</code> - 民法</li>
                            <li><code>kenpou</code> - 憲法</li>
                            <li><code>shoken</code> - 商法・会社法</li>
                            <li><code>general</code> - 一般知識</li>
                        </ul>
                        
                        <h3>プレビュー</h3>
                        <?php echo do_shortcode('[mindmap data="gyosei" height="300px"]'); ?>
                    </div>
                </div>
                
                <div class="gyosei-dashboard-sidebar">
                    <div class="gyosei-widget">
                        <h3 class="gyosei-widget-title">システム状況</h3>
                        <div class="gyosei-widget-content">
                            <p>✅ サンプルデータ: <?php echo !empty($this->sample_data) ? '正常' : 'エラー'; ?></p>
                            <p>✅ ユーザー管理: <?php echo class_exists('GyoseiUserManager') ? '有効' : '無効'; ?></p>
                            <p>✅ コミュニティ: <?php echo class_exists('GyoseiCommunity') ? '有効' : '無効'; ?></p>
                            <p>✅ AI機能: <?php echo class_exists('GyoseiAIAssistant') ? '有効' : '無効'; ?></p>
                            <p>✅ 分析機能: <?php echo class_exists('GyoseiAnalytics') ? '有効' : '無効'; ?></p>
                        </div>
                    </div>
                    
                    <div class="gyosei-widget">
                        <h3 class="gyosei-widget-title">最近の活動</h3>
                        <div class="gyosei-widget-content">
                            <?php $this->show_recent_activity(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .shortcode-example {
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 3px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .shortcode-code {
            flex: 1;
            background: #2d3748;
            color: #e2e8f0;
            padding: 8px 12px;
            border-radius: 3px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
        }
        .gyosei-dashboard {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        .gyosei-dashboard-main, .gyosei-dashboard-sidebar {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .gyosei-widget {
            margin-bottom: 20px;
            padding: 20px;
        }
        .gyosei-widget-title {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 8px;
        }
        .gyosei-widget-content p {
            margin: 8px 0;
            font-size: 14px;
        }
        @media (max-width: 1200px) {
            .gyosei-dashboard {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
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
        
        if ($this->user_manager) {
            $node_id = sanitize_text_field($_POST['node_id']);
            $progress = intval($_POST['progress']);
            $status = sanitize_text_field($_POST['status']);
            $notes = sanitize_textarea_field($_POST['notes'] ?? '');
            $difficulty = intval($_POST['difficulty'] ?? 0);
            
            $result = $this->user_manager->save_user_progress($user_id, $node_id, $progress, $status, $notes, $difficulty);
            
            if ($result) {
                wp_send_json_success('Progress saved');
            } else {
                wp_send_json_error('Failed to save progress');
            }
        } else {
            wp_send_json_error('User manager not available');
        }
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
    
    public function ajax_save_mindmap_admin() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        if ($this->user_manager) {
            $map_id = intval($_POST['map_id'] ?? 0);
            $title = sanitize_text_field($_POST['title']);
            $description = sanitize_textarea_field($_POST['description']);
            $category = sanitize_text_field($_POST['category']);
            $map_data = $_POST['map_data'];
            $is_public = intval($_POST['is_public']);
            $tags = sanitize_text_field($_POST['tags']);
            
            // 管理者として保存
            $user_id = get_current_user_id();
            
            if ($map_id > 0) {
                $result = $this->user_manager->update_mindmap($map_id, $user_id, $title, $description, $category, $map_data, $is_public, $tags);
            } else {
                $result = $this->user_manager->create_mindmap($user_id, $title, $description, $category, $map_data, $is_public, $tags);
            }
            
            if ($result) {
                wp_send_json_success(array('map_id' => $result));
            } else {
                wp_send_json_error('Failed to save mindmap');
            }
        } else {
            wp_send_json_error('User manager not available');
        }
    }
    
    public function ajax_delete_mindmap_admin() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $map_id = intval($_POST['map_id']);
        
        if (function_exists('gyosei_delete_mindmap_admin')) {
            $result = gyosei_delete_mindmap_admin($map_id);
            
            if ($result) {
                wp_send_json_success('Mindmap deleted');
            } else {
                wp_send_json_error('Failed to delete mindmap');
            }
        } else {
            wp_send_json_error('Function not available');
        }
    }
    
    public function ajax_duplicate_mindmap() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $map_id = intval($_POST['map_id']);
        
        if ($this->user_manager) {
            $original_map = $this->user_manager->get_mindmap($map_id, get_current_user_id());
            
            if ($original_map) {
                $title = $original_map['title'] . ' (コピー)';
                $result = $this->user_manager->create_mindmap(
                    get_current_user_id(),
                    $title,
                    $original_map['description'],
                    $original_map['category'],
                    json_encode($original_map['map_data']),
                    false, // 複製は非公開
                    $original_map['tags']
                );
                
                if ($result) {
                    wp_send_json_success(array('new_map_id' => $result));
                } else {
                    wp_send_json_error('Failed to duplicate mindmap');
                }
            } else {
                wp_send_json_error('Original mindmap not found');
            }
        } else {
            wp_send_json_error('User manager not available');
        }
    }
    
    public function ajax_clear_cache() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // WordPressキャッシュクリア
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        // トランジェントクリア
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%gyosei%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%gyosei%'");
        
        wp_send_json_success('Cache cleared');
    }
    
    public function ajax_export_all_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $export_data = array(
            'version' => GYOSEI_MINDMAP_VERSION,
            'export_date' => current_time('mysql'),
            'mindmaps' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gyosei_mindmaps", ARRAY_A),
            'user_progress' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}gyosei_user_progress", ARRAY_A),
            'settings' => array()
        );
        
        // 設定データの取得
        $settings = $wpdb->get_results(
            "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'gyosei_mindmap_%'",
            ARRAY_A
        );
        
        foreach ($settings as $setting) {
            $export_data['settings'][$setting['option_name']] = $setting['option_value'];
        }
        
        wp_send_json_success($export_data);
    }
    
    public function ajax_import_all_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $import_data = json_decode(stripslashes($_POST['data']), true);
        
        if (!$import_data || !is_array($import_data)) {
            wp_send_json_error('Invalid import data');
        }
        
        global $wpdb;
        
        try {
            // マインドマップのインポート
            if (isset($import_data['mindmaps'])) {
                foreach ($import_data['mindmaps'] as $mindmap) {
                    unset($mindmap['id']); // IDをリセット
                    $wpdb->insert($wpdb->prefix . 'gyosei_mindmaps', $mindmap);
                }
            }
            
            // 設定のインポート
            if (isset($import_data['settings'])) {
                foreach ($import_data['settings'] as $option_name => $option_value) {
                    update_option($option_name, $option_value);
                }
            }
            
            wp_send_json_success('Data imported successfully');
        } catch (Exception $e) {
            wp_send_json_error('Import failed: ' . $e->getMessage());
        }
    }
    
    /**
     * プラグイン有効化時の処理
     */
    public function plugin_activate() {
        // テーブル作成
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
        
        $this->create_default_settings();
    }
    
    /**
     * プラグイン無効化時の処理
     */
    public function plugin_deactivate() {
        flush_rewrite_rules();
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
        if ($user_id && $this->user_manager) {
            $user_progress = $this->user_manager->get_user_node_progress($user_id, $node_id);
            if ($user_progress) {
                $details = array_merge($details, $user_progress);
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
            'gyosei_root' => array(
                array('title' => '行政法入門', 'url' => '#', 'type' => '教科書'),
                array('title' => '行政法判例集', 'url' => '#', 'type' => '判例集')
            ),
            'administrative_action' => array(
                array('title' => '行政行為の基礎理論', 'url' => '#', 'type' => '論文'),
                array('title' => '行政裁量の判例分析', 'url' => '#', 'type' => '判例解説')
            )
        );
        
        return $resources[$node_id] ?? array();
    }
    
    private function get_fallback_data() {
        return array(
            'gyosei' => array(
                'title' => '行政法（基本版）',
                'description' => '行政法の基本構造',
                'nodes' => array(
                    array(
                        'id' => 'gyosei_root',
                        'text' => '行政法',
                        'x' => 400,
                        'y' => 250,
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
        if (!$this->user_manager) return 0;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gyosei_user_progress';
        
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
        if (!$this->community) return 0;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gyosei_map_comments';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") != $table_name) {
            return 0;
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}") ?? 0;
    }
    
    private function get_ai_consultations_count() {
        if (!$this->ai_assistant) return 0;
        
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
            'auto_save_interval' => 30,
            'default_theme' => 'default'
        );
        
        foreach ($default_settings as $key => $value) {
            add_option("gyosei_mindmap_{$key}", $value);
        }
    }
    
    private function show_recent_activity() {
        global $wpdb;
        
        // 最近のマップ作成
        $recent_maps = $wpdb->get_results(
            "SELECT m.title, m.created_at, u.display_name 
             FROM {$wpdb->prefix}gyosei_mindmaps m 
             LEFT JOIN {$wpdb->users} u ON m.creator_id = u.ID 
             ORDER BY m.created_at DESC 
             LIMIT 5"
        );
        
        if (empty($recent_maps)) {
            echo '<p>最近の活動はありません。</p>';
            return;
        }
        
        echo '<ul>';
        foreach ($recent_maps as $map) {
            $time_ago = human_time_diff(strtotime($map->created_at), current_time('timestamp'));
            echo '<li>';
            echo '<strong>' . esc_html($map->title) . '</strong><br>';
            echo '作成者: ' . esc_html($map->display_name ?: 'システム') . '<br>';
            echo '<small>' . $time_ago . '前</small>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    private function load_mindmap_preview_template($map_id) {
        get_header();
        echo '<div class="mindmap-preview-container" style="padding: 20px;">';
        echo '<h1>マインドマップ プレビュー</h1>';
        echo do_shortcode("[mindmap map_id=\"{$map_id}\" width=\"100%\" height=\"600px\" details=\"true\" search=\"true\"]");
        echo '</div>';
        get_footer();
        exit;
    }
    
    private function load_study_group_template($group_id) {
        get_header();
        echo '<div class="study-group-container" style="padding: 20px;">';
        echo '<h1>学習グループ</h1>';
        echo '<p>グループID: ' . esc_html($group_id) . '</p>';
        echo '<p>この機能は開発中です。</p>';
        echo '</div>';
        get_footer();
        exit;
    }
}

// プラグインの初期化
new GyoseiMindMapMain();

/**
 * プラグイン完全削除時の処理
 */
register_uninstall_hook(__FILE__, 'gyosei_mindmap_uninstall');

function gyosei_mindmap_uninstall() {
    global $wpdb;
    
    // テーブル削除
    $tables = array(
        $wpdb->prefix . 'gyosei_mindmaps',
        $wpdb->prefix . 'gyosei_user_progress',
        $wpdb->prefix . 'gyosei_learning_sessions',
        $wpdb->prefix . 'gyosei_user_settings',
        $wpdb->prefix . 'gyosei_map_comments',
        $wpdb->prefix . 'gyosei_map_likes',
        $wpdb->prefix . 'gyosei_user_follows',
        $wpdb->prefix . 'gyosei_study_groups',
        $wpdb->prefix . 'gyosei_group_members',
        $wpdb->prefix . 'gyosei_learning_patterns',
        $wpdb->prefix . 'gyosei_ai_recommendations',
        $wpdb->prefix . 'gyosei_chat_sessions',
        $wpdb->prefix . 'gyosei_weakness_analysis',
        $wpdb->prefix . 'gyosei_analytics',
        $wpdb->prefix . 'gyosei_badges',
        $wpdb->prefix . 'gyosei_user_badges',
        $wpdb->prefix . 'gyosei_achievements'
    );
    
    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
    
    // オプション削除
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'gyosei_mindmap_%'"
    );
    
    // 投稿タイプのデータ削除
    $wpdb->query(
        "DELETE FROM {$wpdb->posts} WHERE post_type = 'gyosei_mindmap'"
    );
    
    // トランジェント削除
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%gyosei%'"
    );
    $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%gyosei%'"
    );
    
    // キャッシュクリア
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}

/**
 * 緊急時のデータリセット関数
 */
function gyosei_emergency_reset() {
    if (current_user_can('administrator') && isset($_GET['gyosei_emergency_reset']) && $_GET['gyosei_emergency_reset'] === 'confirm') {
        global $wpdb;
        
        // 全テーブルをトランケート
        $tables = array(
            $wpdb->prefix . 'gyosei_mindmaps',
            $wpdb->prefix . 'gyosei_user_progress',
            $wpdb->prefix . 'gyosei_learning_sessions',
            $wpdb->prefix . 'gyosei_user_settings',
            $wpdb->prefix . 'gyosei_map_comments',
            $wpdb->prefix . 'gyosei_map_likes',
            $wpdb->prefix . 'gyosei_user_follows',
            $wpdb->prefix . 'gyosei_study_groups',
            $wpdb->prefix . 'gyosei_group_members',
            $wpdb->prefix . 'gyosei_learning_patterns',
            $wpdb->prefix . 'gyosei_ai_recommendations',
            $wpdb->prefix . 'gyosei_chat_sessions',
            $wpdb->prefix . 'gyosei_weakness_analysis',
            $wpdb->prefix . 'gyosei_analytics',
            $wpdb->prefix . 'gyosei_badges',
            $wpdb->prefix . 'gyosei_user_badges',
            $wpdb->prefix . 'gyosei_achievements'
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table) {
                $wpdb->query("TRUNCATE TABLE {$table}");
            }
        }
        
        wp_redirect(admin_url('admin.php?page=gyosei-mindmap&message=reset_complete'));
        exit;
    }
}
add_action('admin_init', 'gyosei_emergency_reset');

/**
 * 重要: CSS修正版 - 管理画面の通知重複問題を解決
 */
function gyosei_admin_css_fix() {
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'gyosei-mindmap') !== false) {
        ?>
        <style>
        /* 管理画面通知の重複問題修正 */
        .gyosei-admin-container {
            margin-top: 0 !important;
            position: relative;
            z-index: 1;
            clear: both;
        }
        
        /* WordPressの既存通知との競合を防ぐ */
        .wrap .gyosei-admin-header {
            margin-top: 20px;
            margin-bottom: 20px;
            clear: both;
        }
        
        /* WordPress標準の通知を非表示 */
        .gyosei-admin-container ~ .notice,
        .gyosei-admin-container ~ .updated,
        .gyosei-admin-container ~ .error {
            display: none !important;
        }
        
        /* プラグイン独自の通知スタイル */
        .gyosei-admin-notice {
            margin: 5px 0 15px 0 !important;
            clear: both;
            position: relative;
            z-index: 10;
        }
        
        /* 管理画面ヘッダーのスタイル改善 */
        .gyosei-admin-header {
            background: linear-gradient(135deg, #3f51b5, #303f9f);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .gyosei-admin-header h1 {
            margin: 0;
            color: white;
            font-size: 24px;
            font-weight: 600;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .header-actions .button {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 4px;
            padding: 8px 16px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .header-actions .button:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            text-decoration: none;
        }
        
        .header-actions .button-primary {
            background: rgba(255,255,255,0.9);
            color: #3f51b5;
            font-weight: 600;
        }
        
        .header-actions .button-primary:hover {
            background: white;
            color: #303f9f;
        }
        
        /* WordPress管理画面のフロート問題を修正 */
        .gyosei-admin-container::before,
        .gyosei-admin-container::after {
            content: "";
            display: table;
            clear: both;
        }
        
        /* 統計カードのスタイル */
        .gyosei-admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .gyosei-stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            border-left: 4px solid #3f51b5;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .gyosei-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .gyosei-stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #3f51b5;
            display: block;
            line-height: 1.2;
            margin-bottom: 8px;
        }
        
        .gyosei-stat-label {
            color: #666;
            font-size: 0.9em;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* レスポンシブ対応 */
        @media (max-width: 1200px) {
            .gyosei-admin-header {
                flex-direction: column;
                text-align: center;
            }
        }
        
        @media (max-width: 768px) {
            .gyosei-admin-stats {
                grid-template-columns: 1fr;
            }
            
            .gyosei-admin-header {
                padding: 15px;
            }
            
            .gyosei-admin-header h1 {
                font-size: 20px;
            }
        }
        </style>
        <?php
    }
}
add_action('admin_head', 'gyosei_admin_css_fix');

/**
 * デバッグ情報表示（開発時のみ）
 */
function gyosei_debug_info() {
    if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('administrator')) {
        if (isset($_GET['gyosei_debug'])) {
            echo '<div style="background: #000; color: #0f0; padding: 20px; margin: 20px 0; font-family: monospace;">';
            echo '<h3>🔧 GyoseiMindMap Debug Info</h3>';
            echo '<p><strong>Plugin Version:</strong> ' . GYOSEI_MINDMAP_VERSION . '</p>';
            echo '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';
            echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';
            echo '<p><strong>Plugin Directory:</strong> ' . GYOSEI_MINDMAP_PLUGIN_DIR . '</p>';
            echo '<p><strong>Plugin URL:</strong> ' . GYOSEI_MINDMAP_PLUGIN_URL . '</p>';
            
            // クラスの存在確認
            $classes = array(
                'GyoseiUserManager',
                'GyoseiCommunity', 
                'GyoseiAIAssistant',
                'GyoseiAnalytics'
            );
            
            echo '<p><strong>Classes Status:</strong></p>';
            echo '<ul>';
            foreach ($classes as $class) {
                $status = class_exists($class) ? '✅ Available' : '❌ Missing';
                echo "<li>{$class}: {$status}</li>";
            }
            echo '</ul>';
            
            // データベース状況
            global $wpdb;
            $tables = array(
                'gyosei_mindmaps',
                'gyosei_user_progress',
                'gyosei_learning_sessions'
            );
            
            echo '<p><strong>Database Tables:</strong></p>';
            echo '<ul>';
            foreach ($tables as $table) {
                $full_table = $wpdb->prefix . $table;
                $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table}'") == $full_table;
                $status = $exists ? '✅ Exists' : '❌ Missing';
                $count = $exists ? $wpdb->get_var("SELECT COUNT(*) FROM {$full_table}") : 0;
                echo "<li>{$table}: {$status} ({$count} records)</li>";
            }
            echo '</ul>';
            
            echo '</div>';
        }
    }
}
add_action('wp_footer', 'gyosei_debug_info');
add_action('admin_footer', 'gyosei_debug_info');
?>