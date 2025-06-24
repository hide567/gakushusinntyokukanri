// 行政書士の道 - マインドマップ JavaScript (修正版)

jQuery(document).ready(function($) {
    
    // デバッグ用ログ
    console.log('マインドマップJavaScript読み込み開始');
    console.log('mindmapData:', typeof mindmapData !== 'undefined' ? mindmapData : 'undefined');
    
    class MindMapRenderer {
        constructor(container, data) {
            console.log('MindMapRenderer初期化開始', container, data);
            
            this.container = $(container);
            this.canvas = this.container.find('.mindmap-canvas');
            this.data = data || {};
            this.scale = 1;
            this.translateX = 0;
            this.translateY = 0;
            this.isDragging = false;
            this.isFullscreen = false;
            this.lastX = 0;
            this.lastY = 0;
            this.currentTooltip = null;
            this.tooltipTimeout = null;
            
            // データ検証
            if (!this.data.nodes || !Array.isArray(this.data.nodes)) {
                console.error('無効なマップデータ:', this.data);
                this.showError('マップデータが正しくありません');
                return;
            }
            
            try {
                this.init();
                console.log('MindMapRenderer初期化完了');
            } catch (error) {
                console.error('マインドマップ初期化エラー:', error);
                this.showError('マインドマップの初期化に失敗しました: ' + error.message);
            }
        }
        
        init() {
            this.createViewport();
            this.loadTheme();
            this.bindEvents();
            
            // データがある場合のみレンダリング
            if (this.data.nodes && this.data.nodes.length > 0) {
                this.renderMindMap();
                this.centerMap();
                this.container.removeClass('loading');
            } else {
                this.showError('表示するノードがありません');
            }
        }
        
        createViewport() {
            const viewport = $('<div class="mindmap-viewport"></div>');
            this.canvas.append(viewport);
            this.viewport = viewport;
        }
        
        showError(message) {
            this.canvas.html(`
                <div style="text-align: center; padding: 50px; color: #999;">
                    <div style="font-size: 48px; margin-bottom: 20px;">⚠️</div>
                    <div style="font-size: 16px; margin-bottom: 10px;">${message}</div>
                    <button onclick="location.reload()" style="padding: 8px 16px; background: #3f51b5; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        再読み込み
                    </button>
                </div>
            `);
            this.container.removeClass('loading');
        }
        
        renderMindMap() {
            try {
                this.renderConnections();
                this.renderNodes();
                console.log('マインドマップレンダリング完了');
            } catch (error) {
                console.error('レンダリングエラー:', error);
                this.showError('マップの描画に失敗しました');
            }
        }
        
        renderNodes() {
            const nodes = this.data.nodes || [];
            console.log('ノード描画開始:', nodes.length + '個');
            
            nodes.forEach((node, index) => {
                try {
                    const nodeElement = this.createNodeElement(node);
                    this.viewport.append(nodeElement);
                } catch (error) {
                    console.error(`ノード${index}の描画エラー:`, error, node);
                }
            });
        }
        
        createNodeElement(node) {
            // デフォルト値の設定
            const progressWidth = node.progress || 0;
            const statusClass = node.status || 'not-started';
            const icon = node.icon || '';
            const level = node.level || 0;
            const color = node.color || '#3f51b5';
            const x = node.x || 0;
            const y = node.y || 0;
            const text = node.text || 'ノード';
            
            const nodeEl = $(`
                <div class="mindmap-node level-${level} ${statusClass}" 
                     data-node-id="${node.id}"
                     style="left: ${x}px; top: ${y}px; border-color: ${color};">
                    <div class="mindmap-node-content">
                        ${icon ? `<span class="mindmap-node-icon">${icon}</span>` : ''}
                        <span class="mindmap-node-text">${text}</span>
                    </div>
                    <div class="mindmap-progress-bar">
                        <div class="mindmap-progress-fill" style="width: ${progressWidth}%"></div>
                    </div>
                </div>
            `);
            
            // イベントハンドラー
            nodeEl.on('mouseenter', (e) => {
                this.showTooltip(e, node);
            }).on('mouseleave', () => {
                this.hideTooltip();
            });
            
            nodeEl.on('click', (e) => {
                e.stopPropagation();
                this.onNodeClick(node);
            });
            
            return nodeEl;
        }
        
        renderConnections() {
            const connections = this.data.connections || [];
            console.log('接続線描画開始:', connections.length + '本');
            
            if (connections.length === 0) {
                return;
            }
            
            const svg = $('<svg class="mindmap-connection" style="width: 100%; height: 100%; position: absolute; top: 0; left: 0; pointer-events: none;"></svg>');
            
            connections.forEach((conn, index) => {
                try {
                    const fromNode = this.data.nodes.find(n => n.id === conn.from);
                    const toNode = this.data.nodes.find(n => n.id === conn.to);
                    
                    if (fromNode && toNode) {
                        const line = this.createConnectionLine(fromNode, toNode);
                        svg.append(line);
                    } else {
                        console.warn(`接続線${index}: ノードが見つかりません`, conn);
                    }
                } catch (error) {
                    console.error(`接続線${index}の描画エラー:`, error, conn);
                }
            });
            
            this.viewport.prepend(svg);
        }
        
        createConnectionLine(fromNode, toNode) {
            const x1 = fromNode.x || 0;
            const y1 = fromNode.y || 0;
            const x2 = toNode.x || 0;
            const y2 = toNode.y || 0;
            
            // 直線接続（シンプル版）
            const path = `M ${x1} ${y1} L ${x2} ${y2}`;
            
            const level = Math.min(fromNode.level || 0, toNode.level || 0);
            
            return $(`<path d="${path}" class="mindmap-line level-${level}" />`);
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
            $(document).on('mouseup', () => this.endPan());
            
            // ホイールズーム
            this.canvas.on('wheel', (e) => this.onWheel(e));
            
            // タッチ操作（モバイル対応）
            this.canvas.on('touchstart', (e) => this.startTouch(e));
            this.canvas.on('touchmove', (e) => this.doTouch(e));
            this.canvas.on('touchend', () => this.endTouch());
            
            console.log('イベントバインド完了');
        }
        
        startPan(e) {
            if (e.target === this.canvas[0] || $(e.target).hasClass('mindmap-viewport')) {
                this.isDragging = true;
                this.lastX = e.clientX;
                this.lastY = e.clientY;
                this.canvas.addClass('dragging');
                e.preventDefault();
            }
        }
        
        onNodeClick(node) {
            console.log('ノードクリック:', node);
            
            // 視覚的フィードバック
            const nodeEl = this.viewport.find(`[data-node-id="${node.id}"]`);
            nodeEl.addClass('clicked');
            setTimeout(() => nodeEl.removeClass('clicked'), 200);
            
            // カスタムイベント発火
            this.container.trigger('nodeClicked', [node]);
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
                
                e.preventDefault();
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
            const xs = nodes.map(n => n.x || 0).filter(x => !isNaN(x));
            const ys = nodes.map(n => n.y || 0).filter(y => !isNaN(y));
            
            if (xs.length === 0 || ys.length === 0) return;
            
            const minX = Math.min(...xs) - 100;
            const maxX = Math.max(...xs) + 100;
            const minY = Math.min(...ys) - 50;
            const maxY = Math.max(...ys) + 50;
            
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
        
        showTooltip(e, node) {
            if (this.tooltipTimeout) {
                clearTimeout(this.tooltipTimeout);
            }
            
            const progressText = node.progress ? `進捗: ${node.progress}%` : '未開始';
            const statusTexts = {
                'completed': '✅ 完了',
                'in-progress': '🔄 学習中',
                'not-started': '⏳ 未開始'
            };
            const statusText = statusTexts[node.status] || '未開始';
            
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
                left: Math.max(0, Math.min(x - tooltip.outerWidth() / 2, this.container.width() - tooltip.outerWidth())),
                top: Math.max(0, y)
            }).addClass('show');
            
            this.currentTooltip = tooltip;
        }
        
        hideTooltip() {
            if (this.currentTooltip) {
                this.tooltipTimeout = setTimeout(() => {
                    if (this.currentTooltip) {
                        this.currentTooltip.remove();
                        this.currentTooltip = null;
                    }
                }, 200);
            }
        }
        
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
    }
    
    // グローバルにクラスを登録
    window.MindMapRenderer = MindMapRenderer;
    
    // マインドマップ初期化関数（修正版）
    function initializeMindMaps() {
        console.log('マインドマップ初期化関数実行');
        
        $('.mindmap-container').not('.mindmap-phase2').each(function() {
            const container = $(this);
            const canvas = container.find('.mindmap-canvas');
            const mapType = canvas.data('mindmap-type') || 'gyosei';
            
            console.log('マインドマップ処理中:', {
                container: container.attr('id'),
                mapType: mapType,
                initialized: container.data('initialized')
            });
            
            // 既に初期化済みかチェック
            if (container.data('initialized')) {
                console.log('既に初期化済み:', container.attr('id'));
                return;
            }
            
            // mindmapDataの存在確認
            if (typeof mindmapData === 'undefined') {
                console.error('mindmapDataが定義されていません');
                canvas.html('<div style="text-align: center; padding: 50px; color: #999;">設定エラー: mindmapDataが見つかりません</div>');
                return;
            }
            
            // サンプルデータの確認
            if (!mindmapData.sampleData || !mindmapData.sampleData[mapType]) {
                console.error('サンプルデータが見つかりません:', mapType, mindmapData.sampleData);
                canvas.html(`
                    <div style="text-align: center; padding: 50px; color: #999;">
                        <div>データが見つかりません: ${mapType}</div>
                        <div style="font-size: 12px; margin-top: 10px;">
                            利用可能: ${mindmapData.sampleData ? Object.keys(mindmapData.sampleData).join(', ') : 'なし'}
                        </div>
                    </div>
                `);
                return;
            }
            
            const data = mindmapData.sampleData[mapType];
            console.log('マインドマップデータ:', data);
            
            // データの基本検証
            if (!data || !data.nodes || !Array.isArray(data.nodes)) {
                console.error('無効なマップデータ構造:', data);
                canvas.html('<div style="text-align: center; padding: 50px; color: #999;">データ構造エラー</div>');
                return;
            }
            
            // ローディング表示
            container.addClass('loading');
            
            // 少し遅延してマップを描画
            setTimeout(() => {
                try {
                    new MindMapRenderer(container, data);
                    container.removeClass('loading').data('initialized', true);
                    console.log('マインドマップ初期化成功:', container.attr('id'));
                } catch (error) {
                    console.error('マインドマップの初期化に失敗しました:', error);
                    container.removeClass('loading');
                    canvas.html(`
                        <div style="text-align: center; padding: 50px; color: #999;">
                            <div>マインドマップの読み込みに失敗しました</div>
                            <div style="font-size: 12px; margin-top: 10px;">${error.message}</div>
                            <button onclick="location.reload()" style="margin-top: 10px; padding: 8px 16px; background: #3f51b5; color: white; border: none; border-radius: 4px; cursor: pointer;">
                                再読み込み
                            </button>
                        </div>
                    `);
                }
            }, 100);
        });
    }
    
    // グローバル関数として公開
    window.initializeMindMaps = initializeMindMaps;
    
    // DOM読み込み完了後に初期化
    console.log('DOM読み込み状態:', document.readyState);
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
                console.log('新しいマインドマップが追加されました - 初期化実行');
                setTimeout(initializeMindMaps, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        console.log('MutationObserver設定完了');
    }
    
    // デバッグ用：グローバル変数の状態確認
    console.log('初期化完了時の状態:', {
        mindmapData: typeof mindmapData !== 'undefined' ? mindmapData : 'undefined',
        MindMapRenderer: typeof window.MindMapRenderer,
        containers: $('.mindmap-container').length
    });
});

// 追加のCSS（クリック効果など）
jQuery(document).ready(function($) {
    $('<style>').text(`
        .mindmap-node.clicked {
            transform: translate(-50%, -50%) scale(1.15) !important;
            transition: transform 0.2s ease;
        }
        
        .mindmap-container.loading .mindmap-canvas::after {
            content: "";
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
        
        .mindmap-error {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }
    `).appendTo('head');
});