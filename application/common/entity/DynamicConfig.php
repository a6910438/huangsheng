<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class DynamicConfig extends Model {

    protected $createTime = 'create_time';

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'dynamic_config';
    protected $auto = ['create_time'];
    //添加新数据
    public function addNew($query,$data)
    {
        $query->direct_push = $data['direct_push'];
        $query->team_num = $data['team_num'];
        $query->first = $data['first'];
        $query->second = $data['second'];
        $query->third = $data['third'];
        $query->create_time = time();
        return $query->save();
    }


}
