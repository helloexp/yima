<?php

class MemberAction extends Action {
    // 微信用户id
    public $openid = '';

    public $expiresTime = 120;

    public $CodeexpiresTime = 600;

    public $member_id = '';

    public $node_id = '';

    public $INTEGRAL_BATCH_TYPE = CommonConst::BATCH_TYPE_INTEGRAL;
    // 积分商城
    public $memberM = null;

    private $pageSize = 10;

    //非标和包
    public $isCmPay = 0;

    /**
     * @var MemberInstallModel
     */
    private  $MemberInstallModel;

    public function _initialize() {

        $this->MemberInstallModel = D("MemberInstall", "Model");

        $this->_integralName();
        $ol_node_id = I('get.node_id');
        $label_id = I('get.id');
        if ($ol_node_id == null) {
            $ol_node_id = I('post.node_id');
        }
        
        if (! $ol_node_id) {
            $ol_node_id = M('tbatch_channel')->where(
                array(
                    'id' => $label_id))->getField('node_id');
        }

        $this->node_id = session('cc_node_id');
        if (! in_array(ACTION_NAME, 
            array(
                'checkPhoneLogin', 
                'savePersonal', 
                'checkinCalenderData'))) {
            if (! session('?groupPhone') || session('cc_node_id') == null) {
                $this->_clearMemberSess();
                $surl = urlencode(
                    '/index.php?g=Label&m=Member&a=index&node_id=' . $ol_node_id);
                $url = U('O2OLogin/index') . "&id=" . $label_id . "&node_id=" .
                     $ol_node_id . "&backcall=bclick&surl=" . $surl;
                redirect($url);
            }
            if ($ol_node_id != session('cc_node_id')) {
                $this->_clearMemberSess();
                $surl = urlencode(
                    '/index.php?g=Label&m=Member&a=index&node_id=' . $ol_node_id);
                $url = U('O2OLogin/index') . "&id=" . $label_id . "&node_id=" .
                     $ol_node_id . "&backcall=bclick&surl=" . $surl;
                redirect($url);
            }
            $re = M('tmember_info')->where(
                array(
                    'phone_no' => session('groupPhone'), 
                    'node_id' => $ol_node_id))->select();
            if (! $re) {
                $this->_clearMemberSess();
                $surl = urlencode(
                    '/index.php?g=Label&m=Member&a=index&node_id=' . $ol_node_id);
                $url = U('O2OLogin/index') . "&id=" . $label_id . "&node_id=" .
                     $ol_node_id . "&backcall=bclick&surl=" . $surl;
                redirect($url);
            }
        }
        
        //和包支付
        if(C('CMPAY.storeId') == $this->id){
            session('cmPayId', $this->node_id);
        }

        $user_info = session('store_mem_id' . $this->node_id);
        $this->member_id = $user_info['user_id'];
        $this->assign('node_id', $this->node_id);
        $this->memberM = D('MemberInstall', 'Model');
        // 爱蒂宝*****请保存在初始化的最后一行
        $this->adbInit();
    }

