<?php
namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\common\entity\User;
use app\common\entity\LegalDeal;
use app\common\entity\LegalList;
use app\common\entity\LegalWallet;
use app\common\entity\ManageGroup;
use app\common\entity\ManageUser;
use app\common\entity\ManageUserGroup;
use app\common\entity\FomoWithdraw;
use app\common\entity\FomoConfig;
use app\common\entity\FomoTeam;
use app\common\entity\FomoGame;
use app\common\entity\Buykey;
use app\common\entity\Bonus;
use app\common\entity\FomoGameDrawlog;
use app\common\entity\InviteaWard;
use app\common\entity\FomoAirdrop;
use app\common\entity\FomoAirdropLog;
use app\common\entity\FomoRecharge;
use app\common\entity\UserMagicLog;
use app\index\model\Market;
use think\Request;
use think\Db;
use app\common\command\InitMenu;
use app\common\entity\Export;


class Fomo extends Admin
{   

    /**
     * @power FOMO管理|基础设置
     * @method POST
     */
    public function set(Request $request)
    {   	

        $FomoConfig = new FomoConfig();
        $info = $FomoConfig->where('key','path')->select();
        $info  = $info[0];

    	return $this->render('set', [
            'list' => $FomoConfig->where('type', 1)->where('status',1)->select(),
            'info'=> $info,
        ]);
    }

    /**
     * 添加设置
     */
    public function setadd(Request $request)
    {	
    	$config = new FomoConfig();
    	$config->name = $request->post('name');
        $config->key = $request->post('key');
        $config->value = $request->post('value');
    	
        if ($config->save() === false) {
            throw new AdminException('添加失败');
        }
        return ['code' => 0, 'message' => '添加成功'];
    }


    /**
     * 添加设置
     */
    public function savePic(Request $request)
    {   

        $path = $request->post('paths');

        if (empty($path)) {
            throw new AdminException('添加失败');
        }

        //查看当前有没有存进去
        $config = new FomoConfig();
        $entity = $config->where('key','path')->find();
    

        if($entity){
            $result = $config->where('id',$entity['id'])->update(['value'=>$path]);
        }else{
            $config->name = '收款二维码';
            $config->key = 'path';
            $config->value = $path;
            $result = $config->save();
        }
       
        
        if ($result === false) {
            throw new AdminException('添加失败');
        }

        return ['code' => 0, 'message' => '添加成功'];
    }




    /**
     * 保存设置
     */
    public function setsave(Request $request)
    {
        $key = $request->post('key');
        $value = $request->post('value');
        $config = FomoConfig::where('key', $key)->find();
        if (!$config) {
            throw new AdminException('操作错误');
        }
        $config->value = $value;
        if ($config->save() === false) {
            throw new AdminException('修改失败');
        }
        return ['code' => 0, 'message' => '配置成功'];
    }
    
    /**
     * @power FOMO管理|游戏管理
     * @method POST
     */
    public function index(Request $request)
    {       
        $entity = FomoGame::field('*');

        if ($keyword = $request->get('id','')) {
            $entity->where('id',$keyword);
        }

        $entity->order('status','DESC');

        return $this->render('index', [
            'list' => $entity->paginate(15),
            'keyword'=>$keyword
        ]);
    }

    //队伍编辑
    public function teamedit(Request $request)
    {
        $id = request()->get('id', 0);

        $entity = FomoGame::where('id', $id)->find()->toArray();
        if (!$entity) {
            $this->error('游戏不存在');
        }	

        $team = FomoTeam::where('status',1)->order('sort','desc')->select()->toArray();

        return $this->render('teamedit', [
            'team'=>$team,
            'id'=>$id
        ]);
    }

