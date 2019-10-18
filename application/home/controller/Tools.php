<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/14
 * Time: 16:41
 */

namespace app\home\controller;

use think\Controller;

class Tools extends Controller
{

    public function QRcode(){
        $code = input('get.code');
        $size = input('?get.size') ? (int)input('get.size') : 192;
        if(empty($code)){
            return \think\Response::create("error", 'json')->code(404);
        }
        $qrCode = new \Endroid\QrCode\QrCode($code);
        $qrCode->setSize($size);
        $qrCode->setMargin(0);
        header('Content-Type: '.$qrCode->getContentType());
        echo $qrCode->writeString();
        exit();
    }

}