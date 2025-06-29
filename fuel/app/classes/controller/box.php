<?php

class Controller_Box extends Controller
{
   
    public function action_index()
    {
        $boxes = Model_Box::get_all_boxes();

        $view = View::forge('box/index', array(
            'boxes' => $boxes,
            'title' => '備品一覧',
        ));
        
        return Response::forge($view);
    }

    /**
     * 貸出ページを表示するアクション (GET)
     * URL: /box/loan/{box_id}
     * @param int $id ボックスのID
     */
    public function action_loan($id = null)
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

    /**
     * 返却ページを表示するアクション (GET)
     * URL: /box/return/{box_id}
     * @param int $id ボックスのID
     */
    public function action_return($id = null)
    {
        if ($id === null)
        {
            return $this->action_404();
        }

        // 仮のボックスデータとユーザー名
        $box_data = array(
            'box_id' => $id,
            'label'  => 'B-' . $id,
            'type'   => 'モニター',
        );
        $loaned_user_name = 'test'; // 仮の借りているユーザー名

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
     * 備品管理ページを表示するアクション (GET)
     * URL: /box/manage
     */
    public function action_manage()
    {
        $boxes = Model_Box::get_all_boxes();
        
        $items_by_type = array();

        // 備品の種類を仮で判定
        foreach ($boxes as $box) {
            $item_id = $box['box_id'];
            $item_label = $box['label'];
            
            $item_type = 'モニター';
            if ($item_id >= 16 && $item_id <= 20) { // 例としてB-16からB-20をType-Cコードとする
                $item_type = 'Type-Cコード';
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


    /**
     * 404 Not Found のアクション
     * ... (変更なし) ...
     */
    public function action_404()
    {
        return \Response::forge(\View::forge('404'), 404);
    }
}