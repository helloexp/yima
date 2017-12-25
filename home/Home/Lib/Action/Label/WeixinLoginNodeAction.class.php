<?php

/*
 * 机构微信登录
 */
class WeixinLoginNodeAction extends MyBaseAction {

    public $appid;

    public $secret;

    public $wechatInfo;

    public $auth_flag;

    public $batch_wap_url;

    const __REAL_BACK_URL__ = '__REAL_BACK_URL__';

    const WX_AUTH_TYPE_IMAGECO = 0;

    /**
     *
     * @var WeiXinService
     */
    public $WeiXinService;

    public function _initialize() {
        // to-do debug
        
        // 判断是否是微信过来的
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            // to-do debug
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro7.png', 
                    'errorTxt' => '请使用微信浏览器！', 
                    'errorSoftTxt' => '亲，这个页面要用微信浏览器打开哦~'));
            exit();
        }
        parent::_initialize();
        $url_arr = C('BATCH_WAP_URL');
        $this->batch_wap_url = session(self::__REAL_BACK_URL__) or $this->batch_wap_url = U(
            $url_arr[$this->batch_type], 
            array_merge(array(
                'id' => $this->id)));
        
        // to-do debug
        /*
         * 测试账号 谢堂民的微信测试号 $this->appid = 'wx2cd8c40f859964c9'; $this->secret =
         * '99452713461eb122eb4d02a492a028db';
         */
        
        $configData = array();
        if (isset($this->marketInfo['config_data']) &&
             is_string($this->marketInfo['config_data'])) {
            $configData = unserialize($this->marketInfo['config_data']);
        }
        if (isset($configData['wx_auth_type']) &&
             $configData['wx_auth_type'] == self::WX_AUTH_TYPE_IMAGECO) { // 翼码授权
            $this->appid = C('WEIXIN.appid'); // 应用ID
            $this->secret = C('WEIXIN.secret'); // 应用密钥
        } else { // 机构自己的微信号
                 // 取当前机构微信设置
            $wechatInfo = $this->WeiXinService->getWeixinInfoByNodeId(
                $this->node_id);
            if (isset($wechatInfo['errno']) && $wechatInfo['errno'] < 0) {
                $this->error($wechatInfo['errmsg']);
            } else {
                $this->appid = $wechatInfo['app_id'];
                $this->secret = $wechatInfo['app_secret'];
                $this->auth_flag = $wechatInfo['auth_flag'];
                $this->wechatInfo = $wechatInfo;
            }
            
            $this->WeiXinService = D('WeiXin', 'Service');
        }
    }
    
    // //跳转到授权页面
    // public function index(){
    // $type = I('type','0');//1是 基本信息
    // $callbackUrl = U(
    // 'Label/WeixinLoginNode/callback',
    // array('id' => $this->id, 'type' => $type),
    // '',
    // '',
    // true
    // );
    // $result = $this->WeiXinService->wxAuthorizeAndRedirect($this->id, $type,
    // $callbackUrl);
    // if (isset($result['errmsg']) && $result['errmsg'] != '') {
    // $this->error($result['errmsg']);
    // }
    // }
    //
    // /**
    // *
    // */
    // public function callback()
    // {
    // log_write(print_r(I('get.'), true));
    // //得到响就码
    // $code = I('code', null);
    // $type = I('type', '0');
    // if (empty($code)) {
    // $this->error('参数错误！');
    // }
    //
    // $callbackResult = $this->WeiXinService->callbackAndRedirect($this->id,
    // $code, $this->node_id, $type);
    //
    // if (is_array($callbackResult) && isset($callbackResult['errmsg']) &&
    // $callbackResult['errmsg']) {
    // $this->error($callbackResult['errmsg']);
    // }
    // }

    
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
                    'id' => $this->id, 
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