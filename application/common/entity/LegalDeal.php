<?php
namespace app\common\entity;

use think\Db;
use think\Model;
use app\common\entity\LegalList;
use app\common\entity\LegalWallet;

class LegalDeal extends Model
{
    protected $table = 'legal_deal';

    const TYPE_BUY = 1; //买入订单
    const TYPE_SALE = 2; //卖出订单

    const STATUS_PAY = 1; //等待付款
    const STATUS_CONFIRM = 2; //等待确认付款
    const STATUS_FINISH = 3; //订单完成

    const STATUS_CANCEL = -1; //取消付款
    const STATUS_ERROR = -2; //错误订单

    public function getStatus()
    {
        switch ($this->status) {
            case self::STATUS_PAY:
                return '等待付款';
            case self::STATUS_CONFIRM:
                return '等待收款';
            case self::STATUS_FINISH:
                return '交易完成';
            case self::STATUS_CANCEL:
                return '取消付款';
            case self::STATUS_ERROR:
                return '错误订单';
            default:
                return '';

        }
    }
    /**
     * 发生时间
     */
    public function getCreateTime($createtime)
    {
        return date('Y-m-d H:i:s',$createtime);
    }

    protected function getTid($memberId)
    {
        return 'LD'.date('Ymd') . $memberId . date('His');
    }

    public function add($userId, $data, $types = self::TYPE_BUY)
    {   
        //查询订单详情
        $list = LegalList::where('id', $data['id'])->find();
           
        if (!$list) {
            return false;
        }

        $sale_id = $types == self::TYPE_BUY ? $list->user_id : ($types == self::TYPE_SALE ? $userId : 0);
        $buy_id = $types == self::TYPE_BUY ? $userId : ($types == self::TYPE_SALE ? $list->user_id : 0);
        $entity = new self();
        $entity->sale_id = $sale_id;
        $entity->tid = $this->getTid($userId);
        $entity->price = $list->price;
        $entity->number = $data['number'];
        $entity->totalprice = $data['totalprice'];
        $entity->types = $types;
        $entity->money_type = $list->money_type;
        $entity->buy_id = $buy_id;
        $entity->list_id = $list->id;
        $entity->status = self::STATUS_PAY;
        $entity->createtime = time();

        $result = $entity->save();
        if ($result) {
            return $entity;
        }
        return false;

    }

    /**
     * 确定付款
     */
    public function checkPay($buy_id,$id,$pay_type)
    {
        
        $list = self::where('id', $id)->where('buy_id',$buy_id)->where('status',1)->find();

        if (!$list) {
            return false;
        }

        Db::startTrans();
        try {
            
            $list->status = self::STATUS_CONFIRM;
            $list->pay_type = $pay_type;
            $list->paytime = time();

            if (!$list->save()) {
                throw new \Exception('操作失败');
            }

            Db::commit();

            return true;

        } catch (\Exception $e) {
            Db::rollback();
            return true;
        }
        
    }

    /**
     * 取消付款
     */
    public function cancelPay($buy_id,$id)
    {
        
        $list = self::where('id', $id)->where('buy_id',$buy_id)->where('status','or',"1,-2")->find();

        if (!$list) {
            return false;
        }

        Db::startTrans();
        try {
            
            $list->status = self::STATUS_CANCEL;
            $list->finishtime = time();

            if (!$list->save()) {
                throw new \Exception('操作失败');
            }

            //解封卖家冻结的金额
          	$result = LegalWallet::unFreeze($list->sale_id,$list['money_type'],$list['number']);

            if (!$result) {
                throw new \Exception('操作失败');
            }

            //处理买家违约行为
            //
            //
            //
            
            
            Db::commit();
            return true;

        } catch (\Exception $e) {

            Db::rollback();
            return true;
        }
        
    }

     /**
     * 确认收款
     */
    public function payResult($sale_id,$id)
    {
        
        $list = self::where('id', $id)->where('sale_id',$sale_id)->where('status','or',"2,-2")->find();

        if (!$list) {
            return false;
        }

        Db::startTrans();
        try {
            
            $list->status = self::STATUS_FINISH;
            $list->finishtime = time();

            if (!$list->save()) {
                throw new \Exception('操作失败');
            }


            $result = LegalWallet::setValue($sale_id,$list['money_type'],-$list['number'],'成功卖出');
            
            if (!$result) {
                throw new \Exception('操作失败');
            }

            $result = LegalWallet::setValue($list->buy_id,$list['money_type'],$list['number'],'成功买进');
            
            if (!$result) {
                throw new \Exception('操作失败');
            }

            $result = LegalWallet::unFreeze($sale_id,$list['money_type'],$list['number']);
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

    /**
     * 错误订单
     */
    public function payError($sale_id,$id)
    {
        
        $list = self::where('id', $id)->where('sale_id',$sale_id)->where('status',2)->find();

        if (!$list) {
            return false;
        }

        Db::startTrans();
        try {
            
            $list->status = self::STATUS_ERROR;
            $list->finishtime = time();

            if (!$list->save()) {
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