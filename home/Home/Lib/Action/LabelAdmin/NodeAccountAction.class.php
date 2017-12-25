<?php

/**
 * 编辑支付宝账户
 */
class NodeAccountAction extends BaseAction {

    public function _initialize() {
        parent::_initialize();
    }

    public function edit() {
        $model = M('tnode_account');
        $map = array(
            'node_id' => $this->node_id);
        
        $nodeAccount = $model->where($map)->select();
        $nodeAccountInfo = array_valtokey($nodeAccount, 'account_type');
        $cashInfo = M('tnode_cash')->where(
            array(
                'node_id' => $this->node_id))->find();
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        // 判断是否为电商正是用户
        $nodeAccountInfo['wctype'] = 2;
        // if (!$this->hasPayModule('m2')) {
        // $nodeAccountInfo['wctype'] = 2;
        // $cashInfo['bank_type'] = 1;
        // }
        
        if ($this->isPost()) {
            
            $role_id = M('tuser_info')->where(
                array(
                    'user_name' => $this->user_name))->getField('role_id');
            if ($role_id > 2) {
                $this->error('非管理员账户不得操作收款帐号');
            }
            // 接收参数
            $error = '';
            // 数据校验
            $rules = array(
                'zfb_pay_flag' => array(
                    'null' => false, 
                    'name' => '手机支付宝开通标志', 
                    'inarr' => array(
                        '2', 
                        '1')), 
                'union_pay_flag' => array(
                    'null' => false, 
                    'name' => '银联手机支付开通标志', 
                    'inarr' => array(
                        '2', 
                        '1')), 
                'wx_pay_flag' => array(
                    'null' => false, 
                    'name' => '微支付开通标志', 
                    'inarr' => array(
                        '2', 
                        '1')), 
                'receive_phone' => array(
                    'null' => false, 
                    'strtype' => 'mobile', 
                    'name' => '接受通知手机号'), 
                'account_pwd' => array(
                    'null' => true, 
                    'name' => '保护密码'), 
                'account_pwd2' => array(
                    'null' => true, 
                    'name' => '确认保护密码'), 
                'cash_account_flag' => array(
                    'null' => true, 
                    'name' => '提现账户类型', 
                    'inarr' => array(
                        '0', 
                        '1')), 
                'cash_bank' => array(
                    'null' => true, 
                    'maxlen_cn' => '50', 
                    'name' => '提现银行'), 
                'cash_bank_ex' => array(
                    'null' => true, 
                    'maxlen_cn' => '100', 
                    'name' => '提现银行支行全程'), 
                'cash_name' => array(
                    'null' => true, 
                    'maxlen_cn' => '30', 
                    'name' => '开户人姓名'), 
                'cash_no' => array(
                    'null' => true, 
                    'maxlen' => '50', 
                    'name' => '银行卡号'), 
                'cash_no2' => array(
                    'null' => true, 
                    'maxlen' => '50', 
                    'name' => '确认银行卡号'));
            if (I('zfb_pay_flag', 0, 'intval') == '1') {
                $rules['account_name'] = array(
                    'null' => true, 
                    'maxlen_cn' => '50', 
                    'name' => '支付宝帐号姓名');
                $rules['account_no'] = array(
                    'null' => true, 
                    'maxlen' => '50', 
                    'name' => '支付宝帐号');
                $rules['account_no2'] = array(
                    'null' => true, 
                    'maxlen' => '50', 
                    'name' => '确认支付宝帐号');
            }
            $reqData = $this->_verifyReqData($rules);
            // //判断C1和C2用户是否开户人姓名为对公
            // if(2 == $nodeAccountInfo['wctype']){
            // if($nodeInfo['node_name'] != $reqData['cash_name'])
            // $this->error("开户人姓名应该和企业名称保持一致");
            // }
            // 保护密码
            if (($reqData['account_pwd'] != '') &&
                 ($reqData['account_pwd2'] != '') &&
                 ($reqData['account_pwd'] != $reqData['account_pwd2'])) {
                $this->error("2次输入的保护密码不一致");
            }
            
            if ($reqData['account_no'] &&
                 ($reqData['account_no'] != $reqData['account_no2'])) {
                $this->error("2次输入的支付宝帐号不一致");
            }
            
            $nodeData = array();
            if ($reqData['account_pwd'] != '' && $reqData['account_pwd2'] != '') {
                $nodeData['account_pwd'] = md5($reqData['account_pwd']);
            }
            // 更新手机号
            $nodeData['receive_phone'] = $reqData['receive_phone'];
            $result = M('tnode_info')->where(
                array(
                    'node_id' => $this->node_id))->save($nodeData);
            if ($result === false) {
                $this->error("更新接受通知手机号失败");
            }
            
            for ($vi = 1; $vi <= 3; $vi ++) {
                // 1 支付宝 2联动优势 3微信
                $data_str = array();
                if ($nodeAccountInfo[$vi]) { // 更新
                    log_write(print_r($nodeAccountInfo[$vi], true));
                    $map['id'] = $nodeAccountInfo[$vi]['id'];
                    if ($vi == 1) {
                        $data_str['account_no'] = $reqData['account_no'];
                        $data_str['account_name'] = $reqData['account_name'];
                        $data_str['status'] = $reqData['zfb_pay_flag'];
                    } elseif ($vi == 2) {
                        $data_str['account_no'] = '1234567890';
                        $data_str['account_name'] = 'union_test';
                        $data_str['status'] = $reqData['union_pay_flag'];
                    } elseif ($vi == 3) {
                        $data_str['account_no'] = '09876543210';
                        $data_str['account_name'] = 'weixin_test';
                        $data_str['status'] = $reqData['wx_pay_flag'];
                    }
                    
                    $ret = $model->where($map)->save($data_str);
                    if ($ret === false) {
                        $this->error("更新收款帐号失败" . $vi);
                    }
                } else { // 新增
                    $data_str = array(
                        'node_id' => $this->node_id, 
                        'account_type' => $vi, 
                        // 'account_no'=>$account_no,
                        // 'account_name'=>$account_name,
                        // 'status'=>'1',
                        'add_time' => date('YmdHis'));
                    // 'account_bank'=>'支付宝'
                    
                    if ($vi == 1) {
                        $data_str['account_no'] = $reqData['account_no'];
                        $data_str['account_name'] = $reqData['account_name'];
                        $data_str['status'] = $reqData['zfb_pay_flag'];
                        $data_str['account_bank'] = '支付宝';
                    } elseif ($vi == 2) {
                        $data_str['account_no'] = '1234567890';
                        $data_str['account_name'] = 'union_test';
                        $data_str['status'] = $reqData['union_pay_flag'];
                        $data_str['account_bank'] = '联动优势';
                        $data_str['fee_rate'] = 0.02;
                        $data_str['rolytal_day'] = 3;
                    } elseif ($vi == 3) {
                        $data_str['account_no'] = '09876543210';
                        $data_str['account_name'] = 'weixin_test';
                        $data_str['status'] = $reqData['wx_pay_flag'];
                        $data_str['account_bank'] = '微信';
                        $data_str['fee_rate'] = 0.02;
                        $data_str['rolytal_day'] = 3;
                    }
                    
                    $ret = $model->add($data_str);
                    if ($ret === false) {
                        $this->error("新增收款帐号失败" . $vi);
                    }
                }
            }
            if ($reqData['cash_no']) {
                // 编辑提现帐号
                if ($cashInfo) {
                    $dataInfo = array(
                        'bank_type' => $reqData['cash_account_flag'], 
                        'account_bank' => $reqData['cash_bank'], 
                        'account_bank_ex' => $reqData['cash_bank_ex'], 
                        'account_name' => $reqData['cash_name'], 
                        'account_no' => $reqData['cash_no'], 
                        'modify_time' => date('YmdHis'));
                    $result = M('tnode_cash')->where(
                        array(
                            'id' => $cashInfo['id']))->save($dataInfo);
                    if ($result === false) {
                        $this->error('更新提现帐号信息失败');
                    }
                } else {
                    $dataInfo = array(
                        'node_id' => $this->node_id, 
                        'bank_type' => $reqData['cash_account_flag'], 
                        'account_bank' => $reqData['cash_bank'], 
                        'account_bank_ex' => $reqData['cash_bank_ex'], 
                        'account_name' => $reqData['cash_name'], 
                        'account_no' => $reqData['cash_no'], 
                        'add_time' => date('YmdHis'));
                    $result = M('tnode_cash')->add($dataInfo);
                    if ($result === false) {
                        $this->error('新增提现帐号信息失败');
                    }
                }
            }
            
            $this->success("编辑成功", 
                array(
                    "关闭" => "javascript:window.parent.location.reload();"));
        }
        
        $this->assign('nodeAccountInfo', $nodeAccountInfo);
        $this->assign('nodeInfo', $nodeInfo);
        $this->assign('cashInfo', $cashInfo);
        $this->display(); // 输出模板
    }
    
