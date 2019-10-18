<?php

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

//use think\Controller;
//use think\Cookie;
use think\Db;
use app\common\entity\User;
//use think\Request;
use app\common\entity\Config;
use app\index\model\Publics as PublicModel;
/* 短信通知 */

use app\common\model\SendSms;
/* Queue队列 */

use think\Queue;
use think\Log;
/* websocket */
use GatewayClient\Gateway;

class Every extends Command
{
    public $public_test = 0;

    public $output;

    protected function configure()
    {
        $this->setName('every')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->output = new Output;
        file_put_contents(dirname(__FILE__) . '/output.log', date("Y-m-d H:i:s"));
        $output->writeln("Start Command : ");
        while (true) {
            $h = date('H');
            $m = date('i');
            $hm = $h . $m;
            //结束可锁仓操作，跟新为可跟新酒
            $this->no_lock();
            //生成对应酒馆投放信息（统计可投放酒的数量，key标记当天可投放酒）
            $output->writeln('=>生成对应酒馆投放信息（统计可投放酒的数量，key标记当天可投放酒');
            $this->generate_pool_order();
            //派酒给用户:（把已点击抢酒的用户分配酒）
            $output->writeln('=>派酒给用户:（把已点击抢酒的用户分配酒）');
            $this->deliver_fish();
            //给派到酒的用户发送短信（完成未上定时任务）
            $output->writeln('=>给派到酒的用户发送短信（完成未上定时任务）');
            $this->deliver_fish_sns();
            //完成订单
            $output->writeln('=>完成订单');
            $this->over_fish_order();
            //预约未领取
            $this->pre_over_time_fish();
            //点击领取未分配到的[人多酒少]酒馆领取时间结束半个小时执行
            $this->pre_no_fish();
            //点击领取未分配到的[酒多人少]
            $this->pre_no_man();
            //未上传凭证
            $this->over_time_no_voucher();
            // $this->fish_order_test_sms();
            //清空当天标记【当天要结束时才执行】
            if ($hm == "2359" || $hm == "0000") {
                $this->empty_key();
            }
            //等待60秒执行一次循环
            sleep(60);
        };
        //任务结束
        $output->writeln("done.");
    }


    /**
     * 拆分
     */
    public function split_upgrade()
    {

//      不能在这里拆分
        exit;
        $PublicModel = new  PublicModel;
        $pool = $PublicModel->get_list_pool();

        $time = time();

        //是否有开放的酒馆
        if ($pool) {
            //升级
            foreach ($pool as $k => $v) {

//                    升级
                if ($v['status'] == 0) {

                    $this->set_upgrade_fish($v['id']);

                } elseif ($v['status'] == 1) {
                    //拆分

                    $this->set_split_fish($v['id']);

                }


            }


        }

    }


    /**
     * 获取可以拆分的酒
     * @param $pid 酒馆id
     * @param int $num 执行数量
     * @return bool
     */
    public function set_split_fish($pid, $num = 1000)
    {

        $arr = Db::table('fish')
            ->where('is_status', 1) //装修完成
            //->where('feed_overtime','>',time())
            ->where('status', 0)
            ->where('is_delete', 0)
            ->where('pool_id', $pid)
            ->where('is_show', 1)
            ->field('id')
            ->limit(0, $num)
            ->select();
        if ($arr) {
            $service = new \app\common\service\Fish\Service();


            foreach ($arr as $v) {


                Db::startTrans();
                try {

                    $is_save = $service->SplitFish($v['id']);
                    if (!$is_save) {
                        $log['fid'] = $v['id'];
                        $log['times'] = time();
                        addMy_log('拆分失败', $log);

                        Db::rollback();
                        $this->output->writeln('拆分失败');
                        continue;
                    }

                    Db::commit();

                } catch (\Exception $e) {

                    $log['times'] = time();
                    addMy_log('拆分代码报错', $log);
                    Db::rollback();
                    $this->output->writeln('代码报错');
                    continue;

                }
            }

        } else {
            return false;
        }

    }


    /**
     * 升级
     * @param $pid
     * @param int $num
     * @return bool
     */
    public function set_upgrade_fish($pid, $num = 1000)
    {

        $arr = Db::table('fish')
            ->where('is_status', 1) //装修完成
            ->where('feed_overtime', '>', time())
            ->where('status', 0)
            ->where('is_delete', 0)
            ->where('is_show', 1)
            ->where('pool_id', $pid)
            ->field('id')
            ->limit(0, $num)
            ->select();

        if ($arr) {
            $service = new \app\common\service\Fish\Service();


            foreach ($arr as $v) {


                Db::startTrans();
                try {

                    $is_save = $service->UpgradeFish($v['id']);
                    if (!$is_save) {
                        $log['fid'] = $v['id'];
                        $log['times'] = time();
                        addMy_log('升级失败', $log);
                        Db::rollback();
                        continue;
                    }

                    Db::commit();

                } catch (\Exception $e) {

                    $log['times'] = time();
                    addMy_log('升级代码报错', $log);
                    Db::rollback();
                    $this->output->writeln('代码报错');
                    continue;

                }
            }

        } else {
            return false;
        }


    }


