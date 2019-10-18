<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/14
 * Time: 16:41
 */

namespace app\index\controller;


use app\common\entity\MywalletLog;
use app\common\entity\Mywallet;
use app\common\entity\Profit;
use app\common\entity\Proportion;
use app\common\entity\Quotation;
use app\common\entity\User;
use app\common\entity\Config;
use app\common\entity\WithdrawLog;
use app\common\entity\YekesConfig;
use app\common\entity\YekesLog;
use app\index\model\Publics as PublicModel;
use think\Db;
use think\Request;

class Fish extends Base
{

    /**
     * 我的酒塘
     * @return \think\response\Json
     */

    public function index()
    {
        $uid = $this->userId;

        $page = input('post.page') ? input('post.page') : 1;
        $limit = input('post.limit') ? input('post.limit') : 15;
        $PublicModel = new  PublicModel;

        $user_info = $PublicModel->getWallet($uid);
        $info['mybait'] = empty($user_info['now']) ? 0 : $user_info['now'];//现有GTC
        $info['list'] = array();
        $list = DB::table('fish')
            ->alias('f')
            ->join('bathing_pool bp', 'bp.id = f.pool_id')
            ->where('bp.is_delete', '0')
            ->where('f.is_delete', '0')
            ->where('f.is_show', '1')
            ->where('f.status', 'in', '0,1,2,3')
            ->where('f.u_id', $uid)
            ->field('f.is_re,f.types,f.front_name,f.create_time,f.re_overtime,f.contract_overtime,f.is_lock,f.is_status,f.all_time,f.id,f.is_contract,f.worth,f.is_lock_num,f.is_contract,f.lock_time,bp.name,bp.img,bp.lock_position,bp.contract_time,bp.remarks,bp.profit,bp.bait,f.worth,f.all_time,f.lock_overtime,f.re_overtime')
            ->page($page)
            ->paginate($limit)
            ->toArray();


        if (!empty($list['data'])) {
            $list = $list['data'];

            $re = array();
            foreach ($list as $k => $v) {

                $re[$k]['name'] = $v['name']; //酒馆名
                $re[$k]['en_name'] = getPoolEnName($v['name']); // 酒馆英文名
//                0：后台赠送正常流程； 1：拆分生成； 2：升级生成 3：后台指定 ;4:交易生成；5积分 ；6后台赠送即卖
//                if($v['types'] == 0){
//                    $re[$k]['name'] .='-后台赠送-'.$v['front_name'];
//                }elseif ($v['types'] == 1){
//                    $re[$k]['name'] .='-拆分-'.$v['front_name'];
//                }elseif ($v['types'] == 2){
//                    $re[$k]['name'] .='-升级-'.$v['front_name'];
//                }elseif ($v['types'] == 3){
//                    $re[$k]['name'] .='-后台指定-'.$v['front_name'];
//                }elseif ($v['types'] == 4){
//                    $re[$k]['name'] .='-交易生成-'.$v['front_name'];
//                }elseif ($v['types'] == 5){
//                    $re[$k]['name'] .='-积分-'.$v['front_name'];
//                }elseif ($v['types'] == 6){
//                    $re[$k]['name'] .='-赠送即卖-'.$v['front_name'];
//                }

                $order_worth = $this->getOrderWorth($v['id']); //获取购买时的价格
                $re[$k]['types'] = $v['types'];
                $re[$k]['img'] = $v['img'];   //酒馆图
                $re[$k]['worth'] = $v['worth'];   //价值
                $re[$k]['buy_worth'] = $order_worth == 0 ? $v['worth'] : $order_worth;   //购买价格
                $re[$k]['sell_worth'] = floor($re[$k]['buy_worth'] * (1+ $v['profit']/100));   //出售价格 = 购买价格+合约收益(向下取整)
                $re[$k]['is_lock'] = 0;         //是否锁仓
                $re[$k]['is_re'] = $v['is_re'];     //是否返池的酒
                $re[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);     //生成时间
                $re[$k]['contract_time'] = $v['contract_time'];     //合约时间
                $re[$k]['profit'] = $v['profit'];     //合约
                $re[$k]['bait'] = $v['bait'];
                $re[$k]['contract_day'] = round($v['contract_time'] / 24, 2);
                $re[$k]['id'] = $v['id'];
                $re[$k]['is_lock_num'] = $v['is_lock_num'];
                $re[$k]['create_age'] = get_fagetime($v['id']);     //酒龄
                $re[$k]['remarks'] = $v['remarks'];

                $re[$k]['over_contract_day'] = 0;
                $re[$k]['over_profit'] = 0;


                if ($v['is_status'] == 2) {
                    $types = 3;//重返酒馆
                } elseif ($v['is_lock_num'] > 0 && $v['is_contract'] == 1) {
                    $types = 2; //锁仓
                } else {
                    $types = 1;//合约养殖
                }

                $re[$k]['is_contract'] = Db::table('fish_feed_log')
                    ->where('fid', $v['id'])
                    ->where('stime', '<', time())
                    ->where('ntime', '>', time())         //是否品酒
                    ->where('types', $types)
                    ->value('is_feed');
                if (empty($re[$k]['is_contract'])) {
                    $re[$k]['is_contract'] = 0;
                }

                $Multiple = $v['lock_position'];//锁仓倍数

                $ftypes = get_fish_type($v['id']);
//                dump($ftypes);exit;
                switch ($ftypes) {
                    case 4:
                        //即卖
                        $re[$k]['over_contract_day'] = 1;  //已品酒时间（天）
                        $re[$k]['contract_day'] = 1;       //合约天数
                        $re[$k]['over_profit'] = 0;       //完成合约

                        $re[$k]['is_contract'] = 1;         //是否品酒


                        break;
                    case 31:
                        //重返酒馆 完成
                        $re[$k]['contract_time'] = 24;

                        $re[$k]['contract_day'] = 1;
                        $re[$k]['over_contract_day'] = get_feed_num($v['id'], $v['contract_time'] / 24, 1,$v['create_time']);  //已品酒时间（天）
                        $re[$k]['profit'] = retain_2($v['profit'] / ($v['contract_time'] / 24));                 //收益
                        $re[$k]['over_profit'] = $re[$k]['profit'];       //完成合约

                        $re[$k]['is_contract'] = 1;         //是否品酒
                        break;
                    case 3:
                        //重返酒馆
                        $re[$k]['contract_time'] = 24;
                        $re[$k]['contract_day'] = 1;
                        $re[$k]['over_contract_day'] = 0;  //已品酒时间（天）
                        $re[$k]['profit'] = retain_2($v['profit'] / ($v['contract_time'] / 24));                 //收益
                        $re[$k]['over_profit'] = 0;


                        $re[$k]['contract_time'] = 24;   //品酒所需时间（小时）
                        $re[$k]['bait'] = $v['bait'];                   //所需GTC


                        break;
                    case 21:
                        $re[$k]['contract_day'] = $re[$k]['contract_day'] * $Multiple;

                        //锁仓 完成
                        $re[$k]['over_contract_day'] = $re[$k]['contract_day'];  //已品酒时间（天）
                        $re[$k]['contract_time'] = $re[$k]['contract_time'] * $Multiple;

                        $re[$k]['profit'] = $re[$k]['profit'] * $Multiple;
                        $re[$k]['over_profit'] = $re[$k]['profit'];


                        $re[$k]['is_lock'] = 1;


                        $re[$k]['is_contract'] = 1;         //是否品酒

                        break;
                    case 2:
                        //锁仓

                        $re[$k]['contract_day'] = $re[$k]['contract_day'] * $Multiple;
                        $re[$k]['over_contract_day'] = get_feed_num($v['id'], $v['contract_time'] / 24, $Multiple,$v['create_time']);  //已品酒时间（天）

                        $re[$k]['contract_time'] = $re[$k]['contract_time'] * $Multiple;

                        $re[$k]['profit'] = $re[$k]['profit'] * $Multiple;
                        if ($re[$k]['contract_day'] != 0) {
                            $tover = $re[$k]['over_contract_day'] / $re[$k]['contract_day'];
                        } else {
                            $tover = 0;
                        }

                        if (empty($tover)) {
                            $re[$k]['over_profit'] = 0;
                        } else {

                            $re[$k]['over_profit'] = $re[$k]['profit'] * $tover;
                        }
//                        $re[$k]['over_profit'] = $re[$k]['profit'] / ($re[$k]['over_contract_day']/ $re[$k]['contract_day']);

                        $re[$k]['bait'] = $v['bait'] * $Multiple;                   //所需GTC

                        $re[$k]['is_lock'] = 1;
                        $re[$k]['is_lock_test'] = '到了锁仓';

                        $c = Db::table('fish_feed_log')
                            ->where('fid', $v['id'])
                            ->where('stime', '<', time())
                            ->where('ntime', '>', time())         //是否品酒
                            ->where('types', 1)
                            ->value('is_feed');
                        if ($c) {
                            $re[$k]['is_contract'] = 1;         //是否品酒
                        }

                        break;

                    case 11:
                        //合约养殖 完成
                        $re[$k]['over_contract_day'] = $re[$k]['contract_day'];  //已品酒时间（天）
                        $re[$k]['over_profit'] = $re[$k]['profit'];

                        $re[$k]['is_contract'] = 1;         //是否品酒
                        break;
                    case 1:
                        //合约养殖
                        $re[$k]['over_contract_day'] = get_feed_num($v['id'], $re[$k]['contract_day'], 1,$v['create_time']);  //已品酒时间（天）

                        if ($re[$k]['contract_day'] != 0) {
                            $tover = $re[$k]['over_contract_day'] / $re[$k]['contract_day'];
                        } else {
                            $tover = 0;
                        }

                        if (empty($tover)) {
                            $re[$k]['over_profit'] = 0;
                        } else {

                            $re[$k]['over_profit'] = $re[$k]['profit'] * $tover;
                        }

                        break;

                }

                if ($ftypes == 1 || $ftypes == 11) {

//                    点击了锁仓
                    if ($v['is_lock_num']) {
                        $re[$k]["contract_day"] = $re[$k]["contract_day"] * $Multiple;
                        $re[$k]["profit"] = $re[$k]["profit"] * $Multiple;
                        $re[$k]["contract_time"] = $re[$k]["contract_time"] * $Multiple;
                        $re[$k]["is_lock"] = 1;
                    }
                    if ($v['is_lock_num'] == 0 && $v['is_contract'] == 1) {
                        $re[$k]['is_contract'] = 1;         //是否品酒
                    }


                }

                if ($re[$k]['create_age'] > $re[$k]['contract_time']) {
                    $re[$k]['create_age'] = $re[$k]['contract_time'];
                }

                // $re[$k]['over_contract_day'] = floor($re[$k]['over_contract_day']);
                $re[$k]["id"] = $v['id'];
                $re[$k]["ftypes"] = $ftypes;


            }


            $info['list'] = $re;

            return json(['code' => 0, 'msg' => '获取成功', 'info' => $info]);
        }

        return json(['code' => 0, 'msg' => '暂无数据', 'info' => $info]);

    }

