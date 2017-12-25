<?php

class WeiXinService {

    protected $_base_req = array();

    protected $token = '';

    public $error = '';

    public $appId;

    public $appSecret;

    public $accessToken;

    public $_logId;

    public $accessTokenUpdated = false;
    // 初始化
    const ZQCUT_TYPE = 55;

    const PAYSEND_TYPE = 54;
    // 福满送
    public function init($appId, $appSecret, $accessToken = '') {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->accessToken = $accessToken;
        $this->_logId = mt_rand(10, 99) . time();
    }

    /* 校验签名 */
    public function checkSignature() {
        $signature = get_val($_GET["signature"]);
        $timestamp = get_val($_GET["timestamp"]);
        $nonce = get_val($_GET["nonce"]);
        $token = $this->getToken();
        $tmpArr = array(
            $token, 
            $timestamp, 
            $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    /* 解析请求内容 */
    public function parseRequest() {
        $postStr = file_get_contents('php://input');
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', 
            LIBXML_NOCDATA);
        log_write("REQUEST IP：" . $_SERVER['REMOTE_ADDR']);
        log_write("GET:" . print_r($_GET, true));
        log_write("INPUT:" . $postStr);
        
        if (! $postObj)
            return false;
            // 消息类型
        $msgType = trim($postObj->MsgType);
        $respArr = array();
        switch ($msgType) {
            case 'text':
                break;
            case 'image':
                $respArr = array(
                    'PicUrl' => trim($postObj->PicUrl) . '', 
                    'MediaId' => trim($postObj->MediaId) . '');
                break;
            case 'location':
                $respArr = array(
                    'Location_X' => trim($postObj->Location_X) . '', 
                    'Location_Y' => trim($postObj->Location_Y) . '', 
                    'Label' => trim($postObj->Label) . '', 
                    'Scale' => trim($postObj->Scale) . '');
                break;
            case 'link':
                break;
            case 'event':
                $respArr = array(
                    'Event' => trim($postObj->Event) . '', 
                    'EventKey' => trim($postObj->EventKey) . '');
                if ($respArr['Event'] == 'MASSSENDJOBFINISH') {
                    $respArr = array_merge(
                        array(
                            'MsgID' => trim($postObj->MsgID), 
                            'Status' => trim($postObj->Status), 
                            'TotalCount' => trim($postObj->TotalCount), 
                            'FilterCount' => trim($postObj->FilterCount), 
                            'SentCount' => trim($postObj->SentCount), 
                            'ErrorCount' => trim($postObj->ErrorCount)), $respArr);
                }
                if ($respArr['Event'] == 'card_pass_check') {
                    $respArr = array_merge(
                        array(
                            'CardId' => trim($postObj->CardId)), $respArr);
                }
                if ($respArr['Event'] == 'card_not_pass_check') {
                    $respArr = array_merge(
                        array(
                            'CardId' => trim($postObj->CardId)), $respArr);
                }
                if ($respArr['Event'] == 'user_get_card') {
                    $respArr = array_merge(
                        array(
                            'CardId' => trim($postObj->CardId), 
                            'UserCardCode' => trim($postObj->UserCardCode), 
                            'FriendUserName' => trim($postObj->FriendUserName), 
                            'IsGiveByFriend' => trim($postObj->IsGiveByFrien), 
                            'OuterId' => @trim($postObj->OuterId)),
                        $respArr);
                }
                if ($respArr['Event'] == 'kf_create_session' || $respArr['Event'] == 'kf_close_session') {
                    $respArr['KfAccount']=$postObj->KfAccount;
                }
               if ($respArr['Event'] == 'kf_switch_session') {
                 $respArr['ToKfAccount']=$postObj->ToKfAccount;
                 $respArr['FromKfAccount']=$postObj->FromKfAccount;
               }
                break;
            case 'voice':
                $respArr = array(
                    'MediaId' => trim($postObj->MediaId) . '');
                break;
            case 'news':
                break;
            default:
                exit();
        }
        $fromUserName = $postObj->FromUserName;
        $toUserName = $postObj->ToUserName;
        $Content = trim($postObj->Content);
        
        $this->_base_req = array(
            'fromUserName' => $fromUserName . '', 
            'toUserName' => $toUserName . '');
        return array_merge(
            array(
                'fromUserName' => $fromUserName . '', 
                'toUserName' => $toUserName . '', 
                'msgType' => $msgType . '', 
                'Content' => $Content . ''), $respArr);
    }

    /* 这儿响应不同处理方式 */
    /* 回复文本 */
    public function respText($resp) {
        $resp = array_merge($this->_base_req, $resp);
        $fromUserName = $resp['fromUserName'];
        $toUserName = $resp['toUserName'];
        $Content = html_entity_decode($resp['Content']);
        log_write('Content:' . $Content);
        
        $tm = time();
        $msgTpl = '<?xml version="1.0" encoding="utf-8"?>';
        $msgTpl .= '<xml>' . '<ToUserName><![CDATA[' . $fromUserName .
             ']]></ToUserName>' . '<FromUserName><![CDATA[' . $toUserName .
             ']]></FromUserName>' . '<CreateTime>' . $tm . '</CreateTime>' .
             '<MsgType><![CDATA[text]]></MsgType>' . '<Content><![CDATA[' .
             $Content . ']]></Content>' . '<FuncFlag>0</FuncFlag>' . '</xml>';
        header('Content-type:text/xml;charset=utf-8');
        log_write('发往微信端的数据:'.$msgTpl,'','TEST');
        log_write('Runtime:' . G('initTime', 'end') . ' s','','TEST');

        log_write('RESPONSE:' . $msgTpl);
        log_write('Runtime:' . G('initTime', 'end') . ' s');
        echo $msgTpl;
        // exit;
    }

    /* 回复图文 */
    public function respNews($resp) {
        $resp = array_merge($this->_base_req, $resp);
        $fromUserName = $resp['fromUserName'];
        $toUserName = $resp['toUserName'];
        $Articles = $resp['Articles'];
        
        $tm = time();
        $msgTpl = '<?xml version="1.0" encoding="utf-8"?>';
        $msgTpl .= '<xml>' . '<ToUserName><![CDATA[' . $fromUserName .
             ']]></ToUserName>' . '<FromUserName><![CDATA[' . $toUserName .
             ']]></FromUserName>' . '<CreateTime>' . $tm . '</CreateTime>' .
             '<MsgType><![CDATA[news]]></MsgType>';
        // 文章数
        if ($Articles) {
            $msgTpl .= '<ArticleCount>' . count($Articles) . '</ArticleCount>';
            $msgTpl .= '<Articles>';
            foreach ($Articles as $v) {
                $msgTpl .= '<item>' . '<Title><![CDATA[' . $v['title'] .
                     ']]></Title>' . '<Description><![CDATA[' . $v['description'] .
                     ']]></Description>' . '<PicUrl>' .
                     ($v['picurl'] ? '<![CDATA[' . $v['picurl'] . ']]>' : '') .
                     '</PicUrl>' . '<Url>' .
                     ($v['url'] ? '<![CDATA[' . $v['url'] . ']]>' : '') .
                     '</Url>' . '</item>';
            }
            $msgTpl .= '</Articles>';
        }
        $msgTpl .= '<FuncFlag>1</FuncFlag>' . '</xml>';
        header('Content-type:text/xml;charset=utf-8');
        
        log_write('RESPONSE:' . $msgTpl);
        log_write('Runtime:' . G('initTime', 'end') . ' s');
        echo $msgTpl;
        // exit;
    }

    /* 回复图片 */
    public function respImage($resp) {
        $resp = array_merge($this->_base_req, $resp);
        $fromUserName = $resp['fromUserName'];
        $toUserName = $resp['toUserName'];
        $mediaId = $resp['mediaId'];
        $tm = time();
        $msgTpl = '<?xml version="1.0" encoding="utf-8"?>';
        $msgTpl .= '<xml>' . '<ToUserName><![CDATA[' . $fromUserName .
             ']]></ToUserName>' . '<FromUserName><![CDATA[' . $toUserName .
             ']]></FromUserName>' . '<CreateTime>' . $tm . '</CreateTime>' .
             '<MsgType><![CDATA[image]]></MsgType>' . '<Image>' .
             '<MediaId><![CDATA[' . $mediaId . ']]></MediaId>' . '</Image>' .
             '</xml>';
        header('Content-type:text/xml;charset=utf-8');
        log_write('RESPONSE:' . $msgTpl);
        log_write('Runtime:' . G('initTime', 'end') . ' s');
        echo $msgTpl;
    }

    /* 回复将消息转发到多客服指令 */
    public function respTransferCustomerService($resp) {
        $resp = array_merge($this->_base_req, $resp);
        $fromUserName = $resp['fromUserName'];
        $toUserName = $resp['toUserName'];
        
        $tm = time();
        $msgTpl = '<?xml version="1.0" encoding="utf-8"?>';
        $msgTpl .= '<xml>' . '<ToUserName><![CDATA[' . $fromUserName .
             ']]></ToUserName>' . '<FromUserName><![CDATA[' . $toUserName .
             ']]></FromUserName>' . '<CreateTime>' . $tm . '</CreateTime>' .
             '<MsgType><![CDATA[transfer_customer_service]]></MsgType>' .
             '</xml>';
        header('Content-type:text/xml;charset=utf-8');
        log_write('RESPONSE:' . $msgTpl);
        log_write('Runtime:' . G('initTime', 'end') . ' s');
        log_write('2222222');
        echo $msgTpl;
        // exit;
    }

    /* 校验处理字串 */
    public function valid() {
        $echoStr = get_val($_GET["echostr"]);
        
        // valid signature , option
        if ($this->checkSignature()) {
            echo $echoStr;
            if ($echoStr)
                exit();
        }
    }

    /* 设置token */
    public function setToken($token) {
        $this->token = $token;
    }

    /* 获取token */
    public function getToken() {
        return $this->token;
    }
    
    // 获取access_token
    public function getAccessToken($app_id, $app_secret) {
        // 判断是否授权模式
        $wx_grant = D('WeiXinGrant', 'Service');
        $wx_grant->init();
        $result = $wx_grant->refresh_weixin_token_by_appid($app_id);
        if ($result !== false) {
            $this->accessToken = $result;
            return $result;
        }
        if (! $app_id || ! $app_secret) {
            return false;
        }
        $reqUrl = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" .
             $app_id . "&secret=" . $app_secret;
        
        $result = $this->httpGet($reqUrl);
        $result = json_decode($result, true);
        if ($result && $result['access_token']) {
            $this->accessToken = $result['access_token'];
            $this->accessTokenUpdated = true; // 标记token已经更新
        }
        log_write((print_r($result, true)), 'result');
        return $result;
    }
    
    // 上传图片(非标用于厦门寿司)
    public function uploadMediaFile($file) {
        $i = 1;
        if (! $this->accessToken) {
            $this->accessToken = $this->getAccessToken($this->appId, 
                $this->appSecret);
        }
        while ($i ++ < 3) {
            $apiUrl = 'http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=' .
                 $this->accessToken . '&type=thumb';
            log_write("apiUrl:" . $apiUrl);
            $result_str = $this->https_request($apiUrl, 
                array(
                    "media" => "@" . $file));
            log_write("result_str" . print_r($result_str, true));
            if (! $result_str) {
                throw_exception('调用接口失败' . $this->error . ' file:' . $file);
            }
            log_write($result_str, 'weixin_groupQuery i=' . $i);
            $result = json_decode($result_str, true);
            // 如果验证码超时，或者失效
            if ($result['errcode'] == '40001' || $result['errcode'] == '42001') {
                $resultToken = $this->getAccessToken($this->appId, 
                    $this->appSecret);
                if (! $resultToken || ! $resultToken['access_token']) {
                    throw_exception(
                        '获取token失败:[' . $resultToken['errcode'] . ']' .
                             $resultToken['errmsg']);
                }
            } else {
                $i = 3;
                break;
            }
        }
        
        $media_id = $result['thumb_media_id'];
        log_write("media_id=" . $media_id);
        if (! $media_id) {
            throw_exception('上传多媒体文件失败！' . $result_str);
        }
        return $media_id;
    }

    /**
     * 微信模块上传图片
     *
     * @param eter $url http://www.xxx.com/1.jpg
     * @return $media_id
     *         5kz53V9h-l7ynzls3ZNep96iRmd4040K2qXhvceVOj3ImixthgcoM_QTyWMuFAGC
     */
    public function uploadMediaFile2($file) {
        /* http图片路径转换为项目图片路径 */
        // $file =
        // "http://test.wangcaio2o.com/Home/Upload/00004488/2015/08/19/55d4352ed96f3.jpg";
        // $file =
        // '/www/wangcai_new/Home/Upload/00004488/2015/08/19/55d4352ed96f3.jpg';
        // //eg:项目路径
        $file = preg_replace('/http.*com/iUs', '/www/wangcai2', $file);
        
        log_write("file:" . $file);
        $i = 1;
        if (! $this->accessToken) {
            $this->accessToken = $this->getAccessToken($this->appId, 
                $this->appSecret);
        }
        while ($i ++ < 3) {
            // $apiUrl =
            // 'https://api.weixin.qq.com/cgi-bin/media/upload?access_token='
            // .$this->accessToken.'&type=TYPE';
            $apiUrl = 'http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=' .
                 $this->accessToken . '&type=image';
            log_write("apiUrl:" . $apiUrl);
            $result_str = $this->https_request($apiUrl, 
                array(
                    "media" => "@" . $file));
            log_write("result_str " . print_r($result_str, true));
            if (! $result_str) {
                throw_exception('调用接口失败' . $this->error . ' file:' . $file);
            }
            log_write($result_str, 'weixin_groupQuery i=' . $i);
            $result = json_decode($result_str, true);
            // 如果验证码超时，或者失效
            if ($result['errcode'] == '40001' || $result['errcode'] == '42001') {
                $resultToken = $this->getAccessToken($this->appId, 
                    $this->appSecret);
                if (! $resultToken || ! $resultToken['access_token']) {
                    throw_exception(
                        '获取token失败:[' . $resultToken['errcode'] . ']' .
                             $resultToken['errmsg']);
                }
            } else {
                $i = 3;
                break;
            }
        }
        
        $media_id = $result['media_id'];
        log_write("media_id=" . $media_id);
        if (! $media_id) {
            throw_exception('上传多媒体文件失败！' . $result_str);
        }
        return $media_id;
    }
    
    // 主动发送消息
    public function sendMsg($respArr) {
        for ($i = 0; $i < 3; $i ++) {
            $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' .
                 $this->accessToken;
            $respData = json_encode($respArr);
            $error = '';
            $result = httpPost($apiUrl, $respData, $error);
            if (! $result) {
                return false;
            }
            $result = json_decode($result, true);
            if ($result['errcode'] == '40001' || $result['errcode'] == '42001') { // 需要更新access_token
                                                                                  // 重新获取
                $this->_log('token超时，重新获取');
                $this->getAccessToken($this->appId, $this->appSecret);
                continue;
            }
            $this->_log($apiUrl . 'data:' . print_r($result, true));
            return $result;
        }
        return false;
    }
    
    // 获取粉丝列表
    public function getFansList($access_token, $next_openid = '') {
        $reqUrl = "https://api.weixin.qq.com/cgi-bin/user/get?access_token=" .
             $access_token . "&next_openid=" . $next_openid;
        
        $result = $this->httpGet($reqUrl);
        $result = json_decode($result, true);
        
        log_write((print_r($result, true)));
        return $result;
    }
    
    // 获取粉丝信息
    public function getFansInfo($open_id, $access_token) {
        $reqUrl = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=" .
             $access_token . "&openid=" . $open_id . "&lang=zh_CN";
        
        $result = $this->httpGet($reqUrl);
        $result = json_decode($result, true);
        log_write('here is fansInfo:'.(print_r($result, true)));
        return $result;
    }
    
    // http get
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
        log_write('请求：' . $url . '参数：' . $data);
        $result = $socket->send($data);
        $error = $socket->error();
        // 记录日志
        if ($error) {
            log_write($error);
        }
        log_write('返回：' . $result);
        return $result;
    }

