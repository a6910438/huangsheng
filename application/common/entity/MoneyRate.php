<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class MoneyRate extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'money_rate';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;
    //获取状态
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '启用';
            case 2:
                return '禁用';
            default:
                return '';
        }
    }
    //获取类型
    public function getTypes($types)
    {
        switch ($types) {
            case 1:
                return '取固定额度';
            case 2:
                return '取当前余额';
            case 3:
                return '充值排单币';
            default:
                return '';
        }
    }
    //获取所有类型
    public function getAllTypes()
    {
        return [
              1 => '固定额度',
              2 => '当前余额',
              3 => '充值排单币',
        ];
    }
    //添加新数据
    public function addNew($query,$data)
    {
        $query->types = $data['types'];
        $query->num = $data['num'];
        $query->status = $data['status'];
        $query->create_time = time();
        return $query->save();
    }

}
