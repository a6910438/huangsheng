<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class SafeAnswer extends Model {

    protected $createTime = 'create_time';

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'safe_answer';
    protected $auto = ['create_time'];


    //添加新数据
    public function addNew($query,$data)
    {

        $query->title = $data['title'];
        $query->sort = $data['sort'];
        $query->status = $data['status'];
        $query->create_time = time();
        return $query->save();
    }

}
