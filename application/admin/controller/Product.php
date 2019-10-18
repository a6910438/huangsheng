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

class Product extends Admin {

    /**
     * @power 产品管理|产品列表
     * @rank 1
     */
    public function index(Request $request) {

        $entity = ProductPoolModel::where('is_delete',0);

        $question_list  = $entity
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        $list = $question_list->items();
        if(isset($map['sort'])){
            $map['sort'] = $map['sort'] == 'desc' ? 'asc' : 'desc';
        }
        foreach ($list as $k => $v){
            $key = get_today_key($v['id']);

           $f_num =  FishModel::alias('f')
               ->where('f.key',$key)
               ->where('f.is_delete',0)
                ->count('f.id')??0;
//           dump($f_num);
          if($f_num){
            $list[$k]['fnum'] =$f_num;
            $list[$k]['key'] = $key;

          }else{
              $list[$k]['fnum'] = 0;

          }
//            dump(  $list[$k]['key']);

            $u_num = Db::table('appointment_user')
                ->alias('au')
                ->join('user u','u.id = au.uid')
                ->where('key',$key)
                ->where('types',0)
                ->count('au.id');
            if($u_num){
                $list[$k]['aunum'] =$u_num;

            }else {
                $list[$k]['aunum'] = 0;
            }
            if($u_num || $f_num){
                $list[$k]['key'] = $key;
            }else{
                $list[$k]['key'] = 0;

            }
			$list[$k]['adopt_num'] = Db::table('appointment_user')
					->where('pool_id',$v['id'])
                     ->where('status','not in','0,-2')
                     ->where('key',$key)
					->count();
        }

//        exit;


        return $this->render('index', [
            'list' => $list,
            'question_list' => $question_list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);
    }


    public function orderdetail(Request $request) {
        $id = $request->param('id');
        if(empty($id)){
            $this->error('缺失参数');
        }

        $entity = Db('fish_order')->alias('fo')
            ->leftJoin('appointment_user au','au.id = fo.types')
            ->leftJoin('user u','u.id = au.uid')
            ->leftJoin('fish f','f.id = fo.f_id')
            ->leftJoin('bathing_pool bp','bp.id = f.pool_id')
            ->leftJoin('user fu','fu.id = f.u_id')
            ->join('user_invite_code uic', 'uic.user_id  = u.id')
            ->where('fo.id',$id);



        $entity->field('au.bait,fo.create_time,au.types,au.status,au.adopt_lv,au.oid,u.nick_name,uic.invite_code,u.id uid,u.status ustatus,bp.name fname,fu.nick_name fnick_name,fo.order_number,au.pay_imgs');
        $info = $entity
            ->leftJoin('my_wallet mw','mw.uid = u.id')
            ->find();
        if($info){
           $is_fworth = get_fish_order_worth($info['oid']);
           if($is_fworth){
               $info['worth'] = $is_fworth['old'];
               $info['now'] = $is_fworth['now'];
               $info['num'] = $is_fworth['num'];
           }else{
               $info['worth'] = 0;
               $info['now'] = 0;
               $info['num'] = 0;
           }
        }
        $query = new \app\common\entity\Team();
        return $this->render('ordetail', [
            'info' => $info,
            'queryStr' => isset($map) ? http_build_query($map) : '',
            'query' => $query,
        ]);
    }




    public function audetail(Request $request) {
        $key = $request->param('key');
        if(empty($key)){
            $this->error('缺失参数');
        }

        $entity = Db('appointment_user')->alias('au')
            ->leftJoin('user u','u.id = au.uid')
            ->join('user_invite_code uic', 'uic.user_id  = u.id')
            ->where('au.key',$key)
			->where('au.types',0);

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

        $orderStr = 'u.register_time DESC';
        $entity->field('au.bait,au.create_time,au.types,au.status,au.adopt_lv,au.oid,u.nick_name,uic.invite_code,u.id uid,u.status ustatus');
        $list = $entity
            ->leftJoin('my_wallet mw','mw.uid = u.id')
            ->order($orderStr)
            ->distinct(true)
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        if(isset($map['sort'])){
            $map['sort'] = $map['sort'] == 'desc' ? 'asc' : 'desc';
        }

        $query = new \app\common\entity\Team();
        return $this->render('audetail', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
            'query' => $query,
        ]);
    }
	
