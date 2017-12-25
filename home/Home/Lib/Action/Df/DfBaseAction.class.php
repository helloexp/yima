<?php

/*
 * 营活活动基础类 @auther tr
 */
class DfBaseAction extends BaseAction {

    public $id;

    public $batch_id;

    public $batch_type;

    public $channel_id;

    public $node_id;

    public $node_name;

    public $full_id;

    public $return_commission_flag;

    public $marketInfo;

    public $channelInfo;
    // 渠道信息
    public $wxSess = array();
    // 微信信息
    public $wxUserInfo = array();
    // 当前微信粉丝信息
    public $needWxLogin = 0;
    // 设置是否微信登录 0否1是
    public $shop_mid;

    public $node_cfg;
    
    // 付满送参数
    public $pay_token;

    public function _initialize() {
        // 标签
        $id = I('get.id', I('post.id'), 'trim');
        if ($id == "") {
            $id = session("id");
        }
        $this->assign('jb_label_id', $id);
        if (empty($id))
            $this->error('错误参数！');
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
        if (empty($full_id)) {
            $full_id = $id;
        } else {
            $full_id = $full_id . ',' . $id;
            $full_arr = explode(',', $full_id);
            if (in_array($id, $full_arr)) {
                $full_arr = array_unique($full_arr);
                $full_id = implode(',', $full_arr);
            }
        }
        $this->full_id = $full_id;
        
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
            $memberRegLabelId = M('tbatch_channel')->where($map)
                ->order('add_time desc')
                ->getField('id');
        } else {
            $memberRegLabelId = '';
        }
        
        $this->return_commission_flag = '0';
        $this->assign('member_reg_label_id', $memberRegLabelId);
        $this->assign('label_batch_id', $this->batch_id);
        $this->assign('full_id', $full_id);
        $this->assign('node_id', $this->node_id);
        $this->assign('node_name', $this->node_name);
        $this->assign('node_service_hotline', 
            get_node_info($this->node_id, 'node_service_hotline'));
        $this->assign('from_user_id', $from_user_id);
        $this->assign('from_type', $from_type);
        $this->assign('return_commission_flag', $this->return_commission_flag);
        $this->assign('wx_share_config', $this->setShareConfig());
        $this->assign('join_mode', $this->marketInfo['join_mode']);
        $this->assign('islogin', 0);
        $this->assign('node_cfg', $this->node_cfg);
        
    }
    
    // 判断是抽奖预览时间是否过期
    public function checkCjEndtime() {
        $id = I('get.id', I('post.id'), 'trim');
        
        $batchInfo = M('tbatch_channel')->where(
            array(
                'id' => $id))
            ->field('channel_id, end_time')
            ->find();
        
        $snsType = M('tchannel')->where(
            array(
                'id' => $batchInfo['channel_id']))
            ->field('sns_type')
            ->find();
        
        if ('61' == $snsType['sns_type']) {
            if (date('YmdHis') > $batchInfo['end_time']) {
                return false;
            } else {
                return true;
            }
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
    public function _checkUser($autoLogin = false) {
        $marketInfo = $this->marketInfo;
        $join_mode = $marketInfo['join_mode'];
        $member_batch_id = $marketInfo['member_batch_id'];
        $member_join_flag = $marketInfo['member_join_flag'];
        // 判断是否有微信卡券的奖品，是的话，就需要微信登录
        $this->needWxLogin = $join_mode ? 1 : 0;
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
        // 如果是微信用户才能参加
        if ($this->needWxLogin) {
            // $_GET['_sid_'] = 'w';测试用
            $this->_loginByWeixin($autoLogin);
                // 查询用户
            $wx_openid = $this->wxSess['openid'];
            if (! $wx_openid) {
                log_write('wx_openid is null');
                $this->error("只有微信用户才能参加该活动");
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
            if ($join_mode && $member_join_flag) {
                if (! $userInfo) {
                    log_write("只有微信粉丝才能参加该活动");
                    if ($marketInfo['fans_collect_url']) {
                        redirect($marketInfo['fans_collect_url']);
                    }
                    $this->error("只有微信粉丝才能参加该活动");
                }
                if ($member_batch_id != - 1) {
                    $member_batch_arr = explode(',', $member_batch_id);
                    if (! in_array($userInfo['group_id'], $member_batch_arr)) {
                        $this->error("您所在的分组不允许参加该活动");
                    }
                }
            }
            $this->wxUserInfo = array_merge($userInfo, $this->wxSess);
        }
    }
    
    // 必须从微信登录
    public function _loginByWeixin($autoLogin = false) {
        // todo debug
        if (I('_sid_') == 'w') {
            $_SERVER['HTTP_USER_AGENT'] = 'MicroMessenger'; // 模拟微信
            session('node_wxid_' . $this->node_id, 
                array(
                    // 'openid' => 'oeV3xsiDY2J6JZMTXCRkmPxLxamk',
                    // 'openid' => 'ol-K9tyNP7K5BV483qOpy3FIcg-o',
                    'openid' => 'oeV3xsvRo-wcebbTFLo2hjRZyFOw', 
                    'nickname' => '饭钵勒'));
        }
        
        // 如果发现没有带上 wechat_card_js，则加上
        if (IS_GET && empty($_GET['wechat_card_js'])) {
            $_GET['wechat_card_js'] = 1;
            $jumpurl = U('', I('get.'), '', '', true);
            redirect($jumpurl);
            return;
        }
        
        $wxSess = session('node_wxid_' . $this->node_id);
        if (! $wxSess || ! $wxSess['openid']) {
            // 如果不自动跳转登录
            if (! $autoLogin) {
                $this->wxSess = null;
                return;
            }
            // 计算回调的页面
            $_GET['wechat_card_js'] = 1;
            $backurl = U('', I('get.'), '', '', true);
            $backurl = urlencode($backurl);
            $jumpurl = U(
                'Label/WeixinLoginNode/wechatAuthorizeAndRedirectByDefault', 
                array(
                    'id' => $this->id, 
                    'type' => 1, 
                    'backurl' => $backurl));
            redirect($jumpurl);
        }
        
        // to-do 设置session为空
        // session('node_wxid_'.$this->node_id,null);
        $this->wxSess = $wxSess;
    }
    
    // 获取微信登录用户信息
    public function getWxUserInfo() {
        return $this->wxUserInfo;
    }

    public function _get_shop_mid() {
        static $id = null;
        if ($id === null) {
            $info = M('tmarketing_info')->where(
                "node_id = '{$this->node_id}' and batch_type = '29'")->find();
            $id = $info['id'];
        }
        return $id;
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
                $id = $this->id;
                $opt->UpdateRecord();
            }
            // 要防止重复定向
            $redirect_url = str_replace(array(
                '{$id}'), array(
                $id), $marketInfo['redirect_url']);
            redirect($redirect_url);
            return;
        }
    }
}
