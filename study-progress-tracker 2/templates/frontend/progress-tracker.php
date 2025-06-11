<?php
/**
 * 進捗トラッカーフロントエンド表示テンプレート（旧形式削除・完全修正版）
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="progress-tracker-shortcode <?php echo esc_attr($style_class); ?>" 
     data-nonce="<?php echo wp_create_nonce('progress_tracker_nonce'); ?>">
    
    <?php if (count($subjects) > 1): ?>
    <div class="progress-tabs">
        <?php 
        $first = true;
        foreach ($subjects as $subject_key => $subject_name): 
            $tab_class = $first ? 'progress-tab active' : 'progress-tab';
            $first = false;
        ?>
        <div class="<?php echo $tab_class; ?>" data-subject="<?php echo esc_attr($subject_key); ?>">
            <?php echo esc_html($subject_name); ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php 
    $first = true;
    foreach ($subjects as $subject_key => $subject_name): 
        $display = $first ? 'block' : 'none';
        $first = false;
        $percent = isset($progress_data[$subject_key]['percent']) ? $progress_data[$subject_key]['percent'] : 0;
        $progress_color = isset($chapter_structure[$subject_key]['color']) ? $chapter_structure[$subject_key]['color'] : '#4CAF50';
    ?>
    <div class="progress-subject" data-subject="<?php echo esc_attr($subject_key); ?>" style="display: <?php echo $display; ?>;">
        <h3 class="subject-title">
            <?php echo esc_html($subject_name); ?>
            <span class="percent">(<?php echo esc_html($percent); ?>%)</span>
        </h3>
        
        <?php if ($interactive): ?>
        <div class="progress-controls">
            <button class="reset-progress-btn" data-subject="<?php echo esc_attr($subject_key); ?>">
                <?php _e('進捗をリセット', 'study-progress-tracker'); ?>
            </button>
        </div>
        <?php endif; ?>
        
        <div class="progress-bar-container">
            <div class="progress-bar-fill" style="width:<?php echo esc_attr($percent); ?>%; background-color:<?php echo esc_attr($progress_color); ?>;"></div>
        </div>
        
        <?php if (isset($chapter_structure[$subject_key]['chapters']) && !empty($chapter_structure[$subject_key]['chapters'])): ?>
            <div class="chapters-container">
                <?php foreach ($chapter_structure[$subject_key]['chapters'] as $chapter_id => $chapter_data): 
                    $chapter_title = isset($chapter_data['title']) ? $chapter_data['title'] : '第' . $chapter_id . '章';
                    
                    // 章の完了状態を確認
                    $chapter_completed = false;
                    $chapter_mastered = false;
                    $chapter_class = 'chapter-accordion-item';
                    
                    if (isset($progress_data[$subject_key]['chapters'][$chapter_id])) {
                        $chapter_sections = $progress_data[$subject_key]['chapters'][$chapter_id];
                        $total_sections = isset($chapter_data['section_data']) ? count($chapter_data['section_data']) : 0;
                        
                        if (count($chapter_sections) == $total_sections && $total_sections > 0) {
                            $chapter_completed = true;
                            $chapter_class .= ' completed';
                            
                            // 習得チェック
                            $mastered_count = 0;
                            foreach ($chapter_sections as $section_id => $section_data) {
                                if (is_array($section_data) && isset($section_data['items'])) {
                                    // 新形式：全項目が習得レベルかチェック
                                    $section_info = isset($chapter_data['section_data'][$section_id]) ? $chapter_data['section_data'][$section_id] : null;
                                    if ($section_info && isset($section_info['item_data'])) {
                                        $total_items = count($section_info['item_data']);
                                        $mastered_items = 0;
                                        foreach ($section_data['items'] as $item_level) {
                                            if ($item_level >= 2) $mastered_items++;
                                        }
                                        if ($mastered_items == $total_items) {
                                            $mastered_count++;
                                        }
                                    }
                                }
                            }
                            
                            if ($mastered_count == $total_sections) {
                                $chapter_mastered = true;
                                $chapter_class .= ' mastered';
                            }
                        }
                    }
                    
                    // 章データが正しく設定されている場合のみ表示
                    if (isset($chapter_data['section_data']) && is_array($chapter_data['section_data'])):
                ?>
                    <div class="<?php echo $chapter_class; ?>" 
                         data-chapter="<?php echo esc_attr($chapter_id); ?>" 
                         data-subject="<?php echo esc_attr($subject_key); ?>">
                        <div class="chapter-accordion-header">
                            <span class="chapter-toggle-icon">+</span>
                            <span class="chapter-title"><?php echo esc_html($chapter_title); ?></span>
                        </div>
                        
                        <?php if ($interactive): ?>
                        <div class="chapter-accordion-content" style="display: none;">
                            <?php foreach ($chapter_data['section_data'] as $section_id => $section_data): 
                                $section_title = isset($section_data['title']) ? $section_data['title'] : '節' . $section_id;
                                
                                // 節の完了状態
                                $section_completed = false;
                                $section_mastered = false;
                                $section_class = 'section-item';
                                
                                if (isset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id])) {
                                    $section_progress = $progress_data[$subject_key]['chapters'][$chapter_id][$section_id];
                                    
                                    if (is_array($section_progress) && isset($section_progress['items'])) {
                                        // 新形式
                                        $total_items = isset($section_data['item_data']) ? count($section_data['item_data']) : 0;
                                        $completed_items = count($section_progress['items']);
                                        
                                        if ($total_items > 0 && $completed_items == $total_items) {
                                            $section_completed = true;
                                            $section_class .= ' completed';
                                            
                                            $mastered_items = 0;
                                            foreach ($section_progress['items'] as $item_level) {
                                                if ($item_level >= 2) $mastered_items++;
                                            }
                                            
                                            if ($mastered_items == $total_items) {
                                                $section_mastered = true;
                                                $section_class .= ' mastered';
                                            }
                                        }
                                    }
                                }
                            ?>
                                <div class="<?php echo $section_class; ?>" 
                                     data-section="<?php echo esc_attr($section_id); ?>">
                                    <div class="section-header">
                                        <span class="section-title"><?php echo esc_html($section_title); ?></span>
                                    </div>
                                    
                                    <div class="section-content">
                                        <?php if (isset($section_data['item_data']) && is_array($section_data['item_data'])): ?>
                                            <?php foreach ($section_data['item_data'] as $item_id => $item_data): 
                                                $item_title = isset($item_data['title']) ? $item_data['title'] : '項' . $item_id;
                                                
                                                // チェック状態
                                                $check_level = 0;
                                                if (isset($progress_data[$subject_key]['chapters'][$chapter_id][$section_id]['items'][$item_id])) {
                                                    $check_level = $progress_data[$subject_key]['chapters'][$chapter_id][$section_id]['items'][$item_id];
                                                }
                                                
                                                $item_class = 'item-row';
                                                if ($check_level >= 1) $item_class .= ' checked';
                                                if ($check_level >= 2) $item_class .= ' mastered';
                                            ?>
                                                <div class="<?php echo $item_class; ?>" 
                                                     data-subject="<?php echo esc_attr($subject_key); ?>" 
                                                     data-chapter="<?php echo esc_attr($chapter_id); ?>" 
                                                     data-section="<?php echo esc_attr($section_id); ?>" 
                                                     data-item="<?php echo esc_attr($item_id); ?>">
                                                    <span class="item-title"><?php echo esc_html($item_title); ?></span>
                                                    <div class="item-checkboxes">
                                                        <label class="checkbox-label" title="<?php _e('理解した', 'study-progress-tracker'); ?>">
                                                            <input type="checkbox" 
                                                                   class="item-check-level-1" 
                                                                   data-level="1" 
                                                                   <?php checked($check_level >= 1); ?>>
                                                            <span><?php _e('理解', 'study-progress-tracker'); ?></span>
                                                        </label>
                                                        <label class="checkbox-label" title="<?php _e('習得した', 'study-progress-tracker'); ?>">
                                                            <input type="checkbox" 
                                                                   class="item-check-level-2" 
                                                                   data-level="2" 
                                                                   <?php checked($check_level >= 2); ?>>
                                                            <span><?php _e('習得', 'study-progress-tracker'); ?></span>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="no-items-message" style="padding: 15px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; color: #6c757d; text-align: center; font-style: italic;">
                                                <?php _e('この節には項が設定されていません。', 'study-progress-tracker'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="chapter-accordion-content" style="display: none;">
                            <div class="readonly-message" style="padding: 15px; background-color: #e9ecef; border: 1px solid #ced4da; border-radius: 5px; color: #495057; text-align: center;">
                                <?php _e('進捗の確認・更新にはログインが必要です。', 'study-progress-tracker'); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php 
                    else:
                ?>
                    <div class="incomplete-chapter" style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin-bottom: 10px; color: #856404;">
                        <h4 style="margin: 0 0 5px 0; color: #856404;"><?php echo esc_html($chapter_title); ?></h4>
                        <p style="margin: 0; font-size: 0.9em;">
                            <?php _e('この章にはまだ詳細な学習項目が設定されていません。', 'study-progress-tracker'); ?>
                        </p>
                    </div>
                <?php 
                    endif;
                ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-chapters-message" style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 20px; margin: 15px 0; color: #721c24; text-align: center;">
                <h4 style="margin: 0 0 10px 0; color: #721c24;">
                    <?php _e('学習項目が設定されていません', 'study-progress-tracker'); ?>
                </h4>
                <p style="margin: 0;">
                    <?php _e('この科目にはまだ章・節・項が設定されていません。管理者による設定が必要です。', 'study-progress-tracker'); ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    
    <?php
    // 試験日カウントダウン（オプション）
    $settings = get_option('progress_tracker_settings', array(
        'exam_date' => '',
        'exam_title' => '試験'
    ));
    
    if (!empty($settings['exam_date'])) {
        $exam_date = strtotime($settings['exam_date']);
        $today = current_time('timestamp');
        $days_left = floor(($exam_date - $today) / (60 * 60 * 24));
        
        if ($days_left >= 0) {
            $exam_title = !empty($settings['exam_title']) ? $settings['exam_title'] : __('試験', 'study-progress-tracker');
            ?>
            <div class="exam-countdown">
                <?php echo esc_html($exam_title); ?><?php _e('まであと', 'study-progress-tracker'); ?> 
                <span class="countdown-number"><?php echo esc_html($days_left); ?></span> 
                <?php _e('日', 'study-progress-tracker'); ?>
            </div>
            <?php
        }
    }
    ?>
    
    <?php if ($interactive): ?>
    <!-- 保存中インジケーター -->
    <div class="saving-indicator" style="display: none;">
        <?php _e('保存中...', 'study-progress-tracker'); ?>
    </div>
    <?php endif; ?>
</div>