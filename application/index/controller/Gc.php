<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/14
 * Time: 16:41
 */

namespace app\index\controller;

use app\common\entity\Deposit;
use app\common\entity\MyGcLog;
use app\common\entity\User;
use think\Db;
use app\common\model\SendSms;
use think\Log;

class Gc extends Base
{

    /**
     * 提币记录列表
     * @return \think\response\Json
     */
    public function list()
    {

        $uid = $this->userId;
        $page = input('post.page') ? input('post.page') : 1;
        $type = input('post.type') ? input('post.type') : 0;
        $limit = input('post.limit') ? input('post.limit') : 5;
        if ($type == 1) {//成功
            $map['type'] = 0;
        } elseif ($type == 2) {//失败
            $map['type'] = 1;
        }

        $map['uid'] = $uid;
        $info = MyGcLog::field('id,uid,amount,type,remark,create_time')
            ->where($map)
            ->order('id desc')
            ->page($page)
            ->paginate($limit)
            ->toArray();
        //预处理
        foreach ($info['data'] as $k => $v) {
            if ($v['type'] == 0) {
                $info['data'][$k]['type_des'] = '+';
            } elseif ($v['type'] == 1) {
                $info['data'][$k]['type_des'] = '-';
            }
        }
        //$info = $list;
        return ['code' => 0, 'msg' => 'access!', 'info' => $info];

    }


    /**
     * 添加订单流水接口
     */
    public function addorder()
    {
        if (!input('post.number') || !input('post.from') || !input('post.to') || !input('post.txid')
            || !input('post.height') || !input('post.create_time')) {
            return json(['code' => 1, 'msg' => '参数不足']);
        }
        $number = input('post.number');
        $to = input('post.to');
        $from = input('post.from');
        $txid = input('post.txid');
        $height = input('post.height') ? input('post.height') : 0;
        $create_time = input('post.create_time') ? input('post.create_time') : 0;
//        Log::Write("addorder入参 number:".$number." to:".$to." from:".from." txid:".txid);
        if (!preg_match("/^0x[a-zA-Z0-9]{40}$/", $to) || !preg_match("/^0x[a-zA-Z0-9]{40}$/", $from)) {
            return json(['code' => 1, 'msg' => "钱包地址格式不正确"]);
        }
        if ($txid == "") {
            return json(['code' => 1, 'msg' => "txid 不能为空"]);
        }
        if (!is_numeric($number) || $number <= 0) {
            return json(['code' => 1, 'msg' => '参数错误']);
        };
        $time = time();
        $user = Db::table('user')->where('gc_address', $to)->field('id,mobile')->find();
        Db::startTrans();
        //添加GC流水日志
        $new_gc_log = [
            'uid' => $user['id'],
            'amount' => $number,
            'type' => 0,
            'remark' => '充值成功',
            'create_time' => $time
        ];
        if (!MyGcLog::insert($new_gc_log)) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '提交流水日志失败!']);
        };
        $new_deposit_log = [
            'uid' => $user['id'],
            'from' => $from,
            'to' => $to,
            'number' => $number,
            'txid' => $txid,
            'height' => $height,
            'status' => 1,
            'currency' => "GC",
            'create_time' => $create_time,
            'update_time' => $time
        ];
        if (!Deposit::insert($new_deposit_log)) {
            Db::rollback();
            return json(['code' => 1, 'msg' => '提交流水日志失败!']);
        };
        //成功返回
        Db::commit();
        $send_sms = new SendSms();
        $send_sms->sendDepositSms($user['mobile'], $number, date('Y-m-d H:i:s', $time));
        return json(['code' => 0, 'msg' => 'Success！']);
    }


    private function updata_gc_wallet(){
        $gc = new \app\common\model\GC;
        $user_list = User::field(['id','gc_address','gc_last'])->where(['status'=>'1'])->select();
//        $time = time();
        foreach($user_list AS $user){
            if(!empty($user['gc_address'])){
                $redata = $gc->balance($user['gc_address']);
                $user['gc_last'] = round($user['gc_last'],4);
                if( $redata['code'] == 1 && $redata['balance'] > $user['gc_last'] ){
                    Log::Write("地址是：".$user['gc_address']);
                    $this->output->writeln("正在处理用户 : ".$user['id']);
                    $this->output->writeln("用户钱包地址 : ".$user['gc_address']);
                    $add_balance = $redata['balance'] - $user['gc_last'];
                    //添加GC流水日志
//                    $new_gc_log = [
//                        'uid'=>$user['id'],
//                        'amount'=>$add_balance,
//                        'type'=>0,
//                        'remark'=>'GC充值',
//                        'create_time'=>$time
//                    ];
                    Db::startTrans();
                    try {
                        //code...

                        if(
//                            !MyGcLog::insert($new_gc_log) ||
                            !User::where(['id'=>$user['id']])->update(['gc_last'=>$redata['balance']]) ||
                            !User::where(['id'=>$user['id']])->setInc('gc',$add_balance)
                        ){
                            Db::rollback();
                            $this->output->writeln("添加数量失败！");
                        };
                        Db::commit();
                    } catch (\Throwable $th) {
                        Db::rollback();
                        //throw $th;
                    }
                }else{
                    //$this->output->writeln("无可用的新数量！");
                }
            }else{
                //$this->output->writeln("无钱包地址！");
            }
        }
    }

}