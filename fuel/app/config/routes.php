<?php
return array(
    '_root_'  => 'box/index',
    '_404_'   => 'box/404',

    // Knockout.js 用のAPIエンドポイント
    'box/api/create' => 'box/api_create',
    'box/api/delete/(:num)' => 'box/api_delete/$1',

    // ボックス関連のルート (既存のもの)
    'box' => 'box/index',
    'box/loan/(:num)'   => 'box/loan/$1',
    'box/return/(:num)' => 'box/return/$1',
    'box/edit/(:num)'   => 'box/edit/$1',
    'box/delete/(:num)' => 'box/delete/$1', // このルートは通常のフォーム送信用
    'box/manage' => 'box/manage',
    'box/create' => 'box/create',
);