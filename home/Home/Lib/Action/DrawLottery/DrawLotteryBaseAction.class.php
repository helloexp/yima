<?php

/**
 * 抽奖活动基础类 @auther Jeff Liu<liuwy@imageco.com.cn> Class DrawLotteryBaseAction
 */
class DrawLotteryBaseAction extends BaseAction
{

    public $id;

    public $batch_id;

    public $batch_type;

    public $channel_id;

    public $node_id;

    public $node_name;

    public $fullId;

    public $returnCommissionFlag;

    public $marketInfo;

    public $channelInfo;// 渠道信息
    public $wxUserInfo = array(); // 当前微信粉丝信息
    public $needWxLogin = 0;// 设置是否微信登录 0否1是
    public $shop_mid;

    public $nodeCfg;

    public $join_mode;// 参与方式

    /**
     *
     * @var DrawLotteryModel
     */
    protected $DrawLotteryModel;

    // 付满送参数
    public $payToken;

    /**
     *
     * @var WeiXinQrcodeService
     */
    protected $WeiXinQrcodeService;

    /**
     *
     * @var TweixinInfoModel
     */
    protected $TweixinInfoModel;

    /**
     *
     * @var CjTraceModel
     */
    protected $CjTraceModel;

    /**
     *
     * @var TbarcodeTraceModel
     */
    protected $TbarcodeTraceModel;

    /**
     *
     * @var BonusUseDetailModel
     */
    protected $BonusUseDetailModel;

    /**
     *
     * @var IntegalGetDetailModel
     */
    protected $IntegalGetDetailModel;

    /**
     *
     * @var MemberInstallModel
     */
    protected $MemberInstallModel;

    /**
     *
     * @var DrawLotteryCommonService
     */
    protected $DrawLotteryCommonService;

    /**
     * @var SendAwardTraceModel
     */
    protected $SendAwardTraceModel;

    /**
     * @var ChannelModel
     */
    public $ChannelModel;

    public function __construct()
    {
        $this->DrawLotteryModel         = D('DrawLottery'); // 这个位置不能移动，否则会报错的
        $this->DrawLotteryCommonService = D('DrawLotteryCommon', 'Service');
        parent::__construct();
    }

    /**
     * 过滤抽奖
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function filterDrawLottery()
    {
        $isPreviewChannel = $this->isPreviewChannel($this->node_id);
        if ($isPreviewChannel) {
            $this->showErrorByErrno(-1048);
        }
    }


    /**
     * 为报错页面构建所需数据 （从_initialize提取出来，这些数据不是每次请求都需要请求的。。）
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function buildErrorData()
    {
        $id = I('get.id', I('post.id'), 'trim');
        if (empty($id)) {
            $id = session('id');
        }

        $map    = ['id' => $id];
        $result = $this->DrawLotteryModel->getBatchChannel($map, BaseModel::SELECT_TYPE_ONE);

        // 查询活动
        $map        = ['id' => $result['batch_id'], 'batch_type' => $result['batch_type']];
        $marketInfo = $this->DrawLotteryModel->getMarketInfo($map);

        // 获取logo用于报错页面的显示,如果有活动logo用活动logo,如果没有用机构的logo
        $errorNodeLogo   = '';
        $showErrorRemind = 0;
        if (!empty($result['node_id'])) {
            $nodeInfo = $this->DrawLotteryModel->getNodeInfo(
                    ['node_id' => $result['node_id']],
                    BaseModel::SELECT_TYPE_ONE,
                    'head_photo,node_name'
            );
            // 报错页面的活动图片
            if (!empty($marketInfo) && !empty($marketInfo['log_img'])) {
                $errorImgUrl = $marketInfo['log_img'];
            } else {
                $errorImgUrl = $nodeInfo['head_photo'];
            }
            $errorNodeLogo = get_upload_url($errorImgUrl);
            $weixinInfo    = $this->DrawLotteryModel->getWeixinInfo(['node_id' => $result['node_id']]);
            if ($weixinInfo) {
                $this->assign('weixin_code', $weixinInfo['weixin_code']);
                $qrSource = $this->_getWxQr($weixinInfo);
                $this->assign('qrSource', urlencode($qrSource)); // 微信二维码
            }
        }
        $this->assign('showErrorRemind', $showErrorRemind);
        $this->assign('errorNodeLogo', $errorNodeLogo);
    }

    /**
     * 显示错误信息
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param int     $errno
     * @param boolean $isAjax
     * @param string  $extraMsg
     */
    public function showErrorByErrno($errno, $isAjax = false, $extraMsg = '')
    {
        $this->buildErrorData();
        parent::showErrorByErrno($errno, $isAjax, $extraMsg);
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param string $message
     * @param string $jumpUrl
     * @param null   $ajax
     */
    protected function error($message = '', $jumpUrl = '', $ajax = null)
    {
        $this->buildErrorData();
        parent::error($message, $jumpUrl, $ajax);
    }

    /**
     * 判断当前是否为post请求
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return bool
     */
    public function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * 判断当前是否为get请求
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return bool
     */
    public function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }


