<?php

namespace app\admin\controller;

use app\common\entity\Dynamic_Log;
use app\common\entity\StoreLog;
use app\common\entity\User;
use app\common\entity\MyIntegralLog;
use app\common\entity\MyIntegral;
use app\common\entity\MyGcLog;
use app\common\entity\Prohibit;
use app\common\entity\TakeMoneyLog;
use app\common\entity\RechargeLog;
use app\common\entity\BillLog;
use app\common\entity\ActiveLog;
use app\common\entity\BigWithdra;
use app\common\entity\User as userModel;
use app\common\entity\Withdraw;
use app\common\entity\Match;
use app\common\entity\GcWithdrawLog;
use app\common\entity\OvertimeLog;
use app\common\entity\Config;
use app\index\model\Publics as PublicModel;
use service\LogService;
use think\Request;
use think\Db;

class Integral extends Admin
{
    /**
     * @power 财务管理|会员钱包管理(GTC)
     * @rank 4
     */
    public function wallet(Request $request)
    {
        $entity = MyIntegral::alias('mw')
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

        $entity = MyIntegralLog::alias('rl')
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
        if ($request->isGet()) {
            $log = MyIntegralLog::alias('ml')
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
                        $log->where('u.mobile', 'like', '%' . $keyword . '%');
                        break;
                    case 'ids':
                        $log->where('uic.invite_code', $keyword);
                        break;
                }
                $map['type'] = $type;
                $map['keyword'] = $keyword;
            }
            $data = $log->paginate(15, false, []);


