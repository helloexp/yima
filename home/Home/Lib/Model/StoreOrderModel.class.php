<?php

/**
 * 旺财小店订单相关 model
 *
 * @author : John zeng<zengc@imageco.com.cn> Date: 2016/01/06
 */
class StoreOrderModel extends Model {
    protected $tableName = '__NONE__';
    // 用户手机信息
    public $groupPhone = '';
    // 错误信息
    public $error = '';

    public $orderModel = '';
    // 旺分销
    public $wfxService = '';
    
    // 构造函数
    public function __construct() {
        $this->groupPhone = session('groupPhone');
        $this->orderModel = M("ttg_order_info");
        // 旺分销
        $this->wfxService = D('Wfx', 'Service');
    }
    /**
     * Description of SkuService 获取验证后的订单相关信息
     *
     * @param string $orderId 订单ID
     * @return array 订单数据信息
     */
    public function getVerifyOrderInfo($orderId, $payChannel, $status = false){
        // 取得订单信息
        $orderModel = M('ttg_order_info');
        $orderInfo = $orderModel->where(
            array(
                "order_id" => $orderId))->find();
        if (! $orderInfo) {
            // 没有订单信息返回
            log_write(self::getPayChannel($payChannel) . "支付，未找到订单信息. 订单号：{$orderId}");
            self::showMsg('未找到订单信息', 0, $orderInfo['batch_channel_id'], 
                $orderInfo['order_id'], $orderInfo['order_type'], 
                $orderInfo['node_id']);
        }
        
        // 订单状态，是否成功
        if (true === $status) {
            // Write to log
            log_write(self::getPayChannel($payChannel) . "支付，订单号 ：" . $orderId . " 支付成功!");
            if ($orderInfo['is_gift'] == '1') {
                $giftInfo = M('ttg_order_gift')->where(
                    array(
                        'order_id' => $orderInfo['order_id']))->find();
                if ($giftInfo['gift_type'] == '1') {
                    // 微信送礼
                    $jump_url = C('CURRENT_DOMAIN') .
                         'index.php?g=Label&m=MyGift&a=get_gift&show_wx=1&order_id=' .
                         $orderInfo['order_id'];
                } else {
                    // 短信送礼
                    $jump_url = C('CURRENT_DOMAIN') .
                         'index.php?g=Label&m=MyGift&a=send_ok&order_id=' .
                         $orderInfo['order_id'];
                }
                redirect($jump_url);
            } else {
                self::showMsg('恭喜您，您的订单支付成功并生效！', 1, 
                    $orderInfo['batch_channel_id'], $orderInfo['order_id'], 
                    $orderInfo['order_type'], $orderInfo['node_id'], null, $sourceInfo);
            }
        } else {
            // 支付失败
            log_write(self::getPayChannel($payChannel) . "支付，订单号 ：" . $orderId . " 支付失败!");
            self::showMsg('很抱歉，由于意外情况您的订单支付失败，请前往确认！', 2, 
                $orderInfo['batch_channel_id'], $orderInfo['order_id'], 
                $orderInfo['order_type'], $orderInfo['node_id']);
            exit();
        }
    } 
    /**
     * Description of SkuService 获取订单相关信息
     *
     * @param string $orderId 订单ID
     * @return array 订单数据信息
     */
    public function getOrderInfo($orderId) {
        $orderInfo = $this->orderModel->alias("o")->field(
            'o.*,g.name as group_goods_name,g.goods_num,g.sell_num')->join(
            "tmarketing_info g ON o.batch_no=g.id")->where(
            "o.order_id='{$orderId}' AND o.order_phone={$this->groupPhone}")->find();  
            
        if (! $orderInfo || $orderInfo['pay_status'] != 1) {
            $this->error = "订单信息有误";
            return false;
        }
        
        // 判断活动是否已停用
        if ($orderInfo['order_type'] == '0') {
            $marketInfo = M('tmarketing_info')->where(
                array(
                    'id' => $orderInfo['batch_no']))->find();
            if (! $marketInfo) {
                $this->error = "商品信息有误";
                return false;
            }
            if ($marketInfo['status'] != '1' ||
                 $marketInfo['end_time'] <= date('YmdHis')) {
                $this->error = "商品已过期或已停止出售";
                return false;
            }
        }
        if ($orderInfo['order_type'] == '2') {

            $count = M()->table('ttg_order_info_ex t')->join(
                'tbatch_info b ON b.id=t.b_id')->where(
                "t.order_id='" . $orderInfo['order_id'] .
                     "' AND (b.status!=0 or b.end_time <='" . date('YmdHis') .
                     "')")->count();
            if ($count > 0) {
                $this->error = "商品已过期或已下架";
                return false;
            }
        }
        
        return $orderInfo;
    }

