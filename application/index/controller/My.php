<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/14
 * Time: 16:41
 */

namespace app\index\controller;


use app\common\entity\MyWalletLog;
use app\common\entity\MyWallet;
use app\common\entity\Profit;
use app\common\entity\Proportion;
use app\common\entity\Quotation;
use app\common\entity\User;
use app\common\entity\Config;
use app\common\entity\WithdrawLog;
use app\common\entity\YekesConfig;
use app\common\entity\YekesLog;
use app\common\entity\ProductPool;
use app\common\service\Users\Service;
use app\index\validate\RegisterForm;
use app\index\model\Publics as PublicModel;
use app\index\model\My as MyModel;
use app\index\model\User as UserModel;
use think\Db;
use think\Request;


class My extends Base
{

    /**
     * GTC
     * @return \think\response\Json
     */
    public function bait_list()
    {

        $uid = $this->userId;
        $page = input('post.page') ? input('post.page') : 1;
        $type = input('post.type') ? input('post.type') : 0;
        $limit = input('post.limit') ? input('post.limit') : 15;
        $MyModel = new  MyModel;


        if ($type == 1) {//收入
            $map['number'] = ['>', 0];
        } elseif ($type == 2) {//支出
            $map['number'] = ['<', 0];
        }
        $map['uid'] = $uid;
        $map['is_delete'] = 0;
        $list = Db::table('my_wallet_log')
            ->where($map)
            ->field('id,number,create_time,types,from_id')
            ->order('create_time desc')
            ->page($page)
            ->paginate($limit)
            ->toArray();
        if (empty($list)) {
            $list = array();
        } else {
            $list = $list['data'];
            foreach ($list as $k => $v) {
                if ($v['types'] == 5) {
                    //互转
                    $user = User::alias('u')
                        ->join('user_invite_code uic', 'uic.user_id = u.id')
                        ->where("uic.user_id", $v['from_id'])
                        ->field('u.nick_name,uic.invite_code')->find();
                    $list[$k]['name'] = $MyModel->get_type_name($v['types']) . '给：' . $user['nick_name'] . '（' . $user['invite_code'] . '）';
                } else {
                    $list[$k]['name'] = $MyModel->get_type_name($v['types']);
                }
                $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
        }


        $info = $list;
        return json(['code' => 0, 'msg' => 'access!', 'info' => $info]);


    }

    /**
     * GTC
     * @return \think\response\Json
     */
    public function integral_list()
    {

        $uid = $this->userId;
        $page = input('post.page') ? input('post.page') : 1;
        $type = input('post.type') ? input('post.type') : 0;
        $limit = input('post.limit') ? input('post.limit') : 15;
        $MyModel = new  MyModel;

        if ($type == 1) {//收入
            $map['number'] = ['>', 0];
        } elseif ($type == 2) {//支出
            $map['number'] = ['<', 0];
        }
        $map['uid'] = $uid;
        $map['is_delete'] = 0;
        $list = Db::table('my_integral_log')
            ->where($map)
            ->field('id,number,create_time,types,from_id')
            ->order('create_time desc')
            ->page($page)
            ->paginate($limit)
            ->toArray();
        if (empty($list)) {
            $list = array();
        } else {
            $list = $list['data'];
            foreach ($list as $k => $v) {
                if ($v['types'] == 5) {
                    //互转
                    $user = User::alias('u')
                        ->join('user_invite_code uic', 'uic.user_id = u.id')
                        ->where("uic.user_id", $v['from_id'])
                        ->field('u.nick_name,uic.invite_code')->find();
                    $list[$k]['name'] = $MyModel->get_intagral_log_type_name($v['types']) . '给：' . $user['nick_name'] . '（' . $user['invite_code'] . '）';
                } else {
                    $list[$k]['name'] = $MyModel->get_intagral_log_type_name($v['types']);
                }
                $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
        }

        $info = $list;
        return json(['code' => 0, 'msg' => 'access!', 'info' => $info]);
    }

    /**
     * 团队收益列表
     * @return \think\response\Json
     */
    public function team_profit_list()
    {

        $uid = $this->userId;
        $page = input('post.page') ? input('post.page') : 1;
        $limit = input('post.limit') ? input('post.limit') : 15;
        $type = input('post.type') ? input('post.type') : 0; // 默认0为全部(0:全部,2:收入,4:兑换)

        $map['tpl.uid'] = $uid;
        if (!empty($type)) {
            $map['tpl.type'] = $type;
        }
        $list = Db::table('team_log')
            ->alias('tpl')
            ->join('user u', 'u.id = tpl.source_id')
            ->where($map)
            ->field('tpl.number,tpl.createtime,u.nick_name,tpl.source_id,tpl.type')
            ->order('createtime desc')
            ->page($page)
            ->paginate($limit)
            ->toArray();
        if (empty($list)) {
            $list = array();
        } else {
            $list = $list['data'];
            foreach ($list as $k => $v) {
                $list[$k]['create_time'] = date('Y-m-d H:i:s', $list[$k]['createtime']);
                $list[$k]['order_number'] = 'TD' . $list[$k]['createtime'] . $v['source_id'];
            }
        }


        $info = $list;
        return json(['code' => 0, 'msg' => 'access!', 'info' => $info]);

    }

    /**
     * 推广收益列表
     * @return \think\response\Json
     */
    public function extension_profit_list()
    {

        try {
            $uid = $this->userId;
            $page = input('post.page') ? input('post.page') : 1;
            $limit = input('post.limit') ? input('post.limit') : 15;
            $type = input('post.type') ? input('post.type') : 0; // 默认0为全部(0:全部,2:收入,4:兑换)

            $map['tpl.uid'] = $uid;
            if (!empty($type)) {
                $map['tpl.type'] = $type;
            }

            $map['tpl.uid'] = $uid;
            $list = Db::table('prohibit_log')
                ->alias('tpl')
                ->join('user u', 'u.id = tpl.source_id')
                ->where($map)
                ->field('tpl.number,tpl.createtime,u.nick_name,tpl.source_id,tpl.type')
                ->order('createtime desc')
                ->page($page)
                ->paginate($limit)
                ->toArray();
            if (empty($list)) {
                $list = array();
            } else {
                $list = $list['data'];
                foreach ($list as $k => $v) {
                    $list[$k]['create_time'] = date('Y-m-d H:i:s', $list[$k]['createtime']);
                    $list[$k]['order_number'] = 'TD' . $list[$k]['createtime'] . $v['source_id'];
                }
            }


            $info = $list;
            return json(['code' => 0, 'msg' => 'access!', 'info' => $info]);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }

    }

    /**
     * GTC转出
     * @return \think\response\Json
     */
    public function bait_out()
    {

        $uid = $this->userId;
        $mobile = input('post.mobile');
        $num = input('post.num');
        $pwd = input('post.pwd');

        if (!preg_match('#^1\d{10}$#', $mobile)) {
            return json(['code' => 1, 'message' => '手机号码格式不正确']);
        }
        $user = User::where('id', $this->userId)->find();

        if (empty($user['trad_password'])) {
            return json(['code' => 1, 'msg' => '请先设置支付密码!']);
        }
        $service = new \app\common\service\Users\Service();
        $result = $service->checkPayPassword($pwd, $user);
        if (!$result) {
            return json(['code' => 1, 'msg' => '支付密码错误!']);
        }

        $buser = User::where("mobile", $mobile)->value('id');

        if (!$buser) {
            return json(['code' => 1, 'msg' => '无效用户!']);
        }

        $b_pid = User::where("mobile", $mobile)->value('pid');

        if ($b_pid != $uid) {
            return json(['code' => 1, 'msg' => '只能给自己的队员转出!']);
        }

        if ($buser == $uid) {
            return json(['code' => 1, 'msg' => '不可以转给自己!']);
        }

        if (!$num || $num <= 0) {
            return json(['code' => 1, 'msg' => '交易GTC不能为空!']);

        }

        $activation_num = Config::getValue('activation_num');
        $min_bait = Config::getValue('min_bait');


        $map['uid'] = $uid;

        $bait = Db::table('my_wallet')
            ->where($map)
            ->value('now');


        if ($activation_num > $bait) {
            return json(['code' => 1, 'msg' => "用户GTC小于{$activation_num}不可以转让GTC!"]);

        }

        if ($min_bait > $num) {
            return json(['code' => 1, 'msg' => "转让GTC小于{$min_bait}不可以转让GTC!"]);

        }
        if ($num > $bait) {
            return json(['code' => 1, 'msg' => 'GTC不足!']);
        }

        $now = $bait - $num;

        if ($activation_num > $now) {
            return json(['code' => 1, 'msg' => "用户转让后剩余GTC小于{$activation_num}不可以转让GTC!"]);
        }

        Db::startTrans();

        try {

            //修改用户GTC

            $is_my = Db::table('my_wallet')->where('uid', $uid)->setDec('now', $num);

            if (!$is_my) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }
            //添加数据记录

            $add['uid'] = $uid;
            $add['number'] = -$num;
            $add['now'] = $bait;
            $add['remark'] = '交易GTC';
            $add['types'] = 5;
            $add['create_time'] = time();
            $add['future'] = $now;
            $add['from_id'] = $buser;

            $is_add = Db::table('my_wallet_log')->insert($add);


            if (!$is_add) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }


            $map['uid'] = $buser;

            $bait = Db::table('my_wallet')
                ->where($map)
                ->value('now');

            //修改用户接收GTC
            $b_save['now'] = $bait + $num;

            $is_my = Db::table('my_wallet')->where('uid', $buser)->setInc('now', $num);
            $is_old = Db::table('my_wallet')->where('uid', $buser)->setInc('old', $num);

            if (!$is_my || !$is_old) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }

            //添加数据记录

            $add['uid'] = $buser;
            $add['number'] = $num;
            $add['now'] = $bait;
            $add['remark'] = '交易GTC';
            $add['types'] = 5;
            $add['create_time'] = time();
            $add['future'] = $b_save['now'];
            $add['from_id'] = $uid;
            $is_add = Db::table('my_wallet_log')->insert($add);


            if (!$is_add) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }


