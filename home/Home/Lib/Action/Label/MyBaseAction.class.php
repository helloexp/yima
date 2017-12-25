<?php
/*
 * 营活活动基础类 @auther tr
 */
class MyBaseAction extends BaseAction {

    public $id;

    public $batch_id;

    public $batch_type;

    public $channel_id;

    public $node_id;

    public $node_name;

    public $full_id;

    public $return_commission_flag;

    public $marketInfo;

    public $channelInfo; // 渠道信息

    public $wxUserInfo = array();// 当前微信粉丝信息
    public $needWxLogin = 0;
    // 设置是否微信登录 0否1是
    public $shop_mid;

    public $node_cfg;
    // 付满送参数
    public $pay_token;

    /**
     *
     * @var WeiXinService
     */
    public $WeiXinService;

    /**
     *
     * @var DrawLotteryCommonService
     */
    private $DrawLotteryCommonService;

    /**
     * 判断当前用户是否已经登录
     *
     * @return int
     */
    public function isLogined($useWechat = true) {
        if (isset($_SESSION['onlineExper'])) {
            $islogin = 0;
        } elseif (isset($_SESSION['cjUserInfo'])) {
            $islogin = 1;
        } else if ($useWechat && isset($this->wxUserInfo['openid']) &&
             $this->wxUserInfo['openid']) {
            $islogin = 1;
        } else {
            $islogin = 0;
        }
        return $islogin;
    }

    /**
     * 跳转到授权页面
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param string $type 0:用户基础信息 1：用户完整信息
     * @param string $finalRedirectUrl 最终回调地址
     * @param string $apiCallbackUrl 微信授权callback地址
     */
    public function wechatAuthorizeAndRedirectByDefault($type = '', 
        $finalRedirectUrl = '', $apiCallbackUrl = '') {
        if (empty($type)) {
            $type = I('type', '0'); // 1是 基本信息
        }
        
        if (empty($apiCallbackUrl)) {
            $apiCallbackUrl = U('Label/MyBase/callback', 
                array(
                    'id' => $this->id, 
                    'type' => $type), '', '', true);
        }
        
        if (empty($finalRedirectUrl)) {
            $finalRedirectUrl = I('backurl', '', 'html_entity_decode');
        }
        
        if (empty($this->WeiXinService)) {
            $this->WeiXinService = D('WeiXin', 'Service');
        }
        
        $result = $this->WeiXinService->wechatAuthorizeAndRedirectById(
            $this->id, $type, $finalRedirectUrl, $apiCallbackUrl);
        if (isset($result['errmsg']) && $result['errmsg'] != '') {
            $this->error($result['errmsg']);
        }
    }

    /**
     * 跳转到授权页面
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param $appId
     * @param $appSecret
     * @param $isComponent
     * @param $apiCallbackUrl
     * @param $finalRedirectUrl
     * @param $type
     */
    public function wechatAuthorizeAndRedirectByDetailParam($appId, $appSecret, 
        $isComponent, $apiCallbackUrl, $finalRedirectUrl, $type) {
        if (empty($type)) {
            $type = I('type', '0'); // 1是 基本信息
        }
        
        if (empty($apiCallbackUrl)) {
            $apiCallbackUrl = U('Label/MyBase/callback', 
                array(
                    'id' => $this->id, 
                    'type' => $type), '', '', true);
        }
        
        if (empty($finalRedirectUrl)) {
            $finalRedirectUrl = I('backurl', '', 'html_entity_decode');
        }
        
        if (empty($this->WeiXinService)) {
            $this->WeiXinService = D('WeiXin', 'Service');
        }
        
        $this->WeiXinService->saveWechatAuthorBackurl($finalRedirectUrl);
        
        $result = $this->WeiXinService->wechatAuthorizeAndRedirectByDetailParam(
            $appId, $appSecret, $isComponent, $apiCallbackUrl, $type);
        if (isset($result['errmsg']) && $result['errmsg'] != '') {
            $this->error($result['errmsg']);
        }
    }

