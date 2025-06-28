<?php

class Model_User
{
    protected static $_table_name = 'users';

    protected static $_properties = array(
        'user_id',
        'user_name',        
    );

    public static function get_user_id_by_name($user_name)
    {
        $user = static::query()
                    ->where('user_name', $user_name)
                    ->get_one();
        return $user ? $user->user_id : null;
    }

     public static function get_or_create_user($user_name)
    {
        $user_id = self::get_user_id_by_name($user_name);

        if ($user_id === null) {
            // ユーザーが存在しない場合、新規挿入
            list($insert_id, $rows_affected) = \DB::insert('users')
                                                 ->columns(array('user_name'))
                                                 ->values(array($user_name))
                                                 ->execute();
            return $insert_id;
        }
        return $user_id;
    }
}