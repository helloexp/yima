<?php

/**
 * 付满送活动 Class PaySendActivity
 */
class PaySendActivityAction extends MyBaseAction {

    const PAY_SEND_ACTIVITY_TYPE = 54;
    // 付满送活动类型(抽奖类型为54)
    private $defaultimg;

    /**
     *
     * @var PaySendActivityModel
     */
    public $PaySendActivityModel;

    public function _initialize() {
        $this->defaultimg = 'http://' . $_SERVER["HTTP_HOST"] .
             '/Home/Public/Label/Image/paysend_default.jpg';
        parent::_initialize();
        if ($this->marketInfo['batch_type'] != self::PAY_SEND_ACTIVITY_TYPE) { // 活动类型不正确
            $this->showErrorByErrno(- 1045);
        }
        $cardClass=M()->table(array('tbatch_info'=>'t','twx_card_type'=>'tc'))
        ->field('tc.card_class')->where(array('m_id'=>$this->marketInfo['id']))
        ->where('t.card_id=tc.card_id')
        ->find();
        $this->card_class=$cardClass['card_class'];
        if($cardClass['card_class']!=2){
             $this->_checkUser(true, isFromWechat());
        }
        $resp_cj_trace_id = session('resp_cj_trace_id');
        /*
         * if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')!==false &&
         * !empty($this->wxSess['openid']) && !empty($resp_cj_trace_id)){
         * $this->DrawLotteryService=D('DrawLottery', 'Service');
         * $this->DrawLotteryModel = D('DrawLottery', 'Model'); $awardList =
         * $this->DrawLotteryModel->getAwardList( array( 'mobile' =>
         * $this->wxSess['openid'], 'batch_id' => $this->marketInfo['id'],
         * 'batch_type' => $this->marketInfo['batch_type'] ) ); //
         * log_write('sql:'.M()->getlastsql()); $wechatCard =
         * $this->DrawLotteryService->getUnfetchedWechatCard($awardList);
         * $finalAwardList = $this->formatAwardList($awardList, $wechatCard);
         * foreach( $finalAwardList as $v){
         * if($v['showStatus']=='wechatCardNonReceived' &&
         * $v['cj_trace_id']==session('resp_cj_trace_id')){
         * session('DLCommonMobile',$this->wxSess['openid']);
         * session('gobackLink',$_SERVER['REQUEST_URI']);
         * if(!empty($_REQUEST['check'])){
         * redirect(U('DrawLottery/DrawLotteryCommon/awardList',$_REQUEST));
         * }else {
         * $this->assign('award_url',U('DrawLottery/DrawLotteryCommon/awardList',$_REQUEST));
         * } break; } } }
         */
        $this->shareData(); // 分享
        $this->PaySendActivityModel = D('PaySendActivity');
    }

    public function formatAwardList($awardList, $wechatCard) {
        $finalAwardList = array();
        if ($awardList && is_array($awardList)) {
            $currentTime = date('YmdHis');
            foreach ($awardList as $index => $award) {
                $aid = $award['twx_assist_number_id'] ? $award['twx_assist_number_id'] : 0;
                $award['showStatus'] = 0;
                if ($award['goods_type'] == CommonConst::GOODS_TYPE_HB) { // 红包
                    $phone = isset($award['phone']) ? $award['phone'] : '';
                    if ($currentTime >= $award['bonus_end_time']) { // 已过期
                        $award['showStatus'] = 'bonusExpire'; // 已过期
                    } else if (! strlen($phone) == 11 || ! is_numeric($phone)) { // phone不是手机号，还没有被领取
                        $award['showStatus'] = 'bonusNonReceived'; // 未领取
                    } else if ($award['bonus_num'] > $award['bonus_use_num']) {
                        $award['showStatus'] = 'gotoUseBonus'; // 去使用
                    } else { // 已使用
                        $award['showStatus'] = 'bonushasUsed'; // 已使用
                    }
                } else if ($award['card_id']) { // 微信卡券
                    if ($award['wx_status'] ==
                         DrawLotteryCommonService::WECHAT_CARD_UNFETCHED) { // 没有领取
                        $award['showStatus'] = 'wechatCardNonReceived'; // 没有领取
                    } else {
                        $award['showStatus'] = 'wechatCardHasReceived'; // 已经领取
                    }
                    log_write(
                        __METHOD__ . '$wechatCard:' . var_export($wechatCard, 1));
                    log_write(__METHOD__ . '$aid:' . var_export($aid, 1));
                    $award['wxcardinfo'] = json_encode(
                        array(
                            'card_id' => $award['card_id'], 
                            'card_ext' => $wechatCard[$aid]['card_ext']));
                } else { // 卡券
                    if ($award['send_mobile'] == '13900000000') { // 未领取
                        $award['showStatus'] = 'couponNonReceived'; // 未领取
                    } else {
                        $award['showStatus'] = 'couponHasReceived'; // 已经领取
                    }
                }
                $finalAwardList[$index] = $award;
                unset($award, $index);
            }
        }
        
        return $finalAwardList;
    }

