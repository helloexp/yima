<?php

// 广西石油首页
class IndexAction extends ZggkAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        // 多乐互动
        $model = M('tmarketing_info');
        $batchNum = $model->where(
            array(
                'node_id' => $this->node_id))->count();
        $batchNum = is_null($batchNum) ? 0 : $batchNum;
        // 取活动总访问量
        $visitmap['node_id'] = $this->nodeId;
        $visitSum = M()->table('tmarketing_info')
            ->where($visitmap)
            ->sum('click_count');
        if ($visitSum == "") {
            $visitSum = 0;
        }
        // 发码总数
        $sendSum = M('tpos_day_count')->where("node_id='{$this->nodeId}'")->sum(
            'send_num');
        $sendSum = is_null($sendSum) ? 0 : $sendSum;
        
        // 累计访问量
        $all_click = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => array(
                    'in', 
                    '26,27,29')))->getField('sum(click_count)');
        // 累计订单数
        $order_count = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'order_status' => '0', 
                'pay_status' => '2'))->count();
        // 累计成交额
        $order_amount = M('ttg_order_info')->where(
            array(
                'node_id' => $this->node_id, 
                'order_status' => '0', 
                'pay_status' => '2'))->getField('sum(order_amt)');
        if (! $order_amount) {
            $order_amount = 0;
        }
        
        // 正在销售的商品
        $sale_goods = M('tgoods_info')->where(
            array(
                'node_id' => $this->node_id, 
                'goods_type' => '6'))->count();
        $this->assign('batchNum', $batchNum);
        $this->assign('visitSum', $visitSum);
        $this->assign('sendSum', $sendSum);
        $this->assign('all_click', $all_click);
        $this->assign('order_count', $order_count);
        $this->assign('order_amount', $order_amount);
        $this->assign('sale_goods', $sale_goods);
        $this->display();
    }
}
