<?php

class LaborDayAction extends Action {
    // 推广页
    public function index() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        if ($userInfo == '') {
            $this->assign('LaborDay_url', 'index.php?g=Home&m=Reg&a=index');
            $this->assign('LaborDay_text_flag', '马上注册使用');
        } else {
            // 判断是否企业认证
            $model = M('tnode_info');
            $status = $model->where(
                array(
                    'node_id' => $userInfo['node_id']))->getField('check_status');
            if ($status != 2) {
                $this->assign('LaborDay_url', 
                    "index.php?g=Home&m=AccountInfo&a=index");
                $this->assign('LaborDay_text_flag', '马上认证');
            } else {
                $this->assign('LaborDay_url', 
                    'index.php?g=LabelAdmin&m=LaborDay&a=add');
                $this->assign('LaborDay_text_flag', '马上创建活动');
            }
        }
        $this->display();
    }
}