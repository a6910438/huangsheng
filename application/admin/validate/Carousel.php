<?php

namespace app\admin\validate;

use think\Request;
use think\Validate;

class Carousel extends Validate {

    protected $rule = [
        'path' => 'require',
    ];
    protected $message = [
        'path.require' => '请上传图片',
    ];

}