    // 状态修改
    public function editStatus() {
        $id = I('post.id', null, 'intval');
        $status = I('post.status', null);
        if (is_null($id) || is_null($status)) {
            $this->error('缺少必要参数');
        }
        $result = M('tnode_account')->where(
            "node_id ='{$this->node_id}' AND account_type='1' and id='{$id}'")->find();
        if (! $result) {
            $this->error('未找到该收款记录');
        }
        if ($status == '1') {
            $data = array(
                'status' => '1');
        } else {
            $data = array(
                'status' => '2');
        }
        $result = M('tnode_account')->where("id='{$id}'")->save($data);
        if ($result) {
            node_log('收款帐号状态更改|ID：' . $id);
            $this->success('更新成功', 
                array(
                    '返回' => U('Home/Index/index')));
        } else {
            $this->error('更新失败');
        }
    }

    public function reset_pwd() {
        $nodeInfo = M('tnode_info')->where("node_id='{$this->nodeId}'")->find();
        
        // 重置链接 id，mac，
        $data = array(
            'node_id' => $this->node_id, 
            'oper_id' => $this->userInfo['user_id'], 
            'status' => '0', 
            'add_time' => date('YmdHis'));
        $result = M('to2opwd_reset_trace')->add($data);
        if ($result === false) {
            $this->error("重置密码失败，请联系客服人员进行重置");
        }
        $mac = md5($result . $this->node_id . $this->userInfo['user_id']); // 248
                                                                           // 00004488
        $reset_url = C('CURRENT_DOMAIN') .
             'index.php?g=LabelAdmin&m=NodeAccount&a=resetPwd&id=' . $result .
             '&node_id=' . $this->node_id . '&mac=' . $mac;
        // $reset_url =
        // 'http://localhost/WANGCAI/wangcai_plateform/source/php/home/index.php?g=LabelAdmin&m=NodeAccount&a=resetPwd&id='.$result
        // . '&node_id=' . $this->node_id . '&mac=' . $mac;
        $this->assign('content', $reset_url);
        $content = $this->fetch('reset_pwd');
        $ps = array(
            "subject" => "=?utf-8?B?" . base64_encode("重置收款帐号密码") . "?=", 
            "content" => $content, 
            "email" => $nodeInfo['contact_eml']);
        
        $resp = send_mail($ps);
        if ($resp['sucess'] == '1') {
            $this->success(
                "你的密码重置邮件已发送您的邮箱【{$nodeInfo['contact_eml']}】<br />请注意查收", 
                array(
                    '返回' => 'javascript:history.go(-1)'));
        } else {
            $this->error("你的收款帐号密码重置邮件发送失败", 
                array(
                    '返回' => 'javascript:history.go(-1)'));
        }
    }

