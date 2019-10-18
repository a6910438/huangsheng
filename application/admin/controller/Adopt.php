<?php

namespace app\admin\controller;

use app\admin\exception\AdminException;

use app\common\entity\TransferLog;
use app\common\entity\ProductPool as ProductPoolModel;
use app\common\entity\Fish as FishModel;
use app\common\entity\User as userModel;
use app\common\entity\UserMagicLog;
use app\common\entity\Export;
use app\common\entity\ProductPool;

use app\common\service;
use think\Db;
use think\Request;
use service\LogService;
use think\Session;

class Adopt extends Admin {

    /**
     * @power 产品管理|产品列表
     * @rank 1
     */
    public function index(Request $request) {

        $stime = 0;
        $ntime = 0;
		$where = array();
        if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');
           if(empty($stime)){
               $stime = time();
           }else{
               $stime = strtotime($stime);
           }
            if(empty($ntime)){
                $ntime = time();
            }else{
                $ntime = strtotime($ntime);
            }
			if($stime >= $ntime){
                $this->error('开始时间必须小于结束时间');
            }
			$where = ['au.create_time'=>['between time',[$stime,$ntime]]];
            $map['stime'] = date('Y-m-d',$stime);
            $map['ntime'] = date('Y-m-d',$ntime);
        }

        $entity = ProductPoolModel::where('is_delete',0);

        $list = $entity
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
		$make = 0;$adopt = 0;$contract = 0;
        foreach ($list as $k => $v){
            $list[$k]['MakeAllNum'] =   getMakeAllNum($v['id'],$stime,$ntime);
            $list[$k]['AdoptAllNum'] =  getAdoptAllNum($v['id'],$stime,$ntime);
            $list[$k]['ContractAllNum'] = getContractAllNum($v['id'],$stime,$ntime);
        }
		$make = Db::table('appointment_user')
			->alias('au')
			->join('bathing_pool b','au.pool_id = b.id')
			->where('b.is_delete',0)
			->where($where)
			->where('au.types',0)
			->count('au.id');
		$adopt = Db::table('appointment_user')
			->alias('au')
			->join('user u','u.id = au.uid')
			->join('bathing_pool bp','bp.id = au.pool_id')
			->where('bp.is_delete',0)
			->where('au.status','not in','0,-2')
			->where($where)
			->count('au.id');

		$contract =getContractAllpoolNum($stime,$ntime);
        return $this->render('index', [
            'list' => $list,
			'make' =>$make,
			'adopt'=>$adopt,
			'contract'=>$contract,
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);
    }


