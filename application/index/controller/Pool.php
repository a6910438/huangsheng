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
use think\Db;
use think\Request;

class Pool extends Base
{

    public function index(){


        $uid = $this->userId;

        $lv = Db::table('user')->where('id',$uid)->field('lv')->find();
        $list = DB::table('bathing_pool')
            ->where('is_delete','0')
            ->field('id,lv,worth_max,worth_min,remarks,img,rob_bait,subscribe_bait,start_time,end_time,bait,profit,name,contract_time,is_open,about_start_time,about_end_time,key')
            ->order('sort asc')
            ->select();


        if($list){
            $time = time();
            foreach ( $list as $k => $v){


                $list[$k]['status'] = 0;
                $list[$k]['start_time'] = date('H:i',$v['start_time'] );
                $start_time = strtotime($list[$k]['start_time']);
                $list[$k]['end_time'] = date('H:i',$v['end_time'] );
                $end_time = strtotime($list[$k]['end_time']);

                // $list[$k]['name'] = $v['name']; //酒馆名
                $list[$k]['en_name'] = getPoolEnName($v['name']); // 酒馆英文名


                $list[$k]['about_start_time'] = date('H:i',$v['about_start_time'] );
                $about_start_time = strtotime( $list[$k]['about_start_time']);

                $list[$k]['about_end_time'] = date('H:i',$v['about_end_time'] );
                $about_end_time = strtotime( $list[$k]['about_end_time']);

                if($v['is_open']){

//                    是否到预约时间
                    if($about_start_time < $time && $about_end_time > $time){
                        $list[$k]['status'] = 1;//预约时间
                    }

                    if($start_time < $time && $end_time > $time){
                        $list[$k]['status'] = 2;//领取时间
                    }


                    $key = get_today_key($v['id']);
                    $is_au = Db::table('appointment_user')->where('uid',$uid)->where('pool_id',$v['id'])->where('key',$key)->find();
                    $list[$k]['ttttt'] = $is_au;
                    $list[$k]['v'] = $v;
                    if($is_au){
//                  预约表  -3:未及时支付; -2：超时未领取 -1分配失败； 0:参加预约 ;1：点击抢酒 2：分配到酒
                        if($is_au['status'] == -1 || $is_au['status'] == -3 || $is_au['status'] == -2  ){
                            $list[$k]['status'] = 5;
                        }

                        if($is_au['status'] == 0  ){
                            //预约开始时间 - 领取开始时间
                            if($about_start_time < $time && $start_time > $time){
                                $list[$k]['status'] = 2;//待领取
                            }elseif($start_time < $time && $end_time > $time){
                                $list[$k]['status'] = 3;//领取
                                $list[$k]['start_time'] = date('H:i:s',$start_time);
                                $list[$k]['end_time'] = date('H:i:s',$end_time);

                            }else{
                                $list[$k]['status'] = 5;
//                            $list[$k]['status'] = 3;
                            }
                        }

                        if($is_au['status'] == 1 ){
                            //点击过了抢酒
                            if($start_time < $time && $end_time > $time){

                                $list[$k]['status'] = 4;//排队中
                            }else{
                                $list[$k]['status'] = 5;
                            }
                        }
                        if($is_au['status'] == 2 ){

                            if($is_au['oid']){
                                $f_id = Db::table('fish_order')->where('id',$is_au['oid'])->value('f_id');
                                $list[$k]['fid'] = $f_id;
                                $list[$k]['status'] = 8;//待支付
                            }else{
                                //点击过了抢酒
                                $list[$k]['status'] = 6;//已领取
                            }

                        }
                        if($is_au['status'] == 3 ){
                            $list[$k]['status'] = 9;
                        }
                        if($is_au['status'] == 4 ){
                            $list[$k]['status'] = 5;
                        }



                    }else{

                        if($start_time < $time && $end_time > $time){
                            $list[$k]['status'] = 7;//即抢
                        }elseif($about_start_time < $time && $about_end_time > $time){
                            $list[$k]['status'] = 1;//预约
                        }else{
                            $list[$k]['status'] = 5;//不在预约即抢时间
//                        $list[$k]['status'] = 1;
                        }

                    }

                }

                $list[$k]['status_name'] = pool_status( $list[$k]['status']);

//
                if( $list[$k]['status'] == 5){
                    //                酿酒中-》预约
                    //预约开始时间-当前时间
                    if($about_start_time > time()){
                        $list[$k]['count_down'] = $about_start_time - time();
                    }else{
                        $tmp_time = date('H:i:s',$about_start_time );
                        $list[$k]['count_down'] = strtotime( "$tmp_time + 1 day") -time();
                    }
//                    $list[$k]['count_down'] = 10;//测试记得删除
                    $list[$k]['to_status'] = 1;

                }elseif ($list[$k]['status'] == 1 ){
                    //                预约-》即抢
                    //领取开始时间 - 当前
                    $list[$k]['count_down'] = $start_time - time();
                    $list[$k]['to_status'] = 7;

                }elseif ($list[$k]['status'] == 2 ){
                    //                待领取-》领取
                    //领取开始时间-当前
                    $list[$k]['count_down'] =  $about_start_time - time();
                    $list[$k]['to_status'] = 3;

                }


//                elseif ($list[$k]['status'] == 3 ){
//                //                领取-》排队中
//                    //领取结束时间-当前
//
//
//                }elseif ($list[$k]['status'] == 4 ){
//                //                排队中-》已领取
//
//
//
//                }
//                elseif ($list[$k]['status'] == 6 ){
//                    //                已领取-》待支付
//
//
//                }
//                elseif ($list[$k]['status'] == 8 ){
//                    //                待支付-》待确认
//
//
//
//                }
//                elseif ($list[$k]['status'] == 9 ){
//                    //                待确认-》酿酒中
//                    $over_time =$is_au['buy_time'];
//                    $list[$k]['count_down'] =  $over_time - time();
//
//
//                }
//                elseif ($list[$k]['status'] == 5 ){
//                    //                酿酒中-》预约
//
//
//                }
//                elseif ($list[$k]['status'] == 7 ){
//                    //                即抢-》排队中
//                    $list[$k]['count_down'] =  $about_end_time - time();
//
//                }


            }
        }

        if ($list){

            return json(['code' => 0, 'msg' => '获取成功' , 'info' => $list ]);
        }
        if (empty($list)){
            return json(['code' => 0, 'msg' => '暂无数据' ,'info' => $list ]);

        }
        return json(['code' => 1, 'msg' => '获取失败']);

    }

