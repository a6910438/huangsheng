<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class ProductForm extends Validate
{

    protected $rule = [
        'name' => 'require',

        'worth_min' => 'require',
        'worth_max' => 'require',
        'worth_max' => 'require',
        'start_time' => 'require',
        'end_time' => 'require',
        'bait' => 'require',
        'subscribe_bait' => 'require',
        'rob_bait' => 'require',
        'profit' => 'require',
        'contract_time' => 'require',
        'remarks' => 'require',
        'path' => 'require',
        'about_start_time' => 'require',
        'about_end_time' => 'require',

    ];

    protected $message = [
        'name.require' => '酒馆名不能为空',

        'worth_min.require' => '价值区间最小值不能为空',
        'worth_max.require' => '价值区间最大值不能为空',
        'start_time.require' => '领取开始不能为空',
        'end_time.require' => '领取结束不能为空',
        'bait.require' => '装修消耗GTC不能为空',
        'subscribe_bait.require' => '预约消耗GTC不能为空',
        'rob_bait.require' => '即抢消耗GTC不能为空',
        'profit.require' => '收益不能为空',
        'contract_time.require' => '收益周期时间不能为空',
        'remarks.require' => '备注说明不能为空',
        'path.require' => '图片不能为空',
        'about_start_time.require' => '预约开始不能为空',
        'about_end_time.require' => '预约结束不能为空',

    ];


}