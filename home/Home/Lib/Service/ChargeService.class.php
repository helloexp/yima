<?php

/**
 * 旺财服务开通、服务购买、消费
 *
 * @author kk 2014年8月1日13:41:23
 */
class ChargeService {

    public function open($postInfo) {
        $orderInfo = M('tactivity_order')->getByOrder_number(
            $postInfo['REQUEST']['order_id']);
        if ($orderInfo['pay_status'] == '1') {
            return;
        }
        log_write('在线签约-营帐通知：' . json_encode($postInfo));
        M('tactivity_order')->where(
            array(
                'id' => $orderInfo['id']))->save(
            array(
                'pay_status' => 1));
        $orderDetail = json_decode($orderInfo['detail'], true);
        $nodeInfo = get_node_info($orderInfo['node_id']);
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
        
        if (! $orderDetail['basicEndTime']) {
            if ($orderDetail['version'] == '2') {
                $ServCode = '3004';
                $ServPrice = '0';
                $AgreePrice = '0';
            } elseif ($orderDetail['version'] == '3') {
                $ServCode = '3003';
                $ServPrice = '0';
                $AgreePrice = '0';
            }
        } else {
            $TransactionID .= ',' . ($TransactionID + 1);
            if ($orderDetail['version'] == '2') {
                $ServCode = '3050,3004';
                $ServPrice = '0,0';
                $AgreePrice = '0,0';
            } elseif ($orderDetail['version'] == '3') {
                $ServCode = '3050,3003';
                $ServPrice = '0,0';
                $AgreePrice = '0,0';
            }
        }
        $req_array = array(
            'SetShopServOnReq' => array(
                'TransactionID' => $TransactionID, 
                'SystemID' => C('YZ_SYSTEM_ID'), 
                'NodeID' => $nodeInfo['node_id'], 
                'ContractID' => $nodeInfo['contract_no'], 
                'ServCode' => $ServCode, 
                'ServPrice' => $ServPrice, 
                'AgreePrice' => $AgreePrice, 
                'BeginTime' => '', 
                'EndTime' => '', 
                'ChargingPeriod' => '12', 
                'Effect' => 0, 
                'Paytype' => '1', 
                'ServFlag' => '2', 
                'ServLvl' => '', 
                'AccountPrice' => $postInfo['success']['ACCOUNT_002'], 
                'AccountTrace' => $postInfo['success']['ACCOUNT_TRACE']['account_002'], 
                'WBPrice' => $postInfo['success']['ACCOUNT_003'], 
                'WBTrace' => $postInfo['success']['ACCOUNT_TRACE']['account_003'], 
                'YSZKPrice' => $postInfo['success']['ACCOUNT_004'], 
                'YSZKTrace' => $postInfo['success']['ACCOUNT_TRACE']['account_004'], 
                'FreeFlag' => $postInfo['success']['WB_FREE_FLAG']));
        log_write(
            '在线签约[报文]-订单号（' . $postInfo['REQUEST']['order_id'] . '）:' .
                 json_encode($req_array));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $result = $RemoteRequest->requestYzServ($req_array);
        if ($result['Status']['StatusCode'] != '0000') {
            log_write(
                '在线签约-订单号（' . $postInfo['REQUEST']['order_id'] . '）:' .
                     $result['Status']['StatusText']);
        } else {
            log_write(
                '在线签约-订单号（' . $postInfo['REQUEST']['order_id'] . '）:开通成功！');
        }
    }
}