    /**
     * 跳转到授权页面
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param string $type 0:用户基础信息 1：用户完整信息
     * @param string $finalRedirectUrl 最终回调地址
     * @param string $apiCallbackUrl 微信授权callback地址
     */
    public function wechatAuthorizeByLabelId($labelId, $type = '', 
        $finalRedirectUrl = '', $apiCallbackUrl = '') {
        if (empty($type)) {
            $type = I('type', '0'); // 1是 基本信息
        }
        
        if ($labelId) {
            $labelId = $this->id;
        }
        
        if (empty($apiCallbackUrl)) {
            $apiCallbackUrl = U('Label/MyBase/callback', 
                array(
                    'id' => $labelId, 
                    'spay_token' => $this->pay_token, 
                    'type' => $type), '', '', true);
        }
        
        if (empty($finalRedirectUrl)) {
            $finalRedirectUrl = I('backurl', '', 'html_entity_decode');
        }
        
        if (empty($this->WeiXinService)) {
            $this->WeiXinService = D('WeiXin', 'Service');
        }
        
        $result = $this->WeiXinService->wechatAuthorizeAndRedirectById($labelId, 
            $type, $finalRedirectUrl, $apiCallbackUrl);
        if (isset($result['errmsg']) && $result['errmsg'] != '') {
            $this->error($result['errmsg']);
        }
    }

