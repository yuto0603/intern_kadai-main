<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <?php echo Asset::css('style.css'); ?>
    <?php echo Asset::css('flash.css'); ?>
</head>
<body>

    <div class="container">
        <h1 class="header-title">備品貸出管理</h1>

        <div class="tab-nav">
            <a href="<?php echo Uri::base(); ?>box" class="tab-nav-item">備品一覧</a>
            <a href="<?php echo Uri::base(); ?>box/manage" class="tab-nav-item">備品管理</a>
        </div>

        <h2 class="section-title">備品の編集</h2>

        <?php if (isset($flash_message_error) && !empty($flash_message_error)): ?>
            <div class="flash-message error">
                <?php echo htmlspecialchars($flash_message_error); ?>
            </div>
        <?php endif; ?>

        <p class="item-info-text">備品ID: <?php echo htmlspecialchars($item_id); ?></p>

        <div class="form-section">
            <form action="<?php echo Uri::base(); ?>box/edit/<?php echo $item_id; ?>" method="post">
                <?php echo \Form::csrf(); // CSRFトークンの隠しフィールドを生成 ?>
                <label for="label" class="form-label">備品ラベル:</label>
                <input type="text" id="label" name="label" class="form-input" value="<?php echo htmlspecialchars($item_label); ?>" required>
                
                <button type="submit" class="action-button primary">更新する</button>
            </form>
        </div>

        <p class="back-link"><a href="<?php echo Uri::base(); ?>box/manage">備品管理に戻る</a></p>

    </div>

</body>
</html>