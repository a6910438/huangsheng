<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class AddSearchConfig extends Validate
{

    protected $rule = [
        'num' => 'require|number',
        'types' => 'require|between:1,2',
    ];

    protected $message = [
        'num.require' => '请输入次数',
        'num.number' => '请输入次数对应数字',
        'types.require' => '请选择配置类型',
        'types.between' => '请选择对应配置类型',
    ];


}