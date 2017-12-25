<?php

class ReportManagementAction extends MyBaseAction {
    // 跳转回来地址
    const __TRUE_BACK_URL__ = '__TRUE_BACK_URL__';
    // 微信用户id
    public $expiresTime = 600;
    // 手机验证码过期时间
    public $wxid;
    public $openId = "";
    public $surl='';
    // 微信openId
    public $js_global = array();
    public $wap_sess_name = '';
    public $appid='';
    public $secret='';
    public $WeiXinService='';
    public $CodeexpiresTime='';
    public $node_short_name='';
    public $ReportManagement='';
    public $ReportDate='';
    public function _initialize() {
//        C('SHOW_PAGE_TRACE',false);
        $this->node_id=C('gansu.node_id');
        // 判断是否是微信过来的
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            echo "请从微信访问";
            exit();
        }
        $surl = I('surl');
        if ($surl == '') {
            $surl = U('Label/ReportManagement/checkLogin', array('node_id' => $this->node_id), '', '', true);
        }
        $this->expiresTime = 120; // 手机发送间隔
        $this->CodeexpiresTime = 600; // 手机动态密码过期时间
        $this->WeiXinService = D('WeiXin', 'Service');
        $this->surl=$surl;
        $weiXinInfo=M("tweixin_info")->where(array('node_id'=>$this->node_id))->find();
        $this->appid = $weiXinInfo['app_id'];
        $this->secret = $weiXinInfo['app_secret'];
        $this->wap_sess_name = 'node_wxid_' . $this->node_id;
        $nodeInfo = M('tnode_info')->where(array('node_id' => $this->node_id))->find();
        $this->node_short_name=$nodeInfo['node_short_name'];
        $this->ReportManagement = D('ReportManagement');
        $this->ReportDate=array(
                '2014'=>'2014',
                '2015'=>'2015',
                '2016'=>'2016',
                '2017'=>'2017',
                '2018'=>'2018'
        );
        $this->assign('reportDate',$this->ReportDate);
        if ( ACTION_NAME == 'checkLogin' || ACTION_NAME == 'login' || ACTION_NAME == 'reportList' || ACTION_NAME == 'checkBindOpenid') {
            $this->_ganSu_checklogin(false);
        }
    }
    public function _ganSu_checklogin($return = true) {
       // $merWxUserInfo['openid']='oOqbwv1feAY2uGaVt_99q6AqA_Y0';
       // session('gSwxUserInfo',$merWxUserInfo);
        $merWxUserInfo = session('gSwxUserInfo');
        if (isset($merWxUserInfo['openid']) && $merWxUserInfo['openid']) {
            $this->openId=$merWxUserInfo['openid'];
            return false;
        }
        // 微信首次登录 获取openid
        if (isFromWechat() && empty($merWxUserInfo)) {
            $apiCallbackUrl = U('Label/ReportManagement/callback',
                    array(
                            'id' => $this->id,
                            'node_id' => $this->node_id,
                            'surl' => urlencode($this->surl)), '', '', true);
            $this->wechatAuthorizeByNodeId($this->node_id, 0, '', $apiCallbackUrl);
        }
        return true;
    }
    public function checkLogin(){
        $this->checkBindOpenid();
    }
    public function login(){
        $res=$this->ReportManagement->getMemberIdByOpenId($this->openId);
        if($res!=false){
            session('memberInfo', $res);
            $url=U("Label/ReportManagement/reportList");
            redirect($url);
            exit;
        }
        $postData=I('post.');
        if($postData){
            $groupCheckCode = session('ganSuCheckCode');
            if (! empty($groupCheckCode) && $groupCheckCode['phoneNo'] != $postData['phone'])
                $this->ajaxReturn(array('type' => 'phone'), '手机号不正确', 0);
            if (! empty($groupCheckCode['number']) && $groupCheckCode['number'] != $postData['verify'])
                $this->ajaxReturn(array('type' => 'pass'), '动态密码不正确', 0);
            if (time() - $groupCheckCode['add_time'] > $this->CodeexpiresTime) {
                $this->ajaxReturn(array('type' => 'pass'), '动态密码已经过期', 0);
            }
            $gSwxUserInfo=session('gSwxUserInfo');
             if($gSwxUserInfo['openid']){
                 $memberId=$this->ReportManagement->getMemberIdByMobile($postData['phone']);
                 if($memberId===false){
                     $this->error('抱歉！您暂时没有数据查询权限，请与甘肃销售信息处联系。');
                 }
                 $bindRes=$this->ReportManagement->bindMemberOpenid($memberId,$gSwxUserInfo['openid']);
                 if($bindRes===false){
                     $this->error('抱歉！您暂时没有数据查询权限，请与甘肃销售信息处联系。');
                 }
                 $this->success("绑定成功！");
             }else{
                 $this->error("登录失败！");
             }
        }
        $this->display();
    }

    /**
     *
     */
    public function reportList(){
        $ajax=I('get.ajax');
        if(empty($ajax)){
            $this->assign('day',date('Y-m-d',strtotime('yesterday')));
            $this->display();
            exit;
        }
        $postData=I('post.');
        if($postData){
            if($postData['time']==''){
                $time= date('Ymd',strtotime('yesterday'));
            }else{
                $time=$postData['time'];
            }
        }else{
            $time= date('Ymd',strtotime('yesterday'));
        }
        $where=array(
                'a.openid'=>$this->openId,
        );
        $reportList=$this->ReportManagement->getReportListByOpenId($where,$time);
        $yearStr='';
        $monthStr='';
        $dayStr='';
        if($reportList){
            foreach($reportList as $key=>$val){
                $val['year_salse']=$val['year_salse'];
                $val['year_retail']=$val['year_retail'];
                $val['total_month']=intval($val['total_month']);
                $val['month_retail']=intval($val['month_retail']);
                $val['day_sales']=intval($val['day_sales']);
                $val['day_retail']=intval($val['day_retail']);
                $yearStr.="<tr>
                                            <td>{$val['company_name']}</td>
                                            <td>{$val['year_salse']}</td>
                                            <td>{$val['year_retail']}</td>
                                            <td>{$val['year_rate']}%</td>
                                            <td>{$val['year_sales_complete']}%</td>
                                            <td>{$val['year_retail_complete']}%</td>
                                        </tr>";
                $monthStr.="<tr>
                                            <td>{$val['company_name']}</td>
                                            <td>{$val['total_month']}</td>
                                            <td>{$val['month_retail']}</td>
                                            <td>{$val['month_retail_rate']}%</td>
                                        </tr>";
                $dayStr.="<tr>
                                            <td>{$val['company_name']}</td>
                                            <td>{$val['day_sales']}</td>
                                            <td>{$val['day_retail']}</td>
                                            <td>{$val['day_retail_rate']}%</td>
                                        </tr>";
            }
        }
        $this->success(array(
                'info'   =>"查询成功！",
                'yearStr'=>$yearStr,
                'monthStr'=>$monthStr,
                'dayStr'=>$dayStr,
                'status' => 1,
        ));
    }
    /*
     * 微信认证接口回调
     */
    public function callback($type = '') {
        $sessionKey = 'gSwxUserInfo';
        $wechatInfo = $this->WeiXinService->getWeixinInfoByNodeId($this->node_id);
        $code = I('code', null);
        if ($code) { // 重新获取
            $result = $this->WeiXinService->getOpenid($code, $wechatInfo);
            $openid = $result['openid'];
            $access_token = $result['access_token'];
            if ($openid == '') {
                $this->error('获取授权openid失败！');
            }
            session($sessionKey, array('openid' => $openid, 'appid' => $this->appid, 'access_token' => $access_token));
            $this->openId=$openid;
        }
        redirect(U('Label/ReportManagement/checkLogin',array('id' => $this->id, 'node_id' => $this->node_id, 'surl' => urlencode($this->surl))));
    }
    // 手机发送动态密码
    public function sendCheckCode() {
        $phoneNo = I('get.phone', null);
        if (! check_str($phoneNo, array('null' => false, 'strtype' => 'mobile'), $error)) {
            $this->ajaxReturn(array('type' => 'phone'), "手机号{$error}", 0);
        }
        $memberInfo=$this->ReportManagement->getMemberInfoByMobile($phoneNo);
        if($memberInfo===false){
            $this->error('抱歉！您暂时没有数据查询权限，请与甘肃销售信息处联系。');
        }
        if($memberInfo['openid']){
            $this->error('对不起，您所输入的手机号码已经绑定过会员了！');
        }
        // 测试环境不下发，验证码直接为1111
        if (! is_production()) {
            $groupCheckCode = array(
                    'number' => 1111,
                    'add_time' => time(),
                    'phoneNo' => $phoneNo);
            session('ganSuCheckCode', $groupCheckCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }
        // 发送频率验证
        $groupCheckCode = session('ganSuCheckCode');
        if (! empty($groupCheckCode) &&
                (time() - $groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->CodeexpiresTime / 60;
        $text = "您在{$this->node_short_name}商户的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
        $sendStatus=send_SMS($this->node_id, $phoneNo, $text);
        if($sendStatus===false){
            $this->error('验证发送失败！');
        }
        $groupCheckCode = array(
                'number' => $num,
                'add_time' => time(),
                'phoneNo' => $phoneNo);
        session('ganSuCheckCode', $groupCheckCode);
        $this->success('动态密码已发送');
    }

    public function error($message){
        BaseAction::error($message);
    }
    public function checkBindOpenid(){
        $bindStatus=$this->ReportManagement->getMemberIdByOpenId($this->openId);
        if($bindStatus===false){
            $url=U("Label/ReportManagement/login");
            redirect($url);
            exit;
        }else{
            session('memberInfo', $bindStatus);
            $url=U("Label/ReportManagement/reportList");
            redirect($url);
            exit;
        }
    }

    public function sendCheckCode1() {
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, array('null' => false, 'strtype' => 'mobile'),$error)) {
            $this->error("手机号{$error}");
        }

        $verify=I('post.verifypicture');

        if(!$verify)
        { $this->error('请输入验证码');}

        if(isset($_SESSION['verify_cj']) && session('verify_cj') != md5($verify)){
            //验证码错误
            $this->error('验证码错误');
        }
        $whiteListInfo=true;
        if($whiteListInfo===false){
            $this->error('抱歉！您手机号所绑定的油卡充值未满2000元，暂时无法参与活动');
        }

        // 测试环境不下发，验证码直接为1111
        if (! is_production()) {
            $groupCheckCode = array(
                'number' => 1111,
                'add_time' => time(),
                'phoneNo' => $phoneNo);
            session('ganSuCheckCode', $groupCheckCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }
        // 发送频率验证
        $groupCheckCode = session('ganSuCheckCode');
        if (! empty($groupCheckCode) &&
            (time() - $groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->CodeexpiresTime / 60;
        $text = "您在{$this->node_short_name}商户的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
        $sendStatus=send_SMS($this->node_id, $phoneNo, $text);
        if($sendStatus===false){
            $this->error('验证发送失败！');
        }
        $groupCheckCode = array(
            'number' => $num,
            'add_time' => time(),
            'phoneNo' => $phoneNo);
        session('ganSuCheckCode', $groupCheckCode);
        $this->success('动态密码已发送');
    }
}