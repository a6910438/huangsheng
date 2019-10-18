<?php

namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Db;
use app\common\entity\Quotation;
use app\common\entity\MywalletLog;
use app\common\entity\Mywallet;
use app\common\entity\Lottery as lotteryModel;
use app\common\entity\User as User;
use app\common\entity\PeriodsBonus;

use app\common\entity\YekesLog;


class Percent extends Base {


//  public function initialize() {
//
//        parent::initialize();
//    }


    // 百分彩主页
    public function index(Request $request){

        $ratio = Quotation::where('id',1)->find();

        $time = time();

        $date = date('Ymd',$time);

        // $last = DB::table('percent_date')->where('date',$date)->order('id desc')->find();

        // 当前期数
        $last = DB::table('percent_date')->order('id desc')->find();
  
  
        if($last['rest_num'] > 0){

            $last['num'] = sprintf("%03d", $last['num']);

            $last['usdt'] = $last['btc']/$ratio['btc'] + $last['eth']/$ratio['eth'] + $last['eos']/$ratio['eos'];

            // return $this->fetch('index',['list'=> $last]);
            return json([
                'code' => 0,
                'message' => '请求成功！',
                'info' =>$last
            ]);


        }else if($last['rest_num'] == 0){
            
            // 新的一期
            if($date == $last['date']){

                $num1 = $last['num'] + 1;

                $num1 = sprintf("%03d", $num1);

            }else{

                $num1 = 1;
                $num1 = sprintf("%03d", $num1);
                
            }
           
            $arr = array(
                'date' => $date,
                'buy_num' => 0,
                'num' => $num1,
                'rest_num' => 100,
                'create_time' => time()
                );
         
            $result = DB::table('percent_date')->insert($arr);


            $res = DB::table('percent_date')->order('id desc')->find();

            $res['usdt'] = $res['btc']/$ratio['btc'] + $res['eth']/$ratio['eth'] + $res['eos']/$ratio['eos'];

            return json([
                'code' => 0,
                'message' => '请求成功！',
                'info' =>$res
            ]);

        }

    

    }

    /**
     * @power 获取用户账户余额
     */
    public function getUserNum($uid,$numType)
    {
        return Mywallet::where('user_id',$uid)->value($numType);
    }