    public function _initialize()
    {
        import('@.Vendor.DebugHelper');
        import('@.Vendor.CommonConst');

        $this->ChannelModel = D('Channel');

        // 标签
        $id = I('get.id', I('post.id'), 'trim');
        if (empty($id)) {
            $id = session('id');
        }
        $this->assign('jb_label_id', $id);
        if (empty($id)) {
            $this->showErrorByErrno(-1006);
        }
        import('@.Vendor.CheckStatus');
        $checkStatusObj       = new CheckStatus();
        $resp_msg             = $checkStatusObj->checkId($id);
        $this->checkStatusObj = $checkStatusObj;
        // 获取logo用于报错页面的显示,如果有活动logo用活动logo,如果没有用机构的logo
        $nodeName = '';
        if (!empty($checkStatusObj->nodeId)) {
            $nodeInfo = $this->DrawLotteryModel->getNodeInfo(
                    ['node_id' => $checkStatusObj->nodeId],
                    BaseModel::SELECT_TYPE_ONE,
                    'head_photo,node_name'
            );
            $nodeName = $nodeInfo['node_name']; // 报错页的机构号名字
        }
        $this->assign('node_name', $nodeName);

        if ($resp_msg !== true) {
            $this->error($resp_msg);
        }
        $batchChannelInfo  = $checkStatusObj->batchChannelInfo;
        $marketInfo        = $checkStatusObj->marketInfo;
        $this->channelInfo = $checkStatusObj->channelInfo;
        $this->join_mode   = $checkStatusObj->marketInfo['join_mode'];
        $this->id          = $id;
        $this->batch_id    = $batchChannelInfo['batch_id'];
        $this->channel_id  = $batchChannelInfo['channel_id'];
        $this->batch_type  = $batchChannelInfo['batch_type'];
        $this->node_id     = $batchChannelInfo['node_id'];
        $this->node_name   = get_node_info($this->node_id, 'node_name');
        $this->nodeCfg     = get_node_info($this->node_id, 'cfg_data');
        $full_id           = I('full_id', '', 'trim');

        if (empty($full_id)) {
            $full_id = $id;
        } else {
            $full_id  = $full_id . ',' . $id;
            $full_arr = explode(',', $full_id);
            if (in_array($id, $full_arr)) {
                $full_arr = array_unique($full_arr);
                $full_id  = implode(',', $full_arr);
            }
        }
        $this->fullId = $full_id;

        // 奖品设置会员专享,获取所选粉丝招募活动label_id,默认获取最后发布的
        if ($marketInfo['member_reg_mid']) {
            $map              = ['batch_id' => $marketInfo['member_reg_mid'], 'status' => '1'];
            $memberRegLabelId = $this->DrawLotteryModel->getBatchChannel(
                    $map,
                    BaseModel::SELECT_TYPE_FIELD,
                    'id',
                    'add_time desc'
            );
        } else {
            $memberRegLabelId = '';
        }
        $this->marketInfo = $marketInfo;

        $this->returnCommissionFlag = 0;
        $this->assign('member_reg_label_id', $memberRegLabelId);
        $this->assign('label_batch_id', $this->batch_id);
        $this->assign('full_id', $full_id);
        $this->assign('node_id', $this->node_id);
        $this->assign('node_name', $this->node_name);
        $this->assign('node_service_hotline', get_node_info($this->node_id, 'node_service_hotline'));
        $this->assign('return_commission_flag', $this->returnCommissionFlag);
        $this->assign('wx_share_config', $this->setShareConfig());
        $this->assign('join_mode', (int)$this->marketInfo['join_mode']);
        $this->assign('islogin', 0);
        $this->assign('node_cfg', $this->nodeCfg);
    }

