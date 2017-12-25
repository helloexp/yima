<?php

/**
 * 申请qq公众号 author wang pan Date: 2015/11/23 Time: 16:31
 */
class QqCodeAction extends BaseAction {

    private $qqCodeUserInfo;
    // 用户姓名和联系电话
    private $isThrough;

    public function _initialize() {
        parent::_initialize();
        // 获取用户注册信息
        $this->getNodeQqCode();
        $this->assign('qqCodeUserInfo', $this->qqCodeUserInfo);
        // 获取用户申请信息
        $nodeId = $this->node_id;
        $jobApplyModel = $this->getJobApplyModel();
        $isThrough = $jobApplyModel->getQqCodeInfo($nodeId);
        $this->isThrough = $isThrough;
    }
    // 全局权限设置
    public function beforeCheckAuth() {
        $this->_authAccessMap = '*';
    }

    /**
     *
     * @return JobApplyModel
     */
    private function getJobApplyModel() {
        if (empty($this->jobApplyModel)) {
            $this->jobApplyModel = D('JobApply');
        }
        return $this->jobApplyModel;
    }

    /**
     * Q码页面显示
     */
    public function index() {
        $isThrough = $this->isThrough;
        $isAgain = I('get.isAgain');
        $state = 1;
        
        // 已申请的情况下
        if (isset($isThrough)) {
            if ($isThrough['status'] == 5) {
                // 5是申请失败
                $state = 5;
                $this->assign('isThrough', 
                    json_decode($isThrough['apply_content'], true));
            } elseif ($isThrough['status'] == 4) {
                // 4是成功
                $state = 4;
                $this->assign('isThrough', 
                    json_decode($isThrough['apply_content'], true));
            } else {
                // 2是等待中
                $state = 2;
            }
        }
        if ($isAgain == 'again') {
            $this->assign('qqCodeUserInfo', $this->qqCodeUserInfo);
            $this->assign('isAgain', 'again1');
            $this->assign('isThrough', 
                json_decode($isThrough['apply_content'], true));
            $state = 1;
        }
        $this->assign('state', $state);
        $this->display();
    }

