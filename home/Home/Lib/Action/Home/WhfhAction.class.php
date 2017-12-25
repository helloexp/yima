<?php

class WhfhAction extends Action {

    public function index() {
        $batch_id = C('whfh');
        // 总访问统计
        $click_count = M('tmarketing_info')->where(
            array(
                'id' => $batch_id))->getField('click_count');
        
        // 排行
        $search = M('tfb_whfh_stat')->order('total_count desc')
            ->limit(20)
            ->select();
        if ($search) {
            $slice_arr = array_chunk($search, 10, true);
        }
        
        $this->assign('slice_arr', $slice_arr);
        $this->assign('click_count', $click_count);
        $this->display();
    }
}