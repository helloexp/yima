<?php

/**
 * @@@ 企业介绍页 @@@ add dongdong 2015/04/23 13:28
 */
class QyXcAction extends Action {

    public function _initialize() {
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->assign("userInfo", $userInfo);
    }

    public function index() {
        $xcId = (int) I("get.xcId", 0);
        $this->display();
    }

    public function commit() {
        $data = I('post.');
        
        if ($data['cname'] == '')
            $this->error('联系人姓名不能为空');
        
        if (! preg_match('/^1[34578][0-9]{9}$/', $data['ciphone']))
            $this->error('手机号格式不正确');
        
        if (! preg_match('/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', 
            $data['cemail']))
            $this->error('邮箱格式不正确');
        
        if ($data['qyname'] == '')
            $this->error('企业名称不能为空');
        
        $content = "<BR/>姓名： " . $data['cname'];
        $content .= "<BR/>手机： " . $data['ciphone'];
        $content .= "<BR/>邮箱： " . $data['cemail'];
        $content .= "<BR/>企业： " . $data['qyname'];
        
        $arr = array(
            'subject' => '申请定制企业号', 
            'content' => $content, 
            'email' => 'xietm@imageco.com.cn');
        if (send_mail($arr)) {
            $this->success('发送成功');
        } else {
            $this->error('发送失败');
        }
    }
}