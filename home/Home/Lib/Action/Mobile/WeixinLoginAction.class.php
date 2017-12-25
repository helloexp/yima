<?php

class WeixinLoginAction extends Action {

    public $appid;

    public $secret;

    public $wxid;

    public function _initialize() {
        $this->appid = C('WEIXIN.appid');
        $this->secret = C('WEIXIN.secret');
        
        /*
         * $this->appid = 'wxc87ca4da3a5e5a03'; $this->secret =
         * '01ab48566a1a7f821084172bc0000b9e';
         */
        /* 测试账号 */
        // $this->appid = 'wx6a9bb22c22247085';
        // $this->secret = '9865fb234e80366560bf069374e88eb4';
        
        // $this->appid = 'wx5acb63e448b4fc22';
        // $this->secret = '18b40cd823f7e3ba4f4b69d74752016a';
    }
    
    // 授权页面
    public function index() {
        // $type=1;
        // 授权地址
        $open_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
        // 回调地址
        $backurl = U('Mobile/WeixinLogin/callback', '', '', '', TRUE);
        // 授权参数
        $opt_arr = array(
            'appid' => $this->appid, 
            'redirect_uri' => $backurl, 
            'response_type' => 'code', 
            'scope' => 'snsapi_base');
        // 'scope'=> 'snsapi_userinfo'
        
        $link = http_build_query($opt_arr);
        $gourl = $open_url . $link . '#wechat_redirect';
        // header('location:'.$gourl);
        redirect($gourl);
    }
    
    // 回调
    public function callback() {
        $code = I('code', null);
        if (empty($code))
            $this->error('参数错误！');
        $result = $this->getOpenid($code);
        
        $access_token = $result['access_token'];
        $openid = $result['openid'];
        if ($access_token == '' || $openid == '')
            $this->error('获取授权openid失败！');
            
            // 获取用户信息
            // $wxUserInfo = $this->getUser($access_token,$openid);
            // if($wxUserInfo === false){
            // $this->error('获取用户信息失败！');
            // }
            
        // 判断是否绑定
        session('Mwxid', $openid);
        
        $this->wxid = session('Mwxid');
        
        $this->redirect(U('Mobile/Index/login'));
    }
    
    // 获取openid
    protected function getOpenid($code) {
        if (empty($code))
            return false;
        
        $apiUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' .
             $this->appid . '&secret=' . $this->secret . '&code=' . $code .
             '&grant_type=authorization_code';
        $result = $this->httpsGet($apiUrl);
        if (! $result)
            return false;
        
        $result = json_decode($result, true);
        if ($result['errcode'] != '') {
            return false;
            // $this->error('获取access_token失败！'.$result['errcode'].":".$result['errmsg']);
        }
        return $result;
    }
    
    // 获取用户信息
    protected function getUser($access_token, $openid) {
        if (empty($access_token) || empty($openid)) {
            $this->error('参数不能为空！');
        }
        $userUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' .
             $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $wxUserInfo = $this->httpsGet($userUrl);
        $wxUserInfo = json_decode($wxUserInfo, true);
        if ($wxUserInfo['errcode'] || empty($wxUserInfo)) {
            return false;
        } else {
            return $wxUserInfo;
        }
    }
    
    // 请求接口
    protected function httpsGet($apiUrl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $result = curl_exec($ch);
        return $result;
    }
}