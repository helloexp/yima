<?php

/**
 * 抽奖活动之大转盘抽奖
 * 1、手机号参与，如果奖品中包含微信卡券，强制使其使用微信浏览器访问
 * 2、微信号参与，
 * @author Jeff Liu<liuwy@imageco.com.cn> @date 2016/04/18
 */
class NewSpinTurnplateAction extends DrawLotteryBaseAction
{

    const BATCH_TYPE = 53;
    // 大转盘抽奖类型
    const NO_AWARD_TIP = '对不起，未中奖';
    const DRAW_LOTTERY_SUCCESS = '0000';
    const MEMBER_SHIP_ERR = '1012';//用户会员类型不正确(不是会员)

    /**
     * 手机验证码过期时间
     */
    const VERIFY_CODE_EXPIRE_TIME = 60;

    /**
     *
     * @var RemoteRequestService
     */
    private $RemoteRequestService;

    /**
     *
     * @var MemberRecruitService
     */
    private $MemberRecruitService;

    /**
     * @var IntegalGetDetailModel
     */
    protected $IntegalGetDetailModel;

    /**
     * @var WeiXinService
     */
    public $WeiXinService;

    /**
     * @var DrawLotteryBaseService
     */
    private $DrawLotteryBaseService;

    private $joinViaWechat;

    public function _initialize()
    {
        import('@.Vendor.CommonConst');
        $this->IntegalGetDetailModel = D('IntegalGetDetail');
        if (ACTION_NAME == 'getDrawLotteryResult' || ACTION_NAME == 'debugResult') {
            return;
        }
        $this->DrawLotteryBaseService = D('DrawLotteryBase', 'Service');
        parent::_initialize();
        $this->_checkUser(true);
        $this->joinViaWechat = $this->marketInfo['join_mode'] == CommonConst::JOIN_VIA_WECHAT;
    }

