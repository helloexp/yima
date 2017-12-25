<?php

class WeixinRespAction extends BaseAction {

    public $uploadPath;

    private $node_weixin_code;

    private $node_wx_id;

    private $account_type;

    const CHANNEL_TYPE_WX = '4';
    // 微信公众平台
    const CHANNEL_SNS_TYPE_WX = '41';
    // 微信公众平台发布类型
    public function _initialize() {
        parent::_initialize();
        $this->uploadPath = './Home/Upload/Weixin/'; // 设置附件上传目录
        $info = D('tweixin_info')->where(
            "node_id = '{$this->node_id}' and status = '0'")->find();
        $this->node_weixin_code = $info['weixin_code'];
        $this->node_wx_id = $info['node_wx_id'];
        $this->account_type = $info['account_type'];
    }

    public function index() {
        $where = "node_id='" . $this->nodeId . "'and response_type=0";
        $dao = M('twx_message');
        $messageInfo = $dao->where($where)->find();
        
        $message_id = $messageInfo['id'];
        $respInfo = array();
        
        if ($message_id) {
            $respInfo = M("twx_msgresponse")->where(
                "message_id='" . $message_id . "'")->find();
        }
        
        if ('' != $respInfo['response_info']) {
            
            /**
             * 转换QQ表情 [表情] -> <img >
             */
            $ruleText = '/\[.*\]/iUs';
            preg_match_all($ruleText, $respInfo['response_info'], $arr);
            
            if ($arr[0]) {
                $qqFace = C('qqFace'); // 获取QQ表情配置
                $qqFace = json_decode($qqFace);
                
                foreach ($arr[0] as $sqlK => $sqlV) {
                    foreach ($qqFace as $configK => $configV) {
                        if ($sqlV == $configV->phrase) {
                            $faceArr[] = '/\[' . substr($sqlV, 1, - 1) . '\]/';
                            $imgArr[] = '<img src=' .
                                 C('TMPL_PARSE_STRING.__PUBLIC__') .
                                 '/Image/weixin2/emotion/' .
                                 $qqFace[$configK]->url . '>';
                        }
                    }
                }
                
                $faceRule = $faceArr; // [表情]匹配
                $faceReplace = $imgArr; // 表情图片替换
                $respText = preg_replace($faceRule, $faceReplace, 
                    $respInfo['response_info']);
            } else {
                if ($respInfo['response_class'] == 4 && !empty($respInfo['response_info'])) {
                    $card_info = M()->table("twx_card_type t1")->join(
                        'tgoods_info t2 on t1.goods_id=t2.goods_id')
                        ->where(
                        array(
                            'card_id' => $respInfo['response_info']))
                        ->field(
                        't1.id,logo_url,title,date_type,date_begin_timestamp,date_end_timestamp,date_fixed_timestamp,date_fixed_begin_timestamp,card_id,quantity,card_get_num,goods_image')
                        ->find();
                    $respText = $card_info;
                    $respText['goods_name'] = $card_info['title'];
                    $respText['goods_image_url'] = get_upload_url(
                        $card_info['goods_image']);
                    if ($card_info['date_type'] == 1) {
                        $respText['time'] = date('Y-m-d', 
                            $card_info['date_begin_timestamp']) . '至' .
                             date('Y-m-d', $card_info['date_end_timestamp']);
                    } else {
                        $respText['time'] = '领取后' .
                             $card_info['date_fixed_begin_timestamp'] . '天开始使用，' .
                             $card_info['date_fixed_timestamp'] . '天结束使用';
                    }
                } else {
                    $respText = $respInfo['response_info'];
                }
                
                if ($respInfo['batch_id']) {
                    $respInfo['response_class'] = 5;
                    $respText = explode("\r\n", $respText);
                    $respText['batch_id'] = $respInfo['batch_id'];
                }
            }
        }
        
        $this->assign('imgId', '');
        if ('1' == $respInfo['response_class']) {
            $this->assign('imgId', $respInfo['response_info']);
        }
        //为防止html页面中js因表情引起的报错，所有做出唯一标示
        $hasText = 0;
        if(!empty($respText)){
            $hasText = 1;
        }
        $this->assign('hasText',$hasText);
        $this->assign('node_id',$this->node_id);
        $this->assign('respText', $respText);
        $this->assign('response_class', $respInfo['response_class']);
        $this->assign('status', $messageInfo['status']);
        $this->assign('account_type', $this->account_type);
        $this->display();
    }

