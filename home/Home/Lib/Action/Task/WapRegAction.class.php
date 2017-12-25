<?php
// 注册任务
class WapRegAction extends BaseAction {

    public $taskName = 'WapRegTask';

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
    }
    
    // 任务弹窗内容
    public function index() {
        $task = D('Task', 'Service')->getTask($this->taskName);
        if (! $task) {
            $this->error("任务已失效");
        }
        $giftInfo = $task->getGiftInfo();
        // 查询是否过期
        $result = $task->checkValid();
        $this->assign('giftInfo', $giftInfo);
        $this->assign('isValid', $result);
        $this->display();
    }
    // 结束任务
    public function finish() {
        $node_id = $this->node_id;
        $task = D('Task', 'Service')->getTask($this->taskName);
        if (! $task) {
            $this->error("任务已失效");
        }
        $result = $task->finish();
        if (! $result || $result['code']) {
            log_write('任务完成失败' . $this->taskName);
            $this->error("领取失败");
        }
        $this->success("成功");
    }
}