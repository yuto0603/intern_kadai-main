<?php
return array(
    '_root_'  => 'box/index',  // The default route
    '_404_'   => 'box/404',    // The 404 route

    // ボックス関連のルート
    'box' => 'box/index', // メインの備品一覧ページ

    // パラメータを受け取るルートは (:num) と $1 で統一
    'box/loan/(:num)'   => 'box/loan/$1',    // 貸出フォームページ
    'box/return/(:num)' => 'box/return/$1',  // 返却フォームページ
    'box/edit/(:num)'   => 'box/edit/$1',    // 編集フォームページ
    'box/delete/(:num)' => 'box/delete/$1',  // 削除アクション

    // パラメータを受け取らないルート
    'box/manage' => 'box/manage', // 備品管理ページ
    'box/create' => 'box/create', // 備品作成フォーム

);