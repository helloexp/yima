<?php

/**
 * ALIPAY API: alipay.mobile.public.appinfo.update request
 *
 * @author auto create
 * @since 1.0, 2014-06-05 21:36:37
 */
class AlipayMobilePublicAppinfoUpdateRequest {

    /**
     * 业务json
     */
    private $bizContent;

    private $apiParas = array();

    private $terminalType;

    private $terminalInfo;

    private $prodCode;

    public function setBizContent($bizContent) {
        $this->bizContent = $bizContent;
        $this->apiParas["biz_content"] = $bizContent;
    }

    public function getBizContent() {
        return $this->bizContent;
    }

    public function getApiMethodName() {
        return "alipay.mobile.public.appinfo.update";
    }

    public function getApiParas() {
        return $this->apiParas;
    }

    public function getTerminalType() {
        return $this->terminalType;
    }

    public function setTerminalType($terminalType) {
        $this->terminalType = $terminalType;
    }

    public function getTerminalInfo() {
        return $this->terminalInfo;
    }

    public function setTerminalInfo($terminalInfo) {
        $this->terminalInfo = $terminalInfo;
    }

    public function getProdCode() {
        return $this->prodCode;
    }

    public function setProdCode($prodCode) {
        $this->prodCode = $prodCode;
    }
}
