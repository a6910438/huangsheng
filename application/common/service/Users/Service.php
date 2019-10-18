<?php
namespace app\common\service\Users;

use app\common\entity\User;
use app\common\entity\SafeAnswer;
use think\Request;
use think\Session;

class Service
{
    /**
     * 加密前缀
     */
    const PREFIX_KEY = "eco_member";

    /**
     * 加密函数
     */
    public function getPassword($password)
    {
        return md5(md5(self::PREFIX_KEY . $password));
    }

    /**
     * 验证密码
     */
    public function checkPassword($password, User $entity)
    {
        return $this->getPassword($password) === $entity->getPassword();
    }

    /**
     * 验证交易密码
     * @param $password
     * @param User $entity
     * @return bool
     */
    public function checkPayPassword($password, User $entity)
    {
        return $this->getPassword($password) === $entity->getPayPassword();
    }

    public function checkSafePassword($password, User $entity)
    {
        return $this->getPassword($password) === $entity->getSafePassword();
    }
    /**
     * 验证密保问题
     */
    public function checkSafeQuestion($qid, $answer,$uid)
    {
        $entity = new SafeAnswer();
        return $answer == $entity->where('qid',$qid)->where('uid',$uid)->value('content');

    }

    public function addUser($data)
    {

        $entity = new User();
        $request = Request::instance();
        $entity->nick_name = $data['nick_name'];
        $entity->mobile = $data['mobile'];
        $entity->lv = $data['lv'];

        $entity->pid = $data['pid'];
        $entity->password = $this->getPassword($data['password']);
        $entity->trad_password = $this->getPassword($data['trad_password']);
        $entity->remake = $data['remake'];
        $entity->register_ip = $data['ip'] ?? $request->ip();
        $entity->status = User::STATUS_DEFAULT;
        $entity->is_certification = User::AUTH_ERROR;
        $entity->province = empty($data['province'])?'':$data['province'];
        $entity->city = empty($data['city'])?'':$data['city'];
        $entity->service = empty($data['service'])?'':$data['service'];



        if ($entity->save()) {

            if($data['pid']){
                \app\common\entity\User::where('id', $data['pid'])->setInc('invite_count');
            }
            return $entity->getId();
        }

        return false;
    }

    public function updateUser(User $user, $data)
    {


        $user->nick_name = $data['nick_name'];

        if($data['lv'] <= 0 ){
            $data['lv'] = 0;
        }
        $user->lv = $data['lv'];

        if ($data['password']) {
            $user->password = $this->getPassword($data['password']);
        }

        if ($data['trad_password']) {
            $user->trad_password = $this->getPassword($data['trad_password']);
        }

		$user->update_time = time();
        return $user->save();
    }

    /**
     * 检查用户名是否已存在
     */
    public function checkUser($name, $id = 0)
    {
        $entity = user::where('nick_name', $name);
        if ($id) {
            $entity->where('id', '<>', $id);
        }
        return $entity->find() ? true : false;
    }
    /**
     * 检查上级是否已存在
     */
    public function checkHigher($name)
    {

        $entity = user::where('mobile', $name)->field('id')->find();
        if(!empty($entity['id'])){
            return $entity['id'];
        }else{
            return 0;
        }
    }
    /**
     * 检查交易地址是否已存在
     */
    public function checkAddress($name, $id = 0)
    {
        $entity = user::where('trade_address', $name);
        if ($id) {
            $entity->where('id', '<>', $id);
        }
        return $entity->find() ? true : false;
    }

    /**
     * 银行卡号 微信号 支付宝账号 唯一
     */
    public function checkMsg($type, $account, $id = '')
    {
        return \app\common\entity\User::where("$type", $account)->where('id', '<>', $id)->find();
    }

    public function checkMobile($value,$id = 0)
    {

        $entity = user::where('mobile', $value);
        if ($id) {
            $entity->where('id', '<>', $id);
        }
        return $entity->find() ? true : false;

    }

    /**
     * 用户是否激活
     * @param int $id
     * @return bool
     */
    public function checkUserStatus( $id = 0)
    {

        if(empty($id)){
            return false;
        }

        $entity = user::where('id|mobile', $id);
        $entity->where('status', '1');
        $entity->field('id');
        return $entity->find() ? true : false;
    }

}