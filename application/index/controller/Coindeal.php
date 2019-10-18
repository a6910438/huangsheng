<?php

namespace app\index\controller;

use app\common\entity\Coindeals;
use app\common\entity\Market;
use app\common\entity\User;
use app\common\entity\Config;
use think\Request;

class Coindeal extends Base {


    /**
     * [index 币币交易首页]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function index(Request $request) {

        // if(!$type){
        //     $type = LegalList::TYPE_BUY;
        // }

        return $this->fetch('index', [
        ]);
    }



    /**
     * [ 获取首页数据]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function indexdata(Request $request) {

        $op = $request->post('op')??'';

        if($op=='start'){

            $data = array();

            //查看当前市值
            $market = Market::where('cointype','btc')->order('id','desc')->find();

            $data['market'] = number_format($market['later'],4);

            //查看今日涨跌幅
            $earliestmarket = Market::where('cointype','btc')->where('createtime','>=',strtotime(date('Y-m-d')))->order('id')->find();
            if(!empty($earliestmarket)){
            	$dayamount = bcsub($market['later'],$earliestmarket['before'],8);
            	$dayrate = bcdiv($dayamount,$earliestmarket['before'],2);
            }else{
            	$dayrate = '0.00';
            }
           
           
            if($dayrate > 0){
                $data['dayrate'] = '+'.$dayrate;
            }else if($dayrate==0){
                $data['dayrate'] = $dayrate;
            }else{
                $data['dayrate'] = '-'.$dayrate;
            }

            //查看当前卖的
            $selflist = Coindeals::where([
                ['isshow','=',0],
                ['types','=',2],
                ['cointype','=','btc'],
                ['status','=',0],
                ['price','>',$market['later']],
            ])->order('id')->limit(4)->select();


            //查看当前买的
            $buylist = Coindeals::where([
                ['isshow','=',0],
                ['types','=',1],
                ['cointype','=','btc'],
                ['status','=',0],
                ['price','<',$market['later']],
            ])->order('id')->limit(4)->select();


            $data['selflist'] = $selflist;
            $data['buylist'] = $buylist;


            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => $data,
            ]);

        }
    }




     /**
     * [ 购买币]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function buy(Request $request) {

    	$dealway = intval($request->post('dealway'));

    	if(!in_array($dealway,array(0,1))){
    		return json([
                'code' => -1,
                'message' => '类型有误！',
            ]);
    	}


    	//定价
    	if($dealway==0){
    		

    		$realbugprice = number_format($request->post('realbugprice'),4);
    		$bugnum = number_format($request->post('bugnum'),4);

    		if($realbugprice <= 0 || $bugnum <= 0){
	    		return json([
	                'code' => -1,
	                'message' => '参数有误！',
	            ]);
    		}


    		$Coindeals = new Coindeals();
    		$data = [
    			'cointype'=>'btc',
    			'number'=>$bugnum,
    			'price'=>$realbugprice,
    			'dealway'=>$dealway,
    		];

    		$Coindeals->add($this->userId,$data,Coindeals::TYPE_BUY);

    	}else if($dealway==1){
    	//市场

    		$realbugprice = number_format($request->post('realbugprice'),4);

    		if($realbugprice <= 0){
	    		return json([
	                'code' => -1,
	                'message' => '参数有误！',
	            ]);
    		}

    		$Coindeals = new Coindeals();
    		$data = [
    			'cointype'=>'btc',
    			'price'=>$realbugprice,
    			'dealway'=>$dealway,
    		];

    		$Coindeals->add($this->userId,$data,Coindeals::TYPE_BUY);
    		
    	}

    	return json([
            'code' => 0,
            'message' => '买入成功',
            'toUrl'=>''
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
