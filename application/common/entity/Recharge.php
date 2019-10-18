<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class Recharge extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'recharge';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //获取状态
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '待审核';
            case 2:
                return '通过';
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
        $query->money_address = $data['money_address'];
        $query->nums = $data['nums'];
        $query->create_time = time();
        $res = $query->save();
        if($res){
            return $query->getLastInsID();
        }
    }


}
