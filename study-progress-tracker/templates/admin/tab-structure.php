<?php
/**
 * 管理画面 - 科目構造設定タブ（タブクリック問題完全修正版）
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
                <!-- 科目のアコーディオンヘッダー（修正版） -->
                <div class="subject-header" 
                     data-action="toggle-subject" 
                     data-subject="<?php echo esc_attr($subject_key); ?>" 
                     style="cursor: pointer; padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px; user-select: none;">
                    <h4 style="margin: 0; display: flex; align-items: center; border-bottom: none; padding-bottom: 0;">
                        <span class="subject-toggle-icon" style="margin-right: 10px; font-weight: bold; width: 20px; height: 20px; line-height: 20px; text-align: center; background-color: #eee; border-radius: 50%; font-family: monospace; font-size: 14px;">+</span>
                        <?php echo esc_html($subject_name); ?>
                        <span style="margin-left: auto; font-size: 0.9em; color: #666; font-weight: normal;">
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
                                <div class="chapter-tab" 
                                     data-chapter="<?php echo $i; ?>" 
                                     data-subject="<?php echo esc_attr($subject_key); ?>"
                                     data-action="activate-chapter">
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
                                                <div class="section-tab" 
                                                     data-section="<?php echo $j; ?>" 
                                                     data-chapter="<?php echo $i; ?>" 
                                                     data-subject="<?php echo esc_attr($subject_key); ?>"
                                                     data-action="activate-section">
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