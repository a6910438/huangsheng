<?php

namespace app\common\entity;

use think\Model;

class Userlevelname extends Model {

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'userlevelname';
    public function addUserlevelname($data) {
        $entity = new self();
        $entity->level_name = $data['level_name'];
        $entity->level = $data['level'];
        $entity->createtime = time();
        $entity->updatetime = time();

        return $entity->save();
    }

    public function updateUserlevelname($entity, $data) {
        $entity->level_name = $data['level_name'];
        $entity->level = $data['level'];
        $entity->updatetime = time();
        return $entity->save();
    }
}
