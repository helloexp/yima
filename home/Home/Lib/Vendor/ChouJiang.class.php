<?php

class ChouJiang {

    public $id = '';
    // 标签id
    public $mobile = '';
    // 手机号
    public $today = '';
    // 当前日期
    public $batch_id = '';
    // 活动号
    public $channel_id = '';
    // 渠道号
    public $batch_type = '';
    // 活动类型
    public $status = '1';
    // 中奖状态 1-未中奖
    public $time = '';
    // 当前时间
    public $award_type;
    // 类型 1单 2多
    public $total_rate;
    // 总总奖率
    public $node_id;

    public $resp_msg;

    public $cj_white_black;

    public $cj_rule_id;

    public $phone_total_count;
    // 每个手机总中奖次数
    public $phone_day_count;
    // 每个手机每天中奖次数
    public $phone_total_part;
    // 每个手机总抽奖次数
    public $phone_day_part;
    // 每个手机每天抽奖次数
    public $full_id;

    public $parent_id;

    public $batch_arr = array();

    public $channel_arr = array();

    public $label_arr = array();

    public $optType;

    public $param1;
    // 非标流水号参与次数
    public $ticket_seq;
    // 非标流水号
    public $other_arr;
    // 其他参数
    public $join_mode;
    // 参与方式 0：手机号 1：微信号
    public function __construct($id, $mobile, $full_id = null, $ticket_seq = null, 
        $other_arr = array()) {
        $this->id = $id;
        $this->mobile = $mobile;
        $this->today = Date('Ymd');
        $this->time = Date('His');
        $this->full_id = $full_id;
        $this->ticket_seq = $ticket_seq;
        if (empty($this->full_id)) {
            $this->full_id = $id;
        }
        $full_arr = explode(',', $this->full_id);

        $this->parent_id = count($full_arr) > 1 ? $full_arr[0] : '0';

        $fwh = array(
            'id' => array(
                'in', 
                $full_arr));
        $farr = M('tbatch_channel')->where($fwh)->field('id, node_id, batch_id, batch_type, channel_id')->select();
        
        $batch_arr = array();
        foreach ($farr as $fv) {
            $batch_arr[] = $fv['batch_id'];
            $channel_arr[] = $fv['channel_id'];
            $label_arr[] = $fv['id'];
            
            if($fv['id'] == $this->id){
                $this->channel_id = $fv['channel_id'];
                $this->batch_id = $fv['batch_id'];
                $this->batch_type = $fv['batch_type'];
                $this->node_id = $fv['node_id'];
            }
        }
        
        if (! empty($other_arr) && is_array($other_arr)) {
            $this->other_arr = $other_arr;
        }
        
        $this->batch_arr = $batch_arr;
        $this->channel_arr = $channel_arr;
        $this->label_arr = $label_arr;
    }
    
    // 获取标签详情
    public function getLabel() {
        if (empty($this->id)) {
            $this->resp_msg = '错误请求！';
            return false;
        }
        $model = M('tbatch_channel');
        $map = array(
            'id' => $this->id);
        $result = $model->where($map)->find();
        if (! $result) {
            $this->resp_msg = '活动不存在！';
            return false;
        }
        
        $this->batch_id = $result['batch_id'];
        $this->channel_id = $result['channel_id'];
        $this->batch_type = $result['batch_type'];
        $this->node_id = $result['node_id'];
        $nodem = M('tnode_info');
        $this->cj_white_black = $nodem->where(
            array(
                'node_id' => $this->node_id))->getField('cj_white_black');
    }
    
