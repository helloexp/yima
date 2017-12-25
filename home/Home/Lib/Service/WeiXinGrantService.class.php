<?php
// 微信公告号服务接口
// 获取服务access_token api_component_token
// 获取预授权码 api_create_preauthcode
// 使用授权码换取公众号的授权信息 api_query_auth
// 获取（刷新）授权公众号的令牌 api_authorizer_token
// 获取授权方信息 api_get_authorizer_info
// 获取授权方的选项设置信息 api_get_authorizer_option
// 设置授权方的选项信息 api_set_authorizer_option
// 公众号服务通知 消息解密接口 api_service_notify_decrypt
// https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=xxxx&pre_auth_code=xxxxx&redirect_uri=xxxx
class WeiXinGrantService {

    public $component_appid;

    public $component_appsecret;

    public $encodingAesKey;

    public $component_notify_token;

    public $component_verify_ticket;

    public $component_access_token;

    public $component_access_token_time;

    public $pre_auth_code;

    public $authorizer_appid;

    public $authorizer_access_token;

    public $authorizer_refresh_token;

    public $func_info;
    // 授权权限集信息
    public $parm_arr;

    public $error = '';
    // 初始化
    public function init() {
        $where = "param_name = 'WX_SERVICE_PARM'";
        $wx_service_parm = M()->table('tsystem_param')
            ->where($where)
            ->find();
        if (! $wx_service_parm) {
            log_write("系统参数未配置");
        }
        $parm_value = $wx_service_parm['param_value'];

//        if ($parm_value == '{"component_access_token":null,"component_access_token_time":1460993064}') {
//            $parm_value = '{"component_appid":"wxf6db60cb8a1320da","component_appsecret":"0c79e1fa963cd80cc0be99b20a18faeb","encodingAesKey":"1234567890123456789012345678901234567890abc","component_notify_token":"testwangcaio2oservice","component_verify_ticket":"ticket@@@pq6AiEMQuK6IM9lcVuKSC6MZgoUW3DIkbB8VhdPM3T31eIJVmAHHZvG0VFY6htlDMPNsqO6xauh4949-iPeYeg","redirect_url":"http:\/\/test.wangcaio2o.com\/index.php?g=WeixinServ&m=Service&a=auth","component_access_token":5d2a85462645308392cc6a0dd6dd5a3cc3ee54bf14609922291816824890,"component_access_token_time":1460990753}';
//        }

        $this->parm_arr = json_decode($parm_value, true);
        if (!isset($this->parm_arr['component_access_token'])) {
            $this->parm_arr = json_decode('{"component_appid":"wxf6db60cb8a1320da","component_verify_ticket":"ticket@@@pq6AiEMQuK6IM9lcVuKSC6MZgoUW3DIkbB8VhdPM3T31eIJVmAHHZvG0VFY6htlDMPNsqO6xauh4949-iPeYeg","component_access_token_time":1460990753,"component_appsecret":"0c79e1fa963cd80cc0be99b20a18faeb","component_notify_token":"testwangcaio2oservice","component_access_token":"5d2a85462645308392cc6a0dd6dd5a3cc3ee54bf14609922291816824890","encodingAesKey":"1234567890123456789012345678901234567890abc","redirect_url":"http://test.wangcaio2o.com/index.php?g=WeixinServ&m=Service&a=auth"}', true);
        }
        log_write(__METHOD__ . ' final $this->parm_arr'.var_export($this->parm_arr,1));

        $this->component_appid = $this->parm_arr['component_appid'];
        $this->component_appsecret = $this->parm_arr['component_appsecret'];
        $this->encodingAesKey = $this->parm_arr['encodingAesKey'];
        $this->component_notify_token = $this->parm_arr['component_notify_token'];
        $this->component_verify_ticket = $this->parm_arr['component_verify_ticket'];
        $this->component_access_token = $this->parm_arr['component_access_token'];
        $this->component_access_token_time = $this->parm_arr['component_access_token_time'];
    }

    public function set_component_verify_ticket($component_verify_ticket) {
        $this->component_verify_ticket = $component_verify_ticket;
    }
    // 获取服务access_token api_component_token
    public function api_component_token() {
        if ($this->component_access_token == null ||
             $this->component_access_token_time == null ||
             $this->component_access_token_time < (time() - 5400)) {
            $apiUrl = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
            $post_arr['component_appid'] = $this->component_appid;
            $post_arr['component_appsecret'] = $this->component_appsecret;
            $post_arr['component_verify_ticket'] = $this->component_verify_ticket;
            $post_data = json_encode($post_arr);
            log_write('获取微信响应结果：'.var_export($post_data,true));
            $error = '';
            for ($i = 0; $i < 10; $i ++) {
                $result = httpPost($apiUrl, $post_data, $error, 
                    array(
                        'TIMEOUT' => 30));
                if ($result) {
                    break;
                } else {
                    log_write($error, 'error');
                }
                usleep(500 * 1000);
            }
            // {
            // "component_access_token":"61W3mEpU66027wgNZ_MhGHNQDHnFATkDa9-2llqrMBjUwxRSNPbVsMmyD-yq8wZETSoE5NQgecigDrSHkPtIYA",
            // "expires_in":7200
            // }
            $result = json_decode($result, true);
            $component_access_token = $result['component_access_token'];
            $this->component_access_token = $component_access_token;
            $this->parm_arr['component_access_token'] = $component_access_token;
            $this->parm_arr['component_access_token_time'] = time();
            return $result;
        }
    }
    
