<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class AddDynamicConfig extends Validate
{

    protected $rule = [
        'direct_push' => 'require|number',
        'team_num' => 'require|number',
        'first' => 'require|number',
        'second' => 'require|number',
        'third' => 'require|number',
    ];

    protected $message = [
        'direct_push.require' => '请输入直推人数',
        'direct_push.number' => '请输入直推人数对应数字',
        'team_num.require' => '请输入团队人数',
        'team_num.number' => '请输入团队人数对应数字',
        'first.require' => '请输入第一代提成',
        'first.number' => '请输入第一代提成对应数字',
        'second.require' => '请输入第二代提成',
        'second.number' => '请输入第二代提成对应数字',
        'third.require' => '请输入第三代提成',
        'third.number' => '请输入第三代提成对应数字',
    ];


}