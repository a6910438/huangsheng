<?php 

namespace app\home\controller;

use think\Controller;

class Index extends Controller {

    /**
     * 主页
     */
    public function index() {
    	$this->assign('title', '大富豪');
    	return view();
    }

}
