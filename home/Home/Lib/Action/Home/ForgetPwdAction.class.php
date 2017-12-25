<?php

class ForgetPwdAction extends Action {

    public function index() {
        // 修改密码
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->assign("userInfo", $userInfo);
        $this->display();
    }

    public function getPwd($userName = null) {
        $email = is_null($userName) ? I('post.email', null, 'trim') : $userName;
        $node_name = I('post.node_name', '');
        $type = '';
        if (check_str($email, 
            array(
                'null' => false, 
                'strtype' => 'email'), $error)) {
            $type = 'email';
        } elseif (check_str($email, 
            array(
                'null' => false, 
                'strtype' => 'number'), $error)) {
            $type = 'mobile';
        }
        if (! $type) {
            $this->error("请输入正确邮箱或者手机号");
        }
        // 如果是手机号，但未填写机构号
        if ($type == 'mobile' && ! $node_name) {
            $this->error("注册的企业名称不能为空");
        }
        // 根据手机号和机构号查到邮箱
        if ($type == 'mobile') {
            $email = M('tnode_info')->where(
                array(
                    'contact_phone' => $email,  // email就是手机号了
                    'node_name' => $node_name))->getField('contact_eml') or
                 $this->error("注册的企业名称和手机号码不一致");
        }
        // elseif ($type == 'email') {
        // $email = M('tnode_info')->where(array(
        // 'contact_eml'=>$email
        // ))->getField('contact_eml');
        // }
        // if (!$email) {
        // $jumpUrl = array('返回'=>'javascript:history.go(-1)');
        // $this->assign('jumpUrlList',$jumpUrl);
        // $this->error('不存在该用户',$jumpUrl);
        // }//不能这么改,因为可能tnode_info表里面会没有
        if ($type == 'email') {
            $req_array = array(
                "ResetPassword" => array(
                    'AppId' => C('SSO_SYSID'), 
                    'UserName' => $email, 
                    'ResetType' => 1));
        } else {
            $req_array = array(
                "ResetPassword" => array(
                    'AppId' => C('SSO_SYSID'), 
                    'UserName' => $email, 
                    'NodeName' => $node_name, 
                    'ResetType' => 2));
        }
        $RemoteRequest = D('RemoteRequest', 'Service');
        $reqResult = $RemoteRequest->ssoResetPwd($req_array);
        if ($reqResult['ResetPassword']['Status']['StatusCode'] != '0000') {
            $jumpUrl = array(
                '返回' => 'javascript:history.go(-1)');
            $this->assign('jumpUrlList', $jumpUrl);
            if (! $reqResult['ResetPassword']['Status']['StatusCode']) {
                $this->error($email . "，该邮件地址错误，无法发送密码邮件", $jumpUrl);
            }
            $this->error(
                "密码重置失败！错误码：" .
                     $reqResult['ResetPassword']['Status']['StatusCode'] .
                     "，错误原因：" .
                     $reqResult['ResetPassword']['Status']['StatusText'], 
                    $jumpUrl);
        } else {
            $jumpUrl = array(
                '返回登录' => U('Home/Login/showLogin'));
            $this->assign('jumpUrlList', $jumpUrl);
            if ($type == 'email') {
                $oktype = array(
                    'judge' => 'email');
                $this->success("密码重置成功!新密码已发送至注册邮箱。", $oktype);
            } else {
                $oktype = array(
                    'judge' => 'mobile');
                $this->success("密码重置成功!新密码已发送至注册手机。", $oktype);
            }
        }
        exit();
    }

    public function ajaxAss() {
        $judge = I('msgJudge', '');
        $jumpUrl = array(
            '返回登录' => U('Home/Login/showLogin'));
        $this->assign('jumpUrlList', $jumpUrl);
        $msg = "";
        if ($judge == "email") {
            $msg = "密码重置成功!新密码已发送至注册邮箱。";
        } else {
            $msg = "密码重置成功!新密码已发送至注册手机。";
        }
        $this->success($msg, $jumpUrl);
    }
}