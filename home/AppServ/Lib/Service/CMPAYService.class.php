<?php

/**
 * Class CMPAYService Desc
 */
class CMPAYService {
    // 商户号
    private $merchantId;
    // 密钥
    private $key;
    // 地址
    private $sendUrl;

    public function __construct() {
        $this->merchantId = C('cmpay.merchantId');
        $this->key        = C('cmpay.key');
        $this->sendUrl    = C('cmpay.sendUrl');
    }

    public function cmpayAddCard(
            $mobile,
            $requestId,
            $assistNumber,
            $cardName,
            $cardType,
            $effectDate,
            $expireDate,
            $userRule,
            $barId
    ) {
        $requestArr['merchantId'] = $this->merchantId;
        $requestArr['requestId']  = $requestId;
        $requestArr['signType']   = 'MD5';
        $requestArr['type']       = 'MkmCardManage';
        $requestArr['version']    = '2.0.0';
        $requestArr['cardNo']     = $assistNumber;
        $requestArr['mobileNo']   = $mobile;
        $requestArr['cardName']   = iconv('utf8', 'gbk', $cardName);
        $requestArr['cardType']   = $cardType;
        $requestArr['cardState']  = 'A';
        $requestArr['effectDate'] = $effectDate;
        $requestArr['expireDate'] = $expireDate;
        $requestArr['cardDesc']   = iconv('utf8', 'gbk', $userRule);
        $requestArr['useDesc']    = iconv('utf8', 'gbk', "辅助码:$assistNumber\n{$userRule}");
        $requestArr['shareTitle'] = iconv('utf8', 'gbk', $cardName);
        $requestArr['operateFlg'] = '1';

        $signSrc            = implode('', $requestArr);
        $requestArr['hmac'] = $this->MD5sign($this->key, $signSrc);

        $i = 0;
        do {
            $i++;
            $result = httpPost($this->sendUrl, $requestArr);
            if ($result != null && strlen($result) > 0) {
                break;
            }
        } while ($i < 3);
        parse_str($result, $respArr);
        $respArr['message'] = iconv('gbk', 'utf8', $respArr['message']);
        //保存发送流水记录
        if ($this->saveCmpCardTrace($barId, $respArr) && $respArr['returnCode'] == 'MCG00000') {
            return true;
        } else {
            log_write("cmpayAddCard error ." . $respArr['returnCode']);

            return false;
        }
    }

    public function cmpayUseCard($requestId, $assistNumber) {
        $requestArr['merchantId'] = $this->merchantId;
        $requestArr['requestId']  = $requestId;
        $requestArr['signType']   = 'MD5';
        $requestArr['type']       = 'MkmCardManage';
        $requestArr['version']    = '2.0.0';
        $requestArr['cardNo']     = $assistNumber;
        $requestArr['cardState']  = 'U';
        $requestArr['operateFlg'] = '2';

        $signSrc            = implode('', $requestArr);
        $requestArr['hmac'] = $this->MD5sign($this->key, $signSrc);

        $i = 0;
        do {
            $i++;
            $result = httpPost($this->sendUrl, $requestArr);
            if ($result != null && strlen($result) > 0) {
                break;
            }
        } while ($i < 3);
        parse_str($result, $respArr);
        $respArr['message'] = iconv('gbk', 'utf8', $respArr['message']);
        //保存发送流水记录
        if ($respArr['returnCode'] == 'MCG00000') {
            return true;
        } else {
            log_write("cmpayUseCard error ." . $respArr['returnCode']);

            return false;
        }
    }

    private function saveCmpCardTrace($barId, $respArr) {
        $cardTrace['barcode_trace_id'] = $barId;
        $cardTrace['resp_code']        = $respArr['returnCode'];
        $cardTrace['resp_msg']         = $respArr['message'];
        $rs                            = M('tbarcode_trace_exp_cmpcard')->add($cardTrace);
        if ($rs === false) {
            log_write("save tbarcode_trace_exp_cmpcard error . " . M()->_sql());

            return false;
        }

        return true;
    }

    private function MD5sign($okey, $odata) {
        $signdata = $this->hmac("", $odata);

        return $this->hmac($okey, $signdata);
    }

    private function hmac($key, $data) {
        $key  = iconv('gb2312', 'utf-8', $key);
        $data = iconv('gb2312', 'utf-8', $data);
        $b    = 64;
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }
        $key    = str_pad($key, $b, chr(0x00));
        $ipad   = str_pad('', $b, chr(0x36));
        $opad   = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;

        return md5($k_opad . pack("H*", md5($k_ipad . $data)));
    }

}