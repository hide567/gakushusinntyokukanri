// 行政書士の道 - マインドマップ統合JavaScript

jQuery(document).ready(function($) {
    
    // ==========================================================================
    // 基本マインドマップレンダラー
    // ==========================================================================
    
    class MindMapRenderer {
        constructor(container, data) {
            this.container = $(container);
            this.canvas = this.container.find('.mindmap-canvas');
            this.data = data;
            this.scale = 1;
            this.translateX = 0;
            this.translateY = 0;
            this.isDragging = false;
            this.isFullscreen = false;
            this.lastX = 0;
            this.lastY = 0;
            this.currentTooltip = null;
            this.tooltipTimeout = null;
            
            // 機能レベルの判定
            this.phaseLevel = parseInt(this.container.data('phase-level')) || 1;
            this.featuresLoaded = window.mindmapData?.featuresLoaded || [];
            
            this.init();
        }
        
        init() {
            this.createViewport();
            this.loadTheme();
            this.renderMindMap();
            this.bindEvents();
            this.centerMap();
            
            // 機能レベルに応じて追加機能を初期化
            if (this.phaseLevel >= 2) {
                this.initPhase2Features();
            }
            if (this.phaseLevel >= 3) {
                this.initPhase3Features();
            }
        }
        
        createViewport() {
            if (this.canvas.find('.mindmap-viewport').length === 0) {
                const viewport = $('<div class="mindmap-viewport"></div>');
                this.canvas.append(viewport);
            }
            this.viewport = this.canvas.find('.mindmap-viewport');
        }
        
        renderMindMap() {
            this.viewport.empty();
            this.renderConnections();
            this.renderNodes();
        }
        
        renderNodes() {
            const nodes = this.data.nodes || [];
            
            nodes.forEach(node => {
                const nodeElement = this.createNodeElement(node);
                this.viewport.append(nodeElement);
            });
        }
        
        createNodeElement(node) {
            const progressWidth = node.progress || 0;
            const statusClass = node.status || 'not-started';
            const icon = node.icon || '';
            
            const nodeEl = $(`
                <div class="mindmap-node level-${node.level} ${statusClass}" 
                     data-node-id="${node.id}"
                     style="left: ${node.x}px; top: ${node.y}px; border-color: ${node.color};">
                    <div class="mindmap-node-content">
                        ${icon ? `<span class="mindmap-node-icon">${icon}</span>` : ''}
                        <span class="mindmap-node-text">${node.text}</span>
                    </div>
                    <div class="mindmap-progress-bar">
                        <div class="mindmap-progress-fill" style="width: ${progressWidth}%"></div>
                    </div>
                    ${this.phaseLevel >= 2 ? '<div class="mindmap-node-detail-icon">ℹ️</div>' : ''}
                </div>
            `);
            
            // ノードイベント
            this.bindNodeEvents(nodeEl, node);
            
            return nodeEl;
        }
        
        bindNodeEvents(nodeEl, node) {
            // ホバー効果
            nodeEl.on('mouseenter', (e) => {
                if (this.phaseLevel >= 1) {
                    this.showTooltip(e, node);
                }
            }).on('mouseleave', () => {
                this.hideTooltip();
            });
            
            // クリックイベント
            nodeEl.on('click', (e) => {
                e.stopPropagation();
                this.onNodeClick(node);
            });
            
            // Phase3の編集機能
            if (this.phaseLevel >= 3 && this.container.hasClass('editable')) {
                nodeEl.on('dblclick', (e) => {
                    e.stopPropagation();
                    this.editNodeText(nodeEl, node);
                });
                
                nodeEl.on('contextmenu', (e) => {
                    e.preventDefault();
                    this.showNodeContextMenu(e, nodeEl, node);
                });
            }
        }
        
        renderConnections() {
            const connections = this.data.connections || [];
            const svg = $('<svg class="mindmap-connection" style="width: 100%; height: 100%; position: absolute; top: 0; left: 0;"></svg>');
            
            connections.forEach(conn => {
                const fromNode = this.data.nodes.find(n => n.id === conn.from);
                const toNode = this.data.nodes.find(n => n.id === conn.to);
                
                if (fromNode && toNode) {
                    const line = this.createConnectionLine(fromNode, toNode);
                    svg.append(line);
                }
            });
            
            this.viewport.prepend(svg);
        }
        
        createConnectionLine(fromNode, toNode) {
            const x1 = fromNode.x;
            const y1 = fromNode.y;
            const x2 = toNode.x;
            const y2 = toNode.y;
            
            // 曲線パスを作成
            const midX = (x1 + x2) / 2;
            const midY = (y1 + y2) / 2;
            const offset = Math.abs(x2 - x1) * 0.3;
            
            const path = `M ${x1} ${y1} Q ${midX + offset} ${midY} ${x2} ${y2}`;
            
            return $(`<path d="${path}" class="mindmap-line level-${fromNode.level}" />`);
        }
        
        bindEvents() {
            // コントロールボタンイベント
            this.container.find('[data-action="zoom-in"]').on('click', () => this.zoomIn());
            this.container.find('[data-action="zoom-out"]').on('click', () => this.zoomOut());
            this.container.find('[data-action="reset"]').on('click', () => this.resetView());
            this.container.find('[data-action="fullscreen"]').on('click', () => this.toggleFullscreen());
            this.container.find('[data-action="toggle-theme"]').on('click', () => this.toggleTheme());
            
            // パン操作
            this.canvas.on('mousedown', (e) => this.startPan(e));
            $(document).on('mousemove', (e) => this.doPan(e));
            $(document).on('mouseup', () => this.endPan());
            
            // ホイールズーム
            this.canvas.on('wheel', (e) => this.onWheel(e));
            
            // タッチ操作
            this.canvas.on('touchstart', (e) => this.startTouch(e));
            this.canvas.on('touchmove', (e) => this.doTouch(e));
            this.canvas.on('touchend', () => this.endTouch());
        }
        
        onNodeClick(node) {
            console.log('Node clicked:', node);
            
            // Phase2の詳細機能
            if (this.phaseLevel >= 2 && this.container.find('.mindmap-canvas').data('details') === 'true') {
                this.showDetailModal(node);
            } else {
                // 基本的な視覚フィードバック
                const nodeEl = this.viewport.find(`[data-node-id="${node.id}"]`);
                nodeEl.animate({
                    transform: 'translate(-50%, -50%) scale(1.2)'
                }, 200).animate({
                    transform: 'translate(-50%, -50%) scale(1.0)'
                }, 200);
            }
        }
        
        // パン・ズーム機能
        startPan(e) {
            if (e.target === this.canvas[0] || $(e.target).hasClass('mindmap-viewport')) {
                this.isDragging = true;
                this.lastX = e.clientX;
                this.lastY = e.clientY;
                this.canvas.addClass('dragging');
            }
        }
        
        doPan(e) {
            if (this.isDragging) {
                const deltaX = e.clientX - this.lastX;
                const deltaY = e.clientY - this.lastY;
                
                this.translateX += deltaX;
                this.translateY += deltaY;
                
                this.updateTransform();
                
                this.lastX = e.clientX;
                this.lastY = e.clientY;
            }
        }
        
        endPan() {
            this.isDragging = false;
            this.canvas.removeClass('dragging');
        }
        
        onWheel(e) {
            e.preventDefault();
            
            const delta = e.originalEvent.deltaY;
            const zoomFactor = delta > 0 ? 0.9 : 1.1;
            
            const rect = this.canvas[0].getBoundingClientRect();
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            this.zoomAt(centerX, centerY, zoomFactor);
        }
        
        zoomAt(x, y, factor) {
            const newScale = Math.max(0.1, Math.min(3, this.scale * factor));
            
            if (newScale !== this.scale) {
                this.translateX = x - (x - this.translateX) * (newScale / this.scale);
                this.translateY = y - (y - this.translateY) * (newScale / this.scale);
                
                this.scale = newScale;
                this.updateTransform();
            }
        }
        
        zoomIn() {
            const rect = this.canvas[0].getBoundingClientRect();
            this.zoomAt(rect.width / 2, rect.height / 2, 1.2);
        }
        
        zoomOut() {
            const rect = this.canvas[0].getBoundingClientRect();
            this.zoomAt(rect.width / 2, rect.height / 2, 0.8);
        }
        
        resetView() {
            this.scale = 1;
            this.centerMap();
        }
        
        centerMap() {
            if (!this.data.nodes || this.data.nodes.length === 0) return;
            
            const nodes = this.data.nodes;
            const minX = Math.min(...nodes.map(n => n.x)) - 100;
            const maxX = Math.max(...nodes.map(n => n.x)) + 100;
            const minY = Math.min(...nodes.map(n => n.y)) - 50;
            const maxY = Math.max(...nodes.map(n => n.y)) + 50;
            
            const mapWidth = maxX - minX;
            const mapHeight = maxY - minY;
            
            const canvasWidth = this.canvas.width();
            const canvasHeight = this.canvas.height();
            
            this.translateX = (canvasWidth - mapWidth) / 2 - minX;
            this.translateY = (canvasHeight - mapHeight) / 2 - minY;
            
            this.updateTransform();
        }
        
        updateTransform() {
            this.viewport.css({
                transform: `translate(${this.translateX}px, ${this.translateY}px) scale(${this.scale})`
            });
        }
        
        // ツールチップ
        showTooltip(e, node) {
            if (this.tooltipTimeout) {
                clearTimeout(this.tooltipTimeout);
            }
            
            const progressText = node.progress ? `進捗: ${node.progress}%` : '未開始';
            const statusText = {
                'completed': '✅ 完了',
                'in-progress': '🔄 学習中',
                'not-started': '⏳ 未開始'
            }[node.status] || '未開始';
            
            const tooltip = $(`
                <div class="mindmap-tooltip">
                    <div style="font-weight: bold; margin-bottom: 5px;">
                        ${node.icon || ''} ${node.text}
                    </div>
                    <div style="font-size: 11px; color: #ccc; margin-bottom: 3px;">
                        ${statusText} | ${progressText}
                    </div>
                    <div style="font-size: 11px;">
                        ${node.description || 'クリックで詳細を表示'}
                    </div>
                </div>
            `);
            
            this.container.append(tooltip);
            
            const rect = this.container[0].getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top - 40;
            
            tooltip.css({
                left: x - tooltip.outerWidth() / 2,
                top: y,
                position: 'absolute',
                background: '#333',
                color: 'white',
                padding: '8px 12px',
                borderRadius: '4px',
                fontSize: '12px',
                pointerEvents: 'none',
                zIndex: 1000,
                maxWidth: '200px',
                opacity: 0,
                transition: 'opacity 0.2s ease'
            });
            
            // フェードイン効果
            setTimeout(() => tooltip.css('opacity', 1), 10);
            
            this.currentTooltip = tooltip;
        }
        
        hideTooltip() {
            if (this.currentTooltip) {
                this.tooltipTimeout = setTimeout(() => {
                    this.currentTooltip.remove();
                    this.currentTooltip = null;
                }, 200);
            }
        }
        
        // テーマ切り替え
        toggleTheme() {
            this.container.toggleClass('dark-mode');
            const isDark = this.container.hasClass('dark-mode');
            const button = this.container.find('[data-action="toggle-theme"]');
            button.text(isDark ? '☀️' : '🌙');
            
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem('mindmap-theme', isDark ? 'dark' : 'light');
            }
        }
        
        loadTheme() {
            if (typeof(Storage) !== "undefined") {
                const savedTheme = localStorage.getItem('mindmap-theme');
                if (savedTheme === 'dark') {
                    this.container.addClass('dark-mode');
                    this.container.find('[data-action="toggle-theme"]').text('☀️');
                }
            }
        }
        
        // フルスクリーン
        toggleFullscreen() {
            if (this.isFullscreen) {
                this.container.removeClass('mindmap-fullscreen');
                this.isFullscreen = false;
                this.container.find('[data-action="fullscreen"]').text('⛶');
            } else {
                this.container.addClass('mindmap-fullscreen');
                this.isFullscreen = true;
                this.container.find('[data-action="fullscreen"]').text('✕');
                setTimeout(() => this.centerMap(), 100);
            }
        }
        
        // タッチ操作
        startTouch(e) {
            const touch = e.originalEvent.touches[0];
            this.startPan({
                clientX: touch.clientX,
                clientY: touch.clientY,
                target: e.target
            });
        }
        
        doTouch(e) {
            e.preventDefault();
            const touch = e.originalEvent.touches[0];
            this.doPan({
                clientX: touch.clientX,
                clientY: touch.clientY
            });
        }
        
        endTouch() {
            this.endPan();
        }
        
        // ==========================================================================
        // Phase 2 機能: 検索・詳細表示
        // ==========================================================================
        
        initPhase2Features() {
            if (this.container.find('.mindmap-search').length > 0) {
                this.initSearch();
            }
            
            if (this.canvas.data('details') === 'true') {
                this.initDetailModal();
            }
            
            if (this.canvas.data('draggable') === 'true') {
                this.initDragDrop();
            }
        }
        
        initSearch() {
            const searchInput = this.container.find('.mindmap-search');
            const searchBtn = this.container.find('.mindmap-search-btn');
            const clearBtn = this.container.find('.mindmap-search-clear');
            
            let searchTimeout;
            
            searchInput.on('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(searchInput.val());
                }, 300);
            });
            
            searchBtn.on('click', () => {
                this.performSearch(searchInput.val());
            });
            
            clearBtn.on('click', () => {
                searchInput.val('');
                this.clearSearch();
                clearBtn.hide();
            });
            
            searchInput.on('keypress', (e) => {
                if (e.which === 13) {
                    this.performSearch(searchInput.val());
                }
            });
        }
        
        performSearch(query) {
            if (!query.trim()) {
                this.clearSearch();
                return;
            }
            
            this.container.find('.mindmap-search-clear').show();
            
            if (typeof mindmapData !== 'undefined' && mindmapData.ajaxurl) {
                this.ajaxSearch(query);
            } else {
                this.localSearch(query);
            }
        }
        
        localSearch(query) {
            const nodes = this.data.nodes || [];
            const matchingNodes = [];
            
            nodes.forEach(node => {
                const textMatch = node.text.toLowerCase().includes(query.toLowerCase());
                const descMatch = (node.description || '').toLowerCase().includes(query.toLowerCase());
                
                if (textMatch || descMatch) {
                    matchingNodes.push(node);
                }
            });
            
            this.highlightSearchResults(matchingNodes);
        }
        
        ajaxSearch(query) {
            $.post(mindmapData.ajaxurl, {
                action: 'search_nodes',
                query: query,
                map_type: this.canvas.data('mindmap-type'),
                nonce: mindmapData.nonce
            }).done((response) => {
                if (response.success) {
                    this.highlightSearchResults(response.data);
                }
            });
        }
        
        highlightSearchResults(results) {
            this.viewport.find('.mindmap-node').removeClass('search-highlighted');
            
            results.forEach((result, index) => {
                const nodeEl = this.viewport.find(`[data-node-id="${result.id}"]`);
                nodeEl.addClass('search-highlighted');
                
                if (index === 0) {
                    this.focusOnNode(result);
                }
            });
        }
        
        focusOnNode(node) {
            const canvasWidth = this.canvas.width();
            const canvasHeight = this.canvas.height();
            
            this.translateX = canvasWidth / 2 - node.x * this.scale;
            this.translateY = canvasHeight / 2 - node.y * this.scale;
            
            this.updateTransform();
        }
        
        clearSearch() {
            this.viewport.find('.mindmap-node').removeClass('search-highlighted');
            this.container.find('.mindmap-search-clear').hide();
        }
        
        initDetailModal() {
            const modalId = `mindmap-modal-${this.container.data('mindmap-id')}`;
            const modal = $(`#${modalId}`);
            
            modal.find('.mindmap-modal-close, .mindmap-modal-overlay').on('click', () => {
                this.closeDetailModal();
            });
            
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && modal.is(':visible')) {
                    this.closeDetailModal();
                }
            });
        }
        
        showDetailModal(node) {
            const modalId = `mindmap-modal-${this.container.data('mindmap-id')}`;
            const modal = $(`#${modalId}`);
            
            modal.find('.mindmap-modal-title').text(node.text);
            modal.find('.mindmap-modal-body').html('<div style="text-align: center; padding: 50px;">読み込み中...</div>');
            modal.show();
            
            this.loadNodeDetails(node.id, (details) => {
                this.populateModal(details);
            });
        }
        
        loadNodeDetails(nodeId, callback) {
            if (typeof mindmapData !== 'undefined' && mindmapData.ajaxurl) {
                $.post(mindmapData.ajaxurl, {
                    action: 'get_node_details',
                    node_id: nodeId,
                    map_type: this.canvas.data('mindmap-type'),
                    nonce: mindmapData.nonce
                }).done((response) => {
                    if (response.success) {
                        callback(response.data);
                    }
                });
            } else {
                const node = this.data.nodes.find(n => n.id === nodeId);
                if (node) {
                    callback(node);
                }
            }
        }
        
        populateModal(details) {
            const modalId = `mindmap-modal-${this.container.data('mindmap-id')}`;
            const modal = $(`#${modalId}`);
            
            // ステータス表示
            const statusBadge = `<span class="mindmap-status-badge ${details.status}">${this.getStatusText(details.status)}</span>`;
            modal.find('.mindmap-node-status').html(statusBadge);
            
            // 進捗表示
            const progress = details.progress || 0;
            const progressCircle = `
                <div class="mindmap-progress-circle" style="--progress: ${progress * 3.6}deg;">
                    <span class="mindmap-progress-text">${progress}%</span>
                </div>
            `;
            modal.find('.mindmap-node-progress-display').html(progressCircle);
            
            // 説明
            modal.find('.description-content').text(details.description || 'まだ説明がありません。');
            
            // リソース（Phase2）
            if (details.resources) {
                this.populateResources(modal, details.resources);
            }
            
            // 学習のコツ（Phase2）
            if (details.study_tips) {
                modal.find('.study-tips-content').text(details.study_tips);
            }
            
            // 学習コントロール
            this.populateStudyControls(modal, details);
        }
        
        populateResources(modal, resources) {
            const resourcesList = modal.find('.mindmap-resources-list');
            
            if (resources.length === 0) {
                resourcesList.html('<p>関連リソースがありません。</p>');
                return;
            }
            
            const resourcesHtml = resources.map(resource => `
                <div class="mindmap-resource-item">
                    <div>
                        <a href="${resource.url}" class="mindmap-resource-title" target="_blank">
                            ${resource.title}
                        </a>
                        <div style="font-size: 0.8em; color: #666;">${resource.difficulty || ''}</div>
                    </div>
                    <span class="mindmap-resource-type">${resource.type}</span>
                </div>
            `).join('');
            
            resourcesList.html(resourcesHtml);
        }
        
        populateStudyControls(modal, details) {
            const progressSlider = modal.find('.mindmap-progress-slider');
            const progressValue = modal.find('.mindmap-progress-value');
            const statusSelect = modal.find('.mindmap-status-select');
            const saveBtn = modal.find('.mindmap-save-progress');
            
            progressSlider.val(details.progress || 0);
            progressValue.text(`${details.progress || 0}%`);
            statusSelect.val(details.status || 'not-started');
            
            progressSlider.off('input').on('input', function() {
                progressValue.text(`${this.value}%`);
            });
            
            saveBtn.off('click').on('click', () => {
                this.saveProgress(details.id, {
                    progress: parseInt(progressSlider.val()),
                    status: statusSelect.val(),
                    notes: modal.find('.mindmap-notes-input').val()
                });
            });
            
            modal.find('.mindmap-save-notes').off('click').on('click', () => {
                this.saveProgress(details.id, {
                    progress: parseInt(progressSlider.val()),
                    status: statusSelect.val(),
                    notes: modal.find('.mindmap-notes-input').val()
                });
            });
        }
        
        saveProgress(nodeId, progressData) {
            if (typeof mindmapData !== 'undefined' && mindmapData.ajaxurl) {
                $.post(mindmapData.ajaxurl, {
                    action: 'update_node_progress',
                    node_id: nodeId,
                    progress: progressData.progress,
                    status: progressData.status,
                    notes: progressData.notes,
                    nonce: mindmapData.nonce
                }).done((response) => {
                    if (response.success) {
                        this.showSaveNotification('保存されました！');
                        this.updateNodeVisual(nodeId, progressData);
                    } else {
                        this.showSaveNotification('保存に失敗しました。', 'error');
                    }
                });
            } else {
                this.showSaveNotification('ログインが必要です。', 'warning');
            }
        }
        
        updateNodeVisual(nodeId, progressData) {
            const nodeEl = this.viewport.find(`[data-node-id="${nodeId}"]`);
            
            nodeEl.removeClass('completed in-progress not-started').addClass(progressData.status);
            nodeEl.find('.mindmap-progress-fill').css('width', `${progressData.progress}%`);
        }
        
        showSaveNotification(message, type = 'success') {
            const bgColor = {
                'success': '#4caf50',
                'error': '#f44336',
                'warning': '#ff9800'
            }[type] || '#4caf50';
            
            const notification = $(`
                <div class="mindmap-notification ${type}" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${bgColor};
                    color: white;
                    padding: 12px 20px;
                    border-radius: 6px;
                    z-index: 10001;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    animation: slideIn 0.3s ease-out;
                ">
                    ${message}
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 3000);
        }
        
        closeDetailModal() {
            const modalId = `mindmap-modal-${this.container.data('mindmap-id')}`;
            const modal = $(`#${modalId}`);
            modal.hide();
        }
        
        getStatusText(status) {
            const statusTexts = {
                'completed': '完了',
                'in-progress': '学習中',
                'not-started': '未開始'
            };
            return statusTexts[status] || '未開始';
        }
        
        // ==========================================================================
        // Phase 3 機能: 編集・ユーザー管理
        // ==========================================================================
        
        initPhase3Features() {
            if (this.container.hasClass('editable')) {
                this.initEditMode();
            }
            
            this.initUserControls();
            this.initMapCreator();
            this.initUserMapsModal();
            this.initMapSettings();
        }
        
        initEditMode() {
            this.container.find('[data-action="add-node"]').on('click', () => {
                this.addNewNode();
            });
            
            this.container.find('[data-action="save-map"]').on('click', () => {
                this.saveCustomMap();
            });
            
            this.container.find('[data-action="map-settings"]').on('click', () => {
                this.showMapSettings();
            });
            
            this.makeNodesEditable();
            this.watchForChanges();
        }
        
        initUserControls() {
            this.container.find('[data-action="user-maps"]').on('click', () => {
                this.showUserMapsModal();
            });
            
            this.container.find('[data-action="create-map"]').on('click', () => {
                this.showMapCreator();
            });
        }
        
        // 編集機能の詳細実装は省略（容量制限のため）
        // 実際の実装では以下の機能を含む：
        // - ノード追加・編集・削除
        // - ドラッグ&ドロップ
        // - マップ保存・読み込み
        // - ユーザーマップ管理
        // - インポート・エクスポート
        
        // 基本的なメソッドのスタブ
        addNewNode() { console.log('Add node (stub)'); }
        editNodeText(nodeEl, node) { console.log('Edit node (stub)'); }
        saveCustomMap() { console.log('Save map (stub)'); }
        showMapSettings() { console.log('Show settings (stub)'); }
        showUserMapsModal() { console.log('Show user maps (stub)'); }
        showMapCreator() { console.log('Show creator (stub)'); }
        makeNodesEditable() { console.log('Make editable (stub)'); }
        watchForChanges() { console.log('Watch changes (stub)'); }
        showNodeContextMenu(e, nodeEl, node) { console.log('Context menu (stub)'); }
        
        initMapCreator() {
            const uniqueId = this.container.data('mindmap-id');
            const modal = $(`#mindmap-creator-${uniqueId}`);
            
            if (modal.length === 0) return;
            
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
        
        initUserMapsModal() {
            const uniqueId = this.container.data('mindmap-id');
            const modal = $(`#mindmap-user-maps-${uniqueId}`);
            
            if (modal.length === 0) return;
            
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
        
        initMapSettings() {
            const uniqueId = this.container.data('mindmap-id');
            const modal = $(`#mindmap-settings-${uniqueId}`);
            
            if (modal.length === 0) return;
            
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
                    
                    if (confirm('作成したマップを編集しますか？')) {
                        window.location.href = `?edit_map=${response.data.map_id}`;
                    }
                } else {
                    this.showSaveNotification('作成に失敗しました: ' + response.data, 'error');
                }
            });
        }
        
        switchTab(modal, tabName) {
            modal.find('.tab-btn').removeClass('active');
            modal.find(`[data-tab="${tabName}"]`).addClass('active');
            
            modal.find('.tab-content').hide();
            modal.find(`#${tabName}`).show();
            
            this.loadUserMaps(tabName);
        }
        
        loadUserMaps(type) {
            const uniqueId = this.container.data('mindmap-id');
            const listContainer = $(`#${type}-list-${uniqueId}`);
            
            if (listContainer.length === 0) return;
            
            listContainer.html('<div class="loading-spinner">読み込み中...</div>');
            
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
        
        renderMapsList(container, maps, type) {
            if (maps.length === 0) {
                container.html('<div class="no-maps" style="text-align: center; padding: 40px; color: #666;">マップがありません</div>');
                return;
            }
            
            const mapsHtml = maps.map(map => `
                <div class="map-card" data-map-id="${map.id}">
                    <div class="map-preview">
                        <div class="map-icon">🗺️</div>
                        <div class="map-stats">${map.node_count} ノード</div>
                    </div>
                    <div class="map-info">
                        <h4 class="map-title">${this.escapeHtml(map.title)}</h4>
                        <p class="map-description">${this.escapeHtml(map.description || '説明なし')}</p>
                        ${map.author_name ? `<p class="map-author">作成者: ${this.escapeHtml(map.author_name)}</p>` : ''}
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
            this.bindMapCardEvents(container);
        }
        
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
        
        cloneMap(mapId) {
            $.post(mindmapData.ajaxurl, {
                action: 'clone_map',
                source_map_id: mapId,
                nonce: mindmapData.nonce
            }).done((response) => {
                if (response.success) {
                    this.showSaveNotification('マップが複製されました！');
                    this.loadUserMaps('my-maps');
                } else {
                    this.showSaveNotification('複製に失敗しました: ' + response.data, 'error');
                }
            });
        }
        
        exportMap() {
            const customId = this.container.data('custom-id');
            if (!customId) {
                this.showSaveNotification('エクスポートできるマップがありません', 'error');
                return;
            }
            
            $.post(mindmapData.ajaxurl, {
                action: 'export_map',
                map_id: customId,
                nonce: mindmapData.nonce
            }).done((response) => {
                if (response.success) {
                    const dataStr = JSON.stringify(response.data, null, 2);
                    const dataBlob = new Blob([dataStr], {type: 'application/json'});
                    const url = URL.createObjectURL(dataBlob);
                    
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `mindmap_${customId}_${new Date().toISOString().slice(0,10)}.json`;
                    link.click();
                    
                    URL.revokeObjectURL(url);
                    this.showSaveNotification('エクスポートが完了しました！');
                } else {
                    this.showSaveNotification('エクスポートに失敗しました: ' + response.data, 'error');
                }
            });
        }
        
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
                            this.loadUserMaps('my-maps');
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
        
        saveMapSettings(modal) {
            const formData = new FormData(modal.find('.mindmap-settings-form')[0]);
            const customId = this.container.data('custom-id');
            
            const settings = {
                theme: formData.get('theme'),
                node_style: formData.get('node_style'),
                is_public: formData.get('is_public') ? 1 : 0,
                allow_copy: formData.get('allow_copy') ? 1 : 0
            };
            
            this.applyTheme(settings.theme);
            
            if (customId) {
                $.post(mindmapData.ajaxurl, {
                    action: 'save_custom_map',
                    map_id: customId,
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
        
        closeModal(modal) {
            modal.hide();
        }
        
        // ユーティリティ関数
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
    
    // グローバルにクラスを登録
    window.MindMapRenderer = MindMapRenderer;
    
    // ==========================================================================
    // マインドマップ初期化関数
    // ==========================================================================
    
    function initializeMindMaps() {
        $('.mindmap-container').each(function() {
            const container = $(this);
            const canvas = container.find('.mindmap-canvas');
            const mapType = canvas.data('mindmap-type');
            const customId = container.data('custom-id');
            
            // 既に初期化済みかチェック
            if (container.data('initialized')) {
                return;
            }
            
            container.addClass('loading');
            
            let dataPromise;
            
            if (customId && mapType === 'custom') {
                // カスタムマップの場合はAjaxで読み込み
                dataPromise = $.post(mindmapData.ajaxurl, {
                    action: 'get_custom_map_data',
                    custom_id: customId,
                    nonce: mindmapData.nonce
                }).then((response) => {
                    if (response.success) {
                        return response.data.data;
                    } else {
                        throw new Error(response.data);
                    }
                });
            } else if (mindmapData.sampleData && mindmapData.sampleData[mapType]) {
                // サンプルデータの場合
                dataPromise = Promise.resolve(mindmapData.sampleData[mapType]);
            } else {
                // データが見つからない場合
                dataPromise = Promise.reject(new Error('Data not found'));
            }
            
            dataPromise.then((data) => {
                setTimeout(() => {
                    try {
                        new MindMapRenderer(container, data);
                        container.removeClass('loading').data('initialized', true);
                    } catch (error) {
                        console.error('マインドマップの初期化に失敗:', error);
                        showError(container, 'マインドマップの初期化に失敗しました');
                    }
                }, 300);
            }).catch((error) => {
                console.error('マインドマップデータの読み込みに失敗:', error);
                showError(container, 'データの読み込みに失敗しました');
            });
        });
    }
    
    function showError(container, message) {
        container.removeClass('loading');
        const canvas = container.find('.mindmap-canvas');
        canvas.html(`
            <div style="
                text-align: center; 
                padding: 50px; 
                color: #999;
                font-style: italic;
                border: 2px dashed #ddd;
                border-radius: 8px;
                margin: 20px;
            ">
                <div style="font-size: 2em; margin-bottom: 10px;">😕</div>
                <div>${message}</div>
                <div style="font-size: 0.9em; margin-top: 10px;">
                    <button onclick="location.reload()" style="
                        background: #3f51b5;
                        color: white;
                        border: none;
                        padding: 8px 16px;
                        border-radius: 4px;
                        cursor: pointer;
                        margin-top: 10px;
                    ">再読み込み</button>
                </div>
            </div>
        `);
    }
    
    // ==========================================================================
    // 初期化とイベント処理
    // ==========================================================================
    
    // DOM読み込み完了後に初期化
    initializeMindMaps();
    
    // 動的に追加されたマインドマップにも対応
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            let shouldInit = false;
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            const $node = $(node);
                            if ($node.hasClass('mindmap-container') || $node.find('.mindmap-container').length > 0) {
                                shouldInit = true;
                            }
                        }
                    });
                }
            });
            
            if (shouldInit) {
                setTimeout(initializeMindMaps, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
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
    
    // CSS アニメーション追加
    if (!$('#mindmap-dynamic-styles').length) {
        $('head').append(`
            <style id="mindmap-dynamic-styles">
                @keyframes slideIn {
                    from {
                        opacity: 0;
                        transform: translateX(100%);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }
                
                .mindmap-notification {
                    animation: slideIn 0.3s ease-out;
                }
                
                .mindmap-container.loading .mindmap-canvas::after {
                    content: '';
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    width: 40px;
                    height: 40px;
                    margin: -20px 0 0 -20px;
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #3f51b5;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                }
                
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                
                .mindmap-tooltip {
                    position: absolute;
                    background: #333;
                    color: white;
                    padding: 8px 12px;
                    border-radius: 4px;
                    font-size: 12px;
                    pointer-events: none;
                    z-index: 1000;
                    opacity: 0;
                    transition: opacity 0.2s ease;
                    max-width: 200px;
                    word-wrap: break-word;
                }
                
                .mindmap-tooltip.show {
                    opacity: 1;
                }
                
                .mindmap-tooltip::after {
                    content: '';
                    position: absolute;
                    top: 100%;
                    left: 50%;
                    margin-left: -5px;
                    border-width: 5px;
                    border-style: solid;
                    border-color: #333 transparent transparent transparent;
                }
            </style>
        `);
    }
});