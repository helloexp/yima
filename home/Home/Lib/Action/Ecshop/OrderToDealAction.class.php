<?php

// 订单处理操作
class OrderToDealAction extends Action {

    private $date;

    public function __construct() {
        $this->date = date('Ymd', strtotime("-1 day"));
    }

    public function saveInfo() {
        // $startDate = date('YmdHim', time()-60*60*24*30);
        $startDate = date('YmdHim', time());
        // 取得已处理后的订单节点
        $sendNodeObj = D('SendNode', 'Service');
        $orderConfig = $sendNodeObj->getNodeConfig();
        $orderTime = $sendNodeObj->saveOrder($startDate, $orderConfig);
        if (false != $orderTime) {
            $result = $sendNodeObj->updateNodeConfig($orderTime);
            if (! $result) {
                log_write("记录短信订单配置失败. 订单支付时间：{$orderTime}", 'ERROR', 
                    'SendNode');
            } else {
                log_write("记录短信订单成功. 订单支付时间：{$orderTime}", 'SUCCESSS', 
                    'SendNode');
                echo 'success';
            }
        }
    }
    
    // 临时运行脚本
    public function tmpScript() {
        $dailyObj = D('DailyCashStatistics');
        $getTime = strtotime($dailyObj->getFirstTime());
        $nowTime = strtotime(date("Y-m-d 23:59:59", strtotime("-1 day")));
        $i = 999;
        $oneDay = 3600 * 24;
        for ($j = 0; $j < $i; $i ++) {
            if ($getTime < $nowTime) {
                $dailyObj->saveInfo($getTime);
                $getTime = $getTime + $oneDay;
            } else {
                break;
            }
        }
    }

    /**
     * Description of SkuService 自动提现脚本 暂不使用
     *
     * @param
     *
     * @return
     *
     * @author john_zeng
     */
    public function payMoney() {
        // 查询需要提现的申请
        // $query = $mysql->query($sql);
        $tmpArray = array();
        $payOrderInfo = array();
        $listInfo = M()->table("tnode_cash_trace ct")->join(
            'tnode_cash as tc ON ct.node_id = tc.node_id')
            ->join('tbank_info ti ON ti.bank_name = tc.account_bank')
            ->field(
            'ct.id,ct.cash_money,tc.bank_type,tc.account_bank_ex,tc.account_name,tc.account_no,ti.bank_code')
            ->
        // ->where(array('ct.trans_status' => array('IN', array('0', '1')),
        // 'ct.trans_type' => '2', 'ct.add_time' => array('LIKE',
        // "%{$this->date}%")))
        where(
            array(
                'ct.trans_status' => array(
                    'IN', 
                    array(
                        '0', 
                        '1')), 
                'ct.trans_type' => '2'))
            ->select();
        // $title_arr = array('*商户流水号', '*收款方账户类型', '*收款方开户机构', '*收款方户名',
        // '*收款方账号', '*金额(元)', '是否需审核', '开户机构支行全称', '收款方证件名称', '收款方证件号码',
        // '收款方手机号码', '省', '市', '备注');
        if (is_array($listInfo)) {
            foreach ($listInfo as $val) {
                $payOrderId = get_sn();
                $tmpArray['order_id'] = $payOrderId;
                $tmpArray['mer_date'] = date('Ymd');
                // $tmpArray['amount'] = $listInfo['CASH_MONEY']; //转账金额，单位：元',
                $tmpArray['amount'] = '0.01'; // 转账金额，单位：元',
                $tmpArray['recv_account_type'] = '00'; // 账户类型：00是银行，02是U付',
                $tmpArray['recv_bank_acc_pro'] = $val['BANK_TYPE']; // 对私：0，对公：1',
                $tmpArray['recv_account'] = $val['ACCOUNT_NO']; // 收款方帐号',
                $tmpArray['recv_user_name'] = $val['ACCOUNT_NAME']; // 收款方户名',
                $tmpArray['recv_gate_id'] = $val['BANK_CODE']; // 收款方银行卡的所属银行,选银行卡时必填',
                $tmpArray['bank_brhname'] = $val['ACCOUNT_BANK_EX']; // 开户行支行全称，选银行卡时必填',
                $tmpArray['purpose'] = '提现申请'; // 付款原因，摘要，选银行卡时必填',
                if (! M('ttransfer_trace')->add($tmpArray)) {
                    array_push($payOrderInfo, $payOrderId);
                    M('tnode_cash_trace')->where(
                        array(
                            'id' => $val['ID']))->save(
                        array(
                            'trans_status' => 1, 
                            'pay_order_id' => $payOrderId));
                } else {
                    M('tnode_cash_trace')->where(
                        array(
                            'id' => $val['ID']))->save(
                        array(
                            'trans_status' => 4, 
                            'pay_order_id' => $payOrderId));
                }
            }
            if (count($payOrderInfo) > 0) {
                $pri_file = date('ymd') . '0001';
                $cols_arr = array(
                    'id' => 'ID', 
                    'account_name' => '商户流水号', 
                    'cash_money' => '提现金额', 
                    'bank_type' => '账户类型', 
                    'account_no' => '收款帐号', 
                    'account_bank_ex' => '支行信息', 
                    'bank_code' => '银行编码');
                querydata_download($listInfo, $cols_arr, null, null, $pri_file, 
                    true);
                $msg = '自动提现信息失败，其中订单号：' . implode(',', $payOrderInfo) .
                     '未插入到提现信息中';
                $content['petname'] = C('mailto.sendOne');
                $content['CC'] = C('mailto.notice');
                
                $content['test_title'] = "自动提现提交数据库信息失败" . date('YmdHis');
                $content['text_content'] = "自动提现提交数据库信息失败" . date('YmdHis');
                $content['add_file'] = array();
                $content['add_file'][] = C('DOWN_TEMP') . $pri_file . '.xls';
                // $content['add_file'] = $this->file_path.$pub_file;
                // $content['add_file'] =
                // array($this->file_path.$pub_file,$this->file_path.$pri_file);
                $rs = to_email($content);
                log_write(
                    "send mail to " . $content['petname'] . " CC to " .
                         $content['CC'] . " result[" . $rs . "]");
                log_write($msg, 'error');
            } else {
                $msg = '自动提现信息信息提交成功';
                log_write($msg);
            }
        }
    }

