<?php

/**
 * 任务初始化
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class TaskInitBehavior
 */
class TaskInitBehavior extends TaskBaseBehavior {

    public function run(&$params) {
        $this->_initialize($params);
        $ret = false;
        if ($this->verifyTaskExpire()) {
            $orderNumber = isset($this->params['orderNumber']) ? $this->params['orderNumber'] : '';
            $userId = isset($this->params['user_id']) ? $this->params['user_id'] : '';
            if ($orderNumber) {
                $ret = $this->initByOrderNumber($orderNumber);
            } else if ($userId) {
                $ret = $this->initByUserInfo();
            }
            
            if ($ret) {
                return array(
                    'code' => '0', 
                    'msg' => 'success');
            } else {
                return array(
                    'code' => '-2', 
                    'msg' => 'failure');
            }
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
     * @return bool
     */
    private function initByOrderNumber($orderNumber) {
        $ret = false;
        $orderInfo = $this->OrderModel->getOrderInfoByNumber($orderNumber);
        if (isset($orderInfo['pay_status']) && $orderInfo['pay_status'] == 1) { // 已支付
            $nodeId = isset($orderInfo['node_id']) ? $orderInfo['node_id'] : '';
            $processStatus = $this->DoubleFestivalService->getProgressStatusByTaskIdAndNodeId(
                $this->taskId, $nodeId);
            if ($processStatus == DoubleFestivalService::TASK_NOT_FOUND) { // 还没有接该任务
                $processStatus = DoubleFestivalService::TASK_PROGRESSING;
                $this->startTask($nodeId, $processStatus);
                $ret = true;
            }
        }
        
        return $ret;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return bool
     */
    private function initByUserInfo() {
        $processStatus = $this->DoubleFestivalService->getProgressStatusByUserInfo(
            $this->taskId, $this->params);
        $nodeId = isset($this->params['node_id']) ? $this->params['node_id'] : '';
        if ($processStatus == DoubleFestivalService::TASK_NOT_FOUND) { // 任务没有找到
                                                                       // 初始化任务
            $processStatus = TaskService::TASK_PROGRESSING;
            $this->startTask($nodeId, $processStatus);
            return true;
        }
        return false;
    }
}