<?php
// 选择活动编辑记录
class DfMarketChangeTraceAction extends BaseAction {

    public $_authAccessMap = '*';

    public function index() {
        $batch_no = I('batch_no');
        $batch_type = I('batch_type');
        if (! $batch_no || ! $batch_type)
            $this->error('参数错误');
        $map = array(
            "batch_id" => $batch_no, 
            "batch_type" => $batch_type);
        $list = M("tfb_df_pointshop_change_trace")->where($map)
            ->order('id desc')
            ->select();
        $batch_arr = C('BATCH_TYPE_NAME');
        $this->assign('list', $list);
        $this->assign('batch_arr', $batch_arr);
        $this->assign('empty', '<span class="empty">没有数据</span>');
        $this->display(); // 输出模板
    }
}