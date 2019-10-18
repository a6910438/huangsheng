<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class SeachLog extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'seach_log';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;




}
