<?php

class Controller_Box extends Controller
{
    /**
     * FuelPHPのbeforeメソッド
     * 全てのアクションが実行される前に呼び出されます。
     * ここではCSRFトークンの生成を行います。
     */
    public function before()
    {
        parent::before();
        // セキュリティ要件を満たすため、CSRFトークンを生成しセッションに保存します。
        // これにより、フォーム送信時にトークンを検証できるようになります。
        \Security::fetch_token();
    }
   
    public function action_index()
    {
        // Model_Boxから全ての備品を取得
        $boxes = Model_Box::get_all_boxes();

        // 各備品の貸出状況と借りているユーザー名を取得して追加
        $detailed_boxes = [];
        foreach ($boxes as $box) {
            $loan_status = Model_Loan::get_box_loan_status($box['box_id']); 
            $box['status'] = $loan_status ? $loan_status['status'] : '貸出可能';
            $box['current_user_name'] = $loan_status ? $loan_status['user_name'] : null;
            $detailed_boxes[] = $box;
        }

        // 変数をビューファイル内で使えるようにする
        $data = array(
            'boxes' => $detailed_boxes,
            'title' => '備品一覧',
            'flash_message_success' => \Session::get_flash('success'), // 成功メッセージを取得
            'flash_message_error' => \Session::get_flash('error'),     // エラーメッセージを取得
        );

        // 出力をバッファリングする
        ob_start();
        
        // 変数を現在のスコープにインポート
        extract($data);
        
        // ビューファイルを直接インクルード
        include APPPATH.'views/box/index.php';
        
        // バッファの内容を取得
        $output = ob_get_clean();

        // レスポンスとして返す
        return \Response::forge($output);
    }

    /**
     * 貸出ページを表示するアクション (GET) または貸出処理を実行するアクション (POST)
     * URL: /box/loan/{box_id}
     * @param int $id ボックスのID
     */
    public function action_loan($id = null)
    {
        if ($id === null)
        {
            return $this->action_404();
        }

        // POSTリクエストの場合、貸出処理を実行
        if (\Input::method() == 'POST')
        {
            // CSRFトークンの検証 (セキュリティ要件)
            if ( ! \Security::check_token())
            {
                \Session::set_flash('error', '不正なリクエストです。ページを再読み込みしてください。');
                return \Response::redirect('box/loan/'.$id);
            }

            $user_name = \Input::post('user_name');

            // 入力値のバリデーション
            if (empty($user_name))
            {
                \Session::set_flash('error', '名前を入力してください。');
                return \Response::redirect('box/loan/'.$id);
            }

            try {
                $user_id = Model_Loan::find_or_create_user($user_name);
                $loaned = Model_Loan::loan_box($id, $user_id);

                if ($loaned)
                {
                    \Session::set_flash('success', $user_name.'さんがB-'.$id.'モニターを借りました。');
                    return \Response::redirect('box'); // メインページへリダイレクト
                }
                else
                {
                    \Session::set_flash('error', '貸出処理に失敗しました。貸出中の可能性があります。');
                }
            } catch (\Database_Exception $e) {
                \Session::set_flash('error', 'データベースエラーが発生しました。');
                \Log::error('Database Exception in loan process for box ID ' . $id . ': ' . $e->getMessage());
            } catch (\Exception $e) {
                \Session::set_flash('error', '予期せぬエラーが発生しました。');
                \Log::error('General Exception in loan process for box ID ' . $id . ': ' . $e->getMessage());
            }
            return \Response::redirect('box/loan/'.$id);
        }

        // GETリクエストの場合、貸出ページを表示
        $box_data = Model_Box::get_all_boxes(); 
        $target_box = null;
        foreach ($box_data as $box) {
            if ($box['box_id'] == $id) {
                $target_box = $box;
                break;
            }
        }

        if (!$target_box) {
            return $this->action_404();
        }

        // 変数をビューファイル内で使えるようにする
        $data = array(
            'title'      => '備品の貸出',
            'item_id'    => $target_box['box_id'],
            'item_label' => $target_box['label'],
            'item_type'  => 'モニター', 
            'flash_message_error' => \Session::get_flash('error'),
            'csrf_token' => \Security::fetch_token(),
        );

        // 出力をバッファリングする
        ob_start();
        
        // 変数を現在のスコープにインポート
        extract($data);
        
        // ビューファイルを直接インクルード
        include APPPATH.'views/box/loan.php';
        
        // バッファの内容を取得
        $output = ob_get_clean();

        // レスポンスとして返す
        return \Response::forge($output);
    }

