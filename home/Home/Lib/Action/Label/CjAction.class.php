<?php
// 抽奖
class CjAction extends MyBaseAction {

    private $doubleFestivalLabelIdList = array();

    /**
     * @var DrawLotteryCommonService
     */
    private $DrawLotteryCommonService;

    public function cjindex() {
        $id = $this->id;
        // 活动
        $batchModel = M('tmarketing_info');
        $row = $batchModel->where(
            array(
                'id' => $this->batch_id))->find();
        // 抽奖配置表
        if ($row['is_cj'] != '1') {
            $this->error('该活动未设置抽奖');
        }
        $model_c = M('tcj_rule');
        $map_c = array(
            'batch_type' => $this->batch_type, 
            'batch_id' => $this->batch_id, 
            'status' => '1');
        $cj_rule_query = $model_c->field(
            'id,total_chance,cj_button_text,cj_check_flag')
            ->where($map_c)
            ->find();
        // 抽奖文字配置
        $cj_text = $cj_rule_query['cj_button_text'];
        
        // 奖品
        $jp_arr = M()->table('tcj_batch a')
            ->field('a.cj_cate_id,b.batch_name')
            ->join('tbatch_info b on a.b_id=b.id')
            ->where("a.batch_id='" . $this->batch_id . "' and a.status='1'")
            ->select();
        if (empty($jp_arr)) {
            $this->error('该活动未设置奖品');
        }
        // 获取奖品中的cate_id
        $cj_cate_ids = array_valtokey($jp_arr, 'cj_cate_id', 'cj_cate_id');
        
        // 分类
        $cj_cate_arr = array();
        if ($cj_rule_query) {
            $cj_cate_arr = M('tcj_cate')->field('id,name')
                ->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_id' => $this->batch_id, 
                    'cj_rule_id' => $cj_rule_query['id'], 
                    'id' => array(
                        'in', 
                        $cj_cate_ids)))
                ->select();
        }
        
        // dump($cj_cate_arr);
        // dump($jp_arr);
        // 处理页面奖项奖项
        $cjCateId = array();
        $cjCateName = '';
        foreach ($cj_cate_arr as $v) {
            $cjCateId[] = $v['id'];
            $cjCateName .= '"' . $v['name'] . '",';
        }

        import('@.Service.FilterDuplicateRequestService');
        $needIgnoreKey = FilterDuplicateRequestService::genereateIgnoreKey($this->batch_id);
        $this->assign('needIgnoreKey', $needIgnoreKey);


        // 判断是否显示参与码
        $cj_check_flag = $cj_rule_query['cj_check_flag'];
        $this->assign('id', $this->id);
        $this->assign('row', $row);
        $this->assign('cj_text', $cj_text);
        $this->assign('cj_check_flag', $cj_check_flag);
        $this->assign('total_chance', $cj_rule_query['total_chance']);
        $this->assign('batch_type', $this->batch_type);
        $this->assign('batch_id', $this->batch_id);
        $this->assign('cj_cate_arr', $cj_cate_arr);
        $this->assign('jp_arr', $jp_arr);
        $this->assign('cjCateId', implode(',', $cjCateId));
        $this->assign('cjCateName', substr($cjCateName, 0, - 1));
        switch ($row['cj_phone_type']) {
            case '2':
                $this->display('lunpan');
                break;
            case '3':
                $this->display('laohuji');
                break;
            case '4':
                $this->display('zhajindan');
                break;
            case '5':
                $this->display('shaking');
                break;
            default:
                $this->error('未知的抽奖类型');
        }
    }

    /**
     *
     * @param $result
     * @param $needReturn
     * @return mixed
     */
    public function normalDrawLotteryReturn($result, $status, $needReturn) {
        if ($needReturn) {
            if (is_array($result)) {
                $result['status'] = $status;
            }
            return $result;
        } else {
            $this->ajaxReturn($result);
            return true;
        }
    }

    /**
     * 抽奖
     *
     * @param bool|false $needReturn
     */
    public function doNormalDrawLotterySubmit($needReturn = false) {

        $mobile = I('post.mobile');

        // 过滤重复请求
        $this->filterDuplicateRequest(array('id' => $this->id, 'mobile' => $mobile));
        
        $id = $this->id;
        $overdue = $this->checkDate();
        $status = 0;
        if ($overdue === false) {
            return $this->normalDrawLotteryReturn(- 1005, $status, $needReturn);
        }
        // 是否抽奖
        $query_arr = $this->marketInfo;
        if ($query_arr['is_cj'] != '1') {
            return $this->normalDrawLotteryReturn(- 1054, $status, $needReturn);
        }
        
        if (! $this->isPost()) {
            return $this->normalDrawLotteryReturn(- 1055, $status, $needReturn);
        }
        
        $pay_token = I('pay_token', null);
        $cj_check_flag = I('post.cj_check_flag');
        $check_code = I('post.check_code');
        
        if (! is_numeric(trim($mobile)) || strlen(trim($mobile)) != '11') {
            return $this->normalDrawLotteryReturn(- 1056, $status, $needReturn);
        }
        // 保存全局手机号到cookie
        $this->_userCookieMobile($mobile);
        
        $needCheckVerify = I('post.needCheckVerify', true);
        // 双旦验证 start
        if (in_array($this->id, $this->doubleFestivalLabelIdList)) {
            $verified = session('doClaimTaskAwardVerified');
            if ($verified != 1) {
                return $this->normalDrawLotteryReturn(- 1055, $status, 
                    $needReturn);
            } else {
                session('doClaimTaskAwardVerified', null);
            }
        }
        // 双旦验证 end
        
        // 二维码名片无需验证码
        if ($id != C('VCARD_ACTIVITY_NUMBER') && $needCheckVerify) {
            if (session('verify_cj') != md5(I('post.verify'))) {
                return $this->normalDrawLotteryReturn(- 1058, $status, 
                    $needReturn);
            }
        }
        session('verify_cj', null);
        if (empty($id)) {
            return $this->normalDrawLotteryReturn(- 1023, $status, $needReturn);
        }
        
        $is_update = false;
        if ($cj_check_flag == '1') {
            if ($check_code == '') {
                return $this->normalDrawLotteryReturn(- 1057, $status, 
                    $needReturn);
            }
            
            // 校验是否存在
            $checkm = M('tcode_verify');
            $cmap = array(
                'batch_id' => $this->batch_id, 
                'batch_type' => $this->batch_type, 
                'status' => '0', 
                'verify_code' => strtolower($check_code));
            $query = $checkm->where($cmap)->find();
            if ($query) {
                if ($query['activity_code_status'] == 1) {
                    return $this->normalDrawLotteryReturn(- 1059, $status, 
                        $needReturn);
                } else if ($query['activity_code_status'] == 2) {
                    return $this->normalDrawLotteryReturn(- 1060, $status, 
                        $needReturn);
                } else {
                    $is_update = true;
                }
            } else {
                return $this->normalDrawLotteryReturn(- 1057, $status, 
                    $needReturn);
            }
        }
        import('@.Vendor.ChouJiang');
        $other = array();
        $shopping_trace = I('shopping_trace', NULL);
        // 如果微信登录过
        // 校验使用的用户
        $this->_checkUser();
        
        $wxUserInfo = $this->getwxUserInfo();
        if ($wxUserInfo) {
            $other = array(
                'wx_open_id' => $wxUserInfo['openid'], 
                'wx_nick' => $wxUserInfo['nickname']);
        }
        // 补充付满送的pay_token字段
        if ($pay_token != '') {
            $other = array_merge($other, 
                array(
                    'pay_token' => $pay_token));
        }
        $choujiang = new ChouJiang($id, $mobile, $this->full_id, $shopping_trace, 
            $other);
        $resp = $choujiang->send_code();
        session('input_mobile', $mobile);
        log_write('抽奖返回值:' . print_r($resp, true));
        if ($is_update === true) {
            $checkm->where(array(
                'id' => $query['id']))->save(
                array(
                    'status' => '1', 
                    'use_time' => date('YmdHis'), 
                    'activity_code_status' => '1'));
        }
        $map1 = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'batch_type' => $this->batch_type);
        $ruleInfo = M('tcj_rule')->where($map1)->find();
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            // 中奖提示
            $cj_msg = $ruleInfo["cj_resp_text"] == "" ? "恭喜你，中奖了！！！" : $ruleInfo["cj_resp_text"];
            
            // 奖品类别
            $cjCateInfo = M()->table('tcj_batch b')
                ->join('tcj_cate c ON b.cj_cate_id=c.id')
                ->join('tbatch_info d on d.id=b.b_id')
                ->field('c.id,d.goods_id,d.batch_class')
                ->where("b.id={$resp['rule_id']}")
                ->find();
            // 奖品为定额红包
            if ($cjCateInfo['batch_class'] == '12') {
                $bonus_id = M('tgoods_info')->where(
                    array(
                        'goods_id' => $cjCateInfo['goods_id']))->getField(
                    'bonus_id');
                $bonusInfo = M('tbonus_info')->where(
                    array(
                        'id' => $bonus_id))->find();
                if ($bonusInfo['link_flag'] == '1')
                    $cj_msg = $cj_msg . "<br/>" . "奖品名称:" .
                         $bonusInfo['bonus_page_name'];
            }
            $cjCateId = $cjCateInfo['id'];
            $respData = array(
                'data' => array(
                    'cj_cate_id' => $cjCateId, 
                    'resp' => $resp), 
                'info' => $cj_msg, 
                'status' => 1);
            
            // 定额红包返回值加上跳转链接和按钮名称
            if ($cjCateInfo['batch_class'] == '12' &&
                 $bonusInfo['link_flag'] == '1') {
                $respData['link_url'] = $bonusInfo['link_url'];
                $respData['button_name'] = $bonusInfo['button_name'];
            }
            
            // 中了微信卡券
            if (! empty($resp['card_ext']) || ! empty($resp['card_id'])) {
                log_write(print_r($resp, true));
                $respData['card_ext'] = $resp['card_ext'];
                $respData['card_id'] = $resp['card_id'];
            } elseif (! empty($resp['request_id'])) 
            // || !empty($resp['cj_trace_id'])
            { // 中了手机凭证奖品
                log_write(print_r($resp, true));
                // 临时中奖码，需要响应给前台，校验一下正确性用的，校验正确后失效,以免直接暴露 request_id和
                // cj_trace_id,用完以后清空
                $cj_code = time() . mt_rand(100, 999);
                import('@.Vendor.RedisHelper');
                $personId = $this->getPersonId();
                $cjCodeData = array(
                        'cj_code' => $cj_code,
                        'request_id' => $resp['request_id'],
                        'cj_trace_id' => $resp['cj_trace_id'],
                        'card_ext' => $resp['card_ext'],
                        'card_id' => $resp['card_id']);
                session('_TmpChouJian_', $cjCodeData);
                RedisHelper::getInstance()->set('tmpDrawLottery:cjCode:'.$personId, $cjCodeData);
                $respData['cj_code'] = $cj_code;
            }
            return $this->normalDrawLotteryReturn($respData, 1, $needReturn);
        } else {
            // 二维码名片修改中奖状态
            if ($id == C('VCARD_ACTIVITY_NUMBER')) {
                M('tvisiting_card')->where(
                    array(
                        'phone_no' => $mobile))->save(
                    array(
                        'is_win_prize' => 2));
            }
            $status = '0';
            // 未中奖提示文字
            $noAwardNotice = explode('|', $ruleInfo['no_award_notice']);
            $respInfo = $noAwardNotice[array_rand($noAwardNotice)];
            if ($respInfo == '')
                // 奖品粉丝专享错误提示,需要注册
                if ($resp['resp_id'] == '1012') {
                    $status = '3';
                    return $this->normalDrawLotteryReturn(- 1060, $status, 
                        $needReturn);
                }
                // 奖品粉丝专享错误提示
            if ($resp['resp_id'] == '1008') {
                $status = '0';
                return $this->normalDrawLotteryReturn(- 1060, $status, 
                    $needReturn);
            }
            // 付满送只能参加一次错误提示
            if ($resp['resp_id'] == '1053') {
                $status = '0';
                return $this->normalDrawLotteryReturn(- 1061, $status, 
                    $needReturn);
            }
            
            if ($this->batch_type == '9') { // 优惠券中奖处理
                if ($resp['resp_id'] == '1000' || $resp['resp_id'] == '1005' ||
                     $resp['resp_id'] == '1016') {
                    return $this->normalDrawLotteryReturn(- 1062, $status, 
                        $needReturn);
                } elseif ($resp['resp_id'] == '1003' ||
                     $resp['resp_id'] == '1014') {
                    return $this->normalDrawLotteryReturn(- 1063, $status, 
                        $needReturn);
                }
            } elseif ($resp['resp_id'] == '1005') {
                return $this->normalDrawLotteryReturn(- 1064, $status, 
                    $needReturn);
            } elseif ($resp['resp_id'] == '1016') {
                return $this->normalDrawLotteryReturn(- 1065, $status, 
                    $needReturn);
            } else {
                if ($this->batch_type == '3' &&
                     in_array($this->node_id, C('DM_Haagen_Dazs'))) {
                    if ($resp['resp_id'] == '1044' || $resp['resp_id'] == '1049') { // todo
                                                                                    // 还需要处理
                        $t = intval(date('H') / 2);
                        $str = ($t * 2) . ":00~" . (($t + 1) * 2) . ":00";
                        $respInfo = "亲，很遗憾，{$str}的中奖名额已满，您未能获得奖品，非常感谢您的热心参与。";
                        return $this->normalDrawLotteryReturn(- 1065, $status, 
                            $needReturn);
                    }
                    if ($resp['resp_id'] == '1046') {
                        return $this->normalDrawLotteryReturn(- 1066, $status, 
                            $needReturn);
                    }
                } else {
                    $mark = 3;
                }
            }
            return $this->normalDrawLotteryReturn(- 1067, $status, $needReturn);
        }
    }

    /*
     * 抽奖-普通-手机号参与
     * 非全民营销的活动的抽奖
     */
    public function submit() {
        // 过滤重复请求 start
        $mobile = I('post.mobile');
        $personId = $this->getPersonId();
        $params = array(
                'personId' => $personId,
                'node_id' => $this->node_id,
                'batch_id' => $this->batch_id,
        );
        $this->filterDuplicateRequest($params);
        // 过滤重复请求 end

        $id = $this->id;
        $overdue = $this->checkDate();
        if ($overdue === false) {
            $this->ajaxReturn("error", "该活动不在有效期之内！", 0);
        }
        // 是否抽奖
        $query_arr = $this->marketInfo;
        if ($query_arr['is_cj'] != '1')
            $this->ajaxReturn("error", "未查询到抽奖活动！", 0);
        
        if (! $this->isPost()) {
            $this->ajaxReturn("error", "非法提交！", 0);
        }
        
        $pay_token = I('pay_token', null);
        $cj_check_flag = I('post.cj_check_flag');
        $check_code = I('post.check_code');
        $batch_id = I("post.batch_id");
        // echo $batch_id;
        if (! is_numeric(trim($mobile)) || strlen(trim($mobile)) != '11') {
            $this->ajaxReturn("error", "请正确填写手机号！", 0);
        }
        // 保存全局手机号到cookie
        $this->_userCookieMobile($mobile);

        $ignoreCode = I('post.ignore_code', 0);
        // 二维码名片无需验证码
        if ($id != C('VCARD_ACTIVITY_NUMBER') &&
             $batch_id != C('shandong.batch_id') && $ignoreCode != '1') {
            if (session('verify_cj') != md5(I('post.verify'))) {
                $this->ajaxReturn("error", "验证码错误！", 0);
            }
        }
        session('verify_cj', null);
        if (empty($id)) {
            $this->ajaxReturn("error", "错误的请求！", 0);
        }
        // 福满送特别处理分享领奖人数检查
        $this->paysend_before();
        // 可口可乐非标
        $shopping_trace = I('shopping_trace', NULL);
        if (C('GWYL_NODE') == $this->node_id && $this->batch_type == '3' &&
             ! empty($shopping_trace)) {
            // 校验黑名单
            $fb_wh = array(
                'node_id' => $this->node_id, 
                'batch_id' => $this->batch_id, 
                'mobile' => $mobile, 
                'type' => '2', 
                'status' => '1');
            $isexits = M('tfb_phone')->where($fb_wh)->find();
            if ($isexits)
                $this->ajaxReturn('error', "该手机号不允许参与抽奖！", 0);
        }
        
        $is_update = false;
        if ($cj_check_flag == '1') {
            if ($check_code == '') {
                $this->ajaxReturn("error", "错误的参与码！", 0);
            }
            
            // 校验是否存在
            $checkm = M('tcode_verify');
            $cmap = array(
                'batch_id' => $this->batch_id,
                'batch_type' => $this->batch_type,
                'status' => '0',
                'verify_code' => strtolower($check_code));
            $query = $checkm->where($cmap)->find();
            if ($query) {
                if ($query['activity_code_status'] == 1) {
                    $this->ajaxReturn("error", "该参与码已使用！", 0);
                } else if ($query['activity_code_status'] == 2) {
                    $this->ajaxReturn("error", "该参与码已作废！", 0);
                } else {
                    $is_update = true;
                }
            } else {
                $this->ajaxReturn("error", "错误的参与码！", 0);
            }
        }
        import('@.Vendor.ChouJiang');
        $other = array();
        // 如果微信登录过
        // 校验使用的用户
        $this->_checkUser();
        
        $wxUserInfo = $this->getWxUserInfo();
        if ($wxUserInfo) {
            $other = array(
                'wx_open_id' => $wxUserInfo['openid'], 
                'wx_nick' => $wxUserInfo['nickname']);
        }
        $salerId = I('saler_id', null);
        if (empty($salerId)) {
            $salerId = cookie('saler_id');
        }
        // 新增抽红包绑定关系功能（旺分销客户关系中查看）
        if (! empty($salerId)) {
            $wfxService = D('Wfx', 'Service');
            $wfxService->bind_customer($this->node_id, $mobile, $salerId, 3);
        }
        // 补充付满送的pay_token字段
        if ($pay_token != '')
            $other = array_merge($other, 
                array(
                    'pay_token' => $pay_token));


        //设置正在处理抽奖标识（和过滤重复请求差不多，如果请求处理时间比较长，就有可能出现问题） start
        import('@.Service.FilterDuplicateRequestService');
        $processingFlag = FilterDuplicateRequestService::getDrawLotteryProcessingFlag($this->batch_id, $personId);
        if ($processingFlag) { //正在处理 不能再次请求抽奖接口
            $this->ajaxReturn('error', '您上次抽奖还正在处理，请耐心等待结果出来之后再进行抽奖^_^', 0);
        }
        FilterDuplicateRequestService::setDrawLotteryProcessingFlag($this->batch_id,$personId, 300);//默认 最长设置为300s 5min 50s还没处理完的话自动释放
        //设置正在处理抽奖标识（和过滤重复请求差不多，如果请求处理时间比较长，就有可能出现问题） end

        $choujiang = new ChouJiang($id, $mobile, $this->full_id, $shopping_trace, $other);

        // for 中奖记录 start
        if (empty($this->DrawLotteryCommonService)) {
            $this->DrawLotteryCommonService = D('DrawLotteryCommon', 'Service');
        }
        $this->DrawLotteryCommonService->setMobileAndGobackUrl($id, $other, $mobile);
        // for 中奖记录 start
        
        $resp = $choujiang->send_code();
        FilterDuplicateRequestService::delDrawLotteryProcessingFlag($this->batch_id, $personId);
        session('input_mobile', $mobile);
        log_write('抽奖返回值:' . print_r($resp, true));

        if ($is_update === true) {
            $checkm->where(array(
                'id' => $query['id']))->save(
                array(
                    'status' => '1', 
                    'use_time' => date('YmdHis'), 
                    'activity_code_status' => '1'));
        }
        $map1 = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'batch_type' => $this->batch_type);
        $ruleInfo = M('tcj_rule')->where($map1)->find();
        if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
            $this->_hbtpy($mobile, $resp);
            if ($id == '12009') {
                $this->ajaxReturn("success", "恭喜您抢到一个名额！", 1);
            } elseif ($this->batch_id == '704') {
                $this->ajaxReturn("success", "恭喜您获得一个15元500M办理名额！", 1);
            } elseif ($this->batch_id == '722') {
                $this->ajaxReturn("success", "您的推荐人信息已提交成功！", 1);
            } else {

                // 中奖提示
                $cj_msg = $ruleInfo["cj_resp_text"] == "" ? "恭喜你，中奖了！！！" : $ruleInfo["cj_resp_text"];
                
                // 山东平安
                if ($this->batch_id == C('shandong.batch_id')) {
                    $this->ajaxReturn('success', $cj_msg, 1);
                    exit();
                }
                // 哈根达斯地图调研，返回奖品名称和图片信息
                if ($this->batch_type == '3' &&
                     in_array($this->node_id, C('DM_Haagen_Dazs'))) {
                    $cj_batch_id = $resp['rule_id'];
                    if ($cj_batch_id != '') {
                        $cj_batch_info = M('tcj_batch')->find($cj_batch_id);
                        $prize_info = M('tbatch_info')->find(
                            $cj_batch_info['b_id']);
                        $data = array(
                            'prize_name' => $prize_info['batch_name'], 
                            'prize_img' => C('UPLOAD') . $prize_info['batch_img']);
                        $this->ajaxReturn($data, $cj_msg, 1);
                        exit();
                    }
                }
                $this->paysend_cjafter($resp);
                // 奖品类别
                // $cjCateId = M()->table('tcj_batch b')->join('tcj_cate c ON
                // b.cj_cate_id=c.id')->where("b.id={$resp['rule_id']}")->getField('c.id');
                $cjCateInfo = M()->table('tcj_batch b')
                    ->join('tcj_cate c ON b.cj_cate_id=c.id')
                    ->join('tbatch_info d on d.id=b.b_id')
                    ->field(
                    'c.id,d.goods_id,d.batch_class,d.batch_amt,d.batch_short_name,d.batch_img')
                    ->where("b.id={$resp['rule_id']}")
                    ->find();
                // 奖品为定额红包
                if ($cjCateInfo['batch_class'] == '12') {
                    $bonus_id = M('tgoods_info')->where(
                        array(
                            'goods_id' => $cjCateInfo['goods_id']))->getField(
                        'bonus_id');
                    $bonusInfo = M('tbonus_info')->where(
                        array(
                            'id' => $bonus_id))->find();
                    if ($bonusInfo['link_flag'] == '1')
                        // $cj_msg = $cj_msg.'<br/><a
                        // href="'.$bonusInfo['link_url'].'">'.$bonusInfo['button_name'].'</a>';
                        $cj_msg = $cj_msg . "<br/>" . "奖品名称:" .
                             $bonusInfo['bonus_page_name'];
                }
                
                $cjCateId = $cjCateInfo['id'];
                $respData = array(
                    'data' => $cjCateId, 
                    'info' => $cj_msg, 
                    'status' => 1);
                
                // 定额红包返回值加上跳转链接和按钮名称
                if ($cjCateInfo['batch_class'] == '12' &&
                     $bonusInfo['link_flag'] == '1') {
                    $respData['link_url'] = $bonusInfo['link_url'];
                    $respData['button_name'] = $bonusInfo['button_name'];
                }
                
                // 如果奖品为积分
                if ($cjCateInfo['batch_class'] == '14') {
                    $respData['is_jf'] = 1;
                    $respData['jf_name'] = $cjCateInfo['batch_short_name'];
                    $respData['batch_amt'] = intval($cjCateInfo['batch_amt']);
                    $respData['link_url'] = U('Label/Member/index', 
                        array(
                            'node_id' => $this->node_id));
                }
                log_write('流量包判断前：$respData=' . var_export($respData, true) . '----$cjCateInfo=' . var_export($cjCateInfo, true));
                //如果奖品是流量包
                if ($cjCateInfo['batch_class'] == '15') {
                    $respData['is_llb'] = '1';
                    $respData['info'] = $cjCateInfo['batch_short_name'] . '奖品将在3日之内到账，请您注意查收！';
                    $respData['batch_img'] = get_upload_url($cjCateInfo['batch_img']);
                    $respData['batch_short_name'] = $cjCateInfo['batch_short_name'];
                }
                
                // 唐山平安非标
                if ($this->node_id == C("tangshan.node_id")) {
                    $tangshan_data = array(
                        "m_id" => $query_arr['id'], 
                        "node_id" => $this->node_id);
                    $res_tangshan = M("tfb_tangshan_pingan")->where(
                        $tangshan_data)->find();
                    if ($res_tangshan['tangshan_url']) {
                        $respData = array(
                            'data' => $cjCateId, 
                            'info' => $cj_msg, 
                            "tangshan_url" => $res_tangshan['tangshan_url'], 
                            "tangshan_flag" => 1, 
                            'status' => 1);
                    }
                }
                // 如果有特殊的流程，记录一下session，保留中奖结果,以便用户再次提交手机号
                // 中了微信卡券
                if (! empty($resp['card_ext']) || ! empty($resp['card_id'])) {
                    log_write(print_r($resp, true));
                    $respData['card_ext'] = $resp['card_ext'];
                    $respData['card_id'] = $resp['card_id'];
                } // 中了手机凭证奖品
elseif (! empty($resp['request_id'])) 
                // || !empty($resp['cj_trace_id'])
                {
                    log_write(print_r($resp, true));
                    // 临时中奖码，需要响应给前台，校验一下正确性用的，校验正确后失效,以免直接暴露 request_id和
                    // cj_trace_id,用完以后清空
                    $cj_code = time() . mt_rand(100, 999);
                    import('@.Vendor.RedisHelper');
                    $personId = $this->getPersonId();
                    $cjCodeData = array(
                            'cj_code' => $cj_code,
                            'request_id' => $resp['request_id'],
                            'cj_trace_id' => $resp['cj_trace_id'],
                            'card_ext' => $resp['card_ext'],
                            'card_id' => $resp['card_id']);
                    session('_TmpChouJian_', $cjCodeData );
                    RedisHelper::getInstance()->set('tmpDrawLottery:cjCode:'.$personId, $cjCodeData);
                    $respData['cj_code'] = $cj_code;
                }
                log_write(print_r($respData, true));
                $this->ajaxReturn($respData);
            }
        } else {
            // 二维码名片修改中奖状态
            if ($id == C('VCARD_ACTIVITY_NUMBER')) {
                M('tvisiting_card')->where(
                    array(
                        'phone_no' => $mobile))->save(
                    array(
                        'is_win_prize' => 2));
            }
            $mark = 'error';
            $status = '0';
            // 未中奖提示文字
            $noAwardNotice = explode('|', $ruleInfo['no_award_notice']);
            $respInfo = $noAwardNotice[array_rand($noAwardNotice)];
            $needTmpIgnore = false;
            if ($respInfo == '')
                $respInfo = $resp['resp_desc'];
                // 奖品粉丝专享错误提示,需要注册
            if ($resp['resp_id'] == '1011') {
                $respInfo = "该活动只有该商户粉丝才能参与";
                $status = '3';
                $this->ajaxReturn($mark, $respInfo, $status);
            }
            // 奖品粉丝专享错误提示
            if ($resp['resp_id'] == '1008') {
                $respInfo = "该活动只有该商户粉丝才能参与";
                $status = '0';
                $this->ajaxReturn($mark, $respInfo, $status);
            }
            // 付满送只能参加一次错误提示
            if ($resp['resp_id'] == '1053') {
                $respInfo = "您已参与过本次活动";
                $status = '0';
                $this->ajaxReturn($mark, $respInfo, $status);
            }
            if ($id == '12009') {
                $this->ajaxReturn("error", "很遗憾，今天的100个名额已满！", 0);
            } elseif ($this->batch_id == '700') {
                $this->ajaxReturn("success", 
                    "恭喜您获得图文奥特莱斯网上商城价值25元的电子现金券1张，使用详情请登录<a href='http://oao2o.com/'>http://oao2o.com/</a>咨询！", 
                    1);
            } elseif ($this->batch_id == '704') {
                $this->ajaxReturn("error", "很遗憾，您未获得15元500M办理名额！", 0);
            } elseif ($this->batch_id == '722') {
                $this->ajaxReturn("error", "您已提交过一次，无需重复提交！", 0);
            } else {
                if ($this->batch_type == '9') { // 优惠券中奖处理
                    if ($resp['resp_id'] == '1000' || $resp['resp_id'] == '1005' ||
                         $resp['resp_id'] == '1016') {
                        $needTmpIgnore = true;
                        $respInfo = "亲！早起的鸟儿有虫吃~优惠券被抢完了";
                    } elseif ($resp['resp_id'] == '1003' ||
                         $resp['resp_id'] == '1014') {
                        $needTmpIgnore = true;
                        $respInfo = "亲！您已经领过了";
                    }
                } elseif ($resp['resp_id'] == '1005') {
                    $needTmpIgnore = true;
                    $respInfo = '今天您已经参与过抽奖，不能再抽了！';
                } elseif ($resp['resp_id'] == '1016') {
                    $needTmpIgnore = true;
                    $respInfo = '您已经参与过该抽奖活动，不能再抽了！';
                } else {
                    if ($this->batch_type == '3' &&
                         in_array($this->node_id, C('DM_Haagen_Dazs'))) {
                        if ($resp['resp_id'] == '1044' ||
                         $resp['resp_id'] == '1049') {
                        $t = intval(date('H') / 2);
                        $str = ($t * 2) . ":00~" . (($t + 1) * 2) . ":00";
                        $respInfo = "亲，很遗憾，{$str}的中奖名额已满，您未能获得奖品，非常感谢您的热心参与。";
                        $needTmpIgnore = true;
                    }
                    if ($resp['resp_id'] == '1046') {
                        $needTmpIgnore = true;
                        $respInfo = "当天已领完!";
                    }
                } else {
                    $mark = 3;
                }
            }
            // 唐山非标
            if ($this->node_id == C("tangshan.node_id")) {
                $tangshan_data = array(
                    "m_id" => $query_arr['id'], 
                    "node_id" => $this->node_id);
                $res_tangshan = M("tfb_tangshan_pingan")->where($tangshan_data)->find();
                if ($res_tangshan['tangshan_url']) {
                    $tangshan_arr = array(
                        "info" => $respInfo, 
                        "tangshan_url" => $res_tangshan['tangshan_url']);
                    $this->ajaxReturn($mark, $tangshan_arr, $status);
                }
            }
            // 山东平安
            if ($this->batch_id == C('shandong.batch_id')) {
                $this->ajaxReturn('error', $respInfo, 0);
            }
            // 2015元旦临时非标代码，过后删除--start
            if (in_array(intval($this->batch_id), 
                [
                    44836, 
                    45380, 
                    45381, 
                    45383, 
                    45385]) && in_array(intval($resp['resp_id']), 
                [
                    1001, 
                    1002, 
                    1003, 
                    1006])) {
                $respInfo = '奖品已发完';
                log_write('2015元旦临时非标代码:奖品已发完,活动id' . $this->batch_id);
            }
            if (in_array(intval($resp['resp_id']), 
                [
                    1001, 
                    1002, 
                    1003, 
                    1005, 
                    1006, 
                    1014, 
                    1016])) {
                setcookie('tmpcookie' . $this->batch_id, 
                    json_encode(
                        [
                            'id' => $resp['resp_id'], 
                            'info' => $respInfo]), time() + 300);
                log_write(
                    '2015元旦临时非标代码:cookie设置成功' . print_r(
                        [
                            'id' => $resp['resp_id'], 
                            'info' => $respInfo], true));
            }
            // 2015元旦临时非标代码，过后删除--end
            //暂时屏蔽用户（使其单位时间内不能提交） start
            if ($needTmpIgnore) {
                FilterDuplicateRequestService::setIgnoreFlag($this->batch_id);
            }
            //暂时屏蔽用户（使其单位时间内不能提交） end
            $this->ajaxReturn($mark, $respInfo, $status);
        }
    }
}

