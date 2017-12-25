<?php

/**
 * 任务初始化
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class TaskInitBehavior
 */
class TaskFinishBehavior extends TaskBaseBehavior {

    protected $forceFinish = false;

    public function _initialize(&$params) {
        parent::_initialize($params);
        $this->forceFinish = isset($params['forceFinish']) ? $params['forceFinish'] : false;
    }

    public function run(&$params) {
        $this->_initialize($params);
        if ($this->verifyTaskExpire()) {
            $orderNumber = isset($this->params['orderNumber']) ? $this->params['orderNumber'] : '';
            $userId = isset($this->params['user_id']) ? $this->params['user_id'] : '';
            if ($orderNumber) {
                return $this->finishByOrderNumber($orderNumber);
            } else if ($userId) {
                return $this->finishByUserInfo();
            }
            return array(
                'code' => '0', 
                'msg' => 'success');
        } else {
            return array(
                'code' => '-1', 
                'msg' => 'taskId missiong or task is not valid');
        }
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $orderNumber
     */
    private function finishByOrderNumber($orderNumber) {
        $ret = array(
            'code' => '-2', 
            'msg' => 'failure');
        $nodeId = 0;
        $orderInfo = $this->OrderModel->getOrderInfoByNumber($orderNumber);
        if (isset($orderInfo['pay_status']) && $orderInfo['pay_status'] == 1) { // 已支付
            $nodeId = isset($orderInfo['node_id']) ? $orderInfo['node_id'] : '';
            $processStatus = $this->DoubleFestivalService->getProgressStatusByTaskIdAndNodeId(
                $this->taskId, $nodeId);
            if ($processStatus == DoubleFestivalService::TASK_NOT_FOUND &&
                 $this->forceFinish) { // 还没有接该任务
                $processStatus = DoubleFestivalService::TASK_PROGRESSING;
                $this->startTask($nodeId, $processStatus);
            }
            if ($processStatus == DoubleFestivalService::TASK_PROGRESSING) {
                $ret = $this->DoubleFestivalService->finishByTaskIdAndNodeId(
                    $this->taskId, $nodeId);
            }
        }
        
        $this->triggerNextTask($nodeId);
        
        return $ret;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    private function finishByUserInfo() {
        $ret = array(
            'code' => '-2', 
            'msg' => 'failure');
        // $processStatus =
        // $this->DoubleFestivalService->getProgressStatus($this->taskId);
        $processStatus = $this->DoubleFestivalService->getProgressStatusByUserInfo(
            $this->taskId, $this->params);
        $nodeId = isset($this->params['node_id']) ? $this->params['node_id'] : '';
        if ($processStatus == DoubleFestivalService::TASK_NOT_FOUND &&
             $this->forceFinish) { // 任务没有找到
                                  // 且需要强制完成
            $processStatus = TaskService::TASK_PROGRESSING;
            $this->startTask($nodeId, $processStatus);
        }
        if ($processStatus == DoubleFestivalService::TASK_PROGRESSING) {
            $ret = $this->DoubleFestivalService->finishByTaskIdAndNodeId(
                $this->taskId, $nodeId, $this->params);
        }
        $this->triggerNextTask($nodeId);
        return $ret;
    }
}