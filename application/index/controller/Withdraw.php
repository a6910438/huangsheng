<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/14
 * Time: 16:41
 */

namespace app\index\controller;

use app\common\entity\Config;
use app\common\entity\User;
use app\common\entity\MyGcLog;
use app\common\entity\GcWithdrawLog;
use app\common\model\SendSms;
use think\Db;

class Withdraw extends Base
{

    /**
     * 提币手续费率
     */
    public function get_commission_rate()
    {
        $data = Config::getValue('commission_rate');
        return ['code' => 666, 'data' => $data];
    }

    /**
     * 提交
     */
    public function submit()
    {
        //是否有兑换数据
        if (!input('?post.wallet_address') || !input('?post.amount') || !input('?post.pay_pwd')) {
            return ['code' => 1, 'msg' => '参数不足'];
        }
        //判断钱包地址格式是否正确
        $wallet_address = input('post.wallet_address');
        if (!preg_match("/^0x[a-zA-Z0-9]{40}$/", $wallet_address)) {
            return ['code' => 1, 'msg' => "钱包地址格式不正确"];
        }
        $amount = input('post.amount');
        $pay_pwd = input('post.pay_pwd');
        //判断兑换数据是否合法
        if (!is_numeric($amount) || $amount <= 0) {
            return ['code' => 1, 'msg' => '参数错误'];
        };
        //获取用户数据
        $user = User::where('id', $this->userId)->find();
        //验证支付密码
        $service = new \app\common\service\Users\Service();
        $result = $service->checkPayPassword($pay_pwd, $user);
        if (!$result) {
            return ['code' => 1, 'msg' => '支付密码错误!'];
        }
        //获取汇率，并且判断是否合法
        $rate = Config::getValue('commission_rate');
        if (!is_numeric($rate) || $rate < 0) {
            return ['code' => 1, 'msg' => '手续费出错，请联系管理员'];
        };
        //计算手续费
        $commission = round($amount * $rate / 100, 8);
        $total_amount = $amount + $commission;

        if ($user['gc'] < $total_amount) {
            return ['code' => 1, 'msg' => 'GC余额不足'];
        }

        $time = time();

        Db::startTrans();

        try {
            //扣除GC
            if (!User::where('id', $user['id'])->setDec('gc', $total_amount)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '扣除GC失败!'];
            }
            //添加GC流水日志
            $new_gc_log = [
                'uid' => $user['id'],
                'amount' => $total_amount,
                'type' => 1,
                'remark' => '提币扣除',
                'create_time' => $time
            ];
            if (!MyGcLog::insert($new_gc_log)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '提交流水日志失败!'];
            };

            //添加提币记录
            $new_gc_withdraw_log = [
                'uid' => $user['id'],
                'wallet_address' => $wallet_address,
                'total_amount' => $total_amount,
                'amount' => $amount,
                'commission' => $commission,
                'status' => 0,
                'create_time' => $time,
                'done_time' => 0,
            ];
            if (!GcWithdrawLog::insert($new_gc_withdraw_log)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '添加提币记录失败!'];
            };
            //成功返回
            Db::commit();
            $result = DB::table('warn_receives')->where('type', '2')->select();
            foreach ($result AS $wr) {
                $send_sms = new SendSms();
                $send_sms->sendWithdrawSms($user['nick_name'], $amount, date( "Y-m-d H:i:s", $time), $wr['mobile']);
            }
            return ['code' => 666, 'msg' => '提币记录提交成功，请等待管理员处理'];
        } catch (\Throwable $th) {
            //throw $th;
            Db::rollback();
            return ['code' => 1, 'msg' => '程序报错!', 'th' => $th];
        }

    }

    /**
     * 提币记录列表
     * @return \think\response\Json
     */
    public function list()
    {

        $uid = $this->userId;
        $page = input('post.page') ? input('post.page') : 1;
        $type = input('post.type') ? input('post.type') : 0;
        $limit = input('post.limit') ? input('post.limit') : 5;
        if ($type == 1) {//成功
            $map['status'] = 1;
        } elseif ($type == 2) {//失败
            $map['status'] = 2;
        } else {
            $map['status'] = 0;
        }

        $map['uid'] = $uid;
        $info = GcWithdrawLog::field('id,uid,wallet_address,total_amount,amount,commission,create_time,status,done_time')
            ->where($map)
            ->order('id desc')
            ->page($page)
            ->paginate($limit)
            ->toArray();
        //预处理
        foreach ($info['data'] as $k => $v) {
            //$info['data'][$k]['create_time'] = date('Y-m-d H:i:s',$v['create_time']);
            //$info['data'][$k]['done_time'] = date('Y-m-d H:i:s',$v['done_time']);
            //$info['data'][$k]['done_time'] = date('Y-m-d H:i:s',$v['done_time']);
            if ($v['status'] == 0) {
                $info['data'][$k]['status_des'] = '待处理';
            } elseif ($v['status'] == 1) {
                $info['data'][$k]['status_des'] = '成功';
            } else {
                $info['data'][$k]['status_des'] = '失败';
            }
        }
        //$info = $list;
        return ['code' => 0, 'msg' => 'access!', 'info' => $info];

    }

}