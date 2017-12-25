<?php
// 微信菜单接口
class WeiXinMenuService {

    public $appId;

    public $appSecret;

    public $accessToken;

    public $error = '';
    // 初始化
    public function init($appId, $appSecret, $accessToken = '') {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->accessToken = $accessToken;
    }

    public function setToken($accessToken) {
        $this->accessToken = $accessToken;
    }
    // 获取token
    public function getToken() {
        // 判断是否授权模式
        $wx_grant = D('WeiXinGrant', 'Service');
        $wx_grant->init();
        $result = $wx_grant->refresh_weixin_token_by_appid($this->appId);
        if ($result !== false) {
            $this->accessToken = $result;
            return array(
                'errcode' => 0, 
                'access_token' => $result);
        }
        
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' .
             $this->appId . '&secret=' . $this->appSecret . '';
        $error = '';
        for ($i = 0; $i < 10; $i ++) {
            $result = httpPost($apiUrl, '', $error,
                array(
                    'TIMEOUT' => 30));
            if ($result) {
                break;
            } else {
                log_write($error, 'error');
            }
            usleep(500 * 1000);
        }
        // $result =
        // '{"access_token":"VRrMAUaH3KOPFfJYM6g9ZiSLgBTTuqjiXdkx3qrxKz6bduGlbZ3f7usQkApFhASXSyzbCHliQp80lvoo6y4FKrL_MGbHvtEn7vJNj3Li6uzhwM1aehTQWl-ZpA83MAAl3-3Frr-PPjQR9dvyA55xHQ","expires_in":7200}'
        // ;-- 调试用
        $result = json_decode($result, true);
        $accessToken = $result['access_token'];
        $this->accessToken = $accessToken;
        return $result;
    }
    // 创建菜单
    public function create($data) {
        $accessToken = $this->accessToken;
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token=' .
             $accessToken;
        
        log_write($apiUrl, 'wxmenu');
        log_write(print_r($data,true), 'wxmenu');
        
        // 判断网络
        $error = '';
        for ($i = 0; $i < 10; $i ++) {
            $result_str = httpPost($apiUrl, $data, $error, 
                array(
                    'TIMEOUT' => 30));
            if ($result_str) {
                break;
            } else {
                log_write($error, 'error');
            }
            usleep(500 * 1000);
        }
        
        log_write($result_str, 'wxmenu');
        $result = json_decode($result_str, true);
        return $result;
    }
    
    // 停用菜单
    public function stop($data) {
        $accessToken = $this->accessToken;
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=' .
             $accessToken;
        
        log_write($apiUrl, 'wxmenu');
        log_write($data, 'wxmenu');
        $error = '';
        for ($i = 0; $i < 10; $i ++) {
            $result_str = httpPost($apiUrl, $data, $error, 
                array(
                    'TIMEOUT' => 30, 
                    'METHOD' => 'GET'));
            if ($result_str) {
                break;
            } else {
                log_write($error, 'error');
            }
            usleep(500 * 1000);
        }
        log_write($result_str, 'wxmenu');
        $result = json_decode($result_str, true);
        return $result;
    }
    
    // unicode字符转可见
    public function unicodeDecode($name) {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 
            create_function('$matches', 
                'return mb_convert_encoding(pack("H*", $matches[1]), "utf-8", "UCS-2BE");'), 
            $name);
    }
}