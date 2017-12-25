<?php

class AnswersAction extends Action {

    // 活动类型
    public $BATCH_TYPE = '10';

    /**
     * @var AnswersService
     */
    protected $AnswersService;

    public function _initialize() {
        $this->AnswersService = D('Answers', 'Service');
    }

    public function calcInfoData()
    {
        $key = I('key');
        $batchId = I('batch_id');
        $this->AnswersService->calcInfoData($key, $batchId);
    }
}