    /**
     * 预约统计
     * @param Request $request
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function make_statistics(Request $request) {

       $id = $request->param('id');

        if(empty($id)){
            $this->error('缺失参数');
        }
        $stime = 0;
        $ntime = 0;
        if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');
            if(empty($stime)){
                $stime = time();
            }else{
                $stime = strtotime($stime);
            }
            if(empty($ntime)){
                $ntime = time();
            }else{
                $ntime = strtotime($ntime);
            }
			if($stime >= $ntime){
                $this->error('开始时间必须小于结束时间');
            }
            $map['stime'] = date('Y-m-d',$stime);
            $map['ntime'] = date('Y-m-d',$ntime);
			
        }
		$map['id'] = $id;


        $entity = Db::table('appointment_user')
            ->alias('au')
            ->join('user u','u.id = au.uid')
			->join('user_invite_code uic','uic.user_id = u.id')
            ->join('bathing_pool bp','bp.id = au.pool_id')
            ->where('au.pool_id',$id)
            ->field('uic.invite_code nick_name,u.mobile,au.create_time,au.status,bp.name');
        if($stime){
            $entity->whereTime('au.create_time',[$stime, $ntime]);
        }
        $list = $entity
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);




        return $this->render('make', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);
    }

    /**
     * 领取统计
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function adopt_statistics(Request $request) {

       $id = $request->param('id');

        if(empty($id)){
            $this->error('缺失参数');
        }
        $stime = 0;
        $ntime = 0;
        if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');

            if(empty($stime)){
                $stime = time();
            }else{
                $stime = strtotime($stime);
            }
            if(empty($ntime)){
                $ntime = time();
            }else{
                $ntime = strtotime($ntime);
            }
            if($stime >= $ntime){
                $this->error('开始时间必须小于结束时间');
            }
            $map['stime'] = date('Y-m-d',$stime);
            $map['ntime'] = date('Y-m-d',$ntime);
			
        }
		$map['id'] = $id;



        $entity = Db::table('appointment_user')
            ->alias('au')
            ->join('user u','u.id = au.uid')
			->join('user_invite_code uic','u.id = uic.user_id')
            ->join('bathing_pool bp','bp.id = au.pool_id')
            ->where('au.pool_id',$id)
            ->where('au.status','not in','0,-2')
            ->field('uic.invite_code nick_name,u.mobile,au.create_time,au.status,bp.name,au.oid');

        if($stime){
            $entity->whereTime('au.create_time',[$stime, $ntime]);
        }
        $list = $entity
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ])
			->each(function($item,$key){
				if($item['oid']>0){
					$fu = Db::table('fish_order')
						->alias('fo')
						->join('fish f','f.id = fo.f_id')
						->join('user fu','fu.id = f.u_id')
						->join('user_invite_code uic','fu.id = uic.user_id')
						->field('fu.mobile fmobile,uic.invite_code fnick_name')
						->where(['fo.id'=>$item['oid']])
						->find();
					$item['fnick_name'] = $fu['fnick_name'];
					$item['fmobile'] = $fu['fmobile'];
				}else{
					$item['fnick_name'] = '';
					$item['fmobile'] = '';
				}
				return $item;
			});




        return $this->render('adopt', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);
    }


    /**
     * 合约统计
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function contrac_statistics(Request $request) {

        $id = $request->param('id');

        if(empty($id)){
            $this->error('缺失参数');
        }
        $stime = 0;
        $ntime = 0;
        if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');

            if(empty($stime)){
                $stime = time();
            }else{
                $stime = strtotime($stime);
            }
            if(empty($ntime)){
                $ntime = time();
            }else{
                $ntime = strtotime($ntime);
            }
			if($stime >= $ntime){
                $this->error('开始时间必须小于结束时间');
            }
            $map['f.stime'] = date('Y-m-d',$stime);
            $map['f.ntime'] = date('Y-m-d',$ntime);
        }
		$map['id'] = $id;

        $entity = Db::table('fish')
            ->alias('f')
            ->join('bathing_pool bp','bp.id = f.pool_id')
            ->join('user fu','fu.id = f.u_id')
			->join('user_invite_code uic','uic.user_id = fu.id')
			->join('fish_order fo','fo.id = f.order_id')
			->join('appointment_user au','au.id = fo.types')
			->join('fish nf','nf.id = au.new_fid');

        if($stime){
//            $entity->whereTime('fo.create_time',[$stime, $ntime]);
            $entity->where('fo.create_time','>',$stime);
            $entity->where('fo.create_time','<',$ntime);
        }

        $entity->where('f.pool_id',$id)
            ->where('f.status','in','4,-3')
            ->where('f.is_status','in','1')
            ->field('uic.invite_code fnick_name,fu.mobile fmobile,f.status,nf.worth,bp.name');

        $list['list'] = $entity
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);




        return $this->render('contrac', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);
    }
	
	//用户节点统计
	public function userTj(Request $request){
		$where = array();
		$fwhere = array();
		if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');

            if(empty($stime)){
                $stime = time();
            }else{
                $stime = strtotime($stime);
            }
            if(empty($ntime)){
                $ntime = time();
            }else{
                $ntime = strtotime($ntime);
            }
			if($stime >= $ntime){
                $this->error('开始时间必须小于结束时间');
            }
            $map['au.stime'] = date('Y-m-d',$stime);
            $map['au.ntime'] = date('Y-m-d',$ntime);
			$where = ['register_time'=>['between time',[$stime,$ntime]]];
			$fwhere = ['create_time'=>['between time',[$stime,$ntime]]];
        }
		//高级节点
		$L3 = Db::table('user')->where(['lv'=>3,'status'=>1])->where($where)->count();
		//中级节点
		$L2 = Db::table('user')->where(['lv'=>2,'status'=>1])->where($where)->count();
		//初级节点
		$L1 = Db::table('user')->where(['lv'=>1,'status'=>1])->where($where)->count();
		//普通会员
		$L0 = Db::table('user')->where(['lv'=>0,'status'=>1])->where($where)->count();
		//注册
		$reg = Db::table('user')->where(['register_time'=>['>',strtotime(date('Y-m-d'))]])->where($where)->count();
		//激活
		$act = Db::table('user')->where(['active_time'=>['>',strtotime(date('Y-m-d'))]])->where($where)->count();

        //激活
        $allact = Db::table('user')->where(['status'=>1])->where($where)->count();

		//交易会员--即注册激活成功之后至少成功领取过1条酒的会员
		$valid = Db::table('user')->alias('u')->where(['u.status'=>1])->join('fish f','f.u_id = u.id')->where($fwhere)->group('f.u_id')->count();
		//所有会员
		$all = Db::table('user')->where($where)->count();
		return $this->render('userTj', [
						'L3'=>$L3,
						'L2'=>$L2,
						'L1'=>$L1,
						'L0'=>$L0,
						'reg'=>$reg,
						'act'=>$act,
						'allact'=>$allact,
						'valid'=>$valid,
						'all'=>$all
        ]);
	}
	
	public function fish(Request $request){
		$where = array();
		if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');

            if(empty($stime)){
                $stime = time();
            }else{
                $stime = strtotime($stime);
            }
            if(empty($ntime)){
                $ntime = time();
            }else{
                $ntime = strtotime($ntime);
            }
			if($stime >= $ntime){
                $this->error('开始时间必须小于结束时间');
            }
            $map['au.stime'] = date('Y-m-d',$stime);
            $map['au.ntime'] = date('Y-m-d',$ntime);
			$where = ['create_time'=>['between time',[$stime,$ntime]]];
        }
		/*if(!$request->get('stime') && !$request->get('ntime')){
			$where = ['create_time'=>['between time',[strtotime(date('Y-m-d')),time()]]];
			$map['au.stime'] = date('Y-m-d',time());
		}*/
		//后台放酒
		$system_num = Db::table('fish')
					->where($where)
					->where(['types'=>['in',[0,6]],'front_id'=>0])
					->count();
		$system_worth = Db::table('fish')
					->where($where)
					->where(['types'=>['in',[0,6]],'front_id'=>0])
					->sum('worth');
		//酒馆放酒
		$fish_num = Db::table('fish')
					->where($where)
					->where(['types'=>['not in',[0,6]]])
					->count();
		$fish_worth = Db::table('fish')
					->where($where)
					->where(['types'=>['not in',[0,6]]])
					->sum('worth');
		//所有酒
		$all_num = Db::table('fish')
					->where($where)
//					->where(['is_delete'=>0])
					->count();
		$all_worth = Db::table('fish')
					->where($where)
//					->where(['is_delete'=>0])
					->sum('worth');
		return $this->render('fish', [
				'list'=>[
							['name'=>'后台放酒','num'=>$system_num,'worth'=>$system_worth],
							['name'=>'非后台放酒','num'=>$fish_num,'worth'=>$fish_worth],
							['name'=>'平台总放酒','num'=>$all_num,'worth'=>$all_worth]
						]
        ]);
	}
	
	public function fish_detail(Request $request){
		$where = array();
		$id = $request->param('id');
		$map['id'] = $id;
		if(empty($id)){
			$this->error('缺失参数');
		}
		if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');

            if(empty($stime)){
                $stime = time();
            }else{
                $stime = strtotime($stime);
            }
            if(empty($ntime)){
                $ntime = time();
            }else{
                $ntime = strtotime($ntime);
            }
			if($stime >= $ntime){
                $this->error('开始时间必须小于结束时间');
            }
            $map['stime'] = date('Y-m-d',$stime);
            $map['ntime'] = date('Y-m-d',$ntime);
			$where = ['f.create_time'=>['between time',[$stime,$ntime]]];
        }
		/*if(!$request->get('stime') && !$request->get('ntime')){
			$where = ['f.create_time'=>['between time',[strtotime(date('Y-m-d')),time()]]];
			$map['au.stime'] = date('Y-m-d',time());
		}*/
		//后台放酒
		$entity = Db::table('fish')
					->alias('f')
					->join('bathing_pool bp','bp.id = f.pool_id')
					->join('user_invite_code uic','f.u_id = uic.user_id')
					->where($where)
					->field('bp.name,f.id,f.worth,uic.invite_code,f.create_time')
