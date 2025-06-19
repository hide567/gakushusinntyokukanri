<?php
/**
 * 行政書士の道 - マインドマップ Phase 2拡張機能
 * ノード詳細ポップアップ・検索機能・データ管理
 */

// 既存のクラスを拡張
class GyoseiMindMapPhase2 extends GyoseiMindMap {
    
    public function __construct() {
        parent::__construct();
        
        // Phase 2の追加機能
        add_action('wp_ajax_get_node_details', array($this, 'ajax_get_node_details'));
        add_action('wp_ajax_nopriv_get_node_details', array($this, 'ajax_get_node_details'));
        add_action('wp_ajax_update_node_progress', array($this, 'ajax_update_node_progress'));
        add_action('wp_ajax_search_nodes', array($this, 'ajax_search_nodes'));
    }
    
    public function enqueue_scripts() {
        parent::enqueue_scripts();
        
        // Phase 2専用CSS・JS
        wp_enqueue_style(
            'gyosei-mindmap-phase2-css',
            plugin_dir_url(__FILE__) . 'assets/mindmap-phase2.css',
            array('gyosei-mindmap-css'),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'gyosei-mindmap-phase2-js',
            plugin_dir_url(__FILE__) . 'assets/mindmap-phase2.js',
            array('gyosei-mindmap-js'),
            '1.0.0',
            true
        );
    }
    
    public function mindmap_shortcode($atts) {
        $atts = shortcode_atts(array(
            'data' => 'gyosei',
            'title' => '行政法',
            'width' => '100%',
            'height' => '400px',
            'search' => 'true',
            'details' => 'true',
            'draggable' => 'false'
        ), $atts);
        
        $unique_id = 'mindmap-' . uniqid();
        
        ob_start();
        ?>
        <div class="mindmap-container mindmap-phase2" data-mindmap-id="<?php echo esc_attr($unique_id); ?>">
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
        
        <!-- ノード詳細モーダル -->
        <?php if ($atts['details'] === 'true'): ?>
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
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php
        return ob_get_clean();
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
            'study_tips' => $this->get_study_tips($node_id)
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
}

// Phase 2の初期化
if (!class_exists('GyoseiMindMap')) {
    // 基本クラスが読み込まれていない場合のエラー処理
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>行政書士の道 マインドマップ: 基本プラグインが必要です。</p></div>';
    });
} else {
    // Phase 2を有効化
    new GyoseiMindMapPhase2();
}