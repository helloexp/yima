<?php

class WapRegTask extends TaskService {

    public $config;
    // 配置
    public $taskInfo;

    public $node_id;

    public $user_id;

    public $finishData = 1;
    // 完成任务的数据
    public $winId = '';

    public $wbNum = 30;

    public $wbValid = 30;
    // 旺币有效期天数
    public $taskValid = 3;
    // 任务有效天数
    public function __construct($taskInfo, $config = array()) {
        $this->taskInfo = $taskInfo;
        // 弹窗控制
        $taskConfig = json_decode($this->taskInfo['config'], true);
        $this->winId = $taskConfig['pop_win'];
    }

    /*
     * 完成任务 @param $user_id 用户号 return array('code'=>0,'msg'=>'信息')
     */
    public function finish() {
        // 开始事务
        M()->startTrans();
        
        // 判断任务是否已经完成
        $progressInfo = $this->getProgress($this->taskInfo['task_id'], true);
        if (! $progressInfo) {
            return array(
                'code' => '1', 
                'msg' => '任务不存在');
        }
        if ($progressInfo && $progressInfo['task_status']) {
            return array(
                'code' => '1', 
                'msg' => '任务已经完成');
        }
        
        // 更新进度为完成
        $result = M('ttask_progress')->where(
            array(
                'id' => $progressInfo['id']))->save(
            array(
                'task_data' => 1, 
                'task_status' => 1, 
                'wc_time' => date('YmdHis')));
        if ($result === false) {
            M()->rollback();
            return array(
                'code' => '1', 
                'msg' => '更新失败02');
        }
        // 清除弹窗状态
        $result = $this->removePopWin();
        if ($result === false) {
            M()->rollback();
            return array(
                'code' => '1', 
                'msg' => '更新失败03');
        }
        
        // 插入任务流水表
        $result = M('ttask_trace')->add(
            array(
                't_id' => $this->taskInfo['task_id'], 
                'node_id' => $this->node_id, 
                'user_id' => $this->user_id, 
                'add_time' => date('YmdHis'), 
                'memo' => '注册有礼任务,编号[' . $this->taskInfo['task_id'] . ']完成登录领取'));
        if ($result === false) {
            M()->rollback();
            return array(
                'code' => '1000', 
                'msg' => '插入数据失败[02]');
        }
        
        // 最后送旺币
        $wbResult = $this->sendWcMoney($this->wbNum);
        if (! $wbResult || $wbResult['code'] != '0') {
            M()->rollback();
            log_write('发送旺币失败' . print_r($wbResult, true));
            return array(
                'code' => '1000', 
                'msg' => '赠送旺币失败，原因：' . $wbResult['msg']);
        }
        
        M()->commit();
        // 返回信息
        return array(
            'code' => '0', 
            'msg' => $wbResult['msg']);
        // tag('view_end');
    }

    /*
     * 送注册礼包 @param $user_id 用户号
     */
    public function addGift($user_id) {
        $userInfo = M('tuser_info')->where(
            array(
                'user_id' => $user_id))->find();
        $node_id = $userInfo['node_id'];
        // 查询是否已经完成任务
        $processInfo = M('ttask_progress')->where(
            array(
                'node_id' => $node_id, 
                'task_id' => $this->taskInfo['task_id']))->find();
        if ($processInfo) {
            return array(
                'code' => 0, 
                'msg' => '已领取，请登录旺财PC端查看');
        }
        // 添加任务进程
        $processInfo = array(
            'node_id' => $node_id, 
            'task_id' => $this->taskInfo['task_id'], 
            'task_data' => 0, 
            'task_status' => 0, 
            'wc_time' => date('YmdHis'));
        $result = M('ttask_progress')->add($processInfo);
        if (! $result) {
            return array(
                'code' => - 1, 
                'msg' => 'error[1]');
        }
        $t_id = $result;
        // 添加任务流水
        $data = array(
            't_id' => $t_id, 
            'node_id' => $node_id, 
            'user_id' => $user_id, 
            'add_time' => date('YmdHis'), 
            'memo' => "注册成功");
        $result = M('ttask_trace')->add($data);
        if (! $result) {
            return array(
                'code' => - 1, 
                'msg' => 'error[2]');
        }
        // 添加弹窗
        $data = array(
            'window_id' => $this->winId, 
            'node_id' => $node_id, 
            'user_id' => $user_id, 
            'close_flag' => 3); // 未阅读
        
        $result = M('tpop_window_control')->add($data);
        if (! $result) {
            return array(
                'code' => - 1, 
                'msg' => 'error[3]');
        }
        return array(
            'code' => 0, 
            'msg' => 'success');
    }
    
    // 任务完成不要再弹窗了
    public function removePopWin($flag = 1) {
        $result = M('tpop_window_control')->where(
            array(
                'window_id' => $this->winId, 
                'node_id' => $this->node_id))->save(
            array(
                'close_flag' => $flag,  // 任务完成
                'updated_time' => date('YmdHis')));
        return $result;
    }
    
    // 送旺币
    public function sendWcMoney($num) {
        // 开始赠送旺币
        if ($num) {
            $service = D('RemoteRequest', 'Service');
            $TransactionID = date('ymdHis') . mt_rand(1000, 9999);
            $SystemID = C('YZ_SYSTEM_ID');
            $BeginTime = date('Ymd');
            $EndTime = date('Ymd', strtotime("+ " . $this->wbValid . " days"));
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
                'ReasonID' => 26,  // 20赠送
                'Amount' => 0, 
                'WbNumber' => $num, 
                'Remark' => '注册有礼任务,编号[' . $this->taskInfo['task_id'] . ']完成登录领取');
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

    public function getGiftInfo() {
        $progressInfo = $this->getProgress($this->taskInfo['task_id']);
        if ($progressInfo) {
            $validTime = date('Y-m-d', 
                strtotime("+ " . $this->wbValid . " days", 
                    strtotime($progressInfo['wc_time'])));
        } else {
            $validTime = '';
        }
        return array(
            'wbTime' => $validTime, 
            'wbValid' => $this->wbValid, 
            'wbNum' => $this->wbNum);
    }
    // 校验有效性
    public function checkValid() {
        $progressInfo = $this->getProgress($this->taskInfo['task_id']);
        
        $expire_time = date('YmdHis', 
            strtotime("-" . $this->taskValid . " days"));
        // 要显示窗口
        if ($progressInfo && $progressInfo['task_status'] == '0' &&
             $progressInfo['wc_time'] >= $expire_time) // 完成时间小于三天前
{
            return true;
        }
        // 如果超时更新成失效，移除弹框
        if ($progressInfo && $progressInfo['wc_time'] <= $expire_time) {
            $this->removePopWin(0);
        }
        return false;
    }
}