    public function shareData() {
        $img = $this->defaultimg;
        // 付满送的分享
        // && (strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')!==false)
        if (! empty($this->marketInfo['defined_one_name']) &&
             $this->marketInfo['defined_two_name'] > 0) {
            if (! IS_AJAX && IS_GET &&
             $this->batch_type == self::PAY_SEND_ACTIVITY_TYPE) {
            if (! empty($this->marketInfo['log_img'])) {
                $img = get_upload_url($this->marketInfo['log_img']);
            }
            $mysharedid = $this->insert_share_prize($_REQUEST['sharedid']);
            log_write(__METHOD__ . '$mysharedid:' . $mysharedid);
            if (! $mysharedid)
                $mysharedid = '';
            session('mysharedid', $mysharedid);
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
                $shareArr = array(
                    'config' => D('WeiXin', 'Service')->getWxShareConfig(), 
                    'link' => U('index', 
                        array(
                            'sharedid' => $mysharedid, 
                            'pay_token' => $this->pay_token, 
                            'wechat_card_js' => 1, 
                            'id' => $this->id), '', '', TRUE), 
                    'title' => $this->marketInfo['node_name'], 
                    // 'shareNote'=>$row['wap_info'],
                    'desc' => '精品商户送券啦，先到先得，快来抢~~' .
                         $this->marketInfo['defined_four_name'], 
                        'title1' => '精品商户【' . $this->marketInfo['node_name'] .
                         '】送券啦，先到先得，快来抢~~' .
                         $this->marketInfo['defined_four_name'], 
                        'imgUrl' => $img);
            } else {
                $shareArr = array(
                    'link' => U('index', 
                        array(
                            'sharedid' => $mysharedid, 
                            'pay_token' => $this->pay_token, 
                            'wechat_card_js' => 1, 
                            'id' => $this->id), '', '', TRUE));
            }
            $this->assign('shareData', $shareArr);
        }
    }
}

/**
 * 展示付满送活动页面(用于抽奖)
 *
 * @author Jeff Liu<liuwy@iamgeco.com.cn>
 */

