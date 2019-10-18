<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class UserEditForm extends Validate
{

    protected $rule = [
        'nick_name' => 'require',
        'password' => 'min:6',
        're_password' => 'min:6|confirm:password',
        'trad_password' => 'min:6',
        're_trad_password' => 'confirm:trad_password',

    ];

    protected $message = [
        'nick_name.require' => '请输入用户昵称',
        'password.min' => '密码至少6位数',
        're_password.confirm' => '两次登录密码不一致',
        'trad_password.min' => '交易密码至少6位数',
        're_trad_password.confirm' => '两次交易密码不一致',
    ];


}