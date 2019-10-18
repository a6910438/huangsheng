<?php

namespace app\index\controller;

use app\common\entity\MoneyRate;
use app\common\entity\Orders;
use app\common\entity\Recharge;
use app\common\entity\User;
use app\common\entity\WalletAddressConfig;
use think\Request;
use service\IndexLog;


class Access extends Base {

    /**
     * 贸易首页
     */
    public function index(Request $request)
    {
        $list = User::alias('u')
            ->field('u.nick_name,mw.*')
            ->leftJoin('my_wallet mw','mw.uid = u.id')
            ->where('u.id',$this->userId)
            ->find();
        IndexLog::write('资产', '用户获取资产详情');
        return json()->data(['code'=>0,'msg'=>'Request successful','info'=>$list]);
    }
    /**
     * 充排单币
     */
    public function recharge(Request $request)
    {
        if($request->isGet()){
            $info = WalletAddressConfig::find();

            $max = (new MoneyRate())->where('status',1)->where('types',3)->value('num');
            $info['max_num'] = $max;
//            IndexLog::write('资产', '用户获取充排信息');
            return json()->data(['code'=>0,'msg'=>'Request successful','info'=>$info]);
        }
        if($request->isPost()){
            
            $validate = $this->validate($request->post(), '\app\index\validate\Recharge');
            if ($validate !== true) {
                return json(['code' => 1, 'msg' => $validate]);
            }
            $money_config = MoneyRate::where('types',3)->where('status',1)->value('num');
            if($money_config){
                if($money_config > $request->post('nums')){
                    return json()->data(['code'=>1,'msg'=>'The sum is too small']);
                }
            }
            $query = new Recharge();
            $recharge_data = [
                'uid' => $this->userId,
                'money_address' => $request->post('money_address'),
                'nums' => $request->post('nums'),
            ];
            $low = $query->addNew($query,$recharge_data);

            if($low){
                $entry = new Orders();
                $add_data = [
                    'uid' => $this->userId,
                    'describe' => '用户充值排单币',
                    'pic' => $request->post('pic'),
                    'types' => 2,
                    'active_id' => $low,
                ];
                $res = $entry->addNew($entry,$add_data);
                if($res){
                    IndexLog::write('资产', '用户充排单币');
                    return json()->data(['code'=>0,'msg'=>'Successful operation']);
                }
                return json()->data(['code'=>1,'msg'=>'operation failed']);
            }
        }


    }
}
