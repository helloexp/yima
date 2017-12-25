<?php
import('@.Action.Label.MyBaseAction');

class LabelGoalHasGiftAction extends MyBaseAction {

    public function _initialize() {
        C(require (CONF_PATH . 'Label/config.php'));
        parent::_initialize();
    }

    public function index() {
        if ($this->batch_type != '24') {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 访问量
        import('@.Vendor.ClickStat');
        $opt = new ClickStat();
        $id = $this->id;
        $opt->updateStat($id);
        
        // 活动
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(
            array(
                'id' => $this->batch_id))->find();
        
        // 获取竞猜场次信息
        $team_arr = M('tworld_cup_team_info')->getField('team_id, team_name');
        $events_info = M('tworld_cup_events')->find($row['defined_one_name']);
        $events_info['team1_name'] = $team_arr[$events_info['team1_id']];
        $events_info['team2_name'] = $team_arr[$events_info['team2_id']];
        
        // 取奖品信息
        $prize_list = array();
        $level_arr = array(
            '1' => '一', 
            '2' => '二', 
            '3' => '三');
        if ($row['is_cj'] == '1') {
            $prize_list = (array) M()->table("tcj_batch a")->join(
                'tbatch_info b on a.activity_no = b.batch_no')
                ->where(
                array(
                    'a.batch_id' => $this->batch_id))
                ->order('a.award_level asc')
                ->field('a.*, b.batch_name, b.batch_img')
                ->select();
            
            foreach ($prize_list as &$info) {
                $info['level'] = $level_arr[$info['award_level']] . '等奖';
            }
        }
        
        // 是否过期
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('row', $row);
        $this->assign('events_info', $events_info);
        $this->assign('prizeData', $prize_list);
        
        $this->display(); // 输出模板
    }

    public function submit() {
        $id = $this->id;
        if (! $this->isPost()) {
            $this->ajaxReturn("error", "非法提交！", 0);
        }
        
        $verify = I('post.verify', '', 'trim');
        if (md5($verify) != session('verify_cj')) {
            $this->ajaxReturn("error", "验证码错误！", 0);
        }
        
        session('verify_cj', null);
        
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->ajaxReturn("error", "该活动不在有效期之内！", 0);
            // 是否抽奖
        $query_arr = M('tmarketing_info')->field(
            'is_cj,start_time,end_time,defined_one_name')
            ->where(
            array(
                'id' => $this->batch_id, 
                'batch_type' => $this->batch_type))
            ->find();
        if ($query_arr['is_cj'] != '1')
            $this->ajaxReturn("error", "未查询到抽奖活动！", 0);
            
            // 获取最后一条流水
        $goal_trace = M('tworld_cup_goal_trace')->where(
            array(
                'session_id' => $query_arr['defined_one_name']))
            ->order('add_time desc')
            ->find();
        
        // 是否有进球，是否可以抽奖
        $last_goal_time = $goal_trace['add_time'];
        
        if ($last_goal_time == '')
            $this->ajaxReturn("error", "还没有进球，不能抽奖了！", 0);
        
        $timediff = time() - strtotime($last_goal_time);
        if ($timediff > 60 * 10)
            $this->ajaxReturn("error", "距离上次进球已经超过10分钟，不能抽奖了！", 0);
        
        $mobile = I('post.mobile', '', 'trim');
        if (! is_numeric($mobile) || strlen($mobile) != '11')
            $this->ajaxReturn("error", "您的手机号错误！", 0);
        if (empty($id))
            $this->ajaxReturn("error", "错误的请求！", 0);
            
            // 判断是否有参与该进球的记录
        $count = M('tworld_cup_goalgift_trace')->where(
            array(
                'batch_id' => $this->batch_id, 
                'phone' => $mobile, 
                'goaltrace_id' => $goal_trace['id']))->count();
        if ($count > 0)
            $this->ajaxReturn("error", "您已参加该进球的抽奖了！下个进球再来吧！", 0);
        
        $data = array(
            'batch_id' => $this->batch_id, 
            'phone' => $mobile, 
            'session_id' => $goal_trace['session_id'], 
            'goaltrace_id' => $goal_trace['id'], 
            'add_time' => date('YmdHis'));
        M('tworld_cup_goalgift_trace')->add($data);
        
        import('@.Vendor.ChouJiang');
        $choujiang = new ChouJiang($id, $mobile);
        $resp = $choujiang->send_code();
        
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            $cj_msg = "恭喜您抽中奖品（{$resp['batch_name']}）,奖品将以短彩信发送至您的手机，请注意查收！";
            
            if ($resp['award_level'] == '1') { // 一等奖
                $cj_msg = "恭喜您获得一等奖（{$resp['batch_name']}）,奖品将以短彩信发送至您的手机，请注意查收！";
            } elseif ($resp['award_level'] == '2') { // 二等奖
                $cj_msg = "恭喜您获得二等奖（{$resp['batch_name']}）,奖品将以短彩信发送至您的手机，请注意查收！";
            } elseif ($resp['award_level'] == '3') { // 三等奖
                $cj_msg = "恭喜您获得三等奖（{$resp['batch_name']}）,奖品将以短彩信发送至您的手机，请注意查收！";
            }
            $this->ajaxReturn("success", $cj_msg, 1);
        } else {
            
            if ($resp['resp_id'] == '1005') {
                $resp = '今天您已经参与过抽奖，不能再抽了！';
            } elseif ($resp['resp_id'] == '1016') {
                $resp = '您已经参与过该抽奖活动，不能再抽了！';
            } else {
                $resp = '很遗憾，未中奖,感谢您的参与！';
            }
            $this->ajaxReturn("error", $resp, 0);
        }
    }

    public function query() {
        if (! $this->isPost())
            $this->ajaxReturn("error", "非法提交！", 0);
            
            // 有效期校验
        $id = $this->id;
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->ajaxReturn("error", "该活动不在有效期之内！", 0);
            
            //
        $query_arr = M('tmarketing_info')->field(
            'is_cj,start_time,end_time,defined_one_name')
            ->where(
            array(
                'id' => $this->batch_id, 
                'batch_type' => $this->batch_type))
            ->find();
        if ($query_arr['is_cj'] != '1')
            $this->ajaxReturn("error", "未查询到抽奖活动！", 0);
            
            // 返回数组
            // $result = array();
            // 查询赛事情况
        $events = M('tworld_cup_events')->find($query_arr['defined_one_name']);
        $result['events_status'] = $events['result'];
        
        $b_time = strtotime($events['begin_time']);
        $now = time();
        $flash_time = 0;
        
        // 是否有进球，是否可以抽奖
        $last_goal_time = M('tworld_cup_goal_trace')->where(
            array(
                'session_id' => $query_arr['defined_one_name'], 
                'add_time' => array(
                    'GT', 
                    date('YmdHis', time() - 10 * 60))))->max('add_time');
        
        if ($events['result'] != 0 && $last_goal_time == '') {
            $result = 3;
        } else {
            // 未开始
            if ($b_time > $now) {
                $result = 1;
                $flash_time = $b_time - $now;
            }  // 已开始
else {
                $result = 2;
                // $flash_time = $last_goal_time == '' ? 10 :
                // (strtotime($last_goal_time)+10*60-$now);
                $flash_time = $last_goal_time == '' ? 10 : 30;
                // $flash_time
            }
        }
        
        $this->ajaxReturn("success", 
            array(
                'events_status' => $result, 
                'flash_time' => $flash_time * 1000, 
                'team1_goal' => $events['team1_goal'], 
                'team2_goal' => $events['team2_goal'], 
                'goal_flag' => ! ($last_goal_time == '')), 1);
    }
}