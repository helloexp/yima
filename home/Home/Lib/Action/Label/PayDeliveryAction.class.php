<?php

/**
 * 货到付款支付模块
 *
 * @author tr
 */
class PayDeliveryAction extends BaseAction {

    public $orderData = array();

    public $groupPhone = '';

    public $notifyUrl = '';
    // 异步通知地址
    public $callBackUrl = '';
    // 同步通知地址
    public $node_short_name = '';

    public $node_id = '';

    public function _initialize() {
        C('LOG_PATH', LOG_PATH . 'Log_alipay_'); // 日志路径+文件名前缀,);
        C('CUSTOM_LOG_PATH', LOG_PATH . 'Log_alipay_'); // 日志路径+文件名前缀,);
    }

    public function OrderPay($orderId = null) {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->showMsg('您还没有登录', 0, null, '', '', $this->node_id);
        }
        $this->groupPhone = session('groupPhone');
        $orderId = empty($orderId) ? I('order_id') : $orderId;
        if (empty($orderId))
            $this->showMsg('参数错误', 0, null, '', '', $this->node_id);
        // 获取订单信息
        $this->orderData = D('StoreOrder')->getOrderInfo($orderId);
        if (false === $this->orderData) {
            $msg = D('StoreOrder')->getError();
            $this->error($msg);
        }
        $this->delipay();
    }

    public function delipay() {
        // 给商户通知弹框
        $this->sendNotice2($this->orderData['node_id'], 
            $this->orderData['order_id'], $this->orderData['order_amt']);
        
        $this->showMsg('下单成功', 1, $this->orderData['batch_channel_id'], 
            $this->orderData['order_id'], '', $this->orderData['node_id']);
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
}