            Db::commit();

            return json(['code' => 0, 'msg' => '转让成功!']);


        } catch (\Exception $e) {

            Db::rollback();
            return json(['code' => 1, 'msg' => '网络错误!']);

        }


        return json(['code' => 0, 'msg' => '操作成功!', 'info' => $info]);


    }

    /**
     *  ylbGTC转出
     * @return \think\response\Json
     */
    public function bait_out_ylb()
    {

        $uid = $this->userId;
        $mobile = input('post.mobile');
        $mobile = trim($mobile);
        $num = input('post.num');
        $pwd = input('post.pwd');

        if (empty($mobile)) {
            return json(['code' => 1, 'msg' => '用户id不能为空!']);
        }
        $user = User::where('id', $this->userId)->find();

        if (empty($user['trad_password'])) {
            return json(['code' => 1, 'msg' => '请先设置支付密码!']);
        }
        $service = new \app\common\service\Users\Service();
        $result = $service->checkPayPassword($pwd, $user);
        if (!$result) {
            return json(['code' => 1, 'msg' => '支付密码错误!']);
        }

        $buser = User::alias('u')
            ->join('user_invite_code uic', 'uic.user_id = u.id')
            ->where("uic.invite_code", $mobile)
            ->value('u.id');

        if (!$buser) {
            return json(['code' => 1, 'msg' => '无效用户!']);
        }


        if ($buser == $uid) {
            return json(['code' => 1, 'msg' => '不可以转给自己!']);
        }

        /*
        $child = User::field('id,status')->where('pid',$uid)->select();

        $is_t = (new User())->get_is_Team($child,$buser);

        if(!$is_t){
            return json(['code' => 1, 'msg' => '只能给自己的队员转出!']);
        }
        */


        if (!$num || $num <= 0) {
            return json(['code' => 1, 'msg' => '交易GTC不能为空!']);

        }

        $activation_num = Config::getValue('activation_num');
        $min_bait = Config::getValue('min_bait');


        $map['uid'] = $uid;

        $bait = Db::table('my_wallet')
            ->where($map)
            ->value('now');

        if ($activation_num > $bait) {
            return json(['code' => 1, 'msg' => "用户GTC小于{$activation_num}不可以转让GTC!"]);

        }

        if ($num > $bait) {
            return json(['code' => 1, 'msg' => 'GTC不足!']);
        }

        $now = $bait - $num;

        if ($activation_num > $now) {
            return json(['code' => 1, 'msg' => "用户转让后剩余GTC小于{$activation_num}不可以转让GTC!"]);
        }

        Db::startTrans();

        try {

            //扣除转账方GTC

            if (!Db::table('my_wallet')->where('uid', $user['id'])->setDec('now', $num)) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }

            //添加数据记录

            $new_wallet_log['uid'] = $uid;
            $new_wallet_log['number'] = -$num;
            $new_wallet_log['now'] = $bait;
            $new_wallet_log['remark'] = '交易GTC';
            $new_wallet_log['types'] = 5;
            $new_wallet_log['create_time'] = time();
            $new_wallet_log['future'] = $now;
            $new_wallet_log['from_id'] = $buser;

            if (!Db::table('my_wallet_log')->insert($new_wallet_log)) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }

            /////////////////////////////////////////////////////////////////////////
            $map['uid'] = $buser;

            $bait = Db::table('my_wallet')
                ->where($map)
                ->value('now');

            //修改用户接收GTC
            $b_save['now'] = $bait + $num;

            $is_my = Db::table('my_wallet')->where('uid', $buser)->setInc('now', $num);
            $is_old = Db::table('my_wallet')->where('uid', $buser)->setInc('old', $num);

            if (!$is_my || !$is_old) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }


            $u_num = Db::table('my_wallet')->where('uid', $buser)->field('old')->find();
            $b_uactive = User::alias('u')
                ->join('user_invite_code uic', 'uic.user_id = u.id')
                ->where("uic.invite_code", $mobile)
                ->value('u.is_active');

            if ($u_num && $b_uactive == 0) {

                if ($u_num['old'] >= $activation_num) {

                    $update_buser['is_active'] = 1;
                    $update_buser['status'] = 1;
                    $update_buser['active_time'] = time();
                    $update_buser['update_time'] = time();
                    $res = User::where('id', $buser)->update($update_buser);

                }
            }


            //添加数据记录

            $new_wallet_log['uid'] = $buser;
            $new_wallet_log['number'] = $num;
            $new_wallet_log['now'] = $bait;
            $new_wallet_log['remark'] = '交易GTC';
            $new_wallet_log['types'] = 5;
            $new_wallet_log['create_time'] = time();
            $new_wallet_log['future'] = $b_save['now'];
            $new_wallet_log['from_id'] = $uid;
            $is_add = Db::table('my_wallet_log')->insert($new_wallet_log);


            if (!$is_add) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }


            Db::commit();

            return json(['code' => 0, 'msg' => '转让成功!']);


        } catch (\Exception $e) {

            Db::rollback();
            return json(['code' => 1, 'msg' => '网络错误!']);

        }


        return json(['code' => 0, 'msg' => '操作成功!', 'info' => $info]);


    }

    /**
     *  ylbGTC转出
     * @return \think\response\Json
     */
    public function integral_out_ylb()
    {

        $uid = $this->userId;
        $mobile = input('post.mobile');
        $mobile = trim($mobile);
        $num = input('post.num');
        $pwd = input('post.pwd');

        if (empty($mobile)) {
            return json(['code' => 1, 'msg' => '用户id不能为空!']);
        }
        $user = User::where('id', $this->userId)->find();

        if (empty($user['trad_password'])) {
            return json(['code' => 1, 'msg' => '请先设置支付密码!']);
        }
        $service = new \app\common\service\Users\Service();
        $result = $service->checkPayPassword($pwd, $user);
        if (!$result) {
            return json(['code' => 1, 'msg' => '支付密码错误!']);
        }

        $buser = User::alias('u')
            ->join('user_invite_code uic', 'uic.user_id = u.id')
            ->where("uic.invite_code", $mobile)
            ->value('u.id');

        if (!$buser) {
            return json(['code' => 1, 'msg' => '无效用户!']);
        }


        if ($buser == $uid) {
            return json(['code' => 1, 'msg' => '不可以转给自己!']);
        }


        $child = User::field('id,status')->where('pid', $uid)->select();

        $is_t = (new User())->get_is_Team($child, $buser);

        if (!$is_t) {
            return json(['code' => 1, 'msg' => '只能给自己的队员转出!']);
        }


        if (!$num || $num <= 0) {
            return json(['code' => 1, 'msg' => '交易积分不能为空!']);

        }

        $activation_num = Config::getValue('activation_num');
        $min_bait = Config::getValue('min_bait');


        $map['uid'] = $uid;

        $bait = Db::table('my_integral')
            ->where($map)
            ->value('now');

        if ($activation_num > $bait) {
            return json(['code' => 1, 'msg' => "转出积分不可小于{$activation_num}!"]);

        }

        if ($num > $bait) {
            return json(['code' => 1, 'msg' => '积分不足!']);
        }

        $now = $bait - $num;

        if ($activation_num > $now) {
            return json(['code' => 1, 'msg' => "用户转让后剩余积分小于{$activation_num}不可以转出!"]);
        }

        Db::startTrans();

        try {

            //扣除转账方GTC

            if (!Db::table('my_integral')->where('uid', $user['id'])->setDec('now', $num)) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }

            //添加数据记录

            $new_integral_log['uid'] = $uid;
            $new_integral_log['number'] = -$num;
            $new_integral_log['now'] = $bait;
            $new_integral_log['remark'] = '交易积分';
            $new_integral_log['types'] = 5;
            $new_integral_log['create_time'] = time();
            $new_integral_log['future'] = $now;
            $new_integral_log['from_id'] = $buser;

            if (!Db::table('my_integral_log')->insert($new_integral_log)) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }

            /////////////////////////////////////////////////////////////////////////
            $map['uid'] = $buser;

            $bait = Db::table('my_integral')
                ->where($map)
                ->value('now');

            //修改用户接收GTC
            $b_save['now'] = $bait + $num;

            $is_my = Db::table('my_integral')->where('uid', $buser)->setInc('now', $num);
            $is_old = Db::table('my_integral')->where('uid', $buser)->setInc('old', $num);

            if (!$is_my || !$is_old) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }


            $u_num = Db::table('my_integral')->where('uid', $buser)->field('old')->find();
            $b_uactive = User::alias('u')
                ->join('user_invite_code uic', 'uic.user_id = u.id')
                ->where("uic.invite_code", $mobile)
                ->value('u.is_active');

            if ($u_num && $b_uactive == 0) {

                if ($u_num['old'] >= $activation_num) {

                    $update_buser['is_active'] = 1;
                    $update_buser['status'] = 1;
                    $update_buser['active_time'] = time();
                    $update_buser['update_time'] = time();
                    $res = User::where('id', $buser)->update($update_buser);

                }
            }


            //添加数据记录

            $new_integral_log['uid'] = $buser;
            $new_integral_log['number'] = $num;
            $new_integral_log['now'] = $bait;
            $new_integral_log['remark'] = '交易积分';
            $new_integral_log['types'] = 5;
            $new_integral_log['create_time'] = time();
            $new_integral_log['future'] = $b_save['now'];
            $new_integral_log['from_id'] = $uid;
            $is_add = Db::table('my_integral_log')->insert($new_integral_log);


            if (!$is_add) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '操作失败!']);
            }


            Db::commit();

            return json(['code' => 0, 'msg' => '转让成功!']);


        } catch (\Exception $e) {

            Db::rollback();
            return json(['code' => 1, 'msg' => '网络错误!']);

        }


        //return json(['code' => 0, 'msg' => '操作成功!','info' => $info]);


    }

    /**
     * 手机号获取用户信息
     * @return \think\response\Json
     */
    public function get_username()
    {


        $mobile = input('post.mobile');


        if (!preg_match('#^1\d{10}$#', $mobile)) {
            return json(['code' => 1, 'message' => '手机号码格式不正确']);
        }
        $user = User::where('mobile', $mobile)->value('nick_name');

        if (empty($user)) {
            return json(['code' => 1, 'msg' => '无该用户!']);
        }


        $info['name'] = $user;


        return json(['code' => 0, 'msg' => '操作成功!', 'info' => $info]);


    }

    /**
     * 邀请码是否有效
     * @return \think\response\Json
     */
    public function get_invite_code_username()
    {


        $mobile = input('post.mobile');

        $user = User::alias('u')
            ->join('user_invite_code uic', 'uic.user_id = u.id')
            ->where('uic.invite_code', $mobile)
            ->value('uic.invite_code');

        if (empty($user)) {
            return json(['code' => 1, 'msg' => '无该用户!']);
        }


        $info['name'] = $user;


        return json(['code' => 0, 'msg' => '操作成功!', 'info' => $info]);


    }


    public function fish_culture_list()
    {

        $uid = $this->userId;
        $page = input('post.page') ? input('post.page') : 1;
//        $type = input('post.type')?input('post.type'):0;
        $limit = input('post.limit') ? input('post.limit') : 15;


//        if($type == 1){//收入
//            $map['number'] = ['>',0];
//        }elseif ($type == 2){//支出
//            $map['number'] = ['<',0];
//        }
        $map['u.id'] = $uid;
//        $map['fo.status'] = 1;
        $map['fo.is_delete'] = 0;
        $map['au.status'] = 4;
        $list = Db::table('fish_order')
            ->alias('fo')
            ->join('fish f', 'f.id = fo.f_id')
            ->join('user u', 'u.id = f.u_id')
            ->join('appointment_user au', 'au.id = fo.types')
            ->where($map)
            ->field('fo.order_number,fo.worth,au.create_time,f.worth fworth ,fo.f_id,f.front_worth fwo,f.front_id as fid')
            ->order('au.buy_time desc')
            ->page($page)
            ->paginate($limit)
            ->toArray();

        if (empty($list)) {
            $list = array();
        } else {
            $list = $list['data'];
            foreach ($list as $k => $v) {
                $list[$k]['number'] = $v['worth'] - $v['fworth'];
                if ($list[$k]['number'] == 0) {
                    $list[$k]['number'] = $v['worth'] - $v['fwo'];
                    if ($list[$k]['number'] <= 0) {
                        // 拆分的数量
                        $num = DB::table('fish')->where('front_id', $v['fid'])->count();
                        $worth = DB::table('fish')->where('id', $v['fid'])->value('worth');
                        $list[$k]['number'] = ($v['fwo'] - $worth) / $num;
                    }
                }
                $list[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            }
        }

        $info = $list;
        return json(['code' => 0, 'msg' => '操作成功!', 'info' => $info]);


    }

    /**
     * 领取记录
     * @return \think\response\Json
     */

    public function adopt()
    {

        $uid = $this->userId;
        $page = input('post.page') ? input('post.page') : 1;
        // $type = input('post.type') ? input('post.type') : 0;
        $limit = input('post.limit') ? input('post.limit') : 15;

            $map['au.status'] = ['in', '2,3,4,-4'];
            $map['au.oid'] = ['>', '0'];


        $map['au.uid'] = $uid;


        $list = Db::table('appointment_user')
            ->alias('au')
            ->join('bathing_pool bp', 'bp.id = au.pool_id')
            ->join('user u', 'au.uid = u.id')
            ->join('user_invite_code uic', 'uic.user_id = u.id')
            ->where($map)
            ->field('au.id,bp.contract_time,bp.worth_max worth,bp.img,bp.profit,uic.invite_code user_name,bp.name,au.new_fid,au.pre_endtime over_time,au.oid,au.status,au.buy_time')
            ->order('over_time desc')
            ->page($page)
            ->paginate($limit)
            ->toArray();


        if (empty($list)) {
            $list = array();
        } else {
            $list = $list['data'];
            $time = time();

            foreach ($list as $k => $v) {
                $is_fo = Db::table('fish_order')
                    ->alias('fo')
                    ->join('fish f', 'f.id = fo.f_id')
                    ->join('user u', 'u.id = f.u_id')
                    ->join('user_invite_code uic', 'uic.user_id = u.id')
                    ->where('fo.id', $v['oid'])
                    ->field('fo.id,fo.over_time,fo.f_id,fo.worth,fo.order_number,uic.invite_code user_name')
                    ->find();
                $list[$k]['id'] = $v['id'];
                if ($v['status'] == 1) {
                    $list[$k]['status_name'] = '匹配中';
                    $list[$k]['over_time'] = $list[$k]['over_time'] - $time;
                    $list[$k]['status'] = 1;
                    $list[$k]['fid'] = 0;
                    $list[$k]['order_number'] = '';

                } elseif ($v['status'] == 2) {

                    $list[$k]['status_name'] = '待付款';
                    $list[$k]['over_time'] = date('Y-m-d H:i:s', $is_fo['over_time']);
//                    $list[$k]['over_time'] = $is_fo['over_time'] -$time;
//                    $list[$k]['over_time'] = 400;
                    $list[$k]['status'] = 2;
                    $list[$k]['fid'] = $is_fo['f_id'];
                    $list[$k]['worth'] = $is_fo['worth'];
                    $list[$k]['order_number'] = $is_fo['order_number'];
                    $list[$k]['user_name'] = $is_fo['user_name'];

                } elseif ($v['status'] == 3) {
                    $buy_time = Db::table('appointment_user')->where('oid', $is_fo['id'])->value('buy_time');
                    $list[$k]['over_time'] = payment_time($buy_time);

                    $list[$k]['status'] = 3;
                    $list[$k]['fid'] = $is_fo['f_id'];
                    $list[$k]['worth'] = $is_fo['worth'];
                    $list[$k]['order_number'] = $is_fo['order_number'];
                    $list[$k]['user_name'] = $is_fo['user_name'];

                } elseif ($v['status'] == 4) {
                    $okpay_time = Db::table('appointment_user')->where('oid', $is_fo['id'])->value('okpay_time');

                    $n_fish = Db::table('fish')->where('id', $v['new_fid'])->value('status');
                    if ($n_fish == 4) {
                        $list[$k]['status_name'] = '完成(归档)';
                    } else {
                        $list[$k]['status_name'] = '完成';
                    }

                    $list[$k]['over_time'] = date('Y-m-d H:i:s', $okpay_time);
                    $list[$k]['status'] = 4;
                    $list[$k]['fid'] = $is_fo['f_id'];
                    $list[$k]['worth'] = $is_fo['worth'];
                    $list[$k]['order_number'] = $is_fo['order_number'];
                    $list[$k]['user_name'] = $is_fo['user_name'];

                } elseif ($v['status'] == -4) {
                    $is_a = Db::table('appeal')->where('order_id', $v['oid'])->field('status,create_time')->order('create_time desc')->find();

                    if ($is_a['status'] == -2) {
                        $list[$k]['status_name'] = '取消';
                    } elseif ($is_a['status'] == -1) {
                        $list[$k]['status_name'] = '驳回';
                    } elseif ($is_a['status'] == 0) {
                        $list[$k]['status_name'] = '申诉';
                    } elseif ($is_a['status'] == 1) {
                        $list[$k]['status_name'] = '通过';
                    }
                    $list[$k]['over_time'] = date('Y-m-d H:i:s', $is_a['create_time']);
                    $list[$k]['status'] = -4;
                    $list[$k]['fid'] = $is_fo['f_id'];
                    $list[$k]['worth'] = $is_fo['worth'];
                    $list[$k]['order_number'] = $is_fo['order_number'];
                    $list[$k]['user_name'] = $is_fo['user_name'];

                }
                if ($list[$k]['fid']) {
                    $list[$k]['uid'] = Db::table('fish')->where('id', $list[$k]['fid'])->value('u_id');
                } else {
                    $list[$k]['uid'] = 0;
                }


            }


        }
        return json(['code' => 0, 'msg' => 'access!', 'info' => $list]);
    }


    /**
     * 领取详情
     * @return \think\response\Json
     */
    public function adoptMsg()
    {
        $uid = $this->userId;
        $id = input('post.id');
        if (empty($id)) {
            return json(['code' => 1, 'msg' => '无效数据!']);
        }
        $map['fo.id'] = $id;
        $msg = Db::table('fish_order')
            ->alias('fo')
            ->join('appointment_user au', 'fo.types = au.id')
            ->join('user bu', 'au.uid = bu.id')
            ->join('bathing_pool bp', 'bp.id = au.pool_id')
            ->join('fish f', 'f.id = fo.f_id')
            ->join('user u', 'f.u_id = u.id')
            ->where($map)
            ->field('u.id user_id,bu.nick_name my_name,bu.mobile my_mobile,au.create_time,au.card_id,au.pay_imgs,bp.bait,fo.worth,fo.id,au.buy_types,bp.contract_time,bp.profit,u.nick_name user_name,u.mobile user_mobile,bp.name,fo.order_number,fo.over_time')
            ->find();

        if ($msg) {
            $msg['card_types'] = '';
            $msg['imgs'] = '';
            $msg['bank_name'] = '';
            $msg['names'] = '';
            $is_card = Db::table('card')->where('id', $msg['card_id'])->find();

            if ($msg['card_id'] && $is_card) {
                $msg['names'] = $is_card['names'];
                $msg['bank_name'] = $is_card['bank_name'];
                $msg['imgs'] = $is_card['imgs'];
                $msg['card_types'] = $is_card['types'];
            }
            unset($msg['card_id']);
        }
        if (empty($msg)) {
            return json(['code' => 1, 'msg' => '无效数据!']);
        }

        return json(['code' => 0, 'msg' => 'access!', 'info' => $msg]);

    }


    public function get_user_card_list()
    {
        $id = input('post.uid');
        if (!$id) {
            return json(['code' => 1, 'msg' => 'id不能为空!']);
        }
        $map['u_id'] = $id;
        $map['is_delete'] = 0;
        $list = Db::table('card')
            ->where($map)
            ->field('id,bank_name,types,names')
            ->order('create_time desc')
            ->select();

        return json(['code' => 0, 'msg' => 'access!', 'info' => $list]);
    }


    /**
     * 上传凭证
     * @return \think\response\Json
     */
    public function payOrder()
    {
        set_time_limit(0);
        $id = input('post.id');
        if (empty($id)) {
            return json(['code' => 1, 'msg' => '详情id不能为空!']);
        }
        $cid = input('post.cid');
        if (empty($cid)) {
            return json(['code' => 1, 'msg' => '收款人信息不能为空!']);
        }
        $pay_imgs = input('post.pay_imgs');
        if (empty($pay_imgs)) {
            return json(['code' => 1, 'msg' => '收款凭证不能为空!']);
        }

        $pwd = input('post.pwd');
        if (empty($pwd)) {
            return json(['code' => 1, 'msg' => '支付密码不能为空!']);
        }
        $user = User::where('id', $this->userId)->find();

        if (empty($user['trad_password'])) {
            return json(['code' => 1, 'msg' => '请先设置支付密码!']);
        }

        $service = new \app\common\service\Users\Service();
        $result = $service->checkPayPassword($pwd, $user);
        if (!$result) {
            return json(['code' => 1, 'msg' => '支付密码错误!']);
        }

        $uid = $this->userId;

        $map['bu_id'] = $uid;
        $map['id'] = $id;


        $is_fo = Db::table('fish_order')->where($map)->field('status,types,f_id')->find();

        if ($is_fo) {
            if ($is_fo['status'] == 1) {
                return json(['code' => 1, 'msg' => '已提交改操作，请勿重复提交!']);
            }
            if ($is_fo['status'] != 0) {
                return json(['code' => 1, 'msg' => '无效对象!']);
            }

        } else {
            return json(['code' => 1, 'msg' => '无效对象!']);
        }
        Db::startTrans();
        try {
//      修改酒馆订单信息
            $fo_save['status'] = 1;//上传凭证
            $fo_save['update_time'] = time();
            $is_fosave = Db::table('fish_order')->where($map)->update($fo_save);
            if (!$is_fosave) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '提交失败!']);
            }

            if (empty($is_fo['types'])) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '提交失败!']);
            }


            $aumap['id'] = $is_fo['types'];
            $aumap['uid'] = $uid;
            $is_au = Db::table('appointment_user')->where($aumap)->field('pool_id,id,status')->find();
            if (!$is_au) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '提交失败!']);
            } else {
                if ($is_au['status'] == 1 || empty($is_au['pool_id'])) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => '提交失败!']);
                }
            }


