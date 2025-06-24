// 行政書士の道 - 管理画面JavaScript

jQuery(document).ready(function($) {
    
    // 管理画面の初期化
    console.log('管理画面JavaScript読み込み完了');
    
    // ショートコードコピー機能
    $('.copy-shortcode').on('click', function() {
        const btn = $(this);
        const code = btn.siblings('.shortcode-code').text();
        
        // クリップボードにコピー
        if (navigator.clipboard) {
            navigator.clipboard.writeText(code).then(() => {
                showCopyFeedback(btn);
            }).catch(() => {
                fallbackCopyText(code, btn);
            });
        } else {
            fallbackCopyText(code, btn);
        }
    });
    
    function showCopyFeedback(btn) {
        const originalText = btn.text();
        btn.text('コピー完了！').addClass('copied');
        
        setTimeout(() => {
            btn.text(originalText).removeClass('copied');
        }, 2000);
    }
    
    function fallbackCopyText(text, btn) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        
        try {
            document.execCommand('copy');
            showCopyFeedback(btn);
        } catch (err) {
            console.error('コピーに失敗しました:', err);
            alert('コピーに失敗しました。手動でコピーしてください。');
        }
        
        document.body.removeChild(textArea);
    }
    
    // タブ機能
    $('.gyosei-tab-item').on('click', function(e) {
        e.preventDefault();
        
        const tabItem = $(this);
        const targetTab = tabItem.find('.gyosei-tab-link').attr('href');
        
        // アクティブタブの切り替え
        tabItem.siblings().removeClass('active');
        tabItem.addClass('active');
        
        // コンテンツの切り替え
        $('.gyosei-tab-content').removeClass('active');
        $(targetTab).addClass('active');
    });
    
    // 通知の自動非表示
    $('.gyosei-notice-dismiss').on('click', function() {
        $(this).closest('.gyosei-notice').fadeOut();
    });
    
    // 統計カードのアニメーション
    $('.gyosei-stat-number').each(function() {
        const $this = $(this);
        const finalValue = parseInt($this.text().replace(/,/g, ''));
        
        if (!isNaN(finalValue)) {
            $this.text('0');
            
            $({ counter: 0 }).animate({ counter: finalValue }, {
                duration: 2000,
                step: function() {
                    $this.text(Math.ceil(this.counter).toLocaleString());
                },
                complete: function() {
                    $this.text(finalValue.toLocaleString());
                }
            });
        }
    });
    
    // カラーピッカーの初期化
    if ($.fn.wpColorPicker) {
        $('.color-picker').wpColorPicker();
    }
    
    // フォームの検証
    $('.gyosei-form').on('submit', function(e) {
        const form = $(this);
        let isValid = true;
        
        // 必須フィールドの検証
        form.find('[required]').each(function() {
            const field = $(this);
            if (!field.val().trim()) {
                field.addClass('error');
                isValid = false;
            } else {
                field.removeClass('error');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showNotice('必須フィールドを入力してください。', 'error');
        }
    });
    
    // 動的通知表示
    function showNotice(message, type = 'info') {
        const notice = $(`
            <div class="gyosei-notice gyosei-notice-${type}">
                ${message}
                <button class="gyosei-notice-dismiss">×</button>
            </div>
        `);
        
        $('.gyosei-admin-container').prepend(notice);
        
        // 自動非表示
        setTimeout(() => {
            notice.fadeOut(() => notice.remove());
        }, 5000);
        
        // 手動非表示
        notice.find('.gyosei-notice-dismiss').on('click', () => {
            notice.fadeOut(() => notice.remove());
        });
    }
    
    // Ajax処理の共通設定
    $.ajaxSetup({
        beforeSend: function() {
            $('.gyosei-loading').show();
        },
        complete: function() {
            $('.gyosei-loading').hide();
        },
        error: function(xhr, status, error) {
            console.error('Ajax Error:', error);
            showNotice('通信エラーが発生しました。', 'error');
        }
    });
    
    // 削除確認ダイアログ
    $('.btn-delete').on('click', function(e) {
        if (!confirm('本当に削除しますか？この操作は取り消せません。')) {
            e.preventDefault();
        }
    });
    
    // ツールチップ
    $('[title]').each(function() {
        const $this = $(this);
        const title = $this.attr('title');
        
        $this.removeAttr('title').on('mouseenter', function(e) {
            const tooltip = $(`<div class="admin-tooltip">${title}</div>`);
            $('body').append(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.css({
                position: 'fixed',
                top: rect.bottom + 5,
                left: rect.left + (rect.width / 2) - (tooltip.outerWidth() / 2),
                zIndex: 9999
            });
        }).on('mouseleave', function() {
            $('.admin-tooltip').remove();
        });
    });
    
    // データテーブルの並び替え
    $('.gyosei-mindmap-table th[data-sort]').on('click', function() {
        const th = $(this);
        const table = th.closest('table');
        const column = th.data('sort');
        const currentOrder = th.data('order') || 'asc';
        const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
        
        // ソート方向の更新
        table.find('th').removeData('order').removeClass('sorted-asc sorted-desc');
        th.data('order', newOrder).addClass(`sorted-${newOrder}`);
        
        // 行をソート
        const rows = table.find('tbody tr').toArray();
        const columnIndex = th.index();
        
        rows.sort((a, b) => {
            const aValue = $(a).find('td').eq(columnIndex).text().trim();
            const bValue = $(b).find('td').eq(columnIndex).text().trim();
            
            // 数値かどうかチェック
            const aNum = parseFloat(aValue);
            const bNum = parseFloat(bValue);
            
            if (!isNaN(aNum) && !isNaN(bNum)) {
                return newOrder === 'asc' ? aNum - bNum : bNum - aNum;
            } else {
                return newOrder === 'asc' 
                    ? aValue.localeCompare(bValue)
                    : bValue.localeCompare(aValue);
            }
        });
        
        table.find('tbody').html(rows);
    });
    
    // フィルター機能
    $('.gyosei-filter-input').on('input', function() {
        const input = $(this);
        const table = input.closest('.gyosei-filters').next('.gyosei-mindmap-table');
        const filterValue = input.val().toLowerCase();
        
        table.find('tbody tr').each(function() {
            const row = $(this);
            const text = row.text().toLowerCase();
            
            if (text.includes(filterValue)) {
                row.show();
            } else {
                row.hide();
            }
        });
    });
    
    // JSON エディタ用バリデーション
    $('.json-editor-textarea').on('input', function() {
        const textarea = $(this);
        const validator = textarea.siblings('.json-validator');
        
        try {
            JSON.parse(textarea.val());
            validator.removeClass('json-invalid').addClass('json-valid')
                    .text('✓ 有効なJSONです');
        } catch (error) {
            validator.removeClass('json-valid').addClass('json-invalid')
                    .text('✗ JSONエラー: ' + error.message);
        }
    });
    
    // プレビュー機能
    $('.preview-btn').on('click', function() {
        const btn = $(this);
        const formData = btn.closest('form').serialize();
        
        // プレビューウィンドウを開く
        const previewWindow = window.open('', 'preview', 'width=800,height=600');
        previewWindow.document.write('<html><head><title>プレビュー</title></head><body><p>プレビューを読み込み中...</p></body></html>');
        
        $.post(mindmapAdminData.ajaxurl, formData + '&action=preview_mindmap&nonce=' + mindmapAdminData.nonce)
            .done(function(response) {
                if (response.success) {
                    previewWindow.document.body.innerHTML = response.data;
                } else {
                    previewWindow.document.body.innerHTML = '<p>プレビューの生成に失敗しました。</p>';
                }
            })
            .fail(function() {
                previewWindow.document.body.innerHTML = '<p>通信エラーが発生しました。</p>';
            });
    });
    
    // 設定の自動保存
    $('.auto-save').on('change', function() {
        const field = $(this);
        const data = {
            action: 'save_setting',
            key: field.attr('name'),
            value: field.val(),
            nonce: mindmapAdminData.nonce
        };
        
        $.post(mindmapAdminData.ajaxurl, data)
            .done(function(response) {
                if (response.success) {
                    field.addClass('saved');
                    setTimeout(() => field.removeClass('saved'), 1000);
                }
            });
    });
    
    // ヘルプセクションの展開/折りたたみ
    $('.help-toggle').on('click', function() {
        const toggle = $(this);
        const content = toggle.next('.help-content');
        
        content.slideToggle();
        toggle.text(content.is(':visible') ? '▼ 閉じる' : '▶ 詳細を見る');
    });
    
    // バックアップ・復元機能
    $('#backup-settings').on('click', function() {
        const settings = {};
        
        $('.setting-field').each(function() {
            const field = $(this);
            settings[field.attr('name')] = field.val();
        });
        
        const dataStr = JSON.stringify(settings, null, 2);
        const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
        
        const exportFileDefaultName = 'mindmap-settings-' + new Date().toISOString().split('T')[0] + '.json';
        
        const linkElement = document.createElement('a');
        linkElement.setAttribute('href', dataUri);
        linkElement.setAttribute('download', exportFileDefaultName);
        linkElement.click();
    });
    
    $('#restore-settings').on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const settings = JSON.parse(e.target.result);
                
                Object.keys(settings).forEach(key => {
                    const field = $(`[name="${key}"]`);
                    if (field.length) {
                        field.val(settings[key]);
                    }
                });
                
                showNotice('設定を復元しました。', 'success');
            } catch (error) {
                showNotice('設定ファイルが無効です。', 'error');
            }
        };
        reader.readAsText(file);
    });
    
    // 統計データの更新
    function updateStats() {
        $.post(mindmapAdminData.ajaxurl, {
            action: 'get_admin_stats',
            nonce: mindmapAdminData.nonce
        }).done(function(response) {
            if (response.success) {
                Object.keys(response.data).forEach(key => {
                    $(`.stat-${key}`).text(response.data[key].toLocaleString());
                });
            }
        });
    }
    
    // 5分ごとに統計を更新
    setInterval(updateStats, 5 * 60 * 1000);
    
    console.log('管理画面JavaScript初期化完了');
});

// 管理画面用CSS追加
jQuery(document).ready(function($) {
    $('<style>').text(`
        .admin-tooltip {
            background: #333;
            color: white;
            padding: 5px 8px;
            border-radius: 3px;
            font-size: 12px;
            pointer-events: none;
        }
        
        .gyosei-mindmap-table th[data-sort] {
            cursor: pointer;
            position: relative;
        }
        
        .gyosei-mindmap-table th[data-sort]:hover {
            background: #e9ecef;
        }
        
        .gyosei-mindmap-table th.sorted-asc::after {
            content: '▲';
            position: absolute;
            right: 5px;
            font-size: 10px;
        }
        
        .gyosei-mindmap-table th.sorted-desc::after {
            content: '▼';
            position: absolute;
            right: 5px;
            font-size: 10px;
        }
        
        .setting-field.saved {
            background: #d4edda !important;
            transition: background 0.3s ease;
        }
        
        .error {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        
        .gyosei-loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .gyosei-loading::after {
            content: "";
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3f51b5;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `).appendTo('head');
});