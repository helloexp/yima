<?php

class MyQrCodeAction extends BaseAction
{
    public $wxUserInfo;
    public function _initialize()
    {
        $this->node_id=C('lnsy.node_id');
        $this->wxUserInfo=$this->adbWeixinAuthorize($this->node_id,false);
        
    }

    //首页
    public function index()
    {
        $map=array(
            'openid'=>$this->wxUserInfo['openid'],
            'node_id'=>$this->node_id,
            );
        $info=D('FbMyQrCode')->getMyQrCodeByWhere($map);
        if($info){
             $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '您已经申请过推广二维码，请返回主菜单点击【我的二维码】进行查看'));
        }
        $this->assign('info',$info);
        $this->display();
    }

    //提交页
    public function submit()
    {
        if(!$this->isPost()){
             $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '您访问的页面不存在'));
        }
        $data=I('post.');
        $code= session('groupCheckCode');
        if($code['number'] != $data['verify'] || $data['phone'] != $code['phoneNo'] || !session("?groupCheckCode")){
            $this->error("请输入正确的验证码");
        }
        $data['openid']=$this->wxUserInfo['openid'];
        $data['node_id']=$this->node_id;
        $model=D('FbMyQrCode');
        $res=$model->applyMyQrCode($data);
        if($res == false){
            $err=$model->getError();
            $this->error($err);
        }
        session('groupCheckCode',null);
        $this->success("恭喜您！申请成功！");
    }

    //显示图片
    public function showImg()
    {
        $map=array(
            'openid'=>$this->wxUserInfo['openid'],
            'node_id'=>$this->node_id,
            );
        $info=D('FbMyQrCode')->getMyQrCodeByWhere($map);
        if($info){
            $res=D('FbLiaoNing','Service')->createCodeByString($info);
            if($res){
                exit;
            }
        }
        $this->display("index");
    }

    //验证码
    public function checkVerify()
    {
        if(!$this->isPost()){
             $this->error(
                array(
                    'errorImg' => '__PUBLIC__/Label/Image/waperro5.png', 
                    'errorTxt' => '错误访问！', 
                    'errorSoftTxt' => '您访问的页面不存在'));
        }
        $verify=I('post.verify');
        if(!session('?verify_cj') || session('verify_cj') != md5($verify)) {
           $this->error("请输入正确的验证码！");
        }else{
            $this->success("验证成功");
        }
    }

    // 手机发送动态密码
    public function sendCheckCode() {
        
        // 图片校验码
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


    /**
     * 微信授权
     *
     * @param [type] $nodeId [description]
     * @param integer $type [description]
     * @return [type] [description]
     */
    private function adbWeixinAuthorize($nodeId, $type = 1) {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            $this->error('请在微信中打开');
        }

        if (empty($nodeId)) {
            $this->error('参数错误！');
        }
        $type = $type == 1 ? 1 : 0;
        $weixin_info = session("node_wxid_" . $this->node_id);
        if (empty($weixin_info) || empty($weixin_info['openid'])) {
            $is_back = I('get.weixinback');
            if (empty($this->WeiXinService)) {
                $this->WeiXinService = D('WeiXin', 'Service');
            }
            if ($is_back == 1) { // 授权
                log_write(print_r(I('get.'), true));
                $code = I('code', null);
                if (empty($code)) {
                    $this->error('参数错误！');
                }
                $result = $this->WeiXinService->getWeixinInfoByNodeId($nodeId);
                $result = $this->WeiXinService->weixinCallbackByParam($code,$result);
                log_write("微信授权数据：".print_r($result, true));

                if ($result['errmsg']) {
                    $this->error($result['errmsg']);
                }
                session('node_wxid_' . $this->node_id, $result);
                $weixin_info = $result;
            } else { // 跳转到微信
                $get = I('get.');
                $get['weixinback'] = 1;
                $backurl = U('', $get, '', '', true);
                $result = $this->WeiXinService->buildWechatAuthorizeParamByNodeId(
                    $nodeId, 
                    array(
                        'type' => $type, 
                        "apiCallbackUrl" => $backurl));
                log_write("微信授权地址：".print_r($result, true));
                if ($result['errmsg']) {
                    $this->error($result['errmsg']);
                }
                $result = $this->WeiXinService->processWechatAuthorize($result);
                if ($result['errmsg']) {
                    $this->error($result['errmsg']);
                }
            }
        }
        return $weixin_info;
    }

}