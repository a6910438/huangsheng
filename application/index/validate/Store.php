<?php
namespace app\index\validate;

use app\common\entity\User;
use app\common\entity\UserInviteCode;
use app\index\model\SendCode;
use app\index\model\SendMail;
use think\Validate;

class Store extends Validate
{
    protected $rule = [
        'types' => 'require|between:1,2',
        'num' => 'require',
        'trad_password' => 'require',

    ];

    protected $message = [
        'types.require' => 'Type cannot be empty',
        'types.between' => 'Please select the corresponding type.',
        'num.require' => 'The amount should not be empty',
        'trad_password.require' => 'Trading password cannot be empty',

    ];

}