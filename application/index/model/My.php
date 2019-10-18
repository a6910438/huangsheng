<?php

namespace app\index\model;

use app\common\entity\Config;
use app\common\entity\Dynamic_Log;
use app\common\entity\DynamicConfig;
use app\common\entity\Log;
use app\common\entity\MyWallet;
use app\common\entity\SafeAnswer;
use app\common\entity\StoreLog;
use app\common\entity\SystemConfig;
use app\common\entity\Team;
use app\common\entity\UserInviteCode;
use app\common\entity\UserProduct;
use app\common\service\Users\Identity;
use app\common\service\Users\Service;
use think\Db;
use think\Request;
use think\Session;

class My{

    /**
     * 获得GTC数量
     * @param $id
     * @return array|\PDOStatement|string|\think\Model
     */
    public function bait_num($id){
        $map['uid'] = $id;
       return Db::table('my_wallet')->where($map)->field('now')->find();
    }


    /**
     * GTC状态
     * @param $key
     * @return string
     */
    public function get_type_name($key){
        switch ($key) {
            case 0:
                return '平台';
            case 1:
                return '平台';
            case 2:
                return '装修消耗';
            case 3:
                return '已预约';
            case 4:
                return '领取失败(返回预约GTC)';
            case 5:
                return '互转';
            case 6:
                return '即抢';
            case 7:
                return '房产收益';
            case 8:
                return '兑换';
            case 9:
                return '房产消耗';
            case 10:
                return '收益兑换';
            default:
                return '其它';
        }
    }

    /**
     * 积分状态
     * @param $key
     * @return string
     */
    public function get_intagral_log_type_name($key){
        switch ($key) {
            case 0:
                return '平台';
            case 1:
                return '平台';
            case 2:
                return '装修消耗';
            case 3:
                return '已预约';
            case 4:
                return '领取失败(返回预约积分)';
            case 5:
                return '互转';
            case 6:
                return '即抢';
            case 7:
                return '房产收益';
            case 8:
                return '兑换';
            case 9:
                return '房产消耗';
            default:
                return '其它';
        }
    }

}
