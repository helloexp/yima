<?php

/*
 * auther wtr 任务管理
 */
class TaskService {

    const TASK_NOT_FOUND = - 1;

    const TASK_PROGRESSING = 0;

    const TASK_FINISHED = 2;

    const TASK_CLAIMED = 3;

    public $error = null;

    public $node_id = '';

    public $userInfo;

    public $user_id;
    
    // 做作协 务
    public function getTask($name, $option = array()) {
        $nowtime = date('YmdHis');
        $taskInfo = M('ttask_param')->where(
            array(
                'event_name' => $name, 
                'begin_time' => array(
                    'lt', 
                    $nowtime), 
                'end_time' => array(
                    'gt', 
                    $nowtime)))->find();
        if (! $taskInfo) {
            $error = GROUP_NAME . '/' . MODULE_NAME . '/' . ACTION_NAME . '任务名【' .
                 $name . '】不存在或已过期';
            $this->error = $error;
            tag('view_end');
            return null;
        }
        $class_name = $taskInfo['class_name'];
        if (file_exists(
            LIB_PATH . 'Service/' . $class_name . 'Service.class.php')) {
            return D($class_name, 'Service');
        } else {
            $path = LIB_PATH . 'Action/Task/class';
            import($class_name, $path, '.class.php') or
                 $this->showMsg($path . $name . '不存在');
            return new $class_name($taskInfo, $option);
        }
    }

    /**
     * 根据任务ID 获得任务相关数据
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $taskId
     * @return array
     */
    public function getTaskInfoById($taskId) {
        $nowtime = date('YmdHis');
        $taskInfo = M('ttask_param')->where(
            array(
                'task_id' => $taskId, 
                'begin_time' => array(
                    'lt', 
                    $nowtime), 
                'end_time' => array(
                    'gt', 
                    $nowtime)))->find();
        return $taskInfo;
    }

    public function showMsg($msg) {
        echo $msg;
        tag('view_end');
        exit();
    }

    public function error() {
        return $this->error();
    }
    
    // 得到任务进程
    /**
     *
     * @param int $task_id
     * @param bool $needLock 是否需要锁
     * @return array/bool
     */
    public function getProgress($task_id, $needLock = false) {
        // 开始事务 读写分离的时候 会出问题。。
        M()->startTrans();
        $userSess = D('UserSess', 'Service');
        $userInfo = $userSess->getUserInfo();
        $node_id = $userInfo['node_id'];
        $info = M('ttask_progress')->where(
            array(
                'task_id' => $task_id, 
                'node_id' => $node_id))
            ->lock($needLock)
            ->find();
        $this->node_id = $node_id;
        $this->userInfo = $userInfo;
        $this->user_id = $userInfo['user_id'];
        M()->commit();
        return $info;
    }

    /**
     * 获得任务状态
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $taskId
     * @param bool|false $needLock
     *
     * @return int -1:没有任务进度 0：未完成 1：已完成 2：已领奖
     */
    public function getProgressStatus($taskId, $needLock = false) {
        $progressInfo = $this->getProgress($taskId, $needLock);
        if ($progressInfo && isset($progressInfo['task_status'])) {
            return $progressInfo['task_status'];
        }
        return self::TASK_NOT_FOUND;
    }

    /**
     *
     * @param int $taskId
     * @param bool $needLock 是否需要锁
     * @return array/bool
     */
    public function getProgressByTaskIdAndNodeId($taskId, $nodeId, 
        $needLock = false) {
        $info = M('ttask_progress')->where(
            array(
                'task_id' => $taskId, 
                'node_id' => $nodeId))
            ->lock($needLock)
            ->find();
        $this->node_id = $nodeId;
        return $info;
    }

    /**
     *
     * @param $taskId
     * @param $nodeId
     * @param bool|false $needLock
     *
     * @return int
     */
    public function getProgressStatusByTaskIdAndNodeId($taskId, $nodeId, 
        $needLock = false) {
        $progressInfo = $this->getProgressByTaskIdAndNodeId($taskId, $nodeId, 
            $needLock);
        if ($progressInfo && isset($progressInfo['task_status'])) {
            return $progressInfo['task_status'];
        }
        return self::TASK_NOT_FOUND;
    }

