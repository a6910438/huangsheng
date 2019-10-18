<?php

namespace app\admin\controller;

use app\common\entity\Deposit;
use app\common\entity\Dynamic_Log;
use app\common\entity\StoreLog;
use app\common\entity\User;
use app\common\entity\MyWalletLog;
use app\common\entity\MyWallet;
use app\common\entity\MyGcLog;
use app\common\entity\Prohibit;
use app\common\entity\TakeMoneyLog;
use app\common\entity\RechargeLog;
use app\common\entity\BillLog;
use app\common\entity\ActiveLog;
use app\common\entity\BigWithdra;
use app\common\entity\Withdraw;
use app\common\entity\Match;
use app\common\entity\GcWithdrawLog;
use app\common\entity\OvertimeLog;
use app\common\entity\Config;
use service\LogService;
use think\Request;
use think\Db;
use think\Log;
/* 短信通知 */

use app\common\model\SendSms;

class Money extends Admin
{
    /**
     * @power 财务管理|会员钱包管理(GTC)
     * @rank 4
     */
    public function wallet(Request $request)
    {
        $entity = MyWallet::alias('mw')
            ->field('mw.*,u.id as uid,u.nick_name ,u.status as ustatus');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('nick_name', 'like', '%' . $keyword . '%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime && $endTime) {
            $entity->where('mw.update_time', '<', strtotime($endTime))
                ->where('mw.update_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->leftJoin('user u', 'u.id = mw.uid')
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ]);
        $query = new User();
        return $this->render('wallet', [
            'list' => $list,
            'query' => $query,
        ]);
    }

    /**
     * @power 财务管理|余额记录
     * @rank 4
     */
    public function rechargeLog(Request $request)
    {

        $entity = MyWalletLog::alias('rl')
            ->field('rl.*,u.id as uid,u.nick_name ,u.status as ustatus');

        if ($types = $request->get('types')) {
            $entity->where('rl.types', $types);
            $map['types'] = $types;
        }
        $numtypes = $request->get('numtypes');

        if ($numtypes == 1) {
            $entity->where('rl.number', '>', 0);
        } elseif ($numtypes == 2) {
            $entity->where('rl.number', '<', 0);
        }
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('nick_name', 'like', '%' . $keyword . '%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime && $endTime) {
            $entity->where('rl.create_time', '<', strtotime($endTime))
                ->where('rl.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->leftJoin('user u', 'u.id = rl.uid')
            ->order('create_time', 'desc')
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ]);
        $query = new User();
        return $this->render('rechargeLog', [
            'list' => $list,
            'query' => $query,
            'types' => (new RechargeLog())->getAllType(),
        ]);
    }


    /**
     * @power 财务管理|人工扣款记录
     * @rank 4
     */
    public function takeMoneyLog(Request $request)
    {
        $entity = TakeMoneyLog::alias('tml')
            ->field('tml.*,u.id as uid,u.nick_name ,u.status as ustatus');

        if ($types = $request->get('types')) {
            $entity->where('tml.types', $types);
            $map['types'] = $types;
        }
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('nick_name', 'like', '%' . $keyword . '%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime && $endTime) {
            $entity->where('tml.create_time', '<', strtotime($endTime))
                ->where('tml.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->leftJoin('user u', 'u.id = tml.uid')
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ]);
        $query = new User();
        return $this->render('takeMoneyLog', [
            'list' => $list,
            'query' => $query,
            'types' => (new TakeMoneyLog())->getAllType(),
        ]);
    }