    /**
     * 首页
     */
    public function index() {
        $id = I('id');
        $this->assign('id',$id);
        $user_info = session('store_mem_id' . $this->node_id);
        $this->member_id = $user_info['user_id'];
        $node_id = $this->node_id;
        $label_id = M('tbatch_channel')->where(
            array(
                'node_id' => $node_id, 
                'batch_type' => '2001'))->getField('id');
        $wxopen = session('wxUserInfo');
        $this->openid = $wxopen['openid'];
        $member_id = $this->member_id;
        $channel_id = M('tchannel')->where(
            array(
                'node_id' => $node_id, 
                'type' => '4', 
                'sns_type' => '46'))->getField('id');
        // 小店的m_id
        $m_info = M('tmarketing_info')->where(
            array(
                'node_id' => $node_id, 
                'batch_type' => CommonConst::BATCH_TYPE_STORE))->find();
        $xd_label_id = get_batch_channel($m_info['id'], $channel_id, 
            CommonConst::BATCH_TYPE_STORE, $node_id);
        $cataObj = D('Integral');
        $flag_re = M('tmember_center_config')->where("node_id = '$node_id'")->find();
        if (! $flag_re) {
            $flag_re = array(
                'user_data_flag' => '1', 
                'user_data_name' => '个人资料', 
                'my_dzq_flag' => '1', 
                'my_dzq_name' => '我的卡券', 
                'msg_box_flag' => '1', 
                'msg_box_name' => '消息盒子', 
                'nearby_store_flag' => '1', 
                'nearby_store_name' => '附近门店', 
                'tel_flag' => '1', 
                'tel_name' => '客服热线', 
                'tel' => '400-400-123');
            $module = M('tnode_info')->where(
                array(
                    'node_id' => $node_id))->getField('pay_module');
            if (strpos($module, "m2")) {
                $flag_re['phone_shop_flag'] = '1';
                $flag_re['phone_shop_name'] = '手机商城';
                $flag_re['my_order_flag'] = '1';
                $flag_re['my_order_name'] = '我的订单';
                $flag_re['my_red_flag'] = '1';
                $flag_re['my_red_name'] = '我的红包';
                $flag_re['my_addr_flag'] = '1';
                $flag_re['my_addr_name'] = '我的收货地址';
                $flag_re['shop_cart_flag'] = '1';
                $flag_re['shop_cart_name'] = '购物车';
            }
        }elseif($flag_re['tel'] == ''){
            $flag_re['tel'] =  M("tnode_info")
                    ->where(array('node_id' => $this->node_id))->getField("node_service_hotline");
        }
        // 获取会员详细信息
        $wx_info = $cataObj->user_info($member_id);
        if ($wx_info['name'] == null) {
            $wx_info['name'] = $wx_info['nickname'];
        }
        // 会员卡信息
        $member_data = array(
            'a.phone_no' => session('groupPhone'), 
            'a.node_id' => $node_id);
        $member_card_info = M()->table("tmember_info a")->join(
            'tmember_cards c ON a.card_id = c.id')
            ->where("a.id = '{$member_id}'")
            ->field('c.*,a.member_num')
            ->find();
        if (! $member_card_info) {
            $this->_clearMemberSess();
            $surl = urlencode(
                '/index.php?g=Label&m=Member&a=index&node_id=' . $node_id);
            $url = U('O2OLogin/index') . "&id=" . $label_id . "&node_id=" .
                 $node_id . "&backcall=bclick&surl=" . $surl;
            redirect($url);
        }
        $member_card_info['bg_style'] = get_val($member_card_info, 'bg_style', '1');
        // 分销
        $salerInfo = $this->_getTwxType();

        $_SESSION['twfxSalerID'] = '';
        $_SESSION['twfxRole'] = '';
        
        if (! empty($salerInfo)) {
            $wfxSalerModel = M('twfx_saler');
            $setSendWechatInfo = M('twx_templatemsg_config')->where(
                array(
                    'node_id' => $_SESSION['node_id']))->getField('status');
            if ($setSendWechatInfo == '1') {
                if ($salerInfo['open_id'] == '') {
                    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !==
                         false) {
                        $got = I('type');
                        if ($got != 'got') {
                            $this->_clearMemberSess();
                            $surl = U('Label/Member/index', 
                                array(
                                    'node_id' => $this->node_id), '', '', true);
                            redirect(
                                U('Label/O2OLogin/index', 
                                    array(
                                        'node_id' => $this->node_id, 
                                        'surl' => urlencode($surl), 
                                        'shopAppIdRewrite' => '1')));
                            exit();
                        }
                    }
                    $wfxSalerModel->where(
                        array(
                            'id' => $salerInfo['id']))->save(
                        array(
                            'open_id' => $_SESSION['wxUserInfo']['secondOpenid']));
                }
            }
            session('twfxSalerID', $salerInfo['id']);
            session('node_id', $salerInfo['node_id']);
            session('twfxRole', $salerInfo['role']);
            
            $wfxService = D('Wfx', 'Service');
            $newsMsgArray = $wfxService->getUnreadWfxMsg($wx_info);
            $this->assign('unReadNewsMsgCount', $newsMsgArray['unreadCount']);
            $this->assign('width', $newsMsgArray['length']);
            $this->assign('salerInfo', $salerInfo);
        }
        
        // 标签个数
        $a = 0;
        if ($flag_re['user_data_flag'] == '1') {
            $a ++;
        }
        if ($flag_re['my_privilege_flag'] == '1') {
            $a ++;
        }
        if ($flag_re['my_dzq_flag'] == '1') {
            $a ++;
        }
        if ($flag_re['msg_box_flag'] == '1') {
            $a ++;
        }
        // 订购订单
        $orderCondition = array();
        $orderCondition['order_phone'] = session('groupPhone');
        $orderCondition['node_id'] = $this->node_id;
        $orderCondition['pay_status'] = '2';
        $orderCondition['other_type'] = '1';
        $bookOrderCount = M('ttg_order_info')->where($orderCondition)->count();
        
        // 红包总金额
        $where = array(
            'b.phone' => session('groupPhone'), 
            'b.node_id' => $this->node_id, 
            'i.bonus_end_time' => array(
                'egt', 
                date('YmdHis')));
        $where['_string'] = " (b.bonus_num-b.bonus_use_num) > 0 ";
        $bonusAmount = M()->table('tbonus_use_detail b')
            ->join('tbonus_detail d on d.id=b.bonus_detail_id')
            ->join('tbonus_info i on i.id=b.bonus_id')
            ->where($where)
            ->sum('d.amount');
        if (! $bonusAmount) {
            $bonusAmount = 0;
        }
        
        // 有效凭证数
        $barcodeCount = M('tbarcode_trace')->where(
            array(
                'node_id' => $this->node_id, 
                'phone_no' => session('groupPhone'), 
                'use_status' => '0', 
                'trans_type' => '0001', 
                'status' => '0', 
                'end_time' => array(
                    'egt', 
                    date('YmdHis'))))->count();
        
        //非标和包
        if(session("?cmPayId")){
            $this->isCmPay = session("cmPayId");
        }

