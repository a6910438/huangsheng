<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class V4Level extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'v4_level';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;




}
