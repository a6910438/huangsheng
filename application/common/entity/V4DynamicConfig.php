<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class V4DynamicConfig extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'v4_dynamic_config';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;


    //添加新数据
    public function addNew($query ,$data)
    {
        $query->uid = $data['uid'];
        $query->types = $data['types'];
        $query->price = $data['price'];
        $query->active_num = $data['active_num'];
        $query->line_num = $data['line_num'];
        $query->trade_address = $data['trade_address'];
        $query->pic = $data['pic'];
        $query->create_time = time();
        return $query->save();
    }


}
