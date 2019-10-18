<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class OvertimeLog extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'overtime_log';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;


}
