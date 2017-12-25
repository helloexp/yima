<?php

/**
 * 临时公用的机构登录
 * @author lwb
 * 20160421
 */
class TempLoginAction extends Action {

    public function index() {
        session('tempNodeType',I('get.tempNodeType'));
        $tempLogin = D('TempLogin', 'Service');
        if (!D('UserSess', 'Service')->isLogin()) {
            // 如果没有登录，则生成一个登录session
            $tempLogin->makeUserSessInfo();
        }
        // 跳转到相关链接
        $tempLogin->redirectUrlByUser();
    }
}
