<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/17
 * Time: 10:02
 */

namespace app\index\validate;


use think\Validate;

class ComReply extends Validate
{
    protected $rule = [
        'content' => 'require',
        'com_id' => 'require'
    ];
    protected $message = [
        'content.require' => '内容不能为空',
        'com_id.require' => '文章ID不能为空',
    ];
}