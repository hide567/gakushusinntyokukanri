<?php
// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

// 進捗統計計算関数（重複を防ぐため条件付きで定義）
if (!function_exists('spm_calculate_subject_progress')) {
    function spm_calculate_subject_progress($subject_key, $structure, $progress_data) {
        $total_items = 0;
        $mastered_items = 0;
        
        if (!isset($structure['chapters'])) return 0;
        
        foreach ($structure['chapters'] as $chapter) {
            if (!isset($chapter['sections'])) continue;
            
            foreach ($chapter['sections'] as $section) {
                if (!isset($section['items'])) continue;
                
                foreach ($section['items'] as $item) {
                    $total_items++;
                    $key = $subject_key . '-' . $chapter['chapter_number'] . '-' . $section['section_number'] . '-' . $item['item_number'];
                    
                    if (isset($progress_data[$key]) && $progress_data[$key]['mastery']) {
                        $mastered_items++;
                    }
                }
            }
        }
        
        return $total_items > 0 ? round(($mastered_items / $total_items) * 100) : 0;
    }
}

// 現在のユーザーID取得
$user_id = get_current_user_id();

// ログインしていない場合の処理
if (!$user_id) {
    echo '<div class="spm-login-required">';
    echo '<p>学習進捗を表示するにはログインが必要です。</p>';
    echo '<a href="' . wp_login_url(get_permalink()) . '" class="button">ログイン</a>';
    echo '</div>';
    return;
}

global $wpdb;

// 科目データ取得
$subjects_query = "SELECT * FROM {$wpdb->prefix}study_subjects";
if (!empty($atts['subject'])) {
    $subjects_query .= $wpdb->prepare(" WHERE subject_key = %s", $atts['subject']);
}
$subjects_query .= " ORDER BY id";

$subjects = $wpdb->get_results($subjects_query);

if (empty($subjects)) {
    echo '<div class="spm-no-subjects">';
    echo '<p>表示する科目がありません。</p>';
    echo '</div>';
    return;
}

// 進捗データ取得
$progress_data = array();
foreach ($subjects as $subject) {
    $progress = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}study_progress 
         WHERE user_id = %d AND subject_key = %s",
        $user_id, $subject->subject_key
    ));
    
    foreach ($progress as $item) {
        $key = $subject->subject_key . '-' . $item->chapter_number . '-' . $item->section_number . '-' . $item->item_number;
        $progress_data[$key] = array(
            'understanding' => (bool) $item->understanding_level,
            'mastery' => (bool) $item->mastery_level
        );
    }
}

// 科目構造データ取得
$structure_data = array();
foreach ($subjects as $subject) {
    $chapters = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}study_chapters 
         WHERE subject_key = %s ORDER BY chapter_number",
        $subject->subject_key
    ));
    
    $structure_data[$subject->subject_key] = array('chapters' => array());
    
    foreach ($chapters as $chapter) {
        $sections = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}study_sections 
             WHERE subject_key = %s AND chapter_number = %d 
             ORDER BY section_number",
            $subject->subject_key, $chapter->chapter_number
        ));
        
        $chapter_data = array(
            'chapter_number' => $chapter->chapter_number,
            'chapter_title' => $chapter->chapter_title,
            'sections' => array()
        );
        
        foreach ($sections as $section) {
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}study_items 
                 WHERE subject_key = %s AND chapter_number = %d AND section_number = %d 
                 ORDER BY item_number",
                $subject->subject_key, $chapter->chapter_number, $section->section_number
            ));
            
            $section_data = array(
                'section_number' => $section->section_number,
                'section_title' => $section->section_title,
                'items' => array()
            );
            
            foreach ($items as $item) {
                $section_data['items'][] = array(
                    'item_number' => $item->item_number,
                    'item_title' => $item->item_title
                );
            }
            
            $chapter_data['sections'][] = $section_data;
        }
        
        $structure_data[$subject->subject_key]['chapters'][] = $chapter_data;
    }
}
?>

