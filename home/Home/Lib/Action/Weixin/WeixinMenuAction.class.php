<?php

/* 微信菜单设置 */
class WeixinMenuAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
        $this->uploadPath = './Home/Upload/Weixin/'; // 设置附件上传目录
    }

    public function index() {
        $id = I('id','');
        $node_id = $this->nodeId;
        
        // 获取是否已经设置appid
        $wxInfo = M('tweixin_info')->where(
            array(
                'node_id' => $node_id))->find();
        if (! $wxInfo) {
            $this->error("请先进行微信公众平台渠道配置", U('Weixin/Weixin/index'));
        }
        if ($wxInfo['auth_flag'] == 0 && (! $wxInfo['app_id'] || ! $wxInfo['app_secret'])) {
            $initSet = true;
            $this->assign('initSet', 1);
        }
        // 获取菜单列表
        
        $result = M('TwxMenu')->where("node_id='" . $node_id . "'")
            ->order("level,sort_id,id asc")
            ->select();
        $menuArr = array();
        // 找到所有一级菜单
        if(!$result) $result = array();
        foreach ($result as $v) {
            if ($v['response_class'] == '6') $v['response_info'] = $v['batch_id'];
            $v['type'] = in_array($v['response_class'],array(0,1,5)) ? 0 : 1;
            if ($v['level'] == '1') {
                $menuArr[$v['id']] = $v;
            }elseif ($v['level'] == '2') {
                $menuArr[$v['parent_id']]['sub_menu'][] = $v;
            }
        }
        //微信菜单事件
        $events_list=D('FbLiaoNing','Service')->getEventListByNodeId($this->nodeId);
        $this->assign("events_list",$events_list);
        $this->assign('menuArr', array_values($menuArr));
        $this->assign('flag', empty($menuArr) ? 1 : 0);
        $this->assign('account_type', $wxInfo['account_type']);
        $this->assign('id', $id);
        $this->assign('nodeId',$this->node_id);
        // echo '<pre>';
        // print_r(array_values($menuArr));exit;
        $this->display();
    }

    /**
     * 获取卡券数据
     */
    public function getCardInfo(){
        $card_id = I('card_id','');
        $result = D('TweixinInfo')->getCardInfo($card_id);
        $this->ajaxReturn($result);
    }

    /**
     * 获取营销活动数据
     */
    public function getBatchInfo(){
        $batch_id = I('batch_id','');
        $result = D('TweixinInfo')->getBatchInfo($batch_id,$this->nodeId);
        $this->ajaxReturn($result);
    }
    
    // 菜单排序
    public function menuSort() {
        $id = I('id');
        $sort_id = I('sort_id');
        M('twx_menu')->where("node_id = '".$this->nodeId."' and id =".$id)->setField('sort_id',$sort_id);
    }
    
    public function menuSubmit(){
        $id = I('id','');
        $title = I('title','');
        $level = I('level','');
        $parent_id = I('parent_id',0);
        $response_class = I('response_class','');
        $response_info = I('response_info','','');
        $sort_id = I('sort_id','');
        $add_time = date('YmdHis');
        if ($response_class == '6') {
            $batchInfo = D('TweixinInfo')->getBatchInfo($response_info,$this->nodeId);
//            $labelId = $this->getLabelIdAndTryAddLabelInfo($batchInfo['batch_id'], $batchInfo['batch_type']);
            $labelId = M('tbatch_channel')->join(' a inner join tchannel b on a.channel_id=b.id')->where(array('a.node_id'=>$this->node_id,'a.batch_id'=>$response_info))->getField('a.id');
            $m = $batchInfo['batch_type'] == '4' ? 'MemberRegistration' : 'Label'; // 会员粉丝招幕(特殊处理,不需要跳到标签)
            $url = U('Label/' . $m . '/index/', array('id' => $labelId), '', '', true);
            $batch_id = $response_info;
            $response_info = $batchInfo['name'] .
                 "\r\n<a href='" . $url . "'>参与活动</a>";
        } else {
            $batch_id = '';
        }
        if(empty($title)){
            $this->error('菜单标题不能为空');
        }
        /*
        if(empty($response_info)){
            $this->error('请设置菜单回复内容');
        }
        */
        if($level == 1){
            if (! check_str($title, array('maxlen' => 16))) {
                $this->error('菜单过长');
            }
        }else{
            if (! check_str($title, array('maxlen' => 40))) {
                $this->error('菜单过长');
            }
        }
        $allMenuData = M('twx_menu')->where(array('node_id'=>$this->nodeId))->select();
        //整理空的内容的一级菜单或者二级菜单给到前台使用
        $isNullData = '';
        $parentId = '';
        foreach($allMenuData as $key => $value){
            foreach($allMenuData as $key1 => $value1){
                if($value['id'] == $value1['parent_id'] && empty($value1['response_info'])){
                    $isNullData = $value1['id'];
                    $parentId = $value['id'];
                }
            }
        }
        if(empty($isNullData) && empty($parentId)){
            $join = ' a LEFT JOIN twx_menu b ON a.id = b.parent_id ';
            $where = array('a.node_id'=>$this->nodeId,'a.parent_id'=>0,'a.response_info'=>'','b.id'=>array('EXP','IS NULL'));
            $allMenuData = M('twx_menu')->field('a.*')->join($join)->where($where)->find();
            if(!empty($allMenuData)){
                $isNullData = $allMenuData['id'];
                $parentId = $allMenuData['parent_id'];
            }
        }
        if(!$id){
            // 校验子菜单个数
            $count = M('twx_menu')->where(
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
            if(!empty($parent_id)){
                M("twx_menu")->where("id='" . $parent_id . "' and node_id='" . $this->nodeId . "'")->save(array('response_class'=>0,'response_info'=>'','batch_id'=>''));
            }
            $result = M("twx_menu")->add(
                array(
                    'node_id' => $this->nodeId, 
                    'parent_id' => $parent_id, 
                    'level' => $level, 
                    'title' => $title, 
                    'response_class' => $response_class, 
                    'response_info' => $response_info,
                    'add_time' => $add_time, 
                    'sort_id' => $sort_id, 
                    'batch_id' => $batch_id));
            if (! $result) {
                $this->error("添加失败");
            } else {
                $this->ajaxReturn(array('id'=>$result,'sort_id'=>$sort_id,'isNullId'=>$isNullData,'parentId'=>$parentId),'添加成功',1);
            }
        }else{
            $result = M("twx_menu")->where(
                "id='" . $id . "' and node_id='" . $this->nodeId . "'")->save(
                array(
                    'title' => $title, 
                    'response_class' => $response_class, 
                    'response_info' => $response_info,
                    'add_time' => $add_time, 
                    'sort_id' => $sort_id, 
                    'batch_id' => $batch_id));
            if ($result === false) {
                $this->error("编辑失败");
            } else {
                $this->ajaxReturn(array('id'=>$id,'sort_id'=>$sort_id,'isNullId'=>$isNullData,'parentId'=>$parentId),'编辑成功',1);
            }
        }
    }
    
    public function menuDelete(){
        $id = I('id');
        if (! $id) {
            $this->error("参数不足");
        }
        $result = M('twx_menu')->where(
            "(id = '".$id."' or parent_id = '".$id."') and node_id = '".$this->nodeId."'")->delete();
        $this->publicWx();
        $this->success("删除成功");
    }
    
    
    // 发布到微信
    public function publicWx() {
        $wxInfo = M('tweixin_info')->where("node_id='" . $this->nodeId . "'")
            ->field("app_id,app_secret,app_access_token")
            ->find();
        if (! $wxInfo) {
            $this->error("商户未配置微信业务，请选设置公众账号", U("Weixin/index"));
        }
        if ($wxInfo['auth_flag'] == 0 && (! $wxInfo['app_id'] || ! $wxInfo['app_secret'])) {
            $this->error("AUTH_SET");
        }
        $result = M('TwxMenu')->where("node_id='" . $this->nodeId . "'")
            ->order("level,sort_id asc")
            ->getField(
            "id,id,title,level,parent_id,response_class,response_info");
        $menuArr = array();
        if(!$result) $this->error("请设置菜单内容");
        //校验是否有我的二维码事件
        $check_myqrcode_evets=D('FbLiaoNing','Service')->checkMyQrCodeByNodeId($this->nodeId);
        // 找到所有一级菜单
        foreach ($result as $v) {
            if(!$v['title'] || ($v['level'] == 2 && !$v['response_info'])){
                $data[] = $v;
            }
            if ($v['level'] == '1') {
                $id = intval($v['id']);
                if (count($menuArr) >= 3) {
                    $this->error("最多允许3个一级菜单");
                    break;
                }
                switch ($v['response_class']) {
                    case 2:
                        $menuArr[$id] = array(
                            'name' => $v['title'], 
                            'type' => 'view', 
                            'url' => html_entity_decode($v['response_info']));
                        if(stristr($menuArr[$id]['url'],'http://') === false){
                            $menuArr[$id]['url'] = 'http://'.$menuArr[$id]['url'];
                        }
                        break;
                    case 3:
                        $menuArr[$id] = array(
                            'name' => $v['title'], 
                            'type' => 'scancode_push', 
                            'key' => 'MENU_' . $v['id']);
                        break;
                    case 7:
                        $menuArr[$id] = array(
                                'name' => $v['title'],
                                'type' => 'view',
                                'url' => html_entity_decode($v['response_info']));
                        if(stristr($menuArr[$id]['url'],'http://') === false){
                            $menuArr[$id]['url'] = 'http://'.$menuArr[$id]['url'];
                        }
                        break;
                    case 8://我的二维码
                        //没有此功能时过滤
                        if(!$check_myqrcode_evets) break;
                        $menuArr[$id]=[
                                    'name'=>$v['title'],
                                    'type'=>'click',
                                    'key' => CommonConst::WEIXIN_MENU_MYQRCODE_KEY.$v['id'],
                                ];
                        break;
                    default:
                        $menuArr[$id] = array(
                            'name' => $v['title'], 
                            'type' => 'click', 
                            'key' => 'MENU_' . $v['id']);
                        break;
                }
            }if ($v['level'] == '2' && isset($menuArr[$v['parent_id']])) {
                if (count($menuArr[$v['parent_id']]['sub_button']) >= 5) {
                    $this->error(
                        '菜单:' . $menuArr[$v['parent_id']]['name'] . " 最多允许5个子级菜单");
                    break;
                }
                
                switch ($v['response_class']) {
                    case 2:
                        $fullUrl = html_entity_decode($v['response_info']);
                        if(stristr($v['response_info'],'http://') === false){
                            $fullUrl = 'http://'.html_entity_decode($v['response_info']);
                        }
                        $menuArr[$v['parent_id']]['sub_button'][] = array(
                            'name' => $v['title'], 
                            'type' => 'view', 
                            'url' => $fullUrl);
                        break;
                    case 3:
                        $menuArr[$v['parent_id']]['sub_button'][] = array(
                            'name' => $v['title'], 
                            'type' => 'scancode_push', 
                            'key' => 'MENU_' . $v['id']);
                        break;
                    case 7:
                        $fullUrl = html_entity_decode($v['response_info']);
                        if(stristr($v['response_info'],'http://') === false){
                            $fullUrl = 'http://'.html_entity_decode($v['response_info']);
                        }
                        $menuArr[$v['parent_id']]['sub_button'][] = array(
                                'name' => $v['title'],
                                'type' => 'view',
                                'url' => $fullUrl);
                        break;
                    case 8://我的二维码
                        //没有此功能时过滤
                        if(!$check_myqrcode_evets) break;
                        $menuArr[$v['parent_id']]['sub_button'][]=[
                                    'name'=>$v['title'],
                                    'type'=>'click',
                                    'key' => CommonConst::WEIXIN_MENU_MYQRCODE_KEY.$v['id'],
                                ];
                        break;
                    default:
                        $menuArr[$v['parent_id']]['sub_button'][] = array(
                            'name' => $v['title'], 
                            'type' => 'click', 
                            'key' => 'MENU_' . $v['id']);
                        break;
                }
            }
        }
        if (count($menuArr) == 0) {
            $this->error("至少要设置一个菜单..");
            return;
        }
        // 找到所有二级菜单
        foreach ($menuArr as $v) {
            if(!count($v['sub_button'])){
                $id = substr($v['key'],5);
                $menuInfo = M('twx_menu')->where("id='".$id."'")->find();
                $data[] = $menuInfo;
            }
        }
        log_write('$data is :'.print_r($data,true));
//        if(!empty($data)) $this->ajaxReturn($data,'添加失败',0);
        $menuArr = array(
            'button' => array_values($menuArr));
        $json = json_encode($menuArr);
        $service = D('WeiXinMenu', 'Service');
        $json = $service->unicodeDecode($json);
        $service->init($wxInfo['app_id'], $wxInfo['app_secret'], $wxInfo['app_access_token']);
        // 先获取token
        if (! $wxInfo['app_access_token']) {
            $resultToken = $service->getToken();
            if (! $resultToken || ! $resultToken['access_token']) {
                $this->error(
                    "获取token失败:[" . $resultToken['errcode'] . ']' .
                         $resultToken['errmsg']);
            }
            $app_access_token = $resultToken['access_token'];
        }
        
        $result = $service->create($json);
        if (! $result) {
            $this->error('发布失败');
        }
        
        // 如果验证码超时，或者失效
        if ($result['errcode'] == '40001' || $result['errcode'] == '42001') {
            $resultToken = $service->getToken();
            if (! $resultToken || ! $resultToken['access_token']) {
                $this->error(
                    '获取token失败:[' . $resultToken['errcode'] . ']' .
                         $resultToken['errmsg']);
            }
            $app_access_token = $resultToken['access_token'];
        }
        
        // 更新数据库
        if ($app_access_token) {
            $result = M('tweixin_info')->where(
                "node_id='" . $this->nodeId . "'")->save(
                array(
                    'app_access_token' => $app_access_token));
        }
        tag('view_end');
        // 得到token再次请求
        $result = $service->create($json);
        if (! $result) {
            $this->error('发布失败：');
        }
        if ($result['errcode']) {
            $this->error(
                '发布失败：[' . $result['errcode'] . ']' . $result['errmsg']);
        }
        node_log("【微信公众账号助手】菜单发布"); // 记录日志
        $this->success("发布成功");
    }
    // 停用微信菜单
    public function stopWx() {
        $wxInfo = M('tweixin_info')->where("node_id='" . $this->nodeId . "'")
            ->field("app_id,app_secret,app_access_token")
            ->find();
        if (! $wxInfo) {
            $this->error("商户未配置微信业务，请选设置公众账号", U("Weixin/index"));
        }
        if ($wxInfo['auth_flag'] == 0 && (! $wxInfo['app_id'] || ! $wxInfo['app_secret'])) {
            $this->error("AUTH_SET");
        }
        $service = D('WeiXinMenu', 'Service');
        $service->init($wxInfo['app_id'], $wxInfo['app_secret'], 
            $wxInfo['app_access_token']);
        // 先获取token
        if (! $wxInfo['app_access_token']) {
            $resultToken = $service->getToken();
            if (! $resultToken || ! $resultToken['access_token']) {
                $this->error(
                    "获取token失败:[" . $resultToken['errcode'] . ']' .
                         $resultToken['errmsg']);
            }
            $app_access_token = $resultToken['access_token'];
        }
        $json = '';
        $result = $service->stop($json);
        if (! $result) {
            $this->error('停用失败');
        }
        
        // 如果验证码超时，或者失效
        if ($result['errcode'] == '40001' || $result['errcode'] == '42001') {
            $resultToken = $service->getToken();
            if (! $resultToken || ! $resultToken['access_token']) {
                $this->error(
                    '获取token失败:[' . $resultToken['errcode'] . ']' .
                         $resultToken['errmsg']);
            }
            $app_access_token = $resultToken['access_token'];
        }
        
        // 更新数据库
        if ($app_access_token) {
            $result = M('tweixin_info')->where(
                "node_id='" . $this->nodeId . "'")->save(
                array(
                    'app_access_token' => $app_access_token));
        }
        // 得到token再次请求
        $result = $service->stop($json);
        if (! $result) {
            $this->error('停用失败：');
        }
        if ($result['errcode']) {
            $this->error(
                '停用失败：[' . $result['errcode'] . ']' . $result['errmsg']);
        }
        node_log("【微信公众账号助手】菜单停用"); // 记录日志
        $this->success("停用成功");
    }
    // 保存授权设置 (ajax)
    public function auth() {
        // 获取是否已经设置appid
        $wxInfo = M('tweixin_info')->where(
            array(
                'node_id' => $this->nodeId))->find();
        if (! $wxInfo) {
            echo '<A href="' . U('Weixin/Weixin/index') . '">请先进行微信公众平台渠道配置</A>';
            exit();
        }
        
        if ($this->isPost()) { // 返回json
            $app_id = I('app_id');
            $app_secret = I('app_secret');
            // 校验并获取 token
            /*
             * $service = D('WeiXinMenu','Service');
             * $service->init($app_id,$app_secret); $resultToken =
             * $service->getToken(); if(!$resultToken ||
             * !$resultToken['access_token']){
             * $this->error('获取token失败:['.$resultToken['errcode'].']'.$resultToken['errmsg']);
             * } $app_access_token = $resultToken['access_token'];
             */
            
            // 更新数据库
            $result = M('tweixin_info')->where(
                "node_id='" . $this->nodeId . "'")->save(
                array(
                    'app_id' => $app_id, 
                    'app_secret' => $app_secret, 
                    'app_access_topen' => $app_access_token));
            if ($result === false) {
                $this->error("授权设置失败");
            }
            node_log("【微信公众账号助手】微信开发授权"); // 记录日志
            $this->success("授权设置成功");
            exit();
        }
        $info = M('tweixin_info')->field("app_id,app_secret")
            ->where("node_id='" . $this->nodeId . "'")
            ->find();
        $this->assign('info', $info);
        $this->display();
    }
}