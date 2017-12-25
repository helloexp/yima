<?php

/**
 * 支付模块 银联
 *
 * @author chensf
 */
import('@.ORG.Pay.UnionPay');

class PayUnionAction extends BaseAction {

    public $orderData = array();
    public $groupPhone = '';
    public $notifyUrl = '';
    // 异步通知地址
    public $callBackUrl = '';
    public $node_short_name = '';
    // 同步通知地址
    public $pri_key = "9019.pem";

    public function _initialize() {
        C( 'LOG_PATH', LOG_PATH . 'Log_union_' );        // 日志路径+文件名前缀,);
        C( 'CUSTOM_LOG_PATH', LOG_PATH . 'Log_union_' ); // 日志路径+文件名前缀,);
        
        $this-> node_short_name = M( 'tnode_info' )
                                  -> where( "node_id='" . $this->node_id . "'" )
                                  -> getField( 'node_short_name' );
        
        $this-> callBackUrl = U( 'Label/PayUnion/verifyReturn', '', '', '', TRUE );
        $this-> notifyUrl   = U( 'Label/PayUnion/verifyNotify', '', '', '', TRUE );
    }

    public function OrderPay( $orderId = null )
    {
        //$orderId = 1604071417128780;
        // 判断是否登录
        if ( !session( '?groupPhone' ))
        {
            $this-> error('您还没有登录');
        }
        $this-> groupPhone = session('groupPhone');
        $orderId = empty( $orderId ) ? I('order_id', null, 'mysql_real_escape_string' ) : $orderId;
        if ( empty( $orderId ))
        {
            $this-> error( '参数错误' );
        }

        // 获取订单信息
        $this-> orderData = D( 'StoreOrder' )-> getOrderInfo( $orderId );
        if ( false === $this-> orderData ) {
            $msg = D( 'StoreOrder' )-> getError();
            $this-> error( $msg );
        }
        $this-> unionpay( $orderId );
    }

    /**
     * 银联支付
     * @param $orderId
     */
    public function unionpay( $orderId )
    {
        $config = (object)C( 'UNIONPAY' );
        $params = [

            //以下信息非特殊情况不需要改动
            'version'     => '5.0.0',                 //版本号
            'encoding'    => 'utf-8',				  //编码方式
            'txnType'     => '01',				      //交易类型
            'txnSubType'  => '01',				      //交易子类
            'bizType'     => '000201',				  //业务类型
            'frontUrl'    => $this-> callBackUrl,     //前台通知地址
            'backUrl'     => $this-> notifyUrl,	      //后台通知地址
            'signMethod'  => '01',	                  //签名方法
            'channelType' => '08',	                  //渠道类型，07-PC，08-手机
            'accessType'  => '0',		              //接入类型
            'currencyCode'=> '156',	                  //交易币种，境内商户固定156

            //TODO 以下信息需要填写
            'merId'  => $config-> partner,		                   //商户代码，请改自己的测试商户号，此处默认取demo演示页面传递的参数
            'orderId'=> $orderId,	                               //商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
            'txnTime'=> date('YmdHis'),	                           //订单发送时间，格式为YYYYMMDDhhmmss，取北京时间，此处默认取demo演示页面传递的参数
            'txnAmt' => floor( bcmul($this->orderData['order_amt'],100)), //交易金额，单位分，此处默认取demo演示页面传递的参数
        ];
        UnionPay::sign( $params );
        $html_form = UnionPay::createAutoFormHtml( $params, $config-> FRONT_TRANS_URL );
        echo $html_form;
    }

