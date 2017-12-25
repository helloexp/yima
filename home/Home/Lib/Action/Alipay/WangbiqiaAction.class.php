<?php

/**
 * 支付宝
 */
class TestAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        $service = D('Rechargewb', 'Service');
        echo $this->node_id . '88';
        dump($service->Recharge(50, $this->node_id));
    }
}