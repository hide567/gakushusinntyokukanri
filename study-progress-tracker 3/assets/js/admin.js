/**
 * 学習進捗管理の管理画面用JavaScript（完全修正版）
 *
 * @package StudyProgressTracker
 */
jQuery(document).ready(function($) {
    
    // 初期化フラグ
    let isInitialized = false;
    
    // イベントの重複を防ぐため、すべてのイベントを一度クリア
    $(document).off('.spt-admin');
    
    /**
     * 管理画面用ノンス取得（完全修正版）
     */
    function getAdminNonce() {
        // 1. spt_admin_dataから取得（最優先）
        if (typeof spt_admin_data !== 'undefined') {
            if (spt_admin_data.nonce) {
                console.log('Nonce found in spt_admin_data.nonce:', spt_admin_data.nonce);
                return spt_admin_data.nonce;
            }
            if (spt_admin_data.admin_nonce) {
                console.log('Nonce found in spt_admin_data.admin_nonce:', spt_admin_data.admin_nonce);
                return spt_admin_data.admin_nonce;
            }
            if (spt_admin_data.page_nonce) {
                console.log('Nonce found in spt_admin_data.page_nonce:', spt_admin_data.page_nonce);
                return spt_admin_data.page_nonce;
            }
        }
        
        // 2. フォーム内の_wpnonceフィールドから取得
        var $nonceInput = $('input[name="_wpnonce"]:first');
        if ($nonceInput.length && $nonceInput.val()) {
            console.log('Nonce found in form:', $nonceInput.val());
            return $nonceInput.val();
        }
        
        // 3. WordPressグローバル変数から取得
        if (typeof wpAjax !== 'undefined' && wpAjax.nonce) {
            console.log('Nonce found in wpAjax:', wpAjax.nonce);
            return wpAjax.nonce;
        }
        
        console.error('Nonce not found! Available data:', {
            spt_admin_data: typeof spt_admin_data !== 'undefined' ? Object.keys(spt_admin_data) : 'undefined',
            nonce_inputs: $('input[name="_wpnonce"]').length,
            wpAjax: typeof wpAjax !== 'undefined' ? 'available' : 'undefined'
        });
        return '';
    }
    
    /**
     * 管理画面用色設定取得
     */
    function getAdminFirstCheckColor() {
        if (typeof spt_admin_data !== 'undefined' && spt_admin_data.first_check_color) {
            return spt_admin_data.first_check_color;
        }
        return $('input[name="first_check_color"]').val() || '#e6f7e6';
    }

    function getAdminSecondCheckColor() {
        if (typeof spt_admin_data !== 'undefined' && spt_admin_data.second_check_color) {
            return spt_admin_data.second_check_color;
        }
        return $('input[name="second_check_color"]').val() || '#ffebcc';
    }
    
    /**
     * リアルタイム進捗保存（管理画面用・完全修正版）
     */
    function saveProgressRealtime($itemRow, $firstCheck, $secondCheck) {
        var itemData = extractItemData($itemRow);
        if (!itemData) {
            console.error('項目データの取得に失敗しました');
            return;
        }
        
        // チェックレベルを決定
        var checkLevel = 0;
        if ($firstCheck.prop('checked')) checkLevel = 1;
        if ($secondCheck.prop('checked')) checkLevel = 2;
        
        // 保存インジケーターを表示
        showAdminSavingIndicator(true);
        $itemRow.css('opacity', 0.7);
        
        // Ajax URLを確実に取得
        var ajaxUrl = '/wp-admin/admin-ajax.php';
        if (typeof spt_admin_data !== 'undefined' && spt_admin_data.ajax_url) {
            ajaxUrl = spt_admin_data.ajax_url;
        } else if (typeof ajaxurl !== 'undefined') {
            ajaxUrl = ajaxurl;
        }
        
        var nonce = getAdminNonce();
        
        if (!nonce) {
            console.error('Nonce取得に失敗しました');
            $itemRow.css('opacity', 1);
            showAdminSavingIndicator(false);
            
            if (confirm('セキュリティトークンの取得に失敗しました。ページを再読み込みしますか？')) {
                location.reload();
            }
            return;
        }
        
        var data = {
            action: 'progress_tracker_toggle_item_completion',
            subject: itemData.subject,
            chapter: itemData.chapter,
            section: itemData.section,
            item: itemData.item,
            check_level: checkLevel,
            completed: checkLevel > 0,
            nonce: nonce
        };
        
        console.log('管理画面Ajax送信データ:', data);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: data,
            timeout: 15000,
            cache: false,
            dataType: 'json',
            success: function(response) {
                $itemRow.css('opacity', 1);
                showAdminSavingIndicator(false);
                
                console.log('管理画面Ajax応答:', response);
                
                if (response && response.success) {
                    // 進捗率の更新
                    if (response.data && typeof response.data.percent !== 'undefined') {
                        var $subjectProgress = $itemRow.closest('.subject-progress');
                        if ($subjectProgress.length) {
                            $subjectProgress.find('.percent-display').text('(' + response.data.percent + '%完了)');
                            $subjectProgress.find('.progress-bar-fill').css('width', response.data.percent + '%');
                            
                            // 親コンテナのスタイル更新
                            updateParentContainersAdmin(response.data, $itemRow);
                        }
                    }
                    
                    // 成功フィードバック
                    showSaveSuccessAdmin($itemRow);
                } else {
                    console.error('管理画面保存エラー:', response?.data || 'Unknown error');
                    
                    var errorMessage = '保存に失敗しました';
                    if (response && response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                    
                    if (confirm(errorMessage + '\nページを再読み込みしますか？')) {
                        location.reload();
                    } else {
                        revertCheckboxStateAdmin($itemRow, $firstCheck, $secondCheck);
                    }
                }
            },
            error: function(xhr, status, error) {
                $itemRow.css('opacity', 1);
                showAdminSavingIndicator(false);
                console.error('管理画面Ajax error:', status, error, xhr.responseText);
                
                var errorMsg = '通信エラーが発生しました';
                if (status === 'timeout') {
                    errorMsg = '保存がタイムアウトしました。ネットワーク接続を確認してください。';
                } else if (status === 'error' && xhr.status === 0) {
                    errorMsg = 'ネットワーク接続に問題があります。';
                } else if (xhr.status === 403) {
                    errorMsg = 'セキュリティエラーが発生しました。';
                } else if (xhr.status === 500) {
                    errorMsg = 'サーバーエラーが発生しました。';
                }
                
                if (confirm(errorMsg + '\nページを再読み込みしますか？')) {
                    location.reload();
                } else {
                    revertCheckboxStateAdmin($itemRow, $firstCheck, $secondCheck);
                }
            }
        });
    }
    
    /**
     * 理解・習得チェックボックスの相互制御とリアルタイム保存（完全修正版）
     */
    $(document).on('change.spt-admin touchend.spt-admin', 'input[type="checkbox"]:not([name*="_second"])', function(e) {
        e.stopPropagation();
        
        var $this = $(this);
        var $secondCheck = $this.closest('.item-checkboxes').find('input[name*="_second"]');
        var $itemRow = $this.closest('.item-row');
        
        // 重複処理防止
        if ($itemRow.hasClass('processing')) {
            return false;
        }
        $itemRow.addClass('processing');
        
        // 理解のチェックを外した場合、習得も外す
        if (!$this.prop('checked')) {
            $secondCheck.prop('checked', false);
        }
        
        updateItemStyle($itemRow);
        
        // 遅延実行で安定化
        setTimeout(function() {
            saveProgressRealtime($itemRow, $this, $secondCheck);
            $itemRow.removeClass('processing');
        }, 200);
    });
    
    $(document).on('change.spt-admin touchend.spt-admin', 'input[type="checkbox"][name*="_second"]', function(e) {
        e.stopPropagation();
        
        var $this = $(this);
        var $firstCheck = $this.closest('.item-checkboxes').find('input[type="checkbox"]:not([name*="_second"])');
        var $itemRow = $this.closest('.item-row');
        
        // 重複処理防止
        if ($itemRow.hasClass('processing')) {
            return false;
        }
        $itemRow.addClass('processing');
        
        // 習得をチェックした場合、理解も自動的にチェック
        if ($this.prop('checked')) {
            $firstCheck.prop('checked', true);
        }
        
        updateItemStyle($itemRow);
        
        // 遅延実行で安定化
        setTimeout(function() {
            saveProgressRealtime($itemRow, $firstCheck, $this);
            $itemRow.removeClass('processing');
        }, 200);
    });
    
    /**
     * 項目データを抽出
     */
    function extractItemData($itemRow) {
        var $firstCheckbox = $itemRow.find('input[type="checkbox"]:not([name*="_second"])').first();
        if ($firstCheckbox.length === 0) {
            return null;
        }
        
        var fieldName = $firstCheckbox.attr('name');
        var matches = fieldName.match(/(.+)_chapter_(\d+)_section_(\d+)(?:_item_(\d+))?$/);
        
        if (matches) {
            return {
                subject: matches[1],
                chapter: parseInt(matches[2]),
                section: parseInt(matches[3]),
                item: matches[4] ? parseInt(matches[4]) : 0
            };
        }
        
        return null;
    }
    
    /**
     * 管理画面用保存インジケーター
     */
    function showAdminSavingIndicator(show) {
        var $indicator = $('.admin-saving-indicator');
        
        if ($indicator.length === 0) {
            $indicator = $('<div class="admin-saving-indicator" style="position: fixed; top: 32px; right: 20px; background-color: #0073aa; color: white; padding: 8px 15px; border-radius: 3px; z-index: 99999; display: none; font-size: 13px; box-shadow: 0 4px 8px rgba(0,0,0,0.3);">保存中...</div>');
            $('body').append($indicator);
        }
        
        if (show) {
            $indicator.fadeIn(200);
        } else {
            $indicator.fadeOut(200);
        }
    }
    
    /**
     * 管理画面用保存成功フィードバック
     */
    function showSaveSuccessAdmin($itemRow) {
        var originalBg = $itemRow.css('background-color');
        $itemRow.css('background-color', '#90EE90');
        
        setTimeout(function() {
            $itemRow.css('background-color', originalBg);
        }, 800);
    }
    
    /**
     * チェックボックス状態を元に戻す
     */
    function revertCheckboxStateAdmin($itemRow, $firstCheck, $secondCheck) {
        // 状態を元に戻す
        $firstCheck.prop('checked', !$firstCheck.prop('checked'));
        $secondCheck.prop('checked', !$secondCheck.prop('checked'));
        updateItemStyle($itemRow);
    }
    
    /**
     * 親コンテナの更新（管理画面用）
     */
    function updateParentContainersAdmin(data, $itemRow) {
        var $chapterItem = $itemRow.closest('.chapter-accordion-item');
        var $sectionItem = $itemRow.closest('.section-item');
        
        // 章の状態を更新
        if ($chapterItem.length) {
            var $chapterHeader = $chapterItem.find('.chapter-accordion-header');
            $chapterHeader.css('background-color', '');
            
            if (data.chapter_mastered) {
                $chapterHeader.css('background-color', getAdminSecondCheckColor());
            } else if (data.chapter_completed) {
                $chapterHeader.css('background-color', getAdminFirstCheckColor());
            }
        }
        
        // 節の状態を更新
        if ($sectionItem.length) {
            var $sectionHeader = $sectionItem.find('.section-header');
            $sectionHeader.css('background-color', '');
            
            if (data.section_mastered) {
                $sectionHeader.css('background-color', getAdminSecondCheckColor());
            } else if (data.section_completed) {
                $sectionHeader.css('background-color', getAdminFirstCheckColor());
            }
        }
    }
    
    /**
     * 項目のスタイルを更新（即座に色を反映）
     */
    function updateItemStyle($item) {
        var $firstCheck = $item.find('input[type="checkbox"]:not([name*="_second"])');
        var $secondCheck = $item.find('input[type="checkbox"][name*="_second"]');
        
        $item.css('background-color', '');
        $item.removeClass('checked mastered');
        
        // リアルタイムで色を反映
        if ($secondCheck.prop('checked')) {
            $item.addClass('mastered');
            $item.css('background-color', getAdminSecondCheckColor());
        } else if ($firstCheck.prop('checked')) {
            $item.addClass('checked');
            $item.css('background-color', getAdminFirstCheckColor());
        }
    }
    
    /**
     * 進捗リセットボタンの処理
     */
    $(document).on('click.spt-admin', '.reset-progress-button', function(e) {
        e.preventDefault();
        $('.reset-confirmation').slideDown();
    });
    
    $(document).on('click.spt-admin', '.cancel-reset', function(e) {
        e.preventDefault();
        $('.reset-confirmation').slideUp();
        $('input[name="confirm_reset"]').prop('checked', false);
    });
    
    /**
     * 色設定パネルの表示/非表示
     */
    $(document).on('click.spt-admin', '.color-settings-toggle', function(e) {
        e.preventDefault();
        $('.color-settings-panel').slideToggle();
    });
    
    /**
     * 科目管理タブでの編集・削除処理
     */
    $(document).on('click.spt-admin', '.edit-subject', function(e) {
        e.preventDefault();
        var key = $(this).data('key');
        var name = $(this).data('name');
        var color = $(this).data('color');
        
        $('#edit_subject_key').val(key);
        $('#edit_subject_name').val(name);
        $('#edit_progress_color').val(color);
        
        $('#edit-subject-modal').show();
    });
    
    $(document).on('click.spt-admin', '.close-modal', function(e) {
        e.preventDefault();
        $('#edit-subject-modal').hide();
    });
    
    // モーダル外クリックで閉じる
    $(document).on('click.spt-admin', '#edit-subject-modal', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    /**
     * 科目管理（科目追加のアコーディオン）
     */
    $(document).on('click.spt-admin', '.subject-add-header', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $content = $('.subject-add-content');
        var $icon = $('.subject-add-toggle-icon');
        
        if ($content.is(':visible')) {
            $content.slideUp(200);
            $icon.text('+');
        } else {
            $content.slideDown(200);
            $icon.text('-');
        }
        
        return false;
    });
    
    /**
     * 科目構造設定 - 科目のアコーディオン
     */
    $(document).on('click.spt-admin', '.subject-header', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $content = $(this).siblings('.subject-content');
        var $icon = $(this).find('.subject-toggle-icon');
        
        if ($content.is(':visible')) {
            $content.slideUp(200);
            $icon.text('+');
        } else {
            $content.slideDown(200);
            $icon.text('-');
            
            // 最初の章タブをアクティブにする
            setTimeout(function() {
                $content.find('.chapter-tab').first().trigger('click');
            }, 250);
        }
        
        return false;
    });
    
    /**
     * 進捗管理タブでの章アコーディオン（修正版）
     */
    $(document).on('click.spt-admin', '.chapter-accordion-header:not(.subject-header):not(.subject-add-header)', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // タブクリックでないことを確認
        if ($(e.target).closest('.progress-tab').length > 0 || 
            $(e.target).closest('.chapter-tab').length > 0 ||
            $(e.target).closest('.section-tab').length > 0) {
            return false;
        }
        
        var $content = $(this).siblings('.chapter-accordion-content');
        var $icon = $(this).find('.chapter-toggle-icon');
        
        if ($content.is(':visible')) {
            $content.slideUp(200);
            $icon.text('+');
        } else {
            $content.slideDown(200);
            $icon.text('-');
        }
        
        return false;
    });
    
    /**
     * 進捗管理タブでのタブ切り替え（重複回避・バグ修正版）
     */
    $(document).on('click.spt-admin', '.progress-tab:not(.active)', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // 既にアクティブな場合は何もしない
        if ($(this).hasClass('active')) {
            return false;
        }
        
        var subject = $(this).data('subject');
        
        if (!subject) {
            return false;
        }
        
        console.log('タブ切り替え実行:', subject);
        
        // タブの切り替え
        $('.progress-tab').removeClass('active');
        $(this).addClass('active');
        
        // コンテンツの切り替え
        $('.subject-progress').hide();
        $('.subject-progress[data-subject="' + subject + '"]').show();
        
        // ローカルストレージに保存
        try {
            localStorage.setItem('spt_admin_activeTab', subject);
        } catch (e) {
            console.warn('ローカルストレージへの保存に失敗:', e);
        }
        
        return false;
    });
    
    /**
     * 章タブのクリックイベント（科目構造設定用）
     */
    $(document).on('click.spt-admin', '.chapter-tab', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $subjectSection = $(this).closest('.subject-section');
        var subject = $subjectSection.data('subject-key');
        var chapterNum = $(this).data('chapter');
        
        if (!subject || !chapterNum) {
            return false;
        }
        
        // 同じ科目内の他のタブを非アクティブにする
        $subjectSection.find('.chapter-tab').removeClass('active');
        $(this).addClass('active');
        
        // 章の詳細表示切り替え
        $subjectSection.find('.chapter-details').hide();
        $('#chapter-' + subject + '-' + chapterNum + '-details').show();
        
        // 最初の節タブをアクティブにする
        var $firstSectionTab = $('#chapter-' + subject + '-' + chapterNum + '-details .section-tab').first();
        if ($firstSectionTab.length) {
            $firstSectionTab.trigger('click');
        }
        
        // ローカルストレージに状態を保存
        try {
            localStorage.setItem('spt_admin_activeChapter_' + subject, chapterNum);
        } catch (e) {
            console.warn('ローカルストレージへの保存に失敗:', e);
        }
        
        return false;
    });
    
    /**
     * 節タブのクリックイベント（科目構造設定用）
     */
    $(document).on('click.spt-admin', '.section-tab', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var chapterNum = $(this).data('chapter');
        var sectionNum = $(this).data('section');
        var $chapterDetails = $(this).closest('.chapter-details');
        var subject = $(this).closest('.subject-section').data('subject-key');
        
        if (!chapterNum || !sectionNum) {
            return false;
        }
        
        // 同じ章内の他のタブを非アクティブにする
        $chapterDetails.find('.section-tab').removeClass('active');
        $(this).addClass('active');
        
        // 節詳細の表示切り替え
        $chapterDetails.find('.section-details').hide();
        $('#section-' + subject + '-' + chapterNum + '-' + sectionNum + '-details').show();
        
        // ローカルストレージに状態を保存
        try {
            localStorage.setItem('spt_admin_activeSection_' + subject + '_' + chapterNum, sectionNum);
        } catch (e) {
            console.warn('ローカルストレージへの保存に失敗:', e);
        }
        
        return false;
    });
    
    /**
     * 節数入力欄の変更イベント（科目構造設定用）
     */
    $(document).on('change.spt-admin', '.section-count', function(e) {
        var newCount = parseInt($(this).val());
        var chapterNum = $(this).data('chapter');
        var subject = $(this).data('subject');
        
        if (isNaN(newCount) || newCount < 1 || !chapterNum || !subject) {
            return;
        }
        
        // 現在の節タブの数を取得
        var $chapterDetails = $(this).closest('.chapter-details');
        var $sectionTabs = $chapterDetails.find('.section-tabs');
        var currentCount = $sectionTabs.find('.section-tab').length;
        
        if (newCount > currentCount) {
            // 節を追加
            for (var i = currentCount + 1; i <= newCount; i++) {
                // 新しい節タブを追加
                $sectionTabs.append(
                    '<div class="section-tab" data-section="' + i + '" data-chapter="' + chapterNum + '">' +
                    i + '. 節' + i +
                    '</div>'
                );
                
                // 新しい節詳細セクションを追加
                var $sectionContainer = $chapterDetails.find('.section-container .sections-detail-container');
                var sectionHtml = createSectionDetailsHtml(subject, chapterNum, i);
                $sectionContainer.append(sectionHtml);
            }
        } else if (newCount < currentCount) {
            // 節を削除
            for (var i = currentCount; i > newCount; i--) {
                $sectionTabs.find('.section-tab[data-section="' + i + '"]').remove();
                $('#section-' + subject + '-' + chapterNum + '-' + i + '-details').remove();
            }
        }
        
        // 最初の節タブをアクティブにする
        if ($sectionTabs.find('.section-tab').length > 0) {
            $sectionTabs.find('.section-tab').first().trigger('click');
        }
    });
    
    /**
     * 項の数の変更イベント（科目構造設定用）
     */
    $(document).on('change.spt-admin', '.item-count', function(e) {
        var newCount = parseInt($(this).val());
        
        if (isNaN(newCount) || newCount < 1) {
            return;
        }
        
        var $itemsContainer = $(this).closest('.section-details').find('.items-container tbody');
        var currentCount = $itemsContainer.find('tr').length;
        
        // 項のname属性から情報を取得
        var name = $(this).attr('name');
        var matches = name.match(/(.+)_chapter_(\d+)_section_(\d+)_items/);
        
        if (matches) {
            var subject = matches[1];
            var chapterNum = matches[2];
            var sectionNum = matches[3];
            
            if (newCount > currentCount) {
                // 項を追加
                for (var i = currentCount + 1; i <= newCount; i++) {
                    $itemsContainer.append(createItemRowHtml(subject, chapterNum, sectionNum, i));
                }
            } else if (newCount < currentCount) {
                // 項を削除
                for (var i = currentCount; i > newCount; i--) {
                    $itemsContainer.find('tr').last().remove();
                }
            }
        }
    });
    
    /**
     * 章数の変更イベント（科目構造設定用）
     */
    $(document).on('change.spt-admin', 'input[name$="_chapters"]', function(e) {
        var newCount = parseInt($(this).val());
        var subject = $(this).attr('name').replace('_chapters', '');
        var $subjectSection = $(this).closest('.subject-section');
        var $chapterTabs = $subjectSection.find('.chapter-tabs');
        var currentCount = $chapterTabs.find('.chapter-tab').length;
        
        if (isNaN(newCount) || newCount < 1 || !subject) {
            return;
        }
        
        if (newCount > currentCount) {
            // 章を追加
            for (var i = currentCount + 1; i <= newCount; i++) {
                // 新しい章タブを追加
                $chapterTabs.append(
                    '<div class="chapter-tab" data-chapter="' + i + '">' +
                    i + '. 第' + i + '章' +
                    '</div>'
                );
                
                // 新しい章詳細セクションを追加
                var chapterHtml = createChapterDetailsHtml(subject, i);
                $subjectSection.find('.chapters-detail-container').append(chapterHtml);
            }
        } else if (newCount < currentCount) {
            // 章を削除
            for (var i = currentCount; i > newCount; i--) {
                $chapterTabs.find('.chapter-tab[data-chapter="' + i + '"]').remove();
                $('#chapter-' + subject + '-' + i + '-details').remove();
            }
        }
        
        // 最初の章タブをアクティブにする
        if ($chapterTabs.find('.chapter-tab').length > 0) {
            $chapterTabs.find('.chapter-tab').first().trigger('click');
        }
    });
    
    /**
     * 節詳細HTMLを作成
     */
    function createSectionDetailsHtml(subject, chapterNum, sectionNum) {
        return '<div class="section-details" id="section-' + subject + '-' + chapterNum + '-' + sectionNum + '-details" style="display: none; margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #eee; border-radius: 3px;">' +
            '<h6>節' + sectionNum + 'の詳細設定</h6>' +
            '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">' +
            '<div>' +
            '<label>節タイトル</label>' +
            '<input type="text" name="' + subject + '_chapter_' + chapterNum + '_section_' + sectionNum + '_title" value="節' + sectionNum + '" class="regular-text" style="width: 100%;">' +
            '</div>' +
            '<div>' +
            '<label>項の数</label>' +
            '<input type="number" name="' + subject + '_chapter_' + chapterNum + '_section_' + sectionNum + '_items" value="1" min="1" max="20" class="small-text item-count" style="width: 100%;">' +
            '</div>' +
            '</div>' +
            '<div class="items-container">' +
            '<h6>項の詳細設定</h6>' +
            '<div class="items-table-container" style="overflow-x: auto;">' +
            '<table class="wp-list-table widefat fixed striped" style="min-width: 400px;">' +
            '<thead>' +
            '<tr>' +
            '<th width="15%">項番号</th>' +
            '<th width="85%">項タイトル</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>' +
            '<tr>' +
            '<td>1</td>' +
            '<td>' +
            '<input type="text" name="' + subject + '_chapter_' + chapterNum + '_section_' + sectionNum + '_item_1_title" value="項1" class="regular-text" style="width: 100%;">' +
            '</td>' +
            '</tr>' +
            '</tbody>' +
            '</table>' +
            '</div>' +
            '</div>' +
            '</div>';
    }
    
    /**
     * 項の行HTMLを作成
     */
    function createItemRowHtml(subject, chapterNum, sectionNum, itemNum) {
        return '<tr>' +
            '<td>' + itemNum + '</td>' +
            '<td>' +
            '<input type="text" name="' + subject + '_chapter_' + chapterNum + '_section_' + sectionNum + '_item_' + itemNum + '_title" value="項' + itemNum + '" class="regular-text" style="width: 100%;">' +
            '</td>' +
            '</tr>';
    }
    
    /**
     * 章詳細HTMLを作成
     */
    function createChapterDetailsHtml(subject, chapterNum) {
        return '<div class="chapter-details" id="chapter-' + subject + '-' + chapterNum + '-details" style="display: none; margin-top: 20px; padding: 20px; background: #fafafa; border: 1px solid #e5e5e5; border-radius: 5px;">' +
            '<h5>第' + chapterNum + '章の詳細設定</h5>' +
            '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">' +
            '<div>' +
            '<label>章タイトル</label>' +
            '<input type="text" name="' + subject + '_chapter_' + chapterNum + '" value="第' + chapterNum + '章" class="regular-text" style="width: 100%;">' +
            '</div>' +
            '<div>' +
            '<label>節の数</label>' +
            '<input type="number" name="' + subject + '_sections_' + chapterNum + '" value="1" min="1" max="20" class="small-text section-count" data-chapter="' + chapterNum + '" data-subject="' + subject + '" style="width: 100%;">' +
            '</div>' +
            '</div>' +
            '<div class="section-container">' +
            '<h6>節・項の詳細設定</h6>' +
            '<div class="section-tabs">' +
            '<div class="section-tab" data-section="1" data-chapter="' + chapterNum + '">1. 節1</div>' +
            '</div>' +
            '<div class="sections-detail-container">' +
            createSectionDetailsHtml(subject, chapterNum, 1) +
            '</div>' +
            '</div>' +
            '</div>';
    }
    
    /**
     * 初期表示設定 - ローカルストレージから状態を復元
     */
    function initializeDisplay() {
        if (isInitialized) return;
        isInitialized = true;
        
        console.log('管理画面初期化開始');
        console.log('利用可能なデータ:', {
            spt_admin_data: typeof spt_admin_data !== 'undefined' ? Object.keys(spt_admin_data) : 'undefined',
            ajaxurl: typeof ajaxurl !== 'undefined' ? ajaxurl : 'undefined'
        });
        
        // 進捗管理タブの初期化
        if ($('.progress-tab').length > 0) {
            try {
                var savedTab = localStorage.getItem('spt_admin_activeTab');
                var $targetTab = null;
                
                if (savedTab && $('.progress-tab[data-subject="' + savedTab + '"]').length) {
                    $targetTab = $('.progress-tab[data-subject="' + savedTab + '"]');
                } else {
                    $targetTab = $('.progress-tab').first();
                }
                
                if ($targetTab && $targetTab.length) {
                    // まず全てのタブを非アクティブにする
                    $('.progress-tab').removeClass('active');
                    // 対象タブをアクティブにする
                    $targetTab.addClass('active');
                    
                    // コンテンツの表示
                    var subject = $targetTab.data('subject');
                    $('.subject-progress').hide();
                    $('.subject-progress[data-subject="' + subject + '"]').show();
                    
                    console.log('初期タブ設定完了:', subject);
                }
            } catch (e) {
                console.warn('タブ状態の復元に失敗:', e);
                $('.progress-tab').first().addClass('active');
                $('.subject-progress').first().show();
            }
        }
        
        // 各科目の状態を復元（科目構造設定）
        $('.subject-section').each(function() {
            var subjectKey = $(this).data('subject-key');
            
            if (!subjectKey) return;
            
            try {
                // 保存されていた章を取得
                var savedChapter = localStorage.getItem('spt_admin_activeChapter_' + subjectKey);
                
                // デフォルトは最初の章
                var $chapterTab = savedChapter ? 
                    $(this).find('.chapter-tab[data-chapter="' + savedChapter + '"]') : 
                    $(this).find('.chapter-tab').first();
                
                if ($chapterTab.length) {
                    $chapterTab.trigger('click');
                    
                    // その章の中の保存されていた節を取得
                    var chapterNum = $chapterTab.data('chapter');
                    var savedSection = localStorage.getItem('spt_admin_activeSection_' + subjectKey + '_' + chapterNum);
                    
                    if (savedSection) {
                        var $sectionTab = $(this).find('.section-tab[data-chapter="' + chapterNum + '"][data-section="' + savedSection + '"]');
                        if ($sectionTab.length) {
                            $sectionTab.trigger('click');
                        }
                    }
                }
            } catch (e) {
                console.warn('科目状態の復元に失敗:', e);
                // フォールバック
                $(this).find('.chapter-tab').first().trigger('click');
            }
        });
        
        console.log('管理画面初期化完了');
    }
    
    /**
     * フォーム送信前の確認
     */
    $('form').off('submit.spt-admin').on('submit.spt-admin', function(e) {
        // 構造設定の保存時
        if ($(this).find('input[name="save_structure"]').length) {
            var emptyFields = $(this).find('input[type="text"]:visible').filter(function() {
                return $(this).val().trim() === '';
            });
            
            if (emptyFields.length > 0) {
                if (!confirm('空欄の項目があります。続行しますか？')) {
                    e.preventDefault();
                    return false;
                }
            }
        }
        
        // 進捗リセット時
        if ($(this).find('input[name="reset_progress"]').length) {
            if (!$(this).find('input[name="confirm_reset"]').is(':checked')) {
                alert('リセットを確認するチェックボックスにチェックを入れてください。');
                e.preventDefault();
                return false;
            }
        }
    });
    
    /**
     * エンターキーでのフォーム送信を防止（テキストフィールド）
     */
    $(document).off('keypress.spt-admin').on('keypress.spt-admin', 'input[type="text"], input[type="number"]', function(e) {
        if (e.which == 13) {
            e.preventDefault();
            return false;
        }
    });
    
    /**
     * 初期化実行（遅延実行で確実に）
     */
    setTimeout(function() {
        if ($('.subject-section').length > 0 || $('.progress-tab').length > 0) {
            initializeDisplay();
        }
    }, 500);
    
    console.log('学習進捗管理システム管理画面（完全修正版）が初期化されました');
});