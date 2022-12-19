<?php

// +----------------------------------------------------------------------
// | 几何支付 - demo
// +----------------------------------------------------------------------
// | 官方接口文档：https://www.yuque.com/laoluotongxue/gxog7z/kzeeem
// +----------------------------------------------------------------------
// | 技术支持：QQ-736849829
// +----------------------------------------------------------------------
// | Author: Yao <736849829@qq.com>
// +----------------------------------------------------------------------


namespace app\controller;

use Suixiang\Jhpay\PayService;
use Suixiang\Jhpay\PayModel;

class Pay
{   
    /**
     * 支付接口
     * @param string    $payType    支付类型
     * @param array     $data       自定义数据，一般包含订单号，用来获取订单详情金额等
    */
    public function index() {
         // ------ 这里处理获取订单信息业务 ------
            // $data = request()->param('data'); //通过自定义$data获取订单信息
            // demo
            $payData = [
                'price'     => rand(2, 100)/100, //金额（元）
                'order_sn'  => date("YmdHis"), //订单号
                'notify_url'=> 'https://demo.com/', //异步通知地址
                'subject'   => '购买商品' //订单说明
            ];
         // ------ 这里处理获取订单信息业务 ------
        
        $payType = request()->param('payType');
        switch ($payType) {

            case 'alipayQr': // 支付宝h5
            case 'alipayApp':// 支付宝app
                $PayService = new PayService($this->getConfig());
                $result = $PayService->payAlipayQr($payData);
                return json($result);

            case 'wechat':// 微信支付-公众号
                $PayService = new PayService($this->getConfig());
                $payData['wechat_appid'] = '公众号appid';//公众号appid
                $payData['openid']       = '用户openid';//用户openid
                $result = $PayService->payWechat($payData);
                return json($result);

            case 'routine':// 微信支付-小程序
                $PayService = new PayService($this->getConfig());
                $payData['wechat_appid'] = '小程序appid';//小程序appid
                $payData['openid']       = '用户openid';//用户openid
                $result = $PayService->payRoutine($payData);
                return json($result);

            case 'wechatQr':
            case 'wechatApp':
                /**
                 * 微信支付-APP 和 h5
                 * 这个要特别说明一下，这里采取拉取微信小程序支付的方式，详细步骤如下：
                 * 1.生成跳转到小程序链接（方法如下），获得以weixin://开头的链接
                 * 2.打开链接，到小程序落地页
                 * 3.获取到步骤一携带参数，用来获取订单信息
                 * 4.通过上一个接口“微信支付-小程序”，拉起微信支付
                */
                $PayService = new PayService($this->getConfig());
                $result = $PayService->getOpenlink([
                    'appid'     => '小程序appid',//小程序appid
                    'srcret'    => '小程序srcret',//小程序srcret
                    'path'      => 'pages/payment/index', //小程序路径
                    'query'     => 'order_sn='.$payData['order_sn'],  //携带参数(&=拼接) 
                ]);
                return json($result);

            case 'onekeypay':// 银行快捷支付
                $PayService = new PayService($this->getConfig());
                $result = $PayService->payOnekeyPay($payData);
                if ($result['status']) {
                    $result['data'] = htmlspecialchars_decode($result['data']); //防止特殊字符
                }
                return json($result);
            
            default:
                return json(["status"=>false, "msg"=> "payType Error!"]);
        }
    }
    /**
     * 交易退款
    */
    public function refund() {
        $PayService = new PayService($this->getConfig());
        $result = $PayService->refund([
            'money'         => 0.01, //金额（元）
            'order_sn'      => '20221209164807',  //订单号
            'notify_url'    => 'https://demo.com/', //异步通知地址
            'refund_sn'     => 'R'.date("YmdHis") //退款单号
        ]);
        return json($result);
    }

    /**
     * 异步通知
    */
    public function notify() {
        $PayModel = new PayModel($this->getConfig());
        $log = true; //是否记录日志, 会在创建jhpayNotify文件夹
        $post = request()->post(); //接收post参数
        $data = $PayModel->notify($log, $post);
        if ($data) {
            // 到这一步已经验签成功了，开始处理你的业务吧！
            // 到这一步已经验签成功了，开始处理你的业务吧！
            // 到这一步已经验签成功了，开始处理你的业务吧！
            return "SUCCESS";
        }
    }

    private function getConfig() {
        return [
            // 商户号
            'appid' => '开户后获取',
            // 秘钥
            'secret' => '开户后获取',
            // 私钥
            'private_key' => '开户后获取',
            // 公钥
            'public_key' => '开户后获取',
            // 支付通道
            'channel' => 'yibaopay'
        ];
    }

}
