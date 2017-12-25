<?php

class MarketActiveAction extends YhbAction {

    public $_authAccessMap = '*';

    private $style_arr = array();

    private $transUrlsAdd = array();

    private $transUrlsEdit = array();

    private $batchList = array();

    private $batchList1 = array();

    private $batchList2 = array();

    private $batchList3 = array();

    private $batchList4 = array();

    const MEMBER_RECRUIT_BATCH_TYPE = '52';

    public function _initialize() {
        parent::_initialize();
        
        // 隔离管理员与用户的权限
        $admin_rule = array(
            'listcheck', 
            'checkshow');
        $actionName = strtolower(ACTION_NAME);
        if ($this->is_admin) {
            if (! in_array($actionName, $admin_rule)) {
                $this->redirect(U('Yhb/MarketActive/listCheck'));
            }
        } else {
            if ($actionName == 'listcheck') {
                $this->redirect(U('Yhb/MarketActive/listNew'));
            }
        }
    }
    
    // 开展活动
    public function createNew() {
        // 获取当前活动属于哪一类
        ! I('typelist', '0') or $map['batch_belongto'] = I('typelist', '0');
        // 活动必须有效
        $map['status'] = 1;
        // 列表模板不在此处显示
        $map['batch_type'] = array(
            'in', 
            array(
                '2', 
                '3', 
                '10', 
                '20'));
        
        // 导入分页类
        import('@.ORG.Util.Page');
        $mapcount = M('tmarketing_active')->where($map)->count();
        $Page = new Page($mapcount, 8); // 实例化分页类
        $Page->setConfig('theme', 
            '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $Page->setConfig('prev', '«');
        $Page->setConfig('next', '»');
        $show = $Page->show(); // 分页显示输出
        $batchList = M('tmarketing_active')->where($map)
            ->limit($Page->firstRow, $Page->listRows)
            ->order('batch_order')
            ->select();
        
        foreach ($batchList as $k => $v) {
            $batchList[$k]['batch_create_url'] = U('Yhb/MarketActive/createUrl') .
                 "&actionName=" . $v['batch_create_url'];
        }
        // 获取活动图标信息
        $styleInfo = M('tmarketing_active')->field('batch_type,batch_icon')->select();
        foreach ($styleInfo as $k1 => $v1) {
            $this->style_arr[$v1['batch_type']] = $v1['batch_icon'];
        }
        
        $this->assign('style_arr', $this->style_arr);
        $this->assign('batchList', $batchList);
        $this->assign('typelist', I('typelist', '0'));
        $this->assign('page', $show);
        $this->display();
    }
    
    // 活动列表
    public function listNew() {
        $marketingActive = M('tmarketing_active');
        $marketingInfoModel = M("tmarketing_info");
        $batch_name = I('key', '', 'htmlspecialchars,trim');
        $start_time = I('start_time', '', 'htmlspecialchars,trim');
        $end_time = I('end_time', '', 'htmlspecialchars,trim');
        $batchType = I('batchtype', '');
        $status = I('status', '');
        $_status = I('_status', '');
        $liststyle = I('liststyle', '1');
        $in_batch_type = array(
            '2', 
            '3', 
            '10', 
            '20');
        $marketingArr = $marketingActive->where(
            array(
                'status' => '1', 
                'batch_type' => array(
                    'in', 
                    $in_batch_type)))
            ->field('batch_type,batch_create_url')
            ->select();
        
        $batch_type = array();
        $batch_Url = array();
        foreach ($marketingArr as $k1 => $v1) {
            $batch_type[] = $v1['batch_type'];
            $batch_Url[$v1['batch_type']] = $v1['batch_create_url'];
        }
        // 营销活动比较特殊，不应根据status来判断,不管列表模板是否开启，都应该在列表模板页面显示
        if ($liststyle == '2') {
            $batch_type[] = "8";
            $batch_Url['8'] = 'List';
        } else {
            foreach ($batch_type as $kj => $vj) {
                if ($vj == '8') {
                    unset($batch_type[$kj]);
                }
            }
        }
        
        if (! $this->is_admin) {
            $map['m.merchant_id'] = $this->merchant_id;
        }
        
        $map = array(
            't.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            't.batch_type' => array(
                'in', 
                $batch_type));
        if (! empty($batch_name)) {
            $map['t.name'] = array(
                'like', 
                '%' . $batch_name . '%');
            $this->assign('batch_name', $batch_name);
        }
        if (! empty($start_time)) {
            $map['t.add_time'][] = array(
                'EGT', 
                $start_time . '000000');
            $this->assign('start_time', $start_time);
        }
        if (! empty($end_time)) {
            $map['t.add_time'][] = array(
                'ELT', 
                $end_time . '235959');
            $this->assign('end_time', $end_time);
        }
        if (! empty($batchType)) {
            // 限制活动类型
            if (in_array($batchType, $in_batch_type)) {
                $map['t.batch_type'] = $batchType;
                $this->assign('batchType', $batchType);
            }
        }
        if (! empty($status)) {
            if ($status == '1') {
                $map['m.pub_status'] = array(
                    'lt', 
                    '2');
                $map['t.start_time'] = array(
                    'GT', 
                    date('Ymd') . '000000');
            } else if ($status == '2') {
                $map['m.pub_status'] = 2;
                $map['t.start_time'] = array(
                    'ELT', 
                    date('Ymd') . '000000');
                $map['t.end_time'] = array(
                    'EGT', 
                    date('Ymd') . '235959');
            }
            $this->assign('status', $status);
        }
        
        if (! empty($_status)) {
            $map['m.pub_status'] = 2;
            $map['t.status'] = $_status == '2' ? '2' : '1';
            $this->assign('_status', $_status);
        }
        
        $map['m.id'] = array(
            'neq', 
            "");
        $map['m.merchant_id'] = $this->merchant_id;
        
        import('@.ORG.Util.Page'); // 导入分页类
        $mapcount = $marketingInfoModel->alias("t")->join(
            'tfb_yhb_mconfig m ON m.mid = t.id')
            ->where($map)
            ->count();
        $Page = new Page($mapcount, 8); // 实例化分页类
        $Page->setConfig('theme', 
            '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $Page->setConfig('prev', '«');
        $Page->setConfig('next', '»');
        $show = $Page->show(); // 分页显示输出
        
        $list = $marketingInfoModel->alias("t")->join('tfb_yhb_mconfig m ON m.mid = t.id')
            ->field(
            'm.last_check_id cid,m.pub_status p_status,m.id config_id,t.*')
            ->where($map)
            ->order('t.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        if ($list) {
            $cjRuleModel = M('TcjRule');
            $ciBatchModel = M('TcjBatch');
            $check_trace_model = M('tfb_yhb_check_trace');
            foreach ($list as $k => $v) {
                $is_publish = M('tbatch_channel')->getFieldByBatch_id($v['id'], 
                    "id");
                if (empty($is_publish)) {
                    $list[$k]['is_publish'] = 0;
                } else {
                    $list[$k]['is_publish'] = 1;
                }
                $list[$k]['edit_url'] = U('Yhb/MarketActive/editUrl', 
                    array(
                        'actionName' => $batch_Url[$v['batch_type']], 
                        'id' => $v['id']));
                $list[$k]['check_url'] = U('Yhb/MarketActive/checkShow', 
                    array(
                        'actionName' => $batch_Url[$v['batch_type']], 
                        'id' => $v['id']));
                $list[$k]['actionName'] = $batch_Url[$v['batch_type']];
                $list[$k]['is_mem_batch'] = 'N';
                if (strtotime($v['end_time']) >= time()) {
                    $list[$k]['leave_time'] = floor(
                        (strtotime($v['end_time']) - time()) / (3600 * 24));
                } else {
                    $list[$k]['leave_time'] = '0';
                }
                if ($v['is_cj'] == '1') {
                    $rule_id = $cjRuleModel->where(
                        array(
                            'batch_id' => $v['id'], 
                            'node_id' => $v['node_id'], 
                            'status' => '1'))->getField('id');
                    $mem_batch = $ciBatchModel->where(
                        array(
                            'cj_rule_id' => $rule_id, 
                            'member_batch_id' => array(
                                'neq', 
                                '')))->find();
                    if ($mem_batch) {
                        $list[$k]['is_mem_batch'] = 'Y';
                    }
                }
                if ($v['p_status'] == 3) {
                    $trace_info = $check_trace_model->field('check_memo')
                        ->where(
                        array(
                            'id' => $v['cid'], 
                            'check_status' => 2))
                        ->order('id desc')
                        ->find();
                    if (! empty($trace_info)) {
                        $list[$k]['trace_info'] = $trace_info;
                    }
                }
            }
        }
        $node_short_name = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->getField('node_short_name');
        
        // 审核状态
        $apply_status = array(
            0 => '正常', 
            1 => '待审核', 
            2 => '审核通过', 
            3 => '审核拒绝');
        
        $this->assign('apply_status', $apply_status);
        $this->assign('batchlist', $list);
        $this->assign('batchInfo', $batch_type);
        $this->assign('liststyle', $liststyle);
        $this->assign('batch_type_name', C(BATCH_TYPE_NAME));
        $this->assign('node_short_name', $node_short_name);
        $this->assign('page', $show);
        $this->display();
    }
    
    // 审核列表
    public function listCheck() {
        $marketingActive = M('tmarketing_active');
        $marketingInfoModel = M("tmarketing_info");
        $batch_name = I('key', '', 'htmlspecialchars,trim');
        $start_time = I('start_time', '', 'htmlspecialchars,trim');
        $end_time = I('end_time', '', 'htmlspecialchars,trim');
        $batchType = I('batchtype', '');
        $status = I('status', '');
        $liststyle = I('liststyle', '1');
        $pub_status = I('pub_status', '');
        $nickname = I('nickname', '');
        $_status = I('_status', '');
        $in_batch_type = array(
            '2', 
            '3', 
            '10', 
            '20');
        $marketingArr = $marketingActive->where(
            array(
                'status' => '1', 
                'batch_type' => array(
                    'in', 
                    $in_batch_type)))
            ->field('batch_type,batch_create_url')
            ->select();
        
        $batch_type = array();
        $batch_Url = array();
        foreach ($marketingArr as $k1 => $v1) {
            $batch_type[] = $v1['batch_type'];
            $batch_Url[$v1['batch_type']] = $v1['batch_create_url'];
        }
        // 营销活动比较特殊，不应根据status来判断,不管列表模板是否开启，都应该在列表模板页面显示
        if ($liststyle == '2') {
            $batch_type[] = "8";
            $batch_Url['8'] = 'List';
        } else {
            foreach ($batch_type as $kj => $vj) {
                if ($vj == '8') {
                    unset($batch_type[$kj]);
                }
            }
        }
        $map = array(
            't.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            't.batch_type' => array(
                'in', 
                $batch_type));
        if (! empty($batch_name)) {
            $map['t.name'] = array(
                'like', 
                '%' . $batch_name . '%');
            $this->assign('batch_name', $batch_name);
        }
        if (! empty($start_time)) {
            $map['t.add_time'][] = array(
                'EGT', 
                $start_time . '000000');
            $this->assign('start_time', $start_time);
        }
        if (! empty($end_time)) {
            $map['t.add_time'][] = array(
                'ELT', 
                $end_time . '235959');
            $this->assign('end_time', $end_time);
        }
        if (! empty($batchType)) {
            // 限制活动类型
            if (in_array($batchType, $in_batch_type)) {
                $map['t.batch_type'] = $batchType;
                $this->assign('batchType', $batchType);
            }
        }
        if ($pub_status !== '') {
            $map['m.pub_status'] = $pub_status;
            if ($pub_status == 0) {
                $map['t.end_time'] = array(
                    'GT', 
                    date('Ymd') . '000000');
            }
            $this->assign('pub_status', (int) $pub_status);
        } else {
            $map['m.pub_status'] = array(
                'in', 
                '1,2,3');
        }
        
        if (! empty($status)) {
            if ($status == '1') {
                $map['m.pub_status'] = array(
                    'lt', 
                    '2');
                $map['t.start_time'] = array(
                    'GT', 
                    date('Ymd') . '000000');
            } else if ($status == '2') {
                $map['m.pub_status'] = 2;
                $map['t.start_time'] = array(
                    'ELT', 
                    date('Ymd') . '000000');
                $map['t.end_time'] = array(
                    'EGT', 
                    date('Ymd') . '235959');
            }
            $this->assign('status', $status);
        }
        
        if (! empty($_status)) {
            $map['m.pub_status'] = 2;
            $map['t.status'] = $_status == '2' ? '2' : '1';
            $this->assign('_status', $_status);
        }
        
        if (! empty($nickname)) {
            $map['n.merchant_name'] = array(
                'like', 
                '%' . (string) $nickname . '%');
            $this->assign('nickname', $nickname);
        }
        
        $map['m.id'] = array(
            'neq', 
            "");
        
        import('@.ORG.Util.Page'); // 导入分页类
        $mapcount = $marketingInfoModel->alias("t")->join(
            'tfb_yhb_mconfig m ON m.mid = t.id')
            ->join("tfb_yhb_node_info n ON m.merchant_id = n.id")
            ->where($map)
            ->count();
        $Page = new Page($mapcount, 8); // 实例化分页类
        $Page->setConfig('theme', 
            '%upPage% %prePage%  %linkPage%  %nextPage% %downPage% ');
        $Page->setConfig('prev', '«');
        $Page->setConfig('next', '»');
        $show = $Page->show(); // 分页显示输出
        
        $list = $marketingInfoModel->alias("t")->join('tfb_yhb_mconfig m ON m.mid = t.id')
            ->join("tfb_yhb_node_info n ON m.merchant_id = n.id")
            ->field(
            'n.status is_use,n.merchant_name,m.last_check_id cid,m.pub_status p_status,m.id config_id,t.*')
            ->where($map)
            ->order('t.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        if ($list) {
            $cjRuleModel = M('TcjRule');
            $ciBatchModel = M('TcjBatch');
            $check_trace_model = M('tfb_yhb_check_trace');
            foreach ($list as $k => $v) {
                $is_publish = M('tbatch_channel')->getFieldByBatch_id($v['id'], 
                    "id");
                if (empty($is_publish)) {
                    $list[$k]['is_publish'] = 0;
                } else {
                    $list[$k]['is_publish'] = 1;
                }
                $list[$k]['edit_url'] = U('Yhb/MarketActive/editUrl', 
                    array(
                        'actionName' => $batch_Url[$v['batch_type']], 
                        'id' => $v['id']));
                $list[$k]['check_url'] = U('Yhb/MarketActive/checkShow', 
                    array(
                        'actionName' => $batch_Url[$v['batch_type']], 
                        'id' => $v['id']));
                $list[$k]['actionName'] = $batch_Url[$v['batch_type']];
                $list[$k]['is_mem_batch'] = 'N';
                if (strtotime($v['end_time']) >= time()) {
                    $list[$k]['leave_time'] = floor(
                        (strtotime($v['end_time']) - time()) / (3600 * 24));
                } else {
                    $list[$k]['leave_time'] = '0';
                }
                if ($v['is_cj'] == '1') {
                    $rule_id = $cjRuleModel->where(
                        array(
                            'batch_id' => $v['id'], 
                            'node_id' => $v['node_id'], 
                            'status' => '1'))->getField('id');
                    $mem_batch = $ciBatchModel->where(
                        array(
                            'cj_rule_id' => $rule_id, 
                            'member_batch_id' => array(
                                'neq', 
                                '')))->find();
                    if ($mem_batch) {
                        $list[$k]['is_mem_batch'] = 'Y';
                    }
                }
                $trace_info = $check_trace_model->field(
                    'check_stauts c_status,check_memo')
                    ->where(array(
                    'id' => $v['cid']))
                    ->order('id desc')
                    ->find();
                if (! empty($trace_info)) {
                    $list[$k]['trace_info'] = $trace_info;
                }
            }
        }
        $node_short_name = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->getField('node_short_name');
        
        // 审核状态
        $apply_status = array(
            0 => '正常', 
            1 => '待审核', 
            2 => '审核通过', 
            3 => '审核拒绝');
        
        $this->assign('apply_status', $apply_status);
        $this->assign('batchlist', $list);
        $this->assign('batchInfo', $batch_type);
        $this->assign('liststyle', $liststyle);
        $this->assign('batch_type_name', C(BATCH_TYPE_NAME));
        $this->assign('node_short_name', $node_short_name);
        $this->assign('page', $show);
        $this->display();
    }

