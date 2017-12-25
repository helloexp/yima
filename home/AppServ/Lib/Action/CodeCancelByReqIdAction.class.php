<?php

/**
 *  todo 要管
 * 功能：凭证撤销
 *
 * @author siht 时间：2013-08-15
 */
class CodeCancelByReqIdAction extends BaseAction {

    public $node_id;
    // 商户号
    public $user_id;
    // 操作员
    public $request_id;
    // 源请求流水号
    public $cancel_flag;

    // 撤销标识
    public function _initialize() {
        parent::_initialize();
        // 动态载入 CodeSendForLable 配置文件
        C('CodeSendForLable', require(CONF_PATH . 'configCodeSendForLable.php')); // 和内部发码接口共进退
        // 初始化请求参数
        $this->node_id     = I('node_id');
        $this->user_id     = I('user_id');
        $this->request_id  = I('request_id');
        $this->cancel_flag = I('cancel_flag', 0);
    }

    public function run() {
        // 调用接口
        $info = C('CodeSendForLable');

        if (!$info) {
            // 尚未配置接口，接口不对外
            $resp_desc = "非法访问！";
            $this->returnError($resp_desc);
            exit();
        }

        // 获取接入端IP
        $ip    = $_SERVER['REMOTE_ADDR'];
        $ipArr = explode(',', $info['IMPORT_IP']);
        if (!in_array($ip, $ipArr)) {
            // IP不允许接入
            $resp_desc = "IP:" . $ip . "不允许接入";
            $this->returnError($resp_desc);
        }

        $rs = $this->check();

        if (!$rs) {
            $resp_desc = "请填写商户号、用户号、撤销流水号";
            $this->returnError($resp_desc);
        }

        // 第一步,校验是否具备发码权限
        // (1)商户校验
        $where     = "NODE_ID ='" . $this->node_id . "'";
        $node_info = M('TnodeInfo')->where($where)->find();
        if (!$node_info) {
            $this->returnError('商户不存在', '1011');
        }
        if ($node_info['status'] != '0') {
            $this->returnError('商户已停用', '1012');
        }

        $pos_info = array(
                'pos_id' => '00000000',
        );

        // 第二步,获取发码流水
        $where        = "NODE_ID ='" . $this->node_id . "' and request_id ='" . $this->request_id . "' and trans_type = '0001'";
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

        $rs = M('TbarcodeTrace')->add($barcode_trace);
        if (!$rs) {
            $this->log(print_r($barcode_trace, true));
            $this->log("记录流水信息[tbarcode_trace]失败");
        }

        // save trace
        $tbarcode_trace_send['req_seq']     = $TransactionID;
        $tbarcode_trace_send['org_req_seq'] = $barcode_info['req_seq'];
        $tbarcode_trace_send['trans_type']  = '0002';
        $tbarcode_trace_send['phone_no']    = $barcode_info['phone_no'];
        $tbarcode_trace_send['trans_time']  = date('YmdHis');
        $tbarcode_trace_send['ret_code']    = $ret_msg['StatusCode'];
        $tbarcode_trace_send['ret_desc']    = $ret_msg['StatusText'];

        if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
            $tbarcode_trace_send['status'] = '2';
            $rs                            = M()->table('tbarcode_trace_send')->add($tbarcode_trace_send);
            if ($rs === false) {
                $this->log('保存发送流水失败' . M()->_sql());
            }
            $this->returnError('支撑应答：' . $ret_msg['StatusText'], $ret_msg['StatusCode']);
        }

        $tbarcode_trace_send['status'] = '1';
        $rs                            = M()->table('tbarcode_trace_send')->add($tbarcode_trace_send);
        if ($rs === false) {
            $this->log('保存发送流水失败' . M()->_sql());
        }

        // 更新流水,应答
        $where            = "NODE_ID ='" . $this->node_id . "' and request_id ='" . $this->request_id . "' and trans_type = '0001'";
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
        $this->node_id    = $_REQUEST['node_id'];
        $this->phone_no   = $_REQUEST['phone_no'];
        $this->request_id = $_REQUEST['request_id'];
        $this->pos_id     = '0000000000';
        if ($this->node_id == '' || $this->user_id == '' || $this->request_id == '') {
            return false;
        }

        return true;
    }
}

?>