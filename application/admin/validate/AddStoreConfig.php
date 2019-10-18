<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class AddStoreConfig extends Validate
{

    protected $rule = [
        'num' => 'require|number',
        'price' => 'require|number',
        'status' => 'require|between:1,2',
    ];

    protected $message = [
        'num.require' => '请输入超过金额',
        'num.number' => '请输入金额对应数字',
        'price.require' => '请输入消耗排单表',
        'price.number' => '请输入消耗排单表对应数字',
        'status.require' => '请选择配置状态',
        'status.between' => '请选择对应配置状态',
    ];


}