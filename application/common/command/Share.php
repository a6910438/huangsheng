<?php
namespace app\common\command;

use app\common\entity\User;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Share extends Command
{
    protected function configure()
    {
        $this->setName('share')
            ->setDescription('分红处理');
    }

    protected function execute(Input $input, Output $output)
    {
        set_time_limit(7200);
        $model = new \app\common\service\Share\Service();
        if ($model->magic >= 0) {
            //获取全部会员
            $users = User::where('level', '>', 1)->select();
            foreach ($users as $user) {
                //享受全球分红收益
                $model->exec($user);
            }
        }

    }
}