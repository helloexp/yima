<?php

class OnlineTradingAction extends BaseAction {

    protected $businessEmail = 'qianwen@imageco.com.cn';
    // 开通电商版推送邮箱
    protected $orderStatus = array();
    protected $hallModel;

    public function _initialize() {
    	$this->hallModel = D('Hall');
    	$this->orderStatus = $this->hallModel->getOrderStatus();
        $noLoginArr = array(
            'returnCheck', 
            'notifyCheck', 
            'splitMoney', 
            '_sendMoneyToMerchant'); // 不校验登陆的方法
        if (! in_array(ACTION_NAME, $noLoginArr)) {
            parent::_initialize();
        }
        // 在线交易权限校验:采购商免费版以上都能用 供货商必须开通多宝电商版才能用
        $supplyActionArr = array( // 需要校验的供货商模块
            'supplyConfirmOrder', 
            'supplyCloseOrder', 
            'supplyNodeAccount');
        if (in_array(ACTION_NAME, $supplyActionArr)) {
            $wcversion = $this->_hasEcshop($this->nodeId);
            if (! $wcversion) {
                if (IS_AJAX === true)
                    $this->error('您没有权限使用该功能');
                $this->display('versionErr');
                exit();
            }
        }
    }

    public function supplyOrderList() {
        $map = array(
            'b.goods_node_id' => $this->nodeId);
        $seachStatus = 0; // 更多筛选状态
        $name = I('node_name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['n.node_name'] = array(
                'like', 
                "%{$name}%");
        }
        // 处理特殊查询字段
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['b.add_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['b.add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        
        $status = I('status', null, 'mysql_real_escape_string');
        if (isset($status) && $status != '') {
            $map['b.status'] = $status;
        }
        import("ORG.Util.Page");
        $count = M()->table('tnode_goods_book b')
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        $list = M()->table("tnode_goods_book b")->field(
            'b.*,h.batch_name,h.batch_img,h.end_time,h.id as h_id,n.node_name')
            ->join('thall_goods h ON b.hall_id=h.id')
            ->join('tnode_info n ON b.node_id=n.node_id')
            ->where($map)
            ->order('b.add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $this->assign('status', $this->orderStatus);
        $this->assign('list', $list);
        $this->assign("page", $page);
        $this->assign('seachStatus', $seachStatus);
        $this->assign('hallModel',D('Hall'));
        $this->display();
    }

    /*
     * 供货商确认订单
     */
    public function supplyConfirmOrder() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $map = array(
            'b.goods_node_id' => $this->nodeId, 
            'b.status' => '0', 
            'b.order_id' => $orderId);
        $orderInfo = M()->table("tnode_goods_book b")->field(
            'b.book_num,b.node_id as b_node_id,b.goods_node_id,h.batch_img,h.batch_amt,g.*')
            ->join("thall_goods h ON b.hall_id=h.id")
            ->join("tgoods_info g ON h.goods_id=g.goods_id")
            ->where($map)
            ->find();
        if (! $orderInfo)
            $this->error('未找到该订单信息');
            // 校验供货商是否配置了支付宝信息(暂时不涉及到分润了)
            /*
         * $nodeAccountInfo =
         * M('tnode_account')->where("node_id='{$orderInfo['goods_node_id']}'
         * and account_type='4' and status='1'")->find(); if(!$nodeAccountInfo)
         * $this->error('您还没有配置支付宝账户信息');
         */
        
        $status = '1'; // 订单状态
        if ($orderInfo['batch_amt'] == '0')
            $this->error('订单金额不能为零');
        $goodsModel = D('Goods');
        $goodsModel->startTrans();
        // 扣减供货商库存
        $reduc = $goodsModel->storagenum_reduc($orderInfo['goods_id'], 
            $orderInfo['book_num'], '订单确认', '14');
        if (! $reduc) {
            $goodsModel->rollback();
            $this->error('库存扣减失败:' . $goodsModel->getError());
        }
        $uData = array(
            'status' => $status, 
            'check_user' => $this->userId, 
            'check_time' => date('YmdHis'), 
            // 'fee_amt' => $orderInfo['batch_amt'] * $orderInfo['book_num'] *
            // $nodeAccountInfo['fee_rate'],
            // 'fee_rate' => $nodeAccountInfo['fee_rate'],
            'send_time' => $sendTime, 
            'pay_time' => $status == '4' ? date('YmdHis') : '');
        $resutl = M()->table("tnode_goods_book b")->where($map)->save($uData);
        if ($resutl === false) {
            $goodsModel->rollback();
            $this->error('数据更新失败');
        }
        $goodsModel->commit();
        $this->success('订单确认成功');
    }

