<?php
/**
 * ショートコード機能を処理するクラス
 *
 * @package StudyProgressTracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SPT_Shortcodes {
    
    public function __construct() {
        add_shortcode('study_progress', array($this, 'render_progress_tracker'));
        add_shortcode('exam_countdown', array($this, 'render_exam_countdown'));
    }
    
    /**
     * 学習進捗表示ショートコード
     */
    public function render_progress_tracker($atts) {
        // 属性の初期化
        $atts = shortcode_atts(array(
            'subject' => '',        // 特定の科目のみ表示
            'interactive' => 'yes', // インタラクティブモード
            'style' => 'default',   // デザインスタイル
        ), $atts, 'study_progress');
        
        // インタラクティブモードの設定
        $interactive = $atts['interactive'] === 'yes' && is_user_logged_in();
        
        // データ取得
        $progress_data = get_option('progress_tracker_progress', array());
        $chapter_structure = get_option('progress_tracker_chapters', array());
        $progress_settings = get_option('progress_tracker_check_settings', array(
            'first_check_color' => '#e6f7e6',
            'second_check_color' => '#ffebcc'
        ));
        
        // 科目一覧
        $subjects = get_option('progress_tracker_subjects', array(
            'constitutional' => '憲法',
            'administrative' => '行政法',
            'civil' => '民法',
            'commercial' => '商法・会社法'
        ));
        
        // 表示する科目をフィルタリング
        if (!empty($atts['subject'])) {
            $subject_keys = explode(',', $atts['subject']);
            $filtered_subjects = array();
            
            foreach ($subject_keys as $key) {
                $key = trim($key);
                if (isset($subjects[$key])) {
                    $filtered_subjects[$key] = $subjects[$key];
                }
            }
            
            if (!empty($filtered_subjects)) {
                $subjects = $filtered_subjects;
            }
        }
        
        // スタイルの選択
        $style_class = 'progress-tracker-' . sanitize_html_class($atts['style']);
        
        // 出力開始
        ob_start();
        
        // テンプレートを読み込み
        include SPT_PLUGIN_PATH . 'templates/frontend/progress-tracker.php';
        
        return ob_get_clean();
    }
    
    /**
     * 試験カウントダウンショートコード
     */
    public function render_exam_countdown($atts) {
        $atts = shortcode_atts(array(
            'title' => '試験',
            'date' => '',
        ), $atts, 'exam_countdown');
        
        // 設定から試験日を取得
        $settings = get_option('progress_tracker_settings', array());
        $exam_date = !empty($atts['date']) ? $atts['date'] : 
                     (!empty($settings['exam_date']) ? $settings['exam_date'] : '');
        
        if (empty($exam_date)) {
            return '';
        }
        
        $exam_timestamp = strtotime($exam_date);
        $today = current_time('timestamp');
        $days_left = floor(($exam_timestamp - $today) / (60 * 60 * 24));
        
        if ($days_left < 0) {
            return '<div class="exam-countdown post-exam">' . esc_html($atts['title']) . __('は終了しました。', 'study-progress-tracker') . '</div>';
        }
        
        ob_start();
        ?>
        <div class="exam-countdown">
            <?php echo esc_html($atts['title']); ?><?php _e('まであと', 'study-progress-tracker'); ?> 
            <span class="countdown-number"><?php echo esc_html($days_left); ?></span> 
            <?php _e('日', 'study-progress-tracker'); ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
}