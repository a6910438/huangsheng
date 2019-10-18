<?php
namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\common\entity\Orders;
use app\common\entity\User;
use app\common\entity\LegalDeal;
use app\common\entity\LegalList;
use app\common\entity\LegalWallet;
use app\common\entity\LegalConfig;
use app\index\model\Market;
use think\Request;
use app\common\command\InitMenu;

class Legal extends Admin
{   

    /**
     * @power 法币交易|基础设置
     * @method POST
     */
    public function set(Request $request)
    {   	
    	
    		return $this->render('set', [
            'list' => LegalConfig::where('type', 1)->where('status',1)->select()
        ]);
    }

    /**
     * 添加设置
     */
    public function setadd(Request $request)
    {	
    	$config = new LegalConfig();
    	$config->name = $request->post('name');
        $config->key = $request->post('key');
        $config->value = $request->post('value');
    	
        if ($config->save() === false) {
            throw new AdminException('添加失败');
        }
        return ['code' => 0, 'message' => '添加成功'];
    }

    /**
     * 保存设置
     */
    public function setsave(Request $request)
    {
        $key = $request->post('key');
        $value = $request->post('value');
        $config = LegalConfig::where('key', $key)->find();
        if (!$config) {
            throw new AdminException('操作错误');
        }
        $config->value = $value;
        if ($config->save() === false) {
            throw new AdminException('修改失败');
        }
        return ['code' => 0, 'message' => '配置成功'];
    }

    /**
     * @power 法币交易|收购订单
     * @method POST
     */
    public function index(Request $request)
    {       
        $LegalList = new LegalList(); 
        $LegalWallet = new LegalWallet(); 
        $LegalDeal = new LegalDeal();


        //充值金额
        // $LegalWallet->setValue(1,'USDT',10000,'充值');

        //发布购买订单    
        // $totalPrice = bcmul(1000,6.66,8);
        // $result = $LegalList->add(10004,array('price'=>6.66,'number'=>1000,'totalprice'=>$totalPrice,'money_type'=>'USDT'),LegalList::TYPE_BUY);

        //我要出售
        // $totalPrice = bcmul(200,6.68,8); 
        // $result = $LegalList->buyList(1,array('number'=>200,'totalprice'=>$totalPrice,'id'=>1));

        //取消付款
        // $result = $LegalDeal->cancelPay(1,1);
         

        //确认付款
        // $result = $LegalDeal->checkPay(1,1,'alipay');


        //确认收款
        // $result = $LegalDeal->payResult(2,1);


        //错误订单 
        // $result = $LegalDeal->payError(2,1);

        
        //发布出售订单  
        // $totalPrice = bcmul(1200,6.68,8);  
        // $result = $LegalList->add(1,array('price'=>6.68,'number'=>1200,'totalprice'=>$totalPrice,'money_type'=>'USDT'),LegalList::TYPE_SALE);


        // 我要购买
        // $totalPrice = bcmul(150,6.67,8);
        // $result = $LegalList->buyList(1,array('number'=>150,'totalprice'=>$totalPrice,'id'=>3));


        //购买取消付款
        // $result = $LegalDeal->cancelPay(1,2);
         

        //购买确认付款
        // $result = $LegalDeal->checkPay(1,3,'alipay');


        //购买确认收款
        // $result = $LegalDeal->payResult(2,3);


        //错误订单 
        // $result = $LegalDeal->payError(2,1);

        // $change = bcadd(100, -12.25, 8);
            // echo "<pre>";
            //     print_r($change);
            // echo "</pre>";
            // exit;
            

        $list = $this->search($request, LegalList::TYPE_BUY);
        return $this->render('list', [
            'list' => $list,
            'type' => LegalList::TYPE_BUY
        ]);
    }

    /**
     * @power 法币交易|出售订单
     * @method POST
     */
    public function sale(Request $request)
    {     

        $list = $this->search($request, LegalList::TYPE_SALE);
        return $this->render('list', [
            'list' => $list,
            'type' => LegalList::TYPE_SALE
        ]);

    }

     /**
     * @power 法币交易|交易订单
     * @method POST
     */
    public function deal(Request $request)
    {     

        $list = $this->dealsearch($request);
        return $this->render('deal', [
            'list' => $list,
            'type' => 3
        ]);

    }

     /**
     * @power 法币交易|错误订单
     * @method POST
     */
    public function iserror(Request $request)
    {     

        $list = $this->dealsearch($request,-2);
        return $this->render('deal', [
            'list' => $list,
            'type' => 4
        ]);

    }

     /**
     * @power 法币交易|错误订单@确认收款
     * @method GET
     */
    public function update(Request $request)
    {
        if ($request->isPost()) {
            $id = $request->request('id');
            $order = LegalDeal::where('id', $id)->find();
            if (!$order) {
                throw new AdminException('订单不存在');
            }

            $result = $order->payResult($order->sale_id,$id);
            if (!$result) {
                throw new AdminException('操作失败');
            }

            return json(['code' => 0, 'message' => '确认成功']);
        }
    }

    /**
     * @power 法币交易|错误订单@取消付款
     * @method GET
     */
    public function cancel(Request $request)
    {
        if ($request->isPost()) {
            $id = $request->request('id');
            $order = LegalDeal::where('id', $id)->find();
            if (!$order) {
                throw new AdminException('订单不存在');
            }

            $result = $order->cancelPay($order->buy_id,$id);
            if (!$result) {
                throw new AdminException('操作失败');
            }

            return json(['code' => 0, 'message' => '取消成功']);
        }
    }

    protected function dealsearch($request,$status = '')
    {
        $query = LegalDeal::alias('o')->field('o.*,s.nick_name,s.mobile,b.nick_name as b_nick_name,b.mobile as b_mobile');
        
        $status = $status ? $status : $request->get('status');

        if ($status) {
            $query->where('o.status', $status);
            $map['status'] = $status;
        }else{
            $query->where('o.status','<>',-2);
        }

        if ($tid = $request->get('tid')) {
             $query->where('o.tid','like', $keyword);
        }

        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'sale':
                    $query->where('s.mobile', $keyword);
                    break;
                case 'buy':
                    $query->where('b.mobile', $keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');

        if($startTime && $endTime){
            $query->where('createtime', '<', strtotime($endTime))
            ->where('createtime', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }

        $userTable = (new User())->getTable();
        $list = $query->leftJoin("$userTable s", 's.id = o.sale_id')->leftJoin("$userTable b", 'b.id = o.buy_id')
            ->order('createtime', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        return $list;
    }

    protected function search($request, $type = '',$status = 1)
    {
        $query = LegalList::alias('o')->field('o.*,u.nick_name,u.mobile');
        if($type){
            $query->where('o.types', $type);
        }
        $status = $status ? $status : $request->get('status');
        if ($status) {
            $query->where('o.status', $status);
            $map['status'] = $status;
        }
        if ($keyword = $request->get('keyword')) {
            $type = $request->get('type');
            switch ($type) {
                case 'mobile':
                    $query->where('u.mobile','like', $keyword);
                    break;
                case 'tid':
                    $query->where('o.tid','like',$keyword);
                    break;
            }
            $map['type'] = $type;
            $map['keyword'] = $keyword;
        }
        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $query->where('finishtime', '<', strtotime($endTime))
            ->where('finishtime', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }

        $userTable = (new User())->getTable();
        $list = $query->leftJoin("$userTable u", 'u.id = o.user_id')
            ->order('createtime', 'desc')
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);
        return $list;
    }
}