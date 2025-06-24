<?php
/**
 * Plugin Name: 行政書士の道 - マインドマップ (修正版)
 * Description: 行政書士試験対策用のインタラクティブマインドマップ機能
 * Version: 3.0.1
 * Author: 行政書士の道開発チーム
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// プラグイン定数定義
define('GYOSEI_MINDMAP_VERSION', '3.0.1');
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
        add_action('wp_ajax_save_mindmap_data', array($this, 'ajax_save_mindmap_data'));
        add_action('wp_ajax_delete_mindmap_data', array($this, 'ajax_delete_mindmap_data'));
        
        // プラグイン有効化・無効化
        register_activation_hook(__FILE__, array($this, 'plugin_activate'));
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivate'));
        
        // 初期化を早期に実行
        add_action('plugins_loaded', array($this, 'load_dependencies'));
    }
    
    /**
     * 依存クラス読み込み（修正版）
     */
    public function load_dependencies() {
        // ファイル存在チェック付きで順次読み込み
        $files = array(
            'includes/sample-data.php',
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
        
        // サンプルデータを読み込み
        $this->load_sample_data();
    }
    
    /**
     * 初期化処理
     */
    public function init() {
        $this->register_post_types();
        $this->register_custom_endpoints();
    }
    
    /**
     * サンプルデータの読み込み（修正版）
     */
    private function load_sample_data() {
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
     * フロントエンドスクリプト読み込み（修正版）
     */
    public function enqueue_scripts() {
        // CSS読み込み（依存関係を明確化）
        wp_enqueue_style(
            'gyosei-mindmap-css',
            GYOSEI_MINDMAP_PLUGIN_URL . 'assets/mindmap.css',
            array(),
            GYOSEI_MINDMAP_VERSION
        );
        
        // Phase2 CSS（オプション）
        if (file_exists(GYOSEI_MINDMAP_PLUGIN_DIR . 'assets/mindmap-phase2.css')) {
            wp_enqueue_style(
                'gyosei-mindmap-phase2-css',
                GYOSEI_MINDMAP_PLUGIN_URL . 'assets/mindmap-phase2.css',
                array('gyosei-mindmap-css'),
                GYOSEI_MINDMAP_VERSION
            );
        }
        
        // コミュニティ機能CSS（オプション）
        if (file_exists(GYOSEI_MINDMAP_PLUGIN_DIR . 'assets/community.css')) {
            wp_enqueue_style(
                'gyosei-community-css',
                GYOSEI_MINDMAP_PLUGIN_URL . 'assets/community.css',
                array('gyosei-mindmap-css'),
                GYOSEI_MINDMAP_VERSION
            );
        }
        
        // JavaScript読み込み（順序を重要視）
        wp_enqueue_script(
            'gyosei-mindmap-js',
            GYOSEI_MINDMAP_PLUGIN_URL . 'assets/mindmap.js',
            array('jquery'),
            GYOSEI_MINDMAP_VERSION,
            true
        );
        
        // Phase2 JavaScript（オプション）
        if (file_exists(GYOSEI_MINDMAP_PLUGIN_DIR . 'assets/mindmap-phase2.js')) {
            wp_enqueue_script(
                'gyosei-mindmap-phase2-js',
                GYOSEI_MINDMAP_PLUGIN_URL . 'assets/mindmap-phase2.js',
                array('jquery', 'gyosei-mindmap-js'),
                GYOSEI_MINDMAP_VERSION,
                true
            );
        }
        
        // コミュニティ機能JavaScript（オプション）
        if (file_exists(GYOSEI_MINDMAP_PLUGIN_DIR . 'assets/community.js')) {
            wp_enqueue_script(
                'gyosei-community-js',
                GYOSEI_MINDMAP_PLUGIN_URL . 'assets/community.js',
                array('jquery', 'gyosei-mindmap-js'),
                GYOSEI_MINDMAP_VERSION,
                true
            );
        }
        
        // 分析機能JavaScript（オプション）
        if (file_exists(GYOSEI_MINDMAP_PLUGIN_DIR . 'assets/analytics.js')) {
            wp_enqueue_script(
                'gyosei-analytics-js',
                GYOSEI_MINDMAP_PLUGIN_URL . 'assets/analytics.js',
                array('jquery'),
                GYOSEI_MINDMAP_VERSION,
                true
            );
        }
        
        // Chart.js（分析用、CDN使用）
        wp_enqueue_script(
            'chart-js',
            'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
            array(),
            '3.9.1',
            true
        );
        
        // JavaScriptにデータを渡す（null チェック付き）
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
                array('jquery', 'wp-color-picker'),
                GYOSEI_MINDMAP_VERSION,
                true
            );
            
            wp_enqueue_style('wp-color-picker');
            
            wp_localize_script('gyosei-mindmap-admin-js', 'mindmapAdminData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mindmap_admin_nonce'),
                'sampleData' => $this->sample_data ?: array()
            ));
        }
    }
    
    /**
     * 管理画面メニュー追加
     */
    public function add_admin_menu() {
        // メインメニュー
        add_menu_page(
            '行政書士の道 - マインドマップ',
            'マインドマップ',
            'manage_options',
            'gyosei-mindmap',
            array($this, 'admin_dashboard_page'),
            'dashicons-networking',
            30
        );
        
        // サブメニュー
        $submenus = array(
            array('ダッシュボード', 'ダッシュボード', 'gyosei-mindmap', 'admin_dashboard_page'),
            array('マインドマップ管理', 'マインドマップ管理', 'gyosei-mindmap-manage', 'admin_manage_page'),
            array('設定', '設定', 'gyosei-mindmap-settings', 'admin_settings_page')
        );
        
        foreach ($submenus as $submenu) {
            add_submenu_page(
                'gyosei-mindmap',
                $submenu[0],
                $submenu[1], 
                'manage_options',
                $submenu[2],
                array($this, $submenu[3])
            );
        }
    }
    
    /**
     * ショートコード処理（修正版）
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
            // カスタムマップを読み込み
            $map_data = $this->user_manager->get_mindmap($atts['map_id'], get_current_user_id());
            if ($map_data && !empty($map_data['map_data'])) {
                $atts['title'] = $map_data['title'];
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
            return '<div class="mindmap-error">マップデータが見つかりませんでした。</div>';
        }
        
        ob_start();
        ?>
        <div class="mindmap-container" id="<?php echo esc_attr($unique_id); ?>" data-mindmap-id="<?php echo esc_attr($unique_id); ?>">
            <div class="mindmap-header">
                <h3 class="mindmap-title"><?php echo esc_html($atts['title']); ?></h3>
                <div class="mindmap-controls">
                    <?php if ($atts['search'] === 'true'): ?>
                    <div class="mindmap-search-container">
                        <input type="text" class="mindmap-search" placeholder="検索...">
                        <button class="mindmap-search-btn">🔍</button>
                        <button class="mindmap-search-clear" style="display:none;">✕</button>
                    </div>
                    <?php endif; ?>
                    
                    <button class="mindmap-btn" data-action="zoom-in" title="拡大">➕</button>
                    <button class="mindmap-btn" data-action="zoom-out" title="縮小">➖</button>
                    <button class="mindmap-btn" data-action="reset" title="リセット">🏠</button>
                    <button class="mindmap-btn" data-action="fullscreen" title="フルスクリーン">⛶</button>
                    <button class="mindmap-btn" data-action="toggle-theme" title="テーマ切替">🌙</button>
                </div>
            </div>
            
            <div class="mindmap-canvas" 
                 style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;"
                 data-mindmap-type="<?php echo esc_attr($atts['data']); ?>"
                 data-search="<?php echo esc_attr($atts['search']); ?>"
                 data-details="<?php echo esc_attr($atts['details']); ?>"
                 data-draggable="<?php echo esc_attr($atts['draggable']); ?>">
                <div class="mindmap-loading">マインドマップを読み込み中...</div>
            </div>
        </div>
        
        <?php if ($atts['details'] === 'true'): ?>
        <!-- 詳細モーダル -->
        <div id="mindmap-modal-<?php echo esc_attr($unique_id); ?>" class="mindmap-modal" style="display: none;">
            <div class="mindmap-modal-overlay"></div>
            <div class="mindmap-modal-content">
                <div class="mindmap-modal-header">
                    <h3 class="mindmap-modal-title">ノード詳細</h3>
                    <button class="mindmap-modal-close">✕</button>
                </div>
                <div class="mindmap-modal-body">
                    <div class="mindmap-node-info">
                        <div class="mindmap-node-status">
                            <label>ステータス:</label>
                            <span class="status-display"></span>
                        </div>
                        <div class="mindmap-node-progress-display">
                            <label>進捗:</label>
                            <span class="progress-display"></span>
                        </div>
                    </div>
                    
                    <h4>説明</h4>
                    <div class="mindmap-node-description"></div>
                    
                    <h4>関連リソース</h4>
                    <div class="mindmap-resources-list"></div>
                    
                    <?php if (is_user_logged_in()): ?>
                    <h4>学習管理</h4>
                    <div class="mindmap-study-controls">
                        <div class="mindmap-progress-controls">
                            <label>進捗率:</label>
                            <input type="range" class="mindmap-progress-slider" min="0" max="100" value="0">
                            <span class="mindmap-progress-value">0%</span>
                        </div>
                        
                        <div class="mindmap-status-controls">
                            <label>ステータス:</label>
                            <select class="mindmap-status-select">
                                <option value="not-started">未開始</option>
                                <option value="in-progress">学習中</option>
                                <option value="completed">完了</option>
                            </select>
                        </div>
                        
                        <button class="mindmap-save-progress">進捗を保存</button>
                    </div>
                    
                    <div class="mindmap-node-notes">
                        <h4>メモ</h4>
                        <textarea class="mindmap-notes-input" placeholder="学習メモを入力..."></textarea>
                        <button class="mindmap-save-notes">メモを保存</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <script>
        // マップデータをJavaScriptに渡す
        if (typeof mindmapData === 'undefined') {
            window.mindmapData = {};
        }
        if (!mindmapData.sampleData) {
            mindmapData.sampleData = {};
        }
        mindmapData.sampleData['<?php echo esc_js($atts['data']); ?>'] = <?php echo wp_json_encode($map_data); ?>;
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * 管理画面ページ群
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
                <span>Version <?php echo GYOSEI_MINDMAP_VERSION; ?></span>
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
                    <h2>クイックアクション</h2>
                    <p>マインドマップ機能が正常に動作しています。</p>
                    <p><strong>基本ショートコード:</strong> <code>[mindmap data="gyosei" title="行政法" height="500px"]</code></p>
                    <p><strong>詳細機能付き:</strong> <code>[mindmap data="gyosei" details="true" search="true"]</code></p>
                </div>
                
                <div class="gyosei-dashboard-sidebar">
                    <div class="gyosei-widget">
                        <h3 class="gyosei-widget-title">システム状況</h3>
                        <div class="gyosei-widget-content">
                            <p>✅ サンプルデータ: 正常</p>
                            <p>✅ ユーザー管理: <?php echo class_exists('GyoseiUserManager') ? '有効' : '無効'; ?></p>
                            <p>✅ コミュニティ: <?php echo class_exists('GyoseiCommunity') ? '有効' : '無効'; ?></p>
                            <p>✅ AI機能: <?php echo class_exists('GyoseiAIAssistant') ? '有効' : '無効'; ?></p>
                            <p>✅ 分析機能: <?php echo class_exists('GyoseiAnalytics') ? '有効' : '無効'; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function admin_manage_page() {
        ?>
        <div class="wrap">
            <h1>マインドマップ管理</h1>
            <p>マインドマップの管理機能は準備中です。</p>
        </div>
        <?php
    }
    
    public function admin_settings_page() {
        ?>
        <div class="wrap">
            <h1>設定</h1>
            <p>設定画面は準備中です。</p>
        </div>
        <?php
    }
    
    /**
     * プラグイン有効化時の処理
     */
    public function plugin_activate() {
        // テーブル作成（順序重要）
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
        
        // デフォルト設定の作成
        $this->create_default_settings();
    }
    
    /**
     * プラグイン無効化時の処理
     */
    public function plugin_deactivate() {
        flush_rewrite_rules();
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
    
    public function ajax_save_mindmap_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        wp_send_json_success('Admin save functionality available');
    }
    
    public function ajax_delete_mindmap_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_admin_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        wp_send_json_success('Admin delete functionality available');
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
    
    private function load_mindmap_preview_template($map_id) {
        get_header();
        echo '<div class="mindmap-preview-container">';
        echo do_shortcode("[mindmap map_id=\"{$map_id}\" width=\"100%\" height=\"600px\" details=\"true\"]");
        echo '</div>';
        get_footer();
        exit;
    }
    
    private function load_study_group_template($group_id) {
        get_header();
        echo '<div class="study-group-container">';
        echo '<h1>学習グループ</h1>';
        echo '<p>グループID: ' . esc_html($group_id) . '</p>';
        echo '</div>';
        get_footer();
        exit;
    }
}

// プラグインの初期化
new GyoseiMindMapMain();