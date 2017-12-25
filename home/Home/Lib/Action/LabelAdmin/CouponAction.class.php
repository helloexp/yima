<?php

/*
 * 优惠券
 */
class CouponAction extends BaseAction {
    // 活动类型
    public $BATCH_TYPE = '9';
    // 图片路径
    public $img_path;
    // 初始化
    public function _initialize() {
        parent::_initialize();
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        import('@.Vendor.CommonConst') or die('include file fail.');
        // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        
        $this->assign('img_path', $img_path);
        $this->assign('tmp_path', $tmp_path);
    }

    public function index() {
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
        $model = M('tmarketing_info');
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
        $this->assign('is_cj_button', '1');
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('nodeList', $this->getNodeTree());
        $this->display(); // 输出模板
    }

    public function add() {
        //商户是否开通自定义短信内容的标志
        $startUp = M('tnode_info')->where(array('node_id'=>$this->node_id))->getField('custom_sms_flag');
        if ($this->isPost()) {
            $baseActivityModel = D('BaseActivity', 'Service');
            
            // 数据验证
            $name = I('post.name');
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("活动名称{$error}");
            }
            $baseActivityModel->checkisactivitynamesame($name, 
                $this->BATCH_TYPE);
            
            $startTime = I('post.start_time');
            if (! check_str($startTime, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $endTime = I('post.end_time');
            if (! check_str($endTime, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动结束时间{$error}");
            }
            if ($endTime < date('Ymd')) {
                $this->error('活动结束时间不能小于当前时间');
            }
            if ($endTime < $startTime) {
                $this->error('活动开始时间不能小于活动结束时间');
            }
            $nodeName = I('post.node_name');
            if (! check_str($nodeName, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("商户名称{$error}");
            }
            $wapTitle = I('post.wap_title');
            if (! check_str($wapTitle, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("活动页面标题{$error}");
            }
            $wapInfo = I('post.wap_info');
            if (! check_str($wapInfo, 
                array(
                    'null' => false), $error)) {
                $this->error("活动页面内容{$error}");
            }
            $videoUrl = I('post.video_url');
            if (! check_str($videoUrl, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("视频链接地址{$error}");
            }
            $goodsId = I('post.goods_id');
            if (! check_str($goodsId, 
                array(
                    'null' => false), $error)) {
                $this->error("请选择优惠券");
            }
            if ($goodsId == 'gw1400000000') { // 如果传过来的是这个值,表示奖品是积分奖品
                $jfGoods = D('Goods')->createJfGoods($this->node_id);
                if ($jfGoods['code'] == '0000') {
                    $goodsId = $jfGoods['goods_id'];
                } else {
                    $this->error($jfGoods['msg']);
                }
                $intCount = I('post.int_count');
                if (! check_str($intCount, 
                    array(
                        'null' => false), $error)) {
                    $this->error("填写积分值");
                }
            }
            $goodsInfos = M('tgoods_info')->where(
                array(
                    'goods_id' => $goodsId))
            ->field(['goods_type', 'verify_begin_type', 'verify_begin_date', 'verify_end_date'])
            ->find();
            $goodsType = $goodsInfos['goods_type'];
            if (! in_array($goodsType, 
                array(
                    CommonConst::GOODS_TYPE_HB, 
                    CommonConst::GOODS_TYPE_JF,
                    CommonConst::GOODS_TYPE_LLB, 
                    CommonConst::GOODS_TYPE_HF
                ))) {
                // 使用时间
                $verifyTimeType = I('post.verify_time_type');
                switch ($verifyTimeType) {
                    case 0:
                        $verifyBeginDate = I('post.verify_begin_date');
                        if (! check_str($verifyBeginDate, 
                            array(
                                'null' => false, 
                                'strtype' => 'datetime', 
                                'format' => 'Ymd'), $error)) {
                            $this->error("使用开始时间日期{$error}");
                        }
                        $verifyEndDate = I('post.verify_end_date');
                        if (! check_str($verifyEndDate, 
                            array(
                                'null' => false, 
                                'strtype' => 'datetime', 
                                'format' => 'Ymd'), $error)) {
                            $this->error("使用结束时间日期{$error}");
                        }
                        if (strtotime($verifyEndDate) <
                             strtotime($verifyBeginDate)) {
                            $this->error('使用开始时间日期不能大于使用结束时间日期');
                        }
                        $verifyBeginDate .= '000000';
                        $verifyEndDate .= '235959';
                        break;
                    case 1:
                        $verifyBeginDate = I('post.verify_begin_days');
                        if (! check_str($verifyBeginDate, 
                            array(
                                'null' => false, 
                                'strtype' => 'int'), $error)) {
                            $this->error("使用开始天数{$error}");
                        }
                        $verifyEndDate = I('post.verify_end_days');
                        if (! check_str($verifyEndDate, 
                            array(
                                'null' => false, 
                                'strtype' => 'int'), $error)) {
                            $this->error("使用结束天数{$error}");
                        }
                        if ($verifyEndDate < $verifyBeginDate) {
                            $this->error('使用开始天数不能大于使用结束天数');
                        }
                        break;
                    default:
                        $this->error('未知的使用时间类型');
                }
                /*
                $mmsTitle = I('post.mms_title');
                if (! check_str($mmsTitle, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => '10'), $error)) {
                    $this->error("彩信标题{$error}");
                }
                */
                $usingRules = I('post.using_rules');
                if (! check_str($usingRules, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => '100'), $error)) {
                    $this->error("使用说明{$error}");
                }
            }
            
            //如果是流量包时间需要从goods_info表里赋值过去
            if ($goodsType == CommonConst::GOODS_TYPE_LLB || $goodsType == CommonConst::GOODS_TYPE_HF) {
                $verifyTimeType = $goodsInfos['verify_begin_type'];
                $verifyBeginDate = $goodsInfos['verify_begin_date'];
                $verifyEndDate = $goodsInfos['verify_end_date'];
            }
            
            $number = I('post.number');
            if (! check_str($number, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1'), $error)) {
                $this->error("优惠券数量{$error}");
            }
            $cjButtonText = I('post.cj_button_text');
            if (! check_str($cjButtonText, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '6'), $error)) {
                $this->error("抽奖按钮文字{$error}");
            }
            
            $snsType = I('post.sns_type');
            if (! empty($snsType)) {
                $snsType = $baseActivityModel->implodearray($snsType);
            } else {
                $snsType = '';
            }
            // logo
            $resp_log_img = I('post.resp_log_img', null);
            $resp_bg_img = I('post.resp_bg_img', null);
            if ($resp_log_img != '' && I('post.is_log_img') == 1) {
                $log_img = $resp_log_img;
            }
            // 背景
            if ($resp_bg_img != '') {
                $bg_img = $resp_bg_img;
            }
            $map = array(
                'node_id' => $this->node_id, 
                'goods_type' => array(
                    'in', 
                    '0,1,2,3,7,12,14,15'), 
                'source' => array(
                    'in', 
                    '0,1,2'), 
                'batch_no' => array(
                    'exp', 
                    'IS NOT NULL'), 
                'status' => 0, 
                'goods_id' => $goodsId);
            M()->startTrans();
            $goodsInfo = M('tgoods_info')->lock(true)
                ->where($map)
                ->find();
            if (! $goodsInfo) {
                M()->rollback();
                $this->error('未找到该优惠券信息');
            }
            // 校验库存
            if ($goodsInfo['remain_num'] < $number &&
                 $goodsInfo['storage_type'] == '1') {
                M()->rollback();
                $this->error('该优惠券剩余库存不足');
            }
            // 创建营销活动
            $data = array(
                'name' => $name, 
                'wap_title' => $wapTitle, 
                'log_img' => $log_img, 
                'music' => I('post.resp_music'), 
                'video_url' => I('post.video_url'), 
                'start_time' => $startTime . '000000', 
                'end_time' => $endTime . '235959', 
                'memo' => I('post.memo'), 
                'node_id' => $this->node_id, 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                // 'size' => I('post.size'),
                // 'code_img' => I('post.resp_code_img'),
                'sns_type' => $snsType, 
                'other_url' => I('post.other_url'), 
                'wap_info' => I('post.wap_info'), 
                'node_name' => I('post.node_name'), 
                'is_cj' => '1', 
                'page_style' => I('post.page_style'), 
                'bg_style' => I('post.bg_style'), 
                'bg_pic' => $bg_img, 
                'batch_type' => $this->BATCH_TYPE, 
                'is_show' => '1', 
                'batch_come_from' => session('batch_from') ? session(
                    'batch_from') : '1');
            $marketId = M('tmarketing_info')->add($data);
            if (! $marketId) {
                M()->rollback();
                $this->error('系统错误!数据添加失败');
            }
            if ($goodsInfo['goods_type'] == CommonConst::GOODS_TYPE_JF) {
                $goodsInfo['goods_amt'] = $intCount;
            }
            // 创建活动
            $data = array(
                'batch_no' => $goodsInfo['batch_no'], 
                'batch_short_name' => $goodsInfo['goods_name'], 
                'batch_name' => $goodsInfo['goods_name'], 
                'node_id' => $this->nodeId, 
                'user_id' => $this->userId, 
                'batch_class' => $goodsInfo['goods_type'], 
                'batch_type' => $goodsInfo['source'], 
                'use_rule' => $usingRules, 
                'batch_img' => $goodsInfo['goods_image'], 
                'info_title' => '电子券',
                'batch_amt' => $goodsInfo['goods_amt'], 
                'begin_time' => $goodsInfo['begin_time'], 
                'end_time' => $goodsInfo['end_time'], 
                'verify_begin_date' => $verifyBeginDate, 
                'verify_end_date' => $verifyEndDate, 
                'verify_begin_type' => $verifyTimeType, 
                'verify_end_type' => $verifyTimeType, 
                'add_time' => date('YmdHis'), 
                'node_pos_group' => $goodsInfo['pos_group'], 
                'node_pos_type' => $goodsInfo['pos_group_type'], 
                'batch_desc' => $goodsInfo['goods_desc'], 
                'node_service_hotline' => $goodsInfo['node_service_hotline'], 
                'goods_id' => $goodsId, 
                'storage_num' => $number, 
                'remain_num' => $number, 
                'material_code' => $goodsInfo['customer_no'], 
                'print_text' => $goodsInfo['print_text'], 
                'm_id' => $marketId, 
                'validate_type' => $goodsInfo['validate_type']);
            //自定义短信内容
            if($startUp == 1 && in_array($goodsType,array('0','1','2','3'))){
                $sms_text = I('cusMsg','');
                if(empty($sms_text)){
                    $this->error('短信内容不能空！');
                }else{
                    $data['sms_text'] = $sms_text;
                }
            }

            $batchId = M('tbatch_info')->data($data)->add();
            if (! $batchId) {
                M()->rollback();
                $this->error('系统错误!添加失败-0001');
            }
            // 奖品配置
            $data = array(
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $marketId, 
                'jp_set_type' => '2', 
                'total_chance' => '100', 
                'node_id' => $this->node_id, 
                'cj_button_text' => $cjButtonText, 
                'phone_day_count' => '0', 
                'phone_total_part' => '0', 
                'phone_day_part' => '0', 
                'add_time' => date('YmdHis'), 
                'status' => '1');
            $ruleId = M('tcj_rule')->add($data);
            if (! $ruleId) {
                M()->rollback();
                $this->error('系统错误!数据添加失败-0002');
            }
            $data = array(
                'batch_id' => $marketId, 
                'node_id' => $this->node_id, 
                'activity_no' => $goodsInfo['batch_no'], 
                'award_origin' => '2', 
                'award_level' => '1', 
                'award_rate' => $number, 
                'total_count' => $number, 
                'day_count' => $number, 
                'batch_type' => $this->BATCH_TYPE, 
                'cj_rule_id' => $ruleId, 
                'goods_id' => $goodsInfo['goods_id'], 
                'b_id' => $batchId);
            $cjId = M('tcj_batch')->add($data);
            if (! $cjId) {
                M()->rollback();
                $this->error('系统错误!数据添加失败-0003');
            }
            // 跟新库存
            $goodsModel = D('Goods');
            $result = $goodsModel->storagenum_reduc($goodsInfo['goods_id'], 
                $number, $cjId, 0);
            if (! $result) {
                M()->rollback();
                $this->error($goodsModel->getError());
            }
            M()->commit();
            // 顺便发布到多乐互动专用渠道上
            $bchId = D('MarketCommon')->chPublish($this->nodeId,$marketId);
            if($bchId === false){
                $this->error('发布到渠道失败');
            }
            $ser = D('TmarketingInfo');
            $arr = array(
                'node_id' => $this->nodeId, 
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $marketId);
            $ser->init($arr);
            $ser->sendBatch();
            node_log('优惠券活动添加|活动名:' . $name);
            $this->ajaxReturn(
                array(
                    'url' => U('LabelAdmin/BindChannel/index', 
                        array(
                            'batch_type' => $this->BATCH_TYPE, 
                            'batch_id' => $marketId))), '活动创建成功！', 1);
            exit();
        }
        
        $nodeInfo = get_node_info($this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_logo', $nodeInfo['head_photo']);
        
        //获取发码费用价格
        $sendPrice = D('ActivityPayConfig')->getSendFee($this->node_id);
        $this->assign('sendPrice', $sendPrice);
        
        // 未付款的时候,需要提示有没有超过60,超过部分要另外收费
        $TwoFestivalAdminModel = D('TwoFestivalAdmin');
        $needShowTips = $TwoFestivalAdminModel->needShowExTips($this->node_id, '');
        $paySettingJson = D('ActivityPayConfig')->getDefaultPayConfigModelByBatchType($this->BATCH_TYPE);
        $paySetting = json_decode($paySettingJson, true);
        $this->assign('configOneActDays', $paySetting['duringTime']);
        $this->assign('exPrice', $paySetting['exPrice']);
        $this->assign('startUp',$startUp);
        $this->assign('needShowTips', $needShowTips);
        $LimitInfo = $TwoFestivalAdminModel->getLimitInfo($this->node_id, '');
        $this->assign('type', $LimitInfo['type']);
        $this->assign('freeUseLimit', $LimitInfo['freeUseLimit']);
        
        $this->display();
    }

    public function edit() {
        $batchId = I('id', 'mysql_real_escape_string');
        
        $baseActivityModel = D('BaseActivity', 'Service');
        $query_arr = $baseActivityModel->checkactivityexist($batchId, 
            $this->BATCH_TYPE, $this->nodeIn());
        
        if ($query_arr['log_img'] == '') {
            $nodeInfo = get_node_info($this->node_id);
            $this->assign('head_photo', get_upload_url($nodeInfo['head_photo']));
            $this->assign('node_logo', $nodeInfo['head_photo']);
        }
        // 获取奖品信息
        $map = array(
            'node_id' => $this->nodeId, 
            'batch_id' => $query_arr['id'], 
            'batch_type' => $this->BATCH_TYPE, 
            'status' => '1');
        $cjArr = M('tcj_rule')->where($map)->find();
        $map = array(
            'node_id' => $this->nodeId, 
            'batch_id' => $query_arr['id'], 
            'batch_type' => $this->BATCH_TYPE, 
            'cj_rule_id' => $cjArr['id']);
        $cjBatchArr = M('tcj_batch')->where($map)->find();

        $rowGood = M('tgoods_info')->where(array('goods_id'=>$cjBatchArr['goods_id']))->find();
        $sendPrice = D('ActivityPayConfig')->getSendFee($this->node_id);
        $tipStr = '每张卡券将收取'.$sendPrice['buy'].'元异业卡券下发费';
        if($rowGood['source'] == 0){
            $tipStr = '每张卡券将收取'.$sendPrice['self'].'元卡券下发费用';
        }
        $tipStr = '';
        $this->assign('tipStr', $tipStr);
        //自定义短信
        $startUp = M('tnode_info')->where(array('node_id'=>$this->node_id))->getField('custom_sms_flag');

        // 表单提交
        if ($this->isPost()) {
            // 数据验证
            $name = I('post.name');
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("活动名称{$error}");
            }
            
            $baseActivityModel->checkisactivitynamesame($name, 
                $this->BATCH_TYPE, $batchId);
            
            $startTime = I('post.start_time');
            if (! check_str($startTime, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $endTime = I('post.end_time');
            if (! check_str($endTime, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动结束时间{$error}");
            }
            if ($endTime < date('Ymd')) {
                $this->error('活动结束时间不能小于当前时间');
            }
            if ($endTime < $startTime) {
                $this->error('活动开始时间不能小于活动结束时间');
            }
            $nodeName = I('post.node_name');
            if (! check_str($nodeName, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("商户名称{$error}");
            }
            $wapTitle = I('post.wap_title');
            if (! check_str($wapTitle, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("活动页面标题{$error}");
            }
            $wapInfo = I('post.wap_info');
            if (! check_str($wapInfo, 
                array(
                    'null' => false), $error)) {
                $this->error("活动页面内容{$error}");
            }
            $videoUrl = I('post.video_url');
            if (! check_str($videoUrl, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("视频链接地址{$error}");
            }
            $number = I('post.number');
            if (! check_str($number, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '1'), $error)) {
                $this->error("优惠券数量{$error}");
            }
            $cjButtonText = I('post.cj_button_text');
            if (! check_str($cjButtonText, 
                array(
                    'null' => true, 
                    'maxlen_cn' => '6'), $error)) {
                $this->error("抽奖按钮文字{$error}");
            }
            $snsType = I('post.sns_type');
            if (! empty($snsType)) {
                $snsType = $baseActivityModel->implodearray($snsType);
            } else {
                $snsType = '';
            }
            
            // logo
            $resp_log_img = I('post.resp_log_img', null);
            $resp_log_img = str_replace('..', '', $resp_log_img);
            $resp_bg_img = I('post.resp_bg_img', null);
            $resp_bg_img = str_replace('..', '', $resp_bg_img);
            if (I('post.is_log_img') == '1' && ! empty($resp_log_img)) {
                $log_img = $resp_log_img;
            } else {
                $log_img = '';
            }
            
            // 背景
            if ($resp_bg_img && I('post.reset_bg') == '1') {
                $bg_img = $resp_bg_img;
            } else {
                $bg_img = $resp_bg_img;
            }
            // 如果状态是付费中(不能让他修改时间);
            $isInPay = D('Order')->isInPay($this->node_id, $batchId);
            if ($isInPay) {
                $this->error('订单已生成，活动时间不可更改。如需更改时间，请先到<a target="_blank" href="' .
                         U('Home/ServicesCenter/myOrder') . '">我的订单</a>中取消订单。');
            }
            // 检查是否有没有超过购买的期限
            $isFreeUser = D('node')->getNodeVersion($this->node_id);
            try {
                D('TwoFestivalAdmin')->checkLimitDay($this->node_id,
                    $isFreeUser, $query_arr['id'], $startTime . '000000',
                    $endTime . '235959');
            } catch (Exception $e) {
                $this->error($e->getMessage());
            }
            
            
            // 获取优惠券信息
            M()->startTrans();
            $batchInfo = M('tbatch_info')->lock(true)
                ->where("id={$cjBatchArr['b_id']}")
                ->find();
            $goodsInfo = M('tgoods_info')->lock(true)
                ->where("goods_id='{$batchInfo['goods_id']}'")
                ->find();
            if (! in_array($goodsInfo['goods_type'], 
                array(
                    CommonConst::GOODS_TYPE_HB, 
                    CommonConst::GOODS_TYPE_JF,
                    CommonConst::GOODS_TYPE_HF
                ))) {
                // 使用时间
                $verifyTimeType = $batchInfo['verify_begin_type'];
                switch ($verifyTimeType) {
                    case 0:
                        $verifyEndDate = I('post.verify_end_date');
                        if (! check_str($verifyEndDate, 
                            array(
                                'null' => false, 
                                'strtype' => 'datetime', 
                                'format' => 'Ymd'), $error)) {
                            M()->rollback();
                            $this->error("使用结束时间日期{$error}");
                        }
                        if ($verifyEndDate < date('Ymd')) {
                            M()->rollback();
                            $this->error('使用结束时间不能小于当前时间');
                        }
                        $verifyEndDate .= '235959';
                        if ($verifyEndDate < $batchInfo['verify_end_date']) {
                            M()->rollback();
                            $this->error('使用结束时间不能缩短,只能延长');
                        }
                        break;
                    case 1:
                        $verifyEndDate = I('post.verify_end_days');
                        if (! check_str($verifyEndDate, 
                            array(
                                'null' => false, 
                                'strtype' => 'int'), $error)) {
                            M()->rollback();
                            $this->error("使用结束天数{$error}");
                        }
                        if ($verifyEndDate < $batchInfo['verify_end_date']) {
                            M()->rollback();
                            $this->error('使用结束天数不能缩短,只能延长');
                        }
                        break;
                    default:
                        M()->rollback();
                        $this->error('未知的使用时间类型');
                }
                /*
                $mmsTitle = I('post.mms_title');
                if (! check_str($mmsTitle,
                    array(
                        'null' => false,
                        'maxlen_cn' => '10'), $error)) {
                    $this->error("彩信标题{$error}");
                }
                */
                $usingRules = I('post.using_rules');
                if (! check_str($usingRules, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => '100'), $error)) {
                    $this->error("使用说明{$error}");
                }
            }
            
            // 数据更新
            if ($number != $cjBatchArr['total_count']) { // 库存更新
                if ($goodsInfo['storage_type'] == '1' &&
                     $number > $cjBatchArr['total_count'] && $goodsInfo['remain_num'] <
                     ($number - $cjBatchArr['total_count'])) {
                    M()->rollback();
                    $this->error('该优惠劵库存不足！现有库存为：' . $goodsInfo['remain_num']);
                }
                $batchRemainNum = $number -
                     ($batchInfo['storage_num'] - $batchInfo['remain_num']);
                $data = array(
                    'remain_num' => $batchRemainNum, 
                    'storage_num' => $number, 
                    'update_time' => date('YmdHis'));
                $result = M('tbatch_info')->where("id = '{$batchInfo['id']}'")->save(
                    $data);
                if ($result === false) {
                    M()->rollback();
                    $this->error('库存更新失败-0001');
                }
                $goodsM = D('Goods');
                $count = $number - $cjBatchArr['total_count'];
                $opt_type = $count > 0 ? '6' : '7';
                $result = $goodsM->storagenum_reduc($goodsInfo['goods_id'], 
                    $count, $cjBatchArr['id'], $opt_type, '');
                if (! $result) {
                    M()->rollback();
                    $this->error($goodsM->getError());
                }
            }
            // 活动更新
            $data = array(
                'name' => $name, 
                'wap_title' => $wapTitle, 
                'log_img' => $log_img, 
                'music' => I('post.resp_music'), 
                'video_url' => I('post.video_url'), 
                'start_time' => $startTime . '000000', 
                'end_time' => $endTime . '235959', 
                'memo' => I('post.memo'), 
                'node_id' => $this->node_id, 
                'add_time' => date('YmdHis'), 
                'status' => '1', 
                // 'size' => I('post.size'),
                // 'code_img' => I('post.resp_code_img'),
                'sns_type' => $snsType, 
                'other_url' => I('post.other_url'), 
                'wap_info' => I('post.wap_info'), 
                'node_name' => I('post.node_name'), 
                'is_cj' => '1', 
                'page_style' => I('post.page_style'), 
                'bg_style' => I('post.bg_style'), 
                'bg_pic' => $bg_img, 
                'is_show' => '1');
            $result = M('tmarketing_info')->where(
                "id='{$batchId}' AND node_id='{$this->nodeId}'")->save($data);
            if ($result === false) {
                M()->rollback();
                $this->error('系统错误!数据更新失败-0004');
            }
            // 更新时间短信
            $data = array(
                'use_rule' => $usingRules, 
                'info_title' => $mmsTitle, 
                'verify_end_date' => $verifyEndDate, 
                'status' => '0');
            // 如果是积分奖品,更新积分值
            if ($goodsInfo['goods_type'] == CommonConst::GOODS_TYPE_JF) {
                $intCount = I('post.int_count');
                if (! check_str($intCount, 
                    array(
                        'null' => false), $error)) {
                    $this->error("填写积分值");
                }
                $data['batch_amt'] = $intCount;
            }
            //自定义短信内容
            if($startUp == 1 && in_array($rowGood['goods_type'],array('0','1','2','3'))){
                $sms_text = I('cusMsg','');
                if(empty($sms_text)){
                    $this->error('短信内容不能空！');
                }else{
                    $data['sms_text'] = $sms_text;
                }
            }
            $result = M('tbatch_info')->where("id = '{$batchInfo['id']}'")->save(
                $data);
            if ($result === false) {
                M()->rollback();
                $this->error('库存更新失败-0001');
            }
            // 更新抽奖文字
            $map = array(
                'node_id' => $this->nodeId, 
                'batch_id' => $batchId, 
                'batch_type' => $this->BATCH_TYPE);
            $result = M('tcj_rule')->where($map)->save(
                array(
                    'cj_button_text' => $cjButtonText));
            if ($result === false) {
                M()->rollback();
                $this->error('系统错误!数据更新失败-0002');
            }
            // 奖品总数更新
            $where = array(
                'node_id' => $this->nodeId, 
                'batch_id' => $query_arr['id'], 
                'batch_type' => $this->BATCH_TYPE, 
                'cj_rule_id' => $cjArr['id']);
            $result = M('tcj_batch')->where($where)->save(
                array(
                    'total_count' => $number, 
                    'day_count' => $number));
            if ($result === false) {
                M()->rollback();
                $this->error('系统错误!数据更新失败-0003');
            }
            M()->commit();
            node_log('优惠券活动编辑|活动名:' . $name);
            $this->success('活动更新成功');
            exit();
        }
        
        // 获取优惠券信息
        $batchInfo = M('tbatch_info')->where("id={$cjBatchArr['b_id']}")->find();
        $this->assign('batchInfo', $batchInfo);
        $this->assign('couponText', $cjArr['cj_button_text']);
        $this->assign('cjBatchArr', $cjBatchArr);
        $this->assign('row', $query_arr);
        $this->assign('startUp',$startUp);
        
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
        
        $this->display();
    }

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
            'send_count' => '优惠券量');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // 状态修改
    public function editStatus() {
        $data = I('post.');
        $baseActivityModel = D('BaseActivity', 'Service');
        $result = $baseActivityModel->changeTheActivityStatus($data, 
            $this->BATCH_TYPE, $this->nodeIn());
        if ($result) {
            node_log('优惠券活动状态更改|活动ID：' . $data['batch_id']);
            $this->success('更新成功');
        } else {
            $this->error('更新失败');
        }
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
        
        $fileName = $nodeInfo['name'] . '-' . date('Y-m-d') . '-发放名单.csv';
        $fileName = iconv('utf-8', 'gb2312', $fileName);
        header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=" . $fileName);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header("Expires: 0");
        $start_num = 0;
        $page_count = 5000;
        $cj_title = "手机号,发放时间\r\n";
        echo $cj_title = iconv('utf-8', 'gbk', $cj_title);
        for ($j = 1; $j < 100; $j ++) {
            $page = ($j - 1) * $page_count;
            $query = M()->query(
                "SELECT mobile,add_time FROM
					tcj_trace WHERE batch_id='{$batchId}' AND batch_type={$this->BATCH_TYPE} AND node_id ={$nodeInfo['node_id']}
					ORDER by status DESC LIMIT {$page},{$page_count}");
            if (! $query)
                exit();
            foreach ($query as $v) {
                $cj_status = iconv('utf-8', 'gbk', $v['status']);
                echo "{$v['mobile']}," .
                     date('Y-m-d H:i:s', strtotime($v['add_time'])) . "\r\n";
            }
        }
    }
}