//添加新酒修改原酒操作


            $service = new \app\common\service\Fish\Service();
            $is_save = $service->BuyFishIndex($is_fo['f_id'], $uid, $id);
            if (!$is_save) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '网络繁忙请重新提交!']);
            }

//      修改预约表信息

            $au_save['new_fid'] = $is_save;
            $au_save['pay_imgs'] = $pay_imgs;
            $au_save['card_id'] = $cid;
            $au_save['buy_time'] = time();
            $au_save['status'] = 3;
            $au_save['update_time'] = time();
            $is_ausave = Db::table('appointment_user')->where($aumap)->update($au_save);
            if (!$is_ausave) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '提交失败!']);
            }

            $fmsg = $entity = Db::table('fish')->alias('f')
                ->join('bathing_pool bp', 'bp.id = f.pool_id')
                ->join('user fu', 'fu.id = f.u_id', 'INNER')
                ->where('f.id', $is_fo['f_id'])
                ->where('bp.is_delete', '0')
                ->field('bp.*,f.worth,f.is_show,f.front_id,f.types,f.id fid,f.status fstatus,fu.mobile fmobile')
                ->find();
            $fmsg['name'] = $fmsg['name'] . ',' . payment_zw_time($au_save['buy_time']);


            $Every = new  Every();
