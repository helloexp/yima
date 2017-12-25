<?php
// 圣诞节活动
class SnowBallAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE = '35';
    // 图片路径
    public $img_path;
    
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
        $this->assign('tbatch_type', $this->BATCH_TYPE);
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
        node_log("首页+圣诞节");
        
        $arr_ = C('CHANNEL_TYPE');
        $this->assign('is_cj_button', '1');
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }
    
    // 添加页
    public function add() {
        // edit by tr 针对广西石油临时开放一下权限
        if ($this->node_id != '00004488') {
            $this->offlineactivitynotice();
        }
        
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        $this->display();
    }
    
    // 添加提交页
    public function addSubmit() {
        // edit by tr 针对广西石油临时开放一下权限
        if ($this->node_id != '00004488') {
            $this->offlineactivitynotice();
        }
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
            'memo' => $data['memo'], 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            // 'size'=>'1',
            // 'code_img'=>'',
            'sns_type' => $result['sns_type'], 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_cj' => '0', 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $result['bg_img'], 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1');
        
        $resp_id = $model->add($data_arr);
        if (! $resp_id) {
            $this->error('系统错误！', 
                array(
                    '返回圣诞节' => U('index')));
        }
        
        $ser = D('TmarketingInfo');
        $arr = array(
            'node_id' => $this->nodeId, 
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $resp_id);
        $ser->init($arr);
        $ser->sendBatch();
        node_log('圣诞节添加|活动名:' . $data['name']);
        
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
        // edit by tr 针对广西石油临时开放一下权限
        if ($this->node_id != '00004488') {
            $this->offlineactivitynotice();
        }
        
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
        $this->assign('row', $query_arr);
        $this->display();
    }
    
    // 编辑提交页
    public function editSubmit() {
        // edit by tr 针对广西石油临时开放一下权限
        if ($this->node_id != '00004488') {
            $this->offlineactivitynotice();
        }
        $model = M('tmarketing_info');
        $data = I('post.');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $baseActivityModel->checkisactivityheadinfowrite($data);
        $baseActivityModel->checkisactivitynamesame($data['name'], 
            $this->BATCH_TYPE, $data['id']);
        
        // 社交分享
        if (! empty($data['sns_type'])) {
            $sns_type = $baseActivityModel->implodearray($data['sns_type']);
        } else {
            $sns_type = '';
        }
        
        $query_arr = $baseActivityModel->checkactivityexist($data['id'], 
            $this->BATCH_TYPE, $this->nodeIn());
        $node_id = $query_arr['node_id'];
        
        $map = array(
            'node_id' => $node_id, 
            'id' => $data['id']);
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'video_url' => $data['video_url'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'memo' => $data['memo'], 
            // 'size'=>'1',
            'sns_type' => $sns_type, 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_show' => '1');
        
        $log_img = $data['resp_log_img'];
        $code_img = $data['resp_code_img'];
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
                    '返回圣诞节' => U('index')));
        
        node_log('圣诞节编辑|活动id:' . $data['id']);
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
            node_log('圣诞节状态更改|活动id:' . $data['batch_id']);
            $this->success('更新成功', 
                array(
                    '返回' => U('News/index')));
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
			    	tcj_trace WHERE batch_id='{$batchId}' AND batch_type='{$this->BATCH_TYPE}' AND node_id = '{$node_id}'
			    	ORDER by status DESC";
        $countSql = "SELECT COUNT(*) as count FROM tcj_trace WHERE batch_id='{$batchId}' AND batch_type='{$this->BATCH_TYPE}' AND node_id='{$node_id}' ";
        $cols_arr = array(
            'mobile' => '手机号', 
            'add_time' => '中奖时间', 
            'status' => '是否中奖', 
            'prize_level' => '奖品等级');
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
}
