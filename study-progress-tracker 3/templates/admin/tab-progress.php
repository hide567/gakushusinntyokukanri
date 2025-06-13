<?php
/**
 * 管理画面 - 進捗管理タブ（進捗バー設定追加・保存修正版）
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<form method="post" action="">
    <?php wp_nonce_field('spt_save_progress'); ?>
    
    <div style="display: flex; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <h3 style="margin: 0;"><?php _e('学習進捗の管理', 'study-progress-tracker'); ?></h3>
        
        <!-- デザイン設定ボタン -->
        <span class="design-settings-toggle" style="font-size: 14px; font-weight: normal; cursor: pointer; color: #0073aa; display: flex; align-items: center;">
            <span class="dashicons dashicons-admin-appearance"></span> <?php _e('デザイン設定', 'study-progress-tracker'); ?>
        </span>
        
        <!-- リセットボタン -->
        <button type="button" class="button reset-progress-button" style="margin-left: auto;">
            <?php _e('進捗をリセット', 'study-progress-tracker'); ?>
        </button>
    </div>
    
    <!-- デザイン設定パネル -->
    <div class="design-settings-panel" style="display: none; background: #f9f9f9; padding: 20px; border: 1px solid #e1e1e1; margin-bottom: 20px; border-radius: 5px;">
        <form method="post" action="">
            <?php wp_nonce_field('spt_save_check_settings'); ?>
            
            <h4 style="margin-top: 0;"><?php _e('進捗表示のカスタマイズ', 'study-progress-tracker'); ?></h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
                
                <!-- チェック状態の色設定 -->
                <div class="color-settings-section">
                    <h5><?php _e('チェック状態の色設定', 'study-progress-tracker'); ?></h5>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div>
                            <label for="first_check_color"><?php _e('理解チェック色', 'study-progress-tracker'); ?></label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="color" id="first_check_color" name="first_check_color" 
                                       value="<?php echo esc_attr($progress_settings['first_check_color']); ?>" 
                                       style="width: 60px; height: 40px; border: 1px solid #ddd; border-radius: 3px;">
                                <span style="font-size: 13px; color: #666;">
                                    <?php _e('理解レベル1の項目背景色', 'study-progress-tracker'); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div>
                            <label for="second_check_color"><?php _e('習得チェック色', 'study-progress-tracker'); ?></label>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="color" id="second_check_color" name="second_check_color" 
                                       value="<?php echo esc_attr($progress_settings['second_check_color']); ?>" 
                                       style="width: 60px; height: 40px; border: 1px solid #ddd; border-radius: 3px;">
                                <span style="font-size: 13px; color: #666;">
                                    <?php _e('習得レベル2の項目背景色', 'study-progress-tracker'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 進捗バーの設定 -->
                <div class="progress-bar-settings-section">
                    <h5><?php _e('進捗バーの設定', 'study-progress-tracker'); ?></h5>
                    <div style="display: flex; flex-direction: column; gap: 15px;">
                        <div>
                            <label for="progress_bar_style"><?php _e('進捗バーのスタイル', 'study-progress-tracker'); ?></label>
                            <select id="progress_bar_style" name="progress_bar_style" style="width: 100%; padding: 6px 8px; border: 1px solid #ddd; border-radius: 3px;">
                                <option value="stripes" <?php selected(isset($progress_settings['progress_bar_style']) ? $progress_settings['progress_bar_style'] : 'stripes', 'stripes'); ?>>
                                    <?php _e('ストライプ（縞模様）', 'study-progress-tracker'); ?>
                                </option>
                                <option value="solid" <?php selected(isset($progress_settings['progress_bar_style']) ? $progress_settings['progress_bar_style'] : 'stripes', 'solid'); ?>>
                                    <?php _e('ソリッド（単色）', 'study-progress-tracker'); ?>
                                </option>
                                <option value="gradient" <?php selected(isset($progress_settings['progress_bar_style']) ? $progress_settings['progress_bar_style'] : 'stripes', 'gradient'); ?>>
                                    <?php _e('グラデーション', 'study-progress-tracker'); ?>
                                </option>
                                <option value="dots" <?php selected(isset($progress_settings['progress_bar_style']) ? $progress_settings['progress_bar_style'] : 'stripes', 'dots'); ?>>
                                    <?php _e('ドット模様', 'study-progress-tracker'); ?>
                                </option>
                            </select>
                            <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">
                                <?php _e('進捗バーの見た目を選択します', 'study-progress-tracker'); ?>
                            </p>
                        </div>
                        
                        <div>
                            <label for="progress_bar_animation"><?php _e('アニメーション', 'study-progress-tracker'); ?></label>
                            <select id="progress_bar_animation" name="progress_bar_animation" style="width: 100%; padding: 6px 8px; border: 1px solid #ddd; border-radius: 3px;">
                                <option value="enabled" <?php selected(isset($progress_settings['progress_bar_animation']) ? $progress_settings['progress_bar_animation'] : 'enabled', 'enabled'); ?>>
                                    <?php _e('有効', 'study-progress-tracker'); ?>
                                </option>
                                <option value="disabled" <?php selected(isset($progress_settings['progress_bar_animation']) ? $progress_settings['progress_bar_animation'] : 'enabled', 'disabled'); ?>>
                                    <?php _e('無効', 'study-progress-tracker'); ?>
                                </option>
                                <option value="slow" <?php selected(isset($progress_settings['progress_bar_animation']) ? $progress_settings['progress_bar_animation'] : 'enabled', 'slow'); ?>>
                                    <?php _e('スロー', 'study-progress-tracker'); ?>
                                </option>
                            </select>
                            <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">
                                <?php _e('進捗バーのアニメーション速度を設定します', 'study-progress-tracker'); ?>
                            </p>
                        </div>
                        
                        <!-- プレビュー -->
                        <div class="progress-bar-preview">
                            <label><?php _e('プレビュー', 'study-progress-tracker'); ?></label>
                            <div class="progress-bar-container" style="height: 20px; background-color: #f1f1f1; border-radius: 10px; overflow: hidden; margin-top: 5px;">
                                <div class="progress-bar-fill preview-bar" style="width: 65%; height: 100%; background-color: #4CAF50; position: relative; transition: width 0.5s ease;">
                                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); color: white; font-size: 12px; font-weight: bold;">65%</span>
                                </div>
                            </div>
                            <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">
                                <?php _e('設定を変更すると即座にプレビューに反映されます', 'study-progress-tracker'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <p class="submit" style="margin-top: 20px;">
                <input type="submit" name="save_check_settings" class="button button-primary" value="<?php _e('デザイン設定を保存', 'study-progress-tracker'); ?>">
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
    
    <!-- 科目タブ -->
    <div class="progress-tabs" data-tab-type="progress">
        <?php 
        $first = true;
        foreach ($subjects as $subject_key => $subject_name): 
            $active_class = $first ? 'active' : '';
            $first = false;
        ?>
            <div class="progress-tab <?php echo $active_class; ?>" 
                 data-subject="<?php echo esc_attr($subject_key); ?>"
                 data-tab-id="progress-<?php echo esc_attr($subject_key); ?>">
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
                
                <div class="progress-bar-container dynamic-progress-bar">
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
                                if (isset($chapter_data['section_data'])) {
                                    $total_sections = count($chapter_data['section_data']);
                                    $completed_sections = count($progress_data[$subject_key]['chapters'][$chapter_id]);
                                    $chapter_completed = ($completed_sections == $total_sections);
                                    
                                    // 習得状況をチェック
                                    $mastered_count = 0;
                                    foreach ($progress_data[$subject_key]['chapters'][$chapter_id] as $section_id => $section_progress) {
                                        if (is_array($section_progress) && isset($section_progress['items'])) {
                                            $total_items = isset($chapter_data['section_data'][$section_id]['item_data']) ? 
                                                count($chapter_data['section_data'][$section_id]['item_data']) : 0;
                                            $mastered_items = 0;
                                            foreach ($section_progress['items'] as $item_level) {
                                                if ($item_level >= 2) $mastered_items++;
                                            }
                                            if ($total_items > 0 && $mastered_items == $total_items) {
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
                            
                            // 章データが正しく設定されている場合のみ表示
                            if (isset($chapter_data['section_data']) && is_array($chapter_data['section_data'])):
                        ?>
                            <div class="chapter-accordion-item progress-chapter-item" 
                                 data-chapter="<?php echo esc_attr($chapter_id); ?>" 
                                 data-subject="<?php echo esc_attr($subject_key); ?>"
                                 data-item-type="chapter">
                                <div class="chapter-accordion-header progress-chapter-header" style="<?php echo $chapter_style; ?>">
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
                                                ?>
                                                    <p style="padding: 10px; color: #666; font-style: italic;">
                                                        <?php _e('この節には項が設定されていません。「科目構造設定」タブで項を設定してください。', 'study-progress-tracker'); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php 
                            else:
                        ?>
                            <div class="chapter-accordion-item incomplete-chapter" style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-bottom: 10px;">
                                <p style="margin: 0; color: #856404;">
                                    <strong><?php echo esc_html($chapter_title); ?></strong><br>
                                    <?php _e('この章には節・項が設定されていません。「科目構造設定」タブで詳細を設定してください。', 'study-progress-tracker'); ?>
                                </p>
                            </div>
                        <?php 
                            endif;
                        ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 15px 0; color: #721c24;">
                        <p style="margin: 0;">
                            <strong><?php _e('注意:', 'study-progress-tracker'); ?></strong>
                            <?php _e('この科目には章が設定されていません。「科目構造設定」タブで章・節・項を設定してください。', 'study-progress-tracker'); ?>
                        </p>
                    </div>
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
    // デザイン設定の表示/非表示
    $('.design-settings-toggle').on('click', function(e) {
        e.preventDefault();
        $('.design-settings-panel').slideToggle(300);
    });
    
    // プログレスバーのプレビュー更新
    function updateProgressBarPreview() {
        var style = $('#progress_bar_style').val();
        var animation = $('#progress_bar_animation').val();
        var $preview = $('.preview-bar');
        
        // 既存のスタイルクラスを削除
        $preview.removeClass('stripes-style solid-style gradient-style dots-style');
        $preview.removeClass('animation-enabled animation-disabled animation-slow');
        
        // 新しいスタイルを適用
        $preview.addClass(style + '-style');
        $preview.addClass('animation-' + animation);
        
        // プレビューのアニメーション
        $preview.css('width', '0%').animate({width: '65%'}, 800);
    }
    
    // プレビュー更新のイベントリスナー
    $('#progress_bar_style, #progress_bar_animation').on('change', updateProgressBarPreview);
    
    // 初期プレビュー設定
    updateProgressBarPreview();
    
    // リセット機能
    $('.reset-progress-button').on('click', function(e) {
        e.preventDefault();
        $('.reset-confirmation').slideDown();
    });
    
    $('.cancel-reset').on('click', function(e) {
        e.preventDefault();
        $('.reset-confirmation').slideUp();
        $('input[name="confirm_reset"]').prop('checked', false);
    });
    
    // フォーム送信前の確認とデバッグ
    $('form').on('submit', function(e) {
        if ($(this).find('input[name="save_progress"]').length) {
            console.log('進捗保存フォーム送信中...');
            
            // チェックされたアイテムをログ出力
            var checkedItems = [];
            $(this).find('input[type="checkbox"]:checked').each(function() {
                checkedItems.push($(this).attr('name') + ' = ' + $(this).val());
            });
            console.log('チェック済み項目:', checkedItems);
            
            if (checkedItems.length === 0) {
                if (!confirm('チェックされた項目がありません。このまま保存しますか？')) {
                    e.preventDefault();
                    return false;
                }
            }
        }
        
        // リセット時の確認
        if ($(this).find('input[name="reset_progress"]').length) {
            if (!$(this).find('input[name="confirm_reset"]').is(':checked')) {
                alert('リセットを確認するチェックボックスにチェックを入れてください。');
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>

<style>
/* デザイン設定パネルのスタイル */
.design-settings-toggle:hover {
    color: #005a87 !important;
    text-decoration: underline;
}