    /**
     * 接收处理Q码申请表单信息
     */
    public function apply() {
        
        // 检测是否已申请成功过
        if ($this->isThrough['status'] == 4) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '您已申请过'), 'JSON');
        }
        if ($this->isThrough['status'] == 2) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '您的申请已提交，请勿重复提交'), 'JSON');
        }
        // 验证企业基本信息
        $userInfo = I('post.');
        
        if (! is_numeric($userInfo['qqNumber']) || empty($userInfo['qqNumber'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '管理员QQ号不能为空'), 'JSON');
        }
        if (empty($userInfo['userName'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '账户名不能为空'), 'JSON');
        }
        if (empty($userInfo['enterpriseName'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '企业名不能为空'), 'JSON');
        }
        if (empty($userInfo['businessId'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '请上传营业执照'), 'JSON');
        }
        if (empty($userInfo['organizeDocuments'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '请上传组织机构证件'), 'JSON');
        }
        // 验证申请人信息
        if (empty($userInfo['applyType'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '请选择申请类型'), 'JSON');
        }
        
        if (empty($userInfo['applyPeople'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '请填写注册人姓名'), 'JSON');
        }
        if (! is_numeric($userInfo['telePhone'])) {
            $isEmail = preg_match(
                '/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/', 
                $userInfo['telePhone']);
            if ($isEmail != 1) {
                $this->ajaxReturn(
                    array(
                        'status' => 0, 
                        'info' => '联系方式错误'), 'JSON');
            }
        }
        if (! verify_IDCard($userInfo['identityCard'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '身份证错误'), 'JSON');
        }
        
        // 验证已有平台信息
        if (empty($userInfo['platformName'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '请填写已有平台名称'), 'JSON');
        }
        if (empty($userInfo['platformAccount'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '请填写平台账号名称'), 'JSON');
        }
        if (empty($userInfo['platformID'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '请填写平台ID号'), 'JSON');
        }
        if (empty($userInfo['platformInfo'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '请填写平台认证信息'), 'JSON');
        }
        if (empty($userInfo['platformFansNumber']) ||
             ! is_numeric($userInfo['platformFansNumber'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '请填写正确的平台粉丝量'), 'JSON');
        }
        if (empty($userInfo['industryType'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '请填写行业类型'), 'JSON');
        }
        if (empty($userInfo['province'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '请选择您所在的省'), 'JSON');
        }
        if (empty($userInfo['city'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '请选择您所在的市'), 'JSON');
        }
        if (empty($userInfo['town'])) {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '请选择您所在的区'), 'JSON');
        }
        // 是否已发送Q码，1是未发送
        $userInfo['qqCodeStart'] = 1;
        
        // 审核状态
        $status = 2;
        // 检测用户是否为修改提交
        if ($this->isThrough['status'] == 5) {
            $counts = json_decode($this->isThrough['apply_content'], true);
            $userInfo['qqCodApplyStatus'] = $counts['qqCodApplyStatus'];
        }
        // 入库前整理数据
        $content = json_encode($userInfo);
        $nodeId = $this->node_id;
        $data = array(
            'node_id' => $nodeId, 
            'job_type' => 'e', 
            'apply_time' => date("YmdHis"), 
            'status' => $status, 
            'apply_content' => $content);
        // 入库
        $jobApplyModel = $this->getJobApplyModel();
        
        $isAgain = str_replace("'", '', $userInfo['isAgain']);
        
        if ($isAgain == 'again1') {
            $isOk = $jobApplyModel->modQqCode($data, $this->node_id);
        } else {
            $isOk = $jobApplyModel->addQqCode($data);
        }
        $operateEmail = array(
            'zhengjie@imageco.com.cn', 
            'shaomin@imageco.com.cn', 
            'zhangyanling@imageco.com.cn');
        if ($userInfo['applyType'] == 1) {
            $userInfo['applyType'] = '订阅号';
        } else {
            $userInfo['applyType'] = '服务号';
        }
        if (is_numeric($userInfo['telePhone'])) {
            $this->qqCodeUserInfo['userTel'] = $userInfo['telePhone'];
        }
        
        $contents = <<<html
企业名称：{$userInfo['enterpriseName']}<br/>
联系人：{$this->qqCodeUserInfo['userName']}<br/>
联系电话：{$this->qqCodeUserInfo['userTel']}<br/>
邮箱：{$this->qqCodeUserInfo['userEmail']}<br/>
申请账号类型：{$userInfo['applyType']}
html;
        
        $ps = array(
            "subject" => "“用户申请Q码”", 
            "content" => $contents, 
            "email" => "");
        for ($i = 0; $i <= count($operateEmail); $i ++) {
            $ps['email'] = $operateEmail[$i];
            send_mail($ps);
        }
        
        if ($isOk) {
            
            $this->ajaxReturn(
                array(
                    'status' => 1, 
                    'info' => '已申请'));
        } else {
            $this->ajaxReturn(
                array(
                    'status' => 0, 
                    'info' => '申请失败'));
        }
    }

    /**
     * 获取商户的Q码信息
     */
    public function getNodeQqCode() {
        $nodeInfo = M('tnode_info')->field(
            'contact_name, contact_phone, contact_tel')
            ->where(array(
            'node_id' => $this->node_id))
            ->find();
        $userInfo = array(
            'userName' => $nodeInfo['contact_name'], 
            'userTel' => $nodeInfo['contact_phone'], 
            'userEmail' => $nodeInfo['contact_eml']);
        if (empty($userInfo['userTel'])) {
            $userInfo['userTel'] = $nodeInfo['contact_tel'];
        }
        if (empty($userInfo['userEmail'])) {
            $eml = session('userSessInfo');
            $userInfo['userEmail'] = $eml['user_name'];
        }
        
        $this->qqCodeUserInfo = $userInfo;
    }
}