<?php

/**
 * 闪购活动
 *
 * @author bao
 */
class GoodsSaleAction extends MyBaseAction {

    public $expiresTime = 120;

    public $expiresTime2 = 600;
    // 手机动态密码过期时间
    public $batch_type = 26;

    public $channel_type = 4;

    public $channel_sns_type = 46;

    public $shop_type = 29;

    public $node_short_name = '';

    public $bonusService;

    public $wfxService;

    public $saler_id = '';

    public $wx_flag;

    public $wxnodeinfo;

    public $cityExpress;
    // 区域运费
    public $cityFreight;
    // 统一运费
    public function _initialize() {
        parent::_initialize();
        // 验证是否开通商品销售服务
        
        $this->node_short_name = get_node_info($this->node_id, 
            'node_short_name');
        $hasEcshop = $this->_hasEcshop($this->node_id);
        if ($hasEcshop != true)
            $this->showMsg("该商户未开通商品销售服务模块，无法下单", 0, '', $this->node_short_name);
        $r_phone = get_node_info($this->node_id, 'receive_phone');
        if (! $r_phone) {
            $this->showMsg("该商户的接受通知手机号不完整，无法下单", 0, '', $this->node_short_name);
        }
        $account_info = M('tnode_account')->where(
            array(
                'node_id' => $this->node_id))->select();
        if (! $account_info)
            $this->showMsg("该商户的收款账户信息不完整，无法下单", 0, '', $this->node_short_name);
            
            // 判断是否是微信中打开 0 否 1 是
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            $wx_flag = 0;
        } else {
            $wx_flag = 1;
        }
        $this->wx_flag = $wx_flag;
        // 红包服务
        $this->bonusService = D('Bonus', 'Service');
        // 旺分销
        $this->wfxService = D('Wfx', 'Service');
        // 微信粉丝专享判断 是的话 则隐藏右上角按钮
        $show_menu = 0; // 0显示 1隐藏
        $wxnodeinfo = M('tweixin_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $this->wxnodeinfo = $wxnodeinfo;
        if ($this->marketInfo['member_join_flag'] == '1' &&
             $wxnodeinfo['account_type'] != 4 && $wx_flag == 1) {
            // 非认证服务号无法获取openid 隐藏分享按钮
            $show_menu = 1;
            $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig('', $wxnodeinfo['app_id'], $wxnodeinfo['app_secret']);
            $shareArr = array(
                'config' => $wx_share_config);
            $this->assign('wx_share_config', $wx_share_config);
            $this->assign('shareData', $shareArr);
        }
        // 通宝斋标志
        if (in_array($this->node_id, C('fb_tongbaozhai.node_id'), true))
            $tongbaozhai_flag = 1;
        else
            $tongbaozhai_flag = 0;
            // 旺分销 链接上带的销售员id
        $saler_id = I('saler_id');
        if (! $saler_id) {
            $saler_sess = session('saler_sess' . $this->node_id);
            $saler_id = $saler_sess['saler_id'];
        }
        
        // 运费配置信息
        $cityExpressModel = D('CityExpressShipping');
        // 区域运费
        $this->cityExpress = $cityExpressModel->getCityExpressConfig(
            $this->node_id);
        // 统一运费
        $this->cityFreight = $cityExpressModel->getFreightConfig($this->node_id);
        
        // 非标用户特定 【非标V1.1.0_C15】鱼旨寿司 redmin 15348
        $isFb = in_array($this->batch_id, C('fishsush.mid'));
        
        $this->saler_id = $saler_id;
        $this->assign('saler_id', $saler_id);
        $this->assign('show_menu', $show_menu);
        $this->assign('wx_flag', $this->wx_flag);
        $this->assign('isFb', $isFb); // 鱼旨寿司非标
        $this->assign('tongbaozhai_flag', $tongbaozhai_flag);
        $this->assign('islogin', session('?cjUserInfo'));
    }
    
