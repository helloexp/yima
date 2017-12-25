<?php

class WeixinAction extends BaseAction {

    public $uploadPath;

    private $node_weixin_code;

    private $node_wx_id;

    private $account_type;

    public $node_id_wc = '00000000';
    // 旺财运营平台机构号
    const CHANNEL_TYPE_WX = '4';
    // 微信公众平台
    const CHANNEL_SNS_TYPE_WX = '41';
    // 微信公众平台发布类型
    const BATCH_TYPE_SHOPGPS = 17;

    /**
     * @var WeixinCardGenerationModel
     */
    private $WeixinCardGenerationModel;

    public function _initialize() {
        log_write('准备进入微信');
        parent::_initialize();
        log_write('已过父类初始化');
        $this->uploadPath = './Home/Upload/Weixin/'; // 设置附件上传目录
        $info = D('tweixin_info')->where(
            "node_id = '{$this->node_id}' and status = '0'")->find();
        $this->node_weixin_code = $info['weixin_code'];
        $this->node_wx_id = $info['node_wx_id'];
        $this->account_type = $info['account_type'];
        $this->assign('account_type', $info['account_type']);
        $this->WeixinCardGenerationModel = D('WeixinCardGeneration');
        // C('LABEL_ADMIN',include(CONF_PATH.'LabelAdmin/config.php'));
    }
    // 微信账号绑定
    public function index() {
        user_act_log('进入微信助手模块', '', 
            array(
                'act_code' => '2.3'));
        import('ORG.Util.Page'); // 导入分页类
        $count = M('tym_news')->where(
            array(
                'class_id' => 45, 
                'status' => 1))->count();
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
                                      
        // $Page->setConfig('prev', '<');
                                      // $Page->setConfig('next', '>');
        $Page->setConfig('theme', '%upPage% %linkPage% %downPage%');
        $show = $Page->show();
        
        $newsList = M('tym_news')->field(
            '`news_id`,`news_name`,`content`,`add_time`')
            ->where(
            array(
                'class_id' => 45, 
                'status' => 1))
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('`news_id` Desc')
            ->select();
        
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
        
        // 获取粉丝
        $where = array(
            'node_id' => $this->node_id, 
            'node_wx_id' => $this->node_wx_id, 
            'subscribe' => 1);
        
        $count = M('twx_user')->where($where)->count();
        $where['subscribe_time'] = array(
            'egt', 
            strtotime('-1 hour'));
        $countinc = M('twx_user')->where($where)->count();
        $inc = round($countinc / $count * 100, 2) . '%';
        if ($count > 9999) {
            $count = round($count / 10000, 1) . 'W';
        }
        $this->assign('inc', $inc);
        $this->assign('countinc', $countinc);
        $this->assign('count', $count);
        
        // 获取1小时内新增消息
        $begin_time = date('YmdHis', strtotime("-1 hour"));
        $map['msg_time'] = array(
            'egt', 
            $begin_time);
        $map['node_id'] = $this->node_id;
        $map['node_wx_id'] = $this->node_wx_id;
        $map['msg_sign'] = 0;
        
        $msgcount = M('twx_msg_trace')->where($map)->count();
        
        // 获取授权链接
        $info = $this->_initWeixinInfo($this->node_id);
        $wx_grant = D('WeiXinGrant', 'Service');
        $wx_grant->init();
        //绑定完公众号后的返回地址
        $backUrlNum = I('get.backUrl',0);
        $backUrl = array(
                U('Weixin/Weixin/index'),
                U('WangcaiPc/NumGoods/weChatIndex')
        );
        $wxConfigUrl = $wx_grant->get_auth_url($backUrl[$backUrlNum],$info['node_id']);

        if(!$resultInfo || $resultInfo['auth_flag'] == '0'){        //从未绑定过  和 取消绑定的 跳转页面
            $this->assign('wxInfo',$resultInfo);
            $this->assign('wxConfigUrl',$wxConfigUrl);
            $this->display('indexPre');

        }
        $this->assign('wxConfigUrl', $wxConfigUrl);
        $this->assign('configFlag', 
            $resultInfo['weixin_code'] == '' && $is_show == 1 ? 1 : 0);
        $this->assign('info', $info);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('newsList', $newsList);
        $this->assign('msgcount', $msgcount);
        $this->display();
    }

    /**
     * 未绑定微信公众号的统一处理
     */
    public function indexPre(){
        $resultInfo = M('tweixin_info')->where("node_id='" . $this->nodeId . "'")->find();
        // 获取授权链接
        $info = $this->_initWeixinInfo($this->node_id);
        $wx_grant = D('WeiXinGrant', 'Service');
        $wx_grant->init();
        //绑定完公众号后的返回地址
        $backUrlNum = I('get.backUrl',0);
        $backUrl = array(
                U('Weixin/Weixin/index'),
                U('WangcaiPc/NumGoods/weChatIndex')
        );
        $wxConfigUrl = $wx_grant->get_auth_url($backUrl[$backUrlNum],$info['node_id']);

        $this->assign('wxInfo',$resultInfo);
        $this->assign('wxConfigUrl',$wxConfigUrl);
        $this->display();
    }
    
    // 同步粉丝数据
    public function fansSync() {
        $getfans_flag = I('getfans_flag','');
        //检测是否绑定了公众号
        $wxInfo = M('tweixin_info')->where("node_id='{$this->nodeId}' and status='0'")->find();
        if($wxInfo['auth_flag'] == '0'){
            // 获取授权链接
            $info = $this->_initWeixinInfo($this->node_id);
            $wx_grant = D('WeiXinGrant', 'Service');
            $backUrl = U('Weixin/Weixin/fansmng');
            $wxConfigUrl = $wx_grant->get_auth_url($backUrl,$info['node_id']);
            $this->redirect($wxConfigUrl);
        }
//        if ($getfans_flag === '0' || $getfans_flag === '4') {
        if ($getfans_flag == '1') {
            $result = M('tweixin_info')->where(
                array(
                    'node_id' => $this->node_id))->save(
                array(
                    'getfans_flag' => '0',
                    'update_time' => date('YmdHis')));
            $weiXinService = D('WeiXin','Service');
            $token = $weiXinService->getAccessToken($wxInfo['app_id'],$wxInfo['app_secret']);
            $fansList = $weiXinService->getFansList($token);
            $this->ajaxReturn($fansList['total']);
        } else {
            $info = M('tweixin_info')->where(
                array(
                    'node_id' => $this->node_id))->getField('getfans_flag');
            $this->ajaxReturn($info);
        }
    }
    
