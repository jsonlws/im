<?php
declare(strict_types=1);
/**
 * 收银台业务处理
 */
require_once __DIR__ . '/../../Tool/common/Common.php';
require_once __DIR__ . '/../../Tool/redis/RedisPool.php';
require_once __DIR__ . '/../../Tool/mysql/MysqlPool.php';
require_once __DIR__ . '/../../Tool/beanstalk/Product.php';
use \GatewayWorker\Lib\Gateway;

class Cashier
{
    /**
     * 获取收银台离线消息
     * @param string $clientId
     * @param array $msg
     */
    public static function getSytOfflineMsg(string $clientId,array $msg):void
    {
        $urlConfig = parse_ini_file(__DIR__ . '/../../config/api.ini',true);
        $data = [
            'shop_token'=>$msg['shop_token']
        ];
        $url = $urlConfig['baseUrl']['baseUrl'].$urlConfig['syt']['getOfflineMsg'];
        $res =  json_decode(Common::https_post($url,$data),true);
        if($res == false || $res['code'] != 1){
            Gateway::sendToClient($clientId,Common::json(0,'接口错误或不可用','error'));
            return;
        }
        if(empty($res['data'])){
            Gateway::sendToClient($clientId,Common::json(0,'暂无离线数据','error'));
            return;
        }
        foreach($res['data'] as $val){
            Gateway::sendToClient($clientId,$val['offline_content']);
        }
    }

    /**
     * 删除消息提示任务
     * @param array $msg
     */
    public static function payNotice(array $msg)
    {
        $taskId = (int)$msg['task_id'];
        Product::delTask('zj_crond__Task__payNotice',$taskId);
    }
}
