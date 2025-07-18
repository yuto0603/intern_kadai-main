<?php

class Model_Box
{
    public static function get_all_boxes()
    {
        $result = \DB::select('box_id', 'label')
                      ->from('boxes')
                       ->order_by('box_id', 'ASC')//box_id昇順へ変更
                      ->execute();
                      
        return $result->as_array();
    }

    /**
     * Creates a new box and sets its initial status to '貸出可能'.
     *
     * @param string $label The label for the new box (e.g., 'B-16')
     * @return int|bool The new box_id on success, false on failure
     * @throws \Database_Exception
     */
    public static function create_box($label)
    {
        // トランザクションを開始
        \DB::start_transaction();

        try {
            // 1. boxes テーブルに新しい備品を挿入
            list($box_id, $rows_affected_box) = \DB::insert('boxes')
                                                 ->set(array('label' => $label))
                                                 ->execute();

            if ($rows_affected_box > 0) {
                // 2. box_status テーブルに初期ステータスを挿入
                list($status_id, $rows_affected_status) = \DB::insert('box_status')
                                                            ->set(array(
                                                                'box_id' => $box_id,
                                                                'status' => '貸出可能',
                                                                'current_user_id' => null,
                                                            ))
                                                            ->execute();
                if ($rows_affected_status > 0) {
                    \DB::commit_transaction(); // 両方の挿入が成功した場合、コミット
                    return $box_id; // 新しい備品のIDを返す
                }
            }
            \DB::rollback_transaction(); // どちらかの挿入が失敗した場合、ロールバック
            return false;
        } catch (\Exception $e) {
            \DB::rollback_transaction(); // 例外が発生した場合、ロールバック
            \Log::error('Failed to create new box: ' . $e->getMessage()); // エラーログの記録
            throw new \Database_Exception('Failed to create new box: ' . $e->getMessage());
        }
    }

    /**
     * Gets a single box by its ID.
     *
     * @param int $box_id The ID of the box to retrieve.
     * @return array|null The box data as an associative array, or null if not found.
     */
    public static function get_box($box_id)
    {
        $result = \DB::select('box_id', 'label')
                      ->from('boxes')
                      ->where('box_id', $box_id)
                      ->limit(1)
                      ->execute();
                      
        return $result->as_array();
    }

    /**
     * Updates an existing box's label.
     *
     * @param int $box_id The ID of the box to update.
     * @param string $new_label The new label for the box.
     * @return bool True on success, false on failure.
     * @throws \Database_Exception
     */
    public static function update_box($box_id, $new_label)
    {
        try {
            $rows_affected = \DB::update('boxes')
                               ->set(array('label' => $new_label))
                               ->where('box_id', $box_id)
                               ->execute();

            return $rows_affected > 0;
        } catch (\Exception $e) {
            \Log::error('Failed to update box (ID: '.$box_id.'): ' . $e->getMessage());
            throw new \Database_Exception('Failed to update box: ' . $e->getMessage());
        }
    }

    /**
     * Deletes a box and its related data from the database.
     *
     * @param int $box_id The ID of the box to delete.
     * @return bool True on success, false on failure.
     * @throws \Database_Exception
     */
    public static function delete_box($box_id)
    {
        // トランザクションを開始
        \DB::start_transaction();

        try {
            // 1. box_transactions テーブルから関連する履歴を削除
            \DB::delete('box_transactions')
                ->where('box_id', $box_id)
                ->execute();

            // 2. box_status テーブルから関連するステータスを削除
            \DB::delete('box_status')
                ->where('box_id', $box_id)
                ->execute();

            // 3. boxes テーブルから備品本体を削除
            $rows_affected = \DB::delete('boxes')
                               ->where('box_id', $box_id)
                               ->execute();

            if ($rows_affected > 0) {
                \DB::commit_transaction(); // 全ての削除が成功した場合、コミット
                return true;
            } else {
                \DB::rollback_transaction(); // 備品が見つからなかった場合など、ロールバック
                return false; // 削除対象の備品が存在しなかったことを示す
            }
        } catch (\Exception $e) {
            \DB::rollback_transaction(); // 例外が発生した場合、ロールバック
            \Log::error('Failed to delete box (ID: '.$box_id.'): ' . $e->getMessage()); // エラーログの記録
            throw new \Database_Exception('Failed to delete box: ' . $e->getMessage());
        }
    }
}
