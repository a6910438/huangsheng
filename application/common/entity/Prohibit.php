<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class Prohibit extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'prohibit_log';

    
    //后台 充值/扣款
    public function RechargeLog($data)
    {

        Db::startTrans();
        try {
           $info = DB::table('user')->where('id',$data['uid'])->find();
            $create_data = [
                'uid' => $data['uid'],
                'number' => $data['num'],   //交易数量
                'new' => bcadd($info['prohibit_integral'],$data['num'],5),   //交易前
                'old' => $info['prohibit_integral']  , //交易之后
                'createtime' => time(),
                'type' =>$data['type'],
            ];
            $res2 = DB::table('prohibit_log')->insert($create_data);
            if($data['num'] > 0){
                DB::table('user')->where('id',$data['uid'])->setInc('prohibit_integral',$data['num']);
            }
            if (!$res2) {
                return false;
            }
           $res3 = DB::table('user')->where('id',$data['uid'])->setInc('now_prohibit_integral',$data['num']);

            if(!$res3){
                return false;
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

}
