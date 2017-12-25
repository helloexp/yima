<?php

class FirstloginAction extends Action {

    public function index() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $nodeInfo = M('tnode_info')->field("client_id")
            ->where("node_id='" . $userInfo['node_id'] . "'")
            ->find();
        $this->assign("userInfo", $userInfo);
        $this->assign("nodeInfo", $nodeInfo);
        $this->display();
    }
}