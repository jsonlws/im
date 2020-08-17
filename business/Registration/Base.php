<?php
declare(strict_types=1);
/**
 * 初始化连接服务
 */
require_once __DIR__ . '/../../Tool/common/Common.php';
require_once __DIR__ . '/../../Tool/redis/RedisPool.php';
require_once __DIR__ . '/../../Tool/mysql/Db.php';
use \GatewayWorker\Lib\Gateway;

class Base
{
    /**
     * 收银台
     * @param string $client_id
     * @param string $token
     */
    public static function syt(string $client_id,string $token):void
    {
        $urlConfig = parse_ini_file(__DIR__ . '/../../config/api.ini',true);
        $param = [
            'module_token'=>$urlConfig['auth']['module_token'],
        ];
        $result = json_decode(Common::curl_ajax($urlConfig['baseUrl']['baseUrl'].$urlConfig['auth']['authUserApi'],$param,$token),true);
        if(empty($result) || $result['code'] != 1){
            Gateway::sendToClient($client_id,Common::json(0,'鉴权失败','AuthenticationFailure'));
            Gateway::closeClient($client_id);
            return;
        }
        //重复账号登录则把之前的账号给踢下线
        $uid = $result['data']['device'].$result['data']['user_token'];
        /** @var array  $old_client_id */
        $old_client_ids = Gateway::getClientIdByUid($uid);
        foreach ($old_client_ids as $old_client_id){
            Gateway::sendToClient($old_client_id,Common::json(1,'账号异地登录','re_login'));
            Gateway::closeClient($old_client_id);
        }
        //将$client_id 绑定一个uid唯一标识
        Gateway::bindUid($client_id,$uid);
        Gateway::sendToClient($client_id,Common::json(1,'鉴权成功','AuthenticationSuccess'));
        //将某个$client_id加入某一个唯一的组
        Gateway::joinGroup($client_id,'syt'.$result['data']['shop_token']);
        return;
    }

    /**
     * 交友聊天
     * @param string $uid
     * @param string $client_type
     * @param string $client_id
     */
    public static function chat(string $uid,string $client_type,string $client_id ):void
    {
        if(empty($uid) || !in_array($client_type,['pc','app'],true)){
            Gateway::closeClient($client_id);
            return;
        }
        $checkUserIsExist = Db::getInstance()->table('user')->where(['user_token'=>$uid])->getOne('uid,is_blacklist');
        #不存在或者是在黑名单的账号则不能登录
        if (empty($checkUserIsExist) || $checkUserIsExist['is_blacklist'] == 1) {
            Gateway::closeClient($client_id);
            return;
        }
        Swoole\Runtime::enableCoroutine();
        Co\run(function() use($uid,$client_id,$client_type){
            $chan = new Swoole\Coroutine\Channel(1);
            go(function () use ($uid,$client_id,$client_type,$chan){
                $redis = RedisPool::getInstance()->get();
                $redis->select(2);
                $checkRepeat = $redis->hGet('multi_terminal_binding',$client_type.':'.$uid);
                //判断同一账号是否重复连接，若连接怎直接把之前的给踢下线
                if(!empty($checkRepeat)){
                    Gateway::closeClient($checkRepeat);
                }
                $chan->push($redis);
            });

            go(function ()use($client_id,$uid,$client_type,$chan){
                Gateway::bindUid($client_id, $uid);
                //这个主要解决在onclose中根据clientId获取uid问题
                $_SESSION[$client_id] = $client_type.':'.$uid;
                $chan->pop()->hSet('multi_terminal_binding',$client_type.':'.$uid,$client_id);
            });
        });
    }


    /**
     * 客户端断开处理
     * @param $client_id
     */
    public static function close($client_id){
        Swoole\Runtime::enableCoroutine();
        Co\run(function() use($client_id) {
            $uid = $_SESSION[$client_id];
            if (empty($uid)) {
                return;
            }
            $redis = RedisPool::getInstance()->get();
            $redis->select(2);
            $redis->Hdel('multi_terminal_binding', $uid);
        });
    }
}
