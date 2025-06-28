<?php

class Controller_Box extends Controller
{
    /**
     * index アクション (備品一覧表示)
     */
    public function action_index()
    {
        $boxes = Model_Box::get_all_boxes();
        
        // 各ボックスのステータスデータを取得
        $box_status_data = array();
        foreach ($boxes as $box) {
            $status_info = Model_Box::get_box_status_by_id($box['box_id']);
            if ($status_info) {
                $box_status_data[$box['box_id']] = $status_info;
            }
        }

        $view = View::forge('box/index', array(
            'boxes' => $boxes,
            'box_status_data' => $box_status_data, // Viewにステータスデータを渡す
            'title' => '備品一覧',
        ));
        
        return Response::forge($view);
    }

    /**
     * 貸出ページを表示するアクション (GET)
     */
    public function action_loan($id = null)
    {
        if ($id === null) {
            return $this->action_404();
        }

        $box = Model_Box::get_box_by_id($id);
        if (!$box) {
            return $this->action_404(); // ボックスが見つからない場合
        }
        
        $box_status = Model_Box::get_box_status_by_id($id);
        if ($box_status && $box_status['status'] == '貸出中') {
            // すでに貸出中の場合は、貸出ページではなくメインページにリダイレクト
            // またはエラーメッセージを表示する
            \Session::set_flash('error', htmlspecialchars($box['label']) . ' は現在貸出中です。');
            return \Response::redirect('box');
        }

        $view = View::forge('box/loan', array(
            'title'      => '備品の貸出',
            'item_id'    => $box['box_id'],
            'item_label' => $box['label'],
            'item_type'  => 'モニター', // 現状は固定
        ));
        
        return \Response::forge($view);
    }

    /**
     * 貸出処理を実行するアクション (POST)
     * @param int $id ボックスのID
     */
    public function action_loan_post($id = null)
    {
        // CSRFトークンの検証 (FuelPHP FormクラスやSecurityクラスを使用するとより安全)
        if (\Input::method() != 'POST') {
            \Session::set_flash('error', '不正なリクエストです。');
            return \Response::redirect('box');
        }

        $box_id = \Input::post('box_id');
        $user_name = \Input::post('user_name');

        if (!$box_id || !$user_name) {
            \Session::set_flash('error', '備品IDまたはユーザー名が不足しています。');
            return \Response::redirect('box/loan/' . $id);
        }

        // ユーザーが存在しない場合は作成し、IDを取得
        $user_id = Model_User::get_or_create_user($user_name);

        if ($user_id) {
            // box_status を貸出中に更新
            $status_updated = Model_Box::update_box_status($box_id, '貸出中', $user_id);
            // box_transactions にログを記録
            $log_added = Model_Box::add_box_transaction($box_id, $user_id, '貸出');

            if ($status_updated && $log_added) {
                \Session::set_flash('success', htmlspecialchars($user_name) . ' が ' . htmlspecialchars(Model_Box::get_box_by_id($box_id)['label']) . ' を貸し出しました。');
            } else {
                \Session::set_flash('error', '貸出処理に失敗しました。');
            }
        } else {
            \Session::set_flash('error', 'ユーザーの特定または作成に失敗しました。');
        }

        return \Response::redirect('box'); // メインページへリダイレクト
    }

    /**
     * 返却ページを表示するアクション (GET)
     */
    public function action_return($id = null)
    {
        if ($id === null) {
            return $this->action_404();
        }

        $box = Model_Box::get_box_by_id($id);
        if (!$box) {
            return $this->action_404(); // ボックスが見つからない場合
        }
        
        $box_status = Model_Box::get_box_status_by_id($id);
        if (!$box_status || $box_status['status'] != '貸出中') {
            // 貸出中でない場合は、返却ページではなくメインページにリダイレクト
            \Session::set_flash('error', htmlspecialchars($box['label']) . ' は現在貸出されていません。');
            return \Response::redirect('box');
        }

        $loaned_user_name = isset($box_status['user_name']) ? $box_status['user_name'] : '不明なユーザー';

        $view = View::forge('box/return', array(
            'title'            => '備品の返却',
            'item_id'          => $box['box_id'],
            'item_label'       => $box['label'],
            'item_type'        => 'モニター', // 現状は固定
            'loaned_user_name' => $loaned_user_name,
        ));
        
        return \Response::forge($view);
    }

