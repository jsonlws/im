<?php
declare(strict_types=1);
/**
 * 单聊业务处理
 */
require_once __DIR__ . '/../../Tool/common/Common.php';
require_once __DIR__ . '/../../Tool/redis/RedisPool.php';
require_once __DIR__ . '/../../Tool/mysql/Db.php';
require_once __DIR__ . '/../../Tool/beanstalk/Product.php';
use \GatewayWorker\Lib\Gateway;

class SingleChat
{
    public function __construct()
    {
        $this->init();
    }

    public function init(){
        Swoole\Runtime::enableCoroutine();
    }

    /**
     * 获取单人未读消息数
     * @param string $client_id
     * @param array $msg
     */
    public static function getUnreadMsg(string $client_id,array $msg):void
    {
        Co\run(function() use($msg,$client_id) {
            //获取当前登录用户离线消息
            $redis = RedisPool::getInstance()->get();
            $redis->select(2);
            $result = $redis->HGETALL('unreadMsg' . $msg['uid']);
            Gateway::sendToClient($client_id, Common::json(1, '获取单人未读消息成功', 'getUnreadMsg', $result));
        });
    }

    /**
     * 回执消息已读并且收到
     * @param array $msg
     * @param string $client_id
     */
    public static function signIsReadMsg(array $msg,string $client_id):void
    {
        Co\run(function() use($msg,$client_id) {
            go(function () use ($msg,$client_id) {
                //获取当前登录用户离线消息
                $redis = RedisPool::getInstance()->get();
                $redis->select(2);
                $unreadNum = $redis->hGet('unreadMsg'.$msg['uid'],$msg['fid']);
                if($unreadNum > 0 && $unreadNum >= $msg['num']){
                    $redis->hIncrBy('unreadMsg'.$msg['uid'],$msg['fid'],-$msg['num']);
                }else{
                    $redis->Hdel('unreadMsg'.$msg['uid'],$msg['fid']);
                }
            });
            go(function ()use($client_id){
                Gateway::sendToClient($client_id,Common::json(1,'ok','signIsReadMsg'));
            });
        });
    }

    /**
     * 获取离线消息
     * @param array $msg [发送的消息结构体]
     */
    public static function getOfflineMsg(array $msg):void
    {
        $uid = $msg['uid'] ?? '';//用户id
        $fid = $msg['fid'] ?? '';//用户朋友id
        if(empty($uid) || empty($fid)){
            return;
        }
        Co\run(function() use($uid,$fid){
            $chan = new Swoole\Coroutine\Channel(1);
            go(function ()use($uid,$fid,$chan){
                //获取当前登录用户离线消息
                $redis = RedisPool::getInstance()->get();
                $redis->select(2);
                //未保证其原子性操作减少网络io
                $luaScript = <<<SCRIPT
                            local val = redis.call('HGET','OfflineMsg',KEYS[1])
                            if (val == '0')
                            then
                                redis.call('HDEL','OfflineMsg',KEYS[2])
                                return false
                            end
                                return val
                       SCRIPT;
                $key = 'uid' . $uid . '-' . $fid;
                $result = $redis->eval($luaScript,[$key,$key],2);
                if(empty($result)){
                    Gateway::sendToUid($uid, json_encode([], JSON_UNESCAPED_UNICODE));
                    return;
                }
                //获取离线消息(注意客户端要进行去重操作)
                $msgList = Db::getInstance()->table('single_offlinemsg')
                    ->where(['from_usertoken'=>$fid,'receive_usertoken'=>$uid])
                    ->limit(50)
                    ->getAll('id,msg_id,create_time,msg_type,content');
                $newArr = [];
                foreach ($msgList as $val) {
                    $newArr[] = $val['id'];
                    unset($val['id']);
                    Gateway::sendToUid($uid,Common::json(1,'获取单人聊天离线消息成功','getOfflineMsg',$val));
                }
                $chan->push($newArr);
            });
            #将拉取过的离线消息进行删除操作
            go(function ()use($uid,$fid,$chan){
                $newArr = $chan->pop();
                $sql = sprintf('DELETE FROM `im_single_offlinemsg` WHERE find_in_set(id,%s)',implode(',',$newArr));
                $delResult = Db::getInstance()->query($sql);
                if($delResult){
                    $redis = RedisPool::getInstance()->get();
                    $redis->select(2);
                    $key = 'uid' . $uid . '-' . $fid;
                    $redis->hIncrBy('OfflineMsg', $key,-(sizeof($newArr)));
                }
            });
        });
    }

