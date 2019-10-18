<?php

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

//use think\Controller;
//use think\Cookie;
use think\Db;
use app\common\model\GC;
use app\common\entity\User;
//use think\Request;
use app\common\entity\Config;
use think\Log;

/* 阿里短信通知 */

class GCUpdate extends Command
{

    public $output;

    protected function configure()
    {
        $this->setName('gcupdate')->setDescription('Here is the remark ');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->output = new Output;
        //file_put_contents(dirname(__FILE__).'/output.log',date("Y-m-d H:i:s"));
        $output->writeln("Start Command : ");
        while (true) {
            //更新用户GC钱包
            $this->updata_gc_wallet();
            //更新GC汇率
            $this->update_gc_exchange_rate();
            //等待60秒执行一次循环
            sleep(100);
        };
        //任务结束
        $output->writeln("done.");
    }

    /**
     * 更新用户GC钱包
     */
    private function updata_gc_wallet()
    {
        $gc = new GC;
        $user_list = User::field(['id', 'gc_address', 'gc_last'])->where(['status' => '1'])->select();
        foreach ($user_list AS $user) {
            if (!empty($user['gc_address'])) {
                $redata = $gc->balance($user['gc_address']);
                $user['gc_last'] = round($user['gc_last'], 4);
                if ($redata['code'] == 1 && $redata['balance'] > $user['gc_last']) {
//                    Log::Write("用户钱包地址：".$user['gc_address']);
//                    $this->output->writeln("正在处理用户 : ".$user['id']);
//                    $this->output->writeln("用户钱包地址 : ".$user['gc_address']);
                    $add_balance = $redata['balance'] - $user['gc_last'];
                    //添加GC流水日志
                    Db::startTrans();
                    try {
                        //code...

                        if (
                            !User::where(['id' => $user['id']])->update(['gc_last' => $redata['balance']]) ||
                            !User::where(['id' => $user['id']])->setInc('gc', $add_balance)
                        ) {
                            Db::rollback();
                            $this->output->writeln("添加数量失败！");
                        };
                        Db::commit();
                    } catch (\Throwable $th) {
                        Db::rollback();
                        //throw $th;
                    }
                } else {
                    //$this->output->writeln("无可用的新数量！");
                }
            } else {
                //$this->output->writeln("无钱包地址！");
            }
        }
    }

    /**
     * 更新GC汇率
     */
    private function update_gc_exchange_rate()
    {
        try {
            $this->output = new Output;
            $this->output->writeln("Start Exchange : ");
            //code...
            $curl = new \service\cURL;
            $minute = Config::getValue('get_exchange_minute');
            if (!is_numeric($minute) || $minute < 0) {
                $minute = 0;
            };
            $result = $curl->init('https://app.galaxycoin.vip/api/v1/base/kline/gc_usdt?limit=' . $minute);
            $json = json_decode($result, true);
            $this->output->writeln("返回的json数据为:" . $json);
            if (empty($json) || $json <= 0) {
                return;
            }
            $this->output->writeln("通过了");
            $value = $json * 7;
            $this->output->writeln("准备更新的汇率是:" . $value);

            $config = Config::where('key', 'exchange_rate')->find();
            if (!$config) {
                throw new AdminException('操作错误');
            }
            $config->value = $value;
            if ($config->save() === false) {
                throw new AdminException('修改失败');
            }
            $this->output->writeln('更新GC汇率成功');

        } catch (\Throwable $th) {
            //throw $th;
            $this->output->writeln('更新GC汇率失败，' . $th);
            return;
        }
    }


}