	public function adopt_detail(Request $request) {
        $key = $request->param('key');
        if(empty($key)){
            $this->error('缺失参数');
        }

        $entity = Db('appointment_user')->alias('au')
            ->leftJoin('user u','u.id = au.uid')
            ->join('user_invite_code uic', 'uic.user_id  = u.id')
            ->where('au.key',$key)
			->where('au.status','not in','0,-2')
			->where('au.create_time','>',strtotime(date('Y-m-d')));

        $entity->field('au.bait,au.create_time,au.types,au.status,au.adopt_lv,au.oid,u.nick_name,uic.invite_code,u.id uid,u.status ustatus,u.lv lv');
        $list = $entity
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        return $this->render('adopt_detail', [
            'list' => $list
        ]);
    }



    /**
     * 投酒
     * @param $id
     */
    public function addFish(Request $request){

        $id = $request->param('id');
        if(empty($id)){
            $this->error('缺失参数');
        }

        $info = ProductPoolModel::where('id',$id)->where('is_delete',0)->find();
        if(empty($info)){
            $this->error('无效数据');
        }
        return $this->render('addfish', [
            'info'=>$info,
        ]);


    }


    /**
     * 用户指定酒
     * @param Request $request
     * @return mixed
     */
    public function addUserFish(Request $request){

        $id = $request->param('id');
        if(empty($id)){
            $this->error('缺失参数');
        }

        $info = FishModel::where('id',$id)->where('is_delete',0)->where('status',0)->find();
        if(empty($info)){
            $this->error('无效数据');
        }
        $poolid = $info['pool_id'];
        $pool = ProductPoolModel::where('id',$poolid)->where('is_delete',0)->find();

        if(empty($pool)){
            $this->error('无效酒馆');
        }


        return $this->render('adduserfish', [
            'info'=>$info,
            'pool'=>$pool,
        ]);


    }



    /**
     * 赠酒
     * @param Request $request
     * @return mixed
     */
    public function setUserFish(Request $request){

//        $id = $request->param('id');
//        if(empty($id)){
//            $this->error('缺失参数');
//        }

//        $info = Db::table('user')->where('id',$id)->find();
//        if(!$info){
//            $this->error('无效用户');
//        }

        $pool = ProductPoolModel::where('is_delete',0)->where('is_open',1)->select();


        if(empty($pool)){
            $this->error('无开放酒馆');
        }
		$where = array();
        
		$startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime || $endTime){
			if(empty($startTime)){
				$startTime = date('Y-m-d H:i:s');
				$endTime = $endTime;
			}
			if(empty($endTime)){
				$endTime = date('Y-m-d H:i:s');
				$startTime = $startTime;
			}
			$where['f.create_time'] = ['between time',[strtotime($startTime),strtotime($endTime)]];
			
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }
		if(!$startTime && !$endTime){
			$where['f.create_time'] = ['between time',[strtotime(date('Y-m-d')),time()]];
		}
		if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'ids':
                    $where['uic.invite_code'] = $keyword;
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
		$info = Db::table('fish')
				->alias('f')
				->join('bathing_pool bp','bp.id = f.pool_id')
				->join('user u','u.id = f.u_id')
				->join('user_invite_code uic','uic.user_id =u.id ')
				
				->field('uic.invite_code,bp.name,COUNT(f.id) as fish_num,f.worth,f.create_time')
				->where(['f.types'=>6])
				->where($where)
				->group('f.create_time')
				->order('f.create_time desc')
				->paginate(10, false, [
                'query' => isset($map) ? $map : []
            ]);