<div class="spm-progress-container" data-mode="<?php echo esc_attr($atts['mode']); ?>">
    
    <?php if ($atts['mode'] !== 'summary' && count($subjects) > 1): ?>
    <!-- 科目タブ -->
    <div class="spm-subject-tabs">
        <?php foreach ($subjects as $index => $subject): ?>
            <button class="spm-subject-tab <?php echo $index === 0 ? 'active' : ''; ?>" 
                    data-subject="<?php echo esc_attr($subject->subject_key); ?>"
                    style="--subject-color: <?php echo esc_attr($subject->progress_color); ?>">
                <?php echo esc_html($subject->subject_name); ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 進捗コンテンツ -->
    <div class="spm-progress-content">
        <?php if ($atts['mode'] === 'summary'): ?>
            <!-- サマリーモード -->
            <div class="spm-mode-summary">
                <?php foreach ($subjects as $subject): ?>
                    <?php 
                    $subject_structure = isset($structure_data[$subject->subject_key]) ? $structure_data[$subject->subject_key] : array('chapters' => array());
                    $overall_progress = spm_calculate_subject_progress($subject->subject_key, $subject_structure, $progress_data);
                    ?>
                    <div class="spm-subject-container" style="--subject-color: <?php echo esc_attr($subject->progress_color); ?>">
                        <div class="spm-subject-header">
                            <h3 class="spm-subject-title"><?php echo esc_html($subject->subject_name); ?></h3>
                            <div class="spm-overall-progress">
                                <div class="spm-progress-percentage"><?php echo $overall_progress; ?>%</div>
                                <div class="spm-progress-label">完了率</div>
                            </div>
                        </div>
                        
                        <div class="spm-progress-stats">
                            <?php
                            $total_items = 0;
                            $understood_items = 0;
                            $mastered_items = 0;
                            
                            foreach ($subject_structure['chapters'] as $chapter) {
                                if (!isset($chapter['sections'])) continue;
                                
                                foreach ($chapter['sections'] as $section) {
                                    if (!isset($section['items'])) continue;
                                    
                                    foreach ($section['items'] as $item) {
                                        $total_items++;
                                        $key = $subject->subject_key . '-' . $chapter['chapter_number'] . '-' . $section['section_number'] . '-' . $item['item_number'];
                                        
                                        if (isset($progress_data[$key])) {
                                            if ($progress_data[$key]['understanding']) $understood_items++;
                                            if ($progress_data[$key]['mastery']) $mastered_items++;
                                        }
                                    }
                                }
                            }
                            ?>
                            <div class="spm-stats-grid">
                                <div class="spm-stat-item">
                                    <div class="spm-stat-number"><?php echo $total_items; ?></div>
                                    <div class="spm-stat-label">総項目</div>
                                </div>
                                <div class="spm-stat-item">
                                    <div class="spm-stat-number"><?php echo $understood_items; ?></div>
                                    <div class="spm-stat-label">理解済み</div>
                                </div>
                                <div class="spm-stat-item">
                                    <div class="spm-stat-number"><?php echo $mastered_items; ?></div>
                                    <div class="spm-stat-label">習得済み</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php else: ?>
            <!-- 通常モード・コンパクトモード -->
            <?php foreach ($subjects as $index => $subject): ?>
                <?php 
                $subject_structure = isset($structure_data[$subject->subject_key]) ? $structure_data[$subject->subject_key] : array('chapters' => array());
                $overall_progress = spm_calculate_subject_progress($subject->subject_key, $subject_structure, $progress_data);
                $display_style = (count($subjects) > 1 && $index > 0) ? 'style="display: none;"' : '';
                ?>
                
                <div class="spm-subject-content" data-subject="<?php echo esc_attr($subject->subject_key); ?>" <?php echo $display_style; ?>>
                    <div class="spm-subject-container" style="--subject-color: <?php echo esc_attr($subject->progress_color); ?>">
                        <div class="spm-subject-header">
                            <h2 class="spm-subject-title"><?php echo esc_html($subject->subject_name); ?></h2>
                            <div class="spm-overall-progress">
                                <div class="spm-progress-percentage"><?php echo $overall_progress; ?>%</div>
                                <div class="spm-progress-label">全体進捗</div>
                            </div>
                        </div>
                        
                        <?php if ($atts['mode'] !== 'compact'): ?>
                        <!-- 進捗サマリー -->
                        <div class="spm-progress-summary">
                            <h3>学習進捗サマリー</h3>
                            <?php
                            $total_items = 0;
                            $understood_items = 0;
                            $mastered_items = 0;
                            
                            foreach ($subject_structure['chapters'] as $chapter) {
                                if (!isset($chapter['sections'])) continue;
                                
                                foreach ($chapter['sections'] as $section) {
                                    if (!isset($section['items'])) continue;
                                    
                                    foreach ($section['items'] as $item) {
                                        $total_items++;
                                        $key = $subject->subject_key . '-' . $chapter['chapter_number'] . '-' . $section['section_number'] . '-' . $item['item_number'];
                                        
                                        if (isset($progress_data[$key])) {
                                            if ($progress_data[$key]['understanding']) $understood_items++;
                                            if ($progress_data[$key]['mastery']) $mastered_items++;
                                        }
                                    }
                                }
                            }
                            
                            $completion_rate = $total_items > 0 ? round(($mastered_items / $total_items) * 100) : 0;
                            ?>
                            <div class="spm-summary-grid">
                                <div class="spm-summary-item">
                                    <div class="spm-summary-number"><?php echo $total_items; ?></div>
                                    <div class="spm-summary-label">総項目数</div>
                                </div>
                                <div class="spm-summary-item">
                                    <div class="spm-summary-number"><?php echo $understood_items; ?></div>
                                    <div class="spm-summary-label">理解済み</div>
                                </div>
                                <div class="spm-summary-item">
                                    <div class="spm-summary-number"><?php echo $mastered_items; ?></div>
                                    <div class="spm-summary-label">習得済み</div>
                                </div>
                                <div class="spm-summary-item">
                                    <div class="spm-summary-number"><?php echo $completion_rate; ?>%</div>
                                    <div class="spm-summary-label">完了率</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- 章リスト -->
                        <div class="spm-chapters-list">
                            <?php if (empty($subject_structure['chapters'])): ?>
                                <div class="spm-empty">この科目の構造が設定されていません。</div>
                            <?php else: ?>
                                <?php foreach ($subject_structure['chapters'] as $chapter): ?>
                                    <?php
                                    // 章の進捗計算
                                    $chapter_total = 0;
                                    $chapter_mastered = 0;
                                    
                                    if (isset($chapter['sections'])) {
                                        foreach ($chapter['sections'] as $section) {
                                            if (isset($section['items'])) {
                                                foreach ($section['items'] as $item) {
                                                    $chapter_total++;
                                                    $key = $subject->subject_key . '-' . $chapter['chapter_number'] . '-' . $section['section_number'] . '-' . $item['item_number'];
                                                    
                                                    if (isset($progress_data[$key]) && $progress_data[$key]['mastery']) {
                                                        $chapter_mastered++;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    
                                    $chapter_progress = $chapter_total > 0 ? round(($chapter_mastered / $chapter_total) * 100) : 0;
                                    ?>
                                    
                                    <div class="spm-chapter-item">
                                        <div class="spm-chapter-header" data-chapter="<?php echo $chapter['chapter_number']; ?>">
                                            <div class="spm-chapter-info">
                                                <h3 class="spm-chapter-title">第<?php echo $chapter['chapter_number']; ?>章 <?php echo esc_html($chapter['chapter_title']); ?></h3>
                                                <div class="spm-chapter-progress-bar">
                                                    <div class="spm-chapter-progress-fill" style="width: <?php echo $chapter_progress; ?>%"></div>
                                                </div>
                                                <span class="spm-progress-text"><?php echo $chapter_progress; ?>%</span>
                                            </div>
                                            <span class="spm-expand-icon">▼</span>
                                        </div>
                                        
                                        <div class="spm-chapter-content">
                                            <?php if (empty($chapter['sections'])): ?>
                                                <div class="spm-empty">この章には節が設定されていません。</div>
                                            <?php else: ?>
                                                <div class="spm-sections-grid">
                                                    <?php foreach ($chapter['sections'] as $section): ?>
                                                        <div class="spm-section-card">
                                                            <div class="spm-section-header">
                                                                第<?php echo $section['section_number']; ?>節 <?php echo esc_html($section['section_title']); ?>
                                                            </div>
                                                            
                                                            <div class="spm-items-grid">
                                                                <?php if (empty($section['items'])): ?>
                                                                    <div class="spm-empty">この節には項が設定されていません。</div>
                                                                <?php else: ?>
                                                                    <?php foreach ($section['items'] as $item): ?>
                                                                        <?php
                                                                        $item_key = $subject->subject_key . '-' . $chapter['chapter_number'] . '-' . $section['section_number'] . '-' . $item['item_number'];
                                                                        $item_progress = isset($progress_data[$item_key]) ? $progress_data[$item_key] : array('understanding' => false, 'mastery' => false);
                                                                        ?>
                                                                        
                                                                        <div class="spm-item-row">
                                                                            <div class="spm-item-title">
                                                                                項<?php echo $item['item_number']; ?>: <?php echo esc_html($item['item_title']); ?>
                                                                            </div>
                                                                            
                                                                            <div class="spm-progress-controls">
                                                                                <div class="spm-checkbox-group">
                                                                                    <label class="spm-checkbox-wrapper spm-understanding">
                                                                                        <input type="checkbox" 
                                                                                               class="spm-checkbox" 
                                                                                               data-type="understanding"
                                                                                               data-chapter="<?php echo $chapter['chapter_number']; ?>"
                                                                                               data-section="<?php echo $section['section_number']; ?>"
                                                                                               data-item="<?php echo $item['item_number']; ?>"
                                                                                               data-subject="<?php echo esc_attr($subject->subject_key); ?>"
                                                                                               <?php echo $item_progress['understanding'] ? 'checked' : ''; ?>>
                                                                                        <span class="spm-checkbox-label">理解</span>
                                                                                    </label>
                                                                                    
                                                                                    <label class="spm-checkbox-wrapper spm-mastery">
                                                                                        <input type="checkbox" 
                                                                                               class="spm-checkbox"
                                                                                               data-type="mastery" 
                                                                                               data-chapter="<?php echo $chapter['chapter_number']; ?>"
                                                                                               data-section="<?php echo $section['section_number']; ?>"
                                                                                               data-item="<?php echo $item['item_number']; ?>"
                                                                                               data-subject="<?php echo esc_attr($subject->subject_key); ?>"
                                                                                               <?php echo $item_progress['mastery'] ? 'checked' : ''; ?>>
                                                                                        <span class="spm-checkbox-label">習得</span>
                                                                                    </label>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if ($atts['mode'] !== 'summary'): ?>
    <!-- 操作ボタン -->
    <div class="spm-controls">
        <button type="button" class="button spm-save-btn" onclick="SPM.saveProgress()">
            <span class="dashicons dashicons-saved"></span> 進捗を保存
        </button>
        
        <button type="button" class="button spm-stats-btn" onclick="SPM.showStatistics()">
            <span class="dashicons dashicons-chart-bar"></span> 統計表示
        </button>
        
        <button type="button" class="button spm-export-btn" onclick="SPM.exportProgress()">
            <span class="dashicons dashicons-download"></span> エクスポート
        </button>
        
        <button type="button" class="button spm-collapse-btn" onclick="SPM.collapseAll()">
            <span class="dashicons dashicons-arrow-up-alt2"></span> 全て閉じる
        </button>
    </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
// 科目タブの切り替え機能
document.addEventListener('DOMContentLoaded', function() {
    // 科目タブのクリックイベント
    document.querySelectorAll('.spm-subject-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const subjectKey = this.dataset.subject;
            
            // アクティブタブの切り替え
            document.querySelectorAll('.spm-subject-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // コンテンツの表示切り替え
            document.querySelectorAll('.spm-subject-content').forEach(content => {
                if (content.dataset.subject === subjectKey) {
                    content.style.display = 'block';
                } else {
                    content.style.display = 'none';
                }
            });
        });
    });
    
    // 章の展開/折りたたみ
    document.querySelectorAll('.spm-chapter-header').forEach(header => {
        header.addEventListener('click', function(e) {
            // チェックボックス内のクリックは除外
            if (e.target.closest('.spm-checkbox-wrapper') || e.target.closest('.spm-checkbox')) {
                return;
            }
            
            const content = this.nextElementSibling;
            const icon = this.querySelector('.spm-expand-icon');
            
            if (content.style.display === 'none' || !content.style.display) {
                content.style.display = 'block';
                icon.textContent = '▲';
                this.classList.add('expanded');
            } else {
                content.style.display = 'none';
                icon.textContent = '▼';
                this.classList.remove('expanded');
            }
        });
    });
    
    // 進捗チェックボックスの変更処理
    document.querySelectorAll('.spm-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const type = this.dataset.type;
            const checked = this.checked;
            const itemRow = this.closest('.spm-item-row');
            
            // 習得にチェックが入った場合、理解も自動的にチェック
            if (type === 'mastery' && checked) {
                const understandingCheckbox = itemRow.querySelector('.spm-checkbox[data-type="understanding"]');
                if (understandingCheckbox && !understandingCheckbox.checked) {
                    understandingCheckbox.checked = true;
                }
            }
            
            // 理解のチェックが外された場合、習得も自動的に外す
            if (type === 'understanding' && !checked) {
                const masteryCheckbox = itemRow.querySelector('.spm-checkbox[data-type="mastery"]');
                if (masteryCheckbox && masteryCheckbox.checked) {
                    masteryCheckbox.checked = false;
                }
            }
            
            // 進捗バーの更新
            updateProgressBars();
            
            // 自動保存（2秒後）
            clearTimeout(window.autoSaveTimeout);
            window.autoSaveTimeout = setTimeout(() => {
                if (typeof SPM !== 'undefined' && SPM.saveProgress) {
                    SPM.saveProgress();
                }
            }, 2000);
        });
    });
    
    // 進捗バー更新関数
    function updateProgressBars() {
        // 章レベルの進捗バー更新
        document.querySelectorAll('.spm-chapter-item').forEach(chapterItem => {
            const masteryCheckboxes = chapterItem.querySelectorAll('.spm-checkbox[data-type="mastery"]');
            const checkedCount = chapterItem.querySelectorAll('.spm-checkbox[data-type="mastery"]:checked').length;
            
            if (masteryCheckboxes.length > 0) {
                const progress = Math.round((checkedCount / masteryCheckboxes.length) * 100);
                const progressFill = chapterItem.querySelector('.spm-chapter-progress-fill');
                const progressText = chapterItem.querySelector('.spm-progress-text');
                
                if (progressFill) progressFill.style.width = progress + '%';
                if (progressText) progressText.textContent = progress + '%';
            }
        });
        
        // 全体進捗の更新
        const allMasteryCheckboxes = document.querySelectorAll('.spm-checkbox[data-type="mastery"]');
        const allCheckedCount = document.querySelectorAll('.spm-checkbox[data-type="mastery"]:checked').length;
        
        if (allMasteryCheckboxes.length > 0) {
            const overallProgress = Math.round((allCheckedCount / allMasteryCheckboxes.length) * 100);
            const progressPercentage = document.querySelector('.spm-progress-percentage');
            if (progressPercentage) {
                progressPercentage.textContent = overallProgress + '%';
            }
        }
    }
    
    // 初期状態で進捗バーを更新
    updateProgressBars();
});

