<?php

// 翼惠宝会员管理
class MessageAction extends YhbAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
    }

    public function setting() {
        $user_id = $this->user_id;
        $merchant = M()->table("tfb_yhb_node_info a")
            ->join('tuser_info b on a.user_info_id = b.id')
            ->where("b.user_id=" . $user_id)
            ->field('a.*,b.user_id')
            ->find();
        $this->assign("merchant", $merchant);
        $this->display();
    }

    public function setting_save() {
        $id = I('id', null, 'mysql_escape_string');
        $userId = I('user_id', null, 'mysql_escape_string');
        if (! check_str($userId, array(
            'null' => false), $error)) {
            $this->error("参数错误");
        }
        $userInfo = M('tuser_info')->where(
            "node_id='{$this->nodeId}' AND id='{$userId}'")->find();
        if (! $userInfo) {
            $this->error('未找到该用户信息' . M()->_sql());
        }
        
        if ($userInfo['new_role_id'] == '2' || $userInfo['new_role_id'] == '1') {
            $this->error('超级管理员不允许被编辑');
        }
        if ($this->isPost()) {
            $node_info = array(
                "update_time" => date("YmdHis"), 
                "message" => I('post.message'));
            
            // 数据更新
            $where = array(
                'id' => $id);
            $result = M('tfb_yhb_node_info')->where($where)->save($node_info);
            if ($result === false) {
                $this->ajaxReturn(0, "操作失败！", 0);
            } else {
                $this->ajaxReturn(1, "操作成功！", 1);
            }
        }
    }
}
