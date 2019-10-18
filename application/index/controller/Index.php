<?php

namespace app\index\controller;

use app\common\entity\User;
use app\common\entity\Config;
use app\common\entity\Article;
use app\common\entity\Video;
use think\Db;
use think\Request;
use think\Session;

class Index extends Base {

    public function index(Request $request)
    {


        $info = Video::field('id,src')->find();
        $query = new User();
        $data = \app\common\entity\Match::alias('m')
            ->field('m.money,ll.uid as take_user,w.uid as store_user')
            ->leftJoin('line_list ll','m.take_id = ll.id')
            ->leftJoin('withdraw w','m.store_id = w.id')
            ->where('m.create_time','>',time()-120)
            ->select();
        foreach ($data as $v){
            $v['take_user'] = $query->getNickName($v['take_user']);
            $v['store_user'] = $query->getNickName($v['store_user']);
        }
        return json()->data([
            'code' => 0,
            'msg' => 'Request successful',
            'info' => [
                'video' => $info,
                'data' => $data,
            ],
        ]);
    }
    public function userInfo(Request $request)
    {


    }

}
