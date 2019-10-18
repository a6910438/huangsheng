<?php
namespace app\common\service\Fish;

use app\common\entity\Fish;
use app\common\entity\ProductPool;
use app\index\model\Publics as PublicModel;
use think\Request;
use think\Session;
use think\Db;
class Service
{

    /**
     * 添加原始酒
     * @param $poolid    酒馆id
     * @param $values    价值
     * @param $num       酒的条数
     * @return bool
     */
    public function addData($poolid,$values,$num)
    {

        $Pool = new ProductPool();
        $is_pool = $Pool::where('is_delete',0)->where('id',$poolid)->field('id,name')->find();
        if(empty($is_pool)){
            return false;
        }


        $entity = new Fish();



        Db::startTrans();
        $data['worth'] = $values;//价值
        $data['is_contract'] = 1;//完成合约
        $data['pool_id'] = $poolid;
        $data['create_time'] = time();

        try {
            for ($i = 0; $i < $num; $i++) {
                $arr[$i] = $data;
            }


            if ($entity->insertAll($arr)) {
                Db::commit();
                return true;
            }

        } catch (\Exception $e) {
            Db::rollback();

            Log::addLog(Log::ADD_FISH, $e->getMessage(), [
                'pool_id'=>$poolid,
                'worth'=>$values,
                'num'=>$num,
            ]);
            return false;
        }
        Db::rollback();

        return false;
    }



    /**
     * 指定添加酒给用户
     * @param $fid 酒id
     * @param $uid 用户id
     * @param int $type
     */
    public function addUserfishData($fid,$uid){


        if(!$this->BuyFish($fid,$uid)){
            return false;
        }

        return true;
    }


    /**
     * 指定交易操作
     *
     * @param int $fid
     * @param int $uid
     * @return array|bool|false|\PDOStatement|string|\think\Model
     */
    public function BuyFish($fid = 0,$uid = 0){

        if(empty($fid) || empty($uid)){
            return false;
        }
        $uservice = new \app\common\service\Users\Service();
        if (!$uservice->checkUserStatus($uid)) {
            return false;
        }

        $Fish = new Fish();
        $is_fish = $Fish->where('id',$fid)->where('is_delete','0')->where('status','0')->where('buy_types','0')->field('pool_id,worth,u_id,id,worth')->find();
        if(!$is_fish || empty($is_fish['pool_id'])){
            return false;
        }
        $ProductPool = new ProductPool();
        $ProductPool->where('id',$is_fish['pool_id']);
        $ProductPool->where('is_delete',0);
        $ProductPool->field('id,contract_time,lock_position,name,lv,status,worth_max,worth_min');
        $is_pool =  $ProductPool->find();
        if(empty($is_pool)){
            return false;
        }


//新酒

//--------------前生信息开始-------------
        $add_fishdata['front_id'] = $is_fish['id'];    //酒前生id
        $add_fishdata['front_name'] = $is_pool['name'];//酒前生名字
        $add_fishdata['front_worth'] = $is_fish['worth'];    //前生价值

//--------------前生信息结束-------------


//--------------今生信息开始-------------
        $add_fishdata['u_id'] = $uid;                  //获得酒的用户id
        $add_fishdata['create_time'] = time();
        $add_fishdata['worth'] = $is_fish['worth'];    //价值
        $add_fishdata['pool_id'] = $is_pool['id'];    //酒馆id



//--------------今生信息结束-------------



        $time = time();

        (int)$day = ceil($is_pool['contract_time']/24);


        $arrday = array();
        for ($i=1 ; $i<=$day;$i++){
            if($i > 1){
                $tmptime =   $arrday[$i-1]['ntime'];
            }else{
                $tmptime = $time;
            }

            $arrday[$i]['stime'] = $tmptime;             //品酒开始时间
            $arrday[$i]['ntime'] = strtotime("{$i} day");//品酒结束时间
            $arrday[$i]['is_contract'] = 0;

            $contract_overtime = $arrday[$i]['ntime'];

        }





//原酒





        Db::startTrans();
        try {


            $add_fishdata['types'] = 3;    //后台指定生成
            $order_add['remarks'] = '平台赠送';
            $order_add['f_id'] = $fid;
            $order_add['bu_id'] = $uid;
            $order_add['status'] = 1;
            $order_add['update_time'] = time();
            $order_add['worth'] = $is_fish['worth'];

            $oid = Db::table('fish_order')->insertGetId($order_add);
            $fish_save['order_id'] = $oid;

            if(empty($oid)){
                Db::rollback();
                return false;
            }






            $add_fishdata['contract_overtime'] = 0;   //已品酒时间（秒）
            $add_fishdata['feed_overtime'] = $contract_overtime;  //预计品酒完成时间
            $addfish = new Fish();

//        添加新酒
            $is_addfish = $addfish->insertGetId($add_fishdata);

            if(!$is_addfish){
                Db::rollback();
                return false;
            }

//--------------修改原酒开始-------------
            $upfish = new Fish();


            $fish_save['status'] = 4;                   //以交易

            $fish_save['buy_types'] = 1;           //前端/后台交易
            $fish_save['buy_time'] =  time();           //交易时间
            $fish_save['lv'] = $is_pool['lv'];          //交易等级（已作废）


            $is_save = $upfish->where('id',$fid)->update($fish_save);

            if(!$is_save){
                Db::rollback();
                return false;
            }

//--------------修改原酒结束-------------


        } catch (\Exception $e) {
            Db::rollback();

            return false;
        }
        Db::commit();


        return $is_addfish;

    }