    // 获取服务access_token api_component_token
    // 必须先init
    public function get_component_token() {
        $this->api_component_token();
        $this->parm_arr['component_access_token'] = $this->component_access_token;
        $param_value = json_encode($this->parm_arr);
        $wx_service_parm['param_value'] = $param_value;
        $where = "param_name = 'WX_SERVICE_PARM'";
        $rs = M()->table('tsystem_param')
            ->where($where)
            ->save($wx_service_parm);
        if ($rs === false) {
            log_write("系统参数配置错误[2]" . $param_value);
            ;
        }
    }
    
    // 获取预授权码 api_create_preauthcode
    public function api_create_preauthcode() {
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/component/api_create_preauthcode?component_access_token=' .
             $this->component_access_token;
        $post_arr['component_appid'] = $this->component_appid;
        $post_data = json_encode($post_arr);
        $error = '';
        for ($i = 0; $i < 10; $i ++) {
            $result = httpPost($apiUrl, $post_data, $error, 
                array(
                    'TIMEOUT' => 30));
            if ($result) {
                break;
            } else {
                log_write($error, 'error');
            }
            usleep(500 * 1000);
        }
        // {
        // "pre_auth_code":"Cx_Dk6qiBE0Dmx4EmlT3oRfArPvwSQ-oa3NL_fwHM7VI08r52wazoZX2Rhpz1dEw",
        // "expires_in":600
        // }
        $result = json_decode($result, true);
        $pre_auth_code = $result['pre_auth_code'];
        $this->pre_auth_code = $pre_auth_code;
        return $result;
    }
    
    // 使用授权码换取公众号的授权信息 api_query_auth
    public function api_query_auth($authorization_code) {
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=' .
             $this->component_access_token;
        $post_arr['component_appid'] = $this->component_appid;
        $post_arr['authorization_code'] = $authorization_code;
        $post_data = json_encode($post_arr);
        $error = '';
        for ($i = 0; $i < 10; $i ++) {
            $result = httpPost($apiUrl, $post_data, $error, 
                array(
                    'TIMEOUT' => 30));
            if ($result) {
                break;
            } else {
                log_write($error, 'error');
            }
            usleep(500 * 1000);
        }
        /*
         * { "authorization_info": { "authorizer_appid": "wxf8b4f85f3a794e77",
         * "authorizer_access_token":
         * "QXjUqNqfYVH0yBE1iI_7vuN_9gQbpjfK7hYwJ3P7xOa88a89-Aga5x1NMYJyB8G2yKt1KCl0nPC3W9GJzw0Zzq_dBxc8pxIGUNi_bFes0qM",
         * "expires_in": 7200, "authorizer_refresh_token":
         * "dTo-YCXPL4llX-u1W1pPpnp8Hgm4wpJtlR6iV0doKdY", "func_info": [ {
         * "funcscope_category": { "id": 1 } }, { "funcscope_category": { "id":
         * 2 } }, { "funcscope_category": { "id": 3 } } ] }
         */
        $result = json_decode($result, true);
        $result = $result['authorization_info'];
        $this->authorizer_appid = $result['authorizer_appid'];
        $this->authorizer_access_token = $result['authorizer_access_token'];
        $this->authorizer_refresh_token = $result['authorizer_refresh_token'];
        $this->func_info = $result['func_info'];
        return $result;
    }
    