    public function createUrl() {
        $actionName = I('actionName', "");
        ! empty($actionName) or $this->error("非法操作!");
        switch ($actionName) {
            // 抽奖
            case 'News':
                $addUrl = U('Yhb/News/add') .
                     "&model=event&type=draw&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 市场调研
            case 'Bm':
                $addUrl = U('Yhb/Bm/add') .
                     "&model=event&type=survey&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 有奖答题
            case 'Answers':
                $addUrl = U('Yhb/Answers/add') .
                     "&model=event&type=question&action=create&customer=" .
                     $this->node_type_name;
                break;
            // 投票
            case 'Vote':
                $addUrl = U('Yhb/Vote/add') .
                     "&model=event&type=survey&action=create&customer=" .
                     $this->node_type_name;
                break;
            default:
                $this->error("此活动无法创建!");
                break;
        }
        redirect($addUrl);
    }

    public function editUrl() {
        $actionName = I('actionName', "");
        $id = I('id', "");
        ! empty($actionName) or $this->error("非法操作!");
        ! empty($id) or $this->error("非法操作!");
        switch ($actionName) {
            // 抽奖
            case 'News':
                $editUrl = U('Yhb/News/edit');
                break;
            // 市场调研
            case 'Bm':
                $editUrl = U('Yhb/Bm/edit');
                break;
            // 有奖答题
            case 'Answers':
                $editUrl = U('Yhb/Answers/edit');
                break;
            // 投票
            case 'Vote':
                $editUrl = U('Yhb/Vote/edit');
                break;
            default:
                $this->error("此活动无法编辑!");
                break;
        }
        redirect($editUrl . '&id=' . $id);
    }
    
