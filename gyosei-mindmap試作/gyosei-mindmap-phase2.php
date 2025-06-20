<?php
/**
 * 行政書士の道 - マインドマップ Phase 2
 * 検索・詳細表示・ドラッグ機能
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// 基本クラスが存在することを確認
if (!class_exists('GyoseiMindMap')) {
    return;
}

class GyoseiMindMapPhase2 extends GyoseiMindMap {
    
    protected static $phase2_instance = null;
    
    public static function get_instance() {
        if (null === self::$phase2_instance) {
            self::$phase2_instance = new self();
        }
        return self::$phase2_instance;
    }
    
    public function __construct() {
        // 親クラスの初期化はスキップ（既に初期化済み）
        
        // Phase 2 専用のAjax処理を追加
        add_action('wp_ajax_search_nodes', array($this, 'ajax_search_nodes'));
        add_action('wp_ajax_nopriv_search_nodes', array($this, 'ajax_search_nodes'));
        add_action('wp_ajax_get_node_resources', array($this, 'ajax_get_node_resources'));
        add_action('wp_ajax_nopriv_get_node_resources', array($this, 'ajax_get_node_resources'));
    }
    
    // ショートコード機能を拡張（オーバーライド）
    public function mindmap_shortcode($atts) {
        $atts = shortcode_atts(array(
            'data' => 'gyosei',
            'title' => '行政法',
            'width' => '100%',
            'height' => '400px',
            'search' => 'true',  // Phase2 ではデフォルトでON
            'details' => 'true', // Phase2 ではデフォルトでON
            'draggable' => 'false',
            'editable' => 'false',
            'custom_id' => '',
            'community' => 'false'
        ), $atts);
        
        // 親クラスのショートコードを呼び出し、Phase2の機能を追加
        $base_output = parent::mindmap_shortcode($atts);
        
        // Phase2固有のモーダルを追加
        $unique_id = 'mindmap-' . uniqid();
        $phase2_modals = $this->render_phase2_modals($unique_id, $atts);
        
        return $base_output . $phase2_modals;
    }
    
    // Phase2専用モーダルのレンダリング
    private function render_phase2_modals($unique_id, $atts) {
        if ($atts['details'] !== 'true') {
            return '';
        }
        
        ob_start();
        ?>
        <!-- 拡張ノード詳細モーダル -->
        <div class="mindmap-modal mindmap-phase2-modal" id="mindmap-modal-<?php echo esc_attr($unique_id); ?>" style="display: none;">
            <div class="mindmap-modal-overlay"></div>
            <div class="mindmap-modal-content">
                <div class="mindmap-modal-header">
                    <h3 class="mindmap-modal-title"></h3>
                    <button class="mindmap-modal-close">✕</button>
                </div>
                <div class="mindmap-modal-body">
                    
                    <!-- ノード情報セクション -->
                    <div class="mindmap-node-info">
                        <div class="mindmap-node-status">
                            <label>ステータス:</label>
                            <span class="mindmap-status-badge"></span>
                        </div>
                        <div class="mindmap-node-progress-display">
                            <label>進捗:</label>
                            <div class="mindmap-progress-circle">
                                <span class="mindmap-progress-text">0%</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 説明セクション -->
                    <div class="mindmap-node-description">
                        <h4>説明</h4>
                        <div class="description-content"></div>
                    </div>
                    
                    <!-- リソースセクション -->
                    <div class="mindmap-node-resources">
                        <h4>関連リソース</h4>
                        <div class="mindmap-resources-list">
                            <!-- Ajax で動的に読み込み -->
                        </div>
                    </div>
                    
                    <!-- 学習のコツ -->
                    <div class="mindmap-study-tips">
                        <h4>学習のコツ</h4>
                        <div class="study-tips-content"></div>
                    </div>
                    
                    <?php if (is_user_logged_in()): ?>
                    <!-- 学習管理コントロール -->
                    <div class="mindmap-study-controls">
                        <h4>学習管理</h4>
                        
                        <div class="mindmap-progress-controls">
                            <label>進捗率:</label>
                            <input type="range" class="mindmap-progress-slider" min="0" max="100" step="5" value="0">
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
                    
                    <!-- メモセクション -->
                    <div class="mindmap-node-notes">
                        <h4>学習メモ</h4>
                        <textarea class="mindmap-notes-input" placeholder="学習したことをメモしてください..." rows="4"></textarea>
                        <button class="mindmap-save-notes">メモを保存</button>
                    </div>
                    <?php else: ?>
                    <div class="mindmap-login-notice">
                        <p>学習進捗の保存やメモ機能を利用するには<a href="<?php echo wp_login_url(); ?>">ログイン</a>が必要です。</p>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Ajax処理: ノード検索
    public function ajax_search_nodes() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        $map_type = sanitize_text_field($_POST['map_type']);
        
        if (empty(trim($query))) {
            wp_send_json_success(array());
        }
        
        $results = $this->search_nodes($query, $map_type);
        wp_send_json_success($results);
    }
    
    // Ajax処理: ノードリソース取得
    public function ajax_get_node_resources() {
        check_ajax_referer('mindmap_nonce', 'nonce');
        
        $node_id = sanitize_text_field($_POST['node_id']);
        $map_type = sanitize_text_field($_POST['map_type']);
        
        $resources = $this->get_node_resources($node_id, $map_type);
        wp_send_json_success($resources);
    }
    
    // ノード詳細取得を拡張（オーバーライド）
    protected function get_node_details($node_id, $map_type) {
        $basic_details = parent::get_node_details($node_id, $map_type);
        
        if (!$basic_details) {
            return false;
        }
        
        // Phase2 で追加情報を拡張
        $extended_details = array_merge($basic_details, array(
            'resources' => $this->get_node_resources($node_id, $map_type),
            'related_articles' => $this->get_related_articles($node_id, $map_type),
            'study_tips' => $this->get_study_tips($node_id, $map_type),
            'difficulty_level' => $this->get_difficulty_level($node_id, $map_type),
            'estimated_study_time' => $this->get_estimated_study_time($node_id, $map_type)
        ));
        
        return $extended_details;
    }
    
    // ノード検索機能
    private function search_nodes($query, $map_type) {
        $sample_data = $this->get_sample_data();
        
        if (!isset($sample_data[$map_type])) {
            return array();
        }
        
        $nodes = $sample_data[$map_type]['nodes'];
        $results = array();
        
        foreach ($nodes as $node) {
            $text_match = stripos($node['text'], $query) !== false;
            $desc_match = stripos($node['description'] ?? '', $query) !== false;
            
            if ($text_match || $desc_match) {
                $results[] = array(
                    'id' => $node['id'],
                    'text' => $node['text'],
                    'description' => $node['description'] ?? '',
                    'x' => $node['x'],
                    'y' => $node['y'],
                    'level' => $node['level'],
                    'match_type' => $text_match ? 'title' : 'description'
                );
            }
        }
        
        // 関連度順でソート（タイトルマッチを優先）
        usort($results, function($a, $b) {
            if ($a['match_type'] === 'title' && $b['match_type'] === 'description') {
                return -1;
            } elseif ($a['match_type'] === 'description' && $b['match_type'] === 'title') {
                return 1;
            }
            return 0;
        });
        
        return $results;
    }
    
    // ノードリソース取得
    private function get_node_resources($node_id, $map_type) {
        $resources_map = array(
            'gyosei' => array(
                'root' => array(
                    array('title' => '行政法入門テキスト', 'url' => '#', 'type' => '教科書', 'difficulty' => '初級'),
                    array('title' => '行政法判例集', 'url' => '#', 'type' => '判例集', 'difficulty' => '中級'),
                    array('title' => '行政法概説動画', 'url' => '#', 'type' => '動画', 'difficulty' => '初級')
                ),
                'general' => array(
                    array('title' => '行政行為の基礎理論', 'url' => '#', 'type' => '論文', 'difficulty' => '中級'),
                    array('title' => '行政裁量の判例分析', 'url' => '#', 'type' => '判例解説', 'difficulty' => '上級'),
                    array('title' => '行政法総論解説動画', 'url' => '#', 'type' => '動画', 'difficulty' => '中級')
                ),
                'procedure' => array(
                    array('title' => '行政手続法逐条解説', 'url' => '#', 'type' => '逐条解説', 'difficulty' => '中級'),
                    array('title' => '申請手続きの実務', 'url' => '#', 'type' => '実務書', 'difficulty' => '上級'),
                    array('title' => '手続法の条文暗記カード', 'url' => '#', 'type' => '暗記カード', 'difficulty' => '初級')
                ),
                'case_law' => array(
                    array('title' => '行政事件訴訟法解説', 'url' => '#', 'type' => '教科書', 'difficulty' => '中級'),
                    array('title' => '取消訴訟の要件事実', 'url' => '#', 'type' => '論文', 'difficulty' => '上級'),
                    array('title' => '訴訟類型一覧表', 'url' => '#', 'type' => '一覧表', 'difficulty' => '初級')
                ),
                'compensation' => array(
                    array('title' => '国家賠償法の基礎', 'url' => '#', 'type' => '教科書', 'difficulty' => '初級'),
                    array('title' => '公権力行使と賠償責任', 'url' => '#', 'type' => '論文', 'difficulty' => '中級'),
                    array('title' => '賠償事例集', 'url' => '#', 'type' => '事例集', 'difficulty' => '中級')
                )
            ),
            'minpo' => array(
                'root' => array(
                    array('title' => '民法総則入門', 'url' => '#', 'type' => '教科書', 'difficulty' => '初級'),
                    array('title' => '民法判例百選', 'url' => '#', 'type' => '判例集', 'difficulty' => '中級')
                )
            ),
            'kenpou' => array(
                'root' => array(
                    array('title' => '憲法学読本', 'url' => '#', 'type' => '教科書', 'difficulty' => '初級'),
                    array('title' => '憲法判例集', 'url' => '#', 'type' => '判例集', 'difficulty' => '中級')
                )
            )
        );
        
        return $resources_map[$map_type][$node_id] ?? array();
    }
    
    // 関連記事取得
    private function get_related_articles($node_id, $map_type) {
        $articles_map = array(
            'gyosei' => array(
                'root' => array(
                    array('title' => '行政法とは何か？基本概念を理解しよう', 'url' => '#', 'date' => '2024-01-15'),
                    array('title' => '公法と私法の違いを解説', 'url' => '#', 'date' => '2024-01-10'),
                    array('title' => '行政書士試験での行政法の出題傾向', 'url' => '#', 'date' => '2024-01-05')
                ),
                'general' => array(
                    array('title' => '行政行為の種類と効力', 'url' => '#', 'date' => '2024-02-01'),
                    array('title' => '行政裁量の限界について', 'url' => '#', 'date' => '2024-01-28'),
                    array('title' => '行政指導の法的性質', 'url' => '#', 'date' => '2024-01-25')
                ),
                'procedure' => array(
                    array('title' => '行政手続法の改正ポイント', 'url' => '#', 'date' => '2024-02-10'),
                    array('title' => '聴聞手続きの実務', 'url' => '#', 'date' => '2024-02-05'),
                    array('title' => '申請から処分までの流れ', 'url' => '#', 'date' => '2024-02-01')
                )
            )
        );
        
        return $articles_map[$map_type][$node_id] ?? array();
    }
    
    // 学習のコツ取得
    private function get_study_tips($node_id, $map_type) {
        $tips_map = array(
            'gyosei' => array(
                'root' => '行政法は体系的理解が重要です。まず全体像を把握してから詳細に入りましょう。各法律の相互関係を意識して学習すると効果的です。',
                'general' => '行政行為の概念は他の分野でも重要です。具体例と併せて理解し、処分性の判断基準を覚えましょう。図表を活用した整理がおすすめです。',
                'procedure' => '手続きの流れを図解で整理すると理解しやすくなります。特に聴聞と弁明の違いは重要なポイントです。条文の構造を意識しましょう。',
                'case_law' => '訴訟類型ごとの要件と効果を表で整理しましょう。処分性、原告適格、訴えの利益の判断は判例の知識が不可欠です。',
                'compensation' => '1条責任と2条責任の違いを明確にし、要件の違いを理解しましょう。具体的な事例で学習すると記憶に残りやすいです。'
            ),
            'minpo' => array(
                'root' => '民法は条文数が多いので、体系的な理解が重要です。各編の関係性を意識して学習しましょう。'
            ),
            'kenpou' => array(
                'root' => '憲法は価値判断が重要です。条文だけでなく、背景にある思想も理解しましょう。'
            )
        );
        
        return $tips_map[$map_type][$node_id] ?? '継続的な学習が成功の鍵です。定期的に復習し、理解を深めていきましょう。';
    }
    
    // 難易度レベル取得
    private function get_difficulty_level($node_id, $map_type) {
        $difficulty_map = array(
            'gyosei' => array(
                'root' => 'basic',
                'general' => 'intermediate',
                'procedure' => 'intermediate',
                'case_law' => 'advanced',
                'compensation' => 'intermediate'
            ),
            'minpo' => array(
                'root' => 'basic'
            ),
            'kenpou' => array(
                'root' => 'basic'
            )
        );
        
        return $difficulty_map[$map_type][$node_id] ?? 'basic';
    }
    
    // 推定学習時間取得
    private function get_estimated_study_time($node_id, $map_type) {
        $time_map = array(
            'gyosei' => array(
                'root' => '2-3時間',
                'general' => '8-10時間',
                'procedure' => '6-8時間',
                'case_law' => '10-12時間',
                'compensation' => '4-6時間'
            ),
            'minpo' => array(
                'root' => '3-4時間'
            ),
            'kenpou' => array(
                'root' => '2-3時間'
            )
        );
        
        return $time_map[$map_type][$node_id] ?? '2-3時間';
    }
}

// Phase2 インスタンスの作成（基本クラスが存在する場合のみ）
if (class_exists('GyoseiMindMap')) {
    GyoseiMindMapPhase2::get_instance();
}