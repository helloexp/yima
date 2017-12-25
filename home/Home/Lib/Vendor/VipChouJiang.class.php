<?php

class VipChouJiang {

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

    public $batch_arr = array();

    public $channel_arr = array();

    public $label_arr = array();

    public $optType;

    public $cj_type;
    // 1发文 2点赞
    public function __construct($id, $mobile, $full_id = null, $type) {
        $this->id = $id;
        $this->mobile = $mobile;
        $this->today = Date('Ymd');
        $this->time = Date('His');
        $this->full_id = $full_id;
        if (empty($this->full_id)) {
            $this->full_id = $id;
        }
        $full_arr = explode(',', $this->full_id);
        $fwh = array(
            'id' => array(
                'in', 
                $full_arr));
        $farr = M('tbatch_channel')->where($fwh)->select();
        
        $batch_arr = array();
        foreach ($farr as $fv) {
            - $batch_arr[] = $fv['batch_id'];
            $channel_arr[] = $fv['channel_id'];
            $label_arr[] = $fv['id'];
        }
        $this->batch_arr = $batch_arr;
        $this->channel_arr = $channel_arr;
        $this->label_arr = $label_arr;
        $this->cj_type = $type;
    }
    
    // 获取标签详情
    public function getLabel() {
        if (empty($this->id) || empty($this->mobile)) {
            $this->resp_msg = '错误请求！';
            return false;
        }
        if (! is_numeric($this->mobile) || strlen($this->mobile) != '11') {
            $this->resp_msg = '手机号错误！';
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
            'status' => '1', 
            'type' => $this->cj_type);
        
        $result = $cj_rule->where($cj_map)->find();
        if ($result['jp_set_type'] != '') {
            $this->award_type = $result['jp_set_type'];
            $this->total_rate = $result['total_chance'];
            $this->cj_rule_id = $result['id'];
            $this->phone_total_count = $result['phone_total_count'];
            $this->phone_day_count = $result['phone_day_count'];
            $this->phone_total_part = $result['phone_total_part'];
            $this->phone_day_part = $result['phone_day_part'];
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
                'cj_rule_id' => $this->cj_rule_id); // print_r($send_arr);die;
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
                return $iresp;
            }
        } else {
            // 无抽奖资格
            Log::write(print_r($this->resp_msg, true));
            return '未中奖,感谢您的参与';
        }
    }
    
    // 更新抽奖统计
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
        M('tdaystat')->where($arr)->setInc($this->optType, 1);
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
}