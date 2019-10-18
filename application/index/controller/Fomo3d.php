<?php

namespace app\index\controller;

use app\index\model\SiteAuth;
use app\common\entity\Keychange;
use app\common\entity\FomoGame;
use app\common\entity\FomoTeam;
use app\common\entity\FomoConfig;
use app\common\entity\Buykey;
use app\common\entity\Divide;
use app\common\entity\Bonus;
use app\common\entity\User;
use app\common\entity\FomoWithdraw;
use app\common\entity\FomoAirdrop;
use app\common\entity\FomoAirdropLog;
use app\common\entity\FomoRecharge;
use think\Controller;
use think\Request;
use think\Db;
use app\common\entity\UserInviteCode;
use app\common\entity\InviteaWard;
use app\common\entity\Article;

class Fomo3d extends Base {
     
    public function initialize() {
        
        parent::initialize();
    }

    public function index(Request $request) {
        // var_dump($this->userInfo);
        $InCode = $request->get('incode')??'';
        
    	//查看该期信息
    	$FomoGame = new FomoGame();
        $FomoGamelist = $FomoGame->where('status',1)->order('id','desc')->find();
        if (!$FomoGamelist) {
            $FomoGamelist = $FomoGame->where('status',-1)->order('id','desc')->find();
        }

        $buykey = new buykey();
        $buykeystatistics = $buykey->field('sum(expense) as sumexpense,teamid')->where('periods',$FomoGamelist['id'])->where('status',1)->group('teamid')->select();

       	$statistic = [];
       	$statisticarr = [];


       	foreach ($buykeystatistics as $value) {
       		$statistic[$value['teamid']] = $value;
       		$statisticarr[] = $value['teamid'];
       	}
   		

        $allteam = [];

        if(!empty($FomoGamelist)){
        	//查看队伍情况
        	$allteam = unserialize($FomoGamelist['team_ids']);

			$flag = [];

			foreach ($allteam as &$value) {
				$flag[] = $value['teamid'];
				$team = FomoTeam::where('id',$value['teamid'])->find();
				$value['content'] = $team['content'];
				$value['intro'] = $team['intro'];
                $value['image'] = $team['image'];

				if(in_array($value['teamid'], $statisticarr)){
					$value['expense'] = $statistic[$value['teamid']]['sumexpense'];
				}else{
					$value['expense'] = 0;
				}
				
			}

			array_multisort($flag, SORT_ASC, $allteam);
        }

        $information = $buykey->field('sum(expense) as sumexpense,sum(bonus) as sumbonus,sum(keynum) as sumkeynum')->where('periods',$FomoGamelist['id'])->where('status',1)->find();

        $FomoConfig = new FomoConfig();
        $addseconds = $FomoConfig->getValue('addseconds');
        $FomoGamelist['qrcodes'] = $FomoConfig->getValue('path');
        $FomoGamelist['sumexpense'] = $information['sumexpense'];
        $FomoGamelist['sumbonus'] = $information['sumbonus'];
        $FomoGamelist['sumkeynum'] = $information['sumkeynum'];
        $FomoGamelist['sumtime'] = $information['sumkeynum']*$addseconds;
        $FomoGamelist['sumyear'] = $FomoGamelist['sumtime']/86400;

        if( round($FomoGamelist['sumyear']/365,2) > 0){
        	$FomoGamelist['sumyear'] = round($FomoGamelist['sumyear']/365,2).' 年';
        }else{
        	$FomoGamelist['sumyear'] = round($FomoGamelist['sumyear']/12,2).' 月';
        }
   		$FomoGamelist['nick_name'] = "";
   		//查看最后下单的人
        $endbuy = $buykey->field('user_id')->where('periods',$FomoGamelist['id'])->where('status',1)->order('id','desc')->find();
        
      	if(!empty($endbuy)){
      		$enduser = User::where('id',$endbuy['user_id'])->find();
   			$FomoGamelist['nick_name'] = $enduser['nick_name'];
      	}


   		$FomoGamelist['myselfkey'] = 0;
   		$FomoGamelist['promptlybonus'] = 0;
   		$FomoGamelist['bonus'] = 0;
   		$FomoGamelist['lockbonus'] = 0;

        $inviteawardSum = 0;  // 直推奖励
        $totalbonus = 0; // 总收益

        $balance = 0; // 余额
        $address = '';
   		if($this->userId){
   			//查看用户
   			$member = User::where('id',$this->userId)->find();
            $address = $member['trade_address'];
   			//查看本期已买key
   			$buykeymyself = $buykey->field('sum(keynum) as sumkeynum')->where('periods',$FomoGamelist['id'])->where('status',1)->where('user_id',$this->userId)->find();
   			$FomoGamelist['myselfkey'] = $buykeymyself['sumkeynum'];

   			$promptlybonus = Bonus::field('sum(bonus) as sumbonus')->where('periods',$FomoGamelist['id'])->where('types',1)->where('user_id',$this->userId)->find();
   			$FomoGamelist['promptlybonus'] = $promptlybonus['sumbonus'];

   			$FomoGamelist['bonus'] = $member['bonus'];

   			//查看当前该期所有的key 总和
	        $keynumtotal =  $buykey->where('status',1)->where('periods',$FomoGamelist['id'])->column('sum(keynum)');
	        $keynumtotal = $keynumtotal[0];
            if($FomoGamelist['myselfkey']>0){
                $ratio = bcdiv($FomoGamelist['myselfkey'],$keynumtotal,8);
            }else{
                $ratio = 0;
            }

	        $FomoGamelist['lockbonus'] = $FomoGamelist['capital']>0? bcmul(bcmul($FomoGamelist['capital'],$FomoGamelist['bonus_scale'],8),$ratio,8)/100 :0;

            // 获取邀请获得的金额
            // $data = User::where('pid',$member['id'])->column('id');
            $ini = new InviteaWard();
            $inviteawardSum = $ini->where('user_pid',$this->userId)->where('periods',$FomoGamelist['id'])->sum('inviteaward');

            // var_dump($inviteawardSum);
            // 个人总收益 
            $totalbonus = Bonus::where('periods',$FomoGamelist['id'])->where('types',1)->where('user_id',$this->userId)->sum('bonus');

            $balance = $member['bth']+$member['bonus'];
            
   		}
        $nick_name = $this->userInfo?$this->userInfo->nick_name:'';
            
        // 显示二维码
        $is_showcode = !empty($member['is_showcode'])?$member['is_showcode']:0;
        $invitedCode = $this->userId&&$is_showcode==1?UserInviteCode::getCodeByUserId($this->userId):'';
        
        // 获取基本设置
        $fconfig = $FomoConfig->getALLConfig(); 

        // 获取1个 eth的美金额
        $data = $this->getUSDT();
        $data = json_decode($data,true);

        // 空投表
        $FomoAirdropLog = new FomoAirdropLog();
        $AirdropLog = $FomoAirdropLog->alias('a')
                    ->field('a.*,u.mobile,u.nick_name')
                    ->leftJoin("user u", 'u.id = a.user_id')
                    ->order('a.id desc')
                    ->limit(10)
                    ->select()->toArray();
        
        foreach ($AirdropLog as $key_a => $value_a) {
            $AirdropLog[$key_a]['type'] = 1;
        }

        $adpj = DB::table('fomo_ad')->where('status',0)->select();
        
        foreach ($adpj as $key_b => $value_b) {
            $adpj[$key_b]['type'] = 2;
        }

        $addata = array_merge($AirdropLog,$adpj);
        shuffle($addata); // 打乱广告

        $article = DB::table('article')->where('status',1)->order('sort asc')->select();
     
        // var_dump($allteam);
        return $this->fetch('index', [
        	'allteam'=>$allteam,
        	'fomogame'=>$FomoGamelist,
            'userId'=>$this->userId,
            'nickname'=>$nick_name,
            'invitedCode'=>$invitedCode,
            'InCode'=>$InCode,
            'fconfig'=>$fconfig,
            'inviteawardSum'=>$inviteawardSum,
            'totalbonus'=>$totalbonus,
            'balance'=>$balance,
            'usdt'=>$data['ticker']['buy'],
            'AirdropLog'=>$addata,
            'address'=>$address,
            'article'=>$article,
        ]);
    }



