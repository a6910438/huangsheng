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
use think\Db;

class Exchange extends Base
{

    /**
     * 获取GC闪兑汇率
     */
    public function get_rate()
    {
        // $data = Config::getValue('exchange_rate');
        // return ['code' => 666, 'data' => $data];
        $data = [];
        $data['rate'] = Config::getValue('exchange_rate');
        $data['fall'] = Config::getValue('exchange_fall');
        // 获取用户可用gtc余额
        $data['gtc_num'] = (new User)->getNowWallet($this->userId);
        // 获取用户的gc余额
        $data['gc_num'] = User::where('id', $this->userId)->value('gc');
        return json(['code' => 666, 'data' => $data]);
    }

    /**
     * GC兑GTC
     */
    public function gc_to_gtc()
    {
        //是否有兑换数据
        if (!input('?post.eth')) {
            return ['code' => 1, 'msg' => '参数不足'];
        }
        $eth = input('post.eth');
        //判断兑换数据是否合法
        if (!is_numeric($eth) || $eth <= 0) {
            return ['code' => 1, 'msg' => '参数错误'];
        };
        // // 判断单次闪兑的额度是否超过限额
        // $exchange_limit_num = Config::getValue('exchange_limit_num') ?: 3000;
        // if ($exchange_limit_num < $eth) {
        //     return ['code' => 1, 'msg' => '闪兑失败,单次闪兑不能超过'.$exchange_limit_num];
        // }
        //获取汇率，并且判断是否合法
        $rate = Config::getValue('exchange_rate');
        if (!is_numeric($rate) || $rate <= 0) {
            return ['code' => 1, 'msg' => '汇率出错，请联系管理员'];
        };
        //计算兑换后的GTC数量
        $dai = round($eth * $rate, 2);
        if ($dai <= 0) {
            return ['code' => 1, 'msg' => '汇率出错，兑换后的数量少于0'];
        };
        //获取用户数据
        $user = User::alias('u')
            ->join('my_wallet mw', 'u.id = mw.uid', 'LEFT')
            ->field(['u.id', 'u.gc', 'mw.now gtc', 'mw.old gtc_old'])
            ->where('u.id', $this->userId)
            ->find();

        if (empty($user)) {
            return ['code' => 1, 'msg' => '用户不存在'];
        }
        //判断GC余额是否充足
        if ($user['gc'] < $eth) {
            return ['code' => 1, 'msg' => 'GC余额不足'];
        }

//        $val = DB::table('config')->where('key', 'exchange_times_warn')->value('value');
//        if ($val > 0) {
//            $time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
//            $my_gc_count = Db::table('my_gc_log')
//                ->where('remark', 'like', '%闪兑%')->where('create_time', '>=', $time)->where('uid', $user['id'])->count('id');
//            $my_wallet_count = Db::table('my_wallet_log')
//                ->where('remark', 'like', '%闪兑%')->where('create_time', '>=', $time)->where('uid', $user['id'])->count('id');
//            $number = $my_gc_count + $my_wallet_count;
//            if ($number > $val) {
//                return ['code' => 1, 'msg' => '请勿频繁操作此功能！'];
//            }
//        }
        //当前时间
        $time = time();
        //开始兑换
        //创建数据库事务
        Db::startTrans();
        try {
            //扣除GC
            if (!User::where('id', $user['id'])->setDec('gc', $eth)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '操作失败1!'];
            }

            //增加GTC

            $my_wallet = Db::table('my_wallet')
                ->field(['now', 'old'])
                ->where('uid', $user['id'])
                ->find();
            if (empty($my_wallet)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '操作失败2!'];
            }
            $new_my_wallet = [
                'now' => $my_wallet['now'] + $dai,
                'old' => $my_wallet['old'] + $dai
            ];

