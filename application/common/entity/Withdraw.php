<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class Withdraw extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'withdraw';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //获取类型
    public function getType($type)
    {
        switch ($type) {
            case 1:
                return '固定收入';
            case 2:
                return '当前余额';
            default:
                return '';
        }
    }
    //获取状态
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '排队中';
            case 2:
                return '部分取款';
            case 3:
                return '完全取款';
            default:
                return '';
        }
    }
    //获取类型
    public function getAllType()
    {
        return [
            '1' => '固定收入',
            '2' => '当前余额',
        ];
    }
    //获取全部状态
    public function getAllStatus()
    {
        return [
            '1' => '排队中',
            '2' => '部分取款',
            '3' => '完全取款',
        ];
    }
    //添加新数据
    public function addNew($query,$data)
    {
        $query->uid = $data['uid'];
        $query->total = $data['total'];
        $query->overplus = $data['overplus'];
        $query->types = $data['types'];
        $query->status = $data['status'];
        $query->create_time = time();
        $res = $query->save();
        if($res){
            return $query->id;
        }
    }



}
