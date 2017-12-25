<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates and open the template
 * in the editor.
 */

/**
 * Description of SkuService
 *
 * @author john_zeng
 */
class PayOrderService {
    
    // 构造函数
    public function __construct() {
    }

    /**
     * Description of SkuService 红包总规则添加
     *
     * @param int $nodeId 商户标识 type $type 红包规则类型 0-关闭红包使用 1-不限红包使用 2-限制红包使用
     * @return bloor $res
     * @author john_zeng
     */
    public function payComplete($orderId, $type, $payChannel, $transactionId) {
        // 取得大订单信息
        $orderModel = M('ttg_order_info');
        $result = $orderModel->where(
            array(
                "order_id" => $orderId))->save(
            array(
                'pay_status' => '2', 
                'pay_channel' => $payChannel,  // 新增红包0元支付渠道
                'order_status' => '0', 
                'profit_status' => '0',  // 需要分润
                'update_time' => date('YmdHis'), 
                'pay_seq' => $data['transaction_id']));
        if ($result === false) {
            M()->rollback();
            log_write("[fail]订单状态更新失败. 订单号：{$data['order_seq']}");
            return false;
        }
    }

    /**
     * Description of SkuService 查询支付金额是否符合规定
     *
     * @param float $payMoney 需要支付的金额 int $channel 支付渠道 float $payRule 限定支付金额
     * @return bloor $msg
     * @author john_zeng
     */
    public function checkPayRule($payMoney, $channel, $payRule) {
        $msg = true;
        switch ($channel) {
            case '2':
                if ($payMoney <= $payRule) {
                    $msg = true;
                }
                break;
            default:
                break;
        }
        return $msg;
    }
}
