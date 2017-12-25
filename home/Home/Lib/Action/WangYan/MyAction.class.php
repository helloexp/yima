<?php

class MyAction extends Action {

    public $_authAccessMap = "*";

    /**
     * [nodeAdd 注册]
     *
     * @return [type] [description]
     */
    public function nodeAdd() {
        $nodeName = $_POST['node_name'];
        $regemail = $_POST['regemail'];
        $contactName = $_POST['contact_name'];
        $contactPhone = $_POST['contact_phone'];
        $userPassword = $_POST['user_password'];
        $nodeAddData = array(
            'node_name' => htmlspecialchars(trim($nodeName)), 
            'node_short_name' => mb_substr(htmlspecialchars(trim($nodeName)), 0, 
                9, 'UTF-8'), 
            'regemail' => htmlspecialchars(trim($regemail)), 
            'contact_name' => htmlspecialchars(trim($contactName)), 
            'contact_phone' => htmlspecialchars(trim($contactPhone)), 
            'user_password' => md5(trim($userPassword)), 
            'province_code' => '09', 
            'city_code' => '021', 
            'town_code' => "", 
            'client_manager' => "", 
            'tg_id' => "", 
            'third_uid' => "", 
            'third_token' => "", 
            'reg_from' => '0', 
            'channel_id' => "", 
            'user_name' => htmlspecialchars(trim($regemail)));
        // 判断是否注册，如果已经注册了，则跳过注册阶段
        $serviceNodeReg = D('NodeReg', 'Service');
        $resultNodeAdd = $serviceNodeReg->nodeAdd($nodeAddData);
        if ($resultNodeAdd['status']) {
            echo "status[1]";
        } else {
            echo "status[0]info[" . $resultNodeAdd['info'] . "]";
        }
    }

    /**
     * [nodeAdd 注册]
     *
     * @return [type] [description]
     */
    public function nodeAddFast() {
        $pre = date('mdHis');
        $nodeName = '测试-' . $pre;
        $regemail = $pre . '@qq.com';
        $contactName = '测试账号';
        $contactPhone = '18888888888';
        $userPassword = '111111';
        $nodeAddData = array(
            'node_name' => htmlspecialchars(trim($nodeName)), 
            'node_short_name' => mb_substr(htmlspecialchars(trim($nodeName)), 0, 
                9, 'UTF-8'), 
            'regemail' => htmlspecialchars(trim($regemail)), 
            'contact_name' => htmlspecialchars(trim($contactName)), 
            'contact_phone' => htmlspecialchars(trim($contactPhone)), 
            'user_password' => md5(trim($userPassword)), 
            'province_code' => '09', 
            'city_code' => '021', 
            'town_code' => "", 
            'client_manager' => "", 
            'tg_id' => "", 
            'third_uid' => "", 
            'third_token' => "", 
            'reg_from' => '0', 
            'channel_id' => "", 
            'user_name' => htmlspecialchars(trim($regemail)));
        // 判断是否注册，如果已经注册了，则跳过注册阶段
        $serviceNodeReg = D('NodeReg', 'Service');
        $resultNodeAdd = $serviceNodeReg->nodeAdd($nodeAddData);
        if ($resultNodeAdd['status']) {
            $nodeId = M()->table("tuser_info a")->getFieldByUser_name($regemail,
                'node_id');
            $this->success('注册成功', '', 
                array(
                    'account' => $regemail, 
                    'pass' => '111111', 
                    'node_id' => $nodeId));
        } else {
            $this->error('注册失败，重新申请', '', 
                array(
                    'account' => $regemail, 
                    'pass' => '111111'));
        }
    }

    /**
     * [getNodeInfo 获取机构信息]
     *
     * @return [type] [description]
     */
    public function getNodeInfo() {
        $userName = I('post.condition');
        $type = I('post.type');
        if ($type == '1') {
            $res = M('tnode_info')->field(
                'node_id,node_name,contact_name,contact_phone,wc_version')
                ->where(
                array(
                    'node_id' => array(
                        'like', 
                        '%' . $userName . '%')))
                ->select();
        } else {
            $res = M()->table("tnode_info n")->field(
                'n.node_id,n.node_name,n.contact_name,n.contact_phone,n.wc_version')
                ->where(
                array(
                    'u.user_name' => array(
                        'like', 
                        '%' . $userName . '%')))
                ->join('tuser_info u ON u.node_id = n.node_id')
                ->limit(1)
                ->select();
        }
        $nodeInfo = $res[0];
        $nodeInfo['wbnumber'] = $this->getWbInfo($nodeInfo['node_id']);
        echo "nodeid[" . $nodeInfo['node_id'] . ']nodename[' .
             $nodeInfo['node_name'] . ']contact_name[' .
             $nodeInfo['contact_name'] . ']contact_phone[' .
             $nodeInfo['contact_phone'] . ']wcversion[' . $nodeInfo['wc_version'] .
             ']wbnumber[' . $nodeInfo['wbnumber'] . ']';
        exit();
    }

