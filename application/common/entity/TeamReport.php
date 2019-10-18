<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class TeamReport extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'team_report';


    protected $autoWriteTimestamp = false;

}
