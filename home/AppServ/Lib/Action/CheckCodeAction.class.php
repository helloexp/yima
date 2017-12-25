<?php

/**
 * 功能：验证
 *
 * @author cxz 时间：2013-03-07
 */
class CheckCodeAction extends BaseAction {

    public $node_id;
    // 商户号
    public $pos_id;
    // 终端号
    public $user_id;
    // 用户号
    public $ass_code;
    // 辅助码或 条码
    public $is_ciphertext;
    // 是否为条码，通过设摄像头拍摄，0：否，1：是）
    public $txt_amt;
    // 验证金额
    public $pass_word;

    // 验证密码
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin(); // 校验用户登录
        // 初始化请求参数
        $this->ass_code      = I('ass_code');
        $this->is_ciphertext = I('is_ciphertext');
        $this->txt_amt       = I('txt_amt', 0);
        $this->pass_word     = I('pass_word', '');
    }

    public function run() {
        $rs = $this->check();
        if (!$rs) {
            $resp_desc = "请填写商户号、终端号、用户号、辅助码或条码";
            $this->returnError($resp_desc);
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

        // (2)终端校验
        $where    = "POS_ID ='" . $this->pos_id . "'";
        $pos_info = M('TposInfo')->where($where)->find();
        if (!$pos_info) {
            $this->returnError('该终端不存在', '1013');
        }
        if ($pos_info['pos_status'] > '1') {
            $this->returnError('该终端已停用或欠费停机', '1014');
        }

        $posServ     = D('Pos', 'Service');
        $encode_type = PosService::POS_ENCODE_TYPE_CLEARLY;

        // 如果是条码
        if ($this->is_ciphertext == '1') {
            $encode_type    = PosService::POS_ENCODE_TYPE_CIPHERTEXT;
            $this->ass_code = urlencode($this->ass_code);
            /*
             * $ass_code = base64_encode($this->ass_code); $this->ass_code =
             * urlencode($ass_code);
             */
        }
        $session = $this->session;
        $rs      = $posServ->checkAssistCode($this->ass_code, $this->pos_id,
                // $posServ->getNewPosSeq($this->pos_id, '1'),
                $posServ->getNewPosSeq($this->pos_id), $this->user_id, $session->getUserName(), $encode_type,
                $this->txt_amt, $this->pass_word, '', '', $session->getNodeId());
        if (!$rs) {
            if ($posServ->getErrMsg() == "系统正在处理冲正交易，请稍候再试！") {
                $resp_desc = "系统正在处理冲正交易，请稍候再试";
                $this->returnError($resp_desc);
            } else {
                $resp_info = $posServ->getErrInfo();
                $resp_desc = $resp_info['msg'];
                if ($resp_info['code'] === '3035') {
                    $this->returnError($resp_info['goods_info'], '3035');
                }
                // $resp_desc = $posServ->getErrMsg();
                $this->returnError($resp_desc, $resp_info['code']);
            }
        } else {
            $resp_desc = "验证成功";
            $resp_data = array(
                    "print_text" => $posServ->getPrintText(),
            );;
            $this->returnSuccess($resp_desc, $resp_data);
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