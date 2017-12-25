<?php

/* 这儿封装不同接口的请求 */

class RemoteRequestService
{
    // 请求支撑验证
    public $opt = array();

    public function __construct()
    {
    }

    /* 设置参数 */
    public function setopt()
    {
    }

    /*
     * 支撑终端签到接口 data 数据 inputMacStr 请求加密字符串 mKey 主密钥
     */
    public function requestPosLoginServ($data, $inputMacStr, $mKey)
    {
        $url = C('ISS_POS_SERV_URL') or die('[ISS_POS_SERV_URL]参数未设置');
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk', '<business_trans version="1.0" >%s</business_trans>');
        // 计算mac
        import('@.ORG.Com.WebPos3Des') or die('@.ORG.Com.WebPos3Des导入失败');
        $mObj       = new WebPos3Des();
        $macStr     = $mObj->login_mac($inputMacStr, $mKey);
        $sendStr    = "xml={$str}&mac={$macStr}";
        $error      = '';
        $result_str = httpPost($url, $sendStr, $error);
        parse_str($result_str, $result);
        if (!$result['xml']) {
            return ['result' => ['id' => '-1', 'comment' => 'XML is null',]];
        }
        $xml           = new Xml();
        $result['xml'] = get_magic_quotes_gpc() ? stripslashes($result['xml']) : $result['xml'];
        $arr           = $xml->parse($result['xml']);
        $arr           = $xml->getArrayNoRoot();
        if ($xml->error()) {
            return ['result' => ['id' => '-1', 'comment' => $xml->error(),]];
        }
        $arr['_mac'] = $result['mac'];
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');

        return $arr;
    }

    /*
     * 支撑终端验证接口 data 数据 inputMacStr 请求加密字符串 mKey 主密钥 wKey 工作密钥
     */
    public function requestPosTransServ($data, $inputMacStr, $mKey, $wKey)
    {
        $url = C('ISS_POS_SERV_URL') or die('[ISS_POS_SERV_URL]参数未设置');
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk', '<business_trans version="1.0" >%s</business_trans>');
        // 计算mac
        import('@.ORG.Com.WebPos3Des') or die('@.ORG.Com.WebPos3Des导入失败');
        $mObj       = new WebPos3Des();
        $macStr     = $mObj->trans_mac($inputMacStr, $mKey, $wKey);
        $sendStr    = "xml={$str}&mac={$macStr}";
        $error      = '';
        $result_str = httpPost($url, $sendStr, $error);
        // 如果网络错误
        if ($error) {
            return ['result' => ['id' => '-1', 'comment' => $error,]];
        }
        parse_str($result_str, $result);
        if (!$result['xml']) {
            return ['result' => ['id' => '-1', 'comment' => 'XML is null',]];
        }
        $xml           = new Xml();
        $result['xml'] = get_magic_quotes_gpc() ? stripslashes($result['xml']) : $result['xml'];
        $arr           = $xml->parse($result['xml']);
        $arr           = $xml->getArrayNoRoot();
        if ($xml->error()) {
            return ['result' => ['id' => '-1', 'comment' => $xml->error(),]];
        }
        $arr['_mac'] = $result['mac'];
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');

        return $arr;
    }

    /* 发往支撑 */
    public function requestIssServ($data)
    {
        $url = C('ISS_SEND_SERV_URL') or die('[ISS_SEND_SERV_URL]参数未设置');
        $mac_key = C('ISS_MAC_KEY') or die('[ISS_MAC_KEY]参数未设置');
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml        = new Xml();
        $str        = $xml->getXMLFromArray($data, 'gbk');
        $mac_str    = md5($mac_key . $str . $mac_key);
        $sendStr    = http_build_query(['xml' => $str, 'mac' => $mac_str,]);
        $error      = '';
        $result_str = httpPost($url, $sendStr, $error, ['TIMEOUT' => $timeout,]);
        parse_str($result_str, $result);
        if (!$result['xml']) {
            return ['Status' => ['StatusCode' => '-1', 'StatusText' => 'XML is null',]];
        }
        $xml           = new Xml();
        $result['xml'] = get_magic_quotes_gpc() ? stripslashes($result['xml']) : $result['xml'];
        $arr           = $xml->parse($result['xml']);
        $arr           = $xml->getAll();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return ['Status' => ['StatusCode' => '-1', 'StatusText' => $xml->error(),]];
        }
        $arr['_mac'] = $result['mac'];