    // 下注
    public function betting(Request $request){

        $userid = $this->userId;
        // $member = User::where('id',$userid)->find();
        $member = DB::table('user')->where('id',$userid)->find();

        //获取比例
        $ratio = Quotation::where('id',1)->find();

        if(empty($userid) || empty($member) ){
            return json([
                'code' => -4,
                'message' => '您还没登录！',
            ]);
        }

        $date = $request->post('date');
        $num = $request->post('num');
        $num = sprintf("%03d", $num);
        // $money_type = 1;
        $money_type = $request->post('money_type');


        $number = $request->post("number");

        // 单次投注
        $onetime_max = DB::table('percent_betting_set')->where('key','onetime_max')->value('value');
        
        // 限时投注
        $time_start = DB::table('percent_betting_set')->where('key','time_start')->value('value');
        $time_end = DB::table('percent_betting_set')->where('key','time_end')->value('value');
        $time_start1 = strtotime($time_start);
        $time_end1 = strtotime($time_end);
        if($time_end1 > $time_start1){
        	$now = time();
        	if($now <= $time_end1 && $now >= $time_start1){
        		$YekesLog = new YekesLog();
            	$YekesLog->limitGetYekes($userid);
        	}
        }
        if($number > $onetime_max){

            return json(['code'=>1,'message'=>'单次投注量超额']);
        }

        // 本期最多投注
        $oneman_max = DB::table('percent_betting_set')->where('key','oneman_max')->value('value');

        $total = DB::table('game_lottery')->where('periods',$date.'_'.$num)->where('uid',$userid)->sum('total');

        if($total+$number > $oneman_max){
            return json(['code'=>1,'message'=>'超出本期最多投注量']);

        }
        switch ($money_type)
        {
            case 1:
                $price = $number * $ratio['btc'];

                break;
            case 2:
                $price = $number * $ratio['eth'];
                break;
            case 3:
                $price = $number * $ratio['eos'];
                break;
            default:
                return json(['code'=>1,'message'=>'参数错误']);
        }

        // 判断余额是否足够
        if($money_type == 1){

            $old = $this->getUserNum($userid,'btc');

        }else if($money_type == 2){

            $old = $this->getUserNum($userid,'eth');

        }else if($money_type == 3){

            $old = $this->getUserNum($userid,'eos');

        }else{

            return json(['code'=>1,'message'=>'参数错误']);

        }

        if($old < $price){

            return json(['code'=>1,'message'=>'您的余额不足']);

        }else{

            if($money_type == 1){
                Mywallet::where('user_id',$userid)->setDec('btc',$price);
            }else if($money_type == 2){
                Mywallet::where('user_id',$userid)->setDec('eth',$price);

            }else if($money_type == 3){
                Mywallet::where('user_id',$userid)->setDec('eos',$price);

            }

        }

        if($member['is_active'] == 0){

            Db::table('user')->where('id', $userid)->update(['is_active' =>1,'active_time'=>time()]);

            $YekesLog = new YekesLog();
            $YekesLog->shareGetYekes($userid);
        }

        // 金额明细表
        $detail = array(
            'user_id' => $userid,
            'number' => $price,
            'old' => $old,
            'new' => $old-$price,
            'remark' => '百分彩投注',
            'types' => 1,
            'status' => 2,
            'money_type'=> $money_type,
            'create_time'=>time()

            );
        MywalletLog::insert($detail);

        $info = DB::table('percent_buylog')->where('date',$date)->where('num',$num)->order('id desc')->find();
        
        $arr = '';
        if(empty($info)){
            
            for($i=1;$i<=$number;$i++){

                $arr .= $i.',' ;
                // $arr[] = $i;
            }
            $topicid = rtrim($arr, ',');
            $data = array(
                'userid' => $userid,
                'date' => $date,
                'num' => $num,
                'buy_id' => $topicid,
                'create_time' => time()
                );
            
            $result = DB::table('percent_buylog')->insert($data);

            if($money_type == 1){
                Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('btc',$price);
                $usdt = $number * $ratio['btc'];
                Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('usdt',$usdt);
            }elseif($money_type == 2){
                Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('eth',$price); 
                $usdt = $number * $ratio['eth'];
                Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('usdt',$usdt);  
            }elseif($money_type == 3){
                Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('eos',$price);  
                $usdt = $number * $ratio['eos'];
                Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('usdt',$usdt);
            }
            Db::table('percent_date')->where('date', $date)->where('num',$num)->update(['rest_num' =>100-$number,'buy_num'=>$number]);

            // 投注记录总表
            $aa = array(
                'uid' => $userid,
                'types' => 2,
                'prize_number' => $topicid,
                'periods' => $date.'_'.$num,
                'award' =>0,
                'total' => $number,
                'price' => $price,
                'money_type' => $money_type,
                'create_time' => time()
                );

            $res = DB::table('game_lottery')->insert($aa);

            $periods = $date.'_'.$num;

            $this->setbonus($periods,$number,$money_type,$price);

            if($number == 100){
                $block_id = $this->test();

                Db::table('percent_date')->where('date', $date)->where('num',$num)->update(['block_id' => $block_id,'final_time'=>time()]);
                // $res = $this->waitopen();

                // if($res == 1){
                    return json([
                        'code' => 0,
                        'message' => '下注成功！',
                    ]);
                // }
            }else {
                return json([
                    'code' => 0,
                    'message' => '下注成功！',
                ]);
            }

            
        }else{

            // $lastid = substr($info['buy_id'], -1);


            $lastid_arr = explode(',',$info['buy_id']);

            $lastid = end($lastid_arr);
            
     

            $sum = $lastid + $number;

            // 判断有没有满100
            if($sum < 100){
                $rest_num = 100-$sum;
                for($i=$lastid+1;$i<=$sum;$i++){

                    $arr .= $i.',' ;

                }

                $topicid = rtrim($arr, ',');
                
                $data = array(
                    'userid' => $userid,
                    'date' => $date,
                    'num' => $num,
                    'buy_id' => $topicid,
                    'create_time' => time()
                    );
                
                // 添加到购买记录
                $result = DB::table('percent_buylog')->insert($data);

                Db::table('percent_date')->where('date', $date)->where('num',$num)->update(['rest_num' => $rest_num,'buy_num'=>$sum]);

                // 投注记录总表
                $aa = array(
                    'uid' => $userid,
                    'types' => 2,
                    'prize_number' => $topicid,
                    'periods' => $date.'_'.$num,
                    'award' =>0,
                    'total' => $number,
                    'price' => $price,
                    'money_type' => $money_type,
                    'create_time' => time()
                    );

                $res = DB::table('game_lottery')->insert($aa);

                if($money_type == 1){
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('btc',$price);
                    $usdt = $number * $ratio['btc'];
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('usdt',$usdt);
                }elseif($money_type == 2){
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('eth',$price); 
                    $usdt = $number * $ratio['eth'];
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('usdt',$usdt);  
                }elseif($money_type == 3){
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('eos',$price);
                    $usdt = $number * $ratio['eos'];
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('usdt',$usdt);  
                }
                $periods = $date.'_'.$num;
                $this->setbonus($periods,$number,$money_type,$price);

                return json([
                    'code' => 0,
                    'message' => '下注成功！',
                ]);
            }

            // 刚好满100
            if($sum == 100){

                for($i=$lastid+1;$i<=$sum;$i++){

                    $arr.= $i.',';
                }

                $topicid = rtrim($arr);

                $data = array(
                    'userid' => $userid,
                    'date' => $date,
                    'num' => $num,
                    'buy_id' => $topicid,
                    'create_time' => time()
                    );
                // 添加到购买记录
                $result = DB::table('percent_buylog')->insert($data);

                Db::table('percent_date')->where('date', $date)->where('num',$num)->update(['rest_num' => 0,'buy_num'=>$sum]);

                // 投注记录总表
                $aa = array(
                    'uid' => $userid,
                    'types' => 2,
                    'prize_number' => $topicid,
                    'periods' => $date.'_'.$num,
                    'award' =>0,
                    'total' => $number,
                    'price' => $price,
                    'money_type' => $money_type,
                    'create_time' => time()
                    );

                $res = DB::table('game_lottery')->insert($aa);

                // 获取开奖的区块
                $block_id = $this->test();

                Db::table('percent_date')->where('date', $date)->where('num',$num)->update(['block_id' => $block_id,'final_time'=>time()]);
                if($money_type == 1){
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('btc',$price);
                    $usdt = $number * $ratio['btc'];
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('usdt',$usdt);
                }elseif($money_type == 2){
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('eth',$price);
                    $usdt = $number * $ratio['eth'];
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('usdt',$usdt);   
                }elseif($money_type == 3){
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('eos',$price);  
                    $usdt = $number * $ratio['eos'];
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('usdt',$usdt);
                }

                $periods = $date.'_'.$num;
                $this->setbonus($periods,$number,$money_type,$price);

                // $res = $this->waitopen();

                // if($res == '1'){
                    return json([
                        'code' => 0,
                        'message' => '下注成功！',
                    ]);
                // }
                        
            }

            // 大于100
            if($sum > 100){
                $tou = 0;
                for($i=$lastid+1;$i<=100;$i++){

                    $arr .= $i.',';
                    $tou++;
                }

                $topicid = rtrim($arr);

                $data = array(
                    'userid' => $userid,
                    'date' => $date,
                    'num' => $num,
                    'buy_id' => $topicid,
                    'create_time' => time()
                    );
                
                $result = DB::table('percent_buylog')->insert($data);

                Db::table('percent_date')->where('date', $date)->where('num',$num)->update(['rest_num' => 0,'buy_num'=>100]);
                switch ($money_type)
                {
                    case 1:
                        $price = $tou * $ratio['btc'];
                        break;
                    case 2:
                        $price = $tou * $ratio['eth'];
                        break;
                    case 3:
                        $price = $tou * $ratio['eos'];
                        break;
                    default:
                        return json(['code'=>1,'message'=>'参数错误']);
                }
                // 投注记录总表
                $aa = array(
                    'uid' => $userid,
                    'types' => 2,
                    'prize_number' => $topicid,
                    'periods' => $date.'_'.$num,
                    'award' =>0,
                    'total' => $tou,
                    'price' => $price,
                    'money_type' => $money_type,
                    'create_time' => time()
                    );

                $res = DB::table('game_lottery')->insert($aa);

                $periods = $date.'_'.$num;
                $this->setbonus($periods,$tou,$money_type,$price);
                if($money_type == 1){
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('btc',$price);
                    $usdt = $tou * $ratio['btc'];
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('usdt',$usdt);
                }elseif($money_type == 2){
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('eth',$price);
                    $usdt = $tou * $ratio['eth'];
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('usdt',$usdt);   
                }elseif($money_type == 3){
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('eos',$price);  
                    $usdt = $tou * $ratio['eos'];
                    Db::table('percent_date')->where('date', $date)->where('num',$num)->setInc('usdt',$usdt);
                }
                // 获取开奖的区块
                $block_id = $this->test();

                Db::table('percent_date')->where('date', $date)->where('num',$num)->update(['block_id' => $block_id,'final_time'=>time()]);
                // 多余的到下一期
                $last = DB::table('percent_date')->order('id desc')->find();
                $newdate = date('Ymd',time());
                if($newdate == $last['date']){

                    $nuum = $last['num'] + 1;
                    $nuum = sprintf("%03d", $nuum);
                    $date = $last['date'];


                }else{

                    $nuum = 1;
                    $nuum = sprintf("%03d", $nuum);
                    $date = $newdate;
                }

                $arr1 = array(
                    'date' => $date,
                    'buy_num' => 0,
                    'num' => $nuum,
                    'rest_num' => 100,
                    'create_time' => time()
                    );
                
                $result = DB::table('percent_date')->insert($arr1);

                // 剩余的数量
                $num1 = $sum-100;
                $arrr = '';
                for($i=1;$i<=$num1;$i++){

                    $arrr .= $i.',' ;
                    // $arr[] = $i;
                }
                $topicid1 = rtrim($arrr, ','); 
                $data = array(
                    'userid' => $userid,
                    'date' => $date,
                    'num' => $arr1['num'],
                    'buy_id' => $topicid1,
                    'create_time' => time()
                    );
               
                $result = DB::table('percent_buylog')->insert($data);

                Db::table('percent_date')->where('date', $date)->where('num',$nuum)->update(['rest_num' => 100-$num1,'buy_num'=>$num1]);
                switch ($money_type)
                {
                    case 1:
                        $price = $num1 * $ratio['btc'];
                        break;
                    case 2:
                        $price = $num1 * $ratio['eth'];
                        break;
                    case 3:
                        $price = $num1 * $ratio['eos'];
                        break;
                    default:
                        return json(['code'=>1,'message'=>'参数错误']);
                }
                // 投注记录总表
                $aa1 = array(
                    'uid' => $userid,
                    'types' => 2,
                    'prize_number' => $topicid1,
                    'periods' => $date.'_'.$arr1['num'],
                    'award' =>0,
                    'total' => $num1,
                    'price' => $price,
                    'money_type' => $money_type,
                    'create_time' => time()
                    );

                $res1 = DB::table('game_lottery')->insert($aa1);

                $periods1 = $date.'_'.$arr1['num'];
                $this->setbonus($periods1,$num1,$money_type,$price);
                if($money_type == 1){
                    Db::table('percent_date')->where('date', $date)->where('num',$arr1['num'])->setInc('btc',$price);
                    $usdt = $num1 * $ratio['btc'];
                    Db::table('percent_date')->where('date', $date)->where('num',$arr1['num'])->setInc('usdt',$usdt);
                }elseif($money_type == 2){
                    Db::table('percent_date')->where('date', $date)->where('num',$arr1['num'])->setInc('eth',$price);   
                    $usdt = $num1 * $ratio['eth'];
                    Db::table('percent_date')->where('date', $date)->where('num',$arr1['num'])->setInc('usdt',$usdt);
                }elseif($money_type == 3){
                    Db::table('percent_date')->where('date', $date)->where('num',$arr1['num'])->setInc('eos',$price);
                    $usdt = $num1 * $ratio['eos'];
                    Db::table('percent_date')->where('date', $date)->where('num',$arr1['num'])->setInc('usdt',$usdt);  
                }
                // $res =$this->waitopen();
                // if($res == '1'){

                    return json([
                        'code' => 0,
                        'message' => '下注成功！',
                    ]);
                // }
                

            }

        }
        

    }

