// 行政書士の道 - マインドマップ Phase 2 JavaScript

jQuery(document).ready(function($) {
    
    // Phase 2機能を既存のMindMapRendererクラスに拡張
    class MindMapRendererPhase2 extends (window.MindMapRenderer || class {}) {
        constructor(container, data) {
            super(container, data);
            
            this.searchEnabled = this.canvas.data('search') === 'true';
            this.detailsEnabled = this.canvas.data('details') === 'true';
            this.draggableEnabled = this.canvas.data('draggable') === 'true';
            
            this.initPhase2Features();
        }
        
        initPhase2Features() {
            if (this.searchEnabled) {
                this.initSearch();
            }
            
            if (this.detailsEnabled) {
                this.initDetailModal();
            }
            
            if (this.draggableEnabled) {
                this.initDragDrop();
            }
        }
        
        // 検索機能の初期化
        initSearch() {
            const searchInput = this.container.find('.mindmap-search');
            const searchBtn = this.container.find('.mindmap-search-btn');
            const clearBtn = this.container.find('.mindmap-search-clear');
            
            let searchTimeout;
            
            // リアルタイム検索
            searchInput.on('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(searchInput.val());
                }, 300);
            });
            
            // 検索ボタン
            searchBtn.on('click', () => {
                this.performSearch(searchInput.val());
            });
            
            // クリアボタン
            clearBtn.on('click', () => {
                searchInput.val('');
                this.clearSearch();
                clearBtn.hide();
            });
            
            // エンターキーで検索
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
            
            // Ajax検索またはローカル検索
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
            // 既存のハイライトをクリア
            this.viewport.find('.mindmap-node').removeClass('search-highlighted');
            
            // 新しいハイライトを適用
            results.forEach(result => {
                const nodeEl = this.viewport.find(`[data-node-id="${result.id}"]`);
                nodeEl.addClass('search-highlighted');
                
                // 最初の結果にフォーカス
                if (results.indexOf(result) === 0) {
                    this.focusOnNode(result);
                }
            });
        }
        
        focusOnNode(node) {
            const canvasWidth = this.canvas.width();
            const canvasHeight = this.canvas.height();
            
            // ノードを中央に配置するための計算
            this.translateX = canvasWidth / 2 - node.x * this.scale;
            this.translateY = canvasHeight / 2 - node.y * this.scale;
            
            this.updateTransform();
        }
        
        clearSearch() {
            this.viewport.find('.mindmap-node').removeClass('search-highlighted');
            this.container.find('.mindmap-search-clear').hide();
        }
        
        // 詳細モーダルの初期化
        initDetailModal() {
            // モーダルのクローズイベント
            const modal = $(`#mindmap-modal-${this.container.data('mindmap-id')}`);
            
            modal.find('.mindmap-modal-close, .mindmap-modal-overlay').on('click', () => {
                this.closeDetailModal();
            });
            
            // ESCキーでモーダルを閉じる
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && modal.is(':visible')) {
                    this.closeDetailModal();
                }
            });
        }
        
        onNodeClick(node) {
            if (typeof super.onNodeClick === 'function') {
                super.onNodeClick(node);
            }
            
            if (this.detailsEnabled) {
                this.showDetailModal(node);
            }
        }
        
        showDetailModal(node) {
            const modal = $(`#mindmap-modal-${this.container.data('mindmap-id')}`);
            
            // ローディング状態を表示
            modal.find('.mindmap-modal-title').text(node.text);
            modal.find('.mindmap-modal-body').html('<div style="text-align: center; padding: 50px;">読み込み中...</div>');
            modal.show();
            
            // ノード詳細を取得
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
                // ローカルデータから詳細を生成
                const node = this.data.nodes.find(n => n.id === nodeId);
                if (node) {
                    callback(node);
                }
            }
        }
        
        populateModal(details) {
            const modal = $(`#mindmap-modal-${this.container.data('mindmap-id')}`);
            
            // ステータス表示
            const statusBadge = `<span class="mindmap-status-badge ${details.status}">${this.getStatusText(details.status)}</span>`;
            modal.find('.mindmap-node-status').html(`<label>ステータス:</label> ${statusBadge}`);
            
            // 進捗表示
            const progressCircle = `
                <div class="mindmap-progress-circle" style="--progress: ${details.progress * 3.6}deg;">
                    <span class="mindmap-progress-text">${details.progress}%</span>
                </div>
            `;
            modal.find('.mindmap-node-progress-display').html(`<label>進捗:</label> ${progressCircle}`);
            
            // 説明
            modal.find('.mindmap-node-description').text(details.description || 'まだ説明がありません。');
            
            // リソース
            this.populateResources(modal, details.resources || []);
            
            // 学習コントロール
            this.populateStudyControls(modal, details);
            
            // メモ
            modal.find('.mindmap-notes-input').val(details.notes || '');
        }
        
        populateResources(modal, resources) {
            const resourcesList = modal.find('.mindmap-resources-list');
            
            if (resources.length === 0) {
                resourcesList.html('<p>関連リソースがありません。</p>');
                return;
            }
            
            const resourcesHtml = resources.map(resource => `
                <div class="mindmap-resource-item">
                    <a href="${resource.url}" class="mindmap-resource-title" target="_blank">
                        ${resource.title}
                    </a>
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
            
            // 初期値を設定
            progressSlider.val(details.progress || 0);
            progressValue.text(`${details.progress || 0}%`);
            statusSelect.val(details.status || 'not-started');
            
            // スライダー変更イベント
            progressSlider.on('input', function() {
                progressValue.text(`${this.value}%`);
            });
            
            // 保存ボタンイベント
            saveBtn.off('click').on('click', () => {
                this.saveProgress(details.id, {
                    progress: parseInt(progressSlider.val()),
                    status: statusSelect.val(),
                    notes: modal.find('.mindmap-notes-input').val()
                });
            });
            
            // メモ保存ボタン
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
            
            // ステータスクラスを更新
            nodeEl.removeClass('completed in-progress not-started').addClass(progressData.status);
            
            // 進捗バーを更新
            nodeEl.find('.mindmap-progress-fill').css('width', `${progressData.progress}%`);
        }
        
        showSaveNotification(message, type = 'success') {
            const notification = $(`
                <div class="mindmap-notification ${type}" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#ff9800'};
                    color: white;
                    padding: 12px 20px;
                    border-radius: 6px;
                    z-index: 10001;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
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
            const modal = $(`#mindmap-modal-${this.container.data('mindmap-id')}`);
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
        
        // ドラッグ&ドロップ機能の初期化
        initDragDrop() {
            this.draggedNode = null;
            this.dragOffset = { x: 0, y: 0 };
            
            // ノードにドラッグ可能クラスを追加
            this.viewport.find('.mindmap-node').addClass('draggable');
            
            // ドラッグイベントを設定
            this.viewport.on('mousedown', '.mindmap-node', (e) => {
                if (e.which === 1) { // 左クリックのみ
                    this.startNodeDrag(e);
                }
            });
            
            $(document).on('mousemove', (e) => {
                if (this.draggedNode) {
                    this.doNodeDrag(e);
                }
            });
            
            $(document).on('mouseup', () => {
                if (this.draggedNode) {
                    this.endNodeDrag();
                }
            });
        }
        
        startNodeDrag(e) {
            e.stopPropagation();
            
            const nodeEl = $(e.currentTarget);
            const nodeId = nodeEl.data('node-id');
            const node = this.data.nodes.find(n => n.id === nodeId);
            
            if (node) {
                this.draggedNode = { element: nodeEl, data: node };
                
                const rect = this.viewport[0].getBoundingClientRect();
                const nodeRect = nodeEl[0].getBoundingClientRect();
                
                this.dragOffset.x = e.clientX - nodeRect.left - nodeRect.width / 2;
                this.dragOffset.y = e.clientY - nodeRect.top - nodeRect.height / 2;
                
                nodeEl.addClass('dragging');
                this.viewport.addClass('drag-mode');
            }
        }
        
        doNodeDrag(e) {
            if (!this.draggedNode) return;
            
            const rect = this.viewport[0].getBoundingClientRect();
            const x = (e.clientX - rect.left - this.translateX - this.dragOffset.x) / this.scale;
            const y = (e.clientY - rect.top - this.translateY - this.dragOffset.y) / this.scale;
            
            // ノード位置を更新
            this.draggedNode.element.css({
                left: x + 'px',
                top: y + 'px'
            });
            
            // データも更新
            this.draggedNode.data.x = x;
            this.draggedNode.data.y = y;
            
            // 接続線を更新
            this.updateConnections();
        }
        
        endNodeDrag() {
            if (this.draggedNode) {
                this.draggedNode.element.removeClass('dragging');
                this.viewport.removeClass('drag-mode');
                this.draggedNode = null;
            }
        }
        
        updateConnections() {
            // 接続線を再描画
            this.viewport.find('.mindmap-connection').remove();
            this.renderConnections();
        }
    }
    
    // グローバルに新しいクラスを登録
    window.MindMapRendererPhase2 = MindMapRendererPhase2;
    
    // 既存の初期化をPhase 2版に置き換え
    function initializeMindMapsPhase2() {
        $('.mindmap-container.mindmap-phase2').each(function() {
            const container = $(this);
            const canvas = container.find('.mindmap-canvas');
            const mapType = canvas.data('mindmap-type');
            
            // 既に初期化済みかチェック
            if (container.data('initialized')) {
                return;
            }
            
            if (typeof mindmapData !== 'undefined' && mindmapData.sampleData && mindmapData.sampleData[mapType]) {
                const data = mindmapData.sampleData[mapType];
                
                container.addClass('loading');
                
                setTimeout(() => {
                    try {
                        new MindMapRendererPhase2(container, data);
                        container.removeClass('loading').data('initialized', true);
                    } catch (error) {
                        console.error('Phase 2マインドマップの初期化に失敗しました:', error);
                        container.removeClass('loading');
                        canvas.html('<div style="text-align: center; padding: 50px; color: #999;">マインドマップの読み込みに失敗しました</div>');
                    }
                }, 500);
            }
        });
    }
    
    // Phase 2マインドマップを初期化
    initializeMindMapsPhase2();
});