    /**
     * @param int $pid     酒馆id
     * @param int $uid     用户id
     * @param int $num     酒数
     * @param int $values  价值
     * @param int $is_feed  是否参与品酒
     * @return bool|int|string
     */
    public function addUserFish($pid = 0,$uid = 0,$num = 0,$values = 0,$is_feed = 0){

        if(empty($pid) || empty($uid) || $num <= 0 || $values<= 0 ){
            return false;
        }
        $uservice = new \app\common\service\Users\Service();
        if (!$uservice->checkUserStatus($uid)) {
            return false;
        }

        $Fish = new Fish();

        $ProductPool = new ProductPool();
        $ProductPool->where('id',$pid);
        $ProductPool->where('is_delete',0);
        $ProductPool->field('id,contract_time,lock_position,name,lv,status,worth_max,worth_min');
        $is_pool =  $ProductPool->find();

        if(empty($is_pool)){
            return false;
        }






        if($is_feed){ //即卖
            $add_fishdata['is_status'] = 1;
            $add_fishdata['feed_time'] = time();
            $contract_overtime = time();
            $add_fishdata['types'] = 6;    //后台赠送即卖
            $add_fishdata['contract_overtime'] = 0;   //已品酒时间（秒）

        }else{  //正常流程
            (int)$day = ceil($is_pool['contract_time']/24);

            $contract_overtime = strtotime("{$day} day");
            $add_fishdata['types'] = 0;    //后台赠送正常流程
            $add_fishdata['contract_overtime'] = 0;   //已品酒时间（秒）

        }


        Db::startTrans();
        try {


            $add_fishdata['front_id'] = 0;    //酒前生id
            $add_fishdata['front_name'] = $is_pool['name'];//酒前生名字
            $add_fishdata['front_worth'] = 0;    //前生价值


            $add_fishdata['u_id'] = $uid;                  //获得酒的用户id
            $add_fishdata['create_time'] = time();
            $add_fishdata['worth'] = $values;    //价值
            $add_fishdata['pool_id'] = $is_pool['id'];    //酒馆id


            $add_fishdata['feed_overtime'] = $contract_overtime;  //预计品酒完成时间

            $addfish = new Fish();

//        添加新酒


            $order_add['remarks'] = '平台赠送';
            $order_add['bu_id'] = $uid;
            $order_add['status'] = 2;
            $order_add['update_time'] = time();
            $order_add['worth'] = $values;



            for ($i =0;$i <$num;$i++ ){

                $is_addfish = $addfish->insertGetId($add_fishdata);

                if(!$is_feed){
//                    初始化品酒时间
                    $is_feed_time = $this->add_feed_time($day,$is_addfish, $add_fishdata['create_time'],1);
                    if(!$is_feed_time){
                        Db::rollback();
                        return false;
                    }

                }else{
                    $is_feed_time = $this->add_feed_time(1,$is_addfish, $add_fishdata['create_time'],1,1);
                    if(!$is_feed_time){
                        Db::rollback();
                        return false;
                    }
                }






                $order_add['f_id'] = $is_addfish;
                $order_add['order_number'] = 'ZS'.time().$is_addfish;
                $oid = Db::table('fish_order')->insertGetId($order_add);
                if(empty($oid)){
                    Db::rollback();
                    return false;
                }


                if(!$is_addfish){
                    Db::rollback();
                    return false;
                }

                $add['f_id'] = $is_addfish;
                $add['now_worth'] = retain_2($values);
                $add['front_worth'] = 0.00 ;
                $add['num'] =  $values;
                $add['types'] =  4 ;
                $add['create_time'] =  time() ;

                $is_fi = DB::table('fish_increment')->insert($add);//增值记录


                if(!$is_fi){
                    Db::rollback();
                    return false;
                }

//                $PublicModel = new  PublicModel;
//                 添加收益
//                $user_info = $PublicModel->add_user_profit($uid,$values,2);
//                if(!$user_info){
//                    Db::rollback();
//                    return false;
//                }

            }



        } catch (\Exception $e) {
            Db::rollback();

            return false;
        }
        Db::commit();


        return $is_addfish;

    }


