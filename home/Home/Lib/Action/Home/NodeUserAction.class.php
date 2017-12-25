<?php

/*
 * 机构用户角色权限管理
 */
class NodeUserAction extends BaseAction {
    
    // 用户管理
    public function userInfo() {
        $name = I('name', null, 'mysql_real_escape_string,trim');
        if (isset($name) && $name != '') {
            $map['u.user_name'] = array(
                'like', 
                "%{$name}%");
        }
        $map['u.node_id'] = $this->nodeId;
        $map['u.role_id'] = array(
            'gt', 
            2);
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
            ->field('u.*,r.role_name')
            ->join('trole_info r ON u.role_id=r.role_id')
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
    function addUser() {
        if (! $this->checkUserLevel())
            $this->error('您不是管理员没有该操作权限');
        if ($this->isPost()) {
            $name = I('post.name', null, 'mysql_escape_string');
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'strtype' => 'email'), $error)) {
                $this->error("角色名称{$error}");
            }
            $count = M('tuser_info')->where("user_name='{$name}'")->count();
            if ($count > 0)
                $this->error('该用户名邮箱已被占用');
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
            if ($roleId < 3)
                $this->error('用户角色信息有误');
                
                // 新增小妹用户sso接口请求
            $reqArray = array(
                'UserAdd' => array(
                    'AppId' => C('SSO_SYSID'), 
                    'NodeId' => $this->nodeId, 
                    'Name' => $name, 
                    'UserName' => $name, 
                    'Password' => $pws1, 
                    'Email' => $name, 
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
                'role_id' => $roleId, 
                'user_name' => $name, 
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
        // 获取角色信息,所有角色信息都在顶级商户下面
        $nodeArr = explode(',', $this->nodePath);
        $roleData = M('trole_info')->where("node_id={$nodeArr[0]}")->select();
        $this->assign('roleData', $roleData);
        $this->display();
    }
    
    // 用户修改
    public function userEdit() {
        if (! $this->checkUserLevel())
            $this->error('您不是管理员没有该操作权限');
        $userId = I('user_id', null, 'mysql_escape_string');
        if (! check_str($userId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $userInfo = M('tuser_info')->where(
            "node_id='{$this->nodeId}' AND user_id='{$userId}' AND role_id<>1")->find();
        if (! $userInfo)
            $this->error('未找到该用户信息');
        if ($this->isPost()) {
            $roleId = I('post.role_id');
            if (! check_str($roleId, 
                array(
                    'null' => false), $error)) {
                $this->error("用户角色{$error}");
            }
            if ($roleId < 3)
                $this->error('用户角色信息有误');
                
                // 数据更新
            $data = array(
                'node_id' => $this->nodeId, 
                'user_id' => $reqResult->userId, 
                'role_id' => $roleId);
            $result = M('tuser_info')->where("user_id='{$userId}'")->save(
                array(
                    'role_id' => $roleId));
            if ($result === false)
                $this->error('系统出错，更新失败');
            $this->success('用户更新成功');
            exit();
        }
        // 获取角色信息
        $nodeArr = explode(',', $this->nodePath);
        $roleData = M('trole_info')->where("node_id={$nodeArr[0]}")->select();
        
        user_act_log('编辑用户', '', 
            array(
                'act_code' => '3.5.2.4'));
        $this->assign('roleData', $roleData);
        $this->assign('userInfo', $userInfo);
        $this->display();
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
        if ($userInfo['role_id'] != 1) {
            $roleData = M()->field(
                'r.role_name,r.add_time,p.power_name,g.id,g.powergroup_name')
                ->table('trole_power rp')
                ->join('tpower_info p ON rp.power_id=p.power_id')
                ->join('trole_info r ON rp.role_id=r.role_id')
                ->join('tweb_power_group g ON p.powergroup_id=g.id')
                ->where(
                "r.role_id={$userInfo['role_id']} AND p.type=2 AND p.status=0")
                ->order('g.sort_number,p.sort_number')
                ->select();
            // 提取权限分组
            $roleList = array();
            foreach ($roleData as $k => $v) {
                $roleList[$v['id']][] = $v;
                $roleName = $v['role_name'];
                $addTime = dateformat($v['add_time'], 'Y-m-d');
            }
        } else {
            $roleList = array();
            $roleName = '系统管理员';
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
            "node_id='{$this->nodeId}' AND user_id='{$userId}' AND role_id<>1")->find();
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
        
        $result = M('tuser_info')->where("user_id={$userId}")->save(
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
            "node_id='{$this->nodeId}' AND user_id='{$userId}' AND role_id<>1")->find();
        if (! $userInfo) {
            $this->error('未找到该用户信息');
        }
        R('Home/ForgetPwd/getPwd', 
            array(
                $userInfo['user_name']));
    }

    public function roleInfo() {
        
        // 判断是否是顶级商户
        if (! $this->checkNodeLevel()) {
            $this->error('您没有该操作权限');
        }
        if (! $this->checkUserLevel())
            $this->error('您不是管理员没有该操作权限');
        $map['node_id'] = $this->nodeId;
        import("ORG.Util.Page");
        $count = M('trole_info')->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $roleList = M('trole_info')->where($map)
            ->order('add_time DESC')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        
        $page = $p->show();
        $this->assign('list', $roleList);
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->display();
    }
    // 角色添加
    function addRole() {
        
        // 判断是否是顶级商户
        if (! $this->checkNodeLevel()) {
            $this->error('您没有该操作权限');
        }
        if (! $this->checkUserLevel())
            $this->error('您不是管理员没有该操作权限');
        if ($this->isPost()) {
            $name = I('post.name');
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '32'), $error)) {
                $this->error("角色名称{$error}");
            }
            $roleData = I('post.power_id');
            if (! is_array($roleData) || empty($roleData)) {
                $this->error('请选择要禁用的角色权限');
            }
            // 角色名重复
            $count = M('trole_info')->where("role_name='{$name}'")->count();
            if ($count > 0)
                $this->error('角色名重复');
            M()->startTrans();
            // 角色数据添加
            $data = array(
                'node_id' => $this->nodeId, 
                'role_name' => $name, 
                'add_time' => date('YmdHis'));
            $role_id = M('trole_info')->add($data);
            if (! $role_id) {
                M()->rollback();
                $this->error('系统出错,添加失败');
            }
            // 角色权限关系添加
            foreach ($roleData as $v) {
                $data = array(
                    'role_id' => $role_id, 
                    'power_id' => $v);
                $result = M('trole_power')->add($data);
                if (! $result) {
                    M()->rollback();
                    $this->error('系统出错,添加失败');
                }
            }
            M()->commit();
            $this->success('角色添加成功');
            exit();
        }
        // 获取权限信息
        $listData = M()->field('p.power_id,p.power_name,g.id,g.powergroup_name')
            ->table('tpower_info p')
            ->join('tweb_power_group g ON p.powergroup_id = g.id')
            ->where("p.type=2 AND p.status=0")
            ->order('g.sort_number,p.sort_number')
            ->select();
        // 提取权限分组
        $roleList = array();
        foreach ($listData as $k => $v) {
            $roleList[$v['id']][] = $v;
        }
        user_act_log('新增角色', '', 
            array(
                'act_code' => '3.5.2.1'));
        // dump($roleList);exit;
        $this->assign('roleList', $roleList);
        $this->display();
    }
    
