<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class OvertimeConfig extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'overtime_config';

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
    //获取所有类型
    public function getAllTypes()
    {
        return [
            1 => '打款时间',
            2 => '确认收款时间',
        ];
    }
    //添加新数据
    public function addNew($query,$data)
    {
        $query->time = $data['time'];
        $query->types = $data['types'];
        $query->status = $data['status'];
        $query->create_time = time();
        return $query->save();
    }

}
