<?php
// 举报
class JbAction extends MyBaseAction {

    public function index() {
        $id = $this->id;
        
        $this->assign('id', $this->id);
        $this->assign('query_arr', $query_arr);
        $this->assign('resp_arr', $resp_arr);
        $this->assign('row', $row);
        $this->assign('cj_text', $cj_text);
        $this->assign('cj_check_flag', $cj_check_flag);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        // $this->assign('node_name',$nodeName);
        $this->display(); // 输出模板
    }

    public function submit() {
        // $overdue = $this->checkDate();
        // if($overdue===false)
        // $this->ajaxReturn("error","该活动不在有效期之内！",0);
        if (! $this->isPost())
            $this->ajaxReturn("error", "非法提交！", 0);
        
        $id = $this->id;
        $batch_id = $this->batch_id;
        $batch_type = $this->batch_type;
        $node_id = $this->node_id;
        
        $jb_select = I('jb_select', null);
        $inform_desc = I('inform_desc', null);
        
        if (empty($jb_select))
            $this->ajaxReturn("error", "请选择举报类型！", 0);
        if ($jb_select == '5') {
            if (empty($inform_desc))
                $this->ajaxReturn("error", "请填写举报内容！", 0);
        }
        
        $data = array(
            'label_id' => $this->id, 
            'inform_reson' => $jb_select, 
            'inform_desc' => $inform_desc, 
            'add_time' => date('YmdHis'), 
            'node_id' => $this->node_id, 
            'ip' => get_client_ip());
        $query = M('tinform_info')->add($data);
        if ($query)
            $this->ajaxReturn("success", "举报成功！", 1);
        else
            $this->ajaxReturn("erroe", "举报失败！", 0);
    }
}