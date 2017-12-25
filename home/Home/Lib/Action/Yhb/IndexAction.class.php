<?php

// 翼惠宝首页
class IndexAction extends YhbAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        $this->display();
    }
}
