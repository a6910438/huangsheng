<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class ChangePassword extends Validate
{

    protected $rule = [
        'old_password' => 'require',
        // 'password' => '',
        // 'password'=>'min:6'
        'password_confirmation'=>'require|confirm:password',
        'password'=>'require|min:6',

    ];

    protected $message = [
        'old_password.require' => '请输入原密码',
        'password.require' => '请输入新密码',
        'password_confirmation.require' => '请输入确认密码',
        'password.min' => '密码至少6位数',
        'password_confirmation.confirm'=>'两次密码不一致'
    ];


}
