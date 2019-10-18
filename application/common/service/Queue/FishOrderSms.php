<?php

namespace app\common\service\Queue;

use think\Db;
use think\queue\Job;
/* 短信通知 */

use app\common\model\SendSms;
use think\Log;
use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class FishOrderSms
{
    protected $send_sms;

    public function __construct()
    {
        $this->send_sms = new SendSms();
        $this->send_sms->set_type(2);
    }

    /**
     * fire方法是消息队列默认调用的方法
     * @param Job $job 当前的任务对象
     * @param array|mixed $data 发布任务时自定义的数据
     */
    public function fire(Job $job, $data)
    {
        Log::info('【 抢购订单短信通知 】：开始Queued队列Job查询');
        try {
            // // 有些消息在到达消费者时,可能已经不再需要执行了
            // $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
            // if (!$isJobStillNeedToBeDone) {
            //     $job->delete();
            //     return;
            // }

            $isJobDone = $this->doSendSmsJob($data);

            if ($isJobDone) {
                // 如果任务执行成功， 记得删除任务
                $job->delete();
                Log::info('【抢购订单短信通知】：已成功消费该条订单消息：' . json_encode($data));
            } else {
                if ($job->attempts() > 3) {
                    //通过这个方法可以检查这个任务已经重试了几次了
                    Log::info('【抢购订单短信通知】：该条订单消息发送短信已重试3次：' . json_encode($data));
                    $job->delete();

                    // 也可以重新发布这个任务
                    //print("<info>Hello Job will be availabe again after 2s."."</info>\n");
                    //$job->release(2); //$delay为延迟时间，表示该任务延迟2秒后再执行
                }
            }
        } catch (\Exception $e) {
            Log::error('【抢购订单短信通知】：读取消息订单队列发生异常：' . $e->getMessage());
        }
        Log::info('【 抢购订单短信通知 】：结束Queued队列Job查询');
    }

    // /**
    //  * 有些消息在到达消费者时,可能已经不再需要执行了
    //  * @param array|mixed $data 发布任务时自定义的数据
    //  * @return boolean                 任务执行的结果
    //  */
    // private function checkDatabaseToSeeIfJobNeedToBeDone($data)
    // {
    //     // print_r($data);
    //     $is_send = Db::table('fish_order')->where('id', '=', $data['order_id'])->value('is_send');
    //     if ($is_send == 1) {
    //         Log::info('【抢购订单短信通知】：捕获一条已消费队列消息：' . json_encode($data));
    //         return false;
    //     }
    //     return true;
    // }

    /**
     * 根据消息中的数据处理发送短信请求
     */
    private function doSendSmsJob($data)
    {
        // 云之讯短信
        $this->send_sms->set_mobile($data['mobile']);
        $this->send_sms->set_name($data['name']);
        $send_result1 = $this->send_sms->send();

        // 阿里云短信
        $send_result2 = $this->sendCode($data['mobile'], $data['name']);

        if ($send_result1 || $send_result2) {
            Log::info('【 抢购订单短信通知 】：发送短信成功，消息队列数据：'.json_encode($data));
            return true;
        }else{
            Log::info('【 抢购订单短信通知 】：发送短信失败，消息队列数据：'.json_encode($data));
            return false;
        }
    }


    /**
     * 阿里云发送短信
     * @param type $img
     * @return type
     */
    private function sendCode($mobile, $name)
    {
        $house_name = explode(',',$name);
        $house_name = $house_name[0];

        AlibabaCloud::accessKeyClient('LTAINd96RUWw3vFZ', 'Wv85E02Qcq5xQqRRs7FvgRnfrlel4B')
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
                        'PhoneNumbers' => $mobile,
                        'TemplateCode' => "SMS_170836600",
                        'SignName' => "戈牛科技",
                        'TemplateParam' => json_encode(['name' => $house_name])
                    ],
                ])
                ->request()->toArray();
            if ($result['Code'] == 'OK') {
                Log::info('【 抢购订单短信通知 】：阿里云发送短信成功，返回数据结果：'.json_encode($result));
                return true;
            } else {
                //print_r($result);
                Log::info('【 抢购订单短信通知 】：阿里云发送短信失败，返回数据结果：'.json_encode($result));
                return false;
            }
        } catch (ClientException $e) {
            return false;
        } catch (ServerException $e) {
            return false;
        }
        return true;

    }
}
