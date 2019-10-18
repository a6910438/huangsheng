<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class CloseUserConfig extends Model {

    protected $createTime = 'create_time';

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'close_user_config';
    protected $auto = ['create_time'];

    //添加新数据
    public function addNew($query,$data)
    {
        $query->type = $data['type'];
        $query->value = $data['value'];
        $query->create_time = time();
        return $query->save();
    }

}
