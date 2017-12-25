<?php

/**
 * 谁是大腕 特殊说明 defined_one_name 字段为 旺币赠送状态 0不送 1失败 2成功 @auther tr
 */
class DawanAction extends BaseAction {

    const BATCH_TYPE_DAWAN = 44;
    // 活动类型，谁是大腕
    
    // 活动类型
    public $BATCH_TYPE;

    public $BATCH_NAME;
    
    // 初始化
    public function _initialize() {
        $this->BATCH_TYPE = self::BATCH_TYPE_DAWAN;
        $this->BATCH_NAME = C('BATCH_TYPE_NAME.' . self::BATCH_TYPE_DAWAN);
        parent::_initialize();
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
        node_log("谁是大腕儿");
        $arr_ = C('CHANNEL_TYPE');
        $this->assign('is_cj_button', '1');
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }
    
    // 添加页
    public function add() {
        // 获取商户名称
        $nodeInfo = get_node_info($this->node_id);
        $nodeName = $nodeInfo['node_name'];
        $this->assign('node_name', $nodeName);
        $this->assign('row', 
            array(
                'name' => $this->BATCH_NAME));
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
            'color' => get_val($data['color']), 
            'wap_title' => $data['wap_title'], 
            'log_img' => $result['log_img'], 
            'music' => get_val($data['resp_music']), 
            'video_url' => get_val($data['video_url']), 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'memo' => get_val($data['memo']), 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            // 'size'=>'1',
            // 'code_img'=>'',
            'sns_type' => $result['sns_type'], 
            'other_url' => get_val($data['other_url']), 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_cj' => '0', 
            'page_style' => get_val($data['page_style']), 
            'bg_style' => get_val($data['bg_style']), 
            'bg_pic' => $result['bg_img'], 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1');
        $resp_id = $model->add($data_arr);
        if (! $resp_id) {
            $this->error('系统错误！', 
                array(
                    '返回活动列表' => U('index')));
        }
        // 顺便发布到多乐互动专用渠道上
        $bchId = D('MarketCommon')->chPublish($this->nodeId,$resp_id);
        if($bchId === false){
            $this->error('发布到渠道失败');
        }
        $ser = D('TmarketingInfo');
        $arr = array(
            'node_id' => $this->nodeId, 
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $resp_id);
        $ser->init($arr);
        $ser->sendBatch();
        
        node_log($this->BATCH_NAME . '添加|活动名:' . $data['name']);
        $this->assign('lookUrl', $resp_id);
        $this->success('添加成功！', 
            array(
                '设置抽奖' => U('cjset', 
                    array(
                        'batch_id' => $resp_id)), 
                '发布更多渠道' => U('LabelAdmin/BindChannel/index',
                    array(
                        'batch_type' => $this->BATCH_TYPE, 
                        'batch_id' => $resp_id)), 
                '返回活动列表页' => U('MarketActive/Activity/MarketList')));
    }
    
