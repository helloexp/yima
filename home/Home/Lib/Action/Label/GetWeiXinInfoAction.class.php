<?php

/*
 * 多宝电商微信粉丝专享获取openid 闪购/码上买/小店 在微信里打开的话则去微信隐式认证获取openid
 */
class GetWeiXinInfoAction extends MyBaseAction {

    public $appid = '';

    public $secret = '';

    public $surl = '';
    // 来源地址
    
    /**
     *
     * @var WeiXinService
     */
    public $WeiXinService;

    public function _initialize() {
        $node_id = I('node_id');
        if (! $node_id) {
            $this->error('数据错误');
        }
        if ($node_id == 'onlineExper') {
            $this->appid = C('WEIXIN.appid');
            $this->secret = C('WEIXIN.secret');
        } else {
            $wxInfo = M('tweixin_info')->where(
                array(
                    'node_id' => $node_id))->find();
            $this->appid = $wxInfo['app_id'];
            $this->secret = $wxInfo['app_secret'];
        }
        
        $surl = htmlspecialchars_decode(urldecode(I('surl')));
        $this->surl = $surl;
        $this->node_id = $node_id;
        
        $this->WeiXinService = D('WeiXin', 'Service');
    }
    
    // 入口
    public function index() {
    }

    /**
     * 跳转到最终的页面
     */
    public function goback() {
        $wxBatchOpen = session('wxBatchOpen');
        $openid = $wxBatchOpen['openid'];
        if ($this->node_id == 'onlineExper') {
            redirect($this->surl . '&openid=' . $openid);
        } else {
            redirect($this->surl);
        }
    }

    /**
     * 判断是否需要再次授权(相同node_id不需要多次授权)
     *
     * @return bool
     */
    public function needAuthorize() {
        return true;
        $wxBatchOpen = session('wxBatchOpen');
        if (! isset($wxBatchOpen['nodeId']) ||
             $wxBatchOpen['nodeId'] != $this->node_id) { // 没有授权
            $needAuthorize = true;
        } else { // 已经授权直接跳转
            $needAuthorize = false;
        }
        return $needAuthorize;
    }

    /*
     * 微信认证接口
     */
    public function goAuthorize() {
        if ($this->needAuthorize()) { // 没有授权
            $backurl = U('Label/GetWeiXinInfo/callback', 
                array(
                    'node_id' => $this->node_id, 
                    'surl' => urlencode($this->surl)), '', '', TRUE);
            $this->wechatAuthorizeByNodeId($this->node_id, '0', $this->surl, 
                $backurl);
            
            return;
        } else { // 已经授权直接跳转
            $this->goback();
        }
        
        // //授权地址
        // $open_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
        // //回调地址
        // $backurl = U('Label/GetWeiXinInfo/callback', array('node_id' =>
        // $this->node_id, 'surl' => urlencode($this->surl)), '', '', TRUE);
        // //授权参数
        // $opt_arr = array(
        // 'appid' => $this->appid,
        // 'redirect_uri' => $backurl,
        // 'response_type' => 'code',
        // 'scope' => 'snsapi_base',
        // //'scope'=> 'snsapi_userinfo'
        // );
        // if($this->node_id == 'onlineExper'){
        // $opt_arr['scope'] = 'snsapi_userinfo';
        // }
        // $link = http_build_query($opt_arr);
        // $gourl = $open_url . $link . '#wechat_redirect';
        // //header('location:'.$gourl);
        // redirect($gourl);
    }

    /*
     * 微信认证接口回调
     */
    public function callback() {
        if ($this->needAuthorize()) { // 没有授权
            $code = I('code', null);
            if ($code) {
                $wexinInfo = $this->WeiXinService->getWeixinInfoByNodeId(
                    $this->node_id);
                $result = $this->WeiXinService->getOpenid($code, $wexinInfo);
                $openid = $result['openid'];
                $access_token = $result['access_token'];
                if ($openid == '') {
                    $this->error('获取授权openid失败！');
                }
                /*
                 * 请求userInfo 会出问题 @see http://www.aichengxu.com/view/57220
                 * 微信公共号 40029异常个人解决方案 $wxUserInfo =
                 * $this->WeiXinService->getUser($access_token,$openid);//获取用户信息
                 * if(!$wxUserInfo) { $this->error('获取用户信息失败'); }
                 */
                $wxBatchOpen = array(
                    'openid' => $openid, 
                    'nodeId' => $this->node_id, 
                    'appid' => $wexinInfo['appid']);
                session('wxBatchOpen', $wxBatchOpen);
            }
        }
        
        $this->goback();
        
        /*
         * $code = I('code', null); $wxBatchOpen = session('wxBatchOpen'); if
         * (!$wxBatchOpenId) {//无session if ($code) {//重新获取 //$wxBatchOpen =
         * $this->_getwxUserInfo($code); $result = $this->getOpenid($code);
         * $openid = $result['openid']; if ($openid == '')
         * $this->error('获取授权openid失败！'); session('wxBatchOpen', array('openid'
         * => $openid, 'appid' => $this->appid)); } }
         * //redirect(U('Label/O2OLogin/index',array('id'=>$this->id,'node_id'=>$this->node_id,'surl'=>urlencode($this->surl))));
         * if($this->node_id == 'onlineExper'){
         * redirect($this->surl.'&openid='.$openid); }else{
         * redirect($this->surl); }
         */
    }

    protected function _getwxUserInfo($code) {
        if (empty($code))
            $this->error('参数错误！');
        $result = $this->getOpenid($code);
        $access_token = $result['access_token'];
        $openid = $result['openid'];
        if ($access_token == '' || $openid == '')
            $this->error('获取授权openid失败！');
            // 获取用户信息
        $wxUserInfo = $this->getUser($access_token, $openid);
        if ($wxUserInfo === false) {
            $this->error('获取用户信息失败！');
        }
        // 记录session
        session("wxUserInfo", $wxUserInfo);
        return $wxUserInfo;
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
        }
        return $result;
    }
    
    // 获取用户信息
    protected function getUser($access_token, $openid) {
        if (empty($access_token) || empty($openid)) {
            $this->error('用户数据获取参数不能为空！');
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