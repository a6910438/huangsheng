<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/14
 * Time: 15:06
 */

namespace app\index\validate;


use think\Validate;

class ComSend extends Validate
{
    protected $rule = [
        'content' => 'require',
    ];
    protected $message = [
        'content.require' => '内容不能为空',
    ];
}