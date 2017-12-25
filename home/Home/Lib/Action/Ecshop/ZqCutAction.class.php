<?php

/**
 * 吴刚砍树
 *
 * @author bao
 */
class ZqCutAction extends BaseAction {
    
    // 活动类型
    public $BATCH_TYPE = '55';

    public function _initialize() {
        parent::_initialize();
        // $this->error('尊敬的旺财用户，该活动已停用！');
        // 验证是否开通服务
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        if (! $node_info['receive_phone']) {
            $this->error("您的收款账户信息不完整，请至多宝电商配置处补齐。", 
                array(
                    '返回' => 'javascript:history.go(-1)', 
                    '去配置' => U('Ecshop/BusiOption/index')));
        }
        $account_info = M('tnode_account')->where(
            array(
                'node_id' => $this->node_id))->select();
        if (! $account_info)
            $this->error("您的收款账户信息不完整，请至多宝电商配置处添加", 
                array(
                    '返回' => 'javascript:history.go(-1)', 
                    '去配置' => U('Ecshop/BusiOption/index')));
        
        $this->assign('node_info', $node_info);
    }

    public function add() {
        $guideUrl = M('tweixin_info')->where("node_id='{$this->nodeId}'")->getField(
            'guide_url'); // 关注页链接
        if ($this->isPost()) {
            $error = '';
            $batchName = I('name');
            if (! check_str($batchName, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '8'), $error)) {
                $this->error("活动名称{$error}");
            }
            $startTime = I('start_time');
            if (! check_str($startTime, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $endTime = I('end_time');
            if (! check_str($endTime, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动结束时间{$error}");
            }
            // 免费用户结束时间卡住
            if (! $this->hasPayModule('m2')) {
                if ($endTime > C('CUTDATE.ENDDATE'))
                    $this->error('结束时间不能超过' . C('CUTDATE.ENDDATE'));
            }
            if ($endTime < date('Ymd'))
                $this->error('活动截止日期不能小于当前日期');
            
            $selectGoodsId = I('select_goods_id');
            if (! check_str($selectGoodsId, 
                array(
                    'null' => false), $error)) {
                $this->error("请选择商品或者卡券！");
            }
            $goodsInfo = M('tgoods_info')->where(
                array(
                    'goods_id' => $selectGoodsId, 
                    'node_id' => $this->nodeId))->find(); // tgoodsInfo 数据
            $groupPrice = I('post.group_price', null);
            if (! check_str($groupPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0'), $error)) {
                $this->error("商品销售价{$error}");
            }
            // 商品销售总数
            $goodsNumFlag = I('post.goods_num_limit', null);
            $goodsNum = I('post.goods_num', null);
            if ($goodsNumFlag == 1) {
                $goodsNum = - 1;
            } else {
                if (! check_str($goodsNum, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0'), $error)) {
                    $this->error("商品总数{$error}" . $goodsNumFlag);
                }
            }
            if ($goodsInfo['storage_type'] != 0 &&
                 ($goodsNum > $goodsInfo['remain_num']))
                $this->error('库存不足');
                // 商品市场价格
            $mPrice = I('post.market_price', null);
            $mPriceFlag = I('post.market_price_flag', null);
            if ($mPriceFlag == 0)
                $mPrice = 0;
            else {
                if (! check_str($mPrice, 
                    array(
                        'null' => false, 
                        'strtype' => 'number', 
                        'minval' => '0.01'), $error)) {
                    $this->error("市场价格{$error}");
                }
            }
            // 砍价最低金额
            $cutLowPrice = I('cut_low_price');
            if (! check_str($cutLowPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0'), $error)) {
                $this->error("砍价最低金额{$error}");
            }
            if ($cutLowPrice >= $groupPrice)
                $this->error('砍价最低金额不能大于商品销售价');
                // 单次砍价最高金额
            $onceCutPrice = I('once_cut_price');
            if (! check_str($onceCutPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'number', 
                    'minval' => '0.01', 
                    'maxval' => $groupPrice - $cutLowPrice), $error)) {
                $this->error("单次砍价最高金额{$error}");
            }
            // 帮砍次数
            $helpCutFlag = I('help_cut_flag');
            $helpCutCount = I('help_cut_count', '0');
            if ($helpCutFlag == '1') {
                if (! check_str($helpCutCount, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '1'), $error)) {
                    $this->error("帮助砍价次数{$error}");
                }
            }
            // 微信授权
            $wxAuthType = I('wx_auth_type');
            if (! check_str($wxAuthType, 
                array(
                    'null' => false, 
                    'strtype' => 'int', 
                    'minval' => '0', 
                    'maxval' => '1'), $error)) {
                $this->error("微信授权{$error}");
            }
            if ($wxAuthType == '1') { // 校验是否绑定了微信公众号
                $wxCheck = D('WeelCjSet')->isBindWxServ($this->nodeId);
                if (! $wxCheck)
                    $this->error('您还没有配置您的微信公众号');
                    // 是否关注
                $wxUrlFlag = I('wx_url_flag');
                if ($wxUrlFlag == '1') {
                    if (empty($guideUrl)) {
                        $this->error("请配置关注页链接");
                    }
                } else {
                    $fansCollectUrl = '';
                }
            }
            // 是否支持货到付款
            $deli_pay_flag = I('deli_pay_flag', 0, 'intval'); // 默认不支持
                                                              // 配送方式
            $deliveryFlag = I('post.delivery_flag', null);
            // 非商品类营销品 写死自提方式
            if ($goodsInfo['goods_type'] != '6')
                $deliveryFlag = array(
                    '0');
            if (! empty($deliveryFlag)) {
                $deliveryFlag = implode('-', $deliveryFlag);
            } else
                $this->error("未选择是否配送");
            $showFlag = I('post.show_flag', null);
            if ($showFlag == null)
                $showFlag = 0;
            
            $usingRules = I('post.using_rules');
            $mmsTitle = I('post.mms_title');
            $a = strstr($deliveryFlag, '0');
            if ($a !== false) {
                // 凭证结束时间
                $verify_time_type = I('post.verify_time_type', null);
                if ($verify_time_type == '0') {
                    $verify_end_date = I('post.verify_end_date', null);
                    if ($verify_end_date == null)
                        $this->error('需设置商品使用截至时间');
                    if (! check_str($verify_end_date, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("商品使用截至时间{$error}");
                    }
                    // 商品使用截至时间必须大于等于营销活动结束时间
                    if ($verify_end_date < $endDate) {
                        $this->error('商品使用截至时间必须大于等于营销活动结束时间');
                    }
                    $verify_end_time = $verify_end_date . '235959';
                } elseif ($verify_time_type == '1') {
                    $verify_end_days = I('post.verify_end_days', null);
                    if ($verify_end_days == null)
                        $this->error('需设置商品使用截至时间');
                    if (! check_str($verify_end_days, 
                        array(
                            'null' => false, 
                            'strtype' => 'int', 
                            'minval' => '0'), $error)) {
                        $this->error("商品使用截至天数{$error}");
                    }
                    $verify_end_time = $verify_end_days;
                }
                
                if (! check_str($usingRules, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => '100'), $error)) {
                    $this->error("彩信内容{$error}");
                }
                if (! check_str($mmsTitle, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => '10'), $error)) {
                    $this->error("彩信标题{$error}");
                }
            }
            // 商户logo
            $nodeLogo = I('post.node_logo');
            if (! check_str($nodeLogo, 
                array(
                    'null' => false), $error)) {
                $this->error("请上传商户logo");
            }
            // 商户名称
            $nodeName = I('post.node_name');
            if (! check_str($nodeName, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("请填写企业名称");
            }
            // 商品简介
            $goodsMemo = I('post.goods_memo');
            if (! check_str($goodsMemo, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("商品简介{$error}");
            }
            // 商品详情
            $wapInfo = I('post.wap_info');
            // 分享图片
            $sharePic = I('share_pic');
            $one_map = array(
                'name' => $batchName, 
                'batch_type' => $this->BATCH_TYPE, 
                'node_id' => $this->node_id);
            $info = M('tmarketing_info')->where($one_map)->count('id');
            if ($info > 0)
                $this->error("活动名称重复");
            
            M()->startTrans();
            $goodsInfo = M('tgoods_info')->where(
                array(
                    'goods_id' => $selectGoodsId))
                ->lock(true)
                ->find(); // tgoodsInfo 数据
            $data_arr = array(
                'name' => $batchName,  // 活动名称
                'start_time' => $startTime . '000000',  // 活动开始时间
                'end_time' => $endTime . '235959',  // 活动结束时间
                'node_id' => $this->node_id,  // 商户id
                'add_time' => date('YmdHis'),  // 增加时间
                'status' => '1',  // 活动状态默认正常
                'batch_type' => $this->BATCH_TYPE,  // 活动类型
                'market_price' => $mPrice, 
                'group_price' => $groupPrice, 
                'group_goods_name' => $goodsInfo['goods_name'], 
                'goods_num' => $goodsNum, 
                'goods_img' => $goodsInfo['goods_image'], 
                'defined_one_name' => $deliveryFlag, 
                'defined_two_name' => $showFlag, 
                'defined_three_name' => $cutLowPrice,  // 砍价最低金额
                'defined_four_name' => $onceCutPrice,  // 单次砍价最高金额
                'defined_five_name' => $wxAuthType,  // 微信授权
                'defined_six_name' => $helpCutCount,  // 帮砍次数
                'memo' => $goodsMemo, 
                'log_img' => $nodeLogo, 
                'is_new' => '1', 
                'wap_info' => $wapInfo, 
                'share_pic' => $sharePic, 
                'deli_pay_flag' => $deli_pay_flag, 
                'node_name' => $nodeName, 
                'fans_collect_url' => $wxUrlFlag);
            $batchId = M('tmarketing_info')->add($data_arr); // echo
                                                             // $resp_id;die;
            if (! $batchId) {
                M()->rollback();
                $this->error('活动创建失败！');
            }
            // 创建batch_info数据
            $batch_data = array(
                'batch_no' => $goodsInfo['batch_no'], 
                'batch_short_name' => $goodsInfo['goods_name'], 
                'batch_name' => $goodsInfo['goods_name'], 
                'node_id' => $this->nodeId, 
                'user_id' => $this->userId, 
                'batch_class' => '6',  // 商品销售类型
                'info_title' => $mmsTitle, 
                'use_rule' => $usingRules, 
                'sms_text' => $usingRules, 
                'batch_img' => $goodsInfo['goods_image'], 
                'batch_amt' => $groupPrice, 
                'begin_time' => $startTime . '000000', 
                'end_time' => '20301231235959', 
                // 'send_begin_date' => $startDate.'000000',
                // 'send_end_date' => $endDate.'235959',
                'verify_begin_date' => $startTime . '000000', 
                'verify_end_date' => $verify_end_time, 
                'verify_begin_type' => '0', 
                'verify_end_type' => $verify_time_type, 
                'add_time' => date('YmdHis'), 
                'status' => '0', 
                'goods_id' => $goodsInfo['goods_id'], 
                'storage_num' => $goodsNum, 
                'remain_num' => $goodsNum, 
                'batch_desc' => $goodsInfo['goods_desc'], 
                'm_id' => $batchId, 
                'validate_type' => $goodsInfo['validate_type']);
            $bInfoId = M('tbatch_info')->data($batch_data)->add();
            if (! $bInfoId) {
                M()->rollback();
                $this->error('系统出错,添加tbatch_info失败');
            }
            // 更新tgoods_info库存
            $goodsM = D('Goods');
            $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'], $goodsNum, 
                $bInfoId, '0', '新增吴刚砍树');
            if ($flag === false) {
                M()->rollback();
                $this->error($goodsM->getError());
            }
            // v0.5用户赠送旺币
            if ($this->wc_version == 'v0.5' && date('Ymd') <= C(
                'CUTDATE.WBDATE')) {
                $count = M('tmarketing_info')->where(
                    "batch_type='{$this->BATCH_TYPE}' AND node_id='{$this->nodeId}'")->count();
                if (($count - 1) === 0) { // 事务不提交也会算一条记录,所以减1
                    $WheelM = D('Wheel');
                    $result = $WheelM->setWb($this->nodeId, 200, 
                        C('CUTDATE.WBDATE'), '32');
                    if (! $result) {
                        M()->rollback();
                        $this->error('系统出错,旺币赠送失败');
                    }
                }
            }
            // 记录活动变更流水
            $marketTraceData = array(
                'batch_id' => $batchId, 
                'batch_type' => $this->BATCH_TYPE, 
                'name' => $batchName, 
                'start_time' => $startTime . '000000', 
                'end_time' => $endTime . '235959', 
                'memo' => $goodsMemo, 
                'defined_one_name' => $deliveryFlag, 
                'defined_two_name' => $showFlag, 
                'defined_three_name' => '1',  // 该值只是用于编辑记录显示
                'market_price' => $mPrice, 
                'group_price' => $groupPrice, 
                'goods_num' => $goodsNum, 
                'buy_num' => $buyNum, 
                'verify_begin_date' => $startTime . '000000', 
                'verify_end_date' => $verify_end_time, 
                'verify_begin_type' => '0', 
                'verify_end_type' => $verify_time_type, 
                'modify_time' => date('YmdHis'), 
                'modify_type' => '0', 
                'oper_id' => $this->user_id);
            $result = M('tmarketing_change_trace')->add($marketTraceData);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,记录活动变更流水失败');
            }
            M()->commit();
            $this->success('活动创建成功');
            exit();
        }
        $nodeLogo = get_node_info($this->node_id, 'head_photo');
        $this->assign('node_logo', $nodeLogo);
        $this->assign('guideUrl', $guideUrl);
        $this->assign('hasPayModule', $this->hasPayModule('m2'));
        $this->display();
    }

    public function edit() {
        $id = I('id', null, 'mysql_real_escape_string'); // echo "$id";die;
        $map = array(
            "g.id" => $id, 
            'g.batch_type' => $this->BATCH_TYPE, 
            "g.node_id" => $this->node_id);
        $row = M()->table("tmarketing_info g")->field(
            'g.*,i.info_title,i.use_rule,i.verify_end_date,i.verify_end_type,u.goods_id,i.storage_num,u.goods_name,u.remain_num,u.storage_type,u.goods_type')
            ->join("tbatch_info i ON g.id=i.m_id AND g.node_id=i.node_id")
            ->join("tgoods_info u on u.goods_id = i.goods_id")
            ->where($map)
            ->find();
        if (! $row)
            $this->error('未找到该活动');
            // 活动是否有人参
        $joinCount = M('twx_cuttree_info')->where(
            "node_id='{$this->nodeId}' AND m_id={$row['id']}")->count();
        $guideUrl = M('tweixin_info')->where("node_id='{$this->nodeId}'")->getField(
            'guide_url'); // 关注页链接
        if ($this->ispost()) {
            $error = '';
            $batchName = I('name');
            if (! check_str($batchName, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '8'), $error)) {
                $this->error("活动名称{$error}");
            }
            $startTime = I('start_time');
            if (! check_str($startTime, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $endTime = I('end_time');
            if (! check_str($endTime, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动结束时间{$error}");
            }
            // 免费用户结束时间卡住
            if (! $this->hasPayModule('m2')) {
                if ($endTime > C('CUTDATE.ENDDATE'))
                    $this->error('结束时间不能超过' . C('CUTDATE.ENDDATE'));
            }
            if ($endTime < date('Ymd'))
                $this->error('活动截止日期不能小于当前日期');
                // 商品总数
            $goodsNumFlag = I('post.goods_num_limit', null);
            $goodsNum = I('post.goods_num', null);
            if ($goodsNumFlag == 1) {
                $goodsNum = - 1;
            } else {
                if (! check_str($goodsNum, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '0'), $error)) {
                    $this->error("商品总数{$error}" . $goodsNumFlag);
                }
            }
            // 商品市场价格
            $mPrice = I('post.market_price', null);
            $mPriceFlag = I('post.market_price_flag', null);
            if ($mPriceFlag == 0)
                $mPrice = 0;
            else {
                if (! check_str($mPrice, 
                    array(
                        'null' => false, 
                        'strtype' => 'number', 
                        'minval' => '0.01'), $error)) {
                    $this->error("市场价格{$error}");
                }
            }
            if ($row['defined_five_name'] == '1') { // 校验是否绑定了微信公众号
                                                    // 是否关注
                $wxUrlFlag = I('wx_url_flag');
                if ($wxUrlFlag == '1') {
                    if (empty($guideUrl)) {
                        $this->error("请配置关注页链接");
                    }
                } else {
                    $fansCollectUrl = '';
                }
            }
            // 是否支持货到付款
            $deli_pay_flag = I('deli_pay_flag', 0, 'intval'); // 默认不支持
                                                              // 商品简介
            $goodsMemo = I('post.goods_memo');
            if (! check_str($goodsMemo, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("商品简介{$error}");
            }
            // 商户logo
            $nodeLogo = I('post.node_logo');
            if (! check_str($nodeLogo, 
                array(
                    'null' => false), $error)) {
                $this->error("请上传商户logo");
            }
            // 商户名称
            $nodeName = I('post.node_name');
            if (! check_str($nodeName, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("请填写企业名称");
            }
            // 商品详情
            $wapInfo = I('post.wap_info');
            // 活动有人参与无法修改销售价,砍价最低金额,单次砍价最高金额
            if ($joinCount > 0) {
                $groupPrice = $row['group_price'];
                $cutLowPrice = $row['defined_three_name'];
                $onceCutPrice = $row['defined_four_name'];
            } else {
                $groupPrice = I('post.group_price', null);
                if (! check_str($groupPrice, 
                    array(
                        'null' => false, 
                        'strtype' => 'number', 
                        'minval' => '0'), $error)) {
                    $this->error("商品销售价{$error}");
                }
                // 砍价最低金额
                $cutLowPrice = I('cut_low_price');
                if (! check_str($cutLowPrice, 
                    array(
                        'null' => false, 
                        'strtype' => 'number', 
                        'minval' => '0'), $error)) {
                    $this->error("砍价最低金额{$error}");
                }
                if ($cutLowPrice >= $groupPrice)
                    $this->error('砍价最低金额不能大于商品销售价');
                    // 单次砍价最高金额
                $onceCutPrice = I('once_cut_price');
                if (! check_str($onceCutPrice, 
                    array(
                        'null' => false, 
                        'strtype' => 'number', 
                        'minval' => '0.01', 
                        'maxval' => $groupPrice - $cutLowPrice), $error)) {
                    $this->error("单次砍价最高金额{$error}");
                }
            }
            // 帮砍次数
            $helpCutFlag = I('help_cut_flag');
            $helpCutCount = I('help_cut_count');
            if ($helpCutFlag == '1') {
                if (! check_str($helpCutCount, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '1'), $error)) {
                    $this->error("帮助砍价次数{$error}");
                }
            }
            $deliveryFlag = I('post.delivery_flag', null);
            // 非商品类营销品 写死自提方式
            if ($row['goods_type'] != '6')
                $deliveryFlag = array(
                    '0');
            if (! empty($deliveryFlag)) {
                $deliveryFlag = implode('-', $deliveryFlag);
            } else {
                $this->error("未选择是否配送");
            }
            $mmsTitle = I('post.mms_title');
            $usingRules = I('post.using_rules');
            $a = strstr($deliveryFlag, '0');
            if ($a !== false) {
                // 凭证结束时间
                $verify_time_type = I('post.verify_time_type', null);
                if ($verify_time_type == '0') {
                    $verify_end_date = I('post.verify_end_date', null);
                    if ($verify_end_date == null)
                        $this->error('需设置商品使用截至时间');
                    if (! check_str($verify_end_date, 
                        array(
                            'null' => false, 
                            'strtype' => 'datetime', 
                            'format' => 'Ymd'), $error)) {
                        $this->error("商品使用截至时间{$error}");
                    }
                    // 商品使用截至时间必须大于等于营销活动结束时间
                    if ($verify_end_date < $endDate) {
                        $this->error('商品使用截至时间必须大于等于营销活动结束时间');
                    }
                    $verify_end_time = $verify_end_date . '235959';
                } elseif ($verify_time_type == '1') {
                    $verify_end_days = I('post.verify_end_days', null);
                    if ($verify_end_days == null)
                        $this->error('需设置商品使用截至时间');
                    if (! check_str($verify_end_days, 
                        array(
                            'null' => false, 
                            'strtype' => 'int', 
                            'minval' => '0'), $error)) {
                        $this->error("商品使用截至天数{$error}");
                    }
                    $verify_end_time = $verify_end_days;
                }
                if (! check_str($usingRules, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => '100'), $error)) {
                    $this->error("彩信内容{$error}");
                }
                
                if (! check_str($mmsTitle, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => '10'), $error)) {
                    $this->error("彩信标题{$error}");
                }
            }
            $one_map = array(
                'name' => $batchName, 
                'batch_type' => $this->BATCH_TYPE, 
                'node_id' => $this->node_id, 
                'id' => array(
                    'neq', 
                    $row['id']));
            $info = M('tmarketing_info')->where($one_map)->count('id');
            if ($info > 0)
                $this->error("活动名称重复");
                // 判断库存
            if ($row['storage_type'] != 0) {
                if (($goodsNum - $row['storage_num']) > $row['remain_num'])
                    $this->error("库存不足");
            }
            $batchInfo = M('tbatch_info')->where(
                "node_id='{$this->node_id}' AND m_id='{$id}'")->find();
            if ($goodsNumFlag != 1) { // 限库存
                if (($goodsNum <
                     ($batchInfo['storage_num'] - $batchInfo['remain_num'])) &&
                     $batchInfo['storage_num'] != - 1)
                    $this->error('商品总数需大于等于已售商品与当前锁定商品总和');
            }
            M()->startTrans();
            $data = array(
                'name' => $batchName, 
                'start_time' => $startTime . '000000',  // 活动开始时间
                'end_time' => $endTime . '235959',  // 活动结束时间
                'group_price' => $groupPrice, 
                'defined_three_name' => $cutLowPrice, 
                'defined_four_name' => $onceCutPrice, 
                'defined_six_name' => $helpCutCount,  // 帮砍时间
                'market_price' => $mPrice, 
                'deli_pay_flag' => $deli_pay_flag, 
                'memo' => $goodsMemo, 
                'log_img' => $nodeLogo, 
                'wap_info' => $wapInfo, 
                'goods_num' => $goodsNum, 
                'defined_one_name' => $deliveryFlag, 
                'node_name' => $nodeName, 
                'fans_collect_url' => $wxUrlFlag);
            $result = M('tmarketing_info')->where("id={$id}")->save($data);
            if ($result === false) {
                M()->rollback();
                $this->error('数据库出错,更新失败');
            }
            // 更新tbatch_info表
            $remain_num = $batchInfo['remain_num'] +
                 ($goodsNum - $row['storage_num']);
            $result = M('tbatch_info')->where(
                "node_id='{$this->nodeId}' and m_id='{$id}'")->save(
                array(
                    'verify_end_type' => $verify_time_type, 
                    'verify_end_date' => $verify_end_time, 
                    'info_title' => $mmsTitle, 
                    'use_rule' => $usingRules, 
                    'storage_num' => $goodsNum, 
                    'remain_num' => $remain_num, 
                    'batch_amt' => $groupPrice));
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,更新tbatch_info失败!');
            }
            // 更新库存
            if ($goodsNum != $row['storage_num']) {
                // 更新tgoods_info库存
                $goodsM = D('Goods');
                $flag = $goodsM->storagenum_reduc($row['goods_id'], 
                    $goodsNum - $row['storage_num'], $batchInfo['id'], '0', 
                    '吴刚砍树编辑');
                if ($flag === false) {
                    M()->rollback();
                    $this->error($goodsM->getError());
                }
            }
            // 记录活动变更流水
            $marketTraceData = array(
                'batch_id' => $row['id'], 
                'batch_type' => $this->BATCH_TYPE, 
                'name' => $batchName, 
                'start_time' => $startTime . '000000', 
                'end_time' => $endTime . '235959', 
                'memo' => $goodsMemo, 
                'defined_one_name' => $deliveryFlag, 
                'defined_two_name' => $row['defined_two_name'], 
                'defined_three_name' => '1',  // 该值只是用于编辑记录显示
                'market_price' => $mPrice, 
                'group_price' => $groupPrice, 
                'goods_num' => $goodsNum, 
                'buy_num' => $row['buy_num'], 
                'verify_end_date' => $verify_end_time, 
                'verify_end_type' => $verify_time_type, 
                'modify_time' => date('YmdHis'), 
                'modify_type' => '0', 
                'oper_id' => $this->user_id);
            $result = M('tmarketing_change_trace')->add($marketTraceData);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,记录活动变更流水失败');
            }
            // 更新成功
            M()->commit();
            $this->success('数据更新成功');
            exit();
        }
        
        // 商户名称
        $nodeName = M('tnode_info')->where("node_id='{$this->node_id}'")->getField(
            'node_name');
        $this->assign('guideUrl', $guideUrl);
        $this->assign('row', $row);
        $this->assign('node_name', $nodeName);
        $this->assign('joinCount', $joinCount);
        $this->assign('hasPayModule', $this->hasPayModule('m2'));
        $this->display();
    }
    
