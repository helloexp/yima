<?php

class AlipayFwcService {

    protected $_base_req = array();

    protected $token = '';

    public $error = '';

    public $appId;

    public $nodeId;

    public $alipayPublicKey;

    public $setting = array();

    public $as;
    // 加密解密实例
    public $msg_type;
    // 消息流水表
    public $msg_info;
    // 消息流水表
    public $response_id;

    public $_logId;

    public $accessTokenUpdated = false;
    // 初始化
    public function init($appId, $nodeId, $alipayPublicKey = '', 
        $setting = array()) {
        $this->appId = $appId;
        $this->nodeId = $nodeId;
        $this->setting = $setting;
        $this->alipayPublicKey = $alipayPublicKey;
        $this->as = D('AlipaySign', 'Service');
        $this->_logId = mt_rand(10, 99) . time();
    }

    /* 解析请求内容 */
    public function parseRequest($postStr) {
        if (strpos($postStr, '<?xml version="1.0" encoding="gbk"?>') === false)
            $postStr = '<?xml version="1.0" encoding="gbk"?>' . $postStr;
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', 
            LIBXML_NOCDATA);
        $this->log("REQUEST IP：" . $_SERVER['REMOTE_ADDR'], 'INFO');
        $this->log("GET:" . print_r($_GET, true), 'INFO');
        $this->log("POST:" . $postStr, 'INFO');
        
