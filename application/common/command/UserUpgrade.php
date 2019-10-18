<?php
namespace app\common\command;

use app\common\entity\User;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class UserUpgrade extends Command
{


    protected function configure()
    {
        $this->setName('user-upgrade')
            ->setDescription('会员升级');
    }

    protected function execute(Input $input, Output $output)
    {
        set_time_limit(600);
        //获取全部会员 已认证的，正常的
        $users = User::where('is_certification', 1)->where('status', 1)->field('id,level')->select();
        $model = new \app\common\service\Level\Service();
        if ($users) {
            foreach ($users as $user) {
                $model->upgrade($user);
            }
        }
    }

}