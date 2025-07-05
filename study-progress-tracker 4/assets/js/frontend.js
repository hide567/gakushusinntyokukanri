/**
 * 学習進捗管理 - フロントエンドJavaScript（修正版）
 * assets/js/frontend.js
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // 設定
    const config = {
        saveDelay: 500, // 保存遅延
        storagePrefix: 'spt_'
    };
    
    // 状態管理
    let saveTimeout = null;
    let isProcessing = false;
    
    // データ取得とフォールバック
    const ajaxUrl = (typeof spt_data !== 'undefined' && spt_data.ajax_url) || '/wp-admin/admin-ajax.php';
    const nonce = (typeof spt_data !== 'undefined' && spt_data.nonce) || '';
    const colors = {
        understood: (typeof spt_data !== 'undefined' && spt_data.first_check_color) || '#e6f7e6',
        mastered: (typeof spt_data !== 'undefined' && spt_data.second_check_color) || '#ffebcc'
    };
    
    // 初期化
    init();
    
    function init() {
        console.log('学習進捗管理システム初期化開始');
        
        // 要素の存在確認
        const $tracker = $('.spt-progress-tracker');
        if ($tracker.length === 0) {
            console.warn('進捗トラッカーが見つかりません');
            return;
        }
        
        console.log('進捗トラッカー要素:', $tracker.length + '個見つかりました');
        
        // 科目をデフォルトで閉じた状態に設定
        const $subjects = $('.spt-subject');
        if ($subjects.length > 0) {
            console.log('科目数:', $subjects.length);
            
            // 全ての科目を閉じた状態にする
            $subjects.each(function() {
                const $subject = $(this);
                const $content = $subject.find('.spt-subject-content');
                const $toggle = $subject.find('.spt-subject-toggle');
                
                $content.hide();
                $toggle.text('▶');
                $subject.removeClass('expanded');
            });
            
            console.log('全ての科目をデフォルトで閉じました');
        }
        
        bindEvents();
        restoreState();
        updateColors();
        
        console.log('学習進捗管理システム初期化完了');
    }
    
    // イベントバインド
    function bindEvents() {
        console.log('イベントバインド開始');
        
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
        
        console.log('イベントバインド完了');
    }
    
    // 科目の展開/折りたたみ
    function handleSubjectToggle(e) {
        e.preventDefault();
        
        const $subject = $(this).closest('.spt-subject');
        const $content = $subject.find('.spt-subject-content');
        const $toggle = $(this).find('.spt-subject-toggle');
        
        console.log('科目トグル:', $subject.data('subject'));
        
        if ($content.is(':visible')) {
            $content.slideUp(200);
            $toggle.text('▶');
            $subject.removeClass('expanded');
        } else {
            $content.slideDown(200);
            $toggle.text('▼');
            $subject.addClass('expanded');
        }
        
        // 状態保存
        const subject = $subject.data('subject');
        const isExpanded = $content.is(':visible') || !$content.is(':hidden');
        saveToStorage(`subject_${subject}`, isExpanded ? 'expanded' : 'collapsed');
    }
    
    // 章の展開/折りたたみ
    function handleChapterToggle(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $chapter = $(this).closest('.spt-chapter');
        const $content = $chapter.find('.spt-chapter-content');
        const $toggle = $(this).find('.spt-chapter-toggle');
        
        console.log('章トグル:', $chapter.data('chapter'));
        
        if ($content.is(':visible')) {
            $content.slideUp(200);
            $toggle.text('+');
            $chapter.removeClass('expanded');
        } else {
            $content.slideDown(200);
            $toggle.text('-');
            $chapter.addClass('expanded');
        }
        
        // 状態保存
        const subject = $chapter.closest('.spt-subject').data('subject');
        const chapter = $chapter.data('chapter');
        const isExpanded = $content.is(':visible') || !$content.is(':hidden');
        saveToStorage(`chapter_${subject}_${chapter}`, isExpanded ? 'expanded' : 'collapsed');
    }
    
    // 理解レベルチェック
    function handleUnderstandCheck(e) {
        const $item = $(this).closest('.spt-item');
        const $masterCheck = $item.find('.spt-check-master');
        const isChecked = $(this).prop('checked');
        
        console.log('理解チェック:', isChecked);
        
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
        
        console.log('習得チェック:', isChecked);
        
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
        const $subject = $item.closest('.spt-subject');
        
        // 節の状態更新
        updateSectionStatus($section);
        
        // 章の状態更新
        updateChapterStatus($chapter);
        
        // 科目の状態更新
        updateSubjectStatus($subject);
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
    
    // 科目の状態更新
    function updateSubjectStatus($subject) {
        const $items = $subject.find('.spt-item');
        const totalItems = $items.length;
        const understoodItems = $items.filter('.understood').length;
        const percent = totalItems > 0 ? Math.ceil((understoodItems / totalItems) * 100) : 0;
        
        $subject.find('.spt-percent').text(`(${percent}%)`);
        $subject.find('.spt-progress-fill').css('width', `${percent}%`);
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
        
        console.log('保存開始:', itemData, 'レベル:', level);
        
        isProcessing = true;
        $item.addClass('processing');
        
        // nonce チェック
        if (!nonce) {
            console.error('nonceが設定されていません');
            showNotification('セキュリティエラー: ページを再読み込みしてください', 'error');
            isProcessing = false;
            $item.removeClass('processing');
            return;
        }
        
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
                console.log('Ajax成功:', response);
                if (response && response.success) {
                    // 科目全体の進捗率更新
                    if (response.data && response.data.percent !== undefined) {
                        updateSubjectProgressFromServer(itemData.subject, response.data.percent);
                    }
                    showNotification('保存しました', 'success');
                } else {
                    console.error('保存エラー:', response);
                    const errorMsg = (response && response.data && response.data.message) ? response.data.message : '保存に失敗しました';
                    showNotification(errorMsg, 'error');
                    revertItemState($item);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', status, error, xhr);
                let errorMsg = '通信エラーが発生しました';
                
                if (status === 'timeout') {
                    errorMsg = '保存がタイムアウトしました';
                } else if (xhr.status === 403) {
                    errorMsg = 'セキュリティエラーが発生しました。ページを再読み込みしてください。';
                } else if (xhr.status === 0) {
                    errorMsg = 'ネットワークエラーが発生しました';
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
            console.error('データ属性が不完全:', { subject, chapter, section, item });
            return null;
        }
        
        return { subject, chapter, section, item };
    }
    
    // サーバーからの科目進捗率更新
    function updateSubjectProgressFromServer(subject, percent) {
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
        
        console.log('リセット開始:', subject);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_reset_progress',
                subject: subject,
                nonce: nonce
            },
            success: function(response) {
                console.log('リセット成功:', response);
                if (response && response.success) {
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
            error: function(xhr, status, error) {
                console.error('リセットエラー:', status, error);
                showNotification('通信エラーが発生しました', 'error');
            }
        });
    }
    
    // 通知表示
    function showNotification(message, type = 'info') {
        // 既存の通知を削除
        $('.spt-notification').remove();
        
        const colors = {
            error: '#dc3232',
            warning: '#ffb900',
            success: '#46b450',
            info: '#0073aa'
        };
        
        const bgColor = colors[type] || colors.info;
        
        const $notification = $(`
            <div class="spt-notification" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${bgColor};
                color: white;
                padding: 12px 20px;
                border-radius: 6px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                z-index: 10000;
                max-width: 300px;
                font-size: 14px;
                animation: slideInRight 0.3s ease;
                font-family: inherit;
            ">${message}</div>
        `);
        
        $('body').append($notification);
        
        // 3秒後に自動削除
        setTimeout(() => {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // 色設定の更新
    function updateColors() {
        const style = document.createElement('style');
        style.innerHTML = `
            .spt-progress-tracker {
                --understood-color: ${colors.understood};
                --mastered-color: ${colors.mastered};
            }
        `;
        document.head.appendChild(style);
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
        console.log('状態復元開始');
        
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
                
                console.log('科目状態復元:', subject, 'expanded');
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
                
                console.log('章状態復元:', subject, chapter, 'expanded');
            }
        });
        
        console.log('状態復元完了');
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
        
        .spt-subject-content,
        .spt-chapter-content {
            overflow: hidden;
        }
    `;
    document.head.appendChild(style);
    
    // グローバルに関数を公開（デバッグ用）
    window.sptDebug = {
        showNotification: showNotification,
        updateSubjectStatus: function(subject) {
            const $subject = $(`.spt-subject[data-subject="${subject}"]`);
            updateSubjectStatus($subject);
        },
        getStorageData: function() {
            const data = {};
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key.startsWith(config.storagePrefix)) {
                    data[key] = localStorage.getItem(key);
                }
            }
            return data;
        }
    };
});