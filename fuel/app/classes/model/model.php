<?php
namespace Model;

class MonitorBox extends \Orm\Model
{
    protected static $_table_name = 'monitor_box'; // テーブル名を指定

    protected static $_primary_key = array('id'); // 主キーを指定

    //カラムの定義
    protected static $_properties = array(
        'id',
        'label' => array(
            'data_type' => 'varchar',
            'label' => 'モニターラベル',
            'validation' => array('required', 'max_length' => 20),
            'form' => array('type' => 'text'),
        ),
        'status' => array(
            'data_type' => 'enum',
            'label' => '現在の状態',
            'options' => array('貸出中' => '貸出中', '空き' => '空き'),
            'default' => '空き',
            'validation' => array('required'),
            'form' => array('type' => 'radio'),
        ),
        'current_user' => array(
            'data_type' => 'varchar',
            'label' => '借りた人の名前',
            'validation' => array('max_length' => 50),
            'null' => true,
            'form' => array('type' => 'text'),
        ),
        'updated_at' => array(
            'data_type' => 'datetime',
            'label' => '最終更新日時',
            'null' => true,
            'form' => array('type' => false),
        ),
    );

    // タイムスタンプの自動更新設定（FuelPHPのOrmはcreated_atとupdated_atを自動で管理できる）
    // 今回の要件ではupdated_atのみなので、必要な設定を行うか、手動で管理

    protected static $_observers = array(
        'Orm\\Observer_UpdatedAt' => array(
            'events' => array('before_save'),
            'mysql_timestamp' => false, // datetime型の場合
            'property' => 'updated_at',
        ),
    );

    // リレーションの定義（例：monitor_boxとmonitor_logの1対多）
    
    protected static $_has_many = array(
        'monitor_logs' => array(
            'key_from' => 'id',
            'model_to' => 'Model\\MonitorLog', // MonitorLogモデルも作成する必要がある
            'key_to' => 'monitor_id',
            'cascade_save' => true,
            'cascade_delete' => false,
        )
    );
}