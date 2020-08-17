<?php
declare(strict_types=1);
/**
 * 群聊业务处理类
 */
require_once __DIR__ . '/../../Tool/common/Common.php';
require_once __DIR__ . '/../../Tool/redis/RedisPool.php';
require_once __DIR__ . '/../../Tool/mysql/Db.php';
use \GatewayWorker\Lib\Gateway;

class GroupChat
{
    public function __construct()
    {
        $this->init();
    }

    public function init(){
        Swoole\Runtime::enableCoroutine();
    }


    /**
     * 用户绑定群操做
     * @param $clientId [gateway生成的唯一客户端标识]
     * @param $uid [用户唯一标识]
     * @param $groupId [群唯一标识]
     */
    public static function userBindGroup(string $clientId,string $uid,string $groupId):void
    {
        Co\run(function () use ($clientId, $uid,$groupId) {
            //将群成员放入缓存,只会初始化一次
            go(function () use($groupId,$uid){
                $redis = RedisPool::getInstance()->get();
                $redis->select(3);
                $checkIni = $redis->Hget('groupIni','group'.$groupId);
                if(!empty($checkIni)){
                    return;
                }
                $groupList = Db::getInstance()->table('group_user')->where(['group_id'=>$groupId])->getAll('user_token');
                if(empty($groupList)){
                    return;
                }
                $newGroupList = [];
                foreach ($groupList as $key=>$val){
                    $newGroupList[$val['user_token']] = $val['user_token'];
                    unset($groupList[$key]);
                }
                $redis->HMSET('groupUser'.$groupId,$newGroupList);
                $redis->Hset('groupIni','group'.$groupId,1);
            });

            //缓存群的在线用户
            go(function () use ($clientId, $uid,$groupId) {
                Gateway::joinGroup($clientId, $groupId);
                Gateway::sendToClient($clientId,Common::json(1,'用户绑定群操做成功','userBindGroup'));
            });
        });
    }

    /**
     * 获取群离线消息
     * @param string $uid [用户唯一标识]
     * @param string $groupId [群唯一标识]
     */
    public static function getGroupOfflineMsg(string $uid,string $groupId):void
    {
        Co\run(function () use ($uid, $groupId) {
            $chan = new Swoole\Coroutine\Channel(1);
            go(function()use($uid,$groupId,$chan){
                //获取当前登录用户离线消息
                $checkData = Db::getInstance()->table('group_user')
                    ->where(['user_token'=>$uid,'group_id'=>$groupId])
                    ->getOne('last_ack_msg_id');
                $chan->push($checkData);
                if(empty($checkData)){
                    return;
                }
                $lastMsgId = $checkData['last_ack_msg_id'];
                if ($lastMsgId > 0) {

                    $msgList = Db::getInstance()->table('group_msgs')->where(['group_id'=>$groupId,'msg_id'=>['>',$lastMsgId]])->getAll();

                    foreach ($msgList as $val) {
                        Gateway::sendToUid($uid, Common::json(1,'获取群离线消息成功','getGroupOfflineMsg',$val));
                    }
                }
            });
            //拉取完离线消息就更改群消息最后一条消息id为0
            go(function()use($uid,$groupId,$chan){
                if(empty($chan->pop())){
                    return;
                }
                Db::getInstance()->table('group_user')->where(['uid'=>$uid,'group_id'=>$groupId])->update(['last_ack_msg_id'=>0]);
            });
        });
    }

    /**
     * 获取群未读消息
     * @param array $msg
     */
    public static function getGroupUnreadMsg(array $msg):void
    {
        Co\run(function () use ($msg) {
            $res = RedisPool::getInstance()->get();
            $res->select(3);
            $result = $res->hMGet('groupMsg', explode(',', $msg['groupIds']));
            Gateway::sendToUid($msg['uid'], Common::json(1, '获取群未读消息成功', 'getGroupUnreadMsg', $result));
        });
    }


    /**
     * 发送群消息
     * @param array $msg
     */
    public static function sendGroupMsg(array $msg):void
    {
        Co\run(function () use ($msg) {
            //开启一个协程通道
            $chan = new Swoole\Coroutine\Channel(1);
            //进行消息的发送以及接收方不再线的情况更新其用户最后收到消息的id
            go(function () use ($msg, $chan) {
                $redis = RedisPool::getInstance()->get();
                $redis->select(3);
                $luaScript = <<<SCRIPT
                            local userList = redis.call('hGetAll',KEYS[1])    
                            return userList
                       SCRIPT;
                $checkResult = $redis->eval($luaScript, ['groupUser' . $msg['groupId']], 1);
                //从通道中获取数据
                $insertMsgId = $chan->pop();
                if (!empty($checkResult)) {
                    $arr = array_filter($checkResult, function ($var) {
                        return ($var & 1);
                    }, ARRAY_FILTER_USE_KEY);
                    $bindUserLists = Gateway::getUidListByGroup($msg['groupId']);
                    //给群中人做比较，在线的人不需要去保存最后一个收到消息的id
                    $result = array_diff($arr, array_values($bindUserLists));
                    if (!empty($result)) {
                        //更新操作
                        $sql = sprintf('UPDATE `im_group_user` SET `last_ack_msg_id`=%d WHERE find_in_set(user_token,%s) AND group_id=%d AND `last_ack_msg_id`=0',
                            $insertMsgId, implode(',', $result), $msg['groupId']);
                        Db::getInstance()->query($sql);
                    }
                }
                $msg['msg_id'] = $insertMsgId;
                //增加群消息总条数
                $redis->hIncrBy('groupMsg',$msg['groupId'],1);
                Gateway::sendToGroup($msg['groupId'], Common::json(1,'发送群消息成功','getGroupUnreadMsg',$msg));
            });

            //进行消息保存数据库
            go(function () use ($msg, $chan) {
                $insertResult = Db::getInstance()->table('group_msgs')->insert([
                    'group_id'=>$msg['groupId'],
                    'send_uid'=>$msg['fromUserId'],
                    'content'=>$msg['content'],
                    'msg_type'=>$msg['msgType'],
                    'create_time'=>date('Y-m-d H:i:s', time())
                ]);
                $chan->push($insertResult);
            });

        });
    }
}