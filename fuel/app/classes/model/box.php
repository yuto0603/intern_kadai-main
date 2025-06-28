<?php

class Model_Box
{
    public static function get_all_boxes() 
    {
        
        $result = \DB::select('box_id', 'label') 
                      ->from('boxes')            
                      ->execute(); //SQLを実行              
        
        return $result->as_array();
    }

     public static function get_box_by_id($box_id)
    {
        $result = \DB::select('box_id', 'label')
                      ->from('boxes')
                      ->where('box_id', $box_id)
                      ->limit(1)
                      ->execute();
        return $result->current(); // 1件のみ取得
    }

     public static function get_box_status_by_id($box_id)
    {
        $result = \DB::select('bs.status', 'u.user_name', 'bs.current_user_id')
                      ->from(array('box_status', 'bs'))
                      ->join(array('users', 'u'), 'LEFT') // LEFT JOINでユーザーがNULLでもOK
                      ->on('bs.current_user_id', '=', 'u.user_id')
                      ->where('bs.box_id', $box_id)
                      ->limit(1)
                      ->execute();
        return $result->current();
    }

     public static function update_box_status($box_id, $status, $user_id = null)
    {
        // まず、既存のステータスレコードがあるか確認
        $existing_status = \DB::select('box_id')
                                ->from('box_status')
                                ->where('box_id', $box_id)
                                ->execute()
                                ->current();

        if ($existing_status) {
            // 既存レコードがあれば更新
            $rows_affected = \DB::update('box_status')
                                ->set(array(
                                    'status'          => $status,
                                    'current_user_id' => $user_id,
                                    'updated_at'      => \Date::forge()->format('mysql'), // 現在日時をMySQL形式で
                                ))
                                ->where('box_id', $box_id)
                                ->execute();
        } else {
            // レコードがなければ新規挿入
            // （新規備品登録時にも使われることを想定）
            $rows_affected = \DB::insert('box_status')
                                ->columns(array('box_id', 'status', 'current_user_id', 'updated_at'))
                                ->values(array(
                                    $box_id,
                                    $status,
                                    $user_id,
                                    \Date::forge()->format('mysql'),
                                ))
                                ->execute();
            $rows_affected = $rows_affected[1]; // insertの場合は挿入された行数が配列の2番目に返される
        }
        return $rows_affected > 0;
    }

     public static function add_box_transaction($box_id, $user_id, $action_type)
    {
        $rows_affected = \DB::insert('box_transactions')
                            ->columns(array('box_id', 'user_id', 'action_type', 'transaction_time'))
                            ->values(array(
                                $box_id,
                                $user_id,
                                $action_type,
                                \Date::forge()->format('mysql'), // 現在日時
                            ))
                            ->execute();
        return $rows_affected[1] > 0; // 挿入された行数が返される
    }

     public static function add_new_box($label)
    {
        // まずboxesテーブルに挿入
        list($insert_id, $rows_affected) = \DB::insert('boxes')
                                             ->columns(array('label'))
                                             ->values(array($label))
                                             ->execute();
        
        if ($rows_affected > 0) {
            // 次にbox_statusに初期状態（貸出可能）で挿入
            self::update_box_status($insert_id, '貸出可能', null);
            return $insert_id;
        }
        return false;
    }

    public static function delete_box($box_id)
    {
        // 外部キー制約の都合上、子テーブルから先に削除する
        \DB::delete('box_transactions')->where('box_id', $box_id)->execute();
        \DB::delete('box_status')->where('box_id', $box_id)->execute();
        
        // 最後にboxesテーブルから削除
        $rows_affected = \DB::delete('boxes')->where('box_id', $box_id)->execute();
        return $rows_affected > 0;
    }

    public static function update_box_label($box_id, $new_label)
    {
        $rows_affected = \DB::update('boxes')
                            ->set(array('label' => $new_label))
                            ->where('box_id', $box_id)
                            ->execute();
        return $rows_affected > 0;
    }
}

    