    /**
     * 根据fid获取购买价格，注：赠送的房子价格固定返回0
     *
     * @param [type] $fid
     * @return void
     */
    public function getOrderWorth($fid)
    {
        $appointment_user_oid = Db::table('appointment_user')->where('new_fid', $fid)->value('oid');
        if (!empty($appointment_user_oid)) {
            $order_worth = Db::table('fish_order')->where('id', $appointment_user_oid)->value('worth');
            if (empty($order_worth)) {
                return 0;
            }
            return $order_worth;
        }
        return 0;
    }





    /*
    public function index(){

        $uid = $this->userId;

        $page = input('post.page')?input('post.page'):1;
        $limit = input('post.limit')?input('post.limit'):15;
        $PublicModel = new  PublicModel;

        $user_info = $PublicModel->getWallet($uid);
        $info['mybait'] = empty($user_info['now'])?0:$user_info['now'];//现有GTC
        $info['list'] = array();
        $list = DB::table('fish')
            ->alias('f')
            ->join('bathing_pool bp','bp.id = f.pool_id')
            ->where('bp.is_delete','0')
            ->where('f.is_delete','0')
            ->where('f.is_show','1')
            ->where('f.status','in','0,1,2,3')
            ->where('f.u_id',$uid)
            ->field('f.is_re,f.types,f.front_name,f.create_time,f.re_overtime,f.contract_overtime,f.is_lock,f.is_status,f.all_time,f.id,f.is_contract,f.worth,f.is_lock_num,f.is_contract,f.lock_time,bp.name,bp.img,bp.lock_position,bp.contract_time,bp.profit,bp.bait,f.worth,f.all_time,f.lock_overtime,f.re_overtime')
            ->page($page)
            ->paginate($limit)
            ->toArray();



        if (!empty($list['data'])){
            $list = $list['data'];

            $re =array();
            foreach ($list as $k => $v){



                $service = new \app\common\service\Fish\Service();


                $re[$k]['name'] = $v['name']; //酒馆名
//                0：后台赠送正常流程； 1：拆分生成； 2：升级生成 3：后台指定 ;4:交易生成；5积分 ；6后台赠送即卖
                if($v['types'] == 0){
                    $re[$k]['name'] .='-后台赠送-'.$v['front_name'];
                }elseif ($v['types'] == 1){
                    $re[$k]['name'] .='-拆分-'.$v['front_name'];
                }elseif ($v['types'] == 2){
                    $re[$k]['name'] .='-升级-'.$v['front_name'];
                }elseif ($v['types'] == 3){
                    $re[$k]['name'] .='-后台指定-'.$v['front_name'];
                }elseif ($v['types'] == 4){
                    $re[$k]['name'] .='-交易生成-'.$v['front_name'];
                }elseif ($v['types'] == 5){
                    $re[$k]['name'] .='-积分-'.$v['front_name'];
                }elseif ($v['types'] == 6){
                    $re[$k]['name'] .='-赠送即卖-'.$v['front_name'];
                }



                $re[$k]['types'] = $v['types'];
                $re[$k]['img'] = $v['img'];   //酒馆图
                $re[$k]['worth'] = $v['worth'];   //价值
                $re[$k]['is_lock'] = 0;         //是否锁仓
                $re[$k]['is_add_lock'] = 0;     //是否满足锁仓要求
                $re[$k]['is_re'] = $v['is_re'];     //是否返池的酒
                $re[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);     //生成时间

                if($v['is_re']){

                    $re[$k]['contract_time'] =  24;   //品酒所需时间（小时）
                    $re[$k]['profit'] = retain_2($v['profit']/($v['contract_time']/24));                 //收益
                    $re[$k]['bait'] =   $v['bait'];                   //所需GTC

                }else{
                    $re[$k]['contract_time'] = $v['contract_time'];         //品酒所需时间
                    $re[$k]['profit'] = $v['profit'];                       //收益
                    $re[$k]['bait'] =   $v['bait'];                         //所需GTC
                }

                $re[$k]['contract_day'] = $re[$k]['contract_time'] / 24;    //品酒所需时间（天）
                $re[$k]['id'] = $v['id'];


                $service = new \app\common\service\Fish\Service();

//                $times = $v['re_overtime']+$v['lock_overtime']+$v['contract_overtime'];
                  $times = $service->get_all_feed_time($v['id']);

//                $over_contract_time = (time() - $v['create_time']) / 3600;//酒龄

                $over_contract_time = $times;
                $over_contract_time = intval($over_contract_time);



                if($v['types'] == 6 && $v['is_status'] == 1){ //即卖
//                    $re[$k]['over_contract_time'] = 0; //已品酒时间（小时）
                    $re[$k]['over_contract_day']  = 0;  //已品酒时间（天）
                    $re[$k]['is_contract']  = 1;         //是否品酒
                    $re[$k]['over_profit']  = 0;


                }elseif($v['is_status'] == 1){

//                    $re[$k]['over_contract_time'] = $over_contract_time; //已品酒时间（小时）

                    $re[$k]['over_contract_day']  = get_feed_num($v['id'],$v['contract_time']/24,1);  //已品酒时间（天）
                    $re[$k]['is_contract']  = 1;         //是否品酒

                }else{
//                    $re[$k]['over_contract_time'] =  $over_contract_time; //已品酒时间（小时）

                    $re[$k]['over_contract_day']  =  get_feed_num($v['id'],$v['contract_time']/24,1);  //已品酒时间（天）

                    $re[$k]['is_contract']  =Db::table('fish_feed_log')
                        ->where('fid',$v['id'])
                        ->where('stime','<',time())
                        ->where('ntime','>',time())         //是否品酒
                        ->value('is_feed');

                    if(empty( $re[$k]['is_contract'])){
                        $re[$k]['is_contract'] = 0;
                    }
                }





                $re[$k]['over_profit']  = $re[$k]['profit'] * ( $times/$re[$k]['contract_time']);         //已得收益
                if($v['types'] == 6 && $v['is_status'] == 1){ //即卖
                    $re[$k]['over_profit'] = 0;

                }else{
                    $re[$k]['over_profit'] = retain_2($re[$k]['over_profit']);
                }
                $re[$k]['is_lock_num'] = $v['is_lock_num'];
                if( $v['is_contract'] < 1 && $v['is_lock_num'] == 0 ){
                    $re[$k]['is_add_lock'] = 1;

                }
                if($v['is_lock_num']>0 && $v['is_re'] == 0){
                    $Multiple = $v['lock_position'];//锁仓倍数
                    $re[$k]['contract_time'] = $v['contract_time'] * $Multiple;       //品酒所需时间（小时）
                    $re[$k]['contract_day'] = ($v['contract_time']/24) * $Multiple;   //品酒所需时间（天）
                    $re[$k]['over_contract_day']  =  get_feed_num($v['id'],$v['contract_time']/24,$Multiple);  //已品酒时间（天）
                    $re[$k]['over_profit'] = $re[$k]['profit'];


                    $re[$k]['profit'] = $v['profit']*$Multiple;                 //收益
                    $re[$k]['bait'] =   $v['bait']*$Multiple;                   //所需GTC
                    $re[$k]['is_lock'] =  1;
                    $re[$k]['is_lock_test'] =  '到了锁仓';

                }else{
                    if($re[$k]['contract_day'] > $re[$k]['over_contract_day']){
                        $re[$k]['over_contract_day'] = 0;
                        $re[$k]['over_profit'] = 0;
                    }else{
                        $re[$k]['over_contract_day'] = $re[$k]['contract_day'];
                        $re[$k]['over_profit'] = $re[$k]['profit'];
                    }
                }




                $re[$k]['create_age'] = (time() - $v['create_time']) / 3600;     //酒龄
                $re[$k]['create_age'] = intval($re[$k]['create_age'] );     //酒龄
                if($re[$k]['create_age'] > $re[$k]['contract_time']){
                    $re[$k]['create_age'] =  $re[$k]['contract_time'];
                }
            }




            $info['list'] = $re;

            return json(['code' => 0, 'msg' => '获取成功' , 'info' => $info ]);
        }

        return json(['code' => 0, 'msg' => '暂无数据', 'info' => $info ]);

    }
*/

