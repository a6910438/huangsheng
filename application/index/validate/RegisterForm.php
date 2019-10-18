<?php
namespace app\index\validate;

use app\common\entity\UserInviteCode;
use app\index\model\SendCode;
use app\index\model\SendMail;
use think\Validate;

class RegisterForm extends Validate
{
    protected $rule = [
        'invite_code' => 'checkInvite',
        'mobile' => 'require',
        'nick_name' => 'require',
        'phone_code' => 'require',
        'password' => 'require|min:6',
        're_password' => 'require|confirm:password',
//        'trad_password' => 'require|min:6',
//        're_trad_password' => 'require|min:6',
    ];

    protected $message = [
        'invite_code.require' => '邀请码不能为空',
        'password.require' => '登陆密码不能为空',
        'password.min' => '登陆密码不能为空',
        're_password.confirm' => '两个密码不同',
//        'trad_password.require' => '交易密码不能为空',
//        'trad_password.require' => '交易密码不能为空',
//        'trad_password.min' => '交易密码至少6位',
//        're_trad_password.confirm' => '交易密码不一致',
        'mobile.require' => '手机号不能为空',
        'nick_name.require' => '用户名不能为空',
        'phone_code.require' => '验证码不能为空',

    ];

    public function checkInvite($value, $rule, $data = [])
    {
        //判断邀请码是否存在
        if (!UserInviteCode::getUserIdByCode($value)&&$value) {
            return '邀请代码不存在 ';
        }
        return true;
    }

    public function checkMobile($value, $rule, $data = [])
    {
        if (\app\common\entity\User::checkMobile($value)) {
            return '此账号已被注册，请重新填写';
        }
        return true;
    }

    public function checkCode($value, $email)
    {
        if(empty($value)|| empty($email)){
            return false;
        }
        $sendCode = new SendCode($email, 'register');
        $code = $sendCode->modgetCode($value);
        if($code != $value){
            return false;
        }
        return true;
    }

    public function checkChange($value, $email)
    {
        $sendCode = new SendCode($email, 'change-password');

        $code = $sendCode->modgetCode($value);
        if($code != $value){
            return false;
        }
        return true;
    }

    public function checkPayChange($value, $email)
    {
        $sendCode = new SendCode($email, 'change-pay-password');
        $code = $sendCode->modgetCode($value);
        if($code != $value){
            return false;
        }
        return true;
    }

    public function checkAppeal($value, $email)
    {
        $sendCode = new SendCode($email, 'appeal');
        $code = $sendCode->modgetCode($value);
        if($code != $value){
            return false;
        }
        return true;
    }
}