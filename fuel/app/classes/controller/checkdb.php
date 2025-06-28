<?php
class Controller_Checkdb extends \Controller
{
    public function action_index()
    {
        $result = DB::select()
            ->from('box_transactions')
            ->execute()
            ->current();
        var_dump($result);
        exit;
    }
}