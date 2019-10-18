<?php

namespace app\index\validate;

use app\common\entity\Team;
use think\Validate;

class TeamForm extends Validate {

    protected $rule = [
        'name' => 'require|checkName|max:30',
        'qq' => 'require|number',
        'wx' => 'require',
        'mobile' => 'require',
        'groupid' => 'require',
        'declaration' => 'require|max:300',
    ];
    protected $message = [
        'name.require' => '名称不能为空',
        'name.max' => '名称最多不能超过30个字符',
        'leader_qq.require' => 'QQ号不能为空',
        'leader_wx.require' => '微信号不能为空',
        'leader_mobile.require' => '手机号不能为空',
        'qq_groupid.require' => 'QQ群不能为空',
        'declaration.require' => '公会宣言不能为空',
        'declaration.max' => '宣言最多不能超过300个字符',
    ];

    public function checkName($value, $rule, $data = []) {
        $exists = Team::where('name', $value)->count();
        if ($exists) {
            return '工会名称已存在';
        }
        return true;
    }

}
