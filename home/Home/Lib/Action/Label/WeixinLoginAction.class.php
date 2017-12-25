<?php

class WeixinLoginAction extends MyBaseAction {

    public $appid;

    public $secret;

    public $batch_wap_url;

    public function _initialize() {
        // 判断是否是微信过来的
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            exit();
        }
        parent::_initialize();
        /* 测试账号 */
        $this->appid = 'wx6a9bb22c22247085';
        $this->secret = '9865fb234e80366560bf069374e88eb4';
        $url_arr = C('BATCH_WAP_URL');
        $this->batch_wap_url = U($url_arr[$this->batch_type], 
            array(
                'id' => $this->id));
        // $this->appid = 'wx5acb63e448b4fc22';
        // $this->secret = '18b40cd823f7e3ba4f4b69d74752016a';
    }
    
    // 授权页面
    public function index() {
        $from_user_id = I('from_user_id', NULL);
        $type = I('type', 1);
        // 授权地址
        $open_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
        // 回调地址
        $backurl = U('Label/WeixinLogin/callback', 
            array(
                'id' => $this->id, 
                'from_user_id' => $from_user_id, 
                'type' => $type), '', '', TRUE);
        // 授权参数
        $opt_arr = array(
            'appid' => $this->appid, 
            'redirect_uri' => $backurl, 
            'response_type' => 'code', 
            'scope' => $type == '1' ? 'snsapi_base' : 'snsapi_userinfo');
        // 'scope'=> 'snsapi_userinfo'
        
        $link = http_build_query($opt_arr);
        $gourl = $open_url . $link . '#wechat_redirect';
        // header('location:'.$gourl);
        redirect($gourl);
    }
    
    // 回调
    public function callback() {
        $code = I('code', null);
        $type = I('type', 1);
        $from_user_id = I('from_user_id', NULL);
        if (empty($code))
            $this->error('参数错误！');
        $result = $this->getOpenid($code);
        
        $access_token = $result['access_token'];
        $openid = $result['openid'];
        if ($access_token == '' || $openid == '')
            $this->error('获取授权openid失败！');
        
        $batch_type = M('tmarketing_info')->field('batch_type')
            ->where(array(
            'id' => $this->batch_id))
            ->find();
        // 是否已经授权
        $wxuser = M('twx_wap_user')->where(
            array(
                'openid' => $openid))->find();
        if ($wxuser) {
            $this->wxLogin($wxuser['id'], $from_user_id);
            redirect($this->batch_wap_url);
            exit();
        }
        
        if ($type == '1') {
            $jump_url = U('Label/WeixinLogin/index', 
                array(
                    'id' => $this->id, 
                    'type' => '2', 
                    'from_user_id' => $from_user_id));
            redirect($jump_url);
            exit();
        }
        
        // 获取用户信息
        $wxUserInfo = $this->getUser($access_token, $openid);
        if ($wxUserInfo === false) {
            $this->error('获取用户信息失败！');
        }
        
        // 记录用户信息
        $user_info = $this->inTabel($openid, $access_token, $wxUserInfo);
        if ($user_info) {
            $this->wxLogin($user_info, $from_user_id);
            redirect($this->batch_wap_url);
            exit();
        } else {
            $this->error('授权登录失败!');
        }
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
    
    // 写入表
    protected function inTabel($openid, $access_token, $wxUserInfo = array()) {
        $wxarr = M('twx_wap_user')->where(
            array(
                'openid' => $openid))->find();
        if ($wxarr['id'])
            return $wxarr['id'];
        
        $in_arr = array(
            'node_id' => $this->node_id, 
            'label_id' => $this->id, 
            'add_time' => date('YmdHis'), 
            'nickname' => $wxUserInfo['nickname'], 
            'sex' => $wxUserInfo['sex'], 
            'province' => $wxUserInfo['province'], 
            'city' => $wxUserInfo['city'], 
            'headimgurl' => $wxUserInfo['headimgurl'], 
            'openid' => $openid, 
            'access_token' => $access_token);
        $wxid = M('twx_wap_user')->add($in_arr);
        if (! $wxid) {
            return false;
        }
        return $wxid;
    }
    
    // 登录session
    protected function wxLogin($wxid, $from_user_id) {
        session('wxid', $wxid);
        if (! empty($from_user_id) && $wxid != $from_user_id) {
            session('from_user_id', $from_user_id);
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