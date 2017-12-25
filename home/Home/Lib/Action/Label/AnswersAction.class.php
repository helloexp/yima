<?php
// 报名
class AnswersAction extends MyBaseAction {

    public function _initialize() {
        parent::_initialize();
        // 校验用户登录方式
        $this->_checkUser(true);
        // 判断是否登录
        $islogin = session('cjUserInfo') ? 1 : 0;
        $this->assign('islogin', $islogin);
    }

    public function index() {
        $id = $this->id;
        if ($this->batch_type != '10') {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 判断预览时间是否过期
        if (false === $this->checkCjEndtime()) {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro6.png', 
                    'errorTxt' => '预览时间已过期！', 
                    'errorSoftTxt' => '您访问的预览地址30分钟内有效，现已超时啦~'));
        }
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 活动
        $row = $this->marketInfo;
        
        $query_arr = explode('-', $row['select_type']);
        
        // 问题表
        $tmodel = M('tanswers_question');
        $qarr = $tmodel->where(
            array(
                'label_id' => $this->batch_id))
            ->order('sort')
            ->select();
        
        // 答案表
        $amodel = M('tanswers_question_info');
        
        if ($qarr) {
            $resp_arr = array();
            foreach ($qarr as $k => $v) {
                $resp_arr[$k]['info'] = $v;
                $resp_arr[$k]['list'] = array();
                $aarr = $amodel->where(
                    array(
                        'question_id' => $v['id']))->select();
                foreach ($aarr as $kk => $vv) {
                    $resp_arr[$k]['list'][$kk] = $vv;
                }
            }
        }
        
        // 抽奖配置表
        if ($row['is_cj'] == '1') {
            $model_c = M('tcj_rule');
            $map_c = array(
                'batch_type' => $this->batch_type, 
                'batch_id' => $this->batch_id, 
                'status' => '1');
            $cj_rule_query = $model_c->field('cj_button_text,cj_check_flag')
                ->where($map_c)
                ->find();
            // 抽奖文字配置
            $cj_text = $cj_rule_query['cj_button_text'];
            // 判断是否显示参与码
            $cj_check_flag = $cj_rule_query['cj_check_flag'];
        }
        // 唐山平安非标
        if ($this->node_id == C("tangshan.node_id")) {
            $tangshan_arr = M("tfb_tangshan_pingan")->where(
                array(
                    "m_id" => $row['id'], 
                    "node_id" => $this->node_id))->find();
            if ($tangshan_arr) {
                $this->assign("tangshan_arr", $tangshan_arr);
            }
        }
        
