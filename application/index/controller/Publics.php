<?php

namespace app\index\controller;

use app\index\validate\RegisterForm;
use app\index\model\SendCode;
use app\common\entity\ActiveApply;
use app\common\entity\ActiveLog;
use app\common\entity\BillLog;
use app\common\entity\PersonService;
use app\common\entity\StoreLog;
use app\common\entity\User;
use app\common\entity\UserInviteCode;
use app\common\entity\V4DynamicConfig;
use app\common\entity\Dynamic_Log;
use app\common\entity\V4Level;
use app\index\model\SiteAuth;
use app\common\service\Users\Service;
use app\common\entity\Config;
use app\common\service\Users\Identity;
use think\Controller;
use think\Db;
use think\Request;
use app\common\entity\Question;
use app\common\entity\Answer;
use app\common\entity\ActiveConfig;
use app\common\entity\SafeQuestion;
use app\common\entity\SystemConfig;
use service\IndexLog;


class Publics extends Controller
{

    public function _initialize()
    {

        $switch = SiteAuth::checkSite();
        if ($switch !== true) {
            if (request()->isAjax()) {
                $mobile = request()->param('mobile','','trim');
                $token = request()->param('token','','trim');
                if (!empty($mobile)) { // 是用户登录时验证
                    // 系统维护时允许白名单中的用户登录
                    $is_white = $this->isInWhiteList($mobile);
                    if ($is_white !== true) {
                        // thinkphp5.0中_initialize无法通过return返回数据
                        json(['code' => 9999, 'msg' => $switch])->send();
                        exit;
                        // return json(['code' => 1, 'msg' => $switch]); // 暂时弃用
                    }
                }else{
                    if (!empty($token)) { // 是登录后的验证
                        // 系统维护时允许白名单中的用户登录
                        $is_white = $this->isInWhiteListToken($token);
                        if ($is_white !== true) {
                            json(['code' => 9999, 'msg' => $switch])->send();
                            exit;
                            // $this->redirect('publics/index');
                        }
                    }
                }
                
            } else {
                (new SiteAuth())->alert($switch);
            }
        }
        parent::_initialize();
    }

    public function index()
    {

        return json(['code' => 1, 'msg' => 'Login invalid', 'url' => 'login']);
    }

    /**
     * 是否在白名单内
     *
     * @param  $mobile
     * @return boolean
     */
    public function isInWhiteList($mobile)
    {
        if (empty($mobile)) {
            return false;
        }
        $phone_white_list = SystemConfig::where('id','>',0)->value('phone_white_list');
        $phone_white_list = explode(';',$phone_white_list);
        if (!in_array($mobile,$phone_white_list)) {
            return false;
        }
        return true;
    }

    /**
     * 是否在白名单内
     *
     * @param  $mobile
     * @return boolean
     */
    public function isInWhiteListToken($token)
    {
        if (empty($token)) {
            return false;
        }
        $phone_white_list = SystemConfig::where('id','>',0)->value('phone_white_list');
        $phone_white_list = explode(';',$phone_white_list);
        $white_users_token = User::where('mobile','in',$phone_white_list)->column('usertoken');
        $white_users_token = array_filter($white_users_token);
        // halt($white_users_token);
        if (in_array($token,$white_users_token)) {
            return true;
        }
        return false;
    }