    // 闪购列表页
    public function index() {
        // 标签
        $id = $this->id;
        // 活动
        $goodsInfo = M()->table('tmarketing_info as m')
            ->field("m.*,e.*,b.remain_num,b.begin_time as b_begin_time,b.end_time as b_end_time,b.storage_num,b.batch_amt,b.batch_name,g.goods_image,g.goods_id, e.config_data as econfig_data, g.is_sku, g.is_order")
            ->join("tbatch_info as b on b.m_id=m.id")
            ->join("tgoods_info as g on g.goods_id=b.goods_id")
            ->join("tecshop_goods_ex as e on e.m_id=m.id")
            ->where(array(
            'm.id' => $this->batch_id))
            ->find();

        if(!$goodsInfo){
            $this->error('商品信息不正确');
        }
        $mem_flag = 1; // 粉丝标注 0 否 1是
        if ($goodsInfo['member_join_flag'] == '1' && $this->wx_flag == 1) {
            $wxnodeinfo = $this->wxnodeinfo;
            if ($wxnodeinfo['app_id'] && $wxnodeinfo['app_secret'] &&
                 ($wxnodeinfo['account_type'] == '4')) {
                $wxBatchOpen = session('wxBatchOpen');
                if (! $wxBatchOpen['openid']) {
                    $surl = U('Label/GoodsSale/index', 
                        array(
                            'id' => $id), '', '', true);
                    redirect(
                        U('Label/GetWeiXinInfo/goAuthorize', 
                            array(
                                'node_id' => $this->node_id, 
                                'surl' => urlencode($surl))));
                }
                $count = M('twx_user')->where(
                    array(
                        'subscribe' => array(
                            'neq', 
                            '0'), 
                        'openid' => $wxBatchOpen['openid'], 
                        'node_id' => $this->node_id))->count();
                if ($count < 1)
                    $mem_flag = 0;
            }
        }

        if ($goodsInfo['b_begin_time'] > date('YmdHis')) {
            $due = '1'; // 未开始
        } elseif ($goodsInfo['b_end_time'] < date('YmdHis')) {
            $due = '2'; // 过期
        } else {
            $due = '0'; // 未过期
            $goodsInfo['js_start_time'] = strtotime($goodsInfo['b_begin_time']) * 1000;
            $goodsInfo['js_end_time'] = strtotime($goodsInfo['b_end_time']) * 1000;
        }

        // 补充全局cookie
        $global_phone = cookie('_global_user_mobile');
        if (! $global_phone && session('groupPhone') != null) {
            cookie('_global_user_mobile', session('groupPhone'), 3600 * 24 * 365);
        }
        
        $from_batch_no = I('get.from_batch_no');
        $from_channel_id = I('get.from_channel_id');
        
        // 当天已售总数
        $daySaleNum = M('ttg_order_info')->where(
            "batch_no ={$this->batch_id} and add_time like '" . date('Ymd') . "%' and order_status=0")->sum('buy_num');
        if (! $daySaleNum)
            $daySaleNum = 0;
        // 已售总数
        $totalSaleNum = M('ttg_order_info')->where("batch_no ={$this->batch_id} and order_status=0")->sum('buy_num');
        if (! $totalSaleNum)
            $totalSaleNum = 0;
        
        $totalBuyNum = 0;
        if (session('groupPhone')) {
            // 该手机已购买总数
            $totalBuyNum = M('ttg_order_info')
                    ->where("batch_no ={$this->batch_id} and order_phone ='" . session('groupPhone') . "' and order_status=0")
                    ->sum('buy_num');  
            if(!$totalBuyNum){
                $totalBuyNum = 0;
            }      
        }
        $buyNum = 0; // 最大可购买数  商品总数和最多购买总数和已经卖出去商品数据处理  不限总数
        if ($goodsInfo['storage_num'] == - 1) {
            $buyNum = 999; // 代码写死 个人最大购买数量
        } else {
            $buyNum = $goodsInfo['remain_num']; // 剩余商品
        }
        if ($buyNum <= 0)
            $buyNum = 0;
            /*
         * buyNum 最大购买数量 dayBuyNum 每天可购买剩余数量 phoneBuyNum 单个手机号可购买剩余数量
         */
        $goodsConfig = json_decode($goodsInfo['econfig_data'], true);
        if ($buyNum > 0) {
            // 限当天购买数
            if ($goodsInfo['day_buy_num'] > 0) {
                $lastBuyNum = $goodsInfo['day_buy_num'] - $daySaleNum; // 剩余可买数量
                $lastBuyNum <= 0 ? $dayBuyNum = 0 : $dayBuyNum = $lastBuyNum;
                if ($dayBuyNum <= 0)
                    $buyNum = 0;
                else
                    $buyNum = min($buyNum, $lastBuyNum);
            }
            
            // 限单个手机的购买数量
            if ($goodsInfo['person_buy_num'] > 0) {
                $leftBuyNum = $goodsInfo['person_buy_num'] - $totalBuyNum;
                if ($leftBuyNum <= 0)
                    $buyNum = 0;
                else {
                    $buyNum = min($buyNum, $leftBuyNum);
                }
            }
        }
        $row = array();
        $row['sns_type'] = $goodsInfo['sns_type'];
        
        // 获取小店的链接
        $markId = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => 29))->getField('id');
        $channelId = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => $this->channel_type, 
                'sns_type' => $this->channel_sns_type))->getField('id');
        if ($markId && $channelId) {
            $labelId = get_batch_channel($markId, $channelId, '29', 
                $this->node_id);
        }
        // 展示活动列表个数
        $where = array(
            'node_id' => $this->node_id, 
            'batch_type' => $this->batch_type, 
            'status' => '1', 
            'defined_two_name' => '1', 
            'id' => array(
                'neq', 
                $this->batch_id), 
            'end_time' => array(
                'gt', 
                date('YmdHis')));
        $bcount = M('tmarketing_info')->where($where)->count();
        // 取得总规则信息
        $ruleType = D('SalePro', 'Service')->getNodeRule($this->node_id);
        if ($goodsInfo['bonus_flag'] == '1' && '0' != $ruleType) {
            $bonusRule = M('tbonus_rules')->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '1'))
                ->order('rev_amount')
                ->select();
        }
        $integralRule = '';
        // 取得积分规则信息
        $intergralType = D('SalePro', 'Service')->getNodeRule($this->node_id, 
            'tintegral_rule_main');
        if ($goodsInfo['integral_flag'] == '1' && '0' != $intergralType) {
            $integralRule = M('tintegral_rules')->where(
                array(
                    'node_id' => $this->node_id, 
                    'status' => '1'))
                ->order('rev_amount')
                ->select();
        }
        
        // 是否sku商品
        $skuObj = D('Sku', 'Service');
        unset($map);
        // 判断是否有sku数据
        $goodsSkuTypeList = '';
        $goodsSkuDetailList = '';
        $skuDetail = '';
        $goodsInfo['sku'] = array();
        $isSku = false;
        $skuInfoList = $skuObj->getSkuEcshopList($this->batch_id, $this->node_id, true);
        
        if (!$skuInfoList) {
            $isSku = false;
        } else {
            $isSku = true;
            //取得商品价格
            $skuObj->nodeId = $this->node_id;
            $goodsInfo = $skuObj->makeGoodsListInfo($goodsInfo, $this->batch_id, '');
            // 分离商品表中的规格和规格值ID
            $goods_sku_list = $skuObj->getReloadSku($skuInfoList);
            // 取得规格值表信息
            if (is_array($goods_sku_list['list']))
                $goodsSkuDetailList = $skuObj->getSkuDetailList($goods_sku_list['list']);
            
            if ($goodsInfo['is_order'] == '2') {
                $goodsConfigData = json_decode($goodsInfo['config_data'], TRUE);
                $goodsInfo['config_data'] = $goodsConfigData['cycle'];
                $goodsInfo['end_days'] = (int) ((strtotime($goodsInfo['end_time']) - time()) / 86400);
            }
            // 取得规格表信息
            if (is_array($goodsSkuDetailList)) {
                if (isset($goodsInfo['config_data']['cycle_type'])) // 取得订购类型
                    $goodsSkuTypeList = $skuObj->getSkuTypeList($goodsSkuDetailList, $goodsInfo['config_data']['cycle_type']);
                else
                    $goodsSkuTypeList = $skuObj->getSkuTypeList($goodsSkuDetailList);
            }
            // 价格列表
            $skuDetail = $skuObj->makeSkuList($skuInfoList);
        }
        // 取得门店信息
        $goodsM = D('Goods');
        $storeList = $goodsM->getGoodsShop($goodsInfo['goods_id'], true, 
            $this->nodeIn());
        $goodsM->recentLookGoods($this->id);
        // 获取送礼信息
        $sendConfig = M()->table('tmarketing_info')
            ->field('config_data')
            ->where(array(
            'id' => $this->batch_id))
            ->find();
        $sendGift = D('MarketInfo')->getSendGiftTage($sendConfig);
        $this->assign('sendGift', $sendGift);
        $this->assign('bcount', $bcount);
        $this->assign('bonusRule', $bonusRule);
        $this->assign('intergralType', $intergralType); // 积分规则
        $this->assign('integralRule', $integralRule); // 积分兑换规则
        $this->assign('ruleType', $ruleType); // 红包总规则
        $this->assign('daySaleNum', $daySaleNum);
        $this->assign('totalSaleNum', $totalSaleNum);
        $this->assign('totalBuyNum', $totalBuyNum);
        $this->assign('day', $day);
        $this->assign('hour', $hour);
        $this->assign('due', $due);
        $this->assign('storeList', $storeList);
        // start sku
        $this->assign('isSku', $isSku);
        $this->assign("skuDetail", $skuDetail);
        $this->assign("skutype", 
            $skuObj->makeSkuType($goodsSkuTypeList, $goodsSkuDetailList));
        // end sku
        $this->assign('id', $id);
        $this->assign('from_batch_no', $from_batch_no);
        $this->assign('from_channel_id', $from_channel_id);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('configData', $goodsConfig);
        $this->assign('row', $row);
        $this->assign('labelId', $labelId);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('buyNum', $buyNum);
        $this->assign('mem_flag', $mem_flag);
        $this->assign('wxName', $wxnodeinfo['weixin_code']);
        $this->display();
    }
    
    // 登录
    public function loginPhone() {
        $phoneNo = I('post.phoneNo', null, 'mysql_real_escape_string');
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->ajaxReturn(array(
                'type' => 'phone'), "手机号{$error}", 0);
        }
        // 手机动态密码
        $checkCode = I('post.check_code', null);
        if (! check_str($checkCode, array(
            'null' => false), $error)) {
            $this->ajaxReturn(array(
                'type' => 'pass'), "动态密码{$error}", 0);
        }
        $groupCheckCode = session('groupCheckCode');
        if (! empty($groupCheckCode) && $groupCheckCode['phoneNo'] != $phoneNo)
            $this->ajaxReturn(array(
                'type' => 'phone'), '手机号不正确', 0);
        if (! empty($groupCheckCode) && $groupCheckCode['number'] != $checkCode)
            $this->ajaxReturn(array(
                'type' => 'pass'), '手机动态密码不正确', 0);
        if (time() - $groupCheckCode['add_time'] > $this->expiresTime2)
            $this->ajaxReturn(array(
                'type' => 'pass'), '手机动态密码已经过期', 0);
            
            // 记录session
        session('groupPhone', $phoneNo);
        
        // 插入tmember_info_tmp会员表
        $userId = addMemberByO2o($phoneNo, $this->node_id, $this->channel_id, $this->batch_id);
        
        // 补充全局cookie
        $global_phone = cookie('_global_user_mobile');
        if (! $global_phone) {
            cookie('_global_user_mobile', $phoneNo, 3600 * 24 * 365);
        }
        $this->success('登录成功');
    }
    
    // 订单信息页面
    public function orderInfo() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->showMsg('您还没有登录', 0, '', $this->node_short_name);
        }
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->showMsg("该商品不在有效期之内！", 0, '', $this->node_short_name);
        $id = $this->id;
        $delivery = I('delivery', 0, 'intval'); // 送货方式
        $buycount = I('buycount', 1, 'intval'); // 购买数量
        $skuInfo = I("skuInfo"); // sku信息
        
        $orderType = I('orderType', 'normal', 'string');
        $this->assign('orderType', $orderType);
        if ($orderType == 'bookOrder') {
            $deliveryDate = I('deliveryDate', '0', 'string');
            $deliverySpecialDate = I('deliverySpecialDate', '0', 'string');
            if ($deliverySpecialDate != 'undefined' &&
                 $deliverySpecialDate != '0' && $deliverySpecialDate != '') {
                $deliveryDate = $deliverySpecialDate;
            }
            $this->assign('deliveryDate', $deliveryDate);
        }
        
        $skuList = '';
        // 判断是否sku商品
        $skuObj = D('Sku', 'Service');
        // 活动
        if ('' != $skuInfo) {
            // 将传输进入的，号替换为#号
            $skuList = $skuObj->replaceArray($skuInfo, '&', ',', '#');
            $skuId = implode(',', $skuList);
            $filter[] = "g.sku_detail_id = '" . $skuId . "'";
            $filter[] = "m.id = " . $this->batch_id;
            $goodsInfo = M()->table('tmarketing_info m')
                ->field(
                "m.*, t.goods_image, s.remain_num,s.storage_num,b.batch_no,b.batch_name, s.sale_price, b.id as b_id")
                ->join("tbatch_info b on b.m_id=m.id")
                ->join("tecshop_goods_sku s ON s.m_id=b.m_id")
                ->join("tgoods_info t on t.goods_id=b.goods_id")
                ->join("tgoods_sku_info g ON g.id = s.skuinfo_id")
                ->where($filter)
                ->find();
            // 更换为sku的价格
            $goodsInfo['group_price'] = $goodsInfo['sale_price'];
            $skuInfoList = $skuObj->makeSkuOrderInfo($skuId, 0, $this->batch_id);
            if ($skuInfoList) {
                $goodsInfo['sku_name'] = $skuInfoList['sku_name'];
            }
        } else {
            $goodsInfo = M()->table('tmarketing_info m')
                ->field(
                "m.*, t.goods_image,b.remain_num,b.storage_num,b.batch_no,b.batch_name, b.id as b_id")
                ->join("tbatch_info b on b.m_id=m.id")
                ->join("tgoods_info t on t.goods_id=b.goods_id")
                ->where(array(
                'm.id' => $this->batch_id))
                ->find();
        }
        
        $result = $this->_checkGoods($goodsInfo, $delivery, $buycount);
        if ($result['code'] != '0000')
            $this->error($result['msg']);
            // 获取收货地址列表
        $curAddress = array();
        $selectddres = array();
        $addressList = M('tphone_address e ')->where(
            "e.user_phone='" . session('groupPhone') . "'")
            ->order("last_use_time desc")
            ->limit(20)
            ->select();
        if (! empty($addressList)) {
            foreach ($addressList as $ak => &$al) {
                // 新增城市级联
                $cityInfo = array();
                if ($al['path']) {
                    $cityInfo = M('tcity_code')->where(
                        array(
                            'path' => $al['path']))
                        ->field(
                        'province_code, city_code, town_code, province, city, town')
                        ->find();
                    $al['province_code'] = $cityInfo['province_code'];
                    $al['city_code'] = $cityInfo['city_code'];
                    $al['town_code'] = $cityInfo['town_code'];
                    $al['province'] = $cityInfo['province'];
                    $al['city'] = $cityInfo['city'];
                    $al['town'] = $cityInfo['town'];
                }
                if ($ak == 0) {
                    $curAddress = $al;
                }
                $selectddres[] = $al;
            }
        }
        // 免运费限制
        $ruleFeeLimit = 0;
        // 获取运费信息
        // 计算运费
        if ($delivery == 1) {
            $provinceCode = isset($al['province_code']) ? $al['province_code'] : 0;
            $cityCode = $provinceCode .
                 (isset($al['city_code']) ? $al['city_code'] : 0);
            $cityExpressModel = D('CityExpressShipping');
            $shipfee = $cityExpressModel->getShippingFee($this->node_id, 
                $goodsInfo['group_price'] * $buycount, $cityCode); // 取得地区运费
            $feeLimit = $cityExpressModel->getFeeLimit($this->node_id);
            if ($goodsInfo['group_price'] * $buycount > $feeLimit &&
                 NULL != $feeLimit)
                $ruleFeeLimit = 2;
        } else {
            $shipfee = 0;
        }
        
        // 分销员信息
        $salerInfo = $this->wfxService->get_bind_saler($this->node_id, 
            session('groupPhone'), $this->batch_id, $this->saler_id);
        $errcode = $this->wfxService->errcode;
        if ($this->isPost()) {
            $error = '';
            // 收货人手机号
            // $receive_phone = I('receive_phone');
            $receive_phone = (I('receive_phone') == null ||
                 strlen(trim(I('receive_phone'))) == 0) ? session('groupPhone') : I(
                    'receive_phone');
            // 收货类型
            $receiverType = $delivery;
            // 收货人姓名
            $receiver_name = I('post.receive_name');
            // 收货人地址
            $receiver_addr = I('post.receive_address');
            // 城市代码
            $pcode = I("province_code", "");
            $ccode = I("city_code", "");
            $town_code = I("town_code", "");
            $addsPath = $pcode . $ccode . $town_code;
            $usePoint = (int) I("usePoint"); // 使用积分
            $needPayMoney = 0; // 积分需要抵用金额(初始化)
                               // 备注
            $memo = I('post.memo');
            $skuInfo = I("skuInfo"); // sku信息
            if ($receiverType != '0') {
                if (! check_str($receive_phone, 
                    array(
                        'null' => false, 
                        'strtype' => 'mobile', 
                        'maxlen' => 15), $error)) {
                    $this->showMsg("手机号{$error}", 0, $id, 
                        $this->node_short_name);
                }
                if (! check_str($receiver_name, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => 10), $error)) {
                    $this->showMsg("收货人姓名{$error}", 0, $id, 
                        $this->node_short_name);
                }
                if (empty($pcode) || empty($ccode)) {
                    $this->error('请选择正确的城市信息');
                }
                if (! check_str($receiver_addr, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => 50), $error)) {
                    $this->showMsg("收货地址{$error}", 0, $id, 
                        $this->node_short_name);
                }
                if (! check_str($memo, 
                    array(
                        'null' => true, 
                        'maxlen_cn' => 100), $error)) {
                    $this->showMsg("备注{$error}", 0, $id, $this->node_short_name);
                }
                // 插入手机收货地址表
                if ($receiver_addr != '' && $receiver_name != '') {
                    $count = M('tphone_address')->where(
                        array(
                            'user_phone' => session('groupPhone'), 
                            'user_name' => $receiver_name, 
                            'phone_no' => $receive_phone, 
                            'address' => $receiver_addr))->getField('id');
                    if ($count > 0) {
                        $result = M('tphone_address')->where(
                            array(
                                'id' => $count))->save(
                            array(
                                'last_use_time' => date('YmdHis'), 
                                'path' => trim($addsPath)));
                    } else {
                        $result = M('tphone_address')->add(
                            array(
                                'user_phone' => session('groupPhone'), 
                                'user_name' => $receiver_name, 
                                'phone_no' => $receive_phone, 
                                'address' => $receiver_addr, 
                                'path' => trim($addsPath), 
                                'add_time' => date('YmdHis'), 
                                'last_use_time' => date('YmdHis')));
                    }
                }
            }
            $parm1 = I('parm1', null);
            // 计算运费
            if ($delivery == 1) {
                $provinceCode = isset($pcode) ? $pcode : 0;
                $cityCode = $provinceCode . (isset($ccode) ? $ccode : 0);
                $cityExpressModel = D('CityExpressShipping');
                $shipfee = $cityExpressModel->getShippingFee($this->node_id, 
                    $goodsInfo['group_price'] * $buycount, $cityCode); // 取得地区运费
            } else {
                $shipfee = 0;
            }
            // 支付方式
            $pay_type = I('post.payment_type');
            $from_batch_no = I('post.from_batch_no');
            if (! $from_batch_no) {
                $from_batch_no = $goodsInfo['id'];
            }
            $from_channel_id = I('post.from_channel_id');
            if (! $from_channel_id) {
                $from_channel_id = $id;
            }
            
            // 通宝斋非标 免运费
            if ($this->tongbaozhai_flag == 1 && $parm1)
                $shipfee = 0;
                // 自提订单免运费
            if ($receiverType == '0')
                $shipfee = 0;
            $bonus_use_id = I('bonus_use_id', null);
            // 货到付款不可使用红包
            if ($pay_type == '4')
                $bonus_use_id = '';
            $totalAmt = $goodsInfo['group_price'] * $buycount + $shipfee;
            // 计算订单可减去金额
            $reAmount = $this->bonusService->orderCutBonus(
                session('groupPhone'), $bonus_use_id);
            // 如果累计的红包金额满足最大的使用红包金额，则减去最大红包金额，否则减去红包金额累计
            if ($goodsInfo['bonus_flag'] == '1')
                $maxAmount = $this->bonusService->getUseBonus($this->node_id, 
                    $goodsInfo['group_price'] * $buycount);
            else
                $maxAmount = 0;
            $bounsAmount = min($reAmount, $maxAmount);
            // 判断如果有可使用的积分
            if ($usePoint > 0 &&
                 ($goodsInfo['group_price'] * $buycount - $bounsAmount) > 0) {
                // 取得积分规则信息
                $intergralType = D('SalePro', 'Service')->getNodeRule(
                    $this->node_id, 'tintegral_rule_main');
                // 用户积分
                $memberInfo = D('MemberInstall')->telTermMemberFlag(
                    $this->node_id, session('groupPhone'));
                $myPoint = isset($memberInfo['point']) ? (int) $memberInfo['point'] : 0;
                if ($myPoint < $usePoint) {
                    $this->error("您当前积分不足以支付本次订单！");
                }
                // 获取积分兑换比例
                $exchangeInfo = D('Integral')->getIntegralExchange(
                    $this->node_id);
                
                if (! $exchangeInfo) {
                    $userIntegral = 0;
                } else {
                    if ($usePoint > 0)
                        $userIntegral = D('Integral')->getUseIntergral(
                            $goodsInfo['group_price'] * $buycount, 
                            $this->node_id);
                    else
                        $userIntegral = 0;
                }
                // 可使用积分
                $canUserIntegral = (int) $userIntegral / $exchangeInfo;
                if ($usePoint > $canUserIntegral)
                    $this->error("您使用的积分超出了本次需要使用的积分限制！");
                    // 可抵用金额
                $needPayMoney = $usePoint * $exchangeInfo;
                // 判断用户使用的积分和剩余可扣除的金额
                if ($needPayMoney >
                     ($goodsInfo['group_price'] * $buycount - $bounsAmount))
                    $this->error("您使用的积分已超出本次可抵扣金额！");
            }
            $cutAmount = $bounsAmount + $needPayMoney;
            $cutAmount = floor(bcmul($cutAmount, 100)) / 100;
            // 判断银联金额是否大于1元
            if ('2' == $pay_type) {
                $msg = D('PayOrder', 'Service')->checkPayRule(
                    $totalAmt - $cutAmount, $pay_type, '1.00');
                if (false === $msg)
                    $this->error("订单生成失败，银联支付金额必须大于1元！");
            }
            $wxOpenId = '';
            // 判断是否微信订单
            if ($this->wx_flag == 1) {
                $wxUserInfo = session('merWxUserInfo');
                if (! $wxUserInfo)
                    $wxUserInfo = session('wxUserInfo');
                $wxOpenId = $wxUserInfo['openid'];
            }
            $ip = GetIP();
            M()->startTrans();
            // 生成订单
            $data = array(
                'order_id' => date('ymd') . substr(time(), - 5) .
                     substr(microtime(), 2, 5), 
                    'order_type' => '0', 
                    'order_phone' => session('groupPhone'), 
                    'from_batch_no' => $from_batch_no, 
                    'from_channel_id' => $from_channel_id, 
                    'batch_no' => $goodsInfo['id'], 
                    'group_batch_no' => $goodsInfo['batch_no'], 
                    'node_id' => $goodsInfo['node_id'], 
                    'order_amt' => $totalAmt - $cutAmount, 
                    'receiver_type' => $receiverType, 
                    'receiver_phone' => $receive_phone, 
                    'receiver_name' => $receiver_name, 
                    'receiver_addr' => $receiver_addr, 
                    'buy_num' => $buycount, 
                    'batch_channel_id' => $id, 
                    'status' => '1', 
                    'pay_channel' => $pay_type, 
                    'add_time' => date('YmdHis'), 
                    'memo' => $memo, 
                    'freight' => $shipfee, 
                    'order_ip' => $ip, 
                    'price' => $goodsInfo['group_price'], 
                    'receiver_citycode' => trim($addsPath),  // 新增城市代码 添加人：曾成
                    'bonus_use_amt' => $bounsAmount,  // 新增红包使用金额在订单表中 添加人：曾成
                    'point_use' => $usePoint,  // 新增使用积分在订单表中 添加人：曾成
                    'point_use_amt' => $needPayMoney,  // 新增积分使用金额在订单表中 添加人：曾成
                    'openId' => $wxOpenId); // 新增微信OPENID在订单表中 添加人：曾成
            
            $orderType = I('post.orderType', 'normal', 'string');
            if ($orderType == 'bookOrder') {
                $data['other_type'] = '1';
                $deliveryDate = I('post.deliveryDate', '0', 'string');
                $deliveryDate = $skuObj->checkBookOrderDeliveryDate(
                    $goodsInfo['b_id'], $deliveryDate);
                if ($deliveryDate == 'error') {
                    $this->error('请填写正确的配送日期！');
                } else {
                    $data['book_delivery_date'] = $deliveryDate;
                }
            }
            if ($parm1)
                $data['parm1'] = $parm1;
                // 分销员
            if ($errcode == 0) {
                if ($salerInfo)
                    $data['saler_id'] = $salerInfo['id'];
                else {
                    // 根据前端传入的手机号
                    $saler_phone = I('saler_phone');
                    if ($saler_phone) {
                        $sInfo = $this->wfxService->get_saler_info_by_phone(
                            $this->node_id, $saler_phone);
                        if ($sInfo)
                            $data['saler_id'] = $sInfo['id'];
                    }
                }
            }
            $result = M('ttg_order_info')->add($data);
            if (! $result) {
                M()->rollback();
                $this->showMsg('系统出错,订单创建失败', 0, $id, $this->node_short_name);
            }
            // 更新tmarketing_info sell_num
            // 判断 是否SKU商品
            if (! empty($skuInfo)) {
                $skuList = $skuObj->replaceArray($skuInfo, '&', ',', '#');
                $skuList = implode(',', $skuList);
                $skuInfoList = $skuObj->makeSkuOrderInfo($skuList, 0, 
                    $this->batch_id);
                $result = M('tecshop_goods_sku')->where(
                    "id={$skuInfoList['id']}")->setInc('sell_num', 
                    $data['buy_num']);
                if ($result === false) {
                    M()->rollback();
                    log::write("更改购买数量失败. 订单号：{$data['order_id']} ");
                    $this->error("闪购商品购买数量更新已售数失败");
                }
                $result = M('tecshop_goods_sku')->where(
                    "id={$skuInfoList['id']} and storage_num != -1")->setDec(
                    'remain_num', $data['buy_num']);
                if ($result === false) {
                    M()->rollback();
                    log::write("更改库存数量失败. 订单号：{$data['order_id']} ");
                    $this->error("闪购商品购买数量更新已售数失败");
                }
            } else {
                $result = M('tmarketing_info')->where("id={$data['batch_no']}")->setInc(
                    'sell_num', $data['buy_num']);
            }
            
            if ($result === false) {
                M()->rollback();
                log::write("活动商品购买数量更新失败. 订单号：{$data['order_id']} ");
                $this->error("闪购商品购买数量更新已售数失败");
            }
            // 更新tbatch_info remain_num 不限的不改
            $result = M('tbatch_info')->where(
                "m_id={$data['batch_no']} and storage_num != -1")->setDec(
                'remain_num', $data['buy_num']);
            //锁定商品
            M('tbatch_info')->where("m_id={$data['batch_no']} and storage_num != -1")->setInc('lock_num', $data['buy_num']);   
            if ($result === false) {
                M()->rollback();
                log::write("闪购商品购买数量更新失败. 订单号：{$data['order_id']} ");
                $this->error("闪购商品购买数量更新库存失败");
            }
            // 扣除用户积分
            $retInfo = D('MemberInstall')->deductionMemberPoint($this->node_id, 
                session('groupPhone'), $usePoint, $data['order_id']);
            if (! $retInfo) {
                M()->rollback();
                $this->error("订单生成失败，积分扣减异常！");
            }
            // 更新红包数据
            if ($bonus_use_id != '' && $goodsInfo['bonus_flag'] == '1') {
                $result = $this->bonusService->useBonus($bonus_use_id, 
                    $data['order_id'], $bounsAmount, $this->node_id, 
                    $data['order_amt']);
                if ($result === false) {
                    M()->rollback();
                    log::write("闪购商品购买红包更新失败. 订单号：{$data['order_id']} ");
                    $this->error("闪购商品购买红包更新失败");
                }
            }
            
            // 将单品购买加入到订单表中
            $child_order_id = date('ymd') . substr(time(), - 3) .
                 substr(rand(11111, 99999)) . $bId;
            $row['order_id'] = $data['order_id'];
            $row['trade_no'] = $child_order_id;
            $row['b_id'] = $goodsInfo['b_id'];
            $row['b_name'] = $goodsInfo['name'];
            $row['goods_num'] = $buycount;
            $row['price'] = $goodsInfo['group_price'];
            $row['amount'] = $buycount * $goodsInfo['group_price'];
            $row['receiver_type'] = $receiverType;
            if (! empty($skuInfo)) {
                if ($skuInfoList) {
                    $row['ecshop_sku_id'] = $skuInfoList['id'];
                    $row['ecshop_sku_desc'] = $skuInfoList['sku_name'];
                }
            }
            $res = M('ttg_order_info_ex')->add($row);
            if ($res === false) {
                M()->rollback();
                log::write("添加订单失败. 订单号：{$data['order_id']} ");
                $this->error("订单更新失败");
            }
            M()->commit();
            // 判断是否免支付订单 红包抵扣
            if (0 == $data['order_amt']) {
                $saleModel = D('SalePro', 'Service');
                $result = $saleModel->OrderPay($data['order_id'], $pay_type);
                if ($result == 'success') {
                    // 油豆信息
                    $sourceInfo = D('MemberInstall')->orderPay(
                        $data['order_id'], $this->node_id, $this->id);
                    A('Label/PayMent')->showMsgInfo('购买成功', 1, $this->id, 
                        $data['order_id'], $this->node_id, 
                        $this->node_short_name, $data['other_type']);
                } else {
                    A('Label/PayMent')->showMsgInfo('购买失败', 0, $this->id, 
                        $data['order_id'], $this->node_id, 
                        $this->node_short_name, $data['other_type']);
                }
            } else {
                if ($pay_type == '2') {
                    // 去支付
                    $payModel = A('Label/PayUnion');
                    $payModel->OrderPay($data['order_id']);
                } elseif ($pay_type == '1') {
                    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !==
                         false) {
                        // 微信中用支付宝支付则跳转到中转页面
                        redirect(
                            U('Label/PayConfirm/index', 
                                array(
                                    'order_id' => $data['order_id'], 
                                    'id' => $id)));
                    } else {
                        $payModel = A('Label/PayMent');
                        $payModel->OrderPay($data['order_id']);
                    }
                } elseif ($pay_type == '3') {
                    // 微信支付
                    $payModel = A('Label/PayWeixin');
                    $payModel->goAuthorize($data['order_id']);
                } elseif ($pay_type == '4') {
                    // 货到付款
                    redirect(
                        U('Label/PayDelivery/OrderPay', 
                            array(
                                'order_id' => $data['order_id'])));
                }
                exit();
            }
        }
        
        $from_batch_no = I('get.from_batch_no');
        $from_channel_id = I('get.from_channel_id');
        
        $totalAmount = $goodsInfo['group_price'] * $buycount + $shipfee;
        if (! $curAddress['phone_no'])
            $curAddress['phone_no'] = session('groupPhone');
            // 获取可支付通道
        $hasPayChannel = 0;
        $payChannelInfo = M('tnode_account')->where(
            array(
                'node_id' => $this->node_id))->getField('account_type,status');
        foreach ($payChannelInfo as $v => $k) {
            if ($k == 1)
                $hasPayChannel = 1;
        }
        
        // 计算红包数据
        // 计算可用多少红包
        if ($goodsInfo['bonus_flag'] == '1')
            $userBonus = $this->bonusService->getUseBonus($this->node_id, 
                $totalAmount);
        else
            $userBonus = 0;
            // 可用使用的红包明细
        $userBounsList = $this->bonusService->getUserBonus(
            session('groupPhone'), $this->node_id);
        // 取得总规则信息
        $ruleType = D('SalePro', 'Service')->getNodeRule($this->node_id);
        // 取得积分规则信息
        $intergralType = D('SalePro', 'Service')->getNodeRule($this->node_id, 
            'tintegral_rule_main');
        // 用户积分
        $memberInfo = D('MemberInstall')->telTermMemberFlag($this->node_id, 
            session('groupPhone'));
        $userPoint = isset($memberInfo['point']) ? (int) $memberInfo['point'] : 0;
        // 获取积分兑换比例
        $exchangeInfo = D('Integral')->getIntegralExchange($this->node_id);
        
        if (! $exchangeInfo) {
            $userIntegral = 0;
        } else {
            if ($userPoint > 0)
                $userIntegral = D('Integral')->getUseIntergral(
                    $goodsInfo['group_price'] * $buycount, $this->node_id);
            else
                $userIntegral = 0;
        }
        // 可使用积分
        
        $canUserIntegral = (int) $userIntegral / $exchangeInfo;
        // 积分规则
        $this->assign('userPoint', $userPoint); // 用户积分
        $this->assign('userIntegral', (int) $userIntegral); // 可使用积分规则
        $this->assign('canUserIntegral', (int) $canUserIntegral); // 可使用积分数
        $this->assign('exchangeInfo', $exchangeInfo); // 积分比例
        $this->assign('intergralType', $intergralType); // 积分总规则
        $this->assign('ruleType', $ruleType); // 红包总规则
        $this->assign('userBonus', $userBonus);
        $this->assign('ruleFeeLimit', $ruleFeeLimit); // 免运费总金额
        $this->assign('userBounsList', $userBounsList);
        $this->assign('hasPayChannel', $hasPayChannel);
        $this->assign('payChannelInfo', $payChannelInfo);
        $this->assign('buycount', $buycount);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('totalAmount', $totalAmount);
        $this->assign('id', $id);
        $this->assign('skuInfo', $skuInfo);
        $this->assign('delivery', $delivery);
        $this->assign('from_batch_no', $from_batch_no);
        $this->assign('from_channel_id', $from_channel_id);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('curAddress', $curAddress);
        $this->assign('selectddres', $selectddres);
        $this->assign('shipfee', $shipfee);
        $this->assign('errcode', $errcode);
        $this->assign('salerInfo', $salerInfo);
        $this->display();
    }
    
    // 用户订单查看
    public function showOrderInfo() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->showMsg('您还没有登录', 0, $id, $this->node_short_name);
        }
        // 标签
        $model = M('tbatch_channel');
        $id = I('id', null, 'mysql_real_escape_string');
        $map = array(
            'id' => $id, 
            'batch_type' => $this->batch_type);
        $result = $model->where($map)->find();
        if (! $result)
            $this->showMsg('错误的参数！', 0, $id, $this->node_short_name);
        $where = array(
            'o.order_phone' => session('groupPhone'), 
            'o.node_id' => $result['node_id']);
        // 'o.order_type' => '2'
        
        $nowP = I('p', null, mysql_real_escape_string); // 页数
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount = 10; // 每页显示条数
        $field = array(
            'o.*,g.group_goods_name,g.group_price');
        $orderList = M()->table('ttg_order_info o')
            ->field($field)
            ->join("tmarketing_info g ON o.batch_no=g.id")
            ->where($where)
            ->order('o.add_time DESC')
            ->limit(($nowP - 1) * $pageCount, $pageCount)
            ->select();
        
        $status = array(
            '1' => '未支付', 
            '2' => '已支付');
        $ajax = I('get.ajax', null);
        if ($ajax == 1) {
            $str = '';
            if ($orderList) {
                foreach ($orderList as $v) {
                    $payUrl = '"' . U('Label/PayMent/OrderPay', 
                        array(
                            'order_id' => $v['order_id'])) . '"';
                    $v['status'] == 1 ? $payStr = "<a href='javascript:void(0);' onClick='javascript:link_to({$payUrl});'>支付</a>" : $payStr = '';
                    $str .= '<li>
								<div class="orderList-title">' .
                         $v['group_goods_name'] . $payStr .
                         '</div>
								<div class="orderList-con">
									<p>
									   <span>下单时间:' .
                         dateformat($v['add_time'], 'Y-m-d H:i:s') .
                         '</span>
							           <span class="ml20">收货手机号码:' .
                         $v['receiver_phone'] . '</span>
									</p>
							        <p><span>支付状态:' .
                         $status[$v['status']] . '</span></p>
							        <p>
							           <span>购买数量:' . $v['buy_num'] . '</span>
							           <span class="ml20">商品单价:' .
                         $v['group_price'] . '元</span>
							           <span class="ml20">共支付:' .
                         $v['order_amt'] . '元</span>
							        </p>
							    </div>
							  </li>';
                }
            }
            header("Content-type: text/html; charset=utf-8");
            echo $str;
            exit();
        }
        
        // dump($orderList);exit;
        $this->assign('orderList', $orderList);
        $this->assign('status', $status);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('id', $id);
        $this->display();
    }
    
    // 商户活动列表页
    public function showBatchList() {
        // 标签
        $model = M('tbatch_channel');
        $id = I('id', null, 'mysql_real_escape_string');
        $map = array(
            'id' => $id, 
            'batch_type' => $this->batch_type);
        $result = $model->where($map)->find();
        if (! $result)
            $this->showMsg('错误的参数！', 0, $id, $this->node_short_name);
            // 活动
        $where = array(
            'm.node_id' => $result['node_id'], 
            'm.batch_type' => $this->batch_type, 
            'm.status' => '1', 
            'm.defined_two_name' => '1', 
            'm.end_time' => array(
                'gt', 
                date('YmdHis')));
        
        $nowP = I('p', null, 'mysql_real_escape_string'); // 页数
        if (empty($nowP)) {
            $nowP = 1;
        }
        $pageCount = 10; // 每页显示条数
        $goodsList = M()->table("tmarketing_info m")->field(
            'm.*,b.storage_num,b.remain_num,a.goods_image')
            ->join('tbatch_info b on b.m_id=m.id')
            ->join("tgoods_info a on a.goods_id=b.goods_id")
            ->where($where)
            ->order('m.add_time DESC')
            ->limit(($nowP - 1) * $pageCount, $pageCount)
            ->select();
        // 该商户团购列表渠道id
        $channelId = M('tchannel')->where(
            "node_id={$result['node_id']} AND type=4 AND sns_type=45")->getField(
            'id');
        // 处理剩余日期
        foreach ($goodsList as $k => $v) {
            // 计算剩余天数
            $time = (strtotime($v['end_time']) - time());
            $day = floor($time / (60 * 60 * 24));
            $hour = floor(($time % (60 * 60 * 24)) / (60 * 60));
            $goodsList[$k]['goods_time'] = "<p>距离截止时间仅剩<span>{$day}天{$hour}小时</span></p>";
            // 构建活动列表渠道链接
            $goodsList[$k]['url_id'] = $model->where(
                "node_id={$result['node_id']} AND batch_type='{$this->batch_type}' AND batch_id={$v['id']} AND channel_id={$channelId}")->getField(
                'id');
        }
        $ajax = I('get.ajax', null);
        if ($ajax == 1) {
            $str = '';
            if ($goodsList) {
                foreach ($goodsList as $v) {
                    $detialUrl = '"' . U('Label/Label/index', 
                        array(
                            'id' => $v['url_id'])) . '"';
                    $detialStr = "<a href='javascript:link_to({$detialUrl});'>";
                    $str .= $detialStr . '<li>
								<div class="commodityList-img"><img src="' .
                         get_upload_url($v['goods_img']) . '"/></div>
								<div class="commodityList-con">
									<div class="commodityList-title">' .
                         $v['group_goods_name'] . '</div>
									<div class="commodityList-time"><i class="icon-time"></i>' .
                         $v['goods_time'] . '</div>
									<div class="commodityList-time">
										<i class="icon-number"></i><p><if condition="' .
                         $v['storage_num'] . ' neq -1">仅剩<span>' .
                         $v['remain_num'] . '份</span><else />剩余数量不限</if></p>
									</div>
									<div class="commodityList-price"><p>销售价￥' .
                         $v['group_price'] . '元</p><if condition="' .
                         $v['market_price'] . ' neq 0"><s>市场价￥' .
                         $v['market_price'] . '元</s></if></div>
							    </div>
							  </li></a>';
                }
            }
            header("Content-type: text/html; charset=utf-8");
            echo $str;
            exit();
        }
        // dump($goodsList);exit;
        $this->assign('goodsList', $goodsList);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('id', $id);
        $this->display();
    }
    
    // 手机发送动态密码
    public function sendCheckCode() {
        /*
         * //图片校验码 $verify = I('post.verify',null,'mysql_real_escape_string');
         * if(session('verify') != md5($verify)) { $this->error("图片动态密码错误"); }
         */
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        
        // 测试环境不下发，验证码直接为1111
        if (! is_production()) {
            $groupCheckCode = array(
                'number' => 1111, 
                'add_time' => time(), 
                'phoneNo' => $phoneNo);
            session('groupCheckCode', $groupCheckCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }
        
        // 发送频率验证
        $groupCheckCode = session('groupCheckCode');
        if (! empty($groupCheckCode) &&
             (time() - $groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->expiresTime2 / 60;
        $text = "您在{$this->node_short_name}商户的消费动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
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
        // dump($resp_array);exit;
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error('发送失败');
        }
        $groupCheckCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('groupCheckCode', $groupCheckCode);
        $this->success('动态密码已发送');
    }
    
    // 登录判断
    public function checkPhoneLogin() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->ajaxReturn('', '', 0);
        } else {
            $this->ajaxReturn('', '', 1);
        }
    }
    
    // 输出信息页面
    protected function showMsg($info, $status, $id, $node_short_name) {
        $this->assign('id', $id);
        $this->assign('status', $status);
        $this->assign('info', $info);
        $this->assign('node_id', $this->node_id);
        $this->assign('node_short_name', $node_short_name);
        $this->display('msg');
        exit();
    }
    
    // 销售员ID判断
    public function checkParm1() {
        $parm1 = I('parm1', null);
        if (! $parm1)
            $this->error("销售员ID不存在，<br />请检查销售员ID");
        
        $map = array(
            'node_id' => $this->node_id, 
            'id' => $parm1);
        
        $count = M('tmember_info')->where($map)->count();
        if ($count < 1)
            $this->error("销售员ID不存在，<br />请检查销售员ID");
        else
            $this->success("销售员ID存在，<br />可继续购买");
    }
    
    // 分销员判断
    public function checkSaler() {
        $saler_phone = I('saler_phone', null);
        if (! $saler_phone)
            $this->error("分销员手机号不存在，<br />请检查分销员手机号");
        
        $salerInfo = $this->wfxService->get_saler_info_by_phone($this->node_id, 
            $saler_phone);
        if (! $salerInfo)
            $this->error("分销员不存在，<br />请检查分销员");
        else
            $this->success("分销员存在，<br />可继续购买");
    }

    /*
     * 校验商品库存和购买限制 $garr 商品数据arr $delivery 送货方式 $buycount 购买数量
     */
    private function _checkGoods($garr, $delivery, $buycount) {
        // 库存
        if (($garr['storage_num'] != - 1) && ($garr['remain_num'] < $buycount))
            return array(
                'code' => '0001', 
                'msg' => '商品库存不足');
            
            // 日购买限制 0 不限
        if ($garr['buy_num'] > 0) {
            $daySaleNum = M('ttg_order_info')->where(
                "batch_no ={$garr['id']} and add_time like '" . date('Ymd') .
                     "%' and order_status=0")->sum('buy_num');
            if (! $daySaleNum)
                $daySaleNum = 0;
            if ($buycount > ($garr['buy_num'] - $daySaleNum))
                return array(
                    'code' => '0002', 
                    'msg' => '购买份数超过商品日限购份数');
        }
        
        // 个人购买限制 -1不限
        if ($garr['defined_three_name'] >= 0) {
            $totalBuyNum = M('ttg_order_info')->where(
                "batch_no ={$garr['id']} and order_phone ='" .
                     session('groupPhone') . "' and order_status=0")->sum(
                'buy_num');
            if (! $totalBuyNum)
                $totalBuyNum = 0;
            if ($buycount > ($garr['defined_three_name'] - $totalBuyNum))
                return array(
                    'code' => '0002', 
                    'msg' => '购买份数超过个人限购份数');
        }
        // 送货方式
        if (($garr['defined_one_name'] != '0-1') &&
             ($garr['defined_one_name'] != $delivery))
            return array(
                'code' => '0002', 
                'msg' => '送货方式错误');
        
        return array(
            'code' => '0000', 
            'msg' => '成功');
    }
    
    // 根据城市选择返回运费
    public function getCityFee() {
        $cityCode = I('post.city', null);
        $cityFee = D('CityExpressShipping')->getCityFee($this->cityExpress, 
            $cityCode, $this->cityFreight);
        echo $cityFee;
    }
    
    // 闪购列表页
    public function indexNew() {
        // 标签
        $id = $this->id;
        // 活动
        $row = M()->table('tmarketing_info as m')
            ->field("m.*,b.remain_num,b.storage_num,b.batch_amt,b.batch_name,g.goods_image,g.goods_id, g.is_sku, g.is_order")
            ->join("tbatch_info as b on b.m_id=m.id")
            ->join("tgoods_info as g on g.goods_id=b.goods_id")
            ->where(array(
            'm.id' => $this->batch_id))
            ->find();

        if(!$row){
            $this->error('商品信息不正确');
        }    
        
        // 获取小店的链接
        $markId = M('tmarketing_info')->where(
            array(
                'node_id' => $this->node_id, 
                'batch_type' => 29))->getField('id');
        $channelId = M('tchannel')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => $this->channel_type, 
                'sns_type' => $this->channel_sns_type))->getField('id');
        if ($markId && $channelId) {
            $labelId = get_batch_channel($markId, $channelId, '29', 
                $this->node_id);
        }
        $oneColor = unserialize($row['config_data']);
        $configInfo = unserialize($row['config_data']);

        $shareArr = array(
            'config' => D('WeiXin', 'Service')->getWxShareConfig(),
            'title' => $configInfo['share_title'],
            //'shareNote'=>$configInfo['share_title'],
            'desc' => $configInfo['share_introduce'],
            'imgUrl' =>  C('TMPL_PARSE_STRING.__UPLOAD__') . '/' .$row['share_pic']
        );

        $query_arr = explode('-', $row['sns_type']);
        $this->assign('shareData', $shareArr);
        $this->assign('id', $id);
        $this->assign('row', $row);
        $this->assign('oneColor', $oneColor);
        $this->assign('labelId', $labelId);
        $this->assign('node_short_name', $this->node_short_name);
        $this->display();
    }
}
