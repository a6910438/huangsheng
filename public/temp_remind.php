<?php
/**
 * Created by PhpStorm.
 * User: Leo
 * Date: 2018/12/15
 * Time: 23:18
 */
use Workerman\Worker;
require_once '../vendor/Workerman/workerman/Autoloader.php';
 
$global_uid = 0;
 
// 当客户端连上来时分配uid，并保存连接，并通知所有客户端
function handle_connection($connection) {
    global $text_worker, $global_uid;
    // 为这个链接分配一个uid
    $connection->uid = ++$global_uid;
//    foreach ($text_worker->connections as $conn) {
//        $conn->send("user[{$connection->uid}] online");
//    }
}
 
// 当客户端发送消息过来时，转发给所有人
function handle_message($connection, $data) {
    global $text_worker;
    $list = json_decode($data);
    $id =$list->uid;
    $data_list = $list->list;
    if($data_list){
        foreach ($data_list as $v){
            if($v->uid == $id){
                $vo[] = $v;
            }
        }
    }

    foreach ($text_worker->connections as $conn) {
        if(!isset($vo)){
            $vo = [];
        }
        $conn->send(json_encode($vo));
    }
}
 
// 当客户端断开时，广播给所有客户端
// function handle_close($connection) {
//     global $text_worker;
//     foreach ($text_worker->connections as $conn) {
//         $conn->send("user[{$connection->uid}] logout");
//     }
// }
 
$text_worker = new Worker("websocket://0.0.0.0:2347");
 
$text_worker->count = 2;
 
$text_worker->onConnect = 'handle_connection';
$text_worker->onMessage = 'handle_message';
// $text_worker->onClose = 'handle_close';
 
Worker::runAll();
