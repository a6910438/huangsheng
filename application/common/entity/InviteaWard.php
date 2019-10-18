<?php
namespace app\common\entity;

use think\Db;
use think\Model;
use app\common\entity\FomoConfig;
use app\common\entity\FomoTeam;

class InviteaWard extends Model
{
    protected $table = 'fomo_inviteaward';


     /**
     * 发生时间
     */
    public function getCreateTime()
    {
        return date('Y-m-d H:i:s',$this->createtime);
    }

    public function getTeamTitle($id){
        $team = FomoTeam::where('id',$id)->field('title')->find();
        if (!$team) {
            return false;
        }
        return $team->title;
    }



}
