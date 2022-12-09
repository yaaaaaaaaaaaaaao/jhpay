<?php

namespace Suixiang\Jhpay;

// +----------------------------------------------------------------------
// | 几何支付 - 简单封装几个比较常见的支付类型
// +----------------------------------------------------------------------
// | 官方接口文档：https://www.yuque.com/laoluotongxue/gxog7z/kzeeem
// +----------------------------------------------------------------------
// | 技术支持：QQ-736849829
// +----------------------------------------------------------------------
// | Author: Yao <736849829@qq.com>
// +----------------------------------------------------------------------

class PayService
{
    protected $PayModel;

    /**
     * @param $config['appid']         商户号 必填
     * @param $config['secret']        秘钥   必填
     * @param $config['private_key']   公钥   必填
     * @param $config['public_key']    私钥   必填
     * @param $config['channel']       通道   默认ys
     */
    public function __construct($config)
    {
        $this->PayModel = new PayModel($config);
    }

    /**
     * 支付宝QR
     * @param $data['price']      金额（元）       必填
     * @param $data['order_sn']   订单号（不能重复）必填
     * @param $data['notify_url'] 异步通知地址      必填
     * @param $data['subject']    说明             可选
     * @return string
     */
    public function payAlipayQr($data){
        $serviceNo = 'actionPay';
        $parJson = [
            'payWay' =>'ALIPAY_QR',
            'scene' =>'offline',
            'subject' =>isset($data['subject']) ? $data['subject'] : '购买商品',
            'amount' =>$data['price'],
            'notify_url' =>$data['notify_url'],
            'return_url' =>$data['notify_url'],
            'shopdate' =>date('Y-m-d'),
            'timeout' =>'10',
            'client_ip' =>$this->PayModel->getRealIp(),

        ];
        $res = $this->PayModel->postRequest($serviceNo, $data['order_sn'], $parJson);
        if ($res['status']) {
            return [
                'status' => true, 
                'msg'=>'Success', 
                'data'=>$res['data']['data']['jsapi_pay_info']
            ];
        }
        return $res;
    }
    /**
     * 微信公众号
     * @param $data['wechat_appid'] 公众号appid      必填
     * @param $data['openid']       用户openid       必填
     * @param $data['price']        金额（元）       必填
     * @param $data['order_sn']     订单号（不能重复）必填
     * @param $data['notify_url']   异步通知地址      必填
     * @param $data['subject']      说明             可选
     * @return json
     */
    public function payWechat($data){
        $serviceNo = 'actionPay';
        $parJson = [
            'payWay' =>'WECHAT',
            'scene' =>'offline',
            'subject' =>isset($data['subject']) ? $data['subject'] : '购买商品',
            'amount' =>$data['price'],
            'notify_url' =>$data['notify_url'],
            'return_url' =>$data['notify_url'],
            'shopdate' =>date('Y-m-d'),
            'timeout' =>'10',
            'client_ip' =>$this->PayModel->getRealIp(),
            'sub_appid' => $data['wechat_appid'],
            'sub_openid' => $data['openid'],

        ];
        $res = $this->PayModel->postRequest($serviceNo, $data['order_sn'], $parJson);

        if ($res['status']) {
            return [
                'status' => true, 
                'msg'=>'Success', 
                'data'=>$res['data']['data']['jsapi_pay_info']
            ];
        }
        return $res;
    }
    /**
     * 微信小程序
     * @param $data['wechat_appid'] 公众号appid      必填
     * @param $data['openid']       用户openid       必填
     * @param $data['price']        金额（元）       必填
     * @param $data['order_sn']     订单号（不能重复）必填
     * @param $data['notify_url']   异步通知地址      必填
     * @param $data['subject']      说明             可选
     * @return json
     */
    public function payRoutine($data){
        $serviceNo = 'actionPay';
        $parJson = [
            'payWay' =>'WECHAT_MINI',
            'scene' =>'offline',
            'subject' =>isset($data['subject']) ? $data['subject'] : '购买商品',
            'amount' =>$data['price'],
            'notify_url' =>$data['notify_url'],
            'return_url' =>$data['notify_url'],
            'shopdate' =>date('Y-m-d'),
            'timeout' =>'10',
            'client_ip' =>$this->PayModel->getRealIp(),
            'sub_borrow' => 3,
            'sub_appid' => $data['wechat_appid'],
            'sub_openid' => $data['openid'],

        ];
        $res = $this->PayModel->postRequest($serviceNo, $data['order_sn'], $parJson);

        if ($res['status']) {
            return [
                'status' => true, 
                'msg'=>'Success', 
                'data'=>$res['data']['data']['jsapi_pay_info']
            ];
        }
        return $res;
    }