    /**
     * 完成合约以及达到年龄的标记为可售卖，不在允许锁仓
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function no_lock()
    {

        $PublicModel = new  PublicModel;
        $pool = $PublicModel->get_list_pool();
        $num = 5000;
        $time = time();

        $list = array();
        //是否有开放的酒馆
        if ($pool) {


            foreach ($pool as $k => $v) {

                $this->output->writeln('酒馆' . $v['id'] . '<br>');

                $fishs = Db::table('fish')
                    ->where('status', 0)       //正常
                    ->where('is_delete', 0)    //未被删除
                    ->where('is_status', 0)    //未被标记
                    ->where('is_contract', 1)  //完成合约装修
                    ->where('u_id', '>', 0)
                    ->where('is_lock_num', 0) //未锁仓的
                    ->where('is_lock', 0)
                    ->where('is_show', 1)
                    ->where('pool_id', $v['id'])
//                  ->where('pool_id',61)
                    ->field('id')
                    ->limit(0, $num)
                    ->select();
                if (!$fishs) {
                    $this->output->writeln('无可执行酒馆');
                    continue;
                }


                Db::startTrans();
                try {
                    $count = 0;
                    foreach ($fishs as $fk => $fv) {

                        $service = new \app\common\service\Fish\Service();
                        $age = get_fagetime($fv['id']);


                        if ($v['contract_time'] <= $age) {
                            $update['is_status'] = 1;
                            $update['update_time'] = time();
                            $if_is = Db::table('fish')
                                ->where('id', $fv['id'])
                                ->update($update);

                            if (!$if_is) {
                                Db::rollback();
                                continue;
                            }
                        }


                    }


                    Db::commit();


                    continue;

                } catch (\Exception $e) {

                    Db::rollback();
                    $this->output->writeln('代码报错');
                    continue;

                }


            }

        }

    }


    /**
     * 生成投放信息
     * 标记可预约的酒，
     */
    public function generate_pool_order()
    {

        $PublicModel = new  PublicModel;
        $pool = $PublicModel->get_list_pool();
        $num = 5000;
        $time = time();

        $list = array();
        //是否有开放的酒馆
        if ($pool) {


            foreach ($pool as $k => $v) {
                $stime = strtotime(date('Y-m-d'));                             //当天开始时间(当天凌晨)

                $stime = $stime + 60 * 30;//前面需要拆分酒所以要推迟执行时间
                $ntime = strtotime(date('H:i:s', $v['about_start_time']));      //预约开始时间（预约开始）
                $pre_endtime = strtotime(date('H:i:s', $v['end_time']));        //抢酒结束时间
                $pre_starttime = strtotime(date('H:i:s', $v['start_time']));
//                if(1){
                if ($stime <= $time && $pre_starttime >= $time || $this->public_test == 1) {

                    $this->output->writeln('酒馆' . $v['id'] . '<br>');

                    $key = get_today_key($v['id']);//获取当天的key
                    $this->output->writeln('当天酒馆key: '.$key);
                    $fishs = Db::table('fish')
                        ->alias('f')
                        ->join('user u', 'u.id = f.u_id')
                        ->where('u.status', 1)    //
                        ->where('f.status', 0)    //
                        ->where('f.is_delete', 0)    //
                        ->where('f.is_status', 1) //装修完成
                        ->where('f.u_id', '>', 0)
                        //->where('f.feed_overtime','<=',time()) //满足装修时间
                        ->where('f.is_show', 1)
                        ->where('f.pool_id', $v['id'])
                        ->field('f.id')
                        ->limit(0, $num)
                        ->select();

                    if (!$fishs) {
                        $this->output->writeln('无可执行酒馆');
                        continue;
                    }

                    Db::startTrans();
                    try {
                        $count = 0;

                        foreach ($fishs as $fk => $fv) {

                            $service = new \app\common\service\Fish\Service();


                            if ($v['status'] == 0) {

                                //升级

                                $is_save = $service->UpgradeFish($fv['id']);

                            } elseif ($v['status'] == 1) {
                                //拆分

                                $is_save = $service->SplitFish($fv['id']);

                            }


                            $update['key'] = $key;
                            $update['status'] = 1;
                            $update['update_time'] = $time;
                            $update['pre_endtime'] = $pre_endtime; //抢酒结束时间
                            $if_is = Db::table('fish')
                                ->where('status', 0)    //
                                ->where('is_delete', 0)    //
                                ->where('is_status', 1) //装修完成
                                ->where('u_id', '>', 0)
                                //->where('feed_overtime','<=',time()) //满足装修时间
                                ->where('is_show', 1)
                                ->where('pool_id', $v['id'])
                                ->where('id', $fv['id'])
                                ->update($update);
                            if ($if_is) {
                                $count = Db::table('fish')->where('key', $key)->count('id');
                            }


                        }


                        if (empty($count)) {
                            $count = 0;
                        }

                        $bpsave['key'] = $key;
                        $bpsave['update_time'] = $time;
                        $is_bpsave = Db::table('bathing_pool')
                            ->where('id', $v['id'])
                            ->update($bpsave);

                        if (!$is_bpsave) {
                            Db::rollback();
                            continue;
                        }

//                      统计酒的数量
                        $is_fnum = DB::table('fish_tradable_num')->where('key', $key)->where('pool_id', $v['id'])->find();
                        if ($is_fnum) {

                            $fumupdate['f_num'] = $is_fnum['f_num'] + $count;
                            $fumupdate['update_time'] = time();
                            $is_fumsave = DB::table('fish_tradable_num')->where('key', $key)->update($fumupdate);


                            //日志

                            if (!$is_fumsave) {
                                //日志
                                $last_log['msg'] = 'uperror';
                                $last_log['val'] = $fumupdate['f_num'];
                                $last_log['sql'] = DB::table('fish_tradable_num')->getLastSql();
                            } else {
                                //日志
                                $last_log['msg'] = 'upok';
                                $last_log['val'] = $fumupdate['f_num'];
                                $last_log['sql'] = DB::table('fish_tradable_num')->getLastSql();

                            }

                        } else {
                            $fumsave['create_time'] = time();
                            $fumsave['pool_id'] = $v['id'];
                            $fumsave['key'] = $key;
                            $fumsave['f_num'] = $count;
                            $is_fumsave = DB::table('fish_tradable_num')->insert($fumsave);
                            if (!$is_fumsave) {
                                //日志
                                $last_log['msg'] = 'adderror';
                                $last_log['val'] = $count;
                                $last_log['sql'] = DB::table('fish_tradable_num')->getLastSql();
                            } else {
                                //日志
                                $last_log['msg'] = 'addok';
                                $last_log['val'] = $count;
                                $last_log['sql'] = DB::table('fish_tradable_num')->getLastSql();
                            }


                        }
                        if (empty($is_fumsave)) {
//                            跟新添加失败
                            $this->output->writeln('报错失败');

                            Db::rollback();
                            continue;
                        }
                        Db::commit();

                        $log = json_encode($last_log);
                        $this->output->writeln($log);
                        continue;

                    } catch (\Exception $e) {

                        Db::rollback();
                        $this->output->writeln('代码报错');
                        continue;

                    }
                }

                $this->output->writeln('无可执行酒馆<br>');

            }

        }

    }
//
//    public function generate_pool_order(){
//
//        $PublicModel = new  PublicModel;
//        $pool = $PublicModel->get_list_pool();
//
//        $time = time();
//
//        $list = array();
//        //是否有开放的酒馆
//        if($pool){
//
//
//            foreach ($pool as $k => $v){
//                $stime = strtotime(date('Y-m-d'));                             //当天开始时间(当天凌晨)
//
//                $stime = $stime + 60 * 30;//前面需要拆分酒所以要推迟执行时间
//                $ntime = strtotime(date('H:i:s',$v['about_start_time']));      //预约开始时间（预约开始）
//                $pre_endtime =  strtotime(date('H:i:s',$v['end_time']));        //抢酒结束时间
//
////                if(1){
//                if($stime <= $time && $ntime >= $time || $this->public_test == 1){
//
//
//
//
//
//
//
//                    $this->output->writeln('酒馆'.$v['id'].'<br>');
//
//
//                    $key = get_today_key($v['id']);//获取当天的key
//
//
//                    Db::startTrans();
//                    try {
//
//
//
//
//                        $update['key'] = $key;
//                        $update['status'] = 1;
//                        $update['update_time'] = $time;
//                        $update['pre_endtime'] = $pre_endtime; //抢酒结束时间
//                        $count = Db::table('fish')
//                            ->where('status',0)    //
//                            ->where('is_delete',0)    //
//                            ->where('is_status',1) //装修完成
//                            ->where('u_id','>',0)
//                            ->where('feed_overtime','<=',time()) //满足装修时间
//                            ->where('is_show',1)
//                            ->where('pool_id',$v['id'])
//                            ->update($update);
////                        dump($count);exit;
//                        if(empty($count)){
//                            $count = 0;
//                        }
//
//                        $bpsave['key'] = $key;
//                        $bpsave['update_time'] = $time;
//                        $is_bpsave = Db::table('bathing_pool')
//                            ->where('id',$v['id'])
//                            ->update($bpsave);
//
//                        if(!$is_bpsave){
//                            Db::rollback();
//                            continue;
//                        }
//
////                      统计酒的数量
//                        $is_fnum = DB::table('fish_tradable_num')->where('key',$key)->where('pool_id',$v['id'])->find();
//                        if($is_fnum){
//
//                            $fumupdate['f_num'] = $is_fnum['f_num']+$count;
//                            $fumupdate['update_time'] = time();
//                            $is_fumsave =  DB::table('fish_tradable_num')->where('key',$key)->update($fumupdate);
//
//
//                            //日志
//
//                            if(!$is_fumsave){
//                                //日志
//                                $last_log['msg'] ='uperror';
//                                $last_log['val'] = $fumupdate['f_num'];
//                                $last_log['sql'] = DB::table('fish_tradable_num')->getLastSql();
//                            }else{
//                                //日志
//                                $last_log['msg'] ='upok';
//                                $last_log['val'] = $fumupdate['f_num'];
//                                $last_log['sql'] = DB::table('fish_tradable_num')->getLastSql();
//
//                            }
//
//                        }else{
//                            $fumsave['create_time'] = time();
//                            $fumsave['pool_id'] =$v['id'];
//                            $fumsave['key'] = $key;
//                            $fumsave['f_num'] = $count;
//                            $is_fumsave = DB::table('fish_tradable_num')->insert($fumsave);
//                            if(!$is_fumsave){
//                                //日志
//                                $last_log['msg'] ='adderror';
//                                $last_log['val'] =$count;
//                                $last_log['sql'] = DB::table('fish_tradable_num')->getLastSql();
//                            }else{
//                                //日志
//                                $last_log['msg'] ='addok';
//                                $last_log['val'] =$count;
//                                $last_log['sql'] = DB::table('fish_tradable_num')->getLastSql();
//                            }
//
//
//                        }
//                        if(empty($is_fumsave)){
////                            跟新添加失败
//                            $this->output->writeln('报错失败');
//
//                            Db::rollback();
//                            continue;
//                        }
//                        Db::commit();
//
//                        $log = json_encode($last_log);
//                        $this->output->writeln($log;
//                        continue;
//
//                    } catch (\Exception $e) {
//
//                        Db::rollback();
//                        $this->output->writeln('代码报错');
//                        continue;
//
//                    }
//                }
//
//                $this->output->writeln('无可执行酒馆');
//
//            }
//
//        }
//
//    }
    /**
     * 派酒
     */

