<?php
namespace app\index\validate;

use app\common\entity\UserInviteCode;
use app\index\model\SendCode;
use app\index\model\SendMail;
use think\Validate;

class Withdraw extends Validate
{
    protected $rule = [
        'types' => 'require',
        'num' => 'require',
        'trad_password' => 'require',
    ];

    protected $message = [
        'types.require' => 'Type cannot be empty',
        'num.require' => 'The amount should not be empty',
        'trad_password.require' => 'Trading password cannot be empty',

    ];
}