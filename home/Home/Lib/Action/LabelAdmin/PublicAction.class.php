<?php

/**
 * Created by PhpStorm. User: kk Date: 2/13 0013 Time: 21:29
 * 营销活动创建成功后的跳转页面，跳转时，需带上id U('LabelAdmin/Public/addSubmit',
 * array('id'=>$batch_id))
 */
class PublicAction extends BaseAction {

    public function addSubmit() {
        $batch_id = I('id');
        $map = array(
            'id' => $batch_id, 
            'node_id' => $this->node_id);
        $batchInfo = M('tmarketing_info')->where($map)->find();
        if (! $batch_id) {
            $this->error('无效链接！');
        }
        $this->assign('batch_id', $batch_id);
        $this->assign('batch_type', $batchInfo['batch_type']);
        $batch_edit_url = C('BATCH_EDIT_URL');
        $edit_url = $batch_edit_url[$batchInfo['batch_type']];
        $this->assign('edit_url', $edit_url);
        $this->display();
    }

    public function edit() {
        $batch_id = I('id');
        $map = array(
            'id' => $batch_id, 
            'node_id' => $this->node_id);
        $batchInfo = M('tmarketing_info')->where($map)->find();
        if (! $batch_id) {
            $this->error('无效链接！');
        }
        
        $arr = array(
            '2' => 'News', 
            '3' => 'Bm', 
            '10' => 'Answers', 
            '20' => 'Vote');
        $this->redirect(
            'LabelAdmin/' . $arr[$batchInfo['batch_type']] . '/edit', 
            array(
                'id' => $batch_id));
    }
}