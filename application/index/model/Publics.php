<?php

namespace app\index\model;

use app\common\entity\Config;
use app\common\entity\Dynamic_Log;
use app\common\entity\DynamicConfig;
use app\common\entity\Log;
use app\common\entity\MyWallet;
use app\common\entity\SafeAnswer;
use app\common\entity\StoreLog;
use app\common\entity\SystemConfig;
use app\common\entity\Team;
use app\common\entity\UserInviteCode;
use app\common\entity\UserProduct;
use app\common\service\Users\Identity;
use app\common\service\Users\Service;
use think\Db;
use think\Request;
use think\Session;

class Publics
{


    /**
     * 用户GTC信息
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function getWallet($uid)
    {
        return DB::table('my_wallet')->where('uid', $uid)->where('is_balance_extension', 0)->find();
    }

    /**
     * 品酒操作
     * @param $uid
     * @param $fid
     * @return bool
     */

    public function set_feed($uid, $fid, $boi)
    {


        $is_f = DB::table('fish')
            ->alias('f')
            ->join('bathing_pool bp', 'bp.id = f.pool_id')
            ->where('f.is_delete', '0')
            ->where('f.u_id', $uid)
            ->where('f.id', $fid)
            ->field('f.worth,f.is_contract,f.contract_overtime,f.re_overtime,f.lock_overtime,f.all_time,f.is_lock_num,f.is_lock,f.is_status,bp.contract_time,bp.bait,bp.profit,bp.lock_position,bp.status,bp.worth_max,bp.about_start_time')
            ->find();

        if ($is_f['is_status'] == 1) {
            return false;
        } elseif ($is_f['is_status'] == 2) {
            $types = 3;//重返酒馆
        } elseif ($is_f['is_lock_num'] > 0 && $is_f['is_contract'] == 1) {
            $types = 2; //锁仓
        } else {
            $types = 1;//合约养殖
        }

        $about_start_time = date('H:i:s', $is_f['about_start_time']);
        $about_start_time = strtotime($about_start_time);
        $about_start_time = $about_start_time - (60 * 30);


//      是否有今天的信息
        $is_feed = Db::table('fish_feed_log')
            ->where('fid', $fid)
            ->where('stime', '<', time())
            ->where('ntime', '>', time())
            ->where('types', $types)
            ->field('is_feed,ntime,id')
            ->find();


        if ($is_feed['is_feed'] == 1) {
            return 3;
        }


        $fist_time = get_fishstime($fid);


        $date1 = date('H:i:s', $fist_time);
        $nowtime = strtotime($date1);

        $service = new \app\common\service\Fish\Service();


        $days = $service->timediff($fist_time, time());//该状态养殖到现在共多少时间

        $now_time = time();
        for ($i = 0; $i < $days; $i++) {
//
            $this_day = $fist_time + ($i * 86400);
            $in_times = $this_day + 86400;
            if ($this_day < $now_time && $in_times > $now_time) {

                $save['feed_time'] = $now_time;

                $add['stime'] = $this_day;
                $add['ntime'] = $in_times;
                $add['feed_time'] = $now_time;

                $stime = $this_day;
                $ntime = $in_times;
                $feed_time = $now_time;
            }


        }


        Db::startTrans();
        try {
            if ($types == 1) {
                $tmp_up['contract_overtime'] = $is_f['contract_overtime'] + 24;
                $tmp_up['update_time'] = time();
                $get_time = $tmp_up['contract_overtime'];
                Db::table('fish')->where('id', $fid)->update($tmp_up);
            } elseif ($types == 2) {
                $tmp_up['lock_overtime'] = $is_f['lock_overtime'] + 24;
                $tmp_up['update_time'] = time();
                Db::table('fish')->where('id', $fid)->update($tmp_up);
                $get_time = $tmp_up['lock_overtime'] + $is_f['contract_overtime'];

            } elseif ($types == 3) {
                $tmp_up['re_overtime'] = $is_f['re_overtime'] + 24;
                $tmp_up['update_time'] = time();
                Db::table('fish')->where('id', $fid)->update($tmp_up);
                $get_time = $tmp_up['re_overtime'];
            }


            if ($is_feed) {
                if ($is_feed['is_feed']) {
                    return 3;
                } else {
                    $ups['is_feed'] = 1;
                    $ups['feed_time'] = time();
                    $is_up = Db::table('fish_feed_log')
                        ->where('id', $is_feed['id'])
                        ->update($ups);
                    if (!$is_up) {
                        Db::rollback();
                        return 3;
                    }

                }
            } else {
                $stime = get_fishstime($fid);//酒的创建时间


                $add['fid'] = $fid;
                $add['is_feed'] = 1;
                $add['types'] = $types;

                $is_add = Db::table('fish_feed_log')->insert($add);
                unset($add);
                if (!$is_add) {
                    Db::rollback();
                    return 3;
                }
            }


            $time = time();


            $bait = $is_f['bait'];//GTC
            $profit = $is_f['profit'];//收益百分比
            $contract_time = $is_f['contract_time'];//合约时间
            $lock_position = $is_f['lock_position'];//倍数
            $days = $contract_time / 24;

            $dbprofit = retain_2($profit * $lock_position); //锁仓收益
            $dbbait = retain_2($lock_position * $bait);      //锁仓GTC


            $re_profit = retain_2($profit / $days); //返池  比例

            $age = get_fagetime($fid);

//       合约期内的增值
            if ($types == 1) {

                $is_bait = $bait;
                Db::table('fish')->where('id', $fid)->setInc('all_time', 24);//总品酒时间

                $age = get_fagetime($fid);
//                dump($age); //酒龄   72
//                dump($contract_time);//48
//                dump($get_time);//24
//                exit;

                if (($get_time >= $contract_time) && ($age >= $contract_time)) {

                    if ($age >= $contract_time) {
                        $save['is_status'] = 1;
                    }
                    $save['is_contract'] = 1;

                } else {


                    Db::table('fish')->where('id', $fid)->setInc('all_time', 24);//总品酒时间


                    if ($get_time >= $contract_time) {
                        $save['is_contract'] = 1;
                    } else {
                        $save['is_contract'] = 0;
                    }


                    if ($age >= $contract_time && $save['is_contract'] && $is_f['is_lock_num'] == 0) {
                        $save['is_status'] = 1;
                    }

                }

            } //       锁仓期内的增值
            elseif ($types == 2) {
                $is_bait = $dbbait;
                Db::table('fish')->where('id', $fid)->setInc('all_time', 24);//总品酒时间


                if ($get_time >= ($contract_time * $lock_position)) {

                    $save['is_status'] = 1;
                    $save['is_lock'] = 1;

                }
                $profit = $dbprofit;
            } //       回放期内的增值
            elseif ($types == 3) {
                $is_bait = $bait;
                Db::table('fish')->where('id', $fid)->setInc('all_time', 24);//总品酒时间
                $save['is_status'] = 1;
                $profit = $re_profit;


            } else {

                Db::rollback();
                return false;
            }


            $data['uid'] = $uid;
            $data['from_id'] = $fid;
            $data['remark'] = '品酒';
            $data['number'] = '-' . $is_bait;


            $res = $this->RechargeLog($data, 2, $boi);
            if (!$res) {
                $res = $this->RechargeLog($data, 2, 1);
                if (!$res) {
                    Db::rollback();
                    return 2;
                }
            }


            if ($types == 3) {
                get_fagetime($fid);
                $feed_overtime = Db::table('fish_feed_log')->where('fid', $fid)->order('ntime desc')->value('ntime');
                if (time() > $about_start_time) {
                    $feed_overtime = $about_start_time + (24 * 3600);
                } else {
                    $feed_overtime = $about_start_time;
                }

            } else {
                $is_time = $stime - 3600;
                $feed = Db::table('fish_feed_log')
                    ->where('fid', $fid)
                    ->where('stime', '<', $is_time)
                    ->where('ntime', '>', $is_time)
                    ->where('is_feed', 1)
                    ->order('ntime desc')
                    ->value('ntime');

                if (empty($feed)) {
                    $feed_overtime = $about_start_time + (24 * 3600);
                } else {
                    if (!empty($save['is_status'])) {
                        if (time() > $about_start_time) {
                            $feed_overtime = $about_start_time + (24 * 3600);
                        } else {
                            $feed_overtime = $about_start_time;
                        }
                    } else {
                        $feed_overtime = $about_start_time + (24 * 3600);
                    }
                }
            }


            $save['feed_overtime'] = $feed_overtime;          //预计完成时间
            $save['feed_time'] = $time;                               //最近品酒时间
            $save['feed_bait'] = $is_bait;                       //最近品酒GTC

            $is_save = DB::table('fish')->where('id', $fid)->update($save);

            if ($is_save) {


                Db::commit();
                return 1;
            }


            Db::rollback();
            return 3;


//
        } catch (\Exception $e) {

            Db::rollback();
            return 3;
        }


    }



//    public function set_feed($uid,$fid){
//
//
//        $is_f = DB::table('fish')
//            ->alias('f')
//            ->join('bathing_pool bp','bp.id = f.pool_id')
//            ->where('f.is_delete','0')
//            ->where('f.u_id',$uid)
//            ->where('f.id',$fid)
//            ->field('f.worth,f.is_contract,f.contract_overtime,f.lock_overtime,f.all_time,f.is_lock_num,f.is_lock,f.is_status,bp.contract_time,bp.bait,bp.profit,bp.lock_position,bp.status,bp.worth_max')
//            ->find();
//
//        if($is_f['is_status'] == 1 ){
//            return false;
//        }elseif ($is_f['is_status'] == 2 ){
//            $types = 3;//重返酒馆
//        }elseif($is_f['is_lock_num'] > 0 && $is_f['is_contract'] == 1 ){
//            $types = 2; //锁仓
//        }else{
//            $types = 1;//合约养殖
//        }
//
//
////      是否有今天的信息
//        $is_feed = Db::table('fish_feed_log')
//            ->where('fid',$fid)
//            ->where('types',$types)
//            ->where('stime','<',time())
//            ->where('ntime','>',time())
//            ->field('is_feed,ntime,id')
//            ->find();
//
//
//        if($is_feed['is_feed'] == 1){
//            return false;
//        }
//
//        $service = new \app\common\service\Fish\Service();
//        $get_time = $service->get_all_feed_time($fid);
//
//
//
//
//        Db::startTrans();
//        try {
//            if($types == 1){
//                $tmp_up['contract_overtime'] = $get_time+24;
//                $tmp_up['update_time'] = time();
//                Db::table('fish')->where('id',$fid)->update($tmp_up);
//            }elseif ($types == 2){
//                $tmp_up['lock_overtime'] = $get_time+24;
//                $tmp_up['update_time'] = time();
//                Db::table('fish')->where('id',$fid)->update($tmp_up);
//            }elseif ($types == 3){
//                $tmp_up['re_overtime'] = $get_time+24;
//                $tmp_up['update_time'] = time();
//                Db::table('fish')->where('id',$fid)->update($tmp_up);
//
//            }
//
//            $ctime = get_fishstime($fid);
//
//
//
//            if($is_feed){
//                if($is_feed['is_feed']){
//                    return false;
//                }else{
//                    $ups['is_feed'] = 1;
//                    $ups['feed_time'] = time();
//                    $is_up = Db::table('fish_feed_log')
//                        ->where('id',$is_feed['id'])
//                        ->update($ups);
//                    if(!$is_up){
//                        Db::rollback();
//                        return false;
//                    }
//
//                }
//            }else{
//                $stime = get_fishstime($fid);//酒的创建时间
//
//
//                $date1 = date('H:i:s',$stime);
//                $stime = strtotime($date1);
//
//                $ntime = strtotime("$date1 +1 day");
//
//
//                $add['fid'] = $fid;
//                $add['is_feed'] = 1;
//                $add['stime'] = $stime;
//                $add['ntime'] = $ntime;
//                $add['feed_time'] = $stime+ 1000;
//                $add['types'] = $types;
//                $is_add = Db::table('fish_feed_log')->insert($add);
//                unset($add);
//                if(!$is_add){
//                    Db::rollback();
//                    return false;
//                }
//            }
//
//
//
//
//            $time = time();
//
//
//
//
//
//
//
//            $bait = $is_f['bait'];//GTC
//            $profit = $is_f['profit'];//收益百分比
//            $contract_time = $is_f['contract_time'];//合约时间
//            $lock_position = $is_f['lock_position'];//倍数
//            $days = $contract_time/24;
//
//            $dbprofit = retain_2($profit*$lock_position); //锁仓收益
//            $dbbait = retain_2($profit*$bait);      //锁仓GTC
//
//
//            $re_profit = retain_2($profit/$days); //返池  比例
//
//
//
////       合约期内的增值
//            if($types == 1){
//
//                $is_bait = $bait;
//                Db::table('fish')->where('id',$fid)->setInc('all_time',24);//总品酒时间
//
//                $age = get_fagetime($fid);
////                dump($age); //酒龄   72
////                dump($contract_time);//48
////                dump($get_time);//24
////                exit;
//                if(($get_time >= $contract_time) && ($age >= $contract_time)){
//
//                    if($age >= $contract_time){
//                        $save['is_status'] = 1;
//                    }
//                    $save['is_contract'] = 1;
//
//                }else{
//
//
//                    Db::table('fish')->where('id',$fid)->setInc('contract_overtime',24);//合约期间已品酒时间（小时）
//                    Db::table('fish')->where('id',$fid)->setInc('all_time',24);//总品酒时间
//
//                    $feed_time = $service->get_feed_time($fid,1);
//
//                    if(!$feed_time){
//                        $_over = 24;
//                    }else{
//                        $_over =  $get_time + 24;
//                    }
//
//                    if($_over >=$contract_time){
//                        $save['is_contract'] = 1;
//                    }else{
//                        $save['is_contract'] = 0;
//                    }
//                    $age = get_fagetime($fid);
//
//
//                    if($age >=$contract_time && $save['is_contract']  && $is_f['is_lock_num'] == 0 ){
//                        $save['is_status'] = 1;
//                    }
//
//                }
//
//            }
//            //       锁仓期内的增值
//            elseif ($types == 2){
//                $is_bait = $dbbait;
//                Db::table('fish')->where('id',$fid)->setInc('all_time',24);//总品酒时间
//
//
//                if($get_time >= ($contract_time*$lock_position)){
//
//                    $save['is_status'] = 1;
//                    $save['is_lock'] = 1;
//
//                }else{
//                    Db::table('fish')->where('id',$fid)->setInc('lock_overtime',24);//合约期间已品酒时间（小时）
//                    Db::table('fish')->where('id',$fid)->setInc('all_time',24);//总品酒时间
//
//                    $feed_time = $service->get_feed_time($fid,2);
//
//                    if(!$feed_time){
//                        $_over = 24;
//                    }else{
//                        $_over =  $get_time + 24;
//                    }
//
//                    if($_over >=($contract_time*$lock_position)){
//                        $save['is_status'] = 1;
//                        $save['is_lock'] = 1;
//                    }
//                }
//                $profit = $dbprofit;
//            }
////       回放期内的增值
//            elseif ($types == 3){
//                $is_bait = $bait;
//                Db::table('fish')->where('id',$fid)->setInc('all_time',24);//总品酒时间
//                Db::table('fish')->where('id',$fid)->setInc('re_overtime',24);
//                $save['is_status'] = 1;
//                $profit = $re_profit;
//
//
//            }else{
//
//                Db::rollback();
//                return false;
//            }
//
//
//            $data['uid'] = $uid;
//            $data['from_id'] = $fid;
//            $data['remark'] = '品酒';
//            $data['number'] = '-'.$is_bait;
//
//
//            $res = $this->RechargeLog($data,2);
//            if(!$res){
//                Db::rollback();
//                return false;
//            }
//
//
//
//
//            if($types == 3){
//                $feed_overtime =  Db::table('fish_feed_log')->where('fid',$fid)->order('ntime desc')->value('ntime');
//                $feed_overtime += 3600;
//            }else{
//                $feed_overtime =  Db::table('fish_feed_log')->where('fid',$fid)->order('ntime desc')->value('ntime');
//            }
//
//
//
//            $save['feed_overtime'] = $feed_overtime;          //预计完成时间
//            $save['feed_time'] = $time;                               //最近品酒时间
//            $save['feed_bait'] = $is_bait;                       //最近品酒GTC
//
//            $is_save = DB::table('fish')->where('id',$fid)->update($save);
//
//
//            if($is_save){
//
//
//
//                Db::commit();
//                return true;
//            }
//
//
//            Db::rollback();
//            return false;
//
//
////
//        } catch (\Exception $e) {
//
//            Db::rollback();
//            return false;
//        }
//
//
//
//    }


