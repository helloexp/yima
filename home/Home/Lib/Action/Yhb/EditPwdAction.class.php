<?php

class EditPwdAction extends YhbAction {

    public $_authAccessMap = '*';

    public function index() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $user_name = $userInfo['user_name'];
        $this->assign("user_name", $user_name);
        
        user_act_log('修改密码', '', 
            array(
                'act_code' => '3.5.1.2'));
        // 修改密码
        $this->display();
    }

    public function Edit() { // 查询是否存在
        $email = trim(I('post.email'));
        
        $old_password = trim(I('post.old_password'));
        if ($old_password == "") {
            $this->error("原密码不能为空！");
        }
        $new_password1 = trim(I('post.new_password1'));
        $new_password2 = trim(I('post.new_password2'));
        if ($new_password1 != $new_password2) {
            $this->error("两次新密码不一致!");
        }
        
        // 查询nodeid
        $total_count = M('tuser_info')->where("user_name='" . $email . "'")->count();
        if ($total_count > 1) {
            $this->error("无法修改密码,用户名存在重复!");
        }
        $nodeInfo = M('tuser_info')->where("user_name='" . $email . "'")->find();
        
        $req_array = array(
            "node_id" => $nodeInfo['node_id'], 
            "email" => $email, 
            "new_password1" => $new_password1, 
            "old_password" => $old_password);
        $RemoteRequest = D('UserSess', 'Service');
        $reqResult = $RemoteRequest->EditPwd($req_array);
        
        if ($reqResult->resp_id != '0000') {
            $this->error("密码修改失败！错误原因：" . $reqResult->resp_msg);
        } else {
            node_log("密码修改");
            $this->success("密码修改成功!下次登录请使用新密码。", 
                array(
                    '返回' => U('index')));
        }
        exit();
    }
}
