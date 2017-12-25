<?php

/*
 * 机构用户角色管理
 */
class NodeUserRoleAction extends BaseAction {

    private $roleId = null;

    public $_authAccessMap = '*';

    const ADMIN_NODE_ID = '00000000';
    // 管理机构
    const ADMIN_USER_NAME = 'admin';
    // 管理账号
    public function index() {
        // 判断是否是顶级商户
        if (! $this->checkNodeLevel()) {
            $this->error('您没有该操作权限');
        }
        if (! $this->_checkUserLevel())
            $this->error('您不是管理员没有该操作权限');
        $map = array();
        import("ORG.Util.Page");
        $count = M('tauth_user_role')->where($map)->count();
        $p = new Page($count, 10);
        // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $roleList = M()->table('tauth_user_role a')
            ->field('a.*,b.node_name')
            ->join('left join tnode_info b on b.node_id=a.node_id')
            ->where($map)
            ->order('a.node_id asc,a.id asc')
            ->limit($p->firstRow . ',' . $p->listRows)
            ->select();
        $page = $p->show();
        $this->assign('list', $roleList);
        $this->assign('post', $_REQUEST);
        $this->assign("page", $page);
        $this->display();
    }
    // 角色添加
    function add() {
        // 判断是否是顶级商户
        if (! $this->checkNodeLevel()) {
            $this->error('您没有该操作权限');
        }
        if (! $this->_checkUserLevel())
            $this->error('您不是管理员没有该操作权限');
        if ($this->isPost()) {
            $name = I('post.name');
            $memo = I('post.memo');
            $node_id = I('post.node_id');
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '32'), $error)) {
                $this->error("角色名称{$error}");
            }
            $roleData = I('post.power_id');
            if (! is_array($roleData) || empty($roleData)) {
                $this->error('请选择可用的权限');
            }
            // 角色名重复
            $count = M('tauth_user_role')->where("title='{$name}'")->count();
            if ($count > 0)
                $this->error('角色名重复');
            M()->startTrans();
            $powers = ',' . implode(',', $roleData) . ',';
            // 角色数据添加
            $data = array(
                'node_id' => $node_id, 
                'title' => $name, 
                'memo' => $memo, 
                'status' => 1, 
                'powers' => $powers, 
                'add_time' => date('YmdHis'));
            $role_id = M('tauth_user_role')->add($data);
            if (! $role_id) {
                M()->rollback();
                $this->error('系统出错,添加失败');
            }
            M()->commit();
            $this->success('角色添加成功');
            exit();
        }
        $roleList = $this->_getNodePower($this->node_id);
        user_act_log('新增角色', '', 
            array(
                'act_code' => '3.5.2.1'));
        // dump($roleList);exit;
        $this->assign('roleList', $roleList);
        $this->assign('roleInfo', array());
        $this->display();
    }
    
    // 角色编辑
    public function edit() {
        // 判断是否是顶级商户
        if (! $this->checkNodeLevel()) {
            $this->error('您没有该操作权限');
        }
        if (! $this->_checkUserLevel()) {
            $this->error('您不是管理员没有该操作权限');
        }
        $roleId = I('role_id', null, 'mysql_escape_string');
        if (! check_str($roleId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $roleInfo = M('tauth_user_role')->where(
            array(
                'id' => $roleId))->find();
        if (! $roleInfo)
            $this->error('未找到该角色信息');
        if ($this->isPost()) {
            $name = I('post.name');
            $memo = I('post.memo');
            $node_id = I('post.node_id');
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
            $count = M('tauth_user_role')->where(
                "title='{$name}' AND id<>{$roleId}")->count();
            if ($count > 0)
                $this->error('角色名重复');
            $powers = ',' . implode(',', $roleData) . ',';
            M()->startTrans();
            // 角色数据更新
            $data = array(
                'title' => $name, 
                'memo' => $memo, 
                'powers' => $powers, 
                'node_id' => $node_id);
            $result = M('tauth_user_role')->where(
                array(
                    'id' => $roleInfo['id']))->save($data);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,更新失败');
            }
            M()->commit();
            $this->success('角色更新成功');
            exit();
        }
        // 获取所有权限信息
        $roleList = $this->_getNodePower($this->nodeId, $roleId);
        user_act_log('编辑角色', '', 
            array(
                'act_code' => '3.5.2.2'));
        // dump($powerList);exit;
        $this->assign('roleList', $roleList);
        $this->assign('roleInfo', $roleInfo);
        $this->display('add');
    }
    
    // 角色详情
    public function detail() {
        
        // 判断是否是顶级商户
        if (! $this->checkNodeLevel()) {
            $this->error('您没有该操作权限');
        }
        $roleId = I('role_id', null, 'mysql_escape_string');
        if (! check_str($roleId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $roleInfo = M('tauth_user_role')->where(
            array(
                'node_id' => $this->nodeId, 
                'id' => $roleId))->find();
        $roleList = $this->_getNodePower($this->node_id, $roleId);
        // dump($roleList);exit;
        $this->assign('roleInfo', $roleInfo);
        $this->assign('roleList', $roleList);
        $this->display();
    }
    
    // 判断当前用户是否是管理员
    public function _checkUserLevel() {
        $userInfo = $this->userInfo;
        if ($userInfo['user_name'] == self::ADMIN_USER_NAME) {
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
        foreach ($listData as $k => $v) {
            if (in_array($v['id'], $role_powers)) {
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
}