<?php
// 终端处理服务逻辑
class PosService {

    /**
     * pos机开放
     *
     * @var unknown_type
     */
    const POS_STATUS_OPEN = "0";

    /**
     * pos机关闭
     *
     * @var unknown_type
     */
    const POS_STATUS_CLOSE = "1";

    /**
     * 明码验证
     */
    const POS_ENCODE_TYPE_CLEARLY = "099";

    /**
     * 密文验证
     */
    const POS_ENCODE_TYPE_CIPHERTEXT = "098";

    /**
     * 请求的交易类型
     *
     * @var array
     */
    private static $requestTypes = array(
        "0" => "verify_request", 
        "1" => "cancel_request", 
        "2" => "reversal_request", 
        "4" => "settle_request", 
        "5" => "batch_up_request", 
        "6" => "batch_up_end_request", 
        "7" => "batch_down_request", 
        "8" => "batch_down_end_request");

    /**
     * 反馈的交易类型
     *
     * @var array
     */
    public static $responseTypes = array(
        "0" => "verify_response", 
        "1" => "cancel_response", 
        "4" => "settle_response", 
        "5" => "batch_up_response", 
        "6" => "batch_up_end_response", 
        "7" => "down_down_response", 
        "8" => "batch_down_end_response");

    public $errMsg;

    public $errInfo;

    public $dao;

    public $respInfo;

    public $printText;

    public function __construct() {
        $this->dao = D('TposInfo');
    }

    /**
     * 向支撑提交验证码用于验证
     *
     * @param unknown_type $code
     * @param unknown_type $posId
     * @param unknown_type $posSeq
     * @param unknown_type $userId
     * @param string $encodeType 验证编码类型
     * @return boolean
     */
    public function sendCodeToVerify($code, $posId, $posSeq, $userId, 
        $encodeType = self::POS_ENCODE_TYPE_CLEARLY, $tx_amt = 0, $password = '', $system_id = '', $goods_id = '') {
        $password_md5 = '';
        if ($password) {
            // md5加密
            $password_md5 = md5($password);
        }
        // 请求参数
        $reqArr = array(
            "request_type" => self::$requestTypes["0"], 
            "pos_id" => $posId, 
            "pos_seq" => $posSeq, 
            "user_id" => $userId, 
            "verify_request" => array(
                "valid_info" => $code, 
                "encode_type" => $encodeType, 
                "tx_amt" => $tx_amt, 
                "password_md5" => $password_md5, 
                "system_id" => $system_id, 
                "goods_id" => $goods_id));
        
        $posInfo = $this->getPos($posId);
        if (empty($posInfo)) {
            $this->errMsg = "终端不存在或未开启";
            $this->errInfo = array(
                'code' => '6027', 
                'msg' => $this->errMsg);
            return false;
        }
        
        // 计算密钥
        $inputMacStr = '' . $posId . $userId . $encodeType . $code . $posSeq;
        // 请求远程验证
        $reqService = D('RemoteRequest', 'Service');
        $arr = $reqService->requestPosTransServ($reqArr, $inputMacStr, 
            $posInfo['master_key'], $posInfo['work_key']);
        
        // 过滤掉打印文本后面的活动号和活动名
        if (isset($arr['addition_info']['print_text'])) {
            $arr['addition_info']['print_text'] = $this->get_print_text(
                $arr['addition_info']['print_text']);
            // exit($arr['addition_info']['print_text']);
        }
        
        $this->respInfo = $arr;
        if (is_array($arr) && $arr['result']['id'] === "0000") {
            return true;
        } else {
            $this->errMsg = is_array($arr) ? $arr['result']['comment'] : '验证出错!';
            if ($arr['result']['id'] === "3035") {
                $this->errInfo = array(
                    'code' => $arr['result']['id'], 
                    'msg' => $this->errMsg, 
                    'goods_info' => $arr['addition_info']['goods_info']);
                return false;
            }
            $this->errInfo = array(
                'code' => $arr['result']['id'], 
                'msg' => $this->errMsg);
            return false;
        }
    }

