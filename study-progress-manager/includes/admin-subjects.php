<?php
// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// POSTデータ処理
if ($_POST) {
    // nonce検証 - 修正: 正しいnonce名を使用
    if (isset($_POST['add_subject']) && wp_verify_nonce($_POST['_wpnonce'], 'spm_add_subject')) {
        $subject_key = sanitize_text_field($_POST['subject_key']);
        $subject_name = sanitize_text_field($_POST['subject_name']);
        $total_chapters = intval($_POST['total_chapters']);
        $progress_color = sanitize_hex_color($_POST['progress_color']);
        
        if ($subject_key && $subject_name && $total_chapters > 0) {
            $result = $wpdb->insert(
                $wpdb->prefix . 'study_subjects',
                array(
                    'subject_key' => $subject_key,
                    'subject_name' => $subject_name,
                    'total_chapters' => $total_chapters,
                    'progress_color' => $progress_color ?: '#4CAF50'
                )
            );
            
            if ($result) {
                echo '<div class="notice notice-success"><p>科目を追加しました！</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>エラー: 科目の追加に失敗しました。科目キーが重複している可能性があります。</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>エラー: 全ての項目を正しく入力してください。</p></div>';
        }
    }
    
    if (isset($_POST['update_subject']) && wp_verify_nonce($_POST['_wpnonce'], 'spm_update_subject')) {
        $subject_id = intval($_POST['subject_id']);
        $subject_name = sanitize_text_field($_POST['subject_name']);
        $total_chapters = intval($_POST['total_chapters']);
        $progress_color = sanitize_hex_color($_POST['progress_color']);
        
        $result = $wpdb->update(
            $wpdb->prefix . 'study_subjects',
            array(
                'subject_name' => $subject_name,
                'total_chapters' => $total_chapters,
                'progress_color' => $progress_color
            ),
            array('id' => $subject_id)
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>科目を更新しました！</p></div>';
        }
    }
    
    if (isset($_POST['delete_subject']) && wp_verify_nonce($_POST['_wpnonce'], 'spm_delete_subject')) {
        $subject_id = intval($_POST['subject_id']);
        
        // 関連データも削除
        $subject = $wpdb->get_row($wpdb->prepare("SELECT subject_key FROM {$wpdb->prefix}study_subjects WHERE id = %d", $subject_id));
        
        if ($subject) {
            $wpdb->delete($wpdb->prefix . 'study_progress', array('subject_key' => $subject->subject_key));
            $wpdb->delete($wpdb->prefix . 'study_items', array('subject_key' => $subject->subject_key));
            $wpdb->delete($wpdb->prefix . 'study_sections', array('subject_key' => $subject->subject_key));
            $wpdb->delete($wpdb->prefix . 'study_chapters', array('subject_key' => $subject->subject_key));
            $wpdb->delete($wpdb->prefix . 'study_subjects', array('id' => $subject_id));
            
            echo '<div class="notice notice-success"><p>科目と関連データを削除しました。</p></div>';
        }
    }
}

// 科目一覧取得
$subjects = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}study_subjects ORDER BY id");
?>

