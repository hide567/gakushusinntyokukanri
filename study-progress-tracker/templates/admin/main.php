<?php
/**
 * 管理画面メインテンプレート（完全修正版）
 *
 * @package StudyProgressTracker
 */

if (!defined('ABSPATH')) {
    exit;
}

// 通知メッセージの表示
settings_errors('spt_messages');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=progress-tracker&tab=subjects" class="nav-tab <?php echo $active_tab == 'subjects' ? 'nav-tab-active' : ''; ?>">
            <?php _e('科目管理', 'study-progress-tracker'); ?>
        </a>
        <a href="?page=progress-tracker&tab=structure" class="nav-tab <?php echo $active_tab == 'structure' ? 'nav-tab-active' : ''; ?>">
            <?php _e('科目構造設定', 'study-progress-tracker'); ?>
        </a>
        <a href="?page=progress-tracker&tab=progress" class="nav-tab <?php echo $active_tab == 'progress' ? 'nav-tab-active' : ''; ?>">
            <?php _e('進捗管理', 'study-progress-tracker'); ?>
        </a>
        <a href="?page=progress-tracker&tab=usage" class="nav-tab <?php echo $active_tab == 'usage' ? 'nav-tab-active' : ''; ?>">
            <?php _e('使い方', 'study-progress-tracker'); ?>
        </a>
    </h2>
    
    <?php
    // 各タブの内容を表示
    switch ($active_tab) {
        case 'subjects':
            include SPT_PLUGIN_PATH . 'templates/admin/tab-subjects.php';
            break;
        case 'structure':
            include SPT_PLUGIN_PATH . 'templates/admin/tab-structure.php';
            break;
        case 'progress':
            include SPT_PLUGIN_PATH . 'templates/admin/tab-progress.php';
            break;
        case 'usage':
            include SPT_PLUGIN_PATH . 'templates/admin/tab-usage.php';
            break;
        default:
            include SPT_PLUGIN_PATH . 'templates/admin/tab-subjects.php';
    }
    ?>
</div>