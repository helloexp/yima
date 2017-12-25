<?php

/* 验证接口 */
class PosVerifyService {

    public $opt = array();

    public $errMsg = '';

    public $respData = array();

    public $printText = '';

    public $posSeq = '';

    private $systemId;

    private $systemKey;

    public function __construct() {
        C('LOG_PATH', LOG_PATH . 'POS_VERIFY_'); // 重新定义目志目录
        $this->systemId = C('ISS_SYSTEM_ID') or die('[ISS_SYSTEM_ID]参数未设置');
        $this->systemKey = C('ISS_MAC_KEY') or die('[ISS_MAC_KEY]参数未设置');
        set_time_limit(0);
    }

    /* 设置参数 */
    public function setopt() {
        if (! $this->posSeq) {
            $this->posSeq = $this->getPosSeq();
        }
    }

    /*
     * 验证方法 $posId 终端机构号 $posId 终端号 $assistNumber 辅助码
     */
    public function doPosVerify($posId, $assistNumber, $bRetAll = false, $txAmt = 0) {
        if (! $bRetAll) {
            $this->posSeq = $this->getPosSeq();
        }
        $this->respData = $this->requestVeify($posId, $this->posSeq, 
            $assistNumber, $txAmt);
        $this->errMsg = $this->respData['business_trans']['result']['comment'];
        if ($bRetAll) {
            return $this->respData;
        }
        if ($this->respData['business_trans']['result']['id'] === '0000') {
            $this->printText = $this->respData['business_trans']['addition_info']['print_text'];
            return true;
        } else { // 交易失败
            return $this->errMsg;
        }
    }

    /*
     * 验证撤销方法 $posId 终端号 $assistNumber 辅助码 $orgPosSeq 原终端流水号
     */
    public function doPosCancel($posId, $orgPosSeq, $assistNumber) {
        $this->posSeq = $this->getPosSeq();
        $this->respData = $this->requestCancel($posId, $this->posSeq, 
            $orgPosSeq, $assistNumber);
        $this->errMsg = $this->respData['business_trans']['result']['comment'];
        if ($this->respData['business_trans']['result']['id'] === '0000') {
            $this->printText = $this->respData['business_trans']['addition_info']['print_text'];
            return true;
        } else { // 交易失败
            return false;
        }
    }

    /*
     * 冲正方法 $nodeId 终端机构号 $posId 终端号 $orgPosSeq 原终端流水号 $assistNumber 辅助码
     */
    public function doPosReversal($posId, $orgPosSeq, $assistNumber) {
        $this->posSeq = $this->getPosSeq();
        $this->respData = $this->requestReversal($posId, $this->posSeq, 
            $orgPosSeq, $assistNumber);
        log_write(print_r($this->respData), TRUE);
        $this->errMsg = $this->respData['business_trans']['result']['comment'];
        if ($this->respData['business_trans']['result']['id'] === '0000') {
            return true;
        } else { // 交易失败
            return false;
        }
    }

    /*
     * 验证接口
     */
    private function requestVeify($posId, $posSeq, $assistNumber, $txAmt) {
        $dataArr = array(
            'business_trans' => array(
                'request_type' => 'verify_request', 
                'pos_id' => $posId, 
                'pos_seq' => $posSeq, 
                'verify_request' => array(
                    'valid_info' => $assistNumber, 
                    'encode_type' => '099')));
        if ($txAmt > 0) {
            $dataArr['business_trans']['verify_request']['tx_amt'] = $txAmt;
        }
        
        return $this->requestIssPosVerifyServ($dataArr);
    }

    /*
     * 撤销接口
     */
    private function requestCancel($posId, $posSeq, $orgPosSeq, $assistNumber) {
        $dataArr = array(
            
            'business_trans' => array(
                'request_type' => 'cancel_request', 
                'pos_id' => $posId, 
                'pos_seq' => $posSeq, 
                'cancel_request' => array(
                    'valid_info' => $assistNumber, 
                    'encode_type' => '099', 
                    'org_pos_seq' => $orgPosSeq)));
        return $this->requestIssPosVerifyServ($dataArr);
    }

    /*
     * 冲正接口
     */
    private function requestReversal($posId, $posSeq, $orgPosSeq, $assistNumber) {
        $dataArr = array(
            
            'business_trans' => array(
                'request_type' => 'reversal_request', 
                'pos_id' => $posId, 
                'pos_seq' => $posSeq, 
                'reversal_request' => array(
                    'valid_info' => $assistNumber, 
                    'encode_type' => '099', 
                    'org_pos_seq' => $orgPosSeq)));
        return $this->requestIssPosVerifyServ($dataArr);
    }

    /* 发往支撑验证接口 */
    private function requestIssPosVerifyServ($dataArr) {
        $url = C('ISS_POS_URL') or die('[ISS_POS_URL]参数未设置');
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($dataArr, 'gb2312');
        // $mac_str = md5($this->systemKey.$str.$this->systemKey);
        $sendStr = http_build_query(array(
            'xml' => $str));
        // 'mac'=>$mac_str,
        
        $error = '';
        $this->_log('send url :' . $url);
        $this->_log('send data :' . urldecode($sendStr));
        $result_str = httpPost($url, $sendStr, $error, 
            array(
                'TIMEOUT' => $timeout));
        $this->_log('recv data :' . $result_str);
        if (! $result_str) {
            return array(
                'result' => array(
                    'id' => '-1', 
                    'comment' => '通讯错误'));
        }
        $xml1 = new Xml();
        $xml1->parse($result_str);
        $arr = $xml1->getAll();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return array(
                'result' => array(
                    'id' => '-2', 
                    'comment' => $xml->error()));
        }
        return $arr;
    }

    private function getPosSeq() {
        $data = M()->query(
            "SELECT _nextval('pos_verify_seq') as reqid FROM DUAL");
        if (! $data) {
            $this->_log('get pos_verify_seq fail!');
            $req = str_pad(rand(1, 999999), 12, '0', STR_PAD_LEFT);
        } else {
            $req = str_pad($data[0]['reqid'], 12, '0', STR_PAD_LEFT);
        }
        return $req;
    }

    public function _log($msg, $level = Log::INFO) {
        Log::write($msg, $level);
    }
}