    /**
     * todo 有问题 如果是从 wechatAuthorizeByNodeId 过来 callback就有可能有问题 （有可能 id不存在）
     * 
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function callback() {
        log_write(print_r(I('get.'), true));
        $code = I('code', null);
        $type = I('type', '0');
        if (empty($code)) {
            $this->error('参数错误！');
        }
        
        $callbackResult = $this->WeiXinService->callbackAndRedirectById(
            $this->id, $code, $this->node_id, $type);
        
        if (is_array($callbackResult) && isset($callbackResult['errmsg']) &&
             $callbackResult['errmsg']) {
            $this->error($callbackResult['errmsg']);
        } else {
            if ($this->marketInfo['batch_type'] == '54') {
                $mysession = session('node_wxid_' . $this->node_id);
                // session('s_paytoken',I('spay_token'));
                $this->inTabel($mysession['openid'], $mysession['access_token'], 
                    $mysession);
            }
        }
    }

    public function _initialize() {
        $this->debugWechat();
        // 标签
        $id = I('get.id', I('post.id'), 'trim');
        if ($id == "") {
            $id = session("id");
        }
        
        $this->assign('jb_label_id', $id);
        
        import('@.Vendor.CheckStatus');
        $resp = new CheckStatus();
        $resp_msg = $resp->checkId($id);
        if ($resp_msg !== true) {
            $this->error($resp_msg);
        }
        
        $batchChannelInfo = $resp->batchChannelInfo;
        $marketInfo = $resp->marketInfo;
        $this->channelInfo = $resp->channelInfo;
        $this->id = $id;
        $this->batch_id = $batchChannelInfo['batch_id'];
        $this->channel_id = $batchChannelInfo['channel_id'];
        $this->batch_type = $batchChannelInfo['batch_type'];
        $this->node_id = $batchChannelInfo['node_id'];
        $this->node_name = get_node_info($this->node_id, 'node_name');
        $this->node_cfg = get_node_info($this->node_id, 'cfg_data');
        $full_id = I('full_id', '', 'trim');
        // 翼蕙宝
        if ($this->node_id == C('Yhb.node_id')) {
            $this->assign('is_yhb', true);
        }
        
        if (empty($full_id)) {
            $full_id = $id;
        } else {
            $full_id = $full_id . ',' . $id;
            $full_arr = explode(',', $full_id);
            $full_arr = array_map('intval', $full_arr);
            if (in_array($id, $full_arr)) {
                $full_arr = array_unique($full_arr);
                $full_id = implode(',', $full_arr);
            }
        }
        $this->full_id = $full_id;
        // 积分名称获取
        integralSetName($this->node_id);
        
        // 付满送pay_token检测
        $pay_token = I('pay_token', null);
        $this->pay_token = $pay_token;
        if ($this->pay_token) {
            $giveOrderInfo = M('tpay_give_order')->where(
                array(
                    'pay_token' => $pay_token))->find();
            if (! $giveOrderInfo)
                $this->error('付满送标签错误！');
            if ($giveOrderInfo['status'] != '1')
                $this->error('付满送标签失效！');
        }
        
        $this->marketInfo = $marketInfo;
        
        // 奖品设置会员专享,获取所选粉丝招募活动label_id,默认获取最后发布的
        if ($marketInfo['member_reg_mid']) {
            $map = array(
                'batch_id' => $marketInfo['member_reg_mid'], 
                'status' => '1');
            $memberRegLabelId = M('tbatch_channel')->where($map)->order(
                'add_time desc')->getField('id');
        } else {
            $memberRegLabelId = '';
        }
        
        $this->return_commission_flag = 0;
        $this->assign('member_reg_label_id', $memberRegLabelId);
        $this->assign('label_batch_id', $this->batch_id);
        $this->assign('full_id', $full_id);
        $this->assign('node_id', $this->node_id);
        $this->assign('node_name', $this->node_name);
        $this->assign('node_service_hotline', 
            get_node_info($this->node_id, 'node_service_hotline'));
        $this->assign('return_commission_flag', $this->return_commission_flag);
        $this->assign('wx_share_config', $this->setShareConfig());
        $join_mode = $this->marketInfo['join_mode'];
        if (! $join_mode && $this->marketInfo['batch_type']=='54') {
            $result = M('tbatch_info')->where(
                array(
                    'm_id' => $marketInfo['id'], 
                    '_string' => "ifnull(card_id, '') != ''"))->getField("id");
            if ($result) {
                $join_mode = 1;
            }
        }
        $this->assign('join_mode', $join_mode);
        $this->assign('islogin', 0);
        $this->assign('node_cfg', $this->node_cfg);
        $this->assign('pay_token', $this->pay_token);
        $this->WeiXinService = D('WeiXin', 'Service');
    }

    /**
     * Description of SkuService 积分名称获取
     *
     * @param string $nodeId //唯一商户ID
     * @return string
     * @author john_zeng
     */
    public function _integralSetName($nodeId) {
        // 积分名称获取
        $integralName = session('userSessIntegralName');
        if (! $integralName) {
            $integralName = M("tintegral_node_config")->where(
                array(
                    'node_id' => $nodeId))->getField('integral_name');
            session('userSessIntegralName', $integralName);
        }
        if ($integralName) {
            L('INTEGRAL_NAME', $integralName);
        } else {
            L('INTEGRAL_NAME', '积分');
        }
    }
    // 判断是抽奖预览时间是否过期
    public function checkCjEndtime() {
        $id = I('get.id', I('post.id'), 'trim');
        
        $batchInfo = M('tbatch_channel')->where(
            array(
                'id' => $id))->field('channel_id, end_time')->find();
        
        $snsType = M('tchannel')->where(
            array(
                'id' => $batchInfo['channel_id']))->field('sns_type')->find();
        
        if ('61' == $snsType['sns_type']) {
            if (date('YmdHis') > $batchInfo['end_time']) {
                return false;
            } else {
                return true;
            }
        }
    }

    function get_join_mode() {
        $join_mode = $this->marketInfo['join_mode'];
        if (! $join_mode) {
            $result = M('tbatch_info')->where(
                array(
                    'm_id' => $this->marketInfo['id'], 
                    '_string' => "ifnull(card_id, '') != ''"))->getField("id");
            if ($result) {
                $join_mode = 1;
            }
        }
        return $join_mode;
    }
    
