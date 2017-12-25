<?php

class CodeAction extends Action {

    public function _initialize() {
    }

    /**
     * 适用门店
     */
    public function codeShop() {
        $req = I('get.id');
        $barcodeTraceModel = D('TbarcodeTrace');
        $goodsInfo = $barcodeTraceModel->getGoodsInfoBySeq($req);
        $goodsId = $goodsInfo['goods_id'];
        $goodsModel = D('Goods');
        if ($_SESSION['node_id'] != '') {
            $nodeId = $_SESSION['node_id'];
        } else {
            $nodeId = $goodsInfo['node_id'];
        }
        $shopList = $goodsModel->getGoodsShop($goodsId, TRUE, $nodeId, 'noOnline');
        $this->assign('seq', $req);
        if ($goodsInfo['use_status'] == '2') {
            $this->assign('useStatus', 'used');
        } elseif ($goodsInfo['end_time'] < date('YmdHis')) {
            $this->assign('useStatus', 'overDate');
        } elseif ($goodsInfo['online_verify_flag'] == '1') {
            $this->assign('useStatus', 'yes');
        }
        
        $this->assign('withDraw', $goodsInfo['online_verify_flag']);
        $this->assign('shopList', $shopList);
        $this->assign('goodsInfo', array(
            'req_seq' => $req));
        $this->display();
    }

    /**
     * 提领券详情
     */
    public function goodsDetail() {
        $cityArray = array();
        $seq = I('get.id');
        $condition = array();
        if ($_SESSION['node_id'] != '') {
            $condition['bt.node_id'] = $_SESSION['node_id'];
        }
        $condition['bt.req_seq'] = $seq;
        $codeGoodsInfo = M()->table("tbarcode_trace bt")->join(
            'tgoods_info gi ON bt.batch_no = gi.batch_no')
            ->join(
            'tonline_get_order ogo ON ogo.node_id = bt.node_id AND ogo.req_seq = bt.req_seq')
            ->where($condition)
            ->field('gi.print_text, ogo.delivery_company, ogo.delivery_number')
            ->find();
        $this->assign('codeGoodsInfo', $codeGoodsInfo);
        $this->display();
    }

    public function expressInfo() {
        $seq = I('get.id');
        $codeGoodsInfo = M('tonline_get_order')->where(
            array(
                'req_seq' => $seq, 
                'order_status' => array(
                    'neq', 
                    '2')))
            ->field('delivery_number, delivery_company')
            ->find();
        $this->assign('codeGoodsInfo', $codeGoodsInfo);
        $expressInfo = M('torder_express_info')->where(
            array(
                'order_id' => $seq, 
                'type' => '1'))->getfield('express_content');
        $orderExpressInfoArray = json_decode($expressInfo, TRUE);
        $this->assign('expressInfo', $orderExpressInfoArray);
        $this->display();
    }

