<?php

class Controller_Box extends Controller
{
   
    public function action_index()
    {
        
        $boxes = Model_Box::get_all_boxes(); 
        $view = View::forge('box/index', array(
            'boxes' => $boxes,
            'title' => 'ボックス一覧', 
        ));
        
        return Response::forge($view);
    }

       public function action_loan($id = null)
    {
        if ($id === null)
        {
            return $this->action_404();
        }

        $box_data = array(
            'box_id' => $id,
            'label'  => 'B-' . $id,
            'type'   => 'モニター', 
        );
        
        $view = View::forge('box/loan', array(
            'title'      => '備品の貸出',
            'item_id'    => $box_data['box_id'],
            'item_label' => $box_data['label'],
            'item_type'  => $box_data['type'],
        ));
        
        return \Response::forge($view);
    }

    
    public function action_return($id = null)
    {
        if ($id === null)
        {
            return $this->action_404();
        }

        $box_data = array(
            'box_id' => $id,
            'label'  => 'B-' . $id, 
            'type'   => 'モニター',
        );
        $loaned_user_name = 'test'; 

        $view = View::forge('box/return', array(
            'title'            => '備品の返却',
            'item_id'          => $box_data['box_id'],
            'item_label'       => $box_data['label'],
            'item_type'        => $box_data['type'],
            'loaned_user_name' => $loaned_user_name,
        ));
        
        return \Response::forge($view);
    }


    /**
     * 備品管理ページを表示するアクション
     * URL: /box/manage
     */
    public function action_manage()
    {
        $boxes = Model_Box::get_all_boxes(); 
        
        $items_by_type = array();

        foreach ($boxes as $box) {
            $item_id = $box['box_id'];
            $item_label = $box['label'];
            
            $item_type = 'モニター'; 
            if ($item_id >= 16 && $item_id <= 20) { 
                $item_type = 'Type-Cコード';
            }

            if (!isset($items_by_type[$item_type])) {
                $items_by_type[$item_type] = array();
            }
            $items_by_type[$item_type][] = array(
                'box_id' => $item_id,
                'label'  => $item_label,
                'type'   => $item_type,
            );
        }
        
        $view = View::forge('box/manage', array(
            'title'         => '備品管理',
            'items_by_type' => $items_by_type, 
        ));
        
        return \Response::forge($view);
    }

    public function action_404()
    {
        return \Response::forge(\View::forge('404'), 404);
    }

}