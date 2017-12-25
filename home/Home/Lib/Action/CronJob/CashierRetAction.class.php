<?php

class CashierRetAction extends Action {

    public $info;
    
    // 初始化的时候应该验证一下签名
    public function __construct() {
        parent::__construct();
        // todo(控制post过来的内容,不同的回调对应的参数不一样,然后记录签名,按照规则校验签名)
        $request = I('post.REQUEST', '', '');
        // $result = D('CashierRet')->verifySign($request, ACTION_NAME);
        $this->info = $request;
    }

    public function openTreaty() {
        $orderNumber = $this->info['order_id'];
        log_write(
            '收到在线签约-订单号' . $orderNumber . ':' . json_encode(I('post.', '', '')));
        // 更新订单表pay_status和marketingInfo表pay_status
        $result = D('Order')->changeOrderPayStatus($orderNumber, 
            I('post.', '', ''), true);
        if ($result['resp_id'] === '0000') {
            tag('payorder_task_finish', $orderNumber);
            echo 'Success';
        }
        $orderInfo = D('Order')->getOrderInfoByNumber($orderNumber);
        $orderDetail = json_decode($orderInfo['detail'], true);
        if ($orderDetail['version'] == '2') {
            $content = '<p>恭喜您已成功订购旺财营销工具打包业务。</p>
      				<p>支付金额：4880.00元</p>
      				<p>使用时间：' .
                 $orderDetail['expiryDate'] .
                 '</p>
      				<p>合同下载：《旺财业务服务合同》<a href="' .
                 U("Home/AccountInfo/xy") . '">点击下载</a></p>
      				<p>旺财使用帮助：<a href="' . U(
                    "Home/Help/helpConter", 
                    array(
                        "type" => 1, 
                        "leftId" => "zxwt")) . '">点此进入帮助中心</a></p>
      				<p>感谢您对旺财的支持与信任！</p>
      				<p>如需帮助请拨打热线：400-882-7770</p>
      				<p>服务时间：工作日 9：00—17：30 </p>';
            D('Message')->send('在线签约消息', $content, $orderInfo['node_id']);
            $this->redirect('Home/MarketActive/index');
        } else {
            $content = '<p>恭喜您已成功订购旺财多宝电商基础版业务。</p>
      				<p>支付金额：6880.00元</p>
      				<p>使用时间：' .
                 $orderDetail['expiryDate'] .
                 '</p>
      				<p>合同下载：《旺财业务服务合同》<a href="' .
                 U("Home/AccountInfo/xy") . '">点击下载</a></p>
      				<p>旺财使用帮助：<a href="' . U(
                    "Home/Help/helpConter", 
                    array(
                        "type" => 1, 
                        "leftId" => "zxwt")) . '">点此进入帮助中心</a></p>
      				<p>感谢您对旺财的支持与信任！</p>
      				<p>如需帮助请拨打热线：400-882-7770</p>
      				<p>服务时间：工作日 9：00—17：30 </p>';
            D('Message')->send('在线签约消息', $content, $orderInfo['node_id']);
            $this->redirect('Home/Index/marketingShow5');
        }
    }

    /**
     * 同步活动订单付款状态通知地址
     */
    public function activityPayStatusReturnUrl() {
        $orderNumber = $this->info['order_id'];
        log_write(
            'ReturnUrl收到活动支付订单号' . $orderNumber . ':' .
                 json_encode(I('post.', '', '')));
        // 更新订单表pay_status和marketingInfo表pay_status
        $result = D('Order')->changeOrderPayStatus($orderNumber, 
            I('post.', '', ''));
        $orderInfo = D('Order')->getOrderInfoByNumber($orderNumber);
        if ($result['resp_id'] === '0000') {
            tag('payorder_task_finish', $orderNumber);
            echo 'Success';
            if ($orderInfo['batch_type'] == '61') {//如果是欧洲杯
                $detail = json_decode($orderInfo['detail'], true);
                if ($detail['serviceConfig']['model'] == C('EUROCUP_ALL_MATCH_ACTIVITY_TYPE_CONFIG')) {//如果购买了2980套餐
                    //其他199的欧洲杯（非免费）订单状态改为取消
                    $orderIdArr = D('Order')->changeEuroCupOrder($orderInfo['node_id']);
                    if (!empty($orderIdArr)) {
                        foreach ($orderIdArr as $orderId) {
                            //通知营帐端修改支付状态
                            D('Order')->YzNoticeBackMoney($orderInfo['node_id'], $orderId);
                        }
                    }
                }
            }
        }
        $param = array(
            'batch_id' => $orderInfo['m_id'], 
            'batch_type' => $orderInfo['batch_type']);
        $this->redirect('LabelAdmin/BindChannel/publishSuccess', $param);
    }

    /**
     * 异步活动订单付款状态通知地址
     */
    public function activityPayStatusNotifyUrl() {
        $orderNumber = $this->info['order_id'];
        log_write(
            'NotifyUrl收到活动支付订单号' . $orderNumber . ':' .
                 json_encode(I('post.', '', '')));
        // 更新订单表pay_status和marketingInfo表pay_status
        $result = D('Order')->changeOrderPayStatus($orderNumber, 
            I('post.', '', ''));
        $orderInfo = D('Order')->getOrderInfoByNumber($orderNumber);
        if ($result['resp_id'] === '0000') {
            tag('payorder_task_finish', $orderNumber);
            echo 'Success';
            if ($orderInfo['batch_type'] == '61') {//如果是欧洲杯
                $detail = json_decode($orderInfo['detail'], true);
                if ($detail['serviceConfig']['model'] == C('EUROCUP_ALL_MATCH_ACTIVITY_TYPE_CONFIG')) {//如果购买了2980套餐
                    //其他199的欧洲杯（非免费）订单状态改为取消
                    $orderIdArr = D('Order')->changeEuroCupOrder($orderInfo['node_id']);
                    if (!empty($orderIdArr)) {
                        foreach ($orderIdArr as $orderId) {
                            D('Order')->YzNoticeBackMoney($orderInfo['node_id'], $orderId);
                        }
                    }
                }
            }
            exit();
        }
    }

    /**
     * [applyPos 申请终端通知地址]
     *
     * @return [type] [description]
     */
    public function applyPos() {
        $orderNumber = $this->info['order_id'];
        $postInfo = I('post.', '', '');
        log_write(
            '收到申请终端-订单号' . $orderNumber . ':' . json_encode($postInfo));
        // 更新订单表pay_status和marketingInfo表pay_status
        $result = D('Order')->changeOrderPayStatus($orderNumber, 
            $postInfo, true);
        if ($result['resp_id'] === '0000') {
            D('Stores')->sendEmail($postInfo);
            tag('payorder_task_finish', $orderNumber);
            echo 'Success';
        }
        $this->redirect('Home/ServicesCenter/myOrder');
    }
    /**
     * [openDM 多米收单通知地址]
     *
     * @return [type] [description]
     */
    public function openDM() {
        $orderNumber = $this->info['order_id'];
        $postInfo = I('post.', '', '');
        log_write(
            '收到多米收单-订单号' . $orderNumber . ':' . json_encode($postInfo));
        // 更新订单表pay_status
        $result = D('Order')->changeOrderPayStatus($orderNumber, 
            $postInfo, true);
        if ($result['resp_id'] === '0000') {
            tag('payorder_task_finish', $orderNumber);
            echo 'Success';
        }
        $this->redirect('Alipay/Index/index');
    }
    /**
     * 卡券商城采购电子券回调
     */
    public function onlineNotifyUrl(){
		    	$orderId = $this->info['order_id'];
		    	$postInfo = I('post.', '', '');
		    	$goodsModel = D('Goods');
		    	$hallModel = D('Hall');
		    	log_write('卡券商城异步-订单号' . $orderId . ':' . json_encode($postInfo));
                $orderInfo = M()->table("tnode_goods_book b")->field('b.book_num,b.status as b_status,b.node_id as b_node_id,b.goods_node_id as goods_node_id,h.batch_img,h.batch_amt,h.zc_market_price,h.zc_pact_price,h.zc_print_price_flag,h.zc_print_text,h.zc_print_control,h.spl_goods_number,g.*')
                    ->join("thall_goods h ON b.hall_id=h.id")
                    ->join("tgoods_info g ON h.goods_id=g.goods_id")
                    ->where("b.order_id='{$orderId}'")
                    ->find();
                if ($orderInfo['b_status'] == '1') {
                    // 更新订单状态
                    $uData = array(
                        'pay_time' => date('YmdHis'), 
                    	'pay_type' => $postInfo['pay_channel'],
                        'status' => '2'
                    );
                    $uResult = M('tnode_goods_book')->where("order_id='{$orderId}'")->save($uData);
                    if ($uResult === false) {
                        log_write('tnode_goods_book表更新失败' . M()->_sql());
                    }
                    //如果是微信红包不需创建活动
                    if($orderInfo['goods_type'] != '22'){
                        // 付款成功赠送旺币
                        $WheelM = D('Wheel');
                        $result = $WheelM->setWb($orderInfo['b_node_id'],$orderInfo['book_num'],date('Ymd', time() + 3600 * 24 * 30), '32'); // 有效期30天
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
                            $smilId = $goodsModel->getSmil($hallModel->getfirestImg($orderInfo['batch_img']),$orderInfo['goods_name'], $orderInfo['b_node_id']);
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
                                'goods_image' => $hallModel->getfirestImg($orderInfo['batch_img']),
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
    
    /**
     * 卡券商城采购电子券回调
     */
    public function onlineReturnUrl(){
    	
    	$orderId = $this->info['order_id'];
    	$postInfo = I('post.', '', '');
    	$goodsModel = D('Goods');
    	$hallModel = D('Hall');
    	log_write('卡券商城同步-订单号' . $orderId . ':' . json_encode($postInfo));
		$orderInfo = M()->table("tnode_goods_book b")->field('b.book_num,b.status as b_status,b.node_id as b_node_id,b.goods_node_id as goods_node_id,h.batch_img,h.id,h.batch_amt,h.zc_market_price,h.zc_pact_price,h.zc_print_price_flag,h.zc_print_text,h.zc_print_control,h.spl_goods_number,g.*')
    	->join("thall_goods h ON b.hall_id=h.id")
    	->join("tgoods_info g ON h.goods_id=g.goods_id")
    	->where("b.order_id='{$orderId}'")
    	->find();
    	if ($orderInfo['b_status'] == '1') {
    		// 更新订单状态
    		$uData = array(
    				'pay_time' => date('YmdHis'),
    				'pay_type' => $postInfo['pay_channel'],
    				'status' => '2'
    		);
    		$uResult = M('tnode_goods_book')->where("order_id='{$orderId}'")->save($uData);
    		if ($uResult === false) {
    			log_write('tnode_goods_book表更新失败' . M()->_sql(), true);
    			$this->error('订单状态更新失败001');
    		}
    		//如果是微信红包不需创建活动
    		if($orderInfo['goods_type'] != '22'){
    			// 付款成功赠送旺币
    			$WheelM = D('Wheel');
    			$result = $WheelM->setWb($orderInfo['b_node_id'],$orderInfo['book_num'],date('Ymd', time() + 3600 * 24 * 30), '32'); // 有效期30天
    			if (! $result) {
    				log_write('旺币赠送失败:' . $orderId);
    			}
    			// 该卡券是否已经购买过(不用考虑是否过期,再付款前也检查)
    			$buyAgainInfo = M('tgoods_info')->field('goods_id')
    			->where("purchase_goods_id='{$orderInfo['goods_id']}' AND node_id='{$orderInfo['b_node_id']}' AND online_verify_flag='{$orderInfo['online_verify_flag']}'")
    			->find();
    			if ($buyAgainInfo) { // 购买过直接增加库存
    				$reduc = $goodsModel->storagenum_reduc($buyAgainInfo['goods_id'],$orderInfo['book_num'] * - 1, '', '14', '卡券采购',$orderInfo['book_num'] * - 1);
    				if (! $reduc) {
    					log_write('库存增加失败:' . $goodsModel->getError());
    					$this->error('库存增加失败:' . $goodsModel->getError());
    				}
    			} else {
    				// 支撑创建活动
    				$smilId = $goodsModel->getSmil($hallModel->getfirestImg($orderInfo['batch_img']),$orderInfo['goods_name'], $orderInfo['b_node_id']);
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
    						'goods_image' => $hallModel->getfirestImg($orderInfo['batch_img']),
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
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
    
}



