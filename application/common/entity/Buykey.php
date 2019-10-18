<?php
namespace app\common\entity;

use think\Db;
use think\Model;
use app\common\entity\FomoConfig;


class Buykey extends Model
{
    protected $table = 'fomo_buykey';

    const STATUS_DEFAULT = 0; //生成

    const STATUS_PAY = 1; //付款
    /**
     * 获取当前币种价值
     * cointype 交易币类型
     */
    public function getKey()
    {
        //查看最新的市值
        $recently = self::order('id', 'desc')->find();

        return $recently['later'];
    }
     /**
     * 发生时间
     */
    public function getCreateTime()
    {
        return date('Y-m-d H:i:s',$this->createtime);
    }

    public function getStatus()
    {
        switch ($this->status) {
            case self::STATUS_DEFAULT:
                return '未付款';
            case self::STATUS_PAY:
                return '已付款';
            default:
                return '';
        }
    }

    public function addKey($Keynum)
    {


        if($Keynum<=0){
            return;
        }

        //查看最新的市值
        $recently = self::order('id', 'desc')->find();

        //每增加Key key值增加
        $FomoConfig = new FomoConfig();
        $keygoup = $FomoConfig->getValue('keygoup');

        $amount = bcmul($Keynum,$keygoup,8);
        $newkey = bcadd($recently['later'],$amount,8);

        // periods 第几期 （期数） amount key涨幅变化 before 变化前key价值 later 变化后key价值 addkey 增加key数量 createtime 变化时 

        //插表
        $entity = new self();
        $entity->periods = $recently['periods'];
        $entity->amount = $amount;
        $entity->before = $recently['later'];
        $entity->later = $newkey;
        $entity->addkey = $Keynum;
        $entity->createtime = time();
        $result = $entity->save(); 

    }

    public static function startKey($periods,$later)
    {

        $entity = new self();

        $entity->periods = $periods;
        $entity->later = floatval($later);
        $entity->before = 0;
        $entity->amount = 0;
        $entity->addkey = 0;
        $entity->createtime = time();
            
        return $entity->save(); 

    }



}
