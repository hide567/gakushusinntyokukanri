<?php
/**
 * 柔軟構造対応管理画面テンプレート（修正版）
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
            <?php if (!empty($subjects)): ?>
            <div class="section-card">
                <h3>科目構造の編集</h3>
                
                <?php foreach ($subjects as $subject_key => $subject_name): 
                    $subject_structure = isset($structure[$subject_key]) ? $structure[$subject_key] : array();
                    $subject_progress = isset($progress[$subject_key]) ? $progress[$subject_key] : array();
                    
                    // 進捗率計算
                    $total_items = 0;
                    $completed_items = 0;
                    
                    if (!empty($subject_structure['chapters'])) {
                        foreach ($subject_structure['chapters'] as $chapter_id => $chapter_data) {
                            if (!empty($chapter_data['sections'])) {
                                foreach ($chapter_data['sections'] as $section_id => $section_data) {
                                    if (!empty($section_data['items'])) {
                                        $total_items += count($section_data['items']);
                                        if (isset($subject_progress[$chapter_id][$section_id])) {
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
                        <?php if (!empty($subject_structure['chapters'])): ?>
                            <?php foreach ($subject_structure['chapters'] as $chapter_id => $chapter_data): ?>
                                <div class="chapter-item" data-chapter="<?php echo esc_attr($chapter_id); ?>">
                                    <div class="chapter-header">
                                        <span class="chapter-name editable" 
                                              data-type="chapter" 
                                              data-subject="<?php echo esc_attr($subject_key); ?>"
                                              data-chapter="<?php echo esc_attr($chapter_id); ?>">
                                            <?php echo esc_html(isset($chapter_data['name']) ? $chapter_data['name'] : '無題の章'); ?>
                                        </span>
                                        <div class="chapter-controls">
                                            <button type="button" class="button button-small add-section-btn" 
                                                    data-subject="<?php echo esc_attr($subject_key); ?>"
                                                    data-chapter="<?php echo esc_attr($chapter_id); ?>">
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
                                        <?php if (!empty($chapter_data['sections'])): ?>
                                            <?php foreach ($chapter_data['sections'] as $section_id => $section_data): ?>
                                                <div class="section-item" data-section="<?php echo esc_attr($section_id); ?>">
                                                    <div class="section-header">
                                                        <span class="section-name editable"
                                                              data-type="section"
                                                              data-subject="<?php echo esc_attr($subject_key); ?>"
                                                              data-chapter="<?php echo esc_attr($chapter_id); ?>"
                                                              data-section="<?php echo esc_attr($section_id); ?>">
                                                            <?php echo esc_html(isset($section_data['name']) ? $section_data['name'] : '無題の節'); ?>
                                                        </span>
                                                        <div class="section-controls">
                                                            <button type="button" class="button button-small add-item-btn"
                                                                    data-subject="<?php echo esc_attr($subject_key); ?>"
                                                                    data-chapter="<?php echo esc_attr($chapter_id); ?>"
                                                                    data-section="<?php echo esc_attr($section_id); ?>">
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
                                                        <?php if (!empty($section_data['items'])): ?>
                                                            <?php foreach ($section_data['items'] as $item_id => $item_name): ?>
                                                                <div class="item-element" data-item="<?php echo esc_attr($item_id); ?>">
                                                                    <span class="item-name editable"
                                                                          data-type="item"
                                                                          data-subject="<?php echo esc_attr($subject_key); ?>"
                                                                          data-chapter="<?php echo esc_attr($chapter_id); ?>"
                                                                          data-section="<?php echo esc_attr($section_id); ?>"
                                                                          data-item="<?php echo esc_attr($item_id); ?>">
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
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div class="add-chapter-section">
                            <button type="button" class="button button-secondary add-chapter-btn" 
                                    data-subject="<?php echo esc_attr($subject_key); ?>">
                                + 章を追加
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php endforeach; ?>
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
        
    </div>
</div>

<!-- 項目追加用のモーダル -->
<div id="add-item-modal" class="spt-modal" style="display: none;">
    <div class="spt-modal-content">
        <div class="spt-modal-header">
            <h3>新しい項目を追加</h3>
            <button type="button" class="spt-modal-close">&times;</button>
        </div>
        <div class="spt-modal-body">
            <input type="text" id="new-item-name" placeholder="項目名を入力" class="widefat">
        </div>
        <div class="spt-modal-footer">
            <button type="button" class="button button-primary" id="confirm-add-item">追加</button>
            <button type="button" class="button" id="cancel-add-item">キャンセル</button>
        </div>
    </div>
</div>

<!-- 節追加用のモーダル -->
<div id="add-section-modal" class="spt-modal" style="display: none;">
    <div class="spt-modal-content">
        <div class="spt-modal-header">
            <h3>新しい節を追加</h3>
            <button type="button" class="spt-modal-close">&times;</button>
        </div>
        <div class="spt-modal-body">
            <input type="text" id="new-section-name" placeholder="節名を入力" class="widefat">
        </div>
        <div class="spt-modal-footer">
            <button type="button" class="button button-primary" id="confirm-add-section">追加</button>
            <button type="button" class="button" id="cancel-add-section">キャンセル</button>
        </div>
    </div>
</div>

<!-- 章追加用のモーダル -->
<div id="add-chapter-modal" class="spt-modal" style="display: none;">
    <div class="spt-modal-content">
        <div class="spt-modal-header">
            <h3>新しい章を追加</h3>
            <button type="button" class="spt-modal-close">&times;</button>
        </div>
        <div class="spt-modal-body">
            <input type="text" id="new-chapter-name" placeholder="章名を入力" class="widefat">
        </div>
        <div class="spt-modal-footer">
            <button type="button" class="button button-primary" id="confirm-add-chapter">追加</button>
            <button type="button" class="button" id="cancel-add-chapter">キャンセル</button>
        </div>
    </div>
</div>