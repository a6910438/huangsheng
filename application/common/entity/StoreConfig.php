<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class StoreConfig extends Model {

    protected $createTime = 'create_time';

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'store_config';
    protected $auto = ['create_time'];

    //获取状态
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '启用';
            case 2:
                return '禁用';
            default:
                return '';
        }
    }
    //添加新数据
    public function addNew($query,$data)
    {
        $query->num = $data['num'];
        $query->price = $data['price'];
        $query->rate = $data['rate'];
        $query->status = $data['status'];
        $query->create_time = time();
        return $query->save();
    }

}
