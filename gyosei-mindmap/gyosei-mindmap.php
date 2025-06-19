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
                    <a href="<?php echo admin_url('admin.php?page=gyosei-mindmap-new'); ?>" class="button button-primary">
                        新規マインドマップ作成
                    </a>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
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
                            </td>
                            <td><?php echo esc_html($map_type); ?></td>
                            <td>
                                <code>[mindmap data="<?php echo esc_attr($map_type); ?>" title="<?php echo esc_attr($mindmap->post_title); ?>"]</code>
                                <button class="button copy-shortcode" data-shortcode='[mindmap data="<?php echo esc_attr($map_type); ?>" title="<?php echo esc_attr($mindmap->post_title); ?>"]'>
                                    コピー
                                </button>
                            </td>
                            <td><?php echo get_the_modified_date('Y/m/d H:i', $mindmap); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=gyosei-mindmap-new&edit=' . $mindmap->ID); ?>" class="button">編集</a>
                                <button class="button delete-mindmap" data-id="<?php echo $mindmap->ID; ?>">削除</button>
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
        
        <style>
        .gyosei-admin-container {
            margin-top: 20px;
        }
        .gyosei-admin-header {
            margin-bottom: 20px;
        }
        .copy-shortcode {
            margin-left: 10px;
            font-size: 11px;
        }
        .gyosei-quick-help h4 {
            margin-top: 15px;
            margin-bottom: 5px;
        }
        .gyosei-quick-help ul {
            margin-top: 5px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // ショートコードコピー機能
            $('.copy-shortcode').on('click', function() {
                const shortcode = $(this).data('shortcode');
                navigator.clipboard.writeText(shortcode).then(function() {
                    alert('ショートコードをコピーしました！');
                });
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
    
    // 新規作成・編集ページ
    public function admin_page_new() {
        $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
        $mindmap = null;
        $mindmap_data = array();
        
        if ($edit_id) {
            $mindmap = get_post($edit_id);
            if ($mindmap) {
                $mindmap_data = get_post_meta($edit_id, '_mindmap_data', true);
                if (!$mindmap_data) {
                    $mindmap_data = array();
                }
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo $edit_id ? 'マインドマップ編集' : '新規マインドマップ作成'; ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" id="mindmap-form">
                <input type="hidden" name="action" value="save_mindmap_data">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mindmap_admin_nonce'); ?>">
                <input type="hidden" name="mindmap_id" value="<?php echo $edit_id; ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="mindmap_title">タイトル</label></th>
                        <td>
                            <input type="text" id="mindmap_title" name="mindmap_title" 
                                   value="<?php echo $mindmap ? esc_attr($mindmap->post_title) : ''; ?>" 
                                   class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mindmap_type">マインドマップタイプ</label></th>
                        <td>
                            <select id="mindmap_type" name="mindmap_type">
                                <option value="gyosei" <?php selected(get_post_meta($edit_id, '_mindmap_type', true), 'gyosei'); ?>>行政法</option>
                                <option value="minpo" <?php selected(get_post_meta($edit_id, '_mindmap_type', true), 'minpo'); ?>>民法</option>
                                <option value="kenpou" <?php selected(get_post_meta($edit_id, '_mindmap_type', true), 'kenpou'); ?>>憲法</option>
                                <option value="custom" <?php selected(get_post_meta($edit_id, '_mindmap_type', true), 'custom'); ?>>カスタム</option>
                            </select>
                            <p class="description">既定のマインドマップタイプを選択するか、カスタムで独自のマップを作成します。</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="mindmap_description">説明</label></th>
                        <td>
                            <textarea id="mindmap_description" name="mindmap_description" rows="3" class="large-text"><?php 
                                echo $mindmap ? esc_textarea($mindmap->post_content) : ''; 
                            ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <div id="custom-mindmap-editor" style="display: none;">
                    <h3>カスタムマインドマップエディタ</h3>
                    <p>JSON形式でマインドマップデータを入力してください。</p>
                    <textarea id="mindmap_json" name="mindmap_json" rows="20" class="large-text" style="font-family: monospace;"><?php 
                        echo esc_textarea(json_encode($mindmap_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); 
                    ?></textarea>
                    <p class="description">
                        JSONの形式例は<a href="<?php echo admin_url('admin.php?page=gyosei-mindmap-help'); ?>">使い方ページ</a>をご確認ください。
                    </p>
                </div>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php echo $edit_id ? '更新' : '作成'; ?>">
                    <a href="<?php echo admin_url('admin.php?page=gyosei-mindmap'); ?>" class="button">キャンセル</a>
                </p>
            </form>
            
            <?php if ($edit_id): ?>
            <div class="mindmap-preview" style="margin-top: 30px;">
                <h3>プレビュー</h3>
                <div style="border: 1px solid #ddd; padding: 20px; background: white;">
                    <?php echo do_shortcode('[mindmap data="' . get_post_meta($edit_id, '_mindmap_type', true) . '" title="' . esc_attr($mindmap->post_title) . '"]'); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // マインドマップタイプ変更時の処理
            $('#mindmap_type').on('change', function() {
                if ($(this).val() === 'custom') {
                    $('#custom-mindmap-editor').show();
                } else {
                    $('#custom-mindmap-editor').hide();
                }
            }).trigger('change');
            
            // フォーム送信処理
            $('#mindmap-form').on('submit', function(e) {
                e.preventDefault();
                
                const formData = $(this).serialize();
                
                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        window.location.href = '<?php echo admin_url('admin.php?page=gyosei-mindmap&message=saved'); ?>';
                    } else {
                        alert('保存に失敗しました: ' + response.data);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    // 設定ページ
    public function admin_page_settings() {
        ?>
        <div class="wrap">
            <h1>マインドマップ設定</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('gyosei_mindmap_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="default_width">デフォルト幅</label></th>
                        <td>
                            <input type="text" id="default_width" name="gyosei_mindmap_default_width" 
                                   value="<?php echo esc_attr(get_option('gyosei_mindmap_default_width', '100%')); ?>" 
                                   class="regular-text">
                            <p class="description">マインドマップのデフォルト幅（例: 100%, 800px）</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="default_height">デフォルト高さ</label></th>
                        <td>
                            <input type="text" id="default_height" name="gyosei_mindmap_default_height" 
                                   value="<?php echo esc_attr(get_option('gyosei_mindmap_default_height', '400px')); ?>" 
                                   class="regular-text">
                            <p class="description">マインドマップのデフォルト高さ（例: 400px, 50vh）</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enable_search">検索機能</label></th>
                        <td>
                            <input type="checkbox" id="enable_search" name="gyosei_mindmap_enable_search" 
                                   value="1" <?php checked(get_option('gyosei_mindmap_enable_search', 1)); ?>>
                            <label for="enable_search">デフォルトで検索機能を有効にする</label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="enable_details">詳細モーダル</label></th>
                        <td>
                            <input type="checkbox" id="enable_details" name="gyosei_mindmap_enable_details" 
                                   value="1" <?php checked(get_option('gyosei_mindmap_enable_details', 1)); ?>>
                            <label for="enable_details">デフォルトで詳細モーダルを有効にする</label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    // 使い方ページ
    public function admin_page_help() {
        ?>
        <div class="wrap">
            <h1>マインドマップ 使い方</h1>
            
            <div class="gyosei-help-content">
                <h2>基本的な使い方</h2>
                <ol>
                    <li><strong>新規作成:</strong> 「新規作成」メニューからマインドマップを作成します。</li>
                    <li><strong>ショートコード:</strong> 生成されたショートコードを投稿や固定ページに貼り付けます。</li>
                    <li><strong>表示確認:</strong> フロントエンドでマインドマップが表示されることを確認します。</li>
                </ol>
                
                <h2>ショートコードオプション</h2>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th>オプション</th>
                            <th>説明</th>
                            <th>デフォルト値</th>
                            <th>例</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>data</code></td>
                            <td>マインドマップのタイプ</td>
                            <td>gyosei</td>
                            <td>gyosei, minpo, kenpou</td>
                        </tr>
                        <tr>
                            <td><code>title</code></td>
                            <td>マインドマップのタイトル</td>
                            <td>行政法</td>
                            <td>任意のタイトル</td>
                        </tr>
                        <tr>
                            <td><code>width</code></td>
                            <td>マインドマップの幅</td>
                            <td>100%</td>
                            <td>800px, 90%</td>
                        </tr>
                        <tr>
                            <td><code>height</code></td>
                            <td>マインドマップの高さ</td>
                            <td>400px</td>
                            <td>500px, 60vh</td>
                        </tr>
                        <tr>
                            <td><code>search</code></td>
                            <td>検索機能の有効/無効</td>
                            <td>true</td>
                            <td>true, false</td>
                        </tr>
                        <tr>
                            <td><code>details</code></td>
                            <td>詳細モーダルの有効/無効</td>
                            <td>true</td>
                            <td>true, false</td>
                        </tr>
                    </tbody>
                </table>
                
                <h2>カスタムマインドマップのJSON形式</h2>
                <p>カスタムマインドマップを作成する場合は、以下の形式でJSONデータを入力してください：</p>
                <pre style="background: #f9f9f9; padding: 15px; border-radius: 5px; overflow-x: auto;">
{
  "title": "マインドマップタイトル",
  "nodes": [
    {
      "id": "root",
      "text": "中心ノード",
      "x": 400,
      "y": 200,
      "level": 0,
      "color": "#3f51b5",
      "icon": "⚖️",
      "progress": 0,
      "status": "not-started",
      "description": "ノードの説明文"
    }
  ],
  "connections": [
    {
      "from": "parent_node_id",
      "to": "child_node_id"
    }
  ]
}
                </pre>
                
                <h2>よくある質問</h2>
                <h3>Q: マインドマップが表示されません</h3>
                <p>A: 以下を確認してください：</p>
                <ul>
                    <li>ショートコードが正しく入力されているか</li>
                    <li>JavaScriptエラーがブラウザのコンソールに表示されていないか</li>
                    <li>テーマとの競合がないか</li>
                </ul>
                
                <h3>Q: カスタムマインドマップでエラーが出ます</h3>
                <p>A: JSON形式が正しいか確認してください。オンラインのJSON Validatorなどで構文チェックができます。</p>
                
                <h3>Q: ノードの位置を調整したい</h3>
                <p>A: JSONデータの各ノードの「x」「y」の値を変更してください。数値が大きいほど右下に移動します。</p>
            </div>
        </div>
        
        <style>
        .gyosei-help-content {
            max-width: 800px;
        }
        .gyosei-help-content h2 {
            margin-top: 30px;
            border-bottom: 2px solid #3f51b5;
            padding-bottom: 5px;
        }
        .gyosei-help-content h3 {
            margin-top: 20px;
            color: #333;
        }
        .gyosei-help-content pre {
            font-size: 12px;
            line-height: 1.4;
        }
        </style>
        <?php
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
            '1.0.0'
        );
        
        // JavaScript読み込み
        wp_enqueue_script(
            'gyosei-mindmap-js',
            plugin_dir_url(__FILE__) . 'assets/mindmap.js',
            array('jquery'),
            '1.0.0',
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
                '1.0.0'
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
            'details' => get_option('gyosei_mindmap_enable_details', 1) ? 'true' : 'false'
        ), $atts);
        
        $unique_id = 'mindmap-' . uniqid();
        
        ob_start();
        ?>
        <div class="mindmap-container" data-mindmap-id="<?php echo esc_attr($unique_id); ?>">
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
                 data-details="<?php echo esc_attr($atts['details']); ?>">
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
    
    private function get_sample_data() {
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
                    ),
                    array(
                        'id' => 'case_law',
                        'text' => '行政事件訴訟法',
                        'x' => 200,
                        'y' => 300,
                        'level' => 1,
                        'color' => '#303f9f',
                        'icon' => '🏛️',
                        'progress' => 40,
                        'status' => 'in-progress',
                        'description' => '行政事件訴訟に関する手続を定めた法律。取消訴訟、無効等確認訴訟などを規定。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'compensation',
                        'text' => '国家賠償法',
                        'x' => 600,
                        'y' => 300,
                        'level' => 1,
                        'color' => '#303f9f',
                        'icon' => '💰',
                        'progress' => 20,
                        'status' => 'not-started',
                        'description' => '国又は公共団体の損害賠償責任について定めた法律。',
                        'parent' => 'root'
                    ),
                    // サブノード
                    array(
                        'id' => 'admin_act',
                        'text' => '行政行為',
                        'x' => 100,
                        'y' => 50,
                        'level' => 2,
                        'color' => '#1a237e',
                        'icon' => '⚡',
                        'progress' => 70,
                        'status' => 'in-progress',
                        'description' => '行政庁の処分その他公権力の行使に当たる行為。許可、認可、特許など。',
                        'parent' => 'general'
                    ),
                    array(
                        'id' => 'discretion',
                        'text' => '行政裁量',
                        'x' => 100,
                        'y' => 150,
                        'level' => 2,
                        'color' => '#1a237e',
                        'icon' => '⚖️',
                        'progress' => 50,
                        'status' => 'in-progress',
                        'description' => '行政庁に認められた判断の余地。羈束裁量と自由裁量に分類される。',
                        'parent' => 'general'
                    ),
                    array(
                        'id' => 'notification',
                        'text' => '申請・届出',
                        'x' => 700,
                        'y' => 50,
                        'level' => 2,
                        'color' => '#1a237e',
                        'icon' => '📝',
                        'progress' => 90,
                        'status' => 'completed',
                        'description' => '法令に基づく申請・届出の手続き。標準処理期間の設定などを規定。',
                        'parent' => 'procedure'
                    ),
                    array(
                        'id' => 'hearing',
                        'text' => '聴聞・弁明',
                        'x' => 700,
                        'y' => 150,
                        'level' => 2,
                        'color' => '#1a237e',
                        'icon' => '👂',
                        'progress' => 80,
                        'status' => 'completed',
                        'description' => '不利益処分を行う際の事前手続き。聴聞手続きと弁明機会の付与。',
                        'parent' => 'procedure'
                    )
                ),
                'connections' => array(
                    array('from' => 'root', 'to' => 'general'),
                    array('from' => 'root', 'to' => 'procedure'),
                    array('from' => 'root', 'to' => 'case_law'),
                    array('from' => 'root', 'to' => 'compensation'),
                    array('from' => 'general', 'to' => 'admin_act'),
                    array('from' => 'general', 'to' => 'discretion'),
                    array('from' => 'procedure', 'to' => 'notification'),
                    array('from' => 'procedure', 'to' => 'hearing')
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
                    ),
                    array(
                        'id' => 'general_rule',
                        'text' => '総則',
                        'x' => 200,
                        'y' => 100,
                        'level' => 1,
                        'color' => '#c2185b',
                        'icon' => '🏛️',
                        'progress' => 80,
                        'status' => 'completed',
                        'description' => '民法の基本原則、権利能力、意思表示、代理、時効などの基礎概念。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'property',
                        'text' => '物権',
                        'x' => 600,
                        'y' => 100,
                        'level' => 1,
                        'color' => '#c2185b',
                        'icon' => '🏠',
                        'progress' => 70,
                        'status' => 'in-progress',
                        'description' => '物に対する支配権。所有権、用益物権、担保物権に分類される。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'obligation',
                        'text' => '債権',
                        'x' => 200,
                        'y' => 300,
                        'level' => 1,
                        'color' => '#c2185b',
                        'icon' => '💼',
                        'progress' => 45,
                        'status' => 'in-progress',
                        'description' => '特定人に対して一定の行為を請求する権利。契約、事務管理、不当利得、不法行為。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'family',
                        'text' => '親族・相続',
                        'x' => 600,
                        'y' => 300,
                        'level' => 1,
                        'color' => '#c2185b',
                        'icon' => '👨‍👩‍👧‍👦',
                        'progress' => 30,
                        'status' => 'not-started',
                        'description' => '婚姻、親子関係、相続に関する法律関係を規定。',
                        'parent' => 'root'
                    )
                ),
                'connections' => array(
                    array('from' => 'root', 'to' => 'general_rule'),
                    array('from' => 'root', 'to' => 'property'),
                    array('from' => 'root', 'to' => 'obligation'),
                    array('from' => 'root', 'to' => 'family')
                )
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
                    ),
                    array(
                        'id' => 'human_rights',
                        'text' => '基本的人権',
                        'x' => 200,
                        'y' => 100,
                        'level' => 1,
                        'color' => '#388e3c',
                        'icon' => '👥',
                        'progress' => 70,
                        'status' => 'in-progress',
                        'description' => '個人の尊厳に基づく基本的権利。自由権、社会権、参政権、受益権に分類。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'state_power',
                        'text' => '統治機構',
                        'x' => 600,
                        'y' => 100,
                        'level' => 1,
                        'color' => '#388e3c',
                        'icon' => '🏛️',
                        'progress' => 40,
                        'status' => 'in-progress',
                        'description' => '国家権力の組織と作用。立法、行政、司法の三権分立制度。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'peace',
                        'text' => '平和主義',
                        'x' => 300,
                        'y' => 300,
                        'level' => 1,
                        'color' => '#388e3c',
                        'icon' => '🕊️',
                        'progress' => 60,
                        'status' => 'in-progress',
                        'description' => '戦争放棄と戦力不保持を定めた憲法第9条の理念。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'rule_of_law',
                        'text' => '法の支配',
                        'x' => 500,
                        'y' => 300,
                        'level' => 1,
                        'color' => '#388e3c',
                        'icon' => '⚖️',
                        'progress' => 50,
                        'status' => 'in-progress',
                        'description' => '権力の恣意的行使を法によって制限する原理。立憲主義の基礎。',
                        'parent' => 'root'
                    )
                ),
                'connections' => array(
                    array('from' => 'root', 'to' => 'human_rights'),
                    array('from' => 'root', 'to' => 'state_power'),
                    array('from' => 'root', 'to' => 'peace'),
                    array('from' => 'root', 'to' => 'rule_of_law')
                )
            )
        );
    }
}

// プラグインの初期化
new GyoseiMindMap();