<?php

/**
 * 抽奖
 */
class ShareBatchAction extends MyBaseAction
{

    public $expiresTime = 600;
    // 手机验证码过期时间
    public $joinMode = '';
    // 用户参与方式，很重要，涉及到发奖品的方式

    /**
     * @var DrawLotteryCommonService
     */
    private $DrawLotteryCommonService;

    /**
     * @var SendAwardTraceModel
     */
    protected $SendAwardTraceModel;

    private function checkLogin($autoLogin = false)
    {
        // 校验使用的用户
        $this->_checkUser($autoLogin);
        // 如果是微信用户
        $this->joinMode = $this->marketInfo['join_mode'];
        $userInfo       = session('cjUserInfo');
        if ($userInfo) {
            return true;
        } else {
            return false;
        }
    }

    // 抽奖页面登录
    public function login()
    {
        $mobile     = I('phone', null);
        $check_code = I('verify', null);
        if (empty($mobile) || empty($check_code)) {
            $this->error('手机号和验证码不能为空！');
        }


        $phoneCheckCode = session('checkCode');// 手机验证码

        if (empty($phoneCheckCode) || $phoneCheckCode['number'] != $check_code) {
            $this->error('手机验证码不正确');
        }
        if (time() - $phoneCheckCode['add_time'] > $this->expiresTime) {
            $this->error('手机验证码已经过期');
        }

        $info = M('tmember_info_tmp')->where(['node_id' => $this->node_id, 'phone_no' => $mobile])->find();
        if (!$info) {
            $data      = [
                    'node_id'    => $this->node_id,
                    'phone_no'   => $mobile,
                    'channel_id' => $this->channel_id,
                    'batch_id'   => $this->batch_id,
                    'add_time'   => date('YmdHis')
            ];
            $member_id = M('tmember_info_tmp')->add($data);
            if (!$member_id) {
                $this->error('注册会员失败！');
            }
        } else {
            $member_id = $info['id'];
        }
        $user_info = [
                'phone_no' => $mobile,
                'batch_id' => $this->batch_id,
                'user_id'  => $member_id,
                'label_id' => $this->id,
                'node_id'  => $this->node_id
        ];
        session('cjUserInfo', $user_info);
        if (!session('?groupPhone')) {
            session('groupPhone', $mobile);
        }
        $this->success('绑定成功' . $phoneCheckCode['number'] . $check_code);
    }