<div class="wrap">
    <h1>科目管理</h1>
    
    <div class="spm-subjects-manager">
        
        <!-- 新規科目追加 -->
        <div class="spm-section spm-add-subject">
            <h2>新しい科目を追加</h2>
            <form method="post" class="spm-add-form">
                <?php wp_nonce_field('spm_add_subject'); ?>
                
                <div class="spm-form-grid">
                    <div class="spm-form-group">
                        <label for="subject_key">科目キー（英数字）</label>
                        <input type="text" id="subject_key" name="subject_key" required
                               placeholder="例: kenpo, gyosei, minpo"
                               pattern="[a-zA-Z0-9_]+"
                               title="英数字とアンダースコアのみ使用可能">
                        <small>システム内で使用される英数字のID（例: kenpo, gyosei, minpo など）</small>
                    </div>
                    
                    <div class="spm-form-group">
                        <label for="subject_name">科目名</label>
                        <input type="text" id="subject_name" name="subject_name" required
                               placeholder="例: 憲法, 行政法, 民法">
                        <small>表示される科目名（例: 憲法, 行政法, 民法 など）</small>
                    </div>
                    
                    <div class="spm-form-group">
                        <label for="total_chapters">初期章数</label>
                        <input type="number" id="total_chapters" name="total_chapters" min="1" max="20" value="3" required>
                        <small>この科目の章数</small>
                    </div>
                    
                    <div class="spm-form-group">
                        <label for="progress_color">進捗バーの色</label>
                        <input type="color" id="progress_color" name="progress_color" value="#4CAF50">
                        <small>この科目の進捗バーに使用する色</small>
                    </div>
                </div>
                
                <button type="submit" name="add_subject" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt"></span> 科目を追加
                </button>
            </form>
        </div>

        <!-- 科目一覧 -->
        <div class="spm-section">
            <h2>登録済み科目一覧</h2>
            
            <?php if ($subjects): ?>
                <div class="spm-subjects-list">
                    <?php foreach ($subjects as $subject): ?>
                        <div class="spm-subject-card" data-subject-id="<?php echo $subject->id; ?>">
                            <div class="spm-subject-header" style="border-left: 5px solid <?php echo $subject->progress_color; ?>">
                                <h3><?php echo esc_html($subject->subject_name); ?></h3>
                                <span class="spm-subject-key">ID: <?php echo esc_html($subject->subject_key); ?></span>
                                <span class="spm-chapter-count"><?php echo $subject->total_chapters; ?>章構成</span>
                                
                                <div class="spm-subject-actions">
                                    <button type="button" class="button spm-edit-btn" onclick="toggleEditForm(<?php echo $subject->id; ?>)">
                                        <span class="dashicons dashicons-edit"></span> 編集
                                    </button>
                                    <button type="button" class="button spm-delete-btn" onclick="confirmDelete(<?php echo $subject->id; ?>, '<?php echo esc_js($subject->subject_name); ?>')">
                                        <span class="dashicons dashicons-trash"></span> 削除
                                    </button>
                                </div>
                            </div>
                            
                            <!-- 編集フォーム（非表示） -->
                            <div class="spm-edit-form" id="edit-form-<?php echo $subject->id; ?>" style="display: none;">
                                <form method="post">
                                    <?php wp_nonce_field('spm_update_subject'); ?>
                                    <input type="hidden" name="subject_id" value="<?php echo $subject->id; ?>">
                                    
                                    <div class="spm-edit-grid">
                                        <div class="spm-form-group">
                                            <label>科目名</label>
                                            <input type="text" name="subject_name" value="<?php echo esc_attr($subject->subject_name); ?>" required>
                                        </div>
                                        
                                        <div class="spm-form-group">
                                            <label>章数</label>
                                            <input type="number" name="total_chapters" value="<?php echo $subject->total_chapters; ?>" min="1" max="20" required>
                                        </div>
                                        
                                        <div class="spm-form-group">
                                            <label>進捗バーの色</label>
                                            <input type="color" name="progress_color" value="<?php echo $subject->progress_color; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="spm-edit-actions">
                                        <button type="submit" name="update_subject" class="button button-primary">更新</button>
                                        <button type="button" class="button" onclick="toggleEditForm(<?php echo $subject->id; ?>)">キャンセル</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- 削除フォーム（非表示） -->
                            <form method="post" id="delete-form-<?php echo $subject->id; ?>" style="display: none;">
                                <?php wp_nonce_field('spm_delete_subject'); ?>
                                <input type="hidden" name="subject_id" value="<?php echo $subject->id; ?>">
                                <input type="hidden" name="delete_subject" value="1">
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="spm-no-subjects">
                    <p>まだ科目が登録されていません。上記のフォームから新しい科目を追加してください。</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- 一括登録機能 -->
        <div class="spm-section">
            <h2>一括登録機能</h2>
            <div class="spm-bulk-import">
                <p>よく使用される科目セットを一括で登録できます。</p>
                
                <div class="spm-preset-buttons">
                    <button type="button" class="button" onclick="importPreset('gyoseishoshi')">
                        行政書士試験セット
                    </button>
                    <button type="button" class="button" onclick="importPreset('takken')">
                        宅建試験セット
                    </button>
                    <button type="button" class="button" onclick="importPreset('fp')">
                        FP試験セット
                    </button>
                </div>
                
                <div id="preset-preview" class="spm-preset-preview" style="display: none;">
                    <h4>登録される科目:</h4>
                    <div id="preset-list"></div>
                    <form method="post" id="bulk-import-form">
                        <?php wp_nonce_field('spm_bulk_import'); ?>
                        <input type="hidden" name="bulk_import" value="1">
                        <input type="hidden" name="preset_data" id="preset-data" value="">
                        <button type="submit" class="button button-primary">一括登録実行</button>
                        <button type="button" class="button" onclick="cancelImport()">キャンセル</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 一括登録処理
if (isset($_POST['bulk_import']) && wp_verify_nonce($_POST['_wpnonce'], 'spm_bulk_import')) {
    $preset_data = json_decode(stripslashes($_POST['preset_data']), true);
    
    if ($preset_data && is_array($preset_data)) {
        $success_count = 0;
        foreach ($preset_data as $subject_data) {
            $result = $wpdb->insert(
                $wpdb->prefix . 'study_subjects',
                array(
                    'subject_key' => sanitize_text_field($subject_data['key']),
                    'subject_name' => sanitize_text_field($subject_data['name']),
                    'total_chapters' => intval($subject_data['chapters']),
                    'progress_color' => sanitize_hex_color($subject_data['color'])
                )
            );
            
            if ($result) {
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            echo "<script>location.reload();</script>";
            echo '<div class="notice notice-success"><p>' . $success_count . '個の科目を一括登録しました！</p></div>';
        }
    }
}
?>

<style>
.spm-subjects-manager {
    max-width: 1200px;
}

.spm-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.spm-section h2 {
    margin-top: 0;
    border-bottom: 2px solid #2271b1;
    padding-bottom: 10px;
}

.spm-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.spm-form-group {
    display: flex;
    flex-direction: column;
}

.spm-form-group label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #23282d;
}