    // 获取活动信息
    public function getBatch() {
        $query = $this->getLabel();
        if ($query === false) {
            return false;
        }
        $model = M('tmarketing_info');
        $map = array(
            'id' => $this->batch_id, 
            'batch_type' => $this->batch_type);
        $query_arr = $model->where($map)->find();
        
        if (! $query_arr) {
            $this->resp_msg = "活动不存在";
            return false;
        }
        $this->join_mode = $query_arr['join_mode'];
        
        $wechatLogin = false;
        if (! $this->join_mode) {
            $result = M('tbatch_info')->where(
                array(
                    'm_id' => $this->batch_id, 
                    '_string' => "ifnull(card_id, '') != ''"))->getField("id");
            if ($result) { // 有微信卡券 也是微信登录
                $wechatLogin = true;
            }
        }
        
        if ($this->join_mode || $wechatLogin) {
            $otherarr = $this->other_arr;
            if (empty($otherarr['wx_open_id'])) {
                $this->resp_msg = '错误请求，只有微信粉丝才能参加！';
            }
            if (empty($otherarr['wx_nick'])) {
                $this->resp_msg = '错误请求,只有微信粉丝才能参加！';
            }
        } else { // 校验手机号
            if (empty($this->mobile) || ! is_numeric($this->mobile) ||
                 strlen($this->mobile) != '11') {
                $this->resp_msg = '手机号错误！';
                return false;
            }
        }
        
        if ($query_arr['status'] != '1') {
            $this->resp_msg = '状态不正常！' . $model->getLastSql();
            return false;
        }
        if ($this->today . $this->time > $query_arr['end_time'] ||
             $this->today . $this->time < $query_arr['start_time']) {
            $this->resp_msg = '活动不在有效期！';
            return false;
        }
        if ($query_arr['is_cj'] == '0') {
            $this->resp_msg = '非抽奖活动！';
            return false;
        }
        // 抽奖规则配置
        $cj_rule = M('tcj_rule');
        $cj_map = array(
            'node_id' => $this->node_id, 
            'batch_type' => $this->batch_type, 
            'batch_id' => $this->batch_id, 
            'status' => '1');
        if ($this->cj_rule_id) {
            $cj_map['id'] = $this->cj_rule_id;
        }
        $result = $cj_rule->where($cj_map)->find();
        if ($result['jp_set_type'] != '') {
            $this->award_type = $result['jp_set_type'];
            $this->total_rate = $result['total_chance'];
            $this->cj_rule_id = $result['id'];
            $this->phone_total_count = $result['phone_total_count'];
            $this->phone_day_count = $result['phone_day_count'];
            $this->phone_total_part = $result['phone_total_part'];
            $this->phone_day_part = $result['phone_day_part'];
            $this->param1 = $result['param1'];
        } else {
            $this->resp_msg = '未配置抽奖类型！';
            return false;
        }
    }
    // 抽奖条件
    public function isAllowCj() {
        $query = $this->getBatch();
        if ($query === false) {
            return false;
        }
        $checkModel = M('tcj_trace');
        // 黑白名单
        $phoneModel = M('tcj_white_blacklist');
        $w_map = array(
            'node_id' => $this->node_id, 
            'phone_no' => $this->mobile);
        $p_query = $phoneModel->where($w_map)->find();
        
        if ($this->cj_white_black == '2') {
            if ($p_query) {
                $this->resp_msg = '该手机号不允许抽奖！';
                return false;
            }
        } elseif ($this->cj_white_black == '1') {
            if (! $p_query) {
                $this->resp_msg = '该手机号不允许抽奖！';
                return false;
            }
        }
        
        return true;
    }
    
