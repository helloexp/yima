<?php
// 微信菜单接口
class WeiXinSendService {

    public $nodeId;

    public $appId;

    public $appSecret;

    public $accessToken;

    public $error = '';

    public $req_arr;
    // 需要重新获取token的返回码
    public $refresh_token_code = array(
        '40001', 
        '40014', 
        '41001', 
        '42001');
    
    // 初始化
    public function init($node_id) {
        $wxInfo = M('tweixin_info')->where("node_id='" . $node_id . "'")
            ->field("app_id,app_secret,app_access_token")
            ->find();
        if (! $wxInfo) {
            throw_exception('商户未配置微信业务，请选设置公众账号');
        }
        if (! $wxInfo['app_id']) {
            throw_exception('AUTH_SET');
        }
        
        $this->nodeId = $node_id;
        $this->appId = $wxInfo['app_id'];
        $this->appSecret = $wxInfo['app_secret'];
        $this->accessToken = $wxInfo['app_access_token'];
        
        if (! $wxInfo['app_access_token']) {
            $resultToken = $this->getToken();
            if (! $resultToken || ! $resultToken['access_token']) {
                throw_exception(
                    "获取token失败:[" . $resultToken['errcode'] . ']' .
                         $resultToken['errmsg']);
            }
        }
    }

    public function set_error($error) {
        $this->error = $error;
        return false;
    }

    public function error() {
        return $this->error;
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
        $error = '';
        $result = httpPost($apiUrl, '', $error, 
            array(
                'TIMEOUT' => 30, 
                'METHOD' => 'GET'));
        $result = json_decode($result, true);
        $accessToken = $result['access_token'];
        $this->accessToken = $accessToken;
        
        M('tweixin_info')->where("node_id='{$this->nodeId}'")->save(
            array(
                'app_access_token' => $accessToken));
        return $result;
    }

    /**
     *
     * @param $type 0-文本 1-图片 2-图文 3-语音 4-卡券
     * @param $touser 普通用户openid
     * @param $content 当type=0，content是文本内容，
     *            当type=2，content是文本内容，当type=3，content是素材id，数组，
     * @return mixed
     */
    public function sendCustomMsg($type, $touser, $content) {
        switch ($type) {
            case 0:
                $data = array(
                    'touser' => $touser, 
                    'msgtype' => 'text', 
                    'text' => array(
                        'content' => $content));
                break;
            case 2:
                $data = array(
                    'touser' => $touser, 
                    'msgtype' => 'news', 
                    'news' => array(
                        'articles' => array()));
                
                $list = M('twx_material')->where(
                    "id= '{$content}' or parent_id = '{$content}'")->select();
                if (! $list) {
                    throw_exception("未找到图文素材");
                }
                
                foreach ($list as $info) {
                    $data['news']['articles'][] = array(
                        "title" => $info['material_title'], 
                        "description" => $info['material_summary'], 
                        "url" => $info['material_link'], 
                        "picurl" => get_upload_url($info['material_img']));
                }
                BREAK;
            case 4:
                $data = array(
                    'touser' => $touser, 
                    'msgtype' => 'wxcard', 
                    'wxcard' => $content);
                break;
            default:
                throw_exception("回复类型错误！");
        }
        
        $this->req_arr = $data;
        $data = $this->unicodeDecode(json_encode($data));
        
        $i = 1;
        while ($i ++ < 3) {
            $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' .
                 $this->accessToken;
            log_write($apiUrl, 'weixin_sendText');
            log_write($data, 'weixin_sendText');
            $error = '';
            $result_str = httpPost($apiUrl, $data, $error, 
                array(
                    'TIMEOUT' => 30, 
                    'METHOD' => 'GET'));
            log_write($result_str, 'weixin_sendText');
            $result = json_decode($result_str, true);
            
            // 如果验证码超时，或者失效
            if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                $resultToken = $this->getToken();
                if (! $resultToken || ! $resultToken['access_token']) {
                    throw_exception(
                        '获取token失败:[' . $resultToken['errcode'] . ']' .
                             $resultToken['errmsg']);
                }
            } else {
                $i = 3;
            }
        }
        