    /**
     * 获取酒馆状态
     * @return \think\response\Json
     */
    public function get_pool_status(){
        $uid  = $this->userId;
        $pool_id =  input('post.pool_id')?input('post.pool_id'):0;

        if(empty($pool_id)){
            return json(['code' => 1, 'msg' => '酒馆id不能为空']);
        }


        $is_pool = Db::table('bathing_pool')->where('id',$pool_id)->where('is_delete',0)->find();
        if(empty($is_pool)){
            $re['to_status'] = 5;    //酿酒中
        }


        $key = get_today_key($pool_id);
        $is_au = Db::table('appointment_user')->where('key',$key)->where('uid',$uid)->find();
        $time = time();
        $about_start_time = date('H:i:s',$is_pool['about_start_time'] );
        $about_start_time = strtotime($about_start_time);               //预约开始时间

        $about_end_time = date('H:i:s',$is_pool['about_end_time'] );
        $about_end_time = strtotime( $about_end_time);                  //预约结束时间

        $start_time = date('H:i:s',$is_pool['start_time'] );
        $start_time = strtotime($start_time);                           //领取开始
        $end_time = date('H:i:s',$is_pool['end_time'] );
        $end_time = strtotime($end_time);


        if(empty($is_au)){
            //领取结束

            if($time < $about_start_time){
                $re['to_status'] = 5; //酿酒中


            }elseif ($time > $about_start_time && $time < $about_end_time){
                $re['to_status'] = 1; //预约


            }elseif ($time > $about_end_time && $time < $start_time){
                $re['to_status'] = 2; //待领取


            }elseif ($time > $start_time && $time < $end_time){
                $re['to_status'] = 7; //即抢


            }else{
                $re['to_status'] = 5; //酿酒中


            }



            $re['id'] = $pool_id;
        }else{

//       au stutus     0:参加预约  ;1：点击领取 2：分配到酒(定时任务派酒）   3:上传支付；4完成；
            if($is_au['status'] == 1 ){
                $re['to_status'] = 4;    //排队中
                $list['count_down'] = 0;
            }elseif ($is_au['status'] == 2 ){
                $re['to_status'] = 8;    //待支付

                $re['fid'] =  Db::table('fish_order')->where('id',$is_au['oid'])->value('f_id');
            }elseif ($is_au['status'] == 3 ){
                $re['to_status'] = 9;    //待确认
            }elseif ($is_au['status'] == 4 ){
                $re['to_status'] = 5;    //酿酒中
            }elseif ($is_au['status'] == 0 ){
                if($time > $start_time && $time < $end_time){
                    $re['to_status'] = 3;    //领取
                }else{

                    $re['to_status'] = 2;    //待领取
                }
            }else{
                $re['to_status'] = 5;    //酿酒中
            }


        }
    if($is_pool['is_open'] == 0){
        $re['to_status'] = 0;//生成中
    }
        $re['count_down'] = 10;



        return json(['code' => 0, 'msg' => '查询成功','info'=>$re]);


    }


    /**
     * 待支付的酒
     * @return \think\response\Json
     */
    public function adoption_success(){
        $uid = $this->userId;
        $map['au.status'] = 2;
        $map['au.uid'] = $uid;
        $list = Db::table('appointment_user')
            ->alias('au')
            ->join('fish_order fo','fo.id = au.oid')
            ->join('fish f','f.id = fo.f_id')
            ->join('bathing_pool bp','bp.id = f.pool_id')
            ->field('bp.worth_max,bp.worth_min,bp.profit,bp.start_time,bp.end_time,bp.subscribe_bait,bp.rob_bait,bp.contract_time,bp.bait,fo.f_id,fo.worth,bp.img,bp.name')
            ->where($map)
            ->select();
        return json(['code' => 0, 'msg' => '操作成功', 'info'=>$list]);
    }



}