<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class Match extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'match';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //获取状态
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '打款中';
            case 2:
                return '已完成';
            case 3:
                return '超时未打款';
            case 4:
                return '超时未收款';
            default:
                return '';
        }
    }
    //获取所有状态
    public function getAllStatus()
    {
        return [
            1 => '打款中',
            2 => '已完成',
            3 => '超时未打款',
        ];
    }
    //添加新数据
    public function addNew($query,$data)
    {
        $query->take_id = $data['take_id'];
        $query->store_id = $data['store_id'];
        $query->prove = $data['prove'];
        $query->status = 1;
        $query->money = $data['money'];
        $query->create_time = time();
        $res = $query->save();
        if($res){
            return $query->id;
        }
    }
    //获取用户详情
    public function getUserInfo($uid)
    {
        return User::where('id',$uid)->find();
    }


}