    /**
     * 区块ID
     *
     * 期数
     */
    public function test(){

        $url = "http://47.75.169.53/web4/index/blocknumber";
        // $url = 'http://lll.weixqq4.top/web3/index/GetBalance?address=0x73a473fef74813f2f3136f08d236e54f47fe8eef';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $balance = curl_exec($ch); // 已经获取到内容，没有输出到页面上。
        curl_close($ch);
        
        $balance = json_decode($balance,true);
        return $balance;

    }


    public function getcode($block_id){

        $url = "http://47.75.169.53/web4/index/blocknumber/checkblock/blockno/".$block_id;
        // $url = 'http://lll.weixqq4.top/web3/index/GetBalance?address=0x73a473fef74813f2f3136f08d236e54f47fe8eef';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $balance = curl_exec($ch); // 已经获取到内容，没有输出到页面上。
        curl_close($ch);
        
        $balance = json_decode($balance,true);

        return $balance['hash'];
       
    }

    // 开奖记录
    public function open_list(Request $request){

        $page = $request->post('page') ? $request->post('page'):1;
        $all = DB::table('percent_date')->where('rest_num',0)->where('status',1)->order('id desc')->select();
        
        $total = count($all);
        $list = DB::table('percent_date')->where('rest_num',0)->order('id desc')->limit(5)->page($page)->select();

        foreach ($list as $key => &$value) {
            $value['date_num'] = $value['date'].'_'.sprintf("%03d", $value['num']);
            if(!empty($value['finish_time'])){
                $value['finish_time'] = date('Y-m-d H:i:s',$value['finish_time']);
            }
        }
        
        return json([
                'code' => 0,
                'message' => '请求成功！',
                'info' =>$list,
                'total' => $total
            ]);
       

    }

