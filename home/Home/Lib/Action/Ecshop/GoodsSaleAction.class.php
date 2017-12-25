<?php

/**
 * 闪购
 *
 * @author bao
 */
class GoodsSaleAction extends BaseAction {
    
    // 活动类型
    public $BATCH_TYPE = '26';
    // 图片路径
    public $img_path;

    public $isInterGral = false;

    //开通自定义短信的标志
    public $startUp = 0;

    // 积分权限
    public function _initialize() {
        parent::_initialize();
        // 验证是否开通商品销售服务
        
        $node_info = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->startUp = $node_info['custom_sms_flag'];
        $hasEcshop = $this->_hasMoonDayEcshop();
        if ($hasEcshop != true)
            $this->error("您未开通多宝电商服务模块。");
        if (! $node_info['receive_phone']) {
            $this->error("您的接受通知手机号为空，请至多宝电商配置处补齐。", 
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
        
        $path_arr = C('BATCH_IMG_PATH_NAME');
        $img_path = $path_arr[$this->BATCH_TYPE] . '/' . $this->node_id . '/';
        $this->img_path = $img_path;
        
        // 判断积分权限
        $this->isInterGral = $this->_hasIntegral($this->node_id);
        // 取得积分规则信息
        $intergralType = D('SalePro', 'Service')->getNodeRule($this->node_id, 
            'tintegral_rule_main');
        if ('0' === $intergralType)
            $this->isInterGral = false;
            
            // 临时路径
        $tmp_path = 'Upload/img_tmp' . '/' . $this->node_id . '/';
        $this->assign('img_path', $img_path);
        $this->assign('isIntergral', $this->isInterGral); // 订购权限
        $this->assign('node_info', $node_info);
        $this->assign('tmp_path', $tmp_path);
    }

    public function index() {
        $model = M('tmarketing_info');
        $map = array(
            'm.node_id' => array(
                'exp', 
                "in (" . $this->nodeIn() . ")"));
        
        $data = $_REQUEST;
        
        if ($data['key'] != '') {
            $map['m.name'] = array(
                'like', 
                '%' . $data['key'] . '%');
        }
        if ($data['status'] != '') {
            $map['m.status'] = $data['status'];
        }
        // 处理特殊查询字段
        $beginDate = I('start_time', null, 'mysql_real_escape_string,trim');
        if (! empty($beginDate)) {
            $map['m.start_time'] = array(
                'egt', 
                $beginDate . '000000');
        }
        $endDate = I('end_time', null, 'mysql_real_escape_string,trim');
        if (! empty($endDate)) {
            $map['m.end_time'] = array(
                'elt', 
                $endDate . '235959');
        }
        $map['m.batch_type'] = $this->BATCH_TYPE;
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table("tmarketing_info m")->where($map)->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
        
        foreach ($data as $key => $val) {
            $Page->parameter .= "&$key=" . urlencode($val) . '&';
        }
        
        $show = $Page->show(); // 分页显示输出

        $list = M()->table("tmarketing_info m")->field('m.*,b.storage_num,b.remain_num')
            ->join('tbatch_info b on b.m_id=m.id')
            ->where($map)
            ->order('m.id desc')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        // 创建sku信息
        $skuObj = D('Sku', 'Service');
        foreach ($list as &$v) {
            // 锁定数量
            $lock_count = M('ttg_order_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_no' => $v['id'], 
                    'order_status' => '0', 
                    'pay_status' => '1'))->sum('buy_num');
            $sale_count = M('ttg_order_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_no' => $v['id'], 
                    'order_status' => '0', 
                    'pay_status' => '2'))->sum('buy_num');
            if (! $lock_count) {
                $v['lock_num'] = 0;
            } else {
                $v['lock_num'] = $lock_count;
            }
            
            if (! $sale_count) {
                $v['sale_num'] = 0;
            } else {
                $v['sale_num'] = $sale_count;
            }
            // sku商品
            if ("1" === $val['is_sku']) {
                $val = $skuObj->makeGoodsListInfo($val, $this->node_id, true, 
                    $val['id'], $val['m_id']);
            }
        }
        
        $channelModel = M('tchannel');
        $channelId = $channelModel->where(
            "node_id='{$this->nodeId}' AND type=4 AND sns_type=45 AND status=1")->getField(
            'id');
        if (! $channelId) {
            $data = array(
                'name' => '闪购列表', 
                'type' => '4', 
                'sns_type' => '45', 
                'status' => '1', 
                'node_id' => $this->nodeId, 
                'add_time' => date('YmdHis'));
            $channelId = $channelModel->add($data);
            if (! $channelId)
                $this->error('系统出错创建渠道失败');
        }
        
        $arr_ = C('CHANNEL_TYPE');
        // dump($list);
        $this->assign('arr_', $arr_);
        $this->assign('query_list', $list);
        $this->assign('page', $show); // 赋值分页输出
        $this->display(); // 输出模板
    }

