<?php
namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\common\entity\ActiveApply;
use app\common\entity\FrozenConfig;
use app\common\entity\Linelist;
use app\common\entity\Match;
use app\common\entity\MyWallet;
use app\common\entity\Orders;
use app\common\entity\PersonService;
use app\common\entity\Recharge;
use app\common\entity\RechargeLog;
use app\common\entity\StoreConfig;
use app\common\entity\StoreLog;
use app\common\entity\User;
use app\common\entity\Withdraw;
use app\index\model\Market;
use service\LogService;
use think\Request;

class Order extends Admin
{
    /**
     * @power 交易市场|求购订单
     * @rank 4
     */
    public function index(Request $request)
    {

        $list = $this->search($request);

        return $this->render('list', [
            'list' => $list,
        ]);
    }

    /**
     * @power 交易市场|出售订单@订单详细
     * @method GET
     */
    public function detail(Request $request)
    {
        $id = $request->param('id');
//        $info = Orders::alias('o')
//            ->leftJoin('recharge r','o.active_id = r.id')
//            ->where('o.id',$id)
//            ->find();

        $order = Recharge::alias('r')
            ->field('r.*,u.id as uid ,u.nick_name')
            ->leftJoin('orders o','o.active_id = r.id')
            ->leftjoin('user u','r.uid = u.id')
            ->where('o.id', $id)
            ->find();

        if (!$order) {
            $this->error('对象不存在');
        }

        return $this->render('detail', [
            'order' => $order,
        ]);
    }

    /**
     * @power 工单列表|通过工单
     */
    public function pass(Request $request)
    {
        $id = $request->param('id');
        $info = Orders::alias('o')
            ->leftJoin('recharge r','o.active_id = r.id')
            ->where('o.id',$id)
            ->find();
        $uid = $info['uid'];
        $nums = $info['nums'];
        Orders::alias('o')
            ->leftJoin('my_wallet mw','o.uid = mw.uid')
            ->where('o.uid',$uid)
            ->setInc('mw.number',$info['nums']);
        Orders::alias('o')
            ->leftJoin('recharge r','o.uid = r.uid')
            ->where('o.id',$id)
            ->update(['r.status'=>2]);

        $recharge_data = [
            'uid' => $uid,
            'types' => 2,
            'num' => $nums,
            'remake' => '用户充值余额',
            'create_time' => time(),
        ];
        RechargeLog::insert($recharge_data);
        $res = Orders::where('id',$id)->update(['status'=>2]);
        if($res){
            LogService::write('工单管理', '用户通过充值申请列表');
            return json(['code'=>0,'toUrl'=>url('index')]);
        }
        return json(['code'=>0,'message'=>'操作失败']);
    }

    /**
     * @power 工单列表|拒绝工单
     */
    public function refuse(Request $request)
    {
        $id = $request->param('id');
        $res = Orders::where('id',$id)->update(['status'=>3]);
        if($res){
            LogService::write('工单管理', '用户拒绝充值申请列表');
            return json(['code'=>0,'toUrl'=>url('index')]);
        }
        return json(['code'=>0,'message'=>'操作失败']);
    }

