<?php

/*
 * 指令接口服务设置 author:郑晓华
 */
class IndexAction extends BaseAction {

    public $wx;

    public $node_id;

    public $req;

    public $token;

    public $access_token;

    public $app_id;

    public $app_secret;

    public $user_name;

    public $response_msg_id;

    public $node_wx_id;

    public $scene_id;

    public $msg_type;

    public $msg_info;

    public $service_flag;

    //场景值
    public $outer_id = '';
    // 多客服开关
    /*
     * {"location":{"location_flag":"1","resp_count":"3","large_image":"00004488top.jpg","small_image":"00004488item.jpg"}}
     */
    public $setting = array();

    public function _initialize() {
        error_reporting(0);
        C('WeixinServ', require (CONF_PATH . 'configWeixinServ.php'));
        if (C('WeixinServ.LOG_PATH'))
            C('LOG_PATH', C('WeixinServ.LOG_PATH')); // 重新定义目志目录
        $this->node_id = I('GET.node_id'); // 商户id
        
        C('CUSTOM_LOG_PATH', C('LOG_PATH') . $this->node_id . '_');
        C('LOG_PATH', C('LOG_PATH') . '' . $this->node_id . '_');
        if (! $this->node_id) {
            echo "商户id不能为空";
            exit();
        }
    }

    /* 入口函数 */
    public function index() {
        $this->wx = $wx = D('WeiXin', 'Service');
        $weixin_info = M('TweixinInfo')->where(
            "node_id='" . $this->node_id . "'")->find();
        $this->log(var_export($weixin_info, true));
        $this->token = $weixin_info['token'];
        $this->access_token = $weixin_info['app_access_token'];
        $this->app_id = $weixin_info['app_id'];
        $this->app_secret = $weixin_info['app_secret'];
        $this->service_flag = $weixin_info['service_flag'];
        $this->wx->init($this->app_id, $this->app_secret, $this->access_token);
        if ($weixin_info['setting']) {
            $this->setting = json_decode($weixin_info['setting'], true) or
                 $this->setting = array();
        }
        $wx->setToken($this->token);
        // 校验签名
        if (! $wx->checkSignature()) {
            if ($_REQUEST['ischecked'] == 'ischecked') {} else
                die('签名错误');
        }
        $wx->valid();

        // 开始解析指令
        $this->req = $wx->parseRequest();
        $this->log("into weixin command:");
        $this->log(var_export($this->req, true));
        $this->user_name = $this->req['fromUserName'];
        $this->node_wx_id = $this->req['toUserName'];
        if (($weixin_info['getfans_flag'] == '0') &&
             ($this->req['Content'] === '2152393771')) {
            set_time_limit(0);
            $this->getFansList();
        }
        // //00018419 生产平台cpyfy@imageco.com.cn机构号
        // $yima_wexin_node_id = C('WeixinServ.YIMA_WEIXIN_NODE_ID');
        // if ($this->node_id == $yima_wexin_node_id ){
        // $this->wx->respTransferCustomerService(array());
        // exit;
        // }

        //微信多客服事件监听
        $res=D('WeiXinKf')->KfEvent($this->req,$this->node_id);
        if($res){die('');}
        if ($weixin_info['node_wx_id'] != $this->node_wx_id) {
            M('TweixinInfo')->where("node_id='" . $this->node_id . "'")->save(
                array(
                    'node_wx_id' => $this->node_wx_id));
            $this->log(
                "微信号更新：[" . $weixin_info['node_wx_id'] . "]-[" .
                     $this->node_wx_id . "]");
        }
        switch (strtolower($this->req['msgType'])) {
            // 这儿是普通事件
            case 'event':
                echo $this->_doEvent($this->req);
                break;
            // 指令
            case 'text':
                $this->msg_type = '0';
                $this->msg_info = $this->req['Content'];
                $this->saveMsgTrace('0');
                $respStr = $this->_doKfText($this->req);
                if(!$respStr){
                     $respStr = $this->_doText($this->req);
                }
                $this->saveMsgTrace('1');
                echo $respStr;
                break;
            case 'image':
                $this->msg_type = '1';
                $this->msg_info = $this->req['PicUrl'];
                $this->saveMsgTrace('0');
                // 判断是否转向多客服
                $this->customer_service_checkrsp();
                break;
            case 'video':
                $this->msg_type = '8';
                $this->msg_info = $this->req['MediaId'];
                $this->saveMsgTrace('0');
                // 判断是否转向多客服
                $this->customer_service_checkrsp();
                break;
            case 'shortvideo':
                $this->msg_type = '9';
                $this->msg_info = $this->req['MediaId'];
                $this->saveMsgTrace('0');
                // 判断是否转向多客服
                $this->customer_service_checkrsp();
                break;
            case 'location':
                $this->msg_type = '4';
                $this->msg_info = $this->req['Location_X'] . "|" .
                     $this->req['Location_Y'] . "|" . $this->req['Label'];
                $this->saveMsgTrace('0');
                $respStr = $this->_doLocation($this->req);
                Log_write('response:' . $respStr);
                $this->saveMsgTrace('1');
                echo $respStr;
                break;
            case 'link':
                // 判断是否转向多客服
                $this->customer_service_checkrsp();
                break;
            case 'voice':
                $this->msg_type = '3';
                $this->msg_info = $this->req['MediaId'];
                $this->saveMsgTrace('0');
                // 判断是否转向多客服
                $this->customer_service_checkrsp();
                break;
            case 'news':
                // 判断是否转向多客服
                $this->customer_service_checkrsp();
                break;
            default:
                // 判断是否转向多客服
                $this->customer_service_checkrsp();
                echo 'error';
                exit();
        }
    }
    
    // 判断多客服处理开关是否打开 转向多客服 $this->customer_service_checkrsp();
    private function customer_service_checkrsp() {
        if ($this->service_flag == 1) {
            $this->wx->respTransferCustomerService(array());
            exit();
        }
    }
    
    // 素材，消息不完整时回复默认文本
    private function deafault_text() {
        // 载入扩展
        $param = array(
            'default_text', 
            $this);
        B('WeixinServ', $param); // 添加微信默认回复的处理
        exit();
        $resp = array_merge($this->req, 
            array(
                'Content' => '感谢关注，信息整理中...'));
        $this->wx->respText($resp);
    }
    
    // 扫码回复事件
    private function scan_msgres() {
        $this->wx = $wx = D('WeiXin', 'Service');
        // 设置过响应事件
        if ($this->scene_id) {
            // $msgId = M('twx_message')->where(array('scene_id' =>
            // $this->scene_id))->getField('id');
            // $msgRe = M('twx_msgresponse')->where(array('message_id' =>
            // $msgId))->find();
            
            $where = "scene_id = '" . $this->scene_id .
                 "' and response_type = '4' and status = '0' and node_id = '" .
                 $this->node_id . "'";
            // $where = "response_type = '0' and status = '0' and node_id = '" .
            // $this->node_id . "'";
            
            $message = M('TwxMessage')->where($where)->find();
            log_write("scan_msgres>TwxMessage:" . M()->_sql());
            if (! $message) {
                $this->log(M()->_sql() . "商户[" . $this->node_id . "]扫码消息缺失", 
                    "twx_message error");
                $this->log(var_export($message, true));
                $this->deafault_text();
            }
            
            // ②扫描回复表
            $where2 = "message_id = '" . $message['id'] . "' and node_id = '" .
                 $this->node_id . "'";
            $msg_resp = M('TwxMsgresponse')->where($where2)->find();
            log_write("msg_resp:" . var_export($msg_resp, true));
            
            // echo("(3)".$where);
            if (! $msg_resp) {
                $this->log("获取消息[" . $message['id'] . "]回复失败！", 
                    "twx_msgresponse error");
                $this->deafault_text(); // 这里还找不到就直接回复默认文本
            }
            if ($msg_resp['response_class'] == '0') { // 文本回复
                $resp = array_merge($this->req, 
                    array(
                        'Content' => $msg_resp['response_info']));
                $this->wx->respText($resp);
                log_write(var_export($resp, true));
            } else if ($msg_resp['response_class'] == '1') { // 素材回复
                $material_array = $this->material_msgres(
                    $msg_resp['response_info']);
                if ($material_array === false) {
                    $this->deafault_text(); // 直接消息回复
                }
                $resp = array_merge($this->req, $material_array);
                log_write(var_export($resp, true));
                $this->wx->respNews($resp);
            } else if ($msg_resp['response_class'] == '3') { // 图片回复
                log_write("msg_resp" . var_export($msg_resp, true));
                if ($msg_resp['media_id']) {
                    $mediaId = $msg_resp['media_id'];
                } else {
                    $mediaId = $this->wx->uploadMediaFile2(
                        $msg_resp['response_info']);
                    // 更新mediaId到twx_msgresponse(作用：根据mediaId直接展示图片，不用每次都调用上传图片接口)
                    if ($mediaId) {
                        M('TwxMsgresponse')->where($where2)->save(
                            array(
                                'media_id' => $mediaId));
                    }
                }
                
                $resp = array_merge($this->req, 
                    array(
                        'mediaId' => $mediaId));
                $this->wx->respImage($resp);
                log_write("xml:" . var_export($resp));
            } else if ($msg_resp['response_class'] == '4') { // 卡券回复
                $this->msg_type = '6';
                $result = $this->card_msgres($msg_resp['response_info']);
                log_write($msg_resp['response_info'], 'card_id');
                if ($result === false) {
                    $this->default_msgres(); // 直接消息回复
                }
            }
        }
    }

