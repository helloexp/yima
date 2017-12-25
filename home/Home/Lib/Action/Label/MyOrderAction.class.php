<?php

class MyOrderAction extends Action {

    public $node_id;

    public $id;

    public $node_name;

    /**
     *
     * @var TphoneAddressModel
     */
    public $TphoneAddressModel;

    public $node_short_name;

    public $wx_flag;

    public $pageSize = 10;

    public function _initialize() {
        $this->expiresTime = 120; // 手机发送间隔
        $this->CodeexpiresTime = 600; // 手机动态密码过期时间
        $this->assign('expiresTime', $this->expiresTime);
        $node_id = I("node_id");
        $id = I("id");
        if ($node_id == "" && ACTION_NAME != 'showGetBonus') {
            $node_id = session("node_id");
        }
        $seq = I('seq');
        if ($node_id != session("node_id") && $seq != '' && ACTION_NAME == 'index') {
            session_unset();
        }
        if (! session('?id')) {
            if ($_GET['saler_id']) {
                $node_id = M('twfx_saler')->where(
                    array(
                        'id' => $_GET['saler_id']))->getfield('node_id');
            }
            // 无session('id') 则默认获取小店的的id且存session
            $m_id = M('tmarketing_info')->where(
                array(
                    'node_id' => $node_id, 
                    'batch_type' => 29))->getField('id');
            $channel_id = M('tchannel')->where(
                array(
                    'node_id' => $node_id, 
                    'type' => 4, 
                    'sns_type' => 46))->getField('id');
            $bc_id = M('tbatch_channel')->where(
                array(
                    'batch_type' => 29, 
                    'batch_id' => $m_id, 
                    'channel_id' => $channel_id))->getField('id');
            session("id", $bc_id);
        }
        if (ACTION_NAME == 'myGoodSpreadCode' &&
             $_SESSION['twfxSalerID'] != $_GET['saler_id']) {
            header(
                "location:index.php?g=Label&m=Store&a=index&id=" .
                 $_SESSION['id'] . '&saler_id=' . $_GET['saler_id']);
        }
        
        if ($node_id == "") {
            $this->error('机构号不能为空！');
        }
        if ($node_id != session("node_id")) {
            if (ACTION_NAME == 'myCode' || ACTION_NAME == 'dealerCustomer' ||
                 ACTION_NAME == 'myCommission' ||
                 ACTION_NAME == 'distributionDetail' ||
                 ACTION_NAME == 'soldPercentage' || ACTION_NAME == 'showGetBonus' ||
                 ACTION_NAME == 'commissionDetails' ||
                 ACTION_NAME == 'showGetBonus' || ACTION_NAME == 'soldPercentage' ||
                 ACTION_NAME == 'myDistribution' || ACTION_NAME == 'mySaler' ||
                 ACTION_NAME == 'mySalerIndex' || ACTION_NAME == 'addSaler') {
                $_SESSION = null;
                header(
                    "location:index.php?g=Label&m=Member&a=index&id=" . $this->id .
                         "&node_id=" . $node_id);
            } else {
                session("node_id", "");
            }
        }
        
        session("node_id", $node_id);
        $nodeInfo = get_node_info($node_id);
        // 积分名称获取
        integralSetName($node_id);
        // 判断是否是微信中打开
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            $this->wx_flag = 0;
        } else {
            $this->wx_flag = 1;
        }
        $this->assign('wx_flag', $this->wx_flag);
        $this->id = $id;
        $this->node_id = $node_id;
        $this->node_name = $nodeInfo['node_name'];
        $this->node_short_name = $nodeInfo['node_short_name'];
        $this->assign('label_id', $id);
        $this->assign('node_id', $this->node_id);
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_short_name', $nodeInfo['node_short_name']);
    }
    
    // 入口
    public function index() {
        $seq = I('seq');
        if ($seq == '') {
            $jumpUrl = "index.php?g=Label&m=Member&a=index&id=" . $this->id .
                 "&node_id=" . $this->node_id;
        } elseif ($seq != '') {
            $jumpUrl = "index.php?g=Label&m=MyAddress&a=withDrowAddr&seq=" . $seq;
        }
        if (session('groupPhone') != "") {
            $str = "location:" . $jumpUrl;
            header($str);
            exit();
        }
        // 微信自动登陆
        $show_wx = 0;
        $wxUserInfo = session('wxUserInfo');
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            if (! $wxUserInfo['openid']) {
                if ($seq != '') {
                    $surl = U('Label/MyOrder/index', 
                        array(
                            'node_id' => $this->node_id, 
                            'seq' => $seq), '', '', true);
                } else {
                    $surl = U('Label/MyOrder/index', 
                        array(
                            'node_id' => $this->node_id), '', '', true);
                }
                redirect(
                    U('Label/O2OLogin/wcWeixinCheckLogin', 
                        array(
                            'node_id' => $this->node_id, 
                            'surl' => urlencode($surl))));
            } else {
                $show_wx = 1;
            }
        }
        
        // 查询logo信息
        $node_model = M('tecshop_banner');
        $map = array(
            'node_id' => $this->node_id, 
            'ban_type' => 2);
        
        $logoInfo = $node_model->where($map)->find();
        
        session("login_title", $logoInfo['biaoti']);
        $this->assign('jumpUrl', $jumpUrl);
        $this->assign('login_title', $logoInfo['biaoti']);
        $this->assign('img_url', $logoInfo['img_url']);
        $this->assign('node_id', $this->node_id);
        $this->display();
    }

    /**
     * [hasPayModule 是否有付费模块权限] [strModule 逗号隔开的模块,如'm0,m1,m2']
     *
     * @return boolean [description]
     */
    protected function hasPayModule($strModule) {
        $strModule = trim($strModule, ',');
        if (empty($strModule)) {
            $this->error('hasMudlePower:参数不得为空');
        }
        $payModule = get_node_info($this->node_id, 'pay_module');
        $arrModule = explode(',', $strModule);
        $payModule = explode(',', trim($payModule, ','));
        if (empty($payModule)) {
            return false;
        }
        foreach ($arrModule as $k => $v) {
            if (! in_array($v, $payModule)) {
                return false;
            }
        }
        return true;
    }
    
    // 手机发送动态密码
    public function sendCheckCode() {
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
            exit();
        }
        // 发送频率验证
        $groupCheckCode = session('groupCheckCode');
        if (! empty($groupCheckCode) &&
             (time() - $groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->CodeexpiresTime / 60;
        $text = "您在{$this->node_short_name}商户的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
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
        $groupCheckCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('groupCheckCode', $groupCheckCode);
        $this->success('动态密码已发送');
    }
    
    // 登录
    public function loginPhone() {
        $node_id = I('post.node_id', null, 'mysql_real_escape_string');
        $phoneNo = I('post.phone', null, 'mysql_real_escape_string');
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
        if (! empty($groupCheckCode['number']) &&
             $groupCheckCode['number'] != $checkCode)
            $this->ajaxReturn(array(
                'type' => 'pass'), '动态密码不正确', 0);
        if (time() - $groupCheckCode['add_time'] > $this->CodeexpiresTime)
            $this->ajaxReturn(array(
                'type' => 'pass'), '动态密码已经过期', 0);
            
            // 记录session
        session('groupPhone', $phoneNo);
        $this->success('登录成功');
    }
    
    // 用户订单查看
    public function showOrderList() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->showMsg('您还没有登录');
        }
        
        $type = I('get.type', 'normal', 'string');
        $this->assign('type', $type);
        $where = array();
        $where['o.order_phone'] = session('groupPhone');
        $where['o.node_id'] = $this->node_id;
        $where['o.order_status'] = array(
            'not in', 
            '2,3');
        switch ($type) {
            case 'book':
                $where['o.other_type'] = '1';
                break;
            default:
                $where['o.other_type'] = '0';
        }
        
        $orderList = M()->table('ttg_order_info o')
            ->join('ttg_order_info_ex t on t.order_id = o.order_id')
            ->join('tbatch_channel tbc ON o.from_channel_id = tbc.id')
            ->field(
            'o.*, t.ecshop_sku_id, t.ecshop_sku_desc,t.goods_num, tbc.id as tbcid, tbc.batch_type')
            ->where($where)
            ->group('o.order_id')
            ->order('o.add_time DESC')
            ->select();
        // 获取城市信息
        foreach ($orderList as $key => &$val) {
            if ($val['receiver_citycode']) {
                $cityInfo = M('tcity_code')->where(
                    array(
                        'path' => $val['receiver_citycode']))
                    ->field(
                    'province_code, city_code, town_code, province, city, town')
                    ->find();
                $orderList[$key]['province_code'] = $cityInfo['province_code'];
                $orderList[$key]['city_code'] = $cityInfo['city_code'];
                $orderList[$key]['town_code'] = $cityInfo['town_code'];
                $orderList[$key]['province'] = $cityInfo['province'];
                $orderList[$key]['city'] = $cityInfo['city'];
                $orderList[$key]['town'] = $cityInfo['town'];
            }
        }
        $status = array(
            '1' => '未支付', 
            '2' => '已支付');
        $this->assign('orderList', $orderList);
        $this->assign('status', $status);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('node_id', $this->node_id);
        $this->display();
    }

    /**
     * 订购配送查询
     */
    public function bookOrderDelivery() {
        $this->assign('title', '配送查询');
        
        $orderid = I('get.order');
        $simpleInfo = M()->table("ttg_order_info ttoi")->join(
            'ttg_order_info_ex ttoie ON ttoie.order_id = ttoi.order_id')
            ->join('tcity_code tcc ON tcc.path = ttoi.receiver_citycode')
            ->join('tbatch_info tbi ON tbi.id = ttoie.b_id')
            ->join('tgoods_info tgi ON tgi.goods_id = tbi.goods_id')
            ->field(
            'ttoi.order_id, ttoi.buy_num, ttoi.receiver_name, ttoi.receiver_addr, ttoi.receiver_tel, ttoi.receiver_phone, ttoi.book_delivery_date, ttoie.ecshop_sku_desc, tcc.province, tcc.city, tcc.town, tgi.config_data')
            ->where(
            array(
                'ttoi.order_id' => $orderid, 
                'ttoi.node_id' => $this->node_id))
            ->find();
        if (empty($simpleInfo)) {
            $this->error('请确认订单来源！');
            exit();
        }
        
        $bookConfig = json_decode($simpleInfo['config_data'], TRUE);
        $this->assign('deliveryType', $bookConfig['cycle']['cycle_type']);
        $this->assign('simpleInfo', $simpleInfo);
        
        $skuType = explode('/', $simpleInfo['ecshop_sku_desc']);
        $time = (int) array_pop($skuType);
        
        $bookCycleCondition = array();
        $bookCycleCondition['order_id'] = $orderid;
        $bookCycleCondition['node_id'] = $this->node_id;
        $bookCycleCondition['delivery_status'] = array(
            'in', 
            '2,3');
        $bookOrderList = M('ttg_order_by_cycle')->order('delivery_status ASC')
            ->where($bookCycleCondition)
            ->select();
        
        $this->assign('bookOrderList', $bookOrderList);
        
        $sendCount = count($bookOrderList);
        $this->assign('sendCount', $sendCount);
        
        $remainCount = $time * $simpleInfo['buy_num'] - $sendCount;
        $this->assign('remainCount', $remainCount);
        
        $nextDate = '';
        if ($remainCount > 0) {
            $bookCycleCondition['delivery_status'] = '1';
            $nextDate = M('ttg_order_by_cycle')->order(
                array(
                    'id' => 'ASC'))
                ->where($bookCycleCondition)
                ->getfield('dispatching_date');
        }
        $this->assign('nextDate', $nextDate);
        
        $this->display();
    }

    /**
     * 改变订购订单状态
     */
    public function changeBookOrderStatus() {
        $type = I('post.type', 'normal', 'string');
        switch ($type) {
            case 'del':
                $orderId = I('post.orderId', '0', 'string');
                M('ttg_order_info')->where(
                    array(
                        'order_id' => $orderId, 
                        'node_id' => $this->node_id))->save(
                    array(
                        'order_status' => '3'));
                break;
            default:
                $condition = I('post.con', '0', 'string');
                $conArray = explode('-', $condition);
                M('ttg_order_by_cycle')->where(
                    array(
                        'id' => $conArray[1], 
                        'order_id' => $conArray[0], 
                        'node_id' => $this->node_id))->save(
                    array(
                        'delivery_status' => '3'));
        }
    }

    /**
     * 获取订购订单物流信息
     */
    public function bookOrderExpressInfo() {
        $condition = I('post.con', '0', 'string');
        $conArray = explode('-', $condition);
        $expressInfo = M('torder_express_info')->where(
            array(
                'order_id' => $conArray[0], 
                'book_order_id' => $conArray[1]))->getField('express_content');
        $this->ajaxReturn(json_decode($expressInfo, TRUE));
    }

    /**
     * 改变订购订单收货人
     */
    public function changeBookOrderReceiver() {
        $orderId = I('post.orderId', '0', 'string');
        $addrId = I('post.addrId', '0', 'string');
        $phoneAddressModel = D('TphoneAddress');
        $defindeAddr = $phoneAddressModel->getDefinedPhoneAddress($addrId);
        $saveData = array();
        $saveData['receiver_name'] = $defindeAddr['user_name'];
        $saveData['receiver_citycode'] = $defindeAddr['path'];
        $saveData['receiver_addr'] = $defindeAddr['address'];
        $saveData['receiver_phone'] = $defindeAddr['phone_no'];
        $saveData['receiver_tel'] = $defindeAddr['phone_no'];
        M('ttg_order_info')->where(
            array(
                'order_id' => $orderId, 
                'node_id' => $this->node_id))->save($saveData);
        M('ttg_order_by_cycle')->where(
            array(
                'order_id' => $orderId, 
                'node_id' => $this->node_id, 
                'delivery_status' => '1'))->save($saveData);
    }

    /**
     * 全部优惠券
     */
    public function code() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录！');
        }
        $this->assign('type', 'code');

        $where = array(
            "b.node_id" => $this->node_id,
            "b.phone_no" => session('groupPhone'),
            "b.trans_type" => '0001',
            "b.status" => '0');
        
        $barcodeTraceModel = D('TbarcodeTrace');
        $goodsList = $barcodeTraceModel->getCodeArray($where);

        $goodsList = $this->generateDaysAndDiscounts($goodsList);

         $this->assign('node_id', $this->node_id);
         $this->assign('goodsList', $goodsList);
         $this->display();
     }


    /**
      * 未使用的优惠券
      */
    public function unusedCode() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录！');
        }
        $this->assign('type', 'unusedCode');
        $where = array(
            "b.node_id" => $this->node_id, 
            "b.phone_no" => session('groupPhone'), 
            "b.trans_type" => '0001', 
            "b.status" => '0', 
            "b.use_status" => '0', 
            "b.end_time" => array(
                'egt',
                date('YmdHis')));

        $barcodeTraceModel = D('TbarcodeTrace');
        $goodsList = $barcodeTraceModel->getCodeArray($where);

        //有效日期红字提示，打折额度
        $goodsList = $this->generateDaysAndDiscounts($goodsList);

        $this->assign('node_id', $this->node_id);
        $this->assign('goodsList', $goodsList);
        $this->display('code');
    }

    /**
     * 使用过的优惠券
     */
    public function usedCode() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录！');
        }
        $this->assign('type', 'usedCode');
        
        $where = array(
            "b.node_id" => $this->node_id, 
            "b.phone_no" => session('groupPhone'), 
            "b.trans_type" => '0001', 
            "b.status" => '0', 
            "b.use_status" => '2');
        
        $barcodeTraceModel = D('TbarcodeTrace');
        $goodsList = $barcodeTraceModel->getCodeArray($where);
        //打折额度
        $goodsList = $this->generateDiscounts($goodsList);
        $this->assign('node_id', $this->node_id);
        $this->assign('goodsList', $goodsList);
        $this->display('code');
    }

    /**
     * 过期优惠券
     */
    public function overUsedCode() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录！');
        }
        $this->assign('type', 'overUsedCode');
        
        $where = array(
            "b.node_id" => $this->node_id, 
            "b.phone_no" => session('groupPhone'), 
            "b.trans_type" => '0001', 
            "b.status" => '0', 
            "b.end_time" => array(
                'lt', 
                date('YmdHis')));
        
        $barcodeTraceModel = D('TbarcodeTrace');
        $goodsList = $barcodeTraceModel->getCodeArray($where);
        //打折额度
        $goodsList = $this->generateDiscounts($goodsList);
        $this->assign('node_id', $this->node_id);
        $this->assign('goodsList', $goodsList);
        $this->display('code');
    }

    /**
     * 优惠券详情
     */
    public function code_detail() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->error('您还没有登录');
        }


        $type = I('get.type', 'fail', 'string');
        $this->assign('type', $type);
        $code_seq = I("code_seq");
        if ($code_seq == "") {
            $this->error('参数错误');
        }
        
        $where = array();
        $where['b.node_id'] = $this->node_id;
        $where['b.req_seq'] = $code_seq;
        
        $WithdrawService = D('Withdraw', 'Service');
        $goodsInfo = $WithdrawService->getWithdrawInfo($where);
        $goodsInfo['barcode_bmp'] = $goodsInfo['barcode_bmp'] ? 'data:image/png;base64,' .
             base64_encode(
                $this->_bar_resize(base64_decode($goodsInfo['barcode_bmp']), 
                    'png')) : '';
        
        if ($goodsInfo['end_time'] < date('YmdHis') &&
             $goodsInfo['use_status'] != '2') {
            $goodsInfo['satus'] = 'expire';
        } elseif ($goodsInfo['begin_time'] > date('YmdHis') &&
             $goodsInfo['use_status'] != '2') {
            $goodsInfo['satus'] = 'beforeExpire';
        }
        $this->assign('node_id', $this->node_id);
        $this->assign('goodsInfo', $goodsInfo);
        
        // 查询热线
        $telphone = M('tnode_info')->where(array('node_id' => $this->node_id))->getField('node_service_hotline');
        $this->assign('telphone', $telphone);
        
        $goodsModel = D('Goods');
        $shopList = $goodsModel->getGoodsShop($goodsInfo['goods_id'], TRUE, $this->node_id, 'noOnline');
        $this->assign('shopCount', count($shopList));

        $this->display();
    }
    
    // 我的红包
    public function myBonus() {
        $show_wx = 0;
        $wxUserInfo = session('wxUserInfo');
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            if (! $wxUserInfo['openid']) {
                $surl = U('Label/MyOrder/myBonus', 
                    array(
                        'node_id' => $this->node_id), '', '', true);
                redirect(
                    U('Label/O2OLogin/wcWeixinCallback', 
                        array(
                            'id' => $this->id, 
                            'node_id' => $this->node_id, 
                            'surl' => urlencode($surl))));
            } else {
                $show_wx = 1;
                // $cPhone =
                // M('to2o_wx_config')->where(array('openid'=>$wxUserInfo['openid']))->getField('phone_no');
                $cPhone = session('groupPhone');
            }
        }
        $Phone = session('groupPhone');
        $map['b.phone'] = $Phone;
        $map['b.node_id'] = $this->node_id;
        
        $list = M()->table("tbonus_use_detail b")->field(
            'b.*,m.bonus_page_name,m.bonus_amount,d.amount,m.bonus_start_time,m.bonus_end_time')
            ->join('tbonus_info m on b.bonus_id=m.id')
            ->join('tbonus_detail d on d.id=b.bonus_detail_id')
            ->where($map)
            ->order('m.bonus_end_time desc,b.id asc')
            ->select();
        $this->assign('node_id', session('cc_node_id'));
        $this->assign('list', $list);
        $this->assign('show_wx', $show_wx);
        $this->assign('cPhone', $cPhone);
        $this->assign('now_time', date('YmdHis'));
        $this->display();
    }

    public function _bar_resize($data, $other) {
        $im = $this->_img_resize($data, 3);
        if ($im !== false) {
            ob_start();
            switch ($other) {
                case 'gif':
                    imagegif($im);
                    break;
                case 'jpg':
                case 'jpeg':
                    imagejpeg($im);
                    break;
                case 'png':
                    imagepng($im);
                    break;
                case 'wbmp':
                    imagewbmp($im);
                    break;
                default:
                    return false;
                    break;
            }
            imagedestroy($im);
            $new_img = ob_get_contents();
            ob_end_clean();
            return $new_img;
        } else {
            return false;
        }
    }

    public function logout() {
        if (session('groupPhone') != "") {
            session('groupPhone', null);
        }
        $id = I("id");
        $id = empty($id) ? session('id') : $id;
        if ($id != "") {
            $url = "location:index.php?g=Label&m=Label&a=index&id=" . $id;
        } else {
            $url = "location:index.php?g=Label&m=Member&a=index&id=" . $this->id .
                 "&node_id=" . session("node_id");
        }
        header($url);
    }

    public function _img_resize($data, $fdbs) {
        // Resize
        $source = imagecreatefromstring($data);
        $s_white_x = 0; //
        $s_white_y = 0; //
        $s_w = imagesx($source); // 原图宽度
        $new_img_width = ($s_w) * $fdbs;
        $new_img_height = $new_img_width;
        
        // 新的偏移量
        $d_white_x = ($new_img_width - $s_w * $fdbs) / 2;
        $d_white_y = $d_white_x;
        
        // Load
        $thumb = imagecreate($new_img_width, $new_img_height);
        // $red = imagecolorallocate($thumb, 255, 255, 255);
        
        imagecopyresized($thumb, $source, $d_white_x, $d_white_y, $s_white_x, 
            $s_white_y, $s_w * $fdbs, $s_w * $fdbs, $s_w, $s_w);
        return $thumb;
    }
    
    // 输出信息页面
    protected function showMsg($info, $status = 0, $id = '', $node_short_name = '') {
        $this->assign('id', $id);
        $this->assign('status', $status);
        $this->assign('info', $info);
        $this->assign('node_short_name', $node_short_name);
        $this->display('msg');
        exit();
    }

    public function gotoPay() {
        $order_id = I("order_id");
        if ($order_id == "") {
            $this->error("参数错误");
        }
        $orderInfo = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id))
            ->field('pay_channel,batch_channel_id')
            ->find();
        if ($orderInfo['pay_channel'] == '2') {
            // 去支付
            $payModel = A('Label/PayUnion');
            $payModel->OrderPay($order_id);
        } elseif ($orderInfo['pay_channel'] == '1') {
            if ($this->wx_flag == 1) {
                // 微信中用支付宝支付则跳转到中转页面
                redirect(
                    U('Label/PayConfirm/index', 
                        array(
                            'order_id' => $order_id, 
                            'id' => $orderInfo['batch_channel_id'])));
            } else {
                $payModel = A('Label/PayMent');
                $payModel->OrderPay($order_id);
            }
        } elseif ($orderInfo['pay_channel'] == '3') {
            $payModel = A('Label/PayWeixin');
            $payModel->goAuthorize($order_id);
        }
        // $payModel = A('Label/PayMent');
        // $payModel->OrderPay($order_id);
        exit();
    }

    public function showOrderInfo() {
        // 判断是否登录
        if (! session('?groupPhone')) {
            $this->showMsg('您还没有登录');
        }
        $order_id = I('order_id', null);
        if (! $order_id) {
            $this->error("参数错误");
        }
        $where = array(
            'o.order_id' => $order_id);
        
        $orderInfo = M('ttg_order_info')->where(
            array(
                'order_id' => $order_id, 
                'node_id' => $_SESSION['node_id']))->find();
        if (! empty($orderInfo)) {
                $orderListInfo = M()->table('ttg_order_info o')
                    ->join('ttg_order_info_ex as t on o.order_id = t.order_id')    
                    ->join('tbatch_info as b ON b.id = t.b_id')
                    ->field(
                    'o.*,b.batch_img,o.buy_num AS goods_num,b.batch_name AS b_name, t.ecshop_sku_id, t.ecshop_sku_desc, t.price')
                    ->where(
                    array(
                        't.order_id' => $order_id))
                    ->select();
            // 送礼数据
            $giftInfo = M('ttg_order_gift')->where(
                array(
                    'order_id' => $order_id))->find();
            // 领取数据
            $codeTrace = M('torder_trace')->where(
                array(
                    'order_id' => $order_id))->select();
            $hav_count = count($codeTrace);
            // 红包数据
            $bonusInfo = M('tbonus_use_detail')->where(
                array(
                    'order_id' => $order_id))->getField('bonus_amount');
            if (! $bonusInfo)
                $bonusInfo = 0;
            
            $deliveryStatus = array(
                '1' => '待配送', 
                '2' => '配送中', 
                '3' => '已配送', 
                '4' => '凭证自提');
            $status = array(
                '1' => '未支付', 
                '2' => '已支付');
            if ($orderInfo['receiver_type'] == '1') {
                $orderExpressInfoModel = M('TorderExpressInfo');
                $orderExpressInfo = $orderExpressInfoModel->where(
                    array(
                        'node_id' => $_SESSION['node_id'], 
                        'order_id' => $orderInfo['order_id']))->getfield(
                    'express_content');
                $orderExpressInfoArray = json_decode($orderExpressInfo, TRUE);
                $this->assign('expressInfo', $orderExpressInfoArray);
            }
        }
        // 获取城市信息
        $orderInfo = D('StoreOrder')->getOrderAdress($orderInfo);
        
        $this->assign('orderInfo', $orderInfo);
        $this->assign('orderListInfo', $orderListInfo);
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('status', $status);
        $this->assign('node_short_name', $this->node_short_name);
        $this->assign('codeTrace', $codeTrace);
        $this->assign('giftInfo', $giftInfo);
        $this->assign('bonusInfo', $bonusInfo);
        $this->assign('hav_count', $hav_count);
        $this->assign('node_id', session('node_id'));
        $this->assign('qmyxflag', $qmyxflag);
        
        $this->display();
    }
    
    // 发送更换绑定手机号的验证码
    public function sendChangeCode() {
        
        // 图片校验码
        /*
         * $verify = I('post.verify',null,'mysql_real_escape_string');
         * if(session('verify') != md5($verify)) { $this->error("图片验证码错误"); }
         */
        $phoneNo = I('post.change_phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("换绑手机号{$error}");
        }
        // 测试环境不下发，验证码直接为1111
        if (! is_production()) {
            $groupChangeCode = array(
                'number' => 1111, 
                'add_time' => time(), 
                'phoneNo' => $phoneNo);
            session('groupChangeCode', $groupChangeCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }
        // 发送频率验证
        $groupChangeCode = session('groupChangeCode');
        if (! empty($groupChangeCode) &&
             (time() - $groupChangeCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->CodeexpiresTime / 60;
        $text = "您在{$this->node_short_name}商户的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
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
        $groupChangeCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('groupChangeCode', $groupChangeCode);
        $this->success('动态密码已发送');
    }

    public function updateChangePhone() {
        $change_phone = I('change_phone', null, 'mysql_real_escape_string');
        if (! check_str($change_phone, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->ajaxReturn(array(
                'type' => 'phone'), "换绑手机号{$error}", 0);
        }
        // 手机验证码
        $checkCode = I('check_code', null);
        if (! check_str($checkCode, array(
            'null' => false), $error)) {
            $this->ajaxReturn(array(
                'type' => 'pass'), "动态密码{$error}", 0);
        }
        $groupChangeCode = session('groupChangeCode');
        if (! empty($groupChangeCode) &&
             $groupChangeCode['phoneNo'] != $change_phone)
            $this->ajaxReturn(array(
                'type' => 'phone'), '手机号码不正确', 0);
        if (! empty($groupChangeCode) && $groupChangeCode['number'] != $checkCode)
            $this->ajaxReturn(array(
                'type' => 'pass'), '动态密码不正确', 0);
        if (time() - $groupChangeCode['add_time'] > $this->CodeexpiresTime)
            $this->ajaxReturn(array(
                'type' => 'pass'), '动态密码已经过期', 0);
            // 更换数据库
        $wxUserInfo = session('wxUserInfo');
        if ($wxUserInfo['openid']) {
            // 有openid则更新绑定手机号否则只更换登录手机号
            $ret = M('to2o_wx_config')->where(
                array(
                    'openid' => $wxUserInfo['openid']))->save(
                array(
                    'phone_no' => $change_phone));
            if ($ret === false)
                $this->ajaxReturn(
                    array(
                        'type' => 'pass'), "换绑手机号更新失败", 0);
        }
        // 记录session
        session('groupPhone', $change_phone);
        
        $this->success('登录成功');
    }

    /*
     * 取消订单 订单状态，回滚库存，取消红包
     */
    public function ordercancel() {
        $order_id = I("order_id");
        if ($order_id == "") {
            $this->showMsg("参数错误");
        }
        $orderInfo = M('ttg_order_info')
                ->where(array('order_id' => $order_id))
                ->find();
        if (! $orderInfo){
            $this->showMsg("订单数据错误");
        }    
        if ($orderInfo['order_status'] != '0'){
            $this->showMsg("订单状态有误，无法取消订单！");
        }    
        if ($orderInfo['pay_status'] != '1'){
            $this->showMsg("订单支付状态有误，无法取消订单！");
        }    
            
            // 开启事务
        M()->startTrans();
        // 更新订单状态
        $result = M('ttg_order_info')
                ->where(array('order_id' => $order_id))
                ->save(array('order_status' => '2','update_time' => date('YmdHis')));
        if ($result === false) {
            M()->rollback();
            $this->showMsg("订单状态更新失败");
        }
        
        // 更新库存
        if ($orderInfo['order_type'] == '2') { // 小店订单
            $exorderList = M('ttg_order_info_ex')
                    ->where(array('order_id' => $order_id))
                    ->select();
            if (! $exorderList) {
                M()->rollback();
                $this->showMsg("子订单数据错误");
            }
            foreach ($exorderList as $v) {
                $result = M('tbatch_info')->where(array('id' => $v['b_id'],'storage_num' => array('neq', '-1')))->setInc('remain_num', $v['goods_num']);
                if(false === $result){
                    M()->rollback();
                    $this->showMsg("小店订单商品库存回滚失败");
                }
                //解锁订单
                $result = M('tbatch_info')->where(['id' => $v['b_id']])->setDec('lock_num', $v['goods_num']);
                if(false === $result){
                    M()->rollback();
                    $this->showMsg("小店订单商品解锁失败");
                }
                // sku订单撤销
                $skuId = (int) $v['ecshop_sku_id'];
                if ($skuId > 0) {
                    // 创建sku信息
                    $skuObj = D('Sku', 'Service');
                    $result = $skuObj->returnGoodsNum($v['goods_num'], $skuId);
                    if ($result === false) {
                        M()->rollback();
                        $this->showMsg("小店订单商品库存回滚失败");
                    }
                }
            }
        } elseif ($orderInfo['order_type'] == '0') { // 单品订单
            $result = M('tbatch_info')
                    ->where(array('m_id' => $orderInfo['batch_no'],'storage_num' => array('neq', '-1')))
                    ->setInc('remain_num', $orderInfo['buy_num']);
            if ($result === false) {
                M()->rollback();
                $this->showMsg("单品订单商品库存回滚失败");
            }
            //解锁订单
            $result = M('tbatch_info')->where(['m_id' => $orderInfo['batch_no']])->setDec('lock_num', $orderInfo['buy_num']);
            if(false === $result){
                M()->rollback();
                $this->showMsg("单品订单商品解锁失败");
            }
            // sku订单撤销
            if ($order_id) {
                // 创建sku信息
                $skuObj = D('Sku', 'Service');
                $result = $skuObj->returnGoodsNum(0, 0, $order_id);
                if ($result === false) {
                    M()->rollback();
                    $this->showMsg("小店规格订单商品库存回滚失败");
                }
            }
            // 吴刚砍树订单状态修改
            $buyStatus = M('twx_cuttree_info')->where("order_id={$order_id}")->getField('buy_status');
            if (! empty($buyStatus) && $buyStatus == '1') {
                $res = M('twx_cuttree_info')->where("order_id={$order_id}")->save(
                    array(
                        'buy_status' => '0', 
                        'order_id' => ''));
                if ($res === false) {
                    M()->rollback();
                    $this->error('处理失败：cut订单状态更新失败');
                }
            }
        }
        if ($orderInfo['point_use'] > 0) {
            // 更新积分
            $result = D('MemberInstall')->backMemberPoint($this->node_id, 
                $orderInfo['order_phone'], $order_id);
            if ($result === false) {
                M()->rollback();
                $this->error('处理失败：您的积分返回失败');
            }
        }
        // 更新红包
        $bonusInfo = M('tbonus_use_detail')->where(
            array(
                'order_id' => $order_id))->select();
        if ($bonusInfo) {
            foreach ($bonusInfo as $v) {
                $result = M('tbonus_detail')->where(
                    array(
                        'id' => $v['bonus_detail_id'], 
                        'node_id' => $this->node_id))->setDec('use_num', 
                    $v['bonus_use_num']);
                if ($result === false) {
                    M()->rollback();
                    $this->showMsg("红包使用数量统计回滚失败");
                }
            }
            // 统一更新他bonus_use_detail
            $result = M('tbonus_use_detail')->where(
                array(
                    'order_id' => $order_id))->save(
                array(
                    'order_id' => '', 
                    'bonus_use_num' => 0, 
                    'bonus_amount' => 0, 
                    'use_time' => '', 
                    'order_amt_per' => 0));
            if ($result === false) {
                M()->rollback();
                $this->showMsg("红包使用明细回滚失败");
            }
        }
        M()->commit();
        $this->showMsg('订单取消成功', 1, $this->id, $this->node_short_name);
        exit();
    }
    
    // 购买者领取剩下的所有礼品
    public function getLeftGift() {
        $order_id = I('order_id');
        $orderModel = M("ttg_order_info");
        $orderInfo = $orderModel->alias("o")->join(
            "ttg_order_info_ex t ON t.order_id = o.order_id")
            ->join('tbatch_info b ON b.id=t.b_id')
            ->field("o.*, t.ecshop_sku_desc,b.batch_short_name,b.use_rule")
            ->where("o.order_id={$order_id}")
            ->find();
        if ($orderInfo['pay_status'] != '2')
            $this->error('订单尚未支付');
        if ($orderInfo['is_gift'] != '1')
            $this->error('订单为非送礼类订单');
            // 送礼类型
        $giftInfo = M('ttg_order_gift')->where(
            array(
                'order_id' => $order_id))->find();
        if ($giftInfo['gift_type'] != '1')
            $this->error('订单为非微信送礼类订单，无法领取剩余礼品');
            // 已领
        $count = M('torder_trace')->where(
            array(
                'order_id' => $order_id))->count();
        if ($count >= $orderInfo['buy_num'])
            $this->error('订单已被全部领取，无法领取剩余礼品');
            
            // 处理发码
        $sendCount = $orderInfo['buy_num'] - $count;
        if ($sendCount < 1)
            $this->error('订单已无剩余礼品');
            // 生成发码内容
        $textInfo = array();
        $textInfo['use_rule'] .= $orderInfo['batch_short_name'];
        if (isset($orderInfo['ecshop_sku_desc'])) {
            $textInfo['use_rule'] .= '[' . $orderInfo['ecshop_sku_desc'] . ']';
            $textInfo['print_text'] .= $orderInfo['ecshop_sku_desc'];
        }
        $textInfo['use_rule'] .= $orderInfo['use_rule'];
        
        $channelId = M('tbatch_channel')->where(
            array(
                'id' => $orderInfo['batch_channel_id']))->getField('channel_id');
        if ($orderInfo['order_type'] == '2') {
            $ecgoodsInfo = M()->table('ttg_order_info_ex g')
                ->field('g.*,b.batch_no,b.m_id')
                ->join('tbatch_info b ON b.id=g.b_id')
                ->where(array(
                'g.order_id' => $order_id))
                ->find();
            $mId = $ecgoodsInfo['m_id'];
            $bId = $ecgoodsInfo['b_id'];
            $issBatchNo = $ecgoodsInfo['batch_no'];
        } elseif ($orderInfo['order_type'] == '0') {
            $batchInfo = M('tbatch_info')->where(
                array(
                    'm_id' => $orderInfo['batch_no']))->find();
            $mId = $orderInfo['batch_no'];
            $bId = $batchInfo['id'];
            $issBatchNo = $batchInfo['batch_no'];
        }
        for ($i = 1; $i <= $sendCount; $i ++) {
            $ret = D('SalePro', 'Service')->sendCode2($orderInfo['order_id'], 
                $orderInfo['order_type'], $orderInfo['node_id'], $issBatchNo, 
                $orderInfo['receiver_phone'], $bId, $mId, $channelId, 
                $orderInfo['receiver_phone'], $textInfo);
        }
        $this->success('领取成功');
    }
    
    // 获取用户信息
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

    function myCustomer() {
        $this->assign('title', '我的客户');
        $wfxSalerModel = M('TwfxSaler');
        
        $count = $wfxSalerModel->where(
            array(
                'parent_id' => $_SESSION['twfxSalerID'], 
                'role' => '1', 
                'node_id' => $this->node_id, 
                'status' => array(
                    'not in', 
                    array(
                        '2', 
                        '4'))))->count();
        import('ORG.Util.Page');
        $Page = new Page($count, $this->pageSize);
        if ($_GET['p'] > ceil($count / $this->pageSize) && $this->isAjax()) {
            return;
        }
        
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nexUrl = U('Label/MyOrder/myCustomer', 
            array(
                'p' => ($nowPage + 1)));
        
        $marketInfo = $wfxSalerModel->where(
            array(
                'parent_id' => $_SESSION['twfxSalerID'], 
                'role' => '1', 
                'node_id' => $this->node_id, 
                'status' => array(
                    'not in', 
                    array(
                        '2', 
                        '4'))))
            ->field('id, name, status')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('id DESC')
            ->select();
        
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', $wechatConfig['app_id'], $wechatConfig['app_secret']);
        $this->assign('shareData', $shareConfig);
        
        $this->assign('marketInfo', $marketInfo);
        $this->assign('count', count($marketInfo));
        $this->assign('nextUrl', $nexUrl);
        $this->display();
    }

    function dealerCustomer() {
        $this->assign('title', '我的客户');
        $searchCondition = array();
        $phone = I('phone');
        if ($phone) {
            $customerSearchCondition['phone_no'] = $phone;
        }
        
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        
        $salerID = I('salerID', '0', 'string');
        if ($salerID == '0') {
            $customerSearchCondition['saler_id'] = $_SESSION['twfxSalerID'];
            $nexUrl = U('Label/MyOrder/dealerCustomer', 
                array(
                    'p' => ($nowPage + 1), 
                    'phone' => $phone));
        } else {
            $customerSearchCondition['saler_id'] = $salerID;
            $nexUrl = U('Label/MyOrder/dealerCustomer', 
                array(
                    'salerID' => $salerID, 
                    'p' => ($nowPage + 1), 
                    'phone' => $phone));
        }
        
        $customerSearchCondition['node_id'] = $this->node_id;
        
        $wfxCustomerRelationModel = M('TwfxCustomerRelation');
        $count = $wfxCustomerRelationModel->where($customerSearchCondition)->count();
        
        import('ORG.Util.Page');
        $Page = new Page($count, $this->pageSize);
        if ($_GET['p'] > ceil($count / $this->pageSize) && $this->isAjax()) {
            return;
        }
        
        $dealerCustomerInfo = $wfxCustomerRelationModel->where(
            $customerSearchCondition)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('status DESC')
            ->select();
        
        $this->assign('client', $dealerCustomerInfo);
        $this->assign('count', count($dealerCustomerInfo));
        
        if ($_SESSION['twfxRole'] == '2') {
            $searchCondition['parent_id'] = $_SESSION['twfxSalerID'];
            $searchCondition['status'] = '3';
            $searchCondition['role'] = '1';
            $wfxSalerModel = M('TwfxSaler');
            $salerInfo = $wfxSalerModel->where($searchCondition)
                ->field('name, id')
                ->order('id DESC')
                ->select();
            $this->assign('salerInfo', $salerInfo);
            $this->assign('couldMove', '1');
        }
        
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', $wechatConfig['app_id'], $wechatConfig['app_secret']);
        $this->assign('shareData', $shareConfig);
        
        $this->assign('role', $_SESSION['twfxRole']);
        $this->assign('nextUrl', $nexUrl);
        $this->assign('salerID', $salerID);
        $this->display();
    }

    function changeCustomer() {
        $changeCustomerID = I('post.changeID', '0', 'string');
        $changeToSalerID = I('post.changeToID', '0', 'string');
        $result = array();
        $wfxCustomerRelationModel = M('TwfxCustomerRelation');
        $wfxCustomerRelationModel->where(
            array(
                'node_id' => $this->node_id, 
                'id' => $changeCustomerID))->save(
            array(
                'saler_id' => $changeToSalerID, 
                'status' => '1'));
        $result['error'] = '0';
        $this->ajaxReturn($result);
    }

    function myCommission() {
        if ($_SESSION['twfxRole'] == '2') {
            $this->assign('title', '经销商管理中心');
        } else {
            $this->assign('title', '销售员管理中心');
        }
        
        $searchCondition = array();
        $searchCondition['node_id'] = $this->node_id;
        $searchCondition['saler_id'] = $_SESSION['twfxSalerID'];
        
        $wfxTraceModel = M('twfx_trace');
        $totalCommission = $wfxTraceModel->where($searchCondition)->sum(
            'bonus_amount');
        if (empty($totalCommission)) {
            $totalCommission = $this->_getPriceFormat();
        }
        $this->assign('totalCommission', explode('.', $totalCommission));
        
        $searchCondition['user_get_flag'] = '3';
        $commission = $wfxTraceModel->where($searchCondition)->sum(
            'bonus_amount');
        
        if (empty($commission)) {
            $commission = $this->_getPriceFormat();
        }
        $this->assign('commission', explode('.', $commission));
        
        $searchCondition['user_get_flag'] = '0';
        $thisMonthBonusCommission = $wfxTraceModel->where($searchCondition)->sum(
            'bonus_amount');
        if (empty($thisMonthBonusCommission)) {
            $thisMonthBonusCommission = $this->_getPriceFormat();
        }
        $this->assign('remain_amount', $thisMonthBonusCommission);
        
        $thisMonth = date("Ym") . '00000000,' . date("Ym") . '31235959';
        $searchCondition['add_time'] = array(
            'between', 
            $thisMonth);
        $searchCondition['user_get_flag'] = array(
            'neq', 
            '100');
        $thisMonthCommission = $wfxTraceModel->where($searchCondition)->sum(
            'bonus_amount');
        
        if (empty($thisMonthCommission)) {
            $thisMonthCommission = $this->_getPriceFormat();
        }
        $this->assign('thisMonthCommission', explode('.', $thisMonthCommission));
        
        $wfxSalerModel = M('twfx_saler');
        $infoArray = $wfxSalerModel->join(
            'twfx_node_info ON twfx_node_info.node_id = twfx_saler.node_id')
            ->where(
            array(
                'twfx_saler.id' => $searchCondition['saler_id'], 
                'twfx_saler.node_id' => $this->node_id, 
                'twfx_saler.phone_no' => $_SESSION['groupPhone']))
            ->field(
            'twfx_saler.bank_account, twfx_saler.bank_name, twfx_node_info.settle_type, twfx_node_info.lowest_get_money, twfx_node_info.account_type, twfx_saler.alipay_account, twfx_node_info.customer_bind_flag')
            ->find();
        $this->assign('customer', $infoArray['customer_bind_flag']);
        
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', $wechatConfig['app_id'], $wechatConfig['app_secret']);
        $this->assign('shareData', $shareConfig);
        
        if ($infoArray['account_type'] == '1') {
            $this->assign('account', $infoArray['alipay_account']);
            $this->assign('accountType', '支付宝帐');
            $this->assign('bankArray', 0);
        } elseif ($infoArray['account_type'] == '2') {
            $time = floor(strlen($infoArray['bank_account']) / 4);
            $accountStr = '';
            for ($i = 0; $i <= $time; $i ++) {
                $accountStr .= substr($infoArray['bank_account'], $i * 4, 4) .
                     ' ';
            }
            
            $key = array_search($infoArray['bank_name'], C('defaultBankName'));
            $bankArray = C('defaultBankName');
            unset($bankArray[$key]);
            array_unshift($bankArray, $infoArray['bank_name']);
            $this->assign('bankArray', json_encode($bankArray));
            $this->assign('account', $accountStr);
            $this->assign('bankName', $infoArray['bank_name']);
            $this->assign('accountType', '银行卡卡');
        }
        $this->assign('commissionType', $infoArray['settle_type']);
        $this->assign('lowestMoney', $infoArray['lowest_get_money']);
        
        $saleCount = $wfxSalerModel->where(
            array(
                'parent_id' => $_SESSION['twfxSalerID'], 
                'role' => '1', 
                'node_id' => $this->node_id, 
                'status' => array(
                    'neq', 
                    '5')))->count();
        $this->assign('saleCount', $saleCount);
        
        $wfxCustomerRelationModel = M('twfx_customer_relation');
        $customerCount = $wfxCustomerRelationModel->where(
            array(
                'node_id' => $this->node_id, 
                'saler_id' => $_SESSION['twfxSalerID'], 
                'status' => '1'))->count();
        $this->assign('customerCount', $customerCount);
        
        $recruitStatus = M('tmarketing_info')->where(
            array(
                'node_id' => $_SESSION['node_id'], 
                'batch_type' => '3001'))->getfield('status');
        $this->assign('recruitStatus', $recruitStatus);
        
        if ($_SESSION['twfxRole'] == '1') {
            $customerUrl = U('Label/MyOrder/dealerCustomer', 
                array(
                    'node_id' => $this->node_id));
        } elseif ($_SESSION['twfxRole'] == '2') {
            $undeliveryBookOrderCount = M('twfx_book_order')->where(
                array(
                    'node_id' => $this->node_id, 
                    'order_phone' => $_SESSION['groupPhone'], 
                    'delivery_status' => '0'))->count();
            $this->assign('bookCount', $undeliveryBookOrderCount);
            $customerUrl = U('Label/MyOrder/myCustomer', 
                array(
                    'node_id' => $this->node_id));
        }
        $this->assign('customerUrl', $customerUrl);
        $this->display();
    }

    function _getPriceFormat() {
        return '0.00';
    }

    function bind() {
        $result = array();
        $alipayAccount = I('post.aliaccount', '0', 'string');
        $alipayAccount = str_replace(' ', '', $alipayAccount);
        if (($alipayAccount == '' || $alipayAccount == '0')) {
            $result['error'] = '10001';
            $result['msg'] = '请核对' . $_POST['accountType'] . '号！';
            $this->ajaxReturn($result);
            exit();
        }
        
        $saveData = array();
        $wfxSalerModel = M('twfx_saler');
        if ($_POST['accountType'] == '支付宝帐') {
            $saveData['alipay_account'] = $alipayAccount;
        } elseif ($_POST['accountType'] == '银行卡卡') {
            $bankCountLength = strlen($alipayAccount);
            if ($bankCountLength < 16 || $bankCountLength > 19) {
                $result['error'] = '10001';
                $result['msg'] = '请核对银行卡卡号！';
                $this->ajaxReturn($result);
                exit();
            }
            
            $bankName = I('post.bankName');
            $saveData['bank_account'] = $alipayAccount;
            $saveData['bank_name'] = $bankName;
        }
        
        $alipayName = I('post.alipay_name', '0', 'string');
        
        $wfxSalerModel->where(
            array(
                'id' => $_SESSION['twfxSalerID'], 
                'node_id' => $this->node_id, 
                'phone_no' => $_SESSION['groupPhone']))->save($saveData);
        
        $result['error'] = '0';
        $result['msg'] = '绑定' . $_POST['accountType'] . '成功！';
        $result['account'] = I('post.aliaccount');
        $result['bankName'] = $bankName;
        $this->ajaxReturn($result);
    }

    function commissionDetails() {
        $this->assign('title', '提成明细');
        $type = I('get.type', '0', 'string');
        $searchCondition = array();
        if ($type == 'got') {
            $searchCondition['twfx_trace.user_get_flag'] = '3';
        } elseif ($type == 'thisMonth') {
            $thisMonth = date("Ym") . '00000000,' . date("Ym") . '31235959';
            $searchCondition['add_time'] = array(
                'between', 
                $thisMonth);
        }
        $this->assign('type', $type);
        
        $searchCondition['twfx_trace.node_id'] = $this->node_id;
        $searchCondition['twfx_trace.saler_id'] = $_SESSION['twfxSalerID'];
        
        $wfxTraceModel = M('TwfxTrace');
        
        $count = $wfxTraceModel->join(
            'twfx_node_info ON twfx_node_info.node_id = twfx_trace.node_id')
            ->where($searchCondition)
            ->count();
        
        import('ORG.Util.Page');
        $Page = new Page($count, $this->pageSize);
        if ($_GET['p'] > ceil($count / $this->pageSize) && $this->isAjax()) {
            return;
        }
        
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nexUrl = U('Label/MyOrder/commissionDetails', 
            array(
                'p' => ($nowPage + 1)));
        
        $wfxCommissionInfo = $wfxTraceModel->join(
            'twfx_node_info ON twfx_node_info.node_id = twfx_trace.node_id')
            ->where($searchCondition)
            ->field(
            'twfx_trace.phone_no, twfx_trace.customer_name as name, twfx_trace.add_time, twfx_trace.user_get_flag as deal_flag, twfx_trace.bonus_amount, twfx_node_info.settle_type')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('add_time DESC')
            ->select();
        $resultArray = $this->_getDataFormat($wfxCommissionInfo);
        
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', $wechatConfig['app_id'], $wechatConfig['app_secret']);
        $this->assign('shareData', $shareConfig);
        
        $this->assign('count', count($wfxCommissionInfo));
        $this->assign('commissionArray', $resultArray);
        $this->assign('nextUrl', $nexUrl);
        $this->display();
    }

    function getBonus() {
        $result = array();
        $wfxModel = D('Wfx');
        $searchCondition = array();
        $searchCondition['twfx_trace.node_id'] = $this->node_id;
        $searchCondition['twfx_trace.saler_id'] = $_SESSION['twfxSalerID'];
        $searchCondition['twfx_trace.user_get_flag'] = '0';
        
        $totalCountArray = $wfxModel->getTotalBonus($searchCondition);
        if (! $totalCountArray[0]['bonus_amount']) {
            $result['error'] = '10001';
            $result['msg'] = '暂无可提领的金额或者正在提领的路上！';
            $this->ajaxReturn($result);
            exit();
        }
        
        $lowestBonus = M('twfx_node_info')->where(
            array(
                'node_id' => $_SESSION['node_id']))->getfield('lowest_get_money');
        if ($totalCountArray[0]['bonus_amount'] < $lowestBonus) {
            $result['error'] = '10002';
            $result['msg'] = '提成高于' . $lowestBonus . '元才能申请提现。';
            $this->ajaxReturn($result);
            exit();
        }
        $addData = array();
        $addData['node_id'] = $this->node_id;
        $addData['saler_id'] = $_SESSION['twfxSalerID'];
        $addData['bonus_amount'] = $totalCountArray[0]['bonus_amount'];
        $addData['sale_amount'] = $totalCountArray[0]['amount'];
        $addData['alipay_acount'] = $totalCountArray[0]['alipay_account'];
        $addData['add_time'] = date("YmdHis");
        $addData['month'] = date("Ym");
        $addData['deal_flag'] = '0';
        
        $dateData = $wfxModel->getThisMonthFirstDayAndLastDay();
        $addData['start_get_date'] = $dateData['firstday'];
        $addData['end_get_date'] = $dateData['lastday'];
        
        $result = $wfxModel->saveGetBonusAction($searchCondition, $addData);
        $this->ajaxReturn($result);
    }

    function showGetBonus() {
        $this->assign('title', '提现明细');
        $searchCondition = array();
        $searchCondition['node_id'] = $this->node_id;
        $searchCondition['saler_id'] = $_SESSION['twfxSalerID'];
        
        $wfxGetTraceModel = M('TwfxGetTrace');
        $count = $wfxGetTraceModel->where($searchCondition)->count();
        
        import('ORG.Util.Page');
        $Page = new Page($count, $this->pageSize);
        if ($_GET['p'] > ceil($count / $this->pageSize) && $this->isAjax()) {
            return;
        }
        
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nexUrl = U('Label/MyOrder/showGetBonus', 
            array(
                'p' => ($nowPage + 1), 
                'node_id' => $_SESSION['node_id']));
        
        $infoArray = $wfxGetTraceModel->where($searchCondition)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('add_time DESC')
            ->select();
        
        $resultArray = $this->_getDataFormat($infoArray);
        
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', $wechatConfig['app_id'], $wechatConfig['app_secret']);
        
        $this->assign('shareData', $shareConfig);
        $this->assign('commissionArray', $resultArray);
        $this->assign('count', count($infoArray));
        $this->assign('nextUrl', $nexUrl);
        $this->display();
    }

    function myDistribution() {
        $this->assign('title', '我的分销');
        if ($_SESSION['twfxRole'] != '2') {
            header(
                "location:index.php?g=Label&m=Member&a=index&id=" . $this->id .
                     "&node_id=" . $_SESSION['node_id']);
            exit();
        }
        $wfxSalerModel = M('twfx_saler');
        $myName = $wfxSalerModel->where(
            array(
                'id' => $_SESSION['twfxSalerID'], 
                'node_id' => $_SESSION['node_id']))->getfield('name');
        $myMoney = M('twfx_trace')->where(
            array(
                'node_id' => $_SESSION['node_id'], 
                'saler_id' => $_SESSION['twfxSalerID']))->getfield(
            'SUM(twfx_trace.bonus_amount)');
        $this->assign('myName', $myName);
        $this->assign('myMoney', $myMoney);
        
        $searchCondition = array();
        $searchCondition['twfx_saler.node_id'] = $this->node_id;
        $searchCondition['twfx_saler.parent_id'] = $_SESSION['twfxSalerID'];
        $searchCondition['twfx_saler.status'] = '3';
        $searchCondition['twfx_saler.role'] = '2';
        
        $count = $wfxSalerModel->where($searchCondition)->count();
        
        import('ORG.Util.Page');
        $Page = new Page($count, $this->pageSize);
        if ($_GET['p'] > ceil($count / $this->pageSize) && $this->isAjax()) {
            return;
        }
        
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nexUrl = U('Label/MyOrder/myDistribution', 
            array(
                'p' => ($nowPage + 1)));
        
        $thisMonth = date("Ym");
        $joinStr = 'twfx_trace ON twfx_trace.saler_id = twfx_saler.id AND twfx_trace.add_time BETWEEN ' .
             $thisMonth . '00000000 AND ' . $thisMonth . '31235959';
        $distributionInfo = $wfxSalerModel->join($joinStr)
            ->where($searchCondition)
            ->field(
            'twfx_saler.name, twfx_saler.id, SUM(twfx_trace.bonus_amount) as money')
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->group('twfx_saler.id')
            ->select();
        
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', $wechatConfig['app_id'], $wechatConfig['app_secret']);
        $this->assign('shareData', $shareConfig);
        $this->assign('count', count($distributionInfo));
        $this->assign('distributionInfo', $distributionInfo);
        $this->assign('nextUrl', $nexUrl);
        $this->display();
    }

    function distributionDetail() {
        $this->assign('title', '分销详情');
        $salerID = I('get.saler', '0', 'string');
        $searchCondition = array();
        $searchCondition['twfx_trace.node_id'] = $this->node_id;
        
        $wfxSalerModel = M('TwfxSaler');
        if ($salerID == '0') {
            $searchCondition['twfx_trace.saler_id'] = $_SESSION['twfxSalerID'];
        } else {
            if ($salerID == $_SESSION['twfxSalerID']) {
                $searchCondition['twfx_trace.saler_id'] = $salerID;
            } else {
                $upLevelSalerId = $wfxSalerModel->where(
                    array(
                        'id' => $salerID))->getfield('parent_id');
                if ($upLevelSalerId == $_SESSION['twfxSalerID']) {
                    $searchCondition['twfx_trace.saler_id'] = $salerID;
                } else {
                    header(
                        "location:index.php?g=Label&m=Member&a=index&id=" .
                             $this->id . "&node_id=" . $_SESSION['node_id']);
                    exit();
                }
            }
        }
        
        $count = $wfxSalerModel->join(
            'RIGHT JOIN twfx_trace ON twfx_saler.id = twfx_trace.saler_id')
            ->where($searchCondition)
            ->count();
        
        import('ORG.Util.Page');
        $Page = new Page($count, $this->pageSize);
        if ($_GET['p'] > ceil($count / $this->pageSize) && $this->isAjax()) {
            return;
        }
        
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nexUrl = U('Label/MyOrder/distributionDetail', 
            array(
                'p' => ($nowPage + 1), 
                'saler' => $salerID));
        
        $wfxSalerModel = M('TwfxSaler');
        $orderInfo = $wfxSalerModel->join(
            'RIGHT JOIN twfx_trace ON twfx_saler.id = twfx_trace.saler_id')
            ->where($searchCondition)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('twfx_trace.id DESC')
            ->field(
            'twfx_trace.order_id, twfx_trace.num, twfx_trace.price, twfx_trace.amount, twfx_trace.amount, twfx_trace.goods_name, twfx_trace.bonus_amount')
            ->select();
        
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', $wechatConfig['app_id'], $wechatConfig['app_secret']);
        $this->assign('shareData', $shareConfig);
        $this->assign('orderInfo', $orderInfo);
        $this->assign('count', count($orderInfo));
        $this->assign('nextUrl', $nexUrl);
        $this->display();
    }

    function myCode() {
        $this->assign('title', '我的推广二维码');
        $wfxSalerModel = M('TwfxSaler');
        $salerInfo = $wfxSalerModel->where(
            array(
                'node_id' => $this->node_id, 
                'phone_no' => $_SESSION['groupPhone']))
            ->field('custom_no, id')
            ->find();
        if (empty($salerInfo)) {
            redirect(
                U('Label/MyOrder/index', 
                    array(
                        'node_id' => $_SESSION['node_id'])));
        }
        
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', $wechatConfig['app_id'], $wechatConfig['app_secret']);
        $this->assign('shareData', $shareConfig);
        
        $this->assign('salerInfo', $salerInfo);
        $this->display();
    }

    function showMyCode() {
        $isDownLoad = I('downLoad');
        $wfxModel = D('Wfx');
        $marketingInfoId = $wfxModel->getTmarketingInfoId('29');
        if (! $marketingInfoId) {
            $this->error('获取默认小店活动失败');
        }
        $channelId = $wfxModel->getChannelId('9', '91');
        if (! $channelId) {
            $this->error('获取默认小店渠道失败');
        }
        $batchChannelId = get_batch_channel($marketingInfoId, $channelId, '29', 
            $_SESSION['node_id']);
        if (! $batchChannelId) {
            $this->error('获取默认小店地址失败');
        }
        
        $salerID = I('get.saler_id');
        if ($salerID == '') {
            $salerID = $wfxModel->getSalerId($this->node_id, 
                $_SESSION['groupPhone']);
        }
        
        $shopUrl = U('Label/Store/index', 
            array(
                'id' => $batchChannelId, 
                'saler_id' => $salerID), '', '', TRUE);
        
        $str = array(
            'wfx' => '长按识别二维码');
        $nodeLogo = get_node_info($this->node_id, 'head_photo');
        $imageurl = get_upload_url($nodeLogo);
        if (preg_match('/^http:\/\//', $imageurl)) {
            $imageurlArray = explode('.com/', $imageurl);
            $imageurl = './' . array_pop($imageurlArray);
        }
        import('@.Vendor.MakeCode') or die('include file fail.');
        $makecode = new MakeCode();
        $makecode->MakeCodeImg($shopUrl, false, '3', $imageurl, '', '', $str);
    }

    function mySalerIndex() {
        $this->assign('title', '销售员管理');
        if ($_SESSION['twfxRole'] != '2') {
            header(
                "location:index.php?g=Label&m=Member&a=index&id=" . $this->id .
                     "&node_id=" . $_SESSION['node_id']);
            exit();
        }
        $searchCondition = array();
        $searchCondition['a.parent_id'] = $_SESSION['twfxSalerID'];
        $searchCondition['a.node_id'] = $this->node_id;
        $searchCondition['a.role'] = '1';
        
        $wfxSalerModel = M("TwfxSaler");
        $totalSalerCount = $wfxSalerModel->alias("a")->where($searchCondition)->count();
        $this->assign('totalCount', $totalSalerCount);
        
        $searchCondition['a.role'] = '2';
        $count = $wfxSalerModel->alias("a")->where($searchCondition)->count();
        $this->assign('dealerCount', $count);
        
        import('ORG.Util.Page');
        $Page = new Page($count, $this->pageSize);
        if ($_GET['p'] > ceil($count / $this->pageSize) && $this->isAjax()) {
            return;
        }
        
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nexUrl = U('Label/MyOrder/mySalerIndex', 
            array(
                'p' => ($nowPage + 1)));
        
        $sql = "SELECT b.`name`,b.id,b.`status`,COUNT(a.id) as ditributionCount FROM twfx_saler a RIGHT JOIN ( SELECT `id`,`name`,`status` FROM twfx_saler c WHERE (c.parent_id = '" .
             $_SESSION['twfxSalerID'] . "') AND (c.node_id = '" . $this->node_id .
             "') AND (c.role = '2') AND (c.status = '3')) b ON a.`parent_id`=b.id AND a.`role`=1 GROUP BY b.id limit " .
             $Page->firstRow . "," . $Page->listRows;
        $distributionInfo = M()->query($sql);
        
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', 
            $wechatConfig['app_id'], $wechatConfig['app_secret']);
        $this->assign('shareData', $shareConfig);
        $this->assign('distributionInfo', $distributionInfo);
        $this->assign('count', count($distributionInfo));
        $this->assign('nextUrl', $nexUrl);
        $this->display();
    }

    function mySaler() {
        $this->assign('title', '销售员管理');
        if ($_SESSION['twfxRole'] != '2') {
            header(
                "location:index.php?g=Label&m=Member&a=index&id=" . $this->id .
                     "&node_id=" . $_SESSION['node_id']);
            exit();
        }
        $searchCondition = array();
        $salerID = I('get.salerID', '0', 'string');
        
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        if ($salerID != '0') {
            $searchCondition['a.parent_id'] = $salerID;
            $nexUrl = U('Label/MyOrder/mySaler', 
                array(
                    'p' => ($nowPage + 1), 
                    'salerID' => $salerID));
        } else {
            $this->assign('noAddSaler', 'Y');
            $searchCondition['a.parent_id'] = $_SESSION['twfxSalerID'];
            $nexUrl = U('Label/MyOrder/mySaler', 
                array(
                    'p' => ($nowPage + 1)));
        }
        $searchCondition['a.node_id'] = $this->node_id;
        $searchCondition['a.role'] = '1';
        $searchCondition['a.status'] = array(
            'neq', 
            '5');
        $wfxSalerModel = M("twfx_saler");
        $count = $wfxSalerModel->alias("a")->where($searchCondition)->count();
        
        import('ORG.Util.Page');
        $Page = new Page($count, $this->pageSize);
        if ($_GET['p'] > ceil($count / $this->pageSize) && $this->isAjax()) {
            return;
        }
        
        $salerInfo = $wfxSalerModel->alias("a")->where($searchCondition)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->field('a.name, a.id, a.status, a.phone_no')
            ->select();
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', 
            $wechatConfig['app_id'], $wechatConfig['app_secret']);
        $this->assign('shareData', $shareConfig);
        
        $this->assign('salerInfo', $salerInfo);
        $this->assign('count', $count);
        $this->assign('nextUrl', $nexUrl);
        
        $this->display();
    }

    function addSaler() {
        $this->assign('title', '新增销售员');
        if ($_SESSION['twfxRole'] != '2') {
            header(
                "location:index.php?g=Label&m=Member&a=index&id=" . $this->id .
                     "&node_id=" . $_SESSION['node_id']);
            exit();
        }
        if (IS_POST) {
            $result = array();
            $salerName = I('post.salerName', '0', 'string');
            $salerPhone = I('post.salerPhone', '0', 'string');
            if ($salerName == '0' || $salerPhone == '0' || ! check_str(
                $salerPhone, array(
                    'strtype' => 'mobile'))) {
                $result['error'] = '20001';
                $result['msg'] = '请输入销售员姓名及手机号码。';
                $this->ajaxReturn($result);
                exit();
            }
            $wfxInfoArray = $this->_getTwxType();
            $data = array();
            $data['node_id'] = $this->node_id;
            $data['phone_no'] = $salerPhone;
            $data['name'] = $salerName;
            $data['parent_id'] = $wfxInfoArray['id'];
            $data['status'] = '1';
            $data['add_saler_id'] = $wfxInfoArray['id'];
            $data['add_user_name'] = $wfxInfoArray['name'];
            $data['add_time'] = date("YmdHis");
            $data['role'] = '1';
            $data['level'] = $wfxInfoArray['level'];
            $data['parent_path'] = $wfxInfoArray['parent_path'] .
                 $wfxInfoArray['id'] . ',';
            $wfxSalerModel = M('TwfxSaler');
            $wfxSalerID = $wfxSalerModel->add($data);
            if ($wfxSalerID) {
                $result['error'] = '0';
                $result['msg'] = '新增的销售员通过审核后生效，请耐心等待。';
            } else {
                $result['error'] = '10001';
                $result['msg'] = '您输入的手机号码已存在。';
            }
            $this->ajaxReturn($result);
        } else {
            $wechatService = D('WeiXin', 'Service');
            $wechatConfig = array();
            $wechatConfig['app_id'] = C('WEIXIN.appid');
            $wechatConfig['app_secret'] = C('WEIXIN.secret');
            $shareConfig = $wechatService->getWxShareConfig('', 
                $wechatConfig['app_id'], $wechatConfig['app_secret']);
            $this->assign('shareData', $shareConfig);
            $this->display();
        }
    }

    function soldPercentage() {
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        // 初始化分页参数，js控件无法改变 除 变量名为 p 的值
        if ($nowPage == 1) {
            cookie('wfxStartPage', 0, 24 * 3600);
        } elseif ($nowPage == 2) {
            $_COOKIE['wfxStartPage'] = $_COOKIE['wfxFirstStartPage'];
        }
        $nextPageStart = $_COOKIE['wfxStartPage'];
        
        $no = I('get.no');
        $this->assign('no', $no);
        if($no == 'no')
            $this->pageSize = 20;

        $wfxModel = D('Wfx');
        $goodsCount = $wfxModel->getSellingGoodsCount();
        if ($nextPageStart >= $goodsCount && $this->isAjax()) {
            return;
        }
        $goods = $wfxModel->getSellingGoods($goodsCount, $this->pageSize, 
            $nextPageStart);
        $WfxModel = D('Wfx', 'Service');
        foreach ($goods as $key => $val) {
            $soldPercentage = $WfxModel->get_bonus_config($_SESSION['node_id'], 
                $_SESSION['twfxSalerID'], $val['m_id']);
            if (($soldPercentage['saler_percent'] == 0 ||
                 $soldPercentage['saler_percent'] == '') && ($soldPercentage['manage_percent'] ==
                 0 || $soldPercentage['manage_percent'] == '')) {
                unset($goods[$key]);
            } else {
                $goods[$key]['saler_percent'] = $soldPercentage['saler_percent'];
                $goods[$key]['manage_percent'] = $soldPercentage['manage_percent'];
            }
        }

        $pageStart = $nextPageStart + $this->pageSize;
        $couldUseGoodsCount = count($goods);
        if ($couldUseGoodsCount < $this->pageSize) {
            $needGoodsCount = $this->pageSize - $couldUseGoodsCount;
            $pageStart = $nextPageStart + $this->pageSize;
            if ($pageStart < $goodsCount) {
                $goods = $this->getMoreSellingGoods($goodsCount, 
                    $needGoodsCount, $pageStart, $goods);
                $pageStart = array_pop($goods);
            }
        }
        if ($nowPage == 1) {
            cookie('wfxFirstStartPage', $pageStart, 24 * 3600);
        }
        cookie('wfxStartPage', $pageStart, 24 * 3600);
        $nowPage = $nowPage + 1;
        if ($no == 'no') {
            $nexUrl = U('Label/MyOrder/soldPercentage', 
                array(
                    'p' => $nowPage, 
                    'node_id' => $_SESSION['node_id'], 
                    'no' => 'no'));
        } else {
            $nexUrl = U('Label/MyOrder/soldPercentage', 
                array(
                    'p' => $nowPage, 
                    'node_id' => $_SESSION['node_id']));
        }
        
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', 
            $wechatConfig['app_id'], $wechatConfig['app_secret']);
        $this->assign('shareData', $shareConfig);
        
        $this->assign('nextUrl', $nexUrl);
        $this->assign('goods', $goods);
        $this->display();
    }

    function getMoreSellingGoods($goodsCount, $pagesize, $pageStart, 
        $data = array()) {
        $wfxModel = D('Wfx');
        $goods = $wfxModel->getSellingGoods($goodsCount, $pagesize, $pageStart);
        $WfxService = D('Wfx', 'Service');
        foreach ($goods as $key => $val) {
            $soldPercentage = $WfxService->get_bonus_config(
                $_SESSION['node_id'], $_SESSION['twfxSalerID'], $val['m_id']);
            if (($soldPercentage['saler_percent'] == 0 ||
                 $soldPercentage['saler_percent'] == '') && ($soldPercentage['manage_percent'] ==
                 0 || $soldPercentage['manage_percent'] == '')) {
                unset($goods[$key]);
            } else {
                $goods[$key]['saler_percent'] = $soldPercentage['saler_percent'];
                $goods[$key]['manage_percent'] = $soldPercentage['manage_percent'];
            }
        }
        
        $goods = array_merge($data, $goods);
        if (count($goods) == $this->pageSize) {
            $goods['count'] = $pageStart + $pagesize;
            return $goods;
        } else {
            $twiceNeedGoodsCount = $this->pageSize - count($goods);
            $twicePageStart = $pageStart + $pagesize;
            if ($twicePageStart < $goodsCount) {
                $twiceGoods = $this->getMoreSellingGoods($goodsCount, 
                    $twiceNeedGoodsCount, $twicePageStart, $goods);
                $twiceGoods['count'] = $twicePageStart;
                return $twiceGoods;
            } else {
                $goods['count'] = $pageStart + $pagesize;
                return $goods;
            }
        }
    }

    function myGoodSpreadCode() {
        $goodsID = I('get.id');
        $type = I('get.type');
        $nodeInfo = get_node_info($_SESSION['node_id']);
        $wfxModel = D('Wfx');
        if ($type == 'index') {
            $goodsInfo['name'] = $wfxModel->getShopName($_SESSION['node_id']);
            $goodsInfo['goods_img'] = get_upload_url(
                $wfxModel->getShopLogo($_SESSION['node_id']));
            $goodsInfo['shareDesc'] = $wfxModel->getShopShareDesc(
                $_SESSION['node_id']);
            
            $marketingInfoId = $wfxModel->getTmarketingInfoId('29');
            if (! $marketingInfoId) {
                $this->error('获取默认小店活动失败');
            }
            $channelId = $wfxModel->getChannelId('9', '91');
            if (! $channelId) {
                $this->error('获取默认小店渠道失败');
            }
            $batchChannelId = get_batch_channel($marketingInfoId, $channelId, 
                '29', $_SESSION['node_id']);
            if (! $batchChannelId) {
                $this->error('获取默认小店地址失败');
            }
            
            $url = U('Label/Store/index', 
                array(
                    'id' => $batchChannelId, 
                    'saler_id' => $_SESSION['twfxSalerID']), '', '', TRUE);
            $this->assign('type', 'no');
            $this->assign('shareDesc', $goodsInfo['shareDesc']);
            $this->assign('shareName', $goodsInfo['name']);
        } else {
            $goodsInfo = $wfxModel->getOneSellingGoodInfo($goodsID, $type);
            $goodsInfo['goods_img'] = get_upload_url($goodsInfo['goods_img']);
            
            $WfxService = D('Wfx', 'Service');
            $soldPercentage = $WfxService->get_bonus_config(
                $_SESSION['node_id'], $_SESSION['twfxSalerID'], $goodsID);
            $goodsInfo['saler_percent'] = $soldPercentage['saler_percent'];
            
            $channelId = $wfxModel->getChannelId('9', '91');
            $batchChannelId = get_batch_channel($goodsID, $channelId, $type, 
                $_SESSION['node_id']);
            $url = U('Label/Label/index', 
                array(
                    'id' => $batchChannelId, 
                    'node_id' => $_SESSION['node_id'], 
                    'saler_id' => $_SESSION['twfxSalerID']), '', '', TRUE);
            
            $this->assign('shareDesc', $goodsInfo['name']);
            $this->assign('shareName', $nodeInfo['node_short_name']);
        }
        if ($goodsInfo['group_price'] == '0.00') {
            $skuService = D('Sku', 'Service');
            $goodsInfo = $skuService->makeGoodsListInfo($goodsInfo, 
                $_SESSION['node_id']);
            if ($goodsInfo['market_price'] != '') {
                $goodsInfo['group_price'] = $goodsInfo['market_price'];
            }
        }
        
        $goodsInfo['url'] = create_sina_short_url($url);
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', 
            $wechatConfig['app_id'], $wechatConfig['app_secret']);
        
        $this->assign('shareData', $shareConfig);
        $this->assign('friendsShareName', $goodsInfo['name']);
        $this->assign('shareImgUrl', $goodsInfo['goods_img']);
        $this->assign('shareUrl', $goodsInfo['url']);
        $this->assign('url', $goodsInfo['url']);
        $this->assign('goodsInfo', $goodsInfo);
        $this->display();
    }

    function _getTwxType() {
        $wfxSalerModel = M('TwfxSaler');
        $salerInfo = $wfxSalerModel->join(
            'twfx_node_info ON twfx_node_info.node_id = twfx_saler.node_id')
            ->where(
            array(
                'twfx_saler.node_id' => $this->node_id, 
                'twfx_saler.phone_no' => $_SESSION['groupPhone'], 
                'twfx_saler.status' => '3'))
            ->field(
            'twfx_saler.node_id, twfx_saler.status, twfx_saler.role, twfx_saler.level, twfx_saler.parent_path, twfx_saler.name, twfx_saler.id, twfx_node_info.customer_bind_flag, twfx_saler.open_id')
            ->find();
        return $salerInfo;
    }

    function _getCustomerInfo($salerID, $phone) {
        $searchCondition = array();
        $searchCondition['saler_id'] = $salerID;
        $searchCondition['node_id'] = $this->node_id;
        if ($phone != '') {
            $searchCondition['phone_no'] = $phone;
        }
        $wfxCustomerRelationModel = M('TwfxCustomerRelation');
        $dealerCustomerInfo = $wfxCustomerRelationModel->where($searchCondition)
            ->order('status DESC')
            ->select();
        return $dealerCustomerInfo;
    }

    function _getDataFormat($data) {
        $month = '';
        $resultArray = array();
        foreach ($data as $value) {
            $condition = substr($value['add_time'], 0, 4) . '年' .
                 substr($value['add_time'], 4, 2) . '月';
            if ($month != $condition) {
                $resultArray[$month][$count - 1]['count'] = $count;
                $month = $condition;
                $count = 0;
            }
            $resultArray[$month][] = $value;
            ++ $count;
        }
        $resultArray[$month][$count - 1]['count'] = $count;
        array_shift($resultArray);
        return $resultArray;
    }

    /**
     * 更新物流信息
     */
    public function expressInfo() {
        $orderID = I('post.order', '0', 'string');
        // 订购订单
        $bookOrderId = I('post.bookOrder', '0', 'string');
        
        $expressInfoCondition = array();
        $expressInfoCondition['node_id'] = $_SESSION['cc_node_id'];
        $expressInfoCondition['order_id'] = $orderID;
        if ($bookOrderId != '0') {
            $bookOrderArray = explode('-', $bookOrderId);
            $expressInfoCondition['book_order_id'] = $bookOrderArray['1'];
        }
        $orderExpressInfoModel = M('torder_express_info');
        $orderExpressInfo = $orderExpressInfoModel->where($expressInfoCondition)->find();
        if (empty($orderExpressInfo)) {
            $result = $this->_refreshExpressInfo($orderID, 0, 
                $bookOrderArray['1']);
        } else {
            $systemParamModel = M('TsystemParam');
            $intervalTime = $systemParamModel->where(
                array(
                    'param_name' => 'EXPRESS_QUERY_TIME'))->getfield(
                'param_value');
            $date = dateformat($orderExpressInfo['check_time'], 'Y-m-d H:i:s');
            $dateTime = strtotime($date) + $intervalTime * 60;
            if ($dateTime < time() && $orderExpressInfo['status'] == '0') {
                $result = $this->_refreshExpressInfo($orderID, 1, 
                    $bookOrderArray['1']);
            } else {
                $result = array(
                    'error' => '0');
            }
        }
        $this->ajaxReturn($result);
    }

    function _refreshExpressInfo($orderID, $exist, $bookOrderId) {
        // 按照订单类型查找物流信息
        if ($bookOrderId == '0') {
            $expressInfo = M('ttg_order_info')->where(
                array(
                    'order_id' => $orderID))
                ->field('delivery_company, delivery_number')
                ->find();
        } else {
            $expressInfo = M('ttg_order_by_cycle')->where(
                array(
                    'id' => $bookOrderId, 
                    'order_id' => $orderID))
                ->field('delivery_company, delivery_number')
                ->find();
        }
        
        if ($expressInfo['delivery_number'] != '') {
            $expressName = mb_substr($expressInfo['delivery_company'], 0, 2, 
                'utf-8'); // 此处为了兼容之前的数据
            $expressInfoModel = M('texpress_info');
            $expressNameCode = $expressInfoModel->where(
                array(
                    'express_name' => array(
                        'like', 
                        '%' . $expressName . '%')))->getfield('query_str');
            $expressServiceModel = D('Express', 'Service');
            $result = $expressServiceModel->index($orderID, 
                $_SESSION['node_id'], $exist, $expressNameCode, 
                $expressInfo['delivery_number'], '0', $bookOrderId);
            return $result;
        } else {
            return $result = array(
                'error' => '0');
        }
    }

    /**
     * @return TmemberInfoModel
     */
    public function getMemberInfo(){
        return D('TmemberInfo');
    }

    public function newsMsg(){
        $getMemberModel = $this->getMemberInfo();
        $memberSess = session('store_mem_id'.$this->node_id);
        $memberInfo = $getMemberModel->memberInfo($memberSess['user_id']);
        $this->assign('title','系统消息');
        if($_SESSION['twfxSalerID'] == ''){
            $_SESSION['twfxSalerID'] == '0';
        }
        //会员卡时间
        $memberCardTime = $memberInfo['card_update_time'];
        if(empty($memberCardTime)){
            $memberCardTime = $memberInfo['add_time'];
        }
        $wfxService = D('Wfx', 'Service');
        // 获取商户发送的 未读 消息 和 会员自身触发的 未读 的消息 数量
        $unReadWfxMsgCount = $wfxService->getUnreadWfxMsg($memberInfo, $memberCardTime);

        $this->assign('unReadWfxMsgCount', $unReadWfxMsgCount['unreadCount']);
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $nexUrl  = U('Label/MyOrder/newsMsg', array('p' => ($nowPage + 1)));
        $this->assign('nextUrl', $nexUrl);

        //a 为群发消息  |  b 为模板消息
        $sql = 'SELECT count(*) cou FROM (';
        $sql .= " SELECT m.id, m.title, m.content, m.add_time add_time, l.saler_id FROM twfx_msg m LEFT JOIN twfx_msg_read_list l ON m.`id` = l.msg_id AND l.saler_id = '{$_SESSION['twfxSalerID']}' WHERE m.`node_id` = '{$_SESSION['node_id']}' AND m.`status` = '2'";
        if($_SESSION['twfxRole'] == '1'){
            $sql .= " AND (m.`reader` IN ('1', '3') OR (m.`reader` = '4' AND reader_list LIKE '%".$_SESSION['twfxSalerID'].",%'))";
        }elseif($_SESSION['twfxRole'] == '2'){
            $sql .= " AND (m.`reader` IN ('1', '2') OR (m.`reader` = '4' AND reader_list LIKE '%".$_SESSION['twfxSalerID'].",%'))) c";
        }else{
            $sql .= " AND (m.`reader` = 1  OR (m.`reader` = '4' AND reader_list LIKE '%".$_SESSION['twfxSalerID'].",%'))";
        }
        $sql .= ' UNION ALL ';
        $sql .= "SELECT a.id, a.title, a.content, a.add_time, 'a' FROM tmember_msg a LEFT JOIN tmember_msg_list b ON a.id = b.msg_id AND b.member_id = '{$memberInfo['id']}' WHERE a.node_id = '{$_SESSION['node_id']}' AND a.reader = 2 AND FIND_IN_SET({$memberInfo['card_id']},a.reader_list) AND  a.add_time>='{$memberCardTime}' AND a.`status` = 2 AND ISNULL(b.id) ";
        $sql .= ' UNION ALL ';
        $sql .= " SELECT id, title, content, add_time, 'b' FROM tmember_msg_list WHERE member_id = '{$memberInfo['id']}')c ";

        //获取总记录数
        $count = M()->query($sql);
        import('ORG.Util.Page');
        $Page = new Page($count[0]['cou'], $this->pageSize);
        if ($_GET['p'] > ceil($count[0]['cou'] / $this->pageSize) && $this->isAjax()){
            return;
        }

        //a 为群发消息  |  b 为模板消息
        $sql = 'SELECT*FROM (';
        $sql .= " SELECT m.id, m.title, m.content, m.add_time add_time, l.saler_id,IFNULL(l.id,1) as isRead FROM twfx_msg m LEFT JOIN twfx_msg_read_list l ON m.`id` = l.msg_id AND l.saler_id = '{$_SESSION['twfxSalerID']}' WHERE m.`node_id` = '{$_SESSION['node_id']}' AND m.`status` = '2'";
        if($_SESSION['twfxRole'] == '1'){
            $sql .= " AND (m.`reader` IN ('1', '3') OR (m.`reader` = '4' AND reader_list LIKE '%".$_SESSION['twfxSalerID'].",%'))";
        }elseif($_SESSION['twfxRole'] == '2'){
            $sql .= " AND (m.`reader` IN ('1', '2') OR (m.`reader` = '4' AND reader_list LIKE '%".$_SESSION['twfxSalerID'].",%'))";
        }else{
            $sql .= " AND (m.`reader` = 1  OR (m.`reader` = '4' AND reader_list LIKE '%".$_SESSION['twfxSalerID'].",%'))";
        }
        $sql .= ' UNION ALL ';
        $sql .= "SELECT a.id, a.title, a.content, a.add_time add_time, 'a',IFNULL(b.id,1) as isRead FROM tmember_msg a LEFT JOIN tmember_msg_list b ON a.id = b.msg_id AND b.member_id = '{$memberInfo['id']}' ";
        $sql .= " WHERE a.node_id = '{$_SESSION['node_id']}' AND a.reader = 2 AND FIND_IN_SET({$memberInfo['card_id']},a.reader_list) AND  a.add_time>='{$memberCardTime}' AND a.status = 2 AND ISNULL(b.id)";
        $sql .= ' UNION ALL ';
        $sql .= " SELECT id, title, content, add_time, 'b',msg_status as isRead FROM tmember_msg_list WHERE  member_id = '{$memberInfo['id']}' ";
        $sql .= " ) c ORDER BY c.add_time DESC LIMIT ".$Page->firstRow . ',' . $Page->listRows;
        $msgArray = M()->query($sql);

        $this->assign('node_id',session('cc_node_id'));
        $this->assign('newsMsg', $msgArray);
        $this->display();
    }

    public function newsMsgDetails(){
        $this->assign('title', '系统消息');
        if($_SESSION['twfxSalerID'] == ''){
            $_SESSION['twfxSalerID'] = '0';
        }
        $postData = I('get.');
        $id = $postData['id'];
        // 用于区分所要查找的表 a 为tmember_msg  |  b 为tmember_msg_list  默认为旺分销 twfx_msg
        $salerId = $postData['saler_id'];
        //获取会员信息
        $memberSess = session('store_mem_id'.$this->node_id);
        if($salerId == 'a') {
            $newsMsgArray = $this->getNodeSendMessage($id, $memberSess['user_id']);

        }elseif($salerId == 'b'){

            $newsMsgArray = $this->getMemberMessage($id, $memberSess['user_id']);

        }else{
            $newsMsgArray = $this->getWfxMessage($id);

        }
        $this->assign('newsMsg', $newsMsgArray);
        $this->display();
        }
    //获取商户群发消息
    public function getNodeSendMessage($id, $memberId){
        $newsMsgArray = M('tmember_msg')->field('id,title,add_time,content')->where(array('id'=>$id))->find();
        $memberMsgListModel = M('tmember_msg_list');

        $inTable = $memberMsgListModel->where(array('msg_id'=>$newsMsgArray['id']))->find();
        $hasData = array_filter($inTable);
        if(empty($hasData)){
            $addData = array('msg_id'     => $newsMsgArray['id'],
                             'member_id'  => $memberId,
                             'msg_status' => 2,
                             'add_time'   => date('YmdHis'),
                             'read_time'  => date('YmdHis'),
                             'content'    => $newsMsgArray['content'],
                             'title'      => $newsMsgArray['title']
            );
            $isOk = $memberMsgListModel->add($addData);
            //记录阅读人数
            M('tmember_msg')->where(array('id'=>$id))->setInc('read_count');
            if(!$isOk){
                log_write("消息更新到tmember_msg_list表失败".print_r($addData,true));
            }
        }

        return $newsMsgArray;
    }
    //获取会员自身触发的消息
    public function getMemberMessage($id, $memberId){
        $memberMsgListModel = M('tmember_msg_list');
        $newsMsgArray = $memberMsgListModel->field('title,add_time,content,msg_status')->where(array('id'=>$id,'msg_id'=>0,'member_id'=>$memberId))->find();

        $newsMsgArray['m_id'] = '';
        if($newsMsgArray['msg_status'] == 1){
            $saveData = array('msg_status'=>2,'read_time'=>date('YmdHis'));
            $isOk = $memberMsgListModel->where(array('id'=>$id))->save($saveData);
            if(!$isOk){
                log_write("修改tmember_msg_list表数据失败".print_r($saveData,true));
            }
        }

        return $newsMsgArray;
    }
    //获取旺分销消息
    public function getWfxMessage($id){
        $channelModel        = M('tchannel');
        $channelId           = $channelModel->where(
                array('type' => '1', 'sns_type' => '102', 'node_id' => $_SESSION['node_id'])
        )->getField('id');
        $wfxMsgModel         = M("twfx_msg");
        $newsMsgArray        = $wfxMsgModel->alias("m")->join('tmarketing_info mk on mk.`id` = m.m_id')->join(
                "tbatch_channel bc on bc.batch_type = mk.batch_type AND bc.batch_id = m.m_id AND bc.channel_id = {$channelId} AND bc.node_id = {$_SESSION['node_id']}"
        )->field(
                'mk.name as wap_title, m.title, m.content, bc.id as m_id, m.add_time, m.node_id'
        )->where(array('m.id' => $id))->find();
        $wfxMsgReadListModel = M('twfx_msg_read_list');
        $readMsgId           = $wfxMsgReadListModel->where(
                array('msg_id' => $id, 'saler_id' => $_SESSION['twfxSalerID'])
        )->getField('id');
        $hasData = array_filter($readMsgId);
        if (empty($hasData)) {
            $salerNodeId = M('twfx_saler')->where(array('id' => $_SESSION['twfxSalerID']))->getfield('node_id');
            if ($salerNodeId == $_SESSION['node_id']) {
                $wfxMsgReadListModel->add(
                        array(
                                'msg_id'    => $id,
                                'saler_id'  => $_SESSION['twfxSalerID'],
                                'read_time' => date('YmdHis')
                        )
                );
                $wfxMsgModel->alias("m")->where(array('m.id' => $id))->setInc('m.read_count');
            }
        }
        return $newsMsgArray;
    }

    function activityShare() {
        if (! session('?groupPhone')) {
            $this->showMsg('您还没有登录！');
        }
        $this->assign('title', '招募销售员');
        $twfxSalerId = M('twfx_saler')->where(
            array(
                'node_id' => $_SESSION['node_id'], 
                'phone_no' => $_SESSION['groupPhone'], 
                'role' => '2'))->getfield('id');
        if ($twfxSalerId) {
            $shareUrl = U('Label/Wfx/activity', 
                array(
                    'saler' => $twfxSalerId), '', '', TRUE);
        } else {
            $this->error('请先成为此商户下的经销商');
            exit();
        }
        
        $nodeInfo = get_node_info($_SESSION['node_id']);
        $marketingInfoModel = M('tmarketing_info');
        $recruitInfo = $marketingInfoModel->where(
            array(
                'node_id' => $_SESSION['node_id'], 
                'batch_type' => '3001'))
            ->field(
            'name, bg_pic, node_name, log_img, config_data, wap_info, id, status')
            ->find();
        if ($recruitInfo['status'] == '2') {
            redirect(
                U('Label/MyOrder/my', 
                    array(
                        'node_id' => $nodeId)));
            exit();
        }
        
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', 
            $wechatConfig['app_id'], $wechatConfig['app_secret']);
        $this->assign('shareData', $shareConfig);
        $wxName = M('twx_user')->where(
            array(
                'openid' => $_SESSION['wxUserInfo']['openid']))->getfield(
            'nickname');
        $shopName = htmlspecialchars_decode($_SESSION['login_title']);
        $shareName = $wxName . '邀请你注册成为' . $shopName . '销售员！';
        $shareImgUrl = get_upload_url($nodeInfo['head_photo']);
        $shareDesc = '注册成为' . $shopName . '销售员，分享专属分销二维码给好友，好友购物后就能得提成。快来注册推广吧！';
        $this->assign('shareName', $shareName);
        $this->assign('shareDesc', $shareDesc);
        $this->assign('shareUrl', $shareUrl);
        $this->assign('shareImgUrl', $shareImgUrl);
        
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) {
            $this->assign('wechat', 'wechat');
        }
        
        if ($recruitInfo['log_img'] == '') {
            $recruitInfo['log_img'] = $shareImgUrl;
        } else {
            $recruitInfo['log_img'] = get_upload_url($recruitInfo['log_img']);
        }
        
        $this->assign('recruitInfo', $recruitInfo);
        $this->assign('info', explode(',', $recruitInfo['config_data']));
        $this->display();
    }

    function nextSaler() {
        $this->assign('title', '销售员管理');
        if ($_SESSION['twfxRole'] != '2') {
            header(
                "location:index.php?g=Label&m=Member&a=index&id=" . $this->id .
                     "&node_id=" . $_SESSION['node_id']);
            exit();
        }
        
        $searchCondition = array();
        $nowPage = empty($_GET['p']) ? 1 : $_GET['p'];
        $searchCondition['a.parent_id'] = $_SESSION['twfxSalerID'];
        $nexUrl = U('Label/MyOrder/nextSaler', 
            array(
                'p' => ($nowPage + 1)));
        
        $searchCondition['a.node_id'] = $this->node_id;
        $searchCondition['a.role'] = '2';
        $searchCondition['a.status'] = array(
            'neq', 
            '5');
        $wfxSalerModel = M("twfx_saler");
        $count = $wfxSalerModel->alias("a")->where($searchCondition)->count();
        import('ORG.Util.Page');
        $Page = new Page($count, $this->pageSize);
        if ($_GET['p'] > ceil($count / $this->pageSize) && $this->isAjax()) {
            return;
        }
        
        $salerInfo = $wfxSalerModel->alias("a")->where($searchCondition)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->field('a.name, a.id, a.status, a.phone_no')
            ->select();
        $wechatService = D('WeiXin', 'Service');
        $wechatConfig = array();
        $wechatConfig['app_id'] = C('WEIXIN.appid');
        $wechatConfig['app_secret'] = C('WEIXIN.secret');
        $shareConfig = $wechatService->getWxShareConfig('', 
            $wechatConfig['app_id'], $wechatConfig['app_secret']);
        $this->assign('shareData', $shareConfig);
        
        $this->assign('salerInfo', $salerInfo);
        $this->assign('count', count($salerInfo));
        $this->assign('nextUrl', $nexUrl);
        $this->display();
    }

    /**
     * @param $goodsList
     *
     * @return mixed
     */
    private function generateDaysAndDiscounts($goodsList)
    {
        foreach ($goodsList as $k => $list) {
            $end_time                   = $list['end_time'];
            $today                      = strtotime(date('Y-m-d H:i:s'));
            $end                        = strtotime($end_time);
            $discount                   = $list['goods_discount'];
            $goodsList[$k]['days']      = ceil(($end - $today) / 86400);
            $goodsList[$k]['discounts'] = ceil(10 - $discount / 10);
        }
        return $goodsList;
    }

    /**
     * @param $goodsList
     *
     * @return mixed
     */
    private function generateDiscounts($goodsList)
    {
        foreach ($goodsList as $k => $list) {
            $discount                   = $list['goods_discount'];
            $goodsList[$k]['discounts'] = ceil(10 - $discount / 10);
        }
        return $goodsList;
    }

}
