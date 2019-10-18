<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class AddLineConfig extends Validate
{

    protected $rule = [
        'price' => 'require|number',
        'num' => 'require|number',
        'status' => 'require|between:1,2',
    ];

    protected $message = [
        'price.require' => '请输入金额',
        'price.number' => '请输入金额对应数字',
        'num.require' => '请输入返还排单币数量',
        'num.number' => '请输入返还排单币对应数字',
        'status.require' => '请选择状态',
        'status.between' => '请选择对应配置状态',
    ];


}