<?php

namespace app\index\validate;

use app\common\entity\LegalConfig;
use app\common\entity\MarketPrice;
use app\common\entity\Orders;
use app\common\entity\User;
use app\common\service\Users\Identity;
use think\Validate;

class LegalSaleForm extends Validate {

    protected $rule = [
        'number' => 'require|checkNumber',
        'price' => 'require',//|checkPrice
    ];
    protected $message = [
        'number.require' => '卖出数量不能为空',
        'price.require' => '单价不能为空',
    ];

    public function checkNumber($value, $rules, $data = []) {
//        if (!preg_match('/^[1-9]\d*$/', $value)) {
//            return '卖出数量必须为大于1的正整数';
//        }

//        $min = Config::getValue('market_min');
//        $max = Config::getValue('market_max');
//
//        if ($min > 0 && $value < $min) {
//            return sprintf('卖出数量必须在%s-%s之间', $min, $max);
//        }
//        if ($max > 0 && $value > $max) {
//            return sprintf('卖出数量必须在%s-%s之间', $min, $max);
//        }

        //检查用户的魔石数量
        $idenity = new Identity();
        $order = new Orders();
        $chargNumber = $order->getChargeNumber($value, $idenity->getUserId());

        $user = User::where('id', $idenity->getUserId())->find();

        $moneyName = Config::getValue('web_money_name');
        if ($user->magic < $chargNumber + $value) {
            return sprintf($moneyName.'不够了');
        }

        return true;
    }

    public function checkPrice($value, $rules, $data = []) {
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
            return '卖出单价最多为2位小数';
        }
        $marketPrice = new MarketPrice();
        $prices = $marketPrice->getCurrentPrice();

        $min = $prices['prices']['min'];
        $max = $prices['prices']['max'];

        if ($value < $min || $value > $max) {
            return sprintf('单价在%s-%s之间', $min, $max);
        }
        return true;
    }

}