    /**
     * [setWcVersion 更改旺财权限]
     */
    public function setWcVersion() {
        $nodeid = I('post.nodeid');
        $wcversion = I('post.wcversion');
        if (false === M('tnode_info')->where(
            array(
                'node_id' => $nodeid))->save(
            array(
                'wc_version' => $wcversion))) {
            echo "status[0]";
        } else {
            echo "status[1]";
        }
    }

    /**
     * [setWb 设置旺币]
     */
    public function setWb() {
        $nodeId = I('post.nodeid');
        $wbnumber = I('post.wbnumber');
        $contractNo = M('tnode_info')->getFieldByNode_id($nodeId, 'contract_no');
        $req_array = array(
            'SetWbReq' => array(
                'SystemID' => 1000, 
                'TransactionID' => time() . rand(10000, 99999), 
                'ContractID' => $contractNo, 
                'WbType' => 1, 
                'BeginTime' => date('Ymd'), 
                'EndTime' => date('Ymd', strtotime('+5 year')), 
                'ReasonID' => 1, 
                'Amount' => 0.00, 
                'WbNumber' => $wbnumber, 
                'Remark' => "From soft"));
        log_write(print_r($req_array, true));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $WcInfo = $RemoteRequest->requestYzServ($req_array);
        log_write($WcInfo);
        if ($WcInfo['Status']['StatusCode'] == '0000') {
            echo "status[1]";
        } else {
            echo "status[0]";
        }
        exit();
    }

    /**
     * [getWbInfo 获取旺币信息]
     *
     * @return [type] [返回旺币信息]
     */
    public function getWbInfo($nodeId = "") {
        if (empty($nodeId)) {
            $nodeId = I('post.nodeid');
        }
        $nodeInfo = M('tnode_info')->getByNode_id($nodeId);
        // 查询商户账户余额和流量
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 商户余额流浪报文参数
        $req_array = array(
            'QueryShopInfoReq' => array(
                'SystemID' => C('YZ_SYSTEM_ID'), 
                'NodeID' => $nodeId, 
                'TransactionID' => $TransactionID, 
                'ContractID' => str_pad($nodeInfo['contract_no'], 10, 0, 
                    STR_PAD_LEFT)));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $AccountInfo = $RemoteRequest->requestYzServ($req_array);
        
        if (! $AccountInfo || ($AccountInfo['Status']['StatusCode'] != '0000' &&
             $AccountInfo['Status']['StatusCode'] != '0001')) {
            $AccountInfo = array();
        }
        return $AccountInfo['WbPrice'];
    }

    /**
     * [getAccountInfo 获取账户信息]
     *
     * @return [type] [返回旺币信息]
     */
    public function getAccountInfo($nodeId = "") {
        if (empty($nodeId)) {
            $nodeId = I('post.nodeid');
        }
        $nodeInfo = M('tnode_info')->getByNode_id($nodeId);
        // 查询商户账户余额和流量
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 商户余额流浪报文参数
        $req_array = array(
            'QueryShopInfoReq' => array(
                'SystemID' => C('YZ_SYSTEM_ID'), 
                'NodeID' => $nodeId, 
                'TransactionID' => $TransactionID, 
                'ContractID' => str_pad($nodeInfo['contract_no'], 10, 0, 
                    STR_PAD_LEFT)));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $AccountInfo = $RemoteRequest->requestYzServ($req_array);
        if (! $AccountInfo || ($AccountInfo['Status']['StatusCode'] != '0000' &&
             $AccountInfo['Status']['StatusCode'] != '0001')) {
            $AccountInfo = array();
        }
        echo $AccountInfo['AccountPrice'] . '-' . $AccountInfo['WbPrice'];
    }
}