    // 审核显示
    public function checkShow() {
        $id = I('id', "");
        $actionName = I('actionName', "");
        ! empty($actionName) or $this->error("非法操作!");
        ! empty($id) or $this->error("非法操作!");
        
        $batch_type = M('tmarketing_info')->where(
            array(
                'id' => $id))->getField('batch_type');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $query_arr = $baseActivityModel->checkactivityexist($id, $batch_type, 
            $this->nodeIn());
        if ($query_arr['log_img'] == '') {
            $nodeInfo = get_node_info($this->node_id);
            $this->assign('head_photo', get_upload_url($nodeInfo['head_photo']));
            $this->assign('node_logo', $nodeInfo['head_photo']);
        }
        
        switch ($actionName) {
            // 抽奖
            case 'News':
                break;
            // 市场调研
            case 'Bm':
                $this->showBm($id);
                break;
            // 有奖答题
            case 'Answers':
                $this->showAnswers($id);
                break;
            // 投票
            case 'Vote':
                $this->showVote($id);
                break;
            default:
                $this->error("此活动无法查看!");
                break;
        }
        $where['mid'] = $id;
        $config_info = M('tfb_yhb_mconfig')->field('id,pub_status,merchant_id')
            ->where($where)
            ->find();
        $_where['relation_id'] = $id;
        $_where['check_status'] = 2;
        $e_msg_list = M('tfb_yhb_check_trace')->where($_where)
            ->order('check_time desc')
            ->select();
        $this->assign('e_msg_list', $e_msg_list);
        $n_where['id'] = $config_info['merchant_id'];
        $user_is_use = M('tfb_yhb_node_info')->where($n_where)->getField(
            'status');
        $this->assign('user_is_use', $user_is_use == 1 ? true : false);
        $this->assign('config_id', $config_info['id']);
        $this->assign('config_status', $config_info['pub_status']);
        $this->assign('row', $query_arr);
        
        $this->showCj($id);
        $this->display("MarketActive:" . $actionName . "_show");
    }
    
