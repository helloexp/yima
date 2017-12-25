<?php

/**
 * 抽奖活动之大转盘抽奖 1、手机号参与，如果奖品中包含微信卡券，强制使其使用微信浏览器访问 2、微信号参与，
 *
 * @author Jeff Liu<liuwy@imageco.com.cn> @date 2015/07/14 Class
 *         SpinTurnplateAction
 */
class SpinTurnplateAction extends DrawLotteryBaseAction {

    const BATCH_TYPE = 53;
    // 大转盘抽奖类型
    const NO_AWARD_TIP = '对不起，未中奖';

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

    public function _initialize() {
        import('@.Vendor.CommonConst');
        $this->IntegalGetDetailModel = D('IntegalGetDetail');
        if (ACTION_NAME == 'getDrawLotteryResult' || ACTION_NAME == 'debugResult') {
            return;
        }
        parent::_initialize();
        $this->_checkUser(true);

        // if ($this->marketInfo['batch_type'] != self::BATCH_TYPE) {//活动类型不正确
        // $this->showErrorByErrno(-1045);
        // }
    }

    public function debugResult() {
        error_log('$_REQUEST:' . var_export($_REQUEST, 1) . PHP_EOL, 3, '/tmp/drawLottery.log');
    }

    /**
     * 大转盘抽奖暂时页面
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function index() {
        $isPreviewChannel = $this->isPreviewChannel($this->node_id);
        if (!$isPreviewChannel) { // 不是预览的话 需要多判断 有效期 和 付款状态
            $dateVerified = $this->checkDate();
            if ($dateVerified === false) { // 不再有效期
                $this->showErrorByErrno(-1005);
            }

            if (!$this->hasPayModule('m1', $this->node_id) && verify_val($this->marketInfo, 'pay_status', '0', '===')) { // 未付款
                // 不能使用
                // (非预览不付款不能查看)
                $this->showErrorByErrno(-1046);
            }
        }

        // 获取抽奖信息
        $cjResult = $this->DrawLotteryModel->getCjInfo($this->marketInfo);

        $phoneCountPerDay = isset($cjResult['data']['cj_rule']['phone_day_part']) ? $cjResult['data']['cj_rule']['phone_day_part'] : 0;
        if ($cjResult['code'] == '0') { // 成功
            $this->assign('cjInfo', $cjResult['data']);
            $this->assign('total_part', $phoneCountPerDay); // 参与次数 0为不限制
        }

        // 抽奖配置表
        $cjRule = get_val($cjResult, 'cjRule', array());

        // 抽奖文字配置
        $cjButtonText = get_val($cjRule, 'cj_button_text', '');
        $noAwardNotice = get_val($cjRule, 'no_award_notice', '');
        if ($noAwardNotice) {
            $noAwardNotice = explode('|', $noAwardNotice);
            session('noAwardNotice:' . $this->marketInfo['id'], $noAwardNotice);
        }

        // 判断是否显示参与码
        $cjCheckFlag = get_val($cjRule, 'cj_check_flag', '');

        // 手机号(如果已经抽过奖品)
        $mobile = $this->DrawLotteryCommonService->getDrawLotteryMobile($this->id);

        $drawLotteryId = $this->getDrawLotteryUid($this->id);

        // 剩余抽奖机会
        if ($phoneCountPerDay) { // 有限制次数
            if ($drawLotteryId) { // 已经抽过了，查询剩余
                $leftChances = $this->getLeftChances($drawLotteryId);
            } else { // 还没有抽过 设置为最大允许次数
                $leftChances = $phoneCountPerDay;
            }
        } else { // 次数不限
            $leftChances = 9999999;
        }

        // 微信分享 start
        $config_data = get_val($this->marketInfo, 'config_data', '');
        $configData    = unserialize($config_data);
        $share_descript = get_val($configData, 'share_descript', '');
        $share_pic = get_val($this->marketInfo, 'share_pic', '');
        $name = get_val($this->marketInfo, 'name', '');
        $wxShareConfig = D('WeiXin', 'Service')->getWxShareConfig();
        $wxShareData   = array(
                'config' => $wxShareConfig,
                'link'   => U('index', array(
                        'id' => $this->id,
                ), '', '', true),
                'title'  => $name,
                'desc'   => $share_descript,
                'imgUrl' => $share_pic,
        );
        $this->assign('wxShareData', json_encode($wxShareData));
        // 微信分享 end

        $this->cacheControl(); // 禁止缓存

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
        $this->assign('memberCenterUrl', U('Label/Member/index', array('node_id'=>$this->node_id)));
        $this->display();
    }

    /**
     * 验证抽奖条件
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return mixed
     */
    public function verifyDrawLotteryCondition() {
        $dateVerified = $this->checkDate();
        if ($dateVerified === false) { // 日期验证失败
            $this->ajaxReturn(-1032);
        }

        // 是否抽奖
        if (verify_val($this->marketInfo, 'is_cj', '1', '!=')){ // 当前活动不属于抽奖活动
            $this->ajaxReturn(-1033);
        }

        if (!$this->isPost()) { // 不是通过POST方法进行请求
            $this->ajaxReturn(-1022);
        }

        if ($this->isPreviewChannel($this->node_id)) { // 预览渠道不能进行抽奖
            $this->ajaxReturn(-1048);
        }

        if ((!$this->hasPayModule('m1', $this->node_id) && verify_val($this->marketInfo, 'pay_status', '0', '==='))) { // 未支付
            // 不可以进行抽奖
            $this->ajaxReturn(-1046);
        }

        $mobile = I('post.mobile');

        if (empty($this->id)) { // id丢失
            $this->ajaxReturn(-1023);
        }

        // 剩余抽奖机会
        $leftChances = $this->getLeftChances($mobile);

        if ($leftChances < 1) { // 没有抽奖次数了。
            $this->ajaxReturn(-1040);
        }

        // 如果参加方式是微信号
        if (verify_val($this->marketInfo, 'join_mode', CommonConst::JOIN_VIA_WECHAT, '==')) { // 通过微信号参与
            if (empty($this->wxSess['openid'])) { // openid获取失败
                $this->ajaxReturn(-1034);
            }
        } else { // 通过手机号参与
            if (!is_numeric(trim($mobile)) || strlen(trim($mobile)) != '11') { // 手机号验证失败
                // todo
                // 这个可以搞成通用逻辑
                $this->ajaxReturn(-1035);
            }
            $rememberedMobile = $this->DrawLotteryCommonService->getDrawLotteryMobile($this->id, true);

            // 二维码名片无需验证码
            if ($this->id != C('VCARD_ACTIVITY_NUMBER') && ($rememberedMobile != $mobile)) { // 不是验证码
                // 且
                // 保存的mobile和传递的mobile不一致，验证验证码是否正确
                // 手机验证码
                $checkCode = I('post.verify', null);
                $error     = null;
                $ignoreCode = I('post.ignore_code', 0);
                if ($checkCode && !check_str($checkCode, array(
                                'null' => false,
                        ), $error) && $ignoreCode != '1'
                ) {
                    return array(
                            'errno'  => -1007,
                            'errmsg' => $error,
                    );
                }
                $phone_check_code = session('checkCode');
                if (function_exists('is_production') && !is_production() && $checkCode == '1111') {
                    // 为 测试环境 1111视为正确
                } else {
                    if (empty($phone_check_code) || verify_val($phone_check_code, 'number', $checkCode, '!=')) { // 手机验证码不正确
                        $this->ajaxReturn(-1009);
                    }
                    $add_time = get_val($phone_check_code, 'add_time', 0);
                    if (time() - $add_time > self::VERIFY_CODE_EXPIRE_TIME) { // 手机验证码已经过期
                        $this->ajaxReturn(-1010);
                    }
                }
            }
        }

        return $this->verifyCodeVerify();
    }

