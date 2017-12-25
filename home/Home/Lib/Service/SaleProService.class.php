<?php

class SaleProService {

    protected $tableName = 'tbonus_rule_main';

    public $orderData = array();

    public $groupPhone = '';

    public $error = '';
    
    // 构造函数
    public function __construct() {
    }

    /**
     * Description of SkuService 红包总规则添加
     *
     * @param int $nodeId 商户标识 type $type 红包规则类型 0-关闭红包使用 1-不限红包使用 2-限制红包使用
     * @return bloor $res
     * @author john_zeng
     */
    public function addNodeRule($nodeId, $type, $table = null) {
        if (null === $table)
            $table = $this->tableName;
        $where = array(
            'node_id' => $nodeId);
        
        $res = M($table)->where($where)->find();
        if (NULL === $res) {
            $data = array(
                'node_id' => $nodeId, 
                'rule_type' => $type);
            $result = M($table)->add($data);
            if (false === $result) {
                $this->getError("红包规则添加失败！");
            }
            return $result;
        } else {
            $data = array(
                'rule_type' => $type);
            $result = M($table)->where($where)->save($data);
            if (false === $result) {
                $this->getError("红包规则修改失败！");
            }
            return $result;
        }
    }

    /**
     * Description of SkuService 取得总规则信息
     *
     * @param int $nodeId 商户标识
     * @return int $ruleType 总规则信息 0-关闭红包使用 1-不限红包使用 2-限制红包使用
     * @author john_zeng
     */
    public function getNodeRule($nodeId, $table = null) {
        if (null === $table)
            $table = $this->tableName;
        $ruleInfo = M($table)->where(
            array(
                'node_id' => $nodeId))
            ->field('rule_type')
            ->find();
        $ruleType = isset($ruleInfo['rule_type']) ? $ruleInfo['rule_type'] : 0;
        return $ruleType;
    }

    /**
     * Description of SkuService 红包订单支付
     *
     * @param string $orderId 订单号码
     * @return int $ruleType
     * @author john_zeng
     */
    public function OrderPay($orderId = null, $pay_channel = 1) {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->getError('您还没有登录');
        }
        $this->groupPhone = session('groupPhone');
        $orderId = empty($orderId) ? I('order_id', null, 
            'mysql_real_escape_string') : $orderId;
        if (empty($orderId))
            $this->getError('参数错误');
        $orderInfo = M()->table('ttg_order_info o')
            ->field('o.*,g.group_goods_name,g.goods_num,g.sell_num')
            ->join("tmarketing_info g ON o.group_batch_no=g.member_level")
            ->where(
            "o.order_id='{$orderId}' AND o.order_phone={$this->groupPhone}")
            ->find();
        // 判断活动是否已停用
        if (! $orderInfo || $orderInfo['pay_status'] != 1)
            $this->getError('订单信息有误');
            
