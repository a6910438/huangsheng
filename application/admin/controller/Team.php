<?php
namespace app\admin\controller;

use app\admin\validate\Recharge;
use app\common\entity\ActiveApply;
use app\common\entity\StoreLog;
use app\common\entity\Team as teamModel;
use app\common\entity\User;
use app\common\entity\Linelist;
use app\common\entity\Withdraw;
use app\common\entity\TeamReport;

use think\Request;
use think\Db;

class Team extends Admin
{
    /**
     * @power 团队管理|团队统计
     * @rank 4
     */
    public function count(Request $request)
    {
        $list = teamModel::alias('t')
            ->leftjoin('user u','u.id = t.leader')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('count',[
            'list' => $list,
        ]);
    }

    /**
     * @power 团队管理|团队长列表
     * @method GET
     */
    public function leadersList(Request $request)
    {
        set_time_limit(600);
        $entity = TeamReport::alias('t');
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('t.nick_name', 'like','%'.$keyword.'%');
                    break;
                case 'ids':
                    $entity->where('t.invite_code', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $list = $entity
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);

        $query = new User();
        $team = new \app\common\entity\Team();
        return $this->render('count',[
            'list' => $list,
            'query' => $query,
            'team' => $team,
        ]);
    }
    /**
     * @power 团队管理|团队长详情列表
     * @method GET
     */
    public function leadersListDetail(Request $request)
    {
        $id = $request->param('id');
        $count = User::where('pid',$id)->count();
        if(!$count){
            return json(['code'=>1]);
        }

        $entity = User::alias('u')
            ->join('user_invite_code uic','uic.user_id = u.id')
            ->where('u.status',1)
            ->where('u.is_active',1)
            ->field('u.*,uic.invite_code,mw.old,mw.now');


        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->where('u.nick_name', 'like','%'.$keyword.'%');
                    break;
                case 'ids':
                    $entity->where('uic.invite_code', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $list = $entity
            ->leftJoin('my_wallet mw','mw.uid = u.id')
            ->where('u.pid',$id)
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        foreach ($list as $v){


            $leader = User::alias('u')
                ->join('user_invite_code uic','uic.user_id = u.id')
                ->where('u.id',$v['pid'])->value('uic.invite_code');
            $child = User::field('id,status')->where('pid',$v['id'])->select();
			//团队所有成员
			$allID = array(0=>$v['id']);
			(new User())->getTeamUserIdn($v['id'],$allID);
			
            $t_num = 0;
            (new User())->getTeamZTNum($v['id'],$t_num); //团队人数

            $adopt_num = Db::table('appointment_user')
							->where('uid',$v['id'])
							->where('new_fid','>',0)
							->where('status',4)
							->count('id');
            (new User())->getTeamAdoptFishNum($v['id'],$adopt_num);////领取酒数
            $pre_num = Db::table('appointment_user')
						->where('uid',$v['id'])
						->count('id');
            (new User())->getTeamPreNum($v['id'],$pre_num);//预约酒数
            $now_num = $v['now'];
            (new User())->genTeamNowWallet($v['id'],$now_num);//GTC数

            $addp = Db::table('my_wallet_log')
					->where(['uid'=>$v['id'],'number'=>['>',0]])
					->where('types','in','1,5')
					->where(['from_id'=>['not in',$allID]])
					->sum('number');
            (new User())->genTeamAddWallet($v['id'],$addp,$allID);//团队GTC充值
			//团队消耗GTC
            $my_bait = \think\Db::table('my_wallet_log')
            ->where('uid',$v['id'])
            ->where('types','in','2,3,6,4')
            ->sum('number')??0;
			$my_bait2 = \think\Db::table('my_wallet_log')
				->where('uid',$v['id'])
				->where('types','in','1,5')
				->where(['from_id'=>['not in',$allID]])
				->where('number','<',0)
				->sum('number')??0;
			$reducep = abs($my_bait)+abs($my_bait2);
            (new User())->genTeamReduceWallet($v['id'],$reducep,$allID);

			$total_pro = User::where(['id'=>['in',$allID]])->sum('profit');
			$total_prohibit = User::where(['id'=>['in',$allID]])->sum('now_prohibit_integral');
			$total_team = User::where(['id'=>['in',$allID]])->sum('now_team_integral');

            $v['t_num'] = $t_num?$t_num:0;
            $v['adopt_num'] = $adopt_num?$adopt_num:0;//领取酒数
            $v['pre_num'] = $pre_num?$pre_num:0;//预约酒数
            $v['now_num'] = $now_num?$now_num:0;//GTC数
            $v['addp_num'] = $addp?$addp:0;//添加GTC数
            $v['reducep_num'] = $reducep?$reducep:0;//消耗GTC数
            $v['leader'] = $leader;
			$v['total_pro'] = $total_pro?$total_pro:0;//装修收益
			$v['total_prohibit'] = $total_prohibit?$total_prohibit:0;//团队推广收益
			$v['total_team'] = $total_team?$total_team:0;//团队收益
        }
        $query = new User();
        $team = new \app\common\entity\Team();
        return $this->render('listDetail',[
            'list' => $list,
            'query' => $query,
            'team' => $team,
        ]);
    }

    /**
     * @power 团队管理|团队架构图
     */
    public function framework(Request $request)
    {
        $entity = User::alias('u')
            ->join('user_invite_code uic','uic.user_id = u.id')
            ->where('u.lv','>',0)
            ->where('u.lv','<',4)
            ->field('u.*');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'nick_name':
                    $entity->alias('u')
                        ->where('u.nick_name',$keyword);
                    $my = User::alias('u')
                        ->join('user_invite_code uic','uic.user_id = u.id')
                        ->where('nick_name',$keyword) ->field('u.*,uic.invite_code')->find();
                    break;
                case 'id_number':
                    $entity->alias('u')
                        ->where('uic.invite_code',$keyword);
                    $my = User::alias('u')
                        ->join('user_invite_code uic','uic.user_id = u.id')
                        ->where('uic.invite_code',$keyword) ->field('u.*,uic.invite_code')->find();
                    break;
            }
            
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        if(!$keyword){
            $parent = $entity->where('pid',0)->select();
        }else{
            $parent =   $entity->select();
        }

        $entry = new User();
        foreach ($parent as $k => $v){
            $list = $entry->getChildsInfo($v['id'],3);

        }
        if($keyword){
            $log = [
                'id' => $my['id'],
                'pId' => '',
                'name' => $keyword,
            ];
            $list[] = $log;
        }else{
            $log = [
                'id' => 0,
                'pId' => '',
                'name' => '团队架构图',
            ];
            $list[] = $log;
        }


        $json = json_encode($list);

        return $this->render('framework',[
            'list' => $json,
        ]);
    }

