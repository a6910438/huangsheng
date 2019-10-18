<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class BigConfig extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'big_config';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;
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
        $query->big_price = $data['big_price'];
        $query->status = $data['status'];
        $query->create_time = time();
        return $query->save();
    }

}
