<?php

class WapRegAction extends Action {

    public function index() {
        $cmId = I('GET.cmId'); // 客户经理编号
        if ($cmId && strlen($cmId) > 4) {
            $cmId = '';
        }
        
        // 特殊判断跳转
        if ($cmId == '6670') {
            redirect(
                'http://www.wangcaio2o.com/index.php?&g=Label&m=Bm&a=index&id=24563');
        }
        $this->assign('cmId', $cmId);
        $this->display();
    }
    
    // 注册提交
    public function regSubmit() {
        $data = array();
        $data['node_name'] = trim(I('post.node_name'));
        $data['node_short_name'] = trim(I('post.node_short_name'));
        $data['regemail'] = trim(I('post.regemail'));
        $data['contact_name'] = trim(I('post.contact_name'));
        $data['contact_phone'] = trim(I('post.contact_phone'));
        // $industry = trim(I('post.industry'));
        $data['province_code'] = trim(I('post.province_code'));
        $data['city_code'] = trim(I('post.city_code'));
        $data['client_manager'] = trim(I('post.client_manager'));
        $service = D('NodeReg', 'Service');
        $result = $service->nodeAdd($data);
        if ($result['status']) {
            $result['url'] = U('WapReg/success');
        }
        $this->ajaxReturn($result);
    }
}