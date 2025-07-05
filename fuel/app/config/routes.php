<?php
return array(
    '_root_'  => 'box/index',    // The default route
    '_404_'   => 'box/404',      // The 404 route

    // ボックス関連のルート (GETリクエストのみ)
    'box' => array('box/index'), // メインの備品一覧ページ
    'box/loan/:id' => array('box/loan/$id'), // 貸出フォームページ
    'box/return/:id' => array('box/return/$id'), // 返却フォームページ
    'box/manage' => array('box/manage'), // 備品管理ページ
    'box/create' => array('box/create'), 
    'box/edit/:id' => array('box/edit/$id'),
    'box/delete/:id'        => 'box/delete',
);