<?php

class IndexAction extends Action {

    public function beforeCheckAuth() {
        $this->_authAccessMap = '*';
    }

    public function index() {
        if (IS_POST) {
            $userName = I('post.condition_search', null, 
                'trim,htmlspecialchars');
            if (! $userName) {
                $this->error('条件不能为空！');
            }
            $map = array(
                'b.user_name' => array(
                    'like', 
                    '%' . $userName . '%'));
            $list = M()->table("tnode_info a")->field(
                'b.user_name,a.node_id,a.wc_version,a.add_time')
                ->join('tuser_info b ON b.node_id=a.node_id')
                ->where($map)
                ->select();
            if (! $list) {
                $this->error('没有查找到该账号！');
            }
            foreach ($list as $k => $v) {
                if (strlen($v['user_name']) > 16) {
                    $list[$k]['user_name'] = substr($v['user_name'], 0, 17);
                }
                $list[$k]['add_time'] = date('Y-m-d H:i', 
                    strtotime($v['add_time']));
            }
            $this->success($list);
        }
        $this->display();
    }

    public function getNearAccount() {
        $list = M()->table("tnode_info a")->field(
            'a.contact_eml as user_name,a.node_id,a.wc_version,a.add_time')
            ->order('a.add_time desc')
            ->limit('100')
            ->select();
        if (! $list) {
            $this->error('没有查找到该账号！');
        }
        foreach ($list as $k => $v) {
            if (strlen($v['user_name']) > 16) {
                $list[$k]['user_name'] = substr($v['user_name'], 0, 17);
            }
            $list[$k]['add_time'] = date('Y-m-d H:i', strtotime($v['add_time']));
        }
        $this->success($list);
    }

    public function looknode() {
        $nodeId = I('post.nodeId');
        $nodeInfo = get_node_info($nodeId);
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
        $nodeInfo['client_id'] = str_pad($nodeInfo['client_id'], 6, "0", 
            STR_PAD_LEFT);
        $nodeInfo['wb'] = $AccountInfo['WbPrice'];
        $nodeInfo['account'] = $AccountInfo['AccountPrice'];
        $this->error("", "", $nodeInfo);
    }

    public function modWc() {
        $nodeId = I('post.node_id');
        $wc_version = I('post.wc_version_sel');
        $pay_module = I('post.pay_module_sel');
        if ($wc_version == 'v9' && empty($pay_module)) {
            $this->error('付费版必须选择付费模块');
        } elseif ($wc_version != 'v9' && ! empty($pay_module)) {
            $this->error('非付费版，不能选择付费模块');
        } else {
            if ($wc_version == 'v9') {
                if (! in_array('m0', $pay_module)) {
                    $this->error('付费版必须选择旺财基础平台');
                }
                $nRet = M('tnode_info')->where(
                    array(
                        'node_id' => $nodeId))->save(
                    array(
                        'wc_version' => $wc_version, 
                        'pay_type'   => '1',
                        'node_type'  => '1',
                        'pay_module' => implode(',', $pay_module)));
            } else {
                $node_type = 2;
                if($wc_version == 'v4')
                {
                    $node_type = '4';
                }
                $nRet = M('tnode_info')->where(
                    array(
                        'node_id' => $nodeId))->save(
                    array(
                        'wc_version' => $wc_version, 
                        'node_type'  => $node_type, 
                        'pay_module' => ''));
            }
            
            if ($nRet === false) {
                $this->error('修改失败');
            } else {
                $this->success('修改成功', '', 
                    array(
                        'node_id' => $nodeId));
            }
        }
    }
    public function modDuomi(){
        if(IS_POST){
            $nodeId = I('post.nodeId');
            $sale_flag = get_node_info($nodeId,'sale_flag');
            $ret = M('tnode_info')->where(array('node_id'=>$nodeId))->save(array('sale_flag'=>($sale_flag == '1'?0:1)));
            if($ret === false){
                $this->error('修改失败');
            }else{
                $this->success('修改成功');
            }
        }
    }
}