    /*
     * 供货商关闭订单
     */
    public function supplyCloseOrder() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $map = array(
            'goods_node_id' => $this->nodeId, 
            'status' => '0', 
            'order_id' => $orderId);
        $orderInfo = M('tnode_goods_book')->where($map)->find();
        if (! $orderInfo) $this->error('未找到该订单信息');
        $uData = array('status' => '3');
        $resutl = M('tnode_goods_book')->where($map)->save($uData);
        if ($resutl === false)
            $this->error('系统出错,更新失败');
        $this->success('订单关闭成功');
    }

    /*
     * 采购方关闭订单
     */
    public function purchaserCloseOrder() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $map = array(
            'b.node_id' => $this->nodeId, 
            'b.status' => array(
                'in', 
                '0,1'), 
            'b.order_id' => $orderId);
        $orderInfo = M()->table("tnode_goods_book b")->field('b.*,h.goods_id')
            ->join("thall_goods h ON b.hall_id=h.id")
            ->where($map)
            ->find();
        if (! $orderInfo) $this->error('未找到该订单信息');
        //如果是待付款状态尝试去营帐解冻余额
        if($orderInfo['status'] == '1'){
        	$this->YzNoticeBackMoney($this->nodeId,$orderInfo['order_id']);
        }
        // 给供货商回退库存
        if ($orderInfo['status'] == '1') {
            $goodsModel = D('Goods');
            $reduc = $goodsModel->storagenum_reduc($orderInfo['goods_id'], 
                $orderInfo['book_num'] * - 1, '采货商关闭订单', '14');
            if (! $reduc) {
                $this->error($goodsModel->getError());
            }
        }
        $uData = array(
            'status' => '3');
        $resutl = M()->table("tnode_goods_book b")->where($map)->save($uData);
        if ($resutl === false)
            $this->error('系统出错,更新失败');
        $this->success('订单关闭成功');
    }

    /*
     * 供货商订单交易详情
     */
    public function supplyOrderDetail() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $map = array(
            'b.goods_node_id' => $this->nodeId, 
            'b.order_id' => $orderId);
        $orderInfo = M()->table("tnode_goods_book b")->field(
            'b.*,h.batch_name,h.end_time,h.batch_img,h.id as h_id,n.node_short_name')
            ->join("thall_goods h ON b.hall_id=h.id")
            ->join("tnode_info n ON b.node_id=n.node_id")
            ->where($map)
            ->find();
        if (! $orderInfo)
            $this->error('未找到有效的订单信息');
        $this->assign('orderInfo', $orderInfo);
        $this->assign('status', $this->orderStatus);
        $this->assign('hallModel',D('Hall'));
        $this->display();
    }

    /*
     * 供货商收款记录
     */
    public function supplyCollectionList() {
        $map = array(
            'b.goods_node_id' => $this->nodeId, 
            'b.profit_status' => '2');
        // 处理特殊查询字段
        $startTime = I('start_time', null, 'mysql_real_escape_string');
        if (! empty($startTime)) {
            $map['b.profit_time'] = array(
                'egt', 
                $startTime . '000000');
        }
        $endTime = I('end_time', null, 'mysql_real_escape_string');
        if (! empty($endTime)) {
            $map['b.profit_time '] = array(
                'elt', 
                $endTime . '235959');
        }
        import("ORG.Util.Page");
        $count = M()->table('tnode_goods_book b')
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        $list = M()->table("tnode_goods_book b")->where($map)
            ->order('b.profit_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $this->assign('list', $list);
        $this->assign("page", $page);
        $this->display();
    }

    /*
     * 用户管理
     */
    public function supplyNodeAccount() {
        $nodeAccountInfo = M('tnode_account')->where(
            "node_id='{$this->nodeId}' AND account_type='4'")->find();
        if ($this->isPost()) {
            $error = '';
            $accountName = I('account_name', null);
            if (! check_str($accountName, 
                array(
                    'null' => false), $error)) {
                $this->error("支付宝账号{$error}");
            }
            $pwd = I('pwd', null);
            if (! check_str($pwd, 
                array(
                    'null' => false, 
                    'regxp' => '/^[A-Za-z0-9]+$/', 
                    'minlen' => '6', 
                    'maxlen' => '16'), $error)) {
                $this->error("密码{$error}");
            }
            if (empty($nodeAccountInfo['account_no'])) { // 添加账号
                $cpwd = I('cpwd');
                if ($cpwd !== $pwd)
                    $this->error('俩次密码输入不一致');
                $adata = array(
                    'node_id' => $this->nodeId, 
                    'account_type' => '4', 
                    'account_no' => $accountName, 
                    'status' => '1', 
                    'add_time' => date('YmdHis'), 
                    'account_bank' => '支付宝', 
                    'fee_rate' => '0.0100', 
                    'rolytal_day' => '7', 
                    'check_pwd' => md5($pwd));
                $aresult = M('tnode_account')->add($adata);
                if (! $aresult)
                    $this->error('数据库出错更新失败');
                $this->success('账户配置成功');
            } else { // 更新账号
                if ($nodeAccountInfo['status'] == '2')
                    $this->error('该账户已被停用,请联系客服');
                if (md5($pwd) !== $nodeAccountInfo['check_pwd'])
                    $this->error('密码错误');
                $udata = array(
                    'account_no' => $accountName);
                $uresult = M('tnode_account')->where(
                    "id='{$nodeAccountInfo['id']}'")->save($udata);
                if ($uresult === false)
                    $this->error('数据库出错更新失败');
                $this->success('账户修改成功');
            }
        }
        $this->assign('AccountInfo', $nodeAccountInfo);
        $this->display();
    }

    /*
     * 采购方订单列表
     */
    public function purchaserOrderList() {
        $map = array(
            'b.node_id' => $this->nodeId);
        $seachStatus = 0; // 更多筛选状态
        $name = I('node_name', null, 'mysql_real_escape_string');
        if (! empty($name)) {
            $map['n.node_name'] = array(
                'like', 
                "%{$name}%");
        }
        // 处理特殊查询字段
        $baddTime = I('badd_time', null, 'mysql_real_escape_string');
        if (! empty($baddTime)) {
            $map['b.add_time'] = array(
                'egt', 
                $baddTime . '000000');
        }
        $eaddTime = I('eadd_time', null, 'mysql_real_escape_string');
        if (! empty($eaddTime)) {
            $map['b.add_time '] = array(
                'elt', 
                $eaddTime . '235959');
        }
        
        $status = I('status', null, 'mysql_real_escape_string');
        if (isset($status) && $status != '') {
            $map['b.status'] = $status;
        }
        import("ORG.Util.Page");
        $count = M()->table('tnode_goods_book b')
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        $list = M()->table("tnode_goods_book b")->field(
            'b.*,h.batch_name,h.batch_img,h.end_time,n.node_name,h.id as h_id,h.invoice_type')
            ->join('thall_goods h ON b.hall_id=h.id')
            ->join('tnode_info n ON b.goods_node_id=n.node_id')
            ->where($map)
            ->order('b.add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $page = $p->show();
        $this->assign('status', $this->orderStatus);
        $this->assign('invoiceTypeArr',$this->hallModel->getInvoicePayType());
        $this->assign('payTypeArr',$this->hallModel->getPayType());
        $this->assign('list', $list);
        $this->assign("page", $page);
        $this->assign('seachStatus', $seachStatus);
        $this->assign('hallModel',D('Hall'));
        $this->display();
    }

    /*
     * 采购商订单交易详情
     */
    public function purchaserOrderDetail() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $map = array(
            'b.node_id' => $this->nodeId, 
            'b.order_id' => $orderId);
        $orderInfo = M()->table("tnode_goods_book b")->field(
            'b.*,h.batch_name,h.end_time,h.batch_img,h.cg_name,h.cg_mail,h.cg_phone,h.cg_mark,h.id as h_id,h.invoice_type,n.node_short_name')
            ->join("thall_goods h ON b.hall_id=h.id")
            ->join("tnode_info n ON b.goods_node_id=n.node_id")
            ->where($map)
            ->find();
        if (! $orderInfo)
            $this->error('未找到有效的订单信息');
        $this->assign('orderInfo', $orderInfo);
        $this->assign('status', $this->orderStatus);
        $this->assign('invoiceTypeArr',$this->hallModel->getInvoicePayType());
        $this->assign('hallModel',D('Hall'));
        $this->display();
    }

    /*
     * 采购商付款确认
     */
    public function orderPayConfirm() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $map = array(
            'b.node_id' => $this->nodeId, 
            'b.order_id' => $orderId, 
            'b.status' => '1');
        $orderInfo = M()->table("tnode_goods_book b")->field(
            'b.*,h.batch_name,h.end_time,h.batch_img,h.invoice_type')
            ->join("thall_goods h ON b.hall_id=h.id")
            ->where($map)
            ->find();
        if (! $orderInfo)
            $this->error('未找到有效的订单信息');
        $this->assign('invoiceTypeArr',$this->hallModel->getInvoicePayType());
        $this->assign('orderInfo', $orderInfo);
        $this->assign('hallModel',D('Hall'));
        $this->display();
    }

    /*
     * 采购商付款记录
     */
    public function purchaserPayList() {
        $map = array(
            'b.node_id' => $this->nodeId, 
            'b.status' => array(
                'in', 
                '2,4'), 
            'b.goods_price' => array(
                'gt', 
                '0'));
        // 处理特殊查询字段
        $startTime = I('start_time', null, 'mysql_real_escape_string');
        if (! empty($startTime)) {
            $map['b.pay_time'] = array(
                'egt', 
                $startTime . '000000');
        }
        $endTime = I('end_time', null, 'mysql_real_escape_string');
        if (! empty($endTime)) {
            $map['b.pay_time '] = array(
                'elt', 
                $endTime . '235959');
        }
        import("ORG.Util.Page");
        $count = M()->table('tnode_goods_book b')
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        $list = M()->table("tnode_goods_book b")->where($map)
            ->order('b.pay_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        // 分页显示
        $hallModel =  D('Hall');
        $page = $p->show();
        $this->assign('payTypeArr',$hallModel->getPayType());
        $this->assign('orderStatusArr',$this->orderStatus);
        $this->assign('list', $list);
        $this->assign("page", $page);
        $this->display();
    }

    /*
     * 支付宝付款
     */
    public function parFor() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $map = array(
            'b.node_id' => $this->nodeId, 
            'b.order_id' => $orderId, 
            'b.status' => '1');
        $orderInfo = M()->table("tnode_goods_book b")->field(
            'b.*,h.batch_name,g.goods_type,g.goods_name,g.storage_type,g.remain_num,g.status as g_status')
            ->join("thall_goods h ON b.hall_id=h.id")
            ->join("tgoods_info g ON h.goods_id=g.goods_id")
            ->where($map)
            ->find();
        if (! $orderInfo)
            $this->error('未找到有效的订单信息');
        if ($orderInfo['g_status'] != '0')
            $this->error('该卡券已停用或过期');
        if ($orderInfo['goods_price'] == '0')
            $this->error('订单金额不能为零');
            // 检查库存
        if ($orderInfo['storage_type'] == '1' &&
             $orderInfo['remain_num'] < $orderInfo['book_num']) {
            $this->error('该卡券库存不足,请联系供应商添加库存');
        }
        $totalFee = $orderInfo['goods_price'] * $orderInfo['book_num'];
        //支付数据
        $pData = array(
            'name' => '采购卡券',
            'desc' => $orderInfo['goods_name'],
            'notify_url' => U('CronJob/CashierRet/onlineNotifyUrl','','','',true),
            'return_url' => U('CronJob/CashierRet/onlineReturnUrl','','','',true),
            'system_id' => C('YZ_SYSTEM_ID'),
            'order_id' => $orderInfo['order_id'],
            'rule_type' => '88',
            'client_id' => $this->clientId,
            'price' => $totalFee,
            'data' => json_encode(array(array("S"=>"WcRule","A"=>"EVPay","price"=>$totalFee,'nodeID'=>$this->nodeId,'contractID'=>$this->contractId))), //调用对应操作数据
            'bank_transfer' => '1', //是否支持转账
            'ye_price' => 'ON', //余额支付最大使用金额,'off'不使用'on'开启不传默认为'no'. 0=='off'
            'wb_price' => 'OFF', //旺币支付最大使用金额,'off'不使用'on'开启不传默认为'no'. 0=='off'
            'yszk_price' => 'ON', //预收款账户支付最大使用金额,'off'不使用'on'开启不传默认为'no'. 0=='off'
            'tp_price' => 'on'//第三方支付开关
        );
        switch($orderInfo['goods_type']){
            case '7'://话费
                $pData['name'] = '采购话费';
                $pData['bank_transfer'] = '0';
                break;
            case '8'://Q币
                $pData['name'] = '采购Q币';
                $pData['bank_transfer'] = '0';
                break;
            
                
        }
        //dump($pData);exit;
        log_write('卡券商城跳转到收银台时，传递的参数:' . print_r($pData, true));
        $mackey = C('YZ_MAC_KEY') or die('[YZ_MAC_KEY]参数未设置');
        // 获取sign签名
        ksort($pData); // 排序
        $code = http_build_query($pData); // url编码并生成query字符串
        $sign = md5($mackey . $code . $mackey); // 生成签名
        log_write($mackey . $code . $mackey);
        $pData['sign'] = $sign; // 添加sign
        
        // 跳转到收银台
        $sHtml = "<form id='hideform' style='display:none;' name='hideform' action='" .
             C('YZ_CAHSIER_POST_URL') . "' method='post'>";
        foreach ($pData as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $k => $v) {
                    $sHtml .= "<input type='hidden' name='" . $key . "[" . $k . "]" . "' value='" . $v . "'/>";
                }
            } else {
                $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
            }
        }
        // submit按钮控件请不要含有name属性
        $sHtml = $sHtml . "<input type='submit' value='Submit'></form>";
        $sHtml = $sHtml . "<script>document.forms['hideform'].submit();</script>";
        echo $sHtml;
    }

    /*
     * 支付宝同步返回
     */
    public function returnCheck() {
        $data = $_GET;
        log_write(ACTION_NAME . '___同步____' . print_r($data, true));
        import('@.Vendor.AlipayModel');
        $payMode = new AlipayModel();
        $goodsModel = D('Goods');
        $verifyResult = $payMode->checkReturn($data);
        if ($verifyResult) {
            if ($data['trade_status'] == 'TRADE_FINISHED' ||
                 $data['trade_status'] == 'TRADE_SUCCESS') {
                $orderId = $data['out_trade_no'];
                $orderInfo = M()->table("tnode_goods_book b")->field('b.book_num,b.status as b_status,b.node_id as b_node_id,b.goods_node_id as goods_node_id,h.batch_img,h.id,h.batch_amt,h.zc_market_price,h.zc_pact_price,h.zc_print_price_flag,h.zc_print_text,h.zc_print_control,h.spl_goods_number,g.*')
                    ->join("thall_goods h ON b.hall_id=h.id")
                    ->join("tgoods_info g ON h.goods_id=g.goods_id")
                    ->where("b.order_id='{$orderId}'")
                    ->find();
                if ($orderInfo['b_status'] == '1') {
                    // 更新订单状态
                    $uData = array(
                        'pay_seq' => $data['trade_no'], 
                        'pay_time' => date('YmdHis'), 
                        'status' => '2'
                    );
                    $uResult = M('tnode_goods_book')->where("order_id='{$orderId}'")->save($uData);
                    if ($uResult === false) {
                        log_write('tnode_goods_book表更新失败' . print_r($data, true));
                        $this->error('订单状态更新失败001');
                    }
                    //如果是微信红包不需创建活动
                    if($orderInfo['goods_type'] != '22'){
                        // 付款成功赠送旺币
                        $WheelM = D('Wheel');
                        $result = $WheelM->setWb($orderInfo['b_node_id'],$orderInfo['book_num'],date('Ymd', time() + 3600 * 24 * 30), C('WB_TYPE')); // 有效期30天
                        if (! $result) {
                            log_write('旺币赠送失败:' . $orderId);
                        }
                        // 该卡券是否已经购买过(不用考虑是否过期,再付款前也检查)
                        $buyAgainInfo = M('tgoods_info')->field('goods_id')
                        ->where("purchase_goods_id='{$orderInfo['goods_id']}' AND node_id='{$orderInfo['b_node_id']}' AND goods_amt='{$orderInfo['batch_amt']}' AND online_verify_flag='{$orderInfo['online_verify_flag']}'")
                        ->find();
                        if ($buyAgainInfo) { // 购买过直接增加库存
                            $reduc = $goodsModel->storagenum_reduc($buyAgainInfo['goods_id'],$orderInfo['book_num'] * - 1, '', '14', '卡券采购',$orderInfo['book_num'] * - 1);
                            if (! $reduc) {
                                log_write('库存增加失败:' . $goodsModel->getError());
                                $this->error('库存增加失败:' . $goodsModel->getError());
                            }
                        } else {
                            // 支撑创建活动
                            $goodsModel = D('Goods');
                            // 创建smilid
                            $smilId = $goodsModel->getSmil($orderInfo['batch_img'],$orderInfo['goods_name'], $orderInfo['b_node_id']);
                            if (! $smilId) {
                                log_write('smilId创建失败:' . $goodsModel->getError());
                            }
                            // 创建合约
                            $zcData = array(
                                'shopNodeId' => $orderInfo['goods_node_id'],
                                'bussNodeId' => $orderInfo['b_node_id'],
                                'treatyName' => $orderInfo['goods_name'],
                                'treatyShortName' => $orderInfo['goods_name'],
                                'groupId' => $orderInfo['pos_group'],
                                'salePrice' => $orderInfo['zc_market_price'],
                                'pactPrice' => $orderInfo['zc_pact_price'],
                                'printPriceFlag' => $orderInfo['zc_print_price_flag'],
                                'printControl' => $orderInfo['zc_print_control'],
                                'printText' => $orderInfo['zc_print_text'],
                                'custmomNo' => empty($orderInfo['spl_goods_number']) ? null : $orderInfo['spl_goods_number']);
                            $treatyId = $goodsModel->zcCreateTreaty($zcData);
                            if (! $treatyId) {
                                log_write('合约创建失败:' . $goodsModel->getError());
                                $this->error('合约创建失败:' . $goodsModel->getError());
                            }
                            // 创建活动
                            $orderInfo['online_verify_flag'] == '1' ? $onlineVerify = '01' : $onlineVerify = '';
                            $zcData = array(
                                'isspid' => $orderInfo['b_node_id'],
                                'relationId' => $orderInfo['goods_node_id'],
                                'batchName' => $orderInfo['goods_name'],
                                'batchShortName' => $orderInfo['goods_name'],
                                'groupId' => $orderInfo['pos_group'],
                                'validateType' => $orderInfo['validate_type'],
                                'serviceType' => '05',
                                'onlineVerify' => $onlineVerify,
                                'smilId' => $smilId,
                                'treatyId' => $treatyId,
                                'printText' => $orderInfo['print_text']
                            );
                            $batchInfo = $goodsModel->zcCreateBatch($zcData);
                            if (! $batchInfo) {
                                log_write('活动创建失败:' . $goodsModel->getError());
                                $this->error('活动创建失败:' . $goodsModel->getError());
                            }
                            // 数据库插入数据
                            $goodsData = array(
                                'goods_id' => get_goods_id(),
                                'goods_name' => $orderInfo['goods_name'],
                                'goods_desc' => $orderInfo['goods_desc'],
                                'goods_image' => $orderInfo['batch_img'],
                                'node_id' => $orderInfo['b_node_id'],
                                'pos_group' => $orderInfo['pos_group'],
                                'pos_group_type' => $orderInfo['pos_group_type'],
                                'goods_type' => $orderInfo['goods_type'],
                                'market_price' => $orderInfo['batch_amt'],
                                'goods_amt' => $orderInfo['batch_amt'],
                                'goods_discount' => $orderInfo['goods_discount'],
                                'storage_type' => '1',
                                'storage_num' => $orderInfo['book_num'],
                                'remain_num' => $orderInfo['book_num'],
                                'mms_title' => $orderInfo['mms_title'],
                                'mms_text' => $orderInfo['mms_text'],
                                'sms_text' => $orderInfo['sms_text'],
                                'print_text' => $orderInfo['print_text'],
                                'validate_type' => $orderInfo['validate_type'],
                                'validate_times' => $orderInfo['validate_times'],
                                'begin_time' => $orderInfo['begin_time'],
                                'end_time' => $orderInfo['end_time'],
                                'verify_begin_date' => $orderInfo['verify_begin_date'],
                                'verify_end_date' => $orderInfo['verify_end_date'],
                                'verify_begin_type' => $orderInfo['verify_begin_type'],
                                'verify_end_type' => $orderInfo['verify_end_type'],
                                'add_time' => date('YmdHis'),
                                'join_rule' => $orderInfo['join_rule'],
                                'p_goods_id' => $batchInfo['pGoodsId'],
                                'goods_cat' => $orderInfo['goods_cat'],
                                'source' => '1',
                                'purchase_goods_id' => $orderInfo['goods_id'],
                                'purchase_type' => '0',
                                'batch_no' => $batchInfo['batchNo'],
                                'online_verify_flag' => $orderInfo['online_verify_flag']
                            );
                            $goodsResult = $goodsModel->add($goodsData);
                            if (! $goodsResult) {
                                log_write('goods_info表数据添加失败:' . print_r($goodsData, true));
                                $this->error('数据出错,购买失败');
                            }
                        }
                        // 更新订单状态(已发货)
                        $uData = array(
                            'status' => '4',
                            'send_time' => date('YmdHis')
                        );
                        $uResult = M('tnode_goods_book')->where("order_id='{$orderId}'")->save($uData);
                        if ($uResult === false) {
                            log_write('tnode_goods_book表更新失败' . print_r($uData, true));
                            $this->error('订单状态更新失败002');
                        }
                    }
                    // 翼码代理的发送邮件
                    if (in_array($orderInfo['goods_node_id'],C('YM_HALL_NODE_ID'))) {
                        // 发送邮件
                        $mailData = array(
                            'subject' => "卡券大厅付款:订单号-{$orderId}", 
                            'content' => "订单号:{$orderId}<br/>卡券名称:{$orderInfo['goods_name']}<br/>采购数量:{$orderInfo['book_num']}", 
                            'email' => C('YM_HALL_SEND_MAIL'));
                        send_mail($mailData);
                        log_write("发送邮件成功:订单号{$orderId}为翼码卡券");
                    }
                }
                // 跳转成功页面
                redirect(preg_replace('/dt_alipay_return.php/', 'index.php',U('WangcaiPc/OnlineTrading/paySuccsess',array('order_id' => $orderId), true, false, true)));
                exit();
            }
        } else {
            $this->error('支付失败');
        }
    }

    /**
     * 同步返回支付成功页面
     */
    public function paySuccsess() {
        $orderId = I('order_id');
        $orderInfo = M()->table("tnode_goods_book b")->where("b.order_id='{$orderId}'")->find();
        // 该商户热门的3个卡券
        $otherList = M('thall_goods')->field(
            'id,batch_name,batch_img,batch_amt,visit_num')
            ->where(
            "status='0' AND node_id='{$orderInfo['goods_node_id']}' AND id <> '{$orderInfo['id']}'")
            ->order('visit_num desc')
            ->limit(3)
            ->select();
        if (empty($otherList)) {
            $otherList = M('thall_goods')->field(
                'id,batch_name,batch_img,batch_amt,visit_num')
                ->where("status='0' AND id <> '{$orderInfo['id']}'")
                ->order('hall_top_time desc')
                ->limit(3)
                ->select();
        }
        $this->assign('orderInfo',$orderInfo);
        $this->assign('otherList', $otherList);
        $this->assign('wbNum', $orderInfo['book_num']);
        $this->assign('hallModel',D('Hall'));
        $this->display();
    }

    /*
     * 支付宝异步返回
     */
    public function notifyCheck() {
        $data = $_POST;
        log_write(ACTION_NAME . '___异步____' . print_r($data, true));
        import('@.Vendor.AlipayModel');
        $payMode = new AlipayModel();
        $goodsModel = D('Goods');
        $verifyResult = $payMode->checkReturn($data);
        if ($verifyResult) {
            if ($data['trade_status'] == 'TRADE_FINISHED' ||
                 $data['trade_status'] == 'TRADE_SUCCESS') {
                $orderId = $data['out_trade_no'];
                $orderInfo = M()->table("tnode_goods_book b")->field('b.book_num,b.status as b_status,b.node_id as b_node_id,b.goods_node_id as goods_node_id,h.batch_img,h.batch_amt,h.zc_market_price,h.zc_pact_price,h.zc_print_price_flag,h.zc_print_text,h.zc_print_control,h.spl_goods_number,g.*')
                    ->join("thall_goods h ON b.hall_id=h.id")
                    ->join("tgoods_info g ON h.goods_id=g.goods_id")
                    ->where("b.order_id='{$orderId}'")
                    ->find();
                if ($orderInfo['b_status'] == '1') {
                    // 更新订单状态
                    $uData = array(
                        'pay_seq' => $data['trade_no'], 
                        'pay_time' => date('YmdHis'), 
                        'status' => '2'
                    );
                    $uResult = M('tnode_goods_book')->where("order_id='{$orderId}'")->save($uData);
                    if ($uResult === false) {
                        log_write('tnode_goods_book表更新失败' . print_r($data, true));
                    }
                    //如果是微信红包不需创建活动
                    if($orderInfo['goods_type'] != '22'){
                        // 付款成功赠送旺币
                        $WheelM = D('Wheel');
                        $result = $WheelM->setWb($orderInfo['b_node_id'],$orderInfo['book_num'],date('Ymd', time() + 3600 * 24 * 30), C('WB_TYPE')); // 有效期30天
                        if (! $result) {
                            log_write('旺币赠送失败:' . $orderId);
                        }
                        // 该卡券是否已经购买过
                        $buyAgainInfo = M('tgoods_info')->field('goods_id')
                        ->where("purchase_goods_id='{$orderInfo['goods_id']}' AND node_id='{$orderInfo['b_node_id']}' AND goods_amt='{$orderInfo['batch_amt']}' AND online_verify_flag='{$orderInfo['online_verify_flag']}'")
                        ->find();
                        if ($buyAgainInfo) { // 购买过直接增加库存
                            $reduc = $goodsModel->storagenum_reduc($buyAgainInfo['goods_id'],$orderInfo['book_num'] * - 1, '', '14', '卡券采购',$orderInfo['book_num'] * - 1);
                            if (! $reduc) {
                                log_write('库存增加失败:' . $goodsModel->getError());
                                echo "success";
                                exit();
                            }
                        } else {
                            // 支撑创建活动
                            $goodsModel = D('Goods');
                            // 创建smilid
                            $smilId = $goodsModel->getSmil($orderInfo['batch_img'],$orderInfo['goods_name'], $orderInfo['b_node_id']);
                            if (! $smilId) {
                                log_write('smilId创建失败:' . $goodsModel->getError());
                            }
                            // 创建合约
                            $zcData = array(
                                'shopNodeId' => $orderInfo['goods_node_id'],
                                'bussNodeId' => $orderInfo['b_node_id'],
                                'treatyName' => $orderInfo['goods_name'],
                                'treatyShortName' => $orderInfo['goods_name'],
                                'groupId' => $orderInfo['pos_group'],
                                'salePrice' => $orderInfo['zc_market_price'],
                                'pactPrice' => $orderInfo['zc_pact_price'],
                                'printPriceFlag' => $orderInfo['zc_print_price_flag'],
                                'printControl' => $orderInfo['zc_print_control'],
                                'printText' => $orderInfo['zc_print_text'],
                                'custmomNo' => empty($orderInfo['spl_goods_number']) ? null : $orderInfo['spl_goods_number']
                            );
                            $treatyId = $goodsModel->zcCreateTreaty($zcData);
                            if (! $treatyId) {
                                log_write('合约创建失败:' . $goodsModel->getError());
                                $this->error('合约创建失败:' . $goodsModel->getError());
                            }
                            // 创建活动
                            $orderInfo['online_verify_flag'] == '1' ? $onlineVerify = '01' : $onlineVerify = '';
                            $zcData = array(
                                'isspid' => $orderInfo['b_node_id'],
                                'relationId' => $orderInfo['goods_node_id'],
                                'batchName' => $orderInfo['goods_name'],
                                'batchShortName' => $orderInfo['goods_name'],
                                'groupId' => $orderInfo['pos_group'],
                                'validateType' => $orderInfo['validate_type'],
                                'serviceType' => '05',
                                'onlineVerify' => $onlineVerify,
                                'smilId' => $smilId,
                                'treatyId' => $treatyId,
                                'printText' => $orderInfo['print_text']
                            );
                            $batchInfo = $goodsModel->zcCreateBatch($zcData);
                            if (! $batchInfo) {
                                log_write('活动创建失败:' . $goodsModel->getError());
                                echo "success";
                                exit();
                            }
                            // 数据库插入数据
                            $goodsData = array(
                                'goods_id' => get_goods_id(),
                                'goods_name' => $orderInfo['goods_name'],
                                'goods_desc' => $orderInfo['goods_desc'],
                                'goods_image' => $orderInfo['batch_img'],
                                'node_id' => $orderInfo['b_node_id'],
                                'pos_group' => $orderInfo['pos_group'],
                                'pos_group_type' => $orderInfo['pos_group_type'],
                                'goods_type' => $orderInfo['goods_type'],
                                'market_price' => $orderInfo['batch_amt'],
                                'goods_amt' => $orderInfo['batch_amt'],
                                'goods_discount' => $orderInfo['goods_discount'],
                                'storage_type' => '1',
                                'storage_num' => $orderInfo['book_num'],
                                'remain_num' => $orderInfo['book_num'],
                                'mms_title' => $orderInfo['mms_title'],
                                'mms_text' => $orderInfo['mms_text'],
                                'sms_text' => $orderInfo['sms_text'],
                                'print_text' => $orderInfo['print_text'],
                                'validate_type' => $orderInfo['validate_type'],
                                'validate_times' => $orderInfo['validate_times'],
                                'begin_time' => $orderInfo['begin_time'],
                                'end_time' => $orderInfo['end_time'],
                                'verify_begin_date' => $orderInfo['verify_begin_date'],
                                'verify_end_date' => $orderInfo['verify_end_date'],
                                'verify_begin_type' => $orderInfo['verify_begin_type'],
                                'verify_end_type' => $orderInfo['verify_end_type'],
                                'add_time' => date('YmdHis'),
                                'join_rule' => $orderInfo['join_rule'],
                                'p_goods_id' => $batchInfo['pGoodsId'],
                                'goods_cat' => $orderInfo['goods_cat'],
                                'source' => '1',
                                'purchase_goods_id' => $orderInfo['goods_id'],
                                'purchase_type' => '0',
                                'batch_no' => $batchInfo['batchNo'],
                                'online_verify_flag' => $orderInfo['online_verify_flag']
                            );
                            $goodsResult = $goodsModel->add($goodsData);
                            if (! $goodsResult) {
                                log_write('goods_info表数据添加失败:' . print_r($goodsData, true));
                                echo "success";
                                exit();
                            }
                        }
                        // 更新订单状态(已发货)
                        $uData = array(
                            'status' => '4',
                            'send_time' => date('YmdHis'));
                        $uResult = M('tnode_goods_book')->where(
                                "order_id='{$orderId}'")->save($uData);
                        if ($uResult === false) {
                            log_write('tnode_goods_book表更新失败' . print_r($uData, true));
                            echo "success";
                            exit();
                        }
                    }
                    // 翼码代理的发送邮件
                    if (in_array($orderInfo['goods_node_id'],C('YM_HALL_NODE_ID'))) {
                        $mailData = array(
                            'subject' => "卡券大厅付款:订单号-{$orderId}", 
                            'content' => "订单号:{$orderId}<br/>卡券名称:{$orderInfo['goods_name']}<br/>采购数量:{$orderInfo['book_num']}", 
                            'email' => C('YM_HALL_SEND_MAIL'));
                        send_mail($mailData);
                        log_write("发送邮件成功:订单号{$orderId}为翼码卡券");
                        echo "success";
                        exit();
                    }
                }
                echo "success";
                exit();
            }
        } else {
            echo "fail";
        }
    }

    /*
     * 分润
     */
    protected function _sendMoneyToMerchant($orderId) {
        // 取得订单信息
        $orderModel = M('tnode_goods_book');
        if (! is_array($orderId)) {
            $orderInfo = $orderModel->where("order_id={$orderId}")->find();
        } else {
            $orderInfo = $orderId;
            $orderId = $orderInfo['order_id'];
        }
        log_write("采购订单分润:" . $orderId);
        log_write(print_r($orderInfo, true));
        if (! $orderInfo) {
            log_write("采购订单分润：未找到订单信息。订单号：{$orderInfo['order_id']}");
            return false;
        }
        log_write('采购分润开始,订单信息：' . print_r($orderInfo, true));
        
        $out_bill_no = date("ymdHis") . sprintf('%03s', mt_rand(0, 999));
        $out_trade_no = $orderInfo['order_id'];
        // $trade_no = $orderInfo['pay_seq'];
        $trade_no = '';
        // 获取该商户的支付宝账户
        
        $nodeAccountInfo = M('tnode_account')->where(
            "node_id = {$orderInfo['goods_node_id']} AND account_type=4 AND status=1")
            ->field('account_no,fee_rate')
            ->find();
        $seller_account = $nodeAccountInfo['account_no'];
        
        if (in_array($orderInfo['profit_status'], 
            array(
                '1', 
                '3')) && $orderInfo['status'] == '4') {
            $my_price = $orderInfo['fee_amt']; // 手续费
            $price = $orderInfo['goods_price'] * $orderInfo['book_num'] -
                 $my_price;
            if ($price <= 0) {
                log_write(
                    "分润失败：商家费率：" . $orderInfo['fee_rate'] . ',分润金额：' . $price);
                return false;
            }
            $comment = "旺财采购订单: " . $orderInfo['order_id'];
            $royalty_parameters = $seller_account . "^" . $price . "^" . $comment;
            
            $parameter = array(
                "service" => "distribute_royalty", 
                "partner" => C('FEN_RUN_ALIPAY.Alipay_Partner'), 
                "_input_charset" => C('FEN_RUN_ALIPAY.Alipay_input_charset'), 
                // 请求参数
                "out_bill_no" => $out_bill_no, 
                "out_trade_no" => $out_trade_no, 
                "trade_no" => $trade_no, 
                "royalty_type" => "10", 
                "royalty_parameters" => $royalty_parameters);
            import('@.Vendor.Alipay.alipay_royalty', '', '.php');
            // 构造请求函数
            
            $alipay = new alipay_royalty($parameter, 
                C('FEN_RUN_ALIPAY.Alipay_Key'), 
                C('FEN_RUN_ALIPAY.Alipay_Security'));
            
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
                        'profit_status' => '3'));
                $this->error = '';
                if ($result === false) {
                    $this->error = '分润状态更新失败';
                    log_write(
                        "分润失败,采购订单号:{$orderId} 分润状态更新失败 " . " [SQL]" .
                             M()->_sql() . " [ERROR]" . M()->getDbError(), 
                            'DB_ERROR');
                }
                // Write to log
                log_write(
                    "分润失败, 采购订单号:" . $orderId . " 金额：" . $price . "  备注：" .
                         $comment . '  erro:' . $alipay_error);
                return false;
            }
            
            // 更改分润状态
            
            $result = $orderModel->where("order_id='{$orderId}'")->save(
                array(
                    'profit_time' => date("YmdHis"), 
                    'profit_status' => '2'));
            if ($result === false)
                log_write("分润成功,采购订单号:{$orderId} 分润状态更新失败。" . M()->getDbError(), 
                    'DB_ERROR');
                
                // 增加分润总额
            $result = M('tnode_account')->where(
                array(
                    'account_type' => '4', 
                    'node_id' => $orderInfo['node_id']))->setInc('rolytal_money', 
                $price);
            if ($result === false)
                log_write(
                    "分润成功,采购订单号:{$orderId} 分润金额汇总更新失败。" . M()->getDbError(), 
                    'DB_ERROR');
                
                // Write to log
            log_write(
                "分润成功, 采购订单号:" . $orderId . " 金额：" . $price . "  备注：" . $comment);
            return true;
        } else {
            log_write('采购订单已分润');
            return true;
        }
    }

    /*
     * 分润开始
     */
    public function splitMoney() {
        $orderId = I('orderId');
        $where = array(
            'a.status' => '4', 
            'a.profit_status' => array(
                'in', 
                '1,3'));
        $rolytal_date_col = "DATE_ADD(STR_TO_DATE(SUBSTR(a.`pay_time`,'1',8),'%Y%m%d%'),INTERVAL b.rolytal_day DAY)";
        // 计算分时间的条件
        $where["_string"] = $rolytal_date_col . " < CURDATE()";
        if ($orderId) {
            $where['a.order_id'] = $orderId;
        }
        $orderList = (array) M()->table('tnode_goods_book a')
            ->field("a.*," . $rolytal_date_col . " as rolytal_date")
            ->join(
            "tnode_account b ON a.`goods_node_id` = b.`node_id` AND b.`account_type` = '4'")
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
    
    // 电商版开通发送邮件
    public function businessSend() {
        $contact_phone = I('contact_phone', null);
        $contact_eml = I('contact_eml', null);
        $qq = I('qq', null);
        $sendTime = date('Y-m-d H:i:s');
        if (! $contact_phone || ! $contact_eml)
            $this->error('手机号码或者联系邮箱不得为空');
        $nodeInfo = M('tnode_info')->where("node_id='{$this->nodeId}'")->find();
        
        $content = "旺号：{$nodeInfo['client_id']}<br>真实姓名：{$nodeInfo['contact_name']}<br/>手机号码：{$contact_phone}<br/>邮箱：{$contact_eml}<br/>公司名称：{$nodeInfo['node_name']}<br/>QQ：{$qq}<br/>申请时间：{$sendTime}<br/>";
        $ps = array(
            "subject" => "卡券在线交易业务权限审核", 
            "content" => $content, 
            "email" => $this->businessEmail);
        $resp = send_mail($ps);
        if ($resp['sucess'] == '1') {
            $this->success("您的申请已提交！旺小二稍后会联系您介绍开通相关事宜！");
        } else {
            $this->error("系统出错，邮件发送失败");
        }
    }
    
    /**
     * 线下转账确认
     */
    public function Receivecomfirm(){
    	$orderId = I('order_id',null);
    	$orderInfo = M('tnode_goods_book')->where("order_id='{$orderId}' and node_id='{$this->nodeId}' and pay_type='1'")->find();
    	//if(!$orderInfo) $this->error('未找到有效订单');
    	if($this->ispost()){
    		
    		exit;
    	}
    	$this->display();
    }
    
    /**
     * [YzNoticeBackMoney 通知营帐解冻资金]
     * $flag = 1是关闭该订单的支付能力，并解锁资金
     * $flag = 0是仅仅解锁因支付失败而导致的资金锁定
     */
    public function YzNoticeBackMoney($nodeId, $orderId,$flag = '1') {
    	// 因为存在这笔订单并没有被冻结资金的问题，所以营帐如果判断没有冻结资金，也要返回true
    	$clientId = get_node_info($nodeId,'client_id');
    	$data = array(
    			'system_id' => C('YZ_SYSTEM_ID'),
    			'cancel'    => $flag,
    			'client_id' => $clientId,
    			'order_id'  => $orderId
    	);
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
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}