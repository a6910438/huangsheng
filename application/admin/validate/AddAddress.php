<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class AddAddress extends Validate
{

    protected $rule = [
        'address' => 'require',
        'type' => 'require',
    ];

    protected $message = [
        'address.require' => '请输入钱包地址',
        'type.require' => '请输入钱包类型',
    ];


}