    /*
        public function index(){

            $uid = $this->userId;

            $page = input('post.page')?input('post.page'):1;
            $limit = input('post.limit')?input('post.limit'):15;
            $PublicModel = new  PublicModel;

            $user_info = $PublicModel->getWallet($uid);
            $info['mybait'] = empty($user_info['now'])?0:$user_info['now'];//现有GTC
            $info['list'] = array();
            $list = DB::table('fish')
                ->alias('f')
                ->join('bathing_pool bp','bp.id = f.pool_id')
                ->where('bp.is_delete','0')
                ->where('f.is_delete','0')
                ->where('f.is_show','1')
                ->where('f.status','in','0,1,2,3')
                ->where('f.u_id',$uid)
                ->field('f.is_re,f.types,f.front_name,f.create_time,f.re_overtime,f.contract_overtime,f.is_lock,f.is_status,f.all_time,f.id,f.is_contract,f.worth,f.is_lock_num,f.is_contract,f.lock_time,bp.name,bp.img,bp.lock_position,bp.contract_time,bp.profit,bp.bait,f.worth,f.all_time,f.lock_overtime,f.re_overtime')
                ->page($page)
                ->paginate($limit)
                ->toArray();



            if (!empty($list['data'])){
                $list = $list['data'];

                $re =array();
                foreach ($list as $k => $v){



                    $service = new \app\common\service\Fish\Service();


                    $re[$k]['name'] = $v['name']; //酒馆名
    //                0：后台赠送正常流程； 1：拆分生成； 2：升级生成 3：后台指定 ;4:交易生成；5积分 ；6后台赠送即卖
                    if($v['types'] == 0){
                        $re[$k]['name'] .='-后台赠送-'.$v['front_name'];
                    }elseif ($v['types'] == 1){
                        $re[$k]['name'] .='-拆分-'.$v['front_name'];
                    }elseif ($v['types'] == 2){
                        $re[$k]['name'] .='-升级-'.$v['front_name'];
                    }elseif ($v['types'] == 3){
                        $re[$k]['name'] .='-后台指定-'.$v['front_name'];
                    }elseif ($v['types'] == 4){
                        $re[$k]['name'] .='-交易生成-'.$v['front_name'];
                    }elseif ($v['types'] == 5){
                        $re[$k]['name'] .='-积分-'.$v['front_name'];
                    }elseif ($v['types'] == 6){
                        $re[$k]['name'] .='-赠送即卖-'.$v['front_name'];
                    }



                    $re[$k]['types'] = $v['types'];
                    $re[$k]['img'] = $v['img'];   //酒馆图
                    $re[$k]['worth'] = $v['worth'];   //价值
                    $re[$k]['is_lock'] = 0;         //是否锁仓
                    $re[$k]['is_add_lock'] = 0;     //是否满足锁仓要求
                    $re[$k]['is_re'] = $v['is_re'];     //是否返池的酒
                    $re[$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);     //生成时间

                    if($v['is_re']){

                        $re[$k]['contract_time'] =  24;   //品酒所需时间（小时）
                        $re[$k]['profit'] = retain_2($v['profit']/($v['contract_time']/24));                 //收益
                        $re[$k]['bait'] =   $v['bait'];                   //所需GTC

                    }else{
                        $re[$k]['contract_time'] = $v['contract_time'];         //品酒所需时间
                        $re[$k]['profit'] = $v['profit'];                       //收益
                        $re[$k]['bait'] =   $v['bait'];                         //所需GTC
                    }

                    $re[$k]['contract_day'] = $re[$k]['contract_time'] / 24;    //品酒所需时间（天）
                    $re[$k]['id'] = $v['id'];


                    $service = new \app\common\service\Fish\Service();

    //                $times = $v['re_overtime']+$v['lock_overtime']+$v['contract_overtime'];
                    $times = $service->get_all_feed_time($v['id']);

    //                $over_contract_time = (time() - $v['create_time']) / 3600;//酒龄

                    $over_contract_time = $times;
                    $over_contract_time = intval($over_contract_time);



                    if($v['types'] == 6 && $v['is_status'] == 1){ //即卖
                        $re[$k]['over_contract_time'] = 0; //已品酒时间（小时）
                        $re[$k]['over_contract_day']  = 0;  //已品酒时间（天）
                        $re[$k]['is_contract']  = 1;         //是否品酒
                        $re[$k]['over_profit']  = 0;


                    }elseif($v['is_status'] == 1){
                        $re[$k]['over_contract_time'] = $over_contract_time; //已品酒时间（小时）

                        $re[$k]['over_contract_day']  = $over_contract_time /24;  //已品酒时间（天）
                        $re[$k]['is_contract']  = 1;         //是否品酒

                    }else{
                        $re[$k]['over_contract_time'] =  $over_contract_time; //已品酒时间（小时）
                        $re[$k]['over_contract_day']  =  $times /24;  //已品酒时间（天）

                        $re[$k]['is_contract']  =Db::table('fish_feed_log')
                            ->where('fid',$v['id'])
                            ->where('stime','<',time())
                            ->where('ntime','>',time())         //是否品酒
                            ->value('is_feed');

                        if(empty( $re[$k]['is_contract'])){
                            $re[$k]['is_contract'] = 0;
                        }
                    }





                    $re[$k]['over_profit']  = $re[$k]['profit'] * ( $times/$re[$k]['contract_time']);         //已得收益
                    if($v['types'] == 6 && $v['is_status'] == 1){ //即卖
                        $re[$k]['over_profit'] = 0;

                    }else{
                        $re[$k]['over_profit'] = retain_2($re[$k]['over_profit']);
                    }
                    $re[$k]['is_lock_num'] = $v['is_lock_num'];
                    if( $v['is_contract'] < 1 && $v['is_lock_num'] == 0 ){
                        $re[$k]['is_add_lock'] = 1;

                    }
                    if($v['is_lock_num']>0 && $v['is_re'] == 0){
                        $Multiple = $v['lock_position'];//锁仓倍数
                        $re[$k]['contract_time'] = $v['contract_time'] * $Multiple;       //品酒所需时间（小时）
                        $re[$k]['contract_day'] = ($v['contract_time']/24) * $Multiple;   //品酒所需时间（天）

                        $re[$k]['profit'] = $v['profit']*$Multiple;                 //收益
                        $re[$k]['bait'] =   $v['bait']*$Multiple;                   //所需GTC
                        $re[$k]['is_lock'] =  1;
                        $re[$k]['is_lock_test'] =  '到了锁仓';
                    }

                    if($re[$k]['contract_time'] < $re[$k]['over_contract_time']){
                        $re[$k]['over_contract_time'] = $re[$k]['contract_time'];

                    }
                    $re[$k]['create_age'] = (time() - $v['create_time']) / 3600;     //酒龄
                    $re[$k]['create_age'] = intval($re[$k]['create_age'] );     //酒龄
                    if($re[$k]['create_age'] > $re[$k]['contract_time']){
                        $re[$k]['create_age'] =  $re[$k]['contract_time'];
                    }
                }




                $info['list'] = $re;

                return json(['code' => 0, 'msg' => '获取成功' , 'info' => $info ]);
            }

            return json(['code' => 0, 'msg' => '暂无数据', 'info' => $info ]);

        }

    */