    /* 关注回复 */
    private function subscribe_msgres() {
        log_write('关注');
        $this->wx = $wx = D('WeiXin', 'Service');
        // ①找到主动回复id
        $where = "a.response_type in ('0','7')  and a.status = '0' and a.node_id = '" .
             $this->node_id . "'";
        $message = M('TwxMessage')->where($where)->field(' a.*,b.response_info')->join(' a LEFT JOIN twx_msgresponse b ON a.id=b.message_id')->find();
        if (! $message) {
            $this->log("商户[" . $this->node_id . "]关注消息缺失".M()->_sql(), "twx_message error");
            $this->deafault_text();
        }
        //如果是自动回复并且没有内容的时候就不发消息了
        if($message['response_type'] == '0' && empty($message['response_info'])){
            $this->log("商户[" . $this->node_id . "]: No Send Message!");
            exit;
        }

        if($message['response_type'] == '7') { // 呼朋引伴活动
            //            if (! $this->scene_id) {
            M('twx_message')->where('id=' . $message['id'])->setInc(
                    'focus_num'
            );
            //            }
            $this->msg_type = '6';

            $batchData = M('tbatch_channel')->field(' a.id,b.join_rule')->join(' a inner join tbatch_info b on a.batch_id=b.m_id')->where(
                    array('a.node_id' => $this->node_id, 'a.batch_id' => $message['m_id'])
            )->find();
            //文本消息
            $resp = array_merge(
                    $this->req,
                    array(
                            'Content' => $batchData['join_rule']
                    )
            );
            $this->wx->respText($resp);

            log_write('batch_channel  ID is :' . $batchData['id']);
            import('@.Vendor.ChouJiang');
            $ChouJiang = new ChouJiang($batchData['id'], '', null, null, array('wx_open_id' => $this->user_name));
            $result    = $ChouJiang->send_code();
            log_write(' is chouJiang return: ' . var_export($result, true));
            if ($result['resp_id'] == '0000') {
                if (empty($result['card_id'])) {        //红包
                    M()->startTrans();
                    $request_id = $this->award_reqid();
                    $goods_id   = M('tgoods_info')->field('a.*')->join(
                            ' a inner join tcj_batch b on a.goods_id=b.goods_id '
                    )->where(array('a.node_id' => $this->node_id, 'b.id' => $result['rule_id']))->find();
                    $trace_data = array(
                            'message_id' => $message['id'],
                            'node_id'    => $this->node_id,
                            'open_id'    => $this->user_name,
                            'card_id'    => $goods_id['goods_id'],
                            'add_time'   => date('YmdHis'),
                            'request_id' => $request_id,
                            'type'       => '1'
                    );
                    log_write(__METHOD__ . '$trace_data:' . var_export($trace_data, true));
                    $result = M('twx_msgkwd_trace')->add($trace_data);
                    if ($result) {
                        //卡券领取+1
                        M('twx_message')->where('id=' . $message['id'])->setInc('receive_num');
                        M()->commit();
                    } else {
                        M()->rollback();
                        log_write("进入发送流水表[twx_msgkwd_trace]失败" . M()->_sql());
                    }
                } else {                                //卡券
                    $this->outer_id = $message['m_id'];
                    M('twx_card_type')->where("card_id = '" . $result['card_id'] . "'")->setDec('card_get_num');
                    $this->card_msgres($result['card_id'], $message['id']);
                }
            }
            if ($result === false) {
                $this->deafault_text(); // 直接消息回复
            }
            exit;
        }

        // ②扫描回复表
        $where2 = "message_id = '" . $message['id'] . "' and node_id = '" .
             $this->node_id . "'";
        $msg_resp = M('TwxMsgresponse')->where($where2)->find();
        log_write("msg_resp:" . var_export($msg_resp, true));
        if (! $msg_resp) {
            $this->log("获取消息[" . $message['id'] . "]回复失败！", 
                "twx_msgresponse error");
            $this->deafault_text(); // 这里还找不到就直接回复默认文本
        }
        if ($msg_resp['response_class'] == '0') { // 文本回复
            $resp = array_merge($this->req, 
                array(
                    'Content' => $msg_resp['response_info']));
            $this->wx->respText($resp);
        } else if ($msg_resp['response_class'] == '1') { // 素材回复
            $material_array = $this->material_msgres($msg_resp['response_info']);
            if ($material_array === false) {
                $this->deafault_text(); // 直接消息回复
            }
            $resp = array_merge($this->req, $material_array);
            log_write(var_export($resp, true));
            $this->wx->respNews($resp);
        } else if ($msg_resp['response_class'] == '3') { // 图片回复
            log_write("msg_resp" . var_export($msg_resp, true));
            if ($msg_resp['media_id']) {
                $mediaId = $msg_resp['media_id'];
            } else {
                $mediaId = $this->wx->uploadMediaFile2(
                    $msg_resp['response_info']);
                // 更新mediaId到twx_msgresponse(作用：根据mediaId直接展示图片，不用每次都调用上传图片接口)
                if ($mediaId) {
                    M('TwxMsgresponse')->where($where2)->save(
                        array(
                            'media_id' => $mediaId));
                }
            }
            
            $resp = array_merge($this->req, 
                array(
                    'mediaId' => $mediaId));
            $this->wx->respImage($resp);
            log_write("xml:" . var_export($resp));
        } else if ($msg_resp['response_class'] == '4') { // 卡券回复
            $this->msg_type = '6';
            $result = $this->card_msgres($msg_resp['response_info']);
            log_write($msg_resp['response_info'], 'card_id');
            if ($result === false) {
                $this->default_msgres(); // 直接消息回复
            }
        }else{
            $this->deafault_text(); // 直接消息回复
        }
    }

