<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

use \think\Db; 
use app\common\model\GC;
use app\common\entity\User;

class Getdbbalance extends Command

{

    protected function configure()
    {
        $this->setName('getdbbalance')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
        $gc = new GC;
        $output->writeln("Start Command:");
        $user_list = User::field(['id','gc_address','gc_last'])->where(['status'=>'1'])->select();
        foreach($user_list AS $user){
            $output->writeln("正在处理用户 : ".$user['id']);
            if(!empty($user['gc_address'])){
                $output->writeln("用户钱包地址 : ".$user['gc_address']);
                $redata = $gc->balance($user['gc_address']);
                if( $redata['code']==1 && $redata['balance']>$user['gc_last'] ){
                    $add_balance = $redata['balance'] - $user['gc_last'];
                    Db::startTrans();
                    if( 
                        !User::where(['id'=>$user['id']])->update(['gc_last'=>$redata['balance']]) || 
                        !User::where(['id'=>$user['id']])->setInc('gc',$add_balance) 
                    ){
                        Db::rollback();
                        $output->writeln("添加数量失败！");
                    };
                    Db::commit();
                }else{
                    $output->writeln("无可用的新数量！");
                }
            }else{
                $output->writeln("无钱包地址！");
            }
        }
	}

}



