/**
 * 学習進捗管理用のインタラクティブ機能（完全修正版）
 */
jQuery(document).ready(function($) {
    // グローバル変数を定義
    const STORAGE_PREFIX = 'spt_chapter_'; 
    const ajax_url = progress_tracker?.ajax_url || ajaxurl;
    const nonce = progress_tracker?.nonce || '';
    const firstCheckColor = progress_tracker?.first_check_color || '#e6f7e6';
    const secondCheckColor = progress_tracker?.second_check_color || '#ffebcc';
    
    // デバウンス用タイマー
    let saveTimer = null;
    let isProcessing = false;
    
    // 初期化確認
    if (typeof progress_tracker === 'undefined') {
        console.warn('progress_tracker オブジェクトが定義されていません。一部の機能が制限される可能性があります。');
    }
    
    /**
     * 章の展開/折りたたみ（イベント委譲で重複回避）
     */
    $(document).off('click.spt').on('click.spt', '.progress-tracker-shortcode .chapter-accordion-header', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (isProcessing) return false;
        isProcessing = true;
        
        const $chapterItem = $(this).closest('.chapter-accordion-item');
        const $content = $chapterItem.find('.chapter-accordion-content');
        const $toggle = $(this).find('.chapter-toggle-icon');
        const chapterId = $chapterItem.data('chapter');
        const subjectKey = $chapterItem.closest('.progress-subject').data('subject');
        
        if (chapterId && subjectKey) {
            const storageKey = STORAGE_PREFIX + subjectKey + '_' + chapterId;
            
            if ($content.is(':visible')) {
                $content.slideUp(200, function() {
                    $toggle.text('+');
                    localStorage.setItem(storageKey, 'collapsed');
                    isProcessing = false;
                });
            } else {
                $content.slideDown(200, function() {
                    $toggle.text('-');
                    localStorage.setItem(storageKey, 'expanded');
                    isProcessing = false;
                });
            }
        } else {
            isProcessing = false;
        }
        
        return false;
    });
    
    /**
     * タブ切り替え（イベント委譲で重複回避）
     */
    $(document).off('click.spt-tab').on('click.spt-tab', '.progress-tracker-shortcode .progress-tab', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (isProcessing) return false;
        isProcessing = true;
        
        const subjectKey = $(this).data('subject');
        const $container = $(this).closest('.progress-tracker-shortcode');
        
        if (!subjectKey) {
            isProcessing = false;
            return false;
        }
        
        // タブ切り替え
        $container.find('.progress-tab').removeClass('active');
        $(this).addClass('active');
        
        // 科目表示切り替え
        $container.find('.progress-subject').hide();
        $container.find(`.progress-subject[data-subject="${subjectKey}"]`).show();
        
        // アクティブタブの状態を保存
        try {
            localStorage.setItem('activeProgressTab', subjectKey);
        } catch (e) {
            console.warn('ローカルストレージへの保存に失敗しました:', e);
        }
        
        setTimeout(() => {
            isProcessing = false;
        }, 100);
        
        return false;
    });
    
    /**
     * チェックボックスの処理 - 理解レベル (レベル1)
     */
    $(document).off('change.spt-check1').on('change.spt-check1', '.item-check-level-1', function(e) {
        e.stopPropagation();
        
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
    $(document).off('change.spt-check2').on('change.spt-check2', '.item-check-level-2', function(e) {
        e.stopPropagation();
        
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
    $(document).off('click.spt-reset').on('click.spt-reset', '.reset-progress-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (!confirm('この科目の進捗をリセットしてもよろしいですか？この操作は元に戻せません。')) {
            return false;
        }
        
        const subject = $(this).data('subject');
        if (subject) {
            resetProgress(subject);
        }
        
        return false;
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
        
        // nonce確認
        if (!nonce) {
            console.error('セキュリティトークンが不足しています');
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
            timeout: 30000,
            beforeSend: function() {
                $container.css('opacity', 0.7);
            },
            success: function(response) {
                $container.css('opacity', 1);
                showSavingIndicator(false);
                
                if (response && response.success) {
                    const $subject = $(`.progress-subject[data-subject="${subject}"]`);
                    const $chapter = $subject.find(`.chapter-accordion-item[data-chapter="${chapter}"]`);
                    const $section = $chapter.find(`.section-item[data-section="${section}"]`);
                    
                    // 進捗バーと割合を更新
                    if (response.data && typeof response.data.percent !== 'undefined') {
                        $subject.find('.percent').text(`(${response.data.percent}%)`);
                        $subject.find('.progress-bar-fill').css('width', `${response.data.percent}%`);
                        
                        // 親コンテナのスタイルを更新
                        updateParentContainers(response.data, $chapter, $section);
                        
                        // 保存成功の視覚的フィードバック
                        showSaveSuccess($container);
                    }
                } else {
                    console.error('保存エラー:', response?.data || 'Unknown error');
                    const errorMessage = response?.data?.message || 'データの保存に失敗しました。';
                    alert('更新に失敗しました: ' + errorMessage);
                    // 失敗時は元の状態に戻す
                    revertCheckboxState($container);
                }
            },
            error: function(xhr, status, error) {
                $container.css('opacity', 1);
                showSavingIndicator(false);
                console.error('Ajax error:', status, error, xhr.responseText);
                
                // ネットワークエラーの場合は優しいメッセージ
                if (status === 'timeout') {
                    alert('保存がタイムアウトしました。インターネット接続を確認してから再度お試しください。');
                } else if (status === 'error' && xhr.status === 0) {
                    alert('インターネット接続に問題があります。接続を確認してから再度お試しください。');
                } else {
                    alert('通信エラーが発生しました。ページを再読み込みしてから再度お試しください。');
                }
                
                // 失敗時は元の状態に戻す
                revertCheckboxState($container);
            }
        });
    }
    
    /**
     * 保存中インジケーターの表示/非表示
     */
    function showSavingIndicator(show) {
        let $indicator = $('.saving-indicator');
        
        // インジケーターが存在しない場合は作成
        if ($indicator.length === 0) {
            $indicator = $('<div class="saving-indicator" style="position: fixed; top: 20px; right: 20px; background-color: #0073aa; color: white; padding: 10px 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); z-index: 9999; display: none;">保存中...</div>');
            $('body').append($indicator);
        }
        
        if (show) {
            $indicator.addClass('show').text('保存中...').fadeIn(200);
        } else {
            $indicator.removeClass('show').fadeOut(200);
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
        setTimeout(() => {
            if (confirm('エラーが発生しました。ページを再読み込みしますか？')) {
                location.reload();
            }
        }, 100);
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
        if (!nonce) {
            alert('セキュリティエラーが発生しました。ページを再読み込みしてください。');
            return;
        }
        
        $.ajax({
            url: ajax_url,
            type: 'POST',
            data: {
                action: 'progress_tracker_reset_progress',
                subject: subject,
                nonce: nonce
            },
            timeout: 30000,
            beforeSend: function() {
                $(`.progress-subject[data-subject="${subject}"]`).css('opacity', 0.7);
            },
            success: function(response) {
                const $subject = $(`.progress-subject[data-subject="${subject}"]`);
                $subject.css('opacity', 1);
                
                if (response && response.success) {
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
                    alert('リセットに失敗しました: ' + (response?.data?.message || '不明なエラー'));
                }
            },
            error: function(xhr, status, error) {
                $(`.progress-subject[data-subject="${subject}"]`).css('opacity', 1);
                console.error('Reset error:', status, error);
                
                if (status === 'timeout') {
                    alert('リセット処理がタイムアウトしました。');
                } else {
                    alert('通信エラーが発生しました。');
                }
            }
        });
    }
    
    /**
     * 章の状態復元関数
     */
    function restoreChapterStates() {
        // アクティブタブの復元
        try {
            const activeTab = localStorage.getItem('activeProgressTab');
            
            if (activeTab && $(`.progress-tab[data-subject="${activeTab}"]`).length) {
                $('.progress-tab').removeClass('active');
                $(`.progress-tab[data-subject="${activeTab}"]`).addClass('active');
                $('.progress-subject').hide();
                $(`.progress-subject[data-subject="${activeTab}"]`).show();
            } else if ($('.progress-tab').length > 0) {
                $('.progress-tab').first().addClass('active');
                $('.progress-subject').first().show();
            }
        } catch (e) {
            console.warn('タブ状態の復元に失敗しました:', e);
            // フォールバック
            $('.progress-tab').first().addClass('active');
            $('.progress-subject').first().show();
        }
        
        // 章の折りたたみ状態の復元
        setTimeout(function() {
            $('.chapter-accordion-item').each(function() {
                const $chapter = $(this);
                const chapterId = $chapter.data('chapter');
                const subjectKey = $chapter.closest('.progress-subject').data('subject');
                
                if (chapterId && subjectKey) {
                    try {
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
                    } catch (e) {
                        console.warn('章状態の復元に失敗しました:', e);
                    }
                }
            });
        }, 100);
    }
    
    /**
     * 接続状態の確認
     */
    function checkConnection() {
        if (typeof navigator !== 'undefined' && !navigator.onLine) {
            alert('インターネット接続が必要です。接続を確認してください。');
            return false;
        }
        return true;
    }
    
    // オンライン/オフライン状態の監視
    if (typeof window !== 'undefined') {
        $(window).off('online.spt offline.spt');
        
        $(window).on('online.spt', function() {
            console.log('オンラインになりました');
        });
        
        $(window).on('offline.spt', function() {
            console.log('オフラインになりました');
            alert('インターネット接続が切断されました。変更は保存されない可能性があります。');
        });
    }
    
    // 初期化
    restoreChapterStates();
    
    // ページ離脱時の警告（未保存の変更がある場合）
    let hasUnsavedChanges = false;
    
    $(document).off('change.spt-unsaved').on('change.spt-unsaved', 'input[type="checkbox"]', function() {
        hasUnsavedChanges = true;
        setTimeout(function() {
            hasUnsavedChanges = false;
        }, 1000); // 1秒後にフラグをリセット
    });
    
    $(window).off('beforeunload.spt').on('beforeunload.spt', function(e) {
        if (hasUnsavedChanges) {
            const message = '保存されていない変更があります。このページを離れますか？';
            e.returnValue = message;
            return message;
        }
    });
    
    // デバッグ用コンソール出力
    console.log('学習進捗管理システム（修正版）が初期化されました');
});