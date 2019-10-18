<?php

namespace app\common\entity;


use think\Db;
use think\Model;
use traits\model\SoftDelete;

class User extends Model
{
    use SoftDelete;   //开启了软删除
    const STATUS_DEFAULT = 0;    //默认个
    const STATUS_FORBIDDED = -1; //禁止
    const STATUS_ACTIVATION = 1; //激活
    const AUTH_SUCCESS = 1;      //认证
    const AUTH_ERROR = -1;       //未认证

    protected $createTime = 'register_time';
    protected $login_time = 'login_time';
    protected $updateTime = 'active_time';
    protected $deleteTime = 'delete_time';
    protected $categoryModel;
    /**
     * @var string 对应的数据表名
     */
    protected $table = 'user';
    protected $autoWriteTimestamp = true;

    //获取状态
    public function getStatus($status)
    {
        switch ($status) {
            case -1:
                return '禁用';
            case 0:
                return '普通';
            case 1:
                return '激活';
            default:
                return '';
        }
    }

    //获取状态
    public function getIsDelete($isdelete)
    {
        switch ($isdelete) {
            case 0:
                return '否';
            case 1:
                return '是';
            default:
                return '';
        }
    }

    //获取管控状态
    public function getManageStatus($status)
    {
        switch ($status) {
            case 1:
                return '未管控';
            case 2:
                return '管控中';
            default:
                return '';
        }
    }
    //获取所有管控状态
    public function getAllManageStatus()
    {
        return [
            1 => '未管控',
            2 => '管控中',
        ];
    }

    public function getId()
    {
        return $this->id;
    }

    #获取用户名
    public function getUserName()
    {
        return $this->nick_name;
    }
    #获取用户名
    public function getNickName($id)
    {
        return $this->where('id',$id)->value('nick_name');
    }

    public function getCategoryModel()
    {
        if (!$this->categoryModel) {
            $this->categoryModel = new Category();
        }
        return $this->categoryModel;
    }

    /**
     * 获取密码
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * 获取交易密码
     * @return mixed
     */
    public function getPayPassword()
    {
        return $this->trad_password;
    }

    public function getSafePassword()
    {
        return $this->trad_password;
    }

    /**
     * 获取禁用时间
     */
    public function getForbiddenTime()
    {
        return $this->forbidden_ntime ? date('Y-m-d H:i:s', $this->forbidden_ntime) : 0;
    }

    /**
     * 判断是否被禁用
     */
    public function isForbiddened()
    {
        return $this->forbidden_ntime ? true : false;
    }

    /**
     * 获取注册时间
     */
    public function getRegisterTime()
    {
        return $this->register_time;
    }
    /**
     * 获取用户资料
     */
    public function getUserInfo($uid){
        return $this->where('id',$uid)->find();
    }
    /**
     * 获取反馈内容
     */
    public function feedback($uid){
        $manage_msg = $this->where('id',$uid)->value('manage_msg');

        $forms = "<form class='form-horizontal' method='post' onsubmit='return false' role='form'>";
        $name = "manageMsg?id=$uid";
        $str = "<input type='text'  name='reply' id='reply' value='".$manage_msg." ' class='form-control' placeholder='请输入反馈内容'>";
        $star = "<button class='btn btn-color'  onclick='main.ajaxPosts(this)' data-url=";
        $end = " >确定</button>";
        $forme = "</form>";
        return $forms.$str.$star.$name.$end.$forme;
    }
    /**
     * 获取最后登录时间
     */
    public function getLoginTime()
    {
        return $this->login_time;
    }

    public function getLevel()
    {
        switch ($this->level) {
            case 1:
                return Config::getValue('user_level_1');
            case 2:
                return Config::getValue('user_level_2');
            case 3:
                return Config::getValue('user_level_3');
            case 4:
                return Config::getValue('user_level_4');
            case 5:
                return Config::getValue('user_level_5');
        }
    }

    public static function checkMobile($mobile)
    {

        return self::where('mobile', $mobile)->find();
    }
    public static function checkId($id)
    {

        return self::where('id', $id)->find();
    }
    public static function checkName($name)
    {
        return self::where('nick_name', $name)->find();
    }

