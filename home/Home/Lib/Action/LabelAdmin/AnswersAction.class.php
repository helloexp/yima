<?php

/**
 * @功能：有奖答题 @更新时间：2015/02/05 09:52
 */
class AnswersAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE = '10';
    // 图片路径
    public $img_path;

    /**
     * @var AnswersService
     */
    protected $AnswersService;

    // 初始化
    public function _initialize() {
        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        $this->AnswersService = D('Answers', 'Service');

        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign("node_id", $this->node_id);
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
    }

    // 首页
    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => array(
                'exp',
                "in (" . $this->nodeIn() . ")"),
            'batch_type' => $this->BATCH_TYPE);

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

        $arr_ = C('CHANNEL_TYPE');

        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }

    // 添加页
    public function add() {
        $bm_type_arr = C('BM_TYPE_ARR');
        // 删除信息采集字段中的图片上传
        unset($bm_type_arr['13']);
        unset($bm_type_arr['14'], $bm_type_arr['15'], $bm_type_arr['16'],
            $bm_type_arr['17'], $bm_type_arr['18'], $bm_type_arr['19'],
            $bm_type_arr['20']);

        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);

        $this->assign('bm_type_arr', $bm_type_arr);

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

        if (! empty($data['select_type'])) {
            $select_type = $baseActivityModel->implodearray(
                $data['select_type']);
        } else {
            $select_type = '';
        }

        $data_arr = array(
            'name' => $data['name'],
            'color' => $data['color'],
            'wap_title' => $data['wap_title'],
            'wap_info' => $data['wap_info'],
            'log_img' => $result['log_img'],
            'start_time' => substr($data['start_time'], 0, 8) . '000000',
            'end_time' => substr($data['end_time'], 0, 8) . '235959',
            // 'memo'=>$data['memo'],
            'select_type' => $select_type,
            'node_id' => $this->node_id,
            'add_time' => date('YmdHis'),
            'status' => '1',
            // 'size'=>'2',
            // 'code_img'=>$data['resp_code_img'],
            'sns_type' => $result['sns_type'],
            'chance' => $data['chance'],
            'goods_count' => $data['goods_count'],
            'day_goods_count' => $data['day_goods_count'],
            'is_cj' => '0',
            'jp_type' => $data['jp_type'],
            'zc_batch_no' => $data['zc_batch_no'],
            'wc_batch_no' => $data['wc_batch_no'],
            'defined_one_name' => $data['defined10'],
            'defined_two_name' => $data['defined11'],
            'defined_three_name' => $data['defined12'],
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
            $this->error('活动创建失败！',
                array(
                    '返回有奖答题' => U('index')));
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
                        '返回有奖答题' => U('index')));
            }
        }
        // 处理问卷
        for ($i = 1; $i <= 30; $i ++) {
            $qname = 'q_' . $i;
            $aname = 'a_' . $i;
            $tname = 't_' . $i;
            $sort = 'sort_' . $i;
            $cname = 'c_' . $i;

            if ($data[$qname] == '')
                continue;
                // 正确答案
            if (! empty($data[$cname])) {
                if (is_array($data[$cname])) {
                    $answers = implode('-', $data[$cname]);
                } else {
                    $answers = $data[$cname];
                }
            }
            $in_arr = array(
                'label_id' => $resp_id,
                'questions' => $data[$qname],
                'type' => $data[$tname],
                'sort' => $data[$sort],
                'correct_answer' => $answers);

            // 问题表
            $qmodel = M('tanswers_question');
            $question_id = $qmodel->add($in_arr);
            if (! $question_id) {
                $tranDb->rollback();
                $this->error('系统错误！');
            }

            // 答案表
            $amodel = M('tanswers_question_info');
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
        node_log('有奖答题活动添加|活动名:' . $data['name']);
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
    }

    // 编辑页
    public function edit() {
        $id = $this->_param('id');
        if (empty($id)) {
            $id = I('get.id');
        }

        if (empty($id))
            $this->error('错误参错');

        $baseActivityModel = D('BaseActivity', 'Service');
        $query_arr = $baseActivityModel->checkactivityexist($id,
            $this->BATCH_TYPE, $this->nodeIn());

        if ($query_arr['log_img'] == '') {
            $nodeInfo = get_node_info($this->node_id);
            $this->assign('head_photo', get_upload_url($nodeInfo['head_photo']));
            $this->assign('node_logo', $nodeInfo['head_photo']);
        }

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
        $bm_type_arr = C('BM_TYPE_ARR');
        $this->assign('bm_type_arr', $bm_type_arr);

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

        // 是否有人参与此次答题
        $judge = M('tanswers_question_stat')->where(
            array(
                'label_id' => $data['id']))->find();
        if (! $judge) {
            if (empty($data['select_type'])) {
                $this->error('请选择wap页面显示字段！');
            }
        }

        if (! empty($data['select_type'])) {
            $select_type = $baseActivityModel->implodearray(
                $data['select_type']);
        } else {
            $select_type = '';
        }

        if (! empty($data['sns_type'])) {
            $sns_type = implode('-', $data['sns_type']);
        } else {
            $sns_type = '';
        }

        $query_arr = $baseActivityModel->checkactivityexist($data['id'],
            $this->BATCH_TYPE, $this->nodeIn());
        $node_id = $query_arr['node_id'];

        $bm_id = $data['id'];
        $map = array(
            'node_id' => $node_id,
            'id' => $data['id']);

        // 背景图
        if ($data['resp_bg_img'] != '' && $data['reset_bg'] == '1') {
            // $bg_img =
            // $baseActivityModel->activitybackgroundpicupload($resp_bg_img,$this->BATCH_TYPE,
            // $this->img_path );
            // }else {
            $bg_img = $data['resp_bg_img'];
        }
        $data_arr = array(
            'name' => $data['name'],
            'color' => $data['color'],
            'wap_title' => $data['wap_title'],
            'wap_info' => $data['wap_info'],
            'start_time' => substr($data['start_time'], 0, 8) . '000000',
            'end_time' => substr($data['end_time'], 0, 8) . '235959',
            // 'memo'=>$data['memo'],
            // 'select_type'=>$select_type,
            // 'size'=>$data['size'],
            // 'code_img'=>$data['resp_code_img'],
            'sns_type' => $sns_type,
            'defined_one_name' => $data['defined10'],
            'defined_two_name' => $data['defined11'],
            'defined_three_name' => $data['defined12'],
            'node_name' => $data['node_name'],
            'page_style' => $data['page_style'],
            'bg_style' => $data['bg_style'],
            'bg_pic' => $bg_img,
            'is_show' => '1');

        if (! $judge) {
            $data_arr['select_type'] = $select_type;
            $data_arr['memo'] = $data['memo'];
        }

        $log_img = $data['resp_log_img'];
        $music = $data['resp_music'];
        if ($data['is_log_img'] == '1' && ! empty($log_img)) {
            $data_arr['log_img'] = $log_img;
        } else {
            if ($data['is_log_img'] == '0') {
                $data_arr['log_img'] = '';
            }
        }
        if ($data['is_music'] == '1' && ! empty($music)) {
            $data_arr['music'] = $music;
        } else {
            if ($data['is_music'] == '0') {
                $data_arr['music'] = '';
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
            $this->error('活动更新失败！',
                array(
                    '返回有奖答题' => U('index')));
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
                            '返回有奖答题' => U('index')));
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
                                '返回有奖答题' => U('index')));
                    }
                }
            }
        }
        if (! $judge) {
            // 调研
            $q_stat = M('tanswers_question_stat');
            $q_qeury = $q_stat->where(
                array(
                    'label_id' => $bm_id))->find();
            if (! $q_qeury) {
                $tquestion = M("tanswers_question");
                $tquestion_info = M("tanswers_question_info");

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
                    $cname = 'c_' . $i;

                    if ($data[$qname] == '')
                        continue;
                        // 正确答案
                    if (! empty($data[$cname])) {
                        if (is_array($data[$cname])) {
                            $answers = implode('-', $data[$cname]);
                        } else {
                            $answers = $data[$cname];
                        }
                    }
                    $in_arr = array(
                        'label_id' => $bm_id,
                        'questions' => $data[$qname],
                        'type' => $data[$tname],
                        'sort' => $data[$sort],
                        'correct_answer' => $answers);
                    // 问题表
                    $qmodel = M('tanswers_question');
                    $question_id = $qmodel->add($in_arr);
                    if (! $question_id) {
                        $tranDb->rollback();
                        $this->error('系统错误！');
                    }

                    // 答案表
                    $amodel = M('tanswers_question_info');
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
        node_log('有奖答题活动编辑|活动名:' . $data['name'] . '|活动ID：' . $data['id']);
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
            node_log('有奖答题活动状态更改|活动ID：' . $data['batch_id']);
            $this->success('更新成功',
                array(
                    '返回' => U('Answers/index')));
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

    public function BmCountExport() {
        set_time_limit(0);
        $batchId = I('get.batch_id', null);
        if (is_null($batchId))
            $this->error('缺少参数');

        $edit_wh = array(
            'node_id' => array(
                'exp',
                "in (" . $this->nodeIn() . ")"),
            'id' => $batchId);
        $node_id = M('tmarketing_info')->where($edit_wh)->getField('node_id');
        if (! $node_id)
            $this->error('未查询到记录！');

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
        $type_arr = array(
            '1' => '单选',
            '2' => '多选',
            '3' => '问答');
        $d_query_arr = M('tmarketing_info')->field(
            'name,select_type,defined_one_name,defined_two_name,defined_three_name')
            ->where("id='{$batchId}' AND node_id={$node_id}")
            ->find();
        if (! $d_query_arr)
            $this->error('未找到该活动');
        $arr = explode('-', $d_query_arr['select_type']);
        $column_arr = array();
        foreach ($arr as $v) {
            if ($v == '10') {
                $column_arr[($get_bm_arr[$v])] = $d_query_arr['defined_one_name'];
            } elseif ($v == '11') {
                $column_arr[($get_bm_arr[$v])] = $d_query_arr['defined_two_name'];
            } elseif ($v == '12') {
                $column_arr[($get_bm_arr[$v])] = $d_query_arr['defined_three_name'];
            } else {
                $column_arr[($get_bm_arr[$v])] = $bm_arr[$v];
            }
        }

        $search_str = 'id,add_time,label_id,node_id,' . implode(',',
            $get_bm_arr);

        $fileName = $d_query_arr['name'] . '-' . date('Y-m-d') . '-答题内容.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);

        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");

        $start_num = 0;
        $page_count = 5000;
        $excel_title = '';
        for ($j = 1; $j < 100; $j ++) {
            // 查询调查问卷流水数据
            $query = M('tanswers_trace')->field("{$search_str}")
                ->where("label_id='{$batchId}' AND node_id='{$node_id}'")
                ->order('id')
                ->limit(($j - 1) * $page_count, 5000)
                ->select();

            if (! $query)
                exit();

            $i = 0;
            // 标题
            foreach ($column_arr as $k => $v) {
                $excel_title .= $v . ",";
                $i ++;
            }
            $excel_title .= '日期' . ",";
            // 是否有问卷
            $row = M('tanswers_question')->where("label_id='{$batchId}'")->select();
            if ($row) {
                foreach ($row as $q_v) {

                    $ced = M('tanswers_question_info')->where(
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
                foreach ($row as $q_v) {

                    if (empty($row))
                        break;

                    $res = M('tanswers_question_stat')->where(
                        "bm_seq_id={$vv['id']} AND question_id={$q_v['id']}")->find();
                    // dump($res);//找出当前问题的信息
                    $daan = M('tanswers_question_info')->where(
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
     * 获得最终的数据(数据异步处理的-crontab处理)
     *
     * @param bool   $isAjax
     * @param string $originKey
     * @param string $batchId
     *
     * @return array
     */
    public function getInfoResult($isAjax = true, $originKey = '', $batchId = '')
    {
        $originKey = I('get.key', $originKey);
        $batchId   = I('get.batch_id', $batchId);

        if ($batchId) {
            $map    = [
                    'batch_type' => $this->BATCH_TYPE,
                    'id'         => $batchId,
                    'node_id'    => ['exp', 'in (' . $this->nodeIn() . ')'],
            ];
            $result = $this->AnswersService->getInfoResult($originKey, $batchId, $map);
            if ($result['retCode'] == '0000') {
                $refreshTime = get_val($result, 'refreshTime');
                $this->assign('list', $result['content']);
                $this->assign('batch_id', $batchId);
                $this->assign('refreshTime', $refreshTime);
                $content     = $this->fetch('ajaxInfoResult');
                $data        = ['retCode' => '0000',
                                'data'    => [
                                        'content'     => $content,
                                        'refreshTime' => $refreshTime,
                                        'retTxt'      => '已经计算完成'
                                ]
                ];
                if ($isAjax) {
                    $this->ajaxReturn($data);
                } else {
                    return $data;
                }

            } else {
                if ($isAjax) {
                    $this->ajaxReturn($result);
                }
                return $result;
            }
        }
        $data = ['retCode' => '1002', 'data' => [], 'retTxt' => '请求参数错误'];
        if ($isAjax) {
            $this->ajaxReturn($data);
        }
        return $data;
    }

    /**
     * 强制刷新数据
     */
    public function refreshDataForce()
    {
        file_debug($_REQUEST,1,'cc.log');
        $batchId = I('get.batch_id');
        if ($batchId) {
            $newKey = '';
            $originKey = $this->AnswersService->generateQueueKey($batchId, '', $newKey);
            $result = $this->AnswersService->getInfoQueue($originKey, $batchId);
            file_debug($result,2,'cc.log');
            if (empty($result)) {
                $map = [
                        'batch_type' => $this->BATCH_TYPE,
                        'id'         => $batchId,
                        'node_id'    => ['exp', 'in (' . $this->nodeIn() . ')'],
                ];
                $result = $this->AnswersService->addInfoQueue($originKey, $batchId, $map, $newKey);
                file_debug(3,1,'cc.log');
            }
            if ($result != 1001) { //还没有处理 调用 异步请求进行处理
                $this->AnswersService->processQueueData($newKey, $batchId);
                $this->ajaxReturn(['retCode' => '1001', 'key' => $newKey]);
            }
            $this->ajaxReturn(['retCode' => '0000', 'key' => $newKey]);
        }
    }


    public function calcInfoData()
    {
        $this->AnswersService->calcInfoData();
    }

    /**
     * 有奖答题统计展示
     */
    public function info() {
        $batchId = I('batch_id', 0);
        $model = M('tmarketing_info');
        $map = [
                'batch_type' => $this->BATCH_TYPE,
                'id'         => $batchId,
                'node_id'    => ['exp', 'in (' . $this->nodeIn() . ')']
        ];
        $key = $this->AnswersService->genreateCommonQueueKey();
        $list = $this->getInfoResult(false, $key);
        if ($list['retCode'] === '0000') {
            $this->assign('content', $list['data']['content']);
            $this->assign('getResult', 0);
            $refreshTime = $this->AnswersService->getInfoRefreshTime($batchId);
            $this->assign('refreshTime', $refreshTime);
        } else {
            $content =<<<CONTENT
            <h6>数据上次刷新时间:<span id="refreshTime" style="color:#fde19a;font-weight:bold;">-</span><span id="waitingTime"></span></h6>
CONTENT;
            $this->assign('content', $content);
            $this->assign('getResult', 1);
        }

        $info = $model->where($map)->find();
        $this->assign('batch_id', $batchId);
        $this->assign('key1', $key);
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 有奖答题统计展示
     */
    public function infoOld() {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        $batch_id = I('batch_id', 0);
        $model = M('tmarketing_info');
        $map = array(
                'batch_type' => $this->BATCH_TYPE,
                'id' => $batch_id,
                'node_id' => array(
                        'exp',
                        "in (" . $this->nodeIn() . ")"));
        $info = $model->where($map)->find();

        $result = M('tanswers_question')->where(
                array(
                        'label_id' => $batch_id,
                        'type' => array(
                                'in',
                                '1,2')))
                ->order("sort")
                ->select();

        foreach ($result as $k => $v) {
            $answers_info = M('tanswers_question_info')->where(
                    array(
                            "question_id" => $v['id']))->select();
            if ($answers_info !== false) {
                $result[$k]['answers_list'] = $answers_info;
            }
            $ask_info = M('tanswers_question_stat')->where(
                    array(
                            'label_id' => $batch_id,
                            'question_id' => $v['id']))
                    ->field('answer_list')
                    ->select();

            foreach ($ask_info as $kk => $vv) {
                $result[$k]['ask_list'][] = $ask_info[$kk]['answer_list']; // 选项内容（单选选项）
                $result[$k]['ask_lists'][] = $ask_info[$kk]['answer_list']; // 选项内容（多选选项）
                $result[$k]['count'] = count($result[$k]['ask_lists']); // 选项总数统计
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
                $result[$k]['ask_list_count'] = array_count_values(
                        $result[$k]['ask_list']); // 选项内容分类个数统计
            }
            // 答案以及百分比
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
                    '20' => 'T',
                    '21' => 'U',
                    '22' => 'V',
                    '23' => 'W',
                    '24' => 'X',
                    '25' => 'Y',
                    '26' => 'Z');
            $v_arr = array_flip($v_arr);
            ksort($result[$k]['ask_list_count']);

            foreach ($result[$k]['ask_list_count'] as $kkk => $vvvv) {
                $result[$k]['ask_list_counts'][$v_arr[$kkk] - 1] = $vvvv;
                $result[$k]['percent'][$v_arr[$kkk] - 1] = number_format(
                        $vvvv / $result[$k]['count'], 4);
                // rsort($result[$k]['percent']);
                // rsort($result[$k]['ask_list_counts']);
            }
            // ksort($result[$k]['ask_list_counts']);
        }

        $this->assign('list', $result);
        $this->assign('info', $info);
        $this->display('info');
    }
}