    // 显示市场调研
    private function showBm($id) {
        
        // 是否有人参与此次调研
        $judge = M('tquestion_stat')->where(
            array(
                'label_id' => $id))->find();
        $fruit = empty($judge) ? 2 : 1;
        $this->assign('fruit', $fruit);
        
        $tquestion = M("tquestion");
        $tquestion_info = M("tquestion_info");
        $reuslt = $tquestion->where("label_id = '" . $id . "'")
            ->order("sort")
            ->select();
        foreach ($reuslt as $k => $v) {
            $answers_info = $tquestion_info->where(
                "question_id = '" . $v['id'] . "'")
                ->order()
                ->select();
            if ($answers_info !== false) {
                $reuslt[$k]['answers_list'] = $answers_info;
            }
        }
        $this->assign('question_row', $reuslt);
        $this->assign('can_use_picupload', 
            in_array($this->node_id, C('DM_PICUPLOAD_NODEIDS'), true));
        $config = include (CONF_PATH . "LabelAdmin/config.php");
        $bm_type_arr = $config['BM_TYPE_ARR'];
        $this->assign('bm_type_arr', $bm_type_arr);
    }
    
    // 显示有奖问答
    private function showAnswers($id) {
        
        // 是否有人参与此次答题
        $judge = M('tanswers_question_stat')->where(
            array(
                'label_id' => $id))->find();
        $fruit = empty($judge) ? 2 : 1;
        $this->assign('fruit', $fruit);
        
        $tquestion = M("tanswers_question");
        $tquestion_info = M("tanswers_question_info");
        $reuslt = $tquestion->where("label_id = '" . $id . "'")
            ->order("sort")
            ->select();
        foreach ($reuslt as $k => $v) {
            $answers_info = $tquestion_info->where(
                "question_id = '" . $v['id'] . "'")
                ->order()
                ->select();
            if ($answers_info !== false) {
                $reuslt[$k]['answers_list'] = $answers_info;
            }
        }
        $config = include (CONF_PATH . "LabelAdmin/config.php");
        $bm_type_arr = $config['BM_TYPE_ARR'];
        $this->assign('bm_type_arr', $bm_type_arr);
        $this->assign('question_row', $reuslt);
    }
    
