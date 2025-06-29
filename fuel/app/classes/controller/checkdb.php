<?php
class Controller_Checkdb extends \Controller
{
    public function action_index()
    {
        $result = DB::select()
            ->from('boxes')
            ->execute()
            ->current();
        var_dump($result);
        exit;
    }
}