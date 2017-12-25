<?php

class LandAction extends Action {

    /**
     *
     * @var LoginService
     */
    private $LoginService;

    /**
     * 落地页系统登录/注册
     */
    public function index() {
        $lanme = I('landname', '');
        $tgid = I('tg_id', '');
        $fromuid = I('from_user_id', '');
        $regLogin = I('reg_login', '');
        if ($lanme != "") {
            $this->assign('landname', $lanme);
            $this->assign('tg_id', $tgid);
            $this->assign('fromuid', $fromuid);
            $this->assign('reg_login', $regLogin);
        }
        $this->display();
    }

    public function success() {
        $lanme = I('landname', '');
        $tgid = I('tg_id', '');
        
        $autologininfo = session('autologininfo');
        if ($autologininfo) {
            $this->LoginService = D('Login', 'Service');
            $this->LoginService->ssoLogin($autologininfo);
        }
        
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->assign("userInfo", $userInfo);
        $_s = session('autologininfo');
        if (empty($_s)) {
            $this->assign("autologininfo", true);
        } else {
            $this->assign("autologininfo", $_s);
        }
        $this->assign('landname', $lanme);
        $this->assign('tg_id', $tgid);
        $this->display();
        if ($lanme == 'wangfenxiao') {
            $this->redirect('Wfx/Index/index', 
                array(
                    'auto_apply' => '1'), 2);
        }
    }

    public function indexnew() {
        $lanme = I('landname', '');
        $tgid = I('tg_id', '');
        $fromuid = I('from_user_id', '');
        $regLogin = I('reg_login', '');
        if ($lanme != "") {
            $this->assign('landname', $lanme);
            $this->assign('tg_id', $tgid);
            $this->assign('fromuid', $fromuid);
            $this->assign('reg_login', $regLogin);
        }
        $this->display();
    }

    public function regwfx() {
        $zqgqtype=I('request.zqgqtype','');
        $lanme = I('landname', '');
        $tgid = I('tg_id', '');
        $fromuid = I('from_user_id', '');
        $regLogin = I('reg_login', '');
        if ($lanme != "") {
            $this->assign('landname', $lanme);
            $this->assign('tg_id', $tgid);
            $this->assign('fromuid', $fromuid);
            $this->assign('reg_login', $regLogin);
        }
        if($zqgqtype!=''){
            $this->assign('zqgqtype', $zqgqtype);
        }
        $this->display();
    }

    public function regdmsd() {
        $lanme = I('landname', '');
        $tgid = I('tg_id', '');
        $fromuid = I('from_user_id', '');
        $regLogin = I('reg_login', '');
        if ($lanme != "") {
            $this->assign('landname', $lanme);
            $this->assign('tg_id', $tgid);
            $this->assign('fromuid', $fromuid);
            $this->assign('reg_login', $regLogin);
        }
        $this->display();
    }
}