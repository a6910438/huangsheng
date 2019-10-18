<?php

namespace app\index\model;

use app\common\entity\MarketPrice;
use app\common\entity\Orders;
use app\common\entity\User;
use app\common\entity\UserMagicLog;
use think\Db;

class Market {

    /**
     * 买入
     */
    public function buy($price, $number, $userId) {

        if ($this->checkOrder($userId)) {
            throw new \Exception('你还有交易未完成，请先去完成交易');
        }
        $model = new \app\common\entity\Orders();
        $result = $model->add($userId, [
            'price' => $price,
            'number' => $number
                ], Orders::TYPE_BUY);
        if (!$result) {
            throw new \Exception('买入失败');
        }
    }

    /**
     * 卖出
     */
    public function sale($price, $number, $userId) {
        $order = new Orders();
        $entity = $order->add($userId, [
            'price' => $price,
            'number' => $number
                ], Orders::TYPE_SALE);

        if (!$entity) {
            return false;
        }

        $userInfo = User::where('id', $userId)->find();
        if (!$userInfo) {
            return false;
        }

        $model = new UserMagicLog();
        $result = $model->changeUserMagic($userInfo, [
            'magic' => $number + $entity->charge_number,
            'remark' => '卖出交易',
            'type' => UserMagicLog::TYPE_ORDER
                ], -1);

        return $result;
    }

    /**
     * 买ta
     */
    public function buyTa($orderId, $userId) {
        $order = Orders::where('id', $orderId)->find();
        if (!$order || $order->types != Orders::TYPE_SALE) {
            throw new \Exception('对象不存在');
        }
        if ($order->status != Orders::STATUS_DEFAULT) {
            throw new \Exception('订单已被别人买入了');
        }
        if ($order->user_id == $userId) {
            throw new \Exception('自己的订单不能买入哦');
        }

        if ($this->checkOrder($userId)) {
            throw new \Exception('你还有交易未完成');
        }

        $order->status = Orders::STATUS_PAY;
        $order->target_user_id = $userId;
        $order->match_time = time();

        if (!$order->save()) {
            throw new \Exception('买入失败');
        }
        return $order;
    }

    /**
     * 卖ta
     */
    public function saleTa($orderId, $userId) {
        $order = Orders::where('id', $orderId)->find();

        $userInfo = User::where('id', $userId)->find();
        if (!$userInfo) {
            return false;
        }

        $model = new UserMagicLog();
        $result = $model->changeUserMagic($userInfo, [
            'magic' => $order->number + $order->charge_number,
            'remark' => '卖出交易',
            'type' => UserMagicLog::TYPE_ORDER
                ], -1);

        if ($result) {
            $order->status = Orders::STATUS_PAY;
            $order->status = Orders::STATUS_PAY;
            $order->target_user_id = $userId;
            $order->match_time = time();

            $order->save();

            return true;
        }

        return false;
    }

    /**
     * 卖ta 验证
     */
    public function checkSaleTa($orderId, $userId) {
        $order = Orders::where('id', $orderId)->find();
        if (!$order || $order->types != Orders::TYPE_BUY) {
            throw new \Exception('对象不存在');
        }
        if ($order->status != Orders::STATUS_DEFAULT) {
            throw new \Exception('订单已被别人出售了');
        }
        if ($order->user_id == $userId) {
            throw new \Exception('自己的订单不能出售哦');
        }

        if ($this->checkOrder($userId)) {
            throw new \Exception('你还有交易未完成');
        }

        $user = User::where('id', $userId)->find();
        if ($user->magic < bcadd($order->number, $order->charge_number, 8)) {
            throw new \Exception(sprintf('剩余金币不足哦，需要%s手续费', $order->charge_number));
        }
    }

    /**
     * 买入取消
     */
    public function cancelBuy($order) {
        return $order->delete();
    }