    public function https_request($url, $data = null) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (! empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        if ($output === false) {
            $this->error = curl_error($curl);
        }
        curl_close($curl);
        return $output;
    }

    public function _log($msg) {
        log_write($msg);
    }

    /**
     * @param string $url
     * @param string $appId
     * @param string $appSecret
     *
     * @return array
     */
    function getWxShareConfig($url = '', $appId = '', $appSecret = '') {
        $url = $url ? $url : "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        empty($appId) ? $appId = C('WEIXIN.appid') : $appId; // 应用ID
        empty($appSecret) ? $appSecret = C('WEIXIN.secret') : $appSecret; // 应用密钥
        import('@.Vendor.JSSDK', '', '.php');
        $jssdk = new JSSDK($appId, $appSecret);
        $signPackage = $jssdk->getSignPackage();
        $config_arr = array(
            'appId' => $appId, 
            'timestamp' => $signPackage['timestamp'], 
            'nonceStr' => $signPackage['nonceStr'], 
            'signature' => $signPackage['signature'], 
            'url' => $signPackage['url']);
        return $config_arr;
    }

    const WX_AUTH_TYPE_NODE = 1;

    const WECHAT_GRANTED = '1';

    const WX_AUTH_TYPE_IMAGECO = 0;

    /**
     *
     * @var WeiXinGrantService
     */
    private $wechatGrantService;

