<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class ExtensionEditForm extends Validate
{

    protected $rule = [
        'bait_need' => 'require',
        'profit_need' => 'require',
        'push_need' => 'require',
        'umbrella_need' => 'require',
        'extension_profit1' => 'require',
        'extension_profit2' => 'require',
        'extension_profit3' => 'require',
        'team_num' => 'require',
        'extension_num' => 'require',


    ];

    protected $message = [
        'bait_need' => '推广充值GTC区间不能为空',
        'profit_need' => '推广团队收益区间不能为空',
        'push_need' => '推广直推人数区间不能为空',
        'umbrella_need' => '推广伞下人数区间不能为空',
        'extension_profit1' => '推广奖一级返点不能为空',
        'extension_profit2' => '推广奖二级返点不能为空',
        'extension_profit3' => '推广奖三级返点不能为空',
        'team_num' => '团队积分兑换数不能为空',
        'extension_num' => '推广积分兑换数不能为空',

    ];


}