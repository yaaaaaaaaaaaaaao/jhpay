<?php

// +----------------------------------------------------------------------
// | 几何支付 - 核心方法
// +----------------------------------------------------------------------
// | 官方接口文档：https://www.yuque.com/laoluotongxue/gxog7z/kzeeem
// +----------------------------------------------------------------------
// | 技术支持：QQ-736849829
// +----------------------------------------------------------------------
// | Author: Yao <736849829@qq.com>
// +----------------------------------------------------------------------

namespace Suixiang\Jhpay;

class PayModel
{
    public $url;

    public $appid;

    public $secret_key;

    public $publicKey;

    public $privateKey;

    public $channel;

    /**
     * @param $config['appid']         商户号 必填
     * @param $config['secret']        秘钥   必填
     * @param $config['private_key']   公钥   必填
     * @param $config['public_key']    私钥   必填
     * @param $config['channel']       通道   默认ys
     */
    public function __construct($config)
    {
        $this->url = 'https://jihepay.seixiang.cn/saas/v2/trade';
        $this->channel = isset($config['channel']) ? $config['channel'] : 'ys';
        $this->appid = $config['appid'];
        $this->secret_key = $config['secret'];

        //私钥太长，格式不对。使用此方法格式化私钥
        $private_cert = $config['private_key'];
        $private_key =str_replace(array("\r\n", "\r", "\n"), "", $private_cert);
        $private_key =  "-----BEGIN PRIVATE KEY-----".PHP_EOL . wordwrap($private_key, 64, PHP_EOL,true) . PHP_EOL."-----END PRIVATE KEY-----";
        //这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
        $this->privateKey =  openssl_pkey_get_private($private_key);

        //公钥太长，格式不对。使用此方法格式化公钥
        $public_cert = $config['public_key'];
        $public_key =str_replace(array("\r\n", "\r", "\n"), "", $public_cert);
        $public_key =  "-----BEGIN PUBLIC KEY-----".PHP_EOL . wordwrap($public_key, 64, PHP_EOL,true) . PHP_EOL."-----END PUBLIC KEY-----";
        //这个函数可用来判断公钥是否是可用的
        $this->publicKey = openssl_pkey_get_public($public_key);

    }
    /**
     * 私钥签名
     * @param unknown $data
     */
    public function signByPrivateKey($data){
        openssl_sign($data, $signature, $this->privateKey);
        $encrypted = base64_encode($signature);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
        return $encrypted;
    }

    /**
     * 需要签名的参数排序
     *1.需要根据参数名的首字母,按从 a 到 z 的顺序进行排序.若首字母相同,则根据第二个字母进行排序,以此类推
     *2.排序完成后,再把所有参数以”&”字符作为分隔符进行连接
     * @param $data
     */
    public function ksortToString($result)
    {
        ksort($result);
        $signStr = "";
        foreach ($result as $key => $val) {
            if ($val) $signStr .= $key . '=' . $val . '&';
        }
        $signStr = trim($signStr, '&');
        return $signStr;
    }

    /**
     * post发送请求
     *
     * @param $url
     * @param $myParams
     * @param $response_name
     * @return false|string
     */
    public function post_url($url, $pra=[])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pra));
        $output = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($output, true);
        return $response;
    }

    public function postRequest($serviceNo, $requestNo, $parJson){
        $myParams = array();
        //公共请求参数
        $myParams['requestNo'] = $requestNo;
        $myParams['reqTime'] = date('Y-m-d H:i:s');
        $myParams['appid'] = $this->appid;
        $myParams['channel'] = $this->channel;
        $myParams['version'] = '2.0';
        $myParams['serveNo'] = $serviceNo;
        //业务请求参数
        ksort($parJson);
        $parNewJson = [];
        foreach ($parJson as $key => $val) {
            if ($val) $parNewJson[$key]=$val;
        }
        $myParams['parJson'] = json_encode($parNewJson, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);//构造字符串
        $signStr = $this->ksortToString($myParams);
        $myParams['sign'] = $this->signByPrivateKey($signStr.'&secret='.$this->secret_key);

        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($myParams));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            return ['status' => false, 'msg'=>'请求失败'];
        }
        else {
            $result = json_decode($response, true);
            if($result['code'] !='80000'){
                return ['status' => false, 'msg'=>$result['info']];
            }
            return ['status' => true, 'msg'=>'Success', 'data'=>$result];
        }

    }
    /*
     * 获取客户端ip地址
     */
    public function  getRealIp()
    {
        $ip=false;
        if(!empty($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
            for ($i = 0; $i < count($ips); $i++) {
                if (!eregi ("^(10│172.16│192.168).", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : (isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:request()->ip()));
    }

    public function notify($log=false, $data=[])
    {
        if (empty($data)) {
            $data = $_POST;
        }
        if ($log) {
            $this->writeLog(json_encode($data));
        }
        if ($data && is_array($data)) {
            if ($data['trade_status'] == 'TRADE_SUCCESS') {
                $org_sign = $data['sign'];
                unset($data['sign']);
                $sign = strtoupper(md5($this->ksortToString($data).'&appid='.$this->appid.'&secret='.$this->secret_key));
                if($sign == $org_sign){
                    return $data;
                }
            }
        }
        return false;
    }

    private function writeLog($string){
        $dir = "./jhpayNotify";
        $filename = $dir."/".date("Ymd").".txt";
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($filename, date('Y-m-d H:i:s').PHP_EOL, FILE_APPEND);
        file_put_contents($filename, $string.PHP_EOL, FILE_APPEND);
    }
    
}
