<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

/* 短信通知 */
use app\common\model\SendSms;

class Test extends Command
{

    public $output;

    protected function configure()
    {
        $this->setName('test')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->output = new Output;
        //file_put_contents(dirname(__FILE__).'/output.log',date("Y-m-d H:i:s"));
        $output->writeln("Start Command : ");

        $send_sms = new SendSms();
        $send_sms->set_type(2);
        $send_sms->set_mobile('18607865730');
        $send_sms->set_name('侧式房子,2019年8月8日16时03分');
        $send_sms->send();
        
        //任务结束
        $output->writeln("done.");
    }


}