    // 福满送特别处理分享领奖人数检查
    function paysend_before() {
        $mobile = I('post.mobile');
        if ($this->batch_type == '54') {
            if (! empty($_REQUEST['sharedid'])) {
                $rs = M('tshare_prize_info')->where(
                    array(
                        'id' => $_REQUEST['sharedid'], 
                        'node_id' => $this->node_id, 
                        'm_id' => $this->marketInfo['id'], 
                        'relation_id' => $this->pay_token, 
                        'batch_type' => '54'))->find();
                $mysession = session('node_wxid_' . $this->node_id);
                if (! empty($rs)) {
                    $count = M('tshare_prize_receive_trace')->where(
                        array(
                            'share_id' => $rs['id']))->count();
                    if ($count > 0)
                        $count = $count - 1;
                    if (! empty($this->marketInfo['defined_one_name']) &&
                         $count >= $this->marketInfo['defined_two_name']) {
                        $this->ajaxReturn('error', "分享活动已经结束了!", 0);
                    }
                }
                $_joinMode = $this->get_join_mode();
                if (! $_joinMode) {
                    $rs = M('tshare_prize_receive_trace')->where(
                        array(
                            'share_id' => $_REQUEST['sharedid'], 
                            'receive_phone' => $mobile))->find();
                } else {
                    $rs = M('tshare_prize_receive_trace')->where(
                        array(
                            'share_id' => $_REQUEST['sharedid'], 
                            'receive_wx_openid' => $this->wxSess['openid']))->find();
                }
                if (! empty($rs)) {
                    $respInfo = "您已参与过本次活动";
                    $status = '0';
                    $this->ajaxReturn($mark, $respInfo, $status);
                }
            }
        }
    }

    function paysend_cjafter($resp) {
        session('resp_cj_trace_id', $resp['cj_trace_id']);
        $mobile = I('post.mobile');
        // 付满送特别处理
        if ($this->batch_type == '54') {
            $mysession = session('node_wxid_' . $this->node_id);
            if (! empty($_REQUEST['sharedid'])) {
                $rs = M('tshare_prize_info')->where(
                    array(
                        'id' => $_REQUEST['sharedid'], 
                        'node_id' => $this->node_id, 
                        'm_id' => $this->marketInfo['id'], 
                        'relation_id' => $this->pay_token, 
                        'batch_type' => '54'))->find();
                if (! empty($rs)) {
                    M('tshare_prize_receive_trace')->add(
                        array(
                            'add_time' => date('YmdHis'), 
                            'share_id' => $rs['id'], 
                            'receive_phone' => $mobile, 
                            'receive_wx_openid' => $mysession['openid'] ? $mysession['openid'] : '', 
                            'cj_trace_id' => $resp['cj_trace_id']));
                }
            } else {
                $rs = M('tshare_prize_info')->where(
                    array(
                        'id' => session('mysharedid'), 
                        'node_id' => $this->node_id, 
                        'm_id' => $this->marketInfo['id'], 
                        // 'wx_open_id'=>$mysession['openid']?$mysession['openid']:'',
                        'relation_id' => $this->pay_token, 
                        'batch_type' => '54'))->find();
                if (! empty($rs)) {
                    M('tshare_prize_receive_trace')->add(
                        array(
                            'add_time' => date('YmdHis'), 
                            'share_id' => $rs['id'], 
                            'receive_phone' => $mobile, 
                            'receive_wx_openid' => $mysession['openid'] ? $mysession['openid'] : '', 
                            'cj_trace_id' => $resp['cj_trace_id']));
                }
            }
            $mysession = session('node_wxid_' . $this->node_id);
            $wxarr = M('twx_wap_user')->where(
                array(
                    'openid' => $mysession['openid']))->find();
            if (empty($wxarr) && ! empty($mysession))
                $in_arr = array(
                    'node_id' => $this->node_id, 
                    'label_id' => $this->id, 
                    'add_time' => date('YmdHis'), 
                    'nickname' => $mysession['nickname'], 
                    'sex' => $mysession['sex'], 
                    'province' => $mysession['province'], 
                    'city' => $mysession['city'], 
                    'headimgurl' => $mysession['headimgurl'], 
                    'openid' => $mysession['openid'], 
                    'access_token' => $mysession['access_token']);
            M('twx_wap_user')->add($in_arr);
        }
    }
    // 校验活动是否过期
    public function checkDate($showFlag = false) {
        $query_arr = $this->marketInfo;
        $channelInfo = $this->channelInfo;
        if ($showFlag) {
            // 查询该渠道是否为首页渠道，如果首页渠道不用校验
            if ($channelInfo['sns_type'] == '13' && $channelInfo['type'] == '1') {
                return true;
            }
            
            // 判断是O2O案例渠道的，活动状态停用可访问，渠道取消不可访问
            if ($channelInfo['sns_type'] == '12') {
                return true;
            }
        }
        if (! empty($query_arr['start_time']) && ! empty($query_arr['end_time'])) {
            
            $this_time = date('YmdHis');
            log_write('this_time:' . $this_time);
            log_write('start_time:' . $query_arr['start_time']);
            log_write('end_time:' . $query_arr['end_time']);
            if ($this_time < $query_arr['start_time'] ||
                 $this_time > $query_arr['end_time']) {
                return false;
            }
        }
        return true;
    }
    
    
    // 生成wap页分享的wxconfig配置数据
    protected function setShareConfig() {
        $appId = C('WEIXIN.appid'); // 应用ID
        $appSecret = C('WEIXIN.secret'); // 应用密钥
        $nonceStr = 'imageco';
        $timestamp = time();
        $ticket = S('wx_share_ticket');
        if (! $ticket) {
            $token = getToken($appId, $appSecret);
            $ticket = getTicket($token);
            S('wx_share_ticket', $ticket, 5000);
        }
        $str = array(
            'noncestr' => $nonceStr, 
            'jsapi_ticket' => $ticket, 
            'url' => "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 
            'timestamp' => $timestamp);
        ksort($str);
        $val = urldecode(http_build_query($str));
        $signature = sha1($val);
        $config_arr = array(
            'appId' => $appId, 
            'timestamp' => $timestamp, 
            'noncestr' => $nonceStr, 
            'signature' => $signature, 
            'ticket' => $ticket, 
            'url' => $str['url']);
        return $config_arr;
    }