    // 微信手动绑定
    public function bind() {
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
    
    // bind复制框架页面
    public function bind3() {
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
                // 添加绑定成功关键字回复
                $this->bindSuccess();
                
                node_log("【微信公众账号助手】微信账号绑定", print_r($_POST, true)); // 记录日志
                $this->success("保存成功");
            }
        }
    }

    // 微信智能绑定
    public function autobind() {
        $info = $this->_initWeixinInfo($this->node_id);
        // if(!$info['auth_flag']){
        $wx_grant = D('WeiXinGrant', 'Service');
        $wx_grant->init();
        $wxConfigUrl = $wx_grant->get_auth_url(U('Weixin/Weixin/index'), 
            $info['node_id']);
        $this->assign('wxConfigUrl', $wxConfigUrl);
        // }
        
        $this->display();
    }

    public function _initWeixinInfo($node_id) {
        $where = array(
            'node_id' => $node_id);
        $info = M('tweixin_info')->where($where)->find();
        if ($info) {
            return $info;
        } else {
            $callback_url = U('WeixinServ/Index/index', 
                array(
                    'node_id' => $this->nodeId), '', '', true);
            $data = array(
                'node_id' => $node_id, 
                'callback_url' => $callback_url, 
                'add_time' => date('YmdHis'),
                'app_secret'=> 1
            );
            $result = M('tweixin_info')->add($data);
            $info = M('tweixin_info')->where($where)->find();
            return $info;
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
    
    // 群发添加素材
    public function materialAdd() {
        $content = D('Draft')->getDraftField(
            array(
                'node_id' => $this->nodeId, 
                'type' => 6, 
                'org_id' => 1), 'content');
        if ($content) {
            $materialInfo = json_decode($content, true);
            $materialInfo['material_desc_richtxt'] = html_entity_decode(
                $materialInfo['material_desc_richtxt']);
            if(!$materialInfo['sub_material']) $materialInfo['sub_material'] = array();
            foreach ($materialInfo['sub_material'] as &$v) {
                $v['material_desc_richtxt'] = html_entity_decode(
                    $v['material_desc_richtxt']);
            }
        }
        $this->assign('materialInfo',$materialInfo);
        $this->display('materialAdd_type_2');
    }
    
    // 添加素材
    public function materialAdd2() {
        $content = D('Draft')->getDraftField(
            array(
                'node_id' => $this->nodeId, 
                'type' => 6, 
                'org_id' => 2), 'content');
        if ($content) {
            $materialInfo = json_decode($content, true);
        }
        $this->assign('materialInfo',$materialInfo);
        $this->display('materialAdd2_type_2');
    }
    
    // 群发素材添加提交
    public function materialAddSubmit() {
            $dataArr = I('dataJson');
            $material_type = count($dataArr) == 1 ? 1 : 2;
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
                $material_link = html_entity_decode($v['input_i-url']);
                $material_img = $v['input_i-material_img'];
                $show_cover_pic = $v['input_i-material_imgInsert'];
                $material_summary = $v['input_i-summary'];
                $material_desc = $v['input_i-material_desc_richtxt'];
                $url_type = $v['input_i-url_type'];
                $batch_id = $v['input_i-batch_id'];
                
                $batch_type = $v['input_i-batch_type'];
                
                // 校验batch_id，batch_type有效性
                if ($url_type == '1' && $batch_id) {
                    // 获取链接地址
                    $material_link = $this->_getLabelUrl($batch_type, $batch_id);
                    
                    if (! $material_link) {
                        $this->error("营销活动标签地址生成失败");
                    }
                } else {
                    $batch_id = 0;
                    $batch_type = 0;
                }
                // 添加到素材表，素材级别为1多级根节点
                $data = array(
                    'type' => 1, 
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
                    'show_cover_pic' => $show_cover_pic, 
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
        
        $dao->commit();
        
        // 删除草稿表信息
        $where['node_id'] = $this->node_id;
        $where['type'] = '6';
        $material_type == 1 ? $where['org_id'] = 0 : $where['org_id'] = 1;
        $result = M('tdraft')->where($where)->delete();
        
        // 如果是预览，调用微信预览接口
        if (I('preview') == 'preview') {
            $towxname = I('towxname');
            $wx_send = D('WeiXinSend', 'Service');
            $wx_send->init($this->nodeId);
            $result_str = $wx_send->preview_send($m_id, $towxname);
            $result = json_decode($result_str, true);
            log_write('预览'.$m_id);
            if ($result['errcode'] == 0) {
                $this->success('预览已发送');
            } elseif ($result['errcode'] == 40132) {
                $this->error('您输入的微信号不合法');
            } elseif ($result['errcode'] == 43004) {
                $this->error('您输入的微信号未关注此公众号');
            } else {
                $this->error('预览错误：' . $result_str);
            }
        }
        
        node_log("【微信公众账号助手】素材添加，素材ID号:{$m_id}"); // 记录日志
        $this->success("添加成功");
    }
    
    // 素材添加提交
    public function materialAddSubmit2() {
            $dataArr = I('dataJson');
            $material_type = count($dataArr) == 1 ? 1 : 2;
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
                $material_link = html_entity_decode($v['input_i-url']);
                $material_img = $v['input_i-material_img'];
                $show_cover_pic = $v['input_i-material_imgInsert'];
                $material_summary = $v['input_i-summary'];
                $fuText = $v['input_i-material_desc_richtxt'];
                $material_desc = $v['input_i-material_desc'];
                $url_type = $v['input_i-url_type'];
                $batch_id = $v['input_i-batch_id'];
                
                $batch_type = $v['input_i-batch_type'];
                
                // 校验batch_id，batch_type有效性
                if ($url_type == '1' && $batch_id) {
                    // 获取链接地址
                    $material_link = $this->_getLabelUrl($batch_type, $batch_id);
                    
                    if (! $material_link) {
                        $this->error("营销活动标签地址生成失败");
                    }
                } else {
                    $batch_id = 0;
                    $batch_type = 0;
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
                    'show_cover_pic' => $show_cover_pic, 
                    'batch_type' => $batch_type, 
                    'add_time' => date('YmdHis'));
                $id = $dao->add($data);
                
                if ($id === false) {
                    $dao->rollback();
                    $this->error("添加失败");
                } else {
                    if ($url_type == '2') {
                        $data = array(
                            'material_id' => $id, 
                            'material_text' => $fuText);
                        
                        // 更新twx_material素材表链接地址
                        $material_link = U('Label/FuWenText/index', 
                            array(
                                'id' => $id), '', '', true);
                        
                        $result1 = M('twx_material')->where(
                            array(
                                'id' => $id))->save(
                            array(
                                'material_link' => $material_link));
                        if (false === $result1) {
                            $dao->rollback();
                            $this->error("添加失败");
                        }
                        
                        // 插入富文本素材表
                        $result = M('twx_material_ex')->add($data);
                        
                        if (false === $result) {
                            $dao->rollback();
                            $this->error("添加失败");
                        }
                    }
                }
                $parent_id = $k == 0 ? $id : $parent_id;
                $m_id = $parent_id;
            }
        
        $dao->commit();
        
        // 删除草稿表信息
        $where['node_id'] = $this->node_id;
        $where['type'] = '6';
        $material_type == 1 ? $where['org_id'] = 2 : $where['org_id'] = 3;
        $result = $result = M('tdraft')->where($where)->delete();
        
        // 如果是预览，在关键词自动回复中插入一条数据
        if (I('preview') == 'preview') {
            $result = D('TweixinInfo')->preview($this->nodeId, $m_id);
            if ($resut) {
                log_write("关键词添加成功");
            }
        }
        
        node_log("【微信公众账号助手】素材添加，素材ID号:{$m_id}"); // 记录日志
        $this->success("添加成功");
    }
    
    // 编辑素材
    public function materialEdit2() {
        $id = I('id');
        
        $content = M('tdraft')->where(
            "node_id='" . $this->nodeId . "' and type = 6 and org_id = " . $id)->getField(
            'content');
        if ($content) {
            $materialInfo = json_decode($content, true);
        } else {
            $dao = D('TwxMaterial');
            $materialInfo = $dao->getMaterialInfoById($id, $this->nodeId, true);
            
            /* 组装富文本内容 */
            $fuText = M('twx_material_ex')->where(
                array(
                    'material_id' => $id))->find();
            
            $materialInfo['material_desc_richtxt'] = $fuText['material_text'];
            
            foreach ($materialInfo['sub_material'] as $k => &$v) {
                $fuText = M('twx_material_ex')->where(
                    array(
                        'material_id' => $v['id']))->find();
                $v['material_desc_richtxt'] = $fuText['material_text'];
                // 地址类型
                if ($v['batch_type'] != '0' || ! $v['material_link']) {
                    $v['url_type'] = 1; // 活动
                    unset($v['material_link']);
                } else {
                    $v['url_type'] = 0; // 手动链接
                    if ($fuText) {
                        $v['url_type'] = 2; // 富文本
                        unset($v['material_link']);
                    }
                }
            }
            
            if (! $materialInfo) {
                $this->error("素材不存在！");
            }
            // 地址类型
            if ($materialInfo['batch_type'] != '0' ||
                 ! $materialInfo['material_link']) {
                $materialInfo['url_type'] = 1; // 活动
                unset($materialInfo['material_link']);
            } else {
                $materialInfo['url_type'] = 0; // 手动链接
                if ($materialInfo['material_desc_richtxt']) {
                    $materialInfo['url_type'] = 2; // 富文本
                    unset($materialInfo['material_link']);
                }
            }
            $type = $materialInfo['material_type'];
        }
        $this->assign('materialInfo', $materialInfo);
        
        $this->assign('act', 'edit');
        $this->display('materialAdd2_type_2');
    }
    
    // 群发编辑素材
    public function materialEdit() {
        $id = I('id');
        $content = M('tdraft')->where(
            array(
                'node_id' => $this->nodeId, 
                'type' => 6, 
                'org_id' => $id))->getField('content');
        if ($content) {
            $materialInfo = json_decode($content, true);
            $materialInfo['material_desc_richtxt'] = html_entity_decode(
                $materialInfo['material_desc_richtxt']);
            foreach ($materialInfo['sub_material'] as &$v) {
                $v['material_desc_richtxt'] = html_entity_decode(
                    $v['material_desc_richtxt']);
            }
        } else {
            $dao = D('TwxMaterial');
            $materialInfo = $dao->getMaterialInfoById($id, $this->nodeId, true);
            /* 组装富文本内容 */
            $where = array();
            $materialInfo['material_desc_richtxt'] = html_entity_decode(
                $materialInfo['material_desc']);
            unset($materialInfo['material_desc']);
            // 地址类型
            if ($materialInfo['batch_type'] != '0' ||
                 ! $materialInfo['material_link']) {
                $materialInfo['url_type'] = 1; // 活动
                unset($materialInfo['material_link']);
                if ($materialInfo['batch_type'] != '0') {
                    $batchInfo = $this->_getBatchInfo($materialInfo['batch_id']);
                    $type_name = $this->_getBatchType(
                        $materialInfo['batch_type']);
                    $materialInfo['material_desc'] = $type_name . ' ＞ ' .
                         $batchInfo['name'];
                }
            } else {
                $materialInfo['url_type'] = 0; // 手动链接
            }
            
            foreach ($materialInfo['sub_material'] as &$v) {
                $v['material_desc_richtxt'] = html_entity_decode(
                    $v['material_desc']);
                unset($v['material_desc']);
                // 地址类型
                if ($v['batch_type'] != '0' || ! $v['material_link']) {
                    $v['url_type'] = 1; // 活动
                    unset($v['material_link']);
                    if ($v['batch_type'] != '0') {
                        $batchInfo = $this->_getBatchInfo($v['batch_id']);
                        $type_name = $this->_getBatchType($v['batch_type']);
                        $v['material_desc'] = $type_name . ' ＞ ' .
                             $batchInfo['name'];
                    }
                } else {
                    $v['url_type'] = 0; // 手动链接
                }
            }
        }
        
        if (! $materialInfo) {
            $this->error("素材不存在！");
        }
        
        $type = $materialInfo['material_type'];
        $this->assign('materialInfo', $materialInfo);
        $this->assign('act', 'edit');
        $this->display('materialAdd_type_2');
    }
    
    // 素材编辑提交
    public function materialEditSubmit() {
        $m_id = $id = I('id');
        $dao = M('twx_material');
        $count = $dao->where(
            "id='" . $id . "' and node_id='" . $this->nodeId . "'")->count();
        if (! $count) {
            $this->error("素材不存在");
        }
            $dataArr = I('dataJson');
            $material_type = count($dataArr) == 1 ? 1 : 2;
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
                $material_link = html_entity_decode($v['input_i-url']);
                $material_img = $v['input_i-material_img'];
                $show_cover_pic = $v['input_i-material_imgInsert'];
                $material_summary = $v['input_i-summary'];
                $m_id = $v['input_i-id'];
                $url_type = $v['input_i-url_type'];
                $batch_id = $v['input_i-batch_id'];
                $batch_type = $v['input_i-batch_type'];
                $material_desc = $v['input_i-material_desc_richtxt'];
                
                // 校验batch_id，batch_type有效性
                // 活动
                if ($url_type == '1' && $batch_id) {
                    $batch_info = $this->_getBatchInfo($batch_id);
                    
                    if (! $batch_info) {
                        $this->error("营销活动不存在");
                    }
                    // 获取链接地址
                    $material_link = $this->_getLabelUrl($batch_type, $batch_id);
                    if (! $material_link) {
                        $this->error("营销活动标签地址生成失败");
                    }
                } else {
                    $batch_id = 0;
                    $batch_type = 0;
                }
                
                // 添加到素材表，素材级别为1多级根节点
                $data = array(
                    'material_title' => $material_title, 
                    'material_img' => $material_img, 
                    'material_summary' => $material_summary, 
                    'material_desc' => $material_desc, 
                    'material_link' => $material_link, 
                    'batch_id' => $batch_id, 
                    'batch_type' => $batch_type, 
                    'material_type' => $material_type, 
                    'show_cover_pic' => $show_cover_pic);
                // 如果是新增
                if ($m_id == '') {
                    $data = array_merge($data, 
                        array(
                            'node_id' => $this->nodeId, 
                            'parent_id' => $parent_id, 
                            'material_level' => 2, 
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
        
        // 删除草稿表信息
        $where['node_id'] = $this->node_id;
        $where['type'] = '6';
        $where['org_id'] = $id;
        $result = $result = M('tdraft')->where($where)->delete();
        
        // $dao->rollback();
        $dao->commit();
        // 如果是预览
        if (I('preview') == 'preview') {
            $towxname = I('towxname');
            $wx_send = D('WeiXinSend', 'Service');
            $wx_send->init($this->nodeId);
            $result_str = $wx_send->preview_send($id, $towxname);
            $result = json_decode($result_str, true);
            log_write($result_str, '预览');
            if ($result['errcode'] == 0) {
                $this->success('预览已发送');
            } elseif ($result['errcode'] == 40132) {
                $this->error('您输入的微信号不合法');
            } elseif ($result['errcode'] == 43004) {
                $this->error('您输入的微信号未关注此公众号');
            } else {
                $this->error('预览错误：' . $result_str);
            }
        }
        
        node_log("【微信公众账号助手】素材编辑，素材ID号:{$id}"); // 记录日志
        $this->success("编辑成功");
    }
    
    // 素材编辑提交
    public function materialEditSubmit2() {
        $m_id = $id = I('id');
        $dao = M('twx_material');
        $count = $dao->where(
            "id='" . $id . "' and node_id='" . $this->nodeId . "'")->count();
        if (! $count) {
            $this->error("素材不存在");
        }
            $dataArr = I('dataJson');
            $material_type = count($dataArr) == 1 ? 1 : 2;
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
                $material_link = html_entity_decode($v['input_i-url']);
                $material_img = $v['input_i-material_img'];
                $show_cover_pic = $v['input_i-material_imgInsert'];
                $material_summary = $v['input_i-summary'];
                $m_id = $v['input_i-id'];
                $url_type = $v['input_i-url_type'];
                $batch_id = $v['input_i-batch_id'];
                $batch_type = $v['input_i-batch_type'];
                
                // 校验batch_id，batch_type有效性
                // 活动
                if ($url_type == '1' && $batch_id) {
                    $batch_info = $this->_getBatchInfo($batch_id);
                    
                    if (! $batch_info) {
                        $this->error("营销活动不存在");
                    }
                    
                    $material_desc = $v['input_i-material_desc'];
                    
                    // 获取链接地址
                    $material_link = $this->_getLabelUrl($batch_type, $batch_id);
                    if (! $material_link) {
                        $this->error("营销活动标签地址生成失败");
                    }
                } else {
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
                    'material_type' => $material_type, 
                    'batch_type' => $batch_type, 
                    'show_cover_pic' => $show_cover_pic);
                // 如果是新增
                if ($m_id == '') {
                    $data = array_merge($data, 
                        array(
                            'node_id' => $this->nodeId, 
                            'parent_id' => $parent_id, 
                            'material_level' => 2, 
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
                } else {
                    $fuText = $v['input_i-material_desc_richtxt'];
                    // 富文本
                    if ($url_type == '2') {
                        $data = array(
                            'material_text' => $fuText);
                        
                        // 更新twx_material素材表链接地址
                        $material_link = U('Label/FuWenText/index', 
                            array(
                                'id' => $m_id), '', '', true);
                        $result1 = M('twx_material')->where(
                            array(
                                'id' => $m_id))->save(
                            array(
                                'material_link' => $material_link));
                        if ($result1 === false) {
                            $dao->rollback();
                            $this->error("素材链接地址编辑失败");
                        }
                        
                        $result = M('twx_material_ex')->where(
                            array(
                                'material_id' => $m_id))->find();
                        
                        // 插入富文本素材表
                        if ($result) {
                            $resultSave = M('twx_material_ex')->where(
                                array(
                                    'material_id' => $m_id))->save($data);
                            if ($resultSave === false) {
                                $dao->rollback();
                                $this->error("素材编辑失败");
                            }
                        } else {
                            // 如果第一次没选富文本，则没有富文本素材id，编辑更新时添加一条
                            $data['material_id'] = $m_id;
                            $resultAdd = M('twx_material_ex')->add($data);
                            if (! $resultAdd) {
                                $dao->rollback();
                                $this->error("保存失败");
                            }
                        }
                    } else {
                        M('twx_material_ex')->where(
                            array(
                                'material_id' => $m_id))->delete();
                    }
                }
            }
        
        // $dao->rollback();
        
        $dao->commit();
        
        // 删除草稿表信息
        $where['node_id'] = $this->node_id;
        $where['type'] = '6';
        $where['org_id'] = $id;
        $result = $result = M('tdraft')->where($where)->delete();
        
        // 如果是预览，在关键词自动回复中插入一条数据
        if (I('preview') == 'preview') {
            $result = D('TweixinInfo')->preview($this->nodeId, $id);
            if ($resut) {
                log_write("关键词添加成功");
            }
        }
        
        node_log("【微信公众账号助手】素材编辑，素材ID号:{$id}"); // 记录日志
        $this->success("编辑成功");
    }

    public function autoSave() {
        $dataArr = I('data');
        $type = I('type');
        $key = array(
            'id', 
            'material_title', 
            'material_img', 
            'show_cover_pic', 
            'material_link', 
            'material_summary', 
            'batch_type', 
            'batch_id', 
            'material_desc', 
            'material_desc_richtxt', 
            'url_type', 
            'img_url');
        foreach ($dataArr as $k => $v) {
            $v = json_decode(htmlspecialchars_decode($v), true);
            $v['img_url'] = $this->_getImgUrl($v['input_i-material_img']);
            foreach ($v as &$_vv) {
                $_vv = htmlspecialchars($_vv);
            }
            unset($_vv);
            $value = array_values($v);
            
            $data = array_combine($key, $value);
            ($k == 0) ? $content = $data : $content['sub_material'][] = $data;
        }
        $org_id = $content['id'];
        if (! $org_id)
            $org_id = $type;
        $content = json_encode($content);
        $where = array(
            'node_id' => $this->node_id, 
            'type' => 6, 
            'org_id' => $org_id);
        $tdraft = M('tdraft')->where($where)->find();
        if (! $tdraft) {
            $status = M('tdraft')->add(
                array(
                    'node_id' => $this->node_id, 
                    'content' => $content, 
                    'add_time' => date('Y-m-d H:i:s'), 
                    'type' => 6, 
                    'org_id' => $org_id));
        } else {
            $status = M('tdraft')->where($where)->save(
                array(
                    'content' => $content, 
                    'add_time' => date('Y-m-d H:i:s')));
        }
        ($status === false) ? $status = 0 : $status = 1;
        $this->ajaxReturn($status);
    }
    
    // 删除草稿表信息
    public function del_tdraft() {
        $org_id = I('id');
        $type = I('type');
        if (! $org_id)
            $org_id = $type;
        $where['node_id'] = $this->node_id;
        $where['type'] = '6';
        $where['org_id'] = $org_id;
        $result = $result = M('tdraft')->where($where)->delete();
        $this->ajaxReturn($result);
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
                'material_summary' => '', 
                'material_desc' => '', 
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
        // 旧版
        if (basename($imgname) == $imgname) {
            return $this->uploadPath . $imgname;
        } else {
            return get_upload_url($imgname);
        }
    }
    
    // 群发图文消息
    public function materialImgTxtManage() {
        $dao = M('twx_material');
        $where = array(
            'node_id' => $this->nodeId, 
            'material_level' => 1, 
            'type' => 1);
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
            } else {
                $where['material_type'] = array(
                    'in', 
                    '1,2');
            }
            if (! empty($filter_date_start) && ! empty($filter_date_last)) {
                $where['add_time'] = array(
                    'between', 
                    array(
                        dateformat($filter_date_start, 'Ymd') . '000000', 
                        dateformat($filter_date_last, 'Ymd' . '235959')));
            }
        }
        $totalNum = $dao->where($where)->count();
        import("ORG.Util.Page"); // 导入分页类
        $Page = new Page($totalNum, 15);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "$key=" . urlencode($val) . "&";
        }
        $Page->setConfig('theme', 
            '%totalRow% %header% %nowPage%/%totalPage% 页 %first% %upPage% %linkPage% %downPage% %end%');
        $pageShow = $Page->show();
        
        $queryData = $dao->where($where)
            ->order("id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $materialArr = array();
        if(!$queryData) $queryData = array();
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
        
        $materialGroupArr = array();
        $k = 0;
        if(!$materialArr) $materialArr = array();
        foreach ($materialArr as $v) {
            $i = $k ++ % 3;
            $materialGroupArr[$i][] = $v;
        }
        $this->assign('materialGroupArr', $materialGroupArr);
        $this->assign('pageShow', $pageShow);
        $this->assign('totalNum', $totalNum);
        $this->display();
    }
    
    // 回复图文消息
    public function materialImgTxtManage2() {
        $dao = M('twx_material');
        $where = array(
            'node_id' => $this->nodeId, 
            'material_level' => 1, 
            'type' => 0);
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
            } else {
                $where['material_type'] = array(
                    'in', 
                    '1,2');
            }
            if (! empty($filter_date_start) && ! empty($filter_date_last)) {
                $where['add_time'] = array(
                    'between', 
                    array(
                        dateformat($filter_date_start, 'Ymd') . '000000', 
                        dateformat($filter_date_last, 'Ymd' . '235959')));
            }
        }
        $totalNum = $dao->where($where)->count();
        import("ORG.Util.Page"); // 导入分页类
        $Page = new Page($totalNum, 15);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "$key=" . urlencode($val) . "&";
        }
        $Page->setConfig('theme', 
            '%totalRow% %header% %nowPage%/%totalPage% 页 %first% %upPage% %linkPage% %downPage% %end%');
        $pageShow = $Page->show();
        
        $queryData = $dao->where($where)
            ->order("id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $materialArr = array();
        if(!$queryData) $queryData = array();
        foreach ($queryData as $k => $v) {
            // 处理图片
            $v['material_img_url'] = $this->_getImgUrl($v['material_img']);
            // 处理多图文,如果是子节点
            
            if ($v['material_type'] == '2') {
                $sub_meterial = $dao->where(
                    array(
                        'parent_id' => $v['id']))->select();
                if($sub_meterial){
                    foreach ($sub_meterial as &$vv) {
                        $vv['material_img_url'] = $this->_getImgUrl(
                            $vv['material_img']);
                    }
                    unset($vv);
                }
                $v['sub_material'] = $sub_meterial;
            }
            $materialArr[$v['id']] = $v;
        }
        
        $materialGroupArr = array();
        $k = 0;
        if(!$materialArr) $materialArr = array();
        foreach ($materialArr as $v) {
            $i = $k ++ % 3;
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
        $materialInfo = M('twx_material')->where(
            "id='" . $id . "' or parent_id='" . $id . "'")->select();
        $result = M('twx_material')->where(
            "id='" . $id . "' or parent_id='" . $id . "'")->delete();
        foreach ($materialInfo as $val) {
            $result = M('twx_material_ex')->where(
                "material_id='" . $val['id'] . "'")->delete();
            $filePath = $this->uploadPath . $val['material_img'];
            unlink($filePath);
        }
        log_write("【微信公众账号助手】素材删除，素材ID号:{$id}"); // 记录日志
        $this->success("删除成功");
    }
    
    // 图片管理
    public function materialImgManage() {
        $dao = M('twx_material');
        $where = array(
            'node_id' => $this->nodeId, 
            'material_type' => 3, 
            'material_level' => 0);
        
        if ($this->isPost()) {
            $filter_name = I('post.filter_name', '', 'trim');
            $filter_type = I('post.filter_type', 0, 'intval');
            $filter_date_start = I('post.filter_date_start', '', 'trim');
            $filter_date_last = I('post.filter_date_last', '', 'trim');
        }
        if (! empty($filter_name)) {
            $where['material_title'] = array(
                'like', 
                '%' . $filter_name . '%');
        }
        
        if (! empty($filter_date_start) && ! empty($filter_date_last)) {
            $where['add_time'] = array(
                'between', 
                array(
                    dateformat($filter_date_start, 'Ymd') . '000000', 
                    dateformat($filter_date_last, 'Ymd' . '235959')));
        }
        
        $totalNum = $dao->where($where)->count();
        import("ORG.Util.Page"); // 导入分页类
        $Page = new Page($totalNum, 12);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "$key=" . urlencode($val) . "&";
        }
        $Page->setConfig('theme', 
            '%totalRow% %header% %nowPage%/%totalPage% 页 %first% %upPage% %linkPage% %downPage% %end%');
        $pageShow = $Page->show();
        $queryData = $dao->where($where)
            ->order("id desc")
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        $materialArr = array();
        if(!$queryData) $queryData = array();
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
        $result = $dao->where(
            "node_id='" . $this->nodeId . "' and id='" . $mid .
                 "' and material_type='3'")->save($data);
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
        $result = $dao->where(
            "node_id='" . $this->nodeId . "' and id='" . $mid .
                 "' and material_type='3'")->delete();
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
    private function _getBatchInfo($batch_id) {
        $batch_info = M('tmarketing_info')->where(
            "node_id='" . $this->nodeId . "' and id='" . $batch_id . "'")->find();
        return $batch_info;
    }
    
    // 获取活动名称
    private function _getBatchType($batch_type) {
        $type_name = '';
        
        $type_name_arr = C('BATCH_TYPE_NAME');
        $type_name = $type_name_arr[$batch_type];
        if (! $type_name) {
            $this->error("未知活动类型【" . $batch_type . "】");
        }
        return $type_name;
    }
    
    // 获取活动内容列表
    private function _getBatchList($batch_type, $search = array(), &$pageShow = null) {
        $dao = M('tmarketing_info');
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
    
    // 消息管理
    public function user_msgmng() {
        $day = I('day', 0, 'intval');
        $star = I('star', 0, 'intval');
        $last_time = date('YmdHis');
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
            $last_time = $end_time;
        }         

        // 前天
        else if ($day == 3) {
            $begin_time = date('Ymd000000', strtotime("-2 day"));
            $end_time = date('Ymd235959', strtotime("-2 day"));
            $time_arr = array(
                'a.msg_time' => array(
                    array(
                        'EGT', 
                        $begin_time), 
                    array(
                        'ELT', 
                        $end_time), 
                    'and'));
            $last_time = $end_time;
        }         

        // 更早
        else if ($day == 4) {
            $begin_time = date('Ymd000000', strtotime("-4 day"));
            $end_time = date('Ymd235959', strtotime("-3 day"));
            $time_arr = array(
                'a.msg_time' => array(
                    array(
                        'EGT', 
                        $begin_time), 
                    array(
                        'ELT', 
                        $end_time), 
                    'and'));
            $last_time = $end_time;
        }         

        // 5天内
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

        //关键词消息
        $hide_flag = I('hide_flag', 0, 'intval');
        if ($hide_flag == 1) {
            $where['a.msg_response_flag'] = array(
                'neq', 
                '2');
        }
        //菜单消息
        $hide_flag2 = I('hide_flag2', 0, 'intval');
        if ($hide_flag2 == 1) {
            $where['a.msg_type'] = array(
                    'neq',
                    '5');
        }
        //消息内容
        $sendContent = I('sendContent', '');
        if (!empty($sendContent)) {
            $where['a.msg_info'] = array('like','%'.$sendContent.'%');
            $this->assign('sendContent',$sendContent);
        }
        
        $where = array_merge($where, $time_arr);
        
        if ($star) {
            $where = array_merge($where, 
                $star = array(
                    'a.star' => 1));
        }
        
        $dao = M('twx_msg_trace');
        import('ORG.Util.Page'); // 导入分页类
        $count = $dao->table('twx_msg_trace a')
            ->where($where)
            ->count(); // 查询满足要求的总记录数
        
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        $queryList = $dao->table('twx_msg_trace a')
            ->join(
            'twx_user b on b.openid = a.wx_id and b.node_wx_id = a.node_wx_id and b.node_id = a.node_id')
            ->field('a.*, b.headimgurl, b.nickname, b.remarkname')
            ->where($where)
            ->order('a.msg_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if(!$queryList) $queryList = array();
        foreach ($queryList as $key => $val) {
            $queryList[$key]['html'] = $this->_chat_msg_show($val['msg_type'], $val['msg_info']);
            /*if($val['msg_type'] == '5' && $val['msg_sign'] == '1'){
                unset($queryList[$key]);          //不要回复的内容
            }*/
        }
        
        $this->assign('last_time', $last_time);
        $this->assign('list', $queryList);
        $this->assign('count', $count);
        $this->assign('page', $pageShow);
        // 查询分组
        $where = "(node_id = '' or node_id = '{$this->node_id}') and (node_wx_id = '' or node_wx_id = '{$this->node_wx_id}')";
        $group_list = M('twx_user_group')->where($where)->getField('id, name', 
            true);
        // dump($queryList);exit;
        $this->assign('group_list', $group_list);
        
        $this->display();
    }
    
    // 消息管理星标记
    public function user_msgmng_star() {
        $msg_id = I('msg_id', 0, 'intval');
        if (! $msg_id) {
            $this->error('参数错误！');
        }
        
        $star = M('twx_msg_trace')->where(
            array(
                'msg_id' => $msg_id))->getField('star');
        $star ? $star = 0 : $star = 1;
        $result = M('twx_msg_trace')->where(
            array(
                'msg_id' => $msg_id))->save(
            array(
                'star' => $star));
        if ($result) {
            if (1 == $star) {
                $this->ajaxReturn(1, "收藏成功", 1);
            } else {
                $this->ajaxReturn(0, "取消成功", 1);
            }
        } else {
            $this->ajaxReturn($result, "操作失败", 2);
        }
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
        if(!$list) $list = array();
        foreach ($list as &$val) {
            $val['html'] = $this->_chat_msg_show($val['msg_type'], 
                $val['msg_info']);
        }
        
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
        $reply_text = I('reply_text', null,'');
//        $reply_text = I('reply_text', null, 'trim');
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
                log_write("插入流水表错误！语句：" . $model->_sql());
                $model->rollback();
                break;
            }
            
            // 更改原流水的处理状态
            if ($type == 0 && $info['msg_response_flag'] == '0') {
                $data = array(
                    'msg_response_flag' => '1');
                $flag = $model->where($where)->save($data);
                
                if ($flag === false) {
                    log_write("更新流水错误！语句：" . $model->_sql());
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
    
    // 获取最新的消息数
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
    
    // 互动有礼
    public function interact() {
        $message_id = I('message_id');
        $TweixinInfo = D('TweixinInfo');
        if (! $message_id) {
            $actType = 'add';
        } else {
            $info = $TweixinInfo->getInteractInfo($message_id);
            $actType = 'edit';
            $this->assign('info', $info);
        }
        $this->assign('actType', $actType);
        $this->assign('nodeId', $this->nodeId);
        $this->display();

    }
    //互动有礼的提交地址
    public function interactSubmit(){
        if(!IS_POST){
            $this->ajaxReturn(array('status'=>0,'info'=>'错误请求'),'JSON');
        }
        $postData = I('post.');
        /***********************表单提交的业务数据合理性*********************/
        if(empty($postData['ruleName'])){
            $this->ajaxReturn(array('status'=>0,'info'=>'活动名称不能为空'),'JSON');
        }
        if (dateformat($postData['begin_time'], 'YmdHis') > dateformat($postData['end_time'], 'YmdHis') || empty($postData['begin_time']) || empty($postData['end_time'])) {
            $this->ajaxReturn(array('status'=>0,'info'=>'活动时间错误'),'JSON');
        }
        if(empty($postData['keywordStr'])){
            $this->ajaxReturn(array('status'=>0,'info'=>'请设置关键词'),'JSON');
        }

        $tipStr = '卡券错误';
        $hasCard = count($postData['goodsId']);
        if($hasCard <= 0){
            $this->ajaxReturn(array('status'=>0,'info'=>'请选择卡券！'),'JSON');
        }
        foreach($postData['goodsId'] as $key => $value){

            $type = '卡券：'.$postData['goodsName'][$key];
            if($postData['respContent'][$key] == 6){
                $type = '红包：'.$postData['goodsName'][$key];
            }

            if(!is_numeric($postData['source'][$key]) || !is_numeric($postData['cardCount'][$key]) || !is_numeric($postData['sendType'][$key]) || !is_numeric($postData['dayLimit'][$key])){
                $tipStr = $type.'提交错误';
                $this->ajaxReturn(array('status'=>0,'info'=>$tipStr),'JSON');
            }

            if(empty($postData['respContent'][$key])){
                $tipStr = $type.'的回复内容不能为空';
                $this->ajaxReturn(array('status'=>0,'info'=>$tipStr),'JSON');
            }
        }
        $postData['userId'] = $this->userId;
        $TweixinInfo = D('TweixinInfo');
        if(empty($postData['messageId'])){       //添加
            $result = $TweixinInfo->interactAdd($postData, $this->nodeId);
            if(isset($result['status']) && $result['status']==0){
                $this->error($result['info']);
            }
            if($result['hasCard'] && $result['return'] && $result['cardNum']){
                $this->success('创建活动成功！');
            }
            if(!$result['hasCard']){
                log_write('errorSql:'.$result['SQL']);
                $this->error('卡券错误');
            }
            //卡券数量的检测结果
            if(!$result['cardNum']){
                log_write('errorSql:'.$result['SQL']['tipStr']);
                foreach($postData['goodsId'] as $key => $value){
                    if($value == $result['SQL']['goodsId']){
                        $tipStr = '卡券：'.$postData['goodsName'][$key].'的投放数量不能大于库存';
                        if($postData['source'][$key] == 6){
                            $tipStr = '红包：'.$postData['goodsName'][$key].'的投放数量不能大于库存';
                        }
                    }
                }
                $this->error($tipStr);
            }
            log_write('errorSql:'.$result['SQL']);
            $this->error('创建失败');

        }else{                                   //修改
            $result = $TweixinInfo->interactEdit($postData, $this->nodeId);
            if(isset($result['status']) && $result['status']==0){
                $this->error($result['info']);
            }
            if($result['hasCard'] && $result['return'] && $result['cardNum']){
                $this->success('修改成功！');
            }
            if(!$result['hasCard']){
                log_write('errorSql:'.$result['SQL']);
                $this->error('卡券错误');
            }
            //卡券数量的检测结果
            if(!$result['cardNum']){
                log_write('errorSql:'.$result['SQL']['tipStr']);
                foreach($postData['goodsId'] as $key => $value){
                    if($value == $result['SQL']['goodsId']){
                        $tipStr = '卡券：'.$postData['goodsName'][$key].'的投放数量不能大于库存';
                        if($postData['source'][$key] == 6){
                            $tipStr = '红包：'.$postData['goodsName'][$key].'的投放数量不能大于库存';
                        }
                    }
                }
                $this->error($tipStr);
            }
            log_write('errorSql:'.$result['SQL']);
            $this->error('修改失败');
        }
    }
    //互动有礼删除
    public function interact_del() {
        $message_id = I('message_id');
        $result = D('TweixinInfo')->interactDelete($message_id, $this->nodeId);
        if($result){
            $this->ajaxReturn(array('status'=>1,'info'=>'删除成功'),'JSON');
        }else{
            $this->ajaxReturn(array('status'=>0,'info'=>'删除失败'),'JSON');
        }
    }
    //互动有礼列表页
    public function interact_created() {
        $where['response_type'] = 6;
        $where['status'] = 0;
        $where['node_id'] = $this->node_id;
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M('twx_message')->where($where)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出

        $list = M('twx_message')->field(
            'id,message_name,add_time,begin_time,end_time,receive_num')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->where($where)
            ->order('id desc')
            ->select();
        $this->assign('list', $list);
        $this->assign('page', $show);
        $this->display();
    }
    //互动有礼的明细详情页
    public function interact_detail() {
        $message_id = I('message_id');
        $down = I('down', '0');
        import('ORG.Util.Page'); // 导入分页类
        $sql = "select count(*) from (SELECT count(id) as num,`card_id` FROM `twx_msgkwd_trace` WHERE ( message_id =" .
             $message_id . " ) GROUP BY card_id ) as t";
        $mapcount = M()->query($sql);
        $mapcount = $mapcount[0]['count(*)'];
        
        $Page = new Page($mapcount, 5); // 实例化分页类 传入总记录数和每页显示的记录数
        $show = $Page->show(); // 分页显示输出
        $list = M()->table("twx_msgkwd_trace t1")
            ->where('t1.message_id =' . $message_id)
            ->field('count(t1.id) as num,t1.card_id,t1.message_id')
            ->group('t1.card_id')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('id desc')
            ->select();
        $title_ = '活动名称,生效时间,';
        if(!$list) $list = array();
        foreach ($list as $key => $val) {
            if($this->nodeId == C('new_year_node_id')){
                $list[$key]['name'] = M('tgoods_info')->where("goods_id ='".$val['card_id']."'")->getField('goods_name');
            }else{
                $card_info = M('twx_card_type')->where(
                    "card_id ='" . $val['card_id'] . "'")->find();
                $list[$key]['name'] = $card_info['title'];
                $title_ .= '卡券名称（领取量）,';
            }
        }
        if($message_id == '590'){
            $fans_list = M()->table("twx_msgkwd_trace t1")
                ->join('twx_user t2 on t1.open_id = t2.openid and t1.node_id = t2.node_id')
                ->join('twx_message t4 on t1.message_id = t4.id')
                ->join('twx_bonus_send_trace t3 on t4.m_id = t3.m_id and t1.open_id = t3.openid')
                ->where("t1.message_id='".$message_id."' and t3.status='0'")
                ->field('t1.open_id,t2.nickname')
                ->select();
        }else{
            $fans_list = M()->table("twx_msgkwd_trace t1")
                ->join('twx_user t2 on t1.open_id = t2.openid and t1.node_id = t2.node_id')
                ->join('twx_bonus_send_trace t3 on t1.request_id = t3.request_id')
                ->where("t1.message_id='".$message_id."' and t3.status='0'")
                ->field('t1.open_id,t2.nickname')
                ->select();
        }
        $count = count($fans_list);
        $message_info = M('twx_message')->where("id=" . $message_id)->find();
        if ($message_info['begin_time'] && $message_info['end_time']) {
            $message_info['time'] = dateformat($message_info['begin_time'], 
                "Y-m-d H:i:s") . '至' .
                 dateformat($message_info['end_time'], "Y-m-d H:i:s");
        } elseif ($message_info['begin_time'] && ! $message_info['end_time']) {
            $message_info['time'] = dateformat($message_info['begin_time'], 
                "Y-m-d H:i:s") . '起';
        } elseif (! $message_info['begin_time'] && $message_info['end_time']) {
            $message_info['time'] = dateformat($message_info['add_time'], 
                "Y-m-d H:i:s") . '至' .
                 dateformat($message_info['end_time'], "Y-m-d H:i:s");
        } elseif (! $message_info['begin_time'] && ! $message_info['end_time']) {
            $message_info['time'] = '永久有效';
        }
        if ($down == 1) {
            $this->hpybDownload($message_id);
            exit();
        }
        if($this->nodeId == C('new_year_node_id')){
            $this->assign('count', $count);
            $this->assign('new_year_node_id', '1');
        }
        $this->assign('list', $list);
        $this->assign('message_info', $message_info);
        $this->assign('message_id', $message_id);
        $this->assign('page', $show);
        $this->display();
    }
    //互动有礼下载粉丝数据
    public function interactDownload($message_id){
        $fileName = '卡券投放总量.csv';
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        // 获取粉丝
        $where = array(
                'a.node_id' => $this->node_id,
                'a.node_wx_id' => $this->node_wx_id,
                'a.subscribe' => 1,
                'b.message_id'  => $message_id
        );
        $join = ' a inner join twx_msgkwd_trace b on a.openid=b.open_id tgoods_info c on b.card_id=c.goods_id ';
        //获取到红包的名单
        $redPackageList = M('twx_user')->field(' a.nickname,b.add_time,c.goods_name ')->join($join)->where($where)->select();
        $join = ' a inner join twx_msgkwd_trace b on a.openid=b.open_id twx_card_type c on b.card_id=c.goods_id ';
        //获取到卡券的名单
        $cardList = M('twx_user')->field(' a.nickname,b.add_time,c.title as goods_name ')->join($join)->where($where)->select();

        $list = array_merge($redPackageList,$cardList);

        $title = "卡券名称,粉丝昵称,领取时间 \r\n";
        echo iconv('utf-8', 'gbk', $title);
        foreach($list as $key => $value){
            $time = date('Y年m月d日',strtotime($value['add_time']));
            echo iconv('utf-8', 'gbk', $value['goods_name']) . ", ";
            echo iconv('utf-8', 'gbk', $value['goods_name']) . ", ";
            echo iconv('utf-8', 'gbk', $time) . "\r\n";
        }
    }

    // 呼朋引伴
    public function hpyb() {
        $TweixinInfo = D('TweixinInfo');
        $info = $TweixinInfo->getHpybInfo($this->nodeId);
        $flag = $info['message_info'] ? 1 : 0;
        $this->assign('flag', $flag);
        $this->assign('id', $info['message_info']['id']);
        $this->assign('info', $info);
        $this->assign('card_info', $info['card_info'][0]);
        $this->display();
    }
    //呼朋引伴的提交地址
    public function hpybSubmit(){
        if(!IS_POST){
            $this->ajaxReturn(array('status'=>0,'info'=>'错误请求'),'JSON');
        }
        $postData = I('post.');

        /*****************************表单提交的业务数据合理性  *********************/
        if(empty($postData['goodsId']) || !is_numeric($postData['source']) || !is_numeric($postData['cardCount']) || !is_numeric($postData['sendType']) || !is_numeric($postData['dayLimit'])){
            $this->ajaxReturn(array('status'=>0,'info'=>'错误提交'),'JSON');
        }
        if($postData['sendType'] == 1 && !is_int((int)$postData['dayLimit'])){
            $this->ajaxReturn(array('status'=>0,'info'=>'奖品每日上限错误'),'JSON');
        }
        if(empty($postData['respContent'])){
            $this->ajaxReturn(array('status'=>0,'info'=>'回复内容不能为空'),'JSON');
        }
        if(empty($postData['shareTitle'])){
            $this->ajaxReturn(array('status'=>0,'info'=>'分享标题不能为空'),'JSON');
        }
        if(empty($postData['shareDesc'])){
            $this->ajaxReturn(array('status'=>0,'info'=>'分享描述不能为空'),'JSON');
        }
        if(empty($postData['img_resp'])){
            $this->ajaxReturn(array('status'=>0,'info'=>'请设置分享图标'),'JSON');
        }
        if(empty($postData['color'])){
            $this->ajaxReturn(array('status'=>0,'info'=>'请设置页面背景色'),'JSON');
        }
        if(empty($postData['respDesc'])){
            $this->ajaxReturn(array('status'=>0,'info'=>'活动描述不能为空'),'JSON');
        }
        if(empty($postData['guide_url'])){
            $this->ajaxReturn(array('status'=>0,'info'=>'引导关注页不能为空'),'JSON');
        }

        /***********************开始提交数据 预备写操作*************/
        //分享内容
        $configData = array(
                'share_title'       => $postData['shareTitle'],
                'share_descript'    => $postData['shareDesc'],
                'share_logo'        => $postData['img_resp']
        );
        $active = '呼朋引伴';
        $postData['userId'] = $this->userId;
        $TweixinInfo = D('TweixinInfo');
        if(empty($postData['messageId'])){           //添加

            $result = $TweixinInfo->addReplyMessage($postData,$this->node_id,$active,$configData,1);
            if($result['hasCard'] && $result['return'] && $result['cardNum']){
                $this->ajaxReturn(array('status'=>1,'info'=>'设置成功'),'JSON');
            }
            if(!$result['cardNum']){
                log_write($active.'errorSql:'.$result['SQL']['tipStr']);
                $this->ajaxReturn(array('status'=>0,'info'=>$postData['source']==6?'红包数量不足':'卡券数量不足'),'JSON');
            }
            if(!$result['hasCard']){
                log_write($active.'errorSql:'.$result['SQL']);

                $this->ajaxReturn(array('status'=>0,'info'=>$postData['source']==6?'红包错误':'卡券错误'),'JSON');
            }
            log_write($active.'errorSql:'.$result['SQL']);
            $this->ajaxReturn(array('status'=>0,'info'=>'设置失败'),'JSON');

        }else{                                      //修改
            $result = $TweixinInfo->editReplyMessage($postData,$this->node_id,$configData, 1);
            if($result['hasCard'] && $result['return'] && $result['cardNum']){
                $this->ajaxReturn(array('status'=>1,'info'=>'修改成功'),'JSON');
            }
            if(!$result['hasCard']){
                log_write('edit errorSql:'.$result['SQL']);
                $this->ajaxReturn(array('status'=>0,'info'=>$postData['source']==6?'红包错误':'卡券错误'),'JSON');
            }
            if(!$result['cardNum']){
                log_write($active.'errorSql:'.$result['SQL']['tipStr']);
                $this->ajaxReturn(array('status'=>0,'info'=>$postData['source']==6?'红包数量不足':'卡券数量不足'),'JSON');
            }
            log_write('exit  errorSql:'.$result['SQL']);
            $this->ajaxReturn(array('status'=>0,'info'=>'修改失败'),'JSON');

        }
    }
    // 呼朋引伴关闭
    public function hpyb_del() {
        $result = D('TweixinInfo')->hpybDel($this->nodeId);
        $this->ajaxReturn($result,'JSON');
    }
    //呼朋引伴的列表页
    public function hpyb_static(){
        $list = D('TweixinInfo')->getHpybList($this->nodeId);
        $this->assign('list', $list['list']);
        $this->assign('page', $list['page']);
        $this->display();
    }
    //呼朋引伴的明细详情页
    public function hpybDetail(){
        $id = I('get.id');
        $down = I('get.down',0);
        $messageInfo = M('twx_message')->where(array('id'=>$id))->find();

        $cardList = M('tbatch_info')->field(' b.source,b.goods_name,c.title ')->join(' a inner join tgoods_info b on a.goods_id=b.goods_id inner join twx_card_type c on b.goods_id=c.goods_id ')->where(array('a.m_id'=>$messageInfo['m_id']))->select();
        $names = array();
        foreach($cardList as $key => $value){
            if($value['source'] == 6){          //红包
                $names[] = $value['goods_name'];
            }else{
                $names[] = $value['title'];
            }
        }
        $messageInfo['goods_name'] = implode(',',$names);
        //下载
        if($down == 1){
            $this->hpybDownload($id);
            exit;
        }
        if(empty($messageInfo['end_time'])){
            $messageInfo['time'] = date('Y年m月d H:i:s',strtotime($messageInfo['begin_time'])).'至今';
        }else{
            $messageInfo['time'] = date('Y年m月d日 H:i:s',strtotime($messageInfo['begin_time'])).'至'.date('Y年m月d日 H:i:s',strtotime($messageInfo['end_time']));
        }
        $this->assign('id',$id);
        $this->assign('messageInfo',$messageInfo);
        $this->display();
    }
    //下载呼朋引伴粉丝数据
    public function hpybDownload($message_id){
        //卡券
        $sql = " select*from (";
        $sql .= "select a.subscribe,a.node_wx_id,a.node_id,a.nickname,b.add_time,b.message_id,c.title goods_name from twx_user a inner join twx_msgkwd_trace b on a.openid=b.open_id inner join twx_card_type c on b.card_id=c.card_id";
        //红包
        $sql.=" UNION ALL ";
        $sql.= "select a.subscribe,a.node_wx_id,a.node_id,a.nickname,b.add_time,b.message_id,c.goods_name from twx_user a inner join twx_msgkwd_trace b on a.openid=b.open_id inner join tgoods_info c on b.card_id=c.goods_id) u";
        $sql.= " where u.node_id='{$this->node_id}' and u.node_wx_id='{$this->node_wx_id}' and u.message_id='{$message_id}'";
        $activityList = M()->query($sql);

        $title = "领取时间,奖品名称,粉丝昵称 \r\n";
        $fileName = '活动数据.csv';

        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $title = iconv('utf-8', 'gbk', $title);
        echo $title;
        foreach($activityList as $key => $value) {
            $time = date('Y年m月d日 H:i:s',strtotime($value['add_time']));
            echo iconv('utf-8', 'gbk', $time).',';
            echo iconv('utf-8', 'gbk', $value['goods_name']).',';
            echo iconv('utf-8', 'gbk', $value['nickname']) . "\r\n";
        }
    }
    /**
     * 添加奖品第一步选择奖品
     */
    public function addAward() {
        $m_id = I('m_id', '');
        $prizeCateId = I('prizeCateId', '');
        $b_id = I('b_id', '');

        if (! $b_id) { // 如果没有b_id表示添加奖品
            $this->redirect('Common/SelectJp/indexNew',
                    array(
                            'next_step' => urlencode(
                                    U('Weixin/Weixin/cardConfig',
                                            array(
                                                    'm_id' => $m_id,
                                                    'prizeCateId' => $prizeCateId))),
                            'availableTab' => '1,4',            //最上面一排的切换按钮
                            'availableGoodsType'  => '0,1,2,3', //第二个下拉框
                            'availableSourceType'   => '3',      //左边的下拉框
                            'storeMode'            => '2',       //仅微信奖品使用（1投放  2预存）
                            'goods_type'            => '0,1,2,3' //初始化的时候显示的卡券类型
                            ));
        }
    }
    /**
     * 微信奖品中弹窗里的配置页
     */
    public function cardConfig()
    {
        $isEdit = I('get.isEdit',0);       //奖品ID
        $prizeCateId = I('get.prizeId','');       //奖品ID
        $cardId = I('get.card_id','');            //微信卡券ID
        $batchId = I('get.batchId','');           //活动ID
        $index = I('get.index',0);                //多奖品的时候的奖品下标
        $useIng = 0;                              //标记当前卡券是否被使用中
        if(!empty($cardId)){                      //卡券
            $join = ' a LEFT JOIN tgoods_info b ON a.goods_id=b.goods_id';
            $where = array('a.node_id'=>$this->nodeId,'a.goods_id'=>$prizeCateId);
            $cardData = M('twx_card_type')->join($join)->where($where)->find();
        }else{                                    //红包
            $where = array('node_id'=>$this->nodeId,'source'=>6,'goods_id'=>$prizeCateId);
            $cardData = M('tgoods_info')->where($where)->find();
        }
        if($cardData['date_type'] == 1){
            $cardData['strTime'] = date('Ymd',$cardData['date_begin_timestamp']);
            $cardData['endTime'] = date('Ymd',$cardData['date_end_timestamp']);
        }
        //检测当前卡券是否在其他活动中被使用
        $isUse = M('tbatch_info')->where(array('node_id'=>$this->node_id,'goods_id'=>$prizeCateId))->count();
        if($isUse > 1){
            $useIng = 1;
        }

        $this->assign('batchId',$batchId);
        $this->assign('useIng',$useIng);
        $this->assign('isEdit',$isEdit);
        $this->assign('index',$index);
        $this->assign('cardData',array_filter($cardData,function($var)
        {
            if($var === '0' || !empty($var)){
                return true;
            }else{
                return false;
            }
        }));
        $this->display();

    }
    /**
     * 编辑微信奖品里的配置
     */
    public function editCardConfig(){
        $this->assign('isEdit','1');
        $this->display('cardConfig');
    }

    // 查询批量发送用户
    public function batch_send_count() {
        $group_id = I('group_id', '');
        $sex = I('sex', 0, 'intval');
        $keywords = I('keywords', '', 'trim');
        $nickname = I('nickname', '', 'trim');
        $pid = I('province', 0, 'trim');
        $cid = I('city', 0, 'trim');
        $clientName = I("cN", '', 'trim');
        $clientGroup = I("cG", '', 'trim');
        $clientType = I("cT", '', 'trim');
        $scene = I('scene', '', 'trim');
        
        // 根据条件查询需要批量发送的粉丝
        $where = array(
            't.node_id' => $this->node_id, 
            't.node_wx_id' => $this->node_wx_id, 
            't.subscribe' => '1');
        
        if (in_array($sex, array(
            1, 
            2), true))
            $where['t.sex'] = $sex;
        if ($scene != '') {
            if ('' != $scene) {
                $fsfromId = M()->table("twx_qrchannel wq")->join(
                    'tchannel c on c.id=wq.channel_id')
                    ->where(
                    array(
                        'c.name' => array(
                            'like', 
                            '%' . $scene . '%')))
                    ->field('wq.scene_id')
                    ->select();
                if(!$fsfromId) $this->ajaxReturn(array('countTotal' => 0), '没有找到对应的粉丝来源！', 0);
                    foreach ($fsfromId as &$v) {
                        $scenc_id[] = $v['scene_id'];
                    }
                    $where['t.scene_id'] = array(
                        'in', 
                        $scenc_id);
            }
        }
        if ($group_id != '')
            $where['t.group_id'] = array('in',$group_id);
        
        if (0 != $pid) {
            $province = M('tcity_code')->where(
                "city_level=1 and province_code=$pid")->getfield('province');
            $where['t.province'] = $province;
        }
        
        if (0 != $cid) {
            $city = M('tcity_code')->where("city_level=2 and city_code=$cid")->getfield(
                'city');
            $city = str_replace('市', '', $city);
            $where['t.city'] = $city;
        }
        
        if ($keywords)
            $where['t.remarkname'] = array(
                'like', 
                '%' . $keywords . '%');
        
        if($nickname) $where['t.nickname'] = $nickname;
        
        if ('' != $clientName) {
            $where['n.node_name'] = $clientName;
        }
        if ('' != $clientGroup) {
            $where['i.industry_code'] = $clientGroup;
            // M('tindustry_info')->where(array('industry_code'=>$clientGroup))->find();
        }
        if ('' != $clientType) {
            // 旺财版本 v0 => 旺财免费版, v0.5 => 旺财认证版, v1 => 旺财标准版, v2 => 旺财电商版, v3 =>
            // 旺财全民营销版, v4 => 旺财演示版, v5 => 旺财微博版, v6 => 旺财凭证活动版, v7 => 旺财凭证版
            $where['n.wc_version'] = $clientType;
        }
        
        if ($this->node_id_wc == $this->node_id) {
            import('ORG.Util.Page'); // 导入分页类
            $count = M()->table("twx_user t")->join(
                'tnode_info n ON n.node_id=w.node_id')
                ->join('tindustry_info i ON i.industry_code=n.trade_type')
                ->where($where)
                ->count();
        } else {
            $model = M("twx_user");
            $countTotal = $model->alias("t")->where($where)->count();
            
            // 服务号判断单条粉丝群发接受量
            $where['send_month'] = date('m');
            $userCount = M()->table("twx_user t")->where($where)
                ->field('send_month_stat')
                ->select();
            
            $i = 0;
            if ($userCount) {
                foreach ($userCount as $k => $v) {
                    if ($v['send_month_stat'] >= 4) {
                        $i ++;
                    }
                }
            }
            $countFull = $i;
            
            $count = array(
                'countTotal' => $countTotal, 
                'countFull' => $countFull);
        }
        
        if ($count == 0 || ! $count) {
            $this->ajaxReturn($count, '没有找到对应的用户！', 0);
        }else {
            $this->ajaxReturn($count, "查找成功！", 1);
        }
    }
    
    public function hongbao(){
        $TweixinInfo = D('TweixinInfo');
        $goodsModel = D('Goods');
        $group_list = $TweixinInfo->getWxGroupList($this->node_id, $this->node_wx_id);
        if($this->isPost()){
            $fans_info = $TweixinInfo->getFansInfo($this->node_id, $this->node_wx_id);
            $fans_count = count($fans_info);
            if($fans_count > 50000) $this->error('一次可发5万条，请分批下发');
            $name = I('zfb_account');
            $goodsId = I('goods_id');
            $remain_num = I('remain_num');
            $send_timing = I('send_timing', '', 'trim');
            // $this->error($fans_count);
            if($remain_num < $fans_count) $this->error('微信红包库存不足，请补充库存');
            $result = $TweixinInfo->hongbao($this->nodeId,$this->userId,$fans_info,$name,$goodsId,$remain_num,$send_timing);
            if ($result['status'] == 1) {
                $this->error($result['info']);
            } else {
                $batch_id = $result['batch_id'];
                $goodsModel->storagenum_reduc($goodsId, $fans_count, $batch_id, 0,'微信助手-发红包');
                $this->success($result['info']);
            }
        }
        $this->assign('group_list',$group_list);
        $this->display();
    }
    
    public function hongbao_toSend(){
        $TweixinInfo = D('TweixinInfo');
        $list = $TweixinInfo->hongbaoToSendInfo($this->node_id);
        $this->assign('list', $list['list']);
        $this->assign('page', $list['page']);
        $this->display();
    }
    
    public function hongbaoDel(){
        $TweixinInfo = D('TweixinInfo');
        $goodsModel = D('Goods');
        $id = I('id','');
        $bonus_send_id = I('bonus_send_id','');
        $batch_id = I('batch_id','');
        $total_count = I('total_count',0);
        $goodsId = M('tbatch_info')->where('id = '.$batch_id)->getField('goods_id');
        $result = $TweixinInfo->hongbaoDel($id,$bonus_send_id);
        if ($result['status'] == 1) {
            $this->error($result['info']);
        } else {
            $goodsModel->storagenum_reduc($goodsId, -1 * $total_count, $batch_id, 1,'微信助手-发红包');
            $this->success($result['info']);
        }
    }
    
    public function editSendTime(){
        $id = I('id','');
        $send_time = I('send_time','');
        $send_time = dateformat($send_time, 'YmdHis');
        $result = M('twx_bonus_send')->where("m_id=".$id)->save(array('send_time'=>$send_time));
        if($result === false){
            $this->error('修改失败');
        }else{
            $this->success('修改成功！');
        }
    }
    
    public function hongbao_static(){
        $TweixinInfo = D('TweixinInfo');
        $list = $TweixinInfo->hongbaoStaticInfo($this->node_id);
        $this->assign('list', $list['list']);
        $this->assign('page', $list['page']);
        $this->display();
    }
    
    public function hongbaoStaticDel(){
        $TweixinInfo = D('TweixinInfo');
        $id = I('id','');
        $result = $TweixinInfo->hongbaoStaticDel($id);
        if ($result['status'] == 1) {
            $this->error($result['info']);
        } else {
            $this->success($result['info']);
        }
    }
    
    public function hongbao_staticDetail(){
        $TweixinInfo = D('TweixinInfo');
        $id = I('id','');
        $down = I('down', '0');
        if($down){
            $result = $TweixinInfo->hongbaoStaticDetail($id,$down);
            $list = $result['list'];
            $info = $result['info'];
            $fileName = '发送明细表.csv';
            header("Content-type: text/plain");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: attachment; filename=" . $fileName);
            header(
                "Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            
            echo iconv('utf-8', 'gbk', "活动名称,红包名称,发放数量,发放时间\r\n");
            echo iconv('utf-8', 'gbk', $info['name']) . ", ";
            echo iconv('utf-8', 'gbk', $info['batch_name']) . ", ";
            echo iconv('utf-8', 'gbk', $info['total_count']) . ", ";
            echo iconv('utf-8', 'gbk', $info['send_time']) . "\r\n";
            echo iconv('utf-8', 'gbk',"\r\n");
            echo iconv('utf-8', 'gbk',"\r\n");
            if ($list) {
                foreach ($list as $value) {
                    echo iconv('utf-8', 'gbk', $value['nickname']) . ", ";
                    echo iconv('utf-8', 'gbk', $value['open_id']) . "\r\n";
                }
            }
            exit();
        }else{
            $result = $TweixinInfo->hongbaoStaticDetail($id);
            $info = $result['info'];
            $this->assign('info',$info);
            $this->assign('id',$id);
            $this->display();
        }
    }
    
    // 批量发送
    public function batch_send() {
        $count = D('TweixinInfo')->getSendNum($this->node_wx_id, 
            $this->account_type);
        
        // 查询服务号本月是否已经发满
        $mass_premonth = C('WEIXIN_MASS_PREMONTH');
        $mass_maxnum = C('WEIXIN_MASS_MAXNUM');
        if (4 == $this->account_type) {
            $count = ($count >= $mass_premonth) ? 0 : $mass_premonth - $count;
        }
        
        // 查询订阅号今日是否已经发满
        if (2 == $this->account_type) {
            $count = ($count >= 1) ? 0 : 1;
        }
        
        // 查询分组
        $where = "(node_id = '' or node_id = '{$this->node_id}') and (node_wx_id = '' or node_wx_id = '{$this->node_wx_id}')";
        $group_list = M('twx_user_group')->where($where)->getField('id,name');
        if ($this->isPost()) {
            set_time_limit(0);
                // 数据校验
            $reply_type = I('reply_type', 0, 'trim,intval'); // 0-文本 1-图片 2-图文 3-语音 4-卡券'
            
            $reply_text = I('reply_text', 0, 'trim');
            $reply_text = str_ireplace('&nbsp;', " ", $reply_text);
            $material_id = I('material_id', 0, 'intval');
            $card_id = I('cardid', 0, 'trim');
            $group_id = I('group_id','');
            $sex = I('sex', 0, 'intval');
            $keywords = I('keywords', '', 'trim');
            $pid = I('province', 0, 'trim');
            
            $cid = I('city', 0, 'trim');
            $clientName = I("cN", '', 'trim');
            $clientGroup = I("cG", '', 'trim');
            $clientType = I("cT", '', 'trim');
            $scene = I('scene', '', 'trim');
            $send_mode = I('send_mode', '', 'trim');
            $send_timing = I('send_timing', '', 'trim');
            $send_timing = dateformat($send_timing, 'YmdHis');
            
            // 是否全部粉丝发送
            $is_to_all = ($group_id == '' && $sex == 0 && $pid == 0 &&
                 $keywords == '' && $scene == '') ? 1 : 2;
            
            if ($send_mode == 2 && $send_timing < date('YmdHis'))
                $this->error('日期超出限定范围');
            
            if ($is_to_all == 1 && $count == 0)
                $this->error('群发失败 请重新选择群发对象');
            
            if (! in_array($reply_type, 
                array(0, 1, 2, 4), true))
                $this->error('参数[reply_type]错误！');
            
            if (! in_array($sex, 
                array(0, 1, 2), true))
                $this->error('参数[sex]错误！');
                
                // 根据条件查询需要批量发送的粉丝
            $where = array(
                't.node_id' => $this->node_id, 
                't.node_wx_id' => $this->node_wx_id, 
                't.subscribe' => '1');
            
            if ($is_to_all == 2) {
                if (in_array($sex, 
                    array(1, 2), true))
                    $where['t.sex'] = $sex;
                if ($group_id != '')
                    $where['t.group_id'] = array('in',$group_id);
                
                if ('' != $pid) {
                    $province = M('tcity_code')->where(
                        "city_level=1 and province_code=$pid")->getfield(
                        'province');
                    
                    $where['t.province'] = $province;
                }
                
                if ('' != $cid) {
                    $city = M('tcity_code')->where(
                        "city_level=2 and city_code=$cid")->getfield('city');
                    $city = str_replace('市', '', $city);
                    $where['t.city'] = $city;
                }
                
                if ($scene != '') {
                    if ('' != $scene) {
                        $fsfromId = M()->table("twx_qrchannel wq")->join(
                            'tchannel c on c.id=wq.channel_id')
                            ->where(
                            array(
                                'c.name' => array(
                                    'like', 
                                    '%' . $scene . '%')))
                            ->field('wq.scene_id')
                            ->select();
                        
                        foreach ($fsfromId as &$v) {
                            $scenc_id[] = $v['scene_id'];
                        }
                        $where['t.scene_id'] = array(
                            'in', 
                            $scenc_id);
                    }
                }
                
                if ($keywords)
                    $where['t.remarkname'] = array(
                        'like', 
                        '%' . $keywords . '%');
                if ('' != $clientName) {
                    $where['n.node_name'] = $clientName;
                }
                if ('' != $clientGroup) {
                    $where['i.industry_code'] = $clientGroup;
                    // M('tindustry_info')->where(array('industry_code'=>$clientGroup))->find();
                }
                if ('' != $clientType) {
                    // 旺财版本 v0 => 旺财免费版, v0.5 => 旺财认证版, v1 => 旺财标准版, v2 =>
                    // 旺财电商版, v3 => 旺财全民营销版, v4 => 旺财演示版, v5 => 旺财微博版, v6 =>
                    // 旺财凭证活动版, v7 => 旺财凭证版
                    $where['n.wc_version'] = $clientType;
                }
            }
            
            $model = M("twx_user");
            if ($this->node_id_wc == $this->node_id) {
                import('ORG.Util.Page'); // 导入分页类
                $fans_count = $model->alias("t")->join(
                    'tnode_info n ON n.node_id=w.node_id')
                    ->join('tindustry_info i ON i.industry_code=n.trade_type')
                    ->where($where)
                    ->count();
                
                $fans_list = array();
                $fans_list = $model->alias("t")->join('tnode_info n ON n.node_id=w.node_id')
                    ->join('tindustry_info i ON i.industry_code=n.trade_type')
                    ->where($where)
                    ->getField('t.id, t.openid', true);
                $fans_list = array_values($fans_list);
            } else {
                $fans_count = $model->alias("t")->where($where)->count();
                
                $fans_list = array();
                $fans_list = $model->alias("t")->where($where)->getField('t.id, t.openid',
                    true);
                $fans_list = array_values($fans_list);
            }
            if ($fans_count == 0) {
                $this->error('没有找到查询条件对应的用户！');
            }
            if ($fans_count == 1) {
                $this->error('至少为两位粉丝发送！');
            }
            
            /*
             * if($fans_count > $mass_maxnum){
             * $this->error('群发用户不能超过'.$mass_maxnum); }
             */
            
            // 图文素材
            if ($reply_type == 2) {
                $material_info = M('twx_material')->find($material_id);
                if (! $material_info ||
                     $material_info['node_id'] != $this->node_id)
                    $this->error('素材错误');
            }
            
            // 卡券
            if ($reply_type == 4) {
                $card_info = M('twx_card_type')->where(
                    array(
                        'card_id' => $card_id))->find();
                if (! $card_info)
                    $this->error('卡券错误');
                if ($card_info['quantity'] - $card_info['card_get_num'] <
                     $fans_count)
                    $this->error('卡券库存不足，请补充库存');
            }
            
            M()->startTrans();
            // 插入批量发送主表
            $data = array(
                'node_wx_id' => $this->node_wx_id, 
                'user_id' => $this->user_id, 
                'node_id' => $this->node_id, 
                'total_count' => $fans_count, 
                'add_time' => date('YmdHis'), 
                'msg_type' => $reply_type, 
                'msg_info' => $reply_text, 
                'send_mode' => $send_mode, 
                'is_to_all' => $is_to_all);
            if ($reply_type == 2) {
                $data['msg_info'] = $material_id;
            }
            if ($reply_type == 4) {
                $data['msg_info'] = $card_id;
            }
            
            if ($send_mode == '2') {
                $data['openid_list'] = implode(',', $fans_list);
                $data['send_on_time'] = $send_timing;
                $data['status'] = 0;
                foreach ($fans_list as $k => $v) {
                    $send_month_stat = M('twx_user')->where(
                        array(
                            'node_id' => $this->nodeId, 
                            'openid' => $v, 
                            'send_month' => date("m")))->getField(
                        'send_month_stat');
                    $send_month_stat ? $num = $send_month_stat + 1 : $num = 1;
                    $send_month_stat_data = array(
                        'send_month' => date('m'), 
                        'send_month_stat' => $num);
                    
                    $info = M('twx_user')->where(
                        array(
                            'openid' => $v, 
                            'node_id' => $this->nodeId))->save(
                        $send_month_stat_data);
                    if (! $info) {
                        log_write(
                            'group send update twx_user error:' . M()->_sql());
                    } else {
                        log_write(
                            'group send update twx_user success:' . M()->_sql());
                    }
                }
            }
            $batch_msg_id = M('twx_msgbatch')->add($data);
            if ($batch_msg_id === false) {
                M()->rollback();
                log_write("插入批量发送表错误！语句：" . M()->_sql());
                $this->error('批量发送初始化失败！');
            }
            // 保存记录，如果发送失败，则删除批次记录！
            M()->commit();
            
            if ($send_mode == '1') {
                $wx_send = D('WeiXinSend', 'Service');
                try {
                    $wx_send->init($this->node_id);
                    if (0 == $reply_type) {
                        $result = $wx_send->batch_send_text(
                            array(
                                'openids' => $fans_list, 
                                'content' => $reply_text, 
                                'is_to_all' => $is_to_all));
                    } elseif ($reply_type == 2) {
                        $result = $wx_send->batch_send(
                            array(
                                'material_id' => $material_id, 
                                'openids' => $fans_list, 
                                'is_to_all' => $is_to_all));
                    } elseif ($reply_type == 1) {
                        $result = $wx_send->batch_send_image(
                            array(
                                'content' => $reply_text, 
                                'openids' => $fans_list, 
                                'is_to_all' => $is_to_all));
                    } elseif ($reply_type == 4) {
                        $result = $wx_send->batch_send_card(
                            array(
                                'card_id' => $card_id, 
                                'openids' => $fans_list, 
                                'is_to_all' => $is_to_all));
                    }
                    log_write('群发');
                    if (! $result) {
                        throw_exception('群发失败!');
                    } else {
                        // 更新twx_user表记录发送粉丝条数
                        foreach ($fans_list as $k => $v) {
                            $send_month_stat = M('twx_user')->where(
                                array(
                                    'node_id' => $this->nodeId, 
                                    'openid' => $v, 
                                    'send_month' => date("m")))->getField(
                                'send_month_stat');
                            $send_month_stat ? $num = $send_month_stat + 1 : $num = 1;
                            $send_month_stat_data = array(
                                'send_month' => date('m'), 
                                'send_month_stat' => $num);
                            $info = M('twx_user')->where(
                                array(
                                    'openid' => $v, 
                                    'node_id' => $this->nodeId))->save(
                                $send_month_stat_data);
                            if (! $info) {
                                log_write(
                                    'group send update twx_user error:' .
                                         M()->_sql());
                            } else {
                                log_write(
                                    'group send update twx_user success:' .
                                         M()->_sql());
                            }
                        }
                    }
                } catch (Exception $e) {
                    M('twx_msgbatch')->where("batch_id = '{$batch_msg_id}'")->delete();
                    $this->error('回复失败：' . $e->getMessage());
                }
                
                foreach ($result as $wx_msg_id) {
                    $data = array(
                        'batch_id' => $batch_msg_id, 
                        'wx_batch_id' => $wx_msg_id);
                    $flag = M('twx_msgbatch_resp')->add($data);
                    
                    if ($flag === false) {
                        log_write('微信批量发送微信批次号入表失败！' . print_r($data, true));
                    }
                }
                node_log("【微信公众账号助手】批量发送录入成功！"); // 记录日志
                $this->success('发送成功！');
            } else {
                $this->success('已存入');
            }
        } else {
            $industry = M('tindustry_info')->select();
            $this->assign('count', $count);
            $this->assign('group_list', $group_list);
            $this->assign('industry', $industry);
            if ($this->node_id_wc == $this->node_id) {
                $this->display('batch_send2');
            } else {
                $this->display();
            }
        }
    }
    
    // 群发 定时发送
    public function batch_send_setTiming() {
        $msg_type = I('msg_type', '', 'trim');
        if ($msg_type !== '') {
            $where['msg_type'] = $msg_type;
        }
        $start_time = I('start_time', '', 'trim');
        $start_time = dateformat($start_time, 'YmdHis');
        if ($start_time < date('YmdHis'))
            $start_time = date('YmdHis');
        if ($start_time) {
            $where['send_on_time'] = array(
                'egt', 
                $start_time);
        }
        $end_time = I('end_time', '', 'trim');
        $end_time = dateformat($end_time, 'YmdHis');
        if (! empty($end_time)) {
            $where['send_on_time'] = array(
                'elt', 
                $end_time);
        }
        $where['send_mode'] = '2';
        $where['node_id'] = $this->node_id;
        $where['node_wx_id'] = $this->node_wx_id;
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M('twx_msgbatch')->where($where)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        $list = M('twx_msgbatch')->where($where)
            ->order('add_time desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $msg_type = array(
            '文本', 
            '图片', 
            '图文', 
            '语音');
        if($list){
            foreach ($list as &$val) {
                $val['html'] = $this->_chat_msg_show($val['msg_type'], 
                    $val['msg_info']);
            }
        }
        
        $this->assign('list', $list);
        $this->assign('msg_type', $msg_type);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function batch_send_delete() {
        $batch_id = I('id');
        $openid = M('twx_msgbatch')->where(
            array(
                'batch_id' => $batch_id))->getField('openid_list');
        $openid_list = explode(',', $openid);
        foreach ($openid_list as $val) {
            $status = M('twx_user')->where(
                array(
                    'openid' => $val, 
                    'node_id' => $this->nodeId))->setDec('send_month_stat');
        }
        $status = M('twx_msgbatch')->where(
            array(
                'batch_id' => $batch_id))->delete();
        $this->ajaxReturn($status);
    }

    public function duokefu() {
        $flag = D('tweixin_info')->where(
            "node_id = '{$this->node_id}' and status = '0' ")
            ->field('service_flag')
            ->find();
        ;
        
        if ($this->isPost()) {
            $flag = I('post.flag', 2);
            $state = D('tweixin_info')->where(
                "node_id = '{$this->node_id}' and status = '0' ")->setField(
                'service_flag', $flag);
            if ($state) {
                if (1 == $flag) {
                    $this->ajaxReturn($flag, '开启成功', 1);
                } elseif (0 == $flag) {
                    $this->ajaxReturn($flag, '关闭成功', 1);
                }
            } else {
                $this->ajaxReturn('', '操作失败', 2);
            }
        }
        $this->assign('flag', $flag);
        $this->display();
    }

    public function batch_send_his() {
        $dao = M('twx_msgbatch');
        $where = array(
            'node_id' => $this->node_id, 
            'node_wx_id' => $this->node_wx_id, 
            'status' => 1);
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
        if(!$list) $list = array();
        foreach ($list as &$val) {
            $val['html'] = $this->_chat_msg_show($val['msg_type'], 
                $val['msg_info']);
        }
        
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
        $group_id = I('group_id', '-', 'trim');
        if ($group_id != '-')
            $where['group_id'] = $group_id;
        
        $filter_name = I("n", '', 'trim');
        $province = I("pr", 0, 'trim,intval');
        $city = I("c", 0, 'trim,intval');
        $sex = I("x", 0, 'trim,intval');
        $filter_date_start = I("d1", 0, 'trim');
        $filter_date_last = I("d2", 0, 'trim');
        $keywords = I("k", '', 'trim');
        $clientName = I("cN", '', 'trim');
        $clientGroup = I("cG", '', 'trim');
        $clientType = I("cT", '', 'trim');
        $fsfrom = I("fs", '', 'trim');
        $openid = I("op", '', 'trim');
        $down = I("do", 0);
        
        if ('' != $openid) {
            $where['w.openid'] = array(
                'like', 
                '%' . $openid . '%');
        }
        
        if ('' != $fsfrom) {
            $fsfromId = M()->table("twx_qrchannel wq")->join(
                'tchannel c on c.id=wq.channel_id')
                ->where(
                array(
                    'c.name' => array(
                        'like', 
                        '%' . $fsfrom . '%')))
                ->field('wq.scene_id')
                ->select();
            
            $scenc_id = array();
            foreach ($fsfromId as &$v) {
                $scenc_id[] = $v['scene_id'];
            }
            $where['w.scene_id'] = array(
                'in', 
                $scenc_id);
        }
        if ('' != $filter_name)
            $where['w.nickname'] = array(
                'like', 
                '%' . $filter_name . '%');
        if ('' != $province) {
            $province = M('tcity_code')->where(
                "city_level=1 and province_code=$province")->getfield('province');
            $where['w.province'] = $province;
        }
        
        if ('' != $city) {
            $city = M('tcity_code')->where("city_level=2 and city_code=$city")->getfield(
                'city');
            $city = str_replace('市', '', $city);
            $where['w.city'] = $city;
        }
        
        if (0 != $sex)
            $where['w.sex'] = $sex;
        if (0 != $filter_date_start && 0 != $filter_date_last)
            $where['w.subscribe_time'] = array(
                'between', 
                strtotime($filter_date_start . '00:00:00') . "," .
                     strtotime($filter_date_last . '23:59:59'));
        if ('' != $keywords)
            $where['w.remarkname'] = array(
                'like', 
                '%' . $keywords . '%');
        
        if ($this->node_id_wc == $this->node_id) {
            $industry = M('tindustry_info')->select();
            $wh = 'where 1=1 ';
            
            if ('' != $filter_name)
                $wh .= " and z.nickname like '%" . $filter_name . "%'";
            if ('' != $province) {
                $province = M('tcity_code')->where(
                    "city_level=1 and province='$province'")->getfield(
                    'province');
                $wh .= " and z.province  ='" . $province . "'";
            }
            if (0 != $sex)
                $wh .= " and z.sex ='" . $sex . "'";
            if (0 != $filter_date_start && 0 != $filter_date_last)
                $wh .= " and z.subscribe_time between strtotime " .
                     $filter_date_start . "'00:00:00'" . " AND " .
                     strtotime($filter_date_last . '23:59:59');
            if ('' != $keywords)
                $wh .= "z.remarkname like '%" . $keywords . "%'";
            
            if ('' != $clientName) {
                $wh .= " and  z.petname = '$clientName' ";
            }
            if ('' != $clientGroup) {
                $wh .= " and z.industry_name = '$clientGroup'";
                // M('tindustry_info')->where(array('industry_code'=>$clientGroup))->find();
            }
            if ('' != $clientType) {
                // 旺财版本 v0 => 旺财免费版, v0.5 => 旺财认证版, v1 => 旺财标准版, v2 => 旺财电商版, v3
                // => 旺财全民营销版, v4 => 旺财演示版, v5 => 旺财微博版, v6 => 旺财凭证活动版, v7 =>
                // 旺财凭证版
                $wh .= " and z.wc_version = '$clientType'";
            }
            
            $versionArr = array(
                'v0' => '旺财免费版', 
                'v0.5' => '旺财认证版', 
                'v1' => '旺财标准版', 
                'v2' => '旺财电商版', 
                'v3' => '旺财全民营销版', 
                'v4' => '旺财演示版', 
                'v5' => '旺财微博版', 
                'v6' => '旺财凭证活动版', 
                'v7' => '旺财凭证版');
            import('ORG.Util.Page'); // 导入分页类
            $sql = "SELECT * FROM (" .
                 " SELECT '','' AS wc_version, a.openid,''AS industry_name FROM (SELECT * FROM twx_user c WHERE  NOT EXISTS(SELECT * FROM twx_user_wc_mobile d WHERE c.openid=d.open_id) AND c.node_id='" .
                 $this->node_id . "' AND c.node_wx_id='" . $this->node_wx_id .
                 "' AND c.subscribe=1) a LEFT JOIN twx_user_wc_mobile w ON w.`node_id`=a.node_id LEFT JOIN tnode_info b ON a.node_id=b.node_id" .
                 " UNION ALL " .
                 " SELECT  o.node_name AS petname,o.wc_version, u.openid,y.industry_name nname FROM twx_user_wc_mobile m LEFT JOIN twx_user u ON u.openid=m.open_id LEFT JOIN tnode_info o ON m.node_id=o.node_id LEFT JOIN tindustry_info Y ON y.industry_code=o.trade_type WHERE u.node_id='" .
                 $this->node_id . "' AND u.node_wx_id='" . $this->node_wx_id .
                 "' AND u.subscribe=1 " . ") AS z $wh GROUP BY z.openid";
            // echo $sql;
            $result = M()->query($sql);
            $count = count($result);
            
            $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
            $pageShow = $Page->show(); // 分页显示输出
            
            $sqlList = "SELECT * FROM (" .
                 " SELECT '','' AS wc_version, a.*,'' AS industry_name FROM (SELECT * FROM twx_user c WHERE  NOT EXISTS(SELECT * FROM twx_user_wc_mobile d WHERE c.openid=d.open_id) AND c.node_id='" .
                 $this->node_id . "' AND c.node_wx_id='" . $this->node_wx_id .
                 "' AND c.subscribe=1 ) a LEFT JOIN twx_user_wc_mobile w ON w.`node_id`=a.node_id LEFT JOIN tnode_info b ON a.node_id=b.node_id" .
                 " UNION ALL " .
                 " SELECT  o.node_name AS petname,o.wc_version,u.*,y.industry_name AS industry_name FROM twx_user_wc_mobile m LEFT JOIN twx_user u ON u.openid=m.open_id LEFT JOIN tnode_info o ON m.node_id=o.node_id LEFT JOIN tindustry_info Y ON y.industry_code=o.trade_type WHERE u.node_id='" .
                 $this->node_id . "' AND u.node_wx_id='" . $this->node_wx_id .
                 "' AND u.subscribe=1 " . " ) AS z $wh GROUP BY z.openid" .
                 " limit " . $Page->firstRow . " , " . $Page->listRows;
            // echo $sqlList;
            $list = M()->query($sqlList);
        } else {
            $where['w.node_id'] = $this->node_id;
            $where['w.node_wx_id'] = $this->node_wx_id;
            $where['w.subscribe'] = 1;
            
            import('ORG.Util.Page'); // 导入分页类
            $count = M()->table("twx_user w")->where($where)->count(); // 查询满足要求的总记录数
            $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
            $pageShow = $Page->show(); // 分页显示输出
            
            if ($down) {
                $list = M()->table("twx_user w")->join(
                    "twx_qrchannel wq on wq.scene_id=w.scene_id AND (wq.node_id = '$this->node_id') ")
                    ->join("tchannel c ON wq.channel_id=c.id")
                    ->where($where)
                    ->order('w.id DESC')
                    ->field('w.*, c.name')
                    ->select();
                
                $fileName = '粉丝管理表.csv';
                header("Content-type: text/plain");
                header("Accept-Ranges: bytes");
                header("Content-Disposition: attachment; filename=" . $fileName);
                header(
                    "Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Pragma: no-cache");
                header("Expires: 0");
                $title_ = "粉丝昵称, 粉丝标签, 性别, 国家, 省市, 地区, 客户端语言, 头像URL地址, 关注openid,关注来源, 关注时间\r\n";
                $title_ = iconv('utf-8', 'gbk', $title_);
                echo $title_;
                
                if ($list) {
                    foreach ($list as $value) {
                        echo iconv('utf-8', 'gbk', $value['nickname']) . ", ";
                        echo iconv('utf-8', 'gbk', $value['remarkname']) . ", ";
                        echo iconv('utf-8', 'gbk', 
                            $value['sex'] == 1 ? '男' : '女') . ", ";
                        echo iconv('utf-8', 'gbk', $value['country']) . ", ";
                        echo iconv('utf-8', 'gbk', $value['province']) . ", ";
                        echo iconv('utf-8', 'gbk', $value['city']) . ", ";
                        echo iconv('utf-8', 'gbk', $value['language']) . ", ";
                        echo iconv('utf-8', 'gbk', $value['headimgurl']) . ", ";
                        echo iconv('utf-8', 'gbk', $value['openid']) . ", ";
                        echo iconv('utf-8', 'gbk', $value['name']) . ", ";
                        echo iconv('utf-8', 'gbk', 
                            date("Y-m-d H:i:s ", $value['subscribe_time'])) .
                             "\r\n";
                    }
                }
                exit();
            }
            
            $list = M()->table("twx_user w")->where($where)
                ->order('w.id DESC')
                ->limit($Page->firstRow . ',' . $Page->listRows)
                ->select();
        }
        // 查询分组
        $where = array(
            'w.node_id' => array(
                'in', 
                array(
                    $this->node_id), 
                ''), 
            'w.node_wx_id' => array(
                'in', 
                array(
                    $this->node_wx_id), 
                ''), 
            'w.subscribe' => 1);
        
        $_where = "(node_id = '' or node_id = '{$this->node_id}') and (node_wx_id = '' or node_wx_id = '{$this->node_wx_id}')";
        $group_list = M('twx_user_group')->where($_where)
            ->order('id asc')
            ->select();
        $group_arr = M('twx_user_group')->where($_where)->getField('id, name', 
            true);
        
        // 查询分组用户数
        $group_num_arr = M()->table("twx_user w")->where($where)
            ->group('group_id')
            ->getField('group_id, count(1) cnt', true);
        $this->assign('group_arr', $group_arr);
        $this->assign('group_num_arr', $group_num_arr);
        $this->assign('fans_cnt', array_sum($group_num_arr));
        $this->assign('group_list', $group_list);
        $this->assign('user_list', $list);
        $this->assign('page', $pageShow);
        if ($this->node_id_wc == $this->node_id) {
            $this->display('fansmng2');
        } else {
            $this->display();
        }
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
        $weixinFansGroup = D('WeiXinFansGroup', 'Service');
        $weixinFansGroup->init($this->nodeId);
        $wx_group_id = $weixinFansGroup->apiCreateGroup($group_name);
        if ($wx_group_id) {
            $data['wx_group_id'] = $wx_group_id;
            $flag = $model->add($data);
        }
        if (! $flag) {
            log_write("组添加失败！语句：" . M()->_sql());
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
            $weixinFansGroup = D('WeiXinFansGroup', 'Service');
            $weixinFansGroup->init($this->nodeId);
            $result = $weixinFansGroup->apiCreateGroup($info['wx_group_id'], 
                $group_name);
            if ($result) {
                $data = array(
                    'name' => $group_name);
                $flag = $model->where($where)->save($data);
            }
            if (! $flag) {
                log_write("组编辑失败！语句：" . M()->_sql());
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
        
        $weixinFansGroup = D('WeiXinFansGroup', 'Service');
        $weixinFansGroup->init($this->nodeId);
        $result = $weixinFansGroup->apiDeleteGroup($info['wx_group_id']);
        
        if ($flag === false || ! $result) {
            $model->rollback();
            log_write("分组删除失败！语句：" . M()->_sql());
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
            log_write("移除该分组的用户失败！语句：" . M()->_sql());
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
        
        $weixinFansGroup = D('WeiXinFansGroup', 'Service');
        $weixinFansGroup->init($this->nodeId);
        $result = $weixinFansGroup->apiMoveGroupByOpenId($info['wx_group_id'], 
            $fans_id);
        if ($result) {
            $flag = $model->where($where1)->save(
                array(
                    'group_id' => $group_id));
        }
        
        if (! $flag) {
            log_write("用户组变更失败！语句：" . M()->_sql());
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
        
        $weixinFansGroup = D('WeiXinFansGroup', 'Service');
        $weixinFansGroup->init($this->nodeId);
        $result = $weixinFansGroup->apiBatchMoveGroupByOpenIdArray(
            $info['wx_group_id'], $id_arr);
        if ($result) {
            $flag = $model->where($where1)->save(
                array(
                    'group_id' => $group_id));
        }
        if (! $flag) {
            log_write("用户组变更失败！语句：" . M()->_sql());
            $this->error('用户组变更失败！');
        }
        
        $this->success('用户组变更成功！');
    }

    public function wxuserinfo() {
        $id = I('id', '', 'trim');
        if ($id == '')
            $this->error('参数错误！');
        
        $wh = array(
            'node_id' => $this->node_id, 
            'openid' => $id);
        $user_info = M('twx_user')->where($wh)->find();
        
        if ($user_info['scene_id']) {
            $name = M()->table("twx_qrchannel wq")->join(
                "tchannel c ON wq.channel_id=c.id")
                ->where(
                array(
                    'wq.scene_id' => $user_info['scene_id'], 
                    'wq.node_id' => $this->node_id))
                ->getField('c.name');
            
            $user_info['scene_id'] = $name;
        } else {
            $user_info['scene_id'] = '';
        }
        
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

    /**
     * 绑定成功设置“绑定成功”关键字回复
     */
    public function bindSuccess() {
        
        // 客户绑定成功，新增一个"绑定成功"关键词回复
        $ruleName = '测试绑定成功规则';
        $keywordStr = '绑定测试';
        $wordContent = '绑定成功';
        $nowtime = date('YmdHis');
        $msgDao = M('twx_message');
        
        $msgDao->startTrans();
        // 是否已添加
        $count = $msgDao->where(
            array(
                'node_id' => $this->nodeId, 
                'message_name' => $ruleName))->count();
        if (! $count) {
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
            
            $data = array(
                'node_id' => $this->nodeId, 
                'message_id' => $message_id, 
                'key_words' => $keywordStr, 
                'match_type' => 1, 
                'add_time' => $nowtime, 
                'update_time' => $nowtime);
            
            $result = M('twx_msgkeywords')->add($data);
            
            if ($result === false) {
                $msgDao->rollback();
                $this->error("保存失败02");
            }
            
            $data = array(
                'node_id' => $this->nodeId, 
                'message_id' => $message_id, 
                'response_info' => html_entity_decode($wordContent), 
                'response_class' => 0, 
                'add_time' => $nowtime, 
                'update_time' => $nowtime);
            $result = M('twx_msgresponse')->add($data);
            if ($result === false) {
                $msgDao->rollback();
                $this->error("保存失败03");
            }
            
            $result = $msgDao->commit();
            if (! $result) {
                $this->error("保存失败");
            } else {
                node_log("【微信公众账号助手】设置绑定成功关键词回复"); // 记录日志
                $this->success("保存成功");
            }
        }
    }
    
    // 添加场景码渠道
    public function addChannel() {
        $data_arr1 = array(); // 渠道表
        $name = I('channel_name', '');
        
        $data_arr1['name'] = $name;
        $data_arr1['node_id'] = $this->node_id;
        $data_arr1['add_time'] = date('YmdHis');
        $data_arr1['status'] = '1';
        $data_arr1['type'] = '8';
        $data_arr1['sns_type'] = '81';
        
        if ($name) {
            $tranDb = new Model();
            $tranDb->startTrans();
            $channel_id = M('tchannel')->add($data_arr1);
            
            if (! $channel_id) {
                $tranDb->rollback();
                $this->error('渠道创建失败');
                exit();
            }
            if ($channel_id) {
                $result = $this->_sceneCode($channel_id);
                
                if ($result['error'] != '0') {
                    $tranDb->rollback(); // 事务回滚
                    $this->error($result['msg']);
                    exit();
                }
            }
            $tranDb->commit();
            echo "<script>alert('添加成功');parent.location.reload();parent.art.dialog.list['uduf'].close();</script>";
        }
    }
    
    // 生成场景码二维码
    function _sceneCode($channel, $flag_insert = 0) {
        $node_id = $this->nodeId;
        $result = array();
        // 查询公众账号配置
        $weixinInfo = M('tweixin_info')->where(
            array(
                'node_id' => $node_id))->find();
        
        // 1.校验公众账号
        if (! $weixinInfo || ! $weixinInfo['app_id'] ||
             ! $weixinInfo['app_secret']) {
            $this->error("请先配置微信公众账号。", 
                array(
                    '确定' => U('Weixin/Weixin/index')));
            $result['error'] = '1001';
            $result['url'] = U('Weixin/Weixin/index');
            $result['msg'] = '请先配置微信公众账号!';
            return $result;
            exit();
        }
        
        // 去微信获取token
        $wxService = D('WeiXinQrcode', 'Service');
        $wxService->init($weixinInfo['app_id'], $weixinInfo['app_secret'], 
            $weixinInfo['app_access_token']);
        
        // 查看是否绑定过渠道
        $wcInfo = M('twx_qrchannel')->where(
            array(
                'node_id' => $node_id, 
                'channel_id' => $channel))
            ->field("scene_id,id")
            ->find();
        if (! $wcInfo || ! $wcInfo['scene_id']) {
            $scene_id = D('TweixinInfo')->getSceneId($node_id);
        } else {
            $scene_id = $wcInfo['scene_id'];
        }
        if (! $scene_id) {
            $result['error'] = '2001';
            $result['msg'] = '生成 scene_id 失败!';
            return $result;
            exit();
        }
        // 去微信接口获取图片内容
        $qrResult = $wxService->getQrcodeImg(
            array(
                'scene_id' => $scene_id));
        // 更新accessToken
        if ($weixinInfo['app_access_token'] != $wxService->accessToken) {
            $query = M('tweixin_info')->where(
                array(
                    'node_id' => $node_id))->save(
                array(
                    'app_access_token' => $wxService->accessToken));
        }
        
        // 如果失败
        if ($qrResult['status'] != '1') {
            $result['error'] = '3001';
            $result['msg'] = '获取推广二维码失败，原因：' . $qrResult['errcode'] . ':' .
                 $qrResult['errmsg'];
            return $result;
            exit();
        }
        
        $data[] = $qrResult['ticket_url'];
        // 更新
        if ($wcInfo) {
            $data = array(
                'img_info' => $qrResult['img_url'], 
                'scene_id' => $scene_id);
            $query = M('twx_qrchannel')->where(
                array(
                    'id' => $wcInfo['id']))->save($data);
        } else {
            $data = array(
                'scene_id' => $scene_id, 
                'img_info' => $qrResult['img_url'], 
                'channel_id' => $channel, 
                'add_time' => date('YmdHis'), 
                'node_id' => $node_id);
            if ($flag_insert) {
                $data['flag_insert'] = '1';
            }
            $query = M('twx_qrchannel')->add($data);
        }
        if ($query === false) {
            log_write('twx_qrchannel:' . print_r($data, true));
            $result['error'] = '4001';
            $result['msg'] = '发布失败,渠道号:' . $channel;
            return $result;
        } else {
            $result['error'] = '0';
            return $result;
        }
    }
    
    // 场景码
    public function changjingma() {
        $name = I('keywords', '', 'trim');
        $flag = I('flag', '');
        $down = I('down', '0');
        $where = array(
            'c.node_id' => $this->node_id, 
            'c.sns_type' => '81');
        if ($name) {
            $where['c.name'] = array(
                'like', 
                "%" . $name . "%");
        }
        if ('' != $flag) {
            $where['wq.flag_insert'] = $flag;
        }
        $lnsy_field=$lnsy_down_field="";
        //辽宁非标
        if($this->node_id == C('lnsy.node_id')){
            $this->assign('lnsy_tag',true);
            $lnsy_down_field=",手机号,状态";
            $phone=I('phone');
            if($phone){
                $this->assign('phone',$phone);
                $lnsy_where=array(
                    'myqr.node_id'=>$this->node_id,
                    );
                $lnsy_where['s.phone']=array('like',"%".(string)$phone."%");
                $build_sql=M('tfb_myqrcode_trace')->alias('s')
                                        ->join('tfb_myqrcode myqr on myqr.id= s.mq_id')
                                        ->field('s.qr_id')
                                        ->where($lnsy_where)
                                        ->buildSql();
                $where['_string']="wq.id in".$build_sql;
            }
            $lnsy_field="(select lnsy.phone from tfb_myqrcode_trace lnsy where lnsy.qr_id = wq.id and lnsy.phone <> '') phone ,";
            $lnsy_field.="(select IF(ly.apply_time > ".strtotime("- 720 hours").",'1','2') from tfb_myqrcode_trace ly where ly.qr_id = wq.id and ly.phone <> '' ) as qr_status,";
        }
        
        import('ORG.Util.Page'); // 导入分页类
        
        $count = M()->table("twx_qrchannel wq")->join('tchannel c ON wq.channel_id=c.id')
            ->field(
            $lnsy_field."wq.id,wq.flag_insert,c.name,DATE_FORMAT(c.add_time,'%Y-%m-%d') as add_time,wq.click_count,wq.subscribe_count,wq.img_info")
            ->where($where)
            ->count();
        $Page = new Page($count, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        $pageShow = $Page->show(); // 分页显示输出
        
        if ($down) {
            $list = M()->table("twx_qrchannel wq")->join(
                'tchannel c ON wq.channel_id=c.id')
                ->field(
                $lnsy_field."wq.id,c.name,wq.`flag_insert`,DATE_FORMAT(c.add_time,'%Y-%m-%d %H:%m:%s') as add_time,wq.click_count,wq.subscribe_count,wq.img_info")
                ->where($where)
                ->order('c.add_time Desc')
//                ->limit($Page->firstRow, $Page->listRows)
                ->select();
            
            $fileName = '场景码统计表.csv';
            header("Content-type: text/plain");
            header("Accept-Ranges: bytes");
            header("Content-Disposition: attachment; filename=" . $fileName);
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            $title_ = "场景码名称, 创建时间, 创建方式, 扫码累计数, 累计关注数".$lnsy_down_field."\r\n";
            $title_ = iconv('utf-8', 'gbk', $title_);
            echo $title_;
            
            if ($list) {
                foreach ($list as $value) {
                    echo iconv('utf-8', 'gbk', $value['name']) . ", ";
                    echo iconv('utf-8', 'gbk', $value['add_time']) . ", ";
                    echo iconv('utf-8', 'gbk', 
                        $value['flag_insert'] ? "批量添加" : "单个添加") . ", ";
                    echo iconv('utf-8', 'gbk', $value['click_count']) . ", ";
                    echo iconv('utf-8', 'gbk', $value['subscribe_count']) ;
                    if($lnsy_down_field){
                        echo ",".iconv('utf-8', 'gbk', ($value['phone']?$value['phone']:"-")) . ", ";
                        echo iconv('utf-8', 'gbk', ($value['qr_status']==2?"过期":"正常")) ;
                    }
                    echo  "\r\n";
                }
            }
            exit();
        }
        
        $list = M()->table("twx_qrchannel wq")->join('tchannel c ON wq.channel_id=c.id')
            ->field(
            $lnsy_field."wq.id,c.name,wq.remarkname,wq.`flag_insert`,DATE_FORMAT(c.add_time,'%Y-%m-%d %H:%i:%s') as add_time,wq.click_count,wq.subscribe_count,wq.img_info")
            ->where($where)
            ->order('c.add_time Desc')
            ->limit($Page->firstRow, $Page->listRows)
            ->select();
        
        $this->assign('show', $pageShow);
        $this->assign('list', $list);
        
        $this->display();
    }

    public function changjingmaInfoAjax() {
        $id = I('id', '');
        
        /*
         * import('ORG.Util.Page');// 导入分页类 $count = M('twx_qrchannel wq')
         * ->join('twx_qrchannel_ext wqe on wqe.channel_id=wq.channel_id')
         * ->where(array('wq.id' => $id))
         * ->group('date_format(wqe.add_time,"%Y-%m-%d")') ->count(); $Page =
         * new Page($count,10);// 实例化分页类 传入总记录数和每页显示的记录数 $pageShow =
         * $Page->show();// 分页显示输出
         */
        
        $info = M()->table("twx_qrchannel wq")->join(
            'twx_qrchannel_ext wqe on wqe.channel_id=wq.channel_id')
            ->where(array(
            'wq.id' => $id))
            ->field(
            'sum(CASE wqe.type WHEN 0 THEN 1 ELSE 0 END) as num1, sum(CASE wqe.type WHEN 1 THEN 1 ELSE 0 END) as num2 , date_format(wqe.add_time,"%Y-%m-%d") as time')
            ->
        // ->field('count(id) as num, date_format(`add_time`,"%Y-%m-%d") as
        // time')
        // ->limit($Page->firstRow.','.$Page->listRows)
        group('date_format(wqe.add_time,"%Y-%m-%d")')
            ->order("wqe.add_time Desc")
            ->select();
        // $info['page'] = $pageShow;
        $this->ajaxReturn($info, 'json');
    }
    
    // 场景码下载/展示
    public function changjingmaImg() {
        $id = I('id', 0);
        $isShow = I('down', 0);
        $imgUrl = M('twx_qrchannel')->where(
            array(
                'id' => $id))->getField('img_info');
        import('@.Vendor.MakeCode') or die('include file fail.');
        $makecode = new MakeCode();
        if ($isShow == '1') {
            $makecode->MakeCodeImg($imgUrl, true);
        } else {
            $makecode->MakeCodeImg($imgUrl);
        }
    }
    
    // 引导关注页
    public function focus() {
        $guide_info = D('TweixinInfo')->getGuidUrl($this->node_id);
        if ($this->isPost()) {
            $guide_url = I('guide_url', '');
            if ($guide_url == $guide_info) {
                $this->error("与原链接一致,请修改后再保存");
            } else {
                $result = M('tweixin_info')->where(
                    array(
                        'node_id' => $this->node_id))
                    ->data(
                    array(
                        'guide_url' => $guide_url))
                    ->save();
                if ($result) {
                    $this->ajaxReturn(1, '保存成功', 1);
                }
            }
        }
        $this->assign('guide_url', $guide_info);
        $this->display();
    }

    /**
     *
     * @return StoresModel
     */
    private function getStoresModel() {
        if (empty($this->storesModel)) {
            $this->storesModel = D('Stores');
        }
        return $this->storesModel;
    }

    /**
     *
     * @return StoresGroupModel
     */
    private function getStoresGroupModel() {
        if (empty($this->storesGroupModel)) {
            $this->storesGroupModel = D('StoresGroup');
        }
        return $this->storesGroupModel;
    }
    /**
     * 获取子商户的所有nodeId.
     *
     * @param string $str 多数是个SQL语句
     *
     * @return mixed
     */
    public function getNodeIn($str)
    {
        if (stripos($str, 'from')) {
            $result = M()->query($str);
            $nodeArr = array();
            foreach ($result as $key => $value) {
                $nodeArr[] = $value['node_id'];
            }
            $str = implode(',', $nodeArr);
        }

        return $str;
    }
    
    // 添加场景码
    public function changjingmaAdd() {
        
        /* 门店 */
        $batch_arr = $this->addbatch();
        
        $nodeIn = $this->nodeIn();
        $nodeIn = $this->getNodeIn($nodeIn);

        $storesModel = $this->getStoresModel();
        
        if (IS_POST) {
            $areaType = I('post.city_type');
            $query_arr = (array) $storesModel->areaFilter($nodeIn, $areaType);
            $this->ajaxReturn($query_arr, "查询成功", 0);
            exit();
        }
        // 门店
        $queryList = $storesModel->getAllStore($nodeIn);
        
        // 分组
        $storeGroup = $this->getStoreGroup(true);
        $this->assign('storeGroup', $storeGroup);
        
        // 判断是否为门店提交显示页面
        if ($this->isPost()) {
            $this->assign('is_search', 'Y');
        }
        
        /* 员工 */
        $sql = "SELECT id,name FROM `tchannel` WHERE ( (`node_id` in (select node_id from tnode_info where full_id like '" .
             $this->node_id .
             "%')) ) AND ( `type` = '5' ) AND ( `sns_type` = 51 ) AND (`status`=1)";
        $staffList = M()->query($sql);
        $this->assign('allStores', $queryList);
        $this->assign('batch_arr', $batch_arr);
        $this->assign('staffList', $staffList);
        $this->display();
    }

    /**
     * 获取分组
     *
     * @param $where mixed 获取分组的额外条件
     * @return mixed
     */
    public function getStoreGroup($where) {
        $nodeId = $this->node_id;
        $storesModel = $this->getStoresModel();
        $storesGroupModel = $this->getStoresGroupModel();
        
        // 获取所有分组
        $allGroup = $storesGroupModel->getPopGroupStoreId($nodeId, $where);
        
        // 未分组的门店
        $noGroup = $storesModel->getUnGroupedAllStore($nodeId, $where);
        $noGroupArr = array();
        foreach ($noGroup as $key => $value) {
            $noGroupArr[] = $value['store_id'];
        }
        // 追加未分组项
        array_unshift($allGroup, 
            array(
                'id' => '-1', 
                'group_name' => '未被分组', 
                'num' => count($noGroupArr), 
                'storeId' => implode(',', $noGroupArr)));
        
        return array_filter($allGroup);
    }
    
    // 门店创建二维码场景渠道
    public function changjingmaStore() {
        $store_id = I('store_id', '');
        $storeName = M('tstore_info')->where(array('store_id'=>array('in',explode(',', $store_id))))->getField('store_id,store_short_name');
        if ($store_id) {
            $storeId = explode(',', $store_id);
            // 批量创建
            foreach ($storeId as $k => $v) {
                $data_arr1 = array(); // 渠道表
                $data_arr1['name'] = $storeName[$v];
                $data_arr1['node_id'] = $this->node_id;
                $data_arr1['add_time'] = date('YmdHis');
                $data_arr1['status'] = '1';
                $data_arr1['type'] = '8';
                $data_arr1['sns_type'] = '81';
                $data_arr1['store_id'] = $v;
                
                if ($storeName[$v]) {
                    $tranDb = new Model();
                    $tranDb->startTrans();
                    $channel_id = M('tchannel')->add($data_arr1);
                    if (! $channel_id) {
                        $tranDb->rollback();
                        $this->error('渠道创建失败');
                        exit();
                    }
                    if ($channel_id) {
                        $result = $this->_sceneCode($channel_id, 1);
                        
                        if ($result['error'] != '0') {
                            $tranDb->rollback(); // 事务回滚
                            $this->error($result['msg']);
                            exit();
                        }
                    }
                    $tranDb->commit();
                }
            }
            $this->ajaxReturn(1, "添加成功", 1);
        }
    }
    
    // 员工创建二维码场景
    public function changjingmaStaff() {
        $channel_id = I('channel_id', '');
        $channel_name = I('channel_name', '');
        
        if ($channel_id) {
            $channel_id = explode(',', $channel_id);
            $channel_name = explode(',', $channel_name);
            
            // 批量创建
            foreach ($channel_id as $k => $v) {
                $data_arr1 = array(); // 渠道表
                $name = $channel_name[$k];
                
                $data_arr1['name'] = $name;
                $data_arr1['node_id'] = $this->node_id;
                $data_arr1['add_time'] = date('YmdHis');
                $data_arr1['status'] = '1';
                $data_arr1['type'] = '8';
                $data_arr1['sns_type'] = '81';
                
                $tranDb = new Model();
                $tranDb->startTrans();
                $channel_id_new = M('tchannel')->add($data_arr1);
                
                if (! $channel_id_new) {
                    $tranDb->rollback();
                    $this->error('渠道创建失败');
                    exit();
                }
                
                $result = M('tstaff_channel')->where(
                    array(
                        'channel_id' => $v))->save(
                    array(
                        'staff_id' => $channel_id_new));
                
                if (! $result) {
                    $tranDb->rollback();
                    $this->error("员工渠道更新场景码失败");
                    exit();
                }
                
                if ($channel_id_new) {
                    $result = $this->_sceneCode($channel_id_new, 1);
                    if ($result['error'] != '0') {
                        $this->error($result['msg']);
                        exit();
                    }
                }
                $tranDb->commit();
            }
            $this->ajaxReturn(1, "添加成功", 1);
        }
    }

    private function addbatch() {
        $batch_model = M('tmarketing_info');
        $onemap = array(
            'node_id' => $this->node_id, 
            'batch_type' => self::BATCH_TYPE_SHOPGPS);
        $batch_data = $batch_model->where($onemap)->find();
        if (! $batch_data) {
            // 营销活动不存在则新增
            $batch_arr = array(
                'batch_type' => self::BATCH_TYPE_SHOPGPS, 
                'name' => '门店导航默认营销活动', 
                'status' => '1', 
                'start_time' => date('YmdHis'), 
                'end_time' => date('YmdHis', strtotime("+1 year")), 
                'add_time' => date('YmdHis'), 
                'click_count' => 0, 
                'cj_count' => 0, 
                'send_count' => 0, 
                'node_id' => $this->node_id);
            
            $query = $batch_model->add($batch_arr);
            if (! $query) {
                $this->error('添加门店导航默认营销活动失败');
            }
            return array(
                'batch_id' => $query, 
                'batch_name' => '门店导航默认营销活动', 
                'click_count' => 0);
        } else {
            return array(
                'batch_id' => $batch_data['id'], 
                'batch_name' => $batch_data['name'], 
                'click_count' => $batch_data['click_count'], 
                'wap_title' => $batch_data['wap_title']);
        }
    }
    
    // 选择图文
    public function changjingma_response() {
        $dao = M('twx_material');
        $where = "node_id='" . $this->nodeId . "'";
        $where .= " and material_type in ('1','2') and material_level = '1' and type = 0";
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
            $v['img_url'] = $this->_getImgUrl($v['material_img']);
            // 处理多图文,如果是子节点
            
            if ($v['material_type'] == '2') {
                $sub_meterial = $dao->where(
                    array(
                        'parent_id' => $v['id']))->select();
                if($sub_meterial){
                    foreach ($sub_meterial as &$vv) {
                        $vv['img_url'] = $this->_getImgUrl($vv['material_img']);
                    }
                    unset($vv);
                }
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
        
        // 判断是否含有图文
        $queryData ? $flagImageNews = true : $flagImageNews = false;

        // 响应时间保存后取值展示
        $info = M()->table("twx_message wm")
            ->join('twx_msgresponse wms on wm.id=wms.message_id')
            ->join('twx_qrchannel wq on wq.scene_id=wm.scene_id')
            ->where([
                'wq.id'     => $_GET['id'],
                'wm.node_id'=> $this->nodeId
            ])
            ->find();

        $this->assign('info', $info);
        $this->assign('flagImageNews', $flagImageNews);
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
    
    // 场景码响应
    public function changjingmaResponse() {
        $where = "node_id='" . $this->nodeId . "'and response_type=0";
        $dao = M('twx_message');
        $messageInfo = $dao->field("id")
            ->where($where)
            ->find();
        
        $message_id = $messageInfo['id'];
        $respInfo = array();
        
        if ($message_id) {
            $respInfo = M("twx_msgresponse")->where(
                "message_id='" . $message_id . "'")->find();
        }
        
        $respText = $respInfo['response_info'];
        
        $this->assign('imgId', '');
        if ('1' == $respInfo['response_class']) {
            $this->assign('imgId', $respInfo['response_info']);
        }
        
        $this->assign('respText', $respText);
        $this->assign('response_class', $respInfo['response_class']);
        
        $this->display();
    }
    
    // 场景码响应事件提交
    public function followSubmit() {
        $id = $_GET['id'];
        $scene_id = M('twx_qrchannel')->where(
            array(
                'id' => $id))->getField('scene_id');
        
        if ($scene_id) {
            $response_info = I('post.response_info'); // 消息内容
            $response_info = htmlspecialchars_decode($response_info);
            $response_type = I('post.respType'); // 消息方式 被动0 自动1 关键字2
            $response_class = I('post.respClass'); // 消息类型 文本0 素材1 其他2 图片3
            
            $where = "node_id='" . $this->nodeId .
                 "'and response_type=4 and scene_id= '" . $scene_id . "'";
            
            $dao = M('twx_message');
            $messageInfo = $dao->field("id")
                ->where($where)
                ->find();
            
            $dao->startTrans();
            // 有编辑，没有添加
            do {
                if (! $messageInfo) {
                    $data = array(
                        'node_id' => $this->nodeId, 
                        'response_type' => $response_type, 
                        'add_time' => date('YmdHis'), 
                        'update_time' => date('YmdHis'), 
                        'scene_id' => $scene_id);
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
    }
    
    // 微信模板消息测试
    public function templateSend() {
        $wx_send = D('WeiXinSend', 'Service');
        $wx_send->init($this->node_id);
        $url = U("Label/MyOrder/index", 
            array(
                "node_id" => $this->node_id), '', '', true);
        
        $data = array(
            "data1" => array(
                'value' => '名称', 
                'color' => '#173177'), 
            "data2" => array(
                'value' => '金额', 
                'color' => '#173177'), 
            "data3" => array(
                'value' => date('Y-m-d H:i:s'), 
                'color' => '#173177'));
        
        $result = $wx_send->templateSend(
            $openId = 'oDZrwskn6M6395-YUZndW-tk26oA', $this->nodeId, 
            $templateType = 1, $url, $data);
        
        return $result;
    }
    
    // 粉丝标签同步
    public function subLabel() {
        $name = I('name', '');
        $id = I('id', '');
        $scene_id = M('twx_qrchannel')->where('id='.$id)->getField('scene_id');
        M('twx_qrchannel')->where('id <> '.$id.' and scene_id='.$scene_id)->delete();
        $result = M('twx_qrchannel')->where(
            array(
                'id' => $id))->save(
            array(
                'remarkname' => $name));
        
        if ($result) {
            $this->success("提交成功");
        }
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
    
    public function set_twx_user_stat(){
        set_time_limit(0);
        $weixinFansGroup = D('WeiXinFansGroup', 'Service');
        $weixinInfo = M('tweixin_info')->where('auth_flag = 1 and account_type in(2,4)')->select();
        foreach($weixinInfo as $val){
            log_write('处理机构：'.$val['node_id']);
            $this->nodeId = $val['node_id'];
            $this->node_wx_id = $val['node_wx_id'];
            $weixinFansGroup->init($this->nodeId);
            for($i=0;$i<5;$i++){
                $result = $weixinFansGroup->apiGetusersummary(strtotime('-28 day') + 3600*24*7*$i,strtotime('-22 day') + 3600*24*7*$i);
                $result2 = $weixinFansGroup->apiGetusercumulate(strtotime('-28 day') + 3600*24*7*$i,strtotime('-22 day') + 3600*24*7*$i);
                $arr = array_valtokey($result2['list'],'ref_date');
                foreach($result['list'] as $val){
                    $arr[$val['ref_date']]['new_user'] += $val['new_user'];
                    $arr[$val['ref_date']]['cancel_user'] += $val['cancel_user'];
                }
                foreach($arr as &$val){
                    $val['node_id'] = $this->nodeId;
                    $val['node_wx_id'] = $this->node_wx_id;
                    $val['ref_date'] = dateformat($val['ref_date'],'Ymd');
                    if($val['new_user'] === null){
                        $val['new_user'] = 0;
                        $val['cancel_user'] = 0;
                    }
                    M('twx_user_count')->add($val);
                }
            }
        }
    }


    /**
     * 生成卡券功能开发
     * Author: zhaobl
     * Task: #17371
     * Date: 2016/1/26
     */
    public function cardGeneration()
    {
        $batch_desc = I('param.hdname');//批次名称
        $goods_name = I('param.cardname');//卡券名称
        $print_text = I('param.remarks');//备注
        $status = I('param.generationStatus');//生成状态



        $node_id = $this->nodeId;
        $user_id = $this->userId;
        import('ORG.Util.Page'); // 导入分页类

        $count = $this->WeixinCardGenerationModel->cardGenerationInfo($node_id,$batch_desc,$goods_name,$print_text,$status);

        // 实例化分页类 传入总记录数和每页显示的记录数
        $Page = new Page($count, 10);

        $showData = $this->WeixinCardGenerationModel->cardGenerationInfo($node_id,$batch_desc,$goods_name,$print_text,$status,$Page->firstRow, $Page->listRows);
        $show = $Page->show();
        foreach($showData as $k=>&$v){
            $v['date_begin_timestamp'] = getTime(2,$v['date_begin_timestamp']);
            $v['date_end_timestamp'] = getTime(2,$v['date_end_timestamp']);
            $v['percent'] = round(($v['succ_num'] / $v['total_count'])*100).'%';
            if($v['get_stat_num'] == ''){
                $v['get_stat_num'] = 0;
            }
        }

        session("card",$showData);

        $this->assign('page', $show);
        $this->assign('showData',$showData);

        $this->display();
    }

    /**
     * 批量制卡的页面
     */
    public function cardGenerationEdit()
    {
        $this->display();
    }

    /**
     * 批量制卡表单提交到这里
     */
    public function doWeixinCardGeneration()
    {
        $goodsModel = D('Goods');

        $batchname = I('param.card_batchname');     //批次名称
        $goods_id = I('param.goods_id');            //goods_id
        $card_id = I('param.card_id');              //card_id
        $goodsNo = I('param.goods_quantity');       //数量
        $validate_type = I('param.validate_type');  //制卡方式 3为一码一券 4为一码多券
        $description = I('param.description');      //备注
        $node_id = $this->nodeId ? $this->nodeId: '';
        $user_id = $this->userId ? $this->userId : '';
        if(!$node_id || !$user_id){
            $this->error('网络出错，请重试！');
        }

        $result = $this->WeixinCardGenerationModel->weixinCardBatachGeneration($node_id,$user_id,$batchname,$goods_id,$card_id,$goodsNo,$validate_type,$description);
        if($result){
            $goodsModel->storagenum_reduc($goods_id, $goodsNo, $result, 0,'卡券领取码-批量制码');
            $result = 1;
            $this->ajaxReturn($result,'任务创建成功',1);
        }else{
            $this->ajaxReutrn($result,'任务创建失败',0);
        }

    }

    /**
     * 点击详情来这里
     */
    public function card_staticDetail()
    {
        $node_id = $this->nodeId;
        $id = I('param.id');
        $data = session('card');
        $showData = array();
        foreach($data as $k=>$v){
            if($v['batch_id'] == $id){
                $showData = $data[$k];
            }
        }

        if(!$showData){
            $this->error('网络出错，请重试！');
        }
        $this->assign('showData',$showData);
        $this->display();
    }

    /**
     * 下载领取名单来这里
     */
    public function downLoadList()
    {
        $batch_id = I('param.id');
        $node_id = $this->nodeId;
        if(!$batch_id){
            $this->error('网络出错，请重试！');
        }
        $receiveList = $this->WeixinCardGenerationModel->getReceiveList($node_id,$batch_id);


        foreach($receiveList as $k=>&$v){
            $v['begin_time'] = date('Y-m-d',strtotime($v['begin_time']));
            $v['end_time'] = date('Y-m-d',strtotime($v['end_time']));
            $v['trans_time'] = date('Y-m-d H:i:s',strtotime($v['trans_time']));

        }

        import('@.Vendor.PHPExcel') or die('include file fail.') ;

        $ObjPHPExcel = new PHPExcel();//实例化Excel对象 相当于新建一个Excel表格

        $Objsheet = $ObjPHPExcel->getActiveSheet();//获取当前活动sheet对象


        //若想添加空列或者空行可以填充空数组或者空值在其中

        $list[] = array('卡券名称','开始时间','结束时间','领取openId','领取人昵称','领取时间');//头行
        if($receiveList){
            foreach($receiveList as $k=>$v){
                $list[] = array($v['batch_short_name'],$v['begin_time'],$v['end_time'],$v['wx_open_id'],$v['nickname'],$v['trans_time']);
            }
        }

        $fileName = '领取名单';
        $Objsheet->setTitle($fileName);//sheet名

        $Objsheet->fromArray($list);//加载数组块填充数据

        //设置某列单元格格式为文本格式
        //$Objsheet->getStyle('C2')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

        $objWriter = PHPExcel_IOFactory::createWriter($ObjPHPExcel,'Excel2007');//按照指定格式生成Excel文件

        $this->browser_export('Excel2007', $fileName.'.xlsx');//输出到浏览器
        $objWriter->save("php://output");

    }

    public function browser_export($type, $filename)
    {
        //生成一个下载文件 区分Excel2003和Excel2007
        if ($type == "Excel5") {
            header('Content-Type: application/vnd.ms-excel');//告诉浏览器将要输出excel03文件
        } else {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');//告诉浏览器数据excel07文件
        }
        header('Content-Disposition: attachment;filename="' . $filename . '"');//下载后的文件名
        header('Cache-Control: max-age=0');//禁止缓存
    }

    public function _chat_msg_show($msg_type, $msg_info) {
        $html = '';
        switch ($msg_type) {
            case '0': // 文本
                $html = $msg_info;
                break;
            case '4': // 地理位置
                $html = $msg_info;
                break;
            case '5': // 点击菜单
                $html = $msg_info;
                break;
            case '1': // 图片
                $msg_info = $this->_getImgUrl($msg_info);
                $html = <<<HTML
<a href="{$msg_info}" target="_blank" class="media_img">
    <img class="wxmImg Zoomin" height="50%" width="50%" src="{$msg_info}">
</a>
HTML;
                break;
            case '6': // 卡券
                $card_info = M('twx_card_type')->where(
                    array(
                        'card_id' => $msg_info))->find();
                if ($card_info)
                    $html = "[卡券]{$card_info['title']}";
                break;
            case '2': // 图文
                $arr = json_decode($msg_info, true);
                if (is_array($arr)) {
                    // foreach($arr['Articles'] as $art){
                    $art = isset($arr['articles']) ? $arr['articles'][0] : $arr['Articles'][0];
                } else {
                    $list = M('twx_material')->find($arr);
                    $art['picurl'] = $list['material_img'];
                    $art['url'] = $list['material_link'];
                    $art['title'] = $list['material_title'];
                    $art['description'] = $list['material_summary'];
                }
                $art['picurl'] = $this->_getImgUrl($art['picurl']);
                $html = <<<HTML
<div class="appmsgImgArea">
	<img src="{$art['picurl']}" />
</div>
<div class="appmsgContentArea">
	<div class="appmsgTitle">
        <a href="{$art['url']}" target="_blank">[图文消息]{$art['title']}</a>
    </div>
    <a href="{$art['url']}" target="_blank" class="appmsgDesc">{$art['description']}</a>
</div>
HTML;
                // }
                break;
            case '3':
                $html = <<<HTML
<div class="mediaBox audioBox">
	<div class="mediaContent">
		<span class="audioTxt">语音消息</span>
        <!-- <b>3"</b> -->
		<span class="iconAudio"></span>
	</div>
</div>
HTML;
                break;
            default:
                $html = '';
                break;
        }
        
        return $html;
    }

}