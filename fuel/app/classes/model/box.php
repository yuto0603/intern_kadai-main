<?php

class Model_Box
{
    /**
     * すべてのボックスデータをデータベースから直接SQLで取得する静的メソッド
     * @return array 取得したデータをPHPの連想配列の配列として返します。
     */
    public static function get_all_boxes()
    {
        $result = \DB::select('box_id', 'label') // ★取得したいカラムを明示的に指定
                      ->from('boxes')            // ★テーブル名
                      ->execute();               // ★SQLを実行

        return $result->as_array();
    }
}