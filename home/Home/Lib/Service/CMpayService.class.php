<?php

/**
 * 和包支付相关 model
 *
 * @author : John zeng<zengc@imageco.com.cn> Date: 2016/01/06
 */
class CMpayService {

    private $key;
    // 商户密钥
    private $url;
    // 和包接入地址
    
    public function __construct() {
        $this->key = C('CMPAY.key'); // 商户密钥
        $this->url = C('CMPAY.sendUrl'); // 和包接入地址
    }

    /**
     * Description 将数据按照和包方式进行md5加密
     *
     * @param array $odata 需要加密的数据
     * @return string 加密后的数据
     */
    public function MD5sign($odata) {
        $signdata = self::hmac("", $odata);
        return self::hmac($this->key, $signdata);
    }

    /**
     * Description 将数据按照和包方式进行hmac加密
     *
     * @param array $data 需要加密的数据
     * @return string 加密后的数据
     */
    public function hmac($key, $data) {
        $b = 64;
        if (strlen($key) > $b) {
            $key = pack("H*", md5($key));
        }
        $key = str_pad($key, $b, chr(0x00));
        $ipad = str_pad('', $b, chr(0x36));
        $opad = str_pad('', $b, chr(0x5c));
        $k_ipad = $key ^ $ipad;
        $k_opad = $key ^ $opad;
        log_write('sendPay INFO: $KEY=>' . $key, ' data=>' . $data);
        return md5($k_opad . pack("H*", md5($k_ipad . $data)));
    }

    /**
     * Description 把http请求返回数组 格式化成数组
     *
     * @return array
     */
    public function parseRecv($source) {
        $ret = array();
        $temp = explode("&", $source);
        
        foreach ($temp as $value) {
            $index = strpos($value, "=");
            $_key = substr($value, 0, $index);
            $_value = substr($value, $index + 1);
            $ret[$_key] = $_value;
        }
        
        return $ret;
    }

    /**
     * Description 发送HTTP请求
     *
     * @param string $url 请求URL地址 array $data 请求数据数组
     * @return array
     */
    public function postData($data) {
        $url = parse_url($this->url);
        if (! $url) {
            return "请求URL地址为空";
        }
        if (! isset($url['port'])) {
            $url['port'] = "";
        }
        
        if (! isset($url['query'])) {
            $url['query'] = "";
        }
        
        $encoded = http_build_query($data);
        
        $urlHead = null;
        $urlPort = $url['port'];

        if ($url['scheme'] == "https") {
            $urlHead = "ssl://" . $url['host'];
            if ($url['port'] == null || $url['port'] == 0) {
                $urlPort = 443;
            }
        } else {
            $urlHead = $url['host'];
            if ($url['port'] == null || $url['port'] == 0) {
                $urlPort = 80;
            }
        }
        log_write("YGM" . $urlHead);
        $fp = fsockopen($urlHead, $urlPort);
        if (! $fp) {
            return "Failed to open socket to $url[host]";
        }

        $tmp = "";
        $tmp .= sprintf("POST %s%s%s HTTP/1.0\r\n", $url['path'], 
            $url['query'] ? "?" : "", $url['query']);
        $tmp .= "Host: $url[host]\r\n";
        $tmp .= "Content-type: application/x-www-form-urlencoded\r\n";
        $tmp .= "Content-Length: " . strlen($encoded) . "\r\n";
        $tmp .= "Connection: close\r\n\r\n";
        $tmp .= "$encoded\r\n";
        fputs($fp, $tmp);
        log_write('发送信息:$fp=>' . $fp . '$data=>' . $tmp);
        $line = fgets($fp, 1024);
        if (! preg_match("#^HTTP/1\.. 200#i", $line)) {
            $logstr = "MSG" . $line;
            log_write("YGM" . $logstr);
            return array(
                "FLAG" => 0, 
                "MSG" => $line);
        }
        
        $results = "";
        $inheader = 1;
        while (! feof($fp)) {
            $line = fgets($fp, 1024);
            if ($inheader && ($line == "\n" || $line == "\r\n")) {
                $inheader = 0;
            } elseif (! $inheader) {
                $results .= $line;
            }
        }
        fclose($fp);
        return array(
            "FLAG" => 1, 
            "MSG" => $results);
    }

    /**
     * Description 把UTF-8 编号数据转换成 GB2312 忽略转换错误
     *
     * @return array
     */
    public function encodeString($source) {
        $ret = urlencode($source);
        return $ret;
    }

    /**
     * Description 把GB2312 编号数据转换成 UTF-8 忽略转换错误
     *
     * @return array
     */
    public function decodeString($source) {
        $temp = urldecode($source);
        return $temp;
    }

    /**
     * Description 返回URL处理
     * string   $payUrl    请求数据信息
     *
     * @return array
     */
    function parseUrl($payUrl) {
        $temp = explode("<hi:$$>", $payUrl);
        $url_lst = explode("<hi:=>", $temp[0]);
        $url = $url_lst[1];
        $method_lst = explode("<hi:=>", $temp[1]);
        $method = $method_lst[1];
        $sessionid_lst = explode("<hi:=>", $temp[2]);
        $sessionid = $sessionid_lst[1];
        $url = $url . "?SESSIONID=" . $sessionid;
        $rpayUrl = array();
        $rpayUrl["url"] = $url;
        $rpayUrl["method"] = $method;
        return $rpayUrl;
    }
    
    
    /**
     * Description
     * 返回signData处理
     * array   $data    请求数据数组
     *
     * @return array
     */
    function returnSignData($data) {
        $merchantId = $data["merchantId"];
        $payNo = $data["payNo"];
        $returnCode = $data["returnCode"];
        $message = $data["message"];
        $signType = $data["signType"];
        $type = $data["type"];
        $version = $data["version"];
        $amount = $data["amount"];
        $amtItem = $data["amtItem"];
        $bankAbbr = $data["bankAbbr"];
        $mobile = $data["mobile"];
        $orderId = $data["orderId"];
        $payDate = $data["payDate"];
        $accountDate = $data["accountDate"];
        $reserved1 = $data["reserved1"];
        $reserved2 = $data["reserved2"];
        $status = $data["status"];
        $payType = $data["payType"];
        $orderDate = $data["orderDate"];
        $fee = $data["fee"];
        $vhmac = $data["hmac"];
        
        // 组装签字符串
        $signData = $merchantId . $payNo . $returnCode . $message . $signType . $type . $version . $amount . $amtItem . $bankAbbr . $mobile . $orderId . $payDate . $accountDate . $reserved1 . $reserved2 . $status . $orderDate . $fee;
        
        return $signData;
    }
    
}