    /**
     * 积分购买酒
     * @param int $pid
     * @param int $uid
     * @param int $num
     * @param int $type 1团队 2推广
     * @param int $reduce 减去的积分
     * @return bool|int|string
     */
    public function add_buy_integral_fish($pid = 0,$uid = 0,$num = 0,$type,$reduce){


        if(empty($pid) || empty($uid) || $num <= 0  || $type == 0 ){
            return false;
        }
        $uservice = new \app\common\service\Users\Service();
        if (!$uservice->checkUserStatus($uid)) {
            return false;
        }

        $Fish = new Fish();

        $ProductPool = new ProductPool();
        $ProductPool->where('id',$pid);
        $ProductPool->where('is_delete',0);
        $ProductPool->field('id,contract_time,lock_position,name,lv,status,worth_max,worth_min');
        $is_pool =  $ProductPool->find();
        $values = $is_pool['worth_min'];

        if(empty($is_pool)){
            return false;
        }







        (int)$day = ceil($is_pool['contract_time']/24);

        $contract_overtime = strtotime("{$day} day");
        $add_fishdata['types'] = 0;    //后台赠送正常流程
        $add_fishdata['contract_overtime'] = 0;   //已品酒时间（秒）

        $is_user = Db::table('user')->where('id',$uid)->find();

        if(!$is_user){
            return false;
        }

        Db::startTrans();
        try {


            if($type == 1){
                if($is_user['is_prohibitteam'] == 1){
                    return false;
                }

                $add['uid'] = $uid;
                $add['old'] = $is_user['now_team_integral'];
                $add['new'] = $is_user['now_team_integral'] - $reduce;
                $add['createtime'] = time();
                $add['number'] = -$reduce;
                $is_log = Db::table('team_log')->insert($add);


                $is_reduce =  Db::table('user')->where('id',$uid)->setDec('now_team_integral',$reduce);

            }elseif ($type == 2){


                if($is_user['is_prohibit_extension'] == 1){
                    return false;
                }
                $add['uid'] = $uid;
                $add['old'] = $is_user['now_prohibit_integral'];
                $add['new'] = $is_user['now_prohibit_integral'] - $reduce;
                $add['createtime'] = time();
                $add['number'] = -$reduce;
                $add['type'] = 2;

                $is_log = Db::table('prohibit_log')->insert($add);

                $is_reduce =  Db::table('user')->where('id',$uid)->setDec('now_prohibit_integral',$reduce);
            }else{
                return false;
            }

            if(!$is_reduce && !$is_log ){
                return false;
            }


            $add_fishdata['front_id'] = 0;    //酒前生id
            $add_fishdata['front_name'] = $is_pool['name'];//酒前生名字
            $add_fishdata['front_worth'] = 0;    //前生价值


            $add_fishdata['u_id'] = $uid;                  //获得酒的用户id
            $add_fishdata['create_time'] = time();
            $add_fishdata['worth'] = $values;    //价值
            $add_fishdata['pool_id'] = $is_pool['id'];    //酒馆id

            $add_fishdata['feed_overtime'] = $contract_overtime;  //预计品酒完成时间

            $addfish = new Fish();

//        添加新酒


            $order_add['remarks'] = '积分兑换';
            $order_add['bu_id'] = $uid;
            $order_add['status'] = 2;
            $order_add['update_time'] = time();
            $order_add['worth'] = $values;



            for ($i =0;$i <$num;$i++ ){

                $is_addfish = $addfish->insertGetId($add_fishdata);

//                    初始化品酒时间
                $is_feed_time = $this->add_feed_time($day,$is_addfish, $add_fishdata['create_time'],1);
                if(!$is_feed_time){
                    Db::rollback();
                    return false;
                }





                $order_add['f_id'] = $is_addfish;
                $order_add['order_number'] = 'JF'.time().$is_addfish;
                $oid = Db::table('fish_order')->insertGetId($order_add);
                if(empty($oid)){
                    Db::rollback();
                    return false;
                }


                if(!$is_addfish){
                    Db::rollback();
                    return false;
                }
                unset($add);
                $add['f_id'] = $is_addfish;
                $add['now_worth'] = retain_2($values);
                $add['front_worth'] = 0.00 ;
                $add['num'] =  $values;
                $add['types'] =  5 ;
                $add['create_time'] =  time() ;

                $is_fi = DB::table('fish_increment')->insert($add);//增值记录


                if(!$is_fi){
                    Db::rollback();
                    return false;
                }

                $PublicModel = new  PublicModel;

//                $user_info = $PublicModel->add_user_profit($uid,$values,2);
//                if(!$user_info){
//                    Db::rollback();
//                    return false;
//                }

            }

            Db::commit();


        } catch (\Exception $e) {
            Db::rollback();

            return false;
        }


        return $is_addfish;

    }


    /**
     * 天数计算
     * @param $begin_time
     * @param $end_time
     * @return int
     */
    public function timediff($begin_time,$end_time)
    {

        //计算天数
        $timediff = $end_time-$begin_time;
        $days = ceil($timediff/86400);

        return $days;
    }


