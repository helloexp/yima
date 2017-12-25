<?php

class WfxAction extends Action {

    public $expiresTime;
    // 手机发送间隔
    public $CodeexpiresTime;
    // 手机动态密码过期时间
    public function _initialize() {
        $this->expiresTime = 120; // 手机发送间隔
        $this->CodeexpiresTime = 600; // 手机动态密码过期时间
    }

    function activity() {
        $this->assign('title', '销售员招募');
        $saler = I('get.saler');
        if ($saler) {
            $wfxSalerModel = M('twfx_saler');
            $nodeId = $wfxSalerModel->where(
                array(
                    'id' => $saler))->getField('node_id');
            
            $wfxModel = D('Wfx');
            $countInfo['click_count'] = 1;
            $wfxModel->setRecruitDayStat($nodeId, $saler, $countInfo);
        }
        
        $id = I('id');
        if ($id) {
            $batchChannelInfo = M('tbatch_channel')->where(
                array(
                    'id' => $id))
                ->field('node_id, end_time')
                ->find();
            if ($batchChannelInfo['end_time'] < date('YmdHis')) {
                $this->error('预览时间过期');
                exit();
            } else {
                $nodeId = $batchChannelInfo['node_id'];
                if (empty($saler)) {
                    $wfxModel = D('Wfx');
                    $countInfo['click_count'] = 1;
                    $wfxModel->setRecruitDayStat($nodeId, '0', $countInfo);
                }
            }
            $this->assign('yulan', 'yulan');
        }
        
        if ($saler == '' && $id == '') {
            $this->error('未查到有效信息，请审查来源页面！');
            exit();
        }
        
        $marketingInfoModel = M('tmarketing_info');
        $recruitInfo = $marketingInfoModel->where(
            array(
                'node_id' => $nodeId, 
                'batch_type' => '3001'))
            ->field(
            'name, bg_pic, node_name, log_img, config_data, wap_info, status')
            ->find();
        if ($recruitInfo['status'] == '2') {
            redirect(
                U('Label/MyOrder/index', 
                    array(
                        'node_id' => $nodeId)));
            exit();
        }
        $this->assign('node', $nodeId);
        $recruitInfo['log_img'] = get_upload_url($recruitInfo['log_img']);
        $this->assign('recruitInfo', $recruitInfo);
        $this->assign('info', explode(',', $recruitInfo['config_data']));
        $this->display();
    }

