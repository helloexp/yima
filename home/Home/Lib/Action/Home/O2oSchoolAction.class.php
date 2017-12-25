<?php

class O2oSchoolAction extends Action {

    public $userArr = array();

    public function _initialize() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->userArr = $userInfo;
        $this->assign("userInfo", $userInfo);
    }

    public function index() {
        $row = M('tmessage_tmp')->find();
        $this->assign('row', $row);
        $this->display();
    }
    
    // 课程预约
    public function applay() {
        if ($this->isPost()) {
            $need_time = I('need_time');
            $need_time = date('YmdHis', strtotime($need_time));
            $user_arr = $this->userArr;
            if (empty($user_arr['node_id']))
                $this->error('请登录后预约!');
            
            $query = M('tmessage_apply')->where(
                array(
                    'node_id' => $user_arr['node_id'], 
                    'status' => '1', 
                    'type' => '1'))->find();
            if ($query)
                $this->error('请勿重复预约!');
            
            $array = array(
                'node_id' => $user_arr['node_id'], 
                'need_time' => $need_time, 
                'add_time' => date('YmdHis'), 
                'type' => '1', 
                'status' => '1');
            $query = M('tmessage_apply')->add($array);
            if ($query) {
                $node_arr = M('tnode_info')->where(
                    "node_id='" . $user_arr['node_id'] . "'")->find();
                $content = "商户名：" . $node_arr['node_name'] . '<br />商户号：' .
                     $user_arr['node_id'] . '<br />联系邮箱：' .
                     $node_arr['contact_eml'] . '<br />联系手机：' .
                     $node_arr['contact_phone'] . '<br />预约日期：' .
                     date('Y-m-d', strtotime($need_time));
                $ps = array(
                    "subject" => "张波课程预约提醒", 
                    "content" => $content, 
                    "email" => "bp@imageco.com.cn");
                send_mail($ps);
                $this->success(
                    '预约成功！<br/>我们会在24小时内邀请您加培训QQ群！<br/>您也可以选择自己加:343543999，提供旺号即可。<br/>咨询电话：021-51970527');
            } else
                $this->error('系统错误!');
        }
    }
    
    // 实操培训
    public function training() {
        if ($this->isPost()) {
            $user_arr = $this->userArr;
            if (empty($user_arr['node_id']))
                $this->error('请登录后报名!');
            
            $query = M('tmessage_apply')->where(
                array(
                    'node_id' => $user_arr['node_id'], 
                    'status' => '1', 
                    'type' => '2'))->find();
            if ($query)
                $this->error('请勿重复报名!');
            
            $qq = I('qq');
            $qq_arr = array_filter($qq);
            if (empty($qq_arr))
                $this->error('请填写qq号码！');
            $qq_str = implode('|', $qq_arr);
            $array = array(
                'node_id' => $user_arr['node_id'], 
                'type' => '2', 
                'qq' => $qq_str, 
                'add_time' => date('YmdHis'));
            $query = M('tmessage_apply')->add($array);
            if ($query) {
                $node_arr = M('tnode_info')->where(
                    "node_id='" . $user_arr['node_id'] . "'")->find();
                $content = "商户名：" . $node_arr['node_name'] . '<br />商户号：' .
                     $node_arr['node_id'] . '<br />联系邮箱：' .
                     $node_arr['contact_eml'] . '<br />联系手机：' .
                     $node_arr['contact_phone'] . '<br />QQ：' . $qq_str;
                $ps = array(
                    "subject" => "旺财周四培训报名提醒", 
                    "content" => $content, 
                    "email" => "xufm@imageco.com.cn"); // xufm
                
                send_mail($ps);
                $this->success('报名成功，您将在24小时内收到好友添加消息，记得登录QQ，查看消息！');
            } else
                $this->error('系统错误!');
        }
        
        $this->dislay();
    }
}