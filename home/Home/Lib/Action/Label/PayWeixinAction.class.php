<?php

/**
 * 微信支付模块
 *
 * @author chensf
 */
class PayWeixinAction extends MyBaseAction {

    public $orderData = array();

    public $groupPhone = '';

    public $notifyUrl = '';
    // 异步通知地址
    public $callBackUrl = '';
    // 同步通知地址
    public $pay_fee_rate = 0;
    // 支付费率
    public $split_min_day = 4;
    // 分润天数
    public $node_short_name = '';

    public $appid;

    public $secret;

    /**
     *
     * @var WeiXinService
     */
    public $WeiXinService;

    public function _initialize() {
        C('LOG_PATH', LOG_PATH . 'Log_weixin_'); // 日志路径+文件名前缀,);
        C('CUSTOM_LOG_PATH', LOG_PATH . 'Log_weixin_'); // 日志路径+文件名前缀,);
        $this->callBackUrl = U('Label/PayWeixin/verifyReturn', '', '', '', TRUE);
        $this->notifyUrl = C('CURRENT_HOST') . 'weixin_notify.php';
        // $this->appid = 'wx06d2717f0e7e3824';
        // $this->secret = 'c5d94546cc378e0e86bc23eef7a0abbe';
        // $this->appid = 'wx5acb63e448b4fc22'; //公众号 cpy
        // $this->secret = '18b40cd823f7e3ba4f4b69d74752016a';
        //
        // $this->node_short_name = M('tnode_info')->where("node_id='" .
        // $this->node_id . "'")->getField('node_short_name');
        // 上海翼码技术部公众账号
        // $this->appid = 'wxccabc929493425ad';
        // $this->secret = '8c0fd95cdb2fbbbb5d1222fa32777f99';
        
        $this->node_short_name = M('tnode_info')->where(
            "node_id='" . $this->node_id . "'")->getField('node_short_name');
        
        $this->WeiXinService = D('WeiXin', 'Service');
        $this->appid = C('WEIXIN.appid');
        $this->secret = C('WEIXIN.secret');
    }

    /**
     *
     * @param null $orderId
     * @return void 通过订单号，判断客户是否有配置自己的微信支付通道，如果有，则取配置的信息 使用机构有：广西石油
     *         kk@2015年9月1日10:51:55
     */
    public function _loadNodeWxPay($orderId = null) {
        if (! $orderId)
            return;
        
        $nodeId = M('ttg_order_info')->where(
            array(
                'order_id' => $orderId))->getField('node_id');
        if (! $nodeId)
            return;
        
        $wxConf = M('tnode_wxpay_config')->where(
            array(
                'node_id' => $nodeId))->find();
        if (! $wxConf)
            return;
        
        $this->appid = $wxConf['appid'];
        $this->secret = $wxConf['secret'];
    }
    
    // 跳转授权地址
    public function goAuthorize($orderId = null) {
        $this->_loadNodeWxPay($orderId);
        
        $backurl = U('Label/PayWeixin/OrderPay', 
            array(
                'order_id' => $orderId), '', '', TRUE);
        
        $this->wechatAuthorizeAndRedirectByDetailParam($this->appid, 
            $this->secret, 0, $backurl, '', 0);
        
        /*
         * //授权地址 $open_url =
         * 'https://open.weixin.qq.com/connect/oauth2/authorize?'; //回调地址
         * $backurl =
         * U('Label/PayWeixin/OrderPay',array('order_id'=>$orderId),'','',TRUE);
         * //授权参数 $opt_arr = array( 'appid' => $this->appid, 'redirect_uri' =>
         * $backurl, 'response_type' => 'code', 'scope' => 'snsapi_base',
         * //'scope'=> 'snsapi_userinfo' ); $link = http_build_query($opt_arr);
         * $gourl = $open_url . $link .'#wechat_redirect';
         * //header('location:'.$gourl); redirect($gourl);
         */
    }

    public function OrderPay($orderId = '') {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->showMsg('您还没有登录', 0, null);
        }
        
        // mysql_real_escape_string执行之前，需要连接数据库
        M('tsystem_param')->find();
        if ('' == $orderId)
            $orderId = I('order_id', null, 'mysql_real_escape_string');
        if (empty($orderId))
            $this->showMsg('参数错误', 0, null);
        
        $this->_loadNodeWxPay($orderId);
        
        $code = I('code', null);
        if (empty($code)) {
            $this->error('参数错误！');
        }
        
        $weixinInfo = array(
            'appid' => $this->appid, 
            'secret' => $this->secret);
        