    public function shorturl($long_url) {
        $apiUrl = C('ISS_SERV_FOR_IMAGECO');
        $req_arr = array(
            'CreateShortUrlReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'TransactionID' => time() . rand(10000, 99999), 
                'OriginUrl' => "<![CDATA[$long_url]]>"));
        
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $xml = new Xml();
        $str = $xml->getXMLFromArray($req_arr, 'gbk');
        $error = '';
        $result_str = httpPost($apiUrl, $str, $error);
        if ($error) {
            echo $error;
            return '';
        }
        
        $arr = $xml->parse($result_str);
        $arr = $xml->getArrayNoRoot();
        
        return $arr['Status']['StatusCode'] == '0000' ? $arr['ShortUrl'] : '';
    }

    public function resetPwd() {
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->find();
        $id = I('id', null);
        $node_id = I('node_id', null);
        $mac = I('mac', null);
        $hasEcshop = $this->_hasEcshop();
        $saleflag = $hasEcshop ? 2 : 1;
        
        if ($this->isPost()) {
            $data = $_REQUEST['data'];
            $pwd = $data[0]['value'];
            $pwd2 = $data[1]['value'];
            $id = $data[2]['value'];
            $node_id = $data[3]['value'];
            $mac = $data[4]['value'];
            
            $role_id = M('tuser_info')->where(
                array(
                    'user_name' => $this->user_name))->getField('role_id');
            
            if ($role_id > 2) {
                $this->error('非管理员账户不得操作收款帐号');
            }
            
            if ($pwd != $pwd2) {
                $this->error("2次输入的收款帐号密码不一致");
            }
            
            if (md5($id . $node_id . $this->userInfo['user_id']) != $mac) {
                $this->error("校验失败，无法重置密码");
            }
            $traceInfo = M('to2opwd_reset_trace')->where(
                array(
                    'id' => $id, 
                    'node_id' => $node_id))->find();
            if (! $traceInfo || $traceInfo['status'] != '0') {
                $this->error("重置密码记录无效");
            }
            if (substr($traceInfo['add_time'], 0, 8) != date('Ymd')) {
                $this->error("重置密码链接已过期" . substr($traceInfo['add_time'], 1, 8));
            }
            Log::write(
                "商户重置密码，商户号【" . $node_id . "】操作员【" . $this->userInfo['user_id'] .
                     "】记录流水ID【" . $id . "】");
            $ret = M('tnode_info')->where(
                array(
                    'node_id' => $node_id))->save(
                array(
                    'account_pwd' => md5($pwd)));
            
            if ($ret === false) {
                $this->error("重置密码失败");
            } else {
                $ret = M('to2opwd_reset_trace')->where(
                    array(
                        'id' => $id, 
                        'node_id' => $node_id))->save(
                    array(
                        'status' => '1'));
                $this->success("密码重置成功");
            }
        }
        $this->assign('mac', $mac);
        $this->assign('id', $id);
        $this->assign('node_id', $node_id);
        $this->assign('nodeInfo', $nodeInfo);
        $this->assign('saleflag', $saleflag);
        $this->display();
    }

    /*
     * 校验密码
     */
    public function check_pwd() {
        $input_pwd = I('input_pwd', null);
        if (! $input_pwd) {
            $this->error('保护密码不能为空');
        }
        
        $passwd = M('tnode_info')->where(
            array(
                'node_id' => $this->node_id))->getField('account_pwd');
        
        if (md5($input_pwd) == $passwd) {
            //检查密码输入是否正确
            session('accountPassCheck', $this->node_id);
            $this->success('保护密码校验成功');
        } else {
            $this->error('保护密码错误');
        }
    }

    /**
     * 提现前检测账户信息完整性
     */
    public function checkAccount() {
        $NodeModel = D('node');
        // 存储要返回的提示信息
        $returnTip = array();
        $accountInfo = $NodeModel->getAccountInfo($this->node_id);
        $nodeInfo = M('tnode_info')->field('wc_version')
            ->where(array(
            'node_id' => $this->node_id))
            ->find();
        // 判断是否为电商正是用户
        $wctype = 2;
        // if (!$this->hasPayModule('m2')) {
        // $wctype = 2;
        // $batchInfo = M('tmarketing_info')->field('end_time')->where(
        // array('node_id' => $this->node_id, 'batch_type' => '55')
        // )->find();
        // $nowTime = time();
        // if (isset($batchInfo['end_time'])) {
        // $getCashTime = strtotime($batchInfo['end_time']) + 3600 * 24 * 14;
        // if ($nowTime < $getCashTime) {
        // $returnTip['msg'] = "提现功能将在" . date('Y年m月d日 H时i分s秒', $getCashTime) .
        // "开放，即活动结束后14天。";
        // }
        // }
        // }
        // 字段与表单中的必填项相对应
        if (2 === $wctype) {
            $fieldName = array(
                'account_pwd' => '保护密码', 
                'c_bank' => '银行名称', 
                'c_bank_ex' => '银行支行全称', 
                'c_name' => '开户人姓名', 
                'c_no' => '银行卡号', 
                'i_phone' => '接收通知手机号');
        } else {
            $fieldName = array(
                'a_no' => '支付宝账号', 
                'a_name' => '账号姓名', 
                'account_pwd' => '保护密码', 
                'c_bank' => '银行名称', 
                'c_bank_ex' => '银行支行全称', 
                'c_name' => '开户人姓名', 
                'c_no' => '银行卡号', 
                'i_phone' => '接收通知手机号');
        }
        
        foreach ($accountInfo as $k => $v) {
            if (empty($v)) {
                foreach ($fieldName as $k1 => $v1) {
                    if ($k == $k1) {
                        $returnTip[$k] = $v1;
                    }
                }
            }
        }
        if (empty($returnTip)) {
            $this->ajaxReturn(true, 'JSON');
        } else {
            $this->ajaxReturn($returnTip, 'JSON');
        }
    }
}
