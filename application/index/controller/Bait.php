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

class Bait extends Base
{

    /**
     * GTC列表
     * @return \think\response\Json
     */
//    public function index(){
//
//        $uid = $this->userId;
//        $page = input('post.page')?input('post.page'):1;
//        $limit = input('post.limit')?input('post.limit'):15;
//        $PublicModel = new  PublicModel;
//
//        $user_info = $PublicModel->getWallet($uid);
//        $info['mybait'] = empty($user_info['now'])?0:$user_info['now'];//现有GTC
//        $info['list'] = array();
//        $list = DB::table('fish')
//            ->alias('f')
//            ->join('bathing_pool bp','bp.id = f.pool_id')
//            ->where('f.is_delete','0')
//            ->where('f.u_id',$uid)
//            ->field('f.is_re,f.create_time,f.lock_jsontime,f.status3_jsontime,f.status3_jsontime,f.is_lock,f.is_status,f.id,f.is_contract,f.worth,f.is_lock_num,f.is_contract,f.contract_jsontime,f.contract_overtime,f.lock_time,bp.name,bp.img,bp.lock_position,bp.contract_time,bp.profit,bp.bait')
//            ->page($page)
//            ->paginate($limit)
//            ->toArray();
//
//
//        if (!empty($list['data'])){
//            $list = $list['data'];
//
//            $re =array();
//            foreach ($list as $k => $v){
//                $re[$k]['name'] = $v['name']; //酒馆名
//                $re[$k]['img'] = $v['img'];   //酒馆图
//                $re[$k]['is_lock'] = 0;
//                $re[$k]['is_add_lock'] = 0;
//                if($v['is_re']){
//
//                    $re[$k]['contract_time'] = 24;   //品酒所需时间（小时）
//                    $re[$k]['profit'] = $v['profit']/($v['contract_time']/24);                 //收益
//                    $re[$k]['bait'] =   $v['bait'];                   //所需GTC
//                }elseif ($v['is_lock_num']){
//                    $Multiple = $v['lock_position'];//锁仓倍数
//                    $re[$k]['contract_time'] = $v['contract_time']*$Multiple;   //品酒所需时间（小时）
//                    $re[$k]['profit'] = $v['profit']*$Multiple;                 //收益
//                    $re[$k]['bait'] =   $v['bait']*$Multiple;                   //所需GTC
//                    $re[$k]['is_lock'] =  1;
//                }else{
//                    $re[$k]['contract_time'] = $v['contract_time'];         //品酒所需时间
//                    $re[$k]['profit'] = $v['profit'];                       //收益
//                    $re[$k]['bait'] =   $v['bait'];                         //所需GTC
//                }
//                $re[$k]['contract_day'] = $re[$k]['contract_time'] / 24;    //品酒所需时间（天）
//                $re[$k]['id'] = $v['id'];
//
//                $contract = $PublicModel->contract_time($v['contract_jsontime'], $re[$k]['contract_time']);
//
//
////                下面时间需要修改的
//                $re[$k]['over_contract_time'] = $contract['contract_time']; //已品酒时间（小时）
//                $re[$k]['over_contract_day']  = $contract['contract_day'];  //已品酒时间（天）
//                $re[$k]['is_contract']  = $contract['is_contract'];         //是否品酒
//                $re[$k]['over_profit']  = $re[$k]['profit'] * ( $re[$k]['over_contract_time']/$re[$k]['contract_time']);         //已得收益
//
//                if( $re[$k]['over_contract_time'] >= $re[$k]['contract_time']){
//                    $re[$k]['is_add_lock'] = 1;
//                }
//
//            }
//
//            $info['list'] = $re;
//            return json(['code' => 0, 'msg' => '获取成功' , 'info' => $info ]);
//        }
//
//        return json(['code' => 0, 'msg' => '暂无数据', 'info' => $info ]);
//
//    }




}