    /**
     *
     * 处理登录；
     */
    public function login(Request $request)
    {

        $result = $this->validate($request->post(), 'app\index\validate\LoginForm');

        if ($result !== true) {
            return json(['code' => 1, 'msg' => $result]);
        }
        $model = new \app\index\model\User();
        $result = $model->doLogin($request->post('mobile'), $request->post('password'));
        $user = \app\common\entity\User::where('mobile', $request->post('mobile'))->field('id,status,forbidden_type,forbidden_ntime,forbidden_num,is_active,usertoken,is_delete')->find();


        if (!$user) {
            return json(['code' => 1, 'msg' => '无效用户！']);
        }


        if ($user->status == -1) {
            $type = $user->forbidden_type;//封号原因  1：超时未打款 2：永久封号 3：其它封禁请联系后台
            if ($type == 1) {
                $rearr['msg'] = '超时未打款，封禁到期时间：' . date('Y-m-d H:i:s', $user->forbidden_ntime);
            } else {
                $forbidden_num = $user->forbidden_num;
                if ($forbidden_num > 0 && $forbidden_num < 3) {
                    if ($forbidden_num == 1) {
                        $block_time = '1天';
                    }else{
                        $block_time = '3天';
                    }
                    $rearr['msg'] = '该账号已被封禁'.$block_time.'，封禁到期时间：' . date('Y-m-d H:i:s', $user->forbidden_ntime);
                }else{
                    $rearr['msg'] = '该账号已被永久封禁';
                }
                
            }

            // return json(['code' => -1, 'msg' => '帐户已被禁用,请与服务人员联系！', 'info' => $rearr]);
            return json(['code' => -1, 'msg' => $rearr['msg'], 'info' => $rearr]);

        }
        if ($user->is_delete == 1) {
            return json(['code' => -1, 'msg' => '账号已扔进垃圾箱，请与客服联系！']);
        }
        if ($result !== true) {
            return json(['code' => 1, 'msg' => $result]);
        }


        $service = new Identity();
        $save['update_time'] = time();
        $token = $service->getusertoken($user['id'], $save['update_time']);
        $save['usertoken'] = $token;

        $request = Request::instance();
        $save['login_ip'] = $request->ip();
        $save['login_time'] = time();
        $is_save = \app\common\entity\User::where('id', $user['id'])->update($save);
        if (!$is_save) {
            return json(['code' => 1, 'msg' => '登录失败']);
        }

        IndexLog::write('用户登录', '用户登录', $request->post('mobile'));


        return json(['code' => 0, 'msg' => '登录成功', 'info' => $token]);
    }


    /**
     * 获得公告
     * @return \think\response\Json
     */
    public function get_notice()
    {

        $is_a = Db::table('article')
            ->field('title,article_id id')
            ->where('category', 4)
            ->where('status', 1)
            ->order('sort desc')
            ->find();

        if ($is_a) {
            $re[0]['id'] = $is_a['id'];
            $re[0]['title'] = $is_a['title'];
        } else {
            $re = '';
        }

        return json(['code' => 0, 'msg' => 'access!', 'info' => $re]);
    }


    public function feed_instructions()
    {

        $is_a = Db::table('article')
            ->field('title,article_id id,content')
            ->where('category', 2)
            ->where('status', 1)
            ->order('sort desc')
            ->find();

        if ($is_a) {
            $re['id'] = $is_a['id'];
            $re['title'] = $is_a['title'];
            $re['content'] = $is_a['content'];
        } else {
            $re = '';
        }

        return json(['code' => 0, 'msg' => 'access!', 'info' => $re]);
    }

    public function user_agreement()
    {

        $is_a = Db::table('article')
            ->field('title,article_id id,content')
            ->where('category', 1)
            ->where('status', 1)
            ->order('sort desc')
            ->find();

        if ($is_a) {
            $re['id'] = $is_a['id'];
            $re['title'] = $is_a['title'];
            $re['content'] = $is_a['content'];
        } else {
            $re = '';
        }


        return json(['code' => 0, 'msg' => 'access!', 'info' => $re]);
    }

    public function get_outs()
    {

        $is_a = Db::table('article')
            ->field('title,article_id id,content')
            ->where('category', 5)
            ->where('status', 1)
            ->order('sort desc')
            ->find();

        if ($is_a) {
            $re['id'] = $is_a['id'];
            $re['title'] = $is_a['title'];
            $re['content'] = $is_a['content'];
        } else {
            $re = '';
        }


        return json(['code' => 0, 'msg' => 'access!', 'info' => $re]);
    }