    /**
     * 添加用户收益记录
     * @param $uid
     * @param $number
     * @param $now
     * @param $types 1：平台操作  ;2: 平台赠酒 ; 3交易;4申诉；
     * @return int|string
     */
    public function add_user_profit($uid, $number, $types, $oid)
    {

        $is_user = Db::table('user')->where('id', $uid)->where('status', '>=', 0)->find();
        $is_o = Db::table('user_profit_log')->where('oid', $oid)->field('oid')->find();
        if ($is_o) {
            return false;
        }
        if (!$is_user) {
            return false;
        }
        $pnum = $is_user['now_profit'] + $number;
        if ($pnum <= 0) {
            return false;
        }

        if ($number > 0) {
            Db::table('user')->where('id', $uid)->setInc('profit', $number);
        }
        Db::table('user')->where('id', $uid)->setInc('now_profit', $number);

        $now = $is_user['now_profit'];
        $add['uid'] = $uid;
        $add['number'] = $number;
        $add['now'] = $now;
        $add['types'] = $types;
        $add['create_time'] = time();
        $add['future'] = $now + $number;
        $add['oid'] = $oid;
        return Db::table('user_profit_log')->insert($add);


    }



    /**
     * 拆分升级
     * @param $fid
     * @param $status 1拆分
     * @param $num    品酒后的价值
     * @return bool|int|string
     */
//    public function upgrade_or_split($fid,$status,$num){
//
//        $service = new \app\common\service\Fish\Service();
//        //拆分
//        if($status == 1){
//            $tmp = $num/3;
//            $tmp = floor($tmp);
//
//            $last = $num- ($tmp*2);
//
//            $arr[] = $tmp;
//            $arr[] = $tmp;
//            $arr[] = $last;
//
////            $is_save = $service->SplitFish($fid,$arr,$num);
//
//        }else{
////            $is_save = $service->UpgradeFish($fid,$num);
//
//
//        }
//        return $is_save;
//
//
//    }


