<?php
namespace app\admin\validate;

use think\Validate;

class ManageEditForm extends Validate
{

    protected $rule = [
        'name' => 'require',
        'password' => 'min:6',
        'password_confirmation' => 'confirm:password',
        'groupIds' => 'require'
    ];

    protected $message = [
        'name.require' => '请输入用户名',
        'password.min' => '密码至少6位数',
        'password_confirmation.confirm' => '两次密码输入不一致',
        'groupIds.required' => '请选择分组'
    ];


}