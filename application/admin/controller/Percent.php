<?php

namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\common\entity\Lottery as LotteryModel;
use app\common\entity\PercentWinningNumber;

use app\common\entity\User as userModel;



use think\Db;
use think\Request;
use app\common\entity\Export;
use common\entity\YekesLog;



class Percent extends Admin
{ 

	public function set(Request $request)
    {   	

        $result = DB::table('percent_set')->order('id asc')->select();
        


        // $info = $FomoConfig->where('key','path')->select();
        // $info  = $info[0];
        return $this->render('set',['list'=> $result]);

    	// return $this->render('set', [
     //        'list' => $FomoConfig->where('type', 1)->where('status',1)->select(),
     //        'info'=> $info,
     //    ]);
    }



    /**
     * 添加设置
     */
    public function setadd(Request $request)
    {

    	$data['name'] = $request->post('name');

    	$data['key'] = $request->post('key');

    	$data['number'] = $request->post('number');

    	$data['value'] = $request->post('value');
   
        $result = DB::table('percent_set')->insert($data);


        if (!$result) {
            return ['code'=>1,'message'=>'添加失败'];
        }
        return ['code' => 0, 'message' => '添加成功'];
    	// $data['']


    }

    /**
     * 更改
     */
    public function setsave(Request $request)
    {

    	$id = $request->post('id');

        $result = DB::table('percent_set')->where('key',$id)->find();
        
        if (!$result) {
            throw new AdminException('操作错误');
        }

    	$value = $request->post('value');

    	$number = $request->post('number');
    	$log = array(

    		'value' => $value,

    		'number' => $number

    		);
    
    	$res = Db::table('percent_set')->where('key',$id)->update($log);

    	if(!$res){

        	return ['code' => 1, 'message' => '修改失败'];

    	}

        return ['code' => 0, 'message' => '修改成功'];



    }

    public function buy_log(Request $request){
  
        $list = $this->logsearch($request);
   
      
        foreach ($list as $key => &$value) {

            $value['periods'] = strtr($value['periods'],'_','-');
            if($value['money_type'] == 1){
                $value['money_type'] = 'BTC';
            }else if($value['money_type'] == 2){
                $value['money_type'] ='ETH';
            }else if($value['money_type'] == 3){
                $value['money_type'] = 'EOS';
            }
        }
        return $this->render('', [
            'list' => $list
        ]);

    }