    /**
     * 判断是抽奖预览时间是否过期
     *
     * @return bool
     */
    public function checkCjEndtime()
    {
        $id        = I('get.id', I('post.id'), 'trim');
        $batchInfo = $this->DrawLotteryModel->getBatchChannel(
                ['id' => $id],
                BaseModel::SELECT_TYPE_ONE,
                'channel_id, end_time'
        );
        $snsType   = $this->DrawLotteryModel->getChannel(
                ['id' => $batchInfo['channel_id']],
                BaseModel::SELECT_TYPE_ONE,
                'sns_type'
        );
        if (CommonConst::SNS_TYPE_PREVIEW == $snsType['sns_type']) {
            if (date('YmdHis') > $batchInfo['end_time']) {
                return false;
            }
        }
        return true;
    }

    /**
     * 校验活动是否过期
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param bool|false $showFlag
     *
     * @return bool
     */
    public function checkDate($showFlag = false)
    {
        if ($showFlag) {
            // 查询该渠道是否为首页渠道，如果首页渠道不用校验
            if ($this->channelInfo['sns_type'] == CommonConst::SNS_TYPE_HOME && $this->channelInfo['type'] == '1') {
                return true;
            }

            // 判断是O2O案例渠道的，活动状态停用可访问，渠道取消不可访问
            if ($this->channelInfo['sns_type'] == CommonConst::SNS_TYPE_O2O) {
                return true;
            }
        }
        if (!empty($this->marketInfo['start_time']) && !empty($this->marketInfo['end_time'])) {
            $this_time = date('YmdHis');
            if ($this_time < $this->marketInfo['start_time'] || $this_time > $this->marketInfo['end_time']) {
                return false;
            }
        }
        return true;
    }


    /**
     * 生成wap页分享的wxconfig配置数据
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return array
     */
    protected function setShareConfig()
    {
        $appId     = C('WEIXIN.appid'); // 应用ID
        $appSecret = C('WEIXIN.secret'); // 应用密钥
        $nonceStr  = 'imageco';
        $timestamp = time();
        $ticket    = S('wx_share_ticket');
        if (!$ticket) {
            $token  = getToken($appId, $appSecret);
            $ticket = getTicket($token);
            S('wx_share_ticket', $ticket, 5000);
        }
        $str = [
                'noncestr'     => $nonceStr,
                'jsapi_ticket' => $ticket,
                'url'          => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                'timestamp'    => $timestamp
        ];
        ksort($str);
        $val       = urldecode(http_build_query($str));
        $signature = sha1($val);
        return array(
                'appId'     => $appId,
                'timestamp' => $timestamp,
                'noncestr'  => $nonceStr,
                'signature' => $signature,
                'ticket'    => $ticket,
                'url'       => $str['url']
        );
    }

    /**
     * 校验访问用户类型
     *
     * @param boolean $autoLogin 是否直接登录
     */
    public function _checkUser($autoLogin = false)
    {
        $joinMode       = $this->marketInfo['join_mode'];
        $memberBatchId  = $this->marketInfo['member_batch_id'];
        $memberJoinFlag = $this->marketInfo['member_join_flag'];
        // 判断是否有微信卡券的奖品，是的话，就需要微信登录
        $this->needWxLogin = $joinMode ? 1 : 0;

        if (!$joinMode) {
            $result = $this->DrawLotteryModel->getBatchInfo(
                    ['m_id' => $this->marketInfo['id'], '_string' => "ifnull(card_id, '') != ''"],
                    BaseModel::SELECT_TYPE_FIELD,
                    'id'
            );
            if ($result) {
                $this->needWxLogin = 1;
                log_write("有微信卡券" . M()->_sql(), 'SQL');
            }
        }
        // 如果是微信用户才能参加
        if ($this->needWxLogin) {
            $this->_loginByWeixin($autoLogin);
            // 查询用户
            $wxOpenid = $this->wxSess['openid'];
            if (!$wxOpenid) {
                log_write('wx_openid is null');
                $this->error("只有微信用户才能参加该活动");
            }
            $where    = ['openid' => $wxOpenid, 'node_id' => $this->node_id, 'subscribe' => ['neq', '0']];
            $userInfo = $this->DrawLotteryModel->getWxUser($where);
            if (empty($userInfo)) {
                $userInfo = array();
            }
            // 判断是否粉丝或者会员
            if ($joinMode && $memberJoinFlag) {
                if (!$userInfo) {
                    log_write("只有微信粉丝才能参加该活动");
                    if ($this->marketInfo['fans_collect_url']) {
                        redirect($this->marketInfo['fans_collect_url']);
                    }
                    $this->error("只有微信粉丝才能参加该活动");
                }
                if ($memberBatchId != -1) {
                    $member_batch_arr = explode(',', $memberBatchId);
                    if (!in_array($userInfo['group_id'], $member_batch_arr)) {

                        // 深圳平安非标提示
                        if($this->marketInfo['id'] == C('szpa.ecupLotteryBatchId')) {
                            $this->error("对不起，您不满足本次活动的参与条件。参与任意一场竞猜即可抽奖！");
                            exit;
                        }

                        $this->error("对不起，您不满足本次活动的参与条件。更多活动请关注我们的活动通知，谢谢您的参与！");
                    }
                }
            }
            $this->wxUserInfo = array_merge($userInfo, $this->wxSess);
            $this->assign('islogin', 1);
        }
    }