    /*
     * 校验访问用户类型 @param $autoLogin 是否直接登录
     */
    public function _checkUser($autoLogin = false, $forceWxLogin = false) {
        $marketInfo = $this->marketInfo;
        $join_mode = $marketInfo['join_mode'];
        $member_batch_id = $marketInfo['member_batch_id'];
        $member_join_flag = $marketInfo['member_join_flag'];
        // 判断是否有微信卡券的奖品，是的话，就需要微信登录
        if ($forceWxLogin === false) {
            $this->needWxLogin = $join_mode ? 1 : 0;
            //log_write(__METHOD__ . '$join_mode:' . var_export($join_mode, 1));
            if (! $join_mode) {
                $result = M('tbatch_info')->where(
                    array(
                        'm_id' => $marketInfo['id'], 
                        '_string' => "ifnull(card_id, '') != ''"))->getField("id");
                if ($result) {
                    $this->needWxLogin = 1;
                    log_write("有微信卡券" . M()->_sql(), 'SQL');
                }
            }
        } else {
            $this->needWxLogin = 1;
        }
        
//        log_write(
//            __METHOD__ . '$this->needWxLogin:' .
//                 var_export($this->needWxLogin, 1));
        // 如果是微信用户才能参加
          //$this->needWxLogin=0;
          if ($this->needWxLogin) {
           if (! isFromWechat()) { // 需要通过微信浏览器访问
                $this->showErrorByErrno(- 1070);
            }
            $this->_loginByWeixin($autoLogin);
            // 查询用户
            $wx_openid = $this->wxSess['openid'];
            if (! $wx_openid) {
                log_write('wx_openid is null');
                $this->showErrorByErrno(- 1071);
            }
            
            $where = array(
                'openid' => $wx_openid, 
                'node_id' => $this->node_id, 
                'subscribe' => array(
                    'neq', 
                    '0'));
            $userInfo = M('twx_user')->where($where)->find() or
                 $userInfo = array();
            log_write(M()->_sql());
            // 判断是否粉丝或者会员
            if ($join_mode && $member_join_flag && $marketInfo['batch_type'] != '61') {
                if (! $userInfo) {
                    log_write("只有微信粉丝才能参加该活动");
                    if ($marketInfo['fans_collect_url']) {
                        redirect($marketInfo['fans_collect_url']);
                    }
                    $this->showErrorByErrno(- 1072);
                }
                if ($member_batch_id != - 1) {
                    $member_batch_arr = explode(',', $member_batch_id);
                    if (! in_array($userInfo['group_id'], $member_batch_arr)) {
                        $this->showErrorByErrno(- 1073);
                    }
                }
            }
            $this->wxUserInfo = array_merge($userInfo, $this->wxSess);
        }
    }

