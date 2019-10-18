<?php 

namespace app\home\controller;

use think\Controller;
/* websocket */
use GatewayClient\Gateway;

class Publics extends Controller {

    public function login() {
        $this->assign('title', '登录');
    	return view();
    }

    public function forget() {
        $this->assign('title', '忘记密码');
    	return view();
    }

    public function register() {
        $this->assign('title', '免费注册');
    	return view();
    }

    public function appeal() {
        $this->assign('title', '账号申诉');
    	return view();
    }

    public function privacy_policy() {
        $this->assign('title', '隐私协议');
    	return view();
    }

    public function log_or_reg() {
        $this->assign('title', '登录与注册');
    	return view();
    }

    public function test()
    {
        
    }

    public function websocketTest() {
        $u_id = input('u_id', '999888', 'trim');
        $h_name = input('h_name', '商品房', 'trim');
        // 向所有客户端推送抢购信息
        $push_data = ['恭喜玩家ID: '.$u_id.' 成功抢购 '.$h_name.' 一套！'];
        GateWay::sendToAll(json_encode($push_data));
    }
    
}
