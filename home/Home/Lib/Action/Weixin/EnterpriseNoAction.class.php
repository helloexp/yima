<?php
// 微信企业号
class EnterpriseNoAction extends Action {

    public $_authAccessMap = "*";
    // 微信企业号跳转页面——首页
    public function index() {
        $this->display();
    }
    // 显示的留言页面
    public function message() {
        $this->display();
    }
    // 留言处理
    public function msMail() {
        $guestName = I('post.guestName', "", 'htmlspecialchars,trim');
        $compalyName = I('post.compalyName', "", 'htmlspecialchars,trim');
        $data['qq'] = I('post.telphone', "", 'htmlspecialchars,trim');
        $data['node_id'] = "00000000";
        $data['type'] = "7";
        $data['add_time'] = date("YmdHis", time());
        $data['status'] = "1";
        $data['comment'] = "姓名:" . $guestName . ";电话:" . $data['qq'] . ";公司名称:" .
             $compalyName;
        if (M('tmessage_apply')->data($data)->add()) {
            $ps = array(
                "subject" => "微信企业号定制", 
                "content" => $data['comment'], 
                "email" => "yulf@imageco.com.cn");
            send_mail($ps);
            $this->ajaxReturn(1, '提交成功！', 1);
        } else {
            $this->ajaxReturn(0, '提交失败！', 0);
        }
    }
}

?>