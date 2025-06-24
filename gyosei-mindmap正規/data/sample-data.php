<?php
/**
 * 行政書士の道 - マインドマップ サンプルデータ
 * File: includes/sample-data.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class GyoseiMindMapSampleData {
    
    /**
     * 全サンプルデータを取得
     */
    public static function get_all_data() {
        return array(
            'gyosei' => self::get_gyosei_data(),
            'minpo' => self::get_minpo_data(),
            'kenpou' => self::get_kenpou_data(),
            'shoken' => self::get_shoken_data(),
            'general' => self::get_general_data()
        );
    }
    
    /**
     * 行政法サンプルデータ
     */
    public static function get_gyosei_data() {
        return array(
            'title' => '行政法体系マップ',
            'description' => '行政書士試験における行政法の全体像を把握するためのマインドマップ',
            'nodes' => array(
                // レベル0: 中心ノード
                array(
                    'id' => 'gyosei_root',
                    'text' => '行政法',
                    'x' => 400,
                    'y' => 250,
                    'level' => 0,
                    'color' => '#3f51b5',
                    'icon' => '⚖️',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '国家行政組織や行政作用に関する法律の総称'
                ),
                
                // レベル1: 主要分野
                array(
                    'id' => 'administrative_organization',
                    'text' => '行政組織法',
                    'x' => 200,
                    'y' => 150,
                    'level' => 1,
                    'color' => '#303f9f',
                    'icon' => '🏛️',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '国・地方の行政機関の組織や権限について'
                ),
                array(
                    'id' => 'administrative_action',
                    'text' => '行政作用法',
                    'x' => 600,
                    'y' => 150,
                    'level' => 1,
                    'color' => '#303f9f',
                    'icon' => '⚡',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '行政機関が行う具体的な活動について'
                ),
                array(
                    'id' => 'administrative_procedure',
                    'text' => '行政手続法',
                    'x' => 400,
                    'y' => 100,
                    'level' => 1,
                    'color' => '#303f9f',
                    'icon' => '📋',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '行政処分や行政指導の手続について'
                ),
                array(
                    'id' => 'administrative_remedy',
                    'text' => '行政救済法',
                    'x' => 400,
                    'y' => 400,
                    'level' => 1,
                    'color' => '#303f9f',
                    'icon' => '🛡️',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '行政活動によって損害を受けた場合の救済手段'
                ),
                
                // レベル2: 詳細項目
                array(
                    'id' => 'national_administration',
                    'text' => '国の行政組織',
                    'x' => 100,
                    'y' => 100,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '🏢',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '内閣、各省庁の組織構造'
                ),
                array(
                    'id' => 'local_administration',
                    'text' => '地方の行政組織',
                    'x' => 100,
                    'y' => 200,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '🏛️',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '都道府県、市町村の組織'
                ),
                array(
                    'id' => 'administrative_act',
                    'text' => '行政行為',
                    'x' => 700,
                    'y' => 100,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '📝',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '許可、認可、取消し等の行政処分'
                ),
                array(
                    'id' => 'administrative_guidance',
                    'text' => '行政指導',
                    'x' => 700,
                    'y' => 200,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '👉',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '行政機関が行う任意の指導や要請'
                ),
                array(
                    'id' => 'administrative_litigation',
                    'text' => '行政事件訴訟',
                    'x' => 300,
                    'y' => 450,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '⚖️',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '取消訴訟、無効確認訴訟など'
                ),
                array(
                    'id' => 'administrative_review',
                    'text' => '行政不服審査',
                    'x' => 500,
                    'y' => 450,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '📄',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '審査請求、再調査の請求'
                )
            ),
            'connections' => array(
                // 中心から主要分野への接続
                array('from' => 'gyosei_root', 'to' => 'administrative_organization'),
                array('from' => 'gyosei_root', 'to' => 'administrative_action'),
                array('from' => 'gyosei_root', 'to' => 'administrative_procedure'),
                array('from' => 'gyosei_root', 'to' => 'administrative_remedy'),
                
                // 行政組織法の詳細
                array('from' => 'administrative_organization', 'to' => 'national_administration'),
                array('from' => 'administrative_organization', 'to' => 'local_administration'),
                
                // 行政作用法の詳細
                array('from' => 'administrative_action', 'to' => 'administrative_act'),
                array('from' => 'administrative_action', 'to' => 'administrative_guidance'),
                
                // 行政救済法の詳細
                array('from' => 'administrative_remedy', 'to' => 'administrative_litigation'),
                array('from' => 'administrative_remedy', 'to' => 'administrative_review')
            )
        );
    }
    
    /**
     * 民法サンプルデータ
     */
    public static function get_minpo_data() {
        return array(
            'title' => '民法体系マップ',
            'description' => '民法の全体構造を理解するためのマインドマップ',
            'nodes' => array(
                array(
                    'id' => 'minpo_root',
                    'text' => '民法',
                    'x' => 400,
                    'y' => 250,
                    'level' => 0,
                    'color' => '#e91e63',
                    'icon' => '📜',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '私人間の権利義務関係を規律する基本法'
                ),
                array(
                    'id' => 'general_provisions',
                    'text' => '総則',
                    'x' => 200,
                    'y' => 150,
                    'level' => 1,
                    'color' => '#c2185b',
                    'icon' => '📖',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '民法全体に通用する基本原則'
                ),
                array(
                    'id' => 'real_rights',
                    'text' => '物権',
                    'x' => 600,
                    'y' => 150,
                    'level' => 1,
                    'color' => '#c2185b',
                    'icon' => '🏠',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '物に対する直接的支配権'
                ),
                array(
                    'id' => 'obligations',
                    'text' => '債権',
                    'x' => 400,
                    'y' => 100,
                    'level' => 1,
                    'color' => '#c2185b',
                    'icon' => '💰',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '特定人に対する給付請求権'
                ),
                array(
                    'id' => 'family',
                    'text' => '親族',
                    'x' => 300,
                    'y' => 350,
                    'level' => 1,
                    'color' => '#c2185b',
                    'icon' => '👨‍👩‍👧‍👦',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '婚姻、親子関係等の身分関係'
                ),
                array(
                    'id' => 'succession',
                    'text' => '相続',
                    'x' => 500,
                    'y' => 350,
                    'level' => 1,
                    'color' => '#c2185b',
                    'icon' => '🎭',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '死亡による財産承継'
                )
            ),
            'connections' => array(
                array('from' => 'minpo_root', 'to' => 'general_provisions'),
                array('from' => 'minpo_root', 'to' => 'real_rights'),
                array('from' => 'minpo_root', 'to' => 'obligations'),
                array('from' => 'minpo_root', 'to' => 'family'),
                array('from' => 'minpo_root', 'to' => 'succession')
            )
        );
    }
    
    /**
     * 憲法サンプルデータ
     */
    public static function get_kenpou_data() {
        return array(
            'title' => '憲法体系マップ',
            'description' => '日本国憲法の構造理解のためのマインドマップ',
            'nodes' => array(
                array(
                    'id' => 'kenpou_root',
                    'text' => '日本国憲法',
                    'x' => 400,
                    'y' => 250,
                    'level' => 0,
                    'color' => '#ff9800',
                    'icon' => '🇯🇵',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '国の最高法規'
                ),
                array(
                    'id' => 'basic_principles',
                    'text' => '基本原理',
                    'x' => 400,
                    'y' => 150,
                    'level' => 1,
                    'color' => '#f57c00',
                    'icon' => '⭐',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '国民主権、基本的人権、平和主義'
                ),
                array(
                    'id' => 'fundamental_rights',
                    'text' => '基本的人権',
                    'x' => 250,
                    'y' => 300,
                    'level' => 1,
                    'color' => '#f57c00',
                    'icon' => '🤝',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '個人の尊厳に基づく権利'
                ),
                array(
                    'id' => 'government_structure',
                    'text' => '統治機構',
                    'x' => 550,
                    'y' => 300,
                    'level' => 1,
                    'color' => '#f57c00',
                    'icon' => '🏛️',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '国会、内閣、裁判所'
                )
            ),
            'connections' => array(
                array('from' => 'kenpou_root', 'to' => 'basic_principles'),
                array('from' => 'kenpou_root', 'to' => 'fundamental_rights'),
                array('from' => 'kenpou_root', 'to' => 'government_structure')
            )
        );
    }
    
    /**
     * 商法・会社法サンプルデータ
     */
    public static function get_shoken_data() {
        return array(
            'title' => '商法・会社法体系マップ',
            'description' => '商法と会社法の基本構造',
            'nodes' => array(
                array(
                    'id' => 'shoken_root',
                    'text' => '商法・会社法',
                    'x' => 400,
                    'y' => 250,
                    'level' => 0,
                    'color' => '#4caf50',
                    'icon' => '🏢',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '企業活動に関する法律'
                ),
                array(
                    'id' => 'commercial_law',
                    'text' => '商法総則',
                    'x' => 300,
                    'y' => 180,
                    'level' => 1,
                    'color' => '#388e3c',
                    'icon' => '📊',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '商人・商行為の一般規定'
                ),
                array(
                    'id' => 'company_law',
                    'text' => '会社法',
                    'x' => 500,
                    'y' => 180,
                    'level' => 1,
                    'color' => '#388e3c',
                    'icon' => '🏭',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '会社の設立・運営・解散'
                )
            ),
            'connections' => array(
                array('from' => 'shoken_root', 'to' => 'commercial_law'),
                array('from' => 'shoken_root', 'to' => 'company_law')
            )
        );
    }
    
    /**
     * 一般知識サンプルデータ
     */
    public static function get_general_data() {
        return array(
            'title' => '一般知識体系マップ',
            'description' => '行政書士試験の一般知識分野',
            'nodes' => array(
                array(
                    'id' => 'general_root',
                    'text' => '一般知識',
                    'x' => 400,
                    'y' => 250,
                    'level' => 0,
                    'color' => '#9c27b0',
                    'icon' => '🧠',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '政治・経済・社会・情報・文章理解'
                ),
                array(
                    'id' => 'politics',
                    'text' => '政治',
                    'x' => 250,
                    'y' => 180,
                    'level' => 1,
                    'color' => '#7b1fa2',
                    'icon' => '🗳️',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '政治制度・選挙制度'
                ),
                array(
                    'id' => 'economics',
                    'text' => '経済',
                    'x' => 550,
                    'y' => 180,
                    'level' => 1,
                    'color' => '#7b1fa2',
                    'icon' => '💹',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '経済理論・財政・金融'
                ),
                array(
                    'id' => 'information',
                    'text' => '情報通信',
                    'x' => 300,
                    'y' => 320,
                    'level' => 1,
                    'color' => '#7b1fa2',
                    'icon' => '💻',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => 'IT・情報セキュリティ'
                ),
                array(
                    'id' => 'literature',
                    'text' => '文章理解',
                    'x' => 500,
                    'y' => 320,
                    'level' => 1,
                    'color' => '#7b1fa2',
                    'icon' => '📚',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '現代文・古文'
                )
            ),
            'connections' => array(
                array('from' => 'general_root', 'to' => 'politics'),
                array('from' => 'general_root', 'to' => 'economics'),
                array('from' => 'general_root', 'to' => 'information'),
                array('from' => 'general_root', 'to' => 'literature')
            )
        );
    }
}