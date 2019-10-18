<?php
namespace app\admin\controller;

use app\common\entity\BillLog;
use app\common\entity\LineDetail;
use app\common\entity\ManageLog;
use app\common\entity\MyWallet;
use app\common\entity\OvertimeConfig;
use app\common\entity\StoreConfig;
use app\common\entity\StoreLog;
use app\common\entity\User;
use app\common\entity\Linelist;
use app\common\entity\Withdraw;
use app\common\entity\Match;

use service\LogService;
use think\Request;

class Finance extends Admin
{
    /**
     * @power 存取管理|存款列表
     * @rank 4
     */
    public function store(Request $request)
    {
        $entity = Linelist::alias('ll')
            ->field('ll.*,u.id as uid,u.nick_name ,u.status as ustatus');
        if ($status = $request->get('status')) {
            $entity->where('ll.status', $status);
            $map['status'] = $status;
        }
        if ($types = $request->get('types')) {
            $entity->where('ll.types', $types);
            $map['types'] = $types;
        }
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('nick_name', 'like','%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $entity->where('ll.create_time', '<', strtotime($endTime))
                ->where('ll.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->leftJoin('user u','u.id = ll.uid')
            ->order('ll.create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        $query = new User();
        return $this->render('store',[
            'list' => $list,
            'query' => $query,
            'cate' => (new Linelist())->getAllCate(),
            'type' => (new Linelist())->getAllType(),
        ]);
    }
    /**
     * @power 存取管理|取款列表
     * @rank 4
     */
    public function take(Request $request)
    {
        $entity = Withdraw::alias('w')
            ->field('w.*,u.id as uid,u.nick_name ,u.status as ustatus');
        if ($status = $request->get('status')) {
            $entity->where('w.status', $status);
            $map['status'] = $status;
        }
        if ($types = $request->get('types')) {
            $entity->where('w.types', $types);
            $map['types'] = $types;
        }
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('nick_name', 'like','%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $entity->where('w.create_time', '<', strtotime($endTime))
                ->where('w.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->leftJoin('user u','u.id = w.uid')
            ->order('w.create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        $query = new User();
        return $this->render('take',[
            'list' => $list,
            'query' => $query,
            'types' => (new Withdraw())->getAllType(),
            'status' => (new Withdraw())->getAllStatus(),
        ]);
    }
    /**
     * @power 存取管理|匹配列表
     * @rank 4
     */
    public function match(Request $request)
    {
        $entity = Match::alias('m')
            ->field('m.*,w.uid as take_user,ll.uid as store_user');
        if ($status = $request->get('status')) {
            $entity->where('m.status', $status);
            $map['status'] = $status;
        }
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('nick_name', 'like','%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $entity->where('m.create_time', '<', strtotime($endTime))
                ->where('m.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->leftJoin('withdraw w','w.id = m.take_id')
            ->leftJoin('line_list ll','ll.id = m.store_id')
            ->order('m.create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        $query = new User();
        return $this->render('match',[
            'list' => $list,
            'query' => $query,
            'status' => (new Match())->getAllStatus(),
        ]);
    }
    /**
     * @power 存取管理|管控列表
     * @rank 4
     */
    public function manage(Request $request)
    {
        $entity = User::alias('u')
            ->field('u.*,mw.old,mw.now');
        if ($recommend = $request->get('recommend')) {
            if($recommend == 1){
                $entity->where('u.invite_count','>', 0);
            }elseif($recommend == 2){
                $entity->where('u.invite_count', 0);
            }
            $map['recommend'] = $recommend;
        }
        if ($manage_status = $request->get('manage_status')) {
            $entity->where('u.manage_status', $manage_status);
            $map['manage_status'] = $manage_status;
        }

        $oldMin = $request->get('oldMin');
        $oldMax = $request->get('oldMax');
        if($oldMin>=0 && $oldMax){
            $entity->where('mw.old', '<', $oldMax)
                ->where('mw.old', '>=',$oldMin);
            $map['oldMin'] = $oldMin;
            $map['oldMax'] = $oldMax;
        }
        $nowMin = $request->get('nowMin');
        $nowMax = $request->get('nowMax');
        if($nowMin>=0 && $nowMax){
            $entity->where('mw.now', '<', $nowMax)
                ->where('mw.now', '>=', $nowMin);
            $map['nowMax'] = $nowMax;
            $map['nowMin'] = $nowMin;
        }

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('nick_name', 'like','%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $entity->where('u.register_time', '<', strtotime($endTime))
                ->where('u.register_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }

        $list = $entity
            ->leftJoin('my_wallet mw','mw.uid = u.id')
            ->paginate(15,true,[
                'query' => $request->param()?$request->param():[],
            ]);

        foreach ($list as $key => $val){
            $store = StoreLog::where('uid',$val['id'])->where('my_open_time',null)->sum('num');
            $withdraw = Withdraw::where('uid',$val['id'])->where('status','<>',3)->count('id');
            $total = Withdraw::where('uid',$val['id'])->where('status',3)->sum('total');
            $store_num = StoreLog::where('uid',$val['id'])->where('status',1)->sum('num');
            $val['loss'] = $total - $store_num;
            $val['store'] = $store;
            $val['withdraw'] = $withdraw;
        }


        $query = new User();
        return $this->render('manage',[
            'list' => $list,
            'query' => $query,
            'ManageStatus' => $query->getAllManageStatus(),
        ]);
    }
    /**
     * @power 存取管理|管控所选用户
     * @rank 4
     */
    public function lockAll(Request $request)
    {
        $idStr = $request->post('id');
        if(!$idStr){
            return json()->data(['code' => 1, 'message' => '请选择用户']);
        }
        $idArr = explode(",", $idStr);

        foreach ($idArr as $v){
            User::where('id',$v)->update(['manage_status'=>2]);
            $manage_log_model = new ManageLog();
            $manage_log_data = [
                'uid' => $v,
                'status' => 2,
            ];
            $manage_log_model->addNew($manage_log_model,$manage_log_data);
        }
        LogService::write('管控管理', '用户管控所选用户');
        return json()->data(['code' => 0, 'toUrl' => url('/admin/Finance/manage')]);
    }
    /**
     * @power 存取管理|解除所选用户的管控状态
     * @rank 4
     */
    public function openAll(Request $request)
    {
        $idStr = $request->post('id');
        if(!$idStr){
            return json()->data(['code' => 1, 'message' => '请选择用户']);
        }

        $idArr = explode(",", $idStr);
        foreach ($idArr as $v){
            User::where('id',$v)->update(['manage_status'=>1]);
            $manage_log_data = [
                'uid' => $v,
                'status' => 1,
                'create_time' => time(),
            ];
            $manage_log_model = new ManageLog();
            $manage_log_model->addNew($manage_log_model,$manage_log_data);
        }
        LogService::write('管控管理', '用户解除所选用户的管控状态');
        return json()->data(['code' => 0, 'toUrl' => url('/admin/Finance/manage')]);
    }
    /**
     * @power 存取管理|管控此用户
     * @rank 4
     */
    public function lockOne(Request $request)
    {
        $id = $request->param('id');

        if(!$id){
            return json()->data(['code' => 1, 'message' => '请选择用户']);
        }
        $manage_log_model = new ManageLog();
        $manage_log_data = [
            'uid' => $id,
            'status' => 2,
        ];
        $manage_log_model->addNew($manage_log_model,$manage_log_data);
        $res = User::where('id',$id)->update(['manage_status'=>2]);
        if(is_int($res)){
            LogService::write('管控设置', '用户管控单个用户');
            return json()->data(['code' => 0, 'toUrl' => url('/admin/Finance/manage')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 存取管理|解除管控此用户
     * @rank 4
     */
    public function openOne(Request $request)
    {
        $id = $request->param('id');

        if(!$id){
            return json()->data(['code' => 1, 'message' => '请选择用户']);
        }
        $manage_log_model = new ManageLog();
        $manage_log_data = [
            'uid' => $id,
            'status' => 1,
        ];
        $manage_log_model->addNew($manage_log_model,$manage_log_data);
        $res = User::where('id',$id)->update(['manage_status'=>1]);
        if(is_int($res)){
            LogService::write('管控设置', '用户解除单个用户管控状态');
            return json()->data(['code' => 0, 'toUrl' => url('/admin/Finance/manage')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 存取管理|反馈内容
     * @rank 4
     */
    public function manageMsg(Request $request)
    {
        $id = $request->param('id');
        $manageMsg = $request->post('reply');
        $res = User::where('id',$id)->update([
            'manage_status' => 1,
            'manage_msg' => $manageMsg,
        ]);
        if(is_int($res)){
            LogService::write('管控管理', '用户给用户添加反馈内容');
            return json()->data(['code' => 0, 'toUrl' => url('/admin/Finance/manage')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * @power 存取管理|生成订单
     * @rank 4
     */
    public function manageUser(Request $request)
    {
        $id = $request->param('id');
        $list = User::where('id',$id)->find();
        $query =  new Withdraw();
        return $this->render('manageUser',[
            'list' => $list,
            'query' => $query,
            'types' => $query->getAllType(),
        ]);
    }
    /**
     * @power 存取管理|处理生成取款订单
     * @rank 4
     */
    public function doMangeUser(Request $request)
    {
        $uid = $request->post('uid');
        $userInfo = User::alias('u')
            ->field('u.nick_name,mw.*')
            ->where('u.id',$uid)
            ->leftJoin('my_wallet mw','mw.uid = u.id')
            ->find();
        if($request->post('types') == 1){//固定收入

            if($userInfo['old'] - $request->post('num') < 0){
                return json(['code' => 1, 'message' => '余额不足']);
            }

        }elseif($request->post('types') == 2){//当前余额
            if($userInfo['now'] - $request->post('num') < 0){
                return json(['code' => 1, 'message' => '余额不足']);
            }
        }else{
            return json()->data(['code'=>1,'message'=>'参数错误']);
        }
        $withdraw_data = [
            'uid' => $uid,
            'total' => $request->post('num'),
            'types' => $request->post('types'),
            'status' => 1,
            'overplus' => $request->post('num'),
        ];
        $withdraw = new Withdraw();
        $take_id = $withdraw->addNew($withdraw,$withdraw_data);
        if($take_id){
            $low = \app\common\entity\MyWallet::where('uid',$uid)->setDec('old',$request->post('num'));
            if($low){
                LogService::write('管控管理', '用户操作取款');
                return json()->data(['code'=>0,'toUrl' => url('/admin/Finance/take')]);
            }
        }
        return json()->data(['code'=>1,'message'=>'操作成功']);
    }
    /**
     * @power 存取管理|生成存款订单
     * @rank 4
     */
    public function manageStoreUser(Request $request)
    {
        $id = $request->param('id');
        $list = User::where('id',$id)->find();
        $query =  new Linelist();

        return $this->render('manageStoreUser',[
            'list' => $list,
            'query' => $query,
            'types' => $query->getAllType(),
        ]);
    }
    /**
     * @power 存取管理|处理生成存款订单
     * @rank 4
     */
    public function doManageStoreUser(Request $request)
    {
        $uid = $request->post('uid');
        $userInfo = \app\common\entity\MyWallet::alias('mw')
            ->field('mw.*,u.score,u.level')
            ->where('uid',$uid)
            ->leftJoin('user u','u.id = mw.uid')
            ->find();

        $line_config = StoreConfig::where('status',1)->find();
        //消耗排单币数量，向上取整
        $number = ceil(($request->post('num')/$line_config['num']) * $line_config['price']);
        //添加消耗排单币记录
        if($userInfo['number'] - $number < 0){
            return json()->data(['code'=>1,'message'=>'排单币不足，请先购买']);
        }
        $bill_log = [
            'uid' => $uid,
            'num' => $number,
            'old' => $userInfo['number'],
            'new' => $userInfo['number'] - $number,
            'remake' => '后台存款消耗排单币',
        ];
        $entry = new BillLog();
        $res = $entry->addNew($entry,$bill_log);
        if(!$res){
            return json()->data(['code'=>1,'msg'=>'操作失败']);
        }
        \app\common\entity\MyWallet::where('uid',$uid)->setDec('number',$number);
        $withdraw = new Linelist();
        $line_list_data = [
            'uid' => $uid,
            'num' => $request->post('num'),
            'overmoney' => $request->post('num'),
            'types' => $request->post('types'),
            'status' => 1,
        ];
        $res = $withdraw->addNew($withdraw,$line_list_data);
        if($res){
            LogService::write('管控管理', '用户操作存款');
            return json()->data(['code'=>0,'toUrl' => url('/admin/Finance/store')]);
        }
        return json()->data(['code'=>1,'msg'=>'操作失败']);
    }
    /**
     * @power 存取管理|查看用户生成的取单
     * @rank 4
     */
    public function userTakeList(Request $request)
    {
        $query = Withdraw::alias('w')->field('w.*,u.nick_name,u.status as ustatus');
        $uid = $request->param('id');
        $nick_name = User::where('id',$uid)->value('nick_name');
        $storeMin = $request->get('storeMin');
        $storeMax = $request->get('storeMax');
        if($storeMin>=0 && $storeMax){
            $query->where('w.overplus', '<', $storeMax)
                ->where('w.overplus', '>=', $storeMin);
            $map['storeMax'] = $storeMax;
            $map['storeMin'] = $storeMin;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $query->where('w.create_time', '<', strtotime($endTime))
                ->where('w.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $query
            ->where('uid',$uid)
            ->leftJoin('user u','w.uid = u.id')
            ->where('w.status','<>',3)
            ->paginate(15,true,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('userTakeList',[
            'list' => $list,
            'query' => new User(),
            'nick_name' => $nick_name,
            'uid' => $uid,
        ]);
    }
    /**
     * @power 存取管理|删除订单
     * @rank 4
     */
    public function delmarry(Request $request)
    {
        $id = $request->param('id');
        $uid = $request->param('uid');
        $res = Withdraw::where('id',$id)->delete();
        if($res){
            LogService::write('管控管理', '用户删除订单');
            return json()->data(['code'=>0,'toUrl' => url('/admin/Finance/usertakelist?id='.$uid)]);
        }
        return json()->data(['code'=>1,'msg'=>'操作失败']);
    }
    /**
     * @power 存取管理|匹配到存单
     * @rank 4
     */
    public function marry(Request $request)
    {
        $take_id = $request->param('id');
        $uid = $request->param('uid');
        $entity = Linelist::alias('ll')
            ->field('ll.*,u.id as uid,u.nick_name ,u.status as ustatus');
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('u.nick_name', 'like','%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $storeMin = $request->get('storeMin');

        $storeMax = $request->get('storeMax');
        if($storeMin>=0 && $storeMax){

            $entity->where('ll.overmoney', '<', $storeMax)
                ->where('ll.overmoney', '>=', $storeMin);
            $map['storeMax'] = $storeMax;
            $map['storeMin'] = $storeMin;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $entity->where('ll.create_time', '<', strtotime($endTime))
                ->where('ll.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->leftJoin('user u','u.id = ll.uid')
            ->whereIn('ll.status',[1,2])
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);

        $query = new User();
        return $this->render('userStoreList',[
            'list' => $list,
            'query' => $query,
            'take_id' => $take_id,
            'uid' => $uid,
        ]);

    }
    public function delMarryTo(Request $request)
    {
        $id = $request->param('store_id');
        $uid = $request->param('uid');
        $res = Linelist::where('id',$id)->delete();
        if($res){
            LogService::write('管控管理', '用户删除订单');
            return json()->data(['code'=>0,'toUrl' => url('/admin/Finance/marry?id='.$id.'&uid='.$uid)]);
        }
        return json()->data(['code'=>1,'msg'=>'操作失败']);
    }
    /**
     * @power 存取管理|匹配到存单
     * @rank 4
     */
    public function marryTo(Request $request)
    {
        $store_id = $request->param('store_id');
        $take_id = $request->param('take_id');

        $money =  Withdraw::where('id',$take_id)->value('overplus');//取款金额
        $match = new \app\common\entity\Match();

        $info = Linelist::field('')->where('id',$store_id)->find();//存款金额

        Linelist::where('id',$store_id)->update([
            'status' => 5,
            'update_time' => time(),
        ]);
        $match_data = [
            'take_id' => $take_id,
            'store_id' => $store_id,
            'prove' => '',
            'status' => 1,
        ];

        if($info['num'] == $money){//完全存 取款
            $match_data['money'] = $info['num'];
            Withdraw::where('id',$take_id)->update([
                'status' => 3,
                'delate_time' => date('Y-m-d H:i:s',time()),
                'overplus' => 0,
            ]);
        }
        if($info['num'] > $money){//存款金额大于取款金额
            $match_data['money'] = $money;//完全取款
            Withdraw::where('id',$take_id)->update([
                'status' => 3,
                'delate_time' => date('Y-m-d H:i:s',time()),
                'overplus' => 0,
            ]);
        }
        if($info['num'] < $money){//存款金额小于取款金额
            $match_data['money'] = $info['num'];//部分存款
            Withdraw::where('id',$take_id)->update([
                'status' => 2,
               'overplus' => $money - $info['num'],
            ]);
        }
        $match_id = $match->addNew($match,$match_data);
        if($match_id){
            $match_info = \app\common\entity\Match::where('id',$match_id)->find();
            $store_config = OvertimeConfig::where('types',1)->find();
            $store_time = time() + ($store_config['time'] * 3600);
            $ok_config = OvertimeConfig::where('types',2)->find();

            $ok_time = time() + ($ok_config['time'] * 3600);

            $line_datail = new LineDetail();
            $detail_data = [
                'line_id' => $match_info['store_id'],
                'match_id' => $match_id,
                'num' =>  $match_data['money'],
            ];
            $line_datail->addNew($line_datail,$detail_data);
            $row = \app\common\entity\Match::where('id',$match_id)
                ->update([
                    'over_store_time' => date('Y-m-d H:i:s',$store_time) ,
                    'over_ok_time' => date('Y-m-d H:i:s',$ok_time) ,
                ]);
            if($row){
                return json()->data(['code'=>0,'msg'=>'匹配成功']);
            }
        }

        return json()->data(['code'=>1,'msg'=>'匹配失败']);
    }
    /**
     * @power 存取管理|管控记录
     * @rank 4
     */
    public function managelog(Request $request)
    {
        $entity = ManageLog::alias('ml')
            ->field('ml.*,u.id as uid,u.nick_name ,u.status as ustatus');
        if ($status = $request->get('status')) {
            $entity->where('ml.status', $status);
            $map['status'] = $status;
        }

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('nick_name', 'like','%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $entity->where('ml.create_time', '<', strtotime($endTime))
                ->where('ml.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $list = $entity
            ->leftJoin('user u','u.id = ml.uid')
            ->order('create_time','desc')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        $query = new User();
        return $this->render('managelog',[
            'list' => $list,
            'query' => $query,
            'status' => (new ManageLog())->getAllStatus(),
        ]);
    }
}