        $level_arr = array(
            '2' => '钻石会员',
            '3' => '金牌会员',
            '4' => '银牌会员'
            );
        $this->assign('level_arr', $level_arr);
        // 未支付订单数
        $orderCondition['order_status'] = '0';
        $orderCondition['pay_status'] = '1';
        $orderCondition['other_type'] = '0';
        $orderCount = M('ttg_order_info')->where($orderCondition)->count();
        $this->assign('orderCount', $orderCount);
        $this->assign('barcodeCount', $barcodeCount);
         //非标和包
        $this->assign('cmPayId', $this->isCmPay);
        $this->assign('bonusAmount', $bonusAmount);
        $this->assign('a', $a);
        $this->assign('bookOrderCount', $bookOrderCount);
        $this->assign('flag_re', $flag_re);
        $this->assign('expiresTime', $this->expiresTime);
        $this->assign('member_card_info', $member_card_info);
        $this->assign('xd_label_id', $xd_label_id);
        $this->assign('point', $wx_info['point']);
        $this->assign('name', $wx_info['name']);
        $this->assign('img', $wx_info['nickLogo']);
        $this->assign('mobile', $wx_info['phone_no']);
        $this->assign('label_id', $label_id);
        $this->display();
    }

    /**
     * Activity活动
     */
    public function activity() {
        // 机构下的活动 id
        $batchIdStr = M('tmember_center_config')->where(
            array(
                'node_id' => $this->node_id))->getField('popular_activity');
        $mIns = D('MemberInstall', 'Model');
        // 获取活动列表信息
        $list = $mIns->getBatchDataWap($batchIdStr);
        foreach ($list as $key => $value) {
            $list[$key]['wap_info'] = htmlspecialchars_decode(
                $value['wap_info']);
            $list[$key]['wap_info'] = strip_tags($list[$key]['wap_info']);
            if (mb_strlen($list[$key]['wap_info'], 'utf-8') > 100) {
                $list[$key]['wap_info'] = mb_substr($list[$key]['wap_info'], 0, 
                    100, 'utf-8') . '……';
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 会员特权
     */
    public function member() {
        $data = array(
            'a.node_id' => $this->node_id, 
            'a.id' => $this->member_id);
        $re = M()->table("tmember_info a")->join('tmember_cards b on a.card_id = b.id')
            ->where($data)
            ->field('b.equity')
            ->find();
        $re = htmlspecialchars_decode($re['equity']);
        $this->assign('re', $re);
        $this->display();
    }

    /**
     * 红包
     */
    public function prize() {
        $node_id = $this->node_id;
        $label_id = I('get.id');
        $mobile = session('groupPhone');
        // 机构下的红包id
        $batchIdStr = M('tmember_center_config')->where("node_id = '$node_id'")->getField(
            'receive_bonus');
        
        $mIns = D('MemberInstall', 'Model');
        $bonusArr = $mIns->getRedData($batchIdStr);
        
        foreach ($bonusArr as $key => $value) {
            $bonus_id = $value['id'];
            // 判断红包是否领取
            $getbonus_re = M('tintegral_getbonust_log')->where(
                array(
                    'phone_no' => $mobile, 
                    'node_id' => $node_id, 
                    'bonus_id' => $bonus_id))->find();
            if ($getbonus_re) {
                $bonusArr[$key]['is_bonus_get'] = '1';
            }
            
            // 判断红包是否还有库存
            $goodsStorage = M('tgoods_info')->field('storage_type,remain_num')->where(
                "bonus_id='{$bonus_id}'")->find();
            if ($goodsStorage['remain_num'] < 1 &&
                 $goodsStorage['storage_type'] == '1') {
                $bonusArr[$key]['last_num'] = '0';
            }
            // 红包领取数量
            $get_num = M('tbonus_detail')->where(
                array(
                    'bonus_id' => $bonus_id))->getField('get_num');
            $bonusArr[$key]['get_num'] = $get_num;
        }
        $this->assign('get_num', $get_num);
        $this->assign('label_id', $label_id);
        $this->assign('bonus_list', $bonusArr);
        $this->display();
    }

    /**
     * 领取红包
     */
    public function getPrize() {
        $bonus_id = I('post.bonus_id');
        $mobile = session('groupPhone');
        $node_id = $this->node_id;
        // 获取活动id
        $batch_id = M()->table("tgoods_info a")->join(
            'tbatch_info b ON a.goods_id = b.goods_id')->where(
            "a.bonus_id = '$bonus_id'")->getField('b.id');
        // 获取会员id
        
        $member_id = $this->member_id;
        // 是否领取过该红包
        $getbonus_re = M('tintegral_getbonust_log')->where(
            array(
                'member_id' => $member_id, 
                'node_id' => $node_id, 
                'bonus_id' => $bonus_id))->find();
        
        if ($getbonus_re) {
            $mess = array(
                'status' => '0', 
                'info' => '已经领取过该红包！');
            $this->ajaxReturn($mess);
        }
        // 领取红包
        $re = $this->getredModel($member_id, $batch_id);
        // 红包领取成功！入库流水表
        if ($re) {
            $getbonus_data = array(
                'bonus_id' => $bonus_id, 
                'batch_id' => $batch_id, 
                'phone_no' => $mobile, 
                'member_id' => $member_id, 
                'node_id' => $node_id, 
                'add_time' => date('YmdHis', time()));
            $add_getbonus_re = M('tintegral_getbonust_log')->add($getbonus_data);
            if (! $add_getbonus_re) {
                $mess = array(
                    'status' => '0', 
                    'info' => '入库抽奖流水表失败！');
                $this->ajaxReturn($mess);
            }
        }
        $mess = array(
            'status' => '1', 
            'info' => '领取成功！');
        $this->ajaxReturn($mess);
    }

    /**
     * 会员ID领取红包 member_id:会员id;
     */
    public function getredModel($member_id, $batch_id) {
        $node_id = $this->node_id;
        // 活动是否存在
        $batchInfo = M('tbatch_info')->where("id='{$batch_id}' AND status=0")->find();
        if (! $batchInfo) {
            $mess = array(
                'status' => '0', 
                'info' => '未找到有效的活动');
            $this->ajaxReturn($mess);
        }
        $this->m_id = $batchInfo['m_id'];
        $add_time = date('YmdHis');
        $resp_log_img = I('post.resp_log_img');
        // 校验库存
        M()->startTrans();
        $goodsStorage = M('tgoods_info')->lock(true)->field(
            'storage_type,remain_num,bonus_id')->where(
            "goods_id='{$batchInfo['goods_id']}'")->find();
        if ($goodsStorage['remain_num'] < 1 &&
             $goodsStorage['storage_type'] == '1') {
            M()->rollback();
            $mess = array(
                'status' => '0', 
                'info' => '库存不足！');
            $this->ajaxReturn($mess);
        }
        
        // 插入tbonus_use_detail
        $bonusInfo = M('tbonus_info')->where(
            array(
                'id' => $goodsStorage['bonus_id']))->find();
        if (! $bonusInfo) {
            M()->rollback();
            $mess = array(
                'status' => '0', 
                'info' => '红包数据错误！');
            $this->ajaxReturn($mess);
        }
        $bonusDetailInfo = M('tbonus_detail')->where(
            array(
                'bonus_id' => $goodsStorage['bonus_id']))->find();
        if (! $bonusDetailInfo) {
            M()->rollback();
            $mess = array(
                'status' => '0', 
                'info' => '红包明细数据错误！');
            $this->ajaxReturn($mess);
        }
        $data = array(
            'm_id' => $bonusInfo['m_id'], 
            'node_id' => $this->node_id, 
            'bonus_id' => $bonusInfo['id'], 
            'bonus_detail_id' => $bonusDetailInfo['id'], 
            'bonus_num' => 1, 
            'bonus_use_num' => 0, 
            'phone' => session('groupPhone'), 
            'member_id' => $member_id, 
            'status' => '1');
        $resp = M('tbonus_use_detail')->add($data);
        if ($resp === false) {
            M()->rollback();
            $mess = array(
                'status' => '0', 
                'info' => '红包派发明细数据错误！');
            $this->ajaxReturn($mess);
        }
        $ret = M('tbonus_detail')->where(
            array(
                'id' => $bonusDetailInfo['id']))->setInc('get_num');
        if ($ret === false) {
            M()->rollback();
            $mess = array(
                'status' => '0', 
                'info' => '红包统计数据错误！');
            $this->ajaxReturn($mess);
        }
        $ret = M('tmarketing_info')->where(
            array(
                'id' => $bonusInfo['m_id']))->setInc('send_count');
        if ($ret === false) {
            M()->rollback();
            $mess = array(
                'status' => '0', 
                'info' => '红包派发统计数据错误！');
            $this->ajaxReturn($mess);
        }
        // 更新库存
        $goodsModel = D('Goods');
        $result = $goodsModel->storagenum_reduc($batchInfo['goods_id'], 1, 
            session('groupPhone'), 12, '定额红包单个派发');
        if (! $result) {
            log::write(
                '信息:' . $goodsModel->getError() . 'goods_id:' .
                     $batchInfo['goods_id'] . 'num:1');
        }
        M()->commit();
        node_log(
            "定额单条发送，红包名称：{$batchInfo['batch_name']}，手机号：{session('groupPhone')}");
        
        return true;
    }

    /**
     * 积分详情
     */
    public function youdou() {
        // 会员信息
        $cataObj = D('Integral');
        $wx_info = $cataObj->user_info($this->member_id);
        $this->assign('wx_info', $wx_info);
        
        $y = I('get.yea');
        if ($y == null) {
            $y = date("Y");
        }
        
        // 月份
        $m = I('get.mo');
        if ($m == null || ($m > date('m') && $y == date("Y"))) {
            $m = date("m");
        }
        
        $t1 = mktime(0, 0, 0, $m, 1, $y);
        $one_time = date('YmdHis', $t1);
        // 结束时间
        $d = strtotime("$y" . "-" . "$m");
        $t0 = date('t', $d);
        $t2 = mktime(23, 59, 59, $m, $t0, $y);
        $two_time = date('YmdHis', $t2);
        
        $map = array();
        $map['member_id'] = $this->member_id;
        $map['node_id'] = session('cc_node_id');
        $map['type'] = array(
            'neq', 
            '9');
        $map['trace_time'] = array(
            'between', 
            '' . $one_time . ',' . $two_time . '');
        
        $nowP = I('p', null, 'mysql_real_escape_string');
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount = $this->pageSize; // 每页显示条数
        $limit = ($nowP - 1) * $pageCount . ',' . $pageCount;
        
        $list = M()->table('tintegral_point_trace ')
            ->where($map)
            ->order('trace_time desc')
            ->limit($limit)
            ->select();
        $this->assign('yea', $y);
        $this->assign('mo', $m);
        $this->assign('list', $list);
        
        if (I('in_ajax', 0, 'intval') == 1) {
            $this->display('youdouList');
        } else {
            $gets = I('get.');
            $gets['in_ajax'] = 1;
            $gets['p'] = $nowP + 1;
            $nextUrl = U('', $gets);
            $this->assign('nextUrl', $nextUrl);
            $this->display();
        }
    }

    public function integralChangeDetail() {
        $nowP = I('p', null, 'mysql_real_escape_string');
        empty($nowP) ? $nowP = 1 : $nowP;
        $pageCount = $this->pageSize; // 每页显示条数
        $limit = ($nowP - 1) * $pageCount . ',' . $pageCount;
        
        $map = array();
        $map['tipt.member_id'] = $this->member_id;
        $map['tipt.node_id'] = session('cc_node_id');
        $map['tipt.type'] = '2';
        
        $list = M()->table("tintegral_point_trace tipt")->join(
            'tintegral_order_info tioi ON tioi.order_id = tipt.relation_id')
            ->join(
            'tintegral_order_info_ex tioie ON tioie.order_id = tioi.order_id')
            ->join('tbatch_info tbi ON tbi.id = tioie.b_id')
            ->join('tgoods_info tgi ON tgi.goods_id = tbi.goods_id')
            ->field(
            'tioi.order_id, tioi.goods_name, tioi.delivery_status, tioi.order_amt, tgi.goods_image')
            ->where($map)
            ->order('tipt.trace_time desc')
            ->limit($limit)
            ->select();
        $this->assign('list', $list);
        
        if (I('in_ajax', 0, 'intval') == 1) {
            $this->display('integralChangeDetailList');
        } else {
            $cataObj = D('Integral');
            $wx_info = $cataObj->user_info($this->member_id);
            $this->assign('wx_info', $wx_info);
            
            $gets = I('get.');
            $gets['in_ajax'] = 1;
            $gets['p'] = $nowP + 1;
            $nextUrl = U('', $gets);
            $this->assign('nextUrl', $nextUrl);
            $this->display();
        }
    }

    public function shippingInfo() {
        $orderId = I('get.order', null, 'mysql_real_escape_string');
        $map = array();
        $map['tioi.order_id'] = $orderId;
        $map['tioi.member_id'] = $this->member_id;
        
        $orderInfo = M()->table("tintegral_order_info tioi")->join(
            'torder_express_info toei ON tioi.order_id = toei.order_id AND toei.type = 5')
            ->field(
            'tioi.delivery_number, tioi.delivery_company, toei.express_content')
            ->where($map)
            ->find();
        $orderInfo['express_content'] = json_decode(
            $orderInfo['express_content'], TRUE);
        $this->assign('orderInfo', $orderInfo);
        
        $this->display();
    }
    
    // 商城名称
    public function _integralName() {
        $node_id = session('cc_node_id');
        $integral_name = M("tintegral_node_config")->where(
            array(
                'node_id' => $node_id))->getField('integral_name');
        if ($integral_name) {
            L('INTEGRAL_NAME', $integral_name);
        } else {
            L('INTEGRAL_NAME', '积分');
        }
    }

    /**
     * 订单详情
     */
    public function orderInfo() {
        $order_id = I('order_id', null);
        
        $table_type = I('get.table_type', '', intval);
        $where = array(
            'o.order_id' => $order_id);
        // table_type:1小店;0积分商城;
        if ($table_type == '1') {
            
            $orderInfo = M('ttg_order_info')->where(
                array(
                    'order_id' => $order_id, 
                    'node_id' => $_SESSION['cc_node_id']))->find();
            if (! empty($orderInfo)) {
                if ($orderInfo['order_type'] == '2') {
                    $orderListInfo = M()->table('ttg_order_info_ex e')
                        ->join('tbatch_info b on b.id=e.b_id')
                        ->field('e.*,b.batch_img,b.batch_amt')
                        ->where(
                        array(
                            'e.order_id' => $order_id))
                        ->select();
                } elseif ($orderInfo['order_type'] == '0') {
                    $orderListInfo = M()->table('ttg_order_info t')
                        ->join('tbatch_info b ON b.m_id=t.batch_no')
                        ->join('ttg_order_info_ex t on x.order_id = t.order_id')
                        ->field(
                        'b.batch_img,b.batch_amt,t.buy_num AS goods_num,b.batch_name AS b_name, x.ecshop_sku_id, x.ecshop_sku_desc, b.freight')
                        ->where(
                        array(
                            't.order_id' => $order_id))
                        ->select();
                }
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
            }
            $this->assign('orderInfo', $orderInfo);
            $this->assign('orderListInfo', $orderListInfo);
            $this->assign('node_short_name', $this->node_short_name);
            $this->assign('codeTrace', $codeTrace);
            $this->assign('giftInfo', $giftInfo);
            $this->assign('bonusInfo', $bonusInfo);
            $this->assign('hav_count', $hav_count);
        }
        if ($table_type == '0') {
            $orderInfo = M('tintegral_order_info')->where(
                array(
                    'order_id' => $order_id))->find();
            $orderListInfo = M()->table('tintegral_order_info_ex a')
                ->join("tbatch_info b ON b.id=a.b_id")
                ->join("tgoods_info c ON c.goods_id=b.goods_id")
                ->field('c.goods_image as batch_img,a.* ')
                ->where(array(
                'a.order_id' => $order_id))
                ->select();
            $this->assign('orderInfo', $orderInfo);
            $this->assign('orderListInfo', $orderListInfo);
        }
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
                    'node_id' => $_SESSION['cc_node_id'], 
                    'order_id' => $orderInfo['order_id']))->getfield(
                'express_content');
            $orderExpressInfoArray = json_decode($orderExpressInfo, TRUE);
            $this->assign('expressInfo', $orderExpressInfoArray);
        }
        $this->assign('deliveryStatus', $deliveryStatus);
        $this->assign('status', $status);
        $this->assign('table_type', $table_type);
        $this->display();
    }

    /**
     * 个人资料
     */
    public function personalMess() {
        // 会员信息
        $member_data = array('node_id' => $this->node_id, 'phone_no' => session('groupPhone'));
        $member_re = M()->table("tmember_info a")
            ->join('tcity_code b ON a.citycode = b.path')
            ->field('a.id,a.name,a.phone_no,a.sex,a.birthday,b.province_code,b.city_code,b.town_code, a.custom_field_data')
            ->where($member_data)->find();

        if($member_re['birthday']){
            $member_re['birthday'] = substr($member_re['birthday'], 0, 4) . '-' .substr($member_re['birthday'], 4, 2) . '-' . substr($member_re['birthday'], 6, 2);
        }
        
        if($member_re['custom_field_data'] != ''){
            $memberCustomFieldInfo = json_decode($member_re['custom_field_data'], TRUE);
        }else{
            $memberCustomFieldInfo = array();
        }

        $customFieldInfo = $this->MemberInstallModel->getCustomFieldInfo($memberCustomFieldInfo, $this->node_id, 0,1);

        /*$memberInfo = [
            'name' => $member_re['name'],
            'birthday' => $member_re['birthday'],
            'sex' => $member_re['sex'],
            'province_code' => $member_re['province_code'],
            'city_code' => $member_re['city_code'],
            'town_code' => $member_re['town_code']
        ];*/
        $memberInfo = array();

        //因为产品要求会员中心配置可以控制姓名性别生日地区等基础属性
        //所以分开单独判断并赋值到页面 页面可以根据有无值来决定是否显示
        foreach($customFieldInfo as $key=>$val){
            if($val['name'] == 'name'){
                $memberInfo['name'] = $member_re['name'];
                $this->assign('name', 1);
                unset($customFieldInfo[$key]);
            }elseif($val['name'] == 'birthday'){
                $memberInfo['birthday'] = $member_re['birthday'];
                $this->assign('birthday', 1);
                unset($customFieldInfo[$key]);
            }elseif($val['name'] == 'sex'){
                $memberInfo['sex'] = $member_re['sex'];
                $this->assign('sex', 1);
                unset($customFieldInfo[$key]);
            }elseif($val['name'] == 'area'){
                $memberInfo['province_code'] = $member_re['province_code'];
                $memberInfo['city_code'] = $member_re['city_code'];
                $memberInfo['town_code'] = $member_re['town_code'];
                $this->assign('area', 1);
                unset($customFieldInfo[$key]);
            }
        }

        $sessionInfo = array();
        foreach($customFieldInfo as $k=>$v){
            if($v['name'] != ''){
                $sessionInfo[$v['name']] = $v['value'];
            }
        }
        if($member_re['phone_no']){
            session('groupPhone',$member_re['phone_no']);
            session($member_re['phone_no'],$sessionInfo);
        }else{
            session("session('groupPhone')",$sessionInfo);
        }

        $memberInfo['id'] =  $member_re['id'];
        $memberInfo['phone_no'] =  $member_re['phone_no'];
        $this->assign('member_info', $memberInfo);
        $this->assign('memberCustomFieldInfo', $customFieldInfo);
        $this->display();
    }

    /**
     * 个人资料
     */
    public function savePersonal() {
        $birthday = I('post.birthday');
        $path = I('post.province_code') . I('post.city_code') . I('post.town_code');
        $member_id = I('post.member_id');
        $birthday_ = I('post.birthday_');
        $birthday_array = explode("-", $birthday);
        $year = $birthday_array['0'];
        $month = $birthday_array['1'];
        $day = $birthday_array['2'];
        $age = date('Y', time()) - $year;

        if($birthday_ == '1'){
            if (! is_numeric($year) || ! is_numeric($month) || ! is_numeric($day)) {
                exit('生日请输入数字！');
            }
            if (strlen($year) != '4' || strlen($month) != '2' || strlen($day) != '2') {
                exit('生日格式有误！例如：1977-05-08！');
            }
        }

        // 爱蒂宝
        $this->adbSaveStore();
        
        $customFieldData = M('tmember_info')->where(array('id'=>$member_id))->getfield('custom_field_data');

        $customFieldData = json_decode($customFieldData, TRUE);

        log_write('页面提交过来的数据:'.var_export($_POST,true));
        M()->startTrans();

        $addFieldData = array();
            foreach($_POST as $key=>$val){
                if(strstr($key, 'member_') && $key != 'member_id'){
                    $addFieldData[$key] = $_POST[$key];
                }
            }

        $saveFieldData = $addFieldData;

        //相同数据视为未修改 删除
        foreach ($customFieldData as $key=>$v1) {
            foreach($addFieldData as $key2=>$v2){
                if($v1==$v2 && $key==$key2){
                    unset($customFieldData[$key]);
                    unset($addFieldData[$key2]);
                }
            }
        }

        if($customFieldData){
            foreach($customFieldData as $kk=>$vv){
                $sql = "
UPDATE `tmember_attribute_stat` SET member_cnt=member_cnt-1 WHERE node_id='$this->node_id' AND
field_id IN (SELECT id FROM `tcollect_question_field` WHERE node_id='$this->node_id' AND NAME='$kk')
AND field_value=$vv";
                $res = M()->execute($sql);
                if(!$res){
                    log_write('保存失败res:'.M()->getLastSql());
                    M()->rollback();
                    exit('保存失败');
                }
            }
        }

        $personal_data = array(
            'name' => I('post.name'),
            'sex' => I('post.sex'), 
            'citycode' => $path, 
            'birthday' => $year . $month . $day, 
            'month_days' => $month . $day, 
            'age' => $age,
            'custom_field_data'=>json_encode($saveFieldData)
            );

        $re = M('tmember_info')->where("id = '$member_id'")->save($personal_data);

        if (!$re) {
            log_write('保存失败re:'.M()->getLastSql());
            M()->rollback();
            exit('保存失败');
        }

        foreach($addFieldData as $k=>$v){
            $sql = "
UPDATE `tmember_attribute_stat` SET member_cnt=member_cnt+1 WHERE node_id='$this->node_id' AND
field_id IN (SELECT id FROM `tcollect_question_field` WHERE node_id='$this->node_id' AND NAME='$k')
AND field_value=$v";
            $res = M()->execute($sql);
            if(!$res){
                log_write('保存失败res2:'.M()->getLastSql());
                M()->rollback();
                exit('保存失败');
            }
        }

        M()->commit();
        exit('保存成功');

    }

    /**
     *
     * @return mixed 签到，返回成功或者失败
     */
    public function checkIn() {
        $getPoint = $this->memberM->checkIn($this->node_id, $this->member_id);
        if ($getPoint === false) {
            $this->error('签到失败！' . $this->memberM->getError());
        } else {
            $info = $this->memberM->getCheckinInfo($this->member_id);
            $info['getPoint'] = $getPoint;
            $this->success($info);
        }
    }

    /**
     * 会员签到日历
     */
    public function checkinCalender() {
        $cataObj = D('Integral');
        $wx_info = $cataObj->user_info($this->member_id);
        $this->assign('member', $wx_info);
        
        $checkinInfo = $this->memberM->getCheckinInfo($this->member_id);
        $month_flag = date("m", time());
        $year_flag = date("Y", time());
        $this->assign('month_flag', $month_flag);
        $this->assign('year_flag', $year_flag);
        $this->assign('checkinInfo', $checkinInfo);
        $this->display();
    }

    /**
     *
     * @return html 返回签到日历的表格
     */
    public function checkinCalenderData() {
        $year = I("post.year");
        $month = '0'.I("post.month");
        $checkDays = $this->memberM->getCheckDaysByMonth($this->member_id, 
            $year.$month);
        $days = $this->_get_month_days($year, $month);
        $weak = date("w", mktime(0, 0, 0, $month, 1, $year));
        $html = '<table class="show" border="1" cellspancing="0" paddingspancing="10" width="100%" height="300"><tr>';
        for ($i = 1; $i <= $weak; $i ++) {
            $html .= "<td>&nbsp;</td>";
        }
        for ($j = 1; $j <= $days; $j ++) {
            $dateStr = $year . $month . str_pad($j, 2, 0, STR_PAD_LEFT);
            $signed = in_array($dateStr, $checkDays);
            $checked = $signed ? 'signed' : '';
            $ihtml = $signed ? '<i></i>' : '';
            $html .= "<td data-date='{$dateStr}' class='{$checked}'>{$j}{$ihtml}</td>";
            if ($i % 7 == 0) {
                $html .= '</tr><tr>';
            }
            $i ++;
        }
        $html .= '</tr></table>';
        $this->success($html);
    }

    /**
     * 切换账号
     */
    public function changeMember() {
        // 清空双openid
        $user_openid = array(
            'wx_openid' => array(
                'exp', 
                ' NULL'), 
            'mwx_openid' => array(
                'exp', 
                ' NULL'), 
            'nickname' => array(
                'exp', 
                ' NULL'), 
            'nickLogo' => array(
                'exp', 
                ' NULL'));
        $re = M('tmember_info')->where(
            array(
                'id' => $this->member_id))->save($user_openid);
        $this->_clearMemberSess();
        if ($re === false) {
            $this->ajaxReturn(
                array(
                    'status' => '0', 
                    'info' => '失败！'));
        }
        $this->ajaxReturn(
            array(
                'status' => '1', 
                'info' => '成功'));
    }
    
    // 登录判断
    public function checkPhoneLogin() {
        if (! session('?groupPhone')) {
            $this->ajaxReturn(array(
                'status' => '0'));
        } else {
            $this->ajaxReturn(array(
                'status' => '1'));
        }
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
        log_write(M()->_sql());
        log_write(print_r($salerInfo, TRUE));
        return $salerInfo;
    }

    public function _clearMemberSess(){
        session('groupPhone',null);
        session('cc_node_id',null);
        session('store_mem_id'.$this->node_id, null);
    }

    public function _get_month_days($year, $month) {
        $firstDay = date('Ym01', strtotime($year . $month));
        return date('d', strtotime("$firstDay +1 month -1 day"));
    }

    /**
     * *************爱蒂宝Start****************
     */
    private $is_adb;

    private $adb_openid;

    private $adb_store_id;

    /**
     * 初始化 主要处理公用数据
     *
     * @return null
     */
    private function adbInit() {
        $config = C('adb');
        if ($config['node_id'] != $this->node_id)
            return;
        // 爱蒂宝标识
        $this->is_adb = true;   
        $this->assign('is_adb', true);
        $phoneNo = session("groupPhone");
        $where = array(
            'phone_no' => session('groupPhone'), 
            'node_id' => $this->node_id);
        
        $wxUserInfo = session("node_wxid_" . $this->node_id);
        $wx_openid  =$wxUserInfo['openid'];

        // 记录使用中的openid
        $this->adb_openid = $wx_openid;
        
         //获取绑定门店ID
        $where=array(
            'openid'=>$wx_openid,
            );
        $bind_info=M('tfb_adb_user_store')->where($where)->find();
        $store_id=$bind_info['store_id'];
        if(is_null($store_id)){
            $data=array(
                'openid'=>$wx_openid,
                'store_id'=>0,
                );
            //自动绑定
            M('tfb_adb_user_store')->add($data);
            $store_id=0;
        }
        //当处于分店时。校验门店的可用性，不可用是转换总店
        if($store_id){
           $s_where=array(
                's.node_id'=>$this->node_id,
                'p.status'=>1,
                's.store_id'=>$store_id,
                );
            $store_info=M('tstore_info')->alias('s')
                                        ->field('s.store_id,s.store_name,s.province_code')
                                        ->join('tfb_adb_store_page sp on sp.store_id=s.store_id')
                                        ->join("tecshop_page_sort p on p.id=sp.page_id")
                                        ->where($s_where)->find();
            if(empty($store_info)){
                $store_id=0;
            }
        }else{
            $store_id=0;
        }
        $old_store_id=$store_id;
        if($bind_info['first'] == 1){
            M('tfb_adb_user_store')->where($where)->setField('first',0);
            session('adb_store_id',$store_id);
        }
        $temp_id=session('adb_store_id');
        $store_id=is_numeric($temp_id)?$temp_id:$store_id;
        //会员中心页面
        if(strtolower(ACTION_NAME) == 'index'){
            $check_carts=D('Adb','Service')->checkCarts();
            $this->assign('adb_check_carts',$check_carts);
            $this->assign('adb_is_change',($old_store_id > 0));
            $this->assign('adbEcshop',($store_id>0));
            $this->assign('adb_is_sub', true);
            return;
        }
        $node_id = $this->node_id;
         $channel_id = M('tchannel')->where(
                    array(
                        'node_id' => $node_id, 
                        'type' => '4', 
                        'sns_type' => '46'))->getField('id');
         $m_info = M('tmarketing_info')->where(
                    array(
                        'node_id' => $node_id, 
                        'batch_type' => CommonConst::BATCH_TYPE_STORE))->find();
        $xd_label_id = get_batch_channel($m_info['id'], $channel_id, 
            CommonConst::BATCH_TYPE_STORE, $node_id);
        $this->assign('id',$xd_label_id);

          // 检查关注状态
       $where = array(
            'u.openid' => $wx_openid, 
            'u.node_id' => $this->node_id, 
            'u.subscribe' => array(
                'neq', 
                '0'));
        $check_sub = M('twx_user')->alias('u')
                                  ->join('tweixin_info n on n.node_id=u.node_id and n.node_wx_id=u.node_wx_id')
                                  ->where($where)->count();

        
        // 门店列表
        $where = array(
            'ps.status' => 1, 
            "s.node_id" => $this->node_id, 
            "ps.node_id" => $this->node_id);
        $list = M()->table("tfb_adb_store_page ap")->field(
            's.province_code,s.store_id,s.store_name')
            ->join('tstore_info s on ap.store_id=s.store_id')
            ->join("tecshop_page_sort ps on ps.id = ap.page_id")
            ->where($where)
            ->select();
        
        // 地区
        $model = M('tcity_code');
        $province_code = array();
        $area_list = array();
        if (! empty($list)) {
            foreach ($list as $row) {
                $province_code[] = $row['province_code'];
            }
            $province_code = array_unique($province_code);
            $sort_list = $model->field('province_code id,province name')
                ->where(
                array(
                    'city_level' => 1, 
                    "province_code" => array(
                        "in", 
                        $province_code)))
                ->order('province_code')
                ->select();
            if($sort_list){
                $sort=$config['city_sort'];
                $sort_num=count($sort);
                
                foreach($sort_list as $row){
                    if($sort[$row['id']]){
                        $area_list[$sort[$row['id']]["sort"]]=$row;
                    }else{
                        $sort_num++;
                        $area_list[$sort_num]=$row;
                    }
                } 
                ksort($area_list);
                $area_list=array_values($area_list);
            }
        }
        $this->assign("adb_area_list", $area_list);
        $this->assign('adb_store_list', $list);
        // 是否关注
        $this->assign('adb_is_sub', ($check_sub > 0));
        $this->assign('adb_store_info', $store_info);
        $this->assign('adb_store_id',$store_id);
        // 关注地址二微码图片路径
        $this->assign("adb_attention_href", $config['attention_href']);
    }

    /**
     * 爱蒂宝更新门店
     *
     * @return [type] [description]
     */
    private function adbSaveStore() {
        if (! $this->is_adb || ! IS_POST)
            return true;
        $store_id = I('post.adb_store_id');
        if (empty($store_id)) {
            exit("请选择门店");
        }
        $where = array(
            's.store_id' => $store_id, 
            'p.status' => 1, 
            "s.node_id" => $this->node_id);
        
        $check = M()->table("tstore_info s")->join(
            "tfb_adb_store_page a on a.store_id = s.store_id")
            ->join("tecshop_page_sort p on a.page_id = p.id")
            ->where($where)
            ->count();
        if (! $check) {
            exit("门店不存在");
        }
        $openid = $this->adb_openid;
        $model = M('tfb_adb_user_store');
        $info = $model->field('id,store_id')
            ->where(array(
            'openid' => $openid))
            ->find();
        $id = $info['id'];
        if ($id) {
            $c_save=[];
            if ($info['store_id'] != $store_id) {
                $c_save['store_id']=$store_id;
                //切换到分店门店时清空
                D('Adb','Service')->cleanCarts();
            } 
            if($info['first'] == 1){
                $c_save['first'] =0;
            }

            if($c_save) {
                // 更新
                $res = $model->where(
                    array(
                        'id' => $id))->save($c_save);
            }
        }
        session('adb_store_id',$store_id);
        return true;
    }

/**
 * *************爱蒂宝End****************
 */
}

   