// balco手表非标抽奖提交
public function dosubmit() {
    $id = $this->id;
    $overdue = $this->checkDate();
    if ($overdue === false)
        $this->ajaxReturn("error", "该活动不在有效期之内！", 0);
        // 是否抽奖
    $query_arr = M('tmarketing_info')->field('is_cj,start_time,end_time')
        ->where(
        array(
            'id' => $this->batch_id, 
            'batch_type' => $this->batch_type))
        ->find();
    if ($query_arr['is_cj'] != '1')
        $this->ajaxReturn("error", "未查询到抽奖活动！", 0);
    
    if (! $this->isPost()) {
        $this->ajaxReturn("error", "非法提交！", 0);
    }
    
    // 忽略验证码
    $ignore_code = I('post.ignore_code');
    
    $mobile = I('post.mobile');
    
    $verify = I('post.verify');
    
    $cj_check_flag = I('post.cj_check_flag');
    $check_code = I('post.check_code');
    
    if (! is_numeric(trim($mobile)) || strlen(trim($mobile)) != '11') {
        $this->ajaxReturn("error", "请正确填写手机号！", 3);
    }

    if ($ignore_code != '1') {
        if (session('verify_cj') != md5($verify)) {
            $this->ajaxReturn("error", "验证码错误！!!!", 4);
        }
    }
    
    if (empty($id)) {
        $this->ajaxReturn("error", "错误的请求！", 0);
    }
    
    session('verify_cj', null);
    
    $is_update = false;
    if ($cj_check_flag == '1') {
        if ($check_code == '') {
            $this->ajaxReturn("error", "错误的参与码！", 0);
        }
        
        // 校验是否存在
        $checkm = M('tcode_verify');
        $cmap = array(
            'batch_id' => $this->batch_id, 
            'batch_type' => $this->batch_type, 
            'status' => '0', 
            'verify_code' => strtolower($check_code));
        $query = $checkm->where($cmap)->find();
        if ($query) {
            $is_update = true;
        } else {
            $this->ajaxReturn("error", "错误的参与码！", 0);
        }
    }
    import('@.Vendor.ChouJiang');
    $pay_token=I('pay_token', null);
    $choujiang = new ChouJiang($id, $mobile, $this->full_id,null, $pay_token);
    $resp = $choujiang->send_code();
    if ($is_update === true) {
        $checkm->where(array(
            'id' => $query['id']))->save(array(
            'status' => '1'));
    }
    if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
        
        $this->ajaxReturn("success", 
            "<h1>抽奖成功</h1><p>恭喜您获得精美礼品一份！稍后将以短信形式告知，请注意查收！</p>", 1);
    } else {
        
        $this->ajaxReturn("error", "<h1>感谢您的参与！</h1><p>很遗憾，您没有中奖。</p>", 1);
    }
}

