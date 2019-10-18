<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class Linelist extends Model {

    protected $createTime = 'create_time';

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'line_list';
    protected $auto = ['create_time'];

    public function findCountMoney($uid)
    {
        $detail = $this
            ->where('uid',$uid)
            ->where('status','<>',3)
            ->sum('num');
        return $detail;
    }
    public function findNum($uid)
    {
        $detail = $this
            ->where('uid',$uid)
            ->where('status','<>',3)
            ->count();
        return $detail;
    }
    //获取类型
    public function getType($type)
    {
        switch ($type) {
            case 1:
                return '接受多个订单';
            case 2:
                return '拒绝多个订单';
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
            case 4:
                return '已退回';
            case 5:
                return '正在匹配';
            default:
                return '';
        }
    }
    //获取所有状态
    public function getAllCate()
    {
        return [
            '1' => '排队中',
            '2' => '部分取款',
            '3' => '完全取款',
            '4' => '已退回',
        ];
    }
    //获取所有类型
    public function getAllType()
    {
        return [
            '1' => '接受多个订单',
            '2' => '拒绝多个订单',
        ];
    }
    //生成订单号
    protected function setOrderNumber($memberId)
    {
        return date('Ymd') . $memberId . date('His');
    }
    //添加新数据
    public function addNew($query,$data)
    {
        $query->order_num = $this->setOrderNumber($data['uid']);
        $query->uid = $data['uid'];
        $query->num = $data['num'];
        $query->overmoney = $data['overmoney'];
        $query->types = $data['types'];
        $query->status = $data['status'];
        $query->create_time =time();
        return $query->save();
    }


}