        if (! $postObj)
            return false;
            // 消息类型
        $msgType = trim($postObj->MsgType);
        $respArr = array(
            'AppId' => trim($postObj->AppId), 
            'MsgType' => $msgType, 
            'CreateTime' => trim($postObj->CreateTime), 
            'FromUserId' => trim($postObj->FromUserId), 
            'MsgId' => trim($postObj->MsgId), 
            'UserInfo' => trim($postObj->UserInfo));
        switch ($msgType) {
            case 'text':
                $respArr['Content'] = trim($postObj->Text->Content);
                break;
            case 'image':
                $respArr['MediaId'] = trim($postObj->Image->MediaId);
                $respArr['Format'] = trim($postObj->Image->Format);
                break;
            case 'event':
                $respArr['EventType'] = trim($postObj->EventType);
                $respArr['ActionParam'] = trim($postObj->ActionParam);
                $respArr['AgreementId'] = trim($postObj->AgreementId);
                $respArr['AccountNo'] = trim($postObj->AccountNo);
                break;
            default:
                break;
        }
        return $respArr;
    }

    /* 根据消息体进行处理和回复 */
    public function doResp($req) {
        switch (strtolower($req['MsgType'])) {
            // 这儿是普通事件 关注/取消关注/进入
            case 'event':
                $this->_doEvent($req);
                break;
            // 文本内容
            case 'text':
                $this->msg_type = '0';
                $this->msg_info = $req['Content'];
                $this->saveMsgTrace('0', $req);
                $this->_doText($req);
                $this->saveMsgTrace('1', $req);
                break;
            // 图片信息
            case 'image':
                $biz_content = "{\"mediaId\":\"" . $req['MediaId'] . "\"}";
                $fileName = realpath("UploadAlipayImg") . "/" . $req['MediaId'] .
                     "." . $req['Format'];
                $this->downMediaRequest($biz_content, $fileName);
                
                $this->msg_type = '1';
                $this->msg_info = $fileName;
                $this->saveMsgTrace('0', $req);
                break;
            default:
                echo 'error';
                break;
        }
    }

    /* 生成接收成功回复消息 */
    public function mkAckMsg($toUserId) {
        $response_xml = "<XML><ToUserId><![CDATA[" . $toUserId .
             "]]></ToUserId><AppId><![CDATA[" . $this->appId .
             "]]></AppId><CreateTime>" . time() .
             "</CreateTime><MsgType><![CDATA[ack]]></MsgType></XML>";
        
        $return_xml = $this->as->sign_response($response_xml, 
            C('ALIPAY_FWC.charset'), C('ALIPAY_FWC.merchant_private_key_file'));
        $this->log("response_xml: " . $return_xml);
        return $return_xml;
    }

    /* 处理文本信息 */
    private function _doText($req) {
        // 扫描关键字 优先全匹配
        $where = array(
            'key_words' => $req['Content'], 
            'match_type' => '1', 
            'node_id' => $this->nodeId);
        
        $msg_key = M('tfwc_msgkeywords')->where($where)->find(); // 优先全字匹配并回复第一条
        if (! $msg_key) // 其次包含，模糊匹配
{
            $where = array();
            $where = array(
                'key_words' => array(
                    'like', 
                    '%' . $req['Content'] . '%'), 
                'match_type' => '0', 
                'node_id' => $this->nodeId);
            $msg_key = M('tfwc_msgkeywords')->where($where)->find(); // 模糊匹配并回复第一条
        }
        
        if (! $msg_key) // 最后反向模糊匹配
{
            $where = array();
            $where = array(
                'match_type' => '0', 
                'node_id' => $this->nodeId);
            $key_array = M('tfwc_msgkeywords')->where($where)->getField(
                'id,key_words,message_id', true);
            
            if ($key_array) {
                foreach ($key_array as $key_info) {
                    $pos = strpos($req['Content'], $key_info['key_words']);
                    if ($pos !== false && strlen($key_info['key_words']) >
                         strlen($msg_key['key_words'])) {
                        $msg_key['key_words'] = $key_info['key_words'];
                        $msg_key['message_id'] = $key_info['message_id'];
                    }
                }
            }
        }
        if (! $msg_key) {
            $this->default_msgres($req); // 消息回复
            exit();
        }
        
        // 关键字回复
        $resp_where = array(
            'message_id' => $msg_key['message_id'], 
            'node_id' => $this->nodeId);
        $msg_resp = M('tfwc_msgresponse')->where($resp_where)->find();
        if (! $msg_resp) {
            $this->log("获取消息[" . $msg_key['message_id'] . "]回复失败！", 
                "tfwc_msgresponse error");
            $this->default_msgres($req); // 找不到就直接消息回复
        }
        if ($msg_resp['response_class'] == '0') // 文本回复
{
            $this->msg_type = '0';
            $this->msg_info = $msg_resp['response_info'];
            $resp = array_merge($req, 
                array(
                    'respContent' => $msg_resp['response_info']));
            $result = $this->respText($resp, false);
            $this->log(
                '_doText respText result:' .
                     $result['alipay_mobile_public_message_custom_send_response']['code']);
        } else if ($msg_resp['response_class'] == '1') // 素材回复
{
            $this->msg_type = '2';
            $material_array = $this->material_msgres($msg_resp['response_info']);
            if ($material_array === false) {
                $this->default_msgres($req); // 直接消息回复
            }
            $this->msg_info = $this->JSON($material_array);
            $resp = array_merge($req, $material_array);
            $result = $this->respNews($resp, false);
            $this->log(
                '_doText respNews result:' .
                     $result['alipay_mobile_public_message_custom_send_response']['code']);
        } else // 其他预留 这里依然默认消息回复
{
            $this->log("获取消息[" . $msg_key['message_id'] . "]类型解析失败！", 
                "tfwc_msgresponse error");
            $this->default_msgres($req);
        }
    }

    /*
     * 处理时间信息 关注/取消关注/进入
     */
    private function _doEvent($req) {
        $event = strtolower(trim($req['EventType']));
        // 关注
        if ($event == "follow") {
            $this->msg_type = '0';
            $this->msg_info = '关注';
            $this->saveMsgTrace('2', $req);
            $this->fansAddUpdate($req, 1); // 增加会员
            $this->follow_msgres($req);
        } else if ($event == "enter") // 进入服务窗
{
            // 不做处理
        } else if ($event == "unfollow") // 取消关注
{
            $this->msg_type = '0';
            $this->msg_info = '取消关注';
            $this->fansAddUpdate($req, 0);
            $this->saveMsgTrace('3', $req);
        } else if ($event == "click") // 进入菜单
{
            // ①扫描菜单表
            $where = array(
                'action_param' => $req['ActionParam'], 
                'node_id' => $this->nodeId);
            $menu_info = M('tfwc_menu')->where($where)->find();
            if (! $menu_info) {
                $this->log(
                    "获取菜单[" . $req['ActionParam'] . "]信息失败！input[" .
                         $req['ActionParam'] . "]", "tfwc_menu error");
                return;
            }
            $this->msg_type = '5';
            $this->msg_info = '点击菜单：' . $menu_info['title'];
            $this->saveMsgTrace('5', $req);
            
            // ②回复信息
            if ($menu_info['response_class'] == '0') // 文本回复
{
                $this->msg_type = '0';
                $this->msg_info = $menu_info['response_info'];
                $resp = array_merge($req, 
                    array(
                        'respContent' => $menu_info['response_info']));
                $this->saveMsgTrace('5', $req);
                $result = $this->respText($resp, false);
                $this->log(
                    '_doEvent respText result:' .
                         $result['alipay_mobile_public_message_custom_send_response']['code']);
            } else if ($menu_info['response_class'] == '1') // 素材回复
{
                $material_array = $this->material_msgres(
                    $menu_info['response_info']);
                if ($material_array === false) {
                    $this->log("获取素材[" . $menu_info['response_info'] . "]信息失败！", 
                        "tfwc_matrial error");
                    return; // 直接消息回复
                }
                $this->msg_type = '2';
                $this->msg_info = $this->JSON($material_array);
                $this->saveMsgTrace('5', $req);
                $resp = array_merge($req, $material_array);
                $result = $this->respNews($resp, false);
                $this->log(
                    '_doEvent respNews result:' .
                         $result['alipay_mobile_public_message_custom_send_response']['code']);
            }
        }
    }

    private function default_text($req) {
        $this->msg_type = '0';
        $this->msg_info = '信息整理中';
        $resp = array_merge($req, 
            array(
                'respContent' => '信息整理中'));
        $result = $this->respText($resp, false);
        $this->log(
            'default_text respText result:' .
                 $result['alipay_mobile_public_message_custom_send_response']['code']);
    }

    /* 默认消息回复 */
    private function default_msgres($req) {
        // 先进到特殊流程--------end
        if ($this->setting['msg'] && ($this->setting['msg']['flag'] == '1')) {
            // 计算时间区域
            $week = date('N');
            if (strpos($this->setting['msg']['week'], $week) !== false) {
                $this->log("商户[" . $this->nodeId . "]计算时间区域", 
                    "tfwc_message error");
                if ((date('H:i:s') >= $this->setting['msg']['startTime']) &&
                     ((date('H:i:s') <= $this->setting['msg']['lastTime']))) {
                    if (($this->setting['msg']['minute'] != null) &&
                     ($this->setting['msg']['minute'] > 0)) {
                    $deal_time = date('YmdHis', 
                        time() - $this->setting['msg']['minute'] * 60);
                    // 查看时间段内是否有主动消息回复
                    $where = "open_id='" . $req['FromUserId'] .
                         "' and msg_response_flag='1' and op_user_id is not null and msg_time >='" .
                         $deal_time . "'";
                    $rs = M('tfwc_msg_trace')->where($where)->find();
                    if ($rs === false) {
                        $this->log(
                            "商户[" . $this->nodeId . "]查看时间段内是否有主动消息回复 错误" .
                                 $where, "tfwc_message error");
                        return;
                    } else if ($rs === null) {
                        $this->log(
                            "商户[" . $this->nodeId . "]时间段内无主动消息回复" . $where, 
                            "tfwc_message error");
                    } else {
                        $this->log(
                            "商户[" . $this->nodeId . "]时间段内有主动消息回复" . $where, 
                            "tfwc_message error");
                        return;
                    }
                }
            } else {
                return;
            }
        } else {
            return;
        }
    } else {
        log_write('非关键字回复设置关闭或者未设置' . $this->nodeId);
        return;
    }
    // ①找到消息回复id
    $where = "response_type = '1' and status = '0' and node_id = '" .
         $this->nodeId . "'";
    // echo("(2)".$where);
    $message = M('tfwc_message')->where($where)->find();
    if (! $message) {
        $this->log("商户[" . $this->nodeId . "]默认消息缺失", "tfwc_message error");
        $this->default_text($req);
        exit();
    }
    
    // ②扫描回复表
    $where = "message_id = '" . $message['id'] . "' and node_id = '" .
         $this->nodeId . "'";
    $msg_resp = M('tfwc_msgresponse')->where($where)->find();
    // echo("(3)".$where);
    if (! $msg_resp) {
        $this->log("获取消息[" . $message['id'] . "]回复失败！", 
            "tfwc_msgresponse error");
        $this->default_text($req); // 这里还找不到就直接回复默认文本
        exit();
    }
    if ($msg_resp['response_class'] == '0') // 文本回复
{
        $this->msg_type = '0';
        $this->msg_info = $msg_resp['response_info'];
        $resp = array_merge($req, 
            array(
                'respContent' => $msg_resp['response_info']));
        $result = $this->respText($resp, false);
        $this->log(
            'default_msgres respText result:' .
                 $result['alipay_mobile_public_message_custom_send_response']['code']);
    } else if ($msg_resp['response_class'] == '1') // 素材回复
{
        $material_array = $this->material_msgres($msg_resp['response_info']);
        if ($material_array === false) {
            $this->default_text($req); // 直接消息回复
            exit();
        }
        $this->msg_type = '2';
        $this->msg_info = $this->JSON($material_array);
        $resp = array_merge($req, $material_array);
        $result = $this->respNews($resp, false);
        $this->log(
            'default_msgres respNews result:' .
                 $result['alipay_mobile_public_message_custom_send_response']['code']);
    }
}

