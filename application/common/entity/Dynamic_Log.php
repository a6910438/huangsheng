<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class Dynamic_Log extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'dynamic_log';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //获取类型
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '未解冻';
            case 2:
                return '已解冻';

            default:
                return '';
        }
    }
    //添加新数据
    public function addNew($query ,$data)
    {
        $query->uid = $data['uid'];
        $query->status = $data['status'];
        $query->form_user = $data['form_user'];
        $query->form_level = $data['form_level'];
        $query->total = $data['total'];
        $query->create_time = time();
        $query->open_time = $data['open_time'];
        return $query->save();
    }


}
