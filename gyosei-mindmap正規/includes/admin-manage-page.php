<?php
/**
 * 行政書士の道 - マインドマップ管理ページ
 * File: includes/admin-manage-page.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// マインドマップ管理ページのメイン実装
function gyosei_mindmap_admin_manage_page() {
    global $wpdb;
    
    // 権限チェック
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // アクション処理
    $message = '';
    $error = '';
    
    if (isset($_POST['action'])) {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'gyosei_manage_action')) {
            $error = 'セキュリティチェックに失敗しました。';
        } else {
            switch ($_POST['action']) {
                case 'delete_mindmap':
                    $map_id = intval($_POST['map_id']);
                    if (gyosei_delete_mindmap_admin($map_id)) {
                        $message = 'マインドマップを削除しました。';
                    } else {
                        $error = 'マインドマップの削除に失敗しました。';
                    }
                    break;
                    
                case 'toggle_public':
                    $map_id = intval($_POST['map_id']);
                    $is_public = intval($_POST['is_public']);
                    if (gyosei_toggle_map_public($map_id, $is_public)) {
                        $message = '公開設定を更新しました。';
                    } else {
                        $error = '公開設定の更新に失敗しました。';
                    }
                    break;
                    
                case 'bulk_delete':
                    $map_ids = array_map('intval', $_POST['map_ids'] ?? array());
                    $deleted = 0;
                    foreach ($map_ids as $map_id) {
                        if (gyosei_delete_mindmap_admin($map_id)) {
                            $deleted++;
                        }
                    }
                    $message = "{$deleted}個のマインドマップを削除しました。";
                    break;
                    
                case 'import_template':
                    $result = gyosei_import_template_maps();
                    if ($result) {
                        $message = 'テンプレートマップをインポートしました。';
                    } else {
                        $error = 'テンプレートのインポートに失敗しました。';
                    }
                    break;
            }
        }
    }
    
    // 現在のページ
    $current_page = intval($_GET['paged'] ?? 1);
    $per_page = 20;
    $offset = ($current_page - 1) * $per_page;
    
    // フィルター
    $search = sanitize_text_field($_GET['s'] ?? '');
    $category_filter = sanitize_text_field($_GET['category'] ?? '');
    $creator_filter = sanitize_text_field($_GET['creator'] ?? '');
    $public_filter = sanitize_text_field($_GET['public'] ?? '');
    
    // SQL条件構築
    $where_conditions = array();
    $params = array();
    
    if (!empty($search)) {
        $where_conditions[] = '(m.title LIKE %s OR m.description LIKE %s)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    
    if (!empty($category_filter)) {
        $where_conditions[] = 'm.category = %s';
        $params[] = $category_filter;
    }
    
    if (!empty($creator_filter)) {
        $where_conditions[] = 'm.creator_id = %d';
        $params[] = intval($creator_filter);
    }
    
    if ($public_filter !== '') {
        $where_conditions[] = 'm.is_public = %d';
        $params[] = intval($public_filter);
    }
    
    $where_clause = empty($where_conditions) ? '' : 'WHERE ' . implode(' AND ', $where_conditions);
    
    // マインドマップ一覧取得
    $mindmaps_table = $wpdb->prefix . 'gyosei_mindmaps';
    $sql = "SELECT m.*, u.display_name as creator_name,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}gyosei_user_progress WHERE map_id = m.id) as user_count,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}gyosei_map_comments WHERE map_id = m.id) as comment_count
            FROM {$mindmaps_table} m
            LEFT JOIN {$wpdb->users} u ON m.creator_id = u.ID
            {$where_clause}
            ORDER BY m.created_at DESC
            LIMIT %d OFFSET %d";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $mindmaps = $wpdb->get_results($wpdb->prepare($sql, $params));
    
    // 総数取得
    $count_sql = "SELECT COUNT(*) FROM {$mindmaps_table} m {$where_clause}";
    $total_items = $wpdb->get_var(empty($where_conditions) ? $count_sql : $wpdb->prepare($count_sql, array_slice($params, 0, -2)));
    $total_pages = ceil($total_items / $per_page);
    
    // 統計データ
    $stats = gyosei_get_manage_stats();
    
    ?>
    <div class="wrap gyosei-admin-container">
        <div class="gyosei-admin-header">
            <h1>マインドマップ管理</h1>
            <div class="header-actions">
                <a href="#" class="button" id="import-templates">テンプレートインポート</a>
                <a href="#" class="button button-primary" id="create-new-map">新規作成</a>
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
        
        <!-- 統計カード -->
        <div class="gyosei-admin-stats">
            <div class="gyosei-stat-card">
                <span class="gyosei-stat-number"><?php echo number_format($stats['total_maps']); ?></span>
                <span class="gyosei-stat-label">総マップ数</span>
            </div>
            <div class="gyosei-stat-card">
                <span class="gyosei-stat-number"><?php echo number_format($stats['public_maps']); ?></span>
                <span class="gyosei-stat-label">公開マップ</span>
            </div>
            <div class="gyosei-stat-card">
                <span class="gyosei-stat-number"><?php echo number_format($stats['template_maps']); ?></span>
                <span class="gyosei-stat-label">テンプレート</span>
            </div>
            <div class="gyosei-stat-card">
                <span class="gyosei-stat-number"><?php echo number_format($stats['active_users']); ?></span>
                <span class="gyosei-stat-label">アクティブユーザー</span>
            </div>
        </div>
        
        <!-- フィルター -->
        <div class="gyosei-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="gyosei-mindmap-manage">
                
                <div class="gyosei-filter-group">
                    <span class="gyosei-filter-label">検索:</span>
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>" class="gyosei-filter-input" placeholder="タイトルまたは説明で検索">
                </div>
                
                <div class="gyosei-filter-group">
                    <span class="gyosei-filter-label">カテゴリ:</span>
                    <select name="category" class="gyosei-filter-select">
                        <option value="">全カテゴリ</option>
                        <option value="gyosei" <?php selected($category_filter, 'gyosei'); ?>>行政法</option>
                        <option value="minpo" <?php selected($category_filter, 'minpo'); ?>>民法</option>
                        <option value="kenpou" <?php selected($category_filter, 'kenpou'); ?>>憲法</option>
                        <option value="shoken" <?php selected($category_filter, 'shoken'); ?>>商法・会社法</option>
                        <option value="general" <?php selected($category_filter, 'general'); ?>>一般知識</option>
                        <option value="custom" <?php selected($category_filter, 'custom'); ?>>カスタム</option>
                    </select>
                </div>
                
                <div class="gyosei-filter-group">
                    <span class="gyosei-filter-label">公開状態:</span>
                    <select name="public" class="gyosei-filter-select">
                        <option value="">全て</option>
                        <option value="1" <?php selected($public_filter, '1'); ?>>公開</option>
                        <option value="0" <?php selected($public_filter, '0'); ?>>非公開</option>
                    </select>
                </div>
                
                <button type="submit" class="gyosei-filter-button">フィルター適用</button>
                <a href="?page=gyosei-mindmap-manage" class="button">リセット</a>
            </form>
        </div>
        
        <!-- マインドマップ一覧 -->
        <form method="post" action="" id="bulk-action-form">
            <?php wp_nonce_field('gyosei_manage_action'); ?>
            <input type="hidden" name="action" value="">
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <select name="bulk_action" id="bulk-action-select">
                        <option value="">一括操作を選択</option>
                        <option value="bulk_delete">削除</option>
                        <option value="bulk_public">公開に変更</option>
                        <option value="bulk_private">非公開に変更</option>
                    </select>
                    <button type="button" class="button" id="apply-bulk-action">適用</button>
                </div>
                
                <?php if ($total_pages > 1): ?>
                <div class="alignright">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
                <?php endif; ?>
            </div>
            
            <table class="wp-list-table widefat fixed striped gyosei-mindmap-table">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="select-all">
                        </td>
                        <th class="manage-column column-title" data-sort="title">タイトル</th>
                        <th class="manage-column column-category" data-sort="category">カテゴリ</th>
                        <th class="manage-column column-creator" data-sort="creator">作成者</th>
                        <th class="manage-column column-status">ステータス</th>
                        <th class="manage-column column-stats">統計</th>
                        <th class="manage-column column-date" data-sort="date">作成日</th>
                        <th class="manage-column column-actions">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mindmaps)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 50px;">
                            マインドマップが見つかりませんでした。
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($mindmaps as $map): ?>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" name="map_ids[]" value="<?php echo $map->id; ?>" class="map-checkbox">
                        </th>
                        <td class="column-title">
                            <strong>
                                <a href="#" class="preview-map" data-map-id="<?php echo $map->id; ?>">
                                    <?php echo esc_html($map->title); ?>
                                </a>
                            </strong>
                            <?php if ($map->is_template): ?>
                                <span class="status-badge status-template">テンプレート</span>
                            <?php endif; ?>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="#" class="edit-map" data-map-id="<?php echo $map->id; ?>">編集</a> |
                                </span>
                                <span class="view">
                                    <a href="#" class="preview-map" data-map-id="<?php echo $map->id; ?>">プレビュー</a> |
                                </span>
                                <span class="duplicate">
                                    <a href="#" class="duplicate-map" data-map-id="<?php echo $map->id; ?>">複製</a> |
                                </span>
                                <span class="delete">
                                    <a href="#" class="delete-map" data-map-id="<?php echo $map->id; ?>" style="color: #a00;">削除</a>
                                </span>
                            </div>
                        </td>
                        <td class="column-category">
                            <span class="category-badge category-<?php echo esc_attr($map->category); ?>">
                                <?php echo esc_html(gyosei_get_category_label($map->category)); ?>
                            </span>
                        </td>
                        <td class="column-creator">
                            <?php echo esc_html($map->creator_name ?: 'システム'); ?>
                        </td>
                        <td class="column-status">
                            <div class="status-badges">
                                <?php if ($map->is_public): ?>
                                    <span class="status-badge status-published">公開</span>
                                <?php else: ?>
                                    <span class="status-badge status-draft">非公開</span>
                                <?php endif; ?>
                                
                                <?php if ($map->is_template): ?>
                                    <span class="status-badge status-template">テンプレート</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="column-stats">
                            <div class="stats-mini">
                                <span title="いいね数">👍 <?php echo number_format($map->likes_count); ?></span>
                                <span title="閲覧数">👀 <?php echo number_format($map->views_count); ?></span>
                                <span title="ユーザー数">👥 <?php echo number_format($map->user_count); ?></span>
                                <span title="コメント数">💬 <?php echo number_format($map->comment_count); ?></span>
                            </div>
                        </td>
                        <td class="column-date">
                            <?php echo mysql2date('Y/m/d H:i', $map->created_at); ?>
                        </td>
                        <td class="column-actions">
                            <div class="action-buttons">
                                <a href="#" class="btn-edit" data-map-id="<?php echo $map->id; ?>" title="編集">✏️</a>
                                <a href="#" class="btn-preview" data-map-id="<?php echo $map->id; ?>" title="プレビュー">👁️</a>
                                <a href="#" class="btn-duplicate" data-map-id="<?php echo $map->id; ?>" title="複製">📋</a>
                                <button class="btn-delete" data-map-id="<?php echo $map->id; ?>" title="削除">🗑️</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div class="tablenav bottom">
                <?php if ($total_pages > 1): ?>
                <div class="alignright">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- マップ編集モーダル -->
    <div id="map-editor-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>マインドマップ編集</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="map-edit-form">
                    <div class="form-group">
                        <label for="map-title">タイトル</label>
                        <input type="text" id="map-title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="map-description">説明</label>
                        <textarea id="map-description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="map-category">カテゴリ</label>
                        <select id="map-category" name="category">
                            <option value="custom">カスタム</option>
                            <option value="gyosei">行政法</option>
                            <option value="minpo">民法</option>
                            <option value="kenpou">憲法</option>
                            <option value="shoken">商法・会社法</option>
                            <option value="general">一般知識</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="map-tags">タグ（カンマ区切り）</label>
                        <input type="text" id="map-tags" name="tags" placeholder="例: 基礎, 重要, 頻出">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="map-public" name="is_public">
                            公開する
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="map-template" name="is_template">
                            テンプレートとして設定
                        </label>
                    </div>
                </form>
                
                <div class="json-editor-container">
                    <div class="json-editor-header">
                        <h4>マップデータ（JSON）</h4>
                        <div class="json-editor-tools">
                            <button type="button" class="button" id="format-json">整形</button>
                            <button type="button" class="button" id="validate-json">検証</button>
                            <button type="button" class="button" id="import-json">インポート</button>
                        </div>
                    </div>
                    <textarea id="map-data-editor" class="json-editor-textarea"></textarea>
                    <div id="json-validator" class="json-validator"></div>
                </div>
                
                <div class="preview-section">
                    <div class="preview-header">
                        <h4>プレビュー</h4>
                        <button type="button" class="button" id="refresh-preview">更新</button>
                    </div>
                    <div id="map-preview" class="preview-content">
                        <p>プレビューを表示するには「更新」ボタンをクリックしてください。</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="button" id="cancel-edit">キャンセル</button>
                <button type="button" class="button button-primary" id="save-map">保存</button>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // 管理画面初期化
        initManagePage();
    });
    </script>
    
    <?php
}

// ヘルパー関数
function gyosei_get_category_label($category) {
    $labels = array(
        'gyosei' => '行政法',
        'minpo' => '民法', 
        'kenpou' => '憲法',
        'shoken' => '商法・会社法',
        'general' => '一般知識',
        'custom' => 'カスタム'
    );
    return $labels[$category] ?? $category;
}

function gyosei_get_manage_stats() {
    global $wpdb;
    $mindmaps_table = $wpdb->prefix . 'gyosei_mindmaps';
    
    return array(
        'total_maps' => $wpdb->get_var("SELECT COUNT(*) FROM {$mindmaps_table}"),
        'public_maps' => $wpdb->get_var("SELECT COUNT(*) FROM {$mindmaps_table} WHERE is_public = 1"),
        'template_maps' => $wpdb->get_var("SELECT COUNT(*) FROM {$mindmaps_table} WHERE is_template = 1"),
        'active_users' => $wpdb->get_var("SELECT COUNT(DISTINCT creator_id) FROM {$mindmaps_table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")
    );
}

function gyosei_delete_mindmap_admin($map_id) {
    global $wpdb;
    
    // 関連データも削除
    $wpdb->delete($wpdb->prefix . 'gyosei_user_progress', array('map_id' => $map_id));
    $wpdb->delete($wpdb->prefix . 'gyosei_map_comments', array('map_id' => $map_id));
    $wpdb->delete($wpdb->prefix . 'gyosei_map_likes', array('map_id' => $map_id));
    
    // マップ削除
    return $wpdb->delete($wpdb->prefix . 'gyosei_mindmaps', array('id' => $map_id));
}

function gyosei_toggle_map_public($map_id, $is_public) {
    global $wpdb;
    
    return $wpdb->update(
        $wpdb->prefix . 'gyosei_mindmaps',
        array('is_public' => $is_public),
        array('id' => $map_id),
        array('%d'),
        array('%d')
    );
}

function gyosei_import_template_maps() {
    // テンプレートマップのインポート処理
    if (class_exists('GyoseiMindMapSampleData')) {
        return GyoseiUserManager::create_default_mindmaps();
    }
    return false;
}
?>