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
        \View::set_global('global_data', array());
        //\View::set_global('auto_encode', true);
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
            'global_data'           => array(),
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
            'global_data'           => array(),
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
     * 返却ページを表示するアクション (GET) または返却処理を実行するアクション (POST)
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
            'global_data'           => array(),
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

        // ここで $boxes が配列であり、かつ空でないことを確認
        if (is_array($boxes) && !empty($boxes)) {
            foreach ($boxes as $box) {
                // 各 $box が配列であること、そして 'box_id' と 'label' キーが存在し、null でないことを確認
                // null 合体演算子 (??) を使用して、より簡潔に記述
                $item_id = $box['box_id'] ?? null; // PHP 7.0 以上
                $item_label = $box['label'] ?? '不明な備品'; // PHP 7.0 以上

                // もし PHP のバージョンが古い場合 (PHP 5.x), 以下のように記述
                // $item_id = isset($box['box_id']) ? $box['box_id'] : null;
                // $item_label = isset($box['label']) ? $box['label'] : '不明な備品';


                // IDが取得できない場合は、その備品はスキップする（これはそのまま）
                if ($item_id === null) {
                    \Log::warning('Skipping box entry in manage due to missing or null box_id: ' . print_r($box, true));
                    continue;
                }
                
                $item_type = 'モニター'; 

                if (!isset($items_by_type[$item_type])) {
                    $items_by_type[$item_type] = array();
                }
                $items_by_type[$item_type][] = array(
                    'box_id' => $item_id,
                    'label'  => $item_label,
                    'type'   => $item_type,
                );
            }
        } else {
            // $boxes が空または配列でない場合のログ出力
            \Log::info('No boxes found or invalid box data format in action_manage.');
            // ここで $items_by_type は空のままなので、問題なく処理が進む
        }

        // 変数をビューファイル内で使えるようにする
        $data = array(
            'title'         => '備品管理',
            'items_by_type' => $items_by_type,
            'flash_message_success' => \Session::get_flash('success'), // 成功メッセージを取得
            'flash_message_error' => \Session::get_flash('error'),     // エラーメッセージを取得
            'global_data'           => array(),
        );

        ob_start();
        extract($data);
        include APPPATH.'views/box/manage.php';
        $output = ob_get_clean();
        
        return \Response::forge($output);
    }


    /**
     * 新しい備品を追加するアクション (GET: フォーム表示, POST: フォーム処理)
     * URL: /box/create
     */
    public function action_create()
    {
        // POSTリクエストの場合、備品追加処理を実行
        if (\Input::method() == 'POST')
        {
            // CSRFトークンの検証
            if ( ! \Security::check_token())
            {
                \Session::set_flash('error', '不正なリクエストです。ページを再読み込みしてください。');
                return \Response::redirect('box/create');
            }

            $label = \Input::post('label');

            // 入力値のバリデーション
            if (empty($label))
            {
                \Session::set_flash('error', '備品ラベルを入力してください。');
                return \Response::redirect('box/create');
            }

            try {
                // Model_Box を使って新しい備品を作成
                $new_box_id = Model_Box::create_box($label);

                if ($new_box_id)
                {
                    \Session::set_flash('success', '新しい備品 「'.htmlspecialchars($label).'」 を追加しました。 (ID: '.$new_box_id.')');
                    return \Response::redirect('box/manage'); // 管理ページへリダイレクト
                }
                else
                {
                    \Session::set_flash('error', '備品の追加に失敗しました。');
                }
            } catch (\Database_Exception $e) {
                \Session::set_flash('error', 'データベースエラーが発生しました: ' . $e->getMessage());
                \Log::error('Database Exception when creating box: ' . $e->getMessage());
            } catch (\Exception $e) {
                \Session::set_flash('error', '予期せぬエラーが発生しました: ' . $e->getMessage());
                \Log::error('General Exception when creating box: ' . $e->getMessage());
            }
            return \Response::redirect('box/create'); // エラー時はフォームに戻る
        }

        // GETリクエストの場合、備品追加フォームを表示
        // View::forge の代わりに include 方式を使用
        $data = array(
            'title' => '備品の追加',
            'flash_message_error' => \Session::get_flash('error'), // エラーメッセージを表示
            'csrf_token' => \Security::fetch_token(), // CSRFトークンをビューに渡す
            'global_data'           => array(),
        );

        ob_start();
        extract($data);
        include APPPATH.'views/box/create.php'; // 新しいビューファイルをインクルード
        $output = ob_get_clean();
        
        return \Response::forge($output);
    }

     /**
     * 備品を削除するアクション (POST)
     * URL: /box/delete/{box_id}
     * @param int $id 削除するボックスのID
     */
    public function action_delete($id = null)
    {
      if ($id === null)
        {
            \Session::set_flash('error', '削除する備品のIDが指定されていません。');
            \Response::redirect('box/manage');
            exit;
        }

        // POSTリクエストであること、かつCSRFトークンが有効であることを確認
        if (\Input::method() == 'POST')
        {
            if ( ! \Security::check_token())
            {
                \Session::set_flash('error', '不正なリクエストです。ページを再読み込みしてください。');
                \Response::redirect('box/manage');
                exit;
            }

            try {
                // ここで削除前に備品情報を取得する必要がある場合（例: ログに残すため）
                // ただし、表示に失敗する可能性のある 'label' へのアクセスは避ける
                $box_exists_before_delete = (Model_Box::get_box($id) !== null);

                // 備品が貸出中であるかチェック
                // このチェックは削除を試みる前に行うため、問題ないはずです。
                $loan_status = Model_Loan::get_box_loan_status($id);
                if ($loan_status && $loan_status['status'] === '貸出中') {
                    \Session::set_flash('error', 'この備品は現在貸出中のため、削除できません。');
                    \Response::redirect('box/manage');
                    exit;
                }
                
                // Model_Box を使って備品を削除
                $deleted = Model_Box::delete_box($id);

                if ($deleted)
                {
                    // 備品IDのみで成功メッセージを表示
                    \Session::set_flash('success', '備品 (ID: '.$id.') を削除しました。');
                    \Log::info('Box ID: ' . $id . ' deleted successfully.'); // ログにはIDと元のラベルも残せる
                }
                else
                {
                    // 削除失敗のメッセージ
                    \Session::set_flash('error', '備品の削除に失敗しました。指定された備品が存在しないか、エラーが発生しました。');
                    \Log::error('Failed to delete box ID: ' . $id . '.');
                }
            } catch (\Database_Exception $e) {
                \Session::set_flash('error', 'データベースエラーが発生しました。');
                \Log::error('Database Exception in delete for box ID ' . $id . ': ' . $e->getMessage());
            } catch (\Exception $e) {
                \Session::set_flash('error', '予期せぬエラーが発生しました。');
                \Log::error('General Exception in delete for box ID ' . $id . ': ' . $e->getMessage());
            }
        }
        else
        {
            \Session::set_flash('error', '直接アクセスは許可されていません。');
        }

        // 削除処理後、管理ページへリダイレクト
        \Response::redirect('box/manage');
        exit;
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