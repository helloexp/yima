<?php

/**
 * 功能：凭证发码
 *
 * @author siht 时间：2013-06-24
 */
class CodeSendAction extends BaseAction {

    public $batch_no;
    // 活动号
    public $phone_no;
    // 手机号
    public $send_num;
    // 发送次数
    public $request_id;

    // 旺财app请求流水号
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin();
        $this->batch_no   = I('batch_no'); // 活动号
        $this->phone_no   = I('phone_no'); // 手机号
        $this->send_num   = I('send_num'); // 发送次数
        $this->request_id = I('request_id'); // 旺财app请求流水号
    }

    public function run() {
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

        // (2)终端校验
        $where    = "POS_ID ='" . $this->pos_id . "'";
        $pos_info = M('TposInfo')->where($where)->find();
        if (!$pos_info) {
            $this->returnError('该终端不存在', '1013');
        }
        if ($pos_info['pos_status'] != '0') {
            $this->returnError('该终端已停用或欠费停机', '1014');
        }

        // 第二步,取活动信息
        $where         = "NODE_ID ='" . $this->node_id . "' and BATCH_NO ='" . $this->batch_no . "'";
        $activity_info = M('TbatchInfo')->where($where)->find();
        if (!$activity_info) {
            $this->returnError('该活动不存在', '1015');
        }
        if ($activity_info['status'] != '0') {
            $this->returnError('该活动已停用或已过期', '1016');
        }

        // 计算凭证截止时间
        $end_time = date("Ymd235959", strtotime($activity_info["verify_end_date"]));
        if ($activity_info["verify_end_type"] == '1') {
            $end_time = date("Ymd235959", strtotime("+" . $activity_info["verify_end_date"] . " days"));
        }

        $err_num = 0;
        $err_msg = '';
        for ($i = 1; $i <= $this->send_num; $i++) {
            $TransactionID = date("YmdHis") . rand(100000, 999999); // 凭证发送单号

            $RemoteRequest = D('RemoteRequest', 'Service');
            // 第三步，请求营帐扣费
            $req_array = array(
                    'SendCodeReq' => array(
                            'TransactionID' => $TransactionID,
                            'SystemID'      => C('YZ_SYSTEM_ID'),
                            'NodeID'        => $this->node_id,
                            'ContractID'    => $node_info['contract_no'],
                            'OrderID'       => $this->request_id,
                            'CodeType'      => '00',
                            'PosCode'       => $this->pos_id,
                            'ActivityID'    => $this->batch_no,
                    ),
            );

            $resp_array = $RemoteRequest->requestYzServ($req_array);
            $this->log(print_r($resp_array, true), 'resp_array---debug:');
            $resp_array = current($resp_array);
            $ret_msg    = $resp_array['Status'];
            $this->log(print_r($ret_msg, true), 'ret_msg---debug');

            if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
                $err_num++;
                $err_msg = $ret_msg['StatusText'] ? $ret_msg['StatusText'] : '营帐扣费异常';
                continue;
            }

            // 第四步，请求支撑发码
            $req_array  = array(
                    'SubmitVerifyReq' => array(
                            'SystemID'      => C('ISS_SYSTEM_ID'),
                            'ISSPID'        => $this->node_id,
                            'TransactionID' => $TransactionID,
                            'Recipients'    => array(
                                    'Number' => $this->phone_no,
                            ),
                            'SendClass'     => 'BMS',
                            'Messages'      => array(
                                    'Sms' => array(
                                            'Text' => iconv("UTF-8", "GBK", $activity_info['use_rule']),
                                    ),
                                    'Mms' => array(
                                            'Subject' => iconv("UTF-8", "GBK", $activity_info["info_title"]),
                                            'Text'    => iconv("UTF-8", "GBK", $activity_info['use_rule']),
                                    ),
                            ),
                            'ActivityInfo'  => array(
                                    'ActivityID' => $this->batch_no,
                                    'BeginTime'  => date('Ymd000000', strtotime($activity_info['verify_bagin_date'])),
                                    'EndTime'    => $end_time,
                                    'OrgTimes'   => $activity_info["validate_times"],
                                    'OrgAmt'     => $activity_info['batch_amt'],
                                    'PrintText'  => iconv("UTF-8", "GBK", $activity_info['use_rule']),
                            ),
                            'BmpFlag'       => 0,
                            'CustomArea'    => $this->request_id,
                    ),
            );
            $resp_array = $RemoteRequest->requestIssServ($req_array);
            $ret_msg    = $resp_array['SubmitVerifyRes']['Status'];

            // 第五步,记流水
            $barcode_trace               = array();
            $barcode_trace['req_seq']    = $TransactionID;
            $barcode_trace['sys_seq']    = $resp_array['SubmitVerifyRes']['MessageID'];
            $barcode_trace['request_id'] = $this->request_id;
            $barcode_trace['node_id']    = $this->node_id;
            $barcode_trace['user_id']    = $this->user_id;
            $barcode_trace['pos_id']     = $this->pos_id;
            $barcode_trace['batch_no']   = $this->batch_no;
            $barcode_trace['phone_no']   = $this->phone_no;
            $barcode_trace['trans_time'] = date('YmdHis');
            $barcode_trace['ret_code']   = $ret_msg['StatusCode'];
            $barcode_trace['ret_desc']   = $ret_msg['StatusText'];
            $barcode_trace['trans_type'] = '0001';
            $barcode_trace['trans_type'] = $ret_msg['StatusCode'] > '0001' ? '3' : '0';
            $rs                          = M('TbarcodeTrace')->add($barcode_trace);

            if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
                $err_num++;
                $err_msg = $ret_msg['StatusText'];

                // 发码失败，营帐冲正
                $OriTransactionID = $TransactionID;
                $TransactionID    = date("YmdHis") . rand(100000, 999999);

                $req_array = array(
                        'SendCodeReverseReq' => array(
                                'TransactionID'    => $TransactionID,
                                'OriTransactionID' => $OriTransactionID,
                                'SystemID'         => C('YZ_SYSTEM_ID'),
                                'NodeID'           => $this->node_id,
                                'ContractID'       => $node_info['contract_no'],
                                'OrderID'          => $this->request_id,
                                'CodeType'         => '00',
                        ),
                );

                for ($j = 0; $j < 10; $j++) // 最多冲正10次
                {
                    $resp_array = $RemoteRequest->requestYzServ($req_array);
                    $ret_msg    = $resp_array['SendCodeReverseRes']['Status'];

                    if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
                        sleep(3);
                        continue;
                    } else {
                        break;
                    }
                }
            }
        }

        // 应答
        $succ_num  = $this->send_num - $err_num;
        $resp_desc = '发码数：' . $this->send_num . ' 成功数:' . $succ_num;
        if ($err_num > 0) {
            $resp_desc = $resp_desc . ' 失败数:' . $err_num . ' 失败原因:' . $err_msg;
        }

        $this->returnSuccess($resp_desc, array(
                        "all_num"     => $this->send_num,
                        "success_num" => $succ_num,
                        "error_num"   => $err_num,
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
        if ($this->batch_no == '') {
            return array(
                    'resp_desc' => '活动号不能为空！',
            );

            return false;
        }
        if ($this->phone_no == '') {
            return array(
                    'resp_desc' => '手机号不能为空！',
            );

            return false;
        }
        if ($this->send_num == '') {
            return array(
                    'resp_desc' => '发送次数不能为空！',
            );

            return false;
        }
        if ($this->request_id == '') {
            return array(
                    'resp_desc' => '请求流水号不能为空！',
            );

            return false;
        }

        if ($this->send_num <= 0) {
            return array(
                    'resp_desc' => '发送次数必须大于0！',
            );

            return false;
        }

        return true;
    }
}

?>