<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class SystemLog extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'system_log';


    protected $autoWriteTimestamp = false;


}
