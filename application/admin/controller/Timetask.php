<?php

namespace app\admin\controller;

use app\admin\exception\AdminException;
use app\common\entity\FomoConfig;
use app\common\entity\FomoTeam;
use app\common\entity\FomoGame;
use app\common\entity\Buykey;
use app\common\entity\User;
use app\common\entity\LegalReportcentre;
use think\Controller;
use app\common\entity\FomoNextLog;


class Timetask extends Controller
{   

	public function check()
    {
        echo 1;
        die;
    	file_put_contents(dirname(__FILE__).'/43214321432143',json_encode(4321432143214));
    	$entity = FomoGame::where('status', 1)->where('endtime','<',time())->find();
        file_put_contents(dirname(__FILE__).'/222222222',json_encode(333));

    	if($entity){
    		FomoGame::gameOver($entity);
    	}


        


        $fomogame = new FomoGame();
        $result = $fomogame->where('status',-1)->order('id desc')->find();// 获取游戏是否结束
        $is_openNew = $fomogame->where('status',1)->find(); // 查看是否有开启的游戏存在 
        if($result&&!$is_openNew){

            $FomoConfig = new FomoConfig();
            $autoOpenTime = $FomoConfig->getValue('autoOpenTime');
            $opentime = intval($autoOpenTime*60*60); // 隔多久开启

            // 开启时获取上一轮剩余的资金
            $fomonextlogModel = new FomoNextLog();
            $bonus = $fomonextlogModel->where('type',0)

                        ->where('periods',($result->id))
                        ->order('id','asc')
                        ->find();

            if (time()>$result->endtime+$opentime) {
            // if (time()>$result->endtime+10) {
                $data['id'] = $result->id+1;
                $data['time'] = $result->time;
                $data['team_ids'] = $result->team_ids;
                $data['play_scale'] = $result->play_scale;
                $data['team_scale'] = $result->team_scale;
                $data['bonus_scale'] = $result->bonus_scale;
                $data['status'] = 1;
                if ($bonus) {
                    $data['capital'] = $bonus->next_bonus; // 上一轮加的
                }else{
                    $data['capital'] = 0;
                }
                $data['bonus'] = 0;
                $data['inviteaward'] = 0;
                $data['teamaward'] = 0;
                $data['dropaward'] = 0;
                $data['createtime'] = time();
                $data['endtime'] = time()+60*60*24;

                $res = $fomogame->save($data);
                // var_dump($res);

                // 游戏添加成功
                if ($res&&$bonus) {
                    $res1 = $bonus->where('id',$bonus->id)->setField('type',1); // 确认一取出上一轮的钱
                    // var_dump($res1);
                }
            }

        }
        




        

    }

   



}
