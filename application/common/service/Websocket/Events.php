<?php
namespace app\common\service\Websocket;

use GatewayWorker\Lib\Gateway;
use workerman\MySQL\Connection;
use Workerman\Lib\Timer;
use think\Db;

class Events
{
    /**
     * 新建一个类的静态成员，用来保存数据库实例
     */
    public static $db = null;
    /**
     * 当businessWorker进程启动时触发。每个进程生命周期内都只会触发一次
     * 可以在这里为每一个businessWorker进程做一些全局初始化工作，例如设置定时器，初始化redis等连接等
     * 注意：$businessworker->onWorkerStart和Event::onWorkerStart不会互相覆盖，如果两个回调都设置则都会运行
     * @param  object $businessWorker businessWorker进程实例
     */
    public static function onWorkerStart($businessWorker)
    {
        ini_set('default_socket_timeout', -1); //redis不超时
        // 将db实例存储在全局变量中(也可以存储在某类的静态成员中)
        self::$db = new \Workerman\MySQL\Connection('localhost', '3306', 'root', 'galaxycoin8866', 'vip_bittrust_big');
        // Timer::add(5, function () {
        //     $order_ids = array("163", "166", "168", "180", "170");
        //     $rand_key = array_rand($order_ids);
        //     $datas = [
        //         'order_id' => $order_ids[$rand_key],
        //         'user_id'  => 30,
        //         'unread_message_num' => rand(1, 4),
        //         'order_status' => rand(0, 4),
        //         'order_status_desc' => '已取消',
        //         'type' => 'otc_trade_socket',
        //         'is_delete' => rand(0, 1),
        //     ];
        //     Gateway::sendToAll(json_encode($datas));
        // });
        // global $redis;
        // $redis_config = config('redis.redis');
        // $redis = LinkRedis::getInstance($redis_config);
    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     *
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据
        Gateway::sendToClient($client_id, "Connect Success: $client_id\r\n");
        // // 向所有人发送
        // Gateway::sendToAll("$client_id login\r\n");
    }

    /**
     * 当客户端连接上gateway完成websocket握手时触发的回调函数
     * @param  int $client_id client_id固定为20个字符的字符串，用来全局标记一个socket连接，每个客户端连接都会被分配一个全局唯一的client_id
     * @param  string $data   websocket握手时的http头数据，包含get、server等变量
     */
    public static function onWebSocketConnect($client_id, $data)
    {
        // 可以在这里判断连接来源是否合法，不合法就关掉连接
        // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket链接
        // if($_SERVER['HTTP_ORIGIN'] != 'http://kedou.workerman.net')
        // {
        //     $connection->close();
        // }
        // onWebSocketConnect 里面$_GET $_SERVER是可用的
        // var_dump($_GET, $_SERVER);
        // var_export($data);
        // if (!isset($data['get']['token'])) {
        //      Gateway::closeClient($client_id);
        // }
    }

    /**
     * 当客户端发来数据(Gateway进程收到数据)后触发的回调函数
     * @param int $client_id 全局唯一的客户端socket连接标识
     * @param mixed $message 完整的客户端请求数据，数据类型取决于Gateway所使用协议的decode方法返的回值类型
     */
    public static function onMessage($client_id, $message)
    {
        // global $redis;
        //解析json数据
        $commend = json_decode($message, true);
        $type = $commend['type'];
        if($type !== 'robMessage')
        {
            Gateway::closeClient($client_id, "unknown commend\n");
            return;
        }


        // $map['fo.bu_id'] = ['>', 0];
        // $map['fo.is_send'] = 0;
        // $map['fo.types'] = ['>', 0];
        // $map['fo.over_time'] = ['>', 0];

        // $msglist = Db::table('fish_order')
        //     ->alias('fo')
        //     ->join('user u', 'u.id = fo.bu_id', 'INNER')
        //     ->join('fish f', 'f.id = fo.f_id')
        //     ->join('user_invite_code uic', 'uic.user_id = u.id', 'INNER')
        //     ->join('bathing_pool bp', 'bp.id = f.pool_id')
        //     ->where($map)
        //     ->field('fo.id order_id,u.mobile,uic.invite_code user_code,bp.name,bp.id bpid,fo.over_time')
        //     ->order('fo.create_time desc')
        //     ->limit(10)
        //     ->fetchSql(true)
        //     ->select();
        // dump($msglist);

        // 获取最新的10条抢购订单数据返回
        $rob_sql = "SELECT
                    fo.id order_id,
                    `u`.`mobile`,
                    uic.invite_code user_code,
                    `bp`.`name`,
                    bp.id bpid,
                    `fo`.`over_time`,
                    `fo`.`create_time`  
                FROM
                    `fish_order` `fo`
                    INNER JOIN `user` `u` ON `u`.`id` = `fo`.`bu_id`
                    INNER JOIN `fish` `f` ON `f`.`id` = `fo`.`f_id`
                    INNER JOIN `user_invite_code` `uic` ON `uic`.`user_id` = `u`.`id`
                    INNER JOIN `bathing_pool` `bp` ON `bp`.`id` = `f`.`pool_id` 
                WHERE
                    `fo`.`bu_id` > 0 
                    AND `fo`.`types` > 0 
                    AND `fo`.`over_time` > 0 
                ORDER BY
                    `fo`.`create_time` DESC 
                    LIMIT 10";
        $ret = self::$db->query($rob_sql);
        $rob_data = [];
        foreach ($ret as $key => $value) {
            $rob_data[$key] = '恭喜玩家ID: '.$value['user_code'].' 成功抢购'.$value['name'].'一套！';
        }

        // 打印结果
        // return Gateway::sendToClient($client_id, var_export($rob_data, true));
        return Gateway::sendToClient($client_id, json_encode($rob_data));
    }


    /**
     * 客户端与Gateway进程的连接断开时触发
     * 不管是客户端主动断开还是服务端主动断开，都会触发这个回调
     * 一般在这里做一些数据清理工作
     * @param int $client_id 全局唯一的client_id
     */
    public static function onClose($client_id)
    {
        // 向所有人发送
        // GateWay::sendToAll("$client_id logout\r\n");
    }


    /**
     * 当businessWorker进程退出时触发。每个进程生命周期内都只会触发一次
     * 可以在这里为每一个businessWorker进程做一些清理工作，例如保存一些重要数据等
     * @param  object $businessWorker businessWorker进程实例
     */
    public static function onWorkerStop($businessWorker)
    {
        echo "WorkerStop\n";
    }
}
