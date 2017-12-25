<?php

class RegAction extends Action {

    /**
     *
     * @var VisitLogModel
     */
    private $VisitLogModel;

    private $visitPage = '';

    public function _initialize() {
        $this->VisitLogModel = D('VisitLog');
        switch (ACTION_NAME) {
            case 'index':
                $title = '注册页面';
                $logInfo = '注册页面';
                break;
            default:
                $title = '注册';
                $logInfo = ACTION_NAME;
        }
        $this->visitPage = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http' .
             '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        if (ACTION_NAME !== 'nodeadd') {
            $this->VisitLogModel->logByAction($this->visitPage, $title, 
                $logInfo);
        }
    }

    public function index() {
        // 涞源页为帮助中心的标识
        $getSource = $_SERVER['HTTP_REFERER'];
        $helpArr = explode('&', $getSource);
        
        $fromurl = trim(I('get.fromurl', '', 'urldecode'));
        if ($fromurl) {
            session('fromurl', $fromurl);
        }
        
        $type = I('type');
        $auto_apply = I('auto_apply');
        $landname = I('landname');
        // 查询行业信息
        // $industryInfo =
        // M('tindustry_info')->field('industry_id,industry_name')->where("status=0")->select();
        $provinceInfo = M('tcity_code')->field('province_code,province')
            ->where("city_level=1")
            ->select();
        // $this->assign("industryInfo", $industryInfo);
        $this->assign("provinceInfo", $provinceInfo);
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $recid = trim(I('recid'));
        $this->assign("sessprovince", $_SESSION['province_code']);
        $this->assign("recid", $recid);
        $wid = I('get.wid', '', 'trim'); // 旺号
        $this->assign("wid", $wid);
        $this->assign("userInfo", $userInfo);
        $tg_id = trim(I('tg_id'));
        $this->assign("tg_id", $tg_id);
        $from_user_id = trim(I('from_user_id'));
        $this->assign("from_user_id", $from_user_id);
        
        $this->assign('landname', $landname);
        $this->assign('type', $type);
        $this->assign('auto_apply', $auto_apply);
        $this->assign('isHlepCome', $helpArr[1]);
        $this->display();
    }

    public function nodequery() { // 查询是否存在
        $nodename = trim(I('post.nodename'));
        $req_array = array(
            'QueryClientReq' => array(
                'ClientName' => $nodename));
        log_write("查询商户是否存在=" . $nodename);
        $RemoteRequest = D('RemoteRequest', 'Service');
        $reqResult = $RemoteRequest->requestYzServ($req_array);
        if ($reqResult['Status']['StatusCode'] != '0000') {
            echo "1";
        } else {
            echo "2";
        }
        exit();
    }

    public function queryName() {
        $name = I('fieldValue', '', 'trim');
        if ($name) {
            $result = M('tnode_info')->where(
                array(
                    'node_name' => $name))->select();
            if ($result) {
                $data = array(
                    trim(I('fieldId')), 
                    false);
            } else {
                $data = array(
                    trim(I('fieldId')), 
                    true);
            }
        }
        $this->ajaxReturn($data);
    }

    public function nodeadd() {
        if (is_production()) {
            $sessionVerify = session('verify');
        } else {
            $sessionVerify = session('verify_cj');
        }
        $postVerify = I('post.verify', '', 'trim,mysql_real_escape_string');
        if (!$postVerify) {
            $this->error('验证码不能为空！');
        }
        if ($sessionVerify != md5($postVerify)) {
            $this->error('验证码错误！');
        }
        
        $type = I('type');
        $auto_apply = I('auto_apply');
        $landname = I('landname');
        $data = array();
        $data['node_name'] = trim(I('post.node_name'));
        $data['node_short_name'] = mb_substr($data['node_name'], 0, 9, 'UTF-8');
        $data['regemail'] = trim(I('post.regemail'));
        $data['user_password'] = md5(trim(I('post.user_password'))); // 用户密码
        $data['contact_name'] = trim(I('post.contact_name'));
        $data['contact_phone'] = trim(I('post.contact_phone'));
        // $industry = trim(I('post.industry'));
        $data['province_code'] = trim(I('post.province_code'));
        $data['city_code'] = trim(I('post.city_code'));
        $data['reg_from'] = trim(I('post.reg_from'));
        $data['tg_id'] = I('tg_id', $_COOKIE["reg_tgxt_id"]);
        $data['client_manager'] = trim(I('post.client_manager'));
        if ($data['client_manager'] == "") {
            $data['client_manager'] = trim(I('post.from_user_id'));
        }
        $service = D('NodeReg', 'Service');
        
        session("node_name", $data['node_name']);
        session("node_short_name", $data['node_short_name']);
        session("regemail", $data['regemail']);
        session("contact_name", $data['contact_name']);
        session("contact_phone", $data['contact_phone']);
        session("province_code", $data['province_code']);
        session("city_code", $data['city_code']);
        session("client_manager", $data['client_manager']);
        
        $result = $service->nodeAdd($data);
        $nodeId = isset($result['data']['node_id']) ? $result['data']['node_id'] : '';
        $visitPageTitle = '注册';
        if ($result['status']) {
            session('verify', null);//清理图形验证码session
            // 尝试触发注册相关的任务 start
            tag('reg_task_init', $result['data']);
            tag('reg_task_finish', $result['data']);
            // 尝试触发注册相关任务 end
            
            // 添加统计
            $logInfo = '提交注册表单(注册成功)';
            $this->VisitLogModel->log($nodeId, $this->visitPage, 
                $visitPageTitle, $logInfo);
            session("node_name", null);
            session("node_short_name", null);
            session("regemail", null);
            session("contact_name", null);
            session("contact_phone", null);
            session("province_code", null);
            session("city_code", null);
            $autologininfo = base64_encode(
                json_encode(
                    array(
                        'password' => trim(I('post.user_password')), 
                        'regemail' => $data['regemail'])));
            session('autologininfo', $autologininfo);
            
            session('regok_node_id', $nodeId);
            session('regok_user_password', trim(I('post.user_password')));
            session('regok_regemail', $data['regemail']);
            
            $landname = I('landname');
            if ($landname == 'zqgq') {
                $autologininfo = session('autologininfo');
                if ($autologininfo) { // 自动登录 sso登录
                    $this->LoginService = D('Login', 'Service');
                    $this->LoginService->ssoLogin($autologininfo);
                }
                if (I('param1') == 'gq') {
                    $this->redirect('Home/MarketActive/index');
                }
                $newTime = date('Ymd');
                if ($newTime >= '20150906' && $newTime <= '20151016') {
                    $this->redirect('Ecshop/ZqCut/add');
                } else {
                    $this->redirect('Home/Index/index');
                }
            }
            if ($type == 'wangfenxiao') {
                $url = U('Home/Regok/index', 
                    array(
                        'type' => $type, 
                        'auto_apply' => $auto_apply));
            } else {
                $url = U('Home/Regok/index');
            }
            // 如果是帮助中心过来的就跳转到帮助中心页面去
            $isHelp = I('post.isHlepCome');
            if ($isHelp == 'm=Help') {
                $autologininfo = session('autologininfo');
                if ($autologininfo) { // 自动登录 sso登录
                    $this->LoginService = D('Login', 'Service');
                    $this->LoginService->ssoLogin($autologininfo);
                }
                $url = U('Home/Help/helpConter');
            }
            
            redirect($url);
        } else {
            $logInfo = '提交注册表单(注册失败):' . $result['info'];
            $this->VisitLogModel->log($nodeId, $this->visitPage, 
                $visitPageTitle, $logInfo);
            $this->error($result['info']);
        }
    }

    public function getcity() {
        $province_code = I('get.province_code');
        if ($province_code != "") {
            $allcity = M('tcity_code')->field('city_code,city')
                ->where(
                "city_level=2 AND province_code='" . $province_code . "'")
                ->select();
            
            if (! empty($allcity)) {
                $tempStr = "";
                foreach ($allcity as $k => $val) {
                    if ($tempStr != "") {
                        
                        $tempStr .= ",";
                    }
                    
                    $tempStr .= '{"CITY_CODE":"' . $val['city_code'] .
                         '","CITY":"' . trim($val['city']) . '"}';
                }
            }
            header("content-type: text/html;charset=utf-8");
            
            $echostr = "[" . $tempStr . "]";
            echo $echostr;
            exit();
        }
    }

    public function imgCode() {
//        import('ORG.Util.Image');
//        Image::buildImageVerify($length = 4, $mode = 1, $type = 'png',
//            $width = 48, $height = 22, $verifyName = 'verify');
        import('@.Service.ImageVerifyService');
        ImageVerifyService::buildImageCodeByParam('verify');
    }
    
    public function checkImgCode() {
        if (is_production()) {
            $sessionVerify = session('verify');
        } else {
            $sessionVerify = session('verify_cj');
        }
        $postVerify = I('post.verify', '', 'trim,mysql_real_escape_string');
        if (!$postVerify) {
            $this->error('验证码不能为空！');
        }
        if ($sessionVerify != md5($postVerify)) {
            $this->error('验证码错误！');
        }
        $this->success();
    }
    
    // 发送手机验证码//#9749暂时不用短信验证码
    public function sendSmsCode() {
        $mobile = I('contact_phone');
        $img_code = I('img_code');
        if ($img_code == '' || session('verify') != md5($img_code)) {
            $this->ajaxReturn(
                array(
                    'code' => - 1, 
                    'msg' => "图片验证码错误"));
        }
        session('verify', null);
        $code = mt_rand(100000, 999999);
        $content = $code . '（旺财平台验证码，十分钟内有效），请在页面中输入以完成验证，如非本人操作，请忽略本短信';
        log_write("会员注册：");
        log_write(
            implode("\n", 
                array(
                    '手机号' => $mobile, 
                    '验证码' => $code)));
        session('reg_sms_code', $code); // 记录session
        $result = D('RemoteRequest', 'Service')->smsSend($mobile, $content);
        if (! $result) {
            $this->ajaxReturn(
                array(
                    'code' => - 1, 
                    'msg' => "发送失败"));
            log_write("发送失败" . print_r($result, true));
        }
        $this->ajaxReturn(
            array(
                'code' => 0, 
                'msg' => "发送成功"));
    }

    /**
     * 注册页面验证用户邮箱是否被占
     */
    public function ajaxValidEmail() {
        $tuser = M('tuser_info');
        $regemail = trim(I('fieldValue'));
        $result = $tuser->where(
            array(
                'user_name' => $regemail))->select();
        if ($result) {
            $data = array(
                trim(I('fieldId')), 
                false);
        } else {
            $data = array(
                trim(I('fieldId')), 
                true);
        }
        $this->ajaxReturn($data);
    }
}