    /**
     * 大转盘抽奖暂时页面
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function index()
    {
        $isPreviewChannel = $this->isPreviewChannel($this->node_id);
        if (!$isPreviewChannel) { // 不是预览的话 需要多判断 有效期 和 付款状态
            $dateVerified = $this->checkDate();
            if ($dateVerified === false) { // 不再有效期
                $this->showErrorByErrno(-1005);
            }
            $paied  = $this->hasPayModule('m1', $this->node_id);
            $paied1 = verify_val($this->marketInfo, 'pay_status', '0', '===');
            if (!$paied && $paied1) { // 未付款 不能使用  (非预览不付款不能查看)
                $this->showErrorByErrno(-1046);
            }
        }

        // 获取抽奖信息
        $cjResult         = $this->DrawLotteryBaseService->getCjInfo($this->id);
        $phoneCountPerDay = $this->getPhoneDayPart();

        if ($cjResult['code'] == '0000') { // 成功
            $this->assign('cjInfo', $cjResult['data']);
            $this->assign('total_part', $phoneCountPerDay); // 参与次数 0为不限制
        }

        // 抽奖配置表
        $cjRule = get_val($cjResult, 'cjRule', []);

        // 抽奖文字配置
        $cjButtonText  = get_val($cjRule, 'cj_button_text');
        $noAwardNotice = get_val($cjRule, 'no_award_notice');
        if ($noAwardNotice) {
            $noAwardNotice = explode('|', $noAwardNotice);
            session('noAwardNotice:' . $this->marketInfo['id'], $noAwardNotice);
        }

        // 判断是否显示参与码
        $cjCheckFlag = get_val($cjRule, 'cj_check_flag');

        // 手机号(如果已经抽过奖品)
        $mobile = $this->DrawLotteryCommonService->getDrawLotteryMobile($this->id);

        // 剩余抽奖机会
        if ($phoneCountPerDay) { // 有限制次数
            $drawLotteryId = $this->getDrawLotteryUniqueId($this->id);//获得抽奖唯一标示
            if ($drawLotteryId) { // 已经抽过了，查询剩余
                $leftChances = $this->getLeftChances($drawLotteryId, $phoneCountPerDay);
            } else { // 还没有抽过 设置为最大允许次数
                $leftChances = $phoneCountPerDay;
            }
        } else { // 次数不限
            $leftChances = 9999999;
        }

        //微信分享 start
        $wechatShareData = $this->genreateWechatShareCode();
        $this->assign('wxShareData', json_encode($wechatShareData));
        //微信分享 end

        $this->cacheControl(); // 禁止缓存

        $needIgnoreFlag = $this->getIgnoreFlag(['batchId' => $this->batch_id]);
        $this->assign('needIgnoreFlag', $needIgnoreFlag);

        $key = $this->DrawLotteryCommonService->generateDrawLotteryMobileCookieKey($this->id);
        $this->assign('leftChances', $leftChances);
        $this->assign('cj_check_flag', $cjCheckFlag);
        $this->assign('cj_text', $cjButtonText);
        $this->assign('_global_user_mobile', $this->userCookieMobile());
        $this->assign('mobile', $mobile);
        $this->assign('row', $this->marketInfo);
        $this->assign('id', $this->id);
        $this->assign('mobileCookieId', $key);
        $this->assign('batch_type', $this->batch_type); // 活动类型
        $this->assign('batch_id', $this->batch_id); // 活动id
        $this->assign('verifyCodeExpireTime', self::VERIFY_CODE_EXPIRE_TIME);
        $this->assign('node_name', $this->marketInfo['node_name']);
        $this->assign('memberCenterUrl', U('Label/Member/index', array('node_id' => $this->node_id)));
        $this->display();
    }

    /**
     * 微信分享
     * @return array
     */
    public function genreateWechatShareCode()
    {
        // 微信分享 start
        if (empty($this->WeiXinService)) {
            $this->WeiXinService = D('WeiXin', 'Service');
        }
        $config_data    = get_val($this->marketInfo, 'config_data', '');
        $configData     = unserialize($config_data);
        $share_descript = get_val($configData, 'share_descript', '');
        $share_pic      = get_val($this->marketInfo, 'share_pic', '');
        $name           = get_val($this->marketInfo, 'name', '');
        $wxShareConfig  = $this->WeiXinService->getWxShareConfig();
        $wxShareData    = array(
                'config' => $wxShareConfig,
                'link'   => ['index', ['id' => $this->id,], '', '', true],
                'title'  => $name,
                'desc'   => $share_descript,
                'imgUrl' => $share_pic,
        );
        // 微信分享 end
        return $wxShareData;
    }