        return $arr;
    }

    /* 发往营账接口 */
    public function requestYzServ($data)
    {
        $url = C('YZ_SERV_URL') or die('[YZ_SERV_URL]参数未设置');
        $mac_key = C('YZ_MAC_KEY') or die('[YZ_MAC_KEY]参数未设置');
        $timeout = C('YZ_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml        = new Xml();
        $str        = $xml->getXMLFromArray($data, 'gbk');
        $mac_str    = md5($mac_key . $str . $mac_key);
        $sendStr    = http_build_query(['xml' => $str, 'mac' => $mac_str,]);
        $error      = '';
        $result_xml = httpPost($url, $sendStr, $error, ['TIMEOUT' => $timeout,]);
        if (!$result_xml) {
            return ['ErrorRes' => ['Status' => ['StatusCode' => '-1', 'StatusText' => 'XML is null',],],];
        }
        $xml        = new Xml();
        $result_xml = get_magic_quotes_gpc() ? stripslashes($result_xml) : $result_xml;
        $arr        = $xml->parse($result_xml);
        if ($xml->error()) {
            return ['ErrorRes' => ['Status' => ['StatusCode' => '-1', 'StatusText' => $xml->error(),],],];
        }
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');

        return $arr;
    }

    /* 发往SSO用户接口 */
    public function requestSsoUser($data)
    {
        $url = C('SSO_USER_SERV_URL') or die('[SSO_USER_SERV_URL]参数未设置');
        $error  = '';
        $result = httpPost($url, $data, $error, ['METHOD' => 'GET',]);

        return json_decode($result, true);
    }

    /* 调用AppServ发码接口 */
    public function requestWcAppServ($data)
    {
        $url = C('WC_APPSERV_URL') or die('[WC_APPSERV_URL]参数未设置');
        $error  = '';
        $result = httpPost($url, $data, $error, ['METHOD' => 'GET',]);

        return json_decode($result, true);
    }

    /* 调用WeiXinServ发码接口 */
    public function requestWcWeiXinServ($data)
    {
        $url = C('WC_WXCARD_URL') or die('[WC_WXCARD_URL]参数未设置');
        $error  = '';
        $result = httpPost($url, $data, $error, ['METHOD' => 'GET',]);

        return json_decode($result, true);
    }

    /* 调用WeiXinServ 红包发送接口 */
    public function requestWcWeixinRedPackServ($data)
    {
        $url = C('WC_WXREDPACK_URL') or die('[WC_WXREDPACK_URL]参数未设置');
        $error  = '';
        $result = httpPost($url, $data, $error, ['METHOD' => 'GET',]);

        return json_decode($result, true);
    }

    public function shorturl($long_url)
    {
        $apiUrl  = C('ISS_SERV_FOR_IMAGECO');
        $req_arr = array(
                'CreateShortUrlReq' => array(
                        'SystemID'      => C('ISS_SYSTEM_ID'),
                        'TransactionID' => time() . rand(10000, 99999),
                        'OriginUrl'     => '<![CDATA[' . $long_url . ']]>',
                ),
        );

        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml        = new Xml();
        $str        = $xml->getXMLFromArray($req_arr, 'gbk');
        $error      = '';
        $result_str = httpPost($apiUrl, $str, $error);
        log_write('$apiUrl：' . $apiUrl, 'INFO');
        log_write('$str：' . $str, 'INFO', 'SendCode');
        log_write('$result_str：' . $result_str, 'INFO');
        if ($error) {
            echo $error;
            return '';
        }

        $arr = $xml->parse($result_str);
        $arr = $xml->getArrayNoRoot();

        return $arr['Status']['StatusCode'] == '0000' ? $arr['ShortUrl'] : '';
    }

    /* 调用马上发发码接口 */
    public function requestPtsServ($data)
    {
        $url = C('PTS_SERV_URL') or die('[PTS_SERV_URL]参数未设置');
        $error  = '';
        $result = httpPost($url, $data, $error, ['METHOD' => 'GET',]);

        return json_decode(iconv('gbk', 'utf8', $result), true);
    }

    /**
     * 流量包
     *
     * @param $data
     *
     * @return mixed
     */
    public function requestMobileDataServ($data)
    {
        $url = C('MOBILE_DATA_URL') or die('[MOBILE_DATA_URL]参数未设置');
        $error  = '';
        $result = httpPost($url, $data, $error, ['METHOD' => 'GET',]);
        log_write('origin $result:' . var_export($result,1));
        $return = json_decode(iconv('gbk', 'utf8', $result), true);
        $returne = self::formateJSONErr(json_last_error());

        log_write('final $result:' . var_export($return,1));
        log_write('final $returne:' . $returne);
        return $return;
    }

    public static function formateJSONErr($errno)
    {
        switch($errno)
        {
            case JSON_ERROR_DEPTH:
                $error =  ' - Maximum stack depth exceeded';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $error = ' - Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $error = ' - Syntax error, malformed JSON';
                break;
            case JSON_ERROR_NONE:
            default:
                $error = '';
        }

        return $error;
    }


    /**
     * 流量包
     *
     * @param $data
     *
     * @return mixed
     */
    public function requestMobileHFServ($data)
    {
        $url = C('MOBILE_HF_URL') or die('[MOBILE_HF_URL]参数未设置');
        $error  = '';
        $result = httpPost($url, $data, $error, ['METHOD' => 'GET',]);
        log_write(__METHOD__ . '$result:' . $result);

        return json_decode(iconv('gbk', 'utf8', $result), true);
    }


    /* 调用马上发重发接口 */
    public function requestPtsServResend($data)
    {
        $url = C('PTS_SERV_RESEND_URL') or die('[PTS_SERV_RESEND_URL]参数未设置');
        $error  = '';
        $result = httpPost($url, $data, $error, ['METHOD' => 'GET',]);

        return json_decode(iconv('gbk', 'utf8', $result), true);
    }

    /* 调用马上发撤销接口 */
    public function requestPtsServCancel($data)
    {
        $url = C('PTS_SERV_CANCEL_URL') or die('[PTS_SERV_CANCEL_URL]参数未设置');
        $error  = '';
        $result = httpPost($url, $data, $error, ['METHOD' => 'GET',]);

        return json_decode(iconv('gbk', 'utf8', $result), true);
    }
}