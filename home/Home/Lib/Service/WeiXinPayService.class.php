<?php
// 微信支付接口
class WeiXinPayService {

    public $appid;

    public $mch_id;

    public $app_key;

    public $timeout;

    public $error = '';
    // 初始化
    // add by kk @2015年9月1日16:09:54 ：添加商户自定义参数
    public function init($node_id = null) {
        $this->appid = C('WEIXINPAY.appid');
        $this->mch_id = C('WEIXINPAY.mch_id');
        $this->app_key = C('WEIXINPAY.app_key');
        if ($node_id) {
            $wxConfig = M('tnode_wxpay_config')->where(
                array(
                    'node_id' => $node_id))->find();
            if ($wxConfig) {
                $this->appid = $wxConfig['appid'];
                $this->mch_id = $wxConfig['mch_id'];
                $this->app_key = $wxConfig['paykey'];
            }
        }
        $this->timeout = 30;
    }

    public function sign($in_arr) {
        $src_arr = $in_arr;
        unset($src_arr['sign']);
        foreach ($src_arr as $k => $v) {
            if ($v == null) {
                unset($src_arr[$k]);
            }
        }
        ksort($src_arr, SORT_STRING);
        $sign_src = urldecode(http_build_query($src_arr));
        $sign_src = $sign_src . '&key=' . $this->app_key;
        $sign = strtoupper(md5($sign_src));
        return $sign;
    }
    // 统一下单接口
    /*
     * 入参： $in_arr['body'] = ''; //商品描述 String(32) $in_arr['out_trade_no'] = '';
     * //商户订单号 String(32) $in_arr['total_fee'] = ''; //总金额 int 分为单位
     * $in_arr['spbill_create_ip'] = ''; //用户终端IP String(16)
     * $in_arr['notify_url'] = ''; //通知地址 String(256) $in_arr['trade_type'] =
     * 'JSAPI'; //交易类型 String(16) $in_arr['openid'] = ''; //交易类型 String(128)
     * trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识 $in_arr['product_id'] = '';
     * //交易类型 String(32) 允许为空 $in_arr['detail'] = ''; //商品详情 String(8192) 允许为空
     * $in_arr['attach'] = ''; //自定义附加数据 String(127) 在查询API和支付通知中原样返回 允许为空
     * $in_arr['time_start'] = ''; //交易起始时间 String(14) 允许为空
     * $in_arr['time_expire'] = ''; //交易结束时间 String(14) 允许为空
     * $in_arr['goods_tag'] = ''; //商品标记 String(32) 允许为空 代金券或立减优惠功能的参数
     * $in_arr['device_info'] = ''; //设备号 String(32) 允许为空 返回参数：array类型 $ret_arr
     * ['wx_status'] true or false 判断交易成功与否 false 取 $ret_arr ['return_msg'] 描述
     * true $ret_arr['prepay_id'] 微信预订单号 $ret_arr['jsapi_arr'] jsapi调用所需相关参数
     */
    public function api_unifiedorder($in_arr) {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $in_arr['appid'] = $this->appid;
        $in_arr['mch_id'] = $this->mch_id;
        $in_arr['nonce_str'] = $nonce_str = md5(
            $in_arr['out_trade_no'] . $in_arr['body']);
        $in_arr['sign'] = $this->sign($in_arr);
        foreach ($in_arr as $k => $v) {
            if ($v != null) {
                $in_arr[$k] = '<![CDATA[' . $v . ']]>';
            }
        }
        $data['xml'] = $in_arr;
        $ret_arr = $this->request_weixin_pay($data, $url, $this->timeout);
        if ($ret_arr['return_code'] == 'SUCCESS' &&
             $ret_arr['result_code'] == 'SUCCESS') {
            $jsapi_arr['appId'] = $this->appid;
            $jsapi_arr['timeStamp'] = time();
            $jsapi_arr['nonceStr'] = $nonce_str;
            $jsapi_arr['package'] = 'prepay_id=' . $ret_arr['prepay_id'];
            $jsapi_arr['signType'] = 'MD5';
            $jsapi_arr['paySign'] = $this->sign($jsapi_arr);
            $ret_arr['wx_status'] = true;
            $ret_arr['jsapi_arr'] = $jsapi_arr;
        } else {
            $ret_arr['wx_status'] = false;
        }
        log_write('return arr:' . print_r($ret_arr, true), 'INFO', 'wxpay');
        return $ret_arr;
    }
    // 支付通知校验接口
    /*
     * 入参： 收到的XML报文字符串 返回参数： array类型 $ret_arr ['wx_status'] true or false
     * 判断支付交易成功与否 false 取 $ret_arr ['err_code_des'] 描述 true 的话比较重要的参数如下 微信支付订单号
     * transaction_id String(32) 商户订单号 out_trade_no String(32) 支付完成时间 time_end
     * String(14) 总金额 total_fee Int 现金支付金额 cash_fee Int 其他参数详见
     * http://pay.weixin.qq.com/wiki/doc/api/index.php?chapter=9_7# $ret_arr
     * ['ret_str'] 存放要返回给微信的xml报文，业务处理完输出这个字符串。
     */
    public function api_notify_verify($in_data) {
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $in_data = get_magic_quotes_gpc() ? stripslashes($in_data) : $in_data;
        Log::write('recefromwxpay:' . print_r($in_data, true), 'wxpay');
        $arr = $xml->parse($in_data);
        $arr = $xml->getAll();
        $arr = $arr['xml'];
        // 转换成 utf-8 编码
        // array_walk_recursive($arr,'utf8Str');
        if ($xml->error()) {
            $ret_arr = array(
                'xml' => array(
                    'return_code' => 'FAIL', 
                    'return_msg' => 'PARSE_XML_ERROR'));
        } else {
            // verify
            $model = M('ttg_order_info');
            $orderId = isset($arr['out_trade_no']) ? $arr['out_trade_no'] : false;
            if ($orderId) {
                $order_id = mysql_real_escape_string($orderId);
                $nodeId = $model->where(
                    array(
                        'order_id' => $orderId))->getField('node_id');
                $this->init($nodeId);
            }
            $sign = $this->sign($arr);
            if ($sign !== $arr['sign']) {
                $ret_arr = array(
                    'xml' => array(
                        'return_code' => 'FAIL', 
                        'return_msg' => 'SIGN_VERIFY_ERROR'));
            } else {
                $ret_arr = array(
                    'xml' => array(
                        'return_code' => 'SUCCESS', 
                        'return_msg' => 'OK'));
            }
        }
        
        $xml = new Xml();
        $ret_str = $xml->getXMLFromArray($ret_arr, 'utf-8');
        if ($arr['return_code'] == 'SUCCESS' && $arr['result_code'] == 'SUCCESS' &&
             $arr['err_code'] == null &&
             $ret_arr['xml']['return_code'] == 'SUCCESS') {
            $arr['wx_status'] = true;
        } else {
            $arr['wx_status'] = false;
        }
        $arr['ret_str'] = $ret_str;
        return $arr;
    }