/* 关注回复 */
private function follow_msgres($req) {
    // ①找到主动回复id
    $where = "response_type = '0' and status = '0' and node_id = '" .
         $this->nodeId . "'";
    // echo("(2)".$where);
    $message = M('tfwc_message')->where($where)->find();
    if (! $message) {
        $this->log("商户[" . $this->nodeId . "]关注消息缺失", "tfwc_message error");
        // $this->default_text($req);
        exit();
    }
    
    // ②扫描回复表
    $where = "message_id = '" . $message['id'] . "' and node_id = '" .
         $this->nodeId . "'";
    $msg_resp = M('tfwc_msgresponse')->where($where)->find();
    // echo("(3)".$where);
    if (! $msg_resp) {
        $this->log("获取消息[" . $message['id'] . "]回复失败！", 
            "tfwc_msgresponse error");
        // $this->default_text($req);//这里还找不到就直接回复默认文本
        exit();
    }
    if ($msg_resp['response_class'] == '0') // 文本回复
{
        $resp = array_merge($req, 
            array(
                'respContent' => $msg_resp['response_info']));
        $result = $this->respText($resp, false);
        $this->log(
            'follow_msgres respText result:' .
                 $result['alipay_mobile_public_message_custom_send_response']['code']);
    } else if ($msg_resp['response_class'] == '1') // 素材回复
{
        $material_array = $this->material_msgres($msg_resp['response_info']);
        if ($material_array === false) {
            // $this->default_text($req);//直接消息回复
            exit();
        }
        // echo(print_r($material_array));
        // echo(print_r($resp));
        $resp = array_merge($req, $material_array);
        $result = $this->respNews($resp, false);
        $this->log(
            'follow_msgres respNews result:' .
                 $result['alipay_mobile_public_message_custom_send_response']['code']);
    }
}