    /**
     * 返却ページを表示するアクション (GET)
     * URL: /box/return/{box_id}
     * @param int $id ボックスのID
     */
    public function action_return($id = null)
    {
         if ($id === null)
        {
            return $this->action_404();
        }

        // POSTリクエストの場合、返却処理を実行
        if (\Input::method() == 'POST')
        {
            // CSRFトークンの検証
            if ( ! \Security::check_token())
            {
                \Session::set_flash('error', '不正なリクエストです。ページを再読み込みしてください。');
                return \Response::redirect('box/return/'.$id);
            }

            try {
                // 返却する備品の現在の状態を取得 (誰が借りていたかを知るため)
                $loan_status = Model_Loan::get_box_loan_status($id);
                $loaned_user_id = null;
                $loaned_user_name = '';

                if ($loan_status && $loan_status['status'] === '貸出中') {
                    $loaned_user_id = $loan_status['current_user_id'];
                    $loaned_user_name = $loan_status['user_name'];
                } else {
                    // 貸出中ではないのに返却しようとした場合
                    \Session::set_flash('error', 'この備品は貸出中ではありません。');
                    return \Response::redirect('box');
                }

                // 備品を返却
                $returned = Model_Loan::return_box($id, $loaned_user_id);

                if ($returned)
                {
                    \Session::set_flash('success', ($loaned_user_name ?: '不明なユーザー').'さんがB-'.$id.'モニターを返却しました。');
                    return \Response::redirect('box'); // メインページへリダイレクト
                }
                else
                {
                    \Session::set_flash('error', '返却処理に失敗しました。');
                }
            } catch (\Database_Exception $e) {
                \Session::set_flash('error', 'データベースエラーが発生しました。');
                \Log::error('Database Exception in return process for box ID ' . $id . ': ' . $e->getMessage());
            } catch (\Exception $e) {
                \Session::set_flash('error', '予期せぬエラーが発生しました。');
                \Log::error('General Exception in return process for box ID ' . $id . ': ' . $e->getMessage());
            }
            return \Response::redirect('box/return/'.$id);
        }

        // GETリクエストの場合、返却ページを表示
        // 返却する備品のデータを取得し、誰が借りているか表示する
        $box_data = Model_Box::get_all_boxes(); 
        $target_box = null;
        foreach ($box_data as $box) {
            if ($box['box_id'] == $id) {
                $target_box = $box;
                break;
            }
        }

        if (!$target_box) {
            return $this->action_404();
        }

        // 貸出状況を取得し、借りているユーザー名を表示に使う
        $loan_status = Model_Loan::get_box_loan_status($id);
        $loaned_user_name = ($loan_status && $loan_status['status'] === '貸出中') ? $loan_status['user_name'] : '貸出中ではありません';


        // 変数をビューファイル内で使えるようにする
        $data = array(
            'title'            => '備品の返却',
            'item_id'          => $target_box['box_id'],
            'item_label'       => $target_box['label'],
            'item_type'        => 'モニター', 
            'loaned_user_name' => $loaned_user_name,
            'flash_message_error' => \Session::get_flash('error'), // エラーメッセージを表示
            'csrf_token' => \Security::fetch_token(), // CSRFトークンをビューに渡す
        );

        // 出力をバッファリングする
        ob_start();
        
        // 変数を現在のスコープにインポート
        extract($data);
        
        // ビューファイルを直接インクルード
        include APPPATH.'views/box/return.php';
        
        // バッファの内容を取得
        $output = ob_get_clean();

        // レスポンスとして返す
        return \Response::forge($output);
    }

    

    /**
     * 備品管理ページを表示するアクション (GET)
     * URL: /box/manage
     */
    public function action_manage()
    {
        $boxes = Model_Box::get_all_boxes();
        
        $items_by_type = array();

        // 備品の種類を仮で判定
        foreach ($boxes as $box) {
            $item_id = $box['box_id'];
            $item_label = $box['label'];
            
            $item_type = 'モニター';
            if ($item_id >= 16 && $item_id <= 20) { // 例としてB-16からB-20をType-Cコードとする
                $item_type = 'Type-Cコード';
            } elseif ($item_id > 20) {
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

        // View::forge を使用する（このビューは貸出処理の主要な流れとは異なるため、View::forgeのデバッグは後回し）
        $view = View::forge('box/manage', array(
            'title'         => '備品管理',
            'items_by_type' => $items_by_type,
        ));
        
        return \Response::forge($view);
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