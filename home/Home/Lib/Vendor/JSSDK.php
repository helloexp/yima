<?php

class JSSDK {

    private $appId;

    private $appSecret;

    public function __construct($appId, $appSecret, $accessToken = '') {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->accessToken = $accessToken;
    }

    public function getSignPackage($url = '') {
        $jsapiTicket = $this->getJsApiTicket();
        $url = $url ? $url : "http://" . $_SERVER['HTTP_HOST'] .
             $_SERVER['REQUEST_URI'];
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=" .
             $url;
        
        $signature = sha1($string);
        
        $signPackage = array(
            "appId" => $this->appId, 
            "nonceStr" => $nonceStr, 
            "timestamp" => $timestamp, 
            "url" => $url, 
            "signature" => $signature, 
            "rawString" => $string);
        return $signPackage;
    }

    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i ++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getJsApiTicket() {
        // jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = S($this->appId . '_jsapi_ticket_json');
        if ($data->expire_time < time()) {
            $accessToken = $this->getAccessToken();
            if (! $accessToken) {
                return false;
            }
            $url = "http://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$accessToken";
            $res = json_decode($this->httpGet($url));
            $ticket = $res->ticket;
            if ($ticket) {
                $data->expire_time = time() + 7000;
                $data->jsapi_ticket = $ticket;
                S($this->appId . '_jsapi_ticket_json', $data);
            }
        } else {
            $ticket = $data->jsapi_ticket;
        }
        if (! $ticket) {
            log_write("JSSDK_ERROR ticket is null");
        }
        return $ticket;
    }

    private function getAccessToken() {
        // access_token 应该全局存储与更新，以下代码以写入到文件中做示例
        $data = S($this->appId . "_access_token_json");
        if ($data->expire_time < time()) {
            $wx_grant = D('WeiXinGrant', 'Service');
            $wx_grant->init();
            $result = $wx_grant->refresh_weixin_token_by_appid($this->appId);
            if ($result !== false) {
                return $result;
            }
            
            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$this->appId&secret=$this->appSecret";
            $res = $this->httpGet($url);
            $res = json_decode($res);
            $access_token = $res->access_token;
            
            if ($access_token) {
                $data->expire_time = time() + 7000;
                $data->access_token = $access_token;
                S($this->appId . "_access_token_json", $data);
            }
        } else {
            $access_token = $data->access_token;
        }
        return $access_token;
    }

    private function httpGet($url) {
        log_write("JSSDK:" . $url);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        
        $res = curl_exec($curl);
        curl_close($curl);
        
        return $res;
    }
}