    /**
     * 向支撑提交辅助码码用于撤销
     *
     * @param unknown_type $code
     * @param unknown_type $posId
     * @param unknown_type $posSeq
     * @param unknown_type $orgPosSeq
     * @param unknown_type $userId
     * @return boolean
     */
    public function sendCodeToCancel($code, $posId, $posSeq, $orgPosSeq, $userId, 
        $encodeType = self::POS_ENCODE_TYPE_CLEARLY) {
        $posInfo = $this->getPos($posId);
        if (empty($posInfo)) {
            $this->errMsg = "终端不存在或未开启";
            return false;
        }
        
        /* 请求参数 */
        $reqArr = array(
            "request_type" => self::$requestTypes["1"], 
            "pos_id" => $posId, 
            "pos_seq" => $posSeq, 
            "user_id" => $userId, 
            "cancel_request" => array(
                "org_pos_seq" => $orgPosSeq, 
                "valid_info" => $code, 
                "encode_type" => $encodeType));
        
        // 计算密钥
        $inputMacStr = '' . $posId . $userId . $encodeType . $code . $orgPosSeq .
             $posSeq;
        // 请求远程验证
        $reqService = D('RemoteRequest', 'Service');
        $arr = $reqService->requestPosTransServ($reqArr, $inputMacStr, 
            $posInfo['master_key'], $posInfo['work_key']);
        
        // 过滤掉打印文本后面的活动号和活动名
        if (isset($arr['addition_info']['print_text'])) {
            include_once 'Common/Function/Function.php';
            $arr['addition_info']['print_text'] = $this->get_print_text(
                $arr['addition_info']['print_text']);
            // exit($arr['addition_info']['print_text']);
        }
        
        $this->respInfo = $arr;
        if ($arr['result']['id'] === "0000") {
            return true;
        } else {
            $this->errMsg = $arr['result']['comment'];
            return false;
        }
    }

    /**
     * 向支撑提交原始流水流水号用于冲正
     *
     * @param unknown_type $code
     * @param unknown_type $posId
     * @param unknown_type $posSeq
     * @param unknown_type $orgPosSeq
     * @param unknown_type $userId
     * @return boolean
     */
    public function sendCodeToReversal($code, $posId, $posSeq, $orgPosSeq, 
        $userId, $encodeType = self::POS_ENCODE_TYPE_CLEARLY) {
        /* 请求参数 */
        $reqArr = array(
            "request_type" => self::$requestTypes["2"], 
            "pos_id" => $posId, 
            "pos_seq" => $posSeq, 
            "user_id" => $userId, 
            "reversal_request" => array(
                "org_pos_seq" => $orgPosSeq, 
                "valid_info" => $code, 
                "encode_type" => $encodeType));
        
        // 计算密钥
        $inputMacStr = '' . $posId . $userId . $encodeType . $code . $orgPosSeq .
             $posSeq;
        // 请求远程验证
        $reqService = D('RemoteRequest', 'Service');
        $arr = $reqService->requestPosTransServ($reqArr, $inputMacStr, 
            $posInfo['master_key'], $posInfo['work_key']);
        $this->respInfo = $arr;
        if ($arr['result']['id'] === "0000") {
            return true;
        } else {
            $this->errMsg = $arr['result']['comment'];
            return false;
        }
    }

    public function getErrMsg() {
        return $this->errMsg;
    }

    public function getErrInfo() {
        return $this->errInfo;
    }

    public function getPrintText() {
        return $this->printText;
    }

    public function createPos($posInfo) {
        return $this->dao->save($posInfo);
    }

    public function updatePos($posId, $posName) {
        return $this->dao->where("POS_ID = '" . $posId . "'")->save(
            array(
                "pos_name" => $posName));
    }

    public function updatePosInfo($posId, $array) {
        return $this->dao->where("POS_ID = '" . $posId . "'")->save($array);
    }

    /* 获取终端信息 */
    public function getPos($posId) {
        $posInfo = $this->dao->where("POS_ID='$posId'")->find();
        return $posInfo;
    }

    public function get_print_text($org_print_text) {
        if ($org_print_text == '') {
            return null;
        } else {
            $print_text_arr = explode('<div style="display:none;">', 
                $org_print_text);
            return $print_text_arr[0];
        }
    }
}