    /**
     * @power 财务管理|人工充值
     * @rank 4
     */
    public function recharge(Request $request)
    {
        //GTC充值
        $my_wallet_log = DB::table('my_wallet_log')
            ->alias('ml')
            ->field('u.nick_name,ml.number,ml.remark,ml.create_time,uic.invite_code')
            ->leftJoin('user u', 'u.id=ml.uid')
            ->join('user_invite_code uic', 'uic.user_id = u.id')
            ->where('types', 1)
            ->where('number', '>', 0)
            ->whereTime('create_time', 'today')
            ->order('create_time', 'desc');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $my_wallet_log->where('u.mobile', 'like', '%' . $keyword . '%');
                    break;
                case 'ids':
                    $my_wallet_log->where('uic.invite_code', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $data = $my_wallet_log->paginate(15, false, [

        ]);
        //推广充值
        $data2 = DB::table('prohibit_log')
            ->alias('ml')
            ->field('u.nick_name,ml.number,ml.createtime,uic.invite_code')
            ->join('user_invite_code uic', 'uic.user_id = ml.uid')
            ->where('type', 3)
            ->where('number', '>', 0)
            ->whereTime('createtime', 'today')
            ->order('createtime', 'desc')
            ->leftJoin('user u', 'u.id=ml.uid')
            ->paginate(15, false, [

            ]);

        if ($request->isGet()) {
            return $this->render('recharge', [
                'list' => $data,
                'list2' => $data2
            ]);
        }
        if ($request->isPost()) {

            $result = $this->validate($request->post(), 'app\admin\validate\Recharge');
            if (true !== $result) {
                return json()->data(['code' => 1, 'message' => $result]);
            }

            $is_user = Db::table('user')
                ->alias('u')
                ->join('user_invite_code uic', 'uic.user_id = u.id')
                ->where('u.mobile|uic.invite_code', $request->post('uid'))
                ->field('u.*')
                ->find();
//            echo Db::table('user')->getLastSql();
//            dump($is_user);exit;
            if (empty($is_user)) {

                $this->error('无该用户');
            }
            $query = MyWallet::where('uid', $is_user['id'])->find();
            if (empty($query)) {
                $this->error('无效id');


            }
            $data = $request->post();
            $data['uid'] = $is_user['id'];

            if ($data['case_type'] == 1) {
                $data['type'] = 1;
                $res = $query->RechargeLog($query, $data);
            } else {
                $query = new Prohibit();
                $data['type'] = 3;
                $res = $query->RechargeLog($data);
            }
            $num = Db::table('my_wallet')->where('uid', $is_user['id'])->field('old')->find();
            if ($num && $is_user['is_active'] == 0) {
                $switch = Config::getValue('activation_num');

                if ($num['old'] >= $switch) {
                    $save['is_active'] = 1;
                    $save['status'] = 1;
                    $save['active_time'] = time();
                    $save['update_time'] = time();
                    User::where('id', $is_user['id'])->update($save);
                }
            }

            if ($res) {
                LogService::write('财务管理', '用户人工充值');

                $this->success('充值成功');


//                return json(['code' => 0, 'msg' => '充值成功', 'toUrl' => url('Money/recharge')]);
            }
            $this->error('充值失败');

        }

    }

    /**
     * @power 财务管理|人工扣款
     * @rank 4
     */
    public function takeMoney(Request $request)
    {
        $where = array();

        //GTC扣款
        $my_wallet_log = DB::table('my_wallet_log')
            ->alias('ml')
            ->field('u.nick_name,ml.number,ml.remark,ml.create_time,uic.invite_code')
            ->leftJoin('user u', 'u.id=ml.uid')
            ->where('types', 1)
            ->join('user_invite_code uic', 'uic.user_id = u.id')
            ->where('number', '<', 0)
            ->whereTime('create_time', 'today')
            ->order('create_time', 'desc');


        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $my_wallet_log->where('u.mobile', 'like', '%' . $keyword . '%');
                    break;
                case 'ids':
                    $my_wallet_log->where('uic.invite_code', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $data = $my_wallet_log->paginate(15, false, [

        ]);


        //推广扣款

        $data2 = DB::table('prohibit_log')
            ->alias('ml')
            ->field('u.nick_name,ml.number,createtime')
            ->where('type', 3)
            ->where('number', '<', 0)
            ->whereTime('createtime', 'today')
            ->order('createtime', 'desc')
            ->leftJoin('user u', 'u.id=ml.uid')
            ->paginate(15, false, [

            ]);


        if ($request->isGet()) {
            return $this->render('takeMoney', [
                'list' => $data,
                'list2' => $data2
            ]);
        }
        if ($request->isPost()) {

            $result = $this->validate($request->post(), 'app\admin\validate\Recharge');
            if (true !== $result) {
                return json()->data(['code' => 1, 'message' => $result]);
            }
            $query = MyWallet::where('uid', $request->post('uid'))->find();


            $is_user = Db::table('user')
                ->alias('u')
                ->join('user_invite_code uic', 'uic.user_id = u.id')
                ->where('u.mobile|uic.invite_code', $request->post('uid'))
                ->field('u.*')
                ->find();
            if (empty($is_user)) {
//                return json(['code'=>1,'message'=>'无该用户！']);
                $this->error('无该用户');
            }
            $query = MyWallet::where('uid', $is_user['id'])->find();
            if (empty($query)) {
//                return json(['code'=>1,'message'=>'无效id！']);
                $this->error('无效id');

            }
            $data = $request->post();
            $data['uid'] = $is_user['id'];

            // $data['type'] = 1;

            $data['num'] = -$data['num'];
            if ($data['case_type'] == 1) {
                $data['type'] = 1;
                $edit_data['now'] = $query['now'] - $data['num'];
                if ($edit_data['now'] < 0) {
//                    return json(['code'=>1,'message'=>'余额不足！']);
                    $this->error('余额不足');

                }
                $res = $query->RechargeLog($query, $data);
            } else {
                $info = DB::table('user')->where('id', $data['uid'])->find();
                $info['prohibit_integral'] = $info['prohibit_integral'] - $data['num'];
                if ($info['prohibit_integral'] < 0) {
//                    return json(['code'=>1,'message'=>'收益不足！']);
                    $this->error('收益不足');

                }
                $query = new Prohibit();
                $data['type'] = 3;


                $res = $query->RechargeLog($data);
            }

            if ($res) {
                LogService::write('财务管理', '用户人工扣款');

//                return json(['code' => 0, 'message' => '扣款成功', 'toUrl' => url('Money/recharge')]);
                $this->success('扣款成功');

            }
//            return json(['code'=>1,'message'=>'扣款失败！']);
            $this->error('扣款失败');

        }
    }

    /** 删
     *
     * @power 财务管理|排单币管理
     * @rank 4
     */
    public function lineAdmin(Request $request)
    {
        $entity = BillLog::alias('bl')
            ->field('bl.*,u.id as uid,u.nick_name ,u.status as ustatus');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('nick_name', 'like', '%' . $keyword . '%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime && $endTime) {
            $entity->where('bl.create_time', '<', strtotime($endTime))
                ->where('bl.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->leftJoin('user u', 'u.id = bl.uid')
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ]);
        $query = new User();
        return $this->render('lineAdmin', [
            'list' => $list,
            'query' => $query,
        ]);
    }

    /**
     * @power 财务管理|激活币管理
     * @rank 4
     */
    public function activeAdmin(Request $request)
    {
        $entity = ActiveLog::alias('al')
            ->field('al.*,u.id as uid,u.nick_name ,u.status as ustatus');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('nick_name', 'like', '%' . $keyword . '%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime && $endTime) {
            $entity->where('al.create_time', '<', strtotime($endTime))
                ->where('al.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->leftJoin('user u', 'u.id = al.uid')
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ]);
        $query = new User();
        return $this->render('activeAdmin', [
            'list' => $list,
            'query' => $query,
        ]);
    }

    /**
     * @power 财务管理|大额转让警报列表
     * @rank 4
     */
    public function big(Request $request)
    {


        $big = Config::getValue('team_withdraw_num');
        $big = empty($big) ? 0 : $big;
        $entity = Db::table('my_wallet_log')->alias('mwl')
            ->where('mwl.number', '>=', '0')
            ->where('mwl.types', 5)
            ->where('mwl.number', '>=', $big)
            ->field('mwl.*,u.id as uid,u.nick_name ,u.status as ustatus,bu.nick_name bnick_name');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('u.nick_name', 'like', '%' . $keyword . '%');
                    break;
                case 'ids':
                    $entity->where('uic.invite_code', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime || $endTime) {
            if (empty($startTime)) {
                $startTime = time();
            }
            if (empty($endTime)) {
                $endTime = time();
            }
            $entity->where('mwl.create_time', '<', strtotime($endTime))
                ->where('mwl.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->leftJoin('user u', 'u.id = mwl.uid')
            ->join('user_invite_code uic', 'uic.user_id  = u.id')
            ->leftJoin('user bu', 'bu.id = mwl.from_id')
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ]);
        $query = new User();
        $entry = new Withdraw();
        return $this->render('big', [
            'list' => $list,
            'query' => $query,
            'entry' => $entry,
        ]);
    }

    /**
     * @power 团队管理|超时支付警报列表
     * @rank 4
     */
    public function overtime(Request $request)
    {

        $entity = Db::table('appointment_user')
            ->alias('au')
            ->leftJoin('user u', 'u.id = au.uid')
            ->leftJoin('fish_order fo', 'fo.id = au.oid')
            ->leftJoin('fish f', 'f.id = fo.f_id')
            ->leftJoin('user fu', 'fu.id = f.u_id')
            ->join('user_invite_code uic', 'uic.user_id  = u.id');


        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'number':
                    $entity->where('fo.order_number', $keyword);
                    break;
                case 'ids':
                    $entity->where('uic.invite_code', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $list = $entity->field('au.id,au.create_time,au.buy_time,au.status astatus,au.pay_imgs,u.id as uid,u.nick_name ,u.status as ustatus,fo.order_number,fu.status as f_ustatus,fu.nick_name f_nick_name,fo.worth money')
            ->where('au.new_fid', '>', 0)
            ->where('au.status', -3)
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ]);

        $query = new User();

        return $this->render('overtime', [
            'list' => $list,
            'query' => $query,

        ]);
    }

    /**
     * @power 财务管理|动态收益明细
     * @rank 4
     */
    public function dynamic_log(Request $request)
    {


//        推广积分
        if ($request->get('selects') == 2) {
            $entry = Db::table('prohibit_log')->alias('l');
            $map['selects'] = 2;
        } else {
            $entry = Db::table('team_log')->alias('l');
        }

        $entry->leftJoin('user u', 'u.id = l.uid')
            ->leftJoin('user_invite_code uic', 'uic.user_id  = l.uid');
        $keyword = $request->get('keyword');
        if (!empty($keyword)) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entry->where('u.nick_name', 'like', '%' . $keyword . '%');
                    break;
                case 'ids':
                    $entry->where('uic.invite_code', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        if ($status = $request->get('status')) {
            $entry->where('l.status', $status);
            $map['status'] = $status;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime) {
            $entry->where('l.createtime', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
        }
        if ($endTime) {
            $entry->where('l.createtime', '<', strtotime($endTime));
            $map['endTime'] = $endTime;
        }
        $list = $entry
            ->leftJoin('user bu', 'bu.id = l.source_id')
            ->field('l.old,l.new,l.createtime,l.number,l.status,l.id,u.nick_name,bu.nick_name form_user,u.is_prohibit_extension,u.is_prohibitteam')
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ]);


        return $this->render('dynamic_log', [
            'list' => $list,

        ]);
    }


    public function make(Request $request)
    {


        $big = Config::getValue('overtime_warn');
        $big = empty($big) ? 0 : $big;
        $btime = 2 * 24 * 360;
        $time = time();
        $time = $time - $btime;
        $entity = Db::table('user')->alias('u')
            ->field('u.id as uid,u.nick_name ,u.status as ustatu,u.make_time,uic.invite_code ');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('u.nick_name', 'like', '%' . $keyword . '%');
                    break;
                case 'ids':
                    $entity->where('uic.invite_code', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime || $endTime) {
            if (empty($startTime)) {
                $startTime = time();
            }
            if (empty($endTime)) {
                $endTime = time();
            }
            $entity->where('u.make_time', '<', strtotime($endTime))
                ->where('u.make_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->join('user_invite_code uic', 'uic.user_id  = u.id')
            ->where('u.make_time', '<=', $time)
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ]);
        $query = new User();
        return $this->render('make', [
            'list' => $list,
            'query' => $query,

        ]);
    }

    /**
     * @power 财务管理|储存收益明细
     * @rank 4
     */
    public function store_log(Request $request)
    {
        $entry = StoreLog::alias('sl')
            ->field('sl.*,u.nick_name ,u.status as ustatus');
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entry->where('u.nick_name', 'like', '%' . $keyword . '%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        if ($status = $request->get('status')) {
            $entry->where('sl.status', $status);
            $map['status'] = $status;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime && $endTime) {
            $entry->where('sl.create_time', '<', strtotime($endTime))
                ->where('sl.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entry
            ->leftJoin('user u', 'u.id = sl.uid')
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ]);
        $query = new User();
        return $this->render('store_log', [
            'list' => $list,
            'query' => $query,
        ]);
    }

    //团队大额提现警报设置
    public function team_withdraw_set()
    {
        return $this->render('team_withdraw_set', [
            'list' => \app\common\entity\Config::where('type', 1)->where('name', 'like', '%警%')->where('status', 1)->select()
        ]);
    }

    //会员账变
    public function user_wallet_change(Request $request)
    {
        $log_type = $request->param('logtypes');
        $map['logtypes'] = $log_type;
        $where = array();
        $entity = MyWalletLog::alias('rl')
            ->field('rl.*,u.id as uid,u.nick_name ,u.status as ustatus');

        if ($types = $request->get('types')) {
            $entity->where('rl.types', $types);
            $where = ['rl.types' => $type];
            $map['types'] = $types;
        }
        $numtypes = $request->get('numtypes');
        if ($numtypes == 1) {
            $entity->where('rl.number', '>', 0);
            $where = ['rl.number' => ['>', 0]];
        } elseif ($numtypes == 2) {
            $entity->where('rl.number', '<', 0);
            $where = ['rl.number' => ['<', 0]];
        }
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('nick_name', 'like', '%' . $keyword . '%');
                    $where = ['nick_name' => ['like', '%' . $keyword . '%']];
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime && $endTime) {
            $entity->where('rl.create_time', '<', strtotime($endTime))
                ->where('rl.create_time', '>=', strtotime($startTime));
            $where = ['rl.create_time' => ['<', strtotime($endTime)], 'rl.create_time' => ['>=', strtotime($startTime)]];
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        switch ($log_type) {
            case 1:
                //GTC账变记录
                $list = $entity
                    ->leftJoin('user u', 'u.id = rl.uid')
                    ->order('create_time', 'desc')
                    ->where($where)
                    ->paginate(15, false, [
                        'query' => $request->param() ? $request->param() : [],
                    ]);
                $log = 1;
                break;
            case 2:
                //卖酒收益
                $list = Db::table('fish_order')->alias('fo')
                    ->where('fo.types', '>', 0)
                    ->join('user u', 'u.id = fo.bu_id')
                    ->field('u.nick_name,u.status as ustatus,fo.worth as future,fo.update_time as create_time,fo.*')
                    ->paginate(15, false, [
                        'query' => $request->param() ? $request->param() : [],
                    ])
                    ->each(function ($item, $key) {
                        $before_id = Db::table('fish')->where('id', $key['f_id'])->value('front_id');
                        $item['now'] = Db::table('fish')->where('id', $before_id)->value('worth');
                        $item['number'] = $item['future'] - $item['now'];
                    });
                $log = 2;
                break;
            case 3:
                //推广收益
                $list = Db::table('prohibit_log')->alias('pl')
                    ->join('user u', 'u.id = pl.uid')
                    ->field('pl.old as now,pl.new as future,pl.createtime as create_time,pl.number,u.nick_name as nick_name,u.status as ustatus')
                    ->paginate(15, false, [
                        'query' => $request->param() ? $request->param() : [],
                    ])
                    ->each(function ($item, $key) {
                        $item['remark'] = '推广收益';
                    });
                $log = 3;
                break;
            case 4:
                //团队收益
                $list = Db::table('team_log')->alias('pl')
                    ->join('user u', 'u.id = pl.uid')
                    ->field('pl.old as now,pl.new as future,pl.createtime as create_time,pl.number,u.nick_name as nick_name,u.status as ustatus')
                    ->paginate(15, false, [
                        'query' => $request->param() ? $request->param() : [],
                    ])
                    ->each(function ($item, $key) {
                        $item['remark'] = '团队收益';
                    });
                $log = 4;
                break;
            default:
                //GTC账变记录
                $list = $entity
                    ->leftJoin('user u', 'u.id = rl.uid')
                    ->order('create_time', 'desc')
                    ->where($where)
                    ->paginate(15, false, [
                        'query' => $request->param() ? $request->param() : [],
                    ]);
                $log = 1;
        }


        $query = new User();
        return $this->render('user_wallet_change', [
            'list' => $list,
            'query' => $query,
            'log' => $log,
            'types' => (new RechargeLog())->getAllType(),
        ]);
    }

    //会员收益
    public function user_profit(Request $request)
    {
        $where = array();
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $where = ['nick_name' => $keyword];
                    break;
                case 'ids':
                    $where['uic.invite_code'] = $keyword;
                    break;
                case 'order_number':
                    $where['fo.order_number'] = $keyword;
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime || $endTime) {
            if (empty($startTime)) {
                $startTime = time();
            }
            if (empty($endTime)) {
                $endTime = time();
            }
            // $where = ['au.okpay_time' => ['<', strtotime($endTime)], 'au.okpay_time' => ['>=', strtotime($startTime)]];
            $where['au.okpay_time'] = ['between', [strtotime($startTime), strtotime($endTime)]];
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $question_list = Db::table('fish_order')->alias('fo')
            ->join('user u', 'u.id = fo.bu_id')
            ->join('fish f', 'f.id = fo.f_id')
            ->join('bathing_pool bp', 'bp.id = f.pool_id')
            ->join('appointment_user au', 'au.id = fo.types')
            ->join('user_invite_code uic', 'uic.user_id  = u.id')
            ->where($where)
            ->where('fo.types', '>', 0)
            ->where('fo.status', 2)
            ->field('u.nick_name,u.status as status,fo.worth as future,fo.update_time as create_time,fo.id,f.worth now,f.types,fo.order_number,bp.name,au.okpay_time')
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ]);
        $list = $question_list->items();
        foreach ($list as $k => $v) {
            $is_num = get_fish_order_worth($v['id']);

            if ($is_num) {
                $list[$k]['future'] = $is_num['now'];
                $list[$k]['now'] = $is_num['old'];
            }
        }

