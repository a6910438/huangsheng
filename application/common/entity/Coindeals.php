<?php
namespace app\common\entity;

use think\Db;
use think\Model;
use app\common\entity\Market;


class Coindeals extends Model
{
    protected $table = 'coindeal_order';

    const TYPE_BUY = 1; //买入订单
    const TYPE_SALE = 2; //卖出订单

    const STATUS_CREATE = 0; //生成
    const STATUS_MAKE = 1; //已成交

    const DEALWAY_0 = 0; //限价
    const DEALWAY_1 = 1; //市场


    public function getStatus()
    {
        switch ($this->status) {
            case self::STATUS_CREATE:
                return '等待交易';
            case self::STATUS_MAKE:
                return '交易成功';
            default:
                return '';
        }
    }


    public function getType()
    {
        switch ($this->types) {
            case self::TYPE_BUY:
                return '购买';
            case self::TYPE_SALE:
                return '出售';
            default:
                return '';
        }
    }


    public function getTime()
    {
        return date('Y-m-d H:i:s',$this->createtime);
    }

     public function getDealway()
    {
        switch ($this->dealway) {
            case self::DEALWAY_0:
                return '限价买入';
            case self::DEALWAY_1:
                return '市场买入';
            default:
                return '';
        }
    }


    protected function setOrderNumber($userId)
    {
        return 'LD'.date('Ymd') . $userId . date('His').rand(1000,9999);
    }