/* 拼接回复素材 */
private function material_msgres($material_id) {
    $where = array(
        'id' => $material_id, 
        'node_id' => $this->nodeId);
    $material = M('tfwc_material')->where($where)->find();
    if (! $material) {
        // 素材已删除
        $this->log("获取素材[" . $material_id . "]失败！", "material_msgres error");
        return false;
    }
    
    $url = $material['material_link'];
    $img_url = $this->_getImgUrl($material['material_img']);
    $Articles_root = array(
        'actionName' => '立即查看', 
        'desc' => $material['material_summary'], 
        'imageUrl' => $img_url, 
        'title' => $material['material_title'], 
        'url' => htmlspecialchars_decode($url));
    if ($material['material_type'] == '1') // 单图文
{
        return array(
            'articles' => array(
                $Articles_root));
    } else if ($material['material_type'] == '2') // 多图文
{
        $Articles_list = array();
        $Articles_list['0'] = $Articles_root; // 根图文
                                              
        // 获取子图文
        $where2 = array(
            'parent_id' => $material_id, 
            'node_id' => $this->nodeId);
        $material_list = M('tfwc_material')->where($where2)->select();
        $material_count = 0;
        foreach ($material_list as &$materialvo) {
            $material_count ++;
            $img_url = $this->_getImgUrl($materialvo['material_img']);
            
            $url = $materialvo['material_link'];
            $material_detail = array(
                'actionName' => '立即查看', 
                'desc' => $materialvo['material_summary'], 
                'imageUrl' => $img_url, 
                'title' => $materialvo['material_title'], 
                'url' => htmlspecialchars_decode($url));
            $Articles_list[$material_count] = $material_detail;
        }
        return array(
            'articles' => $Articles_list);
    } else {
        // 其它类型待处理
        return false;
    }
}

/*
 * 群发消息处理 $batch_id 群发批次号 $ret 成功返回 array('code'=>200,'msg'=>'成功') 失败返回
 * array('code'=>12004,'msg'=>'批量发送消息频率超限')
 */
public function customSend($batch_id) {
    $this->log('customSend:' . $batch_id);
    if (! $batch_id) {
        $this->log('获取群发批次号失败！群发批次号：' . $batch_id);
        return false;
    }
    $batch_info = M('tfwc_msgbatch')->where(
        array(
            'batch_id' => $batch_id))->find();
    if (! $batch_info) {
        $this->log('获取群发消息内容失败！群发批次号：' . $batch_id);
        return false;
    }
    
    if ($batch_info['status'] != '1') {
        $this->log('群发消息状态非未发送！群发批次号：' . $batch_id);
        return false;
    }
    
    switch ($batch_info['msg_type']) {
        // 文本
        case '0':
            $resp = array(
                'respContent' => $batch_info['msg_info']);
            $result = $this->respText($resp, true);
            $this->log(
                'customSend respText result:' .
                     $result['alipay_mobile_public_message_total_send_response']['code']);
            $this->updateBatchSend($batch_id, 
                $result['alipay_mobile_public_message_total_send_response']);
            return $result['alipay_mobile_public_message_total_send_response'];
            break;
        // 图片
        case '1':
            $this->log('服务窗暂不支持群发图片消息!群发批次号：' . $batch_id);
            break;
        // 图文
        case '2':
            $material_array = $this->material_msgres($batch_info['msg_info']);
            if ($material_array === false) {
                $this->log('群发消息获取图文素材错误！群发批次号：' . $batch_id); // 直接消息回复
                break;
            }
            $resp = $material_array;
            $this->updateBatchMaterial($batch_id, $resp);
            $result = $this->respNews($resp, true);
            $this->log(
                'customSend respNews result:' .
                     $result['alipay_mobile_public_message_total_send_response']['code']);
            $this->updateBatchSend($batch_id, 
                $result['alipay_mobile_public_message_total_send_response']);
            return $result['alipay_mobile_public_message_total_send_response'];
            break;
        default:
            break;
    }
}

/*
 * 单发消息处理 $trace_id 单发流水号 tfwc_msg_trace的id $ret 成功返回
 * array('code'=>200,'msg'=>'成功') 失败返回 array('code'=>12004,'msg'=>'批量发送消息频率超限')
 */