    /**
     * 处理银联同步回传数据
     */
    public function verifyReturn() {
        log_write( ACTION_NAME . '_________' . file_get_contents('php://input'));

        //$test = '{"accNo":"cjSqVFVvtlhJlDEWA+q4jMckSuOT+fUJ1dZTJkaE+dU8kx2DcNqvMtiXUPPHWAp7oWi91IJHDd+dKG5xuNEmtIlgiXLwtr929pp8o6HrvIdelRLj0wBQSu2wQV42hnJKDffSQkEfYBtLJjSjG\/q9JP8v98gKwWSqgjhagj3i7U8B9wn4sYiO4dsl85rY24iWn0K+2ZRFgJ9\/aUN5Sd70\/L1OY6LeV8IaFzHlCs8r0W\/k0YEXbdr9TCprJ8stn3fAcIFV4DYB+P21Dlw3Tn8ugsZ8aclopqBlc33len0W1KthSztr4uEvWoWu46jHOFRs2PU2dRoU934pt70TD4GeGA==","accessType":"0","bizType":"000201","certId":"68759585097","currencyCode":"156","encoding":"utf-8","merId":"777290058110097","orderId":"1604071834260770","queryId":"201604071529431442728","respCode":"00","respMsg":"success","settleAmt":"18800","settleCurrencyCode":"156","settleDate":"0407","signMethod":"01","signature":"OJRMEl+aEWmJkeK2GCPdbRggNNq22IVE0nWCmhd1ZBIFD1MzXqAz7\/hFM7QeCqDyhVQg+Lmpe3hK3DwLtSPIFUeet1VSSHEzvSUao5\/ror+bWMcM63dk6jut3bgKOC9FkgKQg2CwMIDs9gTBYA9JXjxehe6kyjejoMg7r5T0oJKE7P+qxqpJqtLkMvruFQpOccbkBIg1YKtZ7XOiTVILPtWq9irqGvZsm5WllzYE3czkDv20u5m3P3mBgNoWxANRl\/C6g1PkV2AMXBfdFwEPGXpSVJX46STGybLaIKiRBfkdlms0GYYwFaUvFpgiOzcf0lkOJQ\/h0vb26bP64JDWOg==","traceNo":"144272","traceTime":"0407152943","txnAmt":"18800","txnSubType":"01","txnTime":"20160407152943","txnType":"01","version":"5.0.0"}';
        //$_POST  = json_decode( $test, true );

        $data = I('post.');

        if ( !isset ( $data['signature'] ))
        {
            log_write("签名为空");
            $this-> showMsg('信息验证失败', 0, null, '', '', '');
        }

        if( !UnionPay::validate ( $data ))
        {
            log_write("验签失败");
            $this-> showMsg('信息验证失败', 0, null, '', '', '');
        }

        $orderId  = $data['orderId']; //其他字段也可用类似方式获取
        $respCode = strtolower( trim( $data['respCode'] )); //判断respCode=00或A6即可认为交易成功

        $result   = false;
        if( '00' == $respCode || 'a6' == $respCode )
        {
            $result = true;
        }
        //订单处理
        D('StoreOrder')-> getVerifyOrderInfo( $orderId, 2, $result);
        exit;
    }

    /**
     * 处理银联异步回传数据
     */
    public function verifyNotify()
    {
        log_write( ACTION_NAME . '_________' . file_get_contents('php://input'));
        $data = I('post.');

        if ( !isset ( $data['signature'] ))
        {
            log_write("签名为空");
        }

        if( !UnionPay::validate ( $data ))
        {
            log_write("验签失败");
        }

        $orderId  = $data['orderId']; //其他字段也可用类似方式获取
        $respCode = strtolower( trim( $data['respCode'] )); //判断respCode=00或A6即可认为交易成功

        if( '00' == $respCode || 'a6' == $respCode )
        {
            $result = D('StoreOrder')-> verifyOrderInfo( $orderId, 2, $data['queryId'] );
            if ( true == $result )
            {
                log_write( "[success]银联支付，订单号 ：" . $orderId . " 支付成功!" );
            }
            else
            {
                log_write("[error]" . D('StoreOrder')-> getError());
            }
        }
        else
        {
            log_write("未支付");
        }
    }

