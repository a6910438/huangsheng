<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class AddMoneyRate extends Validate
{

    protected $rule = [
        'num' => 'require|number',
        'types' => 'require|between:1,3',
        'status' => 'require|between:1,2',
    ];

    protected $message = [
        'num.require' => '请输入最小基数',
        'num.number' => '请输入金额对应数字',
        'types.require' => '请选择配置类型',
        'types.number' => '请选择对应配置状态',
        'status.require' => '请选择配置状态',
        'status.between' => '请选择对应配置状态',
    ];


}