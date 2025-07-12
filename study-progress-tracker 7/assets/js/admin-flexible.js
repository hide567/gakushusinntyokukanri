/**
 * 学習進捗管理 - 即座反映対応管理画面JavaScript
 * assets/js/admin-flexible.js
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('即座反映システム開始');
    
    // 基本設定
    const ajaxUrl = spt_admin_data.ajax_url;
    const nonce = spt_admin_data.nonce;
    
    // 一時的なIDカウンター（重複回避用）
    let tempChapterId = Date.now();
    let tempSectionId = Date.now();
    let tempItemId = Date.now();
    
    // 1. 章追加ボタン（即座反映）
    $(document).on('click', '.add-chapter-btn', function() {
        const $button = $(this);
        const subject = $button.data('subject');
        const tempId = ++tempChapterId;
        
        // 即座に画面に追加
        const newChapter = createChapterElement(subject, tempId, '新しい章', true);
        const $container = $(`.subject-structure-card[data-subject="${subject}"] .chapters-container`);
        $container.find('.add-chapter-section').before(newChapter);
        
        // 追加された章の名前を編集モードに
        const $newChapterName = $container.find(`.chapter-item[data-chapter="${tempId}"] .chapter-name`);
        setTimeout(() => {
            $newChapterName.click();
        }, 50);
        
        // バックグラウンドで保存
        saveChapterToServer(subject, tempId, '新しい章', $newChapterName);
    });
    
    // 2. 節追加ボタン（即座反映）
    $(document).on('click', '.add-section-btn', function() {
        const $button = $(this);
        const subject = $button.data('subject');
        const chapter = $button.data('chapter');
        const tempId = ++tempSectionId;
        
        // 即座に画面に追加
        const newSection = createSectionElement(subject, chapter, tempId, '新しい節', true);
        const $sectionsContainer = $(`.chapter-item[data-chapter="${chapter}"] .sections-container`);
        
        // 空のメッセージがあれば削除
        $sectionsContainer.find('p').remove();
        
        $sectionsContainer.append(newSection);
        
        // 追加された節の名前を編集モードに
        const $newSectionName = $sectionsContainer.find(`.section-item[data-section="${tempId}"] .section-name`);
        setTimeout(() => {
            $newSectionName.click();
        }, 50);
        
        // バックグラウンドで保存
        saveSectionToServer(subject, chapter, tempId, '新しい節', $newSectionName);
    });
    
    // 3. 項追加ボタン（即座反映）
    $(document).on('click', '.add-item-btn', function() {
        const $button = $(this);
        const subject = $button.data('subject');
        const chapter = $button.data('chapter');
        const section = $button.data('section');
        const tempId = ++tempItemId;
        
        // 即座に画面に追加
        const newItem = createItemElement(subject, chapter, section, tempId, '新しい項目', true);
        const $itemsContainer = $(`.section-item[data-section="${section}"] .items-container`);
        
        // 空のメッセージがあれば削除
        $itemsContainer.find('p').remove();
        
        $itemsContainer.append(newItem);
        
        // 追加された項の名前を編集モードに
        const $newItemName = $itemsContainer.find(`.item-element[data-item="${tempId}"] .item-name`);
        setTimeout(() => {
            $newItemName.click();
        }, 50);
        
        // バックグラウンドで保存
        saveItemToServer(subject, chapter, section, tempId, '新しい項目', $newItemName);
    });
    
    // 4. バックグラウンド保存関数
    function saveChapterToServer(subject, tempId, chapterName, $element) {
        $element.addClass('saving');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_add_chapter',
                subject: subject,
                chapter_name: chapterName,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // 一時IDを実際のIDに置き換え
                    const realId = response.data.chapter_id;
                    const $chapterItem = $(`.chapter-item[data-chapter="${tempId}"]`);
                    
                    $chapterItem.attr('data-chapter', realId);
                    $element.attr('data-chapter', realId);
                    
                    // 子要素のdata属性も更新
                    $chapterItem.find('.add-section-btn').attr('data-chapter', realId);
                    $chapterItem.find('.delete-chapter-btn').attr('data-chapter', realId);
                    
                    $element.removeClass('saving').addClass('saved');
                    setTimeout(() => $element.removeClass('saved'), 2000);
                    
                    console.log(`章 "${chapterName}" が保存されました (ID: ${realId})`);
                } else {
                    $element.removeClass('saving').addClass('save-error');
                    console.error('章の保存に失敗しました:', response);
                }
            },
            error: function(xhr, status, error) {
                $element.removeClass('saving').addClass('save-error');
                console.error('章保存エラー:', error);
            }
        });
    }
    
    function saveSectionToServer(subject, chapter, tempId, sectionName, $element) {
        $element.addClass('saving');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_add_section',
                subject: subject,
                chapter: chapter,
                section_name: sectionName,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // 一時IDを実際のIDに置き換え
                    const realId = response.data.section_id;
                    const $sectionItem = $(`.section-item[data-section="${tempId}"]`);
                    
                    $sectionItem.attr('data-section', realId);
                    $element.attr('data-section', realId);
                    
                    // 子要素のdata属性も更新
                    $sectionItem.find('.add-item-btn').attr('data-section', realId);
                    $sectionItem.find('.delete-section-btn').attr('data-section', realId);
                    
                    $element.removeClass('saving').addClass('saved');
                    setTimeout(() => $element.removeClass('saved'), 2000);
                    
                    console.log(`節 "${sectionName}" が保存されました (ID: ${realId})`);
                } else {
                    $element.removeClass('saving').addClass('save-error');
                    console.error('節の保存に失敗しました:', response);
                }
            },
            error: function(xhr, status, error) {
                $element.removeClass('saving').addClass('save-error');
                console.error('節保存エラー:', error);
            }
        });
    }
    
    function saveItemToServer(subject, chapter, section, tempId, itemName, $element) {
        $element.addClass('saving');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_add_item',
                subject: subject,
                chapter: chapter,
                section: section,
                item_name: itemName,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // 一時IDを実際のIDに置き換え
                    const realId = response.data.item_id;
                    const $itemElement = $(`.item-element[data-item="${tempId}"]`);
                    
                    $itemElement.attr('data-item', realId);
                    $element.attr('data-item', realId);
                    
                    // 子要素のdata属性も更新
                    $itemElement.find('.delete-item-btn').attr('data-item', realId);
                    
                    $element.removeClass('saving').addClass('saved');
                    setTimeout(() => $element.removeClass('saved'), 2000);
                    
                    console.log(`項目 "${itemName}" が保存されました (ID: ${realId})`);
                } else {
                    $element.removeClass('saving').addClass('save-error');
                    console.error('項目の保存に失敗しました:', response);
                }
            },
            error: function(xhr, status, error) {
                $element.removeClass('saving').addClass('save-error');
                console.error('項目保存エラー:', error);
            }
        });
    }
    
    // 5. インライン編集機能（高速化）
    $(document).on('click', '.editable', function() {
        const $element = $(this);
        const currentText = $element.text().trim();
        const type = $element.data('type');
        
        // 既に編集中の場合は無視
        if ($element.find('input').length > 0) {
            return;
        }
        
        // 保存状態クラスをクリア
        $element.removeClass('saving saved save-error');
        
        // 編集可能状態の視覚的フィードバック
        $element.addClass('editing');
        
        // 入力フィールドを作成
        const $input = $('<input type="text" class="edit-input" />');
        $input.val(currentText);
        
        $element.html($input);
        $input.focus().select();
        
        // 保存処理
        function saveEdit() {
            const newText = $input.val().trim();
            
            if (!newText) {
                $element.removeClass('editing').text(currentText);
                return;
            }
            
            if (newText === currentText) {
                $element.removeClass('editing').text(currentText);
                return;
            }
            
            // 即座にテキストを更新
            $element.removeClass('editing').addClass('saving').text(newText);
            
            // バックグラウンドでサーバーに保存
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'spt_update_name',
                    subject: $element.data('subject'),
                    type: type,
                    chapter: $element.data('chapter') || 0,
                    section: $element.data('section') || 0,
                    item: $element.data('item') || 0,
                    new_name: newText,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $element.removeClass('saving').addClass('saved');
                        setTimeout(() => $element.removeClass('saved'), 1500);
                        console.log(`"${newText}" に更新されました`);
                    } else {
                        $element.removeClass('saving').addClass('save-error');
                        console.error('更新に失敗しました:', response);
                    }
                },
                error: function() {
                    $element.removeClass('saving').addClass('save-error');
                    console.error('通信エラーが発生しました');
                }
            });
        }
        
        // キーイベント
        $input.on('keypress', function(e) {
            if (e.which === 13) { // Enter
                e.preventDefault();
                saveEdit();
            }
        });
        
        $input.on('keydown', function(e) {
            if (e.which === 27) { // Escape
                $element.removeClass('editing').text(currentText);
            }
        });
        
        // フォーカスアウト
        $input.on('blur', function() {
            setTimeout(() => {
                if ($element.find('input').length > 0) {
                    saveEdit();
                }
            }, 50);
        });
    });
    
    // 6. 削除機能（即座反映オプション付き）
    $(document).on('click', '.delete-chapter-btn', function() {
        const $button = $(this);
        const chapterName = $button.closest('.chapter-item').find('.chapter-name').text();
        
        if (!confirm(`章「${chapterName}」を削除しますか？\n関連する進捗データも削除されます。`)) {
            return;
        }
        
        const subject = $button.data('subject');
        const chapter = $button.data('chapter');
        const $chapterElement = $button.closest('.chapter-item');
        
        // 即座に画面から削除（アニメーション付き）
        $chapterElement.addClass('deleting').fadeOut(200, function() {
            $(this).remove();
        });
        
        // バックグラウンドでサーバーから削除
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_delete_element',
                subject: subject,
                type: 'chapter',
                chapter: chapter,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log(`章 "${chapterName}" が削除されました`);
                    showNotification('章を削除しました', 'success');
                } else {
                    console.error('サーバー削除に失敗しました:', response);
                    showNotification('削除処理でエラーが発生しました', 'warning');
                }
            },
            error: function() {
                console.error('削除通信エラー');
                showNotification('削除通信でエラーが発生しました', 'warning');
            }
        });
    });
    
    $(document).on('click', '.delete-section-btn', function() {
        const $button = $(this);
        const sectionName = $button.closest('.section-item').find('.section-name').text();
        
        if (!confirm(`節「${sectionName}」を削除しますか？\n関連する進捗データも削除されます。`)) {
            return;
        }
        
        const subject = $button.data('subject');
        const chapter = $button.data('chapter');
        const section = $button.data('section');
        const $sectionElement = $button.closest('.section-item');
        
        // 即座に画面から削除
        $sectionElement.addClass('deleting').fadeOut(200, function() {
            $(this).remove();
        });
        
        // バックグラウンドでサーバーから削除
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_delete_element',
                subject: subject,
                type: 'section',
                chapter: chapter,
                section: section,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log(`節 "${sectionName}" が削除されました`);
                    showNotification('節を削除しました', 'success');
                } else {
                    console.error('サーバー削除に失敗しました:', response);
                    showNotification('削除処理でエラーが発生しました', 'warning');
                }
            },
            error: function() {
                console.error('削除通信エラー');
                showNotification('削除通信でエラーが発生しました', 'warning');
            }
        });
    });
    
    $(document).on('click', '.delete-item-btn', function() {
        const $button = $(this);
        const itemName = $button.closest('.item-element').find('.item-name').text();
        
        if (!confirm(`項目「${itemName}」を削除しますか？\n関連する進捗データも削除されます。`)) {
            return;
        }
        
        const subject = $button.data('subject');
        const chapter = $button.data('chapter');
        const section = $button.data('section');
        const item = $button.data('item');
        const $itemElement = $button.closest('.item-element');
        
        // 即座に画面から削除
        $itemElement.addClass('deleting').fadeOut(200, function() {
            $(this).remove();
            
            // 項目がなくなった場合のメッセージ表示
            const $itemsContainer = $(`.section-item[data-section="${section}"] .items-container`);
            if ($itemsContainer.find('.item-element').length === 0) {
                $itemsContainer.append('<p style="margin: 10px; color: #666; font-style: italic;">この節には項目がありません。「項追加」ボタンで項目を追加してください。</p>');
            }
        });
        
        // バックグラウンドでサーバーから削除
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_delete_element',
                subject: subject,
                type: 'item',
                chapter: chapter,
                section: section,
                item: item,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    console.log(`項目 "${itemName}" が削除されました`);
                    showNotification('項目を削除しました', 'success');
                } else {
                    console.error('サーバー削除に失敗しました:', response);
                    showNotification('削除処理でエラーが発生しました', 'warning');
                }
            },
            error: function() {
                console.error('削除通信エラー');
                showNotification('削除通信でエラーが発生しました', 'warning');
            }
        });
    });
    
    // 7. 要素作成関数（即座反映対応）
    function createChapterElement(subject, chapterId, chapterName, isTemp = false) {
        const tempClass = isTemp ? ' temp-element' : '';
        return `
            <div class="chapter-item instant-add${tempClass}" data-chapter="${chapterId}">
                <div class="chapter-header">
                    <span class="chapter-name editable" 
                          data-type="chapter" 
                          data-subject="${subject}"
                          data-chapter="${chapterId}">
                        ${escapeHtml(chapterName)}
                    </span>
                    <div class="chapter-controls">
                        <button type="button" class="button button-small add-section-btn" 
                                data-subject="${subject}"
                                data-chapter="${chapterId}">
                            節追加
                        </button>
                        <button type="button" class="button button-link-delete button-small delete-chapter-btn"
                                data-subject="${subject}"
                                data-chapter="${chapterId}">
                            削除
                        </button>
                    </div>
                </div>
                <div class="sections-container">
                    <p style="margin: 10px; color: #666; font-style: italic;">この章には節がありません。「節追加」ボタンで節を追加してください。</p>
                </div>
            </div>
        `;
    }
    
    function createSectionElement(subject, chapter, sectionId, sectionName, isTemp = false) {
        const tempClass = isTemp ? ' temp-element' : '';
        return `
            <div class="section-item instant-add${tempClass}" data-section="${sectionId}">
                <div class="section-header">
                    <span class="section-name editable"
                          data-type="section"
                          data-subject="${subject}"
                          data-chapter="${chapter}"
                          data-section="${sectionId}">
                        ${escapeHtml(sectionName)}
                    </span>
                    <div class="section-controls">
                        <button type="button" class="button button-small add-item-btn"
                                data-subject="${subject}"
                                data-chapter="${chapter}"
                                data-section="${sectionId}">
                            項追加
                        </button>
                        <button type="button" class="button button-link-delete button-small delete-section-btn"
                                data-subject="${subject}"
                                data-chapter="${chapter}"
                                data-section="${sectionId}">
                            削除
                        </button>
                    </div>
                </div>
                <div class="items-container">
                    <p style="margin: 10px; color: #666; font-style: italic;">この節には項目がありません。「項追加」ボタンで項目を追加してください。</p>
                </div>
            </div>
        `;
    }
    
    function createItemElement(subject, chapter, section, itemId, itemName, isTemp = false) {
        const tempClass = isTemp ? ' temp-element' : '';
        return `
            <div class="item-element instant-add${tempClass}" data-item="${itemId}">
                <span class="item-name editable"
                      data-type="item"
                      data-subject="${subject}"
                      data-chapter="${chapter}"
                      data-section="${section}"
                      data-item="${itemId}">
                    ${escapeHtml(itemName)}
                </span>
                <button type="button" class="button button-link-delete button-tiny delete-item-btn"
                        data-subject="${subject}"
                        data-chapter="${chapter}"
                        data-section="${section}"
                        data-item="${itemId}">
                    ×
                </button>
            </div>
        `;
    }
    
    // 8. ヘルパー関数
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function showNotification(message, type) {
        $('.spt-admin-notification').remove();
        
        const colors = {
            error: '#dc3232',
            warning: '#ffb900',
            success: '#46b450',
            info: '#0073aa'
        };
        
        const bgColor = colors[type] || colors.info;
        
        const $notification = $('<div class="spt-admin-notification" style="' +
            'position: fixed; top: 32px; right: 20px; background: ' + bgColor + '; ' +
            'color: white; padding: 12px 20px; border-radius: 6px; ' +
            'box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 100000; ' +
            'max-width: 300px; font-size: 14px; opacity: 0; transform: translateX(100%);">' + message + '</div>');
        
        $('body').append($notification);
        
        // 即座にスライドイン
        $notification.animate({
            opacity: 1,
            transform: 'translateX(0)'
        }, 150);
        
        setTimeout(function() {
            $notification.animate({
                opacity: 0,
                transform: 'translateX(100%)'
            }, 150, function() {
                $(this).remove();
            });
        }, 2500);
    }
    
    // 9. 高速アニメーション用CSS追加
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .instant-add {
                animation: instantFadeIn 0.15s ease-out;
            }
            
            @keyframes instantFadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .editing {
                background-color: #fff8dc !important;
                border-radius: 3px !important;
                transition: all 0.1s ease;
            }
            
            .saving {
                background-color: #e3f2fd !important;
                position: relative;
            }
            
            .saving::after {
                content: '💾';
                position: absolute;
                right: -25px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 12px;
            }
            
            .saved {
                background-color: #e8f5e9 !important;
                position: relative;
            }
            
            .saved::after {
                content: '✅';
                position: absolute;
                right: -25px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 12px;
            }
            
            .save-error {
                background-color: #ffebee !important;
                position: relative;
            }
            
            .save-error::after {
                content: '❌';
                position: absolute;
                right: -25px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 12px;
            }
            
            .deleting {
                opacity: 0.5;
                transform: scale(0.95);
                transition: all 0.2s ease;
            }
            
            .temp-element {
                border-left: 3px solid #2196F3;
            }
            
            /* ボタンホバー効果を高速化 */
            .button {
                transition: all 0.1s ease !important;
            }
            
            .button:hover {
                transform: translateY(-1px);
            }
            
            .button:active {
                transform: translateY(0);
            }
        `)
        .appendTo('head');
    
    console.log('即座反映システム初期化完了');
});