    /* 消息回复 */
    private function default_msgres() {
        // 先进到特殊流程--------
        $param = array(
            'default_text', 
            $this);
        B('WeixinServ', $param);
        // 先进到特殊流程--------end

        /*
         * 从此以后不再有时间限制了
        if ($this->setting['msg']) {
            log_write(var_export($this->setting['msg'], true), "msg");
            // 计算时间区域
            $week = date('N');
            if (strpos($this->setting['msg']['week'], $week) !== false) {
                $this->log("商户[" . $this->node_id . "]计算时间区域", 
                    "twx_message error");
                if ((date('H:i:s') >= $this->setting['msg']['startTime']) &&
                     ((date('H:i:s') <= $this->setting['msg']['lastTime']))) {
                    if ((!empty($this->setting['msg']['minute'])) &&
                     ($this->setting['msg']['minute'] > 0)) {
                        $deal_time = date('YmdHis', 
                            time() - $this->setting['msg']['minute'] * 60);
                        // 查看时间段内是否有主动消息回复
                        $where = "wx_id='" . $this->user_name .
                             "' and msg_response_flag='1' and op_user_id is not null and msg_time >='" .
                             $deal_time . "'";
                        $rs = M('twx_msg_trace')->where($where)->find();
                        if ($rs === false) {
                            $this->log(
                                "商户[" . $this->node_id . "]查看时间段内是否有主动消息回复 错误" .
                                     $where, "twx_message error");
                            exit();
                        } else if ($rs === null) {
                            $this->log(
                                "商户[" . $this->node_id . "]时间段内无主动消息回复" . $where, 
                                "twx_message error");
                        } else {
                            $this->log(
                                "商户[" . $this->node_id . "]时间段内有主动消息回复" . $where, 
                                "twx_message error");
                            exit();
                        }
                    }
                } else {
                    exit();
                }
            } else {
                exit();
            }
        }
        */

    // ①找到消息回复id
    $where = "a.response_type = '1' and a.status = '0' and a.node_id = '" .
         $this->node_id . "'";
    // echo("(2)".$where);
    $message = M('TwxMessage')->field(' a.*,b.response_info')->join(' a LEFT JOIN twx_msgresponse b ON a.id=b.message_id')->where($where)->find();
    if (! $message) {
        $this->log("商户[" . $this->node_id . "]默认消息缺失", "twx_message error");
        $this->deafault_text();
    }
    //如果是自动回复并且没有内容的时候就不发消息了
    if($message['response_type'] == '1' && empty($message['response_info'])){
        $this->log("商户[" . $this->node_id . "]: No Send Message!");
        exit;
    }
    // ②扫描回复表
    $where2 = "message_id = '" . $message['id'] . "' and node_id = '" .
         $this->node_id . "'";
    $msg_resp = M('TwxMsgresponse')->where($where2)->find();
    // echo("(3)".$where);
    log_write("msg_resp:" . var_export($msg_resp, true));
    if (! $msg_resp) {
        $this->log("获取消息[" . $message['id'] . "]回复失败！", "twx_msgresponse error");
        $this->deafault_text(); // 这里还找不到就直接回复默认文本
    }
    if ($msg_resp['response_class'] == '0') { // 文本回复
        $resp = array_merge($this->req, 
            array(
                'Content' => $msg_resp['response_info']));
        $this->wx->respText($resp);
    } else if ($msg_resp['response_class'] == '1') { // 素材回复
        $material_array = $this->material_msgres($msg_resp['response_info']);
        if ($material_array === false) {
            $this->deafault_text(); // 直接消息回复
        }
        $resp = array_merge($this->req, $material_array);
        log_write("resp:" . var_export($resp));
        // echo(var_export($material_array));
        // echo(var_export($resp));
        $this->wx->respNews($resp);
    } else if ($msg_resp['response_class'] == '3') { // 图片回复
        log_write("msg_resp" . var_export($msg_resp, true));

        /*
         * 在微信端素材的有效时间只有3天，所有采用每次都上传素材
        if ($msg_resp['media_id']) {
            $mediaId = $msg_resp['media_id'];
        } else {
            $mediaId = $this->wx->uploadMediaFile2($msg_resp['response_info']);
            // 更新mediaId到twx_msgresponse(作用：根据mediaId直接展示图片，不用每次都调用上传图片接口)
            if ($mediaId) {
                M('TwxMsgresponse')->where($where2)->save(
                    array(
                        'media_id' => $mediaId));
            }
        }
        */
        $mediaId = $this->wx->uploadMediaFile2($msg_resp['response_info']);
        
        $resp = array_merge($this->req, 
            array(
                'mediaId' => $mediaId));
        $this->wx->respImage($resp);
        log_write("xml:" . var_export($resp));
    } else if ($msg_resp['response_class'] == '4') { // 卡券回复
        $this->msg_type = '6';
        $result = $this->card_msgres($msg_resp['response_info']);
        log_write($msg_resp['response_info'], 'card_id');
        if ($result === false) {
            $this->deafault_text(); // 直接消息回复
        }
    }
    exit();
}

// df非标处理 insert memberinfo
private function fb_df_save_memberinfo() {
    $where = "openid = '" . $this->user_name . "' ";
    $member_info = M('tfb_df_member')->where($where)->find();
    if (! $member_info) {
        $save_info['openid'] = $this->user_name;
        
        // 记录关注来源
        if ($this->scene_id) {
            $map = array(
                '_string' => "a.channel_id = b.id and a.scene_id = '{$this->scene_id}'");
            $channel_id = M()->table('twx_qrchannel a, tchannel b')
                ->where($map)
                ->getField('a.channel_id');
            if ($channel_id != '') {
                $save_info['source'] = $channel_id;
            }
        }
        
        $member_id = M('tfb_df_member')->add($save_info);
    }
}

/* 素材回复 */
private function material_msgres($material_id) {
    $config = C('WeixinServ');
    
    if (! $config) {
        // 配置文件缺失
        $this->log("配置文件configWeixinServ.php缺失", "configWexinServ error");
        $this->deafault_text();
    }
    
    $where = "id = '" . $material_id . "' and node_id = '" . $this->node_id . "'";
    $material = M('TwxMaterial')->where($where)->find();
    // echo("(4)".$where);
    if (! $material) {
        // 素材已删除？
        $this->log("获取素材[" . $material_id . "]失败！", "material_msgres error");
        return false;
    }
    
    $url = $material['material_link'];
    $url_words = '';
    $login_time = date('YmdHis');
    if ($material['batch_type'] == '4' || $material['batch_type'] == '21') { // 会员招募类
                                                                             // 需要计算session
        $where = "password = '" . $this->user_name .
             "' and pass_type = '1' and node_id = '" . $this->node_id . "'";
        $member_login = M('TmemberLogin')->where($where)->find();
        // $this->log(var_export($member_login));
        if (! $member_login) { // 初次访问
            $login_info = array();
            $login_info['node_id'] = $this->node_id;
            $login_info['password'] = $this->user_name;
            $login_info['pass_type'] = '1';
            $login_info['add_time'] = $login_time;
            $login_info['login_time'] = $login_time;
            $login_info['s_id'] = MD5($login_time . $this->user_name);
            
            M('TmemberLogin')->add($login_info);
            $url_words = "login_time=" . $login_info['login_time'] . "&s_id=" .
                 $login_info['s_id'];
            
            // $this->log("获取会员登录信息[".$this->user_name."]失败！", "material_msgres
            // error");
        } else {
            $valid_time = date('YmdHis', 
                strtotime(
                    $member_login['login_time'] . "+" . $config['SID_TIME'] .
                         "minutes"));
            if ($valid_time < $login_time) {
                $login_info = array();
                $login_info['login_time'] = $login_time;
                $login_info['s_id'] = MD5($login_time . $this->user_name);
                
                M('TmemberLogin')->where($where)->save($login_info);
                $url_words = "login_time=" . $login_info['login_time'] . "&s_id=" .
                     $login_info['s_id'];
            } else {
                $url_words = "login_time=" . $member_login['login_time'] .
                     "&s_id=" . $member_login['s_id'];
            }
        }
    }
    
    if ($url_words) {
        $url = strpos($url, '?') === false ? $url . '?' . $url_words : $url . '&' .
             $url_words;
    }
    $img_url = $this->_getImgUrl($material['material_img']);
    $Articles_root = array(
        'title' => $material['material_title'], 
        'description' => $material['material_summary'], 
        'picurl' => $img_url, 
        'url' => htmlspecialchars_decode($url));
    if ($material['material_type'] == '1') { // 单图文
        return array(
            'Articles' => array(
                $Articles_root));
    } else if ($material['material_type'] == '2') { // 多图文
        $Articles_list = array();
        $Articles_list['0'] = $Articles_root; // 根图文
                                              // 获取子图文
        $where = "parent_id ='" . $material_id . "' and node_id = '" .
             $this->node_id . "'";
        $material_list = M('TwxMaterial')->where($where)->select();
        $material_count = 0;
        foreach ($material_list as &$material) {
            $material_count ++;
            $img_url = $this->_getImgUrl($material['material_img']);
            
            $url = $material['material_link'];
            if ($url_words) {
                $url = strpos($url, '?') === false ? $url . '?' . $url_words : $url .
                     '&' . $url_words;
            }
            $material_detail = array(
                'title' => $material['material_title'], 
                'description' => $material['material_summary'], 
                'picurl' => $img_url, 
                'url' => htmlspecialchars_decode($url));
            
            $Articles_list[$material_count] = $material_detail;
        }
        return array(
            'Articles' => $Articles_list);
    } else {
        // 其它类型待处理
        return false;
    }
}

/* 这儿处理不同指令 */
private function _doText($req) {
    log_write('微信发送过来的数据：'.print_r($req,true),'','TEST');
    $wx = $this->wx;
    // ①扫描关键字
    $where = "key_words = '" . $req['Content'] .
         "' and match_type = '1' and node_id = '" . $this->node_id . "'";
    
    $msg_key = M('TwxMsgkeywords')->where($where)->find();
    if (! $msg_key) { // 最后反向模糊匹配
        $where = "match_type = '0' and node_id = '" . $this->node_id . "'";
        $key_array = M('TwxMsgkeywords')->where($where)->getField(
            'id,key_words,message_id', true);
        if ($key_array) {
            foreach ($key_array as $key_info) {
                if(!empty($key_info['key_words'])) 
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
        // 判断是否转向多客服
        if ($this->service_flag == 1) {
            // 先进到特殊流程--------
            $param = array(
                'default_text', 
                $this);
            B('WeixinServ', $param);
            // 先进到特殊流程--------end
            $this->customer_service_checkrsp();
        }
        $this->default_msgres(); // 消息回复
    }

    $message_info = M('twx_message')->where("id = " . $msg_key['message_id']." and status = 0")->find();
    if(!$message_info){
        log_write('活动已删除:['.$msg_key['message_id'].']');
        $this->default_msgres();
    }
    //时间错误的处理
    if ($message_info['response_type'] == 6 ) {
        if(($message_info['begin_time'] > date('YmdHis') || ($message_info['end_time'] < date('YmdHis') && $message_info['end_time']))){
            log_write('时间错误');
            log_write('时间错误','','TEST');
            exit;
//            $this->default_msgres();
        }
            $this->msg_type = '6';
            $batchId = M('tbatch_channel')->where(array('node_id' => $this->node_id, 'batch_id' => $message_info['m_id']))->getField('id');
                //载入抽奖接口
                import('@.Vendor.ChouJiang');
                log_write('抽奖唯一使用ID：'.var_export($batchId,true));
                $ChouJiang = new ChouJiang($batchId,'',null,null,array('wx_open_id'=>$this->user_name));
                $result = $ChouJiang->send_code();
                log_write('抽奖接口返回结果：'.var_export($result,true));
                M()->startTrans();
                if($result['resp_id'] == '0000'){
                    $join_rule = M('tcj_trace')->join(' a inner join tbatch_info b on a.b_id=b.id ')->where(array('a.id'=>$result['cj_trace_id']))->getField('b.join_rule');
                    if(empty($result['card_id'])){            //红包
                        $request_id = $this->award_reqid();
                        $goods_id = M('tgoods_info')->field('a.*')->join(' a inner join tcj_batch b on a.goods_id=b.goods_id ')->where(array('a.node_id'=>$this->node_id,'b.id'=>$result['rule_id']))->find();
                        $trace_data = array(
                                'message_id' => $message_info['id'],
                                'node_id' => $this->node_id,
                                'open_id' => $this->user_name,
                                'card_id' => $goods_id['goods_id'],
                                'add_time' => date('YmdHis'),
                                'request_id' => $request_id,
                                'type' => '1'
                        );
                        log_write('中到红包,goods_id:'.$goods_id['goods_id'],'','TEST');
                        log_write('中到红包,goods_id:'.$goods_id['goods_id']);

                        $result = M('twx_msgkwd_trace')->add($trace_data);
                        if($result){
                            //中奖量+1
                            M('twx_message')->where('id=' . $msg_key['message_id'])->setInc('receive_num');
                            M()->commit();
                            $resp = array_merge($this->req,array('Content' => $join_rule));
                            $wx->respText($resp);
                        }else{
                            M()->rollback();
                            log_write("进入发送流水表[twx_msgkwd_trace]失败" . M()->_sql());
                            exit;
//                            $this->default_msgres();
                        }
                    }else{                                   //卡券
                        log_write('中到卡券,card_id:'.$result['card_id'],'','TEST');
                        log_write('中到卡券,card_id:'.$result['card_id']);
                        //避开抽奖接口扣除原卡券的库存量，所以在调用完抽奖接口后要把原库存还回去（微信卡券是以加1表示库存扣减）
                        $isOk = M()->table('twx_card_type ')->where("card_id = '" . $result['card_id'] . "'")->setDec('card_get_num');
                        if(!$isOk){
                            log_write('卡券库存还原失败:'.M()->getLastSql());
                            M()->rollback();
                            exit;
//                            $this->default_msgres();
                        }
                        M()->commit();
                        $this->outer_id = $message_info['m_id'];
                        $resp = array_merge($this->req,array('Content' => $join_rule));
                        $wx->respText($resp);
                        $this->card_msgres($result['card_id'],$message_info['id']);
                    }

                }elseif($result['resp_id'] == '1001'){     //奖品发完了
                    log_write('奖品已全部发完:['.$msg_key['message_id'].']','','TEST');
                    log_write('奖品已全部发完:['.$msg_key['message_id'].']');
                    $resp = array_merge($this->req,array('Content' => $message_info['regret_info']));
                    $wx->respText($resp);

                }elseif($result['resp_id'] == '1002'){     //已达日上限
                    log_write('奖品发放已达日上限:['.$msg_key['message_id'].']','','TEST');
                    log_write('奖品发放已达日上限:['.$msg_key['message_id'].']');
                    $resp = array_merge($this->req,array('Content' => $message_info['day_limit_info']));
                    $wx->respText($resp);

                }else{                                    //已领到过这个奖品的
                    log_write($this->user_name.'已领过该奖品:['.$msg_key['message_id'].']','','TEST');
                    log_write($this->user_name.'已领过该奖品:['.$msg_key['message_id'].']');
                    if(empty($message_info['explain_info'])){
                        $message_info['explain_info'] = '您已领过奖品，邀请更多好友一起参加吧！';
                    }
                    $resp = array_merge($this->req,
                            array(
                                    'Content' => $message_info['explain_info']));
                    $wx->respText($resp);
                }
        return;
    }
    // ②关键字回复
    $where2 = "message_id = '" . $msg_key['message_id'] . "' and node_id = '" .
         $this->node_id . "' and status = 0";
    // echo("(1)".$where);
    $msg_resp = M('TwxMsgresponse')->where($where2)->select();
    if (! $msg_resp) {
        log_write("获取消息[" . $msg_key['message_id'] . "]回复失败！","twx_msgresponse error",'','TEST');
//        $this->log("获取消息[" . $msg_key['message_id'] . "]回复失败！","twx_msgresponse error");
        $this->default_msgres(); // 找不到就直接消息回复
    }
    if ($msg_resp[0]['response_class'] == '0') { // 文本回复
        $this->msg_type = '0';
        $this->msg_info = $msg_resp[0]['response_info'];
        $resp = array_merge($this->req, 
            array(
                'Content' => $msg_resp[0]['response_info']));
        $wx->respText($resp);
    } else if ($msg_resp[0]['response_class'] == '1') { // 素材回复
        $this->msg_type = '2';
        $material_array = $this->material_msgres($msg_resp[0]['response_info']);
        if ($material_array === false) {
            $this->default_msgres(); // 直接消息回复
        }
        $this->msg_info = json_encode($material_array);
        $resp = array_merge($this->req, $material_array);
        $wx->respNews($resp);
    } else if ($msg_resp[0]['response_class'] == '3') { // 图片回复
        log_write("msg_resp" . var_export($msg_resp[0], true));
        /*
        if ($msg_resp[0]['media_id']) {
            $mediaId = $msg_resp[0]['media_id'];
        } else {
            $mediaId = $wx->uploadMediaFile2($msg_resp[0]['response_info']);
            // 更新mediaId到twx_msgresponse(作用：根据mediaId直接展示图片，不用每次都调用上传图片接口)
            if ($mediaId) {
                M('TwxMsgresponse')->where($where2)->save(
                    array(
                        'media_id' => $mediaId));
            }
        }
        */
        $mediaId = $wx->uploadMediaFile2($msg_resp[0]['response_info']);
        $this->msg_info = $msg_resp[0]['response_info'];
        $resp = array_merge($this->req, 
            array(
                'mediaId' => $mediaId));
        $wx->respImage($resp);
        log_write("xml:" . var_export($resp));
    } else if ($msg_resp[0]['response_class'] == '4') { // 卡券回复
        $this->msg_type = '6';
        $result = $this->card_msgres($msg_resp[0]['response_info']);
        if ($result === false) {
            $this->default_msgres(); // 直接消息回复
        }
        $this->msg_info = $msg_resp[0]['response_info'];
    } else if ($msg_resp[0]['response_class'] == '6' || $msg_resp[0]['response_class'] == '8') { // 互动有礼

    } else { // 其他预留 这里依然默认消息回复
        $this->log("获取消息[" . $msg_key['message_id'] . "]类型解析失败！", 
            "twx_msgresponse error");
        $this->default_msgres();
    }
}

/**
 * 回复卡券 调用客服接口发送卡券
 *
 * @param $card_id
 * @return mixed
 */
private function card_msgres($card_id, $message_id = '') {
    log_write("卡券card_id:" . $card_id);
    $wx_send = D('WeiXinSend', 'Service');
    $wx_send->init($this->node_id);
    $wx_card_service = D('WeiXinCard', 'Service');
    $wx_card_service->init_by_node_id($this->node_id);
    
    $wx_card['card_id'] = $card_id;
    $wx_card['card_ext'] = $wx_card_service->get_resp_card_ext($this->node_id, $card_id,$this->outer_id);
    if ($wx_card['card_ext'] === false)
        return false;
    $wx_send->sendCustomMsg('4', $this->user_name, $wx_card);
    if ($message_id) {
        M('twx_msgkwd_trace')->add(
            array(
                'message_id' => $message_id, 
                'node_id' => $this->node_id, 
                'open_id' => $this->user_name,
                'add_time' => date('YmdHis'),
                'card_id' => $card_id));
        /*
        $response_type = M('twx_message')->where('id=' . $message_id)->getField('response_type');
        //改到领取卡券的时候+1

        if ($response_type == 6)
        */
            M('twx_message')->where('id=' . $message_id)->setInc('receive_num');
    }
    return true;
}

/* 处理事件 */
private function _doEvent($req) {
    $wx = $this->wx;
    $tm = time();
    $event = strtolower(trim($req['Event']));
    $this->scene_id = trim($req['EventKey']);
    // 申请完的描述
    if ($event == "subscribe") {
        $this->msg_type = '0';
        $this->msg_info = '关注';
        $this->saveMsgTrace('2');
        $this->integralPayAttention();
        $this->fansAddUpdate();
        $this->adbBindStore();
        if ($this->scene_id) {
            $this->log("into subscribe");
            // $this->scene_id =
            // substr($this->scene_id,8,strlen($this->scene_id)-8);
            $this->log(
                "subscribe info>node_id:$this->node_id  scene_id:$this->scene_id");
            $this->sceneUpdate(1, 1);
        }
        if (C('df.node_id') == $this->node_id) {
            $this->fb_df_save_memberinfo();
        }
        $this->log("UYYYYY");
        // 关注事件
        $this->subscribe_msgres();
        // 扫码事件
        // $this->scan_msgres();
        exit();
    } else if ($event == "unsubscribe") {
        $this->msg_type = '0';
        $this->msg_info = '取消关注';
        $this->fansAddUpdate();
        $this->saveMsgTrace('3');
    } else if ($event == "scan") {
        $this->adbBindStore();
        $this->log("into scan");
        // 扫码数量+1
        $this->sceneUpdate(1, 0);
        // 扫码自动回复
        $this->scan_msgres();
    } else if ($event == "card_pass_check") { // 微信卡券审核通过处理
        $wx_card = D('WeiXinCard', 'Service');
        $wx_card->init_by_node_id($this->node_id);
        $wx_card->card_type_audit($this->node_id, $req['CardId'], $event);
        $this->msg_info = $req['CardId'] . '审核通过';
        $this->msg_type = '0';
        $this->saveMsgTrace('6');
    } else if ($event == "card_not_pass_check") { // 微信卡券审核拒绝处理
        $wx_card = D('WeiXinCard', 'Service');
        $wx_card->init_by_node_id($this->node_id);
        $wx_card->card_type_audit($this->node_id, $req['CardId'], $event);
        $this->msg_info = $req['CardId'] . '审核拒绝通过';
        $this->msg_type = '0';
        $this->saveMsgTrace('6');
    } else if ($event == "user_get_card") { // 微信卡券领取通知处理
        $this->log('pushData:'.var_export($req, true));
        $messageFind = M('twx_message')->where(array('node_id'=>$this->node_id,'status'=>0,'response_type'=>array('in','6,7'),'m_id'=>$req['OuterId']))->find();
        if($messageFind){
            if($messageFind['response_type'] == 7){         //呼朋引伴
                $url = U('Label/FuWenText/hpyb',
                        array(
                                'id' => $messageFind['id']), '', '', true);
                $response_info = "奖品还有很多，分享公众号给小伙伴一起领取吧！\r\n<a href='" . $url .
                        "'>点击这里，立即分享</a>";
                $resp = array_merge($this->req,
                        array(
                                'Content' => $response_info));
                $wx->respText($resp);
                //暂时这样避开后面的库存扣减，等确定下来后统一库存的扣减
                M()->table('twx_card_type ')->where("card_id = '" . $req['CardId'] . "'")->setDec('card_get_num');
            }
            if($messageFind['response_type'] == 6) {         //互动有礼
                //暂时这样避开后面的库存扣减，等确定下来后统一库存的扣减
                M()->table('twx_card_type ')->where("card_id = '" . $req['CardId'] . "'")->setDec('card_get_num');
            }
        }
        $wx_card = D('WeiXinCard', 'Service');
        $wx_card->init_by_node_id($this->node_id);
        $rs = $wx_card->create_code($req['UserCardCode'], $req['fromUserName'], 
            $req['CardId'], $req['FriendUserName'], $req['IsGiveByFriend'],$req['OuterId']);
        $this->msg_info = $req['fromUserName'] . '领取' . $req['CardId'] . '的' .
             $req['UserCardCode'];
        if ($req['IsGiveByFriend'] == '1') {
            $this->msg_info .= $this->msg_info . "[" . $req['FriendUserName'] .
                 "转赠]";
        }
        if ($rs == true) {
            $this->msg_info .= '领取成功';
        }else {
            $this->msg_info .= '领取失败';
        }
        $this->msg_type = '0';
        $this->saveMsgTrace('6');
    } else if ($event == "click") { // 菜单
        $this->wxMenuMyQrCodeEevent(); //我的二维码事件
        $event_key = trim($req['EventKey']);
        $menu_id = intval(substr($event_key, 5));
        if($menu_id=='424'){
           $this->wx->respTransferCustomerService(array('toUserName'=>'imagecotech'));exit;
        }
        // ①扫描菜单表
        $where = "id = '" . $menu_id . "' and node_id = '" . $this->node_id . "'";
        $menu_info = M('TwxMenu')->where($where)->find();
        if (! $menu_info) {
            $this->log("获取菜单[" . $menu_id . "]信息失败！input[" . $event_key . "]", 
                "twx_menu error");
            exit();
        }
        $this->msg_type = '5';
        $this->msg_info = '点击菜单：' . $menu_info['title'];
        $this->saveMsgTrace('5');
        
        // ②回复信息
        if ($menu_info['response_class'] == '0' || $menu_info['response_class'] == '6') { // 文本回复
            $resp = array_merge($this->req, 
                array(
                    'Content' => str_ireplace('<br>', "\n", $menu_info['response_info'])
                    ));
            $this->msg_type = '0';
            $this->msg_info = $menu_info['response_info'];
            $this->saveMsgTrace('1');
            $wx->respText($resp);
        } else if ($menu_info['response_class'] == '1') { // 素材回复
            $material_array = $this->material_msgres($menu_info['response_info']);
            if ($material_array === false) {
                $this->log("获取素材[" . $menu_info['response_info'] . "]信息失败！", "twx_matrial error");
                exit(); // 直接消息回复
            }
            $this->msg_type = '2';
            $this->msg_info = json_encode($material_array);
            $this->saveMsgTrace('1');
            $resp = array_merge($this->req, $material_array);
            $wx->respNews($resp);
        } else if ($menu_info['response_class'] == '5') { // 图片回复
            log_write("menu_info" . var_export($menu_info, true));

            //微信端的media_id存在有效期，所有还是每次去获取下media_id
            $mediaId = $wx->uploadMediaFile2($menu_info['response_info']);
            /*
            if ($menu_info['media_id']) {
                $mediaId = $menu_info['media_id'];
            } else {
                $mediaId = $wx->uploadMediaFile2($menu_info['response_info']);
                // 更新mediaId到twx_menu(作用：根据mediaId直接展示图片，不用每次都调用上传图片接口)
                if ($mediaId) M('twx_menu')->where($where)->save(array('media_id' => $mediaId));
            }
            */
            $this->msg_type = '1';
            $this->msg_info = $menu_info['response_info'];
            $this->saveMsgTrace('1');
            $resp = array_merge($this->req, 
                array(
                    'mediaId' => $mediaId));
            $wx->respImage($resp);
            log_write("xml:" . var_export($resp));
        } else if ($menu_info['response_class'] == '4') { // 卡券回复
            $this->msg_type = '6';
            $result = $this->card_msgres($menu_info['response_info']);
            if ($result === false) {
                $this->default_msgres(); // 直接消息回复
            }
            $this->msg_info = $menu_info['response_info'];
            $this->saveMsgTrace('1');
        }
    } else if ($event == "masssendjobfinish") { // 批量发送推送结果
        log_write("masssendjobfinish:" . var_export($req, true));
        $msgid = $req['MsgID'];
        $resp_status = $req['Status'];
        $totalcount = $req['TotalCount'];
        $filtercount = $req['FilterCount'];
        $sentcount = $req['SentCount'];
        $errorcount = $req['ErrorCount'];
        $createtime = $req['CreateTime'];
        
        $model = M('twx_msgbatch');
        $model_resp = M('twx_msgbatch_resp');
        
        $where = array(
            'wx_batch_id' => $msgid);
        M()->startTrans();
        $resp_info = $model_resp->where($where)
            ->lock(true)
            ->find();
        if (! $resp_info) {
            $this->log("未找到群发批次号！" . M()->_sql(), "twx_batchsend_result error");
            M()->rollback();
            exit(); // 直接消息回复
        }
        
        $map = array(
            'batch_id' => $resp_info['batch_id']);
        $batch_info = $model->where($map)
            ->lock(true)
            ->find();
        if (! $batch_info) {
            $this->log("未找到群发批次号！" . M()->_sql(), "twx_batchsend_result error");
            M()->rollback();
            exit(); // 直接消息回复
        }
        
        $data = array(
            'succ_num' => $batch_info['succ_num'] + $sentcount, 
            'fail_num' => $batch_info['fail_num'] + $errorcount, 
            'update_time' => date('YmdHis'));
        $flag = $model->where($map)->save($data);
        $this->log("twx_msgbatch" . M()->_sql());
        
        if ($flag === false) {
            $this->log("更新群发信息失败！原因:" . $model->getDbError(), 
                "twx_batchsend_result error");
            M()->rollback();
            exit();
        }
        
        $data = array(
            'total_count' => $totalcount, 
            'filter_count' => $filtercount, 
            'sent_count' => $sentcount, 
            'error_count' => $errorcount, 
            'create_time' => date('YmdHis', $createtime), 
            'resp_status' => $resp_status);
        $flag = $model_resp->where($where)->save($data);
        if ($flag === false) {
            $this->log("更新群发信息失败！原因:" . $model->getDbError(), 
                "twx_batchsend_result error");
            M()->rollback();
            exit();
        }
        
        M()->commit();
        $this->log("更新群发信息成功！", "twx_batchsend_result");
    }
}

/* 处理定位 */
private function _doLocation($req) {
    $wx = $this->wx;
    $tm = time();
    
    // 读取配置
    $loationFlag = $this->getSetting('location.location_flag');
    if (! $loationFlag) {
        Log_write('location.location_flag:false');
        return '';
    }
    $count = M('tstore_info')-> where("`node_id`={$this-> node_id} AND `wx_gps_flag`=1 AND `type` <> 3 AND `type`<> 4")-> count();
    //Log_write('count:'.$count);
    // 默认数量
    $respCount = (int)$this->getSetting('location.resp_count') or $respCount = 3;
    $respCount = $respCount > $count ? $count : $respCount;
    
    // 取location信息
    $lbs_x = trim($req['Location_X']);
    $lbs_y = trim($req['Location_Y']);
    $lbs_address = trim($req['Label']);
    
    // 取门店信息
    $where = "`node_id` in (" . $this->nodeId() . ") AND `lbs_x` > 0.001 AND `wx_gps_flag`=1 AND `type` <> 3 AND `type`<> 4";
    
    $order = "(lbs_x -" . $lbs_x . ")*(lbs_x -" . $lbs_x . ")+ (lbs_y -" . $lbs_y .
         ")*(lbs_y -" . $lbs_y . ")";
    $store_list = M('TstoreInfo')->where($where)
        ->order($order)
        ->limit($respCount)
        ->select();
    log_write("store_list:" . M()->getLastSql());
    if (! $store_list) {
        $this->default_msgres();
    }
    
    $_count = 0;
    $Articles_list = array();
    $locationImg = $this->getLocationImg($this->node_id);
    foreach ($store_list as &$store_info) {
        $title = $store_info['store_name'] . "   " . $this->getdistance($lbs_y, 
            $lbs_x, $store_info['lbs_y'], $store_info['lbs_x']) . "km\n" .
             $store_info['address'];
        /*
         * if(!$title) { $title = $store_info['store_name']; }
         */
        $img = $locationImg['list'];
        // 第一张图
        if ($_count == 0) {
            $img = $locationImg['top'];
        }
        $Articles_root = array(
            'title' => $title, 
            'description' => $store_info['store_name'], 
            'picurl' => $img, 
            'url' => $this->getSosoMapUrl($lbs_y, $lbs_x, $store_info['lbs_y'], 
                $store_info['lbs_x'], 
                $lbs_address . "||" . $store_info['address']));
        
        $Articles_list[$_count] = $Articles_root;
        $_count ++;
    }
    
    $material_array = array(
        'Articles' => $Articles_list);
    
    $this->msg_type = '4';
    $this->msg_info = json_encode($material_array);
    $resp = array_merge($this->req, $material_array);
    // $this->log(var_export($material_array,true));
    $this->wx->respNews($resp);
}

/* 生成地址 */
private function _url($url, $param) {
    if ($url == '') {
        return 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
    }
    $param = array_merge(
        array(
            'tm' => time(), 
            'sid' => session_id()), $param);
    return U($url, $param, false, false, true);
}

// 记录日志
protected function log($msg, $level = Log::INFO) {
    // trace('Log.'.$level.':'.$msg);
    log_write($msg, $level);
}

// 获取soso地图url
/**
 * 获取搜搜地图地址 $startLng 起点经度 $startLat 起点纬度 $endLng 终点经度 $endLat 终点纬度 $key
 * 起点地址||终点地址
 */
private function getSosoMapUrl($startLng, $startLat, $endLng, $endLat, $key = '', 
    $opt = array()) {
    $url = 'http://map.wap.soso.com/x/?';
    $opt = array_merge(
        array(
            'type' => 'drive', 
            'cond' => 1, 
            'traffic' => 'close', 
            'welcomeChange' => 1, 
            'welcomeClose' => 1), $opt);
    $opt['startLng'] = $startLng;
    $opt['startLat'] = $startLat;
    $opt['endLng'] = $endLng;
    $opt['endLat'] = $endLat;
    $opt['key'] = $key;
    $url .= http_build_query($opt, '');
    return $url;
}

// 根据经纬度计算距离
function getdistance($lng1, $lat1, $lng2, $lat2) {
    // 将角度转为狐度
    $radLat1 = deg2rad($lat1);
    $radLat2 = deg2rad($lat2);
    $radLng1 = deg2rad($lng1);
    $radLng2 = deg2rad($lng2);
    $a = $radLat1 - $radLat2; // 两纬度之差,纬度<90
    $b = $radLng1 - $radLng2; // 两经度之差纬度<180
    $s = 2 *
         asin(
            sqrt(
                pow(sin($a / 2), 2) +
                 cos($radLat1) * cos($radLat2) * pow(sin($b / 2), 2))) * 6378.137;
    return round($s, 1);
}

// 获取机构地理位置图片
protected function getLocationImg($node_id) {
    $locationImg = C('WeixinServ.LOCATION_IMG');
    $locationImg = $locationImg['default'];
    $imgUrl = C('WeixinServ.IMG_URL');
    log_write(__METHOD__ . ':' . var_export($this->setting, true));
    // 默认大图
    $small = $this->getSetting('location.small_image');
    if ($small != '') {
        if (basename($small) == $small) {
            $small = $imgUrl . 'location/' . $small;
        } else {
            $small = get_upload_url($small);
        }
    } else {
        $small = $locationImg['list'];
    }
    $large = $this->getSetting('location.large_image');
    
    if ($large != '') {
        if (basename($large) == $large) {
            $large = $imgUrl . 'location/' . $large;
        } else {
            $large = get_upload_url($large);
        }
    } else {
        $large = $locationImg['top'];
    }
    $result = array(
        'list' => $small, 
        'top' => $large);
    log_write(var_export($result, true));
    return $result;
}

private function saveMsgTrace($msg_sign) {
    $msg_trace = array();
    $msg_trace['msg_sign'] = $msg_sign;
    $msg_trace['wx_id'] = $this->user_name;
    $msg_trace['msg_type'] = $this->msg_type;
    $msg_trace['msg_info'] = $this->msg_info;
    $msg_trace['msg_time'] = date("YmdHis");
    $msg_trace['msg_response_flag'] = '0'; // 接收类
    $msg_trace['node_id'] = $this->node_id;
    $msg_trace['node_wx_id'] = $this->node_wx_id;
    
    if ($msg_sign == '1') { // 回复类(以下有共性的信息不再抽取，方便阅读)
        $msg_trace['msg_response_flag'] = '1'; // 接口 回复类默认为已处理
        $msg_trace['response_msg_id'] = $this->response_msg_id; // 自动回复记录回复消息id
    } else if ($msg_sign == '2') { // 关注类
        $msg_trace['msg_response_flag'] = '1'; // 关注类默认为已处理
    } else if ($msg_sign == '3') { // 取消关注类
        $msg_trace['msg_response_flag'] = '1'; // 取消类默认为已处理
    } else if ($msg_sign == '5') { // 菜单点击类
        $msg_trace['msg_sign'] = '0'; // 菜单点击修正为接收
    } else if ($msg_sign == '6') { // 微信卡券类
        $msg_trace['msg_response_flag'] = '1'; // 微信卡券类默认为已处理
    }
    $rs = M('TwxMsgTrace')->add($msg_trace);
    $this->log('添加到流水表：'.var_export($msg_trace, true));
    if ($rs === false) {
        $this->log(var_export($msg_trace, true));
        $this->log("记录流水信息[twx_msg_trace]失败");
        exit();
    }
    
    if ($msg_sign == '1') { // 回复类 要更新回复源信息状态
        $where = "msg_id = '" . $this->response_msg_id . "'";
        $ori_msg_trace = array();
        $ori_msg_trace['msg_response_flag'] = '2'; // 接口回复默认为自动回复
        $rs = M('TwxMsgTrace')->where($where)->save($ori_msg_trace);
    }
    $this->response_msg_id = $rs;
}

// 添加粉丝
private function fansAddUpdate() {
    $wx_user = $this->wx->getFansInfo($this->user_name, $this->access_token);
    if ($wx_user['errcode'] == '40001' || $wx_user['errcode'] == '42001' ||
         $wx_user['errcode'] == '41001') { // 需要更新access_token
        if ($this->app_secret == null) {
            $this->app_secret = '1';
        }
        $this->wx->getAccessToken($this->app_id, $this->app_secret);
        $access_token = $this->wx->accessToken;
        $wx_info = array();
        $wx_info['app_access_token'] = $access_token;
        M('TweixinInfo')->where("node_id='" . $this->node_id . "'")->save(
            $wx_info);
        
        $this->access_token = $access_token;
        $wx_user = $this->wx->getFansInfo($this->user_name, $this->access_token);
    }
    if (! isset($wx_user['errcode'])) {
        $this->scene_id = substr($this->scene_id, 8, 
            strlen($this->scene_id) - 8);
        $wx_user['node_id'] = $this->node_id;
        $wx_user['node_wx_id'] = $this->node_wx_id;
        $wx_user['openid'] = $this->user_name;
        $wx_user['group_id'] = 0;
        $wx_user['scene_id'] = $this->scene_id;
        $where = "openid='" . $this->user_name . "' and node_id= '" .
             $this->node_id . "'";
        $weixinUserModel = M('TwxUser');
        $rs = $weixinUserModel->where($where)->find();
        //过滤掉微信传过来不存在的key
        $weixinUserFields = $weixinUserModel->getDbFields();
        $wx_user = array_intersect_key($wx_user,array_flip($weixinUserFields));

        if (! $rs) {
            $rsTwxUser = $weixinUserModel->add($wx_user);
            if($rsTwxUser===false){
                log_write('新增twxuser失败'.var_export($wx_user,true));
            }
            $this->log("get fans error2:[" . M()->_sql() . "]");
        } else {
            $rs = $weixinUserModel->where($where)->save($wx_user);
            $this->log('here is save data: '.var_export($wx_user,true));
            $this->log("get fans error0:[" . $wx_user['errcode'] . "]");
            $this->log("get fans error1:[" . M()->_sql() . "]");
        }
        // 更新扫码备注名
        $where2 = "node_id = '" . $this->node_id . "' and scene_id = '" .
             $this->scene_id . "'";
        $rs2 = M('TwxQrchannel')->where($where2)->find();
        $this->log("get twxqrchannel remarkname SQL:" . M()->_sql());
        
        $weixinUserModel->where($where2)->save(
            array(
                'remarkname' => $rs2['remarkname']));
        $this->log("get twxqrchannel remarkname SQL2:" . M()->_sql());
    } else {
        $this->log("get fans error:[" . $wx_user['errcode'] . "]");
    }
}

private function getFansList() {
    $openid_list = $this->wx->getFansList($this->access_token, '');
    if ($openid_list['errcode'] == '40001' || $openid_list['errcode'] == '42001' ||
         $openid_list['errcode'] == '41001') { // 需要更新access_token
        $this->wx->getAccessToken($this->app_id, $this->app_secret);
        $access_token = $this->wx->accessToken;
        $wx_info = array();
        $wx_info['app_access_token'] = $access_token;
        M('TweixinInfo')->where("node_id='" . $this->node_id . "'")->save(
            $wx_info);
        
        $this->access_token = $access_token;
        $openid_list = $this->wx->getFansList($this->access_token, '');
    }
    if ($openid_list['count'] > '0') {
        foreach ($openid_list['data']['openid'] as &$this->user_name) {
            $this->fansAddUpdate();
        }
        
        $newwx_info = array();
        $newwx_info['getfans_flag'] = '1';
        M('TweixinInfo')->where("node_id='" . $this->node_id . "'")->save(
            $newwx_info);
    } else {
        $this->log("get fanslist error:[" . $openid_list['errcode'] . "]");
    }
}

// 更新点击数和扫码数
private function sceneUpdate($click_count, $subscribe_count) {
    $where = "node_id = '" . $this->node_id . "' and scene_id = '" .
         $this->scene_id . "'";
    $rs = M('TwxQrchannel')->where($where)->find();
    $this->log("sceneUpdate>TwxQrchannel rs:" . M()->_sql());
    $this->log(var_export($rs, true));
    if ($rs) {
        if (C('fb_boya.node_id') == $this->node_id) {
            $wxUserModel = M('TwxUser');
            $wxUserID = $wxUserModel->where(
                array(
                    'openid' => $this->user_name))->getfield('id');
            $ecshopPromotionMemberModel = M('TecshopPromotionMember');
            $data = array();
            $data['promotion_id'] = $rs['channel_id'];
            $data['wx_user_id'] = $wxUserID;
            $ecshopPromotionMemberID = $ecshopPromotionMemberModel->where(
                array(
                    'wx_user_id' => $wxUserID, 
                    'status' => '0'))->getField('id');
            if (empty($ecshopPromotionMemberID)) {
                $data['add_time'] = date('YmdHis');
                $data['status'] = '0';
                $ecshopPromotionMemberModel->add($data);
                $wx_qrchannel = array();
                $wx_qrchannel['click_count'] = $rs['click_count'] + $click_count;
                $wx_qrchannel['subscribe_count'] = $rs['subscribe_count'] +
                     $subscribe_count;
                M('TwxQrchannel')->where($where)->save($wx_qrchannel);
                M('twx_qrchannel_ext')->add(
                    array(
                        'channel_id' => $rs['channel_id'], 
                        'add_time' => $data['add_time'], 
                        'type' => 0));
            } else {
                $wx_qrchannel = array();
                $wx_qrchannel['click_count'] = $rs['click_count'] + $click_count;
                M('TwxQrchannel')->where($where)->save($wx_qrchannel);
            }
        } else {
            M()->startTrans();
            $wx_qrchannel = array();
            $wx_qrchannel['click_count'] = $rs['click_count'] + $click_count;
            $wx_qrchannel['subscribe_count'] = $rs['subscribe_count'] +
                 $subscribe_count;
            M('TwxQrchannel')->where($where)->save($wx_qrchannel);
            $this->log(
                "sceneUpdate>click_count:$click_count|subscribe_count:$subscribe_count  SQL :" .
                     M()->_sql());
            
            M('twx_qrchannel_ext')->add(
                array(
                    'channel_id' => $rs['channel_id'], 
                    'add_time' => date('YmdHis'), 
                    'type' => 1));
            // 根据判断关注数量是否+1(有值)，再添加一条关注流水
            if ($subscribe_count) {
                M('twx_qrchannel_ext')->add(
                    array(
                        'channel_id' => $rs['channel_id'], 
                        'add_time' => date('YmdHis'), 
                        'type' => 0));
            }
            
            M()->commit();
        }
    }
}

private function getSetting($key) {
    if (! $this->setting) {
        return null;
    }
    $keyList = explode('.', $key);
    $value = $this->setting;
    foreach ($keyList as $v) {
        $value = isset($value[$v]) ? $value[$v] : null;
        if (! $value)
            return $value;
    }
    return $value;
}

// 取机构树
private function nodeId() {
    $path = M('tnode_info')->where(
        array(
            'node_id' => $this->node_id))->getField('full_id');
    if (! $path) {
        return "'" . $this->node_id . "'";
    }
    return "select node_id from tnode_info where full_id like '" . $path . "%'";
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

function award_reqid(){
    $data = M()->query("SELECT _nextval('award_send_seq') as reqid FROM DUAL");
    if (! $data) {
        log_write('award_send_seq fail!');
        $req = rand(1, 999999);
    } else {
        $req = $data[0]['reqid'];
    }
    return date('YmdHis') . str_pad($req, 6, '0', STR_PAD_LEFT);
}

private function integralPayAttention() {
    // 判断该商户是否有开通积分营销模块
    log_write("pengqi1进来了么");
    $IntegralConfigNode = new IntegralConfigNodeModel();
    $IntegralConfigInfo = $IntegralConfigNode->checkIntegralConfig(
        $this->node_id);
    if ($IntegralConfigInfo != false && $IntegralConfigInfo) {
        // 有开通积分营销模块
        if ($IntegralConfigInfo['weixin_guanzhu_flag'] == 1 &&
             $IntegralConfigInfo['weixin_guanzhu_rate'] > 0) {
            $weiXinFlag = M("twx_user")->where(
                array(
                    'node_id' => $this->node_id, 
                    'openid' => $this->user_name))->find();
            // 未关注才送积分
            log_write("pengqi2未关注");
            log_write("未关注送积分" . var_export($weiXinFlag, true));
            if (empty($weiXinFlag)) {
                log_write("yesong不存在user_info");
                // 关注送积分
                $map = array(
                    'mwx_openid' => $this->user_name, 
                    'node_id' => $this->node_id);
                $member_info = M('tmember_info')->where($map)->find();
                $IntegralPointTrace = new IntegralPointTraceModel();
                if ($this->scene_id) {
                    $map = array(
                        '_string' => "a.channel_id = b.id and a.scene_id = '{$this->scene_id}'");
                    $channelId = M()->table('twx_qrchannel a, tchannel b')
                        ->where($map)
                        ->getField('a.channel_id');
                }
                M()->startTrans();
                log_write("yesong 走到事务了么？");
                if (empty($member_info)) {
                    log_write("pengqi3没有会员");
                    // 没有，需要给用户新增为会员，并给积分
                    $member_info = $IntegralPointTrace->attentionPoints(
                        $this->node_id, $this->user_name, $channelId, 
                        $IntegralConfigInfo['weixin_guanzhu_rate']);
                    if ($member_info === false) {
                        log_write("pengqi4没有会员");
                        M()->rollback();
                        log_write("关注送积分失败");
                    }
                } else {
                    // 更新会员（赠送相应的积分）
                    $integralRes = $IntegralPointTrace->integralPointChange(
                        '15', $IntegralConfigInfo['weixin_guanzhu_rate'], 
                        $member_info['id'], $this->node_id, $this->user_name, '');
                    if ($integralRes === false) {
                        M()->rollback();
                        log_write("pengqi5");
                        log_write("关注送积分失败");
                    }
                }
                // 新增行为数据
                $res = D("MemberBehavior")->addBehaviorType($member_info['id'], 
                    $this->node_id, 13, 
                    $IntegralConfigInfo['weixin_guanzhu_rate']);
                if ($res === false) {
                    M()->rollback();
                    $this->error("行为增加失败!");
                }
                M()->commit();
            }
        }
    }
}

    /**
     * 多客服转接前的文本机器人
     * @param  [type] $resp [description]
     * @return [type]       [description]
     */
    private function _doKfText($resp)
    {
    //    D('WeiXinKf')->KfEvent($resp,$this->node_id);
        $weixin_kf_config=C('weixinKf');
        if(!in_array($this->node_id,$weixin_kf_config['node_id'])
        || empty($resp)){
            return false;
        }
        //用户openid
        $openid=$resp['fromUserName'];
        $now=time();
        //回去缓存列表
        $cache=S("weixinKf_user_status_".$this->node_id);
        //用户状态信息
        $user_info=$cache[$openid];
        if(empty($user_info) || ($user_info['time']+(20*30)) < $now){//无缓存或操作超时则自动创建(重置)
            $user_info=array(
                'status'=>1,
                );
        }
        $user_info['openid']=$openid;
        //模拟数据
        $kf_list=array(
            '4'=>array(
                "response_info"=>"请选择输入序号#LIST#输入[0]退出",
                "id"=>2,
                "type"=>8,
                ),
            "0"=>array(
                "response_info"=>"谢谢您的支持",
                "id"=>3,
                "type"=>9,
                )
            );
        $kf_keyword=$kf_list[$resp['Content']];
        //获取缓存客服文本导航
        $kf_keyword['response_info']=$kf_keyword["response_info"]?$kf_keyword["response_info"]:$user_info["kf_text_nav"];
        $this->log("【用户状态】".var_export($user_info,true));
        $this->log("【获取客服导航信息】".var_export($kf_keyword,true));
        //普通回复状态且进入客服
        if($user_info['status'] == 1 && $kf_keyword['type'] == 8){
            //获取信息列表
            $kf_class_list=$this->getKfMsgListById($kf_keyword['id']);
            $this->log("【获取客服信息列表】".var_export($kf_class_list,true));
            $kf_class_str="";
            if($kf_class_list){
                foreach($kf_class_list as $row){
                    $kf_class_str.="[".$row['no']."]、".$row['response_info']."\n";
                }
            }
            if($kf_class_str){
                //改变状态
                $user_info['status']=2;
                //拼装回复信息
                $content=str_replace("#LIST#", "\n".$kf_class_str, $kf_keyword['response_info']);
                //记录客服文本导航
                $user_info['kf_text_nav']=$kf_keyword["response_info"];
                //记录客服下一步文本菜单
                $user_info['next_list']=$kf_class_list;
            }else{
                $user_info=false;
                $content=false;
            }

        }elseif($user_info['status'] == 2 && $kf_keyword['type'] == 9){
          //退出客服文本会话
          $content = $kf_keyword['response_info'];
          $user_info=false;
        }elseif($user_info['status'] == 1){
            //自动回复
            //$this->_doText($resp);
            $this->log("【文本回复】".$this->resp['Content']);
            unset($cache[$openid]);
            S("weixinKf_user_status_".$this->node_id,$cache);
            return false;
        }else{
            //Kf文本回复
            if(empty($user_info['next_list'])){
                //改变状态
                $user_info=false;
                $content =false;
            }else{
                $next_info=null;
                //匹配关键字
                foreach($user_info['next_list'] as $row){
                    if($row['no'] == $resp['Content']){
                        $next_info=$row;
                        break;
                    }
                }
                if(empty($next_info['id'])){
                    //模拟数据  默认为 一级菜单
                    $kf_class_list=array(
                    array(
                        "response_info"=>"保险客服",
                        "no"=>"1",
                        "kf_id"=>"2",
                        "id"=>"3",
                        ),
                    array(
                        "response_info"=>"产品客服",
                        "no"=>"2",
                        "kf_id"=>"2",
                        "id"=>"4",
                        ),
                    array(
                        "response_info"=>"售后客服",
                        "no"=>"3",
                        "kf_id"=>"2",
                        "id"=>"5",
                        ),
                    );
                }else{
                    //如果有子菜单则向下
                    $kf_class_list=$this->getKfMsgListById($next_info['id']);
                }
                $this->log("【下一步客服导航】".var_export($kf_class_list,true));
                if($kf_class_list){
                    //获取下一步
                    $kf_class_str="";
                    foreach($kf_class_list as $row){
                        $kf_class_str.="[".$row['no']."]、".$row['response_info']."\n";
                    }
                     //改变状态
                    $user_info['status']=2;
                    $user_info['next_list']=$kf_class_list;
                    $content=str_replace("#LIST#", "\n".$kf_class_str, $kf_keyword['response_info']);
                   
                }else{
                    //创建客服会话
                    $model=D('WeiXinKf');
                     $res=$model->KfCreateCall($this->node_id,$next_info['kf_id'],$openid);
                     if(!$res){
                        $content=$model->getError();
                     }else{
                        $content= "开始创建【".$next_info['response_info']."】会话";
                     }
                     $user_info=false;
                }
            }
        }
        //重设用户状态
        if($user_info){
            $user_info['time']=$now;
            $cache[$openid]=$user_info;
        }else{
            unset($cache[$openid]);
        }
        S("weixinKf_user_status_".$this->node_id,$cache);      
        
        //回复信息
        $content=$content?$content:"请求失败";
        $this->msg_type = '0';
        $this->msg_info = $content;
        $resp = array_merge($this->req, 
        array(
        'Content' => $content));
        $this->wx->respText($resp); 
        
        return true;
    }

    private function getKfMsgListById($id){
        if(empty($id)){
            return false;
        }
         //模拟数据
        $kf_class_data=array(
            "2"=>array(
                array(
                    "response_info"=>"保险客服",
                    "no"=>"1",
                    "kf_id"=>"2",
                    "id"=>"3",
                    ),
                array(
                    "response_info"=>"产品客服",
                    "no"=>"2",
                    "kf_id"=>"2",
                    "id"=>"4",
                    ),
                array(
                    "response_info"=>"售后客服",
                    "no"=>"3",
                    "kf_id"=>"2",
                    "id"=>"5",
                    ),
                ),
            "3"=>array(
                array(
                    "response_info"=>"连线【1】保险客服",
                    "no"=>"11",
                    "kf_id"=>"2",
                    "id"=>"6",
                    ),
                array(
                    "response_info"=>"连线【1】产品客服",
                    "no"=>"12",
                    "kf_id"=>"2",
                    "id"=>"7",
                    ),
                array(
                    "response_info"=>"连线【1】售后客服",
                    "no"=>"13",
                    "kf_id"=>"2",
                    "id"=>"8",
                    ),
                ),
            "4"=>array(
                array(
                    "response_info"=>"连线【2】保险客服",
                    "no"=>"21",
                    "kf_id"=>"2",
                    "id"=>"9",
                    ),
                array(
                    "response_info"=>"连线【2】产品客服",
                    "no"=>"22",
                    "kf_id"=>"2",
                    "id"=>"10",
                    ),
                array(
                    "response_info"=>"连线【2】售后客服",
                    "no"=>"23",
                    "kf_id"=>"2",
                    "id"=>"11",
                    ),
                ),
            "5"=>array(
                array(
                    "response_info"=>"连线【3】保险客服",
                    "no"=>"31",
                    "kf_id"=>"2",
                    "id"=>"12",
                    ),
                array(
                    "response_info"=>"连线【3】产品客服",
                    "no"=>"32",
                    "kf_id"=>"2",
                    "id"=>"13",
                    ),
                array(
                    "response_info"=>"连线【3】售后客服",
                    "no"=>"33",
                    "kf_id"=>"2",
                    "id"=>"14",
                    ),
                ),
            );
        return $kf_class_data[$id];
    }

    //爱蒂宝绑定门店
    private function adbBindStore()
    {
        if($this->node_id != C('adb.node_id')){
            return ;
        }
        $resp=array(
            'openid'=>$this->user_name,
            'scene_id'=>$this->scene_id,
            );
        D('Adb','Service')->bindStoreEvent($resp);
        return ;
    }

    //微信菜单我的微码事件
    private function wxMenuMyQrCodeEevent()
    {   
        $serv=D('FbLiaoNing','Service');
        if($serv->checkMyQrCodeByNodeId($this->node_id)){
            $resp=$serv->myQrCode($this->req);
            if($resp){
                if($resp['mediaId']){
                    $this->wx->respImage($resp);
                }else{
                    $this->wx->respNews($resp);
                }
                log_write("xml:" . var_export($resp,true));
                exit;
            }
        }
        return;
    }
}