    /**
     * 生成跳转链接
     * @param $data['appid']    小程序appid        必填
     * @param $data['srcret']   小程序srcret       必填
     * @param $data['path']     小程序路径         必填
     * @param $data['query']    携带参数(&=拼接)    必填
     * @return json
     */
    public function getOpenlink($data) {
        $tokenUrl="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$data['appid']."&secret=".$data['srcret'];
        $tokenArr=$this->PayModel->post_url($tokenUrl);
        if (!isset($tokenArr['access_token'])) {
            return [
                'status' => false, 
                'msg'=> $tokenArr['errmsg'], 
            ];
        }
        $access_token=$tokenArr['access_token'];
        $url2 = "https://api.weixin.qq.com/wxa/generatescheme?access_token=".$access_token."";
        $pra = [
            'jump_wxa'  => [
                'path'  => "pages/payment/app2mp",
                'query' => $data['query'],
                'env_version' => 'release' //正式版为"release"，体验版为"trial"，开发版为"develop"
            ],
            "is_expire" => false
        ];
        $result = $this->PayModel->post_url($url2,$pra);
        if ($result['errcode'] != 0 || $result['errmsg'] != "ok") {
            return [
                'status' => false, 
                'msg'=> $result['errmsg'], 
            ];
        }
        return [
            'status' => true, 
            'msg'=>'Success', 
            'data'=>$result['openlink']
        ];
    }
    /**
     * 快捷支付
     * @param $data['price']        金额（元）       必填
     * @param $data['order_sn']     订单号（不能重复）必填
     * @param $data['notify_url']   异步通知地址      必填
     * @param $data['subject']      说明             可选
     * @return json
     */
    public function payOnekeyPay($data){
        $serviceNo = 'actionPay';
        $parJson = [
            'payWay' =>'ONEKEYPAY',
            'scene' =>'offline',
            'subject' =>isset($data['subject']) ? $data['subject'] : '购买商品',
            'amount' =>$data['price'],
            'notify_url' =>$data['notify_url'],
            'return_url' =>$data['notify_url'],
            'shopdate' =>date('Y-m-d'),
            'timeout' =>'10',
            'client_ip' =>$this->PayModel->getRealIp(),

        ];
        $res = $this->PayModel->postRequest($serviceNo, $data['order_sn'], $parJson);

        if ($res['status']) {
            return [
                'status' => true, 
                'msg'=>'Success', 
                'data'=>$res['data']['data']['jsapi_pay_info']['action_url']
            ];
        }
        return $res;
    }

    /**
     * 退款
     * @param $data['money']        金额（元）       必填
     * @param $data['order_sn']     订单号          必填
     * @param $data['refund_sn']    退款单号        必填
     * @param $data['notify_url']   异步通知地址     必填
     * @return json
     */
    public function refund($data){
        $serviceNo = 'refundOrder';
        $parJson = [
            'out_trade_no' => $data['order_sn'],
            'remark' =>'交易退款',
            'shopdate' =>date('Y-m-d'),
            'refundAmount' => $data['money'],
            'notify_url' =>$data['notify_url'],
            'client_ip' =>$this->PayModel->getRealIp(),

        ];
        $res = $this->PayModel->postRequest($serviceNo, $data['refund_sn'], $parJson);

        if ($res['status']) {
            return [
                'status' => true, 
                'msg'=>'Success', 
                'data'=>$res['data']['data']
            ];
        }
        return $res;
    }

}
