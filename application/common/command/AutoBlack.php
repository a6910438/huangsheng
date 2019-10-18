<?php

namespace app\common\command;

use app\admin\exception\AdminException;
use app\common\entity\TitleLog;
use think\console\Command;
use think\console\Input;
use think\console\Output;

//use think\Cookie;
use think\Db;

use app\common\entity\User;
use app\common\entity\Config;
use app\index\model\Publics as PublicModel;
/* 短信通知 */

use app\common\model\SendSms;

class AutoBlack extends Command
{
    public $public_test = 0;

    public $output;

    protected function configure()
    {
        $this->setName('autoBlack')->setDescription('User Unblocking and Blackening Program ');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->output = new Output;
        $output->writeln("Start Command : ");
        while (true) {
            $h = date('H');
            $m = date('i');
            $hm = $h . $m;
            $this->pull_black();
            $this->black();
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
     * 自动解封封禁的账号
     * @return string
     */
    public function pull_black()
    {
        $this->output->writeln('=>开始用户解封进程');
        $limit = 1000;

        $time = time();


        // $map['forbidden_num'] = ['in',[1,2]];  // 只解封第一和第二次封号的用户

        $msglist = Db::table('user')
            ->alias('fo')
            ->where('forbidden_num', 'in', [1, 2]) // 只解封第一和第二次封号的用户
            ->where('forbidden_ntime', 'not in', [0])
            ->field('forbidden_ntime,forbidden_num,id,is_active,forbidden_stime')
            ->paginate($limit)
            ->toArray();


        $msglist = $msglist['data'];

        $this->output->writeln('=>待解封数据: ' . json_encode($msglist) . ',待解封条数：' . count($msglist));
        if (empty($msglist)) {
            $this->output->writeln('=>暂无待解封数据');
            $this->output->writeln('=>结束用户解封进程');
            return '暂无待解封数据';
        }


        // $tmptime = $time;
        // $tmptime =   date('Y-m-d H:i:s',$tmptime);
        // $oneday = strtotime("$tmptime +1 day");
        // $alltime = strtotime("$tmptime +100 year");


        foreach ($msglist as $lk => $lv) {
            Db::startTrans();
            $save = [];
            try {
                $this->output->writeln($lv['forbidden_ntime']);
                // 解封操作
                if ($time > $lv['forbidden_ntime'] && $lv['forbidden_ntime'] > 0) {


                    $save_log['uid'] = $lv['id'];
                    $save_log['reason'] = "系统解封";
                    $save_log['stime'] = $lv['forbidden_stime'];
                    $save_log['create_time'] = $time;
                    $save_log['ntime'] = $time;
                    $save_log['type'] = 1;


                    // $this->output->writeln('=>进入数据: '.json_encode($lv));
                    $save['forbidden_num'] = 0;
                    $save['forbidden_stime'] = 0;
                    $save['forbidden_ntime'] = 0;
                    if ($lv['is_active']) {
                        $save['status'] = 1;
                    } else {
                        $save['status'] = 0;
                    }
                    $save['forbidden_type'] = 0;
                    $save['is_prohibitteam'] = 0;
                    $save['is_prohibit_extension'] = 0;
                    $save['usertoken'] = 0;
                    $save['is_title'] = 0;
                    $one_save = Db::table('user')
                        ->where('id', $lv['id'])
                        ->update($save);

                    if (!$one_save) {
                        $this->output->writeln('=>用户ID：' . $lv['id'] . ',解封失败');
                        Db::rollback();
                        continue;

                    }

                    $inslog = TitleLog::insert($save_log);
                    if (!$inslog) {
                        $this->output->writeln('=>用户ID：' . $lv['id'] . ',解封失败');
                        Db::rollback();
                        continue;
                    }

                    $this->output->writeln('=>用户ID：' . $lv['id'] . ',解封成功');

                    Db::commit();
                    continue;
                } else {
                    $this->output->writeln('=>捕获待解封数据: ' . json_encode($lv));
                    Db::commit();
                    continue;
                }
                // //解封
                // if($time > $lv['forbidden_ntime'] && $lv['forbidden_ntime'] > 0){
                //     $save['forbidden_num'] = 0;
                //     $save['forbidden_ntime'] = 0;
                //     if($lv['is_active']){
                //         $save['status'] = 1;
                //     }else{
                //         $save['status'] = 0;
                //     }
                //     $save['forbidden_type'] = 0;

                // }elseif ($lv['forbidden_num'] == 1){
                //     $save['forbidden_ntime'] = $oneday;
                //     $save['status'] = -1;
                //     $save['forbidden_type'] = 1;
                //     $save['is_prohibitteam'] = 1;
                //     $save['is_prohibit_extension'] = 1;
                //     $save['usertoken'] = 0;
                //     $save['forbidden_stime'] = time();

                // }else{
                //     $save['forbidden_ntime'] = $alltime;
                //     $save['status'] = -1;
                //     $save['forbidden_type'] = 2;
                //     $save['is_prohibitteam'] = 1;
                //     $save['is_prohibit_extension'] = 1;
                //     $save['usertoken'] = 0;
                //     $save['forbidden_stime'] = time();


                // }


            } catch (\Exception $e) {
                $this->output->writeln('=>解封程序发生异常: ' . $e->getMessage());
                Db::rollback();
                continue;
            }
        }

        $this->output->writeln('=>结束用户解封进程');

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

    /**
     * 拉黑与解封
     * @return string
     */
    public function black()
    {


        $limit = 1000;

        $time = time();


        $map['is_title'] = ['>', 0];

        $msglist = Db::table('user')
            ->alias('fo')
            ->where($map)
            ->field('forbidden_ntime,forbidden_num,id,is_active,is_title')
            ->paginate($limit)
            ->toArray();


        $msglist = $msglist['data'];


        if (empty($msglist)) {
            return '无可更新数组';
        }


        foreach ($msglist as $lk => $lv) {
            Db::startTrans();
            try {
                //封号
                if ($lv['is_title'] == 1) {
                    if ($lv['forbidden_num'] == 1) {
                        $first_title_time = Config::getValue('first_title_time');
                        $save['forbidden_ntime'] = strtotime("+" . $first_title_time . " day");
                    } else if ($lv['forbidden_num'] == 2) {
                        $two_title_time = Config::getValue('two_title_time');
                        $save['forbidden_ntime'] = strtotime("+" . $two_title_time . " day");
                    } else if ($lv['forbidden_num'] == 3 || $lv['forbidden_num'] > 3) {
                        $three_title_time = Config::getValue('three_title_time');
                        $save['forbidden_ntime'] = strtotime("+" . $three_title_time . " day");
                    }
                    $save['status'] = -1;
                    $save['forbidden_type'] = 1;
                    $save['is_prohibitteam'] = 1;
                    $save['is_prohibit_extension'] = 1;
                    $save['usertoken'] = 0;
                    $save['forbidden_stime'] = $time;
                    $save['is_title'] = 0;


                    $one_save = Db::table('user')
                        ->where('id', $lv['id'])
                        ->update($save);


                    if (!$one_save) {
                        Db::rollback();
                        continue;
                    }

                    $save_log['uid'] = $lv['id'];
                    $save_log['reason'] = "支付超时，系统封号";
                    $save_log['stime'] = $time;
                    $save_log['create_time'] = $time;
                    $save_log['ntime'] = $save['forbidden_ntime'];
                    $save_log['source'] = "系统操作";
                    $save_log['type'] = 0;

                    if (!Db::table('title_log')->insert($save_log)) {
                        Db::rollback();
                        continue;
                    }


                    Db::commit();
                    continue;
                }



            } catch (\Exception $e) {

                Db::rollback();
                continue;

            }
        }


    }


}