<?php

import('@.Service.WeixinRedPackService') or die('导入包失败');

//红包领取查询接口
class WxBonusStatusService extends WeixinRedPackService
{
    protected $mch_billno='';

    
    public function apiGethbinfo($mchBillno)
    {
        $this->mch_billno = $mchBillno;
        $apiUrl  = "https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo";
        $dataArr = [
                'mch_billno' => $this->mch_billno,
                'appid'      => $this->wxappId,
                'mch_id'     => $this->mchId,
                'nonce_str'  => $this->getNonceStr(),
                'bill_type'  => 'MCHT'
        ];
        $sign = $this->sign($dataArr);
        $dataArr['sign'] = $sign;
        $xmlStr = $this->arraytoXml($dataArr);
        log_write("WeixinRedPackLog:{$this->nodeId}-{$xmlStr}");
        // 发送报文
        $result = $this->curlPostSsl($apiUrl, $xmlStr);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

   

}