<?php

namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\common\entity\AvatarLog;
use app\common\entity\Linelist;
use app\common\entity\Mywallet;
use app\common\entity\MywalletLog;
use app\common\entity\RechargeLog;
use app\common\entity\StoreLog;
use app\common\entity\TransferLog;
use app\common\entity\TitleLog;
use app\common\entity\User as userModel;
use app\common\entity\Team as teamModel;
use app\common\entity\UserInviteCode;
use app\common\entity\UserMagicLog;
use app\common\entity\Export;
use app\common\entity\Withdraw;
use app\common\entity\YekesConfig;
use app\common\entity\YekesLog;
use app\common\service\Users\Identity;
use app\common\entity\Fish as FishModel;
use app\common\model\GC;
use app\common\entity\UserVerifyLog;

use think\Db;
use think\Request;
use service\LogService;
use think\Session;

class User extends Admin
{

    /**
     * @power 会员管理|会员列表
     * @rank 1
     */
    public function index(Request $request)
    {
        $entity = userModel::alias('u')->field('u.*,mw.old,mw.now,count(zt_u.id) as getZT');
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

        // 获取排序类型
        if ($sort_type = $request->get('sort_type')) {
            $map['sort'] = $sort_type;
            $map['sort_type'] = $sort_type;
        } else {
            $map['sort'] = 'desc';
            $map['sort_type'] = 'desc';
        }


        // 获取排序字段
        if ($sort_key = $request->get('sort_key')) {
            switch ($sort_key) {
                case 'ZT':
                    $fieldOrderStr = 'getZT ' . strtoupper($map['sort_type']);
                    break;
                case 'NOW_GTC':
                    $fieldOrderStr = 'now ' . strtoupper($map['sort_type']);
                    break;
                case 'OLD_GTC':
                    $fieldOrderStr = 'old ' . strtoupper($map['sort_type']);
                    break;
                case 'GC':
                    $fieldOrderStr = 'gc ' . strtoupper($map['sort_type']);
                    break;
                case 'PROFIT':
                    $fieldOrderStr = 'profit ' . strtoupper($map['sort_type']);
                    break;
                case 'now_prohibit_integral':
                    $fieldOrderStr = 'now_prohibit_integral ' . strtoupper($map['sort_type']);
                    break;
                case 'now_team_integral':
                    $fieldOrderStr = 'now_team_integral ' . strtoupper($map['sort_type']);
                    break;
                default:
                    $fieldOrderStr = 'getZT ' . strtoupper($map['sort_type']);
                    break;
            }
            $orderStr = $fieldOrderStr . ',' . 'u.register_time DESC';
            $map['sort_key'] = $sort_key;
        } else {
            $orderStr = 'u.register_time DESC';
            $map['sort_key'] = '';
        }


        $list = $entity
            ->leftJoin('user zt_u', 'zt_u.pid = u.id')
            ->leftJoin('my_wallet mw', 'mw.uid = u.id')
            // ->distinct(true)
            ->order($orderStr)
            ->group('u.id')
            ->paginate(10, false, [
                'query' => isset($map) ? $map : []
            ]);
        // if (isset($map['sort'])) {
        //     $map['sort'] = $map['sort'] == 'desc' ? 'asc' : 'desc';
        // }
        foreach ($list as $v) {
            // $fishvalue = get_fishvalue($v['id']); //购买酒的价值
            // $leader = \app\common\entity\User::where('id', $v['pid'])->value('nick_name');
            // $next_count = \app\common\entity\User::where('pid', $v['id'])->count();

//            $child = \app\common\entity\User::field('id,status')
//                ->where('pid',$v['id'])
//                ->where('is_active',1)
//                ->where('status',1)
//                ->select();
            //团队总人数
//            $teamCount = (new \app\common\entity\User())->getTeamNum($child);

            if ($v['lv']) {

                $entity = new \app\common\entity\MyWallet();
                $teamCount = $entity->teamnum($v['id']);//团队人数

            } else {
                $teamCount = 0;
            }
            $v['teamCount'] = $teamCount;

            $get_consumep = (new \app\common\entity\User())->get_consumep2($v['id']);
            $v['get_consumep'] = $get_consumep;  //消耗GTC
            // $get_addp = (new \app\common\entity\User())->get_addp_one($v['id']);
//            $is_delete = (new \app\common\entity\User())->getIsDelete($v['is_delete']);

            // $v['get_addp'] = $get_addp;  //充值GTC
            // $v['next_count'] = $next_count;
            // $v['leader'] = $leader;
            // $v['getZT'] = (new \app\common\entity\User())->getZT($v['id']);
//            $v['is_delete'] = $is_delete;

            // $v['fishvalue'] = $fishvalue;
        }
        // halt(collection($list)->toArray());
        $query = new \app\common\entity\Team();
        return $this->render('index', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
            'queryMap' => isset($map) ? $map : ['sort_type' => 'desc'],
            'query' => $query,
        ]);
    }


    /**
     * @power 会员管理|会员列表
     * @rank 1
     */
    public function index_back(Request $request)
    {
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
            $fishvalue = get_fishvalue($v['id']); //购买酒的价值
            $leader = \app\common\entity\User::where('id', $v['pid'])->value('nick_name');
            $next_count = \app\common\entity\User::where('pid', $v['id'])->count();

//            $child = \app\common\entity\User::field('id,status')
//                ->where('pid',$v['id'])
//                ->where('is_active',1)
//                ->where('status',1)
//                ->select();
            //团队总人数
//            $teamCount = (new \app\common\entity\User())->getTeamNum($child);

            if ($v['lv']) {

                $entity = new \app\common\entity\MyWallet();
                $teamCount = $entity->teamnum($v['id']);//团队人数

            } else {
                $teamCount = 0;
            }


            $get_consumep = (new \app\common\entity\User())->get_consumep2($v['id']);
            $get_addp = (new \app\common\entity\User())->get_addp_one($v['id']);
//            $is_delete = (new \app\common\entity\User())->getIsDelete($v['is_delete']);
            $v['get_consumep'] = $get_consumep;  //消耗GTC
            $v['get_addp'] = $get_addp;  //充值GTC
            $v['next_count'] = $next_count;
            $v['leader'] = $leader;
            $v['getZT'] = (new \app\common\entity\User())->getZT($v['id']);
//            $v['is_delete'] = $is_delete;

            $v['teamCount'] = $teamCount;

            $v['fishvalue'] = $fishvalue;
        }
        $query = new \app\common\entity\Team();
        return $this->render('index', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
            'query' => $query,
        ]);
    }


    public function indexLogin(Request $request)
    {
        $id = $request->param('id');

        Session::set('flow_box_member', [
            'id' => $id,
        ]);

        $login_time = session_id();

        \app\common\entity\User::where('id', $id)->update(['login_time' => $login_time]);
        $this->redirect('/free/index/index.html');
    }

    /**
     * 导出数据
     */
    public function exportUser(Request $request)
    {
        $export = new Export();
        $entity = userModel::field('u.*,c.invite_code,mw.number,mw.btc,mw.eth,mw.eos')->alias('u');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'email':
                    $entity->where('u.email', $keyword);
                    break;
                case 'nick_name':
                    $entity->where('u.nick_name', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        if ($certification = $request->get('certification')) {
            if ($certification == 2) {
                $entity->where('u.is_certification', -1);
                $entity->where('u.card_left', '<>', NULL);
                $entity->where('u.card_right', '<>', NULL);
            } else {
                $entity->where('u.is_certification', $certification);
            }
            $map['certification'] = $certification;
        }
        $orderStr = 'u.register_time DESC';
        if ($order = $request->get('order')) {
            $sort = $request->get('sort', 'desc');
            $orderStr = 'u.' . $order . ' ' . $sort;
            $map['order'] = $order;
            $map['sort'] = $sort;
        }
        $codeTable = (new UserInviteCode())->getTable();
        $list = $entity->leftJoin("$codeTable c", 'u.id = c.user_id')
            ->leftJoin('my_wallet mw', 'u.id = mw.user_id')
            ->order($orderStr)
            ->select();
        foreach ($list as $key => &$value) {
            $p = $value->getParentInfo();
            if ($p) {
                $value['p_nick_name'] = $p['nick_name'];
                $value['p_email'] = $p['email'];
            } else {
                $value['p_nick_name'] = '无';
                $value['p_email'] = '无';
            }
            $team = $value->getTeamInfo();
            $value['total'] = $team['total'];
            $value['rate'] = $team['rate'];
            if ($value['is_active'] == 1) {
                $value['isactive'] = '激活';
            } else {
                $value['isactive'] = '未激活';
            }
        }


        $filename = '会员列表';
        $header = array('会员ID', '用户名', '邀请码', '上级用户名', '上级用户ID', 'BTC', 'ETH', 'EOS', '注册时间', '注册ip', '是否激活');
        $index = array('nick_name', 'email', 'invite_code', 'p_email', 'p_nick_name', 'btc', 'eth', 'eos', 'register_time', 'register_ip', 'isactive');
        $export->createtable($list, $filename, $header, $index);
    }


    /**
     * 查看会员详情
     * @method get
     */
    public function userDetail(Request $request)
    {


        $id = $request->param('id');
        $info = userModel::alias('u')
            ->join('user_invite_code uic', 'uic.user_id = u.id')
            ->where('u.id', $id)
            ->field('u.*,uic.invite_code')
            ->find();
        if ($info['status'] == 1) {
            $is_show = 1;
        } else {
            $is_show = 0;
        }
        $id = $request->param('id');
        if (empty($id)) {
            $this->error('缺失参数');
        }


        $entity = FishModel::alias('f')
            ->join('bathing_pool bp', 'bp.id = f.pool_id')
            ->where('f.u_id', $id)
            ->where('f.is_delete', 0)
            ->where('bp.is_delete', '0')
            //->where('f.is_show','1')
            //->where('f.status','in','0,1,2,3')
            //->where('f.status','in','0,1,2,3')
            ->field('bp.*,f.worth,f.is_show,f.front_id,f.types,f.id fid,f.status fstatus,f.id,FROM_UNIXTIME(f.create_time) as ctime');


        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            $entity->join('user u', 'u.id = f.u_id');
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
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        if (isset($map['sort'])) {
        }
        $map['create_time'] = 'desc';


        return $this->render('detail', [
            'list' => $list,
            'info' => $info,
            'is_show' => $is_show,
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);

    }

    /**
     * 激活会员
     * @method get
     */
    public function activation(Request $request)
    {
        $id = $request->param('id');
        $res = userModel::where('id', $id)->update(['status' => 1, 'is_active' => 1, 'active_time' => time(), 'last_store_time' => time()]);
        LogService::write('会员管理', '用户激活会员');
        if ($res) {
            return json()->data(['code' => 0, 'toUrl' => url('/admin/user/index')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }

    /**
     * 冻结会员
     * @method get
     */
    public function freeze(Request $request)
    {
        $id = $request->param('id');
        $res = userModel::where('id', $id)->update(['status' => -1]);
        LogService::write('会员管理', '用户冻结会员');
        if ($res) {
            return json()->data(['code' => 0, 'toUrl' => url('/admin/user/index')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }

    /**
     * 删除会员
     * @method get
     */
    public function delete(Request $request)
    {
        $id = $request->param('id');
        $res = userModel::where('id', $id)->update(['delete_time' => date('Y-m-d H:i:s', time())]);
        LogService::write('会员管理', '用户删除会员');
        if ($res) {
            return json()->data(['code' => 0, 'toUrl' => url('/admin/user/index')]);
        }
        return json()->data(['code' => 1, 'message' => '操作失败']);
    }


    /**
     * 修改会员数据
     * @method get
     */
    public function editUser(Request $request)
    {
        $id = $request->param('id');
        $info = userModel::where('id', $id)->find();
        return $this->render('edit', [
            'info' => $info,
        ]);
    }

    /**
     * @power 会员管理|充值明细
     * @method GET
     */
    public function magicList(Request $request)
    {

        $entity = RechargeLog::alias('um')->field('um.*,u.email');
        if ($keyword = $request->get('keyword')) {
            $entity->where('u.email', $keyword);
            $map['keyword'] = $keyword;

        }


        $userTable = (new \app\common\entity\User())->getTable();

        $list = $entity->leftJoin("{$userTable} u", 'um.user_id = u.id')
            ->order('um.create_time', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        return $this->render('magic', [
            'list' => $list,
        ]);
    }


    /**
     * @power 会员管理|会员列表@添加会员
     */
    public function create()
    {
        return $this->render('edit');
    }

    /**
     * @power 会员管理|会员列表@编辑会员
     */
    public function edit(Request $request)
    {
        $id = $request->param('id');
        $entity = userModel::where('id', $id)->find();
        if (!$entity) {
            $this->error('用户对象不存在');
        }
        $service = new \app\common\service\Users\Service();
        if ($service->checkUser($request->post('nick_name'))) {
            return json()->data(['code' => 1, 'message' => '账号已被注册,请重新填写']);
        }
        if ($service->checkAddress($request->post('trade_address'))) {
            return json()->data(['code' => 1, 'message' => '地址已存在,请重新填写']);
        }

        return $this->render('edit', [
            'info' => $entity,
        ]);
    }


    /**
     * @power 会员管理|会员列表@充值
     * @method POST
     */
    public function saveRecharge($id, Request $request)
    {
        $number = $request->post('magic');
        if (!preg_match('/^[0-9]+.?[0-9]*$/', $number)) {
            throw new AdminException('输入的数量必须为正整数或者小数');
        }
        $remark1 = $request->post("remark");
        $types = $request->post('types');

        if ($types == '1') {
            $types1 = 'btc';
            $remark = '系统充值btc';

        } elseif ($types == '2') {
            $types1 = 'eth';
            $remark = $remark1 . 'eth';
        } elseif ($types == '3') {
            $types1 = 'eos';
            $remark = $remark1 . 'eos';
        } elseif ($types == '4') {
            $types1 = 'number';
            $remark = $remark1 . '余额';
        }
        $hasNum = Mywallet::where('user_id', $id)->value($types1);
        $my_wallet = new Mywallet();


        $my_wallet_log = new MywalletLog();
        $inslog = $my_wallet_log->addLog($id, $number, $types1, $remark, 0, 1, $types);
        $recharge_log = [
            'user_id' => $id,
            'types' => $types,
            'from_address' => '平台',
            'num' => $number,
            'old' => $hasNum,
            'new' => $hasNum + $number,
            'create_time' => time()

        ];
        $updwallet = $my_wallet->updWallet($id, $types1, $number);
        $insRecharge = RechargeLog::insert($recharge_log);
        if (!$inslog) {
            throw new AdminException('充值失败');
        }
        return ['code' => 0, 'message' => '充值成功'];


    }


    /**
     * @power 会员管理|会员列表@添加会员
     */
    public function save(Request $request)
    {

        $teamModel = new teamModel;
        $result = $this->validate($request->post(), 'app\admin\validate\UserForm');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }

        $service = new \app\common\service\Users\Service();
        if ($service->checkUser($request->post('nick_name'))) {
            return json()->data(['code' => 1, 'message' => '用户名已被注册,请重新填写']);
        }
        if ($service->checkMobile($request->post('mobile'))) {
            return json()->data(['code' => 1, 'message' => '手机号已被注册,请重新填写']);
        }

        $add_data = $request->post();
        $pid = $service->checkHigher($request->post('higher'));
        $add_data['lv'] = $request->post('lv') - 1;
        /*if($add_data['lv'] >0){
            $is_pool = Db::table('bathing_pool')
                ->where('lv',$add_data['lv'])
                ->where('is_delete',0)
                ->field('id')
                ->find();
            if(!$is_pool){
                return json()->data(['code' => 1, 'message' =>'无效等级！']);
            }
        }else{
            $add_data['lv'] = 0;
        }*/

        if ($pid) {
            $add_data['pid'] = $pid;
            \app\common\entity\User::where('id', $pid)->setInc('invite_count');
//            $getTeam = $teamModel->getuid_Team($pid);
//            if($getTeam){
//                $add_data['tid'] =  $getTeam['id'];
//                $teamModel->addsetInc($getTeam['id']);
//            }else{
//                return json()->data(['code' => 1, 'message' =>'邀请人无团队分组']);
//
//            }
        } else {
            if ($request->post('higher') == 0) {
                $add_data['pid'] = 0;
            } else {
                $add_data['pid'] = Db::table('user_invite_code')->where('invite_code', $request->post('higher'))->value('user_id');
                if (empty($add_data['pid'])) {
                    return json()->data(['code' => 1, 'message' => '推荐人账号不存在,请重新填写']);
                }


            }
        }
        Db::startTrans();
        try {


            $area = getLocation($request->post('mobile'));

            if ($area['code'] == 200) {
                if ($area['status']) {
                    $add_data['province'] = $area['data']['province'];//归属地省
                    $add_data['city'] = $area['data']['city'];//归属地市
                    $add_data['service'] = $area['data']['service'];//号码服务商
                }

            }

            $add_data['last_store_time'] = time();


            $userId = $service->addUser($add_data);

            if (!$userId) {
                throw new \Exception('保存失败');
            }

            //创建钱包地址
            $gc = new GC;
            $user_update = [];
            $user_update['gc_address'] = $gc->newaccount();
            if (empty($user_update['gc_address']) || userModel::where(['id' => $userId])->update($user_update) < 1) {
                throw new \Exception('创建钱包地址失败');
            };

            if (empty($pid)) {
                $usave['tid'] = $teamModel->add($userId);
                $is_save = Db::table('user')->where('id', $userId)->update($usave);
                if (!$is_save) {
                    throw new \Exception('保存失败');
                }
            }


            $inviteCode = new UserInviteCode();
            if (!$inviteCode->saveCode($userId)) {
                throw new \Exception('保存失败');
            }
            $wallet_data = [
                'uid' => $userId,
                'update_time' => time(),
            ];
            $wallet_model = Db('my_wallet');
            $wallet_id = $wallet_model->insertGetId($wallet_data);

            $integral_data = [
                'uid' => $userId,
                'update_time' => time(),
            ];
            $integral_model = Db('my_integral');
            $integral_id = $integral_model->insertGetId($integral_data);

            \app\common\entity\User::where('id', $userId)->update(['money_address' => $wallet_id]);
            Db::commit();
            LogService::write('会员管理', '用户注册会员');

            return json(['code' => 0, 'toUrl' => url('/admin/user/index')]);
        } catch (\Exception $e) {
            Db::rollback();
            throw new AdminException($e->getMessage());
        }
    }

    /**
     * @power 会员管理|会员列表@编辑会员
     */
    public function update(Request $request, $id)
    {
        $entity = $this->checkInfo($id);
        $result = $this->validate($request->post(), 'app\admin\validate\UserEditForm');
        if (true !== $result) {
            return json()->data(['code' => 1, 'message' => $result]);
        }

        $is_rename = DB::table('user')->where('id', '<>', $id)->where('nick_name', $request->post('nick_name'))->find();

        if ($is_rename) {
            return json(['code' => 1, 'message' => '该用户名已被使用']);
        }
        $service = new \app\common\service\Users\Service();

        /* if($request->post('lv') >0){
             $is_pool = Db::table('bathing_pool')
                 ->where('lv',$add_data['lv'] = $request->post('lv'))
                 ->where('is_delete',0)
                 ->field('id')
                 ->find();
             if(!$is_pool){
                 return json()->data(['code' => 1, 'message' =>'无效等级！']);
             }
         }*/
        $data = $request->post();
        $data['lv'] = $request->post('lv') - 1;
        $result = $service->updateUser($entity, $data, $id);

        LogService::write('会员管理', '用户编辑会员');
        if (!is_int($result)) {
            return json(['code' => 1, 'message' => url('保存失败')]);
        }
        return json(['code' => 0, 'toUrl' => url('/admin/user/index')]);
    }


    /**
     * @power 会员管理|会员列表@解禁会员
     * @method POST
     */
    public function unforbidden($id)
    {
        $entity = $this->checkInfo($id);

        $entity->forbidden_time = 0;
        $entity->status = \app\common\entity\User::STATUS_DEFAULT;

        if (!$entity->save()) {
            throw new AdminException('解禁失败');
        }
        return json(['code' => 0, 'message' => 'success']);
    }

    /**
     * @power 会员管理|会员列表@认证会员
     * @method GET
     */
    public function certification($id)
    {
        $entity = userModel::where('id', $id)->find();
        if (!$entity) {
            $this->error('用户对象不存在');
        }

        return $this->render('certification', [
            'info' => $entity,
        ]);
    }

    /**
     * @power 会员管理|会员列表@认证会员
     * @method POST
     */
    public function certificationPass(Request $request, $id, $status)
    {
        //获取缓存用户详细信息
        $identity = new Identity();
        $userInfo = $identity->getUserInfo($id);

        $entity = $this->checkInfo($id);
        if (!$status) {
            return json(['code' => 0, 'message' => '状态不对']);
        }
        $certification_fail = $request->post("certification_fail");

        $entity->is_certification = $status;
        $entity->certification_fail = $certification_fail;

        if (!$entity->save()) {
            throw new AdminException('认证失败');
        }
        //认证通过送茶园
        if ($status == 1) {
            $model = new \app\index\model\User();
            $res = $model->sendRegisterReward($entity);
//            $user = new userModel();
//            $res1 = $user->recommendReward($entity->pid);
        }
        $identity->delCache($id);

        return json(['code' => 0, 'message' => 'success']);
    }

    /**
     * @power 会员管理|会员列表@手动升级
     * @method POST
     */
    public function level(Request $request)
    {
        if ($request->isPost()) {
            $userId = intval($request->post('user_id'));
            $level = intval($request->post('level'));
            $isReward = intval($request->post('is_reward'));

            $user = \app\common\entity\User::where('id', $userId)->find();
            if (!$user) {
                throw new AdminException('会员不存在');
            }
            if ($user->level == $level) {
                throw new AdminException('会员已是lv' . $level);
            }
            //直接升级
            $user->level = $level;
            if (!$user->save()) {
                throw new AdminException('升级失败');
            }
            //升级处理
            if ($isReward) {
                //赠送奖励
                $model = new \app\common\service\Level\Service();
                $reward = $model->getReward($level);
                $model->sendUserProduct($reward['product_id'], $reward['number'], $user->id);
            }
            return json(['code' => 0, 'message' => '升级成功']);
        }
    }

    private function checkInfo($id)
    {
        $entity = userModel::where('id', $id)->find();
        if (!$entity) {
            throw new AdminException('对象不存在');
        }

        return $entity;
    }

    #转账记录
    public function transfer(Request $request)
    {


        $entity = TransferLog::alias('um')->field('um.*,u.email,u1.email as to_email');
        if ($keyword = $request->get('keyword')) {
            $entity->where('u.email', $keyword);

        }
        // print_r($request->get('keyword'));

        if ($type = $request->get('type') ?? 1) {
            $entity->where('um.types', $type);
            $map['type'] = $type;
        }

        $userTable = (new \app\common\entity\User())->getTable();

        $list = $entity->leftJoin("{$userTable} u", 'um.user_id = u.id')
            ->leftJoin("{$userTable} u1", 'um.to_user = u1.id')
            ->order('um.create_time', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        $count = '0';
        if ($type == 1) {
            $where['um.types'] = $type;
            if ($keyword) {
                $where['u.email'] = $keyword;
            }

        }


        return $this->render('transfer', [
            'list' => $list,
        ]);

    }

    #头像审核列表
    public function avatarList(Request $request)
    {
        $avatarList = new AvatarLog();
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'email':
                    $avatarList->where('u.email', $keyword);
                    break;
                case 'nick_name':
                    $avatarList->where('u.nick_name', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $list = $avatarList->alias('c')
            ->field('c.*,u.nick_name,u.email')
            ->leftJoin('user u', 'c.user_id = u.id')
            ->order('create_time desc')
            ->paginate(15, false);
        return $this->render('avaList', ['list' => $list]);
    }

    #审核通过
    public function avaYes(Request $request)
    {
        $avaid = $request->param('id');
        $avaInfo = AvatarLog::where('id', $avaid)->find();
        $upd = \app\common\entity\User::where('id', $avaInfo['user_id'])->update(['avatar' => $avaInfo['avatar']]);

        if ($upd) {
            $res = AvatarLog::where('id', $avaid)->update(['status' => 1]);

            return json(['code' => 0, 'message' => '操作成功']);
        }
        return json(['code' => 1, 'message' => '操作失败']);

    }

    #审核不通过
    public function avaNo(Request $request)
    {
        $avaid = $request->param('id');

        $res = AvatarLog::where('id', $avaid)->update(['status' => 2]);
        if ($res) {

            return json(['code' => 0, 'message' => '操作成功']);
        }
        return json(['code' => 1, 'message' => '操作失败']);

    }


    #删除头像
    public function avaDel(Request $request)
    {
        $avaid = $request->param('id');
        $avatarInfo = AvatarLog::where('id', $avaid)->find();

        //file文件路径
        $filename = './' . $avatarInfo['avatar'];

        //删除
        if (file_exists($filename)) {
            $info = '原头像删除成功';
            unlink($filename);
        } else {
            $info = '原头像没找到:' . $filename;
        }

        $upd = userModel::where('id', $avatarInfo['user_id'])->update(['avatar' => '']);
        $res = AvatarLog::where('id', $avaid)->delete();

        return json(['code' => 0, 'message' => $info]);


    }

    function freeze_reward(Request $request)
    {
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
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        if (isset($map['sort'])) {
            $map['sort'] = $map['sort'] == 'desc' ? 'asc' : 'desc';
        }
        foreach ($list as $v) {
            $fishvalue = get_fishvalue($v['id']); //购买酒的价值
            $leader = \app\common\entity\User::where('id', $v['pid'])->value('nick_name');
            $next_count = \app\common\entity\User::where('pid', $v['id'])->count();

            $child = \app\common\entity\User::field('id,status')
                ->where('pid', $v['id'])
                ->where('is_active', 1)
                ->where('status', 1)
                ->select();
            //团队总人数
            $teamCount = (new \app\common\entity\User())->getTeamNum($child);


            $get_consumep = (new \app\common\entity\User())->get_consumep($v['id']);
            $get_addp = (new \app\common\entity\User())->get_addp($v['id']);
            $v['get_consumep'] = $get_consumep;  //消耗GTC
            $v['get_addp'] = $get_addp;  //充值GTC
            $v['next_count'] = $next_count;
            $v['leader'] = $leader;
            $v['getZT'] = (new \app\common\entity\User())->getZT($v['id']);

            $v['teamCount'] = $teamCount;

            $v['fishvalue'] = $fishvalue;
        }
        $query = new \app\common\entity\Team();
        return $this->render('freeze_reward', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
            'query' => $query,
        ]);
    }

    function freezing(Request $request)
    {
        $id = $request->param('id');
        $info = Db::table('user')->where('id', $id)->field('id,nick_name,is_prohibitteam,is_prohibit_extension')->find();
        //团队推广冻结
        if ($info['is_prohibitteam'] == 1 && $info['is_prohibit_extension'] == 1) {
            $info['is_freezing'] = 3;
        }
        //团队冻结
        if ($info['is_prohibitteam'] == 1 && $info['is_prohibit_extension'] == 0) {
            $info['is_freezing'] = 1;
        }
        //推广冻结
        if ($info['is_prohibitteam'] == 0 && $info['is_prohibit_extension'] == 1) {
            $info['is_freezing'] = 2;
        }
        //不冻结
        if ($info['is_prohibitteam'] == 0 && $info['is_prohibit_extension'] == 0) {
            $info['is_freezing'] = 0;
        }
        return $this->render('freezing', [
            'info' => $info
        ]);
    }

    //
    public function update_freezing(Request $request)
    {
        $id = $request->param('id');
        $is_freezing = $request->param('is_freezing');
        //不冻结
        if ($is_freezing == 0) {
            $res = Db::table('user')->where('id', $id)->update(['is_prohibitteam' => 0, 'is_prohibit_extension' => 0, 'update_time' => time()]);
        }
        //团队
        if ($is_freezing == 1) {
            $res = Db::table('user')->where('id', $id)->update(['is_prohibitteam' => 1, 'is_prohibit_extension' => 0, 'update_time' => time()]);
        }
        //推广
        if ($is_freezing == 2) {
            $res = Db::table('user')->where('id', $id)->update(['is_prohibitteam' => 0, 'is_prohibit_extension' => 1, 'update_time' => time()]);
        }
        //都冻结
        if ($is_freezing == 3) {
            $res = Db::table('user')->where('id', $id)->update(['is_prohibitteam' => 1, 'is_prohibit_extension' => 1, 'update_time' => time()]);
        }
        if ($res) {
            return json(['code' => 0, 'message' => '修改成功', 'toUrl' => url('/admin/user/freeze_reward')]);
        } else {
            return json(['code' => 1, 'message' => '修改失败']);
        }
    }

    //会员推广页面
    function editextension(Request $request)
    {
        $info = Db::table('user_extension')->where('id', 1)->find();

        return $this->render('editextension', [
            'info' => $info,
        ]);
    }

    //更改会员推广设置
    function extensionupdate(Request $request)
    {
        $post = $request->post();


        $save = [
            'bait_need1' => trim($request->post('bait_need1')),
            'bait_need2' => trim($request->post('bait_need2')),
            'bait_need3' => trim($request->post('bait_need3')),
            'profit_need1' => trim($request->post('profit_need1')),
            'profit_need2' => trim($request->post('profit_need2')),
            'profit_need3' => trim($request->post('profit_need3')),
            'push_need1' => trim($request->post('push_need1')),
            'push_need2' => trim($request->post('push_need2')),
            'push_need3' => trim($request->post('push_need3')),
            'umbrella_need1' => trim($request->post('umbrella_need1')),
            'umbrella_need2' => trim($request->post('umbrella_need2')),
            'umbrella_need3' => trim($request->post('umbrella_need3')),
            'team_profit1' => trim($request->post('team_profit1')),
            'team_profit2' => trim($request->post('team_profit2')),
            'team_profit3' => trim($request->post('team_profit3')),
            'extension_profit1' => trim($request->post('extension_profit1')),
            'extension_profit2' => trim($request->post('extension_profit2')),
            'extension_profit3' => trim($request->post('extension_profit3')),
            'update_time' => time(),

        ];

        $result = Db::table('user_extension')->where('id', $request->param('id'))->update($save);

        LogService::write('会员推广设置', '编辑推广');
        if (!$result) {
            return json(['code' => 1, 'message' => '保存失败']);
        }
        return json(['code' => 0, 'message' => '保存成功']);
    }

    //用户激活设置
    function active_set()
    {
        return $this->render('active_set', [
            'list' => \app\common\entity\Config::where('type', 1)->where('key', 'activation_num')->where('status', 1)->select()
        ]);
    }

    //领取超时设置
    function overtime_set()
    {
        return $this->render('overtime_set', [
//            'list' => \app\common\entity\Config::where('type', 1)->where('key','in','adopt_overtime,first_overtadopt_time,adopt_allow_times')->where('status',1)->select()
            'list' => \app\common\entity\Config::where('type', 1)->where('key', 'in', 'voucher_time')->where('status', 1)->select()
        ]);
    }


    //用户拉黑设置
    public function block(Request $request)
    {
        $id = $request->param('id');
        $info = userModel::where('id', $id)->find();
        if (!$info) {
            $this->error('用户对象不存在');
        }
        return $this->render('block', ['info' => $info, 'id' => $id]);
    }

    /**
     * 会员列表@拉黑
     */
    public function saveBlock(Request $request)
    {
        //$block_start_time=strtotime($request->param('block_start_time'));
        $block_end_time = strtotime($request->param('block_end_time'));
        $id = $request->param('id');
        $service = new \app\admin\service\rbac\Users\Service();
        $info = $service->getManageInfo();
        $time = time();
        $entity = userModel::get($id);
        $forbidden_num = $entity->forbidden_num;
        $entity->forbidden_stime = $time;
        $entity->forbidden_ntime = $block_end_time;
        $entity->status = -1;
        $entity->forbidden_type = 3;
        $entity->is_prohibitteam = 1;
        $entity->is_prohibit_extension = 1;
        $entity->forbidden_num = $forbidden_num + 1;

        if (!$entity->save()) {
            throw new AdminException('拉黑失败');
        }

        $save_log['uid'] = $id;
        $save_log['reason'] = "人工封禁";
        $save_log['stime'] = $time;
        $save_log['create_time'] = $time;
        $save_log['ntime'] = $block_end_time;
        $save_log['source'] = $info['name'];
        $save_log['type'] = 0;

        $inslog = TitleLog::insert($save_log);
        if (!$inslog) {
            throw new AdminException('拉黑失败');
        }

        return json(['code' => 0, 'toUrl' => url('/admin/user/index')]);
    }

    /**
     * 会员列表@解封
     */
    public function deBlock(Request $request)
    {
        $id = $request->param('id');
        $type = $request->param('type');
        $entity = userModel::get($id);
        $time = time();
        $service = new \app\admin\service\rbac\Users\Service();
        $info = $service->getManageInfo();

        $save_log['uid'] = $id;
        $save_log['reason'] = "人工解封";
        $save_log['stime'] = $entity['forbidden_stime'];
        $save_log['create_time'] = $time;
        $save_log['ntime'] = $time;
        $save_log['source'] = $info['name'];
        $save_log['type'] = 1;

        $entity->forbidden_stime = 0;
        $entity->forbidden_ntime = 0;
//         $entity->forbidden_num = 0;

        $entity->status = 1;
        $entity->forbidden_type = 0;
        $entity->is_prohibitteam = 0;
        $entity->is_prohibit_extension = 0;

        $inslog = TitleLog::insert($save_log);
        if (!$inslog) {
            throw new AdminException('解封失败');
        }

        if ($entity->save()) {
            if ($type == 2) {
                $this->redirect('user/title_list');
            } else {
                $this->redirect('user/index');
            }
        }
    }

    //玩家充值详情
    public function recharge_detail(Request $request)
    {
        $id = $request->param('id');
        $map['id'] = $id;
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
            $where = ['mwl.create_time' => ['between time', [$stime, $ntime]]];
        }
        $list = Db::table('my_wallet_log')
            ->alias('mwl')
            ->join('user u', 'u.id = mwl.uid')
            ->join('user_invite_code uic', 'uic.user_id = mwl.uid')
            ->where('mwl.uid', $id)
            ->where($where)
            ->order('mwl.create_time desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ])
            ->each(function ($item, $key) {
                if ($item['types'] == 5) {
                    $item['from_user'] = Db::table('user_invite_code')->where('user_id', $item['from_id'])->value('invite_code');
                } else {
                    $item['from_user'] = '';
                }
                return $item;
            });
        return $this->render('recharge_detail', [
            'list' => $list
        ]);
    }


    public function personal_detail(Request $request)
    {
        $id = $request->param('id');
        $map['au.id'] = $id;
        $map['id'] = $id;
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
            $map['au.stime'] = date('Y-m-d', $stime);
            $map['au.ntime'] = date('Y-m-d', $ntime);
            $where = ['mwl.create_time' => ['between time', [$stime, $ntime]]];
        }
        $id = $request->param('id');
        $info = userModel::alias('u')
            ->join('user_invite_code uic', 'uic.user_id = u.id')
            ->where('u.id', $id)
            ->field('u.*,uic.invite_code')
            ->find();
        if ($info['status'] == 1) {
            $is_show = 1;
        } else {
            $is_show = 0;
        }

        $id = $request->param('id');
        if (empty($id)) {
            $this->error('缺失参数');
        }

        $all_worth = FishModel::alias('f')
                ->join('bathing_pool bp', 'bp.id = f.pool_id')
                ->where('f.u_id', $id)
                ->where('f.is_delete', '0')
                ->where('f.is_show', '1')
                ->where('f.status', 'in', '0,1,2,3')
                ->where('bp.is_delete', '0')
                ->sum('f.worth') ?? 0;


        $all_fnum = FishModel::alias('f')
                ->join('bathing_pool bp', 'bp.id = f.pool_id')
                ->where('f.u_id', $id)
                ->where('f.is_delete', 0)
                ->where('f.is_show', '1')
                ->where('f.status', 'in', '0,1,2,3')
                ->where('bp.is_delete', '0')
                ->count('f.id') ?? 0;

        $all_aumakenum = Db::table('appointment_user')
                ->alias('au')
                ->where('au.uid', $id)
                ->where('au.types', 0)
                ->count('au.id') ?? 0;

        $all_auadoptnum = Db::table('appointment_user')
                ->alias('au')
                ->where('au.uid', $id)
                ->where('au.status', '<>', 0)
                ->count('au.id') ?? 0;

        $all_auaoknum = Db::table('appointment_user')
                ->alias('au')
                ->where('au.uid', $id)
                ->where('au.oid', '>', 0)
                ->count('au.id') ?? 0;


        $entity = FishModel::alias('f')
            ->join('bathing_pool bp', 'bp.id = f.pool_id')
            ->where('f.u_id', $id)
            ->where('f.is_delete', 0)
            ->where('f.is_show', '1')
            ->where('f.status', 'in', '0,1,2,3')
            ->where('bp.is_delete', '0')
            ->field('bp.*,f.worth,f.is_show,f.front_id,f.types,f.id fid,f.status fstatus,f.id');


        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            $entity->join('user u', 'u.id = f.u_id');
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
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);

        return $this->render('personal_detail', [
            'list' => $list,
            'all_worth' => $all_worth,
            'all_fnum' => $all_fnum,
            'all_aumakenum' => $all_aumakenum,
            'all_auadoptnum' => $all_auadoptnum,
            'all_auaoknum' => $all_auaoknum,
            'info' => $info,
            'is_show' => $is_show
        ]);
    }

    /**
     * 提币
     */
    public function verify(Request $request)
    {
        $type = $request->param('type', '', 'trim');
        $keyword = $request->param('keyword', '', 'trim');
        $startTime = $request->param('startTime', '', 'trim');
        $endTime = $request->param('endTime', '', 'trim');
        $where = [];
        // 存在关键字搜索
        if (!empty($keyword)) {
            switch ($type) {
                case 'nick_name':
                    $where['u.nick_name'] = ['like', $keyword.'%'];
                    break;

                case 'id_number':
                    $where['uvl.id_number'] = ['=', $keyword];
                    break;
            }
        }
        // 时间搜索
        $startTime = strtotime($startTime);
        $endTime = strtotime($endTime);
        if (!empty($startTime) || !empty($endTime)) {
            if (!empty($startTime) && !empty($endTime)) {
                if ($startTime > $endTime) {
                    $where['uvl.create_time'] = ['<=', $endTime];
                }else{
                    $where['uvl.create_time'] = ['between', [$startTime,$endTime]];
                }
            }else{
                if (!empty($startTime)) {
                    $where['uvl.create_time'] = ['between', [$startTime,time()]];
                }
                if (!empty($endTime)) {
                    $where['uvl.create_time'] = ['<=', $endTime];
                }
            }
        }
        //读取列表并生成分页
        $list = UserVerifyLog::alias('uvl')
            ->join('user u', 'uvl.uid=u.id', 'LEFT')
            ->field([
                'uvl.id',
                'uvl.uid',
                'u.nick_name',
                'uvl.id_name',
                'uvl.id_number',
                'uvl.status',
                'uvl.create_time',
                'uvl.done_time'
            ])
            ->where($where)
            ->order('uvl.id desc')
            ->paginate(15);
        //渲染
        return $this->render('verify', ['list' => $list]);
    }

    /**
     * 实名审批
     */
    public function verify_review()
    {
        $id = input('id');
        if (empty($id)) {
            $this->error('缺失参数');
        };
        $info = UserVerifyLog::alias('uvl')
            ->join('user u', 'uvl.uid=u.id', 'LEFT')
            ->field([
                'uvl.id',
                'uvl.uid',
                'u.nick_name',
                'uvl.id_name',
                'uvl.id_number',
                'uvl.status',
                'uvl.create_time',
                'uvl.done_time'
            ])
            ->where([
                'uvl.id' => $id
            ])
            ->find();

        return $this->render('verify_review', ['info' => $info]);
    }

    /**
     * 实名审批通过
     */
    public function verify_review_success()
    {

        $id = input('id');
        if (empty($id)) {
            $this->error('缺失参数');
            return;
        };
        $item = UserVerifyLog::where(['id' => $id])->find();
        if (empty($item)) {
            $this->error('记录不存在');
            return;
        }
        //更新记录状态为成功
        Db::startTrans();
        try {
            userModel::where(['id' => $item['uid']])->update([
                'is_verify' => 1,
                'verify_time' => time()
            ]);
            if (UserVerifyLog::where(['id' => $id])->update([
                    'status' => 1,
                    'done_time' => time()
                ]) < 1) {
                Db::rollback();
                $this->error('处理失败2');
            };
            //code...
            Db::commit();
            //成功返回
            return $this->render('verify_review_done', ['status' => 1]);
        } catch (\Throwable $th) {
            Db::rollback();
            //throw $th;
            $this->error('程序错误');
            return;
        }


    }

    /**
     * 实名审批驳回
     */
    public function verify_review_fail()
    {
        $id = input('id');
        if (empty($id)) {
            $this->error('缺失参数');
        };
        $time = time();

        //更新记录状态为失败
        if (!UserVerifyLog::where(['id' => $id])->update([
            'status' => 2,
            'done_time' => $time
        ])) {
            $this->error('处理失败2');
        };
        return $this->render('verify_review_done', ['status' => 2]);


    }

    /**
     * 会员列表@垃圾箱更新
     */
    public function is_into_dustbin(Request $request)
    {
        $id = $request->param('id');
        $is_delete = $request->param('is_delete');
        $type = $request->param('type');
        $entity = userModel::get($id);
        if ($is_delete == 0) {
            // 从垃圾箱里放出去
            $entity->is_prohibitteam = 0;
            $entity->is_prohibit_extension = 0;
            $entity->is_delete = 0;
        } else if ($is_delete == 1) {
            // 扔进垃圾箱
            $tmptime = time();
            $entity->is_prohibitteam = 1;
            $entity->is_prohibit_extension = 1;
            $entity->is_delete = 1;
        } else {

        }

        if ($entity->save()) {
            if ($type == 2) {
                $this->redirect("user/dustbin_list");
            } else {
                $this->redirect("user/index");
            }
        }
    }

    public function title_list(Request $request)
    {
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
            ->where('status', '-1')
            ->order($orderStr)
            ->distinct(true)
            ->paginate(10, false, [
                'query' => isset($map) ? $map : []
            ]);
        if (isset($map['sort'])) {
            $map['sort'] = $map['sort'] == 'desc' ? 'asc' : 'desc';
        }
        foreach ($list as $v) {
            $fishvalue = get_fishvalue($v['id']); //购买酒的价值
            $leader = \app\common\entity\User::where('id', $v['pid'])->value('nick_name');
            $next_count = \app\common\entity\User::where('pid', $v['id'])->count();

//            $child = \app\common\entity\User::field('id,status')
//                ->where('pid',$v['id'])
//                ->where('is_active',1)
//                ->where('status',1)
//                ->select();
            //团队总人数
//            $teamCount = (new \app\common\entity\User())->getTeamNum($child);

            if ($v['lv']) {

                $entity = new \app\common\entity\MyWallet();
                $teamCount = $entity->teamnum($v['id']);//团队人数

            } else {
                $teamCount = 0;
            }


            $get_consumep = (new \app\common\entity\User())->get_consumep2($v['id']);
            $get_addp = (new \app\common\entity\User())->get_addp_one($v['id']);
//            $is_delete = (new \app\common\entity\User())->getIsDelete($v['is_delete']);
            $v['get_consumep'] = $get_consumep;  //消耗GTC
            $v['get_addp'] = $get_addp;  //充值GTC
            $v['next_count'] = $next_count;
            $v['leader'] = $leader;
            $v['getZT'] = (new \app\common\entity\User())->getZT($v['id']);
//            $v['is_delete'] = $is_delete;

            $v['teamCount'] = $teamCount;

            $v['fishvalue'] = $fishvalue;
        }
        $query = new \app\common\entity\Team();
        return $this->render('title_list', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
            'query' => $query,
        ]);
    }

    public function dustbin_list(Request $request)
    {
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
            ->where('is_delete', '1')
            ->order($orderStr)
            ->distinct(true)
            ->paginate(10, false, [
                'query' => isset($map) ? $map : []
            ]);
        if (isset($map['sort'])) {
            $map['sort'] = $map['sort'] == 'desc' ? 'asc' : 'desc';
        }
        foreach ($list as $v) {
            $fishvalue = get_fishvalue($v['id']); //购买酒的价值
            $leader = \app\common\entity\User::where('id', $v['pid'])->value('nick_name');
            $next_count = \app\common\entity\User::where('pid', $v['id'])->count();

//            $child = \app\common\entity\User::field('id,status')
//                ->where('pid',$v['id'])
//                ->where('is_active',1)
//                ->where('status',1)
//                ->select();
            //团队总人数
//            $teamCount = (new \app\common\entity\User())->getTeamNum($child);

            if ($v['lv']) {

                $entity = new \app\common\entity\MyWallet();
                $teamCount = $entity->teamnum($v['id']);//团队人数

            } else {
                $teamCount = 0;
            }


            $get_consumep = (new \app\common\entity\User())->get_consumep2($v['id']);
            $get_addp = (new \app\common\entity\User())->get_addp_one($v['id']);
//            $is_delete = (new \app\common\entity\User())->getIsDelete($v['is_delete']);
            $v['get_consumep'] = $get_consumep;  //消耗GTC
            $v['get_addp'] = $get_addp;  //充值GTC
            $v['next_count'] = $next_count;
            $v['leader'] = $leader;
            $v['getZT'] = (new \app\common\entity\User())->getZT($v['id']);
//            $v['is_delete'] = $is_delete;

            $v['teamCount'] = $teamCount;

            $v['fishvalue'] = $fishvalue;
        }
        $query = new \app\common\entity\Team();
        return $this->render('exception_list', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
            'query' => $query,
        ]);
    }


    public function title_history_list(Request $request)
    {
        $entity = TitleLog::alias('t')->leftJoin('user u', 't.uid = u.id')->field('u.*,t.reason,t.stime,t.ntime,t.source,t.type');

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $entity->where('u.mobile', 'like', '%' . $keyword . '%');
                    break;
                case 'nick_name':
                    $entity->where('u.nick_name', 'like', '%' . $keyword . '%');
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
            ->order($orderStr)
            ->distinct(true)
            ->paginate(10, false, [
                'query' => isset($map) ? $map : []
            ]);
        if (isset($map['sort'])) {
            $map['sort'] = $map['sort'] == 'desc' ? 'asc' : 'desc';
        }
        foreach ($list as $v) {
            $fishvalue = get_fishvalue($v['id']); //购买酒的价值
            $leader = \app\common\entity\User::where('id', $v['pid'])->value('nick_name');
            $next_count = \app\common\entity\User::where('pid', $v['id'])->count();
            if ($v['lv']) {

                $entity = new \app\common\entity\MyWallet();
                $teamCount = $entity->teamnum($v['id']);//团队人数

            } else {
                $teamCount = 0;
            }

//            $is_delete = (new \app\common\entity\User())->getIsDelete($v['is_delete']);
            $v['getZT'] = (new \app\common\entity\User())->getZT($v['id']);
//            $v['is_delete'] = $is_delete;

            $v['teamCount'] = $teamCount;
        }
        $query = new \app\common\entity\Team();
        return $this->render('title_history_list', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
            'query' => $query,
        ]);
    }
}
