<?php

/* 机构自注册 */
class NodeRegService {
    // 请求支撑验证
    public $opt = array();

    public function __construct() {
    }

    /* 设置参数 */
    public function setopt() {
    }
    
    public $regTryTimes = 0;

    /* 发往营账接口 */
    public function ssoRegister($data) {
        $url = C('SSO_NODE_REG_URL') or die('[SSO_NODE_REG_URL]参数未设置');
        $mac_key = C('SSO_SYSID') or die('[SSO_SYSID]参数未设置');
        $timeout = C('YZ_REQ_TIME') or $timeout = 50; // 超时秒数
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($data, 'gbk');
        log_write('请求注册URL' . $url);
        log_write('请求注册参数' . iconv('”GB2312', 'UTF-8', $str));
        $error = '';
        $result_xml = httpPost($url, urlencode($str), $error, 
            array(
                'TIMEOUT' => $timeout));
        if (! $result_xml) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => 'XML is null'));
        }
        $xml = new Xml();
        $result_xml = get_magic_quotes_gpc() ? stripslashes($result_xml) : $result_xml;
        
        $arr = $xml->parse($result_xml);
        $arr = $xml->getArrayNoRoot();
        // 转换成 utf-8 编码
        array_walk_recursive($arr, 'utf8Str');
        if ($xml->error()) {
            return array(
                'Status' => array(
                    'StatusCode' => '-1', 
                    'StatusText' => $xml->error()));
        }
        return $arr;
    }

    /* 机构添加（注册） */
    public function nodeAdd($data) {
        $node_name = $data['node_name'];
        $node_short_name = $data['node_short_name'];
        $regemail = $data['regemail'];
        $contact_name = $data['contact_name'];
        $contact_phone = $data['contact_phone'];
        $user_password = $data['user_password']; // 用户密码
                                                 // $industry
                                                 // =$data['industry'];
        $province_code = $data['province_code'];
        $city_code = $data['city_code'];
        $town_code = $data['town_code'];
        $client_manager = $data['client_manager'];
        $tg_id = $data['tg_id'];
        $third_uid = $data['third_uid']; // 第三方用户ID
        $third_token = $data['third_token']; // 第三方token
        $reg_from = $data['reg_from']; // 注册来源 0，本地 1：sina新浪微博,6 大转盘手机端注册,7手机APP
        $channel_id = $data['channel_id'];
        // 新增，用于兼容旧的，此为真实的用户名
        $user_name = ! empty($data['user_name']) ? $data['user_name'] : $regemail;
        
        if ($regemail == "") {
            return $this->returnError("用户名不能为空！");
        }
        if ($node_name == "") {
            return $this->returnError("企业名称不能为空！");
        }
        if ($node_short_name == "") {
            return $this->returnError("企业简称不能为空！");
        }
        if ($contact_name == "") {
            return $this->returnError("业务负责人姓名不能为空！");
        }
        if ($contact_phone == "") {
            return $this->returnError("业务负责人手机不能为空！");
        }
        if ($province_code == "") {
            return $this->returnError("所在城市省份不能为空！");
        }
        if ($city_code == "") {
            return $this->returnError("所在城市不能为空！");
        }
        
        if ($client_manager != "" && mb_strlen($client_manager, 'utf-8') > 12) {
            return $this->returnError("客户经理编号不能超过12位");
        }
        // 校验手机
        if (! preg_match("/^13[0-9]{9}$|15[0-9]{9}$|18[0-9]{9}$|17[0-9]{9}$/", 
            $data['contact_phone'], $matchs)) {
            return $this->returnError("业务负责人手机号码错误！");
        }

        $registerLaw = session('register_law');
        if ($registerLaw) {
            $lawid = $registerLaw['lawid'];
            $lawlicense = $registerLaw['lawlicense'];
        } else {
            // 企业法人证件号
            $lawid = date('ymd') . substr(time(), - 1) . substr(microtime(), 3, 5);
            // 营业执照号
            $lawlicense = date('ymd') . substr(time(), - 1) .
            substr(microtime(), 3, 5);
        }
        
        $lawname = "法人";
        $nodeaddr = "上海市浦东新区";
        
        $req_array = array(
            'RegisterReq' => array(
                'AppId' => C('SSO_SYSID'), 
                'NodeInfo' => array(
                    'NodeName' => $node_name, 
                    'NodeShortName' => $node_short_name, 
                    'BusinessEntityName' => $lawname, 
                    'BusinessEntityCard' => $lawid, 
                    'BusinessLicenseCard' => $lawlicense, 
                    'BusinessLicenseImag' => '', 
                    'ProvinceCode' => $province_code, 
                    'CityCode' => $city_code, 
                    'TownCode' => $town_code, 
                    'IndustryID' => '30', 
                    'PosBasicPrice' => ',0,188', 
                    'PayType' => '2', 
                    'EmplId' => $client_manager, 
                    'UserName' => $user_name, 
                    'SecPassword' => $user_password), 
                'ContactInfo' => array(
                    'ContactName' => $contact_name, 
                    'ContactPhone' => $contact_phone, 
                    'ContactEmail' => $regemail, 
                    'ContactAddress' => $nodeaddr),
                'BatchFlag' => '1',//不发邮件
            )
        );
        
        log_write(
            "用户注册信息：机构名：" . $node_name . ",企业简称：" . $node_short_name . ",邮箱：" .
                 $regemail . ",联系人：" . $contact_name . ",联系手机：" . $contact_phone .
                 ",客户经理编号" . $client_manager);
        //先存session,接口需要两次完全一样的报文，这两个值是按时间生成的，记录下来
        session('register_law', array('lawid' => $lawid, 'lawlicense' => $lawlicense));
                 
        $RemoteRequest = D('NodeReg', 'Service');
        $beforeRegister = time();
        log_write('register-info' . json_encode($req_array));
        $result = $RemoteRequest->ssoRegister($req_array);
        $afterRegister = time();
        //注册需要的时间，如果小于30秒
        $regNeedTime = $afterRegister - $beforeRegister;
        if ($regNeedTime <= 30) {
            session('register_law', null);
        } else {
            if ($this->regTryTimes == 0) {
                //增加重试次数，只重试一次
                $this->regTryTimes = 1;
                $result = $this->nodeAdd($data);
                return $result;
            }
            exit();
        }
        if ($result['Status']['StatusCode'] == '0000') {
            $tnode = M('tnode_info');
            $citystr = $province_code . $city_code . $town_code;
            $user_id = $result['Info']['UserId'];
            $dataArr = array(
                "node_id" => $result['Info']['NodeId'], 
                "client_id" => $result['Info']['ClientID'], 
                "node_name" => $node_name, 
                "node_short_name" => $node_short_name, 
                "node_license_id" => $lawlicense, 
                "node_law_name" => $lawname, 
                "node_law_id" => $lawid, 
                "applyfee_id" => '188', 
                "trade_type" => '30', 
                "node_type" => '2', 
                "node_citycode" => $citystr, 
                "node_addr" => $nodeaddr, 
                "contact_name" => $contact_name, 
                "contact_phone" => $contact_phone, 
                "contact_eml" => $regemail, 
                "full_id" => $result['Info']['NodeId'], 
                "status" => '0', 
                "sign_time" => date('YmdHis'), 
                "add_time" => date('YmdHis'), 
                "node_manager" => $client_manager, 
                "third_uid" => $third_uid, 
                "third_token" => $third_token, 
                "reg_from" => $reg_from, 
                "channel_id" => $channel_id, 
                "tg_id" => $tg_id);
            $tnode->where(['node_id'=>$result['Info']['NodeId']])->delete();
            $nodeok = $tnode->add($dataArr);
            log_write('注册失败原因：'.M()->_sql().'->'.M()->getDbError());
            if ($nodeok) {
                M()->startTrans(); // 开启事物
                // 插入seq
                $qwhere = "client_" . $result['Info']['ClientID'];
                // 判断seq是否已经存在
                $seqcount = M('tsequence')->where("name='" . $qwhere . "'")->count();
                if ($seqcount == 0) {
                    $seqArr = array(
                        "name" => $qwhere, 
                        "current_value" => 0, 
                        "_increment" => 1);
                    $tsequence = M('tsequence');
                    $seqok = $tsequence->add($seqArr);
                    // echo M('tsequence')->getLastSql();
                    if (! $seqok) {
                        M()->rollback();
                        return $this->returnError("注册失败,插入本地数据库Seq失败！");
                    }
                }
                
                // 插入用户
                $tuser = M('tuser_info');
                $userArr = array(
                    "user_id" => $result['Info']['UserId'], 
                    "role_id" => '2', 
                    "new_role_id" => '2', 
                    "node_id" => $result['Info']['NodeId'], 
                    "user_name" => $user_name, 
                    "true_name" => $contact_name, 
                    "status" => '0', 
                    "add_time" => date('YmdHis'));
                $userok = $tuser->add($userArr);
                
                if (! $userok) {
                    M()->rollback();
                    return $this->returnError("注册失败,插入本地用户失败！");
                }
//#16694 去掉 插入两周以内的公告的操作 start
                $msgStatId = M('tmessage_stat')->add(
                    array(
                        'node_id' => $result['Info']['NodeId'], 
                        'message_type' => 1, 
                        'total_cnt' => 0, 
                        'new_message_cnt' => 0, 
                        'last_time' => date('YmdHis')));
//#16694 去掉 插入两周以内的公告的操作 end
                if (! $msgStatId) {
                    M()->rollback();
                    return $this->returnError("注册失败，插入商户消息统计表失败！");
                }
            } else {
                //M()->rollback();
                return $this->returnError("注册失败,插入本地数据库机构失败！");
            }
            
            if ($userok) {
                // 插入在线活动版块渠道
                $channelm = M('tchannel');
                $channelArr = array(
                    'name' => 'O2O活动', 
                    'type' => '1', 
                    'sns_type' => '12', 
                    'status' => '1', 
                    'node_id' => $result['Info']['NodeId'], 
                    'add_time' => date('YmdHis'));
                $cquery = $channelm->add($channelArr);
                if (! $cquery) {
                    M()->rollback();
                    $this->returnError("注册失败,插入本地数据库在线展示渠道失败！");
                }
                M()->commit();
                
                // 查询结算号
                $trans_seq = date('YmdHis') . rand(10000, 99999);
                $req_array = array(
                    'QueryShopContractReq' => array(
                        'TransactionID' => $trans_seq, 
                        'SystemID' => C('YZ_SYSTEM_ID'), 
                        'NodeID' => $result['Info']['NodeId']));
                log_write("查询结算号=" . $result['Info']['NodeId']);
                $RemoteRequest = D('RemoteRequest', 'Service');
                $qryResult = $RemoteRequest->requestYzServ($req_array);
                log_write("查询结算号" . print_r($qryResult, true));
                if ($qryResult['Status']['StatusCode'] == '0000') {
                    $condata = array(
                        'contract_no' => $qryResult['ContractID'], 
                        'update_time' => date('YmdHis'));
                    $conresult = M('tnode_info')->where(
                        "node_id='" . $qryResult['NodeID'] . "'")->save($condata);
                    if (! $conresult) {
                        log_write('更新结算号数据库错误！');
                    }
                } else {
                    log_write("机构" . $qryResult['NodeID'] . "查询结算号失败");
                }
                
                // 添加用户id
                $dataArr['user_id'] = $user_id;
                return $this->returnSuccess("注册成功", $dataArr);
            }
        } else {
            if ($result['Status']['StatusCode'] == '7013') {
                return $this->returnError('注册中，请稍后……');
            }
            
            if ($result['Status']['StatusCode'] == '7008' ||
                 $result['Status']['StatusCode'] == '7009' ||
                 $result['Status']['StatusCode'] == '7006') {
                return $this->returnError(
                    $node_name . "(企业名称)已在旺财平台注册,请拨打400-882-7770联系客服取回账号, (错误代码:" .
                     $result['Status']['StatusCode'] . ")");
            } else {
                return $this->returnError(
                    "注册失败，返回码：" . $result['Status']['StatusCode'] . ",错误信息：" .
                         $result['Status']['StatusText']);
            }
        }
    }
    
    // 返回失败信息
    public function returnError($message) {
        return array(
            'info' => $message, 
            'status' => 0);
    }
    
    // 返回成功信息
    public function returnSuccess($message, $data = null) {
        return array(
            'data' => $data, 
            'info' => $message, 
            'status' => 1);
    }

    /**
     * 旺财注册(给旺财注册接口用)
     *
     * @param array $data 1 sid session_id 2 check_code 校验码 3 wc_acount 旺财帐号 4
     *            wc_passwd 密码 5 wc_enterprise_name 企业名称 6 wc_acount_name 姓名 7
     *            wc_acount_phone 手机号码 8 province_code 省编码 9 city_code 城市编码
     */
    public function wcReg($data) {
        if ($data['session_id'] != session('sid')) {
            return array(
                'resp_id' => '1001', 
                'resp_desc' => 'session失效');
        }
        if ($data['check_code'] == '' ||
             session('verify_imgcode') != md5($data['check_code'])) {
            return array(
                'resp_id' => '1002', 
                'resp_desc' => '验证码错误');
        } else {
            session('verify_imgcode', null);
        }
        $readyData = array();
        $readyData['regemail'] = $data['wc_acount'] ? $data['wc_acount'] : '';
        $readyData['user_password'] = $data['wc_passwd'] ? md5(
            $data['wc_passwd']) : '';
        $readyData['node_name'] = $data['wc_enterprise_name'] ? $data['wc_enterprise_name'] : '';
        $readyData['node_short_name'] = $data['wc_enterprise_name'] ? mb_substr(
            $data['wc_enterprise_name'], 0, 9, 'utf-8') : ''; // 机构简称
        $readyData['contact_name'] = $data['wc_acount_name'] ? $data['wc_acount_name'] : '';
        $readyData['contact_phone'] = $data['wc_acount_phone'] ? $data['wc_acount_phone'] : '';
        $readyData['province_code'] = $data['province_code'] ? $data['province_code'] : '';
        $readyData['city_code'] = $data['city_code'] ? $data['city_code'] : '';
        $readyData['town_code'] = $data['town_code'] ? $data['town_code'] : '';
        $readyData['reg_from'] = $data['reg_from'] ? $data['reg_from'] : '';
        $res = $this->nodeAdd($readyData);
        if ($res['status'] != 1) {
            return array(
                'resp_id' => '1003', 
                'resp_desc' => $res['info']);
        } else {
            return array(
                'resp_id' => '0000', 
                'resp_desc' => $res['info']);
        }
    }
}