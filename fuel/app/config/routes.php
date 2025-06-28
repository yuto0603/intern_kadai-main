<?php
return array(
	'_root_'  => 'welcome/index',  // The default route
	'_404_'   => 'welcome/404',    // The main 404 route
	
	// ボックス関連のルート
    'box' => array('box/index'), // メインの備品一覧ページ
    'box/loan/:id' => array('box/loan/$id'), // 貸出フォームページ
    'box/return/:id' => array('box/return/$id'), // 返却フォームページ
    'box/manage' => array('box/manage'), // 備品管理ページ
);