    /**
     * 返回已品酒天数
     * @param $fid
     * @param $type
     * @return int
     */
    public function get_all_feed_time($fid){


        $is_f = DB::table('fish')
            ->alias('f')
            ->join('bathing_pool bp','bp.id = f.pool_id')
            ->where('f.id',$fid)
            ->field('f.worth,f.is_re,f.is_contract,f.contract_overtime,f.lock_overtime,f.all_time,f.is_lock_num,f.is_lock,f.is_status,bp.contract_time,bp.bait,bp.profit,bp.lock_position,bp.status,bp.worth_max')
            ->find();

        if ($is_f['is_re'] == 1 ){
            //重返酒馆
            $i_ft = $this->get_feed_time($fid,3);
        }elseif($is_f['is_lock_num'] > 0 && $is_f['is_contract'] == 1 ){
            //锁仓
            $i_ft2 = $this->get_feed_time($fid,2);
//           dump($i_ft2);exit;
            $i_ft1 = $this->get_feed_time($fid,1);

            if(!$i_ft2){
                $i_ft2['f_time'] = 0;
            }
            if(!$i_ft1){
                $i_ft1['f_time'] = 0;
            }
            $i_ft['f_time'] =  $i_ft1['f_time'] + $i_ft2['f_time'];
        }else{
            $i_ft = $this->get_feed_time($fid,1);

        }


        if($i_ft){
            $time = $i_ft['f_time'];
        }else{
            $time = 0;
        }
        if($time <= 0){
            $time = 0;
        }

        return $time;
    }



    /**
     * 品酒时间
     * @param $fid
     * @param $contract_time
     * @param int $types
     * @return mixed
     */
    public function get_feed_time($fid,$types=1){

        $is_fish =   DB::table('fish')
            ->alias('f')
            ->join('bathing_pool bp','bp.id = f.pool_id')
            ->where('f.is_delete','0')
            ->where('f.id',$fid)
            ->field('f.worth,f.is_re,f.is_contract,f.create_time,f.contract_overtime,f.re_overtime,f.lock_time,f.all_time,f.is_lock_num,f.is_lock,f.is_status,bp.contract_time,bp.bait,bp.profit,bp.lock_position,bp.status,bp.worth_max')
            ->find();
        $cday = $is_fish['contract_time']/24;//合约天数

        if(!$is_fish){
            return 0;
        }
        $re['f_time'] =0;

        //合约
        if($types == 1 ){

            if($is_fish['is_contract']){    //完成合约
                $re['f_time'] = $is_fish['contract_time']; //合约品酒时间
                $re['no_time'] = 0;                //还差品酒时间

            }

        }elseif ($types == 2){

            //锁仓
            if($is_fish['is_lock']){        //完成锁仓
                $re['f_time'] = $is_fish['contract_time'];       //锁仓品酒时间
                $re['no_time'] = 0;
            }
        }elseif ($types == 3){
            if($is_fish['is_re'] && $is_fish['is_status'] == 1 ){  //完成返仓喂酒
                $re['f_time'] = 24;      //返仓品酒时间
                $re['no_time'] = 0;
            }

        }else{
            $re['f_time'] = 0;
            $re['no_time'] = 0;
        }




        //还没有完成的，统计时间
        if(empty($re['f_time'])){
//       最开始时间
            if($types == 2){
                $fist_time =  Db::table('fish_feed_log')->where('fid',$fid)->where('types',1)->order('ntime desc')->where('is_feed',1)->value('ntime');//返池酒要确保只有一条信息，在反酒以及品酒中控制

            }else{
                $fist_time =  Db::table('fish_feed_log')->where('fid',$fid)->where('types',$types)->order('stime asc')->where('is_feed',1)->value('stime');//返池酒要确保只有一条信息，在反酒以及品酒中控制
            }


            $date1 = date('H:i:s',$fist_time);
            $nowtime = strtotime($date1);


            $days = $this->timediff($fist_time,time());//该状态养殖到现在共多少时间

            if($days <=0){
                $days = 1;
            }

            if($cday<=0){
                return 0;
            }

            //返仓
            if($types == 3 && $is_fish['is_re'] == 1){

                $cday == 1;

            }




            //合约/锁仓
            if($cday){
                $fee_day = 0;
                for($i=0 ;$i < $days;$i++ ){
//
                    $this_day = $fist_time + ($i*86400);
                    $in_times = $this_day  + 100;

                    //如果大于当前时间跳出
                    if($this_day > time()){
                        continue;
                    }
                    $is_feed = Db::table('fish_feed_log')
                        ->where('fid',$fid)
                        ->where('types',$types)
                        ->where('stime','<',$in_times)
                        ->where('ntime','>',$in_times)
                        ->where('is_feed',1)
                        ->find() ;
//                    echo Db::table('fish_feed_log')->getLastSql();
//                    dump(date('Y-m-d H:i:s',$is_feed['stime']));
//                    dump(date('Y-m-d H:i:s',$this_day));
//                    dump(date('Y-m-d H:i:s',$is_feed['ntime']));
//                    echo '11111';
//                    dump($is_feed);
//                    dump(date('Y-m-d H:i:s',$this_day));
//                    dump(date('Y-m-d H:i:s',$in_times));
//                    exit;

                    if($is_feed){
                            $fee_day = $fee_day+1;

                    }else{

                        $if_now = $this_day + 86400;
                        if($if_now < time()){
                            $fee_day = $fee_day -1;

                        }
                    }

                    if($fee_day < 0){
                        $fee_day = 0;
                    }

                }
                if($fee_day < 0){
                    $fee_day = 0;
                }elseif($fee_day > $cday){
                    $fee_day = $cday;
                }


                $re['f_time'] = $fee_day * 24;
                $re['no_time'] = ($cday - $fee_day) * 24;


            }
//            exit;
            if($re['f_time'] < 0){
                $re['f_time']  = 0;
            }elseif ($re['f_time'] > ($cday *24)){
                $re['f_time'] = ($cday *24);
            }
            if($re['no_time'] < 0){
                $re['no_time']  = 0;
            }elseif ($re['no_time'] > ($cday *24)){
                $re['no_time'] = ($cday *24);
            }
        }

        return $re;
    }






