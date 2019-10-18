<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class SeachConfig extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'seach_config';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;
    //获取类型
    public function getType($type)
    {
        switch ($type) {
            case 1:
                return '搜索次数';
            case 2:
                return '交易次数';
            default:
                return '';
        }
    }
    //添加新数据
    public function addNew($query,$data)
    {
        $query->num = $data['num'];
        $query->types = $data['types'];
        $query->create_time = time();
        return $query->save();
    }

}