    /**
     * 在调用index前的跳转
     */
    public function _before_index()
    {
        // 判断是否有重定向的地址
        $id = 0;
        if (ACTION_NAME == 'index') { // 访问量
            import('@.Vendor.DataStat');
            $opt = new DataStat($this->id, $this->fullId);
            $id  = $this->id;
            $opt->recordSeq();
            session('batchTraceId', $opt->batchTraceId);
        }

        if (!empty($this->marketInfo['redirect_url'])) {
            // 要防止重复定向
            $redirect_url = str_replace(['{$id}'], [$id], $this->marketInfo['redirect_url']);
            redirect($redirect_url);
            return;
        }
    }

    /**
     *
     * @param      $code
     * @param      $msg
     * @param null $data
     */
    protected function responseJson($code, $msg, $data = null)
    {
        $resp = ['code' => $code, 'msg' => $msg, 'data' => $data];
        echo json_encode($resp);
        exit();
    }

    /**
     *
     * @param $weixinInfo
     *
     * @return string
     */
    private function _getWxQr($weixinInfo)
    {
        if ($weixinInfo['account_type'] != '4') {
            return '';
        }
        $arr = json_decode($weixinInfo['setting'], true);
        if (isset($arr['qrUrl'])) {
            return $arr['qrUrl'];
        } else {
            // 去微信获取token
            if (empty($this->WeiXinQrcodeService)) {
                $this->WeiXinQrcodeService = D('WeiXinQrcode', 'Service');
            }
            $this->WeiXinQrcodeService->init(
                    $weixinInfo['app_id'],
                    $weixinInfo['app_secret'],
                    $weixinInfo['app_access_token']
            );
            // 自增确定，以后就用这个场景号作为这个商家的固定二维码，存入setting的json字段
            if (empty($this->TweixinInfoModel)) {
                $this->TweixinInfoModel = D('TweixinInfo');
            }
            $sceneId = $this->TweixinInfoModel->getSceneId($weixinInfo['node_id']);
            if (!$sceneId) {
                return ''; // 如果没有获取到自增场景的id，返回空字符串
            } else {
                // 去微信接口获取图片内容
                $qrResult = $this->WeiXinQrcodeService->getQrcodeImg(['scene_id' => $sceneId]);
                // 更新accessToken
                if ($weixinInfo['app_access_token'] != $this->WeiXinQrcodeService->accessToken) {
                    if (empty($this->TweixinInfoModel)) {
                        $this->TweixinInfoModel = D('TweixinInfo');
                    }
                    $this->TweixinInfoModel->where(['node_id' => $weixinInfo['node_id']])->save(
                            ['app_access_token' => $this->WeiXinQrcodeService->accessToken]
                    );
                }
                // 如果失败
                if ($qrResult['status'] != '1') {
                    log_write(
                            '获取推广二维码失败，原因：' . $qrResult['errcode'] . ':' . $qrResult['errmsg']
                    );
                    return '';
                } else {
                    $arr['qrUrl'] = $qrResult['img_url'];
                    $setting      = json_encode($arr);
                    $this->DrawLotteryModel->updatetWeixinInfo(['id' => $weixinInfo['id']], ['setting' => $setting]);
                    return $qrResult['img_url'];
                }
            }
        }
    }