    /**
     *
     * @param $day    需要品酒的天数
     * @param $fid    酒的id
     * @param $time   开始时间
     * @param $types  1：合约 2：锁仓 3：返池
     */
    public function add_feed_time($day,$fid,$time,$types,$is_feed = 0){

        $date1 = date('Y-m-d H:i:s',$time);
        $time = $time;
        $arrday=array();
        for ($i=1 ; $i<=$day;$i++){
            if($i > 1){
                $tmptime =   $arrday[$i-1]['ntime'];
            }else{
                $tmptime = $time;
            }

            $arrday[$i]['fid']   = $fid;
            $arrday[$i]['stime'] = $tmptime;             //品酒开始时间
            $arrday[$i]['ntime'] = strtotime("$date1 +$i day");//品酒结束时间
            if($is_feed){
                $arrday[$i]['is_feed'] = 1;
            }else{
                $arrday[$i]['is_feed'] = 0;
            }

            $arrday[$i]['types'] = $types;

        }

        if($types == 3){
            Db::table('fish_feed_log')->where('types',3)->where('fid',$fid)->delete();
        }
        return Db::table('fish_feed_log')->insertAll($arrday);


    }



    /**
     * 拆分
     * @param int $fid 酒id
     * @param $arr  拆分瓶数以及对于价值
     * @param $num  品酒后价值，后台拆分写0
     * @return bool|int|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function SplitFish($fid = 0){


    $Fish = new Fish();
    $is_fish = $Fish->where('id',$fid)->field('feed_overtime,pool_id,u_id,id,worth,is_contract,is_lock,is_re')->find();
    if(!$is_fish || empty($is_fish['pool_id'])){
        return false;
    }

    $ProductPool = new ProductPool();
    $ProductPool->where('id',$is_fish['pool_id']);
    $ProductPool->where('is_delete',0);
    $ProductPool->where('status',1);
    $ProductPool->field('id,contract_time,worth_max,profit,lock_position,name,status,worth_max,worth_min,num');
    $is_pool =  $ProductPool->find();


    $profit = $is_pool['profit'];              //收益百分比
    $contract_time = $is_pool['contract_time'];//合约时间
    $lock_position = $is_pool['lock_position'];//倍数
    $days = $contract_time/24;

    $dbprofit = retain_2($profit*$lock_position); //锁仓收益

    $re_profit = retain_2($profit/$days); //返池  比例




    if(empty($is_pool)){
        return false;
    }

    //返池 完成锁仓
    if($is_fish['is_re']){
//            返池完成锁仓
//            $worth = $is_fish['worth'] + ($is_fish['worth'] * ($re_profit/100));
        $worth = $is_fish['worth'] + bcmul($is_fish['worth'] , $re_profit/100,2);

    }elseif ($is_fish['is_lock']){
//            完成锁仓
//            $worth = $is_fish['worth'] + ($is_fish['worth'] * ($dbprofit/100));
        $worth = $is_fish['worth'] + bcmul($is_fish['worth'] ,$dbprofit/100,2);


    }else{
//        完成合约
//            $worth = $is_fish['worth'] + ($is_fish['worth'] * ($profit/100));
        $worth = $is_fish['worth'] + bcmul($is_fish['worth'] ,$profit/100,2);

    }
    $worth = (int)$worth;


//        $worth = retain_2($worth);
    if($is_pool['worth_max'] > $worth){
        return false;
    }

//*********

    $num = $is_pool['num'];

    if($num >1){
        $tmp = $worth/$num;
        $tmp = floor($tmp);


        $pp = new ProductPool();
        $pp->where('is_delete',0);
        $pp->where('is_open',1);
        $pp->where('worth_max','>',$tmp);
        $pp->where('worth_min','<=',$tmp);
        $pid = $pp->value('id');

        if(!$pid){
            return false;
        }
        for ($i = 0; $i < $num ;$i++){
            $arr[$i] = $tmp;

        }


    }else{
        return false;
    }



    $uid = $is_fish['u_id'];



//新酒






//--------------前生信息开始-------------
    $add_fishdata['front_id'] = $is_fish['id'];    //酒前生id
    $add_fishdata['front_name'] = $is_pool['name'];//酒前生名字

    $add_fishdata['front_worth'] = $worth;

//--------------前生信息结束-------------



//--------------今生信息开始-------------
    $add_fishdata['u_id'] = $uid;                  //获得酒的用户id
    $add_fishdata['create_time'] = time();
    $add_fishdata['pool_id'] = $pid;    //酒馆id
    $add_fishdata['types'] = 1;    //拆分生成
    $add_fishdata['is_status'] = 1;    //完成品酒



//--------------今生信息结束-------------




    $contract_overtime = $is_fish['feed_overtime'];



    $add_fishdata['feed_overtime'] = $contract_overtime;  //预计品酒完成时间
    $addfish = new Fish();

    foreach ($arr as $k => $v){


        $add[$k] = $add_fishdata;
        $add[$k]['worth'] = $v;    //价值
        $is_addfish = $addfish->insertGetId($add[$k]);

        //        添加新酒
        if(!$is_addfish){
            return false;
        }


    }










//--------------修改原酒开始-------------


    $upfish = new Fish();

    $fish_save['status'] = 0;
    $fish_save['is_delete'] = 1;                //拆分完毕消失
    $fish_save['update_time'] = time();



    $is_save = $upfish->where('id',$fid)->update($fish_save);

    if(!$is_save){
        return false;
    }



//--------------修改原酒结束-------------



    return $is_addfish;

}


    /**
     * 升级
     * @param int $fid

     * @return bool|int|string
     */
    public function UpgradeFish($fid = 0){

        $Fish = new Fish();
        $is_fish = $Fish->where('id',$fid)->field('feed_overtime,pool_id,u_id,id,worth,is_contract,is_lock,is_re')->find();

        if(!$is_fish || empty($is_fish['pool_id'])){
            return false;
        }

        $ProductPool = new ProductPool();
        $ProductPool->where('id',$is_fish['pool_id']);
        $ProductPool->where('is_delete',0);
        $ProductPool->where('status',0);
        $ProductPool->field('id,contract_time,worth_max,profit,lock_position,name,status,worth_max,worth_min,num');
        $is_pool =  $ProductPool->find();

        $profit = $is_pool['profit'];              //收益百分比
        $contract_time = $is_pool['contract_time'];//合约时间
        $lock_position = $is_pool['lock_position'];//倍数
        $days = $contract_time/24;

        $dbprofit = retain_2($profit*$lock_position); //锁仓收益

        $re_profit = retain_2($profit/$days); //返池  比例




        if(empty($is_pool)){
            return false;
        }

        //返池 完成锁仓
        if($is_fish['is_re']){
//            返池完成锁仓
//            $worth = $is_fish['worth'] + ($is_fish['worth'] * ($re_profit/100));
            $worth = $is_fish['worth'] + bcmul($is_fish['worth'] ,$re_profit/100,2);

        }elseif ($is_fish['is_lock']){
//            完成锁仓
//            $worth = $is_fish['worth'] + ($is_fish['worth'] * ($dbprofit/100));
            $worth = $is_fish['worth'] + bcmul($is_fish['worth'] ,$dbprofit/100,2);


        }else{
//        完成合约
//            $worth = $is_fish['worth'] + ($is_fish['worth'] * ($profit/100));
            $worth = $is_fish['worth'] + bcmul($is_fish['worth'] ,$profit/100,2);


        }
        $worth = (int)$worth;


//        $worth = retain_2($worth);
        if($is_pool['worth_max'] > $worth){
            return false;
        }


        $pp = new ProductPool();
        $pp->where('is_delete',0);
        $pp->where('is_open',1);
        $pp->where('worth_max','>',$worth);
        $pp->where('worth_min','<=',$worth);
        $pool = $pp->find();
        if(!$pool){
            return false;
        }







        $uid = $is_fish['u_id'];



//新酒






//--------------前生信息开始-------------
        $add_fishdata['front_id'] = $is_fish['id'];    //酒前生id
        $add_fishdata['front_name'] = $is_pool['name'];//酒前生名字

        $add_fishdata['front_worth'] = $is_fish['worth'];

//--------------前生信息结束-------------



//--------------今生信息开始-------------
        $add_fishdata['u_id'] = $uid;                  //获得酒的用户id
        $add_fishdata['create_time'] = time();
        $add_fishdata['pool_id'] = $pool['id'];    //酒馆id
        $add_fishdata['types'] = 2;
        $add_fishdata['is_status'] = 1;    //完成品酒



//--------------今生信息结束-------------




        $contract_overtime = $is_fish['feed_overtime'];



        $add_fishdata['feed_overtime'] = $contract_overtime;  //预计品酒完成时间
        $addfish = new Fish();




        $add = $add_fishdata;
        $add['worth'] = $worth;    //价值

        $is_addfish = $addfish->insertGetId($add);

        //        添加新酒
        if(!$is_addfish){
            return false;
        }












//--------------修改原酒开始-------------


        $upfish = new Fish();

        $fish_save['status'] = 0;
        $fish_save['is_delete'] = 1;                //升级完毕消失
        $fish_save['update_time'] = time();



        $is_save = $upfish->where('id',$fid)->update($fish_save);

        if(!$is_save){
            return false;
        }


    }


