<?php

/**
 *
 * @author lwb Time 20150825
 */
class OrderModel extends Model {

    protected $tableName = '__NONE__';
    /**
     * 向支撑发送余额扣款请求
     *
     * @param unknown $nodeId
     * @param unknown $orderId
     * @param unknown $mId
     * @param unknown $price
     * @param unknown $contractId
     * @param unknown $token
     * @return multitype:string
     */
    public function payOrder($nodeId, $orderId, $mId, $price, $contractId, 
        $token) {
        $bInfo = M('tbatch_info')->where(
            array(
                'm_id' => $mId))
            ->field('batch_no')
            ->find();
        $activityId = $bInfo['batch_no'];
        $remoteRequest = D('RemoteRequest', 'Service');
        $req['TransactionId'] = $orderId;
        $req['ContractId'] = $contractId;
        $req['ActivityId'] = $activityId;
        $req['Price'] = $price;
        $req['Remark'] = '备注';
        $result = $remoteRequest->requestYzServ(
            array(
                'ServFeeByTimeReq' => $req));
        $respStatus = $result['Status'] ? $result['Status'] : '';
        if (empty($respStatus)) {
            log_write('发送请求没有响应');
            throw_exception('发送请求没有响应');
        }
        if ($respStatus['StatusCode'] != '0000' &&
             $respStatus['StatusCode'] != '0793') {
            $msg = '错误编码:' . $respStatus['StatusCode'] . ',' .
             $respStatus['StatusText'];
        log_write($msg);
        throw_exception($msg);
    }
    if ($respStatus['StatusCode'] == '0793') {
        if (! isset($result['SID'])) {
            log_write('SID获取失败');
            throw_exception('SID获取失败');
        }
        return array(
            'StatusCode' => '0793', 
            'url' => C('DIRECT_DEDUCT_URL') . '&token=' . $token . '&SID=' .
                 $result['SID']);
    } elseif ($respStatus['StatusCode'] == '0000') {
        return array(
            'StatusCode' => '0000');
    } else {
        log_write('系统错误');
        throw_exception('系统错误');
    }
}

/**
 * 获取订单信息
 *
 * @param unknown $nodeId
 * @param unknown $orderNumber
 * @return Ambigous <mixed, boolean, NULL, multitype:, unknown, string>
 */
public function getOrderInfo($nodeId, $orderNumber) {
    $result = M('tactivity_order')->where(
        array(
            'node_id' => $nodeId, 
            'order_number' => $orderNumber))->find();
    return $result;
}

public function getOrderInfoByNumber($orderNumber) {
    $result = M('tactivity_order')->where(
        array(
            'order_number' => $orderNumber))->find();
    return $result;
}

public function getOrderInfoByNode($nodeId) {
    $result = M('tactivity_order')->where(
        array(
            'node_id' => $nodeId))->find();
    return $result;
}

public function getPaiedOrderInfoByNode($nodeId) {
    $result = M('tactivity_order')->where(
        array(
            'node_id' => $nodeId, 
            'pay_status' => 1, 
            'batch_type' => 59))-> // 双旦活动类型
find();
    return $result;
}

/**
 * 根据订单id查找订单信息
 *
 * @param unknown $nodeId
 * @param unknown $id
 * @return Ambigous <mixed, boolean, NULL, multitype:, unknown, string>
 */
public function getOrderInfoById($nodeId, $id) {
    $result = M('tactivity_order')->where(
        array(
            'node_id' => $nodeId, 
            'id' => $id))->find();
    return $result;
}

/**
 * 更改订单状态为已付款状态
 *
 * @param unknown $nodeId
 * @param unknown $orderNumber
 */
public function changeOrderPayStatus($orderNumber, $retDetail, $treaty = false) {
    $map = array(
        'order_number' => $orderNumber);
    $activityModel = M('tactivity_order');
    $orderInfo = $activityModel->where($map)->find();
    if (! $orderInfo) {
        log_write('订单不存在,order_number:' . $orderNumber);
        return array(
            'resp_id' => '1002', 
            'resp_text' => '订单不存在');
    }
    if ($orderInfo['pay_status'] === '1') {
        log_write('订单付款状态之前已经被更新,order_number:' . $orderNumber);
        return array(
            'resp_id' => '1003', 
            'resp_text' => '订单付款状态之前已经被更新');
    }
    M()->startTrans();
    // 订单状态改为已付款
    $result = $activityModel->where($map)->save(
        array(
            'pay_status' => '1', 
            'pay_way' => '',  // 本来记录余额付款还是现充现付的，现在不计
            'ret_detail' => json_encode($retDetail), 
            'pay_time' => date('Y-m-d H:i:s')));
    if (! $result) {
        M()->rollback();
        log_write('更新订单状态失败!order_number:' . $orderNumber);
        return array(
            'resp_id' => '1001', 
            'resp_text' => '更新订单状态失败!');
    }
    if (! $treaty) {
        // 活动状态改为已付款
        $result = M('tmarketing_info')->where(
            array(
                'id' => $orderInfo['m_id']))->save(
            array(
                'pay_status' => '1'));
        if (false === $result) {
            M()->rollback();
            log_write(
                '更新活动表订单状态失败!order_number:' . $orderNumber . ',m_id:' .
                     $orderInfo['m_id']);
            return array(
                'resp_id' => '1004', 
                'resp_text' => '更新活动付款状态失败!');
        }
    }
    M()->commit();
    log_write('订单付款状态更新为已付款成功!');
    return array(
        'resp_id' => '0000', 
        'resp_text' => '订单付款状态更新为已付款成功!');
}

/**
 * 获取活动订单的卡券详情
 *
 * @param unknown $nodeId
 * @param unknown $couponType
 * @param unknown $orderId
 * @return Ambigous <mixed, string, boolean, NULL, unknown>
 */
public function getOrderCouponDetail($nodeId, $couponType, $orderId) {
    $map = array(
        'order_id' => $orderId, 
        'type' => $couponType);
    $couponDetailArr = M('tactivity_order_coupon_detail')->where($map)->select();
    $wxCardModel = M('twx_card_type');
    $goodsModel = M('tgoods_info');
    if ($couponType == CommonConst::COUPON_TYPE_WX_CARD) {
        foreach ($couponDetailArr as $k => $value) {
            $info = $wxCardModel->where(
                array(
                    'card_id' => $value['goods_id']))
                ->field('title')
                ->find();
            $couponDetailArr[$k]['name'] = $info['title'];
        }
    } else {
        foreach ($couponDetailArr as $k => $value) {
            $info = $goodsModel->where(
                array(
                    'goods_id' => $value['goods_id']))
                ->field('goods_name')
                ->find();
            $couponDetailArr[$k]['name'] = $info['goods_name'];
        }
    }
    
    return $couponDetailArr;
}

public function getCouponTypeNum($nodeId, $couponType, $orderId) {
    $map = array(
        'node_id' => $nodeId, 
        'id' => $orderId);
    $result = M('tactivity_order')->where($map)
        ->field('detail')
        ->find();
    if (! $result) {
        throw_exception('订单不存在');
    }
    $detail = json_decode($result['detail'], true);
    $couponDetail = $detail['couponDetail'];
    return $couponDetail[$couponType]['num'];
}

/**
 * 根据活动号查找orderInfo
 *
 * @param unknown $nodeId
 * @param unknown $mId
 * @return Ambigous <mixed, boolean, NULL, multitype:, unknown, string>
 */
public function getOrderInfoByMid($nodeId, $mId) {
    $result = M('tactivity_order')->where(
        array(
            'node_id' => $nodeId, 
            'm_id' => $mId))->find();
    return $result;
}

public function getAccountInfo($nodeId) {
    $nodeInfo = get_node_info($nodeId);
    // 查询商户账户余额和流量
    $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                               // 商户余额流浪报文参数
    $req_array = array(
        'QueryShopInfoReq' => array(
            'SystemID' => C('YZ_SYSTEM_ID'), 
            'NodeID' => $nodeId, 
            'TransactionID' => $TransactionID, 
            'ContractID' => $nodeInfo['contract_no']));
    $RemoteRequest = D('RemoteRequest', 'Service');
    $AccountInfo = $RemoteRequest->requestYzServ($req_array);
    
    if (! $AccountInfo || ($AccountInfo['Status']['StatusCode'] != '0000' &&
         $AccountInfo['Status']['StatusCode'] != '0001')) {
        $AccountInfo = array();
    }
    return $AccountInfo;
}

public function getWbInfo($nodeId) {
    $nodeInfo = get_node_info($nodeId);
    // 创建接口对象
    $RemoteRequest = D('RemoteRequest', 'Service');
    $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                               // 商户服务信息报文参数
    $req_array = array(
        'QueryWbListReq' => array(
            'SystemID' => C('YZ_SYSTEM_ID'), 
            'TransactionID' => $TransactionID, 
            'ClientID' => $nodeInfo['client_id']));
    $nodeWbInfo = $RemoteRequest->requestYzServ($req_array);
    // dump($nodeWbInfo);
    $nodeWbList = array();
    $nodeWbList['wbOver'] = '0';
    if (! empty($nodeWbInfo['WbList'])) {
        if (isset($nodeWbInfo['WbList'][0])) {
            $nodeWbList['list'] = $nodeWbInfo['WbList'];
        } else {
            $nodeWbList['list'][] = $nodeWbInfo['WbList'];
        }
    }
    if (! empty($nodeWbList['list'])) {
        foreach ($nodeWbList['list'] as $k => $v) {
            if (date('YmdHis') > $v['EndTime']) {
                $nodeWbList['list'][$k]['WbListId'] .= '(已过期)';
            } elseif (date('YmdHis') < $v['BeingTime']) {
                $nodeWbList['list'][$k]['WbListId'] .= '(未开始)';
            } elseif ($v['Status'] == '-1') {
                $nodeWbList['list'][$k]['WbListId'] .= '(已失效)';
            } else {
                $nodeWbList['wbOver'] += $v['WbCurBalance'];
            }
        }
    }
    return $nodeWbList;
}

/**
 * [getCashierUrl 获取收银台的Url]
 *
 * @param [type] $nodeId [商户id]
 * @param [type] $orderId [订单id]
 * @return [type] [收银台的URL]
 */
public function getCashierUrl($nodeId, $orderId) {
    $orderInfo = $this->getOrderInfoById($nodeId, $orderId);
    $data = array();
    switch ($orderInfo['order_type']) {
        case CommonConst::ORDER_TYPE_WHEEL_NORMAL: // 常量名字起的不好,其实表示正常的付费营销活动
            {
                $sendCodeFee = session('sendCodeFee' . $orderInfo['m_id']);//是否计算发码费
                session('sendCodeFee' . $orderInfo['m_id'], null);
                if (!isset($sendCodeFee)) {
                    $sendCodeFee = 1;
                }
                // 欧洲杯活动购买的种类，购买的2980多次还是199单次（1：2980种类，2：199种类）
                $matchType = session('matchType' . $orderInfo['m_id']);
                if (!isset($matchType)) {
                    session('matchType' . $orderInfo['m_id'], $matchType);
                }
                //优惠券辅助码
                $assistCode = session('assistCode' . $orderInfo['m_id']);
                session('assistCode' . $orderInfo['m_id'], null);
                $bindChannelModel = D('BindChannel');
                // 重新生成订单(防止客户修改)
                $varifyRe = $bindChannelModel->createOrder($nodeId, $orderInfo['m_id'], 
                    $orderInfo['batch_type'], $sendCodeFee, $assistCode);
                $this->setPayStatus($nodeId, '3', $orderId);
                $orderInfo = $bindChannelModel->getOrderInfo($orderId, $nodeId);
                
                if ($assistCode) {
                    if ($varifyRe && isset($varifyRe['res_code']) && $varifyRe['res_code'] != '0000') {
                        echo $varifyRe['msg'];exit;
                    }
                    $orderInfo['serviceAmount'] = '0.00';
                    $orderInfo['amount'] = $orderInfo['totalSendAmount'];
                    //如果核销优惠券成功就认为该活动可以用了
                    M('tactivity_order')->where(['id' => $orderId])->save(['order_type' => '2']);
                    M('tmarketing_info')->where(['id' => $orderInfo['m_id']])->save(['pay_status' => '1']);
                }
                
                // 暂时不计流水
                $orderDetail = $orderInfo['detail'];
                $contractNo = get_node_info($orderInfo['node_id'], 
                    'contract_no');
                $dataInData = array(
                            array(
                                "S" => "TwoEgg",  // 营帐那边记录双旦的操作的m,等大转盘上线需要根据订单表记录的活动类型判断怎么填这个参数
                                "A" => "index", 
                                "price" => $orderInfo['serviceAmount'],  // 金额
                                                                 // "price" =>
                                                                 // '0.01',//金额
                                'nodeID' => $orderInfo['node_id'],  // 机构号
                                'ActivityID' => $orderInfo['m_id'],  // 活动编号
                                'remark' => $orderDetail['orderResponse'],  // 暂时先写一个是否有发码费,有的话写1,没有的话写0
                                'transactionId' => $orderInfo['order_number'], 
                                'contract_id' => $contractNo
                            ),
                );
                if ($sendCodeFee == 1) {
                    $prePrice = array(
                        "S" => "Recharge", //必填
                        "A" => "yszkRecharge",//必填
                        "price" => $orderInfo['totalSendAmount'], //充值金额
                        "isAccountTrace" => 1,//必填
                        "contractID" => $contractNo, //计算号
                        "priceLimit" => array(
                            "a002" => $orderInfo['totalSendAmount'],
                            "tp" => $orderInfo['totalSendAmount']
                        ) //控制每个账户可以使用账户的金额数, a002:预付费 a003:旺币 a004预付费账户 ,tp:第三方支付通道
                        //营账那边的逻辑是这样的：活动费用可以使用所有类型的账户来付费，发码费只能用余额和第三方支付来付费（所以会有循环扣的现象）
                    );
                    $dataInData[] = $prePrice;
                }
                
                $data = array(
                    'order_id' => $orderInfo['order_number'],  // 旺财订单号
                    'system_id' => C('YZ_SYSTEM_ID'),  // 系统id
                    'client_id' => get_node_info($nodeId, 'client_id'),  // 用户client_id
                    'desc' => $orderDetail['mTypeName'],  // 业务业务描述
                    'name' => $orderDetail['mTypeName'],  // 业务名称
                    'price' => $orderInfo['amount'],  // 订单总金额
                                                     // 'price' => '0.01', //
                                                     // 订单总金额
                    'bank_transfer' => 'OFF',  // 是否支持转账
                    'notify_url' => U(
                        'CronJob/CashierRet/activityPayStatusNotifyUrl', '', '', 
                        '', true),  // 异步通知地址
                    'return_url' => U(
                        'CronJob/CashierRet/activityPayStatusReturnUrl', '', '', 
                        '', true),  // 同步通知同步
                    'promotion_price' => 0,  // 促销价格
                    'data' => json_encode($dataInData), 
                    'tp_price' => 'on'
                ); // 结算号
                 // 调用对应操作数据
                       // 'ye_price' => 'NO',
                       // 余额支付最大使用金额,'off'不使用'on'开启不传默认为'no'. 0=='off'
                       // 'wb_price' => 'NO',
                       // 旺币支付最大使用金额,'off'不使用'on'开启不传默认为'no'. 0=='off'
                       // 'yszk_price' => 'NO',
                       // 预收款账户支付最大使用金额,'off'不使用'on'开启不传默认为'no'. 0=='off'
                       // 'tp_price' => 'NO',第三行支付通道
            
            }
            break;
        case CommonConst::ORDER_TYPE_APPLY_POS:
            {
                $detail = json_decode($orderInfo['detail'], true);
                $forData = array(
                    "S" => "WcPos", 
                    "A" => "posOrder", 
                    "price" => $orderInfo['amount']);
                $posOrderReq = $detail['payInfo']['PosOrderReq'];
                // 将所有key的首字母变小写
                if (is_array($posOrderReq)) {
                    $posOrderReq = self::array_lcfirst($posOrderReq);
                }
                $forData = array_merge($forData, $posOrderReq);
                $data = array(
                    'desc' => '在线申请终端',  // 业务业务描述
                    'name' => '申请终端',  // 业务名称
                    'notify_url' => U('CronJob/CashierRet/applyPos', '', '', '', 
                        true),  // 异步通知地址
                    'return_url' => U('CronJob/CashierRet/applyPos', '', '', '', 
                        true),  // 同步通知同步
                    'system_id' => C('YZ_SYSTEM_ID'),  // 系统id
                    'order_id' => $orderInfo['order_number'],  // 旺财订单号
                    'client_id' => get_node_info($nodeId, 'client_id'),  // 用户client_id
                    'price' => $orderInfo['amount'],  // 订单总金额
                    'promotion_price' => 0,  // 促销价格
                    'data' => json_encode(
                        array(
                            $forData)),  // 调用对应操作数据
                    'bank_transfer' => 'off',  // 是否支持转账
                    'ye_price' => 'on', 
                    // 余额支付最大使用金额,'off'不使用'on'开启不传默认为'no'. 0=='off'
                    'wb_price' => $detail['payInfo']['maxWbUse'] ? $detail['payInfo']['maxWbUse'] : 'off', 
                    // 旺币支付最大使用金额,'off'不使用'on'开启不传默认为'no'. 0=='off'
                    'yszk_price' => 'off', 
                    'tp_price' => 'on'); // 第三行支付通道
            
            }
            break;
        case CommonConst::ORDER_TYPE_FREE_VALIDATE:
            
            break;
        case CommonConst::ORDER_TYPE_ONLINE_TREATY:
            {
                $orderDetail = json_decode($orderInfo['detail'], true);
                $nodeInfo = get_node_info($orderInfo['node_id']);
                $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                if ($orderDetail['hasBuy'] == '1') {
                    if ($orderDetail['version'] == '2') {
                        $ServCode = '3004';
                        $ServPrice = '4880';
                        $AgreePrice = $orderInfo['amount'];
                    } elseif ($orderDetail['version'] == '3') {
                        $ServCode = '3003';
                        $ServPrice = '6880';
                        $AgreePrice = $orderInfo['amount'];
                    }
                } else {
                    $TransactionID .= ',' . date("YmdHis") .
                         mt_rand(100000, 999999);
                    if ($orderDetail['version'] == '2') {
                        $ServCode = '3050,3004';
                        $ServPrice = '0,4880';
                        $AgreePrice = '0,' . $orderInfo['amount'];
                    } elseif ($orderDetail['version'] == '3') {
                        $ServCode = '3050,3003';
                        $ServPrice = '0,6880';
                        $AgreePrice = '0,' . $orderInfo['amount'];
                    }
                }
                $data = array(
                    'desc' => '在线签约-付费模块',  // 业务业务描述
                    'name' => '在线签约',  // 业务名称
                    'notify_url' => U('CronJob/CashierRet/openTreaty', '', '', 
                        '', true),  // 异步通知地址
                    'return_url' => U('CronJob/CashierRet/openTreaty', '', '', 
                        '', true),  // 同步通知同步
                    'system_id' => C('YZ_SYSTEM_ID'),  // 系统id
                    'order_id' => $orderInfo['order_number'],  // 旺财订单号
                    'client_id' => get_node_info($nodeId, 'client_id'),  // 用户client_id
                    'price' => $orderInfo['amount'],  // 订单总金额
                    'promotion_price' => 0,  // 促销价格
                    'data' => json_encode(
                        array(
                            array(
                                "S" => "WcRule", 
                                "A" => "wcPay", 
                                "price" => $orderInfo['amount'], 
                                'TransactionID' => $TransactionID, 
                                'SystemID' => C('YZ_SYSTEM_ID'), 
                                'NodeID' => $nodeInfo['node_id'], 
                                'ContractID' => $nodeInfo['contract_no'], 
                                'ServCode' => $ServCode, 
                                'ServPrice' => $ServPrice, 
                                'AgreePrice' => $AgreePrice, 
                                'BeginTime' => '', 
                                'EndTime' => '', 
                                'ChargingPeriod' => '12', 
                                'Effect' => 0, 
                                'Paytype' => '1', 
                                'ServFlag' => '2', 
                                'ServLvl' => '3'))),  // 调用对应操作数据
                    'bank_transfer' => 'OFF',  // 是否支持转账
                    'ye_price' => 'ON',
                    // 余额支付最大使用金额,'off'不使用'on'开启不传默认为'no'. 0=='off'
                    'wb_price' => 'ON',
                    // 旺币支付最大使用金额,'off'不使用'on'开启不传默认为'no'. 0=='off'
                    'yszk_price' => 'ON',
                    'tp_price' => 'on'); // 第三行支付通道
            
            }
            break;
        case CommonConst::ORDER_TYPE_DM:
                $data = array(
                    'desc' => '在线签约-多米收单',  // 业务业务描述
                    'name' => '多米收单',  // 业务名称
                    'notify_url' => U('CronJob/CashierRet/openDM', '', '', 
                        '', true),  // 异步通知地址
                    'return_url' => U('CronJob/CashierRet/openDM', '', '', 
                        '', true),  // 同步通知同步
                    'system_id' => C('YZ_SYSTEM_ID'),  // 系统id
                    'order_id' => $orderInfo['order_number'],  // 旺财订单号
                    'client_id' => get_node_info($nodeId, 'client_id'),  // 用户client_id
                    'price' => $orderInfo['amount'],  // 订单总金额
                    'promotion_price' => 0,  // 促销价格
                    'data' => json_encode(
                        array(
                            array(
                                "S" => "WcDuoMi", 
                                "A" => "serverOpen", 
                                "price" => $orderInfo['amount'], 
                                'nodeID' => $nodeId, 
                                'contractID' => get_node_info($nodeId, 'contract_no'), 
                                'rule_price' => '30'))),  // 调用对应操作数据
                    'bank_transfer' => 'OFF',  // 是否支持转账
                    'ye_price' => 'ON', 
                    'wb_price' => 'ON', 
                    'yszk_price' => 'OFF', 
                    'tp_price' => 'ON');
                if($orderInfo['amount'] == '0.00'){
                    $data['tp_price'] = 'OFF';
                }
            break;
        default:
            // code...
            break;
    }
    log_write('跳转到收银台时，传递的参数:' . print_r($data, true));
    $mackey = C('YZ_MAC_KEY') or die('[YZ_MAC_KEY]参数未设置');
    // 获取sign签名
    ksort($data); // 排序
    $code = http_build_query($data); // url编码并生成query字符串
    $sign = md5($mackey . $code . $mackey); // 生成签名
    log_write($mackey . $code . $mackey);
    $data['sign'] = $sign; // 添加sign
                           // 跳转到收银台
    $sHtml = "<form id='hideform' style='display:none;' name='hideform' action='" .
         C('YZ_CAHSIER_POST_URL') . "' method='post'>";
    foreach ($data as $key => $val) {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $sHtml .= "<input type='hidden' name='" . $key . "[" . $k . "]" .
                     "' value='" . $v . "'/>";
            }
        } else {
            $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val .
                 "'/>";
        }
    }
    // submit按钮控件请不要含有name属性
    $sHtml = $sHtml . "<input type='submit' value='Submit'></form>";
    $sHtml = $sHtml . "<script>document.forms['hideform'].submit();</script>";
    return $sHtml;
}

