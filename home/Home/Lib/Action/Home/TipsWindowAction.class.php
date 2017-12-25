<?php
// 右下角弹窗
class TipsWindowAction extends BaseAction {

    public function _initialize() {
        C('LOG_RECORD', false);
        // 获取node_id,user_id
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->user_id = $userInfo['user_id'];
        $this->node_id = $userInfo['node_id'];
    }
    // 消息判断
    public function index() {
        if ($this->user_id == "" || $this->node_id == "") {
            $returnArr = array(
                "guestbookcount" => 0, 
                "messagecount" => 0, 
                "ordercount" => 0, 
                "info" => "提交成功", 
                "status" => 0);
            echo json_encode($returnArr);
            exit();
        }
        
        // 评论数量
        $guestbookcount = M('tbatch_guestbook')->where(
            "touser='" . $this->node_id . "' AND ck_status='2' ")
            ->order("id desc")
            ->count();
        // 私信数量
        $messagecount = M('tmessage_info')->where(
            "receive_node_id='" . $this->node_id . "' AND ck_status='2' ")
            ->order("id desc")
            ->count();
        // 订单通知
        $ordercount = M('torder_notice')->where(
            "node_id='" . $this->node_id . "' AND status='0' ")
            ->order("id desc")
            ->count();
        $returnArr = array(
            "guestbookcount" => $guestbookcount ? $guestbookcount : 0, 
            "messagecount" => $messagecount ? $messagecount : 0, 
            "ordercount" => $ordercount ? $ordercount : 0, 
            "info" => "提交成功", 
            "status" => 1);
        echo json_encode($returnArr);
        exit();
    }
}
