<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/7
 * Time: 11:17
 */

namespace app\common\entity;


use think\Model;

class Guild extends Model
{
    protected $table = 'guild';

    public function addGuild( $entity , $uid , $data)
    {
        $entity->user_id = $uid;
        $entity->title = $data['title'];
        $entity->content = $data['content'];
        $entity->cansee_user_id = $data['cansee'];
        $entity->create_time = time();
        $res = $entity->save();
        $guildId = $entity->getLastInsID();
        if ($res){
            $canseearr = explode(',',$data['cansee']);
            $guildCansee = new GuildCansee();
            $guildCansee->addGuildCansee($guildId,$uid);
            foreach ($canseearr as $v){
                $guildCansee = new GuildCansee();

                $guildCansee->addGuildCansee($guildId,$v);
            }
            return true;
        }
        return false;

    }

}