//					->where(['f.is_delete'=>0])
        ;
		if($id == 1){
			$entity->where(['f.front_id'=>0,'f.types'=>['in',[0,6]]]);
		}else if($id == 2){
			$entity->where(['f.types'=>['not in',[0,6]]]);
		}else if($id == 3){
			$entity->where([]);
		}
		$list = $entity
				->order('f.create_time desc')
				->paginate(15, false, [
					'query' => isset($map) ? $map : []
				])
				->each(function($item,$key){
					$item['create_time'] = date('Y-m-d H:i:s',$item['create_time']);
					return $item;
				});
		
		
		return $this->render('fish_detail', [
				'list'=>$list,
				'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);
	}
	
	public function food(Request $request){
		$where = array();$where1 = array();
		if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');
            
            if(empty($stime)){
                $stime = time();
            }else{
                $stime = strtotime($stime);
            }
            if(empty($ntime)){
                $ntime = time();
            }else{
                $ntime = strtotime($ntime);
            }
			if($stime >= $ntime){
                $this->error('开始时间必须小于结束时间');
            }
            $map['au.stime'] = date('Y-m-d',$stime);
            $map['au.ntime'] = date('Y-m-d',$ntime);
			$where = ['create_time'=>['between time',[$stime,$ntime]]];
			$where1 = ['mwl.create_time'=>['between time',[$stime,$ntime]]];
        }
		$total = Db::table('my_wallet_log')
					->where($where)
					->where(['types'=>1,'number'=>['>',0]])
					->sum('number');
		$L0 = Db::table('my_wallet_log')
					->alias('mwl')
					->join('user u','u.id = mwl.uid')
					->field('u.lv,mwl.number')
					->where($where1)
					->where(['mwl.types'=>1,'mwl.number'=>['>',0],'lv'=>0])
					->sum('mwl.number');
		$L1 = Db::table('my_wallet_log')
					->alias('mwl')
					->join('user u','u.id = mwl.uid')
					->field('u.lv,mwl.number')
					->where($where1)
					->where(['mwl.types'=>1,'mwl.number'=>['>',0],'lv'=>1])
					->sum('mwl.number');
		$L2 = Db::table('my_wallet_log')
					->alias('mwl')
					->join('user u','u.id = mwl.uid')
					->field('u.lv,mwl.number')
					->where($where1)
					->where(['mwl.types'=>1,'mwl.number'=>['>',0],'lv'=>2])
					->sum('mwl.number');
		$L3 = Db::table('my_wallet_log')
					->alias('mwl')
					->join('user u','u.id = mwl.uid')
					->field('u.lv,mwl.number')
					->where($where1)
					->where(['mwl.types'=>1,'mwl.number'=>['>',0],'lv'=>3])
					->sum('mwl.number');
		return $this->render('food', [
				'list'=>[
						['name'=>'普通会员','number'=>$L0],
						['name'=>'初级节点','number'=>$L1],
						['name'=>'中级节点','number'=>$L2],
						['name'=>'高级节点','number'=>$L3],
						['name'=>'后台充值总计','number'=>$total],
					]
        ]);
	}
	
	function check_details(Request $request){
		$where = array();
		if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');
            
            if(empty($stime)){
                $stime = time();
            }else{
                $stime = strtotime($stime);
            }
            if(empty($ntime)){
                $ntime = time();
            }else{
                $ntime = strtotime($ntime);
            }
			if($stime >= $ntime){
                $this->error('开始时间必须小于结束时间');
            }
            $map['au.stime'] = date('Y-m-d',$stime);
            $map['au.ntime'] = date('Y-m-d',$ntime);
			$where = ['mwl.create_time'=>['between time',[$stime,$ntime]]];
        }
		if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'ids':
                    $where = ['uic.invite_code'=>[ 'like','%'.$keyword.'%']];
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
		$type = $request->param('type');
		if($type == 4){
			$user = [];
		}else{
			$user['u.lv'] = $type;
		}
		$list = Db::table('my_wallet_log')
					->alias('mwl')
					->join('user u','u.id = mwl.uid')
					->join('user_invite_code uic','u.id = user_id')
					->field('SUM(number) as number,u.lv,uic.invite_code')
					->where($where)
					->where($user)
					->where(['mwl.types'=>1,'mwl.number'=>['>',0]])
					->group('mwl.uid')
					->order('mwl.create_time desc')
					->paginate(15, false, [
						'query' => isset($map) ? $map : []
					]);
		return $this->render('check_details',[
				'list'=>$list
			]);
	}
	

}