    public function deliver_fish()
    {
        $PublicModel = new  PublicModel;
        $pool = $PublicModel->get_list_pool();

        $time = time();
        if ($pool) {

            $time_fish_num = $PublicModel->get_time_fish_num();

            if (empty($time_fish_num['value'])) {
                $tmp_num = 5000;
            } else {
                $tmp_num = $time_fish_num['value'];
            }

            foreach ($pool as $k => $v) {
                $this->output->writeln('正在处理->' . $v['name']);

                $stime = strtotime(date('H:i:s', $v['end_time']));      //抢购结束时间
                $ntime = $stime + 30 * 60; // 截止分配房子时间


                //if(1){
                // 抢购结束半小时内分配房子
                if ($stime <= $time && $ntime >= $time || $this->public_test == 1) {

                    $key = $v['key'];
                    if (empty($key)) {
                        $this->output->writeln('缺失识别key');
                        continue;
                    }


//                    用户点击领取时生成
                    //获取各区间可以得到的酒
                    $json = $PublicModel->get_lvnum($key);

                    if (empty($json['num_json'])) {
                        $this->output->writeln('无领取申请数据');
                        continue;
                    }


                    $arr = json_decode($json['num_json'], true);

                    foreach ($arr as $ak => $av) {
                        if ($ak < 3) {
                            $tmp_key = $ak + 1;
                        } else {
                            $tmp_key = 0;
                        }

                        if ($arr[$tmp_key]['all'] > 0) {
                            if ($arr[$tmp_key]['all'] > $arr[$tmp_key]['over']) {
                                $num = $arr[$tmp_key]['all'] - $arr[$tmp_key]['over'];
                                $this->output->writeln('分配剩余酒数=' . $num);


//                                单次执行数量
                                if ($tmp_num > 0 && $num > $tmp_num) {
                                    $num = $tmp_num;
                                }


                                //执行酒分配操作
                                $is_add = $PublicModel->set_lvnum($key, $v['id'], $tmp_key, $num);

                                if (!$is_add) {
                                    $this->output->writeln('添加失败2');
                                    continue;
                                } else {
                                    $this->output->writeln('添加完成<br>');
                                }
                            }

                        }
                    }


                } else {
                    $this->output->writeln('未到执行时间');
                }

            }
            $this->output->writeln('完成');

        } else {
            $this->output->writeln('无可执行酒馆');
        }


    }


