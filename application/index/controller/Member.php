<?php

namespace app\index\controller;

use app\common\entity\UserInviteCode;
use app\common\service\Users\Identity;
use think\Env;
use think\Session;
use think\Request;
use app\common\service\Users\Service;
use app\common\entity\User;
use app\common\entity\Config;
use app\common\entity\UserProduct;
use think\Db;
use app\common\entity\UserCount;
use app\common\entity\Category;
use app\common\entity\SafeQuestion;
use service\IndexLog;
use app\index\validate\RegisterForm;

class Member extends Base
{
    /**
     * 获取个人资料
     */
    public function index()
    {
        IndexLog::write('个人中心', '用户获取个人资料');
        //获取缓存用户详细信息
        $userInfo = User::field('')->where('id', $this->userId)->find();
        $question = SafeQuestion::field('id,title')->where('status', 1)->order('sort')->select();
        return json(['code' => 0, 'msg' => 'success', 'info' => [
            'userInfo' => $userInfo,
            'question' => $question,
        ]]);

    }


    /**
     * 编辑个人资料
     */
    public function set(Request $request)
    {

        $model = new \app\common\service\Users\Service();

        if (!$model->checkSafeQuestion($request->post('qid'), $request->post('answer'), $this->userId)) {
            return json(['code' => 1, 'msg' => 'Secret Security Error']);
        }
        $query = new User();
        $update_data = [];
        if ($request->post('chat_num')) {
            $update_data['chat_num'] = $request->post('chat_num');
        }
        if ($request->post('trade_address')) {
            $update_data['trade_address'] = $request->post('trade_address');
        }
        if ($request->post('remake')) {
            $update_data['remake'] = $request->post('remake');
        }
        $res = $query->where('id', $this->userId)->update($update_data);
        $userInfo = User::field('')->where('id', $this->userId)->find();
        IndexLog::write('个人中心', '用户修改个人资料');
        if (is_int($res)) {
            return json(['code' => 0, 'msg' => 'Modified success', 'info' => $userInfo]);
        }
        return json(['code' => 1, 'msg' => 'Modification failed']);
    }


    /**
     * 找回密码 修改密码
     */
    public function changeSave(Request $request)
    {

        $user = User::where("id", $this->userId)->find();


        $old_pwd = $request->post("old_pwd"); //旧密码
        $new_pwd = $request->post("new_pwd"); //新密码
        $confirm_pwd = $request->post("confirm_pwd"); //确认密码

        $phone_code = $request->post('phone_code');
        if (empty($phone_code)) {
            return json(['code' => 1, 'message' => '验证码不能为空！']);

        }
        $form = new RegisterForm();
        if (!$form->checkChange($request->post('phone_code'), $user['mobile'])) {
            return json(['code' => 1, 'msg' => '验证码输入错误']);
        }

        $service = new Service();

        if ($service->getPassword($new_pwd) == $user->password) {
            return json(['code' => 1, 'msg' => '密码没有更改.']);
        }
        if (strlen($new_pwd) < 6) {
            return json(['code' => 1, 'msg' => '密码长度至少6位']);
        }
        if ($new_pwd != $confirm_pwd) {
            return json(['code' => 1, 'msg' => '两次密码不一致']);
        }

        $res = User::where("id", $this->userId)->update(["password" => $service->getPassword($new_pwd), 'usertoken' => 0]);
        $msg = '密码更改成功，请重新登录';

        IndexLog::write('个人中心', '用户修改密码');
        if ($res) {
            return json(['code' => 0, 'msg' => $msg]);
        } else {
            return json(['code' => 1, 'msg' => '修改失败']);
        }
    }

    /**
     * 我要推广
     */
    public function spread()
    {
        $info = UserInviteCode::where('user_id', $this->userId)->value('invite_code');
        IndexLog::write('个人中心', '用户获取推广码');
        return json()->data(['code' => 0, 'msg' => 'Request successful', 'info' => $info]);
    }

