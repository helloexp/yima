<?php

class TmpFromWangyanAction extends Action {

    public $_authAccessMap = "*";

    public $_debug;

    public function __construct() {
        parent::__construct();
        ini_set("max_execution_time", "1800");
    }
    public function test(){

    }
    public function index() {
        $nodeIdArr = M()->table("twfx_node_info a")->field(
            "b.node_id,b.wc_version,b.pay_module")
            ->join('tnode_info b ON a.node_id=b.node_id')
            ->where('a.server_status=1')
            ->select();
        foreach ($nodeIdArr as $k => $v) {
            if ($v['wc_version'] != 'v4') {
                $payModule = trim($v['pay_module'], ',');
                $payModule = explode(',', $payModule);
                foreach ($payModule as $kk => $vv) {
                    if ($vv == 'm3') {
                        unset($payModule[$kk]);
                    }
                }
                if (! empty($payModule)) {
                    
                    $payModule = implode(',', $payModule) . ',m3';
                } else {
                    $payModule = 'm3';
                }
                $wc_version = 'v9';
                M('tnode_info')->where(
                    array(
                        'node_id' => $v['node_id']))->save(
                    array(
                        'wc_version' => $wc_version, 
                        'pay_module' => $payModule));
            }
        }
        echo 'success';
    }

    public function index2() {
        $sql = 'UPDATE tnode_info SET wc_version = \'v9\' ,pay_module=\'m0,m1,m2\' WHERE wc_version=\'v2\'';
        $sql2 = 'UPDATE tnode_info SET wc_version = \'v9\' ,pay_module=\'m0,m1\' WHERE wc_version=\'v1\'';
        echo $sql2;
    }

    public function index3() {
        $info = M('tnode_info')->field(
            'node_id,wc_version,TRIM(BOTH "," FROM pay_module) AS pay_module')
            ->where(array(
            'wc_version' => 'v9'))
            ->select();
        echo "<pre>";
        print_r($info);
        echo "</pre>";
        exit();
        foreach ($info as $k => $v) {
            if ($v['wc_version'] == 'v9') {
                $a = explode(',', $v['pay_module']);
                if (! in_array('m0', $a)) {
                    $a = 'm0,' . implode(',', $a);
                }
                M('tnode_info')->where(
                    array(
                        'node_id' => $v['node_id']))->save(
                    array(
                        'pay_module' => $a));
            }
        }
    }

    public function symWcVersion() {
        $nodeCount = M('tnode_info')->count();
        $runCount = ceil($nodeCount / 1000);
        for ($i = 0; $i < $runCount; $i ++) {
            $nodeIdArray = M('tnode_info')->field('node_id,wc_version')
                ->limit(1000 * $i, 1000)
                ->select();
            foreach ($nodeIdArray as $k => $v) {
                $wcVersionFromYz = self::getWcInfo($v['node_id']);
                M('tnode_info')->where(
                    array(
                        'node_id' => $v['node_id']))->save(
                    array(
                        'wc_version' => $wcVersionFromYz));
            }
        }
        echo "脚本运行完毕！";
    }

    private function getMyAccountInfo($nodeId) {
        // 查询商户账户余额和流量
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
                                                                   // 商户余额流浪报文参数
        $req_array = array(
            'QueryShopInfoReq' => array(
                'SystemID' => C('YZ_SYSTEM_ID'), 
                'NodeID' => $nodeId, 
                'TransactionID' => $TransactionID, 
                'ContractID' => $this->contractId));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $AccountInfo = $RemoteRequest->requestYzServ($req_array);
        
        if (! $AccountInfo || ($AccountInfo['Status']['StatusCode'] != '0000' &&
             $AccountInfo['Status']['StatusCode'] != '0001')) {
            $AccountInfo = array();
        }
        return $AccountInfo;
    }

