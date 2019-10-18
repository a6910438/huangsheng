<?php

namespace app\common\model;

use think\console\Output;
use think\Log;

class SendSms
{

    public $output;
    private $mobile, $name, $templateCode = '';

    public function __construct($mobile = "", $name = "", int $type = 1)
    {
        $this->mobile = $mobile;
        $this->name = $name;
        switch ($type) {
            case 1:
                $this->templateCode = '472141';
                break;
            case 2:
                $this->templateCode = '491636';
                break;
        };
    }

    public function set_mobile($mobile)
    {
        $this->mobile = $mobile;
    }

    public function set_name($name)
    {
        $this->name = $name;
    }

    public function set_type(int $type)
    {
        switch ($type) {
            case 1:
                $this->templateCode = '472141';
                break;
            case 2:
                $this->templateCode = '491636';
                break;
        };
    }

    public function send()
    {
        if (!$this->templateCode) {
            return false;
        }
        set_time_limit(0);
        header("Content-Type:text/html;charset=utf-8");
        $sid = 'c14b65146fb5006ddec15c9394433c43';
        $AppID = 'aea850e3cf064c279bbd8cadb821b2ff';
        $Token = '5162ca2d7062204dd781064cfa561b26';

        if ($this->templateCode == '472141') {
            $url = "https://open.ucpaas.com/ol/sms/sendsms/?sid={$sid}&token={$Token}&appid={$AppID}&templateid={$this->templateCode}&param={$this->name}&mobile={$this->mobile}";
        } elseif ($this->templateCode == '491636') {
            $url = "https://open.ucpaas.com/ol/sms/sendsms_batch/?sid={$sid}&token={$Token}&appid={$AppID}&templateid={$this->templateCode}&param={$this->name}&mobile={$this->mobile}";
        } else {
            return false;
        }

        $body_json = array(
            'sid' => $sid,
            'token' => $Token,
            'appid' => $AppID,
            'templateid' => $this->templateCode,
            'param' => $this->name,
            'mobile' => $this->mobile,

        );

        Log::info('【短信发送到云之讯】：[ ' . date('Y-m-d H:i:s', time()) . ' ] 手机号:' . $this->mobile . ': ' . json_encode($body_json));
        $body = json_encode($body_json);
        try {
            $data = $this->getResult($url, $body, 'post');

            if ($data) {
                $arr = json_decode($data, true);
                if ($arr['code'] == '000000') {
                    Log::info('短信发送成功,发送手机号: ' . $this->mobile . ',运营商返回数据：' . $data);
                    return true;
                } elseif ($arr['code'] == '105147') {
                    Log::info('短信发送成功,发送手机号: ' . $this->mobile . ',运营商返回数据：' . $data);
                    return true;
                } else {
                    Log::info('短信发送失败,发送手机号: ' . $this->mobile . ',运营商返回数据：' . $data);
                    return false;
                }
            } else {
                Log::info('短信发送失败,返回空值,发送手机号: ' . $this->mobile . ',运营商返回数据：' . $data);
                return false;
            }


        } catch (\Exception $e) {
            //addMy_log('报错发送短信失败',$data);
            Log::info('短信提醒发送异常：' . $e->getMessage());
            return false;
        }

    }

    private function getResult($url, $body = null, $method)
    {
        $data = $this->connection($url, $body, $method);
        if (isset($data) && !empty($data)) {
            $result = $data;
        } else {
            $result = '';
        }
        return $result;
    }

    private function connection($url, $body, $method)
    {
        if (function_exists("curl_init")) {
            $header = array(
                'Accept:application/json',
                'Content-Type:application/json;charset=utf-8',
            );
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            if ($method == 'post') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
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
            $headers[] = 'Accept:application/json';
            $headers['header'] = array();
            $headers['header'][] = 'Content-Type:application/json;charset=utf-8';

            if (!empty($body)) {
                $headers['header'][] = 'Content-Length:' . strlen($body);
                $headers['content'] = $body;
            }

            $opts['http'] = $headers;
            $result = file_get_contents($url, false, stream_context_create($opts));
        }
        return $result;
    }


