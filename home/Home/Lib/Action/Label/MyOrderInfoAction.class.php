<?php

class MyOrderInfoAction extends Action {

    public $MyOrderInfoModel;

    public function index() {
        $order_id = $_GET['order_id'];
        if (empty($this->MyOrderInfoModel)) {
            $this->MyOrderInfoModel = D('MyOrderInfo');
        }
        
        $orderInfo = $this->MyOrderInfoModel->OrderInfoShow($order_id);
        $orderListInfo = $this->MyOrderInfoModel->OrderInfo_ex($order_id);
        
        $this->assign('orderInfo', $orderInfo);
        $this->assign('orderListInfo', $orderListInfo);
        
        // 店铺地址
        $m_info = M('tmarketing_info')->where(
            array(
                'node_id' => $orderInfo['node_id'], 
                'batch_type' => '29'))->find();
        $channel_id = M('tchannel')->where(
            array(
                'node_id' => $orderInfo['node_id'], 
                'type' => '4', 
                'sns_type' => '46'))->getField('id');
        $label_id = get_batch_channel($m_info['id'], $channel_id, '29', 
            $orderInfo['node_id']);
        $this->assign('shopid', $label_id);
        
        $this->display();
    }
}