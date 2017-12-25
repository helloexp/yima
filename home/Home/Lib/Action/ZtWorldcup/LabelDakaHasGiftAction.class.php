<?php
import('@.Action.Label.MyBaseAction');

class LabelDakaHasGiftAction extends MyBaseAction {

    const BATCH_TYPE = '23';

    public $today = '';

    public function _initialize() {
        $this->today = date('Ymd');
        C(require (CONF_PATH . 'Label/config.php'));
        parent::_initialize();
        
        if ($this->batch_type != self::BATCH_TYPE) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
    }

    public function index() {
        // 访问量
        import('@.Vendor.ClickStat');
        $opt = new ClickStat();
        $id = $this->id;
        // echo $this->batc;
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
        if ($row['is_cj'] == '1') {
            $prize_list = (array) M()->table("tcj_batch a")->join(
                'tbatch_info b on a.activity_no = b.batch_no')
                ->join('tcj_rule c on c.id=a.cj_rule_id')
                ->where(
                array(
                    'a.batch_id' => $this->batch_id))
                ->order('c.param1 asc')
                ->field('a.*, b.batch_name, b.batch_img, c.param1 total_chance')
                ->select();
        }
        
        // 获取天数
        $map = array(
            'm_id' => $this->batch_id);
        $count = M('tworld_cup_events_day')->where($map)->count();
        if ($count == 0)
            $map['m_id'] = array(
                'exp', 
                'is null');
        
        $dayList = M('tworld_cup_events_day')->where($map)
            ->order('match_date')
            ->getField("match_date", true);
        $this->assign('dayList', $dayList);
        $today = $this->today;
        // 查询今天是否是签到日
        $count = in_array($today, $dayList);
        $errmsg = '';
        if (! $count) {
            $errmsg = '今天' . dateformat($today, 'm.d') . '不是签到日。';
        }
        $this->assign('errmsg', $errmsg);
        
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
        
        $verify_code = I('post.verify_code', '', 'trim');
        if (md5($verify_code) != session('verify_cj')) {
            $this->ajaxReturn("error", "验证码错误！", 0);
        }
        
        session('verify_cj', null);
        
        $mobile = I('post.mobile', '', 'trim');
        if (! is_numeric($mobile) || strlen($mobile) != '11')
            $this->ajaxReturn("error", "您的手机号错误！", 0);
        if (empty($id))
            $this->ajaxReturn("error", "错误的请求！", 0);
        
        $today = $this->today;
        // 今天签到
        
        // 查询最后一次签到次数
        
        $oldLogin = M('tworld_cup_login_trace')->where(
            array(
                'phone_no' => $mobile, 
                'batch_id' => $this->batch_id))
            ->order("login_time desc")
            ->find();
        
        // 查询今天是否是签到日
        $map = array(
            'm_id' => $this->batch_id);
        $count = M('tworld_cup_events_day')->where($map)->count();
        if ($count == 0)
            $map['m_id'] = array(
                'exp', 
                'is null');
        
        $map['match_date'] = $today;
        $count = M('tworld_cup_events_day')->where($map)->find();
        if (! $count) {
            $traceInfo = $this->_getTraceInfo($mobile, $oldLogin['login_days']);
            $msg = '今天' . dateformat($today, 'm.d') . '不是签到日。';
            $otherMsg = $traceInfo['otherMsg'];
            $this->ajaxReturn("error", 
                array(
                    'msg' => $msg, 
                    'other' => $otherMsg), 0);
        }
        // 查询今天是否已经签到
        $count = M('tworld_cup_login_trace')->where(
            array(
                'phone_no' => $mobile, 
                'batch_id' => $this->batch_id, 
                'login_time' => array(
                    'like', 
                    $today . '%')))->find();
        if ($count) {
            $traceInfo = $this->_getTraceInfo($mobile, $oldLogin['login_days']);
            $msg = '今天已经签到过。' . $traceInfo['msg_1'] . $traceInfo['msg_2'];
            $otherMsg = $traceInfo['otherMsg'];
            $this->ajaxReturn("error", 
                array(
                    'msg' => $msg, 
                    'other' => $otherMsg), 1);
        }
        
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
        if ($query_arr['is_cj'] != '1') {
            $this->ajaxReturn("error", "未查询到抽奖活动！[01]", 0);
        }
        
        // 查询抽奖规则表
        $cj_arr = M('tcj_rule')->where(
            array(
                'batch_id' => $this->batch_id))->select();
        if (! $cj_arr) {
            $this->ajaxReturn("error", "未查询到抽奖活动！[02]", 0);
        }
        
        // 取得上一个赛日是否签到
        $map = array(
            'm_id' => $this->batch_id);
        $count = M('tworld_cup_events_day')->where($map)->count();
        if ($count == 0)
            $map['m_id'] = array(
                'exp', 
                'is null');
        
        $map['match_date'] = array(
            'lt', 
            $today);
        
        $yestoday = M('tworld_cup_events_day')->where($map)
            ->order('match_date desc')
            ->getField('match_date');
        $arr = array();
        if ($yestoday) {
            // 查询是否已经签到
            $arr = M('tworld_cup_login_trace')->where(
                array(
                    'phone_no' => $mobile, 
                    'batch_id' => $this->batch_id, 
                    'login_time' => array(
                        'like', 
                        $yestoday . '%')))->find();
        }
        // 连续签到
        if ($arr) {
            $login_days = $arr['login_days'] + 1;
        } else {
            $login_days = 1;
        }
        
        // 如果签到超过一定的数量,则参加抽奖规则
        $cj_rule_id = '';
        foreach ($cj_arr as $v) {
            if (intval($login_days) === intval($v['param1'])) {
                $cj_rule_id = $v['id'];
                Log::write(
                    '连续签到得到抽奖机会：手机号[' . $mobile . ']' . '天数[' . $login_days . ']' .
                         'cj_rule_id[' . $cj_rule_id . ']');
                break;
            }
        }
        
        // 插入签到表
        $data = array(
            'batch_id' => $this->batch_id, 
            'phone_no' => $mobile, 
            'login_time' => $today . date('His'), 
            'login_days' => $login_days, 
            'label_id' => $this->id);
        M()->startTrans();
        $result = M('tworld_cup_login_trace')->add($data);
        if (! $result) {
            M()->rollback();
            $this->ajaxReturn("error", "签到失败[01]");
        }
        // M()->rollback();
        M()->commit();
        
        $traceInfo = $this->_getTraceInfo($mobile, $login_days);
        $msg_1 = $traceInfo['msg_1'];
        $msg_2 = $traceInfo['msg_2'];
        $otherMsg = $traceInfo['otherMsg'];
        // 如果没有抽奖机会
        if (! $cj_rule_id) {
            $msg = '感谢您签到成功，' . $msg_1;
            
            Log::write('签到成功。手机号[' . $mobile . ']' . '天数[' . $login_days . ']');
            
            $this->ajaxReturn("success", 
                array(
                    'msg' => $msg, 
                    'other' => $otherMsg), '1');
            exit();
        }
        
        // session('verify_cj',null);
        import('@.Vendor.ChouJiang');
        $choujiang = new ChouJiang($id, $mobile, $cj_rule_id);
        $resp = $choujiang->send_code();
        
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            
            $cj_name = $resp['batch_name'];
            $msg = '感谢您签到成功，' . $msg_1 . "恭喜您抽中" . $cj_name .
                 ",奖品将以短彩信发送至您的手机，请注意查收！" . $msg_2;
        } else {
            if ($resp['resp_id'] == '1005') {
                $resp = '今天您已经参与过抽奖，不能再抽了！';
            } elseif ($resp['resp_id'] == '1016') {
                $resp = '您已经参与过该抽奖活动，不能再抽了！';
            } else {
                $resp = '很遗憾，未中奖,感谢您的参与！';
            }
            // $resp = '很遗憾，未中奖,感谢您的参与！';
            
            $msg = '感谢您签到成功，' . $msg_1 . $resp . $msg_2;
        }
        Log::write("手机号[" . $mobile . "]签到抽奖结果：" . $msg);
        
