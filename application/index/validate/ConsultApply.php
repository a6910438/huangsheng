<?php
namespace app\index\validate;

use app\common\entity\UserInviteCode;
use app\index\model\SendCode;
use app\index\model\SendMail;
use think\Validate;

class ConsultApply extends Validate
{
    protected $rule = [
        'content' => 'require',
        'pic' => 'require',
    ];

    protected $message = [
        'content.require' => 'Description cannot be empty',
        'pic.require' => 'Vouchers cannot be empty',
    ];
}