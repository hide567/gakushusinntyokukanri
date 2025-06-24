<?php
/**
 * 行政書士の道 - 設定ページ
 * File: includes/admin-settings-page.php
 */

if (!defined('ABSPATH')) {
    exit;
}

function gyosei_mindmap_admin_settings_page() {
    // 権限チェック
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // 設定保存処理
    $message = '';
    $error = '';
    
    if (isset($_POST['submit'])) {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'gyosei_settings_nonce')) {
            $error = 'セキュリティチェックに失敗しました。';
        } else {
            $settings = array(
                'community_enabled' => isset($_POST['community_enabled']),
                'ai_assistant_enabled' => isset($_POST['ai_assistant_enabled']),
                'analytics_enabled' => isset($_POST['analytics_enabled']),
                'public_maps_enabled' => isset($_POST['public_maps_enabled']),
                'auto_save_interval' => intval($_POST['auto_save_interval']),
                'default_theme' => sanitize_text_field($_POST['default_theme']),
                'max_maps_per_user' => intval($_POST['max_maps_per_user']),
                'max_nodes_per_map' => intval($_POST['max_nodes_per_map']),
                'allow_map_sharing' => isset($_POST['allow_map_sharing']),
                'require_approval' => isset($_POST['require_approval']),
                'default_map_category' => sanitize_text_field($_POST['default_map_category']),
                'cache_enabled' => isset($_POST['cache_enabled']),
                'cache_duration' => intval($_POST['cache_duration']),
                'email_notifications' => isset($_POST['email_notifications']),
                'admin_email_notifications' => isset($_POST['admin_email_notifications']),
                'google_analytics_id' => sanitize_text_field($_POST['google_analytics_id']),
                'custom_css' => wp_strip_all_tags($_POST['custom_css']),
                'advanced_features' => isset($_POST['advanced_features']),
                'debug_mode' => isset($_POST['debug_mode']),
                'data_retention_days' => intval($_POST['data_retention_days']),
                'backup_enabled' => isset($_POST['backup_enabled']),
                'backup_frequency' => sanitize_text_field($_POST['backup_frequency'])
            );
            
            $saved = 0;
            foreach ($settings as $key => $value) {
                if (update_option("gyosei_mindmap_{$key}", $value)) {
                    $saved++;
                }
            }
            
            // カテゴリー設定の保存
            if (isset($_POST['categories'])) {
                $categories = array();
                foreach ($_POST['categories'] as $cat) {
                    if (!empty($cat['name'])) {
                        $categories[] = array(
                            'key' => sanitize_key($cat['key']),
                            'name' => sanitize_text_field($cat['name']),
                            'color' => sanitize_hex_color($cat['color']),
                            'enabled' => isset($cat['enabled'])
                        );
                    }
                }
                update_option('gyosei_mindmap_categories', $categories);
            }
            
            // ロール設定の保存
            if (isset($_POST['role_permissions'])) {
                $permissions = array();
                foreach ($_POST['role_permissions'] as $role => $perms) {
                    $permissions[$role] = array(
                        'create_maps' => isset($perms['create_maps']),
                        'edit_own_maps' => isset($perms['edit_own_maps']),
                        'edit_all_maps' => isset($perms['edit_all_maps']),
                        'delete_own_maps' => isset($perms['delete_own_maps']),
                        'delete_all_maps' => isset($perms['delete_all_maps']),
                        'publish_maps' => isset($perms['publish_maps']),
                        'manage_community' => isset($perms['manage_community']),
                        'access_analytics' => isset($perms['access_analytics'])
                    );
                }
                update_option('gyosei_mindmap_role_permissions', $permissions);
            }
            
            $message = '設定を保存しました。';
            
            // キャッシュクリア
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
        }
    }
    
    // データリセット処理
    if (isset($_POST['reset_data'])) {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'gyosei_reset_nonce')) {
            $error = 'セキュリティチェックに失敗しました。';
        } else {
            if (gyosei_reset_plugin_data()) {
                $message = 'データをリセットしました。';
            } else {
                $error = 'データのリセットに失敗しました。';
            }
        }
    }
    
    // 現在の設定取得
    $current_settings = gyosei_get_all_settings();
    $categories = get_option('gyosei_mindmap_categories', gyosei_get_default_categories());
    $role_permissions = get_option('gyosei_mindmap_role_permissions', gyosei_get_default_permissions());
    
    ?>
    <div class="wrap gyosei-admin-container">
        <div class="gyosei-admin-header">
            <h1>マインドマップ設定</h1>
            <div class="header-actions">
                <button type="button" class="button" id="export-settings">設定エクスポート</button>
                <button type="button" class="button" id="import-settings">設定インポート</button>
                <input type="file" id="import-file" accept=".json" style="display: none;">
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($error); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="gyosei-tabs">
            <ul class="gyosei-tab-nav">
                <li class="gyosei-tab-item active">
                    <a href="#general-settings" class="gyosei-tab-link">一般設定</a>
                </li>
                <li class="gyosei-tab-item">
                    <a href="#feature-settings" class="gyosei-tab-link">機能設定</a>
                </li>
                <li class="gyosei-tab-item">
                    <a href="#category-settings" class="gyosei-tab-link">カテゴリ管理</a>
                </li>
                <li class="gyosei-tab-item">
                    <a href="#permission-settings" class="gyosei-tab-link">権限設定</a>
                </li>
                <li class="gyosei-tab-item">
                    <a href="#performance-settings" class="gyosei-tab-link">パフォーマンス</a>
                </li>
                <li class="gyosei-tab-item">
                    <a href="#advanced-settings" class="gyosei-tab-link">高度な設定</a>
                </li>
            </ul>
        </div>
        
        <form method="post" action="" id="settings-form">
            <?php wp_nonce_field('gyosei_settings_nonce'); ?>
            
            <!-- 一般設定 -->
            <div id="general-settings" class="gyosei-tab-content active">
                <div class="settings-section">
                    <h3>基本設定</h3>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="default_theme">デフォルトテーマ</label>
                        </div>
                        <div class="settings-input">
                            <select name="default_theme" id="default_theme">
                                <option value="default" <?php selected($current_settings['default_theme'], 'default'); ?>>デフォルト</option>
                                <option value="dark" <?php selected($current_settings['default_theme'], 'dark'); ?>>ダーク</option>
                                <option value="light" <?php selected($current_settings['default_theme'], 'light'); ?>>ライト</option>
                                <option value="colorful" <?php selected($current_settings['default_theme'], 'colorful'); ?>>カラフル</option>
                            </select>
                        </div>
                        <div class="settings-description">
                            マインドマップのデフォルトテーマを設定します。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="default_map_category">デフォルトカテゴリ</label>
                        </div>
                        <div class="settings-input">
                            <select name="default_map_category" id="default_map_category">
                                <option value="custom" <?php selected($current_settings['default_map_category'], 'custom'); ?>>カスタム</option>
                                <option value="gyosei" <?php selected($current_settings['default_map_category'], 'gyosei'); ?>>行政法</option>
                                <option value="minpo" <?php selected($current_settings['default_map_category'], 'minpo'); ?>>民法</option>
                                <option value="kenpou" <?php selected($current_settings['default_map_category'], 'kenpou'); ?>>憲法</option>
                                <option value="shoken" <?php selected($current_settings['default_map_category'], 'shoken'); ?>>商法・会社法</option>
                                <option value="general" <?php selected($current_settings['default_map_category'], 'general'); ?>>一般知識</option>
                            </select>
                        </div>
                        <div class="settings-description">
                            新規マップ作成時のデフォルトカテゴリです。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="auto_save_interval">自動保存間隔（秒）</label>
                        </div>
                        <div class="settings-input">
                            <input type="number" name="auto_save_interval" id="auto_save_interval" 
                                   value="<?php echo esc_attr($current_settings['auto_save_interval']); ?>" 
                                   min="10" max="600" class="auto-save">
                        </div>
                        <div class="settings-description">
                            ユーザーの進捗データを自動保存する間隔を設定します。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="max_maps_per_user">ユーザー当たりの最大マップ数</label>
                        </div>
                        <div class="settings-input">
                            <input type="number" name="max_maps_per_user" id="max_maps_per_user" 
                                   value="<?php echo esc_attr($current_settings['max_maps_per_user']); ?>" 
                                   min="1" max="1000">
                        </div>
                        <div class="settings-description">
                            一人のユーザーが作成できるマップの最大数です。0で無制限。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="max_nodes_per_map">マップ当たりの最大ノード数</label>
                        </div>
                        <div class="settings-input">
                            <input type="number" name="max_nodes_per_map" id="max_nodes_per_map" 
                                   value="<?php echo esc_attr($current_settings['max_nodes_per_map']); ?>" 
                                   min="10" max="10000">
                        </div>
                        <div class="settings-description">
                            一つのマップに含められるノードの最大数です。
                        </div>
                    </div>
                </div>
                
                <div class="settings-section">
                    <h3>通知設定</h3>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="email_notifications">メール通知</label>
                        </div>
                        <div class="settings-input">
                            <label>
                                <input type="checkbox" name="email_notifications" id="email_notifications" 
                                       <?php checked($current_settings['email_notifications']); ?>>
                                ユーザーへのメール通知を有効にする
                            </label>
                        </div>
                        <div class="settings-description">
                            新しいコメントやバッジ獲得時にメール通知を送信します。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="admin_email_notifications">管理者メール通知</label>
                        </div>
                        <div class="settings-input">
                            <label>
                                <input type="checkbox" name="admin_email_notifications" id="admin_email_notifications" 
                                       <?php checked($current_settings['admin_email_notifications']); ?>>
                                管理者への通知を有効にする
                            </label>
                        </div>
                        <div class="settings-description">
                            新規マップ投稿や報告があった際に管理者にメール通知を送信します。
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 機能設定 -->
            <div id="feature-settings" class="gyosei-tab-content">
                <div class="settings-section">
                    <h3>機能の有効/無効</h3>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="community_enabled">コミュニティ機能</label>
                        </div>
                        <div class="settings-input">
                            <label>
                                <input type="checkbox" name="community_enabled" id="community_enabled" 
                                       <?php checked($current_settings['community_enabled']); ?>>
                                コミュニティ機能を有効にする
                            </label>
                        </div>
                        <div class="settings-description">
                            マップ共有、コメント、いいね機能などを有効にします。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="ai_assistant_enabled">AI学習支援</label>
                        </div>
                        <div class="settings-input">
                            <label>
                                <input type="checkbox" name="ai_assistant_enabled" id="ai_assistant_enabled" 
                                       <?php checked($current_settings['ai_assistant_enabled']); ?>>
                                AI学習支援機能を有効にする
                            </label>
                        </div>
                        <div class="settings-description">
                            弱点分析、学習計画自動生成、AIチャットボット機能を有効にします。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="analytics_enabled">分析機能</label>
                        </div>
                        <div class="settings-input">
                            <label>
                                <input type="checkbox" name="analytics_enabled" id="analytics_enabled" 
                                       <?php checked($current_settings['analytics_enabled']); ?>>
                                学習分析機能を有効にする
                            </label>
                        </div>
                        <div class="settings-description">
                            進捗分析、統計レポート、バッジシステムを有効にします。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="public_maps_enabled">公開マップ</label>
                        </div>
                        <div class="settings-input">
                            <label>
                                <input type="checkbox" name="public_maps_enabled" id="public_maps_enabled" 
                                       <?php checked($current_settings['public_maps_enabled']); ?>>
                                マップの公開機能を有効にする
                            </label>
                        </div>
                        <div class="settings-description">
                            ユーザーが作成したマップを他のユーザーに公開できるようにします。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="allow_map_sharing">マップ共有</label>
                        </div>
                        <div class="settings-input">
                            <label>
                                <input type="checkbox" name="allow_map_sharing" id="allow_map_sharing" 
                                       <?php checked($current_settings['allow_map_sharing']); ?>>
                                マップの共有とエクスポートを許可する
                            </label>
                        </div>
                        <div class="settings-description">
                            ユーザーがマップをダウンロードや外部共有できるようにします。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="require_approval">承認制</label>
                        </div>
                        <div class="settings-input">
                            <label>
                                <input type="checkbox" name="require_approval" id="require_approval" 
                                       <?php checked($current_settings['require_approval']); ?>>
                                公開マップに管理者承認を必要とする
                            </label>
                        </div>
                        <div class="settings-description">
                            ユーザーが公開したマップを管理者が承認してから表示されるようにします。
                        </div>
                    </div>
                </div>
                
                <div class="settings-section">
                    <h3>外部連携</h3>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="google_analytics_id">Google Analytics ID</label>
                        </div>
                        <div class="settings-input">
                            <input type="text" name="google_analytics_id" id="google_analytics_id" 
                                   value="<?php echo esc_attr($current_settings['google_analytics_id']); ?>" 
                                   placeholder="GA4-XXXXXXXXXX">
                        </div>
                        <div class="settings-description">
                            Google AnalyticsのトラッキングIDを設定すると、詳細なアクセス解析が可能になります。
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- カテゴリ管理 -->
            <div id="category-settings" class="gyosei-tab-content">
                <div class="category-management">
                    <div class="settings-section">
                        <h3>カテゴリ管理</h3>
                        <p>マインドマップのカテゴリを管理します。ドラッグ&ドロップで順序を変更できます。</p>
                        
                        <div class="category-actions">
                            <button type="button" class="button button-primary" id="add-category">新しいカテゴリを追加</button>
                            <button type="button" class="button" id="reset-categories">デフォルトに戻す</button>
                        </div>
                        
                        <div id="category-list" class="category-grid">
                            <?php foreach ($categories as $index => $category): ?>
                            <div class="category-card" data-index="<?php echo $index; ?>">
                                <div class="category-header" style="background: <?php echo esc_attr($category['color']); ?>">
                                    <h3><?php echo esc_html($category['name']); ?></h3>
                                    <div class="category-count">
                                        <?php echo gyosei_get_category_count($category['key']); ?>
                                    </div>
                                </div>
                                <div class="category-body">
                                    <input type="hidden" name="categories[<?php echo $index; ?>][key]" value="<?php echo esc_attr($category['key']); ?>">
                                    
                                    <label>カテゴリ名</label>
                                    <input type="text" name="categories[<?php echo $index; ?>][name]" 
                                           value="<?php echo esc_attr($category['name']); ?>" required>
                                    
                                    <label>色</label>
                                    <input type="color" name="categories[<?php echo $index; ?>][color]" 
                                           value="<?php echo esc_attr($category['color']); ?>" class="color-picker">
                                    
                                    <div class="category-actions">
                                        <label>
                                            <input type="checkbox" name="categories[<?php echo $index; ?>][enabled]" 
                                                   <?php checked($category['enabled']); ?>>
                                            有効
                                        </label>
                                        <button type="button" class="button button-link-delete remove-category">削除</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 権限設定 -->
            <div id="permission-settings" class="gyosei-tab-content">
                <div class="settings-section">
                    <h3>ユーザー権限設定</h3>
                    <p>各ユーザーロールの権限を設定します。</p>
                    
                    <?php
                    $roles = get_editable_roles();
                    foreach ($roles as $role_key => $role_info):
                        $permissions = $role_permissions[$role_key] ?? array();
                    ?>
                    <div class="role-permission-section">
                        <h4><?php echo esc_html($role_info['name']); ?></h4>
                        <div class="permission-grid">
                            <label>
                                <input type="checkbox" name="role_permissions[<?php echo $role_key; ?>][create_maps]" 
                                       <?php checked($permissions['create_maps'] ?? false); ?>>
                                マップ作成
                            </label>
                            <label>
                                <input type="checkbox" name="role_permissions[<?php echo $role_key; ?>][edit_own_maps]" 
                                       <?php checked($permissions['edit_own_maps'] ?? false); ?>>
                                自分のマップ編集
                            </label>
                            <label>
                                <input type="checkbox" name="role_permissions[<?php echo $role_key; ?>][edit_all_maps]" 
                                       <?php checked($permissions['edit_all_maps'] ?? false); ?>>
                                全マップ編集
                            </label>
                            <label>
                                <input type="checkbox" name="role_permissions[<?php echo $role_key; ?>][delete_own_maps]" 
                                       <?php checked($permissions['delete_own_maps'] ?? false); ?>>
                                自分のマップ削除
                            </label>
                            <label>
                                <input type="checkbox" name="role_permissions[<?php echo $role_key; ?>][delete_all_maps]" 
                                       <?php checked($permissions['delete_all_maps'] ?? false); ?>>
                                全マップ削除
                            </label>
                            <label>
                                <input type="checkbox" name="role_permissions[<?php echo $role_key; ?>][publish_maps]" 
                                       <?php checked($permissions['publish_maps'] ?? false); ?>>
                                マップ公開
                            </label>
                            <label>
                                <input type="checkbox" name="role_permissions[<?php echo $role_key; ?>][manage_community]" 
                                       <?php checked($permissions['manage_community'] ?? false); ?>>
                                コミュニティ管理
                            </label>
                            <label>
                                <input type="checkbox" name="role_permissions[<?php echo $role_key; ?>][access_analytics]" 
                                       <?php checked($permissions['access_analytics'] ?? false); ?>>
                                分析機能アクセス
                            </label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- パフォーマンス設定 -->
            <div id="performance-settings" class="gyosei-tab-content">
                <div class="settings-section">
                    <h3>キャッシュ設定</h3>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="cache_enabled">キャッシュ</label>
                        </div>
                        <div class="settings-input">
                            <label>
                                <input type="checkbox" name="cache_enabled" id="cache_enabled" 
                                       <?php checked($current_settings['cache_enabled']); ?>>
                                マインドマップのキャッシュを有効にする
                            </label>
                        </div>
                        <div class="settings-description">
                            パフォーマンス向上のためマップデータをキャッシュします。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="cache_duration">キャッシュ保持期間（時間）</label>
                        </div>
                        <div class="settings-input">
                            <input type="number" name="cache_duration" id="cache_duration" 
                                   value="<?php echo esc_attr($current_settings['cache_duration']); ?>" 
                                   min="1" max="168">
                        </div>
                        <div class="settings-description">
                            キャッシュを保持する時間を設定します。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            キャッシュ管理
                        </div>
                        <div class="settings-input">
                            <button type="button" class="button" id="clear-cache">キャッシュをクリア</button>
                            <button type="button" class="button" id="preload-cache">キャッシュを事前生成</button>
                        </div>
                        <div class="settings-description">
                            キャッシュの手動管理を行います。
                        </div>
                    </div>
                </div>
                
                <div class="settings-section">
                    <h3>データ管理</h3>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="data_retention_days">データ保持期間（日）</label>
                        </div>
                        <div class="settings-input">
                            <input type="number" name="data_retention_days" id="data_retention_days" 
                                   value="<?php echo esc_attr($current_settings['data_retention_days']); ?>" 
                                   min="30" max="3650">
                        </div>
                        <div class="settings-description">
                            学習履歴やログデータを保持する期間です。0で無制限。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="backup_enabled">自動バックアップ</label>
                        </div>
                        <div class="settings-input">
                            <label>
                                <input type="checkbox" name="backup_enabled" id="backup_enabled" 
                                       <?php checked($current_settings['backup_enabled']); ?>>
                                定期的な自動バックアップを有効にする
                            </label>
                        </div>
                        <div class="settings-description">
                            マインドマップデータの自動バックアップを行います。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="backup_frequency">バックアップ頻度</label>
                        </div>
                        <div class="settings-input">
                            <select name="backup_frequency" id="backup_frequency">
                                <option value="daily" <?php selected($current_settings['backup_frequency'], 'daily'); ?>>毎日</option>
                                <option value="weekly" <?php selected($current_settings['backup_frequency'], 'weekly'); ?>>毎週</option>
                                <option value="monthly" <?php selected($current_settings['backup_frequency'], 'monthly'); ?>>毎月</option>
                            </select>
                        </div>
                        <div class="settings-description">
                            自動バックアップの実行頻度を設定します。
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 高度な設定 -->
            <div id="advanced-settings" class="gyosei-tab-content">
                <div class="settings-section">
                    <h3>開発者向け設定</h3>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="advanced_features">高度な機能</label>
                        </div>
                        <div class="settings-input">
                            <label>
                                <input type="checkbox" name="advanced_features" id="advanced_features" 
                                       <?php checked($current_settings['advanced_features']); ?>>
                                高度な機能を有効にする
                            </label>
                        </div>
                        <div class="settings-description">
                            実験的機能やデバッグツールへのアクセスを有効にします。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="debug_mode">デバッグモード</label>
                        </div>
                        <div class="settings-input">
                            <label>
                                <input type="checkbox" name="debug_mode" id="debug_mode" 
                                       <?php checked($current_settings['debug_mode']); ?>>
                                デバッグモードを有効にする
                            </label>
                        </div>
                        <div class="settings-description">
                            詳細なログ出力やエラー表示を有効にします。本番環境では無効にしてください。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            <label for="custom_css">カスタムCSS</label>
                        </div>
                        <div class="settings-input">
                            <textarea name="custom_css" id="custom_css" rows="10" class="large-text code"><?php echo esc_textarea($current_settings['custom_css']); ?></textarea>
                        </div>
                        <div class="settings-description">
                            マインドマップのカスタムスタイルを追加できます。
                        </div>
                    </div>
                </div>
                
                <div class="settings-section">
                    <h3>データベース管理</h3>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            システム情報
                        </div>
                        <div class="settings-input">
                            <div class="system-info">
                                <p><strong>プラグインバージョン:</strong> <?php echo GYOSEI_MINDMAP_VERSION; ?></p>
                                <p><strong>WordPressバージョン:</strong> <?php echo get_bloginfo('version'); ?></p>
                                <p><strong>PHPバージョン:</strong> <?php echo phpversion(); ?></p>
                                <p><strong>データベース:</strong> <?php echo $wpdb->db_version(); ?></p>
                                <p><strong>総マップ数:</strong> <?php echo gyosei_get_total_maps(); ?></p>
                                <p><strong>総ユーザー数:</strong> <?php echo gyosei_get_total_users(); ?></p>
                            </div>
                        </div>
                        <div class="settings-description">
                            システムの基本情報を表示します。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            データベース最適化
                        </div>
                        <div class="settings-input">
                            <button type="button" class="button" id="optimize-db">データベースを最適化</button>
                            <button type="button" class="button" id="repair-db">データベースを修復</button>
                        </div>
                        <div class="settings-description">
                            データベースのメンテナンスを実行します。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            データエクスポート/インポート
                        </div>
                        <div class="settings-input">
                            <button type="button" class="button" id="export-data">全データをエクスポート</button>
                            <button type="button" class="button" id="import-data">データをインポート</button>
                            <input type="file" id="import-data-file" accept=".json" style="display: none;">
                        </div>
                        <div class="settings-description">
                            プラグインの全データをバックアップまたは復元します。
                        </div>
                    </div>
                </div>
                
                <div class="settings-section" style="border: 2px solid #dc3545; border-radius: 8px; padding: 20px; background: #fff5f5;">
                    <h3 style="color: #dc3545;">⚠️ 危険な操作</h3>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            データリセット
                        </div>
                        <div class="settings-input">
                            <button type="button" class="button button-link-delete" id="reset-all-data">
                                全データを削除
                            </button>
                        </div>
                        <div class="settings-description" style="color: #dc3545;">
                            <strong>警告:</strong> この操作は取り消せません。全ての学習データ、マインドマップ、設定が削除されます。
                        </div>
                    </div>
                    
                    <div class="settings-row">
                        <div class="settings-label">
                            プラグイン完全削除
                        </div>
                        <div class="settings-input">
                            <button type="button" class="button button-link-delete" id="uninstall-plugin">
                                プラグインを完全削除
                            </button>
                        </div>
                        <div class="settings-description" style="color: #dc3545;">
                            <strong>警告:</strong> プラグインとすべてのデータが完全に削除されます。
                        </div>
                    </div>
                </div>
            </div>
            
            <p class="submit">
                <input type="submit" name="submit" class="button-primary" value="設定を保存">
                <button type="button" class="button" id="reset-to-defaults">デフォルトに戻す</button>
            </p>
        </form>
        
        <!-- データリセット確認モーダル -->
        <div id="reset-confirm-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header" style="background: #dc3545; color: white;">
                    <h3>⚠️ データリセット確認</h3>
                    <span class="close">&times;</span>
                </div>
                <div class="modal-body">
                    <p><strong>本当にすべてのデータを削除しますか？</strong></p>
                    <p>この操作により以下のデータが完全に削除されます：</p>
                    <ul>
                        <li>すべてのマインドマップ</li>
                        <li>ユーザーの学習進捗</li>
                        <li>コメントといいね</li>
                        <li>バッジと実績</li>
                        <li>学習セッション履歴</li>
                        <li>AIチャット履歴</li>
                    </ul>
                    <p style="color: #dc3545;"><strong>この操作は取り消せません。</strong></p>
                    
                    <form method="post" action="" id="reset-data-form">
                        <?php wp_nonce_field('gyosei_reset_nonce'); ?>
                        <input type="hidden" name="reset_data" value="1">
                        
                        <div style="margin: 20px 0;">
                            <label>
                                <input type="checkbox" id="confirm-reset" required>
                                上記の内容を理解し、データの削除に同意します
                            </label>
                        </div>
                        
                        <div style="margin: 20px 0;">
                            <label>確認のため「削除」と入力してください:</label>
                            <input type="text" id="delete-confirmation" placeholder="削除" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="button" id="cancel-reset">キャンセル</button>
                    <button type="button" class="button button-link-delete" id="confirm-reset-btn" disabled>削除実行</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // 設定ページ初期化
        initSettingsPage();
    });
    </script>
    
    <?php
}

