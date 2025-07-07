// mindmap.js - 接続線機能付き修正版
jQuery(document).ready(function($) {
    let mindMapInstances = {};
    
    // マインドマップクラス
    class MindMap {
        constructor(container) {
            this.container = $(container);
            this.canvas = this.container.find('.mindmap-canvas');
            this.svg = this.canvas.find('.mindmap-svg');
            this.mindmapId = this.canvas.data('mindmap-id');
            this.nodes = [];
            this.connections = [];
            this.selectedNode = null;
            this.isPanning = false;
            this.panStart = { x: 0, y: 0 };
            this.nodeCounter = 0;
            this.connectionCounter = 0;
            this.connectionMode = false;
            this.connectionSource = null;
            this.tempLine = null;
            this.detailPopup = null;
            
            this.init();
        }
        
        init() {
            this.setupEventListeners();
            this.createRootNode();
            console.log('MindMap initialized for ID:', this.mindmapId);
        }
        
        setupEventListeners() {
            const self = this;
            
            // ツールバーイベント（イベント委譲を使用）
            this.container.on('click', '#add-node-btn', function(e) {
                e.preventDefault();
                self.addNode();
            });
            
            this.container.on('click', '#connect-mode-btn', function(e) {
                e.preventDefault();
                self.toggleConnectionMode();
            });
            
            this.container.on('click', '#save-mindmap-btn', function(e) {
                e.preventDefault();
                self.saveMindMap();
            });
            
            this.container.on('click', '#fullscreen-btn', function(e) {
                e.preventDefault();
                self.toggleFullscreen();
            });
            
            this.container.on('change', '#theme-selector', function(e) {
                self.changeTheme($(this).val());
            });
            
            // キャンバスイベント
            this.canvas.on('dblclick', function(e) {
                if (e.target === self.canvas[0]) {
                    self.addNodeAtPosition(e);
                }
            });
            
            this.canvas.on('mousemove', function(e) {
                self.updateTempLine(e);
            });
            
            // SVG接続線クリックイベント
            this.svg.on('click', 'path.mindmap-connection', function(e) {
                if (!self.connectionMode) {
                    self.selectConnection($(this));
                }
            });
            
            // キーボードイベント
            $(document).on('keydown', function(e) {
                if (self.container.is(':visible')) {
                    self.handleKeydown(e);
                }
            });
            
            $(document).on('keyup', function(e) {
                if (self.container.is(':visible')) {
                    self.handleKeyup(e);
                }
            });
        }
        
        createRootNode() {
            if (this.nodes.length === 0) {
                const canvasWidth = this.canvas.width() || 800;
                const canvasHeight = this.canvas.height() || 600;
                
                const rootNode = this.createNode({
                    x: Math.max(canvasWidth / 2 - 60, 50),
                    y: Math.max(canvasHeight / 2 - 20, 50),
                    text: 'メインテーマ',
                    type: 'root'
                });
                this.nodes.push(rootNode);
                this.updateNodeCount();
            }
        }
        
        createNode(options) {
            const node = {
                id: 'node_' + (++this.nodeCounter),
                x: options.x || 100,
                y: options.y || 100,
                text: options.text || '新しいノード',
                content: options.content || '',
                type: options.type || 'child',
                understood: options.understood || false,
                element: null
            };
            
            // ノード要素を作成
            const nodeElement = $(`
                <div class="mindmap-node ${node.type}-node new-node ${node.understood ? 'understood' : ''}" data-node-id="${node.id}">
                    <span class="node-text">${this.escapeHtml(node.text)}</span>
                </div>
            `);
            
            nodeElement.css({
                left: node.x + 'px',
                top: node.y + 'px'
            });
            
            // ノードイベント
            const self = this;
            nodeElement.on('click', function(e) {
                e.stopPropagation();
                if (self.connectionMode) {
                    self.handleConnectionClick(node);
                } else {
                    self.selectNode(node);
                }
            });
            
            nodeElement.on('dblclick', function(e) {
                e.stopPropagation();
                if (!self.connectionMode) {
                    self.editNode(node);
                }
            });
            
            // ホバーイベント（詳細表示）
            nodeElement.on('mouseenter', function(e) {
                if (!self.connectionMode && node.content) {
                    self.showNodeDetail(node, e);
                }
            });
            
            nodeElement.on('mouseleave', function() {
                self.hideNodeDetail();
            });
            
            // ドラッグ機能
            this.makeNodeDraggable(nodeElement, node);
            
            this.canvas.append(nodeElement);
            node.element = nodeElement;
            
            // アニメーション
            setTimeout(() => {
                nodeElement.removeClass('new-node');
            }, 300);
            
            return node;
        }
        
        makeNodeDraggable(element, node) {
            let isDragging = false;
            let dragStart = { x: 0, y: 0 };
            
            element.on('mousedown', function(e) {
                if (e.which === 1 && !element.parent().parent().hasClass('connection-mode')) {
                    isDragging = true;
                    const offset = element.offset();
                    const canvasOffset = element.parent().offset();
                    
                    dragStart = {
                        x: e.pageX - (offset.left - canvasOffset.left),
                        y: e.pageY - (offset.top - canvasOffset.top)
                    };
                    
                    element.addClass('dragging');
                    e.preventDefault();
                }
            });
            
            $(document).on('mousemove.drag' + node.id, function(e) {
                if (isDragging) {
                    const canvasOffset = element.parent().offset();
                    node.x = e.pageX - canvasOffset.left - dragStart.x;
                    node.y = e.pageY - canvasOffset.top - dragStart.y;
                    
                    // 境界チェック
                    node.x = Math.max(0, Math.min(node.x, element.parent().width() - element.outerWidth()));
                    node.y = Math.max(0, Math.min(node.y, element.parent().height() - element.outerHeight()));
                    
                    element.css({
                        left: node.x + 'px',
                        top: node.y + 'px'
                    });
                    
                    // 接続線を更新
                    element.parent().parent().find('.mindmap-container').each(function() {
                        const instance = mindMapInstances[$(this).find('.mindmap-canvas').data('mindmap-id')];
                        if (instance) {
                            instance.updateConnections();
                        }
                    });
                }
            });
            
            $(document).on('mouseup.drag' + node.id, function() {
                if (isDragging) {
                    isDragging = false;
                    element.removeClass('dragging');
                }
            });
        }
        
        addNode() {
            const canvasWidth = this.canvas.width() || 800;
            const canvasHeight = this.canvas.height() || 600;
            
            const newNode = this.createNode({
                x: Math.random() * (canvasWidth - 200) + 50,
                y: Math.random() * (canvasHeight - 100) + 50,
                text: '新しいノード'
            });
            
            this.nodes.push(newNode);
            this.updateNodeCount();
            
            // 少し待ってから編集モードに
            setTimeout(() => {
                this.editNode(newNode);
            }, 100);
        }
        
        addNodeAtPosition(e) {
            const rect = this.canvas[0].getBoundingClientRect();
            const x = e.clientX - rect.left - 60;
            const y = e.clientY - rect.top - 20;
            
            const newNode = this.createNode({
                x: Math.max(0, x),
                y: Math.max(0, y),
                text: '新しいノード'
            });
            
            this.nodes.push(newNode);
            this.updateNodeCount();
            
            setTimeout(() => {
                this.editNode(newNode);
            }, 100);
        }
        
        selectNode(node) {
            // 他のノードの選択を解除
            this.nodes.forEach(n => n.element.removeClass('selected'));
            
            // 接続線の選択も解除
            this.svg.find('path.mindmap-connection').removeClass('selected');
            
            // 選択したノードをハイライト
            node.element.addClass('selected');
            this.selectedNode = node;
        }
        
        selectConnection(connectionElement) {
            // 他の選択を解除
            this.nodes.forEach(n => n.element.removeClass('selected'));
            this.svg.find('path.mindmap-connection').removeClass('selected');
            
            // 接続線を選択
            connectionElement.addClass('selected');
            this.selectedNode = null;
        }
        
        editNode(node) {
            // 既存の編集フォームを削除
            this.canvas.find('.node-edit-form').remove();
            
            const formX = Math.min(node.x, this.canvas.width() - 320);
            const formY = Math.min(node.y + 60, this.canvas.height() - 200);
            
            const form = $(`
                <div class="node-edit-form" style="left: ${formX}px; top: ${formY}px;">
                    <input type="text" class="node-title-input" value="${this.escapeHtml(node.text)}" placeholder="ノードのタイトル">
                    <textarea class="node-content-input" placeholder="ノードの詳細内容（学習メモなど）" rows="3">${this.escapeHtml(node.content || '')}</textarea>
                    <div class="form-buttons">
                        <button class="btn-save">💾 保存</button>
                        <button class="btn-cancel">❌ キャンセル</button>
                        <button class="btn-understand ${node.understood ? 'understood' : ''}">${node.understood ? '❓ 未理解' : '✅ 理解'}</button>
                        ${node.type !== 'root' ? '<button class="btn-delete">🗑️ 削除</button>' : ''}
                    </div>
                </div>
            `);
            
            // フォームイベント
            const self = this;
            form.find('.btn-save').on('click', function() {
                const newText = form.find('.node-title-input').val();
                const newContent = form.find('.node-content-input').val();
                if (newText.trim()) {
                    node.text = newText.trim();
                    node.content = newContent.trim();
                    node.element.find('.node-text').text(node.text);
                }
                form.remove();
            });
            
            form.find('.btn-cancel').on('click', function() {
                form.remove();
            });
            
            form.find('.btn-understand').on('click', function() {
                self.toggleNodeUnderstanding(node);
                $(this).toggleClass('understood');
                $(this).text(node.understood ? '❓ 未理解' : '✅ 理解');
            });
            
            form.find('.btn-delete').on('click', function() {
                if (confirm('このノードを削除しますか？')) {
                    self.deleteNode(node);
                    form.remove();
                }
            });
            
            // Enterキーで保存
            form.find('.node-title-input').on('keypress', function(e) {
                if (e.which === 13) {
                    form.find('.btn-save').click();
                }
            });
            
            // Escapeキーでキャンセル
            form.on('keydown', function(e) {
                if (e.which === 27) {
                    form.remove();
                }
            });
            
            this.canvas.append(form);
            form.find('.node-title-input').select();
        }
        
        toggleNodeUnderstanding(node) {
            node.understood = !node.understood;
            if (node.understood) {
                node.element.addClass('understood');
            } else {
                node.element.removeClass('understood');
            }
        }
        
        deleteNode(node) {
            if (node.type === 'root') return; // ルートノードは削除不可
            
            // このノードに関連する接続線を削除
            this.connections = this.connections.filter(conn => {
                if (conn.startId === node.id || conn.endId === node.id) {
                    this.svg.find(`path[data-connection-id="${conn.id}"]`).remove();
                    return false;
                }
                return true;
            });
            
            // ドラッグイベントを削除
            $(document).off('mousemove.drag' + node.id);
            $(document).off('mouseup.drag' + node.id);
            
            // ノードを配列から削除
            this.nodes = this.nodes.filter(n => n.id !== node.id);
            
            // DOM要素を削除
            node.element.remove();
            
            // 選択状態をクリア
            if (this.selectedNode === node) {
                this.selectedNode = null;
            }
            
            this.updateNodeCount();
            this.updateConnectionCount();
        }
        
        // 接続モードの切り替え
        toggleConnectionMode() {
            this.connectionMode = !this.connectionMode;
            const btn = this.container.find('#connect-mode-btn');
            
            if (this.connectionMode) {
                btn.text('🔗 接続終了').addClass('active');
                this.container.addClass('connection-mode');
                this.nodes.forEach(node => node.element.addClass('connection-mode'));
            } else {
                btn.text('🔗 接続モード').removeClass('active');
                this.container.removeClass('connection-mode');
                this.nodes.forEach(node => {
                    node.element.removeClass('connection-mode connection-source');
                });
                this.connectionSource = null;
                this.removeTempLine();
            }
        }
        
        // 接続クリックの処理
        handleConnectionClick(node) {
            if (!this.connectionSource) {
                // 接続の開始点を設定
                this.connectionSource = node;
                node.element.addClass('connection-source');
            } else {
                // 接続の終了点を設定
                if (this.connectionSource.id !== node.id) {
                    this.createConnection(this.connectionSource, node);
                }
                
                // 接続状態をリセット
                this.connectionSource.element.removeClass('connection-source');
                this.connectionSource = null;
                this.removeTempLine();
            }
        }
        
        // 接続線の作成
        createConnection(startNode, endNode) {
            // 既存の接続があるかチェック
            const existingConnection = this.connections.find(conn => 
                (conn.startId === startNode.id && conn.endId === endNode.id) ||
                (conn.startId === endNode.id && conn.endId === startNode.id)
            );
            
            if (existingConnection) return; // 既に接続済み
            
            const connection = {
                id: 'conn_' + (++this.connectionCounter),
                startId: startNode.id,
                endId: endNode.id,
                label: ''
            };
            
            this.connections.push(connection);
            this.drawConnection(connection);
            this.updateConnectionCount();
        }
        
        // 接続線の描画
        drawConnection(connection) {
            const startNode = this.nodes.find(n => n.id === connection.startId);
            const endNode = this.nodes.find(n => n.id === connection.endId);
            
            if (!startNode || !endNode) return;
            
            const startX = startNode.x + startNode.element.outerWidth() / 2;
            const startY = startNode.y + startNode.element.outerHeight() / 2;
            const endX = endNode.x + endNode.element.outerWidth() / 2;
            const endY = endNode.y + endNode.element.outerHeight() / 2;
            
            // ベジェ曲線で接続線を描画
            const midX = (startX + endX) / 2;
            const midY = (startY + endY) / 2;
            const offsetY = Math.abs(endX - startX) * 0.3;
            
            const path = `M ${startX} ${startY} Q ${midX} ${midY - offsetY} ${endX} ${endY}`;
            
            const pathElement = $(`
                <path d="${path}" 
                      class="mindmap-connection" 
                      data-connection-id="${connection.id}"
                      data-start-id="${connection.startId}"
                      data-end-id="${connection.endId}" />
            `);
            
            this.svg.append(pathElement);
        }
        
        // 全接続線の更新
        updateConnections() {
            // SVG内の既存の接続線をクリア
            this.svg.find('path.mindmap-connection').remove();
            
            // 全ての接続線を再描画
            this.connections.forEach(connection => {
                this.drawConnection(connection);
            });
        }
        
        // 一時的な線の更新
        updateTempLine(e) {
            if (!this.connectionMode || !this.connectionSource) return;
            
            const rect = this.canvas[0].getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;
            
            const startX = this.connectionSource.x + this.connectionSource.element.outerWidth() / 2;
            const startY = this.connectionSource.y + this.connectionSource.element.outerHeight() / 2;
            
            this.removeTempLine();
            
            const midX = (startX + mouseX) / 2;
            const midY = (startY + mouseY) / 2;
            const offsetY = Math.abs(mouseX - startX) * 0.3;
            
            const path = `M ${startX} ${startY} Q ${midX} ${midY - offsetY} ${mouseX} ${mouseY}`;
            
            this.tempLine = $(`<path d="${path}" class="mindmap-connection creating"/>`);
            this.svg.append(this.tempLine);
        }
        
        // 一時的な線の削除
        removeTempLine() {
            if (this.tempLine) {
                this.tempLine.remove();
                this.tempLine = null;
            }
        }
        
        // ノード詳細の表示
        showNodeDetail(node, e) {
            if (!node.content) return;
            
            this.hideNodeDetail();
            
            const popup = $(`
                <div class="node-detail-popup">
                    <div class="popup-title">${this.escapeHtml(node.text)}</div>
                    <div class="popup-content">${this.escapeHtml(node.content).replace(/\n/g, '<br>')}</div>
                </div>
            `);
            
            const nodeRect = node.element[0].getBoundingClientRect();
            const canvasRect = this.canvas[0].getBoundingClientRect();
            
            const popupX = nodeRect.left - canvasRect.left + nodeRect.width + 10;
            const popupY = nodeRect.top - canvasRect.top;
            
            popup.css({
                left: popupX + 'px',
                top: popupY + 'px'
            });
            
            this.canvas.append(popup);
            this.detailPopup = popup;
            
            setTimeout(() => popup.addClass('show'), 10);
        }
        
        hideNodeDetail() {
            if (this.detailPopup) {
                this.detailPopup.removeClass('show');
                setTimeout(() => {
                    if (this.detailPopup) {
                        this.detailPopup.remove();
                        this.detailPopup = null;
                    }
                }, 300);
            }
        }
        
        updateNodeCount() {
            this.container.find('#node-count').text(`ノード数: ${this.nodes.length}`);
        }
        
        updateConnectionCount() {
            this.container.find('#connection-count').text(`接続数: ${this.connections.length}`);
        }
        
        handleKeydown(e) {
            if (e.key === 'Delete' && this.selectedNode) {
                if (confirm('選択されたノードを削除しますか？')) {
                    this.deleteNode(this.selectedNode);
                    this.selectedNode = null;
                }
            }
            
            if (e.key === 'Delete' && this.svg.find('path.mindmap-connection.selected').length > 0) {
                if (confirm('選択された接続線を削除しますか？')) {
                    const selectedPath = this.svg.find('path.mindmap-connection.selected');
                    const connectionId = selectedPath.data('connection-id');
                    this.deleteConnection(connectionId);
                }
            }
            
            if (e.key === 'Escape') {
                this.canvas.find('.node-edit-form').remove();
                this.nodes.forEach(n => n.element.removeClass('selected'));
                this.svg.find('path.mindmap-connection').removeClass('selected');
                this.selectedNode = null;
                
                if (this.connectionMode) {
                    this.toggleConnectionMode();
                }
            }
            
            if (e.key === 'Control' || e.key === 'Meta') {
                if (!this.connectionMode) {
                    this.toggleConnectionMode();
                }
            }
        }
        
        handleKeyup(e) {
            if (e.key === 'Control' || e.key === 'Meta') {
                if (this.connectionMode) {
                    this.toggleConnectionMode();
                }
            }
        }
        
        deleteConnection(connectionId) {
            this.connections = this.connections.filter(conn => conn.id !== connectionId);
            this.svg.find(`path[data-connection-id="${connectionId}"]`).remove();
            this.updateConnectionCount();
        }
        
        changeTheme(theme) {
            this.container.removeClass('theme-default theme-dark theme-study');
            this.container.addClass('theme-' + theme);
        }
        
        toggleFullscreen() {
            this.container.toggleClass('fullscreen');
            
            if (this.container.hasClass('fullscreen')) {
                this.container.find('#fullscreen-btn').text('🔍 通常表示');
                $('body').addClass('mindmap-fullscreen-active');
            } else {
                this.container.find('#fullscreen-btn').text('🔍 フルスクリーン');
                $('body').removeClass('mindmap-fullscreen-active');
            }
        }
        
        loadData(data) {
            try {
                const parsedData = typeof data === 'string' ? JSON.parse(data) : data;
                
                // 既存のノードをクリア
                this.nodes.forEach(node => {
                    if (node.element) {
                        $(document).off('mousemove.drag' + node.id);
                        $(document).off('mouseup.drag' + node.id);
                        node.element.remove();
                    }
                });
                this.nodes = [];
                this.connections = [];
                this.nodeCounter = 0;
                this.connectionCounter = 0;
                this.svg.find('path').remove();
                
                // ノードを再作成
                if (parsedData.nodes && Array.isArray(parsedData.nodes)) {
                    parsedData.nodes.forEach(nodeData => {
                        const node = this.createNode({
                            x: nodeData.x || 100,
                            y: nodeData.y || 100,
                            text: nodeData.text || '新しいノード',
                            content: nodeData.content || '',
                            type: nodeData.type || 'child',
                            understood: nodeData.understood || false
                        });
                        this.nodes.push(node);
                        
                        // カウンターを更新
                        if (nodeData.id) {
                            const nodeNum = parseInt(nodeData.id.replace('node_', ''));
                            if (nodeNum > this.nodeCounter) {
                                this.nodeCounter = nodeNum;
                            }
                        }
                    });
                    this.updateNodeCount();
                }
                
                // 接続線を再作成
                if (parsedData.connections && Array.isArray(parsedData.connections)) {
                    this.connections = parsedData.connections;
                    this.connections.forEach(conn => {
                        if (conn.id) {
                            const connNum = parseInt(conn.id.replace('conn_', ''));
                            if (connNum > this.connectionCounter) {
                                this.connectionCounter = connNum;
                            }
                        }
                    });
                    this.updateConnections();
                    this.updateConnectionCount();
                }
                
                console.log('Data loaded successfully:', parsedData);
            } catch (error) {
                console.error('Error loading mindmap data:', error);
            }
        }
        
        saveMindMap() {
            if (!this.mindmapId) {
                alert('マインドマップIDが設定されていません');
                return;
            }
            
            const data = {
                nodes: this.nodes.map(node => ({
                    id: node.id,
                    x: node.x,
                    y: node.y,
                    text: node.text,
                    content: node.content,
                    type: node.type,
                    understood: node.understood
                })),
                connections: this.connections.map(conn => ({
                    id: conn.id,
                    startId: conn.startId,
                    endId: conn.endId,
                    label: conn.label || ''
                }))
            };
            
            this.container.find('#save-status').text('保存中...').removeClass().addClass('save-status saving');
            
            $.post(mindmap_ajax.ajax_url, {
                action: 'save_mindmap',
                mindmap_id: this.mindmapId,
                mindmap_data: JSON.stringify(data),
                nonce: mindmap_ajax.nonce
            })
            .done((response) => {
                if (response.success) {
                    this.container.find('#save-status').text('✅ 保存済み').removeClass().addClass('save-status saved');
                    setTimeout(() => {
                        this.container.find('#save-status').text('未保存').removeClass().addClass('save-status');
                    }, 3000);
                } else {
                    this.container.find('#save-status').text('❌ 保存エラー').removeClass().addClass('save-status error');
                    console.error('Save error:', response);
                }
            })
            .fail((xhr, status, error) => {
                this.container.find('#save-status').text('❌ 保存エラー').removeClass().addClass('save-status error');
                console.error('Save failed:', error);
            });
        }
        
        escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    }
    
    // 各マインドマップコンテナを初期化
    function initializeMindMaps() {
        $('.mindmap-container').each(function() {
            const containerId = $(this).attr('id');
            if (containerId && !mindMapInstances[containerId]) {
                const mindmapId = $(this).find('.mindmap-canvas').data('mindmap-id');
                if (mindmapId) {
                    const instance = new MindMap(this);
                    mindMapInstances[mindmapId] = instance;
                    console.log('Initialized mindmap:', mindmapId);
                }
            }
        });
    }
    
    // 初期化実行
    initializeMindMaps();
    
    // 動的に追加されたコンテンツにも対応
    $(document).on('DOMNodeInserted', function(e) {
        if ($(e.target).hasClass('mindmap-container') || $(e.target).find('.mindmap-container').length > 0) {
            setTimeout(initializeMindMaps, 100);
        }
    });
    
    // グローバル関数（外部から操作可能）
    window.getMindMapInstance = function(mindmapId) {
        return mindMapInstances[mindmapId];
    };
    
    // フルスクリーンイベント
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('.mindmap-container.fullscreen').each(function() {
                $(this).removeClass('fullscreen');
                $(this).find('#fullscreen-btn').text('🔍 フルスクリーン');
                $('body').removeClass('mindmap-fullscreen-active');
            });
        }
    });
});