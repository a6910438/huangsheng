<?php
namespace app\index\validate;

use app\common\entity\UserInviteCode;
use app\index\model\SendCode;
use app\index\model\SendMail;
use think\Validate;

class ActiveApply extends Validate
{
    protected $rule = [
        'num' => 'require',
        'pic' => 'require',
        'trade_address' => 'require',

    ];

    protected $message = [
        'num.require' => 'Quantity cannot be empty',
        'pic.require' => 'Vouchers cannot be empty',
        'trade_address.require' => 'The wallet address cannot be empty',

    ];
}