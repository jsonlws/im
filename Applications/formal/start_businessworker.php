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

// bussinessWorker 进程
$worker = new BusinessWorker();
// worker名称
$worker->name = 'formal';
// bussinessWorker进程数量
$worker->count = $workerConfig['processNum'];
// 服务注册地址
$worker->registerAddress = $workerConfig['registerAddress'].':'.$workerConfig['textPort'];
// 服务


//服务处理类
$worker->eventHandler = 'Events';


// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}

