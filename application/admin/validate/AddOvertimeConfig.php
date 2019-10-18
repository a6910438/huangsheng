<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class AddOvertimeConfig extends Validate
{

    protected $rule = [
        'time' => 'require',
        'status' => 'require',
    ];

    protected $message = [
        'time.require' => '请输入超时时间',
        'status.require' => '请选择配置状态',
    ];


}