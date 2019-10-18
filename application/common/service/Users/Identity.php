<?php

namespace app\common\service\Users;

use app\common\entity\User;
use think\Cache;
use think\Session;
use think\Db;
use think\Request;
use app\common\entity\Config;
use app\index\model\Publics as PublicModel;
use traits\controller\Jump;
use app\common\entity\MyWallet;

class Identity {

    const SESSION_NAME = 'flow_box_member';
    const CACHE_NAME = 'flow_box_member_%s';
    const CACHE_TTS = 3600;

    public function getUserInfo($userId = 0) {
        $userId = $userId ? $userId : $this->getUserId();
        $userInfo = Cache::remember($this->getCacheName($userId), function () use ($userId) {
            $user = User::where('id', $userId)->find();
            return json_encode([
                'user_id' => $userId,
                'forbidden_type' => $user->forbidden_type,
                'status' => $user->status,
                'nick_name' => $user->nick_name,
                'avatar' => $user->avatar,
                'level' => $user->level,
                'is_certification' => $user->is_certification,
                'certification_fail' => $user->certification_fail,
                'money_address' => $user->money_address,
            ]);
        }, self::CACHE_TTS);

        return json_decode($userInfo);
    }

    public function delCache() {
        Session::delete(self::SESSION_NAME);
    }

    public function saveSession(User $user) {
        Session::set(self::SESSION_NAME, [
            'id' => $user->getId(),
            'email' => $user->email,
        ]);
    }

    public function getUserId() {

        if(empty($_POST['token'])){
            return false;
        }
        $usertoken = trim($_POST['token']);
        $user = User::where('usertoken', $usertoken)->field('id,status,is_active,chat_num,is_delete')->find();

        if(empty($user)){
            return false;
        }
        if($user['status'] == -1){

            return json(['code' => 1, 'msg' => 'Login invalid','url'=>'login']);
        }
        if($user['is_delete'] == 1){
            return false;
        }

//        激活GTC数量
        $num = Db::table('my_wallet')->where('uid',$user['id'])->field('old')->find();
        if($num && $user['is_active'] == 0){
            $switch = Config::getValue('activation_num');

            if($num['old'] >= $switch){

                    $save['is_active'] = 1;
                    $save['status'] = 1;
                    $save['active_time'] = time();
                    $save['update_time'] = time();
                    $res = User::where('id', $user['id'])->update($save);





            }
        }

        $token = $this->getusertoken($user['id']);

        if($usertoken != $token){
            return false;
        }


        //酒乐宝需求   会员设置过微信账号才给升级
        if($user['id'] && $user['chat_num']){
            $entity = new \app\common\entity\MyWallet();
            $entity->levelupgare($user['id']);
        }
        return $user['id'] ? $user['id'] : 0;
    }

    public function getusertoken($id,$log_time = 0){
        $date1 = date('Y-m');
        $time = strtotime("$date1 +30 day");
        $user = User::where('id', $id)->field('password,trad_password,login_ip,login_time')->find();

        if(!$user){
            return false;
        }
        $str = $time+$id;
        $str .= $user['password'];

        if(!$log_time){
            $str .= $user['login_time'];
        }else{
            $str .= $log_time;
        }

//        $request = Request::instance();
//        $ip = $request->ip();
//        $str .= $ip;

        return md5(md5($str));

    }

    public function getUserMobile() {
        $info = Session::get(self::SESSION_NAME);
        return $info['mobile'] ? $info['mobile'] : '';
    }

    public function getCacheName($userId) {
        return sprintf(self::CACHE_NAME, $userId);
    }

    /**
     * 退出登录
     */
    public function logout() {
        $this->delCache($this->getUserId());
        Session::delete(self::SESSION_NAME);
    }

}