    private function getWcInfo($nodeId) {
        // 查询商户账户余额和流量
        $contractNo = M('tnode_info')->getFieldByNode_id($nodeId, 'contract_no');
        // 商户余额流浪报文参数
        $req_array = array(
            'QueryShopServ2Req' => array(
                'ContractID' => $contractNo));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $WcInfo = $RemoteRequest->requestYzServ($req_array);
        $YzEqualWcVersion = array(
            '3' => 'v1', 
            '4' => 'v2', 
            '5' => 'v3');
        if (! empty($WcInfo['CurrLvl'])) {
            return $YzEqualWcVersion[$WcInfo['CurrLvl']];
        } else {
            if (M('tnode_info')->getFieldByNode_id($nodeId, 'check_status') ==
                 '2') {
                return 'v0.5';
            } else {
                return 'v0';
            }
        }
    }

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
                'EndTime' => date('Ymd', strtotime('+1 year')), 
                'ReasonID' => 1, 
                'Amount' => 0.00, 
                'WbNumber' => $wbnumber, 
                'Remark' => "From soft"));
        $RemoteRequest = D('RemoteRequest', 'Service');
        $WcInfo = $RemoteRequest->requestYzServ($req_array);
        if ($WcInfo['Status']['StatusCode'] == '0000') {
            echo 1;
        } else {
            echo 2;
        }
    }

    public function getWbInfo() {
        $nodeId = I('post.nodeid');
        // 创建接口对象
        $RemoteRequest = D('RemoteRequest', 'Service');
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
        $clientId = M('tnode_info')->getFieldByNode_id($nodeId, 'client_id');
        // 商户服务信息报文参数
        $req_array = array(
            'QueryWbListReq' => array(
                'SystemID' => C('YZ_SYSTEM_ID'), 
                'TransactionID' => $TransactionID, 
                'ClientID' => $clientId));
        $nodeWbInfo = $RemoteRequest->requestYzServ($req_array);
        $nodeWbList = array();
        $nodeWbList['wbOver'] = '0';
        if (! empty($nodeWbInfo['WbList'])) {
            if (isset($nodeWbInfo['WbList'][0])) {
                $nodeWbList['list'] = $nodeWbInfo['WbList'];
            } else {
                $nodeWbList['list'][] = $nodeWbInfo['WbList'];
            }
        }
        if (! empty($nodeWbList['list'])) {
            foreach ($nodeWbList['list'] as $k => $v) {
                if (date('YmdHis') > $v['EndTime']) {
                    $nodeWbList['list'][$k]['WbListId'] .= '(已过期)';
                } elseif (date('YmdHis') < $v['BeingTime']) {
                    $nodeWbList['list'][$k]['WbListId'] .= '(未开始)';
                } elseif ($v['Status'] == '-1') {
                    $nodeWbList['list'][$k]['WbListId'] .= '(已失效)';
                } else {
                    $nodeWbList['wbOver'] += $v['WbCurBalance'];
                }
            }
        }
        unset($nodeWbList['list']);
        echo $nodeWbList['wbOver'];
    }
    // twfx_goods_config数据脚本
    public function goodsConfig() {
        $configInfo = M('twfx_goods_config')->select();
        foreach ($configInfo as $k => $v) {
            $bonusConfig = json_decode($v['bonus_config_json'], true);
            if (! empty($bonusConfig)) {
                $level1 = $bonusConfig['level_1']['nodes'] or $level1 = array();
                $level2 = $bonusConfig['level_2']['nodes'] or $level2 = array();
                $level3 = $bonusConfig['level_3']['nodes'] or $level3 = array();
                $level4 = $bonusConfig['level_4']['nodes'] or $level4 = array();
                $level5 = $bonusConfig['level_5']['nodes'] or $level5 = array();
                $bonusConfig['agency_config']['nodes'] = array_merge($level1, 
                    $level2, $level3, $level4, $level5);
                unset($bonusConfig['level_1']['nodes']);
                unset($bonusConfig['level_2']['nodes']);
                unset($bonusConfig['level_3']['nodes']);
                unset($bonusConfig['level_4']['nodes']);
                unset($bonusConfig['level_5']['nodes']);
            }
            $bonusConfigJson = json_encode($bonusConfig);
            M('twfx_goods_config')->where(
                array(
                    'id' => $v['id']))->save(
                array(
                    'bonus_config_json' => $bonusConfigJson));
        }
    }

    public function getBalance() {
        echo "<pre>";
        print_r(D('Ump', 'Service')->getBalance());
        echo "</pre>";
        exit();
    }

    public function getSettle() {
        $date = I('date');
        echo "<pre>";
        print_r(D('Ump', 'Service')->getSettle($date));
        echo "</pre>";
        exit();
    }

    public function getStatus() {
        $date = I('date');
        $orderId = I('order_id');
        echo "<pre>";
        print_r(D('Ump', 'Service')->getOrderStatus($orderId, $date));
        echo "</pre>";
        exit();
    }

    public function transfer() {
        $orderId = date('YmdHis') . mt_rand(10000, 99999);
        $config = array(
            'order_id' => $orderId, 
            'mer_date' => date('Ymd'), 
            'amount' => 0.01, 
            'recv_account_type' => '00', 
            'recv_bank_acc_pro' => '1', 
            'recv_account' => '6222600110048032689', 
            'recv_user_name' => '王严', 
            'recv_gate_id' => 'COMM', 
            'bank_brhname' => '上海市支行', 
            'purpose' => '测试付款18');
        M('ttransfer_trace')->add($config);
        exit();
    }

    public function getResult() {
        $result = $_REQUEST;
        error_log(implode($result), 3, "D:/u.txt");
        exit();
    }

    public function runpower() {
        M('tnode_info')->where(array(
            'wc_version' => 'v2'))->save(
            array(
                'wc_version' => 'v9', 
                'pay_module' => ',m1,m2,'));
        M('tnode_info')->where(array(
            'wc_version' => 'v1'))->save(
            array(
                'wc_version' => 'v9', 
                'pay_module' => ',m1,'));
    }
}
