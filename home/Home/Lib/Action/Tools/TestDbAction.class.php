<?php

/**
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> @date 2015/10/28 Class ToolsAction
 */
class TestDbAction extends BaseAction {

    /**
     *
     * @var TestDbModel
     */
    public $TestDbModel;

    public function _initialize() {
        if ((function_exists('is_production') && is_production()) ||
             (C('PRODUCTION_FLAG'))) { // 生产环境不能使用这个功能
            header("HTTP/1.1 404 Not Found");
            exit();
        }
        
        $this->TestDbModel = D('TestDb');
    }

    public function testTran() {
        $this->TestDbModel->testTran();
        
        $this->TestDbModel->testQuery();
        
        // $this->TestDbModel->testExec();
    }

    public function getGoodsId() {
        $goodsId = $this->TestDbModel->getGoodsId();
        echo $goodsId;
        exit();
    }
}