Public function verify() {
//    import('ORG.Util.Image');
//    Image::buildImageVerify($length = 4, $mode = 1, $type = 'png', $width = 48,
//        $height = 22, $verifyName = 'verify_cj');

    import('@.Service.ImageVerifyService');
    ImageVerifyService::buildImageCodeByParam('verify_cj');
}

// 天生一对抽奖
public function submitValentine() {
    $overdue = $this->checkDate();
    if ($overdue === false)
        $this->ajaxReturn("error", "该活动不在有效期之内！", 0);
    
    $id = $this->id;
    if ($this->batch_type == '12' || $this->batch_type == '28')
        $is_set_cj = M('tmarketing_info')->where(
            array(
                'id' => $this->batch_id, 
                'batch_type' => $this->batch_type))->getField('is_cj');
    if ($is_set_cj != '1')
        $this->ajaxReturn("error", "未查询到抽奖活动！", 0);
    
    if (! $this->isPost()) {
        $this->ajaxReturn("error", "非法提交！", 0);
    }
    if (session('verify_cj') != md5(I('post.verify'))) {
        $this->ajaxReturn("error", "验证码错误！", 0);
    }
    $boyphone = I('post.boyphone');
    $girlphone = I('post.girlphone');
    
    if (! is_numeric(trim($boyphone)) || strlen(trim($boyphone)) != '11') {
        $this->ajaxReturn("error", "错误手机号:" . $boyphone, 0);
    }
    if (! is_numeric(trim($girlphone)) || strlen(trim($girlphone)) != '11') {
        $this->ajaxReturn("error", "错误手机号:" . $girlphone, 0);
    }
    session('verify_cj', null);
    
    $bog_cj = false;
    $girl_cj = false;
    import('@.Vendor.ChouJiang');
    // 男孩抽奖
    $pay_token=I('pay_token', null);
    $choujiang_1 = new ChouJiang($id, $boyphone, $this->full_id,null,$pay_token);
    $resp_1 = $choujiang_1->send_code();
    // $this->ajaxReturn("error","错误手机号:".$resp_1,0);
    // 隐藏手机号部分数字
    $pattern = "/(\d{3})\d{4}(\d{4})/";
    $replacement = "\$1****\$2";
    
    if (isset($resp_1['resp_id']) && $resp_1['resp_id'] == '0000') {
        $cj_text = '恭喜您，手机号为' . preg_replace($pattern, $replacement, $boyphone) .
             '的用户，您中奖了，请注意查收您的手机短信哦！';
        $bog_cj = true;
    }
    
    // 女孩抽奖
    $choujiang_2 = new ChouJiang($id, $girlphone, $this->full_id,null,$pay_token);
    $resp_2 = $choujiang_2->send_code();
    
    if (isset($resp_2['resp_id']) && $resp_2['resp_id'] == '0000') {
        $cj_text = '恭喜您，手机号为' . preg_replace($pattern, $replacement, $girlphone) .
             '的用户，您中奖了，请注意查收您的手机短信哦！';
        $girl_cj = true;
    }
    // 全部中奖
    if ($bog_cj === true && $girl_cj === true) {
        $cj_text = '恭喜您，手机号为' . preg_replace($pattern, $replacement, $boyphone) .
             '和' . preg_replace($pattern, $replacement, $girlphone) .
             '的用户，您们都中奖了，请注意查收您们的手机短信哦！';
    }
    // 超过每天的参与次数
    // 报错详细信息
    $errorDetail = '';
    if ($resp_1['resp_id'] == '1005') {
        $errorDetail = preg_replace($pattern, $replacement, $boyphone) .
             ',您已经超过了今天的参与次数。';
    }
    if ($resp_2['resp_id'] == '1005') {
        $errorDetail .= preg_replace($pattern, $replacement, $girlphone) .
             ',您已经超过了今天的参与次数。';
    }
    // 超过总的参与次数
    if ($resp_1['resp_id'] == '1016') {
        $errorDetail .= preg_replace($pattern, $replacement, $boyphone) .
             ',您已用完了本次活动的参与次数。';
    }
    if ($resp_2['resp_id'] == '1016') {
        $errorDetail .= preg_replace($pattern, $replacement, $girlphone) .
             ',您已用完了本次活动的参与次数。';
    }
    if (! empty($errorDetail)) {
        $this->ajaxReturn("error", $errorDetail, 0);
    }
    
    // 全部不中
    if ($bog_cj === false && $girl_cj === false) {
        $cj_text = '很遗憾，您们没有抽中奖品哦！';
    }
    if ($bog_cj === true || $girl_cj === true) {
        $this->ajaxReturn("success", $cj_text, 1);
    } else {
        $this->ajaxReturn("error", $cj_text, 0);
    }
}
// 中秋节抽奖
public function submitzhongqiu() {
    $id = $this->id;
    $overdue = $this->checkDate();
    if ($overdue === false)
        $this->ajaxReturn("error", "该活动不在有效期之内！", 0);
        // 是否抽奖
    $query_arr = M('tmarketing_info')->field('is_cj,start_time,end_time')
        ->where(
        array(
            'id' => $this->batch_id, 
            'batch_type' => $this->batch_type))
        ->find();
    if ($query_arr['is_cj'] != '1')
        $this->ajaxReturn("error", "未查询到抽奖活动！", 0);
    
    if (! $this->isPost()) {
        $this->ajaxReturn("error", "非法提交！", 0);
    }
    
    $mobile = I('post.mobile');
    
    if (! is_numeric(trim($mobile)) || strlen(trim($mobile)) != '11') {
        $this->ajaxReturn("error", "错误手机号！", 0);
    }
    
    // 判断参与数量
    $all_count = 3;
    $cycount = M('tcj_trace')->where(
        array(
            'batch_id' => $this->batch_id, 
            'mobile' => $mobile))->count();
    if ($cycount == '1') {
        $is_sns = M('tsns_log')->where(
            "batch_id='" . $this->batch_id . "' and phone='" . $mobile . "' ")->find();
        if (! $is_sns)
            $this->ajaxReturn("error", "很遗憾您不能再参与抽奖了", 0);
    } elseif ($cycount == '2') {
        $count_sns = M('tsns_log')->where(
            "batch_id='" . $this->batch_id . "' and phone='" . $mobile . "' ")->count();
        if ($count_sns != '2')
            $this->ajaxReturn("error", "请分享到其他微博", 0);
    } elseif ($cycount == '3') {
        $this->ajaxReturn("error", "很遗憾您不能再参与抽奖了", 0);
    }
    
    import('@.Vendor.ChouJiang');
    $pay_token=I('pay_token',null);
    $choujiang = new ChouJiang($id, $mobile, $this->full_id,null,$pay_token);
    $resp = $choujiang->send_code();
    if (isset($resp['resp_id']) && $resp['resp_id'] == '0000') {
        // 中奖提示
        $cjrule = M('tcj_rule');
        $map1 = array(
            'node_id' => $this->node_id, 
            'batch_id' => $this->batch_id, 
            'batch_type' => $this->batch_type);
        $cj_msg = $cjrule->where($map1)->find();
        $cj_msg = $cj_msg["cj_resp_text"] == "" ? "恭喜你，中奖了！！！" : $cj_msg["cj_resp_text"];
        $this->ajaxReturn("success", $cj_msg, 1);
    } else {
        $resp = '很遗憾，未中奖,感谢您的参与！';
        $this->ajaxReturn("error", $resp, 0);
    }
}

