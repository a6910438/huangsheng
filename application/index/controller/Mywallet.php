<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/14
 * Time: 16:41
 */

namespace app\index\controller;


use app\common\entity\MywalletLog;
use app\common\entity\Profit;
use app\common\entity\Proportion;
use app\common\entity\Quotation;
use app\common\entity\User;
use app\common\entity\WithdrawLog;
use app\common\entity\YekesConfig;
use app\common\entity\YekesLog;
use think\Db;
use think\Request;

class Mywallet extends Base
{
    #获取钱包信息
    public function index(){
        $uid = $this->userId;
        $withdraw_ratio = YekesConfig::where('id',5)->value('values');
        $list = \app\common\entity\Mywallet::where('user_id',$uid)->find();
        $ratio = Quotation::where('id',1)->find();
        $list['total'] = $list['btc']/$ratio['btc'] + $list['eos']/$ratio['eos'] + $list['eth']/$ratio['eth'];
        $list['withdraw_ratio'] = $withdraw_ratio;
        if ($list){

            return json(['code' => 0, 'msg' => '获取成功' , 'info' => $list ]);
        }
        if (empty($list)){
            return json(['code' => 0, 'msg' => '暂无数据' ,'info' => $list ]);

        }
        return json(['code' => 1, 'msg' => '获取失败']);

    }

    #获取YEKES记录
    public function gerYekesLog(){

        $uid = $this->userId;
        $yekes = new YekesLog();
        $list = $yekes->getLog($uid);
//        var_dump($list);die;

        if ($list){

            return json(['code' => 0, 'msg' => '获取成功' , 'info' => $list]);
        }
        if (empty($list)){
            return json(['code' => 0, 'msg' => '暂无数据']);

        }
        return json(['code' => 1, 'msg' => '获取失败']);

    }

    #获取YEKES数量
    public function getYekesNum(){
        $uid = $this->userId;
        $user = new User();
        $list = $user::where('id',$uid)->field('yekes')->find();
        if ($list){

            return json(['code' => 0, 'msg' => '获取成功' , 'info' => $list]);
        }
        if (empty($list)){
            return json(['code' => 0, 'msg' => '暂无数据']);

        }
        return json(['code' => 1, 'msg' => '获取失败']);

    }


    #资金变动详情
    public function myWalletLog(Request $request){
        $uid = $request->post('user_id');
        $nick_name = User::where('id',$uid)->value('nick_name');
        $btc_address = User::where('id',$uid)->value('btc_address');

        $mywalletlog = new MywalletLog();
        $list = $mywalletlog->getLog($uid,$request->post('page'),$request->post('limit'));
        $count = $mywalletlog->where('user_id',$uid)->count();
        if ($list){

            return json(['code' => 0, 'msg' => '获取成功' , 'info' => $list , 'count' => $count,'nick_name'=>$nick_name,'btc_address'=>$btc_address]);
        }
        if (empty($list)){
            return json(['code' => 0, 'msg' => '暂无数据' , 'count' => $count ,'nick_name'=>$nick_name,'btc_address'=>$btc_address ]);

        }
        return json(['code' => 1, 'msg' => '获取失败']);
    }
    #我的变动详情
    public function WalletLog(Request $request){
        $uid = $this->userId;
        $nick_name = User::where('id',$uid)->value('nick_name');
        $btc_address = User::where('id',$uid)->value('btc_address');
        $mywalletlog = new MywalletLog();
        $list = $mywalletlog->getMylog($uid,$request->post('page'),$request->post('limit'));
        $count = $mywalletlog->where('user_id',$uid)->whereIn('types',array('5','7','0'))->count();
       	foreach ($list as $vvv) {
       		if ($vvv->types == 0) {
	       		$vvv->remark = '充值';
       		}
       	}
        if ($list){

            return json(['code' => 0, 'msg' => '获取成功' , 'info' => $list , 'count' => $count,'nick_name'=>$nick_name,'btc_address'=>$btc_address]);
        }
        if (empty($list)){
            return json(['code' => 0, 'msg' => '暂无数据' , 'count' => $count ,'nick_name'=>$nick_name,'btc_address'=>$btc_address ]);

        }
        return json(['code' => 1, 'msg' => '获取失败']);
    }

