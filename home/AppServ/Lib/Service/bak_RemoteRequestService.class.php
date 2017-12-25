<?php

/* 这儿封装不同接口的请求 */

class RemoteRequestService_bak {
    // 请求支撑验证
    public $opt = array();

    public function __construct() {
    }

    /* 设置参数 */
    public function setopt() {
    }

    /*
     * 支撑终端签到接口 data 数据 inputMacStr 请求加密字符串 mKey 主密钥
     */
    public function requestPosLoginServ($data, $inputMacStr, $mKey) {
        $url = C('ISS_POS_SERV_URL') or die('[ISS_POS_SERV_URL]参数未设置');
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk', '<business_trans version="1.0" >%s</business_trans>');
        // 计算mac
        import('@.ORG.Com.WebPos3Des') or die('@.ORG.Com.WebPos3Des导入失败');
        $mObj       = new WebPos3Des();
        $macStr     = $mObj->login_mac($inputMacStr, $mKey);
        $sendStr    = "xml={$str}&mac={$macStr}";
        $result_str = httpPost($url, $sendStr, $error);
        parse_str($result_str, $result);
        if (!$result['xml']) {
            return array(
                    'result' => array(
                            'id'      => '-1',
                            'comment' => 'XML is null',
                    ),
            );
        }
        $xml           = new Xml();
        $result['xml'] = get_magic_quotes_gpc() ? stripslashes($result['xml']) : $result['xml'];
        $arr           = $xml->parse($result['xml']);
        $arr           = $xml->getArrayNoRoot();
        if ($xml->error()) {
            return array(
                    'result' => array(
                            'id'      => '-1',
                            'comment' => $xml->error(),
                    ),
            );
        }
        $arr['_mac'] = $result['mac'];
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');

        return $arr;
    }

    /*
     * 支撑终端验证接口 data 数据 inputMacStr 请求加密字符串 mKey 主密钥 wKey 工作密钥
     */
    public function requestPosTransServ($data, $inputMacStr, $mKey, $wKey) {
        $url = C('ISS_POS_SERV_URL') or die('[ISS_POS_SERV_URL]参数未设置');
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk', '<business_trans version="1.0" >%s</business_trans>');
        // 计算mac
        import('@.ORG.Com.WebPos3Des') or die('@.ORG.Com.WebPos3Des导入失败');
        $mObj       = new WebPos3Des();
        $macStr     = $mObj->trans_mac($inputMacStr, $mKey, $wKey);
        $sendStr    = "xml={$str}&mac={$macStr}";
        $result_str = httpPost($url, $sendStr, $error);
        parse_str($result_str, $result);
        if (!$result['xml']) {
            return array(
                    'result' => array(
                            'id'      => '-1',
                            'comment' => 'XML is null',
                    ),
            );
        }
        $xml           = new Xml();
        $result['xml'] = get_magic_quotes_gpc() ? stripslashes($result['xml']) : $result['xml'];
        $arr           = $xml->parse($result['xml']);
        $arr           = $xml->getArrayNoRoot();
        if ($xml->error()) {
            return array(
                    'result' => array(
                            'id'      => '-1',
                            'comment' => $xml->error(),
                    ),
            );
        }
        $arr['_mac'] = $result['mac'];
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');

        return $arr;
    }

    /* 发往支撑 */
    public function requestIssServ($data) {
        $url = C('ISS_SEND_SERV_URL') or die('[ISS_SEND_SERV_URL]参数未设置');
        $mac_key = C('ISS_MAC_KEY') or die('[ISS_MAC_KEY]参数未设置');
        $timeout = C('ISS_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml        = new Xml();
        $str        = $xml->getXMLFromArray($data, 'gbk');
        $mac_str    = md5($mac_key . $str . $mac_key);
        $sendStr    = http_build_query(array(
                'xml' => $str,
                'mac' => $mac_str,
        ));
        $result_str = httpPost($url, $sendStr, $error, array(
                        'TIMEOUT' => $timeout,
                ));
        parse_str($result_str, $result);
        if (!$result['xml']) {
            return array(
                    'Status' => array(
                            'StatusCode' => '-1',
                            'StatusText' => 'XML is null',
                    ),
            );
        }
        $xml           = new Xml();
        $result['xml'] = get_magic_quotes_gpc() ? stripslashes($result['xml']) : $result['xml'];
        $arr           = $xml->parse($result['xml']);
        $arr           = $xml->getAll();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return array(
                    'Status' => array(
                            'StatusCode' => '-1',
                            'StatusText' => $xml->error(),
                    ),
            );
        }
        $arr['_mac'] = $result['mac'];

        return $arr;
    }

    /* 发往营账接口 */
    public function requestYzServ($data) {
        $url = C('YZ_SERV_URL') or die('[YZ_SERV_URL]参数未设置');
        $mac_key = C('YZ_MAC_KEY') or die('[YZ_MAC_KEY]参数未设置');
        $timeout = C('YZ_REQ_TIME') or $timeout = 30; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml        = new Xml();
        $str        = $xml->getXMLFromArray($data, 'gbk');
        $mac_str    = md5($mac_key . $str . $mac_key);
        $sendStr    = http_build_query(array(
                'xml' => $str,
                'mac' => $mac_str,
        ));
        $result_xml = httpPost($url, $sendStr, $error, array(
                        'TIMEOUT' => $timeout,
                ));
        if (!$result_xml) {
            return array(
                    'ErrorRes' => array(
                            'Status' => array(
                                    'StatusCode' => '-1',
                                    'StatusText' => 'XML is null',
                            ),
                    ),
            );
        }
        $xml        = new Xml();
        $result_xml = get_magic_quotes_gpc() ? stripslashes($result_xml) : $result_xml;
        $arr        = $xml->parse($result_xml);
        if ($xml->error()) {
            return array(
                    'ErrorRes' => array(
                            'Status' => array(
                                    'StatusCode' => '-1',
                                    'StatusText' => $xml->error(),
                            ),
                    ),
            );
        }
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');

        return $arr;
    }

    /* 发往SSO用户接口 */
    public function requestSsoUser($data) {
        $url = C('SSO_USER_SERV_URL') or die('[SSO_USER_SERV_URL]参数未设置');
        $result = httpPost($url, $data, $error, array(
                        'METHOD' => 'GET',
                ));

        return json_decode($result, true);
    }
}