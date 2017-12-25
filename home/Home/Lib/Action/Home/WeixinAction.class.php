<?php

class WeixinAction extends BaseAction {

    public $uploadPath;

    private $node_weixin_code;

    private $node_wx_id;

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
        // C('LABEL_ADMIN',include(CONF_PATH.'LabelAdmin/config.php'));
    }
    
    // 微信账号绑定
    public function index() {
        user_act_log('进入微信助手模块', '', 
            array(
                'act_code' => '2.3'));
        $is_show = I('is_show', 1, 'intval');
        $info = array(
            'callback_url' => U('WeixinServ/Index/index', 
                array(
                    'node_id' => $this->nodeId), '', '', true));
        $resultInfo = M('tweixin_info')->where(
            "node_id='" . $this->nodeId . "'")->find();
        if ($resultInfo) {
            $info = array_merge($info, $resultInfo);
        }
        
        if (! checkUserRights($this->node_id, C('MEMBER_CHARGE_ID'))) {
            $this->assign('nopower', true);
        }
        
        $this->assign('configFlag', 
            $resultInfo['weixin_code'] == '' && $is_show == 1 ? 1 : 0);
        $this->assign('info', $info);
        $this->display();
    }
    
    // 微信账户绑定提交
    public function bindSubmit() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            
            user_act_log('【微信公众账号助手】设置微信公众号账号', print_r($_POST, true), 
                array(
                    'act_code' => '2.3.1'));
            
            $callback_url = U('WeixinServ/Index/index', 
                array(
                    'node_id' => $this->nodeId), '', '', true);
            $info = M('tweixin_info')->where("node_id='" . $this->nodeId . "'")->find();
            // 如果未设置微信号
            if (! $info) {
                $data = array(
                    'node_id' => $this->nodeId, 
                    'weixin_code' => I('POST.weixin_code'), 
                    'token' => I('POST.token'), 
                    'callback_url' => $callback_url, 
                    'account_type' => I('account_type'), 
                    'add_time' => date('YmdHis'));
                $result = M('tweixin_info')->add($data);
            } else {
                $data = array(
                    'weixin_code' => I('POST.weixin_code'), 
                    'token' => I('POST.token'), 
                    'callback_url' => $callback_url, 
                    'account_type' => I('account_type'), 
                    'update_time' => date('YmdHis'));
                $result = M('tweixin_info')->where("id='" . $info['id'] . "'")->save(
                    $data);
            }
            if ($result === false) {
                $this->error("保存失败");
            }
            // 获取授权token
            $app_id = I('app_id');
            $app_secret = I('app_secret');
            $account_type = I('account_type');
            if ($account_type != '1' && ($app_id || $app_secret)) {
                // 校验并获取 token
                $service = D('WeiXinMenu', 'Service');
                $service->init($app_id, $app_secret);
                $resultToken = $service->getToken();
                if (! $resultToken || ! $resultToken['access_token']) {
                    $this->error(
                        '获取token失败:[' . $resultToken['errcode'] . ']' .
                             $resultToken['errmsg']);
                }
                $app_access_token = $resultToken['access_token'];
                
                // 更新数据库
                $result = M('tweixin_info')->where(
                    "node_id='" . $this->nodeId . "'")->save(
                    array(
                        'app_id' => $app_id, 
                        'app_secret' => $app_secret, 
                        'app_access_token' => $app_access_token));
            } else {
                // 更新数据库
                $result = M('tweixin_info')->where(
                    "node_id='" . $this->nodeId . "'")->save(
                    array(
                        'app_id' => '', 
                        'app_secret' => '', 
                        'app_access_token' => ''));
            }
            
            if ($result === false) {
                $this->error("保存失败");
            } else {
                node_log("【微信公众账号助手】微信账号绑定", print_r($_POST, true)); // 记录日志
                $this->success("保存成功");
            }
        }
    }
    
    // 生成微信token
    public function generateToken() {
        $user = D('User', 'Service');
        $weixin_code = I('weixin_code');
        if ($weixin_code == '') {
            $this->error("微信账号不能为空");
        }
        $token = md5($weixin_code . time());
        
        $this->success(array(
            'token' => $token));
    }
    
    // 添加素材
    public function materialAdd() {
        $type = I('type', 1);
        
        // 判断是活动信息
        $batch_type = I('batch_type');
        $batch_id = I('batch_id');
        if ($batch_type && $batch_id) {
            $materialInfo = array();
            $materialInfo['batch_type'] = $batch_type;
            $materialInfo['batch_id'] = $batch_id;
            $materialInfo['url_type'] = '1';
            $batchInfo = $this->_getBatchInfo($batch_type, $batch_id);
            
            // 获取活动类型
            $type_name = $this->_getBatchType($batch_type);
            $materialInfo['material_desc'] = $type_name . ' ＞ ' .
                 $batchInfo['name'];
            $this->assign('materialInfo', $materialInfo);
        }
        if ($type == '1') {
            $this->display('materialAdd_type_1');
        } else {
            $this->display('materialAdd_type_2');
        }
    }
    
    // 素材添加提交
    public function materialAddSubmit() {
        $material_type = I('input_i-type');
        // 添加单图文
        if ($material_type == '1') {
            $material_title = I('input_i-title');
            $material_link = I('input_i-url');
            $material_img = I('input_i-material_img');
            $material_desc = I('input_i-material_desc');
            $url_type = I('input_i-url_type');
            $batch_id = I('input_i-batch_id');
            $batch_type = I('input_i-batch_type');
            $dao = M('twx_material');
            $dao->startTrans();
            // 校验batch_id，batch_type有效性
            if ($url_type == '1' && $batch_id && $batch_type) {
                switch ($batch_type) {
                    case '1':
                        $batch_table = 'activity';
                        break;
                    case '2':
                        $batch_table = 'activity';
                        break;
                }
                $batch_info = M($batch_table)->where(
                    "node_id='" . $this->nodeId . "' and batch_id='" . $batch_id .
                         "'");
                if (! $batch_info) {
                    $this->error("营销活动不存在");
                }
                $material_desc = I('input_i-material_desc');
                // 获取链接地址
                $material_link = $this->_getLabelUrl($batch_type, $batch_id);
                if (! $material_link) {
                    $this->error("营销活动标签地址生成失败");
                }
            }  // 如果是人工链接
else {
                $batch_id = 0;
                $batch_type = 0;
                $material_desc = '';
            }
            
            // 添加到素材表，素材级别为1多级根节点
            $data = array(
                'node_id' => $this->nodeId, 
                'material_title' => $material_title, 
                'material_img' => $material_img, 
                'material_summary' => $material_summary, 
                'material_desc' => $material_desc, 
                'material_link' => $material_link, 
                'parent_id' => '0', 
                'material_level' => '1', 
                'material_type' => $material_type, 
                'batch_id' => $batch_id, 
                'batch_type' => $batch_type, 
                'add_time' => date('YmdHis'));
            $m_id = $dao->add($data);
            if ($m_id === false) {
                $dao->rollback();
                $this->error("添加失败");
            }
        } // 如果是多图文
elseif ($material_type == '2') {
            $dataArr = I('dataJson');
            if (! $dataArr) {
                $this->error("提交数据错误");
            }
            
            $dao = M('twx_material');
            $dao->startTrans();
            $parent_id = 0;
            foreach ($dataArr as $k => $v) {
                $v = json_decode(htmlspecialchars_decode($v), true);
                // 批量处理
                foreach ($v as &$_vv) {
                    $_vv = htmlspecialchars($_vv);
                }
                unset($_vv);
                $material_title = $v['input_i-title'];
                $material_link = $v['input_i-url'];
                $material_img = $v['input_i-material_img'];
                $material_summary = $v['input_i-summary'];
                
                $url_type = $v['input_i-url_type'];
                $batch_id = $v['input_i-batch_id'];
                $batch_type = $v['input_i-batch_type'];
                // 校验batch_id，batch_type有效性
                if ($url_type == '1' && $batch_id) {
                    switch ($batch_type) {
                        case '1':
                            $batch_table = 'activity';
                            break;
                        case '2':
                            $batch_table = 'activity';
                            break;
                    }
                    $batch_info = M($batch_table)->where(
                        "node_id='" . $this->nodeId . "' and batch_id='" .
                             $batch_id . "'");
                    if (! $batch_info) {
                        $this->error("营销活动不存在");
                    }
                    $material_desc = $v['input_i-material_desc'];
                    
                    // 获取链接地址
                    $material_link = $this->_getLabelUrl($batch_type, $batch_id);
                    if (! $material_link) {
                        $this->error("营销活动标签地址生成失败");
                    }
                }  // 如果是人工链接
else {
                    $batch_id = 0;
                    $batch_type = 0;
                    $material_desc = '';
                }
                
                // 添加到素材表，素材级别为1多级根节点
                $data = array(
                    'node_id' => $this->nodeId, 
                    'material_title' => $material_title, 
                    'material_img' => $material_img, 
                    'material_summary' => $material_summary, 
                    'material_desc' => $material_desc, 
                    'material_link' => $material_link, 
                    'parent_id' => $parent_id, 
                    'material_level' => $k == 0 ? 1 : 2, 
                    'material_type' => $material_type, 
                    'batch_id' => $batch_id, 
                    'batch_type' => $batch_type, 
                    'add_time' => date('YmdHis'));
                $id = $dao->add($data);
                if ($id === false) {
                    $dao->rollback();
                    $this->error("添加失败");
                }
                $parent_id = $k == 0 ? $id : $parent_id;
                $m_id = $parent_id;
            }
        } else {
            $this->error("未知素材类型" . $type);
        }
        // $dao->rollback();
        tag('view_end');
        $dao->commit();
        
        // 如果是保存并设置，就跳转到设置中去，带上素材号
        if (I('subAct') == 'saveAndSet') {
            $url = U('Weixin/WeixinPublish/index', 
                array(
                    'm_id' => $m_id));
        } else {
            $url = U('Weixin/Weixin/materialImgTxtManage');
        }
        node_log("【微信公众账号助手】素材添加，素材ID号:{$m_id}"); // 记录日志
        $this->success("添加成功", array(
            'gourl' => $url), true);
    }
    
    // 编辑素材
    public function materialEdit() {
        $id = I('id');
        $dao = D('TwxMaterial');
        $materialInfo = $dao->getMaterialInfoById($id, $this->nodeId, true);
        if (! $materialInfo) {
            $this->error("素材不存在！");
        }
        // 地址类型
        if ($materialInfo['batch_type'] == '0') {
            $materialInfo['url_type'] = 0;
        } else {
            $materialInfo['url_type'] = 1;
        }
        $type = $materialInfo['material_type'];
        $this->assign('materialInfo', $materialInfo);
        $this->assign('act', 'edit');
        if ($type == '1') {
            $this->display('materialAdd_type_1');
        } else {
            $this->display('materialAdd_type_2');
        }
    }
    
    // 素材编辑提交
    public function materialEditSubmit() {
        $material_type = I('input_i-type');
        $m_id = $id = I('id');
        if (! $id) {
            $this->error("素材不存在");
        }
        $dao = M('twx_material');
        $count = $dao->where(
            "id='" . $id . "' and node_id='" . $this->nodeId . "'")->count();
        if (! $count) {
            $this->error("素材不存在");
        }
        // 编辑单图文
        if ($material_type == '1') {
            $material_title = I('input_i-title');
            $material_link = I('input_i-url');
            $material_img = I('input_i-material_img');
            $material_summary = I('input_i-summary');
            $batch_type = I('input_i-batch_type');
            $batch_id = I('input_i-batch_id');
            $url_type = I('input_i-url_type');
            // 校验batch_id，batch_type有效性
            if ($url_type == '1' && $batch_id) {
                $batch_info = $this->_getBatchInfo($batch_type, $batch_id);
                if (! $batch_info) {
                    $this->error("营销活动不存在");
                }
                $material_desc = I('input_i-material_desc');
                
                // 获取链接地址
                $material_link = $this->_getLabelUrl($batch_type, $batch_id);
                if (! $material_link) {
                    $this->error("营销活动标签地址生成失败");
                }
            }  // 如果是人工链接
else {
                $batch_id = 0;
                $batch_type = 0;
                $material_desc = '';
            }
            $dao = M('twx_material');
            $dao->startTrans();
            // 根据fileId取图片素材地址
            // 更新到素材表，素材级别为1多级根节点
            $data = array(
                'material_title' => $material_title, 
                'material_img' => $material_img, 
                'material_summary' => $material_summary, 
                'material_desc' => $material_desc, 
                'material_link' => $material_link, 
                'batch_type' => $batch_type, 
                'batch_id' => $batch_id);
            $result = $dao->where(
                "id='" . $id . "' and node_id='" . $this->nodeId . "'")->save(
                $data);
            if ($result === false) {
                $dao->rollback();
                $this->error("添加失败");
            }
        } // 如果是多图文
elseif ($material_type == '2') {
            $dataArr = I('dataJson');
            if (! $dataArr) {
                $this->error("提交数据错误");
            }
            
            $dao = M('twx_material');
            $dao->startTrans();
            $parent_id = $id;
            // 删除不在列表中的记录
            $subIdArr = array();
            foreach ($dataArr as $k => $v) {
                $v = json_decode(htmlspecialchars_decode($v), true);
                $subIdArr[] = $v['input_i-id'];
            }
            $subIdList = implode("','", $subIdArr);
            $result = $dao->where(
                "node_id='" . $this->nodeId .
                     "' and parent_id='$id' and id not in ('" . $subIdList . "')")->delete();
            // 结束删除
            foreach ($dataArr as $k => $v) {
                $v = json_decode(htmlspecialchars_decode($v), true);
                // 批量处理
                foreach ($v as &$_vv) {
                    $_vv = htmlspecialchars($_vv);
                }
                unset($_vv);
                $material_title = $v['input_i-title'];
                $material_link = $v['input_i-url'];
                $material_img = $v['input_i-material_img'];
                $material_summary = $v['input_i-summary'];
                $m_id = $v['input_i-id'];
                $url_type = $v['input_i-url_type'];
                $batch_id = $v['input_i-batch_id'];
                $batch_type = $v['input_i-batch_type'];
                // 校验batch_id，batch_type有效性
                if ($url_type == '1' && $batch_id) {
                    $batch_info = $this->_getBatchInfo($batch_type, $batch_id);
                    
                    if (! $batch_info) {
                        $this->error("营销活动不存在");
                    }
                    
                    $material_desc = $v['input_i-material_desc'];
                    
                    // 获取链接地址
                    $material_link = $this->_getLabelUrl($batch_type, $batch_id);
                    if (! $material_link) {
                        $this->error("营销活动标签地址生成失败");
                    }
                }  // 如果是人工链接
else {
                    $batch_id = 0;
                    $batch_type = 0;
                    $material_desc = '';
                }
                
                // 添加到素材表，素材级别为1多级根节点
                $data = array(
                    'material_title' => $material_title, 
                    'material_img' => $material_img, 
                    'material_summary' => $material_summary, 
                    'material_desc' => $material_desc, 
                    'material_link' => $material_link, 
                    'batch_id' => $batch_id, 
                    'batch_type' => $batch_type);
                // 如果是新增
                if ($m_id == '') {
                    $data = array_merge($data, 
                        array(
                            'node_id' => $this->nodeId, 
                            'parent_id' => $parent_id, 
                            'material_level' => 2, 
                            'material_type' => $material_type, 
                            'add_time' => date('YmdHis')));
                }
                if ($m_id == '') {
                    $result = $dao->add($data);
                } else {
                    $result = $dao->where("id='" . $m_id . "'")->save($data);
                }
                if ($result === false) {
                    $dao->rollback();
                    $this->error("编辑失败");
                }
            }
        } else {
            $this->error("未知素材类型" . $type);
        }
        // $dao->rollback();
        $dao->commit();
        // 如果是保存并设置，就跳转到设置中去，带上素材号
        if (I('subAct') == 'saveAndSet') {
            $url = U('Weixin/WeixinPublish/index', 
                array(
                    'm_id' => $id));
        } else {
            $url = U('Weixin/Weixin/materialImgTxtManage');
        }
        node_log("【微信公众账号助手】素材编辑，素材ID号:{$id}"); // 记录日志
        $this->success("编辑成功", array(
            'gourl' => $url), true);
    }
    // 文件上传
    public function uploadFile() {
        import('ORG.Net.UploadFile');
        $upload = new UploadFile(); // 实例化上传类
        $upload->maxSize = 1024 * 1000; // 设置附件上传大小 1兆
        $upload->allowExts = array(
            'jpg', 
            'gif', 
            'png', 
            'jpeg'); // 设置附件上传类型
        $upload->savePath = $this->uploadPath; // 设置附件上传目录
        if (! $upload->upload()) { // 上传错误提示错误信息
            exit(
                json_encode(
                    array(
                        'info' => $upload->getErrorMsg(), 
                        'status' => 1)));
        } else { // 上传成功 获取上传文件信息
            $info = $upload->getUploadFileInfo();
            if ($info)
                $info = $info[0];
                // 添加到素材表
            $dao = M('Twx_material');
            $data = array(
                'node_id' => $this->nodeId, 
                'material_title' => $info['name'], 
                'material_img' => $info['savename'], 
                'material_size' => sprintf('%0.1f', $info['size'] / 1000), 
                'material_summary' => $material_summary, 
                'material_desc' => $material_desc, 
                'material_link' => '', 
                'parent_id' => '0', 
                'material_level' => 0, 
                'material_type' => 3, 
                'add_time' => date('YmdHis'));
            $result = $dao->add($data);
            if (! $result) {
                
                exit(
                    json_encode(
                        array(
                            'info' => "系统正忙", 
                            'status' => 1)));
            }
            exit(
                json_encode(
                    array(
                        'info' => array(
                            'fileId' => $result, 
                            'imgName' => $info['savename'], 
                            'imgUrl' => $this->_getImgUrl($info['savename'])), 
                        'status' => 0)));
        }
    }
    
    // 根据fileId,或者fileName获取图片
    public function getUploadImage() {
        return;
        $fileId = I('fileId');
        $fileName = I('fileName');
        $dao = M('twx_material');
        if ($fileId) {
            $where = array(
                'id' => $fileId);
        } else {
            $where = array(
                'material_img' => $fileName);
        }
        $info = $dao->where($where)->find();
        if (! $info) {
            die('图片不存在');
        }
        import('ORG.Util.Image');
        Image::showImg($this->uploadPath . $info['material_img']);
    }

    private function _getImgUrl($imgname) {
        return $this->uploadPath . $imgname;
    }
    
    // 图文消息
    public function materialImgTxtManage() {
        $dao = M('twx_material');
        $where = "node_id='" . $this->nodeId . "'";
        $where .= " and material_type in ('1','2') and material_level = '1'";
        $totalNum = $dao->where($where)->count();
        import("ORG.Util.Page"); // 导入分页类
        $Page = new Page($totalNum, 15);
        $Page->setConfig('theme', 
            '%totalRow% %header% %nowPage%/%totalPage% 页 %first% %upPage% %linkPage% %downPage% %end%');
        $pageShow = $Page->show();
        
        $queryData = $dao->where($where)
            ->order("id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $materialArr = array();
        foreach ($queryData as $k => $v) {
            // 处理图片
            $v['material_img_url'] = $this->_getImgUrl($v['material_img']);
            // 处理多图文,如果是子节点
            
            if ($v['material_type'] == '2') {
                $sub_meterial = $dao->where(
                    array(
                        'parent_id' => $v['id']))->select();
                foreach ($sub_meterial as &$vv) {
                    $vv['material_img_url'] = $this->_getImgUrl(
                        $vv['material_img']);
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
        $this->assign('totalNum', $totalNum);
        $this->display();
    }
    
    // 素材删除
    public function materialDelete() {
        $id = I('id');
        $dao = D('TwxMaterial');
        $materialInfo = $dao->getMaterialInfoById($id, $this->nodeId, true);
        if (! $materialInfo) {
            $this->error("素材不存在");
        }
        $dao->startTrans();
        
        // 判断素材类型
        if ($materialInfo['material_type'] == '2') {
            // 删除子素材
            $resutl = $dao->where("parent_id='" . $materialInfo['id'] . "'")->delete();
        }
        // 删除本素材
        $result = $dao->where(
            "id='" . $id . "' and node_id='" . $this->nodeId . "'")->delete();
        if (! $result) {
            $this->error("删除失败");
        }
        $result = $dao->commit();
        if (! $result) {
            $this->error("删除失败");
        }
        if ($result) {
            $materialImg = array(
                $materialInfo['material_img']);
            // 删除素材文件
            if ($materialInfo['sub_material']) {
                $materialImg = $materialImg + array_valtokey(
                    $materialInfo['sub_material'], 'material_img', 
                    'material_img');
                foreach ($materialImg as $v) {
                    if ($v) {
                        $filePath = $this->uploadPath . $v;
                        unlink($filePath);
                    }
                }
            }
        }
        node_log("【微信公众账号助手】素材删除，素材ID号:{$id}"); // 记录日志
        $this->success("删除成功");
    }
    
    // 图片管理
    public function materialImgManage() {
        $dao = M('twx_material');
        $where = "node_id='" . $this->nodeId . "'";
        $where .= " and material_type in ('3') and material_level = '0'";
        $totalNum = $dao->where($where)->count();
        import("ORG.Util.Page"); // 导入分页类
        $Page = new Page($totalNum, 10);
        $Page->setConfig('theme', 
            '%totalRow% %header% %nowPage%/%totalPage% 页 %first% %upPage% %linkPage% %downPage% %end%');
        $pageShow = $Page->show();
        $queryData = $dao->where($where)
            ->order("id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $materialArr = array();
        foreach ($queryData as $k => $v) {
            // 处理图片
            $v['img_url'] = $this->_getImgUrl($v['material_img']);
            $materialArr[] = $v;
        }
        $this->assign('materialArr', $materialArr);
        $this->assign('pageShow', $pageShow);
        $this->display();
    }
    
    // 图片编辑名保存 (ajax)
    public function materialImgEditSubmit() {
        $imgTitle = I('input_i-material_title');
        $mid = I('input_i-material_id');
        $dao = M('twx_material');
        $data = array(
            'material_title' => $imgTitle);
        $result = $dao->where("id='" . $mid . "' and material_type='3'")->save(
            $data);
        if ($result === false) {
            $this->error("修改失败");
        }
        node_log("【微信公众账号助手】图片编辑，ID号:{$mid}"); // 记录日志
        $this->success("修改成功");
    }
    
    // 图片删除
    public function materialImgDeleteSubmit() {
        $mid = I('input_i-material_id');
        $dao = M('twx_material');
        $data = array(
            'material_title' => $imgTitle);
        $result = $dao->where("id='" . $mid . "' and material_type='3'")->delete(
            $data);
        if ($result === false) {
            $this->error("删除失败");
        }
        node_log("【微信公众账号助手】图片删除，ID号:{$mid}"); // 记录日志
        $this->success("删除成功");
    }
    
    // 选择互动模块
    public function selectActivityBatch() {
        $batch_type = I('batch_type', '2');
        $search = array();
        $pageShow = '';
        $queryList = $this->_getBatchList($batch_type, $search, $pageShow);
        /*
         * $queryList = array(
         * array('id'=>1,'name'=>'新店开张抽大奖1','info'=>htmlspecialchars('抽奖活动>新店开张抽大奖1'),'begin_time'=>'20130101','end_time'=>'20131231'),
         * array('id'=>2,'name'=>'新店开张抽大奖2','info'=>htmlspecialchars('抽奖活动>新店开张抽大奖2'),'begin_time'=>'20130101','end_time'=>'20131231'),
         * );
         */
        $batch_type_name = $this->_getBatchType($batch_type);
        $this->assign('batch_type', $batch_type);
        $this->assign('batch_type_name', $batch_type_name);
        $this->assign('queryList', $queryList);
        $this->assign('page', $pageShow);
        $this->display();
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
    
    // 获取活动内容
    private function _getBatchInfo($batch_type, $batch_id) {
        $batch_table = $this->_getBatchTable($batch_type);
        $batch_info = M($batch_table)->where(
            "node_id='" . $this->nodeId . "' and id='" . $batch_id . "'")->find();
        return $batch_info;
    }
    
    // 获取活动表名
    private function _getBatchTable($batch_type) {
        $batch_table = 'tmarketing_info';
        // $batch_type_arr = C('BATCH_TYPE');
        // $batch_table = $batch_type_arr[$batch_type];
        if (! $batch_table) {
            $this->error("未知活动类型【" . $batch_type . "】");
        }
        return $batch_table;
    }
    
    // 获取活动名称
    private function _getBatchType($batch_type) {
        $type_name = '';
        
        $type_name_arr = C('BATCH_TYPE_NAME');
        $type_name = $type_name_arr[$batch_type];
        if (! $type_name) {
            $this->error("未知活动类型【" . $batch_type . "】");
        }
        /*
         * switch($batch_type){ case '1'://游戏 $type_name = '游戏'; break; case
         * '2':// $type_name = '抽奖'; break; case '3': $type_name = '市场调研';
         * break; case '4': $type_name = '会员招募'; break; case '5': $type_name =
         * '团购'; break; case '8': $type_name = '列表'; break; case '9': $type_name
         * = '优惠券'; break; case '10': $type_name = '有奖问答'; break; case '11':
         * $type_name = '码上有红包'; break; default: $type_name =
         * '未知类型'.$batch_type; break; }
         */
        return $type_name;
    }
    
    // 获取活动内容列表
    private function _getBatchList($batch_type, $search = array(), &$pageShow = null) {
        $batch_table = $this->_getBatchTable($batch_type);
        $dao = M($batch_table);
        $search['node_id'] = $this->nodeId;
        $search['batch_type'] = $batch_type;
        $totalNum = $dao->where($search)->count();
        import("ORG.Util.Page"); // 导入分页类
        $Page = new Page($totalNum, 10);
        $Page->setConfig('theme', 
            '%totalRow% %header% %nowPage%/%totalPage% 页 %first% %upPage% %linkPage% %downPage% %end%');
        $pageShow = $Page->show();
        $queryData = $dao->field("id batch_id,name,start_time,end_time")
            ->where($search)
            ->order("id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 获取活动类型
        $type_name = $this->_getBatchType($batch_type);
        if (! $queryData)
            $queryData = array();
        foreach ($queryData as &$v) {
            $v['info'] = $type_name . ' ＞ ' . $v['name'];
        }
        unset($v);
        return $queryData;
    }

    public function getActivityLabelUrl() {
        $batch_type = I('batch_type');
        $batch_id = I('batch_id');
        
        $url = $this->_getLabelUrl($batch_type, $batch_id);
        $this->success(array(
            'url' => $url), '', true);
    }

    /**
     * 消息管理
     */
    public function user_msgmng() {
        $day = I('day', 0, 'intval');
        
        // 今天
        if ($day == 1) {
            $begin_time = date('Ymd000000');
            $end_time = date('Ymd235959');
            $time_arr = array(
                'a.msg_time' => array(
                    array(
                        'EGT', 
                        $begin_time), 
                    array(
                        'ELT', 
                        $end_time), 
                    'and'));
        }  // 昨天
else if ($day == 2) {
            $begin_time = date('Ymd000000', strtotime("-1 day"));
            $end_time = date('Ymd235959', strtotime("-1 day"));
            $time_arr = array(
                'a.msg_time' => array(
                    array(
                        'EGT', 
                        $begin_time), 
                    array(
                        'ELT', 
                        $end_time), 
                    'and'));
        }  // 5天内
else {
            $begin_time = date('Ymd000000', strtotime("-4 day"));
            $time_arr = array(
                'a.msg_time' => array(
                    'EGT', 
                    $begin_time));
        }
        
        $where = array(
            'a.node_id' => $this->node_id, 
            'a.node_wx_id' => $this->node_wx_id, 
            'a.msg_sign' => 0);
        
        $hide_flag = I('hide_flag', 0, 'intval');
        if ($hide_flag == 1) {
            $where['a.msg_response_flag'] = array(
                'neq', 
                '2');
        }
        
        $where = array_merge($where, $time_arr);
        
        $dao = M('twx_msg_trace');
        import('ORG.Util.Page'); // 导入分页类
        $count = $dao->table('twx_msg_trace a')
            ->where($where)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        
        $queryList = $dao->table('twx_msg_trace a')
            ->join(
            'twx_user b on b.openid = a.wx_id and b.node_wx_id = a.node_wx_id')
            ->field('a.*, b.headimgurl, b.nickname, b.remarkname')
            ->where($where)
            ->order('a.msg_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $last_time = $dao->table('twx_msg_trace a')
            ->where($where)
            ->order('a.msg_time desc')
            ->limit(1)
            ->getField('a.msg_time') or $last_time = date('YmdHis');
        
        $this->assign('last_time', $last_time);
        $this->assign('list', $queryList);
        $this->assign('count', $count);
        $this->assign('page', $pageShow);
        
        // 查询分组
        $where = "(node_id = '' or node_id = '{$this->node_id}') and (node_wx_id = '' or node_wx_id = '{$this->node_wx_id}')";
        $group_list = M('twx_user_group')->where($where)->getField('id, name', 
            true);
        $this->assign('group_list', $group_list);
        
        $this->display();
    }

    public function chat_someone() {
        $id = I('id', '', 'trim');
        if ($id == '') {
            $this->error('参数错误！');
        }
        
        $time = I('time', '', 'trim');
        
        if ($time == '')
            $time = date('Ymd000000', strtotime("-4 day"));
        
        $wh = array(
            'node_id' => $this->node_id, 
            'openid' => $id);
        $user_info = M('twx_user')->where($wh)->find();
        if (! $user_info)
            $this->error('参数错误！');
        
        $wh = array(
            'a.msg_time' => array(
                'GT', 
                $time), 
            'a.node_id' => $this->node_id, 
            'a.node_wx_id' => $this->node_wx_id, 
            'a.wx_id' => $id, 
            '_string' => "(a.msg_sign = '0') or (a.msg_sign = '1' and (a.op_user_id is not null or a.op_user_id != '') )");
        
        $model = M();
        $table_a = $model->table('twx_msg_trace a')
            ->where($wh)
            ->order('a.msg_time desc')
            ->limit(0, 20)
            ->buildSql();
        $list = $model->table($table_a . ' a')
            ->join(
            "twx_user b on b.node_id = a.node_id and b.node_wx_id = '{$this->node_wx_id}' and b.openid = a.wx_id ")
            ->field('a.*, b.nickname, b.remarkname, b.headimgurl')
            ->select();
        
        $this->assign('user_info', $user_info);
        $this->assign('list', $list);
        $this->assign('last_time', $list[0]['msg_time']);
        $this->assign('id', $id);
        
        // 查询分组
        $where = "(node_id = '' or node_id = '{$this->node_id}') and (node_wx_id = '' or node_wx_id = '{$this->node_wx_id}')";
        $group_list = M('twx_user_group')->where($where)->getField('id, name', 
            true);
        $this->assign('group_list', $group_list);
        $this->assign('node_weixin_code', $this->node_weixin_code);
        
        $this->display();
    }

    public function usermsg_reply() {
        $type = I('type', 0, 'intval'); // 0 指定消息回复 1 直接回复某人
        $wx_id = I('wx_id', null, 'trim');
        $msg_id = I('msg_id', null, 'intval');
        $reply_text = I('reply_text', null, 'trim');
        $reply_type = I('reply_type', 0, 'intval'); // 0文本回复 2 图文回复
        
        if ($type !== 0 && $type !== 1)
            $this->error('参数[type]错误！');
        
        if ($reply_type !== 0 && $reply_type !== 2)
            $this->error('参数[reply_type]错误！');
            
            // 指定消息回复时，不能为空
        if ($type == 0 && $msg_id == null)
            $this->error('参数[msg_id]错误！');
            
            // 指定某人回复时，不能为空
        if ($type == 1 && $wx_id == null)
            $this->error('参数[wx_id]错误！');
        
        $model = M('twx_msg_trace');
        
        if ($type == 0) {
            $where = array(
                'msg_id' => $msg_id, 
                'node_id' => $this->node_id, 
                'msg_sign' => 0);
            
            $info = $model->where($where)->find();
            if (! $info) {
                $this->error('参数错误！' . $model->_sql());
            }
            
            $wx_id = $info['wx_id'];
        }
        
        // 获取最后一次交互时间
        $info = $model->where(
            array(
                'wx_id' => $wx_id, 
                'node_id' => $this->node_id, 
                'msg_sign' => 0))
            ->order('msg_time desc')
            ->limit('1')
            ->find();
        
        if (! $info)
            $this->error('参数错误！');
        
        if (time() - strtotime($info['msg_time']) > 60 * 60 * 48)
            $this->error('超过48小时未交互，不能回复！');
        
        $wx_send = D('WeiXinSend', 'Service');
        try {
            $wx_send->init($this->node_id);
            $result = $wx_send->sendCustomMsg($reply_type, $wx_id, $reply_text);
            if (! $result)
                $this->error('回复失败');
            
            if ($result['errcode'])
                $this->error(
                    '回复失败：[' . $result['errcode'] . ']' . $result['errmsg']);
        } catch (Exception $e) {
            $this->error('回复失败：' . $e->getMessage());
        }
        
        $req_arr = $wx_send->req_arr;
        $msg_info = '';
        switch ($reply_type) {
            case 0:
                $msg_info = $reply_text;
                break;
            case 2:
                $msg_info = json_encode($req_arr['news']);
                break;
            default:
                $this->error('回复类型错误！');
        }
        
        // 开始数据库操作
        $model->startTrans();
        do {
            // 在流水表中添加回复流水
            $data = array(
                'msg_sign' => '1', 
                'wx_id' => $wx_id, 
                'msg_type' => $reply_type, 
                'msg_info' => $msg_info, 
                'msg_time' => date('YmdHis'), 
                'msg_response_flag' => 1, 
                'response_msg_id' => $type == 0 ? $msg_id : '', 
                'node_id' => $this->node_id, 
                'node_wx_id' => $info['node_wx_id'], 
                'op_user_id' => $this->user_id);
            
            $new_msg_id = $model->add($data);
            
            if ($new_msg_id === false) {
                log::write("插入流水表错误！语句：" . $model->_sql());
                $model->rollback();
                break;
            }
            
            // 更改原流水的处理状态
            if ($type == 0 && $info['msg_response_flag'] == '0') {
                $data = array(
                    'msg_response_flag' => '1');
                $flag = $model->where($where)->save($data);
                
                if ($flag === false) {
                    log::write("更新流水错误！语句：" . $model->_sql());
                    $model->rollback();
                    break;
                }
            }
            $model->commit();
        }
        while (0);
        
        node_log("客服回复，消息id{$msg_id},回复消息id{$new_msg_id}"); // 记录日志
        $this->success('回复成功');
    }

    /**
     * 获取最新的消息数
     */
    public function get_newmsg_cnt($time) {
        $time = I('time', '', 'trim');
        if ($time == '')
            date('Ymd000000', strtotime("-4 day"));
        
        $wh = array(
            'msg_time' => array(
                'GT', 
                $time), 
            'node_id' => $this->node_id, 
            'node_wx_id' => $this->node_wx_id, 
            'msg_sign' => 0);
        $cnt = D('twx_msg_trace')->where($wh)->count();
        $this->success(array(
            'cnt' => $cnt));
    }

    /**
     * 获取用户的最新消息
     */
    public function get_newmsg() {
        $time = I('time', '', 'trim');
        $id = I('id', '', 'trim');
        
        if ($time == '')
            $time = date('Ymd000000', strtotime("-4 day"));
        
        $wh = array(
            'a.msg_time' => array(
                'GT', 
                $time), 
            'a.node_id' => $this->node_id, 
            'a.node_wx_id' => $this->node_wx_id, 
            'a.wx_id' => $id, 
            '_string' => "(a.msg_sign = '0') or (a.msg_sign = '1' and (a.op_user_id is not null or a.op_user_id != '') )");
        
        $model = M();
        $list = $model->table('twx_msg_trace a')
            ->join(
            'twx_user b on b.openid = a.wx_id and b.node_id = a.node_id and b.node_wx_id = a.node_wx_id')
            ->field(
            "a.*, b.nickname, b.remarkname, '{$this->node_weixin_code}' as node_weixin_code")
            ->where($wh)
            ->order('a.msg_time desc')
            ->limit(0, 20)
            ->select();
        
        $data['list'] = null;
        if ($list) {
            $data['last_time'] = $list[0]['msg_time'];
            foreach ($list as &$info) {
                $info['msg_time'] = dateformat($info['msg_time'], 'defined1');
                $info['msg_info'] = chat_msg_show($info['msg_type'], 
                    $info['msg_info']);
                $info['msg_response_flag'] = $info['msg_sign'] == '0' &&
                     $info['msg_response_flag'] == '1';
            }
            $data['list'] = $list;
        }
        
        $this->success($data);
    }

    /**
     * 批量发送
     */
    public function batch_send() {
        // 查询本月是否已经发满
        $mass_premonth = C('WEIXIN_MASS_PREMONTH');
        $mass_maxnum = C('WEIXIN_MASS_MAXNUM');
        $count = M('twx_msgbatch')->where(
            "node_id = '{$this->node_id}' and node_wx_id = '{$this->node_wx_id}' and add_time like '" .
                 date('Ym') . "%'")->count();
        
        // 查询分组
        $where = "(node_id = '' or node_id = '{$this->node_id}') and (node_wx_id = '' or node_wx_id = '{$this->node_wx_id}')";
        $group_list = M('twx_user_group')->where($where)->getField('id, name', 
            true);
        
        if ($this->isPost()) {
            if ($count >= $mass_premonth) {
                $this->error('当月已达到群发次数限制！');
            }
            
            // 数据校验
            $reply_type = I('reply_type', 0, 'trim,intval');
            $reply_text = I('reply_text', 0, 'trim');
            $material_id = I('material_id', 0, 'intval');
            $group_id = I('group_id', '', 'trim');
            $sex = I('sex', 0, 'intval');
            /*
             * $start_score = I('start_score', 0, 'intval,abs'); $end_score =
             * I('end_score', 0, 'intval,abs');
             */
            
            if (! in_array($reply_type, 
                array(
                    0, 
                    2), true))
                $this->error('参数[reply_type]错误！');
            
            if ($group_id != 0 && ! isset($group_list[$group_id]))
                $this->error('参数[group_id]错误！');
            
            if (! in_array($sex, 
                array(
                    0, 
                    1, 
                    2), true))
                $this->error('参数[sex]错误！');
                
                /*
             * if($start_score != 0 && $end_score != 0 && $end_score <
             * $start_score) $this->error('活跃度区间错误！');
             */
                
            // 图文素材
            if ($reply_type == 2) {
                $material = M('twx_material')->find($material_id);
                if (! $material || $material['node_id'] != $this->node_id)
                    $this->error('素材错误');
            }
            
            // 根据条件查询需要批量发送的粉丝
            $where = array(
                't.node_id' => $this->node_id, 
                't.node_wx_id' => $this->node_wx_id, 
                't.subscribe' => '1');
            
            if (in_array($sex, 
                array(
                    1, 
                    2), true))
                $where['t.sex'] = $sex;
            if ($group_id != '')
                $where['t.group_id'] = $group_id;
                /*
             * if($start_score > 0) $where['_string'] .= " and a.cnt >
             * {$start_score}"; if($end_score > 0) $where['_string'] .= " and
             * a.cnt < {$end_score}";
             */
                
            // $deal_time = date('YmdHis', strtotime("-37 hours"));
                
            // $table_name = "twx_user t, (SELECT r.wx_id, COUNT(1) cnt FROM
                // twx_msg_trace r WHERE r.msg_sign = '0'AND r.msg_time >
                // '{$deal_time}' AND r.node_id = '{$this->node_id}' AND
                // r.node_wx_id = '{$this->node_wx_id}' GROUP BY r.wx_id) a";

            $model = M("twx_user");
            $count = $model->alias("t")->where($where)->count();
            if ($count == 0)
                $this->error('没有找到查询条件对应的用户！');
            if ($count > $mass_maxnum)
                $this->error('群发用户不能超过' . $mass_maxnum);
            
            $fans_list = $model->alias("t")->where($where)->getField(
                't.openid id, t.openid', true);
            $fans_list = array_values($fans_list);
            
            if ($reply_type == 2) {
                // 多图文处理
                if ($material['material_type'] == 2) {
                    $list = M('twx_material')->where(
                        "id= '{$material_id}' or parent_id = '{$material_id}'")->select();
                } else {
                    $list = array(
                        $material);
                }
                
                $arr = array();
                foreach ($list as $info) {
                    $arr[] = array(
                        "title" => $info['material_title'], 
                        "description" => $info['material_summary'], 
                        "url" => $info['material_link'], 
                        "picurl" => $info['material_img']);
                }
                $content = array(
                    'Articles' => $arr);
                $reply_text = unicodeDecode(json_encode($content));
            }
            
            M()->startTrans();
            // 插入批量发送主表
            $data = array(
                'node_wx_id' => $this->node_wx_id, 
                'user_id' => $this->user_id, 
                'node_id' => $this->node_id, 
                'total_count' => count($fans_list), 
                'add_time' => date('YmdHis'), 
                // 'batch_token' => ,
                'msg_type' => $reply_type, 
                'msg_info' => $reply_text);
            // 'filename' => $this->node_wx_id,
            
            $batch_msg_id = M('twx_msgbatch')->add($data);
            
            if ($batch_msg_id === false) {
                M()->rollback();
                log::write("插入批量发送表错误！语句：" . M()->_sql());
                $this->error('批量发送初始化失败！');
            }
            
            // 循环插入流水表
            $model = M('twx_msg_trace');
            foreach ($fans_list as $fans) {
                $data = array(
                    'msg_sign' => '4', 
                    'wx_id' => $fans, 
                    'msg_type' => $reply_type, 
                    'msg_info' => $reply_text, 
                    'msg_time' => date('YmdHis'), 
                    'node_id' => $this->node_id, 
                    'node_wx_id' => $this->node_wx_id, 
                    'op_user_id' => $this->user_id, 
                    'batch_msg_id' => $batch_msg_id);
                $flag = $model->add($data);
                
                if ($flag === false) {
                    M()->rollback();
                    log::write("插入批量发送表错误！语句：" . M()->_sql());
                    $this->error('批量发送初始化失败！');
                }
            }
            
            $wx_send = D('WeiXinSend', 'Service');
            try {
                $wx_send->init($this->node_id);
                $result = $wx_send->batch_send(
                    array(
                        'material_id' => $material_id, 
                        'openids' => $fans_list));
                if (! $result) {
                    M()->rollback();
                    $this->error('群发失败！');
                }
                
                if ($result['errcode']) {
                    M()->rollback();
                    $this->error(
                        '回复失败：[' . $result['errcode'] . ']' . $result['errmsg']);
                }
            } catch (Exception $e) {
                M()->rollback();
                $this->error('回复失败：' . $e->getMessage());
            }
            
            $wx_batch_id = $result['msg_id'];
            M('twx_msgbatch')->where("batch_id = '$batch_msg_id'")->save(
                array(
                    'wx_batch_id' => $wx_batch_id));
            M()->commit();
            node_log("【微信公众账号助手】批量发送录入成功！"); // 记录日志
            
            $this->success('发送成功！');
        } else {
            
            $this->assign('mass_premonth', $mass_premonth);
            $this->assign('sent_num', $count);
            $this->assign('group_list', $group_list);
            $this->display();
        }
    }

    public function batch_send_his() {
        $dao = M('twx_msgbatch');
        $where = array(
            'node_wx_id' => $this->node_wx_id);
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
        
        $this->assign('list', $list);
        $this->assign('page', $pageShow);
        $this->assign('status_arr', 
            array(
                '0' => '未处理', 
                '1' => '处理中', 
                '2' => '已处理(有失败的)', 
                '3' => '全处理(全部成功)', 
                '9' => '处理失败'));
        $this->display();
    }

    /**
     * 粉丝管理
     */
    public function fansmng() {
        
        // 获取粉丝
        $where = array(
            'node_id' => $this->node_id, 
            'node_wx_id' => $this->node_wx_id);
        
        $group_id = I('group_id', '-', 'trim');
        if ($group_id != '-')
            $where['group_id'] = $group_id;
        
        import('ORG.Util.Page'); // 导入分页类
        $count = M('twx_user')->where($where)->count(); // 查询满足要求的总记录数
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        
        $list = M('twx_user')->where($where)
            ->order('id')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        // 查询分组
        $where = array(
            'node_id' => array(
                'in', 
                array(
                    $this->node_id), 
                ''), 
            'node_wx_id' => array(
                'in', 
                array(
                    $this->node_wx_id), 
                ''), 
            'subscribe' => 1);
        $_where = "(node_id = '' or node_id = '{$this->node_id}') and (node_wx_id = '' or node_wx_id = '{$this->node_wx_id}')";
        $group_list = M('twx_user_group')->where($_where)
            ->order('id asc')
            ->select();
        $group_arr = M('twx_user_group')->where($_where)->getField('id, name', 
            true);
        
        // 查询分组用户数
        $group_num_arr = M('twx_user')->where($where)
            ->group('group_id')
            ->getField('group_id, count(1) cnt', true);
        
        $this->assign('group_arr', $group_arr);
        $this->assign('group_num_arr', $group_num_arr);
        $this->assign('fans_cnt', array_sum($group_num_arr));
        $this->assign('group_list', array_valtokey($group_list, 'id'));
        $this->assign('user_list', $list);
        $this->assign('page', $pageShow);
        $this->display();
    }

    /**
     * 粉丝组添加
     */
    public function fans_group_add() {
        $group_name = I('group_name', '', 'trim');
        if ($group_name == '')
            $this->error('参数不正确！');
        
        $model = M('twx_user_group');
        
        $data = array(
            'name' => $group_name, 
            'node_id' => $this->node_id, 
            'node_wx_id' => $this->node_wx_id);
        
        $cnt = $model->where($data)->count();
        if ($cnt > 0)
            $this->error('该组已经存在');
            
            /*
         * $data['wx_group_id'] = $model->where(array( 'node_id' =>
         * $this->node_id, 'node_wx_id' => $this->node_wx_id,
         * ))->max('wx_group_id') + 1;
         */
        
        $flag = $model->add($data);
        if ($flag === false) {
            log::write("组添加失败！语句：" . M()->_sql());
            $this->error('组添加失败！');
        }
        
        $this->success("分组添加成功！");
    }

    /**
     * 粉丝组编辑
     */
    public function fans_group_edit() {
        $group_id = I('group_id', 0, 'intval');
        $group_name = I('group_name', '', 'trim');
        
        if ($group_id == 0 || $group_name == '')
            $this->error('参数错误！');
        
        $where = array(
            'id' => $group_id, 
            'node_id' => $this->node_id, 
            'node_wx_id' => $this->node_wx_id);
        
        $model = M('twx_user_group');
        $info = $model->where($where)->find();
        if (! $info)
            $this->error('参数错误2！');
        
        if ($group_name == $info['name'])
            $this->success('编辑成功！');
        else {
            $data = array(
                'name' => $group_name);
            $flag = $model->where($where)->save($data);
            
            if ($flag === false) {
                log::write("组编辑失败！语句：" . M()->_sql());
                $this->error('组编辑失败！');
            } else
                $this->success('编辑成功！');
        }
    }

    /**
     * 粉丝组删除
     */
    public function fans_group_del() {
        $group_id = I('group_id', 0, 'intval');
        $where = array(
            'id' => $group_id, 
            'node_id' => $this->node_id, 
            'node_wx_id' => $this->node_wx_id);
        
        $model = M('twx_user_group');
        $info = $model->where($where)->find();
        if (! $info)
            $this->error('参数错误！');
        
        $model->startTrans();
        $flag = $model->where($where)->delete();
        if ($flag === false) {
            $model->rollback();
            log::write("分组删除失败！语句：" . M()->_sql());
            $this->error('分组删除失败！');
        }
        
        $where = array(
            'group_id' => $group_id, 
            'node_id' => $this->node_id, 
            'node_wx_id' => $this->node_wx_id);
        $data = array(
            'group_id' => '0');
        $flag = M('twx_user')->where($where)->save($data);
        if ($flag === false) {
            $model->rollback();
            log::write("移除该分组的用户失败！语句：" . M()->_sql());
            $this->error('移除该分组的用户失败！');
        }
        
        $model->commit();
        node_log("微信粉丝分组【$group_id】删除成功！");
        $this->success("分组删除成功！");
    }

    /**
     * 变更用户分组
     */
    public function fans_group_chg() {
        $fans_id = I('openid', 0, 'trim');
        $group_id = I('group_id', 0, 'intval');
        
        $where1 = array(
            'openid' => $fans_id, 
            'node_id' => $this->node_id, 
            'node_wx_id' => $this->node_wx_id);
        $model = M('twx_user');
        $user_info = $model->where($where1)->find();
        if (! $user_info)
            $this->error('参数错误！1');
        
        if ($user_info['group_id'] == $group_id) {
            $this->success('用户组变更成功！');
            exit();
        }
        
        $_where = "id = '$group_id' and (node_id = '' or node_id = '{$this->node_id}') and (node_wx_id = '' or node_wx_id = '{$this->node_wx_id}')";
        $info = M('twx_user_group')->where($_where)->find();
        if (! $info)
            $this->error('参数错误！' . M()->_sql());
        
        $flag = $model->where($where1)->save(
            array(
                'group_id' => $group_id));
        if ($flag === false) {
            log::write("用户组变更失败！语句：" . M()->_sql());
            $this->error('用户组变更失败！');
        }
        
        $this->success('用户组变更成功！');
    }

    /**
     * 批量变更用户分组
     */
    public function fans_group_batchchg() {
        $fans_id = I('openids', 0, 'trim');
        $group_id = I('group_id', 0, 'intval');
        $id_arr = explode(',', $fans_id);
        
        $where1 = array(
            'openid' => array(
                'in', 
                $id_arr), 
            'node_id' => $this->node_id, 
            'node_wx_id' => $this->node_wx_id);
        $model = M('twx_user');
        $cnt = $model->where($where1)->count();
        if ($cnt != count($id_arr))
            $this->error('参数错误！');
        
        $where2 = array(
            'id' => $group_id, 
            'node_id' => $this->node_id, 
            'node_wx_id' => $this->node_wx_id);
        $where2 = "id = '$group_id' and (node_id = '' or node_id = '{$this->node_id}') and (node_wx_id = '' or node_wx_id = '{$this->node_wx_id}')";
        $info = M('twx_user_group')->where($where2)->find();
        if (! $info)
            $this->error('参数错误！');
        
        $flag = $model->where($where1)->save(
            array(
                'group_id' => $group_id));
        if ($flag === false) {
            log::write("用户组变更失败！语句：" . M()->_sql());
            $this->error('用户组变更失败！');
        }
        
        $this->success('用户组变更成功！');
    }

    public function wxuserinfo() {
        $id = I('id', '', 'trim');
        if ($id == '')
            $this->error('参数错误！');
        
        $time = I('time', '', 'trim');
        
        $wh = array(
            'node_id' => $this->node_id, 
            'openid' => $id);
        $user_info = M('twx_user')->where($wh)->find();
        if (! $user_info)
            $this->error('参数错误！');
        
        $this->success($user_info);
    }

    public function edit_remarkname() {
        $openid = I('openid', '', 'trim');
        $remarkname = I('remarkname', '', 'trim');
        if ($openid == '')
            $this->error('参数错误！');
        
        $time = I('time', '', 'trim');
        
        $wh = array(
            'node_id' => $this->node_id, 
            'openid' => $openid);
        $model = M('twx_user');
        $user_info = $model->where($wh)->find();
        if (! $user_info)
            $this->error('参数错误！');
        
        $flag = $model->where($wh)->save(
            array(
                'remarkname' => $remarkname));
        if ($flag === false)
            $this->error('编辑错误，请重试！');
        
        $this->success('编辑成功！');
    }

    public function test() {
        $wx_send = D('WeiXinSend', 'Service');
        try {
            $wx_send->init($this->node_id);
            $result = $wx_send->query_group();
            dump($result);
            
            $result = $wx_send->query_user('oh1FRt8yaFBuQ7H_V02BgZ7Iz8gY');
            dump($result);
            
            $result = $wx_send->query_userlist();
            dump($result);
            
            $result = $wx_send->query_usergroup('oh1FRt8yaFBuQ7H_V02BgZ7Iz8gY');
            dump($result);
        } catch (Exception $e) {
            $this->error('回复失败：' . $e->getMessage());
        }
    }
}