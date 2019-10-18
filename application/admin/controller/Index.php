<?php
namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\admin\service\rbac\Users\Service;
use app\common\entity\Dynamic_Log;
use app\common\entity\ManageUser;
use app\common\entity\Orders;
use app\common\entity\StoreLog;
use app\common\entity\User;
use app\common\entity\UserProduct;
use app\common\entity\GcWithdrawLog;
use think\Db;
use think\Session;
use think\Request;

class Index extends Admin
{
    public function index()
    {
        
        //会员数量
        $user['total'] = User::count();
        $user['use_total'] = User::where('status',1)->count();
        $user['register_user'] = User::where('status',1)->whereTime('register_time', 'today')->count();
        $user['today'] = User::whereTime('register_time', 'today')->count();
		//交易会员--即注册激活成功之后至少成功领取过1条酒的会员
		$user['valid'] = Db::table('user')->alias('u')->where(['u.status'=>1])->join('fish f','f.u_id = u.id')->group('f.u_id')->count();
        $user['undone_withdraw'] = GcWithdrawLog::where(['status'=>0])->count();
        $user['appeal_user'] = Db::table('appeal_user')->where(['status'=>0])->count();
        //获取前十日交易
        $time = $this->get_weeks();
        //冻结金额
        $store_money_today = StoreLog::whereTime('create_time','today')
            ->where('status',1)->sum('num');
        for ($x=2; $x<=11; $x++) {
            $i = $x - 1;
            $pre[$i] = DB::table('appointment_user')->whereTime('create_time',[$time[$i], $time[$x]])
                ->count('id');
            $pre[10] =DB::table('appointment_user')->whereTime('create_time','today')
                ->count('id');
        }
        for ($x=2; $x<=11; $x++) {
            $i = $x - 1;
            $adopt[$i] = DB::table('appointment_user')->whereTime('create_time',[$time[$i], $time[$x]])
                ->where('status','in','-4,-3,-2,1,2,3,4')->count('id');
            $adopt[10] = DB::table('appointment_user')->whereTime('create_time',[$time[$i], $time[$x]])
                ->where('status','in','-4,-3,-2,1,2,3,4')->count('id');
        }
        for ($x=2; $x<=11; $x++) {
            $i = $x - 1;
            $contract[$i] = DB::table('fish_tradable_num')->whereTime('create_time',[$time[$i], $time[$x]])
                ->where('is_delete',0)->sum('f_num');
            $contract[10] =  DB::table('fish_tradable_num')->whereTime('create_time','today')
                ->where('is_delete',0)->sum('f_num');
        }

        $address = Db::table('user')->field('province')->group('province')->select();
        if($address){
            foreach ($address as $k => $v){
                $address_user[$k]['province'] = $v['province'];
                $address_user[$k]['all'] = Db::table('user')->where('province',$v['province'])->count('id');
                $address_user[$k]['effective'] = Db::table('user')->where('province',$v['province'])->where('status','in','0,1')->count('id');
                $address_user[$k]['invalid'] = Db::table('user')->where('province',$v['province'])->where('status','in','-1')->count('id');
            }
        }else{
            $address_user=array();
        }

        $pool = Db::table('bathing_pool')->where('is_open',1)->where('is_delete',0)->field('id,name')->select();
        //转让统计

        if($pool){
            foreach ($pool as $k => $v){

                $lookturn[$k]['pool_name'] =  $v['name'];
                $lookturn[$k]['profit_all'] = DB::table('appointment_user')
                    ->alias('au')
                    ->join('fish_order fo','fo.id = au.oid')
                    ->where('au.status','in','3,4')
                    ->where('au.pool_id',$v['id'])
                    ->sum('fo.worth');
                $lookturn[$k]['num_all'] =DB::table('appointment_user')
                    ->alias('au')
                    ->join('fish_order fo','fo.id = au.oid')
                    ->where('au.status','in','3,4')
                    ->where('au.pool_id',$v['id'])
                    ->count('au.id');
                $look_arr = array();
                for ($x=2; $x<=11; $x++) {
                    $i = $x - 1;
                    $look_arr[$i]['profit'] = DB::table('appointment_user')
                        ->alias('au')
                        ->join('fish_order fo','fo.id = au.oid')
                        ->whereTime('au.create_time',[$time[$i], $time[$x]])
                        ->where('au.status','in','3,4')
                        ->where('au.pool_id',$v['id'])
                        ->sum('fo.worth');
                    $look_arr[$i]['num'] = DB::table('appointment_user')
                        ->alias('au')
                        ->join('fish_order fo','fo.id = au.oid')
                        ->whereTime('au.create_time',[$time[$i], $time[$x]])
                        ->where('au.status','in','3,4')
                        ->where('au.pool_id',$v['id'])
                        ->count('au.id');
					//预约
					$look_arr[$i]['pre'] = DB::table('appointment_user')
						->alias('au')
						->join('fish_order fo','fo.id = au.oid')
						->whereTime('au.create_time',[$time[$i], $time[$x]])
						->where('au.status','in','3,4')
                        ->where('au.pool_id',$v['id'])
						->count('au.id');
					$look_arr[10]['pre'] =DB::table('appointment_user')
						->alias('au')
                        ->join('fish_order fo','fo.id = au.oid')
						->whereTime('au.create_time','today')
						->where('au.status','in','3,4')
                        ->where('au.pool_id',$v['id'])
						->count('au.id');
					//领取
					$look_arr[$i]['adopt'] = DB::table('appointment_user')
						->alias('au')
                        ->join('fish_order fo','fo.id = au.oid')
						->whereTime('au.create_time',[$time[$i], $time[$x]])
						->where('au.status','in','-4,-3,-2,1,2,3,4')
						->where('au.status','in','3,4')
                        ->where('au.pool_id',$v['id'])
						->count('au.id');
					$look_arr[10]['adopt']  = DB::table('appointment_user')
						->alias('au')
                        ->join('fish_order fo','fo.id = au.oid')
						->whereTime('au.create_time',[$time[$i], $time[$x]])
						->where('au.status','in','-4,-3,-2,1,2,3,4')
						->where('au.status','in','3,4')
                        ->where('au.pool_id',$v['id'])
						->count('au.id');
					//合约
					$look_arr[$i]['contract'] = DB::table('fish_tradable_num')
						->whereTime('create_time',[$time[$i], $time[$x]])
						->where('is_delete',0)
                        ->where('pool_id',$v['id'])
						->sum('f_num');
					$look_arr[10]['contract'] =  DB::table('fish_tradable_num')
						->whereTime('create_time','today')
						->where('is_delete',0)
                        ->where('pool_id',$v['id'])
						->sum('f_num');
                    $look_arr[10]['profit'] =  DB::table('appointment_user')
                        ->alias('au')
                        ->join('fish_order fo','fo.id = au.oid')
                        ->whereTime('au.create_time',[$time[$i], $time[$x]])
                        ->where('au.status','in','3,4')
                        ->where('au.pool_id',$v['id'])

                        ->sum('fo.worth');
                    $look_arr[10]['num'] =  DB::table('appointment_user')
                        ->alias('au')
                        ->join('fish_order fo','fo.id = au.oid')
                        ->whereTime('au.create_time',[$time[$i], $time[$x]])
                        ->where('au.status','in','3,4')
                        ->where('au.pool_id',$v['id'])
                        ->count('au.id');
                }
                $lookturn[$k]['arr'] = $look_arr;
            }
        }else{
            $lookturn = array();
        }
        unset($look_arr);

        //GTC统计


        $look_arr = array();
		$recharge_lev = array();
		$level_total = array();
        for ($x=2; $x<=11; $x++) {
            $i = $x - 1;

            $look_arr[$i]['num'] = DB::table('my_wallet_log')
                ->whereTime('create_time',[$time[$i], $time[$x]])
                ->where('types','in','1')
                ->where('number','>',0)
                ->sum('number');

            $look_arr[10]['num'] =  DB::table('my_wallet_log')->whereTime('create_time',[$time[$i], $time[$x]])
                ->where('types','in','1')->where('number','>',0)->sum('number');
				
			$recharge_lev[$i]['L0'] = DB::table('my_wallet_log')
				->alias('mw')
				->join('user u','mw.uid = u.id')
				->where('u.lv = 0')
                ->whereTime('mw.create_time',[$time[$i], $time[$x]])
                ->where('mw.types','in','1')
                ->where('mw.number','>',0)
                ->sum('mw.number'); 
			$recharge_lev[$i]['L1'] = DB::table('my_wallet_log')
				->alias('mw')
				->join('user u','mw.uid = u.id')
				->where('u.lv = 1')
                ->whereTime('mw.create_time',[$time[$i], $time[$x]])
                ->where('mw.types','in','1')
                ->where('mw.number','>',0)
                ->sum('mw.number'); 
			$recharge_lev[$i]['L2'] = DB::table('my_wallet_log')
				->alias('mw')
				->join('user u','mw.uid = u.id')
				->where('u.lv = 2')
                ->whereTime('mw.create_time',[$time[$i], $time[$x]])
                ->where('mw.types','in','1')
                ->where('mw.number','>',0)
                ->sum('mw.number'); 
			$recharge_lev[$i]['L3'] = DB::table('my_wallet_log')
				->alias('mw')
				->join('user u','mw.uid = u.id')
				->where('u.lv = 3')
                ->whereTime('mw.create_time',[$time[$i], $time[$x]])
                ->where('mw.types','in','1')
                ->where('mw.number','>',0)
                ->sum('mw.number');
			$recharge_lev[10]['L0'] = DB::table('my_wallet_log')
				->alias('mw')
				->join('user u','mw.uid = u.id')
				->where('u.lv = 0')
                ->whereTime('create_time',[$time[$i], $time[$x]])
                ->where('mw.types','in','1')
                ->where('mw.number','>',0)
                ->sum('mw.number');
			$recharge_lev[10]['L1'] = DB::table('my_wallet_log')
				->alias('mw')
				->join('user u','mw.uid = u.id')
				->where('u.lv = 1')
                ->whereTime('create_time',[$time[$i], $time[$x]])
                ->where('mw.types','in','1')
                ->where('mw.number','>',0)
                ->sum('mw.number'); 
			$recharge_lev[10]['L2'] = DB::table('my_wallet_log')
				->alias('mw')
				->join('user u','mw.uid = u.id')
				->where('u.lv = 2')
                ->whereTime('create_time',[$time[$i], $time[$x]])
                ->where('mw.types','in','1')
                ->where('mw.number','>',0)
                ->sum('mw.number'); 
			$recharge_lev[10]['L3'] = DB::table('my_wallet_log')
				->alias('mw')
				->join('user u','mw.uid = u.id')
				->where('u.lv = 3')
                ->whereTime('create_time',[$time[$i], $time[$x]])
                ->where('mw.types','in','1')
                ->where('mw.number','>',0)
                ->sum('mw.number'); 
			$level_total['L0'] = DB::table('my_wallet_log')
				->alias('mw')
				->join('user u','mw.uid = u.id')
				->where('u.lv = 0')
                ->where('mw.types','in','1')
                ->where('mw.number','>',0)
                ->sum('mw.number'); 
			$level_total['L1'] = DB::table('my_wallet_log')
				->alias('mw')
				->join('user u','mw.uid = u.id')
				->where('u.lv = 1')
                ->where('mw.types','in','1')
                ->where('mw.number','>',0)
                ->sum('mw.number'); 
			$level_total['L2'] = DB::table('my_wallet_log')
				->alias('mw')
				->join('user u','mw.uid = u.id')
				->where('u.lv = 2')
                ->where('mw.types','in','1')
                ->where('mw.number','>',0)
                ->sum('mw.number'); 
			$level_total['L3'] = DB::table('my_wallet_log')
				->alias('mw')
				->join('user u','mw.uid = u.id')
				->where('u.lv = 3')
                ->where('mw.types','in','1')
                ->where('mw.number','>',0)
                ->sum('mw.number'); 
        }
        $lookBait = $look_arr;
		$look_level = $recharge_lev;
		$look_level_total =  $level_total;

        unset($look_arr);
        $look_arr = array();
        for ($x=2; $x<=11; $x++) {
            $i = $x - 1;

            $look_arr[$i]['num'] = DB::table('my_wallet_log')
                ->whereTime('create_time',[$time[$i], $time[$x]])
                ->where('types','in','1,2,3')
                ->where('number','<',0)
                ->sum('number');

            $look_arr[10]['num'] =  DB::table('my_wallet_log')->whereTime('create_time',[$time[$i], $time[$x]])
                ->where('types','in','1,2,3')->where('number','<',0)->sum('number');
        }
        $lookconsumeBait = $look_arr;

       

        $data = [
            'user' => $user,
            'pre' => $pre,
            'adopt' => $adopt,
            'contract' => $contract,
            'time' => $this->get_weeks(10),
            'address_user' => $address_user,
            'lookturn' => $lookturn,               //转让统计
            'lookBait' => $lookBait,               //GTC充值统计
			'lookLevel'=>$look_level,
			'level_total'=>$level_total,
            'lookconsumeBait' => $lookconsumeBait,               //GTC消耗统计
        ];
        
        return $this->render('index', $data);
    }

