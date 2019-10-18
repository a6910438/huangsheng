<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\entity\Orders;
use app\index\model\Market;

class CheckOrder extends Command
{


    protected function configure()
    {
        $this->setName('check-order')
            ->setDescription('撤销订单');
    }

    protected function execute(Input $input, Output $output)
    {
        set_time_limit(7200);
        $todaytime = strtotime(date('Y-m-d',time()));
        $orderList = Orders::where('status',  Orders::STATUS_DEFAULT)->where('create_time', '<', $todaytime)->select();

        $service = new Market();
        foreach($orderList as $k=>$order){
            switch ($order['types']) {
                case 1:
                    $result = $service->cancelBuy($order);
                    break;
                case 2:
                    $result = $service->cancelSale($order);
                    break;
                default:
                    continue;
            }
        }
    }


}