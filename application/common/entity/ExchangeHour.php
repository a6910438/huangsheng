<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class ExchangeHour extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'exchangehour';



    //添加新数据
    public function addNew($query,$data)
    {
        $query->star = $data['star'];
        $query->end = $data['end'];
        return $query->save();
    }
    //修改数据
    public function editData($query,$data)
    {
        $query->star = $data['star'];
        $query->end = $data['end'];
        return $query->save();
    }

}
