<?php
// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// 科目一覧取得
$subjects = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}study_subjects ORDER BY id");

if (empty($subjects)) {
    echo '<div class="wrap"><h1>科目構造設定</h1>';
    echo '<div class="notice notice-warning"><p>科目が登録されていません。まず<a href="' . admin_url('admin.php?page=study-progress-subjects') . '">科目管理</a>から科目を追加してください。</p></div>';
    echo '</div>';
    return;
}

// POSTデータ処理 - nonce名を修正
if ($_POST && wp_verify_nonce($_POST['_wpnonce'], 'spm_structure_save')) {
    
    if (isset($_POST['save_structure'])) {
        $subject_key = sanitize_text_field($_POST['subject_key']);
        $chapters_data = $_POST['chapters'];
        
        // 既存データを削除
        $wpdb->delete($wpdb->prefix . 'study_items', array('subject_key' => $subject_key));
        $wpdb->delete($wpdb->prefix . 'study_sections', array('subject_key' => $subject_key));
        $wpdb->delete($wpdb->prefix . 'study_chapters', array('subject_key' => $subject_key));
        
        // 新しいデータを挿入
        foreach ($chapters_data as $chapter_num => $chapter_data) {
            if (empty($chapter_data['title'])) continue;
            
            // 章を挿入
            $wpdb->insert(
                $wpdb->prefix . 'study_chapters',
                array(
                    'subject_key' => $subject_key,
                    'chapter_number' => $chapter_num,
                    'chapter_title' => sanitize_text_field($chapter_data['title']),
                    'total_sections' => isset($chapter_data['sections']) ? count($chapter_data['sections']) : 0
                )
            );
            
            // 節を挿入
            if (isset($chapter_data['sections'])) {
                foreach ($chapter_data['sections'] as $section_num => $section_data) {
                    if (empty($section_data['title'])) continue;
                    
                    $wpdb->insert(
                        $wpdb->prefix . 'study_sections',
                        array(
                            'subject_key' => $subject_key,
                            'chapter_number' => $chapter_num,
                            'section_number' => $section_num,
                            'section_title' => sanitize_text_field($section_data['title']),
                            'total_items' => isset($section_data['items']) ? count($section_data['items']) : 0
                        )
                    );
                    
                    // 項を挿入
                    if (isset($section_data['items'])) {
                        foreach ($section_data['items'] as $item_num => $item_data) {
                            if (empty($item_data['title'])) continue;
                            
                            $wpdb->insert(
                                $wpdb->prefix . 'study_items',
                                array(
                                    'subject_key' => $subject_key,
                                    'chapter_number' => $chapter_num,
                                    'section_number' => $section_num,
                                    'item_number' => $item_num,
                                    'item_title' => sanitize_text_field($item_data['title'])
                                )
                            );
                        }
                    }
                }
            }
        }
        
        echo '<div class="notice notice-success"><p>科目構造を保存しました！</p></div>';
    }
}

// 選択された科目のデータ取得
$selected_subject = isset($_GET['subject']) ? sanitize_text_field($_GET['subject']) : '';
if (empty($selected_subject) && !empty($subjects)) {
    $selected_subject = $subjects[0]->subject_key;
}

$current_subject = null;
foreach ($subjects as $subject) {
    if ($subject->subject_key === $selected_subject) {
        $current_subject = $subject;
        break;
    }
}

// 既存の構造データ取得
$existing_chapters = array();
$existing_sections = array();
$existing_items = array();

if ($current_subject) {
    $chapters = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}study_chapters WHERE subject_key = %s ORDER BY chapter_number",
        $selected_subject
    ));
    
    $sections = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}study_sections WHERE subject_key = %s ORDER BY chapter_number, section_number",
        $selected_subject
    ));
    
    $items = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}study_items WHERE subject_key = %s ORDER BY chapter_number, section_number, item_number",
        $selected_subject
    ));
    
    foreach ($chapters as $chapter) {
        $existing_chapters[$chapter->chapter_number] = $chapter;
    }
    
    foreach ($sections as $section) {
        $existing_sections[$section->chapter_number][$section->section_number] = $section;
    }
    
    foreach ($items as $item) {
        $existing_items[$item->chapter_number][$item->section_number][$item->item_number] = $item;
    }
}
?>

