<?php

class AccountAuthAction extends BaseAction {

    public function __construct() {
        parent::__construct();
        // 引入初始化列表
    }

    public function index() {
        // 获知当前机构的旺财版本
        if (! in_array($this->wc_version, 
            array(
                'v0', 
                'v0.5', 
                'v4', 
                'v9'))) {
            $this->error('旺财版本出错，请联系客服！');
        }
        if ($this->node_type_name == 'c0') {
            $this->assign('version', '注册用户');
        } elseif ($this->node_type_name == 'c1') {
            $this->assign('version', '认证用户');
        } else {
            $this->assign('version', '付费用户');
        }
        $this->assign('hasMarketTool', $this->hasPayModule('m1'));
        $this->assign('hasO2O', $this->hasPayModule('m2'));
        $this->assign('hasAlipay', $this->nodeInfo['sale_flag']);
        $this->assign('hasWfx', $this->hasPayModule('m3'));
        $this->assign('hasWjf', $this->hasPayModule('m4'));
        $this->display();
    }
}
?>