    public function msg() {
        $where = "node_id='" . $this->nodeId . "'and response_type=1";
        $dao = M('twx_message');
        $messageInfo = $dao->field("id")
            ->where($where)
            ->find();
        $setting = M('tweixin_info')->where("node_id='" . $this->nodeId . "'")
            ->field('setting')
            ->find();
        $setting = json_decode($setting['setting'], true);
        
        $message_id = $messageInfo['id'];
        $respInfo = array();
        
        if ($message_id) {
            $respInfo = M("twx_msgresponse")->where(
                "message_id='" . $message_id . "'")->find();
        }
        
        if ('' != $respInfo['response_info']) {
            /**
             * 转换QQ表情 [表情] -> <img >
             */
            $ruleText = '/\[.*\]/iUs';
            preg_match_all($ruleText, $respInfo['response_info'], $arr);
            if ($arr[0]) { // 匹配到表情则转换
                $qqFace = C('qqFace'); // 获取QQ表情配置
                $qqFace = json_decode($qqFace);
                
                foreach ($arr[0] as $sqlK => $sqlV) {
                    foreach ($qqFace as $configK => $configV) {
                        if ($sqlV == $configV->phrase) {
                            $faceArr[] = '/\[' . substr($sqlV, 1, - 1) . '\]/';
                            if (- 1 == $configK - 1) {
                                $imgArr[] = '<img src=' .
                                     C('TMPL_PARSE_STRING.__PUBLIC__') .
                                     '/Image/weixin2/emotion/0.gif>';
                            } else {
                                $imgArr[] = '<img src=' .
                                     C('TMPL_PARSE_STRING.__PUBLIC__') .
                                     '/Image/weixin2/emotion/' .
                                     $qqFace[$configK]->url . '>';
                            }
                        }
                    }
                }
                
                $faceRule = $faceArr; // [表情]匹配
                $faceReplace = $imgArr; // 表情图片替换
                $respText = preg_replace($faceRule, $faceReplace, 
                    $respInfo['response_info']);
            } else {
                if ($respInfo['response_class'] == 4) {
                    $card_info = M()->table("twx_card_type t1")->join(
                        'tgoods_info t2 on t1.goods_id=t2.goods_id')
                        ->where(
                        array(
                            'card_id' => $respInfo['response_info']))
                        ->field(
                        't1.id,logo_url,title,date_type,date_begin_timestamp,date_end_timestamp,date_fixed_timestamp,date_fixed_begin_timestamp,card_id,quantity,card_get_num,goods_image')
                        ->find();
                    $respText = $card_info;
                    $respText['goods_name'] = $card_info['title'];
                    $respText['goods_image_url'] = get_upload_url(
                        $card_info['goods_image']);
                    if ($card_info['date_type'] == 1) {
                        $respText['time'] = date('Y-m-d', 
                            $card_info['date_begin_timestamp']) . '至' .
                             date('Y-m-d', $card_info['date_end_timestamp']);
                    } else {
                        $respText['time'] = '领取后' .
                             $card_info['date_fixed_begin_timestamp'] . '天开始使用，' .
                             $card_info['date_fixed_timestamp'] . '天结束使用';
                    }
                } else {
                    $respText = $respInfo['response_info'];
                }
                
                if ($respInfo['batch_id']) {
                    $respInfo['response_class'] = 5;
                    $respText = explode("\r\n", $respText);
                    $respText['batch_id'] = $respInfo['batch_id'];
                }
            }
        }
        
        $this->assign('imgId', '');
        if ('1' == $respInfo['response_class']) {
            $this->assign('imgId', $respInfo['response_info']);
        }
        //为防止html页面中js因表情引起的报错，所有做出唯一标示
        $hasText = 0;
        if(!empty($respText)){
            $hasText = 1;
        }
        $this->assign('hasText',$hasText);
        $this->assign('node_id',$this->node_id);
        $this->assign('respText', $respText);
        $this->assign('setting', $setting['msg']);
        $this->assign('response_class', $respInfo['response_class']);
        $this->assign('account_type', $this->account_type);
        $this->display();
    }
    