    /**
     * 完成操作
     * @return string
     */
    public function over_fish_order()
    {
        $PublicModel = new  PublicModel;

        $num = Config::getValue('auto_ok_order_time');
        $num = $num ? $num : 2;

        $limit = Config::getValue('auto_ok_order_time_limit');
        $limit = $limit ? $limit : 1000;

        $time = time();
        $time = date("Y-m-d H:i:s", $time);
        $time = strtotime("$time - $num hours ");//两小时前
        $map['au.buy_time'] = ['<', $time];//两小时前完成的订单
        $map['au.status'] = 3;//上传凭证的
        $map['au.new_fid'] = ['>', 0];
        $map['fo.status'] = 1;//上传支付凭证
        $map['f.is_show'] = 1;
        $map['f.is_delete'] = 0;

        //获取以及提交凭证没有提交申诉的用户
        $msglist = Db::table('appointment_user')
            ->alias('au')
            ->join('fish_order fo', 'au.id = fo.types', 'INNER')
            ->join('fish f', 'f.order_id = fo.id', 'INNER')
            ->join('bathing_pool bp', 'bp.id = f.pool_id', 'INNER')
            ->where($map)
            ->field('au.id,f.worth,f.front_id,f.types,f.front_worth,f.id f_id,f.is_re,f.u_id,fo.id fo_id,au.new_fid,bp.status bpstatus,bp.num')
            ->paginate($limit)
            ->toArray();

//        dump($msglist);exit;
        $msglist = $msglist['data'];
        if (empty($msglist)) {
            return '无可更新数组';
        }


        $bay_time = time();

        foreach ($msglist as $k => $v) {
            Db::startTrans();
            try {


                $au_up['status'] = 4; //转账完成
                $au_up['update_time'] = time();
                $au_up['okpay_time'] = time();
                $is_au = Db::table('appointment_user')->where('id', $v['id'])->update($au_up);
                if (!$is_au) {
                    $log = Db::table('appointment_user')->getLastSql();
                    $this->output->writeln('修改状态失败' . $log);
                    Db::rollback();

                    continue;
                }

                $f_up['status'] = 4; //转账完成
                $f_up['update_time'] = time();
                $f_up['buy_types'] = 2;
                $is_f = Db::table('fish')->where('id', $v['f_id'])->update($f_up);
                if (!$is_f) {
                    $log = Db::table('fish')->getLastSql();
                    $this->output->writeln('修改状态失败' . $log);
                    Db::rollback();

                    continue;
                }
                $nf_up['is_show'] = 1;//显示
                $nf_up['update_time'] = time();

                $is_nf = Db::table('fish')->where('id', $v['new_fid'])->update($nf_up);
                if (!$is_nf) {
                    $log = Db::table('fish')->getLastSql();
                    $this->output->writeln('修改新酒显示状态失败' . $log);
                    Db::rollback();

                    continue;
                }

                $fo_up['status'] = 2; //转账完成
                $fo_up['update_time'] = time();
                $is_fo = Db::table('fish_order')->where('id', $v['fo_id'])->update($fo_up);
                if (!$is_fo) {
                    $log = Db::table('fish_order')->getLastSql();
                    $this->output->writeln('修改状态失败' . $log);
                    Db::rollback();
                    continue;
                }


                //添加积分以及记录
                $PublicModel = new  PublicModel;
                if ($v['types'] == 1 || $v['types'] == 2) {
//                    $tmpworth = Db::table('fish')->where('id',$v['front_id'])->value('worth');
                    $PublicModel = new  PublicModel;
                    //获取- 拆分升级祖级酒信息
                    $is_f = $PublicModel->getPfishworth_num($v['f_id']);

                    if ($is_f) {
                        $tmpworth = $is_f['worth'];
                        $num = $is_f['num'];
                        $tmpworth = bcdiv($tmpworth, $num, 2);

                    } else {
                        $this->output->writeln('获取祖级酒失败' . $log);
                        Db::rollback();
                        continue;
                    }
                    if ($v['types'] == 1) {
                        //拆分


                        $f_worth0 = (int)$tmpworth;

                        if ($v['is_re']) {
                            $f_worth1 = Db::table('fish_order')->where('id', $v['fo_id'])->value('worth');
                        } else {
                            $f_worth1 = Db::table('fish')->where('id', $v['new_fid'])->value('worth');
                        }


                    } else {
                        //升级
                        $f_worth0 = (int)$tmpworth;

                        if ($v['is_re']) {
                            $f_worth1 = Db::table('fish_order')->where('id', $v['fo_id'])->value('worth');
                        } else {
                            $f_worth1 = Db::table('fish')->where('id', $v['new_fid'])->value('worth');
                        }
                    }

                } else {
                    $f_worth0 = Db::table('fish')->where('id', $v['f_id'])->value('worth');
                    $f_worth1 = Db::table('fish_order')->where('id', $v['fo_id'])->value('worth');
                }
                $user_worth = $f_worth1 - $f_worth0;
                $user_worth = (int)$user_worth;
                $is_add = $PublicModel->add_user_profit($v['u_id'], $user_worth, 3, $v['fo_id']);
                if (!$is_add) {
                    Db::rollback();
                    $this->output->writeln("auid:{$v['id']}uid:{$v['u_id']}价值{$v['worth']}添加记录失败");
                    continue;


                }

                $entity = new \app\common\entity\MyWallet();
                if ($user_worth > 0) {
                    $entity->bonusDispense($user_worth, $v['u_id'], 2, 1, 0, $v['fo_id']);//推广收益

                    $entity->teamDispense($user_worth, $v['u_id'], 2, 1, 0, $v['fo_id']);//团队收益
                }

                Db::commit();

                $log = json_encode($msglist);
                $this->output->writeln($log);
                continue;


            } catch (\Exception $e) {
                $this->output->writeln('代码报错');
                continue;
                Db::rollback();

            }


        }


    }