    //获取直推人数
    public function getChildTotal()
    {
        return self::where('pid', $this->getId())->count();
    }

    public function getTeamInfo()
    {
        $categoryModel = $this->getCategoryModel();
        $fids = $categoryModel->getSubChild($this->getId());
        if (isset($fids[0])) {
            unset($fids[0]);
        }
        if ($fids) {
            $totalrate = User::where('id', 'in', $fids)->sum('product_rate');
            return [
                'total' => count($fids),
                'rate' => sprintf('%.5f', $totalrate)
            ];
        } else {
            return ['total' => 0, 'rate' => 0];
        }
    }


    //获取购买矿机的下级用户数量
    public function getSubBuyCount()
    {
        $categoryModel = $this->getCategoryModel();
        $fids = $categoryModel->getSubChild($this->getId());
        if (isset($fids[0])) {
            unset($fids[0]);
        }
        $total = 0;
        if ($fids) {
            $total = UserProduct::where('user_id', 'in', $fids)->where('types', UserProduct::TYPE_BUY)->count();
        }
        return $total;
    }

    //获取团队的人数
    public function getTeam($memberId, $users)
    {

        $Teams = array(); //最终结果
        $mids = array($memberId); //第一次执行时候的用户id


        do {
            $othermids = array();
            $state = false;
            foreach ($mids as $valueone) {

                foreach ($users as $key => $valuetwo) {

                    if ($valuetwo['pid'] == $valueone) {
                        $Teams[] = $valuetwo['id']; //找到我的下级立即添加到最终结果中
                        $othermids[] = $valuetwo['id']; //将我的下级id保存起来用来下轮循环他的下级
                        //array_splice($users, $key, 1);//从所有会员中删除他
                        $state = true;
                    }
                }
            }
            $mids = $othermids; //foreach中找到的我的下级集合,用来下次循环
        } while ($state == true);

        return $Teams;
    }





    public function getChilds($memberId)
    {
        $childs = self::where('pid', $memberId)
            ->field('*')
            ->select();
        return $childs;
    }

    /**
     * 获取用户上级信息
     */
    public function getParentInfo()
    {
        if ($this->pid == 0) {
            return '';
        }

        $data = self::where('id', $this->pid)->value('nick_name');

        return $data ? $data : '';
    }

    /**
     * 获取邀请码
     * @return mixed|string
     */
    public function getInviteCode($uid = 0)
    {
        if(empty($uid)){
            $uid = $this->id;
        }
        $data = Db::table('user_invite_code')->where('user_id', $uid)->value('invite_code');

        return $data ? $data : '异常';
    }

    /**
     * 随机生成提现单号
     */
    public function setWithdrawNumber($userId)
    {
        return 'WS' . date('Ymd') . $userId . date('His') . rand(1000, 9999);
    }

    #获取下级
    public function getChildsInfo($uid, $num = 0)
    {
        static $childs = [];
        static $level = 0;
        $my = User::alias('u')
            ->join('user_invite_code uic','uic.user_id = u.id')
            ->where('u.id', $uid)
            ->field('u.id,u.nick_name as name,u.lv level,pid as pId,uic.invite_code')
            ->find();
        if (isset($num)) {
            if ($level == $num) {

                return $my;
            }
        }
        $child = User::alias('u')
            ->join('user_invite_code uic','uic.user_id = u.id')
            ->where('pid', $uid)
            ->field('u.id,u.nick_name as name,u.lv level,pid as pId,uic.invite_code')
            ->select();
        if ($child) {
            if($my['pId'] == 0){
                if($my){
                    $my['name'] =  '昵称：'. $my['name'] . '|ID:'.$my['invite_code'];
                }
                $childs[] = $my;
            }

            foreach ($child as $v) {
                $childs[] = $v;
                $v['name'] =  '昵称：'. $v['name'] . '|ID:'.$v['invite_code'];
                $this->getChildsInfo($v['id'], $num);
            }
        }
        return $childs;
    }
    #获取下级
    public function getAllChildsInfo($uid)
    {
        static $childs = [];
        static $level = 0;
        $my = User::where('id', $uid)->field('id,nick_name,level,pid')->find();
        if (isset($num)) {
            if ($level == $num) {
                return $my;
            }
        }
        $child = User::where('pid', $uid)->field('id,nick_name,level,pid')->select();
        if ($child) {
            if($my['pid'] == 0){
                $childs[] = $my;
            }
            foreach ($child as $v) {
                $childs[] = $v;
                $this->getAllChildsInfo($v['id']);
            }
        }
        return $childs;
    }
	
