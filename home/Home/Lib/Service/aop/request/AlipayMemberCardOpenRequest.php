<?php

/**
 * ALIPAY API: alipay.member.card.open request
 *
 * @author auto create
 * @since 1.0, 2014-06-12 17:16:29
 */
class AlipayMemberCardOpenRequest {

    /**
     * 商户端开卡业务流水号
     */
    private $bizSerialNo;

    /**
     * 发卡商户信息，json格式。 目前仅支持如下key： merchantUniId：商户唯一标识
     * merchantUniIdType：支持以下3种取值。 LOGON_ID：商户登录ID，邮箱或者手机号格式；
     * UID：商户的支付宝用户号，以2088开头的16位纯数字组成； BINDING_MOBILE：商户支付宝账号绑定的手机号。 注意：
     * 本参数主要用于发卡平台接入场景，request_from为PLATFORM时，不能为空。
     */
    private $cardMerchantInfo;

    /**
     * 持卡用户信息，json格式。 目前仅支持如下key： userUniId：用户唯一标识 userUniIdType：支持以下3种取值。
     * LOGON_ID：用户登录ID，邮箱或者手机号格式； UID：用户支付宝用户号，以2088开头的16位纯数字组成；
     * BINDING_MOBILE：用户支付宝账号绑定的手机号。
     */
    private $cardUserInfo;

    /**
     * 开卡扩展参数，json格式。 用于商户的特定业务信息的传递，只有商户与支付宝约定了传递此参数且约定了参数含义，此参数才有效。
     */
    private $extInfo;

    /**
     * 商户会员卡号。 比如淘宝会员卡号、商户实体会员卡号、商户自有CRM虚拟卡号等。
     */
    private $externalCardNo;

    /**
     * 请求来源。 PLATFORM：发卡平台商 PARTNER：直联商户
     */
    private $requestFrom;

    private $apiParas = array();

    private $terminalType;

    private $terminalInfo;

    private $prodCode;

    public function setBizSerialNo($bizSerialNo) {
        $this->bizSerialNo = $bizSerialNo;
        $this->apiParas["biz_serial_no"] = $bizSerialNo;
    }

    public function getBizSerialNo() {
        return $this->bizSerialNo;
    }

    public function setCardMerchantInfo($cardMerchantInfo) {
        $this->cardMerchantInfo = $cardMerchantInfo;
        $this->apiParas["card_merchant_info"] = $cardMerchantInfo;
    }

    public function getCardMerchantInfo() {
        return $this->cardMerchantInfo;
    }

    public function setCardUserInfo($cardUserInfo) {
        $this->cardUserInfo = $cardUserInfo;
        $this->apiParas["card_user_info"] = $cardUserInfo;
    }

    public function getCardUserInfo() {
        return $this->cardUserInfo;
    }

    public function setExtInfo($extInfo) {
        $this->extInfo = $extInfo;
        $this->apiParas["ext_info"] = $extInfo;
    }

    public function getExtInfo() {
        return $this->extInfo;
    }

    public function setExternalCardNo($externalCardNo) {
        $this->externalCardNo = $externalCardNo;
        $this->apiParas["external_card_no"] = $externalCardNo;
    }

    public function getExternalCardNo() {
        return $this->externalCardNo;
    }

    public function setRequestFrom($requestFrom) {
        $this->requestFrom = $requestFrom;
        $this->apiParas["request_from"] = $requestFrom;
    }

    public function getRequestFrom() {
        return $this->requestFrom;
    }

    public function getApiMethodName() {
        return "alipay.member.card.open";
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