        $result = $this->WeiXinService->getOpenid($code, $weixinInfo);
        $access_token = $result['access_token'];
        $openid = $result['openid'];
        if ($access_token == '' || $openid == '')
            $this->error('获取授权openid失败！');
        if (session('groupPhone') == '15201763752') {
            $openid = 'oVTJst5eby8t8ikJkI0DMpvM8flI'; // chensf
        } else {
            $openid = 'oVTJst0Nl0uWprO7j1ac4xcFXc6M'; // xietm
        }
        $this->groupPhone = session('groupPhone');
        
        // 获取订单信息
        $this->orderData = D('StoreOrder')->getOrderInfo($orderId);
        if (false === $this->orderData) {
            $msg = D('StoreOrder')->getError();
            $this->error($msg);
        }
        
        $this->orderData['openid'] = $openid;
        $this->weixinpay();
    }
    
    // 获取openid
    protected function getOpenid($code) {
        if (empty($code))
            return false;
        
        $apiUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' .
             $this->appid . '&secret=' . $this->secret . '&code=' . $code .
             '&grant_type=authorization_code';
        $result = $this->httpsGet($apiUrl);
        if (! $result) {
            return false;
        }
        $result = json_decode($result, true);
        if ($result['errcode'] != '') {
            return false;
        }
        return $result;
    }

    /**
     * 微信支付
     */
    public function weixinpay() {
        $inarr = array();
        
        $in_arr['body'] = $this->orderData['group_goods_name']; // 商品描述<String(32)>
        
        $in_arr['out_trade_no'] = $this->orderData['order_id']; // 商户订单号<String(32)>
        
        $in_arr['total_fee'] = $this->orderData['order_amt'] * 100; // 总金额<int>分为单位
        
        $in_arr['spbill_create_ip'] = $this->orderData['order_ip']; // 用户终端IP<String(16)>
        
        $in_arr['notify_url'] = $this->notifyUrl; // 通知地址<String(256)>
                                                  // $in_arr['notify_url'] =
                                                  // "http://222.44.51.34/index.php?g=Label&m=PayWeixin&a=verifyNotify";
                                                  // 通知地址
        
        $in_arr['trade_type'] = 'JSAPI'; // 交易类型 String(16)
        $in_arr['openid'] = $this->orderData['openid']; // 交易类型 String(128)
                                                        // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识
        
        $in_arr['product_id'] = ''; // 交易类型 String(32) 允许为空
        $in_arr['detail'] = $this->orderData['group_goods_name']; // 商品详情
                                                                  // String(8192)
                                                                  // 允许为空
        
        $in_arr['attach'] = ''; // 自定义附加数据 String(127) 在查询API和支付通知中原样返回 允许为空
        $in_arr['time_start'] = ''; // 交易起始时间 String(14) 允许为空
        $in_arr['time_expire'] = ''; // 交易结束时间 String(14) 允许为空
        $in_arr['goods_tag'] = ''; // 商品标记 String(32) 允许为空 代金券或立减优惠功能的参数
        $in_arr['device_info'] = ''; // 设备号 String(32) 允许为空
        
        $wx_pay = D('WeiXinPay', 'Service');
        $wx_pay->init($this->orderData['node_id']);
        $result = $wx_pay->api_unifiedorder($in_arr);
        log_write("微信返回信息：" . print_r($result, true), 'ERROR', 'WXPAY');
        if ($result['wx_status']) {
            // 更新ttg_order_info的pay_trade_no
            log_write(
                "微信下单成功. 订单号：{$this->orderData['order_id']}, 订单信息:" .
                     print_r($in_arr['return_msg'], true), 'SUCCESS', 'WXPAY');
            $ret = M('ttg_order_info')->where(
                array(
                    'order_id' => $this->orderData['order_id']))->save(
                array(
                    'pay_trade_no' => $result['prepay_id']));
            if ($ret === false) {
                $this->showMsg('微信下单更新微信订单号失败:' . $result['return_msg'], 0, 
                    $this->orderData['batch_channel_id'], 
                    $this->orderData['order_id'], '', 
                    $this->orderData['node_id']);
            }
            $this->assign('id', $this->orderData['batch_channel_id']);
            $this->assign('bridgeArr', $result['jsapi_arr']);
            $this->assign('orderInfo', $this->orderData);
            $this->display('weixinpay');
        } else {
            log_write(
                "微信下单失败. 订单号：{$this->orderData['order_id']}, 订单信息:" .
                     print_r($in_arr['return_msg'], true), 'ERROR', 'WXPAY');
            $this->showMsg('微信下单失败:' . $result['return_msg'], 0, 
                $this->orderData['batch_channel_id'], 
                $this->orderData['order_id'], '', $this->orderData['node_id']);
        }
    }

    /**
     * 处理微信支付完成调用显示
     */
    public function verifyReturn() {
        log_write(ACTION_NAME . '_________' . print_r($_POST, true));
        log_write(ACTION_NAME . '_________' . print_r($_GET, true));
        
        $orderId = I('orderId', null);
        $status = I('status', 0);
        
        $result = false;
        if('1' == $status)
        {
            $result = true;
        }
        //订单处理
        D('StoreOrder')->getVerifyOrderInfo($orderId, 3, $result);
        exit;
    }

    /**
     * 处理支付宝异步回传数据 验证该回传数据是否是支付宝 取的支付宝回传的支付状态 如果支付成功则返回订单信息进行生成凭证的后续工作
     * 如果支付失败则返回FALSE
     */
    public function verifyNotify() {
        $data = file_get_contents('php://input');
        log_write(ACTION_NAME . '_________' . $data);
        $wx_pay = D('WeiXinPay', 'Service');
        $wx_pay->init();
        $result = $wx_pay->api_notify_verify($data);
        log_write('微信返回数据_________:' . print_r($result, true));
        
        if (! $result['wx_status']) {
            log_write('微信支付异步返回接口交易失败');
            echo $result['ret_str'];
            exit();
        } elseif ($result['wx_status']) {
            // 交易成功结束
            // 判断该笔订单是否在商户网站中已经做过处理
            // 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            // 如果有做过处理，不执行商户的业务程序
            $data = array();
            $data['order_seq'] = $result['out_trade_no']; // 外部交易号
            $data['transaction_id'] = $result['transaction_id']; // 支付流水号
            
            $result = D('StoreOrder')->verifyOrderInfo($data['order_seq'], 3, 
                $data['transaction_id']);
            if (true == $result) {
                echo "success";
                // tag('view_end');
                log_write("[success]微信支付，订单号 ：" . $data['order_seq'] . " 支付成功!");
            } else {
                echo "fail";
                log_write("[error]" . D('StoreOrder')->getError());
            }
            exit();
        }
    }
 
    // 抽奖
    protected function cj($orderId) {
        // 取得订单信息
        $orderModel = M('ttg_order_info');
        $orderInfo = M()->table('ttg_order_info o')
            ->field('o.batch_channel_id,o.receiver_phone,g.is_cj')
            ->join("tmarketing_info g ON o.group_batch_no=g.member_level")
            ->where("o.order_id='{$orderId}'")
            ->find();
        if (! $orderInfo) {
            log_write("订单发码：未找到订单信息。订单号：{$orderInfo['order_id']}");
            return false;
        }
        $info = '';
        // 是否抽奖
        if ($orderInfo['is_cj'] == 1) {
            // 去抽奖
            
            import('@.Vendor.ChouJiang');
            $choujiang = new ChouJiang($orderInfo['batch_channel_id'], 
                $orderInfo['receiver_phone']);
            $resp = $choujiang->send_code();
            if ($resp === true) {
                $info = '恭喜您中奖了！';
                return $info;
            } else {
                $info = '很抱歉,您没有中奖!';
                return $info;
            }
        }
        return $info;
    }
    
    // 输出信息页面
    protected function showMsg($info, $status, $id, $order_id, 
        $order_type = null, $node_id = null, $sourceInfo = '') {
        A('Label/PayMent')->showMsgInfo($info, $status, $id, $order_id, 
            $node_id, $this->node_short_name, $order_type, $sourceInfo);
    }
    
    // 短链接
    public function shortUrl($long_url) {
        if (! $long_url) {
            return '';
        }
        $apiUrl = C('ISS_SERV_FOR_IMAGECO');
        $req_arr = array(
            'CreateShortUrlReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'TransactionID' => time() . rand(10000, 99999), 
                'OriginUrl' => "<![CDATA[$long_url]]>"));
        
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($req_arr, 'gbk');
        $error = '';
        $result_str = httpPost($apiUrl, $str, $error);
        if ($error) {
            echo $error;
            return '';
        }
        
        $arr = $xml->parse($result_str);
        $arr = $xml->getArrayNoRoot();
        
        return $arr['Status']['StatusCode'] == '0000' ? $arr['ShortUrl'] : '';
    }
    
    // 请求接口
    protected function httpsGet($apiUrl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $result = curl_exec($ch);
        return $result;
    }
}