    /**
     * 预约未领取的
     * @return string
     */
    public function pre_over_time_fish()
    {
        $PublicModel = new  PublicModel;
        $pool = $PublicModel->get_list_pool();
        $time = time();

        //是否有开放的酒馆
        if ($pool) {


            foreach ($pool as $k => $v) {

                $stime = strtotime(date('H:i:s', $v['end_time']));      // 领取结束时间
                $ntime = strtotime(date('Y-m-d 23:59:59'));


//                if(1){
                if ($stime <= $time && $ntime >= $time || $this->public_test == 1) {


                    Db::startTrans();
                    try {


                        //没有点击领取的不会提前配酒
                        $update['au.status'] = -2;
                        $update['au.update_time'] = $time;

                        $is_endtime = Db::table('appointment_user')
                            ->alias('au')
                            ->where('au.pre_endtime', '<', $time)    //超时
                            ->where('au.status', '0')
                            ->where('pool_id', $v['id'])
                            ->update($update);


                        if (empty($is_endtime)) {
                            Db::rollback();
                            $this->output->writeln('无');
                            continue;
                        }
                        Db::commit();
                        $this->output->writeln('成功');
                        continue;

                    } catch (\Exception $e) {
                        $this->output->writeln('代码错误');
                        Db::rollback();
                        continue;
                    }
                } else {
                    $this->output->writeln('不在时间段');
                }

            }

        }
    }


    /**
     * 人多酒少
     */
    public function pre_no_fish()
    {
        $PublicModel = new  PublicModel;
        $pool = $PublicModel->get_list_pool();

        $time = time();
        $limit = 10000;
        //是否有开放的酒馆
        if ($pool) {


            foreach ($pool as $k => $v) {
                $this->output->writeln('酒馆:' . $v['id'] . '<br>');
                $this->output->writeln($v['name'] . '<br>');


                $stime = strtotime(date('H:i:s', $v['end_time']));      //预约结束时间（预约开始）
                $stime = $stime + 60 * 30;


                $ntime = strtotime(date('Y-m-d 23:59:59'));


//                if(1){
                if ($stime <= $time && $ntime >= $time || $this->public_test == 1) {


                    Db::startTrans();
                    try {


                        //没有点击领取的不会提前配酒


                        $is_endtime = Db::table('appointment_user')
                            ->alias('au')
                            ->where('au.pre_endtime', '<', $time)    //预约过期时间
                            ->where('au.status', '1')
                            ->field('au.id,au.re_bait,au.uid,au.re_boi')
                            ->where('pool_id', $v['id'])
                            ->paginate($limit)
                            ->toArray();

                        $msglist = $is_endtime['data'];


                        if (empty($msglist)) {
                            $this->output->writeln('无可更新数组<br>');
                            Db::rollback();
                            continue;
                        }


                        //添加返回记录 修改GTC值

                        foreach ($msglist as $lk => $lv) {


                            $update['status'] = -1;
                            $update['update_time'] = $time;

                            $is_up = Db::table('appointment_user')
                                ->alias('au')
                                ->where('au.id', $lv['id'])    //
                                ->update($update);

                            if (empty($is_up)) {
                                Db::rollback();
                                $this->output->writeln('修改记录失败<br>');
                                continue;
                            }
                            $data['uid'] = $lv['uid'];
                            $data['from_id'] = $lv['id'];
                            $data['remark'] = '返料';
                            $data['number'] = $lv['re_bait'];

                            $PublicModel = new PublicModel;
                            $res = $PublicModel->RechargeLog($data, 4, $lv['re_boi']);


                            if (!$res) {
                                $log['times'] = time();
                                $log['data'] = json_encode($data);
                                addMy_log('返料失败', $log);
                                Db::rollback();
                                $this->output->writeln('添加记录失败<br>');
                                continue;
                            }

                            Db::commit();
                            $this->output->writeln('完成<br>');
                            continue;

                        }


                    } catch (\Exception $e) {

                        Db::rollback();
                        $this->output->writeln('报错<br>');
                        continue;
                    }
                } else {
                    $this->output->writeln('未到执行时间' . date('Y-m-d H:i:s', $stime) . '<br/>');
                }

            }


        }
    }


