<?php

class WeixinCountAction extends BaseAction {

    public $uploadPath;

    const CHANNEL_TYPE_WX = '4';
    // 微信公众平台
    const CHANNEL_SNS_TYPE_WX = '41';
    // 微信公众平台发布类型
    public function _initialize() {
        parent::_initialize();
        $this->uploadPath = './Home/Upload/Weixin/'; // 设置附件上传目录
                                                         // C('LABEL_ADMIN',include(CONF_PATH.'LabelAdmin/config.php'));
    }
    
    // 用户统计中心
    public function user() {
        $tableData = $this->_userTableData();
        // 获取关注数
        $this->assign('tableData', $tableData);
        $keyData = $this->_userKeyData();
        $this->assign('keyFollowData', $keyData['keyFollowData']);
        $this->assign('keyCancelData', $keyData['keyCancelData']);
        $this->assign('keyRemainData', $keyData['keyRemainData']);
        $this->assign('keyTotalData', $keyData['keyTotalData']);
        $this->display();
    }
    // 获取用户详细统计数据
    protected function _userTableData() {
        $sdate = $_GET['b_trans_date'] = I('b_trans_date', 
            date('Ymd', strtotime("-8 days")));
        $edate = $_GET['e_trans_date'] = I('e_trans_date', 
            date('Ymd', strtotime("-1 days")));
        $days = DateDiff($edate, $sdate, 'd');
        if ($days < 0 || $days > 31) {
            return "日期范围不正确，不允许查询超过1个月的数据。";
        }
        $dao = M('twx_user_stat');
        $node_id = $this->nodeId;
        $where = "node_id='" . $node_id . "'";
        $where .= " and trans_date>='" . $sdate . "' and trans_date<='" . $edate .
             "'";
        $queryData = $dao->order("trans_date asc")
            ->where($where)
            ->getField(
            "trans_date as _key,node_id,follow_num,cancel_num,total_num,(follow_num-cancel_num) remain_num,trans_date");
        $queryData = $queryData ? $queryData : array();
        for ($i = 0; $i <= $days; $i ++) {
            $d = date("Ymd", strtotime($sdate . "+ $i days"));
            if (! isset($queryData[$d])) {
                $queryData[$d] = array(
                    'node_id' => $node_id, 
                    'follow_num' => 0, 
                    'cancel_num' => 0, 
                    'total_num' => 0, 
                    'remain_num' => 0, 
                    'trans_date' => $d);
            }
        }
        
        return $queryData;
    }
    
