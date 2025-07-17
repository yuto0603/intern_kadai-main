<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <?php echo Asset::css('style.css'); ?>
    <style>
        /* フラッシュメッセージのスタイル */
        .flash-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
            color: #fff;
        }
        .flash-message.success {
            background-color: #4CAF50; /* 緑 */
        }
        .flash-message.error {
            background-color: #f44336; /* 赤 */
        }
    </style>
</head>
<body>


    <div class="container">
        <h1 class="header-title">備品貸出管理</h1>

        <div class="tab-nav">
            <a href="<?php echo Uri::base(); ?>box" class="tab-nav-item">備品一覧</a>
            <a href="#" class="tab-nav-item active">備品管理</a>
        </div>

        <h2 class="section-title">備品の貸出</h2>

        <?php if (isset($flash_message_error) && !empty($flash_message_error)): ?>
            <div class="flash-message error">
                <?php echo htmlspecialchars($flash_message_error); ?>
            </div>
        <?php endif; ?>

        <p class="item-info-text"><?php echo htmlspecialchars($item_label); ?> (モニター)</p>

        <div class="form-section">
            <form action="<?php echo Uri::base(); ?>box/loan/<?php echo $item_id; ?>" method="post">
                <?php echo \Form::csrf(); // CSRFトークンの隠しフィールドを生成 ?>
                <label for="userName" class="form-label">名前(Name):</label>
                <input type="text" id="userName" name="user_name" class="form-input" placeholder="名前を入力してください(Please enter your name)" required>
                
                <button type="submit" class="action-button loan">貸し出す</button>
            </form>
        </div>

    </div>

</body>
</html>