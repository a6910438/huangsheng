<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class AddActicveConfig extends Validate
{

    protected $rule = [
        'type' => 'require|between:1,３',
        'price' => 'require|number',
        'active_num' => 'require|number',
        'line_num' => 'require|number',
        'sort' => 'require|between:0,999',
    ];

    protected $message = [
        'type.require' => '请选择账户类型',
        'type.between' => '请选择对应账户类型',
        'price.require' => '请输入激活币价格',
        'price.number' => '请输入激活币价格对应数字',
        'active_num.require' => '请输入激活币数量',
        'active_num.number' => '请输入激活币数量对应数字',
        'line_num.require' => '请输入赠送排单币数量',
        'line_num.number' => '请输入赠送排单币对应数字',
        'sort.require' => '请输入排序',
        'sort.between' => '排序请输入0-999之间的数字',
    ];


}