//            $fmsg['fmobile'] = '15113497949';
            $is_send = $Every->sendAllCode($fmsg['fmobile'], $fmsg['name'], 1);
            if (!$is_send) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '通知短信发送失败,请重新点击支付!']);
            }
            Db::commit();
            return json(['code' => 0, 'msg' => '提交成功！']);
        } catch (\Exception $e) {

            Db::rollback();
            return json(['code' => 1, 'msg' => '提交失败!']);
        }


    }


    /**
     * 转让记录
     * @return \think\response\Json
     */
    public function turn()
    {

        $uid = $this->userId;
        $page = input('post.page') ? input('post.page') : 1;
        $type = input('post.type') ? input('post.type') : 0;
        $limit = input('post.limit') ? input('post.limit') : 15;

        $map['bp.is_delete'] = 0;
        $map['bp.is_open'] = 1;
        $map['f.is_show'] = 1;
        $map['f.is_delete'] = 0;
        if ($type == 0) {
            $map['au.status'] = ['in', '1,2,3'];// 点击领取,分配到酒,上传支付
            $map['f.u_id'] = $uid;
        } elseif ($type == 1) {
            $map['f.u_id'] = $uid;
            $map['au.status'] = ['in', '4'];
        } elseif ($type == 2) {
            $map['f.u_id'] = $uid;

            $map['au.status'] = ['in', '-4'];//投诉取消
        } else {
            $map['f.status'] = ['in', '1,0'];
            $map['f.u_id'] = $uid;

//            $map['f.is_status'] = 1;

            $list = Db::table('fish')
                ->alias('f')
                ->join('bathing_pool bp', 'bp.id = f.pool_id')
                ->join('user u', 'u.id = f.u_id')
                ->where($map)
                ->field('f.id fid,bp.contract_time,bp.img,f.worth,f.status,bp.profit,bp.name,bp.end_time')
                ->order('f.id desc')
                ->page($page)
                ->paginate($limit)
                ->toArray();

            if (empty($list)) {
                $list = array();
            } else {
                $list = $list['data'];
                foreach ($list as $k => $v) {
                    if ($v['status'] == 1) {
                        $list[$k]['status_name'] = '待转让';
                    } else {
                        $list[$k]['status_name'] = '闲置';
                    }
                    $date1 = date('H:i:s', $v['end_time']);


                }
            }

        }


        if ($type == 0 || $type == 1 || $type == 2) {
//            $map['au.uid'] = $uid;

            $list = Db::table('appointment_user')
                ->alias('au')
                ->join('bathing_pool bp', 'bp.id = au.pool_id')
                ->join('fish_order fo', 'fo.id = au.oid')
                ->join('fish f', 'f.id = fo.f_id')
                ->join('user u', 'f.u_id = u.id')
                ->join('user_invite_code uic', 'uic.user_id = u.id')
                ->where($map)
                ->field('au.id,bp.contract_time,bp.img,bp.worth_max worth,f.worth,bp.profit,uic.invite_code user_name,bp.name,au.new_fid,fo.over_time,au.oid,au.status')
                ->order('au.create_time desc')
                ->page($page)
                ->paginate($limit)
                ->toArray();

            if (empty($list)) {
                $list = array();
            } else {
                $list = $list['data'];
                $time = time();

                foreach ($list as $k => $v) {
                    $is_fo = Db::table('fish_order')
                        ->alias('fo')
                        ->join('user u', 'u.id = fo.bu_id')
                        ->join('user_invite_code uic', 'uic.user_id = u.id')
                        ->where('fo.id', $v['oid'])->field('fo.id,fo.over_time,fo.f_id,fo.worth,fo.order_number,uic.invite_code user_name')->find();
                    $list[$k]['id'] = $v['id'];


                    if ($v['status'] == 1) {
                        $service = new \app\common\service\Fish\Service();
                        $worth = $service->get_worth($v['f_id']);
                        if (empty($worth)) {
                            $worth = 0;
                        }

                        $list[$k]['status_name'] = '匹配中';
//                        $list[$k]['over_time'] = $list[$k]['over_time'] -$time;
                        $list[$k]['over_time'] = date('Y-m-d H:i:s', $list[$k]['over_time']);
                        $list[$k]['status'] = 1;
                        $list[$k]['fid'] = 0;
                        $list[$k]['order_number'] = '';
                        $list[$k]['worth'] = $worth;

                    } elseif ($v['status'] == 2) {

                        $list[$k]['status_name'] = '待付款';
//                        $list[$k]['over_time'] = $is_fo['over_time'] -$time;
                        $list[$k]['over_time'] = date('Y-m-d H:i:s', $list[$k]['over_time']);
//                        $list[$k]['over_time'] = 400;
                        $list[$k]['status'] = 2;
                        $list[$k]['fid'] = $is_fo['f_id'];
                        $list[$k]['worth'] = $is_fo['worth'];
                        $list[$k]['order_number'] = $is_fo['order_number'];
                        $list[$k]['user_name'] = $is_fo['user_name'];


                    } elseif ($v['status'] == 3) {
                        $buy_time = Db::table('appointment_user')->where('oid', $is_fo['id'])->value('buy_time');

                        $list[$k]['status_name'] = '待完成';
                        $list[$k]['over_time'] = payment_time($buy_time);
                        $list[$k]['status'] = 3;
                        $list[$k]['fid'] = $is_fo['f_id'];
                        $list[$k]['worth'] = $is_fo['worth'];
                        $list[$k]['order_number'] = $is_fo['order_number'];
                        $list[$k]['user_name'] = $is_fo['user_name'];
                    } elseif ($v['status'] == 4) {
                        $okpay_time = Db::table('appointment_user')->where('oid', $is_fo['id'])->value('okpay_time');

                        $list[$k]['status_name'] = '完成';
                        $list[$k]['over_time'] = date('Y-m-d H:i:s', $okpay_time);
                        $list[$k]['status'] = 4;
                        $list[$k]['fid'] = $is_fo['f_id'];
                        $list[$k]['worth'] = $is_fo['worth'];
                        $list[$k]['order_number'] = $is_fo['order_number'];
                        $list[$k]['user_name'] = $is_fo['user_name'];
                    } elseif ($v['status'] == -4) {
                        $is_a = Db::table('appeal')->where('order_id', $v['oid'])->field('status,create_time')->order('create_time desc')->find();

                        if ($is_a['status'] == -2) {
                            $list[$k]['status_name'] = '取消';
                        } elseif ($is_a['status'] == -1) {
                            $list[$k]['status_name'] = '驳回';
                        } elseif ($is_a['status'] == 0) {
                            $list[$k]['status_name'] = '申诉';
                        } elseif ($is_a['status'] == 1) {
                            $list[$k]['status_name'] = '通过';
                        }
                        $list[$k]['over_time'] = date('Y-m-d H:i:s', $is_a['create_time']);
                        $list[$k]['status'] = -4;
                        $list[$k]['fid'] = $is_fo['f_id'];
                        $list[$k]['worth'] = $is_fo['worth'];
                        $list[$k]['order_number'] = $is_fo['order_number'];
                        $list[$k]['user_name'] = $is_fo['user_name'];
                    }
                    if ($list[$k]['fid']) {
                        $list[$k]['uid'] = Db::table('fish')->where('id', $list[$k]['fid'])->value('u_id');
                    } else {
                        $list[$k]['uid'] = 0;
                    }
                }
            }
        }

        return json(['code' => 0, 'msg' => 'access!', 'info' => $list]);

    }


    /**
     * 确认支付
     * @return string
     */
    public function over_order()
    {

        //获取支付类型
        $pay_type = input('post.pay_type');

        $oid = input('post.oid');
        if (empty($oid)) {
            return json(['code' => 1, 'msg' => '订单id不能为空!']);
        }
        $time = time();


        $user = User::where('id', $this->userId)->find();

        if (empty($user['trad_password'])) {
            return json(['code' => 1, 'msg' => '请先设置支付密码!']);
        }
        $pwd = input('post.trad_password');
        if (empty($pwd)) {
            return json(['code' => 1, 'msg' => '支付密码不能为空!']);
        }
        $service = new \app\common\service\Users\Service();
        $result = $service->checkPayPassword($pwd, $user);
        if (!$result) {
            return json(['code' => 1, 'msg' => '支付密码错误!']);
        }

        Db::startTrans();
        try {
            //判断支付类型
            if (empty($pay_type)) {
                $map['au.new_fid'] = ['>', 0];  //注释掉的
                $map['au.status'] = 3;//上传凭证的
                $map['fo.status'] = 1;//上传支付凭证
            } else {
                $map['au.status'] = 2;//直接交易
                $map['fo.status'] = 0;//上传支付凭
            }

            $map['au.oid'] = $oid;
            $map['f.is_show'] = 1;
            $map['f.is_delete'] = 0;

            //获取以及提交凭证没有提交申诉的用户
            $msglist = Db::table('appointment_user')
                ->alias('au')
                ->join('fish_order fo', 'au.id = fo.types', 'INNER')
                ->join('fish f', 'f.order_id = fo.id', 'INNER')
                ->join('bathing_pool bp', 'bp.id = f.pool_id', 'INNER')
                ->where($map)
                ->field('au.id,f.worth,f.front_id,f.types,f.front_worth,f.id f_id,f.is_re,f.u_id,fo.id fo_id,fo.worth fo_worth,fo.bu_id,au.new_fid,bp.status bpstatus,bp.num')
                ->find();

            if (empty($msglist)) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '订单已完成或已失效!', 'map' => $map]);
            }


            //如果是GTC支付则需要扣除GTC
            if (!empty($pay_type)) {

                $service = new \app\common\service\Fish\Service();
                $is_save = $service->BuyFishIndex($msglist['f_id'], $msglist['bu_id'], $msglist['fo_id']);
                if (!$is_save) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => '网络繁忙请重新提交!']);
                }
                Db::table('appointment_user')
                    ->where(['id' => $msglist['id']])
                    ->update(['new_fid' => $is_save, 'buy_time' => $time]);

                $msglist['new_fid'] = $is_save;

                $activation_num = Config::getValue('activation_num');

                $buyer_map['uid'] = $msglist['bu_id'];

                $bait = Db::table('my_wallet')
                    ->where($buyer_map)
                    ->value('now');


                if ($activation_num > $bait) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => "用户GTC小于{$activation_num}不可以转让GTC!"]);
                }

                if ($msglist['fo_worth'] > $bait) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => 'GTC不足!']);
                }

                $now = $bait - $msglist['fo_worth'];

                if ($activation_num > $now) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => "用户转让后剩余GTC小于{$activation_num}不可以转让GTC!"]);
                }


                //修改用户GTC

                if (!Db::table('my_wallet')->where('uid', $msglist['bu_id'])->setDec('now', $msglist['fo_worth'])) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => '操作失败!']);
                }

                //添加数据记录

                $add['uid'] = $msglist['bu_id'];
                $add['number'] = -$msglist['fo_worth'];
                $add['now'] = $bait;
                $add['remark'] = '抢购订单扣除';
                $add['types'] = 9;
                $add['create_time'] = $time;
                $add['future'] = $msglist['fo_worth'];
                $add['from_id'] = $msglist['f_id'];

                if (!Db::table('my_wallet_log')->insert($add)) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => '操作失败!']);
                }

                $user_map['uid'] = $msglist['u_id'];

                $bait = Db::table('my_wallet')
                    ->where($user_map)
                    ->value('now');

                //修改用户接收GTC
                $b_save['now'] = $bait + $msglist['fo_worth'];

                $is_my = Db::table('my_wallet')->where('uid', $msglist['u_id'])->setInc('now', $msglist['fo_worth']);
                $is_old = Db::table('my_wallet')->where('uid', $msglist['u_id'])->setInc('old', $msglist['fo_worth']);

                if (!$is_my || !$is_old) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => '操作失败!']);
                }


                $u_num = Db::table('my_wallet')->where('uid', $msglist['u_id'])->field('old')->find();
                $b_uactive = User::alias('u')
                    //->join('user_invite_code uic','uic.user_id = u.id')
                    //->where("uic.invite_code", $mobile)
                    ->where("u.id", $msglist['u_id'])
                    ->value('u.is_active');

                if ($u_num && $b_uactive == 0 && $u_num['old'] >= $activation_num) {

                    $bu_save['is_active'] = 1;
                    $bu_save['status'] = 1;
                    $bu_save['active_time'] = $time;
                    $bu_save['update_time'] = $time;
                    $res = User::where('id', $msglist['u_id'])->update($bu_save);

                };


                //添加数据记录

                $add['uid'] = $msglist['u_id'];
                $add['number'] = $msglist['fo_worth'];
                $add['now'] = $bait;
                $add['remark'] = '抢酒订单收入';
                $add['types'] = 7;
                $add['create_time'] = $time;
                $add['future'] = $b_save['now'];
                $add['from_id'] = $msglist['f_id'];

                if (!Db::table('my_wallet_log')->insert($add)) {
                    Db::rollback();
                    return json(['code' => 1, 'msg' => '操作失败!']);
                }

            }

            $au_up['status'] = 4; //转账完成
            $au_up['update_time'] = time();
            $au_up['okpay_time'] = time();
            $is_au = Db::table('appointment_user')->where('id', $msglist['id'])->update($au_up);
            if (!$is_au) {

                Db::rollback();
                return json(['code' => 1, 'msg' => '确认失败1!']);

            }

            $f_up['status'] = 4; //转账完成
            $f_up['update_time'] = $time;
            $f_up['buy_time'] = $time;
            $f_up['buy_types'] = 2;
            $is_f = Db::table('fish')->where('id', $msglist['f_id'])->update($f_up);
            if (!$is_f) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '确认失败2!']);
            }

            $nf_up['is_show'] = 1;//显示
            $nf_up['update_time'] = $time;

            $is_nf = Db::table('fish')->where('id', $msglist['new_fid'])->update($nf_up);

            if (!$is_nf) {

                Db::rollback();
                return json(['code' => 1, 'msg' => '确认失败3!']);

            }

            $fo_up['status'] = 2; //转账完成
            $fo_up['update_time'] = $time;

            $is_fo = Db::table('fish_order')->where('id', $msglist['fo_id'])->update($fo_up);
            if (!$is_fo) {

                Db::rollback();
                return json(['code' => 1, 'msg' => '确认失败4!']);

            }

            //添加积分以及记录
            $PublicModel = new  PublicModel;
            if ($msglist['types'] == 1 || $msglist['types'] == 2) {
                $PublicModel = new  PublicModel;
                //获取- 拆分升级祖级酒信息
                $is_f = $PublicModel->getPfishworth_num($msglist['f_id']);

                if ($is_f) {
                    $tmpworth = $is_f['worth'];
                    $num = $is_f['num'];
                    $tmpworth = bcdiv($tmpworth, $num, 2);
                } else {
                    return json(['code' => 1, 'msg' => '确认失败6!']);
                }

                if ($msglist['types'] == 1) {
                    //拆分

                    $f_worth0 = (int)$tmpworth;

                    if ($msglist['is_re']) {
                        $f_worth1 = Db::table('fish_order')->where('id', $msglist['fo_id'])->value('worth');
                    } else {
                        $f_worth1 = Db::table('fish')->where('id', $msglist['new_fid'])->value('worth');
                    }

                } else {
                    //升级
                    $f_worth0 = (int)$tmpworth;
                    if ($msglist['is_re']) {
                        $f_worth1 = Db::table('fish_order')->where('id', $msglist['fo_id'])->value('worth');
                    } else {
                        $f_worth1 = Db::table('fish')->where('id', $msglist['new_fid'])->value('worth');
                    }
                }

            } else {
                $f_worth0 = Db::table('fish')->where('id', $msglist['f_id'])->value('worth');
                $f_worth1 = Db::table('fish_order')->where('id', $msglist['fo_id'])->value('worth');
            }

            $user_worth = $f_worth1 - $f_worth0;
            $user_worth = (int)$user_worth;

            $is_add = $PublicModel->add_user_profit($msglist['u_id'], $user_worth, 3, $msglist['fo_id']);

            //dump($user_worth);exit;
            $entity = new \app\common\entity\MyWallet();
            if ($user_worth > 0) {
                $entity->bonusDispense($user_worth, $msglist['u_id'], 2, 1, 0, $msglist['fo_id']);//推广收益
                $entity->teamDispense($user_worth, $msglist['u_id'], 2, 1, 0, $msglist['fo_id']);//团队收益
            }
            if (!$is_add) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '确认失败5!']);
            }

            //exit;
            Db::commit();
            return json(['code' => 0, 'msg' => '操作成功!']);


        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '确认失败7!', 'error' => $e->getMessage()]);
        }

    }


