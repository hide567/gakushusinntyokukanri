<?php
// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// POSTデータ処理 - nonce検証を修正
if ($_POST) {
    if (isset($_POST['add_subject']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'spm_add_subject')) {
        $subject_key = sanitize_text_field($_POST['subject_key']);
        $subject_name = sanitize_text_field($_POST['subject_name']);
        $total_chapters = intval($_POST['total_chapters']);
        $progress_color = sanitize_hex_color($_POST['progress_color']);
        
        // 入力値の検証
        $errors = array();
        
        if (empty($subject_key) || !preg_match('/^[a-zA-Z0-9_]+$/', $subject_key)) {
            $errors[] = '科目キーは英数字とアンダースコアのみ使用可能です。';
        }
        
        if (empty($subject_name)) {
            $errors[] = '科目名を入力してください。';
        }
        
        if ($total_chapters < 1 || $total_chapters > 20) {
            $errors[] = '章数は1〜20の範囲で入力してください。';
        }
        
        if (empty($progress_color)) {
            $progress_color = '#4CAF50'; // デフォルト色
        }
        
        // 既存の科目キーチェック
        if (empty($errors)) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}study_subjects WHERE subject_key = %s",
                $subject_key
            ));
            
            if ($existing > 0) {
                $errors[] = 'この科目キーは既に使用されています。';
            }
        }
        
        if (empty($errors)) {
            $result = $wpdb->insert(
                $wpdb->prefix . 'study_subjects',
                array(
                    'subject_key' => $subject_key,
                    'subject_name' => $subject_name,
                    'total_chapters' => $total_chapters,
                    'progress_color' => $progress_color
                )
            );
            
            if ($result) {
                echo '<div class="notice notice-success"><p>科目「' . esc_html($subject_name) . '」を追加しました！</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>エラー: 科目の追加に失敗しました。データベースエラーが発生しました。</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>エラー: ' . implode('<br>', $errors) . '</p></div>';
        }
    }
    
    if (isset($_POST['update_subject']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'spm_update_subject')) {
        $subject_id = intval($_POST['subject_id']);
        $subject_name = sanitize_text_field($_POST['subject_name']);
        $total_chapters = intval($_POST['total_chapters']);
        $progress_color = sanitize_hex_color($_POST['progress_color']);
        
        if (empty($subject_name)) {
            echo '<div class="notice notice-error"><p>エラー: 科目名を入力してください。</p></div>';
        } else if ($total_chapters < 1 || $total_chapters > 20) {
            echo '<div class="notice notice-error"><p>エラー: 章数は1〜20の範囲で入力してください。</p></div>';
        } else {
            if (empty($progress_color)) {
                $progress_color = '#4CAF50';
            }
            
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
            } else {
                echo '<div class="notice notice-error"><p>エラー: 科目の更新に失敗しました。</p></div>';
            }
        }
    }
    
    if (isset($_POST['delete_subject']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'spm_delete_subject')) {
        $subject_id = intval($_POST['subject_id']);
        
        // 関連データも削除
        $subject = $wpdb->get_row($wpdb->prepare("SELECT subject_key, subject_name FROM {$wpdb->prefix}study_subjects WHERE id = %d", $subject_id));
        
        if ($subject) {
            $wpdb->delete($wpdb->prefix . 'study_progress', array('subject_key' => $subject->subject_key));
            $wpdb->delete($wpdb->prefix . 'study_items', array('subject_key' => $subject->subject_key));
            $wpdb->delete($wpdb->prefix . 'study_sections', array('subject_key' => $subject->subject_key));
            $wpdb->delete($wpdb->prefix . 'study_chapters', array('subject_key' => $subject->subject_key));
            $deleted = $wpdb->delete($wpdb->prefix . 'study_subjects', array('id' => $subject_id));
            
            if ($deleted) {
                echo '<div class="notice notice-success"><p>科目「' . esc_html($subject->subject_name) . '」と関連データを削除しました。</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>エラー: 科目の削除に失敗しました。</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>エラー: 削除対象の科目が見つかりません。</p></div>';
        }
    }
    
    // 一括登録処理
    if (isset($_POST['bulk_import']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'spm_bulk_import')) {
        $preset_data = json_decode(stripslashes($_POST['preset_data']), true);
        
        if ($preset_data && is_array($preset_data)) {
            $success_count = 0;
            $error_count = 0;
            
            foreach ($preset_data as $subject_data) {
                // 既存チェック
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}study_subjects WHERE subject_key = %s",
                    $subject_data['key']
                ));
                
                if ($existing == 0) {
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
                    } else {
                        $error_count++;
                    }
                } else {
                    $error_count++; // 既存の科目はエラーカウント
                }
            }
            
            if ($success_count > 0) {
                echo '<div class="notice notice-success"><p>' . $success_count . '個の科目を一括登録しました！' . ($error_count > 0 ? ' (' . $error_count . '個はスキップされました)' : '') . '</p></div>';
                echo '<script>setTimeout(function(){ location.reload(); }, 1500);</script>';
            } else if ($error_count > 0) {
                echo '<div class="notice notice-warning"><p>登録できる新しい科目がありませんでした。(' . $error_count . '個は既に登録済みまたはエラー)</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>エラー: 一括登録データが無効です。</p></div>';
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
                        <label for="subject_key">科目キー（英数字）<span class="required">*</span></label>
                        <input type="text" id="subject_key" name="subject_key" required
                               placeholder="例: kenpo, gyosei, minpo"
                               pattern="[a-zA-Z0-9_]+"
                               title="英数字とアンダースコアのみ使用可能"
                               maxlength="50">
                        <small>システム内で使用される英数字のID（例: kenpo, gyosei, minpo など）</small>
                    </div>
                    
                    <div class="spm-form-group">
                        <label for="subject_name">科目名<span class="required">*</span></label>
                        <input type="text" id="subject_name" name="subject_name" required
                               placeholder="例: 憲法, 行政法, 民法"
                               maxlength="100">
                        <small>表示される科目名（例: 憲法, 行政法, 民法 など）</small>
                    </div>
                    
                    <div class="spm-form-group">
                        <label for="total_chapters">初期章数<span class="required">*</span></label>
                        <input type="number" id="total_chapters" name="total_chapters" min="1" max="20" value="3" required>
                        <small>この科目の章数（1〜20）</small>
                    </div>
                    
                    <div class="spm-form-group">
                        <label for="progress_color">進捗バーの色</label>
                        <div class="spm-color-picker">
                            <input type="color" id="progress_color" name="progress_color" value="#4CAF50">
                            <div class="spm-color-preview" style="background-color: #4CAF50;"></div>
                        </div>
                        <small>この科目の進捗バーに使用する色</small>
                    </div>
                </div>
                
                <div class="spm-form-actions">
                    <button type="submit" name="add_subject" class="button button-primary button-large">
                        <span class="dashicons dashicons-plus-alt"></span> 科目を追加
                    </button>
                    <button type="button" class="button" onclick="resetForm()">
                        <span class="dashicons dashicons-undo"></span> リセット
                    </button>
                </div>
            </form>
        </div>

        <!-- 科目一覧 -->
        <div class="spm-section">
            <h2>登録済み科目一覧 (<?php echo count($subjects); ?>件)</h2>
            
            <?php if ($subjects): ?>
                <div class="spm-subjects-list">
                    <?php foreach ($subjects as $subject): ?>
                        <div class="spm-subject-card" data-subject-id="<?php echo $subject->id; ?>">
                            <div class="spm-subject-header" style="border-left: 5px solid <?php echo esc_attr($subject->progress_color); ?>">
                                <div class="spm-subject-main">
                                    <h3><?php echo esc_html($subject->subject_name); ?></h3>
                                    <div class="spm-subject-meta">
                                        <span class="spm-subject-key">ID: <?php echo esc_html($subject->subject_key); ?></span>
                                        <span class="spm-chapter-count"><?php echo $subject->total_chapters; ?>章構成</span>
                                        <span class="spm-color-indicator" style="background-color: <?php echo esc_attr($subject->progress_color); ?>"></span>
                                    </div>
                                </div>
                                
                                <div class="spm-subject-actions">
                                    <button type="button" class="button spm-edit-btn" onclick="toggleEditForm(<?php echo $subject->id; ?>)" title="編集">
                                        <span class="dashicons dashicons-edit"></span> 編集
                                    </button>
                                    <button type="button" class="button spm-delete-btn" onclick="confirmDelete(<?php echo $subject->id; ?>, '<?php echo esc_js($subject->subject_name); ?>')" title="削除">
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
                                            <label>科目名<span class="required">*</span></label>
                                            <input type="text" name="subject_name" value="<?php echo esc_attr($subject->subject_name); ?>" required maxlength="100">
                                        </div>
                                        
                                        <div class="spm-form-group">
                                            <label>章数<span class="required">*</span></label>
                                            <input type="number" name="total_chapters" value="<?php echo $subject->total_chapters; ?>" min="1" max="20" required>
                                        </div>
                                        
                                        <div class="spm-form-group">
                                            <label>進捗バーの色</label>
                                            <div class="spm-color-picker">
                                                <input type="color" name="progress_color" value="<?php echo esc_attr($subject->progress_color); ?>">
                                                <div class="spm-color-preview" style="background-color: <?php echo esc_attr($subject->progress_color); ?>;"></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="spm-edit-actions">
                                        <button type="submit" name="update_subject" class="button button-primary">
                                            <span class="dashicons dashicons-saved"></span> 更新
                                        </button>
                                        <button type="button" class="button" onclick="toggleEditForm(<?php echo $subject->id; ?>)">
                                            <span class="dashicons dashicons-no"></span> キャンセル
                                        </button>
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
                
                <div class="spm-bulk-actions">
                    <h3>一括操作</h3>
                    <button type="button" class="button" onclick="exportSubjects()">
                        <span class="dashicons dashicons-download"></span> 科目データをエクスポート
                    </button>
                </div>
                
            <?php else: ?>
                <div class="spm-no-subjects">
                    <div class="spm-no-data-icon">📚</div>
                    <h3>科目がまだ登録されていません</h3>
                    <p>上記のフォームから新しい科目を追加するか、下記の一括登録機能をご利用ください。</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- 一括登録機能 -->
        <div class="spm-section">
            <h2>一括登録機能</h2>
            <div class="spm-bulk-import">
                <p>よく使用される科目セットを一括で登録できます。既存の科目と重複する場合はスキップされます。</p>
                
                <div class="spm-preset-buttons">
                    <button type="button" class="button spm-preset-btn" onclick="importPreset('gyoseishoshi')" data-preset="gyoseishoshi">
                        <span class="dashicons dashicons-groups"></span> 行政書士試験セット
                    </button>
                    <button type="button" class="button smp-preset-btn" onclick="importPreset('takken')" data-preset="takken">
                        <span class="dashicons dashicons-admin-home"></span> 宅建試験セット
                    </button>
                    <button type="button" class="button spm-preset-btn" onclick="importPreset('fp')" data-preset="fp">
                        <span class="dashicons dashicons-money-alt"></span> FP試験セット
                    </button>
                </div>
                
                <div id="preset-preview" class="spm-preset-preview" style="display: none;">
                    <h4>登録される科目:</h4>
                    <div id="preset-list"></div>
                    <form method="post" id="bulk-import-form">
                        <?php wp_nonce_field('spm_bulk_import'); ?>
                        <input type="hidden" name="bulk_import" value="1">
                        <input type="hidden" name="preset_data" id="preset-data" value="">
                        <div class="spm-import-actions">
                            <button type="submit" class="button button-primary">
                                <span class="dashicons dashicons-upload"></span> 一括登録実行
                            </button>
                            <button type="button" class="button" onclick="cancelImport()">
                                <span class="dashicons dashicons-no"></span> キャンセル
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.spm-section h2 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #2271b1;
    color: #1d2327;
    font-size: 1.3em;
    font-weight: 600;
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
    margin-bottom: 8px;
    color: #1d2327;
    font-size: 14px;
}

