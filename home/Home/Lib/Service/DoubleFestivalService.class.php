<?php

/**
 * 双旦任务Service
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> Class DoubleFestivalRegService
 */
import('@.Service.TaskService') or die('导入包失败');

class DoubleFestivalService extends TaskService {

    const REG_TASK_ID = 6;
    // 注册任务ID
    const PAY_ORDER_TASK_ID = 4;
    // 支付双旦模块任务ID
    const FAVOUR_TASK_ID = 5;
    // 最终双旦任务ID
    
    /**
     * 领取经理
     *
     * @param int $taskId 任务ID
     * @param array $userInfo 用户信息
     * @return array('code'=>0,'msg'=>'信息')
     *
     */
    public function claimByUserInfo($taskId, $userInfo = array()) {
        // 开始事务
        M()->startTrans();
        // 判断任务是否已经完成
        $progressStatus = $this->getProgressStatusByUserInfo($taskId, $userInfo, 
            true);
        if ($progressStatus == self::TASK_NOT_FOUND) {
            return array(
                'code' => '1', 
                'msg' => '任务不存在');
        }
        if ($progressStatus == self::TASK_PROGRESSING) {
            return array(
                'code' => '2', 
                'msg' => '任务尚未完成');
        }
        if ($progressStatus == self::TASK_CLAIMED) {
            return array(
                'code' => '3', 
                'msg' => '任务已领奖');
        }
        
        $node_id = isset($userInfo['node_id']) ? $userInfo['node_id'] : '';
        $user_id = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        
        // 更新进度为完成
        $result = M('ttask_progress')->where(
            array(
                'task_id' => $taskId, 
                'node_id' => $node_id))->save(
            array(
                'task_status' => self::TASK_CLAIMED));
        if ($result === false) {
            M()->rollback();
            return array(
                'code' => '4', 
                'msg' => '更新失败02');
        }
        
        // 插入任务流水表
        $result = M('ttask_trace')->add(
            array(
                't_id' => $taskId, 
                'node_id' => $node_id, 
                'user_id' => $user_id, 
                'add_time' => date('YmdHis'), 
                'memo' => '领取奖励'));
        if ($result === false) {
            M()->rollback();
            return array(
                'code' => '1000', 
                'msg' => '插入数据失败[02]');
        }
        M()->commit();
        // 返回信息
        return array(
            'code' => '0', 
            'msg' => 'success');
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $taskId
     * @return int|string
     */
    public function getCjLabelIdByTaskId($taskId) {
        $cjLabelId = 0;
        $taskInfo = $this->getTaskInfoById($taskId);
        if ($taskInfo) {
            $config = isset($taskInfo['config']) ? $taskInfo['config'] : '';
            $taskConfig = json_decode($config, true);
            $cjLabelId = isset($taskConfig['cjLabelId']) ? $taskConfig['cjLabelId'] : '';
        }
        return $cjLabelId;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $taskId
     * @return int|string
     */
    public function getNewRegisterStartTimeByTaskId($taskId) {
        $startTime = 0;
        $taskInfo = $this->getTaskInfoById($taskId);
        if ($taskInfo) {
            $startTime = isset($taskInfo['begin_time']) ? $taskInfo['begin_time'] : '';
        }
        return $startTime;
    }

    public function getDoubleFestivalTaskList() {
        $where['task_id'] = array(
            'IN', 
            self::REG_TASK_ID . ',' . self::PAY_ORDER_TASK_ID . ',' .
                 self::FAVOUR_TASK_ID);
        
        $taskList = M('ttask_param')->where($where)->select();
        
        if ($taskList) {
            $currentTime = date('YmdHis');
            foreach ($taskList as $index => $task) {
                $startTime = isset($task['begin_time']) ? $task['begin_time'] : 0;
                $endTime = isset($task['end_time']) ? $task['end_time'] : 0;
                if ($currentTime < $startTime || $currentTime > $endTime) {
                    unset($taskList[$index]);
                }
            }
        }
        return $taskList;
    }

    public function getProgressTaskList($nodeId) {
        $where['p.task_id'] = array(
            'IN', 
            self::REG_TASK_ID . ',' . self::PAY_ORDER_TASK_ID . ',' .
                 self::FAVOUR_TASK_ID);
        $where['node_id'] = $nodeId;

        $return = M()->table("ttask_progress p")->join(
            'ttask_param t ON t.task_id=p.task_id')
            ->field(
            'p.task_id,p.task_status,t.task_name,t.begin_time, t.end_time')
            ->where($where)
            ->order('task_id desc')
            ->select();
        
        return $return;
    }

    public function showIconData($nodeId) {
        $taskId = 0;
        $taskName = '';
        $cssClass = '';
        $taskList = $this->getProgressTaskList($nodeId);
        
        if ($taskList) {
            $currentTime = date('YmdHis');
            $cssClass = 'bg-qh-ok';
            foreach ($taskList as $index => $task) {
                $beginTime = $task['begin_time'];
                $endTime = $task['end_time'];
                $taskName = $task['task_name'];
                $taskId = $task['task_id'];
                if ($currentTime >= $beginTime && $currentTime <= $endTime) {
                    if (isset($task['task_status']) &&
                         $task['task_status'] == TaskService::TASK_CLAIMED) {
                        $taskId = $task['task_id'];
                        $taskName = $task['task_name'];
                        $cssClass = 'bg-qh-ok';
                        break;
                    } else if (isset($task['task_status']) &&
                         $task['task_status'] == TaskService::TASK_FINISHED) {
                        $taskId = $task['task_id'];
                        $taskName = $task['task_name'];
                        $cssClass = 'bg-qh';
                        break;
                    }
                }
            }
        } else {
            $doubleFestivalTaskList = $this->getDoubleFestivalTaskList();
            if ($doubleFestivalTaskList) {
                $currentTaskInfo = array_shift($doubleFestivalTaskList);
                $taskId = isset($currentTaskInfo['task_id']) ? $currentTaskInfo['task_id'] : '';
                $taskName = isset($currentTaskInfo['task_name']) ? $currentTaskInfo['task_name'] : '';
                $cssClass = 'bg-qh-ok';
            }
        }
        if ($taskId) {
            return array(
                'taskId' => $taskId, 
                'taskName' => $taskName, 
                'cssClass' => $cssClass);
        }
        return array();
    }

    public function getFinishedTask($nodeId) {
        $where['task_id'] = array(
            'IN', 
            self::REG_TASK_ID . ',' . self::PAY_ORDER_TASK_ID . ',' .
                 self::FAVOUR_TASK_ID);
        $where['node_id'] = $nodeId;
        
        $result = M('ttask_progress')->where($where)
            ->order('task_id desc')
            ->select();
        $taskId = 0;
        $task_class = '';
        if ($result) {
            $task_class = 'bg-qh-ok';
            foreach ($result as $item) {
                if (isset($item['task_status']) &&
                     $item['task_status'] == TaskService::TASK_FINISHED) {
                    $taskId = $item['task_id'];
                    $task_class = 'bg-qh';
                    break;
                }
            }
        }
        $result = array();
        if ($taskId) {
            $where = array(
                'task_id' => $taskId, 
                'node_id' => $nodeId);
            $result = M('ttask_param')->where($where)->find();
        }
        if ($result) {
            $result['data'] = $result;
            $result['class'] = $task_class;
        }
        return $result;
    }
}