    /**
     * 获得幻灯片
     * @return \think\response\Json
     */
    public function get_wheel_planting()
    {
        $num = Config::getValue('wheel_planting_num');
        $num = $num ? $num : 3;
        $list = Db::table('wheelplanting')
            ->where('status', 1)
            ->where('is_delete', 0)
            ->field('remarks,img,url,1 type') // type=1是正常轮播图,type=2是排行榜数据
            ->order('sort asc')
            ->paginate($num)
            ->toArray();
        $leader_board = $this->get_week_leader_board(); // 获取排行榜数据
        $data = ['remarks' => '排行榜', 'img' => '', 'url' => '', 'type' => 2, 'data' => $leader_board];
        array_unshift($list['data'], $data); // 排行榜数据置为第一位
        return json(['code' => 0, 'msg' => 'access!', 'info' => $list['data']]);
    }

    /**
     * 获取每周房产利润排行榜
     * 缓存一天的数据，隔天更新
     *
     * @return void
     */
    public function get_week_leader_board()
    {
        // 获取Redis中是否已存在数据
        $week_leader_board = cache('week_leader_board');
        if ($week_leader_board !== false) {
            // return json($week_leader_board);
            return $week_leader_board;
        }
        //获取本周起始时间戳和结束时间戳
        $beginTime = mktime(0,0,0,date('m'),date('d')-date('w')+1,date('Y'));
        $endTime   = mktime(23,59,59,date('m'),date('d')-date('w')+7,date('Y'));

        // 获取上周起始时间戳和结束时间戳
        // $beginTime = mktime(0,0,0,date('m'),date('d')-date('w')+1-7,date('Y'));
        // $endTime   = mktime(23,59,59,date('m'),date('d')-date('w')+7-7,date('Y'));

        // 获取上个月起始时间戳和结束时间戳
        // $beginTime = strtotime(date('Y-m-01 00:00:00',strtotime('-1 month')));
        // $endTime   = strtotime(date("Y-m-d 23:59:59", strtotime(-date('d').'day')));

        $build_sql = Db::table('user_profit_log')
                   ->where('create_time', 'between',[$beginTime,$endTime])
                   ->sum('number');
        $list = Db::table('user')->alias('u')
              ->join('user_profit_log upl', 'u.id = upl.uid')
              ->join('user_invite_code uic', 'u.id = uic.user_id')
              ->field("u.id,$build_sql total_number,sum(upl.number) profit_number,uic.invite_code user_code,u.nick_name")
              ->where('upl.create_time', 'between',[$beginTime,$endTime])
              ->group('u.id')
              ->order('profit_number desc')
              ->limit(3)
              ->select();
        if (empty($list)) {
            // return json([]);
            return [];
        }
        foreach ($list as $key => &$value) {
            $value['percentage'] = sprintf('%.2f', $value['profit_number'] / $value['total_number']);
        }
        // 缓存时间至今天结束为止
        $beginToday = time();
        $endToday = mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        cache('week_leader_board', $list, $endToday - $beginToday);
        // return json($list);
        return $list;
    }


    /**
     * 注册接口
     */
    public function register(Request $request)
    {

        $phone_code = $request->post('phone_code');
        if (empty($phone_code)) {
            return json(['code' => 1, 'message' => '验证码不能为空！']);
        }
        $model = new \app\index\model\User();
        $remake = $model->checkRegisterOpen();
        //判断平台是否关闭
        if (false !== $remake) {
            return json(['code' => 1, 'msg' => $remake]);
        }
//         if (!$model->checkIp()) {
//             return json(['code' => 1, 'msg' => '注册过多！']);
//         }

        if (User::checkMobile($request->post('mobile'))) {
            return json(['code' => 1, 'msg' => '此手机已注册！']);
        }
        if (User::checkName($request->post('nick_name'))) {
            return json(['code' => 1, 'msg' => '此帐户已注册！']);
        }
//
        if (!UserInviteCode::getUserIdByCode($request->get('invite_code')) && $request->get('invite_code')) {
            return json(['code' => 1, 'msg' => '邀请代码不存在，无法注册']);
        }

        $validate = $this->validate($request->post(), '\app\index\validate\RegisterForm');
        if ($validate !== true) {
            return json(['code' => 1, 'msg' => $validate]);
        }


        $form = new RegisterForm();
       if (!$form->checkCode($request->post('phone_code'), $request->post('mobile'))) {
           return json(['code' => 1, 'msg' => '验证码输入错误']);
       }

        $register_data = $request->post();
        $area = getLocation($request->post('mobile'));
        if ($area['code'] == 200) {
            $register_data['province'] = $area['data']['province'];//归属地省
            $register_data['city'] = $area['data']['city'];//归属地市
            $register_data['service'] = $area['data']['service'];//号码服务商
        }

//		return $register_data;
//		exit;
        //注册处理
        Db::startTrans();
        try {
            $result = $model->doRegister($register_data);
            if (!$result) {
                throw new \Exception('保存失败');
            }
            IndexLog::write('个人中心', '用户注册', $request->post('mobile'), 1);
            Db::commit();
            return json(['code' => 0, 'msg' => '注册成功']);
        } catch (\Exception $e) {
            Db::rollback();
            return json(['code' => 1, 'msg' => $e->getMessage()]);
        }
        return json(['code' => 1, 'msg' => '注册失败']);
    }


