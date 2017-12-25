<?php 
/**
 * 多乐互动案例库
 */
class CaseAction extends MarketBaseAction{

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














