<?php
namespace app\common\service\Share;

use app\common\entity\Config;
use app\common\entity\Log;
use app\common\entity\Orders;
use app\common\entity\User;
use app\common\entity\UserMagicLog;
use think\Db;

class Service
{

    protected $rates;

    public $magic;

    public $level = [];

    public function __construct()
    {
        //获取参与分红的金额
        $time = strtotime(date('Y-m-d', time()));
        $magic = Orders::where('status', Orders::STATUS_FINISH)
            ->where('finish_time', '<', $time)
            ->where('finish_time', '>=', $time - 24 * 3600)
            ->sum('charge_number');
        $this->magic = $magic;
        $this->rates = $this->getRate();
    }

    public function exec($user)
    {
        $level = $user->level;
        if ($level > 1) {
            $rate = isset($this->rates[$level]) ? $this->rates[$level] : 0;
            if ($rate) {
                //产生收益
                $this->income($user, $rate);
            }
        }
    }

    public function income($user, $rate)
    {
        Db::startTrans();
        $total = $this->getLevelTotal($user->level);
        if ($total > 0) {
            $yield = bcmul($this->magic, $rate, 8) / (100 * $total);
        } else {
            $yield = bcmul($this->magic, $rate, 8) / 100;
        }

        try {
            $old = $user->magic;
            $new = bcadd($old, $yield, 8);
            $user->magic = $new;

            if (!$user->save()) {
                throw new \Exception('修改会员金币数量失败');
            }

            //填写日志
            $result = UserMagicLog::addInfo($user->id, "全网联盟分红", $yield, $old, $new, UserMagicLog::TYPE_SHARE);

            if (!$result) {
                throw new \Exception('写入金币日志失败');
            }

            Db::commit();

        } catch (\Exception $e) {
            Db::rollback();

            Log::addLog(Log::TYPE_SHARE, $e->getMessage(), [
                'user_id' => $user->id,
                'magic' => $yield
            ]);
        }
    }


    //收益条件
    public function getRate()
    {
        $rates = [
            '2' => Config::getValue('rules_v2_rate'),
            '3' => Config::getValue('rules_v3_rate'),
            '4' => Config::getValue('rules_v4_rate'),
            '5' => Config::getValue('rules_v5_rate'),
        ];

        return $rates;
    }

    //获取等级的人数
    public function getLevelTotal($level)
    {
        $levels = $this->level;
        if (isset($levels[$level])) {
            return $levels[$level];
        }
        //查询
        $total = User::where('level', $level)->count();

        $this->level[$level] = $total;

        return $total;
    }
}