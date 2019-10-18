<?php
namespace app\index\controller;

use app\common\entity\Config;
use app\common\entity\OrderComment;
use app\common\entity\Orders;
use app\common\entity\User;
use app\common\service\Market\Auth;
use app\index\model\Market;
use think\Request;
use think\Response;

class Trade extends Base
{
    public function initialize()
    {
        $authModel = new Auth();
        $authModel->identity();
        parent::initialize();
    }

    /**
     * 交易中
     */
    public function index()
    {
        $model = new \app\index\model\Trade();
        $buy = $model->getBuyList($this->userId);
        $sale = $model->getSaleList($this->userId);
        return $this->fetch('index', [
            'buy' => $buy,
            'sale' => $sale
        ]);
    }

    public function detail(Request $request)
    {
        $id = intval($request->get('id'));
        $type = trim($request->get('type')); //pay confirm

        $order = Orders::where('id', $id)->find();
        if (!$order) {
            $this->redirect('trade/index');
        }
        if (!in_array($type, ['pay', 'confirm'])) {
            $this->redirect('trade/index');
        }

        if ($order->user_id != $this->userId && $order->target_user_id != $this->userId) {
            $this->redirect('trade/index');
        }

        if ($order->user_id == $this->userId) {
            $userInfo = User::where('id', $order->target_user_id)->find();
        }

        if ($order->target_user_id == $this->userId) {
            $userInfo = User::where('id', $order->user_id)->find();
        }

        return $this->fetch('detail', [
            'order' => $order,
            'type' => $type,
            'userInfo' => $userInfo
        ]);
    }

    /**
     * 确定付款
     */
    public function pay(Request $request)
    {
        if ($request->isPost()) {
            $id = intval($request->post('orderId'));

            $order = Orders::where('id', $id)->find();
            if (!$order) {
                return json(['code' => 1, 'msg ' => '操作错误']);
            }
            if ($order->status != Orders::STATUS_PAY) {
                return json(['code' => 1, 'msg ' => '操作错误']);
            }
            if ($order->user_id != $this->userId && $order->target_user_id != $this->userId) {
                return json(['code' => 1, 'msg ' => '操作错误']);
            }
            if (empty($order->image)) {
                return json(['code' => 1, 'msg ' => '请先上传打款凭据截图']);
            }
            $order->status = Orders::STATUS_CONFIRM;
            $order->pay_time = time();

            if ($order->save()) {
                return json(['code' => 0, 'msg' => '操作成功']);
            }
            return json(['code' => 1, 'msg' => '操作失败，请联系管理员处理']);
        }
    }

    /**
     * 确定已收款
     */
    public function confirm(Request $request)
    {
        if ($request->isPost()) {
            $id = intval($request->post('orderId'));

            $order = Orders::where('id', $id)->find();
            if (!$order) {
                return json(['code' => 1, 'msg ' => '操作错误']);
            }
            if ($order->status != Orders::STATUS_CONFIRM) {
                return json(['code' => 1, 'msg ' => '操作错误']);
            }
            if ($order->user_id != $this->userId && $order->target_user_id != $this->userId) {
                return json(['code' => 1, 'msg ' => '操作错误']);
            }

            $result = $order->confirm();

            if ($result) {
                return json(['code' => 0, 'msg' => '操作成功']);
            }
            return json(['code' => 1, 'msg' => '操作失败，请联系管理员处理']);
        }
    }

    /**
     * 买入方取消交易,返回出售方法的钱，删除订单
     */
    public function cancelTrade(Request $request)
    {
        if ($request->isPost()) {
            $id = intval($request->post('orderId'));

            $order = Orders::where('id', $id)->find();
            if (!$order) {
                return json(['code' => 1, 'msg ' => '操作错误']);
            }
            if ($order->status != Orders::STATUS_PAY) {
                return json(['code' => 1, 'msg ' => '操作错误']);
            }
            if ($order->user_id != $this->userId && $order->target_user_id != $this->userId) {
                return json(['code' => 1, 'msg ' => '操作错误']);
            }

            $result = $order->cancel();

            if ($result) {
                return json(['code' => 0, 'msg' => '取消成功']);
            }
            return json(['code' => 1, 'msg' => '取消失败，请联系管理员处理']);
        }
    }


    /**
     * 卖出
     */
    public function sale()
    {
        $order = Orders::where('user_id', $this->userId)
            ->where('status', Orders::STATUS_DEFAULT)
            ->where('types', Orders::TYPE_SALE)
            ->find();
        return $this->fetch('sale', [
            'order' => $order
        ]);
    }

    /**
     * @return mixed 买入
     */
    public function buy()
    {
        $order = Orders::where('user_id', $this->userId)
            ->where('status', Orders::STATUS_DEFAULT)
            ->where('types', Orders::TYPE_BUY)
            ->find();
        return $this->fetch('buy', [
            'order' => $order
        ]);
    }

    /**
     * 取消
     */
    public function cancel(Request $request)
    {
        if ($request->isPost()) {
            $type = $request->post('type');
            $id = $request->post('orderId');

            $order = Orders::where('id', $id)->find();
            if (!$order) {
                return json(['code' => 1, 'message' => '对象不存在']);
            }

            if ($order->user_id != $this->userId) {
                return json(['code' => 1, 'message' => '请求错误']);
            }

            if ($order->status != Orders::STATUS_DEFAULT) {
                return json(['code' => 1, 'message' => '订单已在交易中，请在交易中去继续操作']);
            }

            $service = new Market();
            switch ($type) {
                case 'buy':
                    $result = $service->cancelBuy($order);
                    break;
                case 'sale':
                    $result = $service->cancelSale($order);
                    break;
                default:
                    return json(['code' => 1, 'message' => '请求错误']);
            }

            if ($result) {
                return json(['code' => 0, 'message' => '取消成功']);
            }

            return json(['code' => 1, 'message' => '取消失败']);
        }
    }

    /**
     * 已完成
     */
    public function finish()
    {
        $model = new \app\index\model\Trade();

        return $this->fetch('finish', [
            'list' => $model->getFinishList($this->userId),
            'ownId' => $this->userId
        ]);
    }

    public function fdetail(Request $request)
    {
        $id = intval($request->get('id'));

        $order = Orders::where('id', $id)->find();
        if (!$order) {
            $this->redirect('trade/finish');
        }

        if ($order->user_id == $this->userId) {
            $userInfo = User::where('id', $order->target_user_id)->find();
        }

        if ($order->target_user_id == $this->userId) {
            $userInfo = User::where('id', $order->user_id)->find();
        }

        return $this->fetch('fdetail', [
            'order' => $order,
            'userInfo' => $userInfo,
            'ownId' => $this->userId
        ]);
    }

    public function comment(Request $request)
    {
        if ($request->isPost()) {
            $id = intval($request->post('orderId'));
            $point = intval($request->post('point'));

            $order = Orders::where('id', $id)->find();
            if (!$order) {
                return json(['code' => 0, 'message' => '评论失败']);
            }

            $model = new OrderComment();
            $result = $model->addComment($point, $order);

            if ($result) {
                return json(['code' => 0, 'message' => '评论成功']);
            }
            return json(['code' => 1, 'message' => '评论失败']);
        }

    }

}