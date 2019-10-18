<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class BigWithdra extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'big_withdra';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;



}
