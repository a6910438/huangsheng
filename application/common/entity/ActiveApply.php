<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class ActiveApply extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'active_apply';

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
    //获取状态
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '待审核';
            case 2:
                return '同意';
            case 3:
                return '拒绝';
            default:
                return '';
        }
    }
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
