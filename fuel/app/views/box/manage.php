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
            <a href="<?php echo Uri::base(); ?>box" class="tab-nav-item">備品一覧</a>
            <a href="<?php echo Uri::base(); ?>box/manage" class="tab-nav-item active">備品管理</a>
        </div>

        <h2 class="section-title">備品の追加・編集・削除</h2>

        <div class="registration-form">
            <h3>新しい備品の追加</h3>
            <form action="#" method="post">
                <label for="newId" class="form-label">新しい備品ラベル (例: B-16):</label>
                <input type="text" id="newId" name="new_label" class="form-input" placeholder="例: B-16">
                
                <label for="newName" class="form-label">備品名 (例: モニター):</label>
                <input type="text" id="newName" name="item_type" class="form-input" placeholder="例: モニター" value="モニター"> 
                
                <button type="submit" class="action-button register">備品を登録</button>
            </form>
            </div>

        <div class="existing-items">
            <h2>既存備品の編集・削除</h2>

            <?php if (empty($items_by_type)): ?>
                <p>登録されている備品がありません。</p>
            <?php else: ?>
                <?php foreach ($items_by_type as $item_type => $items): ?>
                    <h3 class="item-group-title"><?php echo htmlspecialchars($item_type); ?></h3>
                    <?php foreach ($items as $item): ?>
                        <div class="edit-delete-card">
                            <div class="item-label-display"><?php echo htmlspecialchars($item['label']); ?>:</div>
                            
                            <form action="#" method="post" style="display: flex; flex-grow: 1; align-items: center;">
                                <input type="text" name="edited_label" class="form-input" value="<?php echo htmlspecialchars($item['label']); ?>">
                                <button type="submit" class="action-button edit">編集</button>
                            </form>
                            
                            <form action="#" method="post" onsubmit="return confirm('本当にこの備品を削除しますか？');">
                                <button type="submit" class="action-button delete">削除</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>