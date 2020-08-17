<?php
declare(strict_types=1);
require_once __DIR__.'/Beanstalk.php';

class Product
{

    /**
     * 消息生产者
     * @param array $apiNeedData [接口需要的参数]
     * @param string $tube [唯一tube]
     * @param string $config [配置的key]
     * @return bool|int
     */
    public static function producer(array $apiNeedData,string $tube='delSource',string $config='beanstalk'):int
    {
        $beanstalkConfig = parse_ini_file(__DIR__.'/../../config/config.ini',true)[$config];
        $config = [
            'host'=>$beanstalkConfig['host'],
            'port'=>$beanstalkConfig['port']
        ];
        $beanstalkServer = new Beanstalk($config);
        $beanstalkServer->connect();
        $beanstalkServer->useTube($tube);//tube 为删除资源文件
        $data = [
            'url'=>$beanstalkConfig['apiUrl'],
            'method'=>'post',
            'data'=>$apiNeedData
        ];
        return (int)$beanstalkServer->put(1,$beanstalkConfig['delay'],20,json_encode($data,JSON_UNESCAPED_UNICODE));
    }


    /**
     * 删除任务
     * @param string $tube
     * @param int $taskId
     * @param string $config
     * @return bool
     */
    public static function delTask(string $tube,int $taskId,string $config='beanstalk'):bool
    {
        $beanstalkConfig = parse_ini_file(__DIR__.'/../../config/config.ini',true)[$config];
        $config = [
            'host'=>$beanstalkConfig['host'],
            'port'=>$beanstalkConfig['port']
        ];
        $beanstalkServer = new Beanstalk($config);
        $beanstalkServer->connect();
        $beanstalkServer->watch($tube);//tube 为删除资源文件
        return $beanstalkServer->delete($taskId);
    }
}