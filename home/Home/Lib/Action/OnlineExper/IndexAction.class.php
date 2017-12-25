<?php

class IndexAction extends Action {

    public $openid;

    public $startDate;

    public $endDate;

    public $codeStr;

    function _initialize() {
        $this->startDate = date('Ymd') . '000000';
        $this->endDate = date('Ymd') . '235959';
        $this->codeStr = 'abcdefghjkmnpqrstuvwxy13456789';
        $this->openid = I('openid');
        if ($_SESSION['onlineExper']['code'] !== '') {
            $this->_checkCouldPlayNow();
        }
    }

    function pcIndex() {
        $isIe = strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE');
        $isQQ = strpos($_SERVER['HTTP_USER_AGENT'], 'QQBrowser');
        $isFireFox = strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox');
        $is2345 = strpos($_SERVER['HTTP_USER_AGENT'], '2345Explorer');
        if ($isIe || $isFireFox || $isQQ || $is2345) {
            //如果是IE浏览器 QQ浏览器 火狐浏览器 2345浏览器 叫他滚
            $this->display('wrongBrowser');
        } else {
            $this->display();
        }
    }

    function wapIndex() {
        $this->display();
    }

    function getCodeSuccess() {
        $this->display();
    }

    function wapLogin() {
        if ($_SESSION['onlineExper']['phone'] != '') {
            $this->assign('phone', $_SESSION['onlineExper']['phone']);
        }
        $this->display();
    }

    function phone() {
        $this->display();
    }

    function getCode() {
        if ($this->openid == '') {
            $surl = $_SERVER['REQUEST_URI'];
            $resultSurl = htmlspecialchars(urlencode($surl));
            redirect(
                U('Label/GetWeiXinInfo/goAuthorize', 
                    array(
                        'node_id' => 'onlineExper', 
                        'surl' => $resultSurl)));
        } else {
            $this->assign('openid', $this->openid);
        }
        $this->display();
    }

    function createCode() {
        $result = array();
        $data = array();
        $phone = I('post.phone', '0', 'string');
        if (! check_str($phone, array(
            'strtype' => 'mobile'))) {
            $result['error'] = '10001';
            $result['msg'] = '请检查手机格式';
            $this->ajaxReturn($result);
            exit();
        }
        $type = I('post.type');
        if ($type != 'noCheck') {
            $name = I('post.name', '0', 'string');
            if ($name === '0' || $name == '') {
                $result['error'] = '20001';
                $result['msg'] = '请核对姓名';
                $this->ajaxReturn($result);
            }
        }
        $companyName = I('post.company', '0', 'string');
        
        $onlineExperienceModel = M('TonlineExperience');
        
        if (strpos($str, 'MicroMessenger')) {
            $searchConditionTwo = array();
            $searchConditionTwo['open_id'] = $this->openid;
            $searchConditionTwo['status'] = '0';
            $searchConditionTwo['add_time'] = array(
                'between', 
                array(
                    $this->startDate, 
                    $this->endDate));
            $sendCodeTimeCount = $onlineExperienceModel->where(
                $searchConditionTwo)->count();
            if ($sendCodeTimeCount >= 3) {
                $result['error'] = '60001';
                $result['msg'] = '您今天已经申请过三次啦！明天再来吧！';
                $this->ajaxReturn($result);
                exit();
            }
        }
        
        $sql = 'select `id`, code from tonline_experience where (phone=' . $phone .
             ') AND ((`status`=0) OR (add_time like "%' . date('Ymd') . '%"))';
        $sendCodeTime = M()->query($sql);
        
        if ($sendCodeTime) {
            $_SESSION['onlineExper']['phone'] = $phone;
            $result['error'] = '30001';
            $result['msg'] = '您已经申请过此手机号码的验证码啦！验证码为：' . $sendCodeTime[0]['code'];
            $this->ajaxReturn($result);
            exit();
        }
        
        $data['open_id'] = $this->openid;
        $data['name'] = $name;
        $data['phone'] = $phone;
        $data['company_name'] = $companyName;
        $data['status'] = '0';
        $data['add_time'] = date('YmdHis');
        
        $code = '';
        $len = strlen($this->codeStr) - 1;
        for ($i = 1; $i <= 4; $i ++) {
            $randNum = mt_rand(0, $len);
            $code .= substr($this->codeStr, $randNum, 1);
        }
        $codeResult = $this->_checkCodeUnique($code);
        if (is_array($codeResult)) {
            $this->ajaxReturn($codeResult);
        } else {
            $data['code'] = $codeResult;
        }
        
        if (! is_production()) {
            $addID = $onlineExperienceModel->data($data)->add();
            if ($addID) {
                $_SESSION['onlineExper']['phone'] = $phone;
                $result['error'] = '0';
                $result['msg'] = '发码成功！';
                $result['code'] = $codeResult;
                $this->ajaxReturn($result);
            }
            exit();
        }
        
        $content = M('TsystemParam')->where(
            array(
                'param_name' => 'ONLINE_EXPERIENCE_NOTE'))->getfield(
            'param_value');
        $sendContent = str_replace('#CODE#', $data['code'], $content);
        
        $RemoteRequest = D('RemoteRequest', 'Service');
        $isSend = $RemoteRequest->smsSend($phone, $sendContent);
        if ($isSend) {
            $addID = $onlineExperienceModel->data($data)->add();
            if ($addID) {
                $result['error'] = '0';
                $result['msg'] = '发码成功！';
                $_SESSION['onlineExper']['phone'] = $phone;
                $this->ajaxReturn($result);
            }
        } else {
            $result['error'] = '40001';
            $result['msg'] = '发码接口存在问题，请联系客服！';
            $this->ajaxReturn($result);
        }
    }