    // 角色编辑
    public function roleEdit() {
        
        // 判断是否是顶级商户
        if (! $this->checkNodeLevel()) {
            $this->error('您没有该操作权限');
        }
        if (! $this->checkUserLevel())
            $this->error('您不是管理员没有该操作权限');
        $roleId = I('role_id', null, 'mysql_escape_string');
        if (! check_str($roleId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $roleInfo = M('trole_info')->where(
            "node_id='{$this->nodeId}' AND role_id='{$roleId}'")->find();
        if (! $roleInfo)
            $this->error('未找到该角色信息');
        if ($this->isPost()) {
            
            $name = I('post.name');
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '32'), $error)) {
                $this->error("角色名称{$error}");
            }
            $roleData = I('post.power_id');
            if (! is_array($roleData) || empty($roleData)) {
                $this->error('请选择要禁用的角色权限');
            }
            // 角色名重复
            $count = M('trole_info')->where(
                "role_name='{$name}' AND role_id<>'{$roleId}'")->count();
            if ($count > 0)
                $this->error('角色名重复');
            M()->startTrans();
            // 角色数据更新
            $data = array(
                'role_id' => $roleId, 
                'role_name' => $name);
            $result = M('trole_info')->save($data);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,更新失败');
            }
            // 删除旧的角色权限关系
            $result = M('trole_power')->where("role_id='{$roleId}'")->delete();
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,更新失败');
            }
            // 角色权限关系添加
            foreach ($roleData as $v) {
                $data = array(
                    'role_id' => $roleId, 
                    'power_id' => $v);
                $result = M('trole_power')->add($data);
                if (! $result) {
                    M()->rollback();
                    $this->error('系统出错,添加失败');
                }
            }
            M()->commit();
            $this->success('角色更新成功');
            exit();
        }
        // 获取所有权限信息
        $listData = M()->field('p.power_id,p.power_name,g.id,g.powergroup_name')
            ->table('tpower_info p')
            ->join('tweb_power_group g ON p.powergroup_id = g.id')
            ->where("p.type=2 AND p.status=0")
            ->order('g.sort_number,p.sort_number')
            ->select();
        // 提取权限分组
        $roleList = array();
        foreach ($listData as $k => $v) {
            $roleList[$v['id']][] = $v;
        }
        // 获取当前角色权限信息
        $powerData = M('trole_power')->field('power_id')
            ->where("role_id='{$roleId}'")
            ->select();
        $powerList = array();
        foreach ($powerData as $v) {
            $powerList[] = $v['power_id'];
        }
        user_act_log('编辑角色', '', 
            array(
                'act_code' => '3.5.2.2'));
        // dump($powerList);exit;
        $this->assign('roleList', $roleList);
        $this->assign('powerList', $powerList);
        $this->assign('roleInfo', $roleInfo);
        $this->display();
    }
    
    // 角色详情
    public function roleDetail() {
        
        // 判断是否是顶级商户
        if (! $this->checkNodeLevel()) {
            $this->error('您没有该操作权限');
        }
        $roleId = I('role_id', null, 'mysql_escape_string');
        if (! check_str($roleId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $roleData = M()->field(
            'r.role_name,r.add_time,p.power_name,g.id,g.powergroup_name')
            ->table('trole_power rp')
            ->join('tpower_info p ON rp.power_id=p.power_id')
            ->join('trole_info r ON rp.role_id=r.role_id')
            ->join('tweb_power_group g ON p.powergroup_id=g.id')
            ->where(
            "r.role_id='{$roleId}' AND r.node_id='{$this->nodeId}' AND p.type=2 AND p.status=0")
            ->order('g.sort_number,p.sort_number')
            ->select();
        // 提取权限分组
        $roleList = array();
        foreach ($roleData as $k => $v) {
            $roleList[$v['id']][] = $v;
            $roleName = $v['role_name'];
            $addTime = dateformat($v['add_time'], 'Y-m-d');
        }
        // dump($roleList);exit;
        $this->assign('roleName', $roleName);
        $this->assign('addTime', $addTime);
        $this->assign('roleList', $roleList);
        $this->display();
    }
    
    // 判断当前商户是否是顶级商户
    public function checkNodeLevel() {
        if (count(explode(',', $this->nodePath)) == 1) {
            return true;
        }
        return false;
    }
    // 判断当前用户是否是管理员
    public function checkUserLevel() {
        $roleId = M('tuser_info')->where("user_id='{$this->userId}'")->getField(
            'role_id');
        if ($roleId == 2) {
            return true;
        }
        return false;
    }
}