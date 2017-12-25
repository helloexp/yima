<?php

/**
 * 功能：撤销验证
 *
 * @author cxz 时间：2013-03-07
 */
class RollbackCodeAction extends BaseAction {

    public $node_id;
    // 商户号
    public $pos_id;
    // 终端号
    public $user_id;
    // 用户号
    public $org_flow_id;
    // 原交易流水号(tbatch_water表ID)
    public $ass_code;
    // 辅助码或条码
    public $is_ciphertext;

    // 是否为条码，通过设摄像头拍摄，0：否，1：是）
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin(); // 校验用户登录
        // 初始化请求参数
        $this->org_flow_id   = I('org_flow_id');
        $this->ass_code      = I('ass_code');
        $this->is_ciphertext = I('is_ciphertext');
    }

    public function run() {
        $rs = $this->check();
        if (!$rs) {
            $resp_desc = "请填写商户号、终端号、用户号、辅助码或条码、原交易流水号";
            $this->returnError($resp_desc);
        }

        $session  = $this->session;
        $posServ  = D('Pos', 'Service');
        $flowServ = D('Flow', 'Service');
        // 获取流水
        $info = $flowServ->getFlowByFlowId($this->org_flow_id);

        /*
         * 用条码查的记录，一定是条码验的 用辅助码查出的记录，两种都有可能。
         */
        if ($this->is_ciphertext == '1') {
            // 条码
            $encode_type = '098';
            $code        = $info['barcode'];
        } else {
            $encode_type = $info['code_type'];
            if ($encode_type == '098') {
                // 条码验证
                $code = $info['barcode'];
            } else {
                // 辅助码验证
                $code = $info['auxiliary_code'];
            }
        }
        $rs = $posServ->cancelAssistCode($code, $this->org_flow_id, $session->getPosId(),
                $posServ->getNewPosSeq($session->getPosId()), $session->getUserId(), $session->getUserName(),
                $encode_type, $session->getNodeId());
        if (!$rs) {
            if ($posServ->getErrMsg() == "系统正在处理冲正交易，请稍候再试！") {
                $resp_desc = "系统正在处理冲正交易，请稍候再试";
                $this->returnError($resp_desc);
            } else {
                $resp_desc = $posServ->getErrMsg();
                $this->returnError($resp_desc);
            }
        } else {
            $resp_desc = "撤销验证成功";
            $this->returnSuccess($resp_desc);
        }
    }

    private function check() {
        if (empty($this->node_id) || empty($this->pos_id) || empty($this->user_id) || empty($this->org_flow_id) || empty($this->ass_code)) {
            return false;
        }

        return true;
    }
}

?>