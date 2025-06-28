<h1>モニター一覧</h1>
<p>これはdbチェック用です</p>

<?php if (!empty($monitors)): ?>
    <h2>取得されたモニターデータ:</h2>
    <ul>
    <?php foreach ($monitors as $monitor): ?>
        <li>
            ID: <?php echo $monitor->id; ?>,
            ラベル: <?php echo $monitor->label; ?>,
            状態: <?php echo $monitor->status; ?>,
            現在の使用者: <?php echo $monitor->current_user; ?>,
            最終更新日時: <?php echo $monitor->updated_at; ?>
        </li>
    <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>データベースからモニターデータが取得できませんでした。</p>
<?php endif; ?>