    /**
     * Description of SkuService 自动发送邮件到财务
     *
     * @param 执行时间，每天凌晨6点执行
     * @return
     *
     * @author john_zeng
     */
    public function sendMailToFinance() {
        // $dayInfo = M('tnode_daily_cash_statistics')->where(array('trans_date'
        // => $this->date))->find();
        $dayInfo = M('tnode_daily_cash_statistics')->where(
            array(
                'trans_date' => $this->date))
            ->field('SUM(actual_money) as actual_money, pay_channel')
            ->group('pay_channel')
            ->select();
         
        
        $aliyPay = '0.00';
        $weChatPay = '0.00';
        $Upay = '0.00';
        if ($dayInfo) {
            foreach ($dayInfo as $val) {
                switch ($val['pay_channel']) {
                    case '1':
                        $aliyPay = $val['actual_money'];
                        break;
                    case '2':
                        $Upay = $val['actual_money'];
                        break;
                    case '3':
                        $weChatPay = $val['actual_money'];
                        break;
                }
            }
        }
        $msg = '<TABLE style="BORDER-COLLAPSE: collapse" borderColor=#000000 cellSpacing=0 width=700 bgColor=#ffffff border=1>';
        $msg .= '<TBODY><TR>' .
             '<TD width=100><DIV align=center>日期</DIV></TD>' .
             '<TD width=300><DIV align=center>微信应到账金额</DIV></TD>' .
             '<TD width=300><DIV align=center>支付宝应到金额</DIV></TD>' .
             '<TD width=300><DIV align=center>银行卡应到账金额</DIV></TD>' .
             '<TD width=300><DIV align=center>应到账总额</DIV></TD></TR>' . '<TR>';
        $msg .= '<TBODY><TR>' . '<TD width=100><DIV align=center>' .
             $this->date . '</DIV></TD>' . '<TD width=100><DIV align=center>' .
             $weChatPay . '￥</DIV></TD>' . '<TD width=100><DIV align=center>' .
             $aliyPay . '￥</DIV></TD>' . '<TD width=100><DIV align=center>' .
             $Upay . '￥</DIV></TD>' . '<TD width=100><DIV align=center>' .
             ($aliyPay + $weChatPay + $Upay) . '￥</DIV></TD></TR>' .
             '<TR></TBODY></TABLE><br />';
        $msg .= "请核对各个支付通道实际到账金额是否正确；<br />";
        $msg .= "如到账金额正确，请将这部分款项转入优付待提现账户；<br />";
        $msg .= "如到账金额不正确，请及时与产品部-" .C('mailto.sendName'). "反馈问题，以便及时处理。<br />";

        $content['petname'] = C('mailto.sendOne');
        // $content['petname'] = 'zengc@imageco.com.cn';
        $content['CC'] = C('mailto.notice');

        $content['test_title'] = "【重要邮件】" .
             date('Y年m月d日', strtotime("-1 day")) . "平台交易结算报表";
        $content['text_content'] = $msg;
        $rs = to_email($content);
        log_write($msg);
    }

