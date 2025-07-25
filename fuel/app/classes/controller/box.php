<?php

class Controller_Box extends Controller
{
    public function before()
    {
        parent::before();
        \Security::fetch_token();
        \View::set_global('global_data', array());
        \View::set_global('auto_encode', true); 
    }
   
    public function action_index()
    {
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
            'flash_message_success' => \Session::get_flash('success'), // 成功
            'flash_message_error' => \Session::get_flash('error'),     // エラー
            'global_data'           => array(),
        );

        ob_start();// 出力をバッファリング
        extract($data);// 変数を現在のスコープにインポート
        include APPPATH.'views/box/index.php';
        $output = ob_get_clean();// バッファの内容を取得
        return \Response::forge($output);  // レスポンスとして返す
    }

    /**
     * URL: /box/loan/{box_id}
     * @param int $id ボックスのID
     */
    public function action_loan($id = null)
    {
        if ($id === null)
        {
            return $this->action_404();
        }

        if (\Input::method() == 'POST') // POSTリクエスト→貸出処理を実行
        {
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
                    return \Response::redirect('box'); 
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

        $box_data = Model_Box::get_all_boxes();  // GETリクエスト→貸出ページへ
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
            'title'            => '備品の貸出',
            'item_id'          => $target_box['box_id'],
            'item_label' => $target_box['label'],
            'item_type'  => 'モニター', 
            'loaned_user_name' => $loaned_user_name,
            'flash_message_error' => \Session::get_flash('error'),
            'csrf_token' => \Security::fetch_token(),
            'global_data'           => array(),
        );

        
        ob_start();
        extract($data);// 変数を現在のスコープにインポート
        include APPPATH.'views/box/loan.php';
        $output = ob_get_clean();// バッファの内容を取得
        return \Response::forge($output); // レスポンスとして返す
    }

    /**
     * URL: /box/return/{box_id}
     * @param int $id ボックスのID
     */
    public function action_return($id = null)
    {
        if ($id === null)
        {
            return $this->action_404();
        }
        
        if (\Input::method() == 'POST')  // POSTリクエストの場合、返却処理を実行
        {
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
            'flash_message_error' => \Session::get_flash('error'),
            'csrf_token' => \Security::fetch_token(),
            'global_data'           => array(),
        );

        ob_start();
        extract($data);// 変数を現在のスコープにインポート
        include APPPATH.'views/box/return.php';
        $output = ob_get_clean();// バッファの内容を取得
        return \Response::forge($output);        // レスポンスとして返す

    }

    /**
     * 備品管理ページを表示するアクション (GET)
     * URL: /box/manage
     */
    public function action_manage()
    {
        $boxes = Model_Box::get_all_boxes();// 備品データを取得

        // 各備品の貸出状況と借りているユーザー名を取得して追加
        $detailed_boxes = [];
        // $boxes が配列でない場合や空の場合に備える
        if (is_array($boxes) && !empty($boxes)) {
            foreach ($boxes as $box) {
                // box_id と label の存在を確実にチェック
                $box_id = isset($box['box_id']) ? $box['box_id'] : null;
                $label = isset($box['label']) ? $box['label'] : 'Unknown Label';

                if ($box_id === null) {
                    \Log::warning('Skipping box entry due to missing box_id in action_manage: ' . print_r($box, true));
                    continue; // box_id がなければスキップ
                }

                $loan_status = Model_Loan::get_box_loan_status($box_id); 
                $box['status'] = $loan_status ? $loan_status['status'] : '貸出可能';
                $box['current_user_name'] = $loan_status ? $loan_status['user_name'] : null;
                $detailed_boxes[] = $box;
            }
        }

        $data = array(
            'title'         => '備品管理',
            // Knockout.js の初期データとして JSON 形式でビューに渡す
            'initial_boxes_json' => json_encode($detailed_boxes), // 詳細な備品データを渡す
            'flash_message_success' => \Session::get_flash('success'),
            'flash_message_error' => \Session::get_flash('error'),
            'global_data'           => array(),
        );

        ob_start();
        extract($data);
        include APPPATH.'views/box/manage.php';
        $output = ob_get_clean();
        
        return \Response::forge($output);
    }

    /**
     * 新しい備品を追加するアクション (Ajax用)
     * POSTリクエストを受け取り、JSON形式で結果を返す
     * URL: /box/api/create
     */
    public function action_api_create()
    {
        // Ajaxリクエストかどうかの確認
        if (!\Input::is_ajax()) {
            return \Response::forge(json_encode(['status' => 'error', 'message' => 'Invalid request method.']), 400);
        }

        if (\Input::method() != 'POST') {
            return \Response::forge(json_encode(['status' => 'error', 'message' => 'Method not allowed.']), 405);
        }

        // CSRFトークンの検証
        if ( ! \Security::check_token())
        {
            return \Response::forge(json_encode(['status' => 'error', 'message' => 'CSRF token mismatch. Please reload.']), 403);
        }

        $label = \Input::post('label');

        if (empty($label))
        {
            return \Response::forge(json_encode(['status' => 'error', 'message' => '備品ラベルを入力してください。']), 400);
        }

        try {
            $new_box_id = Model_Box::create_box($label);

            if ($new_box_id)
            {
                // 成功時は新しい備品のIDとラベル、初期ステータスを返す
                return \Response::forge(json_encode([
                    'status' => 'success',
                    'message' => '新しい備品 「'.htmlspecialchars($label).'」 を追加しました。',
                    'box' => [ // Knockout.js ViewModelが期待する形式で返す
                        'box_id' => $new_box_id,
                        'label' => $label,
                        'status' => '貸出可能',
                        'current_user_name' => null
                    ],
                    'new_csrf_token' => \Security::fetch_token() // ★新しいトークンを追加★
                ]), 200);
            }
            else
            {
                return \Response::forge(json_encode(['status' => 'error', 'message' => '備品の追加に失敗しました。']), 500);
            }
        } catch (\Database_Exception $e) {
            \Log::error('Database Exception when creating box via API: ' . $e->getMessage());
            return \Response::forge(json_encode(['status' => 'error', 'message' => 'データベースエラーが発生しました。']), 500);
        } catch (\Exception $e) {
            \Log::error('General Exception when creating box via API: ' . $e->getMessage());
            return \Response::forge(json_encode(['status' => 'error', 'message' => '予期せぬエラーが発生しました。']), 500);
        } 
    }

    /**
     * 備品を削除するアクション (Ajax用)
     * POSTリクエストを受け取り、JSON形式で結果を返す
     * URL: /box/api/delete/{box_id}
     * @param int $id 削除するボックスのID
     */
    public function action_api_delete($id = null)
    {
        if ($id === null) {
            return \Response::forge(json_encode(['status' => 'error', 'message' => '削除する備品のIDが指定されていません。']), 400);
        }

        // AjaxリクエストかつPOSTメソッドであることを確認
        if (!\Input::is_ajax() || \Input::method() != 'POST') {
            return \Response::forge(json_encode(['status' => 'error', 'message' => 'Invalid request.']), 400);
        }

        // CSRFトークンの検証
        if ( ! \Security::check_token())
        {
            return \Response::forge(json_encode(['status' => 'error', 'message' => 'CSRF token mismatch. Please reload.']), 403);
        }

        try {
            // 削除対象の備品を取得し、存在チェック
            $box = Model_Box::get_box($id);
            if (!$box)
            {
                return \Response::forge(json_encode(['status' => 'error', 'message' => '指定された備品が見つかりませんでした。']), 404);
            }

            // 備品が貸出中であるかチェック
            $loan_status = Model_Loan::get_box_loan_status($id);
            if ($loan_status && $loan_status['status'] === '貸出中') {
                return \Response::forge(json_encode(['status' => 'error', 'message' => 'この備品は現在貸出中のため、削除できません。']), 409);
            }
            
            // Model_Box を使って備品を削除
            $deleted = Model_Box::delete_box($id);

            if ($deleted)
            {
                // 成功時には、新しいCSRFトークンを含めて返す
                return \Response::forge(json_encode([
                    'status' => 'success',
                    'message' => '備品 (ID: '.$id.') を削除しました。',
                    'new_csrf_token' => \Security::fetch_token() // 新しいトークンを追加
                ]), 200);
            }
            else
            {
                return \Response::forge(json_encode(['status' => 'error', 'message' => '備品の削除に失敗しました。']), 500);
            }
        } catch (\Database_Exception $e) {
            \Log::error('Database Exception in API delete for box ID ' . $id . ': ' . $e->getMessage());
            return \Response::forge(json_encode(['status' => 'error', 'message' => 'データベースエラーが発生しました。']), 500);
        } catch (\Exception $e) {
            \Log::error('General Exception in API delete for box ID ' . $id . ': ' . $e->getMessage());
            return \Response::forge(json_encode(['status' => 'error', 'message' => '予期せぬエラーが発生しました。']), 500);
        }
    }

    /**
     * 既存の備品を編集するアクション (GET: フォーム表示, POST: フォーム処理)
     * URL: /box/edit/{box_id}
     * @param int $id 編集するボックスのID
     */
    public function action_edit($id = null)
    {
        if ($id === null)
        {
            return $this->action_404();
        }

        // 既存の備品情報を取得
        $box = Model_Box::get_box($id);
        if (!$box)
        {
            \Session::set_flash('error', '指定された備品が見つかりませんでした。');
            return \Response::redirect('box/manage');
        }

        // POSTリクエストの場合、備品更新処理を実行
        if (\Input::method() == 'POST')
        {
            // CSRFトークンの検証
            if ( ! \Security::check_token())
            {
                \Session::set_flash('error', '不正なリクエストです。ページを再読み込みしてください。');
                return \Response::redirect('box/edit/'.$id);
            }

            $new_label = \Input::post('label');

            // 入力値のバリデーション
            if (empty($new_label))
            {
                \Session::set_flash('error', '備品ラベルを入力してください。');
                return \Response::redirect('box/edit/'.$id);
            }

            try {
                // Model_Box を使って備品を更新
                $updated = Model_Box::update_box($id, $new_label);

                if ($updated)
                {
                    \Session::set_flash('success', '備品 「'.htmlspecialchars($box['label']).'」 を 「'.htmlspecialchars($new_label).'」 に更新しました。');
                    return \Response::redirect('box/manage'); // 管理ページへリダイレクト
                }
                else
                {
                    \Session::set_flash('error', '備品の更新に失敗しました。変更がなかったか、エラーが発生しました。');
                }
            } catch (\Database_Exception $e) {
                \Session::set_flash('error', 'データベースエラーが発生しました: ' . $e->getMessage());
                \Log::error('Database Exception when updating box (ID: '.$id.'): ' . $e->getMessage());
            } catch (\Exception $e) {
                \Session::set_flash('error', '予期せぬエラーが発生しました: ' . $e->getMessage());
                \Log::error('General Exception when updating box (ID: '.$id.'): ' . $e->getMessage());
            }
            return \Response::redirect('box/edit/'.$id); // エラー時はフォームに戻る
        }

        // GETリクエストの場合、備品編集フォームを表示
        $data = array(
            'title' => '備品の編集',
            'item_id' => $box['box_id'],
            'item_label' => $box['label'], // 既存のラベルをフォームに表示するため
            'flash_message_error' => \Session::get_flash('error'),
            'csrf_token' => \Security::fetch_token(),
            'global_data'           => array(),
        );

        ob_start();
        extract($data);
        include APPPATH.'views/box/edit.php';
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
                // 備品が貸出中であるかチェック
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