    /**
     * 验证体验码
     */
    function checkCode() {
        $result = array();
        $code = I('post.code', '0', 'string');
        $phone = I('post.phone', '0', 'string');

        
        if ($code == C('onlineExper')) {
            //万能码 验证通过
            $_SESSION['onlineExper'] = array(
                'code' => $code);
            $this->assign('onlineExpUse', 'Y');
            $this->ajaxReturn(array(
                'error' => '0'));
            exit();
        }
        
        if ($code == '0') {
            $result['error'] = '30001';
            $result['msg'] = '请填写体验码！';
            $this->ajaxReturn($result);
            exit();
        }
        
        $type = I('post.type', '0', 'string');
        $searchCondition = array();
        $searchCondition['code'] = $code;
        $searchCondition['status'] = '0';
        if ($type == 'wap') {
            $sql = "SELECT id, pc_check, wap_check, use_time, phone FROM `tonline_experience` WHERE ( `code` = '" .
                 $code . "' ) AND (`phone` = '" . $phone .
                 "') AND (( `status` = 0 ) OR ( `use_time` LIKE '" . date(Ymd) .
                 "%' )) LIMIT 1";
        } else {
            $sql = "SELECT id, pc_check, wap_check, use_time, phone FROM `tonline_experience` WHERE ( `code` = '" .
                 $code . "' ) AND (( `status` = 0 ) OR ( `use_time` LIKE '" .
                 date(Ymd) . "%' )) LIMIT 1";
        }
        
        $codeInfo = M()->query($sql);
        if (empty($codeInfo)) {
            //如果表中查不到数据
            $result['error'] = '20001';
            $result['msg'] = '请填写正确的体验码！';
            $this->ajaxReturn($result);
            exit();
        } else {
            //如果查得到数据 证明验证码正确 往下走
            $_SESSION['onlineExper'] = array(
                'code' => $code);
            $date = date('YmdHis');
        }
        
        $onlineExperienceModel = M('TonlineExperience');
        if ($type == 'wap') {
            if ($codeInfo[0]['pc_check'] == '1') {
                $onlineExperienceModel->where(
                    array(
                        'id' => $codeInfo[0]['id']))->save(
                    array(
                        'status' => '1', 
                        'wap_check' => '1', 
                        'use_time' => $date));
            } else {
                $onlineExperienceModel->where(
                    array(
                        'id' => $codeInfo[0]['id']))->save(
                    array(
                        'wap_check' => '1', 
                        'use_time' => $date));
            }
            $_SESSION['groupPhone'] = $codeInfo[0]['phone'];
            cookie('_global_user_mobile', $codeInfo[0]['phone'], 
                3600 * 24 * 365);
            $this->ajaxReturn(array(
                'error' => '0'));
            exit();
        } elseif ($type == 'pc') {
            if ($codeInfo[0]['wap_check'] == '1') {
                $onlineExperienceModel->where(
                    array(
                        'id' => $codeInfo[0]['id']))->save(
                    array(
                        'status' => '1', 
                        'pc_check' => '1', 
                        'use_time' => $date));
            } else {
                $onlineExperienceModel->where(
                    array(
                        'id' => $codeInfo[0]['id']))->save(
                    array(
                        'pc_check' => '1', 
                        'use_time' => $date));
            }
            session_unset();
            $this->ajaxReturn(array(
                'error' => '0'));
            exit();
        }
    }

    function _checkCodeUnique($code) {
        $onlineExperienceModel = M('TonlineExperience');
        $searchCondition = array();
        $searchCondition['status'] = '0';
        $codeCount = $onlineExperienceModel->where($searchCondition)->count();
        if ($codeCount == 810000) {
            return array(
                'error' => '50001', 
                'msg' => '验证码已用完！请联系客服！');
            exit();
        }
        
        $searchCondition['code'] = $code;
        $isExist = $onlineExperienceModel->where($searchCondition)->getfield(
            'id');
        if (! $isExist) {
            return $code;
        } else {
            $code = '';
            $len = strlen($this->codeStr) - 1;
            for ($i = 1; $i <= 4; $i ++) {
                $randNum = mt_rand(0, $len);
                $code .= substr($this->codeStr, $randNum, 1);
            }
            
            $this->_checkCodeUnique($code);
        }
    }

    function _checkCouldPlayNow() {
        $searchCondition = array();
        $searchCondition['code'] = $_SESSION['onlineExper']['code'];
        $searchCondition['phone'] = $_SESSION['onlineExper']['phone'];
        $searchCondition['use_time'] = array(
            'between', 
            array(
                $this->startDate, 
                $this->endDate));
        $onlineExperienceModel = M('TonlineExperience');
        $codeInfo = $onlineExperienceModel->where($searchCondition)
            ->field('pc_check, wap_check, phone')
            ->find();
        if ($codeInfo['pc_check'] == '1' && $codeInfo['wap_check'] == '1') {
            $_SESSION['groupPhone'] = $codeInfo[0]['phone'];
            cookie('_global_user_mobile', $codeInfo[0]['phone'], 
                3600 * 24 * 365);
            $this->assign('onlineExpUse', 'Y');
        }
    }
}