    /**
     * Description of SkuService 自动发送短信通知客户
     *
     * @param 执行时间，每天上午10点执行
     * @return
     *
     * @author john_zeng
     */
    public function noticeToCustomer() {
        // 取得订购商品goodsInfo
        $cycleInfo = M('tgoods_info')->where(
            array(
                'is_order' => '2'))
            ->field('config_data,goods_id')
            ->select();
        $configInfo = array();
        // 取得配置信息
        foreach ($cycleInfo as $val) {
            $tmpArray = json_decode($val['config_data'], true);
            $tmpArray['goods_id'] = $val['goods_id'];
            if (1 === $tmpArray['cycle']['cycle_notice_falg']) {
                self::cycleCheck($val['goods_id'], 
                    (int) $tmpArray['cycle']['cycle_notice_before_day']);
            }
        }
    }

    /**
     * Description of SkuService 处理需要发送短信通知的订购订单
     *
     * @param string $goodsId //商品ID int $noticeTime //提前通知用户时间
     * @return bloor
     * @author john_zeng
     */
    public function cycleCheck($goodsId, $noticeTime) {
        // 取得当前商品ID提前通知的订购信息
        $nowTime = date('Ymd');
        $getInfo = M()->table("tbatch_info b")->join(
            'ttg_order_info_ex ex ON b.id = ex.b_id')
            ->join('ttg_order_info o ON o.order_id = ex.order_id')
            ->join('ttg_order_by_cycle cy ON cy.order_id = ex.order_id')
            ->where(
            array(
                'b.goods_id' => "{$goodsId}", 
                'o.pay_status' => '2'))
            ->field(
            "ex.order_id, cy.goods_name, cy.receiver_name, cy.receiver_tel, cy.receiver_citycode, o.node_id, o.batch_no,
                                cy.receiver_addr, DATE_SUB(cy.dispatching_date, INTERVAL +{$noticeTime} DAY) AS send_time")
            ->select();
        if ($getInfo) {
            foreach ($getInfo as $val) {
                $sendTime = date('Ymd', strtotime($val['send_time']));
                if ($nowTime == $sendTime) {
                    $msg = '尊敬的' . $val['receiver_name'] . '先生/女士，您的订购订单:' .
                         $val['order_id'] . "将在" . $noticeTime . "天后发送到:" .
                         city_text($val['receiver_citycode'], '') .
                         $val['receiver_addr'] . '请注意查收。';
                    // D('SalePro', 'Service')->sendBless($val['receiver_tel'],
                    // $msg);
                    $batchNo = get_notes_batch_no($val['node_id']);
                    send_SMS($val['node_id'], $val['receiver_tel'], $msg, 
                        $batchNo);
                }
            }
        }
    }
    
    /**
     * Description of SkuService 将老数据中的普通商品维护到规格商品中
     *
     * 
     * @return bloor
     * @author john_zeng
     */
    public function checkGoodsInSku() {
        //获取所有普通商品信息
        M()->startTrans();
        log_write('进程开始:');
        $goodsInfo = M('tgoods_info as g')
                ->join('tbatch_info as b on b.goods_id = g.goods_id and b.node_id = g.node_id')
                ->where("b.m_id is not NULL and g.is_sku = 0 and g.node_id = '00164578'")
                ->field('b.id, b.m_id, b.status, b.end_time, b.node_id, b.batch_amt, b.storage_num, b.remain_num, g.storage_num as gs_num,'
                        . ' g.remain_num as gr_num, g.goods_id,g.end_time as g_time, g.status as g_status')
                ->select();
        log_write('获取数据信息:'. M()->_sql());
        foreach ($goodsInfo as $val){
            $status = 0;
            if($val['g_time'] < date('YmdHis') || '0' != $val['g_status']){
                $status = 1;
            }
            $data = array(
                'node_id' => $val['node_id'],
                'goods_id' => $val['goods_id'],
                'sku_detail_id' => 0,
                'storage_num' => $val['gs_num'],
                'remain_num' => $val['gr_num'],
                'status' => $status
            );
            $result = M('tgoods_sku_info')->data($data)->add();
            log_write('插入第一组数据:'. M()->_sql());
            if(!$result){
                log_write('插入第一组数据失败');
                M()->rollback();
                $this->error('插入tgoods_sku_info信息失败，失败信息 goods_id=>'.$val['goods_id']);
            }
            unset($status, $data, $result);
            $status = 0;
            if($val['end_time'] < date('YmdHis') || '0' != $val['status']){
                $status = 1;
            }
            $data = array(
                'node_id' => $val['node_id'],
                'b_id' => $val['id'],
                'm_id' => $val['m_id'],
                'skuinfo_id' => 0,
                'sale_price' => $val['batch_amt'],
                'storage_num' => $val['storage_num'],
                'remain_num' => $val['remain_num'],
                'status' => $status
            );
            log_write('插入第二组数据:'. M()->_sql());
            $result = M('tecshop_goods_sku')->data($data)->add();
            if(!$result){
                log_write('插入第二组数据失败');
                M()->rollback();
                $this->error('插入tecshop_goods_sku信息失败，失败信息 b_id=>'.$val['id']);
            }
        }     
        M()->commit();
    }
    
    //处理发码失败的订单
    public function reSendCode2(){
        $orderId = I('orderId');
        $orderInfo = M('ttg_order_info')->where(array('order_id' => $orderId))->find();
        $orderListInfo = M('ttg_order_info_ex')->where(array('order_id' => $orderId))->select();
        
        if (! $orderListInfo) {
            M()->rollback();
            $this->error = "[fail]获取子订单列表失败. 订单号：{$orderId}";
            return false;
        }
        // 循环处理子订单列表
        foreach ($orderListInfo as $v) {
            $ecgoodsInfo = M()->table('tecshop_goods_ex g')
                ->field('g.*,b.batch_no, b.batch_short_name,b.use_rule,b.print_text')
                ->join('tbatch_info b ON b.id=g.b_id')
                ->where(
                array(
                    'g.b_id' => $v['b_id']))
                ->find();
            // 重新生成发码信息
            $textInfo = array();
            $textInfo['use_rule'] = $ecgoodsInfo['batch_short_name'];
            $textInfo['print_text'] = $ecgoodsInfo['print_text'];
            if (isset($v['ecshop_sku_desc'])) {
                $textInfo['use_rule'] .= '[' . $v['ecshop_sku_desc'] .']';
                $textInfo['print_text'] .= $v['ecshop_sku_desc'];
            }
            $textInfo['use_rule'] .= $ecgoodsInfo['use_rule'];
            // $delivery_flag = $ecgoodsInfo['delivery_flag'];
            if ($v['receiver_type'] == '0') { // 发码
                if ($orderInfo['is_gift'] == '1') { // 送礼发码
                                                    // 送礼短信发送
                    $sendInfo = array(
                        'nodeId' => $orderInfo['node_id'], 
                        'batchNo' => $ecgoodsInfo['batch_no'], 
                        'bId' => $ecgoodsInfo['b_id'], 
                        'mId' => $ecgoodsInfo['m_id'], 
                        'channelId' => $channelId, 
                        'textInfo' => $textInfo);
                    log_write("发码开始：{$orderId}", 'FAIL', 'BOUNSPAY');
                    D('SalePro', 'Service')->checkGiftInfo($orderInfo['order_id'], $sendInfo);
                } else {
                    for ($i = 1; $i <= $v['goods_num']; $i ++) {
                        // 发码sendCode2($orderId,$orderType,$nodeId,$issBatchNo,$phone,$bId,$mId)
                        log_write("发码开始2：{$orderId}", 'FAIL', 'BOUNSPAY');
                        D('SalePro', 'Service')->sendCode2(
                            $orderInfo['order_id'], '2', 
                            $orderInfo['node_id'], 
                            $ecgoodsInfo['batch_no'], 
                            $orderInfo['receiver_phone'], 
                            $ecgoodsInfo['b_id'], $ecgoodsInfo['m_id'], 
                            $channelId, '', $textInfo);
                    }
                }
            }
        }
    }
}
