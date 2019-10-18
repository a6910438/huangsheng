<?php

namespace app\index\controller;

use app\common\entity\BigConfig;
use app\common\entity\BillLog;
use app\common\entity\ExchangeHour;
use app\common\entity\FrozenConfig;
use app\common\entity\LineDetail;
use app\common\entity\Linelist;
use app\common\entity\MoneyRate;
use app\common\entity\Orders;
use app\common\entity\OvertimeConfig;
use app\common\entity\OvertimeLog;
use app\common\entity\ReplyConfig;
use app\common\entity\ReturnConfig;
use app\common\entity\SeachConfig;
use app\common\entity\SeachLog;
use app\common\entity\StoreConfig;
use app\common\entity\StoreLog;
use app\common\entity\Team;
use app\common\entity\User;
use app\common\entity\Withdraw;
use think\Request;
use service\IndexLog;


class Match extends Base {

    /**
     * 交易列表
     */
    public function index(Request $request)
    {
        IndexLog::write('交易中心', '用户访问交易列表');
        $limit = $request->get('limit')?$request->get('limit'):5;
        $list = [];
        if($request->post('type') == 1){//优质单
            $list = Linelist::alias('ll')
                ->field('ll.*,u.nick_name,u.level')
                ->leftJoin('user u','u.id = ll.uid')
                ->where('ll.status',2)
                ->where('ll.overmoney','>',0)
                ->order('stick_time desc,u.level desc')
                ->limit($limit)
                ->select();
            return json(['code' => 0, 'msg' => 'Request successful', 'info' => $list,]);
        }elseif ($request->post('type') == 2){//随机交易区
            $list = Linelist::alias('ll')
                ->field('ll.*,u.nick_name,u.level')
                ->leftJoin('user u','u.id = ll.uid')
                ->where('ll.status',1)
                ->where('ll.overmoney','>',0)
                ->orderRaw("rand()")
                ->limit($limit)
                ->select();
            return json(['code' => 0, 'msg' => 'Request successful', 'info' => $list,]);
        }elseif ($request->post('type') == 3){//自主选择区
            $times = SeachConfig::where('types',1)->value('num');
            $seach = SeachLog::where('uid',$this->userId)->where('date',date('Ymd',time()))->find();
            if($seach['times'] + 1 > $times){
                return json()->data(['code'=>1,'msg'=>'Search times exceed the limit on the day']);
            }
            $res = SeachLog::where('uid',$this->userId)->where('date',date('Ymd',time()))->find();
            if(!$res){
                $add_data = [
                    'uid' => $this->userId,
                    'date' => date('Ymd',time()),
                    'create_time' => time(),
                ];
                SeachLog::insert($add_data);
            }


            $low = SeachLog::where('id',$seach['id'])->setInc('times');
            if($low){
                $list = Linelist::alias('ll')
                    ->field('ll.*,u.nick_name,u.trade_address,u.remake,u.level')
                    ->leftJoin('user u','u.id = ll.uid')
                    ->where('u.nick_name',$request->post('nick_name'))
                    ->where('ll.overmoney','>',0)
                    ->limit($limit)
                    ->select();
                return json(['code' => 0, 'msg' => 'Request successful', 'info' => $list,]);
            }
        }else{
            return json()->data(['code'=>1,'msg'=>'Parameter error']);
        }

    }
    /**
     * 我的交易
     */
    public function order(Request $request)
    {
        IndexLog::write('交易中心', '用户访问我的交易');
        $page = $request->get('page')?$request->get('page'):1;
        $limit = $request->get('limit')?$request->get('limit'):15;
        $status = $request->get('status');
        if($status){
            $where['ll.status'] = $status;
            $map['w.status'] = $status * 10;
        }
        //-----冻结账号-----开始
        $line_list = \app\common\entity\Match::alias('m')
            ->field('ll.*,u.id as uid,m.over_store_time,m.id as active_id,u.chat_num')
            ->leftJoin('line_list ll','m.store_id = ll.id')
            ->leftJoin('user u','u.id = ll.uid')
            ->where('u.id',$this->userId)
            ->where('m.delete_time',null)
            ->Distinct(true)
            ->select();
        foreach ($line_list as $k => $v){
            if(strtotime($v['over_store_time']) < time()){
                //超时未打款封号
                User::where('id',$this->userId)->update([
                    'status' => -1,
                    'forbidden_time' => time(),
                    'forbidden_type' => 1,
                ]);
                (new OvertimeLog())->insert(['mid'=>$v['active_id']]);
                //扣除星级
                User::where('id',$this->userId)->setDec('level');

                $info = \app\common\entity\Match::where('id',$v['active_id'])->find();
                Withdraw::where('id',$info['take_id'])->setInc('overplus',$info['money']);
                Withdraw::where('id',$info['take_id'])->update(['status'=>1]);
                Linelist::where('id',$info['store_id'])->update(['status'=>4]);
                \app\common\entity\Match::where('id',$v['active_id'])->delete();
            }
        }
        $withdraw_list = \app\common\entity\Match::alias('m')
            ->field('w.*,u.id as uid,m.over_ok_time,m.id as active_id,u.chat_num,m.prove')
            ->leftJoin('withdraw w','m.take_id = w.id')
            ->leftJoin('user u','u.id = w.uid')
            ->where('u.id',$this->userId)
            ->where('m.delete_time',null)
            ->Distinct(true)
            ->select();
        foreach ($withdraw_list as $i => $m){
            if(strtotime($m['over_ok_time']) <= time()){
                if($withdraw_list['prove'] != ''){
                    User::where('id',$this->userId)->update([
                        'status' => -1,
                        'forbidden_time' => time(),
                        'forbidden_type' => 2,
                    ]);
                    $store_uid = \app\common\entity\Match::alias('m')
                        ->field('ll.uid')
                        ->leftJoin('line_list ll','m.store_id = ll.id')
                        ->where('m.id',$m['active_id'])
                        ->find();
                    User::where('id',$store_uid['uid'])->update([
                        'status' => -1,
                        'forbidden_time' => time(),
                        'forbidden_type' => 3,
                    ]);
                    \app\common\entity\Match::where('id',$m['active_id'])->update([
                        'delete_time' => date('Y-m-d H:i:s',time()),
                        'status' => 4,
                    ]);
                }
            }
        }
        //-----冻结账号-----结束
        if($status){
            if($status == 2) {
                $store_match_id = Linelist::alias('ll')->where('uid',$this->userId)->select();
                $store_match_id_arr = [];
                foreach ($store_match_id as $v){
                    $store_match_id_arr[] = $v['id'];
                }
                $store_list = \app\common\entity\Match::alias('m')
                    ->field('m.*,u.nick_name,u.chat_num,u.trade_address,u.remake')
                    ->leftJoin('line_list ll', 'll.id', 'm.store_id')
                    ->leftJoin('withdraw w', 'w.id', 'm.take_id')
                    ->leftJoin('user u','u.id = w.uid')
                    ->where('m.status', $status - 1)
                    ->where('m.delete_time',null)
                    ->whereIn('ll.id',$store_match_id_arr)
                    ->Distinct(true)
                    ->page($page)
                    ->paginate($limit);
                $withdraw_match_id = Withdraw::alias('w')->where('uid',$this->userId)->select();
                $withdraw_match_id_arr = [];
                foreach ($withdraw_match_id as $val){
                    $withdraw_match_id_arr[] = $val['id'];
                }
                $withdraw_list = \app\common\entity\Match::alias('m')
                    ->field('m.*')
                    ->leftJoin('line_list ll', 'll.id', 'm.store_id')
                    ->leftJoin('withdraw w', 'w.id', 'm.take_id')
                    ->leftJoin('user u','u.id = ll.uid')
                    ->where('m.status', $status - 1)
                    ->where('m.delete_time',null)
                    ->whereIn('w.id',$withdraw_match_id_arr)
                    ->group("m.id")
                    ->Distinct(true)
                    ->page($page)
                    ->paginate($limit);
                return json()->data(['code'=>0,'msg'=>'Request successful','store_info'=>$store_list,'withdraw_list'=>$withdraw_list]);
            }
            if($status == 3) {

                $list = StoreLog::alias('sl')
                    ->field('sl.*,u.nick_name,u.level,u.chat_num')
                    ->leftJoin('user u', 'sl.uid', 'u.id')
                    ->where('sl.you_status', 1)
                    ->where('sl.uid',$this->userId)
                    ->group("sl.id")
                    ->Distinct(true)
                    ->page($page)
                    ->paginate($limit);
                foreach ($list as $v){
                    $v['my_end_time'] = date('Y-m-d H:i:s',$v['my_end_time']);
                    $v['you_end_time'] = date('Y-m-d H:i:s',$v['you_end_time']);
                }
                return json()->data(['code'=>0,'msg'=>'Request successful','info'=>$list]);
            }
        }
        $line = Linelist::alias('ll')
            ->field('ll.*,u.nick_name,u.chat_num')
            ->leftJoin('user u','u.id = ll.uid')
            ->where('u.id',$this->userId)
            ->whereIn('ll.status',[1,2,3,5])
            ->where(isset($where)&&$where?$where:[])
            ->page($page)
            ->paginate($limit);
        $withdraw = Withdraw::alias('w')
            ->field('w.*,u.nick_name,u.chat_num')
            ->leftJoin('user u','u.id = w.uid')
            ->where('u.id',$this->userId)
            ->whereIn('w.status',[1,2,3])
            ->where(isset($map)&&$map?$map:[])
            ->page($page)
            ->paginate($limit);

        return json()->data(['code'=>0,'msg'=>'Request successful','info'=>[
            'line' => $line,
            'withdraw' => $withdraw,
        ]]);
    }
    /**
     * 取款已解冻|确认到固定资产
     */
    public function click(Request $request)
    {
        IndexLog::write('交易中心', '用户确认到固定资产');
        $id = $request->get('id');
        if(!$id){
            return json()->data(['code'=>1,'msg'=>'Parameter error']);
        }
        $info = StoreLog::where('id',$id)->find();
        $res = \app\common\entity\MyWallet::where('uid',$this->userId)->setInc('old',$info['num']+$info['interest']);
        if($res){
            StoreLog::where('id',$id)->update(['my_open_time'=>time(),'status'=>3]);
            return json()->data(['code'=>0,'msg'=>'Successful operation']);
        }
        return json()->data(['code'=>1,'msg'=>'operation failed']);
    }
    /**
     * 取款
     */
    public function withdraw(Request $request)
    {
        IndexLog::write('交易中心', '用户取款');
        if($request->isGet()){
            $withdraw = new Withdraw();
            $detail = $withdraw->getAllType();
            if($types = $request->get('types')){
                $map['types'] = $types;
            }
            $info = (new MoneyRate())->where('status',1)->where(isset($map)&&$map?$map:[])->select();
            return json()->data(['code'=>0,'msg'=>'Request successful','info'=>[
                'types' => $detail,
                'nums' => $info,
            ]]);
        }
        if($request->isPost()){
            $config = ExchangeHour::find();
            $startTime = $config['star'];
            $endTime = $config['end'];
            if($startTime && $endTime){
                if( ( time() > strtotime($endTime) )|| ( time() <= strtotime($startTime) ) ){
                    return json()->data(['code'=>1,'msg'=>'Non-trading time please try again later']);
                }
            }
            $validate = $this->validate($request->post(), '\app\index\validate\Withdraw');
            if ($validate !== true) {
                return json(['code' => 1, 'msg' => $validate]);
            }
            $userInfo = User::alias('u')
                ->field('u.nick_name,mw.*')
                ->where('u.id',$this->userId)
                ->leftJoin('my_wallet mw','mw.uid = u.id')
                ->find();

            $model = new \app\common\service\Users\Service();

            $user_Info = User::alias('u')
                ->where('u.id',$this->userId)
                ->find();
            if (!$model->checkSafePassword($request->post('trad_password'), $user_Info)) {
                return json(['code'=>1,'msg'=>'Secondary password error']);
            }

            if($request->post('types') == 1){//固定收入

                if($userInfo['old'] - $request->post('num') < 0){
                    return json(['code' => 1, 'msg' => 'Sorry, your credit is running low']);
                }
                $money1 = MoneyRate::where('types',1)->where('status',1)->value('num');
                if($money1){
                    if($money1 > $request->post('num')){
                        return json(['code'=>1,'msg'=>'The sum is too small']);
                    }
                }

            }elseif($request->post('types') == 2){//当前余额
                if($userInfo['now'] - $request->post('num') < 0){
                    return json(['code' => 1, 'msg' => 'Sorry, your credit is running low']);
                }
                $money2 = MoneyRate::where('types',2)->where('status',1)->value('num');
                if($money2){
                    if($money2 > $request->post('num')){
                        return json(['code'=>1,'msg'=>'The sum is too small']);
                    }
                }
            }else{
                return json()->data(['code'=>1,'msg'=>'Parameter error']);
            }

            $withdraw_data = [
                'uid' => $this->userId,
                'total' => $request->post('num'),
                'types' => $request->post('types'),
                'status' => 1,
                'overplus' => $request->post('num'),
            ];
            $withdraw = new Withdraw();
            $take_id = $withdraw->addNew($withdraw,$withdraw_data);
            $big_withdra_config = BigConfig::where('status',1)->value('big_price');
            if($request->post('num') > $big_withdra_config){
                if($take_id){
                    $big_config_model = new BigConfig();
                    $big_config_model->insert(['wid'=>$take_id,'create_time'=>time()]);
                }
            }
            if($take_id){
                if($request->post('types') == 1) {
                    $low = \app\common\entity\MyWallet::where('uid', $this->userId)->setDec('old', $request->post('num'));
                }
                if($request->post('types') == 2){
                    $low = \app\common\entity\MyWallet::where('uid', $this->userId)->setDec('now', $request->post('num'));
                }
                if($low){
                    return json()->data(['code'=>0,'msg'=>'Successful operation']);
                }
            }
            return json()->data(['code'=>1,'msg'=>'operation failed']);
        }

    }
    //匹配存款单
    public function matching(Request $request)
    {
        $match_way = $request->post('match_way');
        $store_id = $request->post('store_id');
        $take_id = $request->post('take_id');
        if(!$store_id || !$take_id){
            return json()->data(['code'=>1,'msg'=>'Missing parameters']);
        }
        //取款金融
        $money =  Withdraw::where('id',$take_id)->value('overplus');
        //存款金额
        $store_money = Linelist::where('id',$store_id)->value('overmoney');

        if($match_way == 1){
            $match_time = SeachLog::alias('sl')
                ->whereTime('create_time', '>','-2 day')
                ->sum('match_num');
            $moneyType =  Withdraw::where('id',$take_id)->value('types');
            if($moneyType == 2){
                return json()->data(['code'=>1,'msg'=>'Dynamic earnings cannot be traded in autonomous areas']);
            }
            if($match_time > 0){
                return json()->data(['code'=>1,'msg'=>'Autonomous selection area, the number of transactions has reached the upper limit']);
            }else{
                //最多交易1500
                if($money > 1500 && $store_money > 1500){
                    return json()->data(['code'=>1,'msg'=>'Autonomous selection area, maximum delivery $1500']);
                }
            }
        }
        //是否被管控
        $info = User::where('id',$this->userId)->find();
        if($info['manage_status'] == 2){
            if($info['manage_msg'] == ''){
                $msg = ReplyConfig::find();
                return json()->data(['code'=>1,'msg'=>$msg['reply']]);
            }
            return json()->data(['code'=>1,'msg'=>$info['manage_msg']]);
        }
        //开始匹配
        $match = new \app\common\entity\Match();
        //修改存款单为正在匹配
        Linelist::where('id',$store_id)->update([
            'status' => 5,
            'update_time' => time(),
        ]);

        $match_data = [
            'take_id' => $take_id,
            'store_id' => $store_id,
            'prove' => '',
            'money' => $money,
            'status' => 1,
        ];

        if($store_money == $money){//完全存 取款
            $match_data['money'] = $store_money;
            Withdraw::where('id',$take_id)->update([
                'status' => 3,
                'delate_time' => date('Y-m-d H:i:s',time()),
                'overplus' => 0,
            ]);
        }
        if($store_money > $money){//存款金额大于取款金额
            $match_data['money'] = $money;//完全取款
            Withdraw::where('id',$take_id)->update([
                'status' => 3,
                'delate_time' => date('Y-m-d H:i:s',time()),
                'overplus' => 0,
            ]);
        }
        if($store_money < $money){//存款金额小于取款金额
            $match_data['money'] = $store_money;//完全存款
            Withdraw::where('id',$take_id)->update([
                'status' => 2,
                'overplus' => $money - $store_money,
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
                'num' => $money,
            ];
            $line_datail->addNew($line_datail,$detail_data);
            $row = \app\common\entity\Match::where('id',$match_id)
                ->update([
                    'over_store_time' => date('Y-m-d H:i:s',$store_time) ,
                    'over_ok_time' => date('Y-m-d H:i:s',$ok_time) ,
                ]);
            IndexLog::write('交易中心', '用户匹配存款单');
            if($match_way == 1){//添加交割次数
                $res = SeachLog::where('uid',$this->userId)->where('date',date('Ymd',time()))->find();
                if($res){
                   SeachLog::where('id',$res['id'])->setInc('match_num');
                }
            }
            if($row){
                return json()->data(['code'=>0,'msg'=>'Matching success']);
            }
        }
        return json()->data(['code'=>1,'msg'=>'Matching failure']);
    }
    /**
     * 存款
     */
    public function store(Request $request)
    {

        if($request->isGet()){
            $info =  (new Linelist ())->getAllType();
            return json()->data(['code'=>0,'msg'=>'Request successful','info'=>$info]);
        }
        if($request->isPost()){

            $config = ExchangeHour::find();
            $startTime = $config['star'];
            $endTime = $config['end'];
            if($startTime && $endTime){
                if( ( time() > strtotime($endTime) )|| ( time() <= strtotime($startTime) ) ){
                    return json()->data(['code'=>1,'msg'=>'Non-trading time please try again later']);
                }
            }
            $validate = $this->validate($request->post(), '\app\index\validate\Store');
            if ($validate !== true) {
                return json(['code' => 1, 'msg' => $validate]);
            }
            //查看撞单情况
            $bump_info = Linelist::where('uid',$this->userId)
                ->where('types',2)
                ->whereIn('status',[1,2,5])
                ->find();
            if($bump_info){
                return json()->data(['code'=>1,'msg'=>'Refusal to crash orders, no more orders']);
            }else{
                $line_count = Linelist::where('uid',$this->userId)
                    ->whereIn('status',[1,2,5])
                    ->count();
                if($line_count >= 20){
                    return json()->data(['code'=>1,'msg'=>'A single person can only queue up to 20 orders.']);
                }
            }
            $userInfo = \app\common\entity\MyWallet::alias('mw')
                ->field('mw.*,u.score,u.level')
                ->where('uid',$this->userId)
                ->leftJoin('user u','u.id = mw.uid')
                ->find();
            $user_Info = User::alias('u')
                ->where('u.id',$this->userId)
                ->find();
            $model = new \app\common\service\Users\Service();

            if (!$model->checkSafePassword($request->post('trad_password'), $user_Info)) {
                return json(['code'=>1,'msg'=>'Secondary password error']);
            }
            //星级对应存款额度
            if($userInfo['score'] < $userInfo['level'] * 200){

                if($request->post('num') > $userInfo['score']){
                    return json()->data(['code'=>1,'msg'=>'Over the maximum deposit value of']);
                }
            }else{
                if($request->post('num') > $userInfo['level'] * 200){
                    return json()->data(['code'=>1,'msg'=>'Exceeding the maximum deposit value']);
                }
            }

            $line_config = StoreConfig::where('status',1)->find();
            //消耗排单币数量，向上取整
            $number = ceil(($request->post('num')/$line_config['num']) * $line_config['price']);

            if($userInfo['number'] - $number < 0){
                return json()->data(['code'=>1,'msg'=>'There is not enough money in the row. Please buy it first.']);
            }
            //添加消耗排单币记录
            $bill_log = [
                'uid' => $this->userId,
                'num' => $number,
                'old' => $userInfo['number'],
                'new' => $userInfo['number'] - $number,
                'remake' => 'Front-end user deposits consume row of bills',
            ];
            $entry = new BillLog();
            $res = $entry->addNew($entry,$bill_log);
            if(!$res){
                return json()->data(['code'=>1,'msg'=>'operation failed']);
            }
            User::where('id',$this->userId)->setField('last_store_time',time());
            \app\common\entity\MyWallet::where('uid',$this->userId)->setDec('number',$number);
            $withdraw = new Linelist();
            $line_list_data = [
                'uid' => $this->userId,
                'num' => $request->post('num'),
                'overmoney' => $request->post('num'),
                'types' => $request->post('types'),
                'status' => 1,
            ];

            $res = $withdraw->addNew($withdraw,$line_list_data);
            if($res){
                IndexLog::write('交易中心', '用户存款');
                return json()->data(['code'=>0,'msg'=>'Successful operation']);
            }
            return json()->data(['code'=>1,'msg'=>'operation failed']);
        }
    }
    /**
     * 上传打款凭证
     */
    public function makeMoney(Request $request)
    {
        if($request->isGet()){
            $active_id = $request->get('active_id');
            if(!$active_id){
                return json()->data(['code'=>1,'msg'=>'Missing parameters']);
            }
            $info = \app\common\entity\Match::alias('m')
                ->field('m.id,u.trade_address,u.remake,ll.overmoney')
                ->leftJoin('line_list ll','ll.id = m.store_id')
                ->leftJoin('user u','u.id = ll.uid')
                ->where('m.id',$active_id)
                ->find();
            return json()->data(['code'=>0,'msg'=>'Successful operation','info'=>$info]);
        }
        if($request->isPost()){
            IndexLog::write('交易中心', '用户上传打款凭证');
            $validate = $this->validate($request->post(), '\app\index\validate\MakeMoney');
            if ($validate !== true) {
                return json(['code' => 1, 'msg' => $validate]);
            }
            $row = \app\common\entity\Match::where('id',$request->post('active_id'))
                ->update([
                    'money'=>$request->post('nums'),
                ]);
            if(is_int($row)){
                //修改凭证
//                $entry = new Orders();
//                $add_data = [
//                    'uid' => $this->userId,
//                    'describe' => '用户上传打款凭证',
//                    'pic' => $request->post('pic'),
//                    'types' => 2,
//                    'active_id' => $request->post('active_id'),
//                ];
                $res = \app\common\entity\Match::alias('m')->leftJoin('line_list ll','m.store_id = ll.id')
                        ->where('m.id',$request->post('active_id'))
                        ->update(['prove'=> $request->post('pic')]);

                if($res){

//                    \app\common\entity\Match::alias('m')->leftJoin('line_list ll','m.store_id = ll.id')
//                        ->where('m.id',$request->post('active_id'))
//                        ->update(['delete_time'=>date('Y-m-d H:i:s',time())]);
                    //修改剩余金额
                    \app\common\entity\Match::alias('m')->leftJoin('line_list ll','m.store_id = ll.id')
                        ->where('m.id',$request->post('active_id'))
                        ->setDec('ll.overmoney',$request->post('nums'));
                    return json()->data(['code'=>0,'msg'=>'Successful operation']);
                }
            }
            return json()->data(['code'=>1,'msg'=>'operation failed']);
        }
    }
    /**
     * 确认收款
     */
    public function okMoney(Request $request)
    {
        IndexLog::write('交易中心', '用户确认收款');
        $validate = $this->validate($request->post(), '\app\index\validate\OkMoney');
        if ($validate !== true) {
            return json(['code' => 1, 'msg' => $validate]);
        }

        $info = \app\common\entity\Match::alias('m')
            ->field('m.money,w.overplus,ll.overmoney,ll.uid')
            ->leftJoin('withdraw w','w.id = m.take_id')
            ->leftJoin('line_list ll','ll.id = m.store_id')
            ->where('m.id',$request->post('active_id'))
            ->find();
        $over_ok_time = \app\common\entity\Match::where('id',$request->post('active_id'))->value('over_ok_time');
        if(strtotime($over_ok_time) < time()){
            $over_time_model = new OvertimeLog();
            $over_time_model->insert(['mid'=>$request->post('active_id')]);
        }
        $store_config = StoreConfig::where('status',1)->find();
        $store_log = new StoreLog();

        $interest = $store_config['rate'] * $info['money'] * 0.01;
        $frozen_time = FrozenConfig::where('status',1)->where('types',1)->find();
        $you_frozen_time = FrozenConfig::where('status',1)->where('types',2)->find();

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
                ->where('m.id',$request->post('active_id'))
                ->update([
                    'w.status' => 3,
                    'w.overplus' => 0,
                    'w.delate_time' => date('Y-m-d H:i:s',time()),
                ]);

        }else{
            $res1 = \app\common\entity\Match::alias('m')
                ->field('')
                ->leftJoin('withdraw w','w.id = m.take_id')
                ->where('m.id',$request->post('active_id'))
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
                ->where('m.id',$request->post('active_id'))
                ->update(['ll.status'=>3,'ll.overmoney'=>0]);
            //提升等级
            User::where('id',$info['uid'])->setInc('level');

            $userDetail = User::where('id',$info['uid'])->find();
            Team::where('id',$userDetail['tid'])->setInc('line_count');
            Team::where('id',$userDetail['tid'])->setInc('money_cont',$info['money']);

        }else{
            $res2 = \app\common\entity\Match::alias('m')
                ->field('')
                ->leftJoin('line_list ll','ll.id = m.store_id')
                ->where('m.id',$request->post('active_id'))
                ->update(['ll.status' => 2,'ll.overmoney'=>$info['overmoney'] - $info['money']]);
            //剩余金额  返还排单币，完成此单
            $return_config = ReturnConfig::where('status',1)->find();
            $nextMoney = $info['overmoney'] - $info['money'];
            if($nextMoney  > $return_config['min'] && $nextMoney < $return_config['price'] ){
                \app\common\entity\Match::alias('m')
                    ->field('')
                    ->leftJoin('line_list ll','ll.id = m.store_id')
                    ->where('m.id',$request->post('active_id'))
                    ->update(['ll.overmoney'=>0,'ll.status' => 3]);
                \app\common\entity\Match::alias('m')
                    ->field('')
                    ->leftJoin('line_list ll','ll.id = m.store_id')
                    ->leftJoin('my_wallet mw','ll.uid = mw.uid')
                    ->where('m.id',$request->post('active_id'))
                    ->update(['mw.number'=>$return_config['num']]);
            }
        }
        if(is_int($res1) && is_int($res2)){
            return json()->data(['code'=>0,'msg'=>'Successful operation']);
        }
        return json()->data(['code'=>1,'msg'=>'operation failed']);
    }
    /**
     * 交易置顶
     */
    public function stick(Request $request)
    {
        $userInfo = User::where('id',$this->userId)->find();
        if($userInfo['level'] < 10){
            return json()->data(['code'=>1,'msg'=>'Stars are not 10-level enough to use this function']);
        }
        $store_id = $request->post('store_id');
        $res = Linelist::where('id',$store_id)->update(['stick_time'=>time()]);
        if($res){
            return json()->data(['code'=>0,'msg'=>'Successful operation']);
        }
        return json()->data(['code'=>1,'msg'=>'operation failed']);
    }

}