     //游戏添加队伍
    public function addteam(Request $request) {

        $res = $this->validate($request->post(), 'app\admin\validate\AddGameForm');
           
        if (true !== $res) {
            return json()->data(['code' => 1, 'message' => $res]);
        }

        $game = FomoGame::where('id', request()->post('id'))->find();

        if(!$game){
        	 return json()->data(['code' => 1, 'message' => '所选游戏不存在']);
        }

        $team = FomoTeam::where('id', request()->post('teamid'))->find()->toArray();

        if(!$team){
        	 return json()->data(['code' => 1, 'message' => '所选队伍不存在']);
        }
        	
        $pond_scale = request()->post('pond_scale')?request()->post('pond_scale'):$team['pond_scale'];
        $bonus_scale = request()->post('bonus_scale')?request()->post('bonus_scale'):$team['bonus_scale'];
        $game['team_ids'] = unserialize($game['team_ids'])?unserialize($game['team_ids']):array();
        $data = array();
        $data['teamid'] = $team['id'];
        $data['title'] = $team['title'];
        $data['image'] = $team['image'];  
        $data['pond_scale'] = $pond_scale;
        $data['bonus_scale'] = $bonus_scale;
        
        if(!$game['team_ids']){
        	$game['team_ids'] = serialize(array($team['id']=>$data));
        }else{
	       	$game['team_ids'] = serialize($game['team_ids']+array($team['id']=>$data)); 
        }	
        
        
        if (!$game->save()) {
            throw new AdminException('添加失败');
        }

        return json(['code' => 0, 'message' => '添加成功']);
    }

    //删除队伍
    public function deleteteam(Request $request) {
    	
    	$id = request()->get('id');

    	$teamid = request()->get('teamid');

        $entity = FomoGame::where('id', $id)->find();

        if (!$entity) {
            throw new AdminException('游戏不存在');
        }

        $team_ids = unserialize($entity['team_ids']);
        unset($team_ids[$teamid]);


        $entity['team_ids'] = serialize($team_ids);

        if (!$entity->save()) {
            throw new AdminException('删除失败');
        }

        return json(['code' => 0, 'message' => 'success']);
    }

    //开始新一期游戏
    public function startgame(Request $request)
    {
        $id = request()->get('id', 0);

        $FomoGameModel = new FomoGame();
        
        $havestart = $FomoGameModel->where('status', 1)->find();
        
        if($havestart){
        	throw new AdminException('已经有游戏在开始,无法开启');
        }

        if ($id) {
            $entity = $FomoGameModel->where('id', $id)->find();

            if (!$entity) {
                throw new AdminException('游戏不存在');
            }
           
           	$result = $FomoGameModel->startGame($entity);
           		
           	if (!$result) {
                throw new AdminException('开启失败');
            }
        }

        return json(['code' => 0, 'message' => 'success']);
    }

     //停止游戏
    public function endgame(Request $request)
    {
        $id = request()->get('id', 0);

        $FomoGameModel = new FomoGame();
        
        if ($id) {
            $entity = $FomoGameModel->where('id', $id)->find();

            if (!$entity) {
                throw new AdminException('游戏不存在');
            }
            $entity->status = 0;

           	if (!$entity->save()) {
                throw new AdminException('停止失败');
            }
        }

        return json(['code' => 0, 'message' => 'success']);
    }

