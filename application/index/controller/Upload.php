<?php
namespace app\index\controller;

use app\common\entity\Orders;
use think\Request;
use think\Db;
use app\common\service\AliyunOss\AliOss;
use \think\Log;

class Upload extends Base
{

    public function uploadEditor()
    {
        $uploadModel = new \app\common\service\Upload\Service('image');
        if ($uploadModel->upload()) {
            return json([
                'errno' => 0,
                'data' => [$uploadModel->fileName]
            ]);
        }
        return json([
            'errno' => 1,
            'fail ' => $uploadModel->error
        ]);
    }

    // /**
    //  * 文件上传
    //  */
    // public function uploadImg(){
    //     // 获取表单上传文件 例如上传了001.jpg
    //     $file = request()->file('file');
    //     // 移动到框架应用根目录/public/uploads/ 目录下
    //     $info = $file->validate(['size'=>1024*1024*8,'ext'=>'jpg,png,gif,jpeg'])->move(ROOT_PATH . 'public' . DS . 'uploads');
    //     if($info){
    //         // 成功上传后 获取上传信息
    //         // 输出 jpg
    //         //echo $info->getExtension();
    //         // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
    //         //echo $info->getSaveName();
    //         // 输出 42a79759f284b767dfcb2a0197904287.jpg
    //         //echo $info->getFilename(); 
    //         $data['url'] = "/uploads/".$info->getSaveName();
    //         return json([
    //             'code' => 0,
    //             'msg' => '上传成功!',
    //             'info' => $data
    //         ]);
    //     }else{
    //         // 上传失败获取错误信息
    //         //echo $file->getError();
    //         return json([
    //             'code' => 1,
    //             'msg' => '必须是图片文件并且不大于有8MB'
    //         ]);
    //     }
    // }

  
    public function uploadImg() {
        ini_set ('memory_limit', '600M');  
        try {
           if(!empty($_FILES)){
                   $types =  getimagesize($_FILES['file']['tmp_name']);
             }
   
            $uploadModel = new \app\common\service\Upload\Service('file');
            if ($uploadModel->upload()) {
                $uploaded_type = $_FILES[ 'file' ][ 'type' ];
                $savename = date('Ymd') . '/'. md5(microtime(true));

                if(!empty($types['mime'])){
                    $uploaded_type = $types['mime'];
                }
                if( $uploaded_type == 'image/jpeg' ) {
                    $img = imagecreatefromjpeg( ".".$uploadModel->fileName );
                    imagejpeg( $img, "./uploads/".$savename.".jpg", 100);
                    $atype = ".jpg";
                }
                else {
                    $img = imagecreatefrompng( ".".$uploadModel->fileName );
                    imagepng( $img, "./uploads/".$savename.".png", 9);
                    $atype = ".png";
                }

                unlink(".".$uploadModel->fileName);

                if(!file_exists("./uploads/".$savename.$atype)){

                    return json([
                        'code' => 1,
                        'msg' => "上传失败，请稍后再试"
                    ]);
                }
                // $info['url'] = "/uploads/".$savename.$atype;
                // return json([
                //     'code' => 0,
                //     'msg' => '上传成功!',
                //     'info' => $info
                // ]);

                $savePath = $savename.$atype;
                $AliOss = new AliOss;
                $oss_res = $AliOss->ossUploadImage($savePath, '', true);
                // halt($oss_res);
                if ($oss_res['code'] == 0) {
                    $info['url'] = $oss_res['data'];
                    return json([
                        'code' => 0,
                        'msg' => '上传成功!',
                        'info' => $info
                    ]);
                }else{
                    return json([
                        'code' => 1,
                        'msg' => "上传图片至阿里云服务器失败，请稍后再试"
                    ]);
                }
            }
            return json([
                'code' => 1,
                'msg' => $uploadModel->error
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 1,
                'msg' =>'上传失败！'
            ]);
        }
    }

/**
    public function uploadImg() {

            if(!empty($_FILES)){
                $filename = $_FILES['file']['name'];
            }

        //定义检查的图片类型
        if($filename) {
            $info =explode('.',$filename);

            $ext = array_pop($info);

            if(empty($ext)){
                return  json(['code' => 1, 'msg' => '无效图片格式']);
            }else{
                  $types =  $this->getImgTupe($ext);
                  if(!$types){
                      return  json(['code' => 1, 'msg' => '无效图片格式']);
                  }
            }
        } else {
            return false;
        }

        $uploadModel = new \app\common\service\Upload\Service('file');
        if ($uploadModel->upload()) {

            $info['url'] = $uploadModel->fileName;
            return  json(['code' => 0, 'msg' => '上传成功!','info' => $info]);

        }
        return  json(['code' => 1, 'msg' => $uploadModel->error]);

    }
    public function getImgTupe($type){
        switch ($type){
            case 'jpg':
                return 1;
            case 'png':
                return 1;
            default:
                return 0;
                ;
        }
    }
*/
    public function uploadVideo() {
        $uploadModel = new \app\common\service\Upload\Service('file');
        if ($uploadModel->upload()) {
            return json([
                'errno' => 0,
                'data' => $uploadModel->fileName
            ]);
        }
        return json([
            'errno' => 1,
            'fail ' => $uploadModel->error
        ]);
    }
}