    public function getProgressByUserInfo($taskId, $userInfo, $needLock) {
        $nodeId = isset($userInfo['node_id']) ? $userInfo['node_id'] : '';
        $userId = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        
        $info = M('ttask_progress')->where(
            array(
                'task_id' => $taskId, 
                'node_id' => $nodeId))
            ->lock($needLock)
            ->find();
        $this->node_id = $nodeId;
        $this->user_id = $userId;
        return $info;
    }

    public function getProgressStatusByUserInfo($taskId, $userInfo, 
        $needLock = false) {
        $progressInfo = $this->getProgressByUserInfo($taskId, $userInfo, 
            $needLock);
        if ($progressInfo && isset($progressInfo['task_status'])) {
            return $progressInfo['task_status'];
        }
        return self::TASK_NOT_FOUND;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $processInfo
     * @return mixed
     */
    public function startTask($processInfo) {
        $nodeId = isset($processInfo['node_id']) ? $processInfo['node_id'] : '';
        $taskId = isset($processInfo['task_id']) ? $processInfo['task_id'] : '';
        $taskData = isset($processInfo['task_data']) ? $processInfo['task_data'] : 0;
        $taskStatus = isset($processInfo['task_status']) ? $processInfo['task_status'] : 0;
        $processInfo = array(
            'node_id' => $nodeId, 
            'task_id' => $taskId, 
            'task_data' => $taskData, 
            'task_status' => $taskStatus, 
            'wc_time' => date('YmdHis'));
        return M('ttask_progress')->add($processInfo);
    }

    /**
     * 领取任务奖励
     *
     * @param $taskId
     * @param array $userInfo
     *
     * @return array('code'=>0,'msg'=>'信息')
     */
    public function claimByUserInfo($taskId, $userInfo = array()) {
        // 开始事务
        M()->startTrans();
        // 判断任务是否已经完成
        $progressStatus = $this->getProgressStatus($taskId, true);
        if ($progressStatus == self::TASK_NOT_FOUND) {
            return array(
                'code' => '1', 
                'msg' => '任务不存在');
        }
        if ($progressStatus == self::TASK_CLAIMED) {
            return array(
                'code' => '3', 
                'msg' => '任务已经完成');
        }
        
        // 更新进度为完成
        $result = M('ttask_progress')->where(
            array(
                'task_id' => $taskId))->save(
            array(
                'task_data' => 1, 
                'task_status' => self::TASK_CLAIMED, 
                'wc_time' => date('YmdHis')));
        if ($result === false) {
            M()->rollback();
            return array(
                'code' => '4', 
                'msg' => '更新失败02');
        }
        // 清除弹窗状态
        if ($result === false) {
            M()->rollback();
            return array(
                'code' => '5', 
                'msg' => '更新失败03');
        }
        
        // 插入任务流水表
        $node_id = isset($userInfo['node_id']) ? $userInfo['node_id'] : $this->node_id;
        $user_id = isset($userInfo['user_id']) ? $userInfo['user_id'] : $this->user_id;
        $result = M('ttask_trace')->add(
            array(
                't_id' => $taskId, 
                'node_id' => $node_id, 
                'user_id' => $user_id, 
                'add_time' => date('YmdHis'), 
                'memo' => '领取任务奖励'));
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
     * 完成任务
     *
     * @param $taskId
     * @param array $userInfo
     *
     * @return array('code'=>0,'msg'=>'信息')
     */
    public function claimByTaskIdAndNodeId($taskId, $nodeId, $userInfo = array()) {
        // 开始事务
        M()->startTrans();
        // 判断任务是否已经完成
        $progressStatus = $this->getProgressStatusByTaskIdAndNodeId($taskId, 
            $nodeId, true);
        if ($progressStatus == self::TASK_NOT_FOUND) {
            return array(
                'code' => '1', 
                'msg' => '任务不存在');
        }
        if ($progressStatus == self::TASK_CLAIMED) {
            return array(
                'code' => '3', 
                'msg' => '任务已领奖');
        }
        
        // 更新进度为完成
        $result = M('ttask_progress')->where(
            array(
                'task_id' => $taskId))->save(
            array(
                'task_data' => 1, 
                'task_status' => self::TASK_CLAIMED, 
                'wc_time' => date('YmdHis')));
        if ($result === false) {
            M()->rollback();
            return array(
                'code' => '4', 
                'msg' => '更新失败02');
        }
        // 清除弹窗状态
        if ($result === false) {
            M()->rollback();
            return array(
                'code' => '5', 
                'msg' => '更新失败03');
        }
        
        // 插入任务流水表
        $node_id = isset($userInfo['node_id']) ? $userInfo['node_id'] : $this->node_id;
        $user_id = isset($userInfo['user_id']) ? $userInfo['user_id'] : $this->user_id;
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
     * 完成任务
     *
     * @param $taskId
     * @param array $userInfo
     *
     * @return array('code'=>0,'msg'=>'信息')
     */
    public function finish($taskId, $userInfo = array()) {
        // 开始事务
        M()->startTrans();
        // 判断任务是否已经完成
        $progressStatus = $this->getProgressStatus($taskId, true);
        if ($progressStatus == self::TASK_NOT_FOUND) {
            return array(
                'code' => '1', 
                'msg' => '任务不存在');
        }
        if ($progressStatus == self::TASK_CLAIMED ||
             $progressStatus == self::TASK_FINISHED) {
            return array(
                'code' => '3', 
                'msg' => '任务已经完成');
        }
        
        $nodeId = isset($userInfo['node_id']) ? $userInfo['node_id'] : $this->node_id;
        $user_id = isset($userInfo['user_id']) ? $userInfo['user_id'] : $this->user_id;
        // 更新进度为完成
        $result = M('ttask_progress')->where(
            array(
                'task_id' => $taskId, 
                'node_id' => $nodeId))->save(
            array(
                'task_data' => 1, 
                'task_status' => self::TASK_FINISHED, 
                'wc_time' => date('YmdHis')));
        if ($result === false) {
            M()->rollback();
            return array(
                'code' => '4', 
                'msg' => '更新失败02');
        }
        // 清除弹窗状态
        if ($result === false) {
            M()->rollback();
            return array(
                'code' => '5', 
                'msg' => '更新失败03');
        }
        
        // 插入任务流水表
        
        $result = M('ttask_trace')->add(
            array(
                't_id' => $taskId, 
                'node_id' => $nodeId, 
                'user_id' => $user_id, 
                'add_time' => date('YmdHis'), 
                'memo' => '完成任务'));
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
     * 完成任务
     *
     * @param $taskId
     * @param $nodeId
     * @param array $userInfo
     *
     * @return array
     */
    public function finishByTaskIdAndNodeId($taskId, $nodeId, 
        $userInfo = array()) {
        // 开始事务
        M()->startTrans();
        // 判断任务是否已经完成
        $progressStatus = $this->getProgressStatusByTaskIdAndNodeId($taskId, 
            $nodeId, true);
        if ($progressStatus == self::TASK_NOT_FOUND) {
            return array(
                'code' => '1', 
                'msg' => '任务不存在');
        }
        if ($progressStatus != self::TASK_PROGRESSING) {
            return array(
                'code' => '3', 
                'msg' => '任务不是正在进行状态，无法完成');
        }
        
        // 更新进度为完成
        $result = M('ttask_progress')->where(
            array(
                'task_id' => $taskId))->save(
            array(
                'task_data' => 1, 
                'task_status' => self::TASK_FINISHED, 
                'wc_time' => date('YmdHis')));
        if ($result === false) {
            M()->rollback();
            return array(
                'code' => '4', 
                'msg' => '更新失败02');
        }
        
        // 插入任务流水表
        $userId = isset($userInfo['user_id']) ? $userInfo['user_id'] : $this->user_id;
        $result = M('ttask_trace')->add(
            array(
                't_id' => $taskId, 
                'node_id' => $nodeId, 
                'user_id' => $userId, 
                'add_time' => date('YmdHis'), 
                'memo' => '完成任务'));
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

    public function forceFinish($taskId, $processInfo = array()) {
        $progressStatus = $this->getProgress($taskId);
        if ($progressStatus) {
            $this->finish($taskId);
        } else {
            $this->startTask($processInfo);
        }
    }
}