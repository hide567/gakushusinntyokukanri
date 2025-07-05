<?php
/**
 * 管理画面テンプレート（設定のみ）
 * templates/admin-page.php
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
                                   pattern="[a-zA-Z0-9_]+" placeholder="例: math, english">
                            <small>システム内で使用されるID</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject_name">科目名</label>
                            <input type="text" id="subject_name" name="subject_name" required 
                                   placeholder="例: 数学, 英語">
                            <small>表示される科目名</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="chapters">章数</label>
                            <input type="number" id="chapters" name="chapters" value="10" min="1" max="50">
                            <small>この科目の総章数</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="sections_per_chapter">章あたりの節数</label>
                            <input type="number" id="sections_per_chapter" name="sections_per_chapter" value="3" min="1" max="20">
                            <small>各章の節数</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="items_per_section">節あたりの項数</label>
                            <input type="number" id="items_per_section" name="items_per_section" value="5" min="1" max="20">
                            <small>各節の項数</small>
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
            
            <!-- 科目一覧 -->
            <?php if (!empty($subjects)): ?>
            <div class="section-card">
                <h3>登録済み科目</h3>
                <div class="subjects-grid">
                    <?php foreach ($subjects as $key => $name): 
                        $subject_structure = $structure[$key] ?? array();
                        $subject_progress = $progress[$key] ?? array();
                        
                        // 進捗率計算
                        $total_items = ($subject_structure['chapters'] ?? 0) * 
                                      ($subject_structure['sections_per_chapter'] ?? 0) * 
                                      ($subject_structure['items_per_section'] ?? 0);
                        $completed_items = 0;
                        foreach ($subject_progress as $chapter_data) {
                            foreach ($chapter_data as $section_data) {
                                $completed_items += count($section_data);
                            }
                        }
                        $percent = $total_items > 0 ? ceil(($completed_items / $total_items) * 100) : 0;
                    ?>
                        <div class="subject-card">
                            <div class="subject-header">
                                <h4><?php echo esc_html($name); ?></h4>
                                <span class="subject-key"><?php echo esc_html($key); ?></span>
                            </div>
                            
                            <div class="subject-stats">
                                <div class="stat">
                                    <span class="stat-value"><?php echo $subject_structure['chapters'] ?? 0; ?></span>
                                    <span class="stat-label">章</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-value"><?php echo $total_items; ?></span>
                                    <span class="stat-label">総項目</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-value"><?php echo $percent; ?>%</span>
                                    <span class="stat-label">完了</span>
                                </div>
                            </div>
                            
                            <div class="progress-bar-small">
                                <div class="progress-fill-small" 
                                     style="width: <?php echo $percent; ?>%; background-color: <?php echo esc_attr($subject_structure['color'] ?? '#4CAF50'); ?>;"></div>
                            </div>
                            
                            <form method="post" action="" class="subject-actions">
                                <?php wp_nonce_field('spt_admin', 'spt_nonce'); ?>
                                <button type="submit" name="delete_subject" value="<?php echo esc_attr($key); ?>" 
                                        class="button button-link-delete"
                                        onclick="return confirm('科目「<?php echo esc_js($name); ?>」を削除しますか？関連する進捗データもすべて削除されます。')">
                                    削除
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
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
                               value="<?php echo esc_attr($settings['first_check_color'] ?? '#e6f7e6'); ?>">
                        <small>「理解」チェック時の背景色</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="second_check_color">習得レベルの色</label>
                        <input type="color" id="second_check_color" name="second_check_color" 
                               value="<?php echo esc_attr($settings['second_check_color'] ?? '#ffebcc'); ?>">
                        <small>「習得」チェック時の背景色</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="exam_title">試験名</label>
                        <input type="text" id="exam_title" name="exam_title" 
                               value="<?php echo esc_attr($settings['exam_title'] ?? '試験'); ?>" 
                               placeholder="行政書士試験">
                        <small>カウントダウンで表示される試験名</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="exam_date">試験日</label>
                        <input type="date" id="exam_date" name="exam_date" 
                               value="<?php echo esc_attr($settings['exam_date'] ?? ''); ?>">
                        <small>カウントダウンの目標日</small>
                    </div>
                </div>
                
                <button type="submit" name="save_settings" class="button button-primary">
                    設定を保存
                </button>
            </form>
        </div>
        
        <!-- 進捗リセットセクション -->
        <div class="admin-card danger-zone">
            <h2>⚠️ 進捗データの管理</h2>
            
            <div class="section-card">
                <h3>進捗のリセット</h3>
                <p>学習進捗をリセットできます。この操作は元に戻せません。</p>
                
                <form method="post" action="" id="reset-form">
                    <?php wp_nonce_field('spt_admin', 'spt_nonce'); ?>
                    
                    <div class="form-group">
                        <label for="reset_subject">リセット対象</label>
                        <select id="reset_subject" name="reset_subject">
                            <option value="all">全科目の進捗</option>
                            <?php foreach ($subjects as $key => $name): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="confirm_reset" value="1" required>
                            上記の進捗をリセットすることを確認しました
                        </label>
                    </div>
                    
                    <button type="submit" name="reset_progress" class="button button-link-delete">
                        進捗をリセット
                    </button>
                </form>
            </div>
        </div>
        
    </div>
</div>

<style>
.spt-admin-container {
    max-width: 1200px;
}

.admin-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.admin-card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.section-card {
    background: #f9f9f9;
    border: 1px solid #e5e5e5;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 5px;
}

.form-group input, .form-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group small {
    color: #666;
    font-size: 12px;
    margin-top: 4px;
}

.usage-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
}

.usage-item {
    background: #f0f7ff;
    border: 1px solid #b3d9ff;
    border-radius: 5px;
    padding: 15px;
}

.usage-item h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
}

.usage-item code {
    background: #fff;
    padding: 3px 6px;
    border-radius: 3px;
    font-size: 13px;
    color: #333;
    display: block;
    margin: 5px 0;
}

.usage-item p {
    margin: 5px 0 0 0;
    font-size: 13px;
    color: #666;
}

.subjects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
}

.subject-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 15px;
    transition: box-shadow 0.2s;
}

.subject-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.subject-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.subject-header h4 {
    margin: 0;
    font-size: 16px;
}

.subject-key {
    background: #f0f0f0;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    color: #666;
    font-family: monospace;
}

.subject-stats {
    display: flex;
    justify-content: space-around;
    margin-bottom: 10px;
}

.stat {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 18px;
    font-weight: bold;
    color: #0073aa;
}

.stat-label {
    font-size: 12px;
    color: #666;
}

.progress-bar-small {
    height: 8px;
    background: #f1f1f1;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill-small {
    height: 100%;
    transition: width 0.3s ease;
}

.subject-actions {
    text-align: right;
}

.danger-zone {
    border-color: #dc3232;
}

.danger-zone h2 {
    color: #dc3232;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .usage-grid {
        grid-template-columns: 1fr;
    }
    
    .subjects-grid {
        grid-template-columns: 1fr;
    }
    
    .subject-stats {
        flex-direction: column;
        gap: 5px;
    }
    
    .stat {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .stat-value {
        font-size: 16px;
    }
}
</style>