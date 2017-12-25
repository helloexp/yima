<?php

/* 微信菜单设置 */
class WeixinMenuAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
        $this->uploadPath = './Home/Upload/Weixin/'; // 设置附件上传目录
    }

    public function index() {
        $node_id = $this->nodeId;
        
        // 获取是否已经设置appid
        $wxInfo = M('tweixin_info')->where(
            array(
                'node_id' => $node_id))->find();
        if (! $wxInfo) {
            $this->error("请先进行微信公众平台渠道配置", U('Weixin/Weixin/index'));
        }
        if (! $wxInfo['app_id'] || ! $wxInfo['app_secret']) {
            $initSet = true;
            $this->assign('initSet', 1);
        }
        // 获取菜单列表
        
        $result = M('TwxMenu')->where("node_id='" . $node_id . "'")
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
        // 如果提交，响应为ajax
        if ($this->isPost()) {
            $title = I('menu_title');
            $level = strval(I('level', '0'));
            if ($level != '1')
                $level == '2';
            $parent_id = $level == '1' ? '0' : intval(I('parent_id'));
            $response_class = I('response_class', '0');
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
            
            if ($response_class == '1') {
                $response_info = $response_info_img;
            } elseif ($response_class == '2') {
                $response_info = $response_info_url;
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
                    'sort_id' => 0));
            if (! $result) {
                $this->error("添加失败");
            } else {
                node_log("【微信公众账号助手】菜单添加,菜单名：{$title}"); // 记录日志
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
            $parentInfo = M('twx_menu')->where(
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
        $info = M('twx_menu')->where(
            "id='" . $id . "' and node_id='" . $this->nodeId . "'")->find();
        if (! $info) {
            $this->error("菜单不存在,现在添加", U('WeixinMenu/add'));
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
            $result = M("twx_menu")->where(
                "id='" . $id . "' and node_id='" . $this->nodeId . "'")->save(
                array(
                    'title' => $title, 
                    'response_class' => $response_class, 
                    'response_info' => $response_info, 
                    'add_time' => $add_time));
            if ($result === false) {
                $this->error("编辑失败");
            } else {
                node_log("【微信公众账号助手】微信账号绑定"); // 记录日志
                $this->success("编辑成功");
            }
        }
        
        $actionTitle = '编辑菜单 - ';
        $level = $info['level'];
        $parent_id = $info['parent_id'];
        // 查询一级菜单名
        if ($level == '2') {
            $parentInfo = M('twx_menu')->where(
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
    
    // 菜单排序
    public function menuSort() {
        if ($this->isPost()) {
            // $menuData = '[{id:1,children:[{id:3},{id:4}]},{id:2}]';
            $menuData = I('menuData', '', 'html_entity_decode');
            $arr = json_decode($menuData, true);
            foreach ($arr as $k => $v) {
                $result = M('twx_menu')->where("id='" . intval($v['id']) . "'")->save(
                    array(
                        'sort_id' => $k));
                $children = $v['children'];
                foreach ($children as $k2 => $v2) {
                    $result = M('twx_menu')->where(
                        "id='" . intval($v2['id']) . "'")->save(
                        array(
                            'sort_id' => $k2));
                }
            }
            node_log("【微信公众账号助手】微信菜单排序"); // 记录日志
            $this->success("排序成功");
        }
        
        // 获取菜单列表
        $node_id = $this->nodeId;
        $result = M('TwxMenu')->where("node_id='" . $node_id . "'")
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
    
    // 菜单删除
    public function delete() {
        $id = intval(I('id'));
        if (! $id) {
            $this->error("参数不足");
        }
        $info = M('twx_menu')->where(
            "id='" . $id . "' and node_id='" . $this->nodeId . "'")->find();
        if (! $info) {
            $this->error("菜单不存在");
        }
        // 查询是否有下级菜单
        if ($info['level'] == '1') {
            $count = M('twx_menu')->where("parent_id='" . $info['id'] . "'")->count();
            if ($count > 0) {
                $this->error("存在下级菜单，不允许删除");
            }
        }
        $result = M('twx_menu')->where(
            "id='" . $id . "' and node_id='" . $this->nodeId . "'")->delete();
        node_log("【微信公众账号助手】菜单删除"); // 记录日志
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
        if (! $wxInfo['app_id'] || ! $wxInfo['app_secret']) {
            $this->error("AUTH_SET");
        }
        $result = M('TwxMenu')->where("node_id='" . $this->nodeId . "'")
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
                    'type' => 'view', 
                    'url' => html_entity_decode($v['response_info'])) : array(
                    'name' => $v['title'], 
                    'type' => 'click', 
                    'key' => 'MENU_' . $v['id']));
            }
        }
        if (count($menuArr) == 0) {
            $this->error("至少要设置一个菜单");
            return;
        }
        // 找到所有二级菜单
        foreach ($result as $v) {
            if ($v['level'] == '2' && isset($menuArr[$v['parent_id']])) {
                
                if (count($menuArr[$v['parent_id']]['sub_button']) >= 5) {
                    $this->error(
                        '菜单:' . $menuArr[$v['parent_id']]['name'] . " 最多允许5个子级菜单");
                    break;
                }
                
                $menuArr[$v['parent_id']]['sub_button'][] = ($v['response_class'] ==
                     2 ? array(
                        'name' => $v['title'], 
                        'type' => 'view', 
                        'url' => html_entity_decode($v['response_info'])) : array(
                        'name' => $v['title'], 
                        'type' => 'click', 
                        'key' => 'MENU_' . $v['id']));
            }
        }
        $menuArr = array(
            'button' => array_values($menuArr));
        $json = json_encode($menuArr);
        $service = D('WeiXinMenu', 'Service');
        $json = $service->unicodeDecode($json);
        // $json =
        // '{"button":[{"type":"click","name":"member","key":"11"},{"type":"click","name":"setting","key":"12"},{"type":"click","name":"barcode","key":"13"}]}';
        // dump($json);exit;
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
        if (! $wxInfo['app_id'] || ! $wxInfo['app_secret']) {
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