<?php

namespace app\index\controller;

use app\common\service\Users\Identity;
use think\Controller;
use think\Request;
use think\Db;
use think\facade\Session;
use app\common\entity\User;
use app\common\entity\UserMagicLog;


class Payment extends Controller {

	private $shop_id=6256; //商户ID，商户在千应官网申请到的商户ID
	private $key="8d59ffb6a837419381696d122969fb76"; //密钥

	private $bank_Type=array(101,102); //充值渠道，101表示支付宝快速到账通道 102表示微信快速到账通道
	private $callbackurl="http://lll.weixqq4.top/zhao/index.php/index/payment/callback.html"; //商户的回掉地址，【请根据实际情况修改】
	private $gofalse="http://lll.weixqq4.top/zhao/index.php"; //订单二维码失效，需要重新创建订单时，跳到该页
	private $gotrue="http://lll.weixqq4.top/zhao/index.php/index/payment/successpay.html"; //支付成功后，跳到此页面
	private $posturl='https://www.qianyingnet.com/pay/'; //千应api的post提交接口服务器地址
	private $charset="utf-8"; //字符集编码方式
	private $token=""; //自定义传过来的值 千应平台会返回原值

	public function __construct(Request $request){
		echo 'domain: ' . $request->domain() . '<br/>';
		$this->token = date('Ymd');
	}
	/**
	 * 支付方法
	 */
	public function pay(Request $request){

		// $bank_payMoney = $request->post('bank_payMoney'); //充值金额
		// $bank_Type = $request->post('bank_Type'); // 获取充值类型
		
		$bank_payMoney = 1; //充值金额
		$bank_Type = 101; // 获取充值类型
		$id = 1;
		if (!in_array($bank_Type,$this->bank_Type)) {
			echo '支付类型错误！请选择正确的支付类型';
			return ;
		}

		if (!is_numeric($bank_payMoney)&&$bank_payMoney>0) {
			echo '充值金额为正数';
			return ;
		}

		$orderid = $this->getOrderId($id); //获取订单id

		$parma='uid='.$this->shop_id.'&type='.$bank_Type.'&m='.intval($bank_payMoney).'&orderid='.$orderid.'&callbackurl='.$this->callbackurl; //拼接$param字符串
		echo $parma;
		$parma_key=md5($parma . $this->key);
		echo '<hr>';
		echo $parma_key;
		echo '<hr>';
		$PostUrl=$this->posturl."?".$parma."&sign=".$parma_key."&gofalse=".$this->gofalse."&gotrue=".$this->gotrue."&charset=".$this->charset."&token=".$this->token."&uuid=".$id; 


		switch($bank_Type){
			case 101:
				$remark = '支付宝充值';
				$paytype = 1;
				break;
			case 102:
				$remark = '微信充值';
				$paytype = 2;
				break;
		}

		// 定义订单数组
		$data = [];
		$data['user_id'] = $id;//用户id
		$data['magic'] = $bank_payMoney;//充值金额
		$data['old'] = 0;//原来的金额
		$data['new'] = 0;//之后的金额
		$data['remark'] = $remark;//之后的金额
		$data['orderid'] = $orderid;
		$data['types'] = 1;//1 ：钱包充值，2：系统充值
		$data['paytype'] = $paytype;
		$data['paystatus'] = 0; //支付宝或者微信支付是否成功 0：待支付 ，1：支付成功，2：支付失败
		$data['create_time'] = time();

		$UserMagicLog = new UserMagicLog();
		$result = $UserMagicLog->save($data);

		if ($result) {
			$this->redirect($PostUrl,302);
		}else{
			return json(['code' => -3, 'message' => '支付失败，请重新支付！',]);
		}

		echo $PostUrl;
		exit;
		
	}

	/**
	 * 回调方法
	 */
	public function callback(Request $request){

		$rmbToeth = 1.2; // 1人民币转换为多少eth

		$data = [];
		$data = $request->get();
		// $a = '{"oid":"pay2018082211059576648","status":"1","m1":"1.00","mInt":"1","sign":"7fe9f289e8405aa7731d8325ccaf5c8e","oidMy":"10120180822-095129-696742","oidPay":"2018082221001004550580834565","time":"2018\/8\/22 9:54:08","token":"token","msg":"success","m":"1.00","uuid":"1","tid":"101"}';
		// $data = json_decode($a,true);

		//  订单状态 0未付款；1付款成功；2超时未付款失效；3已删除；4异常；5下发成功，订单正常完成；6补单；7由于网关掉线等导致的失效（这种情况要尽量避免，因为极可能掉单）；8退款
		if ( $data['status']==1) {
			$User = new User();
			$UserMagicLog = new UserMagicLog();
			// $User->where('id',$data['']);

			$userObj = $User->where('id',$data['uuid'])->field('id,bth')->find();
			if ($userObj) {
				print_r($userObj);
				$parma='oid='.$data['oid'].'&status='.$data['status'].'&m='.$data['m']; //拼接$param字符串

				$parma_key=strtolower(md5($parma . $this->key));
				$sign=strtolower($data['sign']);

				// 判断
				if ($parma_key==$sign) {
					echo '123';
					$result = $UserMagicLog->where('orderid',$data['oid'])->where('paystatus',0)->find();
					print_r($result);

					if ($result) {
						$magic = bcmul($data['m'],$rmbToeth,8);// 充值金额转换为eth
						$result->magic = $magic;
						$result->old = $userObj->bth;// 充值前
						$result->new = bcadd($magic,$userObj->bth,8);// 充值后
						$result->paystatus = 1;
						$result->pf_orderid = $data['oidPay'];

						Db::startTrans();
        				try {

							$res = $result->save();
							if ($res===false) {
                				throw new \Exception('充值失败');
							}

							switch($data['tid']){
								case 101:
									// $remark = '支付宝充值';
									// $paytype = 1;
									$User->setBonus($data['uuid'],'bth',$needeth,'支付宝充值');
									break;
								case 102:
									$User->setBonus($data['uuid'],'bth',$needeth,'微信充值');
									// $remark = '微信充值';
									// $paytype = 2;
									break;
							}
							
							Db::commit();

				        } catch (\Exception $e) {
				            Db::rollBack();

				        }
					}


				}
			}
			



			// print_r($request->param());
			file_put_contents('./paylogtest.txt', json_encode($data).PHP_EOL,FILE_APPEND);


		}
		
	}

	/**
	 * int     id  传递用户id
	 * return  orderid 返回生成的用户订单id
	 */
	public function getOrderId($id){
		return 'pay'.date('Ymd') . $id . date('His').rand(1000,9999);
	}

	/**
	 * 成功时回调
	 */
	public function successpay(){

	}

	public function get(){
		$a = '{"oid":"pay2018082210951432356","status":"1","m1":"1.00","mInt":"1","sign":"C0379B088EC7ECF3F2C296A28C81F896","oidMy":"10120180822-095129-696742","oidPay":"2018082221001004550580834565","time":"2018\/8\/22 9:54:08","token":"token","msg":"success","m":"1.00","uuid":"1","tid":"101"}';
		$b = json_decode($a,true);
		print_r($b);
		// var_dump($a);
		echo '<pre>';
		print_r(json_decode($a,true));
		// $data = [];
		// $data[]= $b[''];

	}


}
