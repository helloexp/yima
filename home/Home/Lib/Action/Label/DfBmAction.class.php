<?php

// 报名
class DfBmAction extends MyBaseAction {
    
    // 哈根达斯标志
    private $hgds_flag = false;
    
    // 初始化
    public function _initialize() {
        parent::_initialize();
        $this->hgds_flag = in_array($this->node_id, C('DM_Haagen_Dazs'));
        // 校验用户参与方式
        $this->_checkUser(true);
        // 判断是否登录
        $islogin = session('cjUserInfo') ? 1 : 0;
        $this->assign('islogin', $islogin);
    }

    public function index() {
        $id = $this->id;
        if ($this->batch_type != '1004')
            $this->error('错误访问！');
            // 判断预览时间是否过期
        if (false === $this->checkCjEndtime()) {
            $this->error('预览时间已过期');
        }
        
        // 访问量
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $opt->UpdateRecord();
        
        // 活动
        $row = $this->marketInfo;
        $batch_node_id = $row['node_id'];
        $query_arr = explode('-', $row['select_type']);
        
        // 问题表
        $tmodel = M('tquestion');
        $qarr = $tmodel->where(
            array(
                'label_id' => $this->batch_id))
            ->order('sort')
            ->select();
        
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
        if ($this->hgds_flag) {
            $info = M()->table('tchannel a, tstore_info b')
                ->where(
                "a.id = '{$this->channel_id}' and a.store_id = b.store_id")
                ->find();
            if ($info) {
                $lbs_arr['lbs_x'] = number_format($info['lbs_x'], 6, '.', '');
                $lbs_arr['lbs_y'] = number_format($info['lbs_y'], 6, '.', '');
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
        $this->assign('map_img', $map_img);
        $this->assign('id', $this->id);
        $this->assign('lbs_arr', $lbs_arr);
        $this->assign('query_arr', $query_arr);
        $this->assign('resp_arr', $resp_arr);
        $this->assign('row', $row);
        $this->assign('cj_text', $cj_text);
        $this->assign('cj_check_flag', $cj_check_flag);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        // $this->assign('node_name',$nodeName);
        // 该处为手机WAP页面 取活动所属商户号判断
        if ($this->hgds_flag) {
            $this->assign('hgds_range_remain', $this->_check_hgds_storage());
            $this->display('index_haagen_dazs');
        } else {
            if ($this->batch_id == '3207') {
                // 非标中医药
                $this->display('zy');
            } elseif ($this->batch_id == C('whfh')) {
                // 非标武汉峰会
                $this->display('whfh');
            } elseif ($this->batch_id == C('whfhQd')) {
                $this->display('whfhQd');
            } elseif ($this->batch_id == C('o2oSzSalon')) {
                $this->display('o2oSzSalon');
            } else {
                $this->display();
            }
        }
    }

    public function _check_hgds_storage() {
        $hgds_range_remain = '-';
        // 获取门店库存控制信息，判断当前时段库存余量
        do {
            $map = array(
                'a.id' => $this->channel_id, 
                'a.store_id' => array(
                    'exp', 
                    '=b.store_id'));
            $info = M()->table('tchannel a, tfb_hgds_storage b')
                ->where($map)
                ->field('a.store_id, b.storage_conf')
                ->find();
            
            $store_id = $info['store_id'];
            $storage_conf = $info['storage_conf'];
            if (! $storage_conf)
                break;
            
            $conf = json_decode($storage_conf, true);
            $n = date('N');
            $h = intval(date('H') / 2) + 1;
            // 判断是否有配置当前时段库存
            if (! isset($conf['day'][$n]))
                break;
            
            $day_count = $conf['day'][$n];
            // 统计未配置的剩余数量
            $unassigned_num = $day_count - array_sum($conf['timeRange'][$n]);
            
            // 没有设置时段库存
            if (! isset($conf['timeRange'][$n][$h]) ||
                 $conf['timeRange'][$n][$h] == '') {
                if ($unassigned_num == 0) {
                    $hgds_range_remain = 0;
                    $msg = '该时段的奖品已经发完';
                    break;
                }
                
                // 处理时间段，删除为空的配置
                $arr = $conf['timeRange'][$n];
                foreach ($arr as $k => $v) {
                    if ($v == '')
                        unset($arr[$k]);
                }
                // 统计哪些时间段未设置
                $unassigned_range = array_diff_key(range(1, 12), 
                    array_keys($arr));
                if (! $unassigned_range) { // 这个分支是不可能的....
                    break;
                }
                
                // 获取未配置数量中，有多少是已经发送的
                $map = array(
                    'store_id' => $store_id, 
                    'stat_day' => date('Ymd'), 
                    'time_range' => array(
                        'in', 
                        $unassigned_range));
                $sent_cnt = M('tfb_hgds_storage_stat')->where($map)->sum(
                    'trans_num');
                
                $num = $unassigned_num - $sent_cnt;
                if ($num <= 3 && $num >= 1) {
                    $hgds_range_remain = $num;
                    $msg = "当前时段的奖品数量仅剩{$num}份，请尽快填写问卷并提交！";
                    break;
                }
                if ($num < 1) {
                    $hgds_range_remain = 0;
                    $msg = "当前时段奖品已发完，是否继续参加问卷调查？";
                    break;
                }
            }  // 设置了时段库存
else {
                $range_num = $conf['timeRange'][$n][$h];
                // 获取未配置数量中，有多少是已经发送的
                $map = array(
                    'store_id' => $store_id, 
                    'stat_day' => date('Ymd'), 
                    'time_range' => $h);
                $sent_cnt = (int) M('tfb_hgds_storage_stat')->where($map)->sum(
                    'trans_num');
                $num = $range_num - $sent_cnt;
                if ($num <= 3 && $num >= 1) {
                    $hgds_range_remain = $num;
                    $msg = "当前时段的奖品数量仅剩{$num}份，请尽快填写问卷并提交！";
                    break;
                }
                if ($num < 1) {
                    $hgds_range_remain = 0;
                    $msg = "当前时段奖品已发完，是否继续参加问卷调查？";
                    break;
                }
            }
        }
        while (0);
        
        return $hgds_range_remain;
    }
    
    // 哈根达斯，校验手机号是否已经参与过活动
    public function hgds_check_phone() {
        $mobile = I('mobile', '', 'trim,mysql_real_escape_string');
        $map = array(
            'label_id' => $this->batch_id, 
            'defined_one' => $mobile);
        $cnt = M('tbm_trace')->where($map)->count();
        if ($cnt > 0)
            $this->error('您已经参与过该活动了！');
        else
            $this->success();
    }

    public function submit() {
        $overdue = $this->checkDate();
        if ($overdue === false) {
            $msg = '该活动不在有效期之内！';
            
            $this->ajaxReturn("error", $msg, 0);
        }
        $id = $this->id;
        $batch_id = $this->batch_id;
        $batch_type = $this->batch_type;
        $node_id = $this->node_id;
        
        $bmtrace = M('tbm_trace');
        $mobile1 = I('mobile');
        $result = $bmtrace->where(
            array(
                'mobile' => $mobile1, 
                'label_id' => $batch_id))->find();
        
        if ($result) {
            $error_msg = '该手机号已参加过了!';
            $this->error($error_msg);
        }
        if (! $this->isPost())
            $this->ajaxReturn("error", "非法提交！", 0);
            
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
            '3' => 'sex');
        
        $bm_arr = array(
            '1' => '姓名', 
            '2' => '手机号', 
            '3' => '性别', 
            '4' => '年龄', 
            '5' => '学历', 
            '6' => '收信地址', 
            '7' => '邮箱', 
            '8' => '公司名称', 
            '9' => '职位', 
            '13' => '上传图片');
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
                            'null' => false)))
                        $this->ajaxReturn("error", "请完善页面所有信息！", 0);
            }
            $column_arr[$get_bm_arr[$v]] = I($get_bm_arr[$v]);
        }
        $bmtrace = M('tbm_trace');
        
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
            /*
             * $upload->thumb=true; $upload->thumbPrefix='m';
             * $upload->thumbMaxWidth=150; $upload->thumbMaxHeight=150;
             * $upload->thumbRemoveOrigin=true;
             */
            
            if (! $upload->upload()) { // 上传错误提示错误信息
                                       // $this->errormsg =
                                       // $upload->getErrorMsg();
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
                
                if ($r) {
                    $q_arr = array(
                        'label_id' => $batch_id, 
                        'bm_seq_id' => $bm_seq_id, 
                        'question_id' => $q_v['id'], 
                        'answer_list' => $v_arr[$r], 
                        'add_time' => date('Ymd'));
                } else {
                    $tranDb->rollback();
                    $this->ajaxReturn("error", "请完善页面所有信息！", 0);
                }
                $qsmodel = M('tquestion_stat');
                $query = $qsmodel->add($q_arr);
                /* 每个调研问题的结果保存一个给df表里面 */
                $question_name = M('tquestion')->where(
                    array(
                        'id' => $q_v['id']))
                    ->field('questions')
                    ->find();
                
                $question_answer = M('tquestion_info')->where(
                    array(
                        'question_id' => $q_v['id']))->select();
                
                $answer_checked = "";
                foreach ($question_answer as $answer) {
                    if ($answer['value'] == $r) {
                        $answer_checked = $answer['answers'];
                    }
                }
                $q_arr = array(
                    'mobile' => I('mobile'), 
                    'name' => $question_name["questions"], 
                    'answer' => $answer_checked, 
                    'add_time' => date('Ymd'));
                
                $qsmodel = M('tfb_df_question_answer');
                $sync_flag = $qsmodel->where(
                    "mobile ='" . $q_arr['mobile'] . " ' AND name='" .
                         $question_name["questions"] . "'")->find();
                if (! $sync_flag) {
                    $query = $qsmodel->add($q_arr);
                } else {
                    $query = $qsmodel->where(
                        "mobile ='" . $q_arr['mobile'] . " ' AND name='" .
                             $question_name["questions"] . "'")->save($q_arr);
                }
            }
        }
        
        // 粉丝添加
        if (! empty($column_arr['mobile'])) {
            
            $data = array(
                'name' => $column_arr['true_name'], 
                'phone_no' => $column_arr['mobile'], 
                'age' => $column_arr['mobile']);
            if ($column_arr['sex'] == '男') {
                $data['sex'] = '1';
            } elseif ($column_arr['sex'] == '女') {
                $data['sex'] = '2';
            }
        }
        if ($bm_seq_id !== false && $query !== false) {
            $tranDb->commit();
            if ($is_cj == '1') {
                $this->ajaxReturn("success_cj", "添加成功！", 1);
            } else {
                $this->ajaxReturn("success", "添加成功！", 1);
            }
        } else {
            $tranDb->rollback();
            $this->ajaxReturn("error", "添加失败！", 0);
        }
    }
}
