<?php
/**
 * 管理画面 - 科目構造設定タブ（アコーディオン式修正版）
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<form method="post" action="">
    <?php wp_nonce_field('spt_save_structure'); ?>
    
    <h3><?php _e('科目と章の設定', 'study-progress-tracker'); ?></h3>
    <p><?php _e('各科目の章・節・項の構成を設定します。科目名をクリックすると詳細設定が表示されます。', 'study-progress-tracker'); ?></p>
    
    <div class="subject-settings">
        <?php foreach ($subjects as $subject_key => $subject_name): ?>
            <div class="subject-section" data-subject-key="<?php echo esc_attr($subject_key); ?>">
                <!-- 科目のアコーディオンヘッダー -->
                <div class="subject-header" style="cursor: pointer; padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px;">
                    <h4 style="margin: 0; display: flex; align-items: center;">
                        <span class="subject-toggle-icon" style="margin-right: 10px; font-weight: bold;">+</span>
                        <?php echo esc_html($subject_name); ?>
                        <span style="margin-left: auto; font-size: 0.9em; color: #666;">
                            (<?php 
                            $chapter_count = isset($chapter_structure[$subject_key]['total']) ? $chapter_structure[$subject_key]['total'] : 0;
                            printf(__('%d章', 'study-progress-tracker'), $chapter_count); 
                            ?>)
                        </span>
                    </h4>
                </div>
                
                <!-- 科目の詳細設定（デフォルトで非表示） -->
                <div class="subject-content" style="display: none; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;">
                    
                    <!-- 基本設定 -->
                    <div class="basic-settings" style="margin-bottom: 30px;">
                        <h5><?php _e('基本設定', 'study-progress-tracker'); ?></h5>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                            <div>
                                <label for="<?php echo $subject_key; ?>_chapters"><?php _e('総章数', 'study-progress-tracker'); ?></label>
                                <input type="number" 
                                       id="<?php echo $subject_key; ?>_chapters"
                                       name="<?php echo $subject_key; ?>_chapters" 
                                       value="<?php echo isset($chapter_structure[$subject_key]['total']) ? esc_attr($chapter_structure[$subject_key]['total']) : 10; ?>" 
                                       min="1" max="50" class="small-text" style="width: 100%;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- 章設定 -->
                    <div class="chapter-settings">
                        <h5><?php _e('各章の設定', 'study-progress-tracker'); ?></h5>
                        <p><?php _e('章タブをクリックして節と項を設定できます。', 'study-progress-tracker'); ?></p>
                        
                        <!-- 章タブ一覧 -->
                        <div class="chapter-tabs">
                            <?php
                            $total_chapters = isset($chapter_structure[$subject_key]['total']) ? $chapter_structure[$subject_key]['total'] : 10;
                            for ($i = 1; $i <= $total_chapters; $i++):
                                $chapter_title = isset($chapter_structure[$subject_key]['chapters'][$i]['title']) ? 
                                    $chapter_structure[$subject_key]['chapters'][$i]['title'] : '第' . $i . '章';
                            ?>
                                <div class="chapter-tab" data-chapter="<?php echo $i; ?>">
                                    <?php echo $i; ?>. <?php echo esc_html($chapter_title); ?>
                                </div>
                            <?php endfor; ?>
                        </div>
                        
                        <!-- 章詳細設定 -->
                        <div class="chapters-detail-container">
                            <?php
                            for ($i = 1; $i <= $total_chapters; $i++):
                                $chapter_title = isset($chapter_structure[$subject_key]['chapters'][$i]['title']) ? 
                                    $chapter_structure[$subject_key]['chapters'][$i]['title'] : '第' . $i . '章';
                                $section_count = isset($chapter_structure[$subject_key]['chapters'][$i]['sections']) ? 
                                    $chapter_structure[$subject_key]['chapters'][$i]['sections'] : 1;
                            ?>
                                <div class="chapter-details" id="chapter-<?php echo $subject_key; ?>-<?php echo $i; ?>-details" style="display: none; margin-top: 20px; padding: 20px; background: #fafafa; border: 1px solid #e5e5e5; border-radius: 5px;">
                                    <h5><?php printf(__('第%d章の詳細設定', 'study-progress-tracker'), $i); ?></h5>
                                    
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                                        <div>
                                            <label for="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>"><?php _e('章タイトル', 'study-progress-tracker'); ?></label>
                                            <input type="text" 
                                                   id="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>"
                                                   name="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>" 
                                                   value="<?php echo esc_attr($chapter_title); ?>" 
                                                   class="regular-text" style="width: 100%;">
                                        </div>
                                        <div>
                                            <label for="<?php echo $subject_key; ?>_sections_<?php echo $i; ?>"><?php _e('節の数', 'study-progress-tracker'); ?></label>
                                            <input type="number" 
                                                   id="<?php echo $subject_key; ?>_sections_<?php echo $i; ?>"
                                                   name="<?php echo $subject_key; ?>_sections_<?php echo $i; ?>" 
                                                   value="<?php echo esc_attr($section_count); ?>" 
                                                   min="1" max="20" 
                                                   class="small-text section-count" 
                                                   data-chapter="<?php echo $i; ?>" 
                                                   data-subject="<?php echo $subject_key; ?>" style="width: 100%;">
                                        </div>
                                    </div>
                                    
                                    <div class="section-container">
                                        <h6><?php _e('節・項の詳細設定', 'study-progress-tracker'); ?></h6>
                                        <div class="section-tabs">
                                            <?php 
                                            for ($j = 1; $j <= $section_count; $j++):
                                                $section_title = isset($chapter_structure[$subject_key]['chapters'][$i]['section_data'][$j]['title']) ? 
                                                    $chapter_structure[$subject_key]['chapters'][$i]['section_data'][$j]['title'] : '節' . $j;
                                            ?>
                                                <div class="section-tab" data-section="<?php echo $j; ?>" data-chapter="<?php echo $i; ?>">
                                                    <?php echo $j; ?>. <?php echo esc_html($section_title); ?>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                        
                                        <div class="sections-detail-container">
                                            <?php 
                                            for ($j = 1; $j <= $section_count; $j++):
                                                $section_title = isset($chapter_structure[$subject_key]['chapters'][$i]['section_data'][$j]['title']) ? 
                                                    $chapter_structure[$subject_key]['chapters'][$i]['section_data'][$j]['title'] : '節' . $j;
                                                $item_count = isset($chapter_structure[$subject_key]['chapters'][$i]['section_data'][$j]['items']) ? 
                                                    $chapter_structure[$subject_key]['chapters'][$i]['section_data'][$j]['items'] : 1;
                                            ?>
                                            <div class="section-details" id="section-<?php echo $subject_key; ?>-<?php echo $i; ?>-<?php echo $j; ?>-details" style="display: none; margin-top: 15px; padding: 15px; background: #fff; border: 1px solid #eee; border-radius: 3px;">
                                                <h6><?php printf(__('節%dの詳細設定', 'study-progress-tracker'), $j); ?></h6>
                                                
                                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                                                    <div>
                                                        <label for="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>_section_<?php echo $j; ?>_title"><?php _e('節タイトル', 'study-progress-tracker'); ?></label>
                                                        <input type="text" 
                                                               id="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>_section_<?php echo $j; ?>_title"
                                                               name="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>_section_<?php echo $j; ?>_title" 
                                                               value="<?php echo esc_attr($section_title); ?>" 
                                                               class="regular-text" style="width: 100%;">
                                                    </div>
                                                    <div>
                                                        <label for="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>_section_<?php echo $j; ?>_items"><?php _e('項の数', 'study-progress-tracker'); ?></label>
                                                        <input type="number" 
                                                               id="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>_section_<?php echo $j; ?>_items"
                                                               name="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>_section_<?php echo $j; ?>_items" 
                                                               value="<?php echo esc_attr($item_count); ?>" 
                                                               min="1" max="20" 
                                                               class="small-text item-count" style="width: 100%;">
                                                    </div>
                                                </div>
                                                
                                                <div class="items-container">
                                                    <h6><?php _e('項の詳細設定', 'study-progress-tracker'); ?></h6>
                                                    <div class="items-table-container" style="overflow-x: auto;">
                                                        <table class="wp-list-table widefat fixed striped" style="min-width: 400px;">
                                                            <thead>
                                                                <tr>
                                                                    <th width="15%"><?php _e('項番号', 'study-progress-tracker'); ?></th>
                                                                    <th width="85%"><?php _e('項タイトル', 'study-progress-tracker'); ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php 
                                                                for ($k = 1; $k <= $item_count; $k++):
                                                                    $item_title = isset($chapter_structure[$subject_key]['chapters'][$i]['section_data'][$j]['item_data'][$k]['title']) ? 
                                                                        $chapter_structure[$subject_key]['chapters'][$i]['section_data'][$j]['item_data'][$k]['title'] : '項' . $k;
                                                                ?>
                                                                <tr>
                                                                    <td><?php echo $k; ?></td>
                                                                    <td>
                                                                        <input type="text" 
                                                                               name="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>_section_<?php echo $j; ?>_item_<?php echo $k; ?>_title" 
                                                                               value="<?php echo esc_attr($item_title); ?>" 
                                                                               class="regular-text" style="width: 100%;">
                                                                    </td>
                                                                </tr>
                                                                <?php endfor; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <p class="submit">
        <input type="submit" name="save_structure" class="button button-primary" value="<?php _e('科目構造を保存', 'study-progress-tracker'); ?>">
    </p>
</form>

<script>
jQuery(document).ready(function($) {
    // 科目のアコーディオン
    $('.subject-header').on('click', function() {
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
    });
    
    // 章タブのクリックイベント
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
    });
    
    // 節タブのクリックイベント
    $(document).on('click', '.section-tab', function() {
        var chapterNum = $(this).data('chapter');
        var sectionNum = $(this).data('section');
        var $chapterDetails = $(this).closest('.chapter-details');
        var subject = $(this).closest('.subject-section').data('subject-key');
        
        // 同じ章内の他のタブを非アクティブにする
        $chapterDetails.find('.section-tab').removeClass('active');
        $(this).addClass('active');
        
        // 節詳細の表示切り替え
        $chapterDetails.find('.section-details').hide();
        $('#section-' + subject + '-' + chapterNum + '-' + sectionNum + '-details').show();
    });
    
    // 節数入力欄の変更イベント
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
                var $sectionContainer = $chapterDetails.find('.sections-detail-container');
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
    
    // 項の数の変更イベント
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
    
    // 章数の変更イベント
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
});
</script>

<style>
/* 科目のアコーディオンスタイル */
.subject-header:hover {
    background: #f0f0f0 !important;
}

