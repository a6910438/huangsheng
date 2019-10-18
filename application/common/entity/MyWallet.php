<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class MyWallet extends Model
{


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'my_wallet';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //后台 充值/扣款
    public function RechargeLog($query, $data)
    {

        $oldInfo = $this->where('uid', $data['uid'])->find();

        Db::startTrans();
        try {
            if ($data['type'] == 1) {
                $edit_data['now'] = $data['num'] + $oldInfo['now']; //现在
                if ($data['num'] > 0) {
                    $edit_data['old'] = $data['num'] + $oldInfo['old']; //历史
                }
            }

            $edit_data['update_time'] = time();

            $res = $query->where('uid', $data['uid'])->update($edit_data);

            if (!$res) {
                return false;
            }


            $create_data = [
                'uid' => $data['uid'],
                'number' => $data['num'],   //交易数量
                'now' => $oldInfo['now'],   //交易前
                'remark' => $data['remake'], //备注
                'future' => $edit_data['now'], //交易之后
                'create_time' => time(),
                'types' => $data['type'],
            ];


            $res2 = MyWalletLog::insert($create_data);

            if (!$res2) {
                return false;
            }
//            $this->bonusDispense($data['num'],$data['uid']);
//            $this->teamDispense($data['num'],$data['uid']);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            return false;
        }
    }

    /**
     * 检测等级是否合格并且升级
     *
     */

    public function levelupgare($userId = 0)
    {
        $member = DB::table('user')->where('id', $userId)->find();

        if (empty($member)) {
            return 0;
        }
        //品酒，预约，返料，即抢
        $my_bait = DB::table('my_wallet_log')->where('uid', $userId)->where('types', 'in', '2,3,6,4')->sum('number');
        if (!$my_bait) {
            $my_bait = 0;
        }
        //$my_bait = DB::table('my_wallet_log')->where('uid',$userId)->where('types','in','2,3,6,4')->sum('number') ?? 0;
        //静态GTC
        $my_bait = abs($my_bait);
        $my_profit = $member['profit'];                                          //静态收益
        $invite_num = DB::table('user')->where('pid', $userId)->count();          //直推人数
        $team_num = $this->teamnum($userId);                                     //伞下人数


        //初级判断
        $lv = DB::table('user_extension')
            ->where('bait_need1', '<=', $my_bait)      //静态GTC
            ->where('profit_need1', '<=', $my_profit)  //静态收益
            ->where('push_need1', '<=', $invite_num)   //直推人数
            ->where('umbrella_need1', '<=', $team_num) //伞下人数
            ->count('id') ? 1 : 0;

        //中级判断
        if (!$lv) {
            $lv = DB::table('user_extension')
                ->where('bait_need2', '<=', $my_bait)        //静态GTC
                ->where('profit_need2', '<=', $my_profit)    //静态收益
                ->where('push_need2', '<=', $invite_num)     //直推人数
                ->where('umbrella_need2', '<=', $team_num)   //伞下人数
                ->value('id') ? 2 : 0;
        }

        //高级判断
        if (!$lv) {
            $lv = DB::table('user_extension')
                ->where('bait_need3', '<=', $my_bait)        //静态GTC
                ->where('profit_need3', '<=', $my_profit)    //静态收益
                ->where('push_need3', '<=', $invite_num)     //直推人数
                ->where('umbrella_need3', '<=', $team_num)   //伞下人数
                ->value('id') ? 3 : 0;
        }


        if ($lv > $member['lv']) {


            DB::table('user')->where('id', $userId)->update(['lv' => $lv]);
        } else {
            $lv = $member['lv'];
        }
//        if($lv > 0 && $member['pid'] > 0){
//            $p_lv = DB::table('user')->where('id',$member['pid'])->value('lv');
//            if($lv >= $p_lv){
//                DB::table('user')->where('id',$userId)->update(['pid'=>0]);
//            }
//        }
        return $lv;

    }

//
//    public function levelupgare($userId=0){
//        $member = DB::table('user')->where('id',$userId)->find();
//        if(empty($member)){
//            return 0;
//        }
//        $my_bait = DB::table('my_wallet')->where('uid',$userId)->value('old')??0;
//        $my_profit = $member['profit'];
//        $invite_num = DB::table('user')->where('pid',$userId)->count();
//        $team_num = $this->teamnum($userId);
//        $info = DB::table('bathing_pool')
//                        ->where('profit_need','<',$my_bait)
//                        ->where('bait_need','<',$my_profit)
//                        ->where('push_need','<',$invite_num)
//                        ->where('umbrella_need','<',$team_num)
//                        ->value('lv')??0;
//        if($info > $member['lv']){
//
//            DB::table('user')->where('id',$userId)->update(['lv'=>$info]);
//        }
//        return $info;
//
//    }
    /**
     * 统计伞下团队人数
     */
    public function teamnum($userId = 0)
    {
        $t_num = 1;
        (new User())->getTeamZTNum($userId, $t_num); //团队人数eturn $num;

        return $t_num;
    }

    /**
     * 直推人数
     * @param int $userId
     * @return int|string
     */
    public function pushnum($userId = 0)
    {
        return DB::table('user')->where('pid', $userId)->count();
    }

    /**
     * 直推激活人数
     * @param int $userId
     * @return int|string
     */
    public function pushactivationnum($userId = 0)
    {
        return DB::table('user')->where('pid', $userId)->where('status', 1)->count();
    }

    /**
     * 直推激活人数
     * @param int $userId
     * @return int|string
     */
    public function pushunactivationnum($userId = 0)
    {
        return DB::table('user')->where('pid', $userId)->where('status', 0)->count();
    }

    /**
     * 推广收益
     * type 1 后台充值 2前端互转
     */
    public function bonusDispense($money, $userId = 0, $type = 1, $num = 1, $source_id = 0, $oid)
    {
        if ($userId == 0) {
            return false;
        }
        $member = DB::table('user')->where('id', $userId)->find();

        if (!empty($member)) {
            if ($member['pid'] != 0) {
                if ($num == 1) {
                    $source_id = $userId;
                }
//                $level = $this->levelupgare($member['pid']);

                if ($num == 1) {
                    $extension_profit = DB::table('user_extension')
                        ->value('extension_profit1');
                    $extension_profit = empty($extension_profit) ? 0 : $extension_profit;
                } elseif ($num == 2) {
                    $extension_profit = DB::table('user_extension')
                        ->value('extension_profit2');
                    $extension_profit = empty($extension_profit) ? 0 : $extension_profit;
                } elseif ($num == 3) {
                    $extension_profit = DB::table('user_extension')
                        ->value('extension_profit3');

                    $extension_profit = empty($extension_profit) ? 0 : $extension_profit;

                } else {
                    $extension_profit = 0;
                }


                //如果有数据
                if ($extension_profit) {

                    $value = $extension_profit;
                    if ($value > 0) {


                        $save_money = bcmul($money, $value / 100, 2);
                        if(isset($save_money)){
                            $is_save1 = DB::table('user')
                                ->where('id', $member['pid'])
                                ->where('status', 1)
                                ->where('is_active', 1)
                                ->setInc('prohibit_integral', $save_money);

                            $is_save2 = DB::table('user')
                                ->where('id', $member['pid'])
                                ->where('status', 1)
                                ->where('is_active', 1)
                                ->setInc('now_prohibit_integral', $save_money);
                            if ($is_save1 && $is_save2)
                                $is_save3 = DB::table('prohibit_log')->insert([
                                    'uid' => $member['pid'],
                                    'old' => $member['prohibit_integral'],
                                    'new' => bcadd($member['prohibit_integral'], $save_money, 2),
                                    'createtime' => time(),
                                    'number' => $save_money,
                                    'type' => $type,
                                    'source_id' => $source_id,
                                    'open_time' => time(),
                                    'oid' => $oid,
                                ]);
                        }else{
                            return false;
                        }
                    }
//                    dump($is_save1);
//                    dump($is_save2);
//                    dump($is_save3);
//                    exit;

                }
//                $arr['val'] = $value;
//                $arr['num'] = $num;
//                $arr['pid'] = $member['pid'];
//                $arr['uid'] = $userId;
//                $arr['money'] = $money;
//                $arr['save_money'] = $save_money;
//                $json = json_encode($arr);
//                addMy_log('推广收益',$json);
                $num++;

                if ($num <= 3) {
                    $this->bonusDispense($money, $member['pid'], $type, $num, $source_id, $oid);
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * 团队收益
     * type 1 后台充值 2前端互转
     * bonus 级差奖励
     * source_id 来源id
     * num 统计次数来于跳出循环
     */
    public function teamDispense($money, $userId = 0, $type = 1, $num = 1, $source_id = 0, $oid = 0)
    {
        if ($userId == 0) {
            return false;
        }
        $member = DB::table('user')->where('id', $userId)->find();
        if (!empty($member)) {
            if ($member['pid'] != 0) {
                if ($num == 1) {
                    $source_id = $userId;
                }
                $level = $this->levelupgare($member['pid']);//父级用户等级


                if ($level == 1) {
                    $team_profit = DB::table('user_extension')
                        ->value('team_profit1');
                    $team_profit = empty($team_profit) ? 0 : $team_profit;
                } elseif ($level == 2) {
                    $team_profit = DB::table('user_extension')
                        ->value('team_profit2');
                    $team_profit = empty($team_profit) ? 0 : $team_profit;
                } elseif ($level == 3) {
                    $team_profit = DB::table('user_extension')
                        ->value('team_profit3');
                    $team_profit = empty($team_profit) ? 0 : $team_profit;
                } else {
                    $team_profit = 0;
                }


                //父级等级必须大于用户等级
//                if($team_profit && $member['lv'] < $level){

                if ($money > 0) {


                    $save_money = bcmul($money, $team_profit / 100, 2);
                    if(isset($save_money)) {
                        $is_save1 = DB::table('user')
                            ->where('id', $member['pid'])
                            ->where('status', 1)
                            ->where('is_active', 1)
                            ->setInc('team_integral', $save_money);
                        $is_save2 = DB::table('user')
                            ->where('id', $member['pid'])
                            ->where('status', 1)
                            ->where('is_active', 1)
                            ->setInc('now_team_integral', $save_money);
                        if ($is_save1 && $is_save2) {
                            $is_save3 = DB::table('team_log')->insert([
                                'uid' => $member['pid'],
                                'old' => $member['team_integral'],
                                'new' => bcadd($member['team_integral'], $save_money, 2),
                                'createtime' => time(),
                                'number' => $save_money,
                                'type' => $type,
                                'source_id' => $source_id,
                                'open_time' => time(),
                                'oid' => $oid,
                            ]);
                        }
                    }else{
                        return false;
                    }


                }

            }
            $num++;
            if (isset($level)) {
                if(isset($save_money)){
                if ($level <= 3) {
                    $this->teamDispense($save_money, $member['pid'], $type, $num, $source_id, $oid);
                } else {
                    return false;
                }
                }else{
                    return false;
                }
            } else {
                return false;
            }

        }
//        }
    }


}
