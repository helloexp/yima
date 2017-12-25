<?php

/**
 * @ 功能：拼字 @ 作者：zhengxd
 */
class SpellingAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE = '36';
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
        
        node_log("首页+拼字赢大奖");
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
        // edit by tr
        $this->offlineactivitynotice();
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        $this->display();
    }
    
    // 添加提交页
    public function addSubmit() {
        // edit by tr
        $this->offlineactivitynotice();
        $data = I('post.');
        
        if (empty($data['name']))
            $this->error('请填写活动名称！');
        if (empty($data['wap_title']))
            $this->error('请填写wap页面标题！');
        if (empty($data['start_time']))
            $this->error('请填写标签可用开始时间！');
        if (empty($data['end_time']))
            $this->error('请填写标签可用结束时间！');
        
        $hanzi_info = array_filter($data['zi_']);
        
        if (count($hanzi_info) < 3)
            $this->error('拼字配置长度不能少于3个字！');
        foreach ($hanzi_info as $key => $value) {
            if (preg_match("/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u", $value) == '0') {
                $this->error('不能输入特殊字符');
                break;
            }
            ;
        }
        
        if ($this->repeat($hanzi_info) === true)
            $this->error('拼字配置,最多允许有2个重复的字！！！');
        $hanzi = implode('-', $data['zi_']);
        $arr_diff = array_count_values($hanzi_info);
        $arr_diff_ = array_count_values($arr_diff);
        
        foreach ($arr_diff_ as $k => $v) {
            if ($k == 2 && $v > 1) {
                $this->error('不可配置两组或两组以上相同的文字，如“高高兴兴”、“你你我我他他”');
            }
        }
        
        $baseActivityModel = D('BaseActivity', 'Service');
        if (! empty($data['sns_type'])) {
            $sns_type = $baseActivityModel->implodearray($data['sns_type']);
        } else {
            $sns_type = '';
        }
        
        $model = M('tmarketing_info');
        $this->checkisactivitynamesame($data['name'], $this->BATCH_TYPE);
        // logo
        if ($data['resp_log_img'] != '' && $data['is_logo_img'] == 1) {
            $log_img = $data['resp_log_img'];
        }
        // 背景
        if ($data['resp_bg_img'] != '') {
            // $bg_img =
            // $baseActivityModel->activitybackgroundpicupload($data['resp_bg_img'],
            // $this->BATCH_TYPE, $this->img_path);
            $bg_img = $data['resp_bg_img'];
        }
        
        $range = $data['range'];
        $data_arr = array(
            'name' => $data['name'], 
            'color' => $data['color'], 
            'wap_title' => $data['wap_title'], 
            'log_img' => $log_img, 
            'music' => $data['resp_music'], 
            'video_url' => $data['video_url'], 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'memo' => $data['memo'], 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            // 'size'=>$data['size'],
            // 'code_img'=>$data['resp_code_img'],
            'sns_type' => $sns_type, 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_cj' => '1', 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $bg_img, 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '0', 
            'defined_one_name' => $hanzi, 
            'defined_two_name' => $range, 
            'batch_come_from' => session('batch_from') ? session('batch_from') : '1');
        
        M()->startTrans();
        $resp_id = $model->add($data_arr);
        if (! $resp_id) {
            M()->rollback();
            $this->error('系统错误！', 
                array(
                    '返回拼字活动' => U('index')));
        }
        // $ser = D('TmarketingInfo');
        // $arr = array(
        // 'node_id'=>$this->nodeId,
        // 'batch_type'=>$this->BATCH_TYPE,
        // 'batch_id'=>$resp_id,
        // );
        // $ser->init($arr);
        // $ser->sendBatch();
        $res = $this->tcj_rule($resp_id);
        if (! $res) {
            M()->rollback();
        }
        M()->commit();
        // 自动发布到在线活动
        node_log('拼字活动添加|活动名:' . $data['name']);
        // 顺便发布到多乐互动专用渠道上
        $bchId = D('MarketCommon')->chPublish($this->nodeId,$resp_id);
        if($bchId === false){
            $this->error('发布到渠道失败');
        }
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
        // edit by tr
        $this->offlineactivitynotice();
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
        
        $arr = explode('-', $query_arr['defined_one_name']);
        $BindChannel = M('tbatch_channel')->where(
            array(
                'batch_id' => $id))->find();
        $this->assign('int', $BindChannel);
        $this->assign('row', $query_arr);
        $this->assign('arr', $arr);
        $this->display();
    }
    
    // 编辑提交页
    public function editSubmit() {
        // edit by tr
        $this->offlineactivitynotice();
        $model = M('tmarketing_info');
        $data = I('post.');
        
        if (empty($data['name']) || empty($data['id']))
            $this->error('请填写活动名！');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        if (! empty($data['sns_type'])) {
            $sns_type = $baseActivityModel->implodearray($data['sns_type']);
        } else {
            $sns_type = '';
        }
        
        $query_arr = $baseActivityModel->checkactivityexist($data['id'], 
            $this->BATCH_TYPE, $this->nodeIn());
        $node_id = $query_arr['node_id'];
        $this->checkisactivitynamesame($data['name'], $this->BATCH_TYPE, 
            $data['id']);
        
        // 背景图
        if ($data['resp_bg_img'] != '' && $data['reset_bg'] == '1') {
            // $bg_img =
            // $baseActivityModel->activitybackgroundpicupload($data['resp_bg_img'],$this->BATCH_TYPE,
            // $this->img_path);
            // }else {
            $bg_img = $data['resp_bg_img'];
        }
        
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
            // 'size'=>$data['size'],
            'sns_type' => $sns_type, 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $bg_img);
        
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
                    '返回拼字活动' => U('index')));
        
        node_log('拼字编辑|活动id:' . $data['id']);
        $this->success('更新成功', 
            array(
                '返回拼字活动' => U('MarketActive/Activity/MarketList')));
    }
    
    // 状态修改
    public function editStatus() {
        $data = I('post.');
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($result) {
            node_log('拼字活动状态更改|活动id:' . $data['batch_id']);
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
    
    // 活动创建完成自动创建抽奖RULE
    public function tcj_rule($max) {
        $data = array(
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $max, 
            'jp_set_type' => 1, 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            'total_chance' => 100, 
            'phone_total_count' => 0, 
            'phone_day_count' => 0, 
            'phone_total_part' => 0, 
            'phone_day_part' => 0);
        $flag = M('tcj_rule')->add($data);
        return $flag;
    }

    public function repeat($arr) {
        $new_arr = array_count_values($arr);
        foreach ($new_arr as $v) {
            if ($v > 2) {
                return true;
            }
        }
    }

    /**
     * 以下代码与逻辑没有任何联系，只是用于测试
     */
    public function test_() {
        dump(get_magic_quotes_runtime());
        dump(get_magic_quotes_gpc());
        dump(ini_get('magic_quotes_sybase'));
    }
}