    // 抽奖发码
    public function send_code() {
        $resp = $this->isAllowCj();
        $this->UpdateRecord('cj_count');
        if ($resp === true) {
            // 抽奖接口
            import("@.Vendor.CjInterface");
            $req = new CjInterface();
            $send_arr = array(
                'node_id' => $this->node_id, 
                'batch_id' => $this->batch_id,  // 活动id
                'award_type' => $this->award_type,  // 单，多类型
                'award_times' => $this->phone_day_count,  // 每日限制次数
                'award_count' => $this->phone_total_count,  // 总总将次数
                'day_part' => $this->phone_day_part, 
                'total_part' => $this->phone_total_part, 
                'phone_no' => $this->mobile, 
                'label_id' => $this->id, 
                'channel_id' => $this->channel_id, 
                'batch_type' => $this->batch_type, 
                'total_rate' => $this->total_rate, 
                'ip' => get_client_ip(), 
                'cj_rule_id' => $this->cj_rule_id, 
                'full_id' => $this->full_id);
            if ($this->param1 != '' && ! empty($this->ticket_seq)) {
                $send_arr['ticket_limit_num'] = $this->param1;
                $send_arr['ticket_seq'] = $this->ticket_seq;
            }
            if ($this->other_arr) {
                $otherarr = $this->other_arr;
                if ($otherarr['wx_wap_ranking_id']) {
                    $send_arr['wx_wap_ranking_id'] = $otherarr['wx_wap_ranking_id'];
                }
                if ($otherarr['wx_cjcate_id']) {
                    $send_arr['wx_cjcate_id'] = $otherarr['wx_cjcate_id'];
                }
                if ($otherarr['open_id']) {
                    $send_arr['open_id'] = $otherarr['open_id'];
                }
                if (! empty($otherarr['wx_open_id'])) {
                    log_write('wo dao choujiang  jiekou  li le  003');

                    $send_arr['wx_open_id'] = $otherarr['wx_open_id'];
                }
                if (! empty($otherarr['wx_nick'])) {
                    $send_arr['wx_nick'] = $otherarr['wx_nick'];
                }
                // 补充付满送的pay_token字段
                if (! empty($otherarr['pay_token'])) {
                    $send_arr['pay_token'] = $otherarr['pay_token'];
                }
                if(empty($send_arr['pay_token']))
                $send_arr['pay_token'] =I('pay_token',null);
            }
            $iresp = $req->cj_send($send_arr);
            if ($iresp['resp_id'] == '0000') {
                // 更新统计数据
                $this->UpdateRecord('send_count');
                // 查询奖品名称
                $iresp['batch_name'] = M('tbatch_info')->where(
                    "batch_no='" . $iresp['batch_no'] . "'")->getField(
                    'batch_short_name');
                return $iresp;
            } else {
                if ($iresp['resp_id'] == '1005' 
                    || $iresp['resp_id'] == '1016' 
                    || $iresp['resp_id'] == '1001' //奖品已发完
                    || $iresp['resp_id'] == '1003') {//当日上限
                    //strstr($haystack, $needle);
                    $start = strpos($iresp['resp_desc'], '[');
                    $end = strpos($iresp['resp_desc'], ']');
                    $length = $end - $start;
                    $str = substr($iresp['resp_desc'], ($start + 1), ($length - 1));
                    //log_write('字符串'.$str);
                    $getUpdateInfo = S('is_over_cj_times_'.$this->batch_id . ',no:' . $str);
                    //log_write('读取缓存1：' . json_encode($getUpdateInfo));
                    if($getUpdateInfo === false){
                        $getUpdateInfo = S('is_over_cj_times_'.$this->batch_id . ',no:' . $str, array('no' => $str));
                        //log_write('设置缓存2：' . json_encode($getUpdateInfo));
                    }
                }
                return $iresp;
            }
        } else {
            // 无抽奖资格
            log_write(print_r($this->resp_msg, true));
            return '未中奖,感谢您的参与';
        }
    }
    