/**
 *
 * @param $mobile
 * @param $resp 河北太平洋保险，抽奖成功之后，加入订单表
 */
public function _hbtpy($mobile, $resp) {
    if ($this->node_id == C('hbtpybx.node_id')) {
        $tbl_name = 'tfb_hbtpy_trace';
        $log_prefix = '河北平安太平洋保险非标处理';
    } else if ($this->node_id == C('gstpybx.node_id')) {
        $tbl_name = 'tfb_gstpy_trace';
        $log_prefix = '甘肃平安太平洋保险非标处理';
    } else if ($this->node_id == C('sxtpybx.node_id')) {
        $tbl_name = 'tfb_sxtpy_trace';
        $log_prefix = '山西平安太平洋保险非标处理';
    } else {
        return;
    }
    
    $cj_trace_id = $resp['cj_trace_id'];
    $map = array(
        'a.id' => $cj_trace_id, 
        'a.mobile' => $mobile, 
        'a.node_id' => $this->node_id, 
        'a.rule_id' => array(
            'exp', 
            '= b.id'), 
        'b.b_id' => array(
            'exp', 
            '= c.id'));
    $cj_trace_info = M()->table('tcj_trace a, tcj_batch b , tbatch_info c')
        ->where($map)
        ->field(
        'b.id as b_id, a.add_time, c.verify_end_date, c.verify_end_type, c.batch_name as bname')
        ->find();
    
    // 计算凭证截止时间
    $end_time = date("Ymd235959", strtotime($cj_trace_info["verify_end_date"]));
    if ($cj_trace_info["verify_end_type"] == '1') {
        $end_time = date("Ymd235959", 
            strtotime("+" . $cj_trace_info["verify_end_date"] . " days"));
    }
    
    // 计算凭证开始时间
    $begin_time = date("Ymd000000", 
        strtotime($cj_trace_info["verify_begin_date"]));
    if ($cj_trace_info["verify_begin_type"] == '1') {
        $begin_time = date("Ymd000000", 
            strtotime("+" . $cj_trace_info["verify_begin_date"] . " days"));
    }
    if ($begin_time < date('YmdHis'))
        $begin_time = date('YmdHis');
    
    $data = array(
        'cj_trace_id' => $cj_trace_id, 
        'm_id' => $this->marketInfo['id'], 
        'mname' => $this->marketInfo['name'], 
        'b_id' => $cj_trace_info['b_id'], 
        'bname' => $cj_trace_info['bname'], 
        'add_time' => $cj_trace_info['add_time'], 
        'phone_no' => $mobile, 
        'va_begin_time' => $begin_time, 
        'va_end_time' => $end_time, 
        'use_time' => '', 
        'op_user_id' => '', 
        'car_number' => '', 
        'insur_number' => '');
    $flag = M($tbl_name)->add($data);
    $log = $log_prefix;
    if ($flag === false) {
        $log .= '订单处理失败！' . M()->getDbError();
    } else {
        $log .= '订单处理成功！' . json_encode($data);
    }
    log_write($log);
}

}