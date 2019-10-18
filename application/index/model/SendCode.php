<?php

namespace app\index\model;

use think\Session;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class SendCode
{
    public $mobile;
    public $code;
    public $type;

    private $accessKeyId, $accessSecret,$TemplateCode,$SignName, $error;

    public function __construct($mobile, $type)
    {

        $this->mobile = $mobile;
        $this->type = $type;

        //AccessKeyID：LTAIAUuoW2vRrboZ AccessKeySecret：ituoNfzqwPRkxqYWa8K9u08o48tgqu 
        $this->accessKeyId = 'LTAINd96RUWw3vFZ';//LTAIAUuoW2vRrboZ
        $this->accessSecret = 'Wv85E02Qcq5xQqRRs7FvgRnfrlel4B';
        $this->SignName = 'Magnate';
        switch ($type){
            case 'register':
            //注册用户
            $this->TemplateCode = 'SMS_170445725';
            break;
            case 'change-password':
            case 'change-pay-password':
            //修改密码
            $this->TemplateCode = 'SMS_170445724';
            break;
            case 'appeal':
            //身份认证
            $this->TemplateCode = 'SMS_170445728';
            break;
            default:
            //其他
            //$this->TemplateCode = 'SMS_170445728';
            break;
        }

    }

    /**
     * 随机划一条横线
     * @param type $img
     * @return type
     */
    private function sendCode() {

        usleep(100000);

        AlibabaCloud::accessKeyClient($this->accessKeyId, $this->accessSecret)
            ->regionId('cn-hangzhou') // replace regionId as you need
            ->asDefaultClient();
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->options([
                    'query' => [
                        'PhoneNumbers' => $this->mobile,
                        'TemplateCode' => $this->TemplateCode,
                        'SignName' => $this->SignName,
                        'TemplateParam' => "{\"code\":\"".$this->code."\"}"
                    ],
                ])
                ->request()->toArray();
            if($result['Code'] == 'OK'){
                return true;
            }else{
                //print_r($result);
                return false;
            }
        } catch (ClientException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
            return false;
        } catch (ServerException $e) {
            echo $e->getErrorMessage() . PHP_EOL;
            return false;
        }
        return true;

    }

    public function send()
    {
        $this->setCode();
        //调用发生接口
        if ($this->sendCode()) {
            $this->saveCode(); //保存code值到session值
            return true;
        }
        return false;
    }

    public function getSessionName()
    {
        $sessionNames = [
            'register' => 'register_code_',
            'change-password' => 'change-password_',
            'change-pay-password' => 'change-pay-password_',
            'appeal' => 'appealta_'
        ];

        return $sessionNames[$this->type] . $this->mobile;
    }

    private function getCode()
    {
        return Session::get($this->getSessionName());
    }

    private function setCode()
    {
        $this->code = mt_rand(100000, 999999);
    }

    private function saveCode()
    {
        Session::set($this->getSessionName(), $this->code);
    }

    /*
    private function sendCode_backup()
    {

        $mobile = $this->mobile;
        $code = $this->code;
        header("Content-Type:text/html;charset=utf-8");
        $templateid = '457421';
//        $templateid = '457422';

		$sid = '6c701a507e90b511d56627707e2e4c53';
        $AppID  = '53e797b928844536af4e99dfb16f451e';
        $Token   = 'b490818b2672e70b0da393fd4bd232cd';


        $body_json = array(
            'sid'=>$sid,
            'token'=>$Token,
            'appid'=>$AppID,
            'templateid'=>$templateid,
            'param'=>$code,
            'mobile'=>$mobile,

        );

        $url = "https://open.ucpaas.com/ol/sms/sendsms/?sid={$sid}&token={$Token}&appid={$AppID}&templateid={$templateid}&param={$code}&mobile={$mobile}";

        $body = json_encode($body_json);
        $data = $this->getResult($url, $body,'post');

        return true;
    }


    private function getResult($url, $body = null, $method)
    {
        $data = $this->connection($url,$body,$method);
        if (isset($data) && !empty($data)) {
            $result = $data;
        } else {
            $result = '没有返回数据';
        }
        return $result;
    }

    private function connection($url, $body,$method)
    {
        if (function_exists("curl_init")) {
            $header = array(
                'Accept:application/json',
                'Content-Type:application/json;charset=utf-8',
            );
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            if($method == 'post'){
                curl_setopt($ch,CURLOPT_POST,1);
                curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            $opts = array();
            $opts['http'] = array();
            $headers = array(
                "method" => strtoupper($method),
            );
            $headers[]= 'Accept:application/json';
            $headers['header'] = array();
            $headers['header'][]= 'Content-Type:application/json;charset=utf-8';

            if(!empty($body)) {
                $headers['header'][]= 'Content-Length:'.strlen($body);
                $headers['content']= $body;
            }

            $opts['http'] = $headers;
            $result = file_get_contents($url, false, stream_context_create($opts));
        }
        return $result;
    }
    */


    public function checkCode($code)
    {
        $trueCode = $this->getCode();

        if ($trueCode == $code) {
            Session::delete($this->getSessionName());
            return true;
        }

        return false;
    }
    public function tmpgetCode(){
        return $this->getCode();
    }

    public function modgetCode(){
        return $this->getCode();
    }

}