    // 抽奖发码 异步队列
    public function sendCodeQueue() {
        $resp = $this->isAllowCj();
        $this->UpdateRecord('cj_count'); // send_count 执行多次。。
        if ($resp === true) {
            // 抽奖接口
            import("@.Vendor.CjInterface");
            $req = new CjInterface();
            $send_arr = array(
                'node_id' => $this->node_id, 
                'batch_id' => $this->batch_id,  // 活动id
                'award_type' => $this->award_type,  // 单，多类型
                'award_times' => $this->phone_day_count,  // 每日限制次数
                'award_count' => $this->phone_total_count,  // 总总将次数
                'day_part' => $this->phone_day_part, 
                'total_part' => $this->phone_total_part, 
                'phone_no' => $this->mobile, 
                'label_id' => $this->id, 
                'channel_id' => $this->channel_id, 
                'batch_type' => $this->batch_type, 
                'total_rate' => $this->total_rate, 
                'ip' => get_client_ip(), 
                'cj_rule_id' => $this->cj_rule_id);
            if ($this->param1 != '' && ! empty($this->ticket_seq)) {
                $send_arr['ticket_limit_num'] = $this->param1;
                $send_arr['ticket_seq'] = $this->ticket_seq;
            }
            if ($this->other_arr) {
                $otherarr = $this->other_arr;
                if ($otherarr['wx_wap_ranking_id']) {
                    $send_arr['wx_wap_ranking_id'] = $otherarr['wx_wap_ranking_id'];
                }
                if ($otherarr['wx_cjcate_id']) {
                    $send_arr['wx_cjcate_id'] = $otherarr['wx_cjcate_id'];
                }
                if ($otherarr['open_id']) {
                    $send_arr['open_id'] = $otherarr['open_id'];
                }
                if (! empty($otherarr['wx_open_id'])) {
                    $send_arr['wx_open_id'] = $otherarr['wx_open_id'];
                }
                if (! empty($otherarr['wx_nick'])) {
                    $send_arr['wx_nick'] = $otherarr['wx_nick'];
                }
                // 补充付满送的pay_token字段
                if (! empty($otherarr['pay_token'])) {
                    $send_arr['pay_token'] = $otherarr['pay_token'];
                }
                if(empty($send_arr['pay_token']))
                $send_arr['pay_token'] =I('pay_token',null);
            }
            $iresp = $req->cjSendQueue($send_arr);
            if ($iresp['resp_id'] == '0000') {
                // 更新统计数据
                $this->UpdateRecord('send_count');
                // 查询奖品名称
                $iresp['batch_name'] = M('tbatch_info')->where(
                    "batch_no='" . $iresp['batch_no'] . "'")->getField(
                    'batch_short_name');
                return $iresp;
            } else {
                return $iresp;
            }
        } else {
            // 无抽奖资格
            log_write('活动错误：' . print_r($this->resp_msg, true));
            return array(
                'resp_id' => '-1', 
                'resp_str' => '未中奖,感谢您的参与');
        }
    }

    /**
     * todo 这个可以放在 异步操作 tmarketing_info tbatch_channel 读写操作 更新抽奖统计
     *
     * @param $optType
     * @return bool
     */
    protected function UpdateRecord($optType) {
        if (empty($optType))
            return false;
        $this->optType = $optType;
        $this->UpdateDayData();
        $this->UpdateBatch();
        $this->UpdateChannel();
        $this->UpdateLabel();
    }
    
    // 更新日统计
    protected function UpdateDayData() {
        $arr = array(
            'label_id' => $this->id, 
            'node_id' => $this->node_id, 
            'full_id' => $this->full_id,
            'day' => $this->today);
        $effectNum = M('tdaystat')->where($arr)->setInc($this->optType, 1);

        //如果影响条数为0
        if($effectNum == 0){
            $this->initTdayStat();
        }
    }
    // 更新活动统计数量
    protected function UpdateBatch() {
        $wh = array(
            'node_id' => $this->node_id, 
            'id' => $this->batch_id);
        M('tmarketing_info')->where($wh)->setInc($this->optType, 1);
    }
    // 更新渠道统计数量
    protected function UpdateChannel() {
        $wh = array(
            'node_id' => $this->node_id, 
            'id' => array(
                'in', 
                $this->channel_arr));
        M('tchannel')->where($wh)->setInc($this->optType, 1);
    }
    
    // 更新标签统计数量
    protected function UpdateLabel() {
        $wh = array(
            'node_id' => $this->node_id, 
            'id' => $this->id);
        M('tbatch_channel')->where($wh)->setInc($this->optType, 1);
    }

    // 日统计新增,并初始化cj_count为1
    protected function initTdayStat() {
        $data = array(
            'batch_type' => $this->batch_type,
            'batch_id' => $this->batch_id,
            'channel_id' => $this->channel_id,
            'day' => $this->today,
            'node_id' => $this->node_id,
            'label_id' => $this->id,
            'full_id' => $this->full_id,
            'parent_id' => $this->parent_id);
        $data[$this->optType] = 1;
        $query = M('tdaystat')->add($data);
        if (! $query){
            log_write('初始化tdaystat失败：'.print_r($data));
            //return false;
        }
    }
}