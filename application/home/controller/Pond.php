<?php 

namespace app\home\controller;

use think\Controller;

class Pond extends Controller {

    public function index() {
    	$this->assign('title', 'Your Balance');
    	return view();
    }

    public function detail() {
        $this->assign('title', '订单详情');
        return $this->fetch('member/detail');;
    }

}
