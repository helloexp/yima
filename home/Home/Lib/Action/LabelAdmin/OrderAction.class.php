<?php

/**
 * 订单
 *
 * @author lwb 20150806
 */
class OrderAction extends BaseAction {

    /**
     * @var model
     */
    public $model;
    
    /**
     * @var bindChannelModel
     */
    public $BindChannelModel;

    public function _initialize() {
        $this->_authAccessMap = '*'; // 不校验
        parent::_initialize();
        $this->model = D('Order');
        $this->BindChannelModel = D('BindChannel');
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    public function index() {
        $this->display();
    }

    public function payOrder() {
        $orderId = I('orderId');
        $order = D('BindChannel')->getOrderInfo($orderId, $this->node_id); // 获取订单
        $sendCodeFee = I('sendCodeFee', $order['detail']['orderResponse']); // 默认值是原订单保存的"是否需要付发码费"
        $matchType = I('matchType', '1'); // 欧洲杯活动购买的种类，购买的2980多次还是199单次（1：2980种类，2：199种类）
        session('matchType' . $order['m_id'], $matchType);
        session('sendCodeFee' . $order['m_id'], $sendCodeFee);
        //欧洲杯多加的核验卡券
        if ($order['batch_type'] == '61') {
            $assistCode = I('discount_number');//辅助码
            if ($assistCode) {
                //获取欧洲杯活动用来验证优惠券的配置信息
                $verifyCodeConfig = $this->BindChannelModel->getVerifyBarcodeConfigByBatchType(CommonConst::BATCH_TYPE_EUROCUP);
                $nodeId = get_val($verifyCodeConfig, 'node_id', '');
                //检查tbarcode_trace表里是否有未使用的优惠券记录
                $re = $this->BindChannelModel->checkBarCodeTrace($assistCode, $nodeId);
                if (!$re) {
                    $this->error('优惠券错误');
                }
                if ($sendCodeFee == 0) {//如果没有发码费就是免费订单，核验辅助码
                    $re = $this->model->verifyDiscount($assistCode);
                    if ($re['res_code'] != '0000') {
                        $this->error($re['msg']);
                    }
                    //核验成功后更改活动付款状态，然后跳转成功页面
                    $mId = $order['m_id'];
                    $orderDetail = $order['detail'];
                    $orderDetail['assist_code'] = $assistCode;
                    $re = M('tactivity_order')->where(['id' => $orderId])->save(['order_type' => '2', 'detail' => json_encode($orderDetail)]);
                    if (false === $re) {
                        log_write('更改活动订单为免费订单出错，时间：' . date('YmdHis')
                            . '，订单id：' . $orderId . '，辅助码：' . $assistCode . '，活动号：' . $mId);
                    }
                    $re = M('tmarketing_info')->where(['id' => $mId])->save(['pay_status' => '1']);
                    if (false === $re) {
                        log_write('更改活动表支付状态出错，时间：' . date('YmdHis')
                            . '，订单id：' . $orderId . '，辅助码：' . $assistCode . '，活动号：' . $mId);
                    }
                    redirect(U('LabelAdmin/BindChannel/publishSuccess', 
                        array(
                            'batch_id' => $order['m_id'], 
                            'batch_type' => CommonConst::BATCH_TYPE_EUROCUP
                        ))
                    );
                }
                //如果需要付发码费，继续走订单流程，后面生成订单的时候，核验
                session('assistCode' . $order['m_id'], $assistCode);
            }
        }
        
        // 判断有没有付过
        if ($order['pay_status'] == '1') {
            $this->error('订单已支付');
        }
        // 重新生成订单，防止数据错误(后面有,这里不要了)
        // $orderId = D('BindChannel')->createOrder($this->node_id,
        // $order['m_id'], $order['batch_type'], $sendCodeFee);
        $result = $this->model->getOrderInfo($this->node_id, 
            $order['order_number']);
        
        // 计算服务费(原先的是订单金额,暂时改成只计算服务费)
        // $detail = json_decode($result['detail'], true);
        // $serviceAmount = $detail['serviceArr']['num'] *
        // $detail['serviceArr']['config']['price'];
        
        // 测试用
        // $this->success('等待接口中。。。');
        //
        // $amount = $result['amount'];
        // if ($result) {
        // try {
        // $re = $this->model->payOrder(
        // $this->node_id,
        // $result['order_number'],
        // $result['m_id'],
        // $amount,
        // $this->contractId,
        // $this->userInfo['token']);
        // if ($re['StatusCode'] == '0793') {//余额不足时调用营帐现充现扣接口
        // $this->success(
        // array(
        // 'need_pay_more' => true,
        // 'url' => $re['url']
        // ));
        // } elseif ($re['StatusCode'] == '0000') {//扣款成功时，改变订单状态
        // //1表示余额付款
        // $this->model->changeOrderPayStatus($result['order_number'], '1');
        // $accountInfo = $this->model->getAccountInfo($this->node_id);
        // $wbInfo = $this->model->getWbInfo($this->node_id);
        // //账户余额
        // $account = empty($accountInfo) ? '0.00' :
        // $accountInfo['AccountPrice'];
        // //旺币
        // $wb = $wbInfo['wbOver'];
        // //充值链接
        // $addMoneyUrl = C('YZ_RECHARGE_URL')
        // . '&node_id=' . $this->node_id
        // . '&name=' . $this->user_name
        // . '&token=' . $this->userInfo['token'];
        // $this->success(
        // array(
        // 'need_pay_more' => false,
        // 'url' => U('LabelAdmin/BindChannel/publishSuccess',
        // array('batch_id' => $result['m_id'], 'batch_type' =>
        // $result['batch_type'])),
        // 'account' => $account,
        // 'wb' => $wb,
        // 'addMoneyUrl' => $addMoneyUrl,
        // 'deductAmount' => $amount
        // ));
        // }
        // } catch (Exception $e) {
        // $this->error($e->getMessage());
        // }
        // } else {
        // $this->error('订单不存在');
        // }
        if ($result) {
            // 跳转到收银台
            echo $this->model->getCashierUrl($this->node_id, $orderId);
            exit();
        } else {
            $this->error('订单不存在');
        }
    }

    public function couponDetail() {
        $couponType = I('couponType');
        $orderId = I('orderId');
        $couponDetailArr = $this->model->getOrderCouponDetail($this->node_id, 
            $couponType, $orderId);
        $typeName = '';
        switch ($couponType) {
            case CommonConst::COUPON_TYPE_SELFCREATE:
                $typeName = '自建卡券';
                break;
            case CommonConst::COUPON_TYPE_BUY:
                $typeName = '采购卡券';
                break;
            case CommonConst::COUPON_TYPE_HB:
                $typeName = '红包';
                break;
            case CommonConst::COUPON_TYPE_WX_CARD:
                $typeName = '微信卡券';
                break;
        }
        try {
            $couponNum = $this->model->getCouponTypeNum($this->node_id, 
                $couponType, $orderId);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->assign('couponNum', $couponNum);
        $this->assign('couponType', $couponType);
        $this->assign('typeName', $typeName);
        $this->assign('couponDetailArr', $couponDetailArr);
        $goodsHelp = U('Home/Help/helpConter', 
            array(
                'type' => 7, 
                'left' => 'dzq'));
        $this->assign('goodsHelp', $goodsHelp);
        $this->display();
    }
    
    public function isInPay() {
        $mId = I('m_id');
        // 如果状态是付费中(不能让他修改时间);
        $isInPay = D('Order')->isInPay($this->node_id, $mId);
        if ($isInPay) {
            $this->error('订单已生成，活动时间不可更改。如需更改时间，请先到<a target="_blank" href="' .
                U('Home/ServicesCenter/myOrder') . '">我的订单</a>中取消订单。');
        }
        $this->success();
    }
}