    // 手机发送验证码
    public function sendCheckCode()
    {
        $overdue = $this->checkDate();
        if ($overdue === false) {
            $this->ajaxReturn("error", '该活动不在有效期之内！', 0);
        }

        $phoneNo = I('post.phone', null);
        $error   = null;
        if (!check_str($phoneNo, ['null' => false, 'strtype' => 'mobile'], $error)) {
            $resp = "手机号{$error}";
            $this->ajaxReturn("error", $resp, 0);
        }

        // 测试环境不下发，验证码直接为1111
        if (!is_production()) {
            $checkCode = ['number' => '1111', 'add_time' => time()];
            session('checkCode', $checkCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }

        // 翼蕙宝短信发送
        if ($this->node_id == C('Yhb.node_id')) {
            // 发送频率验证
            $checkCode = session('checkCode');
            if (!empty($checkCode) && (time() - $checkCode['add_time']) < 90) {
                $resp = "验证码发送过于频繁!";
                $this->ajaxReturn("error", $resp, 0);
            }
            $num    = mt_rand(1000, 9999);
            $YhbSms = D('YhbSms', 'Service');
            $res    = $YhbSms->sendVerifyCodeSms($phoneNo, $num);
            if (!$res) {
                $resp = "发送失败!";
                $this->ajaxReturn("error", $resp, 0);
            }
            $checkCode = array(
                    'number'   => $num,
                    'add_time' => time()
            );
            session('checkCode', $checkCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
            exit();
        }

        //厦门银行非标
        if (isXmyhFb($this->node_id, $this->batch_id)) {
            //厦门银行中奖名单
            $xmyhZjmd = D('XmyhZjmd');
            //是否在中奖名单里面
            $onList = $xmyhZjmd->isOnList($phoneNo, $this->batch_id);
            if (!$onList) {
                $resp = '很遗憾，您未达标，感谢您的支持！';
                $this->ajaxReturn("error", $resp, 0);
            } elseif ($onList['joined']) {
                $resp = '您已参与该活动或未达标，感谢您的支持！';
                $this->ajaxReturn("error", $resp, 0);
            }
        }

        // 发送频率验证
        $checkCode = session('checkCode');
        if (!empty($checkCode) && (time() - $checkCode['add_time']) < 90) {
            $resp = "验证码发送过于频繁!";
            $this->ajaxReturn("error", $resp, 0);
        }
        $num = mt_rand(1000, 9999);
        // 短信内容
        $codeInfo = "短信验证码：{$num}；如非本人操作请忽略！";
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号

        // 请求参数
        $req_array     = [
                'NotifyReq' => [
                        'TransactionID' => $TransactionID,
                        'ISSPID'        => C('MOBILE_ISSPID'),
                        'SystemID'      => C('ISS_SYSTEM_ID'),
                        'SendLevel'     => '1',
                        'Recipients'    => array(
                                'Number' => $phoneNo
                        ),  // 手机号
                        'SendClass'     => 'MMS',
                        'MessageText'   => $codeInfo,  // 短信内容
                        'Subject'       => '',
                        'ActivityID'    => C('MOBILE_ACTIVITYID'),
                        'ChannelID'     => '',
                        'ExtentCode'    => ''
                ]
        ];
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array    = $RemoteRequest->requestIssServ($req_array);
        $ret_msg       = $resp_array['NotifyRes']['Status'];
        if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
            $resp = "发送失败!";
            $this->ajaxReturn("error", $resp, 0);
        }
        $checkCode = ['number' => $num, 'add_time' => time()];
        session('checkCode', $checkCode);
        $this->ajaxReturn("success", "验证码已发送", 1);
    }

    // 抽奖
    public function returnCj()
    {
        $this->checkLogin(true);
        $islogin = $this->isLogined();

        $overdue = $this->checkDate();
        if ($overdue === false) {
            $respData = ['info' => '该活动不在有效期之内', 'status' => 0];
            $this->ajaxReturn($respData);
        }

        $return_session = session('cjUserInfo');
        $mobile         = $return_session['phone_no'];

        // 过滤重复请求 start
        $mobile = is_null($mobile) ? 0 : $mobile;
        $personId = $this->getPersonId();
        $prarms = [
                'personId'   => $personId,
                'node_id'  => $this->node_id,
                'id'       => $this->id,
                'batch_id' => $this->batch_id
        ];
        $this->filterDuplicateRequest($prarms);
        // 过滤重复请求 end

        //设置正在处理抽奖标识（和过滤重复请求差不多，如果请求处理时间比较长，就有可能出现问题） start
        import('@.Service.FilterDuplicateRequestService');
        $processingFlag = FilterDuplicateRequestService::getDrawLotteryProcessingFlag($this->batch_id, $personId);
        if ($processingFlag) { //正在处理 不能再次请求抽奖接口
            $this->ajaxReturn('error', '您上次抽奖还正在处理，请耐心等待结果出来之后再进行尝试^_^', 0);
        }
        FilterDuplicateRequestService::setDrawLotteryProcessingFlag($this->batch_id,$personId, 300);//默认 最长设置为300s 5min 50s还没处理完的话自动释放
        //设置正在处理抽奖标识（和过滤重复请求差不多，如果请求处理时间比较长，就有可能出现问题） end

        // 福满送特别处理分享领奖人数检查
        $this->paysend_before();
        // 如果是微信抽奖，进入微信抽奖分支
        $this->joinMode    = $this->marketInfo['join_mode'];
        $isPaySendActivity = $_REQUEST['isPaySendActivity'];
        if ($isPaySendActivity && is_numeric($isPaySendActivity)) {
            $this->joinMode = $this->get_join_mode();
        }
        if ($this->joinMode) {
            $this->returnCjWx();
            return;
        }
        // 如果是手机号登录
        if (!$islogin) {
            FilterDuplicateRequestService::delDrawLotteryProcessingFlag($this->batch_id, $personId);
            $this->error('请登录');
        }
        $other = array();
        // 如果微信登录过
        $wxUserInfo = $this->getWxUserInfo();
        if ($wxUserInfo) {
            $other = [
                    'wx_open_id' => $wxUserInfo['openid'],
                    'wx_nick'    => $wxUserInfo['nickname']
            ];
        }
        // 补充付满送的pay_token字段
        $pay_token = I('pay_token', null);
        if ($pay_token != '') {
            $other = array_merge($other, ['pay_token' => $pay_token]);
        }

        // for 中奖记录 start
        if (empty($this->DrawLotteryCommonService)) {
            $this->DrawLotteryCommonService = D('DrawLotteryCommon', 'Service');
        }
        $this->DrawLotteryCommonService->setMobileAndGobackUrl($this->id, ['openid' => $other['wx_open_id']], $mobile);
        $this->DrawLotteryCommonService->setMobileForAwardList($this->id, $personId);
        // for 中奖记录 start

        import('@.Vendor.ChouJiang');
        $choujiang = new ChouJiang($this->id, $mobile, $this->full_id, null, $other);
        // 中奖提示
        $cjrule   = M('tcj_rule');
        $map1     = [
                'node_id'    => $this->node_id,
                'batch_id'   => $this->batch_id,
                'batch_type' => $this->batch_type
        ];
        $ruleInfo = $cjrule->where($map1)->find();
        $resp     = $choujiang->send_code();
        FilterDuplicateRequestService::delDrawLotteryProcessingFlag($this->batch_id, $personId);
        log_write(__METHOD__ . ': $resp:' . var_export($resp, 1));
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            $cj_msg = $ruleInfo["cj_resp_text"] == "" ? "恭喜你，中奖了！！！" : $ruleInfo["cj_resp_text"];

            // 奖品类别
            $cjCateId = M()->table('tcj_batch b')->join('tcj_cate c ON b.cj_cate_id=c.id')->where(
                    "b.id={$resp['rule_id']}"
            )->getField('c.id');

            $respData = ['data' => $cjCateId, 'info' => $cj_msg, 'status' => 1];
            // 如果有特殊的流程，记录一下session，保留中奖结果,以便用户再次提交手机号
            // 中了微信卡券
            if (!empty($resp['card_ext']) || !empty($resp['card_id'])) {
                $respData['card_ext'] = $resp['card_ext'];
                $respData['card_id']  = $resp['card_id'];
            } elseif (!empty($resp['request_id'])) {// 中了手机凭证奖品
                log_write(print_r($resp, true));
                // 临时中奖码，需要响应给前台，校验一下正确性用的，校验正确后失效,以免直接暴露 request_id和
                // cj_trace_id,用完以后清空
                $cj_code = time() . mt_rand(100, 999);
                $cjCodeData= array(
                        'cj_code'     => $cj_code,
                        'request_id'  => $resp['request_id'],
                        'cj_trace_id' => $resp['cj_trace_id'],
                        'card_ext'    => $resp['card_ext'],
                        'card_id'     => $resp['card_id']
                );
                session('_TmpChouJian_',$cjCodeData);
                $respData['cj_code'] = $cj_code;
                import('@.Vendor.RedisHelper');
                RedisHelper::getInstance()->set('tmpDrawLottery:cjCode:'.$personId, $cjCodeData);
                RedisHelper::getInstance()->del('tmpDrawLottery:cjCode:'.$personId);
            }
            FilterDuplicateRequestService::setIgnoreFlag($this->batch_id);
            $this->ajaxReturn($respData);
        } else {
            $needTmpIgnore = false;
            // 未中奖提示文字
            $noAwardNotice = explode('|', $ruleInfo['no_award_notice']);
            $respInfo = $noAwardNotice[array_rand($noAwardNotice)] or $respInfo = '很遗憾，未中奖,感谢您的参与！';
            if ($resp['resp_id'] == '1005') {
                $needTmpIgnore = true;
                $respInfo      = '今天您已经参与过抽奖，不能再抽了！';
            } elseif ($resp['resp_id'] == '1016') {
                $needTmpIgnore = true;
                $respInfo      = '您已经参与过该抽奖活动，不能再抽了！';
            }

            //暂时屏蔽用户（使其单位时间内不能提交） start
            if ($needTmpIgnore) {
                FilterDuplicateRequestService::setIgnoreFlag($this->batch_id);
            }
            //暂时屏蔽用户（使其单位时间内不能提交） end
            $this->ajaxReturn(10, $respInfo, 2);
        }
    }