    protected function search($request)
    {
        $query = Orders::alias('o')->field('o.*,u.nick_name,u.id as uid');

        if ($status = $request->get('status')) {
            $query->where('o.status', $status);
            $map['status'] = $status;
        }
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $query->where('u.nick_name','like', '%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $query->where('o.create_time', '<', strtotime($endTime))
            ->where('o.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }

        $userTable = (new User())->getTable();
        $list = $query->leftJoin("$userTable u", 'u.id = o.uid')
            ->order('create_time', 'desc')
            ->paginate(2, false, [
                'query' => isset($map) ? $map : []
            ]);

        return $list;
    }
    /**
     * @power 交易市场|求购订单
     * @rank 4
     */
    public function apply(Request $request)
    {

        $list = $this->searchApply($request);

        return $this->render('apply', [
            'list' => $list,
        ]);
    }
    protected function searchApply($request)
    {
        $query = ActiveApply::alias('aa')->field('aa.*,u.nick_name,u.id as uid');

        if ($status = $request->get('status')) {
            $query->where('o.status', $status);
            $map['status'] = $status;
        }
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $query->where('u.nick_name','like', '%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $query->where('o.create_time', '<', strtotime($endTime))
                ->where('o.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }

        $userTable = (new User())->getTable();
        $list = $query->leftJoin("$userTable u", 'u.id = aa.uid')
            ->order('create_time', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);

        return $list;
    }
    /**
     * @power 通过激活币审核
     * @rank 4
     */
    public function passApply(Request $request)
    {
        $id = $request->param('id');
        $res = ActiveApply::where('id',$id)->update([
            'status' => 2,
            'update_time' => time(),
        ]);
        $info = ActiveApply::where('id',$id)->find();
        if($res){
            $low = User::alias('u')
                ->leftJoin('active_apply aa','u.id = aa.uid')
                ->where('aa.id',$id)
                ->update([
                   'u.status' =>  1,
                   'u.forbidden_type' =>  0,
                   'u.forbidden_time' =>  null,
                   'u.last_store_time' =>  time(),
                ]);
            if(is_int($low)){
                $row = MyWallet::where('uid',$info['uid'])->update([
                    'number' => $info['line_num'],
                    'active_num' => $info['active_num'],
                ]);
                if(is_int($row)){
                    LogService::write('工单管理', '用户通过激活币审核');
                    return json(['code'=>0,'toUrl'=>url('apply')]);
                }
            }
        }
        return json(['code'=>0,'message'=>'操作失败']);
    }
    /**
     * @power 拒绝激活币审核
     * @rank 4
     */
    public function refuseApply(Request $request)
    {
        $id = $request->param('id');
        $res = ActiveApply::where('id',$id)->update([
            'status' => 3,
            'update_time' => time(),
        ]);
        if($res){
            LogService::write('工单管理', '用户拒绝激活币审核');
            return json(['code'=>0,'toUrl'=>url('apply')]);
        }
        return json(['code'=>0,'message'=>'操作失败']);
    }
    /**
     * @power 重置该订单
     * @rank 4
     */
    public function passProblemOrder(Request $request)
    {
        $id = $request->param('id');

        $info = Match::where('id',$id)->find();
        $res = Match::where('id',$id)->delete();
        
        if($res){
            $res = Match::alias('m')
                ->where('m.id',$id)
                ->leftJoin('line_list ll','ll.id = m.store_id')
                ->update([
                    'll.status' => 1,
                ]);
            Linelist::where('id',$info['store_id'])->update(['status'=>1]);
            Linelist::where('id',$info['store_id'])->setInc('overmoney',$info['money']);

            Withdraw::where('id',$info['take_id'])->setInc('overplus',$info['money']);
            Withdraw::where('id',$info['take_id'])->update(['status'=>1]);
            if($res){
                LogService::write('工单管理', '用户重置问题订单');
                return json(['code'=>0,'toUrl'=>url('problemOrder')]);
            }
        }
        return json(['code'=>0,'message'=>'操作失败']);
    }
    /**
     * @power 完成该订单
     * @rank 4
     */
    public function refuseProblemOrder(Request $request)
    {
        $id = $request->param('id');
        $res = Match::where('id',$id)->update(['status'=>2]);
//        $res = 1;
        if($res){
            $info = \app\common\entity\Match::alias('m')
                ->field('m.money,w.overplus,ll.overmoney,ll.uid')
                ->leftJoin('withdraw w','w.id = m.take_id')
                ->leftJoin('line_list ll','ll.id = m.store_id')
                ->where('m.id',$id)
                ->find();

            $store_config = StoreConfig::where('status',1)->find();
            $store_log = new StoreLog();

            $interest = $store_config['rate'] * $info['money'] * 0.01;
            $frozen_time = FrozenConfig::where('status',1)->where('types',1)->find();
            $you_frozen_time = FrozenConfig::where('status',1)->where('types',2)->find();
            //静态收益
            $store_log_data = [
                'uid' => $info['uid'],
                'types' => 1,
                'status' => 1,
                'num' => $info['money'],
                'interest' => $interest,
                'my_end_time' => time() + ($frozen_time['values'] * 3600),
                'you_end_time' => time() + ($you_frozen_time['values'] * 3600),
                'you_status' => 1,
            ];

            $store_log_id = $store_log->addNew($store_log,$store_log_data);
            //动态收益

            $num = (new \app\index\model\User())->getDynamic( $info['uid'],$store_log_id);

            if($info['money'] == $info['overplus']){
                $res1 = \app\common\entity\Match::alias('m')
                    ->field('')
                    ->leftJoin('withdraw w','w.id = m.take_id')
                    ->where('m.id',$id)
                    ->update([
                        'w.status' => 3,
                        'w.overplus' => 0,
                        'w.delate_time' => date('Y-m-d H:i:s',time()),
                    ]);
            }else{
                $res1 = \app\common\entity\Match::alias('m')
                    ->field('')
                    ->leftJoin('withdraw w','w.id = m.take_id')
                    ->where('m.id',$id)
                    ->update([
                        'w.status' => 2,
                        'w.overplus' => $info['overplus']- $info['money'],
                        'w.delate_time' => date('Y-m-d H:i:s',time()),
                    ]);
            }

            if($info['money'] == $info['overmoney']){

                $res2 = \app\common\entity\Match::alias('m')
                    ->field('')
                    ->leftJoin('line_list ll','ll.id = m.store_id')
                    ->where('m.id',$id)
                    ->update(['ll.status'=>3,'ll.overmoney'=>0]);
                //提升等级
                User::where('id',$info['uid'])->setInc('level');

                $userDetail = User::where('id',$info['uid'])->find();
                \app\common\entity\Team::where('id',$userDetail['tid'])->setInc('line_count');
                \app\common\entity\Team::where('id',$userDetail['tid'])->setInc('money_cont',$info['money']);

            }else{

                $res2 = \app\common\entity\Match::alias('m')
                    ->field('')
                    ->leftJoin('line_list ll','ll.id = m.store_id')
                    ->where('m.id',$id)
                    ->update(['ll.status' => 2,'ll.overmoney'=>$info['overmoney'] - $info['money']]);
            }

            if(is_int($res1) && is_int($res2)){
                LogService::write('工单管理', '用户完成问题订单');
                return json(['code'=>0,'toUrl'=>url('problemOrder')]);
            }

        }
        return json(['code'=>1,'message'=>'操作失败']);
    }
    /**
     * @power 问题订单列表
     * @rank 4
     */
    public function problemOrder(Request $request)
    {
        $list = $this->searchProblemOrder($request);

        return $this->render('problemOrder', [
            'list' => $list,
            'query' => new User(),
        ]);
    }
    /**
     * @power 搜索问题订单
     * @rank 4
     */
    protected function searchProblemOrder($request)
    {
        $query = Match::alias('m')->field('m.*,ll.uid as take_user_id,w.uid as store_user_id');

        if ($status = $request->get('status')) {
            $query->where('m.status', $status);
            $map['status'] = $status;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $query->where('m.create_time', '<', strtotime($endTime))
                ->where('m.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }

        $list = $query
            ->leftJoin("line_list ll", 'll.id = m.store_id')
            ->leftJoin("withdraw w", 'w.id = m.take_id')
            ->whereIn('m.status',[3,4])
            ->order('create_time', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);

        return $list;
    }
    /**
     * @power 客服工单类表
     * @rank 4
     */
    public function personService(Request $request)
    {
        $query = PersonService::alias('ps')->field('ps.*,u.nick_name');

        if ($status = $request->get('status')) {
            $query->where('ps.status', $status);
            $map['status'] = $status;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $query->where('ps.create_time', '<', strtotime($endTime))
                ->where('ps.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }

        $list = $query
            ->leftJoin('user u','ps.uid = u.id')
            ->paginate(15, false, [
            'query' => isset($map) ? $map : []
        ]);

        return $this->render('personService', [
            'list' => $list,
            'entry' => new User(),
        ]);
    }
    /**
     * @power 拒绝客服工单
     * @rank 4
     */
    public function refuseperson(Request $request)
    {
        $id = $request->param('id');
        $reply = $request->post('reply');
        if(!$id || !$reply){
            return json(['code'=>1,'message'=>'拒绝理由不能为空']);
        }
        $res = PersonService::where('id',$id)->update([
            'status' => 3,
            'reply' => $reply,
        ]);

        if($res){
            LogService::write('工单管理', '用户通过充值申请列表');
            return json(['code'=>0,'message'=>'操作失败']);
        }
        return json(['code'=>1,'message'=>'操作失败']);
    }
    /**
     * @power 通过客服工单
     * @rank 4
     */
    public function passPerson(Request $request)
    {
        $id = $request->param('id');
        $res = PersonService::alias('ps')
            ->leftJoin('user u', 'ps.uid = u.id')
            ->where('ps.id', $id)
            ->update([
                'u.status' => 1,
                'u.forbidden_type' => 0,
                'u.forbidden_time' => null,
            ]);
        if (is_int($res)) {
            $low = PersonService::where('id',$id)->update([
                'status' => 2,
                'reply' => '已通过',
            ]);
            if($low){
                LogService::write('工单管理', '用户通过客服工单并激活用户');
                return json(['code'=>0,'toUrl'=>url('personService')]);
            }

        }
        return json(['code' => 1, 'message' => '操作失败']);
    }
}
