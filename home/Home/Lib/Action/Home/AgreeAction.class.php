<?php

class AgreeAction extends Action {

    public function _initialize() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->assign("userInfo", $userInfo);
    }

    public function index() {
        $this->display();
    }

    public function alipay() {
        $this->display();
    }
}