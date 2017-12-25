<?php

/* 签到有礼竞猜 */
class DakaHasGiftAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE = '23';
    // 图片路径
    public $img_path;
    // 是否允许等级多奖品
    public $mg_flag;
    
    // 初始化
    public function _initialize() {
        parent::_initialize();
        $_GET['batch_type'] = I('get.batch_type');
        $this->assign('batch_type', $this->BATCH_TYPE);
        
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

    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            // 'node_id'=>array('exp',"in (".$this->nodeIn().")"),
            'node_id' => $this->node_id, 
            'batch_type' => $this->BATCH_TYPE);
        
        $_GET['node_id'] = I('node_id');
        if (! empty($_GET['node_id']))
            $map['node_id '] = $_GET['node_id'];
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
        
        import('ORG.Util.Page');
        // 导入分页类
        $mapcount = $model->where($map)->count();
        // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10);
        // 实例化分页类 传入总记录数和每页显示的记录数
        
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
                // 统计签到有礼参与人数
                $list[$k]['join_num'] = M('tworld_cup_login_trace')->where(
                    "batch_id={$v['id']}")->count();
            }
        }
        
        node_log("签到有礼");
        $arr_ = C('CHANNEL_TYPE');
        $this->assign('is_cj_button', '1');
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show);
        // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display();
        // 输出模板
    }
    
    // 新增
    public function add() {
        $model = M('tmarketing_info');
        $one_map = array(
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id, 
            'status' => '1');
        $count = $model->where($one_map)->find();
        if ($count > 0) {
            $this->error("您当前已经有一个签到有礼活动正在进行中，如果需要创建新的活动替换现有活动，请先停用现有活动！");
        }
        
        // 获取商户名称
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        
        $nodeName = $nodeInfo['node_name'];
        node_log('签到有礼|商户名:' . $nodeName);
        
        $this->assign('is_cj_button', '1');
        $this->assign('row', 
            array(
                'name' => '签到有礼', 
                'wap_info' => '在2014巴西世界杯期间。参与签到有礼活动，就有机会获得精美奖品一份，连续签到，更有惊喜哦！', 
                'node_name' => $nodeName));
        $this->display();
    }
    
    // 新增提交
    public function addSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        
        // 初始化默认参数
        
        $data['start_time'] = date('YmdHis');
        $data['end_time'] = C('WORLDCUP_OVER_TIME');
        
        // 奖品设置
        $data['jp_type'] = '0';
        $data['chance'] = '0';
        $data['day_goods_count'] = '0';
        
        if (empty($data['name']))
            $this->error('请填写活动名称！');
        
        $data['wap_title'] = $data['name'];
        if (empty($data['wap_title']))
            $this->error('请填写wap页面标题！');
            // if (empty($data['start_time'])) $this->error('请填写标签可用开始时间！');
            // if (empty($data['end_time'])) $this->error('请填写标签可用结束时间！');
        if (empty($data['wap_info']))
            $this->error('活动页面内容不能为空');
            
            // if ($data['is_cj'] != '1') {
            // $this->error('抽奖设置不能为空');
            // }
        
        if (! empty($data['sns_type'])) {
            $sns_type = implode('-', $data['sns_type']);
        } else {
            $sns_type = '';
        }
        $one_map = array(
            'name' => $data['name'], 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        $info = $model->where($one_map)->count('id');
        if ($info > 0) {
            $this->error("活动名称重复");
        }
        
        // 签到有礼是否重复
        $one_map = array(
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id, 
            'status' => '1');
        $count = $model->where($one_map)->find();
        if ($count > 0) {
            $this->error("您当前已经有一个签到有礼活动正在进行中，如果需要创建新的活动替换现有活动，请先停用现有活动！");
        }
        
        $ruledata = json_decode($data['batchjson'], true);
        
        // 签到不允许超过25个规则
        $total_num = sizeof($ruledata);
        if ($total_num > 25)
            $this->error("不允许超过25个规则");
            
            // logo
        if ($data['resp_log_img'] != '' && $data['is_logo_img'] == 1) {
            $log_img = $data['resp_log_img'];
        }
        
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
            'size' => $data['size'], 
            'code_img' => $data['resp_code_img'], 
            'sns_type' => $sns_type, 
            'other_url' => $data['other_url'], 
            'wap_info' => $data['wap_info'], 
            'node_name' => $data['node_name'], 
            'is_cj' => '1', 
            'page_style' => $data['page_style'], 
            'bg_style' => $data['bg_style'], 
            'bg_pic' => $data['resp_bg_img'], 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1');
        
        // 开启事物
        $tranDb = M();
        $tranDb->startTrans();
        
        $resp_id = $model->add($data_arr);
        if (! $resp_id) {
            $tranDb->rollback();
            $this->error('系统错误！[01]', 
                array(
                    '返回签到有礼' => U('index')));
        }
        
        $data['jp_type'] = '2';
        
        $check_days_arr = array();
        
        $cjrulem = M('tcj_rule');
        $cjbatchm = M('tcj_batch');
        foreach ($ruledata as $v) {
            $check_days = $v['check_days'];
            if (in_array($check_days, $check_days_arr)) {
                $tranDb->rollback();
                $this->error('签到连续天数【' . $check_days . '】存在重复]');
            }
            $check_days_arr[] = $check_days;
            
            // 更新奖品规则
            $cdata = array(
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $resp_id, 
                'jp_set_type' => 2, 
                'day_count' => 1, 
                'total_chance' => $v['award_rate'], 
                'node_id' => $this->node_id, 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                'phone_total_count' => 0, 
                'phone_day_count' => 1, 
                'phone_total_part' => 0, 
                'phone_day_part' => 0, 
                'param1' => $check_days);
            $rulequery = $cjrulem->add($cdata);
            if (! $rulequery) {
                $tranDb->rollback();
                $this->error('系统错误！[02]', 
                    array(
                        '返回签到有礼' => U('index')));
            }
            
            foreach ($v['list'] as $vv) {
                $bdata = array(
                    'batch_id' => $resp_id, 
                    'node_id' => $this->node_id, 
                    'award_origin' => 2, 
                    'activity_no' => $vv['batch_no'], 
                    'award_level' => '1', 
                    'award_rate' => $vv['goods_count'], 
                    'total_count' => $vv['goods_count'], 
                    'day_count' => $vv['day_count'], 
                    'batch_type' => $this->BATCH_TYPE, 
                    'cj_rule_id' => $rulequery);
                
                if (in_array('', $bdata)) {
                    $tranDb->rollback();
                    $this->error('奖品规则设置错误！[01]');
                }
                if ($vv['day_count'] > $vv['goods_count']) {
                    $tranDb->rollback();
                    $this->error('每日奖品限量不能大于总奖品数！');
                }
                $bquery = $cjbatchm->add($bdata);
                
                if (! $bquery) {
                    $tranDb->rollback();
                    $this->error('奖品设置错误！');
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
        node_log('签到有礼|活动名:' . $data['name']);
        $this->success('添加成功！', 
            array(
                '返回签到有礼' => U('index'), 
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
        $where = array(
            'a.batch_id' => $id, 
            'a.status' => '1');
        /*
         * $cjarr = M('tcj_rule a') ->join('tcj_batch b on a.batch_id =
         * b.batch_id and b.cj_rule_id = a.id') ->join('tbatch_info c on
         * c.batch_no = b.activity_no') ->where($where) ->field('b.id
         * cj_batch_id, a.total_chance, b.activity_no, b.award_rate,
         * b.total_count, b.day_count, c.batch_short_name') ->select();
         */
        $list = M()->table('tcj_rule a, tcj_batch b, tbatch_info c')
            ->where(
            "a.batch_id = '$id' and a.status = '1' and a.batch_id = b.batch_id and b.cj_rule_id = a.id and c.batch_no = b.activity_no")
            ->order("a.total_chance asc, b.id asc")
            ->field(
            'b.id cj_batch_id, a.id cj_rule_id, b.activity_no, a.param1 as check_days, b.activity_no, a.total_chance as award_rate, b.total_count, b.day_count, c.batch_short_name')
            ->select();
        
        $cjarr = array();
        foreach ($list as $info) {
            if (! isset($cjarr[$info['check_days']])) {
                $check_days = $info['check_days'];
                $cjarr[$check_days] = array(
                    'check_days' => $check_days, 
                    'award_rate' => $info['award_rate'], 
                    'cj_rule_id' => $info['cj_rule_id'], 
                    'list' => array());
            }
            
            $cjarr[$check_days]['list'][] = array(
                'batch_no' => $info['activity_no'], 
                'goods_name' => $info['batch_short_name'], 
                'total_count' => $info['total_count'], 
                'cj_batch_id' => $info['cj_batch_id'], 
                'day_count' => $info['day_count']);
        }
        
        $this->assign('cjarr', $cjarr);
        $this->assign('row', $query_arr);
        $this->display('add');
    }
    
    // 编辑提交页
    public function editSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        $data['is_cj'] = 1;
        
        // 初始化默认参数
        $data['start_time'] = date('YmdHis');
        $data['end_time'] = C('WORLDCUP_OVER_TIME');
        
        // 奖品设置
        if (empty($data['name']) || empty($data['id']))
            $this->error('请填写活动名！');
        
        if (! empty($data['sns_type'])) {
            $sns_type = implode('-', $data['sns_type']);
        } else {
            $sns_type = '';
        }
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $query_arr = $baseActivityModel->checkactivityexist($data['id'], 
            $this->BATCH_TYPE, $this->nodeIn());
        $node_id = $query_arr['node_id'];
        
        $ruledata = json_decode($data['batchjson'], true);
        // 签到不允许超过25个规则
        $total_num = sizeof($ruledata['new_list']);
        $count = M('tcj_rule')->where(
            array(
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $data['id']))->count();
        if (($count + $total_num) > 25)
            $this->error("不允许超过25个签到天数");
        
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
        
        M()->startTrans();
        $execute = $model->where($map)->save($data_arr);
        
        if ($execute === false) {
            M()->rollback();
            $this->error('系统错误！', 
                array(
                    '返回签到有礼' => U('index')));
        }
        
        // 更新奖品规则
        if ($data['is_reset'] == '2') {
            foreach ($ruledata['change_list'] as $v) {
                $rule_id = $v['cj_rule_id'];
                
                // 处理cj_rule表
                $info = M('tcj_rule')->where(
                    array(
                        'batch_id' => $data['id'], 
                        'id' => $rule_id))->find();
                
                if (! $info) {
                    M()->rollback();
                    $this->error('系统错误[01]！', 
                        array(
                            '返回签到有礼' => U('index')));
                }
                
                $rdata = array(
                    'total_chance' => $v['award_rate']);
                
                $flag = M('tcj_rule')->where(
                    array(
                        'id' => $rule_id))->save($rdata);
                if ($flag === false) {
                    M()->rollback();
                    $this->error('系统错误[02]！', 
                        array(
                            '返回签到有礼' => U('index')));
                }
                
                // 循环处理cj_batch表
                foreach ($v['list'] as $vv) {
                    $cj_batch_id = $vv['cj_batch_id'];
                    
                    $info = M('tcj_batch')->where(
                        array(
                            'batch_id' => $data['id'], 
                            'cj_rule_id' => $rule_id, 
                            'id' => $cj_batch_id))->find();
                    
                    if (! $info) {
                        M()->rollback();
                        $this->error('系统错误[01]！', 
                            array(
                                '返回签到有礼' => U('index')));
                    }
                    
                    // 总数
                    $map = array(
                        'rule_id' => $cj_batch_id);
                    $all_count = M('taward_daytimes')->where($map)->sum(
                        'award_times');
                    if ($all_count > $vv['goods_count']) {
                        M()->rollback();
                        $this->error($info['award_level'] . '等奖日中奖数大于每日奖品限量！', 
                            array(
                                '返回签到有礼' => U('index')));
                    }
                    
                    // 每日
                    $map['trans_date'] = date('Ymd');
                    $day_count = M('taward_daytimes')->where($map)->getField(
                        'award_times');
                    if ($day_count > $vv['day_count']) {
                        M()->rollback();
                        $this->error($info['award_level'] . '等奖已中奖数已大于奖品总数！', 
                            array(
                                '返回签到有礼' => U('index')));
                    }
                    
                    $batchdata = array(
                        'total_count' => $vv['goods_count'], 
                        'day_count' => $vv['day_count'], 
                        'award_rate' => $vv['goods_count']);
                    
                    $flag = M('tcj_batch')->where(
                        array(
                            'id' => $info['id']))->save($batchdata);
                    if ($flag === false) {
                        M()->rollback();
                        $this->error('系统错误[02]！', 
                            array(
                                '返回签到有礼' => U('index')));
                    }
                }
            }
        }
        
        $data['jp_type'] = '2';
        
        $check_days_arr = M('tcj_rule')->where(
            array(
                'batch_id' => $data['id']))->getField('total_chance', true);
        // 添加新规则
        if ($total_num > 0) {
            // for($i = 1; $i <= $total_num; $i++){
            foreach ($ruledata['new_list'] as $v) {
                $check_days = $v['check_days'];
                $award_rate = $v['award_rate'];
                
                if (in_array($check_days, $check_days_arr)) {
                    M()->rollback();
                    $this->error('签到连续天数【' . $check_days . '】存在重复]');
                }
                $check_days_arr[] = $check_days;
                
                // 添加奖品规则
                $cdata = array(
                    'batch_type' => $this->BATCH_TYPE, 
                    'batch_id' => $data['id'], 
                    'jp_set_type' => 2, 
                    'day_count' => 1, 
                    'total_chance' => $award_rate, 
                    'node_id' => $this->node_id, 
                    'add_time' => date('YmdHis'), 
                    'status' => '1', 
                    'phone_total_count' => 0, 
                    'phone_day_count' => 1, 
                    'phone_total_part' => 0, 
                    'phone_day_part' => 0, 
                    'param1' => $check_days);
                $rulequery = M('tcj_rule')->add($cdata);
                if (! $rulequery) {
                    M()->rollback();
                    $this->error('系统错误！[02]', 
                        array(
                            '返回签到有礼' => U('index')));
                }
                
                foreach ($v['list'] as $vv) {
                    $bdata = array(
                        'batch_id' => $data['id'], 
                        'node_id' => $this->node_id, 
                        'award_origin' => $data['jp_type'], 
                        'activity_no' => $vv['batch_no'],  // $data['wc_batch_no_'
                                                          // . $i],
                        'award_level' => '1', 
                        'award_rate' => $vv['goods_count'], 
                        'total_count' => $vv['goods_count'], 
                        'day_count' => $vv['day_count'], 
                        'batch_type' => $this->BATCH_TYPE, 
                        'cj_rule_id' => $rulequery);
                    
                    if (in_array('', $bdata)) {
                        M()->rollback();
                        $this->error('奖品规则设置错误！[01]');
                    }
                    if ($vv['day_count'] > $vv['goods_count']) {
                        M()->rollback();
                        $this->error('每日奖品限量不能大于总奖品数！');
                    }
                    $bquery = M('tcj_batch')->add($bdata);
                    
                    if (! $bquery) {
                        M()->rollback();
                        $this->error('奖品设置错误！');
                    }
                }
            }
        }
        M()->commit();
        
        node_log('签到有礼编辑|活动id:' . $data['id']);
        $this->success('更新成功', array(
            '返回签到有礼' => U('index')));
    }
    
    // 状态修改
    public function editStatus() {
        $data = I('post.');
        $status = I('post.status', null);
        if ($status == 1) {
            $dataInfo = M('tmarketing_info')->where(
                "node_id='{$this->nodeId}' AND batch_type='{$this->BATCH_TYPE}' AND status=1")->find();
            if ($dataInfo) {
                $this->error('您当前已经有一个签到有礼活动正在进行中，如果需要启用新的活动替换现有活动，请先停用现有活动！');
            }
        }
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($result) {
            node_log('签到有礼状态更改|活动id:' . $data['batch_id']);
            $this->success('更新成功', 
                array(
                    '返回' => U('index')));
        } else {
            $this->error('更新失败');
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
        
        $sql = "SELECT t.mobile,t.add_time,CASE t.status WHEN '1' THEN '未中奖' ELSE '中奖' END status,t.prize_level, r.param1 total_chance, CASE t.status WHEN '1' THEN '' ELSE c.batch_short_name END batch_short_name,ch.id channel_id , ch.name channel_name
			    FROM tcj_trace t
			    	left join tcj_rule r on r.batch_id = t.batch_id AND t.cj_rule_id = r.id 
			    	left join tcj_batch b on b.batch_id = t.batch_id AND b.id = t.rule_id
			    	left join tbatch_info c on c.batch_no = b.activity_no
			    	LEFT JOIN tchannel ch ON ch.id = t.channel_id
			    WHERE t.batch_id='{$batchId}' AND t.batch_type='{$this->BATCH_TYPE}' AND t.node_id = '{$node_id}'
			    ORDER by t.status DESC";
        $countSql = "SELECT COUNT(*) as count FROM tcj_trace WHERE batch_id='{$batchId}' AND batch_type='{$this->BATCH_TYPE}' AND node_id='{$node_id}'";
        $cols_arr = array(
            'mobile' => '手机号', 
            'add_time' => '中奖时间', 
            'status' => '是否中奖', 
            'total_chance' => '连续签到天数', 
            'batch_short_name' => '奖品', 
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
        $sql = "SELECT w.*, ch.id channel_id , ch.name channel_name
    	FROM tworld_cup_login_trace w
    	LEFT JOIN tbatch_channel c ON c.id = w.label_id
		LEFT JOIN tchannel ch ON ch.id = c.channel_id
    	WHERE w.batch_id='{$batchId}' ORDER BY w.phone_no";
        $countSql = "SELECT COUNT(*) as count FROM tworld_cup_login_trace WHERE batch_id='{$batchId}'";
        $cols_arr = array(
            'phone_no' => '手机号', 
            'login_time' => '签到日期', 
            'login_days' => '连续签到天数', 
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
