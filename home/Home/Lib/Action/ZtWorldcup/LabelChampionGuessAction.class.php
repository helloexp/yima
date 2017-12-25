<?php

/**
 * 冠军竞猜手机页面
 *
 * @author bao
 */
class LabelChampionGuessAction extends LabelMatchGuessAction {

    public function index() {
        $model = M('tbatch_channel');
        $id = I('id', null, 'mysql_real_escape_string');
        $map = array(
            'id' => $id, 
            'batch_type' => $this->batch_type);
        $result = $model->where($map)->find();
        if (! $result) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 活动
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(
            array(
                'id' => $result['batch_id'], 
                'batch_type' => $this->batch_type))->find();
        // 获取奖品信息
        $level_arr = array(
            '1' => '一', 
            '2' => '二', 
            '3' => '三');
        if ($row['is_cj'] == '1') {
            $prizeData = M()->table("tcj_rule r")->field(
                'i.batch_name,i.batch_img,b.award_level')
                ->join("tcj_batch b ON r.id=b.cj_rule_id")
                ->join("tbatch_info i ON b.activity_no=i.batch_no")
                ->where("r.status=1 AND r.batch_id={$row['id']}")
                ->select();
            foreach ($prizeData as &$info) {
                $info['level'] = $level_arr[$info['award_level']] . '等奖';
            }
        }
        // 获取球队信息
        $teamData = M('tworld_cup_team_info')->select();
        // 更新点击数
        import('@.Vendor.ClickStat');
        $opt = new ClickStat();
        $opt->updateStat($id);
        $this->assign('row', $row);
        $this->assign('prizeData', $prizeData);
        $this->assign('teamData', $teamData);
        $this->assign('id', $id);
        $this->display();
    }

    public function add() {
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->error('该活动不在有效期之内');
        $error = '';
        $phoneNo = I('post.phone', null, 'mysql_real_escape_string');
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        // 手机号是否已参加
        $result = M('tworld_cup_match_quiz')->where(
            "node_id='{$this->node_id}' AND batch_id='{$this->batch_id}' AND phone_no='{$phoneNo}' AND quiz_type=0")->find();
        if ($result)
            $this->error('您已经参与了该冠军竞猜活动');
        $teamId = I('post.team_id', null, 'mysql_real_escape_string');
        if (! check_str($teamId, array(
            'null' => false), $error)) {
            $this->error("请选择冠军球队");
        }
        $verify = I('post.verify', null, 'trim');
        if (md5($verify) != session('verify_cj')) {
            $this->error('验证码错误');
        }
        $data = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'session_id' => '64', 
            'phone_no' => $phoneNo, 
            'team_id' => $teamId, 
            'add_time' => date('YmdHis'), 
            'quiz_type' => '0', 
            'label_id' => $this->id);
        $result = M('tworld_cup_match_quiz')->add($data);
        if (! $result)
            $this->error('系统出错');
            
            // 更新参与人数
        M('tmarketing_info')->where("id={$this->batch_id}")->setInc('cj_count');
        $this->success('竞猜成功，我们会在世界杯结束之后进行抽奖，如果您中奖了，我们会以短信的形式通知您中奖信息!');
    }
}