    /**
     * 各期投注查询
     */
    public function logsearch($request)
    {
        $query = LotteryModel::alias('lm')->field('lm.*,u.nick_name,u.email')->where('types',2);
        if ($status = $request->get('status')) {
            $query->where('lm.status', $status);
            $map['lm.status'] = $status;
        }
        if ($keyword = $request->get('keyword')) {
          
            $type = $request->get('type');
            switch ($type) {
                case 'periods':
                    $keyword = substr($keyword, 0, 1);

                    $keyword = strtr($keyword,'-','_');
                    $query->where('lm.periods','like', '%'.$keyword.'%');
                    break;
                case 'email':
                    $query->where('u.email','like', '%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');

        if($startTime && $endTime){
            $query->where('lm.create_time', '<', strtotime($endTime))
                ->where('lm.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $userTable = (new userModel())->getTable();
        $list = $query
            ->leftJoin("$userTable u", 'u.id = lm.uid')
            ->order('create_time', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        return $list;
    }


    public function open(Request $request){

        // $list = DB::table('percent_winning_number')->order('id desc')->select();

        // $user = new userModel;
            
        $list = $this->winningsearch($request);
        $user = new userModel();
        foreach ($list as $key => &$value) {
            // 投注时用的哪种币
            $jilu = DB::table('game_lottery')->where('periods',$value['periods'])->select();

            foreach ($jilu as $k1 => $v1) {
                $arr = explode(',',$v1['prize_number']);

                if(in_array($value['winning_number'], $arr)){

                    $value['money_type'] = $v1['money_type'];
                    // $touzhu_id = $value['id'];
                }
            }
            $date = explode('_',$value['periods']);

            $value['date'] = $value['periods'];
           $value['periods'] = strtr($value['periods'],'_','-');

           $value['nickname'] = DB::table('user')->where('id',$value['uid'])->value('nick_name');
           
           // $pid = (new userModel())->getParentsInfo($value['uid']);
           
            $pid = $user->getParentsInfo($value['uid']);
            // return json($pid);
            $value['height'] = DB::table('percent_date')->where('date',$date[0])->where('num',$date[1])->value('block_id');
            
           if(!empty($pid)){

            foreach ($pid as $k => $v) {
                    
                if($k == 1){
                    $value['level1'] = DB::table('user')->where('id',$v['id'])->value('nick_name');
                                                                            // 96              
                    $value['ward1'] = DB::table('profit')->where('user_id',$v['id'])->where('types',2)->where('date',$value['date'])->where('prize',$value['grade'])->value('bonus');
                }else if($k == 2){
                    $value['level2'] = DB::table('user')->where('id',$v['id'])->value('nick_name');
                    $value['ward2'] = DB::table('profit')->where('user_id',$v['id'])->where('types',2)->where('date',$value['date'])->where('prize',$value['grade'])->value('bonus');

                }else if($k == 3){
                    $value['level3'] = DB::table('user')->where('id',$v['id'])->value('nick_name');
                    $value['ward3'] = DB::table('profit')->where('user_id',$v['id'])->where('types',2)->where('date',$value['date'])->where('prize',$value['grade'])->value('bonus');

                }
            }

                   

           }

        // return json($value['uid']);
                  
        }
        unset($value);

        return $this->render('open', [
            'list' => $list
        ]);

    }

    /**
     * 各期奖号查询
     */
    public function winningsearch($request)
    {
        $query = PercentWinningNumber::alias('lwn')->field('lwn.*');
    
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'periods':
                    $keyword = substr($keyword, 0, 1);
                    $keyword = strtr($keyword,'-','_');
                    $query->where('lwn.periods','like', '%'.$keyword.'%');
                    break;
                case 'grade':
                    $query->where('lwn.grade','like', '%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');

        if($startTime && $endTime){
            $query->where('lwn.create_time', '<', strtotime($endTime))
                ->where('lwn.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $query
            ->order('id', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
//        dump($query->getLastSql());
//        die;
        return $list;
    }
    public function open_set()
    {

        $result = DB::table('percent_win')->order('id desc')->select();
        foreach ($result as $key => &$value) {
           
           $value['date_num'] = strtr($value['date_num'],'_','-');
           $value['date_num'] = 'H'.$value['date_num'];

        }
        return $this->render('', [
            'list' => $result
        ]);

    }

    public function open_add(Request $request)
    {

        $data['date_num'] = substr($request->post('name'), 0, 1);

        $data['date_num'] = strtr($data['date_num'],'-','_');
     
        $data['first'] = $request->post('first');

        $data['second'] = $request->post('second');

        $data['third'] = $request->post('third');

        $data['lucky'] = $request->post('lucky');

        $date['addtime'] = time();
   
        $result = DB::table('percent_win')->insert($data);


        if (!$result) {
            return ['code'=>1,'message'=>'添加失败'];
        }
        return ['code' => 0, 'message' => '添加成功'];
        // $data['']


    }

    public function setsave_open(Request $request)
    {
       
        $id = $request->post('id');

        $result = DB::table('percent_win')->where('id',$id)->find();
        
        if (!$result) {
            throw new AdminException('操作错误');
        }


        $data = $request->post();
        
    
        $res = Db::table('percent_win')->where('id',$id)->update($data);

        if(!$res){

            return ['code' => 1, 'message' => '修改失败'];

        }

        return ['code' => 0, 'message' => '修改成功'];



    }

    public function open_update($id,$status)
    {

        $info = DB::table('percent_win')->where('id',$id)->find();

        if(!$info){

            throw new AdminException('操作错误');

        }
        $data['status']=$status;
        $res = DB::table('percent_win')->where('id',$id)->update($data);
        if(!$res){

            return ['code' => 1, 'message' => '修改失败'];

        }

        return ['code' => 0, 'message' => '修改成功'];

    }


    public function betting_set(){

        $result = DB::table('percent_betting_set')->order('id desc')->select();
        
        return $this->render('', [
            'list' => $result
        ]);
    }

    public function bettingsetAdd(Request $request){

        $data['name'] = $request->post('name');
        $data['key'] = $request->post('key');
        $data['value'] = $request->post('value');

        $result = DB::table('percent_betting_set')->insert($data);

        if (!$result) {
            return ['code'=>1,'message'=>'添加失败'];
        }
        return ['code' => 0, 'message' => '添加成功'];

    }

    public function bettingsetSave(Request $request){
        $key = $request->post('id');
        
        $info = DB::table('percent_betting_set')->where('key',$key)->find();

        if(!$info){

            throw new AdminException('操作错误');

        }
       
        $value = $request->post('value');
        $log = array(
            'value' => $value,
        );
        $res = DB::table('percent_betting_set')->where('key',$key)->update($log);
        if(!$res){

            return ['code' => 1, 'message' => '修改失败'];

        }

        return ['code' => 0, 'message' => '修改成功'];
    }
    /**
     * 区块ID
     *
     * 期数
     */
    public function test(Request $request){

        $url = "http://47.52.229.43/index/blocknumber";
        // $url = 'http://lll.weixqq4.top/web3/index/GetBalance?address=0x73a473fef74813f2f3136f08d236e54f47fe8eef';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $balance = curl_exec($ch); // 已经获取到内容，没有输出到页面上。
        curl_close($ch);
        
        $balance = json_decode($balance,true);

    }


    // public function test11(Request $request){

    //     $url = "http://47.52.229.43/index/blocknumber/checkblock/blockno/6835771";
    //     // $url = 'http://lll.weixqq4.top/web3/index/GetBalance?address=0x73a473fef74813f2f3136f08d236e54f47fe8eef';
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //     $balance = curl_exec($ch); // 已经获取到内容，没有输出到页面上。
    //     curl_close($ch);
        
    //     $balance = json_decode($balance,true);
    //     echo "<pre>";
    //         print_r($balance);
    //     echo "</pre>";die;

    //     $str = substr($balance['hash'],-2);

    //     echo "<pre>";
    //         print_r($str);
    //     echo "</pre>";die;

    // }

    // public function get_number()
    // {

    //     $str = '0x6296ce6d438bd2fe86e0bd27e8b1406ff0ed6638a69a6c379a80792b814728b8';

    //     // 一等奖
    //     $firstcode = substr($str, -4,4);
    //     $first = hexdec($firstcode)%100;
        
        

    // }

 }