    /**
     * 多出的酒
     */
    public function pre_no_man()
    {
        $PublicModel = new  PublicModel;
        $pool = $PublicModel->get_list_pool();
        $time = time();
        $limit = 10000;
        //是否有开放的酒馆
        if ($pool) {


            foreach ($pool as $k => $v) {
                $this->output->writeln($v['name']);
                $this->output->writeln('<br>');
                $stime = strtotime(date('H:i:s', $v['end_time']));      //预约结束时间（预约开始）
                $stime = $stime + 60 * 30;

                $ntime = strtotime(date('Y-m-d 23:59:59'));


//                if(1){
                if ($stime <= $time && $ntime >= $time || $this->public_test == 1) {


                    $tmptime = $time;
                    $tmptime = date('Y-m-d H:i:s', $tmptime);
                    $tmptime = strtotime("$tmptime +1 day");
                    $tmptime = $tmptime - (2 * 60 * 60);
                    $update['is_re'] = 1;//酒交易失败

                    $update['feed_overtime'] = $tmptime;//结束


                    $update['status'] = 0;
                    $update['buy_types'] = 0;
                    $update['key'] = 0;
                    $update['update_time'] = $time;
                    $update['pre_endtime'] = 0;
                    $update['is_status'] = 2;
                    $update['re_overtime'] = 0;

                    $msglist = Db::table('fish')
                        ->where('pre_endtime', '<', $time) //超过排队抢酒时间
                        ->where('status', '1') //等待预约
                        ->where('pool_id', $v['id'])
                        ->field('id,worth,is_contract,is_lock,is_re,types')
                        ->paginate($limit)
                        ->toArray();

                    $msglist = $msglist['data'];


                    if (empty($msglist)) {
                        $this->output->writeln('无可执行酒<br>');
                        Db::rollback();
                        continue;
                    }

//                        $ids = array_column($msglist,'id');


                    foreach ($msglist as $idv) {
                        Db::startTrans();
                        try {
                            if ($idv['is_re']) {
                                $cday = $v['contract_time'] / 24;
                                $values = bcmul($idv['worth'], ($v['profit'] / 100) / $cday, 2);

                            } elseif ($idv['is_lock']) {
                                //拆分升级的
                                if ($idv['types'] == 1 || $idv['types'] == 2) {
                                    $values = 0;
                                } else {
                                    $values = bcmul($idv['worth'], $v['lock_position'] * $v['profit'] / 100, 2);
                                }
                            } else {
                                //拆分升级的
                                if ($idv['types'] == 1 || $idv['types'] == 2) {
                                    $values = 0;
                                } else {
                                    $values = bcmul($idv['worth'], $v['profit'] / 100, 2);
                                }
                            }
                            $values = (int)$values;


                            $add['f_id'] = $idv['id'];
                            $add['now_worth'] = $idv['worth'] + $values;
                            $add['front_worth'] = $idv['worth'];
                            $add['num'] = $values;
                            $add['types'] = 6;
                            $add['create_time'] = time();

                            $is_fi = DB::table('fish_increment')->insert($add);//增值记录

                            if (!$is_fi) {
                                Db::rollback();
                                continue;
                            }
                            if ($values > 0) {

                                $is_save = Db::table('fish')->where('id', $idv['id'])->setInc('worth', $values);
                                if (!$is_save) {
                                    Db::rollback();
                                    $this->output->writeln('修改失败<br>');
                                    continue;
                                }
                            }

                            $is_save = Db::table('fish')
                                ->where('id', $idv['id'])
                                ->update($update);

                            if (!$is_save) {
                                Db::rollback();
                                $this->output->writeln('修改失败<br>');
                                continue;
                            }


                            $service = new \app\common\service\Fish\Service();
                            $stime = get_fishstime($idv['id']);
                            $stime = strtotime(date('H:i:s', $stime));
                            $get_time = $service->add_feed_time(1, $idv['id'], $stime, 3);

                            if (!$get_time) {
                                $this->output->writeln('返池记录失败<br>');
                                Db::rollback();
                                continue;
                            } else {
                                $this->output->writeln($v['name']);
                                $this->output->writeln('完成更新<br>');
                            }
                            Db::commit();
                        } catch (\Exception $e) {

                            Db::rollback();
                            $this->output->writeln('报错<br>');
                            continue;
                        }

                    }


                    $this->output->writeln('完成<br>');
                    continue;


                }

                $this->output->writeln('未到执行时间<br>');

            }

        }
    }