    public function sendDepositSms($mobile, $number, $time)
    {
        set_time_limit(0);
        header("Content-Type:text/html;charset=utf-8");
        $sid = 'c14b65146fb5006ddec15c9394433c43';
        $AppID = 'aea850e3cf064c279bbd8cadb821b2ff';
        $Token = '5162ca2d7062204dd781064cfa561b26';
        $templateid = '493612';
        $url = 'https://open.ucpaas.com/ol/sms/sendsms';


        $body_json = array(
            'sid' => $sid,
            'token' => $Token,
            'appid' => $AppID,
            'templateid' => $templateid,
            'param' => $number . ',' . $time,
            'mobile' => $mobile,

        );

        $body = json_encode($body_json);
        try {
            $data = $this->getResult($url, $body, 'post');

            if ($data) {
                $arr = json_decode($data, true);
                if ($arr['code'] == '000000') {
                    return true;
                } elseif ($arr['code'] == '105147') {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }


        } catch (\Exception $e) {
            addMy_log('报错发送短信失败', $data);
            return false;
        }

    }


    public function sendExchangeTimesWarnSms($code, $nickname, $time, $number, $total, $mobile)
    {
        $this->output = new Output;
        set_time_limit(0);
        header("Content-Type:text/html;charset=utf-8");
        $sid = 'c14b65146fb5006ddec15c9394433c43';
        $AppID = 'aea850e3cf064c279bbd8cadb821b2ff';
        $Token = '5162ca2d7062204dd781064cfa561b26';
        $templateid = '494378';
        $url = 'https://open.ucpaas.com/ol/sms/sendsms';


        $body_json = array(
            'sid' => $sid,
            'token' => $Token,
            'appid' => $AppID,
            'templateid' => $templateid,
            'param' => $code . ',' . $nickname . ',' . $time . ',' . $number . ',' . $total,
            'mobile' => $mobile,

        );
        $this->output->writeln($code . ',' . $nickname . ',' . $time . ',' . $number . ',' . $total . ',' . $mobile);

        $body = json_encode($body_json);
        try {
            $data = $this->getResult($url, $body, 'post');
            $this->output->writeln($data);

            if ($data) {
                $arr = json_decode($data, true);
                if ($arr['code'] == '000000') {
                    return true;
                } elseif ($arr['code'] == '105147') {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }


        } catch (\Exception $e) {
            addMy_log('报错发送短信失败', $data);
            return false;
        }

    }

    public function sendWithdrawSms($nickname, $number, $time, $mobile)
    {
        $this->output = new Output;
        set_time_limit(0);
        header("Content-Type:text/html;charset=utf-8");
        $sid = 'c14b65146fb5006ddec15c9394433c43';
        $AppID = 'aea850e3cf064c279bbd8cadb821b2ff';
        $Token = '5162ca2d7062204dd781064cfa561b26';
        $templateid = '495688';
        $url = 'https://open.ucpaas.com/ol/sms/sendsms';


        $body_json = array(
            'sid' => $sid,
            'token' => $Token,
            'appid' => $AppID,
            'templateid' => $templateid,
            'param' => $nickname . ',' . $number . ',' . $time,
            'mobile' => $mobile,

        );
        $body = json_encode($body_json);
        try {
            $data = $this->getResult($url, $body, 'post');
            $this->output->writeln($data);

            if ($data) {
                $arr = json_decode($data, true);
                if ($arr['code'] == '000000') {
                    return true;
                } elseif ($arr['code'] == '495688') {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }


        } catch (\Exception $e) {
            addMy_log('报错发送短信失败', $data);
            return false;
        }

    }

    public function sendTibiSms($mobile, $number, $templateid)
    {
        set_time_limit(0);
        header("Content-Type:text/html;charset=utf-8");
        $sid = 'c14b65146fb5006ddec15c9394433c43';
        $AppID = 'aea850e3cf064c279bbd8cadb821b2ff';
        $Token = '5162ca2d7062204dd781064cfa561b26';
        // $templateid = '494378';
        $url = 'https://open.ucpaas.com/ol/sms/sendsms';


        $body_data = [
            'sid' => $sid,
            'token' => $Token,
            'appid' => $AppID,
            'templateid' => $templateid,
            'param' => "$number",
            'mobile' => $mobile,
        ];

        $body = json_encode($body_data);
        try {
            $data = $this->getResult($url, $body, 'post');

            if ($data) {
                $arr = json_decode($data, true);
                if ($arr['code'] == '000000') {
                    Log::info('提币短信提醒发送成功,发送手机号: ' . $mobile . ',提币数量: ' . $number . ',运营商返回数据：' . $data);
                    return true;
                } elseif ($arr['code'] == '105147') {
                    Log::info('提币短信提醒发送成功,发送手机号: ' . $mobile . ',提币数量: ' . $number . ',运营商返回数据：' . $data);
                    return true;
                } else {
                    Log::info('提币短信提醒发送失败,发送手机号: ' . $mobile . ',提币数量: ' . $number . ',运营商返回数据：' . $data);
                    return false;
                }
            } else {
                Log::info('提币短信提醒发送失败,返回空值,发送手机号: ' . $mobile . ',提币数量: ' . $number . ',运营商返回数据：' . $data);
                return false;
            }


        } catch (\Exception $e) {
            // addMy_log('报错发送短信失败', $data);
            Log::info('提币短信提醒发送异常：' . $e->getMessage());
            return false;
        }

    }


    public function sendPayOrderTimeWarnSms($name, $time, $mobile)
    {
        $this->output = new Output;
        set_time_limit(0);
        header("Content-Type:text/html;charset=utf-8");
        $sid = 'c14b65146fb5006ddec15c9394433c43';
        $AppID = 'aea850e3cf064c279bbd8cadb821b2ff';
        $Token = '5162ca2d7062204dd781064cfa561b26';
        $templateid = '498088';
        $url = 'https://open.ucpaas.com/ol/sms/sendsms';


        $body_json = array(
            'sid' => $sid,
            'token' => $Token,
            'appid' => $AppID,
            'templateid' => $templateid,
            'param' => $name . ',' . $time,
            'mobile' => $mobile,

        );

        $body = json_encode($body_json);
        try {
            $data = $this->getResult($url, $body, 'post');
            if ($data) {
                $arr = json_decode($data, true);
                if ($arr['code'] == '000000') {
                    return true;
                } elseif ($arr['code'] == '498088') {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }


        } catch (\Exception $e) {
            addMy_log('报错发送短信失败', $data);
            return false;
        }

    }
}