    // 显示抽奖
    private function showVote() {
        // 是否有人参与此次投票
        $judge = M('tvote_question_stat')->where(
            array(
                'label_id' => $id))->find();
        $fruit = empty($judge) ? 2 : 1;
        $this->assign('fruit', $fruit);
        
        $tquestion = M("tvote_question");
        $tquestion_info = M("tvote_question_info");
        $reuslt = $tquestion->where("label_id = '" . $id . "'")
            ->order("sort")
            ->select();
        foreach ($reuslt as $k => $v) {
            $answers_info = $tquestion_info->where(
                "question_id = '" . $v['id'] . "'")
                ->order()
                ->select();
            if ($answers_info !== false) {
                $reuslt[$k]['answers_list'] = $answers_info;
            }
        }
        $this->assign('question_row', $reuslt);
    }
    
    // 显示抽奖信息
    private function showCj($batch_id) {
        $cjSetModel = D('CjSet');
        $cj_button_type = array(
            '2', 
            '3', 
            '10', 
            '20');
        
        if (empty($batch_id))
            $this->error('活动id不能为空！');
            
            // 校验活动
        $query_arr = M('tmarketing_info')->field(
            'id,cj_phone_type,member_join_flag,member_reg_mid,member_batch_award_id,fans_collect_url,batch_type,defined_one_name,member_batch_id,join_mode')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $batch_id))
            ->find();
        
        if (! $query_arr)
            $this->error('参数错误！');
        if ($query_arr['batch_type'] == '35') {
            $this->redirect(
                U('LabelAdmin/CjSetSnowBall/index', 
                    array(
                        'batch_id' => $batch_id)));
        }
        
        // 微信限制下线
        if (in_array($query_arr['batch_type'], 
            array(
                '42', 
                '34', 
                '30', 
                '36'))) {
            $this->error("该活动已下线");
            exit();
        }
        
        if (in_array($query_arr['batch_type'], $cj_button_type)) {
            $isShowCjButton = true;
        }
        
        // 设置
        $cj_rule_arr = M('tcj_rule')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $batch_id, 
                'status' => '1'))->find();
        
        $jp_arr = array();
        // 分类
        if ($cj_rule_arr) {
            $cj_cate_arr = M('tcj_cate')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_id' => $batch_id, 
                    'cj_rule_id' => $cj_rule_arr['id']))->select();
            
            // 奖品
            $jp_arr = M()->table('tcj_batch a')
                ->field('a.*,b.batch_name')
                ->join('tbatch_info b on a.b_id=b.id')
                ->where(
                "a.node_id='" . $this->node_id . "' and a.batch_id='" . $batch_id .
                     "' and cj_rule_id = '" . $cj_rule_arr['id'] . "'")
                ->select();
        }
        
        // 获取商户会员卡信息
        $mem_batch = $cjSetModel->getMemberBatch($this->node_id);
        $this->assign('mem_batch', $mem_batch);
        
        // 查询该机构的微信分组
        $user_wx_group = $cjSetModel->getWxGroup($this->node_id);
        $this->assign('user_wx_group', $user_wx_group);
        
        // 查询 点击“微信号”是否需要弹框//window_id为12表示这里要用到的弹窗号
        $wx_bd = M('tpop_window_control')->where(
            array(
                'node_id' => $this->node_id, 
                'window_id' => 12))->find() ? 1 : 0;
        $this->assign('wx_bd', $wx_bd);
        // 选中的粉丝
        
        $result = $cjSetModel->getSelectedGroup($query_arr['member_batch_id'], 
            $query_arr['member_batch_award_id'], $query_arr['join_mode'], 
            $mem_batch, $user_wx_group);
        $this->assign('phone_selected', $result['phone_selected']);
        $this->assign('wx_selected', $result['wx_selected']);
        $this->assign('phone_selected_zj', $result['phone_selected_zj']);
        $this->assign('wx_selected_zj', $result['wx_selected_zj']);
        
        $this->assign('phone_selected', $result['phone_selected']);
        $this->assign('wx_selected', $result['wx_selected']);
        $this->assign('phone_selected_zj', $result['phone_selected_zj']);
        $this->assign('wx_selected_zj', $result['wx_selected_zj']);
        
        // 是否有招募活动
        $phone_recruit = $query_arr['member_reg_mid'] ? 1 : 0;
        $this->assign('phone_recruit', $phone_recruit);
        $wx_recruit = $query_arr['fans_collect_url'] ? 1 : 0;
        $this->assign('wx_recruit', $wx_recruit);
        $this->assign('fans_collect_url', $query_arr['fans_collect_url']);
        
        // 是否限制
        $this->assign('member_batch_id_flag', 
            ($query_arr['member_batch_id'] == - 1 ? 0 : 1));
        $member_zj_flag = $query_arr['member_batch_award_id'] == - 1 ? 0 : 1;
        $this->assign('member_zj_flag', $member_zj_flag);
        
        // 未中奖选项学处理
        $cj_rule_arr['no_award_notice'] = explode('|', 
            $cj_rule_arr['no_award_notice']);
        $this->assign('batch_type', $query_arr['batch_type']);
        $this->assign('cj_phone_type', $query_arr['cj_phone_type']);
        $this->assign('query_arr', $query_arr);
        $this->assign('jp_arr', $jp_arr);
        $this->assign('cj_cate_arr', $cj_cate_arr);
        $this->assign('cj_rule_arr', $cj_rule_arr);
        $this->assign('batch_id', $batch_id);
        $this->assign('isShowCjButton', $isShowCjButton);
        $this->assign('defined_one_name', $query_arr['defined_one_name']);
        $this->assign('regName', 
            M('tmarketing_info')->where(
                "id='{$query_arr['member_reg_mid']}' and batch_type=" .
                     self::MEMBER_RECRUIT_BATCH_TYPE)
                ->getField('name'));
    }

    function test() {
        $phone = I('mobile');
        if (empty($phone)) {
            echo '手机号不能为空';
        }
        $where = array(
            'node_id' => $this->node_id, 
            'send_mobile' => $phone);
        $res = M('tcj_trace')->where($where)->delete();
        if ($res) {
            echo '删除成功';
        } else {
            echo '删除失败';
        }
    }
}

