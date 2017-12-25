<?php

// VIP活动
class VipAction extends BaseAction {
    
    // 活动类型
    public $BATCH_TYPE = '21';
    public $_authAccessMap = '*';
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
    
    // 添加页
    public function add() {
        // 获取商户名称
        $nodeName = M('tnode_info')->where("node_id='{$this->node_id}'")->getField(
            'node_name');
        
        // 获取商户会员卡信息
        $mem_batch = M('tmember_batch')->where(
            array(
                'node_id' => $this->node_id,
                'status' => '1'))
            ->order('member_level asc')
            ->select();
        $this->assign('is_cj_button', '1');
        $this->assign('mem_batch', $mem_batch);
        $this->assign('node_name', $nodeName);
        
        $this->display();
    }
    
    // 添加提交页
    public function addSubmit() {
        $model = M('tmarketing_info');
        $data = I('post.');
        
        if (empty($data['name']))
            $this->error('请填写活动名称！');
        if (empty($data['start_time']))
            $this->error('请填写标签可用开始时间！');
        if (empty($data['end_time']))
            $this->error('请填写标签可用结束时间！');
            
 
        $log_img =  $data['resp_log_img'];
       $one_map = array(
            'name' => $data['name'], 
            'batch_type' => $this->BATCH_TYPE, 
            'node_id' => $this->node_id);
        $info = $model->where($one_map)->count('id');
        
        if ($info > 0) {
            $this->error("活动名称重复");
        }
        
        $data_arr = array(
            'name' => $data['name'], 
            'log_img' => $log_img, 
            'start_time' => substr($data['start_time'], 0, 8) . '000000', 
            'end_time' => substr($data['end_time'], 0, 8) . '235959', 
            'node_id' => $this->node_id, 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            'batch_type' => $this->BATCH_TYPE, 
            'is_show' => '1', 
            // 允许发文次数
            'fb_fwnt' => $data['fwnt'], 
            // 发文字数限制
            'fb_fwznt' => $data['fwznt'], 
            // 发文是否抽奖
            'fb_fw_is_cj' => $data['is_cj1'], 
            // 发文抽奖机会
            'fb_fw_cj_set' => $data['jp_set_type1'], 
            // 点赞是否抽奖
            'fb_dz_is_cj' => $data['is_cj2'], 
            // 点赞次数
            'fb_dzn' => $data['dznt'], 
            // 点赞抽奖机会
            'fb_dz_cj_set' => $data['jp_set_type2']);
        
        // 开启事物
        $tranDb = new Model();
        $tranDb->startTrans();
        
        // marketing_info
        $mid = $model->add($data_arr);
        
        if (! $mid) {
            $tranDb->rollback();
            $this->error('系统错误！', 
                array(
                    '返回VIP活动' => U('index')));
        }
        
        // 允许发文抽奖
        if ($data['is_cj1'] == '2') {
            if ($data['day_goods_count1'] > $data['goods_count1']) {
                $tranDb->rollback();
                $this->error('每日奖品限量不能大于总奖品数！');
            }
            
            $cjrulem = M('tcj_rule');
            $cdata = array(
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $mid, 
                // 单奖品多奖品，这里默认为1，表示单奖品。$data['jp_set_type'],
                'jp_set_type' => '2', 
                'day_count' => '1', 
                'total_chance' => $data['chance1'], 
                'node_id' => $this->node_id, 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                // 总中奖次数：
                'phone_total_count' => '0', 
                // 日中奖次数
                'phone_day_count' => '0', 
                // 参与次数
                'phone_total_part' => '0', 
                // 日参与次数
                'phone_day_part' => '0', 
                'type' => '1');
            $cj_rule_id = $cjrulem->add($cdata);
            if (! $cj_rule_id) {
                $tranDb->rollback();
                $this->error('系统错误！', 
                    array(
                        '返回VIP活动' => U('index')));
            }
            
            // 查询商品信息
            $map = array(
                'goods_id' => $data['goods_id1'], 
                'node_id' => $this->nodeId);
            $goodsInfo = M('tgoods_info')->where($map)->find();
            if (! $goodsInfo) {
                $tranDb->rollback();
                $this->error('发文抽奖的卡券信息错误！');
            }
            
            // 初始化tbatch_Info数据
            $batchinfo_data = array(
                'batch_no' => $goodsInfo['batch_no'], 
                'batch_short_name' => $goodsInfo['goods_name'], 
                'batch_name' => $goodsInfo['goods_name'], 
                'node_id' => $this->nodeId, 
                'user_id' => $this->userId, 
                'batch_class' => $goodsInfo['goods_type'], 
                'batch_type' => $goodsInfo['source'], 
                'info_title' => $data['mms_title1'], 
                'use_rule' => $data['using_rules1'], 
                'batch_img' => $goodsInfo['goods_image'], 
                'batch_amt' => $goodsInfo['goods_amt'], 
                'begin_time' => substr($data['start_time'], 0, 8) . '000000', 
                'end_time' => substr($data['end_time'], 0, 8) . '235959', 
                'send_begin_date' => substr($data['start_time'], 0, 8) . '000000', 
                'send_end_date' => substr($data['end_time'], 0, 8) . '235959', 
                'verify_begin_date' => $data['verify_time_type1'] == '0' ? $data['verify_begin_date1'] .
                     '000000' : $data['verify_begin_days1'], 
                    'verify_end_date' => $data['verify_time_type1'] == '0' ? $data['verify_end_date1'] .
                     '235959' : $data['verify_end_days1'], 
                    'verify_begin_type' => $data['verify_time_type1'], 
                    'verify_end_type' => $data['verify_time_type1'], 
                    'storage_num' => $data['goods_count1'], 
                    'add_time' => date('YmdHis'), 
                    'node_pos_group' => $goodsInfo['pos_group'], 
                    'node_pos_type' => $goodsInfo['pos_group_type'], 
                    'batch_desc' => $goodsInfo['goods_desc'], 
                    'node_service_hotline' => $goodsInfo['node_service_hotline'], 
                    'goods_id' => $goods_id, 
                    'remain_num' => $data['goods_count1'], 
                    'material_code' => $goodsInfo['customer_no'], 
                    'print_text' => $goodsInfo['print_text'], 
                    'm_id' => $mid, 
                    'validate_type' => $goodsInfo['validate_type']);
            
            $b_id = M('tbatch_info')->data($batchinfo_data)->add();
            if (! $b_id) {
                $tranDb->rollback();
                $this->error('发文奖品规则设置错误！');
            }
            
            // 初始化tcj_batch
            $cjbatchm = M('tcj_batch');
            $bdata = array(
                'batch_id' => $mid, 
                'node_id' => $this->node_id, 
                'activity_no' => $goodsInfo['batch_no'], 
                'goods_id' => $data['goods_id1'], 
                'award_origin' => '2', 
                'award_level' => '1', 
                'award_rate' => $data['goods_count1'], 
                'total_count' => $data['goods_count1'], 
                'day_count' => $data['day_goods_count1'], 
                'batch_type' => $this->BATCH_TYPE, 
                'cj_rule_id' => $cj_rule_id, 
                'b_id' => $b_id);
            
            if (in_array('', $bdata)) {
                $tranDb->rollback();
                $this->error('发文奖品规则设置错误！');
            }
            
            $cj_batch_id = $cjbatchm->add($bdata);
            
            if (! $cj_batch_id) {
                $tranDb->rollback();
                $this->error('发文抽奖奖品设置错误！');
            }
            
            // 扣减库存
            $goodsM = D('Goods');
            $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'], 
                $data['goods_count1'], $cj_batch_id, '0', '');
            if ($flag === false) {
                $tranDb->rollback();
                $this->error('发文抽奖奖品设置错误！' . $goodsM->getError());
            }
        }
        
