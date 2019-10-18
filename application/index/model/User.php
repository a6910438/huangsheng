<?php

namespace app\index\model;

use app\common\entity\Config;
use app\common\entity\Dynamic_Log;
use app\common\entity\DynamicConfig;
use app\common\entity\Log;
use app\common\entity\MyWallet;
use app\common\entity\MyIntegral;
use app\common\entity\SafeAnswer;
use app\common\entity\StoreLog;
use app\common\entity\SystemConfig;
use app\common\entity\Team;
use app\common\entity\UserInviteCode;
use app\common\entity\UserProduct;
use app\common\model\GC;
use app\common\service\Users\Identity;
use app\common\service\Users\Service;
use think\Db;
use think\Request;
use think\Session;

class User {

    public function checkRegisterOpen() {
        $registerOpen = SystemConfig::where('status',1)->value('content');
        if ($registerOpen) {
            return $registerOpen;
        }
        return false;
    }

     public function checkIp() {
         $ipTotal = Config::getValue('register_ip');
         $request = Request::instance();
         $ip = $request->ip();
         $total = \app\common\entity\User::where('register_ip', $ip)->count();
         if ($ipTotal > $total) {
             return true;
         }
         return false;
     }
    public function doRegister($data) {


        $entity = new \app\common\entity\User();
        $service = new Service();
        $result = UserInviteCode::getUserIdByCode($data['invite_code']);

        $parentInfo = \app\common\entity\User::where('id',$result)->find();

        $parentId = $result?$result:0;
        $entity->nick_name = $data['nick_name'];
        $entity->mobile = $data['mobile'];
        $entity->password = $service->getPassword($data['password']);
//        $entity->trad_password = $service->getPassword($data['trad_password']);
        $entity->register_time = time();
        $request = Request::instance();
        $entity->register_ip = $request->ip();
        $entity->status = \app\common\entity\User::STATUS_DEFAULT;
        $entity->is_certification = \app\common\entity\User::AUTH_ERROR;
        $entity->pid = $parentId;
        $entity->last_store_time = time();
        $entity->province = empty($data['province'])?'':$data['province'];
        $entity->city = empty($data['city'])?'':$data['city'];
        $entity->service = empty($data['service'])?'':$data['service'];


        if ($entity->save()) {
            //增加邀请人数
            if($parentInfo){
                \app\common\entity\User::where('id', $parentId)->setInc('invite_count');
//                $info = $this->getTeamLeader($entity->id);
//                Team::where('leader',$info)->setInc('man_count');
//                $team_id = Team::where('leader',$info)->find();
//                \app\common\entity\User::where('id',$entity->id)->setField('tid',$team_id['id']);
            }


            $wallet_data = [
              'uid' => $entity->id,
              'update_time' => time(),
            ];
            $walletid = MyWallet::insertGetId($wallet_data);

            $integral_data = [
                'uid' => $entity->id,
                'update_time' => time(),
            ];
            $integral_id = MyIntegral::insertGetId($integral_data);
           //创建钱包地址
           $gc = new GC;
           $user_update = [];
           $gc_address = $gc->newaccount();
           if(empty($gc_address)){
               return false;
           }

           $entity->where('id',$entity->id)->update(['money_address'=>$walletid,'gc_address'=>$gc_address]);
           $inviteCode = new UserInviteCode();
           $inviteCode->saveCode($entity->id);

            \app\common\entity\User::where('id', $parentId)->setInc('invite_count');

            return true;
        }

        return false;
    }
    //获取团队长ID
    public function getTeamLeader($uid)
    {
        $entry = new \app\common\entity\User();
        $info = $entry->getParents($uid);
        return $info;
    }

