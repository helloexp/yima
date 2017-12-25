<?php

/**
 * todo 要管
 * 功能：凭证重发
 *
 * @author siht 时间：2013-06-26
 */
class CodeResendReqAction extends BaseAction {

    public $node_id;
    // 商户号
    public $user_id;
    // 操作员
    public $request_id;
    // 源请求流水号
    public $phone_no;

    public function _initialize() {
        parent::_initialize();
        // 动态载入 CodeSendForLable 配置文件
        C('CodeSendForLable', require(CONF_PATH . 'configCodeSendForLable.php')); // 和内部发码接口共进退
        $this->node_id    = I('node_id');
        $this->user_id    = I('user_id');
        $this->request_id = I('request_id');
        $this->phone_no   = I('phone_no');
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

        // 第二步,获取发码流水
        $where        = "NODE_ID ='" . $this->node_id . "' and request_id ='" . $this->request_id . "' and trans_type = '0001'";
        $barcode_info = M('TbarcodeTrace')->where($where)->find();
        if (!$barcode_info) {
            // 锁定查找是否在异步发送缓存表待发，是待发的话，修改异步发送缓存表记录，如果不是待发状态则再次查找发码流水（避免当前记录在锁定前被发送处理）
            // 开启事务锁记录
            M()->startTrans();
            $where                 = "NODE_ID ='" . $this->node_id . "' and request_id ='" . $this->request_id . "'";
            $send_award_trace_info = M('tsend_award_trace')->where($where)->lock(true)->find();
            if (!$send_award_trace_info) { // 未找到记录 不是异步处理数据
                M()->rollback();
                $this->returnError('该凭证不存在', '7019');
            } else {
                // 从tcj_trace取出发送的号码
                $cj_trace_info = M('tcj_trace')->where("
                    batch_id = " . $send_award_trace_info['m_id'] . "
                    and request_id = '" . $send_award_trace_info['request_id'] . "'")->find();
                if (!$cj_trace_info) {
                    M()->rollback();
                    $this->returnError('该凭证不存在', '7018');
                }
                $send_mobile = $cj_trace_info['send_mobile'];
                if ($send_award_trace_info['deal_flag'] == '1' || ($send_award_trace_info['deal_flag'] == '0' && in_array($send_award_trace_info['trans_type'], [3,4]))) { // 找到记录，尚未被发送，直接修改发送的号码
                    $send_award_trace_info_update['phone_no'] = $send_mobile;
                    $send_award_trace_info_update['ims_flag'] = '1';
                    $send_award_trace_info_update['deal_flag'] = '1';
                    $rs                                       = M('tsend_award_trace')->where($where)->save($send_award_trace_info_update);
                    if (!$rs) {
                        M()->rollback();
                        $this->returnError('该凭证不存在', '7020');
                    }
                    // 提交事务 退出处理
                    M()->commit();
                    $this->returnSuccess($resp_desc, array(
                            "message_id" => $resp_array['ResendRes']['MessageID'],
                    ));
                } else { // 找到记录，但状态非待发，可能在这瞬间已经被处理
                    // 重新查找TbarcodeTrace记录，后修改TbarcodeTrace中的手机号码，走入正常重发流程
                    $where        = "NODE_ID ='" . $this->node_id . "' and request_id ='" . $this->request_id . "' and trans_type = '0001'";
                    $barcode_info = M('TbarcodeTrace')->where($where)->find();
                    if (!$barcode_info) {
                        M()->rollback();
                        $this->returnError('该凭证不存在', '7015');
                    }
                    $barcode_info_update['phone_no'] = $send_mobile;
                    $rs                              = M('TbarcodeTrace')->where($where)->save($barcode_info_update);
                    if (!$rs) {
                        M()->rollback();
                        $this->returnError('该凭证不存在', '7017');
                    }
                }
            }
            // 提交事务
            M()->commit();

        }
        if ($barcode_info['status'] != '0') {
            $this->returnError('该凭证已撤销或失败', '9003');
        }

        // 请求支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 凭证重发单号
        $RemoteRequest = D('RemoteRequest', 'Service');
        if ($this->phone_no != null && strlen($this->phone_no) > 10) {
            $barcode_info['phone_no'] = $this->phone_no;
        }
        // 判断是否马上送的数据
        $goods_info = M('tgoods_info')->where("goods_id = '" . $barcode_info['goods_id'] . "'")->find();
        if (!$goods_info) {
            $this->returnError('该卡券不存在', '7016');
        }
        if ($goods_info['goods_type'] == '11') { // 11 马上发商品
            // 调用马上发接口重发
            $req_data              = "prom_key=" . $goods_info['pts_batch_key'] . '&req_seq=' . $barcode_info['req_seq'] . "&resend_req_seq=" . $TransactionID;
            $resp_array            = $RemoteRequest->requestPtsServResend($req_data);
            $ret_msg['StatusCode'] = $resp_array['resp_id'];
            $ret_msg['StatusText'] = $resp_array['resp_str'];
        } else {
            // 请求参数
            $req_array = array(
                    'ResendReq' => array(
                            'TransactionID'    => $TransactionID,
                            'OriTransactionID' => $barcode_info['req_seq'],
                            'SystemID'         => C('ISS_SYSTEM_ID'),
                            'ISSPID'           => $this->node_id,
                            'SendLevel'        => 7,
                            'DataFrom'         => 1,
                            'Recipients'       => array(
                                    'Number' => $barcode_info['phone_no'],
                            ),
                            'SendClass'        => 'SAM',
                            'BmpFlag'          => 0,
                    ),
            );

            $resp_array = $RemoteRequest->requestIssServ($req_array);
            $ret_msg    = $resp_array['ResendRes']['Status'];
        }
        // save trace
        $tbarcode_trace_send['req_seq']     = $TransactionID;
        $tbarcode_trace_send['org_req_seq'] = $barcode_info['req_seq'];
        $tbarcode_trace_send['trans_type']  = '0003';
        $tbarcode_trace_send['phone_no']    = $barcode_info['phone_no'];
        $tbarcode_trace_send['trans_time']  = date('YmdHis');
        $tbarcode_trace_send['ret_code']    = $ret_msg['StatusCode'];
        $tbarcode_trace_send['ret_desc']    = $ret_msg['StatusText'];
        if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
            if ($ret_msg['StatusCode'] == '5016') {
                $changeResendFlag = M('tbarcode_trace')
                ->where(array('req_seq' => $barcode_info['req_seq']))
                ->save(array('resend_allow_flag' => '1'));
                if (false === $changeResendFlag) {
                    $this->log('超过3次重发之后，修正tbarcode_trace的resend_allow_flag失败' . M()->_sql());
                }
            }
            $tbarcode_trace_send['status'] = '2';
            $rs                            = M()->table('tbarcode_trace_send')->add($tbarcode_trace_send);
            if ($rs === false) {
                $this->log('保存发送流水失败' . M()->_sql());
            }
            $resp_desc = "支撑应答：" . $ret_msg['StatusText'];
            $this->returnError($resp_desc, $ret_msg['StatusCode']);
        }

        $tbarcode_trace_send['status'] = '1';
        $rs                            = M()->table('tbarcode_trace_send')->add($tbarcode_trace_send);
        if ($rs === false) {
            $this->log('保存发送流水失败' . M()->_sql());
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
