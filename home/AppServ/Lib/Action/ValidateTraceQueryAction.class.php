<?php

/**
 * 功能：验证流水查询(当日)
 *
 * @author wangtr 时间：2013-06-25
 */
class ValidateTraceQueryAction extends BaseAction {

    public $node_id;
    // 商户号
    public $user_id;
    // 用户号
    public $pos_id;
    // 终端号
    public $current_page;
    // 当前页
    public $page_size;
    // 每页大小
    public $query_pos_id;
    // 查询的终端号
    public $query_batch_no;

    // 查询的活动号
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
            $this->returnError($resp_desc);
        }
        $dao = M()->table('tpos_trace a')->join(array(
                'tgoods_info b on b.batch_no=a.batch_no',
        ));
        // 默认按机构隔离
        $where = "a.NODE_ID ='" . $this->node_id . "'";
        // 当天的时间
        $begin_time = date("Ymd") . "000000";
        if ($begin_time != '') {
            $where .= " and a.TRANS_TIME >= '" . $begin_time . "'";
        }
        // 验证流水且 操作成功
        $where .= " and a.FLOW_STATUS = '1' and a.TRANS_TYPE ='0' and a.STATUS = '0'";

        // 以下是可选查询条件
        if ($this->query_pos_id != '') {
            $where .= " and a.POS_ID = '" . $this->query_pos_id . "'";
        }
        if ($this->query_batch_no != '') {
            $where .= " and a.BATCH_NO = '" . $this->query_batch_no . "'";
        }
        $start    = ($this->current_page - 1) * $this->page_size;
        $pos_info = $dao->where($where)->field("a.PHONE_NO, a.ID as FLOW_ID, a.POS_ID, a.BATCH_NO, b.GOODS_NAME as BATCH_SHORT_NAME, a.TRANS_TIME")->limit($start,
                $this->page_size)->select();

        if (!empty($pos_info)) {
            $resp_desc = "验证流水查询成功";

            foreach ($pos_info as &$value) {
                $value               = array_change_key_case($value, CASE_LOWER);
                $value['trans_time'] = date("Y-m-d H:i:s", strtotime($value['trans_time']));
            }
            unset($value);

            $all_count                 = $dao->where($where)->count();
            $page_info                 = array();
            $page_info['page_size']    = $this->page_size;
            $page_info['current_page'] = $this->current_page;
            $page_info['total_num']    = $all_count;

            $this->returnSuccess($resp_desc, array(
                            'validate_list' => $pos_info,
                            'page_nav'      => $page_info,
                    ));
        } else {
            $resp_desc = "找不到任何验证流水";
            $this->returnError($resp_desc);
        }
    }

    private function check() {
        if (empty($this->node_id) || empty($this->pos_id) || empty($this->user_id) || empty($this->current_page) || empty($this->page_size)) {
            return false;
        }

        return true;
    }
}

?>