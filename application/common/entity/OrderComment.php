<?php
namespace app\common\entity;

use think\Db;
use think\Model;

class OrderComment extends Model
{
    protected $table = 'order_comment';

    public function addComment($point, $order)
    {
        Db::startTrans();
        try {

            $model = new self();
            $model->user_id = $order->user_id;
            $model->star = $point;
            $model->order_id = $order->id;
            $model->create_time = time();

            if (!$model->save()) {
                throw new \Exception('操作失败');
            }

            $order->is_comment = 1;
            if (!$order->save()) {
                throw new \Exception('操作失败');
            }

            //计算用户的好评度
            $userComment = $this->getUserComment($order->user_id);
            $result = User::where('id', $order->user_id)->setField('comment_rate', $userComment);

            if (!$result) {
                throw new \Exception('操作失败');
            }

            Db::commit();

            return true;

        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    public function getUserComment($userId)
    {
        $total = self::where('user_id', $userId)->sum('star');
        $count = self::where('user_id', $userId)->count();

        return round($total / ($count * 5) * 100,2);
    }
}