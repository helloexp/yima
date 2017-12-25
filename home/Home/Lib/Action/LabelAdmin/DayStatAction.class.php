<?php
// 日统计
class DayStatAction extends BaseAction {

    public function _initialize() {
        $this->_checkLogin();
    }

    public function index() {
        $batch_type = $this->_param('batch_type');
        $batch_id = $this->_param('batch_id');
        $channel_id = $this->_param('channel_id');
        $model = M('tdaystat');
        $map = array(
            'batch_type' => $batch_type, 
            'batch_id' => $batch_id, 
            'channel_id' => $channel_id);
        $query_arr = $model->where($map)->select();
        if (! $query_arr)
            $this->error('未查询到统计数据');
        
        $count_arr = array();
        $days_arr = array();
        $send_arr = array();
        foreach ($query_arr as $v) {
            $count_arr[] = $v['click_count'];
            $days_arr[] = $v['day'];
            $send_arr[] = $v['send_count'];
        }
        
        $count = implode(',', $count_arr);
        $days = implode(',', $days_arr);
        $send_count = implode(',', $send_arr);
        
        $this->assign('send_count', $send_count);
        $this->assign('count', $count);
        $this->assign('days', $days);
        $this->display();
    }
}