    const __REAL_BACK_URL__ = '__REAL_BACK_URL__';

    public function initWechatGrantService() {
        $this->wechatGrantService = D('WeiXinGrant', 'Service');
        $this->wechatGrantService->init();
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param string $apiCallbackUrl 微信接收到信息之后的回调地址
     * @param array $param
     *
     * @return string
     */
    public function buildWechatAuthorCallbackUrl($apiCallbackUrl, 
        $param = array()) {
        // 回调地址
        if (empty($apiCallbackUrl)) {
            $apiCallbackUrl = U('Label/MyBase/callback', $param, '', '', true);
        }
        return $apiCallbackUrl;
    }

    /**
     * 保存微信授权后最终跳转地址
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param string $finalRedirectUrl 微信授权后最终跳转地址
     */
    public function saveWechatAuthorBackurl($finalRedirectUrl) {
        // 真正要回跳的地址
        if (empty($finalRedirectUrl)) {
            $finalRedirectUrl = I('backurl', '', 'html_entity_decode');
        }
        session(self::__REAL_BACK_URL__, urldecode($finalRedirectUrl)); // 记录session一下将要跳转的真正地址
    }

    /**
     * 获得微信授权后最终跳转地址
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return mixed
     */
    public function getWechatAuthorBackurl() {
        return session(self::__REAL_BACK_URL__);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param int $nodeId 机构号
     * @param int $type 授权时候获取用户信息类型 0:基础信息 1:详细信息
     * @param string $finalRedirectUrl 保存微信授权后最终跳转地址
     * @param string $apiCallbackUrl 微信接收到信息之后的回调地址
     * @return array|bool
     */
    public function wechatAuthorizeAndRedirectByNodeId($nodeId, $type = 0, 
        $finalRedirectUrl, $apiCallbackUrl = '') {
        $this->saveWechatAuthorBackurl($finalRedirectUrl);
        $apiCallbackUrl = $this->buildWechatAuthorCallbackUrl($apiCallbackUrl, 
            array(
                'node_id' => $nodeId, 
                'type' => $type));
        $result = $this->buildWechatAuthorizeParamByNodeId($nodeId, 
            array(
                'type' => $type, 
                'apiCallbackUrl' => $apiCallbackUrl));
        $this->processWechatAuthorize($result);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $result
     * @return bool
     */
    public function processWechatAuthorize($result) {
        if (isset($result['errmsg']) && $result['errmsg'] != '') {
            return $result;
        } else {
            if (isset($result['gourl']) && $result['gourl'] != '') {
                $gourl = $result['gourl'];
            } else {
                $gourl = '';
            }
            redirect($gourl);
            return true;
        }
    }

    /**
     * 微信authorize
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param int $nodeId tbatch_channel id
     * @param array $param
     *
     * @return array
     */
    public function buildWechatAuthorizeParamByNodeId($nodeId, $param = array()) {
        $wechatInfo = $this->getWeixinInfoByNodeId($nodeId);
        log_write(__METHOD__.'$wechatInfo:'.var_export($wechatInfo,1));
        return $this->buildAuthParam($wechatInfo, $param);
    }

    /**
     *
     * @param $id
     * @param int $type
     * @param string $finalRedirectUrl
     * @param string $apiCallbackUrl
     *
     * @return array|bool
     */
    public function wechatAuthorizeAndRedirectById($id, $type = 0, 
        $finalRedirectUrl, $apiCallbackUrl = '') {
        $this->saveWechatAuthorBackurl($finalRedirectUrl);
        $apiCallbackUrl = $this->buildWechatAuthorCallbackUrl($apiCallbackUrl, 
            array(
                'id' => $id, 
                'type' => $type));
        $result = $this->wechatAuthorizeById($id, $type, $apiCallbackUrl);
        $this->processWechatAuthorize($result);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param string $appId appid
     * @param string $appSecret app secret
     * @param int $isComponent 0：手动绑定微信 1：智能绑定微信
     * @param string $apiCallbackUrl 微信auth callback（用户获取openid）
     * @param int $type 0：用户基本信息 1：用户详细信息
     * @return array
     */
    public function wechatAuthorizeAndRedirectByDetailParam($appId, $appSecret, 
        $isComponent, $apiCallbackUrl, $type = 0) {
        $wechatInfo = array(
            'appid' => $appId, 
            'secret' => $appSecret, 
            'auth_flag' => $isComponent);
        $result = $this->buildAuthParam($wechatInfo, 
            array(
                'type' => $type, 
                'apiCallbackUrl' => $apiCallbackUrl));
        $this->processWechatAuthorize($result);
    }

    /**
     * 微信authorize
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param int $id tbatch_channel id
     * @param int $type
     * @param string $realCallbackUrl
     * @param string $apiCallbackUrl
     *
     * @return array
     */
    public function wechatAuthorize($id, $type = 0, $realCallbackUrl = '', 
        $apiCallbackUrl = '', $isImagecoAuth = '') {
        // 真正要回跳的地址
        if (empty($realCallbackUrl)) {
            $realCallbackUrl = I('backurl', '', 'html_entity_decode');
        }
        session(self::__REAL_BACK_URL__, urldecode($realCallbackUrl)); // 记录session一下将要跳转的真正地址
                                                                       // 授权地址
                                                                       // 回调地址
        if (empty($apiCallbackUrl)) {
            $apiCallbackUrl = U('Label/MyBase/callback', 
                array(
                    'id' => $id, 
                    'type' => $type), '', '', true);
        }
        
        $marketInfo = $this->getMarketInfo($id);
        $wechatInfo = $this->getWeixinInfo($marketInfo);
        
        return $this->buildAuthParam($wechatInfo, 
            array(
                'type' => $type, 
                'apiCallbackUrl' => $apiCallbackUrl));
    }

    /**
     * 微信authorize
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param int $id tbatch_channel id
     * @param int $type
     * @param string $realCallbackUrl
     * @param string $apiCallbackUrl
     *
     * @return array
     */
    public function wxAuthorizeByMarketInfoId($id, $type = 0, 
        $realCallbackUrl = '', $apiCallbackUrl = '') {
        // 真正要回跳的地址
        if (empty($realCallbackUrl)) {
            $realCallbackUrl = I('backurl', '', 'html_entity_decode');
        }
        session(self::__REAL_BACK_URL__, urldecode($realCallbackUrl)); // 记录session一下将要跳转的真正地址
                                                                       // 授权地址
                                                                       // 回调地址
        if (empty($apiCallbackUrl)) {
            $apiCallbackUrl = U('Label/MyBase/callback', 
                array(
                    'id' => $id, 
                    'type' => $type), '', '', true);
        }
        
        $marketInfo = $this->getMarketInfo($id);
        $wechatInfo = $this->getWeixinInfo($marketInfo);
        
        return $this->buildAuthParam($wechatInfo, 
            array(
                'type' => $type, 
                'apiCallbackUrl' => $apiCallbackUrl));
    }

    /**
     * 微信authorize
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param int $id tbatch_channel id
     * @param int $type
     * @param string $realCallbackUrl
     * @param string $apiCallbackUrl
     *
     * @return array
     */
    public function wechatAuthorizeById($id, $type = 0, $apiCallbackUrl = '') {
        $marketInfo = $this->getMarketInfo($id);
        $wechatInfo = $this->getWeixinInfo($marketInfo);
        
        return $this->buildAuthParam($wechatInfo, 
            array(
                'type' => $type, 
                'apiCallbackUrl' => $apiCallbackUrl));
    }

    /**
     *
     * @param $wechatInfo
     * @param $param
     * @return array
     */
    public function buildAuthParam($wechatInfo, $param) {
        $type = isset($param['type']) ? $param['type'] : 0;
        $apiCallbackUrl = isset($param['apiCallbackUrl']) ? $param['apiCallbackUrl'] : 0;
        $oauthUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
        if (! isset($wechatInfo['errmsg'])) { // 授权获取成功，尝试获取openid
            $sopce = $type == '0' ? 'snsapi_base' : 'snsapi_userinfo';
            $optList = array(
                'appid' => $wechatInfo['appid'], 
                'redirect_uri' => $apiCallbackUrl, 
                'response_type' => 'code', 
                'scope' => $sopce);
            if ($wechatInfo['auth_flag'] == self::WECHAT_GRANTED) { // 智能授权
                                                                    // 需要添加component_appid
                $this->initWechatGrantService();
                $optList['component_appid'] = $this->wechatGrantService->component_appid;
            }
            
            $link = http_build_query($optList);
            $gourl = $oauthUrl . $link . '#wechat_redirect';
            $errmsg = '';
        } else { // 微信信息获取失败
            $gourl = '';
            $errmsg = '微信信息获取失败';
        }
        
        return array(
            'gourl' => $gourl, 
            'errmsg' => $errmsg);
    }

    /**
     * 获取marketinfo相关数据
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param int $id tbatch_channel id
     * @return array
     */
    public function getMarketInfo($id) {
        $model = M('tbatch_channel');
        $map = array(
            'id' => $id);
        $batchChannelInfo = $model->where($map)->find();
        
        if (! $batchChannelInfo) {
            return array();
        }
        
        // 查询活动
        $map = array(
            'id' => $batchChannelInfo['batch_id'], 
            'batch_type' => $batchChannelInfo['batch_type']);
        
        return M('tmarketing_info')->where($map)->find();
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $nodeId
     * @return array
     */
    public function getWeixinInfoByNodeId($nodeId) {
        if (is_numeric($nodeId)) {
            $map = array(
                'node_id' => $nodeId);
            $wechatData = M('tweixin_info')->where($map)->find();
            $verifyResult = $this->verifyWechatInfo($wechatData);
            if (isset($verifyResult['errno']) && $verifyResult['errno'] < 0) { // 出现错误了
                return $verifyResult;
            } else {
                return array(
                    'appid' => $wechatData['app_id'], 
                    'secret' => $wechatData['app_secret'], 
                    'auth_flag' => $wechatData['auth_flag'], 
                    'wechatInfo' => $wechatData);
            }
        } else {
            return $this->getWeixinInfoByImagecoAuth();
        }
    }

    public function verifyWechatInfo($wechatData) {
        if (! $wechatData) {
            return array(
                'errmsg' => '未绑定微信公众账号', 
                'errno' => - 1);
        }
        if ($wechatData['auth_flag'] == 0 &&
             (empty($wechatData['app_id']) || empty($wechatData['app_secret']))) {
            return array(
                'errmsg' => '未绑定微信公众账号api', 
                'errno' => - 2);
        }
        
        return array(
            'errmsg' => '', 
            'errno' => 0);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $marketInfo
     * @return array
     */
    public function getWeixinInfoByMarketingInfo($marketInfo) {
        $isImagecoAuth = $this->isImagecoAuthType($marketInfo);
        
        if ($isImagecoAuth) { // 翼码授权
            $wechatInfo = $this->getWeixinInfoByImagecoAuth();
        } else { // 机构自己的微信号
            $wechatInfo = $this->getWeixinInfoByNodeId($marketInfo['node_id']);
        }
        return $wechatInfo;
    }

    /**
     * 翼码授权
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return array
     */
    public function getWeixinInfoByImagecoAuth() {
        return array(
            'appid' => C('WEIXIN.appid'),  // 应用ID
            'secret' => C('WEIXIN.secret')); // 应用密钥
    
    }

    /**
     * 微信相关信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param array $marketInfo
     *
     * @param null $isImagecoAuth
     *
     * @return array
     */
    public function getWeixinInfo($marketInfo = array(), $isImagecoAuth = null) {
        if (is_null($isImagecoAuth)) {
            $isImagecoAuth = $this->isImagecoAuthType($marketInfo);
        }
        
        if ($isImagecoAuth) { // 翼码授权
            $wechatInfo = array(
                'appid' => C('WEIXIN.appid'),  // 应用ID
                'secret' => C('WEIXIN.secret')); // 应用密钥
        
        } else { // 机构自己的微信号
            $wechatInfo = $this->getWeixinInfoByNodeId($marketInfo['node_id']);
        }
        return $wechatInfo;
    }

    /**
     * 微信授权是否为翼码
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param array $marketingInfo
     *
     * @return bool
     */
    public function isImagecoAuthType($marketingInfo = array()) {
        $isImagecoAuthType = false;
        
        $configData = array();
        if (isset($marketingInfo['config_data']) &&
             is_string($marketingInfo['config_data'])) {
            $configData = unserialize($marketingInfo['config_data']);
        }
        
        if (empty($marketingInfo)) { // 和marketing 因为没有关系，只能使用翼码授权
            $isImagecoAuthType = true;
        } else if ($marketingInfo['batch_type'] == self::ZQCUT_TYPE &&
             $marketingInfo['defined_five_name'] != '1') {
             //$marketingInfo['batch_type'] == self::PAYSEND_TYPE || // 中秋砍树
                                                                               // ，付满送，特殊处理
            $isImagecoAuthType = true;
        } else if (isset($configData['wx_auth_type']) &&
             $configData['wx_auth_type'] == self::WX_AUTH_TYPE_IMAGECO) {
            $isImagecoAuthType = true;
        }
        return $isImagecoAuthType;
    }

    /**
     *
     * @param $nodeId
     * @return mixed
     */
    public function getWechatUserInfo($nodeId) {
        return session('node_wxid_' . $nodeId);
    }

    /**
     *
     * @param $wechatUserInfo
     * @param $node_id
     */
    public function setWechatSession($wechatUserInfo, $node_id) {
        session('node_wxid_' . $node_id, $wechatUserInfo);
    }

    /**
     * 获取用户信息
     *
     * @param $access_token
     * @param $openid
     * @return bool|mixed
     */
    public function getUser($access_token, $openid) {
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

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $msg
     * @param string $dst
     */
    public function fileLog($msg, $dst = '') {
        if (empty($dst)) {
            if (defined('IS_WIN') && IS_WIN) {
                $dst = 'd:/openid.log';
            } else {
                $dst = '/tmp/openid.log';
            }
        }
        
        error_log(date('YmdHis') . '|' . $msg . PHP_EOL, 3, $dst);
    }

    /**
     *
     * @param string $appId appid
     * @param string $appSecret app secret 智能授权 设置为null
     * @param int $isComponent 0:手动授权 1：智能授权
     * @param string $code code ()
     * @param string $nodeId
     * @param int $type
     *
     * @return array|bool
     */
    public function callbackAndRedirectByDetailParam($appId, $appSecret, 
        $isComponent, $code, $nodeId, $type = 0) {
        $backurl = I('backurl', session(self::__REAL_BACK_URL__));
        $callbackResult = array(
            'appid' => $appId, 
            'secret' => $appSecret, 
            'auth_flag' => $isComponent, 
            'code' => $code, 
            'type' => $type, 
            'backurl' => $backurl);
        return $this->processWxSessionAndRedirect($callbackResult, $nodeId, 
            $type);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $code
     * @param $wechatInfo
     * @return array
     */
    public function weixinCallbackByParam($code, $wechatInfo) {
        // 获取用户信息，获取完 code失效
        $errmsg = '';
        $openid = '';
        $access_token = '';
        log_write(__METHOD__.'$wechatInfo:'.var_export($wechatInfo,1));
        for ($i = 0; $i <= 3; $i ++) {
            $result = $this->getOpenid($code, $wechatInfo);
            $access_token = $result['access_token'];
            $openid = $result['openid'];
            if ($access_token && $openid) {
                $errmsg = '';
                break;
            }
            sleep(1);
            $errmsg = '获取授权openid失败！';
            log_write($errmsg . ',重试第' . $i . '次');
        }
        if ($errmsg) {
            return array(
                'errmsg' => $errmsg);
        }
        $backurl = I('backurl', session(self::__REAL_BACK_URL__));
        return array(
            'openid' => $openid, 
            'access_token' => $access_token, 
            'backurl' => $backurl, 
            'errmsg' => '');
    }

    public function processWxSessionAndRedirect($callbackResult, $nodeId, 
        $type = 0) {
        if (isset($callbackResult['errmsg']) && $callbackResult['errmsg']) { // 获取微信openid报错
            return $callbackResult;
        }
        
        $accessToken = isset($callbackResult['access_token']) ? $callbackResult['access_token'] : '';
        $openid = isset($callbackResult['openid']) ? $callbackResult['openid'] : '';
        $backurl = isset($callbackResult['backurl']) ? $callbackResult['backurl'] : '';
        $wxSessInfo = array(
            'access_token' => $accessToken, 
            'openid' => $openid);
        $this->setWechatSession($wxSessInfo, $nodeId);
        if ($type == '0') {
            redirect($backurl);
            return true;
        }
        // 如果是获取详情
        $wechatUserInfo = array(); // 用户详细信息
        if ($type == '1') {
            if (empty($accessToken) || empty($openid)) {
                return array(
                    'errmsg' => '参数不能为空!');
            }
            // 获取用户信息
            $wechatUserInfo = $this->getUser($accessToken, $openid);
            if ($wechatUserInfo === false) {
                return array(
                    'errmsg' => '获取用户信息失败！');
            }
            $wechatUserInfo = array_merge($wxSessInfo, $wechatUserInfo);
        }
        if ($wechatUserInfo && ! empty($wechatUserInfo['openid'])) {
            $this->setWechatSession($wechatUserInfo, $nodeId);
            log_write('redirect to:' . $backurl);
            redirect($backurl);
            return true;
        } else {
            $this->setWechatSession(null, $nodeId);
            return array(
                'errmsg' => '授权登录失败!');
        }
    }

    /**
     *
     * @param $id
     * @param $code
     * @param $nodeId
     * @param string $type
     *
     * @return array|bool
     */
    public function callbackAndRedirectById($id, $code, $nodeId, $type = '0') {
        $callbackResult = $this->weixinCallback($id, $code); // 微信授权callback --
                                                             // get openid
        return $this->processWxSessionAndRedirect($callbackResult, $nodeId, 
            $type);
    }
    
    /**
     * 
     * @param unknown $code
     * @param unknown $nodeId
     * @param string $type
     * @return boolean|unknown|string[]
     */
    public function callbackAndRedirectByNodeId($code,$nodeId, $type = '0') {
        $callbackResult = $this->wechatCallbackByNodeId($nodeId, $code); // 微信授权callback --
        // get openid
        return $this->processWxSessionAndRedirect($callbackResult, $nodeId, $type);
    }
    
    
    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $id
     * @param $code
     * @return array
     */
    public function wechatCallbackByNodeId($nodeId, $code) {
        // 得到响就码
        $wechatInfo = $this->getWeixinInfoByNodeId($nodeId);
    
        // 获取用户信息，获取完 code失效
        $errmsg = '';
        $openid = '';
        $access_token = '';
        log_write(__METHOD__.'$wechatInfo:'.var_export($wechatInfo,1));
        for ($i = 0; $i <= 3; $i ++) {
            $result = $this->getOpenid($code, $wechatInfo);
            $access_token = $result['access_token'];
            $openid = $result['openid'];
            if ($access_token && $openid) {
                $errmsg = '';
                break;
            }
            sleep(1);
            $errmsg = '获取授权openid失败！';
            log_write($errmsg . ',重试第' . $i . '次');
        }
        if ($errmsg) {
            return array(
                'errmsg' => $errmsg);
        }
        $backurl = I('backurl', session(self::__REAL_BACK_URL__));
        return array(
            'openid' => $openid,
            'access_token' => $access_token,
            'backurl' => $backurl,
            'errmsg' => '');
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $id
     * @param $code
     * @return array
     */
    public function weixinCallback($id, $code) {
        // 得到响就码
        $marketInfo = $this->getMarketInfo($id);
        $wechatInfo = $this->getWeixinInfo($marketInfo);
        
        // 获取用户信息，获取完 code失效
        $errmsg = '';
        $openid = '';
        $access_token = '';
        log_write(__METHOD__.'$wechatInfo:'.var_export($wechatInfo,1));
        for ($i = 0; $i <= 3; $i ++) {
            $result = $this->getOpenid($code, $wechatInfo);
            $access_token = $result['access_token'];
            $openid = $result['openid'];
            if ($access_token && $openid) {
                $errmsg = '';
                break;
            }
            sleep(1);
            $errmsg = '获取授权openid失败！';
            log_write($errmsg . ',重试第' . $i . '次');
        }
        if ($errmsg) {
            return array(
                'errmsg' => $errmsg);
        }
        $backurl = I('backurl', session(self::__REAL_BACK_URL__));
        return array(
            'openid' => $openid, 
            'access_token' => $access_token, 
            'backurl' => $backurl, 
            'errmsg' => '');
    }

    /**
     * 获取openid
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $code
     * @param $wechatInfo
     * @return bool|mixed
     */
    public function getOpenid($code, $wechatInfo = array()) {
        if (empty($code)) {
            return false;
        }
        
        if (empty($wechatInfo)) {
            $wechatInfo = $this->getWeixinInfo();
        }
        
        $oldApiUrl = $apiUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' .
             $wechatInfo['appid'] . '&secret=' . $wechatInfo['secret'] . '&code=' .
             $code . '&grant_type=authorization_code';
        if (isset($wechatInfo['auth_flag']) &&
             $wechatInfo['auth_flag'] == self::WECHAT_GRANTED) { // 智能授权
            $this->initWechatGrantService();
            $this->wechatGrantService->api_component_token();
            $apiUrl = 'https://api.weixin.qq.com/sns/oauth2/component/access_token?appid=' .
                 $wechatInfo['appid'] . '&code=' . $code .
                 '&grant_type=authorization_code&component_appid=' .
                 $this->wechatGrantService->component_appid .
                 '&component_access_token=' .
                 $this->wechatGrantService->component_access_token;
        }
        
        $error = '';
        $result = httpPost($apiUrl, '', $error, 
            array(
                'METHOD' => 'GET'));
        
        $result = $this->formatResult($result);
        if ($wechatInfo['auth_flag'] == self::WECHAT_GRANTED &&
             isset($result['errcode']) && $result['errcode'] != '') { // 智能绑定的方式获取token失败
                                                                     // 使用原有的方式进行请求
            $apiUrl = $oldApiUrl;
            $result = httpPost($apiUrl, '', $error, 
                array(
                    'METHOD' => 'GET'));
            $result = $this->formatResult($result);
            if (isset($result['errcode']) && $result['errcode'] != '') {
                return false;
            }
        }
        
        if (isset($result['errcode']) && $result['errcode'] != '') {
            return false;
        }
        
        return $result;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $result string json格式
     * @return array
     */
    private function formatResult($result) {
        if (empty($result)) {
            $result = array(
                'errcode' => 504);
        } else {
            $result = json_decode($result, true);
            if ($result === null) { // json解析出错
                $result = array(
                    'errcode' => 505);
            }
        }
        
        return $result;
    }

    /**
     * 获取微信端的媒体文件(视频,音频,图片)
     *
     * @param string $accessToken
     * @param string $mediaId
     * @return array
     */
    public function downloadWeixinFile($accessToken, $mediaId) {
        $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=" .
             $accessToken . "&media_id=" . $mediaId;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0); // 只取body头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $package = curl_exec($ch);
        $httpinfo = curl_getinfo($ch);
        curl_close($ch);
        $imageAll = array_merge(array(
            'header' => $httpinfo), array(
            'body' => $package));
        return $imageAll;
    }

    /**
     * 写入文件
     *
     * @param string $filename 文件地址
     * @param string $filecontent 文件内容
     */
    public function saveWeixinFile($filename, $filecontent) {
        $local_file = fopen($filename, 'w');
        if (false !== $local_file) {
            if (false !== fwrite($local_file, $filecontent)) {
                fclose($local_file);
            }
        }
    }
}