    /**
     * 前端GTC扣除记录
     * @param $data
     * @param $type
     * @return bool
     */
    public function RechargeLog($data, $type, $boi = 0)
    {

        if (empty($boi)) {
            $table = "my_integral";
        } else {
            $table = "my_wallet";
        }


        $oldInfo = Db::table($table)->where('uid', $data['uid'])->where('is_balance_extension', 0)->find();

        if (empty($oldInfo)) {
            return false;
        }

        $edit_data['now'] = $oldInfo['now'] + $data['number']; //现在
        //品酒 和 返饵 ,激活扣除
        if ($type == 2 || $type == 4) {


            $edit_data['old'] = $oldInfo['old']; //历史


        } else {
            $edit_data['old'] = $oldInfo['old'] + $data['number']; //历史
        }

        $edit_data['update_time'] = time();

        if ($edit_data['now'] < 0) {
            return false;
        }

        $res = DB::table($table)->where('uid', $data['uid'])->update($edit_data);

        if (!$res) {
            return false;
        }


        $create_data = [
            'uid' => $data['uid'],
            'number' => $data['number'],     //交易数量
            'now' => $oldInfo['now'],        //交易前
            'remark' => $data['remark'],    //备注
            'future' => $edit_data['now'], //交易之后
            'types' => $type, //1：平台操作 2.品酒 3.用户转让 4.返料
            'create_time' => time(),
            'from_id' => $data['from_id']
        ];


        $res2 = DB::table($table . '_log')->insertGetId($create_data);
        if ($res2) {
            return true;
        }
        return false;

    }


