<?php

class FuWenAction extends BaseAction {

    const BATCH_TYPE_DAWAN = 49;
    // 活动类型，微信富文本
    public $_authAccessMap = '*';
    // 权限映射表
    
    // 活动类型
    public $BATCH_TYPE;

    public $BATCH_NAME;
    
    // 初始化
    public function _initialize() {
        $this->BATCH_TYPE = self::BATCH_TYPE_DAWAN;
        $this->BATCH_NAME = C('BATCH_TYPE_NAME.' . self::BATCH_TYPE_DAWAN);
        parent::_initialize();
        $this->assign('batch_type', $this->BATCH_TYPE);
        $this->assign('batch_name', $this->BATCH_NAME);
    }
    
    // 首页
    public function index() {
        // $model = M('tmarketing_info');
        /*
         * $map = array( 'node_id' => array('exp', "in (" . $this->nodeIn() .
         * ")"), 'batch_type' => $this->BATCH_TYPE );
         */
        $this->display(); // 输出模板
    }
}
