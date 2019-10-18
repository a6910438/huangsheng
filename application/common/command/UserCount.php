<?php
namespace app\common\command;

use app\common\entity\User;
use app\common\service\Users\Cache;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class UserCount extends Command
{

    protected $allUsers = [];

    protected function configure()
    {
        $this->setName('user-count')
            ->setDescription('会员统计');
    }

    protected function execute(Input $input, Output $output)
    {
        set_time_limit(0);
        //把已有的数据全部清0
        $sql = <<<SQL
UPDATE `user_count` set `total`=0,`rate`=0
SQL;
        Db::query($sql);


        $users = User::field('id,pid,product_rate,invite_count')->order('id', 'desc')->select();

        foreach ($users as $user) {
            $this->getTeamInfo($user);
        }


    }

    public function getTeamInfo($userInfo)
    {
        //判断用户是否存在
        $ownCount = \app\common\entity\UserCount::where('user_id', $userInfo->id)->find();
        if (!$ownCount) {
            //添加数据
            $model = new \app\common\entity\UserCount();
            $model->user_id = $userInfo->id;
            $model->total = $userInfo->invite_count;
            $model->rate = 0;

            $model->save();
        }
        $total = $userInfo->invite_count + 1;
        $rate = $userInfo->product_rate;

        if ($userInfo && $userInfo->pid) {
            //查询父级原有的total 和 rate
            $userCount = \app\common\entity\UserCount::where('user_id', $userInfo->pid)->find();
            if ($userCount) {
                //更新数数据
                $userCount->total = $userCount->total + $total;
                $userCount->rate = $userCount->rate + $rate;
                $userCount->save();
            } else {
                //添加数据 获取
                $model = new \app\common\entity\UserCount();
                $model->user_id = $userInfo->pid;
                $model->total = $total;
                $model->rate = $rate;

                $model->save();
            }
        }
    }

}