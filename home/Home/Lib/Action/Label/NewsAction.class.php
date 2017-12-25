<?php

/*
 * 抽奖 @lastedit by tr
 */
class NewsAction extends MyBaseAction {

    public function _initialize() {
        parent::_initialize();
        /*
         * //to-do debug 模拟登录 session('node_wxid_'.$this->node_id,array(
         * 'openid'=>'oEVlMs1khkHW0LHjnyoKkMczlddU', ));
         */
        // 校验用户登录方式
        $this->_checkUser(true);
    }

    public function index() {

        if ($this->id) {
            import('@.Vendor.RankHelper');
            $RankHelper = RankHelper::getInstance();
            $RankHelper->addOneScore($this->id);
        }

        if ($this->batch_type != '2') {
            $this->error(
                    [
                            'errorImg'     => '__PUBLIC__/Label/Image/waperro5.png',
                            'errorTxt'     => '错误访问！',
                            'errorSoftTxt' => '你的访问地址出错啦~'
                    ]
            );
        }
        // 判断预览时间是否过期
        if (false === $this->checkCjEndtime()) {
            $this->error(
                    [
                            'errorImg'     => '__PUBLIC__/Label/Image/waperro6.png',
                            'errorTxt'     => '预览时间已过期！',
                            'errorSoftTxt' => '您访问的预览地址30分钟内有效，现已超时啦~'
                    ]
            );
        }
        // 非标渠道跳转url
        $channelInfo = $this->channelInfo;
        
        // 访问量 start
        import('@.Vendor.DataStat');
        $opt = new DataStat($this->id, $this->full_id);
        $id = $this->id;
        $opt->UpdateRecord();
        // 访问量 end

        $go_url = get_val($channelInfo, 'go_url');
        if ($go_url) {
            redirect($go_url);
            exit();
        }
        
        // 活动
        $row = $this->marketInfo;
        
        // 判断微信名片中奖活动
        if ($id == C('VCARD_ACTIVITY_NUMBER')) {
            $phone = I('get.mobile');
            if (!check_str($phone, ['strtype' => 'mobile'])) {
                $this->error('无法获取正确的手机号码！');
            } else {
                $vcardModel    = M('TvisitingCard');
                $isVcardMobile = $vcardModel->where(['phone_no' => $phone])->getfield('id');
                if (!$isVcardMobile) {
                    $this->error('对不起，参与本活动请先创建二维码名片！', U('Wap/Vcard/index'));
                }
                $this->assign('phone', $phone);
                $this->assign('vcard', 'vcard');
            }
        }
        // 抽奖配置表
        if ($row['is_cj'] == '1') {
            $model_c = M('tcj_rule');
            $map_c = ['batch_type' => '2', 'batch_id' => $this->batch_id, 'status' => '1'];
            $cj_rule_query = $model_c->field('cj_button_text,cj_check_flag')
                ->where($map_c)
                ->find();
            // 抽奖文字配置
            $cj_text = $cj_rule_query['cj_button_text'];
            // 判断是否显示参与码
            $cj_check_flag = $cj_rule_query['cj_check_flag'];
        }
        // 判断是否登录
        if (isset($_SESSION['onlineExper'])) {
            $islogin = 0;
        } else {
            $islogin = session('cjUserInfo') ? 1 : 0;
        }
        
        $this->assign('id', $this->id);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('cj_check_flag', $cj_check_flag);
        $this->assign('cj_text', $cj_text);
        $this->assign('row', $row);
        $this->assign('islogin', $islogin);
        $this->assign('pay_token', $this->pay_token);
        
        // 圣诞树
        if ($this->batch_id == '1098' || $this->batch_id == '1159') {
            $this->display('item_dfs');
            exit();
        }
        // 泡泡
        if ($this->batch_id == '1141') {
            $this->display('item_pop');
            exit();
        }
        $show_menu = 0;
        if (in_array($id, ['5317', '56186', '56175', '56181', '56184',  '56187'])) {
            // 鱼旨非标隐藏分享按钮
            $wxnodeinfo = M('tweixin_info')->where(['node_id' => $this->node_id])->find();
            $show_menu = 1;
            $wx_share_config = D('WeiXin', 'Service')->getWxShareConfig('', $wxnodeinfo['app_id'], $wxnodeinfo['app_secret']);
            $shareArr = array(
                'config' => $wx_share_config);
            $this->assign('wx_share_config', $wx_share_config);
            $this->assign('shareData', $shareArr);
        }
        $this->assign('show_menu', $show_menu);
        // 获取全局存的手机号
        $this->assign('_global_user_mobile', $this->_userCookieMobile());

        import('@.Service.FilterDuplicateRequestService');
        $needIgnoreFlag = FilterDuplicateRequestService::getIgnoreFlag($this->batch_id);
        $this->assign('needIgnoreFlag', $needIgnoreFlag);

        // for 中奖记录 start
        $DLCommonMobile = $this->getMobileForAwardList($id);
        if ($DLCommonMobile) {
            $this->assign('showAwardList', 'block');
        } else {
            if ($needIgnoreFlag && $this->marketInfo['join_mode'] == 1) {//已经抽过奖品了 且为微信参与方式
                $personId = $this->getPersonId();
                $this->setMobileForAwardList($id, $personId);
                $this->assign('showAwardList', 'block');
            } else {
                $this->assign('showAwardList', 'none');
            }

        }
        // for 中奖记录 end

        $weChatDrawLotteryFlag = 0;
        $needSetWeChatDrawLotteryFlag = 0;
        $this->assign('weChatDrawLotteryFlag', $weChatDrawLotteryFlag);
        if (isset($this->marketInfo['join_mode']) && $this->marketInfo['join_mode'] == 1) {
            $personId = $this->getPersonId();
            $weChatDrawLotteryFlag = FilterDuplicateRequestService::getWechatDrawLotteryFlag($this->batch_id, $personId);
            $needSetWeChatDrawLotteryFlag = 1;
        }
        $this->assign('weChatDrawLotteryFlag', $weChatDrawLotteryFlag);

        $this->assign('needSetWeChatDrawLotteryFlag', $needSetWeChatDrawLotteryFlag);


        $this->display(); // 输出模板
    }

