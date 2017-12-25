<?php

// 广西石油首页
class IntegralAuthAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
        $this->beforeCheckAuth();
    }
    // 标准校验营销活动
    protected function beforeCheckAuth() {
        $res = $this->_hasIntegral($this->node_id);
        if ($res === false) {
            redirect(U('Integral/IntegralIntroduce/index'));
        }
        return true;
    }
}
