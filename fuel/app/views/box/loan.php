<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <?php echo Asset::css('style.css'); ?>
</head>
<body>

    <div class="lang-switcher">
        <a href="#" class="lang-btn active">日本語</a>
        <a href="#" class="lang-btn">English</a>
    </div>

    <div class="container">
        <h1 class="header-title">備品貸出管理</h1>

        <div class="tab-nav">
            <a href="<?php echo Uri::base(); ?>box" class="tab-nav-item active">備品一覧</a>
            <a href="#" class="tab-nav-item">備品管理</a>
        </div>

        <h2 class="section-title">備品の貸出</h2>

        <p class="item-info-text"><?php echo htmlspecialchars($item_label); ?> (モニター)</p>

        <div class="form-section">
            <form action="#" method="post">
                <label for="userName" class="form-label">あなたの名前:</label>
                <input type="text" id="userName" name="user_name" class="form-input" placeholder="名前を入力してください">
                
                <button type="submit" class="action-button loan">貸し出す</button>
            </form>
            </div>

    </div>

</body>
</html>