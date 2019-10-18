<?php
namespace app\common\service\Level;

use app\common\entity\Config;
use app\common\entity\Log;
use app\common\entity\UserProduct;

class Service
{
    //获取升级条件
    public function getCondition($level)
    {
        switch ($level) {
            case 2:
                $v2Condition = Config::getValue('rules_v2_condition');
                $v2Condition = explode('@', $v2Condition);
                return $v2Condition;
            case 3:
                $v3Condition = Config::getValue('rules_v3_condition');
                $v3Condition = explode('@', $v3Condition);
                return $v3Condition;
            case 4:
                $v4Condition = Config::getValue('rules_v4_condition');
                $v4Condition = explode('@', $v4Condition);
                return $v4Condition;
            case 5:
                $v5Condition = Config::getValue('rules_v5_condition');
                $v5Condition = explode('@', $v5Condition);
                return $v5Condition;
        }
    }

    //升级开始
    public function upgrade($userInfo)
    {
        /*$userInfo = \app\common\entity\User::where('id', $userId)->find();
        if (!$userInfo) {
            return false;
        }*/
        $level = $userInfo->level;
        switch ($level) {
            case 1:
                $this->upgradeV2($userInfo);
                break;
            case 2:
                $this->upgradeV3($userInfo);
                break;
            case 3:
                $this->upgradeV4($userInfo);
                break;
            case 4:
                $this->upgradeV5($userInfo);
                break;
        }
    }

    //升级为v2
    public function upgradeV2($userInfo)
    {
        if ($userInfo->level == 2) {
            return false;
        }
        $condition = $this->getCondition(2);
        $child = $condition[0];
        $team = $condition[1];
        $rate = $condition[2];
        $userTeam = $userInfo->getTeamInfo();
        $teamTotal = $userInfo->getChildTotal();
        if ($child > $teamTotal) {
            return false;
        }
        if ($team > $userTeam['total']) {
            return false;
        }
        if ($rate > $userTeam['rate']) {
            return false;
        }
        //进行升级
        $userInfo->level = 2;
        if ($userInfo->save()) {
            //升级成功,送奖励
            $reward = $this->getReward(2);
            $this->sendUserProduct($reward['product_id'], $reward['number'], $userInfo->id);
        } else {
            Log::addLog(Log::TYPE_UPGRADE, sprintf("用户升级v2失败,mobile:%s", $userInfo->mobile), '');
        }

        //升级上级
        /*$parent = \app\common\entity\User::where('id', $userInfo->pid)->find();
        if ($parent) {
            $this->upgradeV3($parent);
        }*/
    }

    //升级为v3
    public function upgradeV3($userInfo)
    {
        if ($userInfo->level == 3) {
            return false;
        }
        $condition = $this->getCondition(3);
        $userTeam = $userInfo->getTeamInfo();
        $childTotal = \app\common\entity\User::where('pid', $userInfo->id)->where('level', 2)->count();
        if ($condition[0] > $childTotal) {
            return false;
        }
        if ($condition[1] > $userTeam['rate']) {
            return false;
        }

        $userInfo->level = 3;
        if ($userInfo->save()) {
            //升级成功,送奖励
            $reward = $this->getReward(3);
            $this->sendUserProduct($reward['product_id'], $reward['number'], $userInfo->id);
        } else {
            Log::addLog(Log::TYPE_UPGRADE, sprintf("用户升级v3失败,mobile:%s", $userInfo->mobile), '');
        }

        //升级上级
        /*$parent = \app\common\entity\User::where('id', $userInfo->pid)->find();
        if ($parent) {
            $this->upgradeV4($parent);
        }*/
    }

    //升级为v4
    public function upgradeV4($userInfo)
    {
        if ($userInfo->level == 4) {
            return false;
        }
        $condition = $this->getCondition(4);
        $userTeam = $userInfo->getTeamInfo();
        $childTotal = \app\common\entity\User::where('pid', $userInfo->id)->where('level', 3)->count();
        if ($condition[0] > $childTotal) {
            return false;
        }
        if ($condition[1] > $userTeam['rate']) {
            return false;
        }

        $userInfo->level = 4;
        if ($userInfo->save()) {
            //升级成功,送奖励
            $reward = $this->getReward(4);
            $this->sendUserProduct($reward['product_id'], $reward['number'], $userInfo->id);
        } else {
            Log::addLog(Log::TYPE_UPGRADE, sprintf("用户升级v4失败,mobile:%s", $userInfo->mobile), '');
        }

        //升级上级
        /*$parent = \app\common\entity\User::where('id', $userInfo->pid)->find();
        if ($parent) {
            $this->upgradeV5($parent);
        }*/
    }

    //升级为v5
    public function upgradeV5($userInfo)
    {
        if ($userInfo->level == 5) {
            return false;
        }
        $condition = $this->getCondition(5);
        $userTeam = $userInfo->getTeamInfo();
        $childTotal = \app\common\entity\User::where('pid', $userInfo->id)->where('level', 4)->count();
        if ($condition[0] > $childTotal) {
            return false;
        }
        if ($condition[1] > $userTeam['rate']) {
            return false;
        }

        $userInfo->level = 5;
        if ($userInfo->save()) {
            //升级成功,送奖励
            $reward = $this->getReward(5);
            $this->sendUserProduct($reward['product_id'], $reward['number'], $userInfo->id);
        } else {
            Log::addLog(Log::TYPE_UPGRADE, sprintf("用户升级v5失败,mobile:%s", $userInfo->mobile), '');
        }

    }

    public function getReward($level)
    {
        switch ($level) {
            case 2:
                return [
                    'product_id' => 1,
                    'number' => Config::getValue('rules_v2_reward'),
                ];
            case 3:
                return [
                    'product_id' => 2,
                    'number' => Config::getValue('rules_v3_reward'),
                ];
            case 4:
                return [
                    'product_id' => 3,
                    'number' => Config::getValue('rules_v4_reward'),
                ];
            case 5:
                return [
                    'product_id' => 4,
                    'number' => Config::getValue('rules_v5_reward'),
                ];
        }
    }

    public function sendUserProduct($productId, $number, $userId)
    {
        $model = new UserProduct();
        for ($i = 0; $i < $number; $i++) {
            $result = $model->addInfo($userId, $productId, UserProduct::TYPE_UPGRADE);

            if (!$result) {
                Log::addLog(Log::TYPE_UPGRADE, "升级送矿机失败,会员id：$userId", [
                    'user_id' => $userId
                ]);
            }
        }
    }
}