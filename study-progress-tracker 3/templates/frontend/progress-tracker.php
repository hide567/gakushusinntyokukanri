<?php
/**
 * 進捗トラッカーフロントエンド表示テンプレート（進捗バー設定対応版）
 */

if (!defined('ABSPATH')) {
    exit;
}

// 進捗バー設定を取得
$progress_bar_style = isset($progress_settings['progress_bar_style']) ? $progress_settings['progress_bar_style'] : 'stripes';
$progress_bar_animation = isset($progress_settings['progress_bar_animation']) ? $progress_settings['progress_bar_animation'] : 'enabled';
?>

<div class="progress-tracker-shortcode <?php echo esc_attr($style_class); ?>" 
     data-nonce="<?php echo wp_create_nonce('progress_tracker_nonce'); ?>"
     data-progress-style="<?php echo esc_attr($progress_bar_style); ?>"
     data-progress-animation="<?php echo esc_attr($progress_bar_animation); ?>">
    
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
                                            <div class="no-items-message">
                                                <?php _e('この節には項が設定されていません。', 'study-progress-tracker'); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="chapter-accordion-content" style="display: none;">
                            <div class="readonly-message">
                                <?php _e('進捗の確認・更新にはログインが必要です。', 'study-progress-tracker'); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php 
                    else:
                ?>
                    <div class="incomplete-chapter">
                        <h4><?php echo esc_html($chapter_title); ?></h4>
                        <p><?php _e('この章にはまだ詳細な学習項目が設定されていません。', 'study-progress-tracker'); ?></p>
                    </div>
                <?php 
                    endif;
                ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-chapters-message">
                <h4><?php _e('学習項目が設定されていません', 'study-progress-tracker'); ?></h4>
                <p><?php _e('この科目にはまだ章・節・項が設定されていません。管理者による設定が必要です。', 'study-progress-tracker'); ?></p>
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

<script>
// 進捗バースタイルの動的更新
jQuery(document).ready(function($) {
    // ページ読み込み時に進捗バースタイルを適用
    function applyProgressBarStyles() {
        var $container = $('.progress-tracker-shortcode');
        var style = $container.attr('data-progress-style') || 'stripes';
        var animation = $container.attr('data-progress-animation') || 'enabled';
        
        // デバッグ情報
        console.log('進捗バースタイル適用:', style, animation);
        
        // すべての進捗バーに動的クラスを適用
        $('.progress-bar-fill').each(function() {
            var $fill = $(this);
            
            // 既存のスタイルクラスを削除
            $fill.removeClass('style-stripes style-solid style-gradient style-dots');
            $fill.removeClass('animation-enabled animation-disabled animation-slow');
            
            // 新しいスタイルクラスを追加
            $fill.addClass('style-' + style);
            $fill.addClass('animation-' + animation);
        });
    }
    
    // 初期スタイル適用
    applyProgressBarStyles();
    
    // MutationObserverで動的に追加された進捗バーも監視
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length > 0) {
                $(mutation.addedNodes).find('.progress-bar-fill').each(function() {
                    applyProgressBarStyles();
                });
            }
        });
    });
    
    // 監視開始
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // 設定変更の検知（管理画面からの更新）
    $(document).on('progress-settings-updated', function(e, newSettings) {
        console.log('進捗設定更新:', newSettings);
        var $container = $('.progress-tracker-shortcode');
        
        if (newSettings.progress_bar_style) {
            $container.attr('data-progress-style', newSettings.progress_bar_style);
        }
        if (newSettings.progress_bar_animation) {
            $container.attr('data-progress-animation', newSettings.progress_bar_animation);
        }
        
        applyProgressBarStyles();
    });
});
</script>

<style>
/* 動的スタイルクラス */
.progress-bar-fill.style-stripes::after {
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

.progress-bar-fill.style-solid::after {
    display: none;
}

.progress-bar-fill.style-gradient {
    background: linear-gradient(45deg, currentColor 0%, rgba(255,255,255,0.3) 50%, currentColor 100%) !important;
}

.progress-bar-fill.style-gradient::after {
    display: none;
}

.progress-bar-fill.style-dots::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background-image: radial-gradient(rgba(255,255,255,0.3) 2px, transparent 2px);
    background-size: 8px 8px;
}

/* アニメーション制御 */
.progress-bar-fill.animation-enabled.style-stripes::after {
    animation: progress-bar-stripes 1s linear infinite;
}

.progress-bar-fill.animation-enabled.style-dots::after {
    animation: progress-bar-dots 2s linear infinite;
}

.progress-bar-fill.animation-slow.style-stripes::after {
    animation: progress-bar-stripes 3s linear infinite;
}

.progress-bar-fill.animation-slow.style-dots::after {
    animation: progress-bar-dots 4s linear infinite;
}

.progress-bar-fill.animation-disabled::after {
    animation: none !important;
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

/* 設定反映確認用のデバッグスタイル（開発時のみ） */
.debug-progress-style .progress-bar-fill.style-stripes {
    border: 2px solid red;
}

.debug-progress-style .progress-bar-fill.style-solid {
    border: 2px solid blue;
}

.debug-progress-style .progress-bar-fill.style-gradient {
    border: 2px solid green;
}

.debug-progress-style .progress-bar-fill.style-dots {
    border: 2px solid orange;
}
</style>