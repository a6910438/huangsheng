<?php

namespace app\index\controller;

use app\common\entity\LegalConfig;
use app\common\entity\LegalList;
use app\common\entity\LegalWallet;
use app\common\entity\MarketPrice;
use app\index\model\SiteAuth;
use app\common\entity\User;
use app\common\service\Market\Auth;
use app\index\model\SendCode;
use think\Request;

class Legal extends Base {

    public function initialize() {
        $authModel = new Auth();
        $authModel->identity();
        parent::initialize();
    }

    public function buycoin(Request $request){
        $list = LegalList::where('id', $request->get('id'))->where('status',LegalList::STATUS_DEFAULT)->find();
        
        if (!$list) {
            (new SiteAuth())->alert('订单不存在或已被交易',url('legal/index'));
        }
        
        $member = User::where('id', $list->user_id)->find();
        
        return $this->fetch('buycoin', [
                'price'=>sprintf("%1\$.2f",$list['price']),
                'number'=>sprintf("%1\$.4f",$list['number']),
                'totalprice'=>sprintf("%1\$.2f",$list['totalprice']),
                'nickname'=>$member['nick_name'],
                'order_id'=>$list['id']
        ]);
    }

     public function sellcoin(Request $request){

        $list = LegalList::where('id', $request->get('id'))->where('status',LegalList::STATUS_DEFAULT)->find();
        
        if($list){
           $member = User::where('id', $list->user_id)->find();
        }
           
        return $this->fetch('sellcoin', [
                'price'=>sprintf("%1\$.2f",$list->price),
                'nickname'=>$member->nick_name
        ]);
    }

    /**
     * [bors 发布交易单]
     * @return [type] [description]
     */
    public function bors(Request $request) {

    	$money_type = $request->get('money_type');
    		
        return $this->fetch('bors', [
                'money_type'=>$money_type
        ]);
    }

    public function saleOrBuy(Request $request) {
    	if ($request->isPost()) {	
	    		
	    	$validate = $this->validate($request->post(), '\app\index\validate\LegalBuyForm');
	    	if ($validate !== true) {
                return json(['code' => 1, 'message' => $validate]);
            }

            $user = User::where('id', $this->userId)->find();
            if ($user->is_buy != 1) {
                return json(['code' => 1, 'message' => '您账号禁止购买']);
            }

            $seltype = $request->post('seltype');
            $mytype = $request->post('mytype');
            $money_type = $request->post('money_type');
            $price = $request->post('price');
            $number = $request->post('number');
            $minbuy = $request->post('minbuy');
            $maxbuy = $request->post('maxbuy');

           	$totalPrice = bcmul($number,$price,8);

           	$LegalWallet = new LegalWallet(); 
           	$LegalList = new LegalList(); 

           	if($mytype == LegalList::TYPE_BUY){

        		$result = $LegalList->add($this->userId,array('price'=>$price,'number'=>$number,'totalprice'=>$totalPrice,'money_type'=>$money_type,'minbuy'=>$minbuy,'maxbuy'=>$maxbuy),LegalList::TYPE_BUY);
   
           	}else if($mytype == LegalList::TYPE_SALE){

	           	$money = $LegalWallet->getValue($this->userId,$money_type);

	           	if(!$money ){
	           		return json(['code' => 1, 'message' => '您的出售数量不足']);
	           	}

		    	$knumber = bcsub($money->number,$money->freeze,8);

           		if($knumber < $number){
           			return json(['code' => 1, 'message' => '您的出售数量不足']);
           		}

           		$result = $LegalList->add($this->userId,array('price'=>$price,'number'=>$number,'totalprice'=>$totalPrice,'money_type'=>$money_type,'minbuy'=>$minbuy,'maxbuy'=>$maxbuy),LegalList::TYPE_SALE);
           	}

           	if($result){
           		return json(['code' => 0, 'message' => '发布成功', 'toUrl' => url('legal/index')]);
           	}else{
           		return json(['code' => 1, 'message' => '发布错误']);
           	}

    	}
    }

    /**
     * [index 交易市场]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function index(Request $request) {

        $type = $request->get('type');
        if(!$type){
            $type = LegalList::TYPE_BUY;
        }
        return $this->fetch('index', [
                    'user_id' => $this->userId,
                    'type' => $type
        ]);
    }

    /**
     * [buyList 获取购买订单]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function buyList(Request $request) {

        $Model = new \app\index\model\Legal();    
       
        $data = array();
        	
        $data['page'] = $request->post('page')??1;
        $data['psize'] = $request->post('psize')??10;   
        $data['business'] = $request->post('business')??''; 
        $data['money_type'] = $request->post('money_type')??'';
        $data['type'] = LegalList::TYPE_BUY;

        $data = $Model->getList($data);

        return json([
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * [saleList 获取出售订单]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function saleList(Request $request) {

        $Model = new \app\index\model\Legal();    
        $data = array();

        $data['page'] = $request->post('page')??1;
        $data['psize'] = $request->post('psize')??10;   
        $data['business'] = $request->post('business')??''; 
        $data['money_type'] = $request->post('money_type')??'';
        $data['type'] = LegalList::TYPE_SALE;

        $data = $Model->getList($data);

        return json([
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ]);
    }

    /**
     * [dealList 获取交易订单]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function dealList(Request $request) {
        $type = 3;

        $Model = new \app\index\model\Legal();    
        $data = array();

        $data['page'] = $request->post('page')??1;
        $data['psize'] = $request->post('psize')??10;   
        $data['business'] = $request->post('business')??''; 
        $data['money_type'] = $request->post('money_type')??'';
        $data['type'] = $type;

        $data = $Model->getList($data);

        return json([
            'code' => 0,
            'message' => 'success',
            'data' => $data,
        ]);   
    }



}
