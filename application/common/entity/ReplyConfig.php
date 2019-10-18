<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class ReplyConfig extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'reply_config';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;


    //添加新数据
    public function addNew($query ,$data)
    {
        $query->reply = $data['reply'];
        $query->create_time = time();
        return $query->save();
    }


}