    // 自动回复提交
    public function followSubmit() {
        $response_info = I('post.response_info','','');       //不过滤，拿到换行和空格
        //        $response_info = htmlspecialchars_decode($response_info);
        $response_type = I('post.respType'); // 消息方式 被动0 自动1
        $response_class = I('post.respClass'); // 消息类型 文本0 素材1 其他2 图片3 卡券4
        $response_startTime = I('post.startTime', '0:00:00'); // 当天开启时间段
        $response_lastTime = I('post.lastTime', '23:59:59'); // 当天结束时间段
        $response_week = I('post.week'); // 周几触发
        $response_minute = I('post.minute'); // 几分钟无回复触发
        if ('' != $response_startTime)
            $setting['startTime'] = $response_startTime;
        if ('' != $response_lastTime)
            $setting['lastTime'] = $response_lastTime;
        if ('' != $response_week)
            $setting['week'] = $response_week;
        if ('' != $response_minute)
            $setting['minute'] = $response_minute;
        
        if ($response_type == '' || $response_class == '') {
            $this->error("参数不足");
        }
        if ($response_class == 0) {
            if ('' == $response_info) {
                $this->error("请输入回复消息");
            } else {
                $qqFace = C('qqFace'); // 获取QQ表情配置
                $qqFace = json_decode($qqFace);
                
                // $response_info = 'QQ表情&lt;img
                // src=\&quot;./Home/Public/Image/weixin2/emotion/0.gif\&quot;&gt;&lt;img
                // src=\&quot;./Home/Public/Image/weixin2/emotion/28.gif\&quot;&gt;';
                // $ruleText =
                // '/\&lt\;img.*\/(\d{1,3})\.gif\\\&quot\;\&gt\;/iUs'; //本地环境
                $ruleText = '/<img.*\/(\d{1,3})\.gif">/iUs';
                
                preg_match_all($ruleText, $response_info, $arr);
                
                if ($arr[0]) {
                    foreach ($arr[1] as $key => $value) {
                        foreach ($qqFace as $key1 => $value1) {
                            if ($value == $value1->id) {
                                $response_info = str_replace('<img src="http://test.wangcaio2o.com/Home/Public/Image/weixin2/emotion/'.$value.'.gif">',$value1->phrase,$response_info);
                                $response_info = str_replace('<img src="./Home/Public/Image/weixin2/emotion/'.$value.'.gif">',$value1->phrase,$response_info);
                            }
                        }
                    }
                    /*
                    foreach ($arr[0] as $sqlK => $sqlV) {
                        foreach ($qqFace as $configK => $configV) {
                            if ($configK == $arr[1][$sqlK]) {
                                // $faceRule[] = '/\&lt\;img.*\/' .
                                // (string)((int)$arr[1][$sqlK]-1) .
                                // '\.gif\\\&quot\;\&gt\;/';
                                $faceRule[] = '/<img.*\/' . $arr[1][$sqlK] .
                                     '\.gif">/';
                                $faceReplace[] = $qqFace[$configK]->phrase;
                            }
                        }
                    }
                    $response_info = preg_replace($faceRule, $faceReplace, 
                        $response_info);
                    */
                }
            }
        }
        if ('' == $response_info) {
            $this->error("请输入回复内容");
        }
        
        $where = "node_id='" . $this->nodeId . "' and response_type='" .
             $response_type . "'";
        
        $dao = M('twx_message');
        $messageInfo = $dao->field("id")
            ->where($where)
            ->find();
        $message_id = $messageInfo['id'];
        
        $dao->startTrans();
        // 有编辑，没有添加
        do {
            if (! $messageInfo) {
                $data = array(
                    'node_id' => $this->nodeId, 
                    'response_type' => $response_type, 
                    'add_time' => date('YmdHis'), 
                    'update_time' => date('YmdHis'));
                $message_id = $dao->add($data);
                
                if ($message_id === false) {
                    $this->error("添加错误");
                }
            } else {
                $data = array(
                    'update_time' => date('YmdHis'));
                $result = $dao->where($where)->save($data);
                if ($result === false) {
                    $this->error("添加错误");
                }
                $message_id = $messageInfo['id'];
            }
            // 删除原 msgresponse表
            $result = M('twx_msgresponse')->where(
                "message_id='" . $message_id . "'")->delete();
            if (! $message_id) {
                $this->error("添加失败，message_id不存在");
            }
            // 如果有填回复内容
            if ($response_info != '') {
                // 再添加新的消息
                $data = array(
                    'message_id' => $message_id, 
                    'node_id' => $this->nodeId, 
//                    'response_info' => str_replace(PHP_EOL, '', $response_info),
                    'response_info' =>  $response_info,
                    'response_class' => $response_class,
                    'status' => 0, 
                    'add_time' => date('YmdHis'));
                if (1 == $response_type) {
                    $resultSet = M('tweixin_info')->where(
                        "node_id='" . $this->nodeId . "'")
                        ->field("setting")
                        ->find();
                    if ($resultSet) {
                        $settingJson = $this->_setJson($resultSet['setting'], 
                            null, $setting);
                    }
                    
                    if ($settingJson) {
                        $resultSet = M('tweixin_info')->where(
                            "node_id='" . $this->nodeId . "'")->save(
                            array(
                                'setting' => $settingJson));
                    }
                    
                    if (false === $resultSet) {
                        $this->error("设置失败");
                    }
                }
                if ($response_class == 5) {
                    $tmarketing_info = M('tmarketing_info')->where(
                        array(
                            'id' => $response_info))
                        ->field(
                        "id batch_id,batch_type,name,pay_status,start_time,end_time,click_count")
                        ->find();
                    $url = $this->_getLabelUrl($tmarketing_info['batch_type'], 
                        $tmarketing_info['batch_id']);
                    $data['response_class'] = 0;
                    $data['batch_id'] = $response_info;
                    $data['response_info'] = $tmarketing_info['name'] .
                         "\r\n<a href='" . $url . "'>参与活动</a>";
                } else {
                    $data['batch_id'] = '';
                }
                $result = M('twx_msgresponse')->add($data);
                if ($result === false) {
                    $this->error("添加失败");
                }
            }
            // $dao->rollback();
            $dao->commit();
        }
        while (0);
        node_log("【微信公众账号助手】被关注时自动回复设置"); // 记录日志
        $this->success("添加成功");
    }