    public function buyKey(Request $request) {

    	$tixToBuy = intval($request->post('tixToBuy'))??'';
    	$teamid = intval($request->post('teamid'))??'';
    	$paytype = intval($request->post('paytype'))??'';
        $userid = $this->userId;
        $member = User::where('id',$userid)->find();


    	if(empty($tixToBuy) || $tixToBuy<=0 ){
            return json([
                'code' => -1,
                'message' => '参数有误！',
            ]);
    	}

    	if(empty($teamid)){
            return json([
                'code' => -2,
                'message' => '请选择队伍！',
            ]);
    	}

    	if(empty($paytype) || !in_array($paytype,array(1,2,3)) ){
            return json([
                'code' => -3,
                'message' => '支付类型有误！',
            ]);
    	}


        if(empty($userid) || empty($member) ){
            return json([
                'code' => -4,
                'message' => '您还没登录！',
            ]);
        }



    	//查看key 兑换比例
        $keychange = new Keychange();
        $keyvalue = $keychange->getKey();


    	//计算需要多ETH
    	$needeth = bcmul($tixToBuy,$keyvalue,8);

    	//支付区间
        switch ($paytype) {
            //BTH
            case '1':
                //查看小金库够不够钱
                if($member['bth'] < $needeth){
                    return json([
                        'code' => -1,
                        'message' => 'ETH金额不足！',
                    ]);
                }

                break;

            //金库
            case '2':
                //查看小金库够不够钱
                if($member['bonus'] < $needeth){
                    return json([
                        'code' => -1,
                        'message' => '小金库金额不足！',
                    ]);
                }

                break;

            //二维码
            case '3':
                # code...
                break;
        }

    	//查看该期信息
        $FomoGame = FomoGame::where('status',1)->order('id','desc')->find();

        $teamlist = unserialize($FomoGame['team_ids']);
        $team = $teamlist[$teamid];

        if(empty($team) || empty($FomoGame)){
        	return json([
                'code' => -5,
                'message' => '参数有误！',
            ]);
        }


        if(time() > $FomoGame['endtime']){

        	return json([
                'code' => -1,
                'message' => '本期已结束！',
            ]);
        
        }


        $FomoConfig = new FomoConfig();
        $keySet = $FomoConfig->getALLConfig();

 		$capital = bcdiv(bcmul($needeth,$team['pond_scale'],8),100,8);
 		$bonus = bcdiv(bcmul($needeth,$team['bonus_scale'],8),100,8);
 		$inviteaward = bcdiv(bcmul($needeth,$keySet['inviteaward'],8),100,8);
 		$teamaward = bcdiv(bcmul($needeth,$keySet['teamaward'],8),100,8);
 		$dropaward = bcdiv(bcmul($needeth,$keySet['dropaward'],8),100,8);

        $inviteaward2 = 0; // 第二级直推
        $inviteaward3 = 0; // 第三级直推
        $inviteaward4 = 0; // 第四级直推
        $inviteaward5 = 0; // 第五级直推

        if ($keySet['inviteaward2']>0 && $member['pid']!=0) { // 判断有没有二级
            $member2 = User::where('id',$member['pid'])->find();
            if (!empty($member2)) {
                $inviteaward2 = bcdiv(bcmul($needeth,$keySet['inviteaward2'],8),100,8);// 第二级直推
            }
        }

        if ($keySet['inviteaward3']>0 && !empty($member2)) {
            if ($member2['pid']!=0) {// 判断有没有三级
                $member3 = User::where('id',$member2['pid'])->find();
                if (!empty($member3)) {
                    $inviteaward3 = bcdiv(bcmul($needeth,$keySet['inviteaward3'],8),100,8);
                }
            }
        }

        if ($keySet['inviteaward4']>0 && !empty($member3)) {
            if ($member3['pid']!=0) {// 判断有没有四级
                $member4 = User::where('id',$member3['pid'])->find();
                if (!empty($member4)) {
                    $inviteaward4 = bcdiv(bcmul($needeth,$keySet['inviteaward4'],8),100,8);
                }
            }
        }

        if ($keySet['inviteaward5']>0 && !empty($member4)) {
            if ($member4['pid']!=0) {// 判断有没有五级
                $member5 = User::where('id',$member4['pid'])->find();
                if (!empty($member5)) {
                    $inviteaward5 = bcdiv(bcmul($needeth,$keySet['inviteaward5'],8),100,8);
                }
            }
        }
        

        $time = time();
    	$buyKeyarr = [
    		'periods'=> $FomoGame['id'],
    		'user_id'=>$this->userId,
    		'keynum'=>$tixToBuy,
    		'expense'=>$needeth,
    		'keyval'=>$keyvalue,
    		'teamid'=>$teamid,
    		'capital'=>$capital,
    		'bonus'=>$bonus,
    		'inviteaward'=>$inviteaward,
            'inviteaward2'=>$inviteaward2,
            'inviteaward3'=>$inviteaward3,
            'inviteaward4'=>$inviteaward4,
            'inviteaward5'=>$inviteaward5,
    		'teamaward'=>$teamaward,
    		'dropaward'=>$dropaward,
    		'status'=>1,
    		'paytime'=>$time,
    		'paytype'=>$paytype,
    		'createtime'=>$time,
    	];


    	$Buykey = new Buykey();

        $User = new User();
      
        Db::startTrans();
        try {

            //扣钱
            switch ($paytype) {
                //BTH
                case '1':
                    $User->setBonus($userid,'bth',-$needeth,'支付KEY减少');
                    break;

                //金库
                case '2':
                    $User->setBonus($userid,'bonus',-$needeth,'支付KEY减少');
                    break;
                //二维码
                case '3':
                    # code...
                    break;
            }


            // $result = $User->where('id',$this->userId)->setField('is_showcode',1);

            $Buykey->save($buyKeyarr);
            $Buykeyid = $Buykey->id;

            //更新该期资金池累积和空投
            $Divide = new Divide();
            $Divide->upDivide($Buykeyid);

            //控制key价格增加 更新倒计时
            $keychange->addKey($tixToBuy);

            //发放分红
            $Bonus = new Bonus();
            $Bonus->giveBonus($Buykeyid);

            // 发放直推奖励
            $Bonus->giveRecommend($Buykeyid);

            // 直推记录
            // $invi->save($inviteawardarr);
            Db::commit();

        } catch (\Exception $e) {

            Db::rollback();
            // print_r($e->getMessage());
            return json([
                'code' => -1,
                'message' => '系统繁忙！',
            ]);
        }


        // 概率
        $dropchance = intval($keySet['dropchance']); 
        // 判断是否中奖
        $chance = mt_rand(1,100);
        $is_chance = false;

        if ($dropchance>=$chance) {
            $is_chance = true;
        }

        if ($is_chance) {

            // 获取本期游戏的数据
            $FomoGame = new FomoGame();
            $FomoGamearr = $FomoGame->where('status',1)->order('id','desc')->find();

            // 获取空投池配置
            $FomoAirdrop = new FomoAirdrop();
            $AirdropCofig = $FomoAirdrop->getALLConfig();

            // 计算本期总共投入多少钱
            $expense = $Buykey->where('periods',$FomoGamearr['id'])->where('user_id',$this->userId)->sum('expense');
            // var_dump($expense);
            // 获取用户信息
            $user = new User();
            $userInfo = $user->where('id',$this->userId)->find();


            // 计算需要取出空投池金额的比例
            $proportion = '';
            foreach ($AirdropCofig as $key => $value) {

                if ($expense>=$value['min_eth']&&$expense<$value['max_eth']) {
                    $proportion = bcdiv($value['proportion'],100,2);
                }
                if ($expense>=$AirdropCofig[count($AirdropCofig)-1]['max_eth']) {
                    $proportion = bcdiv($value['proportion'],100,2);
                }
            }

            $bonusadd = bcmul($FomoGamearr['dropaward'],$proportion,8);// 中奖金额
            $bonusbf = bcadd($bonusadd,$userInfo['bonus'],8);// 添加之后的金额
            $bonusDec = bcsub($FomoGamearr['dropaward'],$bonusadd,8);// 扣除中奖金额后的空投池

            $AirderopLogArr = [
                'user_id'=>$this->userId,
                'form_id'=>0,
                'keynum'=>$tixToBuy,
                'before'=>$userInfo['bonus'],
                'later'=>$bonusbf,
                'bonus'=>$bonusadd,
                'periods'=>$FomoGamearr['id'],
                'createtime'=>time(),
            ];
            // var_dump($AirderopLogArr);
            $FomoAirdropLog = new FomoAirdropLog();
            $Divide = new Divide();

            Db::startTrans();
            try {
                // 保存进空投明细表
                $FomoAirdropLog->save($AirderopLogArr);

                // 更新空投池
                // $FomoGame->where('id',$FomoGamearr['id'])->setField('dropaward',$bonusDec);

                // 更新用户表金额
                $user->setBonus($this->userId,'bonus',$bonusadd,'空投中奖增加');
                // 更新空投池
                $Divide->setDivide('dropaward',-$bonusadd,$Buykeyid,'空投中奖扣除空投池');

                Db::commit();

                return json([
                    'code' => 1,
                    'message' => '恭喜你获得了空投奖励',
                ]);

            } catch (\Exception $e) {

                Db::rollback();
                return json([
                    'code' => -2,
                    'message' => '系统繁忙！',
                ]);


            }
        }


        return json([
            'code' => 0,
            'message' => '',
        ]);

    }

