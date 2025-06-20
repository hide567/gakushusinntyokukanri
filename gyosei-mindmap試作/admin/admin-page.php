<?php
/**
 * 行政書士の道 - マインドマップ管理画面メインページ
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// 統計データの取得
$total_maps = wp_count_posts('gyosei_mindmap');
$published_maps = $total_maps->publish ?? 0;
$draft_maps = $total_maps->draft ?? 0;

// カスタムマップの統計
global $wpdb;
$custom_maps_table = $wpdb->prefix . 'gyosei_custom_maps';
$custom_maps_count = 0;
$public_custom_maps = 0;

if ($wpdb->get_var("SHOW TABLES LIKE '$custom_maps_table'") == $custom_maps_table) {
    $custom_maps_count = $wpdb->get_var("SELECT COUNT(*) FROM $custom_maps_table");
    $public_custom_maps = $wpdb->get_var("SELECT COUNT(*) FROM $custom_maps_table WHERE is_public = 1");
}

// システム状態の確認
$system_status = 'active';
$features_status = array(
    'base' => class_exists('GyoseiMindMap'),
    'phase2' => class_exists('GyoseiMindMapPhase2'),
    'phase3a' => class_exists('GyoseiMindMapPhase3A'),
    'community' => class_exists('GyoseiMindMapPhase3B')
);

// マインドマップデータの取得
$mindmaps = get_posts(array(
    'post_type' => 'gyosei_mindmap',
    'posts_per_page' => -1,
    'post_status' => 'any',
    'orderby' => 'modified',
    'order' => 'DESC'
));

// カスタムマップデータの取得
$custom_mindmaps = array();
if ($wpdb->get_var("SHOW TABLES LIKE '$custom_maps_table'") == $custom_maps_table) {
    $custom_mindmaps = $wpdb->get_results(
        "SELECT m.*, u.display_name as author_name 
         FROM $custom_maps_table m 
         LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID 
         ORDER BY m.updated_at DESC 
         LIMIT 20"
    );
}
?>

<div class="wrap gyosei-admin-container">
    <div class="gyosei-admin-header">
        <h1>
            <span class="dashicons dashicons-networking" style="font-size: 24px; margin-right: 10px;"></span>
            マインドマップ管理
        </h1>
        <div class="header-actions">
            <a href="<?php echo admin_url('admin.php?page=gyosei-mindmap-settings'); ?>" class="button">
                <span class="dashicons dashicons-admin-settings"></span> 設定
            </a>
            <a href="<?php echo admin_url('admin.php?page=gyosei-mindmap-help'); ?>" class="button">
                <span class="dashicons dashicons-editor-help"></span> ヘルプ
            </a>
        </div>
    </div>
    
    <?php if (isset($_GET['message'])): ?>
    <div class="notice notice-success is-dismissible">
        <p>
            <?php
            switch ($_GET['message']) {
                case 'saved':
                    echo 'マインドマップが保存されました。';
                    break;
                case 'deleted':
                    echo 'マインドマップが削除されました。';
                    break;
                case 'updated':
                    echo '設定が更新されました。';
                    break;
                default:
                    echo '操作が完了しました。';
            }
            ?>
        </p>
    </div>
    <?php endif; ?>
    
    <!-- 統計カード -->
    <div class="gyosei-admin-stats">
        <div class="gyosei-stat-card">
            <span class="gyosei-stat-number"><?php echo $published_maps; ?></span>
            <span class="gyosei-stat-label">公開マップ</span>
            <span class="gyosei-stat-icon dashicons dashicons-visibility"></span>
        </div>
        <div class="gyosei-stat-card">
            <span class="gyosei-stat-number"><?php echo $draft_maps; ?></span>
            <span class="gyosei-stat-label">下書きマップ</span>
            <span class="gyosei-stat-icon dashicons dashicons-edit"></span>
        </div>
        <div class="gyosei-stat-card">
            <span class="gyosei-stat-number"><?php echo $custom_maps_count; ?></span>
            <span class="gyosei-stat-label">カスタムマップ</span>
            <span class="gyosei-stat-icon dashicons dashicons-admin-users"></span>
        </div>
        <div class="gyosei-stat-card">
            <span class="gyosei-stat-number"><?php echo $public_custom_maps; ?></span>
            <span class="gyosei-stat-label">公開カスタム</span>
            <span class="gyosei-stat-icon dashicons dashicons-share-alt2"></span>
        </div>
    </div>
    
    <!-- システム状態 -->
    <div class="gyosei-system-status">
        <h2>システム状態</h2>
        <div class="status-grid">
            <div class="status-item <?php echo $features_status['base'] ? 'active' : 'inactive'; ?>">
                <span class="status-icon dashicons dashicons-<?php echo $features_status['base'] ? 'yes-alt' : 'dismiss'; ?>"></span>
                <span class="status-label">基本機能</span>
            </div>
            <div class="status-item <?php echo $features_status['phase2'] ? 'active' : 'inactive'; ?>">
                <span class="status-icon dashicons dashicons-<?php echo $features_status['phase2'] ? 'yes-alt' : 'dismiss'; ?>"></span>
                <span class="status-label">検索・詳細機能</span>
            </div>
            <div class="status-item <?php echo $features_status['phase3a'] ? 'active' : 'inactive'; ?>">
                <span class="status-icon dashicons dashicons-<?php echo $features_status['phase3a'] ? 'yes-alt' : 'dismiss'; ?>"></span>
                <span class="status-label">ユーザー管理</span>
            </div>
            <div class="status-item <?php echo $features_status['community'] ? 'active' : 'inactive'; ?>">
                <span class="status-icon dashicons dashicons-<?php echo $features_status['community'] ? 'yes-alt' : 'dismiss'; ?>"></span>
                <span class="status-label">コミュニティ機能</span>
            </div>
        </div>
    </div>
    
    <!-- タブ切り替え -->
    <div class="gyosei-tabs">
        <ul class="gyosei-tab-nav">
            <li class="gyosei-tab-item active">
                <a href="#standard-maps" class="gyosei-tab-link">標準マップ</a>
            </li>
            <li class="gyosei-tab-item">
                <a href="#custom-maps" class="gyosei-tab-link">カスタムマップ</a>
            </li>
            <li class="gyosei-tab-item">
                <a href="#shortcodes" class="gyosei-tab-link">ショートコード</a>
            </li>
        </ul>
    </div>
    
    <!-- 標準マップタブ -->
    <div id="standard-maps" class="gyosei-tab-content">
        <div class="section-header">
            <h2>標準マインドマップ</h2>
            <a href="<?php echo admin_url('post-new.php?post_type=gyosei_mindmap'); ?>" class="button button-primary">新規作成</a>
        </div>
        <?php else: ?>
        <table class="gyosei-mindmap-table wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 30%;">タイトル</th>
                    <th style="width: 15%;">タイプ</th>
                    <th style="width: 35%;">ショートコード</th>
                    <th style="width: 15%;">最終更新</th>
                    <th style="width: 15%;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mindmaps as $mindmap): 
                    $map_type = get_post_meta($mindmap->ID, '_mindmap_type', true) ?: 'gyosei';
                    $map_title = $mindmap->post_title ?: 'マインドマップ';
                ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($map_title); ?></strong>
                        <div class="status-badge status-<?php echo $mindmap->post_status; ?>">
                            <?php echo $mindmap->post_status === 'publish' ? '公開' : '下書き'; ?>
                        </div>
                    </td>
                    <td>
                        <span class="map-type-badge type-<?php echo esc_attr($map_type); ?>">
                            <?php echo esc_html($map_type); ?>
                        </span>
                    </td>
                    <td>
                        <div class="shortcode-display">
                            <code class="shortcode-code">[mindmap data="<?php echo esc_attr($map_type); ?>" title="<?php echo esc_attr($map_title); ?>"]</code>
                            <button class="copy-shortcode" data-shortcode='[mindmap data="<?php echo esc_attr($map_type); ?>" title="<?php echo esc_attr($map_title); ?>"]' title="ショートコードをコピー">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                    </td>
                    <td><?php echo get_the_modified_date('Y/m/d H:i', $mindmap); ?></td>
                    <td>
                        <div class="action-buttons">
                            <a href="<?php echo get_edit_post_link($mindmap->ID); ?>" class="btn-edit" title="編集">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                            <button class="btn-delete delete-mindmap" data-id="<?php echo $mindmap->ID; ?>" title="削除">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <!-- カスタムマップタブ -->
    <div id="custom-maps" class="gyosei-tab-content" style="display: none;">
        <div class="section-header">
            <h2>カスタムマインドマップ</h2>
            <div class="filter-controls">
                <select id="custom-map-filter" class="filter-select">
                    <option value="all">すべて</option>
                    <option value="public">公開のみ</option>
                    <option value="private">非公開のみ</option>
                    <option value="template">テンプレートのみ</option>
                </select>
                <input type="text" id="custom-map-search" placeholder="検索..." class="filter-search">
            </div>
        </div>
        
        <?php if (empty($custom_mindmaps)): ?>
        <div class="no-items-message">
            <div class="no-items-icon dashicons dashicons-admin-users"></div>
            <h3>カスタムマップがありません</h3>
            <p>ユーザーが作成したカスタムマップがここに表示されます。</p>
        </div>
        <?php else: ?>
        <div class="custom-maps-grid" id="custom-maps-container">
            <?php foreach ($custom_mindmaps as $custom_map): 
                $map_data = json_decode($custom_map->map_data, true);
                $node_count = isset($map_data['nodes']) ? count($map_data['nodes']) : 0;
            ?>
            <div class="custom-map-card" data-public="<?php echo $custom_map->is_public; ?>" data-template="<?php echo $custom_map->is_template; ?>">
                <div class="card-header">
                    <h4><?php echo esc_html($custom_map->map_title); ?></h4>
                    <div class="card-badges">
                        <?php if ($custom_map->is_public): ?>
                        <span class="badge public">公開</span>
                        <?php endif; ?>
                        <?php if ($custom_map->is_template): ?>
                        <span class="badge template">テンプレート</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card-content">
                    <p class="map-description"><?php echo esc_html($custom_map->map_description ?: '説明なし'); ?></p>
                    <div class="map-stats">
                        <span class="stat-item">
                            <span class="dashicons dashicons-networking"></span>
                            <?php echo $node_count; ?>ノード
                        </span>
                        <span class="stat-item">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php echo esc_html($custom_map->author_name ?: 'ユーザー'); ?>
                        </span>
                    </div>
                </div>
                
                <div class="card-footer">
                    <div class="card-meta">
                        <span class="creation-date"><?php echo date('Y/m/d', strtotime($custom_map->created_at)); ?></span>
                    </div>
                    <div class="card-actions">
                        <button class="btn-view" data-map-id="<?php echo $custom_map->id; ?>" title="プレビュー">
                            <span class="dashicons dashicons-visibility"></span>
                        </button>
                        <button class="btn-copy-shortcode" data-shortcode='[mindmap custom_id="<?php echo $custom_map->id; ?>"]' title="ショートコードコピー">
                            <span class="dashicons dashicons-admin-page"></span>
                        </button>
                        <button class="btn-delete-custom" data-id="<?php echo $custom_map->id; ?>" title="削除">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- ショートコードタブ -->
    <div id="shortcodes" class="gyosei-tab-content" style="display: none;">
        <h2>ショートコード一覧</h2>
        
        <div class="shortcode-examples">
            <div class="shortcode-example">
                <h4>基本的なマインドマップ</h4>
                <div class="code-example">
                    <code>[mindmap data="gyosei" title="行政法"]</code>
                    <button class="copy-shortcode" data-shortcode='[mindmap data="gyosei" title="行政法"]'>
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                </div>
                <p class="example-description">行政法の基本マインドマップを表示します。</p>
            </div>
            
            <div class="shortcode-example">
                <h4>検索・詳細機能付き</h4>
                <div class="code-example">
                    <code>[mindmap data="gyosei" title="行政法" search="true" details="true"]</code>
                    <button class="copy-shortcode" data-shortcode='[mindmap data="gyosei" title="行政法" search="true" details="true"]'>
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                </div>
                <p class="example-description">検索機能と詳細表示モーダルが有効なマインドマップです。</p>
            </div>
            
            <div class="shortcode-example">
                <h4>編集可能マップ</h4>
                <div class="code-example">
                    <code>[mindmap custom_id="123" editable="true"]</code>
                    <button class="copy-shortcode" data-shortcode='[mindmap custom_id="123" editable="true"]'>
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                </div>
                <p class="example-description">ユーザーが編集できるカスタムマインドマップです。</p>
            </div>
            
            <div class="shortcode-example">
                <h4>コミュニティ機能付き</h4>
                <div class="code-example">
                    <code>[mindmap custom_id="123" community="true" show_rating="true" show_comments="true"]</code>
                    <button class="copy-shortcode" data-shortcode='[mindmap custom_id="123" community="true" show_rating="true" show_comments="true"]'>
                        <span class="dashicons dashicons-admin-page"></span>
                    </button>
                </div>
                <p class="example-description">評価やコメント機能が有効なマインドマップです。</p>
            </div>
        </div>
        
        <div class="shortcode-parameters">
            <h3>パラメーター一覧</h3>
            <table class="parameters-table">
                <thead>
                    <tr>
                        <th>パラメーター</th>
                        <th>説明</th>
                        <th>デフォルト値</th>
                        <th>例</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>data</code></td>
                        <td>マップの種類</td>
                        <td>gyosei</td>
                        <td>gyosei, minpo, kenpou</td>
                    </tr>
                    <tr>
                        <td><code>title</code></td>
                        <td>マップのタイトル</td>
                        <td>行政法</td>
                        <td>任意のテキスト</td>
                    </tr>
                    <tr>
                        <td><code>width</code></td>
                        <td>マップの幅</td>
                        <td>100%</td>
                        <td>800px, 100%</td>
                    </tr>
                    <tr>
                        <td><code>height</code></td>
                        <td>マップの高さ</td>
                        <td>400px</td>
                        <td>500px, 80vh</td>
                    </tr>
                    <tr>
                        <td><code>search</code></td>
                        <td>検索機能の有効/無効</td>
                        <td>false</td>
                        <td>true, false</td>
                    </tr>
                    <tr>
                        <td><code>details</code></td>
                        <td>詳細表示の有効/無効</td>
                        <td>false</td>
                        <td>true, false</td>
                    </tr>
                    <tr>
                        <td><code>editable</code></td>
                        <td>編集機能の有効/無効</td>
                        <td>false</td>
                        <td>true, false</td>
                    </tr>
                    <tr>
                        <td><code>custom_id</code></td>
                        <td>カスタムマップのID</td>
                        <td>なし</td>
                        <td>123, 456</td>
                    </tr>
                    <tr>
                        <td><code>community</code></td>
                        <td>コミュニティ機能の有効/無効</td>
                        <td>false</td>
                        <td>true, false</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- クイックヘルプ -->
    <div class="gyosei-quick-help">
        <h3>使い方</h3>
        <div class="help-grid">
            <div class="help-item">
                <span class="help-icon dashicons dashicons-editor-code"></span>
                <h4>ショートコードの使用</h4>
                <p>投稿や固定ページでマインドマップを表示するには、上記のショートコードをコピーして貼り付けてください。</p>
            </div>
            <div class="help-item">
                <span class="help-icon dashicons dashicons-admin-users"></span>
                <h4>ユーザー作成マップ</h4>
                <p>ログインユーザーは独自のマインドマップを作成・編集することができます。</p>
            </div>
            <div class="help-item">
                <span class="help-icon dashicons dashicons-share-alt2"></span>
                <h4>マップの共有</h4>
                <p>カスタムマップは公開設定により他のユーザーと共有することができます。</p>
            </div>
            <div class="help-item">
                <span class="help-icon dashicons dashicons-chart-line"></span>
                <h4>学習進捗管理</h4>
                <p>ユーザーは各ノードの学習進捗を管理し、メモを残すことができます。</p>
            </div>
        </div>
    </div>
</div>

<!-- マッププレビューモーダル -->
<div id="map-preview-modal" class="mindmap-modal" style="display: none;">
    <div class="mindmap-modal-overlay"></div>
    <div class="mindmap-modal-content">
        <div class="mindmap-modal-header">
            <h3 class="mindmap-modal-title">マッププレビュー</h3>
            <button class="mindmap-modal-close">&times;</button>
        </div>
        <div class="mindmap-modal-body">
            <div id="preview-content" style="min-height: 400px;">
                <!-- プレビュー内容がここに表示される -->
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // タブ切り替え
    $('.gyosei-tab-link').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).attr('href');
        
        // タブの切り替え
        $('.gyosei-tab-item').removeClass('active');
        $(this).parent().addClass('active');
        
        // コンテンツの切り替え
        $('.gyosei-tab-content').hide();
        $(target).show();
    });
    
    // ショートコードコピー機能
    $('.copy-shortcode, .btn-copy-shortcode').on('click', function() {
        var shortcode = $(this).data('shortcode');
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(shortcode).then(function() {
                showNotification('ショートコードをコピーしました！', 'success');
            });
        } else {
            // フォールバック
            var textArea = document.createElement('textarea');
            textArea.value = shortcode;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showNotification('ショートコードをコピーしました！', 'success');
        }
    });
    
    // 削除機能
    $('.delete-mindmap').on('click', function() {
        if (confirm('本当に削除しますか？この操作は取り消せません。')) {
            var id = $(this).data('id');
            var row = $(this).closest('tr');
            
            $.post(ajaxurl, {
                action: 'delete_mindmap_data',
                id: id,
                nonce: '<?php echo wp_create_nonce('mindmap_admin_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    row.fadeOut(function() {
                        row.remove();
                    });
                    showNotification('マインドマップが削除されました。', 'success');
                } else {
                    showNotification('削除に失敗しました。', 'error');
                }
            });
        }
    });
    
    // カスタムマップ削除
    $('.btn-delete-custom').on('click', function() {
        if (confirm('このカスタムマップを削除しますか？')) {
            var id = $(this).data('id');
            var card = $(this).closest('.custom-map-card');
            
            $.post(ajaxurl, {
                action: 'delete_custom_map',
                id: id,
                nonce: '<?php echo wp_create_nonce('mindmap_admin_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    card.fadeOut(function() {
                        card.remove();
                    });
                    showNotification('カスタムマップが削除されました。', 'success');
                } else {
                    showNotification('削除に失敗しました。', 'error');
                }
            });
        }
    });
    
    // カスタムマップフィルター
    $('#custom-map-filter').on('change', function() {
        var filter = $(this).val();
        filterCustomMaps(filter, $('#custom-map-search').val());
    });
    
    $('#custom-map-search').on('input', function() {
        var search = $(this).val();
        filterCustomMaps($('#custom-map-filter').val(), search);
    });
    
    function filterCustomMaps(filter, search) {
        $('.custom-map-card').each(function() {
            var card = $(this);
            var show = true;
            
            // フィルター条件をチェック
            if (filter === 'public' && card.data('public') != 1) {
                show = false;
            } else if (filter === 'private' && card.data('public') == 1) {
                show = false;
            } else if (filter === 'template' && card.data('template') != 1) {
                show = false;
            }
            
            // 検索条件をチェック
            if (search && show) {
                var title = card.find('h4').text().toLowerCase();
                var description = card.find('.map-description').text().toLowerCase();
                if (title.indexOf(search.toLowerCase()) === -1 && 
                    description.indexOf(search.toLowerCase()) === -1) {
                    show = false;
                }
            }
            
            if (show) {
                card.show();
            } else {
                card.hide();
            }
        });
    }
    
    // マッププレビュー
    $('.btn-view').on('click', function() {
        var mapId = $(this).data('map-id');
        showMapPreview(mapId);
    });
    
    function showMapPreview(mapId) {
        $('#map-preview-modal').show();
        $('#preview-content').html('<div style="text-align: center; padding: 50px;">読み込み中...</div>');
        
        // プレビューコンテンツを動的に生成
        var shortcode = '[mindmap custom_id="' + mapId + '" details="true"]';
        $('#preview-content').html('<p>ショートコード: <code>' + shortcode + '</code></p><p>このマップをページに表示するには、上記のショートコードを使用してください。</p>');
    }
    
    // モーダルを閉じる
    $('#map-preview-modal .mindmap-modal-close, #map-preview-modal .mindmap-modal-overlay').on('click', function() {
        $('#map-preview-modal').hide();
    });
    
    // 通知表示関数
    function showNotification(message, type) {
        var className = 'notice notice-' + (type === 'error' ? 'error' : 'success');
        var notice = $('<div class="' + className + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.gyosei-admin-container').prepend(notice);
        
        setTimeout(function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        }, 3000);
    }
});
</script>

<style>
/* 管理画面専用スタイル */
.gyosei-admin-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.gyosei-stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-left: 4px solid #3f51b5;
    position: relative;
    text-align: center;
}

