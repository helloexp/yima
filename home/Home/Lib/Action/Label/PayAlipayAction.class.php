<?php

/**
 * 支付宝支付
 *
 * @author wtr
 */
class PayAlipayAction extends BaseAction {

    public $orderData = array();

    public $groupPhone = '';

    public $notifyUrl = '';
    // 异步通知地址
    public $callBackUrl = '';
    // 同步通知地址
    public function _initialize() {
        $this->callBackUrl = CURRENT_HOST .
             '/index.php/Label/PayMent/verifyReturn';
        $this->notifyUrl = CURRENT_HOST . '/index.php/Label/PayMent/verifyNotify';
    }

    protected function orderPay($orderId = null) {
        // 校验订单
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        $this->groupPhone = session('groupPhone');
        $orderId = empty($orderId) ? I('order_id', null, 
            'mysql_real_escape_string') : $orderId;
        if (empty($orderId))
            $this->error('参数错误');
        $orderInfo = M()->table('ttg_order_info o')
            ->field('o.*,g.group_goods_name,g.goods_num,g.sell_num')
            ->join("tmarketing_info g ON o.group_batch_no=g.member_level")
            ->where(
            "o.order_id='{$orderId}' AND o.order_phone={$this->groupPhone} AND o.order_type=0")
            ->find();
        if (! $orderInfo || $orderInfo['pay_status'] != 1)
            $this->error('订单信息有误');
        if ($orderInfo['goods_num'] - $orderInfo['sell_num'] <= 0)
            $this->error('该商品已经售完');
        $this->orderData = $orderInfo;
        
        $this->alipay();
    }

    /**
     * 支付宝支付
     */
    protected function alipay() {
        import('@.Vendor.Alipay.alipay_service', '', '.php');
        // 构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => "create_direct_pay_by_user", 
            "partner" => $partner, 
            "payment_type" => $payment_type, 
            "notify_url" => $notify_url, 
            "return_url" => $return_url, 
            "seller_email" => $seller_email, 
            "out_trade_no" => $out_trade_no, 
            "subject" => $subject, 
            "total_fee" => $total_fee, 
            "body" => $body, 
            "show_url" => $show_url, 
            "anti_phishing_key" => $anti_phishing_key, 
            "exter_invoke_ip" => $exter_invoke_ip, 
            "_input_charset" => trim(
                strtolower($alipay_config['input_charset'])));
        
        // 建立请求
        $alipaySubmit = new alipay_service($alipay_config);
        
        $html_text = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
        echo $html_text;
        exit();
        
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
        
