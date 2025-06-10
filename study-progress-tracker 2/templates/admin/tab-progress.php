<?php
/**
 * 管理画面 - 進捗管理タブ（修正版）
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<form method="post" action="">
    <?php wp_nonce_field('spt_save_progress'); ?>
    
    <h3><?php _e('学習進捗の管理', 'study-progress-tracker'); ?> 
        <span class="color-settings-toggle" style="font-size: 14px; font-weight: normal; cursor: pointer; margin-left: 15px; color: #0073aa;">
            <span class="dashicons dashicons-admin-appearance"></span> <?php _e('色設定', 'study-progress-tracker'); ?>
        </span>
    </h3>
    
    <!-- 色設定パネル -->
    <div class="color-settings-panel" style="display: none; background: #f9f9f9; padding: 15px; border: 1px solid #e1e1e1; margin-bottom: 15px; border-radius: 3px;">
        <form method="post" action="">
            <?php wp_nonce_field('spt_save_check_settings'); ?>
            <h4><?php _e('チェック状態の色設定', 'study-progress-tracker'); ?></h4>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('理解チェック色', 'study-progress-tracker'); ?></th>
                    <td>
                        <input type="color" name="first_check_color" value="<?php echo esc_attr($progress_settings['first_check_color']); ?>">
                        <p class="description"><?php _e('理解したレベル1のチェック項目の背景色', 'study-progress-tracker'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('習得チェック色', 'study-progress-tracker'); ?></th>
                    <td>
                        <input type="color" name="second_check_color" value="<?php echo esc_attr($progress_settings['second_check_color']); ?>">
                        <p class="description"><?php _e('習得したレベル2のチェック項目の背景色', 'study-progress-tracker'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="save_check_settings" class="button button-primary" value="<?php _e('色設定を保存', 'study-progress-tracker'); ?>">
            </p>
        </form>
    </div>
    
    <p><?php _e('学習状況を「理解」と「習得」の2段階でチェックできます。章をクリックすると展開/折りたたみができます。', 'study-progress-tracker'); ?></p>
    
    <!-- リセットボタン -->
    <div class="reset-progress-container">
        <button type="button" class="button reset-progress-button">
            <?php _e('進捗をリセット', 'study-progress-tracker'); ?>
        </button>
        <div class="reset-confirmation" style="display: none; margin-top: 10px; padding: 10px; background: #ffebe8; border-left: 4px solid #dc3232;">
            <p><?php _e('本当に進捗をリセットしますか？', 'study-progress-tracker'); ?></p>
            <label>
                <input type="checkbox" name="confirm_reset" value="1">
                <?php _e('リセットすることを確認しました', 'study-progress-tracker'); ?>
            </label>
            <br><br>
            <label>
                <?php _e('リセット対象:', 'study-progress-tracker'); ?>
                <select name="reset_subject">
                    <option value="all"><?php _e('全科目', 'study-progress-tracker'); ?></option>
                    <?php foreach ($subjects as $key => $name): ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <br><br>
            <input type="submit" name="reset_progress" class="button button-primary" value="<?php _e('リセット実行', 'study-progress-tracker'); ?>">
            <button type="button" class="button cancel-reset"><?php _e('キャンセル', 'study-progress-tracker'); ?></button>
        </div>
    </div>
    
    <!-- 科目タブ -->
    <div class="progress-tabs">
        <?php 
        $first = true;
        foreach ($subjects as $subject_key => $subject_name): 
            $active_class = $first ? 'active' : '';
            $first = false;
        ?>
            <div class="progress-tab <?php echo $active_class; ?>" data-subject="<?php echo esc_attr($subject_key); ?>">
                <?php echo esc_html($subject_name); ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="progress-settings">
        <?php 
        $first = true;
        foreach ($subjects as $subject_key => $subject_name): 
            $display = $first ? 'block' : 'none';
            $first = false;
            $percent = isset($progress_data[$subject_key]['percent']) ? $progress_data[$subject_key]['percent'] : 0;
            $bar_color = isset($chapter_structure[$subject_key]['color']) ? $chapter_structure[$subject_key]['color'] : '#4CAF50';
        ?>
            <div class="subject-progress" data-subject="<?php echo esc_attr($subject_key); ?>" style="display: <?php echo $display; ?>;">
                <h4><?php echo esc_html($subject_name); ?> 
                    <span class="percent-display">
                        (<?php echo esc_html($percent); ?>%<?php _e('完了', 'study-progress-tracker'); ?>)
                    </span>
                </h4>
                
                <div class="progress-bar-container">
                    <div class="progress-bar-fill" style="width: <?php echo esc_attr($percent); ?>%; background-color: <?php echo esc_attr($bar_color); ?>;"></div>
                </div>
                
                <?php if (isset($chapter_structure[$subject_key]['chapters']) && !empty($chapter_structure[$subject_key]['chapters'])): ?>
                    <div class="chapters-accordion">
                        <?php foreach ($chapter_structure[$subject_key]['chapters'] as $chapter_id => $chapter_data): 
                            $chapter_title = isset($chapter_data['title']) ? $chapter_data['title'] : '第' . $chapter_id . '章';
                            
                            // 章の進捗状況を計算
                            $chapter_completed = false;
                            $chapter_mastered = false;
                            if (isset($progress_data[$subject_key]['chapters'][$chapter_id])) {
                                // 完了状況をチェック
                                if (isset($chapter_data['section_data'])) {
                                    $total_sections = count($chapter_data['section_data']);
                                    $completed_sections = count($progress_data[$subject_key]['chapters'][$chapter_id]);
                                    $chapter_completed = ($completed_sections == $total_sections);
                                    
                                    // 習得状況をチェック
                                    $mastered_count = 0;
                                    foreach ($progress_data[$subject_key]['chapters'][$chapter_id] as $section_id => $section_progress) {
                                        if (is_array($section_progress) && isset($section_progress['items'])) {
                                            // 新形式
                                            $total_items = isset($chapter_data['section_data'][$section_id]['item_data']) ? 
                                                count($chapter_data['section_data'][$section_id]['item_data']) : 0;
                                            $mastered_items = 0;
                                            foreach ($section_progress['items'] as $item_level) {
                                                if ($item_level >= 2) $mastered_items++;
                                            }
                                            if ($total_items > 0 && $mastered_items == $total_items) {
                                                $mastered_count++;
                                            }
                                        } else {
                                            // 旧形式
                                            if (is_numeric($section_progress) && $section_progress >= 2) {
                                                $mastered_count++;
                                            }
                                        }
                                    }
                                    $chapter_mastered = ($mastered_count == $total_sections);
                                }
                            }
                            
                            $chapter_style = '';
                            if ($chapter_mastered) {
                                $chapter_style = 'background-color: ' . esc_attr($progress_settings['second_check_color']) . ';';
                            } elseif ($chapter_completed) {
                                $chapter_style = 'background-color: ' . esc_attr($progress_settings['first_check_color']) . ';';
                            }
                            
                            if (isset($chapter_data['section_data']) && is_array($chapter_data['section_data'])):
                        ?>
                            <div class="chapter-accordion-item" data-chapter="<?php echo esc_attr($chapter_id); ?>" data-subject="<?php echo esc_attr($subject_key); ?>">
                                <div class="chapter-accordion-header" style="<?php echo $chapter_style; ?>">
                                    <span class="chapter-toggle-icon">+</span>
                                    <span class="chapter-title"><?php echo esc_html($chapter_title); ?></span>
                                </div>

                                <div class="chapter-accordion-content" style="display: none;">
                                    <?php foreach ($chapter_data['section_data'] as $section_id => $section_data): 
                                        $section_title = isset($section_data['title']) ? $section_data['title'] : '節' . $section_id;
                                        
                                        // 節の進捗状況を計算
                                        $section_completed = false;
                                        $section_mastered = false;
                                        if (isset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id])) {
                                            $section_progress = $progress_data[$subject_key]['chapters'][$chapter_id][$section_id];
                                            if (is_array($section_progress) && isset($section_progress['items'])) {
                                                $total_items = isset($section_data['item_data']) ? count($section_data['item_data']) : 0;
                                                $completed_items = count($section_progress['items']);
                                                $section_completed = ($total_items > 0 && $completed_items == $total_items);
                                                
                                                $mastered_items = 0;
                                                foreach ($section_progress['items'] as $item_level) {
                                                    if ($item_level >= 2) $mastered_items++;
                                                }
                                                $section_mastered = ($total_items > 0 && $mastered_items == $total_items);
                                            }
                                        }
                                        
                                        $section_style = '';
                                        if ($section_mastered) {
                                            $section_style = 'background-color: ' . esc_attr($progress_settings['second_check_color']) . ';';
                                        } elseif ($section_completed) {
                                            $section_style = 'background-color: ' . esc_attr($progress_settings['first_check_color']) . ';';
                                        }
                                    ?>
                                        <div class="section-item">
                                            <div class="section-header" style="<?php echo $section_style; ?>">
                                                <span class="section-title"><?php echo esc_html($section_title); ?></span>
                                            </div>
                                            <div class="section-content">
                                                <?php 
                                                if (isset($section_data['item_data']) && is_array($section_data['item_data'])):
                                                    foreach ($section_data['item_data'] as $item_id => $item_data): 
                                                        $item_title = isset($item_data['title']) ? $item_data['title'] : '項' . $item_id;
                                                        
                                                        // チェック状態
                                                        $first_check = false;
                                                        $second_check = false;
                                                        
                                                        if (isset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]['items'][$item_id])) {
                                                            $check_level = $progress_data[$subject_key]['chapters'][$chapter_id][$section_id]['items'][$item_id];
                                                            $first_check = $check_level >= 1;
                                                            $second_check = $check_level >= 2;
                                                        }
                                                        
                                                        // 項のスタイル
                                                        $item_style = '';
                                                        if ($second_check) {
                                                            $item_style = 'background-color: ' . esc_attr($progress_settings['second_check_color']) . ';';
                                                        } elseif ($first_check) {
                                                            $item_style = 'background-color: ' . esc_attr($progress_settings['first_check_color']) . ';';
                                                        }
                                                ?>
                                                    <div class="item-row" style="<?php echo $item_style; ?>" 
                                                         data-subject="<?php echo esc_attr($subject_key); ?>" 
                                                         data-chapter="<?php echo esc_attr($chapter_id); ?>" 
                                                         data-section="<?php echo esc_attr($section_id); ?>" 
                                                         data-item="<?php echo esc_attr($item_id); ?>">
                                                        <span class="item-title"><?php echo esc_html($item_title); ?></span>
                                                        <div class="item-checkboxes">
                                                            <label title="<?php _e('理解した', 'study-progress-tracker'); ?>" class="checkbox-label">
                                                                <input type="checkbox" 
                                                                       name="<?php echo $subject_key; ?>_chapter_<?php echo $chapter_id; ?>_section_<?php echo $section_id; ?>_item_<?php echo $item_id; ?>" 
                                                                       value="1" <?php checked($first_check); ?>>
                                                                <span><?php _e('理解', 'study-progress-tracker'); ?></span>
                                                            </label>
                                                            <label title="<?php _e('習得した', 'study-progress-tracker'); ?>" class="checkbox-label">
                                                                <input type="checkbox" 
                                                                       name="<?php echo $subject_key; ?>_chapter_<?php echo $chapter_id; ?>_section_<?php echo $section_id; ?>_item_<?php echo $item_id; ?>_second" 
                                                                       value="1" <?php checked($second_check); ?>>
                                                                <span><?php _e('習得', 'study-progress-tracker'); ?></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php 
                                                    endforeach;
                                                else:
                                                    // 旧形式の互換性維持
                                                    $first_check = false;
                                                    $second_check = false;
                                                    
                                                    if (isset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]) && !is_array($progress_data[$subject_key]['chapters'][$chapter_id][$section_id])) {
                                                        $check_level = $progress_data[$subject_key]['chapters'][$chapter_id][$section_id];
                                                        $first_check = $check_level >= 1;
                                                        $second_check = $check_level >= 2;
                                                    }
                                                    
                                                    $section_style_legacy = '';
                                                    if ($second_check) {
                                                        $section_style_legacy = 'background-color: ' . esc_attr($progress_settings['second_check_color']) . ';';
                                                    } elseif ($first_check) {
                                                        $section_style_legacy = 'background-color: ' . esc_attr($progress_settings['first_check_color']) . ';';
                                                    }
                                                ?>
                                                    <div class="item-row legacy-item" style="<?php echo $section_style_legacy; ?>">
                                                        <span class="item-title"><?php echo esc_html($section_title); ?> <?php _e('(旧形式)', 'study-progress-tracker'); ?></span>
                                                        <div class="item-checkboxes">
                                                            <label title="<?php _e('理解した', 'study-progress-tracker'); ?>" class="checkbox-label">
                                                                <input type="checkbox" 
                                                                       name="<?php echo $subject_key; ?>_chapter_<?php echo $chapter_id; ?>_section_<?php echo $section_id; ?>" 
                                                                       value="1" <?php checked($first_check); ?>>
                                                                <span><?php _e('理解', 'study-progress-tracker'); ?></span>
                                                            </label>
                                                            <label title="<?php _e('習得した', 'study-progress-tracker'); ?>" class="checkbox-label">
                                                                <input type="checkbox" 
                                                                       name="<?php echo $subject_key; ?>_chapter_<?php echo $chapter_id; ?>_section_<?php echo $section_id; ?>_second" 
                                                                       value="1" <?php checked($second_check); ?>>
                                                                <span><?php _e('習得', 'study-progress-tracker'); ?></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p><?php _e('この科目には章が設定されていません。「科目構造設定」タブで設定してください。', 'study-progress-tracker'); ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <p class="submit">
        <input type="submit" name="save_progress" class="button button-primary" value="<?php _e('進捗状況を保存', 'study-progress-tracker'); ?>">
    </p>
</form>

<script>
jQuery(document).ready(function($) {
    // 色設定パネルの表示/非表示
    $('.color-settings-toggle').on('click', function() {
        $('.color-settings-panel').slideToggle();
    });
    
    // リセット確認の表示/非表示
    $('.reset-progress-button').on('click', function() {
        $('.reset-confirmation').slideDown();
    });
    
    $('.cancel-reset').on('click', function() {
        $('.reset-confirmation').slideUp();
        $('input[name="confirm_reset"]').prop('checked', false);
    });
    
    // 進捗管理タブでのタブ切り替え
    $('.progress-tab').on('click', function() {
        var subject = $(this).data('subject');
        
        // タブの切り替え
        $('.progress-tab').removeClass('active');
        $(this).addClass('active');
        
        // コンテンツの切り替え
        $('.subject-progress').hide();
        $('.subject-progress[data-subject="' + subject + '"]').show();
    });
    
    // 章アコーディオン
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
    
    // 理解チェックボックスの処理
    $(document).on('change', 'input[type="checkbox"]:not([name*="_second"])', function() {
        var $this = $(this);
        var $secondCheck = $this.closest('.item-checkboxes').find('input[name*="_second"]');
        
        // 理解のチェックを外した場合、習得も外す
        if (!$this.prop('checked')) {
            $secondCheck.prop('checked', false);
        }
        
        updateItemStyle($this.closest('.item-row'));
    });
    
    // 習得チェックボックスの処理
    $(document).on('change', 'input[type="checkbox"][name*="_second"]', function() {
        var $this = $(this);
        var $firstCheck = $this.closest('.item-checkboxes').find('input[type="checkbox"]:not([name*="_second"])');
        
        // 習得をチェックした場合、理解も自動的にチェック
        if ($this.prop('checked')) {
            $firstCheck.prop('checked', true);
        }
        
        updateItemStyle($this.closest('.item-row'));
    });
    
    // 項目のスタイルを更新
    function updateItemStyle($item) {
        var $firstCheck = $item.find('input[type="checkbox"]:not([name*="_second"])');
        var $secondCheck = $item.find('input[type="checkbox"][name*="_second"]');
        
        $item.css('background-color', '');
        
        if ($secondCheck.prop('checked')) {
            $item.css('background-color', '<?php echo esc_js($progress_settings['second_check_color']); ?>');
        } else if ($firstCheck.prop('checked')) {
            $item.css('background-color', '<?php echo esc_js($progress_settings['first_check_color']); ?>');
        }
    }
});
</script>