.design-settings-panel {
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.design-settings-panel h4 {
    color: #23282d;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 8px;
}

.design-settings-panel h5 {
    color: #32373c;
    margin-bottom: 15px;
    font-size: 14px;
}

/* 進捗バープレビューのスタイル */
.progress-bar-preview {
    background: #fff;
    padding: 15px;
    border-radius: 5px;
    border: 1px solid #ddd;
}

/* 動的進捗バーのスタイル */
.dynamic-progress-bar .progress-bar-fill {
    transition: all 0.5s ease;
    position: relative;
    overflow: hidden;
}

/* ストライプスタイル */
.stripes-style::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background-image: linear-gradient(
        -45deg,
        rgba(255,255,255,.2) 25%,
        transparent 25%,
        transparent 50%,
        rgba(255,255,255,.2) 50%,
        rgba(255,255,255,.2) 75%,
        transparent 75%,
        transparent
    );
    background-size: 20px 20px;
}

.stripes-style.animation-enabled::after {
    animation: progress-bar-stripes 1s linear infinite;
}

.stripes-style.animation-slow::after {
    animation: progress-bar-stripes 3s linear infinite;
}

/* ソリッドスタイル */
.solid-style {
    background: currentColor !important;
}

/* グラデーションスタイル */
.gradient-style {
    background: linear-gradient(45deg, currentColor 0%, rgba(255,255,255,0.3) 50%, currentColor 100%) !important;
}