    /**
     * 拆分升级
     * @param $fid 酒id
     * @param $type 0 升级
     * @return bool
     */
    public  function splistORupgradeFish($fid,$type){
        $service = new \app\common\service\Fish\Service();

        if($type== 0) {

           $is_ok = $this->UpgradeFish($fid);

        }else{
            //拆分

            $is_ok = $this->SplitFish($fid);

        }
        if(!$is_ok ){
            return false;
        }
        return true;


    }



//    public function UpgradeFish($fid = 0){
//
//        $Fish = new Fish();
//        $is_fish = $Fish->where('id',$fid)->field('feed_overtime,pool_id,u_id,id,worth,is_contract,is_lock,is_re')->find();
//
//        if(!$is_fish || empty($is_fish['pool_id'])){
//            return false;
//        }
//
//        $ProductPool = new ProductPool();
//        $ProductPool->where('id',$is_fish['pool_id']);
//        $ProductPool->where('is_delete',0);
//        $ProductPool->where('status',0);
//        $ProductPool->field('id,contract_time,worth_max,profit,lock_position,name,status,worth_max,worth_min,num');
//        $is_pool =  $ProductPool->find();
//
//        $profit = $is_pool['profit'];              //收益百分比
//        $contract_time = $is_pool['contract_time'];//合约时间
//        $lock_position = $is_pool['lock_position'];//倍数
//        $days = $contract_time/24;
//
//        $dbprofit = retain_2($profit*$lock_position); //锁仓收益
//
//        $re_profit = retain_2($profit/$days); //返池  比例
//
//
//
//
//
//        if(empty($is_pool)){
//            return false;
//        }
//
//        //返池 完成锁仓
//        if($is_fish['is_re']){
////            返池完成锁仓
////            $worth = $is_fish['worth'] + ($is_fish['worth'] * ($re_profit/100));
//            $worth = $is_fish['worth'] + bcmul($is_fish['worth'] ,$re_profit/100,2);
//
//        }elseif ($is_fish['is_lock']){
////            完成锁仓
////            $worth = $is_fish['worth'] + ($is_fish['worth'] * ($dbprofit/100));
//            $worth = $is_fish['worth'] + bcmul($is_fish['worth'] ,$dbprofit/100,2);
//
//
//        }else{
////        完成合约
////            $worth = $is_fish['worth'] + ($is_fish['worth'] * ($profit/100));
//            $worth = $is_fish['worth'] + bcmul($is_fish['worth'] ,$profit/100,2);
//
//        }
//        $worth = (int)$worth;
////        $worth = retain_2($worth);
//        if($is_pool['worth_max'] > $worth){
//            return false;
//        }
//
//
//        $pp = new ProductPool();
//        $pp->where('is_delete',0);
//        $pp->where('is_open',1);
//        $pp->where('worth_max','>',$worth);
//        $pp->where('worth_min','<=',$worth);
//        $poolid = $pp->value('id');
//
//        if(!$poolid){
//            return false;
//        }
//
//        $up['pool_id'] = $poolid;
//        $up['update_time'] = time();
//        $Fish = new Fish();
//        $is_save = $Fish->where('id',$fid)->update($up);
//
//        if(!$is_save){
//            return false;
//        }
//        return true;
//    }

