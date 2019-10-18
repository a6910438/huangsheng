<?php

namespace app\admin\validate;

use think\Request;
use think\Validate;

class TeamForm extends Validate {

    protected $rule = [
        'image' => 'require',
        'title' => 'require',
        'content' => 'require',
        'intro' => 'require',
        'pond_scale' => 'require',
        'bonus_scale' => 'require',
        'next_scale' => 'require',
    ];

    protected $message = [
        'image.require' => '请上传图片',
        'title.require' => '请填写队伍名称',
        'content.require' => '请填写队伍标语',
        'intro.require' => '请填写队伍简介',
        'pond_scale.require' => '请填写奖池比例',
        'bonus_scale.require' => '请填写分红比例',
        'next_scale.require' => '请填写进入下一轮的比例',
    ];

}
