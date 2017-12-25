<?php

class GuestbookTask extends TaskService {

    public $config;
    // 配置
    public $taskInfo;

    public $node_id;

    public $user_id;

    public $finishData = 3;
    // 完成任务的数据
    public $wbValiade = "6 months";
    // 旺币有效期
    public function __construct($taskInfo, $config = array()) {
        $this->taskInfo = $taskInfo;
        $userSess = D('UserSess', 'Service'); // 得到用户信息
        $userInfo = $userSess->getUserInfo();
        $this->node_id = $userInfo['node_id'];
        $this->user_id = $userInfo['user_id'];
    }

    /*
     * @param $word 评论字数 return array('code'=>0,'msg'=>'信息')
     */
    public function start($word) {
        // 判断任务是否已经完成
        $progressInfo = $this->getProgress($this->taskInfo['task_id']);
        if ($progressInfo && $progressInfo['task_status']) {
            return array(
                'code' => '1', 
                'msg' => '任务已经完成');
        }
        
        $len = mb_strlen($word, 'utf-8');
        // 如果字符没有达到10个字，则不触发任务
        if ($len < 10) {
            return array(
                'code' => '1000', 
                'msg' => '评论字数不够10个');
        }
        // 开始判断任务进程
        // 查询用户已经连续几天有评论达到10个字（满足任务条件）
        $arr = M('tbatch_guestbook')->field("substr(add_time,'1',8) add_date")
            ->where(
            array(
                'node_id' => $this->node_id, 
                // 'pid'=>0,
                '_string' => "length(content)>=10", 
                'add_time' => array(
                    'gt', 
                    $this->taskInfo['begin_time'])))
            ->group('add_date')
            ->select();
        $count = count($arr);
        $task_data = $count;
        if ($count >= $this->finishData) {
            $task_data = $this->finishData;
        }
        // 如果当前任务进度没变
        if ($task_data == $progressInfo['task_data']) {
            return array(
                'code' => '1', 
                'msg' => '任务进度不变');
        }
        // 更新任务进度
        $data = array(
            'task_data' => $task_data);
        // 如果已经超过3天，则完成任务
        if ($task_data == $this->finishData) {
            // 任务完成以后移除弹窗
            $taskConfig = json_decode($this->taskInfo['config'], true) or
                 $taskConfig = array();
            if ($taskConfig && $taskConfig['pop_win']) {
                $this->removePopWin($taskConfig['pop_win']);
            }
            $data['status'] = 1;
        }
        M()->startTrans();
        // 更新,或者插入
        if ($progressInfo) {
            $result = M('ttask_progress')->where(
                array(
                    'id' => $progressInfo['id']))->save($data);
        } else {
            $data = array_merge($data, 
                array(
                    'node_id' => $this->node_id, 
                    'task_id' => $this->taskInfo['task_id'], 
                    'task_data' => $task_data, 
                    'task_status' => '0', 
                    'wc_time' => date('YmdHis')));
            $result = M('ttask_progress')->add($data);
            // 返回信息
            if (! $result) {
                M()->rollback();
                return array(
                    'code' => '1000', 
                    'msg' => '插入数据失败[01]');
            }
        }
        // 送旺币
        $wbResult = $this->sendWcMoney($task_data);
        if (! $wbResult || $wbResult['code'] != '0') {
            M()->rollback();
            log::write('发送旺币失败' . print_r($wbResult, true));
            return array(
                'code' => '1000', 
                'msg' => '赠送旺币失败，原因：' . $wbResult['msg']);
        }
        // 插入任务流水表
        $result = M('ttask_trace')->add(
            array(
                't_id' => $this->taskInfo['task_id'], 
                'node_id' => $this->node_id, 
                'user_id' => $this->user_id, 
                'add_time' => date('YmdHis'), 
                'memo' => 'O2O案例任务,编号[' . $this->taskInfo['task_id'] . ']完成第' .
                     $task_data . '步'));
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
            'msg' => $wbResult['msg']);
        // tag('view_end');
    }
    // 送旺币
    public function sendWcMoney($task_data) {
        $num = 0;
        if ($task_data == 1) {
            $num = 30;
        } elseif ($task_data == 2) {
            $num = 30;
        } elseif ($task_data == 3) {
            $num = 40;
        }
        // 开始赠送旺币
        if ($num) {
            $service = D('RemoteRequest', 'Service');
            $TransactionID = date('ymdHis') . mt_rand(1000, 9999);
            $SystemID = C('YZ_SYSTEM_ID');
            $BeginTime = date('Ymd');
            $EndTime = date('Ymd', strtotime("+ " . $this->wbValiade));
            $nodeInfo = M('tnode_info')->where(
                array(
                    'node_id' => $this->node_id))->find();
            $data = array(
                'SystemID' => $SystemID, 
                'TransactionID' => $TransactionID, 
                'ContractID' => $nodeInfo['contract_no'], 
                'WbType' => '1', 
                'BeginTime' => $BeginTime, 
                'EndTime' => $EndTime, 
                'ReasonID' => 12, 
                'Amount' => 0, 
                'WbNumber' => $num, 
                'Remark' => 'O2O案例任务,编号[' . $this->taskInfo['task_id'] . ']完成第' .
                     $task_data . '步');
            $data = array(
                'SetWbReq' => $data);
            $yzResult = $service->requestYzServ($data);
            if (! $yzResult || ! $yzResult['Status']) {
                return array(
                    'code' => '9', 
                    'msg' => '赠送旺币失败,网络正忙[01]');
            }
            if ($yzResult['Status']['StatusCode'] != '0') {
                return array(
                    'code' => '9', 
                    'msg' => '赠送旺币失败,原因：' . $yzResult['Status']['StatusText']);
            }
            return array(
                'code' => '0', 
                'msg' => '<h2>已赠送您' . $num . '旺币</h2>' . '<p>有效期为' .
                     dateformat($BeginTime, 'Y-m-d') . '到' .
                     dateformat($EndTime, 'Y-m-d') . '<br/>' .
                     '可以在<a href="index.php?g=Home&m=AccountInfo&a=index">个人帐户中心</a>查看并使用</p>');
        }
        return array(
            'code' => 1, 
            'msg' => "未送旺币");
    }
    // 任务完成不要再弹窗了
    public function removePopWin($id) {
        $count = M('tpop_window_control')->where(
            array(
                'node_id' => $this->node_id, 
                'window_id' => $id, 
                'user_id' => $this->user_id))->count();
        if ($count)
            return true;
        $result = M('tpop_window_control')->add(
            array(
                'window_id' => $id, 
                'node_id' => $this->node_id, 
                'user_id' => $this->user_id, 
                'close_flag' => '1'));
        return $result;
    }
}