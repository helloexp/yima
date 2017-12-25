<?php
// 码上买
class ZqCutAction extends MyBaseAction {

    public $expiresTime = 120;

    public $expiresTime2 = 600;
    // 手机动态密码过期时间
    public $batch_type = 27;

    public $store_batch_type = 29;

    public $channel_type = 4;

    public $channel_sns_type = 46;

    public $shop_type = 29;

    public $node_short_name = '';

    public $wx_flag;

    public $wxnodeinfo;

    public $appid = '';

    public $secret = '';

    public $wechatInfo;

    public $auth_flag;

    public $cityExpress;
    // 区域运费
    public $cityFreight;
    // 统一运费
    const WECHAT_GRANTED = '1';

    /**
     *
     * @var WeiXinGrantService
     */
    public $wechatGrantService;

    /**
     *
     * @var WeiXinService
     */
    public $WeiXinService;

    public function _initialize() {
        $this->WeiXinService = D('WeiXin', 'Service');
        parent::_initialize();
        // 验证是否开通商品销售服务
        $this->node_short_name = get_node_info($this->node_id, 
            'node_short_name');
        $hasEcshop = $this->_hasMoonDayEcshop($this->node_id);
        if ($hasEcshop != true)
            $this->error("该商户未开通商品销售服务模块，无法下单");
            /*
         * if(!$node_info['account_number'] || !$node_info['account_name'] ||
         * !$node_info['account_type'] ||!$node_info['receive_phone']
         * ||!$node_info['person_url']){ $this->error("该商户收款账户信息不完整，无法下单"); }
         */
        $r_phone = get_node_info($this->node_id, 'receive_phone');
        if (! $r_phone) {
            $this->error("该商户的接受通知手机号不完整，无法下单");
        }
        /*
         * if(!$node_info['person_url']){
         * $this->error("该商户的消费者订单查询网址信息不完整，无法下单"); }
         */
        $account_info = M('tnode_account')->where(
            array(
                'node_id' => $this->node_id))->select();
        if (! $account_info)
            $this->error("该商户的收款账户信息不完整，无法下单");
            // 判断是否是微信中打开 0 否 1 是
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            $this->error('请使用手机微信浏览器访问');
        }
        // $cutWxInfo=array('openId'=>'oyJjks_VX0asOAPp5156vA6LWQM4','nodeId'=>$this->node_id);session('CutWxInfo',$cutWxInfo);
        if ($this->marketInfo['defined_five_name'] == '1') { // 商户公众号认证
                                                             // 取当前机构微信设置
            $weixinInfo = M('tweixin_info')->where("node_id='{$this->node_id}'")->find();
            if (! $weixinInfo) {
                $this->error("未绑定微信公众账号");
                exit();
            }
            $this->appid = $weixinInfo['app_id'];
            $this->secret = $weixinInfo['app_secret'];
            $this->auth_flag = $weixinInfo['auth_flag'];
            $this->wechatInfo = $weixinInfo;
        } else { // 翼码公众号
            $this->appid = C('WEIXIN.appid');
            $this->secret = C('WEIXIN.secret');
        }
        // 微信授权
        if (ACTION_NAME != 'callback' && (! session('?CutWxInfo.openId') ||
             session('CutWxInfo.nodeId') != $this->node_id)) {
            $this->goAuthorize();
        }
        
        // 查询logo信息
        $node_model = M('tecshop_banner');
        $map = array(
            'node_id' => $this->node_id, 
            'ban_type' => 2);
        $logoInfo = $node_model->where($map)->find();
        if (! $logoInfo['biaoti'])
            $logoInfo['biaoti'] = $this->node_short_name;
            
            // 运费配置信息
        $cityExpressModel = D('CityExpressShipping');
        // 区域运费
        $this->cityExpress = $cityExpressModel->getCityExpressConfig(
            $this->node_id);
        // 统一运费
        $this->cityFreight = $cityExpressModel->getFreightConfig($this->node_id);
        