    // 获取（刷新）授权公众号的令牌 api_authorizer_token
    public function api_authorizer_token() {
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/component/api_authorizer_token?component_access_token=' .
             $this->component_access_token;
        $post_arr['component_appid'] = $this->component_appid;
        $post_arr['authorizer_appid'] = $this->authorizer_appid;
        $post_arr['authorizer_refresh_token'] = $this->authorizer_refresh_token;
        $post_data = json_encode($post_arr);
        $error = '';
        for ($i = 0; $i < 10; $i ++) {
            $result = httpPost($apiUrl, $post_data, $error, 
                array(
                    'TIMEOUT' => 30));
            if ($result) {
                break;
            } else {
                log_write($error, 'error');
            }
            usleep(500 * 1000);
        }
        /*
         * { "authorizer_access_token":
         * "aaUl5s6kAByLwgV0BhXNuIFFUqfrR8vTATsoSHukcIGqJgrc4KmMJ-JlKoC_-NKCLBvuU1cWPv4vDcLN8Z0pn5I45mpATruU0b51hzeT1f8",
         * "expires_in": 7200, "authorizer_refresh_token":
         * "BstnRqgTJBXb9N2aJq6L5hzfJwP406tpfahQeLNxX0w" }
         */
        $result = json_decode($result, true);
        $this->authorizer_access_token = $result['authorizer_access_token'];
        $this->authorizer_refresh_token = $result['authorizer_refresh_token'];
        return $result;
    }
    // 获取授权方信息 api_get_authorizer_info
    public function api_get_authorizer_info() {
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_info?component_access_token=' .
             $this->component_access_token;
        $post_arr['component_appid'] = $this->component_appid;
        $post_arr['authorizer_appid'] = $this->authorizer_appid;
        $post_data = json_encode($post_arr);
        $error = '';
        for ($i = 0; $i < 10; $i ++) {
            $result = httpPost($apiUrl, $post_data, $error, 
                array(
                    'TIMEOUT' => 30));
            if ($result) {
                break;
            } else {
                log_write($error, 'error');
            }
            usleep(500 * 1000);
        }
        /*
         * { "authorizer_info": { "nick_name": "微信SDK Demo Special", "head_img":
         * "http://wx.qlogo.cn/mmopen/GPyw0pGicibl5Eda4GmSSbTguhjg9LZjumHmVjybjiaQXnE9XrXEts6ny9Uv4Fk6hOScWRDibq1fI0WOkSaAjaecNTict3n6EjJaC/0",
         * "service_type_info": { "id": 2 }, "verify_type_info": { "id": 0 },
         * "user_name":"gh_eb5e3a772040", "alias":"paytest01" },
         * "authorization_info": { "appid": "wxf8b4f85f3a794e77", "func_info": [
         * { "funcscope_category": { "id": 1 } }, { "funcscope_category": {
         * "id": 2 } }, { "funcscope_category": { "id": 3 } } ] } }
         */
        $result = json_decode($result, true);
        $result = $result['authorizer_info'];
        // $this->authorizer_info = $result['authorizer_info'];
        // $this->authorization_info = $result['authorization_info'];
        return $result;
    }
    
    // 获取授权方的选项设置信息 api_get_authorizer_option
    /*
     * option_name option_value 选项值 说明 location_report(地理位置上报选项) 0 无上报 1 进入会话时上报
     * 2 每5s上报 voice_recognize（语音识别开关选项） 0 关闭语音识别 1 开启语音识别
     * customer_service（客服开关选项） 0 关闭多客服 1 开启多客服
     */
    public function api_get_authorizer_option($option_name) {
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/component/api_get_authorizer_option?component_access_token=' .
             $this->component_access_token;
        $post_arr['component_appid'] = $this->component_appid;
        $post_arr['authorizer_appid'] = $this->authorizer_appid;
        $post_arr['option_name'] = $option_name;
        $post_data = json_encode($post_arr);
        $error = '';
        for ($i = 0; $i < 10; $i ++) {
            $result = httpPost($apiUrl, $post_data, $error, 
                array(
                    'TIMEOUT' => 30));
            if ($result) {
                break;
            } else {
                log_write($error, 'error');
            }
            usleep(500 * 1000);
        }
        /*
         * { "authorizer_appid":"wx7bc5ba58cabd00f4",
         * "option_name":"voice_recognize", "option_value":"1" }
         */
        $result = json_decode($result, true);
        $option_value = $result['option_value'];
        return $option_value;
    }
    
    // 设置授权方的选项信息 api_set_authorizer_option
    public function api_set_authorizer_option($option_name, $option_value) {
        $apiUrl = 'https://api.weixin.qq.com/cgi-bin/component/api_set_authorizer_option?component_access_token=' .
             $this->component_access_token;
        $post_arr['component_appid'] = $this->component_appid;
        $post_arr['authorizer_appid'] = $this->authorizer_appid;
        $post_arr['option_name'] = $option_name;
        $post_arr['option_value'] = $option_value;
        $post_data = json_encode($post_arr);
        $error = '';
        for ($i = 0; $i < 10; $i ++) {
            $result = httpPost($apiUrl, $post_data, $error, 
                array(
                    'TIMEOUT' => 30));
            if ($result) {
                break;
            } else {
                log_write($error, 'error');
            }
            usleep(500 * 1000);
        }
        /*
         * { "errcode":0, "errmsg":"ok" }
         */
        $result = json_decode($result, true);
        if (($result['errcode'] != null) && ($result['errcode'] == 0)) {
            return true;
        } else {
            return $result;
        }
    }
    