public function directSend($trace_id) {
    $this->log('directSend:' . $trace_id);
    if (! $trace_id) {
        $this->log('获取单发流水号失败！单发流水号：' . $trace_id);
        return false;
    }
    $trace_info = M('tfwc_msg_trace')->where(
        array(
            'id' => $trace_id))->find();
    if (! $trace_info) {
        $this->log('获取单发消息内容失败！单发流水号：' . $trace_id);
        return false;
    }
    
    if ($trace_info['msg_sign'] != '1') {
        $this->log('单发消息类型为非回复！单发流水号：' . $trace_id);
        return false;
    }
    
    if ($trace_info['msg_response_flag'] != '0') {
        $this->log('单发消息状态非未处理！单发流水号：' . $trace_id);
        return false;
    }
    
    switch ($trace_info['msg_type']) {
        // 文本
        case '0':
            $resp = array(
                'respContent' => $trace_info['msg_info'], 
                'FromUserId' => $trace_info['open_id']);
            $result = $this->respText($resp, false);
            $this->log(
                'directSend respText result:' .
                     $result['alipay_mobile_public_message_custom_send_response']['code']);
            $this->UpdateMsgTrace($trace_id);
            return $result['alipay_mobile_public_message_custom_send_response'];
            break;
        // 图片
        case '1':
            $this->log('服务窗暂不支持单发图片消息!单发流水号：' . $trace_id);
            break;
        // 图文
        case '2':
            $material_array = json_decode($trace_info['msg_info'], true);
            if ($material_array === false) {
                $this->log('单发消息获取图文素材错误！单发流水号：' . $trace_id); // 直接消息回复
                break;
            }
            $resp = array_merge($material_array, 
                array(
                    'FromUserId' => $trace_info['open_id']));
            $result = $this->respNews($resp, false);
            $this->log(
                'directSend respNews result:' .
                     $result['alipay_mobile_public_message_custom_send_response']['code']);
            $this->UpdateMsgTrace($trace_id);
            return $result['alipay_mobile_public_message_custom_send_response'];
            break;
        default:
            break;
    }
}

/*
 * 更新批量发送结果
 */
private function UpdateBatchSend($batch_id, $result) {
    $updateArr = array(
        'status' => '2', 
        'fwc_msg_id' => $result['data'], 
        'fwc_resp_status' => $result['code'], 
        'fwc_resp_msg' => $result['msg']);
    $ret = M('tfwc_msgbatch')->where(
        array(
            'batch_id' => $batch_id))->save($updateArr);
    $this->log('tfwc_msgbatch update result:' . $ret);
}

/*
 * 如果是素材的话则更新素材内容
 */
private function updateBatchMaterial($batch_id, $req) {
    $updateArr = array(
        'msg_info' => $this->JSON($req['articles']));
    $ret = M('tfwc_msgbatch')->where(
        array(
            'batch_id' => $batch_id))->save($updateArr);
    $this->log('tfwc_msgbatch update msg_info result:' . $ret);
}

/*
 * 更新单条发送结果
 */
private function UpdateMsgTrace($trace_id) {
    $updateArr = array(
        'msg_response_flag' => '1', 
        'msg_time' => date('YmdHis'));
    $ret = M('tfwc_msg_trace')->where(array(
        'id' => $trace_id))->save($updateArr);
    $this->log('tfwc_msg_trace update result:' . $ret);
}

/*
 * 获取菜单 成功 返回菜单json 失败 返回具体原因
 */
public function getMenu() {
    $result = $this->doMenuSend('', 'get');
    if ($result['alipay_mobile_public_menu_get_response']['code'] == '200')
        return $result['alipay_mobile_public_menu_get_response'];
    else
        return $result['alipay_mobile_public_menu_get_response'];
}

/*
 * 获取菜单
 */
public function addMenu($biz_content) {
    // 校验菜单json
    $ret = $this->checkMenu($biz_content);
    if ($ret['code'] != '0000')
        return $ret['msg'];
    $result = $this->doMenuSend($biz_content, 'add');
    if ($result['alipay_mobile_public_menu_add_response']['code'] == '200')
        return true;
    else
        return $result['alipay_mobile_public_menu_add_response']['msg'];
}

/*
 * 获取菜单
 */
public function updateMenu($biz_content) {
    // 校验菜单json
    $ret = $this->checkMenu($biz_content);
    if ($ret['code'] != '0000')
        return $ret['msg'];
    $result = $this->doMenuSend($biz_content, 'update');
    if ($result['alipay_mobile_public_menu_update_response']['code'] == '200')
        return true;
    else
        return $result['alipay_mobile_public_menu_update_response']['msg'];
}

/* 对菜单json进行校验 */
private function checkMenu($biz_content) {
    $menu_arr = json_decode($biz_content, true);
    // 1级菜单个数
    $count1 = count($menu_arr['button']);
    if ($count1 > 4 || $count1 < 1)
        return array(
            'code' => '0001', 
            'msg' => '一级菜单个数为1-4个');
        // 校验2级菜单
    foreach ($menu_arr['button'] as $v) {
        if (isset($v['subButton']) || ! empty($v['subButton'])) {
            $count2 = count($v['subButton']);
            if ($count2 < 1 || $count2 > 5)
                return array(
                    'code' => '0002', 
                    'msg' => '二级菜单个数为1-5个');
        }
    }
    return array(
        'code' => '0000', 
        'msg' => '成功');
}

/* 这儿响应不同处理方式 */
/*
 * 回复文本 $customFlag 群发标志 true群发 false 单发
 */
private function respText($resp, $customFlag = false) {
    log_write($resp['respContent']);
    // 获取需要回复的biz_content
    $text_msg = $this->mkTextMsg($resp['respContent']);
    $biz_content = $this->mkTextBizContent($resp['FromUserId'], $text_msg);
    // 转码
    $biz_content = iconv("UTF-8", "GBK//IGNORE", $biz_content);
    
    if ($customFlag)
        return $this->doCustomSend($biz_content);
    else
        return $this->doSend($biz_content);
}

// 纯文本消息
private function mkTextMsg($content) {
    $text = array(
        'content' => $content);
    return $text;
}

