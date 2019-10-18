<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class BillLog extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'bill_log';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //添加新数据
    public function addNew($query,$data)
    {
        $query->uid = $data['uid'];
        $query->num = $data['num'];
        $query->old = $data['old'];
        $query->new = $data['new'];
        $query->remake = $data['remake'];
        $query->create_time = time();
        return $query->save();
    }

}
