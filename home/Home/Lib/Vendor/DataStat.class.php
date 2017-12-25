<?php
// 更新访问量,抽奖数参与数,流水,日统计,粉丝数
class DataStat {

    public $id;

    public $full_id;

    public $parent_id;

    public $batch_id;

    public $channel_id;

    public $batch_type;

    public $node_id;

    public $this_day;

    public $batch_arr = array();

    public $channel_arr = array();

    public $label_arr = array();

    public $optType;
    // 操作类型 1 访问量 2 中奖数 3参与数
    public $optTypeArr = array();

    public $ip_count = 0;

    public $batchTraceId = 0;

    public function __construct($id, $fullId) {
        // 判断是否是微信过来的
        /*if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'com.tencent.mm') {
                return false;
            }
        }*/
        if ($_SERVER['HTTP_USER_AGENT'] == 'Mozilla/4.0') {
            return false;
        }
        
        if (empty($id) || empty($fullId))
            return false;
        $query_arr = M('tbatch_channel')->where(
            array(
                'id' => $id))->find();
        if (! $query_arr)
            return false;
        
        $full_arr = explode(',', $fullId);
        $fwh = array(
            'id' => array(
                'in', 
                $full_arr));
        $farr = M('tbatch_channel')->where($fwh)->select();
        $batch_arr = array();
        foreach ($farr as $fv) {
            $batch_arr[] = $fv['batch_id'];
            $channel_arr[] = $fv['channel_id'];
            $label_arr[] = $fv['id'];
        }
        $this->id = $id;
        $this->full_id = $fullId;
        $this->parent_id = count($full_arr) > 1 ? $full_arr[0] : '0';
        $this->batch_id = $query_arr['batch_id'];
        $this->channel_id = $query_arr['channel_id'];
        $this->batch_type = $query_arr['batch_type'];
        $this->node_id = $query_arr['node_id'];
        $this->this_day = date('Ymd');
        $this->batch_arr = $batch_arr;
        $this->channel_arr = $channel_arr;
        $this->label_arr = $label_arr;
        $this->optType = 1;
        $this->optTypeArr = array(
            '1' => 'click_count');
    }
    
    // 调用程序
    public function UpdateRecord() {
        $this->recordSeq();
        return true;//全部走异步（数据库存储过程实现）
        $this->UpdateDayData();
        $this->UpdateBatch();
        $this->UpdateChannel();
        $this->UpdateLabel();
        $this->UpdateUv();
    }
    
    // 记录访问流水
    public function recordSeq() {
        
        $data = array(
            'batch_type' => $this->batch_type, 
            'batch_id' => $this->batch_id, 
            'channel_id' => $this->channel_id, 
            'add_time' => date('YmdHis'), 
            'node_id' => $this->node_id, 
            'ip' => get_client_ip(), 
            'full_id' => $this->full_id);
        if ($this->optType == '1') {
            $this->batchTraceId = M('tbatch_trace')->add($data);
        }
    }
    
    // 日统计新增
    protected function addDayCount() {
        $arr = array(
            'label_id' => $this->id, 
            'node_id' => $this->node_id, 
            'full_id' => $this->full_id, 
            'day' => $this->this_day);
        $query_arr = M('tdaystat')->where($arr)->find();
        if (! $query_arr) {
            $map = array(
                'batch_type' => $this->batch_type, 
                'batch_id' => $this->batch_id, 
                'channel_id' => $this->channel_id, 
                'day' => date('Ymd'), 
                'node_id' => $this->node_id, 
                'label_id' => $this->id, 
                'full_id' => $this->full_id, 
                'parent_id' => $this->parent_id);
            $query = M('tdaystat')->add($map);
            if (! $query)
                return false;
        }

        return true;
    }
    // 更新日统计
    protected function UpdateDayData() {
        $isexits = $this->addDayCount();
        if ($isexits === false)
            return false;
        
        $arr = array(
            'label_id' => $this->id, 
            'node_id' => $this->node_id, 
            'full_id' => $this->full_id, 
            'day' => $this->this_day);
        M('tdaystat')->where($arr)->setInc($this->optTypeArr[$this->optType], 1);
    }
    // 更新活动统计数量
    protected function UpdateBatch() {
        $wh = array(
            'node_id' => $this->node_id, 
            'id' => $this->batch_id);
        M('tmarketing_info')->where($wh)->setInc(
            $this->optTypeArr[$this->optType], 1);
    }
    // 更新渠道统计数量
    protected function UpdateChannel() {
        $wh = array(
            'node_id' => $this->node_id, 
            'id' => array(
                'in', 
                $this->channel_arr));
        M('tchannel')->where($wh)->setInc($this->optTypeArr[$this->optType], 1);
    }
    
    // 更新标签统计数量
    protected function UpdateLabel() {
        $wh = array(
            'node_id' => $this->node_id, 
            'id' => $this->id, 
            'channel_id' => $this->channel_id, 
            'batch_id' => $this->batch_id);
        M('tbatch_channel')->where($wh)->setInc(
            $this->optTypeArr[$this->optType], 1);
    }
    
    // 更新uv
    protected function UpdateUv() {
        if ($this->ip_count) {
            $map = array(
                'node_id' => $this->node_id, 
                'full_id' => $this->full_id, 
                'day' => $this->this_day);
            // M('tdaystat')->where($map)->save(array('uv_count'=>$uv_count));
            M('tdaystat')->where($map)->setInc('uv_count');
        }
    }
}
