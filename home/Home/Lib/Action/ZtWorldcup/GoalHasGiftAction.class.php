<?php
// 情人节活动
class GoalHasGiftAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE = '24';
    // 图片路径
    public $img_path;
    // 是否允许等级多奖品
    public $mg_flag;
    
    // 初始化
    public function _initialize() {
        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
        
        // 定制化，等级多奖品
        $this->mg_flag = in_array($this->node_id, C('LEVEL_MORE_PRIZE_NODE'), 
            true) ? 1 : 0;
        $this->assign('mg_flag', $this->mg_flag);
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
        import('ORG.Util.Page');
        
        // 导入分页类
        $mapcount = $model->where($map)->count();
        
        // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10);
        
        // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show();
        
        // 分页显示输出
        
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
        node_log("首页+进球有礼");
        
        $arr_ = C('CHANNEL_TYPE');
        $this->assign('is_cj_button', '1');
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show);
        $this->assign('nodeList', $this->getNodeTree());
        $this->display();
    }
    
    // 添加页
    public function add() {
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        
        $nodeName = $nodeInfo['node_name'];
        // 获取商户会员卡信息
        $mem_batch = M('tmember_batch')->where(
            array(
                'node_id' => $this->node_id, 
                'status' => '1'))
            ->order('member_level asc')
            ->select();
        
        node_log('进球有礼点击添加|商户名:' . $nodeName);
        $this->assign('is_cj_button', '1');
        $this->assign('mem_batch', $mem_batch);
        $this->display();
    }
    
    // 添加提交页
    public function addSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        
        if (empty($data['name']))
            $this->error('请填写活动名称！');
        if (empty($data['wap_title']))
            $this->error('请填写wap页面标题！');
        if (empty($data['wap_info']))
            $this->error('活动页面内容不能为空');
        
        if (! empty($data['sns_type'])) {
            $sns_type = implode('-', $data['sns_type']);
        } else {
            $sns_type = '';
        }
        
        // 校验赛事
        $events_info = M('tworld_cup_events')->find($data['events_id']);
        if (! $events_info)
            $this->error('赛事选择错误！');
        if ($events_info['begin_time'] < date('YmdHis'))
            $this->error('赛事已经开始！');
        $cnt = $model->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => $this->BATCH_TYPE, 
                'defined_one_name' => $data['events_id'], 
                'status' => '1'))->count();
        
        if ($cnt > 0)
            $this->error('该场次已经有一个进球有礼活动正在进行中，如果需要创建新的活动替换现有活动，请先停用现有活动！');
        
        $data['start_time'] = date('YmdHis');
        $data['end_time'] = date('Ymd', strtotime($events_info['begin_time']));
        
        $one_map = array(
            'name' => $data['name'], 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("活动名称重复");
        }
        
        // logo
        if ($data['resp_log_img'] != '') {
            $log_img = $data['resp_log_img'];
        }
        
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'log_img' => $log_img, 
            // 'music' => $data['resp_music'],
            // 'video_url' => $data['video_url'],
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'memo' => $data['memo'], 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            'size' => $data['size'], 
            'code_img' => $data['resp_code_img'], 
            'sns_type' => $sns_type, 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_cj' => $data['is_cj'], 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $data['resp_bg_img'], 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1', 
            'defined_one_name' => $data['events_id']);
        $batchdata = json_decode($data['batchjson'], true);
        
        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        
        $resp_id = $model->add($data_arr);
        if (! $resp_id) {
            $tranDb->rollback();
            $this->error('系统错误！', 
                array(
                    '返回进球有礼' => U('index')));
        }
        
        // 更新奖品规则
        if ($data['is_cj'] == '1') {
            
            $cjrulem = M('tcj_rule');
            $cdata = array(
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $resp_id, 
                'jp_set_type' => $data['jp_set_type'], 
                'day_count' => '1', 
                'total_chance' => $data['jp_set_type'] == '2' ? $data['chance'] : '', 
                'node_id' => $this->node_id, 
                'cj_button_text' => $data['cj_button_text'], 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                'phone_total_count' => 1, 
                'phone_day_count' => 1, 
                'phone_total_part' => 0, 
                'phone_day_part' => 0);
            $rulequery = $cjrulem->add($cdata);
            if (! $rulequery) {
                $tranDb->rollback();
                $this->error('系统错误！', 
                    array(
                        '返回进球有礼' => U('index')));
            }
            
            $cjbatchm = M('tcj_batch');
            if ($data['jp_set_type'] == '1') {
                $vv = $batchdata['list'][0];
                $bdata = array(
                    'batch_id' => $resp_id, 
                    'node_id' => $this->node_id, 
                    'activity_no' => $vv['batch_no'], 
                    'award_origin' => 2, 
                    'award_level' => '1', 
                    'award_rate' => $data['chance'], 
                    'total_count' => $vv['total_count'], 
                    'day_count' => $vv['total_count'], 
                    'batch_type' => $this->BATCH_TYPE, 
                    'cj_rule_id' => $rulequery);
                
                if (in_array('', $bdata)) {
                    $tranDb->rollback();
                    $this->error('奖品规则设置错误1！');
                }
                
                $bquery = $cjbatchm->add($bdata);
                
                if (! $bquery) {
                    $tranDb->rollback();
                    $this->error('奖品设置错误！');
                }
            } elseif ($data['jp_set_type'] == '2') {
                foreach ($batchdata['list'] as $vv) {
                    $bdata = array(
                        'batch_id' => $resp_id, 
                        'node_id' => $this->node_id, 
                        'activity_no' => $vv['batch_no'], 
                        'award_origin' => 2, 
                        'award_level' => $vv['level'], 
                        'award_rate' => $vv['total_count'], 
                        'total_count' => $vv['total_count'], 
                        'day_count' => $vv['total_count'], 
                        'batch_type' => $this->BATCH_TYPE, 
                        'cj_rule_id' => $rulequery);
                    
                    if (in_array('', $bdata)) {
                        $tranDb->rollback();
                        $this->error('奖品规则设置错误2！');
                    }
                    
                    $bquery = $cjbatchm->add($bdata);
                    if (! $bquery) {
                        $tranDb->rollback();
                        $this->error('奖品规则设置错误3！');
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
        node_log('进球有礼添加|活动名:' . $data['name']);
        $this->success('添加成功！', 
            array(
                '返回进球有礼' => U('index'), 
                '发布渠道' => U('LabelAdmin/BindChannel/index', 
                    array(
                        'batch_type' => $this->BATCH_TYPE, 
                        'batch_id' => $resp_id))));
    }
    
    // 编辑页
    public function edit() {
        $id = I('get.id');
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
        if ($query_arr['is_cj'] == '1') {
            $cjrule = M('tcj_rule');
            $map1 = array(
                'node_id' => $query_arr['node_id'], 
                'batch_id' => $query_arr['id'], 
                'batch_type' => $this->BATCH_TYPE, 
                'status' => '1');
            
            $batch_model = M('tbatch_info');
            $cjarr = $cjrule->where($map1)->find();
            $map1 = array(
                'node_id' => $query_arr['node_id'], 
                'batch_id' => $query_arr['id'], 
                'batch_type' => $this->BATCH_TYPE, 
                'cj_rule_id' => $cjarr['id']);
            $cjbatch = M('tcj_batch');
            $cjbatch_arr = $cjbatch->where($map1)
                ->order('award_level asc')
                ->select();
            
            $batch_name_arr = array();
            if ($cjbatch_arr) {
                $batch_map = array(
                    'node_id' => $query_arr['node_id']);
                foreach ($cjbatch_arr as $v) {
                    $batch_map['batch_no'] = $v['activity_no'];
                    $batch_name_arr[$v['activity_no']] = $batch_model->where(
                        $batch_map)->getField('batch_short_name');
                }
            }
        }
        
        // 获取商户会员卡信息
        $mem_batch = M('tmember_batch')->where(
            array(
                'node_id' => $query_arr['node_id'], 
                'status' => '1'))
            ->order('member_level asc')
            ->select();
        
        // 获取竞猜场次信息
        $team_arr = M('tworld_cup_team_info')->getField('team_id, team_name');
        $events_info = M('tworld_cup_events')->find(
            $query_arr['defined_one_name']);
        
        $this->assign('events_name', 
            $team_arr[$events_info['team1_id']] . 'VS' .
                 $team_arr[$events_info['team2_id']]);
        $this->assign('mem_batch', $mem_batch);
        $this->assign('is_cj_button', '1');
        $this->assign('cjarr', $cjarr);
        $this->assign('cjbatch_arr', $cjbatch_arr);
        $this->assign('batch_name_arr', $batch_name_arr);
        $this->assign('row', $query_arr);
        $this->assign('batch_type', $this->BATCH_TYPE);
        $this->display();
    }
    
    // 编辑提交页
    public function editSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        
        if (empty($data['name']) || empty($data['id']))
            $this->error('请填写活动名！');
        
        if (! empty($data['sns_type'])) {
            $sns_type = implode('-', $data['sns_type']);
        } else {
            $sns_type = '';
        }
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $baseActivityModel->checkisactivitynamesame($data['name'], 
            $this->BATCH_TYPE, $data['id']);
        $query_arr = $baseActivityModel->checkactivityexist($data['id'], 
            $this->BATCH_TYPE, $this->nodeIn());
        $node_id = $query_arr['node_id'];
        
        if (empty($data['wap_info']))
            $this->error('活动页面内容不能为空');
        $map = array(
            'node_id' => $node_id, 
            'id' => $data['id']);
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'video_url' => $data['video_url'], 
            // 'start_time' => substr($data['start_time'], 0, 8) . '000000',
            // 'end_time' => substr($data['end_time'], 0, 8) . '235959',
            'memo' => $data['memo'], 
            'size' => $data['size'], 
            'sns_type' => $sns_type, 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $data['resp_bg_img'], 
            'is_show' => '1');
        if ($data['is_reset'] == '1') {
            $data_arr['is_cj'] = $data['is_cj'];
        }
        
        $log_img = $data['resp_log_img'];
        $code_img = $data['resp_code_img'];
        $music = $data['resp_music'];
        if ($data['is_code_img'] == '1' && ! empty($code_img)) {
            $data_arr['code_img'] = $code_img;
        } else {
            if ($data['is_code_img'] == '0') {
                $data_arr['code_img'] = '';
            }
        }
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
        
        $execute = $model->where($map)->save($data_arr);
        
        $batchdata = json_decode($data['batchjson'], true);
        
        if ($execute === false)
            $this->error('系统错误！', 
                array(
                    '返回进球有礼' => U('index')));
        else {
            
            // 更新奖品规则
            if ($data['is_reset'] == '2') {
                $ser = D('TCjBatch');
                $arr = array(
                    'node_id' => $this->nodeId, 
                    'batch_type' => $this->BATCH_TYPE, 
                    'batch_id' => $data['id'], 
                    'chance_edit' => $data['total_chance_edit'], 
                    'batch_data' => $batchdata);
                
                $cj_edit_resp = $ser->editCjRuleWorldCup($arr);
                if ($cj_edit_resp !== true)
                    $this->error($cj_edit_resp);
            }
        }
        node_log('进球有礼编辑|活动id:' . $data['id']);
        $this->success('更新成功', array(
            '返回进球有礼' => U('index')));
    }
    
    // 状态修改
    public function editStatus() {
        $data = I('post.');
        
        // 启用活动时，相同活动判断
        $status = I('post.status', null);
        if ($status == 1) {
            $dataInfo = M('tmarketing_info')->where(
                array(
                    'batch_type' => $this->BATCH_TYPE, 
                    'node_id' => $this->node_id, 
                    'status' => '1', 
                    'defined_one_name' => $result['defined_one_name'], 
                    'id' => array(
                        'neq', 
                        $data['batch_id'])))->find();
            if ($dataInfo) {
                $this->error('该场次已经有一个进球有礼活动正在进行中，如果需要启用新的活动替换现有活动，请先停用现有活动！');
            }
        }
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($result) {
            node_log('进球有礼状态更改|活动id:' . $data['batch_id']);
            $this->success('更新成功', 
                array(
                    '返回' => U('index')));
        } else {
            $this->error('更新失败');
        }
    }
    
    // 数据导出
    public function export() {
        $status = I('status', null, 'mysql_real_escape_string');
        
        // 查询条件组合
        $where = "WHERE batch_type='" . $this->BATCH_TYPE . "'";
        if (! empty($_POST)) {
            $filter = array();
            $condition = array_map('trim', I('post.'));
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
    	 tmarketing_info {$where} AND node_id in ({$this->nodeIn() })";
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

    public function winningExport() {
        $batchId = I('batch_id', null, 'mysql_real_escape_string');
        $status = I('status', null, 'mysql_real_escape_string');
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
        
        $sql = "SELECT w.mobile,w.add_time,
			    	CASE w.status WHEN '1' THEN '未中奖' ELSE '中奖' END status,w.prize_level,
			    	ch.id channel_id , ch.name channel_name
			    FROM tcj_trace w
			    LEFT JOIN tchannel ch ON ch.id = w.channel_id
			    WHERE w.batch_id='{$batchId}' AND w.batch_type='{$this->BATCH_TYPE}' AND w.node_id = '{$node_id}'
			    ORDER by w.status DESC";
        $countSql = "SELECT COUNT(*) as count FROM tcj_trace WHERE batch_id='{$batchId}' AND batch_type='{$this->BATCH_TYPE}' AND node_id='{$node_id}' ";
        $cols_arr = array(
            'mobile' => '手机号', 
            'add_time' => '中奖时间', 
            'status' => '是否中奖', 
            'prize_level' => '奖品等级', 
            'channel_id' => '渠道号', 
            'channel_name' => '渠道名称');
        
        // 获取活动名称
        $batchName = M('tmarketing_info')->where(
            "id='{$batchId}' AND node_id ='{$node_id}'")->getField('name');
        $fileName = $batchName . '-' . date('Y-m-d') . '-' . '中奖名单';
        $count = M()->query($countSql);
        if ($count[0]['count'] <= 0)
            $this->error('没有中奖数据');
        if (empty($status))
            $this->ajaxReturn('', '', 1);
        if (querydata_download($sql, $cols_arr, M(), $fileName) == false) {
            $this->error('下载失败或没有中奖数据');
        }
    }
    
    // 参与名单
    public function joinExport() {
        $batchId = I('batch_id', null, 'mysql_real_escape_string');
        $status = I('status', null, 'mysql_real_escape_string');
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
        
        $sql = "SELECT w.mobile,w.add_time,ch.id channel_id , ch.name channel_name
		FROM tcj_trace w
		LEFT JOIN tchannel ch ON ch.id = w.channel_id
		WHERE w.batch_id='{$batchId}' AND w.batch_type='{$this->BATCH_TYPE}' AND w.node_id = '{$node_id}'
		ORDER by w.status DESC";
        $countSql = "SELECT COUNT(*) as count FROM tcj_trace WHERE batch_id='{$batchId}' AND batch_type='{$this->BATCH_TYPE}' AND node_id='{$node_id}' ";
        $cols_arr = array(
            'mobile' => '手机号', 
            'add_time' => '参与时间', 
            'channel_id' => '渠道号', 
            'channel_name' => '渠道名称');
        
        // 获取活动名称
        $batchName = M('tmarketing_info')->where(
            "id='{$batchId}' AND node_id ='{$node_id}'")->getField('name');
        $fileName = $batchName . '-' . date('Y-m-d') . '-' . '参与用户名单';
        $count = M()->query($countSql);
        if ($count[0]['count'] <= 0)
            $this->error('没有查询到相关数据');
        if (empty($status))
            $this->ajaxReturn('', '', 1);
        if (querydata_download($sql, $cols_arr, M(), $fileName) == false) {
            $this->error('下载失败或没有中奖数据');
        }
    }
}
