<?php
/**
 * ワンクリック追加対応管理画面テンプレート
 * templates/admin-page-flexible.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// 通知メッセージの表示
settings_errors('spt_messages');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="spt-admin-container">
        
        <!-- 使い方セクション -->
        <div class="admin-card">
            <h2>📖 使い方</h2>
            <div class="usage-grid">
                <div class="usage-item">
                    <h3>基本のショートコード</h3>
                    <code>[study_progress]</code>
                    <p>全科目の進捗を表示します</p>
                </div>
                <div class="usage-item">
                    <h3>特定科目のみ表示</h3>
                    <code>[study_progress subject="constitutional,civil"]</code>
                    <p>指定した科目のみ表示</p>
                </div>
                <div class="usage-item">
                    <h3>カウントダウン表示</h3>
                    <code>[exam_countdown]</code>
                    <p>試験日までのカウントダウン</p>
                </div>
                <div class="usage-item">
                    <h3>読み取り専用モード</h3>
                    <code>[study_progress interactive="no"]</code>
                    <p>チェックボックスを表示しない</p>
                </div>
            </div>
            
            <!-- 操作説明を追加 -->
            <div style="margin-top: 20px; padding: 15px; background: #e8f5e9; border-left: 5px solid #4caf50; border-radius: 5px;">
                <h4 style="margin-top: 0; color: #2e7d32;">📝 簡単操作ガイド</h4>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li><strong>追加：</strong>各「追加」ボタンをクリックするだけで要素が追加されます</li>
                    <li><strong>編集：</strong>追加された要素の名前をクリックすると編集できます</li>
                    <li><strong>保存：</strong>編集後にEnterキーまたはクリック外で自動保存されます</li>
                    <li><strong>削除：</strong>「削除」ボタンで要素を削除できます（確認ダイアログ付き）</li>
                </ul>
            </div>
        </div>
        
        <!-- 科目管理セクション -->
        <div class="admin-card">
            <h2>📚 科目管理</h2>
            
            <!-- 科目追加フォーム -->
            <div class="section-card">
                <h3>新しい科目を追加</h3>
                <form method="post" action="">
                    <?php wp_nonce_field('spt_admin', 'spt_nonce'); ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="subject_key">科目キー（英数字）</label>
                            <input type="text" id="subject_key" name="subject_key" required 
                                   pattern="[a-zA-Z0-9_]+" placeholder="例: constitutional">
                            <small>システム内で使用されるID</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject_name">科目名</label>
                            <input type="text" id="subject_name" name="subject_name" required 
                                   placeholder="例: 憲法">
                            <small>表示される科目名</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="color">進捗バーの色</label>
                            <input type="color" id="color" name="color" value="#4CAF50">
                            <small>この科目の進捗バー色</small>
                        </div>
                    </div>
                    
                    <button type="submit" name="add_subject" class="button button-primary">
                        科目を追加
                    </button>
                </form>
            </div>
            
            <!-- 科目一覧と構造編集 -->
            <?php if (!empty($subjects) && is_array($subjects)): ?>
            <div class="section-card">
                <h3>科目構造の編集</h3>
                <div style="margin-bottom: 15px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
                    <strong>💡 クイック操作：</strong>
                    「追加」ボタンを押すと即座に要素が追加され、自動で編集モードになります。名前をクリックすると再編集できます。
                </div>
                
                <?php foreach ($subjects as $subject_key => $subject_name): 
                    $subject_structure = isset($structure[$subject_key]) && is_array($structure[$subject_key]) ? $structure[$subject_key] : array('chapters' => array());
                    $subject_progress = isset($progress[$subject_key]) && is_array($progress[$subject_key]) ? $progress[$subject_key] : array();
                    
                    // 進捗率計算
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
                    
                    $percent = $total_items > 0 ? ceil(($completed_items / $total_items) * 100) : 0;
                ?>
                
                <div class="subject-structure-card" data-subject="<?php echo esc_attr($subject_key); ?>">
                    <div class="subject-structure-header">
                        <h4>
                            <?php echo esc_html($subject_name); ?>
                            <span class="subject-key"><?php echo esc_html($subject_key); ?></span>
                        </h4>
                        <div class="subject-meta">
                            <span class="total-items"><?php echo $total_items; ?>項目</span>
                            <span class="progress-percent"><?php echo $percent; ?>%</span>
                            <form method="post" action="" style="display: inline;">
                                <?php wp_nonce_field('spt_admin', 'spt_nonce'); ?>
                                <button type="submit" name="delete_subject" value="<?php echo esc_attr($subject_key); ?>" 
                                        class="button button-link-delete button-small"
                                        onclick="return confirm('科目「<?php echo esc_js($subject_name); ?>」を削除しますか？')">
                                    削除
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="subject-progress-bar">
                        <div class="progress-fill" 
                             style="width: <?php echo esc_attr($percent); ?>%; background-color: <?php echo esc_attr(isset($subject_structure['color']) ? $subject_structure['color'] : '#4CAF50'); ?>;"></div>
                    </div>
                    
                    <div class="chapters-container">
                        <?php if (!empty($subject_structure['chapters']) && is_array($subject_structure['chapters'])): ?>
                            <?php foreach ($subject_structure['chapters'] as $chapter_id => $chapter_data): 
                                if (!is_array($chapter_data)) continue;
                            ?>
                                <div class="chapter-item" data-chapter="<?php echo esc_attr($chapter_id); ?>">
                                    <div class="chapter-header">
                                        <span class="chapter-name editable" 
                                              data-type="chapter" 
                                              data-subject="<?php echo esc_attr($subject_key); ?>"
                                              data-chapter="<?php echo esc_attr($chapter_id); ?>"
                                              title="クリックして編集">
                                            <?php echo esc_html(isset($chapter_data['name']) ? $chapter_data['name'] : '無題の章'); ?>
                                        </span>
                                        <div class="chapter-controls">
                                            <button type="button" class="button button-small add-section-btn" 
                                                    data-subject="<?php echo esc_attr($subject_key); ?>"
                                                    data-chapter="<?php echo esc_attr($chapter_id); ?>"
                                                    title="ワンクリックで節を追加">
                                                節追加
                                            </button>
                                            <button type="button" class="button button-link-delete button-small delete-chapter-btn"
                                                    data-subject="<?php echo esc_attr($subject_key); ?>"
                                                    data-chapter="<?php echo esc_attr($chapter_id); ?>">
                                                削除
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="sections-container">
                                        <?php if (!empty($chapter_data['sections']) && is_array($chapter_data['sections'])): ?>
                                            <?php foreach ($chapter_data['sections'] as $section_id => $section_data): 
                                                if (!is_array($section_data)) continue;
                                            ?>
                                                <div class="section-item" data-section="<?php echo esc_attr($section_id); ?>">
                                                    <div class="section-header">
                                                        <span class="section-name editable"
                                                              data-type="section"
                                                              data-subject="<?php echo esc_attr($subject_key); ?>"
                                                              data-chapter="<?php echo esc_attr($chapter_id); ?>"
                                                              data-section="<?php echo esc_attr($section_id); ?>"
                                                              title="クリックして編集">
                                                            <?php echo esc_html(isset($section_data['name']) ? $section_data['name'] : '無題の節'); ?>
                                                        </span>
                                                        <div class="section-controls">
                                                            <button type="button" class="button button-small add-item-btn"
                                                                    data-subject="<?php echo esc_attr($subject_key); ?>"
                                                                    data-chapter="<?php echo esc_attr($chapter_id); ?>"
                                                                    data-section="<?php echo esc_attr($section_id); ?>"
                                                                    title="ワンクリックで項目を追加">
                                                                項追加
                                                            </button>
                                                            <button type="button" class="button button-link-delete button-small delete-section-btn"
                                                                    data-subject="<?php echo esc_attr($subject_key); ?>"
                                                                    data-chapter="<?php echo esc_attr($chapter_id); ?>"
                                                                    data-section="<?php echo esc_attr($section_id); ?>">
                                                                削除
                                                            </button>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="items-container">
                                                        <?php if (!empty($section_data['items']) && is_array($section_data['items'])): ?>
                                                            <?php foreach ($section_data['items'] as $item_id => $item_name): ?>
                                                                <div class="item-element" data-item="<?php echo esc_attr($item_id); ?>">
                                                                    <span class="item-name editable"
                                                                          data-type="item"
                                                                          data-subject="<?php echo esc_attr($subject_key); ?>"
                                                                          data-chapter="<?php echo esc_attr($chapter_id); ?>"
                                                                          data-section="<?php echo esc_attr($section_id); ?>"
                                                                          data-item="<?php echo esc_attr($item_id); ?>"
                                                                          title="クリックして編集">
                                                                        <?php echo esc_html($item_name); ?>
                                                                    </span>
                                                                    <button type="button" class="button button-link-delete button-tiny delete-item-btn"
                                                                            data-subject="<?php echo esc_attr($subject_key); ?>"
                                                                            data-chapter="<?php echo esc_attr($chapter_id); ?>"
                                                                            data-section="<?php echo esc_attr($section_id); ?>"
                                                                            data-item="<?php echo esc_attr($item_id); ?>">
                                                                        ×
                                                                    </button>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <p style="margin: 10px; color: #666; font-style: italic;">この節には項目がありません。「項追加」ボタンで項目を追加してください。</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p style="margin: 10px; color: #666; font-style: italic;">この章には節がありません。「節追加」ボタンで節を追加してください。</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="margin: 20px; color: #666; font-style: italic; text-align: center;">
                                この科目には章がありません。<br>
                                「+ 章を追加」ボタンで章を追加してください。
                            </p>
                        <?php endif; ?>
                        
                        <div class="add-chapter-section">
                            <button type="button" class="button button-secondary add-chapter-btn" 
                                    data-subject="<?php echo esc_attr($subject_key); ?>"
                                    title="ワンクリックで章を追加">
                                + 章を追加
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="section-card">
                <p style="text-align: center; padding: 40px; color: #666; font-size: 16px;">
                    📚 まだ科目が登録されていません。<br>
                    上記のフォームから科目を追加してください。
                </p>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- 全般設定セクション -->
        <div class="admin-card">
            <h2>⚙️ 全般設定</h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('spt_admin', 'spt_nonce'); ?>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_check_color">理解レベルの色</label>
                        <input type="color" id="first_check_color" name="first_check_color" 
                               value="<?php echo esc_attr(isset($settings['first_check_color']) ? $settings['first_check_color'] : '#e6f7e6'); ?>">
                        <small>「理解」チェック時の背景色</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="second_check_color">習得レベルの色</label>
                        <input type="color" id="second_check_color" name="second_check_color" 
                               value="<?php echo esc_attr(isset($settings['second_check_color']) ? $settings['second_check_color'] : '#ffebcc'); ?>">
                        <small>「習得」チェック時の背景色</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="exam_title">試験名</label>
                        <input type="text" id="exam_title" name="exam_title" 
                               value="<?php echo esc_attr(isset($settings['exam_title']) ? $settings['exam_title'] : '試験'); ?>" 
                               placeholder="行政書士試験">
                        <small>カウントダウンで表示される試験名</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="exam_date">試験日</label>
                        <input type="date" id="exam_date" name="exam_date" 
                               value="<?php echo esc_attr(isset($settings['exam_date']) ? $settings['exam_date'] : ''); ?>">
                        <small>カウントダウンの目標日</small>
                    </div>
                </div>
                
                <button type="submit" name="save_settings" class="button button-primary">
                    設定を保存
                </button>
            </form>
        </div>
        
        <!-- ワンクリック操作ガイド -->
        <div class="admin-card">
            <h2>🚀 ワンクリック操作ガイド</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                <div style="background: #f0f7ff; padding: 15px; border-radius: 8px; border: 1px solid #b3d9ff;">
                    <h4 style="margin-top: 0; color: #0066cc;">🆕 追加操作</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>「+ 章を追加」→ 即座に新しい章が追加</li>
                        <li>「節追加」→ その章に新しい節が追加</li>
                        <li>「項追加」→ その節に新しい項目が追加</li>
                    </ul>
                </div>
                
                <div style="background: #f8f5ff; padding: 15px; border-radius: 8px; border: 1px solid #d1c4e9;">
                    <h4 style="margin-top: 0; color: #6a1b9a;">✏️ 編集操作</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>要素名をクリック→ 編集モード開始</li>
                        <li>Enterキー→ 保存</li>
                        <li>Escapeキー→ キャンセル</li>
                        <li>クリック外→ 自動保存</li>
                    </ul>
                </div>
                
                <div style="background: #fff3e0; padding: 15px; border-radius: 8px; border: 1px solid #ffcc80;">
                    <h4 style="margin-top: 0; color: #f57c00;">🗑️ 削除操作</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>「削除」ボタン→ 確認ダイアログ表示</li>
                        <li>関連する進捗データも一緒に削除</li>
                        <li>削除後は元に戻せません</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- デバッグ情報セクション（開発用） -->
        <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
        <div class="admin-card">
            <h2>🔧 デバッグ情報</h2>
            <div style="background: #f0f0f0; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px;">
                <p><strong>科目数:</strong> <?php echo count($subjects); ?></p>
                <p><strong>構造データ:</strong> <?php echo !empty($structure) ? 'あり' : 'なし'; ?></p>
                <p><strong>進捗データ:</strong> <?php echo !empty($progress) ? 'あり' : 'なし'; ?></p>
                <p><strong>設定データ:</strong> <?php echo !empty($settings) ? 'あり' : 'なし'; ?></p>
                <p><strong>JavaScriptローディング:</strong> <span id="js-status">確認中...</span></p>
                <?php if (!empty($subjects)): ?>
                <details>
                    <summary>科目一覧詳細</summary>
                    <pre><?php echo esc_html(print_r($subjects, true)); ?></pre>
                </details>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        // デバッグ用JavaScript確認
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('js-status').textContent = 'OK';
            console.log('管理画面JavaScript読み込み完了');
        });
        </script>
        <?php endif; ?>
        
    </div>
</div>

<!-- カスタムスタイル追加 -->
<style>
.editable {
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 3px;
    transition: background-color 0.2s ease;
}

.editable:hover {
    background-color: #f0f8ff;
    border: 1px dashed #0073aa;
}

.button:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
}

.fade-in {
    animation: fadeInUp 0.4s ease-out;
}

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

.editing {
    background-color: #fff8dc !important;
    border-radius: 3px !important;
}

/* 通知スタイル */
.spt-admin-notification {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    line-height: 1.4;
    transition: all 0.3s ease;
}

/* ツールチップスタイル */
[title] {
    position: relative;
}

/* 操作ボタンのホバー効果 */
.add-chapter-btn:hover,
.add-section-btn:hover,
.add-item-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* レスポンシブ調整 */
@media (max-width: 768px) {
    .admin-card {
        padding: 15px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .chapter-controls,
    .section-controls {
        flex-direction: column;
        gap: 5px;
    }
    
    .button-small {
        width: 100%;
        text-align: center;
    }
}
</style>