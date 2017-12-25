<?php

/**
 * 支付模块
 *
 * @author tr
 */
class PayTestAction extends BaseAction {

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

    public function _initialize() {
        C('LOG_PATH', LOG_PATH . 'Log_paytest_'); // 日志路径+文件名前缀,);
        C('CUSTOM_LOG_PATH', LOG_PATH . 'Log_paytest_'); // 日志路径+文件名前缀,);
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
        if (empty($orderId))
            $this->showMsg('参数错误', 0, null, '', '', $this->node_id);
        // 获取订单信息
        $this->orderData = D('StoreOrder')->getOrderInfo($orderId);
        if (false === $this->orderData) {
            $msg = D('StoreOrder')->getError();
            $this->error($msg);
        }
        $this->alipay();
    }

    /**
     * 测试支付
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
        $order_id = I('order_id', null);
        if (! $order_id) {
            echo "订单号不得为空";
            exit();
        }
        // 交易成功结束
        // 判断该笔订单是否在商户网站中已经做过处理
        // 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
        // 如果有做过处理，不执行商户的业务程序
        $data = array();
        $data['order_seq'] = $order_id; // 外部交易号
        $data['transaction_id'] = date('YmdHis') .
             str_pad(rand(1, 999999), 6, "0", STR_PAD_LEFT); // 交易号
        $result = D('StoreOrder')->verifyOrderInfo($data['order_seq'], 1, 
                $data['transaction_id']);
        // Write to log
       if (true == $result) {
            echo "success";
            // tag('view_end');
            log_write(
                "[success]支付宝支付，订单号 ：" . $data['order_seq'] . " 支付成功!");
        } else {
            echo "fail";
            log_write("[error]" . D('StoreOrder')->getError());
        }
        log_write("[success]测试支付，订单号 ：" . $data['order_seq'] . " 支付成功!");
        exit();
        // tag('view_end');
    }

    /**
     * 分润给业务商
     *
     * @param int $order_id
     */
    protected function _sendMoneyToMerchant($orderId) {
        // 取得订单信息
        $orderModel = M('ttg_order_info');
        if (! is_array($orderId)) {
            $orderInfo = $orderModel->where("order_id={$orderId}")->find();
        } else {
            $orderInfo = $orderId;
            $orderId = $orderInfo['order_id'];
        }
        log_write("订单分润:" . $orderId);
        log_write(print_r($orderInfo, true));
        if (! $orderInfo) {
            log_write("订单分润：未找到订单信息。订单号：{$orderInfo['order_id']}");
            return false;
        }
        log_write('分润开始,订单信息：' . print_r($orderInfo, true));
        
        $out_bill_no = date("ymdHis") . sprintf('%03s', mt_rand(0, 999));
        $out_trade_no = $orderInfo['order_id'];
        // $trade_no = $orderInfo['pay_seq'];
        $trade_no = '';
        // 获取该商户的支付宝账户
        
        $nodeAccountInfo = M('tnode_account')->where(
            "node_id = {$orderInfo['node_id']} AND account_type=1 AND status=1")
            ->field('account_no,fee_rate')
            ->find();
        $seller_account = $nodeAccountInfo['account_no'];
        $pay_fee_rate = $nodeAccountInfo['fee_rate'];
        // 如果没有配置机构费率，则取系统默认费率
        
        if (! $pay_fee_rate)
            $pay_fee_rate = $this->pay_fee_rate;
        if ($orderInfo['profit_status'] != '2') {
            $price = $orderInfo['order_amt'] * (1 - $pay_fee_rate);
            if ($price <= 0) {
                $price = 0;
            } else {
                if ($price < 0.01) {
                    $price = 0.01;
                }
            }
            if ($price == 0) {
                log_write("分润失败：商家费率：" . $pay_fee_rate . ',分润金额：' . $price);
                return false;
            }
            $price = floor($price * 100) / 100;
            $comment = "旺财订单: " . $orderInfo['order_id'];
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
            log_write("调用分润接口:" . $url);
            $error = '';
            $result = httpPost($url, '', $error, 
                array(
                    'METHOD' => 'GET'));
            import('@.ORG.Util.Xml');
            $xmlparser = new Xml();
            $doc = $xmlparser->parse($result);
            
            // 获取成功标识is_success
            $alipay_is_success = $doc['alipay']['is_success'];
            // 失败
            if ($alipay_is_success != 'T') {
                // 获取错误代码 error
                $alipay_error = $doc['alipay']['error'];
                // 更改分润状态
                
                $result = $orderModel->where("order_id='{$orderId}'")->save(
                    array(
                        'profit_status' => '3', 
                        'receive_amount' => $price, 
                        'fee_amt' => $orderInfo['order_amt'] - $price, 
                        'fee_rate' => $pay_fee_rate));
                $this->error = '';
                if ($result === false) {
                    $this->error = '分润状态更新失败';
                    log_write(
                        "分润失败,订单号:{$orderId} 分润状态更新失败 " . " [SQL]" . M()->_sql() .
                             " [ERROR]" . M()->getDbError(), 'DB_ERROR');
                }
                // Write to log
                log_write(
                    "分润失败, 订单号:" . $orderId . " 金额：" . $price . "  备注：" .
                         $comment . '  erro:' . $alipay_error);
                return false;
            }
            
            // 更改分润状态
            
            $result = $orderModel->where("order_id='{$orderId}'")->save(
                array(
                    'profit_status' => '2', 
                    'receive_amount' => $price, 
                    'fee_amt' => $orderInfo['order_amt'] - $price, 
                    'fee_rate' => $pay_fee_rate));
            if ($result === false)
                log_write("分润成功,订单号:{$orderId} 分润状态更新失败。" . M()->getDbError(), 
                    'DB_ERROR');
                
                // 增加分润总额
            $result = M('tnode_account')->where(
                array(
                    'account_type' => '1', 
                    'node_id' => $orderInfo['node_id']))->setInc('rolytal_money', 
                $price);
            if ($result === false)
                log_write("分润成功,订单号:{$orderId} 分润金额汇总更新失败。" . M()->getDbError(), 
                    'DB_ERROR');
                
                // Write to log
            log_write(
                "分润成功, 订单号:" . $orderId . " 金额：" . $price . "  备注：" . $comment);
            return true;
        } else {
            log_write('订单已分润');
            return true;
        }
    }

