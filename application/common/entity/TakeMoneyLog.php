<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class TakeMoneyLog extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'take_money_log';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //获取类型
    public function getType($type)
    {
        switch ($type) {
            case 1:
                return '未来钱包';
            case 2:
                return '现在钱包';
            case 3:
                return '过去钱包';
            case 4:
                return '排单币';
            default:
                return '';
        }
    }
    //获取类型
    public function getAllType()
    {
        return [
            1 => '未来钱包',
            2 => '现在钱包',
            3 => '过去钱包',
            4 => '排单币',
        ];
    }


}
