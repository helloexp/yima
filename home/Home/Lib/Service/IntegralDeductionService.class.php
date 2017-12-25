<?php

class IntegralDeductionService {

    /**
     * 订单支付后，判断用户类型，处理积分增加
     */
    public function pay_notify($order_id, $order_phone, $order_amt, 
        $orderInfo = null) {
        /*
         * if($order_phone != $orderInfo['receiver_phone']) { $receiverFlag =
         * M('tmember_info')->where(array('node_id'=>$orderInfo['node_id'],
         * 'phone_no'=>$orderInfo['receiver_phone']))->lock(true)->find();
         * if(!$receiverFlag) { $data = array( 'node_id' =>
         * $orderInfo['node_id'], 'batch_no' => $orderInfo['batch_no'], 'name'
         * => '', 'phone_no' => $orderInfo['receiver_phone'], 'sex' => '1',
         * 'years' => date('Y'), 'month_days' => date('md'), 'status' => '0',
         * 'add_time' => date('YmdHis'), 'request_id' => '', 'channel_id' =>
         * $orderInfo['batch_channel_id'], 'batch_id' => $orderInfo['batch_no']
         * ); $result = M('tmember_info')->add($data); if($result===false){
         * return false; } $mIns = D('MemberInstall', 'Model'); //生成会员卡编号 $mNum
         * = $mIns->makeMemberCardNum($orderInfo['node_id'], $result); if($mNum)
         * { //生成会员二维码 $mIns->makeMemberCode($orderInfo['node_id'], $result); }
         * $card_id =
         * M('tmember_cards')->where(array('node_id'=>$orderInfo['node_id'],
         * 'acquiesce_flag'=>1))->getField('id'); if($card_id) { $card_ex =
         * array( 'node_id' => $orderInfo['node_id'], 'member_id' => $result,
         * 'membercard_id' => $card_id, 'add_time' => date('YmdHis') ); $ret =
         * M('tmember_relation_cards')->add(); }
         * node_log('商品购买成功，收件人成为会员，会员id：', print_r($result, true)); } }
         */
        M()->startTrans();
        
        $node_config = M("tintegral_node_config")->where(
            array(
                "node_id" => $orderInfo['node_id']))->getField(
            "shop_online_rate, one_online_rate, one_online_rate_flag, shop_line_flag");
        if (! $node_config) {
            log_write('订单支付成功，但未设置金额转换成积分的比率，暂无处理！订单号：[' . $order_id . ']');
            return;
        }
        
        $memberInfo = M('tmember_info')->where(
            array(
                'node_id' => $orderInfo['node_id'], 
                'phone_no' => $order_phone))->find();
        $change_num = 0; // 应增加的积分
        if ($node_config['shop_line_flag'] == 1) {
            if ($order_amt > 0) {
                $change_num = intval(
                    $order_amt * $node_config['shop_online_rate']);
                if ($node_config['one_online_rate_flag'] == 1) {
                    if ($change_num > $node_config['one_online_rate']) {
                        $change_num = $node_config['one_online_rate'];
                    }
                }
            }
        }
        
        $integral = D('IntegralPointTrace', 'Model');
        $flag = $integral->integralPointChange(1, $change_num, 
            $memberInfo['id'], $orderInfo['node_id'], $order_id);
        if (! $flag) {
            M()->rollback();
            return;
        }
        
        // 添加行为数据
        $MemberBehavior = new MemberBehaviorModel();
        $behaviorRes = $MemberBehavior->addBehaviorData($memberInfo['id'], 
            $orderInfo['node_id'], "", "", "", 1, "");
        if ($behaviorRes === false) {
            M()->rollback();
        }
        
        M()->commit();
        return $change_num;
    }
}