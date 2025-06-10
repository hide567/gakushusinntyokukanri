/**
 * 学習進捗管理の管理画面用JavaScript（修正版）
 *
 * @package StudyProgressTracker
 */
jQuery(document).ready(function($) {
    
    /**
     * 進捗リセットボタンの処理
     */
    $('.reset-progress-button').on('click', function() {
        $('.reset-confirmation').slideDown();
    });
    
    $('.cancel-reset').on('click', function() {
        $('.reset-confirmation').slideUp();
        $('input[name="confirm_reset"]').prop('checked', false);
    });
    
    /**
     * 色設定パネルの表示/非表示
     */
    $('.color-settings-toggle').on('click', function() {
        $('.color-settings-panel').slideToggle();
    });
    
    /**
     * 科目管理タブでの編集・削除処理
     */
    $('.edit-subject').on('click', function() {
        var key = $(this).data('key');
        var name = $(this).data('name');
        var color = $(this).data('color');
        
        $('#edit_subject_key').val(key);
        $('#edit_subject_name').val(name);
        $('#edit_progress_color').val(color);
        
        $('#edit-subject-modal').show();
    });
    
    $('.close-modal').on('click', function() {
        $('#edit-subject-modal').hide();
    });
    
    // モーダル外クリックで閉じる
    $('#edit-subject-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    /**
     * 進捗管理タブでの章アコーディオン
     */
    $(document).on('click', '.chapter-accordion-header', function() {
        var $content = $(this).siblings('.chapter-accordion-content');
        var $icon = $(this).find('.chapter-toggle-icon');
        
        if ($content.is(':visible')) {
            $content.slideUp(200);
            $icon.text('+');
        } else {
            $content.slideDown(200);
            $icon.text('-');
        }
    });
    
    /**
     * 進捗管理タブでのタブ切り替え
     */
    $('.progress-tab').on('click', function() {
        var subject = $(this).data('subject');
        
        // タブの切り替え
        $('.progress-tab').removeClass('active');
        $(this).addClass('active');
        
        // コンテンツの切り替え
        $('.subject-progress').hide();
        $('.subject-progress[data-subject="' + subject + '"]').show();
    });
    
    /**
     * 章タブのクリックイベント（科目構造設定用）
     */
    $(document).on('click', '.chapter-tab', function() {
        var $subjectSection = $(this).closest('.subject-section');
        var subject = $subjectSection.data('subject-key');
        var chapterNum = $(this).data('chapter');
        
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
        localStorage.setItem('spt_admin_activeChapter_' + subject, chapterNum);
    });
    
    /**
     * 節タブのクリックイベント（科目構造設定用）
     */
    $(document).on('click', '.section-tab', function() {
        var chapterNum = $(this).data('chapter');
        var sectionNum = $(this).data('section');
        var $chapterDetails = $(this).closest('.chapter-details');
        
        // 同じ章内の他のタブを非アクティブにする
        $chapterDetails.find('.section-tab').removeClass('active');
        $(this).addClass('active');
        
        // ローカルストレージに状態を保存
        var subject = $(this).closest('.subject-section').data('subject-key');
        localStorage.setItem('spt_admin_activeSection_' + subject + '_' + chapterNum, sectionNum);
    });
    
    /**
     * 節数入力欄の変更イベント（科目構造設定用）
     */
    $(document).on('change', '.section-count', function() {
        var newCount = parseInt($(this).val());
        var chapterNum = $(this).data('chapter');
        var subject = $(this).data('subject');
        
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
                var $sectionContainer = $chapterDetails.find('.section-container');
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
    $(document).on('change', '.item-count', function() {
        var newCount = parseInt($(this).val());
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
    $('input[name$="_chapters"]').on('change', function() {
        var newCount = parseInt($(this).val());
        var subject = $(this).attr('name').replace('_chapters', '');
        var $subjectSection = $(this).closest('.subject-section');
        var $chapterTabs = $subjectSection.find('.chapter-tabs');
        var currentCount = $chapterTabs.find('.chapter-tab').length;
        
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
                $subjectSection.find('.chapter-settings').append(chapterHtml);
            }
        } else if (newCount < currentCount) {
            // 章を削除
            for (var i = currentCount; i > newCount; i--) {
                $chapterTabs.find('.chapter-tab[data-chapter="' + i + '"]').remove();
                $('#chapter-' + subject + '-' + i + '-details').remove();
            }
        }
    });
    
    /**
     * 節詳細HTMLを作成
     */
    function createSectionDetailsHtml(subject, chapterNum, sectionNum) {
        return '<div class="section-details" id="section-' + subject + '-' + chapterNum + '-' + sectionNum + '-details">' +
            '<h6>節' + sectionNum + 'の詳細設定</h6>' +
            '<table class="form-table">' +
            '<tr>' +
            '<th scope="row">節タイトル</th>' +
            '<td>' +
            '<input type="text" name="' + subject + '_chapter_' + chapterNum + '_section_' + sectionNum + '_title" value="節' + sectionNum + '" class="regular-text">' +
            '</td>' +
            '</tr>' +
            '<tr>' +
            '<th scope="row">項の数</th>' +
            '<td>' +
            '<input type="number" name="' + subject + '_chapter_' + chapterNum + '_section_' + sectionNum + '_items" value="1" min="1" max="20" class="small-text item-count">' +
            '</td>' +
            '</tr>' +
            '</table>' +
            '<div class="items-container">' +
            '<h6>項の詳細設定</h6>' +
            '<table class="wp-list-table widefat fixed striped">' +
            '<thead>' +
            '<tr>' +
            '<th width="10%">項番号</th>' +
            '<th width="90%">項タイトル</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>' +
            '<tr>' +
            '<td>1</td>' +
            '<td>' +
            '<input type="text" name="' + subject + '_chapter_' + chapterNum + '_section_' + sectionNum + '_item_1_title" value="項1" class="regular-text">' +
            '</td>' +
            '</tr>' +
            '</tbody>' +
            '</table>' +
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
            '<input type="text" name="' + subject + '_chapter_' + chapterNum + '_section_' + sectionNum + '_item_' + itemNum + '_title" value="項' + itemNum + '" class="regular-text">' +
            '</td>' +
            '</tr>';
    }
    
    /**
     * 章詳細HTMLを作成
     */
    function createChapterDetailsHtml(subject, chapterNum) {
        return '<div class="chapter-details" id="chapter-' + subject + '-' + chapterNum + '-details" style="display: none;">' +
            '<h5>第' + chapterNum + '章の詳細設定</h5>' +
            '<table class="form-table">' +
            '<tr>' +
            '<th scope="row">章タイトル</th>' +
            '<td>' +
            '<input type="text" name="' + subject + '_chapter_' + chapterNum + '" value="第' + chapterNum + '章" class="regular-text">' +
            '</td>' +
            '</tr>' +
            '<tr>' +
            '<th scope="row">節の数</th>' +
            '<td>' +
            '<input type="number" name="' + subject + '_sections_' + chapterNum + '" value="1" min="1" max="20" class="small-text section-count" data-chapter="' + chapterNum + '" data-subject="' + subject + '">' +
            '</td>' +
            '</tr>' +
            '</table>' +
            '<div class="section-container">' +
            '<h6>節・項の詳細設定</h6>' +
            '<div class="section-tabs">' +
            '<div class="section-tab" data-section="1" data-chapter="' + chapterNum + '">1. 節1</div>' +
            '</div>' +
            createSectionDetailsHtml(subject, chapterNum, 1) +
            '</div>' +
            '</div>';
    }
    
    /**
     * 初期表示設定 - ローカルストレージから状態を復元
     */
    function initializeDisplay() {
        // 各科目の状態を復元
        $('.subject-section').each(function() {
            var subjectKey = $(this).data('subject-key');
            
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
        });
        
        // 進捗管理タブの初期化
        if ($('.progress-tab').length > 0) {
            $('.progress-tab').first().trigger('click');
        }
    }
    
    /**
     * フォーム送信前の確認
     */
    $('form').on('submit', function(e) {
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
    $(document).on('keypress', 'input[type="text"], input[type="number"]', function(e) {
        if (e.which == 13) {
            e.preventDefault();
            return false;
        }
    });
    
    /**
     * 初期化実行
     */
    if ($('.subject-section').length > 0 || $('.progress-tab').length > 0) {
        setTimeout(initializeDisplay, 100);
    }
});