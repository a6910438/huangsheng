<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class ActiveConfig extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'active_config';

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
        $query->types = $data['type'];
        $query->price = $data['price'];
        $query->active_num = $data['active_num'];
        $query->line_num = $data['line_num'];
        $query->sort = $data['sort'];
        $query->create_time = time();
        return $query->save();
    }


}