            // 判断活动是否已停用
        if ($orderInfo['order_type'] == '0') {
            $marketInfo = M('tmarketing_info')->where(
                array(
                    'id' => $orderInfo['batch_no']))->find();
            if (! $marketInfo)
                $this->showMsg('商品信息有误', 0, $orderInfo['batch_channel_id'], 
                    $orderId, '', $orderInfo['node_id']);
            if ($marketInfo['status'] != '1' ||
                 $marketInfo['end_time'] <= date('YmdHis'))
                $this->showMsg('商品已过期或已停止出售', 0, $orderInfo['batch_channel_id'], 
                    $orderId, '', $orderInfo['node_id']);
        }
        if ($orderInfo['order_type'] == '2') {
            $group_goods_name = M('ttg_order_info_ex')->where(
                array(
                    "order_id" => $orderInfo['order_id']))->getField(
                "group_concat(b_name)");
            $orderInfo['group_goods_name'] = $group_goods_name;
            
            $count = M()->table('ttg_order_info_ex t')
                ->join('tbatch_info b ON b.id=t.b_id')
                ->where(
                "t.order_id='" . $orderInfo['order_id'] .
                     "' AND (b.status!=0 or b.end_time <='" . date('YmdHis') .
                     "')")
                ->count();
            if ($count > 0)
                $this->showMsg('商品已过期或已下架', 0, $orderInfo['batch_channel_id'], 
                    $orderId, '', $orderInfo['node_id']);
        }
        $this->orderData = $orderInfo;
        return $this->bounsPay($orderId, $pay_channel);
    }

    /**
     * Description of SkuService 红包支付
     *
     * @param string $orderId 订单号码
     * @return int $ruleType
     * @author john_zeng
     */
    public function bounsPay($orderId, $pay_channel) { 
        $result = D('StoreOrder')->verifyOrderInfo($orderId, $pay_channel, $orderId);
        if(false === $result){
            return "fail";
        }else{
            return 'success';
        } 
    }
    
    // 发码处理 新增sku信息
    public function sendCode($orderId, $other = '', $phoneNum = '') {
        // 取得订单信息
        $orderModel = M('ttg_order_info');
        $orderInfo = $orderModel->where("order_id={$orderId}")->find();
        if (! $orderInfo) {
            log_write("订单发码：未找到订单信息。订单号：{$orderInfo['order_id']}", 'FAIL', 
                'BOUNSPAY');
            return false;
        }
        // 发码
        // $transId = date('YmdHis').sprintf('%04s',mt_rand(0,1000));
        // 获取batchinfoid
        $batchInfoId = M('tbatch_info')->where(
            array(
                'node_id' => $orderInfo['node_id'], 
                'm_id' => $orderInfo['batch_no']))->getField('id');
        $batchNo = M('tbatch_info')->where(
            array(
                'node_id' => $orderInfo['node_id'], 
                'm_id' => $orderInfo['batch_no']))->getField('batch_no');
        // 获取channelId
        $channelId = M('tbatch_channel')->where(
            array(
                'id' => $orderInfo['batch_channel_id']))->getField('channel_id');
        // 取得bathNo
        // df非标======开始======
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
        if ('' == $phoneNum)
            $phoneNum = $orderInfo['receiver_phone'];
        $res = $req->wcSendNew($orderInfo['node_id'], '', $batchNo, $phoneNum, 
            '8', $transId, '', $batchInfoId, $channelId, $other);
        if ($res == true) {
            // $result =
            // $orderModel->where("order_id='".$orderInfo['order_id']."'")->save(array('send_seq'=>$transId));
            // 更新code_trace到订单信息表
            $result = M('ttg_order_info_ex')->where(
                array(
                    'order_id' => $orderId, 
                    'b_id' => $batchInfoId))->save(
                array(
                    'code_trace' => $transId));
            if ($result === false) {
                log_write(
                    "订单发码成功,更新订单关联表失败;order_id:{$orderInfo['order_id']},send_seq:{$transId}", 
                    'SUCCESS', 'BOUNSPAY');
            }
            $result = M('torder_trace')->add(
                array(
                    'order_id' => $orderId, 
                    'm_id' => $orderInfo['batch_no'], 
                    'b_id' => $batchInfoId, 
                    'code_trace' => $transId));
            if ($result === false) {
                log_write(
                    "订单发码成功,更新订单发码关联表失败;order_id:{$orderInfo['order_id']},send_seq:{$transId}", 
                    'SUCCESS', 'BOUNSPAY');
            }
            return true;
        } else {
            log_write("订单发码失败,原因:{$res}", 'FAIL', 'BOUNSPAY');
            return false;
        }
    }
    
    // 发码处理 增加sku信息
    public function sendCode2($orderId, $orderType, $nodeId, $issBatchNo, $phone, $bId, $mId, $channelId, $gift_phone = null, $other = '', $torderArray = array()) {
        // 发码
        // $transId = date('YmdHis').sprintf('%04s',mt_rand(0,1000));
        $transId = get_request_id();
        import("@.Vendor.SendCode");
        $req = new SendCode();
        log_write("开始发码;order_id:{$orderId},发码信息:". json_encode($other), 'INFO', 'BOUNSPAY');
        $res = $req->wcSendNew($nodeId, '', $issBatchNo, $phone, '8', $transId, '', $bId, $channelId, $other);
        log_write("结束发码;order_id:{$orderId},发码信息:". json_encode($other), 'INFO', 'BOUNSPAY');
        if ($res == true) {
            if (isset($other['print_text'])){
                log_write(
                    "订单发码成功order_id:{$orderId},SKU信息:{$other['print_text']}", 'SENDINFO', 'BOUNSPAY');
            }else{
                log_write("订单发码成功order_id:{$orderId}", 'SENDINFO', 'BOUNSPAY');
            }    
            $gift_type = 0;
            if ($gift_phone)
                $gift_type = 2;
                // 将code_trace存入ex表
            $result = M('ttg_order_info_ex')->where(
                array(
                    'order_id' => $orderId, 
                    'b_id' => $bId))->save(
                array(
                    'code_trace' => $transId));
            if ($result === false) {
                log_write("订单发码成功,更新订单关联表失败;order_id:{$orderInfo['order_id']},send_seq:{$transId}", 'SUCCESS', 'BOUNSPAY');
            }
            $torderArray['order_id'] = $orderId;
            $torderArray['m_id'] = $mId;
            $torderArray['b_id'] = $bId;
            $torderArray['code_trace'] = $transId;
            $torderArray['gift_phone'] = $gift_phone;
            $torderArray['gift_type'] = $gift_type;
            $torderArray['transt_time'] = date('YmdHis');
            $result = M('torder_trace')->add($torderArray);
            if ($result === false) {
                log_write("订单发码成功,更新send_seq失败;order_id:{$orderId},send_seq:{$transId}", 'SUCCESS', 'BOUNSPAY');
                return false;
            } else {
                log_write("订单发码成功;order_id:{$orderId},发码信息:". json_encode($other), 'SUCCESS', 'BOUNSPAY');
                return true;
            }
        } else {
            log_write("订单发码失败,原因:{$res}", 'FAIL', 'BOUNSPAY');
            return false;
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
        
        // 进统一消息提醒
        $where = array(
            'node_id' => $nodeId, 
            'message_type' => '4');
        add_msgstat($where, 1);
    }
    
    // 输出信息页面
    protected function showMsg($info, $status, $id, $order_id, 
        $order_type = null, $node_id = null) {
        if ($node_id)
            $node_short_name = get_node_info($this->node_id, 'node_short_name');
        $order_amt = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))->getField('order_amt');
        $this->assign('id', $id);
        $this->assign('order_amt', $order_amt);
        $this->assign('status', $status);
        $this->assign('info', $info);
        $this->assign('order_type', $order_type);
        $this->assign('node_id', $node_id);
        $this->assign('order_id', $order_id);
        $this->assign('node_short_name', $node_short_name);
        $this->display('msg');
        exit();
    }
    
    // 发送短信
    public function sendBless($phoneNo, $text) {
        // 获取接收手机号
        if (! $phoneNo) {
            log_write("sendBless通知客户收到礼物短信发送失败,原因:手机号为空", 'FAIL', 'BOUNSPAY');
            return false;
        }
        // 通知商户订单
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
        $sms_text = $text;
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
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            log_write("sendBless通知客户收到礼物短信发送失败,原因:" . $ret_msg['StatusText'], 
                'FAIL', 'BOUNSPAY');
        } else {
            log_write(
                "sendBless通知客户收到礼物短信信息:" . $sms_text . '；当前手机号码：' . $phoneNo, 
                'FAIL', 'BOUNSPAY');
        }
    }

    public function checkGiftInfo($orderId, $sendInfo, $type = 2) {
        $orderGIft = M('ttg_order_gift')->where(
            array(
                'order_id' => $orderId))->find();
        if ($orderGIft['gift_type'] == '2') { // 短信送礼
            $rece_phone = explode("|", $orderGIft['rece_phone_list']);
            foreach ($rece_phone as $v) {
                if ($v) {
                    // 发送短信通知
                    $text = $orderGIft['bless_name'] . "送来一份祝福:" .
                         $orderGIft['bless_msg'] . "，详情可见稍后收到的彩信。";
                    $this->sendBless($v, $text);
                    // 发送凭证
                    if (2 == $type) {
                        $this->sendCode2($orderId, '2', $sendInfo['nodeId'], 
                            $sendInfo['batchNo'], $v, $sendInfo['bId'], 
                            $sendInfo['mId'], $sendInfo['channelId'], $v, 
                            $sendInfo['textInfo']);
                    } else {
                        $this->sendCode($orderId, $sendInfo['textInfo'], $v);
                    }
                }
            }
        }
    }

    /**
     * Description of SkuService 返回错误
     *
     * @param
     *
     * @return error
     * @author john_zeng
     */
    public function getError($str) {
        return $this->error = $str;
    }
}
