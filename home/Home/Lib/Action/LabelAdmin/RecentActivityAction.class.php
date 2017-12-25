<?php

class RecentActivityAction extends BaseAction {

    public function index() {
        $dataList = array();
        // 10条最近的读活动
        $newList = M('tmarketing_info')->field("*")
            ->where(
            "node_id in({$this->nodeIn()}) and batch_type not in ('14','38','39')")
            ->order("add_time DESC")
            ->limit(10)
            ->select();
        if ($newList)
            $dataList = array_merge($dataList, $newList);
        
        $dataList = array_sort($dataList, 'add_time');
        
        if ($dataList) {
            foreach ($dataList as $k => $v) {
                $dataList[$k]['is_mem_batch'] = 'N';
                if ($v['type'] == '2' || $v['type'] == '3' || $v['type'] == '10') {
                    if ($v['is_cj'] == '1') {
                        $rule_id = M('tcj_rule')->where(
                            array(
                                'batch_type' => $v['type'], 
                                'batch_id' => $v['id'], 
                                'node_id' => $v['node_id'], 
                                'status' => '1'))->getField('id');
                        $mem_batch = M('tcj_batch')->where(
                            array(
                                'cj_rule_id' => $rule_id, 
                                'member_batch_id' => array(
                                    'neq', 
                                    '')))->find();
                        if ($mem_batch) {
                            $dataList[$k]['is_mem_batch'] = 'Y';
                        }
                    }
                }
                // 世界杯参与数
                if ($v['type'] == 23) {
                    $dataList[$k]['cj_count'] = M('tworld_cup_login_trace')->where(
                        "batch_id={$v['id']}")->count();
                }
            }
        }
        
        node_log("首页+最近10个活动");
        // dump($dataList);exit;
        $type = C('BATCH_TYPE_NAME');
        $this->assign('data', $dataList);
        $this->assign('type', $type);
        $this->display();
    }
}