    public function open_detail(Request $request){

        $ratio = Quotation::where('id',1)->find();

        $date_num = $request->post('date_num');
        // $date_num = '20181208_001';
        $date = explode('_',$date_num);
       
        $detail = DB::table('percent_winning_number')->where('periods',$date_num)->order('grade asc')->select();

        foreach ($detail as $key => &$value) {
            
            // 投注时用的哪种币
        $jilu = DB::table('game_lottery')->where('periods',$date_num)->select();
        
        foreach ($jilu as $k => $v) {
            $arr = explode(',',$v['prize_number']);

            if(in_array($value['winning_number'], $arr)){

                $value['money_type'] = $v['money_type'];
                // $touzhu_id = $value['id'];
            }
        }
        }
        unset($value);

        foreach ($detail as $key => &$value) {
            $value['nickname'] = DB::table('user')->where('id',$value['uid'])->value('nick_name');

            if($value['grade'] == 1){
                $value['grade'] = '一等奖';
            }elseif($value['grade'] == 2){
                $value['grade'] = '二等奖';
            }elseif($value['grade'] == 3){
                $value['grade'] = '三等奖';
            }elseif($value['grade'] == 4){
                $value['grade'] = '幸运奖';
            }
        }
   
        $detail1 = DB::table('percent_date')->where('date',$date[0])->where('num',$date[1])->find();

        $info = array(
            'final_time' => date('Y-m-d H:i:s',$detail1['final_time']),
            'end_height' => $detail1['block_id'],
            'open_height' => $detail1['block_id'] + 3,
            'blockcode' => $detail1['blockcode'],
            // $value['usdt'] = 
            'usdt' => $detail1['btc']/$ratio['btc'] + $detail1['eth']/$ratio['eth'] + $detail1['eos']/$ratio['eos'],
            'eth' => $detail1['eth'],
            'btc' => $detail1['btc'],
            'eos' => $detail1['eos']
            );

        return json([
                'code' => 0,
                'message' => '请求成功！',
                'info' =>$detail,
                'info2' => $info
            ]);
       
    }


