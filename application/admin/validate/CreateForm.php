<?php
namespace app\admin\validate;

use think\Validate;

class CreateForm extends Validate
{
    protected $rule = [
        'username'  =>  'require',
        'password' =>  'require',
        're_password' => 'require|confirm:password',
    ];

    protected $message  =   [
        'name.require' => '请输入用户名',
        'password.require'     => '请输入密码',
        're_password.require' => '请再次输入确认密码',
        're_password.confirm' => '两次登录密码不一致',
    ];
}