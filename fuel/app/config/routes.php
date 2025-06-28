<?php
return array(
	'_root_'  => 'welcome/index',  // The default route
	'_404_'   => 'welcome/404',    // The main 404 route
	
	// ボックス関連のルート
    'box' => array('box/index'), // メインの備品一覧ページ
    'box/loan/:id' => array('box/loan/$id'), // 貸出フォームページ (GET)
    'box/return/:id' => array('box/return/$id'), // 返却フォームページ (GET)
    'box/manage' => array('box/manage'), // 備品管理ページ (GET)

    'box/loan/:id' => array('box/loan_post/$id', 'name' => 'box_loan_post', 'verb' => 'POST'), // 貸出処理 (POST)
    'box/return/:id' => array('box/return_post/$id', 'name' => 'box_return_post', 'verb' => 'POST'), // 返却処理 (POST)
    'box/add' => array('box/add_post', 'name' => 'box_add_post', 'verb' => 'POST'), // 備品登録処理 (POST)
    'box/edit/:id' => array('box/edit_post/$id', 'name' => 'box_edit_post', 'verb' => 'POST'), // 備品編集処理 (POST)
    'box/delete/:id' => array('box/delete_post/$id', 'name' => 'box_delete_post', 'verb' => 'POST'), // 備品削除処理 (POST)
   

);