        $query = new User();
        return $this->render('user_profit', [
            'question_list' => $question_list,
            'list' => $list,
            'query' => $query
        ]);
    }

    //团队长收益
    public function team_profit(Request $request)
    {
        $where = array();
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $where = ['nick_name' => ['like', '%' . $keyword . '%']];
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime && $endTime) {
            $where = ['rl.create_time' => ['<', strtotime($endTime)], 'rl.create_time' => ['>=', strtotime($startTime)]];
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = Db::table('fish_order')->alias('fo')
            ->join('user u', 'u.id = fo.bu_id')
            ->join('team t', 't.leader = u.id')
            ->where('fo.types', '>', 0)
            ->where($where)
            ->field('u.nick_name,u.status as status,fo.worth as future,fo.update_time as create_time,fo.*')
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ])
            ->each(function ($item, $key) {
                $before_id = Db::table('fish')->where('id', $key['f_id'])->value('front_id');
                $item['now'] = Db::table('fish')->where('id', $before_id)->value('worth');
                $item['number'] = $item['future'] - $item['now'];
            });

        $query = new User();
        return $this->render('team_profit', [
            'list' => $list,
            'query' => $query
        ]);

    }

    //团队大额提现列表
    public function team_withdraw_list(Request $request)
    {
        $team_withdraw_num = Config::where('key', 'team_withdraw_num')->value('value');
        $entity = MyWalletLog::alias('rl')
            ->field('rl.*,u.id as uid,u.nick_name ,u.status as ustatus');

        if ($types = $request->get('types')) {
            $entity->where('rl.types', $types);
            $map['types'] = $types;
        }
        $numtypes = $request->get('numtypes');

        if ($numtypes == 1) {
            $entity->where('rl.number', '>', 0);
        } elseif ($numtypes == 2) {
            $entity->where('rl.number', '<', 0);
        }
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('nick_name', 'like', '%' . $keyword . '%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime && $endTime) {
            $entity->where('rl.create_time', '<', strtotime($endTime))
                ->where('rl.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->leftJoin('user u', 'u.id = rl.uid')
            ->where('u.tid', '>', 0)
            ->group('u.tid')
            ->field('SUM(rl.number) as total,u.tid as tid,rl.remark')
            ->where('rl.types', 5)
            ->where('rl.number', '<', 0)
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ])
            ->each(function ($item, $key) {
                $item['team_name'] = Db::table('user')->where('id', $item['tid'])->value('nick_name');
                $item['total'] = abs($item['total']);
            });
        $query = new User();
        return $this->render('team_withdraw_list', [
            'list' => $list,
            'query' => $query,
            'team_withdraw_num' => $team_withdraw_num,
            'types' => (new RechargeLog())->getAllType(),
        ]);
    }

    //查看队员提现详情
    function check_team_detail(Request $request)
    {
        $tid = $request->param('tid');
        $entity = MyWalletLog::alias('rl')
            ->field('rl.*,u.id as uid,u.nick_name ,u.status as ustatus,u.tid');
        $list = $entity
            ->leftJoin('user u', 'u.id = rl.uid')
            ->where('u.tid', '>', 0)
            ->where('u.tid', $tid)
            ->where('rl.types', 5)
            ->where('rl.number', '<', 0)
            ->paginate(15, false, [
                'query' => $request->param() ? $request->param() : [],
            ])
            ->each(function ($item, $key) {
                $item['number'] = abs($item['number']);
            });
        $query = new User();
        return $this->render('check_team_detail', [
            'list' => $list,
            'query' => $query,
            'types' => (new RechargeLog())->getAllType()
        ]);
    }


    //扣款充值明细
    public function moneydisplay(Request $request)
    {

        $type = $request->param('type');
        $case = $request->param('case');
        $keyword = $request->param('keyword');
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        try {

            if ($type == 2) {
                $proHibitLog = DB::table('prohibit_log')
                    ->alias('p')
                    ->leftJoin('user u', 'u.id=p.uid')
                    ->leftJoin('user_invite_code uic', 'uic.user_id=u.id')
                    ->field('uic.invite_code,u.nick_name,p.number,p.createtime,u.id,2 as type,p.type as remark')
                    ->order('createtime', 'desc');

                switch ($case) {
                    case 'userid':
                        $proHibitLog->where('uic.invite_code', $keyword);
                        break;
                    case 'user_nick_name':
                        $proHibitLog->where('u.nick_name', $keyword);
                        break;
                    case 'mobile':
                        $proHibitLog->where('u.mobile', 'like', $keyword . '%');
                        break;
                }
                $map['type'] = $type;
                $map['case'] = $case;
                $map['keyword'] = $keyword;
                if ($startTime || $endTime) {
                    if (empty($startTime)) {
                        $startTime = time();
                    }
                    if (empty($endTime)) {
                        $endTime = time();
                    }
                    $proHibitLog->where('p.createtime', '<', strtotime($endTime))
                        ->where('p.createtime', '>=', strtotime($startTime));
                    $map['startTime'] = $startTime;
                    $map['endTime'] = $endTime;
                }
                $list = $proHibitLog
                    ->paginate(15, false, ['query' => request()->param()]);
                $result = ['list' => $list, 'map' => $map];
            } else if ($type == 3) {
                $result = $this->getList('my_integral_log', $case, $keyword, $type, $startTime, $endTime);
            } else if ($type == 4) {
                $result = $this->getListGtcAndIntegral($case, $keyword, $type, $startTime, $endTime);
            } else if ($type == 5) {
                $result = $this->getListGc($case, $keyword, $type, $startTime, $endTime);

            } else {
                $result = $this->getList('my_wallet_log', $case, $keyword, $type, $startTime, $endTime);
            }
        } catch (\Exception $e) {
            Db::rollback();
            return true;
        }

        return $this->render('moneyDisplay', $result);

    }

    /**
     * 提币
     */
    public function withdraw()
    {
        //读取列表并生成分页
        $list = GcWithdrawLog::alias('gwl')
            ->join('user u', 'gwl.uid=u.id', 'LEFT')
            ->field([
                'gwl.id',
                'gwl.uid',
                'u.nick_name',
                'gwl.wallet_address',
                'gwl.total_amount',
                'gwl.amount',
                'gwl.commission',
                'gwl.status',
                'gwl.create_time',
                'gwl.done_time'
            ])
            ->order('gwl.id desc')
            ->paginate(15);
        //渲染
        return $this->render('withdraw', ['list' => $list]);
    }

    /**
     * 提币处理
     */
    public function withdraw_operation()
    {
        $id = input('id');
        if (empty($id)) {
            $this->error('缺失参数');
        };
        $info = GcWithdrawLog::alias('gwl')
            ->join('user u', 'gwl.uid=u.id', 'LEFT')
            ->field([
                'gwl.id',
                'gwl.uid',
                'u.nick_name',
                'gwl.wallet_address',
                'gwl.total_amount',
                'gwl.amount',
                'gwl.commission',
                'gwl.status',
                'gwl.create_time',
                'gwl.done_time'
            ])
            ->where([
                'gwl.id' => $id
            ])
            ->find();

        return $this->render('withdraw_operation', ['info' => $info]);
    }

    /**
     * 提币处理通过
     */
    public function withdraw_operation_success()
    {
        $id = input('id');
        if (empty($id)) {
            $this->error('缺失参数');
        };
        Db::startTrans();
        //更新记录状态为成功
        if (GcWithdrawLog::where(['id' => $id])->update([
                'status' => 1,
                'done_time' => time()
            ]) < 1) {
            Db::rollback();
            $this->error('处理失败2');
        };
        Db::commit();
        $result = GcWithdrawLog::alias('a')
            ->join('user b', 'a.uid = b.id')
            ->where(['a.id' => $id])
            ->field('a.amount, b.mobile')
            ->find();
        // 发送提币成功短信提醒
        $SendSms = new SendSms();
        $SendSms->sendTibiSms($result['mobile'], floatval($result['amount']), "495928");
        //成功返回
        return $this->render('withdraw_operation_done', ['status' => 1]);

    }

    /**
     * 提币处理驳回
     */
    public function withdraw_operation_fail()
    {
        $id = input('id');
        if (empty($id)) {
            $this->error('缺失参数');
        };
        $time = time();
        Db::startTrans();
        try {
            $info = GcWithdrawLog::field([
                'id',
                'uid',
                'wallet_address',
                'total_amount',
                'amount',
                'commission',
                'status',
                'create_time',
                'done_time'
            ])
                ->where([
                    'id' => $id
                ])
                ->find();
            //退GC
            if (!User::where(['id' => $info['uid']])->setInc('gc', $info['total_amount'])) {
                Db::rollback();
                $this->error('处理失败1');
            };
            //添加GC流水日志
            $new_gc_log = [
                'uid' => $info['uid'],
                'amount' => $info['total_amount'],
                'type' => 1,
                'remark' => 'GC闪兑GTC',
                'create_time' => $time
            ];
            if (!MyGcLog::insert($new_gc_log)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '提交流水日志失败!'];
            };
            //更新记录状态为失败
            if (!GcWithdrawLog::where(['id' => $id])->update([
                'status' => 2,
                'done_time' => $time
            ])) {
                Db::rollback();
                $this->error('处理失败2');
            };
            //成功返回
            Db::commit();
            $result = GcWithdrawLog::alias('a')
                ->join('user b', 'a.uid = b.id')
                ->where(['a.id' => $id])
                ->field('a.amount, b.mobile')
                ->find();
            // 发送提币退回短信提醒
            $SendSms = new SendSms();
            $SendSms->sendTibiSms($result['mobile'], float($result['amount']), "495931");
            return $this->render('withdraw_operation_done', ['status' => 2]);

        } catch (\Throwable $th) {
            //throw $th;
            //异常处理
            Db::rollback();
            $this->error('处理失败3');
        }

    }


    /**
     * 提币
     */
    public function deposits(Request $request)
    {

        $entity = Deposit::alias('d')
            ->field([
                'd.id',
                'd.uid',
                'uic.invite_code',
                'u.nick_name',
                'd.from',
                'd.to',
                'd.txid',
                'd.height',
                'd.status',
                'd.currency',
                'd.create_time',
                'd.update_time',
                'd.number'
            ])->join('user u', 'd.uid=u.id', 'LEFT')->join('user_invite_code uic', 'uic.user_id=u.id', 'LEFT');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $entity->where('u.mobile', $keyword);
                    break;
                case 'nick_name':
                    $entity->where('u.nick_name', $keyword);
                    break;
                case 'ids':
                    $entity->where('uic.invite_code', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if ($startTime || $endTime) {
            if (empty($startTime)) {
                $startTime = time();
            }
            if (empty($endTime)) {
                $endTime = time();
            }
            $entity->where('d.create_time', '<', strtotime($endTime))
                ->where('d.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }

        //读取列表并生成分页
        $list = $entity
            ->order('d.id desc')
            ->paginate(15);
        //渲染
        return $this->render('deposit', ['list' => $list]);
    }

    /**
     * 封装查询扣款充值列表数据
     * @param $table
     * @param $case
     * @param $keyword
     * @param $type
     * @return array
     * @throws \think\exception\DbException
     */
    public function getList($table, $case, $keyword, $type, $startTime, $endTime)
    {
        // 页面刚打开时为空
        if (empty($type)) {
            $type = 1;
        }
        $myWalletLog = Db::table($table)
            ->alias('m')
            ->join('user u', 'u.id=m.uid')
            ->leftJoin('user_invite_code uic', 'uic.user_id=u.id')
            ->field('u.id,uic.invite_code,u.nick_name,m.number,m.create_time,m.types,m.create_time createtime,' . $type . ' as type,m.remark')
            ->order('create_time desc');

        switch ($case) {
            case 'userid':
                $myWalletLog->where('uic.invite_code', $keyword);
                break;
            case 'user_nick_name':
                $myWalletLog->where('u.nick_name', $keyword);
                break;
            case 'mobile':
                $myWalletLog->where('u.mobile', 'like', $keyword . '%');
                break;
        }
        $map['type'] = $type;
        $map['case'] = $case;
        $map['keyword'] = $keyword;
        if ($startTime || $endTime) {
            if (empty($startTime)) {
                $startTime = time();
            }
            if (empty($endTime)) {
                $endTime = time();
            }
            $myWalletLog->where('m.create_time', '<', strtotime($endTime))
                ->where('m.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $myWalletLog
            ->paginate(15, false, ['query' => request()->param()]);
        return ['list' => $list, 'map' => $map];
    }

    /**
     * 封装查询扣款充值列表数据（按GTC和积分）
     * @param $table
     * @param $case
     * @param $keyword
     * @param $type
     * @return array
     * @throws \think\exception\DbException
     */
    public function getListGtcAndIntegral($case, $keyword, $type, $startTime, $endTime)
    {
        $a = Db::name('my_wallet_log')->alias('m')
            ->join('user u', 'u.id=m.uid')
            ->leftJoin('user_invite_code uic', 'uic.user_id=u.id')
            ->field('u.id,uic.invite_code,u.nick_name,u.mobile,m.number,m.create_time,m.types,m.create_time createtime,1 as type,m.remark')->buildSql();
        $b = Db::name('my_integral_log')->alias('m')
            ->field('u.id,uic.invite_code,u.nick_name,u.mobile,m.number,m.create_time as create_time,m.types,m.create_time createtime,3 as type,m.remark')
            ->join('user u', 'u.id=m.uid')
            ->leftJoin('user_invite_code uic', 'uic.user_id=u.id')->union([$a])->buildSql();

        $list = Db::table($b . ' c')->order('c.create_time', 'desc');


        switch ($case) {
            case 'userid':
                $list->where('c.invite_code', $keyword);
                break;
            case 'user_nick_name':
                $list->where('c.nick_name', $keyword);
                break;
            case 'mobile':
                $list->where('c.mobile', 'like', $keyword . '%');
                break;
        }
        $map['type'] = $type;
        $map['case'] = $case;
        $map['keyword'] = $keyword;
        if ($startTime || $endTime) {
            if (empty($startTime)) {
                $startTime = time();
            }
            if (empty($endTime)) {
                $endTime = time();
            }
            $list->where('c.create_time', '<', strtotime($endTime))
                ->where('c.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $result = $list
            ->paginate(15, false, ['query' => request()->param()]);
        return ['list' => $result, 'map' => $map];
    }

    public function getListGc($case, $keyword, $type, $startTime, $endTime)
    {
        $myGcLog = DB::table('my_gc_log')
            ->alias('p')
            ->leftJoin('user u', 'u.id=p.uid')
            ->leftJoin('user_invite_code uic', 'uic.user_id=u.id')
            ->field('uic.invite_code,u.nick_name,p.amount as number,p.create_time,u.id,4 as type,p.remark,p.create_time as createtime')
            ->order('create_time', 'desc');

        switch ($case) {
            case 'userid':
                $myGcLog->where('uic.invite_code', $keyword);
                break;
            case 'user_nick_name':
                $myGcLog->where('u.nick_name', $keyword);
                break;
            case 'mobile':
                $myGcLog->where('u.mobile', 'like', $keyword . '%');
                break;
        }
        $map['type'] = $type;
        $map['case'] = $case;
        $map['keyword'] = $keyword;
        if ($startTime || $endTime) {
            if (empty($startTime)) {
                $startTime = time();
            }
            if (empty($endTime)) {
                $endTime = time();
            }
            $myGcLog->where('p.create_time', '<', strtotime($endTime))
                ->where('p.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $myGcLog
            ->paginate(15, false, ['query' => request()->param()]);
        return ['list' => $list, 'map' => $map];
    }

}