// 保存インジケーター表示関数
function showSaveIndicator(type, message) {
    // 既存のインジケーターを削除
    const existing = document.querySelector('.spm-save-indicator');
    if (existing) {
        existing.remove();
    }
    
    // 新しいインジケーターを作成
    const indicator = document.createElement('div');
    indicator.className = 'spm-save-indicator ' + type;
    indicator.textContent = message;
    document.body.appendChild(indicator);
    
    // アニメーション付きで表示
    setTimeout(() => {
        indicator.classList.add('show');
    }, 100);
    
    // 3秒後に非表示（保存中以外）
    if (type !== 'saving') {
        setTimeout(() => {
            indicator.classList.remove('show');
            setTimeout(() => {
                indicator.remove();
            }, 300);
        }, 3000);
    }
}

// グローバルSPMオブジェクトの基本実装（JavaScriptが読み込まれていない場合の代替）
if (typeof SPM === 'undefined') {
    window.SPM = {
        saveProgress: function() {
            showSaveIndicator('saving', '保存中...');
            
            // 進捗データの収集
            const progressData = {};
            document.querySelectorAll('.spm-checkbox').forEach(checkbox => {
                const subject = checkbox.dataset.subject;
                const chapter = checkbox.dataset.chapter;
                const section = checkbox.dataset.section;
                const item = checkbox.dataset.item;
                const type = checkbox.dataset.type;
                const checked = checkbox.checked;
                
                const key = subject + '-' + chapter + '-' + section + '-' + item;
                
                if (!progressData[key]) {
                    progressData[key] = {
                        subject_key: subject,
                        chapter: parseInt(chapter),
                        section: parseInt(section),
                        item: parseInt(item),
                        understanding: 0,
                        mastery: 0
                    };
                }
                
                progressData[key][type] = checked ? 1 : 0;
            });
            
            // Ajax保存処理
            const formData = new FormData();
            formData.append('action', 'save_progress');
            formData.append('progress_data', JSON.stringify(progressData));
            formData.append('nonce', '<?php echo wp_create_nonce("spm_nonce"); ?>');
            
            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSaveIndicator('success', '保存しました');
                } else {
                    showSaveIndicator('error', '保存に失敗しました');
                }
            })
            .catch(error => {
                showSaveIndicator('error', '通信エラーが発生しました');
            });
        },
        
        showStatistics: function() {
            // 統計計算
            const totalItems = document.querySelectorAll('.spm-checkbox[data-type="mastery"]').length;
            const masteredItems = document.querySelectorAll('.spm-checkbox[data-type="mastery"]:checked').length;
            const understoodItems = document.querySelectorAll('.spm-checkbox[data-type="understanding"]:checked').length;
            const completionRate = totalItems > 0 ? Math.round((masteredItems / totalItems) * 100) : 0;
            
            alert(`学習統計\n総項目数: ${totalItems}\n理解済み: ${understoodItems}\n習得済み: ${masteredItems}\n完了率: ${completionRate}%`);
        },
        
        exportProgress: function() {
            const progressData = this.collectProgressData();
            const dataStr = JSON.stringify(progressData, null, 2);
            const dataBlob = new Blob([dataStr], { type: 'application/json' });
            
            const link = document.createElement('a');
            link.href = URL.createObjectURL(dataBlob);
            link.download = 'study_progress_' + new Date().toISOString().split('T')[0] + '.json';
            link.click();
        },
        
        collapseAll: function() {
            document.querySelectorAll('.spm-chapter-content').forEach(content => {
                content.style.display = 'none';
            });
            document.querySelectorAll('.spm-expand-icon').forEach(icon => {
                icon.textContent = '▼';
            });
            document.querySelectorAll('.spm-chapter-header').forEach(header => {
                header.classList.remove('expanded');
            });
        },
        
        collectProgressData: function() {
            const progressData = {};
            document.querySelectorAll('.spm-checkbox').forEach(checkbox => {
                const subject = checkbox.dataset.subject;
                const chapter = checkbox.dataset.chapter;
                const section = checkbox.dataset.section;
                const item = checkbox.dataset.item;
                const type = checkbox.dataset.type;
                const checked = checkbox.checked;
                
                const key = subject + '-' + chapter + '-' + section + '-' + item;
                
                if (!progressData[key]) {
                    progressData[key] = {
                        subject_key: subject,
                        chapter: parseInt(chapter),
                        section: parseInt(section),
                        item: parseInt(item),
                        understanding: false,
                        mastery: false
                    };
                }
                
                progressData[key][type] = checked;
            });
            
            return progressData;
        }
    };
}
</script>

