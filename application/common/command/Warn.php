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

/* 警告通知 */

class Warn extends Command
{

    public $output;

    protected function configure()
    {
        $this->setName('warn')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->output = new Output;
        //file_put_contents(dirname(__FILE__).'/output.log',date("Y-m-d H:i:s"));
        $output->writeln("Start Command : ");
        while (true) {
            $this->send_exchange_time_queue();
            $this->send_pay_warn();
            //每十五分钟执行一次循环
            sleep(300);
        };
        //任务结束
        $output->writeln("done.");
    }

    /**
     * 发送闪兑频率警告短信
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function send_exchange_time_queue()
    {
        try {
            $user_list = User::table('user')
                ->alias('u')
                ->join('user_invite_code uic', 'uic.user_id = u.id')->field('u.id,uic.invite_code,u.nick_name')->where(['status' => '1'])->select();
            $time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $end_time = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
            foreach ($user_list AS $user) {
                $my_gc_count = Db::table('my_gc_log')
                    ->where('remark', 'like', '%闪兑%')->where('create_time', '>=', $time)->where('uid', $user['id'])->count('id');
                $my_wallet_count = Db::table('my_wallet_log')
                    ->where('remark', 'like', '%闪兑%')->where('create_time', '>=', $time)->where('uid', $user['id'])->count('id');
                $number = $my_gc_count + $my_wallet_count;
                if ($number == 0) {
                    continue;
                }
                $val = DB::table('config')->where('key', 'every_exchange_times')->value('value');
                if ($number >= $val) {
                    // 需要发送的手机号列表
                    $result = DB::table('warn_receives')->select();
                    foreach ($result AS $wr) {
                        $warn_send_count = DB::table('warn_sends')->where('uid', $user['id'])->where('send_time', '>=', $time)->where('send_time', '<=', $end_time)->count('1');
                        // 保证当天数据只发过一次短信
                        if ($warn_send_count == 0) {
                            Log::info('【闪兑警告消息推送】：[ ' . date('Y-m-d H:i:s', time()) . ' ] 用户昵称: ' . $user['nick_name'] . "准备发送短信通知");
                            $my_gc_count_total = Db::table('my_gc_log')
                                ->where('remark', 'like', '%闪兑%')->where('create_time', '>=', $time)->where('uid', $user['id'])->sum('amount');
                            $my_wallet_count_total = Db::table('my_wallet_log')
                                ->where('remark', 'like', '%闪兑%')->where('create_time', '>=', $time)->where('uid', $user['id'])->sum('number');
                            $total = $my_gc_count_total + $my_wallet_count_total;
                            $send_sms = new SendSms();
                            $send_sms->sendExchangeTimesWarnSms($user['invite_code'], $user['nick_name'], date("Y-m-d H:i", time()), $number, $total, $wr['mobile']);
                            // 当天已发送的记录添加
                            $add['uid'] = $user['id'];
                            $add['send_time'] = time();
                            if (!Db::table('warn_sends')->insert($add)) {
                                Db::rollback();
                                return json(['code' => 1, 'msg' => '操作失败!']);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Db::rollback();
            Log::info('【发送闪兑警告日志:】：[ ' . date('Y-m-d H:i:s', time()) . ' ] 异常：' . $e->getMessage());
        }

    }

    /**
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function send_pay_warn()
    {
        $map['au.status'] = 2;//直接交易
        $map['fo.status'] = 0;//上传支付凭
        $map['f.is_show'] = 1;
        $map['f.is_delete'] = 0;
        $msglist = Db::table('appointment_user')
            ->alias('au')
            ->join('fish_order fo', 'au.id = fo.types', 'INNER')
            ->join('fish f', 'f.order_id = fo.id', 'INNER')
            ->join('user u', 'u.id = fo.bu_id', 'INNER')
            ->join('bathing_pool bp', 'bp.id = f.pool_id', 'INNER')
            ->where($map)
            ->field('fo.over_time,u.mobile,bp.name,fo.id,u.id as uid')
            ->select();

        // 找不到有未支付的订单
        if (empty($msglist)) {
            return false;
        }

        // 遍历判断时间是否到达限度
        foreach ($msglist AS $v) {
            $time = time();
            if ($v['over_time'] - $time <= 1800) {
                // 判断这个订单在之前有没有给用户发过警告
                $warn_send_count = DB::table('warn_sends')->where('uid', $v['uid'])->where('type', $v['id'])->count('1');
                //没发过的话就发一次 否则就不发
                if ($warn_send_count == 0) {
                    Log::info('【订单支付未付款警告消息】：[ ' . date('Y-m-d H:i:s', time()) . ' ] 用户手机号: ' . $v['mobile'] . "准备发送，房屋名称为：" . $v['name']);
                    $send_sms = new SendSms();
                    $send_sms->sendPayOrderTimeWarnSms($v['name'], 30, $v['mobile']);
                    // 当天已发送的记录添加
                    $add['uid'] = $v['uid'];
                    $add['send_time'] = $time;
                    $add['type'] = $v['id'];
                    if (!Db::table('warn_sends')->insert($add)) {
                        Db::rollback();
                        continue;
                    }
                }
            }
        }
    }

}