    /**
     * 品酒
     * @return \think\response\Json
     */
    public function feed()
    {

        $uid = $this->userId;
        // 0表示积分 1表示GTC
        $boi = input('post.pay_type');
        $PublicModel = new PublicModel;

        $fid = input('post.fid');
        if (empty($fid) || empty($uid)) {
            return json(['code' => 1, 'msg' => '参数缺失']);
        }


        $user_info = $PublicModel->getWallet($uid);
        $user_integral = $PublicModel->get_user_integral($uid);

        $user_msg = $PublicModel->get_one_user($uid);
        if (empty($user_msg['trad_password'])) {
            return json(['code' => 1, 'msg' => '请设置交易密码！']);
        }

        if ($user_msg['status'] != 1) {
            return json(['code' => 1, 'msg' => '该账户未被激活！']);
        }
        /*
        $user_msg = $PublicModel->get_user_card($uid);
        if(empty($user_msg)){
            return json(['code' => 1, 'msg' => '请设置交易收款信息！' ]);
        }
        */

        $is_f = DB::table('fish')
            ->alias('f')
            ->join('bathing_pool bp', 'bp.id = f.pool_id')
            ->where('f.is_delete', '0')
            ->where('f.u_id', $uid)
            ->where('f.id', $fid)
            ->field('bp.bait')
            ->find();

        if ($boi == 0) {
            if (empty($user_integral['now']) || $is_f['bait'] > $user_integral['now']) {
                return json(['code' => 1, 'msg' => '您的精粹不足！']);
            }
        } else if ($boi == 1) {
            if (empty($user_info['now']) || $is_f['bait'] > $user_info['now']) {
                return json(['code' => 1, 'msg' => '您的GTC不足！']);
            }
        } else {
            return json(['code' => 1, 'msg' => '未知错误！']);
        }

        $is_feed = $PublicModel->set_feed($uid, $fid, $boi);

        if ($is_feed == 1) {
            return json(['code' => 0, 'msg' => '装修成功']);
        } else if ($is_feed == 2) {
            return json(['code' => 1, 'msg' => '积分或GTC不足']);
        } else {
            return json(['code' => 1, 'msg' => '装修失败']);
        }


    }