<div class="wrap">
    <h1>科目構造設定</h1>
    
    <!-- 科目選択 -->
    <div class="spm-subject-selector">
        <h2>設定する科目を選択</h2>
        <div class="spm-subject-tabs">
            <?php foreach ($subjects as $subject): ?>
                <a href="?page=study-progress-structure&subject=<?php echo $subject->subject_key; ?>" 
                   class="spm-tab <?php echo ($subject->subject_key === $selected_subject) ? 'active' : ''; ?>"
                   style="border-bottom: 3px solid <?php echo $subject->progress_color; ?>">
                    <?php echo esc_html($subject->subject_name); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($current_subject): ?>
    <div class="spm-structure-manager">
        <div class="spm-section">
            <div class="spm-section-header">
                <h2 style="color: <?php echo $current_subject->progress_color; ?>">
                    <?php echo esc_html($current_subject->subject_name); ?> の構造設定
                </h2>
                <p>各科目の章・節・項の構造を設定します。展開ボタンをクリックして節と項を設定できます。</p>
            </div>
            
            <form method="post" id="structure-form">
                <?php wp_nonce_field('spm_structure_save'); ?>
                <input type="hidden" name="subject_key" value="<?php echo $selected_subject; ?>">
                
                <div class="spm-structure-container">
                    <div class="spm-chapters-container">
                        <?php for ($chapter = 1; $chapter <= $current_subject->total_chapters; $chapter++): ?>
                            <div class="spm-chapter-card" data-chapter="<?php echo $chapter; ?>">
                                <div class="spm-chapter-header">
                                    <div class="spm-chapter-info">
                                        <h3>第<?php echo $chapter; ?>章</h3>
                                        <input type="text" 
                                               name="chapters[<?php echo $chapter; ?>][title]" 
                                               class="spm-chapter-title"
                                               placeholder="章のタイトルを入力"
                                               value="<?php echo isset($existing_chapters[$chapter]) ? esc_attr($existing_chapters[$chapter]->chapter_title) : ''; ?>">
                                    </div>
                                    
                                    <div class="spm-chapter-controls">
                                        <span class="spm-section-count">
                                            節数: <span id="section-count-<?php echo $chapter; ?>">
                                                <?php echo isset($existing_sections[$chapter]) ? count($existing_sections[$chapter]) : 2; ?>
                                            </span>
                                        </span>
                                        <button type="button" class="button spm-toggle-btn" onclick="toggleChapter(<?php echo $chapter; ?>)">
                                            <span class="dashicons dashicons-arrow-down"></span> 展開
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="spm-sections-container" id="sections-<?php echo $chapter; ?>" style="display: none;">
                                    <div class="spm-sections-list" id="sections-list-<?php echo $chapter; ?>">
                                        <?php 
                                        $section_count = isset($existing_sections[$chapter]) ? count($existing_sections[$chapter]) : 2;
                                        for ($section = 1; $section <= max($section_count, 2); $section++): 
                                        ?>
                                            <div class="spm-section-card" data-section="<?php echo $section; ?>">
                                                <div class="spm-section-header">
                                                    <div class="spm-section-info">
                                                        <h4>第<?php echo $section; ?>節</h4>
                                                        <input type="text" 
                                                               name="chapters[<?php echo $chapter; ?>][sections][<?php echo $section; ?>][title]" 
                                                               class="spm-section-title"
                                                               placeholder="節のタイトルを入力"
                                                               value="<?php echo isset($existing_sections[$chapter][$section]) ? esc_attr($existing_sections[$chapter][$section]->section_title) : ''; ?>">
                                                    </div>
                                                    
                                                    <div class="spm-section-controls">
                                                        <span class="spm-item-count">
                                                            項数: <span id="item-count-<?php echo $chapter; ?>-<?php echo $section; ?>">
                                                                <?php echo isset($existing_items[$chapter][$section]) ? count($existing_items[$chapter][$section]) : 2; ?>
                                                            </span>
                                                        </span>
                                                        <button type="button" class="button button-small spm-toggle-btn" onclick="toggleSection(<?php echo $chapter; ?>, <?php echo $section; ?>)">
                                                            <span class="dashicons dashicons-arrow-down"></span> 展開
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <div class="spm-items-container" id="items-<?php echo $chapter; ?>-<?php echo $section; ?>" style="display: none;">
                                                    <div class="spm-items-list" id="items-list-<?php echo $chapter; ?>-<?php echo $section; ?>">
                                                        <?php 
                                                        $item_count = isset($existing_items[$chapter][$section]) ? count($existing_items[$chapter][$section]) : 2;
                                                        for ($item = 1; $item <= max($item_count, 2); $item++): 
                                                        ?>
                                                            <div class="spm-item-row">
                                                                <label>項<?php echo $item; ?></label>
                                                                <input type="text" 
                                                                       name="chapters[<?php echo $chapter; ?>][sections][<?php echo $section; ?>][items][<?php echo $item; ?>][title]" 
                                                                       class="spm-item-title"
                                                                       placeholder="項のタイトルを入力"
                                                                       value="<?php echo isset($existing_items[$chapter][$section][$item]) ? esc_attr($existing_items[$chapter][$section][$item]->item_title) : ''; ?>">
                                                            </div>
                                                        <?php endfor; ?>
                                                    </div>
                                                    
                                                    <div class="spm-items-controls">
                                                        <button type="button" class="button button-small" onclick="addItem(<?php echo $chapter; ?>, <?php echo $section; ?>)">
                                                            <span class="dashicons dashicons-plus-alt"></span> 項を追加
                                                        </button>
                                                        <button type="button" class="button button-small" onclick="removeItem(<?php echo $chapter; ?>, <?php echo $section; ?>)">
                                                            <span class="dashicons dashicons-minus"></span> 項を削除
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                    
                                    <div class="spm-sections-controls">
                                        <button type="button" class="button" onclick="addSection(<?php echo $chapter; ?>)">
                                            <span class="dashicons dashicons-plus-alt"></span> 節を追加
                                        </button>
                                        <button type="button" class="button" onclick="removeSection(<?php echo $chapter; ?>)">
                                            <span class="dashicons dashicons-minus"></span> 節を削除
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="spm-save-section">
                    <button type="submit" name="save_structure" class="button button-primary button-large">
                        <span class="dashicons dashicons-saved"></span> 構造を保存
                    </button>
                    <p class="description">※保存すると既存の構造データは上書きされます。</p>
                </div>
            </form>
        </div>

        <!-- プリセット構造 -->
        <div class="spm-section">
            <h2>プリセット構造</h2>
            <p>よく使用される科目構造を素早く設定できます。</p>
            
            <div class="spm-presets">
                <?php if ($selected_subject === 'kenpo'): ?>
                    <div class="spm-preset-card">
                        <h4>憲法 標準構造</h4>
                        <button type="button" class="button" onclick="loadPreset('kenpo_standard')">この構造を読み込む</button>
                        <ul class="spm-preset-preview">
                            <li>第1章 憲法総論
                                <ul>
                                    <li>第1節 憲法の意味・分類</li>
                                    <li>第2節 立憲主義</li>
                                </ul>
                            </li>
                            <li>第2章 基本的人権
                                <ul>
                                    <li>第1節 基本権総論</li>
                                    <li>第2節 包括的基本権</li>
                                    <li>第3節 平等権</li>
                                    <li>第4節 自由権</li>
                                    <li>第5節 社会権</li>
                                </ul>
                            </li>
                            <li>第3章 統治機構
                                <ul>
                                    <li>第1節 権力分立</li>
                                    <li>第2節 国会</li>
                                    <li>第3節 内閣</li>
                                    <li>第4節 裁判所</li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                <?php elseif ($selected_subject === 'gyosei'): ?>
                    <div class="spm-preset-card">
                        <h4>行政法 標準構造</h4>
                        <button type="button" class="button" onclick="loadPreset('gyosei_standard')">この構造を読み込む</button>
                        <ul class="spm-preset-preview">
                            <li>第1章 行政法の一般的な法理論</li>
                            <li>第2章 行政手続法</li>
                            <li>第3章 行政不服審査法</li>
                            <li>第4章 行政事件訴訟法</li>
                            <li>第5章 国家賠償法</li>
                            <li>第6章 地方自治法</li>
                            <li>第7章 情報公開法・個人情報保護法</li>
                        </ul>
                    </div>
                <?php elseif ($selected_subject === 'minpo'): ?>
                    <div class="spm-preset-card">
                        <h4>民法 標準構造</h4>
                        <button type="button" class="button" onclick="loadPreset('minpo_standard')">この構造を読み込む</button>
                        <ul class="spm-preset-preview">
                            <li>第1章 総則</li>
                            <li>第2章 物権</li>
                            <li>第3章 債権総論</li>
                            <li>第4章 債権各論</li>
                            <li>第5章 親族</li>
                            <li>第6章 相続</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.spm-subject-selector {
    margin-bottom: 20px;
}

