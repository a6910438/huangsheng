<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/4
 * Time: 18:04
 */

namespace app\index\model;
use app\common\PHPMailer\PHPMailer;
use think\Session;

class SendMail
{

    public $email;
    public $code;
    public $type;

    public function __construct($email, $type)
    {
        $this->email = $email;
        $this->type = $type;
    }

    public function send()
    {
        $this->setCode();
        //调用发生接口
        $send = $this->sendCode();
        if ($send === true) {
            $this->saveCode(); //保存code值到session值
            return true;
        }
        return $send;
    }

    public function getSessionName()
    {
        $sessionNames = [
            'register' => 'register_code_',
            'change-password' => 'change-password_',
            'market' => 'market_sale_',
            'market_sale' => 'market_sale_ta_'
        ];

        return $sessionNames[$this->type] . $this->email;
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
    

    public function checkCode($code)
    {
        $trueCode = $this->getCode();

        if ($trueCode == $code) {
            Session::delete($this->getSessionName());
            return true;
        }

        return false;
    }

    //发送邮箱验证码
    public function sendCode()
    {
        $toemail = $this->email;//定义收件人的邮箱
        $code = $this->code;

        $mail = new PHPMailer();

        $mail->isSMTP();// 使用SMTP服务
        $mail->CharSet = "utf8";// 编码格式为utf8，不设置编码的话，中文会出现乱码
//        $mail->Host = "smtp.gmail.com";// 发送方的SMTP服务器地址
        $mail->Host = "smtp.163.com";// 发送方的SMTP服务器地址
        $mail->SMTPAuth = true;// 是否使用身份验证
//        $mail->Username = "system@yekes.org";// google邮箱
//        $mail->Password = "v5664TAU9Jjcxm9";// 邮箱密码
        $mail->Username = "demonyxp@163.com";// 发送方的163邮箱用户名，就是你申请163的SMTP服务使用的163邮箱
        $mail->Password = "yxp1218";// 发送方的邮箱密码，注意用163邮箱这里填写的是“客户端授权密码”而不是邮箱的登录密码！
        $mail->SMTPSecure = "ssl";// 使用ssl协议方式
        $mail->Port = 465;// 163邮箱的ssl协议方式端口号是465/994

        $mail->setFrom("demonyxp@163.com", "Mailer");// 设置发件人信息，如邮件格式说明中的发件人，这里会显示为Mailer(xxxx@163.com），Mailer是当做名字显示
        $mail->addAddress($toemail, $toemail);// 设置收件人信息，如邮件格式说明中的收件人，这里会显示为Liang(yyyy@163.com)
        $mail->addReplyTo("demonyxp@163.com", "Reply");// 设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址


        $mail->Subject = "邮箱验证码";// 邮件标题
        $mail->Body = "邮件内容是,您的验证码是：{$code}。";// 邮件正文


        if (!$mail->send()) {// 发送邮件
//            echo "Message could not be sent.";
//            echo "Mailer Error: " . $mail->ErrorInfo;// 输出错误信息
            return $mail->ErrorInfo;
        } else {
            return true;
        }

    }
}