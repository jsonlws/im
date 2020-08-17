<?php
/**
 * redis 操作单例模式
 * Class RedisPool
 */

class RedisPool
{

    private static $instance;
    private $sever;

    public static function getInstance($cache='redis')
    {
        if (empty(self::$instance)) {
            self::$instance = new static($cache);
        }
        return self::$instance;
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public function __construct($cache='redis')
    {
        $redisConfig = parse_ini_file(__DIR__.'/../../config/config.ini',true)[$cache];
        $this->sever = new Redis();
        try{
            $this->sever->connect($redisConfig['host'], $redisConfig['port']);
            $this->sever->auth($redisConfig['auth']);
        }catch (RuntimeException $e){
            throw new RuntimeException("redis服务不可用");
        }
    }

    public function get()
    {
        return $this->sever;
    }

}