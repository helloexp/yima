<?php

// 翼惠宝首页
class MerchantAction extends YhbAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        if (! $this->is_admin) {
            $this->error("访问错误！");
        }
        $post = I('request.');
        $map = array();
        $id = I('id');
        $parent_id = I('parent_id', '', 'trim,mysql_real_escape_string');
        $catalog_id = I('catalog_id', '', 'trim,mysql_real_escape_string');
        $merchant_name = I('merchant_name', '', 'trim,mysql_real_escape_string');
        $begin_time = I('start_time', '', 'trim,mysql_real_escape_string');
        $end_time = I('end_time', '', 'trim,mysql_real_escape_string');
        $status = I('status', '', 'trim,mysql_real_escape_string');
        
        if ($id != '') {
            $map['a.id'] = (int) $id;
        }
        if ($parent_id != '') {
            $map['a.parent_id'] = $parent_id;
        }
        if ($catalog_id != '') {
            $map['a.catalog_id'] = $catalog_id;
        }
        if ($merchant_name != '') {
            $map['a.merchant_name'] = array(
                'like', 
                '%' . $merchant_name . '%');
        }
        if ($status != '') {
            $map['a.status'] = $status;
        }
        
        if ($begin_time != '') {
            $map['a.add_time'][] = array(
                'EGT', 
                $begin_time . "000000");
        }
        if ($end_time != '') {
            $map['a.add_time'][] = array(
                'ELT', 
                $end_time . "232929");
        }
        
        import("ORG.Util.Page");
        $count = M()->table("tfb_yhb_node_info a")
            ->where($map)
            ->count();
        $p = new Page($count, 20);
        $page = $p->show();
        $list = M()->table("tfb_yhb_node_info a")
            ->join('tuser_info b on a.user_info_id = b.id')
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->order('a.id desc')
            ->field('a.*,b.user_id')
            ->select();
        
        $parentInfo = M('tfb_yhb_catalog')->field('id,catalog_name')
            ->where("parent_id=0 and is_delete=0")
            ->select();
        $catalogInfo = array();
        foreach ($parentInfo as $v) {
            $catalogInfo[$v['id']] = M('tfb_yhb_catalog')->field(
                'id,catalog_name')
                ->where("parent_id=" . $v['id'] . " and is_delete=0")
                ->select();
        }
        $this->assign('post', $post);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->assign("catalogInfo", $catalogInfo);
        $this->assign("parentInfo", $parentInfo);
        $this->display();
    }

    public function download() {
        if (! $this->is_admin) {
            $this->error("访问错误！");
        }
        $post = I('request.');
        $map = array();
        $id = I('id');
        $parent_id = I('parent_id', '', 'trim,mysql_real_escape_string');
        $catalog_id = I('catalog_id', '', 'trim,mysql_real_escape_string');
        $merchant_name = I('merchant_name', '', 'trim,mysql_real_escape_string');
        $begin_time = I('start_time', '', 'trim,mysql_real_escape_string');
        $end_time = I('end_time', '', 'trim,mysql_real_escape_string');
        $status = I('status', '', 'trim,mysql_real_escape_string');
        
        if ($id != '') {
            $map['a.id'] = (int) $id;
        }
        if ($parent_id != '') {
            $map['a.parent_id'] = $parent_id;
        }
        if ($catalog_id != '') {
            $map['a.catalog_id'] = $catalog_id;
        }
        if ($merchant_name != '') {
            $map['a.merchant_name'] = array(
                'like', 
                '%' . $merchant_name . '%');
        }
        if ($status != '') {
            $map['a.status'] = $status;
        }
        
        if ($begin_time != '') {
            $map['a.add_time'][] = array(
                'EGT', 
                $begin_time);
        }
        if ($end_time != '') {
            $map['a.add_time'][] = array(
                'ELT', 
                $end_time);
        }
        $count = M()->table("tfb_yhb_node_info a")
            ->where($map)
            ->count();
        
        $list = M()->table("tfb_yhb_node_info a")
            ->join('tuser_info b on a.user_info_id = b.id')
            ->where($map)
            ->order('a.id desc')
            ->field('a.*,b.user_id')
            ->select();
        if ($count == 0) {
            $this->error('无查寻结果下载！');
        }
        $fileName = date("YmdHis") . "-翼惠宝商户列表.csv";
        $fileName = iconv('utf-8', 'gbk', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $title = "旺号,商户名称,商户简称,注册时间,一级分类,二级分类,服务热线,负责人,手机号码,商户简介,状态\r\n";
        echo $title = iconv('utf-8', 'gbk', $title);
        if ($list) {
            foreach ($list as $vo) {
                $vo['user_id'] = sprintf('%05s', $vo['user_id']);
                
                if ($vo['status'] == '0') {
                    $vo['status'] = "停用";
                } elseif ($vo['status'] == '1') {
                    $vo['status'] = "启用";
                } else {
                    $vo['status'] = "未知";
                }
                $vo['parent_id'] = $this->catalog_array[$vo['parent_id']];
                $vo['catalog_id'] = $this->catalog_array[$vo['catalog_id']];
                iconv_arr('utf-8', 'gbk', $vo);
                echo "=\"{$vo['user_id']}\",=\"{$vo['merchant_name']}\",=\"{$vo['merchant_short_name']}\",=\"{$vo['add_time']}\",=\"{$vo['parent_id']}\",=\"{$vo['catalog_id']}\",=\"{$vo['hot_line_tel']}\",=\"{$vo['contact_name']}\",=\"{$vo['mobile']}\",=\"{$vo['description']}\",=\"{$vo['status']}\"" .
                     "\r\n";
            }
        }
    }

    public function add() {
        $parentInfo = M('tfb_yhb_catalog')->field('id,catalog_name')
            ->where("parent_id=0 and is_delete=0")
            ->select();
        $catalogInfo = array();
        foreach ($parentInfo as $v) {
            $catalogInfo[$v['id']] = M('tfb_yhb_catalog')->field(
                'id,catalog_name')
                ->where("parent_id=" . $v['id'] . " and is_delete=0")
                ->select();
        }
        $this->assign("catalogInfo", $catalogInfo);
        $this->assign("parentInfo", $parentInfo);
        $this->display();
    }

    public function edit() {
        if (! $this->is_admin) {
            $this->error("访问错误！");
        }
        $id = I('id');
        $parentInfo = M('tfb_yhb_catalog')->field('id,catalog_name')
            ->where("parent_id=0 and is_delete=0")
            ->select();
        $catalogInfo = array();
        foreach ($parentInfo as $v) {
            $catalogInfo[$v['id']] = M('tfb_yhb_catalog')->field(
                'id,catalog_name')
                ->where("parent_id=" . $v['id'] . " and is_delete=0")
                ->select();
        }
        $merchant = M('tfb_yhb_node_info')->where("id=" . $id)->find();
        $this->assign("merchant", $merchant);
        $this->assign("catalogInfo", $catalogInfo);
        $this->assign("parentInfo", $parentInfo);
        $this->display();
    }

    public function edit_me() {
        $userId = $this->userId;
        $parentInfo = M('tfb_yhb_catalog')->field('id,catalog_name')
            ->where("parent_id=0 and is_delete=0")
            ->select();
        $catalogInfo = array();
        foreach ($parentInfo as $v) {
            $catalogInfo[$v['id']] = M('tfb_yhb_catalog')->field(
                'id,catalog_name')
                ->where("parent_id=" . $v['id'] . " and is_delete=0")
                ->select();
        }
        $merchant = M()->table("tfb_yhb_node_info a")->join(
            'tuser_info b on a.user_info_id = b.id')
            ->field('a.*')
            ->where("b.user_id=" . $userId)
            ->find();
        $this->assign("merchant", $merchant);
        $this->assign("catalogInfo", $catalogInfo);
        $this->assign("parentInfo", $parentInfo);
        $this->display();
    }
    
    // 校验一下输入的权限列表的有效性
    protected function _checkRuleId($role_id) {
        // 查询出能用的权限
        $arr = M('tauth_node_role')->where(
            array(
                'id' => $role_id, 
                'node_id' => '00000000'))->select();
        $roleDiff = array_diff($arr, $role_id);
        if ($roleDiff) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 注册页面验证用户邮箱是否被占
     */
    public function ajaxValidEmail() {
        $tuser = M('tuser_info');
        $regemail = trim(I('fieldValue'));
        $result = $tuser->where(
            array(
                'user_name' => $regemail))->select();
        if ($result) {
            $data = array(
                trim(I('fieldId')), 
                false);
        } else {
            $data = array(
                trim(I('fieldId')), 
                true);
        }
        $this->ajaxReturn($data);
    }

    public function queryName() {
        $name = I('fieldValue', '', 'trim');
        if ($name) {
            $result = M('tnode_info')->where(
                array(
                    'node_name' => $name))->select();
            $result0 = M('tfb_yhb_node_info')->where(
                array(
                    'merchant_name' => $name))->select();
            if ($result || $result0) {
                $data = array(
                    trim(I('fieldId')), 
                    false);
            } else {
                $data = array(
                    trim(I('fieldId')), 
                    true);
            }
        }
        $this->ajaxReturn($data);
    }
    
    // 用户添加
    public function nodeadd() {
        if ($this->isPost()) {
            /* 信息校验 */
            $user_name = I('post.regemail', null, 'mysql_escape_string');
            $true_name = I('merchant_name', null, 'mysql_escape_string');
            if (! check_str($user_name, 
                array(
                    'null' => false, 
                    'strtype' => 'email'), $error)) {
                $this->error("用户名称{$error}");
            }
            $count = M('tuser_info')->where("user_name='{$user_name}'")->count();
            if ($count > 0) {
                $this->error('该用户名邮箱已被占用');
            }
            $pws1 = I('post.user_password');
            if (! check_str($pws1, array(
                'null' => false), $error)) {
                $this->error("密码{$error}");
            }
            $pws2 = I('post.re_password');
            if (! check_str($pws2, array(
                'null' => false), $error)) {
                $this->error("密码{$error}");
            }
            if ($pws1 != $pws2) {
                $this->error('两次密码不一致');
            }
            $roleId = I('post.role_id');
            
            if (! check_str($roleId, 
                array(
                    'null' => false), $error)) {
                $this->error("用户角色{$error}");
            }
            
            $roleCheck = $this->_checkRuleId($roleId);
            if (! $roleCheck) {
                $this->error("该角色不可用");
            }
            
            /* 创建sso账号信息 */
            
            // 新增用户sso接口请求
            $reqArray = array(
                'UserAdd' => array(
                    'AppId' => C('SSO_SYSID'), 
                    'NodeId' => $this->nodeId, 
                    'Name' => $user_name, 
                    'UserName' => $true_name, 
                    'Password' => $pws1, 
                    'Email' => $user_name, 
                    'Telephone' => '', 
                    'Notes' => date('Y-m-d H:i:s') .
                         ' Interface registration with lower user', 
                        'Position' => '', 
                        'Address' => '', 
                        'PosCode' => '', 
                        'CustomNo' => ''));
            $RemoteRequest = D('RemoteRequest', 'Service');
            $reqResult = $RemoteRequest->requestSsoUserCreate($reqArray);
            if ($reqResult['UserAdd']['Status']['StatusCode'] != '0000') {
                $this->error(
                    "商户新增失败：" . $reqResult['UserAdd']['Status']['StatusText']);
            }
            // 数据添加
            $data = array(
                'node_id' => $this->nodeId, 
                'user_id' => $reqResult['UserAdd']['Info']['UserId'], 
                'new_role_id' => $roleId, 
                'user_name' => $user_name, 
                'true_name' => $true_name, 
                'add_time' => date('YmdHis'));
            $result = M('tuser_info')->add($data);
            if (! $result) {
                $this->error('系统出错，添加失败');
            } else {
                /* 把商户信息存到翼惠宝的商户信息表里面 */
                $node_info = array(
                    "user_info_id" => $result, 
                    "merchant_name" => $true_name, 
                    "merchant_short_name" => I('post.merchant_short_name'), 
                    "spending_av_per" => I('post.spending_av_per'), 
                    "catalog_id" => I('post.catalog_id'), 
                    "parent_id" => I('post.parent_id'), 
                    "hot_line_tel" => I('post.hot_line_tel'), 
                    "contact_name" => I('post.contact_name'), 
                    "mobile" => I('post.contact_phone'), 
                    "province_code" => I('post.province_code'), 
                    "city_code" => I('post.city_code'), 
                    "description" => I('post.description'), 
                    "login_name" => $user_name, 
                    "add_time" => date("YmdHis"));
                $result_info = M('tfb_yhb_node_info')->add($node_info);
                if (! $result_info) {
                    $this->error('系统出错，添加失败');
                }
                $this->success('用户添加成功');
            }
            exit();
        }
    }
    
    // 商户修改
    public function nodeedit() {
        /*
         * if (!$this->_hasStandard() && !_hasIss()) {
         * $this->_handleCheckAuth("只能付费版才能使用此功能"); } if
         * (!$this->checkUserLevel()) { //$this->error('您不是管理员没有该操作权限'); }
         */
        $id = I('id', null, 'mysql_escape_string');
        $userId = I('user_id', null, 'mysql_escape_string');
        if (! check_str($userId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $userInfo = M('tuser_info')->where(
            "node_id='{$this->nodeId}' AND id='{$userId}'")->find();
        if (! $userInfo) {
            $this->error('未找到该用户信息' . M()->_sql());
        }
        
        if ($userInfo['new_role_id'] == '2' || $userInfo['new_role_id'] == '1') {
            $this->error('超级管理员不允许被编辑');
        }
        
        if ($this->isPost()) {
            $node_info = array(
                "merchant_short_name" => I('post.merchant_short_name'), 
                "spending_av_per" => I('post.spending_av_per'), 
                "catalog_id" => I('post.catalog_id'), 
                "parent_id" => I('post.parent_id'), 
                "hot_line_tel" => I('post.hot_line_tel'), 
                "contact_name" => I('post.contact_name'), 
                "mobile" => I('post.contact_phone'), 
                "description" => I('post.description'));
            
            // 数据更新
            $where = array(
                'id' => $id);
            $result = M('tfb_yhb_node_info')->where($where)->save($node_info);
            if ($result === false) {
                $this->error('系统出错，更新失败');
            }
            $this->success('用户更新成功');
            exit();
        }
    }

    public function nodeeditme() {
        $id = I('id', null, 'mysql_escape_string');
        $userId = I('user_id', null, 'mysql_escape_string');
        if (! check_str($userId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $userInfo = M('tuser_info')->where(
            "node_id='{$this->nodeId}' AND id='{$userId}'")->find();
        if (! $userInfo) {
            $this->error('未找到该用户信息' . M()->_sql());
        }
        
        if ($userInfo['new_role_id'] == '2' || $userInfo['new_role_id'] == '1') {
            $this->error('超级管理员不允许被编辑');
        }
        
        if ($this->isPost()) {
            $node_info = array(
                "hot_line_tel" => I('post.hot_line_tel'), 
                "description" => I('post.description'));
            
            // 数据更新
            $where = array(
                'id' => $id);
            $result = M('tfb_yhb_node_info')->where($where)->save($node_info);
            if ($result === false) {
                $this->error('系统出错，更新失败');
            }
            $this->success('用户更新成功');
            exit();
        }
    }

    public function reset_password() {
        $id = I('id', null, 'mysql_escape_string');
        $userId = I('user_id', null, 'mysql_escape_string');
        if (! check_str($userId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $userInfo = M('tuser_info')->where(
            "node_id='{$this->nodeId}' AND id='{$userId}'")->find();
        if (! $userInfo) {
            $this->error('未找到该用户信息' . M()->_sql());
        }
        
        if ($userInfo['new_role_id'] == '2' || $userInfo['new_role_id'] == '1') {
            $this->error('超级管理员不允许被编辑');
        }
        if ($this->isPost()) {
            // 更新密码
            $pws1 = I('post.user_password');
            if (! check_str($pws1, array(
                'null' => false), $error)) {
                $this->error("密码{$error}");
            }
            $pws2 = I('post.re_password');
            if (! check_str($pws2, array(
                'null' => false), $error)) {
                $this->error("密码{$error}");
            }
            if ($pws1 != $pws2) {
                $this->error('两次密码不一致');
            }
            $password = I('post.user_password');
            if ($password != '') {
                // 更新密码
                $result = $this->_modifySsoPwd(
                    array(
                        'user_name' => $userInfo['user_name'], 
                        'password' => md5($password)));
                $result = $result['Status'];
                if ($result['StatusCode'] != '0000') {
                    $this->error($result['StatusText']);
                }
            }
            $this->success('重置密码成功');
            exit();
        }
    }

    public function change_status() {
        if (! $this->is_admin) {
            $this->error("访问错误！");
        }
        $id = I('id');
        $merchant = M('tfb_yhb_node_info')->where("id=" . $id)->find();
        $userinfo = M('tuser_info')->where("id=" . $merchant['user_info_id'])->find();
        $status = I('status', null);
        user_act_log('停用用户', '', 
            array(
                'act_code' => '3.5.2.5'));
        /*
         * if (!$this->checkUserLevel()) $this->error('您不是管理员没有该操作权限');
         */
        $userId = $userinfo['user_id'];
        if (! check_str($userId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $userInfo = M('tuser_info')->where(
            "node_id='{$this->nodeId}' AND user_id='{$userId}'")->find();
        if (! $userInfo) {
            $this->error('未找到该用户信息');
        }
        /* 如果是停用 需要把相关活动也停掉 */
        M()->startTrans();
        if ($status == 0) {
            /* 店铺停掉 */
            $map = array(
                'merchant_id' => $id);
            $resstore = M("tfb_yhb_store")->where($map)->save(
                array(
                    'line_status' => 1));
            if ($resstore === false) {
                M()->rollback(); // 不成功，则回滚
                $this->ajaxReturn(0, "操作失败0！", 0);
            }
            $resgoods = M("tfb_yhb_goods")->where($map)->save(
                array(
                    'line_status' => 2));
            if ($resgoods === false) {
                M()->rollback(); // 不成功，则回滚
                $this->ajaxReturn(0, "操作失败1！", 0);
            }
            $map = array(
                'strb.merchant_id' => $id, 
                '_string' => 'strb.store_id=stb.store_id');
            $reststore = M()->table("tstore_info stb,tfb_yhb_store strb ")
                ->where($map)
                ->save(array(
                'stb.status' => 1));
            if ($reststore === false) {
                M()->rollback(); // 不成功，则回滚
                $this->ajaxReturn(0, "操作失败2！", 0);
            }
            /* 活动停掉 */
            
            $map = array(
                'pg.merchant_id' => $id, 
                '_string' => 'pg.goods_id=g.goods_id');
            $restgoods = M()->table('tgoods_info g ,tfb_yhb_goods pg ')
                ->where($map)
                ->save(array(
                'g.status' => 1));
            if ($restgoods === false) {
                M()->rollback(); // 不成功，则回滚
                $this->ajaxReturn(0, "操作失败3！", 0);
            }
            
            $map = array(
                'pg.merchant_id' => $id, 
                '_string' => 'pg.goods_id=b.goods_id');
            $restbatch = M()->table('tbatch_info b,tfb_yhb_goods pg')
                ->where($map)
                ->save(array(
                'b.status' => 1));
            if ($restbatch === false) {
                M()->rollback(); // 不成功，则回滚
                $this->ajaxReturn(0, "操作失败4！", 0);
            }
            $map = array(
                'pg.merchant_id' => $id, 
                '_string' => 'pg.goods_id=b.goods_id and b.m_id=m.id');
            $restmarketing = M()->table(
                'tmarketing_info m,tbatch_info b,tfb_yhb_goods pg')
                ->where($map)
                ->save(array(
                'm.status' => 2));
            if ($restmarketing === false) {
                M()->rollback(); // 不成功，则回滚
                $this->ajaxReturn(0, "操作失败5！", 0);
            }
        }
        // 接口请求
        $req_array = array(
            "UserStatusReq" => array(
                'UserId' => $userId, 
                'Status' => $status == 1 ? 0 : 1));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $reqResult = $RemoteRequest->requestSsoUserStatus($req_array);
        $ret_msg = $reqResult['UserStatusRes']['Status'];
        if (! $reqResult || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error("更新失败:{$ret_msg['StatusText']}");
        }
        $result = M('tuser_info')->where("user_id='{$userId}'")->save(
            array(
                'status' => $status == 1 ? 0 : 1));
        $map = array(
            'id' => $id);
        $res = M("tfb_yhb_node_info")->where($map)->save(
            array(
                'status' => $status));
        if ($res && $result) {
            
            M()->commit();
            $this->ajaxReturn(1, "操作成功！", 1);
        } else {
            M()->rollback(); // 不成功，则回滚
            $this->ajaxReturn(0, "操作失败6！", 0);
        }
    }

    public function catalog() {
        if (! $this->is_admin) {
            $this->error("访问错误！");
        }
        $queryList = M()->table("tfb_yhb_catalog")
            ->where("parent_id=0 and is_delete=0")
            ->select();
        $child_catalog = array();
        foreach ($queryList as $v) {
            $child_catalog[$v['id']] = M()->table("tfb_yhb_catalog")
                ->where("parent_id=" . $v['id'] . " and is_delete=0")
                ->select();
        }
        $this->assign("queryList", $queryList);
        $this->assign("child_catalog", $child_catalog);
        $this->display();
    }

    public function add_catalog() {
        if (! $this->is_admin) {
            $this->error("访问错误！");
        }
        $parent_id = I('parent_id');
        if (IS_POST) {
            $model = M('tfb_yhb_catalog');
            $count = $model->where(
                "parent_id=" . $parent_id . " and catalog_name='" .
                     trim(I('catalog_name')) . "'")->count();
            if ($count > 0) {
                $this->error("分类名称重复！");
            }
            $data = array(
                'catalog_name' => trim(I('catalog_name')), 
                'parent_id' => $parent_id, 
                'addtime' => date('YmdHis'));
            $flag = $model->add($data);
            if ($flag === false) {
                $this->error('分类添加失败！');
            }
            
            $this->success('分类添加成功！');
            exit();
        }
        $this->assign("parent_id", $parent_id);
        $this->display();
    }

    public function edit_catalog() {
        if (! $this->is_admin) {
            $this->error("访问错误！");
        }
        $id = I('id');
        $catalog_info = M('tfb_yhb_catalog')->where("id=" . (int) $id)->find();
        $parent_id = (int) I('parent_id');
        if (IS_POST) {
            $model = M('tfb_yhb_catalog');
            $count = $model->where(
                "parent_id=" . $parent_id . " and catalog_name='" .
                     trim(I('catalog_name')) . "' and id <>" . $id)->count();
            if ($count > 0) {
                $this->error('分类名称重复！');
            }
            $data = array(
                'catalog_name' => I('catalog_name'));
            $flag = $model->where("id=" . (int) $id)->save($data);
            if ($flag === false) {
                $this->error('分类编辑失败！');
            }
            
            $this->success('分类编辑成功！');
            exit();
        }
        $this->assign("catalog_info", $catalog_info);
        $this->display();
    }

    public function delete_catalog() {
        if (! $this->is_admin) {
            $this->error("访问错误！");
        }
        
        // 数据库中增加新字段 is_delete
        $id = I('id');
        $data['is_delete'] = 1;
        $map = array(
            'id' => $id);
        $count = M('tfb_yhb_node_info')->where(
            "catalog_id=" . $id . " or parent_id=" . $id)->count();
        if ($count > 0) {
            $this->ajaxReturn(0, "有商户正在使用该类，不能删除！", 0);
        }
        $list = M()->query(
            "select * from tfb_yhb_store where merchant_id in(select id from tfb_yhb_node_info where catalog_id=" .
                 $id . " or parent_id=" . $id . ")");
        if ($list) {
            $this->ajaxReturn(0, "该分类下的商户有门店数据，不能删除！", 0);
        }
        $list = M()->query(
            "select * from tfb_yhb_goods where merchant_id in(select id from tfb_yhb_node_info where catalog_id=" .
                 $id . " or parent_id=" . $id . ")");
        if ($list) {
            $this->ajaxReturn(0, "该分类下的商户有优惠数据，不能删除！", 0);
        }
        $count = M('tfb_yhb_catalog')->where("parent_id = " . $id)->count();
        if ($count > 0) {
            $this->ajaxReturn(0, "该分类有二级分类，不能删除！", 0);
        }
        $res = M("tfb_yhb_catalog")->where($map)->save($data);
        if ($res) {
            $this->ajaxReturn(1, "删除成功！", 1);
        } else {
            $this->ajaxReturn(0, "删除失败！", 0);
        }
    }

    public function AjaxCatalog() {
        $data = I('request.');
        $query_arr = $model = M('tfb_yhb_catalog');
        $parent_id = $data['parent_id'];
        $field_str = '';
        $map = array(
            'parent_id' => (int) $parent_id);
        $field_str = 'id,catalog_name';
        $query_arr = $model->field($field_str)
            ->where($map)
            ->select() or $query_arr = array();
        // $this->ajaxReturn($query_arr, "查询成功", 0);
        echo json_encode($query_arr);
    }

    public function nodequery() { // 查询是否存在
        $nodename = trim(I('post.merchant_name'));
        $count = M("tfb_yhb_node_info")->where(
            "merchant_name='" . $nodename . "'")->count();
        log_write("查询翼惠宝商户是否存在=" . $nodename);
        if ($count == 0) {
            echo "1";
        } else {
            echo "2";
        }
        exit();
    }
    
    // 修改密码
    protected function _modifySsoPwd($arr) {
        // 请求用户修改接口
        // 请求SSO用户密码修改
        $reqData = array(
            'ResetChildPassword' => array(
                'AppId' => C('SSO_SYSID'), 
                'Token' => $this->userInfo['token'], 
                'UserName' => $arr['user_name'], 
                'Password' => $arr['password']));
        $requestServ = D('RemoteRequest', 'Service');
        $resp_arr = $requestServ->requestSsoInterface('ResetChildPassword', 
            $reqData);
        $statusArr = $resp_arr['Status'];
        if ($statusArr['StatusCode'] != '0000') {
            $resp_desc = $statusArr['StatusText'];
            log_write($resp_desc);
        }
        return $resp_arr;
    }
}