/**
 * [YzNoticeBackMoney 通知营帐解冻资金]
 * $flag = 1是关闭该订单的支付能力，并解锁资金
 * $flag = 0是仅仅解锁因支付失败而导致的资金锁定
 */
public function YzNoticeBackMoney($nodeId, $orderId,$flag = '1') {
    // 因为存在这笔订单并没有被冻结资金的问题，所以营帐如果判断没有冻结资金，也要返回true
    $orderInfo = $this->getOrderInfoById($nodeId, $orderId);
    $clientId = get_node_info($nodeId,'client_id');
    $data = array(
        'system_id' => C('YZ_SYSTEM_ID'), 
        'cancel'    => $flag, 
        'client_id' => $clientId, 
        'order_id'  => $orderInfo['order_number']);
    $mackey = C('YZ_MAC_KEY') or die('[YZ_MAC_KEY]参数未设置');
    // 获取sign签名
    ksort($data); // 排序
    $code = http_build_query($data); // url编码并生成query字符串
    $sign = md5($mackey . $code . $mackey); // 生成签名
    $query = array(
        'm' => 'Cashier', 
        'a' => 'unlockByOrder', 
        'system_id' => $data['system_id'], 
        'cancel'    => $flag, 
        'client_id' => $clientId, 
        'order_id'  => $data['order_id'], 
        'sign' => $sign);
    $url = C('YZ_CANCEL_ORDER_URL') . http_build_query($query);
    log_write('订单' . $data['order_id'] . '请求收银台的地址:' . $url);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $res = curl_exec($ch);
    curl_close($ch);
    log_write('订单' . $data['order_id'] . '请求收银台返回信息:' . $res);
    $aRet = json_decode($res, true);
    if ($aRet['code'] == '0000') {
        log_write('订单' . $data['order_id'] . '取消成功;收银台返回信息:' . $aRet['msg']);
        return true;
    } else {
        log_write('订单' . $data['order_id'] . '取消失败;收银台返回信息:' . $aRet['msg']);
        return false;
    }
}

