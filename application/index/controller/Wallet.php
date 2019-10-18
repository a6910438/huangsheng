<?php

namespace app\index\controller;

use app\common\entity\Charge;
use app\common\entity\UserInviteCode;
use app\common\service\Market\Auth;
use app\common\service\Users\Identity;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Grafika\Color;
use Grafika\Grafika;
use think\facade\Env;
use think\facade\Session;
use think\facade\Url;
use think\Request;
use app\common\service\Users\Service;
use app\common\entity\User;
use app\common\entity\Currency;
use app\common\entity\Mywallet;
use app\common\entity\Config;
use app\common\entity\UserProduct;
use app\common\entity\UserMagicLog;
use Zxing\Qrcode\QRCodeReader;
use think\Db;
use app\common\entity\UserCount;
use app\common\entity\Category;

class Wallet extends Base {

    public function index() {
        
        //获取缓存用户详细信息
        $userInfo = User::where('id', $this->userId)->find();

        // print_r($userInfo);
        //获取用户冻结资金 和交易总数
        $freeze = $userInfo->getFreeze();
        $config = new Config();
        $config->delCache();
        $config = new Config();
        $wallet = bcadd($userInfo['magic'] ,$freeze['freeze'],8);
        $mobile = $userInfo->getMobile();

        return $this->fetch('index', [
                    'list' => $userInfo,
                    'freeze' => $freeze,
                    'mobile' => $mobile,
                    'wallet' => $wallet,
                    'credit_qrcode' => $config->getValue('credit_qrcode'),
                    'money_name' => $config->getValue('web_money_name'),
        ]);
    }

    public function my_wallet() {
        
        //获取缓存用户详细信息
        $userInfo = User::where('id', $this->userId)->find();

        // print_r($userInfo);
        //获取用户冻结资金 和交易总数
        $freeze = $userInfo->getFreeze();
        $config = new Config();
        $config->delCache();
        $config = new Config();
        $wallet = bcadd($userInfo['magic'] ,$freeze['freeze'],8);
        $mobile = $userInfo->getMobile();
        

        $currency = Currency::all(function($query){
            $query;
        });
        $count = 0.0000000;
        foreach($currency as &$row){
            
            $row['money'] = Mywallet::where('user_id', $this->userId) -> where('money_type' , $row['title']) -> value('number');
            $row['freeze'] = Mywallet::where('user_id', $this->userId) -> where('money_type' , $row['title']) -> value('freeze');

            $count += $row['money'];
        }

        unset($row);
        // echo '<pre>';
        // print_r($currency);

        return $this->fetch('my_wallet', [
                    'list' => $userInfo,
                    'currency' => $currency,
                    'count' => $count
                    
        ]);
    }

    public function charge() {
        return $this->fetch('charge', ['list' => Charge::select()]);
    }

    /**
     * 设置页面
     */
    public function set() {
        //获取缓存用户详细信息
        // $identity = new Identity();
        // $identity->delCache($this->userId);
        $identity = new Identity();
        $userInfo = $identity->getUserInfo($this->userId);
        return $this->fetch('set', ["list" => $userInfo]);
    }

    /**
     * 关于
     */
    public function about() {
        return $this->fetch("about");
    }

    /**
     * 修改密码页面
     */
    public function password() {
        return $this->fetch("password");
    }

    //便民
    public function conven() {
        $article = new \app\index\model\Article();
        $articleList = $article->getArticleList(3);
        return $this->fetch('conven', ["list" => $articleList]);
    }

