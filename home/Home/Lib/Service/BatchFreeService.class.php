<?php

/**
 * 限免活动设置 User: rongrong Date: 15-6-4 Time: 上午10:51
 */
class BatchFreeService {

    public $batch_type;

    public $node_id;

    public $freeInfo = array();

    public $traceInfo = array();

    public $error = '';

    public $now_time = '';

    public $wc_version = '';

    public function __construct() {
        $this->now_time = date('YmdHis');
    }
    // 初始化
    public function init($opt = array()) {
        $this->batch_type = $opt['batch_type'];
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->node_id = $userInfo['node_id'];
        $this->wc_version = get_wc_version($this->node_id);
        return $this;
    }
    // todo 获取限免信息
    public function getFreeInfo() {
        if ($this->wc_version != 'v0.5') {
            $this->error = '只有v0.5才能参加限免';
            return false;
        }
        if ($this->freeInfo) {
            return $this->freeInfo;
        }
        $this->freeInfo = M('tbatch_free_set')->where(
            array(
                'batch_type' => $this->batch_type))->find();
        if (empty($this->freeInfo)) {
            return array();
        }
        return $this->freeInfo;
    }
    // todo
    public function getTraceInfo() {
        if ($this->traceInfo) {
            return $this->traceInfo;
        }
        $this->traceInfo = M('tbatch_free_trace')->where(
            array(
                'node_id' => $this->node_id))->find();
        if (empty($this->traceInfo)) {
            return array();
        }
        return $this->traceInfo;
    }
    
    // 判断是否允许限免
    public function checkFree() {
        $freeInfo = $this->getFreeInfo();
        if (! $freeInfo) {
            $this->error = '该活动不允许限免';
            return false;
        }
        // 判断有效期
        if ($freeInfo['begin_time'] > $this->now_time) {
            $this->error = '该活动未开始限免';
            return false;
        }
        // 判断有效期
        if ($freeInfo['end_time'] > $this->now_time) {
            $this->error = '该活动限免已结束';
            return false;
        }
        return true;
    }

    public function getError() {
        return $this->error;
    }

    public function addTrace() {
        $data = array(
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'batch_type' => $this->batch_type, 
            'memo' => '参与限免');
        M('tbatch_free_trace')->add($data);
    }

    public function checkFree2() {
    }
} 