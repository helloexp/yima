<?php 
/**
 * 微信互动工具
 */
class WeChatAction extends MarketBaseAction{

	public function _initialize() {
        parent::_initialize();
    }
    /**
     * [beforeCheckAuth 提前校验权限]
     */
    public function beforeCheckAuth(){
        // 跳过系统权限校验
        $this->_authAccessMap = '*';
    }

    public function index(){
        $this->display();
    }
}













