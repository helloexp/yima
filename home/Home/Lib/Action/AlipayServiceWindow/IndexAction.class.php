<?php

class IndexAction extends BaseAction {

    public $_authAccessMap = '*';

    public $CHANNEL_TYPE = "5";
    // 高级渠道
    public $CHANNEL_SNS_TYPE = "57";

    public $fwcMaterialModel = '';

    public function _initialize() {
        parent::_initialize();
        $this->fwcMaterialModel = M('tfwc_material');
    }

    public function index() {
        // to do 编号待定
        // user_act_log('进入支付宝服务窗模块','',array(
        // 'act_code'=>'2.3',
        // ));
        $newsList = M('tym_news')->where(
            array(
                'news_id' => array(
                    'in', 
                    '1359,1360,1361,1362,1363,1364')))->select();
        $fwcInfo = M('tfwc_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->assign('fwcInfo', $fwcInfo);
        $this->assign('newsList', $newsList);
        $this->display();
    }

    public function bindAccount() {
        $tfwcModel = M('tfwc_info');
        if (IS_POST) {
            $data['alipay_account'] = I('alipay_account', '', 'trim');
            $data['app_id'] = I('app_id', '', 'trim');
            $alipay_public_key = I('alipay_public_key', '', 'trim');
            $alipay_public_key = $this->_combinePublicKey($alipay_public_key); // 每64位要加"\r\n"
            $data['alipay_public_key'] = $alipay_public_key;
            $data['update_time'] = date('YmdHis');
            $data['status'] = 0;
            $where = array(
                'node_id' => $this->node_id);
            $result = $tfwcModel->where($where)->find();
            // 如果原来有记录就是更新,没有就插入
            if ($result) {
                $re = $tfwcModel->where($where)->save($data);
            } else {
                $re = $tfwcModel->where(
                    array(
                        'alipay_account' => $data['alipay_account']))->find();
                if ($re) {
                    $this->error('已存在相同的alipay账户');
                }
                $data['node_id'] = $this->node_id;
                // $data['callback_url'] = '';
                $data['add_time'] = date('YmdHis');
                $re = $tfwcModel->add($data);
            }
            if (false === $re) {
                $this->error('保存失败', '', true);
            } else {
                $this->success('保存成功', '', true);
            }
        }
        $info = $tfwcModel->where(
            array(
                'node_id' => $this->node_id))->find();
        $rsaPublicKeyPath = C('ALIPAY_FWC.merchant_public_key_file');
        $content = file_get_contents($rsaPublicKeyPath);
        $content = str_replace("-----BEGIN PUBLIC KEY-----", "", $content);
        $content = str_replace("-----END PUBLIC KEY-----", "", $content);
        $content = str_replace("\r", "", $content);
        $content = str_replace("\n", "", $content);
        $this->assign('develop_id', $content); // 开发者公钥
        $this->assign('info', $info);
        $this->assign('receivedUrl', 
            'http://' . $_SERVER['HTTP_HOST'] . U('AlipayServ/Index/index', 
                array(
                    'node_id' => $this->node_id)));
        $this->display();
    }

    /**
     * 被添加的回复
     */
    public function keyWordsResponse() {
        // 初始化第一个表单，新增
        $msgListData = array(
            'add' => array(
                'actType' => 'add', 
                'title' => '新规则', 
                'message_name' => '', 
                'msgKeywords' => array(), 
                'msgResponse' => array(), 
                'replyCnt' => 0, 
                'wordsCnt' => 0, 
                'appMsgCnt' => 0));
        
        // 查询出所有消息数
        $msgDao = M('tfwc_message');
        $where = "node_id='" . $this->nodeId . "' and response_type='3'";
        $msgResult = $msgDao->where($where)
            ->order("id desc")
            ->select();
        $length = count($msgResult);
        if ($msgResult) {
            $msg_ids = array_valtokey($msgResult, 'id', 'id');
            $msg_ids = implode("','", $msg_ids);
            $where = "message_id in ('" . $msg_ids . "')";
            $keywordsResult = M('tfwc_msgkeywords')->where($where)->select();
            $msgKeywords = array();
            foreach ($keywordsResult as $v) {
                $msgKeywords[$v['message_id']][] = $v;
            }
            // 回复内容查询
            $responseResult = M('tfwc_msgresponse')->where($where)->select();
            $msgResponse = array();
            $msgRespCnt = array();
            // 查询素材表
            $material_ids = array();
            foreach ($responseResult as $v) {
                if ($v['response_class'] == '1') {
                    $material_ids[] = $v['response_info'];
                }
            }
            $material_info = $this->_getMaterialInfo($material_ids, '', false);
            // $material_info = array_valtokey($material_info,'id');
            
            foreach ($responseResult as &$v) {
                // 素材信息
                if ($v['response_class'] == '1') {
                    $v['material_title'] = $material_info[$v['response_info']]['material_title'];
                    $v['material_img_url'] = $material_info[$v['response_info']]['material_img'];
                }
                $msgResponse[$v['message_id']][] = $v;
                // 统计
                $msgRespCnt[$v['message_id']]['replyCnt'] ++;
                $cntType = 'unknow';
                if ($v['response_class'] == '0') {
                    $cntType = 'wordsCnt';
                } elseif ($v['response_class'] == '1') {
                    $cntType = 'appMsgCnt';
                }
                $msgRespCnt[$v['message_id']][$cntType] ++;
            }
            unset($v);
            
            foreach ($msgResult as $v) {
                $temp = array(
                    'actType' => 'edit', 
                    'title' => '规则[' . $length . ']', 
                    'message_id' => $v['id'], 
                    'message_name' => $v['message_name'], 
                    'msgKeywords' => $msgKeywords[$v['id']], 
                    'msgResponse' => $msgResponse[$v['id']], 
                    'replyCnt' => $msgRespCnt[$v['id']]['replyCnt'] * 1, 
                    'wordsCnt' => $msgRespCnt[$v['id']]['wordsCnt'] * 1, 
                    'appMsgCnt' => $msgRespCnt[$v['id']]['appMsgCnt'] * 1);
                $msgListData[] = $temp;
                $length --;
            }
        }
        
        $this->assign('msgListData', $msgListData);
        $this->display();
    }

    public function addedResponse() {
        $where = "node_id='" . $this->nodeId . "'and response_type=0";
        $dao = M('tfwc_message');
        $messageInfo = $dao->field("id")
            ->where($where)
            ->find();
        
        $message_id = $messageInfo['id'];
        $respInfo = array();
        
        if ($message_id) {
            $respInfo = M("tfwc_msgresponse")->where(
                "message_id='" . $message_id . "'")->find();
        }
        
        $respText = $respInfo['response_info'];
        $this->assign('imgId', '');
        if ('1' == $respInfo['response_class']) {
            $this->assign('imgId', $respInfo['response_info']);
        }
        $this->assign('respText', $respText);
        $this->display();
    }
    
    // 选择图文(没有筛选的)
    public function selectImgTxt() {
        $where['node_id'] = $this->nodeId;
        $where['material_type'] = array(
            'in', 
            array(
                '1', 
                '2'));
        $where['material_level'] = '1';
        $this->_getAndAssignImgTxt($where);
        $this->display();
    }
    // 选择图文(界面有筛选,编辑,删除)
    public function picTextManage() {
        $where['node_id'] = $this->nodeId;
        $where['material_type'] = array(
            'in', 
            array(
                '1', 
                '2'));
        $where['material_level'] = '1';
        if ($this->isPost()) {
            $filter_name = I('post.filter_name', '', 'trim');
            $filter_type = I('post.filter_type', 0, 'intval');
            $filter_date_start = I('post.filter_date_start', '', 'trim');
            $filter_date_last = I('post.filter_date_last', '', 'trim');
            if (! empty($filter_name)) {
                $where['material_title'] = array(
                    'like', 
                    '%' . $filter_name . '%');
            }
            if (in_array($filter_type, 
                array(
                    1, 
                    2), true)) {
                $where['material_type'] = $filter_type;
            }
            if (! empty($filter_date_start) && ! empty($filter_date_last)) {
                $where['add_time'] = array(
                    'between', 
                    array(
                        dateformat($filter_date_start, 'Ymd') . '000000', 
                        dateformat($filter_date_last, 'Ymd' . '235959')));
            }
        }
        $this->_getAndAssignImgTxt($where);
        $this->display();
    }

    /**
     * 删除图文
     */
    public function removeMaterial() {
        $materialId = I('materialId', '');
        $fwcMeterialModel = $this->fwcMaterialModel;
        $result = $fwcMeterialModel->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $materialId))->find();
        if (! $result) {
            $this->error("图文不存在");
        }
        $fwcMeterialModel->startTrans();
        if ($result['material_type'] == '2') {
            $delRe = $fwcMeterialModel->where(
                array(
                    'parent_id' => $materialId))->delete();
            if (false === $delRe) {
                $fwcMeterialModel->rollback();
                $this->error("删除子图文出错");
            }
        }
        $delRe = $fwcMeterialModel->where(
            array(
                'id' => $materialId))->delete();
        if (false === $delRe) {
            $fwcMeterialModel->rollback();
            $this->error("删除主图文出错");
        }
        $fwcMeterialModel->commit();
        $this->success('', '', true);
    }

    public function msgResponse() {
        $where = "node_id='" . $this->nodeId . "'and response_type=1";
        $dao = M('tfwc_message');
        $message_id = $dao->where($where)->getField('id');
        $setting = M('tfwc_info')->where("node_id='" . $this->nodeId . "'")
            ->field('setting')
            ->find();
        $setting = json_decode('"' . $setting['setting'] . '"', true);
        $setting = json_decode($setting, true);
        $respInfo = array();
        if ($message_id) {
            $respInfo = M("tfwc_msgresponse")->where(
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
                                $imgArr[] = '<img src="' .
                                     C('TMPL_PARSE_STRING.__PUBLIC__') .
                                     '/Image/weixin2/emotion/0.gif">';
                            } else {
                                $imgArr[] = '<img src="' .
                                     C('TMPL_PARSE_STRING.__PUBLIC__') .
                                     '/Image/weixin2/emotion/' .
                                     $qqFace[$configK]->url . '">';
                            }
                        }
                    }
                }
                
                $faceRule = $faceArr; // [表情]匹配
                $faceReplace = $imgArr; // 表情图片替换
                $respText = preg_replace($faceRule, $faceReplace, 
                    $respInfo['response_info']);
            } else {
                $respText = $respInfo['response_info'];
            }
        }
        $this->assign('imgId', '');
        if ('1' == $respInfo['response_class']) {
            $this->assign('imgId', $respInfo['response_info']);
        }
        
        $this->assign('setting', $setting['msg']);
        $this->assign('respText', $respText);
        $this->display();
    }

    /**
     * 编辑图文
     */
    public function materialEdit() {
        $material_type = I('material_type', 1); // 1单图文 2多图文
        $materialId = I('get.materialId');
        $this->_assignMaterialInfo($materialId, $material_type);
        if (IS_POST) {
            $materialId = I('post.materialId');
            $data = $this->_readyData($material_type);
            if ($materialId) { // 如果是编辑
                $this->_updateMaterial($data);
            } else { // 如果是新增
                $this->_addMaterial($data);
            }
            $this->success("编辑成功", '', true);
        }
        $this->display();
    }
    
    // 自动回复提交
    public function followSubmit() {
        $response_info = I('post.response_info', 'trim'); // 消息内容
        $response_type = I('post.respType'); // 消息方式 被动0 自动1 关键字2
        $response_class = I('post.respClass'); // 消息类型 文本0 素材1 其他2
        $response_startTime = I('post.startTime', '0:00:00'); // 当天开启时间段
        $response_lastTime = I('post.lastTime', '23:59:59'); // 当天结束时间段
        $response_week = I('post.week'); // 周几触发
        $response_minute = I('post.minute'); // 几分钟无回复触发
        $response_flag = I('post.responseFlag'); // 消息自动回复开关标记 0关 1开
        if ('' != $response_startTime)
            $setting['startTime'] = $response_startTime;
        if ('' != $response_lastTime)
            $setting['lastTime'] = $response_lastTime;
        if ('' != $response_week)
            $setting['week'] = $response_week;
        if ('' != $response_minute)
            $setting['minute'] = $response_minute;
        if ('' !== $response_flag) {
            $setting['flag'] = $response_flag;
        }
        if ($response_type == '' || $response_class == '') {
            $this->error("参数不足");
        }
        if ($response_class == 0) {
            if ($response_type != 0) {
                if (empty($response_info)) {
                    $this->error("请输入回复消息");
                }
            }
        }
        $where = "node_id='" . $this->nodeId . "'
				and response_type='" . $response_type . "'";
        
        $dao = M('tfwc_message');
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
            // 20150506
            // 为什么是直接删了再添加?而不是去改tfwc_msgresponse里面"message_id='".$message_id."'"的记录呢?这样的话生成时间不就不对了?
            // 删除原 msgresponse表
            $result = M('tfwc_msgresponse')->where(
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
                    'response_info' => $response_info, 
                    'response_class' => $response_class, 
                    'status' => 0, 
                    'add_time' => date('YmdHis'), 
                    'update_time' => date('YmdHis'));
                if (1 == $response_type) {
                    $resultSet = M('tfwc_info')->where(
                        "node_id='" . $this->nodeId . "'")
                        ->field("setting")
                        ->find();
                    // 没有填info表的情况怎么考虑的？暂时让他先绑定账户吧
                    if ($resultSet) {
                        $settingJson = $this->_setInitialMsgJson(
                            $resultSet['setting'], $setting); // 按接口要求拼出来的字符串:{\"location\":\"\",\"msg\":{\"startTime\":\"10:56:46\",\"lastTime\":\"10:56:52\",\"week\":\"1,7,\",\"minute\":\"5\",\"flag\":\"1\"}}
                    } else {
                        $this->error("请先设置账户信息");
                    }
                    if ($settingJson) {
                        $resultSet = M('tfwc_info')->where(
                            "node_id='" . $this->nodeId . "'")->save(
                            array(
                                'setting' => $settingJson));
                    }
                    
                    if (false === $resultSet) {
                        $this->error("设置失败");
                    }
                }
                $result = M('tfwc_msgresponse')->add($data);
                if ($result === false) {
                    $this->error("添加失败");
                }
            }
            $dao->commit();
        }
        while (0);
        node_log("【服务窗】被关注时自动回复设置"); // 记录日志
        $this->success("添加成功");
    }
    
    // 根据ID显示图文
    public function showMaterialById() {
        $mid = I('material_id');
        // 得到图文信息
        $materialInfo = $this->_getMaterialInfo($mid, '', false);
        $this->assign('materialInfo', $materialInfo);
        $this->display('showMaterialById');
    }
    
    // 关键词回复提交
    public function keywordsSubmit() {
        $actType = I('actType'); // add 添加 edit 删除 delete
                                 // 删除
        if ($actType == 'delete') {
            $message_id = I('msgId'); // 请求的message_id
                                      // 校验一下是否允许操作
            $where_msg = "id='" . $message_id .
                 "' and response_type='3' and node_id='" . $this->nodeId . "'";
            $result = M('tfwc_message')->where($where_msg)->find();
            if (! $result) {
                $this->error("删除失败，要删除的消息不存在");
            }
            $where = "message_id='$message_id' and node_id='" . $this->nodeId .
                 "'";
            // 删除关键词表
            $result = M('tfwc_msgkeywords')->where($where)->delete();
            // 删除回复表
            $result = M('tfwc_msgresponse')->where($where)->delete();
            // 删除消息表
            $result = M('tfwc_message')->where($where_msg)->delete();
            node_log("【服务窗】删除关键词回复"); // 记录日志
            $this->success("删除成功");
            exit();
        }
        // 关键词
        $keywordStr = I('keywordStr', array()); // 关键词
        $matchMode = I('matchMode', array()); // 匹配模式 0 模糊，1精确
                                              // 回复内容
        $wordContent = I('wordContent', array()); // 回复内容,有可能是素材，也有可能是素材ID，要看
                                                  // respClass
        $respClass = I('respClass', array()); // 回复类型 0 文字 1素材
        $kwdId = I('kwdId', array()); // 关键字列表
        $respId = I('respId', array()); // 回复列表
        $ruleName = I('ruleName'); // 规则名
        $message_id = I('msgId'); // 请求的message_id
        $nowtime = date('YmdHis');
        // 如果提交方式是添加
        $msgDao = M('tfwc_message');
        $msgDao->startTrans();
        if ($actType == 'add' || $actType == 'edit') {
            if ($actType == 'add' && $message_id == '') {
                // 去除重复
                $count = $msgDao->where(
                    array(
                        'node_id' => $this->nodeId, 
                        'message_name' => $ruleName))->count();
                if ($count) {
                    $this->error("保存失败，规则名不能复重");
                }
                // 先加到 tfwc_message表
                $data = array(
                    'node_id' => $this->nodeId, 
                    'message_name' => $ruleName, 
                    'response_type' => 3, 
                    'status' => 0, 
                    'add_time' => $nowtime, 
                    'update_time' => $nowtime);
                
                $result = $msgDao->add($data);
                if (! $result) {
                    $this->error("保存失败01");
                }
                // 得到添加的message_id
                $message_id = $result;
            }  // 如果是编辑，且 $message_id != ''
else {
                // 去除重复
                $count = $msgDao->where(
                    array(
                        'node_id' => $this->nodeId, 
                        'message_name' => $ruleName, 
                        'id' => array(
                            'NEQ', 
                            $message_id)))->count();
                if ($count) {
                    $this->error("保存失败，规则名不能复重");
                }
                
                // 校验一下是否允许编辑
                $where = "id='" . $message_id .
                     "' and response_type='3' and node_id='" . $this->nodeId .
                     "'";
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
                $result = M('tfwc_msgkeywords')->where($where)->delete();
                // 删除不在列表中的回复
                $notInIds = implode("','", $respId);
                $where = $where_base . " and id not in('" . $notInIds . "')";
                $result = M('tfwc_msgresponse')->where($where)->delete();
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
                    $result = M('tfwc_msgkeywords')->add($data);
                }  // 编辑 kwdId
else {
                    $data = array(
                        'key_words' => $keywordStr[$k], 
                        'match_type' => $matchMode[$k], 
                        'update_time' => $nowtime);
                    $where = "message_id='" . $message_id . "' and id='" .
                         $kwdId[$k] . "'";
                    $result = M('tfwc_msgkeywords')->where($where)->save($data);
                }
                if ($result === false) {
                    $msgDao->rollback();
                    $this->error("保存失败02");
                }
            }
            
            // 添加回复内容表
            foreach ($wordContent as $k => $v) {
                // 如果没有 respId 添加
                if (! $respId[$k]) {
                    $data = array(
                        'node_id' => $this->nodeId, 
                        'message_id' => $message_id, 
                        'response_info' => html_entity_decode($wordContent[$k]), 
                        'response_class' => $respClass[$k], 
                        'add_time' => $nowtime, 
                        'update_time' => $nowtime);
                    $result = M('tfwc_msgresponse')->add($data);
                }  // 编辑 respId
else {
                    $data = array(
                        'response_info' => html_entity_decode($wordContent[$k]), 
                        'update_time' => $nowtime);
                    $where = "message_id='" . $message_id . "' and id='" .
                         $respId[$k] . "'";
                    $result = M('tfwc_msgresponse')->where($where)->save($data);
                }
                if ($result === false) {
                    $msgDao->rollback();
                    $this->error("保存失败03");
                }
            }
            $result = $msgDao->commit();
            // $result = $msgDao->rollback();
            if (! $result) {
                $this->error("保存失败");
            } else {
                node_log("服务窗设置关键词回复"); // 记录日志
                $this->success("保存成功");
            }
        }
    }

    public function selfMenu() {
        $node_id = $this->nodeId;
        
        // 获取是否已经设置appid
        $wxInfo = M('tfwc_info')->where(
            array(
                'node_id' => $node_id))->find();
        if (! $wxInfo) {
            $this->error("请先进行服务窗配置", U('AlipayServiceWindow/Index/index'));
        }
        // 获取菜单列表
        $result = M('tfwc_menu')->where("node_id='" . $node_id . "'")
            ->order("level,sort_id asc")
            ->getField("id,id,title,level,parent_id");
        $menuArr = array();
        if ($result) {
            // 找到所有一级菜单
            foreach ($result as $v) {
                if ($v['level'] == '1') {
                    $menuArr[$v['id']] = $v;
                }
            }
            // 找到所有二级菜单
            foreach ($result as $v) {
                if ($v['level'] == '2') {
                    $menuArr[$v['parent_id']]['sub_menu'][] = $v;
                }
            }
        }
        $this->assign('menuArr', $menuArr);
        $this->display();
    }
    
    // 菜单排序
    public function menuSort() {
        if ($this->isPost()) {
            // $menuData = '[{id:1,children:[{id:3},{id:4}]},{id:2}]';
            $menuData = I('menuData', '', 'html_entity_decode');
            $arr = json_decode($menuData, true);
            foreach ($arr as $k => $v) {
                $result = M('tfwc_menu')->where("id='" . intval($v['id']) . "'")->save(
                    array(
                        'sort_id' => $k));
                $children = $v['children'];
                foreach ($children as $k2 => $v2) {
                    $result = M('tfwc_menu')->where(
                        "id='" . intval($v2['id']) . "'")->save(
                        array(
                            'sort_id' => $k2));
                }
            }
            node_log("服务窗菜单排序"); // 记录日志
            $this->success("排序成功");
        }
        
        // 获取菜单列表
        $node_id = $this->nodeId;
        $result = M('tfwc_menu')->where("node_id='" . $node_id . "'")
            ->order("level,sort_id asc")
            ->getField("id,id,title,level,parent_id");
        $menuArr = array();
        // 找到所有一级菜单
        foreach ($result as $v) {
            if ($v['level'] == '1') {
                $menuArr[$v['id']] = $v;
            }
        }
        // 找到所有二级菜单
        foreach ($result as $v) {
            if ($v['level'] == '2') {
                $menuArr[$v['parent_id']]['sub_menu'][] = $v;
            }
        }
        $this->assign('menuArr', $menuArr);
        $this->display();
    }
    
    // 添加菜单
    public function add() {
        $fwcMemuModel = M('tfwc_menu');
        // 如果提交，响应为ajax
        if ($this->isPost()) {
            $title = I('menu_title');
            $level = strval(I('level', '0'));
            if ($level != '1')
                $level == '2';
            $parent_id = $level == '1' ? '0' : intval(I('parent_id'));
            $response_class = I('response_class', '0'); // 响应类型0文字1图文 2链接 3-电话
                                                        // 菜单类型，out:事件型菜单；link:链接型菜单；tel:点击拨打电话
            $response_info = I('response_info');
            $response_info_img = I('response_info_img');
            $response_info_url = I('response_info_url');
            $add_time = date('YmdHis');
            // 校验有效性
            do {
                $error = '';
                if (! check_str($title, 
                    array(
                        'null' => 0, 
                        'maxlen_cn' => 10), $error)) {
                    $error = '菜单名称' . $error;
                    break;
                }
                if (! check_str($level, 
                    array(
                        'null' => 0, 
                        'inarr' => array(
                            '1', 
                            '2')), $error)) {
                    $error = '菜单级别' . $error;
                    break;
                }
                if (! check_str($parent_id, 
                    array(
                        'null' => 0), $error)) {
                    $error = '父菜单' . $error;
                    break;
                }
                if (! check_str($response_class, 
                    array(
                        'null' => 0, 
                        'inarr' => array(
                            '0', 
                            '1', 
                            '2')), $error)) {
                    $error = '回复类型' . $error;
                    break;
                }
                if ($response_class == '0' && ! check_str($response_info, 
                    array(
                        'null' => 0, 
                        'maxlen_cn' => 300), $error)) {
                    $error = '回复内容' . $error;
                    break;
                }
                if ($response_class == '1' && ! check_str($response_info_img, 
                    array(
                        'null' => 0, 
                        'strtype' => 'number'), $error)) {
                    $error = '回复图文内容' . $error;
                    break;
                }
                if ($response_class == '2' && ! check_str($response_info_url, 
                    array(
                        'null' => 0), $error)) {
                    $error = '回复链接内容' . $error;
                    break;
                }
                
                /*
                 * //校验菜单名是否重复
                 * if(M('twx_menu')->where(array('node_id'=>$this->nodeId,'title'=>$title))->count()){
                 * $error = '菜单名称已存在'; break; }
                 */
            }
            while (0);
            if ($error) {
                $this->error($error);
            }
            // 校验子菜单个数
            $count = $fwcMemuModel->where(
                array(
                    'node_id' => $this->nodeId, 
                    'parent_id' => $parent_id, 
                    'level' => $level))->count();
            if ($level == '1' && $count >= 3) {
                $this->error("最多只能设置3个一级菜单");
            }
            if ($level == '2' && $count >= 5) {
                $this->error("最多只能设置5个二级菜单");
            }
            
            if ($response_class == '1') {
                $response_info = $response_info_img;
            } elseif ($response_class == '2') {
                $response_info = $response_info_url;
            }
            
            $result = $fwcMemuModel->add(
                array(
                    'node_id' => $this->nodeId, 
                    'parent_id' => $parent_id, 
                    'level' => $level, 
                    'title' => $title, 
                    'response_class' => $response_class, 
                    'response_info' => $response_info, 
                    'add_time' => $add_time, 
                    'sort_id' => 0));
            if (! $result) {
                $this->error("添加失败");
            } else {
                node_log("【服务窗】菜单添加,菜单名：{$title}"); // 记录日志
                $this->success("添加成功");
            }
        }
        $actionTitle = '新增菜单 - ';
        $level = I('level', '1');
        if ($level != '1')
            $level == '2';
        $parent_id = $level == '1' ? '0' : intval(I('parent_id'));
        // 查询一级菜单名
        if ($level == '2') {
            $parentInfo = $fwcMemuModel->where(
                array(
                    'id' => $parent_id, 
                    'node_id' => $this->nodeId, 
                    'level' => '1'))->find();
            if (! $parentInfo) {
                $this->error("上级菜单不存在");
            }
            $actionTitle .= '<b>' . $parentInfo['title'] . '</b> - 二级菜单';
        } else {
            $actionTitle .= '一级菜单';
        }
        $this->assign('level', $level);
        $this->assign('parent_id', $parent_id);
        $this->assign('actionTitle', $actionTitle);
        $this->assign('info', array());
        $this->display();
    }
    
    // 编辑菜单
    public function edit() {
        $id = I('id');
        if (! $id) {
            $this->error("参数不足");
        }
        $info = M('tfwc_menu')->where(
            "id='" . $id . "' and node_id='" . $this->nodeId . "'")->find();
        if (! $info) {
            $this->error("菜单不存在,现在添加", U('Index/add'));
        }
        if ($info['response_class'] == '1') {
            $info['response_info_img'] = $info['response_info'];
            $info['response_info'] = '';
        } elseif ($info['response_class'] == '2') {
            $info['response_info_url'] = $info['response_info'];
            $info['response_info'] = '';
        }
        // 如果提交，响应为ajax
        if ($this->isPost()) {
            $title = I('menu_title');
            $level = I('level');
            $parent_id = $level == '1' ? '0' : I('parent_id');
            $response_class = I('response_class');
            $response_info = I('response_info');
            $response_info_img = I('response_info_img');
            $response_info_url = I('response_info_url', '', ''); // todo
            $add_time = date('YmdHis');
            
            // 校验有效性
            do {
                $error = '';
                $title_max_len = $level == '1' ? 4 : 10;
                if (! check_str($title, 
                    array(
                        'null' => 0, 
                        'maxlen_cn' => $title_max_len), $error)) {
                    $error = '菜单名称' . $error;
                    break;
                }
                if (! check_str($response_class, 
                    array(
                        'null' => 0, 
                        'inarr' => array(
                            '0', 
                            '1', 
                            '2')), $error)) {
                    $error = '回复类型' . $error;
                    break;
                }
                if ($response_class == '0' && ! check_str($response_info, 
                    array(
                        'null' => 0, 
                        'maxlen_cn' => 300), $error)) {
                    $error = '回复内容' . $error;
                    break;
                }
                if ($response_class == '1' && ! check_str($response_info_img, 
                    array(
                        'null' => 0, 
                        'strtype' => 'number'), $error)) {
                    $error = '回复图文内容' . $error;
                    break;
                }
                if ($response_class == '2' && ! check_str($response_info_url, 
                    array(
                        'null' => 0), $error)) {
                    $error = '回复链接内容' . $error;
                    break;
                }
                
                /*
                 * //校验菜单名是否重复
                 * if(M('twx_menu')->where(array('node_id'=>$this->nodeId,'title'=>$title,'id'=>array('neq',$id)))->count()){
                 * $error = '菜单名称已存在'; break; }
                 */
            }
            while (0);
            if ($error) {
                $this->error($error);
            }
            if ($response_class == '1') {
                $response_info = $response_info_img;
            } elseif ($response_class == '2') {
                $response_info = $response_info_url;
            }
            $result = M("tfwc_menu")->where(
                "id='" . $id . "' and node_id='" . $this->nodeId . "'")->save(
                array(
                    'title' => $title, 
                    'response_class' => $response_class, 
                    'response_info' => $response_info, 
                    'add_time' => $add_time));
            if ($result === false) {
                $this->error("编辑失败");
            } else {
                node_log("【服务窗】账号绑定"); // 记录日志
                $this->success("编辑成功");
            }
        }
        
        $actionTitle = '编辑菜单 - ';
        $level = $info['level'];
        $parent_id = $info['parent_id'];
        // 查询一级菜单名
        if ($level == '2') {
            $parentInfo = M('tfwc_menu')->where(
                array(
                    'id' => $parent_id, 
                    'node_id' => $this->nodeId, 
                    'level' => '1'))->find();
            if (! $parentInfo) {
                $this->error("上级菜单不存在");
            }
            $actionTitle .= '<b>' . $parentInfo['title'] . '</b> - 二级菜单';
        } else {
            $actionTitle .= '一级菜单';
        }
        
        $this->assign('level', $level);
        $this->assign('parent_id', $parent_id);
        $this->assign('actionTitle', $actionTitle);
        $this->assign('info', $info);
        $this->display('add');
    }
    
    // 菜单删除
    public function delete() {
        $id = intval(I('id'));
        if (! $id) {
            $this->error("参数不足");
        }
        $fwcMenuModel = M('tfwc_menu');
        $info = $fwcMenuModel->where(
            "id='" . $id . "' and node_id='" . $this->nodeId . "'")->find();
        if (! $info) {
            $this->error("菜单不存在");
        }
        // 查询是否有下级菜单
        if ($info['level'] == '1') {
            $count = $fwcMenuModel->where("parent_id='" . $info['id'] . "'")->count();
            if ($count > 0) {
                $this->error("存在下级菜单，不允许删除");
            }
        }
        $result = $fwcMenuModel->where(
            "id='" . $id . "' and node_id='" . $this->nodeId . "'")->delete();
        node_log("【服务号】菜单删除"); // 记录日志
        $this->success("删除成功");
    }
    
    // 发布到微信
    public function publishMenu() {
        $wxInfo = M('tfwc_info')->where("node_id='" . $this->nodeId . "'")->find();
        if (! $wxInfo) {
            $this->error("商户未配置服务窗，请先设置服务窗", U("Index/index"));
        }
        // 组装准备发送的json
        $menuModel = M('tfwc_menu');
        $result = $menuModel->where("node_id='" . $this->nodeId . "'")
            ->order("level,sort_id asc")
            ->getField(
            "id,id,title,level,parent_id,response_class,response_info");
        $menuArr = array();
        // 找到所有一级菜单
        foreach ($result as $v) {
            if ($v['level'] == '1') {
                $id = intval($v['id']);
                if (count($menuArr) >= 3) {
                    $this->error("最多允许3个一级菜单");
                    break;
                }
                $menuArr[$id] = ($v['response_class'] == 2 ? array(
                    'name' => $v['title'], 
                    'actionType' => 'link', 
                    'actionParam' => html_entity_decode($v['response_info'])) : array(
                    'name' => $v['title'], 
                    'actionType' => 'out', 
                    'actionParam' => 'MENU_' . $v['id']));
                $menuData = array(
                    'action_param' => '');
                if ($v['response_class'] != 2) {
                    $menuData['action_param'] = 'MENU_' . $v['id'];
                }
                $menuModel->where(
                    array(
                        'id' => $v['id']))->save($menuData);
            }
        }
        if (count($menuArr) == 0) {
            $this->error("至少要设置一个菜单");
            return;
        }
        // 找到所有二级菜单
        foreach ($result as $v) {
            if ($v['level'] == '2' && isset($menuArr[$v['parent_id']])) {
                // 如果有子菜单,清空一级菜单除name以外的其他参数
                $title = $menuArr[$v['parent_id']]['name'];
                $menuArr[$v['parent_id']] = array();
                $menuArr[$v['parent_id']]['name'] = $title;
                // 清空menu表里父菜单的action_param
                $menuData = array(
                    'action_param' => '');
                $menuModel->where(
                    array(
                        'id' => $v['parent_id']))->save($menuData);
            }
        }
        
        foreach ($result as $v) {
            if ($v['level'] == '2' && isset($menuArr[$v['parent_id']])) {
                $menuArr[$v['parent_id']]['subButton'][] = ($v['response_class'] ==
                     2 ? array(
                        'name' => $v['title'], 
                        'actionType' => 'link', 
                        'actionParam' => html_entity_decode($v['response_info'])) : array(
                        'name' => $v['title'], 
                        'actionType' => 'out', 
                        'actionParam' => 'MENU_' . $v['id']));
                $menuData = array(
                    'action_param' => '');
                if ($v['response_class'] != 2) {
                    $menuData['action_param'] = 'MENU_' . $v['id'];
                }
                $menuModel->where(
                    array(
                        'id' => $v['id']))->save($menuData);
            }
        }
        foreach ($menuArr as $j => $val) {
            if (isset($val['subButton']) && count($val['subButton']) > 5) {
                $this->error('菜单:' . $menuArr[$j]['name'] . " 最多允许5个子级菜单");
                break;
            }
        }
        $menuArr = array(
            'button' => array_values($menuArr));
        $json = json_encode($menuArr);
        $service = D('AlipayFwc', 'Service');
        $service->init($wxInfo['app_id'], $wxInfo['node_id'], 
            $wxInfo['alipay_public_key'], $wxInfo['setting']);
        $ret = $service->getMenu();
        if ($ret['menu_content']) {
            $ret = $service->updateMenu($json);
        } else {
            $ret = $service->addMenu($json);
        }
        if (true === $ret) {
            log_write("菜单发布成功");
            $this->success('发布成功', '', true);
        } else {
            log_write("菜单发布错误:");
            $this->error('发布错误', '', true);
        }
    }

    public function getActivityLabelUrl() {
        $batch_type = I('batch_type');
        $batch_id = I('batch_id');
        
        $url = $this->_getLabelUrl($batch_id, $batch_type);
        $this->success(array(
            'url' => $url), '', true);
    }

    /**
     * 群发功能
     */
    public function groupSend() {
        
        // 获取是否已经设置appid
        $fwcInfo = M('tfwc_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        if (! $fwcInfo) {
            $this->error("请先进行服务窗配置", U('AlipayServiceWindow/Index/index'));
        }
        
        // 查询服务号本月是否已经发满todo
        // $mass_premonth = C('WEIXIN_MASS_PREMONTH');
        // $mass_maxnum = C('WEIXIN_MASS_MAXNUM');
        // $count = M('tfwc_msgbatch')->where("node_id = '{$this->node_id}' and
        // user_id = '{$this->node_wx_id}' and add_time like
        // '".date('Ym')."%'")->count();
        
        if ($this->isPost()) {
            set_time_limit(0);
            // 数据校验
            $reply_type = I('reply_type', 0, 'trim,intval');
            $reply_text = I('reply_text', 0, 'trim');
            $material_id = I('material_id', 0, 'intval');
            // 图文素材
            if ($reply_type == 2) {
                $material = $this->fwcMaterialModel->find($material_id);
                if (! $material || $material['node_id'] != $this->node_id) {
                    $this->error('素材错误');
                }
                $reply_text = $material_id;
            }
            // 插入批量发送主表
            $data = array(
                'app_id' => $fwcInfo['app_id'], 
                'user_id' => $this->user_id, 
                'node_id' => $this->node_id, 
                'add_time' => date('YmdHis'), 
                'msg_type' => $reply_type, 
                'msg_info' => $reply_text);
            $msgbatchModel = M('tfwc_msgbatch');
            $batch_msg_id = $msgbatchModel->add($data);
            if ($batch_msg_id === false) {
                log_write("插入批量发送表错误！语句：" . M()->_sql());
                $this->error('批量发送初始化失败！');
            }
            // 保存记录，如果发送失败，则删除批次记录！
            $fwcServe = D('AlipayFwc', 'Service');
            $fwcServe->init($fwcInfo['app_id'], $fwcInfo['node_id'], 
                $fwcInfo['alipay_public_key'], $fwcInfo['setting']);
            $result = $fwcServe->customSend($batch_msg_id);
            if (false === $result || $result['code'] != '200') {
                log_write('服务窗群发失败'); // 记录日志
                $this->error('发送失败！', '', true);
            } else {
                log_write('服务窗群发成功'); // 记录日志
                $this->success('发送成功！', '', true);
            }
        }
        $this->display();
    }

    /**
     * 已发送的群发消息
     */
    public function alreadySent() {
        $dao = M('tfwc_msgbatch');
        $where = array(
            'node_id' => $this->node_id);
        import('ORG.Util.Page'); // 导入分页类
        $count = $dao->where($where)
            ->order('batch_id desc')
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        
        $list = $dao->where($where)
            ->order('batch_id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        foreach ($list as $k => $v) {
            if ($v['msg_type'] != 0) { // 不是文本的时候
                $material = json_decode($v['msg_info'], true);
                $this->assign('material', $material);
                $list[$k]['msg_info'] = $this->fetch('picText');
            }
        }
        $this->assign('list', $list);
        $this->assign('page', $pageShow);
        $this->display();
    }

    /**
     * 消息管理
     */
    public function messageManage() {
        $star = I('star', 0, 'intval');
        $where = array(
            'a.node_id' => $this->node_id, 
            'a.msg_sign' => 0, 
            'a.msg_time' => array(
                'egt', 
                date('YmdHis', time() - 432000))); // 五天以内的时间
        
        $dao = M('tfwc_msg_trace');
        $hide_flag = I('hide_flag', 0, 'intval'); // 是否隐藏关键字回复
        if ($hide_flag == 1) {
            $where['a.msg_response_flag'] = array(
                'in', 
                array(
                    0, 
                    1));
        }
        if ($star) { // 有标星的消息
            $where = array_merge($where, 
                array(
                    'a.star' => 1));
        }
        import('ORG.Util.Page'); // 导入分页类
        $count = $dao->alias('a')
            ->where($where)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        $subSql = $dao->alias('a')
            ->where($where)
            ->order('a.msg_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select(false);
        $queryList = M('tfwc_user')->alias('u')
            ->join('right join ' . $subSql . ' as m on m.open_id = u.openid')
            ->field('m.*, u.login_id')
            ->select();
        foreach ($queryList as $k => $v) {
            $responseInfoArr = $dao->where(
                array(
                    'response_msg_id' => $v['id'], 
                    'msg_response_flag' => '1'))
                ->field('msg_info')
                ->select();
            $queryList[$k]['responseInfo'] = $responseInfoArr;
        }
        $this->assign('star', $star);
        $this->assign('list', $queryList);
        $this->assign('count', $count);
        $this->assign('page', $pageShow);
        $this->assign('hide_flag', $hide_flag);
        $this->display();
    }

    /**
     * 标记消息
     */
    public function markStar() {
        $msg_id = I('msg_id', 0, '');
        if (! $msg_id) {
            $this->error('参数错误！');
        }
        $where = array(
            'node_id' => $this->node_id, 
            'msg_id' => $msg_id);
        $star = M('tfwc_msg_trace')->where($where)->getField('star');
        if (false === $star) {
            $this->ajaxReturn(false, '操作失败', 0);
        }
        $star = $star ? 0 : 1;
        $result = M('tfwc_msg_trace')->where($where)->save(
            array(
                'star' => $star));
        if ($result) {
            if (1 == $star) {
                $this->ajaxReturn(1, "收藏成功", 1);
            } else {
                $this->ajaxReturn(0, "取消成功", 1);
            }
        } else {
            $this->ajaxReturn($result, "操作失败", 0);
        }
    }

    public function userMsgReply() {
        $type = I('type', 0, 'intval'); // 0 指定消息回复 1 直接回复某人,20150514暂时只有指定消息回复
        $msg_id = I('msg_id', null, 'trim');
        $reply_text = I('reply_text', null, 'trim');
        $reply_type = I('reply_type', 0, 'intval'); // 0文本回复 2 图文回复,暂时只有文本回复
        
        if ($type !== 0 && $type !== 1)
            $this->error('参数[type]错误！');
        
        if ($reply_type !== 0 && $reply_type !== 2)
            $this->error('参数[reply_type]错误！');
            
            // 指定消息回复时，不能为空
        if ($type == 0 && $msg_id == null)
            $this->error('参数[msg_id]错误！');
        
        $model = M('tfwc_msg_trace');
        
        if ($type == 0) {
            $where = array(
                'msg_id' => $msg_id, 
                'node_id' => $this->node_id, 
                'msg_sign' => 0); // 0-接收 1-回复 2-关注 3-取消关注
            
            $info = $model->where($where)->find();
            if (! $info) {
                $this->error('参数错误！' . $model->_sql());
            }
        }
        $fwcInfo = M('tfwc_info')->where("node_id='" . $this->nodeId . "'")->find();
        if (! $fwcInfo) {
            log_write('服务窗数据错误');
            $this->error('服务窗数据错误');
        }
        // 在流水表中添加回复流水
        $data = array(
            'msg_sign' => '1', 
            'msg_type' => $reply_type, 
            'msg_info' => $reply_text, 
            'msg_time' => date('YmdHis'), 
            'msg_response_flag' => 0, 
            'response_msg_id' => $type == 0 ? $info['id'] : '',  // 暂时只会存msg_trace的id
            'node_id' => $this->node_id, 
            'app_id' => $fwcInfo['app_id'], 
            'open_id' => $info['open_id'], 
            'op_user_id' => $this->user_id);
        $model->startTrans();
        $new_msg_id = $model->add($data);
        if ($new_msg_id === false) {
            log_write("插入流水表错误！语句：" . $model->_sql());
            $model->rollback();
            exit();
        }
        $model->commit();
        $fwcServ = D('AlipayFwc', 'Service');
        $fwcServ->init($fwcInfo['app_id'], $fwcInfo['node_id'], 
            $fwcInfo['alipay_public_key'], $fwcInfo['setting']);
        $ret = $fwcServ->directSend($new_msg_id);
        if (false === $ret || $ret['code'] != '200') {
            log_write('消息回复失败'); // 记录日志
            $this->error('发送失败！', '', true);
        } else {
            log_write('消息回复成功'); // 记录日志
            $this->success('发送成功！', '', true);
        }
    }

    /**
     * 服务窗渠道数据
     */
    public function batchData() {
        $channel_id = $this->_getChannelId();
        // $_GET['channel_id'] = $channel_id;
        $model = M('tbatch_channel');
        $map = array(
            'node_id' => $this->node_id, 
            'channel_id' => $channel_id);
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = $model->where($map)
            ->order('id DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $type_name = C('BATCH_TYPE_NAME');
        $mod = M('tmarketing_info');
        if ($list) {
            foreach ($list as $k => $v) {
                $query = $mod->where(
                    array(
                        'node_id' => $v['node_id'], 
                        'id' => $v['batch_id'], 
                        'batch_type' => $v['batch_type']))->getField('name');
                $list[$k]['name'] = $query;
                // 求该batch总的访问量和中奖数
                $subSql = $model->where(
                    array(
                        'node_id' => $v['node_id'], 
                        'batch_id' => $v['batch_id'], 
                        'batch_type' => $v['batch_type']))
                    ->field(
                    'sum(click_count) as ck_count, sum(send_count) as sd_count')
                    ->select();
                $list[$k]['ck_count'] = $subSql[0]['ck_count'];
                $list[$k]['sd_count'] = $subSql[0]['sd_count'];
            }
        }
        $this->assign('arr', $type_name);
        $this->assign('query_list', $list);
        $this->assign('channel_id', $channel_id);
        $this->display();
    }

    /**
     *
     * @param int/array $materialId
     * @param unknown $type
     * @return array($materialId_1 =>
     *         array(0=>$childrenResult_0,1=>$childrenResult_1,...),
     *         $materialId_2 => array(...),...)
     */
    private function _getMaterialInfo($materialId, $type, $needCheckType = true) {
        $fwcMaterialModel = $this->fwcMaterialModel;
        $map['node_id'] = $this->node_id;
        if (empty($materialId)) {
            return array();
        }
        if (is_array($materialId)) {
            $map['id'] = array(
                'in', 
                $materialId);
        } else {
            $map['id'] = $materialId;
        }
        $result = $fwcMaterialModel->where($map)->getField(
            'id,material_title,material_img,
				material_summary,material_desc,material_link,parent_id,material_level,
				status,material_type,add_time,batch_type,batch_id');
        if (! $result) {
            return array();
        }
        foreach ($result as $id => $value) {
            if ($needCheckType && $value['material_type'] != $type) {
                $this->error('编辑图文传参有误');
            }
            $childrenResult = $fwcMaterialModel->where(
                array(
                    'parent_id' => $id))->select();
            $result[$id]['childrenMaterial'] = $childrenResult;
        }
        return $result;
    }

    /**
     *
     * @param int $batch_id
     * @param int $batch_type
     * @return int/boolean
     */
    protected function _getLabelId($batch_id = '', $batch_type = '') {
        $node_id = $this->nodeId;
        $channelId = $this->_getChannelId();
        // 如果没有，则创建
        if (! $channelId) {
            $channelId = M('tchannel')->add(
                array(
                    'name' => '服务窗', 
                    'type' => $this->CHANNEL_TYPE, 
                    'sns_type' => $this->CHANNEL_SNS_TYPE, 
                    'status' => '1', 
                    'node_id' => $node_id, 
                    'add_time' => date('YmdHis')));
        }
        $label_id = false;
        if ($channelId && $batch_id != '' && $batch_type != '') {
            $label_id = get_batch_channel($batch_id, $channelId, $batch_type, 
                $node_id); // batch_channel表的id
        }
        return $label_id;
    }

    private function _getChannelId() {
        $channelId = M('tchannel')->where(
            array(
                'node_id' => $this->nodeId, 
                'type' => $this->CHANNEL_TYPE, 
                'sns_type' => $this->CHANNEL_SNS_TYPE))->getField('id');
        return $channelId;
    }

    private function _getLabelUrl($batch_id = '', $batch_type = '') {
        $label_id = $this->_getLabelId($batch_id, $batch_type);
        $url = '';
        if ($label_id) {
            if ($batch_type != 4) {
                $url = U('Label/Label/index/', 
                    array(
                        'id' => $label_id), '', '', true);
            } else { // 会员粉丝招幕(特殊处理,不需要跳到标签)
                $config_url_arr = C('BATCH_WAP_URL');
                $url = U($config_url_arr[$batch_type], 
                    array(
                        'id' => $label_id), '', '', true);
            }
        }
        return $url;
    }

    private function _assignMaterialInfo($materialId, $material_type) {
        // 如果有传过来的materialId说明是编辑,否则是增加
        if ($materialId) {
            $materialInfo = $this->_getMaterialInfo($materialId, $material_type);
            $parentInfo = array();
            $childrenInfo = array();
            foreach ($materialInfo as $value) {
                $parentInfo = $value;
                if ($value['batch_type'] == 0 && $value['batch_id'] == 0) {
                    $parentInfo['url_type'] = 0;
                } else {
                    $parentInfo['url_type'] = 1;
                }
                $childrenInfo = $value['childrenMaterial'];
            }
            foreach ($childrenInfo as $k => $v) {
                if ($v['batch_type'] == 0 && $v['batch_id'] == 0) {
                    $childrenInfo[$k]['url_type'] = 0;
                } else {
                    $childrenInfo[$k]['url_type'] = 1;
                }
            }
        } else {
            // 当增加时初始化materialInfo数据todo
            $parentInfo = array(
                'url_type' => 1);
            $childrenInfo = array();
            if ($material_type == '2') {
                $childrenInfo = array(
                    'url_type' => 1);
            }
        }
        $this->assign('materialInfo', $parentInfo);
        $this->assign('info_sub_material', $childrenInfo);
        $this->assign('material_type', $material_type);
    }

    private function _readyData($material_type = 1) {
        $dataArr = I('dataJson', '', '');
        if (! $dataArr) {
            $this->error("提交数据错误");
        }
        $fwcMatetialModel = $this->fwcMaterialModel;
        $data = array();
        foreach ($dataArr as $k => $v) {
            $v = json_decode($v, true);
            // 批量处理
            $material_id = $v['input_i-id'];
            $material_title = $v['input_i-title'];
            $material_link = $v['input_i-url'];
            $material_img = get_upload_url($v['input_i-material_img']);
            $material_summary = $v['input_i-summary'];
            $url_type = $v['input_i-url_type'];
            $batch_id = $v['input_i-batch_id'];
            $batch_type = $v['input_i-batch_type'];
            $material_desc = $v['input_i-material_desc'];
            if ($url_type === '1') {
                // 获取链接地址
                if ($batch_type && $batch_id) {
                    $material_link = $this->_getLabelUrl($batch_id, $batch_type);
                    if (! $material_link) {
                        $this->error("营销活动标签地址生成失败");
                    }
                }
            } else {
                $batch_id = 0;
                $batch_type = 0;
                $material_desc = '';
            }
            // 添加到素材表，素材级别为1多级根节点
            $data[] = array(
                'id' => $material_id, 
                'node_id' => $this->nodeId, 
                'material_title' => $material_title, 
                'material_img' => $material_img, 
                'material_summary' => $material_summary, 
                'material_desc' => $material_desc, 
                'material_link' => $material_link, 
                'material_level' => $k == 0 ? 1 : 2, 
                'material_type' => $material_type, 
                'parent_id' => 0, 
                'batch_id' => $batch_id, 
                'batch_type' => $batch_type, 
                'add_time' => date('YmdHis'));
        }
        return $data;
    }

    private function _addMaterial($data) {
        $materialModel = $this->fwcMaterialModel;
        $materialModel->startTrans();
        $parentId = 0;
        foreach ($data as $key => $value) {
            if ($key !== 0) {
                $value['parent_id'] = $parentId;
            }
            $id = $materialModel->add($value);
            if (! $id) {
                $materialModel->rollback();
                $this->error('图文保存失败');
            }
            if ($key === 0) {
                $parentId = $id;
            }
        }
        $materialModel->commit();
    }

    private function _updateMaterial($data) {
        $materialModel = $this->fwcMaterialModel;
        $materialModel->startTrans();
        $parentId = $data[0]['id'];
        $result = $materialModel->where(
            array(
                'id' => $parentId))->save($data[0]);
        if (false === $result) {
            $materialModel->rollback();
            $this->error('更新主图文失败');
        }
        $idArr = array();
        if ($data[0]['material_type'] == 2) {
            $idArr = $materialModel->where(
                array(
                    'parent_id' => $parentId))->getField('id', true);
        }
        if (! is_array($idArr)) {
            $idArr = array(
                $idArr);
        }
        unset($data[0]);
        foreach ($data as $value) {
            $searchResult = array_search($value['id'], $idArr);
            $value['parent_id'] = $parentId;
            if ($searchResult !== false) {
                $result = $materialModel->where(
                    array(
                        'id' => $value['id']))->save($value);
                if (false === $result) {
                    $materialModel->rollback();
                    $this->error('更新子图文失败');
                }
                unset($idArr[$searchResult]);
            } else {
                $result = $materialModel->add($value);
                if (false === $result) {
                    $materialModel->rollback();
                    $this->error('更新新增子图文失败');
                }
            }
        }
        if (count($idArr) > 0) {
            $result = $materialModel->where(
                array(
                    'id' => array(
                        'in', 
                        $idArr)))->delete();
            if (false === $result) {
                $materialModel->rollback();
                $this->error('删除原有子图文失败');
            }
        }
        $materialModel->commit();
    }

    private function _getAndAssignImgTxt($where) {
        $dao = $this->fwcMaterialModel;
        $totalNum = $dao->where($where)->count();
        import("ORG.Util.Page"); // 导入分页类
        $Page = new Page($totalNum, 15);
        $pageShow = $Page->show();
        $queryData = $dao->where($where)
            ->order("id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $materialArr = array();
        foreach ($queryData as $k => $v) {
            // 处理图片
            $v['img_url'] = $v['material_img'];
            // 处理多图文,如果是子节点
            if ($v['material_type'] == '2') {
                $sub_meterial = $dao->where(
                    array(
                        'parent_id' => $v['id']))->select();
                foreach ($sub_meterial as &$vv) {
                    $vv['img_url'] = $vv['material_img'];
                }
                unset($vv);
                $v['sub_material'] = $sub_meterial;
            }
            $materialArr[$v['id']] = $v;
        }
        // 按单双数排序
        $materialGroupArr = array();
        $k = 0;
        foreach ($materialArr as $v) {
            $i = $k ++ % 2;
            $materialGroupArr[$i][] = $v;
        }
        $this->assign('materialGroupArr', $materialGroupArr);
        $this->assign('pageShow', $pageShow);
    }

    private function _combinePublicKey($alipay_public_key) {
        $alipay_public_key = str_replace('-----BEGIN PUBLIC KEY-----', '', 
            $alipay_public_key);
        $alipay_public_key = str_replace('-----END PUBLIC KEY-----', '', 
            $alipay_public_key);
        $str_len = strlen($alipay_public_key);
        $newStr = "-----BEGIN PUBLIC KEY-----\r\n";
        for ($i = 0; $i < $str_len; $i = $i + 64) {
            $newStr .= substr($alipay_public_key, $i, 64) . "\r\n";
        }
        $newStr .= "-----END PUBLIC KEY-----";
        return $newStr;
    }

    private function _setInitialMsgJson($oldSetting = null, $msg = null) {
        $location = '\"\"';
        if ($oldSetting) {
            $locationStart = 14; // {\"location\":的后一位
            $locationEnd = strpos($oldSetting, ',\"msg\":');
            $location = substr($oldSetting, $locationStart, 
                ($locationEnd - $locationStart));
        }
        $setting = '';
        if ($msg !== null) {
            $msg = json_encode($msg);
            $msg = json_encode($msg);
            $msg = substr($msg, 1);
            $msg = substr($msg, 0, ($msg . length - 1));
            $setting = '{\"location\":' . $location . ',\"msg\":' . $msg . '}';
        }
        return $setting;
    }
}