        $this->assign('login_title', $logoInfo['biaoti']);
        $this->assign('img_url', $logoInfo['img_url']);
    }

    public function index() {
        $id = $this->id;
        if ($this->batch_type != '55') {
            $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        // 判断是否是帮砍
        $wxId = I('wxid');
        if (empty($wxId) || session('CutWxInfo.openId') == $wxId) {
            session('helpId', null);
        } else {
            $count = M('twx_wap_user')->where("openid='{$wxId}'")->count(); // 判断是否是有效的帮砍id
            $count == 0 ? session('helpId', null) : session('helpId', $wxId);
        }
        // 判断是否要关注(帮砍不需要关注)
        if (! $this->isHelpCut()) {
            if ($this->marketInfo['defined_five_name'] == '1' &&
                 $this->marketInfo['fans_collect_url'] == '1') {
                $openId = session('CutWxInfo.openId');
                $count = M('twx_user')->where(
                    "openid = '{$openId}' and ifnull(subscribe, '0') != '0' and node_id=" .
                         $this->node_id)->count();
                if ($count <= 0) {
                    $guideUrl = M('tweixin_info')->where(
                        "node_id='{$this->node_id}'")->getField('guide_url'); // 关注页链接
                    if (empty($guideUrl))
                        $this->error('该商户没有配置关注页链接');
                    $checkUrl = strtolower(substr($guideUrl, 0, 4));
                    if ($checkUrl == 'http') {
                        redirect($guideUrl);
                    } else {
                        redirect('http://' . $guideUrl);
                    }
                    exit();
                }
            }
        }
        // 补充全局cookie
        $global_phone = cookie('_global_user_mobile');
        if (! $global_phone && session('groupPhone') != null) {
            cookie('_global_user_mobile', session('groupPhone'), 
                3600 * 24 * 365);
        }
        // 活动
        $marketInfo = M()->table('tmarketing_info m')
            ->field(
            "m.name,m.node_name,m.memo,m.node_id,m.market_price,m.start_time,m.end_time,m.defined_five_name,m.defined_six_name,m.log_img,m.market_price,b.remain_num,b.storage_num,b.batch_name,g.goods_name,g.goods_image")
            ->join("tbatch_info b on b.m_id=m.id")
            ->join("tgoods_info g on g.goods_id=b.goods_id")
            ->where(array(
            'm.id' => $this->batch_id))
            ->find();
        if (! $marketInfo)
            $this->error('未找到活动信息');
        if ($marketInfo['redirect_url'] == '') {
            import('@.Vendor.DataStat');
            $opt = new DataStat($this->id, $this->full_id);
            $id = $this->id;
            $opt->UpdateRecord();
        }
        // 获取微信用户砍价详情
        $cutList = $this->getAllCutList();
        
        if ($marketInfo['start_time'] > date('YmdHis')) {
            $due = '1'; // 未开始
        } elseif ($marketInfo['end_time'] < date('YmdHis')) {
            $due = '2'; // 过期
        }
        
        $nodeName = empty($marketInfo['node_name']) ? $this->node_short_name : $marketInfo['node_name'];
        // 帮砍链接
        $this->isHelpCut() ? $shareUrl = U('Label/ZqCut/index', 
            array(
                'id' => $this->id, 
                'wxid' => session('helpId')), true, false, true) : $shareUrl = U(
            'Label/ZqCut/index', 
            array(
                'id' => $this->id, 
                'wxid' => session('CutWxInfo.openId')), true, false, true);
        $cutCount = $this->wxCutCount(); // 帮砍次数
        $this->assign('due', $due);
        $this->assign('marketInfo', $marketInfo);
        $this->assign('id', $this->id);
        $this->assign('nodeName', $nodeName);
        $this->assign('cutingPrice', $this->cutingPrice()); // 活动还可以砍的金额
        $this->assign('wxCutedPrice', $this->wxCutedPrice()); // 活动已砍掉的金额
        $this->assign('totalCutedPrice', $this->totalCutedPrice()); // 活动的剩余价格
        $this->assign('cutList', $cutList);
        $this->assign('orderStatus', $this->getOrderStatus()); // 订单状态
        $this->assign('shareData', D('WeiXin', 'Service')->getWxShareConfig());
        $this->assign('wxInfo', $this->getWxInfo());
        $this->assign('shareUrl', $shareUrl);
        $this->assign('isHelp', $this->isHelpCut());
        $this->assign('cutCount', $cutCount);
        $this->assign('footerUrl', 
            'http://cp.wangcaio2o.com/wapzq.html?id=88765');
        $this->display(); // 输出模板
    }
    
    // 砍价活动页面
    public function cuting() {
        // 活动
        $marketInfo = M()->table('tmarketing_info m')
            ->field(
            "m.name,m.group_price,m.node_name,m.start_time,m.log_img,m.end_time,defined_three_name,m.defined_four_name,m.defined_six_name,b.remain_num,b.storage_num,b.batch_name,g.goods_id,g.goods_image")
            ->join("tbatch_info b on b.m_id=m.id")
            ->join("tgoods_info g on g.goods_id=b.goods_id")
            ->where(array(
            'm.id' => $this->batch_id))
            ->find();
        // 每次要砍的价格
        $cutPrice = $this->cutingPrice();
        $cutPrice > $marketInfo['defined_four_name'] ? $cutPrice = $marketInfo['defined_four_name'] : $cutPrice = $cutPrice;
        if ($this->ispost()) {
            $cPrice = I('price');
            if (! check_str($cPrice, 
                array(
                    'null' => false, 
                    'strtype' => 'number')))
                $this->error("参数错误");
            if ($marketInfo['start_time'] > date('YmdHis'))
                $this->error('活动未开始');
            if ($marketInfo['end_time'] < date('YmdHis'))
                $this->error('活动已过期');
            if ($this->getOrderStatus() == '2')
                $this->error('商品已被购买');
            $wxCutedPrice = $this->wxCutedPrice();
            if (! empty($wxCutedPrice))
                $this->error('该活动您已经砍过价了');
            if ($this->isHelpCut()) { // 帮砍次数校验
                $cutCount = $this->wxCutCount();
                if ($marketInfo['defined_six_name'] > 0 &&
                     $cutCount >= $marketInfo['defined_six_name'])
                    $this->error('斧头已经用光,您不能再帮小伙伴砍价了');
            }
            $cutingPrice = $this->cutingPrice();
            if (empty($cutingPrice))
                $this->error('已经是最低金额了');
            
            if ($cPrice > $cutPrice)
                $cPrice = $cutPrice;
            if ($cPrice != '0') {
                $this->isHelpCut() ? $openId = session('helpId') : $openId = session(
                    'CutWxInfo.openId');
                M()->startTrans();
                $cutCount = M('twx_cuttree_info')->where(
                    "wx_user_id='{$openId}' AND node_id='{$this->node_id}' AND m_id='{$this->batch_id}'")->count();
                if ($cutCount > 0) { // 更新砍价金额,次数
                    $sql = "update twx_cuttree_info set cut_amt=cut_amt + {$cPrice},cut_number=cut_number+1 where wx_user_id='{$openId}' AND node_id='{$this->node_id}' AND m_id='{$this->batch_id}'";
                    $result = M()->execute($sql);
                    if ($result === false) {
                        M()->rollback();
                        $this->error('数据更新失败');
                    }
                } else { // 添加新数据
                    $data = array(
                        'wx_user_id' => $openId, 
                        'cut_amt' => $cPrice, 
                        'g_id' => $marketInfo['goods_id'], 
                        'node_id' => $this->node_id, 
                        'm_id' => $this->batch_id, 
                        'cut_number' => '1', 
                        'buy_status' => '0');
                    $result = M('twx_cuttree_info')->add($data);
                    if (! $result) {
                        M()->rollback();
                        $this->error('数据添加失败');
                    }
                }
                // 添加twx_cuttree_trace数据
                $data = array(
                    'from_user_id' => session('CutWxInfo.openId'), 
                    'send_user_id' => $this->isHelpCut() ? $openId = session(
                        'helpId') : $openId = session('CutWxInfo.openId'), 
                    'cut_amt' => $cPrice, 
                    'node_id' => $this->node_id, 
                    'm_id' => $this->batch_id, 
                    'add_time' => date('YmdHis'));
                $result = M('twx_cuttree_trace')->add($data);
                if (! $result) {
                    M()->rollback();
                    $this->error('详情数据添加失败');
                }
                M()->commit();
            }
            $this->success('砍价成功');
            exit();
        }
        $nodeName = empty($marketInfo['node_name']) ? $this->node_short_name : $marketInfo['node_name'];
        $this->assign('cutPrice', $cutPrice);
        $this->assign('cutingPrice', $this->cutingPrice());
        $this->assign('totalCutPrice', 
            ($marketInfo['group_price'] - $marketInfo['defined_three_name'])); // 该活动能砍掉多少钱
        $this->assign('nodeName', $nodeName);
        $this->assign('id', $this->id);
        $this->assign('wxid', session('helpId'));
        $this->assign('shareData', D('WeiXin', 'Service')->getWxShareConfig());
        $this->assign('logImg', $marketInfo['log_img']);
        $this->display();
    }

    public function goodsInfo() {
        // 活动
        $goodsInfo = M()->table('tmarketing_info m')
            ->field(
            "m.*,b.remain_num,b.storage_num,b.batch_name,g.goods_id,g.goods_image")
            ->join("tbatch_info b on b.m_id=m.id")
            ->join("tgoods_info g on g.goods_id=b.goods_id")
            ->where(array(
            'm.id' => $this->batch_id))
            ->find();
        if ($goodsInfo['start_time'] > date('YmdHis')) {
            $due = '1'; // 未开始
        } elseif ($goodsInfo['end_time'] < date('YmdHis')) {
            $due = '2'; // 过期
        } else {
            $due = '0'; // 未过期
                        // 计算剩余天数
            $time = (strtotime($goodsInfo['end_time']) - time());
            $day = floor($time / (60 * 60 * 24));
            $hour = floor(($time % (60 * 60 * 24)) / (60 * 60));
        }
        // #15230-非标
        if (in_array($this->node_id, C('WG_CUT_NODE_ID'))) {
            if ($this->cutingPrice() != 0)
                $noBuy = '1'; // 没砍到底,不可以买
        }
        // 当天已售总数
        $daySaleNum = M('ttg_order_info')->where(
            "batch_no ={$goodsInfo['id']} and add_time like '" . date('Ymd') .
                 "%' and order_status=0")->sum('buy_num');
        if (! $daySaleNum)
            $daySaleNum = 0;
            // 已售总数
        $totalSaleNum = M('ttg_order_info')->where(
            "batch_no ={$goodsInfo['id']} and order_status=0")->sum('buy_num');
        if (! $totalSaleNum)
            $totalSaleNum = 0;
            // 取得门店信息
        $goodsM = D('Goods');
        $storeList = $goodsM->getGoodsShop($goodsInfo['goods_id'], true, 
            $this->nodeIn($this->node_id));
        $this->assign('storeList', $storeList);
        $this->assign('daySaleNum', $daySaleNum);
        $this->assign('totalSaleNum', $totalSaleNum);
        $this->assign('totalBuyNum', $totalBuyNum);
        $this->assign('day', $day);
        $this->assign('hour', $hour);
        $this->assign('due', $due);
        $this->assign('id', $this->id);
        $this->assign('noBuy', $noBuy);
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('isHelpCut', $this->isHelpCut());
        $this->assign('orderStatus', $this->getOrderStatus()); // 订单状态
        $this->assign('wxid', session('helpId'));
        $this->assign('totalCutedPrice', $this->totalCutedPrice()); // 活动的剩余价格
        $this->display();
    }
    
    // 订单信息页面
    public function orderInfo() {
        
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }
        $overdue = $this->checkDate();
        if ($overdue === false)
            $this->error("该商品不在有效期之内！");
        $id = $this->id;
        $delivery = I('delivery', 0, 'intval'); // 送货方式
        
        $goodsInfo = M()->table('tmarketing_info m')
            ->field(
            "m.*,g.goods_id,b.remain_num,b.storage_num,b.batch_no,b.batch_name")
            ->join("tbatch_info b on b.m_id=m.id")
            ->join("tgoods_info g on g.goods_id=b.goods_id")
            ->where(array(
            'm.id' => $this->batch_id))
            ->find();
        
        if (($goodsInfo['storage_num'] != - 1) && ($goodsInfo['remain_num'] < 1))
            $this->error('商品库存不足');
            // 送货方式
        if (($goodsInfo['defined_one_name'] != '0-1') &&
             ($goodsInfo['defined_one_name'] != $delivery))
            $this->error('送货方式错误');
        
        $isHelgCut = $this->isHelpCut();
        if ($isHelgCut)
            $this->error('帮砍着不能购买');
        $orderStatus = $this->getOrderStatus();
        if (! $isHelgCut && $orderStatus == '2')
            $this->error('您已经购买过该商品');
        if (! $isHelgCut && $orderStatus == '1')
            $this->error('您已经下过该活动订单,请去订单中心完成付款');
            // #15230-非标判断
        if (in_array($this->node_id, C('WG_CUT_NODE_ID'))) {
            if ($this->cutingPrice() != 0)
                $this->error('还未砍到最低价'); // 没砍到底,不可以买
        }
        $totalCutedPrice = $this->totalCutedPrice();
        
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
        // 计算运费
        if ($delivery == 1) {
            $provinceCode = isset($al['province_code']) ? $al['province_code'] : 0;
            $cityCode = $provinceCode .
                 (isset($al['city_code']) ? $al['city_code'] : 0);
            $cityExpressModel = D('CityExpressShipping');
            $shipfee = $cityExpressModel->getShippingFee($this->node_id, 
                $totalCutedPrice, $cityCode); // 取得地区运费
            $feeLimit = $cityExpressModel->getFeeLimit($this->node_id);
            if ($totalCutedPrice > $feeLimit && NULL != $feeLimit)
                $ruleFeeLimit = 2;
        } else {
            $shipfee = 0;
        }
        
        // 提交
        if ($this->isPost()) {
            
            $error = '';
            // 收货人手机号
            $receive_phone = I('receive_phone');
            if (! check_str($receive_phone, 
                array(
                    'null' => false, 
                    'strtype' => 'mobile', 
                    'maxlen' => 15), $error)) {
                $this->showMsg("手机号{$error}", 0, $id, $this->node_short_name);
            }
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
            
            // 备注
            $memo = I('post.memo');
            if ($receiverType != '0') {
                if (! check_str($receiver_name, 
                    array(
                        'null' => false, 
                        'maxlen_cn' => 10), $error)) {
                    $this->showMsg("收货人姓名{$error}", 0, $id, 
                        $this->node_short_name);
                }
                if (empty($pcode) || empty($ccode) || empty($town_code)) {
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
                            'address' => $receiver_addr, 
                            'path' => trim($addsPath)))->getField('id');
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
            // 计算运费
            if ($delivery == 1) {
                $provinceCode = isset($pcode) ? $pcode : 0;
                $cityCode = $provinceCode . (isset($ccode) ? $ccode : 0);
                $cityExpressModel = D('CityExpressShipping');
                $shipfee = $cityExpressModel->getShippingFee($this->node_id, 
                    $totalCutedPrice, $cityCode); // 取得地区运费
            } else {
                $shipfee = 0;
            }
            $totalAmount = $totalCutedPrice + $shipfee;
            $parm1 = I('parm1', null);
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
            // 判断银联金额是否大于1元
            if ('2' == $pay_type) {
                $msg = D('PayOrder', 'Service')->checkPayRule($totalAmount, 
                    $pay_type, '1.00');
                if (false === $msg)
                    $this->error("订单生成失败，银联支付金额必须大于1元！");
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
                    'order_amt' => $totalAmount, 
                    'receiver_type' => $receiverType, 
                    'receiver_phone' => $receive_phone, 
                    'receiver_name' => $receiver_name, 
                    'receiver_addr' => $receiver_addr, 
                    'buy_num' => '1', 
                    'batch_channel_id' => $id, 
                    'status' => '1', 
                    'pay_channel' => $pay_type, 
                    'add_time' => date('YmdHis'), 
                    'memo' => $memo, 
                    'freight' => $shipfee, 
                    'order_ip' => $ip, 
                    'price' => $totalCutedPrice, 
                    'receiver_citycode' => trim($addsPath)); // 新增城市代码 添加人：曾成
            
            if ($parm1)
                $data['parm1'] = $parm1;
            
            $result = M('ttg_order_info')->add($data);
            if (! $result) {
                M()->rollback();
                $this->showMsg('系统出错,订单创建失败', 0, $id, $this->node_short_name);
            }
            // 更新tmarketing_info sell_num
            // 判断 是否SKU商品
            $result = M('tmarketing_info')->where("id={$data['batch_no']}")->setInc(
                'sell_num', $data['buy_num']);
            
            if ($result === false) {
                M()->rollback();
                log::write("活动商品购买数量更新失败. 订单号：{$data['order_id']} ");
                $this->error("闪购商品购买数量更新已售数失败");
            }
            // 更新tbatch_info remain_num 不限的不改
            $result = M('tbatch_info')->where(
                "m_id={$data['batch_no']} and storage_num != -1")->setDec(
                'remain_num', $data['buy_num']);
            if ($result === false) {
                M()->rollback();
                log::write("闪购商品购买数量更新失败. 订单号：{$data['order_id']} ");
                $this->error("闪购商品购买数量更新库存失败");
            }
            
            // 将单品购买加入到订单表中
            $child_order_id = date('ymd') . substr(time(), - 3) .
                 substr(rand(11111, 99999)) . $bId;
            $row['order_id'] = $data['order_id'];
            $row['trade_no'] = $child_order_id;
            $row['b_id'] = $goodsInfo['b_id'];
            $row['b_name'] = $goodsInfo['name'];
            $row['goods_num'] = '1';
            $row['price'] = $totalCutedPrice;
            $row['amount'] = $totalCutedPrice;
            $row['receiver_type'] = $receiverType;
            $res = M('ttg_order_info_ex')->add($row);
            if ($res === false) {
                M()->rollback();
                log::write("添加订单失败. 订单号：{$data['order_id']} ");
                $this->error("订单更新失败");
            }
            // 更新twx_cuttree_info订单信息
            $openId = session('CutWxInfo.openId');
            $cutCount = M('twx_cuttree_info')->where(
                "wx_user_id='{$openId}' AND node_id='{$this->node_id}' AND m_id='{$this->batch_id}'")->count(); // 判断不砍价直接购买的情况
            if ($cutCount > 0) { // 更新
                $cutData = array(
                    'buy_status' => '1', 
                    'order_id' => $data['order_id']);
                
                $res = M('twx_cuttree_info')->where(
                    "wx_user_id='{$openId}' AND node_id='{$this->node_id}' AND m_id='{$this->batch_id}'")->save(
                    $cutData);
                if ($res === false) {
                    M()->rollback();
                    log::write(
                        "twx_cuttree_info订单更新失败. 订单号：{$data['order_id']} ");
                    $this->error("订单更新失败");
                }
            } else { // 添加
                $cutData = array(
                    'wx_user_id' => $openId, 
                    'cut_amt' => '0', 
                    'g_id' => $goodsInfo['goods_id'], 
                    'node_id' => $this->node_id, 
                    'm_id' => $this->batch_id, 
                    'cut_number' => '0', 
                    'buy_status' => '1', 
                    'order_id' => $data['order_id']);
                $res = M('twx_cuttree_info')->add($cutData);
                if (! $res) {
                    M()->rollback();
                    log::write(
                        "twx_cuttree_info订单添加失败. 订单号：{$data['order_id']} ");
                    $this->error("订单更新失败");
                }
            }
            
            M()->commit();
            // 判断是否免支付订单 红包抵扣
            if (0 == $data['order_amt']) {
                $saleModel = D('SalePro', 'Service');
                $result = $saleModel->OrderPay($data['order_id'], $pay_type);
                // $result = 'success';
                if ($result == 'success') {
                    // 油豆信息
                    $sourceInfo = D('MemberInstall')->orderPay(
                        $data['order_id'], $this->node_id, $this->id);
                    A('Label/PayMent')->showMsgInfo('购买成功', 1, $this->id, 
                        $data['order_id'], $this->node_id, 
                        $this->node_short_name);
                } else {
                    A('Label/PayMent')->showMsgInfo('购买失败', 0, $this->id, 
                        $data['order_id'], $this->node_id, 
                        $this->node_short_name);
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
        // $totalAmount = $totalCutedPrice + $shipfee;
        $totalAmount = $totalCutedPrice; // 无需加运费
        
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
        
        $this->assign('hasPayChannel', $hasPayChannel);
        $this->assign('payChannelInfo', $payChannelInfo);
        $this->assign('ruleFeeLimit', $ruleFeeLimit); // 免运费总金额
        $this->assign('goodsInfo', $goodsInfo);
        $this->assign('totalAmount', $totalAmount);
        $this->assign('id', $id);
        $this->assign('delivery', $delivery);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('curAddress', $curAddress);
        $this->assign('selectddres', $selectddres);
        $this->assign('shipfee', $shipfee);
        $this->display();
    }
    
    // 获取商户该活动已砍掉的金额
    public function cutedPrice() {
        $this->isHelpCut() ? $openId = session('helpId') : $openId = session(
            'CutWxInfo.openId');
        $cutedPrice = M('twx_cuttree_info')->where(
            "wx_user_id='{$openId}' AND node_id='{$this->node_id}' AND m_id='{$this->batch_id}'")->getField(
            'cut_amt');
        empty($cutedPrice) ? $cutedPrice = '0' : $cutedPrice;
        return $cutedPrice;
    }
    
    // 获取商户该活动还可以砍的金额
    public function cutingPrice() {
        $marketInfo = M('tmarketing_info')->field(
            'group_price,defined_three_name')
            ->where(array(
            'id' => $this->batch_id))
            ->find();
        $maxPrice = $marketInfo['group_price'] -
             $marketInfo['defined_three_name']; // 最大砍掉金额
        $cutedPrice = $this->cutedPrice(); // 已经砍掉的金额
        return bcsub($maxPrice, $cutedPrice, 2);
    }
    
    // 获取活动的剩余价格(砍后)
    public function totalCutedPrice() {
        $marketInfo = M('tmarketing_info')->field('group_price')
            ->where(array(
            'id' => $this->batch_id))
            ->find();
        $Price = $marketInfo['group_price'];
        $cutedPrice = $this->cutedPrice(); // 已经砍掉的金额
        return bcsub($Price, $cutedPrice, 2);
    }
    
    // 获取当前微信用户对商户该活动已砍掉的金额
    public function wxCutedPrice() {
        $openId = session('CutWxInfo.openId');
        $this->isHelpCut() ? $sendUserId = session('helpId') : $sendUserId = $openId;
        $totalPrice = M('twx_cuttree_trace')->where(
            "send_user_id='{$sendUserId}' AND from_user_id='{$openId}' AND node_id='{$this->node_id}' AND m_id='{$this->batch_id}'")->sum(
            'cut_amt');
        return empty($totalPrice) ? '0' : $totalPrice;
    }
    
    // 获取当前微信用户帮助别人已经砍过该商品的次数
    public function wxCutCount() {
        $openId = session('CutWxInfo.openId');
        $cutCount = M('twx_cuttree_trace')->where(
            "from_user_id='{$openId}' AND send_user_id<>'{$openId}' AND node_id='{$this->node_id}' AND m_id='{$this->batch_id}'")->count();
        return $cutCount;
    }
    // 判断当前微信用户是否是帮砍用户
    public function isHelpCut() {
        return session('?helpId') ? true : false;
    }
    
    // 获取商户该活动所有砍价详情
    public function getAllCutList() {
        $openId = session('CutWxInfo.openId');
        $this->isHelpCut() ? $sendUserId = session('helpId') : $sendUserId = $openId;
        $data = M()->table("twx_cuttree_trace c")->field('c.*,w.headimgurl,w.nickname')
            ->join('twx_wap_user w ON c.from_user_id=w.openid')
            ->where(
            "c.send_user_id='{$sendUserId}' AND c.node_id='{$this->node_id}' AND c.m_id='{$this->batch_id}'")
            ->select();
        // echo M()->getLastSql();exit;
        return $data;
    }
    
    // 获取该微信用户的订单状态
    public function getOrderStatus() {
        $openId = session('CutWxInfo.openId');
        $this->isHelpCut() ? $openId = session('helpId') : $openId = $openId;
        $orderStatus = M('twx_cuttree_info')->where(
            "wx_user_id='{$openId}' AND node_id='{$this->node_id}' AND m_id='{$this->batch_id}'")->getField(
            'buy_status');
        return empty($orderStatus) ? $orderStatus = '0' : $orderStatus;
    }
    
    // 获取微信用户信息
    public function getWxInfo() {
        $openId = session('CutWxInfo.openId');
        $this->isHelpCut() ? $openId = session('helpId') : $openId = $openId;
        $wxInfo = M('twx_wap_user')->where("openid='{$openId}'")->find();
        return $wxInfo;
    }
    
    // 登录判断
    public function checkPhoneLogin() {
        // session('groupPhone',null);
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->ajaxReturn('', '', 0);
        } else {
            $this->ajaxReturn('', '', 1);
        }
    }

    /*
     * 微信认证接口
     */
    public function goAuthorize() {
        $urlOpt = array(
            'id' => $this->id);
        $wxId = I('wxid');
        if (! empty($wxId)) {
            $urlOpt['wxid'] = $wxId;
        }
        $apiCallbackurl = U('Label/ZqCut/callback', $urlOpt, '', '', TRUE);
        $backurl = U('Label/ZqCut/index', $urlOpt, '', '', TRUE);
        parent::wechatAuthorizeAndRedirectByDefault(1, $backurl, 
            $apiCallbackurl);
        // //授权地址
        // $open_url = 'https://open.weixin.qq.com/connect/oauth2/authorize?';
        // //回调地址
        // $urlOpt = array(
        // 'id'=>$this->id,
        // );
        // $wxId = I('wxid');
        // if(!empty($wxId)){
        // $urlOpt['wxid'] = $wxId;
        // }
        // $backurl = U('Label/ZqCut/callback',$urlOpt,'','',TRUE);
        // //授权参数
        // $opt_arr = array(
        // 'appid' => $this->appid,
        // 'redirect_uri' => $backurl,
        // 'response_type' => 'code',
        // //'scope' => 'snsapi_base',
        // 'scope'=> 'snsapi_userinfo'
        // );
        //
        // if ($this->auth_flag == self::WECHAT_GRANTED ) {
        // $this->initWechatGrantService();
        // $opt_arr['component_appid'] =
        // $this->wechatGrantService->component_appid;
        // }
        //
        // $link = http_build_query($opt_arr);
        // $gourl = $open_url . $link .'#wechat_redirect';
        // //header('location:'.$gourl);
        // redirect($gourl);
    }

    public function initWechatGrantService() {
        $this->wechatGrantService = D('WeiXinGrant', 'Service');
        $this->wechatGrantService->init();
    }

    /*
     * 微信认证接口回调
     */
    public function callback() {
        $code = I('code', null);
        $wxid = I('wxid', null);
        if ($code) {
            $result = $this->WeiXinService->weixinCallback($this->id, $code);
            // $result = $this->getOpenid($code);
            if (isset($result['errmsg']) && $result['errmsg'] != '') {
                $this->error($result['errmsg']);
            }
            $openid = $result['openid'];
            $access_token = $result['access_token'];
            $wxUserInfo = $this->WeiXinService->getUser($access_token, $openid); // 获取用户信息
            if (! $wxUserInfo) {
                $this->error('获取用户信息失败');
            }
            $this->setWxUserInfo($openid, $access_token, $wxUserInfo); // 更新twx_wap_user表
            $cutWxInfo = array(
                'openId' => $openid, 
                'nodeId' => $this->node_id);
            session('CutWxInfo', $cutWxInfo);
        }
        redirect(
            U('Label/ZqCut/index', 
                array(
                    'id' => $this->id, 
                    'wxid' => $wxid)));
    }
    
    // 获取openid
    protected function getOpenid($code) {
        if (empty($code)) {
            return false;
        }
        
        $oldApiUrl = $apiUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' .
             $this->appid . '&secret=' . $this->secret . '&code=' . $code .
             '&grant_type=authorization_code';
        
        if ($this->auth_flag == self::WECHAT_GRANTED) { // 已授权
            $this->initWechatGrantService();
            $this->wechatGrantService->api_component_token();
            $apiUrl = 'https://api.weixin.qq.com/sns/oauth2/component/access_token?appid=' .
                 $this->appid . '&code=' . $code .
                 '&grant_type=authorization_code&component_appid=' .
                 $this->wechatGrantService->component_appid .
                 '&component_access_token=' .
                 $this->wechatGrantService->component_access_token;
        }
        
        $result = $this->httpsGet($apiUrl);
        if ($this->auth_flag == self::WECHAT_GRANTED && ! $result) {
            $apiUrl = $oldApiUrl;
            $result = $this->httpsGet($apiUrl);
        }
        
        if (! $result) {
            return false;
        }
        
        $result = json_decode($result, true);
        if ($result['errcode'] != '') {
            return false;
        }
        return $result;
    }
    
    // 获取微信用户信息
    protected function getUser($access_token, $openid) {
        if (empty($access_token) || empty($openid)) {
            $this->error('用户数据获取参数不能为空！');
        }
        $userUrl = 'https://api.weixin.qq.com/sns/userinfo?access_token=' .
             $access_token . '&openid=' . $openid . '&lang=zh_CN';
        $error = '';
        $wxUserInfo = httpPost($userUrl, '', $error, 
            array(
                'METHOD' => 'GET'));
        $wxUserInfo = json_decode($wxUserInfo, true);
        if ($wxUserInfo['errcode'] || empty($wxUserInfo)) {
            return false;
        } else {
            return $wxUserInfo;
        }
    }
    
    // 请求接口
    protected function httpsGet($apiUrl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $result = curl_exec($ch);
        return $result;
    }
    
    // 更新添加twx_wap_user表数据
    protected function setWxUserInfo($openId, $accessToken, $data) {
        $count = M('twx_wap_user')->where("openid='{$openId}'")->count();
        $data = array(
            'access_token' => $accessToken, 
            'openid' => $openId, 
            'nickname' => $data['nickname'], 
            'sex' => $data['sex'], 
            'province' => $data['province'], 
            'city' => $data['city'], 
            'headimgurl' => $data['headimgurl']);
        if ($count > 0) { // 更新操作
            $result = M('twx_wap_user')->where("openid='{$openId}'")->save(
                $data);
            if ($result === false) {
                $this->error('更新微信用户数据失败');
            }
        } else { // 添加操作
            $result = M('twx_wap_user')->add($data);
            if ($result === false) {
                $this->error('添加微信用户数据失败');
            }
        }
    }
    
    
    // 根据城市选择返回运费
    public function getCityFee() {
        $provinceCode = I('post.province', null);
        $cityCode = I('post.city', null);
        $cityFee = D('CityExpressShipping')->getCityFee($this->cityExpress, 
            $cityCode, $this->cityFreight);
        echo $cityFee;
    }
}