    /**
     *
     * @param $drawLotteryId
     *
     * @return string
     */
    private function generateDrawLotteryChanceInfoKey($drawLotteryId) {
        return 'drawLotteryChancesInfo:' . md5($this->id . ':' . $drawLotteryId);
    }

    /**
     * 获得剩余抽奖机会
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param int $drawLotteryId 手机号或者openid
     *
     * @return int|mixed
     */
    public function getLeftChances($drawLotteryId) {
        // 剩余抽奖机会
        $leftChances            = 0;
        $drawLotteryChancesInfo = $this->getDrawLotteryChanceInfo($drawLotteryId);
        if (isset($drawLotteryChancesInfo['leftChances'])) {
            $leftChances = $drawLotteryChancesInfo['leftChances'];
        }

        return $leftChances;
    }

    /**
     *
     * @param $drawLotteryId
     * @param $drawLotteryChanceInfo
     */
    public function setDrawLotteryChanceInfo($drawLotteryId, $drawLotteryChanceInfo) {
        $key = $this->generateDrawLotteryChanceInfoKey($drawLotteryId);
        session($key, $drawLotteryChanceInfo);
    }

    /**
     *
     * @param $drawLotteryId
     *
     * @return mixed
     */
    public function getDrawLotteryChanceInfo($drawLotteryId) {
        $key                    = $this->generateDrawLotteryChanceInfoKey($drawLotteryId);
        $drawLotteryChancesInfo = session($key);
        if (empty($drawLotteryChancesInfo)) { // session中还没有相关信息
            // 获取抽奖信息
            $cjResult         = $this->DrawLotteryModel->getCjInfo($this->marketInfo);
            $phoneCountPerDay = 0;
            if (isset($cjResult['data']['cj_rule']['phone_day_part'])) {
                $phoneCountPerDay = $cjResult['data']['cj_rule']['phone_day_part'];
            }

            $leftChances            = $this->DrawLotteryModel->getDrawLotteryLeftChancesPerDay($this->id,
                    $this->batch_type, $this->batch_id, $drawLotteryId, $phoneCountPerDay);
            $drawLotteryChancesInfo = array(
                    'leftChances'      => $leftChances,
                    'phoneCountPerDay' => $phoneCountPerDay,
            );
            $this->setDrawLotteryChanceInfo($drawLotteryId, $drawLotteryChancesInfo);
        }

        return $drawLotteryChancesInfo;
    }

