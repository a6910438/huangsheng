<?php
namespace app\common\command;

use app\common\service\Websocket\Events;
use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker;

class WebSocketService extends Command
{
    protected function configure()
    {
        $this->setName('WebSocketService')
            ->addArgument('action', Argument::OPTIONAL, "action  start|stop|restart|reload|status")
            ->addArgument('mode', Argument::OPTIONAL, "mode  d|g")
            ->setDescription('WebSocketService Connection');
    }

    protected function execute(Input $input, Output $output)
    {
        global $argv;
        $action = trim($input->getArgument('action'));
        switch (trim($input->getArgument('mode'))) {
            case 'd':
                $mode = '-d';
                break;

            case 'g':
                $mode = '-g';
                break;

            default:
                $mode = '';
                break;
        }
        // $mode   = trim($input->getArgument('mode')) ? '-d' : '';

        $argv[0] = 'WebSocketService';
        $argv[1] = $action;
        // $argv[2] = $mode ? '-d' : '';
        $argv[2] = $mode;
        $this->start();
    }
    private function start()
    {
        $this->startGateWay();
        $this->startBusinessWorker();
        $this->startRegister();
        Worker::runAll();
    }


    /**
     * BusinessWorker类其实也是基于基础的Worker开发的,
     * BusinessWorker是运行业务逻辑的进程,
     * BusinessWorker收到Gateway转发来的事件及请求时会默认调用Events.php中的onConnect onMessage onClose方法处理事件及数据,
     * 开发者正是通过实现这些回调控制业务及流程
     */
    private function startBusinessWorker()
    {
        $worker                  = new BusinessWorker();
        $worker->name            = 'BusinessWorker'; // 设置BusinessWorker进程的名称，方便status命令中查看统计
        $worker->count           = 4;                // 设置BusinessWorker进程的数量，以便充分利用多cpu资源
        $worker->registerAddress = '127.0.0.1:1236'; // 注册服务地址，只写格式类似于 '127.0.0.1:1236'
        /**
         * 设置使用哪个类来处理业务，默认值是Events，即默认使用Events.php中的Events类来处理业务
         * 业务类至少要实现onMessage静态方法，onConnect和onClose静态方法可以不用实现
         * 如果类带有命名空间，则需要把命名空间加上,
         * 类似$worker->eventHandler='\my\namespace\MyEvent';
         */
        $worker->eventHandler    = Events::class;
    }


    /**
     * Gateway类用于初始化Gateway进程,
     * Gateway进程是暴露给客户端的让其连接的进程,
     * 所有客户端的请求都是由Gateway接收然后分发给BusinessWorker处理,
     * 同样BusinessWorker也会将要发给客户端的响应通过Gateway转发出去,
     * Gateway类是基于基础的Worker开发的,
     * 可以给Gateway对象的onWorkerStart、onWorkerStop、onConnect、onClose设置回调函数,但是无法给设置onMessage回调
     * Gateway的onMessage行为固定为将客户端数据转发给BusinessWorker。
     */
    private function startGateWay()
    {
        $gateway = new Gateway("websocket://0.0.0.0:8585");
        $gateway->name                 = 'Gateway'; // 设置Gateway进程的名称，方便status命令中查看统计
        $gateway->count                = 4;         // 设置Gateway进程的数量，以便充分利用多cpu资源
        /**
         * lanIp是Gateway所在服务器的内网IP,默认填写127.0.0.1即可,
         * 多服务器分布式部署的时候需要填写真实的内网ip,不能填写127.0.0.1,
         * 注意：lanIp只能填写真实ip,不能填写域名或者其它字符串,无论如何都不能写0.0.0.0
         */
        $gateway->lanIp                = '127.0.0.1';
        $gateway->startPort            = 2500;
        $gateway->pingInterval         = 30; // 发送心跳间隔时间
        // pingNotResponseLimit = 0代表服务端允许客户端不发送心跳，
        // 服务端不会因为客户端长时间没发送数据而断开连接,如果pingNotResponseLimit = 1，则代表客户端必须定时发送心跳给服务端，
        // 否则pingNotResponseLimit*pingInterval=55秒内没有任何数据发来则关闭对应连接，并触发onClose
        $gateway->pingNotResponseLimit = 0;
        $gateway->pingData             = '{"type":"ping"}';  // 服务端定时向客户端发送的数据
        $gateway->registerAddress      = '127.0.0.1:1236'; // 注册服务地址，只写格式类似于 '127.0.0.1:1236'
    }


    /**
     * Register类其实也是基于基础的Worker开发的,
     * Gateway进程和BusinessWorker进程启动后分别向Register进程注册自己的通讯地址,
     * Gateway进程和BusinessWorker通过Register进程得到通讯地址后，就可以建立起连接并通讯了,
     * 注意,客户端不要连接Register服务的端口,Register服务是GatewayWorker内部通讯用的,
     * Register类只能定制监听的ip和端口，并且目前只能使用text协议
     */
    private function startRegister()
    {
        new Register('text://0.0.0.0:1236');
    }
}
