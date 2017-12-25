<?php
// 微信卡券投放
class WeixinCardAction extends MyBaseAction {

    public $appid;

    public $secret;

    public $accessToken;

    public $ymAppid;

    public $ymSecret;

    public function _initialize() {
        parent::_initialize();
        // 判断是否是微信过来的
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            $this->error('请使用微信浏览器');
        }
        preg_match('/.*?(MicroMessenger\/([0-9.]+))\s*/', 
            $_SERVER['HTTP_USER_AGENT'], $matches);
        $baseNum = substr($matches[2], 0, 1);
        if ($baseNum < 6) {
            $this->error('请使用6.0版本以上微信浏览器');
        }
        $wxInfo = M()->table("tbatch_channel b")->field(
            'i.app_id,i.app_secret,i.app_access_token')
            ->join("tweixin_info i ON b.node_id=i.node_id")
            ->where("b.id='{$this->id}'")
            ->find();
        if (empty($wxInfo['app_id']) && empty($wxInfo['app_secret'])) {
            $this->error('商户微信配置信息有误');
            log_write(
                'weixinCard-error:app_id:' . $wxInfo['app_id'] . 'app_secret' .
                     $wxInfo['app_secret']);
            exit();
        }
        $this->appid = $wxInfo['app_id'];
        $this->secret = $wxInfo['app_secret'];
        $this->accessToken = $wxInfo['app_access_token'];
        /*
         * $this->ymAppid = 'wx6a9bb22c22247085'; $this->ymSecret =
         * '9865fb234e80366560bf069374e88eb4';
         */
        
        $this->ymAppid = C('WEIXIN.appid');
        $this->ymSecret = C('WEIXIN.secret');
        // 微信授权
        if (ACTION_NAME != 'callback' && ! session('?wxCardId')) {
            $this->goAuthorize();
        }
        if (ACTION_NAME == 'phoneIndex' && session('?wxCardId')) { // 将phoneIndex
                                                                   // 强制跳转到
                                                                   // index
            redirect(U('Home/Label/WeixinCard/index', $_REQUEST));
        }
    }
    
    // 授权页面
    public function index() {
        $openId = session('wxCardId');
        if (empty($openId))
            $this->error('参数错误');
        $batchInfo = M()->table("tmarketing_info b")->field(
            'b.name,b.status,b.start_time,b.end_time,b.wap_info,b.button_text,i.card_id')
            ->join("tbatch_info i ON b.id=i.m_id")
            ->where("b.id='{$this->batch_id}'")
            ->find();
        if (! $batchInfo)
            $this->error('未找到相关活动信息');
        if ($batchInfo['status'] == '2')
            $this->error('该活动已取消');
        if (date('YmdHis') < $batchInfo['start_time'] ||
             date('YmdHis') > $batchInfo['end_time'])
            $this->error('活动不在有效期之内');
            // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        $this->assign('batchInfo', $batchInfo);
        $this->assign('id', $this->id);
        $this->assign('uid', $openId);
        $this->display();
    }
    // 微信授权
    public function goAuthorize() {
        // 授权地址
        $open_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
        // 回调地址
        $backurl = U('Label/WeixinCard/callback', 
            array(
                'id' => $this->id), '', '', TRUE);
        // 授权参数
        $opt_arr = array(
            'appid' => $this->ymAppid, 
            'redirect_uri' => $backurl, 
            'response_type' => 'code', 
            'scope' => 'snsapi_base');
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
        session('wxCardId', $openid);
        redirect(
            U('Label/WeixinCard/index', 
                array(
                    'id' => $this->id, 
                    'wechat_card_js' => '1')));
        exit();
    }
    
    // 获取openid
    protected function getOpenid($code) {
        if (empty($code))
            return false;
        
        $apiUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' .
             $this->ymAppid . '&secret=' . $this->ymSecret . '&code=' . $code .
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

    public function getCardtext() {
        $openId = session('wxCardId');
        if (empty($openId))
            $this->error('参数错误');
        $batchId = M('tbatch_info')->where("m_id='{$this->batch_id}'")->getField(
            'id');
        if (! $batchId)
            $this->error('未找到活动信息');
        
        $service = D('WeiXinCard', 'Service');
        $service->init($this->appid, $this->secret, $this->accessToken);
        $result = $service->add_assist_number($openId, $batchId);
        if ($result) {
            $this->ajaxReturn($result, '', 1);
        } else {
            $this->error($service->error);
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