    /**
     * 群发验证码
     * @return string
     */
    public function deliver_fish_sns()
    {

        $PublicModel = new  PublicModel;

        $limit = 99;

        $map['fo.bu_id'] = ['>', 0];
        $map['fo.is_send'] = 0;
        $map['fo.types'] = ['>', 0];
        $map['fo.over_time'] = ['>', 0];


        //获取以及提交凭证没有提交申诉的用户
        $msglist = Db::table('fish_order')
            ->alias('fo')
            ->join('user u', 'u.id = fo.bu_id', 'INNER')
            ->join('fish f', 'f.id = fo.f_id')
            ->join('user fu', 'fu.id = f.u_id', 'INNER')
            ->join('user_invite_code uic', 'uic.user_id = u.id', 'INNER')
            ->join('bathing_pool bp', 'bp.id = f.pool_id')
            ->where($map)
            ->field('u.mobile,fu.mobile fmobile,fo.id,bp.name,bp.id bpid,fo.over_time,uic.invite_code user_code')
            ->order('f.pool_id')
            ->paginate($limit)
            ->toArray();

        $msglist = $msglist['data'];
        $list = array();
        if ($msglist) {
            foreach ($msglist as $k => $v) {

                // 向所有客户端推送抢购信息
                $push_data = ['恭喜玩家ID: '.$v['user_code'].' 成功抢购'.$v['name'].'一套！'];
                GateWay::sendToAll(json_encode($push_data));

                if (empty($list[$v['bpid'] . $v['over_time']])) {

                    $list[$v['bpid'] . $v['over_time']]['mobile'] = $v['mobile'];
                    $list[$v['bpid'] . $v['over_time']]['id'] = $v['id'];
                    $list[$v['bpid'] . $v['over_time']]['fmobile'] = $v['fmobile'];

                } else {
                    $list[$v['bpid'] . $v['over_time']]['mobile'] .= ',' . $v['mobile'];
                    $list[$v['bpid'] . $v['over_time']]['id'] .= ',' . $v['id'];
                    $list[$v['bpid'] . $v['over_time']]['fmobile'] .= ',' . $v['fmobile'];

                }
                $list[$v['bpid'] . $v['over_time']]['name'] = $v['name'] . ',' . date('Y年m月d日H时i分', $v['over_time']);
            }
        }
        $this->output->writeln('list数据：' . json_encode($list));
        // $send_sms = new SendSms();
        // $send_sms->set_type(2);

        foreach ($list as $k => $v) {

            // $send_sms->set_mobile($v['mobile']);
            // $send_sms->set_name($v['name']);
            // $send_sms->send();

            // 推送至Redis队列中的数据(数组格式)
            $push_data = ['order_id' => $v['id'], 'name' => $v['name'], 'mobile' => $v['mobile']];
            // 推送数据
            $push_result = $this->pushMsgSmsQueue($push_data);

            // usleep('100');

            // 推送队列成功则更新状态
            if ($push_result !== false) {
                $ids = $v['id'];
                $s_send['is_send'] = 1;
                $all_save = Db::table('fish_order')
                    ->alias('fo')
                    ->where('fo.id','in',$ids)
                    ->update($s_send);
                if($all_save){
                    $this->output->writeln('发送成功<br>');
                }else{
                    $this->output->writeln('发送失败<br>');

                }
            }
            continue;
        }
        if (empty($msglist)) {
            return '无可更新数组';
        }


    }

    // 短信测试方法
    public function fish_order_test_sms()
    {
        // 推送至队列中的数据(数组格式)
        $push_data = ['order_id' => 1, 'name' => 'Thomas,' . date('Y年m月d日H时i分', time()), 'mobile' => '13728656748'];
        // 推送数据
        $this->pushMsgSmsQueue($push_data);
    }

    /**
     * 生产者推送消息至redis队列
     */
    public function pushMsgSmsQueue($push_data)
    {

        // 1.当前任务将由哪个类来负责处理。
        //   当轮到该任务时，系统将生成一个该类的实例，并调用其 fire 方法
        $jobHandlerClassName = 'app\common\service\Queue\FishOrderSms';

        // 2.当前任务归属的队列名称，如果为新队列，会自动创建
        $jobQueueName = "fishOrderSmsQueue";

        // 3.当前任务所需的业务数据 . 不能为 resource 类型，其他类型最终将转化为json形式的字符串
        //   ( $push_data 为对象时，存储其public属性的键值对 )

        // 4.将该任务推送到消息队列，等待对应的消费者去执行
        $isPushed = Queue::push($jobHandlerClassName, $push_data, $jobQueueName);

        // database 驱动时，返回值为 1|false  ;   redis 驱动时，返回值为 随机字符串|false
        if ($isPushed !== false) {
            // echo date('Y-m-d H:i:s') . " a new Hello Job is Pushed to the MQ"."<br>";
            $this->output->writeln('[ ' . date('Y-m-d H:i:s', time()) . ' ] 消息推送至fishOrderSmsQueue队列成功: ' . json_encode($push_data));
            Log::info('【抢购订单推送通知】：[ ' . date('Y-m-d H:i:s', time()) . ' ] 消息推送至fishOrderSmsQueue队列成功: ' . json_encode($push_data));
            return true;
        } else {
            // echo 'Oops, something went wrong.';
            $this->output->writeln('[ ' . date('Y-m-d H:i:s', time()) . ' ] 消息推送至fishOrderSmsQueue队列失败: ' . json_encode($push_data));
            Log::info('【抢购订单推送通知】：[ ' . date('Y-m-d H:i:s', time()) . ' ] 消息推送至fishOrderSmsQueue队列失败: ' . json_encode($push_data));
            return false;
        }
    }

    // /**
    //  * 群发验证码
    //  * @return string
    //  */
    // public function deliver_fish_sns(){

    //     $PublicModel = new  PublicModel;

    //     $limit = 99;

    //     $map['fo.bu_id'] = ['>',0];
    //     $map['fo.is_send'] = 0;
    //     $map['fo.types'] = ['>',0];
    //     $map['fo.over_time'] = ['>',0];


    //     //获取以及提交凭证没有提交申诉的用户
    //     $msglist =  Db::table('fish_order')
    //         ->alias('fo')
    //         ->join('user u','u.id = fo.bu_id','INNER')
    //         ->join('fish f','f.id = fo.f_id')
    //         ->join('user fu','fu.id = f.u_id','INNER')
    //         ->join('bathing_pool bp','bp.id = f.pool_id')
    //         ->where($map)
    //         ->field('u.mobile,fu.mobile fmobile,fo.id,bp.name,bp.id bpid,fo.over_time')
    //         ->order('f.pool_id')
    //         ->paginate($limit)
    //         ->toArray();

