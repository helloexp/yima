<?php

/**
 * @功能：投票 @更新时间: 2015/02/05 13:22
 */
class VoteAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE = '20';
    // 图片路径
    public $img_path;

    /**
     *
     * @var VoteQuestionModel
     */
    public $VoteQuestionModel;
    
    // 初始化
    public function _initialize() {
        parent::_initialize();
        $this->assign('batch_type_v', $this->BATCH_TYPE);
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
        $this->assign("node_id", $this->node_id);
        
        $this->VoteQuestionModel = D('VoteQuestion');
    }
    
    // 首页
    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        
        $data = I('request.');
        
        if (! empty($data['node_id']))
            $map['node_id '] = $data['node_id'];
        if ($data['key'] != '') {
            $map['name'] = array(
                'like', 
                '%' . $data['key'] . '%');
        }
        if ($data['status'] != '') {
            $map['status'] = $data['status'];
        }
        if ($data['start_time'] != '' && $data['end_time'] != '') {
            $map['add_time'] = array(
                'BETWEEN', 
                array(
                    $data['start_time'] . '000000', 
                    $data['end_time'] . '235959'));
        }
        
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $list = $model->where($map)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['is_mem_batch'] = 'N';
                if ($v['is_cj'] == '1') {
                    $rule_id = M('tcj_rule')->where(
                        array(
                            'batch_type' => $this->BATCH_TYPE, 
                            'batch_id' => $v['id'], 
                            'node_id' => $v['node_id'], 
                            'status' => '1'))->getField('id');
                    $mem_batch = M('tcj_batch')->where(
                        array(
                            'cj_rule_id' => $rule_id, 
                            'member_batch_id' => array(
                                'neq', 
                                '')))->find();
                    
                    if ($mem_batch) {
                        $list[$k]['is_mem_batch'] = 'Y';
                    }
                }
            }
        }
        
        // node_log("首页+市场调研");
        $arr_ = C('CHANNEL_TYPE');
        
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }
    
    // 添加页
    public function add() {
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        
        // 未付款的时候,需要提示有没有超过60,超过部分要另外收费
        $TwoFestivalAdminModel = D('TwoFestivalAdmin');
        $needShowTips = $TwoFestivalAdminModel->needShowExTips($this->node_id, '');
        $paySettingJson = D('ActivityPayConfig')->getDefaultPayConfigModelByBatchType($this->BATCH_TYPE);
        $paySetting = json_decode($paySettingJson, true);
        $this->assign('configOneActDays', $paySetting['duringTime']);
        $this->assign('exPrice', $paySetting['exPrice']);
        $this->assign('needShowTips', $needShowTips);
        $LimitInfo = $TwoFestivalAdminModel->getLimitInfo($this->node_id, '');
        $this->assign('type', $LimitInfo['type']);
        $this->assign('freeUseLimit', $LimitInfo['freeUseLimit']);
        
        $this->display();
    }
    
    // 添加提交页
    public function addSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->activityAddDataBaseVirefy($data, 
            $this->BATCH_TYPE, $this->img_path);
        
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'wap_info' => $data['wap_info'], 
            'log_img' => $result['log_img'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            // 'memo' => $data['memo'],
            // 'select_type'=>$select_type,
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            'size' => '2', 
            // 'code_img' => $data['resp_code_img'],
            'sns_type' => $result['sns_type'], 
            'chance' => $data['chance'], 
            'goods_count' => $data['goods_count'], 
            'day_goods_count' => $data['day_goods_count'], 
            'is_cj' => '0', 
            // 'jp_type'=>$data['jp_type'],
            // 'zc_batch_no'=>$data['zc_batch_no'],
            // 'wc_batch_no'=>$data['wc_batch_no'],
            // 'defined_one_name'=>$data['defined10'],
            // 'defined_two_name'=>$data['defined11'],
            // 'defined_three_name'=>$data['defined12'],
            'node_name' => $data['node_name'], 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $result['bg_img'], 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1', 
            'batch_come_from' => session('batch_from') ? session('batch_from') : '1');
        
        $tranDb = new Model();
        $tranDb->startTrans();
        $resp_id = $model->add($data_arr);
        if (! $resp_id) {
            $tranDb->rollback();
            $this->error('投票添加失败！', 
                array(
                    '返回投票' => U('index')));
        }
        // 唐山平安非标
        if ($this->node_id == C("tangshan.node_id") &&
             ($data['sub_name'] != "" || $data['tangshan_url'] != "")) {
            // 需要存入提交按钮名称和跳转的url
            $tangshan_data = array(
                "m_id" => $resp_id, 
                "node_id" => $this->node_id, 
                "sub_name" => $data['sub_name'], 
                "tangshan_url" => $data['tangshan_url'], 
                'add_time' => date('YmdHis'));
            $res_tangshan = M("tfb_tangshan_pingan")->add($tangshan_data);
            if ($res_tangshan === false) {
                $tranDb->rollback();
                $this->error('提交按钮和跳转url失败！', 
                    array(
                        '返回投票' => U('index')));
            }
        }
        // 处理投票
        for ($i = 1; $i <= 30; $i ++) {
            $qname = 'q_' . $i;
            $aname = 'a_' . $i;
            $tname = 't_' . $i;
            $sort = 'sort_' . $i;
            
            if ($data[$qname] == '')
                continue;
            $in_arr = array(
                'label_id' => $resp_id, 
                'questions' => $data[$qname], 
                'type' => $data[$tname], 
                'sort' => $data[$sort]);
            // 问题表
            $qmodel = M('tvote_question');
            $question_id = $qmodel->add($in_arr);
            if (! $question_id) {
                $tranDb->rollback();
                $this->error('系统错误！');
            }
            
            // 答案表
            $amodel = M('tvote_question_info');
            if ($data[$aname] != '' && is_array($data[$aname])) {
                $z = 0;
                foreach ($data[$aname] as $v) {
                    $z ++;
                    if (empty($v))
                        continue;
                    $in_arr = array(
                        'question_id' => $question_id, 
                        'answers' => $v, 
                        'value' => $z);
                    $query = $amodel->add($in_arr);
                    if (! $query) {
                        $tranDb->rollback();
                        $this->error('系统错误！');
                    }
                }
            }
        }
        
        $tranDb->commit();
        $ser = D('TmarketingInfo');
        $arr = array(
            'node_id' => $this->nodeId, 
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $resp_id);
        $ser->init($arr);
        $ser->sendBatch();
        
        node_log('投票活动添加|活动名:' . $data['name']);
        // 顺便发布到多乐互动专用渠道上
        $bchId = D('MarketCommon')->chPublish($this->nodeId,$resp_id);
        if($bchId === false){
            $this->error('发布到渠道失败');
        }
        $this->redirect('LabelAdmin/CjSet/index', 
            array(
                'batch_id' => $resp_id, 
                'from' => 'add'));
        exit();
        
        $this->success('添加成功！', 
            array(
                '设置抽奖' => U('LabelAdmin/CjSet/index', 
                    array(
                        'batch_id' => $resp_id)), 
                '发布到线上线下渠道' => U('LabelAdmin/BindChannel/index', 
                    array(
                        'batch_type' => $this->BATCH_TYPE, 
                        'batch_id' => $resp_id)), 
                '返回活动列表页' => U('index')));
    }
    
    // 编辑页
    public function edit() {
        $id = I('id');
        if (empty($id))
            $this->error('错误参数');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $query_arr = $baseActivityModel->checkactivityexist($id, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($query_arr['log_img'] == '') {
            $nodeInfo = get_node_info($this->node_id);
            $this->assign('head_photo', get_upload_url($nodeInfo['head_photo']));
            $this->assign('node_logo', $nodeInfo['head_photo']);
        }
        
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
        // 判断是否为唐山平安的有奖竟达活动
        if ($this->node_id == C("tangshan.node_id")) {
            // 需要存入提交按钮名称和跳转的url
            $tangshan_data = array(
                "m_id" => $id, 
                "node_id" => $this->node_id);
            $res_tangshan = M("tfb_tangshan_pingan")->where($tangshan_data)->find();
            if ($res_tangshan) {
                $this->assign('res_tangshan', $res_tangshan);
            }
        }
        $this->assign('question_row', $reuslt);
        $this->assign('row', $query_arr);
        
        // 未付款的时候,需要提示有没有超过60,超过部分要另外收费
        $TwoFestivalAdminModel = D('TwoFestivalAdmin');
        $needShowTips = $TwoFestivalAdminModel->needShowExTips($this->node_id, $id);
        $paySettingJson = D('ActivityPayConfig')->getDefaultPayConfigModelByBatchType($this->BATCH_TYPE);
        $paySetting = json_decode($paySettingJson, true);
        $this->assign('configOneActDays', $paySetting['duringTime']);
        $this->assign('exPrice', $paySetting['exPrice']);
        $this->assign('needShowTips', $needShowTips);
        $LimitInfo = $TwoFestivalAdminModel->getLimitInfo($this->node_id, $id);
        $this->assign('type', $LimitInfo['type']);
        $this->assign('freeUseLimit', $LimitInfo['freeUseLimit']);
        $this->display();
    }
    
    // 编辑提交页
    public function editSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $baseActivityModel->checkisactivityheadinfowrite($data);
        $baseActivityModel->checkisactivitynamesame($data['name'], 
            $this->BATCH_TYPE, $data['id']);
        if (! empty($data['sns_type'])) {
            $sns_type = $baseActivityModel->implodearray($data['sns_type']);
        } else {
            $sns_type = '';
        }
        
        // 是否有人参与此次投票
        $judge = M('tvote_question_stat')->where(
            array(
                'label_id' => $data['id']))->find();
        
        $query_arr = $baseActivityModel->checkactivityexist($data['id'], 
            $this->BATCH_TYPE, $this->nodeIn());
        $node_id = $query_arr['node_id'];
        
        // 背景图
        if ($data['resp_bg_img'] != '' && $data['reset_bg'] == '1') {
            // $bg_img =
            // $baseActivityModel->activitybackgroundpicupload($data['resp_bg_img'],
            // $this->BATCH_TYPE, $this->img_path);
            // } else {
            $bg_img = $data['resp_bg_img'];
        }
        
        $bm_id = $data['id'];
        $map = array(
            'node_id' => $node_id, 
            'id' => $data['id']);
        $data_arr = array(
            'name' => $data['name'], 
            // 'color'=>$data['color'],
            'wap_title' => $data['wap_title'], 
            'wap_info' => $data['wap_info'], 
            'log_img' => $data['resp_log_img'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            // 'memo' => $data['memo'],
            // 'select_type'=>$select_type,
            // 'size' => $data['size'],
            // 'code_img' => $data['resp_code_img'],
            'sns_type' => $sns_type, 
            // 'defined_one_name'=>$data['defined10'],
            // 'defined_two_name'=>$data['defined11'],
            // 'defined_three_name'=>$data['defined12'],
            'node_name' => $data['node_name'], 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $bg_img, 
            'is_show' => '1');
        
        $log_img = $data['resp_log_img'];
        $music = $data['resp_music'];
        if ($data['is_log_img'] == '1' && ! empty($log_img)) {
            $data_arr['log_img'] = $log_img;
        } else {
            if ($data['is_log_img'] == '0') {
                $data_arr['log_img'] = '';
            }
        }
        // 如果状态是付费中(不能让他修改时间);
        $isInPay = D('Order')->isInPay($this->node_id, $data['id']);
        if ($isInPay) {
            $this->error('订单已生成，活动时间不可更改。如需更改时间，请先到<a target="_blank" href="' .
                         U('Home/ServicesCenter/myOrder') . '">我的订单</a>中取消订单。');
        }
        // 检查是否有没有超过购买的期限
        $isFreeUser = D('node')->getNodeVersion($this->node_id);
        try {
            D('TwoFestivalAdmin')->checkLimitDay($this->node_id,
                $isFreeUser, $data['id'], $data_arr['start_time'],
                $data_arr['end_time']);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        
        $tranDb = new Model();
        $tranDb->startTrans();
        $resp_id = $model->where($map)->save($data_arr);
        
        if ($resp_id === false) {
            $tranDb->rollback();
            $this->error('投票编辑失败！', 
                array(
                    '返回投票' => U('index')));
        }
        // 唐山平安非标
        if ($this->node_id == C("tangshan.node_id")) {
            // 需要存入提交按钮名称和跳转的url
            $tangshan_data = array(
                "m_id" => $data['id'], 
                "node_id" => $this->node_id);
            // 查询用户是否有标记记录
            $res_tangshan = M("tfb_tangshan_pingan")->where($tangshan_data)->find();
            if ($res_tangshan) {
                $tangshan_arr = array(
                    "sub_name" => $data['sub_name'], 
                    "tangshan_url" => $data['tangshan_url']);
                $res = M("tfb_tangshan_pingan")->where($tangshan_data)->save(
                    $tangshan_arr);
                if ($res === false) {
                    $tranDb->rollback();
                    $this->error('唐山平安存储失败！', 
                        array(
                            '返回投票' => U('index')));
                }
            } else {
                // 需要存入提交按钮名称和跳转的url
                if ($data['sub_name'] != "" || $data['tangshan_url'] != "") {
                    $tangshan_data = array(
                        "m_id" => $data['id'], 
                        "node_id" => $this->node_id, 
                        "sub_name" => $data['sub_name'], 
                        "tangshan_url" => $data['tangshan_url'], 
                        'add_time' => date('YmdHis'));
                    $res_tangshan = M("tfb_tangshan_pingan")->add(
                        $tangshan_data);
                    if ($res_tangshan === false) {
                        $tranDb->rollback();
                        $this->error('提交按钮和跳转url失败！', 
                            array(
                                '返回投票' => U('index')));
                    }
                }
            }
        }
        if (! $judge) {
            // 测试
            $cjrulem = M('tcj_rule');
            // 更新状态
            $uparr = array(
                'node_id' => $node_id, 
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $data['id']);
            $up = $cjrulem->where($uparr)->save(
                array(
                    'cj_resp_text' => get_val($data,'cj_resp_text','')));
            
            // 投票
            $q_stat = M('tvote_question_stat');
            $q_qeury = $q_stat->where(
                array(
                    'label_id' => $bm_id))->find();
            if (! $q_qeury) {
                $tquestion = M("tvote_question");
                $tquestion_info = M("tvote_question_info");
                
                $query_arr = $tquestion->where(
                    array(
                        'label_id' => $bm_id))->getField('id', true);
                if (is_array($query_arr)) {
                    
                    $info_map['question_id'] = array(
                        'in', 
                        $query_arr);
                    $res = $tquestion_info->where($info_map)->delete();
                }
                $res = $tquestion->where(
                    array(
                        'label_id' => $bm_id))->delete();
                
                // 处理问卷
                for ($i = 1; $i <= 30; $i ++) {
                    $qname = 'q_' . $i;
                    $aname = 'a_' . $i;
                    $tname = 't_' . $i;
                    $sort = 'sort_' . $i;
                    
                    if ($data[$qname] == '')
                        continue;
                    $in_arr = array(
                        'label_id' => $bm_id, 
                        'questions' => $data[$qname], 
                        'type' => $data[$tname], 
                        'sort' => $data[$sort]);
                    // 问题表
                    $qmodel = M('tvote_question');
                    $question_id = $qmodel->add($in_arr);
                    if (! $question_id) {
                        $tranDb->rollback();
                        $this->error('系统错误！');
                    }
                    
                    // 答案表
                    $amodel = M('tvote_question_info');
                    if ($data[$aname] != '' && is_array($data[$aname])) {
                        $z = 0;
                        foreach ($data[$aname] as $v) {
                            $z ++;
                            if (empty($v))
                                continue;
                            $in_arr = array(
                                'question_id' => $question_id, 
                                'answers' => $v, 
                                'value' => $z);
                            $query = $amodel->add($in_arr);
                            if (! $query) {
                                $tranDb->rollback();
                                $this->error('系统错误！');
                            }
                        }
                    }
                }
            }
        }
        $tranDb->commit();
        node_log('投票活动编辑|活动名:' . $data['name'] . '|活动ID：' . $data['id']);
        redirect(
            U('MarketActive/Activity/MarketList'));
    }
    
    // 状态修改
    public function editStatus() {
        $data = I('post.');
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($result) {
            node_log('投票活动状态更改|活动ID：' . $data['batch_id']);
            $this->success('更新成功', 
                array(
                    '返回' => U('Vote/index')));
        } else {
            $this->error('更新失败');
        }
    }
    
    // 数据导出
    public function export() {
        // 查询条件组合
        $where = "WHERE batch_type='" . $this->BATCH_TYPE . "'";
        if (! empty($_POST)) {
            $filter = array();
            $condition = array_map('trim', $_POST);
            if (isset($condition['key']) && $condition['key'] != '') {
                $filter[] = "name LIKE '%{$condition['key']}%'";
            }
            if (isset($condition['status']) && $condition['status'] != '') {
                $filter[] = "status = '{$condition['status']}'";
            }
            if (isset($condition['start_time']) && $condition['start_time'] != '') {
                $condition['start_time'] = $condition['start_time'] . ' 000000';
                $filter[] = "add_time >= '{$condition['start_time']}'";
            }
            if (isset($condition['end_time']) && $condition['end_time'] != '') {
                $condition['end_time'] = $condition['end_time'] . ' 235959';
                $filter[] = "add_time <= '{$condition['end_time']}'";
            }
        }
        if (! empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }
        $sql = "SELECT name,add_time,start_time,end_time,
    	CASE status WHEN '1' THEN '正常' ELSE '停用' END status,
    	click_count,send_count
    	FROM
    	 tmarketing_info {$where} AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
            'name' => '活动名称', 
            'add_time' => '添加时间', 
            'start_time' => '活动开始时间', 
            'end_time' => '活动结束时间', 
            'status' => '状态', 
            'click_count' => '访问量', 
            'send_count' => '发码量');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }

    public function VoteCountExport() {
        set_time_limit(0);
        $batchId = I('get.batch_id', null);
        if (is_null($batchId))
            $this->error('缺少参数');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $d_query_arr = $baseActivityModel->checkactivityexist($batchId, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        $type_arr = array(
            '1' => '单选', 
            '2' => '多选', 
            '3' => '问答');
        
        $column_arr = array();
        
        $search_str = 'id,add_time,label_id,node_id,channel_id,channel_name';
        
        $fileName = $d_query_arr['name'] . '-' . date('Y-m-d') . '-投票结果.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        $start_num = 0;
        $page_count = 5000;
        for ($j = 1; $j < 100; $j ++) {
            // 查询调查问卷流水数据
            $query = M('tvote_trace')->field("{$search_str}")
                ->where(
                array(
                    "label_id" => $batchId, 
                    "node_id" => $this->node_id))
                ->order('id')
                ->limit(($j - 1) * $page_count, 5000)
                ->select();
            
            if (! $query)
                exit();
            
            $i = 0;
            if ($j == 1) {
                // 标题
                foreach ($column_arr as $k => $v) {
                    $excel_title .= $v . ",";
                    $i ++;
                }
                $excel_title .= '日期' . "," . '渠道id' . "," . '来源渠道' . ",";
                // 是否有问卷
                $row = M('tvote_question')->where("label_id='{$batchId}'")->select();
                if ($row) {
                    foreach ($row as $q_v) {
                        $ced = M('tvote_question_info')->where(
                            "question_id={$q_v['id']}")->select();
                        // dump($row);
                        if ($ced) {
                            foreach ($ced as $vv) {
                                
                                $ques = $vv['answers'];
                                $excel_title .= $q_v['questions'] . 'Q_' . $ques .
                                     ",";
                            }
                        }
                    }
                }
                $excel_title = substr($excel_title, 0, - 1);
                $excel_title = iconv('utf-8', 'gbk', $excel_title);
                echo $excel_title . "\r\n";
            }
            // 内容
            
            foreach ($query as $vv) {
                $arr_stu = array(
                    'A' => '1', 
                    'B' => '2', 
                    'C' => '3', 
                    'D' => '4', 
                    'E' => '5', 
                    'F' => '6', 
                    'G' => '7', 
                    'H' => '8', 
                    'I' => '9', 
                    'J' => '10', 
                    'K' => '11', 
                    'L' => '12', 
                    'M' => '13', 
                    'N' => '14', 
                    'O' => '15', 
                    'P' => '16', 
                    'Q' => '17', 
                    'R' => '18', 
                    'S' => '19', 
                    'T' => '20', 
                    'U' => '21', 
                    'V' => '22', 
                    'W' => '23', 
                    'X' => '24', 
                    'Y' => '25', 
                    'Z' => '26');
                
                $arr_stc = array(
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
                    '20' => 'T', 
                    '21' => 'U', 
                    '22' => 'V', 
                    '23' => 'W', 
                    '24' => 'X', 
                    '25' => 'Y', 
                    '26' => 'Z');
                
                $excel_note = '';
                foreach ($column_arr as $k => $v) {
                    $excel_note .= $vv[$k] . ",";
                }
                $excel_note .= dateformat($vv['add_time'], 'Y-m-d H:i:s') . ",";
                $excel_note .= $vv['channel_id'] . ",";
                $excel_note .= $vv['channel_name'] . ",";
                foreach ($row as $q_v) {
                    
                    if (empty($row))
                        break;
                    $res = M('tvote_question_stat')->where(
                        "bm_seq_id={$vv['id']} AND question_id={$q_v['id']}")->find();
                    $daan = M('tvote_question_info')->where(
                        "question_id={$q_v['id']}")->select();
                    $cou = count($daan);
                    $guod = array(
                        'A' => $daan['0']['answers'], 
                        'B' => $daan['1']['answers'], 
                        'C' => $daan['2']['answers'], 
                        'D' => $daan['3']['answers'], 
                        'E' => $daan['4']['answers'], 
                        'F' => $daan['5']['answers'], 
                        'G' => $daan['6']['answers'], 
                        'H' => $daan['7']['answers'], 
                        'I' => $daan['8']['answers'], 
                        'J' => $daan['9']['answers'], 
                        'K' => $daan['10']['answers'], 
                        'L' => $daan['11']['answers'], 
                        'M' => $daan['12']['answers'], 
                        'N' => $daan['13']['answers'], 
                        'O' => $daan['14']['answers'], 
                        'P' => $daan['15']['answers'], 
                        'Q' => $daan['16']['answers'], 
                        'R' => $daan['17']['answers'], 
                        'S' => $daan['18']['answers'], 
                        'T' => $daan['19']['answers'], 
                        'U' => $daan['20']['answers'], 
                        'V' => $daan['21']['answers'], 
                        'W' => $daan['22']['answers'], 
                        'X' => $daan['23']['answers'], 
                        'Y' => $daan['24']['answers'], 
                        'Z' => $daan['25']['answers']);
                    if ($res) {
                        $excel_note11 = $res['answer_list'] . ",";
                        $acc = explode("-", $res['answer_list']);
                        $max = $cou;
                        foreach ($arr_stc as $k => $v_d) {
                            if ($k > $max) {
                                break;
                            }
                            if (in_array($v_d, $acc)) {
                                $excel_note .= $guod[$arr_stc[$k]] . ",";
                            } else {
                                $excel_note .= ",";
                                // echo ' ';
                            }
                        }
                    } else {
                        $excel_note .= "\t";
                    }
                }
                $excel_note = iconv('utf-8', 'gbk', $excel_note);
                echo $excel_note . "\r\n";
            }
        }
        exit();
    }
    
    // 投票选项标签转化成投票选项内容 A => 答案1
    private function getAnswerText($answer_list, $question_id) {
        $question_answers = M('tvote_question_info')->where(
            "question_id='{$question_id}'")->getField('value,answers');
        $search_arr = array(
            'A', 
            'B', 
            'C', 
            'D', 
            'E', 
            'F', 
            'G', 
            'H', 
            'I', 
            'J', 
            'K', 
            'L', 
            'M', 
            'N', 
            'O', 
            'P', 
            'Q', 
            'R', 
            'S', 
            'T', 
            'U', 
            'V', 
            'W', 
            'X', 
            'Y', 
            'Z');
        $replace_arr = array(
            $question_answers['1'], 
            $question_answers['2'], 
            $question_answers['3'], 
            $question_answers['4'], 
            $question_answers['5'], 
            $question_answers['6'], 
            $question_answers['7'], 
            $question_answers['8'], 
            $question_answers['9'], 
            $question_answers['10'], 
            $question_answers['11'], 
            $question_answers['12'], 
            $question_answers['13'], 
            $question_answers['14'], 
            $question_answers['15'], 
            $question_answers['16'], 
            $question_answers['17'], 
            $question_answers['18'], 
            $question_answers['19'], 
            $question_answers['20'], 
            $question_answers['21'], 
            $question_answers['22'], 
            $question_answers['23'], 
            $question_answers['24'], 
            $question_answers['25'], 
            $question_answers['26']);
        $replace_str = str_replace($search_arr, $replace_arr, $answer_list);
        
        return str_replace('-', '、', $replace_str);
    }
    
    // 中奖名单下载
    public function winningExport() {
        $batchId = I('get.batch_id', null);
        if (is_null($batchId))
            $this->error('缺少参数');
        
        $edit_wh = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $batchId);
        $nodeInfo = M('tmarketing_info')->where($edit_wh)->find();
        if (! $nodeInfo)
            $this->error('未查询到记录！');
        
        $fileName = $nodeInfo['name'] . '-' . date('Y-m-d') . '-中奖名单.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "手机号,中奖时间,中奖状态,奖品等级\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $query = M()->query(
                "SELECT mobile,add_time,
							    	CASE status WHEN '1' THEN '未中奖' ELSE '中奖' END status,prize_level
							    	FROM
							    	tcj_trace WHERE batch_id='{$batchId}' AND batch_type={$this->BATCH_TYPE} AND node_id ={$nodeInfo['node_id']}
							    	ORDER by status DESC LIMIT {$page},{$page_count}");
            if (! $query)
                exit();
            foreach ($query as $v) {
                $cj_status = iconv('utf-8', 'gbk', $v['status']);
                echo "{$v['mobile']}," .
                     date('Y-m-d H:i:s', strtotime($v['add_time'])) .
                     ",{$cj_status},{$v['prize_level']}\r\n";
            }
        }
    }

    /**
     * 投票统计展示
     */
    public function info() {
        $batchId = I('batch_id', 0);
        $model = M('tmarketing_info');
        $map = array(
            'batch_type' => $this->BATCH_TYPE, 
            'id' => $batchId, 
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        $info = $model->where($map)->find();
        
        $result = M('tvote_question')->where(
            array(
                'label_id' => $batchId, 
                'type' => array(
                    'in', 
                    '1,2')))
            ->order("sort")
            ->select();
        
        $varItemList = array(
            'A' => 0, 
            'B' => 1, 
            'C' => 2, 
            'D' => 3, 
            'E' => 4, 
            'F' => 5, 
            'G' => 6, 
            'H' => 7, 
            'I' => 8, 
            'J' => 9, 
            'K' => 10, 
            'L' => 11, 
            'M' => 12, 
            'N' => 13, 
            'O' => 14, 
            'P' => 15, 
            'Q' => 16, 
            'R' => 17, 
            'S' => 18, 
            'T' => 19, 
            'U' => 20, 
            'V' => 21, 
            'W' => 22, 
            'X' => 23, 
            'Y' => 24, 
            'Z' => 25);
        $result = $this->formatResult($result, $varItemList, $batchId);
        
        $this->assign('list', $result);
        $this->assign('info', $info);
        $this->display();
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $result
     * @param $varItemList
     * @param $batchId
     * @return mixed
     */
    private function formatResult($result, $varItemList, $batchId) {
        foreach ($result as $k => $v) {
            $answersInfoList = $this->VoteQuestionModel->getQuestionList(
                array(
                    'question_id' => $v['id']));
            
            if ($answersInfoList !== false) {
                $result[$k]['answers_list'] = $answersInfoList;
            }
            
            $ask_info = $this->VoteQuestionModel->getAskList(
                array(
                    'label_id' => $batchId, 
                    'question_id' => $v['id']), 'answer_list');
            $this->formatAskInfoList($result, $k, $ask_info);
        }
        $this->calcResultCount($result, $varItemList);
        
        return $result;
    }

    private function formatAskInfoList(&$result, $k, $ask_info) {
        foreach ($ask_info as $kk => $vv) {
            $result[$k]['ask_list'][] = $ask_info[$kk]['answer_list']; // 选项内容（单选选项）
            $result[$k]['ask_lists'][] = $ask_info[$kk]['answer_list']; // 选项内容（多选选项）
            $result[$k]['count'] = 0; // count($result[$k]['ask_lists']);
                                      // //选项总数统计
                                      // 多选处理
            if (2 == $result[$k]['type']) {
                foreach ($result[$k]['ask_list'] as $kkk => $vvv) {
                    if (1 < strlen($vvv)) {
                        $arr = explode('-', $result[$k]['ask_list'][$kkk]);
                        unset($result[$k]['ask_list'][$kkk]);
                        $result[$k]['ask_list'] = array_merge(
                            $result[$k]['ask_list'], $arr);
                    }
                }
            }
            // $result[$k]['ask_list_count'] =
            // 0;//array_count_values($result[$k]['ask_list']); //选项内容分类个数统计
            $result[$k]['ask_list_count'] = 0; // 选项内容分类个数统计
        }
    }

    private function calcResultCount(&$result, $varItemList) {
        foreach ($result as $index => $item) {
            $count = count($item['ask_lists']);
            $result[$index]['count'] = $count;
            $result[$index]['ask_list_count'] = array_count_values(
                $item['ask_list']);
            
            // 答案以及百分比
            foreach ($result[$index]['ask_list_count'] as $askInfo => $askCount) {
                $result[$index]['ask_list_counts'][$varItemList[$askInfo]] = $askCount;
                $result[$index]['percent'][$varItemList[$askInfo]] = number_format(
                    $askCount / $count, 4);
            }
        }
    }
}
