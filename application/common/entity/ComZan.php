<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/17
 * Time: 10:14
 */

namespace app\common\entity;


use think\Model;

class ComZan extends Model
{
    protected $table = 'community_zan';

    public function addClick($entity,$data,$user_id){
        $entity->user_id = $user_id;
        $entity->status = 1;
        $entity->com_id = $data['com_id'];
        $entity->create_time = time();

        return $entity->save();

    }



}