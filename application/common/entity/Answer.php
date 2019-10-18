<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class Answer extends Model {

    protected $createTime = 'create_time';

    /**
     * @var string 对应的数据表名
     */
    protected $table = 'answer';
    protected $auto = ['create_time'];

    //获取状态
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '启用';
            case 2:
                return '禁用';
            default:
                return '';
        }
    }
    //选项
    public function arr($k)
    {
        $arr = [
            1=>'a',
            2=>'b',
            3=>'c',
            4=>'d',
            5=>'e',
            6=>'f',
            7=>'g',
            8=>'h',
            9=>'i',
            10=>'j',
            11=>'k',
            12=>'l',
            13=>'m',
            14=>'n',
            15=>'o',
            16=>'p',
            17=>'q',
            18=>'r',
            19=>'s',
            20=>'t',
            21=>'u',
            22=>'v',
            23=>'w',
            24=>'x',
            25=>'y',
            26=>'z',
        ];
        return $arr[$k];
    }
    //修改数据
    public function  editData($query,$data)
    {

        $query->content = $data['content'];
        $query->score = $data['score'];
        $query->sort = $data['sort'];
        $query->status = $data['status'];
        $query->create_time = time();
        return $query->save();
    }
    //添加新数据
    public function  addNew($query,$data)
    {
        $query->qid = $data['qid'];
        $query->content = $data['content'];
        $query->score = $data['score'];
        $query->sort = $data['sort'];
        $query->status = $data['status'];
        $query->create_time = time();
        return $query->save();
    }


}