    /**
     * Description of SkuService 订单支付信息验证处理
     *
     * @param string $orderId 订单ID
     * @param string $payChannel 支付通道
     * @param string $transactionId 交易号
     * @param bool $sendNotice 是否发码
     * @return array 订单数据信息
     */
    public function verifyOrderInfo($orderId, $payChannel, $transactionId, $sendNotice = true) {
        M()->startTrans();
        // 取得大订单信息
        $orderInfo = $this->orderModel->alias("o")->join(
            "ttg_order_info_ex t ON t.order_id = o.order_id")
            ->join('tbatch_info b ON b.id=t.b_id')
            ->field("o.*, t.ecshop_sku_desc,b.batch_short_name,b.use_rule,b.print_text")
            ->where(array(
            "o.order_id" => $orderId))
            ->lock(true)
            ->find();

        if (! $orderInfo) {
            // 没有订单信息返回
            M()->rollback();
            $this->error = self::getPayChannel($payChannel) .
                 "支付，未找到订单信息. 订单号：{$orderId}";
            return false;
        }
        if ($orderInfo['pay_status'] == '2') {
            $this->error = "订单已支付，无需重复支付. 订单号：{$orderId}";
            return false;
        }
        // 生成发码内容
        $textInfo = array();
        $textInfo['use_rule'] = $orderInfo['batch_short_name'];
        $textInfo['print_text'] = $orderInfo['print_text'];
        if (isset($orderInfo['ecshop_sku_desc'])) {
            $textInfo['use_rule'] .= '[' . $orderInfo['ecshop_sku_desc'] . ']';
            $textInfo['print_text'] .= $orderInfo['ecshop_sku_desc'];
        }
        $textInfo['use_rule'] .= $orderInfo['use_rule'];
        if(false === $sendNotice){
            $textInfo['fb_cmpay_flag'] = 'yes';
        }

        // 未付款
        if ($orderInfo['pay_status'] == '1') {
            $profitStatus = '0'; // 取消分润 0为取消分润 1为需要分润
                                 // 汇率信息
            $feeInfo = D('node')->getNodeAccountFeeInfo($orderInfo['node_id'],
                $orderInfo['order_amt'], $payChannel);
            log_write('返回汇率信息:' . json_encode($feeInfo));
            // 更新大订单状态
            $treeCutData = array(
                'pay_status' => '2',
                'pay_channel' => $payChannel,
                'order_status' => '0',
                'profit_status' => $profitStatus,  // 吴刚砍树v0.5和m1不需要自动分润
                'update_time' => date('YmdHis'),
                'pay_time' => date('YmdHis'),  // 支付时间
                'pay_seq' => $transactionId,
                'receive_amount' => isset($feeInfo['receive_amount']) ? $feeInfo['receive_amount'] : 0,  // 实收金额（交易金额-费率金额）
                'fee_amt' => isset($feeInfo['fee_amt']) ? $feeInfo['fee_amt'] : $orderInfo['order_amt'],  // 支付费率扣费金额
                'fee_rate' => isset($feeInfo['fee_rate']) ? $feeInfo['fee_rate'] : C('FEERATE')); // 支付费率

            $result = $this->orderModel->where(
                array(
                    "order_id" => $orderInfo['order_id']))->save($treeCutData);
            log_write('pay_Info:' . json_encode($feeInfo) . ' SQLINFO:' . M()->_sql());
            $srt = json_encode($treeCutData);
            log_write("订单情况：{$srt}", 'FAIL', 'BOUNSPAY');
            if ($result === false) {
                M()->rollback();
                $this->error = "[fail]" . self::getPayChannel($payChannel) .
                     "支付，订单状态更新失败. 订单号：{$orderId}";
                return false;
            }
            // 吴刚砍树活动twx_cuttree_info订单状态更新
            $buyStatus = M('twx_cuttree_info')->where(
                "order_id={$orderInfo['order_id']}")->getField('buy_status');
            if (! empty($buyStatus) && $buyStatus == '1') {
                $res = M('twx_cuttree_info')->where(
                    "order_id={$orderInfo['order_id']}")->save(
                    array(
                        'buy_status' => '2'));
                if ($res === false) {
                    M()->rollback();
                    $this->error = "[fail]" . self::getPayChannel($payChannel) .
                         "支付，twx_cuttree_info订单状态更新失败. 订单号：{$orderId}";
                    return false;
                }
                // 吴刚砍树v0.5和m1不需要自动分润
                $wcv = get_wc_version($orderInfo['node_id']);
                if ($wcv == 'v0.5' || $this->hasPayModule('m1')) {
                    $profitStatus = '0';
                }
            }
            // 获取channelId
            $channelId = M('tbatch_channel')->where(
                array(
                    'id' => $orderInfo['batch_channel_id']))->getField(
                'channel_id');

            // 判断是否为订购单子
            if ($orderInfo['other_type'] == '1') {
                $bookOrderinfo = M('ttg_order_info_ex')->where(
                    array(
                        'order_id' => $orderId))->find();
                $skuDesc = explode('/', $bookOrderinfo['ecshop_sku_desc']);
                $time = (int) array_pop($skuDesc);
                $time = $time * $orderInfo['buy_num'];
                $bookOrderData = array();
                $bookOrderData['node_id'] = $orderInfo['node_id'];
                $bookOrderData['b_id'] = $bookOrderinfo['b_id'];
                $bookOrderData['order_id'] = $orderInfo['order_id'];
                $bookOrderData['receiver_name'] = $orderInfo['receiver_name'];
                $bookOrderData['receiver_citycode'] = $orderInfo['receiver_citycode'];
                $bookOrderData['receiver_addr'] = $orderInfo['receiver_addr'];
                $bookOrderData['receiver_tel'] = $orderInfo['receiver_phone'];
                $bookOrderData['delivery_status'] = '1';
                $bookOrderData['extract_status'] = '0';
                $bookOrderData['goods_name'] = $orderInfo['batch_short_name'] .
                     '/' . implode('/', $skuDesc);

                $cycleI = 1;
                $singlePrice = (int) $orderInfo['order_amt'] / $time;
                $bookOrderConfig = M()->table("tbatch_info tbi")->join(
                    'tgoods_info tgi ON tbi.goods_id = tgi.goods_id')->where(
                    array(
                        'tbi.id' => $bookOrderinfo['b_id']))->getfield(
                    'tgi.config_data');
                $bookOrderConfig = json_decode($bookOrderConfig, TRUE);
                $dispatchingDate = '';
                $skuService = D('Sku', 'Service');
                while ($cycleI <= $time) {
                    if ($cycleI == $time) {
                        $bookOrderData['order_cash'] = ($orderInfo['order_amt'] -
                             $singlePrice * ($time - 1));
                    } else {
                        $bookOrderData['order_cash'] = $singlePrice;
                    }

                    $dispatchingDate = $skuService->getBookOrderDeliveryDate(
                        $bookOrderConfig['cycle']['cycle_type'],
                        $orderInfo['book_delivery_date'], $dispatchingDate);

                    $bookOrderData['dispatching_date'] = $dispatchingDate;
                    $ttobcId = M('ttg_order_by_cycle')->add($bookOrderData);
                    if (! $ttobcId) {
                        M()->rollback();
                        break;
                    }
                    $cycleI ++;
                }
            }
            if ($orderInfo['order_type'] == 0) { // 闪购/码上买订单
                                                 // 获取商品是自提还是配送 0 自提发码
                                                 // 1物流不发码
                                                 // $marketInfo =
                                                 // M('tmarketing_info')->where(array('id'=>$orderInfo['batch_no']))->find();
                                                 // $delivery_flag =
                                                 // $marketInfo['defined_one_name'];
                if ($orderInfo['is_gift'] != '1') {
                    if ($orderInfo['receiver_type'] == '0') { // 发码
                        for ($i = 1; $i <= $orderInfo['buy_num']; $i ++) {
                            D('SalePro', 'Service')->sendCode(
                                $orderInfo['order_id'], $textInfo);
                        }
                    }
                } else {
                    // 送礼短信发送
                    $sendInfo = array(
                        'textInfo' => $textInfo);
                    D('SalePro', 'Service')->checkGiftInfo(
                        $orderInfo['order_id'], $sendInfo, 3);
                }
            } elseif ($orderInfo['order_type'] == 2) { // 旺财小店订单
                                                       // 获取子订单列表
                $orderListInfo = M('ttg_order_info_ex')
                        ->where(array('order_id' => $orderInfo['order_id']))
                        ->select();
                if (! $orderListInfo) {
                    M()->rollback();
                    $this->error = "[fail]" . self::getPayChannel($payChannel) .
                         "支付，获取子订单列表失败. 订单号：{$orderId}";
                    return false;
                }
                // 循环处理子订单列表
                foreach ($orderListInfo as $v) {
                    $ecgoodsInfo = M()->table('tecshop_goods_ex g')
                        ->field('g.*,b.batch_no, b.batch_short_name,b.use_rule,b.print_text')
                        ->join('tbatch_info b ON b.id=g.b_id')
                        ->where(array('g.b_id' => $v['b_id']))
                        ->find();
                    //更新锁定商品信息
                    $lockInfo = M('tbatch_info')->where(['id'=>$v['b_id']])->setDec('lock_num', $v['goods_num']);
                    if(!$lockInfo){
                        M()->rollback();
                        $this->error = "[fail]" . self::getPayChannel($payChannel) . "支付，订单解锁失败. 订单号：{$orderId}";
                        return false;
                    }
                    // 重新生成发码信息
                    $textInfo = array();
                    $textInfo['use_rule'] = $ecgoodsInfo['batch_short_name'];
                    $textInfo['print_text'] = $ecgoodsInfo['print_text'];
                    if (isset($v['ecshop_sku_desc'])) {
                        $textInfo['use_rule'] .= '[' . $v['ecshop_sku_desc'] .']';
                        $textInfo['print_text'] .= $v['ecshop_sku_desc'];
                    }
                    $textInfo['use_rule'] .= $ecgoodsInfo['use_rule'];
                    if(false === $sendNotice){
                        $textInfo['fb_cmpay_flag'] = 'yes';
                    }
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
                            $result = D('SalePro', 'Service')->checkGiftInfo( $orderInfo['order_id'], $sendInfo);
                            if(false === $result){
                                M()->rollback();
                                $this->error = "[fail]订单发码失败. 订单号：{$orderId}";
                                return false;
                            }
                            var_dump($result);die;
                        } else {
                            for ($i = 1; $i <= $v['goods_num']; $i ++) {
                                // 发码sendCode2($orderId,$orderType,$nodeId,$issBatchNo,$phone,$bId,$mId)
                                log_write("发码开始2：{$orderId}", 'FAIL',
                                    'BOUNSPAY');
                                $result = D('SalePro', 'Service')->sendCode2(
                                    $orderInfo['order_id'], '2',
                                    $orderInfo['node_id'],
                                    $ecgoodsInfo['batch_no'],
                                    $orderInfo['receiver_phone'],
                                    $ecgoodsInfo['b_id'], $ecgoodsInfo['m_id'],
                                    $channelId, '', $textInfo);
                                if(false === $result){
                                    M()->rollback();
                                    $this->error = "[fail]订单发码失败. 订单号：{$orderId}";
                                    return false;
                                }
                            }
                        }
                    }
                }
            }
            // DF非标。。。
            if ($orderInfo['node_id'] == C('df.node_id')) {
                $df = D('Df', 'Service');
                $df->pay_notify($orderInfo['order_id'],
                    $orderInfo['order_phone'], $orderInfo['order_amt']);
            }

