<?php

/**
 * 功能：验证流水查询(当日,为撤销服务)
 *
 * @author wtr 时间：2013-06-26
 */
class SearchFlowForRollbackAction extends BaseAction {

    public $node_id;
    // 商户号
    public $user_id;
    // 用户号
    public $pos_id;
    // 终端号
    public $ass_code;
    // 辅助码或 条码
    public $is_ciphertext;

    // 是否为条码，通过设摄像头拍摄，0：否，1：是）
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin(); // 校验用户登录
        // 初始化请求参数
        $this->ass_code      = I('ass_code');
        $this->is_ciphertext = I('is_ciphertext');
    }

    public function run() {
        $rs = $this->check();
        if (!$rs) {
            $resp_desc = "请填写商户号、终端号、用户号、辅助码或条码";
            $this->returnError($resp_desc);
        }
        $dao = M()->table('tpos_trace a')->join('tgoods_info b on b.batch_no=a.batch_no');
        // 数据隔离，只能查本机构，本终端
        $where = "a.NODE_ID ='" . $this->node_id . "' and a.POS_ID = '" . $this->pos_id . "'";
        // 判断是否辅助
        if ($this->is_ciphertext == '1') {
            $where .= " and a.ASSISTANT_DES = '" . md5(urlencode($this->ass_code)) . "'"; // 条码md5
        } else {
            $where .= " and a.ASSISTANT_NO_MD5 = '" . md5($this->ass_code) . "'"; // 辅助码md5
        }
        $where .= " and (a.ASSISTANT_DES = '" . md5(urlencode($this->ass_code)) . "' or a.ASSISTANT_NO_MD5 = '" . md5($this->ass_code) . "')";
        // 24小时前的时间戳
        $begin_time = date("Ymd") . "000000";

        if ($begin_time != '') {
            $where .= " and a.TRANS_TIME >= " . $begin_time;
        }

        // 验证流水且 操作成功
        $where .= " and a.FLOW_STATUS = '1' and a.TRANS_TYPE ='0' and a.IS_CANCELED ='0' and a.STATUS = '0' and a.IS_SETTLED = '0'";

        $pos_info = $dao->where($where)->field("a.PHONE_NO, a.ID as FLOW_ID, a.POS_ID, a.BATCH_NO, CONCAT(a.EXCHANGE_AMT) as EXCHANGE_AMT, b.GOODS_NAME as BATCH_SHORT_NAME, a.TRANS_TIME")->order("a.id desc")->limit(0,
                        1000)->select();

        if (!empty($pos_info)) {
            $resp_desc = "验证流水查询成功";

            foreach ($pos_info as &$value) {
                $value               = array_change_key_case($value, CASE_LOWER);
                $value['trans_time'] = date("Y-m-d H:i:s", strtotime($value['trans_time']));
            }
            unset($value);

            $this->returnSuccess($resp_desc, array(
                            'validate_list' => $pos_info,
                    ));
        } else {
            $resp_desc = "找不到任何验证流水";
            $this->returnError($resp_desc);
        }
    }

    private function check() {
        if (empty($this->node_id) || empty($this->pos_id) || empty($this->user_id) || empty($this->ass_code)) {
            return false;
        }

        return true;
    }
}

?>