    #我的资金记录
    public function WalletLogType(Request $request){
        $uid = $this->userId;
        $nick_name = User::where('id',$uid)->value('nick_name');
        $btc_address = User::where('id',$uid)->value('btc_address');

        $money_type = $request->post('money_type');
        $mywalletlog = new MywalletLog();
        $list = $mywalletlog->getMylogType($uid,$money_type,$request->post('page'),$request->post('limit'));
        foreach ($list as $vvv) {
       		if ($vvv->types == 0) {
	       		$vvv->remark = '充值';
       		}
       	}
        $count = $mywalletlog->where('money_type',$money_type)->whereIn('types',array('5','7','0'))->where('user_id',$uid)->count();
        if ($list){

            return json(['code' => 0, 'msg' => '获取成功' , 'info' => $list,'count' => $count,'nick_name'=>$nick_name,'btc_address'=>$btc_address]);
        }
        if (empty($list)){
            return json(['code' => 0, 'msg' => '暂无数据','nick_name'=>$nick_name,'btc_address'=>$btc_address]);

        }
        return json(['code' => 1, 'msg' => '获取失败']);
    }

    #别人资金记录
    public function myWalletLogType(Request $request){
        $uid = $request->post('user_id');
        $nick_name = User::where('id',$uid)->value('nick_name');
        $btc_address = User::where('id',$uid)->value('btc_address');

        $money_type = $request->post('money_type');
        $mywalletlog = new MywalletLog();
        $list = $mywalletlog->getTypeLog($uid,$money_type,$request->post('page'),$request->post('limit'));
        $count = $mywalletlog->where('money_type',$money_type)->where('user_id',$uid)->count();
        if ($list){

            return json(['code' => 0, 'msg' => '获取成功' , 'info' => $list,'count' => $count,'nick_name'=>$nick_name,'btc_address'=>$btc_address]);
        }
        if (empty($list)){
            return json(['code' => 0, 'msg' => '暂无数据','nick_name'=>$nick_name,'btc_address'=>$btc_address]);

        }
        return json(['code' => 1, 'msg' => '获取失败']);
    }


    #我的收益
    public function profit(Request $request){
        $uid = $this->userId;
        $types = $request->get('types');
        $profit = new Profit();
        $list = $profit->getList($uid,$types);
        // $btc = Quotation::where('id',1)->value('btc');
        if ($list){

            return json(['code' => 0, 'msg' => '获取成功' , 'info' => $list]);
        }
        if (empty($list)){
            return json(['code' => 0, 'msg' => '暂无数据']);

        }
        return json(['code' => 1, 'msg' => '获取失败']);
    }

    #提现
    public function withdraw(Request $request){
        $uid = $this->userId;
        $money_type = $request->post('money_type');
        $number = $request->post('number');

        $withdraw = new WithdrawLog();
        if ($money_type == 1){
            $updtype = 'btc';
        }elseif ($money_type == 2){
            $updtype = 'eth';
        }elseif ($money_type == 3){
            $updtype = 'eos';
        }elseif ($money_type == 4){
            $updtype = 'number';
        }

        $myWallet = \app\common\entity\Mywallet::where('user_id',$uid)->find();
        if ($myWallet[$updtype] < $number){
            return json(['code' => 1, 'msg' => '余额不足']);
        }
        $upd = \app\common\entity\Mywallet::where('user_id',$uid)->update([$updtype=>$myWallet[$updtype] - $number]);
//        dump($upd);die;
        if ($upd){
            $withdraw_ratio = YekesConfig::where('id',5)->value('values');
            $list = $withdraw->addWithdraw($withdraw,$request->post(),$uid);
            $myWalletLog = new MywalletLog();
            $insLog = $myWalletLog->addLog($uid,$number-$number*$withdraw_ratio,$updtype,$updtype.'提币',7,2,$money_type);
            if ($list){

                return json(['code' => 0, 'msg' => '提交成功' ]);
            }
        }

        return json(['code' => 1, 'msg' => '提交失败']);

    }


    #获取行情
    public function getRatio(){
        $list = Quotation::where('id',1)->find();
        $data = Proportion::select();
//        $list['eos'][] = $data[1]['rate_percent'];
        $data[0]['ratio'] = round(1/$list['eos'],8);
        $data[1]['ratio'] = round(1/$list['eth'],8);
        $data[2]['ratio'] = round(1/$list['btc'],8);
        return json(['code' => 0, 'msg' => '获取成功' , 'info' => $data ]);
    }

    public function as()
    {
        $user = new User;
        for ($i = 0; $i < 10; $i++) {

            $aa[] = $user->getParentsInfo(3);
        }
        return json($aa);
        foreach ($aa as $k => $v) {
            dump($v);
        }
    }

    #充值记录
    public function indexRecharge()
    {
        $list = MywalletLog::alias('m')->field('m.*,u.nick_name')
        ->leftJoin('user u','m.user_id = u.id')
        ->wherein('types',[5,0])->select();
        return json(['code' => 0, 'msg' => '获取成功' , 'info' => $list ]);
    }

}