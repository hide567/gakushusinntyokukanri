/**
 * 学習進捗管理プラグイン - フロントエンドスクリプト（修正版）
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
            this.isLoading = false;
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
                const subjectKey = $(e.currentTarget).data('subject');
                this.switchSubject(subjectKey);
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

            // キーボードショートカット
            $(document).on('keydown', (e) => {
                // Ctrl+S で保存
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    this.saveProgress();
                }

                // Esc で全ての章を閉じる
                if (e.key === 'Escape') {
                    this.collapseAllChapters();
                }
            });
        }

        // 初期データの読み込み
        loadInitialData() {
            if (this.isLoading) return;
            
            this.isLoading = true;
            this.showLoading();
            
            $.ajax({
                url: spm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_subjects',
                    nonce: smp_ajax.nonce
                },
                success: (response) => {
                    try {
                        if (response.success && response.data) {
                            this.processSubjectData(response.data);
                        } else {
                            this.showError('データの読み込みに失敗しました。');
                            console.error('Load subjects error:', response);
                        }
                    } catch (error) {
                        this.showError('データの処理中にエラーが発生しました。');
                        console.error('Data processing error:', error);
                    }
                    this.hideLoading();
                    this.isLoading = false;
                },
                error: (xhr, status, error) => {
                    this.showError('サーバーとの通信に失敗しました。');
                    console.error('Ajax error:', { xhr, status, error });
                    this.hideLoading();
                    this.isLoading = false;
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
            
            this.subjects.forEach((subject, index) => {
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
            if (this.currentSubject === subjectKey || this.isLoading) return;

            $('.spm-subject-tab').removeClass('active');
            $(`.spm-subject-tab[data-subject="${subjectKey}"]`).addClass('active');
            
            this.currentSubject = subjectKey;
            this.loadSubjectProgress(subjectKey);
        }

        // 科目の進捗データ読み込み
        loadSubjectProgress(subjectKey) {
            if (this.isLoading) return;
            
            this.isLoading = true;
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
                    try {
                        if (response.success && response.data) {
                            this.renderSubjectContent(response.data);
                        } else {
                            this.showError('進捗データの読み込みに失敗しました。');
                            console.error('Load progress error:', response);
                        }
                    } catch (error) {
                        this.showError('データの処理中にエラーが発生しました。');
                        console.error('Progress processing error:', error);
                    }
                    this.hideLoading();
                    this.isLoading = false;
                },
                error: (xhr, status, error) => {
                    this.showError('サーバーとの通信に失敗しました。');
                    console.error('Ajax error:', { xhr, status, error });
                    this.hideLoading();
                    this.isLoading = false;
                }
            });
        }

        // 科目コンテンツの描画
        renderSubjectContent(data) {
            const container = $('.spm-progress-content');
            if (container.length === 0) return;

            const subject = this.subjects.find(s => s.subject_key === this.currentSubject);
            if (!subject) return;

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
                    
                    ${this.renderProgressSummary(data.progress, subject.progress_color)}
                    
                    <div class="spm-chapters-list">
                        ${this.renderChapters(data.structure, data.progress, subject)}
                    </div>
                </div>
            `;

            container.html(html);
            this.updateProgressBars();
            this.applySubjectColors(subject.progress_color);
        }

        // 科目色の動的適用
        applySubjectColors(color) {
            // CSS変数の更新
            document.documentElement.style.setProperty('--current-subject-color', color);
            
            // チェックボックスの色更新
            $('.spm-checkbox').each(function() {
                $(this).css('--subject-color', color);
            });
            
            // プログレスバーの色更新
            $('.spm-chapter-progress-fill').css('background', color);
            $('.spm-progress-text').css('color', color);
        }

        // 進捗サマリーの描画
        renderProgressSummary(progressData, subjectColor) {
            const stats = this.calculateProgressStats(progressData);
            
            return `
                <div class="spm-progress-summary" style="background: linear-gradient(135deg, ${subjectColor} 0%, ${this.darkenColor(subjectColor, 20)} 100%)">
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

        // 色を暗くする関数
        darkenColor(color, percent) {
            const num = parseInt(color.replace("#", ""), 16);
            const amt = Math.round(2.55 * percent);
            const R = (num >> 16) - amt;
            const G = (num >> 8 & 0x00FF) - amt;
            const B = (num & 0x0000FF) - amt;
            return "#" + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
                (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
                (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
        }

        // 章の描画
        renderChapters(structure, progressData, subject) {
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
                                         style="width: ${chapterProgress}%; background: ${subject.progress_color}"></div>
                                </div>
                                <span class="spm-progress-text" style="color: ${subject.progress_color}">${chapterProgress}%</span>
                            </div>
                            <span class="spm-expand-icon">▼</span>
                        </div>
                        <div class="spm-chapter-content">
                            ${this.renderSections(chapter, progressData, subject)}
                        </div>
                    </div>
                `;
            }).join('');
        }

        // 節の描画
        renderSections(chapter, progressData, subject) {
            if (!chapter.sections || chapter.sections.length === 0) {
                return '<div class="spm-empty">この章には節が設定されていません。</div>';
            }

            return `
                <div class="spm-sections-grid">
                    ${chapter.sections.map(section => `
                        <div class="spm-section-card">
                            <div class="spm-section-header" style="border-left-color: ${subject.progress_color}">
                                第${section.section_number}節 ${section.section_title}
                            </div>
                            <div class="spm-items-grid">
                                ${this.renderItems(chapter.chapter_number, section, progressData, subject)}
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        // 項の描画
        renderItems(chapterNum, section, progressData, subject) {
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
                                           data-subject="${subject.subject_key}"
                                           ${progress.understanding ? 'checked' : ''}
                                           style="--subject-color: #2196F3">
                                    <span class="spm-checkbox-label">理解</span>
                                </label>
                                <label class="spm-checkbox-wrapper spm-mastery">
                                    <input type="checkbox" class="spm-checkbox"
                                           data-type="mastery" 
                                           data-chapter="${chapterNum}"
                                           data-section="${section.section_number}"
                                           data-item="${item.item_number}"
                                           data-subject="${subject.subject_key}"
                                           ${progress.mastery ? 'checked' : ''}
                                           style="--subject-color: #4CAF50">
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
            this.scheduleAutoSave();
        }

        // 進捗バーの更新
        updateProgressBars() {
            // 章レベルの進捗バー更新
            $('.spm-chapter-item').each((index, element) => {
                const chapterElement = $(element);
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
            if (this.isLoading) return;
            
            const progressData = this.collectProgressData();
            
            this.showSaveIndicator('saving');

            $.ajax({
                url: spm_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'save_progress',
                    progress_data: JSON.stringify(progressData),
                    nonce: spm_ajax.nonce
                },
                success: (response) => {
                    try {
                        if (response.success) {
                            this.showSaveIndicator('success');
                            this.markAsSaved();
                        } else {
                            this.showSaveIndicator('error');
                            console.error('Save error:', response);
                        }
                    } catch (error) {
                        this.showSaveIndicator('error');
                        console.error('Save response error:', error);
                    }
                },
                error: (xhr, status, error) => {
                    this.showSaveIndicator('error');
                    console.error('Save ajax error:', { xhr, status, error });
                }
            });
        }

        // 進捗データの収集
        collectProgressData() {
            const progressData = {};

            $('.spm-checkbox').each((index, element) => {
                const checkbox = $(element);
                const subject = checkbox.data('subject');
                const chapter = checkbox.data('chapter');
                const section = checkbox.data('section');
                const item = checkbox.data('item');
                const type = checkbox.data('type');
                const checked = checkbox.is(':checked');

                const key = `${subject}-${chapter}-${section}-${item}`;
                
                if (!progressData[key]) {
                    progressData[key] = {
                        subject_key: subject,
                        chapter: chapter,
                        section: section,
                        item: item,
                        understanding: 0,
                        mastery: 0
                    };
                }

                progressData[key][type] = checked ? 1 : 0;
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
            // モバイル表示の調整
            this.handleResize();
            
            // タッチイベント対応
            this.initTouchEvents();
        }

        // ウィンドウリサイズ処理
        handleResize() {
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                $('.spm-progress-container').addClass('mobile-layout');
            } else {
                $('.spm-progress-container').removeClass('mobile-layout');
            }
        }

        // タッチイベント対応
        initTouchEvents() {
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

                if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                    if (diffX > 0) {
                        this.switchToNextSubject();
                    } else {
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

        // 統計情報の表示
        showStatistics() {
            const progressData = this.collectProgressData();
            
            let totalItems = 0;
            let understoodItems = 0;
            let masteredItems = 0;
            
            for (const key in progressData) {
                const item = progressData[key];
                if (item.subject_key === this.currentSubject) {
                    totalItems++;
                    if (item.understanding) understoodItems++;
                    if (item.mastery) masteredItems++;
                }
            }
            
            const completionRate = totalItems > 0 ? Math.round((masteredItems / totalItems) * 100) : 0;

            const statsHTML = `
                <div class="spm-statistics-modal">
                    <div class="spm-statistics-content">
                        <h3>学習統計 - ${this.subjects.find(s => s.subject_key === this.currentSubject)?.subject_name || ''}</h3>
                        <div class="spm-stats-grid">
                            <div class="spm-stat-item">
                                <div class="spm-stat-number">${totalItems}</div>
                                <div class="spm-stat-label">総項目数</div>
                            </div>
                            <div class="spm-stat-item">
                                <div class="spm-stat-number">${understoodItems}</div>
                                <div class="spm-stat-label">理解済み</div>
                            </div>
                            <div class="spm-stat-item">
                                <div class="spm-stat-number">${masteredItems}</div>
                                <div class="spm-stat-label">習得済み</div>
                            </div>
                            <div class="spm-stat-item">
                                <div class="spm-stat-number">${completionRate}%</div>
                                <div class="spm-stat-label">完了率</div>
                            </div>
                        </div>
                        <div class="spm-modal-actions">
                            <button class="button button-primary" onclick="$('.spm-statistics-modal').remove()">閉じる</button>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(statsHTML);
            
            // モーダルのクリックで閉じる
            $('.spm-statistics-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).remove();
                }
            });
        }
    }

    // プラグインの初期化
    $(document).ready(function() {
        // spm_ajaxオブジェクトが存在する場合のみ初期化
        if (typeof spm_ajax !== 'undefined' && spm_ajax.ajax_url) {
            window.spmManager = new StudyProgressManager();

            // グローバル関数の定義（外部から呼び出し可能）
            window.SPM = {
                saveProgress: () => window.spmManager.saveProgress(),
                exportProgress: () => window.spmManager.exportProgress(),
                showStatistics: () => window.spmManager.showStatistics(),
                selectAll: () => window.spmManager.selectAllItems(),
                collapseAll: () => window.spmManager.collapseAllChapters()
            };
        } else {
            console.warn('SPM: Ajax configuration not found');
        }
    });

    // エラーハンドリング
    window.addEventListener('error', function(event) {
        console.error('SPM Global Error:', event.error);
    });

    // CSS動的追加
    const addDynamicStyles = () => {
        if (document.getElementById('spm-dynamic-styles')) return;
        
        const styles = `
            <style id="spm-dynamic-styles">
                .spm-statistics-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    animation: fadeIn 0.3s ease-out;
                }
                
                .spm-statistics-content {
                    background: white;
                    padding: 30px;
                    border-radius: 12px;
                    max-width: 600px;
                    width: 90%;
                    max-height: 80vh;
                    overflow-y: auto;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                    animation: slideInUp 0.3s ease-out;
                }
                
                .spm-statistics-content h3 {
                    margin: 0 0 20px 0;
                    color: #1d2327;
                    text-align: center;
                    border-bottom: 2px solid #2271b1;
                    padding-bottom: 10px;
                }
                
                .spm-statistics-content .spm-stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                    gap: 20px;
                    margin-bottom: 30px;
                }
                
                .spm-statistics-content .spm-stat-item {
                    text-align: center;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 8px;
                    border: 1px solid #e0e0e0;
                }
                
                .spm-statistics-content .spm-stat-number {
                    font-size: 2em;
                    font-weight: 700;
                    color: #2271b1;
                    margin-bottom: 5px;
                }
                
                .spm-statistics-content .spm-stat-label {
                    font-size: 0.9em;
                    color: #666;
                    font-weight: 500;
                }
                
                .spm-modal-actions {
                    text-align: center;
                    padding-top: 20px;
                    border-top: 1px solid #e0e0e0;
                }
                
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                
                @keyframes slideInUp {
                    from {
                        opacity: 0;
                        transform: translateY(50px) scale(0.9);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
                
                .mobile-layout .spm-subject-tabs {
                    flex-direction: column;
                    gap: 5px;
                }
                
                .mobile-layout .spm-subject-tab {
                    border-radius: 6px;
                    margin-bottom: 5px;
                }
                
                .mobile-layout .spm-chapter-info {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }
                
                .mobile-layout .spm-chapter-progress-bar {
                    width: 100%;
                    max-width: none;
                    margin: 0;
                }
                
                .mobile-layout .spm-progress-controls {
                    width: 100%;
                }
                
                .mobile-layout .spm-item-row {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 10px;
                }
                
                .mobile-layout .spm-checkbox-group {
                    width: 100%;
                    justify-content: flex-start;
                }
                
                @media (max-width: 480px) {
                    .spm-statistics-content {
                        padding: 20px;
                        margin: 10px;
                        width: calc(100% - 20px);
                    }
                    
                    .spm-statistics-content .spm-stats-grid {
                        grid-template-columns: repeat(2, 1fr);
                        gap: 15px;
                    }
                }
            </style>
        `;
        
        $('head').append(styles);
    };

    // 初期化時にスタイル追加
    $(document).ready(addDynamicStyles);

})(jQuery);