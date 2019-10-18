<?php
namespace app\index\controller;

use app\common\entity\Orders;
use think\Request;
use think\Db;
use app\common\service\AliyunOss\AliOss;
use \think\Log;
use think\Controller;

class Uploadimgoss extends Controller
{

    /**
     * 把现有的用户头像上传至阿里云oss
     *
     * @return void
     */
    public function doHeadImgUploadToAliyunOss()
    {
        $user_list = Db::table('user')->field('id,avatar')->select();
        $AliOss = new AliOss;
        $img_data = [];
        Db::startTrans();
        foreach ($user_list as $key => $value) {
            if (strpos($value['avatar'],'uploads') !== false) {
                if (!file_exists('.'.$value['avatar'])) {
                    continue;
                }
                $savePath = substr($value['avatar'], 9); // 获取保存的路径
                $oss_res = $AliOss->ossUploadImage($savePath, '', false);
                if ($oss_res['code'] == 0) {
                    $oss_url = $oss_res['data'];
                    // 替换用户头像
                    $update_res = Db::table('user')->where('id',$value['id'])->setField('avatar', $oss_url);
                    if ($update_res !== false) {
                        Log::write('upload目录下用户头像成功上传图片至阿里云服务器,访问url为：' . $oss_url,'info');
                        continue;
                    }
                }else{
                    Db::rollback();
                    Log::write('upload目录下用户头像上传至阿里云服务器失败','error');
                    break;
                }
            } elseif ($value['avatar'] == '/static/images/head_img.png') {
                if (!file_exists('.'.$value['avatar'])) {
                    continue;
                }
                if (!isset($img_data['url'])) { // 是否已定义url
                    $savePath = substr($value['avatar'], 8); // 获取保存的路径
                    $oss_res = $AliOss->ossUploadImage($savePath, '', false, true);
                    if ($oss_res['code'] == 0) {
                        $oss_url = $oss_res['data'];
                        // 替换用户头像
                        $update_res = Db::table('user')->where('id',$value['id'])->setField('avatar', $oss_url);
                        if ($update_res !== false) {
                            $img_data['url'] = $oss_res['data'];
                            Log::write('static目录下用户头像成功上传图片至阿里云服务器,访问url为：' . $oss_url,'info');
                            continue;
                        }
                    }else{
                        Db::rollback();
                        Log::write('static目录下用户头像上传至阿里云服务器失败','error');
                        break;
                    }
                }else{
                    // 已定义则直接调用
                    $oss_url = $img_data['url'];
                    // 替换用户头像
                    $update_res = Db::table('user')->where('id',$value['id'])->setField('avatar', $oss_url);
                    if ($update_res !== false) {
                        Log::write('static目录下用户头像成功更新,访问url为：' . $oss_url,'info');
                        continue;
                    }
                }
            } else {
                continue;
            }
        }
        Db::commit();
    }
}