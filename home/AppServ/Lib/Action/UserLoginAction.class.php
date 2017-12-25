<?php

/**
 * 功能：用户登录
 *
 * @author wangtr 时间：2013-06-21
 */
class UserLoginAction extends BaseAction {

    public $user_name;
    // 用户名
    public $user_pwd;
    // 密码
    public $user_id;
    // 用户号
    public $node_name;

    // 商户名
    public function _initialize() {
        parent::_initialize();
        $this->user_name = I('user_name'); // 用户名
        $this->user_pwd  = I('user_pwd'); // 密码
    }

    public function run() {
        $rs = $this->check();
        if ($rs !== true) {
            $this->returnError($rs);
        }

        // 判断是否第一次登陆
        $this->checkFirstLogin($this->node_id, $this->pos_id, $this->pos_serial, $this->user_name, $this->user_pwd);
        $userSess = D('UserSess', 'Service');
        // 请求EPOS登录
        $rs = $userSess->LoginWangCai($this->pos_id, $this->user_id, $this->user_name, $this->node_id,
                $this->pos_serial);
        if ($rs) {
            $session                  = $userSess->getSession();
            $resp_desc                = "用户登录成功";
            $user_info2['user_id']    = $session->getUserId();
            $user_info2['node_id']    = $session->getNodeId();
            $user_info2['node_name']  = $this->node_name;
            $user_info2['role_id']    = $session->getUserRole();
            $user_info2['session_id'] = $session->getSessionId();
            $this->returnSuccess($resp_desc, $user_info2);
        } else {
            $resp_desc = $userSess->getErrMsg();
            $this->returnError($resp_desc);
        }
    }

    private function check() {
        if (empty($this->node_id) || empty($this->pos_id) || empty($this->pos_serial)) {
            return "机身号[" . $this->pos_serial . "]未绑定";
        }
        if (empty($this->user_name) || empty($this->user_pwd)) {
            return "用户名密码不能为空";
        }

        return true;
    }

    private function checkFirstLogin(
            $node_id,
            $pos_id,
            $pos_serial,
            $user_name,
            $user_pwd
    ) {
        // 请求SSO用户登录验证
        $url = C('SSO_REQUEST_LOGIN_URL');
        /*
         * //测试参数 $node_id = '00000000'; $user_name = 'wangtr'; $user_pwd =
         * md5('12345678');
         */
        $result = $this->httpPost($url . '&node_id=' . $node_id . '&user_name=' . $user_name . '&password=' . $user_pwd,
                '', $error, array(
                        'METHOD' => 'GET',
                ));
        $rs     = json_decode($result);

        if ($rs->resp_id == '9999') {
            $resp_desc = $rs->resp_data->msg;
            $this->returnError($resp_desc);
        } else {
            // 获取SSO用户ID
            $this->user_id = $rs->resp_data->userId;
            // 获取SSO商户名
            $this->node_name = $rs->resp_data->nodeName;
        }
    }
}

?>