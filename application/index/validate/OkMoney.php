<?php
namespace app\index\validate;

use app\common\entity\Match;
use app\common\entity\UserInviteCode;
use app\index\model\SendCode;
use app\index\model\SendMail;
use think\Validate;

class OkMoney extends Validate
{
    protected $rule = [
        'active_id' => 'require|checkActive_id',
    ];

    protected $message = [
        'active_id.require' => 'Order ID cannot be empty',
    ];
    public function checkActive_id($value)
    {
        $res = Match::where('id',$value)->find();
        if(!$res){
            return 'Order does not exist';
        }
        return true;

    }
}