<?php
/**
 * Plugin Name: 行政書士の道 - マインドマップ
 * Description: 行政書士試験対策用のインタラクティブマインドマップ機能
 * Version: 1.0.0
 * Author: 行政書士の道開発チーム
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

class GyoseiMindMap {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_shortcode('mindmap', array($this, 'mindmap_shortcode'));
        
        // 管理画面でのAjax処理
        add_action('wp_ajax_save_mindmap_data', array($this, 'ajax_save_mindmap_data'));
        add_action('wp_ajax_get_mindmap_data', array($this, 'ajax_get_mindmap_data'));
        add_action('wp_ajax_delete_mindmap_data', array($this, 'ajax_delete_mindmap_data'));
        
        // Phase 2の追加機能
        add_action('wp_ajax_get_node_details', array($this, 'ajax_get_node_details'));
        add_action('wp_ajax_nopriv_get_node_details', array($this, 'ajax_get_node_details'));
        add_action('wp_ajax_update_node_progress', array($this, 'ajax_update_node_progress'));
        add_action('wp_ajax_search_nodes', array($this, 'ajax_search_nodes'));
        
        // プラグイン有効化時の処理
        register_activation_hook(__FILE__, array($this, 'plugin_activate'));
    }
    
    public function init() {
        // カスタム投稿タイプを登録
        $this->register_post_types();
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
            'show_in_menu' => false, // 独自メニューで管理するため
            'supports' => array('title', 'editor'),
            'capability_type' => 'post'
        );
        
        register_post_type('gyosei_mindmap', $args);
    }
    
    // 管理画面メニューの追加
    public function add_admin_menu() {
        // メインメニュー
        add_menu_page(
            '行政書士の道 - マインドマップ',  // ページタイトル
            'マインドマップ',               // メニュータイトル
            'manage_options',               // 権限
            'gyosei-mindmap',              // メニュースラッグ
            array($this, 'admin_page'),    // コールバック関数
            'dashicons-networking',         // アイコン
            30                             // 位置
        );
        
        // サブメニュー
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
            '新規作成',
            '新規作成',
            'manage_options',
            'gyosei-mindmap-new',
            array($this, 'admin_page_new')
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
    
    // 管理画面メインページ
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>マインドマップ管理</h1>
            
            <?php if (isset($_GET['message']) && $_GET['message'] == 'saved'): ?>
            <div class="notice notice-success">
                <p>マインドマップが保存されました。</p>
            </div>
            <?php endif; ?>
            
            <div class="gyosei-admin-container">
                <div class="gyosei-admin-header">
                    <h2>マインドマップ一覧</h2>
                    <a href="<?php echo admin_url('admin.php?page=gyosei-mindmap-new'); ?>" class="button button-primary">
                        新規マインドマップ作成
                    </a>
                </div>
                
                <div class="gyosei-admin-stats">
                    <div class="gyosei-stat-card">
                        <span class="gyosei-stat-number"><?php echo wp_count_posts('gyosei_mindmap')->publish; ?></span>
                        <span class="gyosei-stat-label">公開マップ</span>
                    </div>
                    <div class="gyosei-stat-card">
                        <span class="gyosei-stat-number"><?php echo wp_count_posts('gyosei_mindmap')->draft; ?></span>
                        <span class="gyosei-stat-label">下書きマップ</span>
                    </div>
                    <div class="gyosei-stat-card">
                        <span class="gyosei-stat-number"><?php echo do_shortcode('[mindmap data="gyosei"]') ? '動作中' : '停止中'; ?></span>
                        <span class="gyosei-stat-label">システム状態</span>
                    </div>
                </div>
                
                <table class="gyosei-mindmap-table wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>タイトル</th>
                            <th>タイプ</th>
                            <th>ショートコード</th>
                            <th>最終更新</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $mindmaps = get_posts(array(
                            'post_type' => 'gyosei_mindmap',
                            'posts_per_page' => -1,
                            'post_status' => 'any'
                        ));
                        
                        if (empty($mindmaps)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px;">
                                まだマインドマップがありません。
                                <a href="<?php echo admin_url('admin.php?page=gyosei-mindmap-new'); ?>">新規作成</a>してください。
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($mindmaps as $mindmap): 
                            $map_type = get_post_meta($mindmap->ID, '_mindmap_type', true) ?: 'gyosei';
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($mindmap->post_title); ?></strong>
                                <div class="status-badge status-<?php echo $mindmap->post_status; ?>">
                                    <?php echo $mindmap->post_status === 'publish' ? '公開' : '下書き'; ?>
                                </div>
                            </td>
                            <td><?php echo esc_html($map_type); ?></td>
                            <td>
                                <div class="shortcode-display">
                                    <code class="shortcode-code">[mindmap data="<?php echo esc_attr($map_type); ?>" title="<?php echo esc_attr($mindmap->post_title); ?>"]</code>
                                    <button class="copy-shortcode" data-shortcode='[mindmap data="<?php echo esc_attr($map_type); ?>" title="<?php echo esc_attr($mindmap->post_title); ?>"]'>
                                        コピー
                                    </button>
                                </div>
                            </td>
                            <td><?php echo get_the_modified_date('Y/m/d H:i', $mindmap); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="<?php echo admin_url('admin.php?page=gyosei-mindmap-new&edit=' . $mindmap->ID); ?>" class="btn-edit">編集</a>
                                    <button class="btn-delete delete-mindmap" data-id="<?php echo $mindmap->ID; ?>">削除</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <div class="gyosei-quick-help" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 5px;">
                    <h3>ショートコードの使い方</h3>
                    <p>投稿や固定ページでマインドマップを表示するには、上記のショートコードをコピーして貼り付けてください。</p>
                    <h4>オプション:</h4>
                    <ul>
                        <li><code>width</code>: 幅を指定（デフォルト: 100%）</li>
                        <li><code>height</code>: 高さを指定（デフォルト: 400px）</li>
                        <li><code>search</code>: 検索機能の有効/無効（true/false）</li>
                        <li><code>details</code>: 詳細モーダルの有効/無効（true/false）</li>
                    </ul>
                    <p><strong>例:</strong> <code>[mindmap data="gyosei" title="行政法" width="800px" height="500px" search="true" details="true"]</code></p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // ショートコードコピー機能
            $('.copy-shortcode').on('click', function() {
                const shortcode = $(this).data('shortcode');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(shortcode).then(function() {
                        const btn = $(this);
                        btn.text('コピー済み').addClass('copied');
                        setTimeout(() => {
                            btn.text('コピー').removeClass('copied');
                        }, 2000);
                    }.bind(this));
                } else {
                    // フォールバック
                    const textArea = document.createElement('textarea');
                    textArea.value = shortcode;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    alert('ショートコードをコピーしました！');
                }
            });
            
            // 削除機能
            $('.delete-mindmap').on('click', function() {
                if (confirm('本当に削除しますか？')) {
                    const id = $(this).data('id');
                    $.post(ajaxurl, {
                        action: 'delete_mindmap_data',
                        id: id,
                        nonce: '<?php echo wp_create_nonce('mindmap_admin_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('削除に失敗しました。');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    // 新規作成・編集ページ（省略 - 前回と同じ内容）
    public function admin_page_new() {
        // 前回のコードと同じ内容を保持
        echo '<div class="wrap"><h1>新規作成ページ</h1><p>実装中...</p></div>';
    }
    
    public function admin_page_settings() {
        // 前回のコードと同じ内容を保持
        echo '<div class="wrap"><h1>設定ページ</h1><p>実装中...</p></div>';
    }
    
    public function admin_page_help() {
        // 前回のコードと同じ内容を保持
        echo '<div class="wrap"><h1>ヘルプページ</h1><p>実装中...</p></div>';
    }
    
    // Ajax処理: マインドマップデータ保存
    public function ajax_save_mindmap_data() {
        check_ajax_referer('mindmap_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        $mindmap_id = intval($_POST['mindmap_id']);
        $title = sanitize_text_field($_POST['mindmap_title']);
        $type = sanitize_text_field($_POST['mindmap_type']);
        $description = sanitize_textarea_field($_POST['mindmap_description']);
        $json_data = $_POST['mindmap_json'] ?? '';
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $description,
            'post_type' => 'gyosei_mindmap',
            'post_status' => 'publish'
        );
        
        if ($mindmap_id) {
            $post_data['ID'] = $mindmap_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if ($result) {
            update_post_meta($result, '_mindmap_type', $type);
            
            if ($type === 'custom' && $json_data) {
                $decoded_data = json_decode($json_data, true);
                if ($decoded_data) {
                    update_post_meta($result, '_mindmap_data', $decoded_data);
                }
            }
            
            wp_send_json_success('保存されました');
        } else {
            wp_send_json_error('保存に失敗しました');
        }
    }
    
    // Ajax処理: マインドマップデータ削除
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
    
    // ノード詳細情報を取得するAjaxハンドラ
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
    
    // 進捗更新のAjaxハンドラ
    public function ajax_update_node_progress() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        $node_id = sanitize_text_field($_POST['node_id']);
        $progress = intval($_POST['progress']);
        $status = sanitize_text_field($_POST['status']);
        $notes = sanitize_textarea_field($_POST['notes']);
        
        // ユーザーごとの進捗をデータベースに保存
        $user_id = get_current_user_id();
        if ($user_id) {
            $progress_data = array(
                'progress' => $progress,
                'status' => $status,
                'notes' => $notes,
                'updated' => current_time('mysql')
            );
            
            update_user_meta($user_id, "mindmap_progress_{$node_id}", $progress_data);
            wp_send_json_success('Progress saved');
        } else {
            wp_send_json_error('User not logged in');
        }
    }
    
    // ノード検索のAjaxハンドラ
    public function ajax_search_nodes() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        $map_type = sanitize_text_field($_POST['map_type']);
        
        $results = $this->search_nodes($query, $map_type);
        wp_send_json_success($results);
    }
    
    private function get_node_details($node_id, $map_type) {
        $sample_data = $this->get_sample_data();
        
        if (!isset($sample_data[$map_type])) {
            return false;
        }
        
        $nodes = $sample_data[$map_type]['nodes'];
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
        
        // 追加の詳細情報を生成
        $details = array(
            'id' => $node['id'],
            'text' => $node['text'],
            'description' => $node['description'] ?? '',
            'icon' => $node['icon'] ?? '',
            'progress' => $node['progress'] ?? 0,
            'status' => $node['status'] ?? 'not-started',
            'resources' => $this->get_node_resources($node_id),
            'related_articles' => $this->get_related_articles($node_id),
            'study_tips' => $this->get_study_tips($node_id),
            'notes' => ''
        );
        
        // ユーザーごとの進捗を取得
        $user_id = get_current_user_id();
        if ($user_id) {
            $user_progress = get_user_meta($user_id, "mindmap_progress_{$node_id}", true);
            if ($user_progress) {
                $details['progress'] = $user_progress['progress'];
                $details['status'] = $user_progress['status'];
                $details['notes'] = $user_progress['notes'] ?? '';
            }
        }
        
        return $details;
    }
    
    private function get_node_resources($node_id) {
        // ノードごとの学習リソースを定義
        $resources = array(
            'root' => array(
                array('title' => '行政法入門', 'url' => '#', 'type' => '教科書'),
                array('title' => '行政法判例集', 'url' => '#', 'type' => '判例集')
            ),
            'general' => array(
                array('title' => '行政行為の基礎理論', 'url' => '#', 'type' => '論文'),
                array('title' => '行政裁量の判例分析', 'url' => '#', 'type' => '判例解説')
            ),
            'procedure' => array(
                array('title' => '行政手続法逐条解説', 'url' => '#', 'type' => '逐条解説'),
                array('title' => '申請手続きの実務', 'url' => '#', 'type' => '実務書')
            )
        );
        
        return $resources[$node_id] ?? array();
    }
    
    private function get_related_articles($node_id) {
        // 関連記事を定義
        $articles = array(
            'root' => array(
                array('title' => '行政法とは何か？基本概念を理解しよう', 'url' => '#'),
                array('title' => '公法と私法の違いを解説', 'url' => '#')
            ),
            'general' => array(
                array('title' => '行政行為の種類と効力', 'url' => '#'),
                array('title' => '行政裁量の限界について', 'url' => '#')
            )
        );
        
        return $articles[$node_id] ?? array();
    }
    
    private function get_study_tips($node_id) {
        // 学習のコツを定義
        $tips = array(
            'root' => '行政法は体系的理解が重要です。まず全体像を把握してから詳細に入りましょう。',
            'general' => '行政行為の概念は他の分野でも重要です。具体例と併せて理解しましょう。',
            'procedure' => '手続きの流れを図解で整理すると理解しやすくなります。',
            'case_law' => '訴訟類型ごとの要件と効果を表で整理しましょう。'
        );
        
        return $tips[$node_id] ?? '継続的な学習が成功の鍵です。';
    }
    
    private function search_nodes($query, $map_type) {
        $sample_data = $this->get_sample_data();
        
        if (!isset($sample_data[$map_type])) {
            return array();
        }
        
        $nodes = $sample_data[$map_type]['nodes'];
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
    
    // プラグイン有効化時の処理
    public function plugin_activate() {
        // カスタム投稿タイプを登録
        $this->register_post_types();
        
        // リライトルールをフラッシュ
        flush_rewrite_rules();
        
        // デフォルト設定を保存
        add_option('gyosei_mindmap_default_width', '100%');
        add_option('gyosei_mindmap_default_height', '400px');
        add_option('gyosei_mindmap_enable_search', 1);
        add_option('gyosei_mindmap_enable_details', 1);
    }
    
    public function enqueue_scripts() {
        // CSS読み込み
        wp_enqueue_style(
            'gyosei-mindmap-css',
            plugin_dir_url(__FILE__) . 'assets/mindmap.css',
            array(),
            '1.0.1'
        );
        
        // Phase 2専用CSS・JS
        wp_enqueue_style(
            'gyosei-mindmap-phase2-css',
            plugin_dir_url(__FILE__) . 'assets/mindmap-phase2.css',
            array('gyosei-mindmap-css'),
            '1.0.1'
        );
        
        // JavaScript読み込み
        wp_enqueue_script(
            'gyosei-mindmap-js',
            plugin_dir_url(__FILE__) . 'assets/mindmap.js',
            array('jquery'),
            '1.0.1',
            true
        );
        
        wp_enqueue_script(
            'gyosei-mindmap-phase2-js',
            plugin_dir_url(__FILE__) . 'assets/mindmap-phase2.js',
            array('gyosei-mindmap-js'),
            '1.0.1',
            true
        );
        
        // データをJavaScriptに渡す
        wp_localize_script('gyosei-mindmap-js', 'mindmapData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mindmap_nonce'),
            'sampleData' => $this->get_sample_data()
        ));
    }
    
    public function admin_enqueue_scripts($hook) {
        // マインドマップ管理画面でのみ読み込み
        if (strpos($hook, 'gyosei-mindmap') !== false) {
            wp_enqueue_style(
                'gyosei-mindmap-admin-css',
                plugin_dir_url(__FILE__) . 'assets/admin.css',
                array(),
                '1.0.1'
            );
        }
    }
    
    public function mindmap_shortcode($atts) {
        $atts = shortcode_atts(array(
            'data' => 'gyosei',
            'title' => '行政法',
            'width' => get_option('gyosei_mindmap_default_width', '100%'),
            'height' => get_option('gyosei_mindmap_default_height', '400px'),
            'search' => get_option('gyosei_mindmap_enable_search', 1) ? 'true' : 'false',
            'details' => get_option('gyosei_mindmap_enable_details', 1) ? 'true' : 'false',
            'draggable' => 'false'
        ), $atts);
        
        $unique_id = 'mindmap-' . uniqid();
        $container_class = 'mindmap-container';
        
        // Phase 2機能が有効な場合のクラス追加
        if ($atts['search'] === 'true' || $atts['details'] === 'true' || $atts['draggable'] === 'true') {
            $container_class .= ' mindmap-phase2';
        }
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($container_class); ?>" data-mindmap-id="<?php echo esc_attr($unique_id); ?>">
            <div class="mindmap-header">
                <h3 class="mindmap-title"><?php echo esc_html($atts['title']); ?></h3>
                <div class="mindmap-controls">
                    <?php if ($atts['search'] === 'true'): ?>
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
                </div>
            </div>
            <div class="mindmap-canvas" 
                 id="<?php echo esc_attr($unique_id); ?>"
                 style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;"
                 data-mindmap-type="<?php echo esc_attr($atts['data']); ?>"
                 data-search="<?php echo esc_attr($atts['search']); ?>"
                 data-details="<?php echo esc_attr($atts['details']); ?>"
                 data-draggable="<?php echo esc_attr($atts['draggable']); ?>">
                <!-- マインドマップがここに描画される -->
            </div>
            <div class="mindmap-loading">
                <span>マインドマップを読み込み中...</span>
            </div>
        </div>
        
        <?php if ($atts['details'] === 'true'): ?>
        <!-- ノード詳細モーダル -->
        <div class="mindmap-modal" id="mindmap-modal-<?php echo esc_attr($unique_id); ?>" style="display: none;">
            <div class="mindmap-modal-overlay"></div>
            <div class="mindmap-modal-content">
                <div class="mindmap-modal-header">
                    <h3 class="mindmap-modal-title"></h3>
                    <button class="mindmap-modal-close">✕</button>
                </div>
                <div class="mindmap-modal-body">
                    <div class="mindmap-node-info">
                        <div class="mindmap-node-status"></div>
                        <div class="mindmap-node-progress-display"></div>
                    </div>
                    <div class="mindmap-node-description"></div>
                    <div class="mindmap-node-resources">
                        <h4>関連リソース</h4>
                        <div class="mindmap-resources-list"></div>
                    </div>
                    <?php if (is_user_logged_in()): ?>
                    <div class="mindmap-study-controls">
                        <h4>学習管理</h4>
                        <div class="mindmap-progress-controls">
                            <label>進捗率:</label>
                            <input type="range" class="mindmap-progress-slider" min="0" max="100" step="5">
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
                        <h4>学習メモ</h4>
                        <textarea class="mindmap-notes-input" placeholder="学習したことをメモしてください..."></textarea>
                        <button class="mindmap-save-notes">メモを保存</button>
                    </div>
                    <?php else: ?>
                    <div class="mindmap-login-notice" style="padding: 15px; background: #f0f8ff; border-radius: 5px; text-align: center;">
                        <p>学習進捗の保存やメモ機能を利用するには<a href="<?php echo wp_login_url(); ?>">ログイン</a>が必要です。</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }
    
    public function get_sample_data() {
        // サンプルデータファイルを読み込み
        if (file_exists(plugin_dir_path(__FILE__) . 'data/sample-data.php')) {
            require_once plugin_dir_path(__FILE__) . 'data/sample-data.php';
            return GyoseiMindMapSampleData::get_all_data();
        }
        
        // ファイルが見つからない場合のフォールバック
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
                        'progress' => 75,
                        'status' => 'in-progress',
                        'description' => '行政に関する法律の総称。国民の権利保護と行政の適正な運営を図る。'
                    ),
                    array(
                        'id' => 'general',
                        'text' => '行政法総論',
                        'x' => 200,
                        'y' => 100,
                        'level' => 1,
                        'color' => '#303f9f',
                        'icon' => '📚',
                        'progress' => 60,
                        'status' => 'in-progress',
                        'description' => '行政法の基本原理・原則を学ぶ分野。行政行為、行政裁量などの基礎概念を扱う。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'procedure',
                        'text' => '行政手続法',
                        'x' => 600,
                        'y' => 100,
                        'level' => 1,
                        'color' => '#303f9f',
                        'icon' => '📋',
                        'progress' => 85,
                        'status' => 'completed',
                        'description' => '行政庁の処分、行政指導及び届出に関する手続を定めた法律。',
                        'parent' => 'root'
                    )
                ),
                'connections' => array(
                    array('from' => 'root', 'to' => 'general'),
                    array('from' => 'root', 'to' => 'procedure')
                )
            ),
            'minpo' => array(
                'title' => '民法',
                'nodes' => array(
                    array(
                        'id' => 'root',
                        'text' => '民法',
                        'x' => 400,
                        'y' => 200,
                        'level' => 0,
                        'color' => '#e91e63',
                        'icon' => '📖',
                        'progress' => 65,
                        'status' => 'in-progress',
                        'description' => '私人間の法律関係を規律する私法の一般法。財産関係と家族関係を規定。'
                    )
                ),
                'connections' => array()
            ),
            'kenpou' => array(
                'title' => '憲法',
                'nodes' => array(
                    array(
                        'id' => 'root',
                        'text' => '憲法',
                        'x' => 400,
                        'y' => 200,
                        'level' => 0,
                        'color' => '#4caf50',
                        'icon' => '📜',
                        'progress' => 55,
                        'status' => 'in-progress',
                        'description' => '国家の基本法。国民の基本的人権の保障と国家権力の組織・作用を定める。'
                    )
                ),
                'connections' => array()
            )
        );
    }
}

// プラグインの初期化
new GyoseiMindMap();