// 写入微信表
public function inTabel($openid, $access_token, $wxUserInfo = array()) {
    $wxarr = M('twx_wap_user')->where(array(
        'openid' => $openid))->find();
    if ($wxarr['id'])
        return $wxarr['id'];
    $in_arr = array(
        'node_id' => $this->node_id, 
        'label_id' => $this->id, 
        'add_time' => date('YmdHis'), 
        'nickname' => $wxUserInfo['nickname'], 
        'sex' => $wxUserInfo['sex'], 
        'province' => $wxUserInfo['province'], 
        'city' => $wxUserInfo['city'], 
        'headimgurl' => $wxUserInfo['headimgurl'], 
        'openid' => $openid, 
        'access_token' => $access_token);
    $wxid = M('twx_wap_user')->add($in_arr);
    if (! $wxid) {
        return false;
    }
    return $wxid;
}
// (strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')!==false) &&
// !empty($this->wxSess['openid'])
public function insert_share_prize($shredid) {
    if (! empty($this->marketInfo['defined_one_name']) &&
         $this->marketInfo['defined_two_name'] > 0) {
        // 分享回调url
        if (! empty($shredid))
            $rs = M('tshare_prize_info')->where(
                array(
                    // 'wx_open_id'=>$this->wxSess['openid'],
                    'id' => $shredid, 
                    'node_id' => $this->node_id, 
                    'm_id' => $this->marketInfo['id'], 
                    'relation_id' => $this->pay_token, 
                    'batch_type' => self::PAY_SEND_ACTIVITY_TYPE))->find();
        if (empty($rs)) {
            $lastid = M('tshare_prize_info')->add(
                array(
                    'node_id' => $this->node_id, 
                    'm_id' => $this->marketInfo['id'], 
                    'wx_open_id' => $this->wxSess['openid'], 
                    'add_time' => date("YmdHis"), 
                    'relation_id' => $this->pay_token, 
                    'batch_type' => self::PAY_SEND_ACTIVITY_TYPE, 
                    'phone_no' => session('input_mobile') ? session(
                        'input_mobile') : '',  // 手机号码要修改
                    'total_num' => $this->marketInfo['defined_two_name']));
            return $lastid;
        } else {
            return $rs['id'];
        }
    }
    return 0;
}

