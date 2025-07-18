<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    
    <?php echo Asset::css('style.css'); ?>
    <?php echo Asset::css('flash.css'); ?>
    <?php echo Asset::css('card_style.css'); ?>
    
</head>
<body>

    <div class="container">
        <h1 class="header-title">備品貸出管理 </h1>

        <div class="tab-nav">
            <a href="<?php echo Uri::base(); ?>box" class="tab-nav-item active">備品一覧 (Equipment List)</a>
            <a href="<?php echo Uri::base(); ?>box/manage" class="tab-nav-item">備品管理 (Equipment Management)</a>
        </div>

        <h2 class="section-title">現在の備品状況(Equipment Status)</h2>
        <h3 class="section-title">モニター(Monitor)</h3>

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

        <?php if (empty($boxes)): ?>
            <p>現在、登録されているボックスデータはありません。</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($boxes as $box): ?>
                    <?php
                        // 貸出状況に基づいてカードのクラスと表示テキストを決定
                        $is_loaned = ($box['status'] === '貸出中');
                        $card_class = $is_loaned ? 'loaned' : 'available';
                        $status_text = $is_loaned ? '貸出中 Borrowed' : '貸出可能 (Available)';
                        
                        // 貸出中の場合は返却ページへ、貸出可能の場合は貸出ページへリンク
                        $card_link_url = $is_loaned ? 'return/' . $box['box_id'] : 'loan/' . $box['box_id'];
                    ?>
                    <a href="<?php echo Uri::base() . 'box/' . $card_link_url; ?>" class="item-card <?php echo $card_class; ?>">
                        <div class="item-name"><?php echo htmlspecialchars($box['label']); ?></div>
                        <div class="item-type">モニター</div>
                        <div class="item-status"><?php echo $status_text; ?></div>
                        <?php if ($is_loaned && $box['current_user_name']): // 貸出中の場合のみユーザー名を表示 ?>
                            <div class="loaned-user-info">
                                (<?php echo htmlspecialchars($box['current_user_name']); ?>)
                            </div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>