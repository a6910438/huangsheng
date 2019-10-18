<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class UserForm extends Validate
{

    protected $rule = [
        'mobile' => 'require|regex:^1[2,3,4,5,7,8,9][0-9]{9}$',
        'higher' => 'require',
        'nick_name' => 'require',
        'password' => 'require|min:6',
        're_password' => 'require|confirm:password',
        'trad_password' => 'require|min:6',
        're_trad_password' => 'require|confirm:trad_password',
    ];

    protected $message = [
        'mobile.require' => '手机号码不能为空',
        'mobile.regex' => '手机号码格式不正确',
        'higher.require' => '请输入推荐人账号',
        'nick_name.require' => '请输入会员账号',
        'password.require' => '请输入密码',
        'password.min' => '密码至少6位数',
        're_password.require' => '请再次输入确认密码',
        're_password.confirm' => '两次登录密码不一致',
        'trad_password.require' => '请输入交易密码',
        'trad_password.min' => '交易密码至少6位数',
        're_trad_password.require' => '请再次输入交易密码',
        're_trad_password.confirm' => '两次交易密码不一致',

    ];


}