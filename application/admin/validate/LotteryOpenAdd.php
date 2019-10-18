<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class LotteryOpenAdd extends Validate
{

    protected $rule = [
        'name' => 'require',
        'first' => 'require',
        'second' => 'require|min:1',
        'third' => 'require',
        'lucky' => 'require',

    ];

    protected $message = [
        'name.require' => '期号不能为空',
        'first.require' => '请输入一等奖号码',
        'second.require' => '请输入二等奖号码',
        'third.require' => '请输入三等奖号码',
        'lucky.require' => '请输入幸运奖号码',

    ];


}