    // 编辑页
    public function edit() {
        $model = M('tmarketing_info');
        $id = I('get.id');
        if (empty($id))
            $this->error('错误参错');
        
        $map = array(
            'node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"), 
            'id' => $id, 
            'batch_type' => $this->BATCH_TYPE);
        $query_arr = $model->where($map)->find();
        if (! $query_arr)
            $this->error('未查询到数据！');
        
        $this->assign('node_name', $query_arr['node_name']);
        $this->assign('row', $query_arr);
        $this->assign('send_wb_flag', 0);
        
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
        
        $this->display('add');
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
        $edit_wh = array(
            'node_id' => $this->node_id, 
            'id' => $data['id'], 
            'batch_type' => $this->BATCH_TYPE);
        $info = $model->where($edit_wh)->find();
        if (! $info)
            $this->error('未查询到记录');
        $node_id = $info['node_id'];
        $one_map = array_merge($edit_wh, 
            array(
                'name' => $data['name'], 
                'node_id' => $this->node_id, 
                'id' => array(
                    'neq', 
                    $data['id'])));
        $info_ = $model->where($one_map)->find();
        if ($info_) {
            $this->error("活动名称重复");
        }
        if (empty($data['wap_info'])) {
            $this->error('活动页面内容不能为空');
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
            'memo' => $data['memo'], 
            // 'size'=>'1',
            'sns_type' => $sns_type, 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_show' => '1');
        
        $log_img = $data['resp_log_img'];
        
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
        
        $execute = $model->where($map)->save($data_arr);
        if ($execute === false)
            $this->error('系统错误！', 
                array(
                    '返回活动列表' => U('index')));
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

    public function cjset() {
        $batch_id = I('batch_id', NULL, 'trim');
        if (empty($batch_id))
            $this->error('活动id不能为空！');
            
            // 校验活动
        $query_arr = M('tmarketing_info')->field(
            'id,cj_phone_type,batch_type,defined_one_name,config_data')
            ->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $batch_id, 
                'batch_type' => self::BATCH_TYPE_DAWAN))
            ->find();
        if (! $query_arr)
            $this->error('参数错误！');
        
        $isShowCjButton = true;
        
        // 未设置抽奖规则默认写入
        $cj_rule_arr = M('tcj_rule')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $batch_id, 
                'status' => '1'))->find();
        if (! $cj_rule_arr) {
            $in_arr = array(
                'node_id' => $this->node_id, 
                'batch_type' => $query_arr['batch_type'], 
                'batch_id' => $batch_id, 
                'add_time' => date('YmdHis'), 
                'total_chance' => 100, 
                'phone_total_count' => 0, 
                'phone_day_count' => 0, 
                'phone_total_part' => 0, 
                'phone_day_part' => 0);
            $insert = M('tcj_rule')->add($in_arr);
            if (! $insert) {
                $this->error('操作失败！');
            }
            $cj_rule_arr = M('tcj_rule')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_id' => $batch_id, 
                    'status' => '1'))->find();
        }
        // 奖项
        $cj_cate_arr = M('tcj_cate')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_id' => $batch_id, 
                'cj_rule_id' => $cj_rule_arr['id']))->select();
        // 默认插入奖项
        if (! $cj_cate_arr) {
            $default_arr = array(
                '1' => array(
                    'name' => '一等奖', 
                    'min_rank' => 1, 
                    'max_rank' => 10), 
                '2' => array(
                    'name' => '二等奖', 
                    'min_rank' => 11, 
                    'max_rank' => 20), 
                '3' => array(
                    'name' => '三等奖', 
                    'min_rank' => 21, 
                    'max_rank' => 30), 
                '4' => array(
                    'name' => '四等奖', 
                    'min_rank' => 31, 
                    'max_rank' => 40), 
                '5' => array(
                    'name' => '五等奖', 
                    'min_rank' => 41, 
                    'max_rank' => 50), 
                '6' => array(
                    'name' => '参与奖', 
                    'min_rank' => 51, 
                    'max_rank' => 60));
            $cate_in = array(
                'node_id' => $this->node_id, 
                'batch_type' => $query_arr['batch_type'], 
                'batch_id' => $batch_id, 
                'cj_rule_id' => $cj_rule_arr ? $cj_rule_arr['id'] : $insert, 
                'add_time' => date('YmdHis'));
            foreach ($default_arr as $d) {
                $cate_in['name'] = $d['name'];
                $cate_in['score'] = 0;
                $cate_in['min_rank'] = $d['min_rank'];
                $cate_in['max_rank'] = $d['max_rank'];
                $cate_default = M('tcj_cate')->add($cate_in);
            }
            
            $cj_cate_arr = M('tcj_cate')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_id' => $batch_id, 
                    'cj_rule_id' => $cj_rule_arr['id']))->select();
        }
        $config_data = $query_arr['config_data'];
        $config_data = unserialize($config_data);
        $store_list = $config_data['store_list'];
        /*
         * $store_list = M('tstore_info')->where(array( 'store_id' =>
         * array('in', $store_list)
         * ))->field("store_id,store_short_name")->select();
         */
        $store_flag = $store_list ? 1 : 0;
        $store_num = $store_list ? count(explode(',', $store_list)) : 0;
        
        $rule_num = max(count($config_data['rule_detail']), 1);
        $this->assign('rule_detail', $config_data['rule_detail']);
        $this->assign('location_flag', $config_data['location_flag']);
        $this->assign('rule_num', $rule_num);
        $this->assign('store_list', $store_list);
        $this->assign('store_flag', $store_flag);
        $this->assign('store_num', $store_num);
        
        // 奖品
        $jp_arr = M()->table('tcj_batch a')
            ->field('a.*,b.batch_name,c.online_verify_flag')
            ->join('tbatch_info b on a.b_id=b.id')
            ->join('tgoods_info c on c.goods_id = a.goods_id')
            ->where(
            "a.node_id='" . $this->node_id . "' and a.batch_id='" . $batch_id .
                 "'")
            ->select();
        $this->assign('batch_type', $query_arr['batch_type']);
        $this->assign('cj_phone_type', $query_arr['cj_phone_type']);
        $this->assign('jp_arr', $jp_arr);
        $this->assign('cj_cate_arr', $cj_cate_arr);
        $this->assign('cj_rule_arr', $cj_rule_arr);
        $this->assign('batch_id', $batch_id);
        $this->assign('isShowCjButton', $isShowCjButton);
        $this->assign('defined_one_name', $query_arr['defined_one_name']);
        
        $userInfo = $this->userInfo;
        $yz_url = C('YZ_RECHARGE_URL') . '&' . http_build_query(
            array(
                'node_id' => $userInfo['node_id'], 
                'name' => $userInfo['user_name'], 
                'token' => $userInfo['token']));
        $this->assign('yz_url', $yz_url);
        
        $this->display();
    }
    
    // 奖品类型
    public function jpType() {
        $cj_cate_id = I('cj_cate_id', NULL, 'trim');
        $batch_id = I('batch_id', NULL, 'trim');
        if (empty($batch_id))
            $this->error('活动id不能为空！');
        
        $cate_arr = array();
        if ($cj_cate_id) {
            $cate_arr = M('tcj_cate')->where(
                array(
                    'node_id' => $this->node_id, 
                    'id' => $cj_cate_id, 
                    'batch_id' => $batch_id))->find();
        }
        $this->assign('cate_arr', $cate_arr);
        $this->assign('cj_cate_id', $cj_cate_id);
        $this->assign('batch_id', $batch_id);
        $this->display();
    }
    
    // 抽奖设置
    public function cjsetSubmit() {
        $rule_detail = I('rule_detail', array(), '');
        $location_flag = I('location_flag', '0');
        $store_list = I('store_list', '');
        $batch_id = I('batch_id');
        if (! $batch_id) {
            $this->error("活动号不能为空");
        }
        // 校验有效性
        if (! $rule_detail || count($rule_detail) == 0) {
            $this->error("活动规则不能为空");
        }
        // 校验规则有效性
        if (! $this->_checkRule($rule_detail)) {
            return $this->error("规则有误");
        }
        foreach ($rule_detail as &$v) {
            if (empty($v['batch_number'])) {
                $batch_number = D('TsysSequence')->getNextSeq(
                    'twx_dawan_batch_number');
                if (! $batch_number) {
                    $this->error("系统正忙，获取 seq.twx_dawan_batch_number 失败");
                }
                $v['batch_number'] = $batch_number;
            }
        }
        unset($v);
        
        $model = M('tmarketing_info');
        $map = array(
            'node_id' => $this->node_id, 
            'batch_type' => $this->BATCH_TYPE, 
            'id' => $batch_id);
        $info = $model->where($map)->find();
        if (! $info) {
            $this->error("活动不存在");
        }
        
        $_data = array(
            'rule_detail' => $rule_detail, 
            'location_flag' => $location_flag, 
            'store_list' => $store_list);
        $config_data = serialize($_data);
        $data = array(
            'config_data' => $config_data);
        $result = $model->where($map)->save($data);
        if ($result === false) {
            $this->error("更新失败,系统正忙");
        }
        $this->success("成功");
    }

    protected function _checkRule($rule_detail) {
        foreach ($rule_detail as $k => $v) {
            if ($v['rule_begin_time'] >= $v['rule_end_time']) {
                $this->error("第 " . ($k + 1) . " 场结束时间必须大于开始时间");
            }
            $_rule_detail = $rule_detail;
            unset($_rule_detail[$k]);
            foreach ($_rule_detail as $kk => $vv) {
                if (($v['rule_begin_time'] >= $vv['rule_begin_time'] &&
                     $v['rule_begin_time'] <= $vv['rule_end_time']) || ($v['rule_end_time'] >=
                     $vv['rule_begin_time'] &&
                     $v['rule_end_time'] <= $vv['rule_end_time'])) {
                    $this->error(
                        "第 " . ($k + 1) . " 场时间:" . $v['rule_begin_time'] . '-' .
                         $v['rule_end_time'] . '与第' . ($kk + 1) . '场 ' .
                         $vv['rule_begin_time'] . '-' . $vv['rule_end_time'] .
                         '冲突');
                }
            }
        }
        return true;
    }
}
