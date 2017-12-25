<?php

/**
 * 功能：凭证重试发送
 *
 * @author siht 时间：2013-06-26
 */
class CodeRetryReqAction extends BaseAction {

    public $node_id;
    // 商户号
    public $user_id;
    // 操作员
    public $request_id;

    // 源请求流水号
    public function _initialize() {
        parent::_initialize();
        // 动态载入 CodeSendForLable 配置文件
        C('CodeSendForLable', require(CONF_PATH . 'configCodeSendForLable.php')); // 和内部发码接口共进退
        $this->node_id    = I('node_id');
        $this->user_id    = I('user_id');
        $this->request_id = I('request_id');
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
        if (!($rs === true)) {
            $this->returnError($rs['resp_desc']);
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
        $where        = "NODE_ID ='" . $this->node_id . "' and request_id ='" . $this->request_id . "' and trans_type = '0001' ";
        $barcode_info = M('TbarcodeTrace')->where($where)->find();
        if (!$barcode_info) {
            $this->returnError('该凭证不存在', '7015');
        }
        if ($barcode_info['status'] != '3') {
            $this->returnError('该凭证非失败记录', '9003');
        }

        // 请求支撑
        $RemoteRequest = D('RemoteRequest', 'Service');

        // 请求参数
        $req_array = array(
                'SubmitVerifyReq' => array(
                        'SystemID'      => C('ISS_SYSTEM_ID'),
                        'ISSPID'        => $this->node_id,
                        'TransactionID' => $barcode_info['req_seq'],
                        'Recipients'    => array(
                                'Number' => $barcode_info['phone_no'],
                        ),
                        'SendClass'     => 'SAM',
                        'Messages'      => array(
                                'Sms' => array(
                                        'Text' => $barcode_info['sms_text'],
                                ),
                                'Mms' => array(
                                        'Subject' => $barcode_info["mms_title"],
                                        'Text'    => $barcode_info['mms_text'],
                                ),
                        ),
                        'ActivityInfo'  => array(
                                'ActivityID' => $barcode_info['batch_no'],
                                'BeginTime'  => $barcode_info['begin_time'],
                                'EndTime'    => $barcode_info['end_time'],
                                'OrgTimes'   => $barcode_info["validate_times"],
                                'OrgAmt'     => $barcode_info['price'],
                                'PrintText'  => $barcode_info['print_text'],
                        ),
                        'BmpFlag'       => 2,
                        'CustomArea'    => $barcode_info['request_id'],
                ),
        );

        $resp_array = $RemoteRequest->requestIssServ($req_array);
        $ret_msg    = $resp_array['SubmitVerifyRes']['Status'];

        if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
            $resp_desc = "支撑应答：" . $ret_msg['StatusText'];
            $this->returnError($resp_desc, $ret_msg['StatusCode']);
        }
        $barcode_trace['ret_code']   = $ret_msg['StatusCode'];
        $barcode_trace['ret_desc']   = $ret_msg['StatusText'];
        $barcode_trace['trans_type'] = '0001';
        if (($ret_msg['StatusCode'] === '0000') || ($ret_msg['StatusCode'] === '0001')) {
            $barcode_trace['status'] = '0';
        } else {
            $barcode_trace['status'] = '3';
        }
        $barcode_trace['assist_number'] = $resp_array['SubmitVerifyRes']['AssistNumber'];
        $barcode_trace['barcode_bmp']   = $resp_array['SubmitVerifyRes']['Wbmp'];
        $barcode_trace['sys_seq']       = $resp_array['SubmitVerifyRes']['MessageID'];
        // 更新tbarcode_trace
        $rs = M('TbarcodeTrace')->where($where)->save($barcode_trace);
        if ($rs === false) {
            $this->returnError('凭证发送成功，记录数据失败', '9004');
        }

        $this->returnSuccess($resp_desc, array(
                        "message_id" => $resp_array['ResendRes']['MessageID'],
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
        if ($this->request_id == '') {
            return array(
                    'resp_desc' => '请求流水号不能为空！',
            );

            return false;
        }

        return true;
    }
}

?>