    // 公众号服务通知 消息解密接口 api_service_notify_decrypt
    public function api_service_notify_decrypt($from_xml, $msg_sign, $timeStamp,  $nonce) {
        $pc = new WXBizMsgCrypt($this->component_notify_token, $this->encodingAesKey, $this->component_appid);
/*
        $xml_tree = new DOMDocument();
        $xml_tree->loadXML($from_xml);
        $array_a = $xml_tree->getElementsByTagName('AppId');
        $array_e = $xml_tree->getElementsByTagName('Encrypt');
        $appId = $array_a->item(0)->nodeValue;
        $encrypt = $array_e->item(0)->nodeValue;
        $xmlData = <<<XML
        <xml><AppId><![CDATA[{$appId}]]></AppId><Encrypt><![CDATA[{$encrypt}]]></Encrypt><MsgSignature>{$msg_sign}</MsgSignature><TimeStamp>{$timeStamp}</TimeStamp><Nonce>{$nonce}</Nonce></xml>
XML;
        log_write('[$xmlData]'.var_export($xmlData,true));
*/

        $msg = '';
        $errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
//        $errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);

        log_write(__METHOD__.' $msg:'.var_export($msg,true));

        if ($errCode != 0) {
            return false;
        }
        import('@.ORG.Util.Xml') or die('[@.ORG.Util.Xml]导入失败');
        $xml = new Xml();
        $xml_arr = $xml->parse($msg);
        $xml_arr = $xml_arr['xml'];
        if ($xml_arr['InfoType'] == 'component_verify_ticket') { // 秘钥同步
            $this->component_verify_ticket = $xml_arr['ComponentVerifyTicket'];
            $this->parm_arr['component_verify_ticket'] = $xml_arr['ComponentVerifyTicket'];
            $param_value = json_encode($this->parm_arr);
            $wx_service_parm['param_value'] = $param_value;
            $where = "param_name = 'WX_SERVICE_PARM'";
            $rs = M()->table('tsystem_param')
                ->where($where)
                ->save($wx_service_parm);
            if ($rs === false) {
                log_write("系统参数配置错误[0]" . $param_value . M()->getDbError());
                ;
            }
            return $xml_arr;
        } else if ($xml_arr['InfoType'] == 'unauthorized') { // 取消授权通知
            $this->authorizer_appid = $xml_arr['AuthorizerAppid'];
            // 更改微信状态 tweixin_info auth_flag 为0 未授权
            $wx_info['auth_flag'] = '0';
            $where = "app_id = '" . $this->authorizer_appid . "'";
            $rs = M()->table('tweixin_info')
                ->where($where)
                ->save($wx_info);
            if (! $rs) {
                log_write(
                    "更改微信状态 tweixin_info auth_flag 为停用 错误 :" .
                         $this->authorizer_appid);
                ;
            }
            return $xml_arr;
        }
        return false;
    }
    
