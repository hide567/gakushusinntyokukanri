/**
 * 学習進捗管理 - フロントエンドJavaScript（シンプル修正版）
 * assets/js/frontend.js
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('学習進捗管理システム開始');
    
    // 基本設定
    const ajaxUrl = (typeof spt_data !== 'undefined' && spt_data.ajax_url) || '/wp-admin/admin-ajax.php';
    const nonce = (typeof spt_data !== 'undefined' && spt_data.nonce) || '';
    
    // 1. 初期状態を強制的に閉じる
    function forceCloseAll() {
        console.log('全要素を強制的に閉じます');
        
        $('.spt-subject').each(function() {
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
        
        const $subject = $(this).closest('.spt-subject');
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
        const $subject = $item.closest('.spt-subject');
        
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
    }
    
    function updateSubjectStatus($subject) {
        const $items = $subject.find('.spt-item');
        const totalItems = $items.length;
        const understoodItems = $items.filter('.understood').length;
        const percent = totalItems > 0 ? Math.ceil((understoodItems / totalItems) * 100) : 0;
        
        $subject.find('.spt-percent').text('(' + percent + '%)');
        $subject.find('.spt-progress-fill').css('width', percent + '%');
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
            console.error('データ属性が不完全です');
            return;
        }
        
        const $understandCheck = $item.find('.spt-check-understand');
        const $masterCheck = $item.find('.spt-check-master');
        
        let level = 0;
        if ($understandCheck.prop('checked')) level = 1;
        if ($masterCheck.prop('checked')) level = 2;
        
        console.log('保存開始:', { subject, chapter, section, item, level });
        
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
            }
        });
    }
    
    // 8. 進捗リセット
    $(document).on('click', '.spt-reset-btn', function(e) {
        e.preventDefault();
        
        const subject = $(this).data('subject');
        const $subject = $('.spt-subject[data-subject="' + subject + '"]');
        
        if (!confirm('この科目の進捗をリセットしますか？')) {
            return;
        }
        
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
                    $subject.find('.spt-check-understand, .spt-check-master').prop('checked', false);
                    $subject.find('.spt-item, .spt-section, .spt-chapter').removeClass('understood mastered completed');
                    $subject.find('.spt-percent').text('(0%)');
                    $subject.find('.spt-progress-fill').css('width', '0%');
                    $subject.find('.spt-chapter-percent, .spt-section-percent').text('0%');
                    
                    showNotification('進捗をリセットしました', 'success');
                } else {
                    showNotification('リセットに失敗しました', 'error');
                }
            },
            error: function() {
                showNotification('通信エラーが発生しました', 'error');
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
            'max-width: 300px; font-size: 14px;">' + message + '</div>');
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // 10. 初期化実行
    forceCloseAll();
    
    // デバッグ用グローバル関数
    window.sptDebug = {
        forceCloseAll: forceCloseAll,
        showNotification: showNotification
    };
    
    console.log('学習進捗管理システム初期化完了');
});