    public function recharge(Request $request){
        $bth = $request->post('amount');
        $message = $request->post("message");
        $id = $this->userId;

        if (!$id) {
            return ['code' => -1, 'message' => '请先登录后在进行充值！'];
        }

        if (!preg_match('/^[0-9]+.?[0-9]*$/', $bth)) {
            return ['code' => -1, 'message' => '充值的数量必须为正整数或者小数'];
        }

        if (!$message) {
            return ['code' => -1, 'message' => '请按规格填写备注！'];
        }

        $FomoConfig = new FomoConfig();
        $keySet = $FomoConfig->getALLConfig();

        $User = new user();
        $orderid = $User->setWithdrawNumber($id);

        $FomoRecharge = new FomoRecharge();

        //插入更新
        $arr = [
            'periods'=>0,
            'user_id'=>$id,
            'types'=>'bth',
            'orderid'=>$orderid,
            'change'=>$bth,
            'message'=>$message,
            'rechargepath'=>$keySet['rechargepath'],
            'path'=>$keySet['path'],
            'status'=>0,
            'createtime'=>time(),
            'examinetime'=>0,
        ];

        $result = $FomoRecharge->save($arr);

        
        // $result = $User->setBonus($id,'bth',$bth, $remark,$message);

        if (!$result) {
            return ['code' => -1, 'message' => '下单失败'];
        }
        return ['code' => 0, 'message' => '下单成功'];
    }


