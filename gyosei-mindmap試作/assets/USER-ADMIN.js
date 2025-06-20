// 行政書士の道 - マインドマップ Phase 3-A JavaScript
// ユーザー管理強化機能

jQuery(document).ready(function($) {
    
    class MindMapRendererPhase3A extends MindMapRendererPhase2 {
        constructor(container, data) {
            super(container, data);
            
            this.editMode = this.canvas.data('editable') === 'true';
            this.customId = this.container.data('custom-id');
            this.isModified = false;
            
            this.initPhase3AFeatures();
        }
        
        initPhase3AFeatures() {
            if (this.editMode) {
                this.initEditMode();
            }
            
            this.initUserControls();
            this.initMapCreator();
            this.initUserMapsModal();
            this.initMapSettings();
        }
        
        // 編集モードの初期化
        initEditMode() {
            // ノード追加ボタン
            this.container.find('[data-action="add-node"]').on('click', () => {
                this.addNewNode();
            });
            
            // 保存ボタン
            this.container.find('[data-action="save-map"]').on('click', () => {
                this.saveCustomMap();
            });
            
            // 設定ボタン
            this.container.find('[data-action="map-settings"]').on('click', () => {
                this.showMapSettings();
            });
            
            // ノードの編集可能化
            this.makeNodesEditable();
            
            // 変更の監視
            this.watchForChanges();
        }
        
        // ユーザーコントロールの初期化
        initUserControls() {
            // マイマップボタン
            this.container.find('[data-action="user-maps"]').on('click', () => {
                this.showUserMapsModal();
            });
            
            // 新規作成ボタン
            this.container.find('[data-action="create-map"]').on('click', () => {
                this.showMapCreator();
            });
        }
        
        // マップ作成UI の初期化
        initMapCreator() {
            const modal = $(`#mindmap-creator-${this.container.data('mindmap-id')}`);
            
            // テンプレート選択
            modal.find('.template-option').on('click', function() {
                modal.find('.template-option').removeClass('selected');
                $(this).addClass('selected');
            });
            
            // フォーム送信
            modal.find('.mindmap-creator-form').on('submit', (e) => {
                e.preventDefault();
                this.createNewMap(modal);
            });
            
            // モーダルを閉じる
            modal.find('.mindmap-modal-close').on('click', () => {
                this.closeModal(modal);
            });
        }
        
        // ユーザーマップモーダルの初期化
        initUserMapsModal() {
            const modal = $(`#mindmap-user-maps-${this.container.data('mindmap-id')}`);
            
            // タブ切り替え
            modal.find('.tab-btn').on('click', (e) => {
                const tabName = $(e.target).data('tab');
                this.switchTab(modal, tabName);
            });
            
            // 検索機能
            modal.find('.map-search').on('input', (e) => {
                this.filterMaps($(e.target).val());
            });
            
            // モーダルを閉じる
            modal.find('.mindmap-modal-close').on('click', () => {
                this.closeModal(modal);
            });
        }
        
        // マップ設定の初期化
        initMapSettings() {
            const modal = $(`#mindmap-settings-${this.container.data('mindmap-id')}`);
            
            // エクスポートボタン
            modal.find('[data-action="export-map"]').on('click', () => {
                this.exportMap();
            });
            
            // インポートファイル選択
            modal.find('input[type="file"]').on('change', (e) => {
                this.importMap(e.target.files[0]);
            });
            
            // 設定フォーム送信
            modal.find('.mindmap-settings-form').on('submit', (e) => {
                e.preventDefault();
                this.saveMapSettings(modal);
            });
            
            // モーダルを閉じる
            modal.find('.mindmap-modal-close').on('click', () => {
                this.closeModal(modal);
            });
        }
        
        // 新しいノードを追加
        addNewNode() {
            const newNode = {
                id: 'node_' + Date.now(),
                text: '新しいノード',
                x: 400 + Math.random() * 100 - 50,
                y: 200 + Math.random() * 100 - 50,
                level: 1,
                color: '#3f51b5',
                icon: '📝',
                progress: 0,
                status: 'not-started',
                description: '新しく追加されたノードです。'
            };
            
            this.data.nodes.push(newNode);
            
            const nodeElement = this.createNodeElement(newNode);
            this.viewport.append(nodeElement);
            
            // 編集モードにする
            this.editNodeText(nodeElement);
            
            this.markAsModified();
        }
        
        // ノードを編集可能にする
        makeNodesEditable() {
            this.viewport.on('dblclick', '.mindmap-node', (e) => {
                e.stopPropagation();
                this.editNodeText($(e.currentTarget));
            });
            
            // 右クリックメニュー
            this.viewport.on('contextmenu', '.mindmap-node', (e) => {
                e.preventDefault();
                this.showNodeContextMenu(e, $(e.currentTarget));
            });
        }
        
        // ノードテキスト編集
        editNodeText(nodeElement) {
            const nodeId = nodeElement.data('node-id');
            const node = this.data.nodes.find(n => n.id === nodeId);
            const textSpan = nodeElement.find('.mindmap-node-text');
            
            const input = $('<input type="text" class="node-edit-input">')
                .val(node.text)
                .css({
                    background: 'transparent',
                    border: 'none',
                    color: 'inherit',
                    font: 'inherit',
                    width: '100%',
                    outline: 'none'
                });
            
            textSpan.hide().after(input);
            input.focus().select();
            
            const saveEdit = () => {
                const newText = input.val().trim();
                if (newText && newText !== node.text) {
                    node.text = newText;
                    textSpan.text(newText);
                    this.markAsModified();
                }
                input.remove();
                textSpan.show();
            };
            
            input.on('blur', saveEdit);
            input.on('keypress', (e) => {
                if (e.which === 13) { // Enter
                    saveEdit();
                }
            });
        }
        
        // ノードコンテキストメニュー
        showNodeContextMenu(e, nodeElement) {
            const nodeId = nodeElement.data('node-id');
            
            const menu = $(`
                <div class="node-context-menu" style="
                    position: fixed;
                    top: ${e.clientY}px;
                    left: ${e.clientX}px;
                    background: white;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                    z-index: 10000;
                    min-width: 150px;
                ">
                    <div class="menu-item" data-action="edit">✏️ 編集</div>
                    <div class="menu-item" data-action="change-color">🎨 色を変更</div>
                    <div class="menu-item" data-action="add-child">➕ 子ノード追加</div>
                    <div class="menu-item" data-action="delete">🗑️ 削除</div>
                </div>
            `);
            
            $('body').append(menu);
            
            menu.find('.menu-item').on('click', (e) => {
                const action = $(e.target).data('action');
                this.handleNodeContextAction(action, nodeId, nodeElement);
                menu.remove();
            });
            
            // 外をクリックしたら閉じる
            $(document).one('click', () => menu.remove());
        }
        
        // ノードコンテキストアクション処理
        handleNodeContextAction(action, nodeId, nodeElement) {
            const node = this.data.nodes.find(n => n.id === nodeId);
            
            switch (action) {
                case 'edit':
                    this.editNodeText(nodeElement);
                    break;
                    
                case 'change-color':
                    this.showColorPicker(node, nodeElement);
                    break;
                    
                case 'add-child':
                    this.addChildNode(node);
                    break;
                    
                case 'delete':
                    this.deleteNode(nodeId);
                    break;
            }
        }
        
        // 色選択
        showColorPicker(node, nodeElement) {
            const colors = ['#3f51b5', '#e91e63', '#4caf50', '#ff9800', '#9c27b0', '#f44336'];
            
            const picker = $('<div class="color-picker"></div>');
            colors.forEach(color => {
                const colorBtn = $(`<div class="color-option" style="
                    width: 30px;
                    height: 30px;
                    background: ${color};
                    border-radius: 50%;
                    margin: 5px;
                    cursor: pointer;
                    display: inline-block;
                "></div>`);
                
                colorBtn.on('click', () => {
                    node.color = color;
                    nodeElement.css('border-color', color);
                    this.markAsModified();
                    picker.remove();
                });
                
                picker.append(colorBtn);
            });
            
            nodeElement.append(picker);
            
            setTimeout(() => {
                $(document).one('click', () => picker.remove());
            }, 100);
        }
        
        // 子ノードを追加
        addChildNode(parentNode) {
            const childNode = {
                id: 'node_' + Date.now(),
                text: '子ノード',
                x: parentNode.x + 150,
                y: parentNode.y + 80,
                level: parentNode.level + 1,
                color: parentNode.color,
                icon: '📄',
                progress: 0,
                status: 'not-started',
                description: '子ノードです。',
                parent: parentNode.id
            };
            
            this.data.nodes.push(childNode);
            this.data.connections.push({
                from: parentNode.id,
                to: childNode.id
            });
            
            // 再描画
            this.renderMindMap();
            this.markAsModified();
        }
        
        // ノードを削除
        deleteNode(nodeId) {
            if (confirm('このノードを削除しますか？')) {
                // ノードを削除
                this.data.nodes = this.data.nodes.filter(n => n.id !== nodeId);
                
                // 接続も削除
                this.data.connections = this.data.connections.filter(c => 
                    c.from !== nodeId && c.to !== nodeId
                );
                
                // 再描画
                this.renderMindMap();
                this.markAsModified();
            }
        }
        
        // 変更を監視
        watchForChanges() {
            // ドラッグ終了時
            $(document).on('mouseup', () => {
                if (this.draggedNode) {
                    this.markAsModified();
                }
            });
        }
        
        // 変更フラグを設定
        markAsModified() {
            this.isModified = true;
            this.container.find('[data-action="save-map"]')
                .addClass('modified')
                .text('💾 保存*');
        }
        
        // カスタムマップを保存
        saveCustomMap() {
            if (!this.customId) {
                this.showSaveNotification('カスタムマップIDが設定されていません', 'error');
                return;
            }
            
            const mapData = {
                title: this.data.title,
                nodes: this.data.nodes,
                connections: this.data.connections
            };
            
            $.post(mindmapData.ajaxurl, {
                action: 'save_custom_map',
                map_id: this.customId,
                map_data: JSON.stringify(mapData),
                settings: JSON.stringify(this.getCurrentSettings()),
                nonce: mindmapData.nonce
            }).done((response) => {
                if (response.success) {
                    this.isModified = false;
                    this.container.find('[data-action="save-map"]')
                        .removeClass('modified')
                        .text('💾 保存');
                    this.showSaveNotification('保存されました！');
                } else {
                    this.showSaveNotification('保存に失敗しました: ' + response.data, 'error');
                }
            });
        }
        
        // 新しいマップを作成
        createNewMap(modal) {
            const formData = new FormData(modal.find('.mindmap-creator-form')[0]);
            const selectedTemplate = modal.find('.template-option.selected').data('template') || 'blank';
            
            $.post(mindmapData.ajaxurl, {
                action: 'create_custom_map',
                title: formData.get('map_title'),
                description: formData.get('map_description'),
                template: selectedTemplate,
                is_public: formData.get('is_public') ? 1 : 0,
                is_template: formData.get('is_template') ? 1 : 0,
                nonce: mindmapData.nonce
            }).done((response) => {
                if (response.success) {
                    this.showSaveNotification('マップが作成されました！');
                    this.closeModal(modal);
                    
                    // 作成されたマップに移動（オプション）
                    if (confirm('作成したマップを編集しますか？')) {
                        window.location.href = `?edit_map=${response.data.map_id}`;
                    }
                } else {
                    this.showSaveNotification('作成に失敗しました: ' + response.data, 'error');
                }
            });
        }
        
        // ユーザーマップモーダルを表示
        showUserMapsModal() {
            const modal = $(`#mindmap-user-maps-${this.container.data('mindmap-id')}`);
            modal.show();
            this.loadUserMaps('my-maps');
        }
        
        // マップ作成モーダルを表示
        showMapCreator() {
            const modal = $(`#mindmap-creator-${this.container.data('mindmap-id')}`);
            modal.show();
        }
        
        // マップ設定モーダルを表示
        showMapSettings() {
            const modal = $(`#mindmap-settings-${this.container.data('mindmap-id')}`);
            this.populateSettingsForm(modal);
            modal.show();
        }
        
        // タブ切り替え
        switchTab(modal, tabName) {
            modal.find('.tab-btn').removeClass('active');
            modal.find(`[data-tab="${tabName}"]`).addClass('active');
            
            modal.find('.tab-content').hide();
            modal.find(`#${tabName}`).show();
            
            this.loadUserMaps(tabName);
        }
        
        // ユーザーマップを読み込み
        loadUserMaps(type) {
            const listContainer = $(`#${type.replace('-', '-')}-list`);
            listContainer.html('<div class="loading">読み込み中...</div>');
            
            $.post(mindmapData.ajaxurl, {
                action: 'load_user_maps',
                type: type,
                nonce: mindmapData.nonce
            }).done((response) => {
                if (response.success) {
                    this.renderMapsList(listContainer, response.data, type);
                } else {
                    listContainer.html('<div class="error">読み込みに失敗しました</div>');
                }
            });
        }
        
        // マップリストを描画
        renderMapsList(container, maps, type) {
            if (maps.length === 0) {
                container.html('<div class="no-maps">マップがありません</div>');
                return;
            }
            
            const mapsHtml = maps.map(map => `
                <div class="map-card" data-map-id="${map.id}">
                    <div class="map-preview">
                        <div class="map-icon">🗺️</div>
                        <div class="map-stats">${map.node_count} ノード</div>
                    </div>
                    <div class="map-info">
                        <h4 class="map-title">${map.title}</h4>
                        <p class="map-description">${map.description || '説明なし'}</p>
                        ${map.author_name ? `<p class="map-author">作成者: ${map.author_name}</p>` : ''}
                        <div class="map-meta">
                            <span class="map-date">${new Date(map.updated_at).toLocaleDateString()}</span>
                            ${map.is_public ? '<span class="badge public">公開</span>' : ''}
                            ${map.is_template ? '<span class="badge template">テンプレート</span>' : ''}
                        </div>
                    </div>
                    <div class="map-actions">
                        <button class="btn btn-primary btn-sm" data-action="open-map">開く</button>
                        ${type === 'my-maps' ? '<button class="btn btn-secondary btn-sm" data-action="edit-map">編集</button>' : ''}
                        ${type !== 'my-maps' ? '<button class="btn btn-secondary btn-sm" data-action="clone-map">複製</button>' : ''}
                    </div>
                </div>
            `).join('');
            
            container.html(mapsHtml);
            
            // イベントハンドラを設定
            this.bindMapCardEvents(container);
        }
        
        // マップカードのイベントを設定
        bindMapCardEvents(container) {
            container.find('[data-action="open-map"]').on('click', (e) => {
                const mapId = $(e.target).closest('.map-card').data('map-id');
                window.location.href = `?mindmap=${mapId}`;
            });
            
            container.find('[data-action="edit-map"]').on('click', (e) => {
                const mapId = $(e.target).closest('.map-card').data('map-id');
                window.location.href = `?edit_map=${mapId}`;
            });
            
            container.find('[data-action="clone-map"]').on('click', (e) => {
                const mapId = $(e.target).closest('.map-card').data('map-id');
                this.cloneMap(mapId);
            });
        }
        
        // マップを複製
        cloneMap(mapId) {
            $.post(mindmapData.ajaxurl, {
                action: 'clone_map',
                source_map_id: mapId,
                nonce: mindmapData.nonce
            }).done((response) => {
                if (response.success) {
                    this.showSaveNotification('マップが複製されました！');
                    this.loadUserMaps('my-maps'); // リストを更新
                } else {
                    this.showSaveNotification('複製に失敗しました: ' + response.data, 'error');
                }
            });
        }
        
        // マップをエクスポート
        exportMap() {
            if (!this.customId) {
                this.showSaveNotification('エクスポートできるマップがありません', 'error');
                return;
            }
            
            $.post(mindmapData.ajaxurl, {
                action: 'export_map',
                map_id: this.customId,
                nonce: mindmapData.nonce
            }).done((response) => {
                if (response.success) {
                    const dataStr = JSON.stringify(response.data, null, 2);
                    const dataBlob = new Blob([dataStr], {type: 'application/json'});
                    const url = URL.createObjectURL(dataBlob);
                    
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `mindmap_${this.customId}_${new Date().toISOString().slice(0,10)}.json`;
                    link.click();
                    
                    URL.revokeObjectURL(url);
                    this.showSaveNotification('エクスポートが完了しました！');
                } else {
                    this.showSaveNotification('エクスポートに失敗しました: ' + response.data, 'error');
                }
            });
        }
        
        // マップをインポート
        importMap(file) {
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const importData = JSON.parse(e.target.result);
                    
                    $.post(mindmapData.ajaxurl, {
                        action: 'import_map',
                        import_data: JSON.stringify(importData),
                        nonce: mindmapData.nonce
                    }).done((response) => {
                        if (response.success) {
                            this.showSaveNotification('インポートが完了しました！');
                            this.loadUserMaps('my-maps'); // リストを更新
                        } else {
                            this.showSaveNotification('インポートに失敗しました: ' + response.data, 'error');
                        }
                    });
                } catch (error) {
                    this.showSaveNotification('無効なファイル形式です', 'error');
                }
            };
            reader.readAsText(file);
        }
        
        // 設定フォームに現在の値を設定
        populateSettingsForm(modal) {
            const settings = this.getCurrentSettings();
            
            modal.find('[name="theme"]').val(settings.theme || 'light');
            modal.find('[name="node_style"]').val(settings.node_style || 'rounded');
            modal.find('[name="is_public"]').prop('checked', settings.is_public || false);
            modal.find('[name="allow_copy"]').prop('checked', settings.allow_copy || false);
        }
        
        // 現在の設定を取得
        getCurrentSettings() {
            return {
                theme: this.container.hasClass('dark-mode') ? 'dark' : 'light',
                node_style: 'rounded', // 実装に応じて調整
                is_public: false, // DBから取得する必要がある
                allow_copy: false
            };
        }
        
        // マップ設定を保存
        saveMapSettings(modal) {
            const formData = new FormData(modal.find('.mindmap-settings-form')[0]);
            
            const settings = {
                theme: formData.get('theme'),
                node_style: formData.get('node_style'),
                is_public: formData.get('is_public') ? 1 : 0,
                allow_copy: formData.get('allow_copy') ? 1 : 0
            };
            
            // テーマを即座に適用
            this.applyTheme(settings.theme);
            
            if (this.customId) {
                $.post(mindmapData.ajaxurl, {
                    action: 'save_custom_map',
                    map_id: this.customId,
                    map_data: JSON.stringify(this.data),
                    settings: JSON.stringify(settings),
                    nonce: mindmapData.nonce
                }).done((response) => {
                    if (response.success) {
                        this.showSaveNotification('設定が保存されました！');
                        this.closeModal(modal);
                    } else {
                        this.showSaveNotification('保存に失敗しました: ' + response.data, 'error');
                    }
                });
            } else {
                this.closeModal(modal);
            }
        }
        
        // テーマを適用
        applyTheme(theme) {
            this.container.removeClass('dark-mode blue-theme green-theme');
            
            switch (theme) {
                case 'dark':
                    this.container.addClass('dark-mode');
                    break;
                case 'blue':
                    this.container.addClass('blue-theme');
                    break;
                case 'green':
                    this.container.addClass('green-theme');
                    break;
            }
        }
        
        // マップをフィルタリング
        filterMaps(query) {
            $('.map-card').each(function() {
                const title = $(this).find('.map-title').text().toLowerCase();
                const description = $(this).find('.map-description').text().toLowerCase();
                const searchText = query.toLowerCase();
                
                if (title.includes(searchText) || description.includes(searchText)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }
        
        // モーダルを閉じる
        closeModal(modal) {
            modal.hide();
        }
    }
    
    // グローバルに新しいクラスを登録
    window.MindMapRendererPhase3A = MindMapRendererPhase3A;
    
    // Phase 3-A マインドマップの初期化
    function initializeMindMapsPhase3A() {
        $('.mindmap-container.mindmap-phase3a').each(function() {
            const container = $(this);
            const canvas = container.find('.mindmap-canvas');
            const mapType = canvas.data('mindmap-type');
            const customId = container.data('custom-id');
            
            let data;
            if (customId && mapType === 'custom') {
                // カスタムマップの場合はAjaxで読み込み
                $.post(mindmapData.ajaxurl, {
                    action: 'get_custom_map_data',
                    custom_id: customId,
                    nonce: mindmapData.nonce
                }).done((response) => {
                    if (response.success) {
                        data = response.data.data;
                        initializeRenderer();
                    } else {
                        console.error('カスタムマップの読み込みに失敗:', response.data);
                        showError();
                    }
                }).fail(() => {
                    showError();
                });
            } else if (mindmapData.sampleData && mindmapData.sampleData[mapType]) {
                data = mindmapData.sampleData[mapType];
                initializeRenderer();
            } else {
                showError();
            }
            
            function initializeRenderer() {
                container.addClass('loading');
                
                setTimeout(() => {
                    try {
                        new MindMapRendererPhase3A(container, data);
                        container.removeClass('loading');
                    } catch (error) {
                        console.error('Phase 3-A マインドマップの初期化に失敗:', error);
                        showError();
                    }
                }, 500);
            }
            
            function showError() {
                container.removeClass('loading');
                canvas.html('<div style="text-align: center; padding: 50px; color: #999;">マインドマップの読み込みに失敗しました</div>');
            }
        });
    }
    
    // Phase 3-A マインドマップを初期化
    initializeMindMapsPhase3A();
    
    // 未保存の変更がある場合の警告
    window.addEventListener('beforeunload', function(e) {
        const modifiedMaps = $('.mindmap-container').filter(function() {
            return $(this).find('[data-action="save-map"]').hasClass('modified');
        });
        
        if (modifiedMaps.length > 0) {
            e.preventDefault();
            e.returnValue = '未保存の変更があります。ページを離れますか？';
            return e.returnValue;
        }
    });
});