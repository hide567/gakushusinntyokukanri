<?php
/**
 * 行政書士の道 - マインドマップサンプルデータ
 * サンプルデータの定義と管理
 */

if (!defined('ABSPATH')) {
    exit;
}

class GyoseiMindMapSampleData {
    
    public static function get_all_data() {
        return array(
            'gyosei' => self::get_gyosei_data(),
            'minpo' => self::get_minpo_data(),
            'kenpou' => self::get_kenpou_data(),
            'shoken' => self::get_shoken_data()
        );
    }
    
    public static function get_gyosei_data() {
        return array(
            'title' => '行政法',
            'description' => '行政に関する法律の総称',
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
                    'description' => '行政に関する法律の総称。国民の権利保護と行政の適正な運営を図る。',
                    'resources' => array(
                        array('title' => '行政法入門', 'url' => '#', 'type' => '教科書'),
                        array('title' => '行政法判例集', 'url' => '#', 'type' => '判例集')
                    )
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
                    'parent' => 'root',
                    'resources' => array(
                        array('title' => '行政行為の基礎理論', 'url' => '#', 'type' => '論文'),
                        array('title' => '行政裁量の判例分析', 'url' => '#', 'type' => '判例解説')
                    )
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
                    'parent' => 'root',
                    'resources' => array(
                        array('title' => '行政手続法逐条解説', 'url' => '#', 'type' => '逐条解説'),
                        array('title' => '申請手続きの実務', 'url' => '#', 'type' => '実務書')
                    )
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
        );
    }
    
    public static function get_minpo_data() {
        return array(
            'title' => '民法',
            'description' => '私人間の法律関係を規律する私法の一般法',
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
        );
    }
    
    public static function get_kenpou_data() {
        return array(
            'title' => '憲法',
            'description' => '国家の基本法',
            'nodes' => array(
                array(
                    'id' => 'root',
                    'text' => '憲法',
                    'x' => 400,
                    'y' => 200,
                    'level' => 0,
                    'color' => '#4caf50',
                    'icon' => '📜',
                    'progress' => 55,
                    'status' => 'in-progress',
                    'description' => '国家の基本法。国民の基本的人権の保障と国家権力の組織・作用を定める。'
                ),
                array(
                    'id' => 'human_rights',
                    'text' => '基本的人権',
                    'x' => 200,
                    'y' => 100,
                    'level' => 1,
                    'color' => '#388e3c',
                    'icon' => '👥',
                    'progress' => 70,
                    'status' => 'in-progress',
                    'description' => '個人の尊厳に基づく基本的権利。自由権、社会権、参政権、受益権に分類。',
                    'parent' => 'root'
                ),
                array(
                    'id' => 'state_power',
                    'text' => '統治機構',
                    'x' => 600,
                    'y' => 100,
                    'level' => 1,
                    'color' => '#388e3c',
                    'icon' => '🏛️',
                    'progress' => 40,
                    'status' => 'in-progress',
                    'description' => '国家権力の組織と作用。立法、行政、司法の三権分立制度。',
                    'parent' => 'root'
                ),
                array(
                    'id' => 'peace',
                    'text' => '平和主義',
                    'x' => 300,
                    'y' => 300,
                    'level' => 1,
                    'color' => '#388e3c',
                    'icon' => '🕊️',
                    'progress' => 60,
                    'status' => 'in-progress',
                    'description' => '戦争放棄と戦力不保持を定めた憲法第9条の理念。',
                    'parent' => 'root'
                ),
                array(
                    'id' => 'rule_of_law',
                    'text' => '法の支配',
                    'x' => 500,
                    'y' => 300,
                    'level' => 1,
                    'color' => '#388e3c',
                    'icon' => '⚖️',
                    'progress' => 50,
                    'status' => 'in-progress',
                    'description' => '権力の恣意的行使を法によって制限する原理。立憲主義の基礎。',
                    'parent' => 'root'
                )
            ),
            'connections' => array(
                array('from' => 'root', 'to' => 'human_rights'),
                array('from' => 'root', 'to' => 'state_power'),
                array('from' => 'root', 'to' => 'peace'),
                array('from' => 'root', 'to' => 'rule_of_law')
            )
        );
    }
    
    public static function get_shoken_data() {
        return array(
            'title' => '商法・会社法',
            'description' => '商取引と会社に関する法律',
            'nodes' => array(
                array(
                    'id' => 'root',
                    'text' => '商法・会社法',
                    'x' => 400,
                    'y' => 200,
                    'level' => 0,
                    'color' => '#ff9800',
                    'icon' => '🏢',
                    'progress' => 35,
                    'status' => 'in-progress',
                    'description' => '商取引と会社組織に関する法律の総称。'
                ),
                array(
                    'id' => 'company',
                    'text' => '会社法',
                    'x' => 250,
                    'y' => 100,
                    'level' => 1,
                    'color' => '#f57c00',
                    'icon' => '🏭',
                    'progress' => 40,
                    'status' => 'in-progress',
                    'description' => '会社の設立、組織、運営、解散に関する法律。',
                    'parent' => 'root'
                ),
                array(
                    'id' => 'commercial',
                    'text' => '商法総則・商行為',
                    'x' => 550,
                    'y' => 100,
                    'level' => 1,
                    'color' => '#f57c00',
                    'icon' => '💱',
                    'progress' => 30,
                    'status' => 'not-started',
                    'description' => '商人、商行為、商業登記に関する規定。',
                    'parent' => 'root'
                )
            ),
            'connections' => array(
                array('from' => 'root', 'to' => 'company'),
                array('from' => 'root', 'to' => 'commercial')
            )
        );
    }
}