    /**
     * 
     */
    public function buycode(Request $request){

        $op = $request->post('op')??'';

        if ($op=='buy') {

            $userid = $this->userId;
            $member = User::where('id',$userid)->find();

            if(empty($userid) || empty($member) ){
                return json([
                        'code' => -1,
                        'message' => '您还没登录！',
                ]);
            }
            // 获取需要多少个ETH才能开启配置
            $FomoConfig = new FomoConfig();
            $inviteaward_num = $FomoConfig->getValue('inviteaward_num');

            // var_dump($member);

            if ($member['bth']<$inviteaward_num) {
                return json([
                        'code' => -1,
                        'message' => '余额不足！',
                ]);
            }

            Db::startTrans();
            try {
                
                $User = new User();
                //扣钱
                // if ($member['bonus']>=$inviteaward_num) {
                    
                //     $User->setBonus($userid,'bonus',-$inviteaward_num,'购买邀请码');
                // }else if($member['bth']>=$inviteaward_num){

                    $User->setBonus($userid,'bth',-$inviteaward_num,'购买邀请码');
                // }
                

                $User->where('id',$userid)->setField('is_showcode',1);

                Db::commit();

                return json([
                    'code' => 0,
                    'message' => '购买成功！',
                ]);


            } catch (\Exception $e) {

                Db::rollback();
                return json([
                    'code' => -1,
                    'message' => '系统繁忙！',
                ]);

            }



        }

    }



