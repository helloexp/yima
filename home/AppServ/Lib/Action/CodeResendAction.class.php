<?php

/**
 * 功能：凭证重发
 *
 * @author siht 时间：2013-06-26
 */
class CodeResendAction extends BaseAction {

    public $org_transaction_id;

    // 源请求流水号
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin();
        $this->org_transaction_id = I('org_transaction_id'); // 源请求流水号
    }

    public function run() {
        $rs = $this->check();
        if (!($rs === true)) {
            $this->returnError($rs['resp_desc']);
        }

        // 1. 获取源流水信息
        $where         = "NODE_ID ='" . $this->node_id . "' and req_seq ='" . $this->org_transaction_id . "'";
        $barcode_trace = M('TbarcodeTrace')->where($where)->find();

        if (!$barcode_trace) {
            $this->returnError('改请求流水不存在', '1012');
        }

        // 请求支撑
        $TransactionID = date("YmdHis") . rand(100000, 999999); // 凭证重发单号
        $RemoteRequest = D('RemoteRequest', 'Service');

        // 请求参数
        $req_array = array(
                'ResendReq' => array(
                        'TransactionID'    => $TransactionID,
                        'OriTransactionID' => $this->org_transaction_id,
                        'SystemID'         => C('ISS_SYSTEM_ID'),
                        'ISSPID'           => $this->node_id,
                        'SendLevel'        => 7,
                        'DataFrom'         => 1,
                        'Recipients'       => array(
                                'Number' => $barcode_trace['PHONE_NO'],
                        ),
                        'SendClass'        => 'SAM',
                        'BmpFlag'          => 0,
                ),
        );

        $resp_array = $RemoteRequest->requestIssServ($req_array);
        $ret_msg    = $resp_array['ResendRes']['Status'];

        if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
            if ($ret_msg['StatusCode'] == '5016') {
                M('tbarcode_trace')->where($where)->save(['resend_allow_flag' => 1]);
            }
            log_write('重发返回的状态码：' . $ret_msg['StatusCode']);
            $resp_desc = "支撑应答：" . $ret_msg['StatusText'];
            $this->returnError($resp_desc, $ret_msg['StatusCode']);
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
        if ($this->pos_id == '') {
            return array(
                    'resp_desc' => '终端号不能为空！',
            );

            return false;
        }
        if ($this->org_transaction_id == '') {
            return array(
                    'resp_desc' => '源请求流水号不能为空！',
            );

            return false;
        }

        return true;
    }
}

?>