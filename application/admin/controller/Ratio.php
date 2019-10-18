<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/20
 * Time: 14:52
 */

namespace app\admin\controller;


use think\Db;

class Ratio
{
    //http://47.75.169.53/web4/index/dogegate
    public function updRatio(){
        $url = 'http://47.75.169.53/web4/index/dogegate';
        $res = $this->http_request($url);
        $arr = json_decode($res);
//        var_dump($arr);die;

        $data = [
            'eos' => 1/ ($arr[1]->rate) ,
            'eth' => 1/ ($arr[0]->rate) ,
            'btc' => 1/ ($arr[2]->rate) ,
            'create_time' => time()
        ];
        $data_eos = [
            'rate_percent' => $arr[1]->rate_percent,
            'trend' => $arr[1]->trend,
            'update_time' => time()
        ];
        $data_eth = [
            'rate_percent' => $arr[0]->rate_percent,
            'trend' => $arr[0]->trend,
            'update_time' => time()
        ];
        $data_btc = [
            'rate_percent' => $arr[2]->rate_percent,
            'trend' => $arr[2]->trend,
            'update_time' => time()
        ];
        $ins = Db::table('quotation')->where('id',1)->update($data);
        Db::table('proportion')->where('name','eos')->update($data_eos);
        Db::table('proportion')->where('name','eth')->update($data_eth);
        Db::table('proportion')->where('name','btc')->update($data_btc);

        echo 'ok';
    }


    #发送请求
    protected function http_request($url, $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (! empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

}