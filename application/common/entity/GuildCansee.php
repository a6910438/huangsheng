<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/7
 * Time: 11:17
 */

namespace app\common\entity;


use think\Model;

class GuildCansee extends Model
{
    protected $table = 'guild_cansee';

    public function addGuildCansee(  $guildId , $cansee_user_id)
    {
        $this->guild_id = $guildId;
        $this->cansee_user_id = $cansee_user_id;
        $this->create_time = time();
        $res = $this->save();
        return $res;
    }

}