    /**
     * 锁仓
     * @return \think\response\Json
     */
    private function lock_fish()
    {
        $uid = $this->userId;

        $fid = input('post.fid');
        if (empty($fid) || empty($uid)) {
            return json(['code' => 1, 'msg' => '参数缺失']);
        }


        $is_f = DB::table('fish')
            ->alias('f')
            ->join('bathing_pool bp', 'bp.id = f.pool_id')
            ->where('f.is_delete', '0')
            ->where('f.u_id', $uid)
            ->where('f.id', $fid)
            ->field('f.is_lock_num,bp.contract_time,f.lock_overtime,f.types')
            ->find();


        if (empty($is_f)) {
            return json(['code' => 1, 'msg' => '无效对象']);
        }

        if (in_array($is_f['types'], [0, 1, 3, 6])) {
            return json(['code' => 1, 'msg' => '非交易兑换获得的酒不得锁仓！']);
        }


        if ($is_f['is_lock_num']) {
            return json(['code' => 1, 'msg' => '该酒已被锁仓']);
        }


        $stime = get_fishstime($fid);

        if (empty($stime)) {
            return json(['code' => 1, 'msg' => '锁仓失败！']);
        }


        (int)$day = $is_f['contract_time'] / 24;

//        $arrday = array();
//        for ($i=1 ; $i<=$day;$i++){
//
//
//            $arrday[$i]['stime'] = $stime;             //品酒开始时间
//
//            $tmptime =   date('Y-m-d H:i:s',$stime);
//            $tmptime = strtotime("$tmptime +1 day");
//
//            $arrday[$i]['ntime'] = $tmptime;//品酒结束时间
//            $arrday[$i]['is_feed'] = 0;
//            $arrday[$i]['types'] = 2;
//            $arrday[$i]['fid'] = $fid;
//
//            $overtime = $arrday[$i]['ntime'];
//        }
//
//
//        $is_add = Db::table('fish_feed_log')->insertAll($arrday);
//
//
//        if(!$is_add){
//            return json(['code' => 1, 'msg' => '锁仓失败！' ]);
//        }

        $save['is_lock_num'] = 1;
//        $save['feed_overtime'] = $overtime;
        $is_save = DB::table('fish')->where('id', $fid)->update($save);
        if ($is_save) {
            return json(['code' => 0, 'msg' => '操作成功']);

        }


        return json(['code' => 1, 'msg' => '锁仓失败！']);

    }


