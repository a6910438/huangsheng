<?php
namespace app\common\entity;

use think\Db;
use think\Model;
use app\common\entity\FomoConfig;
use app\common\entity\FomoGame;


class Keychange extends Model
{
    protected $table = 'fomo_keychange';

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


    /*
        控制key价值  
        更新倒计时
    */
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

        //插表
        $entity = new self();
        $entity->periods = $recently['periods'];
        $entity->amount = $amount;
        $entity->before = $recently['later'];
        $entity->later = $newkey;
        $entity->addkey = $Keynum;
        $entity->createtime = time();
        $result = $entity->save(); 


        //更新倒计时
        $FomoGame = new FomoGame();
        $FomoGamelist = $FomoGame->where('status',1)->order('id','desc')->find();

        $FomoConfig = new FomoConfig();
        $addseconds = $FomoConfig->getValue('addseconds');

        $addtime = bcmul($Keynum,$addseconds,8);
        $newendtime = bcadd($FomoGamelist['endtime'],$addtime,8);

        if($newendtime > time()+86400 ){
            $newendtime = time()+86400;
        }

        $FomoGame->where('id',$FomoGamelist['id'])->update(['endtime'=>$newendtime]);

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
