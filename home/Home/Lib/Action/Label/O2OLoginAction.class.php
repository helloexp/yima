<?php

/*
 * 多宝电商统一登录 闪购/码上买/小店 在微信里打开的话则去微信隐式认证获取openid
 */
class O2OLoginAction extends MyBaseAction {

    public $node_id = '';

    public $batch_id = '';

    public $channel_id = '';

    public $id = '';

    public $appid = '';

    public $secret = '';

    public $surl = '';
    // 来源地址
    public $appIdRewrite = '';

    public $batch_type = '';

    public $change = 0;
    // 切换判断 1切换账号
    private $nodePhone;

    public function _initialize() {
        $this->expiresTime =60; // 手机发送间隔
        $this->CodeexpiresTime = 600; // 手机动态密码过期时间
                                      // 默认服务号为翼码旺财
        $this->appid = C('WEIXIN.appid');
        $this->secret = C('WEIXIN.secret');
        
        $change = I("change", '');
        if ($change) {
            $this->change = $change;
        }
        
        $id = I("id");
        $surl = I('surl');
        $node_id = I("node_id", null);
        $appIdRewrite = I('shopAppIdRewrite');
        $this->appIdRewrite = $appIdRewrite;
        if ($node_id == '' && $id == '') {
            $this->error(
                array(
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '参数错误~'));
        }
        
        $this->channel_id = 0;
        if ($id != '') {
            $batchChannelInfo = M('tbatch_channel')->where(
                array(
                    'id' => $id))->find();
            if (! $batchChannelInfo)
                $this->error(
                    array(
                        'errorTxt' => '错误访问！', 
                        'errorSoftTxt' => '您的访问地址出错啦~'));
            
            $node_id = $batchChannelInfo['node_id'];
            $this->channel_id = $batchChannelInfo['channel_id'];
            $this->batch_id = $batchChannelInfo['batch_id'];
        }
        
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $node_id))->find();
        if (! $nodeInfo) {
            $this->error(
                array(
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '您的访问地址出错啦~'));
        }
        
        if (! empty($node_id) && $appIdRewrite == '1') {
            $beforeSurl = I('surl') . '&type=got';
            $surl = htmlspecialchars_decode(urldecode($beforeSurl));
            $this->appid = $nodeInfo['app_id'];
            $this->secret = $nodeInfo['app_secret'];
        } elseif ($node_id) {
            $beforeSurl = I('surl');
            $surl = htmlspecialchars_decode(urldecode($beforeSurl));
        }
        
        if (C('fb_boya.node_id') == $nodeInfo['node_id']) {
            $this->appid = C('fb_boya.appid');
            $this->secret = C('fb_boya.secret');
        }
        if ($surl == '') {
            $surl = U('Label/Member/index', 
                array(
                    'node_id' => $node_id), '', '', true);
        }
        
        $this->id = $id;
        $this->surl = $surl;
        $this->node_id = $nodeInfo['node_id'];
        $this->node_name = $nodeInfo['node_name'];
        $this->node_short_name = $nodeInfo['node_short_name'];
        $this->nodePhone = empty($nodeInfo['contact_tel']) ? $nodeInfo['contact_phone'] : $nodeInfo['contact_tel'];
        $this->assign('node_name', $nodeInfo['node_name']);
        $this->assign('node_short_name', $nodeInfo['node_short_name']);
        $this->assign('expiresTime', $this->expiresTime);
        
        $this->WeiXinService = D('WeiXin', 'Service');
    }
    
    // 入口修改
    public function index() {
        // 回调触发
        $bcall = I('backcall', null);
        if (isFromWechat()) {
            // 旺财授权
            $this->wcWeixinCheckLogin($bcall);
            // 获取微信openidsession
            $wxUserInfo = session('wxUserInfo');
            $nodeInfo = M('tweixin_info')->where(
                array(
                    'node_id' => $this->node_id, 
                    'account_type' => 4))->find();
            if ($wxUserInfo['openid']) {
                if ($this->change != 1) {
                    $result = $this->_updateWCMemberPhone($wxUserInfo['openid']);
                    if ($result['phone']) {
                        $ret = $this->_suppleSession($result['phone']);
                        
                        if ($result['openid'] || $nodeInfo['err_flag'] == '0') {
                            $upTime = M("tmember_info")->where(
                                array(
                                    'node_id' => $this->node_id, 
                                    'phone_no' => $result['phone']))->save(
                                array(
                                    'update_time' => date('YmdHis')));
                            redirect($this->surl);
                        }
                    }
                }
            }
            
            // 商户授权
            if ($nodeInfo['err_flag'] == '1') {
                $this->merchantWxCheckLogin($bcall);
                
                // 获取微信openidsession
                $merWxUserInfo = session('merWxUserInfo');
                if ($merWxUserInfo['openid']) {
                    if (session('groupPhone')) {
                        $mwxComFlag = true;
                        M()->startTrans();
                        $mInfo = M("tmember_info")->where(
                            array(
                                'node_id' => $this->node_id, 
                                'phone_no' => session('groupPhone'), 
                                'wx_openid' => $wxUserInfo['openid']))
                            ->field('id,point')
                            ->find();
                        if (! $mInfo) {
                            session("groupPhone", null);
                        }
                        $result = M('tmember_info')->where(
                            array(
                                'node_id' => $this->node_id, 
                                '_string' => 'phone_no is NULL', 
                                'mwx_openid' => $merWxUserInfo['openid'], 
                                'status' => '0'))->find();
                        if ($result) {
                            if ($result['point'] > 0) {
                                $integral = D('IntegralPointTrace', 'Model');
                                $flag = $integral->integralPointChange(9, 
                                    $result['point'], $mInfo['id'], 
                                    $this->node_id);
                                if ($flag === false) {
                                    $mwxComFlag = false;
                                    M()->rollback();
                                }
                            }
                            
                            if ($mwxComFlag) {
                                $upPT = $this->updateMemberPointTrace(
                                    $mInfo['id'], $result['id']);
                                if (! $upPT) {
                                    $mwxComFlag = false;
                                    M()->rollback();
                                }
                            }
                            
                            if ($mwxComFlag) {
                                $upBT = $this->updateMemberBehaviorTrace(
                                    $mInfo['id'], $result['id']);
                                if (! $upBT) {
                                    $mwxComFlag = false;
                                    M()->rollback();
                                }
                            }
                            
                            if ($mwxComFlag) {
                                $upAT = $this->updateMemberActivityTotal(
                                    $mInfo['id'], $result['id']);
                                if (! $upAT) {
                                    $mwxComFlag = false;
                                    M()->rollback();
                                }
                            }
                            
                            if ($mwxComFlag) {
                                // $sql = "insert into tmember_info_del_backup select * from tmember_info where id = " .
                                //      $result['id'];
                                // $resulB = M()->execute($sql);
                                // if (! $resulB) {
                                //     $mwxComFlag = false;
                                //     log_write(
                                //         "将商户授权openid会员移动到备份表失败！" .
                                //              print_r($result, true));
                                //     M()->rollback();
                                // }
                                log_write(
                                "将商户授权openid会员移动到备份表，data=" . print_r($result, 
                                    true));
                            }
                            
                            if ($mwxComFlag) {
                                $relt = M('tmember_info')->where(
                                    array(
                                        'id' => $result['id']))->delete();
                                if (! $relt) {
                                    $mwxComFlag = false;
                                    log_write(
                                        "删除商户授权openid会员失败！" .
                                             print_r($relt, true));
                                    M()->rollback();
                                }
                            }
                        }
                        
                        if ($mwxComFlag) {
                            $ret = M('tmember_info')->where(
                                array(
                                    'id' => $mInfo['id']))->save(
                                array(
                                    'mwx_openid' => $merWxUserInfo['openid'], 
                                    'update_time' => date('YmdHis')));
                            if (! $ret) {
                                $mwxComFlag = false;
                                log_write("会员合并更新失败1！会员id=" . $mInfo['id']);
                                M()->rollback();
                            }
                        }
                        M()->commit();
                        
                        redirect($this->surl);
                    }
                    
                    $this->_updateWxUserMsg($merWxUserInfo['access_token'], 
                        $merWxUserInfo['openid']);
                } else {
                    $ret = M('tweixin_info')->where(
                        array(
                            'id' => $nodeInfo['id']))->save(
                        array(
                            'err_flag' => '0'));
                }
            }
        }
        
        // 查询logo信息
        $node_model = M('tecshop_banner');
        $map = array(
            'node_id' => $this->node_id, 
            'ban_type' => 2);
        $logoInfo = $node_model->where($map)->find();
        if (! $logoInfo['biaoti'])
            $logoInfo['biaoti'] = $this->node_short_name;
        
        $this->assign('login_title', $logoInfo['biaoti']);
        $this->assign('img_url', $logoInfo['img_url']);
        $this->assign('node_id', $this->node_id);
        $this->assign('surl', $this->surl);
        $this->assign('stress_test', I('stress_test'));
        $this->assign('id', $this->id);
        $this->assign('bcall', $bcall);
        $this->assign('node_service_hotline', $this->nodePhone);
        
        $this->display();
    }

    public function wcWeixinCheckLogin($bcall = '') {
        // 获取微信openidsession
        $wxUserInfo = session('wxUserInfo');
        if (isset($wxUserInfo['openid']) && $wxUserInfo['openid']) {
            return false;
        }
        
        // 微信首次登录 获取openid
        if (isFromWechat() && ! $wxUserInfo) {
            if ($bcall) {
                cookie('bcall', $bcall);
            }
            $apiCallbackUrl = U('Label/O2OLogin/wcWeixinCallback', 
                array(
                    'id' => $this->id, 
                    'change' => $this->change, 
                    'node_id' => $this->node_id, 
                    'shopAppIdRewrite' => $this->appIdRewrite, 
                    'surl' => urlencode($this->surl)), '', '', true);
            
            $this->wechatAuthorizeAndRedirectByDetailParam($this->appid, 
                $this->secret, 0, $apiCallbackUrl, $this->surl, 0);
        }
        
        return true;
    }

    /**
     * 商户授权
     *
     * @param $callbackUrl
     * @return bool
     */
    public function merchantWxCheckLogin($callbackUrl) {
        // 获取微信openidsession
        $merWxUserInfo = session('merWxUserInfo');
        if (isset($merWxUserInfo['openid']) && $merWxUserInfo['openid']) {
            return false;
        }
        
        // 微信首次登录 获取openid
        if (isFromWechat() && empty($merWxUserInfo)) {
            if ($callbackUrl) {
                cookie('bcall', $callbackUrl);
            }
            $apiCallbackUrl = U('Label/O2OLogin/merchantWeixinCallbackcall', 
                array(
                    'id' => $this->id, 
                    'change' => $this->change, 
                    'node_id' => $this->node_id, 
                    'shopAppIdRewrite' => $this->appIdRewrite, 
                    'surl' => urlencode($this->surl)), '', '', true);
            $this->wechatAuthorizeByNodeId($this->node_id, 0, '', 
                $apiCallbackUrl);
        }
        
        return true;
    }

    /*
     * 微信认证接口回调
     */
    public function wcWeixinCallback() {
        $this->callback('wcuser');
    }

    /*
     * 微信认证接口回调
     */
    public function callback($type = '') {
        if ($type == 'wcuser') {
            $sessionKey = 'wxUserInfo';
            $wechatInfo = array(
                'appid' => $this->appid, 
                'secret' => $this->secret);
        } else {
            $sessionKey = 'merWxUserInfo';
            $wechatInfo = $this->WeiXinService->getWeixinInfoByNodeId(
                $this->node_id);
        }
        
        $code = I('code', null);
        if ($code) { // 重新获取
            $result = $this->WeiXinService->getOpenid($code, $wechatInfo);
            $openid = $result['openid'];
            $access_token = $result['access_token'];
            if ($openid == '') {
                $this->error('获取授权openid失败！');
            }
            if ($this->appIdRewrite == '1') {
                $_SESSION[$sessionKey]['secondOpenid'] = $openid;
            } else {
                session($sessionKey, 
                    array(
                        'openid' => $openid, 
                        'appid' => $this->appid, 
                        'access_token' => $access_token));
            }
        }
        redirect(
            U('Label/O2OLogin/index', 
                array(
                    'id' => $this->id, 
                    'change' => $this->change, 
                    'node_id' => $this->node_id, 
                    'surl' => urlencode($this->surl))));
    }

    /**
     * 微信认证接口回调
     */
    public function merchantWeixinCallbackcall() {
        $this->callback('merchant');
    }
    
    // 请求接口
    protected function httpsGet($apiUrl) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $result = curl_exec($ch);
        return $result;
    }
    
    // 手机发送动态密码
    public function sendCheckCode() {
        
        // 图片校验码
        /*
         * $verify = I('post.verify',null,'mysql_real_escape_string');
         * if(session('verify') != md5($verify)) { $this->error("图片动态密码错误"); }
         */
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->ajaxReturn(array(
                'type' => 'phone'), "手机号{$error}", 0);
        }
        // 测试环境不下发，验证码直接为1111
        if (! is_production()) {
            $groupCheckCode = array(
                'number' => 1111, 
                'add_time' => time(), 
                'phoneNo' => $phoneNo);
            session('groupCheckCode', $groupCheckCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }
        // 发送频率验证
        $groupCheckCode = session('groupCheckCode');
        if (! empty($groupCheckCode) &&
             (time() - $groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->CodeexpiresTime / 60;
        $text = "您在{$this->node_short_name}商户的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
        // 通知支撑
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 请求参数
        $req_array = array(
            'NotifyReq' => array(
                'TransactionID' => $TransactionID, 
                'ISSPID' => C('MOBILE_ISSPID'), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'SendLevel' => '1', 
                'Recipients' => array(
                    'Number' => $phoneNo),  // 手机号
                'SendClass' => 'SMS', 
                'MessageText' => $text,  // 短信内容
                'Subject' => '', 
                'ActivityID' => C('ALIPAY_NOTICE_ACTIVITYID'), 
                'ChannelID' => '', 
                'ExtentCode' => ''));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssServ($req_array);
        
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error('发送失败');
        }
        $groupCheckCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('groupCheckCode', $groupCheckCode);
        $this->success('动态密码已发送');
    }
    
    // 登录
    public function loginPhone() {
        $bcall = I('bcall');
        if ($bcall)
            cookie('bcall', $bcall);
        $node_id = I('post.node_id', null, 'mysql_real_escape_string');
        $phoneNo = I('post.phone', null, 'mysql_real_escape_string');
        $batchChannelId = I('id',null);//带过来的活动id 如果是从活动链接点进来的 则来源渠道使用活动名
        //压测使用
        $isStressTest = I('post.stress_test', null, 'mysql_real_escape_string');
        if('yes' != $isStressTest){
            if (! check_str($phoneNo, 
                array(
                    'null' => false, 
                    'strtype' => 'mobile'), $error)) {
                $this->ajaxReturn(array(
                    'type' => 'phone'), "手机号{$error}", 0);
            }
            // 手机动态密码
            $checkCode = I('post.check_code', null);
            if (! check_str($checkCode, array(
                'null' => false), $error)) {
                $this->ajaxReturn(array(
                    'type' => 'pass'), "动态密码{$error}", 0);
            }
            $verifyCheckCode = true;
            if (! is_production() && function_exists('isTestCheckCode') &&
                 isTestCheckCode($checkCode)) {
                $verifyCheckCode = false;
            }

            if ($verifyCheckCode) {
                $groupCheckCode = session('groupCheckCode');
                if (! empty($groupCheckCode) &&
                     $groupCheckCode['phoneNo'] != $phoneNo)
                    $this->ajaxReturn(
                        array(
                            'type' => 'phone'), '手机号不正确', 0);
                if (! empty($groupCheckCode['number']) &&
                     $groupCheckCode['number'] != $checkCode)
                    $this->ajaxReturn(
                        array(
                            'type' => 'pass'), '动态密码不正确', 0);
                if (time() - $groupCheckCode['add_time'] > $this->CodeexpiresTime) {
                    $this->ajaxReturn(
                        array(
                            'type' => 'pass'), '动态密码已经过期', 0);
                }
            }
        }    
        // 记录session
        $ret = $this->_suppleSession($phoneNo,$batchChannelId);
        
        // 合并积分
        $this->memberPointMerge($phoneNo);
        
        // 微信配置openid
        $wxUserInfo = session('wxUserInfo');
        if ($wxUserInfo['openid']) {
            $ret = $this->_updateWxPhone($wxUserInfo['openid'], $phoneNo);
            
            // 博雅非标
            if ($this->node_id == C('fb_boya.node_id')) {
                $user_info = session('store_mem_id' . $this->node_id);
                $fbBoyaService = D('FbBoya', 'Service');
                $fbBoyaService->updateTecshopProMId($wxUserInfo['openid'], 
                    $this->node_id, $user_info['user_id']);
            }
        }
        $this->success('登录成功');
    }

    //登录不是微信绑定的手机号时，解绑之前绑定的手机号
    private function checkMemberUnbindOpenid($phone, $wx_openid='', $mwx_openid='')
    {
        if(empty($wx_openid) && empty($mwx_openid)) {
            return true;
        }

        $where = array(
            'node_id' => $this->node_id,
            '_string' => 'phone_no is not NULL and (wx_openid="'.$wx_openid.'" or mwx_openid="'.$mwx_openid.'")',
            'status' => '0'
            );
        $member = M('tmember_info');
        $list = $member->where($where)->field('id')->select();
        foreach($list as $v) {
            if($v['phone_no'] != $phone) {
                $sql = 'UPDATE tmember_info SET wx_openid=NULL, mwx_openid=NULL, nickname=NULL, nickLogo=NULL WHERE id = '.$v['id'];
                $result = M()->execute($sql);
                if(!$result) {
                    log_write('解除手机号绑定微信失败！'.$sql);
                    return false;
                }                
            }
        }

        return true;
    }
    
    // 更新删除openid 会员原流水
    // m_id 手机号会员id wx_mid合并的微信号会员id
    private function updateMemberPointTrace($m_id, $wx_mid) {
        $resultM = M('tintegral_point_trace')->where(
            array(
                'member_id'=>$m_id, 'type'=>'15'))->count();
        if($result > 0) {
            return true;
        }

        $result = M('tintegral_point_trace')->where(
            array(
                'member_id' => $wx_mid))->count();
        if ($result < 1) {
            return true;
        }
        
        $point = M('tintegral_point_trace')->where(
            array(
                'member_id' => $wx_mid))->save(
            array(
                'member_id' => $m_id));
        if (! $point) {
            log_write(
                "会员合并积分流水失败！手机号会员id=" . $m_id . "，被合并微信openid 会员id=" . $wx_mid);
            return false;
        }
        return true;
    }

    private function updateMemberBehaviorTrace($m_id, $wx_mid) {
        $result = M('tintegral_behavior_trace')->where(
            array(
                'member_id' => $wx_mid))->count();
        if ($result < 1) {
            return true;
        }
        
        $beha = M("tintegral_behavior_trace")->where(
            array(
                'member_id' => $wx_mid))->save(
            array(
                'member_id' => $m_id));
        if (! $beha) {
            log_write(
                "会员合并行为记录失败！手机号会员id=" . $m_id . "，被合并微信openid 会员id=" . $wx_mid);
            return false;
        }
        return true;
    }

    private function updateMemberActivityTotal($m_id, $wx_mid) {
        $wxtotal = M("tmember_activity_total")->where(
            array(
                'member_id' => $wx_mid))->find();
        if (! $wxtotal) {
            return true;
        }
        
        $total = M("tmember_activity_total")->where(
            array(
                'member_id' => $m_id))->find();
        $data = array(
            'join_total' => array(
                'exp', 
                "join_total+" . $wxtotal['join_total']), 
            'send_total' => array(
                'exp', 
                "send_total+" . $wxtotal['send_total']), 
            'verify_total' => array(
                'exp', 
                "verify_total+" . $wxtotal['verify_total']), 
            'shop_total' => array(
                'exp', 
                "shop_total+" . $wxtotal['shop_total']), 
            'shopline_total' => array(
                'exp', 
                "shopline_total+" . $wxtotal['shopline_total']));
        if ($total) {
            $result = M("tmember_activity_total")->where(
                array(
                    'member_id' => $m_id))->save($data);
            if ($result) {
                $res = M("tmember_activity_total")->where(
                    array(
                        'member_id' => $wx_mid))->delete();
                if (! $res) {
                    log_write(
                        "会员合并活动记录，删除微信openid会员记录失败！手机号会员id=" . $m_id .
                             "，被合并微信openid 会员id=" . $wx_mid);
                    return false;
                }
            } else {
                log_write(
                    "会员合并活动记录失败！手机号会员id=" . $m_id . "，被合并微信openid 会员id=" .
                         $wx_mid);
                return false;
            }
        } else {
            $data['member_id'] = $m_id;
            $data['trans_date'] = date('YmdHis');
            $data['stauts'] = '1';
            $result = M("tmember_activity_total")->add($data);
            if ($result) {
                $res = M("tmember_activity_total")->where(
                    array(
                        'member_id' => $wx_mid))->delete();
                if (! $res) {
                    log_write(
                        "会员合并活动记录，删除微信openid会员记录失败！手机号会员id=" . $m_id .
                             "，被合并微信openid 会员id=" . $wx_mid);
                    return false;
                }
            } else {
                log_write(
                    "会员合并活动记录，新增手机号会员记录失败！手机号会员id=" . $m_id .
                         "，被合并微信openid 会员id=" . $wx_mid);
                return false;
            }
        }
        
        return true;
    }
    
    // 会员积分合并
    public function memberPointMerge($phone) {
        M()->startTrans();
        $wxUserInfo = session('wxUserInfo');
        $merWxUserInfo = session('merWxUserInfo');
        $ch_unbind = $this->checkMemberUnbindOpenid($phone, $wxUserInfo['openid'], $merWxUserInfo['openid']);
        if(!$ch_unbind) {
            M()->rollback();
            M()->commit();

            return true;
        }
        $member = M("tmember_info")->where(
            array(
                'node_id' => $this->node_id, 
                'phone_no' => $phone))->find();
        if ($member) {
            $data = array();
            if ($wxUserInfo['openid']) {
                $wxComFlag = true;
                $result = M('tmember_info')->where(
                    array(
                        'node_id' => $this->node_id, 
                        '_string' => 'phone_no is NULL', 
                        'wx_openid' => $wxUserInfo['openid'], 
                        'status' => '0'))->find();
                if ($result) {
                    if ($result['point'] > 0) {
                        $integral = D('IntegralPointTrace', 'Model');
                        $flag = $integral->integralPointChange(9, 
                            $result['point'], $member['id'], $this->node_id);
                        if ($flag === false) {
                            $wxComFlag = false;
                            M()->rollback();
                        }
                    }
                    
                    if ($wxComFlag) {
                        $upPT = $this->updateMemberPointTrace($member['id'], 
                            $result['id']);
                        if (! $upPT) {
                            $wxComFlag = false;
                            M()->rollback();
                        }
                    }
                    
                    if ($wxComFlag) {
                        $upBT = $this->updateMemberBehaviorTrace($member['id'], 
                            $result['id']);
                        if (! $upBT) {
                            $wxComFlag = false;
                            M()->rollback();
                        }
                    }
                    
                    if ($wxComFlag) {
                        $upAT = $this->updateMemberActivityTotal($member['id'], 
                            $result['id']);
                        if (! $upAT) {
                            $wxComFlag = false;
                            M()->rollback();
                        }
                    }
                    
                    if ($wxComFlag) {
                        // $sql = "insert into tmember_info_del_backup select * from tmember_info where id = " .
                        //      $result['id'];
                        // $resulB = M()->execute($sql);
                        // if (! $resulB) {
                        //     $wxComFlag = false;
                          
                        //     log_write(
                        //         "将翼码旺财授权openid会员移动到备份表失败！" .
                        //              print_r($result, true).'mysql'.M()->getLastSql());
                        //     M()->rollback();
                        // }
                        log_write(
                                "将商户授权openid会员移动到备份表，data=" . print_r($result, 
                                    true));
                    }
                    
                    if ($wxComFlag) {
                        $resultd = M('tmember_info')->where(
                            array(
                                'id' => $result['id']))->delete();
                        if (! $resultd) {
                            $wxComFlag = false;
                            log_write(
                                "删除翼码旺财授权openid会员失败！" . print_r($result, true));
                            M()->rollback();
                        }
                    }
                }
                if ($wxComFlag) {
                    $data['wx_openid'] = $wxUserInfo['openid'];
                }
            }
            
            if ($merWxUserInfo['openid']) {
                $mwxComFlag = true;
                $result = M('tmember_info')->where(
                    array(
                        'node_id' => $this->node_id, 
                        '_string' => 'phone_no is NULL', 
                        'mwx_openid' => $merWxUserInfo['openid'], 
                        'status' => '0'))->find();
                if ($result) {
                    if ($result['point'] > 0) {
                        $integral = D('IntegralPointTrace', 'Model');
                        $flag = $integral->integralPointChange(9, 
                            $result['point'], $member['id'], $this->node_id);
                        if ($flag === false) {
                            $mwxComFlag = false;
                            M()->rollback();
                        }
                    }
                    
                    if ($mwxComFlag) {
                        $upPT = $this->updateMemberPointTrace($member['id'], 
                            $result['id']);
                        if (! $upPT) {
                            $mwxComFlag = false;
                            M()->rollback();
                        }
                    }
                    
                    if ($mwxComFlag) {
                        $upBT = $this->updateMemberBehaviorTrace($member['id'], 
                            $result['id']);
                        if (! $upBT) {
                            $mwxComFlag = false;
                            M()->rollback();
                        }
                    }
                    
                    if ($mwxComFlag) {
                        $upAT = $this->updateMemberActivityTotal($member['id'], 
                            $result['id']);
                        if (! $upAT) {
                            $mwxComFlag = false;
                            M()->rollback();
                        }
                    }
                    
                    if ($mwxComFlag) {
                        // $sql = "insert into tmember_info_del_backup select * from tmember_info where id = " .
                        //      $result['id'];
                        // $resulB = M()->execute($sql);
                        // if (! $resulB) {
                        //     $mwxComFlag = false;
                        //     log_write(
                        //         "将商户授权openid会员移动到备份表失败！" . print_r($result, 
                        //             true));
                        //     M()->rollback();
                        // }
                        log_write(
                                "将商户授权openid会员移动到备份表，data=" . print_r($result, 
                                    true));
                    }
                    
                    if ($mwxComFlag) {
                        $resultd = M('tmember_info')->where(
                            array(
                                'id' => $result['id']))->delete();
                        if (! $resultd) {
                            $mwxComFlag = false;
                            log_write(
                                "删除商户授权openid会员失败！" . print_r($result, true));
                            M()->rollback();
                        }
                    }
                }
                if ($wxComFlag) {
                    $data['mwx_openid'] = $merWxUserInfo['openid'];
                }
            }
            
            log_write("wc openid===".$wxUserInfo['openid']."====mer openid====".$merWxUserInfo['openid']);
            $wxData = M("twx_user")->where(
                array(
                    'openid' => $merWxUserInfo['openid'], 
                    'node_id' => $this->node_id))->find();
            log_write("wxdata==".print_r($wxData, true));
            if ($wxData) {
                $data['nickname'] = $wxData['nickname'];
                $data['nickLogo'] = $wxData['headimgurl'];
                if (! $member['citycode']) {
                    $citycode = D("MemberInstall")->cacheCityData(
                        $wxData['province'], $wxData['city']);
                    $data['citycode'] = $citycode;
                }
                if (! $member['address']) {
                    $data['address'] = $wxData['province'] . $wxData['city'];
                }
            }
            // 判断是否有会员卡
            if (! $member['card_id']) {
                $cardData = D("MemberInstall")->getDefMemberCards(
                    $this->node_id);
                $data['card_id'] = $cardData['id'];
            }
            
            $data['update_time'] = date('YmdHis');
            
            $flag = M('tmember_info')->where(
                array(
                    'id' => $member['id']))->save($data);
            if (! $flag) {
                log_write("会员合并更新失败！" . print_r($data, true)."mysql".M()->getLastSql());
                M()->rollback();
            }
        }
        
        M()->commit();
    }
    
    // 根据openid在会员表tmember_info中获取手机号
    private function _updateMemberPhone($openid) {
        if (! $openid)
            return array(
                'code' => '0001', 
                'msg' => 'OPENID不能为空');
        
        $member = M('tmember_info')->where(
            array(
                'wx_openid' => $openid, 
                'node_id' => $this->node_id))
            ->field('phone_no, mwx_openid')
            ->find();
        return array(
            'code' => '0000', 
            'msg' => '成功', 
            'phone' => $member['phone_no'], 
            'openid' => $member['mwx_openid']);
    }
    
    // 根据openid在会员表tmember_info中查询全平台对应手机号
    private function _updateWCMemberPhone($openid) {
        if (! $openid)
            return array(
                'code' => '0001', 
                'msg' => 'OPENID不能为空');
        
        $member = M('tmember_info')->where(
            array(
                'wx_openid' => $openid, 
                'node_id' => $this->node_id))
            ->field('phone_no, mwx_openid')
            ->find();
        if ($member['phone_no']) {
            return array(
                'code' => '0000', 
                'msg' => '成功', 
                'phone' => $member['phone_no'], 
                'openid' => $member['mwx_openid']);
        }
        
        M()->startTrans();
        $memberWC = M('tmember_info')->where(
            array(
                'wx_openid' => $openid, 
                '_string' => 'phone_no is not NULL'))
            ->field('phone_no, mwx_openid')
            ->limit(2)
            ->select();
        if (count($memberWC) != 1) {
            M()->rollback();
            return false;
        } else {
            $phoneM = M("tmember_info")->where(
                array(
                    'phone_no' => $memberWC[0]['phone_no'], 
                    'node_id' => $this->node_id))->find();
            if ($phoneM) {
                $data = array(
                    'wx_openid' => $openid, 
                    'mwx_openid' => NULL);
                if ($memberWC[0]['nickname']) {
                    $data['nickname'] = $memberWC[0]['nickname'];
                }
                if ($memberWC[0]['nickLogo']) {
                    $data['nickLogo'] = $memberWC[0]['nickLogo'];
                }
                
                $upMember = M("tmember_info")->where(
                    array(
                        'id' => $phoneM['id']))->save($data);
                if (! $upMember) {
                    log_write(
                        "根据翼码授权Openid查询旺财平台会员手机号快速登录，更新当前" . $this->node_id .
                             "机构会员openid信息失败:手机号" . $memberWC[0]['phone_no']);
                    M()->rollback();
                    return false;
                }
                M()->commit();
                
                return array(
                    'code' => '0000', 
                    'msg' => '成功', 
                    'phone' => $phoneM['phone_no'], 
                    'openid' => '');
            } else {
                $data = array(
                    'node_id' => $this->node_id, 
                    'MemberPhone' => $memberWC[0]['phone_no'], 
                    'channel_id' => $this->channel_id, 
                    'batch_id' => $this->batch_id, 
                    'type' => 2, 
                    'openid' => $openid);
                if ($memberWC[0]['nickname']) {
                    $data['nickname'] = $memberWC[0]['nickname'];
                }
                if ($memberWC[0]['nickLogo']) {
                    $data['nickLogo'] = $memberWC[0]['nickLogo'];
                }
                $addMember = D("MemberInstall")->MemberAddOne($data);
                if (! $addMember) {
                    log_write(
                        "根据翼码授权Openid查询旺财平台会员手机号快速登录，" . $this->node_id .
                             "机构新增会员失败:手机号" . $memberWC[0]['phone_no']);
                    M()->rollback();
                    return false;
                }
                M()->commit();
                
                return array(
                    'code' => '0000', 
                    'msg' => '成功', 
                    'phone' => $memberWC[0]['phone_no'], 
                    'openid' => '');
            }
        }
        
        return array(
            'code' => '0000', 
            'msg' => '成功', 
            'phone' => $member['phone_no'], 
            'openid' => $member['mwx_openid']);
    }
    
    // 判断是否关注公众号，是则获取用户信息
    private function _updateWxUserMsg($access_token, $openid) {
        $wxData = M("twx_user")->where(
            array(
                'openid' => $openid, 
                'node_id' => $this->node_id))->find();
        if ($wxData['subscribe'] == '1') {
            $userInfo = $this->WeiXinService->getUser($access_token, $openid);
            if ($userInfo) {
                $data_wx = array(
                    'nickname' => $userInfo['nickname'], 
                    'sex' => $userInfo['sex'], 
                    'province' => $userInfo['province'], 
                    'city' => $userInfo['city'], 
                    'country' => $userInfo['country'], 
                    'headimgurl' => $userInfo['headimgurl']);
                $retw = M("twx_user")->where(
                    array(
                        'openid' => $openid, 
                        'node_id' => $this->node_id))->save($data_wx);
            }
        }
    }

    /*
     * 根据openid和phone来进行判断to2o_wx_config phone有值则为绑定openid和phone的关系
     * phone为空则为更具openid来获取手机号 node_id 判断是否为非标 为空为标准
     */
    private function _updateWxPhone($openid, $phone = '') {
        if (! $openid)
            return array(
                'code' => '0001', 
                'msg' => 'OPENID不能为空');
        if (C('fb_boya.node_id') == $this->node_id) {
            $model = M('to2o_wx_special');
        } else
            $model = M('to2o_wx_config');
            
            // 手机号为空则为获取手机号
        if (! $phone) {
            $phone = $model->where(
                array(
                    'openid' => $openid))->getField('phone_no');
            return array(
                'code' => '0000', 
                'msg' => '成功', 
                'phone' => $phone);
        } else {
            $count = $model->where(
                array(
                    'openid' => $openid, 
                    'phone_no' => $phone))->count();
            if ($count < 1) {
                $arr = array(
                    'openid' => $openid, 
                    'phone_no' => $phone, 
                    // 'node_id'=>$this->node_id,
                    'add_time' => date('YmdHis'));
                $ret = $model->add($arr);
                if ($ret !== false) {
                    cookie('__wc_binded_openid_and_phone_', $phone, 3600 * 24 * 365);
                    return array(
                            'code' => '0000',
                            'msg' => '成功',
                            'phone' => $phone);
                }  else {
                    return array(
                            'code' => '0010',
                            'msg' => '绑定失败',
                            'phone' => $phone);
                }

            }
        }
    }
    
    // 补充session
    private function _suppleSession($phone,$batchChannelId = '') {
        if (! $phone)
            return false;
            
            // 小店登录session
        session('groupPhone', $phone);
        log_write("===groupPhone==".session('groupPhone'));
        session('cc_node_id', $this->node_id);
        // store_mem_id
        //if (! session('?store_mem_id' . $this->node_id)) {
        // 插入tmember_info_tmp会员表

        if($batchChannelId){
            //如果有活动id 则根据id查询渠道id
            $channelId = M()->query("SELECT c.id FROM `tbatch_channel` t LEFT JOIN `tchannel` c ON c.id=t.channel_id WHERE t.id= '".$batchChannelId ."'");

            $channel_id = $channelId['0']['id'];
            if($channel_id){
                $this->channel_id = $channel_id;
            }
        }
        if(!$this->channel_id){
            //如果没有渠道 则来源渠道取会员中心
            $sns_type = CommonConst::SNS_TYPE_MEMSTORENAV;
            $memberChannel = M('tchannel')->where(array('node_id'=>$this->node_id,'type'=>'5','sns_type'=>$sns_type))->getField('id');
            $this->channel_id = $memberChannel;
        }

        $userId = addMemberByO2o($phone, $this->node_id, $this->channel_id, $this->batch_id);
        session('store_mem_id' . $this->node_id, array('user_id' => $userId));
        //}
        // 补充全局cookie
        $global_phone = cookie('_global_user_mobile');
        if (! $global_phone) {
            cookie('_global_user_mobile', $phone, 3600 * 24 * 365);
        }
        return true;
    }
    public function error($message){
        BaseAction::error($message);
    }
}
