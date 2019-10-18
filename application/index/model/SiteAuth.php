<?php

namespace app\index\model;

use app\common\entity\Config;
use app\common\entity\Linelist;
use app\common\entity\StoreLog;
use app\common\entity\SystemConfig;
use app\common\entity\Withdraw;

class SiteAuth {

    //判断站点是否开启
    public static function checkSite() {
        $switch = SystemConfig::where('status',1)->find();
        if ($switch) {
            return $switch['content'] ? : '站点关闭';
        }
        return true;
    }

    //判断交易市场是否开启
    public static function checkMarket() {
        $switch = Config::getValue('web_switch_market');
        if (!$switch) {
            return '交易市场已关闭';
        }
        $startTime = Config::getValue('web_start_time') ? : 0;
        $endTime = Config::getValue('web_end_time') ? : 0;
        if ($startTime && time() < strtotime(date('Y-m-d') . ' ' . $startTime)) {

            return '市场开启时间为' . $startTime . '-' . $endTime;
        }

        if ($endTime && time() > strtotime(date('Y-m-d') . ' ' . $endTime)) {

            return '市场开启时间为' . $startTime . '-' . $endTime;
        }

        return true;
    }

    //php alert
    public function alert($message, $jumpUrl = '') {
        if ($jumpUrl) {
            $js = "function(){ window.location.href = '{$jumpUrl}'}";
        } else {
            $js = "''";
        }
        $html = <<<EOF
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale=1,maximum-scale=1,user-scalable=no" />
        <title>温馨提示</title>
        <link href="/static/css/mui.min.css" rel="stylesheet" />
        
        <script src="/static/js/mui.min.js"></script>
        <script type="text/javascript" charset="utf-8">
            mui.init();
        </script>
    </head>
    <body>
        <script>
            window.onload = function(){
                mui.alert('{$message}','温馨提示',$js);
            }
        </script>
    </body>
</html>
EOF;
        echo $html;
    }

    /**
     * 判断交易市场是否开启
     */
    public function checkAuth() {
        $startTime = Config::getValue('web_start_time');
        $endTime = Config::getValue('web_end_time');
        $startTime = strtotime(date('Y-m-d') . ' ' . $startTime);
        $endTime = strtotime(date('Y-m-d') . ' ' . $endTime);
        if (time() < $startTime) {
            return sprintf('交易市场开市时间为%s-%s', $startTime, $endTime);
        }
        if (time() > $endTime) {
            return sprintf('交易市场开市时间为%s-%s', $startTime, $endTime);
        }
        return true;
    }
    /**
     * 判断规则
     */
    public static function blockUser($uid)
    {
        $userInfo = \app\common\entity\User::where('id',$uid)->find();
        if($userInfo['last_store_time'] < strtotime("-3 day")){
            $info = Linelist::where('uid',$uid)->whereIn('status',[1,2,5])->find();
            $withdraw = Withdraw::where('uid',$uid)->whereIn('status',[1,2])->find();
            $store_log = StoreLog::where('uid',$uid)->where('status',1)->find();
            if(!$info && !$withdraw && !$store_log){
                \app\common\entity\User::where('id',$uid)->update(['status'=>-1,'forbidden_time'=>time()]);
            }
        }

    }

}
