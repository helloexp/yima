<?php

/**
 * 支付模块
 *
 * @author tr
 */
class PayMentAction extends BaseAction {

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
    //非标和包
    public $cmPayId = 0;

    public function _initialize() {
        C('LOG_PATH', LOG_PATH . 'Log_alipay_'); // 日志路径+文件名前缀,);
        C('CUSTOM_LOG_PATH', LOG_PATH . 'Log_alipay_'); // 日志路径+文件名前缀,);
        $this->callBackUrl = C('CURRENT_HOST') . 'alipay_return.php';
        $this->notifyUrl = C('CURRENT_HOST') . 'alipay_notify.php';
        
        $this->pay_fee_rate = D('TsystemParam')->getValue('ZFB_FEE_RATE');
        $this->node_short_name = M('tnode_info')->where(
            "node_id='" . $this->node_id . "'")->getField('node_short_name');
        if ($this->pay_fee_rate > 1) {
            $this->showMsg("费率值有误，不允许大于1。" . $this->pay_fee_rate, 0, null, '', 
                '', $this->node_id);
        }
    }

    public function OrderPay($orderId = null) {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->showMsg('您还没有登录', 0, null, '', '', $this->node_id);
        }
        $this->groupPhone = session('groupPhone');
        $orderId = empty($orderId) ? I('order_id', null, 
            'mysql_real_escape_string') : $orderId;
        if (empty($orderId)) {
            $this->showMsg('参数错误', 0, null, '', '', $this->node_id);
        }
        // 获取订单信息
        $this->orderData = D('StoreOrder')->getOrderInfo($orderId);
        if (false === $this->orderData) {
            $msg = D('StoreOrder')->getError();
            $this->error($msg);
        }
        $this->alipay();
    }

    /**
     * 支付宝支付
     */
    public function alipay() {
        import('@.Vendor.Alipay.alipay_service', '', '.php');
        // dump($this->orderData);exit;
        // 构造请求函数
        
        $alipay = new alipay_service();
        $pms1 = array(
            "req_data" => '<direct_trade_create_req><subject>' .
                 $this->orderData['group_goods_name'] .
                 '</subject><out_trade_no>' . $this->orderData['order_id'] .
                 '</out_trade_no><total_fee>' . $this->orderData['order_amt'] .
                 "</total_fee><seller_account_name>" .
                 C('ALIPAY.Alipay_Seller_Email') .
                 "</seller_account_name><notify_url>" . $this->notifyUrl .
                 "</notify_url><out_user>" . $this->groupPhone .
                 "</out_user><call_back_url>" . $this->callBackUrl .
                 "</call_back_url><pay_expire>" . C('ALIPAY.Alipay_Pay_Expire') .
                 "</pay_expire></direct_trade_create_req>", 
                "service" => C('ALIPAY.Alipay_Service1'), 
                "sec_id" => C('ALIPAY.Alipay_Security'), 
                "partner" => C('ALIPAY.Alipay_Partner'), 
                "req_id" => date("Ymdhms"), 
                "format" => C('ALIPAY.Alipay_Format'), 
                "v" => C('ALIPAY.Alipay_version'));
        // dump($pms1);exit;
        // 调用alipay_wap_trade_create_direct接口，并返回token返回参数
        $token = $alipay->alipay_wap_trade_create_direct($pms1, 
            C('ALIPAY.Alipay_Key'), C('ALIPAY.Alipay_Security'));
        
        // 测试输出token（调试用）
        // echo $token;
        
        /*
         * ******************alipay_Wap_Auth_AuthAndExecute*******************
         */
        // 构造要请求的参数数组，无需改动
        $pms2 = array(
            "req_data" => "<auth_and_execute_req><request_token>" . $token .
                 "</request_token></auth_and_execute_req>", 
                "service" => C('ALIPAY.Alipay_Service2'), 
                "sec_id" => C('ALIPAY.Alipay_Security'), 
                "partner" => C('ALIPAY.Alipay_Partner'), 
                "call_back_url" => $this->callBackUrl, 
                "notify_url" => $this->notifyUrl, 
                "format" => C('ALIPAY.Alipay_Format'), 
                "v" => C('ALIPAY.Alipay_version'));
        // 调用alipay_Wap_Auth_AuthAndExecute接口方法，并重定向页面
        
        $alipay->alipay_Wap_Auth_AuthAndExecute($pms2, C('ALIPAY.Alipay_Key'));
    }

    /**
     * 处理支付宝同步回传数据 验证该回传数据是否是支付宝 取的支付宝回传的支付状态 如果支付成功则返回订单信息进行生成凭证的后续工作
     * 如果支付失败则返回FALSE
     */
    public function verifyReturn() {
        log_write(ACTION_NAME . '_________' . print_r($_POST, true));
        log_write(ACTION_NAME . '_________' . print_r($_GET, true));
        import('@.Vendor.Alipay.alipay_notify', '', '.php');
        // 构造通知函数信息
        $alipay = new alipay_notify(C('ALIPAY.Alipay_Partner'), 
            C('ALIPAY.Alipay_Key'), C('ALIPAY.Alipay_Security'), 
            C('ALIPAY.Alipay_input_charset'));
        // 计算得出通知验证结果
        $verify_result = $alipay->return_verify();
        
        if (! $verify_result) {
            // 验证失败返回
            log_write("验证失败");
            $this->showMsg('信息验证失败', 0, null, '', '', '');
        }
        // 外部交易号
        
        $data['order_seq'] = $_GET['out_trade_no'];
        // 交易号
        
        $data['transaction_id'] = $_GET['trade_no'];
        $status = strtolower( trim( $_GET['result'] ) );
        $result = false;
        if("success" == $status)
        {
            $result = true;
        }
        //订单处理
        D('StoreOrder')->getVerifyOrderInfo($data['order_seq'], 1, $result);
        exit;
    }

    /**
     * 处理支付宝异步回传数据 验证该回传数据是否是支付宝 取的支付宝回传的支付状态 如果支付成功则返回订单信息进行生成凭证的后续工作
     * 如果支付失败则返回FALSE
     */
    public function verifyNotify() {
        log_write(ACTION_NAME . '_________' . print_r($_POST, true));
        log_write(ACTION_NAME . '_________' . print_r($_GET, true));
        import('@.Vendor.Alipay.alipay_notify', '', '.php');
        // 构造通知函数信息
        $alipay = new alipay_notify(C('ALIPAY.Alipay_Partner'), 
            C('ALIPAY.Alipay_Key'), C('ALIPAY.Alipay_Security'), 
            C('ALIPAY.Alipay_input_charset'));
        // 计算得出通知验证结果
//        $verify_result = $alipay->notify_verify();
//        
//        if (! $verify_result) {
//            // 验证失败返回
//            log_write("验证失败");
//            echo 'fail';
//            exit();
//        }
        $notify_data = $_POST['notify_data'];
        
        // $notify_data =
        // '<notify><trade_status>TRADE_SUCCESS</trade_status><out_trade_no>1406249039314062</out_trade_no><trade_no>2014062471106048</trade_no></notify>';
        
        import('@.ORG.Util.Xml');
        $xmlparser = new Xml();
        $resp = $xmlparser->parse($notify_data);
        
        if (! isset($resp['notify'])) {
            log_write('FAIL272');
            log_write($notify_data);
            echo "fail";
            exit();
        }
        $status = $resp['notify']['trade_status'];
        if ($status == 'TRADE_SUCCESS' || $status == 'TRADE_FINISHED') {
            // 交易成功结束
            // 判断该笔订单是否在商户网站中已经做过处理
            // 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            // 如果有做过处理，不执行商户的业务程序
            $data = array();
            $data['order_seq'] = $resp['notify']['out_trade_no']; // 外部交易号
            
            $data['transaction_id'] = $resp['notify']['trade_no']; // 交易号

            $result = D('StoreOrder')->verifyOrderInfo($data['order_seq'], 1, $data['transaction_id']);
            if (true == $result) {
                echo "success";
                // tag('view_end');
                log_write(
                    "[success]支付宝支付，订单号 ：" . $data['order_seq'] . " 支付成功!");
            } else {
                echo "fail";
                log_write("[error]" . D('StoreOrder')->getError());
            }
            exit();
        }
        log_write("支付宝支付异步返回接口交易失败");
        echo "fail";
        exit();
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
        if (! $order_id) {
            $rebate = 0;
        } else {
            $orderInfo = M('ttg_order_info')->where(
                array(
                    'order_id' => $order_id))->find();
            $markInfo = M('tmarketing_info')->where(
                array(
                    'id' => $orderInfo['batch_no']))->find();
        }
        
        $nodeShortName = $this->node_short_name;
        if (! $nodeShortName)
            $nodeShortName = $markInfo['name'];
        if (! $nodeShortName)
            $nodeShortName = '错误提示';
        //非标和包
        if(C('CMPAY.nodeId') == $node_id){
            $this->cmPayId =  $node_id;
        }
        $this->assign('cmPayId', $this->cmPayId );
        
        $this->assign('id', $id);
        $this->assign('status', $status);
        $this->assign('info', $info);
        $this->assign('order_type', $order_type);
        $this->assign('sourceInfo', $sourceInfo);
        $this->assign('node_id', $node_id);
        $this->assign('node_short_name', $nodeShortName);
        $this->assign('bookOrder', $orderInfo['other_type']);
        $this->display('msg');
        exit();
    }
    
    // 输出公用信息页面
    public function showMsgInfo($info, $status, $id, $order_id, $node_id = null, 
        $shortName = null, $type = null, $sourceInfo = '') {
        //初始化变量
        $batchInfo = '';
        if (! $order_id) {
            $rebate = 0;
        } else {
            $orderInfo = M('ttg_order_info')->where(['order_id' => $order_id])->find();
            $markInfo = M('tmarketing_info')->where(['id' => $orderInfo['batch_no']])->find();
            //获取推荐商品信息
            $where = ['i.node_id' => $orderInfo['node_id'], 'i.status'=>'0', 'i.end_time'=>['gt', date('YmdHis')], 'ex.label_id'=>['neq', '']];
            
            $batchInfo = M('tbatch_info as i')
                ->join('tecshop_goods_ex as ex on i.id = ex.b_id')
                ->join('tgoods_info as g on i.goods_id =  g.goods_id')    
                ->where($where)
                ->field('i.*, ex.label_id, g.is_sku')
                ->order('i.end_time desc')   
                ->group('i.id')
                ->limit(2)
                ->select();
            //处理规格商品价格
            foreach ($batchInfo as &$val){
                if ("1" === $val['is_sku']) {
                    $skuObj = D('Sku', 'Service');
                    $skuObj->nodeId = $orderInfo['node_id'];
                    $val = $skuObj->makeGoodsListInfo($val, $val['m_id'], '');
                    if(false == $val){
                        unset($list[$key]);
                    }
                }
            }
        }
        if (! $shortName)
            $shortName = $markInfo['name'];
        if (! $shortName)
            $shortName = '错误提示';
        //非标和包
        if(C('CMPAY.nodeId') == $node_id){
            $this->cmPayId =  $node_id;
        }
        $this->assign('cmPayId', $this->cmPayId );
        
        $this->assign('bookOrder', $type);
        $this->assign('id', $id);
        $this->assign('node_short_name', $shortName);
        $this->assign('batchInfo', $batchInfo);
        $this->assign('sourceInfo', $sourceInfo);
        $this->assign('status', $status);
        $this->assign('info', $info);
        $this->assign('node_id', $node_id);
        $this->assign('bookOrder', $orderInfo['other_type']);
        $this->display('./Home/Tpl/Label/PayMent_showmsginfo.html');
        exit();
    }

    protected function sendNotice($nodeId, $orderId) {
        // 获取接收手机号
        $phoneNo = M('tnode_info')->where(
            array(
                'node_id' => $nodeId))->getField('receive_phone');
        if (! $phoneNo) {
            log_write("通知商户订单短信发送失败,原因:手机号为空");
            return false;
        }
        // 通知商户订单
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
        $sms_text = '有订单付款成功，订单号：' . $orderId;
        // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $phoneNo),  // 手机号
                'SendClass' => 'SMS', 
                'MessageText' => $sms_text,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('ALIPAY_NOTICE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        // dump($resp_array);exit;
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            log_write("通知商户订单短信发送失败,原因:" . $ret_msg['StatusText']);
        }
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
}
