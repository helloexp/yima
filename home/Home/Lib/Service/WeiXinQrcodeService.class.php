<?php
// 微信推广二维码接口
class WeiXinQrcodeService {

    public $appId;

    public $appSecret;

    public $accessToken;

    public $error = '';

    public $thisClassName = 'WeiXinQrcodeService';
    // 初始化
    public function init($appId, $appSecret, $accessToken = '') {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        if ($accessToken == '') {
            $this->getToken();
        } else {
            $this->accessToken = $accessToken;
        }
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
        $result = httpPost($apiUrl, '', $this->error,
            array(
                'TIMEOUT' => 30));
        // $result =
        // '{"access_token":"VRrMAUaH3KOPFfJYM6g9ZiSLgBTTuqjiXdkx3qrxKz6bduGlbZ3f7usQkApFhASXSyzbCHliQp80lvoo6y4FKrL_MGbHvtEn7vJNj3Li6uzhwM1aehTQWl-ZpA83MAAl3-3Frr-PPjQR9dvyA55xHQ","expires_in":7200}'
        // ;
        $result = json_decode($result, true);
        $accessToken = $result['access_token'];
        $this->accessToken = $accessToken;
        return $result;
    }

    /**
     * 创建并自动重置token $data:{"expire_seconds": 1800, "action_name": "QR_SCENE",
     * "action_info": {"scene": {"scene_id": 123}}}
     */
    public function send($data, $apiUrl) {
        $data = $this->formatJson($data, 
            array(
                'expire_seconds' => 0,  // 该二维码有效时间，以秒为单位。 最大不超过1800。
                'action_name' => 'QR_LIMIT_SCENE',  // 二维码类型，QR_SCENE为临时,QR_LIMIT_SCENE为永久
                'action_info' => array(
                    'scene' => array(
                        'scene_id' => 1)))); // 场景值ID，
        
        for ($i = 0; $i < 3; $i ++) {
            if (! $this->accessToken) {
                $tokenResult = $this->getToken();
                // 获取token失败
                if (! $tokenResult || $tokenResult['errcode'] != '0') {
                    return array(
                        'status' => '0', 
                        'info' => $tokenResult['errmsg']);
                }
            }
            $accessToken = $this->accessToken;
            $error = '';
            Log::write('---第:' . $i . '次请求---');
            $result_str = httpPost($apiUrl . $accessToken, $data, $error, 
                array(
                    'TIMEOUT' => 30));
            Log::write($result_str, $this->thisClassName);
            $result = json_decode($result_str, true);
            // 如果没有获取到，睡一秒继续来一次
            if (! $result) {
                continue;
            }
            // token失效的话再获取一次
            $result['errcode'] = $this->accessToken == 'error' ? '42001' : $result['errcode'];
            if ($result['errcode'] == '42001' || $result['errcode'] == '40001') {
                Log::write("重新获取微信token");
                $tokenResult = $this->getToken();
                // 获取token失败
                if (! $tokenResult || $tokenResult['errcode']) {
                    Log::write("重新获取微信token失败");
                    return $tokenResult;
                }
                continue;
            }
            // 查看状态
            break;
        }
        return $result;
    }

    /**
     * 获取二维码 data：scene_id:场景号
     */
    public function getQrcodeImg($data) {
        $data = array(
            'action_info' => array(
                'scene' => array(
                    'scene_id' => $data['scene_id']))); // 场景值ID，
        
        $data = $this->formatJson($data, 
            array(
                'expire_seconds' => 0,  // 该二维码有效时间，以秒为单位。 最大不超过1800。
                'action_name' => 'QR_LIMIT_SCENE',  // 二维码类型，QR_SCENE为临时,QR_LIMIT_SCENE为永久
                'action_info' => array(
                    'scene_id' => 1))); // 场景值ID，
        
        $accessToken = $this->accessToken;
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=';
        $sendResult = $this->send($data, $apiUrl);
        /*
         * //测试数据 $sendResult = array(
         * 'ticket'=>'gQG28DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL0FuWC1DNmZuVEhvMVp4NDNMRnNRAAIEesLvUQMECAcAAA==',
         * 'expire_seconds '=>'1800', );
         */
        if (! $sendResult || $sendResult['errcode'] != 0) {
            $result = array(
                'status' => '0', 
                'info' => '失败', 
                'errcode' => $sendResult['errcode'], 
                'errmsg' => $sendResult['errmsg']);
        } else {
            // 获取到图片
            $ticket = $sendResult['ticket'];
            $ticket_url = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' .
                 $ticket;
            $img_content = $this->httpGet($ticket_url, '', $this->error, 
                array(
                    'METHOD' => 'GET'));
            $result = array(
                'status' => '1', 
                'info' => '成功', 
                'ticket_url' => $ticket_url, 
                'img_content' => $img_content, 
                'img_url' => $sendResult['url']);
        }
        return $result;
    }
    
    // unicode字符转可见
    public function unicodeDecode($name) {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 
            create_function('$matches', 
                'return mb_convert_encoding(pack("H*", $matches[1]), "utf-8", "UCS-2BE");'), 
            $name);
    }
    // 格式化可见json
    public function formatJson($data, $default = array()) {
        if (is_array($data)) {
            $data = array_merge($default, $data);
            $data = json_encode($data);
        }
        $json = $this->unicodeDecode($data);
        return $json;
    }

    public function httpGet($url, $data = null, $error = null, $opt = array()) {
        $opt = array_merge(
            array(
                'TIMEOUT' => 30, 
                'METHOD' => 'GET'), $opt);
        // 创建post请求参数
        import('@.ORG.Net.FineCurl') or die('[@.ORG.Net.FineCurl]导入包失败');
        $socket = new FineCurl();
        $socket->setopt('URL', $url);
        $socket->setopt('TIMEOUT', $opt['TIMEOUT']);
        $socket->setopt('HEADER_TYPE', $opt['METHOD']);
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        Log::write('请求：' . $url . '参数：' . $data, 'REMOTE');
        $result = $socket->send($data);
        $error = $socket->error();
        // 记录日志
        if ($error) {
            Log::write($error, 'ERROR');
        }
        return $result;
    }
}