        /**
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
        import('@.Vendor.Alipay.alipay_notify', '', '.php');
        // 构造通知函数信息
        $alipay = new alipay_notify(C('ALIPAY.Alipay_Partner'), 
            C('ALIPAY.Alipay_Key'), C('ALIPAY.Alipay_Security'), 
            C('ALIPAY.Alipay_input_charset'));
        // 计算得出通知验证结果
        $verify_result = $alipay->return_verify();
        
        if (! $verify_result) {
            // 验证失败返回
            log::write("验证失败");
            $this->msgJump('信息验证失败');
        }
        // 外部交易号
        $data['order_seq'] = $_GET['out_trade_no'];
        // 交易号
        $data['transaction_id'] = $_GET['trade_no'];
        // 取得订单信息
        $orderModel = M('ttg_order_info');
        $orderInfo = $orderModel->where(
            "order_id={$data['order_seq']} AND order_type=0")->find();
        if (! $orderInfo) {
            // 没有订单信息返回
            log::write("团购支付宝支付，未找到订单信息. 订单号：{$data['order_seq']}");
            $this->msgJump('未找到订单信息');
        }
        // 订单状态，是否成功
        $result = strtolower(trim($_GET['result']));
        if ($result == "success") {
            // 支付成功
            if ($orderInfo['status'] == 1) {
                $result = $orderModel->where(
                    "order_id={$orderInfo['order_id']} AND order_type=0")->save(
                    array(
                        'status' => '2')); // 更新订单状态已支付
                if ($result === false)
                    log::write("团购支付宝支付，订单状态更新失败. 订单号：{$data['order_seq']}");
                    // 和业务商进行分润
                $this->sendMoneyToMerchant($orderInfo['order_id']);
                // 发码
                $this->sendCode($orderInfo['order_id']);
                // 抽奖
                $cjInfo = $this->cj($orderInfo['order_id']);
                // 更新tmarketing_info sell_num
                $result = M('tmarketing_info')->where(
                    "batch_no={$orderInfo['batch_no']}")->setInc('sell_num', 
                    $orderInfo['buy_num']);
                if (! $result)
                    log::write(
                        "团购支付宝支付,活动商品购买数量更新失败. 订单号：{$data['order_seq']} ");
            }
            // Write to log
            log::write("团购支付宝支付，订单号 ：" . $data['order_seq'] . " 支付成功!");
            $this->msgJump('恭喜您，您的订单支付成功并生效，请在有效期内前往指定地点享受服务！' . $cjInfo, 1, 
                $orderInfo['batch_channel_id']);
        } else {
            // 支付失败
            log::write("团购支付宝支付，订单号 ：" . $data['order_seq'] . " 支付失败!");
            $this->msgJump('很抱歉，由于意外情况您的订单支付失败，请前往确认！', 1, 
                $orderInfo['node_id']);
            exit();
        }
    }

    /**
     * 处理支付宝异步回传数据 验证该回传数据是否是支付宝 取的支付宝回传的支付状态 如果支付成功则返回订单信息进行生成凭证的后续工作
     * 如果支付失败则返回FALSE
     */
    public function verifyNotify() {
        import('@.Vendor.Alipay.alipay_notify', '', '.php');
        // 构造通知函数信息
        $alipay = new alipay_notify(C('ALIPAY.Alipay_Partner'), 
            C('ALIPAY.Alipay_Key'), C('ALIPAY.Alipay_Security'), 
            C('ALIPAY.Alipay_input_charset'));
        // 计算得出通知验证结果
        $verify_result = $alipay->notify_verify();
        
        if (! $verify_result) {
            // 验证失败返回
            log::write("验证失败");
            echo 'fail';
            exit();
        }
        
        $notify_data = $_POST['notify_data'];
        
        import('@.ORG.Util.Xml');
        $xmlparser = new Xml();
        $resp = $xmlparser->parse($notify_data);
        
        if (! isset($resp['notify'])) {
            log::write('FAIL272');
            log::log($notify_data);
            echo "fail";
            exit();
        }
        
        $status = $resp['notify']['trade_status'];
        // if($status == 'TRADE_FINISHED')
        if ($status == 'TRADE_SUCCESS' || $status == 'TRADE_FINISHED') {
            // 交易成功结束
            // 判断该笔订单是否在商户网站中已经做过处理
            // 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            // 如果有做过处理，不执行商户的业务程序
            $data['order_seq'] = $resp['notify']['out_trade_no']; // 外部交易号
            $data['transaction_id'] = $resp['notify']['trade_no']; // 交易号
                                                                   
            // 取得订单信息
            $orderModel = M("ttg_order_info");
            $orderInfo = $orderModel->alias("o")->join(
                "ttg_order_info_ex t ON t.order_id = o.order_id")
                ->field("o.*, t.ecshop_sku_desc")
                ->where("o.order_id={$data['order_seq']} AND o.order_type=0")
                ->find();
            if (! $orderInfo) {
                // 没有订单信息返回
                log::write("团购支付宝支付，未找到订单信息. 订单号：{$data['order_seq']}");
                echo "fail";
                exit();
            }
            // 取得sku规格信息
            $skuTypeName = '';
            if (isset($orderInfo['ecshop_sku_desc'])) {
                $skuTypeName = $orderInfo['ecshop_sku_desc'];
            }
            if ($orderInfo['status'] == 1) {
                // 更新订单状态
                $result = $orderModel->alias("o")->where(
                    "order_id={$orderInfo['order_id']} AND order_type=0")->save(
                    array(
                        'status' => '2'));
                if ($result === false)
                    log::write("团购支付宝支付，订单状态更新失败. 订单号：{$data['order_seq']}");
                    
                    // 和业务商进行分润
                $rolytal = $this->sendMoneyToMerchant($orderInfo['order_id']);
                // 发码
                $this->sendCode($orderInfo['order_id'], $skuTypeName);
                // 抽奖
                $cjInfo = $this->cj($orderInfo['order_id']);
                // 更新tmarketing_info sell_num
                $result = M('tmarketing_info')->where(
                    "batch_no={$orderInfo['batch_no']}")->setInc('sell_num', 
                    $orderInfo['buy_num']);
                if (! $result)
                    log::write(
                        "团购支付宝支付,活动商品购买数量更新失败. 订单号：{$data['order_seq']} ");
            }
            // Write to log
            echo "success";
            log::write("团购支付宝支付，订单号 ：" . $data['order_seq'] . " 支付成功!");
            exit();
        }
        log::write("团购支付宝支付异步返回接口交易失败");
        echo "fail";
        exit();
    }

