<?php

/**
 * @功能：DF调研 @更新时间: 2015/02/04 15:50
 */
class BmAction extends BaseAction {
    
    // public $_authAccessMap = '*';
    // 活动类型
    public $BATCH_TYPE = '1004';
    // 图片路径
    public $img_path;
    // 哈根达斯标志
    private $hgds_flag = false;
    
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
        
        $this->hgds_flag = in_array($this->node_id, C('DM_Haagen_Dazs'));
        $this->assign('hgds_flag', $this->hgds_flag);
        C(include (CONF_PATH . 'Label/config.php'));
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
                $list[$k]['is_map'] = M('tquestion')->where(
                    array(
                        'label_id' => $v['id'], 
                        'type' => '4'))
                    ->limit(1)
                    ->getField('id');
            }
        }
        
        node_log("首页+市场调研");
        $arr_ = C('CHANNEL_TYPE');
        
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show);
        // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display();
        // 输出模板
    }
    
    // 添加页
    public function add() {
        $bm_type_arr = array(
            '1' => '姓名', 
            '2' => '手机号', 
            '3' => '性别');
        
        // 获取商户名称
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        
        $this->assign('bm_type_arr', $bm_type_arr);
        $this->assign('node_id', $this->nodeId);
        $this->assign('can_use_picupload', 
            in_array($this->node_id, C('DM_PICUPLOAD_NODEIDS'), true));
        
        if (in_array($this->nodeId, C('DM_Haagen_Dazs'))) {
            $this->display('add_haagen_dazs');
        } else {
            $this->display('Bm/add');
        }
    }
    
    // 添加提交页
    public function addSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->activityAddDataBaseVirefy($data, 
            $this->BATCH_TYPE, $this->img_path);
        
        // 信息采集字段
        if (! empty($data['select_type'])) {
            if (! in_array($this->node_id, C('DM_PICUPLOAD_NODEIDS'), true) &&
                 in_array('13', $data['select_type'], true)) {
                $this->error('当前机构不能使用调研的信息采集字段：图片上传！');
            }
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
            // 'memo' => $data['memo'],
            'select_type' => $select_type, 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            // 'size' => '2',
            // 'code_img' => $data['resp_code_img'],
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
            'defined_four_name' => $data['defined18'], 
            'defined_five_name' => $data['defined19'], 
            'defined_six_name' => $data['defined20'], 
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
            $this->error('市场调研添加失败！', 
                array(
                    '返回市场调研' => U('index')));
        }
        
        // 处理问卷
        
        /* 根据DF设置json格式处理 */
        $row = M('tfb_df_system_param')->where(
            "param_name ='DF_INFO_COLLECTION'")->find();
        $rows = json_decode($row['param_value']);
        $pro_num = I('pro_num') ? I('pro_num') : 30;
        
        // for ($i = 1; $i <= $pro_num; $i++)
        foreach ($rows as $key => $item) {
            if ($item->name == '')
                continue;
            $in_arr = array(
                'label_id' => $resp_id, 
                'questions' => $item->name, 
                'type' => 1, 
                'sort' => $key);
            
            // 问题表
            $qmodel = M('tquestion');
            $question_id = $qmodel->add($in_arr);
            if (! $question_id) {
                $tranDb->rollback();
                $this->error('系统错误！');
            }
            
            // 答案表
            $amodel = M('tquestion_info');
            if ($item->item != '' && is_array($item->item)) {
                $z = 0;
                foreach ($item->item as $v) {
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
        
        node_log('市场调研活动添加|活动名:' . $data['name']);
        
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
        if (empty($id)) {
            $this->error('错误参错');
        }
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $query_arr = $baseActivityModel->checkactivityexist($id, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($query_arr['log_img'] == '') {
            $nodeInfo = get_node_info($this->node_id);
            $this->assign('head_photo', get_upload_url($nodeInfo['head_photo']));
            $this->assign('node_logo', $nodeInfo['head_photo']);
        }
        
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
        $this->assign('row', $query_arr);
        $this->assign('can_use_picupload', 
            in_array($this->node_id, C('DM_PICUPLOAD_NODEIDS'), true));
        
        $bm_type_arr = array(
            '1' => '姓名', 
            '2' => '手机号', 
            '3' => '性别');
        
        $this->assign('bm_type_arr', $bm_type_arr);
        
        $this->display('Bm/edit');
    }
    
    // 编辑提交页
    public function editSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        
        // 是否有人参与此次调研
        $judge = M('tquestion_stat')->where(
            array(
                'label_id' => $data['id']))->find();
        if (! $judge) {
            if (empty($data['select_type'])) {
                $this->error('请选择wap页面显示字段！');
            }
        }
        
        $baseActivityModel = D('BaseActivity', 'Service');
        if (! empty($data['select_type'])) {
            if (! in_array($this->node_id, C('DM_PICUPLOAD_NODEIDS'), true) &&
                 in_array('13', $data['select_type'], true)) {
                $this->error('当前机构不能使用调研的信息采集字段：图片上传！');
            }
            $select_type = $baseActivityModel->implodearray(
                $data['select_type']);
        } else {
            $select_type = '';
        }
        
        $baseActivityModel->checkisactivityheadinfowrite($data);
        $baseActivityModel->checkisactivitynamesame($data['name'], 
            $this->BATCH_TYPE, $data['id']);
        
        $query_arr = $baseActivityModel->checkactivityexist($data['id'], 
            $this->BATCH_TYPE, $this->nodeIn());
        $node_id = $query_arr['node_id'];
        $sns_type = implode('-', $data['sns_type']);
        
        // 背景图
        if ($data['resp_bg_img'] != '' && $data['reset_bg'] == '1') {
            // 采用新的图片上传类 by tr
            // $bg_img =
            // $newsActionModel->activitybackgroundpicupload($data['resp_bg_img'],
            // $this->BATCH_TYPE, $this->img_path);
            $bg_img = $data['resp_bg_img'];
        } else {
            $bg_img = $data['resp_bg_img'];
        }
        
        $map = array(
            'node_id' => $node_id, 
            'id' => $data['id']);
        
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'wap_info' => $data['wap_info'], 
            'log_img' => $data['resp_log_img'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            // 'memo' => $data['memo'],
            // 'select_type' => $select_type,
            // 'size' => $data['size'],
            // 'code_img' => $data['resp_code_img'],
            'sns_type' => $sns_type, 
            'defined_one_name' => $data['defined10'], 
            'defined_two_name' => $data['defined11'], 
            'defined_three_name' => $data['defined12'], 
            'defined_four_name' => $data['defined18'], 
            'defined_five_name' => $data['defined19'], 
            'defined_six_name' => $data['defined20'], 
            'node_name' => $data['node_name'], 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $bg_img, 
            'is_show' => '1');
        
        if (! $judge) {
            $data_arr['select_type'] = $select_type;
            $data_arr['memo'] = $data['memo'];
        }
        $select_type = $baseActivityModel->implodearray($data['select_type']);
        $data_arr['select_type'] = $select_type;
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
        
        $tranDb = new Model();
        $tranDb->startTrans();
        $resp_id = $model->where($map)->save($data_arr);
        
        if ($resp_id === false) {
            $tranDb->rollback();
            $this->error('调研编辑失败！', 
                array(
                    '返回市场调研' => U('Df/Member/infocollection')));
        }
        
        if (! $judge) {
            // 调研
            $q_stat = M('tquestion_stat');
            $q_qeury = $q_stat->where(
                array(
                    'label_id' => $data['id']))->find();
            
            if (! $q_qeury) {
                $tquestion = M("tquestion");
                $tquestion_info = M("tquestion_info");
                
                $query_arr = $tquestion->where(
                    array(
                        'label_id' => $data['id']))->getField('id', true);
                if (is_array($query_arr)) {
                    
                    $info_map['question_id'] = array(
                        'in', 
                        $query_arr);
                    $res = $tquestion_info->where($info_map)->delete();
                }
                $res = $tquestion->where(
                    array(
                        'label_id' => $data['id']))->delete();
                
                // 处理问卷
                $pro_num = I('pro_num') ? I('pro_num') : 30;
                for ($i = 1; $i <= $pro_num; $i ++) {
                    $qname = 'q_' . $i;
                    $aname = 'a_' . $i;
                    $tname = 't_' . $i;
                    $sort = 'sort_' . $i;
                    
                    if ($data[$qname] == '')
                        continue;
                    $in_arr = array(
                        'label_id' => $data['id'], 
                        'questions' => $data[$qname], 
                        'type' => $data[$tname], 
                        'sort' => $data[$sort]);
                    
                    // 问题表
                    $qmodel = M('tquestion');
                    $question_id = $qmodel->add($in_arr);
                    if (! $question_id) {
                        $tranDb->rollback();
                        $this->error('系统错误！');
                    }
                    
                    // 答案表
                    $amodel = M('tquestion_info');
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
        node_log('市场调研活动编辑|活动名:' . $data['name'] . '|活动ID：' . $data['id']);
        redirect(U('Df/Member/infocollection'));
    }
    
    // 状态修改
    public function editStatus() {
        $data = I('post.');
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        if ($result) {
            node_log('市场调研活动状态更改|活动ID：' . $data['batch_id']);
            $this->success('更新成功', 
                array(
                    '返回' => U('Bm/index')));
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
    	 tmarketing_info {$where} AND node_id in({$this->nodeIn() })";
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
            '9' => '职位', 
            '14' => '车牌号', 
            '15' => '城市', 
            '16' => '身份证后6位', 
            '17' => '收货地址');
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
        $type_arr = array(
            '1' => '单选', 
            '2' => '多选', 
            '3' => '问答', 
            '4' => '地图调研');
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
        
        $d_query_arr = M('tmarketing_info')->field(
            'name,select_type,defined_one_name,defined_two_name,defined_three_name,defined_four_name,defined_five_name,defined_six_name')
            ->where("id={$batchId} AND node_id={$node_id}")
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
            } elseif ($v == '18') {
                $column_arr[($get_bm_arr[$v])] = $d_query_arr['defined_four_name'];
            } elseif ($v == '19') {
                $column_arr[($get_bm_arr[$v])] = $d_query_arr['defined_five_name'];
            } elseif ($v == '20') {
                $column_arr[($get_bm_arr[$v])] = $d_query_arr['defined_six_name'];
            } else {
                $column_arr[($get_bm_arr[$v])] = $bm_arr[$v];
            }
        }
        
        $search_str = 'id,add_time,label_id,node_id,' . implode(',', 
            $get_bm_arr) . ',channel_id,channel_name';
        
        $fileName = $d_query_arr['name'] . '-' . date('Y-m-d') . '-市场调研内容.csv';
        $fileName = iconv('utf-8', 'gbk', $fileName);
        
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        // header('Content-Type: text/html; charset=utf-8');
        foreach ($column_arr as $k => $v) {
            $excel_title .= $v . ",";
            $i ++;
        }
        $excel_title .= '"日期","渠道id","来源渠道"';
        
        // 查询表头
        $sql = "SELECT 
				  CASE
				    WHEN t.type IN ('3', '4') 
				    THEN t.questions 
				    ELSE CONCAT(t.questions, 'Q_', a.answers) 
				  END AS title
				  , t.id AS q_id
				  , t.questions AS q_value
				  , a.value AS a_id
				  , a.answers AS a_value 
				  , t.type
				FROM
				  tquestion t 
				  LEFT JOIN tquestion_info a 
				    ON a.question_id = t.id 
				WHERE t.label_id = '{$batchId}' 
				ORDER BY t.id ASC
				  , CAST(a.value AS UNSIGNED INT) ASC";
        
        $tmp_list = M()->table("($sql) a")->select();
        // 包含问题和答案的数组
        $qa_arr = array();
        
        // 问题-类型数组（1单选 2 多选 3文本 4地图调研）
        $qt_arr = array();
        foreach ($tmp_list as $info) {
            $excel_title .= ",\"{$info['title']}\"";
            $qa_arr[$info['q_id'] . '-' . $info['a_id']] = $info['a_value'];
            $qt_arr[$info['q_id']] = $info['type'];
        }
        
        $empty_answer = array_fill_keys(array_keys($qa_arr), '');
        // echo($excel_title."\r\n");
        echo iconv('utf-8', 'gbk', $excel_title) . "\r\n";
        
        $page_count = 5000;
        
        for ($j = 1; $j < 100; $j ++) {
            $sql = M('tbm_trace')->field("{$search_str}")
                ->where("label_id='{$batchId}' AND node_id='{$node_id}'")
                ->order('id')
                ->limit(($j - 1) * $page_count, $page_count)
                ->buildSql();
            $list = M()->table("($sql) t")
                ->join("tquestion_stat b ON b.bm_seq_id = t.id")
                ->field("t.*, b.question_id, b.answer_list")
                ->order("t.id ASC")
                ->select();
            $bm_seq_id = null;
            $line = '';
            $tmp_arr = $empty_answer;
            
            if ($list) {
                foreach ($list as $info) {
                    if ($info['id'] != $bm_seq_id) {
                        if ($line != '') {
                            foreach ($tmp_arr as $val) {
                                $line .= ',"' . $val . '"';
                            }
                            echo iconv('utf-8', 'gbk', $line) . "\r\n";
                            $line = '';
                            $tmp_arr = $empty_answer;
                        }
                        
                        $bm_seq_id = $info['id'];
                        foreach ($column_arr as $k => $v) {
                            $line .= $info[$k] . ",";
                        }
                        
                        $line .= dateformat($info['add_time'], 'Y-m-d H:i:s') .
                             ",";
                        $line .= $info['channel_id'] . ",";
                        $line .= $info['channel_name'] . "";
                    }
                    $type = $qt_arr[$info['question_id']];
                    $key = $info['question_id'] . '-';
                    if ($type == '3') {
                        $tmp_arr[$key] = $info['answer_list'];
                    } else if ($type == '4') {
                        $tmp_arr[$key] = substr($info['answer_list'], 0, 
                            strpos($info['answer_list'], '|#'));
                    } else {
                        $t_arr = explode('-', $info['answer_list']);
                        foreach ($t_arr as $a) {
                            $newkey = $key . $arr_stu[$a];
                            $tmp_arr[$newkey] = $qa_arr[$newkey];
                        }
                    }
                }
                
                foreach ($tmp_arr as $val) {
                    $line .= ',"' . $val . '"';
                }
                echo iconv('utf-8', 'gbk', $line) . "\r\n";
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
							    	tcj_trace WHERE batch_id={$batchId} AND batch_type={$this->BATCH_TYPE} AND node_id ={$nodeInfo['node_id']}
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
    
    // 导入黑名单
    public function whitelist() {
        $id = I('id');
        if ($_FILES['log_img']) {
            
            $id = I('wid');
            import('ORG.Net.UploadFile');
            // 导入文件
            $upload = new UploadFile();
            // 实例化上传类
            $upload->maxSize = 3145728;
            // 文件最大值
            $upload->allowExts = array(
                'csv');
            // 上传类型
            $upload->savePath = 'Home/Upload/whitelist_img/';
            // 上传路径
            $info = $upload->uploadOne($_FILES['log_img']);
            $flieWay = $upload->savePath . $info['savepath'] .
                 $info[0]['savename'];
            
            // 删除数据
            $data = array(
                'node_id' => $this->nodeId, 
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $id, 
                'type' => '2');
            $isexits = M('tfb_phone')->where($data)->find();
            
            // 上传路径接上文件名
            if (($handle = fopen($flieWay, "rw")) !== FALSE) {
                if ($isexits) {
                    $del = M('tfb_phone')->where($data)->delete();
                }
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // 读取csv文件
                    ++ $row;
                    $data = utf8Array($data);
                    if ($row == 1) {
                        $fileField = array(
                            '手机号');
                        $arrDiff = array_diff_assoc($data, $fileField);
                        if (count($data) != count($fileField) ||
                             ! empty($arrDiff)) {
                            fclose($handle);
                            unlink($flieWay);
                            $this->error('file contents is error!');
                        }
                        continue;
                    }
                    
                    // 校验字段
                    $memberName = $data[0];
                    $batchModel = M('tfb_phone');
                    $data = array(
                        'node_id' => $this->nodeId, 
                        'mobile' => $memberName, 
                        'batch_type' => $this->BATCH_TYPE, 
                        'add_time' => date('YmdHis'), 
                        'batch_id' => $id, 
                        'type' => '2');
                    
                    $batchId = $batchModel->add($data);
                }
            }
            echo "<script>parent.art.dialog.list['aa'].close()</script>";
            exit();
        }
        
        $this->assign('id', $id);
        $this->display();
    }
    
    // 下载手机号黑名单
    public function downPhone() {
        $batch_id = I('batch_id', null);
        $data = array(
            'node_id' => $this->nodeId, 
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $batch_id, 
            'type' => '2');
        $query_arr = M('tfb_phone')->field('mobile')
            ->where($data)
            ->select();
        if (! $query_arr) {
            $this->error('未导入黑名单！');
        }
        
        $fileName = '手机号.csv';
        $fileName = iconv('utf-8', 'gbk', $fileName);
        
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        $cj_title = "手机号\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        foreach ($query_arr as $v) {
            echo $v['mobile'] . "\r\n";
        }
    }

    /**
     * 市场营销统计展示
     */
    public function info() {
        $batch_id = I('batch_id', 0);
        $model = M('tmarketing_info');
        $map = array(
            'batch_type' => $this->BATCH_TYPE, 
            'id' => $batch_id, 
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        $info = $model->where($map)->find();
        
        $result = M('tquestion')->where(
            array(
                'label_id' => $batch_id, 
                'type' => array(
                    'in', 
                    '1,2')))
            ->order("sort")
            ->select();
        
        foreach ($result as $k => $v) {
            $answers_info = M('tquestion_info')->where(
                array(
                    "question_id" => $v['id']))->select();
            if ($answers_info !== false) {
                $result[$k]['answers_list'] = $answers_info;
            }
            $ask_info = M('tquestion_stat')->where(
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
        $this->display();
    }
}