.gyosei-stat-number {
    display: block;
    font-size: 2.5em;
    font-weight: bold;
    color: #3f51b5;
    line-height: 1;
}

.gyosei-stat-label {
    display: block;
    margin-top: 8px;
    color: #666;
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.gyosei-stat-icon {
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 24px;
    color: #3f51b5;
    opacity: 0.3;
}

.gyosei-system-status {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 6px;
    background: #f9f9f9;
}

.status-item.active {
    background: #e8f5e8;
    color: #2e7d32;
}

.status-item.inactive {
    background: #ffebee;
    color: #c62828;
}

.custom-maps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.custom-map-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.custom-map-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.shortcode-examples {
    display: grid;
    gap: 20px;
    margin-bottom: 30px;
}

.shortcode-example {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #3f51b5;
}

.code-example {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f5f5f5;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
}

.code-example code {
    flex: 1;
    background: none;
    padding: 0;
}

.parameters-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.parameters-table th,
.parameters-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.parameters-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.help-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.help-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.help-icon {
    font-size: 24px;
    color: #3f51b5;
    margin-top: 5px;
}

.no-items-message {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.no-items-icon {
    font-size: 48px;
    color: #ccc;
    margin-bottom: 20px;
}
</style>_type=gyosei_mindmap'); ?>" class="button button-primary">
                <span class="dashicons dashicons-plus-alt"></span> 新規マップ作成
            </a>
        </div>