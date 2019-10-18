<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/14
 * Time: 15:14
 */

namespace app\common\entity;


use think\Model;

class ComSend extends Model
{
    protected $table = 'community_send';

    protected $autoWriteTimestamp = false;

    #添加发送
    public function addSend($entity,$data,$user_id){

        $entity->user_id = $user_id;
        $entity->content = $data['content'];
        $entity->image = $data['image']?$data['image']:'';
        $entity->status = 0;
        $entity->create_time = time();

        return $entity->save();

    }



}