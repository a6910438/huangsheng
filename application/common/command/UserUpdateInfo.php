<?php

namespace app\common\command;

use app\admin\exception\AdminException;
use app\common\entity\TitleLog;
use think\console\Command;
use think\console\Input;
use think\console\Output;

//use think\Cookie;
use think\Db;
/* 日志引用 */

use app\common\entity\User;
use app\common\entity\Config;
use app\index\model\Publics as PublicModel;

class UserUpdateInfo extends Command
{
    public $public_test = 0;

    public $output;

    protected function configure()
    {
        $this->setName('userUpdateInfo')->setDescription('User Info Updated ');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->output = new Output;
        $this->output->writeln("Start Command : ");
        while (true) {
            //用户等级升级程序
            $this->user_level_upgare();
            
            //等待300秒执行一次循环
            sleep(300);
        };
        //任务结束
        $this->output->writeln("done.");
    }

    /**
     * 检测用户是否合格并且升级
     *
     * @return void
     */
    protected function user_level_upgare()
    {
        $entity = new \app\common\entity\MyWallet();
        // 获取所有正常用户
        $users = User::where('status', 1)->where('is_delete', '<>', 1)->field('id,status,is_active,chat_num,is_delete')->select();
        foreach ($users as $key => $user) {
            if($user['id'] && $user['chat_num']){
                $entity->levelupgare($user['id']);
                // $this->output->writeln("upgared.");
            }
        }
        
    }

}