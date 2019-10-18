<?php

namespace app\common\entity;

use think\Db;
use think\Model;

class PersonService extends Model {


    /**
     * @var string 对应的数据表名
     */
    protected $table = 'person_service';

    protected $createTime = 'create_time';

    protected $autoWriteTimestamp = false;

    //获取类型
    public function getType($type)
    {
        switch ($type) {
            case 1:
                return '冻结客服协商';
            default:
                return '';
        }
    }
    //获取状态
    public function getStatus($status)
    {
        switch ($status) {
            case 1:
                return '待审核';
            case 2:
                return '通过';
            case 3:
                return '拒绝';
            default:
                return '';
        }
    }
    //添加新数据
    public function addNew($query ,$data)
    {
        $query->uid = $data['uid'];
        $query->types = $data['types'];
        $query->content = $data['content'];
        $query->pic = $data['pic'];
        $query->create_time = time();
        return $query->save();
    }
    public function getReply($id)
    {
        $forms = "<form class='form-horizontal' method='post' onsubmit='return false' role='form'>";
        $name = "refuseperson?id=$id";
        $str = "<input type='text' value='' name='reply' id='reply' class='form-control' placeholder='请输入拒绝理由'>";
        $star = "<button class='btn btn-color'  onclick='main.ajaxPosts(this)' data-url=";
        $end = " >确定</button>";
        $forme = "</form>";
        return $forms.$str.$star.$name.$end.$forme;
    }


}