// ヘルパー関数
function gyosei_get_all_settings() {
    $defaults = array(
        'community_enabled' => true,
        'ai_assistant_enabled' => true,
        'analytics_enabled' => true,
        'public_maps_enabled' => true,
        'auto_save_interval' => 30,
        'default_theme' => 'default',
        'max_maps_per_user' => 100,
        'max_nodes_per_map' => 1000,
        'allow_map_sharing' => true,
        'require_approval' => false,
        'default_map_category' => 'custom',
        'cache_enabled' => true,
        'cache_duration' => 24,
        'email_notifications' => true,
        'admin_email_notifications' => true,
        'google_analytics_id' => '',
        'custom_css' => '',
        'advanced_features' => false,
        'debug_mode' => false,
        'data_retention_days' => 365,
        'backup_enabled' => false,
        'backup_frequency' => 'weekly'
    );
    
    $settings = array();
    foreach ($defaults as $key => $default) {
        $settings[$key] = get_option("gyosei_mindmap_{$key}", $default);
    }
    
    return $settings;
}

function gyosei_get_default_categories() {
    return array(
        array(
            'key' => 'gyosei',
            'name' => '行政法',
            'color' => '#3f51b5',
            'enabled' => true
        ),
        array(
            'key' => 'minpo',
            'name' => '民法',
            'color' => '#e91e63',
            'enabled' => true
        ),
        array(
            'key' => 'kenpou',
            'name' => '憲法',
            'color' => '#ff9800',
            'enabled' => true
        ),
        array(
            'key' => 'shoken',
            'name' => '商法・会社法',
            'color' => '#4caf50',
            'enabled' => true
        ),
        array(
            'key' => 'general',
            'name' => '一般知識',
            'color' => '#9c27b0',
            'enabled' => true
        ),
        array(
            'key' => 'custom',
            'name' => 'カスタム',
            'color' => '#607d8b',
            'enabled' => true
        )
    );
}