        // 允许点赞抽奖
        if ($data['is_cj2'] == '2') {
            if ($data['day_goods_count2'] > $data['goods_count2']) {
                $tranDb->rollback();
                $this->error('每日奖品限量不能大于总奖品数！');
            }
            
            $cjrulem = M('tcj_rule');
            $cdata = array(
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $mid, 
                'jp_set_type' => '2', 
                'day_count' => '1', 
                'total_chance' => $data['chance2'], 
                'node_id' => $this->node_id, 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                // 总中奖次数：
                'phone_total_count' => '0', 
                // 日中奖次数
                'phone_day_count' => '0', 
                // 参与次数
                'phone_total_part' => '0', 
                // 日参与次数
                'phone_day_part' => '0', 
                'type' => '2');
            $cj_rule_id = $cjrulem->add($cdata);
            if (! $cj_rule_id) {
                $tranDb->rollback();
                $this->error('系统错误！', 
                    array(
                        '返回VIP活动' => U('index')));
            }
            
            // 查询商品信息
            $map = array(
                'goods_id' => $data['goods_id2'], 
                'node_id' => $this->nodeId);
            $goodsInfo = M('tgoods_info')->where($map)->find();
            if (! $goodsInfo) {
                $tranDb->rollback();
                $this->error('点赞抽奖的卡券信息错误！');
            }
            
            // 初始化tbatch_Info数据
            $batchinfo_data = array(
                'batch_no' => $goodsInfo['batch_no'], 
                'batch_short_name' => $goodsInfo['goods_name'], 
                'batch_name' => $goodsInfo['goods_name'], 
                'node_id' => $this->nodeId, 
                'user_id' => $this->userId, 
                'batch_class' => $goodsInfo['goods_type'], 
                'batch_type' => $goodsInfo['source'], 
                'info_title' => $data['mms_title2'], 
                'use_rule' => $data['using_rules2'], 
                'batch_img' => $goodsInfo['goods_image'], 
                'batch_amt' => $goodsInfo['goods_amt'], 
                'begin_time' => substr($data['start_time'], 0, 8) . '000000', 
                'end_time' => substr($data['end_time'], 0, 8) . '235959', 
                'send_begin_date' => substr($data['start_time'], 0, 8) . '000000', 
                'send_end_date' => substr($data['end_time'], 0, 8) . '235959', 
                'verify_begin_date' => $data['verify_time_type2'] == '0' ? $data['verify_begin_date2'] .
                     '000000' : $data['verify_begin_days2'], 
                    'verify_end_date' => $data['verify_time_type2'] == '0' ? $data['verify_end_date2'] .
                     '235959' : $data['verify_end_days2'], 
                    'verify_begin_type' => $data['verify_time_type2'], 
                    'verify_end_type' => $data['verify_time_type2'], 
                    'storage_num' => $data['goods_count2'], 
                    'add_time' => date('YmdHis'), 
                    'node_pos_group' => $goodsInfo['pos_group'], 
                    'node_pos_type' => $goodsInfo['pos_group_type'], 
                    'batch_desc' => $goodsInfo['goods_desc'], 
                    'node_service_hotline' => $goodsInfo['node_service_hotline'], 
                    'goods_id' => $goods_id, 
                    'remain_num' => $data['goods_count2'], 
                    'material_code' => $goodsInfo['customer_no'], 
                    'print_text' => $goodsInfo['print_text'], 
                    'm_id' => $mid, 
                    'validate_type' => $goodsInfo['validate_type']);
            
            $b_id = M('tbatch_info')->data($batchinfo_data)->add();
            if (! $b_id) {
                $tranDb->rollback();
                $this->error('点赞奖品规则设置错误！');
            }
            
            // 初始化tcj_batch
            $cjbatchm = M('tcj_batch');
            $bdata = array(
                'batch_id' => $mid, 
                'node_id' => $this->node_id, 
                'activity_no' => $goodsInfo['batch_no'], 
                'goods_id' => $data['goods_id2'], 
                'award_origin' => '2', 
                'award_level' => '1', 
                'award_rate' => $data['goods_count2'], 
                'total_count' => $data['goods_count2'], 
                'day_count' => $data['day_goods_count2'], 
                'batch_type' => $this->BATCH_TYPE, 
                'cj_rule_id' => $cj_rule_id, 
                'b_id' => $b_id);
            
            if (in_array('', $bdata)) {
                $tranDb->rollback();
                $this->error('点赞奖品规则设置错误！');
            }
            
            $cj_batch_id = $cjbatchm->add($bdata);
            
            if (! $cj_batch_id) {
                $tranDb->rollback();
                $this->error('点赞抽奖奖品设置错误！');
            }
            
            // 扣减库存
            $goodsM = D('Goods');
            $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'], 
                $data['goods_count2'], $cj_batch_id, '0', '');
            if ($flag === false) {
                $tranDb->rollback();
                $this->error('点赞抽奖奖品设置错误！' . $goodsM->getError());
            }
        }
        
        $tranDb->commit();
        $ser = D('TmarketingInfo');
        $arr = array(
            'node_id' => $this->nodeId, 
            'batch_type' => $this->BATCH_TYPE, 
            'batch_id' => $mid);
        $ser->init($arr);
        $ser->sendBatch();
        $this->ajaxReturn(
            array(
                'url' => U('LabelAdmin/BindChannel/index', 
                    array(
                        'batch_type' => $this->BATCH_TYPE, 
                        'batch_id' => $mid))), '活动创建成功！', 1);
    }
    
    // 审核页
    public function shenhe() {
        $modelsh = M('tfb_msg');
        $id = I("id");
        $arr = array(
            'batch_id' => $id, 
            'node_id' => $this->node_id);
        if (isset($_POST['mobile'])) {
            if ($_POST['mobile'] != '') {
                $arr['mobile'] = array(
                    'like', 
                    '%' . $_POST['mobile'] . '%');
            }
            if ($_POST['start_time'] != '' && $_POST['end_time'] != '') {
                $arr['add_time'] = array(
                    'BETWEEN', 
                    array(
                        $_POST['start_time'] . '000000', 
                        $_POST['end_time'] . '235959'));
            }
            if ($_POST['status'] != '') {
                $arr['status'] = $_POST['status'];
            }
        }
        
        // print_r($arr);
        import('ORG.Util.Page');
        // 导入分页类
        $mapcount = $modelsh->where($arr)->count();
        // 查询满足要求的总记录数
        $Page = new Page($mapcount, 8);
        // 实例化分页类 传入总记录数和每页显示的记录数
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        $show = $Page->show();
        // 分页显示输出
        $list = $modelsh->where($arr)
            ->order('id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        
        // echo $modelsh->getLastSql();
        $this->assign('bid', $id);
        $this->assign('page', $show);
        $this->assign('list', $list);
        $this->display();
    }
    
    // 是否通过审核
    public function isshenhe() {
        $modelsh = M('tfb_msg');
        $id = I('id');
        $arr = array(
            'id' => $id, 
            'node_id' => $this->node_id);
        
        $sid = I('stype');
        if ($sid == '2') {
            $tkinfo = $modelsh->where($arr)->find();
            $modeltk = M('tmarketing_info');
            $mp = array(
                'id' => $tkinfo['batch_id']);
            $cj = $modeltk->where($mp)->find();
            if ($cj['fb_fw_is_cj'] == '2') {
                if ($cj['fb_fw_cj_set'] == '1') {
                    $tfbmp = array(
                        'batch_id' => $tkinfo['batch_id'], 
                        'mobile' => $tkinfo['mobile'], 
                        'check_is_cj' => '2');
                    $iscj = $modelsh->where($tfbmp)->find();
                    if ($iscj) {
                        $cjdata = array(
                            'check_is_cj' => '1');
                        $modelsh->where($arr)->save($cjdata);
                    } else {
                        $cjdata = array(
                            'check_is_cj' => '2');
                        $modelsh->where($arr)->save($cjdata);
                    }
                } else {
                    $cjdata = array(
                        'check_is_cj' => '2');
                    $modelsh->where($arr)->save($cjdata);
                }
            }
            $data = array(
                'status' => '2', 
                'check_time' => date('YmdHis'));
            $modelsh->where($arr)->save($data);
            echo "<script>parent.art.dialog.list['uduf'].close()</script>";
            exit();
        } elseif ($sid == '3') {
            $data = array(
                'status' => '3', 
                'check_time' => date('YmdHis'));
            $modelsh->where($arr)->save($data);
            echo "<script>parent.art.dialog.list['uduf'].close()</script>";
            exit();
        } else {}
        $row = $modelsh->where($arr)->find();
        $this->assign('row', $row);
        $this->display();
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
            'id' => $id);
        $query_arr = $model->where($map)->find();
        
        // echo $query_arr['log_img'];exit;
        $batch_model = M('tbatch_info');
        if (! $query_arr)
            $this->error('未查询到数据！');
            
            // 发文是否抽奖，2表示抽奖
        if ($query_arr['fb_fw_is_cj'] == '2') {
            $cjrule = M('tcj_rule');
            $map1 = array(
                'node_id' => $query_arr['node_id'], 
                'batch_id' => $query_arr['id'], 
                'batch_type' => $this->BATCH_TYPE, 
                'status' => '1', 
                'type' => '1');
            
            // print_r($map1);exit;
            $cjarr = $cjrule->where($map1)->find();
            $map1 = array(
                'node_id' => $query_arr['node_id'], 
                'batch_id' => $query_arr['id'], 
                'batch_type' => $this->BATCH_TYPE, 
                'cj_rule_id' => $cjarr['id']);
            $cjbatch = M('tcj_batch');
            $cjbatch_arr = $cjbatch->where($map1)->find();
        }
        
        // 以下内容有问题
        $batch_map = array(
            'node_id' => $query_arr['node_id']);
        // print_r($batch_map);exit;
        $batch_map['batch_no'] = $cjbatch_arr['activity_no'];
        // echo $cjbatch_arr['activity_no'];exit;
        $batch_name_arr = $batch_model->where($batch_map)->getField(
            'batch_short_name');
        $batch_map = array(
            'node_id' => $query_arr['node_id']);
        // print_r($batch_map);exit;
        
        // 点赞是否抽奖，2表示抽奖
        if ($query_arr['fb_dz_is_cj'] == '2') {
            $cjrule = M('tcj_rule');
            $map1 = array(
                'node_id' => $query_arr['node_id'], 
                'batch_id' => $query_arr['id'], 
                'batch_type' => $this->BATCH_TYPE, 
                'status' => '1', 
                'type' => '2');
            $dzcjarr = $cjrule->where($map1)->find();
            $dzmap1 = array(
                'node_id' => $query_arr['node_id'], 
                'batch_id' => $query_arr['id'], 
                'batch_type' => $this->BATCH_TYPE, 
                'cj_rule_id' => $dzcjarr['id']);
            $cjbatch = M('tcj_batch');
            $dzcjbatch_arr = $cjbatch->where($dzmap1)->find();
        }
        $batch_map['batch_no'] = $dzcjbatch_arr['activity_no'];
        // echo $cjbatch_arr['activity_no'];exit;
        $dzbatch_name_arr = $batch_model->where($batch_map)->getField(
            'batch_short_name');
        
        // echo $query_arr["log_img"];exit;
        // 获取商户会员卡信息
        $mem_batch = M('tmember_batch')->where(
            array(
                'node_id' => $query_arr['node_id'], 
                'status' => '1'))
            ->order('member_level asc')
            ->select();
        $this->assign('tid', $id);
        $this->assign('mem_batch', $mem_batch);
        $this->assign('is_cj_button', '1');
        $this->assign('cjarr', $cjarr);
        $this->assign('cjbatch_arr', $cjbatch_arr);
        $this->assign('batch_name_arr', $batch_name_arr);
        $this->assign('dzbatch_name_arr', $dzbatch_name_arr);
        $this->assign('dzcjarr', $dzcjarr);
        $this->assign('dzcjbatch_arr', $dzcjbatch_arr);
        $this->assign('row', $query_arr);
        $this->display();
    }
    
    // 编辑提交页
    public function editSubmit() {
        $model = M('tmarketing_info');
        $id = I('post.id');
        $zname = I('post.name');
        $zstime = I('post.start_time');
        $zetime = I('post.end_time');
        $fwnt = I('post.fwnt');
        $fwznt = I('post.fwznt');
        $dznt = I('post.dznt');
        $resp_log_img = I('resp_log_img');
        
            
        $log_img = $resp_log_img;
        
        
        // $zname=I('post.resp_log_img');
        $arr = array(
            'id' => $id, 
            'node_id' => $this->node_id);
        $mpe = array(
            'name' => $zname, 
            'start_time' => substr($zstime, 0, 8) . '000000', 
            'end_time' => substr($zetime, 0, 8) . '235959', 
            'fb_dzn' => $dznt, 
            'fb_fwnt' => $fwnt, 
            'fb_fwznt' => $fwznt, 
            'log_img' => $log_img);
        $chen = $model->where($arr)->save($mpe);
        if ($chen === false) {
            $this->success('更新失败', 
                array(
                    '返回VIP活动' => U('index')));
        }
        $this->success('更新成功', array(
            '返回VIP活动' => U('index')));
    }
    
    // 导入白名单
    public function whitelist() {
        $id = I('id');
        if ($_FILES['log_img']) {
            
            // echo '123';exit;
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
            // 上传路径接上文件名
            if (($handle = fopen($flieWay, "rw")) !== FALSE) {
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
                            $this->error(
                                '文件第' . $row . '行字段不符合要求,请确保与下载模板文件中第一行的字段保持一致!');
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
                        'batch_id' => $id);
                    $batchId = $batchModel->add($data);
                }
            }
            echo "<script>parent.art.dialog.list['aa'].close()</script>";
            exit();
        }
        
        $this->assign('id', $id);
        $this->display();
    }
    
    // 状态修改
    public function editStatus() {
        $batchId = I('post.batch_id', null);
        $status = I('post.status', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $result = M('tmarketing_info')->where(
            "node_id in ( {$this->nodeIn() } ) AND id='{$batchId}'")->find();
        if (! $result) {
            $this->error('未找到该活动');
        }
        if ($status == '1') {
            $data = array(
                'id' => $batchId, 
                'status' => '1');
        } else {
            $data = array(
                'id' => $batchId, 
                'status' => '2');
        }
        $result = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $batchId))->save($data);
        if ($result) {
            node_log('抽奖活动状态更改|活动id:' . $batchId);
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
    
    // 审核名单下载
    public function xiahai() {
        $id = I("id");
        // 活动号
        if (is_null($id))
            $this->error('缺少参数');
        $fileName = $id . '-' . date('Y-m-d') . '-审核名单.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "标题,手机号,审批时间,点击数,点赞数\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $query = M()->query(
                "SELECT title,mobile,
									check_time,click_count,dz_count
									FROM
									tfb_msg WHERE batch_id='{$id}' and node_id='{$this->nodeId}' and status=2 ORDER by id DESC LIMIT {$page},{$page_count}");
            if (! $query)
                exit();
            foreach ($query as $v) {
                $title = iconv('utf-8', 'gbk', $v['title']);
                echo "{$title},{$v['mobile']}," .
                     date('Y-m-d H:i:s', strtotime($v['check_time'])) .
                     ",{$v['click_count']},{$v['dz_count']}\r\n";
            }
        }
    }
}