public function index() {
    // 非标渠道跳转url
    $channelInfo = $this->channelInfo;
    $defaultimg = $this->defaultimg;
    $this->assign('defaultimg', $defaultimg);
    // 访问量
    import('@.Vendor.DataStat');
    $opt = new DataStat($this->id, $this->full_id);
    $id = $this->id;
    $opt->UpdateRecord();
    
    $go_url = $channelInfo['go_url'];
    if ($go_url) {
        redirect($go_url);
        exit();
    }
    if($this->card_class==2){
        //goto 领取朋友的券

    }else{
    
    // 活动
    $row = $this->marketInfo;
    
    // 判断微信名片中奖活动
    if ($id == C('VCARD_ACTIVITY_NUMBER')) {
        $phone = I('get.mobile');
        if (! check_str($phone, array(
            'strtype' => 'mobile'))) {
            $this->error('无法获取正确的手机号码！');
            exit();
        } else {
            $isVcardMobile = $this->PaySendActivityModel->getVisitingCard(
                array(
                    'phone_no' => $phone), 'id');
            if (! $isVcardMobile) {
                $this->error('对不起，参与本活动请先创建二维码名片！', U('Wap/Vcard/index'));
            }
            $this->assign('phone', $phone);
            $this->assign('vcard', 'vcard');
        }
    }
    
    $cjCheckFlag = 0;
    $cjText = '立即抽取';
    // 抽奖配置表
    if ($row['is_cj'] == '1') {
        $where = array(
            'batch_type' => self::PAY_SEND_ACTIVITY_TYPE, 
            'batch_id' => $this->batch_id, 
            'status' => '1');
        $cjRule = $this->PaySendActivityModel->getCjRule($where, 
            'cj_button_text,cj_check_flag');
        // 抽奖文字配置
        $cjText = $cjRule['cj_button_text'];
        // 判断是否显示参与码
        $cjCheckFlag = $cjRule['cj_check_flag'];
    }
    // 判断是否登录
    $islogin = $this->isLogined(false);
    log_write(__METHOD__ . '$_SESSION:' . var_export($_SESSION, 1));
    log_write(
        __METHOD__ . '$this->wxUserInfo:' . var_export($this->wxUserInfo, 1));
    log_write(__METHOD__ . '$this->wxSess:' . var_export($this->wxSess, 1));
    log_write(__METHOD__ . '$islogin:' . var_export($islogin, 1));
    $remain_num = M('tbatch_info')->where(
        array(
            'm_id' => $this->marketInfo['id']))->getField('remain_num');
    $this->assign('remain_num', $remain_num);
    if (! empty($_REQUEST['sharedid'])) {
        $rs = M('tshare_prize_info')->where(
            array(
                'id' => $_REQUEST['sharedid'], 
                'node_id' => $this->node_id, 
                'm_id' => $this->marketInfo['id'], 
                'relation_id' => $this->pay_token, 
                'batch_type' => '54'))->find();
    }
    $sessionmobile = session('input_mobile');
    if (empty($sessionmobile))
        $sessionmobile = '';
    if (! empty($rs)) {
        $list = M('tshare_prize_receive_trace')->alias('tr')
            ->field(
            'tr.id,tr.add_time, tr.receive_phone,tu.nickname,tu.headimgurl')
            ->join('twx_wap_user  tu on tu.openid=tr.receive_wx_openid')
            ->where(array(
            'tr.share_id' => $rs['id']))
            ->
        // 'tr.receive_phone'=>array('neq',$sessionmobile)
        
        select();
        $this->assign('list', $list);
        $count = M()->table(
            array(
                'tshare_prize_info' => 'ti', 
                'tshare_prize_receive_trace' => 'tp'))
            ->where(array(
            'tp.share_id' => $rs['id']))
            ->where('ti.id=tp.share_id')
            ->count();
    }
    if ($count > 0)
        $count = $count - 1;
    if (! empty($this->marketInfo['defined_one_name'])) {
        
        $leave = $this->marketInfo['defined_two_name'] - $count;
        $this->assign('leave', $leave);
    }
    // pd是否微信卡券card_id
    $card_id = M('tbatch_info')->where(
        array(
            'node_id' => $this->node_id, 
            'm_id' => $this->marketInfo['id']))->getField('card_id');
    $this->assign('card_id', $card_id);
    $join_mode = $this->marketInfo['join_mode'];
    if (! $join_mode) {
        if ($card_id)
            $join_mode = 1;
    }
    if (! empty($rs)) {
        if (! $join_mode) {
            $showactive = M('tshare_prize_receive_trace')->where(
                array(
                    'receive_phone' => $sessionmobile, 
                    'share_id' => $rs['id']))->count();
        } else {
            $showactive = M('tshare_prize_receive_trace')->where(
                array(
                    'receive_wx_openid' => $this->wxSess['openid'], 
                    'share_id' => $rs['id']))->count();
        }
        $this->assign('showactive', $showactive);
    }
    // 是否抽奖
    /*
     * $ward=M('tcj_trace')->where( array( 'batch_type'=>$this->batch_type,
     * 'batch_id'=>$this->marketInfo['id'],
     * 'channel_id'=>$this->channelInfo['id'], 'mobile'=>$sessionmobile,
     * 'node_id'=>$this->node_id, 'status'=>'2' ))->find();
     * $this->assign('myward',$ward);
     */
    // 0-优惠券 1-代金券 2-提领券 3-折扣券
    $goodsinfo = M()->table(
        array(
            'tgoods_info' => 'gf', 
            'tbatch_info' => 'ti'))
        ->field(
        'gf.goods_name,gf.goods_amt,gf.market_price,gf.goods_discount, gf.goods_type,ti.batch_amt,ti.batch_img,ti.begin_time,ti.end_time,ti.batch_short_name')
        ->where(
        array(
            'ti.node_id' => $this->node_id, 
            'ti.m_id' => $this->marketInfo['id']))
        ->where('gf.goods_id = ti.goods_id')
        ->find();
    $this->assign('goodsinfo', $goodsinfo);
    // 是否过期
    $this->assign('sharedid', I("sharedid"));
    $this->assign('id', $this->id);
    $this->assign('batch_type', $this->batch_type);
    $this->assign('batch_id', $this->batch_id);
    $this->assign('cj_check_flag', $cjCheckFlag);
    $this->assign('cj_text', $cjText);
    $this->assign('row', $row);
    $this->assign('islogin', $islogin);
    $this->assign('pay_token', $this->pay_token);
    
    $this->assign('isPaySendActivity', 1);
    
    $this->display(); // 输出模板
    }
}
}