//    public function turn(){
//
//        $uid = $this->userId;
//        $page = input('post.page')?input('post.page'):1;
//        $type = input('post.type')?input('post.type'):0;
//        $limit = input('post.limit')?input('post.limit'):15;
//
//        $map['f.u_id'] =$uid;// 所有者用户id
//        $map['f.is_delete'] =0;// 所有者用户id
//        if($type == 0){
//            $map['f.status'] = ['in','1,2'];// 等待预约
//            $map['f.order_id'] = 0;
//        }elseif ($type ==1){
//            $map['f.status'] = 3;// 转账中
//            $map['f.order_id'] = ['>',0];
//        }elseif($type == 2){
//            $map['f.status'] = 4;//完成
//        }else{
//            $map['f.status'] = -3;//
//        }
//
//
//        $list =  Db::table('fish')
//            ->alias('f')
//            ->join('bathing_pool bp','bp.id = f.pool_id')
//            ->join('user u','f.u_id = u.id')
//            ->where($map)
//            ->field('u.nick_name user_name,bp.name,f.id fid,f.worth,bp.profit,bp.bait,bp.contract_time,order_id')
//            ->page($page)
//            ->paginate($limit)
//            ->toArray();
//        if(empty($list)){
//            $list = array();
//        }else{
//            $list = $list['data'];
//            if($list){
//                foreach($list as $k =>$v){
//                    $list[$k]['order_number'] = '';
//                    $list[$k]['over_time'] = '';
//                    $list[$k]['buy_time'] = '';
//                    $list[$k]['user_name'] = '';
//                    if($v['order_id']>0){
//                        $is_fo = Db::table('fish_order')
//                            ->alias('fo')
//                            ->join('appointment_user au','au.id = fo.types')
//                            ->where('fo.id',$v['order_id'])
//                            ->field('fo.order_number,fo.over_time,au.buy_time,au.uid')
//                            ->find();
//                        if($is_fo){
//                            $list[$k]['order_number'] = $is_fo['order_number'];
//                            $list[$k]['over_time'] = $is_fo['over_time'];
//                            $list[$k]['buy_time'] = date('Y-m-d H:i:s',$is_fo['buy_time']);
//                            if($is_fo['uid']){
//                                $is_u = Db::table('user')->where('id',$is_fo['uid'])
//                                    ->field('nick_name')->find();
//                                if($is_u){
//                                    $list[$k]['user_name'] =$is_u['nick_name'];
//                                }
//                            }
//                        }
//                    }
//                }
//
//            }
//
//        }
//        return json(['code' => 0, 'msg' => '操作成功!','info' => $list]);
//    }
    /**
     * 申诉
     * @return \think\response\Json
     */
    public function appeal()
    {
        $fid = input('post.fid');
        if (!$fid) {
            return json(['code' => 1, 'msg' => '缺失参数!']);
        }
        $content = input('post.content');

        if (!$content) {
            return json(['code' => 1, 'msg' => '申诉内容不能为空!']);
        }

        $content = trim($content);
        $len = mb_strlen($content, 'UTF8');
        if ($len > 250) {
            return json(['code' => 1, 'msg' => '申诉内容不能大于250个字符!']);
        }
        $uid = $this->userId;

        $map['f.id'] = $fid;
        $map['f.order_id'] = ['>', 0];
        $is_fish = Db::table('fish')
            ->alias('f')
            ->join('user u', 'f.u_id = u.id')
            ->where($map)
            ->field('u.id uid,f.id fid,f.worth,f.order_id,f.status')
            ->find();


        if (!$is_fish) {
            return json(['code' => 1, 'msg' => '申诉失败!']);
        }
        if ($is_fish['status'] == -3) {
            return json(['code' => 1, 'msg' => '请勿重复提交!']);
        }


        Db::startTrans();
        try {

            $save['f.update_time'] = time();
            $save['f.status'] = -3;
            $is_save = Db::table('fish')
                ->alias('f')
                ->join('user u', 'f.u_id = u.id')
                ->where($map)
                ->update($save);
            if (!$is_save) {
                Db::rollback();

                return json(['code' => 1, 'msg' => '申诉失败!']);
            }

            $is_fo = Db::table('fish_order')
                ->where('id', $is_fish['order_id'])
                ->field('types')
                ->find();
            if (empty($is_fo)) {
                Db::rollback();

                return json(['code' => 1, 'msg' => '无效订单!']);
            }


            //冻结酒
            $is_au = Db::table('appointment_user')
                ->where('id', $is_fo['types'])
                ->field('new_fid')
                ->find();
            if (!$is_au) {
                Db::rollback();

                return json(['code' => 1, 'msg' => '无效订单!']);
            }

            $auup['status'] = -4;

            $is_auup = Db::table('appointment_user')
                ->where('id', $is_fo['types'])
                ->update($auup);
            if (!$is_auup) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '申诉失败']);
            }

            $fupsave['status'] = -1;
            $is_fup = Db::table('fish')->where('id', $is_au['new_fid'])->update($fupsave);
            if (!$is_fup) {
                Db::rollback();

                return json(['code' => 1, 'msg' => '申诉失败']);
            }

            $fosave['status'] = -1;

            $is_fosave = Db::table('fish_order')
                ->where('id', $is_fish['order_id'])
                ->update($fosave);

            if (!$is_fosave) {
                Db::rollback();

                return json(['code' => 1, 'msg' => '申诉失败!']);
            }

            $amap['order_id'] = $is_fish['order_id'];
            $amap['status'] = ['>', -1];
            $is_a = Db::table('appeal')->where($amap)->find();
            if ($is_a) {
                Db::rollback();

                return json(['code' => 1, 'msg' => '已提交申诉请求，如有疑问请与工作人员联系!']);
            }
            $add['create_time'] = time();
            $add['status'] = 0;
            $add['content'] = $content;
            $add['order_id'] = $is_fish['order_id'];
            $add['uid'] = $uid;
            $is_aadd = Db::table('appeal')->insert($add);


            if (!$is_aadd) {
                Db::rollback();

                return json(['code' => 1, 'msg' => '添加失败!']);
            }

            Db::commit();

            return json(['code' => 0, 'msg' => '操作成功!']);


        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '提交失败!']);
        }
    }


    /**
     * 取消申诉
     * @return \think\response\Json
     */
    public function cancel_appeal()
    {
        $fid = input('post.fid');
        if (!$fid) {
            return json(['code' => 1, 'msg' => '缺失参数!']);
        }
        $uid = $this->userId;

        $map['f.id'] = $fid;
        $map['f.order_id'] = ['>', 0];
//        $map['f.u_id'] = $uid;

        $is_fish = Db::table('fish')
            ->alias('f')
            ->join('user u', 'f.u_id = u.id')
            ->where($map)
            ->field('u.id uid,f.id fid,f.worth,f.order_id,f.status')
            ->find();


        if (!$is_fish) {
            return json(['code' => 1, 'msg' => '无效对象!']);
        }
        if ($is_fish['status'] != -3) {
            return json(['code' => 1, 'msg' => '无效对象!']);
        }


        Db::startTrans();
        try {


            $is_fo = Db::table('fish_order')
                ->where('id', $is_fish['order_id'])
                ->field('types')
                ->find();
            if (empty($is_fo)) {
                Db::rollback();

                return json(['code' => 1, 'msg' => '无效对象!']);
            }


            //恢复熊猫
            $is_au = Db::table('appointment_user')
                ->where('id', $is_fo['types'])
                ->field('new_fid,okpay_time')
                ->find();
            if (!$is_au) {
                Db::rollback();

                return json(['code' => 1, 'msg' => '无效对象!']);
            }
//           完成
            if ($is_au['okpay_time']) {
                $auup['status'] = 4;

                $save['f.status'] = 4;//完成转账

                $fupsave['status'] = 0;//新
                $fupsave['is_show'] = 1;//新

                $fosave['status'] = 2;//完成订单
            } else {
                $auup['status'] = 3;

                $save['f.status'] = 3;

                $fupsave['status'] = 0;//新

                $fosave['status'] = 1;
            }


            $is_auup = Db::table('appointment_user')
                ->where('id', $is_fo['types'])
                ->update($auup);
            if (!$is_auup) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '申诉取消失败']);
            }

            $save['f.update_time'] = time();


            $is_save = Db::table('fish')
                ->alias('f')
                ->join('user u', 'f.u_id = u.id')
                ->where($map)
                ->update($save);
            if (!$is_save) {
                Db::rollback();

                return json(['code' => 1, 'msg' => '取消失败!']);
            }


            $is_fup = Db::table('fish')->where('id', $is_au['new_fid'])->update($fupsave);
            if (!$is_fup) {
                Db::rollback();

                return json(['code' => 1, 'msg' => '取消失败!']);
            }

            $is_fosave = Db::table('fish_order')
                ->where('id', $is_fish['order_id'])
                ->update($fosave);

            if (!$is_fosave) {
                Db::rollback();

                return json(['code' => 1, 'msg' => '取消失败!']);
            }

            $amap['order_id'] = $is_fish['order_id'];
            $amap['status'] = 0;
            $asave['status'] = -2;
            $asave['update_time'] = time();

            $is_a = Db::table('appeal')->where($amap)->order('id desc')->find();

            if ($is_a['uid'] != $uid) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '非投诉方，不得取消!']);
            }


            $is_asave = Db::table('appeal')->where($amap)->update($asave);
            if (!$is_asave) {
                Db::rollback();
                return json(['code' => 1, 'msg' => '取消失败!']);
            }


            Db::commit();

            return json(['code' => 0, 'msg' => '操作成功!']);


        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '提交失败!']);
        }

    }


    /**
     * 酒详情
     * @return \think\response\Json
     */
    public function turn_msg()
    {
        $uid = $this->userId;

        $fid = input('post.fid');
        if (!$fid) {
            return json(['code' => 1, 'msg' => '缺失参数!']);
        }


        $map['f.id'] = $fid;


        $is_fish = Db::table('fish')
            ->alias('f')
            ->where($map)
            ->join('user u', 'f.u_id = u.id')
            ->join('bathing_pool bp', 'bp.id = f.pool_id')
            ->field('f.id fid,f.status,f.is_status,f.order_id,f.is_contract,f.worth,bp.bait,bp.name,bp.contract_time,bp.profit,bp.img,f.contract_overtime,f.is_lock_num,f.types')
            ->find();
        $oid = $is_fish['order_id'];

        $fstatus = $is_fish['status'];
        if ($oid) {
            $is_newfish = Db::table('fish_order')
                ->alias('fo')
                ->join('appointment_user au', 'au.id = fo.types')
                ->where('fo.id', $oid)
                ->field('au.new_fid,au.okpay_time')->find();
            $is_fish['is_show_lock'] = 0;

            //以领取 非冻结
            if ($is_newfish['new_fid']) {
                if ($fstatus == 4) {
                    $map['f.id'] = $is_newfish['new_fid'];
                    $is_fish = Db::table('fish')
                        ->alias('f')
                        ->where($map)
                        ->join('user u', 'f.u_id = u.id')
                        ->join('bathing_pool bp', 'bp.id = f.pool_id')
                        ->field('f.id fid,f.order_id,f.is_status,f.is_contract,f.worth,bp.bait,bp.name,bp.contract_time,bp.profit,bp.img,f.contract_overtime,f.is_lock_num,f.types')
                        ->find();
                    if ($is_fish) {
                        $is_fish['order_id'] = $oid;
                        $is_fish['is_show_lock'] = 1;
                    }


                }

            }
        }

        $contract_time = $is_fish['contract_time'];
        if ($is_fish['is_lock_num'] > 0) {
            $is_fish['is_ok_lock'] = 2;
        } elseif ($is_fish['is_lock_num'] == 0 && $is_fish['is_status'] == 0) {
            $is_fish['is_ok_lock'] = 1;

        } else {
            $is_fish['is_ok_lock'] = 0;
        }

        //非积分兑换 ， 交易生成不得锁仓

        if (in_array($is_fish['types'], [0, 1, 3, 6])) {
            $is_fish['is_ok_lock'] = 0;
        }


        unset($map);

        // 获取房子的英文名称
        $is_fish['en_name'] = getPoolEnName($is_fish['name']);
        $re_arr['fish_msg'] = $is_fish;


        $map['f.id'] = $fid;
        $map['fo.id'] = $oid;

        $is_pay = Db::table('fish_order')
            ->alias('fo')
            ->join('appointment_user au', 'fo.types = au.id')
            ->join('fish f', 'f.id = fo.f_id')
            ->join('user bu', 'au.uid = bu.id')
            ->join('user_invite_code buic', 'buic.user_id =  bu.id')
            ->where($map)
            ->field('fo.id foid,fo.worth,fo.create_time,f.u_id f_uid,bu.id user_id,buic.invite_code nick_name,bu.mobile,au.status,au.buy_time,au.card_id,au.pay_imgs,au.pre_endtime,fo.order_number,au.okpay_time')
            ->find();

//        if(1){
        if ($is_pay) {
//支付时间
            if ($is_pay['buy_time']) {
                $is_pay['buy_time'] = date('Y-m-d H:i:s', $is_pay['buy_time']);
            } else {
                $is_pay['buy_time'] = '未付款';
            }


            //      转让方信息
            $msg = Db::table('card')->where('id', $is_pay['card_id'])->field('types,imgs,account_num,bank_name,sub_branch,names')->find();

            $map['f.id'] = $fid;
            $re_time = $is_pay['pre_endtime'];

            if ($msg) {
                $msg['pre_endtime'] = $re_time;
            }

//超过两小时 影藏
            if ($is_pay['okpay_time']) {
                $show_times = $is_pay['okpay_time'] + (60 * 60 * 2);
                if ($show_times <= time()) {

                    $tmp_mobil = substr($is_pay['mobile'], 0, 4) . '***' . substr($is_pay['mobile'], -4, 4);
                    $is_pay['mobile'] = $tmp_mobil;

                }

            }


            $re_arr['turn_msg'] = $msg;


            // 购买方

            $re_arr['purchase_msg'] = $is_pay;

            $re_arr['order_number'] = $is_pay['order_number'];

            $turn_msg = [];
            if (!empty($is_pay['card_id'])) {
                $turn_msg = DB::table('card')->where('id', $is_pay['card_id'])->field('types,imgs,bank_name,account_num,sub_branch,names')->find();
            }


            if ($is_pay['f_uid']) {
                $tmp_user = Db::table('user')
                    ->alias('u')
                    ->join('user_invite_code uic', 'uic.user_id =  u.id')
                    ->where('u.id', $is_pay['f_uid'])
                    ->field('uic.invite_code nick_name,u.mobile')
                    ->find();
                if ($is_pay['okpay_time']) {
                    $show_times = $is_pay['okpay_time'] + (60 * 60 * 2);
                    if ($show_times <= time()) {

                        $tmp_mobil = substr($msg['account_num'], 0, 4) . '***' . substr($msg['account_num'], -4, 4);
                        $turn_msg['account_num'] = $tmp_mobil;
                        $tmp_mobil = substr($tmp_user['mobile'], 0, 4) . '***' . substr($tmp_user['mobile'], -4, 4);

                        $tmp_user['mobile'] = $tmp_mobil;

                    }

                }
                if ($tmp_user) {
                    if ($turn_msg) {
                        $turn_msg = array_merge($turn_msg, $tmp_user);
                    } else {
                        $turn_msg = $tmp_user;
                    }
                    $pay_time['buy_time'] = date('Y-m-d H:i:s', $is_pay['create_time']);
                    $turn_msg = array_merge($turn_msg, $pay_time);


                }

            }

            $re_arr['turn_msg'] = $turn_msg;

            $re = array();
            if ($is_pay['status'] == 1) {
                $re['status_name'] = '匹配中';
                $re['status'] = 1;

            } elseif ($is_pay['status'] == 2) {

                $re['status_name'] = '待付款';
                $re['status'] = 2;
                $re_arr['fish_msg']['worth'] = $is_pay['worth'];

                // 获取用户可用余额
                $user_balance = Db::table('my_wallet')->where('uid',$uid)->where('is_balance_extension',0)->value('now');
                $re['user_balance'] = $user_balance;
                // 获取支付期限(小时)
                $pay_limit_time = Db::table('config')->where('key', 'voucher_time')->value('value');
                // 剩余支付时间等于抢购结束时间加上支付时间再减去当前时间
                $pay_allow_time = $re_time + $pay_limit_time * 3600 -time();
                $re['pay_allow_time'] = $pay_allow_time;

            } elseif ($is_pay['status'] == 3) {
                $re['status_name'] = '待完成';
                $re['status'] = 3;


            } elseif ($is_pay['status'] == 4 || $is_pay['status'] == -4) {
                $re['status_name'] = '完成';
                if ($is_pay['status'] == 4) {

                    $re['status'] = 4;
                } else {
                    $re['status'] = -4;
                    $is_a = Db::table('appeal')->where('order_id', $is_pay['foid'])->field('status')->order('create_time desc')->find();
                    if ($is_a['status'] == -2) {
                        $re['appeal_status_name'] = '申诉取消';
                        $re['appeal_status'] = -2;

                    } elseif ($is_a['status'] == -1) {
                        $re['appeal_status_name'] = '申诉驳回';
                        $re['appeal_status'] = -1;

                    } elseif ($is_a['status'] == 0) {
                        $re['appeal_status_name'] = '申诉中';
                        $re['appeal_status'] = -4;

                    } elseif ($is_a['status'] == 1) {
                        $re['appeal_status_name'] = '申诉通过';
                        $re['appeal_status'] = -3;
                    }

                }


            }
            if ($re) {
                $re_arr['pay_type'] = $re;
            } else {
                $re_arr['pay_type'] = '';
            }


        } else {
            $re_arr['turn_msg'] = '';
            $re_arr['purchase_msg'] = '';
            $re_arr['order_number'] = '';
            $re_arr['pay_type'] = '';
        }


        if (empty($re_arr)) {
            return json(['code' => 1, 'msg' => '无效数据!']);
        }

        return json(['code' => 0, 'msg' => 'access!', 'info' => $re_arr]);
    }

    /**
     * 预约记录
     * @return \think\response\Json
     */
    public function make_record()
    {
        $uid = $this->userId;
        $page = input('post.page') ? input('post.page') : 1;
        $limit = input('post.limit') ? input('post.limit') : 15;
        $map['uid'] = $uid;
        $list = Db::table('appointment_user')
            ->alias('au')
            ->join('bathing_pool bp', 'bp.id = au.pool_id')
            ->where($map)
            ->field('bp.name,bp.profit,bp.bait,bp.contract_time,au.status,au.status,au.create_time,au.bait')
            ->order('au.create_time desc')
            ->page($page)
            ->paginate($limit)
            ->toArray();
        if (empty($list)) {
            $list = array();
        } else {
            $list = $list['data'];

            foreach ($list as $k => $v) {
                if ($v['status'] == -1) {
                    $list[$k]['bait'] = abs($v['bait']);
                }
                $list[$k]['status'] = $this->getAuType($v['status']);
                $list[$k]['create_time'] = date('Y-m-d H:i:s', $list[$k]['create_time']);
            }
        }
        return json(['code' => 0, 'msg' => '操作成功!', 'info' => $list]);
    }

    public function getAuType($type)
    {
        switch ($type) {
            case -3:
                return '未及时支付';
            case -2:
                return '预约未领取';
            case -1:
                return '领取抢购失败(返回)';
            case 0:
                return '已预约';
            case 1:
                return '待领取';
            case 2:
                return '待支付';
            case 3:
                return '待用户确认收款';
            case 4:
                return '领取成功';
            default:
                return $type;
        }
    }

    /**
     * 修改支付密码
     */
    public function changePyaSave(Request $request)
    {
        $uid = $this->userId;

        $is_user = User::checkId($uid);

        //判断手机号码是否注册
        if (!$is_user) {
            return json(['code' => 1, 'msg' => '此账号不存在']);
        }
        $phone_code = $request->post('phone_code');
        if (empty($phone_code)) {
            return json(['code' => 1, 'msg' => '验证码不能为空！']);

        }

        $new_pwd = $request->post("pay_pwd"); //新密码


        if (strlen($new_pwd) < 6) {
            return json(['code' => 1, 'msg' => '密码长度至少6位']);
        }


        $service = new Service();
        $form = new RegisterForm();
        if (!$form->checkPayChange($request->post('phone_code'), $is_user->mobile)) {
            return json(['code' => 1, 'msg' => '验证码输入错误']);
        }

        $res = User::where("id", $uid)->update(["trad_password" => $service->getPassword($new_pwd), 'update_time' => time()]);

        if ($res) {
            return json(['code' => 0, 'msg' => '密码修改成功']);
        } else {
            return json(['code' => 1, 'msg' => '密码修改失败']);
        }
    }


    /**
     * 邀请用户
     * @return \think\response\Json
     */
    public function InvitationList()
    {

        $uid = $this->userId;
        $page = input('post.page') ? input('post.page') : 1;
        $type = input('post.type') ? input('post.type') : 0;
        $limit = input('post.limit') ? input('post.limit') : 15;
        $map['u.pid'] = $uid;
        if ($type == 1) {
            $map['u.is_active'] = 1;
        } elseif ($type == 2) {
            $map['u.is_active'] = 0;
        }

        // 获取团队长id
        $userModel = new UserModel;
        $team_user_id = $userModel->getTeamLeader($uid);
        $team_code = Db::table('user_invite_code')->where('user_id', $team_user_id)->value('invite_code');
        $list = Db::table('user')->alias('u')
            ->join('user_invite_code uic', 'uic.user_id = u.id')
            ->where($map)
            ->field('u.nick_name,u.register_time,u.mobile,uic.invite_code user_code,'. $team_code . ' team_code')
            ->page($page)
            ->paginate($limit)
            ->toArray();
        if (empty($list)) {
            $list = array();
        } else {
            $list = $list['data'];
            foreach ($list as $k => $v) {
                $list[$k]['register_time'] = date('Y-m-d H:i:s', $v['register_time']);
            }


        }

        return json(['code' => 0, 'msg' => '操作成功', 'info' => $list]);


    }


    /**
     * 推广兑换酒馆
     */
    public function integral_pool()
    {
        $uid = $this->userId;
        $map['id'] = $uid;

        $type = input('post.types') ? input('post.types') : 1;
        $is_u = Db::table('user')
            ->where($map)
            ->field('now_prohibit_integral,now_team_integral')
            ->find();
        // 团队
        if ($type == 1) {
            if (!$is_u['now_team_integral']) {
                $integral = 0;
            } else {
                $integral = $is_u['now_team_integral'];
            }
        } elseif ($type == 2) {
            if (!$is_u['now_prohibit_integral']) {
                $integral = 0;
            } else {
                $integral = $is_u['now_prohibit_integral'];
            }
        }
        unset($map);
        $map['is_integral'] = $type;
        $is_pool = Db::table('bathing_pool')->where($map)->field('integral,name,id')->find();

        if (!$is_pool) {
            $map['is_integral'] = 3;
            $is_pool = Db::table('bathing_pool')->where($map)->field('integral,name,id')->find();
        }

        if ($integral && $is_pool['integral']) {
            $num = $integral / $is_pool['integral'];
            $num = floor($num);
        } else {
            $num = 0;
        }

        if ($is_pool) {
            $is_pool['num'] = $num;
//            $is_pool['num'] = 9;
        }
        return json(['code' => 0, 'msg' => '操作成功', 'info' => $is_pool]);
    }

    /**
     * 积分兑换酒
     * @return \think\response\Json
     */
    public function buy_integral_fish()
    {
        $uid = $this->userId;

        $type = input('post.types') ? input('post.types') : 1;

        $num = input('post.num');
        $num = trim($num);

        if (empty($num)) {
            return json(['code' => 0, 'msg' => '兑换数量错误']);
        }
        if ($num <= 0) {
            return json(['code' => 0, 'msg' => '兑换数量错误']);
        }

        $uid = $this->userId;
        $map['id'] = $uid;
        $is_u = Db::table('user')
            ->where($map)
            ->find();
        unset($map);

//        团队
        if ($type == 1) {
            if ($is_u['is_prohibitteam'] == 1) {

                return json(['code' => 0, 'msg' => '团队积分处于冻结状态，禁止兑换！']);
            }
            if (!$is_u['now_team_integral']) {
                $integral = 0;
            } else {
                $integral = $is_u['now_team_integral'];

            }
        } elseif ($type == 2) {
            if ($is_u['is_prohibit_extension'] == 1) {

                return json(['code' => 0, 'msg' => '推广积分处于冻结状态，禁止兑换！']);
            }
            if (!$is_u['now_prohibit_integral']) {
                $integral = 0;
            } else {
                $integral = $is_u['now_prohibit_integral'];
            }

        }

        $map['is_integral'] = $type;
        $is_pool = Db::table('bathing_pool')->where($map)->find();


        if (!$is_pool) {
            $map['is_integral'] = 3;
            $is_pool = Db::table('bathing_pool')->where($map)->find();
        }
        if (empty($is_pool)) {
            return json(['code' => 0, 'msg' => '无效酒馆']);
        }


        if ($integral) {
            $get_num = $integral / $is_pool['integral'];
            $get_num = floor($get_num);
        } else {
            $get_num = 0;
        }

        if (!$get_num) {
            return json(['code' => 0, 'msg' => '可兑换数量不充足']);
        }

        if ($get_num < $num) {
            return json(['code' => 0, 'msg' => '超过最大可兑换数量']);
        }

        $reduce = $num * $is_pool['integral'];//减少的积分

        $p_id = $is_pool['id'];
        $service = new \app\common\service\Fish\Service();
        $is_save = $service->add_buy_integral_fish($p_id, $uid, $num, $type, $reduce);
        if (!$is_save) {
            return json(['code' => 0, 'msg' => '兑换失败']);
        }

        return json(['code' => 0, 'msg' => '操作成功']);
    }


}