    /* 微信接口发送 */
    public function request_weixin_pay($data, $url, $timeout) {
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'utf-8');
        $error = '';
        log_write('sendtowxpay:' . $str, 'INFO', 'wxpay');
        $result_str = httpPost($url, $str, $error, 
            array(
                'TIMEOUT' => $timeout));
        // echo "[".$result_str."]";
        // $result_str = iconv('gbk','utf-8',$result_str);
        log_write('recefromwxpay:' . $result_str, 'INFO', 'wxpay');
        $xml = new Xml();
        $result_str = get_magic_quotes_gpc() ? stripslashes($result_str) : $result_str;
        log_write('recefromwxpay:' . $result_str, 'INFO', 'wxpay');
        $arr = $xml->parse($result_str);
        $arr = $xml->getAll();
        // 转换成 utf-8 编码
        // array_walk_recursive($arr,'utf8Str');
        if ($xml->error()) {
            return array(
                'xml' => array(
                    'return_code' => '-1', 
                    'return_msg' => $xml->error()));
        }
        return $arr['xml'];
    }
    // unicode字符转可见
    public function unicodeDecode($name) {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 
            create_function('$matches', 
                'return mb_convert_encoding(pack("H*", $matches[1]), "utf-8", "UCS-2BE");'), 
            $name);
    }
}