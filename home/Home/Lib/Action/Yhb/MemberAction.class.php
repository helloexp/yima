<?php

// 翼惠宝会员管理
class MemberAction extends YhbAction {

    public $_authAccessMap = '*';

    public function _initialize() {
        parent::_initialize();
        if (! $this->is_admin) {
            $this->error("访问错误！");
        }
    }

    public function index() {
        $post = I('request.');
        $map = array();
        $mobile = I('mobile', '', 'trim,mysql_real_escape_string');
        $begin_time = I('start_time', '', 'trim,mysql_real_escape_string');
        $end_time = I('end_time', '', 'trim,mysql_real_escape_string');
        if ($mobile != '') {
            $map['a.mobile'] = $mobile;
        }
        if ($begin_time != '') {
            $map['a.related_time'][] = array(
                'EGT', 
                $begin_time . "000000");
        }
        if ($end_time != '') {
            $map['a.related_time'][] = array(
                'ELT', 
                $end_time . "235959");
        }
        
        import("ORG.Util.Page");
        $count = M()->table("tfb_yhb_member")
            ->where($map)
            ->count();
        $p = new Page($count, 10);
        $page = $p->show();
        $list = M()->table("tfb_yhb_member a")
            ->join('twx_user b on a.openid = b.openid')
            ->where($map)
            ->limit($p->firstRow . ',' . $p->listRows)
            ->group('mobile')
            ->order('a.id desc')
            ->field(
            "a.*,b.nickname as wx_nick_name")
            ->select();
        $this->assign('post', $post);
        $this->assign('list', $list);
        $this->assign('page', $page);
        $this->display();
    }

    public function edit() {
        $id = I('id');
        $m_info = M('tfb_yhb_member')->where("id=" . (int) $id)->find();
        
        if (IS_POST) {
            $model = M('tfb_yhb_member');
            $count = $model->where(
                "mobile='" . I('mobile') . "' and id <>" . $id)->count();
            if ($count > 0) {
                $this->error('手机号码已经被使用！');
            }
            $data = array(
                'mobile' => I('mobile'),
                );
            $flag = $model->where("id=" . (int) $id)->save($data);
            if ($flag === false) {
                $this->error('会员编辑失败！');
            }
            
            $this->success('会员编辑成功！');
            exit();
        }
        $this->assign("m_info", $m_info);
        $this->display();
    }

    public function wx_unrelated() {
        $id = I('id');
        $data['openid'] = '';
        $data['wx_nick_name'] = '';
        $map = array(
            'id' => $id);
        $res = M("tfb_yhb_member")->where($map)->save($data);
        if ($res) {
            $this->ajaxReturn(1, "解绑成功！", 1);
        } else {
            $this->ajaxReturn(0, "解绑失败！", 0);
        }
    }
}