            return $this->render('recharge', [
                'list' => $data
            ]);

        } elseif ($request->isPost()) {

            $result = $this->validate($request->post(), 'app\admin\validate\Recharge');
            if (true !== $result) {
                return json()->data(['code' => 1, 'message' => $result]);
            }

            $is_user = User::alias('u')
                ->join('user_invite_code uic', 'uic.user_id = u.id')
                ->where('u.mobile|uic.invite_code', $request->post('uid'))
                ->field('u.*')
                ->find();
//            echo Db::table('user')->getLastSql();
//            dump($is_user);exit;
            if (empty($is_user)) {
                $this->error('无该用户');
            }
            $query = MyIntegral::where('uid', $is_user->id)->find();
            if (empty($query)) {
                $this->error('无效id');
            }
            $data = $request->post();
            $data['uid'] = $is_user->id;

            $data['type'] = 1;
            $res = $query->RechargeLog($query, $data);

            /* 激活用户 */
            $num = MyIntegral::where('uid', $is_user->id)->field('old')->find();
            if ($num && $is_user->is_active == 0) {
                $switch = Config::getValue('activation_num');
                if ($num->old >= $switch) {
                    $save['is_active'] = 1;
                    $save['status'] = 1;
                    $save['active_time'] = time();
                    $save['update_time'] = time();
                    User::where('id', $is_user->id)->update($save);
                }
            }

            if ($res) {
                LogService::write('财务管理', '用户人工充值积分');
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
    public function take(Request $request)
    {
        if ($request->isGet()) {

            //GTC扣款
            $log = MyIntegralLog::alias('ml')
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
                        $log->where('u.mobile', 'like', '%' . $keyword . '%');
                        break;
                    case 'ids':
                        $log->where('uic.invite_code', $keyword);
                        break;
                }
                $map['type'] = $type;
                $map['keyword'] = $keyword;
            }

            $data = $log->paginate(15, false, []);
            return $this->render('take', [
                'list' => $data
            ]);
        }
        if ($request->isPost()) {

            $result = $this->validate($request->post(), 'app\admin\validate\Recharge');
            if (true !== $result) {
                return json()->data(['code' => 1, 'message' => $result]);
            }
            $query = MyIntegral::where('uid', $request->post('uid'))->find();


            $is_user = User::alias('u')
                ->join('user_invite_code uic', 'uic.user_id = u.id')
                ->where('u.mobile|uic.invite_code', $request->post('uid'))
                ->field('u.*')
                ->find();
            if (empty($is_user)) {
//                return json(['code'=>1,'message'=>'无该用户！']);
                $this->error('无该用户');
            }
            $query = MyIntegral::where('uid', $is_user->id)->find();
            if (empty($query)) {
//                return json(['code'=>1,'message'=>'无效id！']);
                $this->error('无效id');

            }
            $data = $request->post();
            $data['uid'] = $is_user->id;

            // $data['type'] = 1;

            $data['num'] = -$data['num'];
            $data['type'] = 1;
            $edit_data['now'] = $query['now'] - $data['num'];
            if ($edit_data['now'] < 0) {
//              return json(['code'=>1,'message'=>'余额不足！']);
                $this->error('余额不足');
            }
            $res = $query->RechargeLog($query, $data);

            if ($res) {
                LogService::write('财务管理', '用户人工扣款积分');

//                return json(['code' => 0, 'message' => '扣款成功', 'toUrl' => url('Money/recharge')]);
                $this->success('扣款成功');

            }
//            return json(['code'=>1,'message'=>'扣款失败！']);
            $this->error('扣款失败');

        }
    }

    /**
     * 统计
     */
    public function count(Request $request)
    {
        $where = array();
        $where1 = array();
        if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');

            if (empty($stime)) {
                $stime = time();
            } else {
                $stime = strtotime($stime);
            }
            if (empty($ntime)) {
                $ntime = time();
            } else {
                $ntime = strtotime($ntime);
            }
            if ($stime >= $ntime) {
                $this->error('开始时间必须小于结束时间');
            }
            $map['au.stime'] = date('Y-m-d', $stime);
            $map['au.ntime'] = date('Y-m-d', $ntime);
            $where = ['create_time' => ['between time', [$stime, $ntime]]];
            $where1 = ['mwl.create_time' => ['between time', [$stime, $ntime]]];
        }
        $total = MyIntegralLog::where($where)
            ->where(['types' => 1, 'number' => ['>', 0]])
            ->sum('number');
        $L0 = MyIntegralLog::alias('mwl')
            ->join('user u', 'u.id = mwl.uid')
            ->field('u.lv,mwl.number')
            ->where($where1)
            ->where(['mwl.types' => 1, 'mwl.number' => ['>', 0], 'lv' => 0])
            ->sum('mwl.number');
        $L1 = MyIntegralLog::alias('mwl')
            ->join('user u', 'u.id = mwl.uid')
            ->field('u.lv,mwl.number')
            ->where($where1)
            ->where(['mwl.types' => 1, 'mwl.number' => ['>', 0], 'lv' => 1])
            ->sum('mwl.number');
        $L2 = MyIntegralLog::alias('mwl')
            ->join('user u', 'u.id = mwl.uid')
            ->field('u.lv,mwl.number')
            ->where($where1)
            ->where(['mwl.types' => 1, 'mwl.number' => ['>', 0], 'lv' => 2])
            ->sum('mwl.number');
        $L3 = MyIntegralLog::alias('mwl')
            ->join('user u', 'u.id = mwl.uid')
            ->field('u.lv,mwl.number')
            ->where($where1)
            ->where(['mwl.types' => 1, 'mwl.number' => ['>', 0], 'lv' => 3])
            ->sum('mwl.number');
        return $this->render('count', [
            'list' => [
                ['name' => '普通会员', 'number' => $L0],
                ['name' => '初级节点', 'number' => $L1],
                ['name' => '中级节点', 'number' => $L2],
                ['name' => '高级节点', 'number' => $L3],
                ['name' => '后台充值总计', 'number' => $total],
            ]
        ]);
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
            'list' => \app\common\entity\Config::where('type', 1)->where('key', 'team_withdraw_num')->where('status', 1)->select()
        ]);
    }

    //超时预约警报设置
    public function overtime_day_set()
    {
        return $this->render('overtime_day_set', [
            'list' => \app\common\entity\Config::where('type', 1)->where('key', 'overtime_warn')->where('status', 1)->select()
        ]);
    }

    //会员账变
    public function user_wallet_change(Request $request)
    {
        $log_type = $request->param('logtypes');
        $map['logtypes'] = $log_type;
        $where = array();
        $entity = MyIntegralLog::alias('rl')
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
            $where = ['au.okpay_time' => ['<', strtotime($endTime)], 'au.okpay_time' => ['>=', strtotime($startTime)]];
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
        $entity = MyIntegralLog::alias('rl')
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
        $entity = MyIntegralLog::alias('rl')
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
        if ($type == 2) {
            $proHibitLog = DB::table('prohibit_log')
                ->alias('p')
                ->leftJoin('user u', 'u.id=p.uid')
                ->leftJoin('user_invite_code uic', 'uic.user_id=u.id')
                ->field('uic.invite_code,u.nick_name,p.number,p.createtime,u.id,p.type')
                ->order('createtime', 'desc');

            switch ($case) {
                case 'userid':
                    $proHibitLog->where('uic.invite_code', $keyword);
                    break;
                case 'user_nick_name':
                    $myWalletLog->where('u.nick_name', $keyword);
                    break;
                case 'mobile':
                    $proHibitLog->where('u.mobile', 'like', $keyword . '%');
                    break;
            }
            $map['type'] = $type;
            $map['case'] = $case;
            $map['keyword'] = $keyword;
            $list = $proHibitLog
                ->paginate(15);

        } else {

            $myWalletLog = Db::table('my_wallet_log')
                ->alias('m')
                ->join('user u', 'u.id=m.uid')
                ->leftJoin('user_invite_code uic', 'uic.user_id=u.id')
                ->field('u.id,uic.invite_code,u.nick_name,m.number,m.create_time,m.types,m.create_time createtime,m.types type,m.remark')
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
            $list = $myWalletLog
                ->paginate(15);
        }

        return $this->render('moneyDisplay', [
            'list' => $list,
            'map' => $map
        ]);

    }

    /**
     * @power 积分管理|积分列表
     * @rank 1
     */
    public function index(Request $request)
    {
        $PublicModel = new PublicModel;
        $entity = userModel::alias('u')->field('u.*,mw.old,mw.now');

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
                    $entity->join('user_invite_code uic', 'uic.user_id  = u.id');
                    $entity->where('uic.invite_code', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $orderStr = 'u.register_time DESC';

        $list = $entity
            ->leftJoin('my_wallet mw', 'mw.uid = u.id')
            ->order($orderStr)
            ->distinct(true)
            ->paginate(10, false, [
                'query' => isset($map) ? $map : []
            ]);
        if (isset($map['sort'])) {
            $map['sort'] = $map['sort'] == 'desc' ? 'asc' : 'desc';
        }
        foreach ($list as $v) {
            $next_count = \app\common\entity\User::where('pid', $v['id'])->count();

            if ($v['lv']) {

                $entity = new \app\common\entity\MyWallet();
                $teamCount = $entity->teamnum($v['id']);//团队人数

            } else {
                $teamCount = 0;
            }


            $v['next_count'] = $next_count;
            $v['getZT'] = (new \app\common\entity\User())->getZT($v['id']);

            $v['teamCount'] = $teamCount;
            $user_integral = $PublicModel->get_user_integral($v['id']);
            $v['old'] = $user_integral['old'];
            $v['now'] = $user_integral['now'];
        }
        $query = new \app\common\entity\Team();
        return $this->render('index', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
            'query' => $query,
        ]);
    }

    public function integral_detail(Request $request)
    {
        $where = array();
        if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');

            if (empty($stime)) {
                $stime = time();
            } else {
                $stime = strtotime($stime);
            }
            if (empty($ntime)) {
                $ntime = time();
            } else {
                $ntime = strtotime($ntime);
            }
            if ($stime >= $ntime) {
                $this->error('开始时间必须小于结束时间');
            }
            $map['stime'] = date('Y-m-d', $stime);
            $map['ntime'] = date('Y-m-d', $ntime);
            $where = ['m.create_time' => ['between time', [$stime, $ntime]]];
        }
        $uid = $request->param('uid');
        $map['uid'] = $uid;
        $list = Db::table('my_integral_log')
            ->alias('m')
            ->join('user u', 'u.id=m.uid')
            ->leftJoin('user_invite_code uic', 'uic.user_id=u.id')
            ->where('u.id', $uid)
            ->where($where)
            ->order('create_time desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ])->each(function ($item, $key) {
                if ($item['types'] == 5) {
                    $item['from_user'] = Db::table('user_invite_code')->where('user_id', $item['from_id'])->value('invite_code');
                } else {
                    $item['from_user'] = '';
                }
                return $item;
            });
        return $this->render('detail', [
            'list' => $list
        ]);
    }

}