    /*
     * 分钱接口
     */
    public function splitMoney() {
        // 分润时间
        $splitDate = date('YmdHis', 
            strtotime("-" . $this->split_min_day . " days"));
        $orderId = I('orderId');
        $where = array(
            'a.pay_status' => 2, 
            'a.profit_status' => array(
                'in', 
                '1,3'), 
            'a.pay_channel' => 1);
        // 超过一定天数
        // 'a.update_time'=>array('lt',$splitDate),
        
        $rolytal_date_col = "DATE_ADD(STR_TO_DATE(SUBSTR(a.`update_time`,'1',8),'%Y%m%d%'),INTERVAL b.rolytal_day DAY)";
        // 计算分时间的条件
        $where["_string"] = $rolytal_date_col . " < CURDATE()";
        if ($orderId) {
            $where['a.order_id'] = $orderId;
        }
        $orderList = (array) M()->table('ttg_order_info a')
            ->field("a.*," . $rolytal_date_col . " as rolytal_date")
            ->join(
            "left join tnode_account b ON a.`node_id` = b.`node_id` AND b.`account_type` = '1'")
            ->where($where)
            ->select();
        do {
            log_write(
                '分润开始:[count]' . count($orderList) . ' SQL:' . M()->_sql());
            if (! $orderList) {
                log_write("没有需要分润的订单");
                echo '没有需要分润的订单';
                break;
            }
            foreach ($orderList as $orderInfo) {
                $result = $this->_sendMoneyToMerchant($orderInfo);
                $resp = '订单号:[' . $orderInfo['order_id'] . ']' .
                     ($result ? '分润成功。' : '分润失败。') . $this->error;
                log_write($resp);
                echo $resp . "\r\n";
            }
        }
        while (0);
        // tag('view_end');
    }
    // 发码处理
    protected function sendCode($orderId) {
        // 取得订单信息
        $orderModel = M('ttg_order_info');
        $orderInfo = $orderModel->where("order_id={$orderId}")->find();
        if (! $orderInfo) {
            log_write("订单发码：未找到订单信息。订单号：{$orderInfo['order_id']}");
            return false;
        }
        // 获取batchinfoid
        $batchInfoId = M('tbatch_info')->where(
            array(
                'node_id' => $orderInfo['node_id'], 
                'm_id' => $orderInfo['batch_no']))->getField('id');
        // 发码
        // $transId = date('YmdHis').sprintf('%04s',mt_rand(0,1000));
        
        // df非标======开始======
        $other = array();
        if ($orderInfo['node_id'] == C('df.node_id')) {
            $phone = $orderInfo['receiver_phone'];
            $memberInfo = M('tfb_df_member')->where("mobile = '{$phone}'")->find();
            if (! $memberInfo) {
                $memberInfo = M('tfb_df_member_import')->where(
                    "mobile = '{$phone}' and status = '0'")->find();
                if ($memberInfo)
                    $other['df_openid'] = $phone;
            } else {
                $other['df_openid'] = $memberInfo['openid'];
            }
        }
        // df非标======结束======
        
        $transId = get_request_id();
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $res = $req->wc_send($orderInfo['node_id'], '', 
            $orderInfo['group_batch_no'], $orderInfo['receiver_phone'], '8', 
            $transId, '', $batchInfoId, null, $other);
        if ($res == true) {
            // $result =
            // $orderModel->where("order_id='".$orderInfo['order_id']."'")->save(array('send_seq'=>$transId));
            $result = M('torder_trace')->add(
                array(
                    'order_id' => $orderId, 
                    'm_id' => $orderInfo['batch_no'], 
                    'b_id' => $batchInfoId, 
                    'code_trace' => $transId));
            if ($result === false) {
                log_write(
                    "订单发码成功,更新订单发码关联表失败;order_id:{$orderInfo['order_id']},send_seq:{$transId}");
            }
        } else {
            log_write("订单发码失败,原因:{$res}");
        }
    }
    // 发码处理
    protected function sendCode2($orderId, $orderType, $nodeId, $issBatchNo, 
        $phone, $bId, $mId, $gift_phone = null) {
        // 发码
        // $transId = date('YmdHis').sprintf('%04s',mt_rand(0,1000));
        $transId = get_request_id();
        import("@.Vendor.SendCode");
        $req = new SendCode();
        
        // df非标======开始======
        $other = array();
        if ($nodeId == C('df.node_id')) {
            $memberInfo = M('tfb_df_member')->where("mobile = '{$phone}'")->find();
            if (! $memberInfo) {
                $memberInfo = M('tfb_df_member_import')->where(
                    "mobile = '{$phone}' and status = '0'")->find();
                if ($memberInfo)
                    $other['df_openid'] = $phone;
            } else {
                $other['df_openid'] = $memberInfo['openid'];
            }
        }
        // df非标======结束======
        
        $res = $req->wc_send($nodeId, '', $issBatchNo, $phone, '8', $transId, 
            '', $bId, null, $other);
        if ($res == true) {
            $result = M('torder_trace')->add(
                array(
                    'order_id' => $orderId, 
                    'm_id' => $mId, 
                    'b_id' => $bId, 
                    'code_trace' => $transId, 
                    'gift_phone' => $gift_phone));
            if ($result === false) {
                log_write(
                    "订单发码成功,更新send_seq失败;order_id:{$orderId},send_seq:{$transId}");
            }
        } else {
            log_write("订单发码失败,原因:{$res}");
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
        $order_type = null, $node_id = null) {
        if (! $order_id) {
            $rebate = 0;
        } else {
            $orderInfo = M('ttg_order_info')->where(
                array(
                    'order_id' => $order_id))->find();
            $markInfo = M('tmarketing_info')->where(
                array(
                    'id' => $orderInfo['batch_no']))->find();
            $rebate = $markInfo['return_commission_flag'];
            $shareTitle = $markInfo['group_goods_name'];
        }
        if ($rebate != 0) {
            // 获取背景图
            $imgUrl = M()->table("tbatch_info b")->join(
                'tgoods_info g ON g.goods_id=b.goods_id')
                ->where(
                array(
                    'b.m_id' => $orderInfo['batch_no']))
                ->getField('g.goods_image');
            $imgUrl = C('CURRENT_DOMAIN') . "Home/Upload/" . $imgUrl;
            // 获取返佣id
            $userId = M('tmember_info')->where(
                array(
                    'phone_no' => $orderInfo['order_phone'], 
                    'node_id' => $orderInfo['node_id']))->getField('id');
            // $sns_url =
            // U('Label/Label/index',array('id'=>$id,'from_user_id'=>$userId,'from_type'=>'9'),'','',true);
            $sns_url = C('CURRENT_DOMAIN') .
                 'index.php?g=Label&m=Label&a=index&id=' . $id . '&from_user_id=' .
                 $userId . '&from_type=9';
            // qq空间
            $sns_zone_url = C('CURRENT_DOMAIN') .
                 'index.php?g=Label&m=Label&a=index&id=' . $id . '&from_user_id=' .
                 $userId . '&from_type=1';
            // 人人
            $sns_renren_url = C('CURRENT_DOMAIN') .
                 'index.php?g=Label&m=Label&a=index&id=' . $id . '&from_user_id=' .
                 $userId . '&from_type=3';
            // 微信
            $sns_wx_url = C('CURRENT_DOMAIN') .
                 'index.php?g=Label&m=Label&a=index&id=' . $id . '&from_user_id=' .
                 $userId . '&from_type=4';
            $rebate_rule = M('treturn_commission_info')->where(
                array(
                    'marketing_info_id' => $orderInfo['batch_no'], 
                    'node_id' => $orderInfo['node_id']))->getField(
                'commission_rule');
            
            $user_info = array(
                'phone_no' => session('groupPhone'), 
                'batch_id' => $orderInfo['batch_no'], 
                'user_id' => $userId, 
                'label_id' => $id, 
                'node_id' => $node_id);
            session('cjUserInfo', $user_info);
        }
        
        $nodeShortName = $this->node_short_name;
        if (! $nodeShortName)
            $nodeShortName = $markInfo['name'];
        if (! $nodeShortName)
            $nodeShortName = '错误提示';
            
            // 通宝斋标志
        if (in_array($node_id, C('fb_tongbaozhai.node_id'), true))
            $tongbaozhai_flag = 1;
        else
            $tongbaozhai_flag = 0;
        
        $this->assign('id', $id);
        $this->assign('status', $status);
        $this->assign('info', $info);
        $this->assign('order_type', $order_type);
        $this->assign('node_id', $node_id);
        $this->assign('sns_url', $this->shortUrl($sns_url));
        $this->assign('sns_zone_url', $this->shortUrl($sns_zone_url));
        $this->assign('sns_renren_url', $this->shortUrl($sns_renren_url));
        $this->assign('sns_wx_url', $this->shortUrl($sns_wx_url));
        $this->assign('rebate', $rebate);
        $this->assign('rebate_rule', $rebate_rule);
        $this->assign('node_short_name', $nodeShortName);
        $this->assign('imgUrl', $imgUrl);
        $this->assign('shareTitle', $shareTitle);
        $this->assign('tongbaozhai_flag', $tongbaozhai_flag);
        $this->display('msg');
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

    protected function sendNotice2($nodeId, $orderId, $orderAmt) {
        // 插入商户订单通知表torder_notice
        $message = "您有支付成功订单，订单号：{$orderId}，金额￥{$orderAmt}元";
        
        $data = array(
            'message_text' => $message, 
            'node_id' => $nodeId, 
            'status' => '0', 
            'add_time' => date('YmdHis'));
        
        $result = M('torder_notice')->add($data);
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