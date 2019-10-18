<?php

namespace app\admin\controller;

use app\admin\exception\AdminException;

use app\common\entity\TransferLog;
use app\common\entity\ProductPool as ProductPoolModel;
use app\common\entity\Fish as FishModel;
use app\common\entity\User as userModel;
use app\common\entity\UserMagicLog;
use app\common\entity\Export;
use app\common\entity\ProductPool;

use app\common\service;
use think\Db;
use think\Request;
use service\LogService;
use think\Session;

class Bait extends Admin {

    /**
     * @power
     * @rank 1
     */
    public function index(Request $request) {
        $stime = 0;
        $ntime = 0;
        if ($request->get('stime') || $request->get('ntime')) {
            $stime = $request->get('stime');
            $ntime = $request->get('ntime');

            if(empty($stime)){
                $stime = time();
            }else{
                $stime = strtotime($stime);
            }
            if(empty($ntime)){
                $ntime = time();
            }else{
                $ntime = strtotime($ntime);
            }
			if($stime >= $ntime){
                $this->error('开始时间必须小于结束时间');
            }
            $map['stime'] = date('Y-m-d',$stime);
            $map['ntime'] = date('Y-m-d',$ntime);
        }
        $entity = ProductPoolModel::where('is_delete',0);

        $list = $entity
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);

        foreach ($list as $k => $v){
            $list[$k]['MakeOverMoney'] = getBaitMoney($v['id'],$stime,$ntime);

            $list[$k]['MakeOverNum'] =  getBaitFishNum($v['id'],$stime,$ntime);

        }
        $date1 = date('Y-m-d');
        $tos = strtotime($date1);
        $ton = strtotime("$date1 + 1 day ");
        $tomn = strtotime("$date1 + 2 day ");

        $res['list'] = $list;
        $res['to_money'] = getBaitMoney(0,$stime,$ntime);
        $res['to_num'] = getBaitFishNum(0,$stime,$ntime);

        $res['tom_money'] = getAllBaitYjMoney(0,$ton,$tomn);
        $res['tom_num'] = getBaitFishYjNum(0,$ton,$tomn);

        return $this->render('index', [
            'list' => $res,
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);
    }



    /**
     * 详情
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function details(Request $request) {

        $id = $request->param('id');

        if(empty($id)){
            $this->error('确实参数');
        }
        $stime = 0;
        $ntime = 0;
        if ($request->param('stime') || $request->param('ntime')) {
            $stime = $request->param('stime');
            $ntime = $request->param('ntime');

            if(empty($stime)){
                $stime = time();
            }else{
                $stime = strtotime($stime);
            }
            if(empty($ntime)){
                $ntime = time();
            }else{
                $ntime = strtotime($ntime);
            }
			if($stime >= $ntime){
                $this->error('开始时间必须小于结束时间');
            }
            $map['au.stime'] = date('Y-m-d',$stime);
            $map['au.ntime'] = date('Y-m-d',$ntime);
        }



        $entity = Db::table('fish_feed_log')
            ->alias('ffl')
            ->join('fish f','f.id = ffl.fid')
            ->join('bathing_pool bp','bp.id = f.pool_id')
            ->join('user u','u.id = f.uid');
        $entity ->where('bp.id',$id);

        $entity->where('ffl.is_feed',1);

        $entity ->group('ffl.fid');

        if($stime&&$ntime){
            $entity->whereTime('ffl.stime',[$stime, $ntime]);
        }else{
            $date1 = date('Y-m-d');

            $tos = strtotime($date1);
            $ton = strtotime("$date1 + 1 day ");
            $entity->whereTime('ffl.stime',[$tos, $ton]);
        }

        $entity->field('u.nick_name,bp.name,ffl.feed_time,bp,contract_time,bp,profit,bp,lock_position');
        $list = $entity
            ->paginate(15, false, [
                'query' => isset($map) ? $map : []
            ]);

        dump($list);exit;

        return $this->render('details', [
            'list' => $list,
            'queryStr' => isset($map) ? http_build_query($map) : '',
        ]);
    }




}