    // 获取昨日关键指标
    protected function _userKeyData() {
        // 日指标
        $yesDay = date('Ymd', strtotime("-1 days")); // 昨天
        $lastDay = date('Ymd', strtotime("-2 days")); // 前天
        $yesDayNum = array();
        $lastDayNum = array();
        $node_id = $this->nodeId;
        $where = array(
            'node_id' => $node_id, 
            'trans_date' => array(
                'in', 
                array(
                    $yesDay, 
                    $lastDay)));
        $fields = "trans_date as _k,node_id,
		follow_num,cancel_num,
		total_num,(follow_num-cancel_num) remain_num,
		follow_week_num,follow_lastweek_num,
		cancel_week_num,cancel_lastweek_num,
		(follow_week_num-cancel_week_num) remain_week_num,
		(follow_lastweek_num-cancel_lastweek_num) remain_lastweek_num,
		follow_month_num,follow_lastmonth_num,
		cancel_month_num,cancel_lastmonth_num,
		(follow_month_num-cancel_month_num) remain_month_num,
		(follow_lastmonth_num-cancel_lastmonth_num) remain_lastmonth_num,
		trans_date";
        $queryData = M('twx_user_stat')->where($where)->getField($fields);
        if (isset($queryData[$yesDay])) {
            $yesDayNum = $queryData[$yesDay];
        }
        if (isset($queryData[$yesDay])) {
            $lastDayNum = $queryData[$lastDay];
        }
        // 关注统计数
        $keyFollowData = array();
        $keyFollowData['count'] = $yesDayNum['follow_num'] * 1;
        $keyFollowData['day_rate'] = $this->_getRate($yesDayNum['follow_num'], 
            $lastDayNum['follow_num']);
        $keyFollowData['week_rate'] = $this->_getRate(
            $yesDayNum['follow_week_num'], $yesDayNum['follow_lastweek_num']);
        $keyFollowData['month_rate'] = $this->_getRate(
            $yesDayNum['follow_month_num'], $yesDayNum['follow_lastmonth_num']);
        
        // 取消统计数
        $keyCancelData = array();
        $keyCancelData['count'] = $yesDayNum['cancel_num'] * 1;
        $keyCancelData['day_rate'] = $this->_getRate($yesDayNum['cancel_num'], 
            $lastDayNum['cancel_num']);
        $keyCancelData['week_rate'] = $this->_getRate(
            $yesDayNum['cancel_week_num'], $yesDayNum['cancel_lastweek_num']);
        $keyCancelData['month_rate'] = $this->_getRate(
            $yesDayNum['cancel_month_num'], $yesDayNum['cancel_lastmonth_num']);
        
        // 净增统计数
        $keyRemainData = array();
        $keyRemainData['count'] = $yesDayNum['remain_num'] * 1;
        $keyRemainData['day_rate'] = $this->_getRate($yesDayNum['remain_num'], 
            $lastDayNum['remain_num']);
        $keyRemainData['week_rate'] = $this->_getRate(
            $yesDayNum['remain_week_num'], $yesDayNum['remain_lastweek_num']);
        $keyRemainData['month_rate'] = $this->_getRate(
            $yesDayNum['remain_month_num'], $yesDayNum['remain_lastmonth_num']);
        
        // 累计统计数
        $keyTotalData = array();
        $keyTotalData['count'] = $yesDayNum['total_num'] * 1;
        $keyTotalData['day_rate'] = $this->_getRate($yesDayNum['total_num'], 
            $lastDayNum['total_num']);
        $keyTotalData['week_rate'] = $this->_getRate(
            $yesDayNum['total_week_num'], $yesDayNum['total_lastweek_num']);
        $keyTotalData['month_rate'] = $this->_getRate(
            $yesDayNum['total_month_num'], $yesDayNum['total_lastmonth_num']);
        
        return array(
            'keyFollowData' => $keyFollowData, 
            'keyCancelData' => $keyCancelData, 
            'keyRemainData' => $keyRemainData, 
            'keyTotalData' => $keyTotalData);
    }
    
    // 消息统计
    public function message() {
        $tableData = $this->_messageTableData();
        // 获取关注数
        $this->assign('tableData', $tableData);
        $keyData = $this->_messageKeyData();
        $this->assign('keySendSumData', $keyData['keySendSumData']);
        $this->assign('keySendNumData', $keyData['keySendNumData']);
        $this->assign('keyPerNumData', $keyData['keyPerNumData']);
        $this->display();
    }
    
    // 获取用户详细统计数据
    protected function _messageTableData() {
        $sdate = $_GET['b_trans_date'] = I('b_trans_date', 
            date('Ymd', strtotime("-8 days")));
        $edate = $_GET['e_trans_date'] = I('e_trans_date', 
            date('Ymd', strtotime("-1 days")));
        $days = DateDiff($edate, $sdate, 'd');
        if ($days < 0 || $days > 31) {
            return "日期范围不正确，不允许查询超过1个月的数据。";
        }
        $dao = M('twx_msg_stat');
        $node_id = $this->nodeId;
        $where = "node_id='" . $node_id . "'";
        $where .= " and trans_date>='" . $sdate . "' and trans_date<='" . $edate .
             "'";
        $queryData = $dao->order("trans_date asc")
            ->where($where)
            ->getField(
            "trans_date as _key,node_id,send_sum,send_num,trans_date");
        $queryData = $queryData ? $queryData : array();
        for ($i = 0; $i <= $days; $i ++) {
            $d = date("Ymd", strtotime($sdate . "+ $i days"));
            if (! isset($queryData[$d])) {
                $queryData[$d] = array(
                    'node_id' => $node_id, 
                    'send_sum' => 0, 
                    'send_num' => 0, 
                    'per_num' => 0, 
                    'trans_date' => $d);
            }
            $queryData[$d]['per_num'] = $this->_getPer(
                $queryData[$d]['send_sum'], $queryData[$d]['send_num']);
        }
        
        return $queryData;
    }
    
