<?php

/*
 * 机构微信登录
 */
class DFWeixinLoginNodeAction extends Action {

    public $appid;

    public $secret;

    public $batch_wap_url;

    public $node_id;

    const __REAL_BACK_URL__ = '__REAL_BACK_URL__';

    public function _initialize() {
        // to-do debug
        // $_SERVER['HTTP_USER_AGENT'] = 'MicroMessenger';//模拟微信
        // 判断是否是微信过来的
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            // to-do debug
            echo '请从微信访问';
            exit();
        }
        // $url_arr = C('BATCH_WAP_URL');
        // $this->batch_wap_url = session(self::__REAL_BACK_URL__) or
        // $this->batch_wap_url =
        // U($url_arr[$this->batch_type],array_merge(array(
        // 'id'=>$this->id
        // )));
        $this->node_id = C('df.node_id');
        // 取当前机构微信设置
        $weixinInfo = M('tweixin_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        if (! $weixinInfo) {
            $this->error("未绑定微信公众账号");
            exit();
        }
        if (empty($weixinInfo['app_id']) || empty($weixinInfo['app_secret'])) {
            $this->error("未绑定微信公众账号api");
            exit();
        }
        // to-do debug
        // 测试账号 谢堂民的微信测试号
        // $this->appid = 'wx2cd8c40f859964c9';
        // $this->secret = '99452713461eb122eb4d02a492a028db';
        
        $this->appid = $weixinInfo['app_id'];
        $this->secret = $weixinInfo['app_secret'];
    }
    
    // 跳转到授权页面
    public function index() {
        // 真正要回跳的地址
        $realBackUrl = I('backurl', '', 'html_entity_decode');
        session(self::__REAL_BACK_URL__, urldecode($realBackUrl)); // 记录session一下将要跳转的真正地址
        
        $type = I('type', '0'); // 1是 基本信息
                                // 授权地址
        $open_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
        // 回调地址
        $backurl = U('Df/DFWeixinLoginNode/callback', 
            array(
                'type' => $type), '', '', TRUE);
        // 授权参数
        $opt_arr = array(
            'appid' => $this->appid, 
            'redirect_uri' => $backurl, 
            'response_type' => 'code', 
            'scope' => $type == '0' ? 'snsapi_base' : 'snsapi_userinfo');
        // 'scope'=> 'snsapi_userinfo'
        
        $link = http_build_query($opt_arr);
        $gourl = $open_url . $link . '#wechat_redirect';
        log_write("jump:" . $gourl);
        // header('location:'.$gourl);
        redirect($gourl);
    }
    
    // 回调
    public function callback() {
        log_write(print_r(I('get.'), true));
        // 得到响就码
        $code = I('code', null);
        $type = I('type', '0');
        $backurl = I('backurl', session(self::__REAL_BACK_URL__));
        if (empty($code))
            $this->error('参数错误！');
            // 获取用户信息，获取完 code失效
        $result = $this->getOpenid($code);
        $access_token = $result['access_token'];
        $openid = $result['openid'];
        if ($access_token == '' || $openid == '')
            $this->error('获取授权openid失败！');
        
        $wxSessInfo = array(
            'access_token' => $access_token, 
            'openid' => $openid, 
            'att_flag' => true);
        
        // 查询DF会员信息
        $memberInfo = M('tfb_df_member')->where("openid='{$openid}'")->find();
        if (! $memberInfo) {
            $wxSessInfo['att_flag'] = false;
        }
        $this->_setWxSession($wxSessInfo);
        if ($type == '0') {
            redirect($backurl);
            return;
        }
    }
    
    // 获取openid
    protected function getOpenid($code) {
        if (empty($code))
            return false;
        
        $apiUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' .
             $this->appid . '&secret=' . $this->secret . '&code=' . $code .
             '&grant_type=authorization_code';
        $error = '';
        $result = httpPost($apiUrl, '', $error, 
            array(
                'METHOD' => 'GET'));
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
        $error = '';
        $wxUserInfo = httpPost($userUrl, '', $error, 
            array(
                'METHOD' => 'GET'));
        $wxUserInfo = json_decode($wxUserInfo, true);
        if ($wxUserInfo['errcode'] || empty($wxUserInfo)) {
            return false;
        } else {
            return $wxUserInfo;
        }
    }

    public function _setWxSession($val) {
        log_write(print_r($val, true), '------------');
        log_write(
            "weixin oauth result：" . print_r(
                array(
                    'node_id' => $this->node_id, 
                    'wx_sess' => $val), true));
        session('node_wxid_' . $this->node_id, $val);
    }

    public function _getWxSession() {
        return session('node_wxid_' . $this->node_id);
    }
    
    // 退出微信登录
    public function logout() {
        session('node_wxid_' . $this->node_id, null);
        echo 'success';
    }
}