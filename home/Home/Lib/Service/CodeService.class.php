<?php

class CodeService extends Action {

    public function _initialize() {
    }

    /**
     * 检验
     *
     * @param type $seq
     * @return type
     */
    public function checkSeq($seq) {
        $barcodeTrace = M()->table("tbarcode_trace bt")->join(
            'tgoods_info gi ON gi.batch_no = bt.batch_no')
            ->where(
            array(
                'bt.req_seq' => $seq, 
                'bt.node_id' => $_SESSION['node_id']))
            ->field('bt.id, bt.assist_number, gi.begin_time, gi.end_time')
            ->find();
        return $barcodeTrace;
    }

    /**
     *
     * @param type $nodeId
     * @param type $type
     * @return type
     */
    public function getOnlinePosId($nodeId, $type) {
        $storeInfoModel = M('tstore_info');
        $storeId = $storeInfoModel->where(
            array(
                'node_id' => $nodeId, 
                'type' => $type))->getField('store_id');
        $tposInfoModel = M('tpos_info');
        $posId = $tposInfoModel->where(
            array(
                'store_id' => $storeId, 
                'node_id' => $nodeId, 
                'is_activated' => '1', 
                'pos_status' => '0'))->getField('pos_id');
        return $posId;
    }

    /**
     */
    public function addDataToTable($seqNum, $addressId, $posId, $posSeq, $nodeId, 
        $deliveryNodeId) {
        $saveData = array();
        $saveData['node_id'] = $_SESSION['node_id'];
        $saveData['from_node_id'] = $nodeId;
        $saveData['add_time'] = date('YmdHis');
        $addressInfo = M('tphone_address')->where(
            array(
                'user_phone' => $_SESSION['groupPhone'], 
                'id' => $addressId))->find();
        
        $saveData['receiver_name'] = $addressInfo['user_name'];
        $saveData['receiver_citycode'] = $addressInfo['path'];
        $saveData['receiver_addr'] = $addressInfo['address'];
        $saveData['receiver_phone'] = $addressInfo['phone_no'];
        $saveData['req_seq'] = $seqNum;
        $saveData['pos_id'] = $posId;
        $saveData['pos_seq'] = $posSeq;
        $saveData['delivery_node_id'] = $deliveryNodeId;
        
        $dataId = M('tonline_get_order')->add($saveData);
        if ($dataId) {
            M('tphone_address')->where(
                array(
                    'user_phone' => $_SESSION['groupPhone'], 
                    'id' => $addressId))->save(
                array(
                    'last_use_time' => date('YmdHis')));
            return $dataId;
        }
    }
}