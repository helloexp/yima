<?php

/**
 * @登陆首页 @修改时间：2015/01/12 11:49
 */
class LoginAction extends EmptyAction {

    /**
     *
     * @var VisitLogModel
     */
    private $VisitLogModel;

    public function _initialize() {
        $this->VisitLogModel = D('VisitLog');
        switch (ACTION_NAME) {
            case 'showLogin':
                $title = '登录';
                $logInfo = '登录';
                break;
            default:
                $title = '登录';
                $logInfo = ACTION_NAME;
        }
        $visitPage = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http' .
             '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        if (ACTION_NAME !== 'ajaxLoginError') {
            $this->VisitLogModel->logByAction($visitPage, $title, $logInfo);
        }
    }

    /**
     * 增加逻辑：如果已经登录了就不显示登录页面而是显示目录页面
     */
    public function _before_showLogin() {
        $userService = D('UserSess', 'Service');
        if ($userService->isLogin()) {
            $this->redirect('Home/Index/index');
        }
    }

    public function index() {
        import('@.ORG.Util.Mobile_Detect'); // 导入文件
        $Mobile_Detect = new Mobile_Detect(); // 检测
        
        if ($Mobile_Detect->isMobile() && ! $Mobile_Detect->isTablet()) {
            $autoGoMicroSite = (boolean) I('auto_go_micro', 1, 'intval');
            if($autoGoMicroSite){
                header("location:" . C("MICRO_SITE"));
            }
        }
        if (in_array($_SERVER['HTTP_HOST'], C('cnpc_gx.domain'))) {
            if ($this->nodeId == C('cnpc_gx.node_id')) { // 石油帐号登录跳转
                header("location:index.php?g=CnpcGX&m=Index&a=index");
            } else {
                header("location:index.php?g=CnpcGX&m=Login&a=showLogin");
            }
        }
        if (in_array($_SERVER['HTTP_HOST'], C('yhb.domain'))) {
            if ($this->nodeId == C('yhb.node_id')) { // 石油帐号登录跳转
                header("location:index.php?g=Yhb&m=Index&a=index");
            } else {
                header("location:index.php?g=Yhb&m=Login&a=showLogin");
            }
        }
        if (in_array($_SERVER['HTTP_HOST'], C('zggk.domain'))) {//  中港高科帐号登录跳转
                header("location:index.php?g=Zggk&m=Login&a=showLogin");
        }
        $this->assign('nodeId', $this->nodeId);
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        if ($userInfo['node_id'] == D('TempLogin', 'Service')->getTempNodeId()) {
            session('userSessInfo',null);
            unset($userInfo);
        }
        // 查询统计数据
        $indexInfo = M('index_count')->field('merchant,activity,goods')
            ->where("rec_id=1")
            ->find();
        $merchantcount = number_format($indexInfo['merchant']);
        $activitycount = number_format($indexInfo['activity']);
        $goodscount = number_format($indexInfo['goods']);
        
        // 推荐o2o渠道
        $ChannelSql = "SELECT a.admin_query_code_img, a.id,a.batch_type,c.client_id,
		IF(d.node_name IS NULL OR d.node_name = '',c.node_short_name,d.node_name) AS node_short_name,d.name,d.push_img FROM tbatch_channel a 
					INNER JOIN (SELECT id FROM tchannel b WHERE b.type=1 AND b.sns_type=13) b ON a.channel_id=b.id  
					LEFT JOIN tnode_info c ON a.node_id=c.node_id 
					LEFT JOIN tmarketing_info d ON a.batch_id=d.id 
 					ORDER BY d.batch_o2o_push_time DESC,a.id DESC LIMIT 8";
        $query_arr = M()->query($ChannelSql);
        
        // 分类显示
        $news = M('tym_news');
        $LatestNews = $news->where('class_id=1 and check_status=2 and status=1')
            ->order('to_index desc,add_time desc')
            ->limit(6)
            ->select();
        
        $MediaReports = $news->where(
            'class_id=2 and check_status=2 and status=1')
            ->order('to_index desc, add_time desc')
            ->limit(6)
            ->select();
        
        $adds = $news->where('class_id=19 and status=1 and to_index=1')
            ->order('sort desc')
            ->limit(18)
            ->select();
        $index_count = count($adds);
        $other_count = 18 - $index_count;
        if ($other_count > 0) {
            $other_arr = $news->where('class_id=19 and status=1 and to_index=0')
                ->order('add_time desc')
                ->limit($other_count)
                ->select();
            if ($other_arr) {
                foreach ($other_arr as $v) {
                    $adds[] = $v;
                }
            }
        }
        
        // 友情链接
        $link_arr = M('tym_news')->where(
            array(
                'class_id' => '20', 
                'status' => '1'))->select();
        $this->assign('link_arr', $link_arr);
        
        // 图片分类
        $banner_arr = M('tym_news')->where(
            array(
                'class_id' => '31', 
                'status' => '1'))
            ->order('sort desc')
            ->select();
        $this->assign('banner_arr', $banner_arr);
        
        $tsystem_param = M('tsystem_param')->field('param_value')
            ->where(
            "param_name in('HOME_PAGE_SHOP_NUMBER','HOME_PAGE_BATCH_NUMBER','HOME_PAGE_IN_NUMBER')")
            ->select();
        
        if (is_numeric($tsystem_param['0']['param_value'])) {
            $tsystem_param['shanghu'] = number_format(
                    $tsystem_param['0']['param_value']);
        }
        if (is_numeric($tsystem_param['1']['param_value'])) {
            $tsystem_param['huodong'] = number_format(
                    $tsystem_param['1']['param_value']);
        }
        if (is_numeric($tsystem_param['2']['param_value'])) {
            $tsystem_param['geren'] = number_format(
                    $tsystem_param['2']['param_value']);
        }

        
        $this->assign('tsystem_param', $tsystem_param);
        $imgName = C('O2O_DEFULT_IMG');
        $batch_name_arr = C('BATCH_TYPE_NAME');
        $this->assign('imgName', $imgName);
        $this->assign('batch_name_arr', $batch_name_arr);
        $this->assign("query_arr", $query_arr);
        $this->assign("merchantcount", $merchantcount);
        $this->assign("activitycount", $activitycount);
        $this->assign("goodscount", $goodscount);
        $this->assign("userInfo", $userInfo);
        $this->assign("LatestNews", $LatestNews);
        $this->assign("MediaReports", $MediaReports);
        $this->assign("adds", $adds);
        $this->display();
    }

    public function buyIndex() {
        $this->display();
    }

    public function ssologin() {
        $zqgqtype = I('zqgqtype');
        $loginError = session('loginError');
        $autologininfo = isset($_REQUEST['autologininfo']) ? $_REQUEST['autologininfo'] : '';
        $autologin_flag = false;
        // print_r($_REQUEST);
        // exit;
        if (! empty($autologininfo)) {
            $autologin_flag = true;
            $array = (array) json_decode(base64_decode($autologininfo));
            $email = trim($array['regemail']);
            $password = trim($array['password']);
            session('fromurl', $_REQUEST['fromurl']);
        } else {
            // SSO登录
            $email = trim(I('post.email'));
            $password = trim(I('post.password'));
            $verify = trim(I('post.verify'));
            $rememberMe = trim(I('rememberMe'));
        }
        $fromurl = session('fromurl');
        if ($rememberMe == 1) {
            setcookie("emailCookie", $email);
        }
        
        if ($email == "") {
            if ($autologin_flag)
                redirect(U('Home/Login/showLogin'));
            $this->printAjaxLoginError('登录名不能为空');
            $this->error("登录名不能为空！<a href='javascript:openlogin();'>重新登录</a>");
        }
        if ($password == "") {
            if ($autologin_flag)
                redirect(U('Home/Login/showLogin'));
            $this->printAjaxLoginError('登录密码不能为空');
            $this->error("登录密码不能为空！<a href='javascript:openlogin();'>重新登录</a>");
        }
        if ($autologin_flag == false && $loginError && isset(
            $_SESSION['verify']) && session('verify') != md5($verify)) {
            $this->printAjaxLoginError('验证码错误');
            $this->error("验证码错误！<a href='javascript:openlogin();'>重新登录</a>");
        }
        
        // 获取机构是否重复
        $nodecount = M('tuser_info')->field('node_id')
            ->where("user_name='" . $email . "'")
            ->count();
        if ($nodecount > 1) {
            if ($autologin_flag)
                redirect(U('Home/Login/showLogin'));
            $this->printAjaxLoginError('登录失败，用户存在重复');
            $this->error(
                "登录失败，用户存在重复！<a href='javascript:openlogin();'>重新登录</a>");
        }
        
        // 查询机构
        $uInfo = M('tuser_info')->field('node_id,user_id,login_time')
            ->where("user_name='" . $email . "'")
            ->find();
        if ($uInfo['user_id'] == "") {
            if ($autologin_flag)
                redirect(U('Home/Login/showLogin'));
            $this->printAjaxLoginError('登录用户不存在');
            $this->error("登录用户不存在！");
        }
        
        $req_array = array(
            "email" => $email, 
            "password" => $password, 
            "node_id" => $uInfo['node_id']);
        $RemoteRequest = D('UserSess', 'Service');
        $reqResult = $RemoteRequest->ssoLogin($req_array);
        
        // 查询是否第一次登陆
        $userloginInfo = M('tuser_info')->field('login_time,first_time')
            ->where("user_name='" . $email . "'")
            ->find();

        log_write($email . "获取登录时间是否为空：" . $userloginInfo['login_time']);
        log_write($email . '$reqResult:' . var_export($reqResult,1));

        if ($reqResult === true) {
            session('loginError', null);
            // 更新ip,登陆时间
            $data = array(
                'login_time' => date('YmdHis'), 
                'login_ip' => get_client_ip(), 
                'update_time' => date('YmdHis'));
            if (! $userloginInfo['login_time'])
                $data['first_time'] = date('YmdHis');
            elseif ($userloginInfo['login_time'] &&
                 ! $userloginInfo['first_time'])
                $data['first_time'] = date('YmdHis');
            $result = M('tuser_info')->where(
                "node_id='" . $uInfo['node_id'] . "'" . " AND " . "user_id='" .
                     $uInfo['user_id'] . "'")->save($data);
            node_log("登录", null, 
                array(
                    'log_type' => 'LOGIN'));
            $qsql = M()->getLastSql();
            log_write(
                "更新用户===node_id=" . $uInfo['node_id'] . "==user_id=" .
                     $uInfo['user_id'] . "===" . $qsql);
            
            $node_id = isset($uInfo['node_id']) ? $uInfo['node_id'] : '';
            $visit_page = U('Home/Log/ssologin', '', '', false, true);
            $visit_page_title = '登录';
            $log_info = '登录成功';
            D('VisitLog')->log($node_id, $visit_page, $visit_page_title, 
                $log_info);
            
            if (! $result) {
                log_write('更新ip，上次登陆时间失败');
            }
            unset($_SESSION['verify']);
            
            if (! empty($uInfo)) {
                $nodeInfo = M('tnode_info')->field(
                    'node_id,contract_no,status_tips')
                    ->where("node_id='" . $uInfo['node_id'] . "'")
                    ->find();
                $contract_no = $nodeInfo['contract_no'];
                if ($nodeInfo['contract_no'] == "") {
                    // 查询结算号
                    $trans_seq = date('YmdHis') . rand(10000, 99999);
                    $req_array = array(
                        'QueryShopContractReq' => array(
                            'TransactionID' => $trans_seq, 
                            'SystemID' => C('YZ_SYSTEM_ID'), 
                            'NodeID' => $nodeInfo['node_id']));
                    log_write("查询结算号=" . $nodeInfo['node_id']);
                    $RemoteRequest = D('RemoteRequest', 'Service');
                    $qryResult = $RemoteRequest->requestYzServ($req_array);
                    log_write("查询结算号" . print_r($qryResult, true));
                    if ($qryResult['Status']['StatusCode'] == '0000') {
                        $contract_no = $qryResult['ContractID'];
                        $condata = array(
                            'contract_no' => $qryResult['ContractID'], 
                            'update_time' => date('YmdHis'));
                        $conresult = M('tnode_info')->where(
                            "node_id='" . $qryResult['NodeID'] . "'")->save(
                            $condata);
                        if (! $conresult) {
                            log_write('更新结算号数据库错误！');
                        }
                    } else {
                        log_write("机构" . $qryResult['NodeID'] . "查询结算号失败");
                    }
                }
            }
            
            // 判断是否是今天第一次登录，用户弹出劳动最光荣提示
            if ($uInfo['login_time'] == '' ||
                 substr($uInfo['login_time'], 0, 8) != date('Ymd')) {
                session('today_first_login', true);
            }
            // 检测是否是从帮助中心页面过来
            $ishelp = I('isHelp');
            if ($ishelp == '1') {
                $this->ajaxReturn(
                    array(
                        'success' => 1, 
                        'redirectUrl' => "index.php?g=Home&m=Help&a=helpConter"));
            }
            
            // 大转盘专用,检查是否完善信息
            $nodeInfo = M('tnode_info')->getByNode_id($uInfo['node_id']);
            if ($nodeInfo['contact_eml'] == $email . '@7005.com.cn' &&
                 $nodeInfo['reg_from'] == '6') {
                $this->ajaxReturn(
                    array(
                        'success' => 1, 
                        'redirectUrl' => "index.php?g=Home&m=AccountInfo&a=finishReg"));
            }
            if ($fromurl != '') {
                // header("location:".htmlspecialchars_decode($fromurl));
                session('fromurl', null);
                if ($autologin_flag)
                    header("location:$fromurl");
                switch($fromurl){
                    // 产品介绍页 多乐互动 套餐区 立即购买
                    case "buyWc":
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => "index.php?g=Home&m=Wservice&a=basicVersion"));
                    // 产品介绍页 多乐互动 单品 立即购买
                    case "marketToolVersion":
                        if(strpos($nodeInfo['pay_module'], 'm1')){
                            $redirectUrl = "index.php?g=Home&m=MarketActive&a=createNew";
                        }else{
                            $redirectUrl = "index.php?g=Home&m=Wservice&a=marketToolVersion";
                        }
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => $redirectUrl));
                    // 产品介绍页 条码支付 申请开通
                    case "alipay":
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => "index.php?g=Alipay&m=Index&a=index"));
                    // 产品介绍页 申请验证终端
                    case "Wapply_terminal":
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => "index.php?g=Home&m=Store&a=Wapply_terminal"));
                    // 产品介绍页 会员积分 会员管理
                    case "promotionn":
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => "index.php?g=Wmember&m=Member&a=promotionn4880"));
                    // 产品介绍页 会员积分 多赢积分
                    case "integralMarketing":
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => "index.php?g=Integral&m=Integral&a=index"));
                    default:
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => htmlspecialchars_decode($fromurl)));
                }
            } else {
                $lname = trim(I('post.landname'));
                $wap = I('post.wap');
                if (! empty($wap)) {
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Alipay&m=Paywap&a=accountMsg&type=login"));
                }
                // 落地页监控登录逻辑
                switch($lname){
                    // 微信营销
                    case "wxyx":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Weixin&m=Weixin&a=index"));
                    break;
                    //二维码名片
                    case "ymp":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Wap&m=Vcard&a=index"));
                    break;
                    // 新浪微博
                    case "weibozhushou":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=LabelAdmin&m=Weibo&a=index"));
                    break;
                    // 直达号
                    case "zhidahao":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=LabelAdmin&m=Med&a=index"));
                    break;
                    // 微官网
                    case "wgw":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=MicroWeb&m=Index&a=index"));
                    break;
                    // 炫码
                    case "xuanma":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=MarketActive&m=VisualCode&a=selectType"));
                    break;
                    // 炫码
                    case "xuanmaredirect":
                    $this->ajaxReturn(
                    array(
                        'success' => 1, 
                        'redirectUrl' => "index.php?g=MarketActive&m=VisualCode&a=done&istmp=1"));
                    break;
                    // 炫码
                    case "xuanmaregdirect":
                    $this->ajaxReturn(
                    array(
                        'success' => 1, 
                        'redirectUrl' => "index.php?g=MarketActive&m=VisualCode&a=selectType"));
                    break;
                    // 支付宝线下收单
                    case "zfb":
                    case "tmzf":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Alipay&m=Index&a=index"));
                    break;
                    // 抽奖
                    case "wcj":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Home&m=MarketActive&a=createNew&typelist=1"));
                    break;
                    // 有奖问答
                    case "yjwd":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=LabelAdmin&m=Answers&a=add&model=event&type=question&action=create&customer=c2"));
                    break;
                    // 市场调研
                    case "scdy":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=LabelAdmin&m=Bm&a=add&model=event&type=survey&action=create&customer=c2"));
                    break;
                    // 礼品派发
                    case "lppf":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Home&m=MarketActive&a=createNew&typelist=3"));
                    break;
                    // 优惠劵
                    case "yhj":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Home&m=MarketActive&a=listNew&batchtype=9"));
                    break;
                    // 列表模板
                    case "zhyx":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Home&m=MarketActive&a=listNew&liststyle=2&batchtype=8"));
                    break;
                    // 条码支付
                    case "pay":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Alipay&m=Index&a=index&landname=pay"));
                    break;
                    // 投票
                    case "wtp":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Home&m=MarketActive&a=createNew&typelist=2"));
                    break;
                    // 旺财小店
                    case "wcxd":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Ecshop&m=Index&a=preview"));
                    break;
                    // 幸运大转盘
                    case "xydzp":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=LabelAdmin&m=DrawLotteryAdmin&a=setActBasicInfo"));
                    break;
                    // 付满送
                    case "fms":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Alipay&m=Index&a=index"));
                    break;
                    // 多乐互动
                    case "dlhd":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=MarketActive&m=Activity&a=createFestival"));
                    break;
                    // 端午节
                    case "dwj":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=MarketActive&m=Activity&a=createFestival"));
                    break;
                    // 中秋国庆
                    case "zqgq":
                    $userService = D('UserSess', 'Service');
                    $userInfo = $userService->getUserInfo();
                    $newTime = date('Ymd');
                    if ($zqgqtype == 'zq') {
                        if ($newTime >= '20150906' && $newTime <= '20151016') {
                            
                            $dataArr = array(
                                'node_id' => $userInfo['node_id'], 
                                'batch_type' => '55');
                            $dataInfo = M('tmarketing_info')->where($dataArr)->find();
                            if ($dataInfo == '') {
                                $this->ajaxReturn(
                                    array(
                                        'success' => 1, 
                                        'redirectUrl' => "index.php?g=Ecshop&m=O2OHot&a=index&batch_type=55"));
                            }
                            if ($dataInfo['status'] == '1') {
                                $this->ajaxReturn(
                                    array(
                                        'success' => 1, 
                                        'redirectUrl' => "index.php?g=Ecshop&m=O2OHot&a=index&batch_type=55"));
                            }
                        } else {
                            $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=Home&m=Index&a=index"));
                        }
                    }
                    if ($zqgqtype == 'gq') {
                        $this->ajaxReturn(
                                array(
                                    'success' => 1, 
                                    'redirectUrl' => "index.php?g=MarketActive&m=Activity&a=createFestival"));
                    }
                    break;
                    // 旺分销
                    case "wangfenxiao":
                    if (M('twfx_node_info')->getFieldByNode_id($this->node_id, 
                        'server_status') == '1') {
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => "index.php?g=Wfx&m=Index&a=index"));
                    } else {
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => "index.php?g=Wfx&m=Index&a=index&auto_apply=1"));
                    }
                    break;
                    // 多米收单
                    case "dmsd":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Alipay&m=Index&a=index&landname=dmsd"));
                    break;
                    // 电子海报
                    case "dzhb":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=MarketActive&m=NewPoster&a=revisePosterDataToNewNode"));
                    break;
                    // 电子海报登陆后直接跳编辑页
                    case "dzhbdirect":
                        $this->ajaxReturn(
                        array(
                        'success' => 1,
                        'redirectUrl' => "index.php?g=MarketActive&m=NewPoster&a=revisePosterDataToNewNode&backToEditPoster=1"));
                    break;
                    // 欧洲杯
                    case "ecup":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=MarketActive&m=Activity&a=createFestival&landname=ecup"));
                    break;
                    // Q码申请
                    case "qqgzh":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=QqPublicNumber&m=QqCode&a=index&landname=qqgch"));
                    break;
                    // 卡券营销
                    case "kqyx":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=WangcaiPc&m=NumGoods&a=index&landname=kqyx"));
                    break;
                    // 卡券管理
                    case "kqgl":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=WangcaiPc&m=NumGoods&a=index&landname=kqgl"));
                    break;
                    // 卡券商城
                    case "mall":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Hall&m=Index&a=index&landname=mall"));
                    break;
                    // 多赢积分
                    case "dyjf":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Integral&m=Integral&a=integralMarketing&landname=dyjf"));
                    break;
                    // 金猴闹新春
                    case "jhnxc":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=Home&m=MarketActive&a=createNew&typelist=4&landname=jhnxc"));
                    break;
                    // 微信红包
                    case "wxhb":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=WangcaiPc&m=NumGoods&a=ymWeChatIndex&landname=wxhb"));
                    break;
                    // 妈妈,我爱你(母亲节)
                    case "mama":
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?g=MarketActive&m=Activity&a=createFestival&landname=mama"));
                    break;
                    // 原旺财登录逻辑
                    default:
                    if ($uInfo['node_id'] == C('Yhb.node_id')) { // 翼惠宝帐号登录跳转
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => U('Yhb/Index/index')));
                    }
                    if ($uInfo['node_id'] == C('cnpc_gx.node_id')) { // 翼惠宝帐号登录跳转
                        $this->ajaxReturn(
                            array(
                                'success' => 1, 
                                'redirectUrl' => U('CnpcGX/Index/index')));
                    }
                    if ($uInfo['node_id'] == C('zggk.node_id')) { // 翼惠宝帐号登录跳转
                            $this->ajaxReturn(
                                    array(
                                            'success' => 1,
                                            'redirectUrl' => U('Zggk/Index/index')));
                    }
                    if ($autologin_flag)
                        header("location:index.php?m=Index&a=index");
                    $this->ajaxReturn(
                        array(
                            'success' => 1, 
                            'redirectUrl' => "index.php?m=Index&a=index"));
                }
            }
            exit();
        } else {
            if ($autologin_flag)
                redirect(U('Home/Login/showLogin'));
            $reqResult = str_replace('机构号、', '', $reqResult);
            $this->printAjaxLoginError("登录失败:{$reqResult}");
            // $this->error("登录失败:{$reqResult}！<a href='javascript:openlogin();'
            // >重新登录</a>");
        }
    }

    public function ssoAjaxlogin() {
        // SSO登录
        $email = trim(I('post.email'));
        $password = trim(I('post.password'));
        $verify = trim(I('post.verify'));
        $rememberMe = trim(I('rememberMe'));
        $fromurl = trim(I('post.fromurl'));
        if ($rememberMe == 1) {
            setcookie("emailCookie", $email);
        }
        
        if ($email == "") {
            $this->error("登录名不能为空！");
        }
        if ($password == "") {
            $this->error("登录密码不能为空！");
        }
        
        if (session('verify') != md5($verify)) {
            $this->error("验证码错误！");
        }
        
        // 获取机构是否重复
        $nodecount = M('tuser_info')->field('node_id')
            ->where("user_name='" . $email . "'")
            ->count();
        if ($nodecount > 1) {
            $this->error("登录失败，用户存在重复！");
        }
        
        // 查询机构
        $uInfo = M('tuser_info')->field('node_id,user_id')
            ->where("user_name='" . $email . "'")
            ->find();
        if ($uInfo['user_id'] == "") {
            $this->error("登录用户不存在！");
        }
        
        $req_array = array(
            "email" => $email, 
            "password" => $password, 
            "node_id" => $uInfo['node_id']);
        $RemoteRequest = D('UserSess', 'Service');
        $reqResult = $RemoteRequest->ssoLogin($req_array);
        
        // 查询是否第一次登陆
        $userloginInfo = M('tuser_info')->field('login_time,first_time')
            ->where("user_name='" . $email . "'")
            ->find();
        
        log_write($email . "获取登录时间是否为空：" . $userloginInfo['login_time']);
        
        if ($reqResult === true) {
            
            // 更新ip,登陆时间
            $data = array(
                'login_time' => date('YmdHis'), 
                'login_ip' => get_client_ip(), 
                'update_time' => date('YmdHis'));
            if (! $userloginInfo['login_time'])
                $data['first_time'] = date('YmdHis');
            elseif ($userloginInfo['login_time'] &&
                 ! $userloginInfo['first_time'])
                $data['first_time'] = date('YmdHis');
            $result = M('tuser_info')->where(
                "node_id='" . $uInfo['node_id'] . "'" . " AND " . "user_id='" .
                     $uInfo['user_id'] . "'")->save($data);
            node_log("登录", null, 
                array(
                    'log_type' => 'LOGIN'));
            $qsql = M()->getLastSql();
            log_write(
                "更新用户===node_id=" . $uInfo['node_id'] . "==user_id=" .
                     $uInfo['user_id'] . "===" . $qsql);
            if (! $result) {
                log_write('更新ip，上次登陆时间失败');
            }
            unset($_SESSION['verify']);
            
            if (! empty($uInfo)) {
                $nodeInfo = M('tnode_info')->field('node_id,contract_no')
                    ->where("node_id='" . $uInfo['node_id'] . "'")
                    ->find();
                
                if ($nodeInfo['contract_no'] == "") {
                    // 查询结算号
                    $trans_seq = date('YmdHis') . rand(10000, 99999);
                    $req_array = array(
                        'QueryShopContractReq' => array(
                            'TransactionID' => $trans_seq, 
                            'SystemID' => C('YZ_SYSTEM_ID'), 
                            'NodeID' => $nodeInfo['node_id']));
                    log_write("查询结算号=" . $nodeInfo['node_id']);
                    $RemoteRequest = D('RemoteRequest', 'Service');
                    $qryResult = $RemoteRequest->requestYzServ($req_array);
                    log_write("查询结算号" . print_r($qryResult, true));
                    if ($qryResult['Status']['StatusCode'] == '0000') {
                        
                        $condata = array(
                            'contract_no' => $qryResult['ContractID'], 
                            'update_time' => date('YmdHis'));
                        $conresult = M('tnode_info')->where(
                            "node_id='" . $qryResult['NodeID'] . "'")->save(
                            $condata);
                        if (! $conresult) {
                            log_write('更新结算号数据库错误！');
                        }
                    } else {
                        // $this->error("登录失败，查询结算号失败！<a
                        // href='javascript:openlogin();' >重新登录</a>");
                        log_write("机构" . $qryResult['NodeID'] . "查询结算号失败");
                    }
                }
            }
            
            $this->success('登录成功！');
            
            /*
             * if($userloginInfo['login_time']=="") {
             * header("location:index.php?g=Home&m=Firstlogin"); exit; }else {
             * if($fromurl != ''){
             * header("location:".htmlspecialchars_decode($fromurl)); }else{
             * header("location:index.php?m=Index&a=index"); } exit; }
             */
        } else {
            $this->error("登录失败:{$reqResult}！");
        }
    }

    /*
     * public function pageInfo(){ set_time_limit( 0 ); $sucess = 1; $i = 0;
     * $model = M('tnews_batch'); while(true){ $dataInfo =
     * $model->field('id,wap_page_title_one,wap_page_info_one,wap_page_img_one,wap_page_title_two,wap_page_info_two,wap_page_img_two,wap_page_title_three,wap_page_info_three,wap_page_img_three')
     * ->limit($i,1) ->select(); $i++; if(!$dataInfo) break; $str = '';
     * if(!empty($dataInfo[0]['wap_page_title_one'])){ $str .=
     * '<p>'.$dataInfo[0]['wap_page_title_one'].'</p>'; }
     * if(!empty($dataInfo[0]['wap_page_img_one'])){ $str .= '<p><img
     * src="/Home/Upload/'.$dataInfo[0]['wap_page_img_one'].'"/></p>'; }
     * if(!empty($dataInfo[0]['wap_page_info_one'])){ $str .=
     * '<p>'.$dataInfo[0]['wap_page_info_one'].'</p>'; }
     * if(!empty($dataInfo[0]['wap_page_title_two'])){ $str .=
     * '<p>'.$dataInfo[0]['wap_page_title_two'].'</p>'; }
     * if(!empty($dataInfo[0]['wap_page_img_two'])){ $str .= '<p><img
     * src="/Home/Upload/'.$dataInfo[0]['wap_page_img_two'].'"/></p>'; }
     * if(!empty($dataInfo[0]['wap_page_info_two'])){ $str .=
     * '<p>'.$dataInfo[0]['wap_page_info_two'].'</p>'; }
     * if(!empty($dataInfo[0]['wap_page_title_three'])){ $str .=
     * '<p>'.$dataInfo[0]['wap_page_title_three'].'</p>'; }
     * if(!empty($dataInfo[0]['wap_page_img_three'])){ $str .= '<p><img
     * src="/Home/Upload/'.$dataInfo[0]['wap_page_img_three'].'"/></p>'; }
     * if(!empty($dataInfo[0]['wap_page_info_three'])){ $str .=
     * '<p>'.$dataInfo[0]['wap_page_info_three'].'</p>'; } if(!$str) continue;
     * $data = array('wap_page_info'=>$str); $result =
     * $model->where("id={$dataInfo[0]['id']}")->save($data); if($result){
     * $sucess++; } } echo '已更新'.$sucess.'条'; }
     */
    /*
     * 游客留言
     */
    /*
     * public function visitor_feedback(){ $phone = I('post.phone');
     * if($phone=='请输入联系电话'){ $phone = ''; } $title = I('post.title'); $content
     * = I('post.content'); $error = '';
     * if(!check_str($phone,array('null'=>true,'strtype'=>'mobile','maxlen_cn'=>'11'),$error)){
     * $error = "联系电话{$error}"; }
     * if(!check_str($title,array('null'=>false,'maxlen_cn'=>'25'),$error)){
     * $error = "标题{$error}"; }
     * if(!check_str($content,array('null'=>false,'maxlen_cn'=>'100'),$error)){
     * $error = "内容{$error}"; } $data = array( 'leave_phone'=>$phone,
     * 'leave_title'=>$title, 'leave_content' => $content, 'leave_time' =>
     * date('YmdHis'), 'type' =>'1' ); $result =
     * M('tmessage_feedback')->data($data)->add(); $content =
     * "联系电话：{$phone}<br/>留言标题：{$title}<br/>留言内容：{$content}<br/>日期：".date('Y-m-d
     * H:i:s'); $ps = array( "subject"=>"旺财营销平台-商户留言", "content"=>$content,
     * "email"=>"wuqx@imageco.com.cn", ); $resp = send_mail($ps); $jumpUrl =
     * array('返回'=>'javascript:history.go(-1)');
     * if($result&&$resp['sucess']=='1'){ $message = "留言已经添加成功，感谢您的留言！"; }else{
     * $message = "系统错误,您的留言添加失败"; } $this->assign("error",$error);
     * $this->assign("message",$message); $this->assign("jumpUrl",$jumpUrl);
     * $this->assign("jumpUrlList",$jumpUrl); $this->display(APP_PATH .
     * '/Tpl/Public/Public_msg.html'); }
     */
    // 在线留言
    public function userFeedback() {
        if (IS_AJAX) {
            $phone = I('post.phone');
            $title = '在线留言';
            $content = I('post.content');
            if (empty($content) || $content == '请填写内容哦~')
                $this->error('请填写内容哦~');
            
            $img_node = str_replace('..', '', I('post.img_node'));
            $error = '';
            if (! check_str($content, 
                array(
                    'null' => false, 
                    'maxlen_cn' => '100'), $error)) {
                $this->error("内容{$error}");
            }
            
            $node_info = M('tnode_info')->field('node_name,contact_phone')
                ->where("node_id='" . $this->nodeId . "'")
                ->find();
            if ($phone == '邮箱/用户名/手机都可以啦~') {
                $phone_new = $node_info['contact_phone'];
            } else {
                $phone_new = $phone;
            }
            
            // 000000表示游客
            $data = array(
                'node_id' => 000000, 
                'leave_title' => $title,  // 前端去掉标题
                'leave_phone' => $phone, 
                'leave_content' => $content, 
                'upload_img' => $img_node, 
                'leave_time' => date('YmdHis'), 
                'type' => '1');
            $result = M('tmessage_feedback')->data($data)->add();
            
            $content = "商户名：{$node_info['node_name']}<br/>联系方式：{$phone}<br/>留言标题：{$title}<br/>留言内容：{$content}<br/>图片：{$flieWay}<br/>日期：" .
                 date('Y-m-d H:i:s');
            $ps = array(
                "subject" => "旺财营销平台-商户留言", 
                "content" => $content, 
                "email" => "7005@imageco.com.cn"); // 原邮箱wuqx@imageco.com.cn
            
            $resp = send_mail($ps);
            if ($result && $resp['sucess'] == '1') {
                $this->success('提交成功！');
            } else {
                $this->error('系统错误,您的留言添加失败');
            }
        } else
            $this->display();
    }

    /**
     * 显示登陆页面
     */
    public function showLogin() {
        $zqgqtype = I('zqgqtype');
        $fromurl = trim(I('get.fromurl', '', 'urldecode'));
        if ($fromurl) {
            session('fromurl', $fromurl);
        }
        $lname = trim(I('get.landname', ''));
        if ($lname != "") {
            $this->assign("landname", $lname);
        }
        
        $this->assign('zqgqtype', $zqgqtype);
        $this->display();
    }

    public function ajaxIsLoginError() {
        $loginError = session('loginError');
        $this->ajaxReturn(
            array(
                'email', 
                true));
    }

    public function ajaxLoginError() {
        $loginError = session('loginError');
        if ($loginError) {
            $this->ajaxReturn(
                array(
                    'loginError' => $loginError));
        } else {
            $this->ajaxReturn(array(
                'loginError' => - 1));
        }
    }

    /**
     * @param string $message
     * @param string $jumpUrl
     * @param bool   $ajax
     */
    protected function error($message = '', $jumpUrl = '', $ajax = false) {
        if ($jumpUrl && ! is_array($jumpUrl)) {
            $jumpUrl = array(
                '现在跳转' => $jumpUrl);
        }
        if ($jumpUrl === '') {
            $jumpUrl = array(
                '返回' => 'javascript:history.go(-1)');
        }
        $this->assign('jumpUrlList', $jumpUrl, $ajax);
        M()->rollback(); // 回滚事务
        parent::error($message, $jumpUrl, $ajax);
    }
    
    // 重定义正确输出
    protected function success($message = '', $jumpUrl = '', $ajax = false) {
        if ($jumpUrl && ! is_array($jumpUrl)) {
            $jumpUrl = array(
                '现在跳转' => $jumpUrl);
        }
        if ($jumpUrl === '') {
            $jumpUrl = array(
                '返回' => 'javascript:history.go(-1)');
        }
        $this->assign('jumpUrlList', $jumpUrl);
        parent::success($message, $jumpUrl, $ajax);
    }

    private function printAjaxLoginError($error_msg) {
        if ($this->isAjax()) {
            session('loginError', $error_msg);
            $this->ajaxReturn(
                array(
                    'success' => 0, 
                    'error_msg' => $error_msg));
        }
    }
}
