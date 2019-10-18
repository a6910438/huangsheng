<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class ManageLog extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'manage_log';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //获取类型
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '解除管控';
            case 2:
                return '管控会员';
            default:
                return '';
        }
    }
    //获取所有类型
    public function getAllStatus()
    {
        return [
            1 =>   '解除管控',
            2 =>   '管控会员',
        ];
    }
    //添加新数据
    public function addNew($query ,$data)
    {
        $query->uid = $data['uid'];
        $query->status = $data['status'];
        $query->create_time = time();
        return $query->save();
    }


}