    /**
     * 联动优势支付
     */
/*    public function unionpay($orderId) {
        $arr = array(
            'service' => 'pay_req_shortcut', 
            'charset' => 'GBK', 
            'mer_id' => '9019', 
            // 'sign_type' =>'RSA',
            // 'sign' => '',
            'notify_url' => $this->notifyUrl, 
            'ret_url' => $this->callBackUrl, 
            'res_format' => 'HTML', 
            'version' => '4.0', 
            'goods_id' => $this->orderData['batch_no'], 
            'goods_inf' => $this->orderData['group_goods_name'], 
            'order_id' => $this->orderData['order_id'], 
            'mer_date' => date('Ymd'), 
            'amount' => ceil($this->orderData['order_amt'] * 100), 
            'amt_type' => 'RMB', 
            'mer_priv' => 'IMAGECO', 
            'expand' => '', 
            'expire_time' => '30');
        
        ksort($arr, SORT_STRING);
        $sign_src = "";
        foreach ($arr as $key => $val) {
            if ($sign_src != "")
                $sign_src = $sign_src . "&";
            $sign_src = $sign_src . "$key=$val";
        }
        $sign = $this->rsa_sign($sign_src);
        $arr['ret_url'] = urlencode($arr['ret_url']);
        $arr['notify_url'] = urlencode($arr['notify_url']);
        $arr['sign_type'] = 'RSA';
        $sign_src = "";
        foreach ($arr as $key => $val) {
            if ($sign_src != "")
                $sign_src = $sign_src . "&";
            $sign_src = $sign_src . "$key=$val";
        }
        
        $sign = urlencode($sign);
        log_write(
            "请求联动优势：http://pay.soopay.net/spay/pay/payservice.do?" . $sign_src .
                 "&sign=" . $sign);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 
            "http://pay.soopay.net/spay/pay/payservice.do");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sign_src . "&sign=" . $sign);
        $output = curl_exec($ch);
        curl_close($ch);
        
        $meta_header = "<META NAME=\"MobilePayPlatform\" CONTENT=\"";
        $s = strpos($output, $meta_header);
        $e = strpos($output, "\">", $s + 1);
        
        $ret = substr($output, $s + strlen($meta_header), 
            $e - $s - strlen($meta_header));
        $ret = iconv("utf-8", "GBK", $ret);
        log_write("union_ret:" . $ret);
        // 解析返回结果 更新数据库联动订单号
        // 验签
        parse_str($ret, $ret_arr);

        $ret = $this->back_verify($ret_arr);
        if (! $ret) {
            $this->error('联动优势下订单返回验签失败');
            exit();
        }
        log_write("向联动优势下订单验签成功");
        
        if ($ret_arr['ret_code'] != '0000') {
            $this->error('联动优势下订单失败');
            exit();
        }
        
        // 更新订单表 联动优势订单号
        $result = M('ttg_order_info')->where("order_id='{$orderId}'")->save(
            array(
                'pay_trade_no' => $ret_arr['trade_no']));
        if ($result === false) {
            $this->error('更新联动优势订单号失败');
            exit();
        }
        
        redirect(
            "https://m.soopay.net/q/xhtml/index.do?tradeNo=" .
                 $ret_arr['trade_no']);
    }*/

    /*
     * 验签
     */
/*    public function back_verify($ret_arr) {
        $ret_arr['sign'] = str_replace(" ", "+", $ret_arr['sign']);
        $sign = $ret_arr['sign'];
        unset($ret_arr['sign']);
        unset($ret_arr['sign_type']);
        ksort($ret_arr, SORT_STRING);
        
        $sign_src = urldecode(http_build_query($ret_arr));
        return $this->rsa_verify($sign_src, $sign);
    }*/

    /**
     * 处理联动同步回传数据 只判断交易状态trade_state 跳转页面
     */
/*    public function verifyReturn() {
        log_write("verifyReturn:" . $_SERVER["REQUEST_URI"]);
        
        // parse_str($_SERVER['QUERY_STRING'], $data);
        $data = I('get.');
        $verify_result = $this->back_verify($data);
        if ($verify_result){
            log_write("verifyReturn success");
        }else {
            log_write("verifyReturn fail");
            $this->showMsg('信息验证失败', 0, null, '', '', '');
        }

        $status = strtolower(trim($data['trade_state']));
        $result = false;
        if('trade_success' == $status)
        {
            $result = true;
        }
        //订单处理
        D('StoreOrder')->getVerifyOrderInfo($data['order_id'], 2, $result);
        exit;
        
    }*/

    /**
     * 处理联动优势异步回传数据 验证该回传数据是否是联动优势 取的联动优势回传的支付状态 如果支付成功则返回订单信息进行生成凭证的后续工作
     * 如果支付失败则返回FALSE
     */
 /*   public function verifyNotify() {
        log_write("verifyNotify:" . $_SERVER["REQUEST_URI"]);
        
        // parse_str($_SERVER['QUERY_STRING'], $data);
        $data = I('get.');
        $verify_result = $this->back_verify($data);
        if ($verify_result)
            log_write("verifyNotify success");
        else
            log_write("verifyNotify fail");
        
        $status = $data['trade_state'];
        if ($status == 'TRADE_SUCCESS') {
            // 交易成功结束
            // 判断该笔订单是否在商户网站中已经做过处理
            // 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            // 如果有做过处理，不执行商户的业务程序
            // 取得订单信息
            
            $result = D('StoreOrder')->verifyOrderInfo($data['order_id'], 2, 
                $data['pay_seq']);
            if (true == $result) {
                // tag('view_end');
                log_write(
                    "[success]联动优势支付，订单号 ：" . $data['order_id'] . " 支付成功!");
            } else {
                log_write("[error]" . D('StoreOrder')->getError());
            }
        }
        // 返回成功
        log_write("联动优势支付异步返回接口通知永远返回成功");
        $arr = array(
            'mer_id' => '9019', 
            'version' => '4.0', 
            'ret_code' => '0000', 
            'ret_msg' => 'success', 
            'order_id' => $data['order_id'], 
            'mer_date' => $data['mer_date']);
        ksort($arr, SORT_STRING);
        $sign_src = "";
        foreach ($arr as $key => $val) {
            if ($sign_src != "")
                $sign_src = $sign_src . "&";
            $sign_src = $sign_src . "$key=$val";
        }
        $sign = $this->rsa_sign($sign_src);
        echo '<META NAME="MobilePayPlatform" CONTENT="mer_date=' .
             $data['mer_date'] . '&mer_id=' . $data['mer_id'] . '&order_id=' .
             $data['order_id'] .
             '&ret_code=0000&ret_msg=success&sign_type=RSA&version=4.0&sign=' .
             $sign . '" />';
        exit();
    }*/

