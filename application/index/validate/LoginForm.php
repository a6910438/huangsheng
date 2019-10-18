<?php
namespace app\index\validate;

use think\Validate;

class LoginForm extends Validate
{
//    protected $rule = [
//        'mobile' => 'require',
//        'password' => 'require',
//        'verify_code|验证码'=>'require|captcha'
//    ];

    protected $message = [
        'mobile.require' => '用户账号不能为空！',
        'password.require' => '密码不能为空'
    ];

}