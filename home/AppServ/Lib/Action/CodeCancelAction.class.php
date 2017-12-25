<?php

/**
 * 功能：凭证撤销
 *
 * @author siht 时间：2013-08-15
 */
class CodeCancelAction extends BaseAction {

    public $request_id;
    // 源请求流水号
    public $cancel_flag;

    // 撤销标识
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin();
        // 初始化请求参数
        $this->request_id  = I('request_id');
        $this->cancel_flag = I('cancel_flag', 0);
    }

    public function run() {
        $rs = $this->check();
        if (!($rs === true)) {
            $this->returnError($rs['resp_desc']);
        }

        // 第一步,校验是否具备撤销权限
        // (1)商户校验
        $where     = "NODE_ID ='" . $this->node_id . "'";
        $node_info = M('TnodeInfo')->where($where)->find();
        if (!$node_info) {
            $this->returnError('商户不存在', '1011');
        }
        if ($node_info['status'] != '0') {
            $this->returnError('商户已停用', '1012');
        }

        // (2)终端校验
        $where    = "POS_ID ='" . $this->pos_id . "'";
        $pos_info = M('TposInfo')->where($where)->find();
        if (!$pos_info) {
            $this->returnError('该终端不存在', '1013');
        }
        if ($pos_info['pos_status'] != '0') {
            $this->returnError('该终端已停用或欠费停机', '1014');
        }

        // 第二步,获取发码流水
        $where        = "NODE_ID ='" . $this->node_id . "' and req_seq ='" . $this->request_id . "' and trans_type = '0001'";
        $barcode_info = M('TbarcodeTrace')->where($where)->find();
        if (!$barcode_info) {
            $this->returnError('该凭证不存在', '7015');
        }
        if ($barcode_info['status'] != '0') {
            $this->returnError('该凭证已撤销或失败', '9003');
        }

        $TransactionID = date("YmdHis") . rand(100000, 999999); // 凭证发送单号
        $RemoteRequest = D('RemoteRequest', 'Service');

        // 第三步，请求支撑撤销
        $req_array  = array(
                'CancelReq' => array(
                        'SystemID'         => C('ISS_SYSTEM_ID'),
                        'ISSPID'           => $this->node_id,
                        'TransactionID'    => $TransactionID,
                        'OriTransactionID' => $barcode_info['req_seq'],
                        'CancelFlag'       => $this->cancel_flag,
                ),
        );
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        $ret_msg    = $resp_array['CancelRes']['Status'];

        // 第四步, 更新源流水状态，记流水
        $barcode_trace                = array();
        $barcode_trace['req_seq']     = $TransactionID;
        $barcode_trace['org_req_seq'] = $barcode_info['req_seq'];
        $barcode_trace['sys_seq']     = $barcode_info['sys_seq'];
        $barcode_trace['request_id']  = $this->request_id;
        $barcode_trace['node_id']     = $this->node_id;
        $barcode_trace['user_id']     = $this->user_id;
        $barcode_trace['pos_id']      = $pos_info['pos_id'];
        $barcode_trace['batch_no']    = $barcode_info['batch_no'];
        $barcode_trace['phone_no']    = $barcode_info['phone_no'];
        $barcode_trace['trans_time']  = date('YmdHis');
        $barcode_trace['ret_code']    = $ret_msg['StatusCode'];
        $barcode_trace['ret_desc']    = $ret_msg['StatusText'];
        $barcode_trace['trans_type']  = '0002';
        $barcode_trace['status']      = $ret_msg['StatusCode'] > '0001' ? '3' : '0';
        $barcode_trace['data_from']   = $barcode_info['data_from'];
        $barcode_trace['batch_id']    = $barcode_info['batch_id'];
        $barcode_trace['price']       = $barcode_info['price'];
        $rs                           = M('TbarcodeTrace')->add($barcode_trace);
        if (!$rs) {
            $this->log(print_r($barcode_trace, true));
            $this->log("记录流水信息[tbarcode_trace]失败");
        }

        if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
            $this->returnError($ret_msg['StatusText'], $ret_msg['StatusCode']);
        }

        // 更新流水,应答
        $where            = "NODE_ID ='" . $this->node_id . "' and req_seq ='" . $this->request_id . "' and trans_type = '0001'";
        $oribarcode_trace = array(
                'status' => '1',
        );
        $rs               = M('TbarcodeTrace')->where($where)->save($oribarcode_trace);
        if (!$rs) {
            $this->log("更新流水信息[tbarcode_trace]失败");
        }
        $this->returnSuccess('撤销成功', array(
                        "request_id" => $this->request_id,
                ));
    }

    private function check() {
        if ($this->node_id == '') {
            return array(
                    'resp_desc' => '商户号不能为空！',
            );

            return false;
        }
        if ($this->user_id == '') {
            return array(
                    'resp_desc' => '用户id不能为空！',
            );

            return false;
        }
        if ($this->pos_id == '') {
            return array(
                    'resp_desc' => '终端号不能为空！',
            );

            return false;
        }
        if ($this->request_id == '') {
            return array(
                    'resp_desc' => '撤销流水号不能为空！',
            );

            return false;
        }

        return true;
    }
}

?>