.spm-subject-tabs {
    display: flex;
    gap: 0;
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
}

.spm-tab {
    padding: 12px 20px;
    text-decoration: none;
    background: #f1f1f1;
    border: 1px solid #ddd;
    border-bottom: none;
    color: #666;
    transition: all 0.3s ease;
}

.spm-tab.active {
    background: white;
    color: #2271b1;
    font-weight: 600;
}

.spm-tab:hover {
    background: #e8e8e8;
    color: #2271b1;
}

.spm-structure-manager {
    max-width: 1200px;
}

.spm-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.spm-section-header h2 {
    margin-top: 0;
    border-bottom: 2px solid currentColor;
    padding-bottom: 10px;
}

.spm-chapters-container {
    display: grid;
    gap: 15px;
}

.spm-chapter-card {
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    background: #f9f9f9;
}

.spm-chapter-header {
    padding: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f1f1f1;
    border-radius: 6px 6px 0 0;
}

.spm-chapter-info {
    display: flex;
    align-items: center;
    gap: 15px;
    flex: 1;
}

.spm-chapter-info h3 {
    margin: 0;
    color: #2271b1;
    min-width: 80px;
}

.spm-chapter-title {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    font-weight: 500;
}

.spm-chapter-controls {
    display: flex;
    align-items: center;
    gap: 15px;
}

