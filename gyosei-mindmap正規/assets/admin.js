// 行政書士の道 - 管理画面JavaScript (完全修正版)
// File: assets/admin.js

jQuery(document).ready(function($) {
    
    console.log('管理画面JavaScript読み込み完了');
    
    // 管理画面初期化
    function initManagePage() {
        // モーダル初期化
        initModals();
        
        // テーブルソート
        initTableSorting();
        
        // フィルター機能
        initFilters();
        
        // 一括操作
        initBulkActions();
        
        // マップ操作
        initMapActions();
        
        // Ajax処理
        initAjaxHandlers();
        
        console.log('管理ページ初期化完了');
    }
    
    // 設定ページ初期化
    function initSettingsPage() {
        // タブ機能
        initTabs();
        
        // カラーピッカー
        initColorPickers();
        
        // カテゴリ管理
        initCategoryManagement();
        
        // フォーム保存
        initFormSaving();
        
        // 危険な操作
        initDangerousActions();
        
        console.log('設定ページ初期化完了');
    }
    
    // モーダル初期化
    function initModals() {
        // モーダルを開く
        $(document).on('click', '[data-modal]', function(e) {
            e.preventDefault();
            const modalId = $(this).data('modal');
            $(`#${modalId}`).show();
        });
        
        // モーダルを閉じる
        $(document).on('click', '.modal .close, .modal-overlay', function() {
            $(this).closest('.modal').hide();
        });
        
        // Escキーでモーダルを閉じる
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.modal:visible').hide();
            }
        });
    }
    
    // テーブルソート初期化
    function initTableSorting() {
        $('.gyosei-mindmap-table th[data-sort]').on('click', function() {
            const th = $(this);
            const table = th.closest('table');
            const column = th.data('sort');
            const currentOrder = th.data('order') || 'asc';
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            // ソート方向の更新
            table.find('th').removeData('order').removeClass('sorted-asc sorted-desc');
            th.data('order', newOrder).addClass(`sorted-${newOrder}`);
            
            // 行をソート
            const rows = table.find('tbody tr').toArray();
            const columnIndex = th.index();
            
            rows.sort((a, b) => {
                const aValue = $(a).find('td').eq(columnIndex).text().trim();
                const bValue = $(b).find('td').eq(columnIndex).text().trim();
                
                // 数値かどうかチェック
                const aNum = parseFloat(aValue.replace(/[^\d.-]/g, ''));
                const bNum = parseFloat(bValue.replace(/[^\d.-]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return newOrder === 'asc' ? aNum - bNum : bNum - aNum;
                } else {
                    return newOrder === 'asc' 
                        ? aValue.localeCompare(bValue)
                        : bValue.localeCompare(aValue);
                }
            });
            
            table.find('tbody').html(rows);
        });
    }
    
    // フィルター初期化
    function initFilters() {
        $('.gyosei-filter-input').on('input', debounce(function() {
            applyFilters();
        }, 300));
        
        $('.gyosei-filter-select').on('change', function() {
            applyFilters();
        });
    }
    
    function applyFilters() {
        const searchValue = $('.gyosei-filter-input').val().toLowerCase();
        const categoryFilter = $('#category-filter').val();
        const statusFilter = $('#status-filter').val();
        
        $('.gyosei-mindmap-table tbody tr').each(function() {
            const row = $(this);
            const title = row.find('.column-title').text().toLowerCase();
            const category = row.find('.column-category').text().toLowerCase();
            const status = row.find('.column-status').text().toLowerCase();
            
            let show = true;
            
            if (searchValue && !title.includes(searchValue)) {
                show = false;
            }
            
            if (categoryFilter && !category.includes(categoryFilter.toLowerCase())) {
                show = false;
            }
            
            if (statusFilter && !status.includes(statusFilter.toLowerCase())) {
                show = false;
            }
            
            row.toggle(show);
        });
    }
    
    // 一括操作初期化
    function initBulkActions() {
        // 全選択/全解除
        $('#select-all').on('change', function() {
            const checked = $(this).is(':checked');
            $('.map-checkbox').prop('checked', checked);
            updateBulkActionButton();
        });
        
        // 個別チェックボックス
        $(document).on('change', '.map-checkbox', function() {
            updateBulkActionButton();
        });
        
        // 一括操作実行
        $('#apply-bulk-action').on('click', function() {
            const action = $('#bulk-action-select').val();
            const selectedIds = $('.map-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (!action) {
                alert('操作を選択してください。');
                return;
            }
            
            if (selectedIds.length === 0) {
                alert('マップを選択してください。');
                return;
            }
            
            if (confirm(`選択した${selectedIds.length}個のマップに対して「${action}」を実行しますか？`)) {
                executeBulkAction(action, selectedIds);
            }
        });
    }
    
    function updateBulkActionButton() {
        const selectedCount = $('.map-checkbox:checked').length;
        const button = $('#apply-bulk-action');
        
        if (selectedCount > 0) {
            button.prop('disabled', false).text(`適用 (${selectedCount})`);
        } else {
            button.prop('disabled', true).text('適用');
        }
    }
    
    function executeBulkAction(action, mapIds) {
        const data = {
            action: action,
            map_ids: mapIds,
            nonce: mindmapAdminData.nonce
        };
        
        $.post(mindmapAdminData.ajaxurl, data)
            .done(function(response) {
                if (response.success) {
                    showNotice(response.data || '操作が完了しました。', 'success');
                    location.reload();
                } else {
                    showNotice(response.data || '操作に失敗しました。', 'error');
                }
            })
            .fail(function() {
                showNotice('通信エラーが発生しました。', 'error');
            });
    }
    
    // マップ操作初期化
    function initMapActions() {
        // プレビュー
        $(document).on('click', '.preview-map, .btn-preview', function(e) {
            e.preventDefault();
            const mapId = $(this).data('map-id');
            openMapPreview(mapId);
        });
        
        // 編集
        $(document).on('click', '.edit-map, .btn-edit', function(e) {
            e.preventDefault();
            const mapId = $(this).data('map-id');
            openMapEditor(mapId);
        });
        
        // 複製
        $(document).on('click', '.duplicate-map, .btn-duplicate', function(e) {
            e.preventDefault();
            const mapId = $(this).data('map-id');
            duplicateMap(mapId);
        });
        
        // 削除
        $(document).on('click', '.delete-map, .btn-delete', function(e) {
            e.preventDefault();
            const mapId = $(this).data('map-id');
            
            if (confirm('本当にこのマップを削除しますか？この操作は取り消せません。')) {
                deleteMap(mapId);
            }
        });
    }
    
    function openMapPreview(mapId) {
        const url = `${window.location.origin}/?mindmap_preview=1&map_id=${mapId}`;
        window.open(url, 'mindmap_preview', 'width=1200,height=800,scrollbars=yes,resizable=yes');
    }
    
    function openMapEditor(mapId) {
        // マップデータを取得してエディターモーダルを開く
        $.post(mindmapAdminData.ajaxurl, {
            action: 'load_user_mindmap',
            map_id: mapId,
            nonce: mindmapAdminData.nonce
        })
        .done(function(response) {
            if (response.success) {
                populateMapEditor(response.data);
                $('#map-editor-modal').show();
            } else {
                showNotice('マップの読み込みに失敗しました。', 'error');
            }
        })
        .fail(function() {
            showNotice('通信エラーが発生しました。', 'error');
        });
    }
    
    function populateMapEditor(mapData) {
        $('#map-title').val(mapData.title);
        $('#map-description').val(mapData.description);
        $('#map-category').val(mapData.category);
        $('#map-tags').val(mapData.tags);
        $('#map-public').prop('checked', mapData.is_public);
        $('#map-template').prop('checked', mapData.is_template);
        $('#map-data-editor').val(JSON.stringify(mapData.map_data, null, 2));
        
        // 現在編集中のマップIDを保存
        $('#map-editor-modal').data('editing-map-id', mapData.id);
    }
    
    function duplicateMap(mapId) {
        $.post(mindmapAdminData.ajaxurl, {
            action: 'duplicate_mindmap',
            map_id: mapId,
            nonce: mindmapAdminData.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice('マップを複製しました。', 'success');
                location.reload();
            } else {
                showNotice('マップの複製に失敗しました。', 'error');
            }
        })
        .fail(function() {
            showNotice('通信エラーが発生しました。', 'error');
        });
    }
    
    function deleteMap(mapId) {
        $.post(mindmapAdminData.ajaxurl, {
            action: 'delete_mindmap_admin',
            map_id: mapId,
            nonce: mindmapAdminData.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice('マップを削除しました。', 'success');
                $(`tr[data-map-id="${mapId}"]`).fadeOut();
            } else {
                showNotice('マップの削除に失敗しました。', 'error');
            }
        })
        .fail(function() {
            showNotice('通信エラーが発生しました。', 'error');
        });
    }
    
    // Ajax処理初期化
    function initAjaxHandlers() {
        // JSONエディター関連
        $('#format-json').on('click', function() {
            try {
                const json = JSON.parse($('#map-data-editor').val());
                $('#map-data-editor').val(JSON.stringify(json, null, 2));
                showNotice('JSONを整形しました。', 'success');
            } catch (e) {
                showNotice('JSONの形式が正しくありません。', 'error');
            }
        });
        
        $('#validate-json').on('click', function() {
            try {
                JSON.parse($('#map-data-editor').val());
                $('#json-validator').removeClass('json-invalid').addClass('json-valid')
                    .text('✓ 有効なJSONです');
            } catch (e) {
                $('#json-validator').removeClass('json-valid').addClass('json-invalid')
                    .text('✗ JSONエラー: ' + e.message);
            }
        });
        
        // マップ保存
        $('#save-map').on('click', function() {
            saveMap();
        });
        
        // プレビュー更新
        $('#refresh-preview').on('click', function() {
            updateMapPreview();
        });
    }
    
    function saveMap() {
        const mapId = $('#map-editor-modal').data('editing-map-id') || 0;
        
        // JSONの検証
        let mapData;
        try {
            mapData = JSON.parse($('#map-data-editor').val());
        } catch (e) {
            showNotice('JSONの形式が正しくありません: ' + e.message, 'error');
            return;
        }
        
        const data = {
            action: 'save_mindmap_admin',
            map_id: mapId,
            title: $('#map-title').val(),
            description: $('#map-description').val(),
            category: $('#map-category').val(),
            tags: $('#map-tags').val(),
            is_public: $('#map-public').is(':checked') ? 1 : 0,
            is_template: $('#map-template').is(':checked') ? 1 : 0,
            map_data: JSON.stringify(mapData),
            nonce: mindmapAdminData.nonce
        };
        
        $.post(mindmapAdminData.ajaxurl, data)
            .done(function(response) {
                if (response.success) {
                    showNotice('マップを保存しました。', 'success');
                    $('#map-editor-modal').hide();
                    location.reload();
                } else {
                    showNotice('マップの保存に失敗しました。', 'error');
                }
            })
            .fail(function() {
                showNotice('通信エラーが発生しました。', 'error');
            });
    }
    
    function updateMapPreview() {
        try {
            const mapData = JSON.parse($('#map-data-editor').val());
            const previewContainer = $('#map-preview');
            
            // プレビュー用のマインドマップを生成
            previewContainer.html('<div id="preview-mindmap" style="width: 100%; height: 300px;"></div>');
            
            // MindMapRendererが利用可能な場合は実際のプレビューを表示
            if (typeof window.MindMapRenderer !== 'undefined') {
                new window.MindMapRenderer($('#preview-mindmap'), mapData);
            } else {
                previewContainer.html(`
                    <div style="padding: 20px; text-align: center; background: #f0f0f0; border-radius: 5px;">
                        <h4>${mapData.title || '無題のマップ'}</h4>
                        <p>ノード数: ${mapData.nodes ? mapData.nodes.length : 0}</p>
                        <p>接続数: ${mapData.connections ? mapData.connections.length : 0}</p>
                    </div>
                `);
            }
        } catch (e) {
            $('#map-preview').html('<p style="color: red;">プレビューの生成に失敗しました: ' + e.message + '</p>');
        }
    }
    
    // 設定ページ関連
    function initTabs() {
        $('.gyosei-tab-item').on('click', function(e) {
            e.preventDefault();
            
            const tabItem = $(this);
            const targetTab = tabItem.find('.gyosei-tab-link').attr('href');
            
            // アクティブタブの切り替え
            tabItem.siblings().removeClass('active');
            tabItem.addClass('active');
            
            // コンテンツの切り替え
            $('.gyosei-tab-content').removeClass('active');
            $(targetTab).addClass('active');
            
            // URLのハッシュを更新
            window.location.hash = targetTab;
        });
        
        // ページ読み込み時にハッシュに基づいてタブを表示
        if (window.location.hash) {
            const targetTab = window.location.hash;
            $(`.gyosei-tab-link[href="${targetTab}"]`).closest('.gyosei-tab-item').click();
        }
    }
    
    function initColorPickers() {
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker({
                change: function(event, ui) {
                    const color = ui.color.toString();
                    $(this).closest('.category-card').find('.category-header').css('background', color);
                }
            });
        }
    }
    
    function initCategoryManagement() {
        let categoryIndex = $('.category-card').length;
        
        // カテゴリ追加
        $('#add-category').on('click', function() {
            const newCategory = createCategoryCard(categoryIndex++, {
                key: 'custom_' + Date.now(),
                name: '新しいカテゴリ',
                color: '#607d8b',
                enabled: true
            });
            $('#category-list').append(newCategory);
        });
        
        // カテゴリ削除
        $(document).on('click', '.remove-category', function() {
            if (confirm('このカテゴリを削除しますか？')) {
                $(this).closest('.category-card').remove();
            }
        });
        
        // ソート可能にする
        if ($.fn.sortable) {
            $('#category-list').sortable({
                handle: '.category-header',
                update: function() {
                    // ソート後のインデックスを更新
                    $('#category-list .category-card').each(function(index) {
                        $(this).find('input[name*="categories"]').each(function() {
                            const name = $(this).attr('name');
                            const newName = name.replace(/categories\[\d+\]/, `categories[${index}]`);
                            $(this).attr('name', newName);
                        });
                    });
                }
            });
        }
        
        // デフォルトリセット
        $('#reset-categories').on('click', function() {
            if (confirm('カテゴリをデフォルトに戻しますか？')) {
                resetCategoriesToDefault();
            }
        });
    }
    
    function createCategoryCard(index, category) {
        return $(`
            <div class="category-card" data-index="${index}">
                <div class="category-header" style="background: ${category.color}">
                    <h3>${category.name}</h3>
                    <div class="category-count">0</div>
                </div>
                <div class="category-body">
                    <input type="hidden" name="categories[${index}][key]" value="${category.key}">
                    
                    <label>カテゴリ名</label>
                    <input type="text" name="categories[${index}][name]" value="${category.name}" required>
                    
                    <label>色</label>
                    <input type="color" name="categories[${index}][color]" value="${category.color}" class="color-picker">
                    
                    <div class="category-actions">
                        <label>
                            <input type="checkbox" name="categories[${index}][enabled]" ${category.enabled ? 'checked' : ''}>
                            有効
                        </label>
                        <button type="button" class="button button-link-delete remove-category">削除</button>
                    </div>
                </div>
            </div>
        `);
    }
    
    function resetCategoriesToDefault() {
        // デフォルトカテゴリを再作成
        $('#category-list').empty();
        
        const defaultCategories = [
            { key: 'gyosei', name: '行政法', color: '#3f51b5', enabled: true },
            { key: 'minpo', name: '民法', color: '#e91e63', enabled: true },
            { key: 'kenpou', name: '憲法', color: '#ff9800', enabled: true },
            { key: 'shoken', name: '商法・会社法', color: '#4caf50', enabled: true },
            { key: 'general', name: '一般知識', color: '#9c27b0', enabled: true },
            { key: 'custom', name: 'カスタム', color: '#607d8b', enabled: true }
        ];
        
        defaultCategories.forEach((category, index) => {
            const categoryCard = createCategoryCard(index, category);
            $('#category-list').append(categoryCard);
        });
        
        // カラーピッカーを再初期化
        if ($.fn.wpColorPicker) {
            $('.color-picker').wpColorPicker();
        }
    }
    
    function initFormSaving() {
        // 自動保存機能
        $('.auto-save').on('change', function() {
            const field = $(this);
            const data = {
                action: 'save_setting',
                key: field.attr('name'),
                value: field.val(),
                nonce: mindmapAdminData.nonce
            };
            
            $.post(mindmapAdminData.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        field.addClass('saved');
                        setTimeout(() => field.removeClass('saved'), 1000);
                    }
                });
        });
        
        // 設定エクスポート
        $('#export-settings').on('click', function() {
            exportSettings();
        });
        
        // 設定インポート
        $('#import-settings').on('click', function() {
            $('#import-file').click();
        });
        
        $('#import-file').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                importSettings(file);
            }
        });
        
        // データエクスポート
        $('#export-data').on('click', function() {
            exportAllData();
        });
        
        // データインポート
        $('#import-data').on('click', function() {
            $('#import-data-file').click();
        });
        
        $('#import-data-file').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                importAllData(file);
            }
        });
        
        // キャッシュ管理
        $('#clear-cache').on('click', function() {
            clearCache();
        });
        
        $('#preload-cache').on('click', function() {
            preloadCache();
        });
        
        // データベース最適化
        $('#optimize-db').on('click', function() {
            optimizeDatabase();
        });
        
        $('#repair-db').on('click', function() {
            repairDatabase();
        });
    }
    
    function initDangerousActions() {
        // データリセット
        $('#reset-all-data').on('click', function() {
            $('#reset-confirm-modal').show();
        });
        
        // リセット確認
        $('#confirm-reset, #delete-confirmation').on('input change', function() {
            const isChecked = $('#confirm-reset').is(':checked');
            const confirmText = $('#delete-confirmation').val();
            const isValid = isChecked && confirmText === '削除';
            
            $('#confirm-reset-btn').prop('disabled', !isValid);
        });
        
        // リセット実行
        $('#confirm-reset-btn').on('click', function() {
            $('#reset-data-form').submit();
        });
        
        // リセットキャンセル
        $('#cancel-reset').on('click', function() {
            $('#reset-confirm-modal').hide();
            $('#confirm-reset').prop('checked', false);
            $('#delete-confirmation').val('');
        });
        
        // プラグイン完全削除
        $('#uninstall-plugin').on('click', function() {
            if (confirm('本当にプラグインを完全削除しますか？すべてのデータが失われます。')) {
                if (confirm('この操作は取り消せません。本当に実行しますか？')) {
                    uninstallPlugin();
                }
            }
        });
    }
    
    // 設定管理関数
    function exportSettings() {
        const settings = {};
        
        $('#settings-form input, #settings-form select, #settings-form textarea').each(function() {
            const field = $(this);
            const name = field.attr('name');
            
            if (name) {
                if (field.attr('type') === 'checkbox') {
                    settings[name] = field.is(':checked');
                } else {
                    settings[name] = field.val();
                }
            }
        });
        
        const dataStr = JSON.stringify(settings, null, 2);
        const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
        
        const exportFileDefaultName = `mindmap-settings-${new Date().toISOString().split('T')[0]}.json`;
        
        const linkElement = document.createElement('a');
        linkElement.setAttribute('href', dataUri);
        linkElement.setAttribute('download', exportFileDefaultName);
        linkElement.click();
        
        showNotice('設定をエクスポートしました。', 'success');
    }
    
    function importSettings(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const settings = JSON.parse(e.target.result);
                
                Object.keys(settings).forEach(key => {
                    const field = $(`[name="${key}"]`);
                    if (field.length) {
                        if (field.attr('type') === 'checkbox') {
                            field.prop('checked', settings[key]);
                        } else {
                            field.val(settings[key]);
                        }
                    }
                });
                
                showNotice('設定をインポートしました。', 'success');
            } catch (error) {
                showNotice('設定ファイルが無効です。', 'error');
            }
        };
        reader.readAsText(file);
    }
    
    function exportAllData() {
        $.post(mindmapAdminData.ajaxurl, {
            action: 'export_all_data',
            nonce: mindmapAdminData.nonce
        })
        .done(function(response) {
            if (response.success) {
                const dataStr = JSON.stringify(response.data, null, 2);
                const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
                
                const exportFileDefaultName = `mindmap-data-${new Date().toISOString().split('T')[0]}.json`;
                
                const linkElement = document.createElement('a');
                linkElement.setAttribute('href', dataUri);
                linkElement.setAttribute('download', exportFileDefaultName);
                linkElement.click();
                
                showNotice('データをエクスポートしました。', 'success');
            } else {
                showNotice('データのエクスポートに失敗しました。', 'error');
            }
        })
        .fail(function() {
            showNotice('通信エラーが発生しました。', 'error');
        });
    }
    
    function importAllData(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const data = JSON.parse(e.target.result);
                
                $.post(mindmapAdminData.ajaxurl, {
                    action: 'import_all_data',
                    data: JSON.stringify(data),
                    nonce: mindmapAdminData.nonce
                })
                .done(function(response) {
                    if (response.success) {
                        showNotice('データをインポートしました。', 'success');
                        location.reload();
                    } else {
                        showNotice('データのインポートに失敗しました。', 'error');
                    }
                })
                .fail(function() {
                    showNotice('通信エラーが発生しました。', 'error');
                });
            } catch (error) {
                showNotice('データファイルが無効です。', 'error');
            }
        };
        reader.readAsText(file);
    }
    
    function clearCache() {
        $.post(mindmapAdminData.ajaxurl, {
            action: 'clear_mindmap_cache',
            nonce: mindmapAdminData.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice('キャッシュをクリアしました。', 'success');
            } else {
                showNotice('キャッシュのクリアに失敗しました。', 'error');
            }
        });
    }
    
    function preloadCache() {
        $.post(mindmapAdminData.ajaxurl, {
            action: 'preload_mindmap_cache',
            nonce: mindmapAdminData.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice('キャッシュを事前生成しました。', 'success');
            } else {
                showNotice('キャッシュの事前生成に失敗しました。', 'error');
            }
        });
    }
    
    function optimizeDatabase() {
        if (confirm('データベースを最適化しますか？')) {
            $.post(mindmapAdminData.ajaxurl, {
                action: 'optimize_mindmap_database',
                nonce: mindmapAdminData.nonce
            })
            .done(function(response) {
                if (response.success) {
                    showNotice('データベースを最適化しました。', 'success');
                } else {
                    showNotice('データベースの最適化に失敗しました。', 'error');
                }
            });
        }
    }
    
    function repairDatabase() {
        if (confirm('データベースを修復しますか？')) {
            $.post(mindmapAdminData.ajaxurl, {
                action: 'repair_mindmap_database',
                nonce: mindmapAdminData.nonce
            })
            .done(function(response) {
                if (response.success) {
                    showNotice('データベースを修復しました。', 'success');
                } else {
                    showNotice('データベースの修復に失敗しました。', 'error');
                }
            });
        }
    }
    
    function uninstallPlugin() {
        $.post(mindmapAdminData.ajaxurl, {
            action: 'uninstall_mindmap_plugin',
            nonce: mindmapAdminData.nonce
        })
        .done(function(response) {
            if (response.success) {
                alert('プラグインを削除しました。ページをリロードします。');
                window.location.href = admin_url('plugins.php');
            } else {
                showNotice('プラグインの削除に失敗しました。', 'error');
            }
        });
    }
    
    // ユーティリティ関数
    function showNotice(message, type = 'info') {
        // 既存の通知を削除
        $('.gyosei-admin-notice').remove();
        
        const notice = $(`
            <div class="notice notice-${type} is-dismissible gyosei-admin-notice">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">この通知を非表示にする</span>
                </button>
            </div>
        `);
        
        // 管理画面ヘッダーの後に挿入
        $('.gyosei-admin-header').after(notice);
        
        // 自動非表示
        setTimeout(() => {
            notice.fadeOut(() => notice.remove());
        }, 5000);
        
        // 手動非表示
        notice.find('.notice-dismiss').on('click', () => {
            notice.fadeOut(() => notice.remove());
        });
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // 統計カードのアニメーション
    function animateStatNumbers() {
        $('.gyosei-stat-number').each(function() {
            const $this = $(this);
            const finalValue = parseInt($this.text().replace(/,/g, ''));
            
            if (!isNaN(finalValue) && finalValue > 0) {
                $this.text('0');
                
                $({ counter: 0 }).animate({ counter: finalValue }, {
                    duration: 2000,
                    step: function() {
                        $this.text(Math.ceil(this.counter).toLocaleString());
                    },
                    complete: function() {
                        $this.text(finalValue.toLocaleString());
                    }
                });
            }
        });
    }
    
    // ショートコードコピー機能
    $('.copy-shortcode').on('click', function() {
        const btn = $(this);
        const code = btn.siblings('.shortcode-code').text();
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(code).then(() => {
                showCopyFeedback(btn);
            }).catch(() => {
                fallbackCopyText(code, btn);
            });
        } else {
            fallbackCopyText(code, btn);
        }
    });
    
    function showCopyFeedback(btn) {
        const originalText = btn.text();
        btn.text('コピー完了！').addClass('copied');
        
        setTimeout(() => {
            btn.text(originalText).removeClass('copied');
        }, 2000);
    }
    
    function fallbackCopyText(text, btn) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            showCopyFeedback(btn);
        } catch (err) {
            console.error('コピーに失敗しました:', err);
            showNotice('コピーに失敗しました。手動でコピーしてください。', 'error');
        }
        
        document.body.removeChild(textArea);
    }
    
    // ページ初期化
    $(document).ready(function() {
        // ページによって初期化を分ける
        const currentPage = window.location.search;
        
        if (currentPage.includes('gyosei-mindmap-manage')) {
            initManagePage();
        } else if (currentPage.includes('gyosei-mindmap-settings')) {
            initSettingsPage();
        }
        
        // 共通の初期化
        animateStatNumbers();
        
        // 通知の自動非表示
        $('.notice.is-dismissible').each(function() {
            const notice = $(this);
            setTimeout(() => {
                if (notice.is(':visible')) {
                    notice.fadeOut();
                }
            }, 10000);
        });
    });
    
    // グローバル関数として公開
    window.initManagePage = initManagePage;
    window.initSettingsPage = initSettingsPage;
    
    console.log('管理画面JavaScript初期化完了');
});