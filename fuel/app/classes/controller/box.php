<?php

class Controller_Box extends Controller
{
    public function action_index() //一覧（メインページ）
    {

        $boxes = Model_Box::get_all_boxes();
        $box_status_data = array();

        foreach ($boxes as $box) {
            $box_id = $box['box_id'];
            $is_loaned = ($box_id == 1);
            
            $box_status_data[$box_id] = array(
                'status' => $is_loaned ? '貸出中' : '貸出可能',
                'user_name' => $is_loaned ? 'test' : null,
                'current_user_id' => $is_loaned ? 1 : null,
            );
        }

        $view = View::forge('box/index', array(
            'boxes' => $boxes,
            'box_status_data' => $box_status_data,
            'title' => '備品一覧',
        ));
        
        return Response::forge($view);
    }

    public function action_loan($id = null) //貸出
    {
        if ($id === null)
        {
            return $this->action_404();
        }

        // 仮のボックスデータ
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

    
    public function action_return($id = null)//返却
    {
        if ($id === null)
        {
            return $this->action_404();
        }

        $box_data = array( //仮
            'box_id' => $id,
            'label'  => 'B-' . $id,
            'type'   => 'モニター',
        );
        $loaned_user_name = 'test'; //仮

        $view = View::forge('box/return', array(
            'title'            => '備品の返却',
            'item_id'          => $box_data['box_id'],
            'item_label'       => $box_data['label'],
            'item_type'        => $box_data['type'],
            'loaned_user_name' => $loaned_user_name,
        ));
        
        return \Response::forge($view);
    }

    public function action_manage()//備品管理
    {
        $boxes = Model_Box::get_all_boxes();
        
        $items_by_type = array();

        foreach ($boxes as $box) { //仮
            $item_id = $box['box_id'];
            $item_label = $box['label'];
            
            $item_type = 'モニター';
            if ($item_id >= 16 && $item_id <= 20) { // 例
            } elseif ($item_id > 20) {
                $item_type = 'その他備品'; 
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