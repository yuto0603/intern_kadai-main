<?php

class Model_Box
{
    public static function get_all_boxes()
    {
        $result = \DB::select('box_id', 'label') 
                      ->from('boxes')            
                      ->execute();    

        return $result->as_array();
    }
}