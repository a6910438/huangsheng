<?php

namespace app\common\entity;


use think\Model;
use traits\model\SoftDelete;



class Video extends Model
{
    use SoftDelete;   //开启了软删除

    protected $deleteTime = 'delete_time';

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'video';



}
