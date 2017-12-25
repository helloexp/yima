<?php

/* 这儿封装不同接口的请求 */
class RemoteRequestService {
    // 请求支撑验证
    public $opt = array();

    public function __construct() {
    }

    /* 设置参数 */
    public function setopt() {
    }

    /* 发往营账接口 */
    public function requestYzServ($data) {
        $url = C('YZ_SERV_URL') or die('[YZ_SERV_URL]参数未设置');
        $mac_key = C('YZ_MAC_KEY') or die('[YZ_MAC_KEY]参数未设置');
        $timeout = C('YZ_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk');
        
        $mac_str = md5($mac_key . $str . $mac_key);
        $sendStr = http_build_query(
            array(
                'xml' => $str, 
                'mac' => $mac_str));
        $error = '';
        $result_xml = httpPost($url, $sendStr, $error, 
            array(
                'TIMEOUT' => $timeout));
        if (! $result_xml) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => 'XML is null'));
        }
        $xml = new Xml();
        $result_xml = get_magic_quotes_gpc() ? stripslashes($result_xml) : $result_xml;
        
        $arr = $xml->parse($result_xml);
        $arr = $xml->getArrayNoRoot();
        if ($xml->error()) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => $xml->error()));
        }
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        return $arr;
    }

    /* 调用WeiXinServ发码接口 */
    public function requestWcWeiXinServ($data) {
        $url = C('WC_WXCARD_URL') or die('[WC_WXCARD_URL]参数未设置');
        $error = '';
        $result = httpPost($url, $data, $error, array(
                'METHOD' => 'GET',
        ));
        return json_decode($result, true);
    }

    /* 发往支撑 */
    public function requestIssServ($data) {
        $url = C('ISS_SEND_SERV_URL') or die('[ISS_SEND_SERV_URL]参数未设置');
        $mac_key = C('ISS_MAC_KEY') or die('[ISS_MAC_KEY]参数未设置');
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk');
        log_write("请求报文信息:" . $str . "/r/n 请求地址：" . $url, 'INFO', 
            'RemoteRequest');
        $mac_str = md5($mac_key . $str . $mac_key);
        $sendStr = http_build_query(
            array(
                'xml' => $str, 
                'mac' => $mac_str));
        $error = '';
        $result_str = httpPost($url, $sendStr, $error, 
            array(
                'TIMEOUT' => $timeout));
        parse_str($result_str, $result);
        if (! $result['xml']) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => 'XML is null'));
        }
        $xml = new Xml();
        $result['xml'] = get_magic_quotes_gpc() ? stripslashes($result['xml']) : $result['xml'];
        log_write("返回报文信息:" . $result['xml'], 'INFO', 'RemoteRequest');
        $arr = $xml->parse($result['xml']);
        $arr = $xml->getAll();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => $xml->error()));
        }
        $arr['_mac'] = $result['mac'];
        return $arr;
    }

    /* 发往会员中心 */
    public function requestMemberServ($data) {
        $url = C('MEMBER_URL') or die('[MEMBER_URL]参数未设置');
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        
        $sendStr = http_build_query($data);
        $error = '';
        $result_str = httpPost($url, $sendStr, $error, 
            array(
                'TIMEOUT' => $timeout));
        $result = json_decode(iconv("gbk", "utf8", $result_str), true);
        if ($result['resp_id'] == '0000')
            return true;
        else
            return false;
    }

    /* 发往支撑电子合约修改接口 */
    public function SetGoodsInfoReq($data) {
        $url = C('ISS_SETG00DSINFOREQ_URL') or
             die('[ISS_SETG00DSINFOREQ_URL]参数未设置');
        // $mac_key = C('ISS_MAC_KEY') or die('[ISS_MAC_KEY]参数未设置');
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk');
        $error = '';
        $result_str = httpPost($url, $str, $error, 
            array(
                'TIMEOUT' => $timeout));
        $xml = new Xml();
        $result['xml'] = get_magic_quotes_gpc() ? stripslashes($result_str) : $result_str;
        $arr = $xml->parse($result['xml']);
        $arr = $xml->getAll();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => $xml->error()));
        }
        // $arr['_mac'] = $result['mac'];
        return $arr;
    }

    /* 支撑获取smil_id */
    public function smilAddEditReq($data) {
        $url = C('ISS_SEND_URL') or die('[ISS_SEND_URL]参数未设置');
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk');
        $error = '';
        $result_str = httpPost($url, $str, $error, 
            array(
                'TIMEOUT' => $timeout));
        $xml = new Xml();
        $result_str = get_magic_quotes_gpc() ? stripslashes($result_str) : $result_str;
        $arr = $xml->parse($result_str);
        $arr = $xml->getAll();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => $xml->error()));
        }
        return $arr;
    }

    public function requestSsoServ($data, $url) {
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk');
        $error = '';
        $result_str = httpPost($url, $str, $error, 
            array(
                'TIMEOUT' => $timeout));
        $xml = new Xml();
        $result_str = get_magic_quotes_gpc() ? stripslashes($result_str) : $result_str;
        $arr = $xml->parse($result_str);
        $arr = $xml->getAll();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => $xml->error()));
        }
        return $arr;
    }

    /* sso重置密码 */
    public function ssoResetPwd($data) {
        $url = C('SSO_RESETPWD_SERV_URL') or die('[SSO_RESETPWD_SERV_URL]参数未设置');
        return $this->requestSsoServ($data, $url);
    }
    
    // sso修改用户状态
    public function requestSsoUserStatus($data) {
        $url = C('SSO_USER_STATUS_SERV_URL') or
             die('[SSO_USER_STATUS_SERV_URL]参数未设置');
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk');
        $error = '';
        $result_str = httpPost($url, $str, $error, 
            array(
                'TIMEOUT' => $timeout));
        $xml = new Xml();
        $result_str = get_magic_quotes_gpc() ? stripslashes($result_str) : $result_str;
        $arr = $xml->parse($result_str);
        $arr = $xml->getAll();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => $xml->error()));
        }
        return $arr;
    }

    /* 发往支撑内部接口，小妹账号创建 */
    public function requestSsoUserCreate($data) {
        $url = C('SSO_USER_NEW_SERV_URL') or die('[SSO_USER_NEW_SERV_URL]参数未设置');
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk');
        $error = '';
        $result_str = httpPost($url, $str, $error, 
            array(
                'TIMEOUT' => $timeout));
        // $result_str = iconv('gbk','utf-8',$result_str);
        $xml = new Xml();
        $result_str = get_magic_quotes_gpc() ? stripslashes($result_str) : $result_str;
        $arr = $xml->parse($result_str);
        $arr = $xml->getAll();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => $xml->error()));
        }
        return $arr;
    }

    /* 发往支撑内部接口，门店修改啥啥啥的 */
    public function requestIssForImageco($data) {
        $url = C('ISS_SERV_FOR_IMAGECO') or die('[ISS_SERV_FOR_IMAGECO]参数未设置');
        // $mac_key = C('ISS_MAC_KEY') or die('[ISS_MAC_KEY]参数未设置');
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk');
        $error = '';
        $result_str = httpPost($url, $str, $error,
            array(
                'TIMEOUT' => $timeout));
        $xml = new Xml();
        $result['xml'] = get_magic_quotes_gpc() ? stripslashes($result_str) : $result_str;
        $arr = $xml->parse($result['xml']);
        $arr = $xml->getAll();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => $xml->error()));
        }
        // $arr['_mac'] = $result['mac'];
        return $arr;
    }

    /* 短信重发 */
    public function resend($data) {
        $url = C('ISS_SEND_SERV_URL') or die('[ISS_SEND_SERV_URL]参数未设置');
        $mac_key = C('AP_ISS_MAC_KEYS') or die('[AP_ISS_MAC_KEYS]参数未设置');
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk');
        $mac_str = md5($mac_key . $str . $mac_key);
        $sendStr = http_build_query(
            array(
                'xml' => $str, 
                'mac' => $mac_str));
        $error = '';
        $result_str = httpPost($url, $sendStr, $error, 
            array(
                'TIMEOUT' => $timeout));
        parse_str($result_str, $result);
        if (! $result['xml']) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => 'XML is null'));
        }
        $xml = new Xml();
        $result['xml'] = get_magic_quotes_gpc() ? stripslashes($result['xml']) : $result['xml'];
        $arr = $xml->parse($result['xml']);
        $arr = $xml->getAll();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => $xml->error()));
        }
        $arr['_mac'] = $result['mac'];
        return $arr;
    }
    
    // 爱拍获取门店，创建终端
    public function GetStoreAreaInfoReq($data) {
        $url = C('STORE_URL') or die('[STORE_URL]参数未设置');
        // $mac_key = C('ISS_MAC_KEY') or die('[ISS_MAC_KEY]参数未设置');
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk');
        $error = '';
        $result_str = httpPost($url, $str, $error, 
            array(
                'TIMEOUT' => $timeout));
        $xml = new Xml();
        $result['xml'] = get_magic_quotes_gpc() ? stripslashes($result_str) : $result_str;
        $arr = $xml->parse($result['xml']);
        
        $arr = $xml->getAll();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => $xml->error()));
        }
        // $arr['_mac'] = $result['mac'];
        return $arr;
    }
    
    // 通过新浪接口获取短连接
    public function getSinaShortUrl($long_url) {
        $long_url = urlencode($long_url);
        $api = 'http://api.t.sina.com.cn/short_url/shorten.json?source=4294557489&url_long=' .
             $long_url;
        $error = '';
        $result_str = httpPost($api, '', $error, 
            array(
                'TIMEOUT' => 30, 
                'METHOD' => 'GET'));
        if ($error) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => '获取新浪短链接失败,原因：' . $error));
        }
        $result = json_decode($result_str);
        $short_url = $result[0]->url_short;
        if (! $short_url) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => '获取新浪短链接失败'));
        }
        $short_url_exct = str_replace('http://t.cn', 'HTTP://T.CN', $short_url);
        return array(
            'Status' => array(
                'StatusCode' => '0000'), 
            'ShortUrl' => $short_url_exct);
        // return $short_url_exct;
    }
    
    // 获取短连接地址
    public function GetShortUrl($data) {
        $url = C('STORE_URL') or die('[STORE_URL]参数未设置');
        // $mac_key = C('ISS_MAC_KEY') or die('[ISS_MAC_KEY]参数未设置');
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk');
        $error = '';
        $result_str = httpPost($url, $str, $error, 
            array(
                'TIMEOUT' => $timeout));
        $xml = new Xml();
        $result['xml'] = get_magic_quotes_gpc() ? stripslashes($result_str) : $result_str;
        $arr = $xml->parse($result['xml']);
        
        $arr = $xml->getArrayNoRoot();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => $xml->error()));
        }
        // $arr['_mac'] = $result['mac'];
        return $arr;
    }

    /*
     * 支撑终端验证接口 data 数据 inputMacStr 请求加密字符串 mKey 主密钥 wKey 工作密钥
     */
    public function requestPosTransServ($data, $inputMacStr, $mKey, $wKey) {
        $url = C('ISS_POS_SERV_URL') or die('[ISS_POS_SERV_URL]参数未设置');
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk', 
            '<business_trans version="1.0" >%s</business_trans>');
        // 计算mac
        import('@.ORG.Com.WebPos3Des') or die('@.ORG.Com.WebPos3Des导入失败');
        $mObj = new WebPos3Des();
        $macStr = $mObj->trans_mac($inputMacStr, $mKey, $wKey);
        $sendStr = "xml={$str}&mac={$macStr}";
        $error = '';
        $result_str = httpPost($url, $sendStr, $error);
        // 如果网络错误
        if ($error) {
            return array(
                'result' => array(
                    'id' => '-1', 
                    'comment' => $error));
        }
        parse_str($result_str, $result);
        if (! $result['xml']) {
            return array(
                'result' => array(
                    'id' => '-1', 
                    'comment' => 'XML is null'));
        }
        $xml = new Xml();
        $result['xml'] = get_magic_quotes_gpc() ? stripslashes($result['xml']) : $result['xml'];
        $arr = $xml->parse($result['xml']);
        $arr = $xml->getArrayNoRoot();
        if ($xml->error()) {
            return array(
                'result' => array(
                    'id' => '-1', 
                    'comment' => $xml->error()));
        }
        $arr['_mac'] = $result['mac'];
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        return $arr;
    }

    /*
     * 发短信函数 created by tr 发往支撑短信 @param string $mobile :手机号 @param string
     * $content :内容 @return bool
     */
    public function smsSend($phoneNo, $text) {
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $phoneNo),  // 手机号
                'SendClass' => 'SMS', 
                'MessageText' => $text,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('MOBILE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            return false;
        }
        return true;
    }

    /* 调用AppServ发码接口 */
    public function requestWcAppServ($data) {
        $url = C('WC_SEND_ARR.url') or die('[WC_SEND_ARR]参数未设置');
        log_write($url);
        $error = '';
        $result = httpPost($url, $data, $error, 
            array(
                'METHOD' => 'GET'));
        return json_decode($result, true);
    }

    /* 调用AppServ发码接口 */
    public function requestWcAppServToCancel($data) {
        $url = C('WC_CANCEL_ARR.url') or die('[WC_CANCEL_ARR]参数未设置');
        $error = '';
        $result = httpPost($url, $data, $error, 
            array(
                'METHOD' => 'GET'));
        return json_decode($result, true);
    }

    /* 调用AppServ发码接口 */
    public function requestWcAppServToResend($data) {
        $url = C('WC_RESEND_ARR.url') or die('[WC_RESEND_ARR]参数未设置');
        $error = '';
        $result = httpPost($url, $data, $error, 
            array(
                'METHOD' => 'GET'));
        return json_decode($result, true);
    }
    
    // 发往sso接口
    public function requestSsoInterface($action, $data) {
        $url = C('SSO_INTERFACE_URL') or die('[SSO_INTERFACE_URL]参数未设置');
        $url = $url . '&a=' . $action;
        $result = $this->requestSsoServ($data, $url);
        return empty($result[$action]) ? $result : $result[$action];
    }
}