    /**
     * 消息撤回
     * @param array $msg [发送的消息结构体]
     */
    public static function msgToWithdraw(array $msg):void
    {
        $delResult = Db::getInstance()->table('single_msg')->where(['id'=>$msg['msgId']])->delete();
        //接收消息者不在线时删除相应的离线消息
        if(empty(Gateway::getClientIdByUid($msg['sendToUser']))){
            Db::getInstance()->table('single_offlinemsg')->where(['msg_id'=>$msg['msgId']])->delete();
        }
        if($delResult) {
            Gateway::sendToUid($msg['sendToUser'], Common::json(1,'撤回消息成功','msgToWithdraw',$msg));
        }
    }

    /**
     * 发送单人消息
     * @param array $msg [消息结构体]
     */
    public static function sendSingleMsg(array $msg){
        Co\run(function() use($msg){
            # 开启两个协程通道
            $chan = new Swoole\Coroutine\Channel(2);
            # 进行消息的发送以及接收方不再线的情况更新其用户最后收到消息的id
            go(function ()use($msg,$chan){
                //从通道中获取插入数据库消息id
                $insertMsgId = $chan->pop();
                $msg['msg_id'] = $insertMsgId;
                //接收消息用户不在线时操作
                if(empty(Gateway::getClientIdByUid($msg['sendToUser']))){
                    $chan->push(['msg_id'=>$insertMsgId,'is_offline'=>true]);
                    //当不在线时返回msg_id是为了使客户端使用使用撤回消息功能
                    Gateway::sendToUid($msg['fromUserId'],Common::json(1,'发送消息成功','sendSingleMsg',$msg));
                    return;
                }
                //支持多端发送消息
                Gateway::sendToUid($msg['sendToUser'],Common::json(1,'发送消息成功','sendSingleMsg',$msg));
                $chan->push(['msg_id'=>$insertMsgId,'is_offline'=>false]);
            });

            #将消息投递到任务中主要做对一些资源文件的过期处理
            go(function () use ($msg){
                if(in_array($msg['msgType'],['voice','video','picture'],true)){
                    $data = [
                        'source'=>111
                    ];
                    Product::producer($data);
                }
            });

            # 处理接收消息者离线在线各自的操作
            go(function()use($msg,$chan){
                $chanData = $chan->pop();
                $redis = RedisPool::getInstance()->get();
                $redis->select(2);
                //记录用户未读消息数
                $redis->hIncrBy('unreadMsg'.$msg['sendToUser'],$msg['fromUserId'],1);
                //若用户不在线则保存离线消息
                if(!empty($chanData) && $chanData['is_offline'] === true) {
                    Db::getInstance()->table('single_offlinemsg')->insert([
                        'msg_id'=>$chanData['msg_id'],
                        'from_usertoken'=>$msg['fromUserId'],
                        'receive_usertoken'=>$msg['sendToUser'],
                        'msg_type'=>$msg['msgType'],
                        'content'=>$msg['content'],
                        'create_time'=>date('Y-m-d H:i:s'),
                    ]);
                    $redis->hIncrBy('OfflineMsg', 'uid' . $msg['sendToUser'] . '-' . $msg['fromUserId'],1);
                }
                //消息进行ack告知发送方已成功接收消息,未告知则对方不在线
                if(!empty($chanData) && $chanData['is_offline'] === false) {
                    Gateway::sendToUid($msg['fromUserId'],Common::json(1,'发送消息成功','sendSingleMsg',['msg_id'=>$chanData['msg_id'],'status'=>'ok']));
                }
            });
            # 进行消息保存数据库
            go(function ()use($msg,$chan){
                $insertResult = Db::getInstance()->table('single_msg')->insert([
                    'send_uid'=>$msg['fromUserId'],
                    'receive_uid'=>$msg['sendToUser'],
                    'content'=>$msg['content'],
                    'msg_type'=>$msg['msgType'],
                    'create_time'=>date('Y-m-d H:i:s'),
                ]);
                $chan->push($insertResult);
            });
        });
    }

}