<style>
/* ショートコード固有のスタイル調整 */
.spm-controls {
    margin-top: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    text-align: center;
}

.spm-controls .button {
    margin: 0 5px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    background: #2271b1;
    color: white;
    border: none;
    border-radius: 4px;
    text-decoration: none;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.spm-controls .button:hover {
    background: #135e96;
    color: white;
}

.spm-login-required {
    text-align: center;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px dashed #ddd;
}

.spm-login-required .button {
    background: #2271b1;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 4px;
    display: inline-block;
    margin-top: 15px;
}

.spm-no-subjects {
    text-align: center;
    padding: 40px;
    background: #fff3cd;
    border: 1px solid #ffeeba;
    border-radius: 8px;
    color: #856404;
}

.spm-save-indicator {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 10px 15px;
    border-radius: 6px;
    font-size: 0.9em;
    font-weight: 500;
    z-index: 1000;
    transition: all 0.3s ease;
    transform: translateY(-100px);
    opacity: 0;
}

.spm-save-indicator.show {
    transform: translateY(0);
    opacity: 1;
}

.spm-save-indicator.success {
    background: #4CAF50;
    color: white;
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
}

.spm-save-indicator.error {
    background: #f44336;
    color: white;
    box-shadow: 0 4px 12px rgba(244, 67, 54, 0.3);
}

.spm-save-indicator.saving {
    background: #2196F3;
    color: white;
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
}

.spm-empty {
    text-align: center;
    padding: 20px;
    color: #666;
    font-style: italic;
    background: #f9f9f9;
    border-radius: 6px;
    border: 2px dashed #ddd;
    margin: 10px 0;
}

.spm-empty::before {
    content: '📝';
    font-size: 2em;
    display: block;
    margin-bottom: 10px;
    opacity: 0.5;
}

/* プログレスバーのアニメーション */
.spm-chapter-progress-fill {
    transition: width 0.5s ease;
}

.spm-progress-percentage {
    transition: all 0.3s ease;
}

/* チェックボックスの改良スタイル */
.spm-checkbox {
    appearance: none;
    width: 18px;
    height: 18px;
    border: 2px solid #ddd;
    border-radius: 3px;
    background: white;
    cursor: pointer;
    position: relative;
    transition: all 0.2s ease;
}

.spm-checkbox:checked {
    background: var(--subject-color, #4CAF50);
    border-color: var(--subject-color, #4CAF50);
}

.spm-checkbox:checked::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
    font-weight: bold;
}

.spm-checkbox:hover {
    border-color: var(--subject-color, #4CAF50);
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.spm-checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    user-select: none;
    padding: 4px 8px;
    border-radius: 4px;
    transition: background-color 0.2s ease;
}

.spm-checkbox-wrapper:hover {
    background: rgba(0, 0, 0, 0.05);
}

.spm-checkbox-label {
    font-size: 0.85em;
    color: #666;
    font-weight: 500;
}

/* 理解・習得の色分け */
.spm-understanding .spm-checkbox:checked {
    background: #2196F3;
    border-color: #2196F3;
}

.spm-understanding .spm-checkbox:hover {
    border-color: #2196F3;
    box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.2);
}

.spm-mastery .spm-checkbox:checked {
    background: #4CAF50;
    border-color: #4CAF50;
}

.spm-mastery .spm-checkbox:hover {
    border-color: #4CAF50;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

/* 科目タブの改良 */
.spm-subject-tab {
    position: relative;
    overflow: hidden;
}

.spm-subject-tab::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: var(--subject-color, #2271b1);
    transform: scaleX(0);
    transition: transform 0.3s ease;
}

.spm-subject-tab.active::before {
    transform: scaleX(1);
}

/* レスポンシブ調整 */
@media (max-width: 768px) {
    .spm-controls {
        padding: 10px;
    }
    
    .spm-controls .button {
        display: block;
        margin: 5px 0;
        width: 100%;
        justify-content: center;
    }
    
    .spm-save-indicator {
        top: 10px;
        right: 10px;
        left: 10px;
        transform: translateY(-100px);
    }
    
    .spm-save-indicator.show {
        transform: translateY(0);
    }
    
    .spm-checkbox-group {
        flex-direction: column;
        gap: 8px;
        align-items: flex-start;
    }
    
    .spm-item-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .spm-progress-controls {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .spm-login-required,
    .spm-no-subjects {
        padding: 20px;
        margin: 10px;
    }
    
    .spm-empty {
        padding: 15px;
    }
    
    .spm-controls {
        margin: 15px 0;
        padding: 10px;
    }
}

/* ダークモード対応 */
@media (prefers-color-scheme: dark) {
    .spm-login-required {
        background: #2d2d2d;
        border-color: #404040;
        color: #e0e0e0;
    }
    
    .spm-no-subjects {
        background: #3d3d3d;
        border-color: #505050;
        color: #e0e0e0;
    }
    
    .spm-empty {
        background: #2d2d2d;
        border-color: #404040;
        color: #ccc;
    }
    
    .spm-controls {
        background: #2d2d2d;
    }
    
    .spm-checkbox {
        background: #2d2d2d;
        border-color: #666;
    }
    
    .spm-checkbox-wrapper:hover {
        background: rgba(255, 255, 255, 0.1);
    }
}

/* アニメーション */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.spm-subject-container {
    animation: fadeInUp 0.3s ease-out;
}

.spm-chapter-content {
    animation: fadeInUp 0.3s ease-out;
}

/* アクセシビリティ改善 */
.spm-checkbox:focus {
    outline: 2px solid var(--subject-color, #2271b1);
    outline-offset: 2px;
}

.spm-subject-tab:focus {
    outline: 2px solid var(--subject-color, #2271b1);
    outline-offset: 2px;
}

.spm-controls .button:focus {
    outline: 2px solid #2271b1;
    outline-offset: 2px;
}

/* 印刷対応 */
@media print {
    .spm-controls,
    .spm-save-indicator {
        display: none;
    }
    
    .spm-chapter-content {
        display: block !important;
    }
    
    .spm-subject-container {
        break-inside: avoid;
        margin-bottom: 20px;
    }
    
    .spm-checkbox:checked::after {
        color: black;
    }
}
</style>