<?php

namespace app\index\model;

use app\common\entity\MarketPrice;
use app\common\entity\LegalList;
use app\common\entity\LegalDeal;
use app\common\entity\LegalWallet;
use app\common\entity\LegalWalletLog;
use app\common\entity\User;
use app\common\entity\UserMagicLog;
use think\Db;

class Legal {

    /**
     * 获取列表
     */
    public function getList($data = array()) {
        $orderModel = new LegalList();
        $orderTable = $orderModel->getTable();
        $userModel = new User();
        $userTable = $userModel->getTable();

        $defaultStatus = LegalList::STATUS_DEFAULT;

        //类型 1购买（默认） 2出售 
        $type  = $data['type']??LegalList::TYPE_BUY;
        
        //交易钱币类型  无则不查  
        $money_type = $data['money_type']??'';

        //是否认证商家
        $is_certification = $data['business']??'';
        
        //页数
        $page = $data['page']??1;
        $psize = $data['psize']??10;

        $offset = ($page - 1) * $psize;

    $sql = <<<SQL
SELECT o.number,o.types,o.user_id,o.price,o.id,o.totalprice,u.nick_name,u.avatar,o.linetime,u.is_certification 
FROM {$orderTable} as o LEFT JOIN {$userTable} as u ON o.user_id=u.id WHERE 1
SQL;
    $sqls = <<<SQL
SELECT count(*) as total FROM {$orderTable} as o LEFT JOIN {$userTable} as u ON o.user_id=u.id WHERE 1
SQL;

        $cons = " AND o.status = {$defaultStatus} AND o.types={$type}";

        if($money_type){
            $cons .= " AND o.money_type = '{$money_type}' ";
        }

        if($is_certification){
            $cons .= " AND u.is_certification = {$is_certification} ";
        }

        $sqls .= $cons;

        $cons .= " ORDER BY o.price DESC, o.linetime DESC limit {$offset},{$psize}";
        
        $sql .= $cons;   

        $list = Db::query($sql);
        $total = Db::query($sqls);
          
        $data = [];
        if ($list) {
            foreach ($list as $key => $item) {
                $data[$key]['user_id'] = $item['user_id'];
                $data[$key]['nick_name'] = $item['nick_name'];
                $data[$key]['number'] = sprintf('%.4f', $item['number']);
                $data[$key]['order_id'] = $item['id'];
                $data[$key]['price'] = sprintf('%.2f', $item['price']);
                $data[$key]['totalprice'] = $item['totalprice'];
                $data[$key]['type'] = $item['types'];
            }
        }

         return ['data' => $data,'page'=> $page,'psize'=>$psize,'total'=>$total[0]['total']];
    }


    public function getListByPage($data = array()){
        $defaultStatus = LegalList::STATUS_DEFAULT;
        $psize = 20 ;
        $type  = isset($data['type'])?$data['type']:'';
        if(!$type){
            $type = LegalList::TYPE_BUY;
        }

        $money_type = isset($data['money_type'])?$data['money_type']:'';

        $field = 'o.number,o.types,o.user_id,o.price,o.id,o.totalprice,u.nick_name,u.avatar,o.linetime';
        $query = LegalList::alias('o')->field($field)->leftJoin("user u", 'o.user_id=u.id');
        
        if($money_type){
            $query->where('o.money_type',$money_type);
        }

        $query->where('o.status',$defaultStatus);
        $query->where('o.types', $type);

        $query->order("o.price DESC, o.linetime DESC");
        $list = $query->paginate($psize, false, [
            'fragment' => 'list_area'
        ]);
        $page = $list->render();

        $data = [];
        if ($list) {
            foreach ($list as $key => $item) {
                $data[$key]['user_id'] = $item->user_id;
                $data[$key]['nick_name'] = $item->nick_name;
                $data[$key]['number'] = sprintf('%.4f', $item->number);
                $data[$key]['order_id'] = $item->id;
                $data[$key]['price'] = sprintf('%.2f', $item->price);
                $data[$key]['totalprice'] = $item->totalprice;
                $data[$key]['type'] = $item->types;
            }
        }

        return ['data' => $data,'page'=> $page,'psize'=>$psize];
    }

    public function alert($message, $jumpUrl = '') {
       
        $html = <<<EOF

        <link href="/static/css/mui.min.css" rel="stylesheet" />
        <script src="/static/js/mui.min.js"></script>
        <script type="text/javascript" charset="utf-8">
            mui.init();
        </script>

        <script>
            window.onload = function(){
                mui.confirm('{$message}', '温馨提示', ['去查看',['取消']], function (e1) {
                    if (e1.index == 0) {
                       window.location.href = '{$jumpUrl}' ;
                    }
                })
            }
        </script>
EOF;
        echo $html;
    }


}