    //     $msglist = $msglist['data'];
    //     $list  = array();
    //     if($msglist){
    //         foreach ($msglist as $k => $v){
    //             if(empty( $list[$v['bpid'].$v['over_time']])){

    //                 $list[$v['bpid'].$v['over_time']]['mobile'] = $v['mobile'];
    //                 $list[$v['bpid'].$v['over_time']]['id'] = $v['id'];
    //                 $list[$v['bpid'].$v['over_time']]['fmobile'] = $v['fmobile'];

    //             }else{
    //                 $list[$v['bpid'].$v['over_time']]['mobile'] .=','. $v['mobile'];
    //                 $list[$v['bpid'].$v['over_time']]['id'] .=','. $v['id'];
    //                 $list[$v['bpid'].$v['over_time']]['fmobile'] .=','. $v['fmobile'];

    //             }
    //             $list[$v['bpid'].$v['over_time']]['name'] = $v['name'].','.date('Y年m月d日H时i分',$v['over_time']);
    //         }
    //     }
    //     $this->output->writeln('list数据：'.json_encode($list));
    //     $send_sms = new SendSms();
    //     $send_sms->set_type(2);

    //     foreach($list as $k => $v){

    //         $ids = $v['id'];

    //         $send_sms->set_mobile($v['mobile']);
    //         $send_sms->set_name($v['name']);
    //         $send_sms->send();

    //         usleep('100');

    //         $s_send['is_send'] = 1;
    //         $all_save = Db::table('fish_order')
    //             ->alias('fo')
    //             ->where('fo.id','in',$ids)
    //             ->update($s_send);
    //         if($all_save){
    //             $this->output->writeln('发送成功<br>');
    //         }else{
    //             $this->output->writeln('发送失败<br>');

    //         }
    //     }
    //     if(empty($msglist)){
    //         return '无可更新数组';
    //     }


    // }


    /**
     * 到时间 未付款（封号）
     * @return string
     */
    public function over_time_no_voucher()
    {


        $limit = 1000;

        $time = time();

        $map['au.status'] = 2;//分配到酒
        $map['au.card_id'] = 0;//未上传支付凭证
        $map['fo.over_time'] = ['<', $time];//超过操作时间的
        $map['fo.status'] = 0;//待支付状态

        $msglist = Db::table('fish_order')
            ->alias('fo')
            ->join('appointment_user au', 'au.id = fo.types', 'INNER')
            ->join('fish f', 'f.id = fo.f_id', 'INNER')
            ->where($map)
            ->field('au.id auid,fo.id,fo.bu_id,fo.f_id,fo.worth')
            ->paginate($limit)
            ->toArray();

        $msglist = $msglist['data'];

        if (empty($msglist)) {
            return '无可更新数组';
        }

        $ids = array_column($msglist, 'id');

        if (empty($ids)) {
            return '数据有误';
        }


        Db::startTrans();
        try {

            $tmptime = $time;
            $arrday[1]['stime'] = $tmptime;             //装修开始时间

            $tmptime = date('Y-m-d H:i:s', $tmptime);
            $tmptime = strtotime("$tmptime +1 day");

            $arrday[1]['ntime'] = $tmptime;//装修结束时间
            $arrday[1]['is_contract'] = 0;
            $json = json_encode($arrday);    //添加重新需要装修的时间

            $save['f.is_re'] = 1;//酒交易失败
            $save['f.is_status'] = 2;//重返喂酒
            $save['f.feed_overtime'] = $tmptime;//结束
            $save['f.status'] = 0;
            $save['f.pre_endtime'] = 0;
            $save['f.key'] = 0;


            $save['au.status'] = -3;//超时
            $save['fo.status'] = -3;//超时


            foreach ($msglist as $lk => $lv) {
                $stime = get_fishstime($lv['id']);
                $stime = strtotime(date('H:i:s', $stime));
                $save['f.worth'] = $lv['worth'];
                $tmpday = date('Y-m-d H:i:s', $stime);
                $save['f.feed_overtime'] = strtotime("$tmpday + 1 day");

                $all_save = Db::table('fish_order')
                    ->alias('fo')
                    ->join('appointment_user au', 'au.id = fo.types', 'INNER')
                    ->join('fish f', 'f.id = fo.f_id', 'INNER')
                    ->where('fo.id', $lv['id'])
                    ->where($map)
                    ->update($save);

                if (!$all_save) {
                    Db::rollback();
                    return '修改状态失败';
                }

                $service = new \app\common\service\Fish\Service();

                $get_time = $service->add_feed_time(1, $lv['id'], $stime, 3);

                if (!$get_time) {
                    return '返池记录失败';
                }

                $is_usersave = Db::table('user')->where('id', $lv['bu_id'])->setInc('forbidden_num', 1);//添加违规次数
                if ($is_usersave) {
                    // 修改状态任务准备封停
                    Db::table('user')->where('id', $lv['bu_id'])->update(['is_title' => 1]);
                } else {
                    Db::rollback();
                    return '修改状态失败';
                }
            }


            Db::commit();
            return '操作成功';


        } catch (\Exception $e) {
            return '出错';
            Db::rollback();

        }

    }


    /**
     * 清空当天标记
     */
    public function empty_key()
    {

        $PublicModel = new  PublicModel;
        $pool = $PublicModel->get_list_pool();


        //是否有开放的酒馆
        if ($pool) {
            //升级
            foreach ($pool as $k => $v) {
                if ($v['key']) {
                    $up['key'] = 0;
                    Db::table('bathing_pool')->where('id', $v['id'])->update($up);
                }


            }


        }

    }


}
