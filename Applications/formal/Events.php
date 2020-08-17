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

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

require_once __DIR__. '/../../Tool/common/Common.php';
require_once __DIR__ . '/../../business/Registration/Base.php';
require_once __DIR__ . '/../../business/Cashier/Cashier.php';
require_once __DIR__ . '/../../business/GroupChat/GroupChat.php';
require_once __DIR__ . '/../../business/SingleChat/SingleChat.php';


use \GatewayWorker\Lib\Gateway;


/**
 * 单聊程序主逻辑
 * 客户端发送数据格式
 * [
 * 'fromUserId'=>'22',//发送消息者唯一标识
 * 'sendToUser'=>'23',//接受消息者唯一标识
 * 'content'=>'',//消息内容 根据消息类型决定除了text是文字其他类型都为这个资源文件的url
 * 'msgType'=>''//消息类型  text voice video picture
 * ]
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当客户端连接时触发
     * @param $client_id [通讯时在聊天服务器上的唯一标识]
     * @param $data [客户端发送的数据]
     */
    public static function onWebSocketConnect($client_id, $data)
    {
        # 收银台专用
        if(isset($data['get']['token'])){
            Base::syt($client_id,$data['get']['token']);
            return;
        }

        Base::chat($data['get']['uid'],$data['get']['client_type'],$client_id);

    }

    
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message)
   {
       //判断客户端发送的数据是否为json格式
       $checkResult = Common::IsJson($message);
       if(false === $checkResult){
           Gateway::sendToClient($client_id,Common::json(0,'请发送json数据包','error'));
           return;
       }
       //客户端与服务端之间定义的json消息结构体
       $msg = json_decode($message,true);
       if(!isset($msg['request-type'])){
           Gateway::sendToClient($client_id,Common::json(0,'消息体中缺少action参数','error'));
           return;
       }
       switch ($msg['request-type']){
           /********************************************** 单聊处理 *************************************************/
           // 获取单人未读消息<ACK使用建议程序每3秒操作一次>
           case 'getUnreadMsg':
               SingleChat::getUnreadMsg($client_id,$msg);
               return;
           //  回执消息已读并且收到
           case 'signIsReadMsg':
               SingleChat::signIsReadMsg($msg,$client_id);
               return;
           //  获取单人聊天离线未读消息
           case 'getOfflineMsg':
               SingleChat::getOfflineMsg($msg);
               return;
           // 消息撤回
           case 'msgToWithdraw':
               SingleChat::msgToWithdraw($msg);
               return;
           // 单聊消息发送
           case 'sendSingleMsg':
               SingleChat::sendSingleMsg($msg);
               return;
           /********************************************** 群聊处理 *************************************************/
           // 进行群绑定操作
           case 'userBindGroup':
               $uid = $msg['uid'] ?? '';//用户id
               $groupId = $msg['group_id'] ?? '';//群唯一标识
               if(empty($uid) || empty($groupId)){
                   return;
               }
               GroupChat::userBindGroup($client_id,$uid,$groupId);
               return;
           // 获取离线未读消息<ACK使用建议程序;当点进单个用户界面时触发>
           case 'getGroupOfflineMsg':
               $uid = $msg['uid'] ?? '';//用户id
               $groupId = $msg['group_id'] ?? '';//用户朋友id
               if(empty($uid) || empty($groupId)){
                   return;
               }
               GroupChat::getGroupOfflineMsg($uid,$groupId);
               return;
           // 群未读消息读取(群未读消息计算规则客户端需要记录用户接收群消息的条数，服务器端只需要记录群消息总条数即可，再做计算时只需要将群总消息数减去客户端记录条数就能得到未读数)
           case 'getGroupUnreadMsg':
               GroupChat::getGroupUnreadMsg($msg);
               return;
           // 发送群消息
           case 'sendGroupMsg':
               GroupChat::sendGroupMsg($msg);
               return;
           /********************************************** 收银台 *************************************************/
           //获取收银台离线消息
           case 'getSytOfflineMsg':
               Cashier::getSytOfflineMsg($client_id,$msg);
               return;
           //支付消息通知
           case 'payNotice':
               Cashier::payNotice($client_id,$msg);
               return;
           //心跳处理
           case 'ping':
               Gateway::sendToClient($client_id,Common::json(1,'心跳','ping',$msg));
               return;
       }
   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id)
   {
       //echo '客户端client_id:'.$client_id.'已断开'.PHP_EOL;
       //Base::close($client_id);
   }


}
