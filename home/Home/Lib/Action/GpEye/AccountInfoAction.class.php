<?php

/**
 * @功能：北京光平 @更新时间: 2015/02/04 15:50
 */
class AccountInfoAction extends GpBaseAction
{
    public $_authAccessMap = '*';
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        $userInfo = M('tuser_info')->where(
            "node_id='{$this->nodeId}' AND user_id='{$this->user_id}'")->find();
        if (!$userInfo) {
            $this->error('未找到该用户信息'.M()->_sql());
        }
        if ($userInfo['new_role_id'] == '2' || $userInfo['new_role_id'] == '1') {
            $this->error('超级管理员不允许被编辑');
        }
        $map = array('status' => 0);
        $list = D('GpMerchant')->getOptionList($map);
        $technician_list = D('GpTechnician')->getOptionList($map);
        $this->assign('list', $list);
        $this->assign('technician_list', $technician_list);
        $this->assign('role_list', C('GpEye.roles'));
        $this->assign('info', $userInfo);
        $this->display('AccountInfo/index');
    }
    public function edit_user_save()
    {
        if ($this->isPost()) {
            $id = I('id');
            // 更新密码
            $pws1 = I('post.user_password1');
            if (!check_str($pws1, array(
                'null' => false, ), $error)) {
                $this->error("密码{$error}");
            }
            $pws2 = I('post.user_password2');
            if (!check_str($pws2, array(
                'null' => false, ), $error)) {
                $this->error("密码{$error}");
            }
            if ($pws1 != $pws2) {
                $this->error('两次密码不一致');
            }
            $password = I('post.user_password1');
            if ($password != '') {
                // 更新密码
                $result = $this->_modifySsoPwd(array('user_name' => $this->user_name, 'password' => md5($password)));
                $result = $result['Status'];
                if ($result['StatusCode'] != '0000') {
                    $this->error($result['StatusText']);
                }
            }
            $this->success('用户更新成功');
            exit();
        }
    }
    // 修改密码
    protected function _modifySsoPwd($arr)
    {
        // 请求用户修改接口
        // 请求SSO用户密码修改
        $reqData = array(
            'ResetChildPassword' => array(
                'AppId' => C('SSO_SYSID'),
                'Token' => $this->userInfo['token'],
                'UserName' => $arr['user_name'],
                'Password' => $arr['password'], ), );
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