    /**
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @return int
     */
    public function needWechatLogin() {
        $marketInfo = $this->marketInfo;
        $join_mode = $marketInfo['join_mode'];
        $this->needWxLogin = $join_mode ? 1 : 0;
        return $this->needWxLogin;
    }
    

    /**
     * 是否需要微信授权
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function needWechatAuthorize() {
        $needWechatLogin = $this->needWechatLogin();
        if ($needWechatLogin) {
            $wechatUserInfo = $this->WeiXinService->getWechatUserInfo(
                $this->node_id);
            if ($wechatUserInfo) {
                return false;
            } else {
                return true;
            }
        }
        return false;
    }
    
    // 在调用index前的跳转
    public function _before_index() {
        // 判断是否有重定向的地址
        $marketInfo = $this->marketInfo;
        if (! empty($marketInfo['redirect_url'])) {
            // 更新访问统计
            if (ACTION_NAME == 'index') {
                // 访问量
                import('@.Vendor.DataStat');
                $opt = new DataStat($this->id, $this->full_id);
                $opt->UpdateRecord();
            }
            // 要防止重复定向
            $redirect_url = str_replace(array(
                '{$id}'), array(
                $this->id), $marketInfo['redirect_url']);
            redirect($redirect_url);
            return;
        }
    }

    protected function responseJson($code, $msg, $data = null) {
        $resp = array(
            'code' => $code, 
            'msg' => $msg, 
            'data' => $data);
        echo json_encode($resp);
        exit();
    }
    
    // 获取抽奖信息
    public function _getCjInfo() {
        $row = $this->marketInfo;
        // 抽奖配置表
        if ($row['is_cj'] != '1') {
            return array(
                'code' => '1', 
                'msg' => '未设置抽奖');
        }
        $model_c = M('tcj_rule');
        $map_c = array(
            'batch_type' => $this->batch_type, 
            'batch_id' => $this->batch_id, 
            'status' => '1');
        $cj_rule_query = $model_c->field(
            'id,total_chance,cj_button_text,cj_check_flag')->where($map_c)->find();
        // 抽奖文字配置
        $cj_text = $cj_rule_query['cj_button_text'];
        // 奖品
        $jp_arr = M()->table('tcj_batch a')
            ->field('a.cj_cate_id,b.batch_name')
            ->join('tbatch_info b on a.b_id=b.id')
            ->where("a.batch_id='" . $this->batch_id . "' and a.status='1'")
            ->select();
        if (empty($jp_arr)) {
            return array(
                'code' => '1', 
                'msg' => '未设置奖品');
        }
        // 获取奖品中的cate_id
        $cj_cate_ids = array_valtokey($jp_arr, 'cj_cate_id', 'cj_cate_id');
        
        // 分类
        $cj_cate_arr = array();
        if ($cj_rule_query) {
            $cj_cate_arr = M('tcj_cate')->field('id,name')->where(
                array(
                    'node_id' => $this->node_id, 
                    'batch_id' => $this->batch_id, 
                    'cj_rule_id' => $cj_rule_query['id'], 
                    'id' => array(
                        'in', 
                        $cj_cate_ids)))->select();
        }
        
        // 处理页面奖项奖项
        $cjCateId = array();
        $cjCateName = '';
        foreach ($cj_cate_arr as $v) {
            $cjCateId[] = $v['id'];
            $cjCateName .= '"' . $v['name'] . '",';
        }
        return array(
            'code' => 0, 
            'msg' => 'success', 
            'data' => array(
                'cjCateId' => implode(',', $cjCateId), 
                'cjCateName' => trim($cjCateName, ','), 
                'total_chance' => $cj_rule_query['total_chance'], 
                'cj_text' => $cj_text));
    }

    private function _getWxQr($weixinInfo) {
        if ($weixinInfo['account_type'] != '4' || $weixinInfo['status'] != '0') { // 不是已认证的服务号的或者状态不正常的，直接返回空
            return '';
        }
        $arr = json_decode($weixinInfo['setting'], true);
        if (isset($arr['qrUrl'])) {
            return $arr['qrUrl'];
        } else {
            // 去微信获取token
            $wxService = D('WeiXinQrcode', 'Service');
            $wxService->init($weixinInfo['app_id'], $weixinInfo['app_secret'], 
                $weixinInfo['app_access_token']);
            // 自增确定，以后就用这个场景号作为这个商家的固定二维码，存入setting的json字段
            M()->startTrans();
            $scene_id = D('TweixinInfo')->getSceneId($weixinInfo['node_id']);
            if (! $scene_id) {
                M()->rollback();
                return ''; // 如果没有获取到自增场景的id，返回空字符串
            }
            // 去微信接口获取图片内容
            $qrResult = $wxService->getQrcodeImg(
                array(
                    'scene_id' => $scene_id));
            // 更新accessToken
            if ($weixinInfo['app_access_token'] != $wxService->accessToken) {
                $query = M('tweixin_info')->where(
                    array(
                        'node_id' => $weixinInfo['node_id']))->save(
                    array(
                        'app_access_token' => $wxService->accessToken));
            }
            // 如果失败
            if ($qrResult['status'] != '1') {
                log_write(
                    '获取推广二维码失败，原因：' . $qrResult['errcode'] . ':' .
                         $qrResult['errmsg']);
                M()->rollback();
                return '';
            }
            $arr['qrUrl'] = $qrResult['img_url'];
            $setting = json_encode($arr);
            $result = M('tweixin_info')->where(
                array(
                    'id' => $weixinInfo['id']))->save(
                array(
                    'setting' => $setting));
            if (false === $result) {
                M()->rollback();
            }
            M()->commit();
            return $qrResult['img_url'];
        }
    }
    
    // 设置，获取全局手机号
    protected function _userCookieMobile($mobile = null) {
        if ($mobile !== null) {
            cookie('_global_user_mobile', $mobile, 3600 * 24 * 365);
            return $mobile;
        }
        return cookie('_global_user_mobile');
    }

    /**
     * 检查用户是否翼蕙宝会员
     *
     * @return bool|array 会员信息
     */
    public function checkYhbMember() {
        $this->_loginByWeixin(true);
        $yhb_where['openid'] = $this->wxSess["openid"];
        $yhb_where['mobile'] = array(
            'neq', 
            '');
        $check_data = M('tfb_yhb_member')->field('mobile,openid')->where(
            $yhb_where)->find();
        
        $info = array(
            'is_member' => true, 
            'info' => $check_data);
        
        if (empty($check_data)) {
            $info = array(
                'is_member' => false, 
                'info' => array(
                    'openid' => $this->wxSess['openid']));
        }
        return $info;
    }