            if (!Db::table('my_wallet')->where('uid', $user['id'])->update($new_my_wallet)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '操作失败!'];
            }

            //兑换后如果GTC余额大于充值激活最低金额则激活用户
            $activation_num = Config::getValue('activation_num'); //最低激活余额
            $u_num = Db::table('my_wallet')->where('uid', $user['id'])->field('old')->find(); //历史充值金额
            $b_uactive = User::where("id", $user['id'])->value('is_active'); //激活状态
            if ($u_num && $b_uactive == 0 && $u_num['old'] >= $activation_num) {
                $bu_save['is_active'] = 1;
                $bu_save['status'] = 1;
                $bu_save['active_time'] = $time;
                $bu_save['update_time'] = $time;
                $res = User::where('id', $user['id'])->update($bu_save);
            };


            //添加GC流水日志
            $new_gc_log = [
                'uid' => $user['id'],
                'amount' => $eth,
                'type' => 1,
                'remark' => 'GC闪兑GTC',
                'create_time' => $time
            ];

            //添加数据记录
            $new_my_wallet_log['uid'] = $user['id'];
            $new_my_wallet_log['number'] = $dai;
            $new_my_wallet_log['now'] = $my_wallet['now'];
            $new_my_wallet_log['remark'] = 'GC闪兑';
            $new_my_wallet_log['types'] = 8;
            $new_my_wallet_log['create_time'] = $time;
            $new_my_wallet_log['future'] = $new_my_wallet['now'];
            $new_my_wallet_log['from_id'] = 0;
            $new_my_wallet_log['curr_rate'] = $rate;

            if (!MyGcLog::insert($new_gc_log) || !Db::table('my_wallet_log')->insert($new_my_wallet_log)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '提交流水日志失败!'];
            };

            Db::commit();
            return ['code' => 666, 'msg' => '兑换成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 1, 'msg' => '出现异常'];
        }

    }

    /**
     * GTC兑GC
     */
    public function gtc_to_gc()
    {
        //是否有兑换数据
        if (!input('?post.eth')) {
            return ['code' => 1, 'msg' => '参数不足'];
        }
        $eth = input('post.eth');
        //判断兑换数据是否合法
        if (!is_numeric($eth) || $eth <= 0) {
            return ['code' => 1, 'msg' => '参数错误'];
        };
        // 判断单次闪兑的额度是否超过限额
        $exchange_limit_num = Config::getValue('exchange_limit_num') ?: 3000;
        if ($exchange_limit_num < $eth) {
            return ['code' => 1, 'msg' => '闪兑失败,单次闪兑不能超过'.$exchange_limit_num];
        }
        //获取汇率，并且判断是否合法
        $rate = Config::getValue('exchange_rate');
        if (!is_numeric($rate) || $rate <= 0) {
            return ['code' => 1, 'msg' => '汇率出错，请联系管理员'];
        };
        $fall = Config::getValue('exchange_fall');
        $rate = round(1 / ($rate+$fall), 4);
        //计算兑换后的GC数量
        $dai = round($eth * $rate, 2);
        if ($dai <= 0) {
            return ['code' => 1, 'msg' => '汇率出错，兑换后的数量少于0'];
        };
        //获取用户数据
        $user = User::alias('u')
            ->join('my_wallet mw', 'u.id = mw.uid', 'LEFT')
            ->field(['u.id', 'u.gc', 'mw.now gtc'])
            ->where('u.id', $this->userId)
            ->find();

        if (empty($user)) {
            return ['code' => 1, 'msg' => '用户不存在'];
        }
        //判断GC余额是否充足
        if ($user['gtc'] < $eth) {
            return ['code' => 1, 'msg' => 'GTC余额不足'];
        }

        $val = DB::table('config')->where('key', 'every_exchange_times')->value('value');
        if ($val > 0) {
            $time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
            $my_gc_count = Db::table('my_gc_log')
                ->where('remark', 'like', '%闪兑%')->where('create_time', '>=', $time)->where('uid', $user['id'])->count('id');
            $my_wallet_count = Db::table('my_wallet_log')
                ->where('remark', 'like', '%闪兑%')->where('create_time', '>=', $time)->where('uid', $user['id'])->count('id');
            $number = $my_gc_count + $my_wallet_count;
            if ($number > $val) {
                return ['code' => 1, 'msg' => '请勿频繁操作此功能！'];
            }
        }
        //当前时间
        $time = time();
        //开始兑换
        //创建数据库事务
        Db::startTrans();
        try {
            //扣除GTC
            if (!Db::table('my_wallet')->where('uid', $user['id'])->setDec('now', $eth)) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }

            //增加GC
            if (!User::where('id', $user['id'])->setInc('gc', $dai)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '操作失败1!'];
            }

            //添加数据记录

            $new_my_wallet_log['uid'] = $user['id'];
            $new_my_wallet_log['number'] = -$eth;
            $new_my_wallet_log['now'] = $user['gtc'] - $eth;
            $new_my_wallet_log['remark'] = 'GTC兑换GC';
            $new_my_wallet_log['types'] = 8;
            $new_my_wallet_log['create_time'] = $time;
            $new_my_wallet_log['future'] = $eth;
            $new_my_wallet_log['from_id'] = 0;
            $new_my_wallet_log['curr_rate'] = $rate;

            //添加GC流水日志
            $new_gc_log = [
                'uid' => $user['id'],
                'amount' => $dai,
                'type' => 0,
                'remark' => 'GTC闪兑GC',
                'create_time' => $time
            ];
            if (!MyGcLog::insert($new_gc_log) || !Db::table('my_wallet_log')->insert($new_my_wallet_log)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '提交流水日志失败!'];
            };

            Db::commit();
            return ['code' => 666, 'msg' => '兑换成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 1, 'msg' => '出现异常'];
        }

    }

    /**
     * 推广收益兑GTC
     */
    public function invite_profit_to_gtc()
    {
        try {
            //是否有兑换数据
            if (!input('?post.eth')) {
                return ['code' => 1, 'msg' => '参数不足'];
            }
            $eth = input('post.eth');
            //判断兑换数据是否合法
            if (!is_numeric($eth) || $eth <= 0) {
                return ['code' => 1, 'msg' => '参数错误'];
            };
            //获取用户数据
            $user = User::alias('u')
                ->field(['u.id', 'u.now_prohibit_integral'])
                ->where('u.id', $this->userId)
                ->find();

            if (empty($user)) {
                return ['code' => 1, 'msg' => '用户不存在'];
            }
            //判断GC余额是否充足
            if ($user['now_prohibit_integral'] < $eth) {
                return ['code' => 1, 'msg' => '推广收益不足'];
            }
            //当前时间
            $time = time();
            //开始兑换
            //创建数据库事务
            Db::startTrans();

            $now = $user['now_prohibit_integral'] - $eth;

            //添加GC流水日志
            $my_prohibit_log = [
                'uid' => $user['id'],
                'number' => -$eth,
                'type' => 4,
                'createtime' => $time,
                'old' => $user['now_prohibit_integral'],
                'source_id' => $user['id'],
                'new' => $now
            ];
            if (!Db::table('prohibit_log')->insert($my_prohibit_log)) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }


            //扣除团队收益
            if (!User::where('id', $user['id'])->setDec('now_prohibit_integral', $eth)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '操作失败1!'];
            }

            //增加GTC

            $my_wallet = Db::table('my_wallet')
                ->field(['now', 'old'])
                ->where('uid', $user['id'])
                ->find();
            if (empty($my_wallet)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '操作失败2!'];
            }
            $new_my_wallet = [
                'now' => $my_wallet['now'] + $eth,
                'old' => $my_wallet['old'] + $eth
            ];

            if (!Db::table('my_wallet')->where('uid', $user['id'])->update($new_my_wallet)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '操作失败!'];
            }

            //兑换后如果GTC余额大于充值激活最低金额则激活用户
            $activation_num = Config::getValue('activation_num'); //最低激活余额
            $u_num = Db::table('my_wallet')->where('uid', $user['id'])->field('old')->find(); //历史充值金额
            $b_uactive = User::where("id", $user['id'])->value('is_active'); //激活状态
            if ($u_num && $b_uactive == 0 && $u_num['old'] >= $activation_num) {
                $bu_save['is_active'] = 1;
                $bu_save['status'] = 1;
                $bu_save['active_time'] = $time;
                $bu_save['update_time'] = $time;
                $res = User::where('id', $msglist['u_id'])->update($bu_save);
            };
            //添加数据记录
            $new_my_wallet_log['uid'] = $user['id'];
            $new_my_wallet_log['number'] = $eth;
            $new_my_wallet_log['now'] = $my_wallet['now'];
            $new_my_wallet_log['remark'] = '收益兑换';
            $new_my_wallet_log['types'] = 10;
            $new_my_wallet_log['create_time'] = $time;
            $new_my_wallet_log['future'] = $new_my_wallet['now'];
            $new_my_wallet_log['from_id'] = 0;

            if (!Db::table('my_wallet_log')->insert($new_my_wallet_log)) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }
            Db::commit();
            return ['code' => 0, 'msg' => '兑换成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 1, 'msg' => '出现异常'];
        }
    }

    /**
     * 团队收益兑GTC
     */
    public function team_profit_to_gtc()
    {
        try {
            //是否有兑换数据
            if (!input('?post.eth')) {
                return ['code' => 1, 'msg' => '参数不足'];
            }
            $eth = input('post.eth');
            //判断兑换数据是否合法
            if (!is_numeric($eth) || $eth <= 0) {
                return ['code' => 1, 'msg' => '参数错误'];
            };
            //获取用户数据
            $user = User::alias('u')
                ->field(['u.id', 'u.now_team_integral'])
                ->where('u.id', $this->userId)
                ->find();

            if (empty($user)) {
                return ['code' => 1, 'msg' => '用户不存在'];
            }
            //判断GC余额是否充足
            if ($user['now_team_integral'] < $eth) {
                return ['code' => 1, 'msg' => '团队收益不足'];
            }
            //当前时间
            $time = time();
            //开始兑换
            //创建数据库事务
            Db::startTrans();

            $now = $user['now_team_integral'] - $eth;

            //添加GC流水日志
            $my_team_log = [
                'uid' => $user['id'],
                'number' => -$eth,
                'type' => 4,
                'createtime' => $time,
                'old' => $user['now_team_integral'],
                'source_id' => $user['id'],
                'new' => $now
            ];
            if (!Db::table('team_log')->insert($my_team_log)) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }


            //扣除团队收益
            if (!User::where('id', $user['id'])->setDec('now_team_integral', $eth)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '操作失败1!'];
            }

            //增加GTC

            $my_wallet = Db::table('my_wallet')
                ->field(['now', 'old'])
                ->where('uid', $user['id'])
                ->find();
            if (empty($my_wallet)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '操作失败2!'];
            }
            $new_my_wallet = [
                'now' => $my_wallet['now'] + $eth,
                'old' => $my_wallet['old'] + $eth
            ];

            if (!Db::table('my_wallet')->where('uid', $user['id'])->update($new_my_wallet)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '操作失败!'];
            }

            //兑换后如果GTC余额大于充值激活最低金额则激活用户
            $activation_num = Config::getValue('activation_num'); //最低激活余额
            $u_num = Db::table('my_wallet')->where('uid', $user['id'])->field('old')->find(); //历史充值金额
            $b_uactive = User::where("id", $user['id'])->value('is_active'); //激活状态
            if ($u_num && $b_uactive == 0 && $u_num['old'] >= $activation_num) {
                $bu_save['is_active'] = 1;
                $bu_save['status'] = 1;
                $bu_save['active_time'] = $time;
                $bu_save['update_time'] = $time;
                $res = User::where('id', $msglist['u_id'])->update($bu_save);
            };
            //添加数据记录
            $new_my_wallet_log['uid'] = $user['id'];
            $new_my_wallet_log['number'] = $eth;
            $new_my_wallet_log['now'] = $my_wallet['now'];
            $new_my_wallet_log['remark'] = '收益兑换';
            $new_my_wallet_log['types'] = 10;
            $new_my_wallet_log['create_time'] = $time;
            $new_my_wallet_log['future'] = $new_my_wallet['now'];
            $new_my_wallet_log['from_id'] = 0;

            if (!Db::table('my_wallet_log')->insert($new_my_wallet_log)) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }
            Db::commit();
            return ['code' => 0, 'msg' => '兑换成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 1, 'msg' => '出现异常'];
        }
    }

    /**
     * 推广收益/团队收益兑GTC
     */
    public function exchange_profit_to_gtc()
    {
        try {
            //是否有兑换数据
            if (!input('?post.eth')) {
                return json(['code' => 1, 'msg' => '参数不足']);
            }
            $eth = input('post.eth');
            //判断兑换数据是否合法
            if (!is_numeric($eth) || $eth <= 0) {
                return json(['code' => 1, 'msg' => '参数错误']);
            };
            //获取用户数据
            $user = User::alias('u')
                ->field('u.id,u.now_prohibit_integral,u.now_team_integral,sum(u.now_prohibit_integral + u.now_team_integral) total_integral')
                ->where('u.id', 1536) // $this->userId
                ->find();

            if (empty($user)) {
                return json(['code' => 1, 'msg' => '用户不存在']);
            }
            //兑换数量是否充足
            if ($user['total_integral'] < $eth) {
                return json(['code' => 1, 'msg' => '兑换失败,可兑换数量不足']);
            }
            //当前时间
            $time = time();
            //开始兑换
            //创建数据库事务
            Db::startTrans();

            $now = $user['now_prohibit_integral'] - $eth;

            //添加GC流水日志
            $my_prohibit_log = [
                'uid' => $user['id'],
                'number' => -$eth,
                'type' => 4,
                'createtime' => $time,
                'old' => $user['now_prohibit_integral'],
                'source_id' => $user['id'],
                'new' => $now
            ];

            if (!Db::table('prohibit_log')->insert($my_prohibit_log)) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }


            //扣除团队收益
            if (!User::where('id', $user['id'])->setDec('now_prohibit_integral', $eth)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '操作失败1!'];
            }

            //增加GTC

            $my_wallet = Db::table('my_wallet')
                ->field(['now', 'old'])
                ->where('uid', $user['id'])
                ->find();
            if (empty($my_wallet)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '操作失败2!'];
            }
            $new_my_wallet = [
                'now' => $my_wallet['now'] + $eth,
                'old' => $my_wallet['old'] + $eth
            ];

            if (!Db::table('my_wallet')->where('uid', $user['id'])->update($new_my_wallet)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '操作失败!'];
            }

            //兑换后如果GTC余额大于充值激活最低金额则激活用户
            $activation_num = Config::getValue('activation_num'); //最低激活余额
            $u_num = Db::table('my_wallet')->where('uid', $user['id'])->field('old')->find(); //历史充值金额
            $b_uactive = User::where("id", $user['id'])->value('is_active'); //激活状态
            if ($u_num && $b_uactive == 0 && $u_num['old'] >= $activation_num) {
                $bu_save['is_active'] = 1;
                $bu_save['status'] = 1;
                $bu_save['active_time'] = $time;
                $bu_save['update_time'] = $time;
                $res = User::where('id', $user['id'])->update($bu_save);
            };
            //添加数据记录
            $new_my_wallet_log['uid'] = $user['id'];
            $new_my_wallet_log['number'] = $eth;
            $new_my_wallet_log['now'] = $my_wallet['now'];
            $new_my_wallet_log['remark'] = '收益兑换';
            $new_my_wallet_log['types'] = 10;
            $new_my_wallet_log['create_time'] = $time;
            $new_my_wallet_log['future'] = $new_my_wallet['now'];
            $new_my_wallet_log['from_id'] = 0;

            if (!Db::table('my_wallet_log')->insert($new_my_wallet_log)) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }
            Db::commit();
            return ['code' => 0, 'msg' => '兑换成功'];
        } catch (\Exception $e) {
            Db::rollback();
            return ['code' => 1, 'msg' => '出现异常'];
        }
    }
}