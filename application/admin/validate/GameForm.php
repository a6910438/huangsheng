<?php

namespace app\admin\validate;

use think\Request;
use think\Validate;

class GameForm extends Validate {

    protected $rule = [
    	'time' => 'require',
        'play_scale' => 'require',
        'team_scale' => 'require',
        // 'bonus_scale' => 'require',
    ];
    protected $message = [
	    'time.require' => '请填写倒计时间',
	    'play_scale.require' => '请填写赢家比例',
        'team_scale.require' => '请填写团队比例',
        // 'bonus_scale.require' => '请填写分红比例',
    ];

}