    /**
     * 得到酒馆信息
     * @param $id
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function get_one_pool($id)
    {

        return DB::table('bathing_pool')
            ->where('is_delete', '0')
//            ->where('is_open','1')
            ->where('id', $id)
            ->find();
    }

    public function get_list_pool()
    {

        return DB::table('bathing_pool')
            ->where('is_delete', '0')
            ->where('is_open', '1')
            ->select();
    }

    /**
     * 获取对应酒馆可兑换酒数
     * @param $key
     */
    public function get_tradable_num($key)
    {
        return DB::table('fish_tradable_num')
            ->where('is_delete', '0')
            ->where('key', $key)
            ->field('pool_id,id,f_num,key')
            ->find();
    }


    /**
     * 返回预约信息
     * @param $uid
     * @param $key
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function get_deduction_reserve_bait($uid, $key)
    {
        return Db::table('appointment_user')
            ->where('uid', $uid)
            ->where('key', $key)
            ->find();
    }


    /**
     * 获取用户剩余GTC
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function get_user_bait($uid)
    {
        return DB::table('my_wallet')
            ->where('is_balance_extension', '0')
            ->where('uid', $uid)
            ->find();
    }

    /**
     * 获取用户剩余积分
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function get_user_integral($uid)
    {
        return DB::table('my_integral')
            ->where('is_balance_extension', '0')
            ->where('uid', $uid)
            ->find();
    }

    /**
     * 获取用户基本信息
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function get_one_user($uid)
    {
        return DB::table('user')
            ->where('id', $uid)
            ->find();
    }

    /**
     * 收款信息
     * @param $uid
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function get_user_card($uid)
    {
        return DB::table('card')
            ->where('u_id', $uid)
            ->where('is_delete', 0)
            ->find();

    }

    /**
     * 扣除预约GTC
     * @param $uid    用户id
     * @param $bait   GTC
     * @param $pid    池id
     * @param $key    关联可以
     * @param $fail_return   返饵比例
     * @param $types 0 ：预约； 1：即抢
     * @param $rob_bait       即抢GTC
     * @return bool
     */
    public function deduction_reserve_bait($uid, $bait, $pid, $key, $fail_return, $types, $rob_bait, $end_time, $boi = 0)
    {
        $end_time = strtotime(date('H:i:s', $end_time));
        $is_gub = $this->get_user_bait($uid);
        if (empty($is_gub)) {
            return false;
        }
        if ($types) {
            $now_bait = $rob_bait; // 为1则是即时抢购
        } else {
            $now_bait = $bait; // 为0则是预约
        }

        if (empty($boi)) {
            $table = 'my_integral';
        } else {
            $table = 'my_wallet';
        }

        $is_mwupdate = DB::table($table)
            ->where('uid', $uid)
            ->where('is_balance_extension', 0)
            ->setDec('now', $now_bait);

        if (!$is_mwupdate) {
            return false;
        }


        $add['uid'] = $uid;
        $add['number'] = '-' . $now_bait;
        $add['now'] = $is_gub['now'];
        $add['from_id'] = $pid;
        if ($types) {
            $add['types'] = 6;
            $add['remark'] = '即抢';
        } else {
            $add['types'] = 3;
            $add['remark'] = '预约';
        }

        $add['future'] = $is_gub['now'] - $now_bait;
        $add['create_time'] = time();
        $is_mwladd = DB::table($table . '_log')
            ->insert($add);

        if (!$is_mwladd) {
            return false;
        }

        //添加预约记录开始

        $auadd['pool_id'] = $pid;
        $auadd['create_time'] = time();
        $auadd['status'] = 0;
        $auadd['key'] = $key;

        $auadd['uid'] = $uid;  //用户id
        $auadd['bait'] = '-' . $now_bait;      //扣除

//        $auadd['re_bait'] = $now_bait * ($fail_return /100) ;      //返饵数量
        $auadd['re_bait'] = bcmul($now_bait, $fail_return / 100, 2);      //返饵数量
        $auadd['re_bait'] = retain_2($auadd['re_bait']);      //返饵数量
        $auadd['re_boi'] = $boi;
        $auadd['types'] = $types;  //0预约 1即抢
        $auadd['pre_endtime'] = $end_time; //领取结束时间
        $is_auadd = Db::table('appointment_user')
            ->insert($auadd);

        if (!$is_auadd) {
            return false;
        }

//        添加预约记录结束

        $user_up['make_time'] = time();
        DB::table('user')->where('id', $uid)->update($user_up);

        return true;
    }

