<?php
// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// 統計データ取得
$total_subjects = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}study_subjects");
$total_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}study_progress");
$total_progress_entries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}study_progress");

// 各科目の進捗統計
$subject_stats = $wpdb->get_results("
    SELECT 
        s.subject_name,
        s.progress_color,
        COUNT(DISTINCT p.user_id) as active_users,
        AVG(p.understanding_level) as avg_understanding,
        AVG(p.mastery_level) as avg_mastery
    FROM {$wpdb->prefix}study_subjects s
    LEFT JOIN {$wpdb->prefix}study_progress p ON s.subject_key = p.subject_key
    GROUP BY s.id
    ORDER BY s.id
");
?>

<div class="wrap">
    <h1>学習進捗管理システム</h1>
    
    <div class="spm-dashboard">
        
        <!-- 概要統計 -->
        <div class="spm-stats-grid">
            <div class="spm-stat-card">
                <div class="spm-stat-icon">📚</div>
                <div class="spm-stat-content">
                    <h3><?php echo $total_subjects; ?></h3>
                    <p>登録科目数</p>
                </div>
            </div>
            
            <div class="spm-stat-card">
                <div class="spm-stat-icon">👥</div>
                <div class="spm-stat-content">
                    <h3><?php echo $total_users; ?></h3>
                    <p>学習中ユーザー</p>
                </div>
            </div>
            
            <div class="spm-stat-card">
                <div class="spm-stat-icon">📊</div>
                <div class="spm-stat-content">
                    <h3><?php echo $total_progress_entries; ?></h3>
                    <p>進捗記録数</p>
                </div>
            </div>
        </div>

        <!-- 科目別統計 -->
        <div class="spm-section">
            <h2>科目別学習状況</h2>
            <div class="spm-subject-stats">
                <?php foreach ($subject_stats as $stat): ?>
                <div class="spm-subject-stat-card">
                    <div class="spm-subject-header" style="border-left: 5px solid <?php echo $stat->progress_color; ?>">
                        <h3><?php echo esc_html($stat->subject_name); ?></h3>
                        <span class="spm-active-users"><?php echo $stat->active_users; ?>人が学習中</span>
                    </div>
                    
                    <div class="spm-progress-bars">
                        <div class="spm-progress-item">
                            <label>理解度平均</label>
                            <div class="spm-progress-bar">
                                <div class="spm-progress-fill" 
                                     style="width: <?php echo ($stat->avg_understanding * 50); ?>%; background-color: <?php echo $stat->progress_color; ?>">
                                </div>
                            </div>
                            <span><?php echo round($stat->avg_understanding * 50, 1); ?>%</span>
                        </div>
                        
                        <div class="spm-progress-item">
                            <label>習得度平均</label>
                            <div class="spm-progress-bar">
                                <div class="spm-progress-fill" 
                                     style="width: <?php echo ($stat->avg_mastery * 50); ?>%; background-color: <?php echo $stat->progress_color; ?>">
                                </div>
                            </div>
                            <span><?php echo round($stat->avg_mastery * 50, 1); ?>%</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- クイックアクション -->
        <div class="spm-section">
            <h2>クイックアクション</h2>
            <div class="spm-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=study-progress-subjects'); ?>" class="spm-action-btn spm-btn-primary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    新しい科目を追加
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=study-progress-structure'); ?>" class="spm-action-btn spm-btn-secondary">
                    <span class="dashicons dashicons-admin-settings"></span>
                    科目構造を設定
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=study-progress-admin'); ?>" class="spm-action-btn spm-btn-tertiary">
                    <span class="dashicons dashicons-chart-bar"></span>
                    進捗を管理
                </a>
            </div>
        </div>

        <!-- ショートコード使用方法 -->
        <div class="spm-section">
            <h2>ショートコード使用方法</h2>
            <div class="spm-shortcode-info">
                <div class="spm-shortcode-item">
                    <h4>基本表示</h4>
                    <code>[study_progress]</code>
                    <p>全科目の進捗を表示します</p>
                </div>
                
                <div class="spm-shortcode-item">
                    <h4>特定科目のみ表示</h4>
                    <code>[study_progress subject="kenpo"]</code>
                    <p>憲法のみの進捗を表示します</p>
                </div>
                
                <div class="spm-shortcode-item">
                    <h4>コンパクト表示</h4>
                    <code>[study_progress mode="compact"]</code>
                    <p>コンパクトな形式で表示します</p>
                </div>
                
                <div class="spm-shortcode-item">
                    <h4>サマリー表示</h4>
                    <code>[study_progress mode="summary"]</code>
                    <p>進捗率のみをサマリー表示します</p>
                </div>
            </div>
        </div>

        <!-- 最近の活動 -->
        <div class="spm-section">
            <h2>最近の学習活動</h2>
            <div class="spm-recent-activity">
                <?php
                $recent_activities = $wpdb->get_results("
                    SELECT 
                        u.display_name,
                        s.subject_name,
                        p.chapter_number,
                        p.section_number,
                        p.item_number,
                        p.understanding_level,
                        p.mastery_level,
                        p.last_updated
                    FROM {$wpdb->prefix}study_progress p
                    JOIN {$wpdb->users} u ON p.user_id = u.ID
                    JOIN {$wpdb->prefix}study_subjects s ON p.subject_key = s.subject_key
                    ORDER BY p.last_updated DESC
                    LIMIT 10
                ");
                
                if ($recent_activities): ?>
                    <table class="spm-activity-table">
                        <thead>
                            <tr>
                                <th>ユーザー</th>
                                <th>科目</th>
                                <th>学習箇所</th>
                                <th>進捗</th>
                                <th>更新日時</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_activities as $activity): ?>
                            <tr>
                                <td><?php echo esc_html($activity->display_name); ?></td>
                                <td><?php echo esc_html($activity->subject_name); ?></td>
                                <td>第<?php echo $activity->chapter_number; ?>章 第<?php echo $activity->section_number; ?>節 項<?php echo $activity->item_number; ?></td>
                                <td>
                                    <span class="spm-progress-badge spm-understanding">理解: <?php echo $activity->understanding_level ? '✓' : '×'; ?></span>
                                    <span class="spm-progress-badge spm-mastery">習得: <?php echo $activity->mastery_level ? '✓' : '×'; ?></span>
                                </td>
                                <td><?php echo date('m/d H:i', strtotime($activity->last_updated)); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="spm-no-data">まだ学習活動がありません。</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.spm-dashboard {
    max-width: 1200px;
}

.spm-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.spm-stat-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.spm-stat-icon {
    font-size: 2.5em;
    margin-right: 15px;
}

.spm-stat-content h3 {
    margin: 0;
    font-size: 2em;
    color: #2271b1;
}

.spm-stat-content p {
    margin: 5px 0 0 0;
    color: #666;
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

.spm-subject-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.spm-subject-stat-card {
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 15px;
    background: #f9f9f9;
}

.spm-subject-header {
    padding-left: 15px;
    margin-bottom: 15px;
}

.spm-subject-header h3 {
    margin: 0 0 5px 0;
}

.spm-active-users {
    font-size: 0.9em;
    color: #666;
}

.spm-progress-item {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.spm-progress-item label {
    width: 80px;
    font-size: 0.9em;
    color: #666;
}

.spm-progress-bar {
    flex: 1;
    height: 20px;
    background: #e0e0e0;
    border-radius: 10px;
    overflow: hidden;
    margin: 0 10px;
}

.spm-progress-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.spm-quick-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.spm-action-btn {
    display: inline-flex;
    align-items: center;
    padding: 12px 20px;
    text-decoration: none;
    border-radius: 6px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.spm-btn-primary {
    background: #2271b1;
    color: white;
}

.spm-btn-secondary {
    background: #72aee6;
    color: white;
}

.spm-btn-tertiary {
    background: #00a32a;
    color: white;
}

.spm-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    color: white;
}

.spm-action-btn .dashicons {
    margin-right: 8px;
}

.spm-shortcode-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.spm-shortcode-item {
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 15px;
    background: #f6f7f7;
}

.spm-shortcode-item h4 {
    margin: 0 0 10px 0;
    color: #2271b1;
}

.spm-shortcode-item code {
    display: block;
    background: #2c3338;
    color: #50c878;
    padding: 8px 12px;
    border-radius: 4px;
    margin-bottom: 10px;
    font-family: 'Courier New', monospace;
}

.spm-activity-table {
    width: 100%;
    border-collapse: collapse;
}

.spm-activity-table th,
.spm-activity-table td {
    text-align: left;
    padding: 12px;
    border-bottom: 1px solid #ddd;
}

.spm-activity-table th {
    background: #f1f1f1;
    font-weight: 600;
}

.spm-progress-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    margin-right: 5px;
}

.spm-understanding {
    background: #e3f2fd;
    color: #1976d2;
}

.spm-mastery {
    background: #e8f5e8;
    color: #2e7d32;
}

.spm-no-data {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 40px;
}

@media (max-width: 768px) {
    .spm-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .spm-quick-actions {
        flex-direction: column;
    }
    
    .spm-action-btn {
        justify-content: center;
    }
    
    .spm-activity-table {
        font-size: 0.9em;
    }
}
</style>