    public function add() {
        if ($this->isPost()) {
            
            $error = '';
            $name = I('post.name', null);
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("活动名称{$error}");
            }
            $dataInfo = M('tmarketing_info')->where(
                "node_id='{$this->nodeId}' AND name='{$name}' and batch_type='" .
                     $this->BATCH_TYPE . "'")->find();
            if ($dataInfo)
                $this->error('活动名称已经存在');
            $startDate = I('post.start_time', null);
            if (! check_str($startDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $endDate = I('post.end_time', null);
            if (! check_str($endDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动结束时间{$error}");
            }
            if ($endDate < date('Ymd'))
                $this->error('活动截止日期不能小于当前日期');
                // if($endDate > '20140930') $this->error('活动截止日期不能大于9月30日');
            
            $goodsMemo = I('post.goods_memo', null);
            if (! check_str($goodsMemo, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("商品描述{$error}");
            }
            /*
             * $groupGoodsName = I('post.group_goods_name',null);
             * if(!check_str($groupGoodsName,array('null'=>false,'maxlen_cn'=>'20'),$error)){
             * $this->error("商品名称{$error}"); } $goodsImg =
             * I('post.resp_goods_img');
             * if(!check_str($goodsImg,array('null'=>false),$error)){
             * $this->error("商品图片{$error}"); } //移动图片 $img_move =
             * move_batch_image($goodsImg,$this->BATCH_TYPE,$this->node_id);
             * if($img_move !==true) $this->error('商品图片上传失败！'); $goodsImg =
             * $this->img_path .$goodsImg; $marketPrice =
             * I('post.market_price',null);
             * if(!check_str($marketPrice,array('null'=>false,'strtype'=>'number','minval'=>'0'),$error)){
             * $this->error("商品市场价{$error}"); }
             */
            // tgoods_info id
            $goods_id = I('post.select_goods_id', null);
            if (! $goods_id)
                $this->error("请选择卡券");
            $goodsInfo = M('tgoods_info')->where(
                array(
                    'id' => $goods_id))->find(); // tgoodsInfo 数据
                                                 // sku提交
            $is_sku = I('post.is_sku', null);
            if ('true' === $is_sku) {
                $skuMarkPrice = json_decode(
                    html_entity_decode(I('post.data_price_info', null)), true);
                if (! $skuMarkPrice) {
                    $this->error("该商品并不是sku商品");
                }
                $countNum = I('post.count_num', null);
                if ('-1' == $countNum) {
                    $goodsNum = - 1;
                    $goodsNumFlag = 1;
                } else {
                    $goodsNum = (int) $countNum;
                }
            } else {
                $groupPrice = I('post.group_price', null);
                if (! check_str($groupPrice, 
                    array(
                        'null' => false, 
                        'strtype' => 'number', 
                        'minval' => '0'), $error)) {
                    $this->error("商品销售价{$error}");
                }
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
                if ($goodsInfo['storage_type'] != 0 &&
                     $goodsNum > $goodsInfo['remain_num'])
                    $this->error('库存不足');
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
            $buyNum = I('post.buy_num', null);
            $buyNumFlag = I('post.buy_num_flag', null);
            if ($buyNumFlag == 0)
                $buyNum = 0;
            else {
                if (! check_str($buyNum, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '1'), $error)) {
                    $this->error("每日限购量{$error}");
                }
            }
            $buyCont = I('post.buy_cont', null);
            $buyNumUser = I('post.buy_num_user', null);
            if ($buyNumUser == 0)
                $buyCont = - 1;
            else {
                if (! check_str($buyCont, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '1'), $error)) {
                    $this->error("个人限购量{$error}");
                }
            }
            
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
                // 粉丝专享
            $member_join_flag = I('member_join_flag', 0, 'intval');
            $fans_collect_url = I('fans_collect_url', null);
            if ($member_join_flag == 1 && ! $fans_collect_url)
                $this->error('粉丝专享活动请输入关注引导页地址');
            $usingRules = I('post.using_rules');
            $mmsTitle = I('post.mms_title','商品');
            $send_gift = I('post.send_gift');
            $a = strstr($deliveryFlag, '0');
            // 送礼初始值
            $sendGift = 0;
            if ($a !== false) {
                // 是否送礼
                $sendGift = I('post.send_gift');
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
                    $this->error("商品使用说明{$error}");
                }
                /*
                if (! check_str($mmsTitle, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => '10'), $error)) {
                    $this->error("彩信标题{$error}");
                }
                */
            }
            /*
             * if($groupPrice > $marketPrice) $this->error("销售价格不得大于市场价格");
             */
            $size = I('post.size', null);
            $isCodeImg = I('post.is_code_img', null);
            $respCodeImg = I('post.resp_code_img', null);
            if ($isCodeImg != '1')
                $respCodeImg = '';
                // $isCj = I('post.is_cj',null);
            $snsType = I('post.sns_type');
            if (! empty($snsType)) {
                $snsType = implode('-', $snsType);
            }
            
            // 是否可以使用红包
            $bonusFlag = I('bonus_flag', 1, 'intval'); // 默认可以使用
                                                       // 判断积分是否开启
            $integralFlag = I('integral_flag', 0, 'intval'); // 默认不开启
            if (! $this->isInterGral) {
                $integralFlag = 0;
            }
            // 是否支持货到付款
            $deli_pay_flag = I('deli_pay_flag', 0, 'intval'); // 默认不支持
                                                              // dump($_POST);exit;
                                                              // 订购商品不允许货到付款和自提
            if ('2' == $goodsInfo['is_order']) {
                $deli_pay_flag = 0;
                $deliveryFlag = 1;
            }
            
            M()->startTrans();
            $goodsInfo = M('tgoods_info')->where(
                array(
                    'id' => $goods_id))
                ->lock(true)
                ->find(); // tgoodsInfo 数据
                          // tmarketing_info数据创建
            $market_data = array(
                'name' => $name, 
                'batch_type' => $this->BATCH_TYPE, 
                'node_id' => $this->nodeId, 
                'start_time' => $startDate . '000000', 
                'end_time' => $endDate . '235959', 
                'market_price' => $mPrice, 
                'group_price' => $groupPrice, 
                'group_goods_name' => $goodsInfo['goods_name'], 
                'goods_num' => $goodsNum, 
                'buy_num' => $buyNum, 
                'goods_img' => $goodsInfo['goods_image'], 
                'size' => $size, 
                'code_img' => $respCodeImg, 
                // 'is_cj' => $isCj,
                'memo' => $goodsMemo, 
                'sns_type' => $snsType, 
                'defined_one_name' => $deliveryFlag, 
                'defined_two_name' => $showFlag, 
                'defined_three_name' => $buyCont, 
                'status' => '1', 
                'add_time' => date('YmdHis'), 
                'member_join_flag' => $member_join_flag, 
                'fans_collect_url' => $fans_collect_url, 
                'bonus_flag' => $bonusFlag, 
                'integral_flag' => $integralFlag, 
                'config_data' => strstr($deliveryFlag, '0') ||
                     $deliveryFlag == '0' ? serialize(
                        array(
                            'send_gift' => $sendGift)) : serialize(
                        array(
                            'send_gift' => '0')),  // 送礼开关
                    'deli_pay_flag' => strstr($deliveryFlag, '1') ? $deli_pay_flag : 0); // 物流支持货到付款
            
            $batchId = M('tmarketing_info')->add($market_data);
            if (! $batchId) {
                M()->rollback();
                $this->error('系统出错,添加marketing_info失败');
            }
            // 创建batch_info数据
            $batch_data = array(
                'batch_no' => $goodsInfo['batch_no'], 
                'batch_short_name' => $goodsInfo['goods_name'], 
                'batch_name' => $goodsInfo['goods_name'], 
                'node_id' => $this->nodeId, 
                'user_id' => $this->userId, 
                // 'material_code' => $materialCode,
                'batch_class' => '6',  // 商品销售类型
                'info_title' => $mmsTitle, 
                'use_rule' => $usingRules, 
//                'sms_text' => $usingRules,
                'batch_img' => $goodsInfo['goods_image'], 
                'batch_amt' => $groupPrice, 
                'begin_time' => $startDate . '000000', 
                'end_time' => '20301231235959', 
                // 'send_begin_date' => $startDate.'000000',
                // 'send_end_date' => $endDate.'235959',
                'verify_begin_date' => $startDate . '000000', 
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
            //自定义短信内容
            if($deliveryFlag == '0' || $deliveryFlag == '0-1') {
                if ($this->startUp == 1) {
                    $sms_text = I('cusMsg', '');
                    if (empty($sms_text)) {
                        M()->rollback();
                        $this->error('短信内容不能空！');
                    } else {
                        $batch_data['sms_text'] = $sms_text;
                    }
                }
            }

            $bInfoId = M('tbatch_info')->data($batch_data)->add();
            if (! $bInfoId) {
                M()->rollback();
                $this->error('系统出错,添加tbatch_info失败');
            }
            // 更新tgoods_info库存
            $goodsM = D('Goods');
            $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'], $goodsNum, 
                $bInfoId, '0', '新增闪购');
            if ($flag === false) {
                M()->rollback();
                $this->error('系统出错,更新tgoods_info库存失败');
            }
            
            // 添加sku上架信息
            if ('true' === $is_sku) {
                // 创建sku链接
                $skuObj = D('Sku', 'Service');
                $info = $skuObj->addGoodsSkuInfo($skuMarkPrice, $this->nodeId, 
                    $batchId, $bInfoId);
                if (false == $info) {
                    M()->rollback();
                    $this->error($skuObj->getError());
                }
            }
            // 记录活动变更流水
            $marketTraceData = array(
                'batch_id' => $batchId, 
                'batch_type' => $this->BATCH_TYPE, 
                'name' => $name, 
                'start_time' => $startDate . '000000', 
                'end_time' => $endDate . '235959', 
                'memo' => $goodsMemo, 
                'defined_one_name' => $deliveryFlag, 
                'defined_two_name' => $showFlag, 
                'defined_three_name' => $buyCont, 
                'market_price' => $mPrice, 
                'group_price' => $groupPrice, 
                'goods_num' => $goodsNum, 
                'buy_num' => $buyNum, 
                'verify_begin_date' => $startDate . '000000', 
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
            // 发布至默认闪购渠道
            $channelId = M('tchannel')->where(
                "node_id='{$this->nodeId}' AND type=4 AND sns_type=45 AND status=1")->getField(
                'id');
            $data = array(
                'batch_type' => $this->BATCH_TYPE, 
                'batch_id' => $batchId, 
                'channel_id' => $channelId, 
                'add_time' => date('YmdHis'), 
                'node_id' => $this->nodeId);
            $result = M('tbatch_channel')->add($data);
            if (! $result) {
                M()->rollback();
                $this->error('系统出错,发布默认渠道失败');
            }
            M()->commit();
            $this->ajaxReturn(
                array(
                    'url' => U('LabelAdmin/BindChannel/index', 
                        array(
                            'batch_type' => $this->BATCH_TYPE, 
                            'batch_id' => $batchId))), '创建成功！', 1);
            exit();
        }
        //获取当前机构的资费标准
        $sendPrice = D('ActivityPayConfig')->getSendFee($this->node_id);
        $this->assign('sendPrice', $sendPrice);
        $this->assign('startUp', $this->startUp);
        $this->display();
    }

    public function edit() {
        $id = I('id', null, 'mysql_real_escape_string');
        if (empty($id))
            $this->error('错误参数');
        $where = array(
            'g.node_id' => $this->nodeId, 
            'g.batch_type' => $this->BATCH_TYPE, 
            'g.id' => $id);
        $groupInfo = M()->table("tmarketing_info g")->field(
            'g.*,i.info_title,i.use_rule,i.verify_end_date,i.verify_end_type,i.sms_text,u.id as goods_id, u.is_order,i.storage_num,u.goods_name,u.remain_num,u.storage_type,u.goods_type,u.goods_id as goods_no')
            ->join("tbatch_info i ON g.id=i.m_id AND g.node_id=i.node_id")
            ->join("tgoods_info u on u.goods_id = i.goods_id")
            ->where($where)
            ->find();
        
        if (empty($groupInfo))
            $this->error('未找到该活动!');
            
            // 创建sku信息
        $skuObj = D('Sku', 'Service');
        unset($map);
        $skuInfoList = $skuObj->getSkuEcshopList($id, $this->nodeId);
        // 判断是否有sku数据
        $goodsSkuTypeList = '';
        $goodsSkuDetailList = '';
        $skuDetail = '';
        $isCycle = $groupInfo['is_order']; // 是否订购
        
        if (NULL === $skuInfoList) {
            $isSku = false;
        } else {
            $isSku = true;
            // 分离商品表中的规格和规格值ID
            $goods_sku_list = $skuObj->getReloadSku($skuInfoList);
            // 取得规格值表信息
            if (is_array($goods_sku_list['list']))
                $goodsSkuDetailList = $skuObj->getSkuDetailList(
                    $goods_sku_list['list']);
                // 取得规格表信息
            if (is_array($goodsSkuDetailList))
                $goodsSkuTypeList = $skuObj->getSkuTypeList($goodsSkuDetailList);
                
                // 价格列表
            $skuDetail = $skuObj->makeSkuList($skuInfoList);
        }

        //自定义短信内容
        if ($this->isPost()) {
            $error = '';
            $name = I('post.name', null);
            if (! check_str($name, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '20'), $error)) {
                $this->error("活动名称{$error}");
            }
            $dataInfo = M('tmarketing_info')->where(
                "node_id='{$this->nodeId}' AND name='{$name}' AND id<>'{$id}' AND batch_type='" .
                     $this->BATCH_TYPE . "'")->find();
            if ($dataInfo)
                $this->error('活动名称已经存在');
                // tgoods_info id
            $goods_id = I('post.select_goods_id', null);
            if (! $goods_id)
                $this->error("请选择卡券");
            $goodsInfo = M('tgoods_info')->where(
                array(
                    'id' => $goods_id))->find(); // tgoodsInfo 数据
            
            $startDate = I('post.start_time', null);
            if (! check_str($startDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动开始时间{$error}");
            }
            $endDate = I('post.end_time', null);
            if (! check_str($endDate, 
                array(
                    'null' => false, 
                    'strtype' => 'datetime', 
                    'format' => 'Ymd'), $error)) {
                $this->error("活动结束时间{$error}");
            }
            if ($endDate < date('Ymd'))
                $this->error('活动截止日期不能小于当前日期');
                // if($endDate > '20140930') $this->error('活动截止日期不能大于9月30日');
            $goodsMemo = I('post.goods_memo', null);
            if (! check_str($goodsMemo, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '200'), $error)) {
                $this->error("商品描述{$error}");
            }
            /*
             * $groupGoodsName = I('post.group_goods_name',null);
             * if(!check_str($groupGoodsName,array('null'=>false,'maxlen_cn'=>'20'),$error)){
             * $this->error("商品名称{$error}"); } $goodsImg =
             * I('post.resp_goods_img');
             * if(!check_str($goodsImg,array('null'=>false),$error)){
             * $this->error("商品图片{$error}"); } $goodsMemo =
             * I('post.goods_memo',null);
             * if(!check_str($goodsMemo,array('null'=>false,'maxlen_cn'=>'200'),$error)){
             * $this->error("商品描述{$error}"); } $marketPrice =
             * I('post.market_price',null);
             * if(!check_str($marketPrice,array('null'=>false,'strtype'=>'number','minval'=>'0'),$error)){
             * $this->error("商品市场价{$error}"); }
             */
            
            // sku提交
            $is_sku = I('post.is_sku', null);
            if ('1' === $is_sku) {
                $skuMarkPrice = json_decode(
                    html_entity_decode(I('post.data_price_info', null)), true);
                if (! $skuMarkPrice) {
                    $this->error("该商品并不是sku商品");
                }
                $countNum = I('post.count_num', null);
                if ('-1' == $countNum) {
                    $goodsNum = - 1;
                    $goodsNumFlag = 1;
                } else {
                    $goodsNum = (int) $countNum;
                }
                // 检查商品库存数
                $returnInfo = $skuObj->checkRemainNum($skuMarkPrice, 
                    $skuInfoList);
                if (false === $returnInfo['msg'])
                    $this->error($returnInfo['info']);
            } else {
                $groupPrice = I('post.group_price', null);
                if (! check_str($groupPrice, 
                    array(
                        'null' => false, 
                        'strtype' => 'number', 
                        'minval' => '0'), $error)) {
                    $this->error("商品销售价{$error}");
                }
                // 商品总数
                $goodsNumFlag = I('post.goods_num_limit', null);
                $goodsNum = I('post.goods_num', null);
                if ($goodsNumFlag == 1) {
                    $goodsNum = - 1;
                } else {
                    // 商品下架后编辑
                    if (isset($groupInfo['storage_num'])) {
                        if ('-1' == $groupInfo['storage_num'])
                            $goodsNumFlag = 1;
                        else
                            $goodsNumFlag = 0;
                        // $goodsNum = $groupInfo['storage_num'];
                    } else {
                        if (! check_str($goodsNum, 
                            array(
                                'null' => false, 
                                'strtype' => 'int', 
                                'minval' => '0'), $error)) {
                            $this->error("商品总数{$error}" . $goodsNumFlag);
                        }
                    }
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
            $buyNum = I('post.buy_num', null);
            $buyNumFlag = I('post.buy_num_flag', null);
            if ($buyNumFlag == 0)
                $buyNum = 0;
            else {
                if (! check_str($buyNum, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '1'), $error)) {
                    $this->error("每日限购量{$error}");
                }
            }
            $buyCont = I('post.buy_cont', null);
            $buyNumUser = I('post.buy_num_user', null);
            if ($buyNumUser == 0) {
                $buyCont = - 1;
            } else {
                if (! check_str($buyCont, 
                    array(
                        'null' => false, 
                        'strtype' => 'int', 
                        'minval' => '1'), $error)) {
                    $this->error("个人限购量{$error}");
                }
            }
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
                // 粉丝专享
            $member_join_flag = I('member_join_flag', 0, 'intval');
            $fans_collect_url = I('fans_collect_url', null);
            if ($member_join_flag == 1 && ! $fans_collect_url)
                $this->error('粉丝专享活动请输入关注引导页地址');
                // 彩信标题
            $infotitle = I('post.mms_title','商品');
            // 彩信内容
            $usingRules = I('post.using_rules');
            $a = strstr($deliveryFlag, '0');
            // 送礼初始值
            $sendGift = 0;
            if ($a !== false) {
                // 是否送礼
                $sendGift = I('post.send_gift');
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
                /*
                if (! check_str($infotitle, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => '10'), $error)) {
                    $this->error("彩信标题{$error}");
                }
                */
                if (! check_str($usingRules, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => '100'), $error)) {
                    $this->error("商品使用说明{$error}");
                }
            }
            // 是否可以使用红包
            $bonusFlag = I('bonus_flag', 1, 'intval'); // 默认可以使用
                                                       // 判断积分是否开启
            $integralFlag = I('integral_flag', 0, 'intval'); // 默认不开启
            if (! $this->isInterGral) {
                $integralFlag = 0;
            }
            // 是否支持货到付款
            $deli_pay_flag = I('deli_pay_flag', 0, 'intval'); // 默认不支持
                                                              // 订购商品不允许货到付款和自提
            if ('2' == $isCycle) {
                $deli_pay_flag = 0;
                $deliveryFlag = 1;
            }
            /*
             * if($groupPrice > $marketPrice) $this->error("销售价格不得大于市场价格");
             */
            // 判断库存
            if ($goodsInfo['storage_type'] != 0) {
                if (($goodsNum - $groupInfo['storage_num'] >
                     $goodsInfo['remain_num']) && $goodsInfo['remain_num'] != - 1)
                    $this->error("库存不足");
            }
            $size = I('post.size', null);
            $isCodeImg = I('post.is_code_img', null);
            $respCodeImg = I('post.resp_code_img', null);
            if ($isCodeImg != '1')
                $respCodeImg = '';
                // $isCj = I('post.is_cj',null);
                // $isRest = I('post.is_reset');
            $snsType = I('post.sns_type');
            if (! empty($snsType)) {
                $snsType = implode('-', $snsType);
            }
            // tmartketing_info数据创建
            $data = array(
                'name' => $name, 
                'node_id' => $this->nodeId, 
                'start_time' => $startDate . '000000', 
                'end_time' => $endDate . '235959', 
                'market_price' => $mPrice, 
                'group_price' => $groupPrice, 
                // 'group_goods_name' => $goodsInfo['goods_name'],
                'goods_num' => $goodsNum, 
                'buy_num' => $buyNum, 
                // 'goods_img' => $goodsInfo['goods_image'],
                'size' => $size, 
                'code_img' => $respCodeImg, 
                'memo' => $goodsMemo, 
                'sns_type' => $snsType, 
                'defined_one_name' => $deliveryFlag, 
                'defined_two_name' => $showFlag, 
                'defined_three_name' => $buyCont, 
                'add_time' => date('YmdHis'), 
                'member_join_flag' => $member_join_flag, 
                'fans_collect_url' => $fans_collect_url, 
                'bonus_flag' => $bonusFlag, 
                'integral_flag' => $integralFlag, 
                'config_data' => strstr($deliveryFlag, '0') ||
                     $deliveryFlag == '0' ? serialize(
                        array(
                            'send_gift' => $sendGift)) : serialize(
                        array(
                            'send_gift' => '0')),  // 送礼开关
                    'deli_pay_flag' => strstr($deliveryFlag, '1') ? $deli_pay_flag : 0); // 物流支持货到付款
                                                                                         
            // if($isRest == '1'){
                                                                                         // $data['is_cj']
                                                                                         // =
                                                                                         // $isCj;
                                                                                         // }
            $batchInfo = M('tbatch_info')->where(
                "node_id='{$this->nodeId}' AND m_id='{$id}'")->find();
            if ($goodsNumFlag != 1) { // 限库存
                if (($goodsNum <
                     ($batchInfo['storage_num'] - $batchInfo['remain_num'])) &&
                     ($batchInfo['storage_num'] != - 1))
                    $this->error('商品总数需大于等于已售商品与当前锁定商品总和');
            }
            M()->startTrans();
            $goodsInfo = M('tgoods_info')->where(
                array(
                    'id' => $goods_id))
                ->lock(true)
                ->find(); // tgoodsInfo 数据锁表
            $result = M('tmarketing_info')->where(
                "node_id='{$this->nodeId}' AND id='{$id}'")->save($data);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,更新tmarketing_info失败!');
            }
            // 更新tbatch_info表
            
            $remain_num = $batchInfo['remain_num'] +
                 ($goodsNum - $groupInfo['storage_num']);
            $batchToAdd = array(
                    'verify_end_type' => $verify_time_type,
                    'verify_end_date' => $verify_end_time,
                    'info_title' => $infotitle,
                    'use_rule' => $usingRules,
                    'storage_num' => $goodsNum,
                    'remain_num' => $remain_num,
                    'batch_amt' => $groupPrice);

            if($deliveryFlag == '0' || $deliveryFlag == '0-1') {
                if ($this->startUp == 1) {
                    $sms_text = I('cusMsg', '');
                    if (empty($sms_text)) {
                        M()->rollback();
                        $this->error('短信内容不能空！');
                    } else {
                        $batchToAdd['sms_text'] = $sms_text;
                    }
                }
            }

            $result = M('tbatch_info')->where("node_id='{$this->nodeId}' and m_id={$groupInfo['id']}")->save($batchToAdd);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,更新tbatch_info失败!');
            }
            // 更新库存
            if ($goodsNum != $groupInfo['storage_num']) {
                // 更新tgoods_info库存
                $goodsM = D('Goods');
                $flag = $goodsM->storagenum_reduc($goodsInfo['goods_id'], 
                    $goodsNum - $groupInfo['storage_num'], $batchInfo['id'], '0', 
                    '编辑闪购');
                if ($flag === false) {
                    M()->rollback();
                    $this->error('系统出错,更新tgoods_info库存失败');
                }
            }
            
            // 添加sku上架信息
            if ('1' === $is_sku) {
                // 创建sku链接
                $skuObj = D('Sku', 'Service');
                $info = $skuObj->addGoodsSkuInfo($skuMarkPrice, $this->nodeId, 
                    $groupInfo['id'], $batchInfo['id']);
                if (false == $info) {
                    M()->rollback();
                    $this->error($skuObj->getError());
                }
            }
            // 记录活动变更流水
            $marketTraceData = array(
                'batch_id' => $id, 
                'batch_type' => $this->BATCH_TYPE, 
                'name' => $name, 
                'start_time' => $startDate . '000000', 
                'end_time' => $endDate . '235959', 
                'memo' => $goodsMemo, 
                'defined_one_name' => $deliveryFlag, 
                'defined_two_name' => $showFlag, 
                'defined_three_name' => $buyCont, 
                'market_price' => $mPrice, 
                'group_price' => $groupPrice, 
                'goods_num' => $goodsNum, 
                'buy_num' => $buyNum, 
                // 'verify_begin_date' => $startDate.'000000',
                'verify_end_date' => $verify_end_time, 
                // 'verify_begin_type' => '0',
                'verify_end_type' => $verify_time_type, 
                'modify_time' => date('YmdHis'), 
                'modify_type' => '1', 
                'oper_id' => $this->user_id);
            $result = M('tmarketing_change_trace')->add($marketTraceData);
            if ($result === false) {
                M()->rollback();
                $this->error('系统出错,记录活动变更流水失败');
            }
            M()->commit();
            $this->success('更新成功!');
            exit();
        }
        // 获取送礼信息
        $sendGift = D('MarketInfo')->getSendGiftTage($groupInfo);
        $this->assign('sendGift', $sendGift);
        $this->assign('startUp', $this->startUp);
        $this->assign('isCycle', $isCycle); // 是否订购
        $this->assign("skuDetail", $skuDetail);
        $this->assign("skutype", 
            $skuObj->makeSkuType($goodsSkuTypeList, $goodsSkuDetailList));
        $this->assign('isSku', $isSku);
        $this->assign('row', $groupInfo);
        $this->display();
    }
    
    // 查看订单信息
    public function orderList() {
        $batchNo = I('batch_no', null, 'mysql_real_escape_string');
        if (empty($batchNo))
            $this->error('参数错误');
        $goodsName = I('goods_name', null, 'mysql_real_escape_string,trim');
        if (isset($goodsName) && $goodsName != '') {
            $where['g.group_goods_name'] = array(
                'like', 
                "%{$goodsName}%");
        }
        $orderId = I('order_id', null, 'mysql_real_escape_string,trim');
        if (! empty($orderId)) {
            $where['o.order_id'] = $orderId;
        }
        // 支付状态
        $pay_status = I('pay_status', null);
        if ($pay_status != '') {
            $where['o.pay_status'] = $pay_status;
        }
        // 订单状态
        $order_status = I('order_status', null);
        if ($order_status != '') {
            $where['o.order_status'] = $order_status;
        }
        // 配送状态
        $delivery_status = I('delivery_status', null);
        if ($delivery_status != '') {
            $where['o.delivery_status'] = $delivery_status;
        }
        // 订单类型
        $receiver_type = I('receiver_type', null);
        if ($receiver_type != '') {
            $where['o.receiver_type'] = $receiver_type;
        }
        $where['o.batch_no'] = $batchNo;
        $where['o.from_batch_no'] = $batchNo;
        $where['o.node_id'] = $this->nodeId;
        // $where['o.order_type'] = '2';
        import('ORG.Util.Page'); // 导入分页类
        $mapcount = M()->table('ttg_order_info o')
            ->where($where)
            ->count(); // 查询满足要求的总记录数
        $Page = new Page($mapcount, 10); // 实例化分页类 传入总记录数和每页显示的记录数
                                         // 确保分页查询条件
        foreach ($_REQUEST as $key => $val) {
            $p->parameter .= "$key=" . urlencode($val) . "&";
        }
        $show = $Page->show(); // 分页显示输出
        $field = array(
            'o.*,g.group_goods_name,g.group_price');
        $orderList = M()->table('ttg_order_info o')
            ->field($field)
            ->join("tmarketing_info g ON o.batch_no=g.id")
            ->where($where)
            ->order('o.add_time DESC')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $payStatus = array(
            '1' => '未支付', 
            '2' => '已支付');
        $orderStatus = array(
            '0' => '正常', 
            '1' => '取消');
        $deliveryStatus = array(
            '1' => '待配送', 
            '2' => '配送中', 
            '3' => '已配送', 
            '4' => '凭证自提');
        $receiverType = array(
            '0' => ' 凭证自提订单', 
            '1' => '物流订单');
        
        $this->assign('page', $show); // 赋值分页输出
        $this->assign('orderList', $orderList);
        $this->assign('payStatus', $payStatus);
        $this->assign('receiverType', $receiverType);
        $this->assign('orderStatus', $orderStatus);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('post', $_REQUEST);
        $this->assign('batchNo', $batchNo);
        $this->assign('empty', '<tr><td colspan="11">无数据</td></span>');
        $this->display();
    }
    
    // 数据导出
    public function export() {
        
        // 查询条件组合
        $where = "WHERE batch_type='" . $this->BATCH_TYPE . "' ";
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
                $condition['start_time'] = $condition['start_time'] . '000000';
                $filter[] = "start_time >= '{$condition['start_time']}'";
            }
            if (isset($condition['end_time']) && $condition['end_time'] != '') {
                $condition['end_time'] = $condition['end_time'] . '235959';
                $filter[] = "end_time <= '{$condition['end_time']}'";
            }
        }
        if (! empty($filter)) {
            $condition = implode(' AND ', $filter);
            $where .= ' AND ' . $condition;
        }
        $filter[] = "batch_type='" . $this->BATCH_TYPE . "'";
        $filter[] = "node_id in({$this->nodeIn()})";
        $count = M('tmarketing_info')->where($filter)->count();
        if ($count <= 0)
            $this->error('无订单数据可下载');
        
        $sql = "SELECT name,add_time,start_time,end_time,
		CASE status WHEN '1' THEN '正常' ELSE '停用' END status,
		click_count,sell_num
		FROM
		tmarketing_info {$where} AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
            'name' => '活动名称', 
            'add_time' => '添加时间', 
            'start_time' => '活动开始时间', 
            'end_time' => '活动结束时间', 
            'status' => '状态', 
            'click_count' => '访问量', 
            'sell_num' => '已卖量');
        if (querydata_download($sql, $cols_arr, M()) == false) {
            $this->error('下载失败');
        }
    }
    
    // 状态修改
    public function editBatchStatus() {
        $batchId = I('post.batch_id', null);
        $status = I('post.status', null);
        if (is_null($batchId) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        // 检查是否普通商品变sku商品
        $skuObj = D('Sku', 'Service');
        $skuInfoList = $skuObj->getSkuEcshopList($batchId, $this->node_id);
        $isChange = $skuObj->checkIsProToSku($this->node_id, $batchId, $status);
        if (NULL === $skuInfoList) {
            if (1 === $isChange)
                $this->error("该商品已添加规格信息，请重新上架！", 
                    array(
                        'return' => 'javascript:history.go(-1)', 
                        'reload' => U('Ecshop/GoodsSale/add')));
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
        if (false != $result) {
            node_log('投票活动状态更改|活动ID：' . $batchId);
            $this->success('更新成功', 
                array(
                    'id' => $batchId, 
                    'status' => $status));
        } else {
            $this->error('更新失败');
        }
    }
    
    // 状态修改
    public function editStatus() {
        $orderId = I('order_id', null);
        if (is_null($orderId)) {
            $this->error('缺少订单号');
        }
        $result = M('ttg_order_info')->where("order_id='{$orderId}'")->find();
        if (! $result) {
            $this->error('未找到订单信息');
        }
        if ($this->isPost()) {
            $status = I('post.status', null);
            if (is_null($status)) {
                $this->error('缺少配送状态');
            }
            $data = array(
                'order_id' => $orderId, 
                'delivery_status' => $status);
            $result = M('ttg_order_info')->save($data);
            if ($result) {
                $message = array(
                    'respId' => 0, 
                    'respStr' => '更新成功');
                $this->success($message);
            } else {
                $this->error('更新失败');
            }
        }
        
        $deliveryStatus = array(
            '1' => '待配送', 
            '2' => '配送中', 
            '3' => '已配送');
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('order_id', $result['order_id']);
        $this->assign('delovery_status', $result['delivery_status']);
        $this->display();
    }

    public function winningExport() {
        $batchId = I('batch_id', null, 'mysql_real_escape_string');
        $status = I('status', null, 'mysql_real_escape_string');
        if (is_null($batchId))
            $this->error('缺少参数');
        $sql = "SELECT mobile,add_time,
		CASE status WHEN '1' THEN '未中奖' ELSE '中奖' END status,prize_level
		FROM
		tcj_trace WHERE batch_id='{$batchId}' AND batch_type=5 AND node_id in({$this->nodeIn()})
		ORDER by status DESC";
        $countSql = "SELECT COUNT(*) as count FROM tcj_trace WHERE batch_id='{$batchId}' AND batch_type=5 AND node_id in({$this->nodeIn()})";
        $cols_arr = array(
            'mobile' => '手机号', 
            'add_time' => '中奖时间', 
            'status' => '是否中奖', 
            'prize_level' => '奖品等级');
        // 获取活动名称
        $batchName = M('tmarketing_info')->where(
            "id='{$batchId}' AND node_id in({$this->nodeIn()})")->getField(
            'name');
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
    
    // 发送团购商品
    public function sendCode() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $orderInfo = M('ttg_order_info')->where(
            "order_id='{$orderId}' AND node_id='{$this->nodeId}' AND order_type=0")->find();
        if (! $orderInfo)
            $this->error('未找到该订单信息');
            // 是否应经支付
        if ($orderInfo['pay_status'] != 2)
            $this->error('该订单还未支付');
        if (! empty($orderInfo['send_seq']))
            $this->error('该订单已经发过码');
            // 获取batchinfoid
        $batchInfoId = M('tbatch_info')->where(
            array(
                'node_id' => $orderInfo['node_id'], 
                'm_id' => $orderInfo['batch_no']))->getField('id');
        import("@.Vendor.SendCode");
        $req = new SendCode();
        // $transId = date('YmdHis').sprintf('%04s',mt_rand(0,1000));
        $transId = get_request_id();
        $resp = $req->wc_send($this->nodeId, $this->userId, 
            $orderInfo['group_batch_no'], $orderInfo['receiver_phone'], '8', 
            $transId, '', $batchInfoId);
        if ($resp === true) {
            // 更新request_id
            $result = M('ttg_order_info')->where("order_id='{$orderId}'")->save(
                array(
                    'send_seq' => $transId));
            $this->success('发送成功');
        } else {
            $this->error('发送失败:' . $resp);
        }
    }
    
    // 撤消团购商品
    public function cancelCode() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $orderInfo = M('ttg_order_info')->where(
            "order_id='{$orderId}' AND node_id='{$this->nodeId}' AND order_type=0")->find();
        if (! $orderInfo)
            $this->error('未找到该订单信息');
            // 是否应经支付
        if ($orderInfo['pay_status'] != 2)
            $this->error('该订单还未支付');
        if (empty($orderInfo['send_seq']))
            $this->error('该订单还未发码');
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $resp = $req->cancelcode($this->nodeId, $this->userId, 
            $orderInfo['send_seq']);
        if ($resp === true) {
            // 更新request_id
            $result = M('ttg_order_info')->where("order_id='{$orderId}'")->save(
                array(
                    'send_seq' => ''));
            $this->success('撤消成功');
        } else {
            $this->error('撤消失败:' . $resp);
        }
    }
    
    // 重发团购商品
    public function resendCode() {
        $orderId = I('order_id', null, 'mysql_real_escape_string');
        $orderInfo = M('ttg_order_info')->where(
            "order_id='{$orderId}' AND node_id='{$this->nodeId}' AND order_type=0")->find();
        if (! $orderInfo)
            $this->error('未找到该订单信息');
            // 是否应经支付
        if ($orderInfo['pay_status'] != 2)
            $this->error('该订单还未支付');
        if (empty($orderInfo['send_seq']))
            $this->error('该订单还未发码');
        import("@.Vendor.SendCode");
        $req = new SendCode();
        $resp = $req->resend_send($this->node_id, $this->user_id, 
            $orderInfo['send_seq']);
        if ($resp === true) {
            $this->success('重发成功');
        } else {
            $this->error('重发失败:' . $resp);
        }
    }

    public function send_eamil() {
        $this->error(print_r($this->userInfo));
        $client_id = $this->userInfo['client_id'];
        $content = "旺号：{$client_id}<br>真实姓名：{$name}<br/>手机号码：{$phone}<br/>邮箱：{$email}<br/>公司名称：{$company_name}<br/>职位：{$office}<br/>省市区：{$citystr}";
        $ps = array(
            "subject" => "旺财平台商品销售类业务权限申请", 
            "content" => $content, 
            "email" => "chensf@imageco.com.cn");
        $resp = send_mail($ps);
        if ($resp['sucess'] == '1') {
            $this->success("亲，我们已收到您的申请请求，旺小二会在2个工作日内通过邮件告知您相关审核结果！！");
        } else {
            $this->error("商品销售类业务权限申请失败，邮件发送失败！");
        }
    }
}