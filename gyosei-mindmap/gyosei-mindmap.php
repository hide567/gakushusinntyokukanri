<?php
/**
 * Plugin Name: 行政書士の道 - マインドマップ
 * Description: 行政書士試験対策用のインタラクティブマインドマップ機能
 * Version: 1.0.0
 * Author: 行政書士の道開発チーム
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

class GyoseiMindMap {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('mindmap', array($this, 'mindmap_shortcode'));
    }
    
    public function init() {
        // 初期化処理
    }
    
    public function enqueue_scripts() {
        // CSS読み込み
        wp_enqueue_style(
            'gyosei-mindmap-css',
            plugin_dir_url(__FILE__) . 'assets/mindmap.css',
            array(),
            '1.0.0'
        );
        
        // JavaScript読み込み
        wp_enqueue_script(
            'gyosei-mindmap-js',
            plugin_dir_url(__FILE__) . 'assets/mindmap.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // データをJavaScriptに渡す
        wp_localize_script('gyosei-mindmap-js', 'mindmapData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mindmap_nonce'),
            'sampleData' => $this->get_sample_data()
        ));
    }
    
    public function mindmap_shortcode($atts) {
        $atts = shortcode_atts(array(
            'data' => 'gyosei',
            'title' => '行政法',
            'width' => '100%',
            'height' => '400px'
        ), $atts);
        
        $unique_id = 'mindmap-' . uniqid();
        
        ob_start();
        ?>
        <div class="mindmap-container" data-mindmap-id="<?php echo esc_attr($unique_id); ?>">
            <div class="mindmap-header">
                <h3 class="mindmap-title"><?php echo esc_html($atts['title']); ?></h3>
                <div class="mindmap-controls">
                    <button class="mindmap-btn" data-action="zoom-in">🔍+</button>
                    <button class="mindmap-btn" data-action="zoom-out">🔍-</button>
                    <button class="mindmap-btn" data-action="reset">⚪</button>
                    <button class="mindmap-btn" data-action="fullscreen">⛶</button>
                    <button class="mindmap-theme-toggle" data-action="toggle-theme">🌙</button>
                </div>
            </div>
            <div class="mindmap-canvas" 
                 id="<?php echo esc_attr($unique_id); ?>"
                 style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;"
                 data-mindmap-type="<?php echo esc_attr($atts['data']); ?>">
                <!-- マインドマップがここに描画される -->
            </div>
            <div class="mindmap-loading">
                <span>マインドマップを読み込み中...</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    private function get_sample_data() {
        return array(
            'gyosei' => array(
                'title' => '行政法',
                'nodes' => array(
                    array(
                        'id' => 'root',
                        'text' => '行政法',
                        'x' => 400,
                        'y' => 200,
                        'level' => 0,
                        'color' => '#3f51b5',
                        'icon' => '⚖️',
                        'progress' => 75,
                        'status' => 'in-progress',
                        'description' => '行政に関する法律の総称。国民の権利保護と行政の適正な運営を図る。'
                    ),
                    array(
                        'id' => 'general',
                        'text' => '行政法総論',
                        'x' => 200,
                        'y' => 100,
                        'level' => 1,
                        'color' => '#303f9f',
                        'icon' => '📚',
                        'progress' => 60,
                        'status' => 'in-progress',
                        'description' => '行政法の基本原理・原則を学ぶ分野。行政行為、行政裁量などの基礎概念を扱う。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'procedure',
                        'text' => '行政手続法',
                        'x' => 600,
                        'y' => 100,
                        'level' => 1,
                        'color' => '#303f9f',
                        'icon' => '📋',
                        'progress' => 85,
                        'status' => 'completed',
                        'description' => '行政庁の処分、行政指導及び届出に関する手続を定めた法律。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'case_law',
                        'text' => '行政事件訴訟法',
                        'x' => 200,
                        'y' => 300,
                        'level' => 1,
                        'color' => '#303f9f',
                        'icon' => '🏛️',
                        'progress' => 40,
                        'status' => 'in-progress',
                        'description' => '行政事件訴訟に関する手続を定めた法律。取消訴訟、無効等確認訴訟などを規定。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'compensation',
                        'text' => '国家賠償法',
                        'x' => 600,
                        'y' => 300,
                        'level' => 1,
                        'color' => '#303f9f',
                        'icon' => '💰',
                        'progress' => 20,
                        'status' => 'not-started',
                        'description' => '国又は公共団体の損害賠償責任について定めた法律。',
                        'parent' => 'root'
                    ),
                    // サブノード
                    array(
                        'id' => 'admin_act',
                        'text' => '行政行為',
                        'x' => 100,
                        'y' => 50,
                        'level' => 2,
                        'color' => '#1a237e',
                        'icon' => '⚡',
                        'progress' => 70,
                        'status' => 'in-progress',
                        'description' => '行政庁の処分その他公権力の行使に当たる行為。許可、認可、特許など。',
                        'parent' => 'general'
                    ),
                    array(
                        'id' => 'discretion',
                        'text' => '行政裁量',
                        'x' => 100,
                        'y' => 150,
                        'level' => 2,
                        'color' => '#1a237e',
                        'icon' => '⚖️',
                        'progress' => 50,
                        'status' => 'in-progress',
                        'description' => '行政庁に認められた判断の余地。羈束裁量と自由裁量に分類される。',
                        'parent' => 'general'
                    ),
                    array(
                        'id' => 'notification',
                        'text' => '申請・届出',
                        'x' => 700,
                        'y' => 50,
                        'level' => 2,
                        'color' => '#1a237e',
                        'icon' => '📝',
                        'progress' => 90,
                        'status' => 'completed',
                        'description' => '法令に基づく申請・届出の手続き。標準処理期間の設定などを規定。',
                        'parent' => 'procedure'
                    ),
                    array(
                        'id' => 'hearing',
                        'text' => '聴聞・弁明',
                        'x' => 700,
                        'y' => 150,
                        'level' => 2,
                        'color' => '#1a237e',
                        'icon' => '👂',
                        'progress' => 80,
                        'status' => 'completed',
                        'description' => '不利益処分を行う際の事前手続き。聴聞手続きと弁明機会の付与。',
                        'parent' => 'procedure'
                    )
                ),
                'connections' => array(
                    array('from' => 'root', 'to' => 'general'),
                    array('from' => 'root', 'to' => 'procedure'),
                    array('from' => 'root', 'to' => 'case_law'),
                    array('from' => 'root', 'to' => 'compensation'),
                    array('from' => 'general', 'to' => 'admin_act'),
                    array('from' => 'general', 'to' => 'discretion'),
                    array('from' => 'procedure', 'to' => 'notification'),
                    array('from' => 'procedure', 'to' => 'hearing')
                )
            ),
            'minpo' => array(
                'title' => '民法',
                'nodes' => array(
                    array(
                        'id' => 'root',
                        'text' => '民法',
                        'x' => 400,
                        'y' => 200,
                        'level' => 0,
                        'color' => '#e91e63',
                        'icon' => '📖',
                        'progress' => 65,
                        'status' => 'in-progress',
                        'description' => '私人間の法律関係を規律する私法の一般法。財産関係と家族関係を規定。'
                    ),
                    array(
                        'id' => 'general_rule',
                        'text' => '総則',
                        'x' => 200,
                        'y' => 100,
                        'level' => 1,
                        'color' => '#c2185b',
                        'icon' => '🏛️',
                        'progress' => 80,
                        'status' => 'completed',
                        'description' => '民法の基本原則、権利能力、意思表示、代理、時効などの基礎概念。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'property',
                        'text' => '物権',
                        'x' => 600,
                        'y' => 100,
                        'level' => 1,
                        'color' => '#c2185b',
                        'icon' => '🏠',
                        'progress' => 70,
                        'status' => 'in-progress',
                        'description' => '物に対する支配権。所有権、用益物権、担保物権に分類される。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'obligation',
                        'text' => '債権',
                        'x' => 200,
                        'y' => 300,
                        'level' => 1,
                        'color' => '#c2185b',
                        'icon' => '💼',
                        'progress' => 45,
                        'status' => 'in-progress',
                        'description' => '特定人に対して一定の行為を請求する権利。契約、事務管理、不当利得、不法行為。',
                        'parent' => 'root'
                    ),
                    array(
                        'id' => 'family',
                        'text' => '親族・相続',
                        'x' => 600,
                        'y' => 300,
                        'level' => 1,
                        'color' => '#c2185b',
                        'icon' => '👨‍👩‍👧‍👦',
                        'progress' => 30,
                        'status' => 'not-started',
                        'description' => '婚姻、親子関係、相続に関する法律関係を規定。',
                        'parent' => 'root'
                    )
                ),
                'connections' => array(
                    array('from' => 'root', 'to' => 'general_rule'),
                    array('from' => 'root', 'to' => 'property'),
                    array('from' => 'root', 'to' => 'obligation'),
                    array('from' => 'root', 'to' => 'family')
                )
            )
        );
    }
}

// プラグインの初期化
new GyoseiMindMap();