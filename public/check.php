<?php

// ★★★ データベース接続情報 ★★★
// fuel/app/config/db.php の情報をここにコピーして貼り付けてください
$hostname = 'db'; // Dockerの場合、dbサービス名であることが多いですが、localhostも試します。
                         // もしdocker-compose.ymlのdbサービスのportsに"3306:3306"があればlocalhostでOKです。
                         // もしdbサービス名が 'db' なら、hostname を 'db' に変更してみてください。
$database = 'equipment_db'; // あなたのデータベース名
$username = 'root';         // あなたのデータベースユーザー名
$password = 'root';         // あなたのデータベースパスワード（rootユーザーの場合）
$port = '3306';             // あなたのデータベースポート

// DSN (Data Source Name)
$dsn = "mysql:host={$hostname};port={$port};dbname={$database};charset=utf8";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // エラーモードを例外に設定
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // フェッチモードを連想配列に設定
    PDO::ATTR_EMULATE_PREPARES   => false,                  // プリペアドステートメントのエミュレーションを無効に
];

$boxes = []; // データを格納する配列

try {
    // データベースに接続
    $pdo = new PDO($dsn, $username, $password, $options);

    // SQLクエリの実行
    $stmt = $pdo->query("SELECT box_id, label FROM boxes ORDER BY box_id ASC");

    // 結果を全て取得
    $boxes = $stmt->fetchAll();

} catch (\PDOException $e) {
    // データベース接続またはクエリ実行エラーが発生した場合
    // エラーメッセージをブラウザに表示
    echo "<h1>データベース接続またはクエリ実行エラーが発生しました！</h1>";
    echo "<p>エラーコード: " . $e->getCode() . "</p>";
    echo "<p>エラーメッセージ: " . $e->getMessage() . "</p>";
    echo "<p>これは、データベースの設定が間違っているか、データベースサーバーが起動していない可能性が高いです。</p>";
    exit; // スクリプトの実行を停止
}

// ★★★ 取得したデータを表示するHTML ★★★
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>データ取得チェック</title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f0f2f5; }
        h1 { color: #3498db; }
        table { width: 80%; border-collapse: collapse; margin-top: 20px; background-color: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #3498db; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>ボックスデータチェック</h1>

    <?php if (empty($boxes)): ?>
        <p>ボックスデータがありません。</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Box ID</th>
                    <th>Label</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($boxes as $box): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($box['box_id']); ?></td>
                        <td><?php echo htmlspecialchars($box['label']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php
    // 取得した生のデータもダンプして確認
    echo "<h2>取得した生データ:</h2>";
    echo "<pre>";
    var_dump($boxes);
    echo "</pre>";
    ?>

</body>
</html>