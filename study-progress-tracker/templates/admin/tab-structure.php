<?php
/**
 * 管理画面 - 科目構造設定タブ
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<form method="post" action="">
    <?php wp_nonce_field('spt_save_structure'); ?>
    
    <h3><?php _e('科目と章の設定', 'study-progress-tracker'); ?></h3>
    <p><?php _e('各科目の章・節・項の構成を設定します。', 'study-progress-tracker'); ?></p>
    
    <div class="subject-settings">
        <?php foreach ($subjects as $subject_key => $subject_name): ?>
            <div class="subject-section" data-subject-key="<?php echo esc_attr($subject_key); ?>">
                <h4><?php echo esc_html($subject_name); ?></h4>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('総章数', 'study-progress-tracker'); ?></th>
                        <td>
                            <input type="number" 
                                   name="<?php echo $subject_key; ?>_chapters" 
                                   value="<?php echo isset($chapter_structure[$subject_key]['total']) ? esc_attr($chapter_structure[$subject_key]['total']) : 10; ?>" 
                                   min="1" max="50" class="small-text">
                        </td>
                    </tr>
                </table>
                
                <div class="chapter-settings">
                    <h5><?php _e('各章の設定', 'study-progress-tracker'); ?></h5>
                    <p><?php _e('展開ボタンをクリックして節と項を設定できます。', 'study-progress-tracker'); ?></p>
                    
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
                    <?php
                    for ($i = 1; $i <= $total_chapters; $i++):
                        $chapter_title = isset($chapter_structure[$subject_key]['chapters'][$i]['title']) ? 
                            $chapter_structure[$subject_key]['chapters'][$i]['title'] : '第' . $i . '章';
                        $section_count = isset($chapter_structure[$subject_key]['chapters'][$i]['sections']) ? 
                            $chapter_structure[$subject_key]['chapters'][$i]['sections'] : 1;
                    ?>
                        <div class="chapter-details" id="chapter-<?php echo $subject_key; ?>-<?php echo $i; ?>-details" style="display: none;">
                            <h5><?php printf(__('第%d章の詳細設定', 'study-progress-tracker'), $i); ?></h5>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('章タイトル', 'study-progress-tracker'); ?></th>
                                    <td>
                                        <input type="text" 
                                               name="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>" 
                                               value="<?php echo esc_attr($chapter_title); ?>" 
                                               class="regular-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('節の数', 'study-progress-tracker'); ?></th>
                                    <td>
                                        <input type="number" 
                                               name="<?php echo $subject_key; ?>_sections_<?php echo $i; ?>" 
                                               value="<?php echo esc_attr($section_count); ?>" 
                                               min="1" max="20" 
                                               class="small-text section-count" 
                                               data-chapter="<?php echo $i; ?>" 
                                               data-subject="<?php echo $subject_key; ?>">
                                    </td>
                                </tr>
                            </table>
                            
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
                                
                                <?php 
                                for ($j = 1; $j <= $section_count; $j++):
                                    $section_title = isset($chapter_structure[$subject_key]['chapters'][$i]['section_data'][$j]['title']) ? 
                                        $chapter_structure[$subject_key]['chapters'][$i]['section_data'][$j]['title'] : '節' . $j;
                                    $item_count = isset($chapter_structure[$subject_key]['chapters'][$i]['section_data'][$j]['items']) ? 
                                        $chapter_structure[$subject_key]['chapters'][$i]['section_data'][$j]['items'] : 1;
                                ?>
                                <div class="section-details" id="section-<?php echo $subject_key; ?>-<?php echo $i; ?>-<?php echo $j; ?>-details">
                                    <h6><?php printf(__('節%dの詳細設定', 'study-progress-tracker'), $j); ?></h6>
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><?php _e('節タイトル', 'study-progress-tracker'); ?></th>
                                            <td>
                                                <input type="text" 
                                                       name="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>_section_<?php echo $j; ?>_title" 
                                                       value="<?php echo esc_attr($section_title); ?>" 
                                                       class="regular-text">
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php _e('項の数', 'study-progress-tracker'); ?></th>
                                            <td>
                                                <input type="number" 
                                                       name="<?php echo $subject_key; ?>_chapter_<?php echo $i; ?>_section_<?php echo $j; ?>_items" 
                                                       value="<?php echo esc_attr($item_count); ?>" 
                                                       min="1" max="20" 
                                                       class="small-text item-count">
                                            </td>
                                        </tr>
                                    </table>
                                    
                                    <div class="items-container">
                                        <h6><?php _e('項の詳細設定', 'study-progress-tracker'); ?></h6>
                                        <table class="wp-list-table widefat fixed striped">
                                            <thead>
                                                <tr>
                                                    <th width="10%"><?php _e('項番号', 'study-progress-tracker'); ?></th>
                                                    <th width="90%"><?php _e('項タイトル', 'study-progress-tracker'); ?></th>
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
                                                               class="regular-text">
                                                    </td>
                                                </tr>
                                                <?php endfor; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <p class="submit">
        <input type="submit" name="save_structure" class="button button-primary" value="<?php _e('科目構造を保存', 'study-progress-tracker'); ?>">
    </p>
</form>