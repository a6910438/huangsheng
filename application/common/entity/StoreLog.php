<?php

namespace app\common\entity;


use think\Model;
use traits\model\SoftDelete;



class StoreLog extends Model
{


    protected $deleteTime = 'delete_time';

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'store_log';

    /**
     * @var string 获取类型 1：冻结中 2：待释放 3：已释放
     */
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '冻结中';
            case 2:
                return '待释放';
            case 3:
                return '已释放';
            default:
                return '';
        }
    }
    /**
     * @var string 获取类型
     */
    public function getTypes($types)
    {
        switch ($types) {
            case 1:
                return '固定收入';
            case 2:
                return '动态收益';

            default:
                return '';
        }
    }
    /**
     * @var string 添加新数据
     */
    public function addNew($query,$data)
    {
        $query->uid = $data['uid'];
        $query->types = $data['types'];
        $query->status = $data['status'];
        $query->num = $data['num'];
        $query->interest = $data['interest'];
        $query->create_time = time();
        $query->my_end_time = $data['my_end_time'];
        $query->you_end_time = $data['you_end_time'];
        $query->you_status = $data['you_status'];
        $res =  $query->save();
        if($res){
            return $query->id;
        }
    }




}
