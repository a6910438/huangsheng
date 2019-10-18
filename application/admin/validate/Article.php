<?php
namespace app\admin\validate;

use think\Request;
use think\Validate;

class Article extends Validate
{

    protected $rule = [
        'title' => 'require',
        'category' => 'require',
        'content' => 'require',
    ];

    protected $message = [
        'title.require' => '标题不能为空',
        'content.require' => '内容不能为空',
        'category.require' => '请选择分类',
    ];


}