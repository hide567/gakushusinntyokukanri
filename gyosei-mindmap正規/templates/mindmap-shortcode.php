<?php
/**
 * マインドマップショートコード用テンプレート (修正版)
 * File: templates/mindmap-shortcode.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// データの検証と準備
$error_message = '';

// カスタムマップの場合
if (!empty($atts['map_id']) && $atts['map_id'] > 0) {
    if (isset($this->user_manager) && method_exists($this->user_manager, 'get_mindmap')) {
        $custom_map = $this->user_manager->get_mindmap($atts['map_id'], get_current_user_id());
        if ($custom_map && !empty($custom_map['map_data'])) {
            $atts['title'] = $custom_map['title'];
            $map_data = $custom_map['map_data'];
        }
    }
}

// サンプルデータの場合
if (!isset($map_data) && isset($this->sample_data[$atts['data']])) {
    $map_data = $this->sample_data[$atts['data']];
    if (empty($atts['title'])) {
        $atts['title'] = $map_data['title'] ?? 'マインドマップ';
    }
}

// データが見つからない場合のエラーハンドリング
if (!isset($map_data) || !$map_data) {
    $error_message = 'マップデータが見つかりませんでした。';
    if (isset($this->sample_data) && is_array($this->sample_data)) {
        $error_message .= ' 利用可能: ' . implode(', ', array_keys($this->sample_data));
    }
}
?>

<div class="mindmap-container <?php echo $atts['search'] === 'true' || $atts['details'] === 'true' ? 'mindmap-phase2' : ''; ?>" 
     id="<?php echo esc_attr($unique_id); ?>" 
     data-mindmap-id="<?php echo esc_attr($unique_id); ?>">
     
    <?php if ($error_message): ?>
        <div class="mindmap-error" style="padding: 20px; text-align: center; color: #666; border: 1px solid #ddd; border-radius: 5px;">
            <p><?php echo esc_html($error_message); ?></p>
            <p><small>データ属性: <?php echo esc_html($atts['data']); ?></small></p>
        </div>
    <?php else: ?>
        
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
                
                <?php if ($atts['ai_assistant'] === 'true' && class_exists('GyoseiAIAssistant')): ?>
                <button class="mindmap-btn" data-action="ai-assistant" title="AI支援">🤖</button>
                <?php endif; ?>
                
                <?php if ($atts['analytics'] === 'true' && class_exists('GyoseiAnalytics')): ?>
                <button class="mindmap-btn" data-action="analytics" title="分析">📊</button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mindmap-canvas" 
             style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;"
             data-mindmap-type="<?php echo esc_attr($atts['data']); ?>"
             data-map-id="<?php echo esc_attr($atts['map_id']); ?>"
             data-search="<?php echo esc_attr($atts['search']); ?>"
             data-details="<?php echo esc_attr($atts['details']); ?>"
             data-draggable="<?php echo esc_attr($atts['draggable']); ?>"
             data-community="<?php echo esc_attr($atts['community']); ?>"
             data-theme="<?php echo esc_attr($atts['theme']); ?>">
            <div class="mindmap-loading">マインドマップを読み込み中...</div>
        </div>
        
        <?php if ($atts['details'] === 'true'): ?>
        <!-- ノード詳細モーダル -->
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
                        
                        <div class="mindmap-difficulty-controls">
                            <label>難易度評価:</label>
                            <select class="mindmap-difficulty-select">
                                <option value="1">とても簡単</option>
                                <option value="2">簡単</option>
                                <option value="3">普通</option>
                                <option value="4">難しい</option>
                                <option value="5">とても難しい</option>
                            </select>
                        </div>
                        
                        <button class="mindmap-save-progress">進捗を保存</button>
                    </div>
                    
                    <div class="mindmap-node-notes">
                        <h4>学習メモ</h4>
                        <textarea class="mindmap-notes-input" placeholder="学習メモを入力..." rows="4"></textarea>
                        <button class="mindmap-save-notes">メモを保存</button>
                    </div>
                    <?php else: ?>
                    <div class="mindmap-login-prompt">
                        <p>進捗管理とメモ機能を利用するには<a href="<?php echo wp_login_url(); ?>">ログイン</a>が必要です。</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($atts['community'] === 'true' && class_exists('GyoseiCommunity')): ?>
        <!-- コミュニティパネル -->
        <div id="community-panel-<?php echo esc_attr($unique_id); ?>" class="mindmap-community-panel" style="display: none;">
            <div class="community-panel-header">
                <h4>コミュニティ</h4>
                <button class="community-panel-close">✕</button>
            </div>
            <div class="community-panel-content">
                <div class="community-tabs">
                    <button class="community-tab active" data-tab="comments">コメント</button>
                    <button class="community-tab" data-tab="ratings">評価</button>
                    <button class="community-tab" data-tab="share">共有</button>
                </div>
                <div class="community-tab-content">
                    <!-- コミュニティコンテンツ -->
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- JavaScriptでマップデータを設定 -->
        <script>
        (function() {
            // グローバルmindmapDataオブジェクトの初期化
            if (typeof window.mindmapData === 'undefined') {
                window.mindmapData = {
                    sampleData: {},
                    ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
                    nonce: '<?php echo wp_create_nonce('mindmap_nonce'); ?>',
                    isLoggedIn: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>,
                    currentUser: <?php echo is_user_logged_in() ? get_current_user_id() : 0; ?>,
                    pluginUrl: '<?php echo GYOSEI_MINDMAP_PLUGIN_URL; ?>'
                };
            }
            
            if (!window.mindmapData.sampleData) {
                window.mindmapData.sampleData = {};
            }
            
            // このマップのデータを設定
            window.mindmapData.sampleData['<?php echo esc_js($atts['data']); ?>'] = <?php echo wp_json_encode($map_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            
            // マップ固有の設定
            window.mindmapData.currentMap = {
                id: '<?php echo esc_js($unique_id); ?>',
                type: '<?php echo esc_js($atts['data']); ?>',
                features: {
                    search: <?php echo $atts['search'] === 'true' ? 'true' : 'false'; ?>,
                    details: <?php echo $atts['details'] === 'true' ? 'true' : 'false'; ?>,
                    draggable: <?php echo $atts['draggable'] === 'true' ? 'true' : 'false'; ?>,
                    community: <?php echo $atts['community'] === 'true' ? 'true' : 'false'; ?>,
                    analytics: <?php echo $atts['analytics'] === 'true' ? 'true' : 'false'; ?>
                }
            };
            
            // DOM読み込み完了後に初期化
            function initCurrentMap() {
                // マインドマップクラスが利用可能かチェック
                if (typeof window.MindMapRenderer !== 'undefined') {
                    try {
                        const container = document.getElementById('<?php echo esc_js($unique_id); ?>');
                        if (container) {
                            const mapData = window.mindmapData.sampleData['<?php echo esc_js($atts['data']); ?>'];
                            
                            // Phase2機能があるかチェック
                            const usePhase2 = (<?php echo $atts['search'] === 'true' || $atts['details'] === 'true' ? 'true' : 'false'; ?>) && 
                                             typeof window.MindMapRendererPhase2 !== 'undefined';
                            
                            const RendererClass = usePhase2 ? window.MindMapRendererPhase2 : window.MindMapRenderer;
                            new RendererClass(container, mapData);
                            
                            console.log('マインドマップが正常に初期化されました:', '<?php echo esc_js($unique_id); ?>');
                        }
                    } catch (error) {
                        console.error('マインドマップの初期化に失敗しました:', error);
                        
                        // エラー表示
                        const container = document.getElementById('<?php echo esc_js($unique_id); ?>');
                        if (container) {
                            const canvas = container.querySelector('.mindmap-canvas');
                            if (canvas) {
                                canvas.innerHTML = '<div style="text-align: center; padding: 50px; color: #999;">マインドマップの読み込みに失敗しました<br><small>' + error.message + '</small></div>';
                            }
                        }
                    }
                } else {
                    // フォールバック処理
                    setTimeout(function() {
                        if (typeof window.initializeMindMaps === 'function') {
                            window.initializeMindMaps();
                        } else {
                            console.warn('MindMapRendererクラスまたは初期化関数が見つかりません');
                        }
                    }, 100);
                }
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initCurrentMap);
            } else {
                initCurrentMap();
            }
        })();
        </script>
        
    <?php endif; ?>
</div>

<?php if (!$error_message): ?>
<style>
/* マップ固有のスタイル調整 */
#<?php echo esc_attr($unique_id); ?> {
    margin: 20px 0;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

#<?php echo esc_attr($unique_id); ?> .mindmap-canvas {
    min-height: 300px;
    background: linear-gradient(45deg, #f8f9fa 25%, transparent 25%),
                linear-gradient(-45deg, #f8f9fa 25%, transparent 25%),
                linear-gradient(45deg, transparent 75%, #f8f9fa 75%),
                linear-gradient(-45deg, transparent 75%, #f8f9fa 75%);
    background-size: 20px 20px;
    background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
}

#<?php echo esc_attr($unique_id); ?> .mindmap-error {
    background: #fff3cd;
    border-color: #ffeaa7;
    color: #856404;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    #<?php echo esc_attr($unique_id); ?> .mindmap-header {
        flex-direction: column;
        gap: 10px;
    }
    
    #<?php echo esc_attr($unique_id); ?> .mindmap-search-container {
        order: -1;
        width: 100%;
    }
    
    #<?php echo esc_attr($unique_id); ?> .mindmap-search {
        width: 100%;
    }
}
</style>
<?php endif; ?>