    /**
     * 卖出取消,退回会员的魔石和手续费
     */
    public function cancelSale($order) {
        $userInfo = User::where('id', $order->user_id)->find();
        if (!$userInfo) {
            return false;
        }

        $model = new UserMagicLog();
        $result = $model->changeUserMagic($userInfo, [
            'magic' => $order->number + $order->charge_number,
            'remark' => '取消卖出交易',
            'type' => UserMagicLog::TYPE_ORDER
                ], 1);

        if ($result) {
            $order->delete();
            return true;
        }
        return false;
    }

    /**
     * 判断用户是否还有交易没完成
     */
    public function checkOrder($userId) {
        $total = Orders::where('user_id|target_user_id', $userId)
                ->where('status', 'in', [Orders::STATUS_DEFAULT, Orders::STATUS_PAY, Orders::STATUS_CONFIRM])
                ->count();
        return $total >= 1 ? true : false;
    }

    /**
     * 获取列表
     */
    public function getList($type, $userId, $page = 1, $limit = 20, $mobile = '') {
        $orderModel = new Orders();
        $orderTable = $orderModel->getTable();
        $userModel = new User();
        $userTable = $userModel->getTable();

        $finishStatus = Orders::STATUS_FINISH;
        $defaultStatus = Orders::STATUS_DEFAULT;

        $offset = ($page - 1) * $limit;

        $sql = <<<SQL
SELECT o.number,o.user_id,o.price,o.id,o.total_price,o.total_price_china,u.nick_name,u.avatar,u.comment_rate,
(SELECT count(*) FROM {$orderTable} where user_id = o.user_id and status = {$finishStatus} limit 1) as finish
FROM {$orderTable} as o LEFT JOIN {$userTable} as u ON o.user_id=u.id WHERE o.status ={$defaultStatus} AND
o.types={$type} AND user_id<>{$userId}
SQL;
        if ($mobile) {
            $sql .= " AND u.mobile={$mobile} ";
        }
        $sql .= " ORDER BY is_top DESC, o.create_time DESC limit {$offset},{$limit}";

        $list = Db::query($sql);

        $data = [];
        if ($list) {
            foreach ($list as $key => $item) {
                $data[$key]['avatar'] = $item['avatar'] ? $item['avatar'] : '/static/img/headphoto.png';
                $data[$key]['nick_name'] = $item['nick_name'];
                $data[$key]['number'] = $item['number'];
                $data[$key]['order_id'] = $item['id'];
                $data[$key]['price'] = sprintf('%.2f', $item['price']);
                $data[$key]['finish'] = $item['finish'] ? $item['finish'] : 0;
                $data[$key]['comment'] = $item['comment_rate'];
                $data[$key]['china_price'] = $item['total_price_china'];
                $data[$key]['total_money'] = $item['total_price'];
            }
        }

        return $data;
    }

    public function getListByPage($userId, $mobile = ''){
        $defaultStatus = Orders::STATUS_DEFAULT;
        $field = 'o.number,o.types,o.user_id,o.price,o.id,o.total_price,o.total_price_china,u.nick_name,u.avatar,u.comment_rate';
        $query = Orders::alias('o')->field($field)->leftJoin("user u", 'o.user_id=u.id');
        $query->where('o.status',$defaultStatus);
//        $query->where('user_id','<>',$userId);
        if ($mobile) {
            $query->where('u.mobile',$mobile);
        }
        $query->order("is_top DESC, o.create_time DESC");
        $list = $query->paginate(30, false, [
            'fragment' => 'list_area'
        ]);
        $page = $list->render();

        $data = [];
        if ($list) {
            foreach ($list as $key => $item) {
                $data[$key]['user_id'] = $item->user_id;
                $data[$key]['nick_name'] = $item->nick_name;
                $data[$key]['number'] = $item->number;
                $data[$key]['order_id'] = $item->id;
                $data[$key]['price'] = sprintf('%.3f', $item->price);
                $data[$key]['comment'] =$item->comment_rate;
                $data[$key]['china_price'] = $item->total_price_china;
                $data[$key]['total_money'] = $item->total_price;
                $data[$key]['type'] = $item->types;
            }
        }

        return ['data' => $data,'page'=> $page];
    }

}