.spm-section-count,
.spm-item-count {
    font-size: 0.9em;
    color: #666;
    white-space: nowrap;
}

.spm-sections-container {
    padding: 15px;
}

.spm-sections-list {
    display: grid;
    gap: 10px;
    margin-bottom: 15px;
}

.spm-section-card {
    border: 1px solid #ddd;
    border-radius: 6px;
    background: white;
}

.spm-section-header {
    padding: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8f9fa;
    border-radius: 5px 5px 0 0;
}

.spm-section-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.spm-section-info h4 {
    margin: 0;
    color: #2271b1;
    min-width: 60px;
    font-size: 14px;
}

.spm-section-title {
    flex: 1;
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.spm-section-controls {
    display: flex;
    align-items: center;
    gap: 10px;
}

.spm-items-container {
    padding: 12px;
    background: white;
}

.spm-items-list {
    display: grid;
    gap: 8px;
    margin-bottom: 10px;
}

.spm-item-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.spm-item-row label {
    min-width: 40px;
    font-size: 13px;
    color: #666;
    font-weight: 500;
}

.spm-item-title {
    flex: 1;
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
}

.spm-sections-controls,
.spm-items-controls {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding-top: 10px;
    border-top: 1px solid #f0f0f0;
}

.spm-save-section {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
    margin-top: 20px;
}

.spm-presets {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.spm-preset-card {
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 15px;
    background: #f9f9f9;
}

.spm-preset-card h4 {
    margin: 0 0 10px 0;
    color: #2271b1;
}

.spm-preset-preview {
    margin: 10px 0;
    font-size: 0.9em;
    color: #666;
}

.spm-preset-preview ul {
    margin: 5px 0;
    padding-left: 20px;
}

@media (max-width: 768px) {
    .spm-chapter-header,
    .spm-section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .spm-chapter-info,
    .spm-section-info {
        width: 100%;
    }
    
    .spm-chapter-controls,
    .spm-section-controls {
        width: 100%;
        justify-content: space-between;
    }
    
    .spm-subject-tabs {
        flex-wrap: wrap;
    }
}
</style>

<script>
function toggleChapter(chapterNum) {
    const container = document.getElementById('sections-' + chapterNum);
    const button = container.previousElementSibling.querySelector('.spm-toggle-btn');
    const icon = button.querySelector('.dashicons');
    
    if (container.style.display === 'none') {
        container.style.display = 'block';
        icon.className = 'dashicons dashicons-arrow-up';
        button.innerHTML = '<span class="dashicons dashicons-arrow-up"></span> 閉じる';
    } else {
        container.style.display = 'none';
        icon.className = 'dashicons dashicons-arrow-down';
        button.innerHTML = '<span class="dashicons dashicons-arrow-down"></span> 展開';
    }
}

function toggleSection(chapterNum, sectionNum) {
    const container = document.getElementById('items-' + chapterNum + '-' + sectionNum);
    const button = container.previousElementSibling.querySelector('.spm-toggle-btn');
    const icon = button.querySelector('.dashicons');
    
    if (container.style.display === 'none') {
        container.style.display = 'block';
        icon.className = 'dashicons dashicons-arrow-up';
        button.innerHTML = '<span class="dashicons dashicons-arrow-up"></span> 閉じる';
    } else {
        container.style.display = 'none';
        icon.className = 'dashicons dashicons-arrow-down';
        button.innerHTML = '<span class="dashicons dashicons-arrow-down"></span> 展開';
    }
}

function addSection(chapterNum) {
    const sectionsList = document.getElementById('sections-list-' + chapterNum);
    const currentSections = sectionsList.querySelectorAll('.spm-section-card').length;
    const newSectionNum = currentSections + 1;
    
    const sectionHTML = createSectionHTML(chapterNum, newSectionNum);
    sectionsList.insertAdjacentHTML('beforeend', sectionHTML);
    updateSectionCount(chapterNum);
}

function removeSection(chapterNum) {
    const sectionsList = document.getElementById('sections-list-' + chapterNum);
    const sections = sectionsList.querySelectorAll('.spm-section-card');
    
    if (sections.length > 1) {
        sections[sections.length - 1].remove();
        updateSectionCount(chapterNum);
    }
}

function addItem(chapterNum, sectionNum) {
    const itemsList = document.getElementById('items-list-' + chapterNum + '-' + sectionNum);
    const currentItems = itemsList.querySelectorAll('.spm-item-row').length;
    const newItemNum = currentItems + 1;
    
    const itemHTML = `
        <div class="spm-item-row">
            <label>項${newItemNum}</label>
            <input type="text" name="chapters[${chapterNum}][sections][${sectionNum}][items][${newItemNum}][title]" class="spm-item-title" placeholder="項のタイトルを入力">
        </div>
    `;
    
    itemsList.insertAdjacentHTML('beforeend', itemHTML);
    updateItemCount(chapterNum, sectionNum);
}

function removeItem(chapterNum, sectionNum) {
    const itemsList = document.getElementById('items-list-' + chapterNum + '-' + sectionNum);
    const items = itemsList.querySelectorAll('.spm-item-row');
    
    if (items.length > 1) {
        items[items.length - 1].remove();
        updateItemCount(chapterNum, sectionNum);
    }
}

function createSectionHTML(chapterNum, sectionNum) {
    return `
        <div class="spm-section-card" data-section="${sectionNum}">
            <div class="spm-section-header">
                <div class="spm-section-info">
                    <h4>第${sectionNum}節</h4>
                    <input type="text" name="chapters[${chapterNum}][sections][${sectionNum}][title]" class="spm-section-title" placeholder="節のタイトルを入力">
                </div>
                <div class="spm-section-controls">
                    <span class="spm-item-count">項数: <span id="item-count-${chapterNum}-${sectionNum}">2</span></span>
                    <button type="button" class="button button-small spm-toggle-btn" onclick="toggleSection(${chapterNum}, ${sectionNum})">
                        <span class="dashicons dashicons-arrow-down"></span> 展開
                    </button>
                </div>
            </div>
            <div class="spm-items-container" id="items-${chapterNum}-${sectionNum}" style="display: none;">
                <div class="spm-items-list" id="items-list-${chapterNum}-${sectionNum}">
                    <div class="spm-item-row">
                        <label>項1</label>
                        <input type="text" name="chapters[${chapterNum}][sections][${sectionNum}][items][1][title]" class="spm-item-title" placeholder="項のタイトルを入力">
                    </div>
                    <div class="spm-item-row">
                        <label>項2</label>
                        <input type="text" name="chapters[${chapterNum}][sections][${sectionNum}][items][2][title]" class="spm-item-title" placeholder="項のタイトルを入力">
                    </div>
                </div>
                <div class="spm-items-controls">
                    <button type="button" class="button button-small" onclick="addItem(${chapterNum}, ${sectionNum})">
                        <span class="dashicons dashicons-plus-alt"></span> 項を追加
                    </button>
                    <button type="button" class="button button-small" onclick="removeItem(${chapterNum}, ${sectionNum})">
                        <span class="dashicons dashicons-minus"></span> 項を削除
                    </button>
                </div>
            </div>
        </div>
    `;
}

function updateSectionCount(chapterNum) {
    const sectionsList = document.getElementById('sections-list-' + chapterNum);
    const count = sectionsList.querySelectorAll('.spm-section-card').length;
    document.getElementById('section-count-' + chapterNum).textContent = count;
}

function updateItemCount(chapterNum, sectionNum) {
    const itemsList = document.getElementById('items-list-' + chapterNum + '-' + sectionNum);
    const count = itemsList.querySelectorAll('.spm-item-row').length;
    document.getElementById('item-count-' + chapterNum + '-' + sectionNum).textContent = count;
}

function loadPreset(presetType) {
    if (confirm('現在の設定内容は失われます。プリセット構造を読み込みますか？')) {
        alert('プリセット機能は実装中です。手動で設定してください。');
    }
}
</script>