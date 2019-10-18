<?php
namespace app\common\entity;

use think\Db;
use think\Model;
use app\common\entity\LegalDeal;
use app\common\entity\LegalWallet;

class LegalList extends Model
{
    protected $table = 'legal_list';

    const TYPE_BUY = 1; //买入订单
    const TYPE_SALE = 2; //卖出订单
    
    const IS_SYS = 1; //是否系统生产

    const STATUS_DEFAULT = 1; //加入订单

    const STATUS_FINISH = 2; //订单完成

    public function getStatus()
    {
        switch ($this->status) {
            case self::STATUS_DEFAULT:
                return '等待交易';
            case self::STATUS_FINISH:
                return '交易完成';
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
        return 'LL'.date('Ymd') . $memberId . date('His');
    }

    public function add($userId, $data, $types = self::TYPE_BUY,$issys = 0)
    {
           
        $entity = new self();
        $entity->tid = $this->getTid($userId);
        $entity->user_id = $userId;
        $entity->number = $data['number'];
        $entity->price = $data['price'];
        $entity->totalprice = $data['totalprice'];
        $entity->money_type = $data['money_type'];
        $entity->minbuy = $data['minbuy'];
        $entity->maxbuy = $data['maxbuy'];
        $entity->types = $types;
        $entity->status = self::STATUS_DEFAULT;
        $entity->createtime = time();
        if($issys){
            $entity->linetime = $data['createtime'];
        }else{
            $entity->linetime = time();
        }
        $entity->is_sys = $issys;

        Db::startTrans();

        try {
            $result = $entity->save();
            
            if (!$result) {
                throw new \Exception('操作失败');
            }

            if($types == self::TYPE_SALE ){
                $result = LegalWallet::setFreeze($userId,$data['money_type'],$data['number']);
                if (!$result) {
                    throw new \Exception('操作失败');
                }
            }

            Db::commit();
            return true;

        } catch (\Exception $e) {
            Db::rollback();

            return true;
        }  
           
        return false;

    }

    public function buyList($userId, $data, $type = self::TYPE_BUY)
    {
        //查询订单详情
        $list = self::where('id', $data['id'])->where('status',1)->find();
            
        if (!$list) {
            return false;
        }
            
        if($list->number<$data['number'] || $list->totalprice < $data['totalprice']){
            return false;
        }
           
        Db::startTrans();
        try {
           
            $newnumber = bcsub($list->number,$data['number'], 8);
            $newprice = bcsub($list->totalprice,$data['totalprice'],8);

            //生产交易订单
            $LegalDeal = new LegalDeal();
            
            $types = $list->types == self::TYPE_BUY ? self::TYPE_SALE: ($list->types == self::TYPE_SALE ? self::TYPE_BUY: 0);

            $result = $LegalDeal->add($userId,$data,$types);
               
            if (!$result) {
                throw new \Exception('操作失败');
            }

            if($types == self::TYPE_SALE ){
                $result = LegalWallet::setFreeze($userId,$list['money_type'],$data['number']);
                if (!$result) {
                    throw new \Exception('操作失败');
                }
            }

            $resulta = $this->add($list->user_id,array('price'=>$list->price,'number'=>$newnumber,'totalprice'=>$newprice,'money_type'=>$list->money_type,'createtime'=>$list->createtime),$list->types,self::IS_SYS);

            if (!$resulta) {
                throw new \Exception('操作失败');
            }

            //订单等待付款状态
            $list->status = self::STATUS_FINISH;
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

    /**
     * 确定已收款
     */
    public function confirm()
    {
        $userId = $this->types == self::TYPE_BUY ? $this->user_id : ($this->types == self::TYPE_SALE ? $this->target_user_id : 0);
        if (!$userId) {
            return false;
        }

        Db::startTrans();
        try {
            //添加用户的魔石
            $user = User::where('id', $userId)->find();
            $old = $user->magic;
            $change = bcadd($old, $this->number, 8);
            $new = $change;
            $user->magic = $new;

            if (!$user->save()) {
                throw new \Exception('操作失败');
            }

            //写入日志
            $model = new UserMagicLog();
            $result = $model->addInfo($userId, '买入交易成功', $this->number, $old, $new, UserMagicLog::TYPE_ORDER);
            if (!$result) {
                throw new \Exception('操作失败');
            }

            //修改订单状态
            $this->status = Orders::STATUS_FINISH;
            $this->finish_time = time();

            if (!$this->save()) {
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
     * 取消交易中的订单
     */
    public function cancel()
    {
        $userId = $this->types == self::TYPE_BUY ? $this->target_user_id : ($this->types == self::TYPE_SALE ? $this->user_id : 0);
        if (!$userId) {
            return false;
        }

        Db::startTrans();
        try {
            //返回用户的魔石的手续费
            $user = User::where('id', $userId)->find();
            $old = $user->magic;
            $change = bcadd($this->number, $this->charge_number, 8);
            $new = bcadd($old, $change, 8);
            $user->magic = $new;

            if (!$user->save()) {
                throw new \Exception('操作失败');
            }

            //写入日志
            $model = new UserMagicLog();
            $result = $model->addInfo($userId, '出售交易取消', $change, $old, $new, UserMagicLog::TYPE_ORDER);
            if (!$result) {
                throw new \Exception('操作失败');
            }

            //删除订单
            if (!$this->delete()) {
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