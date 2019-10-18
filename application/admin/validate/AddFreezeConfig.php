<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class AddFreezeConfig extends Validate
{

    protected $rule = [
        'type' => 'require|number',
        'value' => 'require|number',
    ];

    protected $message = [
        'type.require' => '请选择配置',
        'type.number' => '请选择对应配置名称',
        'value.require' => '请输入值',
        'value.number' => '请输入值对应数字',
//        'status.require' => '请选择配置状态',
//        'status.between' => '请选择对应配置状态',
    ];


}