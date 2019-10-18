<?php
namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\common\entity\User;
use app\common\entity\Coindeals;
use app\common\entity\Market;
use think\Request;
use app\common\command\InitMenu;

class Coindeal extends Admin
{   


    /**
     * @power 币币交易|订单记录@列表
     */
    public function index(Request $request)
    {     
        $type='';

        $reality = !empty($request->get('reality'))?intval($request->get('reality')):'';
        if($reality){
            $list = $this->search($request,'',1,1,'');
        }else{
            $type = !empty($request->get('types'))?intval($request->get('types')):1;
            $list = $this->search($request,$type);
        }
        // echo '<pre>';
        // print_r($list);

        return $this->render('list', [
            'list' => $list,
            'type' => $type,
            'reality' => $reality,
        ]);

    }

    /**
     * @power 币币交易|订单记录@明细
     */
    public function detail(Request $request)
    {      
        $id = !empty($request->get('id'))?intval($request->get('id')):0;

        $order = Coindeals::where('id',$id)->find();
        $list = $this->search($request,'',1,1,$id);

        return $this->render('detail', [
            'list' => $list,
            'order'=>$order
        ]);

    }



     /**
     * @power 币币交易|交易币涨跌
     */
    public function market(Request $request)
    {     
       
        $list = $this->searchMarket($request);

        // Coindeals::where('isshow',0)->where('remnant',0)->update(['status' => 1]);

        // echo '<pre>';
        // print_r($list);
        return $this->render('market', [
            'list' => $list
        ]);

    }





    /**
     * @power 币币交易|设置
     */
    public function set(Request $request)
    {
    	$Coindeals = new Coindeals();
    	$data = ['cointype'=>'btc','number'=>20,'price'=>63.36355454,'dealway'=>0];
        $Coindeals->add(10,$data,1);
    }



     /**
     * 	$request 参数
     * 	$type   买卖类型
     * 	$isshow 是否真实订单
     * 	$status 状态
     * 	$orderid 关联订单ID
     */
    protected function search($request, $type = '',$isshow = 0,$status='',$orderid=0)
    {
    
        $query = Coindeals::alias('o')->where('isshow',$isshow)->field('o.*,u.nick_name,u.mobile');
        $map['isshow'] = $isshow;

        if($request->get('reality')==1){
        	$map['reality'] = 1;
        }

        if($type){
            $query->where('o.types', $type);
            $map['types'] = $type;
        }

        if($orderid){
            $query->where('o.orderid', $orderid);
            $map['orderid'] = $orderid;
        }

    
        if ($status || $request->get('status')>0 ) {
            $status = $status ? $status : $request->get('status')-1;

            $query->where('o.status', $status);
            $map['status'] = $status;
        }

        if ($cdsn = $request->get('cdsn')) {
            $query->where('o.cdsn', $cdsn);
            $map['cdsn'] = $cdsn;
        }

        if ($keyword = $request->get('keyword')) {
            $query->where('u.mobile', $keyword);
            $map['mobile'] = $keyword;
        }

        $startTime = $request->get('startTime');
        $endTime = $request->get('endTime');
        if($startTime && $endTime){
            $query->where('finish_time', '<', strtotime($endTime))
            ->where('finish_time', '>=', strtotime($startTime));
            $map['startTime'] = $startTime;
            $map['endTime'] = $endTime;
        }

        $userTable = (new User())->getTable();
        $list = $query->leftJoin("$userTable u", 'u.id = o.user_id')
            ->order('id', 'desc')
            ->paginate(20, false, [
                'query' => isset($map) ? $map : []
            ]);
        return $list;
    }



    protected function searchMarket($request, $cointype = '')
    {
   
        $query = Market::alias('o')->field('o.*');

       	$cointype = $cointype ? $cointype : $request->get('cointype');

        if ($cointype) {
            $query->where('o.cointype', $cointype);
            $map['cointype'] = $cointype;
        }
       
        $list = $query->order('id', 'desc')
            ->paginate(20, false, [
                'query' => isset($map) ? $map : []
            ]);
        return $list;
    }



}
