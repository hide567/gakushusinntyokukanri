/**
 * 学習進捗管理 - フロントエンドJavaScript（シンプル版）
 * assets/js/frontend.js
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // 設定
    const config = {
        saveDelay: 100, // 即座に保存
        animationSpeed: 0, // アニメーション無効
        storagePrefix: 'spt_'
    };
    
    // 状態管理
    let saveTimeout = null;
    let isProcessing = false;
    
    // データ取得
    const ajaxUrl = spt_data?.ajax_url || '/wp-admin/admin-ajax.php';
    const nonce = spt_data?.nonce || '';
    const colors = {
        understood: spt_data?.first_check_color || '#e6f7e6',
        mastered: spt_data?.second_check_color || '#ffebcc'
    };
    
    // 初期化
    init();
    
    function init() {
        bindEvents();
        restoreState();
        console.log('学習進捗管理システム（シンプル版）が初期化されました');
    }
    
    // イベントバインド
    function bindEvents() {
        // 科目ヘッダークリック（展開/折りたたみ）
        $(document).on('click', '.spt-subject-header', handleSubjectToggle);
        
        // 章の展開/折りたたみ
        $(document).on('click', '.spt-chapter-header', handleChapterToggle);
        
        // チェックボックス変更
        $(document).on('change', '.spt-check-understand', handleUnderstandCheck);
        $(document).on('change', '.spt-check-master', handleMasterCheck);
        
        // 進捗リセット
        $(document).on('click', '.spt-reset-btn', handleReset);
        
        // オンライン/オフライン状態監視
        $(window).on('online', () => console.log('オンラインになりました'));
        $(window).on('offline', () => showNotification('オフラインです。変更は保存されない可能性があります。', 'warning'));
    }
    
    // 科目の展開/折りたたみ
    function handleSubjectToggle(e) {
        e.preventDefault();
        
        const $subject = $(this).closest('.spt-subject');
        const $content = $subject.find('.spt-subject-content');
        const $toggle = $(this).find('.spt-subject-toggle');
        
        if ($content.is(':visible')) {
            $content.hide();
            $toggle.text('▶');
            $subject.removeClass('expanded');
        } else {
            $content.show();
            $toggle.text('▼');
            $subject.addClass('expanded');
        }
        
        // 状態保存
        const subject = $subject.data('subject');
        const isExpanded = $content.is(':visible');
        saveToStorage(`subject_${subject}`, isExpanded ? 'expanded' : 'collapsed');
    }
    
    // 章の展開/折りたたみ（即座に切り替え）
    function handleChapterToggle(e) {
        e.preventDefault();
        e.stopPropagation(); // 親要素への伝播防止
        
        const $chapter = $(this).closest('.spt-chapter');
        const $content = $chapter.find('.spt-chapter-content');
        const $toggle = $(this).find('.spt-chapter-toggle');
        
        if ($content.is(':visible')) {
            $content.hide();
            $toggle.text('+');
            $chapter.removeClass('expanded');
        } else {
            $content.show();
            $toggle.text('-');
            $chapter.addClass('expanded');
        }
        
        // 状態保存
        const subject = $chapter.closest('.spt-subject').data('subject');
        const chapter = $chapter.data('chapter');
        const isExpanded = $content.is(':visible');
        saveToStorage(`chapter_${subject}_${chapter}`, isExpanded ? 'expanded' : 'collapsed');
    }
    
    // 理解レベルチェック
    function handleUnderstandCheck(e) {
        const $item = $(this).closest('.spt-item');
        const $masterCheck = $item.find('.spt-check-master');
        const isChecked = $(this).prop('checked');
        
        // 理解のチェックを外した場合、習得も外す
        if (!isChecked) {
            $masterCheck.prop('checked', false);
        }
        
        updateItemStatus($item);
        saveProgress($item);
    }
    
    // 習得レベルチェック
    function handleMasterCheck(e) {
        const $item = $(this).closest('.spt-item');
        const $understandCheck = $item.find('.spt-check-understand');
        const isChecked = $(this).prop('checked');
        
        // 習得をチェックした場合、理解も自動的にチェック
        if (isChecked) {
            $understandCheck.prop('checked', true);
        }
        
        updateItemStatus($item);
        saveProgress($item);
    }
    
    // 項目状態更新
    function updateItemStatus($item) {
        const $understandCheck = $item.find('.spt-check-understand');
        const $masterCheck = $item.find('.spt-check-master');
        
        $item.removeClass('understood mastered');
        
        if ($masterCheck.prop('checked')) {
            $item.addClass('understood mastered');
        } else if ($understandCheck.prop('checked')) {
            $item.addClass('understood');
        }
        
        // 上位コンテナの状態も更新
        updateParentStatus($item);
    }
    
    // 上位コンテナの状態更新
    function updateParentStatus($item) {
        const $section = $item.closest('.spt-section');
        const $chapter = $item.closest('.spt-chapter');
        
        // 節の状態更新
        updateSectionStatus($section);
        
        // 章の状態更新
        updateChapterStatus($chapter);
    }
    
    // 節の状態更新
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
        
        // パーセント更新
        const percent = totalItems > 0 ? Math.ceil((understoodItems / totalItems) * 100) : 0;
        $section.find('.spt-section-percent').text(`${percent}%`);
    }
    
    // 章の状態更新
    function updateChapterStatus($chapter) {
        const $sections = $chapter.find('.spt-section');
        const totalSections = $sections.length;
        const completedSections = $sections.filter('.completed').length;
        const masteredSections = $sections.filter('.mastered').length;
        
        $chapter.removeClass('completed mastered');
        
        if (masteredSections === totalSections && totalSections > 0) {
            $chapter.addClass('completed mastered');
        } else if (completedSections === totalSections && totalSections > 0) {
            $chapter.addClass('completed');
        }
        
        // パーセント更新
        const $items = $chapter.find('.spt-item');
        const totalItems = $items.length;
        const understoodItems = $items.filter('.understood').length;
        const percent = totalItems > 0 ? Math.ceil((understoodItems / totalItems) * 100) : 0;
        $chapter.find('.spt-chapter-percent').text(`${percent}%`);
    }
    
    // 進捗保存（デバウンス付き）
    function saveProgress($item) {
        if (saveTimeout) {
            clearTimeout(saveTimeout);
        }
        
        saveTimeout = setTimeout(() => {
            performSave($item);
        }, config.saveDelay);
    }
    
    // 実際の保存処理
    function performSave($item) {
        if (isProcessing) return;
        
        const itemData = extractItemData($item);
        if (!itemData) {
            console.error('項目データの取得に失敗しました');
            return;
        }
        
        const $understandCheck = $item.find('.spt-check-understand');
        const $masterCheck = $item.find('.spt-check-master');
        
        let level = 0;
        if ($understandCheck.prop('checked')) level = 1;
        if ($masterCheck.prop('checked')) level = 2;
        
        isProcessing = true;
        $item.addClass('processing');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_toggle_progress',
                subject: itemData.subject,
                chapter: itemData.chapter,
                section: itemData.section,
                item: itemData.item,
                level: level,
                nonce: nonce
            },
            timeout: 15000,
            success: function(response) {
                if (response.success) {
                    // 科目全体の進捗率更新
                    updateSubjectProgress(itemData.subject, response.data.percent);
                } else {
                    console.error('保存エラー:', response.data);
                    showNotification('保存に失敗しました: ' + (response.data?.message || '不明なエラー'), 'error');
                    revertItemState($item);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', status, error);
                let errorMsg = '通信エラーが発生しました';
                
                if (status === 'timeout') {
                    errorMsg = '保存がタイムアウトしました';
                } else if (xhr.status === 403) {
                    errorMsg = 'セキュリティエラーが発生しました';
                }
                
                showNotification(errorMsg, 'error');
                revertItemState($item);
            },
            complete: function() {
                isProcessing = false;
                $item.removeClass('processing');
            }
        });
    }
    
    // 項目データ抽出
    function extractItemData($item) {
        const subject = $item.data('subject');
        const chapter = $item.data('chapter');
        const section = $item.data('section');
        const item = $item.data('item');
        
        if (!subject || !chapter || !section || !item) {
            return null;
        }
        
        return { subject, chapter, section, item };
    }
    
    // 科目進捗率更新
    function updateSubjectProgress(subject, percent) {
        const $subject = $(`.spt-subject[data-subject="${subject}"]`);
        $subject.find('.spt-percent').text(`(${percent}%)`);
        $subject.find('.spt-progress-fill').css('width', `${percent}%`);
    }
    
    // 項目状態を元に戻す
    function revertItemState($item) {
        const $understandCheck = $item.find('.spt-check-understand');
        const $masterCheck = $item.find('.spt-check-master');
        
        $understandCheck.prop('checked', !$understandCheck.prop('checked'));
        $masterCheck.prop('checked', !$masterCheck.prop('checked'));
        
        updateItemStatus($item);
    }
    
    // 進捗リセット
    function handleReset(e) {
        e.preventDefault();
        
        const subject = $(this).data('subject');
        const $subject = $(`.spt-subject[data-subject="${subject}"]`);
        
        if (!confirm('この科目の進捗をリセットしますか？この操作は元に戻せません。')) {
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
                if (response.success) {
                    // UI をリセット
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
    }
    
    // 通知表示
    function showNotification(message, type = 'info') {
        // 既存の通知を削除
        $('.spt-notification').remove();
        
        const typeClass = type === 'error' ? 'error' : type === 'warning' ? 'warning' : type === 'success' ? 'success' : 'info';
        
        const $notification = $(`
            <div class="spt-notification ${typeClass}" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'error' ? '#dc3232' : type === 'warning' ? '#ffb900' : type === 'success' ? '#46b450' : '#0073aa'};
                color: white;
                padding: 12px 20px;
                border-radius: 6px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                max-width: 300px;
                font-size: 14px;
                animation: slideInRight 0.3s ease;
            ">${message}</div>
        `);
        
        $('body').append($notification);
        
        // 3秒後に自動削除
        setTimeout(() => {
            $notification.fadeOut(200, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // ローカルストレージ操作
    function saveToStorage(key, value) {
        try {
            localStorage.setItem(config.storagePrefix + key, JSON.stringify(value));
        } catch (e) {
            console.warn('ローカルストレージへの保存に失敗:', e);
        }
    }
    
    function getFromStorage(key, defaultValue = null) {
        try {
            const value = localStorage.getItem(config.storagePrefix + key);
            return value ? JSON.parse(value) : defaultValue;
        } catch (e) {
            console.warn('ローカルストレージからの読み込みに失敗:', e);
            return defaultValue;
        }
    }
    
    // 状態復元
    function restoreState() {
        // 科目の展開状態復元
        $('.spt-subject').each(function() {
            const $subject = $(this);
            const subject = $subject.data('subject');
            const state = getFromStorage(`subject_${subject}`);
            
            if (state === 'expanded') {
                const $content = $subject.find('.spt-subject-content');
                const $toggle = $subject.find('.spt-subject-toggle');
                
                $content.show();
                $toggle.text('▼');
                $subject.addClass('expanded');
            }
        });
        
        // 章の展開状態復元
        $('.spt-chapter').each(function() {
            const $chapter = $(this);
            const subject = $chapter.closest('.spt-subject').data('subject');
            const chapter = $chapter.data('chapter');
            const state = getFromStorage(`chapter_${subject}_${chapter}`);
            
            if (state === 'expanded') {
                const $content = $chapter.find('.spt-chapter-content');
                const $toggle = $chapter.find('.spt-chapter-toggle');
                
                $content.show();
                $toggle.text('-');
                $chapter.addClass('expanded');
            }
        });
    }
    
    // CSS アニメーション追加
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    `;
    document.head.appendChild(style);
});