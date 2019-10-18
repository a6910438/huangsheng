<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class ManageForm extends Validate
{

    protected $rule = [
        'name' => 'require',
        'password' => 'require|min:6',
        'password_confirmation' => 'require|confirm:password',
        'groupIds' => 'require'
    ];

    protected $message = [
        'name.require' => '请输入用户名',
        'password.require' => '请输入密码',
        'password.min' => '密码至少6位数',
        'password_confirmation.require' => '请确认密码',
        'password_confirmation.confirm' => '两次密码输入不一致',
        'groupIds.required' => '请选择分组'
    ];


}