/**
 * 设置订单支付状态
 *
 * @param unknown $nodeId
 * @param unknown $payStatus
 * @param unknown $orderId
 */
public function setPayStatus($nodeId, $payStatus, $orderId) {
    $re = M('tactivity_order')->where(
        array(
            'id' => $orderId, 
            'node_id' => $nodeId))->save(
        array(
            'pay_status' => $payStatus));
}

public function isInPay($nodeId, $mId) {
    // 是否有"支付中"状态的记录
    $re = M('tactivity_order')->where(
        array(
            'node_id' => $nodeId, 
            'm_id' => $mId, 
            'pay_status' => '3'))->find();
    return $re;
}

/**
 * [array_lcfirst 将数组中所有的key的首字母变小写]
 *
 * @return [type] [description]
 */
private function array_lcfirst($arr) {
    $newArr = array();
    foreach ($arr as $k => $v) {
        if (! is_array($v)) {
            $newArr[lcfirst($k)] = $v;
        } else {
            $newArr[lcfirst($k)] = self::array_lcfirst($v);
        }
    }
    return $newArr;
}
    /**
     * 修改欧洲杯活动的状态和订单状态（购买2980欧洲杯活动之后，之前未付款的要取消订单）
     * @param string $nodeId
     * @return mixed
     */
    public function changeEuroCupOrder($nodeId) {
        $needChangeRecord = M('tactivity_order')->where(
            array(
                'node_id' => $nodeId,
                'batch_type' => '61',//欧洲杯竞猜
                'order_type' => '1',
                'pay_status' => array('in', array('3','0'))//付款中和未付款
            ))
            ->getField('id', true);
        log_write(var_export($needChangeRecord, true));
        if (!$needChangeRecord) {
            return false;
        }
        M()->startTrans();
        // 是否有"支付中"状态的记录
        $re = M('tactivity_order')->where(
            array(
                'node_id' => $nodeId,
                'batch_type' => '61',//欧洲杯竞猜
                'order_type' => '1',
                'pay_status' => array('in', array('3','0'))//付款中和未付款
            ))
            ->save(['pay_status' => '2']);//改成已取消
        if (false === $re) {
            M()->rollback();
            return false;
        }
        //对应的活动付款状态也改掉
        $re = M('tmarketing_info')->where(['batch_type' => '61', 'node_id' => $nodeId])->save(['pay_status' => '1']);
        if (false === $re) {
            M()->rollback();
            return false;
        }
        M()->commit();
        return $needChangeRecord;
    }
    
    public function verifyDiscount($assistCode) {
        $mPosVerify = D('PosVerify', 'Service');
        // 检查是否有未冲正的过往订单
        $PosReversalModel = M('tpos_reversal');
        $verifyCodeConfig = D('BindChannel')->getVerifyBarcodeConfigByBatchType(CommonConst::BATCH_TYPE_EUROCUP);
        $posId = get_val($verifyCodeConfig, 'pos_id', '');
        $nodeId = get_val($verifyCodeConfig, 'node_id', '');
        $aReversal = $PosReversalModel->where(array('node_id' => $nodeId,'status' => '1'))->find();
        if (! empty($aReversal)) {
            $szRetInfo = $mPosVerify->doPosReversal($aReversal['pos_id'],
                $aReversal['res_seq'], $aReversal['assist_code']);
            if ($szRetInfo['business_trans']['result']['id'] != '0000') {
                $re = [
                    'res_code' => -1002,
                    'msg' => "验证终端出错，请联系客服处理！",//验证终端还有未冲正的过往记录
                ];
                log_write('verifyDiscount:' . var_export($re, true));
                return $re;
            } else {
                $PosReversalModel->where(
                    array(
                        'id' => $aReversal['id']))->delete();
            }
        }
        
        // 初始化流水号
        $mPosVerify->setopt();
        // 提前记录一次
        $aVerifyData = array(
            'pos_id' => $posId,
            'node_id' => $nodeId,
            'res_seq' => $mPosVerify->posSeq,
            'assist_code' => $assistCode,
            'add_time' => date('YmdHis'));
        $bRet = $PosReversalModel->add($aVerifyData);
        if (! $bRet) {
            $re = [
                'res_code' => -1001, 
                'msg' => "验证失败，请重试！",
            ];
            log_write('verifyDiscount:' . var_export($re, true));
            return $re;
        }
        // 验证辅助码
        $szRetInfo = $mPosVerify->doPosVerify($posId, $assistCode, true);
        // 超时，删除记录
        if (! $szRetInfo) {
            $PosReversalModel->where(array(
                'id' => $bRet))->delete();
            $re = [
                'res_code' => -1001, 
                'msg' => "验证失败，请重试！",
            ];
            log_write('verifyDiscount:' . var_export($re, true));
            return $re;
        }
        
        // 处理返回结果
        if ($szRetInfo['business_trans']['result']['id'] == '0000') {
            // 验证成功
            $PosReversalModel
            ->where(array('id' => $bRet))
            ->save(
                array(
                    'status' => '3',
                    'trans_time' => $szRetInfo['business_trans']['trans_time'],
                    'tx_amt' => $szRetInfo['business_trans']['addition_info']['tx_amt'],
                    'phone_no' => $szRetInfo['business_trans']['addition_info']['phone_no']
                    
                )
            );
            return [
                'res_code' => '0000',
                'msg' => '',
            ];
        } else {
            // 验证失败，删除记录
            $PosReversalModel->where(array(
                'id' => $bRet))->delete();
            $re = [
                'res_code' => -1003,
                'msg' => $szRetInfo['business_trans']['result']['comment'],
            ];
            log_write('verifyDiscount:' . var_export($re, true));
            return $re;
        }
    }
}