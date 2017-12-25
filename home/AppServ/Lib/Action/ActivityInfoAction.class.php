<?php

/**
 * 功能：活动详情
 *
 * @author siht 时间：2013-06-24
 */
class ActivityInfoAction extends BaseAction {

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

        $activity      = D("Activity", 'Service');
        $where         = "BATCH_NO = '" . $this->batch_no . "' and NODE_ID ='" . $this->node_id . "'";
        $activity_info = $activity->getActivityInfo($where);

        if (!empty($activity_info)) {
            $resp_desc                       = "获取活动详情成功";
            $batch_info                      = array();
            $batch_info['batch_name']        = $activity_info['batch_name'];
            $batch_info['batch_short_name']  = $activity_info['batch_short_name'];
            $batch_info['begin_time']        = date("Y-m-d", strtotime($activity_info['begin_time']));
            $batch_info['end_time']          = date("Y-m-d", strtotime($activity_info['end_time']));
            $batch_info['batch_no']          = $activity_info['batch_no'];
            $batch_info['batch_class']       = $activity_info['batch_class'];
            $batch_info['batch_class_text']  = D('Dict')->getBatchClass($activity_info['batch_class']);
            $batch_info['batch_game_flag']   = '0';
            $batch_info['batch_status']      = $activity_info['status'];
            $batch_info['batch_status_text'] = D('Dict')->getBatchStatus($activity_info['status']);
            $batch_info['batch_amt']         = $activity_info['batch_amt'];
            $batch_info['batch_discount']    = $activity_info['batch_discount'];
            $batch_info['send_begin_date']   = $activity_info['send_begin_date'];
            $batch_info['send_end_date']     = $activity_info['send_end_date'];
            $batch_info['verify_begin_date'] = $activity_info['verify_begin_date'];
            $batch_info['verify_end_date']   = $activity_info['verify_end_date'];
            $batch_info['verify_begin_type'] = $activity_info['verify_begin_type'];
            $batch_info['verify_end_type']   = $activity_info['verify_end_type'];
            $batch_info['material_code']     = $activity_info['material_code'];
            $batch_info['join_rule']         = $activity_info['join_rule'];
            $batch_info['use_rule']          = $activity_info['use_rule'];
            $batch_info['batch_img']         = $activity_info['batch_img'];
            $batch_info['info_title']        = $activity_info['info_title'];
            // $batch_info['validate_times'] = $activity_info['VALIDATE_TIMES'];

            $batch_info['send_begin_date'] = date("Y-m-d", strtotime($batch_info['send_begin_date']));
            $batch_info['send_end_date']   = date("Y-m-d", strtotime($batch_info['send_end_date']));

            // 格式化一下时间
            if ($batch_info['verify_begin_type'] == '0') {
                $batch_info['verify_begin_date'] = date("Y-m-d", strtotime($batch_info['verify_begin_date']));
            }
            // 格式化一下时间
            if ($batch_info['verify_end_type'] == '0') {
                $batch_info['verify_end_date'] = date("Y-m-d", strtotime($batch_info['verify_end_date']));
            }

            $this->returnSuccess($resp_desc, $batch_info);
        } else {
            $resp_desc = "找不到此活动的详情";
            $this->returnError($resp_desc, '1004');
        }
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

        return true;
    }
}

?>