    // 公众号服务消息转发接口 api_service_notify_decrypt
    public function msg_redirect($from_xml, $msg_sign, $timeStamp, $nonce, $app_id) {
        $pc = new WXBizMsgCrypt($this->component_notify_token, 
            $this->encodingAesKey, $this->component_appid);
        $msg = '';
        $errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, 
            $msg);
        log_write("dercypt :" . $msg);
        if ($errCode != 0) {
            return false;
        }
        import('@.ORG.Util.Xml') or die('[@.ORG.Util.Xml]导入失败');
        // 通过APPID找 tweixin_info
        $where = "app_id = '" . str_replace('/', "", $app_id) . "' ";
        $weixin_info = M()->table('tweixin_info')
            ->where($where)
            ->find();
        if (! $weixin_info) {
            log_write("微信机构未配置 :" . $where);
            return false;
        }
        // 向新地址请求
        $error = '';
        $result = httpPost(
            $weixin_info['callback_url'] . "&ischecked=ischecked", $msg, $error, 
            array(
                'TIMEOUT' => 5));
        if ($result) {} else {
            log_write($error, 'error');
            $result = "";
        }
        $encryptMsg = "";
        if (strlen($result) > 0)
            $pc->encryptMsg($result, $timeStamp, $nonce, $encryptMsg);
        else
            return "";
        return $encryptMsg;
    }
    
    // 获取授权链接
    public function get_auth_url($redirect_url, $node_id) {
        $url = $this->parm_arr['redirect_url'] . "&node_id=" . $node_id .
             "&header_url=" . base64_encode($redirect_url);
        log_write("test===========" . $url);
        // 获取服务access_token api_component_token
        $this->api_component_token();
        $this->parm_arr['component_access_token'] = $this->component_access_token;
        $param_value = json_encode($this->parm_arr);
        $wx_service_parm['param_value'] = $param_value;
        $where = "param_name = 'WX_SERVICE_PARM'";
        $rs = M()->table('tsystem_param')
            ->where($where)
            ->save($wx_service_parm);
        if ($rs === false) {
            log_write("系统参数配置错误[1]" . $param_value . M()->getDbError());
            ;
        }
        // 获取预授权码 api_create_preauthcode
        $this->api_create_preauthcode();
        log_write(
            "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=" .
                 $this->component_appid . "&pre_auth_code=" .
                 $this->pre_auth_code . "&redirect_uri=" . urlencode($url));
        $return = "https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid=" .
             $this->component_appid . "&pre_auth_code=" . $this->pre_auth_code .
             "&redirect_uri=" . urlencode($url);
        return $return;
    }
    // 通过授权码设置微信账户信息
    public function set_weixin_info($node_id, $auth_code) {
        $this->component_access_token = $this->parm_arr['component_access_token'];
        // 使用授权码换取公众号的授权信息
        $this->api_query_auth($auth_code);
        // 查询公众号的信息
        $result = $this->api_get_authorizer_info();
        $weixin_info['weixin_code'] = $result['nick_name']; // 微信昵称
                                                            // 判断类型
        /*
         * service_type_info 授权方公众号类型，0代表订阅号，1代表由历史老帐号升级后的订阅号，2代表服务号
         * verify_type_info 授权方认证类型，-1代表未认证，0代表微信认证，1代表新浪微博认证，2代表腾讯微博认证
         * account_type 账号类型：1未认证订阅号2已认证订阅号3未认证服务号4已认证服务号
         */
        if ($result['service_type_info']['id'] == '2') {
            if ($result['verify_type_info']['id'] == '-1') {
                $weixin_info['account_type'] = '3'; // 3未认证服务号
            } else {
                $weixin_info['account_type'] = '4'; // 4已认证服务号
            }
        } else {
            if ($result['verify_type_info']['id'] == '-1') {
                $weixin_info['account_type'] = '1'; // 1未认证订阅号
            } else {
                $weixin_info['account_type'] = '2'; // 2已认证订阅号
            }
        }
        $weixin_info['node_wx_id'] = $result['user_name']; // 微信账号
        $weixin_info['app_id'] = $this->authorizer_appid;
        $weixin_info['app_access_token'] = $this->authorizer_access_token;
        $weixin_info['authorizer_refresh_token'] = $this->authorizer_refresh_token;
        $weixin_info['head_img'] = $result['head_img']; // 微信公众号头像
        $weixin_info['auth_flag'] = '1';
        $weixin_info['app_secret'] = '1';               //自动绑定时候填充字段值
        if ($weixin_info['app_id'] == null) {
            return false;
        }
        $weixin_info['func_info'] = json_encode($this->func_info);
        $weixin_info['err_flag'] = '1';
        log_write("1111 :" . var_export($weixin_info, true));
        
        $where = "node_id = '" . $node_id . "'";
        $old_wxData = M()->table('tweixin_info')
            ->where($where)
            ->find();
        
        $rs = M()->table('tweixin_info')
            ->where($where)
            ->save($weixin_info);
        if ($rs === false) {
            log_write("使用授权码设置公众号的授权信息[1] 错误 :" . $auth_code);
            return false;
        }
        
        if ($old_wxData['app_id'] != $weixin_info['app_id']) {
            $uret = M("tmember_info")->where(
                array(
                    'node_id' => $node_id, 
                    '_string' => 'phone_no IS NOT NULL'))->save(
                array(
                    'mwx_openid' => array(
                        'exp', 
                        ' NULL')));
            log_write("服务号变更删除机构下会员商户授权openid：" . M()->_sql());
        }
        // 智能绑定更新之前绑定过的app_id，使为空
        $where = " node_id <> '" . $node_id . "'" . " and app_id = '" .
             $this->authorizer_appid . "'";
        $rs = M()->table('tweixin_info')
            ->where($where)
            ->save(
            array(
                'app_id' => '', 
                'auth_flag' => '0'));
        return true;
    }
    
    // 刷新微信账户授权token信息
    public function refresh_weixin_token_by_nodeid($node_id) {
        // 获取服务access_token api_component_token
        $this->api_component_token();
        $this->parm_arr['component_access_token'] = $this->component_access_token;
        $param_value = json_encode($this->parm_arr);
        $wx_service_parm['param_value'] = $param_value;
        $where = "param_name = 'WX_SERVICE_PARM'";
        $rs = M()->table('tsystem_param')
            ->where($where)
            ->save($wx_service_parm);
        if ($rs === false) {
            log_write("系统参数配置错误[2]" . $param_value);
            ;
        }
        // 刷新微信账户授权token信息
        $where = "node_id = '" . $node_id . "'";
        $weixin_info = M()->table('tweixin_info')
            ->where($where)
            ->find();
        if (! $weixin_info) {
            log_write("微信公众号未配置");
            return false;
        }
        if ($weixin_info['auth_flag'] != 1) { // 未授权
            log_write("微信公众号未未授权");
            return false;
        }
        $this->authorizer_appid = $weixin_info['app_id'];
        $this->authorizer_refresh_token = $weixin_info['authorizer_refresh_token'];
        // 刷新公众号信息
        $this->api_authorizer_token();
        $weixin_info['app_access_token'] = $this->authorizer_access_token;
        $weixin_info['authorizer_refresh_token'] = $this->authorizer_refresh_token;
        $where = "node_id = '" . $node_id . "'";
        $rs = M()->table('tweixin_info')
            ->where($where)
            ->save($weixin_info);
        if ($rs === false) {
            log_write("使用授权码设置公众号的授权信息[2] 错误 :" . M()->getDbError());
        }
        return $this->authorizer_access_token;
    }
    
    // 刷新微信账户授权token信息
    public function refresh_weixin_token_by_appid($app_id) {
        // 获取服务access_token api_component_token
        $this->api_component_token();
        $this->parm_arr['component_access_token'] = $this->component_access_token;
        $param_value = json_encode($this->parm_arr);
        $wx_service_parm['param_value'] = $param_value;
        $where = "param_name = 'WX_SERVICE_PARM'";
        $rs = M()->table('tsystem_param')
            ->where($where)
            ->save($wx_service_parm);
        if ($rs === false) {
            log_write("系统参数配置错误[3]" . $param_value);
            ;
        }
        // 刷新微信账户授权token信息
        $where = "app_id = '" . $app_id . "'";
        $weixin_info = M()->table('tweixin_info')
            ->where($where)
            ->find();
        if (! $weixin_info) {
            log_write("微信公众号未配置");
            return false;
        }
        if ($weixin_info['auth_flag'] != 1) { // 未授权
            log_write("微信公众号未未授权");
            return false;
        }
        $node_id = $weixin_info['node_id'];
        $this->authorizer_appid = $weixin_info['app_id'];
        $this->authorizer_refresh_token = $weixin_info['authorizer_refresh_token'];
        // 刷新公众号信息
        $this->api_authorizer_token();
        $weixin_info['app_access_token'] = $this->authorizer_access_token;
        $weixin_info['authorizer_refresh_token'] = $this->authorizer_refresh_token;
        $where = "node_id = '" . $node_id . "'";
        $rs = M()->table('tweixin_info')
            ->where($where)
            ->save($weixin_info);
        if ($rs === false) {
            log_write("使用授权码设置公众号的授权信息[3] 错误 :" . M()->getDbError());
            ;
        }
        return $this->authorizer_access_token;
    }
    
    // unicode字符转可见
    public function unicodeDecode($name) {
        return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 
            create_function('$matches', 
                'return mb_convert_encoding(pack("H*", $matches[1]), "utf-8", "UCS-2BE");'), 
            $name);
    }
}

