<?php

/**
 * 功能：用户密码修改
 *
 * @author cxz 时间：2013-06-26
 */
class ChangeUserPwdAction extends BaseAction {

    public $node_id;
    // 商户号
    public $pos_id;
    // 终端号
    public $user_id;
    // 用户号

    // 以下是特殊请求参数
    public $old_password;
    // 原密码
    public $password;
    // 新密码
    public $user_name;

    // 用户名
    public function _initialize() {
        parent::_initialize();
        $this->checkLogin(); // 校验用户登录
        // 初始化请求参数
        $this->old_password = I('old_password');
        $this->password     = I('password');
        $this->user_name    = I('user_name');
    }

    public function run() {
        $rs = $this->check();
        if (!$rs) {
            $resp_desc = "请填写商户号、终端号、用户号、用户名、旧密码、新密码";
            $this->returnError($resp_desc);
        }

        if (strlen($_REQUEST["old_password"]) > 20 || strlen($_REQUEST["password"]) > 20) {
            $resp_desc = "新旧密码长度不能超过20字节";
            $this->returnError($resp_desc);
        }
        // 请求用户修改接口
        $sys_auth = "connecttosso";
        $sys_psw  = md5($sys_auth . md5($sys_auth));
        // 请求SSO用户密码修改
        $reqData     = array(
                'node_id'      => $this->node_id,
                'name'         => $this->user_name,
                'password'     => $this->password,
                'password_old' => $this->old_password,
                'sys_auth'     => $sys_auth,
                'sys_psw'      => $sys_psw,
                'type'         => 3,
        );
        $requestServ = D('RemoteRequest', 'Service');
        $rsp_arr     = $requestServ->requestSsoUser($reqData);

        if ($rsp_arr['resp_id'] != '0000') {
            $resp_desc = $rsp_arr['resp_msg'];
            $this->returnError($resp_desc);
        } else {
            $resp_desc = "修改密码成功";
            $this->returnSuccess($resp_desc);
        }
    }

    private function check() {
        if (empty($this->node_id) || empty($this->pos_id) || empty($this->user_id) || $this->user_name == '' || $this->old_password == '' || $this->password == '') {
            return false;
        }

        return true;
    }
}

?>