<?php

namespace app\common\command;

use app\common\model\SendSms;
use think\console\Command;
use think\console\Input;
use think\console\Output;

//use think\Controller;
//use think\Cookie;
use think\Db;
use app\common\model\GC;
use app\common\entity\User;
//use think\Request;
use app\common\entity\Config;
use think\Log;

/* 报表更新 */

class Report extends Command
{

    public $output;

    protected function configure()
    {
        $this->setName('report')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->output = new Output;
        //file_put_contents(dirname(__FILE__).'/output.log',date("Y-m-d H:i:s"));
        $output->writeln("Start Command : ");
        while (true) {
            $this->create_or_update_teamleader_list();
            //每十五分钟执行一次循环
            sleep(300);
        };
        //任务结束
        $output->writeln("done.");
    }

    /**
     * 创建更新团队长列表
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function create_or_update_teamleader_list()
    {
        try {
            $entity = User::table('user')
                ->alias('u')
                ->join('user_invite_code uic', 'uic.user_id = u.id')
                ->where('u.lv', '>', 0)
                ->where('u.lv', '<', 4)
                ->where('u.status', 1)
                ->where('u.is_active', 1)
                ->field('u.*,uic.invite_code,mw.old,mw.now,mw.old');

            $list = $entity
                ->leftJoin('my_wallet mw', 'mw.uid = u.id')
                ->select();
            $this->output->writeln(count($list));

            foreach ($list as $v) {
                $t['pname'] = '';
                if ($v['pid']) {
                    $leader = User::alias('u')
                        ->join('user_invite_code uic', 'uic.user_id = u.id')
                        ->where('u.id', $v['pid'])->value('uic.invite_code');
                    $t['pname'] = $leader;
                }
                $this->output->writeln($t['pname']);

                $child = (new User())->get_child($v['id']);
//            $t_num =(new User())->getTeamZTNum($child); //团队人数
                $allID = array(0 => $v['id']);
                (new User())->getTeamUserIdn($v['id'], $allID);

                $my_bait = \think\Db::table('my_wallet_log')
                        ->where('uid', $v['id'])
                        ->where('types', 'in', '2,3,6,4')
                        ->sum('number') ?? 0;
                $my_bait2 = \think\Db::table('my_wallet_log')
                        ->where('uid', $v['id'])
                        ->where('types', 'in', '1,5')
                        ->where(['from_id' => ['not in', $allID]])
                        ->where('number', '<', 0)
                        ->sum('number') ?? 0;
                $reducep = abs($my_bait) + abs($my_bait2);

                (new User())->genTeamReduceWallet($v['id'], $reducep, $allID);

                $t_num = 1;
                (new User())->getTeamZTNum($v['id'], $t_num); //团队人数
                $this->output->writeln($t_num);

                $adopt_num = Db::table('appointment_user')
                    ->where('uid', $v['id'])
                    ->where('new_fid', '>', 0)
                    ->where('status', 4)
                    ->count('id');
                (new User())->getTeamAdoptFishNum($v['id'], $adopt_num);////领取酒数
                $this->output->writeln('$adopt_num:'.$adopt_num);
//
//            $pre_num = Db::table('appointment_user')
//						->where('uid',$v['id'])
//						->count('id');
//            (new User())->getTeamPreNum($v['id'],$pre_num);//预约酒数

                $now_num = $v['now'];
                (new User())->genTeamNowWallet($v['id'], $now_num);//GTC数
                $this->output->writeln('$now_num:'.$now_num);


                $addp = Db::table('my_wallet_log')
                    ->where(['uid' => $v['id'], 'number' => ['>', 0]])
                    ->where('types', 'in', '1,5')
                    ->where(['from_id' => ['not in', $allID]])
                    ->sum('number');

                (new User())->genTeamAddWallet($v['id'], $addp, $allID);//团队GTC充值
                $this->output->writeln('$addp:'.$addp);

                $total_pro = User::where(['id' => ['in', $allID]])->sum('profit');
                $total_prohibit = User::where(['id' => ['in', $allID]])->sum('now_prohibit_integral');
                $total_team = User::where(['id' => ['in', $allID]])->sum('now_team_integral');

                //$addp = $now_num - $reducep;
                $t['t_num'] = $t_num ? $t_num : 0;
                $t['adopt_num'] = $adopt_num ? $adopt_num : 0;//领取酒数
//            $v['pre_num'] = $pre_num?$pre_num:0;//预约酒数
                $t['now_num'] = $now_num ? $now_num : 0;//GTC数
                $t['addp_num'] = $addp ? $addp : 0;//添加GTC数
                $t['reducep_num'] = $reducep ? $reducep : 0;//消耗GTC数
                $t['total_pro'] = $total_pro ? $total_pro : 0;//装修收益
                $t['total_prohibit'] = $total_prohibit ? $total_prohibit : 0;//团队推广收益
                $t['total_team'] = $total_team ? $total_team : 0;//团队收益
                $t['id'] = $v['id'];
                $t['is_prohibit_extension'] = $v['is_prohibit_extension'];
                $t['is_prohibitteam'] = $v['is_prohibitteam'];
                $t['nick_name'] = $v['nick_name'];
                $t['invite_code'] = $v['invite_code'];
                $t['lv'] = $v['lv'];
                $t['status'] = $v['status'];

                $isT = Db::table('team_report')->where('id', $v['id'])->count();
                $this->output->writeln('是否该插入这条数据' . $isT . '！用户ID' . $t['id']);
                if ($isT) {
                    Db::table('team_report')->where('id', $v['id'])->update($t);
                } else {
                    Db::table('team_report')->insert($t);
                }
            }

        } catch (\Exception $e) {
            Db::rollback();
            Log::info('【创建团队长列表异常:】：[ ' . date('Y-m-d H:i:s', time()) . ' ] 异常：' . $e->getMessage());
        }

    }

}