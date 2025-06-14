/**
 * 学習進捗管理プラグイン - フロントエンドスクリプト（完全版）
 */

(function($) {
    'use strict';

    // プラグインのメインクラス
    class StudyProgressManager {
        constructor() {
            this.currentSubject = null;
            this.progressData = {};
            this.saveTimeout = null;
            this.hasUnsavedData = false;
            this.subjects = [];
            this.init();
        }

        init() {
            this.bindEvents();
            this.loadInitialData();
            this.initializeInterface();
        }

        // イベントバインディング
        bindEvents() {
            // 科目タブの切り替え
            $(document).on('click', '.spm-subject-tab', (e) => {
                this.switchSubject($(e.currentTarget).data('subject'));
            });

            // 章の展開/折りたたみ
            $(document).on('click', '.spm-chapter-header', (e) => {
                if (!$(e.target).is('input, button, .spm-checkbox-wrapper, .spm-checkbox')) {
                    this.toggleChapter($(e.currentTarget));
                }
            });

            // 進捗チェックボックスの変更
            $(document).on('change', '.spm-checkbox', (e) => {
                this.handleProgressChange($(e.currentTarget));
            });

            // 自動保存の実装
            $(document).on('change', '.spm-checkbox', () => {
                this.scheduleAutoSave();
            });

            // ウィンドウリサイズ対応
            $(window).on('resize', () => {
                this.handleResize();
            });

            // ページ離脱時の警告（未保存データがある場合）
            $(window).on('beforeunload', (e) => {
                if (this.hasUnsavedChanges()) {
                    return '未保存の変更があります。ページを離れますか？';
                }
            });
        }

        // 初期データの読み込み
        loadInitialData() {
            this.showLoading();
            
            $.ajax({
                url: spm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_subjects',
                    nonce: spm_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.processSubjectData(response.data);
                        this.hideLoading();
                    } else {
                        this.showError('データの読み込みに失敗しました。');
                    }
                },
                error: () => {
                    this.showError('サーバーとの通信に失敗しました。');
                }
            });
        }

        // 科目データの処理
        processSubjectData(subjects) {
            if (!subjects || subjects.length === 0) {
                this.showEmpty('科目が登録されていません。');
                return;
            }

            this.subjects = subjects;
            this.currentSubject = subjects[0].subject_key;
            this.renderSubjectTabs();
            this.loadSubjectProgress(this.currentSubject);
        }

        // 科目タブの描画
        renderSubjectTabs() {
            const tabsContainer = $('.spm-subject-tabs');
            if (tabsContainer.length === 0) return;

            tabsContainer.empty();
            
            this.subjects.forEach(subject => {
                const tab = $(`
                    <button class="spm-subject-tab" 
                            data-subject="${subject.subject_key}"
                            style="--subject-color: ${subject.progress_color}">
                        ${subject.subject_name}
                    </button>
                `);
                
                if (subject.subject_key === this.currentSubject) {
                    tab.addClass('active');
                }
                
                tabsContainer.append(tab);
            });
        }

        // 科目の切り替え
        switchSubject(subjectKey) {
            if (this.currentSubject === subjectKey) return;

            $('.spm-subject-tab').removeClass('active');
            $(`.spm-subject-tab[data-subject="${subjectKey}"]`).addClass('active');
            
            this.currentSubject = subjectKey;
            this.loadSubjectProgress(subjectKey);
        }

        // 科目の進捗データ読み込み
        loadSubjectProgress(subjectKey) {
            this.showLoading();

            $.ajax({
                url: spm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_subject_progress',
                    subject_key: subjectKey,
                    nonce: spm_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderSubjectContent(response.data);
                        this.hideLoading();
                    } else {
                        this.showError('進捗データの読み込みに失敗しました。');
                    }
                },
                error: () => {
                    this.showError('サーバーとの通信に失敗しました。');
                }
            });
        }

        // 科目コンテンツの描画
        renderSubjectContent(data) {
            const container = $('.spm-progress-content');
            if (container.length === 0) return;

            const subject = this.subjects.find(s => s.subject_key === this.currentSubject);
            const progressPercentage = this.calculateOverallProgress(data.progress);

            const html = `
                <div class="spm-subject-container" style="--subject-color: ${subject.progress_color}">
                    <div class="spm-subject-header">
                        <h2 class="spm-subject-title">${subject.subject_name}</h2>
                        <div class="spm-overall-progress">
                            <div class="spm-progress-percentage">${progressPercentage}%</div>
                            <div class="spm-progress-label">全体進捗</div>
                        </div>
                    </div>
                    
                    ${this.renderProgressSummary(data.progress)}
                    
                    <div class="spm-chapters-list">
                        ${this.renderChapters(data.structure, data.progress)}
                    </div>
                </div>
            `;

            container.html(html);
            this.updateProgressBars();
        }

        // 進捗サマリーの描画
        renderProgressSummary(progressData) {
            const stats = this.calculateProgressStats(progressData);
            
            return `
                <div class="spm-progress-summary">
                    <h3>学習進捗サマリー</h3>
                    <div class="spm-summary-grid">
                        <div class="spm-summary-item">
                            <div class="spm-summary-number">${stats.totalItems}</div>
                            <div class="spm-summary-label">総項目数</div>
                        </div>
                        <div class="spm-summary-item">
                            <div class="spm-summary-number">${stats.understoodItems}</div>
                            <div class="spm-summary-label">理解済み</div>
                        </div>
                        <div class="spm-summary-item">
                            <div class="spm-summary-number">${stats.masteredItems}</div>
                            <div class="spm-summary-label">習得済み</div>
                        </div>
                        <div class="spm-summary-item">
                            <div class="spm-summary-number">${stats.completionRate}%</div>
                            <div class="spm-summary-label">完了率</div>
                        </div>
                    </div>
                </div>
            `;
        }

        // 章の描画
        renderChapters(structure, progressData) {
            if (!structure || !structure.chapters) {
                return '<div class="spm-empty">この科目の構造が設定されていません。</div>';
            }

            return structure.chapters.map(chapter => {
                const chapterProgress = this.calculateChapterProgress(chapter, progressData);
                
                return `
                    <div class="spm-chapter-item">
                        <div class="spm-chapter-header" data-chapter="${chapter.chapter_number}">
                            <div class="spm-chapter-info">
                                <h3 class="spm-chapter-title">第${chapter.chapter_number}章 ${chapter.chapter_title}</h3>
                                <div class="spm-chapter-progress-bar">
                                    <div class="spm-chapter-progress-fill" 
                                         style="width: ${chapterProgress}%"></div>
                                </div>
                                <span class="spm-progress-text">${chapterProgress}%</span>
                            </div>
                            <span class="spm-expand-icon">▼</span>
                        </div>
                        <div class="spm-chapter-content">
                            ${this.renderSections(chapter, progressData)}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // 節の描画
        renderSections(chapter, progressData) {
            if (!chapter.sections || chapter.sections.length === 0) {
                return '<div class="spm-empty">この章には節が設定されていません。</div>';
            }

            return `
                <div class="spm-sections-grid">
                    ${chapter.sections.map(section => `
                        <div class="spm-section-card">
                            <div class="spm-section-header">
                                第${section.section_number}節 ${section.section_title}
                            </div>
                            <div class="spm-items-grid">
                                ${this.renderItems(chapter.chapter_number, section, progressData)}
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        // 項の描画
        renderItems(chapterNum, section, progressData) {
            if (!section.items || section.items.length === 0) {
                return '<div class="spm-empty">この節には項が設定されていません。</div>';
            }

            return section.items.map(item => {
                const itemKey = `${chapterNum}-${section.section_number}-${item.item_number}`;
                const progress = progressData[itemKey] || { understanding: false, mastery: false };

                return `
                    <div class="spm-item-row">
                        <div class="spm-item-title">
                            項${item.item_number}: ${item.item_title}
                        </div>
                        <div class="spm-progress-controls">
                            <div class="spm-checkbox-group">
                                <label class="spm-checkbox-wrapper spm-understanding">
                                    <input type="checkbox" class="spm-checkbox" 
                                           data-type="understanding"
                                           data-chapter="${chapterNum}"
                                           data-section="${section.section_number}"
                                           data-item="${item.item_number}"
                                           ${progress.understanding ? 'checked' : ''}>
                                    <span class="spm-checkbox-label">理解</span>
                                </label>
                                <label class="spm-checkbox-wrapper spm-mastery">
                                    <input type="checkbox" class="spm-checkbox"
                                           data-type="mastery" 
                                           data-chapter="${chapterNum}"
                                           data-section="${section.section_number}"
                                           data-item="${item.item_number}"
                                           ${progress.mastery ? 'checked' : ''}>
                                    <span class="spm-checkbox-label">習得</span>
                                </label>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // 章の展開/折りたたみ
        toggleChapter(chapterHeader) {
            const content = chapterHeader.next('.spm-chapter-content');
            const icon = chapterHeader.find('.spm-expand-icon');
            
            if (content.hasClass('expanded')) {
                content.removeClass('expanded').slideUp(300);
                icon.removeClass('rotated').text('▼');
                chapterHeader.removeClass('expanded');
            } else {
                content.addClass('expanded').slideDown(300);
                icon.addClass('rotated').text('▲');
                chapterHeader.addClass('expanded');
            }
        }

        // 進捗変更の処理
        handleProgressChange(checkbox) {
            const chapter = checkbox.data('chapter');
            const section = checkbox.data('section');
            const item = checkbox.data('item');
            const type = checkbox.data('type');
            const checked = checkbox.is(':checked');

            // 習得にチェックが入った場合、理解も自動的にチェック
            if (type === 'mastery' && checked) {
                const understandingCheckbox = checkbox.closest('.spm-item-row')
                    .find('.spm-checkbox[data-type="understanding"]');
                if (!understandingCheckbox.is(':checked')) {
                    understandingCheckbox.prop('checked', true);
                }
            }

            // 理解のチェックが外された場合、習得も自動的に外す
            if (type === 'understanding' && !checked) {
                const masteryCheckbox = checkbox.closest('.spm-item-row')
                    .find('.spm-checkbox[data-type="mastery"]');
                if (masteryCheckbox.is(':checked')) {
                    masteryCheckbox.prop('checked', false);
                }
            }

            this.updateProgressBars();
            this.markAsUnsaved();
        }

        // 進捗バーの更新
        updateProgressBars() {
            // 章レベルの進捗バー更新
            $('.spm-chapter-item').each((index, element) => {
                const chapterElement = $(element);
                const chapterNum = chapterElement.find('.spm-chapter-header').data('chapter');
                const progress = this.calculateChapterProgressFromDOM(chapterElement);
                
                chapterElement.find('.spm-chapter-progress-fill').css('width', progress + '%');
                chapterElement.find('.spm-progress-text').text(progress + '%');
            });

            // 全体進捗の更新
            const overallProgress = this.calculateOverallProgressFromDOM();
            $('.spm-progress-percentage').text(overallProgress + '%');
        }

        // DOMから章の進捗を計算
        calculateChapterProgressFromDOM(chapterElement) {
            const checkboxes = chapterElement.find('.spm-checkbox[data-type="mastery"]');
            if (checkboxes.length === 0) return 0;
            
            const checkedCount = checkboxes.filter(':checked').length;
            return Math.round((checkedCount / checkboxes.length) * 100);
        }

        // DOMから全体進捗を計算
        calculateOverallProgressFromDOM() {
            const allCheckboxes = $('.spm-checkbox[data-type="mastery"]');
            if (allCheckboxes.length === 0) return 0;
            
            const checkedCount = allCheckboxes.filter(':checked').length;
            return Math.round((checkedCount / allCheckboxes.length) * 100);
        }

        // 進捗統計の計算
        calculateProgressStats(progressData) {
            const stats = {
                totalItems: 0,
                understoodItems: 0,
                masteredItems: 0,
                completionRate: 0
            };

            for (const key in progressData) {
                stats.totalItems++;
                if (progressData[key].understanding) stats.understoodItems++;
                if (progressData[key].mastery) stats.masteredItems++;
            }

            stats.completionRate = stats.totalItems > 0 ? 
                Math.round((stats.masteredItems / stats.totalItems) * 100) : 0;

            return stats;
        }

        // 全体進捗の計算
        calculateOverallProgress(progressData) {
            const stats = this.calculateProgressStats(progressData);
            return stats.completionRate;
        }

        // 章進捗の計算
        calculateChapterProgress(chapter, progressData) {
            if (!chapter.sections) return 0;

            let totalItems = 0;
            let masteredItems = 0;

            chapter.sections.forEach(section => {
                if (section.items) {
                    section.items.forEach(item => {
                        totalItems++;
                        const itemKey = `${chapter.chapter_number}-${section.section_number}-${item.item_number}`;
                        if (progressData[itemKey] && progressData[itemKey].mastery) {
                            masteredItems++;
                        }
                    });
                }
            });

            return totalItems > 0 ? Math.round((masteredItems / totalItems) * 100) : 0;
        }

        // 自動保存のスケジュール
        scheduleAutoSave() {
            if (this.saveTimeout) {
                clearTimeout(this.saveTimeout);
            }

            this.saveTimeout = setTimeout(() => {
                this.saveProgress();
            }, 2000); // 2秒後に保存
        }

        // 進捗の保存
        saveProgress() {
            const progressData = this.collectProgressData();
            
            this.showSaveIndicator('saving');

            $.ajax({
                url: spm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_progress',
                    subject_key: this.currentSubject,
                    progress_data: JSON.stringify(progressData),
                    nonce: spm_ajax.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showSaveIndicator('success');
                        this.markAsSaved();
                    } else {
                        this.showSaveIndicator('error');
                    }
                },
                error: () => {
                    this.showSaveIndicator('error');
                }
            });
        }

        // 進捗データの収集
        collectProgressData() {
            const progressData = {};

            $('.spm-checkbox').each((index, element) => {
                const checkbox = $(element);
                const chapter = checkbox.data('chapter');
                const section = checkbox.data('section');
                const item = checkbox.data('item');
                const type = checkbox.data('type');
                const checked = checkbox.is(':checked');

                const key = `${chapter}-${section}-${item}`;
                
                if (!progressData[key]) {
                    progressData[key] = {
                        chapter: chapter,
                        section: section,
                        item: item,
                        understanding: false,
                        mastery: false
                    };
                }

                progressData[key][type] = checked;
            });

            return progressData;
        }

        // 保存インジケーターの表示
        showSaveIndicator(type) {
            let message = '';
            let className = 'spm-save-indicator';

            switch (type) {
                case 'saving':
                    message = '保存中...';
                    className += ' saving';
                    break;
                case 'success':
                    message = '保存しました';
                    className += ' success';
                    break;
                case 'error':
                    message = '保存に失敗しました';
                    className += ' error';
                    break;
            }

            // 既存のインジケーターを削除
            $('.spm-save-indicator').remove();

            // 新しいインジケーターを追加
            const indicator = $(`<div class="${className}">${message}</div>`);
            $('body').append(indicator);

            // アニメーション付きで表示
            setTimeout(() => {
                indicator.addClass('show');
            }, 100);

            // 成功・エラーの場合は3秒後に非表示
            if (type === 'success' || type === 'error') {
                setTimeout(() => {
                    indicator.removeClass('show');
                    setTimeout(() => {
                        indicator.remove();
                    }, 300);
                }, 3000);
            }
        }

        // ローディング表示
        showLoading() {
            const container = $('.spm-progress-content');
            container.html('<div class="spm-loading">読み込み中...</div>');
        }

        // ローディング非表示
        hideLoading() {
            $('.spm-loading').remove();
        }

        // エラー表示
        showError(message) {
            const container = $('.spm-progress-content');
            container.html(`<div class="spm-error">${message}</div>`);
        }

        // 空状態の表示
        showEmpty(message) {
            const container = $('.spm-progress-content');
            container.html(`<div class="spm-empty">${message}</div>`);
        }

        // 未保存マーク
        markAsUnsaved() {
            this.hasUnsavedData = true;
        }

        // 保存済みマーク
        markAsSaved() {
            this.hasUnsavedData = false;
        }

        // 未保存データの確認
        hasUnsavedChanges() {
            return this.hasUnsavedData || false;
        }

        // インターフェースの初期化
        initializeInterface() {
            // 進捗データのローカルストレージからの復元
            this.restoreFromLocalStorage();

            // キーボードショートカット
            this.initKeyboardShortcuts();

            // タッチイベント対応
            this.initTouchEvents();
        }

        // ローカルストレージからの復元
        restoreFromLocalStorage() {
            try {
                const savedData = localStorage.getItem('spm_draft_progress');
                if (savedData) {
                    const data = JSON.parse(savedData);
                    // 必要に応じて復元処理を実装
                }
            } catch (e) {
                console.warn('ローカルストレージからの復元に失敗しました:', e);
            }
        }

        // キーボードショートカット
        initKeyboardShortcuts() {
            $(document).on('keydown', (e) => {
                // Ctrl+S で保存
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    this.saveProgress();
                }

                // Ctrl+A で全て選択
                if (e.ctrlKey && e.key === 'a' && e.target.classList.contains('spm-progress-container')) {
                    e.preventDefault();
                    this.selectAllItems();
                }

                // Esc で全ての章を閉じる
                if (e.key === 'Escape') {
                    this.collapseAllChapters();
                }
            });
        }

        // タッチイベント対応
        initTouchEvents() {
            // スワイプで科目切り替え
            let startX = 0;
            let startY = 0;

            $('.spm-progress-container').on('touchstart', (e) => {
                startX = e.originalEvent.touches[0].clientX;
                startY = e.originalEvent.touches[0].clientY;
            });

            $('.spm-progress-container').on('touchend', (e) => {
                if (!startX || !startY) return;

                const endX = e.originalEvent.changedTouches[0].clientX;
                const endY = e.originalEvent.changedTouches[0].clientY;

                const diffX = startX - endX;
                const diffY = startY - endY;

                // 水平方向のスワイプが垂直方向より大きい場合
                if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                    if (diffX > 0) {
                        // 左スワイプ - 次の科目
                        this.switchToNextSubject();
                    } else {
                        // 右スワイプ - 前の科目
                        this.switchToPrevSubject();
                    }
                }

                startX = 0;
                startY = 0;
            });
        }

        // 次の科目に切り替え
        switchToNextSubject() {
            const currentIndex = this.subjects.findIndex(s => s.subject_key === this.currentSubject);
            const nextIndex = (currentIndex + 1) % this.subjects.length;
            this.switchSubject(this.subjects[nextIndex].subject_key);
        }

        // 前の科目に切り替え
        switchToPrevSubject() {
            const currentIndex = this.subjects.findIndex(s => s.subject_key === this.currentSubject);
            const prevIndex = currentIndex === 0 ? this.subjects.length - 1 : currentIndex - 1;
            this.switchSubject(this.subjects[prevIndex].subject_key);
        }

        // 全項目選択
        selectAllItems() {
            $('.spm-checkbox[data-type="mastery"]').prop('checked', true);
            $('.spm-checkbox[data-type="understanding"]').prop('checked', true);
            this.updateProgressBars();
            this.markAsUnsaved();
        }

        // 全章を閉じる
        collapseAllChapters() {
            $('.spm-chapter-header.expanded').each((index, element) => {
                this.toggleChapter($(element));
            });
        }

        // ウィンドウリサイズ処理
        handleResize() {
            // モバイル表示の調整
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                $('.spm-progress-container').addClass('mobile-layout');
            } else {
                $('.spm-progress-container').removeClass('mobile-layout');
            }
        }

        // パフォーマンス最適化
        debounce(func, wait) {
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

        // エクスポート機能
        exportProgress() {
            const progressData = this.collectProgressData();
            const exportData = {
                subject: this.currentSubject,
                date: new Date().toISOString(),
                progress: progressData
            };

            const dataStr = JSON.stringify(exportData, null, 2);
            const dataBlob = new Blob([dataStr], { type: 'application/json' });

            const link = document.createElement('a');
            link.href = URL.createObjectURL(dataBlob);
            link.download = `progress_${this.currentSubject}_${new Date().toISOString().split('T')[0]}.json`;
            link.click();
        }

        // インポート機能
        importProgress(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const data = JSON.parse(e.target.result);
                    this.restoreProgress(data.progress);
                } catch (error) {
                    alert('ファイルの読み込みに失敗しました。');
                }
            };
            reader.readAsText(file);
        }

        // 進捗の復元
        restoreProgress(progressData) {
            for (const key in progressData) {
                const progress = progressData[key];
                const understandingCheckbox = $(`.spm-checkbox[data-chapter="${progress.chapter}"][data-section="${progress.section}"][data-item="${progress.item}"][data-type="understanding"]`);
                const masteryCheckbox = $(`.spm-checkbox[data-chapter="${progress.chapter}"][data-section="${progress.section}"][data-item="${progress.item}"][data-type="mastery"]`);

                understandingCheckbox.prop('checked', progress.understanding);
                masteryCheckbox.prop('checked', progress.mastery);
            }

            this.updateProgressBars();
            this.markAsUnsaved();
        }

        // 統計情報の表示
        showStatistics() {
            const progressData = this.collectProgressData();
            const stats = this.calculateProgressStats(progressData);

            const statsHTML = `
                <div class="spm-statistics-modal">
                    <div class="spm-statistics-content">
                        <h3>学習統計</h3>
                        <div class="spm-stats-grid">
                            <div class="spm-stat-item">
                                <div class="spm-stat-number">${stats.totalItems}</div>
                                <div class="spm-stat-label">総項目数</div>
                            </div>
                            <div class="spm-stat-item">
                                <div class="spm-stat-number">${stats.understoodItems}</div>
                                <div class="spm-stat-label">理解済み</div>
                            </div>
                            <div class="spm-stat-item">
                                <div class="spm-stat-number">${stats.masteredItems}</div>
                                <div class="spm-stat-label">習得済み</div>
                            </div>
                            <div class="spm-stat-item">
                                <div class="spm-stat-number">${stats.completionRate}%</div>
                                <div class="spm-stat-label">完了率</div>
                            </div>
                        </div>
                        <button class="button" onclick="$('.spm-statistics-modal').remove()">閉じる</button>
                    </div>
                </div>
            `;

            $('body').append(statsHTML);
        }
    }

    // プラグインの初期化
    $(document).ready(function() {
        // プラグインのインスタンス化
        window.spmManager = new StudyProgressManager();

        // グローバル関数の定義（外部から呼び出し可能）
        window.SPM = {
            saveProgress: () => window.spmManager.saveProgress(),
            exportProgress: () => window.spmManager.exportProgress(),
            showStatistics: () => window.spmManager.showStatistics(),
            selectAll: () => window.spmManager.selectAllItems(),
            collapseAll: () => window.spmManager.collapseAllChapters()
        };
    });

    // ユーティリティ関数
    const Utils = {
        // 日付フォーマット
        formatDate: (date) => {
            return new Intl.DateTimeFormat('ja-JP', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            }).format(date);
        },

        // パーセンテージ計算
        calculatePercentage: (current, total) => {
            return total > 0 ? Math.round((current / total) * 100) : 0;
        },

        // カラーの明度調整
        adjustColor: (color, amount) => {
            const clamp = (val) => Math.min(Math.max(val, 0), 255);
            const num = parseInt(color.replace("#", ""), 16);
            const r = clamp((num >> 16) + amount);
            const g = clamp(((num >> 8) & 0x00FF) + amount);
            const b = clamp((num & 0x0000FF) + amount);
            return "#" + ((r << 16) | (g << 8) | b).toString(16).padStart(6, '0');
        },

        // アニメーション
        animate: (element, property, targetValue, duration = 300) => {
            $(element).animate({ [property]: targetValue }, duration);
        },

        // HTML エスケープ
        escapeHtml: (text) => {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        // デバウンス関数
        debounce: (func, wait) => {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // スロットル関数
        throttle: (func, limit) => {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        }
    };

    // ブラウザ対応チェック
    const BrowserSupport = {
        checkLocalStorage: () => {
            try {
                localStorage.setItem('test', 'test');
                localStorage.removeItem('test');
                return true;
            } catch (e) {
                return false;
            }
        },

        checkTouchSupport: () => {
            return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        },

        checkServiceWorker: () => {
            return 'serviceWorker' in navigator;
        },

        checkWebGL: () => {
            try {
                const canvas = document.createElement('canvas');
                return !!(window.WebGLRenderingContext && canvas.getContext('webgl'));
            } catch (e) {
                return false;
            }
        },

        showUnsupportedBrowser: () => {
            const message = 'お使いのブラウザは一部の機能をサポートしていません。最新のブラウザをご利用ください。';
            $('.spm-progress-container').prepend(`<div class="spm-browser-warning">${message}</div>`);
        }
    };

    // パフォーマンス監視
    const Performance = {
        startTime: 0,
        
        start: (label) => {
            Performance.startTime = performance.now();
            console.time(label);
        },

        end: (label) => {
            const endTime = performance.now();
            console.timeEnd(label);
            console.log(`${label}: ${endTime - Performance.startTime}ms`);
        },

        measure: (name, startMark, endMark) => {
            if (performance.measure) {
                performance.measure(name, startMark, endMark);
            }
        },

        mark: (name) => {
            if (performance.mark) {
                performance.mark(name);
            }
        }
    };

    // エラーハンドリング
    const ErrorHandler = {
        init: () => {
            window.addEventListener('error', ErrorHandler.handleError);
            window.addEventListener('unhandledrejection', ErrorHandler.handlePromiseRejection);
        },

        handleError: (event) => {
            console.error('JavaScript Error:', event.error);
            ErrorHandler.reportError(event.error);
        },

        handlePromiseRejection: (event) => {
            console.error('Unhandled Promise Rejection:', event.reason);
            ErrorHandler.reportError(event.reason);
        },

        reportError: (error) => {
            // エラー報告ロジック（実装時に追加）
            if (typeof spm_ajax !== 'undefined') {
                $.ajax({
                    url: spm_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'spm_log_error',
                        error: error.toString(),
                        stack: error.stack,
                        nonce: spm_ajax.nonce
                    },
                    error: () => {
                        // エラー報告に失敗した場合の処理
                        console.warn('Failed to report error to server');
                    }
                });
            }
        }
    };

    // アクセシビリティ支援
    const Accessibility = {
        init: () => {
            Accessibility.setupKeyboardNavigation();
            Accessibility.setupScreenReaderSupport();
            Accessibility.setupHighContrastMode();
        },

        setupKeyboardNavigation: () => {
            // Tabキーでの移動を改善
            $(document).on('keydown', '.spm-checkbox', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(e.target).click();
                }
            });

            // 矢印キーでの項目移動
            $(document).on('keydown', '.spm-item-row', (e) => {
                const currentRow = $(e.currentTarget);
                let targetRow = null;

                switch (e.key) {
                    case 'ArrowDown':
                        targetRow = currentRow.next('.spm-item-row');
                        break;
                    case 'ArrowUp':
                        targetRow = currentRow.prev('.spm-item-row');
                        break;
                }

                if (targetRow && targetRow.length > 0) {
                    targetRow.find('.spm-checkbox').first().focus();
                }
            });
        },

        setupScreenReaderSupport: () => {
            // ARIA属性の追加
            $('.spm-progress-container').attr('role', 'application');
            $('.spm-chapter-header').attr('role', 'button').attr('aria-expanded', 'false');
            $('.spm-checkbox').attr('role', 'checkbox');
            
            // 進捗バーにARIA属性追加
            $('.spm-chapter-progress-bar').attr('role', 'progressbar').attr('aria-valuemin', '0').attr('aria-valuemax', '100');
        },

        setupHighContrastMode: () => {
            // 高コントラストモードの検出
            if (window.matchMedia('(prefers-contrast: high)').matches) {
                $('body').addClass('spm-high-contrast');
            }
        },

        announceProgress: (message) => {
            // スクリーンリーダーへの通知
            const announcement = $('<div aria-live="polite" class="sr-only"></div>');
            announcement.text(message);
            $('body').append(announcement);
            
            setTimeout(() => {
                announcement.remove();
            }, 1000);
        }
    };

    // データ検証
    const DataValidator = {
        validateProgressData: (data) => {
            if (!data || typeof data !== 'object') {
                return false;
            }

            for (const key in data) {
                const item = data[key];
                if (!item.hasOwnProperty('understanding') || !item.hasOwnProperty('mastery')) {
                    return false;
                }
                if (typeof item.understanding !== 'boolean' || typeof item.mastery !== 'boolean') {
                    return false;
                }
            }

            return true;
        },

        sanitizeData: (data) => {
            const sanitized = {};
            
            for (const key in data) {
                if (typeof key === 'string' && /^[\w-]+$/.test(key)) {
                    const item = data[key];
                    sanitized[key] = {
                        understanding: Boolean(item.understanding),
                        mastery: Boolean(item.mastery),
                        chapter: parseInt(item.chapter) || 0,
                        section: parseInt(item.section) || 0,
                        item: parseInt(item.item) || 0
                    };
                }
            }

            return sanitized;
        }
    };

    // 通知システム
    const NotificationSystem = {
        show: (message, type = 'info', duration = 5000) => {
            const notification = $(`
                <div class="spm-notification spm-notification-${type}">
                    <span class="spm-notification-icon"></span>
                    <span class="spm-notification-message">${Utils.escapeHtml(message)}</span>
                    <button class="spm-notification-close" aria-label="閉じる">&times;</button>
                </div>
            `);

            notification.find('.spm-notification-close').on('click', () => {
                NotificationSystem.hide(notification);
            });

            $('.spm-notifications-container').append(notification);

            // アニメーション
            setTimeout(() => {
                notification.addClass('show');
            }, 10);

            // 自動非表示
            if (duration > 0) {
                setTimeout(() => {
                    NotificationSystem.hide(notification);
                }, duration);
            }

            return notification;
        },

        hide: (notification) => {
            notification.removeClass('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        },

        success: (message, duration) => {
            return NotificationSystem.show(message, 'success', duration);
        },

        error: (message, duration) => {
            return NotificationSystem.show(message, 'error', duration);
        },

        warning: (message, duration) => {
            return NotificationSystem.show(message, 'warning', duration);
        },

        info: (message, duration) => {
            return NotificationSystem.show(message, 'info', duration);
        }
    };

    // 初期化処理
    $(document).ready(function() {
        // ブラウザサポートチェック
        if (!BrowserSupport.checkLocalStorage()) {
            BrowserSupport.showUnsupportedBrowser();
        }

        // エラーハンドリング初期化
        ErrorHandler.init();

        // アクセシビリティ初期化
        Accessibility.init();

        // 通知コンテナの作成
        if (!$('.spm-notifications-container').length) {
            $('body').append('<div class="spm-notifications-container"></div>');
        }

        // パフォーマンス測定開始
        Performance.mark('spm-script-loaded');
    });

    // CSS動的追加
    const addDynamicStyles = () => {
        const styles = `
            <style id="spm-dynamic-styles">
                .spm-notifications-container {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    pointer-events: none;
                }
                
                .spm-notification {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    min-width: 300px;
                    margin-bottom: 10px;
                    padding: 12px 16px;
                    border-radius: 6px;
                    color: white;
                    font-weight: 500;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                    pointer-events: all;
                    transform: translateX(100%);
                    opacity: 0;
                    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                }
                
                .spm-notification.show {
                    transform: translateX(0);
                    opacity: 1;
                }
                
                .spm-notification-success {
                    background: linear-gradient(135deg, #4CAF50, #45a049);
                }
                
                .spm-notification-error {
                    background: linear-gradient(135deg, #f44336, #d32f2f);
                }
                
                .spm-notification-warning {
                    background: linear-gradient(135deg, #ff9800, #f57c00);
                }
                
                .spm-notification-info {
                    background: linear-gradient(135deg, #2196f3, #1976d2);
                }
                
                .spm-notification-icon::before {
                    font-family: 'dashicons';
                    font-size: 16px;
                }
                
                .spm-notification-success .spm-notification-icon::before {
                    content: '\\f147'; /* checkmark */
                }
                
                .spm-notification-error .spm-notification-icon::before {
                    content: '\\f335'; /* warning */
                }
                
                .spm-notification-warning .spm-notification-icon::before {
                    content: '\\f534'; /* info */
                }
                
                .spm-notification-info .spm-notification-icon::before {
                    content: '\\f534'; /* info */
                }
                
                .spm-notification-message {
                    flex: 1;
                    font-size: 14px;
                    line-height: 1.4;
                }
                
                .spm-notification-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 18px;
                    cursor: pointer;
                    padding: 0;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: background-color 0.2s ease;
                }
                
                .spm-notification-close:hover {
                    background-color: rgba(255,255,255,0.2);
                }
                
                .spm-browser-warning {
                    background: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 6px;
                    padding: 12px 16px;
                    margin-bottom: 20px;
                    color: #856404;
                    font-size: 14px;
                }
                
                .spm-high-contrast .spm-checkbox {
                    border-width: 3px !important;
                    border-color: #000 !important;
                }
                
                .spm-high-contrast .spm-progress-fill {
                    background: #000 !important;
                }
                
                .sr-only {
                    position: absolute !important;
                    width: 1px !important;
                    height: 1px !important;
                    padding: 0 !important;
                    margin: -1px !important;
                    overflow: hidden !important;
                    clip: rect(0,0,0,0) !important;
                    white-space: nowrap !important;
                    border: 0 !important;
                }
                
                @media (max-width: 768px) {
                    .spm-notifications-container {
                        top: 10px;
                        right: 10px;
                        left: 10px;
                    }
                    
                    .spm-notification {
                        min-width: auto;
                        width: 100%;
                    }
                }
            </style>
        `;
        
        if (!$('#spm-dynamic-styles').length) {
            $('head').append(styles);
        }
    };

    // スタイル追加
    addDynamicStyles();

    // グローバルAPIの拡張
    window.SPMUtils = Utils;
    window.SPMBrowserSupport = BrowserSupport;
    window.SPMPerformance = Performance;
    window.SPMNotification = NotificationSystem;
    window.SPMAccessibility = Accessibility;

})(jQuery);