function gyosei_get_default_permissions() {
    return array(
        'administrator' => array(
            'create_maps' => true,
            'edit_own_maps' => true,
            'edit_all_maps' => true,
            'delete_own_maps' => true,
            'delete_all_maps' => true,
            'publish_maps' => true,
            'manage_community' => true,
            'access_analytics' => true
        ),
        'editor' => array(
            'create_maps' => true,
            'edit_own_maps' => true,
            'edit_all_maps' => false,
            'delete_own_maps' => true,
            'delete_all_maps' => false,
            'publish_maps' => true,
            'manage_community' => true,
            'access_analytics' => true
        ),
        'subscriber' => array(
            'create_maps' => true,
            'edit_own_maps' => true,
            'edit_all_maps' => false,
            'delete_own_maps' => true,
            'delete_all_maps' => false,
            'publish_maps' => false,
            'manage_community' => false,
            'access_analytics' => false
        )
    );
}

function gyosei_get_category_count($category_key) {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}gyosei_mindmaps WHERE category = %s",
        $category_key
    ));
}

function gyosei_get_total_maps() {
    global $wpdb;
    return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gyosei_mindmaps");
}

function gyosei_get_total_users() {
    global $wpdb;
    return $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}gyosei_user_progress");
}

function gyosei_reset_plugin_data() {
    global $wpdb;
    
    try {
        // テーブルのクリア
        $tables = array(
            $wpdb->prefix . 'gyosei_mindmaps',
            $wpdb->prefix . 'gyosei_user_progress',
            $wpdb->prefix . 'gyosei_learning_sessions',
            $wpdb->prefix . 'gyosei_user_settings',
            $wpdb->prefix . 'gyosei_map_comments',
            $wpdb->prefix . 'gyosei_map_likes',
            $wpdb->prefix . 'gyosei_user_follows',
            $wpdb->prefix . 'gyosei_study_groups',
            $wpdb->prefix . 'gyosei_group_members',
            $wpdb->prefix . 'gyosei_learning_patterns',
            $wpdb->prefix . 'gyosei_ai_recommendations',
            $wpdb->prefix . 'gyosei_chat_sessions',
            $wpdb->prefix . 'gyosei_weakness_analysis',
            $wpdb->prefix . 'gyosei_analytics',
            $wpdb->prefix . 'gyosei_badges',
            $wpdb->prefix . 'gyosei_user_badges',
            $wpdb->prefix . 'gyosei_achievements'
        );
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table) {
                $wpdb->query("TRUNCATE TABLE {$table}");
            }
        }
        
        // オプションの削除
        $options = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'gyosei_mindmap_%'"
        );
        
        foreach ($options as $option) {
            delete_option($option->option_name);
        }
        
        // キャッシュクリア
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>