    //删除关注微信后的自动发送消息
    public function delSendMessage(){

        $response_type = I('post.respType'); // 消息方式 被动0 自动1
        $messageId = M('twx_message')->where(array('node_id'=>$this->nodeId,'response_type'=>$response_type))->find();
        M('twx_msgresponse')->where(array('message_id'=>$messageId['id']))->save(array('response_info'=>'','response_class'=>0));

        $this->success("删除成功");
    }
    
    // 按关键词回复
    public function keywords() {
        // 初始化第一个表单，新增
        $msgListData = array(
            'add' => array(
                'actType' => 'add', 
                'title' => '新规则', 
                'message_name' => '', 
                'msgKeywords' => array(), 
                'msgResponse' => array()));
        
        // 查询出所有消息数
        $msgDao = M('twx_message');
        $where = "node_id='" . $this->nodeId .
             "' and response_type='3' and message_name <> '素材管理内容预览'";
        $msgResult = $msgDao->where($where)
            ->order("id desc")
            ->select();
        if ($msgResult) {
            $msg_ids = array_valtokey($msgResult, 'id', 'id');
            $msg_ids = implode("','", $msg_ids);
            $where = "message_id in ('" . $msg_ids . "')";
            $keywordsResult = M('twx_msgkeywords')->where($where)->select();
            $msgKeywords = array();
            if(!$keywordsResult) $keywordsResult = array();
            foreach ($keywordsResult as $v) {
                $msgKeywords[$v['message_id']][] = $v;
            }
            // 回复内容查询
            $responseResult = M('twx_msgresponse')->where($where)->select();
            // dump($responseResult);
            $msgResponse = array();
            $msgRespCnt = array();
            // 查询素材表
            $material_ids = array();
            if(!$responseResult) $responseResult = array();
            foreach ($responseResult as $v) {
                if ($v['response_class'] == '1') {
                    $material_ids[] = $v['response_info'];
                }
            }
            $material_info = D('TwxMaterial')->getMaterialInfoById(
                $material_ids, $this->nodeId, false);
            if($material_info) $material_info = array_valtokey($material_info, 'id');
            if(!$responseResult) $responseResult = array();
            foreach ($responseResult as &$v) {
                $msgRespCnt[$v['message_id']]['replyCnt'] = 0;
                $msgRespCnt[$v['message_id']]['unknow'] = 0;
                $msgRespCnt[$v['message_id']]['appMsgCnt'] = 0;
                $msgRespCnt[$v['message_id']]['picCnt'] = 0;
                $msgRespCnt[$v['message_id']]['cardCnt'] = 0;
                $msgRespCnt[$v['message_id']]['activeCnt'] = 0;
                $msgRespCnt[$v['message_id']]['wordsCnt'] = 0;
                // 素材信息
                if ($v['response_class'] == '1') {
                    $v['material_title'] = $material_info[$v['response_info']]['material_title'];
                    $v['material_img_url'] = $material_info[$v['response_info']]['img_url'];
                } elseif ($v['response_class'] == '4') {
                    $card_info = M()->table("twx_card_type t1")->join(
                        'tgoods_info t2 on t1.goods_id=t2.goods_id')
                        ->where(
                        array(
                            'card_id' => $v['response_info']))
                        ->field(
                        't1.id,logo_url,title,date_type,date_begin_timestamp,date_end_timestamp,date_fixed_timestamp,date_fixed_begin_timestamp,card_id,quantity,card_get_num,goods_image')
                        ->find();
                    $card_info['goods_name'] = $card_info['title'];
                    if ($card_info['date_type'] == 1) {
                        $card_info['time'] = date('Y-m-d', 
                            $card_info['date_begin_timestamp']) . '至' .
                             date('Y-m-d', $card_info['date_end_timestamp']);
                    } else {
                        $card_info['time'] = '领取后' .
                             $card_info['date_fixed_begin_timestamp'] . '天开始使用，' .
                             $card_info['date_fixed_timestamp'] . '天结束使用';
                    }
                    $v[] = $card_info;
                } elseif ($v['response_class'] == '0' && $v['batch_id']) {
                    $v['response_class'] = 5;
                    $v['response_info'] = explode("\r\n", $v['response_info']);
                }
                $msgResponse[$v['message_id']][] = $v; // 统计
                $msgRespCnt[$v['message_id']]['replyCnt'] ++;
                $cntType = 'unknow';
                if ($v['response_class'] == '0') {
                    $cntType = 'wordsCnt';
                } elseif ($v['response_class'] == '1') {
                    $cntType = 'appMsgCnt';
                } elseif ($v['response_class'] == '3') {
                    $cntType = 'picCnt';
                } elseif ($v['response_class'] == '4') {
                    $cntType = 'cardCnt';
                } elseif ($v['response_class'] == '5') {
                    $cntType = 'activeCnt';
                }
                $msgRespCnt[$v['message_id']][$cntType] ++;
            }
            unset($v);
            foreach ($msgResult as $v) {
                $temp = array(
                    'actType' => 'edit', 
                    'title' => '规则[' . $v['id'] . ']', 
                    'message_id' => $v['id'], 
                    'message_name' => $v['message_name'], 
                    'msgKeywords' => $msgKeywords[$v['id']], 
                    'msgResponse' => $msgResponse[$v['id']], 
                    'replyCnt' => $msgRespCnt[$v['id']]['replyCnt'] * 1, 
                    'wordsCnt' => $msgRespCnt[$v['id']]['wordsCnt'] * 1, 
                    'appMsgCnt' => $msgRespCnt[$v['id']]['appMsgCnt'] * 1, 
                    'picCnt' => $msgRespCnt[$v['id']]['picCnt'] * 1, 
                    'cardCnt' => $msgRespCnt[$v['id']]['cardCnt'] * 1, 
                    'activeCnt' => $msgRespCnt[$v['id']]['activeCnt'] * 1);
                $msgListData[] = $temp;
            }
        }
        // dump($msgListData[0]);exit;
        $this->assign('msgListData', $msgListData);
        $this->assign('account_type', $this->account_type);
        $this->display();
    }
    
