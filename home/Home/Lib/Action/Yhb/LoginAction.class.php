<?php

/**
 * @登陆首页 @修改时间：2015/01/12 11:49
 */
class LoginAction extends Action {

    /**
     * 增加逻辑：如果已经登录了就不显示登录页面而是显示目录页面
     */
    public function _before_showLogin() {
        $userService = D('UserSess', 'Service');
        if ($userService->isLogin()) {
            $this->redirect('Yhb/Index/index');
        }
    }

    public function index() {
        import('@.ORG.Util.Mobile_Detect'); // 导入文件
        $Mobile_Detect = new Mobile_Detect(); // 检测
        
        if ($Mobile_Detect->isMobile() && ! $Mobile_Detect->isTablet()) {
            
            header("location:" . C("MICRO_SITE"));
        }
        
        $this->assign('nodeId', $this->nodeId);
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        // 查询统计数据
        $indexInfo = M('index_count')->field('merchant,activity,goods')
            ->where("rec_id=1")
            ->find();
        $merchantcount = number_format($indexInfo['merchant']);
        $activitycount = number_format($indexInfo['activity']);
        $goodscount = number_format($indexInfo['goods']);
        
        // 推荐o2o渠道
        $ChannelSql = "SELECT a.id,a.batch_type,c.client_id,
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
        
        $tsystem_param['shanghu'] = number_format(
            $tsystem_param['0']['param_value']);
        $tsystem_param['huodong'] = number_format(
            $tsystem_param['1']['param_value']);
        $tsystem_param['geren'] = number_format(
            $tsystem_param['2']['param_value']);
        
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
        $loginError = session('loginError');
        $autologininfo = $_REQUEST['autologininfo'];
        $autologin_flag = false;
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
                redirect(U('Yhb/Login/showLogin'));
            $this->printAjaxLoginError('登录名不能为空');
            $this->error("登录名不能为空！<a href='javascript:openlogin();'>重新登录</a>");
        }
        if ($password == "") {
            if ($autologin_flag)
                redirect(U('Yhb/Login/showLogin'));
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
                redirect(U('Yhb/Login/showLogin'));
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
                redirect(U('Yhb/Login/showLogin'));
            $this->printAjaxLoginError('登录用户不存在');
            $this->error("登录用户不存在！");
        }
        
        $req_array = array(
            "email" => $email, 
            "password" => $password, 
            "node_id" => $uInfo['node_id']);
        $RemoteRequest = D('UserSess', 'Service');
        $reqResult = $RemoteRequest->ssoLogin($req_array);
        if (strpos($reqResult, '400')) {
            $reqResult = '您的账户已被停用，如需帮助请联系客服，给您带来不便请谅解！';
        }
        // 查询是否第一次登陆
        $userloginInfo = M('tuser_info')->field('login_time,first_time')
            ->where("user_name='" . $email . "'")
            ->find();
        
        log_write($email . "获取登录时间是否为空：" . $userloginInfo['login_time']);
        
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
            
            if ($fromurl != '') {
                session('fromurl', null);
                if ($autologin_flag)
                    header("location:$fromurl");
                $this->ajaxReturn(
                    array(
                        'success' => 1, 
                        'redirectUrl' => htmlspecialchars_decode($fromurl)));
            } else {
                if (C('yhb.node_id') != $uInfo['node_id']) {
                    header("location:index.php?g=Home&m=Index&a=index");
                }
                if ($autologin_flag) {
                    header("location:index.php?g=Yhb&m=Index&a=index");
                }
                $this->ajaxReturn(
                    array(
                        'success' => 1, 
                        'redirectUrl' => "index.php?g=Yhb&m=Index&a=index"));
            }
            exit();
            // }
        } else {
            if ($autologin_flag)
                redirect(U('Yhb/Login/showLogin'));
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
        } else {
            $this->error("登录失败:{$reqResult}！");
        }
    }

    /**
     * 显示登陆页面
     */
    public function showLogin() {
        $fromurl = trim(I('get.fromurl', '', 'urldecode'));
        if ($fromurl) {
            session('fromurl', $fromurl);
        }
        $lname = trim(I('get.landname', ''));
        if ($lname != "") {
            $this->assign("landname", $lname);
        }
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
    
    // 重定义错误输出
    protected function error($message, $jumpUrl = null, $ajax = false) {
        if ($jumpUrl && ! is_array($jumpUrl)) {
            $jumpUrl = array(
                '现在跳转' => $jumpUrl);
        }
        if (is_null($jumpUrl)) {
            $jumpUrl = array(
                '返回' => 'javascript:history.go(-1)');
        }
        $this->assign('jumpUrlList', $jumpUrl, $ajax);
        M()->rollback(); // 回滚事务
        parent::error($message, $jumpUrl, $ajax);
    }
    
    // 重定义正确输出
    protected function success($message, $jumpUrl = null, $ajax = false) {
        if ($jumpUrl && ! is_array($jumpUrl)) {
            $jumpUrl = array(
                '现在跳转' => $jumpUrl);
        }
        if (is_null($jumpUrl)) {
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