    public function get_number($str)
    {

        // $str = '0da20ad7153312d1e923e339548a6d3685600c152ddac467558d07bb8cc02087';

        // 一等奖
        $firstcode = substr($str, -4,4);
        $first1 = hexdec($firstcode)%100; // 24

        if($first1 == 0){

                $first1 = 100;
            }
        $number['first'][] = $first1;
        $number['second'] =array();
        $number['third'] =array();

        $number['lucky'] =array();

        $count = 1;

        $c1 = 1;
        // 二等奖
        $info2 = DB::table('percent_set')->select();
        $num2 = $info2[1]['number'];

        while ($count <= $num2) {
            
            $secondcode = substr($str,(-4-$c1),4);
            $aa = hexdec($secondcode)%100;

            if($aa == 0){

                $aa = 100;
            }
            // if(!in_array($aa, $number['first']) && !in_array($aa, $number['second'])){
                $count++;
                $number['second'][] = $aa;

            // }

            $c1++;
        }
        
       
       // 三等奖

        $num3 = $info2[2]['number'];
        $count3 = 1;
        while ($count3 <= $num3) {
            
            $thirdcode = substr($str,(-4-$c1),4);
            $aa = hexdec($thirdcode)%100;

            if($aa == 0){

                $aa = 100;
            }
            // if(!in_array($aa, $number['first']) && !in_array($aa, $number['second']) && !in_array($aa, $number['third'])){
                $count3++;
                $number['third'][] = $aa;

            // }
          

            $c1++;


        }

        // 幸运奖
        $num4 = $info2[3]['number'];
        $count4 = 1;
        while ($count4 <= $num4) {
            
            $lucky = substr($str,(-4-$c1),4);
            $aa = hexdec($lucky)%100;

            if($aa == 0){

                $aa = 100;
            }
            // if(!in_array($aa, $number['first']) && !in_array($aa, $number['second']) && !in_array($aa, $number['third'])&& !in_array($aa, $number['lucky']) ){
                $count4++;
                $number['lucky'][] = $aa;

            // }
            

            $c1++;


        }
        
        return $number;

        // for ($i=1;$i<=$num2;$i++){

        //  $secondcode = substr($str,(-4-$i),4);

        //  $aa = hexdec($secondcode)%100;

        //  if(!in_array($aa, $number['first'])){
        //      $count++;
        //      $number['second'][] = $aa;
        //  }

        // }

        

    }


