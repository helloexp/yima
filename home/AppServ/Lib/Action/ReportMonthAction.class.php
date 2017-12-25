<?php

/**
 * 功能：数据统计(月报表)
 *
 * @author cxz 时间：2013-03-07 更新时间:2013-6-24
 */
class ReportMonthAction extends BaseAction {

    public $node_id;
    // 商户号
    public $user_id;
    // 用户号
    public $pos_id;
    // 终端号
    // 以下是请求参数
    public $current_page;
    // 当前页
    public $page_size;
    // 每页大小
    public $query_pos_id;
    // 查询终端号
    public $query_batch_no;

    // 查询活动号
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin(); // 校验用户登录
        // 初始化请求参数
        $this->current_page   = I('current_page', 1);
        $this->page_size      = I('page_size');
        $this->query_pos_id   = I('query_pos_id');
        $this->query_batch_no = I('query_batch_no');
    }

    public function run() {
        $rs = $this->check();
        if (!$rs) {
            $resp_desc = "请填写商户号、终端号、用户号、当前页、每页大小";
            $this->returnError($resp_desc, "");
        }

        $where = "NODE_ID ='" . $this->node_id . "'";

        if (isset($_REQUEST['query_pos_id']) && $_REQUEST['query_pos_id'] != '') {
            $where .= " and POS_ID = '" . $_REQUEST['query_pos_id'] . "'";
        }
        if (isset($_REQUEST['query_batch_no']) && $_REQUEST['query_batch_no'] != '') {
            $where .= " and BATCH_NO = '" . $_REQUEST['query_batch_no'] . "'";
        }
        $start     = ($this->current_page - 1) * $this->page_size;
        $stat_info = M('TposDayCount')->field("sum(SEND_NUM) as SEND_NUM, sum(VERIFY_NUM) as VALIDATE_num, sum(CANCEL_NUM) as CANCEL_NUM, substr(TRANS_DATE,1,7) as TRANS_MONTH")->where($where)->group('TRANS_MONTH')->order('TRANS_DATE desc')->limit($start,
                        $this->page_size)->select();

        if (!empty($stat_info)) {

            $resp_desc = "月数据统计成功";

            foreach ($stat_info as &$value) {
                $value = array_change_key_case($value, CASE_LOWER);
            }
            unset($value);

            $all_count                 = M('TBATCH_STAT')->count("DISTINCT substr(TRANS_DATE,1,7)");
            $page_info                 = array();
            $page_info['page_size']    = $this->page_size;
            $page_info['current_page'] = $this->current_page;
            $page_info['total_num']    = $all_count;

            $this->returnSuccess($resp_desc, array(
                            'report_list' => $stat_info,
                            'page_nav'    => $page_info,
                    ));
        } else {
            $resp_desc = "找不到任何月数据";
            $this->returnError($resp_desc, "");
        }
    }

    private function check() {
        if (empty($this->node_id) || empty($this->pos_id) || empty($this->user_id) || empty($this->current_page) || empty($this->page_size)) {
            return false;
        }

        return true;
    }
}