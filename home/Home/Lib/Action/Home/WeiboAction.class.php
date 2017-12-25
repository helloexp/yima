<?php

class WeiboAction extends Action {

    const REG_FROM_SINA = 1;

    const NODE_TYPE_THIRD = 5;

    public function intro() {
        $this->display();
    }

    public function help() {
        $this->display();
    }

    public function help_submit() {
        $name = I("name");
        $phone = I("phone");
        $email = I("email");
        $client_id = I("client_id");
        $company_name = I("company_name");
        $office = I("office");
        $province = I("province");
        $city = I("city");
        $town = I("town");
        $valicode = I("valicode");
        
        if (md5($valicode) != session("weiboverify")) {
            $this->error("验证码错误！");
        }
        // 查询区域
        $city_info = M('tcity_code')->where(
            array(
                'province_code' => $province, 
                'city_code' => $city, 
                'town_code' => $town))->find();
        
        $citystr = "省份：" . $city_info['province'] . ",地市：" . $city_info['city'] .
             ",区域：" . $city_info['town'];
        
        $content = "旺号：{$client_id}<br>真实姓名：{$name}<br/>手机号码：{$phone}<br/>邮箱：{$email}<br/>公司名称：{$company_name}<br/>职位：{$office}<br/>省市区：{$citystr}";
        $ps = array(
            "subject" => "自媒体高级权限申请-商户留言", 
            "content" => $content, 
            "email" => "yangyang@imageco.com.cn");
        $resp = send_mail($ps);
        if ($resp['sucess'] == '1') {
            $this->success("亲，我们已收到您的申请请求，旺小二会在2个工作日内通过邮件告知您相关审核结果！！");
        } else {
            $this->error("高级权限申请失败，邮件发送失败！");
        }
    }

    /**
     * 授权开始
     */
    public function authorize() {
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance("SINA3");
        
        // 跳转到授权页面
        redirect($sns->getRequestCodeURL());
    }

    public function sns_Callback($type = null, $code = null) {
        
        // 加载ThinkOauth类并实例化一个对象
        import("ORG.ThinkSDK.ThinkOauth");
        $sns = ThinkOauth::getInstance($type);
        $extend = null;
        $token = $sns->getAccessToken($code, $extend);
        // $token = session('weibo_token');
        // dump($token);exit;
        if (! empty($token)) {
            
            // token记录SESSION
            session('weibo_token', $token);
            
            // 再根据uid查找用户信息
            $userdata = array();
            $userdata['uid'] = $token['openid'];
            
            $sinaUserInfo = $sns->call('users/show', $userdata, 'get');
            if ($sinaUserInfo['error_code']) {
                $this->error("登录错误，原因：" . $sinaUserInfo['error']);
                return;
            }
            
            // 判断加V和粉丝数量
            if ($userinfo['verified'] == false) {
                $this->error(
                    "亲！自媒体人使用旺财需要您的微博账户完成新浪认证，赶紧到新浪微博认证吧！认证地址http://verified.weibo.com/ 如有疑问，请致电400-882-7770");
            }
            if ($userinfo['friends_count'] < 2000) {
                $this->error(
                    "亲！自媒体人使用旺财需要您的新浪微博账户至少拥有2000粉丝，赶紧到新浪微博去加粉吧！如有疑问，请致电400-882-7770");
            }
            
            // 判断用户是否存在，存在则更新，不存在去注册
            $account_info = M('tnode_info')->where(
                array(
                    'third_uid' => $token['openid'], 
                    'reg_from' => self::REG_FROM_SINA))->find();
            // print_r($account_info);
            
            $nowtime = date('YmdHis');
            
            // 已经存在，无需注册
            if ($account_info) {
                Log::write("已存在微博用户无需注册，" . $account_info['third_uid']);
                $data = array(
                    'third_token' => $token['access_token'], 
                    'update_time' => $nowtime);
                $result = M('tnode_info')->where(
                    "node_id='" . $account_info['node_id'] . "'")->save($data);
                
                // 直接登录
            } else {
                // 注册
                $req = array(
                    "uid" => $token['openid'], 
                    "node_name" => "新浪微博_" . $token['openid'], 
                    "name" => $sinaUserInfo['screen_name'], 
                    "token" => $token['access_token']);
                $res = $this->weibo_user_reg($req);
                if (! $res || $res['status'] != '1') {
                    $this->error("注册失败，原因：" . $res['info']);
                }
                $account_info = M('tnode_info')->where(
                    array(
                        'third_uid' => $token['openid'], 
                        'reg_from' => self::REG_FROM_SINA))->find();
            }
            
            // 查询用户信息
            $wcUserInfo = M('tuser_info')->where(
                array(
                    'node_id' => $account_info['node_id']))->find();
            if (! $wcUserInfo) {
                $this->error("登录失败，用户状态异常。");
            }
            
            if (! $wcUserInfo['first_time']) {
                $wcUserInfo['first_time'] = date('YmdHis');
            }
            
            if ($account_info['check_status'] == 0) {
                Log::write("已存在微博用户，需要认证为C1，" . $account_info['third_uid']);
                $req = array(
                    "uid" => $token['openid'], 
                    "token" => $token['access_token'], 
                    'first_time' => $wcUserInfo['first_time']);
                $this->check_cert($req);
            }
            
            // 登录Session
            $userSess = D('UserSess', 'Service');
            $req = array(
                'user_id' => $wcUserInfo['user_id'], 
                'node_id' => $wcUserInfo['node_id'], 
                'user_name' => $sinaUserInfo['screen_name'], 
                'name' => $sinaUserInfo['name'], 
                'third_token' => $token['access_token'], 
                'third_uid' => $token['openid'], 
                'reg_from' => self::REG_FROM_SINA);
            $result = $userSess->thirdLogin($req);
            
            if ($result) {
                if ($wcUserInfo['login_time'] == "") {
                    header(
                        "location:" .
                             U('Home/Firstlogin/index', '', '', '', true));
                    exit();
                } else {
                    header(
                        "location:" . U('Home/Index/index', '', '', '', true));
                    exit();
                }
            }
        } else {
            
            $this->error("授权失败！");
        }
    }
    