    /**
     * 扣除预约GTC
     * @param $uid    用户id
     * @param $bait   GTC
     * @param $pid    池id
     * @param $key    关联可以
     * @param $fail_return   返饵比例
     * @param $types 0 ：预约； 1：即抢
     * @param $rob_bait       即抢GTC
     * @return bool
     */
    public function pay_fish_change_bait($uid, $bait, $pid, $key, $fail_return, $types, $rob_bait, $end_time)
    {

    }


    /**
     * 获得预约信息
     * @param $uid
     * @param $key
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function get_appointment_user($uid, $key)
    {

        return Db::table('appointment_user')
            ->where('key', $key)
            ->where('uid', $uid)
            ->find();

    }


    /**
     * 保存点击领取操作
     * @param $id
     * @return int|string
     */
    public function set_appointment_user_status1($id, $pid, $key, $uid)
    {

        $lv = $this->re_adopt_lv($pid, $uid);

        $update['adopt_lv'] = $lv;//分配等级
        $update['status'] = 1;
        $update['update_time'] = time();
        $is_save = Db::table('appointment_user')
            ->where('id', $id)
            ->update($update);

        if ($is_save) {
            $arr = $this->calculation_lvnum($pid, $key);
            if ($arr) {
                $json = json_encode($arr);
                $ftnupdate['num_json'] = $json;
                $ftnupdate['update_time'] = time();
                $is_ftnsave = Db::table('fish_tradable_num')->where('key', $key)->where('is_delete', 0)->update($ftnupdate);

                //if(empty($is_ftnsave)){
                //    return false;
                //}
            }

            $user_up['make_time'] = time();
            DB::table('user')->where('id', $uid)->update($user_up);

            return true;
        }
        return false;

    }


    /**
     * 获取预约人数
     * @param $key
     */
    public function get_appointment_user_status1num($key)
    {
        return Db::table('appointment_user')
            ->where('key', $key)
            ->where('status', 1)
            ->count('id');

    }