    /**
     * 获取该酒完成合约价值
     * @param $fid
     * @return bool|string
     */
    public function get_worth($fid){


        $Fish = new Fish();
        $is_fish = $Fish->where('id', $fid)->field('feed_overtime,pool_id,u_id,id,worth,is_contract,is_lock,is_re,types')->find();

        if (!$is_fish || empty($is_fish['pool_id'])) {
            return false;
        }

        $ProductPool = new ProductPool();
        $ProductPool->where('id', $is_fish['pool_id'])
            ->where('is_delete', 0)

            ->field('id,contract_time,worth_max,profit,lock_position,name,status,worth_max,worth_min,num');
        $is_pool = $ProductPool->find();

        $profit = $is_pool['profit'];              //收益百分比
        $contract_time = $is_pool['contract_time'];//合约时间
        $lock_position = $is_pool['lock_position'];//倍数
        $days = $contract_time / 24;

        $dbprofit = retain_2($profit * $lock_position); //锁仓收益

        $re_profit = retain_2($profit / $days); //返池  比例


        if (empty($is_pool)) {
            return false;
        }

        //返池 完成
        if ($is_fish['is_re']) {
//            返池完成
            $worth = $is_fish['worth'] + ($is_fish['worth'] * ($re_profit / 100));

        } elseif($is_fish['types'] == 1|| $is_fish['types'] == 2){
//                升级拆分的酒
            $worth = $is_fish['worth'];

        }elseif ($is_fish['is_lock']) {
//            完成锁仓
            $worth = $is_fish['worth'] + ($is_fish['worth'] * ($dbprofit / 100));


        } else {
//        完成合约
            $worth = $is_fish['worth'] + ($is_fish['worth'] * ($profit / 100));

        }
        return  $worth = (int)$worth;
    }



