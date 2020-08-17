<?php
/**
 * Class Common
 * 请求方法公共文件
 */

class Common
{
    /**
     * curl ajax请求
     * @param string $url
     * @param array $param
     * @param string $token
     * @return bool|string
     */
    public static function curl_ajax(string $url,array $param,string $token){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>json_encode($param),
            CURLOPT_HTTPHEADER => array(
                "access-token: ".$token,
                "X-Requested-With: XMLHttpRequest",
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }


    /**
     * http post请求方法
     * @param $url
     * @param $data
     * @return mixed
     */
    public static function https_post(string $url, array $data):string
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS =>json_encode($data),
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/json"
            ),
        ));
        if (curl_errno($curl)) {
            return false;
        }else{
            $result=curl_exec($curl);
        }
        curl_close($curl);
        if(self::IsJson($result)){
            return $result;
        }else{
            return false;
        }

    }


    /**
     * http get请求方法
     * @param $url
     * @return mixed|string
     */
    public static function https_get(string $url):string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_HEADER, FALSE) ;
        curl_setopt($curl, CURLOPT_TIMEOUT,60);
        if (curl_errno($curl)) {
            return false;
        }
        else{
            $result=curl_exec($curl);
        }
        curl_close($curl);
        if(self::IsJson($result)){
            return $result;
        }else{
            return false;
        }

    }

    /**
     * 解析json串
     * @param string $json_str
     * @return mixed
     */
    public static function IsJson(string $json_str) {
        $json_str = str_replace('＼＼', '', $json_str);
        $out_arr = array();
        preg_match('/{.*}/', $json_str, $out_arr);
        if (!empty($out_arr)) {
            $result = json_decode($out_arr[0], TRUE);
        } else {
            return false;
        }
        return $result;
    }

    /**
     * 返回json 字符串
     * @param int $code
     * @param string $msg
     * @param string $command
     * * @param array $data
     * @return false|string
     */
    public static function json(int $code,string $msg,string $command,array $data=[]){
        $data = [
            'code'=>$code,
            'data'=>$data,
            'msg'=>$msg,
            'updateType'=>$command
        ];
        return json_encode($data,JSON_UNESCAPED_UNICODE);
    }

}