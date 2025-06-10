<?php
/**
 * 管理画面 - 科目管理タブ
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="admin-section">
    <h3><?php _e('科目の追加', 'study-progress-tracker'); ?></h3>
    <p><?php _e('試験や資格ごとに科目を追加できます。', 'study-progress-tracker'); ?></p>
    
    <form method="post" action="">
        <?php wp_nonce_field('spt_add_subject'); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('科目キー（英数字）', 'study-progress-tracker'); ?></th>
                <td>
                    <input type="text" name="new_subject_key" class="regular-text" pattern="[a-zA-Z0-9_]+">
                    <p class="description"><?php _e('システム内で使用される英数字のID（例：math, english など）', 'study-progress-tracker'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('科目名', 'study-progress-tracker'); ?></th>
                <td>
                    <input type="text" name="new_subject_name" class="regular-text">
                    <p class="description"><?php _e('表示される科目名（例：数学，英語 など）', 'study-progress-tracker'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('初期章数', 'study-progress-tracker'); ?></th>
                <td>
                    <input type="number" name="new_subject_chapters" value="10" min="1" max="50" class="small-text">
                    <p class="description"><?php _e('この科目の章数', 'study-progress-tracker'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('進捗バーの色', 'study-progress-tracker'); ?></th>
                <td>
                    <input type="color" name="progress_color" value="#4CAF50">
                    <p class="description"><?php _e('この科目の進捗バーに使用する色', 'study-progress-tracker'); ?></p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="add_subject" class="button button-primary" value="<?php _e('科目を追加', 'study-progress-tracker'); ?>">
        </p>
    </form>
</div>

<div class="admin-section">
    <h3><?php _e('科目の管理', 'study-progress-tracker'); ?></h3>
    
    <?php if (!empty($subjects)): ?>
        <form method="post" action="">
            <?php wp_nonce_field('spt_delete_subject'); ?>
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
                            <td><?php echo esc_html($key); ?></td>
                            <td><?php echo esc_html($name); ?></td>
                            <td><?php echo esc_html($total_chapters); ?></td>
                            <td>
                                <div class="progress-mini-bar">
                                    <div class="progress-mini-fill" style="width:<?php echo esc_attr($percent); ?>%; background-color:<?php echo esc_attr($color); ?>;"></div>
                                </div>
                                <?php echo esc_html($percent); ?>%
                            </td>
                            <td><span style="display:inline-block; width:20px; height:20px; background-color:<?php echo esc_attr($color); ?>; border-radius:3px;"></span></td>
                            <td>
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
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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