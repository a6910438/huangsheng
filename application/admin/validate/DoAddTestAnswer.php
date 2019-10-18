<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class DoAddTestAnswer extends Validate
{

    protected $rule = [
        'content' => 'require',
        'score' => 'require|number',
        'sort' => 'require|between:1,9',
        'status' => 'require|between:1,2',
    ];

    protected $message = [
        'content.require' => '请输入答案内容',
        'score.require' => '请输入答案分数',
        'score.number' => '请输入答案分数对应数字',
        'sort.require' => '请输入排序',
        'sort.between' => '排序1-9之间',
        'status.require' => '请选择状态',
        'status.between' => '请选择对应状态',
    ];


}