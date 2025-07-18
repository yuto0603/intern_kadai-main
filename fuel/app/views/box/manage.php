<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <?php echo Asset::css('style.css'); ?>
    <?php echo Asset::css('flash.css'); ?>
    <?php echo Asset::css('manage_style.css'); ?>
   
</head>
<body>

    <div class="container">
        <h1 class="header-title">備品貸出管理</h1>

        <div class="tab-nav">
            <a href="<?php echo Uri::base(); ?>box" class="tab-nav-item">備品一覧</a>
            <a href="<?php echo Uri::base(); ?>box/manage" class="tab-nav-item active">備品管理</a>
        </div>

        <h2 class="section-title"><?php echo $title; ?></h2>

        <?php if (isset($flash_message_success) && !empty($flash_message_success)): ?>
            <div class="flash-message success">
                <?php echo htmlspecialchars($flash_message_success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($flash_message_error) && !empty($flash_message_error)): ?>
            <div class="flash-message error">
                <?php echo htmlspecialchars($flash_message_error); ?>
            </div>
        <?php endif; ?>

        <a href="<?php echo Uri::base(); ?>box/create" class="add-item-link">新しい備品を追加</a>

        <?php if (empty($items_by_type)): ?>
            <p>現在、登録されている備品はありません。</p>
        <?php else: ?>
            <?php foreach ($items_by_type as $type => $items): ?>
                <h3 class="section-subtitle"><?php echo htmlspecialchars($type); ?></h3>
                <table class="item-list-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ラベル</th>
                            <th>種類</th>
                            <th>アクション</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['box_id']); ?></td>
                                <td><?php echo htmlspecialchars($item['label']); ?></td>
                                <td><?php echo htmlspecialchars($item['type']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo Uri::base(); ?>box/edit/<?php echo $item['box_id']; ?>" class="edit-btn">編集</a>
                                        <form action="<?php echo Uri::base(); ?>box/delete/<?php echo $item['box_id']; ?>" method="post" style="display:inline;" onsubmit="return confirm('本当に備品「<?php echo htmlspecialchars($item['label']); ?>」を削除しますか？');">
                                            <?php echo \Form::csrf(); ?>
                                            <button type="submit" class="delete-btn">削除</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

</body>
</html>