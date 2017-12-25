<?php

class IssSend {

    protected $generate_xml = '';

    protected $resend_xml = '';
    
    // 支撑发码接口
    public function zc_send($info) {
        $req_xml = '<?xml version="1.0" encoding="GBK" ?>' . '<SubmitVerifyReq>' .
             '<SystemID>' . C('ISS_PLATFORM_ID') . '</SystemID>' . '<ISSPID>' .
             $info['ISSPID'] . '</ISSPID>' . '<TransactionID>' .
             $info['TransactionID'] . '</TransactionID>' . '<Recipients>' .
             '<Number>' . $info['Number'] . '</Number>' . '</Recipients>' .
             '<SendClass>SAM</SendClass>' . '<Messages>' . '<Sms>' . '<Text>' .
             $info['sms_info'] . '</Text>' . '</Sms>' . '<Mms>' . '<Subject>' .
             $info['mms_title'] . '</Subject>' . '<Text>' . $info['mms_info'] .
             '</Text>' . '</Mms>' . '</Messages>' . '<ActivityInfo>' .
             '<ActivityID>' . $info['ActivityID'] . '</ActivityID>' .
             '<BeginTime></BeginTime>' . '<EndTime></EndTime>' .
             '<OrgTimes>1</OrgTimes>' . '<OrgAmt></OrgAmt>' . '<PrintText>' .
             $info['PrintText'] . '</PrintText>' . '</ActivityInfo>' .
             '</SubmitVerifyReq>';
        $url = C('ISS_SEND_URL');
        
        php_log('ISS GENERATE URL:' . $url);
        php_log('ISS GENERATE REQXML' . $req_xml);
        $req_xml = iconv('UTF-8', 'GBK', $req_xml);
        
        $mac = md5(C('ISS_SEND_USER_PASS') . $req_xml . C('ISS_SEND_USER_PASS'));
        $req_xml = 'xml=' . urlencode($req_xml) . '&mac=' . $mac;
        
        import("Vendor.Curl");
        $curl = new Curl($url);
        $curl->setopt(CURLOPT_SSL_VERIFYHOST, 0);
        $curl->setopt(CURLOPT_SSL_VERIFYPEER, 0);
        
        $resp_xml = $curl->sendmsg($req_xml);
        $curl->close();
        
        $arr = array();
        parse_str($resp_xml, $arr);
        $resp_xml = str_replace("\\", "", $arr['xml']);
        
        php_log('ISS GENERATE RESPXML' . $resp_xml, 'GBK');
        
        import("Vendor.Xml");
        $xmlparser = new Xml();
        $xmlparser->setopt('ENCODING', 'UTF-8');
        $xmlparser->parse($resp_xml);
        
        $error = $xmlparser->error();
        if ($error) {
            php_log('请求支撑返回报文错误：' . $error);
            throw_exception('发送失败！');
        }
        
        $ret_id = $xmlparser->getValue('SubmitVerifyRes->Status->StatusCode');
        $ret_str = $xmlparser->getValue('SubmitVerifyRes->Status->StatusText');
        
        if ($ret_id != '0000') {
            php_log('条码发送错误：' . $ret_str);
            return '';
        }
        
        $AssistNumber = $xmlparser->getValue('SubmitVerifyRes->AssistNumber');
        return $AssistNumber;
    }
}