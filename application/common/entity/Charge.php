<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/12
 * Time: 11:59
 */

namespace app\common\entity;


use think\Model;

class Charge extends Model
{
    protected $table = 'charge';

    protected $autoWriteTimestamp = false;

    public function addInfo($logo, $qrcode, $name)
    {
        $entity = new self();
        $entity->logo = $logo;
        $entity->qrcode = $qrcode;
        $entity->name = $name;
        $entity->create_time = time();
        return $entity->save();
    }
}