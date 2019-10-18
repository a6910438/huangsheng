<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class AddBigConfig extends Validate
{

    protected $rule = [
        'big_price' => 'require',
        'status' => 'require',
    ];

    protected $message = [
        'big_price.require' => '请输入超过金额',
        'status.require' => '请选择配置状态',
    ];


}