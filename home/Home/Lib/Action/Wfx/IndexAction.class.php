<?php

class IndexAction extends BaseAction {

    public $_authAccessMap = "*";

    public function _initialize() {
        parent::_initialize();
        if ($this->hasPayModule('m3')) {
            redirect(U('Wfx/Fxgl/index'));
        }
    }

    public function index() {
        $this->display();
    }

    public function popform() {
        $this->assign('node_info', $this->nodeInfo);
        $this->display();
    }

    public function apply() {
        $name = I('cname', '', 'trim');
        $mobile = I('ciphone', '', 'trim');
        $email = I('cemail', '', 'trim');
        $applyExt = I('qyname', '', 'trim');
        
        if ($name == '' || $mobile == '' || $email == '' || $applyExt == '')
            $this->error('带*号的必填！');
        
        if (empty($mobile) || ! is_numeric($mobile) || strlen($mobile) != 11)
            $this->error('联系电话格式不正确！');
        if (empty($email) || ! ereg(
            "^[-a-zA-Z0-9_\.]+\@([0-9A-Za-z][0-9A-Za-z-]+\.)+[A-Za-z]{2,5}$", 
            $email)) {
            $this->error('邮箱格式不正确！');
        }
        $query_arr = array(
            'node_id' => $this->nodeId, 
            'type' => '8');
        
        $result = M('tmessage_apply')->where($query_arr)->find();
        if ($result)
            $this->error('您已经提交申请，请勿重复提交！');
        
        $send_email = "yulf@imageco.com.cn";
        $nodeInfo = M('tnode_info')->where("node_id = '{$this->nodeId}'")->find();
        $arr = array(
            "旺号：{$nodeInfo['client_id']}", 
            "联系人: {$name}", 
            "联系电话：{$mobile}", 
            "联系邮箱：{$email}", 
            "申请说明：{$applyExt}");
        
        $data = array(
            'node_id' => $this->nodeId, 
            'type' => '8', 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            'comment' => implode(';', $arr));
        $flag = M('tmessage_apply')->add($data);
        if ($flag) {
            $content = implode('<br>', $arr);
            $ps = array(
                "subject" => "旺分销开通申请,旺号：" . $nodeInfo['client_id'], 
                "content" => $content, 
                "email" => $send_email);
            send_mail($ps);
            $temp_ps = array(
                "subject" => "旺分销开通申请,旺号：" . $nodeInfo['client_id'], 
                "content" => $content, 
                "email" => "mayy@imageco.com.cn");
            send_mail($temp_ps);
            $this->success('申请成功！');
        } else
            $this->success('申请失败！');
    }
}

