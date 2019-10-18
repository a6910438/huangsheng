<?php
namespace app\index\validate;

use app\common\entity\UserInviteCode;
use app\index\model\SendCode;
use app\index\model\SendMail;
use think\Validate;

class MakeMoney extends Validate
{
    protected $rule = [
        'nums' => 'require',
        'money_address' => 'require',
        'active_id' => 'require',
        'pic' => 'require',
    ];

    protected $message = [
        'nums.require' => 'Quantity cannot be empty',
        'money_address.require' => 'The wallet address cannot be empty',
        'active_id.require' => 'Order ID cannot be empty',
        'pic.require' => 'Payment vouchers cannot be empty',
    ];
}