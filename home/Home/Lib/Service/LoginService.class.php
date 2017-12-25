<?php

/**
 * 登录
 *
 * @author Jeff Liu<liuwy@imageco.com.cn>
 */
class LoginService {

    /**
     * 自动登录 @auto Jeff Liu<liuwy@imageco.com.cn>
     *
     * @param $autologininfo
     * @return array|bool
     */
    public function autoLogin($autologininfo) {
        if (empty($autologininfo)) {
            $autologininfo = I('request.autologininfo', false);
        }
        
        $result = array(
            'status' => 0, 
            'msg' => '', 
            'url' => U('Home/Login/showLogin'), 
            'data' => '');
        $autoLoginInfo = json_decode(base64_decode($autologininfo), true);
        $email = trim($autoLoginInfo['regemail']);
        $password = trim($autoLoginInfo['password']);
        $fromUrl = I('fromurl');
        session('fromurl', $fromUrl);
        
        $result['data'] = array(
            'email' => $email, 
            'password' => $password);
        session('autologininfo', null);
        return $result;
    }

    /**
     * ssologin
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     * @param bool $autologininfo
     *
     * @return bool
     */
    public function ssoLogin($autologininfo = false) {
        if (empty($autologininfo)) {
            $autologininfo = I('request.autologininfo', false);
        }
        if ($autologininfo) {
            $reqInfo = $this->autoLogin($autologininfo);
        } else {
            $reqInfo = $this->normalSsoLogin();
        }
        
        $email = isset($reqInfo['data']['email']) ? $reqInfo['data']['email'] : '';
        
        // 查询机构
        $uInfo = M('tuser_info')->field('node_id,user_id,login_time')
            ->where("user_name='" . $email . "'")
            ->find();
        if (empty($uInfo['user_id'])) {
            $result['msg'] = '登录用户不存在';
            return $result;
        }
        
        // 判断是否是今天第一次登录，用户弹出劳动最光荣提示
        if ($uInfo['login_time'] == '' ||
             substr($uInfo['login_time'], 0, 8) != date('Ymd')) {
            session('today_first_login', true);
        }
        
        $node_id = isset($uInfo['node_id']) ? $uInfo['node_id'] : '';
        $user_id = isset($uInfo['user_id']) ? $uInfo['user_id'] : '';
        
        $password = isset($reqInfo['data']['password']) ? $reqInfo['data']['password'] : '';
        $reqArray = array(
            'email' => $email, 
            'password' => $password, 
            'node_id' => $node_id);
        
        $RemoteRequest = D('UserSess', 'Service');
        $reqResult = $RemoteRequest->ssoLogin($reqArray);
        
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
            if (! $userloginInfo['login_time']) {
                $data['first_time'] = date('YmdHis');
            } elseif ($userloginInfo['login_time'] &&
                 ! $userloginInfo['first_time']) {
                $data['first_time'] = date('YmdHis');
            }
            $result = M('tuser_info')->where(
                "node_id='" . $node_id . "'" . " AND " . "user_id='" . $user_id .
                     "'")->save($data);
            node_log("登录", null, 
                array(
                    'log_type' => 'LOGIN'));
            $qsql = M()->getLastSql();
            log_write(
                "更新用户===node_id=" . $node_id . "==user_id=" . $user_id . "===" .
                     $qsql);
            
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
                    ->where("node_id='" . $node_id . "'")
                    ->find();
                if (empty($nodeInfo['contract_no'])) {
                    // 查询结算号
                    $trans_seq = date('YmdHis') . rand(10000, 99999);
                    $reqInfo = array(
                        'QueryShopContractReq' => array(
                            'TransactionID' => $trans_seq, 
                            'SystemID' => C('YZ_SYSTEM_ID'), 
                            'NodeID' => $nodeInfo['node_id']));
                    log_write("查询结算号=" . $nodeInfo['node_id']);
                    $RemoteRequest = D('RemoteRequest', 'Service');
                    $qryResult = $RemoteRequest->requestYzServ($reqInfo);
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
                        log_write("机构" . $qryResult['NodeID'] . "查询结算号失败");
                    }
                }
            }
            
            $fromUrl = I('fromurl');
            if ($fromUrl != '') {
                session('fromurl', null);
                $result['status'] = 1;
                $result['url'] = htmlspecialchars_decode($fromUrl);
            } else { // 落地页监控登录逻辑
                $landName = trim(I('post.landname'));
                switch ($landName) {
                    case 'wxyx': // 微信营销
                        $result['status'] = 1;
                        $result['url'] = 'index.php?g=Weixin&m=Weixin&a=index';
                        break;
                    case 'weibozhushou': // 新浪微博
                        $result['status'] = 1;
                        $result['url'] = 'index.php?g=LabelAdmin&m=Weibo&a=index';
                        break;
                    case 'zhidahao': // 直达号
                        $result['status'] = 1;
                        $result['url'] = 'index.php?g=LabelAdmin&m=Med&a=index';
                        break;
                    case 'weiguanwang': // 微官网
                        $result['status'] = 1;
                        $result['url'] = 'index.php?g=MicroWeb&m=Index&a=index';
                        break;
                    case 'xuanma': // 炫码
                        $result['status'] = 1;
                        $result['url'] = 'index.php?g=VisualCode&m=Index&a=index';
                        break;
                    case 'zfb': // 支付宝线下收单
                        $result['status'] = 1;
                        $result['url'] = 'index.php?g=Alipay&m=Index&a=index';
                        break;
                    default:
                        $result['status'] = 1;
                        $result['url'] = 'index.php?m=Index&a=index';
                }
            }
        } else {
            $reqResult = str_replace('机构号、', '', $reqResult);
            $result['msg'] = "登录失败:{$reqResult}";
        }
        return $result;
    }

    /**
     * 普通登录(输入用户名密码)
     *
     * @author Jeff Liu<liuwy@imageco.com.cn>
     */
    public function normalSsoLogin() {
        $result = array(
            'status' => 0, 
            'msg' => '', 
            'url' => U('Home/Login/showLogin'), 
            'data' => '');
        $email = trim(I('post.email'));
        $password = trim(I('post.password'));
        $verify = trim(I('post.verify'));
        $rememberMe = trim(I('rememberMe'));
        $fromUrl = session('fromurl');
        if ($rememberMe == 1) {
            setcookie("emailCookie", $email);
        }
        
        if (empty($email)) {
            $result['msg'] = '登录名不能为空';
            return $result;
        }
        if (empty($password)) {
            $result['msg'] = '登录密码不能为空';
            return $result;
        }
        if (isset($_SESSION['verify']) && session('verify') != md5($verify)) {
            $result['msg'] = '验证码错误';
            return $result;
        }
        
        // 获取机构是否重复
        $nodeCount = M('tuser_info')->field('node_id')
            ->where("user_name='" . $email . "'")
            ->count();
        if ($nodeCount > 1) {
            $result['msg'] = '登录失败，用户存在重复';
            return $result;
        }
        
        $reqInfo = array(
            'email' => $email, 
            'password' => $password);
        
        $result['status'] = 1;
        $result['msg'] = 'success';
        $result['data'] = $reqInfo;
        
        return $result;
    }
}