    /**
     * 设置session中的剩余抽奖次数
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $drawLotteryId
     * @param $step
     */
    public function setTmpLeftChances($drawLotteryId, $step) {
        $drawLotteryChanceInfo = $this->getDrawLotteryChanceInfo($drawLotteryId);
        if (isset($drawLotteryChanceInfo['leftChances'])) {
            $drawLotteryChanceInfo['leftChances'] -= abs($step);
            if ($drawLotteryChanceInfo['leftChances'] < 0) {
                $drawLotteryChanceInfo['leftChances'] = 0;
            }
        }
        $this->setDrawLotteryChanceInfo($drawLotteryId, $drawLotteryChanceInfo);
    }

    /**
     * 验证参与码的有效性
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return array
     */
    public function verifyCodeVerify() {
        $needUpdateCodeVerify = false;
        $codeVerifyInfo       = array();
        $drawLotteryCheckFlag = I('post.cj_check_flag'); // 是否检查参与码
        $checkCode            = I('post.check_code'); // 参与码

        if ($drawLotteryCheckFlag == '1') {
            if ($checkCode == '') { // 参与码为空
                $this->ajaxReturn(-1036);
            }

            $where          = array(
                    'batch_id'    => $this->batch_id,
                    'batch_type'  => $this->batch_type,
                    'status'      => '0',
                    'verify_code' => strtolower($checkCode),
            );
            $codeVerifyInfo = $this->DrawLotteryModel->getCodeVerify($where);
            if ($codeVerifyInfo) {
                $needUpdateCodeVerify = true;
            } else {
                $this->ajaxReturn(-1036);
            }
        }

        return array(
                'needUpdate' => $needUpdateCodeVerify,
                'codeVerify' => $codeVerifyInfo,
        );
    }

