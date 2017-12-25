<?php

class WeixinPublishAction extends BaseAction {

    public $uploadPath;

    public function _initialize() {
        parent::_initialize();
        $this->uploadPath = './Home/Upload/Weixin/'; // 设置附件上传目录
    }
    // 发布素材
    public function index() {
        // 获取素材
        $m_id = I('m_id');
        $dao = D('TwxMaterial');
        $materialInfo = $dao->getMaterialInfoById($m_id, $this->nodeId, true);
        if (! $materialInfo) {
            $this->error("素材不存在");
        }
        $this->assign('materialInfo', $materialInfo);
        
        $menuList = $this->_getMenuList();
        $keywordsList = $this->_getKeywordsList();
        $this->assign('menuList', $menuList);
        $this->assign('keywordsList', $keywordsList);
        $this->assign('m_id', $m_id);
        $this->display();
    }
    
    // 获取自定义菜单内容(ajax)
    private function _getMenuList() {
        // 获取自定义菜单
        // 获取菜单列表
        $node_id = $this->node_id;
        $result = M('TwxMenu')->where("node_id='" . $node_id . "'")
            ->order("level,sort_id asc")
            ->getField(
            "id,id,title,level,parent_id,response_info,response_class");
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
        return $menuArr;
    }
    
    // 获取消息自动回复
    private function _getKeywordsList() {
        // 初始化第一个表单，新增
        $msgListData = array();
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
            $keywordsResult = M('twx_msgkeywords')->field(
                "id,message_id,key_words")
                ->where($where)
                ->select();
            $msgKeywords = array();
            foreach ($keywordsResult as $v) {
                $msgKeywords[$v['message_id']][] = $v['key_words'];
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
                if (! isset($msgRespCnt[$v['message_id']]['replyCnt']))
                    $msgRespCnt[$v['message_id']]['replyCnt'] = 0;
                $msgRespCnt[$v['message_id']]['replyCnt'] ++;
                $cntType = 'unknow';
                if ($v['response_class'] == '0') {
                    $cntType = 'wordsCnt';
                } elseif ($v['response_class'] == '1') {
                    $cntType = 'appMsgCnt';
                }
                if (! isset($msgRespCnt[$v['message_id']][$cntType]))
                    $msgRespCnt[$v['message_id']][$cntType] = 0;
                $msgRespCnt[$v['message_id']][$cntType] ++;
            }
            unset($v);
            
            foreach ($msgResult as $v) {
                $temp = array(
                    'actType' => 'edit', 
                    'title' => '规则[' . $v['id'] . ']', 
                    'message_id' => $v['id'], 
                    'message_name' => $v['message_name'], 
                    'msgKeywords' => implode(',', $msgKeywords[$v['id']]), 
                    'msgResponse' => $msgResponse[$v['id']], 
                    'replyCnt' => @$msgRespCnt[$v['id']]['replyCnt'] * 1, 
                    'wordsCnt' => @$msgRespCnt[$v['id']]['wordsCnt'] * 1, 
                    'appMsgCnt' => @$msgRespCnt[$v['id']]['appMsgCnt'] * 1);
                $msgListData[] = $temp;
            }
        }
        return $msgListData;
    }
    
    // 提交
    public function publishSave() {
        $m_id = I('m_id');
        $subAct = I('subAct');
        $ckid = I('ckid');
        // 进入不同的处理方式
        // 菜单
        if ($subAct == 'saveMenu') {
            $data = array(
                'response_class' => 1, 
                'response_info' => $m_id, 
                'add_time' => date('YmdHis'));
            $result = M('twx_menu')->where(
                "id='$ckid' and node_id='" . $this->nodeId . "'")->save($data);
            if (! $result) {
                $this->error("保存失败");
            } else {
                node_log("【微信公众账号助手】素材发布到菜单"); // 记录日志
                $this->success("保存成功");
            }
        } // 关键词
elseif ($subAct == 'saveKwd') {
            $data = array(
                'message_id' => $ckid, 
                'node_id' => $this->nodeId, 
                'response_class' => 1, 
                'response_info' => $m_id, 
                'update_time' => date('YmdHis'), 
                'add_time' => date('YmdHis'));
            // 判断 ckid是否有效
            $dao = M('twx_message');
            $messageInfo = $dao->field("id")
                ->where("id='$ckid' and node_id='" . $this->nodeId . "'")
                ->find();
            if (! $messageInfo) {
                $this->error("数据不存在");
            }
            // 删除旧的回复
            $result = M('twx_msgresponse')->where(
                "message_id='$ckid' and node_id='" . $this->nodeId . "'")->delete();
            $result = M('twx_msgresponse')->add($data);
            if (! $result) {
                $this->error("保存失败");
            } else {
                node_log("【微信公众账号助手】素材发布关键词"); // 记录日志
                $this->success("保存成功");
            }
        } // 自动回复,关注回复
elseif ($subAct == 'saveAuto' || $subAct == 'saveFollow') {
            $response_type = $subAct == 'saveAuto' ? '0' : '1';
            $response_class = 1;
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
            
            $response_info = $m_id;
            
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
                    $this->error("保存失败，message_id不存在");
                }
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
                    $this->error("保存失败");
                }
                // $dao->rollback();
                $dao->commit();
            }
            while (0);
            node_log("【微信公众账号助手】素材发布到自动回复"); // 记录日志
            $this->success("保存成功");
        } else {
            $this->error("未知操作类型");
        }
    }
}