            // 给商户通知弹框
            $result = self::sendNotice2($orderInfo['node_id'], $orderInfo['order_id'], $orderInfo['order_amt']);
            if(false === $result){
                M()->rollback();
                $this->error = "[fail]" . self::getPayChannel($payChannel) ."支付，商户通知弹框失败. 订单号：{$orderId}";
                return false;
            }
            // 分销触发
            if ($orderInfo['saler_id']) {
                $wfxService = D('Wfx', 'Service');
                $wfxService->return_bonus($orderInfo['node_id'], $orderInfo['order_id'], $orderInfo['saler_id']);
                //美惠非标处理，多订单，需记录子订单的会员价
                if($orderInfo['node_id'] == C('meihui.node_id')){
                    D("meihui")->getGoodsMemberPrice($orderId,$orderInfo['saler_id'],$orderInfo['node_id']);
                }
            }
            // 判断是否纯字体订单 是则更新配送状态为凭证自提
            if ($orderInfo['receiver_type'] == '0') { // 发码
                $result = $this->orderModel->where(
                    array(
                        "order_id" => $orderInfo['order_id']))->save(
                    array(
                        'delivery_status' => '4'));
                if ($result === false) {
                    M()->rollback();
                    $this->error = "[fail]" . self::getPayChannel($payChannel) .
                         "支付，自提订单配送状态更新失败. 订单号：{$orderId}";
                    return false;
                }
            }
        }
        log_write("支付咯：{$orderInfo['order_id']}", 'FAIL', 'BOUNSPAY');
        M()->commit();
        // 油豆信息
        D('MemberInstall')->orderPay($orderInfo['order_id'], $orderInfo['node_id'], $orderInfo['batch_channel_id']);
        return true;
    }

    /**
     * Description of SkuService 小店，单品订单处理
     *
     * @param string $orderType 订单类型
     * @param array $orderInfo 订单信息
     * @param string $transactionId 交易号
     * @return array 订单数据信息
     */
    protected function handleOrder($orderType, $orderInfo) {
        // 闪购/码上买订单
        $skuId = isset($orderInfo['ecshop_sku_desc']) ? (int) $orderInfo['ecshop_sku_desc'] : 0;
        if (0 == $orderType) {
            return self::handleInfo($orderInfo['batch_no'], $orderType, 
                $orderInfo['order_id'], $orderInfo['buy_num'], $skuId, 
                $orderInfo['pay_channel']);
        } else if (2 == $orderType) {
            $orderListExInfo = M('ttg_order_info_ex')->where(
                array(
                    "order_id" => $orderInfo['order_id']))->select();
            if (! $orderListExInfo) {
                $this->error = self::getPayChannel($orderInfo['pay_channel']) .
                     "支付，未找到已取消订单的库存信息. 订单号：{$orderInfo['order_id']}";
                return false;
            }
            // 循环处理 更新库存
            foreach ($orderListExInfo as $v) {
                return self::handleInfo($v['b_id'], $orderType, 
                    $orderInfo['order_id'], $v['goods_num'], $skuId, 
                    $orderInfo['pay_channel']);
            }
        }
    }

    /**
     * Description of SkuService 信息处理
     *
     * @param string $orderType 订单类型
     * @param array $orderInfo 订单信息
     * @param string $transactionId 交易号
     * @return array 订单数据信息
     */
    protected function handleInfo($bId, $type, $orderId, $goodsNum, $skuId, 
        $payChannel) {
        if (0 == $type) {
            $map = array(
                'id' => $bId);
        } else {
            $map = array(
                'm_id' => $bId);
        }
        // 获取库存数据
        $batchInfo = M('tbatch_info')->where($map)->find();
        if (! $batchInfo) {
            $this->error = self::getPayChannel($payChannel) .
                 "支付，未找到已取消订单的库存信息. 订单号：{$orderId}";
            return false;
        }
        // 非不限库存
        if ($batchInfo['storage_num'] != - 1) {
            // 处理SKU商品 START
            if ($skuId > 0) {
                // 创建sku信息
                $skuObj = D('Sku', 'Service');
                $result = $skuObj->returnPayGoodsNum($skuId, $goodsNum, 
                    $orderId);
                if (false === $result) {
                    $this->error = "SKU商品支付失败，库存扣减失败. 订单号：{$orderId}";
                    return false;
                }
            }
            // 处理SKU商品 END
            if ($batchInfo['remain_num'] > $goodsNum) {
                // 库存够 减少库存
                $ret = M('tbatch_info')->where($map)->setDec('remain_num', 
                    $goodsNum);
                if ($ret === false) {
                    $this->error = self::getPayChannel($payChannel) .
                         "支付，已取消订单的库存扣减失败. 订单号：{$orderId}";
                    return false;
                }
            } else {
                // 库存不足
                $ret = $this->orderModel->where(
                    array(
                        "order_id" => $orderId))->save(
                    array(
                        'memo' => '已取消订单收到支付通知，但库存不足'));
                log_write("已取消订单收到支付通知，但库存不足, 订单号：{$orderId}");
                return true;
            }
        }
    }

    
    
    /**
     * Description of SkuService 发送短信通知信息
     *
     * @param string $nodeId 商户唯一标识
     * @param array $orderId 订单ID
     * @param string $orderAmt 订单金额
     * @return
     *
     */
    protected function sendNotice2($nodeId, $orderId, $orderAmt) {
        // 插入商户订单通知表torder_notice
        $message = "您有支付成功订单，订单号：{$orderId}，金额￥{$orderAmt}元";
        
        $data = array(
            'message_text' => $message, 
            'node_id' => $nodeId, 
            'status' => '0', 
            'add_time' => date('YmdHis'));
        
        $result = M('torder_notice')->add($data);
        if(!$result){
            return false;
        }
        // 进统一消息提醒
        $where = array(
            'node_id' => $nodeId, 
            'message_type' => '4');
        add_msgstat($where, 1);
        return true;
    }
    
    /**
     * 订单非标博雅处理
     *
     * @param  string 
     *
     * @return string
     * @author john_zeng
     */
    public  function orderToFbBoya($order_phone) {
        $parm1 = '';
        $user_info = session('store_mem_id' . $this->nodeId);
        $wxUserInfo = session('wxUserInfo');
        $fbBoyaService = D('FbBoya', 'Service');
        if ($wxUserInfo['openid']){
            $ret_arr = $fbBoyaService->getPromotionByOpenid($wxUserInfo['openid'], $this->nodeId,$user_info['user_id']);
        }else{
            $ret_arr = $fbBoyaService->getPromotionByPhone($order_phone, $this->nodeId, $user_info['user_id']);
        }    
        if ($ret_arr['code'] == '0000'){
            $parm1 = $ret_arr['echopPromotionMemberID'];
        }
        return $parm1;
    }
    
    /**
     * 订单保存接口
     *
     * @param  string 
     *
     * @return string
     * @author john_zeng
     */
    public  function orderToSave() {
        M()->startTrans(); // 起事务
    }
    
    
     /**
     * 订单库存处理
     *
     * @param  array $bInfo 商品信息 
     * @param  string $bId 商品ID
     * @param  string $row 订单信息
     * @param  string $isSku 是否规格商品
     *
     * @return string
     * @author john_zeng
     */
     public function orderStorageDec($bInfo, $bId, $row, $isSku = '0') {
        // 判断是否减库存
        $model_ = M('tbatch_info');
        $map_ = array('id' => $bId);

        if ($bInfo['storage_num'] > 0) {
            //判断库存数
            if(($bInfo['remain_num'] - $row['goods_num']) < 0){
                $this->error = "库存不足！";
                return false;
            }
            $query_arr = $model_->where($map_)->setDec('remain_num', $row['goods_num']);
            if(!$query_arr){
                $this->error = "库存扣除失败！";
                return false;
            }
            //锁定商品
            $result = $model_->where($map_)->setInc('lock_num', $row['goods_num']);
            if(!$result){
                $this->error = "锁定商品失败！";
                return false;
            }
        }
        // 判断是否是sku商品
        if ('1' === $isSku) {
            // 判断是否减库存
            unset($model_, $map_);
            $model_ = M('tecshop_goods_sku');
            $map_ = array('id' => $row['ecshop_sku_id']);

            $bInfo = $model_->field('storage_num,remain_num')
                ->where($map_)
                ->find();

            //判断库存数
            if ($bInfo['storage_num'] > 0) {
                if(($bInfo['remain_num'] - $row['goods_num']) < 0){
                    $this->error = "规格库存不足！";
                    return false;
                }
                $query_arr = $model_->where($map_)->setDec('remain_num', $row['goods_num']);
                if(!$query_arr){
                    $this->error = "规格库存扣除失败！";
                    return false;
                }
            }
        }
        return true;
    }
    
    
    /**
     * 旺分销接口
     *
     * @param  string  $order_id  订单ID
     * @param  array  $goods  商品信息
     * @param  string  $order_phone  手机号码
     * @param  array  $saler_sess  旺分销信息
     * @param  string  $saler_phone  旺分销手机信息
     *
     * @return string
     * @author john_zeng
     */
    public  function isSalersess($order_id, $goods, $order_phone, $saler_sess, $saler_phone) {
        $saler_id = '';
        log_write($order_id.'saler_sess:'.print_r($saler_sess,true));
        $errcode = - 1;
        foreach ($goods as $v) {
            $batch_id = M('tbatch_info')->where(array('id' => $v))->getField('m_id');
            $salerInfo = $this->wfxService->get_bind_saler($this->nodeId, $order_phone, $batch_id, $saler_sess['saler_id']);
            if ($this->wfxService->errcode == 0){
                $errcode = 0;
            }    
            if ($errcode == 0 && $salerInfo){
                break;
            }    
        }
        log_write($order_id.'salerInfo:'.print_r($salerInfo,true));
        if ($errcode == 0) {
            if ($salerInfo){
                $saler_id = $salerInfo['id'];
            }else {
                // 根据前端传入的手机号  
                if ($saler_phone) {
                    $sInfo = $this->wfxService->get_saler_info_by_phone($this->node_id, $saler_phone);
                    if ($sInfo){
                        $saler_id = $sInfo['id'];
                    }    
                }
            }
        }
        return $saler_id;
    }
    
    /**
     * 订单支付接口
     *
     * @param  string $order_id 订单号
     * @param  string $order_amt 订单金额
     * @param  string $pay_channel 通道号
     * @param  string $nodeShortName 商品短标签
     * @param  string $other 另类类型
     * @param  string $wx_flag  是否微信
     * @param  string $sessionId  渠道ID
     *
     * @return string
     * @author john_zeng
     */
    public  function orderToPay($order_id, $order_amt, $pay_channel, $wx_flag = '', $sessionId = '', $id = '', $nodeShortName = '', $other = '') {
        // 判断是否免支付订单 红包抵扣
        if (0 == $order_amt) {
            $saleModel = D('SalePro', 'Service');
            $result = $saleModel->OrderPay($order_id, $pay_channel);
            // $result = 'success';
            if ($result == 'success') {
                // 油豆信息
                D('MemberInstall')->orderPay($order_id, $this->nodeId, $id);
                A('Label/PayMent')->showMsgInfo('购买成功', 1, $id,$order_id, $this->nodeId, $nodeShortName,$other);
            } else {
                A('Label/PayMent')->showMsgInfo('购买失败', 0, $id,$order_id, $this->nodeId, $nodeShortName, $other);
            }
        } else {
            // 去支付
            if ($pay_channel == '2') {
                // 去支付
                $payModel = A('Label/PayUnion');
                $payModel->OrderPay($order_id);
            } elseif ($pay_channel == '1') {
                if ($wx_flag == 1) {
                    // 微信中用支付宝支付则跳转到中转页面
                    redirect(
                        U('Label/PayConfirm/index', 
                            array(
                                'order_id' => $order_id, 
                                'id' => $sessionId)));
                } else {
                    $payModel = A('Label/PayMent');
                    $payModel->OrderPay($order_id);
                }
            } elseif ($pay_channel == '3') {
                // 微信支付
                $payModel = A('Label/PayWeixin');
                $payModel->goAuthorize($order_id);
                // $payModel->OrderPay($order_id);
            } elseif( $pay_channel == '5' ){
                $payModel = A( 'Label/PayYinlian' );
                $payModel-> OrderPay( $order_id );
            } elseif ($pay_channel == '4') {
                // 货到付款
                redirect(
                    U('Label/PayDelivery/OrderPay', 
                        array(
                            'order_id' => $order_id)));
            }elseif($pay_channel == '6'){    
                //非标和包
                $payModel = A('Label/PayCM');
                $payModel->OrderPay($order_id);
        }
        exit();
        }
    }
    
    /**
     * 订单支付接口
     *
     * @param  string $order_phone 用户登录手机号码
     * @param  string $receive_phone 收货手机号码
     * @param  string $address  地址信息
     * @param  string $receive_name 收货人信息
     * @param  string $addsPath 地址详细信息
     *
     * @return string
     * @author john_zeng
     */
    public  function updateOrderAddress($order_phone, $receive_phone, $address, $receive_name, $addsPath) {
        $map_ = array(
            'user_phone' => $order_phone, 
            'phone_no' => trim($receive_phone), 
            'address' => trim($address));
        // 'path' => trim($addsPath),

        $bInfo = M('tphone_address')->field('id')
            ->where($map_)
            ->find();
        if (!$bInfo) {
            $wurow['user_phone'] = $order_phone;
            $wurow['user_name'] = trim($receive_name);
            $wurow['phone_no'] = trim($receive_phone);
            $wurow['address'] = trim($address);
            $wurow['add_time'] = date('YmdHis');
            $wurow['last_use_time'] = date('YmdHis');
            $wurow['path'] = trim($addsPath);
            $res = M('tphone_address')->add($wurow);
        } else {
            $wurow['last_use_time'] = date('YmdHis');
            $wurow['path'] = trim($addsPath);
            $res = M('tphone_address')->where("id='" . $bInfo['id'] . "'")->save($wurow);
        }
        return $res;
    }
    
    /**
     * 订单保存接口
     *
     * @param  string $pcode 市编码
     * @param  string $ccode 城市编码
     * @param  string $totalAmt 订单金额
     *
     * @return string
     * @author john_zeng
     */
    public  function getShippingFee($pcode, $ccode, $totalAmt) {
        $provinceCode = isset($pcode) ? $pcode : 0;
        $cityCode = $provinceCode . (isset($ccode) ? $ccode : 0);
        $cityExpressModel = D('CityExpressShipping');
        $shippingFee = $cityExpressModel->getShippingFee($this->nodeId, $totalAmt, $cityCode);
        return $shippingFee;
    }
    /**
     * Description of SkuService 返回支付通道名称
     *
     * @param  string $payChannel   //支付通道
     *
     * @return string
     * @author john_zeng
     */
    protected  function getPayChannel($payChannel) {
        $info = 'PAYCHANNEL.' . $payChannel;
        return C($info);
    }
    
    /**
     * Description of SkuService 获取订单地址信息
     *
     * @param  array $orderInfo   //支付通道
     *
     * @return array
     * @author john_zeng
     */
    public function getOrderAdress($orderInfo) {
        if ($orderInfo['receiver_citycode']) {
            $cityInfo = M('tcity_code')
                    ->where(array('path' => $orderInfo['receiver_citycode']))
                    ->field('province_code, city_code, town_code, province, city, town')
                    ->find();
            $orderInfo['province_code'] = $cityInfo['province_code'];
            $orderInfo['city_code'] = $cityInfo['city_code'];
            $orderInfo['town_code'] = $cityInfo['town_code'];
            $orderInfo['province'] = $cityInfo['province'];
            $orderInfo['city'] = $cityInfo['city'];
            $orderInfo['town'] = $cityInfo['town'];
        }
        return $orderInfo;
    }
    

    // 输出信息页面
    public function showMsg($info, $status, $id, $order_id, $order_type = null, $node_id = null, $node_short_name='', $sourceInfo = '') {
        $node_short_name = '';
        A('Label/PayMent')->showMsgInfo($info, $status, $id, $order_id, $node_id, null, $order_type, $sourceInfo);
    }
    /**
     * Description of SkuService 返回错误
     *
     * @param
     *
     * @return error
     * @author john_zeng
     */
    public function getError() {
        return $this->error;
    }
}