/**
 * 返回纯文本消息的biz_content
 *
 * @param unknown $toUserId
 * @param unknown $text
 * @return string
 */
private function mkTextBizContent($toUserId, $text) {
    $biz_content = array(
        'msgType' => 'text', 
        'text' => $text);
    return $this->toBizContentJson($biz_content, $toUserId);
}

/*
 * 回复图文 $customFlag 群发标志 true群发 false 单发
 */
private function respNews($resp, $customFlag = false) {
    // 获取需要回复的biz_content
    $articles = $this->mkImageTextMsg($resp['articles']);
    $biz_content = $this->mkImageTextBizContent($resp['FromUserId'], $articles);
    // 转码
    // $biz_content = iconv ( "UTF-8", "GBK//IGNORE", $biz_content );
    if ($customFlag)
        return $this->doCustomSend($biz_content);
    else
        return $this->doSend($biz_content);
}

// 图文消息，
// $authType=loginAuth时，用户点击链接会将带有auth_code，可以换取用户信息
public function mkImageTextMsg($articles, $authType = NULL) {
    $articles_arr = array();
    foreach ($articles as $k => $v) {
        $articles_arr[$k] = array(
            'actionName' => iconv("UTF-8", "GBK", $v['actionName']), 
            'desc' => iconv("UTF-8", "GBK", $v['desc']), 
            'imageUrl' => $v['imageUrl'], 
            'title' => iconv("UTF-8", "GBK", $v['title']), 
            'url' => $v['url'], 
            'authType' => $authType);
    }
    return $articles_arr;
}

/**
 * 返回图文消息的biz_content
 *
 * @param string $toUserId
 * @param array $articles
 * @return string
 */
public function mkImageTextBizContent($toUserId, $articles) {
    $biz_content = array(
        'msgType' => 'image-text', 
        'createTime' => time(), 
        'articles' => $articles);
    return $this->toBizContentJson($biz_content, $toUserId);
}

/* 校验字串是否有效 */
public function valid($data) {
    $sign = $data['sign'];
    $sign_type = $data['sign_type'];
    $biz_content = $data['biz_content'];
    $service = $data['service'];
    $charset = $data['charset'];
    
    if (empty($sign) || empty($sign_type) || empty($biz_content) ||
         empty($service) || empty($charset))
        return false;
    else
        return true;
}

/* 验签 */
public function verify_sign($param, $rsaPublicKeyFilePath) {
    return $this->as->rsaCheckV2($param, $rsaPublicKeyFilePath);
}

/*
 * 会员信息新增修改 status 0 取消关注 1关注
 */
private function fansAddUpdate($req, $status) {
    $where = array(
        'openid' => $req['FromUserId']);
    $rs = M('tfwc_user')->where($where)->find();
    if (! $rs) // 新关注
{
        $user_arr = json_decode($req['UserInfo'], true);
        $userInfo = array(
            'openid' => $req['FromUserId'], 
            'login_id' => $user_arr['logon_id'], 
            'real_name' => $user_arr['user_name'], 
            'subscribe_status' => $status, 
            'subscribe_time' => date('YmdHis'), 
            'app_id' => $this->appId, 
            'node_id' => $this->nodeId);
        
        $ret = M('tfwc_user')->add($userInfo);
        if ($ret)
            $this->log(
                '新增粉丝成功,OPENID【' . $req['FromUserId'] . '】粉丝ID【' . $ret . '】');
        else
            $this->log('新增粉丝失败,OPENID【' . $req['FromUserId'] . '】');
    } else // 旧用户取消关注后再关注
{
        $data = array(
            'subscribe_status' => $status, 
            'subscribe_time' => date('YmdHis'));
        $ret = M('tfwc_user')->where(
            array(
                'id' => $rs['id'], 
                'openid' => $req['FromUserId']))->save($data);
        if ($ret !== false)
            $this->log(
                '更新粉丝成功,OPENID【' . $req['FromUserId'] . '】粉丝ID【' . $rs['id'] .
                     '】状态【' . $status . '】');
        else
            $this->log(
                '更新粉丝失败,OPENID【' . $req['FromUserId'] . '】粉丝ID【' . $rs['id'] .
                     '】状态【' . $status . '】');
    }
}

/*
 * 网关校验回复函数 $biz_content 支付宝网关发送的POST消息中biz_content的内容 $flag 请求参数签名状态 默认false
 */
public function verifygw($req, $flag = false) {
    if ($req['EventType'] == "verifygw") {
        if ($flag) {
            $response_xml = "<success>true</success><biz_content>" .
                 $this->getPublicKeyStr(
                    C('ALIPAY_FWC.merchant_public_key_file')) . "</biz_content>";
        } else {
            $response_xml = "<success>false</success><error_code>VERIFY_FAILED</error_code><biz_content>" .
                 $this->getPublicKeyStr(
                    C('ALIPAY_FWC.merchant_public_key_file')) . "</biz_content>";
        }
        $return_xml = $this->as->sign_response($response_xml, 
            C('ALIPAY_FWC.charset'), C('ALIPAY_FWC.merchant_private_key_file'));
        $this->log("response_xml: " . $return_xml);
        echo $return_xml;
        exit();
    }
}

