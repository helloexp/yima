<?php
// GroupBuyAction => sendCheckCode()短信发送验证码
class WapAction extends Action {
    // 跳转回来地址
    const __TRUE_BACK_URL__ = '__TRUE_BACK_URL__';
    // 微信用户id
    public $expiresTime = 50;
    // 手机发送间隔
    public $CodeexpiresTime = 60;
    // 手机验证码过期时间
    public $openId = "";
    // 微信openId
    public $js_global = array();

    public $wap_sess_name = '';

    public function _initialize() {
        if (I('_sid_', '') == 'w') {
            $_SERVER['HTTP_USER_AGENT'] = 'MicroMessenger';
        }
        
        // 判断是否是微信过来的
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) {
            echo "请从微信访问";
            exit();
        }
        
        if (ACTION_NAME == 'scan') {
            $this->_cw_checklogin(false);
        }
    }

    public function _cw_checklogin($return = true) {
        if (session('?' . $this->wap_sess_name) && $return) {
            return true;
        }
        $login = false;
        $userid = '';
        $backurl = U('', I('get.'), '', '', true);
        $backurl = urlencode($backurl);
        // 微信授权,取全局的微信服务号
        $login = session('?node_wxid_chaowai');
        
        $jumpurl = U('Chaowai/CwWeixinLoginNode/index', 
            array(
                'type' => 1, 
                'backurl' => $backurl));
        if ($login)
            $info = session('node_wxid_chaowai');
        if (! $login) {
            redirect($jumpurl);
        }
        session($this->wap_sess_name, $userid);
        return $login;
    }
    // 用户绑定手机界面
    public function bindIndex() {
        $key = I('key', null);
        
        $this->assign("key", $key);
        $this->display();
    }
    
    // 用户身份绑定确认
    public function idenBind() {
        $key = I('post.key', null);
        $sArr = session('node_wxid_chaowai');
        $openid = $sArr['openid'];
        $phoneNo = I('post.phone', null, 'mysql_real_escape_string');
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            exit(
                json_encode(
                    array(
                        'info' => "手机号{$error}", 
                        'status' => 0)));
        }
        
        // 手机验证码
        $checkCode = I('post.check_code', null);
        if (! check_str($checkCode, array(
            'null' => false), $error)) {
            exit(
                json_encode(
                    array(
                        'info' => "验证码{$error}", 
                        'status' => 0)));
        }
        $groupChaoWaiCode = session('groupChaoWaiCode');
        if (! empty($groupChaoWaiCode) &&
             $groupChaoWaiCode['phoneNo'] != $phoneNo) {
            exit(
                json_encode(
                    array(
                        'info' => "手机号码不正确", 
                        'status' => 0)));
        }
        if (! empty($groupChaoWaiCode) &&
             $groupChaoWaiCode['number'] != $checkCode) {
            exit(
                json_encode(
                    array(
                        'info' => "验证码错误", 
                        'status' => 0)));
        }
        if (time() - $groupChaoWaiCode['add_time'] > $this->CodeexpiresTime) {
            exit(
                json_encode(
                    array(
                        'info' => "验证码已经过期", 
                        'status' => 0)));
        }
        
        $data = array(
            'open_id' => $openid, 
            'mobile' => $phoneNo, 
            'add_time' => date('YmdHis'));
        $result = M("tfb_movingcar_customers")->add($data);
        if (! $result) {
            exit(
                json_encode(
                    array(
                        'info' => "绑定失败！", 
                        'status' => 0)));
        }
        
        if ($key == 1) {
            exit(
                json_encode(
                    array(
                        'data' => "/index.php?g=Chaowai&m=Wap&a=comCard", 
                        'info' => "恭喜您，绑定成功！", 
                        'status' => 1)));
        } else {
            exit(
                json_encode(
                    array(
                        'data' => "/index.php?g=Chaowai&m=Wap&a=scan&key={$key}", 
                        'info' => "恭喜您，绑定成功！", 
                        'status' => 1)));
        }
    }
    
    // 手机发送验证码
    public function sendIdentifCode() {
        $phoneNo = I('bind_phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}" . $phoneNo);
        }
        // 测试环境不下发，验证码直接为1111
        // if (!is_production()) {
        // $groupChaoWaiCode = array('number' => 1111, 'add_time' => time(),
        // 'phoneNo' => $phoneNo);
        // session('groupChaoWaiCode', $groupChaoWaiCode);
        // $this->ajaxReturn("success", "验证码已发送", 1);
        // }
        // 发送频率验证
        $groupChaoWaiCode = session('groupChaoWaiCode');
        if (! empty($groupChaoWaiCode) &&
             (time() - $groupChaoWaiCode['add_time']) < $this->expiresTime) {
            exit(
                json_encode(
                    array(
                        'info' => "动态密码发送过于频繁!", 
                        'status' => 0)));
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->CodeexpiresTime;
        $text = "您在{$this->node_short_name}商户的动态密码是：{$num}，有效期{$exptime}秒。如非本人操作请忽略。";
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
            exit(
                json_encode(
                    array(
                        'info' => "发送失败", 
                        'status' => 0)));
        }
        $groupChaoWaiCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('groupChaoWaiCode', $groupChaoWaiCode);
        exit(
            json_encode(
                array(
                    'info' => "动态密码已发送", 
                    'status' => 1)));
    }
    
    // 扫描二维码展示车辆信息
    public function scan() {
        $this->_cw_checklogin();
        $key = I("key", "");
        $sArr = session('node_wxid_chaowai');
        
        $bindFlag = M("tfb_movingcar_customers")->where(
            array(
                'open_id' => $sArr['openid']))->count();
        if ($bindFlag < 1) {
            redirect("index.php?g=Chaowai&m=Wap&a=bindIndex&key={$key}");
        }
        
        $phoneN = M("tfb_movingcar_customers")->field("id, mobile")
            ->where(array(
            'open_id' => $sArr['openid']))
            ->find();
        
        $idenMsg = M("tfb_movingcar_carinfo")->where(
            array(
                'key' => $key))->find();
        if (! $idenMsg) {
            redirect("index.php?g=Chaowai&m=Wap&a=scanErr");
        }
        
        // 手机号中间4位星号显示
        $cMobile = substr_replace($idenMsg['mobile'], '****', 3, 4);
        
        $this->assign("userMsg", $idenMsg);
        $this->assign("cus_id", $phoneN['id']);
        $this->assign("mobile", $phoneN['mobile']);
        $this->assign("car_mobile", $cMobile);
        $this->display();
    }

    public function scanErr() {
        $this->display();
    }
    
    // 向被申请人发送通知短信
    // 被申请人手机号：carMobile 申请人手机号：curMobile 挪车原因：desc
    public function sendNotice($carMobile, $curMobile, $desc) {
        $text = "手机号：{$curMobile}向您发送挪车请求，挪车原因是：{$desc}。";
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
                    'Number' => $carMobile),  // 手机号
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
            return false;
        }
        return true;
    }
    
    // 申请发送挪车短信到用户手机
    public function scan_handle() {
        $carid = I("carinfo_id", "");
        $carmobile = I("carinfo_mobile", "");
        $mcReason = I("mc_reason", "");
        $cusid = I("cus_id", "");
        $mobile = I("cus_mobile", "");
        
        if (empty($carid) && empty($mcReason) && empty($cusid) && empty($mobile)) {
            exit(
                json_encode(
                    array(
                        'info' => "网络出错！", 
                        'status' => 0)));
        }
        
        if ($carmobile == $mobile) {
            exit(
                json_encode(
                    array(
                        'info' => "不能给自己的车辆发送挪车申请！", 
                        'status' => 0)));
        }
        
        $resC = M("tfb_movingcar_log")->field("id, notice_status, notice_time")
            ->where(
            array(
                'cusyomer_id' => $cusid, 
                'carinfo_id' => $carid))
            ->order("notice_time desc")
            ->find();
        
        $mCarTime = strtotime($resC['notice_time']);
        if (time() - $mCarTime < 300 && $resC['notice_status'] == 1) {
            exit(
                json_encode(
                    array(
                        'info' => "您已发送挪车请求，请稍后发送~~", 
                        'status' => 0)));
        } else {
            $addTime = date('YmdHis');
            $strTime = strtotime($addTime);
            $data = array(
                'cusyomer_id' => $cusid, 
                'carinfo_id' => $carid, 
                'reason_id' => $mcReason, 
                'notice_status' => 0, 
                'notice_time' => '');
            
            // 判断当前申请人向同一个手机号发送的挪车申请次数
            $noticeNum = M("tfb_movingcar_log")->field("id, notice_time")
                ->where(
                array(
                    'notice_time' => $cusid, 
                    'carinfo_id' => $carid, 
                    'notice_status' => 1, 
                    '_string' => "notice_time like " . date('Ymd') . "%"))
                ->count();
            if ($noticeNum >= 3) {
                exit(
                    json_encode(
                        array(
                            'info' => "您申请该车的挪车请求已超过当前申请总数！", 
                            'status' => 0)));
            }
            
            $res_desc = M("tfb_movingcar_reason")->where(
                array(
                    'id' => $mcReason))->find();
            
            $res = $this->sendNotice($carmobile, $mobile, $res_desc['desc']);
            if ($res) {
                $data['notice_status'] = 1;
            } else {
                $data['notice_status'] = 2;
            }
            // 发送申请短信后的时间
            $data['notice_time'] = date('YmdHis');
            
            $res_log = '';
            $data['add_time'] = $addTime;
            $res_log = M("tfb_movingcar_log")->add($data);
            
            if ($res_log > 0 && $res == true) {
                exit(
                    json_encode(
                        array(
                            'info' => "申请发送成功！", 
                            'status' => 1)));
            } else {
                exit(
                    json_encode(
                        array(
                            'info' => "发送请求失败，请重新发送！", 
                            'status' => 0)));
            }
        }
    }
    
    // 申请社区卡
    public function comCard() {
        $this->_cw_checklogin();
        $sArr = session('node_wxid_chaowai');
        
        $bindFlag = M("tfb_movingcar_customers")->where(
            array(
                'open_id' => $sArr['openid']))->count();
        if ($bindFlag < 1) {
            redirect("index.php?g=Chaowai&m=Wap&a=bindIndex&key=1");
        }
        
        $this->display();
    }
    
    // 申请社区卡提交处理
    public function comCardHandle() {
        $carNumber = I("car_number_name", "");
        $carMobile = I("phone", "");
        $carName = I("full_name", "");
        $radioNum = I("radioNum", "");
        if ($radioNum == "1") {
            $pcode = I("province_code", "");
            $ccode = I("city_code", "");
            $address = I("address", "");
        }
        
        // 判断社区卡申请表中当前申请车牌是否在表中有效存在
        $cardArr = M("tfb_movingcar_card")->field("status")
            ->where(array(
            'plate_number' => $carNumber))
            ->order("add_time desc")
            ->find();
        if ($cardArr != NULL && $cardArr['status'] != 2) {
            exit(json_encode(array(
                'status' => 0)));
        }
        
        // 判断当前申请车牌号是否被企业申请过
        $cardInfoCount = M("tfb_movingcar_carinfo")->where(
            array(
                'plate_number' => $carNumber))->count();
        if ($cardInfoCount > 0) {
            exit(json_encode(array(
                'status' => 4)));
        }
        
        // 判断当前申请手机号在申请表中存在的有效的记录中对应的姓名是否和当前申请姓名相同
        $cardName = M("tfb_movingcar_card")->field("proposer")
            ->where("mobile=" . $carMobile . " and status<>2")
            ->find();
        if ($cardName != NULL && $cardName['proposer'] != $carName) {
            exit(json_encode(array(
                'status' => 3)));
        }
        
        $carInfoName = M("tfb_movingcar_carinfo")->field("driver_name")
            ->where("mobile=" . $carMobile)
            ->find();
        if ($carInfoName != NULL && $carInfoName['driver_name'] != $carName) {
            exit(json_encode(array(
                'status' => 3)));
        }
        
        $dataCard = array(
            'card_attribute' => 1, 
            'proposer' => $carName, 
            'mobile' => $carMobile, 
            'plate_number' => $carNumber, 
            'add_time' => date('YmdHis'), 
            'shipping_method' => $radioNum, 
            'status' => 0);
        
        if ($radioNum == '1') {
            $cityName = M('tcity_code')->field("province, city")
                ->where(
                array(
                    'province_code' => $pcode, 
                    'city_code' => $ccode))
                ->find();
            $dataCard['shipping_address'] = $cityName['province'] . " " .
                 $cityName['city'] . " " . $address;
        }
        $resultCard = M("tfb_movingcar_card")->add($dataCard);
        if (! $resultCard) {
            exit(
                json_encode(
                    array(
                        'info' => "申请失败，请检查网络是否可用！", 
                        'status' => 2)));
        } else {
            exit(json_encode(array(
                'status' => 1)));
        }
    }
}