<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class LineDetail extends Model {

    protected $createTime = 'create_time';

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'line_detail';
    protected $auto = ['create_time'];

    //添加新数据
    public function addNew($query,$data)
    {
        $query->line_id = $data['line_id'];
        $query->match_id = $data['match_id'];
        $query->num = $data['num'];
        $query->create_time = time();
        return $query->save();
    }


}
