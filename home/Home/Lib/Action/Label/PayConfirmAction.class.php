<?php

/*
 * 微信中用支付宝支付的跳转中立页面 在浏览器中打开后直接跳支付页面
 */
class PayConfirmAction extends MyBaseAction {

    public function _initialize() {
        parent::_initialize();
    }
    
    // 入口
    public function index() {
        $order_id = I('order_id', null);
        if (! $order_id)
            $this->showMsg('数据错误');
        $orderInfo = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))->find();
        if (! $orderInfo)
            $this->showMsg('订单数据错误');
        if ($orderInfo['order_status'] != '0')
            $this->showMsg('订单已过期，请重新下单');
        if ($orderInfo['pay_status'] != '1')
            $this->showMsg('订单非未支付状态，无法支付，请重新下单');
            
            // 非微信中打开
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            // 浏览器中补充session
            if (! session("?groupPhone"))
                session("groupPhone", $orderInfo['order_phone']);
            if (! session("?id"))
                session("id", $orderInfo['batch_channel_id']);
                // 直接跳转支付页面
            $payModel = A('Label/PayMent');
            $payModel->OrderPay($order_id);
        }
        // 获取可支付通道
        $hasPayChannel = 0;
        $payChannelInfo = M('tnode_account')->where(
            array(
                'node_id' => $this->node_id))->getField('account_type,status');
        foreach ($payChannelInfo as $v => $k) {
            if ($k == 1)
                $hasPayChannel = 1;
        }
        $this->assign('hasPayChannel', $hasPayChannel);
        $this->assign('payChannelInfo', $payChannelInfo);
        $this->assign('orderInfo', $orderInfo);
        $this->display();
    }
    
    // 提交付款
    public function save() {
        $order_id = I('order_id', null);
        if (! $order_id)
            $this->showMsg('数据错误');
        $orderInfo = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))->find();
        if (! $orderInfo)
            $this->showMsg('订单数据错误');
        if ($orderInfo['order_status'] != '0')
            $this->showMsg('订单已过期，请重新下单');
        if ($orderInfo['pay_status'] != '1')
            $this->showMsg('订单非未支付状态，无法支付，请重新下单');
        $payment = I('payment_type', 0, 'intval');
        if (! in_array($payment, 
            array(
                2, 
                3)))
            $this->showMsg('支付方式错误');
            // 更新原订单的支付方式
        $ret = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))->save(
            array(
                'pay_channel' => $payment));
        if ($ret === false)
            $this->showMsg('更新支付方式错误');
        if ($payment == '2') {
            // 去支付
            $payModel = A('Label/PayUnion');
            $payModel->OrderPay($order_id);
        } elseif ($payment == '3') {
            $payModel = A('Label/PayWeixin');
            $payModel->goAuthorize($order_id);
        }
        exit();
    }

    public function showMsg($info, $status = 0) {
        if ($this->node_id)
            $node_short_name = get_node_info($this->node_id, 'node_short_name');
        else
            $node_short_name = '';
        $this->assign('id', $this->id);
        $this->assign('status', $status);
        $this->assign('info', $info);
        $this->assign('node_short_name', $node_short_name);
        $this->display('msg');
        exit();
    }
}