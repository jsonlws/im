<?php 
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use \Workerman\Worker;
use \Workerman\WebServer;
use \GatewayWorker\Gateway;
use \GatewayWorker\BusinessWorker;
use \Workerman\Autoloader;

// 自动加载类
require_once __DIR__ . '/../../vendor/autoload.php';

// 读取worker配置
$workerConfig = parse_ini_file(__DIR__.'/../../config/worker.ini');

if($workerConfig['ssl'] == true) {
    $context = array(
        'ssl' => array(
            // 请使用绝对路径
            'local_cert' => __DIR__.'/../../ssl/'.$workerConfig['crtFilePath'], // 也可以是crt文件
            'local_pk' => __DIR__.'/../../ssl/'.$workerConfig['keyFilePath'],
            'verify_peer' => false,
            // 'allow_self_signed' => true, //如果是自签名证书需要开启此选项
        )
    );
}

// gateway 进程，这里使用Text协议，可以用telnet测试
$gateway = new Gateway("websocket://0.0.0.0:".$workerConfig['websocketPort'],$context ?? []);
// gateway名称，status方便查看
$gateway->name = 'formal';

if($workerConfig['ssl'] == true) {
    //开启SSL，websocket+SSL 即wss
    $gateway->transport = 'ssl';
}

// gateway进程数
$gateway->count = $workerConfig['processNum'];
// 本机ip，分布式部署时使用内网ip
$gateway->lanIp = $workerConfig['latIp'];
// 内部通讯起始端口，假如$gateway->count=4，起始端口为4000
// 则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口 
$gateway->startPort = (int)$workerConfig['startPort'];
// 服务注册地址
$gateway->registerAddress = $workerConfig['registerAddress'].':'.$workerConfig['textPort'];

// 心跳间隔
$gateway->pingInterval = (int)$workerConfig['pingInterval'];

//客户端必须发送心跳包（测试阶段可以设置为0）
$gateway->pingNotResponseLimit = (int)$workerConfig['pingNotResponseLimit'];

// 心跳数据{"code":1,"msg":"心跳包","updateType":"ping"}
$gateway->pingData = $workerConfig['pingData'];



// 当客户端连接上来时，设置连接的onWebSocketConnect，即在websocket握手时的回调
//$gateway->onConnect = function($connection)
//{
//    $connection->onWebSocketConnect = function($connection , $http_header)
//    {
//        // 可以在这里判断连接来源是否合法，不合法就关掉连接
//        // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket链接
//        if($_SERVER['HTTP_ORIGIN'] != 'http://kedou.workerman.net')
//        {
//            $connection->close();
//        }
//        // onWebSocketConnect 里面$_GET $_SERVER是可用的
//        // var_dump($_GET, $_SERVER);
//    };
//};


// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