    /**
     * 设置，获取全局手机号
     *
     * @param null $mobile
     *
     * @return mixed|null
     */
    public function userCookieMobile($mobile = null)
    {
        if ($mobile !== null) {
            cookie('_global_user_mobile', $mobile, 3600 * 24 * 365);
            return $mobile;
        }
        return cookie('_global_user_mobile');
    }

    protected $cjTraceInfo = array();

    protected $cjTraceId = 0;

    public function verifyGetAwardCondition()
    {
        $this->cjTraceId = I('cj_trace_id');
        if (empty($this->cjTraceId)) {
            $this->error('数据丢失');
        }
        if (empty($this->CjTraceModel)) {
            $this->CjTraceModel = D('CjTrace');
        }
        $cjTraceInfo = $this->CjTraceModel->getCjTrace(['id' => $this->cjTraceId]);
        if (empty($cjTraceInfo)) {
            $this->error("数据丢失");
        }

        $this->cjTraceInfo = $cjTraceInfo[0];
        $cjPhone           = $this->DrawLotteryCommonService->getMobileForAwardList($this->id);
        if (empty($cjPhone)) {
            $cjPhone = $this->getDrawLotteryUid($this->id);
        }
        if (isset($this->cjTraceInfo['mobile']) && $this->cjTraceInfo['mobile'] != $cjPhone) { // 不属于当前用户
            $this->error("数据不正确");
        }
    }

    private function getGoodsInfoByGoodsId($goodsId)
    {
        return M('tgoods_info')->where(['goods_id' => $goodsId])->find();
    }

    private function getBatchInfoById($bId)
    {
        return M('tbatch_info')->where(['id' => $bId])->find();
    }

