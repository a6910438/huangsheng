<?php 

namespace app\home\controller;

use think\Controller;

class Member extends Controller {

    public function center() {
    	$this->assign('title', '个人中心');
    	return view();
    }

    public function setting() {
    	$this->assign('title', '设置');
    	return view();
    }

    public function bait() {
    	$this->assign('title', 'GTC');
    	return view();
    }

    public function integral() {
    	$this->assign('title', 'GTC');
    	return view();
    }

    public function culture() {
    	$this->assign('title', '装修收益');
    	return view();
    }

    public function extension() {
    	$this->assign('title', '推广收益');
    	return view();
    }

    public function adoption() {
    	$this->assign('title', '领取记录');
    	return view();
    }

    public function transfer() {
    	$this->assign('title', '转让记录');
    	return view();
    }

    public function appointment() {
    	$this->assign('title', '预约记录');
    	return view();
    }

    public function gcLog() {
        $this->assign('title', 'GC账单');
    	return view();
    }

    public function exchange() {
    	$this->assign('title', '闪兑');
    	return view();
    }

    public function withdraw() {
    	$this->assign('title', 'GC提币');
    	return view();
    }

    public function withdrawLog() {
        $this->assign('title', 'GC提币记录');
    	return view();
    }

    public function teamProfit() {
    	$this->assign('title', '团队收益');
    	return view();
    }

    public function inviteFriend() {
    	$this->assign('title', '邀请好友');
    	return view();
    }

    public function collection() {
    	$this->assign('title', '收款账户');
    	return view();
    }

    public function collectionAdd() {
        $this->assign('title', '添加收款账户');
        return view();
    }

    public function noticeList() {
        $this->assign('title', '公告');
        return view();
    }

    public function customer() {
    	$this->assign('title', '客服中心');
    	return view();
    }

    public function revisePw() {
        $this->assign('title', '修改密码');
        return view();
    }

    public function qrcode() {
        $this->assign('title', '我的二维码');
        return view();
    }

    public function modifyName() {
        $this->assign('title', '修改昵称');
        return view();
    }

    public function turnOut() {
        $this->assign('title', 'GTC转出');
        return view();
    }

    public function turnOutIntegral() {
        $this->assign('title', '积分转出');
        return view();
    }

    public function article() {
        $this->assign('title', '文章详情');
        return view();
    }

    public function detail() {
        $this->assign('title', '订单详情');
        return view();
    }

    public function about() {
        $this->assign('title', '关于我们');
        return view();
    }

    public function wechat() {
        $this->assign('title', '绑定微信');
        return view();
    }
    public function verifyid() {
        $this->assign('title', '实名认证');
        return view();
    }

    public function exchange_profit(){
        $this->assign('title', '推广收益/兑换GTC');
        return view();
    }

    public function exchange_team_profit(){
        $this->assign('title', '团队收益/兑换GTC');
        return view();
    }
}