    /**
     * 返回用户领取优先等级
     * @param $pid
     * @param $uid
     */
    public function re_adopt_lv($pid, $uid)
    {
        $is_pool = $this->get_one_pool($pid);
        $is_user = $this->get_one_user($uid);

        $profit = $is_user['profit'];


        if ($is_pool['open_section'] == 3) {

            if ($is_pool['first_section_min'] <= $profit && $is_pool['first_section_max'] > $profit) {
                return 1;
            }

            if ($is_pool['second_section_min'] <= $profit && $is_pool['second_section_max'] > $profit) {
                return 2;
            }

            if ($is_pool['third_section_min'] <= $profit && $is_pool['third_section_max'] > $profit) {
                return 3;
            }
        } elseif ($is_pool['open_section'] == 2) {
            if ($is_pool['first_section_min'] <= $profit && $is_pool['first_section_max'] > $profit) {
                return 1;
            }

            if ($is_pool['second_section_min'] <= $profit && $is_pool['second_section_max'] > $profit) {
                return 2;
            }
        } elseif ($is_pool['open_section'] == 1) {
            if ($is_pool['first_section_min'] <= $profit && $is_pool['first_section_max'] > $profit) {
                return 1;
            }

        }
        return 0;

    }


    /**
     * 获得酒分配比例
     * @param $pid
     * @param $key
     * @return bool
     */
    public function calculation_lvnum($pid, $key)
    {

        $is_pool = $this->get_one_pool($pid);
        $all = $this->get_tradable_num($key);

        if (empty($all['f_num'])) {
            return false;
        }
        $all_num = $all['f_num'];

        $lvarr[0]['all'] = 0;
        $lvarr[0]['over'] = 0;
        $lvarr[1]['all'] = 0;
        $lvarr[1]['over'] = 0;
        $lvarr[2]['all'] = 0;
        $lvarr[2]['over'] = 0;
        $lvarr[3]['all'] = 0;
        $lvarr[3]['over'] = 0;


        if ($is_pool['open_section']) {

            if ($is_pool['open_section'] == 1 || $is_pool['open_section'] == 2 || $is_pool['open_section'] == 3) {

                $L1_user = DB::table('appointment_user')
                    ->where('adopt_lv', 1)
                    ->where('status', 1)
                    ->where('key', $key)
                    ->count('id');
                $L1_fish = bcmul($all_num, $is_pool['first_section_percent'] / 100, 2);
//                $L1_fish = retain_2($L1_fish);
                if ($L1_fish > 0 && $L1_fish < 1) {
                    $L1_fish = 1;
                } else {
                    $L1_fish = floor($L1_fish);//实际分配酒数量
                }


                $tmpNum1 = 0;
                //人多酒少 给酒的数量
                if ($L1_user >= $L1_fish) {
                    $lvarr[1]['all'] = $L1_fish;

                } else {

                    //酒多人少
                    $lvarr[1]['all'] = $L1_user;
                    $tmpNum1 = $L1_fish - $L1_user;//多出的酒
                }


                if ($is_pool['open_section'] == 2 || $is_pool['open_section'] == 3) {


                    $L2_user = DB::table('appointment_user')
                        ->where('adopt_lv', 2)
                        ->where('status', 1)
                        ->where('key', $key)
                        ->count('id');

                    $L2_fish = bcmul($all_num, $is_pool['second_section_percent'] / 100, 2);
//                    $L2_fish = retain_2($L2_fish);
                    if ($L2_fish > 0 && $L2_fish < 1) {
                        $L2_fish = 1;
                    } else {
                        $L2_fish = floor($L2_fish);//实际分配酒数量
                    }

                    $L2_fish = $L2_fish + $tmpNum1;//上个区间多出的酒

                    $tmpNum2 = 0;
                    //人多酒少 给酒的数量
                    if ($L2_user >= $L2_fish) {
                        $lvarr[2]['all'] = $L2_fish;

                    } else {

                        //酒多人少
                        $lvarr[2]['all'] = $L2_user;
                        $tmpNum2 = $L2_fish - $L2_user;//多出的酒

                    }


                }

                if ($is_pool['open_section'] == 3) {

                    $L3_user = DB::table('appointment_user')
                        ->where('adopt_lv', 3)
                        ->where('status', 1)
                        ->where('key', $key)
                        ->count('id');

                    $L3_fish = bcmul($all_num, $is_pool['third_section_percent'] / 100, 2);
//                    $L3_fish = retain_2($L3_fish);

                    if ($L3_fish > 0 && $L3_fish < 1) {
                        $L3_fish = 1;
                    } else {
                        $L3_fish = floor($L3_fish);//实际分配酒数量
                    }

                    $L3_fish = $L3_fish + $tmpNum2;//上个区间多出的酒
                    $tmpfish = $L3_fish + $tmpNum2;//多出的酒

                    //人多酒少 给酒的数量
                    if ($L3_user >= $L3_fish) {
                        $lvarr[3]['all'] = $L3_fish;

                    } else {

                        //酒多人少
                        $lvarr[3]['all'] = $L3_user;
                        $tmpfish = $L3_fish - $L3_user;//多出的酒
                    }

                }


                $L0_user = DB::table('appointment_user')
                    ->where('adopt_lv', 0)
                    ->where('status', 1)
                    ->where('key', $key)
                    ->count('id');

                $L0_fish = $all_num - $lvarr[1]['all'] + $lvarr[2]['all'] + $lvarr[3]['all'];
                $L0_fish = floor($L0_fish);//实际分配酒数量


                //人多酒少 给酒的数量
                if ($L0_user >= $L0_fish) {
                    $lvarr[0]['all'] = $L0_fish;
                    return $lvarr;
                } else {

                    //酒多人少
                    $lvarr[0]['all'] = $L0_user;
                }

            }


        } else {

            $user = DB::table('appointment_user')
                ->where('status', 1)
                ->where('key', $key)
                ->count('id');


            $fish = $all_num;

            //人多酒少 给酒的数量
            if ($user >= $fish) {
                $lvarr[0]['all'] = $fish;
                return $lvarr;
            } else {

                //酒多人少
                $lvarr[0]['all'] = $user;
            }
        }


        //addMy_log('点击领取生成酒人分配数据',$lvarr);

        return $lvarr;

    }


