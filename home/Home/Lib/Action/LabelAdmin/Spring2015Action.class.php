<?php

/**
 * 春节打炮活动 特殊说明 defined_one_name 字段为 旺币赠送状态 0不送 1失败 2成功 @auther tr
 */
class Spring2015Action extends BaseAction {
    // public $_authAccessMap = '*';
    const BATCH_TYPE_SPRING = 42;
    // 活动类型
    const DEFAULT_COMMUNITY_ID = 1;
    // 默认炮区号
    
    // 活动类型
    public $BATCH_TYPE;

    public $BATCH_NAME;
    // 图片路径
    public $img_path;
    // 旺币有效期，旺币值
    public $wbValidDate = '20150331';

    public $wbNum = 200;
    
    // 初始化
    public function _initialize() {
        $this->BATCH_TYPE = self::BATCH_TYPE_SPRING;
        $this->BATCH_NAME = C('BATCH_TYPE_NAME.' . self::BATCH_TYPE_SPRING);
        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
        $this->assign('batch_type', $this->BATCH_TYPE);
        $this->assign('batch_name', $this->BATCH_NAME);
    }
    
    // 首页
    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'batch_type' => $this->BATCH_TYPE);
        
        $_GET['node_id'] = I('node_id');
        if ($_GET['node_id'] != '') {
            $map['node_id '] = $_GET['node_id'];
        }
        $_GET['key'] = I('key');
        if ($_GET['key'] != '') {
            $map['name'] = array(
                'like', 
                '%' . $_GET['key'] . '%');
        }
        
        $_GET['status'] = I('status');
        if ($_GET['status'] != '') {
            $map['status'] = $_GET['status'];
        }
        $_GET['start_time'] = I('start_time');
        $_GET['end_time'] = I('end_time');
        if ($_GET['start_time'] != '' && $_GET['end_time'] != '') {
            $map['add_time'] = array(
                'BETWEEN', 
                array(
                    $_GET['start_time'] . '000000', 
                    $_GET['end_time'] . '235959'));
        }
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = $model->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
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
        node_log("首页+打炮总动员");
        
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
        // edit by tr
        $this->offlineactivitynotice();
        
        $model = M('tmarketing_info');
        $one_map = array(
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("只允许创建一个活动");
        }
        
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        
        $this->assign('row', 
            array(
                'name' => $this->BATCH_NAME));
        
        $send_wb_flag = 1;
        $wc_version = get_wc_version($this->node_id);
        // 只能赠送给c0,c1用户
        if ($wc_version != 'v0' && $wc_version != 'v0.5') {
            $send_wb_flag = 0;
        }
        $this->assign('send_wb_flag', $send_wb_flag);
        $this->display();
    }
    
    // 添加提交页
    public function addSubmit() {
        // edit by tr
        $this->offlineactivitynotice();
        $model = M('tmarketing_info');
        $data = I('post.');
        
        $one_map = array(
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("只允许创建一个活动");
        }
        
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
                    '返回活动列表' => U('index')));
        }
        
        $ser = D('TmarketingInfo');
        $arr = array(
            'node_id' => $this->nodeId, 
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $resp_id);
        $ser->init($arr);
        $ser->sendBatch();
        
        // 完成赠送任务
        $wbResult = $this->_sendWcMoney($this->wbNum);
        if ($wbResult === false) {
            // 更新状态
            $model->where(array(
                'id' => $resp_id))->save(
                array(
                    'defined_one_name' => '0'));
        } elseif ($wbResult['code'] != 0) {
            // 更新状态
            $model->where(array(
                'id' => $resp_id))->save(
                array(
                    'defined_one_name' => '1'));
            log_write('赠送旺币失败' . print_r($wbResult, true));
        } else {
            // 更新状态
            $model->where(array(
                'id' => $resp_id))->save(
                array(
                    'defined_one_name' => '2'));
            log_write('赠送旺币成功');
        }
        
        node_log($this->BATCH_NAME . '添加|活动名:' . $data['name']);
        
        // 加入默炮区
        $this->_addCommunity(self::DEFAULT_COMMUNITY_ID, $this->nodeInfo);
        // 顺便发布到多乐互动专用渠道上
        $bchId = D('MarketCommon')->chPublish($this->nodeId,$resp_id);
        if($bchId === false){
            $this->error('发布到渠道失败');
        }
        $this->success('添加成功！', 
            array(
                '设置抽奖' => U('LabelAdmin/Spring2015CjSet/index', 
                    array(
                        'batch_id' => $resp_id)), 
                '发布到更多渠道' => U('LabelAdmin/BindChannel/index',
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
            $this->BATCH_TYPE, $this->noneIn());
        
        if ($query_arr['log_img'] == '') {
            $nodeInfo = get_node_info($this->node_id);
            $this->assign('head_photo', get_upload_url($nodeInfo['head_photo']));
            $this->assign('node_logo', $nodeInfo['head_photo']);
        }
        
        $this->assign('node_name', $query_arr['node_name']);
        $this->assign('row', $query_arr);
        $this->assign('send_wb_flag', 0);
        $this->display('add');
    }
    
    // 编辑提交页
    public function editSubmit() {
        // edit by tr
        $this->offlineactivitynotice();
        
        $model = M('tmarketing_info');
        $data = I('post.');
        
        if (empty($data['name']) || empty($data['id']))
            $this->error('请填写活动名！');
        
        $this->checkisactivityheadinfowrite($data);
        
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
                    '返回活动列表' => U('index')));
            
            // 完成赠送任务
            // 如果原来赠送失败了，再来一次
        $id = $info['id'];
        if ($info['defined_one_name'] == '1') {
            $wbResult = $this->_sendWcMoney($this->wbNum);
            if ($wbResult === false) {
                // 更新状态
                $model->where(array(
                    'id' => $id))->save(
                    array(
                        'defined_one_name' => '0'));
            } elseif ($wbResult['code'] != 0) {
                // 更新状态
                $model->where(array(
                    'id' => $id))->save(
                    array(
                        'defined_one_name' => '1'));
                log_write('赠送旺币失败' . print_r($wbResult, true));
            } else {
                // 更新状态
                $model->where(array(
                    'id' => $id))->save(
                    array(
                        'defined_one_name' => '2'));
                log_write('赠送旺币成功');
            }
        }
        
        node_log($this->BATCH_NAME . '编辑|活动id:' . $data['id']);
        redirect(
            U('MarketActive/Activity/MarketList'));
        // $this->success('更新成功', array('返回活动列表'=>U('index')));
    }
    
    // 状态修改
    public function editStatus() {
        $data = I('post.');
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($result) {
            node_log($this->BATCH_NAME . '状态更改|活动id:' . $data['batch_id']);
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

    /**
     * 送旺币,一定要private
     *
     * @return array
     */
    private function _sendWcMoney($num) {
        $node_id = $this->node_id;
        $wc_version = get_wc_version($node_id);
        // 只能赠送给c0,c1用户
        if ($wc_version != 'v0' && $wc_version != 'v0.5') {
            log_write($this->node_id . '[' . $wc_version . ']机构不赠送旺币');
            return false;
        }
        // 开始赠送旺币
        if ($num) {
            $service = D('RemoteRequest', 'Service');
            $TransactionID = date('ymdHis') . mt_rand(1000, 9999);
            $SystemID = C('YZ_SYSTEM_ID');
            $BeginTime = date('Ymd');
            $EndTime = $this->wbValidDate;
            $nodeInfo = M('tnode_info')->where(
                array(
                    'node_id' => $this->node_id))->find();
            $data = array(
                'SystemID' => $SystemID, 
                'TransactionID' => $TransactionID, 
                'ContractID' => $nodeInfo['contract_no'], 
                'WbType' => '1', 
                'BeginTime' => $BeginTime, 
                'EndTime' => $EndTime, 
                'ReasonID' => 27,  // 27是赠送
                'Amount' => 0, 
                'WbNumber' => $num, 
                'Remark' => '打炮总动员');
            $data = array(
                'SetWbReq' => $data);
            $yzResult = $service->requestYzServ($data);
            if (! $yzResult || ! $yzResult['Status']) {
                return array(
                    'code' => '9', 
                    'msg' => '赠送旺币失败,网络正忙[01]');
            }
            if ($yzResult['Status']['StatusCode'] != '0') {
                return array(
                    'code' => '9', 
                    'msg' => '赠送旺币失败,原因：' . $yzResult['Status']['StatusText']);
            }
            return array(
                'code' => '0', 
                'msg' => '<h2>已赠送您' . $num . '旺币</h2>' . '<p>有效期为' .
                     dateformat($BeginTime, 'Y-m-d') . '到' .
                     dateformat($EndTime, 'Y-m-d') . '<br/>' .
                     '可以在<a href="index.php?g=Home&m=AccountInfo&a=index">个人帐户中心</a>查看并使用</p>');
        }
        return array(
            'code' => 1, 
            'msg' => "未送旺币");
    }

    /*
     * 加入默认炮区
     */
    protected function _addCommunity($community_id, $node_info) {
        $node_id = M('twx_firecrackers_community')->where(
            array(
                'id' => $community_id))->getField('node_id');
        if (! $node_id) {
            log_write("未创建默认炮区:" . $community_id);
            return true;
        }
        // 默认加到系统炮区
        $result = M('twx_firecrackers_community_relation')->add(
            array(
                'community_id' => $community_id, 
                'node_id' => $node_id, 
                'add_time' => date('YmdHis'), 
                'join_node_id' => $node_info['node_id'], 
                'contact_name' => $node_info['contact_name'], 
                'contact_mobile' => $node_info['contact_phone']));
        if (! $result) {
            log_write(
                "加入默认炮区失败:[sql]" . M()->_sql() . "[error]" . M()->getDbError());
        }
        return $result;
    }
}
