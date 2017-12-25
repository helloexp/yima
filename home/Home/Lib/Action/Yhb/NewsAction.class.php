<?php
// 新闻阅读
class NewsAction extends YhbAction {
    // 活动类型
    public $BATCH_TYPE = '2';
    // 图片路径
    public $img_path;

    public $_authAccessMap = '*';
    
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
        $sns_arr = array(
            '1' => '新浪微博', 
            '2' => '腾讯微博', 
            '3' => 'QQ空间', 
            '4' => '人人网');
        $this->assign('sns_arr', $sns_arr);
    }
    
    // 添加页
    public function add() {
        // 获取商户名称
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        $this->display("MarketActive:News_add");
    }
    
    // 添加提交页
    public function addSubmit() {
        $tranDb = new Model();
        $tranDb->startTrans();
        $model = M('tmarketing_info');
        $data = I('post.');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->activityAddDataBaseVirefy($data, 
            $this->BATCH_TYPE, $this->img_path);
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'log_img' => $result['log_img'], 
            'music' => $data['resp_music'], 
            'video_url' => $data['video_url'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            // 'memo'=>$data['memo'],
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            // 'size'=>'2',
            // 'code_img'=>$data['resp_code_img'],
            'sns_type' => $result['sns_type'], 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_cj' => '0', 
            'pay_status' => '1', 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $result['bg_img'], 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1', 
            'batch_come_from' => session('batch_from') ? session('batch_from') : '1');
        
        $resp_id = $model->add($data_arr);
        if (! $resp_id) {
            $tranDb->rollback();
            $this->error('系统错误！', 
                array(
                    '返回抽奖活动' => U('MarketActive/listNew', 
                        array(
                            'batch_type' => $this->BATCH_TYPE))));
        }
        $userService = D('UserSess', 'Service');
        $user_info = $userService->getUserInfo();
        $config_data = array(
            'mid' => $resp_id, 
            'user_id' => $user_info['user_id'], 
            'merchant_id' => $this->merchant_id, 
            'pub_status' => 0);
        
        $mcres = M('tfb_yhb_mconfig')->add($config_data);
        if ($mcres == false) {
            $tranDb->rollback();
            $this->error('系统错误！');
        }
        
        $tranDb->commit();
        
        $ser = D('TmarketingInfo');
        $arr = array(
            'node_id' => $this->nodeId, 
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $resp_id);
        $ser->init($arr);
        $ser->sendBatch();
        // 自动发布到在线活动
        node_log('抽奖活动添加|活动名:' . $data['name']);
        
        $lookUrl = $resp_id;
        $this->assign('lookUrl', $lookUrl);
        $this->redirect('Yhb/CjSet/index', 
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
        $id = I('get.id');
        if (empty($id)) {
            $this->error('错误参错');
        }
        
        // 审核后的活动不可再次修改
        $is_passed = $this->check_batch_passed($id);
        if ($is_passed)
            $this->error('活动已经审核通过，不可修改');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $query_arr = $baseActivityModel->checkactivityexist($id, 
            $this->BATCH_TYPE, $this->nodeIn());
        if ($query_arr['log_img'] == '') {
            $nodeInfo = get_node_info($this->node_id);
            $this->assign('head_photo', get_upload_url($nodeInfo['head_photo']));
            $this->assign('node_logo', $nodeInfo['head_photo']);
        }
        
        $this->assign('row', $query_arr);
        $this->display("MarketActive:News_edit");
    }
    
    // 编辑提交页
    public function editSubmit() {
        $data = I('post.');
        if ($data['id'] == '') {
            $this->error('参数错误');
        }
        
        $model = M('tmarketing_info');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $baseActivityModel->checkisactivityheadinfowrite($data);
        if (! empty($data['sns_type'])) {
            $sns_type = implode('-', $data['sns_type']);
        } else {
            $sns_type = '';
        }
        
        $baseActivityModel->checkisactivitynamesame($data['name'], 
            $this->BATCH_TYPE, $data['id']);
        
        // 背景图
        if ($data['resp_bg_img'] != '' && $data['reset_bg'] == '1') {
            // $bg_img =
            // $this->activitybackgroundpicupload($data['resp_bg_img'],
            // $this->BATCH_TYPE, $this->img_path);
            $bg_img = $data['resp_bg_img'];
        } else {
            $bg_img = $data['resp_bg_img'];
        }
        
        $map = array(
            'node_id' => $this->node_id, 
            'id' => $data['id']);
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'video_url' => $data['video_url'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            // 'memo'=>$data['memo'],
            // 'size'=>$data['size'],
            'sns_type' => $sns_type, 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
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
        if ($data['is_music'] == '1' && ! empty($music)) {
            $data_arr['music'] = $music;
        } else {
            if ($data['is_music'] == '0') {
                $data_arr['music'] = '';
            }
        }
        
        $execute = $model->where($map)->save($data_arr);
        if ($execute === false)
            $this->error('系统错误！', 
                array(
                    '返回抽奖活动' => U('MarketActive/listNew', 
                        array(
                            'batch_type' => $this->BATCH_TYPE))));
        
        node_log('抽奖活动编辑|活动id:' . $data['id']);
        redirect(
            U('Yhb/MarketActive/listNew', 
                array(
                    'batchtype' => $this->BATCH_TYPE)));
    }
    
    // 状态修改
    public function editStatus() {
        $data = I('post.');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        if ($result) {
            node_log('抽奖活动状态更改|活动id:' . $data['batch_id']);
            $this->success('更新成功', 
                array(
                    '返回' => U('MarketActive/listNew', 
                        array(
                            'batch_type' => $this->BATCH_TYPE))));
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
                $condition['start_time'] = $condition['start_time'] . '000000';
                $filter[] = "add_time >= '{$condition['start_time']}'";
            }
            if (isset($condition['end_time']) && $condition['end_time'] != '') {
                $condition['end_time'] = $condition['end_time'] . '235959';
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
    	 tmarketing_info {$where} AND node_id in ({$this->nodeIn()})";
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
        
        $sql = "SELECT mobile,add_time,
			    	CASE status WHEN '1' THEN '未中奖' ELSE '中奖' END status,prize_level
			    	FROM
			    	tcj_trace WHERE batch_id='{$batchId}' AND batch_type={$this->BATCH_TYPE} AND node_id = {$node_id}
			    	ORDER by status DESC";
        $countSql = "SELECT COUNT(*) as count FROM tcj_trace WHERE batch_id='{$batchId}' AND batch_type={$this->BATCH_TYPE} AND node_id={$node_id} ";
        $cols_arr = array(
            'mobile' => '手机号', 
            'add_time' => '中奖时间', 
            'status' => '是否中奖', 
            'prize_level' => '奖品等级');
        // 获取活动名称
        $batchName = M('tmarketing_info')->where(
            "id='{$batchId}' AND node_id ={$node_id}")->getField('name');
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
}