    public function waitopen()
    {

      
        // 最近一条未开奖
        $info = DB::table('percent_date')->where('rest_num',0)->where('status',0)->order('id asc')->find();
      
        if(empty($info)){
            return '2';
        }

        //获取比例
        $ratio = Quotation::where('id',1)->find();

        $usdt = $info['btc']/$ratio['btc'] + $info['eth']/$ratio['eth'] + $info['eos']/$ratio['eos'];

        DB::table('percent_date')->where('id',$info['id'])->update(['usdt'=>$usdt]);

        $info['num'] = sprintf("%03d", $info['num']);

        $date_num = $info['date'].'_'.$info['num'];

        // 查询有没有后台手动开奖
        $back = DB::table('percent_win')->where('date_num',$date_num)->where('status',1)->find();
       

        if(!empty($info['block_id']) && empty($back)){
        
            $blockcode = $this->getcode($info['block_id']+3);
            
            if(empty($blockcode)){

                return;
            }

            if(empty($info['blockcode'])){
                DB::table('percent_date')->where('id',$info['id'])->update(['blockcode'=>$blockcode]);

            }
            // 中奖号码
            $win_number = $this->get_number($blockcode);
          
            // $all_log = DB::table('game_lottery')->where('periods',$date_num)->select();
          
            $all_log = DB::table('percent_buylog')->where('date',$info['date'])->where('num',$info['num'])->select();
            
            
            foreach ($all_log as $key => &$value) {

                $value['buy_id'] = explode(',',$value['buy_id']);
                
                foreach ($value['buy_id'] as $k => $v) {
           
                    $num = array_count_values($win_number['lucky']);
                     
                    // 有没有中一等奖
                    if(in_array($v, $win_number['first'])){

                        $this->ward($value['userid'],1,$date_num,$v,1);

                    }

                    // 如果中二等奖
                    $second = array_count_values($win_number['second']);

                    if(in_array($v, $win_number['second'])){
                        for($i=1;$i<=$second[$v];$i++){
                            $this->ward($value['userid'],2,$date_num,$v,1);
                        }
                    }
                    $third = array_count_values($win_number['third']);

                    if(in_array($v, $win_number['third'])){

                        for($i=1;$i<=$third[$v];$i++){
                            $this->ward($value['userid'],3,$date_num,$v,1);
                        }
                    }

                    $lucky = array_count_values($win_number['lucky']);


                    if(in_array($v, $win_number['lucky'])){

                        for($i=1;$i<=$lucky[$v];$i++){

                            $this->ward($value['userid'],4,$date_num,$v,1);
                        }
 
                    }

                }
                unset($value);
            }  
            
        }

        // 如果有设置手动开奖
        if(!empty($back)){

            $blockcode = $this->getcode($info['block_id']+3);
            
            if(empty($blockcode)){

                return;
            }

            if(empty($info['blockcode'])){
                DB::table('percent_date')->where('id',$info['id'])->update(['blockcode'=>$blockcode]);

            }
      
            $all_log = DB::table('percent_buylog')->where('date',$info['date'])->where('num',$info['num'])->select();

            $win_number['first'] = explode(',',$back['first']);
            $win_number['second'] = explode(',',$back['second']);
            $win_number['third'] = explode(',',$back['third']);
            $win_number['lucky'] = explode(',',$back['lucky']);


            foreach ($all_log as $key => &$value) {
                $value['buy_id'] = explode(',',$value['buy_id']);
                
                foreach ($value['buy_id'] as $k => $v) {

                    // 有没有中一等奖
                    if(in_array($v, $win_number['first'])){

                        $this->ward($value['userid'],1,$date_num,$v,2);

                    }

                    // 如果中二等奖
                    $second = array_count_values($win_number['second']);

                    if(in_array($v, $win_number['second'])){
                        for($i=1;$i<=$second[$v];$i++){
                            $this->ward($value['userid'],2,$date_num,$v,2);
                        }
                    }
                    $third = array_count_values($win_number['third']);

                    if(in_array($v, $win_number['third'])){

                        for($i=1;$i<=$third[$v];$i++){
                            $this->ward($value['userid'],3,$date_num,$v,2);
                        }
                    }

                    $lucky = array_count_values($win_number['lucky']);


                    if(in_array($v, $win_number['lucky'])){

                        for($i=1;$i<=$lucky[$v];$i++){

                            $this->ward($value['userid'],4,$date_num,$v,2);
                        }
 
                    }
                    
                }
                unset($value);
            }

        }

        return '1';

    }

