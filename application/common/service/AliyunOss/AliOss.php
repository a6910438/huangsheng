<?php
namespace app\common\service\AliyunOss;
use think\Controller;
 
require_once APP_PATH . '/../extend/aliyun_oss/autoload.php';
use OSS\Core\OssException;
use OSS\OssClient;
use \think\Log;

class AliOss extends Controller
{
    // AppKey 参数
    protected $Access_Key = '';

    // AppSecret 参数
    protected $Secret_Key = '';

    // 存储空间名称
    protected $bucket     = '';

    // 节点信息
    protected $endpoint   = '';

    // 阿里云访问url
    protected $url        = '';

    /**
     * 构造函数：初始化类
     */
    public function __construct(){
        $this->init();
    }

    /**
     * 初始化参数
     *
     * @return void
     */
    public function init(){
        $this->Access_Key   = config('aliyun_oss.AppKeyId');
        $this->Secret_Key   = config('aliyun_oss.AppKeySecret');
        $this->bucket       = config('aliyun_oss.Bucket');
        $this->endpoint     = config('aliyun_oss.EndPoint');
        $this->url          = config('aliyun_oss.url');
    }

    /**
     * 阿里云oss上传图片接口
     *
     * @param string $savePath 保存的文件名
     * @param string $category 保存的目录
     * @param boolean $isunlink 是否删除本地图片源文件
     * @return void
     */
    public function ossUploadImage($savePath,$category = '',$isunlink = false, $is_static = false) {
        try {
            Log::write('AppKeyId ：' . $this->Access_Key,'info');
            // 实例化
            $ossClient = new OssClient($this->Access_Key, $this->Secret_Key, $this->endpoint);
            // 判断bucketname是否存在，不存在就去创建
            if( !$ossClient->doesBucketExist($this->bucket)){
                $ossClient->createBucket($this->bucket);
            }
            // 存储目录
            $category=empty($category) ? $this->bucket : $category;
            // 转义字符
            $savePath = str_replace("\\","/",$savePath);
            //截取文件后缀名如 (.jpg,.png)
            $format = strrchr($savePath, '.');
            //sha1加密 生成文件名 连接后缀
            $fileName = $category.'/'.sha1(date('YmdHis', time()) . uniqid()) . $format; //想要保存文件的名称
            if ($is_static) {
                $file = './static/'.$savePath;//文件路径，必须是本地的。
            }else{
                $file = './uploads/'.$savePath;//文件路径，必须是本地的。
            }
            //执行阿里云上传
            $result = $ossClient->uploadFile($this->bucket, $fileName, $file);
            Log::write('图片上传阿里云服务器返回结果：' . json_encode($result),'info');
            //图片地址:$result['info']['url']
            if ($isunlink == true){
                if (!$is_static) {
                    unlink($file);
                }
            }
            $oss_url = $this->url."/".$fileName; // 注：阿里云oss bucket acl必须设置为公共读
            // 记录上传日志记录
            Log::write('成功上传图片至阿里云服务器,访问url为：' . $oss_url,'info');
            return ['code' => 0,'msg' => '图片上传阿里云服务器成功','data' => $oss_url];
        } catch (OssException $e) {
            Log::write('图片上传阿里云服务器发生异常,异常信息：' . $e->getMessage(),'error');
            return ['code' => 1,'msg' => $e->getMessage(),'data' => ''];
        }
    }

}