    // 关键词回复提交
    public function keywordsSubmit() {
        $actType = I('actType'); // add 添加 edit 删除 delete 删除
        // 关键词
        $keywordStr = I('keywordStr', array()); // 关键词
        $matchMode = I('matchMode', array()); // 匹配模式 0 模糊，1精确
        $kwdId = I('kwdId', array()); // 关键字列表 
        $wordContent = I('wordContent', array(),''); // 回复内容,有可能是素材，也有可能是素材ID，也有可能是图片, 要看 respClass
        $respClass = I('respClass', array()); // 回复类型 0 文字 1素材 3图片
        $respId = I('respId', array()); // 回复列表
        $ruleName = I('ruleName'); // 规则名
        $message_id = I('msgId'); // 请求的message_id
        if ($actType == 'delete') {
            $message_id = I('msgId'); // 请求的message_id 校验一下是否允许操作
            $where_msg = "id='" . $message_id .
                 "' and response_type='3' and node_id='" . $this->nodeId . "'";
            $result = M('twx_message')->where($where_msg)->find();
            if (! $result) {
                $this->error("删除失败，要删除的消息不存在");
            }
            $where = "message_id='$message_id' and node_id='" . $this->nodeId .
                 "'";
            // 删除关键词表
            $result = M('twx_msgkeywords')->where($where)->delete();
            // 删除回复表
            $result = M('twx_msgresponse')->where($where)->delete();
            // 删除消息表
            $result = M('twx_message')->where($where_msg)->delete();
            node_log("【微信公众账号助手】删除关键词回复"); // 记录日志
            $this->success("删除成功");
            exit();
        }
        
        $response_type = 3;
        $response_info = $wordContent;
        $nowtime = date('YmdHis');
        // 如果提交方式是添加
        $msgDao = M('twx_message');
        $msgDao->startTrans();
        if ($actType == 'add' || $actType == 'edit') {
            if ($actType == 'add' && $message_id == '') {
                // 去除重复
                foreach ($keywordStr as $val) {
                    $count = M('twx_msgkeywords')
                        ->where(
                        array(
                            'node_id' => $this->nodeId, 
                            'key_words' => $val))
                        ->count();
                    if ($count) $this->error("保存失败，关键词" . $val . "已存在");
                }
                // 先加到 twx_message表
                $data = array(
                    'node_id' => $this->nodeId, 
                    'message_name' => $ruleName, 
                    'response_type' => $response_type, 
                    'status' => 0, 
                    'add_time' => $nowtime, 
                    'update_time' => $nowtime);
                
                $result = $msgDao->add($data);
                if (! $result) {
                    $this->error("保存失败01");
                }
                // 得到添加的message_id
                $message_id = $result;
            } else {
                // 去除重复
                foreach ($keywordStr as $val) {
                    $count = M('twx_msgkeywords')
                        ->where(
                        array(
                            'node_id' => $this->nodeId, 
                            'key_words' => $val, 
                            'message_id' => array(
                                'NEQ', 
                                $message_id)))
                        ->count();
                    if ($count) $this->error("保存失败，关键词" . $val . "已存在");
                    
                }
                
                // 校验一下是否允许编辑
                $where = "id='" . $message_id . "' and response_type='" .
                     $response_type . "' and node_id='" . $this->nodeId . "'";
                $result = $msgDao->where($where)->find();
                if (! $result) {
                    $this->error("保存失败，要编辑的消息不存在");
                }
                // 更新时间
                $result = $msgDao->where($where)->save(
                    array(
                        "message_name" => $ruleName, 
                        "update_time" => $nowtime));
                // 删除不在列表中的关键字
                $where_base = "message_id='$message_id'";
                $notInIds = implode("','", $kwdId);
                $where = $where_base . " and id not in('" . $notInIds . "')";
                $result = M('twx_msgkeywords')->where($where)->delete();
                // 删除不在列表中的回复
                $notInIds = implode("','", $respId);
                $where = $where_base . " and id not in('" . $notInIds . "')";
                $result = M('twx_msgresponse')->where($where)->delete();
            }
            // 添加或者编辑回复关键字
            foreach ($keywordStr as $k => $v) {
                // 如果没有 kwd 添加
                if (! $kwdId[$k]) {
                    $data = array(
                        'node_id' => $this->nodeId, 
                        'message_id' => $message_id, 
                        'key_words' => $keywordStr[$k], 
                        'match_type' => $matchMode[$k], 
                        'add_time' => $nowtime, 
                        'update_time' => $nowtime);
                    $result = M('twx_msgkeywords')->add($data);
                } else {
                    $data = array(
                        'key_words' => $keywordStr[$k], 
                        'match_type' => $matchMode[$k], 
                        'update_time' => $nowtime);
                    $where = "message_id='" . $message_id . "' and id='" .
                         $kwdId[$k] . "'";
                    $result = M('twx_msgkeywords')->where($where)->save($data);
                }
                if ($result === false) {
                    $msgDao->rollback();
                    $this->error("保存失败02");
                }
            }
            // 添加回复内容表
            foreach ($response_info as $k => $v) {
                // 如果没有 respId 添加
                if (! $respId[$k]) {
                    $data = array(
                        'node_id' => $this->nodeId, 
                        'message_id' => $message_id, 
                        'response_info' => $response_info[$k],
                        'response_class' => $respClass[$k], 
                        'add_time' => $nowtime, 
                        'update_time' => $nowtime);
                    if ($respClass[$k] == 5) {
                        $tmarketing_info = M('tmarketing_info')->where(
                            array(
                                'id' => $response_info[$k]))
                            ->field(
                            "id batch_id,batch_type,name,pay_status,start_time,end_time,click_count")
                            ->find();
                        $url = $this->_getLabelUrl(
                            $tmarketing_info['batch_type'], 
                            $tmarketing_info['batch_id']);
                        $data['response_class'] = 0;
                        $data['batch_id'] = $response_info[$k];
                        $data['response_info'] = $tmarketing_info['name'] .
                             "\r\n<a href='" . $url . "'>参与活动</a>";
                    } else {
                        $data['batch_id'] = '';
                    }
                    $result = M('twx_msgresponse')->add($data);
                } else {
                    $data = array(
                        'response_info' => $response_info[$k],
                        'response_class' => $respClass[$k], 
                        'update_time' => $nowtime);
                    if ($respClass[$k] == 5) {
                        $tmarketing_info = M('tmarketing_info')->where(
                            array(
                                'id' => $response_info[$k]))
                            ->field(
                            "id batch_id,batch_type,name,pay_status,start_time,end_time,click_count")
                            ->find();
                        $url = $this->_getLabelUrl(
                            $tmarketing_info['batch_type'], 
                            $tmarketing_info['batch_id']);
                        $data['response_class'] = 0;
                        $data['batch_id'] = $response_info[$k];
                        $data['response_info'] = $tmarketing_info['name'] .
                             "\r\n<a href='" . $url . "'>参与活动</a>";
                    } else {
                        $data['batch_id'] = '';
                    }
                    $where = "message_id='" . $message_id . "' and id='" .
                         $respId[$k] . "'";
                    $result = M('twx_msgresponse')->where($where)->save($data);
                }
                if ($result === false) {
                    $msgDao->rollback();
                    $this->error("保存失败03");
                }
            }
            $msgDao->commit();
            node_log("【微信公众账号助手】设置关键词回复"); // 记录日志
            $this->success("保存成功");
        }
    }
    
