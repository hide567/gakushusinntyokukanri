<?php
/**
 * フロントエンド進捗表示テンプレート（修正版）
 * templates/progress-display.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// デバッグ情報（開発時のみ）
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo "<!-- SPT Debug: subjects=" . count($subjects) . ", structure=" . count($structure) . " -->";
}
?>

<div class="spt-progress-tracker <?php echo esc_attr($atts['style'] ?? 'default'); ?>" 
     data-interactive="<?php echo $interactive ? 'true' : 'false'; ?>">
    
    <?php if (empty($subjects)): ?>
        <div class="spt-no-data">
            <p>📚 科目が登録されていません。</p>
            <p>管理画面で科目を追加してください。</p>
        </div>
    <?php else: ?>
    
    <?php foreach ($subjects as $subject_key => $subject_name): 
        $subject_structure = $structure[$subject_key] ?? array();
        $subject_progress = $progress[$subject_key] ?? array();
        
        // 構造データの検証
        $chapters_count = intval($subject_structure['chapters'] ?? 10);
        $sections_per_chapter = intval($subject_structure['sections_per_chapter'] ?? 3);
        $items_per_section = intval($subject_structure['items_per_section'] ?? 5);
        
        // 進捗率計算
        $total_items = $chapters_count * $sections_per_chapter * $items_per_section;
        $completed_items = 0;
        
        if (!empty($subject_progress) && is_array($subject_progress)) {
            foreach ($subject_progress as $chapter_data) {
                if (is_array($chapter_data)) {
                    foreach ($chapter_data as $section_data) {
                        if (is_array($section_data)) {
                            $completed_items += count($section_data);
                        }
                    }
                }
            }
        }
        
        $percent = 0;
        if ($total_items > 0) {
            $percent = min(100, ceil(($completed_items / $total_items) * 100));
        }
        
        $subject_color = $subject_structure['color'] ?? '#4CAF50';
    ?>
    
    <div class="spt-subject" data-subject="<?php echo esc_attr($subject_key); ?>">
        
        <div class="spt-subject-header" data-subject="<?php echo esc_attr($subject_key); ?>">
            <h3 class="spt-subject-title">
                <span class="spt-subject-toggle">▶</span>
                <?php echo esc_html($subject_name); ?>
                <span class="spt-percent">(<?php echo esc_html($percent); ?>%)</span>
            </h3>
        </div>
        
        <div class="spt-subject-content" style="display: none;">
            <!-- 進捗バー -->
            <div class="spt-progress-bar">
                <div class="spt-progress-fill" 
                     style="width: <?php echo esc_attr($percent); ?>%; background-color: <?php echo esc_attr($subject_color); ?>;"></div>
            </div>
            
            <?php if ($chapters_count > 0): ?>
            <div class="spt-chapters">
                <?php 
                for ($chapter = 1; $chapter <= $chapters_count; $chapter++): 
                    $chapter_progress = $subject_progress[$chapter] ?? array();
                    $chapter_total = $sections_per_chapter * $items_per_section;
                    $chapter_completed = 0;
                    
                    if (is_array($chapter_progress)) {
                        foreach ($chapter_progress as $section_data) {
                            if (is_array($section_data)) {
                                $chapter_completed += count($section_data);
                            }
                        }
                    }
                    
                    $chapter_percent = 0;
                    if ($chapter_total > 0) {
                        $chapter_percent = ceil(($chapter_completed / $chapter_total) * 100);
                    }
                    
                    // 章の完了状態
                    $chapter_class = 'spt-chapter';
                    if ($chapter_percent >= 100) {
                        $chapter_class .= ' completed';
                        
                        // 習得チェック
                        $mastered_count = 0;
                        if (is_array($chapter_progress)) {
                            foreach ($chapter_progress as $section_data) {
                                if (is_array($section_data)) {
                                    foreach ($section_data as $item_level) {
                                        if (intval($item_level) >= 2) {
                                            $mastered_count++;
                                        }
                                    }
                                }
                            }
                        }
                        if ($mastered_count == $chapter_total) {
                            $chapter_class .= ' mastered';
                        }
                    }
                ?>
                
                <div class="<?php echo $chapter_class; ?>" data-chapter="<?php echo esc_attr($chapter); ?>">
                    <div class="spt-chapter-header">
                        <span class="spt-chapter-toggle">+</span>
                        <span class="spt-chapter-title">第<?php echo esc_html($chapter); ?>章</span>
                        <span class="spt-chapter-percent"><?php echo esc_html($chapter_percent); ?>%</span>
                    </div>
                    
                    <div class="spt-chapter-content" style="display: none;">
                        <?php for ($section = 1; $section <= $sections_per_chapter; $section++): 
                            $section_progress = $chapter_progress[$section] ?? array();
                            $section_completed = is_array($section_progress) ? count($section_progress) : 0;
                            
                            $section_percent = 0;
                            if ($items_per_section > 0) {
                                $section_percent = ceil(($section_completed / $items_per_section) * 100);
                            }
                            
                            $section_class = 'spt-section';
                            if ($section_percent >= 100) {
                                $section_class .= ' completed';
                                
                                // 習得チェック
                                $mastered_items = 0;
                                if (is_array($section_progress)) {
                                    foreach ($section_progress as $item_level) {
                                        if (intval($item_level) >= 2) {
                                            $mastered_items++;
                                        }
                                    }
                                }
                                if ($mastered_items == $items_per_section) {
                                    $section_class .= ' mastered';
                                }
                            }
                        ?>
                        
                        <div class="<?php echo $section_class; ?>" data-section="<?php echo esc_attr($section); ?>">
                            <div class="spt-section-header">
                                <span class="spt-section-title">節<?php echo esc_html($section); ?></span>
                                <span class="spt-section-percent"><?php echo esc_html($section_percent); ?>%</span>
                            </div>
                            
                            <?php if ($interactive): ?>
                            <div class="spt-items">
                                <?php for ($item = 1; $item <= $items_per_section; $item++): 
                                    $item_level = intval($section_progress[$item] ?? 0);
                                    $item_class = 'spt-item';
                                    if ($item_level >= 1) {
                                        $item_class .= ' understood';
                                    }
                                    if ($item_level >= 2) {
                                        $item_class .= ' mastered';
                                    }
                                ?>
                                
                                <div class="<?php echo $item_class; ?>" 
                                     data-subject="<?php echo esc_attr($subject_key); ?>"
                                     data-chapter="<?php echo esc_attr($chapter); ?>" 
                                     data-section="<?php echo esc_attr($section); ?>" 
                                     data-item="<?php echo esc_attr($item); ?>">
                                    <span class="spt-item-title">項<?php echo esc_html($item); ?></span>
                                    <div class="spt-item-checks">
                                        <label class="spt-check-label">
                                            <input type="checkbox" 
                                                   class="spt-check-understand" 
                                                   <?php checked($item_level >= 1); ?>>
                                            <span>理解</span>
                                        </label>
                                        <label class="spt-check-label">
                                            <input type="checkbox" 
                                                   class="spt-check-master" 
                                                   <?php checked($item_level >= 2); ?>>
                                            <span>習得</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <?php endfor; ?>
                            </div>
                            <?php else: ?>
                            <div class="spt-items-readonly">
                                <p>項目数: <?php echo esc_html($items_per_section); ?>個 / 完了: <?php echo esc_html($section_completed); ?>個</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php endfor; ?>
                    </div>
                </div>
                
                <?php endfor; ?>
            </div>
            
            <?php if ($interactive): ?>
            <div class="spt-controls">
                <button type="button" class="spt-reset-btn" data-subject="<?php echo esc_attr($subject_key); ?>">
                    この科目をリセット
                </button>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <p class="spt-no-structure">この科目の構造が設定されていません。</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php endforeach; ?>
    
    <?php endif; ?>
    
    <?php
    // 試験カウントダウン表示
    if (!empty($settings['exam_date'])):
        $exam_timestamp = strtotime($settings['exam_date']);
        $today = current_time('timestamp');
        $days_left = floor(($exam_timestamp - $today) / (60 * 60 * 24));
        
        if ($days_left >= 0):
    ?>
    <div class="spt-countdown">
        <?php echo esc_html($settings['exam_title'] ?? '試験'); ?>まであと 
        <span class="spt-countdown-days"><?php echo esc_html($days_left); ?></span> 日
    </div>
    <?php elseif ($days_left >= -30): ?>
    <div class="spt-countdown post-exam">
        <?php echo esc_html($settings['exam_title'] ?? '試験'); ?>は終了しました
    </div>
    <?php endif; endif; ?>
</div>