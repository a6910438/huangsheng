<?php
namespace app\common\entity;

use think\Db;
use think\Model;

class UserProduct extends Model
{
    protected $table = 'user_product';

    public $autoWriteTimestamp = true;

    const TYPE_REGISTER = 4; //注册赠送
    const TYPE_SYSTEM = 1; //后台发放
    const TYPE_BUY = 2; //购买
    const TYPE_UPGRADE = 3; //升级赠送
    const TYPE_CERTIFICATION = 5; //认证赠送

    const STATUS_RUNNING = 1; //运行中
    const STATUS_STOP = 0; //已过期;

    /**
     * @param $userId int 用户id
     * @param $productId int 产品id
     * @param $type
     * @return bool
     */
    public function addInfo($userId, $productId, $type)
    {
        if ($type == self::TYPE_BUY) {
            //判断限制条件
        }
        $productModel = new Product();
        $product = $productModel->getInfoById($productId);
        if (!$product) {
            return false;
        }
        Db::startTrans();
        try {

            if (!$this->createInfo($product, $userId, $type)) {
                throw new \Exception('操作失败');
            }

            $productRate = $product->getRate();

            $user = User::where('id', $userId)->find();
            $user->product_rate = $productRate + $user->product_rate;

            if (!$user->save()) {
                throw new \Exception('操作失败');
            }

            Db::commit();
            return true;

        } catch (\Exception $e) {

            Db::rollback();
            return false;
        }
    }

    public function createInfo($product, $userId, $type)
    {
        $entity = new self();
        $entity->product_id = $product->id;
        $entity->product_number = $this->getProductNumber($userId, $product->id);
        $entity->buy_time = time();
        $entity->end_time = time() + $product->period * 24 * 3600;
        $entity->status = self::STATUS_RUNNING;
        $entity->user_id = $userId;
        $entity->total_day = $product->period;
        $entity->types = $type;

        return $entity->save();
    }

    public function getProductNumber($userId, $productId)
    {
        return substr(time() . $userId . $productId, 4);
    }

    /**
     * 查询出用户所拥有的魔盒数量
     */
    public function getBox($id, $product_id = '')
    {
        if($product_id)
        {
            return self::where("user_id", $id)->where("product_id", $product_id)->where('status', 1)->select();
        }
        else
        {
            return self::field('up.id product_number,up.total,up.buy_time,up.end_time, p.*, up.status, up.types')->alias('up')->leftJoin("product p", 'up.product_id = p.id')->where("user_id", $id)->select();
        }
    }
    public function getInfo($id,$userId){
        return self::field('up.*,p.product_name')->alias('up')->leftJoin("product p", 'up.product_id = p.id')->where(['up.id'=>$id,"user_id" => $userId])->find();
    }

    /**
     * 获取购买时间
     */
    public function getBuyTime()
    {
        return date("Y-m-d",$this->buy_time);
    }

    /**
     * 获取到期时间
     */
    public function getEndTime()
    {
        return date("Y-m-d", $this->end_time);
    }

    public function getType()
    {
        if($this->types == 1)
        {
            return '后台发放';
        }
        if($this->types == 2)
        {
            return '购买';
        }
        if($this->types == 3)
        {
            return '升级赠送';
        }
        if($this->types == 4)
        {
            return '购买赠送';
        }
    }
}