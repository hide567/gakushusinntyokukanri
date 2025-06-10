/**
 * 学習進捗管理用のインタラクティブ機能（修正版）
 */
jQuery(document).ready(function($) {
    // グローバル変数を定義
    const STORAGE_PREFIX = 'spt_chapter_'; 
    const ajax_url = progress_tracker.ajax_url;
    const nonce = progress_tracker.nonce;
    const firstCheckColor = progress_tracker.first_check_color || '#e6f7e6';
    const secondCheckColor = progress_tracker.second_check_color || '#ffebcc';
    
    // デバウンス用タイマー
    let saveTimer = null;
    
    /**
     * 章の展開/折りたたみ
     */
    $(document).on('click', '.progress-tracker-shortcode .chapter-accordion-header', function(e) {
        e.preventDefault();
        
        const $chapterItem = $(this).closest('.chapter-accordion-item');
        const $content = $chapterItem.find('.chapter-accordion-content');
        const $toggle = $(this).find('.chapter-toggle-icon');
        const chapterId = $chapterItem.data('chapter');
        const subjectKey = $chapterItem.closest('.progress-subject').data('subject');
        const storageKey = STORAGE_PREFIX + subjectKey + '_' + chapterId;
        
        if ($content.is(':visible')) {
            $content.slideUp(200);
            $toggle.text('+');
            localStorage.setItem(storageKey, 'collapsed');
        } else {
            $content.slideDown(200);
            $toggle.text('-');
            localStorage.setItem(storageKey, 'expanded');
        }
    });
    
    /**
     * タブ切り替え
     */
    $(document).on('click', '.progress-tracker-shortcode .progress-tab', function() {
        const subjectKey = $(this).data('subject');
        
        // タブ切り替え
        $('.progress-tracker-shortcode .progress-tab').removeClass('active');
        $(this).addClass('active');
        
        // 科目表示切り替え
        $('.progress-tracker-shortcode .progress-subject').hide();
        $(`.progress-tracker-shortcode .progress-subject[data-subject="${subjectKey}"]`).show();
        
        // アクティブタブの状態を保存
        localStorage.setItem('activeProgressTab', subjectKey);
    });
    
    /**
     * チェックボックスの処理 - 理解レベル (レベル1)
     */
    $(document).on('change', '.item-check-level-1', function() {
        const $this = $(this);
        const isChecked = $this.prop('checked');
        const $container = $this.closest('.item-row');
        const $level2Check = $container.find('.item-check-level-2');
        
        // レベル1のチェックを外した場合、レベル2も自動的に外す
        if (!isChecked) {
            $level2Check.prop('checked', false);
        }
        
        updateStatus($container, isChecked ? 1 : 0);
    });
    
    /**
     * チェックボックスの処理 - 習得レベル (レベル2)
     */
    $(document).on('change', '.item-check-level-2', function() {
        const $this = $(this);
        const isChecked = $this.prop('checked');
        const $container = $this.closest('.item-row');
        const $level1Check = $container.find('.item-check-level-1');
        
        // レベル2をチェックした場合、レベル1も自動的にチェック
        if (isChecked) {
            $level1Check.prop('checked', true);
        }
        
        updateStatus($container, isChecked ? 2 : ($level1Check.prop('checked') ? 1 : 0));
    });
    
    /**
     * リセットボタンの処理
     */
    $(document).on('click', '.reset-progress-btn', function() {
        if (confirm('この科目の進捗をリセットしてもよろしいですか？この操作は元に戻せません。')) {
            const subject = $(this).data('subject');
            resetProgress(subject);
        }
    });
    
    /**
     * 状態更新関数（デバウンス機能付き）
     */
    function updateStatus($container, level) {
        // UIを即座に更新
        updateContainerStyle($container, level);
        
        // デバウンスタイマーをクリア
        if (saveTimer) {
            clearTimeout(saveTimer);
        }
        
        // 300ms後に保存を実行
        saveTimer = setTimeout(function() {
            saveStatus($container, level);
        }, 300);
    }
    
    /**
     * 実際の保存処理
     */
    function saveStatus($container, level) {
        const subject = $container.data('subject');
        const chapter = $container.data('chapter');
        const section = $container.data('section');
        const item = $container.data('item');
        
        // データ検証
        if (!subject || !chapter || !section || !item) {
            console.error('保存に必要なデータが不足しています', {subject, chapter, section, item});
            return;
        }
        
        // Ajaxデータの準備
        const data = {
            action: 'progress_tracker_toggle_item_completion',
            subject: subject,
            chapter: chapter,
            section: section,
            item: item,
            check_level: level,
            completed: level > 0,
            nonce: nonce
        };
        
        // 保存中の表示
        showSavingIndicator(true);
        
        // Ajaxで更新
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: data,
            beforeSend: function() {
                $container.css('opacity', 0.7);
            },
            success: function(response) {
                $container.css('opacity', 1);
                showSavingIndicator(false);
                
                if (response.success) {
                    const $subject = $(`.progress-subject[data-subject="${subject}"]`);
                    const $chapter = $subject.find(`.chapter-accordion-item[data-chapter="${chapter}"]`);
                    const $section = $chapter.find(`.section-item[data-section="${section}"]`);
                    
                    // 進捗バーと割合を更新
                    $subject.find('.percent').text(`(${response.data.percent}%)`);
                    $subject.find('.progress-bar-fill').css('width', `${response.data.percent}%`);
                    
                    // 親コンテナのスタイルを更新
                    updateParentContainers(response.data, $chapter, $section);
                    
                    // 保存成功の視覚的フィードバック
                    showSaveSuccess($container);
                } else {
                    console.error('保存エラー:', response.data);
                    alert('更新に失敗しました: ' + (response.data.message || 'Unknown error'));
                    // 失敗時は元の状態に戻す
                    revertCheckboxState($container);
                }
            },
            error: function(xhr, status, error) {
                $container.css('opacity', 1);
                showSavingIndicator(false);
                console.error('Ajax error:', status, error, xhr.responseText);
                alert('通信エラーが発生しました。再度お試しください。');
                // 失敗時は元の状態に戻す
                revertCheckboxState($container);
            }
        });
    }
    
    /**
     * 保存中インジケーターの表示/非表示
     */
    function showSavingIndicator(show) {
        const $indicator = $('.saving-indicator');
        if (show) {
            $indicator.addClass('show').text('保存中...');
        } else {
            $indicator.removeClass('show');
        }
    }
    
    /**
     * 保存成功の視覚的フィードバック
     */
    function showSaveSuccess($container) {
        const originalBg = $container.css('background-color');
        $container.css('background-color', '#90EE90');
        
        setTimeout(function() {
            $container.css('background-color', originalBg);
        }, 500);
    }
    
    /**
     * チェックボックスの状態を元に戻す
     */
    function revertCheckboxState($container) {
        // 簡略化のため、ページをリロード
        location.reload();
    }
    
    /**
     * 親コンテナの更新
     */
    function updateParentContainers(data, $chapter, $section) {
        // 章の状態を更新
        $chapter.removeClass('completed mastered');
        if (data.chapter_mastered) {
            $chapter.addClass('completed mastered');
            updateContainerStyle($chapter.find('.chapter-accordion-header'), 2);
        } else if (data.chapter_completed) {
            $chapter.addClass('completed');
            updateContainerStyle($chapter.find('.chapter-accordion-header'), 1);
        } else {
            $chapter.find('.chapter-accordion-header').css('background-color', '');
        }
        
        // 節の状態を更新
        $section.removeClass('completed mastered');
        if (data.section_mastered) {
            $section.addClass('completed mastered');
            updateContainerStyle($section.find('.section-header'), 2);
        } else if (data.section_completed) {
            $section.addClass('completed');
            updateContainerStyle($section.find('.section-header'), 1);
        } else {
            $section.find('.section-header').css('background-color', '');
        }
    }
    
    /**
     * コンテナスタイル更新
     */
    function updateContainerStyle($container, level) {
        $container.removeClass('checked mastered');
        $container.css('background-color', '');
        
        if (level >= 2) {
            $container.addClass('mastered');
            $container.css('background-color', secondCheckColor);
        } else if (level >= 1) {
            $container.addClass('checked');
            $container.css('background-color', firstCheckColor);
        }
    }
    
    /**
     * 進捗リセット
     */
    function resetProgress(subject) {
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'progress_tracker_reset_progress',
                subject: subject,
                nonce: nonce
            },
            beforeSend: function() {
                $(`.progress-subject[data-subject="${subject}"]`).css('opacity', 0.7);
            },
            success: function(response) {
                const $subject = $(`.progress-subject[data-subject="${subject}"]`);
                $subject.css('opacity', 1);
                
                if (response.success) {
                    // 進捗バーと割合をリセット
                    $subject.find('.percent').text('(0%)');
                    $subject.find('.progress-bar-fill').css('width', '0%');
                    
                    // チェックボックスをリセット
                    $subject.find('input[type="checkbox"]').prop('checked', false);
                    
                    // 背景色をリセット
                    $subject.find('.item-row, .chapter-accordion-header, .section-header').css('background-color', '');
                    $subject.find('.chapter-accordion-item, .section-item, .item-row').removeClass('completed mastered checked');
                    
                    alert('進捗をリセットしました。');
                } else {
                    alert('リセットに失敗しました。');
                }
            },
            error: function() {
                $(`.progress-subject[data-subject="${subject}"]`).css('opacity', 1);
                alert('通信エラーが発生しました。');
            }
        });
    }
    
    /**
     * 章の状態復元関数
     */
    function restoreChapterStates() {
        // アクティブタブの復元
        const activeTab = localStorage.getItem('activeProgressTab');
        
        if (activeTab && $(`.progress-tab[data-subject="${activeTab}"]`).length) {
            $(`.progress-tab[data-subject="${activeTab}"]`).trigger('click');
        } else if ($('.progress-tab').length > 0) {
            $('.progress-tab').first().trigger('click');
        }
        
        // 章の折りたたみ状態の復元
        setTimeout(function() {
            $('.chapter-accordion-item').each(function() {
                const $chapter = $(this);
                const chapterId = $chapter.data('chapter');
                const subjectKey = $chapter.closest('.progress-subject').data('subject');
                
                if (chapterId && subjectKey) {
                    const storageKey = STORAGE_PREFIX + subjectKey + '_' + chapterId;
                    const savedState = localStorage.getItem(storageKey);
                    
                    const $content = $chapter.find('.chapter-accordion-content');
                    const $icon = $chapter.find('.chapter-toggle-icon');
                    
                    if (savedState === 'expanded') {
                        $content.show();
                        $icon.text('-');
                    } else if (savedState === 'collapsed') {
                        $content.hide();
                        $icon.text('+');
                    }
                    // savedStateがない場合はデフォルト状態を維持
                }
            });
        }, 100);
    }
    
    /**
     * 接続状態の確認
     */
    function checkConnection() {
        if (!navigator.onLine) {
            alert('インターネット接続が必要です。接続を確認してください。');
            return false;
        }
        return true;
    }
    
    // オンライン/オフライン状態の監視
    $(window).on('online', function() {
        console.log('オンラインになりました');
    });
    
    $(window).on('offline', function() {
        console.log('オフラインになりました');
        alert('インターネット接続が切断されました。変更は保存されない可能性があります。');
    });
    
    // 初期化
    restoreChapterStates();
    
    // ページ離脱時の警告（未保存の変更がある場合）
    let hasUnsavedChanges = false;
    
    $(document).on('change', 'input[type="checkbox"]', function() {
        hasUnsavedChanges = true;
        setTimeout(function() {
            hasUnsavedChanges = false;
        }, 1000); // 1秒後にフラグをリセット
    });
    
    $(window).on('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            const message = '保存されていない変更があります。このページを離れますか？';
            e.returnValue = message;
            return message;
        }
    });
    
    // デバッグ用コンソール出力
    if (typeof progress_tracker === 'undefined') {
        console.error('progress_tracker オブジェクトが定義されていません。プラグインが正しく読み込まれているか確認してください。');
    } else {
        console.log('学習進捗管理システムが初期化されました');
    }
});