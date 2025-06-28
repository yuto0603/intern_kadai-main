<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>'備品管理システム'</title>
    <?php echo Asset::css('style.css'); ?> 
</head>
<body>

    <header>
        <h1>モニター管理システム</h1>
        <nav>
            <ul>
                <li><a href="<?php echo Uri::base(); ?>monitor">モニター一覧</a></li>
                <li><a href="<?php echo Uri::base(); ?>monitor/create">新規登録</a></li>
                <li><a href="<?php echo Uri::base(); ?>monitor/history">貸出履歴</a></li>                
            </ul>
        </nav>
    </header>

    <main>
        <div class="monitor-list-section">
            <h3>モニターリスト</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ラベル</th>
                        <th>状態</th>
                        <th>現在の使用者</th>
                        <th>最終更新日時</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                   
                    ?>
                </tbody>
            </table>
            <p><a href="<?php echo Uri::base(); ?>monitor/create">新しいモニターを登録する</a></p>
        </div>
        
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> モニター管理システム. All rights reserved.</p>
    </footer>

</body>
</html>