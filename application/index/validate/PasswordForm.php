<?php
namespace app\index\validate;

use think\Validate;

class PasswordForm extends Validate
{
    protected $rule = [
        'old_pwd' => 'require',
        'new_pwd' => 'require',
        'confirm_pwd' => 'require|confirm:new_pwd'
    ];

    protected $message = [
        'old_pwd.require' => '原密码不能为空',
        'new_pwd.require' => '新密码不能为空',
        'confirm_pwd.require' => '确定密码不能为空',
        'confirm_pwd.confirm' => '两次密码不一样'
    ];

}