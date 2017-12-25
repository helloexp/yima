<?php

/**
 * 领取任务奖励 (通用, 需要传递参数，taskId)
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class ClaimBehavior
 */
abstract class TaskBaseBehavior extends Behavior {

    /**
     *
     * @var DoubleFestivalService
     */
    public $DoubleFestivalService;

    /**
     *
     * @var OrderModel
     */
    public $OrderModel;

    /**
     *
     * @var TaskService
     */
    public $TaskService;

    /**
     *
     * @var
     *
     */
    protected $params;

    /**
     *
     * @var
     *
     */
    protected $taskId;

    /**
     *
     * @var array
     */
    protected $taskInfo = array();

    public function _initialize(&$params) {
        $this->params = $params;
        $this->DoubleFestivalService = D('DoubleFestival', 'Service');
        $this->TaskService = D('Task', 'Service');
        $this->OrderModel = D('Order');
        $this->taskId = isset($this->params['taskId']) ? $this->params['taskId'] : '';
    }

    /**
     *
     * @param $nodeId
     */
    protected function triggerNextTask($nodeId) {
        $nextTaskId = isset($this->params['nextTaskId']) ? $this->params['nextTaskId'] : '';
        if ($nextTaskId) {
            $processInfo = array(
                'node_id' => $nodeId, 
                'task_id' => $nextTaskId, 
                'task_data' => 1, 
                'task_status' => TaskService::TASK_PROGRESSING, 
                'wc_time' => date('YmdHis'));
            $this->DoubleFestivalService->startTask($processInfo);
        }
    }

    /**
     * 开始一个任务 (机构)
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param string $nodeId 机构id
     * @param int $taskStatus 任务起始状态
     */
    protected function startTask($nodeId, 
        $taskStatus = TaskService::TASK_PROGRESSING) {
        $processInfo = array(
            'node_id' => $nodeId, 
            'task_id' => $this->taskId, 
            'task_data' => 1, 
            'task_status' => $taskStatus, 
            'wc_time' => date('YmdHis'));
        $this->DoubleFestivalService->startTask($processInfo);
    }

    /**
     * 根据任务ID获得任务基本信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param int $taskId 任务ID
     * @return array
     */
    protected function getTaskInfo($taskId) {
        $this->taskInfo = $this->TaskService->getTaskInfoById($taskId);
        return $this->taskInfo;
    }

    /**
     * 验证任务有效期
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return bool
     */
    public function verifyTaskExpire() {
        if (empty($this->taskInfo) && $this->taskId) {
            $this->getTaskInfo($this->taskId);
        }
        
        if ($this->taskInfo) {
            $currentTime = date('YmdHis');
            $startTime = isset($this->taskInfo['begin_time']) ? $this->taskInfo['begin_time'] : 0;
            $endTime = isset($this->taskInfo['end_time']) ? $this->taskInfo['end_time'] : 0;
            if ($currentTime >= $startTime && $currentTime <= $endTime) {
                return true;
            }
        }
        return false;
    }
}