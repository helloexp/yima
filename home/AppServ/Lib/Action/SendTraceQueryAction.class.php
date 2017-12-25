<?php

/**
 * 功能：发码流水查询(24小时内)
 *
 * @author cxz 时间：2013-03-07
 */
class SendTraceQueryAction extends BaseAction {

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
            $this->returnError($resp_desc);
        }
        $dao = M()->table('tbarcode_trace a')->join(array(
                'tgoods_info b on b.batch_no=a.batch_no',
        ));

        // 基本数据隔离
        $where = "a.NODE_ID ='" . $this->node_id . "' and a.status in ('0','1') and trans_type = '0001'";
        // 24小时前的时间戳
        $begin_time = date('YmdHis', strtotime("-24 hours"));
        if ($begin_time != '') {
            $where .= " and a.TRANS_TIME >= '" . $begin_time . "'";
        }

        // 以下是可选条件
        if ($this->query_pos_id != '') {
            $where .= " and a.POS_ID = '" . $this->query_pos_id . "'";
        }
        if ($this->query_batch_no != '') {
            $where .= " and a.BATCH_NO = '" . $this->query_batch_no . "'";
        }
        $start     = ($this->current_page - 1) * $this->page_size;
        $send_info = $dao->where($where)->field("a.PHONE_NO, a.req_seq as transaction_id, a.POS_ID, a.BATCH_NO, a.TRANS_TIME, a.STATUS, b.GOODS_NAME as BATCH_SHORT_NAME")->limit($start,
                        $this->page_size)->order("a.trans_time desc")->select();

        if (!empty($send_info)) {
            $resp_desc = "发码流水查询成功";

            foreach ($send_info as &$value) {
                $value               = array_change_key_case($value, CASE_LOWER);
                $value['trans_time'] = date("Y-m-d H:i:s", strtotime($value['trans_time']));
            }
            unset($value);

            $all_count                 = $dao->table('tbarcode_trace a')->where($where)->count();
            $page_info                 = array();
            $page_info['page_size']    = $this->page_size;
            $page_info['current_page'] = $this->current_page;
            $page_info['total_num']    = $all_count;

            $this->returnSuccess($resp_desc, array(
                            'send_list' => $send_info,
                            'page_nav'  => $page_info,
                    ));
        } else {
            $resp_desc = "找不到任何发码流水";
            $this->returnSuccess($resp_desc);
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