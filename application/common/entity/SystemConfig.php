<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class SystemConfig extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'system_config';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //添加新数据
    public function addNew($query,$data)
    {
        $query->status = $data['status'];
        $query->content = $data['content'];
        $query->create_time = time();
        return $query->save();
    }
    //修改数据
    public function editData($query,$data)
    {
        $query->status = $data['status'];
        $query->content = $data['content'];
        return $query->save();
    }

}