    public function editUser(Request $request)
    {
        IndexLog::write('个人中心', '用户编辑个人资料');
        $data = [];
        if ($request->post('trade_address')) {
            $data['trade_address'] = $request->post('trade_address');
        }
        if ($request->post('chat_num')) {
            $data['chat_num'] = $request->post('chat_num');
        }
        if ($request->post('remake')) {
            $data['remake'] = $request->post('remake');
        }


        $res = User::where('id', $this->userId)->update($data);
        if (is_int($res)) {
            return json()->data(['code' => 0, 'msg' => 'success']);
        }
        return json()->data(['code' => 1, 'msg' => 'error']);
    }


    /**
     * 添加银行卡
     */
    public function card()
    {
        //获取缓存用户详细信息
        $identity = new Identity();
        $userInfo = $identity->getUserInfo($this->userId);

        return $this->fetch("card", ['list' => $userInfo]);
    }

    /**
     * 修改个人信息
     */
    public function updateUser(Request $request)
    {
        //获取缓存用户详细信息
        $identity = new Identity();
        $userInfo = $identity->getUserInfo($this->userId);

        $user = new Service();

        $data = array();

        $card = $request->post("card"); //银行卡号
        if ($card) {
            if ($user->checkMsg("card", $card, $userInfo->user_id)) {
                return json(['code' => 1, 'msg' => '该银行卡号已经被绑定了']);
            } else {
                $data['card'] = $card;
            }
        }
        $card_name = $request->post("card_name"); //开户行
        if ($card_name) {
            $data['card_name'] = $card_name;
        }
        $zfb = $request->post("zfb"); //支付宝
        if ($zfb) {
            if ($user->checkMsg("zfb", $zfb, $userInfo->user_id)) {
                return json(['code' => 1, 'msg' => '该支付宝号已经被绑定了']);
            } else {
                $data['zfb'] = $zfb;
            }
        }
        $zfb_image_url = $request->post("zfb_image_url");

        if ($zfb_image_url) {
            $data['zfb_image_url'] = $zfb_image_url;
        }
        $wx = $request->post("wx"); //微信
        if ($wx) {
            if ($user->checkMsg("wx", $wx, $userInfo->user_id)) {
                return json(['code' => 1, 'msg' => '该微信号已经被绑定了']);
            } else {
                $data['wx'] = $wx;
            }
        }
        $wx_image_url = $request->post("wx_image_url");
        if ($wx_image_url) {
            $data['wx_image_url'] = $wx_image_url;
        }
        $real_name = $request->post("real_name"); //真实姓名
        if ($real_name) {
            $data['real_name'] = $real_name;
        }
        $card_id = $request->post("card_id"); //身份证号
        if ($card_id) {
            if ($user->checkMsg("card_id", $card_id, $userInfo->user_id)) {
                return json(['code' => 1, 'msg' => '该身份证号已经被绑定了']);
            } else {
                $data['card_id'] = $card_id;
            }
        }
        $card_left = $request->post("card_left"); //身份证反面
        if ($card_left) {
            $data['card_left'] = $card_left;
        }
        $card_right = $request->post("card_right"); //身份证反面
        if ($card_right) {
            $data['card_right'] = $card_right;
        }
        $avatar = $request->post("avatar"); //头像
        if ($avatar) {
            $data['avatar'] = $avatar;
        }
        $money_address = $request->post("money_address"); //头像
        if ($money_address) {
            $data['money_address'] = $money_address;
        }

        $res = \app\common\entity\User::where('id', $this->userId)->update($data);
        // dump(\app\common\entity\User::getLastsql());die;
        if ($res) {
            //更新缓存
            $identity->delCache($this->userId);
            return json(['code' => 0, 'msg' => '修改成功', 'toUrl' => url('member/set')]);
        } else {
            return json(['code' => 1, 'msg' => '修改失败']);
        }
    }


    /**
     * 清除缓存
     */
    public function delCache()
    {
        $identity = new Identity();
        $identity->delCache($this->userId);
    }


    public function safepassword(Request $request)
    {
        if ($request->isPost()) {
            $validate = $this->validate($request->post(), '\app\index\validate\PasswordForm');

            if ($validate !== true) {
                return json(['code' => 1, 'msg' => $validate]);
            }

            //判断原密码是否相等
            $oldPassword = $request->post('old_pwd');
            $user = User::where('id', $this->userId)->find();
            $service = new \app\common\service\Users\Service();
            $result = $service->checkSafePassword($oldPassword, $user);

            if (!$result) {
                return json(['code' => 1, 'msg' => '原密码输入错误']);
            }

            //修改
            $user->trad_password = $service->getPassword($request->post('new_pwd'));

            if (!$user->save()) {
                return json(['code' => 1, 'msg' => '修改失败']);
            }

            return json(['code' => 0, 'msg' => '修改成功']);
        }
        return $this->fetch('safepassword');
    }


