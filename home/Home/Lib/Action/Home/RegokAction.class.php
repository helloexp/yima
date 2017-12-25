<?php

class RegokAction extends Action {

    /**
     *
     * @var LoginService
     */
    private $LoginService;

    public function index() {
        $autologininfo = session('autologininfo');
        if ($autologininfo) { // �Զ���¼ sso��¼
            $this->LoginService = D('Login', 'Service');
            $this->LoginService->ssoLogin($autologininfo);
        }
        
        $userService = D('UserSess', 'Service');
        $userInfo = $userService->getUserInfo();
        $this->assign("userInfo", $userInfo);
        $_s = session('autologininfo');
        if (empty($_s)) {
            $this->assign("autologininfo", true);
        } else {
            $this->assign("autologininfo", $_s);
        }
        $fromurl = session('fromurl');
        if ($fromurl != '') {
            session('fromurl', null);
            if ($fromurl == 'buyWc') { // 产品介绍页 多乐互动 套餐区 立即购买
                $this->redirect('Home/Wservice/basicVersion');
            } else if ($fromurl == 'marketToolVersion') { // 产品介绍页 多乐互动 单品 立即购买
                $this->redirect('Home/Wservice/marketToolVersion');
            } else if ($fromurl == 'alipay') { // 产品介绍页 条码支付 申请开通
                $this->redirect('Alipay/Index/info_alipay');
            } else if ($fromurl == 'Wapply_terminal') { // 产品介绍页 申请验证终端
                $this->redirect('Home/Store/Wapply_terminal');
            } else if ($fromurl == 'promotionn') { // 产品介绍页 会员积分 会员管理
                $this->redirect('Wmember/Member/promotionn4880');
            } else if ($fromurl == 'integralMarketing') { // 产品介绍页 会员积分 多赢积分
                                                          // ��Ӯ����
                $this->redirect('Integral/Integral/index');
            } else {
                redirect($fromurl);
            }
        } else {
            $this->display();
        }
    }

    public function ssoLogin($email, $password, $nodeId) {
        // ��ѯ����
        $uInfo = M('tuser_info')->field('node_id,user_id')
            ->where("user_name='" . $email . "'")
            ->find();
        $req_array = array(
            "email" => $email, 
            "password" => $password, 
            "node_id" => $nodeId);
        $RemoteRequest = D('UserSess', 'Service');
        $reqResult = $RemoteRequest->ssoLogin($req_array);
        
        // ��ѯ�Ƿ��һ�ε�½
        $userloginInfo = M('tuser_info')->field('login_time,first_time')
            ->where("user_name='" . $email . "'")
            ->find();
        
        log_write($email . "��ȡ��¼ʱ���Ƿ�Ϊ�գ�" . $userloginInfo['login_time']);
        
        if ($reqResult === true) {
            // ����ip,��½ʱ��
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
                "node_id='" . $nodeId . "'" . " AND " . "user_id='" .
                     $uInfo['user_id'] . "'")->save($data);
            node_log("��¼", null, 
                array(
                    'log_type' => 'LOGIN'));
            $qsql = M()->getLastSql();
            log_write(
                "�����û�===node_id=" . $nodeId . "==user_id=" . $uInfo['user_id'] .
                     "===" . $qsql);
            if (! $result) {
                log_write('����ip���ϴε�½ʱ��ʧ��');
            }
            unset($_SESSION['verify']);
        }
    }
}