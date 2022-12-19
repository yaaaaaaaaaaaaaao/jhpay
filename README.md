# 几何支付thinkphp+uniapp
## 简介
>>此插件支持支付宝、微信、银联，H5、APP、微信小程序、公众号多个平台适用。
## 安装说明
>### thinkphp端
>>1.安装核心文件 composer require suixiang/jhpay  
>>2.编写控制器文件，代码如本目录下demo.php
>### uniapp端
>>1.使用HBuilderX导入插件
>>2.拉起收银台支付
```
import JhpayCashier from '@/components/suixiang-jhpay/index.vue'
<Jhpay-Cashier
	:apiUrl="apiUrl"              //请求地址
	:showPay.sync='showPay'		  
	:data='orderInfo'			  //订单信息
	@payResult='payResult'
></Jhpay-Cashier>

```

