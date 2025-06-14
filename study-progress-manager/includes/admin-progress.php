<?php
// セキュリティチェック
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// ユーザー一覧取得
$users = get_users(array(
    'meta_query' => array(
        array(
            'key' => 'wp_capabilities',
            'value' => 'subscriber',
            'compare' => 'LIKE'
        )
    )
));

// 科目一覧取得
$subjects = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}study_subjects ORDER BY id");

// 選択されたユーザーとフィルター
$selected_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$selected_subject = isset($_GET['subject']) ? sanitize_text_field($_GET['subject']) : '';
$date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

// 進捗データ取得（フィルター適用）
$where_conditions = array('1=1');
$query_params = array();

if ($selected_user > 0) {
    $where_conditions[] = 'p.user_id = %d';
    $query_params[] = $selected_user;
}

if (!empty($selected_subject)) {
    $where_conditions[] = 'p.subject_key = %s';
    $query_params[] = $selected_subject;
}

if (!empty($date_from)) {
    $where_conditions[] = 'DATE(p.last_updated) >= %s';
    $query_params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = 'DATE(p.last_updated) <= %s';
    $query_params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

$progress_query = "
    SELECT 
        p.*,
        u.display_name,
        u.user_email,
        s.subject_name,
        s.progress_color
    FROM {$wpdb->prefix}study_progress p
    JOIN {$wpdb->users} u ON p.user_id = u.ID
    JOIN {$wpdb->prefix}study_subjects s ON p.subject_key = s.subject_key
    WHERE {$where_clause}
    ORDER BY p.last_updated DESC
    LIMIT 100
";

if (!empty($query_params)) {
    $progress_data = $wpdb->get_results($wpdb->prepare($progress_query, $query_params));
} else {
    $progress_data = $wpdb->get_results($progress_query);
}

// 統計データ計算
$stats_query = "
    SELECT 
        COUNT(DISTINCT p.user_id) as active_users,
        COUNT(*) as total_progress_entries,
        SUM(p.understanding_level) as total_understanding,
        SUM(p.mastery_level) as total_mastery,
        AVG(p.understanding_level) as avg_understanding,
        AVG(p.mastery_level) as avg_mastery
    FROM {$wpdb->prefix}study_progress p
    WHERE {$where_clause}
";

if (!empty($query_params)) {
    $stats = $wpdb->get_row($wpdb->prepare($stats_query, $query_params));
} else {
    $stats = $wpdb->get_row($stats_query);
}

// 科目別統計
$subject_stats_query = "
    SELECT 
        s.subject_name,
        s.progress_color,
        s.subject_key,
        COUNT(DISTINCT p.user_id) as users_count,
        COUNT(p.id) as items_count,
        SUM(p.understanding_level) as understood_count,
        SUM(p.mastery_level) as mastered_count,
        ROUND(AVG(p.understanding_level) * 100, 1) as understanding_rate,
        ROUND(AVG(p.mastery_level) * 100, 1) as mastery_rate
    FROM {$wpdb->prefix}study_subjects s
    LEFT JOIN {$wpdb->prefix}study_progress p ON s.subject_key = p.subject_key
    GROUP BY s.id
    ORDER BY s.id
";

$subject_stats = $wpdb->get_results($subject_stats_query);

// 日別進捗統計（過去30日）
$daily_stats_query = "
    SELECT 
        DATE(p.last_updated) as date,
        COUNT(*) as updates_count,
        COUNT(DISTINCT p.user_id) as active_users
    FROM {$wpdb->prefix}study_progress p
    WHERE p.last_updated >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(p.last_updated)
    ORDER BY date DESC
";

$daily_stats = $wpdb->get_results($daily_stats_query);
?>

<div class="wrap">
    <h1>進捗管理</h1>
    
    <!-- 統計サマリー -->
    <div class="spm-admin-stats">
        <div class="spm-stats-cards">
            <div class="spm-stat-card">
                <div class="spm-stat-icon">👥</div>
                <div class="spm-stat-content">
                    <h3><?php echo number_format($stats->active_users ?? 0); ?></h3>
                    <p>アクティブユーザー</p>
                </div>
            </div>
            
            <div class="spm-stat-card">
                <div class="spm-stat-icon">📊</div>
                <div class="spm-stat-content">
                    <h3><?php echo number_format($stats->total_progress_entries ?? 0); ?></h3>
                    <p>進捗記録数</p>
                </div>
            </div>
            
            <div class="spm-stat-card">
                <div class="spm-stat-icon">🎯</div>
                <div class="spm-stat-content">
                    <h3><?php echo round(($stats->avg_understanding ?? 0) * 100, 1); ?>%</h3>
                    <p>平均理解度</p>
                </div>
            </div>
            
            <div class="spm-stat-card">
                <div class="spm-stat-icon">🏆</div>
                <div class="spm-stat-content">
                    <h3><?php echo round(($stats->avg_mastery ?? 0) * 100, 1); ?>%</h3>
                    <p>平均習得度</p>
                </div>
            </div>
        </div>
    </div>

    <!-- フィルター -->
    <div class="spm-admin-section">
        <h2>フィルター</h2>
        <form method="get" class="spm-filter-form">
            <input type="hidden" name="page" value="study-progress-admin">
            
            <div class="spm-filter-grid">
                <div class="spm-filter-item">
                    <label for="user_id">ユーザー</label>
                    <select name="user_id" id="user_id">
                        <option value="">全ユーザー</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user->ID; ?>" <?php selected($selected_user, $user->ID); ?>>
                                <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="spm-filter-item">
                    <label for="subject">科目</label>
                    <select name="subject" id="subject">
                        <option value="">全科目</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject->subject_key; ?>" <?php selected($selected_subject, $subject->subject_key); ?>>
                                <?php echo esc_html($subject->subject_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="spm-filter-item">
                    <label for="date_from">開始日</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($date_from); ?>">
                </div>
                
                <div class="spm-filter-item">
                    <label for="date_to">終了日</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($date_to); ?>">
                </div>
            </div>
            
            <div class="spm-filter-actions">
                <button type="submit" class="button button-primary">フィルター適用</button>
                <a href="?page=study-progress-admin" class="button">リセット</a>
                <button type="button" class="button" onclick="exportProgressData()">CSVエクスポート</button>
            </div>
        </form>
    </div>

    <!-- 科目別統計 -->
    <div class="spm-admin-section">
        <h2>科目別統計</h2>
        <div class="spm-subject-stats-grid">
            <?php foreach ($subject_stats as $subject_stat): ?>
                <div class="spm-subject-stat-card" style="border-left: 5px solid <?php echo $subject_stat->progress_color; ?>">
                    <div class="spm-subject-stat-header">
                        <h3><?php echo esc_html($subject_stat->subject_name); ?></h3>
                        <span class="spm-users-count"><?php echo $subject_stat->users_count; ?>人が学習中</span>
                    </div>
                    
                    <div class="spm-subject-stat-body">
                        <div class="spm-stat-row">
                            <span class="spm-stat-label">総項目数:</span>
                            <span class="spm-stat-value"><?php echo number_format($subject_stat->items_count); ?></span>
                        </div>
                        
                        <div class="spm-stat-row">
                            <span class="spm-stat-label">理解済み:</span>
                            <span class="spm-stat-value"><?php echo number_format($subject_stat->understood_count); ?> (<?php echo $subject_stat->understanding_rate; ?>%)</span>
                        </div>
                        
                        <div class="spm-stat-row">
                            <span class="spm-stat-label">習得済み:</span>
                            <span class="spm-stat-value"><?php echo number_format($subject_stat->mastered_count); ?> (<?php echo $subject_stat->mastery_rate; ?>%)</span>
                        </div>
                        
                        <div class="spm-progress-bars">
                            <div class="spm-progress-item">
                                <label>理解度</label>
                                <div class="spm-progress-bar">
                                    <div class="spm-progress-fill" style="width: <?php echo $subject_stat->understanding_rate; ?>%; background-color: <?php echo $subject_stat->progress_color; ?>"></div>
                                </div>
                            </div>
                            
                            <div class="spm-progress-item">
                                <label>習得度</label>
                                <div class="spm-progress-bar">
                                    <div class="spm-progress-fill" style="width: <?php echo $subject_stat->mastery_rate; ?>%; background-color: <?php echo $subject_stat->progress_color; ?>"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 日別活動グラフ -->
    <div class="spm-admin-section">
        <h2>日別学習活動（過去30日）</h2>
        <div class="spm-daily-chart">
            <canvas id="dailyActivityChart" width="800" height="300"></canvas>
        </div>
    </div>

    <!-- 進捗データテーブル -->
    <div class="spm-admin-section">
        <h2>詳細進捗データ</h2>
        
        <?php if ($progress_data): ?>
            <div class="spm-table-container">
                <table class="spm-progress-table">
                    <thead>
                        <tr>
                            <th>ユーザー</th>
                            <th>科目</th>
                            <th>学習箇所</th>
                            <th>理解度</th>
                            <th>習得度</th>
                            <th>最終更新</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($progress_data as $progress): ?>
                            <tr>
                                <td>
                                    <div class="spm-user-info">
                                        <strong><?php echo esc_html($progress->display_name); ?></strong>
                                        <small><?php echo esc_html($progress->user_email); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="spm-subject-badge" style="background-color: <?php echo $progress->progress_color; ?>">
                                        <?php echo esc_html($progress->subject_name); ?>
                                    </span>
                                </td>
                                <td>
                                    第<?php echo $progress->chapter_number; ?>章 
                                    第<?php echo $progress->section_number; ?>節 
                                    項<?php echo $progress->item_number; ?>
                                </td>
                                <td>
                                    <span class="spm-status-badge <?php echo $progress->understanding_level ? 'understood' : 'not-understood'; ?>">
                                        <?php echo $progress->understanding_level ? '理解済み' : '未理解'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="spm-status-badge <?php echo $progress->mastery_level ? 'mastered' : 'not-mastered'; ?>">
                                        <?php echo $progress->mastery_level ? '習得済み' : '未習得'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo date('Y/m/d H:i', strtotime($progress->last_updated)); ?>
                                </td>
                                <td>
                                    <button class="button button-small" onclick="viewUserProgress(<?php echo $progress->user_id; ?>, '<?php echo $progress->subject_key; ?>')">
                                        詳細表示
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="spm-pagination">
                <p>表示中: <?php echo count($progress_data); ?>件 (最新100件まで表示)</p>
            </div>
            
        <?php else: ?>
            <div class="spm-no-data">
                <p>条件に合致する進捗データがありません。</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ユーザー進捗詳細モーダル -->
<div id="userProgressModal" class="spm-modal" style="display: none;">
    <div class="spm-modal-content">
        <div class="spm-modal-header">
            <h3>ユーザー進捗詳細</h3>
            <button class="spm-modal-close" onclick="closeUserProgressModal()">&times;</button>
        </div>
        <div class="spm-modal-body">
            <div id="userProgressContent">
                <!-- Ajax で読み込まれるコンテンツ -->
            </div>
        </div>
    </div>
</div>

<style>
.spm-admin-stats {
    margin-bottom: 30px;
}

.spm-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
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

.smp-stat-icon {
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

.spm-admin-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.spm-admin-section h2 {
    margin-top: 0;
    border-bottom: 2px solid #2271b1;
    padding-bottom: 10px;
}

.spm-filter-form {
    margin-bottom: 20px;
}

.spm-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.spm-filter-item label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.spm-filter-item select,
.spm-filter-item input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.spm-filter-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.spm-subject-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.spm-subject-stat-card {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 15px;
}

.spm-subject-stat-header {
    margin-bottom: 15px;
}

.spm-subject-stat-header h3 {
    margin: 0 0 5px 0;
    color: #333;
}

.spm-users-count {
    font-size: 0.9em;
    color: #666;
}

.spm-stat-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.spm-stat-label {
    font-weight: 500;
}

.spm-progress-bars {
    margin-top: 15px;
}

.spm-progress-item {
    margin-bottom: 10px;
}

.spm-progress-item label {
    display: block;
    font-size: 0.9em;
    margin-bottom: 5px;
}

.spm-progress-bar {
    height: 20px;
    background: #e0e0e0;
    border-radius: 10px;
    overflow: hidden;
}

.spm-progress-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.spm-daily-chart {
    height: 300px;
    margin: 20px 0;
}

.spm-table-container {
    overflow-x: auto;
}

.spm-progress-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.spm-progress-table th,
.spm-progress-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.spm-progress-table th {
    background: #f1f1f1;
    font-weight: 600;
}

.spm-user-info strong {
    display: block;
}

.spm-user-info small {
    color: #666;
}

.spm-subject-badge {
    display: inline-block;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.85em;
    font-weight: 500;
}

.spm-status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: 500;
}

.spm-status-badge.understood {
    background: #e3f2fd;
    color: #1976d2;
}

.spm-status-badge.not-understood {
    background: #fafafa;
    color: #666;
}

.spm-status-badge.mastered {
    background: #e8f5e8;
    color: #2e7d32;
}

.spm-status-badge.not-mastered {
    background: #fafafa;
    color: #666;
}

.spm-pagination {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.spm-no-data {
    text-align: center;
    padding: 40px;
    color: #666;
}

.spm-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.spm-modal-content {
    background: white;
    border-radius: 8px;
    max-width: 800px;
    width: 90%;
    max-height: 80%;
    overflow-y: auto;
}

.spm-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.spm-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.spm-modal-body {
    padding: 20px;
}

@media (max-width: 768px) {
    .spm-stats-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .spm-filter-grid {
        grid-template-columns: 1fr;
    }
    
    .spm-subject-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .spm-filter-actions {
        flex-direction: column;
    }
    
    .spm-progress-table {
        font-size: 0.9em;
    }
}
</style>

<script>
// ユーザー進捗詳細表示
function viewUserProgress(userId, subjectKey) {
    document.getElementById('userProgressModal').style.display = 'flex';
    document.getElementById('userProgressContent').innerHTML = '<div class="loading">読み込み中...</div>';
    
    // Ajax実装（実際のプロジェクトでは適切なAjax処理を実装）
    setTimeout(() => {
        document.getElementById('userProgressContent').innerHTML = `
            <h4>ユーザーID: ${userId} - 科目: ${subjectKey}</h4>
            <p>詳細な進捗データがここに表示されます。</p>
            <p>（実装時にAjaxで動的に読み込み）</p>
        `;
    }, 1000);
}

// モーダルを閉じる
function closeUserProgressModal() {
    document.getElementById('userProgressModal').style.display = 'none';
}

// CSVエクスポート
function exportProgressData() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '?' + params.toString();
}

// 日別活動チャート（Chart.jsを使用する場合）
document.addEventListener('DOMContentLoaded', function() {
    const dailyData = <?php echo json_encode($daily_stats); ?>;
    
    // 簡易チャート実装（実際のプロジェクトではChart.jsなどを使用）
    console.log('日別データ:', dailyData);
});
</script>