    // 获取昨日关键指标
    protected function _messageKeyData() {
        // 日指标
        $yesDay = date('Ymd', strtotime("-1 days")); // 昨天
        $lastDay = date('Ymd', strtotime("-2 days")); // 前天
        $yesDayNum = array();
        $lastDayNum = $yesDayNum;
        $node_id = $this->nodeId;
        $where = array(
            'node_id' => $node_id, 
            'trans_date' => array(
                'in', 
                array(
                    $yesDay, 
                    $lastDay)));
        $fields = "trans_date as _k,node_id,
		send_sum,send_num,
		send_week_sum,send_week_num,
		send_lastweek_sum,send_lastweek_num,
		send_month_sum,send_month_num,
		send_lastmonth_sum,send_lastmonth_num,
		trans_date";
        $queryData = M('twx_msg_stat')->where($where)->getField($fields) or
             $queryData = array();
        // 处理一下数据
        foreach ($queryData as &$v) {
            $v['per_num'] = $this->_getPer($v['send_sum'], $v['send_num']);
            $v['per_week_num'] = $this->_getPer($v['send_week_sum'], 
                $v['send_week_num']);
            $v['per_lastweek_num'] = $this->_getPer($v['send_lastweek_sum'], 
                $v['send_lastweek_num']);
            $v['per_month_num'] = $this->_getPer($v['send_month_sum'], 
                $v['send_month_num']);
            $v['per_lastmonth_num'] = $this->_getPer($v['send_lastmonth_sum'], 
                $v['send_lastmonth_num']);
        }
        unset($v);
        if (isset($queryData[$yesDay])) {
            $yesDayNum = $queryData[$yesDay];
        }
        if (isset($queryData[$yesDay])) {
            $lastDayNum = $queryData[$lastDay];
        }
        // 发送次数
        $keySendSumData = array();
        $keySendSumData['count'] = $yesDayNum['send_sum'] * 1;
        $keySendSumData['day_rate'] = $this->_getRate($yesDayNum['send_sum'], 
            $lastDayNum['send_sum']);
        $keySendSumData['week_rate'] = $this->_getRate(
            $yesDayNum['send_week_sum'], $yesDayNum['send_lastweek_sum']);
        $keySendSumData['month_rate'] = $this->_getRate(
            $yesDayNum['send_month_sum'], $yesDayNum['send_lastmonth_sum']);
        
        // 发送人数
        $keySendNumData = array();
        $keySendNumData['count'] = $yesDayNum['send_num'] * 1;
        $keySendNumData['day_rate'] = $this->_getRate($yesDayNum['send_num'], 
            $lastDayNum['send_num']);
        $keySendNumData['week_rate'] = $this->_getRate(
            $yesDayNum['send_week_num'], $yesDayNum['send_lastweek_num']);
        $keySendNumData['month_rate'] = $this->_getRate(
            $yesDayNum['send_month_num'], $yesDayNum['send_lastmonth_num']);
        
        // 人均次数
        $keyPerNumData = array();
        $keyPerNumData['count'] = $yesDayNum['per_num'] * 1;
        $keyPerNumData['day_rate'] = $this->_getRate($yesDayNum['per_num'], 
            $lastDayNum['per_num']);
        $keyPerNumData['week_rate'] = $this->_getRate(
            $yesDayNum['per_week_num'], $yesDayNum['per_lastweek_num']);
        $keyPerNumData['month_rate'] = $this->_getRate(
            $yesDayNum['per_month_num'], $yesDayNum['per_lastmonth_num']);
        
        return array(
            'keySendSumData' => $keySendSumData, 
            'keySendNumData' => $keySendNumData, 
            'keyPerNumData' => $keyPerNumData);
    }

    public function _getRate($num = 0, $last_num = 0) {
        if ($num == 0) {
            return '0';
        }
        if ($last_num == 0) {
            return '--';
        }
        return strval(round(($num - $last_num) / $last_num));
    }

    public function _getPer($num = 0, $last_num = 0) {
        if ($num == 0) {
            return '0';
        }
        if ($last_num == 0) {
            return '--';
        }
        return strval(ceil($num / $last_num));
    }
    
    // 微信渠道统计
    public function channel() {
        $node_id = $this->nodeId;
        $where = "node_id='" . $node_id . "'";
        $channelInfo = M('tchannel')->where(
            array(
                'node_id' => $node_id, 
                'type' => self::CHANNEL_TYPE_WX, 
                'sns_type' => self::CHANNEL_SNS_TYPE_WX))->find();
        $channel_id = $channelInfo['id'];
        $_GET['channel_id'] = $channel_id;
        $this->redirect('LabelAdmin/ChannelBatchList/Chart', 
            array(
                'id' => $channel_id), 0);
        
        $ChannelBatchList = A('LabelAdmin/ChannelBatchList');
        $ChannelBatchList->Chart();
    }
}