    // 按微信openid抽奖
    public function returnCjWx()
    {
        import('@.Service.FilterDuplicateRequestService');
        import('@.Vendor.ChouJiang');
        $wxUserInfo = $this->getWxUserInfo();
        $personId = $this->getPersonId();
        if (!$wxUserInfo) {
            FilterDuplicateRequestService::delDrawLotteryProcessingFlag($this->batch_id, $personId);
            $this->error("请从微信登录");
        }

        $mobile = '';
        $other = [
                'pay_token'  => get_val($_REQUEST, 'pay_token'),
                'wx_open_id' => $wxUserInfo['openid'],
                'wx_nick'    => $wxUserInfo['nickname']
        ];

        // for 中奖记录 start
        if (empty($this->DrawLotteryCommonService)) {
            $this->DrawLotteryCommonService = D('DrawLotteryCommon', 'Service');
        }
        $this->DrawLotteryCommonService->setMobileAndGobackUrl(
                $this->id,
                ['openid' => $other['wx_open_id'], 'wx_nick' => $wxUserInfo['nickname']],
                $mobile
        );
        // for 中奖记录 start

        // 翼惠宝抽奖：1 判断用户是否注册 2 抽奖需带上注册的手机号
        if ($this->node_id == C('Yhb.node_id')) {
            $yhbUser = $this->checkYhbMember();
            if (!$yhbUser['is_member']) {
                $respData = ['info' => '请先注册！', 'status' => 5];
                FilterDuplicateRequestService::delDrawLotteryProcessingFlag($this->batch_id, $personId);
                $this->ajaxReturn($respData);
            }
            $mobile = $yhbUser['info']['mobile'];
        }

        //如果是微信的话 进行测试处理，发起抽奖请求之前 先设置已抽奖flag
        if (isset($this->marketInfo['join_mode']) && $this->marketInfo['join_mode'] == 1) {
            $wechatDrawLotteryFlag = FilterDuplicateRequestService::getWechatDrawLotteryFlag($this->batch_id, $personId);
            if (empty($wechatDrawLotteryFlag)) {
                FilterDuplicateRequestService::setWechatDrawLotteryFlag($this->batch_id, $personId, 86400);
            } else { //已经抽奖过了，不能再次参与
                FilterDuplicateRequestService::delDrawLotteryProcessingFlag($this->batch_id, $personId);
                $this->ajaxReturn('error', '抱歉，您已经参与过该抽奖，不能再次参与(┬＿┬) ', 0);
            }
        }

        $choujiang = new ChouJiang($this->id, $mobile, $this->full_id, '', $other);
        $resp      = $choujiang->send_code();
        $personId = $this->getPersonId();
        FilterDuplicateRequestService::delDrawLotteryProcessingFlag($this->batch_id, $personId);
        log_write('接口返回信息:' . json_encode($resp));
        // 中奖提示
        $cjrule   = M('tcj_rule');
        $map1     = ['node_id' => $this->node_id, 'batch_id' => $this->batch_id, 'batch_type' => $this->batch_type];
        $ruleInfo = $cjrule->where($map1)->find();

        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            $this->paysend_cjafter($resp);
            $cj_msg = $ruleInfo["cj_resp_text"] == "" ? "恭喜你，中奖了！！！" : $ruleInfo["cj_resp_text"];

            // 奖品类别
            $cjCateInfo = M()->table('tcj_batch b')->join('tcj_cate c ON b.cj_cate_id=c.id')->join(
                    'tbatch_info d on d.id=b.b_id'
            )->field(
                    'c.id,d.goods_id,d.batch_class,d.batch_type,d.batch_amt,d.batch_short_name'
            )->where("b.id={$resp['rule_id']}")->find();
            log_write('中奖后的查询语句:' . M()->_sql() . ',结果:' . json_encode($cjCateInfo));

            if ($cjCateInfo['batch_class'] == '12') { // 奖品为定额红包
                $bonus_id  = M('tgoods_info')->where(['goods_id' => $cjCateInfo['goods_id']])->getField('bonus_id');
                $bonusInfo = M('tbonus_info')->where(['id' => $bonus_id])->find();
                if ($bonusInfo['link_flag'] == '1') {
                    $cj_msg = $cj_msg . "<br/>" . "奖品名称:" . $bonusInfo['bonus_page_name'];
                }
            }
            $cjCateId = $cjCateInfo['id'];

            $respData = ['data' => $cjCateId, 'info' => $cj_msg, 'status' => 1];
            // 定额红包返回值加上跳转链接和按钮名称
            if ($cjCateInfo['batch_class'] == '12' && $bonusInfo['link_flag'] == '1') {
                $respData['link_url']    = $bonusInfo['link_url'];
                $respData['button_name'] = $bonusInfo['button_name'];
            }

            // 如果奖品为积分
            if ($cjCateInfo['batch_class'] == '14') {
                $respData['is_jf']     = 1;
                $respData['jf_name']   = $cjCateInfo['batch_short_name'];
                $respData['batch_amt'] = intval($cjCateInfo['batch_amt']);
                $respData['link_url']  = U('Label/Member/index', ['node_id' => $this->node_id]);
                $memberPhone           = $resp['member_phone'];
                if (!empty($memberPhone)) {
                    $memberPhone = str_pad($memberPhone, 11, '0');
                    $memberPhone = substr($memberPhone, 0, 3) . '****' . substr($memberPhone, 7);
                }
                $respData['member_phone'] = $memberPhone;
            } else if ($cjCateInfo['batch_class'] == '15') {//流量包
                $respData['batch_class']  = '15';
                $respData['batch_name']   = $cjCateInfo['batch_short_name'];
                $respData['member_phone'] = $resp['member_phone'];
            }

            // 微信红包中奖提示
            if ($cjCateInfo['batch_type'] == '6' || $cjCateInfo['batch_type'] == '7') {
                $respData['info'] = "您获得了{$cjCateInfo['batch_amt']}元微信红包,请到您的微信查看领取。";
            }

            // 如果有特殊的流程，记录一下session，保留中奖结果,以便用户再次提交手机号

            if (!empty($resp['card_ext'])) {// 中了微信卡券
                log_write(print_r($resp, true));
                $respData['card_ext'] = $resp['card_ext'];
                $respData['card_id']  = $resp['card_id'];
            } elseif (!empty($resp['request_id'])) {// 中了手机凭证奖品
                log_write(print_r($resp, true));
                // 临时中奖码，需要响应给前台，校验一下正确性用的，校验正确后失效,以免直接暴露 request_id和
                // cj_trace_id,用完以后清空
                $cj_code = time() . mt_rand(100, 999);
                $cjCodeData = [
                'cj_code'     => $cj_code,
                                'request_id'  => $resp['request_id'],
                                'cj_trace_id' => $resp['cj_trace_id'],
                                'card_ext'    => $resp['card_ext'],
                                'card_id'     => $resp['card_id']
                        ];
                session('_TmpChouJian_',$cjCodeData);
                import('@.Vendor.RedisHelper');
                RedisHelper::getInstance()->set('tmpDrawLottery:cjCode:'.$personId, $cjCodeData);
                $respData['cj_code'] = $cj_code;
            } elseif (!empty($resp['bonus_use_detail_id'])) {
                $respData['bonus_use_detail_id'] = $resp['bonus_use_detail_id'];
            } else if (!empty($resp['prize_type']) && $resp['prize_type'] == '4' && isset($resp['integral_get_flag']) && $resp['integral_get_flag'] == 0) {
                // 如果是积分奖品,并且需要绑定手机
                // 临时中奖码，需要响应给前台，校验一下正确性用的，校验正确后失效,以免直接暴露 request_id和
                // cj_trace_id,用完以后清空
                if (!isset($resp['integral_get_id'])) {
                    log_write('获取integral_get_id失败:' . print_r($resp, true));
                }
                $cj_code = time() . mt_rand(100, 999);
                $cjCodeData = [
                        'cj_code'         => $cj_code,
                        'request_id'      => $resp['request_id'],
                        'cj_trace_id'     => $resp['cj_trace_id'],
                        'card_ext'        => $resp['card_ext'],
                        'card_id'         => $resp['card_id'],
                        'prize_type'      => $resp['prize_type'],
                        'integral_get_id' => $resp['integral_get_id']
                ];
                session('_TmpChouJian_', $cjCodeData);
                import('@.Vendor.RedisHelper');
                RedisHelper::getInstance()->set('tmpDrawLottery:cjCode:'.$personId, $cjCodeData);

                $respData['cj_code'] = $cj_code;
            }
            //暂时屏蔽用户（使其单位时间内不能提交） start 微信抽奖只能中奖一次 所以 中奖之后 直接设置为 不可以在次抽奖
            $expire = 2592000;
            FilterDuplicateRequestService::setIgnoreFlag($this->batch_id, 1, $expire); //一个月 不能再抽取
            //暂时屏蔽用户（使其单位时间内不能提交） end
            $this->ajaxReturn($respData);
        } else {
            // 未中奖提示文字
            $needTmpIgnore = false;
            $expire        = 300;
            $noAwardNotice = explode('|', $ruleInfo['no_award_notice']);
            $respInfo = $noAwardNotice[array_rand($noAwardNotice)] or $respInfo = '很遗憾，未中奖,感谢您的参与！';

            if ($resp['resp_id'] == '1005') {
                if ($this->batch_id == '42038') { // 广西石油非标，微信抽奖只有1次机会，不应该提示“今天”
                    $respInfo      = '您已经参与过抽奖，不能再抽了！';
                    $needTmpIgnore = true;
                    $expire        = 2592000;
                } else {
                    $respInfo      = '今天您已经参与过抽奖，不能再抽了！';
                    $needTmpIgnore = true;
                    $expire        = 2592000;
                }
            } elseif ($resp['resp_id'] == '1016') {
                $respInfo      = '您已经参与过该抽奖活动，不能再抽了！';
                $needTmpIgnore = true;
                $expire        = 2592000;
            }
            // 唐山非标
            if ($this->node_id == C("tangshan.node_id")) {
                $tangshan_data = ['m_id' => $this->batch_id, 'node_id' => $this->node_id];
                $res_tangshan  = M("tfb_tangshan_pingan")->where($tangshan_data)->find();
                if ($res_tangshan['tangshan_url']) {
                    $tangshan_arr = ['info' => $respInfo, 'tangshan_url' => $res_tangshan['tangshan_url']];
                    $this->ajaxReturn(10, $tangshan_arr, 2);
                }
            }
            //暂时屏蔽用户（使其单位时间内不能提交） start
            if ($needTmpIgnore) {
                FilterDuplicateRequestService::setIgnoreFlag($this->batch_id, 1, $expire);
            }
            //暂时屏蔽用户（使其单位时间内不能提交） end
            $this->ajaxReturn(10, $respInfo, 2);
        }
    }

    // 短链接
    public function _shortUrl($long_url)
    {
        $md5 = md5($long_url);
        $key = 'return.share.' . $md5;
        $url = S($key);
        if ($url != '' && $url != null) {
            return $url;
        }

        $apiUrl  = C('ISS_SERV_FOR_IMAGECO');
        $req_arr = [
                'CreateShortUrlReq' => [
                        'SystemID'      => C('ISS_SYSTEM_ID'),
                        'TransactionID' => time() . rand(10000, 99999),
                        'OriginUrl'     => "<![CDATA[$long_url]]>"
                ]
        ];

        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml        = new Xml();
        $str        = $xml->getXMLFromArray($req_arr, 'gbk');
        $error      = '';
        $result_str = httpPost($apiUrl, $str, $error);
        if ($error) {
            echo $error;
            return '';
        }

        $arr = $xml->parse($result_str);
        $arr = $xml->getArrayNoRoot();

        if ($arr['Status']['StatusCode'] == '0000') {
            S($key, $arr['ShortUrl']);
        }

        return $arr['Status']['StatusCode'] == '0000' ? $arr['ShortUrl'] : '';
    }

    // 根据抽奖码发送奖品(手机号)
    public function getPrize()
    {
        $cj_code    = I('cj_code');
        $phone      = I('phone');
        $activityID = I('id');
        if (!$phone) {
            $this->error("手机号不能为空");
        }
        if (!$cj_code) {
            $this->error("系统正忙");
        }
        $personId = $this->getPersonId();
        // 校验一下中奖码是否正确
        $tmpCjData = session('_TmpChouJian_');
        if (empty($tmpCjData)) {
            import('@.Vendor.RedisHelper');
            $tmpCjData = RedisHelper::getInstance()->get('tmpDrawLottery:cjCode:'.$personId);
            RedisHelper::getInstance()->del('tmpDrawLottery:cjCode:'.$personId);
        }
        log_write('开始领奖:' . $cj_code . ', phone_no:' . $phone . ',' . print_r($tmpCjData, true));
        if (!$tmpCjData || $tmpCjData['cj_code'] != $cj_code) {
            log_write('领奖失败：' . $cj_code . ' ' . print_r($tmpCjData, true));
            $this->error("领奖失败，中奖码无效");
        }
        $cj_trace_id = $tmpCjData['cj_trace_id'];
        $request_id  = $tmpCjData['request_id'];
        // 修改数据库中的手机号字段，并且调用重发接口
        $result = M('tcj_trace')->where(['id' => $cj_trace_id])->save(['send_mobile' => $phone]);
        //        file_debug($result, '$result', 'cj.log');
        if ($result) {
            if (empty($this->SendAwardTraceModel)) {
                $this->SendAwardTraceModel = D('SendAwardTrace');
            }
            $sendAwardTrace = $this->SendAwardTraceModel->getByRequestId($request_id);
            //            file_debug($sendAwardTrace, '$sendAwardTrace', 'cj.log');
            if (isset($tmpCjData['goods_info']['goods_type']) && $tmpCjData['goods_info']['goods_type'] == '8') {
                $result = $this->SendAwardTraceModel->updatePhonenoAndStatus(
                        ['deal_flag' => 1, 'phone_no' => $phone],
                        ['request_id' => $request_id]
                );
                if ($result) {
                    $this->success("领奖成功");
                } else {
                    $this->success("领奖失败1");
                }
            } else if ($sendAwardTrace['trans_type'] == 3 || $sendAwardTrace['trans_type'] == 4) { //3:流量 4：话费
                $result = $this->SendAwardTraceModel->updatePhonenoAndStatus(
                        ['deal_flag' => 1, 'phone_no' => $phone],
                        ['request_id' => $request_id]
                );
                if ($result) {
                    $this->success("领奖成功");
                } else {
                    $this->success("领奖失败2");
                }

            } else {
                // 修改发码表的字段
                M('tbarcode_trace')->where(['request_id' => $request_id])->save(['phone_no' => $phone]);
                // 如果是积分奖品(有这个值,也说明该微信号之前没绑定过手机号)
                if ($tmpCjData['prize_type'] == '4') {
                    session('_TmpChouJian_', null);
                    log_write("领奖成功:" . json_encode($tmpCjData));
                    $memberModel = D('MemberInstall', 'Model');
                    $result      = $memberModel->receiveIntegal(
                            $this->node_id,
                            $tmpCjData['integral_get_id'],
                            $phone
                    ); // 返回true或者false
                    log_write("调用会员接口后的返回结果" . json_encode($result));
                    $this->success('领奖成功'); // 返回跳个人中心
                }

                // 然后调用重发接口
                import("@.Vendor.CjInterface");
                $req    = new CjInterface();
                $result = $req->cj_resend(
                        ['request_id' => $request_id, 'node_id' => $this->node_id, 'user_id' => '00000000']
                );
                if (!$result || $result['resp_id'] != '0000') {

                    if ($this->node_id == C("tangshan.node_id")) {// 唐山平安非标
                        $tangshan_data = array(
                                "m_id"    => $this->batch_id,
                                "node_id" => $this->node_id
                        );
                        $res_tangshan  = M("tfb_tangshan_pingan")->where($tangshan_data)->find();
                        if ($res_tangshan['tangshan_url']) {
                            $resptangshan = array(
                                    "tangshan_url"  => $res_tangshan['tangshan_url'],
                                    "tangshan_flag" => 1,
                                    "status"        => 0,
                                    'info'          => "领奖失败"
                            );
                            $this->error($resptangshan);
                        }
                    }

                    //todo  临时处理，深圳平安未充值无法下发，对于微信参与无法领取奖品，直接返回成功
                    if ($this->node_id == C('szpa.node_id')) {
                        $this->success('领奖成功');
                        exit;
                    }

                    $this->error("领奖失败");
                } else if ($activityID == C('VCARD_ACTIVITY_NUMBER')) {
                    M('tvisiting_card')->where(['phone_no'])->save(['is_win_prize' => 1]);
                }

                // 清除中奖码,以免重复提交
                session('_TmpChouJian_', null);
                log_write("领奖成功");
                // 唐山平安非标
                if ($this->node_id == C("tangshan.node_id")) {
                    $tangshan_data = ['m_id' => $this->batch_id, 'node_id' => $this->node_id];
                    $res_tangshan  = M("tfb_tangshan_pingan")->where($tangshan_data)->find();
                    if ($res_tangshan['tangshan_url']) {
                        $resptangshan = [
                                'tangshan_url'  => $res_tangshan['tangshan_url'],
                                'tangshan_flag' => 1,
                                'status'        => 1,
                                'info'          => "领奖成功"
                        ];
                        $this->success($resptangshan);
                    }
                }
            }
            $this->success("领奖成功");
        }
    }

    // 获取红包
    public function getBonus()
    {
        $bonus_use_detail_id = I('bonus_use_detail_id');
        $phone               = I('phone');
        if (!$phone) {
            $this->error("手机号不能为空");
        }
        if (!$bonus_use_detail_id) {
            $this->error("系统正忙");
        }
        $result = D('MemberInstall', 'Model')->receiveBonus($this->node_id, $bonus_use_detail_id, $phone);

        if ($result === false) {
            $this->error('领取失败');
        } else {
            $this->success("领奖成功");
        }
    }

    /**
     * 翼蕙宝会员注册
     */
    public function yhbRegister()
    {
        $phone  = I('phone', null);
        $verify = I('verify', null);
        $email  = I('email', null);
        $code   = session('checkCode');
        if ($verify != $code['number'] || empty($code['number'])) {
            $return = ['status' => 0, 'info' => '验证码不正确'];
            $this->ajaxReturn($return);
            exit();
        }
        $error = null;

        if (!check_str(
                $phone,
                array(
                        'strtype' => "mobile"
                ),
                $error
        )
        ) {
            $msg    = "手机号码" . $error;
            $return = ['status' => 0, 'info' => $msg];
            $this->ajaxReturn($return);
            exit();
        }

        session('checkCode', 'null');

        $where['mobile'] = $phone;
        $model           = M('tfb_yhb_member');
        $check_data      = $model->where($where)->find();
        if (!empty($check_data)) {
            $return = ['status' => 0, 'info' => "该手机号已被注册"];
            $this->ajaxReturn($return);
            exit();
        }

        $user_info = $this->checkYhbMember();
        if ($user_info['is_member']) {
            $return = [
                    'status' => 0,
                    'info'   => "该用户已绑定手机"
            ];
            $this->ajaxReturn($return);
            exit();
        }
        $data['openid']       = $user_info['info']['openid'];
        $data['mobile']       = $phone;
        $data['related_time'] = date('YmdHis');
        $res                  = $model->add($data);
        $return               = ['status' => 1, 'info' => "注册成功"];
        if ($res == false) {
            $return = ['status' => 0, 'info' => "注册会员失败，请重新注册"];
        }
        $this->ajaxReturn($return);
        exit();
    }

    //厦门银行非标start
    /**
     * 厦门银行非标
     * 验证手机验证码和手机号是否在名单里面
     */
    public function validatePhone()
    {
        $phone  = I('phone', null);
        $verify = I('verify', null);
        //测试压力用 start
        $ignoreCode = I('ignore_code');
        if ($ignoreCode != 1) {
            $code = session('checkCode');
            if ($verify != $code['number'] || empty($code['number'])) {
                $return = ['status' => 0, 'info' => '验证码不正确'];
                $this->ajaxReturn($return);
                exit();
            }
        }
        //测试压力用 end

        $error = null;
        //测试压力用 start
        if (!$ignoreCode) {
            //测试压力用 end
            if (!check_str($phone, ['strtype' => 'mobile'], $error)) {
                $msg    = "手机号码" . $error;
                $return = ['status' => 0, 'info' => $msg];
                $this->ajaxReturn($return);
                exit();
            }
            // 测试压力用 start
        }
        // 测试压力用 end
        //厦门银行中奖名单
        $xmyhZjmd = D('XmyhZjmd');
        log_write('电话：' . $phone);
        //是否在中奖名单里面
        $onList = $xmyhZjmd->isOnList($phone, $this->marketInfo['id']);
        if (!$onList) {
            $return = ['status' => 0, 'info' => '未达到参与标准'];
            $this->ajaxReturn($return);
        } elseif ($onList['joined']) {
            $this->ajaxReturn(array('status' => 0, 'info' => '此号码已参与'));
        }
        session('checkCode', null);
        //抽奖步骤
        $this->xmyhCj();

        exit();
    }

    //厦门银行非标end

    public function xmyhCj()
    {
        $this->checkLogin(true);
        $islogin = $this->isLogined();

        $overdue = $this->checkDate();
        if ($overdue === false) {
            $respData = array(
                    'info'   => '该活动不在有效期之内',
                    'status' => 0
            );
            $this->ajaxReturn($respData);
        }
        $mobile = I('phone', null);

        // 过滤重复请求 start
        $mobile = is_null($mobile) ? 0 : $mobile;
        $prarms = [
                'mobile'   => $mobile,
                'node_id'  => $this->node_id,
                'id'       => $this->id,
                'batch_id' => $this->batch_id
        ];
        $this->filterDuplicateRequest($prarms);
        // 过滤重复请求 end

        // 如果是微信抽奖，进入微信抽奖分支
        //$this->joinMode = 1;

        import('@.Service.FilterDuplicateRequestService');
        log_write("开按微信登录抽奖");
        import('@.Vendor.ChouJiang');
        $ignoreCode = I('ignore_code');
        if (!$ignoreCode) {
            $wxUserInfo = $this->getWxUserInfo();
            if (!$wxUserInfo) {
                $this->error("请从微信登录");
            }
        }
        // 如果是手机号登录
        if (!$islogin) {
            $this->error('请登录');
        }
        $xmyhZjmd    = D('XmyhZjmd');
        $prizeCateId = $xmyhZjmd->getPrizeCateIdByPhone($mobile, $this->batch_id);

        $other = [
                'wx_cjcate_id' => $prizeCateId,
                'wx_open_id'   => $wxUserInfo['openid'],
                'wx_nick'      => $wxUserInfo['nickname']
        ];
        // for 中奖记录 start
        if (empty($this->DrawLotteryCommonService)) {
            $this->DrawLotteryCommonService = D('DrawLotteryCommon', 'Service');
        }
        $this->DrawLotteryCommonService->setMobileAndGobackUrl($this->id, $wxUserInfo, null);
        // for 中奖记录 end

        import('@.Vendor.ChouJiang');
        $choujiang = new ChouJiang(
                $this->id, $ignoreCode ? $mobile : $wxUserInfo['openid'], $this->full_id, null, $other
        );
        // 中奖提示
        $cjrule   = M('tcj_rule');
        $map1     = array(
                'node_id'    => $this->node_id,
                'batch_id'   => $this->batch_id,
                'batch_type' => $this->batch_type
        );
        $ruleInfo = $cjrule->where($map1)->find();
        $resp     = $choujiang->send_code();
        $xmyhZjmd->setJoined($mobile, $this->batch_id);
        log_write(
                '厦门银行抽奖日志：' . __METHOD__ . ',手机：' . $mobile . ',微信号：' . $wxUserInfo['openid'] . ',时间：' . date(
                        'Y-m-d H:i:s',
                        time()
                ) . ',$resp:' . var_export($resp, 1)
        );
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            $cj_msg = $ruleInfo["cj_resp_text"] == "" ? "恭喜你，中奖了！！！" : $ruleInfo["cj_resp_text"];

            // 奖品类别
            $cjCateInfo = M()->table('tcj_batch b')->join('tcj_cate c ON b.cj_cate_id=c.id')->join(
                    'tbatch_info d on d.id=b.b_id'
            )->field('c.id,d.goods_id,d.batch_class,d.batch_type,d.batch_amt,d.batch_short_name')->where(
                    "b.id={$resp['rule_id']}"
            )->find();
            log_write('中奖后的查询语句:' . M()->_sql() . ',结果:' . json_encode($cjCateInfo));
            $cjCateId = $cjCateInfo['id'];
            if (!$cjCateId) {
                log_write(M()->_sql());
            }

            $respData = ['data' => $cjCateId, 'info' => $cj_msg, 'status' => 1];
            // 微信红包中奖提示
            if ($cjCateInfo['batch_type'] == '6' || $cjCateInfo['batch_type'] == '7') {
                $respData['info'] = "您获得了{$cjCateInfo['batch_amt']}元微信红包,请到您的微信查看领取。";
            }

            if (!empty($resp['request_id'])) {
                log_write(print_r($resp, true));
                // 临时中奖码，需要响应给前台，校验一下正确性用的，校验正确后失效,以免直接暴露 request_id和
                // cj_trace_id,用完以后清空
                $cj_code = time() . mt_rand(100, 999);
                session(
                        '_TmpChouJian_',
                        [
                                'cj_code'     => $cj_code,
                                'request_id'  => $resp['request_id'],
                                'cj_trace_id' => $resp['cj_trace_id'],
                                'card_ext'    => $resp['card_ext'],
                                'card_id'     => $resp['card_id']
                        ]
                );
                $respData['cj_code'] = $cj_code;
            }

            $this->ajaxReturn($respData);
        } else {
            $needTmpIgnore = false;
            // 未中奖提示文字
            $noAwardNotice = explode('|', $ruleInfo['no_award_notice']);
            $respInfo = $noAwardNotice[array_rand($noAwardNotice)] or $respInfo = '很遗憾，未中奖,感谢您的参与！';
            if ($resp['resp_id'] == '1005') {
                $needTmpIgnore = true;
                $respInfo      = '今天您已经参与过抽奖，不能再抽了！';
            } elseif ($resp['resp_id'] == '1016') {
                $needTmpIgnore = true;
                $respInfo      = '您已经参与过该抽奖活动，不能再抽了！';
            }

            //暂时屏蔽用户（使其单位时间内不能提交） start
            if ($needTmpIgnore) {
                import('@.Service.FilterDuplicateRequestService');
                FilterDuplicateRequestService::setIgnoreFlag($this->batch_id);
            }
            //暂时屏蔽用户（使其单位时间内不能提交） end

            $this->ajaxReturn(10, $respInfo, 2);
        }
    }
}