    /**
     * 根据抽奖码发送奖品(手机号)
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function getPrize()
    {
        log_write(var_export($_REQUEST, 1));
        $phone = I('phone');
        if (!$phone) {
            $this->error('手机号不能为空');
        }

        // 校验一下中奖码是否正确
        $this->verifyGetAwardCondition();
        if (isset($this->cjTraceInfo['send_mobile']) && $this->cjTraceInfo['send_mobile'] != '13900000000') { // 已经领取过了
            $this->error('不能重复领取');
        }

        $requestId = I('request_id');
        // 修改数据库中的手机号字段，并且调用重发接口
        if (empty($this->CjTraceModel)) {
            $this->CjTraceModel = D('CjTrace');
        }
        $result = $this->CjTraceModel->where(['id' => $this->cjTraceId])->save(['send_mobile' => $phone]);
        log_write(
                'CjTraceModel:' . $this->CjTraceModel->_sql() . ':' . var_export($result, 1)
        );
        if ($result) {
            if (empty($this->SendAwardTraceModel)) {
                $this->SendAwardTraceModel = D('SendAwardTrace');
            }
            $sendAwardTrace = $this->SendAwardTraceModel->getByRequestId($requestId);
            $goodsId = isset($this->cjTraceInfo['g_id']) ? $this->cjTraceInfo['g_id'] : '';
            if (empty($goodsId)) {
                $bId = isset($this->cjTraceInfo['b_id']) ? $this->cjTraceInfo['b_id'] : '';
                if ($bId) {
                    $batchInfo = $this->getBatchInfoById($bId);
                    $goodsId = isset($batchInfo['goods_id']) ? $batchInfo['goods_id'] : '';
                }
            }
            if ($goodsId) {
                $goodsInfo = $this->getGoodsInfoByGoodsId($goodsId);
            } else {
                $this->error('商品信息获取失败');
            }

            if (isset($goodsInfo['goods_type']) && ($goodsInfo['goods_type'] == '7' || $goodsInfo['goods_type'] == '8')) {
                $result = $this->SendAwardTraceModel->updatePhonenoAndStatus(
                        ['deal_flag' => 1, 'phone_no' => $phone],
                        ['request_id' => $requestId]
                );
                if ($result) {
                    $this->success("领奖成功");
                } else {
                    $this->success("领奖失败");
                }
            } else if ($sendAwardTrace['trans_type'] == 3) {
                $result = $this->SendAwardTraceModel->updatePhonenoAndStatus(
                        ['deal_flag' => 1, 'phone_no' => $phone],
                        ['request_id' => $requestId]
                );
                if ($result) {
                    $this->success("领奖成功");
                } else {
                    $this->success("领奖失败");
                }

            } else {
                // 修改发码表的字段
                if (empty($this->TbarcodeTraceModel)) {
                    $this->TbarcodeTraceModel = D('TbarcodeTrace');
                }
                $result = $this->TbarcodeTraceModel->updatePhone(['request_id' => $requestId], $phone);

                if ($result) {
                    // 然后调用重发接口
                    import("@.Vendor.CjInterface");
                    $req    = new CjInterface();
                    $result = $req->cj_resend(
                            ['request_id' => $requestId, 'node_id' => $this->node_id, 'user_id' => '00000000']
                    );
                    if (!$result || $result['resp_id'] != '0000') {
                        $this->error("领奖失败");
                    }
                    log_write("领奖成功");
                    $this->success("领奖成功");
                } else {
                    $this->error("领奖失败");
                }
            }
        }
    }

    /**
     * 获取积分
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function getIntegal()
    {
        $this->verifyGetAwardCondition();

        $integalGetId  = I('post.integal_get_id');
        $integalNodeId = I('post.integal_node_id');
        $phone         = I('post.phone', I('post.mobile'));
        if (!$phone) {
            $this->error("手机号不能为空");
        }
        if (!$integalGetId) {
            $this->error("系统正忙");
        }
        if (empty($this->IntegalGetDetailModel)) {
            $this->IntegalGetDetailModel = D('IntegalGetDetail');
        }
        $integalGetDetail = $this->IntegalGetDetailModel->getIntegalGetDetail(
                ['id' => $integalGetId],
                BaseModel::SELECT_TYPE_ONE
        );
        if (!$integalGetDetail || $integalGetDetail['node_id'] != $integalNodeId) {
            $this->error("奖品数据错误");
        }

        if (empty($this->MemberInstallModel)) {
            $this->MemberInstallModel = D('MemberInstall');
        }
        $result = $this->MemberInstallModel->receiveIntegal($integalNodeId, $integalGetId, $phone);
        if ($result === false) {
            $this->error('领取失败');
        } else {
            $this->success("领奖成功");
        }
    }

    /**
     * 获取红包
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function getBonus()
    {
        file_debug($_REQUEST, '$_REQUEST', 'get.log');
        $this->verifyGetAwardCondition();

        $bonusUseDetailId = I('bonus_use_detail_id');
        $phone            = I('phone');
        if (!$phone) {
            $this->error("手机号不能为空");
        }
        if (!$bonusUseDetailId) {
            $this->error("系统正忙");
        }
        if (empty($this->BonusUseDetailModel)) {
            $this->BonusUseDetailModel = D('BonusUseDetail');
        }
        $bonusUseDetail = $this->BonusUseDetailModel->getBonusUseDetail(
                ['id' => $bonusUseDetailId],
                BaseModel::SELECT_TYPE_ONE
        );
        if (!$bonusUseDetail) {
            $this->error("奖品数据错误");
        }
        $result = $this->BonusUseDetailModel->updateBonusPhone(
                ['id' => $bonusUseDetailId, 'phone' => $this->cjTraceInfo['mobile']],
                $phone
        );
        if ($result === false) {
            $this->error('领取失败');
        } else {
            if ($result === 0) {
                $this->error('不能重复领取');
            } else {
                $this->success("领奖成功");
            }
        }
    }

    /**
     * 获得id（唯一标示，可能为mobile，也可能是openid）
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return array|mixed|null|string
     */
    public function getDrawLotteryUid($id)
    {
        if ($this->marketInfo['join_mode'] == 1) { // 微信参与 获得openid
            $wxUserInfo     = $this->getWxUserInfo();
            $drawLotteryUid = $wxUserInfo['openid'];
        } else { // 手机号参与 获得已经参与过的手机号
            $drawLotteryUid = $this->DrawLotteryCommonService->getDrawLotteryMobile($id);
        }
        return $drawLotteryUid;
    }

    /**
     * 禁止浏览器缓存
     */
    protected function cacheControl()
    {
        if (!headers_sent()) {
            header('pragma:no-cache');
            header('Cache-Control:no-store, must-revalidate');
            header('expires:0');
        }
        $cacheControl = '<META HTTP-EQUIV="pragma" CONTENT="no-cache">
<META HTTP-EQUIV="Cache-Control" CONTENT="no-store, must-revalidate">
<META HTTP-EQUIV="expires" CONTENT="Wed, 26 Feb 1997 08:21:57 GMT">
<META HTTP-EQUIV="expires" CONTENT="0"> ';
        $this->assign('cacheControl', $cacheControl);
    }
}