    // 查看未领取卡券
    protected function _searchUnfetchCard() {
        // 如果是需要片信卡妆
        if ($this->needWxLogin) {
            $wxInfo = $this->getWxUserInfo();
            $open_id = $wxInfo['openid'];
            $sql = "SELECT a.id FROM tbatch_info a INNER JOIN tbatch_channel b ON a.m_id = b.batch_id WHERE " .
                 " b.id = " . $this->id . " and a.card_id is not null";
            log_write($sql, 'SQL');
            $batchids = M()->query($sql);
            log_write(print_r($batchids, true), 'batchids');
            $batchids = array_valtokey($batchids, 'id', 'id');
            if (! $batchids) {
                return false;
            }
            $where = array(
                'open_id' => $open_id, 
                'card_batch_id' => array(
                    'in', 
                    $batchids), 
                'status' => 1);
            // 这条可以查找 用户有没有未领取的记录
            $assist_number = M()->table('twx_assist_number')
                ->where($where)
                ->find();
            log_write(M()->_sql(), 'SQL');
            if (! $assist_number) {
                return false;
            }
            $batchInfo = M('tbatch_info')->where(
                array(
                    'id' => $assist_number['card_batch_id']))->find();
            log_write(M()->_sql(), 'SQL');
            $service = D('WeiXinCard', 'Service');
            $service->init_by_node_id($this->node_id);
            // $service->init($this->appId,$this->appSecret,$this->accessToken);
            $card_ext = $service->add_assist_number($open_id, 
                $assist_number['card_batch_id']);
            $card_id = $batchInfo['card_id'];
            return array(
                'card_ext' => $card_ext, 
                'card_id' => $card_id);
        }
        return false;
    }
}