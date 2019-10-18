<?php
namespace app\common\entity;

use think\Db;
use think\Model;

class Orders extends Model
{
    protected $table = 'orders';

    const TYPE_BUY = 1; //买入订单
    const TYPE_SALE = 2; //卖出订单

    const STATUS_DEFAULT = 1; //加入订单
    const STATUS_PAY = 2; //等待付款
    const STATUS_CONFIRM = 3; //等待确认付款
    const STATUS_FINISH = 4; //订单完成

    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '待审核';
            case 2:
                return '通过';
            case 3:
                return '拒绝';
            default:
                return '';
        }
    }
    public function getType($type)
    {
        switch ($type) {
            case 1:
                return '解封申请';
            case 2:
                return '充币申请';
            case 3:
                return '等待收款';
            case 4:
                return '交易完成';
            default:
                return '';
        }
    }

    protected function setOrderNumber($memberId)
    {
        return date('Ymd') . $memberId . date('His');
    }

    public function add($userId, $data, $type = self::TYPE_BUY)
    {
        $entity = new self();
        $marketPrice = new MarketPrice();
        $entity->order_number = $this->setOrderNumber($userId);
        $entity->user_id = $userId;
        $entity->number = $data['number'];
        $entity->price = $data['price'];
        $entity->create_time = time();
        $entity->status = self::STATUS_DEFAULT;
        $entity->types = $type;
        $entity->charge_number = $this->getChargeNumber($data['number'], $userId);

        $totalPrice = bcmul($data['number'], $data['price'], 3);
        $entity->total_price = $totalPrice;
        $entity->total_price_china = $marketPrice->getChinaMoney($totalPrice, 3);

        $result = $entity->save();
        if ($result) {
            return $entity;
        }
        return false;

    }

    public function getChargeNumber($number, $userId)
    {
        $rate = Config::getValue('market_sys_rate');
        $rate = explode('@', $rate);

        //查询出会员等级
        $user = User::where('id', $userId)->find();
        $rate = $rate[$user->level-1];
        return bcmul($number, $rate, 8) / 100;
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
    //添加新数据
    public function addNew($query,$data)
    {
        $query->uid = $data['uid'];
        $query->describe = $data['describe'];
        $query->pic = $data['pic'];
        $query->types = $data['types'];
        $query->active_id = $data['active_id'];
        $query->create_time = time();
        return $query->save();
    }
}