    function get_weeks($day=11,$time = '', $format='Y-m-d'){
        $time = $time != '' ? $time : time();
        //组合数据
        $date = [];
        for ($i=1; $i<=$day; $i++){
            $date[$i] = date($format ,strtotime( '+' . $i-$day .' days', $time));
        }
        return $date;
    }



    //修改密码
    public function updateInfo(Request $request)
    {
        if ($request->isPost()) {
            $validate = $this->validate($request->post(), '\app\admin\validate\ChangePassword');

            if ($validate !== true) {
                throw new AdminException($validate);
            }

            //判断原密码是否相等
            $model = new \app\admin\service\rbac\Users\Service();
            $user = ManageUser::where('id', $model->getManageId())->find();
            $oldPassword = $model->checkPassword($request->post('old_password'), $user);
            if (!$oldPassword) {
                throw new AdminException('原密码错误');
            }

            $user->password = $model->getPassword($request->post('password'), $user->getPasswordSalt());

            if ($user->save() === false) {
                throw new AdminException('修改失败');
            }

            return json(['code' => 0, 'message' => '修改成功', 'toUrl' => url('login/index')]);
        }
        return $this->render('change');
    }

    //获取交易数据
    protected function getOrders()
    {
        $match = Orders::where('status', Orders::STATUS_DEFAULT)->sum('number');
        $pay = Orders::where('status', Orders::STATUS_PAY)->sum('number');
        $confirm = Orders::where('status', Orders::STATUS_CONFIRM)->sum('number');
        $finish = Orders::where('status', Orders::STATUS_FINISH)->sum('number');

        return [
            'match' => $match,
            'pay' => $pay,
            'confirm' => $confirm,
            'finish' => $finish
        ];

    }

    //统计功能 会员等级处理
    protected function getLevel()
    {
        $model = new User();
        $userTable = $model->getTable();
        $sql = <<<SQL
SELECT count(*) as total,`level` FROM {$userTable} GROUP BY `level`
SQL;
        $userLevel = Db::query($sql);
        $data = [];
        foreach ($userLevel as $item) {
            $data[$item['level']] = $item['total'];
        }
        return $data;
    }


    //退出系统
    public function logout()
    {
        $service = new Service();
        $service->logout();

        $this->redirect('admin/login/index');
    }

    public function clear()
    {
        //清除所有session
        Session::destroy();
    }
}