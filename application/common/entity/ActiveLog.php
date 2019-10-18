<?php

namespace app\common\entity;

use think\Db;
use think\Model;
use think\Request;

class ActiveLog extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'active_log';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //获取类型
    public function getType($type)
    {
        switch ($type) {
            case 1:
                return '注册激活币';
            case 2:
                return '惩罚A激活币';
            case 3:
                return '惩罚B激活币';
            default:
                return '';
        }
    }
    //添加新数据
    public function addNew($query,$data)
    {
        $query->uid = $data['uid'];
        $query->types = $data['types'];
        $query->types = $data['types'];
        $query->num = $data['num'];
        $query->old = $data['old'];
        $query->new = $data['new'];
        $query->remake = $data['remake'];
        $query->create_time = time();
        return $query->save();
    }


}
