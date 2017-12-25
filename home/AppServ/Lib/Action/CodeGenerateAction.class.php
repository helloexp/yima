<?php

/**
 * 功能：生成验证码
 *
 * @author siht 时间：2013-07-07
 */
class CodeGenerateAction extends BaseAction {

    public $node_id;
    // 商户号
    public $batch_id;
    // 活动id
    public $batch_type;
    // 活动类型
    public $total_count;

    // 生成数量
    public function _initialize() {
        parent::_initialize();
        // 初始化请求参数
        $this->node_id     = I('node_id');
        $this->batch_id    = I('batch_id');
        $this->batch_type  = I('batch_type');
        $this->total_count = I('total_count');
    }

    // 生成随机码
    public function random_code($length) {
        $returnStr = '';
        $pattern   = '1234567890abcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i < $length; $i++) {
            $returnStr .= $pattern{mt_rand(0, 35)}; // 生成随机数
        }

        return $returnStr;
    }

    public function run() {
        $rs = $this->check();

        if (!$rs) {
            $resp_desc = "请填写商户号、活动id、活动类型、生成数";
            $this->returnError($resp_desc);
        }

        // (1)商户校验
        /*
         * $where = "NODE_ID ='".$this->node_id."'"; $node_info =
         * M('TnodeInfo')->where($where)->find(); if(!$node_info) {
         * $this->returnError('商户不存在','1011'); } if($node_info['status'] != '0')
         * { $this->returnError('商户已停用','1012'); }
         */

        $err_num = 0;
        // (2)制码
        for ($i = 1; $i <= $this->total_count; $i++) {
            // 1.生成码
            $verify_code = $this->random_code(6);
            $where       = "node_id ='" . $this->node_id . "' and verify_code = '" . $verify_code . "'";
            $code        = M('TcodeVerify')->where($where)->find();
            if ($code) // 生成重复
            {
                $i--;
                continue;
            }

            // 2.写入数据表
            $code_info                = array();
            $code_info['node_id']     = $this->node_id;
            $code_info['verify_code'] = $verify_code;
            $code_info['status']      = 0;
            $code_info['batch_id']    = $this->batch_id;
            $code_info['batch_type']  = $this->batch_type;
            $code_info['add_time']    = date("YmdHis");

            $rs = M('TcodeVerify')->add($code_info);
            if (!$rs) {
                $this->log(print_r($code_info, true));
                $this->log("写入[tcode_verify]失败");
                $err_num++;
            }

            // echo(print_r($code_info,true));
        }

        // 第二步,取活动信息
        /*
         * $where = "NODE_ID ='".$this->node_id."' and BATCH_NO
         * ='".$this->batch_no."'"; $activity_info =
         * M('TbatchInfo')->where($where)->find(); if(!$activity_info) {
         * $this->returnError('该活动不存在','1015'); } if($activity_info['status'] !=
         * '0') { $this->returnError('该活动已停用或已过期','1016'); } //计算凭证截止时间
         * $end_time = date("Ymd235959",
         * strtotime($activity_info["verify_end_date"]));
         * if($activity_info["verify_end_type"] == '1') { $end_time =
         * date("Ymd235959",strtotime("+".$activity_info["verify_end_date"]."
         * days")); }
         * //计算凭证开始时间 $begin_time = date("Ymd000000",
         * strtotime($activity_info["verify_begin_date"]));
         * if($activity_info["verify_begin_type"] == '1') { $begin_time =
         * date("Ymd000000",strtotime("+".$activity_info["verify_begin_date"]."
         * days")); }
         *
         * $err_num = 0; $err_msg = '';
         * $this->send_num = 1; $this->pos_id = $pos_info['pos_id'];
         * for($i=1; $i<= $this->send_num; $i++) { $TransactionID =
         * date("YmdHis").rand(100000,999999); //凭证发送单号
         * $RemoteRequest = D('RemoteRequest','Service'); //第三步，请求营帐扣费
         * $req_array = array( 'SendCodeReq'=>array(
         * 'TransactionID'=>$TransactionID, 'SystemID'=>C('YZ_SYSTEM_ID'),
         * 'NodeID'=>$this->node_id, 'ContractID'=>$node_info['contract_no'],
         * 'OrderID'=>$this->request_id, 'CodeType'=>'00', 'PosCode' =>
         * $pos_info['pos_id'], 'ActivityID' => $this->batch_no ) );
         * $resp_array = $RemoteRequest->requestYzServ($req_array);
         * $this->log(print_r($resp_array,true),'resp_array---debug:');
         * $resp_array = current($resp_array); $ret_msg = $resp_array['Status'];
         * $this->log(print_r($ret_msg,true),'ret_msg---debug');
         * if(!$resp_array || ( $ret_msg['StatusCode'] != '0000' &&
         * $ret_msg['StatusCode'] != '0001' ) ) { $err_num++; $err_msg =
         * $ret_msg['StatusText'] ? $ret_msg['StatusText'] : '营帐扣费异常'; continue;
         * }
         * //第四步，请求支撑发码 $req_array = array( 'SubmitVerifyReq'=>array(
         * 'SystemID'=>C('ISS_SYSTEM_ID'), 'ISSPID'=>$this->node_id,
         * 'TransactionID'=>$TransactionID, 'Recipients'=>array(
         * 'Number'=>$this->phone_no ), 'SendClass'=>'BMS', 'Messages'=>array(
         * 'Sms'=>array( 'Text'=>$activity_info['use_rule'] ), 'Mms'=>array(
         * 'Subject'=>$activity_info["info_title"],
         * 'Text'=>$activity_info['use_rule'] ) ), 'ActivityInfo'=>array(
         * 'ActivityID'=>$this->batch_no, 'BeginTime'=>$begin_time,
         * 'EndTime'=>$end_time, 'OrgTimes'=>$activity_info["validate_times"],
         * 'OrgAmt'=>$activity_info['batch_amt'],
         * 'PrintText'=>$activity_info['use_rule'] ), 'BmpFlag'=>0,
         * 'SendLevel'=>$this->send_level, 'CustomArea'=>$this->request_id ) );
         * $resp_array = $RemoteRequest->requestIssServ($req_array); $ret_msg =
         * $resp_array['SubmitVerifyRes']['Status'];
         * //第五步,记流水 $barcode_trace = array(); $barcode_trace['req_seq'] =
         * $TransactionID; $barcode_trace['sys_seq'] =
         * $resp_array['SubmitVerifyRes']['MessageID'];
         * $barcode_trace['request_id'] = $this->request_id;
         * $barcode_trace['node_id'] = $this->node_id; $barcode_trace['user_id']
         * = $this->user_id; $barcode_trace['pos_id'] = $this->pos_id;
         * $barcode_trace['batch_no'] = $this->batch_no;
         * $barcode_trace['phone_no'] = $this->phone_no;
         * $barcode_trace['trans_time'] = date('YmdHis');
         * $barcode_trace['ret_code'] = $ret_msg['StatusCode'];
         * $barcode_trace['ret_desc'] = $ret_msg['StatusText'];
         * $barcode_trace['trans_type'] = '0001'; $barcode_trace['status'] =
         * $ret_msg['StatusCode'] > '0001'?'3':'0'; $barcode_trace['data_from']
         * = $this->data_from; $barcode_trace['batch_id'] = $this->batch_id; $rs
         * = M('TbarcodeTrace')->add($barcode_trace); if(!$rs) {
         * $this->log(print_r($barcode_trace,true));
         * $this->log("记录流水信息[tbarcode_trace]失败"); }
         * if(!$resp_array || ($ret_msg['StatusCode'] != '0000' &&
         * $ret_msg['StatusCode'] != '0001')) { $err_num++; $err_msg =
         * $ret_msg['StatusText'];
         * //发码失败，营帐冲正 $OriTransactionID = $TransactionID; $TransactionID =
         * date("YmdHis").rand(100000,999999);
         * $req_array = array( 'SendCodeReverseReq'=>array(
         * 'TransactionID'=>$TransactionID,
         * 'OriTransactionID'=>$OriTransactionID, 'SystemID'=>C('YZ_SYSTEM_ID'),
         * 'NodeID'=>$this->node_id, 'ContractID'=>$node_info['contract_no'],
         * 'OrderID'=>$this->request_id, 'CodeType'=>'00' ) );
         * for($j=0; $j<10; $j++)//最多冲正10次 { $resp_array =
         * $RemoteRequest->requestYzServ($req_array); $ret_msg =
         * $resp_array['SendCodeReverseRes']['Status'];
         * if(!$resp_array || ($ret_msg['StatusCode'] != '0000' &&
         * $ret_msg['StatusCode'] != '0001') ) { sleep(3); continue; } else
         * break;
         * } }
         * }
         * //成功数 $succ_num = $this->send_num-$err_num;
         * //第六步，记录发码统计 $where = "NODE_ID ='".$this->node_id."' and BATCH_NO
         * ='".$this->batch_no."' and POS_ID ='".$this->pos_id."' and TRANS_DATE
         * ='".date('Y-m-d')."'"; $pos_day_count =
         * M('TposDayCount')->where($where)->find(); if(!$pos_day_count) {
         * $pos_day_count['node_id'] = $this->node_id; $pos_day_count['pos_id']
         * = $this->pos_id; $pos_day_count['batch_no'] = $this->batch_no;
         * $pos_day_count['trans_date'] = date('Y-m-d');
         * $pos_day_count['send_num'] = $succ_num; $pos_day_count['send_amt'] =
         * $activity_info['batch_amt']*$succ_num; $pos_day_count['verify_num'] =
         * 0; $pos_day_count['verify_amt'] = 0; $pos_day_count['cancel_num'] =
         * 0; $pos_day_count['cancel_amt'] = 0; $rs =
         * M('TposDayCount')->add($pos_day_count); if(!$rs) {
         * $this->log(print_r($pos_day_count,true));
         * $this->log("记录统计信息[tpos_day_count]失败"); } } else { $new_day_count =
         * array(); $new_day_count['send_num'] = $pos_day_count['send_num'] +
         * $succ_num; $new_day_count['send_amt'] = $pos_day_count['send_amt'] +
         * $activity_info['batch_amt']*$succ_num; $rs =
         * M('TposDayCount')->where($where)->save($new_day_count); if(!$rs) {
         * $this->log(print_r($new_day_count,true));
         * $this->log("更新统计信息[tpos_day_count]失败"); }
         * }
         * //应答 $resp_desc ='发码数：'.$this->send_num.' 成功数:'.$succ_num;
         * if($err_num > 0) { $resp_desc = $resp_desc. ' 失败数:'.$err_num.'
         * 失败原因:'.$err_msg; $this->returnError($resp_desc); }
         */

        $this->returnSuccess($resp_desc, array(
                        "code_num" => $this->total_count,
                        "err_num"  => $err_num,
                ));
    }

    private function check() {
        if ($this->node_id == '' || $this->batch_id == '' || $this->batch_type == '' || $this->total_count == '') {
            return false;
        }

        return true;
    }
}

?>