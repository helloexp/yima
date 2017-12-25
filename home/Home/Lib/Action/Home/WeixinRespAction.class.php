<?php

class WeixinRespAction extends BaseAction {

    public $uploadPath;

    public function _initialize() {
        parent::_initialize();
        $this->uploadPath = './Home/Upload/Weixin/'; // 设置附件上传目录
    }
    
    // 添加被添加回复
    public function follow() {
        $response_type = I('respType', '0');
        $response_class = I('respClass', '0');
        $where = "node_id='" . $this->nodeId . "' 
				and response_type='" . $response_type . "'";
        $dao = M('twx_message');
        $messageInfo = $dao->field("id")
            ->where($where)
            ->find();
        $message_id = $messageInfo['id'];
        $respInfo = array();
        if ($message_id) {
            $respInfo = M("twx_msgresponse")->where(
                "message_id='" . $message_id . "'")->find();
            if ($respInfo) {
                $response_class = I('respClass', $respInfo['response_class']);
                if ($response_class != $respInfo['response_class']) {
                    $respInfo['response_info'] = '';
                }
            }
        }
        // 如果是文字
        if ($response_class == '0') {
            $tpl_name = 'follow_type_0';
        } // 如果是图文
elseif ($response_class == '1') {
            if ($respInfo) {
                $respInfo['material_info'] = D('TwxMaterial')->getMaterialInfoById(
                    trim($respInfo['response_info']), $this->nodeId, true);
            }
            
            $tpl_name = 'follow_type_1';
        } else {
            $this->error("未知类型");
        }
        $this->assign("respType", $response_type); // 回复类型0 被添加1消息自动回复
        $this->assign('respInfo', $respInfo);
        $this->display($tpl_name);
    }
    // 被关注时提交
    public function followSubmit() {
        $response_type = I('respType');
        $response_class = I('respClass');
        if ($response_type == '' || $response_class == '') {
            $this->error("参数不足");
        }
        $where = "node_id='" . $this->nodeId . "' 
				and response_type='" . $response_type . "'";
        $dao = M('twx_message');
        $messageInfo = $dao->field("id")
            ->where($where)
            ->find();
        $message_id = $messageInfo['id'];
        
        $response_info = I('response_info');
        
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
                    'response_info' => $response_info, 
                    'response_class' => $response_class, 
                    'status' => 0, 
                    'add_time' => date('YmdHis'));
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
    
    // 按关键词回复
    public function keywords() {
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
        $msgDao = M('twx_message');
        $where = "node_id='" . $this->nodeId . "' and response_type='3'";
        $msgResult = $msgDao->where($where)
            ->order("id desc")
            ->select();
        if ($msgResult) {
            $msg_ids = array_valtokey($msgResult, 'id', 'id');
            $msg_ids = implode("','", $msg_ids);
            $where = "message_id in ('" . $msg_ids . "')";
            $keywordsResult = M('twx_msgkeywords')->where($where)->select();
            $msgKeywords = array();
            foreach ($keywordsResult as $v) {
                $msgKeywords[$v['message_id']][] = $v;
            }
            // 回复内容查询
            $responseResult = M('twx_msgresponse')->where($where)->select();
            $msgResponse = array();
            $msgRespCnt = array();
            // 查询素材表
            $material_ids = array();
            foreach ($responseResult as $v) {
                if ($v['response_class'] == '1') {
                    $material_ids[] = $v['response_info'];
                }
            }
            $material_info = D('TwxMaterial')->getMaterialInfoById(
                $material_ids, $this->nodeId, false);
            $material_info = array_valtokey($material_info, 'id');
            
            foreach ($responseResult as &$v) {
                // 素材信息
                if ($v['response_class'] == '1') {
                    $v['material_title'] = $material_info[$v['response_info']]['material_title'];
                    $v['material_img_url'] = $material_info[$v['response_info']]['img_url'];
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
                    'title' => '规则[' . $v['id'] . ']', 
                    'message_id' => $v['id'], 
                    'message_name' => $v['message_name'], 
                    'msgKeywords' => $msgKeywords[$v['id']], 
                    'msgResponse' => $msgResponse[$v['id']], 
                    'replyCnt' => $msgRespCnt[$v['id']]['replyCnt'] * 1, 
                    'wordsCnt' => $msgRespCnt[$v['id']]['wordsCnt'] * 1, 
                    'appMsgCnt' => $msgRespCnt[$v['id']]['appMsgCnt'] * 1);
                $msgListData[] = $temp;
            }
        }
        $this->assign('msgListData', $msgListData);
        $this->display();
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
        $msgDao = M('twx_message');
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
                // 先加到 twx_message表
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
                }  // 编辑 kwdId
else {
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
                    $result = M('twx_msgresponse')->add($data);
                }  // 编辑 respId
else {
                    $data = array(
                        'response_info' => html_entity_decode($wordContent[$k]), 
                        'update_time' => $nowtime);
                    $where = "message_id='" . $message_id . "' and id='" .
                         $respId[$k] . "'";
                    $result = M('twx_msgresponse')->where($where)->save($data);
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
                node_log("【微信公众账号助手】设置关键词回复"); // 记录日志
                $this->success("保存成功");
            }
        }
    }
    
    // 选择图文
    public function selectImgTxt() {
        $dao = M('twx_material');
        $where = "node_id='" . $this->nodeId . "'";
        $where .= " and material_type in ('1','2') and material_level = '1'";
        $totalNum = $dao->where($where)->count();
        import("ORG.Util.Page"); // 导入分页类
        $Page = new Page($totalNum, 15);
        $pageShow = $Page->show();
        
        $queryData = $dao->where($where)
            ->order("id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $img_upload_path = $this->uploadPath;
        $materialArr = array();
        foreach ($queryData as $k => $v) {
            // 处理图片
            $v['img_url'] = $img_upload_path . $v['material_img'];
            // 处理多图文,如果是子节点
            
            if ($v['material_type'] == '2') {
                $sub_meterial = $dao->where(
                    array(
                        'parent_id' => $v['id']))->select();
                foreach ($sub_meterial as &$vv) {
                    $vv['img_url'] = $img_upload_path . $vv['material_img'];
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
        $this->display();
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
}