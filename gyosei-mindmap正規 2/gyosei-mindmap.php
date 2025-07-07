<?php
/**
 * Plugin Name: 行政書士の道 マインドマップ
 * Description: シンプルなマインドマップ機能で学習内容を整理
 * Version: 1.0.1
 * Author: 行政書士の道
 */

// プラグインのセキュリティ
if (!defined('ABSPATH')) {
    exit;
}

class GyoseishosiMindMap {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_shortcode('mindmap', array($this, 'mindmap_shortcode'));
        add_action('wp_ajax_save_mindmap', array($this, 'save_mindmap'));
        add_action('wp_ajax_nopriv_save_mindmap', array($this, 'save_mindmap'));
        add_action('wp_ajax_load_mindmap', array($this, 'load_mindmap'));
        add_action('wp_ajax_nopriv_load_mindmap', array($this, 'load_mindmap'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // プラグイン有効化時にテーブルを作成
        register_activation_hook(__FILE__, array($this, 'create_tables'));
    }
    
    public function init() {
        // カスタム投稿タイプ「マインドマップ」を作成
        register_post_type('mindmap', array(
            'labels' => array(
                'name' => 'マインドマップ',
                'singular_name' => 'マインドマップ',
                'add_new' => '新規作成',
                'add_new_item' => '新しいマインドマップを作成',
                'edit_item' => 'マインドマップを編集',
                'new_item' => '新しいマインドマップ',
                'view_item' => 'マインドマップを表示',
                'search_items' => 'マインドマップを検索',
                'not_found' => 'マインドマップが見つかりません',
            ),
            'public' => true,
            'supports' => array('title', 'editor'),
            'menu_icon' => 'dashicons-networking',
            'show_in_menu' => false, // 独自の管理画面を使用
        ));
        
        // カスタムタクソノミー「科目」を作成
        register_taxonomy('subject', 'mindmap', array(
            'labels' => array(
                'name' => '科目',
                'singular_name' => '科目',
                'add_new_item' => '新しい科目を追加',
            ),
            'hierarchical' => true,
            'public' => true,
            'show_admin_column' => true,
        ));
    }
    
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('mindmap-js', plugin_dir_url(__FILE__) . 'mindmap.js', array('jquery'), '1.0.1', true);
        wp_enqueue_style('mindmap-css', plugin_dir_url(__FILE__) . 'mindmap.css', array(), '1.0.1');
        
        // Ajax用のデータを渡す
        wp_localize_script('mindmap-js', 'mindmap_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mindmap_nonce')
        ));
    }
    
    public function admin_enqueue_scripts() {
        wp_enqueue_style('mindmap-admin-css', plugin_dir_url(__FILE__) . 'admin-style.css', array(), '1.0.0');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'マインドマップ管理',
            'マインドマップ',
            'manage_options',
            'mindmap-admin',
            array($this, 'admin_page'),
            'dashicons-networking',
            30
        );
    }
    
    public function admin_page() {
        ?>
        <div class="wrap mindmap-admin">
            <h1>🧠 マインドマップ管理</h1>
            
            <div class="mindmap-admin-content">
                <!-- 使い方ガイド -->
                <div class="mindmap-guide">
                    <h2>📖 使い方ガイド</h2>
                    <div class="guide-section">
                        <h3>1. ショートコードの貼り付け</h3>
                        <p>投稿や固定ページに以下のショートコードを貼り付けてください：</p>
                        <div class="code-box">
                            <code>[mindmap id="学習計画" width="100%" height="600px"]</code>
                            <button class="copy-btn" onclick="copyToClipboard('[mindmap id=&quot;学習計画&quot; width=&quot;100%&quot; height=&quot;600px&quot;]')">コピー</button>
                        </div>
                        
                        <h4>オプション説明：</h4>
                        <ul>
                            <li><strong>id</strong>: マインドマップの識別名（日本語OK）</li>
                            <li><strong>width</strong>: 横幅（100%, 800px など）</li>
                            <li><strong>height</strong>: 高さ（600px, 80vh など）</li>
                        </ul>
                    </div>
                    
                    <div class="guide-section">
                        <h3>2. 基本操作</h3>
                        <div class="operation-grid">
                            <div class="operation-item">
                                <h4>🎯 ノード追加</h4>
                                <p>「ノード追加」ボタンまたは空白部分をダブルクリック</p>
                            </div>
                            <div class="operation-item">
                                <h4>✏️ ノード編集</h4>
                                <p>ノードをダブルクリックで編集フォーム表示</p>
                            </div>
                            <div class="operation-item">
                                <h4>🖱️ ノード移動</h4>
                                <p>ノードをドラッグして位置変更</p>
                            </div>
                            <div class="operation-item">
                                <h4>✅ 理解済み</h4>
                                <p>編集時に「理解」ボタンでノードを薄くします</p>
                            </div>
                            <div class="operation-item">
                                <h4>💾 保存</h4>
                                <p>「保存」ボタンで変更内容をデータベースに保存</p>
                            </div>
                            <div class="operation-item">
                                <h4>🎨 テーマ変更</h4>
                                <p>ドロップダウンでデザインテーマを切り替え</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="guide-section">
                        <h3>3. 学習活用例</h3>
                        <div class="example-grid">
                            <div class="example-item">
                                <h4>📚 科目別学習計画</h4>
                                <p>各科目をルートノードにして、章・節・ポイントを整理</p>
                            </div>
                            <div class="example-item">
                                <h4>🔗 知識の関連付け</h4>
                                <p>異なる分野の関連性を視覚的に表現</p>
                            </div>
                            <div class="example-item">
                                <h4>📝 要点整理</h4>
                                <p>試験前の重要ポイントまとめに活用</p>
                            </div>
                            <div class="example-item">
                                <h4>✅ 進捗管理</h4>
                                <p>理解済みノードで学習進捗を視覚化</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- ショートコード生成器 -->
                <div class="shortcode-generator">
                    <h2>⚡ ショートコード生成器</h2>
                    <div class="generator-form">
                        <label for="mindmap-id">マインドマップID:</label>
                        <input type="text" id="mindmap-id" value="学習計画" placeholder="例: 民法基礎">
                        
                        <label for="mindmap-width">横幅:</label>
                        <select id="mindmap-width">
                            <option value="100%">100%（フル幅）</option>
                            <option value="800px">800px</option>
                            <option value="1000px">1000px</option>
                            <option value="80vw">80vw（画面幅の80%）</option>
                        </select>
                        
                        <label for="mindmap-height">高さ:</label>
                        <select id="mindmap-height">
                            <option value="600px">600px（推奨）</option>
                            <option value="500px">500px</option>
                            <option value="800px">800px</option>
                            <option value="80vh">80vh（画面高さの80%）</option>
                        </select>
                        
                        <button class="generate-btn" onclick="generateShortcode()">ショートコード生成</button>
                    </div>
                    
                    <div class="generated-shortcode">
                        <label>生成されたショートコード:</label>
                        <div class="code-box">
                            <code id="generated-code">[mindmap id="学習計画" width="100%" height="600px"]</code>
                            <button class="copy-btn" onclick="copyGeneratedCode()">コピー</button>
                        </div>
                    </div>
                </div>
                
                <!-- 保存されたマインドマップ一覧 -->
                <div class="saved-mindmaps">
                    <h2>📁 保存済みマインドマップ</h2>
                    <?php $this->display_saved_mindmaps(); ?>
                </div>
            </div>
        </div>
        
        <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('ショートコードをコピーしました！');
            });
        }
        
        function generateShortcode() {
            const id = document.getElementById('mindmap-id').value || '学習計画';
            const width = document.getElementById('mindmap-width').value;
            const height = document.getElementById('mindmap-height').value;
            
            const shortcode = `[mindmap id="${id}" width="${width}" height="${height}"]`;
            document.getElementById('generated-code').textContent = shortcode;
        }
        
        function copyGeneratedCode() {
            const code = document.getElementById('generated-code').textContent;
            copyToClipboard(code);
        }
        
        // 入力値が変更されたら自動でショートコード更新
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = ['mindmap-id', 'mindmap-width', 'mindmap-height'];
            inputs.forEach(id => {
                document.getElementById(id).addEventListener('change', generateShortcode);
            });
        });
        </script>
        <?php
    }
    
    public function display_saved_mindmaps() {
        global $wpdb;
        
        $results = $wpdb->get_results(
            "SELECT post_id, meta_value as mindmap_id 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = 'mindmap_id' 
             ORDER BY post_id DESC 
             LIMIT 20"
        );
        
        if ($results) {
            echo '<div class="mindmap-list">';
            foreach ($results as $result) {
                $post = get_post($result->post_id);
                if ($post) {
                    $created_date = get_the_date('Y-m-d H:i', $result->post_id);
                    echo '<div class="mindmap-item">';
                    echo '<h4>' . esc_html($result->mindmap_id) . '</h4>';
                    echo '<p>作成日: ' . esc_html($created_date) . '</p>';
                    echo '<code>[mindmap id="' . esc_attr($result->mindmap_id) . '"]</code>';
                    echo '</div>';
                }
            }
            echo '</div>';
        } else {
            echo '<p>保存されたマインドマップはまだありません。</p>';
        }
    }
    
    public function mindmap_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 'default',
            'width' => '100%',
            'height' => '600px'
        ), $atts);
        
        $mindmap_id = sanitize_text_field($atts['id']);
        $unique_id = 'mindmap_' . md5($mindmap_id . time());
        
        // 保存されたデータを読み込み
        $saved_data = $this->get_mindmap_data($mindmap_id);
        
        ob_start();
        ?>
        <div class="mindmap-container" id="<?php echo esc_attr($unique_id); ?>" style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
            <div class="mindmap-toolbar">
                <button id="add-node-btn" class="mindmap-btn">🎯 ノード追加</button>
                <button id="connect-mode-btn" class="mindmap-btn">🔗 接続モード</button>
                <button id="save-mindmap-btn" class="mindmap-btn">💾 保存</button>
                <button id="fullscreen-btn" class="mindmap-btn">🔍 フルスクリーン</button>
                <select id="theme-selector" class="mindmap-select">
                    <option value="default">🎨 デフォルト</option>
                    <option value="dark">🌙 ダーク</option>
                    <option value="study">📚 勉強モード</option>
                </select>
                <span class="toolbar-help">💡 Ctrlキーで接続モード | 空白部分をダブルクリックでノード追加</span>
            </div>
            <div class="mindmap-canvas" data-mindmap-id="<?php echo esc_attr($mindmap_id); ?>">
                <svg class="mindmap-svg" width="100%" height="100%"></svg>
            </div>
            <div class="mindmap-status">
                <span id="node-count">ノード数: 0</span>
                <span id="connection-count">接続数: 0</span>
                <span id="save-status" class="save-status">未保存</span>
                <span class="help-text">ヒント: ノードをダブルクリックで編集 | 接続線をクリックで選択</span>
            </div>
        </div>
        
        <?php if ($saved_data): ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const instance = window.getMindMapInstance('<?php echo esc_js($mindmap_id); ?>');
                if (instance) {
                    instance.loadData(<?php echo $saved_data; ?>);
                }
            }, 500);
        });
        </script>
        <?php endif; ?>
        
        <?php
        return ob_get_clean();
    }
    
    public function get_mindmap_data($mindmap_id) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT pm.meta_value 
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id
             WHERE pm.meta_key = 'mindmap_data' 
             AND pm2.meta_key = 'mindmap_id' 
             AND pm2.meta_value = %s
             ORDER BY pm.post_id DESC 
             LIMIT 1",
            $mindmap_id
        ));
        
        return $result ? $result : null;
    }
    
    public function save_mindmap() {
        // セキュリティチェック
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error(array('message' => 'セキュリティチェックに失敗しました'));
            return;
        }
        
        $mindmap_id = sanitize_text_field($_POST['mindmap_id']);
        $mindmap_data = sanitize_textarea_field($_POST['mindmap_data']);
        
        if (empty($mindmap_id) || empty($mindmap_data)) {
            wp_send_json_error(array('message' => 'データが不正です'));
            return;
        }
        
        // 既存のデータを検索
        $existing_post = get_posts(array(
            'post_type' => 'mindmap',
            'meta_query' => array(
                array(
                    'key' => 'mindmap_id',
                    'value' => $mindmap_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if ($existing_post) {
            // 既存データを更新
            $post_id = $existing_post[0]->ID;
            update_post_meta($post_id, 'mindmap_data', $mindmap_data);
            wp_update_post(array(
                'ID' => $post_id,
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', 1)
            ));
        } else {
            // 新規作成
            $post_id = wp_insert_post(array(
                'post_type' => 'mindmap',
                'post_title' => 'マインドマップ: ' . $mindmap_id,
                'post_status' => 'publish',
                'meta_input' => array(
                    'mindmap_data' => $mindmap_data,
                    'mindmap_id' => $mindmap_id
                )
            ));
        }
        
        if ($post_id) {
            wp_send_json_success(array(
                'message' => '保存しました',
                'post_id' => $post_id,
                'timestamp' => current_time('mysql')
            ));
        } else {
            wp_send_json_error(array('message' => '保存に失敗しました'));
        }
    }
    
    public function load_mindmap() {
        if (!wp_verify_nonce($_POST['nonce'], 'mindmap_nonce')) {
            wp_send_json_error(array('message' => 'セキュリティチェックに失敗しました'));
            return;
        }
        
        $mindmap_id = sanitize_text_field($_POST['mindmap_id']);
        $data = $this->get_mindmap_data($mindmap_id);
        
        if ($data) {
            wp_send_json_success(array('data' => $data));
        } else {
            wp_send_json_error(array('message' => 'データが見つかりません'));
        }
    }
    
    public function create_tables() {
        // プラグイン有効化時の処理（必要に応じて）
        // 現在はWordPressの標準テーブルを使用するため特別な処理不要
    }
}

// プラグインを初期化
new GyoseishosiMindMap();