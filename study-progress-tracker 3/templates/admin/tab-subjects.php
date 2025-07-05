<?php
/**
 * 管理画面 - 科目管理タブ（コンパクト化対応版）
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="admin-section">
    <!-- 科目追加のアコーディオンヘッダー（コンパクト版） -->
    <div class="subject-add-header" 
         data-action="toggle-add-form" 
         style="cursor: pointer; padding: 15px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px; user-select: none; display: inline-block; max-width: 200px;">
        <h3 style="margin: 0; display: flex; align-items: center; border-bottom: none; padding-bottom: 0;">
            <span class="subject-add-toggle-icon" style="margin-right: 10px; font-weight: bold; width: 20px; height: 20px; line-height: 20px; text-align: center; background-color: #eee; border-radius: 50%; font-family: monospace; font-size: 14px;">+</span>
            <?php _e('科目の追加', 'study-progress-tracker'); ?>
        </h3>
    </div>
    
    <!-- 科目追加フォーム（デフォルトで非表示） -->
    <div class="subject-add-content" style="display: none; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;">
        <p><?php _e('試験や資格ごとに科目を追加できます。', 'study-progress-tracker'); ?></p>
        
        <form method="post" action="">
            <?php wp_nonce_field('spt_add_subject'); ?>
            
            <div class="form-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <label for="new_subject_key"><?php _e('科目キー（英数字）', 'study-progress-tracker'); ?></label>
                    <input type="text" id="new_subject_key" name="new_subject_key" class="regular-text" pattern="[a-zA-Z0-9_]+" style="width: 100%;">
                    <p class="description"><?php _e('システム内で使用される英数字のID（例：math, english など）', 'study-progress-tracker'); ?></p>
                </div>
                
                <div>
                    <label for="new_subject_name"><?php _e('科目名', 'study-progress-tracker'); ?></label>
                    <input type="text" id="new_subject_name" name="new_subject_name" class="regular-text" style="width: 100%;">
                    <p class="description"><?php _e('表示される科目名（例：数学，英語 など）', 'study-progress-tracker'); ?></p>
                </div>
                
                <div>
                    <label for="new_subject_chapters"><?php _e('初期章数', 'study-progress-tracker'); ?></label>
                    <input type="number" id="new_subject_chapters" name="new_subject_chapters" value="10" min="1" max="50" class="small-text" style="width: 100%;">
                    <p class="description"><?php _e('この科目の章数', 'study-progress-tracker'); ?></p>
                </div>
                
                <div>
                    <label for="progress_color"><?php _e('進捗バーの色', 'study-progress-tracker'); ?></label>
                    <input type="color" id="progress_color" name="progress_color" value="#4CAF50" style="width: 100%; height: 40px;">
                    <p class="description"><?php _e('この科目の進捗バーに使用する色', 'study-progress-tracker'); ?></p>
                </div>
            </div>
            
            <p class="submit" style="margin-top: 20px;">
                <input type="submit" name="add_subject" class="button button-primary" value="<?php _e('科目を追加', 'study-progress-tracker'); ?>">
            </p>
        </form>
    </div>
</div>

<div class="admin-section">
    <h3><?php _e('科目の管理', 'study-progress-tracker'); ?></h3>
    
    <?php if (!empty($subjects)): ?>
        <form method="post" action="">
            <?php wp_nonce_field('spt_delete_subject'); ?>
            
            <!-- レスポンシブテーブル -->
            <div class="table-responsive">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('科目キー', 'study-progress-tracker'); ?></th>
                            <th><?php _e('科目名', 'study-progress-tracker'); ?></th>
                            <th><?php _e('章数', 'study-progress-tracker'); ?></th>
                            <th><?php _e('進捗', 'study-progress-tracker'); ?></th>
                            <th><?php _e('進捗バーの色', 'study-progress-tracker'); ?></th>
                            <th><?php _e('操作', 'study-progress-tracker'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $key => $name): 
                            $total_chapters = isset($chapter_structure[$key]['total']) ? $chapter_structure[$key]['total'] : 0;
                            $percent = isset($progress_data[$key]['percent']) ? $progress_data[$key]['percent'] : 0;
                            $color = isset($chapter_structure[$key]['color']) ? $chapter_structure[$key]['color'] : '#4CAF50';
                        ?>
                            <tr>
                                <td data-label="<?php _e('科目キー', 'study-progress-tracker'); ?>"><?php echo esc_html($key); ?></td>
                                <td data-label="<?php _e('科目名', 'study-progress-tracker'); ?>"><?php echo esc_html($name); ?></td>
                                <td data-label="<?php _e('章数', 'study-progress-tracker'); ?>"><?php echo esc_html($total_chapters); ?></td>
                                <td data-label="<?php _e('進捗', 'study-progress-tracker'); ?>">
                                    <div class="progress-mini-bar">
                                        <div class="progress-mini-fill" style="width:<?php echo esc_attr($percent); ?>%; background-color:<?php echo esc_attr($color); ?>;"></div>
                                    </div>
                                    <span class="progress-percent"><?php echo esc_html($percent); ?>%</span>
                                </td>
                                <td data-label="<?php _e('進捗バーの色', 'study-progress-tracker'); ?>">
                                    <span class="color-indicator" style="display:inline-block; width:20px; height:20px; background-color:<?php echo esc_attr($color); ?>; border-radius:3px; border: 1px solid #ddd;"></span>
                                </td>
                                <td data-label="<?php _e('操作', 'study-progress-tracker'); ?>">
                                    <div class="action-buttons">
                                        <button type="button" class="button button-small edit-subject" 
                                                data-key="<?php echo esc_attr($key); ?>" 
                                                data-name="<?php echo esc_attr($name); ?>" 
                                                data-color="<?php echo esc_attr($color); ?>">
                                            <?php _e('編集', 'study-progress-tracker'); ?>
                                        </button>
                                        <button type="submit" name="delete_subject" value="<?php echo esc_attr($key); ?>" 
                                                class="button button-small button-link-delete" 
                                                onclick="return confirm('<?php _e('この科目を削除してもよろしいですか？関連するすべての進捗データも削除されます。', 'study-progress-tracker'); ?>');">
                                            <?php _e('削除', 'study-progress-tracker'); ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    <?php else: ?>
        <p><?php _e('現在、科目がありません。「科目を追加」フォームから新しい科目を追加してください。', 'study-progress-tracker'); ?></p>
    <?php endif; ?>
    
    <!-- 科目編集モーダル -->
    <div id="edit-subject-modal" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <h3><?php _e('科目の編集', 'study-progress-tracker'); ?></h3>
            <form method="post" action="">
                <?php wp_nonce_field('spt_edit_subject'); ?>
                <input type="hidden" id="edit_subject_key" name="edit_subject_key" value="">
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('科目名', 'study-progress-tracker'); ?></th>
                        <td>
                            <input type="text" id="edit_subject_name" name="edit_subject_name" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('進捗バーの色', 'study-progress-tracker'); ?></th>
                        <td>
                            <input type="color" id="edit_progress_color" name="edit_progress_color" value="#4CAF50">
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="edit_subject" class="button button-primary" value="<?php _e('更新', 'study-progress-tracker'); ?>">
                    <button type="button" class="button close-modal"><?php _e('キャンセル', 'study-progress-tracker'); ?></button>
                </p>
            </form>
        </div>
    </div>
</div>