    /**
     * 获得单次处理酒数
     */
    public function get_time_fish_num()
    {
        return Db::table('config')->where('key', 'time_fish_num')->where('status', 1)->find();
    }


    /**
     * 获取各区间酒分配数量
     * @param $key
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function get_lvnum($key)
    {
        return Db::table('fish_tradable_num')
            ->where('key', $key)
            ->where('is_delete', 0)
            ->field('num_json')
            ->find();
    }

    /**
     * 单次派酒
     * @param $key
     * @param $lv
     * @param $num
     */
    public function set_lvnum($key, $pid, $lv, $num)
    {


        $times = Config::getValue('voucher_time');

        $times = $times ? $times : 2;//超时时间
        // 获取该等级已经抢购成功的用户id
        $users = $this->get_lvusernum($key, $lv, $num);//获取该等级的用户id


        if (empty($users)) {
            //echo '>用户不存在<';
            return false;
        }

        // 获取当天等待预约的房子的id
        $fishs = $this->get_lvfishnum($key, $num);    //获取酒id
        if (empty($fishs)) {
            //echo '>鱼不存在<';
            return false;
        }

        $fishsnum = count($fishs);
        $users = array_slice($users, 0, $fishsnum);//过滤多余的人


        //跟新派酒数量
        $num_json = Db::table('fish_tradable_num')
            ->where('key', $key)
            ->field('num_json')
            ->find();

        if (empty($num_json['num_json'])) {
            $log['sql'] = Db::table('fish_tradable_num')->getLastSql();
            $log['times'] = time();
            //addMy_log('分配酒失败:num_json无数据',$log);
            //echo '>num_json无数据<';
            return false;
        }
        $toarr = json_decode($num_json['num_json'], true);
        if (empty($toarr[$lv])) {
            $log['sql'] = Db::table('fish_tradable_num')->getLastSql();
            $log['times'] = time();
            $log['lv'] = $lv;
            //echo '>分配错误6<';
            return false;
        }
        $over_num = $toarr[$lv]['over'];//已分配的人数


        foreach ($users as $k => $v) {

            Db::startTrans();
            try {

                $au_id = Db::table('appointment_user')
                    ->where('status', 1)//点击领取的用户
                    ->where('key', $key)
                    ->where('uid', $v)
                    ->value('id');

//            不测试时记得开启
                // 是否是自己的房子，是则跳过不分配
                $is_self = Db::table('fish')->where('id', $fishs[$k])->where('u_id', $v)->find();

                if ($is_self) {

                    $log['sql'] = Db::table('fish')->getLastSql();
                    $log['times'] = time();
                    addMy_log('分配酒失败:自己的酒跳出', $log);
                    Db::rollback();
                    Db::table('appointment_user')
                        ->where('uid', $v)
                        ->setInc('sort');//下次派酒排序往后推

                    continue;
                }

                // 获取待分配的房子信息
                $is_fish = Db::table('fish')
                    ->where('id', $fishs[$k])
                    ->find();


//                $is_user = Db::table('user')->where('id', $v)->find();
//                if ($is_user['status'] == -1) {
//                    $log['sql'] = Db::table('fish')->getLastSql();
//                    $log['times'] = time();
//                    addMy_log('用户被冻结，不允许房子流通!', $log);
//                    Db::rollback();
//                    continue;
//                }

                if (!$is_fish) {
                    $log['sql'] = Db::table('fish')->getLastSql();
                    $log['times'] = time();
                    addMy_log('分配酒失败:无效酒', $log);
                    Db::rollback();
                    continue;
                }

//                if($is_fish['pre_endtime'] < time()){
//                    $log['sql'] = Db::table('fish')->getLastSql();
//                    $log['times'] = time();
//                    addMy_log('分配酒失败:超时',$log);
//                    Db::rollback();
//                    continue;
//                }


                $service = new \app\common\service\Fish\Service();
                $worth = $service->get_worth($is_fish['id']);

                if (!$worth) {

                    $log['times'] = time();
                    addMy_log('分配酒失败:酒增益价值获取失败', $log);
                    Db::rollback();
                    continue;
                }

                $create_time = time();
                $date1 = date('Y-m-d H:i:s', $create_time);
                $get_au_2['f_id'] = $is_fish['id'];
                $get_au_2['status'] = 0;    //待支付
                $get_au_2['worth'] = $worth;
                $get_au_2['update_time'] = time();
                $get_au_2['order_number'] = 'FNO' . time() . $is_fish['id'];  //单号;
                $get_au_2['over_time'] = strtotime(date("Y-m-d H:i:s", strtotime("$date1 + $times hours")));  //提交凭证期限;
                $get_au_2['remarks'] = '交易';
                $get_au_2['types'] = $au_id;
                $get_au_2['bu_id'] = $v;
                $get_au_2['create_time'] = $create_time;


                $is_add = Db::table('fish_order')->insertGetId($get_au_2); // 新增订单


                if (empty($is_add)) {
                    $log['sql'] = Db::table('fish_order')->getLastSql();
                    $log['times'] = time();
                    addMy_log('分配酒失败:酒订单添加失败', $log);
                    Db::rollback();
                    continue;
                }


                $is_fishworth = get_fish_order_worth($is_add);
                if (empty($is_fishworth)) {
                    $audate['profit'] = 0;
                } else {
                    $audate['profit'] = $is_fishworth['num'];
                }

                $audate['status'] = 2;//已派酒
                $audate['update_time'] = time();
                $audate['oid'] = $is_add;
//        修改用户酒获取状态
                $au_update2 = Db::table('appointment_user')
                    ->where('status', 1)//点击抢酒的用户
                    ->where('key', $key)
                    ->where('uid', $v)
                    ->where('id', $au_id)
                    ->update($audate);

                if (!$au_update2) {
                    Db::rollback();
                    $log['sql'] = Db::table('appointment_user')->getLastSql();
                    $log['times'] = time();
                    addMy_log('分配酒失败', $log);
                    Db::rollback();

                    continue;

                }


                $fishup['order_id'] = $is_add;
                $fishup['status'] = 2;//等待用户转账
                $is_fishup = Db::table('fish')
                    ->where('id', $fishs[$k])
                    ->where('is_status', 1)
                    ->update($fishup);

                if (empty($is_fishup)) {
                    $log['sql'] = Db::table('fish')->getLastSql();
                    $log['times'] = time();
                    addMy_log('分配酒失败:订单id添加失败', $log);
                    Db::rollback();
                    continue;
                }


                $over_num += 1;


                $toarr[$lv]['over'] = $over_num;
                $tojson = json_encode($toarr);
//            统计已派送的酒数
                $num_jsonupdate['num_json'] = $tojson;
                $num_jsonupdate['update_time'] = time();
                $is_tradable_numupdate = Db::table('fish_tradable_num')
                    ->where('key', $key)
                    ->update($num_jsonupdate);


                if (empty($is_tradable_numupdate)) {
                    $log['sql'] = Db::table('fish_tradable_num')->getLastSql();
                    $log['times'] = time();
                    addMy_log('分配酒失败:酒数统计数量失败', $log);
                    Db::rollback();
                    continue;
                }


                Db::commit();


            } catch (\Exception $e) {
                $log['error'] = '派酒代码摆错';
                addMy_log('分配酒失败:酒数统计数量失败', $log);

                Db::rollback();
                return false;

            }


        }
        return true;


    }

