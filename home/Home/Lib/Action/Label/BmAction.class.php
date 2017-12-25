<?php

// 报名
class BmAction extends MyBaseAction {
    // 初始化
    public function _initialize() {
        parent::_initialize();
        // 校验用户参与方式
        $this->_checkUser(true);
        // 判断是否登录
        $islogin = session('cjUserInfo') ? 1 : 0;
        $this->assign('islogin', $islogin);
    }

    public function index() {
        $id = $this->id;
        if ($this->batch_type != '3') {
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
        $tmodel = M('tquestion');
        
        $heibei_pars_batch_id = C('hebei_pars.batch_id');
        if (in_array($this->batch_id, $heibei_pars_batch_id)) {
            $_config = C('hebei_pars.resp_arr');
            $ids = array_values($_config[$this->batch_id]);
            $qarr = $tmodel->where(
                array(
                    'label_id' => $this->batch_id, 
                    'id' => array(
                        'in', 
                        $ids)))
                ->order('sort')
                ->select();
        } else {
            $qarr = $tmodel->where(
                array(
                    'label_id' => $this->batch_id))
                ->order('sort desc')
                ->select();
        }
        // 答案表
        $amodel = M('tquestion_info');
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
            
            $map_img = $model_c->alias('t1')
                ->join(
                "tcj_batch t2 on t1.batch_id = t2.batch_id and t2.cj_rule_id = t1.id and t1.status = 1")
                ->join("tgoods_info t3 on t2.activity_no = t3.batch_no")
                ->where(
                array(
                    't1.status' => '1', 
                    't1.batch_type' => $this->batch_type, 
                    't1.batch_id' => $this->batch_id))
                ->getField('t3.goods_image');
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
        
        $this->assign('map_img', $map_img);
        $this->assign('id', $this->id);
        $this->assign('query_arr', $query_arr);
        $this->assign('resp_arr', $resp_arr);
        $this->assign('row', $row);
        $this->assign('cj_text', $cj_text);
        $this->assign('cj_check_flag', $cj_check_flag);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        // $this->assign('node_name',$nodeName);
        
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
        
        // 该处为手机WAP页面 取活动所属商户号判断
        if ($this->batch_id == C('yunnan.batch_id')) {
            $this->display('yunnan');
        } elseif (in_array($this->batch_id, $heibei_pars_batch_id)) {
            $goto = I('get.goto');
            if (! empty($goto)) {
                $this->display($goto);
            } else {
                $this->display('hbpars');
            }
        } elseif ($this->batch_id == C('shandong.batch_id')) {
            $this->display('shandong');
        }
        elseif ($this->batch_id == C('gssy.batch_id')) {
            $this->display('gssy');}
        else {
            $this->display();
        }
    }

    public function submit() {
        $overdue = $this->checkDate();
        if ($overdue === false) {
            $msg = '该活动不在有效期之内！';
            $this->ajaxReturn("error", $msg, 0);
        }
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
        
        $batch_id = $this->batch_id;
        $batch_type = $this->batch_type;
        $node_id = $this->node_id;
        
        // 活动
        $batchModel = M('tmarketing_info');
        $batch_arr = $batchModel->field(
            'is_cj,start_time,end_time,defined_one_name,select_type')
            ->where(
            array(
                'id' => $batch_id, 
                'batch_type' => $batch_type))
            ->find();
        $is_cj = $batch_arr['is_cj'];
        $selecttype = $batch_arr['select_type'];
        $selectarr = explode('-', $selecttype);
        if (C('GWYL_NODE') == $this->node_id &&
             $batch_arr['defined_one_name'] == '小票号' &&
             in_array('10', $selectarr)) {
            $ticket_seq = I('defined_one', null, 'trim');
            if (empty($ticket_seq)) {
                $this->ajaxReturn("error", "小票号不能为空！", 0);
            }
            // 参与次数
            $cycount = M('tcj_rule')->where(
                array(
                    'batch_id' => $this->batch_id, 
                    'status' => '1', 
                    'node_id' => $this->node_id))->getField('param1');
            $cycount = (int) $cycount;
            if ($cycount > 0) {
                $usecount = M('tfb_ticket_trace')->where(
                    array(
                        'batch_id' => $this->batch_id, 
                        'ticket_num' => $ticket_seq))->getField('use_count');
                $usecount = (int) $usecount;
                if ($usecount >= $cycount) {
                    $this->ajaxReturn("error", "小票号参与已超过指定次数！", 0);
                }
            }
        }
        
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
            '12' => 'defined_three', 
            '13' => 'pic_one', 
            '14' => 'car_num', 
            '15' => 'city', 
            '16' => 'id_card', 
            '17' => 'get_goods_address', 
            '18' => 'defined_four', 
            '19' => 'defined_five', 
            '20' => 'defined_six');

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
                        $this->ajaxReturn("error", "请填写正确的手机号！", 0);
                    break;
                case 'email':
                    if (! check_str(
                        I('post.' . $get_bm_arr[$v], 'null', 'trim'), 
                        array(
                            'null' => false, 
                            'strtype' => 'email')))
                        $this->ajaxReturn("error", "请填写正确的邮箱地址！", 0);
                    break;
                case 'pic_one':
                    if (! isset($_FILES['pic_one']))
                        $this->ajaxReturn("error", "图片未选择，请重试！", 0);
                    
                    $pic_one = $_FILES['pic_one'];
                    if ($pic_one['error'] !== 0)
                        $this->ajaxReturn("error", "图片上传失败，请重试！", 0);
                    if ($pic_one['size'] === 0)
                        $this->ajaxReturn("error", "图片无效，请重新选择！", 0);
                    if ($pic_one['size'] > 1024 * 1024 * 3)
                        $this->ajaxReturn("error", "图片超过3M，请重新选择！", 0);
                    $pic_ex = strtolower(
                        substr($pic_one['name'], 
                            strripos($pic_one['name'], '.') + 1));
                    if (! in_array($pic_ex, 
                        array(
                            'gif', 
                            'jpg', 
                            'jpeg', 
                            'bmp', 
                            'png'), true))
                        $this->ajaxReturn("error", "图片格式不对！", 0);
                    break;
                default:
                    if (! check_str(
                        I('post.' . $get_bm_arr[$v], 'null', 'trim'), 
                        array(
                            'minlen' => 1)))
                        $this->ajaxReturn("error", "请完善页面所有信息！", 0);
            }
            $column_arr[$get_bm_arr[$v]] = I($get_bm_arr[$v]);
        }
        if ($this->batch_id == C('yunnan.batch_id')) {
            $verifycode = I('verifycode');
            if ($verifycode != $_SESSION['checkCode']['number']) {
                exit(
                    json_encode(
                        array(
                            'info' => '验证码不正确！', 
                            'status' => 0)));
            }
        }
        if ($this->batch_id == C('gssy.batch_id')) {
            $verifycode = I('verifycode');
            $phone=I('mobile');
            if ($verifycode != $_SESSION['ganSuCheckCode']['number']||$phone!=$_SESSION['ganSuCheckCode']['phoneNo']) {
            $this->error('手机验证码错误');}
            unset($_SESSION['ganSuCheckCode']['number']);
            unset($_SESSION['ganSuCheckCode']['phoneNo']);

        }
        $bmtrace = M('tbm_trace');
        // 山东调研手机验证码检验
        if ($this->batch_id == C('shandong.batch_id')) {
            $mobile = I("mobile");
            $carNum = I("carNum");
            $re1 = $bmtrace->where(
                array(
                    'mobile' => $mobile, 
                    'label_id' => $batch_id))->find();
            $qid = M("tquestion")->field("id")
                ->where(
                array(
                    'questions' => '车牌号', 
                    'label_id' => $batch_id))
                ->find();
            $re2 = M("tquestion_stat")->where(
                array(
                    'label_id' => $batch_id, 
                    'question_id' => $qid['id'], 
                    'answer_list' => $carNum))->find();
            if ($re1 || $re2) {
                $error_msg = '车牌号/手机号已存在!';
                $this->error($error_msg);
            }
            $verifycode = I('verifycode');
            if ($verifycode != $_SESSION['checkCode']['number']) {
                exit(
                    json_encode(
                        array(
                            'info' => '验证码不正确！', 
                            'status' => 0)));
            }
        }
        $column_arr['add_time'] = date('YmdHis');
        $column_arr['label_id'] = $batch_id;
        $column_arr['node_id'] = $node_id;
        $column_arr['ip'] = get_client_ip();
        $column_arr['channel_id'] = $this->channel_id;
        $column_arr['channel_name'] = M('tchannel')->where(
            array(
                'id' => $this->channel_id))->getField('name');

        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        
        $bm_seq_id = $bmtrace->add($column_arr);
        
        // 若开启了上传
        if (in_array('13', $query_arr, true)) {
            import('ORG.Net.UploadFile');
            $upload = new UploadFile(); // 实例化上传类
            $upload->maxSize = 1024 * 1024 * 3;
            $upload->savePath = C('UPLOAD') . '/bm_upload/' . $this->batch_id .
                 '/'; // 设置附件
            $upload->supportMulti = false;
            $upload->allowExts = array(
                'gif', 
                'jpg', 
                'jpeg', 
                'bmp', 
                'png');
            
            if (! $upload->upload()) { // 上传错误提示错误信息
                $tranDb->rollback();
                $this->ajaxReturn("error", "图片上传失败！", 0);
                exit();
            } else { // 上传成功 获取上传文件信息
                $info = $upload->getUploadFileInfo();
                // 以报名序号重命名
                rename($info[0]['savepath'] . $info[0]['savename'], 
                    $info[0]['savepath'] . $bm_seq_id . '.' .
                         $info[0]['extension']);
            }
        }
        
        // 问题表
        $tmodel = M('tquestion');
        $row_q = $tmodel->where(
            array(
                'label_id' => $batch_id))->select();
        $v_arr = array(
            '1' => 'A', 
            '2' => 'B', 
            '3' => 'C', 
            '4' => 'D', 
            '5' => 'E', 
            '6' => 'F', 
            '7' => 'G', 
            '8' => 'H', 
            '9' => 'I', 
            '10' => 'J', 
            '11' => 'K', 
            '12' => 'L', 
            '13' => 'M', 
            '14' => 'N', 
            '15' => 'O', 
            '16' => 'P', 
            '17' => 'Q', 
            '18' => 'R', 
            '19' => 'S', 
            '20' => 'T');
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
                        'answer_list' => $v_arr[$r] ? $v_arr[$r] : $r, 
                        'add_time' => date('Ymd'));
                } elseif ($c) {
                    $in_arr = array();
                    foreach ($c as $in_v) {
                        $in_arr[] = $v_arr[$in_v] ? $v_arr[$in_v] : $in_v;
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
                $qsmodel = M('tquestion_stat');
                $query = $qsmodel->add($q_arr);
            }
        }
        
        if ($bm_seq_id !== false && $query !== false) {
            $tranDb->commit();
            if ($is_cj == '1') {
                if ($this->hgds_flag) {
                    $this->ajaxReturn(
                        array(
                            'type' => 'success_cj', 
                            'hgds_range_remain' => $this->_check_hgds_storage()), 
                        "添加成功！", 1);
                } else {
                    $this->ajaxReturn("success_cj", "添加成功!", 1);
                }
            } else {
                if ($this->id == C('yunnan.id')) {
                    $this->ajaxReturn("success", "已成功提交！", 1);
                } else if ($this->batch_id == '23340') {
                    $this->ajaxReturn("success", "感谢您的参与，祝您幸福平安！", 1);
                } else if($this->batch_id == C('gssy.batch_id')) {
                    $this->ajaxReturn("success",
                        "感谢参与,签到成功", 1);
                }

                else {
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
            }
        } else {
            $tranDb->rollback();
            $this->ajaxReturn("error", "添加失败！", 0);
        }
    }

    // 河北平安人寿添加调研信息
    public function hbinfo() {
        if (! $this->isPost())
            $this->ajaxReturn("error", "非法提交！", 0);
        $batch_id = $this->batch_id;
        $node_id = $this->node_id;
        $channel_id = $this->channel_id;
        
        // 活动跟踪信息处理
        $column_arr['add_time'] = date('YmdHis');
        $column_arr['label_id'] = $batch_id;
        $column_arr['node_id'] = $node_id;
        $column_arr['ip'] = get_client_ip();
        $column_arr['channel_id'] = $channel_id;
        $column_arr['channel_name'] = M('tchannel')->where(
            array(
                'id' => $channel_id))->getField('name');
        
        $bmtrace = M('tbm_trace');
        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        // 添加活动跟踪信息
        $bm_seq_id = $bmtrace->add($column_arr);
        
        $_config = C('hebei_pars.resp_arr');
        $_config = $_config[$batch_id];
        $_ids = array_values($_config);
        $config = array_flip($_config);
        // 问题表
        $tmodel = M('tquestion');
        $row_q = $tmodel->where(
            array(
                'label_id' => $batch_id, 
                'id' => array(
                    'in', 
                    $_ids)))->select();
        
        if ($row_q) {
            foreach ($row_q as $q_v) {
                $field = $config[$q_v['id']];
                $t = I($field);
                
                if (! strcasecmp($field, 'mortgage_and_debt')) {
                    $t = empty($t) ? 0 : $t;
                }
                if ($t !== NULL) {
                    // 判断参数
                    if (strcasecmp($field, 'mortgage_and_debt')) {
                        if (! is_numeric($t) || $t < 0) {
                            $tranDb->rollback();
                            $this->ajaxReturn('error', '请入合法数字！', 0);
                        }
                    }
                    
                    // 如果是寿保或按揭(债务)以万计算
                    if (! strcasecmp($field, 'has_amount') ||
                         ! strcasecmp($field, 'mortgage_and_debt')) {
                        $t *= 10000;
                    }
                    // 处理信息
                    $q_arr = array(
                        'label_id' => $batch_id, 
                        'bm_seq_id' => $bm_seq_id, 
                        'question_id' => $q_v['id'], 
                        'answer_list' => (float) $t, 
                        'add_time' => date('Ymd'));
                } else {
                    $tranDb->rollback();
                    $this->ajaxReturn('error', '请完善页面所有信息！', 0);
                }
                $query = M('tquestion_stat')->add($q_arr);
                if ($query == false) {
                    $tranDb->rollback();
                    $this->ajaxReturn('error', '提交失败！', 0);
                }
            }
        } else {
            $tranDb->rollback();
            $this->ajaxReturn('error', '暂无活动问题！', 0);
        }
        
        $tranDb->commit();
        $info = array(
            'type' => 'success_cj', 
            'bmseq' => $bm_seq_id, 
            'id' => $this->id);
        $this->ajaxReturn($info, '提交成功，点击继续', 1);
    }
    
    // 计算公式
    public function hbreckon() {
        $batch_id = $this->batch_id;
        if (! is_numeric($batch_id) || $batch_id < 0) {
            $this->error('非法提交！');
        }
        // 获取统计ID
        $bm_seq_id = I('get.bmseq');
        if (empty($bm_seq_id)) {
            $this->error('非法参数');
        }
        
        $where['label_id'] = $batch_id;
        $where['bm_seq_id'] = $bm_seq_id;
        // 查询统计信息
        $tque = M('tquestion_stat')->where($where)
            ->field('question_id,answer_list')
            ->select();
        
        if (empty($tque)) {
            $this->error('参数不存在！');
        }
        // 获取配置信息
        $_config = C('hebei_pars.resp_arr');
        $_config = $_config[$batch_id];
        $config = array_flip($_config);
        $years = $monthly_amount = $yield = $mortgage_and_debt = $has_amount = 0;
        // 参数赋值
        foreach ($tque as $row) {
            
            $field = $config[$row['question_id']];
            if (! strcasecmp($field, 'years')) {
                $years = $row['answer_list'];
            } elseif (! strcasecmp($field, 'monthly_amount')) {
                $monthly_amount = $row['answer_list'];
            } elseif (! strcasecmp($field, 'yield')) {
                $yield = $row['answer_list'];
            } elseif (! strcasecmp($field, 'mortgage_and_debt')) {
                $mortgage_and_debt = $row['answer_list'];
            } elseif (! strcasecmp($field, 'has_amount')) {
                $has_amount = $row['answer_list'];
            }
        }
        
        // 数据工具表
        $tool_data = array(
            10 => array(
                'A' => 97791, 
                'B' => 95660, 
                'C' => 93605, 
                'D' => 91622, 
                'E' => 89709, 
                'F' => 87861, 
                'G' => 86077, 
                'H' => 84353, 
                'I' => 82688, 
                'J' => 81078), 
            11 => array(
                'A' => 107304, 
                'B' => 104713, 
                'C' => 102222, 
                'D' => 99826, 
                'E' => 97521, 
                'F' => 95302, 
                'G' => 93166, 
                'H' => 91109, 
                'I' => 89127, 
                'J' => 87217), 
            12 => array(
                'A' => 116770, 
                'B' => 113676, 
                'C' => 110711, 
                'D' => 107868, 
                'E' => 105142, 
                'F' => 102526, 
                'G' => 100016, 
                'H' => 97605, 
                'I' => 95289, 
                'J' => 93064), 
            13 => array(
                'A' => 126189, 
                'B' => 122551, 
                'C' => 119075, 
                'D' => 115753, 
                'E' => 112578, 
                'F' => 109540, 
                'G' => 106633, 
                'H' => 103851, 
                'I' => 101186, 
                'J' => 98633), 
            14 => array(
                'A' => 135562, 
                'B' => 131337, 
                'C' => 127315, 
                'D' => 123484, 
                'E' => 119832, 
                'F' => 116350, 
                'G' => 113027, 
                'H' => 109856, 
                'I' => 106829, 
                'J' => 103936), 
            15 => array(
                'A' => 144887, 
                'B' => 140037, 
                'C' => 135434, 
                'D' => 131062, 
                'E' => 126909, 
                'F' => 122961, 
                'G' => 119205, 
                'H' => 115631, 
                'I' => 112228, 
                'J' => 108986), 
            16 => array(
                'A' => 154166, 
                'B' => 148651, 
                'C' => 143432, 
                'D' => 138493, 
                'E' => 133814, 
                'F' => 129379, 
                'G' => 125174, 
                'H' => 121184, 
                'I' => 117395, 
                'J' => 113797), 
            17 => array(
                'A' => 163399, 
                'B' => 157179, 
                'C' => 151313, 
                'D' => 145777, 
                'E' => 140550, 
                'F' => 135611, 
                'G' => 130941, 
                'H' => 126523, 
                'I' => 122340, 
                'J' => 118378), 
            18 => array(
                'A' => 172586, 
                'B' => 165623, 
                'C' => 159076, 
                'D' => 152919, 
                'E' => 147122, 
                'F' => 141661, 
                'G' => 136513, 
                'H' => 131657, 
                'I' => 127072, 
                'J' => 122741), 
            19 => array(
                'A' => 181728, 
                'B' => 173983, 
                'C' => 166726, 
                'D' => 159920, 
                'E' => 153534, 
                'F' => 147535, 
                'G' => 141897, 
                'H' => 136593, 
                'I' => 131600, 
                'J' => 126896), 
            20 => array(
                'A' => 190824, 
                'B' => 182260, 
                'C' => 174262, 
                'D' => 166785, 
                'E' => 159789, 
                'F' => 153238, 
                'G' => 147098, 
                'H' => 141339, 
                'I' => 135933, 
                'J' => 130853), 
            21 => array(
                'A' => 199874, 
                'B' => 190456, 
                'C' => 181686, 
                'D' => 173514, 
                'E' => 165892, 
                'F' => 158775, 
                'G' => 152124, 
                'H' => 145903, 
                'I' => 140079, 
                'J' => 134622), 
            22 => array(
                'A' => 208880, 
                'B' => 198570, 
                'C' => 189001, 
                'D' => 180112, 
                'E' => 171845, 
                'F' => 164150, 
                'G' => 156980, 
                'H' => 150292, 
                'I' => 144047, 
                'J' => 138212), 
            23 => array(
                'A' => 217841, 
                'B' => 206604, 
                'C' => 196208, 
                'D' => 186580, 
                'E' => 177654, 
                'F' => 169369, 
                'G' => 161671, 
                'H' => 154511, 
                'I' => 147844, 
                'J' => 141630), 
            24 => array(
                'A' => 226757, 
                'B' => 214558, 
                'C' => 203309, 
                'D' => 192922, 
                'E' => 183321, 
                'F' => 174436, 
                'G' => 166204, 
                'H' => 158568, 
                'I' => 151478, 
                'J' => 144886), 
            25 => array(
                'A' => 235629, 
                'B' => 222434, 
                'C' => 210304, 
                'D' => 199139, 
                'E' => 188850, 
                'F' => 179355, 
                'G' => 170584, 
                'H' => 162470, 
                'I' => 154955, 
                'J' => 147986), 
            26 => array(
                'A' => 244456, 
                'B' => 230232, 
                'C' => 217196, 
                'D' => 205235, 
                'E' => 194244, 
                'F' => 184131, 
                'G' => 174815, 
                'H' => 166221, 
                'I' => 158282, 
                'J' => 150939), 
            27 => array(
                'A' => 253240, 
                'B' => 237952, 
                'C' => 223986, 
                'D' => 211210, 
                'E' => 199506, 
                'F' => 188768, 
                'G' => 178904, 
                'H' => 169828, 
                'I' => 161466, 
                'J' => 153752), 
            28 => array(
                'A' => 261980, 
                'B' => 245596, 
                'C' => 230676, 
                'D' => 217069, 
                'E' => 204640, 
                'F' => 193270, 
                'G' => 182854, 
                'H' => 173296, 
                'I' => 164513, 
                'J' => 156430), 
            29 => array(
                'A' => 270677, 
                'B' => 253164, 
                'C' => 237267, 
                'D' => 222813, 
                'E' => 209649, 
                'F' => 197641, 
                'G' => 186670, 
                'H' => 176631, 
                'I' => 167429, 
                'J' => 158981), 
            30 => array(
                'A' => 279330, 
                'B' => 260658, 
                'C' => 243761, 
                'D' => 228444, 
                'E' => 214535, 
                'F' => 201885, 
                'G' => 190358, 
                'H' => 179837, 
                'I' => 170219, 
                'J' => 161411), 
            31 => array(
                'A' => 287941, 
                'B' => 268077, 
                'C' => 250158, 
                'D' => 233965, 
                'E' => 219303, 
                'F' => 206004, 
                'G' => 193920, 
                'H' => 182920, 
                'I' => 172889, 
                'J' => 163725), 
            32 => array(
                'A' => 296508, 
                'B' => 275423, 
                'C' => 256461, 
                'D' => 239377, 
                'E' => 223954, 
                'F' => 210004, 
                'G' => 197363, 
                'H' => 185885, 
                'I' => 175444, 
                'J' => 165928), 
            33 => array(
                'A' => 305033, 
                'B' => 282696, 
                'C' => 262671, 
                'D' => 244683, 
                'E' => 228492, 
                'F' => 213888, 
                'G' => 200689, 
                'H' => 188736, 
                'I' => 177889, 
                'J' => 168027), 
            34 => array(
                'A' => 313515, 
                'B' => 289897, 
                'C' => 268790, 
                'D' => 249886, 
                'E' => 232919, 
                'F' => 217658, 
                'G' => 203902, 
                'H' => 191476, 
                'I' => 180229, 
                'J' => 170025), 
            35 => array(
                'A' => 321955, 
                'B' => 297027, 
                'C' => 274817, 
                'D' => 254986, 
                'E' => 237238, 
                'F' => 221318, 
                'G' => 207007, 
                'H' => 194112, 
                'I' => 182468, 
                'J' => 171929), 
            36 => array(
                'A' => 330354, 
                'B' => 304086, 
                'C' => 280756, 
                'D' => 259986, 
                'E' => 241452, 
                'F' => 224872, 
                'G' => 210007, 
                'H' => 196646, 
                'I' => 184610, 
                'J' => 173742), 
            37 => array(
                'A' => 338710, 
                'B' => 311075, 
                'C' => 286607, 
                'D' => 264888, 
                'E' => 245563, 
                'F' => 228323, 
                'G' => 212905, 
                'H' => 199083, 
                'I' => 186660, 
                'J' => 175469), 
            38 => array(
                'A' => 347025, 
                'B' => 317995, 
                'C' => 292371, 
                'D' => 269695, 
                'E' => 249573, 
                'F' => 231672, 
                'G' => 215705, 
                'H' => 201426, 
                'I' => 188622, 
                'J' => 177113), 
            39 => array(
                'A' => 355299, 
                'B' => 324847, 
                'C' => 298051, 
                'D' => 274406, 
                'E' => 253486, 
                'F' => 234925, 
                'G' => 218411, 
                'H' => 203679, 
                'I' => 190500, 
                'J' => 178679), 
            40 => array(
                'A' => 363531, 
                'B' => 331630, 
                'C' => 303646, 
                'D' => 279026, 
                'E' => 257303, 
                'F' => 238082, 
                'G' => 221025, 
                'H' => 205845, 
                'I' => 192297, 
                'J' => 180170));
        $y = array();
        $_tool_data = array_keys($tool_data);
        // 如果在数据范围内获取 Y 轴的值
        if (in_array($years, $_tool_data)) {
            $y = $tool_data[$years];
        } else {
            if ($years < 10) { // 不在范围内并小于范围最小值获取最小值
                $y = $tool_data[10];
            } else { // 则获取最大值
                $y = $tool_data[40];
            }
        }
        
        $x = 0;
        
        $yield_data = array(
            'A' => 0.5, 
            'B' => 1, 
            'C' => 1.5, 
            'D' => 2, 
            'E' => 2.5, 
            'F' => 3, 
            'G' => 3.5, 
            'H' => 4, 
            'I' => 4.5, 
            'J' => 5);
        // 如果在数据范围内获取 X 轴的值
        $_yield_data = array_values($yield_data);
        if (in_array($yield, $_yield_data)) {
            foreach ($yield_data as $key => $val) {
                if (! bccomp($val, $yield, 100)) {
                    $x = $y[$key];
                }
            }
        } else {
            if (bccomp($yield, 0.5, 100) < 0) { // 不在范围内并小于范围最小值获取最小值
                $x = $y['A'];
            } else { // 则获取最大值
                $x = $y['J'];
            }
        }
        
        // 全年家庭生活费
        $year_amount = $monthly_amount * 12;
        // 保守投资倍数
        $investment = $year_amount / 10000;
        // 保障金
        $fund = $x;
        // 现在需准备的生活费
        $living = $x * $investment;
        // 家庭保障
        $protection = $living + $mortgage_and_debt - $has_amount;
        
        // 万单位格式化
        $living = round($living / 10000, 2);
        $investment = round($investment, 4);
        $protection = round($protection / 10000, 2);
        $fund = round($fund / 10000, 2);
        $has_amount = round($has_amount / 10000, 2);
        $mortgage_and_debt = round($mortgage_and_debt / 10000, 2);
        $year_amount = round($year_amount / 10000, 2);
        
        $this->assign('years', $years);
        $this->assign('monthly_amount', $monthly_amount);
        $this->assign('yield', $yield);
        $this->assign('mortgage_and_debt', $mortgage_and_debt);
        $this->assign('has_amount', $has_amount);
        $this->assign('fund', $fund);
        $this->assign('living', $living);
        $this->assign('protection', $protection);
        $this->assign('year_amount', $year_amount);
        $this->assign('investment', $investment);
        $this->display();
    }

    public function sendCheckCode() {

        $batch_id=C('gssy.batch_id');
        if($this->batch_id != $batch_id){
            $this->error('参数错误！');
        }

        $phoneNo = I('post.mobile', null);
        if (! check_str($phoneNo, array('null' => false, 'strtype' => 'mobile'),$error)) {
            $this->error("手机号{$error}");
        }

        $verify=I('post.verifypicture');

        if(!$verify){
            $this->error('请输入验证码');
        }

        $sessVerifyCode = session('verify_cj');
        if( $sessVerifyCode == '' ){
            $this->error('请刷新验证码重试');
        }

        if($sessVerifyCode != md5($verify)){
            //验证码错误
            $this->error('验证码错误');
        }

        session('verify_cj', null);

        $matchCount=intval( M('tfb_phone')->where(array('batch_id'=>$batch_id,'mobile'=>$phoneNo))->count() );
        if($matchCount == 0){
            $this->error('抱歉！您手机号所绑定的油卡充值未满指定额度，暂时无法参与活动');
        }


        // 测试环境不下发，验证码直接为1111
        if (! is_production()) {
            $groupCheckCode = array(
                'number' => 1111,
                'add_time' => time(),
                'phoneNo' => $phoneNo);
            session('ganSuCheckCode', $groupCheckCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }
        // 发送频率验证
        $groupCheckCode = session('ganSuCheckCode');
        if (! empty($groupCheckCode) &&
            (time() - $groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->CodeexpiresTime / 60;
        $text = "您在{$this->node_short_name}商户的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
        $sendStatus=send_SMS($this->node_id, $phoneNo, $text);
        if($sendStatus===false){
            $this->error('验证发送失败！');
        }
        $groupCheckCode = array(
            'number' => $num,
            'add_time' => time(),
            'phoneNo' => $phoneNo);
        session('ganSuCheckCode', $groupCheckCode);
        $this->success('动态密码已发送');
    }
}