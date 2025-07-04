<?php

class Model_Loan extends \Model
{
    /**
     * Finds a user by name or creates a new one if not found.
     *
     * @param string $user_name
     * @return int User ID
     * @throws \Database_Exception
     */
    public static function find_or_create_user($user_name)
    {
        // ユーザー名で検索
        $result = \DB::select('user_id')
                      ->from('users')
                      ->where('user_name', $user_name)
                      ->execute();

        if ($result->count() > 0) {
            // 既存のユーザーが見つかった場合、そのIDを返す
            return $result->current()['user_id'];
        } else {
            // ユーザーが存在しない場合、新しく挿入
            list($user_id, $rows_affected) = \DB::insert('users')
                                               ->set(array('user_name' => $user_name))
                                               ->execute();
            if ($rows_affected > 0) {
                return $user_id;
            } else {
                throw new \Database_Exception('Failed to create user.');
            }
        }
    }

    /**
     * Loans a box to a user and records the transaction.
     *
     * @param int $box_id
     * @param int $user_id
     * @return bool True on success, false on failure
     * @throws \Database_Exception
     */
    public static function loan_box($box_id, $user_id)
    {
          // トランザクションを開始
        \DB::start_transaction();

        try {
            // box_status テーブルを更新
            $update_result = \DB::update('box_status')
                               ->set(array(
                                   'status' => '貸出中',
                                   'current_user_id' => $user_id,
                               ))
                               ->where('box_id', $box_id)
                               ->execute();

            // 更新が成功し、かつ1行以上影響があった場合
            if ($update_result > 0) {
                // box_transactions テーブルに貸出記録を挿入
                list($insert_id, $rows_affected) = \DB::insert('box_transactions')
                                                     ->set(array(
                                                         'box_id' => $box_id,
                                                         'user_id' => $user_id,
                                                         'action_type' => '貸出',
                                                         // 修正点: 'transaction_date' を 'transaction_time' に変更
                                                         'transaction_time' => \Date::forge()->format('mysql'), // 現在時刻を記録
                                                     ))
                                                     ->execute();
                if ($rows_affected > 0) {
                    \DB::commit_transaction(); // 全ての操作が成功した場合、コミット
                    return true;
                }
            }
            \DB::rollback_transaction(); // 失敗した場合、ロールバック
            return false;
        } catch (\Exception $e) {
            \DB::rollback_transaction(); // 例外が発生した場合、ロールバック
            \Log::error('Loan transaction failed: ' . $e->getMessage()); // エラーログの記録
            throw new \Database_Exception('Loan transaction failed: ' . $e->getMessage());
        }
    }

    /**
     * Gets the current loan status and user for a given box.
     *
     * @param int $box_id
     * @return array|null Box status and user data, or null if not found
     */
    public static function get_box_loan_status($box_id)
    {
        $result = \DB::select(
                        'bs.status',
                        'u.user_name',
                        'bs.current_user_id'
                    )
                    ->from(array('box_status', 'bs'))
                    ->join(array('users', 'u'), 'left')
                    ->on('bs.current_user_id', '=', 'u.user_id')
                    ->where('bs.box_id', $box_id)
                    ->execute();

        if ($result->count() > 0) {
            return $result->current();
        }
        return null;
    }

     public static function return_box($box_id, $user_id_who_was_loaned = null)
    {
        // トランザクションを開始
        \DB::start_transaction();

        try {
            // box_status テーブルを更新: 貸出可能に戻し、借りているユーザーIDをNULLにする
            $update_result = \DB::update('box_status')
                               ->set(array(
                                   'status' => '貸出可能',
                                   'current_user_id' => null, // NULL に設定
                               ))
                               ->where('box_id', $box_id)
                               ->execute();

            // 更新が成功し、かつ1行以上影響があった場合
            if ($update_result > 0) {
                // box_transactions テーブルに返却記録を挿入
                list($insert_id, $rows_affected) = \DB::insert('box_transactions')
                                                     ->set(array(
                                                         'box_id' => $box_id,
                                                         'user_id' => $user_id_who_was_loaned, // 返却者は元の貸出者と紐づける
                                                         'action_type' => '返却',
                                                         'transaction_time' => \Date::forge()->format('mysql'), // 現在時刻を記録
                                                     ))
                                                     ->execute();
                if ($rows_affected > 0) {
                    \DB::commit_transaction(); // 全ての操作が成功した場合、コミット
                    return true;
                }
            }
            \DB::rollback_transaction(); // 失敗した場合、ロールバック
            return false;
        } catch (\Exception $e) {
            \DB::rollback_transaction(); // 例外が発生した場合、ロールバック
            \Log::error('Return transaction failed: ' . $e->getMessage()); // エラーログの記録
            throw new \Database_Exception('Return transaction failed: ' . $e->getMessage());
        }
    }
}