	#获取团队所有用户id
    public function getAllChildsID($uid)
    {
        static $childs = [];
        static $level = 0;
        $my = User::where('id', $uid)->field('id,pid')->find();
        if (isset($num)) {
            if ($level == $num) {
                return $my;
            }
        }
        $child = User::where('pid', $uid)->field('id,pid')->select();
        if ($child) {
            if($my['pid'] == 0){
                $childs[] = $my['id'];
            }
            foreach ($child as $v) {
                $childs[] = $v['id'];
                $this->getAllChildsID($v['id']);
            }
        }
        return array_unique($childs);
    }

    #获取团队长ID
    public function getParents($uid ,&$parent = array() , &$level=0)
    {
        // static $parent = [];
        // static $level = 0;
        $userInfo = User::where('id', $uid)->field('id,register_time,nick_name,level,pid,status')->find();

        $pid = $userInfo['pid'];
        $level++;

        if ($pid == '0') {
            return $userInfo['id'];
        }
        return $this->getParents($pid ,$parent , $level);
//        return $pid;

    }
    #获取上级ID
    public function getParentsId($uid ,$num,&$parent = array() , &$level=0)
    {
        // static $parent = [];
        // static $level = 0;
        $userInfo = User::where('id', $uid)->field('id,register_time,nick_name,level,pid,status')->find();

        $pid = $userInfo['pid'];

        $level++;

        if ($level >= $num) {

            return $userInfo['pid'];
        }
        return $this->getParentsId($pid ,$num ,$parent , $level);


    }
    /**
     * 获取下级有效人数
     * $child   下级数组
     * $num   查到第几级
     */
    public function getChildsNum($child,$num,&$level=0,&$count=1)
    {
        $childs = [];
        foreach ($child as $key => $val){

            if($val['status'] == 1){

                $childs[] = $val;
            }

        }
        $count1 = count($childs);
        $count = $count + $count1;
        $level++;
        if($level >= $num){
            return $count;
        }
        foreach ($child as $v) {

            $cc = User::field('id,status')->where('pid', $v['id'])->select();

            $this->getChildsNum($cc,$num,$level,$count);
        }
        return $count;

    }
    /**
     * 获取无限层团队人数
     * $child   下级数组
     * $num   查到第几级
     */
    public function getTeamNum($child,&$count=0)
    {

        $count1 = count($child);
        $count = $count + $count1;

        foreach ($child as $v) {

            $cc = User::field('id,status')->where('pid', $v['id'])->select();

            $this->getTeamNum($cc,$count);
        }
        return $count;
    }

    /**
     * @param $child
     * @param int $count
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_is_Team($child,$buser)
    {



        foreach ($child as $v) {

            if($v['id'] == $buser){
                return true;
            }
            $cc = User::field('id,status')->where('pid', $v['id'])->select();

            $is_t = $this->get_is_Team($cc,$buser);
            if($is_t){
                return true;
            }

        }

        return false;
    }


    /**
     * 获取团队领取酒数
     * @param $child
     * @param int $count
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
//    public function getTeamAdoptFishNum($child,&$count=0)
//    {
//        foreach ($child as $v) {
//            $cc = User::field('id,status')->where('pid', $v['id'])->select();
//            $count1 = $count;
//            $count = $this->getUserAdoptFishNum($v['id']);
//            $count = $count + $count1;
//            $this->getTeamNum($cc,$count);
//        }
//        return $count;
//    }


    public function getTeamAdoptFishNum($id,&$num)
    {
        $list = DB::name('user')
            ->where('pid', $id)
            ->where('status',1)
            ->where('is_active',1)
            ->field('id')
            ->select();
        foreach ($list as $value) {
            $newid = $value['id'];
//            $arr[] = $newid;
            $count = $this->getUserAdoptFishNum($value['id']);

            $num = $num + $count;

            $this->getTeamAdoptFishNum($newid, $num);

        }
    }

    /**
     * 用户领取酒数
     * @param $uid
     * @return int|string
     * @throws \think\Exception
     */
    public function getUserAdoptFishNum($uid){
        return  Db::table('appointment_user')
            ->where('uid',$uid)
            ->where('new_fid','>',0)
            ->where('status',4)
            ->count('id');
    }