    /**
     * 找回密码 修改密码
     */
    public function changeSave(Request $request)
    {
        $mobile = $request->post("mobile");
        //检验手机号码
        if (!preg_match('#^1\d{10}$#', $mobile)) {
            return json(['code' => 1, 'msg' => '手机号码格式不正确']);
        }
        $user = User::where("mobile", $mobile)->find();
        //判断手机号码是否注册
        if (!User::checkMobile($mobile)) {
            return json(['code' => 1, 'msg' => '此账号不存在，请重新填写']);
        }
        $phone_code = $request->post('phone_code');
        if (empty($phone_code)) {
            return json(['code' => 1, 'msg' => '验证码不能为空！']);

        }

        $new_pwd = $request->post("new_pwd"); //新密码
//        $confirm_pwd = $new_pwd; //确认密码

        $service = new Service();
        if ($service->getPassword($new_pwd) == $user->password) {
            return json(['code' => 1, 'msg' => '密码没变']);
        }

        if (strlen($new_pwd) < 6) {
            return json(['code' => 1, 'msg' => '密码长度至少6位']);
        }

//        if ($new_pwd != $confirm_pwd) {
//            return json(['code' => 1, 'message' => '两次密码输入不一致']);
//        }

        $form = new RegisterForm();
        if (!$form->checkChange($request->post('phone_code'), $request->post('mobile'))) {
            return json(['code' => 1, 'msg' => '验证码输入错误']);
        }

        $res = User::where("mobile", $mobile)->update(["password" => $service->getPassword($new_pwd)]);
        if ($res) {
            return json(['code' => 0, 'msg' => '密码修改成功']);
        } else {
            return json(['code' => 1, 'msg' => '密码修改失败']);
        }
    }


    /**
     * 申诉
     */
    public function appeal_user(Request $request)
    {
        $mobile = $request->post("mobile");
        $content = $request->post("content");
        $phone_code = $request->post('phone_code');
        if (empty($phone_code)) {
            return json(['code' => 1, 'message' => '验证码不能为空！']);

        }
        //检验手机号码
        if (!preg_match('#^1\d{10}$#', $mobile)) {
            return json(['code' => 1, 'message' => '手机号码格式不正确']);
        }
        if (empty($content)) {
            return json(['code' => 1, 'message' => '申诉内容不能为空']);
        }
        $len = mb_strlen($content);
        if ($len > 240) {
            return json(['code' => 1, 'message' => '申诉内容不得超过240个字符']);
        }

        //判断手机号码是否注册
        $is_user = User::checkMobile($mobile);
        if (!$is_user) {
            return json(['code' => 1, 'message' => '此账号不存在，请重新填写']);
        }
        $form = new RegisterForm();
        if (!$form->checkAppeal($request->post('phone_code'), $request->post('mobile'))) {
            return json(['code' => 1, 'msg' => '验证码输入错误']);
        }

        $is_appeal_user = Db::table('appeal_user')->where('u_id', $is_user['id'])->where('status', 0)->field('id')->find();

        if ($is_appeal_user) {
            return json(['code' => 1, 'message' => '已提交过申诉，请耐心等待回复']);
        }


        $add['content'] = trim($content);
        $add['u_id'] = $is_user['id'];
        $add['mobile'] = $is_user['mobile'];
        $add['create_time'] = time();
        $res = Db::table('appeal_user')->insert($add);


        if ($res) {
            return json(['code' => 0, 'message' => '提交过申诉成功，请耐心等待回复']);
        } else {
            return json(['code' => 1, 'message' => '提交过申诉失败']);
        }
    }