.subject-toggle-icon {
    transition: transform 0.2s ease;
}

/* 章・節タブのスタイル */
.chapter-tabs,
.section-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 15px;
}

.chapter-tab,
.section-tab {
    padding: 8px 12px;
    background-color: #f0f0f0;
    border: 1px solid #ddd;
    border-radius: 3px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 13px;
}

.chapter-tab:hover,
.section-tab:hover {
    background-color: #e0e0e0;
}

.chapter-tab.active,
.section-tab.active {
    background-color: #0073aa;
    color: white;
    border-color: #0073aa;
}

/* レスポンシブ対応 */
@media (max-width: 782px) {
    .subject-settings {
        padding: 0;
    }
    
    .subject-header h4 {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 5px;
    }
    
    .subject-header h4 span:last-child {
        margin-left: 0 !important;
        font-size: 0.8em !important;
    }
    
    .basic-settings div[style*="display: grid"] {
        grid-template-columns: 1fr !important;
    }
    
    .chapter-details div[style*="display: grid"] {
        grid-template-columns: 1fr !important;
    }
    
    .section-details div[style*="display: grid"] {
        grid-template-columns: 1fr !important;
    }
    
    .chapter-tabs,
    .section-tabs {
        flex-direction: column;
        gap: 3px;
    }
    
    .chapter-tab,
    .section-tab {
        width: 100%;
        text-align: left;
    }
    
    .items-table-container {
        overflow-x: auto;
    }
    
    .wp-list-table {
        min-width: 300px;
    }
    
    .wp-list-table th,
    .wp-list-table td {
        padding: 8px;
        font-size: 13px;
    }
    
    .regular-text,
    .small-text {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .subject-content {
        padding: 15px !important;
    }
    
    .chapter-details {
        padding: 15px !important;
    }
    
    .section-details {
        padding: 10px !important;
    }
    
    .chapter-tab,
    .section-tab {
        padding: 10px 8px;
        font-size: 12px;
    }
    
    h5, h6 {
        font-size: 14px;
    }
    
    .wp-list-table th,
    .wp-list-table td {
        padding: 6px;
        font-size: 12px;
    }
}

/* フォーム要素の改善 */
input[type="text"],
input[type="number"] {
    border: 1px solid #ddd;
    border-radius: 3px;
    padding: 6px 8px;
}

input[type="text"]:focus,
input[type="number"]:focus {
    border-color: #0073aa;
    box-shadow: 0 0 0 1px #0073aa;
    outline: none;
}

/* グリッドレイアウトの改善 */
div[style*="display: grid"] label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #333;
}

/* テーブルの改善 */
.items-table-container {
    border: 1px solid #ddd;
    border-radius: 3px;
    overflow: hidden;
}

.wp-list-table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.wp-list-table tbody tr:hover {
    background-color: #f8f9fa;
}
</style>