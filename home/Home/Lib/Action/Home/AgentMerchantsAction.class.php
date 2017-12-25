<?php

class AgentMerchantsAction extends Action {

    public function _initialize() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->assign("userInfo", $userInfo);
    }

    public function index() {
        $this->display();
    }
    
    // 运营商申请
    public function apply() {
        if ($this->isPost()) {
            $send_email = 'zhang.wei@imageco.com.cn';
            $type_arr = array(
                '1' => '申请白云运营商', 
                '2' => '申请彩云运营商', 
                '3' => '开通旺财直通车', 
                '4' => '申请代运营', 
                '5' => '申请祥云运营商');
            $apply_type = I('apply_type');
            $company_name = I('company_name');
            $company_type = I('company_type');
            $company_text = I('company_text');
            $company_url = I('company_url');
            $user_name = I('user_name');
            $position = I('position');
            $mobile = I('mobile');
            $email = I('email');
            if (! empty($email)) {
                if (! check_str($email, 
                    array(
                        'null' => false, 
                        'strtype' => 'email'), $error)) {
                    $this->error("邮箱格式不正确！");
                }
            }
            if ($apply_type == '1' || $apply_type == '2' || $apply_type == '5') {
                if (empty($company_name) || empty($company_type) ||
                     empty($company_text) || empty($company_url) ||
                     empty($user_name) || empty($position) || empty($mobile) ||
                     empty($email)) {
                    $this->error('带*号的必填！');
                }
            } elseif ($apply_type == '3') {
                $send_email = 'chenjianyong@imageco.com.cn';
                $channel_type = I('channel_type');
                $channel_text = I('channel_text');
                $channel_other = I('channel_other');
                if (empty($channel_text)) {
                    $this->error('渠道描述不能为空！');
                }
                if ($channel_type == '6') {
                    if (empty($channel_other)) {
                        $this->error('渠道其他类型名不能为空！');
                    }
                }
            } else {
                if (empty($user_name) || empty($mobile)) {
                    $this->error('带*号的必填！');
                }
            }
            
            if (empty($mobile) || ! is_numeric($mobile) || strlen($mobile) != 11)
                $this->error('联系电话格式不正确！');
            
            $query_arr = array(
                'mobile' => $mobile, 
                'type' => $apply_type);
            
            $result = M('tapplay_member')->where($query_arr)->find();
            if ($result)
                $this->error('请勿重复提交！');
            $req_arr = array(
                'user_name' => $user_name, 
                'mobile' => $mobile, 
                'type' => $apply_type, 
                'add_time' => date('YmdHis'), 
                'company_name' => $company_name, 
                'company_type' => $company_type, 
                'company_text' => $company_text, 
                'company_url' => $company_url, 
                'position' => $position, 
                'email' => $email, 
                'channel_type' => $channel_type, 
                'channel_text' => $channel_text, 
                'channel_other' => $channel_other);
            $query = M('tapplay_member')->add($req_arr);
            if ($query) {
                $content = "姓名：" . $user_name . " 联系方式:" . $mobile .
                     $type_arr[$apply_type];
                $ps = array(
                    "subject" => "代运营商申请提醒", 
                    "content" => $content, 
                    "email" => $send_email);
                send_mail($ps);
                $this->success('申请成功！');
            } else
                $this->error('申请失败！');
        }
    }
}