<?php
/**
 * 管理画面 - 使い方タブ
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<h3><?php _e('学習進捗管理システムの使い方', 'study-progress-tracker'); ?></h3>

<div class="usage-container">
    <div class="usage-section">
        <h4><?php _e('ショートコードの使用方法', 'study-progress-tracker'); ?></h4>
        <div class="shortcode-usage">
            <p><?php _e('進捗表示ショートコード:', 'study-progress-tracker'); ?> <code>[study_progress]</code></p>
            <p><?php _e('特定の科目のみ表示:', 'study-progress-tracker'); ?> <code>[study_progress subject="constitutional,civil"]</code></p>
            <p><?php _e('スタイル指定:', 'study-progress-tracker'); ?> <code>[study_progress style="simple"]</code> <?php _e('(スタイル: default, simple, card, minimal)', 'study-progress-tracker'); ?></p>
            <p><?php _e('試験カウントダウン:', 'study-progress-tracker'); ?> <code>[exam_countdown]</code></p>
            <p><?php _e('カスタム試験名:', 'study-progress-tracker'); ?> <code>[exam_countdown title="司法試験"]</code></p>
        </div>
    </div>
    
    <div class="usage-section">
        <h4><?php _e('進捗管理の使い方', 'study-progress-tracker'); ?></h4>
        <ol>
            <li><strong><?php _e('科目管理', 'study-progress-tracker'); ?></strong>: <?php _e('「科目管理」タブで試験科目を追加・編集・削除できます。', 'study-progress-tracker'); ?></li>
            <li><strong><?php _e('科目構造設定', 'study-progress-tracker'); ?></strong>: <?php _e('「科目構造設定」タブで各科目の章・節・項を設定できます。', 'study-progress-tracker'); ?></li>
            <li><strong><?php _e('進捗管理', 'study-progress-tracker'); ?></strong>: <?php _e('「進捗管理」タブで学習の進み具合を記録します。', 'study-progress-tracker'); ?>
                <ul>
                    <li><?php _e('理解した内容には「理解」チェックボックスにチェック', 'study-progress-tracker'); ?></li>
                    <li><?php _e('完全に習得した内容には「習得」チェックボックスにチェック', 'study-progress-tracker'); ?></li>
                    <li><?php _e('章をクリックすると展開/折りたたみができます', 'study-progress-tracker'); ?></li>
                    <li><?php _e('「リセット」ボタンで進捗を初期化できます', 'study-progress-tracker'); ?></li>
                </ul>
            </li>
        </ol>
    </div>
    
    <div class="usage-section">
        <h4><?php _e('学習進捗の2段階チェックについて', 'study-progress-tracker'); ?></h4>
        <p><?php _e('このシステムでは、学習進捗を2段階で管理できます：', 'study-progress-tracker'); ?></p>
        <ol>
            <li><strong><?php _e('理解', 'study-progress-tracker'); ?></strong>: <?php _e('内容を理解し、基本的な知識を得た状態', 'study-progress-tracker'); ?></li>
            <li><strong><?php _e('習得', 'study-progress-tracker'); ?></strong>: <?php _e('完全に暗記・習得し、いつでも使える状態', 'study-progress-tracker'); ?></li>
        </ol>
        <p><?php _e('「習得」にチェックを入れると、「理解」も自動的にチェックされます。', 'study-progress-tracker'); ?></p>
    </div>
    
    <div class="usage-section">
        <h4><?php _e('ウィジェットの活用', 'study-progress-tracker'); ?></h4>
        <p><?php _e('「学習進捗状況」ウィジェットをサイドバーに追加すれば、常に進捗状況を確認できます。', 'study-progress-tracker'); ?></p>
        <p><?php _e('ウィジェットは「外観」→「ウィジェット」から設定できます。', 'study-progress-tracker'); ?></p>
    </div>
    
    <div class="usage-section">
        <h4><?php _e('ショートコードのパラメータ詳細', 'study-progress-tracker'); ?></h4>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('パラメータ', 'study-progress-tracker'); ?></th>
                    <th><?php _e('説明', 'study-progress-tracker'); ?></th>
                    <th><?php _e('値', 'study-progress-tracker'); ?></th>
                    <th><?php _e('デフォルト', 'study-progress-tracker'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>subject</code></td>
                    <td><?php _e('表示する科目をカンマ区切りで指定', 'study-progress-tracker'); ?></td>
                    <td><?php _e('科目キー（例: constitutional,civil）', 'study-progress-tracker'); ?></td>
                    <td><?php _e('全科目', 'study-progress-tracker'); ?></td>
                </tr>
                <tr>
                    <td><code>interactive</code></td>
                    <td><?php _e('インタラクティブモード（ログインユーザーのみ）', 'study-progress-tracker'); ?></td>
                    <td>yes / no</td>
                    <td>yes</td>
                </tr>
                <tr>
                    <td><code>style</code></td>
                    <td><?php _e('表示スタイル', 'study-progress-tracker'); ?></td>
                    <td>default / simple / card / minimal</td>
                    <td>default</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="usage-section">
        <h4><?php _e('トラブルシューティング', 'study-progress-tracker'); ?></h4>
        <ul>
            <li><strong><?php _e('チェックボックスが保存されない場合', 'study-progress-tracker'); ?></strong>
                <ul>
                    <li><?php _e('ブラウザのJavaScriptが有効になっているか確認', 'study-progress-tracker'); ?></li>
                    <li><?php _e('コンソールでエラーが出ていないか確認', 'study-progress-tracker'); ?></li>
                    <li><?php _e('プラグインの競合がないか確認', 'study-progress-tracker'); ?></li>
                </ul>
            </li>
            <li><strong><?php _e('進捗率が正しく表示されない場合', 'study-progress-tracker'); ?></strong>
                <ul>
                    <li><?php _e('科目構造設定で章・節・項が正しく設定されているか確認', 'study-progress-tracker'); ?></li>
                    <li><?php _e('ブラウザのキャッシュをクリア', 'study-progress-tracker'); ?></li>
                </ul>
            </li>
        </ul>
    </div>
</div>