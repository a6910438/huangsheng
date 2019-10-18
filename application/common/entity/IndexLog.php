<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class IndexLog extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'index_log';


    protected $autoWriteTimestamp = false;




}
