<?php
/**
 * redis实现分布式锁
 * Class RedisMutexLock
 * 正常的分布式锁要满足以下几点要求：
    1.能解决并发时资源争抢。这是最核心的需求。
    2.锁能正常添加与释放。不能出现死锁。
    3.锁能实现等待，否则不能最大保证用户的体验
 */

class RedisMutexLock
{

    /**
     * 缓存 Redis 连接。
     */
    public static function getRedis()
    {
        $redisConfig = parse_ini_file(__DIR__.'/../../config/config.ini',true)['redis'];
        $redis = new Redis();
        $redis->connect($redisConfig['host'], $redisConfig['port']); //连接Redis
        $redis->auth($redisConfig['auth']); //密码验证
        $redis->select(12);//选择数据库2
        return $redis;
    }

    /**
     * 获得锁,如果锁被占用,阻塞,直到获得锁或者超时。
     * -- 1、如果 $timeout 参数为 0,则立即返回锁。
     * -- 2、建议 timeout 设置为 0,避免 redis 因为阻塞导致性能下降。请根据实际需求进行设置。
     *
     * @param  string  $key         缓存KEY。
     * @param  int     $timeout     取锁超时时间。单位(秒)。等于0,如果当前锁被占用,则立即返回失败。如果大于0,则反复尝试获取锁直到达到该超时时间。
     * @param  int     $lockSecond  锁定时间。单位(秒)。
     * @param  int     $sleep       取锁间隔时间。单位(微秒)。当锁为占用状态时。每隔多久尝试去取锁。默认 0.1 秒一次取锁。
     * @return bool 成功:true、失败:false
     * @throws \Exception
     */
    public static function lock($key, $timeout = 0, $lockSecond = 10, $sleep = 100000)
    {
        if (strlen($key) === 0) {
            // 项目抛异常方法
            throw new \Exception('缓存KEY没有设置');
        }
        $start = self::getMicroTime();
        $redis = self::getRedis();
        do {
            // [1] 锁的 KEY 不存在时设置其值并把过期时间设置为指定的时间。锁的值并不重要。重要的是利用 Redis 的特性。
            $acquired = $redis->set("Lock:{$key}", 1, ['NX', 'EX' => $lockSecond]);
            if ($acquired) {
                break;
            }
            if ($timeout === 0) {
                break;
            }
            usleep($sleep);
        } while (!is_numeric($timeout) || (self::getMicroTime()) < ($start + ($timeout * 1000000)));
        return $acquired ? true : false;
    }

    /**
     * 释放锁
     *
     * @param  mixed  $key  被加锁的KEY。
     * @return void
     * @throws \Exception
     */
    public static function release($key)
    {
        if (strlen($key) === 0) {
            // 项目抛异常方法
            throw new \Exception('缓存KEY没有设置');
        }
        $redis = self::getRedis();
        $redis->del("Lock:{$key}");
    }

    /**
     * 获取当前微秒。
     *
     * @return int
     */
    protected static function getMicroTime()
    {
        return bcmul(microtime(true), 1000000);
    }
}