    /**
     * 联盟
     */
    public function union() {
        //统计所有
        $categoryModel = new Category();
        $fids = $categoryModel->getSubChild($this->userId);
        $userTotal = $userRate = 0;
        $userList = [];
        //剔除自己
        if(isset($fids[0])){
            unset($fids[0]);
        }
        if($fids){
            $userList = User::where('id','in',$fids)->field('id,avatar,pid,mobile,nick_name,product_rate,invite_count,register_time')->select();
            $userList = $userList->toArray();
            $userModel = new User();
            foreach($userList as $k=>$v){
                $userList[$k]['team_rate'] = $userModel->getTeamRate($v['id']);
            }
            $userTotal = count($userList);
            $userRate = array_sum(array_column($userList, 'product_rate'));
        }
        $userInfo = User::where('id', $this->userId)->find();

        return $this->fetch('union', [
                    "list" => $userInfo,
                    "userList" => $userList,
                    "userTotal" => $userTotal,
                    "userRate" => sprintf('%.5f', $userRate),
            ]
        );
    }
    /**
     * 联盟
     */
    public function unionold() {
        $userInfo = User::where('id', $this->userId)->find();
        $usercountInfo = UserCount::where('user_id',$this->userId)->find();
        //获得直推会员
        $userList = $userInfo->getChilds($this->userId);
        $userTotal = $userRate = 0;
        $userCountByIdList = [];
        if($userList){
            $userIds = [];
            foreach($userList as $v){
                $userIds[] = $v->id;
            }
            $usercountList = UserCount::where('user_id','in', $userIds)->select();
            $sumRate = 0;
            foreach($usercountList as $k=>$v){
                $sumRate += $v['rate'];
                $userCountByIdList[$v['user_id']]['rate'] = $v['rate'];
            }
            if($usercountInfo){
                $userTotal = $usercountInfo->total;
                $userRate = $usercountInfo->rate + $sumRate;
            }
        }
        return $this->fetch('union', [
                    "list" => $userInfo,
                    "userList" => $userList,
                    "userTotal" => $userTotal,
                    "userRate" => $userRate,
                    "userCountByIdList" => $userCountByIdList,
            ]
        );
    }

    /**
     * 团队
     */
    public function team() {
        $userInfo = User::where('id', $this->userId)->find();

        if ($userInfo['tid']) {
            //工会信息
            $teamInfo = \app\common\entity\Team::where('id', $userInfo['tid'])->find();
            //查询会长
            $leaderInfo = User::where('id', $teamInfo['uid'])->find();
            //若是有团队
            return $this->fetch('teamInfo', [
                        "teamInfo" => $teamInfo,
                        "leaderInfo" => $leaderInfo,
                        "userId" => $this->userId
                            ]
            );
        } else {
            //若是没有团队
            return $this->fetch('teamList');
        }
    }

    /**
     * 团队成员列表
     */
    public function teamUserList(Request $request) {
        $tid = $request->post('tid', 0);
        $page = $request->post('page', 1);
        $tuid = $request->post('tuid', 0);

        $model = new \app\common\entity\TeamUser();
        $list = $model->getList($tid, $page, $tuid);

        return $this->ajaxreturn($list, '', true);
    }

    /**
     * 退出工会
     */
    public function exitTeam() {
        $userInfo = User::where('id', $this->userId)->find();
        if ($userInfo->tid <= 0) {
            return $this->ajaxreturn('', '你还没有加入工会');
        }
        $teamInfo = \app\common\entity\Team::where('id', $userInfo->tid)->find();
        if (!$teamInfo) {
            $userInfo->tid = 0;
            $userInfo->save();
            return $this->ajaxreturn('', '', true);
        }
        $teamUserModel = new \app\common\entity\TeamUser();
        $res = $teamUserModel->where('uid', $this->userId)->delete();
        if ($res) {
            $teamInfo->count = $teamInfo['count'] - 1;
            $calculationnew = $teamInfo->team_calculation - $userInfo->product_rate;
            $teamInfo->team_calculation = $calculationnew < 0 ? 0 : $calculationnew;
            $teamInfo->save();
            $userInfo->tid = 0;
            $userInfo->save();
        } else {
            //删除失败
            return $this->ajaxreturn('', '操作失败');
        }
    }

