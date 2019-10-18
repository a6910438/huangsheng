<?php
namespace app\common\command;

use app\common\entity\UserProduct;
use app\common\service\Product\Compute;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\entity\Log;

class IncomeCheck extends Command
{


    protected function configure()
    {
        $this->setName('income-check')
            ->setDescription('魔盒收益');
    }

    protected function execute(Input $input, Output $output)
    {
        set_time_limit(7200);
        $model = new Compute();
        $today = strtotime(date('Y-m-d 00:00:00'));
        $userProduct = UserProduct::where('status', UserProduct::STATUS_RUNNING)
                ->where('last_time','<>',0)
                ->where('last_time','<',$today)->select();
        $count = 0;
        foreach($userProduct as $k=>$v){
            $res = $model->income($v);
            if($res){
                $count++;
            }else{
                Log::addLog(Log::TYPE_INCOME, $res, [
                    'id' => $v->id,
                    'user_id' => $v->user_id,
                    'product_number' => $v->product_number]);
            }
        }
        if($count){
            Log::addLog(Log::TYPE_INCOME, '收益检查', $count);
        }
    }


}