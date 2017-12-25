<?php

class Spring2015Action extends Action {

    public $_authAccessMap = '*';
    // 推广页
    public function index() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->assign('userInfo', $userInfo);
        $this->display('info');
    }
}