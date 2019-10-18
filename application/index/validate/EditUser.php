<?php
namespace app\index\validate;

use app\common\entity\UserInviteCode;
use app\index\model\SendCode;
use app\index\model\SendMail;
use think\Validate;

class EditUser extends Validate
{
    protected $rule = [
        'nick_name' => 'require',
        'trad_password' => 'require',
        'qid' => 'require',
        'answer' => 'require',
    ];

    protected $message = [
        'nick_name.require' => 'nickname cannot be empty',
        'trad_password.require' => 'Secondary password cannot be empty',
        'qid.require' => 'Problem ID cannot be empty',
        'answer.require' => 'Answer should not be empty',
    ];
}