    /**
     * 分润给业务商
     *
     * @param int $order_id
     */
    public function sendMoneyToMerchant($orderId) {
        
        // 取得订单信息
        $orderModel = M('ttg_order_info');
        $orderInfo = $orderModel->where(
            "order_id='{$orderId}' AND order_type=0")->find();
        if (! $orderInfo) {
            log::write("订单分润：未找到订单信息。订单号：{$orderInfo['order_id']}");
            $this->msgJump('未找到订单信息');
        }
        
        $out_bill_no = date("YmdHis");
        $out_trade_no = $orderInfo['order_id'];
        $trade_no = $orderInfo['transaction_id'];
        // 获取该商户的支付宝账户
        $seller_account = M('tnode_account')->where(
            "node_id = {$orderInfo['node_id']} AND account_type=1 AND status=1")->getField(
            'account_no');
        
        if ($orderInfo['status'] == 2 && $orderInfo['profit_status'] != 2) {
            $price = $orderInfo['order_amt'] *
                 (1 - C('ALIPAY.Alipay_default_rate'));
            $comment = "日期：" . date("Y-m-d H:i:s") . "订单编号: " .
                 $orderInfo['order_id'];
            $royalty_parameters = $seller_account . "^" . $price . "^" . $comment;
            
            $parameter = array(
                "service" => "distribute_royalty", 
                "partner" => C('ALIPAY.Alipay_Partner'), 
                "_input_charset" => C('ALIPAY.Alipay_input_charset'), 
                // 请求参数
                "out_bill_no" => $out_bill_no, 
                "out_trade_no" => $out_trade_no, 
                "trade_no" => $trade_no, 
                "royalty_type" => "10", 
                "royalty_parameters" => $royalty_parameters);
            import('@.Vendor.Alipay.alipay_royalty', '', '.php');
            // 构造请求函数
            $alipay = new alipay_royalty($parameter, C('ALIPAY.Alipay_Key'), 
                C('ALIPAY.Alipay_Security'));
            
            $url = $alipay->create_url();
            // print_r($url);
            
            $doc = new DOMDocument();
            $doc->load($url);
            // var_dump($doc);
            
            // 获取成功标识is_success
            $itemIs_success = $doc->getElementsByTagName("is_success");
            $nodeIs_success = $itemIs_success->item(0)->nodeValue;
            if ($nodeIs_success == "T") {
                // 更改分润状态
                $result = $orderModel->where(
                    "order_id='{$orderId}' AND order_type=0")->save(
                    array(
                        'profit_status' => '2'));
                if ($result === false)
                    log::write("分润成功,订单号:{$orderId}分润状态更新失败");
                    // Write to log
                log::write("分润成功, 金额：" . $price . "  备注：" . $comment);
                return true;
            }
            
            // 获取错误代码 error
            $itemError_code = $doc->getElementsByTagName("error");
            $nodeError_code = $itemError_code->item(0)->nodeValue;
            // 更改分润状态
            $result = $orderModel->where(
                "order_id='{$orderId}' AND order_type=0")->save(
                array(
                    'profit_status' => '3'));
            if ($result === false)
                log::write("分润失败,订单号:{$orderId}分润状态更新失败");
                // Write to log
            log::write(
                "分润失败, 金额：" . $price . "  备注：" . $comment . '  erro:' .
                     $nodeError_code);
            return false;
        }
    }
    
    // 发码处理 新增sku信息
    public function sendCode($orderId, $skuInfo = '') {
        // 取得订单信息
        $orderModel = M('ttg_order_info');
        $orderInfo = $orderModel->where(
            "order_id='{$orderId}' AND order_type=0")->find();
        if (! $orderInfo) {
            log::write("订单发码：未找到订单信息。订单号：{$orderInfo['order_id']}");
            $this->msgJump('未找到订单信息');
        }
        // 发码
        $transId = date('YmdHis') . sprintf('%04s', mt_rand(0, 1000));
        import("@.Vendor.SendCode");
        $req = new SendCode();
        // START sku传值到凭证
        $tmpArray = '';
        if ('' != $skuInfo)
            $tmpArray = array(
                'print_text' => $skuInfo);
            // END sku传值到凭证
        $res = $req->wc_send($orderInfo['node_id'], '', $orderInfo['batch_no'], 
            $orderInfo['receiver_phone'], '8', $transId, null, null, null, 
            $tmpArray);
        if ($res === true) {
            $result = $orderModel->where("id={$orderInfo['id']}")->save(
                array(
                    'request_id' => $transId));
            if ($result === false) {
                log::write(
                    "团购订单发码成功,更新request_id失败;order_id:{$orderInfo['order_id']},request_id:{$transId}");
            }
        } else {
            log::write("团购订单发码失败,原因:{$res}");
        }
    }
    // 抽奖
    public function cj($orderId) {
        // 取得订单信息
        $orderModel = M('ttg_order_info');
        $orderInfo = M()->table('ttg_order_info o')
            ->field('o.batch_channel_id,o.receiver_phone,g.is_cj')
            ->join("tmarketing_info g ON o.group_batch_no=g.member_level")
            ->where("o.order_id='{$orderId}' AND o.order_type=0")
            ->find();
        if (! $orderInfo) {
            log::write("订单发码：未找到订单信息。订单号：{$orderInfo['order_id']}");
            $this->msgJump('未找到订单信息');
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
    
    // 信息跳转
    public function msgJump($info, $status = 0, $id = '') {
        $info = urlencode($info);
        $this->redirect('Label/PayMent/showMsg', 
            array(
                'info' => $info, 
                'status' => $status, 
                'id' => $id));
    }
    // 输出信息页面
    public function showMsg() {
        $info = I('info', null);
        $status = I('status', null);
        $id = I('id', null);
        $this->assign('id', $id);
        $this->assign('status', $status);
        $this->assign('info', $info);
        $this->display('msg');
        exit();
    }
}