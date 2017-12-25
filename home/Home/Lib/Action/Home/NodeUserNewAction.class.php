<?php

/*
 * 机构用户角色管理
 */
class NodeUserNewAction extends BaseAction {

    private $roleId = null;

    const ADMIN_NODE_ID = '00000000';

    const ROLE_ID_POS_USER = '3';
    // 终端操作员
    const ROLE_ID_ADMIN = '2';
    // 商户管理员
    public function _initialize() {
        // 凭证通用户不校验
        if (_hasIss()) {
            $this->_authAccessMap = '*';
        }
        parent::_initialize();
    }
    // 用户管理
    public function index() {
        $name = I('name', null, 'mysql_real_escape_string,trim');
        if (isset($name) && $name != '') {
            $map['u.user_name'] = array(
                'like', 
                "%{$name}%");
        }
        $map['u.node_id'] = $this->nodeId;
        $map['_string'] = "u.role_id > 2 or u.new_role_id>=2";
        import("ORG.Util.Page");
        $count = M()->table('tuser_info u')
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $userList = M()->table('tuser_info u')
            ->field('u.*,r.role_name role_name_old,r2.title role_name')
            ->join('trole_info r ON u.role_id=r.role_id')
            ->join('tauth_user_role r2 ON u.new_role_id=r2.id')
            ->where($map)
            ->order('u.add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $status = array(
            '已启用', 
            '已停用');
        // dump($userList);exit;
        $page = $p->show();
        $this->assign('list', $userList);
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->assign('status', $status);
        $this->assign('empty', '<tr><td colspan="4">无数据</td></span>');
        $this->display();
    }
    
    // 用户添加
    function userAdd() {
        if (! $this->_hasStandard() && ! _hasIss()) {
            $this->_handleCheckAuth("只能付费版才能使用此功能");
        }
        if (! $this->checkUserLevel()) {
            // $this->error('您不是管理员没有该操作权限');
        }
        if ($this->isPost()) {
            $user_name = I('post.user_name', null, 'mysql_escape_string');
            $true_name = I('true_name', null, 'mysql_escape_string');
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
            $pws1 = I('post.pws1');
            if (! check_str($pws1, array(
                'null' => false), $error)) {
                $this->error("密码{$error}");
            }
            $pws2 = I('post.pws2');
            if (! check_str($pws2, array(
                'null' => false), $error)) {
                $this->error("密码{$error}");
            }
            if ($pws1 != $pws2)
                $this->error('两次密码不一致');
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
            if ($reqResult['UserAdd']['Status']['StatusCode'] != '0000')
                $this->error(
                    "用户新增失败：" . $reqResult['UserAdd']['Status']['StatusText']);
                
                // 数据添加
            $data = array(
                'node_id' => $this->nodeId, 
                'user_id' => $reqResult['UserAdd']['Info']['UserId'], 
                'new_role_id' => $roleId, 
                'user_name' => $user_name, 
                'true_name' => $true_name, 
                'add_time' => date('YmdHis'));
            $result = M('tuser_info')->add($data);
            if (! $result)
                $this->error('系统出错，添加失败');
            $this->success('用户添加成功');
            exit();
        }
        
        user_act_log('新增用户', '', 
            array(
                'act_code' => '3.5.2.3'));
        // 获取当前机构可用角色
        $roleData = $this->_getRoleList();
        $this->assign('roleData', $roleData);
        $this->display();
    }
    