    /**
     * 预约次数
     * @param $uid
     * @return int|string
     * @throws \think\Exception
     */
    public function getUserPreFishNum($uid){
        return  Db::table('appointment_user')
            ->where('uid',$uid)
            ->count('id');
    }



    /**
     * 团队GTC消耗
     * @param $child
     * @param int $count
     * @return float|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
//    public function genTeamReduceWallet($child,$count=0){
//        foreach ($child as $v) {
//            $cc = User::field('id,status')->where('pid', $v['id'])->select();
//            $count1 = $count;
//            $count = $this->get_reducep($v['id']);
//            $count = $count + $count1;
//            $this->genTeamReduceWallet($cc,$count);
//        }
//        return $count;
//    }



    public function genTeamReduceWallet($id,&$num,$allID = []){
        $list = DB::name('user')
            ->where('pid',$id)
            ->where('status',1)
            ->where('is_active',1)
            ->field('id')
            ->select();
        foreach ($list as $value) {
            $newid = $value['id'];
//            $arr[] = $newid;
            $count = $this->get_reducep($value['id'],$allID);

            $num = $num + $count;

            $this->genTeamReduceWallet($newid,$num,$allID);
        }
    }
    /**
     * GTC消耗
     * @param $uid
     * @return float|int
     */
    public  function get_reducep($uid,$allID=[]){
		
		$my_bait = \think\Db::table('my_wallet_log')
            ->where('uid',$uid)
            ->where('types','in','2,3,6,4')
            ->sum('number');
        if(!$my_bait){
                $my_bait = 0;
            }
		$my_bait2 = \think\Db::table('my_wallet_log')
            ->where('uid',$uid)
            ->where('types','in','1,5')
			->where(['from_id'=>['not in',$allID]])
			->where('number','<',0)
            ->sum('number');
        if(!$my_bait2){
                $my_bait = 0;
            }
        $my_bait = abs($my_bait)+abs($my_bait2);
		
        return  $my_bait;/*\think\Db::table('my_wallet_log')
            ->where('uid',$uid)
            ->where('types','in','1,2,3')
            ->where('number','<',0)
            ->sum('number');*/
    }


    /**
     * 团队GTC充值
     * @param $child
     * @param int $count
     * @return float|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
//    public function genTeamAddWallet($child,$count=0){
//        foreach ($child as $v) {
//            $cc = User::field('id,status')->where('pid', $v['id'])->select();
//            $count1 = $count;
//            $count = $this->get_addp($v['id']);
//            $count = $count + $count1;
//            $this->genTeamAddWallet($cc,$count);
//        }
//        return $count;
//    }
    public function genTeamAddWallet($id,&$num,$allID=[]){
		//$allID = array(0=>$id);
		//$this->getTeamUserIdn($id,$allID);
        $list = DB::name('user')
            ->where('pid',$id)
            ->where('status',1)
            ->where('is_active',1)
            ->field('id')
            ->select();
        foreach ($list as $value) {
            $newid = $value['id'];
//            $arr[] = $newid;
            $count = $this->get_addp($value['id'],$allID);

            $num = $num + $count;

            $this->genTeamAddWallet($newid,$num,$allID);
        }
    }




    /**
     * GTC充值 团队
     * @param $uid
     * @return float|int
     */
    public function get_addp($uid,$allID=[]){
		
       return  \think\Db::table('my_wallet_log')
           ->where('uid',$uid)
           ->where('types','in','1,5')
		   ->where(['from_id'=>['not in',$allID]])
           ->where('number','>',0)
           ->sum('number');
        /*return  \think\Db::table('my_wallet')
            ->where('uid',$uid)
            ->sum('now');*/
    }
	