/**
 * XMLParse class 提供提取消息格式中的密文及生成回复消息格式的接口.
 */
class XMLParse {

    /**
     * 提取出xml数据包中的加密消息
     *
     * @param string $xmltext 待提取的xml字符串
     * @return string 提取出的加密消息字符串
     */
    public function extract($xmltext) {
        try {
            $xml = new DOMDocument();
            $xml->loadXML($xmltext);
            $array_e = $xml->getElementsByTagName('Encrypt');
            $array_a = $xml->getElementsByTagName('ToUserName');
            $encrypt = $array_e->item(0)->nodeValue;
            $tousername = $array_a->item(0)->nodeValue;
            return array(
                0, 
                $encrypt, 
                $tousername);
        } catch (Exception $e) {
            // print $e . "\n";
            return array(
                ErrorCode::$ParseXmlError, 
                null, 
                null);
        }
    }

    /**
     * 生成xml消息
     *
     * @param string $encrypt 加密后的消息密文
     * @param string $signature 安全签名
     * @param string $timestamp 时间戳
     * @param string $nonce 随机字符串
     */
    public function generate($encrypt, $signature, $timestamp, $nonce) {
        $format = "<xml>
<Encrypt><![CDATA[%s]]></Encrypt>
<MsgSignature><![CDATA[%s]]></MsgSignature>
<TimeStamp>%s</TimeStamp>
<Nonce><![CDATA[%s]]></Nonce>
</xml>";
        return sprintf($format, $encrypt, $signature, $timestamp, $nonce);
    }
}

/**
 * PKCS7Encoder class 提供基于PKCS7算法的加解密接口.
 */
class PKCS7Encoder {

    public static $block_size = 32;

    /**
     * 对需要加密的明文进行填充补位
     *
     * @param $text 需要进行填充补位操作的明文
     * @return 补齐明文字符串
     */
    function encode($text) {
        $block_size = PKCS7Encoder::$block_size;
        $text_length = strlen($text);
        // 计算需要填充的位数
        $amount_to_pad = PKCS7Encoder::$block_size -
             ($text_length % PKCS7Encoder::$block_size);
        if ($amount_to_pad == 0) {
            $amount_to_pad = PKCS7Encoder::block_size;
        }
        // 获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = "";
        for ($index = 0; $index < $amount_to_pad; $index ++) {
            $tmp .= $pad_chr;
        }
        return $text . $tmp;
    }

    /**
     * 对解密后的明文进行补位删除
     *
     * @param decrypted 解密后的明文
     * @return 删除填充补位后的明文
     */
    function decode($text) {
        $pad = ord(substr($text, - 1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }

        log_write('[$pad]:'.var_export($pad,true));

        return substr($text, 0, (strlen($text) - $pad));
    }
}

/**
 * Prpcrypt class 提供接收和推送给公众平台消息的加解密接口.
 */
class Prpcrypt {

    public $key;

    function Prpcrypt($k) {
        $this->key = base64_decode($k . "=");
    }

