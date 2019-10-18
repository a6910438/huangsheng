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

class Setup{

    /**
     * @param $id
     * @return array|\PDOStatement|string|\think\Model
     */
    public function user_info($id){
        $map['u.id'] = $id;
       return Db::table('user')
           ->alias('u')
           ->join('user_invite_code uic','u.id = uic.user_id')
           ->join('my_wallet mw','u.id = mw.uid')
           ->join('my_integral mi','u.id = mi.uid')
           ->where($map)
           ->field('u.pid,u.nick_name,u.status,u.is_verify,u.chat_num,u.id,u.lv,u.gc,u.gc_address,u.is_active,u.avatar,u.mobile,uic.invite_code,mw.old old_bait,mw.now now_bait,mi.now now_integral,u.now_profit,u.prohibit_integral,u.team_integral,u.now_prohibit_integral,u.now_team_integral')
           ->find();
    }

}