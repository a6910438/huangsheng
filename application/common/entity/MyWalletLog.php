<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class MyWalletLog extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'my_wallet_log';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //获取状态
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '+';
            case 2:
                return '-';
            default:
                return '';
        }
    }
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


}