        $this->ajaxReturn("success", 
            array(
                'msg' => $msg, 
                'other' => $otherMsg), '1');
    }

    public function _getTraceInfo($mobile, $login_days) {
        $map = array(
            'm_id' => $this->batch_id);
        $count = M('tworld_cup_events_day')->where($map)->count();
        if ($count == 0)
            $map['m_id'] = array(
                'exp', 
                'is null');
        
        $dayList = M('tworld_cup_events_day')->where($map)
            ->order('match_date')
            ->getField("match_date", true);
        // 查询已签到数
        // 查询是否已经签到
        $checkInArr = M('tworld_cup_login_trace')->where(
            array(
                'phone_no' => $mobile, 
                'batch_id' => $this->batch_id))
            ->field('substr(login_time,"1","8") as login_time')
            ->select();
        if ($checkInArr) {
            $checkInArr = array_valtokey($checkInArr, 'login_time', 
                'login_time');
        }
        
        $otherMsg = '
                <div class="cjText-calendarTable">
                	<table class="calendarTable" cellpadding="0" cellspacing="0">
                          <tbody><tr><th colspan="5">您已签到的日期</th></tr>';
        $otherMsg .= '<tr>';
        foreach ($dayList as $k => $vo) {
            if ($k % 5 == 0 && $k != 0) {
                $otherMsg .= '</tr></tr>';
            }
            if (in_array($vo, $checkInArr)) {
                $otherMsg .= '<td class="openhover">' . dateformat($vo, 'm.d') .
                     '</td>';
            } else {
                $otherMsg .= '<td >' . dateformat($vo, 'm.d') . '</td>';
            }
        }
        $otherMsg .= '</tr>';
        $otherMsg .= '</tbody></table></div>';
        // 查询出下一个签到奖品
        
        // 查询抽奖规则表
        $cj_arr = M('tcj_rule')->where(
            array(
                'batch_id' => $this->batch_id, 
                'param1' => array(
                    'gt', 
                    $login_days)))
            ->order("param1")
            ->find();
        
        $msg_1 = '您已经连续签到' . $login_days . '次了。';
        $msg_2 = '';
        if ($cj_arr) {
            $cj_name = M()->table('tcj_batch a')
                ->join('tbatch_info b on b.batch_no=a.activity_no')
                ->where("a.cj_rule_id= '" . $cj_arr['id'] . "'")
                ->getField('batch_name');
            $msg_2 = '继续签到' . ($cj_arr['param1'] - $login_days) . '次您将有机会获得' .
                 $cj_name;
        }
        
        return array(
            'msg_1' => $msg_1, 
            'msg_2' => $msg_2, 
            'otherMsg' => $otherMsg);
    }
    /*
     * public function ajaxReturn($status,$msg,$code){
     * C('show_page_trace',true); tag('view_end');
     * parent::ajaxReturn($status,$msg,$code); }
     */
}