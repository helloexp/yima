<?php

/**
 * 冠军竞猜营销活动
 *
 * @author bao
 */
class ChampionGuessAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE = '25';
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
        // 定制化，等级多奖品
        $this->mg_flag = in_array($this->node_id, C('LEVEL_MORE_PRIZE_NODE'), 
            true) ? 1 : 0;
        $this->assign('mg_flag', $this->mg_flag);
        
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
    }

    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'batch_type' => $this->BATCH_TYPE);
        $nodeId = I('node_id', null, 'mysql_real_escape_string');
        if (! empty($nodeId)) {
            $map['node_id '] = $nodeId;
        }
        $name = I('key', '', 'mysql_escape_string');
        if ($name != '') {
            $map['name'] = array(
                'like', 
                '%' . $name . '%');
        }
        $status = I('status', null, 'mysql_escape_string');
        if (! empty($status)) {
            $map['status'] = $status;
        }
        // 处理特殊查询字段
        $beginDate = I('start_time', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate)) {
            $map['add_time'] = array(
                'egt', 
                $beginDate . '000000');
        }
        $endDate = I('end_time', null, 'mysql_real_escape_string,trim');
        if (! empty($endDate)) {
            $map[' add_time'] = array(
                'elt', 
                $endDate . '235959');
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($_REQUEST as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出
        
        $list = $model->where($map)
            ->order('status')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $arr_ = C('CHANNEL_TYPE');
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }

    public function add() {
        $worldCupEndTime = C('WORLDCUP_END_TIME');
        $addtime = date('YmdHis');
        if ($worldCupEndTime <= date('Ymd'))
            $this->error('世界杯比赛已经结束');
        $dataInfo = M('tmarketing_info')->where(
            "node_id='{$this->nodeId}' AND batch_type='{$this->BATCH_TYPE}' AND status=1")->find();
        if ($dataInfo) {
            $this->error('您当前已经有一个冠军竞猜活动正在进行中，如果需要创建新的活动替换现有活动，请先停用现有活动！');
        }
        
        if ($this->isPost()) {
            $error = '';
            $name = I('post.name', null);
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("活动名称{$error}");
            }
            
            $baseActivityModel = D('BaseActivity', 'Service');
            $baseActivityModel->checkisactivitynamesame($name, 
                $this->BATCH_TYPE);
            
            $startDate = date('Ymd');
            $endDate = $worldCupEndTime;
            $showNodeName = I('post.node_name', null);
            if (! check_str($showNodeName, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("商户名称{$error}");
            }
            $wapInfo = I('post.wap_info', null);
            if (! check_str($wapInfo, 
                array(
                    'null' => true), $error)) {
                $this->error("活动页面内容{$error}");
            }
            // 奖品处理
            $batchJson = $_POST['batchjson'];
            $batchJson = json_decode($batchJson, true);
            if (empty($batchJson) || empty($batchJson['list'])) {
                $this->error('请选择奖品');
            }
            $snsType = I('post.sns_type');
            if (! empty($snsType)) {
                $snsType = implode('-', $snsType);
            }
            
            // logo图
            $logImgName = I('post.resp_log_img', null);
            $useLogoOrNot = I('post.is_logo_img');
            if ($logImgName != '' && $useLogoOrNot == 1) {
                $logImg = $logImgName;
            }
            $data = array(
                'name' => $name, 
                'node_id' => $this->nodeId, 
                'node_name' => $showNodeName, 
                'wap_info' => $wapInfo, 
                'start_time' => $startDate . '000000', 
                'end_time' => $endDate, 
                'log_img' => $logImg, 
                'is_cj' => '1', 
                'sns_type' => $snsType, 
                'add_time' => $addtime, 
                'status' => '1', 
                'batch_type' => $this->BATCH_TYPE, 
                'is_show' => '1');
            M()->startTrans();
            $batchId = M('tmarketing_info')->add($data);
            if (! $batchId) {
                M()->rollback();
                $this->error('系统出错,添加失败');
            }
            // 更新奖品规则
            if ($data['is_cj'] == '1') {
                $cjrulem = M('tcj_rule');
                $cdata = array(
                    'batch_type' => $this->BATCH_TYPE, 
                    'batch_id' => $batchId, 
                    'jp_set_type' => count($batchJson['list']) > 1 ? '2' : '1', 
                    'node_id' => $this->nodeId, 
                    'add_time' => date('YmdHis'), 
                    'status' => '1');
                $rulequery = $cjrulem->add($cdata);
                if (! $rulequery) {
                    M()->rollback();
                    $this->error('系统出错,添加失败！', 
                        array(
                            '返回抽奖活动' => U('index')));
                }
                
                $cjbatchm = M('tcj_batch');
                if ($_POST['jp_set_type'] == '1') {
                    $vv = $batchJson['list'][0];
                    $bdata = array(
                        'batch_id' => $batchId, 
                        'node_id' => $this->nodeId, 
                        'activity_no' => $vv['batch_no'], 
                        'award_origin' => '2', 
                        'award_level' => '1', 
                        'total_count' => $vv['total_count'], 
                        'batch_type' => $this->BATCH_TYPE, 
                        'cj_rule_id' => $rulequery, 
                        'b_id' => $vv['batch_id']);
                    
                    if (in_array('', $bdata)) {
                        M()->rollback();
                        $this->error('奖品规则设置错误！');
                    }
                    $bquery = $cjbatchm->add($bdata);
                    if (! $bquery) {
                        M()->rollback();
                        $this->error('奖品设置错误！');
                    }
                } elseif ($_POST['jp_set_type'] == '2') {
                    foreach ($batchJson['list'] as $vv) {
                        $bdata = array(
                            'batch_id' => $batchId, 
                            'node_id' => $this->node_id, 
                            'activity_no' => $vv['batch_no'], 
                            'award_origin' => 2, 
                            'award_level' => $vv['level'], 
                            'total_count' => $vv['total_count'], 
                            'batch_type' => $this->BATCH_TYPE, 
                            'cj_rule_id' => $rulequery, 
                            'b_id' => $vv['batch_id']);
                        
                        if (in_array('', $bdata)) {
                            M()->rollback();
                            $this->error('奖品规则设置错误2！');
                        }
                        
                        $bquery = $cjbatchm->add($bdata);
                        if (! $bquery) {
                            M()->rollback();
                            $this->error('奖品规则设置错误3！');
                        }
                    }
                }
            }
            M()->commit();
            $ser = D('TmarketingInfo');
            $arr = array(
                'node_id' => $this->nodeId, 
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $batchId);
            $ser->init($arr);
            $ser->sendBatch();
            node_log("冠军竞猜活动创建。名称：" . $name);
            $this->ajaxReturn(
                array(
                    'url' => U('LabelAdmin/BindChannel/index', 
                        array(
                            'batch_type' => $this->BATCH_TYPE, 
                            'batch_id' => $batchId))), '添加成功！', 1);
            exit();
        }
        node_log("首页+冠军竞猜");
        // 获取商户名称
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        $this->assign('batch_list', $arr);
        $this->display();
    }

    public function edit() {
        $id = I('id', null, 'intval');
        if (empty($id)) {
            $this->error('错误参数');
        }
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $query_arr = $baseActivityModel->checkactivityexist($id, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($query_arr['log_img'] == '') {
            $nodeInfo = get_node_info($this->node_id);
            $this->assign('head_photo', get_upload_url($nodeInfo['head_photo']));
            $this->assign('node_logo', $nodeInfo['head_photo']);
        }
        
        if ($this->isPost()) {
            $error = '';
            $name = I('post.name', null);
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("活动名称{$error}");
            }
            
            $baseActivityModel->checkisactivitynamesame($name, 
                $this->BATCH_TYPE, $id);
            
            $showNodeName = I('post.node_name', null);
            if (! check_str($showNodeName, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("商户名称{$error}");
            }
            $wapInfo = I('post.wap_info', null);
            if (! check_str($wapInfo, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("活动页面内容{$error}");
            }
            $isCj = I('post.is_cj', null);
            $isRest = I('post.is_reset');
            $snsType = I('post.sns_type');
            if (! empty($snsType)) {
                $snsType = implode('-', $snsType);
            }
            // logo图
            $logImgName = I('post.resp_log_img', null);
            $resetloge = I('post.reset_logo', null);
            $logImg = $logImgName;
            $data = array(
                'name' => $name, 
                'node_id' => $this->node_id, 
                'node_name' => $showNodeName, 
                'wap_info' => $wapInfo, 
                'log_img' => $logImg, 
                'sns_type' => $snsType, 
                'is_show' => '1');
            $result = M('tmarketing_info')->where(
                "node_id='{$this->node_id}' AND id='{$id}'")->save($data);
            if ($result === false) {
                $this->error('系统出错,更新失败!');
            }
            if ($_POST['is_reset'] == '2') {
                // 奖品处理
                $batchJson = $_POST['batchjson'];
                $batchJson = json_decode($batchJson, true);
                $ser = D('TCjBatch');
                $arr = array(
                    'node_id' => $this->node_id, 
                    'batch_type' => $this->BATCH_TYPE, 
                    'batch_id' => $id, 
                    'batch_data' => $batchJson);
                
                $cj_edit_resp = $ser->editCjRuleWorldCup($arr);
                if ($cj_edit_resp !== true)
                    $this->error($cj_edit_resp);
            }
            node_log("冠军竞猜活动修改。名称：" . $name);
            $this->success('更新成功!');
            exit();
        }
        
        $batch_model = M('tbatch_info');
        if ($query_arr['is_cj'] == '1') {
            $cjrule = M('tcj_rule');
            $map1 = array(
                'node_id' => $this->node_id, 
                'batch_id' => $query_arr['id'], 
                'batch_type' => $this->BATCH_TYPE, 
                'status' => '1');
            $cjarr = $cjrule->where($map1)->find();
            $map1 = array(
                'node_id' => $this->node_id, 
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
                    'node_id' => $this->node_id);
                foreach ($cjbatch_arr as $v) {
                    $batch_map['id'] = $v['b_id'];
                    $batch_name_arr[$v['activity_no']] = $batch_model->where(
                        $batch_map)->getField('batch_short_name');
                }
            }
        }
        $this->assign('cjarr', $cjarr);
        $this->assign('cjbatch_arr', $cjbatch_arr);
        $this->assign('batch_name_arr', $batch_name_arr);
        $this->assign('row', $query_arr);
        $this->display();
    }
    
    // 状态修改
    public function editStatus() {
        $data = I('post.');
        $status = I('post.status', null);
        if ($status == 1) {
            $dataInfo = M('tmarketing_info')->where(
                "node_id='{$this->nodeId}' AND batch_type='{$this->BATCH_TYPE}' AND status=1")->find();
            if ($dataInfo) {
                $this->error('您当前已经有一个冠军竞猜活动正在进行中，请先停用现有活动！');
            }
        }
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($result) {
            node_log(
                "冠军竞猜活动" . $status == 1 ? '开启' : '停用' . "。活动ID：" .
                     $data['batch_id']);
            $this->success('更新成功', 
                array(
                    '返回' => U('News/index')));
        } else {
            $this->error('更新失败');
        }
    }

    public function winningExport() {
        $batchId = I('batch_id', null, 'mysql_real_escape_string');
        $status = I('status', null, 'mysql_real_escape_string');
        if (is_null($batchId)) {
            $this->error('缺少参数');
        }
        $sql = "SELECT w.phone_no,w.add_time,
				(CASE w.result 
				WHEN '0' THEN '未抽奖' 
				WHEN '1' THEN '未中奖'
				WHEN '2' THEN '已中奖'
				WHEN '3' THEN '发码失败'
				ELSE '未知' END) result,b.batch_short_name,ch.id channel_id , ch.name channel_name
				FROM tworld_cup_match_quiz w
				LEFT JOIN tbatch_info b ON w.activity_no=b.batch_no AND w.activity_no is not null AND w.activity_no <>''
				LEFT JOIN tbatch_channel c ON c.id = w.label_id
				LEFT JOIN tchannel ch ON ch.id = c.channel_id
				WHERE w.batch_id='{$batchId}' AND w.node_id='{$this->nodeId}' AND w.quiz_type=0";
        $countSql = "SELECT COUNT(*) as count FROM tworld_cup_match_quiz WHERE batch_id='{$batchId}' AND node_id='{$this->nodeId}' AND quiz_type=0";
        $cols_arr = array(
            'phone_no' => '手机号', 
            'add_time' => '参与时间', 
            'result' => '抽奖结果', 
            'batch_short_name' => '奖品名称', 
            'channel_id' => '渠道号', 
            'channel_name' => '渠道名称');
        // 获取活动名称
        $batchName = M('tmarketing_info')->where(
            "id='{$batchId}' AND node_id in({$this->nodeIn()})")->getField(
            'name');
        $fileName = $batchName . '-' . date('Y-m-d') . '-' . '中奖名单';
        $count = M()->query($countSql);
        if ($count[0]['count'] <= 0)
            $this->error('没有查询到中奖数据');
        if (empty($status))
            $this->ajaxReturn('', '', 1);
        if (querydata_download($sql, $cols_arr, M(), $fileName) == false) {
            $this->error('下载失败');
        }
    }
    
    // 参与名单
    public function joinExport() {
        $batchId = I('batch_id', null, 'mysql_real_escape_string');
        $status = I('status', null, 'mysql_real_escape_string');
        if (is_null($batchId))
            $this->error('缺少参数');
        $sql = "SELECT w.phone_no,t.team_name,w.add_time ,ch.id channel_id , ch.name channel_name
		FROM tworld_cup_match_quiz w
		LEFT JOIN tworld_cup_team_info t ON w.team_id=t.team_id
		LEFT JOIN tbatch_channel c ON c.id = w.label_id
		LEFT JOIN tchannel ch ON ch.id = c.channel_id
		WHERE w.batch_id='{$batchId}' AND w.node_id='{$this->nodeId}' AND w.quiz_type=0";
        $countSql = "SELECT COUNT(*) as count FROM tworld_cup_match_quiz WHERE batch_id='{$batchId}' AND node_id='{$this->nodeId}' AND quiz_type=0";
        $cols_arr = array(
            'phone_no' => '手机号', 
            'team_name' => '竞猜的队伍', 
            'add_time' => '参与时间', 
            'channel_id' => '渠道号', 
            'channel_name' => '渠道名称');
        // 获取活动名称
        $batchName = M('tmarketing_info')->where(
            "id='{$batchId}' AND node_id in({$this->nodeIn()})")->getField(
            'name');
        $fileName = $batchName . '-' . date('Y-m-d') . '-' . '参与用户名单';
        $count = M()->query($countSql);
        if ($count[0]['count'] <= 0)
            $this->error('没有查询到相关数据');
        if (empty($status))
            $this->ajaxReturn('', '', 1);
        if (querydata_download($sql, $cols_arr, M(), $fileName) == false) {
            $this->error('下载失败');
        }
    }
}