        return $this->render('setuserfish', [
            'list'=>$info,
            'pool'=>$pool,
        ]);


    }



    /**
     * @power 产品管理|列表@添加产品
     */
    public function create() {
        return $this->render('edit');
    }

    /**
     * 投酒
     * @param Request $request
     * @return $this|\think\response\Json
     * @throws \Exception
     */
    public function fishsave(Request $request){

        if(empty($request->post('num')) || $request->post('num') < 0){
            return json()->data(['code' => 1, 'message' => '酒数不能为空！']);
        }


        if(empty($request->post('values')) || $request->post('values') < 0){
            return json()->data(['code' => 1, 'message' => '价值不能为空！']);
        }

        if(empty($request->post('poolid'))){
            return json()->data(['code' => 1, 'message' => '无效参数！']);
        }
        $service = new \app\common\service\Fish\Service();
        $is_save = $service->addData($request->post('poolid'),$request->post('values'),$request->post('num'));
        if(!$is_save){
            throw new \Exception('保存失败');
        }

        return json(['code' => 0, 'toUrl' => url('/admin/product/index')]);

    }


    /**
     * 指定添加操作
     * @param Request $request
     * @return $this|\think\response\Json
     * @throws \Exception
     */
    public function userfishsave(Request $request){


        if(empty($request->post('id')) || $request->post('mobile') < 0){
            return json()->data(['code' => 1, 'message' => '参数缺失！']);
        }
        $mobile = $request->post('mobile');
        $modile = trim($mobile);

        $id = $request->post('id');

        $user = userModel::where('mobile', $modile)->find();

        if (!$user) {
            return json()->data(['code' => 1, 'message' => '用户对象不存在！']);
        }
        if($user['status'] !=1){
            return json()->data(['code' => 1, 'message' => '该用户未激活！']);
        }


        $service = new \app\common\service\Fish\Service();
        $is_save = $service->addUserfishData($id,$user['id']);

        if(!$is_save){
            throw new \Exception('保存失败');
        }

        return json(['code' => 0, 'toUrl' => url('/admin/product/index')]);

    }


    /**
     * 添加返池记录
     * @param $fid
     * @return int|string
     */

     public function add_re_log($fid,$time){

         $tmptime = $time;
         $stime = $tmptime;             //装修开始时间

         $tmptime =   date('Y-m-d H:i:s',$tmptime);
         $ntime = strtotime("$tmptime +1 day");
                $add['fid'] = $fid;
                $add['is_feed'] = 0;
                $add['stime'] = $stime;
                $add['ntime'] = $ntime;
                $add['types'] = 3;
               return Db::table('fish_feed_log')->insert($add);
     }



    /**
     * 后台赠送酒
     * @param Request $request
     * @return $this|\think\response\Json
     * @throws \Exception
     */
    public function setuserfishsave(Request $request){


        if(empty($request->post('mobile')) || empty($request->post('pid')) || empty($request->post('num'))|| empty($request->post('values')) ){
            return json()->data(['code' => 1, 'message' => '参数缺失！']);
        }

        $is_feed = input('post.is_status')?input('post.is_status'):0;
        $mobile = $request->post('mobile');
        $pid = $request->post('pid');
        $num = $request->post('num');
        $values = $request->post('values');


        $user = Db::table('user')
            ->alias('u')
            ->join('user_invite_code uic','uic.user_id = u.id')
            ->where('u.mobile|uic.invite_code',$mobile)
            ->field('u.*')
            ->find();

        if (!$user) {

            return json()->data(['code' => 1, 'message' => '用户对象不存在！']);
        }
        $uid = $user['id'];
        if($user['status'] !=1){

            return json()->data(['code' => 1, 'message' => '该用户未激活！']);
        }

//       $is_c = Db::table('card')->where('u_id',$uid)->where('is_delete',0)->field('id')->find();
//        if(empty($is_c)){
//
//
//            return json()->data(['code' => 1, 'message' => '该用户未设置收款信息1！']);
//        }

        $ProductPool = new ProductPool();
        $ProductPool->where('id',$pid);
        $ProductPool->where('is_delete',0);
        $ProductPool->field('id,contract_time,lock_position,name,lv,status,worth_max,worth_min');
        $is_pool =  $ProductPool->find();

        if(!$is_pool){


            return json()->data(['code' => 1, 'message' => '无效酒馆！']);
        }

        if($is_pool['worth_max']  <= $values || $values < $is_pool['worth_min']){


            return json()->data(['code' => 1, 'message' => '不在酒馆价值之内！']);
        }
        $service = new \app\common\service\Fish\Service();
        $is_save = $service->addUserFish($pid,$uid,$num,$values,$is_feed);

        if(!$is_save){
            throw new \Exception('保存失败');
        }

        return json(['code' => 0,'message' => '赠送成功！','toUrl'=>'setuserfish']);
    }



    public function lookPool(Request $request){
        $id = $request->param('id');
        if(empty($id)){
            $this->error('缺失参数');
        }

        $pool = ProductPoolModel::where('id',$id)->where('is_delete',0)->find();
        if(empty($pool)){
            $this->error('无效数据');
        }

        $entity = FishModel::alias('f')
            ->where('f.pool_id',$id)
            ->where('f.status','in','0,1,2,3')
            ->where('f.is_show',1)
            ->where('f.is_delete',0)
			->join('user u','u.id = f.u_id');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $entity->where('u.mobile', $keyword);
                    break;
                case 'nick_name':
                    $entity->where('u.nick_name', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

		
        $list = $entity
			->join('user_invite_code uic','uic.user_id =u.id')
            ->field('f.* , uic.invite_code,u.status uststus')
			->paginate(15, false,[
                'query' => isset($map) ? $map : []
           ]);
		$total_worth = FishModel::alias('f')
            ->where('f.pool_id',$id)
            ->where('f.status','in','0,1,2,3')
            ->where('f.is_delete',0)
            ->where('f.is_show',1)

            ->join('user u','u.id = f.u_id')
			->sum('worth');
		$total_num = count($list);
        

        return $this->render('lookpool', [
            'pool' => $pool,
            'list' => $list,
			'total_worth'=>$total_worth,
			'total_num'=>$total_num,
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);

    }


    /**
     * 查看当天可售卖的酒
     * @param Request $request
     * @return mixed
     */
    public function detail(Request $request){
        $key = $request->param('key');
        if(empty($key)){
            $this->error('缺失参数');
        }



        $entity = FishModel::alias('f')->where('f.key',$key)
            ->where('f.is_delete',0);


//        if ($keyword = $request->get('keyword')) {
//            $type = $request->get('type');
//            $entity->join('user u','u.id = f.u_id');
//            switch ($type) {
//                case 'mobile':
//                    $entity->where('u.mobile', $keyword);
//                    break;
//                case 'nick_name':
//                    $entity->where('u.nick_name', $keyword);
//                    break;
//            }
//            $map['type'] = $type;
//            $map['keyword'] = $keyword;
//        }


        $list = $entity

            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        if(isset($map['sort'])){
        }
        $map['create_time'] = 'desc' ;
		

        return $this->render('detail', [

            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);

    }

    public function fish_activation(Request $request){
        $id = $request->param('id');
        if(empty($id)){
            $this->error('缺失参数');
        }
        $up['is_show'] = 0;
        //$up['status'] = 0;
        $up['update_time'] = time();
        $is_save = Db::table('fish')->where('id',$id)->update($up);

        LogService::write('酒馆管理', '冻结酒');
        if($is_save){
            return json()->data(['code' => 0]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }

    public function fish_unactivation(Request $request){
        $id = $request->param('id');
        if(empty($id)){
            $this->error('缺失参数');
        }
        $up['is_show'] = 1;
        $up['update_time'] = time();
        $is_save = Db::table('fish')->where('id',$id)->update($up);

        LogService::write('酒馆管理', '恢复酒');
        if($is_save){
            return json()->data(['code' => 0]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }

    public function fish_del(Request $request){
        $id = $request->param('id');
        if(empty($id)){
            $this->error('缺失参数');
        }
        $up['is_delete'] = 1;
        $up['delate_time'] = time();
       $is_save = Db::table('fish')->where('id',$id)->update($up);
        LogService::write('酒馆管理', '删除酒');
        if($is_save){
            return json()->data(['code' => 0]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }



    public function fish_splistORupgrade(Request $request){
        $id = $request->param('id');
        if(empty($id)){
            $this->error('缺失参数');
        }
        $types = $request->param('types');
        $service = new \app\common\service\Fish\Service();
        if($types == 0){

            LogService::write('酒馆管理', '升级酒');

        }else{
            LogService::write('酒馆管理', '拆分酒');
        }

        $is_save =$service->splistORupgradeFish($id,$types);

        if($is_save){
            return json()->data(['code' => 0]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败,未满足升级拆分条件']);
    }


    /**
     * 转让统计
     * @param Request $request
     * @return mixed
     */
    public function lookTurn(Request $request){
        $id = $request->param('id');
        if(empty($id)){
            $this->error('缺失参数');
        }

        $pool = ProductPoolModel::where('id',$id)->where('is_delete',0)->find();
        if(empty($pool)){
            $this->error('无效数据');
        }

        $entity = FishModel::where('pool_id',$id)->where('u_id','>',0);
        $list = $entity

            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        if(isset($map['sort'])){
        }
        $map['create_time'] = 'desc' ;


        return $this->render('lookturn', [
            'pool' => $pool,
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);

    }

    /**
     * 开放酒馆
     * @method get
     */
    public function activation(Request $request)
    {
        $id = $request->param('id');
        $res = ProductPoolModel::where('id',$id)->update(['is_open'=>1,'update_time'=>time()]);
        LogService::write('酒馆管理', '酒馆开放');
        if($res){
            return json()->data(['code' => 0, 'toUrl' => url('index')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }
    /**
     * 冻结酒馆
     * @method get
     */
    public function freeze(Request $request)
    {
        $id = $request->param('id');
        $res = ProductPoolModel::where('id',$id)->update(['is_open'=>0,'update_time'=>time()]);
        LogService::write('酒馆管理', '酒馆关闭');
        if($res){
            return json()->data(['code' => 0, 'toUrl' => url('index')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }


    /**
     * 删除酒馆
     * @param Request $request
     * @return \think\response\Json
     */
    public function delete(Request $request)
    {
        $id = $request->param('id');
        $res = ProductPoolModel::where('id',$id)->update(['is_delete'=>1,'update_time'=>time()]);
        LogService::write('酒馆管理', '酒馆删除');
        if($res){
            return json()->data(['code' => 0, 'toUrl' => url('index')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }



    /**
     * @power 产品管理|产品列表@添加产品
     */
    public function save(Request $request) {


        $result = $this->validate($request->post(), 'app\admin\validate\ProductForm');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }


        $service = new \app\common\service\ProductPool\Service();

//        if ($service->checkLv($request->post('lv'))) {
//            return json()->data(['code' => 1, 'msg' =>'该等级已被使用,请重新填写']);
//        }



        $cTime = $service->checkTime(
            $request->post('about_start_time'),//预约开始时间
            $request->post('about_end_time'),  //预约结束时间
            $request->post('start_time'),      //领取开始时间
            $request->post('end_time')         //领取开始时间
        );
        if($cTime['code'] == 1){
            return json()->data(['code' => 1, 'message' =>$cTime['message']]);
        }

        $cSection = $service->checkSection(
            $request->post('first_section_percent'),
            $request->post('second_section_percent'),
            $request->post('third_section_percent')
        );

        if($cSection['code'] == 1){
            return json()->data(['code' => 1, 'message' =>$cSection['message']]);
        }


        $add_data = $request->post();

        $userId = $service->addData($add_data);
        if (!$userId) {
            throw new \Exception('保存失败');
        }



        LogService::write('产品管理', '添加酒馆');
        return json(['code' => 0, 'toUrl' => url('/admin/product/index')]);

    }


    public function editPool(Request $request)
    {
        $id = $request->param('id');
        $info = ProductPoolModel::where('id',$id)->find();

        return $this->render('edit',[
            'info' => $info,
        ]);
    }

    public function update(Request $request, $id) {
        $entity = $this->checkInfo($id);

        if(empty($entity)){
            return json()->data(['code' => 1, 'message' => '无效对象']);
        }
        $result = $this->validate($request->post(), 'app\admin\validate\ProductEditForm');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }



        $is_rename = ProductPoolModel::where('id', '<>', $id)->where('is_delete',0)->where('name', $request->post('name'))->find();

        if($is_rename){
            return json(['code' => 1, 'message' =>'该酒馆名已被使用']);
        }


//        $is_lv = ProductPoolModel::where('id', '<>', $id)->where('is_delete',0)->where('lv', $request->post('lv'))->find();
//
//        if($is_lv){
//            return json(['code' => 1, 'msg' =>'该酒馆等级已被使用']);
//        }



        $service = new \app\common\service\ProductPool\Service();
        $cTime = $service->checkTime(
            strtotime($request->post('about_start_time')),//预约开始时间
            strtotime($request->post('about_end_time')),  //预约结束时间
            strtotime($request->post('start_time')),      //领取开始时间
            strtotime($request->post('end_time'))         //领取开始时间
        );
        if($cTime['code'] == 1){
            return json()->data(['code' => 1, 'message' =>$cTime['message']]);
        }

        $cSection = $service->checkSection(
            $request->post('first_section_percent'),
            $request->post('second_section_percent'),
            $request->post('third_section_percent')
        );

        if($cSection['code'] == 1){
            return json()->data(['code' => 1, 'message' =>$cSection['message']]);
        }


        $save['name']= trim($request->post('name'));
        $save['num']= trim($request->post('num'));
        $save['lv']= trim($request->post('lv'));
        $save['lock_position'] = $request->post('?lock_position') ? trim($request->post('lock_position')) : '0';
        $save['status']= trim($request->post('status'));
        $save['worth_min']= trim($request->post('worth_min'));
        $save['worth_max']= trim($request->post('worth_max'));
        $save['about_start_time']= strtotime(trim($request->post('about_start_time')));
        $save['about_end_time']=  strtotime(trim($request->post('about_end_time')));
        $save['start_time']=  strtotime(trim($request->post('start_time')));
        $save['end_time']=  strtotime(trim($request->post('end_time')));
        $save['bait']= trim($request->post('bait'));
        $save['subscribe_bait']= trim($request->post('subscribe_bait'));
        $save['rob_bait']= trim($request->post('rob_bait'));
        $save['fail_return']= trim($request->post('fail_return'));
        $save['profit']= trim($request->post('profit'));
        $save['contract_time']= trim($request->post('contract_time'));
        $save['is_open']= trim($request->post('is_open'));
        $save['remarks']= trim($request->post('remarks'));
        $save['sort']= trim($request->post('sort'));
        $save['img']= trim($request->post('path'));
        $save['open_section']= trim($request->post('open_section'));
        $save['first_section_min']= trim($request->post('first_section_min'));
        $save['first_section_max']= trim($request->post('first_section_max'));
        $save['first_section_percent']= trim($request->post('first_section_percent'));
        $save['second_section_min']= trim($request->post('second_section_min'));
        $save['second_section_max']= trim($request->post('second_section_max'));
        $save['second_section_percent']= trim($request->post('second_section_percent'));
        $save['third_section_min']= trim($request->post('third_section_min'));
        $save['third_section_max']= trim($request->post('third_section_max'));
        $save['third_section_percent']= trim($request->post('third_section_percent'));
        $save['fail_return']= trim($request->post('fail_return'));
        $save['update_time']= time();

        $result = ProductPoolModel::where('id',$id)->update($save);




        LogService::write('酒馆管理', '用户编辑酒馆');
        if (!$result) {
            return json(['code' => 1, 'message' => url('保存失败')]);
        }
        return json(['code' => 0, 'toUrl' => url('index')]);
    }
    private function checkInfo($id) {
        $entity = ProductPoolModel::where('id', $id)->find();
        if (!$entity) {
            throw new AdminException('对象不存在');
        }

        return $entity;
    }


    public function editExtension(Request $request)
    {
        $id = $request->param('id');
        $info = ProductPoolModel::where('id',$id)->find();

        return $this->render('editextension',[
            'info' => $info,
        ]);
    }

    /**
     * 积分兑换酒馆设置
     * @param Request $request
     * @param $id
     * @return $this|\think\response\Json
     */
    public function extensionupdate(Request $request, $id) {
        $entity = $this->checkInfo($id);
        $post = $request->post();
        if(empty($entity)){
            return json()->data(['code' => 1, 'message' => '无效对象']);
        }

        $integral = $request->post('integral');
        if(empty($integral)){
            return json()->data(['code' => 1, 'message' => '兑换积分不能为空！']);
        }
        if($request->post('is_integral') == 1){
           $is_i = Db::table('bathing_pool')->where('is_delete',0)->where('is_integral','in','1,3')->where('id','<>',$id)->find();

        }elseif ($request->post('is_integral') == 2){
            $is_i = Db::table('bathing_pool')->where('is_delete',0)->where('is_integral','in','2,3')->where('id','<>',$id)->find();
        }elseif ($request->post('is_integral') == 3){
            $is_i = Db::table('bathing_pool')->where('is_delete',0)->where('is_integral','in','1,2,3')->where('id','<>',$id)->find();
        }
        if(!empty($is_i['id']) ){
            $msg = '请关闭'.$is_i['name'].'积分兑换状态！';
            return json()->data(['code' => 1, 'message' => $msg]);
        }

            $save = [
                'is_integral' => trim($request->post('is_integral')),
				'integral' => trim($request->post('integral')),
                'update_time' => time(),
            ];

        $result = ProductPoolModel::where('id',$id)->update($save);

        LogService::write('酒馆管理', '编辑推广');
        if (!$result) {
            return json(['code' => 1, 'message' => url('保存失败')]);
        }
        return json(['code' => 0, 'toUrl' => url('index')]);
    }
	
	//用户激活设置
	function food_set(){
		return $this->render('food_set', [
            'list' => \app\common\entity\Config::where('type', 1)->where('key','in','rob_food,order_food,feed_food')->where('status',1)->select()
        ]);
	}


}