    //动态收益
    public function getDynamic($uid,$store_log_id)
    {
        $user = new \app\common\entity\User();
        $store_info = StoreLog::where('id',$store_log_id)->find();
        $open_time = $store_info['you_end_time'];

        //一代奖励
        $first_id = $user->getParentsId($uid ,1);
        //获取有效直推人数
        $invite_count = $user->where('pid',$first_id)->where('status',1)->count('id');
        //计算奖励金额
        $first_store_info = DynamicConfig::where('id',1)->find();
        if($invite_count >= $first_store_info['direct_push']){
            $total1 = $store_info['num'] * $first_store_info['first'] * 0.01;
        }

        //二代奖励
        $second_id = $user->getParentsId($uid ,2);
        //获取下一代详细
        $second_child = $user->field('id,status')->where('pid',$second_id)->select();
        //获取有效直推人数
        $second_invite_count = $user->where('pid',$second_id)->where('status',1)->count('id');
        //获取代内有效团队人数
        $second_num = $user->getChildsNum($second_child,2);
        $second_store_info = DynamicConfig::where('id',2)->find();
        if($second_invite_count >= $second_store_info['direct_push'] && $second_num >= $second_store_info['team_num'] ){
            $total2 = $store_info['num'] * $second_store_info['second'] * 0.01;
        }

        //三代奖励
        $third_id = $user->getParentsId($uid ,3);
        //获取下一代详细
        $third_child = $user->field('id,status')->where('pid',$third_id)->select();
        //获取有效直推人数
        $third_invite_count = $user->where('pid',$second_id)->where('status',1)->count('id');
        //获取代内有效团队人数
        $third_num = $user->getChildsNum($third_child,3);
        $third_store_info = DynamicConfig::where('id',2)->find();

        if($third_invite_count >= $third_store_info['direct_push'] && $third_num >= $third_store_info['team_num'] ){
            $total3 = $store_info['num'] * $third_store_info['third'] * 0.01;
        }


        if(isset($total1)&&$total1 > 0){
            $data1 = [
                'uid' => $user->getParentsId($uid ,1),
                'status' => 1,
                'store_id' => $store_log_id,
                'form_user' => $uid,
                'form_level' => 1,
                'total' => $total1,
                'open_time' => $open_time,
            ];
            $entity = new \app\common\entity\MyWallet();
            $entity->where('uid',$data1['uid'])->setInc('future',$data1['total']);
            (new Dynamic_Log())->insert($data1);

        }
        if(isset($total2)&&$total2 > 0){
            $data2 = [
                'uid' => $user->getParentsId($uid ,2),
                'status' => 1,
                'store_id' => $store_log_id,
                'form_user' => $uid,
                'form_level' => 2,
                'total' => $total2,
                'open_time' => $open_time,
            ];
            $entity = new \app\common\entity\MyWallet();
            $entity->where('uid',$data2['uid'])->setInc('future',$data2['total']);
            (new Dynamic_Log())->insert($data2);
        }
        if(isset($total3)&&$total3 > 0){
            $data3 = [
                'uid' => $user->getParentsId($uid ,3),
                'status' => 1,
                'store_id' => $store_log_id,
                'form_user' => $uid,
                'form_level' => 3,
                'total' => $total3,
                'open_time' => $open_time,
            ];
            $entity = new \app\common\entity\MyWallet();
            $entity->where('uid',$data3['uid'])->setInc('future',$data3['total']);
            (new Dynamic_Log())->insert($data3);
        }

    }

    /**
     * 得到用户的详细信息
     */
    public function getInfo($id) {
        return \app\common\entity\User::where('id', $id)->find();
    }

    /**
     * 银行卡号 微信号 支付宝账号 唯一
     */
    public function checkMsg($type, $account, $id = '') {
        return \app\common\entity\User::where("$type", $account)->where('id', '<>', $id)->find();
    }

    public function doLogin($mobile, $password) {


        $user = \app\common\entity\User::where('mobile', $mobile)->find();

        if (!$user) {
            return '账号或者密码错误';
        }

        $model = new \app\common\service\Users\Service();

        if (!$model->checkPassword($password, $user)) {
            return '账号或者密码错误';
        }


        \app\common\entity\User::where('mobile', $mobile)->update(['login_time'=>time()]);

        return true;
    }

}
