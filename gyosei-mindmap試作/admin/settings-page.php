<?php
/**
 * 行政書士の道 - マインドマップ設定ページ
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// 設定の保存処理
if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'gyosei_mindmap_settings')) {
    
    // 基本設定
    update_option('gyosei_mindmap_default_width', sanitize_text_field($_POST['default_width']));
    update_option('gyosei_mindmap_default_height', sanitize_text_field($_POST['default_height']));
    update_option('gyosei_mindmap_enable_search', isset($_POST['enable_search']) ? 1 : 0);
    update_option('gyosei_mindmap_enable_details', isset($_POST['enable_details']) ? 1 : 0);
    update_option('gyosei_mindmap_enable_community', isset($_POST['enable_community']) ? 1 : 0);
    
    // 表示設定
    update_option('gyosei_mindmap_default_theme', sanitize_text_field($_POST['default_theme']));
    update_option('gyosei_mindmap_node_style', sanitize_text_field($_POST['node_style']));
    update_option('gyosei_mindmap_animation_speed', sanitize_text_field($_POST['animation_speed']));
    
    // ユーザー権限設定
    update_option('gyosei_mindmap_allow_custom_maps', isset($_POST['allow_custom_maps']) ? 1 : 0);
    update_option('gyosei_mindmap_allow_public_maps', isset($_POST['allow_public_maps']) ? 1 : 0);
    update_option('gyosei_mindmap_max_custom_maps', intval($_POST['max_custom_maps']));
    
    // コミュニティ設定
    update_option('gyosei_mindmap_enable_rating', isset($_POST['enable_rating']) ? 1 : 0);
    update_option('gyosei_mindmap_enable_comments', isset($_POST['enable_comments']) ? 1 : 0);
    update_option('gyosei_mindmap_moderation_required', isset($_POST['moderation_required']) ? 1 : 0);
    
    // パフォーマンス設定
    update_option('gyosei_mindmap_cache_enabled', isset($_POST['cache_enabled']) ? 1 : 0);
    update_option('gyosei_mindmap_lazy_loading', isset($_POST['lazy_loading']) ? 1 : 0);
    update_option('gyosei_mindmap_cdn_enabled', isset($_POST['cdn_enabled']) ? 1 : 0);
    
    $success_message = '設定が保存されました。';
}

// 現在の設定値を取得
$settings = array(
    'default_width' => get_option('gyosei_mindmap_default_width', '100%'),
    'default_height' => get_option('gyosei_mindmap_default_height', '400px'),
    'enable_search' => get_option('gyosei_mindmap_enable_search', 1),
    'enable_details' => get_option('gyosei_mindmap_enable_details', 1),
    'enable_community' => get_option('gyosei_mindmap_enable_community', 0),
    'default_theme' => get_option('gyosei_mindmap_default_theme', 'light'),
    'node_style' => get_option('gyosei_mindmap_node_style', 'rounded'),
    'animation_speed' => get_option('gyosei_mindmap_animation_speed', 'normal'),
    'allow_custom_maps' => get_option('gyosei_mindmap_allow_custom_maps', 1),
    'allow_public_maps' => get_option('gyosei_mindmap_allow_public_maps', 1),
    'max_custom_maps' => get_option('gyosei_mindmap_max_custom_maps', 10),
    'enable_rating' => get_option('gyosei_mindmap_enable_rating', 1),
    'enable_comments' => get_option('gyosei_mindmap_enable_comments', 1),
    'moderation_required' => get_option('gyosei_mindmap_moderation_required', 0),
    'cache_enabled' => get_option('gyosei_mindmap_cache_enabled', 1),
    'lazy_loading' => get_option('gyosei_mindmap_lazy_loading', 1),
    'cdn_enabled' => get_option('gyosei_mindmap_cdn_enabled', 0)
);
?>

<div class="wrap gyosei-admin-container">
    <div class="gyosei-admin-header">
        <h1>
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; margin-right: 10px;"></span>
            マインドマップ設定
        </h1>
        <div class="header-actions">
            <a href="<?php echo admin_url('admin.php?page=gyosei-mindmap'); ?>" class="button">
                <span class="dashicons dashicons-arrow-left-alt"></span> 管理画面に戻る
            </a>
        </div>
    </div>
    
    <?php if (isset($success_message)): ?>
    <div class="notice notice-success is-dismissible">
        <p><?php echo esc_html($success_message); ?></p>
    </div>
    <?php endif; ?>
    
    <form method="post" class="gyosei-settings-form">
        <?php wp_nonce_field('gyosei_mindmap_settings'); ?>
        
        <!-- 基本設定 -->
        <div class="settings-section">
            <h2>基本設定</h2>
            <p class="section-description">マインドマップの基本的な動作設定を行います。</p>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="default_width">デフォルト幅</label>
                </div>
                <div class="settings-input">
                    <input type="text" id="default_width" name="default_width" 
                           value="<?php echo esc_attr($settings['default_width']); ?>" 
                           placeholder="100%">
                </div>
                <div class="settings-description">
                    マインドマップのデフォルト幅を設定します。（例: 100%, 800px）
                </div>
            </div>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="default_height">デフォルト高さ</label>
                </div>
                <div class="settings-input">
                    <input type="text" id="default_height" name="default_height" 
                           value="<?php echo esc_attr($settings['default_height']); ?>" 
                           placeholder="400px">
                </div>
                <div class="settings-description">
                    マインドマップのデフォルト高さを設定します。（例: 400px, 50vh）
                </div>
            </div>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="enable_search">検索機能</label>
                </div>
                <div class="settings-input">
                    <label class="switch">
                        <input type="checkbox" id="enable_search" name="enable_search" 
                               <?php checked($settings['enable_search'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-description">
                    ノード検索機能をデフォルトで有効にします。
                </div>
            </div>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="enable_details">詳細表示機能</label>
                </div>
                <div class="settings-input">
                    <label class="switch">
                        <input type="checkbox" id="enable_details" name="enable_details" 
                               <?php checked($settings['enable_details'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-description">
                    ノード詳細表示機能をデフォルトで有効にします。
                </div>
            </div>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="enable_community">コミュニティ機能</label>
                </div>
                <div class="settings-input">
                    <label class="switch">
                        <input type="checkbox" id="enable_community" name="enable_community" 
                               <?php checked($settings['enable_community'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-description">
                    評価・コメント機能をサイト全体で有効にします。
                </div>
            </div>
        </div>
        
        <!-- 表示設定 -->
        <div class="settings-section">
            <h2>表示設定</h2>
            <p class="section-description">マインドマップの見た目や動作に関する設定です。</p>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="default_theme">デフォルトテーマ</label>
                </div>
                <div class="settings-input">
                    <select id="default_theme" name="default_theme">
                        <option value="light" <?php selected($settings['default_theme'], 'light'); ?>>ライト</option>
                        <option value="dark" <?php selected($settings['default_theme'], 'dark'); ?>>ダーク</option>
                        <option value="blue" <?php selected($settings['default_theme'], 'blue'); ?>>ブルー</option>
                        <option value="green" <?php selected($settings['default_theme'], 'green'); ?>>グリーン</option>
                    </select>
                </div>
                <div class="settings-description">
                    マインドマップのデフォルトカラーテーマを設定します。
                </div>
            </div>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="node_style">ノードスタイル</label>
                </div>
                <div class="settings-input">
                    <select id="node_style" name="node_style">
                        <option value="rounded" <?php selected($settings['node_style'], 'rounded'); ?>>角丸</option>
                        <option value="square" <?php selected($settings['node_style'], 'square'); ?>>四角</option>
                        <option value="circle" <?php selected($settings['node_style'], 'circle'); ?>>円形</option>
                    </select>
                </div>
                <div class="settings-description">
                    ノードの形状スタイルを設定します。
                </div>
            </div>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="animation_speed">アニメーション速度</label>
                </div>
                <div class="settings-input">
                    <select id="animation_speed" name="animation_speed">
                        <option value="slow" <?php selected($settings['animation_speed'], 'slow'); ?>>遅い</option>
                        <option value="normal" <?php selected($settings['animation_speed'], 'normal'); ?>>標準</option>
                        <option value="fast" <?php selected($settings['animation_speed'], 'fast'); ?>>速い</option>
                        <option value="none" <?php selected($settings['animation_speed'], 'none'); ?>>無効</option>
                    </select>
                </div>
                <div class="settings-description">
                    マインドマップのアニメーション速度を設定します。
                </div>
            </div>
        </div>
        
        <!-- ユーザー権限設定 -->
        <div class="settings-section">
            <h2>ユーザー権限設定</h2>
            <p class="section-description">ユーザーのマインドマップ作成・編集権限を設定します。</p>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="allow_custom_maps">カスタムマップ作成</label>
                </div>
                <div class="settings-input">
                    <label class="switch">
                        <input type="checkbox" id="allow_custom_maps" name="allow_custom_maps" 
                               <?php checked($settings['allow_custom_maps'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-description">
                    ログインユーザーのカスタムマップ作成を許可します。
                </div>
            </div>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="allow_public_maps">公開マップ作成</label>
                </div>
                <div class="settings-input">
                    <label class="switch">
                        <input type="checkbox" id="allow_public_maps" name="allow_public_maps" 
                               <?php checked($settings['allow_public_maps'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-description">
                    ユーザーが作成したマップの公開を許可します。
                </div>
            </div>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="max_custom_maps">最大作成数</label>
                </div>
                <div class="settings-input">
                    <input type="number" id="max_custom_maps" name="max_custom_maps" 
                           value="<?php echo esc_attr($settings['max_custom_maps']); ?>" 
                           min="1" max="100">
                </div>
                <div class="settings-description">
                    1ユーザーあたりの最大カスタムマップ作成数を設定します。
                </div>
            </div>
        </div>
        
        <!-- コミュニティ設定 -->
        <div class="settings-section">
            <h2>コミュニティ設定</h2>
            <p class="section-description">評価・コメント機能の詳細設定です。</p>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="enable_rating">評価機能</label>
                </div>
                <div class="settings-input">
                    <label class="switch">
                        <input type="checkbox" id="enable_rating" name="enable_rating" 
                               <?php checked($settings['enable_rating'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-description">
                    マップの星評価機能を有効にします。
                </div>
            </div>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="enable_comments">コメント機能</label>
                </div>
                <div class="settings-input">
                    <label class="switch">
                        <input type="checkbox" id="enable_comments" name="enable_comments" 
                               <?php checked($settings['enable_comments'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-description">
                    マップへのコメント機能を有効にします。
                </div>
            </div>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="moderation_required">モデレーション</label>
                </div>
                <div class="settings-input">
                    <label class="switch">
                        <input type="checkbox" id="moderation_required" name="moderation_required" 
                               <?php checked($settings['moderation_required'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-description">
                    コメントの事前承認を必要とします。
                </div>
            </div>
        </div>
        
        <!-- パフォーマンス設定 -->
        <div class="settings-section">
            <h2>パフォーマンス設定</h2>
            <p class="section-description">サイトのパフォーマンス向上に関する設定です。</p>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="cache_enabled">キャッシュ機能</label>
                </div>
                <div class="settings-input">
                    <label class="switch">
                        <input type="checkbox" id="cache_enabled" name="cache_enabled" 
                               <?php checked($settings['cache_enabled'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-description">
                    マインドマップデータのキャッシュを有効にします。
                </div>
            </div>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="lazy_loading">遅延読み込み</label>
                </div>
                <div class="settings-input">
                    <label class="switch">
                        <input type="checkbox" id="lazy_loading" name="lazy_loading" 
                               <?php checked($settings['lazy_loading'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-description">
                    マインドマップの遅延読み込みを有効にします。
                </div>
            </div>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="cdn_enabled">CDN使用</label>
                </div>
                <div class="settings-input">
                    <label class="switch">
                        <input type="checkbox" id="cdn_enabled" name="cdn_enabled" 
                               <?php checked($settings['cdn_enabled'], 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-description">
                    外部CDNからのライブラリ読み込みを有効にします。
                </div>
            </div>
        </div>
        
        <!-- 詳細設定 -->
        <div class="settings-section">
            <h2>詳細設定</h2>
            <p class="section-description">高度な設定オプションです。</p>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="debug_mode">デバッグモード</label>
                </div>
                <div class="settings-input">
                    <label class="switch">
                        <input type="checkbox" id="debug_mode" name="debug_mode" 
                               <?php checked(get_option('gyosei_mindmap_debug_mode', 0), 1); ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="settings-description">
                    開発者向けのデバッグ情報を表示します。
                </div>
            </div>
            
            <div class="settings-row">
                <div class="settings-label">
                    <label for="custom_css">カスタムCSS</label>
                </div>
                <div class="settings-input">
                    <textarea id="custom_css" name="custom_css" rows="8" 
                              placeholder="/* カスタムCSS */&#10;.mindmap-container {&#10;    /* スタイルを追加 */&#10;}"><?php echo esc_textarea(get_option('gyosei_mindmap_custom_css', '')); ?></textarea>
                </div>
                <div class="settings-description">
                    マインドマップ用の追加CSSを記述できます。
                </div>
            </div>
        </div>
        
        <!-- データ管理 -->
        <div class="settings-section">
            <h2>データ管理</h2>
            <p class="section-description">プラグインデータの管理・メンテナンス機能です。</p>
            
            <div class="data-management-actions">
                <div class="action-item">
                    <h4>データのエクスポート</h4>
                    <p>すべてのマインドマップデータを一括でエクスポートします。</p>
                    <button type="button" id="export-data" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span> データをエクスポート
                    </button>
                </div>
                
                <div class="action-item">
                    <h4>キャッシュのクリア</h4>
                    <p>マインドマップのキャッシュデータをクリアします。</p>
                    <button type="button" id="clear-cache" class="button button-secondary">
                        <span class="dashicons dashicons-update"></span> キャッシュをクリア
                    </button>
                </div>
                
                <div class="action-item">
                    <h4>データベースの最適化</h4>
                    <p>マインドマップ関連のデータベーステーブルを最適化します。</p>
                    <button type="button" id="optimize-db" class="button button-secondary">
                        <span class="dashicons dashicons-performance"></span> データベース最適化
                    </button>
                </div>
            </div>
        </div>
        
        <!-- 保存ボタン -->
        <div class="settings-submit">
            <input type="submit" name="submit" class="button-primary" value="設定を保存">
            <button type="button" id="reset-settings" class="button button-secondary">
                <span class="dashicons dashicons-undo"></span> デフォルトに戻す
            </button>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    
    // データエクスポート
    $('#export-data').on('click', function() {
        const button = $(this);
        const originalText = button.html();
        
        button.html('<span class="dashicons dashicons-update spin"></span> エクスポート中...');
        button.prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'export_mindmap_data',
            nonce: '<?php echo wp_create_nonce('gyosei_mindmap_admin'); ?>'
        }, function(response) {
            if (response.success) {
                // ダウンロードリンクを作成
                const dataStr = JSON.stringify(response.data, null, 2);
                const dataBlob = new Blob([dataStr], {type: 'application/json'});
                const url = URL.createObjectURL(dataBlob);
                
                const link = document.createElement('a');
                link.href = url;
                link.download = 'mindmap-data-' + new Date().toISOString().slice(0,10) + '.json';
                link.click();
                
                URL.revokeObjectURL(url);
                showNotification('データのエクスポートが完了しました。', 'success');
            } else {
                showNotification('エクスポートに失敗しました。', 'error');
            }
        }).always(function() {
            button.html(originalText);
            button.prop('disabled', false);
        });
    });
    
    // キャッシュクリア
    $('#clear-cache').on('click', function() {
        if (confirm('キャッシュをクリアしますか？')) {
            const button = $(this);
            const originalText = button.html();
            
            button.html('<span class="dashicons dashicons-update spin"></span> クリア中...');
            button.prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'clear_mindmap_cache',
                nonce: '<?php echo wp_create_nonce('gyosei_mindmap_admin'); ?>'
            }, function(response) {
                if (response.success) {
                    showNotification('キャッシュをクリアしました。', 'success');
                } else {
                    showNotification('キャッシュのクリアに失敗しました。', 'error');
                }
            }).always(function() {
                button.html(originalText);
                button.prop('disabled', false);
            });
        }
    });
    
    // データベース最適化
    $('#optimize-db').on('click', function() {
        if (confirm('データベースを最適化しますか？')) {
            const button = $(this);
            const originalText = button.html();
            
            button.html('<span class="dashicons dashicons-update spin"></span> 最適化中...');
            button.prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'optimize_mindmap_db',
                nonce: '<?php echo wp_create_nonce('gyosei_mindmap_admin'); ?>'
            }, function(response) {
                if (response.success) {
                    showNotification('データベースの最適化が完了しました。', 'success');
                } else {
                    showNotification('最適化に失敗しました。', 'error');
                }
            }).always(function() {
                button.html(originalText);
                button.prop('disabled', false);
            });
        }
    });
    
    // 設定リセット
    $('#reset-settings').on('click', function() {
        if (confirm('設定をデフォルトに戻しますか？この操作は取り消せません。')) {
            // デフォルト値にリセット
            $('#default_width').val('100%');
            $('#default_height').val('400px');
            $('#enable_search').prop('checked', true);
            $('#enable_details').prop('checked', true);
            $('#enable_community').prop('checked', false);
            $('#default_theme').val('light');
            $('#node_style').val('rounded');
            $('#animation_speed').val('normal');
            $('#allow_custom_maps').prop('checked', true);
            $('#allow_public_maps').prop('checked', true);
            $('#max_custom_maps').val(10);
            $('#enable_rating').prop('checked', true);
            $('#enable_comments').prop('checked', true);
            $('#moderation_required').prop('checked', false);
            $('#cache_enabled').prop('checked', true);
            $('#lazy_loading').prop('checked', true);
            $('#cdn_enabled').prop('checked', false);
            $('#debug_mode').prop('checked', false);
            $('#custom_css').val('');
            
            showNotification('設定をデフォルトに戻しました。保存ボタンをクリックしてください。', 'info');
        }
    });
    
    // 通知表示関数
    function showNotification(message, type) {
        const className = 'notice notice-' + (type === 'error' ? 'error' : (type === 'info' ? 'info' : 'success'));
        const notice = $('<div class="' + className + ' is-dismissible"><p>' + message + '</p></div>');
        
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
/* 設定ページ専用スタイル */
.gyosei-settings-form {
    max-width: 1000px;
}

.settings-section {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 25px;
}

.settings-section h2 {
    margin: 0 0 15px 0;
    color: #3f51b5;
    border-bottom: 2px solid #e8eaf6;
    padding-bottom: 10px;
}

.section-description {
    margin-bottom: 20px;
    color: #666;
    font-style: italic;
}

.settings-row {
    display: grid;
    grid-template-columns: 200px 1fr 300px;
    gap: 20px;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.settings-label {
    font-weight: 600;
    color: #333;
}

.settings-input input,
.settings-input select,
.settings-input textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.settings-input textarea {
    resize: vertical;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 13px;
    line-height: 1.4;
}

.settings-description {
    font-size: 12px;
    color: #666;
    line-height: 1.4;
}

/* スイッチトグル */
.switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #3f51b5;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

/* データ管理セクション */
.data-management-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.action-item {
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
}

.action-item h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.action-item p {
    margin: 0 0 15px 0;
    font-size: 14px;
    color: #666;
}

/* 保存ボタンエリア */
.settings-submit {
    background: white;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    text-align: center;
    display: flex;
    gap: 15px;
    justify-content: center;
}

/* スピンアニメーション */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.spin {
    animation: spin 1s linear infinite;
}

/* レスポンシブ対応 */
@media (max-width: 768px) {
    .settings-row {
        grid-template-columns: 1fr;
        text-align: left;
    }
    
    .settings-input {
        margin: 10px 0;
    }
    
    .settings-description {
        margin-top: 5px;
    }
    
    .data-management-actions {
        grid-template-columns: 1fr;
    }
    
    .settings-submit {
        flex-direction: column;
        align-items: center;
    }
}
</style>