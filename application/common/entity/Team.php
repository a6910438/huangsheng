<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class Team extends Model {

    protected $createTime = 'create_time';

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'team';
    protected $auto = ['create_time'];

    //返回原有数据  不自动进行时间转换
    public function getCreateTimeAttr($time) {
        return $time;
    }
    //获取团队取款数
    public function findTeamDraw($tid)
    {
        $data = User::alias('u')
            ->leftJoin('withdraw w','w.uid = u.id')
            ->where('u.tid',$tid)
            ->where('u.status',1)
            ->sum('w.total');
        return $data;
    }
    //获取团队冻结人数
    public function getFreezeCount($tid)
    {
        $data = User::alias('u')
        ->where('u.tid',$tid)
        ->where('u.status',-1)
        ->count('u.id');
        return $data;

    }
    //获取团队存款数
    public function findTeamDeposit($tid)
    {
        $data = User::alias('u')
            ->leftJoin('line_list ll','ll.uid = u.id')
            ->where('u.tid',$tid)
            ->where('u.status',1)
            ->sum('w.num');
        return $data;
    }
    //获取下级人数
    public function getChildCount($uid)
    {
        $res = User::where('pid',$uid)->select();
        if(!$res){
            return true;
        }
    }
    //是否为团队长
    public function isTeam($uid)
    {
        $res = $this->where('leader',$uid)->find();
        if(!$res){
            return true;
        }
    }

    public function getuid_Team($uid)
    {
        return $this->where('leader',$uid)->find();

    }


    /**
     * 添加团队
     * @param $uid
     * @return bool|int|string
     */
    public function add($uid){


        $res = $this->where('leader',$uid)->find();
        if($res){
            return false;
        }
        $add['leader'] = $uid;
        $add['man_count'] = 1;
        $add['line_count'] = 0;
        $add['money_cont'] = 0;

       return $this->insertGetId($add);
    }


    public function addsetInc($id){
        $this->where('id',$id)->setInc('man_count',1);
    }

}
