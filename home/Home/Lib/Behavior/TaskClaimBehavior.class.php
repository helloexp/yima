<?php

/**
 * 领取任务奖励 (通用, 需要传递参数，taskId)
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class ClaimBehavior
 */
class TaskClaimBehavior extends TaskBaseBehavior {

    public function run(&$params) {
        $this->_initialize($params);
        
        if ($this->verifyTaskExpire()) {
            $orderNumber = isset($params['orderNumber']) ? $params['orderNumber'] : '';
            $userId = isset($params['user_id']) ? $params['user_id'] : '';
            if ($orderNumber) {
                return $this->claimByOrderNumber($orderNumber);
            } else if ($userId) {
                return $this->claimByUserInfo();
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
     * @return array
     */
    private function claimByOrderNumber($orderNumber) {
        $ret = array(
            'code' => '-2', 
            'msg' => 'failure');
        $orderInfo = $this->OrderModel->getOrderInfoByNumber($orderNumber);
        if (isset($orderInfo['pay_status']) && $orderInfo['pay_status'] == 1) {
            $nodeId = isset($orderInfo['node_id']) ? $orderInfo['node_id'] : '';
            $processStatus = $this->DoubleFestivalService->getProgressStatusByTaskIdAndNodeId(
                $this->taskId, $nodeId);
            if ($processStatus == DoubleFestivalService::TASK_NOT_FOUND) { // 还没有接该任务
                $processInfo = array(
                    'node_id' => $nodeId, 
                    'task_id' => $this->taskId, 
                    'task_data' => 1, 
                    'task_status' => TaskService::TASK_FINISHED, 
                    'wc_time' => date('YmdHis'));
                $this->DoubleFestivalService->startTask($processInfo);
                $processStatus = DoubleFestivalService::TASK_FINISHED;
            }
            if ($processStatus == DoubleFestivalService::TASK_FINISHED) {
                $ret = $this->DoubleFestivalService->claimByTaskIdAndNodeId(
                    $this->taskId, $nodeId);
                $this->triggerNextTask($nodeId);
            }
        }
        return $ret;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return array
     */
    private function claimByUserInfo() {
        $ret = array(
            'code' => '-2', 
            'msg' => 'failure');
        $processStatus = $this->DoubleFestivalService->getProgressStatusByUserInfo(
            $this->taskId, $this->params);
        $nodeId = isset($this->params['node_id']) ? $this->params['node_id'] : '';
        if ($processStatus == DoubleFestivalService::TASK_NOT_FOUND) { // 任务没有完成，直接设置为已完成状态
            $processStatus = TaskService::TASK_FINISHED;
            $processInfo = array(
                'node_id' => $nodeId, 
                'task_id' => $this->taskId, 
                'task_data' => 1, 
                'task_status' => $processStatus, 
                'wc_time' => date('YmdHis'));
            $this->DoubleFestivalService->startTask($processInfo);
        }
        if ($processStatus == DoubleFestivalService::TASK_FINISHED) {
            $ret = $this->DoubleFestivalService->claimByUserInfo($this->taskId, 
                $this->params);
            $this->triggerNextTask($nodeId);
        }
        return $ret;
    }
}