        // for 中奖记录 start
        $DLCommonMobile = $this->getMobileForAwardList($id);
        if ($DLCommonMobile) {
            log_write('if $DLCommonMobile:' . var_export($DLCommonMobile, 1));
            $this->assign('showAwardList', 'block');
        } else {
            log_write('else $DLCommonMobile:' . var_export($DLCommonMobile, 1));
            $this->assign('showAwardList', 'none');
        }
        // for 中奖记录 end
        $this->assign('id', $this->id);
        $this->assign('query_arr', $query_arr);
        $this->assign('resp_arr', $resp_arr);
        $this->assign('row', $row);
        $this->assign('cj_text', $cj_text);
        $this->assign('cj_check_flag', $cj_check_flag);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('pay_token', $this->pay_token);
        // $this->assign('node_name',$nodeName);
        $this->display(); // 输出模板
    }

    public function submit() {
        if (! $this->isPost())
            $this->ajaxReturn("error", "非法提交！", 0);
            
            // 翼蕙宝微信会员效验
        if ($this->node_id == C('Yhb.node_id')) {
            $this->assign('is_yhb', true);
            $yhb_user_info = $this->checkYhbMember();
            if (! $yhb_user_info['is_member']) {
                $return = array(
                    'status' => 5, 
                    'info' => "请先注册！");
                $this->ajaxReturn($return);
                exit();
            }
        }
        
        $id = $this->id;
        $batch_id = $this->batch_id;
        $batch_type = $this->batch_type;
        $node_id = $this->node_id;
        // 配置
        $get_bm_arr = array(
            '1' => 'true_name', 
            '2' => 'mobile', 
            '3' => 'sex', 
            '4' => 'age', 
            '5' => 'edu', 
            '6' => 'address', 
            '7' => 'email', 
            '8' => 'company', 
            '9' => 'position', 
            '10' => 'defined_one', 
            '11' => 'defined_two', 
            '12' => 'defined_three');
        
        $bm_arr = array(
            '1' => '姓名', 
            '2' => '联系方式', 
            '3' => '性别', 
            '4' => '年龄', 
            '5' => '学历', 
            '6' => '收信地址', 
            '7' => '邮箱', 
            '8' => '公司名称', 
            '9' => '职位');
        $bm_len_arr = array(
            '1' => '10', 
            '2' => '11', 
            '3' => '3', 
            '4' => '3', 
            '5' => '30', 
            '6' => '100', 
            '7' => '100', 
            '8' => '100', 
            '9' => '100', 
            '10' => '100', 
            '11' => '100', 
            '12' => '100');
        
        $select_type = I('post.select_type');
        $query_arr = explode('-', $select_type);
        $column_arr = array();
        foreach ($query_arr as $v) {
            switch ($get_bm_arr[$v]) {
                case 'mobile':
                    if (! check_str(
                        I('post.' . $get_bm_arr[$v], 'null', 'trim'), 
                        array(
                            'null' => false, 
                            'strtype' => 'mobile')))
                        $this->ajaxReturn("error", "联系方式请填写正确的手机号！", 0);
                    break;
                case 'email':
                    if (! check_str(
                        I('post.' . $get_bm_arr[$v], 'null', 'trim'), 
                        array(
                            'null' => false, 
                            'strtype' => 'email')))
                        $this->ajaxReturn("error", "请填写正确的邮箱地址！", 0);
                    break;
                default:
                    if (! check_str(
                        I('post.' . $get_bm_arr[$v], 'null', 'trim'), 
                        array(
                            'null' => false)))
                        $this->ajaxReturn("error", "请完善页面所有信息！", 0);
            }
            $column_arr[$get_bm_arr[$v]] = I($get_bm_arr[$v]);
        }
        $bmtrace = M('tanswers_trace');
        /*
         * $result=
         * $bmtrace->where(array('mobile'=>$mobile,'label_id'=>$batch_id))->select();
         * if($result){ $error_msg = '该手机号已做调研!'; $this->error($error_msg); }
         */
        $column_arr['add_time'] = date('YmdHis');
        $column_arr['label_id'] = $batch_id;
        $column_arr['node_id'] = $node_id;
        $column_arr['ip'] = get_client_ip();
        
        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        $bm_seq_id = $bmtrace->add($column_arr);
        
        // 问题表
        $tmodel = M('tanswers_question');
        $row_q = $tmodel->where(
            array(
                'label_id' => $batch_id))->select();
        $v_arr = array(
            '1' => 'A', 
            '2' => 'B', 
            '3' => 'C', 
            '4' => 'D', 
            '5' => 'E');
        if ($row_q) {
            foreach ($row_q as $q_v) {
                $r = I('radio-' . $q_v['id']);
                $c = I('checkbox-' . $q_v['id']);
                $t = I('textarea-' . $q_v['id']);
                if ($r) {
                    $q_arr = array(
                        'label_id' => $batch_id, 
                        'bm_seq_id' => $bm_seq_id, 
                        'question_id' => $q_v['id'], 
                        'answer_list' => $v_arr[$r], 
                        'add_time' => date('Ymd'));
                } elseif ($c) {
                    $in_arr = array();
                    foreach ($c as $in_v) {
                        $in_arr[] = $v_arr[$in_v];
                    }
                    $q_arr = array(
                        'label_id' => $batch_id, 
                        'bm_seq_id' => $bm_seq_id, 
                        'question_id' => $q_v['id'], 
                        'answer_list' => implode('-', $in_arr), 
                        'add_time' => date('Ymd'));
                } elseif ($t != '') {
                    $q_arr = array(
                        'label_id' => $batch_id, 
                        'bm_seq_id' => $bm_seq_id, 
                        'question_id' => $q_v['id'], 
                        'answer_list' => $t, 
                        'add_time' => date('Ymd'));
                } else {
                    $tranDb->rollback();
                    $this->ajaxReturn("error", "请完善页面所有信息！", 0);
                }
                $qsmodel = M('tanswers_question_stat');
                $query = $qsmodel->add($q_arr);
            }
        }
        
        // 活动
        $marketInfo = $this->marketInfo;
        $is_cj = $marketInfo['is_cj'];
        if ($bm_seq_id !== false && $query !== false) {
            $tranDb->commit();
            if ($is_cj == '1') {
                $this->ajaxReturn("success_cj", "添加成功！", 1);
            } else {
                // 唐山非标信息
                if ($this->node_id == C("tangshan.node_id")) {
                    $tangshan_data = array(
                        "m_id" => $this->batch_id, 
                        "node_id" => $this->node_id);
                    $res_tangshan = M("tfb_tangshan_pingan")->where(
                        $tangshan_data)->find();
                    if ($res_tangshan['tangshan_url']) {
                        $map = array(
                            "tangshan_url" => $res_tangshan['tangshan_url'], 
                            "info" => "添加成功！");
                        $this->ajaxReturn("success", $map, 1);
                    }
                }
                $this->ajaxReturn("success", "添加成功！", 1);
            }
        } else {
            $tranDb->rollback();
            $this->ajaxReturn("error", "添加失败！", 0);
        }
    }
}