    function recruitInfo() {
        $result = array();
        $saveData = array();
        $agreement = I('post.agreement');
        if ($agreement != '1') {
            $result['error'] = '1004';
            $result['msg'] = '请阅读协议！';
            $this->ajaxReturn($result);
            exit();
        }
        $nodeId = I('post.node');
        $salerId = I('post.saler');
        if ($salerId != 'null') {
            $saveData['referee_id'] = $salerId;
            $salerInfo = M('twfx_saler')->where(
                array(
                    'id' => $salerId))
                ->field('role', 'node_id')
                ->find();
            if ($salerInfo['role'] == '1') {
                $result['error'] = '1004';
                $result['msg'] = '推荐人不是分销商，无法成为其名下的销售员';
                $this->ajaxReturn($result);
                exit();
            }
            $nodeId = $salerInfo['node_id'];
        }
        
        $marketingInfoModel = M('tmarketing_info');
        $recruitInfo = $marketingInfoModel->where(
            array(
                'node_id' => $nodeId, 
                'batch_type' => '3001'))
            ->field('name, bg_pic, node_name, log_img, config_data, wap_info')
            ->find();
        $recruitConfigInfo = explode(',', $recruitInfo['config_data']);
        
        $saveData['node_id'] = $nodeId;
        
        if (in_array(1, $recruitConfigInfo)) {
            if ($_SESSION['groupChangeCode']['number'] != $_POST['code']) {
                $result['error'] = '1001';
                $result['msg'] = '验证码错误';
                $this->ajaxReturn($result);
                exit();
            }
            
            $saveData['phone_no'] = I('post.phone');
            if ($saveData['phone_no'] != $_SESSION['groupChangeCode']['phoneNo']) {
                $result['error'] = '1003';
                $result['msg'] = '验证手机号和提交手机号不一致';
                $this->ajaxReturn($result);
                exit();
            } else if ($salerId != 'null') {
                $_SESSION['groupPhone'] = $saveData['phone_no'];
            }
        }
        
        if (in_array(2, $recruitConfigInfo)) {
            $saveData['name'] = I('post.name', '0', 'string');
            if ($saveData['name'] == '0' || $saveData['name'] == '') {
                $result['error'] = '2003';
                $result['msg'] = '姓名不能为空！';
                $this->ajaxReturn($result);
                exit();
            }
        }
        
        if (in_array(3, $recruitConfigInfo)) {
            $saveData['sex'] = I('post.sex');
            if ($saveData['sex'] == '') {
                $result['error'] = '3003';
                $result['msg'] = '性别不能为空！';
                $this->ajaxReturn($result);
                exit();
            }
        }
        
        if (in_array(4, $recruitConfigInfo)) {
            $birth = I('post.birth');
            $birth = date('YmdHis', strtotime($birth));
            $age = floor((time() - strtotime($birth)) / (60 * 60 * 24 * 365));
            $saveData['birth'] = $birth;
            if ($saveData['birth'] == '') {
                $result['error'] = '3003';
                $result['msg'] = '出生年月不能为空！';
                $this->ajaxReturn($result);
                exit();
            }
        }
        
        if (in_array(5, $recruitConfigInfo)) {
            $saveData['email'] = I('post.email');
            if ($saveData['email'] == '') {
                $result['error'] = '3003';
                $result['msg'] = '邮箱不能为空！';
                $this->ajaxReturn($result);
                exit();
            }
        }
        
        if (in_array(6, $recruitConfigInfo)) {
            $saveData['area'] = $_POST['privince'] . $_POST['city'] .
                 $_POST['town'];
            if ($saveData['area'] == '') {
                $result['error'] = '3003';
                $result['msg'] = '地址不能为空！';
                $this->ajaxReturn($result);
                exit();
            }
        }
        
        if (in_array(7, $recruitConfigInfo)) {
            $saveData['home_address'] = I('post.homePlace');
            if ($saveData['home_address'] == '') {
                $result['error'] = '3003';
                $result['msg'] = '家庭地址不能为空！';
                $this->ajaxReturn($result);
                exit();
            }
        }
        
        if (in_array(8, $recruitConfigInfo)) {
            $saveData['job'] = I('post.work');
            if ($saveData['job'] == '') {
                $result['error'] = '3003';
                $result['msg'] = '职业不能为空！';
                $this->ajaxReturn($result);
                exit();
            }
        }
        
        $saveData['contact_name'] = I('post.name', '0', 'string');
        $saveData['status'] = '5';
        $saveData['role'] = '1';
        $saveData['add_from'] = 3;
        $saveData['apply_time'] = date('YmdHis');
        $saveData['age'] = $age;
        $saveID = M('twfx_saler')->add($saveData);
        if ($saveID) {
            $result['error'] = '0';
            $result['msg'] = '申请成功';
            $wfxModel = D('Wfx');
            $countInfo['apply_count'] = 1;
            $wfxModel->setRecruitDayStat($nodeId, $salerId, $countInfo);
        } else {
            $result['error'] = '1004';
            $result['msg'] = '请确认手机号码未在此商户下申请过！';
        }
        $this->ajaxReturn($result);
    }
    
    // 发送更换绑定手机号的验证码
    public function sendChangeCode() {
        $phoneNo = I('post.change_phone', null);
        $type = I('type', null);
        if (! check_str($phoneNo, 
            array(
                'null' => false, 
                'strtype' => 'mobile'), $error)) {
            if ($type == 'recruit') {
                $this->error("手机号码{$error}");
            } else {
                $this->error("换绑手机号{$error}");
            }
        }
        // 测试环境不下发，验证码直接为1111
        if (! is_production()) {
            $groupChangeCode = array(
                'number' => 1111, 
                'add_time' => time(), 
                'phoneNo' => $phoneNo);
            session('groupChangeCode', $groupChangeCode);
            $this->ajaxReturn("success", "验证码已发送", 1);
        }
        
        // 发送频率验证
        $groupChangeCode = session('groupChangeCode');
        if (! empty($groupChangeCode) &&
             (time() - $groupChangeCode['add_time']) < $this->expiresTime) {
            $this->error('动态密码发送过于频繁!');
        }
        $num = mt_rand(1000, 9999);
        $exptime = $this->CodeexpiresTime / 60;
        if ($type == 'recruit') {
            $text = "您的动态密码是：{$num}，有效期{$exptime}分钟。如非本人操作请忽略。";
        }
        
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
        $groupChangeCode = array(
            'number' => $num, 
            'add_time' => time(), 
            'phoneNo' => $phoneNo);
        session('groupChangeCode', $groupChangeCode);
        $this->success('动态密码已发送');
    }
}
