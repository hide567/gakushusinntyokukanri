<?php
/**
 * 柔軟構造対応フロントエンド進捗表示テンプレート（完全修正版）
 * templates/progress-display-flexible.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="spt-progress-tracker spt-flexible <?php echo esc_attr(isset($atts['style']) ? $atts['style'] : 'default'); ?>" 
     data-interactive="<?php echo $interactive ? 'true' : 'false'; ?>">
    
    <?php if (empty($subjects) || !is_array($subjects)): ?>
        <div class="spt-no-data">
            <p>📚 科目が登録されていません。</p>
            <p>管理画面で科目を追加してください。</p>
        </div>
    <?php else: ?>
    
    <?php foreach ($subjects as $subject_key => $subject_name): 
        $subject_structure = isset($structure[$subject_key]) && is_array($structure[$subject_key]) ? $structure[$subject_key] : array('chapters' => array());
        $subject_progress = isset($progress[$subject_key]) && is_array($progress[$subject_key]) ? $progress[$subject_key] : array();
        
        // 進捗率計算（新構造対応）
        $total_items = 0;
        $completed_items = 0;
        
        if (!empty($subject_structure['chapters']) && is_array($subject_structure['chapters'])) {
            foreach ($subject_structure['chapters'] as $chapter_id => $chapter_data) {
                if (!is_array($chapter_data)) continue;
                if (!empty($chapter_data['sections']) && is_array($chapter_data['sections'])) {
                    foreach ($chapter_data['sections'] as $section_id => $section_data) {
                        if (!is_array($section_data)) continue;
                        if (!empty($section_data['items']) && is_array($section_data['items'])) {
                            $total_items += count($section_data['items']);
                            if (isset($subject_progress[$chapter_id][$section_id]) && is_array($subject_progress[$chapter_id][$section_id])) {
                                $completed_items += count($subject_progress[$chapter_id][$section_id]);
                            }
                        }
                    }
                }
            }
        }
        
        $percent = $total_items > 0 ? min(100, ceil(($completed_items / $total_items) * 100)) : 0;
        $subject_color = isset($subject_structure['color']) ? $subject_structure['color'] : '#4CAF50';
    ?>
    
    <!-- 科目：進捗バー付きヘッダー -->
    <div class="spt-subject spt-subject-flexible" data-subject="<?php echo esc_attr($subject_key); ?>">
        
        <div class="spt-subject-header" data-subject="<?php echo esc_attr($subject_key); ?>">
            <div class="spt-subject-title-container">
                <h3 class="spt-subject-title">
                    <span class="spt-subject-toggle">▶</span>
                    <?php echo esc_html($subject_name); ?>
                    <span class="spt-percent"><?php echo esc_html($percent); ?>%</span>
                </h3>
                
                <!-- ヘッダー内進捗バー -->
                <div class="spt-progress-bar-header">
                    <div class="spt-progress-fill-header" 
                         style="width: <?php echo esc_attr($percent); ?>%; background-color: <?php echo esc_attr($subject_color); ?>;"></div>
                </div>
            </div>
        </div>
        
        <!-- 科目コンテンツ：デフォルトで非表示 -->
        <div class="spt-subject-content" style="display: none;">
            
            <?php if (!empty($subject_structure['chapters']) && is_array($subject_structure['chapters'])): ?>
            <div class="spt-chapters">
                <?php foreach ($subject_structure['chapters'] as $chapter_id => $chapter_data): 
                    if (!is_array($chapter_data)) continue;
                    
                    $chapter_progress = isset($subject_progress[$chapter_id]) && is_array($subject_progress[$chapter_id]) ? $subject_progress[$chapter_id] : array();
                    
                    // 章の進捗計算
                    $chapter_total = 0;
                    $chapter_completed = 0;
                    $chapter_mastered = 0;
                    
                    if (!empty($chapter_data['sections']) && is_array($chapter_data['sections'])) {
                        foreach ($chapter_data['sections'] as $section_id => $section_data) {
                            if (!is_array($section_data)) continue;
                            if (!empty($section_data['items']) && is_array($section_data['items'])) {
                                $chapter_total += count($section_data['items']);
                                
                                if (isset($chapter_progress[$section_id]) && is_array($chapter_progress[$section_id])) {
                                    $chapter_completed += count($chapter_progress[$section_id]);
                                    // 習得レベルのカウント
                                    foreach ($chapter_progress[$section_id] as $item_level) {
                                        if (intval($item_level) >= 2) {
                                            $chapter_mastered++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    $chapter_percent = $chapter_total > 0 ? ceil(($chapter_completed / $chapter_total) * 100) : 0;
                    
                    // 章の完了状態
                    $chapter_class = 'spt-chapter';
                    if ($chapter_percent >= 100) {
                        $chapter_class .= ' completed';
                        if ($chapter_mastered == $chapter_total) {
                            $chapter_class .= ' mastered';
                        }
                    }
                ?>
                
                <!-- 章：進捗バー付きヘッダー -->
                <div class="<?php echo esc_attr($chapter_class); ?>" data-chapter="<?php echo esc_attr($chapter_id); ?>">
                    <div class="spt-chapter-header">
                        <div class="spt-chapter-title-container">
                            <div class="spt-chapter-top">
                                <span class="spt-chapter-toggle">+</span>
                                <span class="spt-chapter-title"><?php echo esc_html(isset($chapter_data['name']) ? $chapter_data['name'] : '第' . $chapter_id . '章'); ?></span>
                                <span class="spt-chapter-percent"><?php echo esc_html($chapter_percent); ?>%</span>
                            </div>
                            
                            <!-- 章内進捗バー -->
                            <div class="spt-progress-bar-chapter">
                                <div class="spt-progress-fill-chapter" 
                                     style="width: <?php echo esc_attr($chapter_percent); ?>%; background-color: <?php echo esc_attr($subject_color); ?>;"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 章コンテンツ：デフォルトで非表示 -->
                    <div class="spt-chapter-content" style="display: none;">
                        <?php if (!empty($chapter_data['sections']) && is_array($chapter_data['sections'])): ?>
                            <?php foreach ($chapter_data['sections'] as $section_id => $section_data): 
                                if (!is_array($section_data)) continue;
                                
                                $section_progress = isset($chapter_progress[$section_id]) && is_array($chapter_progress[$section_id]) ? $chapter_progress[$section_id] : array();
                                $section_total = !empty($section_data['items']) && is_array($section_data['items']) ? count($section_data['items']) : 0;
                                $section_completed = count($section_progress);
                                
                                $section_percent = $section_total > 0 ? ceil(($section_completed / $section_total) * 100) : 0;
                                
                                // 節の完了状態
                                $section_class = 'spt-section';
                                if ($section_percent >= 100) {
                                    $section_class .= ' completed';
                                    
                                    // 習得チェック
                                    $mastered_items = 0;
                                    foreach ($section_progress as $item_level) {
                                        if (intval($item_level) >= 2) {
                                            $mastered_items++;
                                        }
                                    }
                                    if ($mastered_items == $section_total) {
                                        $section_class .= ' mastered';
                                    }
                                }
                            ?>
                            
                            <div class="<?php echo esc_attr($section_class); ?>" data-section="<?php echo esc_attr($section_id); ?>">
                                <div class="spt-section-header">
                                    <div class="spt-section-title-container">
                                        <span class="spt-section-title"><?php echo esc_html(isset($section_data['name']) ? $section_data['name'] : '節' . $section_id); ?></span>
                                        <span class="spt-section-percent"><?php echo esc_html($section_percent); ?>%</span>
                                    </div>
                                    
                                    <!-- 節内進捗バー -->
                                    <div class="spt-progress-bar-section">
                                        <div class="spt-progress-fill-section" 
                                             style="width: <?php echo esc_attr($section_percent); ?>%; background-color: <?php echo esc_attr($subject_color); ?>;"></div>
                                    </div>
                                </div>
                                
                                <?php if ($interactive && !empty($section_data['items']) && is_array($section_data['items'])): ?>
                                <div class="spt-items">
                                    <?php foreach ($section_data['items'] as $item_id => $item_name): 
                                        $item_level = intval(isset($section_progress[$item_id]) ? $section_progress[$item_id] : 0);
                                        $item_class = 'spt-item';
                                        if ($item_level >= 1) {
                                            $item_class .= ' understood';
                                        }
                                        if ($item_level >= 2) {
                                            $item_class .= ' mastered';
                                        }
                                    ?>
                                    
                                    <div class="<?php echo esc_attr($item_class); ?>" 
                                         data-subject="<?php echo esc_attr($subject_key); ?>"
                                         data-chapter="<?php echo esc_attr($chapter_id); ?>" 
                                         data-section="<?php echo esc_attr($section_id); ?>" 
                                         data-item="<?php echo esc_attr($item_id); ?>">
                                        <span class="spt-item-title"><?php echo esc_html($item_name); ?></span>
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
                                    
                                    <?php endforeach; ?>
                                </div>
                                <?php elseif (!empty($section_data['items']) && is_array($section_data['items'])): ?>
                                <div class="spt-items-readonly">
                                    <p>項目数: <?php echo count($section_data['items']); ?>個 / 完了: <?php echo $section_completed; ?>個</p>
                                    <div class="spt-items-list">
                                        <?php foreach ($section_data['items'] as $item_id => $item_name): 
                                            $item_level = intval(isset($section_progress[$item_id]) ? $section_progress[$item_id] : 0);
                                            $item_status = '';
                                            if ($item_level >= 2) {
                                                $item_status = ' ✓✓';
                                            } elseif ($item_level >= 1) {
                                                $item_status = ' ✓';
                                            }
                                        ?>
                                            <span class="spt-item-readonly"><?php echo esc_html($item_name) . $item_status; ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="spt-items-readonly">
                                    <p style="color: #666; font-style: italic; text-align: center; padding: 15px;">
                                        この節には項目がありません。
                                    </p>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="spt-no-sections">この章には節が設定されていません。</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php endforeach; ?>
            </div>
            
            <?php if ($interactive): ?>
            <div class="spt-controls">
                <button type="button" class="spt-reset-btn" data-subject="<?php echo esc_attr($subject_key); ?>">
                    この科目をリセット
                </button>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <p class="spt-no-structure">この科目には章が設定されていません。</p>
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
        <?php echo esc_html(isset($settings['exam_title']) ? $settings['exam_title'] : '試験'); ?>まであと 
        <span class="spt-countdown-days"><?php echo esc_html($days_left); ?></span> 日
    </div>
    <?php elseif ($days_left >= -30): ?>
    <div class="spt-countdown post-exam">
        <?php echo esc_html(isset($settings['exam_title']) ? $settings['exam_title'] : '試験'); ?>は終了しました
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
<!-- デバッグ情報 -->
<div style="margin-top: 20px; padding: 10px; background: #f0f0f0; border-radius: 5px; font-size: 12px; color: #666;">
    <strong>デバッグ情報:</strong>
    科目数: <?php echo count($subjects); ?> | 
    構造データ: <?php echo !empty($structure) ? '有効' : '無効'; ?> | 
    進捗データ: <?php echo !empty($progress) ? '有効' : '無効'; ?>
</div>
<?php endif; ?>