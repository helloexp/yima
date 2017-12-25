<?php

/**
 * 功能：数据统计,按不同的
 *
 * @author cxz @update author bao 时间：2013-03-07 更新时间:2013-6-25
 */
class ReportAction extends BaseAction {

    public $node_id;
    // 商户号
    public $user_id;
    // 用户号
    public $pos_id;
    // 终端号
    public $query_batch_no;

    public $query_pos_id;

    public $req_operate;

    // 请求统计类型，多个参数用,号隔开
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin(); // 校验用户登录
        // 初始化请求参数
        $this->query_batch_no = I('query_batch_no');
        $this->query_pos_id   = I('query_pos_id');
        $this->req_operate    = I('req_operate');
    }

    public function run() {
        $rs = $this->check();
        if (!$rs) {
            $resp_desc = "请填写商户号、终端号、用户号";
            $this->returnError($resp_desc);
        }

        // 数据隔离 没有关联表
        $where = "node_id ='" . $this->node_id . "'";
        // 可选查询条件
        if ($this->query_pos_id != '') {
            $where .= " and pos_id = '" . $this->query_pos_id . "'";
        }
        if ($this->query_batch_no != '') {
            $where .= " and batch_no = '" . $this->query_batch_no . "'";
        }

        // 数据隔离 A，关联tbatch_info
        $where_a = "a.node_id ='" . $this->node_id . "'";
        // 可选查询条件
        if ($this->query_pos_id != '') {
            $where_a .= " and a.pos_id = '" . $this->query_pos_id . "'";
        }
        if ($this->query_batch_no != '') {
            $where_a .= " and a.batch_no = '" . $this->query_batch_no . "'";
        }

        $resp_desc = "数据统计成功";

        // 返回结果
        $return_arr = array();

        // 请求参数为req_operate=batch_dong_num,batch_num
        $req_operate     = $this->req_operate;
        $req_operate_arr = explode(',', $req_operate);

        // 若为空，则全部返回

        if (!$req_operate || in_array('batch_num_doing', $req_operate_arr)) {
            // 进行中活动数量
            $batch_num_doing               = M('TbatchInfo')->where($where . " and STATUS ='0' and END_TIME >= '" . date("YmdHis") . "'")->count();
            $return_arr['batch_num_doing'] = $batch_num_doing ? $batch_num_doing : 0;
        }
        if (!$req_operate || in_array('batch_num', $req_operate_arr)) {
            // 累计活动数
            $batch_num               = M('TbatchInfo')->where($where)->count();
            $return_arr['batch_num'] = $batch_num ? $batch_num : 0;
        }
        if (!$req_operate || in_array('send_num', $req_operate_arr)) {
            // 累计发码数
            $send_num               = M('TposDayCount')->where($where)->getField('sum(SEND_NUM)');
            $return_arr['send_num'] = $send_num ? $send_num : 0;
        }
        if (!$req_operate || in_array('validate_num', $req_operate_arr)) {
            // 累计验证数
            $validate_num               = M('TposDayCount')->where($where)->getField('sum(VERIFY_NUM)');
            $return_arr['validate_num'] = $validate_num ? $validate_num : 0;
        }
        if (!$req_operate || in_array('cancel_num', $req_operate_arr)) {
            // 累计撤销数
            $cancel_num               = M('TposDayCount')->where($where)->getField('sum(CANCEL_NUM)');
            $return_arr['cancel_num'] = $cancel_num ? $cancel_num : 0;
        }
        if (!$req_operate || in_array('send_num_24h', $req_operate_arr)) {
            // 24小时发码数
            $send_num_24h               = M('TbarcodeTrace')->where($where . " and TRANS_TIME >= " . date('YmdHis',
                            strtotime("-24 hours")))->count();
            $return_arr['send_num_24h'] = $send_num_24h ? $send_num_24h : 0;
        }
        if (!$req_operate || in_array('validate_num_today', $req_operate_arr)) {
            // 当日
            $begin_time = date("Ymd", time()) . "000000";
            // 当日验证数
            $validate_num_today               = M()->table('tpos_trace a')->where($where_a . " and a.TRANS_TIME >= " . $begin_time . " and a.TRANS_TYPE ='0' and a.FLOW_STATUS = '1' and a.STATUS = '0'")->count();
            $return_arr['validate_num_today'] = $validate_num_today ? $validate_num_today : 0;
        }
        if (!$req_operate || in_array('cancel_num_today', $req_operate_arr)) {
            // 当日
            $begin_time = date("Ymd", time()) . "000000";

            // 当日撤销数
            $cancel_num_today               = M()->table('tpos_trace a')->where($where_a . " and a.TRANS_TIME >= " . $begin_time . " and a.TRANS_TYPE ='1' and a.FLOW_STATUS = '1' and a.STATUS = '0'")->count();
            $return_arr['cancel_num_today'] = $cancel_num_today ? $cancel_num_today : 0;
        }
        $this->returnSuccess($resp_desc, $return_arr);
    }

    private function check() {
        if (empty($this->node_id) || empty($this->pos_id) || empty($this->user_id)) {
            return false;
        }

        return true;
    }
}