    /**
     * 参加预约
     * @return \think\response\Json
     */

    public function appointment_fish()
    {
        $pid = input('post.pool_id');
        $types = input('post.types');
        // 0表示积分 1表示GTC
        $boi = input('post.pay_type');
        $uid = $this->userId;

        if (empty($types)) {
            $types = 0;
        } else {
            $types = 1;
        }


        if (empty($pid) || empty($uid) || empty($boi)) {
            return json(['code' => 1, 'msg' => '参数缺失']);
        }

        $PublicModel = new  PublicModel;
        $is_p = $PublicModel->get_one_pool($pid);

        if (!$is_p) {
            return json(['code' => 1, 'msg' => '无效对象']);
        }

        $bait = $is_p['subscribe_bait']; //预约扣取费用


        $user_msg = $PublicModel->get_one_user($uid);
        if (empty($user_msg['trad_password'])) {
            return json(['code' => 1, 'msg' => '请设置交易密码！']);
        }
        if ($user_msg['status'] != 1) {
            return json(['code' => 1, 'msg' => '该账户未被激活！']);
        }
        if (empty($user_msg['is_verify'])) {
            return json(['code' => 1, 'msg' => '该账户未实名认证！']);
        }
        /*
        $user_msg = $PublicModel->get_user_card($uid);
        if(empty($user_msg)){
            return json(['code' => 1, 'msg' => '请设置交易收款信息！' ]);
        }
        */

        $is_tnum = $PublicModel->get_tradable_num($is_p['key']);

        $key = get_today_key($pid);

        Db::startTrans();
        try {

            if ($PublicModel->get_deduction_reserve_bait($uid, $key)) {
                return json(['code' => 1, 'msg' => '您已预约！']);
            }

            if ($boi == 0) {
                $is_inte = $PublicModel->get_user_integral($uid);
                if (empty($is_inte['now']) || $is_p['bait'] > $is_inte['now']) {
                    return json(['code' => 1, 'msg' => '您的精粹不足！']);
                }
            } else if ($boi == 1) {
                $is_gub = $PublicModel->get_user_bait($uid);
                if (empty($is_gub['now']) || $is_p['bait'] > $is_gub['now']) {
                    return json(['code' => 1, 'msg' => '您的GTC不足！']);
                }
            } else {
                return json(['code' => 1, 'msg' => '未知错误！']);
            }


//        扣除GTC开始

            $is_drb = $PublicModel->deduction_reserve_bait($uid, $bait, $pid, $key, $is_p['fail_return'], 0, $is_p['rob_bait'], $is_p['end_time'], $boi);

            if (!$is_drb) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '预约失败！']);
            }

