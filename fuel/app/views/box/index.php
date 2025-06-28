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
            <a href="<?php echo Uri::base(); ?>box/manage" class="tab-nav-item">備品管理</a> 
        </div>

        <h2 class="section-title">現在の備品状況</h2>
        <h3 class="section-title">モニター</h3>

        <?php if (empty($boxes)): ?>
            <p>現在、登録されているボックスデータはありません。</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($boxes as $box): ?>
                    <?php
    
                        $is_loaned = ($box['box_id'] == 1);
                        $card_class = $is_loaned ? 'loaned' : 'available';
                        $status_text = $is_loaned ? '貸出中' : '貸出可能';
                        $loaned_by_name = $is_loaned ? '(test)' : '';
                        
                        
                        $card_link_url = $is_loaned ? 'return/' . $box['box_id'] : 'loan/' . $box['box_id'];
                    ?>
                    <a href="<?php echo Uri::base() . 'box/' . $card_link_url; ?>" class="item-card <?php echo $card_class; ?>">
                        <div class="item-name"><?php echo $box['label']; ?></div>
                        <div class="item-type">モニター</div>
                        <div class="item-status"><?php echo $status_text; ?></div>
                        <?php if ($is_loaned): ?>
                            <div class="loaned-by"><?php echo $loaned_by_name; ?></div>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div> </body>
</html>