    /**
     * 返却処理を実行するアクション (POST)
     * @param int $id ボックスのID
     */
    public function action_return_post($id = null)
    {
        if (\Input::method() != 'POST') {
            \Session::set_flash('error', '不正なリクエストです。');
            return \Response::redirect('box');
        }

        $box_id = \Input::post('box_id');

        if (!$box_id) {
            \Session::set_flash('error', '備品IDが不足しています。');
            return \Response::redirect('box/return/' . $id);
        }

        // 現在のユーザーIDを取得（ログ記録のため）
        $current_status = Model_Box::get_box_status_by_id($box_id);
        $user_id_for_log = null;
        if ($current_status && isset($current_status['current_user_id'])) {
            $user_id_for_log = $current_status['current_user_id'];
        }

        // box_status を空きに更新
        $status_updated = Model_Box::update_box_status($box_id, '貸出可能', null);
        // box_transactions にログを記録
        $log_added = false;
        if ($user_id_for_log) { // 借りているユーザーが特定できた場合のみログを記録
             $log_added = Model_Box::add_box_transaction($box_id, $user_id_for_log, '返却');
        } else {
             // ユーザーID不明でもステータス更新が成功すればOKとする
             $log_added = true; 
        }
       
        if ($status_updated && $log_added) {
            \Session::set_flash('success', htmlspecialchars(Model_Box::get_box_by_id($box_id)['label']) . ' を返却しました。');
        } else {
            \Session::set_flash('error', '返却処理に失敗しました。');
        }

        return \Response::redirect('box'); // メインページへリダイレクト
    }

    /**
     * 備品管理ページを表示するアクション (GET)
     */
    public function action_manage()
    {
        $boxes = Model_Box::get_all_boxes();
        
        $items_by_type = array();

        // 実際のアプリではboxesテーブルにitem_typeカラムを追加し、それに基づいてグループ化
        // ここではまだitem_typeカラムがないため、box_idで仮にグループ化
        foreach ($boxes as $box) {
            $item_id = $box['box_id'];
            $item_label = $box['label'];
            
            // 備品の種類を仮で判定 (将来的にDBカラムから取得)
            $item_type = 'モニター';
            if ($item_id >= 16 && $item_id <= 20) {
                $item_type = 'Type-Cコード';
            } elseif ($item_id > 20) { // 21以降は「その他」などの新しいカテゴリに
                $item_type = 'その他備品'; 
            }

            if (!isset($items_by_type[$item_type])) {
                $items_by_type[$item_type] = array();
            }
            $items_by_type[$item_type][] = array(
                'box_id' => $item_id,
                'label'  => $item_label,
                'type'   => $item_type,
            );
        }

        $view = View::forge('box/manage', array(
            'title'         => '備品管理',
            'items_by_type' => $items_by_type,
        ));
        
        return \Response::forge($view);
    }

    /**
     * 新規備品登録処理 (POST)
     */
    public function action_add_post()
    {
        if (\Input::method() != 'POST') {
            \Session::set_flash('error', '不正なリクエストです。');
            return \Response::redirect('box/manage');
        }

        $new_label = \Input::post('new_label');
        // $item_type = \Input::post('item_type'); // 現状は使用しないが、将来的に必要

        if (!$new_label) {
            \Session::set_flash('error', '新しい備品のラベルが入力されていません。');
            return \Response::redirect('box/manage');
        }

        $inserted_id = Model_Box::add_new_box($new_label);

        if ($inserted_id) {
            \Session::set_flash('success', htmlspecialchars($new_label) . ' を新規登録しました。');
        } else {
            \Session::set_flash('error', '備品の新規登録に失敗しました。');
        }

        return \Response::redirect('box/manage');
    }

    /**
     * 既存備品の編集処理 (POST)
     */
    public function action_edit_post($id = null)
    {
        if (\Input::method() != 'POST') {
            \Session::set_flash('error', '不正なリクエストです。');
            return \Response::redirect('box/manage');
        }

        $box_id = \Input::post('box_id');
        $edited_label = \Input::post('edited_label');

        if (!$box_id || !$edited_label) {
            \Session::set_flash('error', '備品IDまたはラベルが不足しています。');
            return \Response::redirect('box/manage');
        }

        $updated = Model_Box::update_box_label($box_id, $edited_label);

        if ($updated) {
            \Session::set_flash('success', htmlspecialchars($edited_label) . ' の備品情報を更新しました。');
        } else {
            \Session::set_flash('error', '備品情報の更新に失敗しました。');
        }

        return \Response::redirect('box/manage');
    }

    /**
     * 備品削除処理 (POST)
     */
    public function action_delete_post($id = null)
    {
        if (\Input::method() != 'POST') {
            \Session::set_flash('error', '不正なリクエストです。');
            return \Response::redirect('box/manage');
        }

        $box_id = \Input::post('box_id');

        if (!$box_id) {
            \Session::set_flash('error', '備品IDが不足しています。');
            return \Response::redirect('box/manage');
        }

        $deleted = Model_Box::delete_box($box_id);

        if ($deleted) {
            \Session::set_flash('success', '備品 (ID: ' . htmlspecialchars($box_id) . ') を削除しました。');
        } else {
            \Session::set_flash('error', '備品の削除に失敗しました。関連するデータがある可能性があります。');
        }

        return \Response::redirect('box/manage');
    }

    /**
     * 404 Not Found のアクション
     * ... (変更なし) ...
     */
    public function action_404()
    {
        return \Response::forge(\View::forge('404'), 404);
    }
}