//        扣除GTC结束
            Db::commit();
            return json(['code' => 0, 'msg' => '预约成功，请及时领取！']);

        } catch (\Exception $e) {


            Db::rollback();
            return json(['code' => 1, 'msg' => '预约失败！']);
        }

    }




    /*
    public function appointment_fish(){
    $pid = input('post.pool_id');
    $types = input('post.types');
    $uid = $this->userId;

    if(empty($types)){
    $types = 0;
    }else{
    $types = 1;
    }

    if(empty($pid) || empty($uid)){
    return json(['code' => 1, 'msg' => '参数缺失' ]);
    }

    $PublicModel = new  PublicModel;
    $is_p = $PublicModel->get_one_pool($pid);

    if(!$is_p){
    return json(['code' => 1, 'msg' => '无效对象' ]);
    }

    if($types == 1){
    $bait = $is_p['rob_bait'];     //即抢
    }else{
    $bait = $is_p['subscribe_bait']; //预约
    }

    $is_gub =  $PublicModel->get_user_bait($uid);


    if(empty($is_gub['now']) || $is_p['bait'] > $is_gub['now']){
    return json(['code' => 1, 'msg' => 'GTC不足！' ]);
    }

    $user_msg = $PublicModel->get_one_user($uid);
    if(empty($user_msg['trad_password'])){
    return json(['code' => 1, 'msg' => '请设置交易密码！' ]);
    }
    if($user_msg['status'] != 1){
    return json(['code' => 1, 'msg' => '该账户未被激活！' ]);
    }
    $user_msg = $PublicModel->get_user_card($uid);

    if(empty($user_msg)){
    return json(['code' => 1, 'msg' => '请设置交易收款信息！' ]);
    }




    $is_tnum = $PublicModel->get_tradable_num($is_p['key']);


    if(empty($is_tnum['key'])){
    $key =  strtotime(date('Y-m-d')).$is_p['id'];
    }else{
    $key = $is_p['key'];
    }

    if($PublicModel->get_deduction_reserve_bait($uid,$key)){
    return json(['code' => 1, 'msg' => '您已预约！' ]);
    }




    Db::startTrans();
    try {
    //        扣除GTC开始

    $is_drb = $PublicModel->deduction_reserve_bait($uid,$bait,$pid,$key,$is_p['fail_return'],$types,$is_p['rob_bait'],$is_p['end_time']);

    if(!$is_drb){
    Db::rollback();
    return json(['code' => 1, 'msg' => '预约失败！' ]);
    }


    $entity = new \app\common\entity\MyWallet();
    $entity->bonusDispense($bait,$uid,2,1);//推广收益
    $entity->teamDispense($bait,$uid,2,1);//团队收益

    //        扣除GTC结束
    Db::commit();
    return json(['code' => 0, 'msg' => '预约成功，请及时领取！' ]);

    } catch (\Exception $e) {


    Db::rollback();
    return json(['code' => 1, 'msg' => '预约失败！' ]);
    }

    }
     */


    /**
     * 领取
     * @return \think\response\Json
     */
    public function adopt_fish()
    {

        $pid = input('post.pool_id');

        $uid = $this->userId;

        if (empty($pid) || empty($uid)) {
            return json(['code' => 1, 'msg' => '参数缺失1']);
        }

        $PublicModel = new  PublicModel;
        $is_p = $PublicModel->get_one_pool($pid);

        if (!$is_p) {
            return json(['code' => 1, 'msg' => '无效对象']);
        }

        $time = time();

        $stime = strtotime(date('H:i:s', $is_p['start_time']));   //开始领取时间
        $ntime = strtotime(date('H:i:s', $is_p['end_time']));     //结束领取时间

        if ($stime > $time) {
            return json(['code' => 1, 'msg' => '未到领取时间！']);
        }

        if ($ntime < $time) {
            return json(['code' => 1, 'msg' => '领取时间已过！']);
        }

        $PublicModel = new  PublicModel;

        $user_msg = $PublicModel->get_one_user($uid);
        if (empty($user_msg['trad_password'])) {
            return json(['code' => 1, 'msg' => '请设置交易密码！']);
        }
        if ($user_msg['status'] != 1) {
            return json(['code' => 1, 'msg' => '该账户未被激活！']);
        }
        if (empty($user_msg['is_verify'])) {
            return json(['code' => 1, 'msg' => '该账户未实名认证！']);
        }
        /*
        $user_msg = $PublicModel->get_user_card($uid);
        if(empty($user_msg)){
            return json(['code' => 1, 'msg' => '请设置交易收款信息！' ]);
        }
        */

        $key = get_today_key($is_p['id']);


        Db::startTrans();
        try {

            $is_gau = $PublicModel->get_appointment_user($uid, $key);

            if (!$is_gau) {
                
                // 0表示积分 1表示GTC
                $boi = input('post.pay_type');
                if (empty($boi)) {
                    $boi = 0;
                }else{
                    $boi = 1;
                }

                if ($boi == 0) {
                    $is_inte = $PublicModel->get_user_integral($uid);
                    if (empty($is_inte['now']) || $is_p['rob_bait'] > $is_inte['now']) {
                        return json(['code' => 1, 'msg' => '您的精粹不足！']);
                    }
                } else if ($boi == 1) {
                    $is_gub = $PublicModel->get_user_bait($uid);
                    if (empty($is_gub['now']) || $is_p['rob_bait'] > $is_gub['now']) {
                        return json(['code' => 1, 'msg' => '您的GTC不足！']);
                    }
                } else {
                    return json(['code' => 1, 'msg' => '未知错误！']);
                }

                // $is_inte = $PublicModel->get_user_integral($uid);
                // if (empty($is_inte['now']) || $is_p['rob_bait'] > $is_inte['now']) {
                //     $is_gub = $PublicModel->get_user_bait($uid);
                //     if (empty($is_gub['now']) || $is_p['rob_bait'] > $is_gub['now']) {
                //         return json(['code' => 1, 'msg' => '积分或GTC不足！']);
                //     } else {
                //         $boi = 1;
                //     }
                // } else {
                //     $boi = 0;
                // }

                //即抢
                $is_drb = $PublicModel->deduction_reserve_bait($uid, 0, $pid, $key, $is_p['fail_return'], 1, $is_p['rob_bait'], $is_p['end_time'], $boi);
                if (!$is_drb) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => '预约失败！']);
                }

                $is_gau = $PublicModel->get_appointment_user($uid, $key);
                if (!$is_gau) {
                    return json(['code' => 1, 'msg' => '即抢失败！']);
                }
            }

            if ($is_gau['status'] == 1) {
                return json(['code' => 1, 'msg' => '已点击领取，请勿重复操作！']);
            }

            if ($is_gau['status'] != 0) {
                return json(['code' => 1, 'msg' => '非法操作！']);
            }


            $is_set = $PublicModel->set_appointment_user_status1($is_gau['id'], $is_p['id'], $key, $uid);
            if (!$is_set) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '请勿频繁操作！']);
            }
            Db::commit();
            return json(['code' => 0, 'msg' => '申请成功，等待系统分配！']);

        } catch (\Exception $e) {

            Db::rollback();
            return json(['code' => 1, 'msg' => '操作异常，申请领取失败！']);
        }

    }

    /**
     * 获取房产拍卖状态
     * @return \think\response\Json
     */
    public function fish_sell_status()
    {
        $fid = input('post.fid');

        if (empty($fid)) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $is_f = DB::table('fish')->alias('f')
            ->join('bathing_pool bp', 'bp.id = f.pool_id')
            ->join('fish_order fo', 'f.order_id = fo.id')
            ->join('user u', 'u.id = fo.bu_id')
            ->join('user_invite_code uic', 'u.id = uic.user_id')
            ->where('f.is_delete', '0')
            ->where('f.id', $fid)
            ->field('fo.status,fo.bu_id user_id,fo.order_number,uic.invite_code user_code')
            ->find();
        if (empty($is_f)) {
            return json(['code' => 1, 'msg' => '无效数据']);
        }

        if ($is_f['status'] != -3) {
            $is_f['status'] = 1;
            return json(['code' => 1, 'msg' => '正常', 'data' => '']);
        }else{
            $is_f['status'] = 0;
            return json(['code' => 0, 'msg' => '封禁', 'data' => $is_f]);
        }
        

    }


}