    /**
     * 验证提领券
     */
    public function veryWithDrowCode() {
        $result = array();
        $seqNum = I('post.seq');
        $addrId = I('post.address');
        $phoneAddressModel = D('TphoneAddress');
        $checkAddrResult = $phoneAddressModel->checkAddr($addrId);
        if (empty($checkAddrResult)) {
            $result['error'] = '1001';
            $result['msg'] = '未查询到对应的地址，请重新选择！';
            $result['url'] = U('Label&m=MyAddress&a=withDrowAddr', 
                array(
                    'seq' => $seqNum));
            $this->ajaxReturn($result);
            exit();
        }
        
        $codeService = D('Code', 'Service');
        $checkSeqResult = $codeService->checkSeq($seqNum);
        if (empty($checkSeqResult)) {
            $result['error'] = '2001';
            $result['msg'] = '未查询到对应的提领券，请确认或联系商家！';
            $result['url'] = U('Label/MyOrder/unusedCode', 
                array(
                    'node_id' => $_SESSION['node_id']));
            $this->ajaxReturn($result);
            exit();
        }
        
        $goodsInfo = M()->table("tbarcode_trace bt")->join(
            'tgoods_info gi ON bt.batch_no = gi.batch_no')
            ->field(
            'gi.source, gi.purchase_goods_id, gi.goods_name, gi.online_verify_flag, gi.purchase_node_id, bt.assist_number')
            ->where(
            array(
                'bt.node_id' => $_SESSION['node_id'], 
                'bt.req_seq' => $seqNum))
            ->find();
        if ($goodsInfo['online_verify_flag'] != '1') {
            $result['error'] = '2002';
            $result['msg'] = '此券，不是提领券，请确认或联系商家！';
            $result['url'] = U('Label/MyOrder/unusedCode', 
                array(
                    'node_id' => $_SESSION['node_id']));
            $this->ajaxReturn($result);
            exit();
        }
        
        if ($goodsInfo['source'] == '1') {
            $superiorGoodsInfo = M('tgoods_info')->where(
                array(
                    'goods_id' => $goodsInfo['purchase_goods_id']))
                ->field('node_id, source, purchase_node_id')
                ->find();
            if ($superiorGoodsInfo['source'] == '0') {
                $nodeId = $superiorGoodsInfo['node_id'];
                $deliveryNodeId = $nodeId;
            } elseif ($superiorGoodsInfo['source'] == '5') {
                $nodeId = $superiorGoodsInfo['node_id'];
                $deliveryNodeId = $superiorGoodsInfo['purchase_node_id'];
            }
        } elseif ($goodsInfo['source'] == '5') {
            $nodeId = $_SESSION['node_id'];
            $deliveryNodeId = $goodsInfo['purchase_node_id'];
        } else {
            $nodeId = $_SESSION['node_id'];
            $deliveryNodeId = $nodeId;
        }
        $posIdresult = $codeService->getOnlinePosId($nodeId, '3');
        if ($posIdresult == '') {
            $result['error'] = '4001';
            $result['msg'] = '未查询到有效的在线提领门店，请联系商家！';
            $result['url'] = U('Label/MyOrder/code_detail', 
                array(
                    'node_id' => $_SESSION['node_id'], 
                    'code_seq' => $seqNum));
            $this->ajaxReturn($result);
            exit();
        }
        $PosVerifyService = D('PosVerify', 'Service');
        $posVerifyResult = $PosVerifyService->doPosVerify($posIdresult, 
            $checkSeqResult['assist_number']);
        if ($posVerifyResult === TRUE) {
            $posSeq = $PosVerifyService->posSeq;
            $addDataResult = $codeService->addDataToTable($seqNum, $addrId, 
                $posIdresult, $posSeq, $nodeId, $deliveryNodeId);
            $result['error'] = '0';
            $result['msg'] = '恭喜你提领成功！';
            $startStr = substr($goodsInfo['assist_number'], 0, 4);
            $result['url'] = U('Label/Withdraw/showWithdraw', 
                array(
                    'seq' => $seqNum), '', '', TRUE);
            if ($startStr == '7063') {
                $isSendWithdrowDetail = M()->table("twx_assist_number tan")->join(
                    'tbatch_info ti on ti.id = tan.card_batch_id')
                    ->join('twx_card_type tcp on tcp.card_id = ti.card_id')
                    ->where(
                    array(
                        'tan.assist_number' => $checkSeqResult['assist_number']))
                    ->field('tcp.send_withdrow_detail')
                    ->find();
                if ($isSendWithdrowDetail['send_withdrow_detail'] == '1') {
                    $shortUrl = create_sina_short_url($result['url']);
                    $withdrowSendContent = '您的' . $goodsInfo['goods_name'] .
                         '提领成功，查看详情请点击：' . $shortUrl;
                    $result['meg'] = send_SMS($_SESSION['node_id'], 
                        $checkAddrResult, $withdrowSendContent, null);
                }
                $result['url'] = U('Label/Member/index',array('node_id'=>$_SESSION['node_id']));
            } else {
                $result['url'] = U('Label/MyOrder/code_detail', 
                    array(
                        'node_id' => $_SESSION['node_id'], 
                        'code_seq' => $seqNum, 
                        'type' => 'succ'), '', '', TRUE);
            }
            $this->ajaxReturn($result);
        } else {
            $result['error'] = '3001';
            $result['msg'] = $PosVerifyService->errMsg;
            $result['url'] = U('Label/MyOrder/code_detail', 
                array(
                    'node_id' => $_SESSION['node_id'], 
                    'code_seq' => $seqNum));
            $this->ajaxReturn($result);
        }
    }
}
