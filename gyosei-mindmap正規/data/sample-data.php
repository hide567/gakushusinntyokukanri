<?php
/**
 * 行政書士の道 - マインドマップ サンプルデータ (修正版)
 * File: data/sample-data.php
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
     * 行政法サンプルデータ (完全版)
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
                    'description' => '国家行政組織や行政作用に関する法律の総称。公権力の行使に関する基本的な法制度を規律する。'
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
                    'description' => '国・地方の行政機関の組織や権限について定める法分野。内閣法、国家行政組織法、地方自治法等が含まれる。'
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
                    'description' => '行政機関が行う具体的な活動について定める法分野。行政行為、行政指導、行政契約等を含む。'
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
                    'description' => '行政処分や行政指導の手続について定める法律。適正手続の保障と行政運営の公正・透明性確保を目的とする。'
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
                    'description' => '行政活動によって損害を受けた場合の救済手段を定める法分野。行政事件訴訟、行政不服審査、国家賠償等を含む。'
                ),
                
                // レベル2: 詳細項目 - 行政組織法
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
                    'description' => '内閣、各省庁の組織構造。内閣法、国家行政組織法による規律。'
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
                    'description' => '都道府県、市町村の組織。地方自治法による規律。'
                ),
                array(
                    'id' => 'independent_agencies',
                    'text' => '独立行政法人',
                    'x' => 300,
                    'y' => 120,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '🏭',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '国の事務事業を効率的に実施するための法人。独立行政法人通則法による規律。'
                ),
                
                // レベル2: 詳細項目 - 行政作用法
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
                    'description' => '許可、認可、取消し等の行政処分。法律に基づく公権力の行使として行われる。'
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
                    'description' => '行政機関が行う任意の指導や要請。法的強制力はないが実質的影響力を持つ。'
                ),
                array(
                    'id' => 'administrative_contract',
                    'text' => '行政契約',
                    'x' => 500,
                    'y' => 120,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '📄',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '行政機関が私人と対等な立場で締結する契約。公共事業契約等が典型例。'
                ),
                
                // レベル2: 詳細項目 - 行政救済法
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
                    'description' => '取消訴訟、無効確認訴訟、義務付け訴訟等。行政事件訴訟法による規律。'
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
                    'description' => '審査請求、再調査の請求。行政不服審査法による簡易迅速な救済制度。'
                ),
                array(
                    'id' => 'state_compensation',
                    'text' => '国家賠償',
                    'x' => 200,
                    'y' => 380,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '💰',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '公権力行使による損害の賠償。国家賠償法による金銭的救済。'
                ),
                
                // レベル3: さらに詳細な項目
                array(
                    'id' => 'cabinet_system',
                    'text' => '内閣制度',
                    'x' => 50,
                    'y' => 50,
                    'level' => 3,
                    'color' => '#e91e63',
                    'icon' => '👥',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '内閣総理大臣と国務大臣で構成。行政権の最高機関。'
                ),
                array(
                    'id' => 'ministry_system',
                    'text' => '省庁制度',
                    'x' => 150,
                    'y' => 50,
                    'level' => 3,
                    'color' => '#e91e63',
                    'icon' => '🏢',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '各省の組織と所掌事務。府省庁再編による現行制度。'
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
                array('from' => 'administrative_organization', 'to' => 'independent_agencies'),
                
                // 行政作用法の詳細
                array('from' => 'administrative_action', 'to' => 'administrative_act'),
                array('from' => 'administrative_action', 'to' => 'administrative_guidance'),
                array('from' => 'administrative_action', 'to' => 'administrative_contract'),
                
                // 行政救済法の詳細
                array('from' => 'administrative_remedy', 'to' => 'administrative_litigation'),
                array('from' => 'administrative_remedy', 'to' => 'administrative_review'),
                array('from' => 'administrative_remedy', 'to' => 'state_compensation'),
                
                // さらに詳細な接続
                array('from' => 'national_administration', 'to' => 'cabinet_system'),
                array('from' => 'national_administration', 'to' => 'ministry_system')
            )
        );
    }
    
    /**
     * 民法サンプルデータ (完全版)
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
                    'description' => '私人間の権利義務関係を規律する基本法。私法の一般法としての性格を持つ。'
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
                    'description' => '民法全体に通用する基本原則。権利能力、行為能力、法律行為等を規定。'
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
                    'description' => '物に対する直接的支配権。所有権、用益物権、担保物権に分類される。'
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
                    'description' => '特定人に対する給付請求権。契約、事務管理、不当利得、不法行為により発生。'
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
                    'description' => '婚姻、親子関係等の身分関係。家族法の基本的内容を規定。'
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
                    'description' => '死亡による財産承継。法定相続、遺言相続、遺留分等を規定。'
                ),
                
                // レベル2詳細項目
                array(
                    'id' => 'legal_capacity',
                    'text' => '権利能力',
                    'x' => 100,
                    'y' => 100,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '👤',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '権利義務の主体となる能力。自然人は出生により取得。'
                ),
                array(
                    'id' => 'legal_act',
                    'text' => '法律行為',
                    'x' => 300,
                    'y' => 100,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '📋',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '意思表示を要素とする法律事実。契約、単独行為等を含む。'
                ),
                array(
                    'id' => 'ownership',
                    'text' => '所有権',
                    'x' => 700,
                    'y' => 100,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '🏡',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '物に対する最も完全な支配権。使用、収益、処分権能を含む。'
                ),
                array(
                    'id' => 'contract',
                    'text' => '契約',
                    'x' => 300,
                    'y' => 50,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '🤝',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '当事者の合意により成立する法律行為。債権の主要な発生原因。'
                )
            ),
            'connections' => array(
                array('from' => 'minpo_root', 'to' => 'general_provisions'),
                array('from' => 'minpo_root', 'to' => 'real_rights'),
                array('from' => 'minpo_root', 'to' => 'obligations'),
                array('from' => 'minpo_root', 'to' => 'family'),
                array('from' => 'minpo_root', 'to' => 'succession'),
                array('from' => 'general_provisions', 'to' => 'legal_capacity'),
                array('from' => 'general_provisions', 'to' => 'legal_act'),
                array('from' => 'real_rights', 'to' => 'ownership'),
                array('from' => 'obligations', 'to' => 'contract')
            )
        );
    }
    
    /**
     * 憲法サンプルデータ (完全版)
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
                    'description' => '国の最高法規。国民主権、基本的人権の尊重、平和主義を基本原理とする。'
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
                    'description' => '国民主権、基本的人権の尊重、平和主義の三大原理。憲法の基本的性格を規定。'
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
                    'description' => '個人の尊厳に基づく権利。自由権、社会権、参政権、受益権に分類。'
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
                    'description' => '国会、内閣、裁判所による権力分立制。それぞれの権限と相互関係を規定。'
                ),
                
                // レベル2詳細項目
                array(
                    'id' => 'popular_sovereignty',
                    'text' => '国民主権',
                    'x' => 300,
                    'y' => 100,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '👥',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '国政の最終決定権が国民にあること。民主政治の基礎原理。'
                ),
                array(
                    'id' => 'pacifism',
                    'text' => '平和主義',
                    'x' => 500,
                    'y' => 100,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '🕊️',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '戦争放棄と戦力不保持を規定。憲法第9条により具体化。'
                ),
                array(
                    'id' => 'freedom_rights',
                    'text' => '自由権',
                    'x' => 150,
                    'y' => 350,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '🗽',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '国家からの自由。精神的自由、経済的自由、人身の自由を含む。'
                ),
                array(
                    'id' => 'social_rights',
                    'text' => '社会権',
                    'x' => 350,
                    'y' => 350,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '🏥',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '国家による自由。生存権、教育を受ける権利、労働権等を含む。'
                ),
                array(
                    'id' => 'diet',
                    'text' => '国会',
                    'x' => 450,
                    'y' => 380,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '🏛️',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '国権の最高機関。立法権を有し、衆議院と参議院で構成。'
                ),
                array(
                    'id' => 'cabinet',
                    'text' => '内閣',
                    'x' => 550,
                    'y' => 380,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '🏢',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '行政権の主体。内閣総理大臣と国務大臣で構成。'
                ),
                array(
                    'id' => 'judiciary',
                    'text' => '裁判所',
                    'x' => 650,
                    'y' => 350,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '⚖️',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '司法権の主体。違憲審査権を有し、最高裁判所を頂点とする。'
                )
            ),
            'connections' => array(
                array('from' => 'kenpou_root', 'to' => 'basic_principles'),
                array('from' => 'kenpou_root', 'to' => 'fundamental_rights'),
                array('from' => 'kenpou_root', 'to' => 'government_structure'),
                array('from' => 'basic_principles', 'to' => 'popular_sovereignty'),
                array('from' => 'basic_principles', 'to' => 'pacifism'),
                array('from' => 'fundamental_rights', 'to' => 'freedom_rights'),
                array('from' => 'fundamental_rights', 'to' => 'social_rights'),
                array('from' => 'government_structure', 'to' => 'diet'),
                array('from' => 'government_structure', 'to' => 'cabinet'),
                array('from' => 'government_structure', 'to' => 'judiciary')
            )
        );
    }
    
    /**
     * 商法・会社法サンプルデータ (完全版)
     */
    public static function get_shoken_data() {
        return array(
            'title' => '商法・会社法体系マップ',
            'description' => '商法と会社法の基本構造を理解するためのマインドマップ',
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
                    'description' => '企業活動に関する法律。商法総則・商行為法と会社法に大別される。'
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
                    'description' => '商人・商行為の一般規定。商業登記、商号等を規律。'
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
                    'description' => '会社の設立・運営・解散に関する法律。株式会社を中心とする。'
                ),
                array(
                    'id' => 'commercial_transactions',
                    'text' => '商行為',
                    'x' => 200,
                    'y' => 300,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '💼',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '商法により規律される取引行為。売買、運送、保険等を含む。'
                ),
                array(
                    'id' => 'stock_company',
                    'text' => '株式会社',
                    'x' => 600,
                    'y' => 300,
                    'level' => 2,
                    'color' => '#7b1fa2',
                    'icon' => '📈',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '最も重要な会社形態。株主、取締役、監査役等による機関構造。'
                )
            ),
            'connections' => array(
                array('from' => 'shoken_root', 'to' => 'commercial_law'),
                array('from' => 'shoken_root', 'to' => 'company_law'),
                array('from' => 'commercial_law', 'to' => 'commercial_transactions'),
                array('from' => 'company_law', 'to' => 'stock_company')
            )
        );
    }
    
    /**
     * 一般知識サンプルデータ (完全版)
     */
    public static function get_general_data() {
        return array(
            'title' => '一般知識体系マップ',
            'description' => '行政書士試験の一般知識分野を体系化したマインドマップ',
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
                    'description' => '政治・経済・社会・情報・文章理解の5分野から構成される試験科目。'
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
                    'description' => '政治制度・選挙制度・政党政治等。日本と外国の政治システムを含む。'
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
                    'description' => '経済理論・財政・金融・国際経済等。マクロ・ミクロ経済学の基礎。'
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
                    'description' => 'IT・情報セキュリティ・個人情報保護等。現代社会の情報化に関する知識。'
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
                    'description' => '現代文・古文の読解力。論理的思考力と読解力が問われる。'
                ),
                
                // レベル2詳細項目
                array(
                    'id' => 'electoral_system',
                    'text' => '選挙制度',
                    'x' => 150,
                    'y' => 120,
                    'level' => 2,
                    'color' => '#e91e63',
                    'icon' => '🗳️',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '小選挙区制・比例代表制等の選挙制度。有権者・被選挙権の要件。'
                ),
                array(
                    'id' => 'political_parties',
                    'text' => '政党政治',
                    'x' => 350,
                    'y' => 120,
                    'level' => 2,
                    'color' => '#e91e63',
                    'icon' => '🏛️',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '政党の役割と機能。議院内閣制における政党政治の意義。'
                ),
                array(
                    'id' => 'fiscal_policy',
                    'text' => '財政政策',
                    'x' => 650,
                    'y' => 120,
                    'level' => 2,
                    'color' => '#e91e63',
                    'icon' => '💰',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '予算・租税・国債等による経済政策。財政の機能と役割。'
                ),
                array(
                    'id' => 'monetary_policy',
                    'text' => '金融政策',
                    'x' => 450,
                    'y' => 120,
                    'level' => 2,
                    'color' => '#e91e63',
                    'icon' => '🏦',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '中央銀行による金利・通貨供給量の調整。金融システムの安定。'
                ),
                array(
                    'id' => 'information_security',
                    'text' => '情報セキュリティ',
                    'x' => 200,
                    'y' => 380,
                    'level' => 2,
                    'color' => '#e91e63',
                    'icon' => '🔒',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => 'サイバー攻撃対策・暗号化・認証技術等。情報システムの安全性確保。'
                ),
                array(
                    'id' => 'personal_information',
                    'text' => '個人情報保護',
                    'x' => 400,
                    'y' => 380,
                    'level' => 2,
                    'color' => '#e91e63',
                    'icon' => '🛡️',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '個人情報保護法・GDPR等の規制。プライバシー保護の重要性。'
                ),
                array(
                    'id' => 'modern_japanese',
                    'text' => '現代文',
                    'x' => 600,
                    'y' => 380,
                    'level' => 2,
                    'color' => '#e91e63',
                    'icon' => '📖',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '論説文・随筆・小説等の読解。内容把握・要旨把握が中心。'
                ),
                array(
                    'id' => 'classical_japanese',
                    'text' => '古文',
                    'x' => 400,
                    'y' => 450,
                    'level' => 2,
                    'color' => '#e91e63',
                    'icon' => '📜',
                    'progress' => 0,
                    'status' => 'not-started',
                    'description' => '古典文学の読解。文法知識と古典常識が必要。'
                )
            ),
            'connections' => array(
                array('from' => 'general_root', 'to' => 'politics'),
                array('from' => 'general_root', 'to' => 'economics'),
                array('from' => 'general_root', 'to' => 'information'),
                array('from' => 'general_root', 'to' => 'literature'),
                array('from' => 'politics', 'to' => 'electoral_system'),
                array('from' => 'politics', 'to' => 'political_parties'),
                array('from' => 'economics', 'to' => 'fiscal_policy'),
                array('from' => 'economics', 'to' => 'monetary_policy'),
                array('from' => 'information', 'to' => 'information_security'),
                array('from' => 'information', 'to' => 'personal_information'),
                array('from' => 'literature', 'to' => 'modern_japanese'),
                array('from' => 'literature', 'to' => 'classical_japanese')
            )
        );
    }
}