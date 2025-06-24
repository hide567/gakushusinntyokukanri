// 行政書士の道 - コミュニティ機能 JavaScript
// File: assets/community.js

jQuery(document).ready(function($) {
    
    // コミュニティマップ管理
    class CommunityManager {
        constructor() {
            this.currentPage = 1;
            this.perPage = 12;
            this.filters = {
                category: '',
                sort: 'newest',
                search: ''
            };
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.loadCommunityMaps();
        }
        
        bindEvents() {
            // 検索
            $('#community-search').on('input', $.debounce(300, () => {
                this.filters.search = $('#community-search').val();
                this.loadCommunityMaps(1);
            }));
            
            // フィルター
            $('#community-category-filter').on('change', () => {
                this.filters.category = $('#community-category-filter').val();
                this.loadCommunityMaps(1);
            });
            
            $('#community-sort-filter').on('change', () => {
                this.filters.sort = $('#community-sort-filter').val();
                this.loadCommunityMaps(1);
            });
            
            // いいねボタン
            $(document).on('click', '.map-like-btn', this.handleLike.bind(this));
            
            // コメント投稿
            $(document).on('click', '.comment-submit-btn', this.handleComment.bind(this));
            
            // フォローボタン
            $(document).on('click', '.follow-user-btn', this.handleFollow.bind(this));
        }
        
        loadCommunityMaps(page = this.currentPage) {
            this.currentPage = page;
            
            $.post(mindmapData.ajaxurl, {
                action: 'get_community_maps',
                page: page,
                per_page: this.perPage,
                category: this.filters.category,
                sort: this.filters.sort,
                search: this.filters.search,
                nonce: mindmapData.nonce
            }).done((response) => {
                if (response.success) {
                    this.renderMaps(response.data.maps);
                    this.renderPagination(response.data.pagination);
                } else {
                    this.showError('マップの読み込みに失敗しました');
                }
            });
        }
        
        renderMaps(maps) {
            const container = $('#community-maps-grid');
            container.empty();
            
            if (maps.length === 0) {
                container.html('<p class="no-maps">マップが見つかりませんでした。</p>');
                return;
            }
            
            maps.forEach(map => {
                const mapCard = this.createMapCard(map);
                container.append(mapCard);
            });
        }
        
        createMapCard(map) {
            const isLiked = this.checkUserLiked(map.id);
            const tagsHtml = map.tags.map(tag => `<span class="map-tag">${tag}</span>`).join('');
            
            return $(`
                <div class="map-card" data-map-id="${map.id}">
                    <div class="map-card-header">
                        <h3 class="map-title">
                            <a href="${map.preview_url}">${map.title}</a>
                        </h3>
                        <div class="map-meta">
                            <span class="map-category">${this.getCategoryLabel(map.category)}</span>
                            <span class="map-nodes">${map.node_count} ノード</span>
                        </div>
                    </div>
                    
                    <div class="map-card-body">
                        <p class="map-description">${map.description}</p>
                        <div class="map-tags">${tagsHtml}</div>
                    </div>
                    
                    <div class="map-card-footer">
                        <div class="map-author">
                            <span>作成者: ${map.creator_name}</span>
                        </div>
                        
                        <div class="map-stats">
                            <span class="stat-item">
                                <i class="icon-view"></i> ${map.views_count}
                            </span>
                            <span class="stat-item">
                                <i class="icon-comment"></i> ${map.comment_count}
                            </span>
                            ${map.average_rating ? `
                                <span class="stat-item">
                                    <i class="icon-star"></i> ${map.average_rating}
                                </span>
                            ` : ''}
                        </div>
                        
                        <div class="map-actions">
                            <button class="btn-secondary map-like-btn ${isLiked ? 'liked' : ''}" 
                                    data-map-id="${map.id}">
                                <i class="icon-heart"></i> 
                                <span class="like-count">${map.likes_count}</span>
                            </button>
                            
                            <button class="btn-secondary map-share-btn" 
                                    data-map-id="${map.id}">
                                <i class="icon-share"></i> 共有
                            </button>
                            
                            <a href="${map.preview_url}" class="btn-primary">
                                <i class="icon-play"></i> 学習開始
                            </a>
                        </div>
                    </div>
                </div>
            `);
        }
        
        handleLike(e) {
            e.preventDefault();
            
            if (!mindmapData.isLoggedIn) {
                this.showLoginRequired();
                return;
            }
            
            const btn = $(e.currentTarget);
            const mapId = btn.data('map-id');
            const isLiked = btn.hasClass('liked');
            const action = isLiked ? 'unlike' : 'like';
            
            btn.prop('disabled', true);
            
            $.post(mindmapData.ajaxurl, {
                action: 'like_map',
                map_id: mapId,
                action_type: action,
                nonce: mindmapData.nonce
            }).done((response) => {
                if (response.success) {
                    btn.toggleClass('liked');
                    btn.find('.like-count').text(response.data.likes_count);
                    
                    this.showSuccess(isLiked ? 'いいねを取り消しました' : 'いいねしました！');
                } else {
                    this.showError('操作に失敗しました');
                }
            }).always(() => {
                btn.prop('disabled', false);
            });
        }
        
        handleComment(e) {
            e.preventDefault();
            
            if (!mindmapData.isLoggedIn) {
                this.showLoginRequired();
                return;
            }
            
            const btn = $(e.currentTarget);
            const form = btn.closest('.comment-form');
            const mapId = form.data('map-id');
            const content = form.find('.comment-input').val().trim();
            const rating = form.find('.rating-input').val();
            
            if (!content) {
                this.showError('コメントを入力してください');
                return;
            }
            
            btn.prop('disabled', true);
            
            $.post(mindmapData.ajaxurl, {
                action: 'add_comment',
                map_id: mapId,
                content: content,
                rating: rating,
                nonce: mindmapData.nonce
            }).done((response) => {
                if (response.success) {
                    this.addCommentToList(response.data);
                    form.find('.comment-input').val('');
                    form.find('.rating-input').val('0');
                    this.showSuccess('コメントを投稿しました！');
                } else {
                    this.showError('コメントの投稿に失敗しました');
                }
            }).always(() => {
                btn.prop('disabled', false);
            });
        }
        
        handleFollow(e) {
            e.preventDefault();
            
            if (!mindmapData.isLoggedIn) {
                this.showLoginRequired();
                return;
            }
            
            const btn = $(e.currentTarget);
            const userId = btn.data('user-id');
            const isFollowing = btn.hasClass('following');
            const action = isFollowing ? 'unfollow' : 'follow';
            
            btn.prop('disabled', true);
            
            $.post(mindmapData.ajaxurl, {
                action: 'follow_user',
                user_id: userId,
                action_type: action,
                nonce: mindmapData.nonce
            }).done((response) => {
                if (response.success) {
                    btn.toggleClass('following');
                    btn.text(isFollowing ? 'フォロー' : 'フォロー中');
                    
                    this.showSuccess(isFollowing ? 'フォローを解除しました' : 'フォローしました！');
                } else {
                    this.showError('操作に失敗しました');
                }
            }).always(() => {
                btn.prop('disabled', false);
            });
        }
        
        renderPagination(pagination) {
            const container = $('#community-pagination');
            container.empty();
            
            if (pagination.total_pages <= 1) return;
            
            const currentPage = pagination.current_page;
            const totalPages = pagination.total_pages;
            
            // 前のページボタン
            if (currentPage > 1) {
                container.append(
                    `<button class="pagination-btn" data-page="${currentPage - 1}">‹ 前へ</button>`
                );
            }
            
            // ページ番号
            for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
                const activeClass = i === currentPage ? 'active' : '';
                container.append(
                    `<button class="pagination-btn ${activeClass}" data-page="${i}">${i}</button>`
                );
            }
            
            // 次のページボタン
            if (currentPage < totalPages) {
                container.append(
                    `<button class="pagination-btn" data-page="${currentPage + 1}">次へ ›</button>`
                );
            }
            
            // ページネーションイベント
            container.find('.pagination-btn').on('click', (e) => {
                const page = $(e.target).data('page');
                if (page && page !== currentPage) {
                    this.loadCommunityMaps(page);
                }
            });
        }
        
        getCategoryLabel(category) {
            const labels = {
                'gyosei': '行政法',
                'minpo': '民法',
                'kenpou': '憲法',
                'shoken': '商法・会社法',
                'custom': 'カスタム'
            };
            return labels[category] || category;
        }
        
        checkUserLiked(mapId) {
            // ローカルストレージまたはサーバーから取得
            // 簡易実装
            return false;
        }
        
        addCommentToList(comment) {
            // コメントリストに新しいコメントを追加
            // 実装詳細は省略
        }
        
        showSuccess(message) {
            this.showNotification(message, 'success');
        }
        
        showError(message) {
            this.showNotification(message, 'error');
        }
        
        showLoginRequired() {
            this.showNotification('この機能を利用するにはログインが必要です', 'warning');
        }
        
        showNotification(message, type = 'info') {
            const notification = $(`
                <div class="community-notification ${type}">
                    <span>${message}</span>
                    <button class="close-notification">×</button>
                </div>
            `);
            
            $('body').append(notification);
            
            setTimeout(() => {
                notification.fadeOut(() => notification.remove());
            }, 5000);
            
            notification.find('.close-notification').on('click', () => {
                notification.fadeOut(() => notification.remove());
            });
        }
    }
    
    // 学習グループ管理
    class StudyGroupManager {
        constructor() {
            this.init();
        }
        
        init() {
            this.bindEvents();
            this.loadStudyGroups();
        }
        
        bindEvents() {
            // グループ作成
            $('#create-group-btn').on('click', this.showCreateGroupModal.bind(this));
            $('#create-group-form').on('submit', this.handleCreateGroup.bind(this));
            
            // グループ参加
            $(document).on('click', '.join-group-btn', this.handleJoinGroup.bind(this));
            
            // グループ検索
            $('#groups-search').on('input', $.debounce(300, this.handleGroupSearch.bind(this)));
            
            // カテゴリフィルター
            $('#groups-category').on('change', this.handleCategoryFilter.bind(this));
            
            // マイグループフィルター
            $('#my-groups-only').on('change', this.handleMyGroupsFilter.bind(this));
        }
        
        loadStudyGroups() {
            const category = $('#groups-category').val();
            const search = $('#groups-search').val();
            const userGroupsOnly = $('#my-groups-only').is(':checked');
            
            $.post(mindmapData.ajaxurl, {
                action: 'get_study_groups',
                category: category,
                search: search,
                user_groups_only: userGroupsOnly,
                nonce: mindmapData.nonce
            }).done((response) => {
                if (response.success) {
                    this.renderStudyGroups(response.data);
                } else {
                    this.showError('グループの読み込みに失敗しました');
                }
            });
        }
        
        renderStudyGroups(groups) {
            const container = $('#groups-list');
            container.empty();
            
            if (groups.length === 0) {
                container.html('<p class="no-groups">グループが見つかりませんでした。</p>');
                return;
            }
            
            groups.forEach(group => {
                const groupCard = this.createGroupCard(group);
                container.append(groupCard);
            });
        }
        
        createGroupCard(group) {
            const membershipStatus = this.getMembershipStatus(group);
            const privacyIcon = group.is_private ? '🔒' : '🌍';
            
            return $(`
                <div class="group-card" data-group-id="${group.id}">
                    <div class="group-header">
                        <h3 class="group-name">
                            ${privacyIcon} ${group.name}
                        </h3>
                        <div class="group-meta">
                            <span class="group-category">${this.getCategoryLabel(group.category)}</span>
                            <span class="group-members">${group.member_count}/${group.max_members} メンバー</span>
                        </div>
                    </div>
                    
                    <div class="group-body">
                        <p class="group-description">${group.description}</p>
                        
                        <div class="group-info">
                            <div class="group-creator">
                                <span>作成者: ${group.creator_name}</span>
                            </div>
                            
                            ${group.target_exam_date ? `
                                <div class="group-exam-date">
                                    <span>目標試験日: ${group.target_exam_date}</span>
                                </div>
                            ` : ''}
                            
                            ${group.last_activity ? `
                                <div class="group-activity">
                                    <span>最終活動: ${this.formatDate(group.last_activity)}</span>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <div class="group-footer">
                        <div class="group-actions">
                            ${this.renderGroupAction(group, membershipStatus)}
                        </div>
                    </div>
                </div>
            `);
        }
        
        renderGroupAction(group, membershipStatus) {
            if (membershipStatus === 'member') {
                return `<a href="${group.group_url}" class="btn-primary">グループを開く</a>`;
            } else if (membershipStatus === 'full') {
                return `<button class="btn-secondary" disabled>満員</button>`;
            } else {
                return `<button class="btn-primary join-group-btn" data-group-id="${group.id}">参加する</button>`;
            }
        }
        
        getMembershipStatus(group) {
            // 簡易実装 - 実際はサーバーサイドで判定
            if (group.member_count >= group.max_members) {
                return 'full';
            }
            return 'available';
        }
        
        showCreateGroupModal() {
            $('#create-group-modal').show();
        }
        
        handleCreateGroup(e) {
            e.preventDefault();
            
            const form = $(e.target);
            const formData = {
                action: 'create_study_group',
                name: form.find('[name="name"]').val(),
                description: form.find('[name="description"]').val(),
                category: form.find('[name="category"]').val(),
                max_members: form.find('[name="max_members"]').val(),
                is_private: form.find('[name="is_private"]').is(':checked'),
                target_exam_date: form.find('[name="target_exam_date"]').val(),
                nonce: mindmapData.nonce
            };
            
            $.post(mindmapData.ajaxurl, formData).done((response) => {
                if (response.success) {
                    $('#create-group-modal').hide();
                    this.loadStudyGroups();
                    this.showSuccess('グループを作成しました！');
                    
                    if (response.data.join_code) {
                        this.showJoinCode(response.data.join_code);
                    }
                } else {
                    this.showError('グループの作成に失敗しました');
                }
            });
        }
        
        handleJoinGroup(e) {
            const btn = $(e.currentTarget);
            const groupId = btn.data('group-id');
            
            // プライベートグループの場合は参加コードを要求
            this.promptJoinCode((joinCode) => {
                $.post(mindmapData.ajaxurl, {
                    action: 'join_study_group',
                    group_id: groupId,
                    join_code: joinCode,
                    nonce: mindmapData.nonce
                }).done((response) => {
                    if (response.success) {
                        this.loadStudyGroups();
                        this.showSuccess(`${response.data.group_name}に参加しました！`);
                    } else {
                        this.showError(response.data || 'グループへの参加に失敗しました');
                    }
                });
            });
        }
        
        promptJoinCode(callback) {
            const joinCode = prompt('参加コードを入力してください（公開グループの場合は空白のままOKを押してください）:');
            callback(joinCode || '');
        }
        
        showJoinCode(joinCode) {
            alert(`グループの参加コード: ${joinCode}\nこのコードを他のメンバーに共有してください。`);
        }
        
        handleGroupSearch() {
            this.loadStudyGroups();
        }
        
        handleCategoryFilter() {
            this.loadStudyGroups();
        }
        
        handleMyGroupsFilter() {
            this.loadStudyGroups();
        }
        
        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ja-JP');
        }
        
        getCategoryLabel(category) {
            const labels = {
                'gyosei': '行政法',
                'minpo': '民法',
                'kenpou': '憲法',
                'general': '一般'
            };
            return labels[category] || category;
        }
        
        showSuccess(message) {
            console.log('Success:', message);
            // 通知実装
        }
        
        showError(message) {
            console.log('Error:', message);
            // エラー通知実装
        }
    }
    
    // jQuery debounce プラグイン
    $.debounce = function(delay, fn) {
        let timeoutId;
        return function(...args) {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => fn.apply(this, args), delay);
        };
    };
    
    // グローバル初期化
    window.initCommunityMaps = function() {
        if ($('#community-maps-container').length) {
            new CommunityManager();
        }
    };
    
    window.initStudyGroups = function() {
        if ($('#study-groups-container').length) {
            new StudyGroupManager();
        }
    };
    
    // 自動初期化
    $(document).ready(function() {
        initCommunityMaps();
        initStudyGroups();
    });
});