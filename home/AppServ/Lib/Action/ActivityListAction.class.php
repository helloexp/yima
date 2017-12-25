<?php

/**
 * 功能：活动列表
 *
 * @author siht 时间：2013-06-24
 */
class ActivityListAction extends BaseAction {

    public $batch_class;
    // 活动类型
    public $batch_status;
    // 活动状态
    public $current_page;
    // 当前页
    public $page_size;

    // 每页大小
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin();
        $this->batch_class  = I('batch_class'); // 活动类型
        $this->batch_status = I('batch_status'); // 活动状态
        $this->current_page = I('current_page'); // 当前页
        $this->page_size    = I('page_size'); // 每页大小
    }

    public function run() {
        $rs = $this->check();
        if (!($rs === true)) {
            $this->returnError($rs['resp_desc']);
        }
        $todayTime = date('YmdHis');

        $activity = D("Activity", 'Service');
        $where    = "NODE_ID ='" . $this->node_id . "'";

        if (isset($this->batch_class) && $this->batch_class != '') {
            $where .= " and BATCH_CLASS = '" . $this->batch_class . "'";
        }
        if (isset($this->batch_status) && $this->batch_status != '') {
            // $where .= " and STATUS = '".$this->batch_status."'";
            // 如果为0,正常
            if ($this->batch_status == '0') {
                $where .= " and STATUS = '0'" . " and END_TIME >= '" . $todayTime . "'" . // 未到活动结束时间
                        " and SEND_BEGIN_DATE <= '" . $todayTime . "'" . // 已到发送开始时间
                        " and SEND_END_DATE >= '" . $todayTime . "'"; // 未到发送结束时间
            }

            // 如果为2,过期
            if ($this->batch_status == '2') {
                $where .= " and STATUS in ('0','1','2') and END_TIME < '" . date("YmdHis") . "'";
            }

            // 如果为3,停发
            if ($this->batch_status == '3') {
                $where .= " and STATUS = '1'";
            }
        }

        $activity_info = $activity->searchActivity($where,
                "BATCH_NAME, BATCH_SHORT_NAME, BEGIN_TIME, END_TIME, BATCH_NO, BATCH_CLASS, STATUS AS BATCH_STATUS, BATCH_AMT, BATCH_DISCOUNT, SEND_BEGIN_DATE,SEND_END_DATE,VERIFY_BEGIN_DATE,VERIFY_END_DATE,VERIFY_BEGIN_TYPE,VERIFY_END_TYPE, CASE WHEN timestamp(END_TIME) < now() then  2 else 0 END AS STATUS_ORDER",
                $this->current_page, $this->page_size);
        if (!empty($activity_info)) {
            $resp_desc = "获取活动列表成功";
            foreach ($activity_info as &$value) {
                $value                      = array_change_key_case($value, CASE_LOWER);
                $value['batch_class_text']  = D('Dict')->getBatchClass($value['batch_class']);
                $value['batch_status_text'] = D('Dict')->getBatchStatus($value['batch_status']);
                $value['begin_time']        = date("Y-m-d H:i:s", strtotime($value['begin_time']));
                $value['end_time']          = date("Y-m-d H:i:s", strtotime($value['end_time']));
            }
            unset($value);

            $all_count                 = $activity->countActivity($where);
            $page_info                 = array();
            $page_info['page_size']    = $this->page_size;
            $page_info['current_page'] = $this->current_page;
            $page_info['total_num']    = $all_count;

            $this->returnSuccess($resp_desc, array(
                            'activity_list' => $activity_info,
                            'page_nav'      => $page_info,
                    ));
        } else {
            $resp_desc = "无活动";
            $this->returnError($resp_desc, '1005');
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
        if ($this->current_page == '') {
            return array(
                    'resp_desc' => '当前页不能为空！',
            );

            return false;
        }
        if ($this->page_size == '') {
            return array(
                    'resp_desc' => '单页大小不能为空！',
            );

            return false;
        }

        return true;
    }
}

?>