<?php

// 微信粉丝分组接口
class WeiXinBaseService
{

    public $appId;

    public $appSecret;

    public $accessToken;

    public $weixinInfo;

    public $error = '';

    private $initStatus = null;

    /**
     * @var WeiXinGrantService
     */
    private $WeiXinGrantService;

    /**
     * 初始化
     *
     * @param $node_id
     *
     * @return bool
     */
    public function init($node_id)
    {
        $wx_node_info = M('tweixin_info')->where(['node_id' => $node_id])->find();
        if ($wx_node_info) {
            $this->appId       = $wx_node_info['app_id'];
            $this->appSecret   = $wx_node_info['app_secret'];
            $this->accessToken = $wx_node_info['app_access_token'];
            $this->weixinInfo  = $wx_node_info;
            $this->initStatus  = true;
            return true;
        } else {
            $this->initStatus = false;
            return false;
        }
    }

    /**
     * @param $accessToken
     *
     * @return bool
     */
    protected function setToken($accessToken)
    {
        $this->accessToken                = $accessToken;
        $wx_node_info['app_access_token'] = $accessToken;
        $rs                               = M('tweixin_info')->where(['app_id' => $this->appId])->save($wx_node_info);
        if (!$rs) {
            log_write('save tweixin_info setToken ' . M()->_sql(), 'error');
        }

        return $rs;
    }

    // 获取token
    protected function getToken()
    {
        // 判断是否授权模式
        if (empty($this->WeiXinGrantService)) {
            $this->WeiXinGrantService = D('WeiXinGrant', 'Service');
        }
        $this->WeiXinGrantService->init();
        $result = $this->WeiXinGrantService->refresh_weixin_token_by_appid($this->appId);
        if ($result !== false) {
            $this->setToken($result);
            return ['errcode' => 0, 'access_token' => $result];
        }

        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $this->appId . '&secret=' . $this->appSecret . '';
        $error  = '';
        for ($i = 0; $i < 10; $i++) {
            $result = httpPost($apiUrl, '', $error, ['TIMEOUT' => 30]);
            if ($result) {
                break;
            } else {
                log_write($error, 'error');
            }
            usleep(500 * 1000);
        }
        $result      = json_decode($result, true);
        $accessToken = $result['access_token'];
        $this->setToken($accessToken);
        return $result;
    }

    /**
     * 发送报文
     *
     * @param $api_uri
     * @param $data_arr
     *
     * @return mixed
     */
    protected function send($api_uri, $data_arr)
    {
        $accessToken = $this->accessToken;
        if (strpos($api_uri, '?') === false) {
            $api_url = $api_uri . '?access_token=' . $accessToken;
        } else {
            $api_url = $api_uri . '&access_token=' . $accessToken;
        }
        $post_data = json_encode($data_arr);
        $post_data = $this->unicodeDecode($post_data);
        log_write($api_url);
        log_write($post_data);

        // 判断网络
        $error      = '';
        $result_str = '';
        for ($i = 0; $i < 10; $i++) {
            $result_str = httpPost($api_url, $post_data, $error, ['TIMEOUT' => 30]);
            if ($result_str) {
                break;
            } else {
                log_write($error, 'error');
            }
            usleep(500 * 1000);
        }

        log_write($result_str, 'result_str');
        $result = json_decode($result_str, true);

        if (!empty($result['errcode']) && ($result['errcode'] == '40001' || $result['errcode'] == '42001' || $result['errcode'] == '41001')) {
            $this->getToken();
            if (strpos($api_uri, '?') === false) {
                $api_url = $api_uri . '?access_token=' . $this->accessToken;
            } else {
                $api_url = $api_uri . '&access_token=' . $this->accessToken;
            }
            // 判断网络
            $error = '';
            for ($i = 0; $i < 3; $i++) {
                $result_str = httpPost($api_url, $post_data, $error, ['TIMEOUT' => 30]);
                if ($result_str) {
                    break;
                } else {
                    log_write($error);
                }
                usleep(500 * 1000);
            }

            log_write($result_str);
            $result      = json_decode($result_str, true);
            $this->error = "[" . $result['errcode'] . "]" . $result['errmsg'];
        }
        return $result;
    }

    /**
     * unicode字符转可见
     *
     * @param $name
     *
     * @return mixed
     */
    public function unicodeDecode($name)
    {
        return preg_replace_callback(
                '/\\\\u([0-9a-f]{4})/i',
                create_function(
                        '$matches',
                        'return mb_convert_encoding(pack("H*", $matches[1]), "utf-8", "UCS-2BE");'
                ),
                $name
        );
    }

    //获取签名
    protected function sign($data) {
        foreach ($data as $k => $v) {
            if ($v == null) {
                unset($data[$k]);
            }
        }
        ksort($data, SORT_STRING);
        $signSrc = urldecode(http_build_query($data));
        $signSrc = $signSrc . '&key=' . $this->appKey;
        $sign = strtoupper(md5($signSrc));
        return $sign;
    }
    /**
     * 获取红包接口中nonce_str字段
     */
    public function getNonceStr() {
        $str = '1234567890abcdefghijklmnopqrstuvwxyz';
        $t1 = '';
        for ($i = 0; $i < 30; $i ++) {
            $j = rand(0, 35);
            $t1 .= $str[$j];
        }
        return $t1;
    }

}