/**
 * 学習進捗管理 - 即座展開対応フロントエンドJavaScript
 * assets/js/frontend-flexible.js
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('即座展開学習進捗管理システム開始');
    
    // 基本設定
    const ajaxUrl = (typeof spt_data !== 'undefined' && spt_data.ajax_url) || '/wp-admin/admin-ajax.php';
    const nonce = (typeof spt_data !== 'undefined' && spt_data.nonce) || '';
    
    // デバッグ用
    if (typeof spt_data !== 'undefined' && spt_data.debug) {
        console.log('Ajax URL:', ajaxUrl);
        console.log('Nonce:', nonce);
    }
    
    // 1. 初期状態を強制的に閉じる（高速化）
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
    
    // 2. 科目のクリックイベント（即座展開）
    $(document).on('click', '.spt-subject-header', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $subject = $(this).closest('.spt-subject-flexible');
        const $content = $subject.find('.spt-subject-content');
        const $toggle = $subject.find('.spt-subject-toggle');
        
        console.log('科目クリック:', $subject.data('subject'));
        
        if ($content.is(':visible')) {
            // 即座に閉じる
            $content.hide();
            $toggle.text('▶');
            $subject.removeClass('expanded');
            console.log('科目を即座に閉じました');
        } else {
            // 即座に開く
            $content.show();
            $toggle.text('▼');
            $subject.addClass('expanded');
            console.log('科目を即座に開きました');
        }
    });
    
    // 3. 章のクリックイベント（即座展開）
    $(document).on('click', '.spt-chapter-header', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $chapter = $(this).closest('.spt-chapter');
        const $content = $chapter.find('.spt-chapter-content');
        const $toggle = $chapter.find('.spt-chapter-toggle');
        
        console.log('章クリック:', $chapter.data('chapter'));
        
        if ($content.is(':visible')) {
            // 即座に閉じる
            $content.hide();
            $toggle.text('+');
            $chapter.removeClass('expanded');
            console.log('章を即座に閉じました');
        } else {
            // 即座に開く
            $content.show();
            $toggle.text('-');
            $chapter.addClass('expanded');
            console.log('章を即座に開きました');
        }
    });
    
    // 4. チェックボックスイベント（即座反映）
    $(document).on('change', '.spt-check-understand', function() {
        const $item = $(this).closest('.spt-item');
        const $masterCheck = $item.find('.spt-check-master');
        const isChecked = $(this).prop('checked');
        
        if (!isChecked) {
            $masterCheck.prop('checked', false);
        }
        
        // 即座に画面状態を更新
        updateItemStatusInstantly($item);
        
        // バックグラウンドで保存
        saveProgressInBackground($item);
    });
    
    $(document).on('change', '.spt-check-master', function() {
        const $item = $(this).closest('.spt-item');
        const $understandCheck = $item.find('.spt-check-understand');
        const isChecked = $(this).prop('checked');
        
        if (isChecked) {
            $understandCheck.prop('checked', true);
        }
        
        // 即座に画面状態を更新
        updateItemStatusInstantly($item);
        
        // バックグラウンドで保存
        saveProgressInBackground($item);
    });
    
    // 5. 項目状態更新（即座反映）
    function updateItemStatusInstantly($item) {
        const $understandCheck = $item.find('.spt-check-understand');
        const $masterCheck = $item.find('.spt-check-master');
        
        // 即座にクラスを更新
        $item.removeClass('understood mastered');
        
        if ($masterCheck.prop('checked')) {
            $item.addClass('understood mastered');
        } else if ($understandCheck.prop('checked')) {
            $item.addClass('understood');
        }
        
        // 親要素も即座に更新
        updateParentStatusInstantly($item);
    }
    
    // 6. 上位要素の状態更新（即座反映）
    function updateParentStatusInstantly($item) {
        const $section = $item.closest('.spt-section');
        const $chapter = $item.closest('.spt-chapter');
        const $subject = $item.closest('.spt-subject-flexible');
        
        // 節の即座更新
        updateSectionStatusInstantly($section);
        // 章の即座更新
        updateChapterStatusInstantly($chapter);
        // 科目の即座更新
        updateSubjectStatusInstantly($subject);
    }
    
    function updateSectionStatusInstantly($section) {
        if (!$section.length) return;
        
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
        
        // 節内進捗バー即座更新
        $section.find('.spt-progress-fill-section').css('width', percent + '%');
    }
    
    function updateChapterStatusInstantly($chapter) {
        if (!$chapter.length) return;
        
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
        
        // 章内進捗バー即座更新
        $chapter.find('.spt-progress-fill-chapter').css('width', percent + '%');
    }
    
    function updateSubjectStatusInstantly($subject) {
        if (!$subject.length) return;
        
        const $items = $subject.find('.spt-item');
        const totalItems = $items.length;
        const understoodItems = $items.filter('.understood').length;
        const percent = totalItems > 0 ? Math.ceil((understoodItems / totalItems) * 100) : 0;
        
        $subject.find('.spt-percent').text(percent + '%');
        
        // ヘッダー内進捗バー即座更新
        $subject.find('.spt-progress-fill-header').css('width', percent + '%');
    }
    
    // 7. 進捗保存（バックグラウンド）
    function saveProgressInBackground($item) {
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
        
        console.log('バックグラウンド保存開始:', { subject, chapter, section, item, level });
        
        // 項目に保存中表示
        $item.addClass('bg-saving');
        
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
            timeout: 5000, // 5秒タイムアウト
            success: function(response) {
                console.log('保存成功:', response);
                if (response && response.success) {
                    $item.removeClass('bg-saving').addClass('bg-saved');
                    setTimeout(() => $item.removeClass('bg-saved'), 1000);
                } else {
                    console.error('保存エラー:', response);
                    $item.removeClass('bg-saving').addClass('bg-save-error');
                    setTimeout(() => $item.removeClass('bg-save-error'), 2000);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', status, error, xhr.responseText);
                $item.removeClass('bg-saving').addClass('bg-save-error');
                setTimeout(() => $item.removeClass('bg-save-error'), 2000);
            }
        });
    }
    
    // 8. 進捗リセット（即座反映）
    $(document).on('click', '.spt-reset-btn', function(e) {
        e.preventDefault();
        
        const subject = $(this).data('subject');
        const $subject = $('.spt-subject-flexible[data-subject="' + subject + '"]');
        
        if (!confirm('この科目の進捗をリセットしますか？')) {
            return;
        }
        
        // 即座に画面をリセット
        $subject.find('.spt-check-understand, .spt-check-master').prop('checked', false);
        $subject.find('.spt-item, .spt-section, .spt-chapter').removeClass('understood mastered completed');
        $subject.find('.spt-percent').text('0%');
        $subject.find('.spt-chapter-percent, .spt-section-percent').text('0%');
        $subject.find('.spt-progress-fill-header, .spt-progress-fill-chapter, .spt-progress-fill-section').css('width', '0%');
        
        showNotification('進捗をリセットしました', 'success');
        
        // バックグラウンドでサーバーリセット
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_reset_progress',
                subject: subject,
                nonce: nonce
            },
            timeout: 5000,
            success: function(response) {
                if (response && response.success) {
                    console.log('サーバーリセット完了');
                } else {
                    console.error('サーバーリセット失敗');
                }
            },
            error: function(xhr, status, error) {
                console.error('Reset error:', status, error);
            }
        });
    });
    
    // 9. 通知表示（高速化）
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
        
        // 即座にフェードイン
        $notification.animate({opacity: 1}, 100);
        
        setTimeout(function() {
            $notification.animate({opacity: 0}, 100, function() {
                $(this).remove();
            });
        }, 2000);
    }
    
    // 10. 進捗バーのアニメーション効果（高速化）
    function animateProgressBars() {
        $('.spt-progress-fill-header, .spt-progress-fill-chapter, .spt-progress-fill-section').each(function() {
            const $bar = $(this);
            const width = $bar.data('target-width') || $bar.css('width');
            
            if (width !== '0px' && width !== '0%') {
                $bar.css('width', '0%').animate({width: width}, 300); // 300msに短縮
            }
        });
    }
    
    // 11. スムーズスクロール（高速化）
    function scrollToElement($element, offset = 100) {
        if ($element.length) {
            $('html, body').animate({
                scrollTop: $element.offset().top - offset
            }, 300); // 300msに短縮
        }
    }
    
    // 12. キーボードショートカット（高速化）
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
        
        // Spaceで最初の科目を開く/閉じる
        if (e.key === ' ' && !$(e.target).is('input, textarea')) {
            e.preventDefault();
            $('.spt-subject-header').first().click();
        }
    });
    
    // 13. 自動保存状態の視覚化（高速化）
    function addSaveIndicator() {
        const $indicator = $('<div id="save-indicator" style="' +
            'position: fixed; top: 50%; right: 20px; transform: translateY(-50%); ' +
            'background: #46b450; color: white; padding: 8px 12px; ' +
            'border-radius: 20px; font-size: 12px; opacity: 0; ' +
            'transition: opacity 0.1s ease; z-index: 9999;">' +
            '✓ 自動保存</div>');
        
        $('body').append($indicator);
        
        return $indicator;
    }
    
    const $saveIndicator = addSaveIndicator();
    
    // 保存成功時にインジケーター表示（高速化）
    $(document).on('ajaxSuccess', function(event, xhr, settings) {
        if (settings.data && settings.data.includes('spt_toggle_progress')) {
            $saveIndicator.css('opacity', 1);
            setTimeout(function() {
                $saveIndicator.css('opacity', 0);
            }, 800); // 800msに短縮
        }
    });
    
    // 14. 高速CSS追加
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            /* 展開・収縮の高速化 */
            .spt-subject-content,
            .spt-chapter-content {
                transition: none !important;
            }
            
            /* 進捗バーの高速化 */
            .spt-progress-fill-header,
            .spt-progress-fill-chapter,
            .spt-progress-fill-section {
                transition: width 0.2s ease !important;
            }
            
            /* チェックボックス状態の高速化 */
            .spt-item {
                transition: background-color 0.1s ease, border-color 0.1s ease !important;
            }
            
            /* 保存状態の視覚化 */
            .bg-saving {
                background: linear-gradient(90deg, transparent, rgba(33, 150, 243, 0.1), transparent) !important;
                animation: saving-pulse 1s infinite;
            }
            
            .bg-saved {
                background: rgba(76, 175, 80, 0.1) !important;
                border-left: 3px solid #4CAF50 !important;
            }
            
            .bg-save-error {
                background: rgba(244, 67, 54, 0.1) !important;
                border-left: 3px solid #f44336 !important;
            }
            
            @keyframes saving-pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.7; }
            }
            
            /* クリック反応の高速化 */
            .spt-subject-header,
            .spt-chapter-header {
                transition: background-color 0.1s ease !important;
            }
            
            .spt-subject-header:active,
            .spt-chapter-header:active {
                transform: scale(0.99);
                transition: transform 0.05s ease;
            }
            
            /* チェックボックスの高速化 */
            .spt-check-label {
                transition: all 0.1s ease !important;
            }
            
            .spt-check-label:active {
                transform: scale(0.95);
            }
            
            /* 通知の高速化 */
            .spt-notification {
                transition: opacity 0.1s ease !important;
            }
            
            /* トグルボタンの回転高速化 */
            .spt-subject-toggle,
            .spt-chapter-toggle {
                transition: transform 0.1s ease !important;
            }
            
            /* ホバー効果の高速化 */
            .spt-item:hover {
                transform: translateY(-1px);
                transition: transform 0.1s ease;
            }
            
            /* 読み込み高速化 */
            .spt-progress-tracker * {
                will-change: auto;
            }
            
            /* GPU加速 */
            .spt-progress-fill-header,
            .spt-progress-fill-chapter,
            .spt-progress-fill-section {
                transform: translateZ(0);
            }
        `)
        .appendTo('head');
    
    // 15. 初期化実行（高速化）
    forceCloseAll();
    
    // ページ読み込み完了後にアニメーション実行（高速化）
    setTimeout(animateProgressBars, 100); // 100msに短縮
    
    // 初期状態の進捗バーを設定
    $('.spt-item').each(function() {
        updateItemStatusInstantly($(this));
    });
    
    // デバッグ用グローバル関数
    window.sptFlexibleDebug = {
        forceCloseAll: forceCloseAll,
        showNotification: showNotification,
        animateProgressBars: animateProgressBars,
        updateAllProgress: function() {
            $('.spt-item').each(function() {
                updateItemStatusInstantly($(this));
            });
        },
        testSpeed: function() {
            console.time('展開テスト');
            $('.spt-subject-header').first().click();
            setTimeout(() => {
                $('.spt-chapter-header').first().click();
                console.timeEnd('展開テスト');
            }, 10);
        }
    };
    
    console.log('即座展開学習進捗管理システム初期化完了');
    console.log('操作テスト: sptFlexibleDebug.testSpeed() で速度測定可能');
});