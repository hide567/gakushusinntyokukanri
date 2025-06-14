<?php
/**
 * 進捗表示ウィジェット
 *
 * @package StudyProgressTracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SPT_Progress_Widget extends WP_Widget {
    
    /**
     * コンストラクタ
     */
    public function __construct() {
        parent::__construct(
            'spt_progress_widget',
            __('学習進捗状況', 'study-progress-tracker'),
            array(
                'description' => __('学習進捗を表示します。', 'study-progress-tracker'),
                'customize_selective_refresh' => true,
            )
        );
    }
    
    /**
     * ウィジェットの表示
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        $title = !empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : __('学習進捗状況', 'study-progress-tracker');
        
        if (!empty($title)) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }
        
        // データ取得
        $subjects = get_option('progress_tracker_subjects', array(
            'constitutional' => '憲法',
            'administrative' => '行政法',
            'civil' => '民法',
            'commercial' => '商法・会社法'
        ));
        
        $progress_data = get_option('progress_tracker_progress', array());
        $chapter_structure = get_option('progress_tracker_chapters', array());
        
        // 表示する科目をフィルタリング
        if (!empty($instance['subjects'])) {
            $selected_subjects = array();
            foreach ($instance['subjects'] as $key) {
                if (isset($subjects[$key])) {
                    $selected_subjects[$key] = $subjects[$key];
                }
            }
            if (!empty($selected_subjects)) {
                $subjects = $selected_subjects;
            }
        }
        
        ?>
        <div class="spt-widget-progress">
            <?php foreach ($subjects as $subject_key => $subject_name): ?>
                <?php
                $percent = isset($progress_data[$subject_key]['percent']) ? $progress_data[$subject_key]['percent'] : 0;
                $completed_chapters = 0;
                $mastered_chapters = 0;
                $total_chapters = isset($chapter_structure[$subject_key]['total']) ? $chapter_structure[$subject_key]['total'] : 0;
                
                // 完了した章と習得した章をカウント
                if (isset($progress_data[$subject_key]['chapters'])) {
                    $completed_chapters = count($progress_data[$subject_key]['chapters']);
                    
                    // 習得した章をカウント
                    foreach ($progress_data[$subject_key]['chapters'] as $chapter_id => $sections) {
                        $all_mastered = true;
                        $total_sections = isset($chapter_structure[$subject_key]['chapters'][$chapter_id]['sections']) ? 
                            $chapter_structure[$subject_key]['chapters'][$chapter_id]['sections'] : 0;
                            
                        if ($total_sections > 0) {
                            $mastered_count = 0;
                            foreach ($sections as $section_num => $level) {
                                if (is_array($level) && isset($level['items'])) {
                                    // 新形式の3階層構造
                                    $all_items_mastered = true;
                                    $total_items = isset($chapter_structure[$subject_key]['chapters'][$chapter_id]['section_data'][$section_num]['item_data']) ?
                                        count($chapter_structure[$subject_key]['chapters'][$chapter_id]['section_data'][$section_num]['item_data']) : 0;
                                    $mastered_items = 0;
                                    
                                    foreach ($level['items'] as $item_id => $item_level) {
                                        if ($item_level >= 2) {
                                            $mastered_items++;
                                        }
                                    }
                                    
                                    if ($total_items > 0 && $mastered_items == $total_items) {
                                        $mastered_count++;
                                    }
                                } else {
                                    // 旧形式の2階層構造
                                    if (is_numeric($level) && $level >= 2) {
                                        $mastered_count++;
                                    }
                                }
                            }
                            if ($mastered_count == $total_sections) {
                                $mastered_chapters++;
                            }
                        }
                    }
                }
                
                // 進捗バーの色
                $bar_color = isset($chapter_structure[$subject_key]['color']) ? $chapter_structure[$subject_key]['color'] : '#4CAF50';
                ?>
                <div class="spt-widget-subject">
                    <p class="subject-name"><?php echo esc_html($subject_name); ?></p>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo esc_attr($percent); ?>%; background-color: <?php echo esc_attr($bar_color); ?>;"></div>
                    </div>
                    <div class="progress-stats">
                        <span class="percent"><?php echo esc_html($percent); ?>%</span>
                        <span class="chapters">
                            <?php 
                            /* translators: 1: completed chapters, 2: total chapters */
                            printf(__('完了: %1$d/%2$d章', 'study-progress-tracker'), $completed_chapters, $total_chapters); 
                            ?>
                        </span>
                        <?php if (!empty($instance['show_mastered'])): ?>
                        <span class="mastered">
                            <?php 
                            /* translators: 1: mastered chapters, 2: total chapters */
                            printf(__('習得: %1$d/%2$d章', 'study-progress-tracker'), $mastered_chapters, $total_chapters); 
                            ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (!empty($instance['show_link'])): ?>
                <div class="widget-footer">
                    <a href="<?php echo esc_url($instance['link_url']); ?>" class="progress-link">
                        <?php echo esc_html($instance['link_text']); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
        .spt-widget-progress .spt-widget-subject {
            margin-bottom: 15px;
        }
        .spt-widget-progress .subject-name {
            margin: 0 0 5px 0;
            font-weight: bold;
        }
        .spt-widget-progress .progress-bar {
            height: 15px;
            background-color: #e9ecef;
            border-radius: 3px;
            margin-bottom: 5px;
            overflow: hidden;
        }
        .spt-widget-progress .progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        .spt-widget-progress .progress-stats {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            font-size: 0.85em;
            color: #666;
        }
        .spt-widget-progress .widget-footer {
            margin-top: 10px;
            text-align: center;
        }
        .spt-widget-progress .progress-link {
            font-size: 0.9em;
        }
        </style>
        <?php
        
        echo $args['after_widget'];
    }
    
    /**
     * ウィジェット設定フォーム
     */
    public function form($instance) {
        $defaults = array(
            'title' => __('学習進捗状況', 'study-progress-tracker'),
            'subjects' => array(),
            'show_mastered' => true,
            'show_link' => false,
            'link_text' => __('詳細を見る', 'study-progress-tracker'),
            'link_url' => '',
        );
        
        $instance = wp_parse_args((array) $instance, $defaults);
        
        // 利用可能な科目を取得
        $available_subjects = get_option('progress_tracker_subjects', array(
            'constitutional' => '憲法',
            'administrative' => '行政法',
            'civil' => '民法',
            'commercial' => '商法・会社法'
        ));
        
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php _e('タイトル:', 'study-progress-tracker'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($instance['title']); ?>">
        </p>
        
        <p>
            <label><?php _e('表示する科目:', 'study-progress-tracker'); ?></label><br>
            <?php foreach ($available_subjects as $key => $name): ?>
                <label>
                    <input type="checkbox" 
                           name="<?php echo esc_attr($this->get_field_name('subjects')); ?>[]" 
                           value="<?php echo esc_attr($key); ?>"
                           <?php checked(in_array($key, $instance['subjects'])); ?>>
                    <?php echo esc_html($name); ?>
                </label><br>
            <?php endforeach; ?>
            <small><?php _e('何も選択しない場合は全科目を表示', 'study-progress-tracker'); ?></small>
        </p>
        
        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_mastered')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_mastered')); ?>" 
                   value="1" <?php checked($instance['show_mastered']); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_mastered')); ?>">
                <?php _e('習得章数を表示', 'study-progress-tracker'); ?>
            </label>
        </p>
        
        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('show_link')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('show_link')); ?>" 
                   value="1" <?php checked($instance['show_link']); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_link')); ?>">
                <?php _e('リンクを表示', 'study-progress-tracker'); ?>
            </label>
        </p>
        
        <div style="<?php echo $instance['show_link'] ? '' : 'display:none;'; ?>" class="link-settings">
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('link_text')); ?>">
                    <?php _e('リンクテキスト:', 'study-progress-tracker'); ?>
                </label>
                <input class="widefat" 
                       id="<?php echo esc_attr($this->get_field_id('link_text')); ?>" 
                       name="<?php echo esc_attr($this->get_field_name('link_text')); ?>" 
                       type="text" value="<?php echo esc_attr($instance['link_text']); ?>">
            </p>
            
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('link_url')); ?>">
                    <?php _e('リンクURL:', 'study-progress-tracker'); ?>
                </label>
                <input class="widefat" 
                       id="<?php echo esc_attr($this->get_field_id('link_url')); ?>" 
                       name="<?php echo esc_attr($this->get_field_name('link_url')); ?>" 
                       type="url" value="<?php echo esc_attr($instance['link_url']); ?>">
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#<?php echo esc_js($this->get_field_id('show_link')); ?>').change(function() {
                $(this).closest('.widget-content').find('.link-settings').toggle(this.checked);
            });
        });
        </script>
        <?php
    }
    
    /**
     * ウィジェット設定の更新
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['subjects'] = (!empty($new_instance['subjects'])) ? array_map('sanitize_key', $new_instance['subjects']) : array();
        $instance['show_mastered'] = !empty($new_instance['show_mastered']);
        $instance['show_link'] = !empty($new_instance['show_link']);
        $instance['link_text'] = (!empty($new_instance['link_text'])) ? sanitize_text_field($new_instance['link_text']) : '';
        $instance['link_url'] = (!empty($new_instance['link_url'])) ? esc_url_raw($new_instance['link_url']) : '';
        
        return $instance;
    }
}