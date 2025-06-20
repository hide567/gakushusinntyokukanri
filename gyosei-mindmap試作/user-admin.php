<?php
/**
 * 行政書士の道 - マインドマップ Phase 3-A
 * ユーザー管理強化機能（カスタムマップ、編集機能）
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// Phase2クラスが存在することを確認
if (!class_exists('GyoseiMindMapPhase2')) {
    return;
}

class GyoseiMindMapPhase3A extends GyoseiMindMapPhase2 {
    
    protected static $phase3a_instance = null;
    
    public static function get_instance() {
        if (null === self::$phase3a_instance) {
            self::$phase3a_instance = new self();
        }
        return self::$phase3a_instance;
    }
    
    public function __construct() {
        // 親クラスの初期化はスキップ（既に初期化済み）
        
        // Phase 3-A 専用のAjax処理を追加
        add_action('wp_ajax_create_custom_map', array($this, 'ajax_create_custom_map'));
        add_action('wp_ajax_save_custom_map', array($this, 'ajax_save_custom_map'));
        add_action('wp_ajax_get_custom_map_data', array($this, 'ajax_get_custom_map_data'));
        add_action('wp_ajax_load_user_maps', array($this, 'ajax_load_user_maps'));
        add_action('wp_ajax_clone_map', array($this, 'ajax_clone_map'));
        add_action('wp_ajax_export_map', array($this, 'ajax_export_map'));
        add_action('wp_ajax_import_map', array($this, 'ajax_import_map'));
        
        // カスタムマップテーブル作成
        add_action('init', array($this, 'create_custom_maps_table'));
    }
    
    // カスタムマップ用DBテーブル作成
    public function create_custom_maps_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'gyosei_custom_maps';
        
        // テーブルが既に存在するかチェック
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                map_title varchar(255) NOT NULL,
                map_description text,
                map_data longtext NOT NULL,
                map_settings text,
                is_public tinyint(1) DEFAULT 0,
                is_template tinyint(1) DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY is_public (is_public),
                KEY is_template (is_template)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    // ショートコード機能を拡張（オーバーライド）
    public function mindmap_shortcode($atts) {
        $atts = shortcode_atts(array(
            'data' => 'gyosei',
            'title' => '行政法',
            'width' => '100%',
            'height' => '400px',
            'search' => 'true',
            'details' => 'true',
            'draggable' => 'false',
            'editable' => 'false',
            'custom_id' => '',
            'community' => 'false'
        ), $atts);
        
        // カスタムマップの場合、データを取得
        if ($atts['custom_id']) {
            $custom_data = $this->get_custom_map_data($atts['custom_id']);
            if ($custom_data) {
                $atts['data'] = 'custom';
                $atts['title'] = $custom_data['title'];
            }
        }
        
        // 親クラスのショートコードを呼び出し
        $base_output = parent::mindmap_shortcode($atts);
        
        // Phase3A固有のモーダルを追加
        if (is_user_logged_in()) {
            $unique_id = substr(md5(uniqid()), 0, 8);
            $phase3a_modals = $this->render_phase3a_modals($unique_id, $atts);
            return $base_output . $phase3a_modals;
        }
        
        return $base_output;
    }
    
    // Phase3A専用モーダルのレンダリング
    private function render_phase3a_modals($unique_id, $atts) {
        ob_start();
        ?>
        
        <!-- カスタムマップ作成モーダル -->
        <div class="mindmap-creator-modal" id="mindmap-creator-<?php echo esc_attr($unique_id); ?>" style="display: none;">
            <div class="mindmap-modal-overlay"></div>
            <div class="mindmap-modal-content mindmap-creator-content">
                <div class="mindmap-modal-header">
                    <h3 class="mindmap-modal-title">新しいマインドマップを作成</h3>
                    <button class="mindmap-modal-close">✕</button>
                </div>
                <div class="mindmap-modal-body">
                    <form class="mindmap-creator-form">
                        <div class="form-section">
                            <h4>基本情報</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="map-title-<?php echo esc_attr($unique_id); ?>">マップタイトル</label>
                                    <input type="text" id="map-title-<?php echo esc_attr($unique_id); ?>" name="map_title" required>
                                </div>
                                <div class="form-group">
                                    <label for="map-description-<?php echo esc_attr($unique_id); ?>">説明</label>
                                    <textarea id="map-description-<?php echo esc_attr($unique_id); ?>" name="map_description" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4>テンプレート選択</h4>
                            <div class="template-selector">
                                <div class="template-option selected" data-template="blank">
                                    <div class="template-preview">🗒️</div>
                                    <div class="template-info">
                                        <h5>空白のマップ</h5>
                                        <p>ゼロから作成</p>
                                    </div>
                                </div>
                                <div class="template-option" data-template="gyosei">
                                    <div class="template-preview">⚖️</div>
                                    <div class="template-info">
                                        <h5>行政法テンプレート</h5>
                                        <p>行政法の基本構造</p>
                                    </div>
                                </div>
                                <div class="template-option" data-template="minpo">
                                    <div class="template-preview">📖</div>
                                    <div class="template-info">
                                        <h5>民法テンプレート</h5>
                                        <p>民法の基本構造</p>
                                    </div>
                                </div>
                                <div class="template-option" data-template="kenpou">
                                    <div class="template-preview">📜</div>
                                    <div class="template-info">
                                        <h5>憲法テンプレート</h5>
                                        <p>憲法の基本構造</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h4>マップ設定</h4>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="is_public" value="1">
                                        公開マップにする（他のユーザーも閲覧可能）
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="is_template" value="1">
                                        テンプレートとして提供する
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">マップを作成</button>
                            <button type="button" class="btn btn-secondary mindmap-modal-close">キャンセル</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- ユーザーマップ一覧モーダル -->
        <div class="mindmap-user-maps-modal" id="mindmap-user-maps-<?php echo esc_attr($unique_id); ?>" style="display: none;">
            <div class="mindmap-modal-overlay"></div>
            <div class="mindmap-modal-content">
                <div class="mindmap-modal-header">
                    <h3 class="mindmap-modal-title">マイマップ管理</h3>
                    <button class="mindmap-modal-close">✕</button>
                </div>
                <div class="mindmap-modal-body">
                    <div class="user-maps-tabs">
                        <button class="tab-btn active" data-tab="my-maps">マイマップ</button>
                        <button class="tab-btn" data-tab="public-maps">公開マップ</button>
                        <button class="tab-btn" data-tab="templates">テンプレート</button>
                    </div>
                    
                    <div class="tab-content" id="my-maps-<?php echo esc_attr($unique_id); ?>">
                        <div class="maps-toolbar">
                            <button class="btn btn-primary" data-action="create-new-map">新規作成</button>
                            <div class="search-box">
                                <input type="text" placeholder="マップを検索..." class="map-search">
                            </div>
                        </div>
                        <div class="maps-grid" id="user-maps-list-<?php echo esc_attr($unique_id); ?>">
                            <div class="loading-spinner">読み込み中...</div>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="public-maps-<?php echo esc_attr($unique_id); ?>" style="display: none;">
                        <div class="maps-grid" id="public-maps-list-<?php echo esc_attr($unique_id); ?>">
                            <div class="loading-spinner">読み込み中...</div>
                        </div>
                    </div>
                    
                    <div class="tab-content" id="templates-<?php echo esc_attr($unique_id); ?>" style="display: none;">
                        <div class="maps-grid" id="templates-list-<?php echo esc_attr($unique_id); ?>">
                            <div class="loading-spinner">読み込み中...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- マップ設定モーダル -->
        <div class="mindmap-settings-modal" id="mindmap-settings-<?php echo esc_attr($unique_id); ?>" style="display: none;">
            <div class="mindmap-modal-overlay"></div>
            <div class="mindmap-modal-content">
                <div class="mindmap-modal-header">
                    <h3 class="mindmap-modal-title">マップ設定</h3>
                    <button class="mindmap-modal-close">✕</button>
                </div>
                <div class="mindmap-modal-body">
                    <form class="mindmap-settings-form">
                        <div class="settings-section">
                            <h4>表示設定</h4>
                            <div class="form-group">
                                <label for="map-theme-<?php echo esc_attr($unique_id); ?>">テーマ</label>
                                <select id="map-theme-<?php echo esc_attr($unique_id); ?>" name="theme">
                                    <option value="light">ライト</option>
                                    <option value="dark">ダーク</option>
                                    <option value="blue">ブルー</option>
                                    <option value="green">グリーン</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="node-style-<?php echo esc_attr($unique_id); ?>">ノードスタイル</label>
                                <select id="node-style-<?php echo esc_attr($unique_id); ?>" name="node_style">
                                    <option value="rounded">角丸</option>
                                    <option value="square">四角</option>
                                    <option value="circle">円形</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h4>共有設定</h4>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_public" value="1">
                                    公開マップにする
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="allow_copy" value="1">
                                    他ユーザーの複製を許可
                                </label>
                            </div>
                        </div>
                        
                        <div class="settings-section">
                            <h4>エクスポート/インポート</h4>
                            <div class="form-actions-inline">
                                <button type="button" class="btn btn-secondary" data-action="export-map">
                                    📥 エクスポート
                                </button>
                                <label class="btn btn-secondary file-upload">
                                    📤 インポート
                                    <input type="file" accept=".json" style="display: none;">
                                </label>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">設定を保存</button>
                            <button type="button" class="btn btn-secondary mindmap-modal-close">キャンセル</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php
        return ob_get_clean();
    }
    
    // Ajax処理: カスタムマップ作成
    public function ajax_create_custom_map() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('ログインが必要です');
        }
        
        $user_id = get_current_user_id();
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $template = sanitize_text_field($_POST['template'] ?? 'blank');
        $is_public = isset($_POST['is_public']) ? 1 : 0;
        $is_template = isset($_POST['is_template']) ? 1 : 0;
        
        // テンプレートに基づいてマップデータを生成
        $map_data = $this->generate_map_from_template($template);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gyosei_custom_maps';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'map_title' => $title,
                'map_description' => $description,
                'map_data' => json_encode($map_data),
                'map_settings' => json_encode(array(
                    'theme' => 'light',
                    'node_style' => 'rounded'
                )),
                'is_public' => $is_public,
                'is_template' => $is_template
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        if ($result) {
            wp_send_json_success(array(
                'map_id' => $wpdb->insert_id,
                'message' => 'マップが作成されました'
            ));
        } else {
            wp_send_json_error('マップの作成に失敗しました');
        }
    }
    
    // Ajax処理: カスタムマップ保存
    public function ajax_save_custom_map() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('ログインが必要です');
        }
        
        $user_id = get_current_user_id();
        $map_id = intval($_POST['map_id']);
        $map_data = $_POST['map_data'];
        $settings = $_POST['settings'] ?? '{}';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gyosei_custom_maps';
        
        // 所有者確認
        $map = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
            $map_id, $user_id
        ));
        
        if (!$map) {
            wp_send_json_error('マップが見つからないか、編集権限がありません');
        }
        
        $result = $wpdb->update(
            $table_name,
            array(
                'map_data' => is_string($map_data) ? $map_data : json_encode($map_data),
                'map_settings' => is_string($settings) ? $settings : json_encode($settings)
            ),
            array('id' => $map_id),
            array('%s', '%s'),
            array('%d')
        );
        
        if ($result !== false) {
            wp_send_json_success('マップが保存されました');
        } else {
            wp_send_json_error('保存に失敗しました');
        }
    }
    
    // Ajax処理: カスタムマップデータ取得
    public function ajax_get_custom_map_data() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        $custom_id = intval($_POST['custom_id']);
        $map_data = $this->get_custom_map_data($custom_id);
        
        if ($map_data) {
            wp_send_json_success($map_data);
        } else {
            wp_send_json_error('マップが見つかりません');
        }
    }
    
    // Ajax処理: ユーザーマップ一覧取得
    public function ajax_load_user_maps() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('ログインが必要です');
        }
        
        $user_id = get_current_user_id();
        $type = sanitize_text_field($_POST['type'] ?? 'my-maps');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gyosei_custom_maps';
        
        switch ($type) {
            case 'my-maps':
                $maps = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE user_id = %d ORDER BY updated_at DESC",
                    $user_id
                ));
                break;
                
            case 'public-maps':
                $maps = $wpdb->get_results(
                    "SELECT m.*, u.display_name as author_name 
                     FROM $table_name m 
                     LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID 
                     WHERE m.is_public = 1 AND m.user_id != $user_id
                     ORDER BY m.updated_at DESC 
                     LIMIT 50"
                );
                break;
                
            case 'templates':
                $maps = $wpdb->get_results(
                    "SELECT m.*, u.display_name as author_name 
                     FROM $table_name m 
                     LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID 
                     WHERE m.is_template = 1 
                     ORDER BY m.updated_at DESC"
                );
                break;
                
            default:
                wp_send_json_error('無効なタイプです');
        }
        
        // マップデータを整形
        $formatted_maps = array();
        foreach ($maps as $map) {
            $map_data = json_decode($map->map_data, true);
            $node_count = isset($map_data['nodes']) ? count($map_data['nodes']) : 0;
            
            $formatted_maps[] = array(
                'id' => $map->id,
                'title' => $map->map_title,
                'description' => $map->map_description,
                'node_count' => $node_count,
                'is_public' => $map->is_public,
                'is_template' => $map->is_template,
                'author_name' => $map->author_name ?? '',
                'created_at' => $map->created_at,
                'updated_at' => $map->updated_at
            );
        }
        
        wp_send_json_success($formatted_maps);
    }
    
    // Ajax処理: マップ複製
    public function ajax_clone_map() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('ログインが必要です');
        }
        
        $user_id = get_current_user_id();
        $source_map_id = intval($_POST['source_map_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gyosei_custom_maps';
        
        // 元マップを取得
        $source_map = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND (is_public = 1 OR user_id = %d)",
            $source_map_id, $user_id
        ));
        
        if (!$source_map) {
            wp_send_json_error('マップが見つからないか、複製権限がありません');
        }
        
        // 新しいマップとして保存
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'map_title' => $source_map->map_title . ' (コピー)',
                'map_description' => $source_map->map_description,
                'map_data' => $source_map->map_data,
                'map_settings' => $source_map->map_settings,
                'is_public' => 0, // コピーは非公開から始める
                'is_template' => 0
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        if ($result) {
            $new_map_id = $wpdb->insert_id;
            wp_send_json_success(array(
                'map_id' => $new_map_id,
                'message' => 'マップが複製されました'
            ));
        } else {
            wp_send_json_error('複製に失敗しました');
        }
    }
    
    // Ajax処理: マップエクスポート
    public function ajax_export_map() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('ログインが必要です');
        }
        
        $user_id = get_current_user_id();
        $map_id = intval($_POST['map_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gyosei_custom_maps';
        
        $map = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
            $map_id, $user_id
        ));
        
        if (!$map) {
            wp_send_json_error('マップが見つからないか、エクスポート権限がありません');
        }
        
        $export_data = array(
            'version' => '1.0',
            'export_date' => current_time('mysql'),
            'map_title' => $map->map_title,
            'map_description' => $map->map_description,
            'map_data' => json_decode($map->map_data, true),
            'map_settings' => json_decode($map->map_settings, true)
        );
        
        wp_send_json_success($export_data);
    }
    
    // Ajax処理: マップインポート
    public function ajax_import_map() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('ログインが必要です');
        }
        
        $user_id = get_current_user_id();
        $import_data = json_decode(stripslashes($_POST['import_data']), true);
        
        if (!$import_data || !isset($import_data['map_data'])) {
            wp_send_json_error('無効なインポートデータです');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'gyosei_custom_maps';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'map_title' => ($import_data['map_title'] ?? 'インポートマップ') . ' (インポート)',
                'map_description' => $import_data['map_description'] ?? '',
                'map_data' => json_encode($import_data['map_data']),
                'map_settings' => json_encode($import_data['map_settings'] ?? array()),
                'is_public' => 0,
                'is_template' => 0
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        if ($result) {
            $map_id = $wpdb->insert_id;
            wp_send_json_success(array(
                'map_id' => $map_id,
                'message' => 'マップがインポートされました'
            ));
        } else {
            wp_send_json_error('インポートに失敗しました');
        }
    }
    
    // テンプレートからマップデータを生成
    private function generate_map_from_template($template) {
        switch ($template) {
            case 'blank':
                return array(
                    'title' => '新しいマップ',
                    'nodes' => array(
                        array(
                            'id' => 'root',
                            'text' => '中心テーマ',
                            'x' => 400,
                            'y' => 200,
                            'level' => 0,
                            'color' => '#3f51b5',
                            'icon' => '💡',
                            'progress' => 0,
                            'status' => 'not-started',
                            'description' => 'ここに説明を入力してください'
                        )
                    ),
                    'connections' => array()
                );
                
            case 'gyosei':
            case 'minpo':
            case 'kenpou':
                $sample_data = $this->get_sample_data();
                return $sample_data[$template] ?? $this->generate_map_from_template('blank');
                
            default:
                return $this->generate_map_from_template('blank');
        }
    }
    
    // カスタムマップデータ取得
    protected function get_custom_map_data($custom_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gyosei_custom_maps';
        
        $map = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $custom_id
        ));
        
        if ($map) {
            // 公開マップまたは自分のマップのみアクセス可能
            if ($map->is_public || (is_user_logged_in() && $map->user_id == get_current_user_id())) {
                return array(
                    'title' => $map->map_title,
                    'data' => json_decode($map->map_data, true),
                    'settings' => json_decode($map->map_settings, true),
                    'author_id' => $map->user_id,
                    'is_public' => $map->is_public,
                    'created_at' => $map->created_at
                );
            }
        }
        
        return false;
    }
}

// Phase3A インスタンスの作成（Phase2が存在する場合のみ）
if (class_exists('GyoseiMindMapPhase2')) {
    GyoseiMindMapPhase3A::get_instance();
}