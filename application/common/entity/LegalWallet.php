<?php
namespace app\common\entity;

use think\Db;
use think\Model;
use app\common\entity\LegalWalletLog;

class LegalWallet extends Model
{
    protected $table = 'legal_wallet';

    const TYPE_BUY = 1; 
    
    public static function getValue($userId,$money_type)
    {
        $value = self::where('user_id', $userId)->where('money_type',$money_type)->find();

        if(!$value){
            return false;
        }

        return $value;
       
    }

    public static function setValue($userId,$money_type,$number,$remark)
    {
        $value = self::where('user_id', $userId)->where('money_type',$money_type)->find();
           
        Db::startTrans();

        try {

            if(!$value){

                if($number<=0){
                   throw new \Exception('操作失败');
                }

                $entity = new self();
                $entity->user_id = $userId;
                $entity->money_type = $money_type;
                $entity->number = $number;
                $entity->updatetime = time();
                $old = 0;
                $new = $number;

                if (! $entity->save()) {
                       
                    throw new \Exception('操作失败');
                }
                
            }else{

                    $old = $value->number;
                    $change = bcadd($old, $number, 8);
                    $new = $change;
                    $value->number = $new;

                    if (!$value->save()) {
                        throw new \Exception('操作失败');
                    }
            }

            $model = new LegalWalletLog();
               
            $result = $model->addInfo($userId,$money_type,$number, $old, $new, $remark, LegalWalletLog::TYPE_SYSTEM);
                  
            if (!$result) {
                throw new \Exception('操作失败');
            }

            Db::commit();
            return true;

        } catch (\Exception $e) {

            Db::rollback();
            return true;
        }     

    }
    

    public static function setFreeze($userId,$money_type,$number)
    {
        $value = self::where('user_id', $userId)->where('money_type',$money_type)->find();
           
        if(!$value){
            return false;
        } 

        Db::startTrans();

        try {

            $old = $value->freeze;
            $change = bcadd($old, $number, 8);
            $new = $change;
            $value->freeze = $new;
           
            if (!$value->save()) {
                throw new \Exception('操作失败');
            }   

            Db::commit();
            return true;

        } catch (\Exception $e) {

            Db::rollback();
            return true;
        }     
    }    


    public static function unFreeze($userId,$money_type,$number)
    {
        $value = self::where('user_id', $userId)->where('money_type',$money_type)->find();
           
        if(!$value){
            return false;
        } 

        Db::startTrans();

        try {

            $old = $value->freeze;
            $change = bcsub($old, $number, 8);
            $new = $change;
            
            if($new<0){
                throw new \Exception('操作失败');
            }

            $value->freeze = $new;
           
            if (!$value->save()) {
                throw new \Exception('操作失败');
            }   

            Db::commit();
            return true;

        } catch (\Exception $e) {

            Db::rollback();
            return true;
        }     
    }    


}