<?php

/**
 * 获取微信用户的信息 add dongdong time 2015/0413
 */
class GetWxUserInfoAction extends MyBaseAction {

    public $appid;

    public $secret;

    protected $barr = array(
        '46' => 'MamaSjb', 
        '36' => 'Spelling');

    public function _initialize() {
        $this->judgeLiq();
        parent::_initialize();
        /* 测试账号 */
        $this->appid = C('WEIXIN.appid');
        $this->secret = C('WEIXIN.secret');
        
        // $this->appid = 'wx5acb63e448b4fc22';
        // $this->secret = '18b40cd823f7e3ba4f4b69d74752016a';
    }
    
    // 判断是否微信过来的。。。
    public function judgeLiq() {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($useragent, 'MicroMessenger') === false &&
             strpos($useragent, 'Windows Phone') === false) {
            $this->error('请使用微信浏览器');
        }
    }
    
    // 授权页面
    public function weixinSq() {
        $type = I('type', 1);
        $this->judgeLiq();
        // 回调地址
        $backurl = U('Label/GetWxUserInfo/callback', 
            array(
                'id' => $this->id, 
                'type' => $type, 
                'laiyuan' => I('laiyuan')), '', '', TRUE);
        $this->wechatAuthorizeAndRedirectByDetailParam($this->appid, 
            $this->secret, 0, $backurl, '', $type);
    }

    /**
     * 回调
     */
    public function callback() {
        $code = I('code', null);
        $type = I('type', 1);
        $laiyuan = I('laiyuan');
        $barr = $this->barr;
        $barr = $barr[$this->batch_type];
        if (empty($code)) {
            $this->error('参数错误！');
        }
        
        $wechatInfo = array(
            'appid' => $this->appid, 
            'secret' => $this->secret);
        $result = $this->WeiXinService->getOpenid($code, $wechatInfo);
        
        $access_token = $result['access_token'];
        $openid = $result['openid'];
        if ($access_token == '' || $openid == '') {
            $this->error('获取授权openid失败！');
        }
        
        $wxuser = M('twx_wap_user')->where(
            array(
                'openid' => $openid))->find();
        if ($wxuser) {
            session($barr, $wxuser['id']);
            redirect(
                U("Label/" . $barr . "/index", 
                    array(
                        'id' => $this->id, 
                        'laiyuan' => $laiyuan)));
            die();
        }
        
        if ($type == '1') {
            redirect(
                U('Label/GetWxUserInfo/weixinSq', 
                    array(
                        'id' => $this->id, 
                        'type' => '2', 
                        'laiyuan' => $laiyuan)));
            die();
        }
        
        // 获取用户信息
        $wxUserInfo = $this->WeiXinService->getUser($access_token, $openid);
        if ($wxUserInfo === false) {
            $this->error('获取用户信息失败！');
        }
        
        // 记录用户信息
        $user_info = $this->inTabel($openid, $access_token, $wxUserInfo);
        if ($user_info) {
            session($barr, $user_info);
            redirect(
                U("Label/" . $barr . "/index", 
                    array(
                        'id' => $this->id, 
                        'laiyuan' => $laiyuan)));
            die();
        } else {
            $this->error('授权登录失败!');
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
            // 'batch_type'=>$this->batch_type,
            'access_token' => $access_token);
        $wxid = M('twx_wap_user')->add($in_arr);
        if (! $wxid) {
            return false;
        }
        return $wxid;
    }
}