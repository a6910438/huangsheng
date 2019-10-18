<?php
namespace app\admin\validate;

use think\Validate;

class LoginForm extends Validate
{
    protected $rule = [
        'username'  =>  'require',
        'password' =>  'require',
    ];

    protected $message  =   [
        'name.require' => '请输入用户名',
        'password.require'     => '请输入密码'
    ];
}