    public function indexdata(Request $request) {

 		$op = $request->post('op')??'';

        if($op=='start'){

            $data = array();

            //查看key 兑换比例
            $keychange = new Keychange();
            $keyvalue = $keychange->getKey();
            $data['keyvalue'] = $keyvalue?:0;
    
            //查看该期信息
            $FomoGame = FomoGame::where('status',1)->order('id','desc')->find();

            if(!empty($FomoGame)){
            	$data['endtime'] = date('m/d/Y H:i:s',$FomoGame['endtime']);
            }
            

            return json([
                'code' => 0,
                'message' => 'success',
                'data' => $data,
            ]);

        }
    }


    public function withdraw(Request $request) {

        $withdrawnum =  $request->post('withdrawnum')?floatval($request->post('withdrawnum')):'';
        $withdrawInputPath =  $request->post('withdrawInputPath')?$request->post('withdrawInputPath'):'';
        $userid = $this->userId;
        $member = User::where('id',$userid)->find();


        if(empty($userid) || empty($member) ){
            return json([
                'code' => -1,
                'message' => '您还没登录！',
            ]);
        }

        // var_dump($withdrawnum);
        // var_dump($withdrawInputPath);


        if(empty($withdrawnum) || $withdrawnum <= 0){
            return json([
                'code' => -1,
                'message' => '参数有误！',
            ]);
        }

        // 判断是否有地址
        if(empty($withdrawInputPath)){
            return json([
                'code' => -1,
                'message' => '参数有误！',
            ]);
        }



        if($member['bonus'] < $withdrawnum){
            return json([
                'code' => -1,
                'message' => '小金库金额不足！',
            ]);
        }

        $User = new User();
        $wssn =  $User->setWithdrawNumber($userid); // 获取订单编号

        $withdrawarr = [
            'user_id'=>$userid,
            'wssn'=>$wssn,
            'money'=>$withdrawnum,
            'wspath'=>$withdrawInputPath,
            'status'=>0,
            'createtime'=>time(),
        ];

        Db::startTrans();
        try {

            //扣钱
            $User->setBonus($userid,'bonus',-$withdrawnum,'提取减少');

            $FomoWithdraw = new FomoWithdraw();
            $result = $FomoWithdraw->save($withdrawarr);

            if (!$result) {
                throw new \Exception('操作失败');
            }

            Db::commit();

        } catch (\Exception $e) {

            Db::rollback();
        }

        return json([
            'code' => 0
        ]);

    }

    public function getUSDT(){
        $url = 'https://www.okcoin.com/api/v1/ticker.do?symbol=eth_usd';
        $data = $this->https_request($url);
        return $data;
    }

    function https_request($url)
    {   
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //重要！
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl,CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322; .NET CLR 2.0.50727)"); //模拟浏览器代理
        //执行命令
        $data = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        return $data;
    }



}