/*
 * 保存消息流水 $msg_sign 消息标志 0-接收 1-回复 2-关注 3-取消关注 $req biz_content解析出来的array
 */
private function saveMsgTrace($msg_sign, $req) {
    $msg_trace = array();
    $msg_trace['msg_sign'] = $msg_sign;
    $msg_trace['msg_id'] = $req['MsgId'];
    $msg_trace['msg_type'] = $this->msg_type;
    $msg_trace['msg_info'] = $this->msg_info;
    $msg_trace['msg_time'] = date("YmdHis");
    $msg_trace['msg_response_flag'] = '0'; // 接收类
    $msg_trace['node_id'] = $this->nodeId;
    $msg_trace['app_id'] = $this->appId;
    $msg_trace['open_id'] = $req['FromUserId'];
    
    if ($msg_sign == '1') // 回复类(以下有共性的信息不再抽取，方便阅读)
{
        $msg_trace['msg_response_flag'] = '1'; // 接口 回复类默认为已处理
        $msg_trace['response_id'] = $this->response_id; // 自动回复记录回复消息id
    } else if ($msg_sign == '2') // 关注类
{
        $msg_trace['msg_response_flag'] = '1'; // 关注类默认为已处理
    } else if ($msg_sign == '3') // 取消关注类
{
        $msg_trace['msg_response_flag'] = '1'; // 取消类默认为已处理
    }
    
    $rs = M('tfwc_msg_trace')->add($msg_trace);
    if ($rs === false) {
        $this->log(print_r($msg_trace, true));
        $this->log("记录流水信息[tfwc_msg_trace]失败");
        return false;
    }
    
    if ($msg_sign == '1') // 回复类 要更新回复源信息状态
{
        $where = "id = '" . $this->response_id . "'";
        $ori_msg_trace = array();
        $ori_msg_trace['msg_response_flag'] = '2'; // 接口回复默认为自动回复
        $rs = M('tfwc_msg_trace')->where($where)->save($ori_msg_trace);
    }
    $this->response_id = $rs;
}

/**
 * 下载用户发送过来的图片
 *
 * @param unknown $biz_content
 * @param unknown $fileName
 */
private function downMediaRequest($biz_content, $fileName) {
    date_default_timezone_set(PRC);
    $paramsArray = array(
        'method' => "alipay.mobile.public.multimedia.download", 
        'biz_content' => $biz_content, 
        'charset' => C('ALIPAY_FWC.charset'), 
        'sign_type' => 'RSA', 
        'app_id' => $this->appId, 
        'timestamp' => date('Y-m-d H:i:s', time()), 
        'version' => "1.0");
    // echo $biz_content;
    
    // print_r($paramsArray);
    $sign = $this->as->sign_request($paramsArray, 
        C('ALIPAY_FWC.merchant_private_key_file'));
    $paramsArray['sign'] = $sign;
    // $url=$this->as->getSignContent ( $paramsArray );
    $url = "https://openfile.alipay.com/chat/multimedia.do?";
    foreach ($paramsArray as $key => $value) {
        $url .= "$key=" . urlencode($value) . "&";
    }
    
    // print_r ( $url );F
    // 日志记录下受到的请求
    writeLog("请求图片地址：" . $url);
    file_put_contents($fileName, file_get_contents($url));
}

private function toBizContentJson($biz_content, $toUserId) {
    // 如果toUserId为空，则是发给所有关注的而用户，且不可删除，慎用
    if (isset($toUserId) && ! empty($toUserId)) {
        $biz_content['toUserId'] = $toUserId;
    }
    
    $content = $this->JSON($biz_content);
    return $content;
}

/**
 * ************************************************************
 * 使用特定function对数组中所有元素做处理
 *
 * @param string &$array 要处理的字符串
 * @param string $function 要执行的函数
 * @return boolean $apply_to_keys_also 是否也应用到key上
 * @access public ***********************************************************
 */
protected function arrayRecursive(&$array, $function, 
    $apply_to_keys_also = false) {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $this->arrayRecursive($array[$key], $function, $apply_to_keys_also);
        } else {
            $array[$key] = $function($value);
        }
        
        if ($apply_to_keys_also && is_string($key)) {
            $new_key = $function($key);
            if ($new_key != $key) {
                $array[$new_key] = $array[$key];
                unset($array[$key]);
            }
        }
    }
}

/**
 * ************************************************************
 * 将数组转换为JSON字符串（兼容中文）
 *
 * @param array $array 要转换的数组
 * @return string 转换得到的json字符串
 * @access public ***********************************************************
 */
protected function JSON($array) {
    $this->arrayRecursive($array, 'urlencode', true);
    $json = json_encode($array);
    return urldecode($json);
}

/* 调用SDK执行单发发送接口 */
private function doSend($biz_content) {
    $this->log('doSend:' . $biz_content);
    require_once (APP_PATH .
         'Lib/Service/aop/request/AlipayMobilePublicMessageCustomSendRequest.php');
    require_once (APP_PATH . 'Lib/Service/aop/AopClient.php');
    // 发送参数类
    $custom_send = new AlipayMobilePublicMessageCustomSendRequest();
    $custom_send->setBizContent($biz_content);
    // 使用SDK执行接口请求
    $aop = new AopClient();
    $aop->gatewayUrl = C('ALIPAY_FWC.gatewayUrl');
    $aop->appId = $this->appId;
    $aop->rsaPrivateKeyFilePath = C('ALIPAY_FWC.merchant_private_key_file');
    $aop->apiVersion = "1.0";
    $result = $aop->execute($custom_send, '');
    $this->log("response: " . print_r($result, true));
    return $result;
}