    public function ward($uid,$level,$date_num,$number,$type)
    {
       
         
        PeriodsBonus::where('periods',$date_num)->setField('status',3);
        PeriodsBonus::where('periods',$date_num)->setField('open_time',time());
        
        
        $ratio = Quotation::where('id',1)->find();
       
        // 投注时用的哪种币
        $jilu = DB::table('game_lottery')->where('periods',$date_num)->select();
        
        foreach ($jilu as $kk => $vv) {
            $arr = explode(',',$vv['prize_number']);

            if(in_array($number, $arr)){

                $money_type = $vv['money_type'];
                $touzhu_id = $vv['id'];
            }
        }
        DB::table('game_lottery')->where('periods',$date_num)->update(['status'=>2]);


        // 计算投注总额

        // 等级对应的奖励比例
        $date = explode('_',$date_num);
      
        $info = DB::table('percent_set')->where('id',$level)->find();
        
        // if(empty($info)){
        //     return;
        // }
        $bili = $info['value'] / $info['number'];
        
        // 计算奖励金额
        // $award = $bili * 100 
  
        switch ($money_type)
            {
                case 1:
                    $award_money = $bili * $ratio['btc'];
                    break;
                case 2:
                    $award_money = $bili* $ratio['eth'];
                    
                    break;
                case 3:
                    $award_money = $bili * $ratio['eos'];
                    
                    break;
                default:
                    return json(['code'=>1,'msg'=>'参数错误']);
            }
 
        // 奖金大于1 USDT，给上级返佣
        // 佣金设置
        $set = DB::table('bonus')->where('key','percent')->find();
 

        if($bili >= $set['num']){

            $user = new User;
            $pid = $user->getParentsInfo($uid);
            // $jiangli=0;
          
        
            $sum_1 = 0;
            if(!empty($pid)){                
                foreach ($pid as $key => $value) {
                   
                    if($key === 1){
                       $jiangli = $award_money * ($set['one']/100); 
                       $jiangli = retain_2($jiangli);

                    }elseif($key === 2){
                       $jiangli = $award_money * ($set['two']/100); 
                       $jiangli = retain_2($jiangli);
                   }elseif ($key ===3) {

                       $jiangli = $award_money * ($set['three']/100);                        
                       $jiangli = retain_2($jiangli);
                   }

                   $ward_arr = array(
                    'user_id' => $value['id'],
                    'types' => 2,
                    'from_user' => $uid,
                    'from_level' => 'L'.$key,
                    'game_types' => 1,
                    'date' => $date_num,
                    'prize' => $level,
                    'bonus' => $jiangli,
                    'money_type' => $money_type,
                    'create_time' => time()
                    );
                // if($ward_arr['bonus']){
                    $aa = DB::table('profit')->insert($ward_arr);
                // }
                
                    switch ($money_type)
                    {
                        case 1:
                            
                            $old = $this->getUserNum($value['id'],'btc')?$this->getUserNum($value['id'],'btc'):0;
                            Mywallet::where('user_id',$value['id'])->setInc('btc',$jiangli);
                            break;
                        case 2:
                            
                            $old = $this->getUserNum($value['id'],'eth')?$this->getUserNum($value['id'],'eth'):0;
                            Mywallet::where('user_id',$value['id'])->setInc('eth',$jiangli);
                            break;
                        case 3:
                            
                            $old = $this->getUserNum($value['id'],'eos')?$this->getUserNum($value['id'],'eos'):0;
                            Mywallet::where('user_id',$value['id'])->setInc('btc',$jiangli);
                            break;
                    }
                    Mywallet::where('user_id',$value['id'])->setField('update_time',time());
                    $walletLog = [
                        'user_id' => $value['id'],
                        'number' => $jiangli,
                        'old' => $old,
                        'new' => $old + $jiangli,
                        'remark' => '百分彩中奖分红',
                        'types' => 6,
                        'status' => 1,
                        'money_type' => $money_type,
                        'create_time' => time(),
                    ];
                    if ($jiangli > 0) {
                        MywalletLog::insert($walletLog);
                    }
                   $sum_1 += $jiangli;

                }
                
            }
            
            $award_money = $award_money-$sum_1;
        }

        switch ($money_type)
        {
            case 1: 
                $old = $this->getUserNum($uid,'btc')?$this->getUserNum($uid,'btc'):0;
                Mywallet::where('user_id',$uid)->setInc('btc',$award_money);
                break;
            case 2:
                
                $old = $this->getUserNum($uid,'eth')?$this->getUserNum($uid,'eth'):0;
                Mywallet::where('user_id',$uid)->setInc('eth',$award_money);
                break;
            case 3:
                
                $old = $this->getUserNum($uid,'eos')?$this->getUserNum($uid,'eos'):0;
                Mywallet::where('user_id',$uid)->setInc('btc',$award_money);
                break;
        }
        Mywallet::where('user_id',$uid)->setField('update_time',time());
        $walletLog = [
            'user_id' => $uid,
            'number' => $award_money,
            'old' => $old,
            'new' => $old + $award_money,
            'remark' => '百分彩中奖',
            'types' => 1,
            'status' => 1,
            'money_type' => $money_type,
            'create_time' => time(),
        ];
        if ($award_money > 0) {
            MywalletLog::insert($walletLog);
        }

        $ward_arr1 = array(
        'user_id' => $uid,
        'types' => 1,
        'game_types' => 1,
        'date' => $date_num,
        'prize' => $level,
        'bonus' => $award_money,
        'money_type' => $money_type,
        'create_time' => time()
        );
       DB::table('profit')->insert($ward_arr1);

        Db::table('game_lottery')->where('id', $touzhu_id)->setInc('award',$award_money);    
        // 消息提示
        $message = array(
            'uid' => $uid,
            'date_num' => $date_num,
            'level' => $level,
            'money' => $award_money,
            'type' => $money_type,
            'create_time' => time()
            );
        DB::table('percent_message')->insert($message);
        // 开奖记录
        $arr = array(
            'uid' => $uid,
            'tid' => $touzhu_id,
            'periods' => $date_num,
            'grade' => $level,
            // (中奖金额，待修改)
            'award' => $award_money, 
            'winning_number' => $number,
            'types' => $type,
            'create_time' => time()
        );

        $result = DB::table('percent_winning_number')->insert($arr);

        DB::table('percent_date')->where('date',$date[0])->where('num',$date[1])->update(['status'=>1,'finish_time'=>time()]);

         //修改投注表状态为已开奖
        lotteryModel::where('types',2)
            ->where('periods',$date_num)
            ->update(['status'=>2]);
    }


