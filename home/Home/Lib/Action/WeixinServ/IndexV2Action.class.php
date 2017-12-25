<?php
// 指令接口服务设置
class IndexV2Action extends BaseAction {

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

    /*
     * {"location":{"location_flag":"1","resp_count":"3","large_image":"00004488top.jpg","small_image":"00004488item.jpg"}}
     */
    public $setting = array();

    public function _initialize() {
        C('WeixinServ', require (CONF_PATH . 'configWeixinServ.php'));
        if (C('WeixinServ.LOG_PATH'))
            C('LOG_PATH', C('WeixinServ.LOG_PATH')); // 重新定义目志目录
        $this->node_id = I('GET.node_id'); // 商户id
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
        $this->log(print_r($weixin_info, true));
        $this->token = $weixin_info['token'];
        $this->access_token = $weixin_info['app_access_token'];
        $this->app_id = $weixin_info['app_id'];
        $this->app_secret = $weixin_info['app_secret'];
        $this->wx->init($this->app_id, $this->app_secret, $this->access_token);
        if ($weixin_info['setting']) {
            $this->setting = json_decode($weixin_info['setting'], true) or
                 $this->setting = array();
        }
        $wx->setToken($this->token);
        // 校验签名
        if (! $wx->checkSignature()) {
            // to-do debug
            // die('签名错误');
        }
        $wx->valid();
        
        // 开始解析指令
        $this->req = $wx->parseRequest();
        $this->user_name = $this->req['fromUserName'];
        $this->node_wx_id = $this->req['toUserName'];
        if ($weixin_info['getfans_flag'] == '0') {
            $this->getFansList();
        }
        // 00018419 生产平台cpyfy@imageco.com.cn机构号
        $yima_wexin_node_id = C('WeixinServ.YIMA_WEIXIN_NODE_ID');
        if ($this->node_id == $yima_wexin_node_id) {
            $this->wx->respTransferCustomerService(array());
            exit();
        }
        
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
                $respStr = $this->_doText($this->req);
                $this->saveMsgTrace('1');
                echo $respStr;
                break;
            case 'image':
                $this->msg_type = '1';
                $this->msg_info = $this->req['PicUrl'];
                $this->saveMsgTrace('0');
                break;
            case 'location':
                $this->msg_type = '4';
                $this->msg_info = $this->req['Location_X'] . "|" .
                     $this->req['Location_Y'] . "|" . $this->req['Label'];
                $this->saveMsgTrace('0');
                $respStr = $this->_doLocation($this->req);
                Log::write('response:' . $respStr);
                $this->saveMsgTrace('1');
                echo $respStr;
                break;
            case 'link':
                break;
            case 'voice':
                $this->msg_type = '3';
                $this->msg_info = $this->req['MediaId'];
                $this->saveMsgTrace('0');
                break;
            case 'news':
                break;
            default:
                echo 'error';
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

    /* 关注回复 */
    private function subscribe_msgres() {
        // ①找到主动回复id
        $where = "response_type = '0' and status = '0' and node_id = '" .
             $this->node_id . "'";
        // echo("(2)".$where);
        $message = M('TwxMessage')->where($where)->find();
        if (! $message) {
            $this->log("商户[" . $this->node_id . "]关注消息缺失", "twx_message error");
            $this->deafault_text();
        }
        
        // ②扫描回复表
        $where = "message_id = '" . $message['id'] . "' and node_id = '" .
             $this->node_id . "'";
        $msg_resp = M('TwxMsgresponse')->where($where)->find();
        // echo("(3)".$where);
        if (! $msg_resp) {
            $this->log("获取消息[" . $message['id'] . "]回复失败！", 
                "twx_msgresponse error");
            $this->deafault_text(); // 这里还找不到就直接回复默认文本
        }
        if ($msg_resp['response_class'] == '0') // 文本回复
{
            $resp = array_merge($this->req, 
                array(
                    'Content' => $msg_resp['response_info']));
            $this->wx->respText($resp);
        } else if ($msg_resp['response_class'] == '1') // 素材回复
{
            $material_array = $this->material_msgres($msg_resp['response_info']);
            if ($material_array === false) {
                $this->deafault_text(); // 直接消息回复
            }
            $resp = array_merge($this->req, $material_array);
            // echo(print_r($material_array));
            // echo(print_r($resp));
            $this->wx->respNews($resp);
        }
    }

    /* 消息自动回复 */
    private function default_msgres() {
        // 先进到特殊流程--------
        $param = array(
            'default_text', 
            $this);
        B('WeixinServ', $param);
        // 先进到特殊流程--------end
        
        // ①找到消息回复id
        $where = "response_type = '1' and status = '0' and node_id = '" .
             $this->node_id . "'";
        // echo("(2)".$where);
        $message = M('TwxMessage')->where($where)->find();
        if (! $message) {
            $this->log("商户[" . $this->node_id . "]默认消息缺失", "twx_message error");
            $this->deafault_text();
        }
        
        // ②扫描回复表
        $where = "message_id = '" . $message['id'] . "' and node_id = '" .
             $this->node_id . "'";
        $msg_resp = M('TwxMsgresponse')->where($where)->find();
        // echo("(3)".$where);
        if (! $msg_resp) {
            $this->log("获取消息[" . $message['id'] . "]回复失败！", 
                "twx_msgresponse error");
            $this->deafault_text(); // 这里还找不到就直接回复默认文本
        }
        if ($msg_resp['response_class'] == '0') // 文本回复
{
            $resp = array_merge($this->req, 
                array(
                    'Content' => $msg_resp['response_info']));
            $this->wx->respText($resp);
        } else if ($msg_resp['response_class'] == '1') // 素材回复
{
            $material_array = $this->material_msgres($msg_resp['response_info']);
            if ($material_array === false) {
                $this->deafault_text(); // 直接消息回复
            }
            $resp = array_merge($this->req, $material_array);
            // echo(print_r($material_array));
            // echo(print_r($resp));
            $this->wx->respNews($resp);
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
        
        $where = "id = '" . $material_id . "' and node_id = '" . $this->node_id .
             "'";
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
        if ($material['batch_type'] == '4' || $material['batch_type'] == '21' ||
             $material['batch_type'] == '0') // 会员招募类
                                            // 需要计算session
        {
            $where = "password = '" . $this->user_name .
             "' and pass_type = '1' and node_id = '" . $this->node_id . "'";
        $member_login = M('TmemberLogin')->where($where)->find();
        // $this->log(print_r($member_login));
        if (! $member_login) // 初次访问
{
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
    
    $img_url = $config['IMG_URL'] . $material['material_img'];
    $Articles_root = array(
        'title' => $material['material_title'], 
        'description' => $material['material_summary'], 
        'picurl' => $img_url, 
        'url' => htmlspecialchars_decode($url));
    if ($material['material_type'] == '1') // 单图文
{
        return array(
            'Articles' => array(
                $Articles_root));
    } else if ($material['material_type'] == '2') // 多图文
{
        $Articles_list = array();
        $Articles_list['0'] = $Articles_root; // 根图文
                                              
        // 获取子图文
        $where = "parent_id ='" . $material_id . "' and node_id = '" .
             $this->node_id . "'";
        $material_list = M('TwxMaterial')->where($where)->select();
        $material_count = 0;
        foreach ($material_list as &$material) {
            $material_count ++;
            $img_url = $config['IMG_URL'] . $material['material_img'];
            
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
    $wx = $this->wx;
    
    // ①扫描关键字
    $where = "key_words = '" . $req['Content'] .
         "' and match_type = '1' and node_id = '" . $this->node_id . "'";
    
    $msg_key = M('TwxMsgkeywords')->where($where)->find(); // 优先全字匹配并回复第一条
                                                           // echo("(1x)".print_r($msg_key,true));
    if (! $msg_key) // 其次模糊匹配
{
        $where = "key_words like '%" . $req['Content'] .
             "%' and match_type = '0' and node_id = '" . $this->node_id . "'";
        $msg_key = M('TwxMsgkeywords')->where($where)->find(); // 模糊匹配并回复第一条
    }
    
    if (! $msg_key) // 最后反向模糊匹配
{
        $where = "match_type = '0' and node_id = '" . $this->node_id . "'";
        $key_array = M('TwxMsgkeywords')->where($where)->getField(
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
        $this->default_msgres(); // 消息回复
    }
    
    // ②关键字回复
    $where = "message_id = '" . $msg_key['message_id'] . "' and node_id = '" .
         $this->node_id . "'";
    // echo("(1)".$where);
    $msg_resp = M('TwxMsgresponse')->where($where)->find();
    if (! $msg_resp) {
        $this->log("获取消息[" . $msg_key['message_id'] . "]回复失败！", 
            "twx_msgresponse error");
        $this->default_msgres(); // 找不到就直接消息回复
    }
    if ($msg_resp['response_class'] == '0') // 文本回复
{
        $this->msg_type = '0';
        $this->msg_info = $msg_resp['response_info'];
        $resp = array_merge($this->req, 
            array(
                'Content' => $msg_resp['response_info']));
        $wx->respText($resp);
    } else if ($msg_resp['response_class'] == '1') // 素材回复
{
        $this->msg_type = '2';
        $material_array = $this->material_msgres($msg_resp['response_info']);
        if ($material_array === false) {
            $this->default_msgres(); // 直接消息回复
        }
        $this->msg_info = json_encode($material_array);
        $resp = array_merge($this->req, $material_array);
        $wx->respNews($resp);
    } else // 其他预留 这里依然默认消息回复
{
        $this->log("获取消息[" . $msg_key['message_id'] . "]类型解析失败！", 
            "twx_msgresponse error");
        $this->default_msgres();
    }
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
        $this->fansAddUpdate();
        if ($this->scene_id) {
            $this->scene_id = substr($this->scene_id, 8, 
                strlen($this->scene_id) - 8);
            $this->sceneUpdate(1, 1);
        }
        $this->subscribe_msgres();
    } else if ($event == "unsubscribe") {
        $this->msg_type = '0';
        $this->msg_info = '取消关注';
        $this->fansAddUpdate();
        $this->saveMsgTrace('3');
    } else if ($event == "scan") {
        $this->sceneUpdate(1, 0);
    } else if ($event == "click") // 菜单
{
        $event_key = trim($req['EventKey']);
        $menu_id = intval(substr($event_key, 5));
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
        if ($menu_info['response_class'] == '0') // 文本回复
{
            $resp = array_merge($this->req, 
                array(
                    'Content' => $menu_info['response_info']));
            $this->msg_type = '0';
            $this->msg_info = $menu_info['response_info'];
            $this->saveMsgTrace('5');
            $wx->respText($resp);
        } else if ($menu_info['response_class'] == '1') // 素材回复
{
            $material_array = $this->material_msgres(
                $menu_info['response_info']);
            if ($material_array === false) {
                $this->log("获取素材[" . $menu_info['response_info'] . "]信息失败！", 
                    "twx_matrial error");
                exit(); // 直接消息回复
            }
            $this->msg_type = '2';
            $this->msg_info = json_encode($material_array);
            $this->saveMsgTrace('5');
            $resp = array_merge($this->req, $material_array);
            $wx->respNews($resp);
        }
    } else if ($event == "masssendjobfinish") // 批量发送推送结果
{
        $msgid = $req['MsgID'];
        $resp_status = $req['Status'];
        $totalcount = $req['TotalCount'];
        $filtercount = $req['FilterCount'];
        $sentcount = $req['SentCount'];
        $errorcount = $req['ErrorCount'];
        
        $model = M('twx_msgbatch');
        $where = array(
            'wx_batch_id' => $msgid);
        $count = $model->where($where)->count();
        
        if ($count == 0) {
            $this->log("未找到群发批次号！" . $model->_sql(), 
                "twx_batchsend_result error");
            exit(); // 直接消息回复
        }
        
        if ($resp_status != 'send success') {
            $status = '9';
        } else {
            if ($totalcount == $sentcount)
                $status = '3';
            if ($errorcount > 0)
                $status = '2';
        }
        
        $data = array(
            'succ_num' => $sentcount, 
            'fail_num' => $errorcount, 
            'status' => $status, 
            'wx_resp_status' => $resp_status);
        $flag = $model->where($where)->save($data);
        if ($flag === false) {
            $this->log("更新群发信息失败！原因:" . $model->getDbError(), 
                "twx_batchsend_result error");
            exit(); // 直接消息回复
        }
        
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
        Log::write('location.location_flag:false');
        return '';
    }
    // 默认数量
    $respCount = $this->getSetting('location.resp_count') or $respCount = 3;
    
    // 取location信息
    $lbs_x = trim($req['Location_X']);
    $lbs_y = trim($req['Location_Y']);
    $lbs_address = trim($req['Label']);
    
    // 取门店信息
    $where = "node_id in (" . $this->nodeId() .
         ") and lbs_x > 0.001 and status='0'";
    $order = "(lbs_x -" . $lbs_x . ")*(lbs_x -" . $lbs_x . ")+ (lbs_y -" . $lbs_y .
         ")*(lbs_y -" . $lbs_y . ")";
    $store_list = M('TstoreInfo')->where($where)
        ->order($order)
        ->limit($respCount)
        ->select();
    // Log::write("store_list:".M()->getLastSql());
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
    // $this->log(print_r($material_array,true));
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
    Log::write($msg, '[' . _APP_PID_ . ']' . $level);
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
    
    // 默认大图
    $small = $this->getSetting('location.small_image');
    if ($small != '') {
        $small = $imgUrl . 'location/' . $small;
    } else {
        $small = $locationImg['list'];
    }
    $large = $this->getSetting('location.large_image');
    
    if ($large != '') {
        $large = $imgUrl . 'location/' . $large;
    } else {
        $large = $locationImg['top'];
    }
    return array(
        'list' => $small, 
        'top' => $large);
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
    
    if ($msg_sign == '1') // 回复类(以下有共性的信息不再抽取，方便阅读)
{
        $msg_trace['msg_response_flag'] = '1'; // 接口 回复类默认为已处理
        $msg_trace['response_msg_id'] = $this->response_msg_id; // 自动回复记录回复消息id
    } else if ($msg_sign == '2') // 关注类
{
        $msg_trace['msg_response_flag'] = '1'; // 关注类默认为已处理
    } else if ($msg_sign == '3') // 取消关注类
{
        $msg_trace['msg_response_flag'] = '1'; // 取消类默认为已处理
    } else if ($msg_sign == '5') // 菜单点击类
{
        $msg_trace['msg_sign'] = '0'; // 菜单点击修正为接收
    }
    $rs = M('TwxMsgTrace')->add($msg_trace);
    if ($rs === false) {
        $this->log(print_r($msg_trace, true));
        $this->log("记录流水信息[twx_msg_trace]失败");
        exit();
    }
    
    if ($msg_sign == '1') // 回复类 要更新回复源信息状态
{
        $where = "msg_id = '" . $this->response_msg_id . "'";
        $ori_msg_trace = array();
        $ori_msg_trace['msg_response_flag'] = '2'; // 接口回复默认为自动回复
        $rs = M('TwxMsgTrace')->where($where)->save($ori_msg_trace);
    }
    $this->response_msg_id = $rs;
}

private function fansAddUpdate() {
    $where = "openid='" . $this->user_name . "'";
    $rs = M('TwxUser')->where($where)->find();
    
    $wx_user = $this->wx->getFansInfo($this->user_name, $this->access_token);
    if ($wx_user['errcode'] == '40001' || $wx_user['errcode'] == '42001') // 需要更新access_token
{
        $access_token = $this->wx->getAccessToken($this->app_id, 
            $this->app_secret);
        $wx_info = array();
        $wx_info['app_access_token'] = $access_token['access_token'];
        M('TweixinInfo')->where("node_id='" . $this->node_id . "'")->save(
            $wx_info);
        
        $this->access_token = $access_token['access_token'];
        $wx_user = $this->wx->getFansInfo($this->user_name, $this->access_token);
    }
    if (! isset($wx_user['errcode'])) {
        $wx_user['node_id'] = $this->node_id;
        $wx_user['node_wx_id'] = $this->node_wx_id;
        $wx_user['group_id'] = 0;
        if (! $rs) {
            $rs = M('TwxUser')->add($wx_user);
        } else {
            $rs = M('TwxUser')->where($where)->save($wx_user);
        }
    } else {
        $this->log("get fans error:[" . $wx_user['errcode'] . "]");
    }
}

private function getFansList() {
    $openid_list = $this->wx->getFansList($this->access_token, '');
    if ($openid_list['errcode'] == '40001' || $openid_list['errcode'] == '42001') // 需要更新access_token
{
        $access_token = $this->wx->getAccessToken($this->app_id, 
            $this->app_secret);
        $wx_info = array();
        $wx_info['app_access_token'] = $access_token['access_token'];
        M('TweixinInfo')->where("node_id='" . $this->node_id . "'")->save(
            $wx_info);
        
        $this->access_token = $access_token['access_token'];
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

private function sceneUpdate($click_count, $subscribe_count) {
    $where = "node_id = '" . $this->node_id . "' and scene_id = '" .
         $this->scene_id . "'";
    $rs = M('TwxQrchannel')->where($where)->find();
    if ($rs) {
        M()->startTrans();
        $wx_qrchannel = array();
        $wx_qrchannel['click_count'] = $rs['click_count'] + $click_count;
        $wx_qrchannel['subscribe_count'] = $rs['subscribe_count'] +
             $subscribe_count;
        M('TwxQrchannel')->where($where)->save($wx_qrchannel);
        M()->commit();
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
}