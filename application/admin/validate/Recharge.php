<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class Recharge extends Validate
{

    protected $rule = [
    'uid' => 'require',
    'num' => 'require|number',
    'remake' => 'require',

];

    protected $message = [
        'uid.require' => '请输入推荐人ID',
        'num.require' => '请输入充值金额',
        'num.number' => '请输入充值金额数字',
        'remake.require' => '请输入备注',
    ];


}