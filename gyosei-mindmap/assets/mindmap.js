// 行政書士の道 - マインドマップ JavaScript

jQuery(document).ready(function($) {
    
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
            
            this.init();
        }
        
        init() {
            this.createViewport();
            this.loadTheme();
            this.renderMindMap();
            this.bindEvents();
            this.centerMap();
        }
        
        createViewport() {
            const viewport = $('<div class="mindmap-viewport"></div>');
            this.canvas.append(viewport);
            this.viewport = viewport;
        }
        
        renderMindMap() {
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
                </div>
            `);
            
            // ノードホバーイベント
            nodeEl.on('mouseenter', (e) => {
                this.showTooltip(e, node);
            }).on('mouseleave', () => {
                this.hideTooltip();
            });
            
            // ノードクリックイベント
            nodeEl.on('click', (e) => {
                e.stopPropagation();
                this.onNodeClick(node);
            });
            
            return nodeEl;
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
            const container = this.container;
            
            // コントロールボタンイベント
            container.find('[data-action="zoom-in"]').on('click', () => this.zoomIn());
            container.find('[data-action="zoom-out"]').on('click', () => this.zoomOut());
            container.find('[data-action="reset"]').on('click', () => this.resetView());
            container.find('[data-action="fullscreen"]').on('click', () => this.toggleFullscreen());
            container.find('[data-action="toggle-theme"]').on('click', () => this.toggleTheme());
            
            // パン操作
            this.canvas.on('mousedown', (e) => this.startPan(e));
            $(document).on('mousemove', (e) => this.doPan(e));
            $(document).on('mouseup', () => this.endPan());find('[data-action="zoom-in"]').on('click', () => this.zoomIn());
            container.find('[data-action="zoom-out"]').on('click', () => this.zoomOut());
            container.find('[data-action="reset"]').on('click', () => this.resetView());
            container.find('[data-action="fullscreen"]').on('click', () => this.toggleFullscreen());
            
            // パン操作
            this.canvas.on('mousedown', (e) => this.startPan(e));
            $(document).on('mousemove', (e) => this.doPan(e));
            $(document).on('mouseup', () => this.endPan());
            
            // ホイールズーム
            this.canvas.on('wheel', (e) => this.onWheel(e));
            
            // タッチ操作（モバイル対応）
            this.canvas.on('touchstart', (e) => this.startTouch(e));
            this.canvas.on('touchmove', (e) => this.doTouch(e));
            this.canvas.on('touchend', () => this.endTouch());
        }
        
        startPan(e) {
            if (e.target === this.canvas[0] || $(e.target).hasClass('mindmap-viewport')) {
                this.isDragging = true;
                this.lastX = e.clientX;
                this.lastY = e.clientY;
                this.canvas.addClass('dragging');
            }
        }
        
        onNodeClick(node) {
            console.log('Node clicked:', node);
            // TODO: Phase 2で詳細機能を実装
            
            // 一時的な視覚フィードバック
            const nodeEl = this.viewport.find(`[data-node-id="${node.id}"]`);
            nodeEl.animate({
                transform: 'translate(-50%, -50%) scale(1.2)'
            }, 200).animate({
                transform: 'translate(-50%, -50%) scale(1.0)'
            }, 200);
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
                // ズーム中心点を基準に平行移動を調整
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
            
            // ノードの境界を計算
            const nodes = this.data.nodes;
            const minX = Math.min(...nodes.map(n => n.x)) - 100;
            const maxX = Math.max(...nodes.map(n => n.x)) + 100;
            const minY = Math.min(...nodes.map(n => n.y)) - 50;
            const maxY = Math.max(...nodes.map(n => n.y)) + 50;
            
            const mapWidth = maxX - minX;
            const mapHeight = maxY - minY;
            
            const canvasWidth = this.canvas.width();
            const canvasHeight = this.canvas.height();
            
            // 中央に配置
            this.translateX = (canvasWidth - mapWidth) / 2 - minX;
            this.translateY = (canvasHeight - mapHeight) / 2 - minY;
            
            this.updateTransform();
        }
        
        updateTransform() {
            this.viewport.css({
                transform: `translate(${this.translateX}px, ${this.translateY}px) scale(${this.scale})`
            });
        }
        
        toggleFullscreen() {
            if (this.isFullscreen) {
                this.container.removeClass('mindmap-fullscreen');
                this.isFullscreen = false;
                this.container.find('[data-action="fullscreen"]').text('⛶');
            } else {
                this.container.addClass('mindmap-fullscreen');
                this.isFullscreen = true;
                this.container.find('[data-action="fullscreen"]').text('✕');
                
                // フルスクリーン時にマップを再センタリング
                setTimeout(() => this.centerMap(), 100);
            }
        }
        
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
                top: y
            }).addClass('show');
            
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
        
        toggleTheme() {
            this.container.toggleClass('dark-mode');
            const isDark = this.container.hasClass('dark-mode');
            const button = this.container.find('[data-action="toggle-theme"]');
            button.text(isDark ? '☀️' : '🌙');
            
            // ローカルストレージに保存（オプション）
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem('mindmap-theme', isDark ? 'dark' : 'light');
            }
        }
        
        loadTheme() {
            // ローカルストレージからテーマを読み込み（オプション）
            if (typeof(Storage) !== "undefined") {
                const savedTheme = localStorage.getItem('mindmap-theme');
                if (savedTheme === 'dark') {
                    this.container.addClass('dark-mode');
                    this.container.find('[data-action="toggle-theme"]').text('☀️');
                }
            }
        }
        
        // タッチ操作（基本実装）
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
    }
    
    // マインドマップ初期化関数
    function initializeMindMaps() {
        $('.mindmap-container').each(function() {
            const container = $(this);
            const canvas = container.find('.mindmap-canvas');
            const mapType = canvas.data('mindmap-type');
            
            if (mindmapData.sampleData && mindmapData.sampleData[mapType]) {
                const data = mindmapData.sampleData[mapType];
                
                // ローディング表示
                container.addClass('loading');
                
                // 少し遅延してマップを描画（ローディング効果）
                setTimeout(() => {
                    try {
                        new MindMapRenderer(container, data);
                        container.removeClass('loading');
                    } catch (error) {
                        console.error('マインドマップの初期化に失敗しました:', error);
                        container.removeClass('loading');
                        canvas.html('<div style="text-align: center; padding: 50px; color: #999;">マインドマップの読み込みに失敗しました</div>');
                    }
                }, 500);
            } else {
                console.warn('マインドマップデータが見つかりません:', mapType);
                canvas.html('<div style="text-align: center; padding: 50px; color: #999;">データが見つかりません</div>');
            }
        });
    }
    
    // DOM読み込み完了後に初期化
    initializeMindMaps();
    
    // 動的に追加されたマインドマップにも対応
    $(document).on('DOMNodeInserted', function(e) {
        const target = $(e.target);
        if (target.hasClass('mindmap-container') || target.find('.mindmap-container').length > 0) {
            setTimeout(initializeMindMaps, 100);
        }
    });
});