    // 状态修改
    public function editBatchStatus() {
        $batchId = I('post.batch_id', null, 'intval');
        $status = I('post.status', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $result = M('tmarketing_info')->where(
            "node_id in ({$this->nodeIn()}) AND id='{$batchId}'")->find();
        if (! $result) {
            $this->error('未找到该活动');
        }
        if ($status == '1') {
            $data = array(
                'status' => '1');
        } else {
            $data = array(
                'status' => '2');
        }
        $result = M('tmarketing_info')->where("id='{$batchId}'")->save($data);
        if ($result) {
            node_log('投票活动状态更改|活动ID：' . $batchId);
            $this->success('更新成功', 
                array(
                    '返回' => U('Vote/index')));
        } else {
            $this->error('更新失败');
        }
    }
    // 手机发送动态密码
    public function sendCheckCode() {
        $expiresTime = '90'; // 手机发送间隔
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        // 测试环境不下发，验证码直接为1111
        if (! is_production()) {
            $cutCheckCode = array(
                'number' => 1111, 
                'add_time' => time(), 
                'phoneNo' => $phoneNo);
            session('cutCheckCode', $cutCheckCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }
        // 发送频率验证
        $cutCheckCode = session('cutCheckCode');
        if (! empty($cutCheckCode) &&
             (time() - $cutCheckCode['add_time']) < $expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $text = "您在{$this->node_short_name}商户的动态密码是：{$num}。如非本人操作请忽略。";
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $phoneNo),  // 手机号
                'SendClass' => 'SMS', 
                'MessageText' => $text,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('ALIPAY_NOTICE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error('发送失败');
        }
        $cutCheckCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('cutCheckCode', $cutCheckCode);
        $this->success('动态密码已发送');
    }

    public function checkPhone() {
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        $checkPhone = I('post.checkphone', null);
        $cutCheckCode = session('cutCheckCode');
        if ($cutCheckCode['number'] != $checkPhone ||
             $cutCheckCode['phoneNo'] != $phoneNo) {
            $this->error('您输入的信息有误,验证失败');
        }
        session('cutCheckedPhone', $cutCheckCode['phoneNo']);
        $cutCheckCode = session('cutCheckCode', null);
        $this->success('验证成功');
    }
}