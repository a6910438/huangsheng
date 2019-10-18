<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/17
 * Time: 9:41
 */

namespace app\common\entity;


use think\Model;

class ComReply extends Model
{
    protected $table = 'community_reply';

    public function addReply($entity,$data,$user_id){
        $entity->user_id = $user_id;
        $entity->content = $data['content'];
        $entity->com_id = $data['com_id'];
        $entity->create_time = time();

        return $entity->save();
    }
}