/**
 * 学習進捗管理 - 柔軟構造対応管理画面JavaScript
 * assets/js/admin-flexible.js
 */

jQuery(document).ready(function($) {
    'use strict';
    
    console.log('柔軟構造管理システム開始');
    
    // 基本設定
    const ajaxUrl = spt_admin_data.ajax_url;
    const nonce = spt_admin_data.nonce;
    
    let currentSubject, currentChapter, currentSection;
    
    // 1. 章追加ボタン
    $(document).on('click', '.add-chapter-btn', function() {
        currentSubject = $(this).data('subject');
        $('#add-chapter-modal').show();
        $('#new-chapter-name').focus();
    });
    
    // 2. 節追加ボタン
    $(document).on('click', '.add-section-btn', function() {
        currentSubject = $(this).data('subject');
        currentChapter = $(this).data('chapter');
        $('#add-section-modal').show();
        $('#new-section-name').focus();
    });
    
    // 3. 項追加ボタン
    $(document).on('click', '.add-item-btn', function() {
        currentSubject = $(this).data('subject');
        currentChapter = $(this).data('chapter');
        currentSection = $(this).data('section');
        $('#add-item-modal').show();
        $('#new-item-name').focus();
    });
    
    // 4. モーダル閉じる
    $(document).on('click', '.spt-modal-close, [id^="cancel-"]', function() {
        $('.spt-modal').hide();
        resetModalData();
    });
    
    // 5. モーダル外クリックで閉じる
    $(document).on('click', '.spt-modal', function(e) {
        if (e.target === this) {
            $(this).hide();
            resetModalData();
        }
    });
    
    // 6. エンターキーでの追加
    $(document).on('keypress', '#new-chapter-name', function(e) {
        if (e.which === 13) {
            $('#confirm-add-chapter').click();
        }
    });
    
    $(document).on('keypress', '#new-section-name', function(e) {
        if (e.which === 13) {
            $('#confirm-add-section').click();
        }
    });
    
    $(document).on('keypress', '#new-item-name', function(e) {
        if (e.which === 13) {
            $('#confirm-add-item').click();
        }
    });
    
    // 7. 章追加の確定
    $('#confirm-add-chapter').on('click', function() {
        const chapterName = $('#new-chapter-name').val().trim();
        
        if (!chapterName) {
            alert('章名を入力してください。');
            return;
        }
        
        $(this).prop('disabled', true).text('追加中...');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_add_chapter',
                subject: currentSubject,
                chapter_name: chapterName,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // 新しい章要素を作成
                    const newChapter = createChapterElement(
                        currentSubject, 
                        response.data.chapter_id, 
                        response.data.chapter_name
                    );
                    
                    // 追加ボタンの前に挿入
                    const $container = $(`.subject-structure-card[data-subject="${currentSubject}"] .chapters-container`);
                    $container.find('.add-chapter-section').before(newChapter);
                    
                    $('#add-chapter-modal').hide();
                    showNotification('章を追加しました', 'success');
                } else {
                    showNotification('章の追加に失敗しました', 'error');
                }
            },
            error: function() {
                showNotification('通信エラーが発生しました', 'error');
            },
            complete: function() {
                $('#confirm-add-chapter').prop('disabled', false).text('追加');
                resetModalData();
            }
        });
    });
    
    // 8. 節追加の確定
    $('#confirm-add-section').on('click', function() {
        const sectionName = $('#new-section-name').val().trim();
        
        if (!sectionName) {
            alert('節名を入力してください。');
            return;
        }
        
        $(this).prop('disabled', true).text('追加中...');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_add_section',
                subject: currentSubject,
                chapter: currentChapter,
                section_name: sectionName,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // 新しい節要素を作成
                    const newSection = createSectionElement(
                        currentSubject,
                        currentChapter,
                        response.data.section_id,
                        response.data.section_name
                    );
                    
                    // 章内の節コンテナに追加
                    const $sectionsContainer = $(`.chapter-item[data-chapter="${currentChapter}"] .sections-container`);
                    $sectionsContainer.append(newSection);
                    
                    $('#add-section-modal').hide();
                    showNotification('節を追加しました', 'success');
                } else {
                    showNotification('節の追加に失敗しました', 'error');
                }
            },
            error: function() {
                showNotification('通信エラーが発生しました', 'error');
            },
            complete: function() {
                $('#confirm-add-section').prop('disabled', false).text('追加');
                resetModalData();
            }
        });
    });
    
    // 9. 項追加の確定
    $('#confirm-add-item').on('click', function() {
        const itemName = $('#new-item-name').val().trim();
        
        if (!itemName) {
            alert('項名を入力してください。');
            return;
        }
        
        $(this).prop('disabled', true).text('追加中...');
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'spt_add_item',
                subject: currentSubject,
                chapter: currentChapter,
                section: currentSection,
                item_name: itemName,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    // 新しい項要素を作成
                    const newItem = createItemElement(
                        currentSubject,
                        currentChapter,
                        currentSection,
                        response.data.item_id,
                        response.data.item_name
                    );
                    
                    // 節内の項コンテナに追加
                    const $itemsContainer = $(`.section-item[data-section="${currentSection}"] .items-container`);
                    $itemsContainer.append(newItem);
                    
                    $('#add-item-modal').hide();
                    showNotification('項を追加しました', 'success');
                } else {
                    showNotification('項の追加に失敗しました', 'error');
                }
            },
            error: function() {
                showNotification('通信エラーが発生しました', 'error');
            },
            complete: function() {
                $('#confirm-add-item').prop('disabled', false).text('追加');
                resetModalData();
            }
        });
    });
    
    // 10. インライン編集機能
    $(document).on('click', '.editable', function() {
        const $element = $(this);
        const currentText = $element.text().trim();
        const type = $element.data('type');
        
        // 既に編集中の場合は無視
        if ($element.find('input').length > 0) {
            return;
        }
        
        // 入力フィールドを作成
        const $input = $('<input type="text" class="edit-input" />');
        $input.val(currentText);
        $input.css({
            'width': '100%',
            'padding': '4px 8px',
            'border': '1px solid #0073aa',
            'border-radius': '3px',
            'font-size': 'inherit',
            'font-family': 'inherit',
            'background': 'white'
        });
        
        $element.html($input);
        $input.focus().select();
        
        // 保存処理
        function saveEdit() {
            const newText = $input.val().trim();
            
            if (!newText) {
                $element.text(currentText);
                return;
            }
            
            if (newText === currentText) {
                $element.text(currentText);
                return;
            }
            
            // サーバーに保存
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
                        $element.text(newText);
                        showNotification('名称を更新しました', 'success');
                    } else {
                        $element.text(currentText);
                        showNotification('更新に失敗しました', 'error');
                    }
                },
                error: function() {
                    $element.text(currentText);
                    showNotification('通信エラーが発生しました', 'error');
                }
            });
        }
        
        // キーイベント
        $input.on('keypress', function(e) {
            if (e.which === 13) { // Enter
                saveEdit();
            }
        });
        
        $input.on('keydown', function(e) {
            if (e.which === 27) { // Escape
                $element.text(currentText);
            }
        });
        
        // フォーカスアウト
        $input.on('blur', function() {
            saveEdit();
        });
    });
    
    // 11. 削除機能
    $(document).on('click', '.delete-chapter-btn', function() {
        if (!confirm('この章を削除しますか？関連する進捗データも削除されます。')) {
            return;
        }
        
        const subject = $(this).data('subject');
        const chapter = $(this).data('chapter');
        const $chapterElement = $(this).closest('.chapter-item');
        
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
                    $chapterElement.fadeOut(300, function() {
                        $(this).remove();
                    });
                    showNotification('章を削除しました', 'success');
                } else {
                    showNotification('削除に失敗しました', 'error');
                }
            },
            error: function() {
                showNotification('通信エラーが発生しました', 'error');
            }
        });
    });
    
    $(document).on('click', '.delete-section-btn', function() {
        if (!confirm('この節を削除しますか？関連する進捗データも削除されます。')) {
            return;
        }
        
        const subject = $(this).data('subject');
        const chapter = $(this).data('chapter');
        const section = $(this).data('section');
        const $sectionElement = $(this).closest('.section-item');
        
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
                    $sectionElement.fadeOut(300, function() {
                        $(this).remove();
                    });
                    showNotification('節を削除しました', 'success');
                } else {
                    showNotification('削除に失敗しました', 'error');
                }
            },
            error: function() {
                showNotification('通信エラーが発生しました', 'error');
            }
        });
    });
    
    $(document).on('click', '.delete-item-btn', function() {
        if (!confirm('この項を削除しますか？関連する進捗データも削除されます。')) {
            return;
        }
        
        const subject = $(this).data('subject');
        const chapter = $(this).data('chapter');
        const section = $(this).data('section');
        const item = $(this).data('item');
        const $itemElement = $(this).closest('.item-element');
        
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
                    $itemElement.fadeOut(300, function() {
                        $(this).remove();
                    });
                    showNotification('項を削除しました', 'success');
                } else {
                    showNotification('削除に失敗しました', 'error');
                }
            },
            error: function() {
                showNotification('通信エラーが発生しました', 'error');
            }
        });
    });
    
    // 12. 要素作成関数
    function createChapterElement(subject, chapterId, chapterName) {
        return `
            <div class="chapter-item" data-chapter="${chapterId}">
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
                <div class="sections-container"></div>
            </div>
        `;
    }
    
    function createSectionElement(subject, chapter, sectionId, sectionName) {
        return `
            <div class="section-item" data-section="${sectionId}">
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
                <div class="items-container"></div>
            </div>
        `;
    }
    
    function createItemElement(subject, chapter, section, itemId, itemName) {
        return `
            <div class="item-element" data-item="${itemId}">
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
    
    // 13. ヘルパー関数
    function resetModalData() {
        currentSubject = null;
        currentChapter = null;
        currentSection = null;
        $('#new-chapter-name, #new-section-name, #new-item-name').val('');
    }
    
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
            'max-width: 300px; font-size: 14px;">' + message + '</div>');
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // 14. ドラッグ&ドロップ並び替え（簡易版）
    function initSortable() {
        // 章の並び替え
        $('.chapters-container').each(function() {
            if (typeof $.fn.sortable === 'function') {
                $(this).sortable({
                    items: '.chapter-item',
                    handle: '.chapter-header',
                    placeholder: 'sortable-placeholder',
                    tolerance: 'pointer',
                    update: function(event, ui) {
                        // 並び順保存（実装が複雑になるため省略）
                        showNotification('並び替え機能は今後実装予定です', 'info');
                    }
                });
            }
        });
    }
    
    // jQuery UI Sortableが利用可能な場合のみ初期化
    if (typeof $.fn.sortable === 'function') {
        initSortable();
    }
    
    console.log('柔軟構造管理システム初期化完了');
});