    /**
     * 手机发送验证码
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function sendCheckCode() {
        $overdue = $this->checkDate();
        if ($overdue === false) { // 该活动不在有效期之内
            $this->showErrorByErrno(-1016);
        }
        $error  = null;
        $mobile = I('post.phone_no', null);
        if (!check_str($mobile, array(
                'null'    => false,
                'strtype' => 'mobile',
        ), $error)
        ) {
            $this->showErrorByErrno(-1008, null, $error);
        }

        // 发送频率验证
        $checkCode = session('checkCode');
        $oldMobile = get_val($checkCode, 'mobile', '');
        $add_time = get_val($checkCode, 'add_time', 0);
        if (!empty($checkCode) && $oldMobile == $mobile && (time() - $add_time) < self::VERIFY_CODE_EXPIRE_TIME) {
            $this->showErrorByErrno(-1017, null, time() - $add_time);
        }
        $num = mt_rand(1000, 9999);
        // 短信内容
        if (empty($this->MemberRecruitService)) {
            $this->MemberRecruitService = D('MemberRecruit', 'Service');
        }
        $node_id = get_val($this->marketInfo, 'node_id', 0);
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
                        'Recipients'    => array(
                                'Number' => $mobile,
                        ),  // 手机号
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

        $ret_msg = isset($respInfo['NotifyRes']['Status']) ? $respInfo['NotifyRes']['Status'] : array();
        if (!$respInfo || (verify_val($ret_msg,'StatusCode','0000',' != ') && verify_val($ret_msg, 'StatusCode', '0001', '!='))) {
            $this->showErrorByErrno(-1018);
        }
        $checkCode = array(
                'number'   => $num,
                'add_time' => time(),
                'mobile'   => $mobile,
        );
        session('checkCode', $checkCode);
        $this->success('验证码已发送');
    }

    /**
     * 以异步队列方式进行抽奖
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function submitQueue() {
        $id               = $this->id;
        $codeVerifyResult = $this->verifyDrawLotteryCondition(); // 验证抽奖条件 验证失败的话
        // 会直接在方法中返回并退出
        $mobile = I('post.mobile', null);

        session('verify_cj', null);

        // 校验是否存在
        import('@.Vendor.DrawLottery');
        $other = array();

        // 微信登录过
        $wxUserInfo = $this->getwxUserInfo();
        if ($wxUserInfo) {
            $other         = array(
                    'wx_open_id' => get_val($wxUserInfo, 'openid'),
                    'wx_nick'    => get_val($wxUserInfo, 'nickname'),
            );
            $drawLotteryId = $wxUserInfo['openid'];
        } else {
            $drawLotteryId = $mobile;
            $this->setDrawLotteryMobile($mobile);
        }

        // 统计
        $participationId           = $this->addParticipationLog($mobile);
        $other['participation_id'] = $participationId;
        $drawLottery               = new DrawLottery($id, $mobile, $this->fullId, null, $other);
        $resp                      = $drawLottery->sendCodeQueue();
        log_write('sendCodeQueue()' . print_r($resp, true));
        if ($resp['resp_id'] != '0000') { // 统一提示未中奖
            $this->responseJson(-1, self::NO_AWARD_TIP);

            return;
        }

        if ($codeVerifyResult['needUpdate'] === true) { // 需要更新参与码信息
            $this->DrawLotteryModel->updateCodeVerifyData(array(
                    'id' => $codeVerifyResult['codeVerify']['id'],
            ), array(
                    'status' => '1',
            ));
        }

        $respData = $resp['data'];
        $data     = array(
                'msgid' => $respData['key'],
                'url'   => U('getDrawLotteryResult', array(
                        'key' => $respData['key'],
                )),
        );
        $this->setTmpLeftChances($drawLotteryId, 1); // 修改剩余抽奖次数

        $this->userCookieMobile($mobile); // 将手机号记录到cookie中


        // for 中奖记录 start
        if (empty($this->DrawLotteryCommonService)) {
            $this->DrawLotteryCommonService = D('DrawLotteryCommon', 'Service');
        }
        $this->DrawLotteryCommonService->setMobileAndGobackUrl($id, $other, $mobile);
        // for 中奖记录 start

        $this->responseJson('-1001', 'success', $data);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $mobile
     */
    public function setDrawLotteryMobile($mobile) {
        $key = $this->DrawLotteryCommonService->generateDrawLotteryMobileCookieKey($this->id);
        cookie($key, $mobile, array(
                'expire' => '31536000',
        )); // 一年有效期
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
    public function addParticipationLog($mobile) {
        // 统计
        import("@.Vendor.Statistics");

        $batchTraceId = session('batchTraceId');
        if (empty($batchTraceId)) {
            $batchTraceId = 0;
        }
        $insertData = array(
                'label_id'       => $this->id,
                'node_id'        => $this->node_id,
                'batch_type'     => $this->batch_type,
                'batch_id'       => $this->batch_id,
                'channel_id'     => $this->channel_id,
                'join_mode'      => $this->join_mode,
                'full_id'        => $this->fullId,
                'batch_trace_id' => $batchTraceId,
                'mobile'         => $mobile,
        );

        return Statistics::addParticipationLog($insertData);
    }

    /**
     * 查看抽奖记录
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function awardList() {
        $this->cacheControl();
        $rememberedMobile = $this->getDrawLotteryUid($this->id);
        $awardList        = $this->DrawLotteryModel->getAwardList(array(
                'mobile'     => $rememberedMobile,
                'batch_id'   => $this->marketInfo['id'],
                'batch_type' => $this->marketInfo['batch_type'],
        ));

        $wechatCard     = $this->DrawLotteryCommonService->getUnfetchedWechatCard($awardList);
        $finalAwardList = $this->DrawLotteryCommonService->formatAwardList($awardList, $wechatCard);

        $this->assign('awardList', $finalAwardList);
        $this->assign('id', $this->id);

        $this->display();
    }

    /**
     * 获取微信登录用户信息
     *
     * @return mixed
     */
    public function getWxUserInfo() {
        if (empty($this->WeiXinCardService)) {
            $this->WeiXinCardService = D('WeiXinCard', 'Service');
        }

        return $this->WeiXinCardService->getWxUserInfo($this->node_id);
    }

    /**
     * 获取剩余次数
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return int
     */
    public function getDrawLotteryLeftChances() {
        $mobile      = $this->getDrawLotteryUid($this->id);
        $leftChances = $this->getLeftChances($mobile);
        $json        = array(
                'leftChances' => $leftChances,
        );
        $this->ajaxReturn($json, 'JSON');
    }

    /**
     * 查询调用抽奖异步结果
     */
    public function getDrawLotteryResult() {
        import("@.Vendor.CjInterface");
        $cjInterface = new CjInterface();
        $key         = I('get.key', I('post.key'));
        $result = $cjInterface->getCjResultByKey($key);

        if (!$result) {
            $this->responseJson(-1001, 'waiting');

            return;
        }
        log_write('result:' . var_export($result, true));
        if (verify_val($result, 'resp_id','0000','!= ')){ // 如果是被限制都统一叫未中奖
            $noAwardNoticeMsg = self::NO_AWARD_TIP;
            $code             = get_val($result, 'resp_id');
            if ($code == '1012') { // 需要会员
                $code = -1060;
            }
            $this->responseJson($code, $noAwardNoticeMsg);

            return;
        }

        $goods_id = $result['resp_data']['rule_id'];
        // $bonus_id = $result['resp_data']['bonus_use_detail_id'];
        $where = "a.id='{$goods_id}'";

        $goodsDefaultInfo = array(
                'goods_name' => '',
                'goods_id'   => '',
                'goods_type' => '',
                'bonus_id'   => '',
                'link_url'   => '',
                'num' => 0,
        );

        $goodsInfo = $this->DrawLotteryModel->getGoodsInfoAndBounsInfo($where);
        //$goodsInfo['node_name']替换成活动设置的node_name
        $goodsInfo = $this->DrawLotteryModel->mergeGoodsInfoByBid($goodsInfo);
        $goodsInfo = array_merge($goodsDefaultInfo, $goodsInfo);
        $goodsInfo['goods_image'] = get_upload_url($goodsInfo['goods_image']);
        if (issetAndNotEmpty($result['resp_data'], 'integral_get_id')) { //积分
            $integalGetDetail      = $this->IntegalGetDetailModel->getIntegalGetDetail(
                    ['id' => get_val($result['resp_data'], 'integral_get_id')],
                    BaseModel::SELECT_TYPE_ONE
            );
            $goodsInfo['num']      = get_val($integalGetDetail, 'integral_num', 0);
        }
        // 中了手机凭证奖品
        $resp = get_val($result, 'resp_data');
        if (!empty($resp['request_id']) || (verify_val($resp, 'prize_type', '4') && verify_val($resp, 'integral_get_flag', '0'))) {
            log_write(print_r($resp, true));
            // 临时中奖码，需要响应给前台，校验一下正确性用的，校验正确后失效,以免直接暴露 request_id和 cj_trace_id,用完以后清空
            $cj_code = time() . mt_rand(100, 999);
            session(
                    '_TmpChouJian_',
                    array(
                            'cj_code'         => $cj_code,
                            'request_id'      => $resp['request_id'],
                            'cj_trace_id'     => $resp['cj_trace_id'],
                            'card_ext'        => $resp['card_ext'],
                            'card_id'         => $resp['card_id'],
                            'goods_info'      => $goodsInfo,
                            'prize_type'      => get_val($resp, 'prize_type'),
                            'integral_get_id' => get_val($resp, 'integral_get_id'),
                    )
            );
            $result['resp_data']['cj_code'] = $cj_code;
        }
        // 返回结果
        $result['resp_data']['goods_info'] = $goodsInfo;
        log_write('$result[\'resp_data\']:' . print_r($result['resp_data'], true));

        $formatedInfo = $this->formatDrawLotteryResult($result);
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
    public function formatDrawLotteryResult($result) {
        $finalMsg = array(
                'status' => '',
                'msg'    => '',
                'data'   => '',
        );
        if (verify_val($result, 'resp_id', '0000', '==')) { // 已中奖
            $finalMsg['status'] = 0; // 成功
            $finalMsg['data']   = get_val($result, 'resp_data');
            if (isset($result['resp_data']['card_id']) && $result['resp_data']['card_id']) { // 微信卡券
                $finalMsg['msg'] = isset($result['resp_data']['goods_info']['goods_name']) ? $result['resp_data']['goods_info']['goods_name'] : '';
            } else if (isset($result['resp_data']['goods_info']['bonus_id']) && $result['resp_data']['goods_info']['bonus_id']) { // 红包
                $finalMsg['msg'] = isset($result['resp_data']['goods_info']['goods_name']) ? $result['resp_data']['goods_info']['goods_name'] : '';
                $finalMsg['msg'] .= '， 请到' . $result['resp_data']['goods_info']['node_name'] . '使用';
            } else { // 卡券
                $finalMsg['msg'] = isset($result['resp_data']['goods_info']['goods_name']) ? $result['resp_data']['goods_info']['goods_name'] : '';

                if($result['resp_data']['goods_info']['node_id'] == C('szpa.node_id')){
                    $finalMsg['msg'] .= '，中奖信息将以短信或者微信的形式通知您，请留意查看。';
                } else {
                    $finalMsg['msg'] .= '， 中奖凭证将自动下发至您的手机，请注意查收!';
                }
            }
        } else { // 未中奖
            $finalMsg = array(
                    'status' => get_val($result, 'resp_id'),
                    'msg'    => self::NO_AWARD_TIP,
                    'data'   => '',
            );
        }

        return $finalMsg;
    }
}