    public function add($userId, $data, $type = self::TYPE_BUY)
    {
        //定价
        if($data['dealway']==self::DEALWAY_0){
            $entity->number = $data['number'];
            $entity->remnant = $data['number'];
            $entity->price = $data['price'];
            $entity->sumprice = bcmul($data['number'],$data['price'],8);
        }else if($data['dealway']==self::DEALWAY_1){
        //市场

            //剩余未交易 等于价格
            if($type==TYPE_BUY){
                $entity->remnant = $data['price'];
                $entity->price = $data['price'];
            }else if($type==TYPE_SALE){
            //剩余未交易 等于数量
                $entity->number = $data['number'];
                $entity->remnant = $data['number'];
            }
            
        }

        $entity = new self();
        $entity->cdsn = $this->setOrderNumber($userId);
        $entity->user_id = $userId;
        $entity->types = $type;
        $entity->cointype = $data['cointype'];
        $entity->status = self::STATUS_CREATE;
        $entity->createtime = time();
        $entity->dealway = $data['dealway'];
        $entity->isshow = 0;


        Db::startTrans();

        try {

            $result = $entity->save(); 
            
            if (!$result) {
                throw new \Exception('操作失败');
            }

            $cdid = $entity->id;

            //扣钱

            //撮合交易
            if($type == self::TYPE_BUY){

                $this->buyBringTogether($cdid);

            }else if($type == self::TYPE_SALE){

                $this->saleBringTogether($cdid);
            }

            Db::commit();
            return true;

        } catch (\Exception $e) {

            Db::rollback();
            return true;
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
     * 会员买币撮合匹配交易
     */
    public function buyBringTogether($cdid)
    {

        if (!$cdid) {
            return false;
        }

        //查看该笔交易
        $order = self::where('id', $cdid)->find();

    
        //查看卖的 (先查出20条)
        $allord = self::where('types',self::TYPE_SALE)->where('cointype',$order['cointype'])->where("price <= {$order['price']}")->where('status',0)->where('remnant','>',0)->field('id,user_id,cointype,number,price,dealway,createtime,types,remnant')->order('id', 'asc')->limit(20)->select();


        //如果查不到
        if(empty(count($allord))){
            return;
        }

        //买入数量
        $number = $order['remnant'];

        foreach ($allord as $value) {

            //相减
            $differ = bcsub($value['remnant'],$number,8);

            $makenum = $differ>=0?$number:$value['remnant'];

            //卖的足够抵消 剩余买的
            if($makenum > 0){
        
                //处理
                //如果当前记录大于剩余处理数量
                if($differ>=0){

                    Db::startTrans();
                    try {
                        //先处理买的记录 (插入为成功) 
                        //更新为成功 （买的）
                        self::where('id',$order['id'])->update(['remnant' => 0,'status'=>1,'maketime'=>time()]);

                        //增加记录
                        $newbuyarr = [
                            'cdsn'=>$this->setOrderNumber($order['user_id']),
                            'user_id'=>$order['user_id'],
                            'deal_id'=>$value['user_id'],
                            'types'=>$order['types'],
                            'cointype'=>$order['cointype'],
                            'number'=>$makenum,
                            'remnant'=>0,
                            'price'=>$order['price'],
                            'status'=>self::STATUS_MAKE,
                            'createtime'=>$order['createtime'],
                            'maketime'=>time(),
                            'makeprice'=>$value['price'],
                            'makesumprice'=>bcmul($makenum,$value['price'],8),
                            'dealway'=>$order['dealway'],
                            'orderid'=>$order['id'],
                            'makeid'=>$value['id'],
                            'isshow'=>1,
                        ];

                        $buyentity = new self();
                        $buyentity->save($newbuyarr);
                        
                        //市值变动
                        $Market = new Market();
                        $Market->coinMarket($order['cointype'],$value['price']);


                        //买家退钱区间


                        //计算剩余 没处理
                        $salenum = bcsub($value['remnant'],$makenum,8);
                        $update['remnant'] = $salenum;

                        if($salenum==0){
                            $update['status'] = 1;
                            $update['maketime'] = time();
                        }

                        //处理卖的 （当前记录数量要减掉）
                        self::where('id',$value['id'])->update($update);

                        //增加记录
                        $newarr = [
                            'cdsn'=>$this->setOrderNumber($value['user_id']),
                            'user_id'=>$value['user_id'],
                            'deal_id'=>$order['user_id'],
                            'types'=>$value['types'],
                            'cointype'=>$value['cointype'],
                            'number'=>$makenum,
                            'remnant'=>0,
                            'price'=>$value['price'],
                            'status'=>self::STATUS_MAKE,
                            'createtime'=>$value['createtime'],
                            'maketime'=>time(),
                            'makeprice'=>$value['price'],
                            'makesumprice'=>bcmul($makenum,$value['price'],8),
                            'dealway'=>$value['dealway'],
                            'orderid'=>$value['id'],
                            'makeid'=>$order['id'],
                            'isshow'=>1,
                        ];

                        $entity = new self();
                        $entity->save($newarr);

                        //卖家加钱



                        Db::commit();
                        $number = bcsub($number,$makenum,8);

                    } catch (\Exception $e) {
                      
                        Db::rollback();
                    }


                }else{
                //卖的小于 剩余买的的情况
                
                    Db::startTrans();
                    try {

                        //先处理卖的记录 (插入为成功) 
                        //更新为成功 （卖的）
                        self::where('id',$value['id'])->update(['remnant' => 0,'status'=>1,'maketime'=>time()]);

                        //增加记录
                        $newbuyarr = [
                            'cdsn'=>$this->setOrderNumber($value['user_id']),
                            'user_id'=>$value['user_id'],
                            'deal_id'=>$order['user_id'],
                            'types'=>$value['types'],
                            'cointype'=>$value['cointype'],
                            'number'=>$makenum,
                            'remnant'=>0,
                            'price'=>$value['price'],
                            'status'=>self::STATUS_MAKE,
                            'createtime'=>$value['createtime'],
                            'maketime'=>time(),
                            'makeprice'=>$value['price'],
                            'makesumprice'=>bcmul($makenum,$value['price'],8),
                            'dealway'=>$value['dealway'],
                            'orderid'=>$value['id'],
                            'makeid'=>$order['id'],
                            'isshow'=>1,
                        ];

                        $buyentity = new self();
                        $buyentity->save($newbuyarr);

                        //市值变动
                        $Market = new Market();
                        $Market->coinMarket($order['cointype'],$value['price']);



                        //卖家加钱


                        //计算剩余 没处理买部分
                        $salenum = bcsub($number,$makenum,8);

                        //处理的 （当前记录数量要减掉）
                        self::where('id',$order['id'])->update(['remnant' => $salenum]);

                        //增加记录
                        $newarr = [
                            'cdsn'=>$this->setOrderNumber($order['user_id']),
                            'user_id'=>$order['user_id'],
                            'deal_id'=>$value['user_id'],
                            'types'=>$order['types'],
                            'cointype'=>$order['cointype'],
                            'number'=>$makenum,
                            'remnant'=>0,
                            'price'=>$order['price'],
                            'status'=>self::STATUS_MAKE,
                            'createtime'=>$order['createtime'],
                            'maketime'=>time(),
                            'makeprice'=>$order['price'],
                            'makesumprice'=>bcmul($makenum,$order['price'],8),
                            'dealway'=>$order['dealway'],
                            'orderid'=>$order['id'],
                            'makeid'=>$value['id'],
                            'isshow'=>1,
                        ];

                        $entity = new self();
                        $entity->save($newarr);


                        //买家退钱区间


                        Db::commit();
                        $number = bcsub($number,$makenum,8);

                    } catch (\Exception $e) {
                      
                        Db::rollback();
                    }

                }


            }else{
                 continue;
            }
        }
        unset($value);


        //如果买的数量还大于 0 的话
        if($number > 0){
            //回归处理
            $this->buyBringTogether($cdid);
        }


    }



     /**
     * 会员卖币撮合匹配交易
     */
    public function saleBringTogether($cdid)
    {

        if (!$cdid) {
            return false;
        }

        //查看该笔交易
        $order = self::where('id', $cdid)->find();


        //查看买的 (先查出20条)
        $allord = self::where('types',self::TYPE_BUY)->where('cointype',$order['cointype'])->where("price >= {$order['price']}")->where('status',0)->where('remnant','>',0)->field('id,user_id,cointype,number,price,dealway,createtime,types,remnant')->order('id', 'asc')->limit(20)->select();
        // echo '<pre>';
        // print_r($allord);exit;

        //如果查不到
        if(empty(count($allord))){
            return;
        }

        //卖出数量
        $number = $order['remnant'];


        foreach ($allord as $value) {

            //相减
            $differ = bcsub($value['remnant'],$number,8);

            $makenum = $differ>=0?$number:$value['remnant'];

            //买的足够抵消 剩余卖的
            if($makenum > 0){

                //处理
                //如果当前记录大于剩余处理数量
                if($differ>=0){

                    Db::startTrans();
                    try {

                        //先处理卖的记录 (插入为成功) 
                        //更新为成功 （卖的）
                        self::where('id',$order['id'])->update(['remnant' => 0,'status'=>1,'maketime'=>time()]);

                        //增加记录
                        $newbuyarr = [
                            'cdsn'=>$this->setOrderNumber($order['user_id']),
                            'user_id'=>$order['user_id'],
                            'deal_id'=>$value['user_id'],
                            'types'=>$order['types'],
                            'cointype'=>$order['cointype'],
                            'number'=>$makenum,
                            'remnant'=>0,
                            'price'=>$order['price'],
                            'status'=>self::STATUS_MAKE,
                            'createtime'=>$order['createtime'],
                            'maketime'=>time(),
                            'makeprice'=>$order['price'],
                            'makesumprice'=>bcmul($makenum,$order['price'],8),
                            'dealway'=>$order['dealway'],
                            'orderid'=>$order['id'],
                            'makeid'=>$value['id'],
                            'isshow'=>1,
                        ];

                        $buyentity = new self();
                        $buyentity->save($newbuyarr);
                        
                        //市值变动
                        $Market = new Market();
                        $Market->coinMarket($order['cointype'],$order['price']);


                        //卖家加钱区间



                        //计算剩余 没处理
                        $salenum = bcsub($value['remnant'],$makenum,8);

                        $update['remnant'] = $salenum;

                        if($salenum==0){
                            $update['status'] = 1;
                            $update['maketime'] = time();
                        }

                        //处理买的 （当前记录数量要减掉）
                        self::where('id',$value['id'])->update($update);

                        //增加记录
                        $newarr = [
                            'cdsn'=>$this->setOrderNumber($value['user_id']),
                            'user_id'=>$value['user_id'],
                            'deal_id'=>$order['user_id'],
                            'types'=>$value['types'],
                            'cointype'=>$value['cointype'],
                            'number'=>$makenum,
                            'remnant'=>0,
                            'price'=>$value['price'],
                            'status'=>self::STATUS_MAKE,
                            'createtime'=>$value['createtime'],
                            'maketime'=>time(),
                            'makeprice'=>$order['price'],
                            'makesumprice'=>bcmul($makenum,$order['price'],8),
                            'dealway'=>$value['dealway'],
                            'orderid'=>$value['id'],
                            'makeid'=>$order['id'],
                            'isshow'=>1,
                        ];

                        $entity = new self();
                        $entity->save($newarr);
                        //买家退钱区间


                        Db::commit();
                        $number = bcsub($number,$makenum,8);

                    } catch (\Exception $e) {
                        Db::rollback();
                    }


                }else{
                //买的小于 剩余卖的的情况

                    Db::startTrans();
                    try {

                        //先处理买的记录 (插入为成功) 
                        //更新为成功 （买的）
                        self::where('id',$value['id'])->update(['remnant' => 0,'status'=>1,'maketime'=>time()]);

                        //增加记录
                        $newbuyarr = [
                            'cdsn'=>$this->setOrderNumber($value['user_id']),
                            'user_id'=>$value['user_id'],
                            'deal_id'=>$order['user_id'],
                            'types'=>$value['types'],
                            'cointype'=>$value['cointype'],
                            'number'=>$makenum,
                            'remnant'=>0,
                            'price'=>$value['price'],
                            'status'=>self::STATUS_MAKE,
                            'createtime'=>$value['createtime'],
                            'maketime'=>time(),
                            'makeprice'=>$order['price'],
                            'makesumprice'=>bcmul($makenum,$order['price'],8),
                            'dealway'=>$value['dealway'],
                            'orderid'=>$value['id'],
                            'makeid'=>$order['id'],
                            'isshow'=>1,
                        ];

                        $buyentity = new self();
                        $buyentity->save($newbuyarr);

                        //市值变动
                        $Market = new Market();
                        $Market->coinMarket($order['cointype'],$order['price']);


                        //退还买的钱


                        //计算剩余 没处理买部分
                        $salenum = bcsub($number,$makenum,8);

                        //处理的 （当前记录数量要减掉）
                        self::where('id',$order['id'])->update(['remnant' => $salenum]);

                        //增加记录
                        $newarr = [
                            'cdsn'=>$this->setOrderNumber($order['user_id']),
                            'user_id'=>$order['user_id'],
                            'deal_id'=>$value['user_id'],
                            'types'=>$order['types'],
                            'cointype'=>$order['cointype'],
                            'number'=>$makenum,
                            'remnant'=>0,
                            'price'=>$order['price'],
                            'status'=>self::STATUS_MAKE,
                            'createtime'=>$order['createtime'],
                            'maketime'=>time(),
                            'makeprice'=>$order['price'],
                            'makesumprice'=>bcmul($makenum,$order['price'],8),
                            'dealway'=>$order['dealway'],
                            'orderid'=>$order['id'],
                            'makeid'=>$value['id'],
                            'isshow'=>1,
                        ];

                        $entity = new self();
                        $entity->save($newarr);


                        //卖家加钱区间


                        Db::commit();
                        $number = bcsub($number,$makenum,8);

                    } catch (\Exception $e) {
                        Db::rollback();
                    }

                }


            }else{
                 continue;
            }
        }
        unset($value);


        //如果买的数量还大于 0 的话
        if($number > 0){
            //回归处理
            $this->saleBringTogether($cdid);
        }


    }


  




}
