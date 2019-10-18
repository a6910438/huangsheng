<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class RechargeLog extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'recharge_log';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //获取类型
    public function getType($type)
    {
        switch ($type) {
            case 1:
                return '平台操作';
            case 2:
                return '品酒';
            case 3:
                return '用户转让';
            case 4:
                return '品酒收益';

            default:
                return '';
        }
    }
    //获取所有类型
    public function getAllType()
    {
        return [
            1 => '平台操作',
            2 => '品酒',
            3 => '用户转让',
            3 => '品酒收益',
        ];
    }


}
