<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class AddFrozenConfig extends Validate
{

    protected $rule = [
        'types' => 'require',
        'values' => 'require',
        'status' => 'require',
    ];

    protected $message = [
        'types.require' => '请选择类型',
        'values.require' => '请输入时间值',
        'status.require' => '请选择配置状态',
    ];


}