    /**
     * 创建公会
     */
    public function teamCreate(Request $request) {
        if ($request->isAjax()) {
            $result = $this->validate($request->post(), 'app\index\validate\TeamForm');
            if ($result !== true) {
                return json([
                    'code' => -1,
                    'msg' => $result,
                ]);
            }
            $userInfo = User::where('id', $this->userId)->find();
            $temModel = new \app\common\entity\Team();
            $tid = $temModel->addTeam($request->post(), $userInfo);
            if ($tid) {
                //添加team user
                $teamUserModel = new \app\common\entity\TeamUser();
                $teamUserModel->save([
                    'tid' => $tid,
                    'uid' => $this->userId,
                    'create_time' => date('Y-m-d H:i:s'),
                ]);
                $userInfo->tid = $tid;
                $userInfo->save();
                return json([
                    'code' => 0,
                    'toUrl' => url('team'),
                    'msg' => '创建成功 '
                ]);
            } else {
                return json([
                    'code' => -1,
                    'msg' => '创建失败',
                ]);
            }
        }
        $userInfo = User::where('id', $this->userId)->find();
        if ($userInfo['tid']) {
            $this->redirect('team');
        }
        return $this->fetch('teamCreate', [
                    'userInfo' => $userInfo,
        ]);
    }

    /**
     * 加入工会
     */
    public function joinTeam(Request $request) {
        $tid = $request->post('tid', 0);
        if (!$tid) {
            return $this->ajaxreturn('', '缺少参数tid');
        }
        //查询公会是否存在
        $teamInfo = \app\common\entity\Team::where('id', $tid)->find();
        if (!$teamInfo['id']) {
            return $this->ajaxreturn('', '工会不存在');
        }
        $teamUserModel = new \app\common\entity\TeamUser();
        $teamUserId = $teamUserModel->add($tid, $this->userId);
        //添加成功
        if ($teamUserId) {
            //更改工会人数
            $userModel = new User();
            $userModel->where('id', $this->userId)->update(['tid' => $tid]);
            $teamInfo->count = $teamInfo['count'] + 1;
            $teamInfo->team_calculation = $teamInfo->team_calculation + $userInfo->product_rate;
            $teamInfo->save();
            return $this->ajaxreturn('', '加入成功', true);
        } else {
            return $this->ajaxreturn('', '加入失败');
        }
    }

    /**
     * 公会列表
     * @param Request $request
     * @return type
     */
    public function teamList(Request $request) {
        $page = $request->post('page', 1);
        $limit = $request->post('limit', 1);

        $model = new \app\common\entity\Team();
        $list = $model->getList($page, $limit);

        return json([
            'status' => true,
            'info' => 'success',
            'data' => $list
        ]);
    }

    /**
     * 修改密码
     */
    public function updatePassword(Request $request) {
        $validate = $this->validate($request->post(), '\app\index\validate\PasswordForm');

        if ($validate !== true) {
            return json(['code' => 1, 'msg' => $validate]);
        }

        $oldPassword = $request->post('old_pwd');
        $user = User::where('id', $this->userId)->find();
        $service = new \app\common\service\Users\Service();
        $result = $service->checkPassword($oldPassword, $user);

        if (!$result) {
            return json(['code' => 1, 'msg' => '原密码输入错误']);
        }

        //修改
        $user->password = $service->getPassword($request->post('new_pwd'));

        if ($user->save() === false) {
            return json(['code' => 1, 'msg' => '修改失败']);
        }

        return json(['code' => 0, 'msg' => '修改成功']);
    }

    /**
     * 新手解答
     */
    public function articleList() {
        //获取缓存用户详细信息
        $article = new \app\index\model\Article();
        $articleList = $article->getArticleList(2);
        return $this->fetch('articleList', ["list" => $articleList]);
    }

    /**
     * 问题留言
     */
    public function submitMsg(Request $request) {
        //获取缓存用户详细信息
        $identity = new Identity();
        $userInfo = $identity->getUserInfo($this->userId);

        //内容
        $data['content'] = $request->post("content");
        $data['create_time'] = time();
        $data['user_id'] = $this->userId;

        $res = \app\common\entity\Message::insert($data);
        if ($res) {
            return json(['code' => 0, 'msg' => '提交成功', 'toUrl' => url('member/message')]);
        } else {
            return json(['code' => 1, 'msg' => '提交失败']);
        }
    }

