<?php

namespace app\admin\validate;

use think\Request;
use think\Validate;

class AddGameForm extends Validate {

    protected $rule = [
        'id' => 'require',
        'teamid' => 'require',
    ];
    protected $message = [
        'id.require' => '游戏不存在或已删除',
        'teamid.require' => '请选择队伍',
    ];

}
