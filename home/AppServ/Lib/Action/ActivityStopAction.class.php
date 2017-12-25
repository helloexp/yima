<?php

/**
 * 功能：活动停止
 *
 * @author siht 时间：2013-06-24
 */
class ActivityStopAction extends BaseAction {

    public $batch_no;

    // 活动号
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin();
        $this->batch_no = I('batch_no'); // 活动号
    }

    public function run() {
        $rs = $this->check();
        if (!($rs === true)) {
            $this->returnError($rs['resp_desc']);
        }

        $TransactionID  = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
        $ActivityStatus = "3";

        $where = " BATCH_NO ='" . $this->batch_no . "'";

        // 1.根据活动号查询活动号信息
        $activity      = D("Activity", 'Service');
        $activity_info = $activity->getActivityInfo($where);
        if ($activity_info) {
            $batch_name       = $activity_info["batch_name"];
            $batch_short_name = $activity_info["batch_short_name"];
            $begin_time       = $activity_info["begin_time"];
            $end_time         = $activity_info["end_time"];
            $node_id          = $activity_info["node_id"];
            $material_code    = $activity_info["material_code"];
        } else {
            $resp_desc = "找不到此活动";
            $this->returnError($resp_desc, '1004');
        }

        // 2.请求支撑，请求参数
        $req_array = array(
                'ActivityModifyReq' => array(
                        'SystemID'       => C('ISS_SYSTEM_ID'),
                        'ISSPID'         => $this->node_id,
                        'TransactionID'  => $TransactionID,
                        'ActivityID'     => $this->batch_no,
                        'ActivityStatus' => '3',
                        'ActivityInfo'   => array(
                                'CustomNo'          => $material_code,
                                'ActivityName'      => $batch_name,
                                'ActivityShortName' => $batch_short_name,
                                'BeginTime'         => $begin_time,
                                'EndTime'           => $end_time,
                        ),
                ),
        );

        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array    = $RemoteRequest->requestIssServ($req_array);
        $ret_msg       = $resp_array['ActivityModifyReq']['Status'];

        if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
            $resp_desc = "支撑应答：" . $ret_msg['StatusText'];
            $this->returnError($resp_desc, $ret_msg['StatusCode']);
        }

        // 3.本地活动停止
        $rs = $activity->updateActivity(array(
                "status" => '1',
        ), $where);
        if ($rs !== false) {
            $resp_desc = "活动停止成功";
            $this->returnSuccess($resp_desc, '');
        } else {
            $resp_desc = "活动停止失败";
            $this->returnError($resp_desc, '1006');
        }
    }

    private function check() {
        if (!$this->node_id) {
            return array(
                    'resp_desc' => '商户号不能为空！',
            );

            return false;
        }
        if (!$this->user_id) {
            return array(
                    'resp_desc' => '用户id不能为空！',
            );

            return false;
        }
        if (!$this->pos_id) {
            return array(
                    'resp_desc' => '终端号不能为空！',
            );

            return false;
        }
        if (!$this->batch_no) {
            return array(
                    'resp_desc' => '活动号不能为空！',
            );

            return false;
        }

        return true;
    }
}

?>