.spm-form-group input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.spm-form-group small {
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.spm-subjects-list {
    display: grid;
    gap: 15px;
}

.spm-subject-card {
    border: 1px solid #ddd;
    border-radius: 6px;
    background: #f9f9f9;
}

.spm-subject-header {
    padding: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}

.spm-subject-header h3 {
    margin: 0;
    flex: 1;
}

.spm-subject-key {
    background: #e0e0e0;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    color: #666;
}

.spm-chapter-count {
    background: #2271b1;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
}

.spm-subject-actions {
    display: flex;
    gap: 8px;
}

.spm-edit-btn {
    background: #72aee6;
    color: white;
    border: none;
}

.spm-delete-btn {
    background: #d63638;
    color: white;
    border: none;
}

.spm-edit-form {
    padding: 15px;
    border-top: 1px solid #ddd;
    background: white;
}

.spm-edit-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.spm-edit-actions {
    display: flex;
    gap: 10px;
}

.spm-no-subjects {
    text-align: center;
    padding: 40px;
    color: #666;
}

.spm-preset-buttons {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.spm-preset-preview {
    background: #f0f8ff;
    border: 1px solid #72aee6;
    border-radius: 6px;
    padding: 15px;
    margin-top: 15px;
}

.spm-preset-preview h4 {
    margin: 0 0 10px 0;
    color: #2271b1;
}

@media (max-width: 768px) {
    .spm-form-grid {
        grid-template-columns: 1fr;
    }
    
    .spm-subject-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .spm-subject-actions {
        width: 100%;
        justify-content: flex-end;
    }
    
    .spm-edit-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function toggleEditForm(subjectId) {
    const editForm = document.getElementById('edit-form-' + subjectId);
    editForm.style.display = editForm.style.display === 'none' ? 'block' : 'none';
}

function confirmDelete(subjectId, subjectName) {
    if (confirm('本当に「' + subjectName + '」を削除しますか？\n\n※関連する進捗データも全て削除されます。この操作は取り消せません。')) {
        document.getElementById('delete-form-' + subjectId).submit();
    }
}

const presets = {
    gyoseishoshi: [
        {key: 'kenpo', name: '憲法', chapters: 3, color: '#2196F3'},
        {key: 'gyosei', name: '行政法', chapters: 7, color: '#4CAF50'},
        {key: 'minpo', name: '民法', chapters: 6, color: '#FF9800'},
        {key: 'shoho', name: '商法・会社法', chapters: 2, color: '#9C27B0'},
        {key: 'ippan', name: '一般知識', chapters: 4, color: '#607D8B'}
    ],
    takken: [
        {key: 'kensetsu', name: '宅建業法', chapters: 5, color: '#F44336'},
        {key: 'minpo_takken', name: '権利関係', chapters: 4, color: '#3F51B5'},
        {key: 'horei', name: '法令上の制限', chapters: 3, color: '#009688'},
        {key: 'zeikin', name: '税・その他', chapters: 2, color: '#795548'}
    ],
    fp: [
        {key: 'life', name: 'ライフプランニング', chapters: 3, color: '#E91E63'},
        {key: 'risk', name: 'リスク管理', chapters: 2, color: '#673AB7'},
        {key: 'kinyu', name: '金融資産運用', chapters: 4, color: '#2196F3'},
        {key: 'tax', name: 'タックスプランニング', chapters: 3, color: '#4CAF50'},
        {key: 'fudosan', name: '不動産', chapters: 3, color: '#FF9800'},
        {key: 'sozoku', name: '相続・事業承継', chapters: 3, color: '#9C27B0'}
    ]
};

function importPreset(presetType) {
    const preset = presets[presetType];
    const previewDiv = document.getElementById('preset-preview');
    const listDiv = document.getElementById('preset-list');
    
    listDiv.innerHTML = preset.map(subject => 
        `<div style="padding: 8px; border-left: 4px solid ${subject.color}; margin-bottom: 5px; background: white;">
            <strong>${subject.name}</strong> (${subject.key}) - ${subject.chapters}章
        </div>`
    ).join('');
    
    document.getElementById('preset-data').value = JSON.stringify(preset);
    previewDiv.style.display = 'block';
}

function cancelImport() {
    document.getElementById('preset-preview').style.display = 'none';
}
</script>