    // 用户注册，区别用户
    protected function weibo_user_reg($data) {
        $userservice = D('NodeReg', 'Service');
        $data['node_name'] = $data['node_name'];
        $data['node_short_name'] = $data['node_name'];
        $data['regemail'] = $data['uid'] . "@7005.com.cn";
        $data['contact_name'] = $data['node_name'];
        $data['contact_phone'] = "13800000000";
        $data['province_code'] = "09";
        $data['city_code'] = "021";
        $data['client_manager'] = "";
        $data['third_uid'] = $data['uid'];
        $data['third_token'] = $data['token'];
        $data['reg_from'] = self::REG_FROM_SINA;
        
        $result = $userservice->nodeAdd($data);
        if (! $result || $result['status'] != '1') {
            return array(
                'status' => $result['status'], 
                'info' => $result['info']);
        }
        Log::write("微博用户注册，" . print_r($result, true));
        
        return array(
            'status' => $result['status'], 
            'info' => $result['info']);
    }

    public function check_cert($data) {
        
        // 查询client_id
        $node_info = M('tnode_info')->where(
            array(
                'third_uid' => $data['uid'], 
                'reg_from' => self::REG_FROM_SINA))->find();
        Log::write("查询入库的机构信息，" . M('tnode_info')->getLastSql());
        
        if ($node_info['client_id'] != "") {
            // 查询首次登录时间
            $first_time = $data['first_time'];
            
            $RemoteRequest = D('RemoteRequest', 'Service');
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
            
            $certtime = date('YmdHis');
            // 请求审核商户改为c1
            $req_array = array(
                'CertifSetReq' => array(
                    'TransactionID' => $TransactionID, 
                    'SystemID' => C('YZ_SYSTEM_ID'), 
                    'ClientID' => $node_info['client_id'], 
                    'CertifDate' => $certtime, 
                    'FirstLoginWCTime' => $first_time));
            $certres = $RemoteRequest->requestYzServ($req_array);
            Log::write("请求认证接口返回，" . print_r($certres, true));
            if ($certres['Status']['StatusCode'] == '0000') {
                // 更新tnode_info
                
                $nodeArr = array(
                    "yzxt_node_check_time" => $certtime, 
                    "check_status" => 2, 
                    "node_check_time" => $certtime, 
                    "node_type" => self::NODE_TYPE_THIRD);
                // "sina_uid"=>$data['uid'],
                // "sina_token"=>$data['token']
                
                $result = M('tnode_info')->where(
                    array(
                        'node_id' => $node_info['node_id']))->save($nodeArr);
                
                Log::write("微博登陆更新nodesql，" . M('tnode_info')->getLastSql());
            }
        }
    }
}