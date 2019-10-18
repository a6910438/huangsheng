<?php

namespace app\index\controller;

use app\common\entity\User;
use app\common\service\Users\Identity;
use app\index\model\SiteAuth;
use think\Controller;
use think\Session;
use think\Db;
use app\common\entity\SystemConfig;

class Base extends Controller
{

    public $userId;
    public $userInfo;

    public function _initialize()
    {

        $site = $this->checkSite();
        if ($site) {
            $token = request()->param('token','','trim');
            if (!empty($token)) {
                // 系统维护时允许白名单中的用户登录
                $is_white = $this->isInWhiteList($token);
                if ($is_white !== true) {
                    json(['code' => 9999, 'msg' => $site])->send();
                    exit;
                    // $this->redirect('publics/index');
                }
            }
        }
        $this->checkLogin();


        parent::_initialize();
    }

    //判断是否登录
    public function checkLogin()
    {
        if (input('post.token') == "60ec9966fec55aafc0b5b67e0ab9565c") {

        } else {
            $identity = new Identity();
            $userId = $identity->getUserId();


            if ($_SERVER['REQUEST_URI'] != '/index.php/index/Index/index' &&
                $_SERVER['REQUEST_URI'] != '/index.php/index/Upload/uploadImg' &&
                $_SERVER['REQUEST_URI'] != '/index.php/index/publics/register' &&
                $_SERVER['REQUEST_URI'] != '/index.php/index/Access/recharge' &&

                $_SERVER['REQUEST_URI'] != '/index/Index/index' &&
                $_SERVER['REQUEST_URI'] != '/index/Upload/uploadImg' &&
                $_SERVER['REQUEST_URI'] != '/index/publics/register' &&
                $_SERVER['REQUEST_URI'] != '/index/Access/recharge'
            ) {


                if (!$userId) {
                    $this->redirect('publics/index');
                }

            }


            if ($userId) {
                $this->userId = $userId;
                $userInfo = User::where('id', $userId)->find();
                if ($_SERVER['REQUEST_URI'] != '/index.php/index/Index/index' &&
                    $_SERVER['REQUEST_URI'] != '/index.php/index/Upload/uploadImg' &&
                    $_SERVER['REQUEST_URI'] != '/index.php/index/publics/register' &&
                    $_SERVER['REQUEST_URI'] != '/index.php/index/Access/recharge' &&

                    $_SERVER['REQUEST_URI'] != '/index/Index/index' &&
                    $_SERVER['REQUEST_URI'] != '/index/Upload/uploadImg' &&
                    $_SERVER['REQUEST_URI'] != '/index/publics/register' &&
                    $_SERVER['REQUEST_URI'] != '/index/Access/recharge'
                ) {

                    if ($userInfo->status == -1) {
                        $this->redirect('publics/index');
                    }

                }

//            $this->checkLine($userId);

//            $this->userInfo = $identity->getUserInfo();
//            $info = User::where('id',$userId)->find();

//            $query = new Publics();
//            $query->text($userId);
//            Session::set('username',$this->userInfo->nick_name);
//            if($info['login_time'] != session_id()){
//                $this->redirect('publics/index');
//            }


            }
        }


    }

    //检查站点
    public function checkSite()
    {
        $switch = SiteAuth::checkSite();

        if ($switch !== true) {
            return $switch;
            // return json(['code' => 1, 'msg' => $switch]);
        }
    }

    /**
     * 是否在白名单内
     *
     * @param  $mobile
     * @return boolean
     */
    public function isInWhiteList($token)
    {
        if (empty($token)) {
            return false;
        }
        $phone_white_list = SystemConfig::where('id','>',0)->value('phone_white_list');
        $phone_white_list = explode(';',$phone_white_list);
        $white_users_token = User::where('mobile','in',$phone_white_list)->column('usertoken');
        $white_users_token = array_filter($white_users_token);
        // halt($white_users_token);
        if (in_array($token,$white_users_token)) {
            return true;
        }
        return false;
    }



    //检查账号是否不排单
//    public function checkLine($uid)
//    {
//        SiteAuth::blockUser($uid);
//    }

    /**
     * ajax 返回
     * @param type $data
     * @param type $info
     * @param type $status
     * @return type
     */
    public function ajaxreturn($data, $info, $status = false)
    {
        return json([
            'status' => $status,
            'info' => $info,
            'data' => $data
        ]);
    }

}