	/**
     * GTC充值 个人
     * @param $uid
     * @return float|int
     */
    public function get_addp_one($uid){
		
       return  \think\Db::table('my_wallet_log')
           ->where('uid',$uid)
           ->where('types','in','1,5')
           ->where('number','>',0)
           ->sum('number');
        /*return  \think\Db::table('my_wallet')
            ->where('uid',$uid)
            ->sum('now');*/
    }

    /**
     * 消耗GTC 团队
     * @param $uid
     * @return float|int
     */
    public function get_consumep($uid){
        $my_bait = \think\Db::table('my_wallet_log')
            ->where('uid',$uid)
            ->where('types','in','2,3,6,4')
            ->sum('number')??0;
		$my_bait2 = \think\Db::table('my_wallet_log')
            ->where('uid',$uid)
            ->where('types','in','1')
			->where('number','<',0)
            ->sum('number')??0;
        $my_bait = abs($my_bait)+abs($my_bait2);
        return $my_bait;
    }
	
	/**
     * 消耗GTC 个人
     * @param $uid
     * @return float|int
     */
    public function get_consumep2($uid){
        $my_bait = \think\Db::table('my_wallet_log')
            ->where('uid',$uid)
            ->where('types','in','2,3,6,4')
            ->sum('number')??0;
		$my_bait2 = \think\Db::table('my_wallet_log')
            ->where('uid',$uid)
            ->where('types','in','1,5')
			->where('number','<',0)
            ->sum('number')??0;
        $my_bait = abs($my_bait)+abs($my_bait2);
        return $my_bait;
    }

    /**
     * 团队
     * @param $child
     * @param $
     * @param int $count
     * @return float|int
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
//    public function genTeamNowWallet($child,$count=0){
//        foreach ($child as $v) {
//            $cc = User::field('id,status')->where('pid', $v['id'])->select();
//            $count1 = $count;
//            $count = $this->getNowWallet($v['id']);
//            $count = $count + $count1;
//            $this->genTeamNowWallet($cc,$count);
//        }
//        return $count;
//    }

    public function genTeamNowWallet($id,&$num){
        $list = DB::name('user')
            ->where('pid',$id)
            ->where('status',1)
            ->where('is_active',1)
            ->field('id')
            ->select();
        foreach ($list as $value) {
            $newid = $value['id'];
//            $arr[] = $newid;
            $count = $this->getNowWallet($value['id']);

            $num = $num + $count;

            $this->genTeamNowWallet($newid,$num);
        }
    }



    /**
     * 获取GTC数
     * @param $id
     * @return float|int
     */
    public function getNowWallet($id){
        /*return   Db::table('user')->alias('u')
            ->join('my_wallet mw','mw.uid = u.id')
            ->where('u.id',$id)
            ->where('mw.is_balance_extension',0)
            ->value('mw.now');*/
		return Db::table('my_wallet')
				->where('uid',$id)
				->where('is_balance_extension',0)
				->value('now');
    }

    /**
     * 直推人数
     * @param $uid
     * @return int|string
     * @throws \think\Exception
     */
    public function getZT($uid){

        return Db::table('user')->where('pid',$uid)->count('id'); //直推人数
    }


    public function get_child($id){
        return  $child = User::field('id,status')->where('pid',$id)->select();
    }
    /**
     * 团队人数
     * @param $child
     * @param int $count
     * @return int|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public function getTeamZTNum($id,&$num){

        $list = DB::name('user')
            ->where('pid',$id)
//            ->where('status',1)
            ->where('is_active',1)

            ->field('id')
            ->select();
        foreach ($list as $value) {
            $newid = $value['id'];
//            $arr[] = $newid;
            $num +=1;
            $this->getTeamZTNum($newid,$num);
        }
    }







    /**
     * 团队预约酒数
     * @param $uid
     * @return int|string
     * @throws \think\Exception
     */