    /**
     * 获取对应等级的用户id
     * @param $key
     * @param $lv
     * @param $num
     * @return array|bool|int
     */
    public function get_lvusernum($key, $lv, $num)
    {
        $arr = Db::table('appointment_user')
            ->alias('au')
//            ->leftJoin('user_verify_log uvl', 'uvl.uid = au.uid')
//            ->where('uvl.status', 1)
            ->where('au.status', 1)
            ->where('au.adopt_lv', $lv)
            ->where('au.key', $key)
            ->where('au.uid', '>', 0)
            ->field('au.uid')
            ->order('au.sort')
            ->limit(0, $num)
            ->select();
        $uids = 0;
        if ($arr) {
            $uids = array_column($arr, 'uid');
        }

        if ($uids) {
            return $uids;
        }
        return false;
    }


    /**
     * 获取酒id
     * @param $key
     * @param $num
     * @return array|bool|int
     */
    public function get_lvfishnum($key, $num)
    {
        $arr = Db::table('fish')->alias('f')
            ->leftJoin('user u', 'u.id = f.u_id')
            ->where('f.status', 1)
            ->where('u.status', '1')
            ->where('f.key', $key)
            ->where('f.is_status', 1)
            ->where('f.is_delete', '0')
            ->where('f.is_show', '1')
            ->where('f.u_id', '>', 0)
            ->field('f.id,f.worth,f.create_time')
            ->limit(0, $num)
            ->select();
        $ids = 0;
        if ($arr) {
            $ids = array_column($arr, 'id');
        }

        if ($ids) {
            return $ids;
        }
        return false;
    }


    public function get_arrfid_list($uid, $farr)
    {


        $is_f = DB::table('fish')
            ->alias('f')
            ->join('bathing_pool bp', 'bp.id = f.pool_id')
            ->where('f.is_delete', '0')
            ->where('f.u_id', $uid)
            ->where('f.id', 'in', $farr)
            ->field('f.contract_jsontime,f.worth,f.is_contract,f.lock_jsontime,f.is_lock,f.status3_jsontime,f.is_status,bp.contract_time,bp.bait,bp.profit,bp.lock_position')
            ->select();

        return $is_f;

    }

    /**
     * 拆分升级祖级酒
     * @param $fid
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPfishworth_num($fid)
    {

        $is_pfish = Db::table('fish')
            ->alias('f')
            ->join('bathing_pool bp', 'bp.id = f.pool_id')
            ->where('f.id', $fid)
            ->where('f.types', 'in', '1,2')
            ->field('f.types,f.front_id')
            ->find();

        if ($is_pfish) {
            $is_pfish['num'] = Db::table('fish')
                ->alias('f')
                ->join('bathing_pool bp', 'bp.id = f.pool_id')
                ->where('f.front_id', $is_pfish['front_id'])
                ->count('f.front_id');
            $is_pfish['worth'] = Db::table('fish')
                ->alias('f')
                ->join('bathing_pool bp', 'bp.id = f.pool_id')
                ->where('f.id', $is_pfish['front_id'])
                ->value('f.worth');
            $is_tmp = $this->getPfishworth_num($is_pfish['front_id']);
            if ($is_tmp['front_id'] > 0 && ($is_tmp['types'] == 1 || $is_tmp['types'] == 2)) {
                return $is_tmp;
            }

            return $is_pfish;
        }

    }


}