    //游戏编辑
    public function gameedit(Request $request)
    {
        $id = request()->get('id', 0);

        $FomoGameModel = new FomoGame();
        if ($id) {
            $entity = $FomoGameModel->where('id', $id)->find()->toArray();
            if (!$entity) {
                $this->error('用户对象不存在');
            }
            $title = '编辑游戏';
        } else {
            $entity = [
                'id' => 0,
                'time' => 0,
                'play_scale' => 0,
                'team_scale' => 0,
                'bonus_scale' => 0,
            ];
            $title = '添加游戏';
        }
        return $this->render('gameedit', [
                    'title' => $title,
                    'info' => $entity,
        ]);
    }

    
    //添加游戏
    public function savegame(Request $request, $id) {
        $res = $this->validate($request->post(), 'app\admin\validate\GameForm');
           
        if (true !== $res) {
            return json()->data(['code' => 1, 'message' => $res]);
        }

        $FomoGameodel = new FomoGame();
            
        if ($id) {
            $entity = $FomoGameodel->where('id', $id)->find();
            $result = $FomoGameodel->updateGame($entity, $request->post());
        } else {

            $team = FomoTeam::select()->toArray();
            $data = array();
            foreach ($team as $k => $v) {

                $data[$v['id']]['teamid'] = $v['id'];
                $data[$v['id']]['title'] = $v['title'];
                $data[$v['id']]['image'] = $v['image'];  
                $data[$v['id']]['pond_scale'] = $v['pond_scale'];
                $data[$v['id']]['bonus_scale'] = $v['bonus_scale'];
            }

            $FomoGameodel->team_ids = serialize($data);
            
            $FomoGameodel->time = $request->post('time');
            $FomoGameodel->play_scale = $request->post('play_scale');
            $FomoGameodel->team_scale = $request->post('team_scale');
            $FomoGameodel->status = 0;
            $FomoGameodel->createtime = time();

            $result = $FomoGameodel->save();

        }

        if (!$result) {
            throw new AdminException('保存失败');
        }

        return json(['code' => 0, 'toUrl' => url('fomo/index')]);
    }

    //获取队伍信息
    public function getTeamInfo(Request $request){
    	if ($request->isPost()) {
            
            $id = $request->post('id');

            $order = FomoGame::where('id', $id)->find();
            if (!$order) {
                throw new AdminException('游戏不存在');
            }
            if($order['team_ids']){
            	$teams = unserialize($order['team_ids']);
            }else{
            	return json(['code' => 1, 'message' => '暂无队伍']);
            }

            return json(['code' => 0, 'team' => $teams]);
        }
    }

    /**
     * @power FOMO管理|队伍管理
     * @method POST
     */
    public function team(Request $request)
    {       

        $entity = FomoTeam::field('*');
        if ($keyword = $request->get('keyword','')) {
            $entity->where('title','like','%'.$keyword.'%');
        }
        $entity->order('sort','desc');

        return $this->render('team', [
            'list' => $entity->paginate(15),
            'keyword'=>$keyword
        ]);
    }

    //添加队伍
    public function save(Request $request, $id) {
        $res = $this->validate($request->post(), 'app\admin\validate\TeamForm');
           
        if (true !== $res) {
            return json()->data(['code' => 1, 'message' => $res]);
        }

        $FomoTeamodel = new FomoTeam();
            
        if ($id) {
            $entity = $FomoTeamodel->where('id', $id)->find();
            $result = $FomoTeamodel->updateTeam($entity, $request->post());
        } else {
            $result = $FomoTeamodel->addTeam($request->post());
        }

        if (!$result) {
            throw new AdminException('保存失败');
        }

        return json(['code' => 0, 'toUrl' => url('fomo/team')]);
    }

    //删除
    public function delete(Request $request, $id) {

        $entity = FomoGame::where('id', $id)->find();
        // var_dump($entity);

        if (!$entity) {
            throw new AdminException('对象不存在');
        }
        if (!$entity->delete()) {
            throw new AdminException('删除失败');
        }

        return json(['code' => 0, 'message' => 'success']);
    }

    //编辑
    public function edit(Request $request)
    {
        $id = request()->get('id', 0);

        $FomoTeamModel = new FomoTeam();
        if ($id) {
            $entity = $FomoTeamModel->where('id', $id)->find()->toArray();
            if (!$entity) {
                $this->error('用户对象不存在');
            }
            $title = '编辑队伍';
        } else {
            $entity = [
                'id' => 0,
                'title' => '',
                'image' => '',
                'content' => '',
                'intro' => '',
                'pond_scale' => 0,
                'bonus_scale' => 0,
                'next_scale'=>0,
                'win_bonus_scale'=>0,
                'sort' => 0,
                'status' => 0,
            ];
            $title = '添加队伍';
        }
    
        return $this->render('edit', [
                    'title' => $title,
                    'info' => $entity,
        ]);
    }