    // 选择图文
    public function selectImgTxt() {
        $type = I('type', 0);
        $material_type = I('material_type', '0');
        
        $dao = M('twx_material');
        $where = "node_id='" . $this->nodeId . "'";
        if ($type) {
            //
            $where .= " and type= '" . $type . "' ";
        } else {
            $where .= " and type= '0' ";
        }
        if ($material_type == '0') {
            $where .= " and material_type in ('1','2') and material_level = '1'";
        } elseif ($material_type == '3') {
            $where .= " and material_type = '3' and material_level = '0'";
        }
        
        $totalNum = $dao->where($where)->count();
        import("ORG.Util.Page"); // 导入分页类
        $Page = new Page($totalNum, 15);
        $pageShow = $Page->show();
        
        $queryData = $dao->where($where)
            ->order("id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $materialArr = array();
        if($queryData){
            foreach ($queryData as $k => $v) {
                // 处理图片
                $v['img_url'] = $this->_getImgUrl($v['material_img']);
                // 处理多图文,如果是子节点
                
                if ($v['material_type'] == '2') {
                    $sub_meterial = $dao->where(
                        array(
                            'parent_id' => $v['id']))->select();
                    if(!$sub_meterial) $sub_meterial = array();
                    foreach ($sub_meterial as &$vv) {
                        $vv['img_url'] = $this->_getImgUrl($vv['material_img']);
                    }
                    unset($vv);
                    $v['sub_material'] = $sub_meterial;
                }
                $materialArr[$v['id']] = $v;
            }
        }
        // 按单双数排序
        $materialGroupArr = array();
        $k = 0;
        foreach ($materialArr as $v) {
            $i = $k ++ % 3;
            $materialGroupArr[$i][] = $v;
        }
        $this->assign('type', $type);
        $this->assign('material_type', $material_type);
        $this->assign('materialGroupArr', $materialGroupArr);
        $this->assign('pageShow', $pageShow);
        $this->display();
    }

    private function _getImgUrl($imgname) {
        if (! $imgname) {
            return;
        }
        // 旧版
        if (basename($imgname) == $imgname) {
            return $this->uploadPath . $imgname;
        } else {
            return get_upload_url($imgname);
        }
    }
    
    // 根据ID显示图文
    public function showMaterialById() {
        $mid = I('material_id');
        // 得到图文信息
        $materialInfo = D('TwxMaterial')->getMaterialInfoById($mid, 
            $this->nodeId, true);
        $this->assign('materialInfo', $materialInfo);
        $this->display('showMaterialById');
    }
    
    // 创建标签地址
    private function _getLabelUrl($batch_type, $batch_id) {
        // http://222.44.51.34/wangcai_new/index.php?g=Label&m=News&a=index&id=634
        // 判断batch_type有效性，以及得到标签模块action名
        $m = 'Label';
        switch ($batch_type) {
            case '4': // 会员粉丝招幕(特殊处理,不需要跳到标签)
                $m = 'MemberRegistration';
                break;
        }
        $channel_id = $this->_getChannelId();
        $node_id = $this->nodeId;
        if (! $channel_id) {
            return false;
        }
        // 判断是否已经生成过标签
        $labelInfo = M('tbatch_channel')->where(
            "node_id='$node_id'
		and batch_type='$batch_type'
		and batch_id='$batch_id'
		and channel_id='$channel_id'")->find();
        if (! $labelInfo) {
            // 插入到活动标签表 batch_channel
            $labelId = M('tbatch_channel')->add(
                array(
                    'batch_type' => $batch_type, 
                    'batch_id' => $batch_id, 
                    'channel_id' => $channel_id, 
                    'add_time' => date('YmdHis'), 
                    'node_id' => $node_id, 
                    'status' => '1'));
        } else {
            $labelId = $labelInfo['id'];
        }
        if (! $labelId)
            return false;
        $url = U('Label/' . $m . '/index/', 
            array(
                'id' => $labelId), '', '', true);
        return $url;
    }
    
    // 创建并获取渠道ID
    private function _getChannelId() {
        $node_id = $this->nodeId;
        $info = M('tchannel')->where(
            array(
                'node_id' => $node_id, 
                'type' => self::CHANNEL_TYPE_WX, 
                'sns_type' => self::CHANNEL_SNS_TYPE_WX))->find();
        // 如果没有，则创建
        if (! $info) {
            $result = M('tchannel')->add(
                array(
                    'name' => '微信公众平台', 
                    'type' => self::CHANNEL_TYPE_WX, 
                    'sns_type' => self::CHANNEL_SNS_TYPE_WX, 
                    'status' => '1', 
                    'node_id' => $node_id, 
                    'add_time' => date('YmdHis')));
            if (! $result) {
                return false;
            }
            $channelId = $result;
        } else {
            $channelId = $info['id'];
        }
        if (! $channelId)
            return false;
        return $channelId;
    }
}