    /*
     * //发码处理 public function sendCode($orderId){ //取得订单信息 $orderModel =
     * M('ttg_order_info'); $orderInfo =
     * $orderModel->where("order_id='{$orderId}'")->find(); if(!$orderInfo){
     * log_write("订单发码：未找到订单信息。订单号：{$orderId}"); $this->msgJump('未找到订单信息'); }
     * //发码 $transId = date('YmdHis').sprintf('%04s',mt_rand(0,1000)); import (
     * "@.Vendor.SendCode" ); $req = new SendCode(); $res =
     * $req->wc_send($orderInfo['node_id'],'',$orderInfo['group_batch_no'],$orderInfo['receiver_phone'],'8',$transId);
     * if($res === true){ $result =
     * $orderModel->where("order_id={$orderInfo['order_id']}")->save(array('send_seq'=>$transId));
     * if($result === false){
     * log_write("订单发码成功,更新request_id失败;order_id:{$orderInfo['order_id']},send_seq:{$transId}");
     * } }else{ log_write("订单发码失败,原因:{$res}"); } }
     */
    
    // 信息跳转
    public function msgJump($info, $status = 0, $id = '') {
        $info = urlencode($info);
        $this->redirect('Label/PayUnion/showMsg', 
            array(
                'info' => $info, 
                'status' => $status, 
                'id' => $id));
    }

    /*
     * //输出信息页面 public function showMsg(){ $info = I('info',null); $status =
     * I('status',null); $id = I('id',null); $this->assign('id',$id);
     * $this->assign('status',$status); $this->assign('info',$info);
     * $this->display('msg'); exit; }
     */
    
    // 输出信息页面
    protected function showMsg($info, $status, $id, $order_id,
        $order_type = null, $node_id = null, $sourceInfo = '') {
        A('Label/PayMent')->showMsgInfo($info, $status, $id, $order_id, 
            $node_id, $this->node_short_name, $order_type, $sourceInfo);
    }

/*    public function rsa_sign($data) {
        // 读取私钥文件
        $priKey = file_get_contents(CONF_PATH . 'key/9019.pem');
        // 转换为openssl密钥，必须是没有经过pkcs8转换的私钥
        // $res = openssl_get_privatekey($priKey);
        // 调用openssl内置签名方法，生成签名$sign
        // openssl_private_encrypt(pack('H*',md5($data)), $crypttext,$priKey,
        // OPENSSL_PKCS1_PADDING);
        $req_key = openssl_pkey_get_private($priKey);
        // echo "\n====({$req_key})\n";
        
        $ret = openssl_sign(($data), $crypttext, $req_key, OPENSSL_ALGO_SHA1);
        // echo "===".$ret."===";
        // $ret = openssl_private_encrypt(sha1($data), $crypttext,$priKey,
        // OPENSSL_PKCS1_PADDING );
        // 释放资源
        // openssl_free_key($res); //base64编码
        $sign = base64_encode($crypttext);
        return $sign;
    }*/

    /* * RSA验签 * $data待签名数据 * $sign需要验签的签名 * 验签用公钥 * return 验签是否通过 bool值 */
/*    public function rsa_verify($data, $sign) {
        // 读取联动优势公钥文件
        $pubKey = file_get_contents(CONF_PATH . 'key/cert.pem');
        // echo('数据'.$data.'密钥：'.$pubKey);
        $req_key = openssl_get_publickey($pubKey);
        // log_write('decode数据'.base64_decode($sign));
        // 转换为openssl格式密钥
        // $oks=openssl_public_decrypt(base64_decode($sign),$crypttext,$pubKey,
        // OPENSSL_PKCS1_PADDING);
        $ret = openssl_verify($data, base64_decode($sign), $req_key);
        return $ret;
        // echo('sign='.$sign.'crypttext=='.$crypttext);
        // $endata=bin2hex($crypttext);
        // echo("md5 data".md5($data).'====解密data'.$endata);
    }*/
    
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
}