    /**
     * 验证抽奖条件
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return mixed
     */
    public function verifyDrawLotteryCondition()
    {
        if (empty($this->id)) { // id丢失
            $this->ajaxReturn(-1023);
        }

        if (!$this->isPost()) { // 不是通过POST方法进行请求
            $this->ajaxReturn(-1022);
        }

        if ($this->isPreviewChannel($this->node_id, $this->id)) { // 预览渠道不能进行抽奖
            $this->ajaxReturn(-1048);
        }

        $paied  = $this->hasPayModuleWithCache('m1', $this->node_id);
        $paied1 = verify_val($this->marketInfo, 'pay_status', '0', '===');
        if ((!$paied && $paied1)) { // 未支付 不可以进行抽奖
            $this->ajaxReturn(-1046);
        }

        $mobile = I('post.mobile');

        // 如果参加方式是微信号
        if ($this->joinViaWechat) { // 通过微信号参与
            if (empty($this->wxSess['openid'])) { // openid获取失败
                $this->ajaxReturn(-1034);
            }
        } else { // 通过手机号参与
            if (!is_numeric(trim($mobile)) || strlen(trim($mobile)) != '11') { // 手机号验证失败
                // 这个可以搞成通用逻辑
                $this->ajaxReturn(-1035);
            }
            $rememberedMobile = $this->DrawLotteryCommonService->getDrawLotteryMobile($this->id, true);

            // 二维码名片无需验证码
            if ($this->id != C('VCARD_ACTIVITY_NUMBER') && ($rememberedMobile != $mobile)) {
                //  不是验证码且 保存的mobile和传递的mobile不一致，验证验证码是否正确 手机验证码
                $checkCode  = I('post.verify', null);
                $error      = null;
                $ignoreCode = I('post.ignore_code', 0);
                if ($checkCode && !check_str($checkCode, ['null' => false,], $error) && $ignoreCode != '1') {
                    return ['errno' => -1007, 'errmsg' => $error,];
                }
                $phone_check_code = session('checkCode');
                if (function_exists('is_production') && !is_production() && $checkCode == '1111') {
                    // 为 测试环境 1111视为正确
                } else {
                    $cookiePhone = $this->getDrawLotteryMobile();
                    if ($cookiePhone != $mobile) {
                        if (empty($phone_check_code) || verify_val($phone_check_code, 'number', $checkCode, '!=')) {
                            $this->ajaxReturn(-1009);// 手机验证码不正确
                        }
                        $add_time = get_val($phone_check_code, 'add_time', 0);
                        if (time() - $add_time > self::VERIFY_CODE_EXPIRE_TIME) { // 手机验证码已经过期
                            $this->ajaxReturn(-1010);
                        }
                    }
                }
            }
        }

        return $this->verifyCodeVerify();
    }

    /**
     * 获取剩余抽奖次数
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $drawLotteryId
     * @param $phoneCountPerDay
     *
     * @return int
     */
    public function getLeftChances($drawLotteryId, $phoneCountPerDay)
    {
        $key               = '_drawLottery_' . md5($this->id . $drawLotteryId);
        $cookieLeftChances = cookie($key);
        if ($cookieLeftChances == null) {
            $final = $phoneCountPerDay;
        } else if ($cookieLeftChances > $phoneCountPerDay) {
            $final = $phoneCountPerDay;
        } else if ($cookieLeftChances < 0) {
            $final = 0;
        } else {
            $final = (int)$cookieLeftChances;
        }

        return $final;
    }

    /**
     * 设置用户剩余参与次数
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $drawLotteryId
     * @param $leftChances
     * @param $expire
     */
    public function setLeftChances($drawLotteryId, $leftChances, $expire)
    {
        $key = '_drawLottery_' . md5($this->id . $drawLotteryId);
        cookie($key, $leftChances, $expire);
    }



    /**
     * 验证参与码的有效性
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return array
     */
    public function verifyCodeVerify()
    {
        $needUpdateCodeVerify = false;
        $codeVerifyInfo       = array();
        $drawLotteryCheckFlag = I('post.cj_check_flag'); // 是否检查参与码
        $checkCode            = I('post.check_code'); // 参与码

        if ($drawLotteryCheckFlag == '1') {
            if (empty($checkCode)) { // 参与码为空
                $this->ajaxReturn(-1036);
            }

            $where          = [
                    'batch_id'    => $this->batch_id,
                    'batch_type'  => $this->batch_type,
                    'status'      => '0',
                    'verify_code' => strtolower($checkCode),
            ];
            $codeVerifyInfo = $this->DrawLotteryModel->getCodeVerify($where);
            if ($codeVerifyInfo) {
                $needUpdateCodeVerify = true;
            } else {
                $this->ajaxReturn(-1036);
            }
        }

        return ['needUpdate' => $needUpdateCodeVerify, 'codeVerify' => $codeVerifyInfo,];
    }

