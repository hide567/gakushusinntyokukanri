/**
 * 学習進捗管理 - 柔軟構造対応フロントエンドJavaScript
 * assets/js/frontend-flexible.js
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('柔軟構造学習進捗管理システム開始');
    
    // 基本設定
    const ajaxUrl = (typeof spt_data !== 'undefined' && spt_data.ajax_url) || '/wp-admin/admin-ajax.php';
    const nonce = (typeof spt_data !== 'undefined' && spt_data.nonce) || '';
    
    // 1. 初期状態を強制的に閉じる
    function forceCloseAll() {
        console.log('全要素を強制的に閉じます');
        
        $('.spt-subject-flexible').each(function() {
            const $subject = $(this);
            const $content = $subject.find('.spt-subject-content');
            const $toggle = $subject.find('.spt-subject-toggle');
            
            $content.hide();
            $toggle.text('▶');
            $subject.removeClass('expanded');
        });
        
        $('.spt-chapter').each(function() {
            const $chapter = $(this);
            const $content = $chapter.find('.spt-chapter-content');
            const $toggle = $chapter.find('.spt-chapter-toggle');
            
            $content.hide();
            $toggle.text('+');
            $chapter.removeClass('expanded');
        });
        
        console.log('全要素を閉じました');
    }
    
    // 2. 科目のクリックイベント
    $(document).on('click', '.spt-subject-header', function(e) {
        e.preventDefault();
        
        const $subject = $(this).closest('.spt-subject-flexible');
        const $content = $subject.find('.spt-subject-content');
        const $toggle = $subject.find('.spt-subject-toggle');
        
        console.log('科目クリック:', $subject.data('subject'));
        
        if ($content.is(':visible')) {
            // 閉じる
            $content.slideUp(200);
            $toggle.text('▶');
            $subject.removeClass('expanded');
            console.log('科目を閉じました');
        } else {
            // 開く
            $content.slideDown(200);
            $toggle.text('▼');
            $subject.addClass('expanded');
            console.log('科目を開きました');
        }
    });
    
    // 3. 章のクリックイベント
    $(document).on('click', '.spt-chapter-header', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $chapter = $(this).closest('.spt-chapter');
        const $content = $chapter.find('.spt-chapter-content');
        const $toggle = $chapter.find('.spt-chapter-toggle');
        
        console.log('章クリック:', $chapter.data('chapter'));
        
        if ($content.is(':visible')) {
            // 閉じる
            $content.slideUp(200);
            $toggle.text('+');
            $chapter.removeClass('expanded');
            console.log('章を閉じました');
        } else {
            // 開く
            $content.slideDown(200);
            $toggle.text('-');
            $chapter.addClass('expanded');
            console.log('章を開きました');
        }
    });
    
    // 4. チェックボックスイベント
    $(document).on('change', '.spt-check-understand', function() {
        const $item = $(this).closest('.spt-item');
        const $masterCheck = $item.find('.spt-check-master');
        const isChecked = $(this).prop('checked');
        
        if (!isChecked) {
            $masterCheck.prop('checked', false);
        }
        
        updateItemStatus($item);
        saveProgress($item);
    });
    
    $(document).on('change', '.spt-check-master', function() {
        const $item = $(this).closest('.spt-item');
        const $understandCheck = $item.find('.spt-check-understand');
        const isChecked = $(this).prop('checked');
        
        if (isChecked) {
            $understandCheck.prop('checked', true);
        }
        
        updateItemStatus($item);
        saveProgress($item);
    });
    
    // 5. 項目状態更新
    function updateItemStatus($item) {
        const $understandCheck = $item.find('.spt-check-understand');
        const $masterCheck = $item.find('.spt-check-master');
        
        $item.removeClass('understood mastered');
        
        if ($masterCheck.prop('checked')) {
            $item.addClass('understood mastered');
        } else if ($understandCheck.prop('checked')) {
            $item.addClass('understood');
        }
        
        updateParentStatus($item);
    }
    
    // 6. 上位要素の状態更新
    function updateParentStatus($item) {
        const $section = $item.closest('.spt-section');
        const $chapter = $item.closest('.spt-chapter');
        const $subject = $item.closest('.spt-subject-flexible');
        
        // 節の更新
        updateSectionStatus($section);
        // 章の更新
        updateChapterStatus($chapter);
        // 科目の更新
        updateSubjectStatus($subject);
    }
    
    function updateSectionStatus($section) {
        const $items = $section.find('.spt-item');
        const totalItems = $items.length;
        const understoodItems = $items.filter('.understood').length;
        const masteredItems = $items.filter('.mastered').length;
        
        $section.removeClass('completed mastered');
        
        if (masteredItems === totalItems && totalItems > 0) {
            $section.addClass('completed mastered');
        } else if (understoodItems === totalItems && totalItems > 0) {
            $section.addClass('completed');
        }
        
        const percent = totalItems > 0 ? Math.ceil((understoodItems / totalItems) * 100) : 0;
        $section.find('.spt-section-percent').text(percent + '%');
        
        // 節内進捗バー更新
        $section.find('.spt-progress-fill-section').css('width', percent + '%');
    }
    
    function updateChapterStatus($chapter) {
        const $items = $chapter.find('.spt-item');
        const totalItems = $items.length;
        const understoodItems = $items.filter('.understood').length;
        const masteredItems = $items.filter('.mastered').length;
        
        $chapter.removeClass('completed mastered');
        
        if (masteredItems === totalItems && totalItems > 0) {
            $chapter.addClass('completed mastered');
        } else if (understoodItems === totalItems && totalItems > 0) {
            $chapter.addClass('completed');
        }
        
        const percent = totalItems > 0 ? Math.ceil((understoodItems / totalItems) * 100) : 0;
        $chapter.find('.spt-chapter-percent').text(percent + '%');
        
        // 章内進捗バー更新
        $chapter.find('.spt-progress-fill-chapter').css('width', percent + '%');
    }
    
    function updateSubjectStatus($subject) {
        const $items = $subject.find('.spt-item');
        const totalItems = $items.length;
        const understoodItems = $items.filter('.understood').length;
        const percent = totalItems > 0 ? Math.ceil((understoodItems / totalItems) * 100) : 0;
        
        $subject.find('.spt-percent').text(percent + '%');
        
        // ヘッダー内進捗バー更新
        $subject.find('.spt-progress-fill-header').css('width', percent + '%');
    }
    
    // 7. 進捗保存
    function saveProgress($item) {
        if (!nonce) {
            console.error('nonceが設定されていません');
            return;
        }
        
        const subject = $item.data('subject');
        const chapter = $item.data('chapter');
        const section = $item.data('section');
        const item = $item.data('item');
        
        if (!subject || !chapter || !section || !item) {
            console.error('データ属性が不完全です:', {subject, chapter, section, item});
            return;
        }
        
        const $understandCheck = $item.find('.spt-check-understand');
        const $masterCheck = $item.find('.spt-check-master');
        
        let level = 0;
        if ($understandCheck.prop('checked')) level = 1;
        if ($masterCheck.prop('checked')) level = 2;
        
        console.log('保存開始:', { subject, chapter, section, item, level });
        
        // 項目を処理中状態にする
        $item.addClass('processing');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_toggle_progress',
                subject: subject,
                chapter: chapter,
                section: section,
                item: item,
                level: level,
                nonce: nonce
            },
            success: function(response) {
                console.log('保存成功:', response);
                if (response && response.success) {
                    showNotification('保存しました', 'success');
                } else {
                    console.error('保存エラー:', response);
                    showNotification('保存に失敗しました', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', status, error);
                showNotification('通信エラーが発生しました', 'error');
            },
            complete: function() {
                $item.removeClass('processing');
            }
        });
    }
    
    // 8. 進捗リセット
    $(document).on('click', '.spt-reset-btn', function(e) {
        e.preventDefault();
        
        const subject = $(this).data('subject');
        const $subject = $('.spt-subject-flexible[data-subject="' + subject + '"]');
        
        if (!confirm('この科目の進捗をリセットしますか？')) {
            return;
        }
        
        $(this).prop('disabled', true).text('リセット中...');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_reset_progress',
                subject: subject,
                nonce: nonce
            },
            success: function(response) {
                if (response && response.success) {
                    // 全チェックボックスをクリア
                    $subject.find('.spt-check-understand, .spt-check-master').prop('checked', false);
                    
                    // 全要素の状態をクリア
                    $subject.find('.spt-item, .spt-section, .spt-chapter').removeClass('understood mastered completed');
                    
                    // パーセント表示をリセット
                    $subject.find('.spt-percent').text('0%');
                    $subject.find('.spt-chapter-percent, .spt-section-percent').text('0%');
                    
                    // 進捗バーをリセット
                    $subject.find('.spt-progress-fill-header, .spt-progress-fill-chapter, .spt-progress-fill-section').css('width', '0%');
                    
                    showNotification('進捗をリセットしました', 'success');
                } else {
                    showNotification('リセットに失敗しました', 'error');
                }
            },
            error: function() {
                showNotification('通信エラーが発生しました', 'error');
            },
            complete: function() {
                $('.spt-reset-btn[data-subject="' + subject + '"]').prop('disabled', false).text('この科目をリセット');
            }
        });
    });
    
    // 9. 通知表示
    function showNotification(message, type) {
        $('.spt-notification').remove();
        
        const colors = {
            error: '#dc3232',
            warning: '#ffb900',
            success: '#46b450',
            info: '#0073aa'
        };
        
        const bgColor = colors[type] || colors.info;
        
        const $notification = $('<div class="spt-notification" style="' +
            'position: fixed; top: 20px; right: 20px; background: ' + bgColor + '; ' +
            'color: white; padding: 12px 20px; border-radius: 6px; ' +
            'box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 10000; ' +
            'max-width: 300px; font-size: 14px; opacity: 0;">' + message + '</div>');
        
        $('body').append($notification);
        
        // フェードイン
        $notification.animate({opacity: 1}, 200);
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // 10. 進捗バーのアニメーション効果
    function animateProgressBars() {
        $('.spt-progress-fill-header, .spt-progress-fill-chapter, .spt-progress-fill-section').each(function() {
            const $bar = $(this);
            const width = $bar.data('target-width') || $bar.css('width');
            
            if (width !== '0px' && width !== '0%') {
                $bar.css('width', '0%').animate({width: width}, 800, 'easeOutQuart');
            }
        });
    }
    
    // 11. スムーズスクロール
    function scrollToElement($element, offset = 100) {
        if ($element.length) {
            $('html, body').animate({
                scrollTop: $element.offset().top - offset
            }, 500);
        }
    }
    
    // 12. キーボードショートカット
    $(document).on('keydown', function(e) {
        // Ctrl + R で全体リセット（管理者のみ）
        if (e.ctrlKey && e.key === 'r' && $('.spt-reset-btn').length > 0) {
            e.preventDefault();
            if (confirm('全科目の進捗をリセットしますか？')) {
                $('.spt-reset-btn').first().click();
            }
        }
        
        // Escapeで全て閉じる
        if (e.key === 'Escape') {
            forceCloseAll();
        }
    });
    
    // 13. 自動保存状態の視覚化
    function addSaveIndicator() {
        const $indicator = $('<div id="save-indicator" style="' +
            'position: fixed; top: 50%; right: 20px; transform: translateY(-50%); ' +
            'background: #46b450; color: white; padding: 8px 12px; ' +
            'border-radius: 20px; font-size: 12px; opacity: 0; ' +
            'transition: opacity 0.3s ease; z-index: 9999;">' +
            '✓ 自動保存済み</div>');
        
        $('body').append($indicator);
        
        return $indicator;
    }
    
    const $saveIndicator = addSaveIndicator();
    
    // 保存成功時にインジケーター表示
    $(document).on('ajaxSuccess', function(event, xhr, settings) {
        if (settings.data && settings.data.includes('spt_toggle_progress')) {
            $saveIndicator.css('opacity', 1);
            setTimeout(function() {
                $saveIndicator.css('opacity', 0);
            }, 1500);
        }
    });
    
    // 14. パフォーマンス最適化
    let updateTimeout;
    
    function throttledUpdate($item) {
        clearTimeout(updateTimeout);
        updateTimeout = setTimeout(function() {
            updateParentStatus($item);
        }, 100);
    }
    
    // 15. 初期化実行
    forceCloseAll();
    
    // ページ読み込み完了後にアニメーション実行
    setTimeout(animateProgressBars, 500);
    
    // デバッグ用グローバル関数
    window.sptFlexibleDebug = {
        forceCloseAll: forceCloseAll,
        showNotification: showNotification,
        animateProgressBars: animateProgressBars,
        updateAllProgress: function() {
            $('.spt-item').each(function() {
                updateItemStatus($(this));
            });
        }
    };
    
    console.log('柔軟構造学習進捗管理システム初期化完了');
});