    #获取下级
    public function getChilds(Request $request)
    {

        $uid = $this->userId;

        $level = $request->post('level');

        $user = new User();

        $childs = $user->getChildsInfo1($uid, $level);
//        var_dump($childs);die;

        if ($childs) {
            foreach ($childs as &$a) {
                foreach ($a as $v) {
                    $active_time = strtotime($v['active_time']);
                    $register_time = strtotime($v['register_time']);
                    $v['active_time1'] = date('Ymd-H:i', $active_time);
                    $v['register_time1'] = date('Ymd-H:i', $register_time);

                }
            }
            return json(['code' => 0, 'msg' => '获取成功', 'info' => $childs]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);

    }


    #获取上级
    public function getParents(Request $request)
    {

        $uid = $this->userId;

        $level = $request->post('level');

        $user = new User();

        $childs = $user->getParentsInfo1($uid, $level);
        // var_dump($childs);die;

        if ($childs) {
            $userInfo = $user->where('id', $uid)->field('id,register_time,avatar,email,nick_name,mobile,level,pid,active_time,status,yekes,btc_address,eth_address,eos_address,is_active')->find();
            $childs['l0'] = $userInfo;
            foreach ($childs as &$v) {
                $active_time = strtotime($v['active_time']);
                $register_time = strtotime($v['register_time']);
                $v['active_time1'] = date('Ymd-H:i', $active_time);
                $v['register_time1'] = date('Ymd-H:i', $register_time);
            }
            return json(['code' => 0, 'msg' => '获取成功', 'info' => $childs]);

        }

        return json(['code' => 1, 'msg' => '获取失败']);

    }

    public function asd(Request $request)
    {
        $user = new User();
        $uid = $request->post('uid');
        $level = $request->post('level');

        $childs = $user->getParentsInfo($uid, $level);
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $childs]);

    }


    public function getInviteCode()
    {
        $uid = $this->userId;
        $list = Db::table('user_invite_code')->where('user_id', $uid)->find();
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);

    }

    #获取下级有多少人
    public function getChildsNum()
    {
        $uid = $this->userId;
        $user = new User();
//        var_dump($uid);die;
        $childs = $user->where('pid', $uid)->field('id')->select();
        $list = $user->getChildsNum($childs);
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $list]);


    }


    #获取上级有多少人
    public function getParentNum()
    {
        $uid = $this->userId;
        $user = new User();

        $list = $user->getParents($uid);
        return json(['code' => 0, 'msg' => '获取成功', 'info' => $list - 1]);

    }

    #是否登录
    public function is_login()
    {
        $uid = $this->userId;
        if ($uid) {
            return json(['code' => 0, 'msg' => 'yes', 'info' => $uid]);
        }
        return json(['code' => 1, 'msg' => 'no', 'url' => 'login']);

    }

    #上传头像
    public function updAvatar(Request $request)
    {
        $uid = $this->userId;
        $avatar = $request->post('avatar');
        $avatarLog = AvatarLog::where('user_id', $uid)->find();
        if ($avatarLog) {
            $res = AvatarLog::where('user_id', $uid)->update(['avatar' => $avatar, 'status' => 0, 'create_time' => time()]);
        } else {
            $res = AvatarLog::insert(['user_id' => $uid, 'avatar' => $avatar, 'status' => 0, 'create_time' => time()]);
        }
        if ($res) {
            return json(['code' => 0, 'msg' => '上传头像成功,等待审核中']);
        }
        return json(['code' => 1, 'msg' => '上传头像失败']);
    }

    /**
     * 提交实名验证
     */
    public function submitverify()
    {
        //参数不能为空
        if (!input('?post.id_name') || !input('?post.id_number')) {
            return ['code' => 1, 'msg' => '参数不足'];
        }

        //获取用户数据
        $user = User::where('id', $this->userId)->find();

        if (empty($user)) {
            return ['code' => 1, 'msg' => '用户不存在'];
        }

        // 是否已实名认证
        if ($user['is_verify']) {
            return ['code' => 1, 'msg' => '用户已认证,无需重复提交申请'];
        }

        // 是否已提交过实名申请
        $is_exist_log = Db::table('user_verify_log')->where('uid', $this->userId)->where('status', 'in', [0, 1])->find();
        if (!empty($is_exist_log)) {
            return ['code' => 1, 'msg' => '用户已提交过申请,无需重复提交'];
        }

        $post = input('post.', '', 'trim');
        // 身份证号是否已被实名
        $is_only_log = Db::table('user_verify_log')->where('id_number', $post['id_number'])->where('status', 1)->find();
        if (!empty($is_only_log)) {
            return ['code' => 1, 'msg' => '用户身份证号已被实名']; // 存在直接返回错误
        }
        // 验证身份证是否有效
        $is_verify = $this->checkVerifyID($post);
        $time = time();
        if ($is_verify['code'] !== 0) {
            //添加实名记录
            $new_user_verify_log = [
                'uid' => $user['id'],
                'id_name' => $post['id_name'],
                'id_number' => $post['id_number'],
                'status' => 0,
                'create_time' => $time,
                'done_time' => $time,
            ];
            if (!\app\common\entity\User::where(['id' => $this->userId])->update([
                    'is_verify' => 1,
                    'verify_time' => $time
                ]) || !\app\common\entity\UserVerifyLog::insert($new_user_verify_log)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '添加实名认证申请失败!'];
            } else {
                return ['code' => 0, 'msg' => '实名认证申请成功，请等待系统审核!'];
            }
        } else {
            //添加实名记录
            $new_user_verify_log = [
                'uid' => $user['id'],
                'id_name' => $post['id_name'],
                'id_number' => $post['id_number'],
                'status' => 1,
                'create_time' => $time,
                'done_time' => $time,
            ];
        }


        Db::startTrans();
        try {
            if (!\app\common\entity\User::where(['id' => $this->userId])->update([
                    'is_verify' => 1,
                    'verify_time' => $time
                ]) || !\app\common\entity\UserVerifyLog::insert($new_user_verify_log)) {
                Db::rollback();
                return ['code' => 1, 'msg' => '添加实名认证申请失败!'];
            };
            Db::commit();
            //成功返回
            return ['code' => 0, 'msg' => '实名认证成功'];
        } catch (\Throwable $th) {
            Db::rollback();
            return ['code' => 1, 'msg' => '系统错误,添加实名认证申请失败!'];
        }
    }

    /**
     * 阿里云验证身份证号是否匹配
     *
     * @param array $post
     * @return bool
     */
    public function checkVerifyID($post)
    {
        $host = "https://idenauthen.market.alicloudapi.com";
        $path = "/idenAuthentication";
        $method = "POST";
        $appcode = config('aliyun.appcode');
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type" . ":" . "application/x-www-form-urlencoded; charset=UTF-8");
        $bodys = "idNo=" . $post['id_number'] . "&name=" . $post['id_name'];
        $url = $host . $path;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        if (1 == strpos("$" . $host, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
        $result = json_decode(curl_exec($curl), true);
        if ($result['respCode'] != '0000') {
            $return_data['code'] = 1;
            switch ($result['respCode']) {
                case '0001':
                    $return_data['msg'] = '真实姓名不能为空';
                    break;

                case '0002':
                    $return_data['msg'] = '真实姓名不能包含特殊字符';
                    break;

                case '0003':
                    $return_data['msg'] = '身份证号不能为空';
                    break;

                case '0004':
                    $return_data['msg'] = '身份证号格式错误';
                    break;

                case '0007':
                    $return_data['msg'] = '无此身份证号码';
                    break;

                case '0008':
                    $return_data['msg'] = '身份证信息不匹配';
                    break;

                case '00010':
                    $return_data['msg'] = '系统维护，请稍后再试';
                    break;
                default:
                    $return_data['msg'] = '系统维护，请稍后再试';
                    break;
            }
        } else {
            $return_data['code'] = 0;
            $return_data['msg'] = '身份证信息匹配';
        }
        return $return_data;

    }

}
