<?php

namespace app\index\controller;

use app\common\entity\Dynamic_Log;
use app\common\entity\StoreLog;
use app\common\entity\User;
use think\Controller;
use think\Db;

class T extends Controller {

    public function test()
    {
        $list = Dynamic_Log::where('status',1)->where('update_time',null)->limit(1000)->select();
        if(!empty($list)){
            foreach ($list as $k => $v){
                if($v['open_time'] <= time()){
                    Db::startTrans();
                    $res = Dynamic_Log::where('id',$v['id'])->update([
                        'status' => 2,
                        'update_time' => time(),
                    ]);
                    if(!$res){
                        Db::rollback();
                    }
                    $user_res = \app\common\entity\MyWallet::where('id',$v['uid'])->setInc('now',$v['total']);
                    if(!$user_res){
                        Db::rollback();
                    }
                    $store_res = StoreLog::where('id',$v['store_id'])->update([
                        'you_open_time' => time(),
                        'you_status' => 2,
                    ]);
                    if(!$store_res){
                        Db::rollback();
                    }
                    echo 'OK';
                    Db::commit();
                }else{
                    echo '无数据';
                }
            }
        }
    }


}
