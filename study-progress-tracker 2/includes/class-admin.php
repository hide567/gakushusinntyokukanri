<?php
/**
 * 管理画面機能を処理するクラス（完全修正版）
 *
 * @package StudyProgressTracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SPT_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * 管理メニューを追加
     */
    public function add_admin_menu() {
        add_menu_page(
            __('学習進捗管理', 'study-progress-tracker'),
            __('学習進捗管理', 'study-progress-tracker'),
            'manage_options',
            'progress-tracker',
            array($this, 'render_admin_page'),
            'dashicons-welcome-learn-more',
            30
        );
    }
    
    /**
     * 管理画面の表示
     */
    public function render_admin_page() {
        // 権限チェック
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // データ取得
        $subjects = get_option('progress_tracker_subjects', $this->get_default_subjects());
        $chapter_structure = get_option('progress_tracker_chapters', $this->get_default_chapters());
        $progress_data = get_option('progress_tracker_progress', array());
        $progress_settings = get_option('progress_tracker_check_settings', $this->get_default_settings());
        $custom_subjects = get_option('progress_tracker_custom_subjects', array());
        
        // POST処理
        $this->handle_post_requests($subjects, $chapter_structure, $progress_data, $progress_settings, $custom_subjects);
        
        // タブの処理
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'subjects';
        
        // 管理画面を表示
        include SPT_PLUGIN_PATH . 'templates/admin/main.php';
    }
    
    /**
     * POSTリクエストの処理
     */
    private function handle_post_requests(&$subjects, &$chapter_structure, &$progress_data, &$progress_settings, &$custom_subjects) {
        // 科目と章の設定を保存
        if (isset($_POST['save_structure']) && wp_verify_nonce($_POST['_wpnonce'], 'spt_save_structure')) {
            $this->save_chapter_structure($subjects, $chapter_structure);
        }
        
        // 進捗データを保存
        if (isset($_POST['save_progress']) && wp_verify_nonce($_POST['_wpnonce'], 'spt_save_progress')) {
            $this->save_progress_data($subjects, $chapter_structure, $progress_data);
        }
        
        // 進捗データのリセット
        if (isset($_POST['reset_progress']) && isset($_POST['confirm_reset']) && $_POST['confirm_reset'] == '1' && wp_verify_nonce($_POST['_wpnonce'], 'spt_reset_progress')) {
            $this->reset_progress($progress_data);
        }
        
        // カスタム科目の追加
        if (isset($_POST['add_subject']) && wp_verify_nonce($_POST['_wpnonce'], 'spt_add_subject')) {
            $this->add_custom_subject($subjects, $custom_subjects, $chapter_structure, $progress_data);
        }
        
        // 科目の編集
        if (isset($_POST['edit_subject']) && wp_verify_nonce($_POST['_wpnonce'], 'spt_edit_subject')) {
            $this->edit_subject($subjects, $custom_subjects, $chapter_structure);
        }
        
        // 科目の削除
        if (isset($_POST['delete_subject']) && wp_verify_nonce($_POST['_wpnonce'], 'spt_delete_subject')) {
            $this->delete_subject($subjects, $custom_subjects, $chapter_structure, $progress_data);
        }
        
        // 進捗チェック設定の保存
        if (isset($_POST['save_check_settings']) && wp_verify_nonce($_POST['_wpnonce'], 'spt_save_check_settings')) {
            $this->save_check_settings($progress_settings);
        }
    }
    
    /**
     * 章構造を保存
     */
    private function save_chapter_structure($subjects, &$chapter_structure) {
        $updated_structure = array();
        
        foreach ($subjects as $subject_key => $subject_name) {
            $total_chapters = isset($_POST[$subject_key . '_chapters']) ? intval($_POST[$subject_key . '_chapters']) : 0;
            
            $chapters = array();
            for ($i = 1; $i <= $total_chapters; $i++) {
                $chapter_title = isset($_POST[$subject_key . '_chapter_' . $i]) ? 
                    sanitize_text_field($_POST[$subject_key . '_chapter_' . $i]) : '第' . $i . '章';
                $section_count = isset($_POST[$subject_key . '_sections_' . $i]) ? 
                    intval($_POST[$subject_key . '_sections_' . $i]) : 1;
                
                $sections = array();
                for ($j = 1; $j <= $section_count; $j++) {
                    $section_title = isset($_POST[$subject_key . '_chapter_' . $i . '_section_' . $j . '_title']) ? 
                        sanitize_text_field($_POST[$subject_key . '_chapter_' . $i . '_section_' . $j . '_title']) : '節' . $j;
                    $item_count = isset($_POST[$subject_key . '_chapter_' . $i . '_section_' . $j . '_items']) ? 
                        intval($_POST[$subject_key . '_chapter_' . $i . '_section_' . $j . '_items']) : 1;
                    
                    $items = array();
                    for ($k = 1; $k <= $item_count; $k++) {
                        $item_title = isset($_POST[$subject_key . '_chapter_' . $i . '_section_' . $j . '_item_' . $k . '_title']) ? 
                            sanitize_text_field($_POST[$subject_key . '_chapter_' . $i . '_section_' . $j . '_item_' . $k . '_title']) : '項' . $k;
                        $items[$k] = array(
                            'title' => $item_title
                        );
                    }
                    
                    $sections[$j] = array(
                        'title' => $section_title,
                        'items' => $item_count,
                        'item_data' => $items
                    );
                }
                
                $chapters[$i] = array(
                    'title' => $chapter_title,
                    'sections' => $section_count,
                    'section_data' => $sections
                );
            }
            
            $progress_color = isset($chapter_structure[$subject_key]['color']) ? 
                $chapter_structure[$subject_key]['color'] : '#4CAF50';
            
            $updated_structure[$subject_key] = array(
                'total' => $total_chapters,
                'chapters' => $chapters,
                'color' => $progress_color
            );
        }
        
        update_option('progress_tracker_chapters', $updated_structure);
        $chapter_structure = $updated_structure;
        
        $this->add_admin_notice(__('科目と章の構造を保存しました。', 'study-progress-tracker'), 'success');
    }
    
    /**
     * 進捗データを保存
     */
    private function save_progress_data($subjects, $chapter_structure, &$progress_data) {
        $updated_progress = array();
        
        foreach ($subjects as $subject_key => $subject_name) {
            $subject_progress = array();
            
            if (isset($chapter_structure[$subject_key]['chapters']) && is_array($chapter_structure[$subject_key]['chapters'])) {
                foreach ($chapter_structure[$subject_key]['chapters'] as $chapter_id => $chapter_data) {
                    $completed_sections = array();
                    
                    if (isset($chapter_data['section_data']) && is_array($chapter_data['section_data'])) {
                        foreach ($chapter_data['section_data'] as $section_id => $section_data) {
                            $completed_items = array();
                            
                            if (isset($section_data['item_data']) && is_array($section_data['item_data'])) {
                                foreach ($section_data['item_data'] as $item_id => $item_data) {
                                    $field_name = $subject_key . '_chapter_' . $chapter_id . '_section_' . $section_id . '_item_' . $item_id;
                                    $second_check_field = $field_name . '_second';
                                    
                                    $check_level = 0;
                                    if (isset($_POST[$field_name]) && $_POST[$field_name] == '1') {
                                        $check_level = 1;
                                    }
                                    if (isset($_POST[$second_check_field]) && $_POST[$second_check_field] == '1') {
                                        $check_level = 2;
                                    }
                                    
                                    if ($check_level > 0) {
                                        $completed_items[$item_id] = $check_level;
                                    }
                                }
                            } else {
                                // 旧形式の節ベースのチェック
                                $field_name = $subject_key . '_chapter_' . $chapter_id . '_section_' . $section_id;
                                $second_check_field = $field_name . '_second';
                                
                                $check_level = 0;
                                if (isset($_POST[$field_name]) && $_POST[$field_name] == '1') {
                                    $check_level = 1;
                                }
                                if (isset($_POST[$second_check_field]) && $_POST[$second_check_field] == '1') {
                                    $check_level = 2;
                                }
                                
                                if ($check_level > 0) {
                                    $completed_sections[$section_id] = $check_level;
                                }
                            }
                            
                            if (!empty($completed_items)) {
                                $completed_sections[$section_id] = array('items' => $completed_items);
                            }
                        }
                    }
                    
                    if (!empty($completed_sections)) {
                        $subject_progress[$chapter_id] = $completed_sections;
                    }
                }
            }
            
            // 進捗率を計算
            $percent = $this->calculate_progress_percent($subject_key, $subject_progress, $chapter_structure);
            
            $updated_progress[$subject_key] = array(
                'chapters' => $subject_progress,
                'percent' => $percent
            );
        }
        
        update_option('progress_tracker_progress', $updated_progress);
        $progress_data = $updated_progress;
        
        $this->add_admin_notice(__('進捗状況を更新しました。', 'study-progress-tracker'), 'success');
    }
    
    /**
     * 進捗率を計算
     */
    private function calculate_progress_percent($subject_key, $subject_progress, $chapter_structure) {
        $total_items = 0;
        $completed_count = 0;
        
        if (isset($chapter_structure[$subject_key]['chapters']) && is_array($chapter_structure[$subject_key]['chapters'])) {
            foreach ($chapter_structure[$subject_key]['chapters'] as $chapter_id => $chapter_data) {
                if (isset($chapter_data['section_data']) && is_array($chapter_data['section_data'])) {
                    foreach ($chapter_data['section_data'] as $section_id => $section_data) {
                        if (isset($section_data['item_data']) && is_array($section_data['item_data'])) {
                            $total_items += count($section_data['item_data']);
                            
                            if (isset($subject_progress[$chapter_id][$section_id]['items'])) {
                                $completed_count += count($subject_progress[$chapter_id][$section_id]['items']);
                            }
                        } else {
                            $total_items++;
                            if (isset($subject_progress[$chapter_id][$section_id]) && !is_array($subject_progress[$chapter_id][$section_id])) {
                                $completed_count++;
                            }
                        }
                    }
                } else {
                    $total_items += $chapter_data['sections'];
                    if (isset($subject_progress[$chapter_id])) {
                        $completed_count += count($subject_progress[$chapter_id]);
                    }
                }
            }
        }
        
        return ($total_items > 0) ? min(100, ceil(($completed_count / $total_items) * 100)) : 0;
    }
    
    /**
     * 進捗データのリセット
     */
    private function reset_progress(&$progress_data) {
        $reset_subject = isset($_POST['reset_subject']) ? sanitize_key($_POST['reset_subject']) : 'all';
        
        if ($reset_subject == 'all') {
            update_option('progress_tracker_progress', array());
            $progress_data = array();
            $this->add_admin_notice(__('全科目の進捗をリセットしました。', 'study-progress-tracker'), 'success');
        } else {
            if (isset($progress_data[$reset_subject])) {
                unset($progress_data[$reset_subject]);
                update_option('progress_tracker_progress', $progress_data);
                $this->add_admin_notice(__('進捗をリセットしました。', 'study-progress-tracker'), 'success');
            }
        }
    }
    
    /**
     * カスタム科目を追加
     */
    private function add_custom_subject(&$subjects, &$custom_subjects, &$chapter_structure, &$progress_data) {
        $subject_key = sanitize_key($_POST['new_subject_key']);
        $subject_name = sanitize_text_field($_POST['new_subject_name']);
        $total_chapters = intval($_POST['new_subject_chapters']);
        $progress_color = isset($_POST['progress_color']) ? sanitize_hex_color($_POST['progress_color']) : '#4CAF50';
        
        if (!empty($subject_key) && !empty($subject_name) && $total_chapters > 0) {
            if (!isset($subjects[$subject_key])) {
                $subjects[$subject_key] = $subject_name;
                update_option('progress_tracker_subjects', $subjects);
                
                $custom_subjects[$subject_key] = $subject_name;
                update_option('progress_tracker_custom_subjects', $custom_subjects);
                
                $chapter_structure[$subject_key] = array(
                    'total' => $total_chapters,
                    'chapters' => array(),
                    'color' => $progress_color
                );
                update_option('progress_tracker_chapters', $chapter_structure);
                
                $progress_data[$subject_key] = array(
                    'chapters' => array(),
                    'percent' => 0
                );
                update_option('progress_tracker_progress', $progress_data);
                
                $this->add_admin_notice(__('科目を追加しました。', 'study-progress-tracker'), 'success');
            } else {
                $this->add_admin_notice(__('このキーは既に使用されています。', 'study-progress-tracker'), 'error');
            }
        } else {
            $this->add_admin_notice(__('すべての項目を入力してください。', 'study-progress-tracker'), 'error');
        }
    }
    
    /**
     * 科目を編集
     */
    private function edit_subject(&$subjects, &$custom_subjects, &$chapter_structure) {
        $subject_key = sanitize_key($_POST['edit_subject_key']);
        $new_subject_name = sanitize_text_field($_POST['edit_subject_name']);
        $progress_color = sanitize_hex_color($_POST['edit_progress_color']);
        
        if (isset($subjects[$subject_key])) {
            $subjects[$subject_key] = $new_subject_name;
            update_option('progress_tracker_subjects', $subjects);
            
            if (isset($custom_subjects[$subject_key])) {
                $custom_subjects[$subject_key] = $new_subject_name;
                update_option('progress_tracker_custom_subjects', $custom_subjects);
            }
            
            if (isset($chapter_structure[$subject_key])) {
                $chapter_structure[$subject_key]['color'] = $progress_color;
                update_option('progress_tracker_chapters', $chapter_structure);
            }
            
            $this->add_admin_notice(__('科目を更新しました。', 'study-progress-tracker'), 'success');
        }
    }
    
    /**
     * 科目を削除
     */
    private function delete_subject(&$subjects, &$custom_subjects, &$chapter_structure, &$progress_data) {
        $subject_key = sanitize_key($_POST['delete_subject']);
        
        if (isset($subjects[$subject_key])) {
            unset($subjects[$subject_key]);
            update_option('progress_tracker_subjects', $subjects);
            
            if (isset($custom_subjects[$subject_key])) {
                unset($custom_subjects[$subject_key]);
                update_option('progress_tracker_custom_subjects', $custom_subjects);
            }
            
            if (isset($chapter_structure[$subject_key])) {
                unset($chapter_structure[$subject_key]);
                update_option('progress_tracker_chapters', $chapter_structure);
            }
            
            if (isset($progress_data[$subject_key])) {
                unset($progress_data[$subject_key]);
                update_option('progress_tracker_progress', $progress_data);
            }
            
            $this->add_admin_notice(__('科目を削除しました。', 'study-progress-tracker'), 'success');
        }
    }
    
    /**
     * チェック設定を保存
     */
    private function save_check_settings(&$progress_settings) {
        $updated_settings = array(
            'first_check_color' => sanitize_hex_color($_POST['first_check_color']),
            'second_check_color' => sanitize_hex_color($_POST['second_check_color'])
        );
        
        update_option('progress_tracker_check_settings', $updated_settings);
        $progress_settings = $updated_settings;
        
        $this->add_admin_notice(__('チェック設定を保存しました。', 'study-progress-tracker'), 'success');
    }
    
    /**
     * 管理画面の通知を追加
     */
    private function add_admin_notice($message, $type = 'success') {
        add_settings_error('spt_messages', 'spt_message', $message, $type);
    }
    
    /**
     * デフォルト科目を取得
     */
    private function get_default_subjects() {
        return array(
            'constitutional' => '憲法',
            'administrative' => '行政法',
            'civil' => '民法',
            'commercial' => '商法・会社法'
        );
    }
    
    /**
     * デフォルト章構造を取得
     */
    private function get_default_chapters() {
        return array(
            'constitutional' => array('total' => 15, 'chapters' => array(), 'color' => '#4CAF50'),
            'administrative' => array('total' => 15, 'chapters' => array(), 'color' => '#4CAF50'),
            'civil' => array('total' => 20, 'chapters' => array(), 'color' => '#4CAF50'),
            'commercial' => array('total' => 10, 'chapters' => array(), 'color' => '#4CAF50')
        );
    }
    
    /**
     * デフォルト設定を取得
     */
    private function get_default_settings() {
        return array(
            'first_check_color' => '#e6f7e6',
            'second_check_color' => '#ffebcc'
        );
    }
}