    /**
     * 客服页面
     */
    public function message() {

        return $this->fetch("message",['kfmobile' => Config::getValue('kf_mobile')]);
    }
    /**
     * 反馈列表 - 发件箱
     */
    public function sendbox(){
        $entity = \app\common\entity\Message::field('m.*, u.nick_name, u.avatar')
                ->alias("m")
                ->leftJoin("user u", 'm.user_id = u.id')
                ->where('m.user_id', $this->userId)
                ->order('m.create_time', 'desc')
                ->select();
        return $this->fetch("sendbox", ['list' => $entity]);
    }
    /**
     * 收件箱
     */
    public function inbox(){
        $entity = \app\common\entity\MessageReply::field('m.*,mi.content o_content, u.nick_name, u.avatar')
                ->alias("m")
                ->leftJoin("user u", 'm.user_id = u.id')
                ->leftJoin("message mi", 'mi.message_id = m.message_id')
                ->where('m.user_id', $this->userId)
                ->order('m.create_time', 'desc')
                ->select();
        return $this->fetch("inbox", ['list' => $entity]);
    }

    /**
     * 实名认证
     */
    public function certification() {
        //获取缓存用户详细信息
        $identity = new Identity();
        $userInfo = $identity->getUserInfo($this->userId);

        return $this->fetch("certification", ['list' => $userInfo]);
    }

    /**
     * 实名认证下一步
     */
    public function lastreal(Request $request) {
        $data['real_name'] = $request->get("real_name");
        $data['card_id'] = $request->get("card_id");

        if (!$data['real_name'] || !$data['card_id']) {
            $this->error("请输入姓名和身份证号！！");
        }

        //获取缓存用户详细信息
        $identity = new Identity();
        $userInfo = $identity->getUserInfo($this->userId);

        return $this->fetch("lastreal", ['list' => $userInfo, "data" => $data]);
    }

    /**
     * 支付宝
     */
    public function zfb() {
        //获取缓存用户详细信息
        $identity = new Identity();
        $userInfo = $identity->getUserInfo($this->userId);

        return $this->fetch("zfb", ['list' => $userInfo]);
    }

    /**
     * 微信
     */
    public function wx() {
        //获取缓存用户详细信息
        $identity = new Identity();
        $userInfo = $identity->getUserInfo($this->userId);

        return $this->fetch("wx", ['list' => $userInfo]);
    }

    /**
     * 钱包地址
     */
    public function money() {
        //获取缓存用户详细信息
        $identity = new Identity();
        $userInfo = $identity->getUserInfo($this->userId);

        return $this->fetch("money", ['list' => $userInfo]);
    }

    /**
     * 添加银行卡
     */
    public function card() {
        //获取缓存用户详细信息
        $identity = new Identity();
        $userInfo = $identity->getUserInfo($this->userId);

        return $this->fetch("card", ['list' => $userInfo]);
    }