    // 用户修改
    public function userEdit() {
        if (! $this->_hasStandard() && ! _hasIss()) {
            $this->_handleCheckAuth("只能付费版才能使用此功能");
        }
        if (! $this->checkUserLevel()) {
            // $this->error('您不是管理员没有该操作权限');
        }
        $userId = I('user_id', null, 'mysql_escape_string');
        if (! check_str($userId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $userInfo = M('tuser_info')->where(
            "node_id='{$this->nodeId}' AND user_id='{$userId}'")->find();
        if (! $userInfo) {
            $this->error('未找到该用户信息' . M()->_sql());
        }
        
        if ($userInfo['new_role_id'] == '2' || $userInfo['new_role_id'] == '1') {
            $this->error('超级管理员不允许被编辑');
        }
        
        if ($this->isPost()) {
            $roleId = I('post.role_id');
            if (! check_str($roleId, 
                array(
                    'null' => false), $error)) {
                $this->error("用户角色{$error}");
            }
            if ($roleId <= 2)
                $this->error('特殊角色不允许编辑');
                
                // 如果是自已，则不能修改角色
            if ($userInfo['user_id'] == $this->user_id) {
                $roleId = $userInfo['new_role_id'];
            }
            
            $roleCheck = $this->_checkRuleId($roleId);
            if (! $roleCheck) {
                $this->error("该角色不存在");
            }
            
            $true_name = I('true_name', '', 'mysql_real_escape_string');
            
            // 数据更新
            $where = array(
                'node_id' => $this->nodeId, 
                'user_id' => $userId);
            $result = M('tuser_info')->where($where)->save(
                array(
                    'new_role_id' => $roleId, 
                    'true_name' => $true_name));
            if ($result === false) {
                $this->error('系统出错，更新失败');
            }
            // 更新密码
            $password = I('post.pws1');
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
            $this->success('用户更新成功');
            exit();
        }
        // 获取角色信息
        $nodeArr = explode(',', $this->nodePath);
        // 获取当前机构可用角色
        $roleData = $this->_getRoleList();
        $this->assign('roleData', $roleData);
        user_act_log('编辑用户', '', 
            array(
                'act_code' => '3.5.2.4'));
        $this->assign('info', $userInfo);
        $this->display('userAdd');
    }
    
    // 用户详情
    public function userDetail() {
        $userId = I('user_id', null, 'mysql_escape_string');
        if (! check_str($userId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $userInfo = M('tuser_info')->where(
            "node_id='{$this->nodeId}' AND user_id='{$userId}'")->find();
        if (! $userInfo) {
            $this->error('未找到该用户信息');
        }
        $roleList = $this->_getNodePower($this->node_id, 
            $userInfo['new_role_id']);
        $roleInfo = M('tauth_user_role')->where(
            array(
                'id' => $userInfo['new_role_id']))->find();
        if ($this->_checkRoleLevel($userInfo['new_role_id'])) {
            $roleName = "机构管理员";
        } else {
            $roleName = $roleInfo['title'];
        }
        
        // dump($roleList);exit;
        $this->assign('userInfo', $userInfo);
        $this->assign('roleName', $roleName);
        $this->assign('roleList', $roleList);
        $this->assign('empty', '该用户拥有所有操作权限');
        $this->display();
    }
    
    // 用户状态改变
    public function userStatus() {
        user_act_log('停用用户', '', 
            array(
                'act_code' => '3.5.2.5'));
        if (! $this->checkUserLevel())
            $this->error('您不是管理员没有该操作权限');
        $userId = I('user_id', null, 'mysql_escape_string');
        if (! check_str($userId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $userInfo = M('tuser_info')->where(
            "node_id='{$this->nodeId}' AND user_id='{$userId}' AND new_role_id<>1")->find();
        if (! $userInfo) {
            $this->error('未找到该用户信息');
        }
        $status = I('status', null);
        // 接口请求
        $req_array = array(
            "UserStatusReq" => array(
                'UserId' => $userId, 
                'Status' => $status == 1 ? 1 : 0));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $reqResult = $RemoteRequest->requestSsoUserStatus($req_array);
        $ret_msg = $reqResult['UserStatusRes']['Status'];
        if (! $reqResult || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error("更新失败:{$ret_msg['StatusText']}");
        }
        
        $result = M('tuser_info')->where("user_id='{$userId}'")->save(
            array(
                'status' => $status == 1 ? 1 : 0));
        if ($result === false) {
            $this->error('更新失败');
        }
        $this->success('更新成功');
    }
    
    // 重置密码
    public function resetPws() {
        user_act_log('重置用户密码', '', 
            array(
                'act_code' => '3.5.2.6'));
        if (! $this->checkUserLevel())
            $this->error('您不是管理员没有该操作权限');
        $userId = I('user_id', null, 'mysql_escape_string');
        if (! check_str($userId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $userInfo = M('tuser_info')->where(
            "node_id='{$this->nodeId}' AND user_id='{$userId}' AND new_role_id<>1")->find();
        if (! $userInfo) {
            $this->error('未找到该用户信息');
        }
        R('Home/ForgetPwd/getPwd', 
            array(
                $userInfo['user_name']));
    }
    
    // 判断当前商户是否是顶级商户
    public function checkNodeLevel() {
        if (count(explode(',', $this->nodePath)) == 1) {
            return true;
        }
        return false;
    }
    // 判断当前用户是否是管理员
    public function _checkRoleLevel($roleId = null) {
        if (! $roleId) {
            if ($this->roleId === null) {
                $this->roleId = M('tuser_info')->where(
                    "user_id={$this->userId}")->getField('new_role_id') or
                     $this->roleId = '';
            }
            $roleId = $this->roleId;
        }
        if ($roleId == '2') {
            return true;
        }
        return false;
    }

    private function _getNodePower($node_id, $role_id = '') {
        $ids = array();
        // 获取模块权限及非标权限
        $nodePowers = M('tauth_node_power')->getFieldByNode_id($this->nodeId, 
            'powers');
        $fbPowers = trim($nodePowers, ',');
        $modulePowers = trim($this->nodeInfo['pay_module'], ',');
        
        // 获取机构权限，
        $role_alias = $this->wc_version;
        if (in_array($role_alias, 
            array(
                'v0', 
                'v0.5'))) {
            $PowersRet = M('tauth_node_role')->getFieldByAlias($role_alias, 
                'powers');
            $Powers = trim($PowersRet, ',');
            if ($Powers) {
                $ids = array_unique(explode(',', $Powers));
            }
        } elseif ($role_alias == 'v9') {
            $modulePowers .= ',v0.5'; // v9权限和v0.5一样，只是个付费标志而已
            $moduleRet = M('tauth_node_role')->field('powers')
                ->where(
                array(
                    'alias' => array(
                        'in', 
                        $modulePowers)))
                ->select();
            $idsTmp = '';
            if (! empty($moduleRet)) {
                foreach ($moduleRet as $k => $v) {
                    if (trim($v['powers'], ',')) {
                        $idsTmp .= ',' . trim($v['powers'], ',');
                    }
                }
            }
            $ids = array_unique(explode(',', trim($idsTmp, ',')));
        }
        if (! empty($fbPowers)) {
            $ids2 = explode(',', $fbPowers);
            $ids = array_merge($ids, $ids2);
            $ids = array_unique($ids);
        }
        // 获取非标特殊机构权限结束
        
        $node_powers = $ids;
        
        $listData = M()->field('*,p.grouppath powergroup_name')
            ->table('tauth_power p')
            ->where(
            array(
                'p.id' => array(
                    'in', 
                    $node_powers), 
                'p.level' => '1'))
            ->order('grouppath asc')
            ->select();
        if ($role_id) {
            // 获取权限信息
            $role_powers = M('tauth_user_role')->where(
                array(
                    'id' => $role_id))->getField('powers');
            $role_powers = explode(',', trim($role_powers, ','));
        } else {
            $role_powers = array();
        }
        // 提取权限分组
        $roleList = array();
        $isAdmin = $this->_checkRoleLevel($role_id);
        foreach ($listData as $k => $v) {
            if ($isAdmin || in_array($v['id'], $role_powers)) {
                $v['selected'] = 1;
            } else {
                $v['selected'] = 0;
            }
            $roleList[$v['powergroup_name']][] = $v;
        }
        return $roleList;
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
    // 得到权限列表
    protected function _getRoleList() {
        $arr = M('tauth_user_role')->where(
            array(
                'node_id' => array(
                    'in', 
                    array(
                        self::ADMIN_NODE_ID, 
                        $this->node_id)), 
                'status' => 1))
            ->order('node_id')
            ->select();
        return $arr;
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