    /**
     * 手机发送验证码
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function sendCheckCode()
    {
        $overdue = $this->checkDate();
        if ($overdue === false) { // 该活动不在有效期之内
            $this->showErrorByErrno(-1016);
        }
        $error  = null;
        $mobile = I('post.phone_no', null);
        if (!check_str($mobile, ['null' => false, 'strtype' => 'mobile',], $error)) {
            $this->showErrorByErrno(-1008, null, $error);
        }

        // 发送频率验证
        $checkCode = session('checkCode');
        $oldMobile = get_val($checkCode, 'mobile', '');
        $add_time  = get_val($checkCode, 'add_time', 0);
        if (!empty($checkCode) && $oldMobile == $mobile && (time() - $add_time) < self::VERIFY_CODE_EXPIRE_TIME) {
            $this->showErrorByErrno(-1017, null, time() - $add_time);
        }
        $num = mt_rand(1000, 9999);
        // 短信内容
        if (empty($this->MemberRecruitService)) {
            $this->MemberRecruitService = D('MemberRecruit', 'Service');
        }
        $node_id   = get_val($this->marketInfo, 'node_id', 0);
        $node_name = $this->MemberRecruitService->getNodeInfo($node_id);
        $code_info = "【{$node_name}】 大转盘抽奖,您此次的动态验证码为：{$num} 如非本人操作请忽略！";
        // 通知支撑
        $transaction_id = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
        // 请求参数
        $req_array = array(
                'NotifyReq' => array(
                        'TransactionID' => $transaction_id,
                        'ISSPID'        => C('MOBILE_ISSPID'),
                        'SystemID'      => C('ISS_SYSTEM_ID'),
                        'SendLevel'     => '1',
                        'Recipients'    => ['Number' => $mobile,],  // 手机号
                        'SendClass'     => 'MMS',
                        'MessageText'   => $code_info,  // 短信内容
                        'Subject'       => '',
                        'ActivityID'    => C('MOBILE_ACTIVITYID'),
                        'ChannelID'     => '',
                        'ExtentCode'    => '',
                ),
        );
        if (empty($this->RemoteRequestService)) {
            $this->RemoteRequestService = D('RemoteRequest', 'Service');
        }
        $respInfo = $this->RemoteRequestService->requestIssServ($req_array);

        $ret_msg            = isset($respInfo['NotifyRes']['Status']) ? $respInfo['NotifyRes']['Status'] : array();
        $drawLotterySuccess = verify_val($ret_msg, 'StatusCode', self::DRAW_LOTTERY_SUCCESS, '!=');
        if (!$respInfo || ($drawLotterySuccess && verify_val($ret_msg, 'StatusCode', '0001', '!='))) {
            $this->showErrorByErrno(-1018);
        }
        $checkCode = ['number' => $num, 'add_time' => time(), 'mobile' => $mobile,];
        session('checkCode', $checkCode);
        $this->success('验证码已发送');
    }

    /**
     * 日参与抽奖次数 todo 这个可以优化？？
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     * @return int
     */
    public function getPhoneDayPart()
    {
        $phoneCountPerDay = 0;
        $cjResult         = $this->DrawLotteryBaseService->getCjInfo($this->id);
        if (isset($cjResult['data']['cj_rule']['phone_day_part'])) {
            $phoneCountPerDay = $cjResult['data']['cj_rule']['phone_day_part'];
        }

        return $phoneCountPerDay;
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function drawLottery()
    {
        // 过滤重复请求 start
        $personId = $this->getPersonId();
        $params   = ['mobile' => $personId, 'node_id' => $this->node_id, 'batch_id' => $this->batch_id,];
        $this->filterDuplicateRequest($params, 'dzp');
        // 过滤重复请求 end

        //当前用户是否还有正在进行中的抽奖 start
        $params         = ['batchId' => $this->batch_id, 'personId' => $personId];
        $canDrawLottery = $this->canDrawLotteryProcessTaskAndSetStatus($params);
        if ($canDrawLottery == false) {
            $this->ajaxReturn(-1076);
        }

        $id               = $this->id;
        $codeVerifyResult = $this->verifyDrawLotteryCondition(); // 验证抽奖条件, 验证失败的话会直接在方法中返回并退出

        $mobile = I('post.mobile', null);
        session('verify_cj', null);

        import('@.Vendor.DrawLotteryBase');
        $other      = [];
        $wxUserInfo = [];

        if ($this->joinViaWechat) { //需要微信微信参与
            $wxUserInfo = $this->wxSess ? $this->wxSess : $this->getWxUserInfo();
        }

        if ($wxUserInfo) {
            $other = [
                    'wx_open_id' => get_val($wxUserInfo, 'openid'),
                    'wx_nick'    => get_val($wxUserInfo, 'nickname'),
            ];
        } else {
            $this->setDrawLotteryMobile($mobile);
        }

        // 剩余抽奖机会 start
        $phoneCountPerDay = $this->getPhoneDayPart();
        if ($phoneCountPerDay) { //有日参与次数限制 判断当前用户的剩余参与次数
            $leftChances = $this->getLeftChances($personId, $phoneCountPerDay);
            if ($leftChances < 1) { // 没有抽奖次数了。
                $this->ajaxReturn(-1040);
            }
        }
        // 剩余抽奖机会 end

        //将抽奖唯一标识存入COOKIE start
        $endTime = $this->marketInfo['end_time'];
        $year    = $month = $day = $hour = $minute = $second = 0;
        if (strlen($endTime) == 14) {
            sscanf($endTime, '%4s%2s%2s%2s%2s%2s', $year, $month, $day, $hour, $minute, $second);
        }
        $expire = strtotime("{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}") + 2592000;//结束时间一个月之后过期
        $this->setDrawLotteryUniqueId($this->id, $personId, $expire);
        //将抽奖唯一标识存入COOKIE end

        // 统计(参与信息)
        $participationId           = $this->addParticipationLog($mobile);
        $other['participation_id'] = $participationId;

        //抽奖 start ===================================================================================================
        $DrawLotteryBase   = new DrawLotteryBase();
        $other['phone_no'] = $mobile;
        $resp              = $DrawLotteryBase->drawLottery($id, $other);
        log_write('DrawLotteryBase::drawLottery ' . var_export($resp, true));
        //抽奖 end =====================================================================================================

        if (isset($resp['resp_id']) && $resp['resp_id'] == self::MEMBER_SHIP_ERR) { //需要会员才能参与
            $formatedInfo = $this->formateResult($resp);
            $this->clearDrawLotteryProcessFlag($params);
            $this->responseJson($formatedInfo['status'], $formatedInfo['msg'], $formatedInfo['data']);
            return;
        }
        //        $formatedInfo = $this->fillAwardDataAndFormatForShow($resp);
        $formatedInfo = $this->formateResult($resp, 'rule_id');
        if ($formatedInfo['status'] != self::DRAW_LOTTERY_SUCCESS) { //统一提示未中奖
            $formatedInfo['msg'] = self::NO_AWARD_TIP;
        }

        if ($codeVerifyResult['needUpdate'] === true) { //需要更新参与码信息
            $this->DrawLotteryModel->updateCodeVerifyData(
                    ['id' => $codeVerifyResult['codeVerify']['id'],],
                    ['status' => '1',]
            );
        }
        if ($formatedInfo['status'] < 9900) { //9900 ~ 10000 属于系统问题  系统问题的话 不应该扣除抽奖次数
            $this->userCookieMobile($mobile); // 将手机号记录到cookie中
            if ($phoneCountPerDay) {//需要控制每日参与次数 修改参与次数限制
                $leftChances = $this->getLeftChances($personId, $phoneCountPerDay);
                $leftChances -= 1;
                if ($leftChances < 0) {
                    $leftChances = 0;
                }
                $this->setLeftChances($personId, $leftChances, $expire);
                $formatedInfo['data']['leftChances'] = $leftChances;
                if ($this->joinViaWechat || $leftChances < 1) {//没有抽奖次数了
                    $this->setIgnoreFlag(['batchId' => $this->batch_id, 'value' => 1, 'expire' => 2592000]);//一个月
                }
            }
        }

        // for 中奖记录 start
        if (empty($this->DrawLotteryCommonService)) {
            $this->DrawLotteryCommonService = D('DrawLotteryCommon', 'Service');
        }
        $this->DrawLotteryCommonService->setMobileAndGobackUrl($id, $other, $mobile);
        // for 中奖记录 start
        $this->clearDrawLotteryProcessFlag($params);

        $this->responseJson($formatedInfo['status'], $formatedInfo['msg'], $formatedInfo['data']);
    }

    /**
     * 设置抽奖唯一标示
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $id
     * @param $uniqueId
     * @param $expire
     */
    protected function setDrawLotteryUniqueId($id, $uniqueId, $expire)
    {
        $key = '_drawLotteryUnique' . $id;
        cookie($key, $uniqueId, $expire);
    }

    /**
     * 获得抽奖唯一标识
     * @author Jeff.Liu<liuwy@imageco.com.cn>
     *
     * @param $id
     *
     * @return mixed
     */
    protected function getDrawLotteryUniqueId($id)
    {
        $key = '_drawLotteryUnique' . $id;
        return cookie($key);
    }

    /**
     * @param $resp
     *
     * @return array
     */
    protected function fillAwardDataAndFormatForShow($resp)
    {
        $respData = get_val($resp, 'resp_data');
        $goods_id = get_val($respData, 'rule_id');
        $where    = "a.id='{$goods_id}'";

        $defaultInfo = [
                'goods_name' => '',
                'goods_id'   => '',
                'goods_type' => '',
                'bonus_id'   => '',
                'link_url'   => '',
                'num'        => 0,
        ];

        $goodsInfo                = $this->DrawLotteryModel->getGoodsInfoAndBounsInfo($where);
        $goodsInfo                = $this->DrawLotteryModel->mergeGoodsInfoByBid($goodsInfo);
        $goodsInfo                = array_merge($defaultInfo, (array)$goodsInfo);
        $goodsInfo['goods_image'] = get_upload_url($goodsInfo['goods_image']);
        if (issetAndNotEmpty($respData, 'integral_get_id')) { //积分
            $integalGetDetail = $this->IntegalGetDetailModel->getIntegalGetDetail(
                    ['id' => get_val($respData, 'integral_get_id')],
                    BaseModel::SELECT_TYPE_ONE
            );
            $goodsInfo['num'] = get_val($integalGetDetail, 'integral_num', 0);
        }
        // 中了手机凭证奖品
        $prizeTypeIs4       = verify_val($respData, 'prize_type', '4');
        $integralGetFlagIs0 = verify_val($respData, 'integral_get_flag', '0');
        if (!empty($respData['request_id']) || ($prizeTypeIs4 && $integralGetFlagIs0)) {
            // 临时中奖码，需要响应给前台，校验一下正确性用的，校验正确后失效,以免直接暴露 request_id和 cj_trace_id,用完以后清空
            $cj_code = time() . mt_rand(100, 999);
            session(
                    '_TmpChouJian_',
                    [
                            'cj_code'         => $cj_code,
                            'request_id'      => get_val($respData, 'request_id'),
                            'cj_trace_id'     => get_val($respData, 'cj_trace_id'),
                            'card_ext'        => get_val($respData, 'card_ext'),
                            'card_id'         => get_val($respData, 'card_id'),
                            'goods_info'      => $goodsInfo,
                            'prize_type'      => get_val($respData, 'prize_type'),
                            'integral_get_id' => get_val($respData, 'integral_get_id'),
                    ]
            );
            $respData['cj_code'] = $cj_code;
        }
        // 返回结果
        $respData['goods_info'] = $goodsInfo;
        $resp['resp_data']      = $respData;

        $formatedInfo = $this->formatDrawLotteryResult($resp);
        return $formatedInfo;
    }


    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $mobile
     *
     * @return bool
     */
    public function setDrawLotteryMobile($mobile)
    {
        return $this->DrawLotteryCommonService->setMobileForAwardList($this->id, $mobile);
    }

    public function getDrawLotteryMobile()
    {
        return $this->DrawLotteryCommonService->getMobileForAwardList($this->id);
    }

    /**
     * 新增参与流水记录
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param string $mobile
     *
     * @return bool|mixed
     */
    public function addParticipationLog($mobile)
    {
        // 统计
        import("@.Vendor.Statistics");

        $batchTraceId = session('batchTraceId');
        if (empty($batchTraceId)) {
            $batchTraceId = 0;
        }
        $insertData = [
                'label_id'       => $this->id,
                'node_id'        => $this->node_id,
                'batch_type'     => $this->batch_type,
                'batch_id'       => $this->batch_id,
                'channel_id'     => $this->channel_id,
                'join_mode'      => $this->join_mode,
                'full_id'        => $this->fullId,
                'batch_trace_id' => $batchTraceId,
                'mobile'         => $mobile,
        ];

        return Statistics::addParticipationLog($insertData);
    }

    /**
     * 查看抽奖记录
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function awardList()
    {
        $this->cacheControl();
        $rememberedMobile = $this->getDrawLotteryUid($this->id);
        $awardList        = $this->DrawLotteryModel->getAwardList(
                [
                        'mobile'     => $rememberedMobile,
                        'batch_id'   => $this->marketInfo['id'],
                        'batch_type' => $this->marketInfo['batch_type'],
                ]
        );

        $wechatCard     = $this->DrawLotteryCommonService->getUnfetchedWechatCard($awardList);
        $finalAwardList = $this->DrawLotteryCommonService->formatAwardList($awardList, $wechatCard);

        $this->assign('awardList', $finalAwardList);
        $this->assign('id', $this->id);

        $this->display();
    }

    /**
     * @param $result
     *
     * @return array
     */
    private function formateResult($result, $idKey = 'cj_batch_id')
    {
        $respData    = get_val($result, 'resp_data');
        $cj_batch_id = get_val($respData, $idKey);
        $where       = "a.id='{$cj_batch_id}'";

        $defaultInfo = [
                'goods_name' => '',
                'goods_id'   => '',
                'goods_type' => '',
                'bonus_id'   => '',
                'link_url'   => '',
                'num'        => 0,
        ];

        $goodsInfo                = $this->DrawLotteryModel->getGoodsInfoAndBounsInfo($where);
        $goodsInfo                = $this->DrawLotteryModel->mergeGoodsInfoByBid($goodsInfo);
        $goodsInfo                = array_merge($defaultInfo, (array)$goodsInfo);
        $goodsInfo['goods_image'] = get_upload_url($goodsInfo['goods_image']);
        if (issetAndNotEmpty($respData, 'integral_get_id')) { //积分
            $integalGetDetail = $this->IntegalGetDetailModel->getIntegalGetDetail(
                    ['id' => get_val($respData, 'integral_get_id')],
                    BaseModel::SELECT_TYPE_ONE
            );
            $goodsInfo['num'] = get_val($integalGetDetail, 'integral_num', 0);
        }
        // 中了手机凭证奖品
        $prizeTypeIs4       = verify_val($respData, 'prize_type', '4');
        $integralGetFlagIs0 = verify_val($respData, 'integral_get_flag', '0');
        if (!empty($respData['request_id']) || ($prizeTypeIs4 && $integralGetFlagIs0)) {
            log_write(print_r($respData, true));
            // 临时中奖码，需要响应给前台，校验一下正确性用的，校验正确后失效,以免直接暴露 request_id和 cj_trace_id,用完以后清空
            $cj_code = time() . mt_rand(100, 999);
            session(
                    '_TmpChouJian_',
                    [
                            'cj_code'         => $cj_code,
                            'request_id'      => get_val($respData, 'request_id'),
                            'cj_trace_id'     => get_val($respData, 'cj_trace_id'),
                            'card_ext'        => get_val($respData, 'card_ext'),
                            'card_id'         => get_val($respData, 'card_id'),
                            'goods_info'      => $goodsInfo,
                            'prize_type'      => get_val($respData, 'prize_type'),
                            'integral_get_id' => get_val($respData, 'integral_get_id'),
                    ]
            );
            $respData['cj_code'] = $cj_code;
        }
        // 返回结果
        $respData['goods_info'] = $goodsInfo;
        log_write('resp_data:' . print_r($respData, true));
        $result['resp_data'] = $respData;
        $formatedInfo        = $this->formatDrawLotteryResult($result);
        return $formatedInfo;
    }

    /**
     * 查询调用抽奖异步结果
     */
    public function getDrawLotteryResult()
    {
        import("@.Vendor.CjInterface");
        $cjInterface = new CjInterface();
        $key         = I('get.key', I('post.key'));
        $result      = $cjInterface->getCjResultByKey($key);

        if (!$result) {
            $this->responseJson(-1001, 'waiting');

            return;
        }
        log_write('result:' . var_export($result, true));
        if (verify_val($result, 'resp_id', self::DRAW_LOTTERY_SUCCESS, '!= ')) { // 如果是被限制都统一叫未中奖
            $noAwardNoticeMsg = self::NO_AWARD_TIP;
            $code             = get_val($result, 'resp_id');
            if ($code == self::MEMBER_SHIP_ERR) { // 需要会员
                $code = -1060;
            }
            $this->responseJson($code, $noAwardNoticeMsg);

            return;
        }

        $formatedInfo = $this->formateResult($result);
        $this->responseJson($formatedInfo['status'], $formatedInfo['msg'], $formatedInfo['data']);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $result
     *
     * @return array
     */
    public function formatDrawLotteryResult($result)
    {
        $finalMsg             = ['status' => '', 'msg' => '', 'data' => '',];
        $isDrawLotterySuccess = verify_val($result, 'resp_id', self::DRAW_LOTTERY_SUCCESS, '==');
        if ($isDrawLotterySuccess) { // 已中奖
            $finalMsg['status'] = 0; // 成功
            $finalMsg['data']   = get_val($result, 'resp_data');
            if (isset($result['resp_data']['card_id']) && $result['resp_data']['card_id']) { // 微信卡券
                $finalMsg['msg'] = isset($result['resp_data']['goods_info']['goods_name']) ? $result['resp_data']['goods_info']['goods_name'] : '';
            } else if (isset($result['resp_data']['goods_info']['bonus_id']) && $result['resp_data']['goods_info']['bonus_id']) { // 红包
                $finalMsg['msg'] = isset($result['resp_data']['goods_info']['goods_name']) ? $result['resp_data']['goods_info']['goods_name'] : '';
                $finalMsg['msg'] .= '， 请到' . $result['resp_data']['goods_info']['node_name'] . '使用';
            } else { // 卡券
                $finalMsg['msg'] = isset($result['resp_data']['goods_info']['goods_name']) ? $result['resp_data']['goods_info']['goods_name'] : '';

                if ($this->node_id == C('szpa.node_id')) {
                    $finalMsg['msg'] .= '，中奖信息将以短信或者微信的形式通知您，请留意查看。';
                } else {
                    $finalMsg['msg'] .= '， 中奖凭证将自动下发至您的手机，请注意查收!';
                }
            }
        } else { // 未中奖
            $finalMsg = ['status' => get_val($result, 'resp_id'), 'msg' => self::NO_AWARD_TIP, 'data' => '',];
        }

        return $finalMsg;
    }
}