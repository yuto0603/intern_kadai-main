<?php

class Model_User extends \Orm\Model
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
}