/* ドットスタイル */
.dots-style::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background-image: radial-gradient(rgba(255,255,255,0.3) 2px, transparent 2px);
    background-size: 8px 8px;
}

.dots-style.animation-enabled::after {
    animation: progress-bar-dots 2s linear infinite;
}

.dots-style.animation-slow::after {
    animation: progress-bar-dots 4s linear infinite;
}

/* アニメーション定義 */
@keyframes progress-bar-stripes {
    0% { background-position: 20px 20px; }
    100% { background-position: 0 0; }
}

@keyframes progress-bar-dots {
    0% { background-position: 0 0; }
    100% { background-position: 8px 8px; }
}

/* アニメーション無効 */
.animation-disabled::after {
    animation: none !important;
}

/* タブクリックイベントの競合を防ぐための修正 */
.progress-tab {
    position: relative;
    z-index: 10;
    user-select: none;
    pointer-events: auto;
    cursor: pointer;
}

.progress-tab:hover {
    background-color: #e8e8e8 !important;
}

.progress-tab.active {
    background-color: #fff !important;
    border-bottom-color: #fff !important;
    margin-bottom: -1px;
    font-weight: bold;
    z-index: 11;
}

/* 章アコーディオンとタブの分離強化 */
.progress-chapter-header {
    position: relative;
    z-index: 5;
    cursor: pointer;
}

.progress-chapter-header:hover {
    background-color: #f0f0f0 !important;
}

/* レスポンシブ対応 */
@media (max-width: 782px) {
    .design-settings-panel > div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
    
    div[style*="display: flex"] {
        flex-direction: column !important;
        align-items: flex-start !important;
    }
    
    .reset-progress-button {
        margin-left: 0 !important;
        margin-top: 10px;
    }
    
    .progress-tabs {
        flex-direction: column;
        border-bottom: none;
    }
    
    .progress-tab {
        margin-right: 0;
        margin-bottom: 5px;
        border: 1px solid #ddd;
        border-radius: 3px;
        width: 100%;
        text-align: center;
    }
    
    .item-row {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .item-title {
        margin-bottom: 10px;
    }
    
    .item-checkboxes {
        width: 100%;
        justify-content: flex-start;
    }
}
</style>