.required {
    color: #d63638;
    margin-left: 3px;
}

.spm-form-group input {
    padding: 10px 12px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.spm-form-group input:focus {
    outline: none;
    border-color: #2271b1;
    box-shadow: 0 0 0 2px rgba(34, 113, 177, 0.2);
}

.spm-form-group small {
    margin-top: 5px;
    color: #646970;
    font-size: 12px;
    line-height: 1.4;
}

.spm-color-picker {
    display: flex;
    align-items: center;
    gap: 10px;
}

.spm-color-picker input[type="color"] {
    width: 50px;
    height: 50px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    padding: 0;
}

.spm-color-preview {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 2px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.spm-form-actions {
    display: flex;
    gap: 15px;
    align-items: center;
    margin-top: 20px;
}

.spm-subjects-list {
    display: grid;
    gap: 15px;
}

.spm-subject-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    background: #f9f9f9;
    overflow: hidden;
    transition: all 0.3s ease;
}

.spm-subject-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.spm-subject-header {
    padding: 20px;
    background: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
}

.spm-subject-main {
    flex: 1;
}

.spm-subject-main h3 {
    margin: 0 0 8px 0;
    color: #1d2327;
    font-size: 1.2em;
}

.spm-subject-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.spm-subject-key {
    background: #f0f0f1;
    color: #646970;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.85em;
    font-family: 'Courier New', monospace;
}

.spm-chapter-count {
    background: #2271b1;
    color: white;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.85em;
    font-weight: 500;
}

.spm-color-indicator {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 0 1px #ddd;
}

.spm-subject-actions {
    display: flex;
    gap: 8px;
}

.spm-edit-btn {
    background: #72aee6;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.3s ease;
}

.spm-edit-btn:hover {
    background: #5a9fd4;
    color: white;
}

.spm-delete-btn {
    background: #d63638;
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.3s ease;
}

.spm-delete-btn:hover {
    background: #c92d30;
    color: white;
}

.spm-edit-form {
    padding: 20px;
    background: white;
    border-top: 1px solid #ddd;
}

.spm-edit-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.spm-edit-actions {
    display: flex;
    gap: 10px;
}

.spm-no-subjects {
    text-align: center;
    padding: 60px 20px;
    color: #646970;
    background: #f6f7f7;
    border-radius: 8px;
    border: 2px dashed #c3c4c7;
}

.spm-no-data-icon {
    font-size: 4em;
    margin-bottom: 20px;
    opacity: 0.7;
}

.spm-no-subjects h3 {
    margin: 0 0 10px 0;
    color: #1d2327;
}

.spm-bulk-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.spm-bulk-actions h3 {
    margin: 0 0 15px 0;
    color: #1d2327;
}

.spm-preset-buttons {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.spm-preset-btn {
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.spm-preset-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.spm-preset-preview {
    background: #e7f3ff;
    border: 1px solid #72aee6;
    border-radius: 6px;
    padding: 20px;
    margin-top: 20px;
    animation: slideDown 0.3s ease-out;
}

.spm-preset-preview h4 {
    margin: 0 0 15px 0;
    color: #2271b1;
}

#preset-list {
    margin-bottom: 20px;
}

.spm-preset-item {
    padding: 12px;
    margin-bottom: 8px;
    background: white;
    border-radius: 6px;
    border-left: 4px solid;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: all 0.2s ease;
}

.spm-preset-item:hover {
    transform: translateX(5px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.spm-import-actions {
    display: flex;
    gap: 10px;
}

.spm-bulk-import {
    padding: 20px;
    background: #f9f9f9;
    border-radius: 6px;
    border: 1px solid #ddd;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* レスポンシブ対応 */
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
    
    .spm-preset-buttons {
        flex-direction: column;
    }
    
    .spm-preset-btn {
        width: 100%;
        justify-content: center;
    }
    
    .spm-form-actions,
    .spm-edit-actions,
    .spm-import-actions {
        flex-direction: column;
    }
    
    .spm-form-actions button,
    .spm-edit-actions button,
    .spm-import-actions button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
function toggleEditForm(subjectId) {
    const editForm = document.getElementById('edit-form-' + subjectId);
    const isVisible = editForm.style.display !== 'none';
    
    // 他の編集フォームを全て閉じる
    document.querySelectorAll('.spm-edit-form').forEach(form => {
        form.style.display = 'none';
    });
    
    // 現在のフォームの表示切り替え
    editForm.style.display = isVisible ? 'none' : 'block';
}

function confirmDelete(subjectId, subjectName) {
    if (confirm('本当に「' + subjectName + '」を削除しますか？\n\n※関連する進捗データも全て削除されます。この操作は取り消せません。')) {
        document.getElementById('delete-form-' + subjectId).submit();
    }
}

function resetForm() {
    document.querySelector('.spm-add-form').reset();
    document.getElementById('progress_color').value = '#4CAF50';
    updateColorPreview(document.getElementById('progress_color'));
}

// 色プレビューの更新
document.addEventListener('DOMContentLoaded', function() {
    const colorInputs = document.querySelectorAll('input[type="color"]');
    colorInputs.forEach(input => {
        input.addEventListener('change', function() {
            updateColorPreview(this);
        });
        
        // 初期化
        updateColorPreview(input);
    });
});

function updateColorPreview(colorInput) {
    const preview = colorInput.parentNode.querySelector('.spm-color-preview');
    if (preview) {
        preview.style.backgroundColor = colorInput.value;
    }
}

// プリセットデータ
const presets = {
    gyoseishoshi: [
        {key: 'kenpo', name: '憲法', chapters: 3, color: '#2196F3'},
        {key: 'gyosei', name: '行政法', chapters: 7, color: '#4CAF50'},
        {key: 'minpo', name: '民法', chapters: 6, color: '#FF9800'},
        {key: 'shoho', name: '商法・会社法', chapters: 2, color: '#9C27B0'},
        {key: 'ippan', name: '一般知識', chapters: 4, color: '#607D8B'}
    ],
    takken: [
        {key: 'takkengyoho', name: '宅建業法', chapters: 5, color: '#F44336'},
        {key: 'kenri', name: '権利関係', chapters: 4, color: '#3F51B5'},
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
        `<div class="spm-preset-item" style="border-left-color: ${subject.color};">
            <div>
                <strong>${subject.name}</strong> (${subject.key}) - ${subject.chapters}章
            </div>
            <div class="spm-color-indicator" style="background-color: ${subject.color};"></div>
        </div>`
    ).join('');
    
    document.getElementById('preset-data').value = JSON.stringify(preset);
    previewDiv.style.display = 'block';
    
    // スムーズスクロール
    previewDiv.scrollIntoView({ behavior: 'smooth' });
}

function cancelImport() {
    document.getElementById('preset-preview').style.display = 'none';
}

function exportSubjects() {
    // 科目データをJSONでエクスポート
    const subjects = <?php echo json_encode($subjects); ?>;
    const exportData = {
        export_date: new Date().toISOString(),
        subjects: subjects.map(subject => ({
            subject_key: subject.subject_key,
            subject_name: subject.subject_name,
            total_chapters: parseInt(subject.total_chapters),
            progress_color: subject.progress_color
        }))
    };
    
    const dataStr = JSON.stringify(exportData, null, 2);
    const dataBlob = new Blob([dataStr], { type: 'application/json' });
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(dataBlob);
    link.download = 'study_subjects_' + new Date().toISOString().split('T')[0] + '.json';
    link.click();
}

// フォームバリデーション
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.spm-add-form');
    form.addEventListener('submit', function(e) {
        const subjectKey = document.getElementById('subject_key').value;
        const subjectName = document.getElementById('subject_name').value;
        const totalChapters = parseInt(document.getElementById('total_chapters').value);
        
        let errors = [];
        
        if (!/^[a-zA-Z0-9_]+$/.test(subjectKey)) {
            errors.push('科目キーは英数字とアンダースコアのみ使用可能です。');
        }
        
        if (subjectName.trim() === '') {
            errors.push('科目名を入力してください。');
        }
        
        if (totalChapters < 1 || totalChapters > 20) {
            errors.push('章数は1〜20の範囲で入力してください。');
        }
        
        if (errors.length > 0) {
            e.preventDefault();
            alert('エラー:\n' + errors.join('\n'));
        }
    });
});
</script>