    /**
     * 交易生成新酒
     * @param int $fid
     * @param int $uid
     * @param int $oid
     * @return bool|int|string
     */
    public function BuyFishIndex($fid = 0,$uid = 0,$oid = 0){


        if(empty($fid) || empty($uid)){
            return false;
        }

        $Fish = new Fish();
        $is_fish = $Fish->where('id',$fid)->where('is_delete','0')->where('buy_types','0')->field('pool_id,worth,u_id,id')->find();

        if(!$is_fish || empty($is_fish['pool_id'])){
            return false;
        }
        $ProductPool = new ProductPool();
        $ProductPool->where('id',$is_fish['pool_id']);
        $ProductPool->where('is_delete',0);
        $ProductPool->field('id,contract_time,lock_position,name,lv,status,worth_max,worth_min');
        $is_pool =  $ProductPool->find();

        if(empty($is_pool)){
            return false;
        }

        $is_order = Db::table('fish_order')->where('id',$oid)->find();
        if(!$is_order){
            return false;
        }

//新酒

//--------------前生信息开始-------------
        $add_fishdata['front_id'] = $is_fish['id'];          //酒前生id
        $add_fishdata['front_name'] = $is_pool['name'];      //酒前生名字
        $add_fishdata['front_worth'] = $is_fish['worth'];    //前生未增值的价值

//--------------前生信息结束-------------


//--------------今生信息开始-------------
        $add_fishdata['u_id'] = $uid;                  //获得酒的用户id
        $add_fishdata['create_time'] = time();
        $add_fishdata['worth'] = $is_order['worth'];    //价值


        $pp = new ProductPool();
        $pp->where('is_delete',0);
        $pp->where('is_open',1);
        $pp->where('worth_max','>', $is_order['worth']);
        $pp->where('worth_min','<=', $is_order['worth']);
        $pid = $pp->value('id');

        if($pid){
            $add_fishdata['pool_id'] = $pid;    //酒馆id
        }else{
            $add_fishdata['pool_id'] = $is_pool['id'];    //酒馆id
        }



//--------------今生信息结束-------------



        $time = time();

        (int)$day = ceil($is_pool['contract_time']/24);


        $arrday = array();
        for ($i=1 ; $i<=$day;$i++){
            if($i > 1){
                $tmptime =   $arrday[$i-1]['ntime'];
            }else{
                $tmptime = $time;
            }

            $arrday[$i]['stime'] = $tmptime;             //品酒开始时间
            $arrday[$i]['ntime'] = strtotime("{$i} day");//品酒结束时间
            $arrday[$i]['is_feed'] = 0;
            $arrday[$i]['types'] = 1;

            $contract_overtime = $arrday[$i]['ntime'];

        }






//原酒





        if(empty($oid)){
            return false;
        }

        $add_fishdata['types'] = 4;    //交易生成
        $add_fishdata['is_show'] = 0;    //确认交易后才显示
        $add_fishdata['contract_overtime'] = 0;   //已品酒时间（秒）
        $add_fishdata['feed_overtime'] = $contract_overtime;  //预计品酒完成时间
        $addfish = new Fish();

//        添加新酒
        $is_addfish = $addfish->insertGetId($add_fishdata);

        if(!$is_addfish){
            return false;
        }

//--------------修改原酒开始-------------
        $upfish = new Fish();

        $fish_save['order_id'] = $oid;
        $fish_save['status'] = 3;                   //以交易
        $fish_save['buy_types'] = 2;                //后台交易
        $fish_save['buy_time'] =  time();           //交易时间
        $fish_save['lv'] = $is_pool['lv'];          //交易等级

        $is_save = $upfish->where('id',$fid)->update($fish_save);

        if(!$is_save){
            return false;
        }

        foreach ($arrday as $k => $v){
            $arrday[$k]['fid'] = $is_addfish;
        }

        $is_addlog = Db::table('fish_feed_log')->insertAll($arrday);
        if(!$is_addlog){
            return false;
        }


//--------------修改原酒结束-------------



        return $is_addfish;

    }

}