    /**
     * 对明文进行加密
     *
     * @param string $text 需要加密的明文
     * @return string 加密后的密文
     */
    public function encrypt($text, $appid) {
        try {
            // 获得16位随机字符串，填充到明文之前
            $random = $this->getRandomStr();
            $text = $random . pack("N", strlen($text)) . $text . $appid;
            // 网络字节序
            $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 
                MCRYPT_MODE_CBC, '');
            $iv = substr($this->key, 0, 16);
            // 使用自定义的填充方式对明文进行补位填充
            $pkc_encoder = new PKCS7Encoder();
            $text = $pkc_encoder->encode($text);
            mcrypt_generic_init($module, $this->key, $iv);
            // 加密
            $encrypted = mcrypt_generic($module, $text);
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);
            
            // print(base64_encode($encrypted));
            // 使用BASE64对加密后的字符串进行编码
            return array(
                ErrorCode::$OK, 
                base64_encode($encrypted));
        } catch (Exception $e) {
            // print $e;
            return array(
                ErrorCode::$EncryptAESError, 
                null);
        }
    }

    /**
     * 对密文进行解密
     *
     * @param string $encrypted 需要解密的密文
     * @return string 解密得到的明文
     */
    public function decrypt($encrypted, $appid) {
        try {
            // 使用BASE64对需要解密的字符串进行解码
            log_write(__METHOD__.' $encrypted:'.var_export($encrypted,true));
            log_write(__METHOD__.' $appid:'.var_export($appid,true));
            $ciphertext_dec = base64_decode($encrypted);
            log_write(__METHOD__.' $ciphertext_dec:'.var_export($ciphertext_dec,true));
            $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '',
                MCRYPT_MODE_CBC, '');
            $iv = substr($this->key, 0, 16);
            mcrypt_generic_init($module, $this->key, $iv);
            // 解密
            $decrypted = mdecrypt_generic($module, $ciphertext_dec);
            log_write(__METHOD__.' $decrypted:'.var_export($decrypted,true));
            mcrypt_generic_deinit($module);
            mcrypt_module_close($module);
        } catch (Exception $e) {
            log_write(__METHOD__.' $e:'.$e->getMessage());
            return array(
                ErrorCode::$DecryptAESError, 
                null);
        }
        try {
            // 去除补位字符
            $pkc_encoder = new PKCS7Encoder();
            $result = $pkc_encoder->decode($decrypted);
            log_write(__METHOD__.' $result:'.var_export($result,true));
            // 去除16位随机字符串,网络字节序和AppId
            if (strlen($result) < 16)
                return "";
            $content = substr($result, 16, strlen($result));
            $len_list = unpack("N", substr($content, 0, 4));
            $xml_len = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_appid = substr($content, $xml_len + 4);
            log_write(__METHOD__.' $content:'.var_export($content,1));
            log_write(__METHOD__.' $len_list:'.var_export($len_list,1));
            log_write(__METHOD__.' $xml_len:'.var_export($xml_len,1));
            log_write(__METHOD__.' $xml_content:'.var_export($xml_content,1));
            log_write(__METHOD__.' $from_appid:'.var_export($from_appid,1));
        } catch (Exception $e) {
            // print $e;
            log_write(__METHOD__.' $result:'.$e->getMessage());
            return array(
                ErrorCode::$IllegalBuffer, 
                null);
        }
        if ($from_appid != $appid)
            return array(
                ErrorCode::$ValidateAppidError, 
                null);
        return array(
            0, 
            $xml_content);
    }

    /**
     * 随机生成16位字符串
     *
     * @return string 生成的字符串
     */
    function getRandomStr() {
        $str = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($str_pol) - 1;
        for ($i = 0; $i < 16; $i ++) {
            $str .= $str_pol[mt_rand(0, $max)];
        }
        return $str;
    }
}

/**
 * 1.第三方回复加密消息给公众平台； 2.第三方收到公众平台发送的消息，验证消息的安全性，并对消息进行解密。
 */
class WXBizMsgCrypt {

    private $token;

    private $encodingAesKey;

    private $appId;

    /**
     * 构造函数
     *
     * @param $token string 公众平台上，开发者设置的token
     * @param $encodingAesKey string 公众平台上，开发者设置的EncodingAESKey
     * @param $appId string 公众平台的appId
     */
    public function WXBizMsgCrypt($token, $encodingAesKey, $appId) {
        $this->token = $token;
        $this->encodingAesKey = $encodingAesKey;
        $this->appId = $appId;

    }