    protected function error($message = '', $jumpUrl = '', $ajax = null) {
        $id = I('get.id', I('post.id'), 'trim');
        if ($id == "") {
            $id = session("id");
        }
        $batchChannelInfo = M('tbatch_channel')->where(
            array(
                'id' => $id))->Field('node_id,batch_id')->find();
        if (! $batchChannelInfo) {
            parent::error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '你的访问地址出错啦~'));
        }
        $nodeId = $batchChannelInfo['node_id'];
        $mId = $batchChannelInfo['batch_id'];
        $mInfo = M('tmarketing_info')->where(
            array(
                'node_id' => $nodeId, 
                'id' => $mId))->find();
        // 获取logo用于报错页面的显示,如果有活动logo用活动logo,如果没有用机构的logo
        $errorNodeLogo = '';
        $node_name = '';
        $showErrorRemind = 0;
        if (! empty($nodeId)) {
            $nodeInfo = M('tnode_info')->where(
                array(
                    'node_id' => $nodeId))->field(
                'head_photo,node_name,node_short_name')->find();
            // 报错页面的活动图片
            if (! empty($mInfo) && ! empty($mInfo['log_img'])) {
                $errorNodeLogo = get_upload_url($mInfo['log_img']);
            } else {
                $errorNodeLogo = get_upload_url($nodeInfo['head_photo']);
            }
            $node_name = $nodeInfo['node_short_name']; // 报错页的机构号名字
            $weixinInfo = M('tweixin_info')->where(
                array(
                    'node_id' => $nodeId))->find();
            if ($weixinInfo) {
                $this->assign('weixin_code', $weixinInfo['weixin_code']);
                $qrSource = $this->_getWxQr($weixinInfo);
                $this->assign('qrSource', urlencode($qrSource)); // 微信二维码
            }
            $microInfo = M('tmarketing_info')->where(
                array(
                    'node_id' => $nodeId, 
                    'batch_type' => 13))->find(); // 微官网的batchType为13
            if ($microInfo) {
                $channel_id = M('tchannel')->where(
                    array(
                        'node_id' => $nodeId, 
                        'sns_type' => 43))->limit('1')->getField('id');
                if ($channel_id) {
                    $labelId = get_batch_channel($microInfo['id'], $channel_id, 
                        13, $nodeId);
                    $microUrl = U('Label/Label/index', 
                        array(
                            'id' => $labelId), '', '', true);
                    $this->assign('microUrl', $microUrl);
                    $this->assign('microName', $microInfo['name']);
                    $showErrorRemind = 1;
                }
            }
            // 旺财小店
            $ecShopInfo = M('tmarketing_info')->where(
                array(
                    'node_id' => $nodeId, 
                    'batch_type' => '29'))->find();
            if ($ecShopInfo) {
                $channel_id = M('tchannel')->where(
                    array(
                        'node_id' => $nodeId, 
                        'type' => '4', 
                        'sns_type' => '46'))->getField('id');
                if ($channel_id) {
                    $labelId = get_batch_channel($ecShopInfo['id'], $channel_id, 
                        '29', $nodeId);
                    $ecShopUrl = U('Label/Label/index', 
                        array(
                            'id' => $labelId), '', '', true);
                    $this->assign('ecShopUrl', $ecShopUrl);
                    $this->assign('ecShopName', $ecShopInfo['name']);
                    $showErrorRemind = 1;
                }
            }
        }
        // 翼蕙宝
        if ($nodeId == C('Yhb.node_id')) {
            $this->assign('is_yhb', true);
        }
        
        $this->assign('showErrorRemind', $showErrorRemind);
        $this->assign('node_name', $node_name);
        $this->assign('errorNodeLogo', $errorNodeLogo);
        parent::error($message, $jumpUrl, $ajax);
    }

    public function getMobileForAwardList($id) {
        if (empty($this->DrawLotteryCommonService)) {
            $this->DrawLotteryCommonService = D('DrawLotteryCommon', 'Service');
        }
        return $this->DrawLotteryCommonService->getMobileForAwardList($id);
    }

    public function setMobileForAwardList($id, $mobile)
    {
        if (empty($this->DrawLotteryCommonService)) {
            $this->DrawLotteryCommonService = D('DrawLotteryCommon', 'Service');
        }
        return $this->DrawLotteryCommonService->setMobileForAwardList($id, $mobile);
    }

    public function getGobackUrlForAwardList($id) {
        if (empty($this->DrawLotteryCommonService)) {
            $this->DrawLotteryCommonService = D('DrawLotteryCommon', 'Service');
        }
        return $this->DrawLotteryCommonService->getGobackUrlForAwardList($id);
    }
}
