<?php

/**
 * zhengxd 2014-12-05
 */
class WangbiqiaAction extends BaseAction {

    const POS_ID = "0001020045";

    public $expiresTime = 600;

    public function _initialize() {
        parent::_initialize();
    }

    public function beforeCheckAuth() {
        $this->_authAccessMap = '*';
    }

    /**
     * @验证辅助码
     */
    public function examinegoods() {
        $data = I('post.');
        
        // $phoneCheckCode = session('groupCheckCode');
        
        /**
         * if($phoneCheckCode['number'] != $data['verify'] ||
         * $phoneCheckCode['phoneNo'] != $data['phoneNo'])
         * exit(json_encode(array( 'code'=>'1', 'code_text'=>'验证码错误' )) );
         * if(time()-$phoneCheckCode['add_time'] > $this->expiresTime)
         * exit(json_encode(array( 'code'=>'1', 'code_text'=>'验证码过期了' )) );
         */
        
        if (session('verify') != md5($data['verify'])) {
            exit(
                json_encode(
                    array(
                        'code' => '1', 
                        'code_text' => '验证码错误')));
        }
        
        if (strlen($data['wangmas']) != '16') {
            exit(
                json_encode(
                    array(
                        'code' => '2', 
                        'code_text' => '输入的旺码不合规则')));
        }
        
        $service = D('Pos', 'Service');
        $posSeq = date('md') . rand(10000000, 99999999);
        $pos_ids = self::POS_ID;
        
        $outacom = $service->sendCodeToVerify($data['wangmas'], $pos_ids, 
            $posSeq, $this->user_id);
        
        if ($outacom !== true) {
            $arr = $this->errInfo;
            if ($arr['code'] == '3038') {
                exit(
                    json_encode(
                        array(
                            'code' => '3', 
                            'code_text' => "该旺码已使用！")));
            } else if ($arr['code'] == '2030') {
                exit(
                    json_encode(
                        array(
                            'code' => '3', 
                            'code_text' => "旺码错误，请核对后重新输入！")));
            } else {
                exit(
                    json_encode(
                        array(
                            'code' => '3', 
                            'code_text' => "验证失败！")));
            }
        } else if ($outacom === true) {
            if ($service->respInfo['result']['id'] == '0000') {
                $number = $service->respInfo['addition_info']['tx_amt'];
                $result = $this->Recharge($number);
                if ($result['Status']['StatusCode'] == '0000') {
                    exit(
                        json_encode(
                            array(
                                'code' => '0', 
                                'code_text' => '验证成功，请前往帐户中心查看旺币金额 ！！！')));
                } else {
                    $posSeq_off = date('md') . rand(10000000, 99999999);
                    $finally = $service->sendCodeToCancel($data['wangmas'], 
                        $pos_ids, $posSeq_off, $posSeq, $this->user_id);
                    if ($finally === true) {
                        exit(
                            json_encode(
                                array(
                                    'code' => '4', 
                                    'code_text' => '验证失败，请在良好的网络环境下重新尝试！！！')));
                    } else {
                        exit(
                            json_encode(
                                array(
                                    'code' => '5', 
                                    'code_text' => '验证失败，如果请联系客服:4008827770！！！')));
                    }
                }
            }
        }
    }

    /**
     * @充值旺币 @ param $num 充值金额
     */
    public function Recharge($num) {
        $service = D('RemoteRequest', 'Service');
        
        $TransactionID = date('ymdHis') . mt_rand(1000, 9999);
        
        $SystemID = C('YZ_SYSTEM_ID');
        
        $BeginTime = date('Ymd');
        
        $EndTime = date("Ymd", strtotime("+1 year"));
        
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        
        $data = array(
            'SetWbReq' => array(
                'SystemID' => $SystemID, 
                'TransactionID' => $TransactionID, 
                'ContractID' => $nodeInfo['contract_no'], 
                'WbType' => '1', 
                'BeginTime' => $BeginTime, 
                'EndTime' => $EndTime, 
                'ReasonID' => 23, 
                'Amount' => 0, 
                'WbNumber' => $num, 
                'Remark' => '使用旺币卡充值旺币！！！'));
        
        $yzResult = $service->requestYzServ($data);
        
        return $yzResult;
    }

    /**
     * @测试旺币充值功能
     */
    public function test() {
        $result = $this->Recharge(5001);
        if ($result['Status']['StatusCode'] == '0000') {
            echo '测试成功';
        }
        dump($result);
    }

    /**
     * @手机发送动态密码
     */
    public function sendCheckCode() {
        /*
         * //图片校验码 $verify = I('post.verify',null,'mysql_real_escape_string');
         * if(session('verify') != md5($verify)) { $this->error("图片动态密码错误"); }
         */
        $phoneNo = I('post.phone', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            $this->error("手机号{$error}");
        }
        // 发送频率验证
        $groupCheckCode = session('groupCheckCode');
        if (! empty($groupCheckCode) &&
             (time() - $groupCheckCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->expiresTime / 60;
        $text = "您的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
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
        // dump($resp_array);exit;
        $ret_msg = $resp_array['NotifyRes']['Status'];
        if (! $resp_array || ($ret_msg['StatusCode'] != '0000' &&
             $ret_msg['StatusCode'] != '0001')) {
            $this->error('发送失败' . print_r($resp_array, true) . '0');
        }
        $groupCheckCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('groupCheckCode', $groupCheckCode);
        $this->success('动态密码已发送');
    }
}