    /**
     * @power FOMO管理|购买记录
     * @method POST
     */
    public function buy(Request $request)
    {     

        $list = $this->search($request);
           
        return $this->render('list', [
            'list' => $list
        ]);

    }

     /**
     * @power FOMO管理|分红明细
     * @method POST
     */
    public function bonus(Request $request)
    {     

        $list = $this->dealsearch($request);
        return $this->render('deal', [
            'list' => $list
        ]);

    }



     /**
     * @power FOMO管理|客户充值明细
     * @method POST
     */
    public function withdraw(Request $request)
    {     

        $list = $this->withdrawsearch($request);
        return $this->render('withdraw', [
            'list' => $list
        ]);

    }


    protected function withdrawsearch($request)
    {
        $query = FomoWithdraw::alias('o')->field('o.*,s.nick_name,s.mobile');
        
        $status = $request->get('status');

        if ($status) {
            $statuss = $status-2;
            $query->where('o.status', $statuss);
            $map['status'] = $status;
        }

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $query->where('s.mobile','like','%'.$keyword.'%');
                    break;
                case 'wssn':
                    $query->where('o.wssn','like','%'.$keyword.'%');
                    break;
                case 'title':
                    $query->where('t.title','like','%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');

        if($startTime && $endTime){
            $query->where('o.createtime', '<', strtotime($endTime))
            ->where('o.createtime', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }

        $userTable = (new User())->getTable();

        $list = $query->leftJoin("$userTable s", 's.id = o.user_id')
            ->order('createtime', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        return $list;
    }

    /**
     * 导出提现明细
     */
    public function exportwithdraw(){

    }



     /**
     * @power FOMO管理|提现明细@提现审批
     * @method POST
     */
    public function approve(Request $request)
    {     

        $id = $request->get('id')??'';
        $op = $request->get('op')??'';
        
        $log = FomoWithdraw::where('id',$id)->where('status',0)->find();

        if(empty($id) || empty($log)){
            throw new AdminException('操作有误！');
        }


        $FomoWithdraw = new FomoWithdraw();

        //手动打款
        if($op=='pass'){

            $FomoWithdraw->where('id',$id)->update(['examinetime'=>time(),'status'=>1]);

        }else if($op=='reject'){
        //拒绝

            Db::startTrans();

            try {

                $result = $FomoWithdraw->where('id',$id)->update(['examinetime'=>time(),'status'=>-1]);

                if (!$result) {
                throw new \Exception('操作失败');
                }

                //加钱
                $User = new User();
                $User->setBonus($log['user_id'],'bonus',$log['money'],'拒绝提取增加');

                Db::commit();

            } catch (\Exception $e) {

                Db::rollback();
            }

        }

        return json(['code' => 0, 'message' => '操作成功']);

    }


    
    public function iserror(Request $request)
    {     

        $list = $this->dealsearch($request,-2);
        return $this->render('deal', [
            'list' => $list,
            'type' => 4
        ]);

    }

     
    public function update(Request $request)
    {
        if ($request->isPost()) {
            $id = $request->request('id');
            $order = LegalDeal::where('id', $id)->find();
            if (!$order) {
                throw new AdminException('订单不存在');
            }

            $result = $order->payResult($order->sale_id,$id);
            if (!$result) {
                throw new AdminException('操作失败');
            }

            return json(['code' => 0, 'message' => '确认成功']);
        }
    }

   
    public function cancel(Request $request)
    {
        if ($request->isPost()) {
            $id = $request->request('id');
            $order = LegalDeal::where('id', $id)->find();
            if (!$order) {
                throw new AdminException('订单不存在');
            }

            $result = $order->cancelPay($order->buy_id,$id);
            if (!$result) {
                throw new AdminException('操作失败');
            }

            return json(['code' => 0, 'message' => '取消成功']);
        }
    }

    protected function dealsearch($request)
    {
        $query = Bonus::alias('o')->field('o.*,s.nick_name,s.mobile');
        
        $types = $request->get('types');

        // if ($types) {

        //     $query->where('o.types', $types);
        //     $map['types'] = $types;
        // }

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $query->where('s.mobile','like', $keyword);
                    break;
                case 'periods':
                    $query->where('o.periods','like',$keyword);
                    break;
                case 'title':
                    $query->where('t.title','like',$keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');

        if($startTime && $endTime){
            $query->where('o.createtime', '<', strtotime($endTime))

            ->where('o.createtime', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }

        $userTable = (new User())->getTable();

        $list = $query->leftJoin("$userTable s", 's.id = o.user_id')
            ->order('createtime', 'desc')
            ->where('o.types', 1)
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        return $list;
    }


    protected function search($request)
    {
        $query = Buykey::alias('o')->field('o.*,u.nick_name,u.mobile,t.image,t.title');
       
        $status = $request->get('status');

        if ($status) {
            if($status==-1){
                $status = 0;
            }
            $query->where('o.status', $status);
            $map['status'] = $status;
        }

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $query->where('u.mobile','like', $keyword);
                    break;
                case 'periods':
                    $query->where('o.periods','like',$keyword);
                    break;
                case 'title':
                    $query->where('t.title','like',$keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $query->where('o.createtime', '<', strtotime($endTime))
            ->where('o.createtime', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }

        $userTable = (new User())->getTable();
        $teamTable = (new FomoTeam())->getTable();

        $list = $query->leftJoin("$userTable u", 'u.id = o.user_id')
            ->leftJoin("$teamTable t", 't.id = o.teamid')
            ->order('createtime', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        return $list;
    }

    //游戏开奖结果
    public function gameresult(Request $request)
    {     
        $id = request()->get('id', 0);

        $query = FomoGameDrawlog::alias('o')->field('o.*,u.nick_name,u.mobile');
       
        $status = $request->get('status');
        
        if($id){
            $query->where('o.periods', $id);
        }
        
        $types = $request->get('types');

        if ($types) {
            $query->where('o.types', $types);
            $map['types'] = $types;
        }
        // if ($status) {
        //     if($status==-1){
        //         $status = 0;
        //     }
        //     $query->where('o.status', $status);
        //     $map['status'] = $status;
        // }

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $query->where('u.mobile','like', $keyword);
                    break;
                case 'periods':
                    $query->where('o.periods','like',$keyword);
                    break;
                case 'title':
                    $query->where('t.title','like',$keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');

        if($startTime && $endTime){
            $query->where('o.createtime', '<', strtotime($endTime))
            ->where('o.createtime', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }

        $userTable = (new User())->getTable();
       
        $list = $query->leftJoin("$userTable u", 'u.id = o.user_id')
            ->order('o.createtime', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);

        return $this->render('result', [
            'list' => $list,
            'id'=>$id
        ]);

    }


     /**
     * @power FOMO管理|直推明细
     * @method POST
     */
    public function inviteaward(Request $request)
    {     

        $list = $this->inviteasearch($request);
        // print_r($list);
        return $this->render('inviteaward', [
            'list' => $list
        ]);

    }

    protected function inviteasearch($request)
    {
        $query = InviteaWard::alias('o')->field('o.*,s.nick_name,s.mobile,t.nick_name as p_nick_name,t.mobile as p_mobile');
        
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $query->where('s.mobile','like', $keyword);
                    break;
                case 'p_mobile':
                    $query->where('t.mobile','like', $keyword);
                    break;
           
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');

        if($startTime && $endTime){
            $query->where('o.createtime', '<', strtotime($endTime))
            ->where('o.createtime', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $user = new User();
        $userTable = $user->getTable();

        $list = $query->leftJoin("$userTable s", 's.id = o.user_id')
            ->leftJoin("$userTable t", 't.id = o.user_pid')
            ->order('createtime', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);

        return $list;
    }

    /**
     * @power FOMO管理|空投设置
     * @method POST
     */
    public function airdrop(Request $request)
    {       

        $FomoAirdrop = new FomoAirdrop();
        $info = $FomoAirdrop->select()->toArray();
        // var_dump($info);
        return $this->render('airdrop', [
            'list' => $info,
        ]);
    }

    /**
     * 添加空投比例
     */
    public function airdropAdd(Request $request)
    {       
        $config = new FomoAirdrop();
        $config->min_eth = $request->post('min_eth');
        $config->max_eth = $request->post('max_eth');
        $config->proportion = $request->post('proportion');
        
        if ($config->save() === false) {
            throw new AdminException('添加失败');
        }
        return ['code' => 0, 'message' => '添加成功'];

        
    }

    /**
     * 添加空投比例
     */
    public function airdropSave(Request $request)
    {       


        $id = $request->post('id');
        
        $config = FomoAirdrop::where('id', $id)->find();

        if (!$config) {
            throw new AdminException('操作错误');
        }
        $config->min_eth = $request->post('min_eth');
        $config->max_eth = $request->post('max_eth');
        $config->proportion = $request->post('proportion');
        if ($config->save() === false) {
            throw new AdminException('修改失败');
        }
        return ['code' => 0, 'message' => '配置成功'];
        
    }

     /**
     * @power FOMO管理|直推明细
     * @method POST
     */
    public function airdroplog(Request $request)
    {     

        $list = $this->airdroplogsearch($request);
        return $this->render('airdroplog', [
            'list' => $list
        ]);

    }

    protected function airdroplogsearch($request)
    {

        $query = FomoAirdropLog::alias('o')->field('o.*,s.nick_name,s.mobile');
        
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $query->where('s.mobile','like', $keyword);
                    break;

            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');

        if($startTime && $endTime){
            $query->where('o.createtime', '<', strtotime($endTime))
            ->where('o.createtime', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
        $user = new User();
        $userTable = $user->getTable();

        $list = $query->leftJoin("$userTable s", 's.id = o.user_id')
            ->order('createtime', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);

        return $list;
    }


     /**
     * @power FOMO管理|充值明细
     * @method POST
     */
    public function recharge(Request $request)
    {     

        $list = $this->rechargesearch($request);

        return $this->render('recharge', [
            'list' => $list
        ]);

    }

    protected function rechargesearch($request)
    {
        $query = UserMagicLog::alias('o')->field('o.*,s.nick_name,s.mobile,s.trade_address');
        
        $status = $request->get('status');

        if ($status) {
            $statuss = $status;
            $query->where('o.types', $statuss);
            $map['status'] = $status;
        }

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $query->where('s.mobile','like','%'.$keyword.'%');
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');

        // if($startTime && $endTime){
        //     $query->where('o.create_time', '<', strtotime($endTime))
        //     ->where('o.create_time', '>=', strtotime($startTime));
        //     $map['startTime'] = $startTime;
        //     $map['endTime'] = $endTime;
        // }

        if($startTime ){
            $query->where('o.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
        }
        if ($endTime) {
            $query->where('o.create_time', '<=', strtotime($endTime));
            $map['endTime'] = $endTime;
        }

        $userTable = (new User())->getTable();

        $list = $query->leftJoin("$userTable s", 's.id = o.user_id')
            ->order('create_time', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);

        return $list;
    }

    /**
     * 导出充值明细
     */
    public function exportRecharge(Request $request){

        $export = new Export();
        $entity = UserMagicLog::alias('um')->field('um.*,u.mobile,u.nick_name,u.trade_address');

        if ($keyword = $request->get('keyword')) {
            $entity->where('u.mobile', $keyword);

        }

        $status = $request->get('status');

        if ($status) {
            $statuss = $status;
            $entity->where('um.types', $statuss);
        }

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $entity->where('u.mobile','like','%'.$keyword.'%');
                    break;
            }
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');

        if($startTime ){
            $entity->where('um.create_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
        }
        if ($endTime) {
            $entity->where('um.create_time', '<=', strtotime($endTime));
            $map['endTime'] = $endTime;
        }

        $userTable = (new \app\common\entity\User())->getTable();

        $list = $entity->leftJoin("{$userTable} u", 'um.user_id = u.id')
                ->order('um.create_time', 'desc')
                ->select();
        foreach ($list as $key => &$value) {
            $value['types'] = $value->getType($value['types']);
        }
        // echo '<pre>';
        // print_r($list);
        // exit;
        $filename = '充值明细列表';
        $header = array('id', '会员昵称', '会员账号', '充值地址', '金币数量', '变化前', '变化后', '类型', '备注', '时间');
        $index = array('id', 'nick_name', 'mobile', 'trade_address', 'magic', 'old', 'new', 'types', 'remark', 'create_time');
        $export->createtable($list, $filename, $header, $index);
    }




     /**
     * @power FOMO管理|充值审批
     * @method POST
     */
    // public function rechargeapprove(Request $request)
    // {     

    //     $id = $request->get('id')??'';
    //     $op = $request->get('op')??'';
        
    //     $log = FomoRecharge::where('id',$id)->where('status',0)->find();

    //     if(empty($id) || empty($log)){
    //         throw new AdminException('操作有误！');
    //     }


    //     $FomoRecharge = new FomoRecharge();

    //     //手动打款
    //     if($op=='reject'){

    //         $FomoRecharge->where('id',$id)->update(['examinetime'=>time(),'status'=>2]);

    //     }else if($op=='pass'){
    //     //拒绝


    //         Db::startTrans();

    //         try {

    //             $result = $FomoRecharge->where('id',$id)->update(['examinetime'=>time(),'status'=>1]);

    //             if (!$result) {
    //             throw new \Exception('操作失败');
    //             }

    //             //加钱
    //             // $User = new User();
    //             // $User->setBonus($log['user_id'],'bth',$log['change'],'客户充值增加');

    //             Db::commit();

    //         } catch (\Exception $e) {

    //             Db::rollback();
    //         }

    //     }

    //     return json(['code' => 0, 'message' => '操作成功']);

    // }

    /**
     * @power FOMO管理|公告管理
     * @method POST
     */
    public function admanage(Request $request){

        $result = DB::table('fomo_ad')->order('sort asc')->select();
        return $this->render('admanage', [
            'list' => $result,
        ]);
    }


    /**
     * 添加广告
     */
    public function adadd(Request $request)
    {   

        $time = time();
        $data['notice'] = $request->post('notice');
        $data['sort'] = $request->post('sort');
        
        $data['createtime'] = $time;
        $data['updatetime'] = $time;

        $result = DB::table('fomo_ad')->insert($data);
        if (!$result) {
            return ['code'=>1,'message'=>'添加失败'];
        }
        return ['code' => 0, 'message' => '添加成功'];
    }

    /**
     * 修改广告
     */
    public function adsave(Request $request)
    {   

        $time = time();

        $id = $request->post('id');
        $data['notice'] = $request->post('notice');
        $data['sort'] = $request->post('sort');
        $data['updatetime'] = $time;

        $result = DB::table('fomo_ad')->where('id',$id)->update($data);
        if (!$result) {
            return ['code'=>1,'message'=>'修改失败'];
        }
        return ['code' => 0, 'message' => '修改成功'];
    }

    /**
     * 修改广告
     */
    public function statussave(Request $request)
    {   


        $id = $request->post('id');
        $status = $request->post('status');
        $status = $status==1?0:1; // 取相反
        $result = DB::table('fomo_ad')->where('id',$id)->setField('status',$status);
        if (!$result) {
            return ['code'=>1,'message'=>'修改失败'];
        }
        return ['code' => 0, 'message' => '修改成功'];
    }


}
