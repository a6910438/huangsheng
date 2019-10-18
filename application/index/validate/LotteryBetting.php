<?php
namespace app\index\validate;

use think\Validate;

class LotteryBetting extends Validate
{
    protected $rule = [
        'num' => 'require',
        'money_type' => 'require|between:1,3',
    ];

    protected $message = [
        'num.require' => '下注数量不能为空',
        'money_type.require' => '钱币类型不能为空',
    ];



}