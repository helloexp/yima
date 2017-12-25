<?php

/**
 * 我的业务中心
 *
 * @author bao
 */
class ServicesCenterAction extends IndexAction {

    const NUMBER_PER_PAGE = 10;
    // 每页显示10条
    public function _initialize() {
        parent::_initialize();
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    public function accountDetails() {
        $monthArr = $this->agoMonth();
        $listData = array();
        foreach ($monthArr as $k => $v) {
            $listData[$k]['bill_month'] = dateformat($v . '25', 'Y-m');
            $listData[$k]['mon'] = $v;
        }
        // user_act_log('查看账单信息','',array('act_code'=>'3.5.1.2'));
        $this->assign('dataInfo', $listData);
        $this->display();
    }
    
    // 充值记录
    public function rechargeRecord() {
        // 充值记录
        $dataList = array();
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
        $onePageNum = 10; // 每页记录数
        isset($_GET['p']) ? $pageNum = $_GET['p'] : $pageNum = 1; // 当前页数
                                                                  // 充值记录报文参数
        $req_array = array(
            'QueryRechargeAmtReq' => array(
                'SystemID' => C('YZ_SYSTEM_ID'), 
                'NodeID' => $this->nodeId, 
                'TransactionID' => $TransactionID, 
                'ContractID' => $this->contractId, 
                'OrderBy' => 'trans_time desc', 
                'OnePageLimitNum' => $onePageNum, 
                'CurrPageNum' => $pageNum));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $rechargeRecordList = $RemoteRequest->requestYzServ($req_array);
        // dump($rechargeRecordList);
        if (! $rechargeRecordList ||
             ($rechargeRecordList['Status']['StatusCode'] != '0000' &&
             $rechargeRecordList['Status']['StatusCode'] != '0001')) {
            $this->error("获取信息失败{$rechargeRecordList['Status']['StatusCode']}");
        }
        if (! empty($rechargeRecordList['NodeRechargeAmtInfo']['Row'])) {
            import("ORG.Util.Page"); // 导入分页类
            $Page = new Page($rechargeRecordList['TotalNum'], $onePageNum);
            $show = $Page->show();
            if (isset($rechargeRecordList['NodeRechargeAmtInfo']['Row'][0])) {
                $dataList = array_merge($dataList, 
                    $rechargeRecordList['NodeRechargeAmtInfo']['Row']);
            } else {
                $dataList[] = array_merge($dataList, 
                    $rechargeRecordList['NodeRechargeAmtInfo']['Row']);
            }
            $this->assign('page', $show);
        }
        $this->assign('dataList', $dataList);
        $this->display();
    }
    
    // 获取从当前月份开始的前的月份
    public function agoMonth($num = 12) {
        $month = array();
        $m = date('m');
        for ($i = 0; $i < $num; $i ++) {
            $month[] = date("Ym", mktime(0, 0, 0, $m - $i, 1));
        }
        return $month;
    }
    
    // 账单详情
    public function accDetail() {
        $mon = I('get.month', null, 'intval');
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999);
        $req_array = array(
            'QueryBillReq' => array(
                'TransactionID' => $TransactionID,  // 流水号
                'SystemID' => C('YZ_SYSTEM_ID'),  // 平台号
                'ContractID' => $this->contractId,  // 结算号
                'Month' => $mon)); // 月份
        
        $RemoteRequest = D('RemoteRequest', 'Service');
        $rechargeRecordList = $RemoteRequest->requestYzServ($req_array);
        if ($rechargeRecordList['Status']['StatusCode'] == '0000') {
            $htmlinfo = $rechargeRecordList['HtmlInfo'];
        } else if ($rechargeRecordList['Status']['StatusCode'] == '1000') {
            $info = substr($mon, 4, 6);
            $htmlinfo = $this->htmlcontent();
            $htmlinfo = str_replace("####", $info, $htmlinfo);
        } else {
            $htmlinfo = $rechargeRecordList['Status']['StatusText'];
        }
        $this->show($htmlinfo);
    }

