<?php
namespace app\index\validate;

use app\common\entity\LegalConfig;
use app\common\entity\MarketPrice;
use think\Validate;

class LegalBuyForm extends Validate
{
    protected $rule = [
        'price' => 'require|checkPrice',
        'number' => 'require|checkNumber',
        'money_type'=>'require|checkType',
        'seltype'=>'require|in:1,2',
        'mytype'=>'require|in:1,2',
        'minbuy'=>'require|number',
        'maxbuy'=>'require|number',
        
    ];

    protected $message = [
        'seltype.require'=>'请选择类型',
        'money_type.require'=>'交易类型错误',
        'price.require' => '单价不能为空',
        'number.require' => '交易数量不能为空',
        'minbuy.require' => '单笔最小限额不能为空',
        'maxbuy.require' => '单笔最大限额不能为空',
        'mytype.require'=>'未知错误',
    ];

    public function checkType($value, $rules, $data = [])
    {   
    
        return true;
    }

    public function checkNumber($value, $rules, $data = [])
    {   
         if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
           return '购买数量最多为2位小数';
       }
        // if (!preg_match('/^[1-9]\d*$/', $value)) {
        //     return '购买数量必须为大于1的正整数';
        // }

        // $min = Config::getValue('market_min');
        // $max = Config::getValue('market_max');

        // if ($min > 0 && $value < $min) {
        //     return sprintf('购买数量必须在%s-%s之间', $min, $max);
        // }
        // if ($max > 0 && $value > $max) {
        //     return sprintf('购买数量必须在%s-%s之间', $min, $max);
        // }
        return true;
    }

   public function checkPrice($value, $rules, $data = [])
   {
       if (!preg_match('/^\d+(\.\d{1,2})?$/', $value)) {
           return '购买单价最多为2位小数';
       }
       // $marketPrice = new MarketPrice();
       // $prices = $marketPrice->getCurrentPrice();

       // $min = $prices['prices']['min'];
       // $max = $prices['prices']['max'];

       // if ($value < $min || $value > $max) {
       //     return sprintf('单价在%s-%s之间', $min, $max);
       // }
       return true;
   }


}