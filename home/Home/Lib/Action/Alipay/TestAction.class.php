<?php

/**
 * 惠付模块
 * Class HuifuAction
 */
class TestAction extends BaseAction
{
    public $_authAccessMap = '*';
    public function __construct()
    {
        $this-> setApi();
    }
    public function test()
    {
        $doman = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
        $param = [
            'appid'          => 'wxccabc929493425ad',
            'redirect_uri'   => U('Alipay/Test/test', '', '', '', TRUE ),
            'response_type'  => 'code',
            'scope'          => 'snsapi_userinfo',
            'state'          => 'STATE',
            'component_appid'=> 'wxf6db60cb8a1320da',
        ];

        $url = $doman . http_build_query( $param ) . '#wechat_redirec';

        $code  = I('code');
            $state = I('state');
        if ( empty( $code ) && empty( $state ))
        {
            header("Location: " . $url);
            exit();
        }
        var_dump($code);
    }
    
    public function testPayMent()
    {
        $orderId = I('orderId');
        $param = [
            'service'        => 'alipay.wap.trade.create.direct',
            'sign'           =>  '006c21991fc9248534888785821bd7dd',
            'sec_id'         => 'MD5',
            'v'              => '1.0',
            'notify_data'    => '<notify><payment_type>1</payment_type><subject>测试商品分组</subject><trade_no>1606072682753010</trade_no>'
            . '<buyer_email>akeaifanfan@sina.com</buyer_email><gmt_create>2016-06-07 15:58:36</gmt_create>'
            . '<notify_type>trade_status_sync</notify_type><quantity>1</quantity><out_trade_no>'.$orderId.'</out_trade_no>'
            . '<notify_time>2016-06-07 15:58:51</notify_time><seller_id>2088301462777393</seller_id><trade_status>TRADE_SUCCESS</trade_status>'
            . '<is_total_fee_adjust>N</is_total_fee_adjust><total_fee>258.00</total_fee><gmt_payment>2016-06-07 15:58:51</gmt_payment>'
            . '<seller_email>impay@imageco.com.cn</seller_email><price>258.00</price><buyer_id>2088002077447373</buyer_id>'
            . '<notify_id>c8526e3994a1fa4bc51f9296543aeadiuu</notify_id><use_coupon>N</use_coupon></notify>',
        ];
        $url = 'http://test.wangcaio2o.com/alipay_notify.php';
        $info = $this->send($url, 0, $param);
        
        echo $info;
    }
    
    
    public function send($url, $type = 0, $data = '') {
        $url = urldecode($url);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, $type);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // https
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // https
        $data = curl_exec($curl);
        curl_close($curl); 
        
        $arr = @json_decode($data, true);
        
        return $data;
    }
}