    /**
     * @power 团队管理|团队取款统计
     */
    public function draw(Request $request)
    {
        $entity = teamModel::alias('t')
            ->field('u.*');

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
        $list = $entity
            ->leftjoin('user u','u.id = t.leader')
            ->where('u.status',1)
            ->paginate(1,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        foreach ($list as $v){
            $idArr = (new User())->getAllChildsInfo($v['id']);
            $arr = [];
            foreach ($idArr as $value){
                $arr[] = $value['id'];
            }
            $totalMy = Withdraw::where('uid',$v['id'])->where('status',3)->sum('total');//已提现金额
            $store_order = Withdraw::whereIn('uid',$arr)->where('status',3)->count();
            $total = Withdraw::whereIn('uid',$arr)->where('status',3)->sum('total');//已提现金额
            $child = User::field('id,status')->where('pid',$v['id'])->select();
            //团队总人数
            $teamCount = (new User())->getTeamNum($child);
            //个人盈亏  已提现的金额减去冻结中资金
            $v['store_order'] = $store_order?$store_order:0;
            $v['teamCount'] = $teamCount;
            $v['teamTakeMoney'] = $total + $totalMy;

        }
        return $this->render('draw',[
            'list' => $list,
        ]);
    }
    /**
     * @power 团队管理|团队取款详情
     */
    public function drawDetail(Request $request)
    {
        $id = $request->param('id');
        $idArr = (new User())->getAllChildsInfo($id);

        foreach ($idArr as $v){
            $arr[] = $v['id'];
        }
        $entity = Withdraw::alias('w')
            ->field('w.*,u.nick_name,u.id as uid');
        if ($status = $request->get('status')) {
            $entity->where('w.status', $status);
            $map['status'] = $status;
        }
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
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $entity->where('w.create_time', '<', strtotime($endTime))
                ->where('w.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $data = $entity
            ->leftJoin('user u','w.uid = u.id')
            ->whereIn('u.id',$arr)
            ->where('u.status',1)
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        $query = new Withdraw();
        return $this->render('drawDetail',[
            'list' => $data,
            'query' => $query,
        ]);
    }
    /**
     * @power 团队管理|团队冻结统计
     */
    public function freeze(Request $request)
    {
        $entity = teamModel::alias('t')
            ->field('u.*');

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
        $list = $entity
            ->leftjoin('user u','u.id = t.leader')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        foreach ($list as $v){
            $idArr = (new User())->getAllChildsInfo($v['id']);
            $arr = [];
            foreach ($idArr as $value){
                $arr[] = $value['id'];
            }
            $freeze_order = StoreLog::whereIn('uid',$arr)->where('status',1)->count();
            $total = StoreLog::whereIn('uid',$arr)->where('status',1)->sum('num');//冻结中的金额
            $child = User::field('id,status')->where('pid',$v['id'])->select();
            //团队总人数
            $teamCount = (new User())->getTeamNum($child);
            //个人盈亏  已提现的金额减去冻结中资金
            $v['freeze_order'] = $freeze_order?$freeze_order:0;
            $v['teamCount'] = $teamCount;
            $v['total'] = $total;
        }
        return $this->render('freeze',[
            'list' => $list,
        ]);

    }
    /**
     * @power 团队管理|团队冻结详情
     */
    public function freezeDetail(Request $request)
    {
        $id = $request->param('id');
        $idArr = (new User())->getAllChildsInfo($id);

        foreach ($idArr as $v){
            $arr[] = $v['id'];
        }
        $entity = StoreLog::alias('sl')
            ->field('sl.*,u.nick_name,u.id as uid');
        if ($status = $request->get('status')) {
            $entity->where('w.status', $status);
            $map['status'] = $status;
        }
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
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $entity->where('sl.create_time', '<', strtotime($endTime))
                ->where('sl.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $data = $entity
            ->leftJoin('user u','sl.uid = u.id')
            ->whereIn('u.id',$arr)
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        return $this->render('freezeDetail',[
            'list' => $data,
        ]);
    }
    /**
     * @power 团队管理|团队存款统计
     */
    public function deposit(Request $request)
    {
        $entity = teamModel::alias('t')
            ->field('u.*');

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
        $list = $entity
            ->leftjoin('user u','u.id = t.leader')
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);
        foreach ($list as $v){
            $idArr = (new User())->getAllChildsInfo($v['id']);
            $arr = [];
            foreach ($idArr as $value){
                $arr[] = $value['id'];
            }
            $store_num = Linelist::where('uid',$v['id'])->whereIn('status',[1,2])->sum('overmoney');
            $store_order = Linelist::whereIn('uid',$arr)->whereIn('status',[1,2])->count();
            $total = Linelist::whereIn('uid',$arr)->whereIn('status',[1,2])->sum('overmoney');//冻结中的金额
            $child = User::field('id,status')->where('pid',$v['id'])->select();
            //团队总人数
            $teamCount = (new User())->getTeamNum($child);
            //个人盈亏  已提现的金额减去冻结中资金
            $v['store_order'] = $store_order?$store_order:0;
            $v['teamCount'] = $teamCount;
            $v['total'] = $total + $store_num;
        }
        return $this->render('deposit',[
            'list' => $list,
        ]);
    }
    /**
     * @power 团队管理|团队存款详情
     */
    public function depositDetail(Request $request)
    {
        $id = $request->param('id');
        $idArr = (new User())->getAllChildsInfo($id);

        foreach ($idArr as $v){
            $arr[] = $v['id'];
        }
        $entity = Linelist::alias('ll')
            ->field('ll.*,u.nick_name,u.id as uid');
        if ($status = $request->get('status')) {
            $entity->where('ll.status', $status);
            $map['status'] = $status;
        }
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
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $entity->where('ll.create_time', '<', strtotime($endTime))
                ->where('ll.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $data = $entity
            ->leftJoin('user u','ll.uid = u.id')
            ->whereIn('u.id',$arr)
            ->paginate(15,false,[
                'query' => $request->param()?$request->param():[],
            ]);

        $query = new Withdraw();
        return $this->render('depositDetail',[
            'list' => $data,
            'query' => $query,
            'cate' => (new Linelist())->getAllCate(),
            'type' => (new Linelist())->getAllType(),
        ]);
    }

    public function upPid(Request $request){
        $id = $request->param('id');
        $info = Db::table('user')->where('id',$id)->find();
        return $this->render('edit',[
            'info' => $info
        ]);
    }

    public function updatePid(Request $request){
        $pid = $request->param('pid');
        $id = $request->param('id');
        if($pid){
            $parent = Db::table('user')->where('id',$pid)->find();
            if(!$parent){
                return json(['code'=>1,'message'=>'该上级不存在,请重新输入']);
            }
        }else{
            return json(['code'=>1,'message'=>'没有输入上级信息']);
        }
        $res = Db::table('user')->where('id',$id)->update(['pid'=>$pid]);
        if($res){
            return json(['code'=>0,'message'=>'修改成功']);
        }else{
            return json(['code'=>1,'message'=>'修改失败']);
        }
    }


}
