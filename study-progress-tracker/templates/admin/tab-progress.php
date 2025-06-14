<?php
/**
 * 管理画面 - 進捗管理タブ（タブクリック問題完全修正版）
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<form method="post" action="">
    <?php wp_nonce_field('spt_save_progress'); ?>
    
    <div style="display: flex; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <h3 style="margin: 0;"><?php _e('学習進捗の管理', 'study-progress-tracker'); ?></h3>
        
        <!-- 色設定ボタン -->
        <span class="color-settings-toggle" style="font-size: 14px; font-weight: normal; cursor: pointer; color: #0073aa; display: flex; align-items: center;">
            <span class="dashicons dashicons-admin-appearance"></span> <?php _e('色設定', 'study-progress-tracker'); ?>
        </span>
        
        <!-- リセットボタン -->
        <button type="button" class="button reset-progress-button" style="margin-left: auto;">
            <?php _e('進捗をリセット', 'study-progress-tracker'); ?>
        </button>
    </div>
    
    <!-- 色設定パネル -->
    <div class="color-settings-panel" style="display: none; background: #f9f9f9; padding: 15px; border: 1px solid #e1e1e1; margin-bottom: 15px; border-radius: 3px;">
        <form method="post" action="">
            <?php wp_nonce_field('spt_save_check_settings'); ?>
            <h4><?php _e('チェック状態の色設定', 'study-progress-tracker'); ?></h4>
            <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                <div>
                    <label><?php _e('理解チェック色', 'study-progress-tracker'); ?></label><br>
                    <input type="color" name="first_check_color" value="<?php echo esc_attr($progress_settings['first_check_color']); ?>">
                    <p class="description"><?php _e('理解したレベル1のチェック項目の背景色', 'study-progress-tracker'); ?></p>
                </div>
                <div>
                    <label><?php _e('習得チェック色', 'study-progress-tracker'); ?></label><br>
                    <input type="color" name="second_check_color" value="<?php echo esc_attr($progress_settings['second_check_color']); ?>">
                    <p class="description"><?php _e('習得したレベル2のチェック項目の背景色', 'study-progress-tracker'); ?></p>
                </div>
            </div>
            <p class="submit">
                <input type="submit" name="save_check_settings" class="button button-primary" value="<?php _e('色設定を保存', 'study-progress-tracker'); ?>">
            </p>
        </form>
    </div>
    
    <!-- リセット確認パネル -->
    <div class="reset-confirmation" style="display: none; margin-bottom: 15px; padding: 15px; background: #ffebe8; border-left: 4px solid #dc3232; border-radius: 3px;">
        <form method="post" action="">
            <?php wp_nonce_field('spt_reset_progress'); ?>
            <h4><?php _e('進捗リセットの確認', 'study-progress-tracker'); ?></h4>
            <p><?php _e('本当に進捗をリセットしますか？この操作は元に戻せません。', 'study-progress-tracker'); ?></p>
            
            <div style="margin-bottom: 15px;">
                <label>
                    <?php _e('リセット対象:', 'study-progress-tracker'); ?>
                    <select name="reset_subject" style="margin-left: 10px;">
                        <option value="all"><?php _e('全科目', 'study-progress-tracker'); ?></option>
                        <?php foreach ($subjects as $key => $name): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label>
                    <input type="checkbox" name="confirm_reset" value="1">
                    <?php _e('リセットすることを確認しました', 'study-progress-tracker'); ?>
                </label>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <input type="submit" name="reset_progress" class="button button-primary" value="<?php _e('リセット実行', 'study-progress-tracker'); ?>">
                <button type="button" class="button cancel-reset"><?php _e('キャンセル', 'study-progress-tracker'); ?></button>
            </div>
        </form>
    </div>
    
    <p><?php _e('学習状況を「理解」と「習得」の2段階でチェックできます。章をクリックすると展開/折りたたみができます。', 'study-progress-tracker'); ?></p>
    
    <!-- 科目タブ（修正版） -->
    <div class="progress-tabs" data-tab-type="progress">
        <?php 
        $first = true;
        foreach ($subjects as $subject_key => $subject_name): 
            $active_class = $first ? 'active' : '';
            $first = false;
        ?>
            <div class="progress-tab <?php echo $active_class; ?>" 
                 data-subject="<?php echo esc_attr($subject_key); ?>"
                 data-tab-id="progress-<?php echo esc_attr($subject_key); ?>"
                 data-action="switch-progress-tab">
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
                            <div class="chapter-accordion-item progress-chapter-item" 
                                 data-chapter="<?php echo esc_attr($chapter_id); ?>" 
                                 data-subject="<?php echo esc_attr($subject_key); ?>"
                                 data-item-type="chapter">
                                <div class="chapter-accordion-header progress-chapter-header" 
                                     data-action="toggle-chapter"
                                     style="<?php echo $chapter_style; ?>">
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