    /**
     * 将公众平台回复用户的消息加密打包. <ol> <li>对要发送的消息进行AES-CBC加密</li> <li>生成安全签名</li>
     * <li>将消息密文和安全签名打包成xml格式</li> </ol>
     *
     * @param $replyMsg string 公众平台待回复用户的消息，xml格式的字符串
     * @param $timeStamp string 时间戳，可以自己生成，也可以用URL参数的timestamp
     * @param $nonce string 随机串，可以自己生成，也可以用URL参数的nonce
     * @param &$encryptMsg string 加密后的可以直接回复用户的密文，包括msg_signature, timestamp,
     *            nonce, encrypt的xml格式的字符串, 当return返回0时有效
     * @return int 成功0，失败返回对应的错误码
     */
    public function encryptMsg($replyMsg, $timeStamp, $nonce, &$encryptMsg) {
        $pc = new Prpcrypt($this->encodingAesKey);
        
        // 加密
        $array = $pc->encrypt($replyMsg, $this->appId);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        
        if ($timeStamp == null) {
            $timeStamp = time();
        }
        $encrypt = $array[1];
        
        // 生成安全签名
        $sha1 = new SHA1();
        $array = $sha1->getSHA1($this->token, $timeStamp, $nonce, $encrypt);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        $signature = $array[1];
        
        // 生成发送的xml
        $xmlparse = new XMLParse();
        $encryptMsg = $xmlparse->generate($encrypt, $signature, $timeStamp, 
            $nonce);
        return ErrorCode::$OK;
    }

    /**
     * 检验消息的真实性，并且获取解密后的明文. <ol> <li>利用收到的密文生成安全签名，进行签名验证</li>
     * <li>若验证通过，则提取xml中的加密消息</li> <li>对消息进行解密</li> </ol>
     *
     * @param $msgSignature string 签名串，对应URL参数的msg_signature
     * @param $timestamp string 时间戳 对应URL参数的timestamp
     * @param $nonce string 随机串，对应URL参数的nonce
     * @param $postData string 密文，对应POST请求的数据
     * @param &$msg string 解密后的原文，当return返回0时有效
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptMsg($msgSignature, $timestamp = null, $nonce, $postData, &$msg) {
        log_write(__METHOD__ .'start:');
        if (strlen($this->encodingAesKey) != 43) {
            log_write(__METHOD__ .'err 43:');
            return ErrorCode::$IllegalAesKey;
        }
        $pc = new Prpcrypt($this->encodingAesKey);

        // 提取密文
        $xmlparse = new XMLParse();
        $array = $xmlparse->extract($postData);
        log_write(__METHOD__ .'extract $array:'.var_export($array,true));
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        if ($timestamp == null) {
            $timestamp = time();
        }
        $encrypt = $array[1];

        $touser_name = $array[2];
        // 验证安全签名
        $sha1 = new SHA1();
        $array = $sha1->getSHA1($this->token, $timestamp, $nonce, $encrypt);
        $ret = $array[0];
        log_write(__METHOD__.' getSHA1 $array:'.var_export($array,true));
        if ($ret != 0) {
            return $ret;
        }
        $signature = $array[1];
        if ($signature != $msgSignature) {
            log_write(__METHOD__ . ' error: $signature:'.var_export($signature,true));
            log_write(__METHOD__ . ' error: $msgSignature '.var_export($msgSignature,true));
            return ErrorCode::$ValidateSignatureError;
        }
        $result = $pc->decrypt($encrypt, $this->appId);
        log_write(__METHOD__.' $result:'.var_export($result,true));
        if ($result[0] != 0) {
            return $result[0];
        }
        $msg = $result[1];

        log_write(__METHOD__ .  ' $msg:'.var_export($msg,true));
        return ErrorCode::$OK;
    }
}

/**
 * SHA1 class 计算公众平台的消息签名接口.
 */
class SHA1 {

    /**
     * 用SHA1算法生成安全签名
     *
     * @param string $token 票据
     * @param string $timestamp 时间戳
     * @param string $nonce 随机字符串
     * @param string $encrypt 密文消息
     */
    public function getSHA1($token, $timestamp, $nonce, $encrypt_msg) {
        // 排序
        try {
            $array = array(
                $encrypt_msg, 
                $token, 
                $timestamp, 
                $nonce);
            sort($array, SORT_STRING);
            $str = implode($array);
            return array(
                ErrorCode::$OK, 
                sha1($str));
        } catch (Exception $e) {
            // print $e . "\n";
            return array(
                ErrorCode::$ComputeSignatureError, 
                null);
        }
    }
}

/**
 * error code 说明. <ul> <li>-40001: 签名验证错误</li> <li>-40002: xml解析失败</li>
 * <li>-40003: sha加密生成签名失败</li> <li>-40004: encodingAesKey 非法</li> <li>-40005:
 * appid 校验错误</li> <li>-40006: aes 加密失败</li> <li>-40007: aes 解密失败</li>
 * <li>-40008: 解密后得到的buffer非法</li> <li>-40009: base64加密失败</li> <li>-40010:
 * base64解密失败</li> <li>-40011: 生成xml失败</li> </ul>
 */
class ErrorCode {

    public static $OK = 0;

    public static $ValidateSignatureError = - 40001;

    public static $ParseXmlError = - 40002;

    public static $ComputeSignatureError = - 40003;

    public static $IllegalAesKey = - 40004;

    public static $ValidateAppidError = - 40005;

    public static $EncryptAESError = - 40006;

    public static $DecryptAESError = - 40007;

    public static $IllegalBuffer = - 40008;

    public static $EncodeBase64Error = - 40009;

    public static $DecodeBase64Error = - 40010;

    public static $GenReturnXmlError = - 40011;
}