    public function getMessage(Request $request){

        $id = $request->post('data');

        if(!empty($id)){

            DB::table('percent_message')->where('id',$id)->update(['status'=>1]);
        }
        $message = DB::table('percent_message')->where('status',0)->select();
        $id = $this->userId;
        return json([
                'code' => 0,
                'message' => '请求成功！',
                'info' =>$message,
                'uid' =>$id,
            ]);
    }
    public function demo(Request $request)
    {
        $data = $request->post('data');
        $list = json_decode($data);
        $id =$list->uid;
        $data_list = $list->info;
        foreach ($data_list as $v){
            if($v->uid == $id){
                $vo[] = $v;
            }
        }

    }


    public function setbonus($periods,$number,$money_type,$price){

        //获取比例
        $ratio = Quotation::where('id',1)->find();

        switch ($money_type)
        {
            case 1:
                $money1 = $price * $ratio['btc'];
                break;
            case 2:
                $money1 = $price * $ratio['eth'];
                break;
            case 3:
                $money1 = $price * $ratio['eos'];
                break;
            default:
                return json(['code'=>1,'message'=>'参数错误']);
        }
        $info = PeriodsBonus::where('periods',$periods)->find();

        if(empty($info)){

            $total_btc = 0;
            $total_eth = 0;
            $total_eos = 0;
            switch ($money_type)
            {
                case 1:
                    $total_btc = $price;
                    break;
                case 2:
                    $total_eth = $price;

                    break;
                case 3:
                    $total_eos = $price;

                    break;
                default:
                    return json(['code'=>1,'message'=>'参数错误']);
            }
            $arr = array(
                'types'=>2,
                'periods' => $periods,
                'number' => $number,
                'total' => $money1,
                'total_btc'=>$total_btc,
                'total_eth'=>$total_eth,
                'total_eos'=>$total_eos,
                'create_time'=>time()
                );
            $add_result = PeriodsBonus::insert($arr);

        }else{

             switch ($money_type)
            {
                case 1:
                    $total_btc = $price;
                    $sum = $info['total_btc']+$total_btc;
                    PeriodsBonus::where('periods',$periods)->setField('total_btc',$sum);
                    $money1 = $price * $ratio['btc'];
                    PeriodsBonus::where('periods',$periods)->setField('total',$info['total']+$money1);

                    break;
                case 2:
                    $total_eth = $price;
                    $sum = $info['total_eth']+$total_eth;
                    PeriodsBonus::where('periods',$periods)->setField('total_eth',$sum);
                    $money1 = $price * $ratio['eth'];
                    PeriodsBonus::where('periods',$periods)->setField('total',$info['total']+$money1);

                    break;
                case 3:
                    $total_eos = $price;
                    $sum = $info['total_eos']+$total_eos;
                    PeriodsBonus::where('periods',$periods)->setField('total_eos',$sum);
                    $money1 = $price * $ratio['eos'];
                    PeriodsBonus::where('periods',$periods)->setField('total',$info['total']+$money1);
                    break;
                default:
                    return json(['code'=>1,'message'=>'参数错误']);
            }

            PeriodsBonus::where('periods',$periods)->setField('number',$info['number']+$number);
            PeriodsBonus::where('periods',$periods)->setField('update_time',time());


        }

    }

    public function getdata($date,$num){

        $ratio = Quotation::where('id',1)->find();

        $num = sprintf("%03d", $num);
    	$periods = $date.'_'.$num;
        $data = DB::table('game_lottery')->where('periods',$periods)->order('id desc')->select();
        
  
        foreach ($data as $key => &$value) {
        	$value['btc'] = DB::table('percent_date')->where('date',$date)->where('num',$num)->value('btc');
	    	$value['eth'] = DB::table('percent_date')->where('date',$date)->where('num',$num)->value('eth');
	    	$value['eos'] = DB::table('percent_date')->where('date',$date)->where('num',$num)->value('eos');
     
        	$value['nickname'] = DB::table('user')->where('id',$value['uid'])->value('nick_name');
        	$value['create_time'] = date('Ymd-H:i:s',$value['create_time']);

            $value['usdt'] = $value['btc']/$ratio['btc'] + $value['eth']/$ratio['eth'] + $value['eos']/$ratio['eos'];
        	
        	
}

        unset($value);

        return json([
                'code' => 0,
                'message' => '请求成功！',
                'info' =>$data,
                
            ]);
        
    }
}