    /*
 * 发送注册验证码
 */
    public function send(Request $request)
    {
        if ($request->isPost()) {
            $mobile = $request->post('mobile');
            //检验手机号码
            if (!preg_match('/^[1][3-9][0-9]{9}$/', $mobile)) {
                return json(['code' => 1, 'msg' => '手机号码格式不正确', 'info' => $mobile]);
            }
            $types = $request->post('types');

            $have_user = 0;
            //判断手机号码是否已被注册
            if (User::checkMobile($mobile)) {
                $have_user = 1;
            }


            if (empty($types)) {
                if ($have_user) {
                    return json(['code' => 1, 'msg' => '此账号已被注册，请重新填写']);
                }
                $model = new SendCode($mobile, 'register');

            } elseif ($types == 1) {
                if (!$have_user) {
                    return json(['code' => 1, 'msg' => '无效用户']);
                }
                $model = new SendCode($mobile, 'change-password');
            } elseif ($types == 2) {
                if (!$have_user) {
                    return json(['code' => 1, 'msg' => '无效用户']);
                }
                $model = new SendCode($mobile, 'change-pay-password');

            } elseif ($types == 3) {
                if (!$have_user) {
                    return json(['code' => 1, 'msg' => '无效用户']);
                }
                $model = new SendCode($mobile, 'appeal');

            } else {
                return json(['code' => 1, 'msg' => '无效类型']);
            }


            if ($model->send()) {

                //return json(['code' => 0, 'msg' => '你的验证码发送成功','codenum'=>$model->tmpgetCode()]);
                return json(['code' => 0, 'msg' => '你的验证码发送成功']);
            }

            return json(['code' => 1, 'msg' => '发送失败']);
        }
    }

    /**
     * 客服中心
     * @param Request $request
     * @return $this
     */
    public function customer_service(Request $request)
    {
        $page = $request->post('page') ? $request->post('page') : 1;
        $limit = $request->post('limit') ? $request->post('limit') : 15;
        $types = 3;
        $list = Db::table('article')
            ->field('title,article_id id')
            ->where('category', $types)
            ->where('status', 1)
            ->order('sort desc')
            ->page($page)
            ->paginate($limit)
            ->toArray();


        $list = $list['data'];


        return json()->data(['code' => 0, 'info' => $list]);
    }

    public function get_article(Request $request)
    {
        $id = $request->post('id');
        $is_a = Db::table('article')
            ->field('title,content,article_id id,create_time')
            ->where('status', 1)
            ->where('article_id', $id)
            ->order('sort desc')
            ->find();

        if (!$is_a) {
            return json(['code' => 1, 'msg' => '无效对象']);
        }

        return json()->data(['code' => 0, 'info' => $is_a]);
    }

    public function get_notice_list(Request $request)
    {
        $page = $request->post('page') ? $request->post('page') : 1;
        $limit = $request->post('limit') ? $request->post('limit') : 12;
        $is_a = Db::table('article')
            ->field('title,article_id id,create_time')
            ->where('status', 1)
            ->order('id desc')
            ->page($page)
            ->paginate($limit)
            ->toArray();

        if (!$is_a) {
            return json(['code' => 1, 'msg' => '无效对象']);
        }
        foreach ($is_a['data'] as $k => $v) {
            $is_a['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        return json()->data(['code' => 0, 'info' => $is_a['data']]);
    }

}