    public function getTeamPreNum($id,&$num){
        $list = DB::name('user')
            ->where('pid',$id)
            ->where('status',1)
            ->where('is_active',1)
            ->field('id')
            ->select();
        foreach ($list as $value) {
            $newid = $value['id'];
//            $arr[] = $newid;
            $count = $this->getUserPreFishNum($value['id']);

            $num = $num + $count;

            $this->getTeamPreNum($newid,$num);
        }
    }
//    public function getTeamPreNum($child,$count=0)
//    {
//        foreach ($child as $v) {
//            $cc = User::field('id,status')->where('pid', $v['id'])->select();
//            $count1 = $count;
//            $count = $this->getUserPreFishNum($v['id']);
//            $count = $count + $count1;
//            $this->getTeamPreNum($cc,$count);
//        }
//        return $count;
//    }







    /**
     * 获取下级无限层存GTC
     */
//    public function getTeamStore($child,&$money=0)
//    {
//        foreach ($child as $v) {
//            $money = Db::table('my_wallet_log')->where('types',1)->where('uid',$v['id'])->where('is_delete',0)->sum('number') + $money;
//            $cc = User::field('id,status')->where('pid', $v['id'])->where('status', 1)->select();
//
//            $this->getTeamStore($cc,$money);
//        }
//        return $money;
//    }


    public function getTeamStore($id,&$num){
        $list = DB::name('user')
            ->where('pid',$id)
            ->where('status',1)
            ->where('is_active',1)
            ->field('id')
            ->select();
        foreach ($list as $v) {
            $newid = $v['id'];
//            $arr[] = $newid;
            $count =  Db::table('my_wallet_log')->where('types',1)->where('uid',$v['id'])->where('is_delete',0)->sum('number') ;

            $num = $num + $count;
            $this->getTeamStore($newid,$num);
        }
    }


    /**
     * 获取下级无限冻结GTC
     */
//    public function getTeamFrozen($child,&$money=0)
//    {
//        foreach ($child as $v) {
//            $is_user = MyWallet::where('uid',$v['id'])->where('is_balance_extension',1)->sum('now') ;
//            if($is_user){
//                $money =  $is_user; + $money;
//            }
//            $cc = User::field('id,status')->where('pid', $v['id'])->select();
//
//            $this->getTeamFrozen($cc,$money);
//        }
//        return $money;
//    }
    public function getTeamFrozen($id,&$num){
        $list = DB::name('user')
            ->where('pid',$id)
            ->where('status',1)
            ->where('is_active',1)
            ->field('id')
            ->select();
        foreach ($list as $v) {
            $newid = $v['id'];
//            $arr[] = $newid;
            $count =   MyWallet::where('uid',$v['id'])->where('is_balance_extension',1)->sum('now') ;

            $num = $num + $count;
            $this->getTeamFrozen($newid,$num);
        }
    }
	
	    public function getTeamUserIdn($id,&$arr){
        $list = DB::name('user')
            ->where('pid',$id)
            ->where('status',1)
            ->where('is_active',1)
            ->field('id')
            ->select();
        foreach ($list as $v) {
            $newid = $v['id'];
            $arr[] = $newid;
         
            $this->getTeamUserIdn($newid,$arr);
        }
    }

    /**
     * 统计手机归属地省
     */
    public static function getAllProvince(){
        $cate=User::distinct(true)->field('province')->select();
        return $cate;
        ;
    }

    /**
     * 获取禁用开始时间
     */
    public function getForbiddensTime()
    {
        return $this->forbidden_stime ? date('Y-m-d H:i:s', $this->forbidden_stime) : 0;
    }

    /**
     * 获取结束时间
     */
    public function getForbiddennTime()
    {
        return $this->forbidden_ntime ? date('Y-m-d H:i:s', $this->forbidden_ntime) : 0;
    }


}
