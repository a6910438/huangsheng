<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class DoTestQuestion extends Validate
{

    protected $rule = [
        'title' => 'require',
        'sort' => 'require|between:0,999',
        'status' => 'require|between:1,2',
    ];

    protected $message = [
        'title.require' => '请输入题目内容',
        'sort.require' => '请输入排序',
        'sort.between' => '排序1-9之间',
        'status.require' => '请选择状态',
        'status.between' => '请选择对应状态',
    ];


}