    /**
     * 修改个人信息
     */
    public function updateUser(Request $request) {
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
     * 魔盒
     */
    public function magicbox() {
        $user_product = new UserProduct();
        $magicList = $user_product->getBox($this->userId);
        return $this->fetch("magicbox", ["magicList" => $magicList,'moneyName'=>Config::getValue("web_money_name")]);
    }
    /**
     * 茶园收益
     */
    public function productIncome() {
        $user_product = new UserProduct();
        $magicList = $user_product->getBox($this->userId);
        $magicList = $magicList->toArray();
        foreach($magicList as $k=>$v){
            if($v['status']==1){
                $magicList[$k]['statusTitle'] = '正在运行';
                $magicList[$k]['hour'] = $this->getHour($v['buy_time']);
            }else{
                $magicList[$k]['statusTitle'] = '已到期';
                $magicList[$k]['hour'] = $this->getHour($v['buy_time'],$v['end_time']);
            }
        }
        return $this->fetch("productIncome", ["lists" => $magicList,'moneyName'=>Config::getValue("web_money_name")]);
    }
    private function getHour($timestamp,$timestamp2 = ''){
        $timestamp2 = $timestamp2 ? $timestamp2 : time();
        $diff = abs($timestamp2 - $timestamp);
        $hour = round($diff / 3600,1);
        return $hour;
    }
    /**
     * 动画页面
     */
    public function income(Request $request){
        $id = $request->get('id');
        $user_product = new UserProduct();
        $magicInfo = $user_product->getInfo($id,$this->userId);
        $magicInfo['hour'] = 0;
        if($magicInfo['end_time'] > time()){
            $magicInfo['hour'] = $this->getHour(time(),$magicInfo['end_time']);
        }
        return $this->fetch("income", ["magicInfo" => $magicInfo,'moneyName'=>Config::getValue("web_money_name")]);
    }

    /**
     * 清除缓存
     */
    public function delCache() {
        $identity = new Identity();
        $identity->delCache($this->userId);
    }

    /**
     * 登录到交易市场
     */
    public function login(Request $request) {
        if ($request->isPost()) {
            $password = $request->post('password');
            if (!$password) {
                return json(['code' => 1, 'msg' => '请输入密码']);
            }
            $auth = new Auth();
            if (!$auth->check($password)) {
                return json(['code' => 1, 'msg' => '密码错误']);
            }
            $url = Session::get('prev_url');
            Session::delete('prev_url');
            return json(['code' => 0, 'msg' => '登录成功', 'toUrl' => $url]);
        }
        Session::set('prev_url', !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('market/index'));
        return $this->fetch('login');
    }

    /**
     * 账单
     */
    public function magicloglist(Request $request) {
        $type = $request->get("type", 1);
        if ($request->isAjax()) {
            $page = $request->get('page', 1);
            $model = new UserMagicLog();
            $list = $model->magicloglist($type, $this->userId, $page);

            return json(['code' => 0, 'msg' => 'success', 'data' => $list]);
        }
        return $this->fetch("magicloglist", [
                    'type' => $type
        ]);
    }

    /**
     * 退出登录
     */
    public function logout() {
        $service = new Identity();
        $service->logout();

        $this->redirect('publics/index');
    }

    /**
     * 推广
     */
    public function spread() {
        //获取当前用户的推广码
        //$path = url('qrcode');
        $code = User::where('id', $this->userId)->value('mobile');

        $fileName = Env::get('app_path') . '../public/code/qrcode_' . $code . '.png';
//        if (!file_exists($fileName)) {
            $path = $this->qrcode($code);

            ob_clean();
            $editor = Grafika::createEditor(['Gd']);

            $background = Env::get('app_path') . '../public/static/img/zhaomubg.png';

            $editor->open($image1, $background);
//            $editor->text($image1, $code, 20, 220, 575, new Color('#ffffff'), '', 0);
            $editor->open($image2, $path);
            $editor->blend($image1, $image2, 'normal', 0.9, 'top-left',260,570);
            $editor->save($image1, Env::get('app_path') . '../public/code/qrcode_' . $code . '.png');
//        }

        return $this->fetch('spread', [
                    'path' => '/code/qrcode_' . $code . '.png'
        ]);
    }

    protected function qrcode($code) {
        //$code = UserInviteCode::where('user_id', $this->userId)->value('invite_code');
        $path = Env::get('app_path') . '../public/code/' . $code . '.png';

//        if (!file_exists($path)) {
            ob_clean();
            $url = url('publics/register', ['code' => $code], 'html', true);
            $qrCode = new \Endroid\QrCode\QrCode();

            $qrCode->setText($url);
            $qrCode->setSize(360);
            $qrCode->setWriterByName('png');
            $qrCode->setMargin(10);
            $qrCode->setEncoding('UTF-8');
            $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH);
            $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
            $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 100]);
            //$qrCode->setLabel('Scan the code', 16, __DIR__.'/../assets/fonts/noto_sans.otf', LabelAlignment::CENTER);
//            $qrCode->setLogoPath(Env::get('app_path') . '../public/static/img/logo5.png');
//            $qrCode->setLogoWidth(60);
            $qrCode->setValidateResult(false);

            header('Content-Type: ' . $qrCode->getContentType());
            $content = $qrCode->writeString();

            $path = Env::get('app_path') . '../public/code/' . $code . '.png';

            file_put_contents($path, $content);
//        }

        return $path;
    }

    public function safepassword(Request $request) {
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

}