/* 调用SDK执行群发发送接口 */
private function doCustomSend($biz_content) {
    $this->log('doCustomSend:' . $biz_content);
    require_once (APP_PATH .
         'Lib/Service/aop/request/AlipayMobilePublicMessageTotalSendRequest.php');
    require_once (APP_PATH . 'Lib/Service/aop/AopClient.php');
    // 发送参数类
    $custom_send = new AlipayMobilePublicMessageTotalSendRequest();
    $custom_send->setBizContent($biz_content);
    // 使用SDK执行接口请求
    $aop = new AopClient();
    $aop->gatewayUrl = C('ALIPAY_FWC.gatewayUrl');
    $aop->appId = $this->appId;
    $aop->rsaPrivateKeyFilePath = C('ALIPAY_FWC.merchant_private_key_file');
    $aop->apiVersion = "1.0";
    $result = $aop->execute($custom_send, '');
    $this->log("response: " . print_r($result, true));
    return $result;
}

/*
 * 调用SDK执行获取菜单接口 $action get 获取菜单 add新增菜单 update 更新菜单
 */
private function doMenuSend($biz_content, $action) {
    $this->log('doMenuSend:' . $biz_content);
    $biz_content = iconv("UTF-8", "GBK//IGNORE", $biz_content);
    require_once (APP_PATH .
         'Lib/Service/aop/request/AlipayMobilePublicMenuUpdateRequest.php');
    require_once (APP_PATH .
         'Lib/Service/aop/request/AlipayMobilePublicMenuAddRequest.php');
    require_once (APP_PATH .
         'Lib/Service/aop/request/AlipayMobilePublicMenuGetRequest.php');
    require_once (APP_PATH . 'Lib/Service/aop/AopClient.php');
    // 发送参数类
    switch (strtolower($action)) {
        case 'get':
            $custom_send = new AlipayMobilePublicMenuGetRequest();
            break;
        case 'add':
            $custom_send = new AlipayMobilePublicMenuAddRequest();
            $custom_send->setBizContent($biz_content);
            break;
        case 'update':
            $custom_send = new AlipayMobilePublicMenuUpdateRequest();
            $custom_send->setBizContent($biz_content);
            break;
        default:
            $this->log('菜单操作类型错误:' . $actions);
            return false;
            break;
    }
    
    // 使用SDK执行接口请求
    $aop = new AopClient();
    $aop->gatewayUrl = C('ALIPAY_FWC.gatewayUrl');
    $aop->appId = $this->appId;
    $aop->rsaPrivateKeyFilePath = C('ALIPAY_FWC.merchant_private_key_file');
    $aop->apiVersion = "1.0";
    $result = $aop->execute($custom_send, '');
    $this->log("response: " . print_r($result, true));
    return $result;
}

/* 获取商户公钥字符串 */
protected function getPublicKeyStr($pub_pem_path) {
    $content = file_get_contents($pub_pem_path);
    $content = str_replace("-----BEGIN PUBLIC KEY-----", "", $content);
    $content = str_replace("-----END PUBLIC KEY-----", "", $content);
    $content = str_replace("\r", "", $content);
    $content = str_replace("\n", "", $content);
    return $content;
}

// 记录日志
protected function log($msg, $level = Log::INFO) {
    // trace('Log.'.$level.':'.$msg);
    log_write($msg, '[' . _APP_PID_ . ']' . $level);
}

// 获取图片路径
private function _getImgUrl($imgname) {
    $config = C('WeixinServ');
    $img_upload_path = $config['IMG_URL']; // 设置附件上传目录
                                           // 旧版
    if (basename($imgname) == $imgname) {
        return $img_upload_path . $imgname;
    } else {
        return get_upload_url($imgname);
    }
}

// 上传图片
public function uploadMediaFile($file) {
    $i = 1;
    if (! $this->accessToken) {
        $this->accessToken = $this->getAccessToken($this->appId, 
            $this->appSecret);
    }
    while ($i ++ < 3) {
        $apiUrl = 'http://file.api.weixin.qq.com/cgi-bin/media/upload?access_token=' .
             $this->accessToken . '&type=thumb';
        $result_str = $this->https_request($apiUrl, 
            array(
                "media" => "@" . $file));
        if (! $result_str) {
            throw_exception('调用接口失败' . $this->error . ' file:' . $file);
        }
        Log::write($result_str, 'weixin_groupQuery i=' . $i);
        $result = json_decode($result_str, true);
        // 如果验证码超时，或者失效
        if ($result['errcode'] == '40001' || $result['errcode'] == '42001') {
            $resultToken = $this->getAccessToken($this->appId, $this->appSecret);
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
    if (! $media_id) {
        throw_exception('上传多媒体文件失败！' . $result_str);
    }
    return $media_id;
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
    Log::write($msg, 'log_id[' . $this->_logId . ']：');
}
}