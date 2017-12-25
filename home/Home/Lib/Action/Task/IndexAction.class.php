<?php
// 任务
class IndexAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
    }
    
    // 任务弹窗内容
    public function duty() {
        $id = I('get.id', null, 'mysql_real_escape_string');
        $popid = I('get.popid', null, 'mysql_real_escape_string');
        // $id='1';
        $task = M('Ttask_param')->field('begin_time,end_time')
            ->where(array(
            'task_id' => $id))
            ->find();
        $this_time = date('YmdHis');
        if ($this_time < $task['begin_time'] || $this_time > $task['end_time'])
            $this->error('任务不在有效期！');
        
        $row = M('ttask_progress')->field('task_data')
            ->where(
            array(
                'task_id' => $id, 
                'node_id' => $this->node_id))
            ->find();
        $this->assign("id", $popid);
        $this->assign("image", $row['task_data']);
        $this->display();
    }
}
?>