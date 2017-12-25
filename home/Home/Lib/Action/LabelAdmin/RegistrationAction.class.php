<?php

class RegistrationAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE = '32';
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
    }
    
    // 首页
    public function index() {
        if (! $this->checkRegNode())
            $this->error('您无法使用该功能');
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
        $re_type_arr = C('RE_TYPE_ARR');
        $this->assign('re_type_arr', $re_type_arr);
        
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        $this->display();
    }
    
    // 添加提交页
    public function addSubmit() {
        if (! $this->checkRegNode()) {
            $this->error('您无法使用该功能');
        }
        
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
            'memo' => $data['memo'], 
            'select_type' => $select_type, 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            // 'size'=>$data['size'],
            // 'code_img'=>$data['resp_code_img'],
            'sns_type' => $result['sns_type'], 
            'chance' => $data['chance'], 
            'goods_count' => $data['goods_count'], 
            'day_goods_count' => $data['day_goods_count'], 
            'is_cj' => '0', 
            'jp_type' => $data['jp_type'], 
            'zc_batch_no' => $data['zc_batch_no'], 
            'wc_batch_no' => $data['wc_batch_no'], 
            'defined_one_name' => $data['defined7'], 
            'defined_two_name' => $data['defined8'], 
            'defined_three_name' => $data['defined9'], 
            'node_name' => $data['node_name'], 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $result['bg_img'], 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1');
        
        $tranDb = new Model();
        $tranDb->startTrans();
        $resp_id = $model->add($data_arr);
        if (! $resp_id) {
            $tranDb->rollback();
            $this->error('活动创建失败！', 
                array(
                    '返回有奖答题' => U('index')));
        }
        $tranDb->commit();
        node_log('注册有礼活动添加|活动名:' . $data['name']);
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
        $id = $this->_param('id');
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
        if (! $this->checkRegNode()) {
            $this->error('您无法使用该功能');
        }
        $model = M('tmarketing_info');
        $data = I('post.');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $baseActivityModel->checkisactivityheadinfowrite($data);
        $baseActivityModel->checkisactivitynamesame($data['name'], 
            $this->BATCH_TYPE, $data['id']);
        
        if (! empty($data['select_type'])) {
            $select_type = $baseActivityModel->imploadearray(
                $data['select_type']);
        } else {
            $select_type = '';
        }
        
        // 社交分享
        if (! empty($data['sns_type'])) {
            $sns_type = $baseActivityModel->implodearray($data['sns_type']);
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
        // if ($data['resp_bg_img'] != '' && $data['reset_bg'] == '1') {
        // $bg_img =
        // $baseActivityModel->activitybackgroundpicupload($data['resp_bg_img'],
        // $this->BATCH_TYPE, $this->img_path);
        // }else {
        $bg_img = $data['resp_bg_img'];
        // }
        
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'wap_info' => $data['wap_info'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'memo' => $data['memo'], 
            'select_type' => $select_type, 
            // 'size'=>$data['size'],
            // 'code_img'=>$data['resp_code_img'],
            'sns_type' => $sns_type, 
            'node_name' => $data['node_name'], 
            'defined_one_name' => $data['defined10'], 
            'defined_two_name' => $data['defined11'], 
            'defined_three_name' => $data['defined12'], 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $bg_img, 
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
        
        $tranDb = new Model();
        $tranDb->startTrans();
        $resp_id = $model->where($map)->save($data_arr);
        
        if ($resp_id === false) {
            $tranDb->rollback();
            $this->error('活动更新失败！', 
                array(
                    '返回注册有礼' => U('index')));
        }
        $tranDb->commit();
        node_log('注册有礼活动编辑|活动名:' . $data['name'] . '|活动ID：' . $data['id']);
        redirect(
            U('MarketActive/Activity/MarketList'));
    }
    
    // 状态修改
    public function editStatus() {
        if (! $this->checkRegNode()) {
            $this->error('您无法使用该功能');
            exit();
        }
        
        $data = I('post.');
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        if ($result) {
            node_log('注册有礼活动状态更改|活动ID：' . $data['batch_id']);
            $this->success('更新成功', 
                array(
                    '返回' => U('Answers/index')));
        } else {
            $this->error('更新失败');
        }
    }
    
    // 数据导出
    public function export() {
        if (! $this->checkRegNode())
            $this->error('您无法使用该功能');
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
		click_count,send_count,member_sum
		FROM
		tmarketing_info {$where} AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
            'name' => '活动名称', 
            'add_time' => '添加时间', 
            'start_time' => '活动开始时间', 
            'end_time' => '活动结束时间', 
            'status' => '状态', 
            'click_count' => '访问量', 
            'send_count' => '发码量', 
            'member_sum' => '注册数');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }

    public function BmCountExport() {
        if (! $this->checkRegNode())
            $this->error('您无法使用该功能');
        set_time_limit(0);
        $batchId = I('get.batch_id', null, 'intval');
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
                    $excel_title .= $q_v['questions'] . '(' .
                         $type_arr[$q_v['type']] . ')' . ",";
                }
            }
            $excel_title = substr($excel_title, 0, - 1);
            $excel_title = iconv('utf-8', 'gbk', $excel_title);
            echo $excel_title . "\r\n";
            // 内容
            foreach ($query as $vv) {
                $excel_note = '';
                foreach ($column_arr as $k => $v) {
                    $excel_note .= $vv[$k] . ",";
                }
                $excel_note .= dateformat($vv['add_time'], 'Y-m-d') . ",";
                foreach ($row as $q_v) {
                    
                    if (empty($row))
                        break;
                    $res = M('tanswers_question_stat')->where(
                        "bm_seq_id={$vv['id']} AND question_id={$q_v['id']}")->find();
                    if ($res) {
                        $excel_note .= $res['answer_list'] . ",";
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
        if (! $this->checkRegNode())
            $this->error('您无法使用该功能');
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
    
    // 下载注册名单
    public function nodeExport() {
        $batchId = I('get.batch_id', null);
        if (is_null($batchId))
            $this->error('缺少参数');
        
        $edit_wh = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $batchId);
        $batchInfo = M('tmarketing_info')->where($edit_wh)->find();
        if (! $batchInfo)
            $this->error('未查询到记录！');
        
        $fileName = $batchInfo['name'] . '-' . date('Y-m-d') . '-注册名单.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "注册时间,来源渠道,用户名,企业名称,负责人,手机号,所在城市\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $query = M()->query(
                "SELECT LEFT(i.add_time,8) as add_time,n.name,i.contact_eml,i.node_name,i.contact_name,i.contact_phone,c.city
								 FROM tnode_info i
								 LEFT JOIN tcity_code c ON i.node_citycode = c.path
								 Left JOIN tchannel n ON i.channel_id=n.id
								 WHERE i.add_time >= {$batchInfo['start_time']} AND i.add_time <= {$batchInfo['end_time']} AND i.reg_from = '2'
							     ORDER BY i.add_time DESC 
							     LIMIT {$page},{$page_count}");
            if (! $query)
                exit();
            foreach ($query as $v) {
                $channelName = iconv('utf-8', 'gbk', $v['name']);
                $contactEml = iconv('utf-8', 'gbk', $v['contact_eml']);
                $nodeName = iconv('utf-8', 'gbk', $v['node_name']);
                $contactName = iconv('utf-8', 'gbk', $v['contact_name']);
                $city = iconv('utf-8', 'gbk', $v['city']);
                echo "{$v['add_time']},{$channelName},{$contactEml},{$nodeName},{$contactName},{$v['contact_phone']},{$city}\r\n";
            }
        }
    }
    
    // 校验能够使用注册有礼活动的商户(市场部的商户)
    public function checkRegNode() {
        $nodeArr = C('REG_NODE_LIST');
        if (in_array($this->nodeId, $nodeArr)) {
            return true;
        } else {
            return false;
        }
    }
}