    public function htmlcontent() {
        return '<html>
                <head>
                <meta http-equiv="Content-Type" content="text/html; charset=GBK">
                </head>
                <body>
                    <table style="color:#000;font:14px/1.5em microsoft yahei;" align="center" bgcolor="#ffffff" border="0" cellpadding="0" cellspacing="0" width="650">
                        <tr>
                          <td style="text-align:left;" width="60%"><img src="http://101.231.188.78:8083/jsxt/images/bill-logo.png"></td>
                          <td style="text-align:right;"><h1 style="text-align: right;color: #000;font-size:20px;"><span style="text-align: right;color: #0074b6;font-size: 26px;font-weight: bold;">####月</span>账单&nbsp;&nbsp;</h1></td>
                          <td style="text-align:right;"><img src="http://101.231.188.78:8083/jsxt/images/bill-qr.png"></td>
                        </tr>
                        <tr>
           <tr>
            <td colspan="3" style="padding:10px; text-indent:20px;font-size: 14px;text-align: center;">
              <br/><br/><br/><br/><br/><p style="margin:0;">该月没有账单数据</p><br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>
           </tr>
            <tr>
            <td colspan="3" style="padding:20px; text-indent:20px;font-size: 14px;text-align: left;border: solid 1px #dad9d9;background: #f8f8f8;border-radius: 5px;-moz-border-radius: 5px;-ms-border-radius: 5px;-o-border-radius: 5px;-webkit-border-radius: 5px;">
              <p style="margin:0;">感谢您使用电子凭证服务，为您提供优质的服务一直是我们努力的目标！也希望您将我们的热忱传播给您的朋友，竭诚期盼您的推荐，我们的联系电话是<a href="tel:4008827770" style="color: #0074b6; text-decoration:none;">400-882-7770</a>，您还可以使用客户服务信箱<a href="mailto:7005@imageco.com.cn" style="color: #0074b6; text-decoration:none;">7005@imageco.com.cn</a>。再次感谢您！祝您工作愉快！</p>
        </tr>
        <tr>
        <td colspan="3" style="padding:10px; text-indent:20px;font-size: 14px;text-align: center;">
              <p style="margin:0;">客服热线：<a href="tel:4008827770" style="color: #000000; text-decoration:none;">400&nbsp;882&nbsp;7770</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;官网：<a href="http://www.imageco.com.cn" style="color: #000000; text-decoration:none;">www.imageco.com.cn</a></p>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * 显示已购买的所有服务(活动)
     */
    public function serviceBuy() {
        // 检测是否是付费用户
        if ($this->wc_version == 'v9' || $this->wc_version == 'v4') {
            $this->display();
            exit();
        }
        
        // 检测并整理搜索条件
        if (IS_POST) {
            $argument = array_filter(I('post.')); // 过滤空value
        } else {
            $argument = array_filter(I('get.')); // 过滤空value
            unset($argument['p']);
        }
        
        if (count($argument) == '2') {
            if (empty($argument['badd_time']) || empty($argument['eadd_time'])) {
                $this->error('请设置正确的时间进行查询');
                exit();
            }
        }
        
        $ServicesModel = D('ServicesCenter');
        
        // 拿到总页数
        $count = $ServicesModel->getindent($this->node_id, $argument, 'true');
        
        // 没记录就直接显示提示
        if ($count == '0') {
            $this->assign('argument', $argument);
            $this->display();
            exit();
        }
        
        // 载入分页类
        import("ORG.Util.Page");
        $p = new Page($count, self::NUMBER_PER_PAGE);
        
        // 存回url中
        if (count($argument) > 0) {
            $p->parameter = http_build_query($argument);
        }
        
        $allindent = $ServicesModel->getindent($this->node_id, $argument, '', 
            $p->firstRow, $p->listRows);
        $page = $p->show();
        
        $this->assign('argument', $argument);
        $this->assign('ServiceInfo', $allindent);
        $this->assign('page', $page);
        $this->display();
    }

    /**
     * 指定的服务(活动)详细信息
     */
    public function ByServiceInfo() {
        if (IS_POST) {
            $actId = I('post.actId');
            
            $ServicesModel = D('ServicesCenter');
            
            $result = $ServicesModel->servicedetails($this->node_id, $actId);
            
            if ($result) {
                echo $result;
                exit();
            } else {
                echo "false";
                exit();
            }
        }
    }

    /**
     * 我的订单
     */
    public function myOrder() {
        // 搜索条件 开始时间
        $startTime = I('start_time_pay_order');
        $this->assign('start_time_pay_order', $startTime);
        if ($startTime) {
            $startTime = strtotime($startTime);
        }
        // 搜索条件 结束时间
        $endTime = I('end_time_pay_order');
        $this->assign('end_time_pay_order', $endTime);
        if ($endTime) {
            $endTime = strtotime($endTime . '235959');
        }
        // 支付状态
        $payStatus = I('pay_status');
        $this->assign('pay_status', $payStatus);
        // 订单类型
        $orderType = I('order_type');
        $this->assign('order_type', $orderType);
        if ($orderType == '') {
            $orderType = array(
                'in', 
                array(
                    CommonConst::ORDER_TYPE_WHEEL_NORMAL, 
                    // 还未开发,暂时不显示
                    CommonConst::ORDER_TYPE_APPLY_POS, 
                    CommonConst::ORDER_TYPE_DM, 
                    // CommonConst::ORDER_TYPE_FREE_VALIDATE,
                    CommonConst::ORDER_TYPE_ONLINE_TREATY));
        }
        $keyword = I('orderKeywords');
        $this->assign('orderKeywords', $keyword);
        // 载入分页类
        import("ORG.Util.Page");
        $count = D('ServicesCenter')->getMyOrder($this->node_id, $orderType, 
            $startTime, $endTime, $keyword, $payStatus, false);
        $p = new Page($count, self::NUMBER_PER_PAGE);
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $orderList = D('ServicesCenter')->getMyOrder($this->node_id, $orderType, 
            $startTime, $endTime, $keyword, $payStatus, 
            $p->firstRow . ',' . $p->listRows);
        $page = $p->show();
        $this->assign('page', $page);
        $this->assign('orderList', $orderList);
        $this->display();
    }

    /**
     * 订单详情
     */
    public function myOrderDetail() {
        $orderId = I('orderId');
        try {
            $result = D('ServicesCenter')->getOrderDetail($this->node_id, 
                $orderId);
            $this->assign('data', $result['data']);
            $content = $this->fetch($result['template']);
            $this->show($content);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 将内容进行UNICODE编码
     *
     * @param string $name 汉字
     * @return string unicode字符串
     */
    public function unicode_encode($name) {
        $script = preg_replace_callback("/[\x{4e00}-\x{9fa5}]/iu", 
            function ($match) {
                return '\u' .
                     (string) bin2hex(iconv('UTF-8', 'UCS-2', $match[0]));
            }, $name);
        return $script;
    }

    /**
     * 检查有没有付款，并且ajax返回用来跳转的url
     */
    public function goCashier() {
        $orderId = I('orderId');
        // 跳转到收银台
        echo D('Order')->getCashierUrl($this->node_id, $orderId);
    }

    /**
     * [checkOrderExpiry 检查订单是否可以支付]
     *
     * @return [type] [description]
     */
    public function checkOrderExpiry() {
        $orderId = I('post.orderId', null);
        if (! $orderId) {
            $this->error('订单号不存在！');
        }
        $orderInfo = M('tactivity_order')->where(
            array(
                'id' => $orderId, 
                'node_id' => $this->nodeId))->find();
        if ($orderInfo['pay_status'] == '1') {
            $this->error('您的订单已支付成功！');
        }
        $orderMonth = date('Ym', strtotime($orderInfo['add_time']));
        if ($orderInfo['order_type'] == 5 && date('Ym') > $orderMonth) // 在线签约
{
            $this->error('无法支付，请前往在线签约重新生成订单！');
        } else {
            $this->success('订单可以支付！');
        }
    }

    /**
     * [cancelOrder 取消订单，并通知营帐解冻资金]
     *
     * @return [type] [description]
     */
    public function cancelOrder() {
        $orderId = I('post.orderId', null);
        if (! $orderId) {
            $this->error('订单号不存在！');
        }
        $orderInfo = M('tactivity_order')->where(
            array(
                'id' => $orderId, 
                'node_id' => $this->nodeId))->find();
        if ($orderInfo['pay_status'] == '1') {
            $this->error('您的订单已支付成功,无法取消！');
        } elseif ($orderInfo['pay_status'] == '2') {
            $this->error('您的订单已取消！');
        }
        // 这里未用事务处理，因为担心通知营帐的时候耗时过长而导致订单表出故障
        $bRet = M('tactivity_order')->where(
            array(
                'id' => $orderId, 
                'node_id' => $this->nodeId))->save(
            array(
                'pay_status' => '2'));
        if ($bRet === false) {
            log_write(
                '订单' . $orderInfo['order_number'] .
                     '更改数据库pay_status时没有成功，导致订单取消失败！');
            $this->error('订单取消失败！');
        } else {
            log_write('订单' . $orderInfo['order_number'] . '开始请求营帐解冻订单！');
            $bRet = D('Order')->YzNoticeBackMoney($this->nodeId, $orderId);
            if ($bRet) {
                log_write(
                    '订单' . $orderInfo['order_number'] . '取消成功;订单状态已修改成:已取消');
                $this->success('订单取消成功！');
            } else {
                M('tactivity_order')->where(
                    array(
                        'id' => $orderId, 
                        'node_id' => $this->nodeId))->save(
                    array(
                        'pay_status' => '0'));
                log_write(
                    '订单' . $orderInfo['order_number'] . '取消失败;订单状态已修改成:待支付');
                $this->error('订单取消失败！');
            }
        }
    }
}