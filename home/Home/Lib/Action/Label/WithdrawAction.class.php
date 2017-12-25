<?php

/*
 * 提领
 */
class WithdrawAction extends Action {

    public $node_id;

    public function __construct() {
        parent::__construct();
    }

    public function wechatBridge() {
        $encryptCode = I('get.encrypt_code');
        $wechatCardId = I('get.card_id');
        $nodeId = M('twx_card_type')->where(
            array(
                'card_id' => $wechatCardId))->getfield('node_id');
        if (! $nodeId) {
            $this->error('未找到对应的商户信息！');
        }
        $wechatCardService = D('WeiXinCard', 'Service');
        $wechatCardService->init_by_node_id($nodeId);
        $result = $wechatCardService->decodeWechatCardCode($encryptCode);
        if ($result['errcode'] == '0') {
            $assitentNumber = $result['code'];
            $reqSeq = M('tbarcode_trace')->where(
                array(
                    'assist_number' => $assitentNumber))->getfield('req_seq');
            if ($reqSeq != '') {
                redirect(
                    U('Label/Withdraw/showWithdraw', 
                        array(
                            'seq' => $reqSeq)));
            } else {
                $this->error('没有找到对应的提领券！请联系商家');
            }
        } else {
            $this->error('微信接口返回错误' . $result['errmsg']);
        }
    }

    /**
     * 提领查看页面
     */
    public function showWithdraw() {
        $seq = I('seq');
        $where = array("b.req_seq" => $seq);
        
        $WithdrawService = D('Withdraw', 'Service');
        $goodsInfo = $WithdrawService->getWithdrawInfo($where);
        
        $imgResult = $WithdrawService->_bar_resize(base64_decode($goodsInfo['barcode_bmp']), 'png');
        $goodsInfo['barcode_bmp'] = $goodsInfo['barcode_bmp'] ? 'data:image/png;base64,' . base64_encode($imgResult) : '';
        $this->node_id = $goodsInfo['node_id'];
        if ($goodsInfo['end_time'] < date('YmdHis')) {
            $goodsInfo['satus'] = 'expire';
        } elseif ($goodsInfo['begin_time'] > date('YmdHis')) {
            $goodsInfo['satus'] = 'beforeExpire';
        }
        $this->assign('node_id', $this->node_id);
        $this->assign('goodsInfo', $goodsInfo);
        
        // 查询热线
        $telphone = M('tnode_info')->where(array('node_id' => $this->node_id))->getField('node_service_hotline');
        $this->assign('telphone', $telphone);
        
        //店铺
        $goodsModel = D('Goods');
        $shopList = $goodsModel->getGoodsShop($goodsInfo['goods_id'], TRUE, $this->node_id, 'noOnline');
        $this->assign('shopCount', count($shopList));
        
        // 小店Id
        $label_id = '';
        $m_info = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => '29'))->find();
        if (! empty($m_info)) {
            $channel_id = M('tchannel')->where(
                array(
                    'node_id' => $this->node_id, 
                    'type' => '4', 
                    'sns_type' => '46'))->getField('id');
            if ($channel_id) {
                $label_id = get_batch_channel($m_info['id'], $channel_id, '29', 
                    $this->node_id);
            }
        }
        $this->assign('shopId', $label_id);
        
        $this->display();
    }
}