        return $result;
    }

    public function query_group() {
        $i = 1;
        while ($i ++ < 3) {
            $apiUrl = 'https://api.weixin.qq.com/cgi-bin/groups/get?access_token=' .
                 $this->accessToken;
            log_write($apiUrl, 'weixin_groupQuery');
            $error = '';
            $result_str = httpPost($apiUrl, null, $error, 
                array(
                    'TIMEOUT' => 30, 
                    'METHOD' => 'GET'));
            log_write($result_str, 'weixin_groupQuery');
            $result = json_decode($result_str, true);
            
            // 如果验证码超时，或者失效
            if ($result['errcode'] == '40001' || $result['errcode'] == '42001') {
                $resultToken = $this->getToken();
                if (! $resultToken || ! $resultToken['access_token']) {
                    throw_exception(
                        '获取token失败:[' . $resultToken['errcode'] . ']' .
                             $resultToken['errmsg']);
                }
            } else {
                $i = 3;
            }
        }
        
        return $result;
    }

    public function query_userlist($next_openid) {
        $i = 1;
        while ($i ++ < 3) {
            $apiUrl = 'https://api.weixin.qq.com/cgi-bin/user/get?access_token=' .
                 $this->accessToken . '&next_openid=' . $next_openid;
            log_write($apiUrl, 'weixin_userListQuery');
            $error = '';
            $result_str = httpPost($apiUrl, null, $error, 
                array(
                    'TIMEOUT' => 30, 
                    'METHOD' => 'GET'));
            log_write($result_str, 'weixin_userListQuery');
            $result = json_decode($result_str, true);
            
            // 如果验证码超时，或者失效
            if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                $resultToken = $this->getToken();
                if (! $resultToken || ! $resultToken['access_token']) {
                    throw_exception(
                        '获取token失败:[' . $resultToken['errcode'] . ']' .
                             $resultToken['errmsg']);
                }
            } else {
                $i = 3;
            }
        }
        
        return $result;
    }

    public function query_usergroup($openid) {
        $i = 1;
        while ($i ++ < 3) {
            $apiUrl = 'https://api.weixin.qq.com/cgi-bin/groups/getid?access_token=' .
                 $this->accessToken;
            log_write($apiUrl, 'weixin_groupQuery');
            $error = '';
            $result_str = httpPost($apiUrl, 
                json_encode(
                    array(
                        'openid' => $openid)), $error, 
                array(
                    'TIMEOUT' => 30, 
                    'METHOD' => 'POST'));
            log_write($result_str, 'weixin_groupQuery');
            $result = json_decode($result_str, true);
            
            // 如果验证码超时，或者失效
            if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                $resultToken = $this->getToken();
                if (! $resultToken || ! $resultToken['access_token']) {
                    throw_exception(
                        '获取token失败:[' . $resultToken['errcode'] . ']' .
                             $resultToken['errmsg']);
                }
            } else {
                $i = 3;
            }
        }
        
        return $result;
    }

    public function query_user($openid) {
        $i = 1;
        while ($i ++ < 3) {
            $apiUrl = 'https://api.weixin.qq.com/cgi-bin/user/info?openid=' .
                 $openid . '&lang=zh_CN&access_token=' . $this->accessToken;
            echo $apiUrl;
            log_write($apiUrl, 'weixin_groupQuery');
            $error = '';
            $result_str = httpPost($apiUrl, null, $error, 
                array(
                    'TIMEOUT' => 30, 
                    'METHOD' => 'GET'));
            log_write($result_str, 'weixin_groupQuery');
            $result = json_decode($result_str, true);
            
            // 如果验证码超时，或者失效
            if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                $resultToken = $this->getToken();
                if (! $resultToken || ! $resultToken['access_token']) {
                    throw_exception(
                        '获取token失败:[' . $resultToken['errcode'] . ']' .
                             $resultToken['errmsg']);
                }
            } else {
                $i = 3;
            }
        }
        
        return $result;
    }

    /**
     * 群发文本回复
     */
    public function batch_send_text($info) {
        $openids = $info['openids'];
        $content = $info['content'];
        $is_to_all = $info['is_to_all'];
        $wx_batch_id = array();
        $is_to_all_fail = false;
        if ($is_to_all == 1) {
            $data = array(
                'filter' => array(
                    'is_to_all' => true), 
                'text' => array(
                    'content' => $content), 
                'msgtype' => 'text');
            $data = $this->unicodeDecode(json_encode($data));
            log_write($data, 'uploadnewsReq');
            $i = 1;
            while ($i ++ < 3) {
                $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=' .
                     $this->accessToken;
                log_write($apiUrl, 'uploadnewsReq');
                $error = '';
                $result_str = httpPost($apiUrl, $data, $error, 
                    array(
                        'TIMEOUT' => 30, 
                        'METHOD' => 'POST'));
                log_write($result_str, 'uploadnewsReq-is_to_all');
                $result = json_decode($result_str, true);
                
                // 如果验证码超时，或者失效
                if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                    $resultToken = $this->getToken();
                    if (! $resultToken || ! $resultToken['access_token']) {
                        throw_exception(
                            '获取token失败:[' . $resultToken['errcode'] . ']' .
                                 $resultToken['errmsg']);
                    }
                } elseif (! isset($result['msg_id'])) {
                    if ($result['errcode'] == '-1') {
                        $is_to_all_fail = true;
                        break;
                    }
                    throw_exception(
                        '发送失败！[' . $result['errcode'] . ']:' . $result['errmsg']);
                } else {
                    $wx_batch_id[] = $result['msg_id'];
                    $i = 3;
                }
            }
        }
        
        if (($is_to_all != 1) || $is_to_all_fail) {
            // 第三步：根据OpenID列表群发
            $num_unit = 10000;
            $t_openids = array_slice($openids, 0, $num_unit);
            $wx_batch_id = array();
            $i = 1;
            do {
                $data = array(
                    'touser' => array_values($t_openids), 
                    'text' => array(
                        'content' => $content), 
                    'msgtype' => 'text');
                $data = $this->unicodeDecode(json_encode($data));
                $t = 1;
                while ($i ++ < 3) {
                    $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=' .
                         $this->accessToken;
                    log_write($apiUrl, 'uploadnewsReq');
                    $error = '';
                    $result_str = httpPost($apiUrl, $data, $error, 
                        array(
                            'TIMEOUT' => 30, 
                            'METHOD' => 'POST'));
                    log_write($result_str, 'uploadnewsReq');
                    $result = json_decode($result_str, true);
                    
                    // 如果验证码超时，或者失效
                    if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                        $resultToken = $this->getToken();
                        if (! $resultToken || ! $resultToken['access_token']) {
                            throw_exception(
                                '获取token失败:[' . $resultToken['errcode'] . ']' .
                                     $resultToken['errmsg']);
                        }
                    } elseif (! isset($result['msg_id'])) {
                        throw_exception(
                            '发送失败！[' . $result['errcode'] . ']:' .
                                 $result['errmsg']);
                    } else {
                        $wx_batch_id[] = $result['msg_id'];
                        $i = 3;
                    }
                }
                $t_openids = array_slice($openids, $num_unit * ($t ++), 
                    $num_unit);
            }
            while (! empty($t_openids) && $t < 100);
        }
        log_write(
            '微信批量发送,机构号：' . $this->nodeId . '微信批次号：' .
                 print_r($wx_batch_id, true) . '次数：' . $t);
        
        return $wx_batch_id;
    }

    /**
     * 群发卡券
     */
    public function batch_send_card($info) {
        $openids = $info['openids'];
        $card_id = $info['card_id'];
        $is_to_all = $info['is_to_all'];
        
        $wx_batch_id = array();
        $is_to_all_fail = false;
        if ($is_to_all == 1) {
            $data = array(
                'filter' => array(
                    'is_to_all' => true), 
                'wxcard' => array(
                    'card_id' => $card_id), 
                'msgtype' => 'wxcard');
            $data = $this->unicodeDecode(json_encode($data));
            log_write($data, 'uploadnewsReq');
            $i = 1;
            while ($i ++ < 3) {
                $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=' .
                     $this->accessToken;
                log_write($apiUrl, 'uploadnewsReq');
                $error = '';
                $result_str = httpPost($apiUrl, $data, $error, 
                    array(
                        'TIMEOUT' => 30, 
                        'METHOD' => 'POST'));
                log_write($result_str, 'uploadnewsReq-is_to_all');
                $result = json_decode($result_str, true);
                
                // 如果验证码超时，或者失效
                if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                    $resultToken = $this->getToken();
                    if (! $resultToken || ! $resultToken['access_token']) {
                        throw_exception(
                            '获取token失败:[' . $resultToken['errcode'] . ']' .
                                 $resultToken['errmsg']);
                    }
                } elseif (! isset($result['msg_id'])) {
                    if ($result['errcode'] == '-1') {
                        $is_to_all_fail = true;
                        break;
                    }
                    throw_exception(
                        '发送失败！[' . $result['errcode'] . ']:' . $result['errmsg']);
                } else {
                    $wx_batch_id[] = $result['msg_id'];
                    $i = 3;
                }
            }
        }
        
        if (($is_to_all != 1) || $is_to_all_fail) {
            // 第三步：根据OpenID列表群发
            $num_unit = 10000;
            $t_openids = array_slice($openids, 0, $num_unit);
            $wx_batch_id = array();
            $t = 1;
            do {
                $data = array(
                    'touser' => array_values($t_openids), 
                    'wxcard' => array(
                        'card_id' => $card_id), 
                    'msgtype' => 'wxcard');
                $data = $this->unicodeDecode(json_encode($data));
                $i = 1;
                while ($i ++ < 3) {
                    $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=' .
                         $this->accessToken;
                    log_write($apiUrl, 'uploadnewsReq');
                    $error = '';
                    $result_str = httpPost($apiUrl, $data, $error, 
                        array(
                            'TIMEOUT' => 30, 
                            'METHOD' => 'POST'));
                    log_write($result_str, 'uploadnewsReq');
                    $result = json_decode($result_str, true);
                    
                    // 如果验证码超时，或者失效
                    if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                        $resultToken = $this->getToken();
                        if (! $resultToken || ! $resultToken['access_token']) {
                            throw_exception(
                                '获取token失败:[' . $resultToken['errcode'] . ']' .
                                     $resultToken['errmsg']);
                        }
                    } elseif (! isset($result['msg_id'])) {
                        throw_exception(
                            '发送失败！[' . $result['errcode'] . ']:' .
                                 $result['errmsg']);
                    } else {
                        $wx_batch_id[] = $result['msg_id'];
                        $i = 3;
                    }
                }
                $t_openids = array_slice($openids, $num_unit * ($t ++), 
                    $num_unit);
            }
            while (! empty($t_openids) && $t < 100);
        }
        log_write(
            '微信批量发送,机构号：' . $this->nodeId . '微信批次号：' .
                 print_r($wx_batch_id, true) . '次数：' . $t);
        
        return $wx_batch_id;
    }

    /**
     * 群发图片
     */
    public function batch_send_image($info) {
        $openids = $info['openids'];
        $content = $info['content'];
        $is_to_all = $info['is_to_all'];
        $im = imagecreatefromstring(file_get_contents($content));
        $tmpfname = tempnam(C('DOWN_TEMP'), 'wcj');
        $file = $tmpfname . '.jpg';
        rename($tmpfname, $file);
        $quality = 75;
        do {
            imagejpeg($im, $file, $quality);
            $quality -= 5;
        }
        while (filesize($file) / 1024 / 1024 > 1 && $quality > 0);
        $i = 1;
        while ($i ++ < 3) {
            $apiUrl = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=' .
                 $this->accessToken . '&type=image';
            $result_str = $this->https_request($apiUrl, 
                array(
                    "media" => "@" . $file));
            log_write($content, 'weixin_groupQuery');
            log_write($apiUrl, 'weixin_groupQuery');
            log_write($result_str, 'weixin_groupQuery');
            $result = json_decode($result_str, true);
            
            // 如果验证码超时，或者失效
            if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                $resultToken = $this->getToken();
                if (! $resultToken || ! $resultToken['access_token']) {
                    unlink($file);
                    throw_exception(
                        '获取token失败:[' . $resultToken['errcode'] . ']' .
                             $resultToken['errmsg']);
                }
            } else {
                $i = 3;
            }
        }
        unlink($file);
        $media_id = $result['media_id'];
        if (! $media_id)
            throw_exception('上传多媒体文件多媒体文件失败！' . $result_str);
        
        if ($is_to_all == 1) {
            $data = array(
                'filter' => array(
                    'is_to_all' => true), 
                'image' => array(
                    'media_id' => $media_id), 
                'msgtype' => 'image');
            $data = $this->unicodeDecode(json_encode($data));
            log_write($data, 'uploadnewsReq');
            $i = 1;
            while ($i ++ < 3) {
                $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=' .
                     $this->accessToken;
                log_write($apiUrl, 'uploadnewsReq');
                $error = '';
                $result_str = httpPost($apiUrl, $data, $error, 
                    array(
                        'TIMEOUT' => 30, 
                        'METHOD' => 'POST'));
                log_write($result_str, 'uploadnewsReq-is_to_all');
                $result = json_decode($result_str, true);
                
                // 如果验证码超时，或者失效
                if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                    $resultToken = $this->getToken();
                    if (! $resultToken || ! $resultToken['access_token']) {
                        throw_exception(
                            '获取token失败:[' . $resultToken['errcode'] . ']' .
                                 $resultToken['errmsg']);
                    }
                } elseif (! isset($result['msg_id'])) {
                    if ($result['errcode'] == '-1') {
                        $is_to_all_fail = true;
                        break;
                    }
                    throw_exception(
                        '发送失败！[' . $result['errcode'] . ']:' . $result['errmsg']);
                } else {
                    $wx_batch_id[] = $result['msg_id'];
                    $i = 3;
                }
            }
        }
        
        if (($is_to_all != 1) || $is_to_all_fail) {
            // 根据OpenID列表群发
            $num_unit = 10000;
            $t_openids = array_slice($openids, 0, $num_unit);
            $wx_batch_id = array();
            $t = 1;
            do {
                $data = array(
                    'touser' => array_values($t_openids), 
                    'image' => array(
                        'media_id' => $media_id), 
                    'msgtype' => 'image');
                $data = $this->unicodeDecode(json_encode($data));
                $i = 1;
                while ($i ++ < 3) {
                    $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=' .
                         $this->accessToken;
                    log_write($apiUrl, 'uploadnewsReq');
                    $error = '';
                    $result_str = httpPost($apiUrl, $data, $error, 
                        array(
                            'TIMEOUT' => 30, 
                            'METHOD' => 'POST'));
                    log_write($result_str, 'uploadnewsReq');
                    $result = json_decode($result_str, true);
                    
                    // 如果验证码超时，或者失效
                    if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                        $resultToken = $this->getToken();
                        if (! $resultToken || ! $resultToken['access_token']) {
                            throw_exception(
                                '获取token失败:[' . $resultToken['errcode'] . ']' .
                                     $resultToken['errmsg']);
                        }
                    } elseif (! isset($result['msg_id'])) {
                        throw_exception(
                            '发送失败！[' . $result['errcode'] . ']:' .
                                 $result['errmsg']);
                    } else {
                        $wx_batch_id[] = $result['msg_id'];
                        $i = 3;
                    }
                }
                $t_openids = array_slice($openids, $num_unit * ($t ++), 
                    $num_unit);
            }
            while (! empty($t_openids) && $t < 100);
        }
        log_write(
            '微信批量发送,机构号：' . $this->nodeId . '微信批次号：' .
                 print_r($wx_batch_id, true) . '次数：' . $t);
        
        return $wx_batch_id;
    }

    /**
     * 群发图文回复
     */
    public function batch_send($info) {
        $material_id = $info['material_id'];
        $openids = $info['openids'];
        $is_to_all = $info['is_to_all'];
        
        $media_id = $this->get_media_id($material_id);
        
        $wx_batch_id = array();
        $is_to_all_fail = false;
        if ($is_to_all == 1) {
            $data = array(
                'filter' => array(
                    'is_to_all' => true), 
                'mpnews' => array(
                    'media_id' => $media_id), 
                'msgtype' => 'mpnews');
            $data = $this->unicodeDecode(json_encode($data));
            
            $i = 1;
            while ($i ++ < 3) {
                $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=' .
                     $this->accessToken;
                log_write($apiUrl, 'uploadnewsReq');
                $error = '';
                $result_str = httpPost($apiUrl, $data, $error, 
                    array(
                        'TIMEOUT' => 30, 
                        'METHOD' => 'POST'));
                log_write($result_str, 'uploadnewsReq-is_to_all');
                $result = json_decode($result_str, true);
                
                // 如果验证码超时，或者失效
                if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                    $resultToken = $this->getToken();
                    if (! $resultToken || ! $resultToken['access_token']) {
                        throw_exception(
                            '获取token失败:[' . $resultToken['errcode'] . ']' .
                                 $resultToken['errmsg']);
                    }
                } elseif (! isset($result['msg_id'])) {
                    if ($result['errcode'] == '-1') {
                        $is_to_all_fail = true;
                        break;
                    }
                    throw_exception(
                        '发送失败！[' . $result['errcode'] . ']:' . $result['errmsg']);
                } else {
                    $wx_batch_id[] = $result['msg_id'];
                    $i = 3;
                }
            }
        }
        
        if (($is_to_all != 1) || $is_to_all_fail) {
            // 第三步：根据OpenID列表群发
            $num_unit = 10000;
            $t_openids = array_slice($openids, 0, $num_unit);
            $t = 1;
            
            $data = array(
                'touser' => array_values($t_openids), 
                'mpnews' => array(
                    'media_id' => $media_id), 
                'msgtype' => 'mpnews');
            $data = $this->unicodeDecode(json_encode($data));
            
            do {
                $i = 1;
                while ($i ++ < 3) {
                    $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=' .
                         $this->accessToken;
                    $error = '';
                    $result_str = httpPost($apiUrl, $data, $error, 
                        array(
                            'TIMEOUT' => 30, 
                            'METHOD' => 'POST'));
                    $result = json_decode($result_str, true);
                    
                    // 如果验证码超时，或者失效
                    if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                        $resultToken = $this->getToken();
                        if (! $resultToken || ! $resultToken['access_token']) {
                            throw_exception(
                                '获取token失败:[' . $resultToken['errcode'] . ']' .
                                     $resultToken['errmsg']);
                        }
                    } elseif (! isset($result['msg_id'])) {
                        throw_exception(
                            '发送失败！[' . $result['errcode'] . ']:' .
                                 $result['errmsg']);
                    } else {
                        $wx_batch_id[] = $result['msg_id'];
                        $i = 3;
                    }
                }
                $t_openids = array_slice($openids, $num_unit * ($t ++), 
                    $num_unit);
            }
            while (! empty($t_openids) && $t < 100);
        }
        log_write(
            '微信批量发送,机构号：' . $this->nodeId . '微信批次号：' .
                 print_r($wx_batch_id, true) . '次数：' . $t);
        return $wx_batch_id;
    }
    
    // 上传图文素材获取media_id
    public function get_media_id($material_id) {
        $articles = array();
        
        $list = M('twx_material')->where(
            "node_id = '{$this->nodeId}' and (id= '{$material_id}' or parent_id = '{$material_id}') and material_img is not null and material_img != ''")->select();
        if (! $list)
            throw_exception('未找到回复图文！');
            
            // 第一步：上传多媒体
        foreach ($list as $material) {
            // 将正文图片上传微信获取url
            $material['material_desc'] = html_entity_decode(
                $material['material_desc']);
            $preg = '/http:\/\/.*?(?:png|jpg)/';
            preg_match_all($preg, $material['material_desc'], $match);
            log_write(print_r($match, true), 'match');
            if ($match[0]) {
                foreach ($match[0] as $val) {
                    $des[] = $this->uploadimg($val);
                    $preg2[] = '/' . str_replace("/", "\/", $val) . '/';
                }
                $material['material_desc'] = preg_replace($preg2, $des, 
                    $material['material_desc']);
            }
            $material_img = $this->_getImgPath($material['material_img']);
            if (! file_exists($material_img))
                continue;
            
            $im = imagecreatefromstring(file_get_contents($material_img));
            $tmpfname = tempnam(C('DOWN_TEMP'), 'wcj');
            $file = $tmpfname . '.jpg';
            rename($tmpfname, $file);
            $quality = 75;
            do {
                imagejpeg($im, $file, $quality);
                $quality -= 5;
            }
            while (filesize($file) / 1024 > 64 && $quality > 0);
            
            $i = 1;
            while ($i ++ < 3) {
                $apiUrl = 'http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=' .
                     $this->accessToken . '&type=thumb';
                $result_str = $this->https_request($apiUrl, 
                    array(
                        "media" => "@" . $file));
                log_write($result_str, 'weixin_groupQuery');
                $result = json_decode($result_str, true);
                
                // 如果验证码超时，或者失效
                if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                    $resultToken = $this->getToken();
                    if (! $resultToken || ! $resultToken['access_token']) {
                        unlink($file);
                        throw_exception(
                            '获取token失败:[' . $resultToken['errcode'] . ']' .
                                 $resultToken['errmsg']);
                    }
                } else {
                    $i = 3;
                }
            }
            unlink($file);
            
            $media_id = $result['thumb_media_id'];
            if (! $media_id)
                throw_exception('上传多媒体文件多媒体文件失败！' . $result_str);
            
            $articles[] = array(
                'thumb_media_id' => $media_id, 
                // 'author' => $media_id,
                'title' => $material['material_title'], 
                'content_source_url' => $material['material_link'], 
                'content' => $material['material_desc'], 
                'digest' => $material['material_summary'], 
                'show_cover_pic' => $material['show_cover_pic']);
            
            log_write(print_r($articles, true), 'articles');
        }
        
        if (! $articles)
            throw_exception('图文错误！001');
        foreach ($articles as &$val) {
            $val['content'] = str_replace("\"", "'", $val['content']);
        }
        // 第二步：上传图文消息素材
        $data = $this->unicodeDecode(
            json_encode(array(
                'articles' => $articles)));
        log_write(print_r($data, true), 'newsData');
        $i = 1;
        while ($i ++ < 3) {
            $apiUrl = 'https://api.weixin.qq.com/cgi-bin/media/uploadnews?access_token=' .
                 $this->accessToken;
            log_write($apiUrl, 'uploadnewsReq');
            $error = '';
            $result_str = httpPost($apiUrl, $data, $error, 
                array(
                    'TIMEOUT' => 30, 
                    'METHOD' => 'POST'));
            log_write($result_str, 'uploadnewsReq');
            $result = json_decode($result_str, true);
            
            // 如果验证码超时，或者失效
            if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                $resultToken = $this->getToken();
                if (! $resultToken || ! $resultToken['access_token']) {
                    throw_exception(
                        '获取token失败:[' . $resultToken['errcode'] . ']' .
                             $resultToken['errmsg']);
                }
            } else {
                $i = 3;
            }
        }
        
        $media_id = $result['media_id'];
        if (! $media_id)
            throw_exception('上传图文消息素材失败！' . $result_str);
        
        return $media_id;
    }
    
    // 将正文图片上传微信获取url
    public function uploadimg($img) {
        $im = imagecreatefromstring(file_get_contents($img));
        $tmpfname = tempnam(C('DOWN_TEMP'), 'wcj');
        $file = $tmpfname . '.jpg';
        rename($tmpfname, $file);
        $quality = 75;
        do {
            imagejpeg($im, $file, $quality);
            $quality -= 5;
        }
        while (filesize($file) / 1024 / 1024 > 1 && $quality > 0);
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=' .
             $this->accessToken;
        $result_str = $this->https_request($apiUrl, 
            array(
                "media" => "@" . $file));
        log_write($result_str, 'weixin_uploadimg');
        $result = json_decode($result_str, true);
        unlink($file);
        return $result['url'];
    }
    
    // 微信群发素材预览
    public function preview_send($material_id, $towxname) {
        $media_id = $this->get_media_id($material_id);
        $data = array(
            'towxname' => $towxname, 
            'mpnews' => array(
                'media_id' => $media_id), 
            'msgtype' => 'mpnews');
        $data = $this->unicodeDecode(json_encode($data));
        
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=' .
             $this->accessToken;
        log_write($apiUrl, 'preview');
        $error = '';
        $result_str = httpPost($apiUrl, $data, $error, 
            array(
                'TIMEOUT' => 30, 
                'METHOD' => 'POST'));
        log_write($result_str, 'preview');
        return $result_str;
    }

    public function https_request($url, $data = null) {
        log_write($url . print_r($data, true));
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
        curl_close($curl);
        log_write('https_request' . $output);
        return $output;
    }
    
    // unicode字符转可见
    public function unicodeDecode($name) {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 
            create_function('$matches', 
                'return mb_convert_encoding(pack("H*", $matches[1]), "utf-8", "UCS-2BE");'), 
            $name);
    }

    private function _getImgPath($img) {
        if (! $img) {
            return false;
        }
        if (basename($img) == $img) {
            $upload_dir = C('UPLOAD') . 'Weixin/';
            return $upload_dir . $img;
        } else {
            return C('UPLOAD') . $img;
        }
    }

    /**
     * 微信消息模板
     *
     * @param string $openId 发送微信openId
     * @param string $nodeId
     * @param string $templateType
     * @param string $url
     * @param array $data 数值模板json数组
     * @return array $result
     */
    public function templateSend($openId, $nodeId, $templateType, $url, 
        $data = array()) {
        $result = M('twx_templatemsg_config')->where(
            array(
                'node_id' => $nodeId, 
                'template_type' => $templateType))->getField('status');
        if ($result) {
            return false; // 关闭微信模板消息
        }
        
        $info = M('twx_templatemsg_config')->where(
            array(
                'node_id' => $nodeId, 
                'template_type' => $templateType))->find();
        
        if (! $info) {
            log_write(print_r($info, true), 'WeixinTemplate');
        }
        
        $jsonData = array(
            "touser" => $openId, 
            "template_id" => $info['template_id'], 
            "url" => $url ? $url : $info['url'], 
            "topcolor" => $info['topcolor']);
        
        $jsonData['data'] = array_merge(json_decode($info['data_config'], true), 
            $data);
        
        $i = 1;
        while ($i ++ < 3) {
            $sendUrl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" .
                 $this->accessToken;
            
            $result = httpPost($sendUrl, json_encode($jsonData), $error,
                array(
                    'TIMEOUT' => 30, 
                    'METHOD' => 'POST'));
            $result = json_decode($result, true);
            
            if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
                $resultToken = $this->getToken();
                if (! $resultToken || ! $resultToken['access_token']) {
                    throw_exception(
                        '获取token失败:[' . $resultToken['errcode'] . ']' .
                             $resultToken['errmsg']);
                }
            } else {
                $i = 3;
                0 == $result['errcode'] ? $send_status = 1 : $send_status = 2;
                
                $data = array(
                    'node_id' => $this->nodeId, 
                    'opend_id' => $openId, 
                    'send_data' => json_encode($jsonData), 
                    'send_status' => $send_status, 
                    'wx_msgid' => $result['msgid'], 
                    'wx_msg_status' => $result['errmsg'], 
                    'add_time' => date("YmdHis"));
                
                $bool = M('twx_template_msg_trace')->where(
                    array(
                        'node_id' => $this->nodeId))->add($data);
                if (! $bool) {
                    log_write(M()->_sql(), "twx_template_msg_trace");
                }
            }
            log_write(print_r($result, true), "WeixinTemplate");
        }
        return $result;
    }

	/**
	 * 发送微信模板消息
	 *
	 * author  王盼
	 * @param $nodeId string 商户编号
	 * @param $data array('weChatTemplateId'     微信模板ID
     *                    'openId'               会员OPENID
     *                    'content'              发往微信的消息信息
     *                      )
	 * @return mixed
	 */
    public function autoSendTemplate($nodeId, $data){
		$this->init($nodeId);
        //检测是否关注商户微信公众号
        $isFans = M('twx_user')->where(array('openid'=>$data['openId'],'node_id'=>$nodeId))->getField('subscribe');
        if($isFans != 1){
            return false;
        }
        $sendToWeiXinData = array();
        foreach($data['content'] as $key=>$value){
            $sendToWeiXinData[$key] = array('value'=>$value);
        }

        $clickUrl = U('Label/Member/index', array('node_id' => $nodeId));
        $jsonData = array(
                'touser'      => $data['openId'],
                'template_id' => $data['weChatTemplateId'],
                'url'         => 'http://test.wangcaio2o.com' . $clickUrl,           //上线后改地址
                'data'        => $sendToWeiXinData
        );
        $sendUrl  = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=" . $this->accessToken;
        $error = [];
        $sendData = httpPost($sendUrl, json_encode($jsonData), $error, array('TIMEOUT' => 30, 'METHOD' => 'POST'));
        $result   = json_decode($sendData, true);
        if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
            $resultToken = $this->getToken();
            if (!$resultToken || !$resultToken['access_token']) {
                log_write('获取token失败:[' . $resultToken['errcode'] . ']  |  ' . $resultToken['errmsg']);
            } else {
                log_write('POST数据错误：' . $jsonData);
            }
        }
        return $error;

    }
    /**
     * 获取商户的微信消息模板列表
     * @param string $nodeId
     * @return mixed
     */
    public function getMessageList($nodeId){
        $this->init($nodeId);
        $sendUrl = "https://api.weixin.qq.com/cgi-bin/template/get_all_private_template?access_token=".$this->accessToken;
        $sendData = httpPost($sendUrl, '', $error, array('TIMEOUT' => 30, 'METHOD' => 'GET'));
        $result = json_decode($sendData, true);
        if (isset($result['errcode']) && in_array($result['errcode'], $this->refresh_token_code)) {
            $resultToken = $this->getToken();
            if(!$resultToken || !$resultToken['access_token']){
                log_write('获取token失败:['.$resultToken['errcode'].']  |  '.$resultToken['errmsg']);
            }
        }
        return $result;
    }

}