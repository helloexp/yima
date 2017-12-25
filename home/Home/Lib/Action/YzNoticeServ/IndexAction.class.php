<?php

/* 主动通知接口 */
class IndexAction extends Action {

    public $ReqArr;

    public $transType;

    public $responseType;

    private $reqXml;

    private $reqArr;

    private $transId;

    private $xml;
    
    // 记录日志
    protected function _log($msg, $level = Log::INFO) {
        Log::write($msg, $level);
    }
    
    // 返回
    public function _response($code, $text = '', $respName = 'SyncJsIDRes') {
        $arr = array(
            $respName => array(
                'Status' => array(
                    'StatusCode' => $code, 
                    'StatusText' => $text), 
                'TransactionID' => $this->transId));
        $str = $this->xml->getXMLFromArray($arr, 'gbk');
        $this->_log($str, 'RESPONSE');
        die($str);
    }

    public function index() {
        C('LOG_PATH', C('yz_notice.LOG_PATH')); // 重新定义目志目录
        
        import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
        $this->xml = new Xml();
        
        $this->reqXml = I('post.xml', '', 'trim,urldecode');
        $this->mac = I('post.mac', '', 'trim,strtolower');
        $this->_log(file_get_contents('php://input'), 'REQUEST');
        
        $this->_log(print_r($_POST, TRUE), 'POST');
        $this->_log(print_r($_GET, TRUE), 'GET');
        
        $reqArr = $this->xml->parse($this->reqXml);
        $this->reqType = $this->xml->getRootName();
        $this->reqArr = $this->xml->getArrayNoRoot();
        iconv_arr('gbk', 'utf-8', $this->reqArr);
        
        $respName = 'SyncJsIDRes';
        if ($this->reqType == 'SyncOrderStatusReq') {
            $respName = 'SyncOrderStatusRes';
        }
        
        if ($this->reqXml == '' || $this->mac == '' || strlen($this->mac) != 32)
            $this->_response('9999', '参数不完整', $respName);
        
        $mackey = C('YZ_MAC_KEY');
        if (md5($mackey . $this->reqXml . $mackey) != $this->mac) {
            $this->_log($mackey);
            $this->_log($this->reqXml);
            $this->_log($this->mac);
            $this->_response('9001', '签名不通过', $respName);
        }
        
        $this->transId = $this->reqArr['TransactionID'];
        $this->_log(print_r($this->reqArr, true), 'REQUEST');
        
        if (! method_exists(__CLASS__, '_' . $this->reqType))
            $this->_response('9002', '错误的请求类型');
        $this->{'_' . $this->reqType}();
    }

    /**
     * 根据order_number更改tactivity_order的pay_status
     */
    private function _SyncOrderStatusReq() {
        $r = $this->reqArr;
        $map = array(
            'order_number' => $r['TransactionID']);
        // 2表示现充现扣
        $result = D('Order')->changeOrderPayStatus($r['TransactionID'], '2');
        $this->_response($result['resp_id'], $result['resp_text'], 
            'SyncOrderStatusRes');
    }

    /**
     * 根据结算号更新客户的付费标志、企业认证信息、支付宝开通信息。。。
     */
    private function _SyncJsIDReq() {
        $r = $this->reqArr;
        log_write('营帐通知旺财付费标志、认证等信息：' . json_encode($r));
        $map = array(
            'contract_no' => $r['ContractID']);
        $count = M('tnode_info')->where($map)->count();
        if ($count === 0) {
            $this->_response('0000', 'not found valid data!');
        }
        
        $uData = array();
        // 认证审核状态
        if (empty($r['CertifDate'])) {
            $status = '0'; // 未审核
        } elseif ($r['CertifDate'] == '0000/00/00') {
            $status = '3'; // 审核中
        } elseif ($r['CertifDate'] == '9999/99/99') {
            $status = '1'; // 审核拒绝
        } else {
            $status = '2'; // 审核通过
        }
        $uData['check_status'] = $status;
        
        $uData['pay_type'] = $r['PayType'];
        $uData['yzxt_client_type'] = $r['ClientType'];
        // $r['ShopServ2_flag'],-3黑名且欠费,-2黑名单 -1预付费客户欠费 0免费版 1付费 2演示版
        if ($r['ShopServ2_flag'] == '1') {
            $ShopServ2_lvl = explode(',', trim($r['ShopServ2_lvl'], ','));
        } else {
            $ShopServ2_lvl = array();
        }
        $uData['yzxt_client_level'] = $ShopServ2_lvl;
        
        if (! empty($r['ClientShortName']))
            $uData['node_short_name'] = $r['ClientShortName'];
        if (! empty($r['ClientName']))
            $uData['node_name'] = $r['ClientName'];
        if (! empty($r['CityId']))
            $uData['node_citycode'] = $r['CityId'];
        if (! empty($r['Client_Addr']) || ! empty($r['ProvinceName']) ||
             ! empty($r['CityName']))
            $uData['node_addr'] = $r['ProvinceName'] . $r['Client_Addr'] .
             $r['CityName'];
        // 多米收单，结束时间，如果为空表示没开通，有值就是开通
        if (! empty($r['ShopServ2_dm_end']) && $r['ShopServ2_dm_end'] >= date('Ymd')){
            $uData['sale_flag'] = 1;
        }else{
            $uData['sale_flag'] = 0;
        }
        if (! empty($r['IndustryID']))
            $uData['trade_type'] = $r['IndustryID'];
        if (! empty($r['UserName']))
            $uData['contact_name'] = $r['UserName'];
        if (! empty($r['UserTel']))
            $uData['contact_phone'] = $r['UserTel'];
        if (! empty($r['ServiceTel']))
            $uData['node_service_hotline'] = $r['ServiceTel'];
        // 未知字段用途，暂存备注中
        $memo = array();
        if (! empty($r['IndustryName']))
            $memo['IndustryName'] = $r['IndustryName'];
        if (! empty($r['JsPrincipal']))
            $memo['JsPrincipal'] = $r['JsPrincipal'];
        if (! empty($r['EmplNo']))
            $memo['EmplNo'] = $r['EmplNo'];
        if (! empty($r['CurLevel']))
            $memo['CurLevel'] = $r['CurLevel'];
        if (! empty($r['ServFirstTime']))
            $memo['ServFirstTime'] = $r['ServFirstTime'];
        if (! empty($r['FirstQyTime']))
            $memo['FirstQyTime'] = $r['FirstQyTime'];
        $uData['memo'] = json_encode($memo);
    
    //sale_flag 标志给多米收单使用，原电商标志已无用
	//$uData['sale_flag'] = in_array('3003', $ShopServ2_lvl) ? '2' : '0';
    
    // 只有为1时，ShopServ2_lvl此字段才有效
    // node_type和status_tips更新 欠费,黑名单只做提醒,不改变商户其他信息
    switch ($r['ShopServ2_flag']) {
        case '-3':
            $uData = array(); // 之所以初始化，因为黑名单且欠费用户，不用更新
            $uData['status_tips'] = '3'; // 欠费且黑名单
            break;
        case '-2':
            $uData = array();
            $uData['status_tips'] = '2'; // 黑名单
            break;
        case '-1':
            $uData = array();
            $uData['status_tips'] = '1'; // 欠费
            break;
        case '0':
            $uData['status_tips'] = '0'; // 正常，不用提醒
            $uData['node_type'] = '2'; // 免费版，注册版
            break;
        case '1':
            $uData['status_tips'] = '0'; // 正常，不用提醒
            $uData['node_type'] = '1'; // 付费版，预付费
            break;
        case '2':
            $uData['status_tips'] = '0'; // 正常，不用提醒
            $uData['node_type'] = '4'; // 演示版，预付费
            break;
        default:
            $this->_response('0000', 
                "Invalid ShopServ2_flag value {$uData['node_type']}!");
    }
    // 获取商户版本,欠费,黑名单除外
    if (! is_null($uData['node_type'])) {
        $param = array(
            'node_type' => $uData['node_type'], 
            'yzxt_client_level' => $ShopServ2_lvl, 
            'node_check_status' => $status);
        $arrRes = $this->get_wc_version($param, $map);
        $uData['wc_version'] = $arrRes['wc_version'];
        $uData['pay_module'] = $arrRes['pay_module'];
        $uData['wc_version_endtime'] = $r['ServEndTime'];
    }
    
    $list = M('tnode_info')->where($map)->select();
    foreach ($list as $info) {
        $node_map = "node_id = '{$info['node_id']}'";
        $uData2 = $uData;
        if (in_array($info['node_type'], 
            array(
                '0', 
                '1', 
                '2'))) { // 翼码和演示客户的不做更新
            if($info['sale_flag'] == '0' && $uData['sale_flag'] == '1')
            {
                $_beginDate = date('Ym01', strtotime(date("Ymd")));
                $_endtime = date('Ymd',
                        strtotime("$_beginDate +2 month -1 day")) . '235959';
                $_count = M('tnode_charge')->where(
                    array(
                        'charge_id' => '3091',
                        'charge_level' => 1,
                        'node_id' => $info['node_id']))->count();
                if ($_count < 1) {
                    $flag1 = M('tnode_charge')->add(
                        array(
                            'status' => 0,
                            'charge_id' => '3091',
                            'charge_level' => 1,
                            'node_id' => $info['node_id'],
                            'begin_time' => date('YmdHis'),
                            'end_time' => $_endtime));
                }

                if ($flag1 === false) {
                    $this->_log(
                        "update tnode_charge fail![" . M()->_sql() . "]",
                        'ERROR');
                } else {
                    $this->_log(
                        "update tnode_charge success! map: " .
                        print_r(
                            array(
                                'charge_id' => '3091',
                                'charge_level' => 1,
                                'node_id' => $info['node_id']), true) .
                        " data:" . print_r(
                            array(
                                'status' => 0,
                                'charge_id' => '3091',
                                'charge_level' => 1,
                                'node_id' => $info['node_id'],
                                'begin_time' => date('YmdHis'),
                                'end_time' => $_endtime), true));
                }
            }
            $flag = M('tnode_info')->where($node_map)->save($uData2);
            if ($flag === false) {
                $this->_log("update tnode_info fail![" . M()->_sql() . "]", 
                    'ERROR');
            } else {
                $this->_log(
                    "update tnode_info success! node_id: {$info['node_id']} data:" .
                         print_r($uData2, true));
            }
        }
        
        // 支付宝,微信等信息更新
        if (! empty($r['PayInfo'])) {
            if (isset($r['PayInfo'][0])) { // 多条
                foreach ($r['PayInfo'] as $v) {
                    if ($v['Type'] == '3')
                        $v['Type'] = '5';
                    $data = array(
                        'status' => $v['Status'], 
                        'fee_rate' => $v['Rate'], 
                        'zfb_account' => $v['UserId'], 
                        'open_time' => $v['AddTime'], 
                        'account_pid' => $v['PId']);
                    if ($v['Type'] == '6'){
                        $data['auth_flag'] = '2';
                        $v['Type'] = '0';
                    }
                    $map1 = array(
                        'node_id' => $info['node_id'], 
                        'pay_type' => $v['Type']);
                    if ($v['Status'] == 1) { // 付满送模块有效期
                        $result = M('tzfb_offline_pay_info')->field(
                            'contact_phone,node_name')
                            ->where(
                            array(
                                'status' => array(
                                    'in', 
                                    '2,3')))
                            ->where($map1)
                            ->find();
                        
                        if (! empty($result)) {
                            if ($v['Type'] == '0')
                                $typestr = '支付宝';
                            else if ($v['Type'] == '1')
                                $typestr = '微信';
                            else if ($v['Type'] == '2')
                                $typestr = '翼支付';
                            else if ($v['Type'] == '4')
                                $typestr = '通联支付';
                            else if ($v['Type'] == '5')
                                $typestr = 'QQ支付';
                            $content = "尊敬的" . $result['node_name'] . "，您申请的 " .
                                 $typestr .
                                 " 条码支付已完成开通；如需申请终端，可登录旺财平台在线申请；如有疑问，可联系客服：4008807005";
                            
                            $rs = send_SMS('00000243', $result['contact_phone'], 
                                $content, '13101622783');
                            
                            // send_SMS('00000830', $result['contact_phone'],
                            // $content,'13120597564');
                        }
                    }
                    $flag = M('tzfb_offline_pay_info')->where($map1)->save(
                        $data);
                    if ($flag === false) {
                        $this->_log(
                            "update tzfb_offline_pay_info fail![" . M()->_sql() .
                                 "]", 'ERROR');
                    } else {
                        $this->_log(
                            "update tzfb_offline_pay_info success! map: " .
                                 print_r($map1, true) . " data:" .
                                 print_r($data, true));
                    }
                }
            } else { // 单条
                if ($r['PayInfo']['Type'] == '3')
                    $r['PayInfo']['Type'] = '5';
                $data = array(
                    'status' => $r['PayInfo']['Status'], 
                    'fee_rate' => $r['PayInfo']['Rate'], 
                    'zfb_account' => $r['PayInfo']['UserId'], 
                    'open_time' => $r['PayInfo']['AddTime'], 
                    'account_pid' => $r['PayInfo']['PId']);
                if ($r['PayInfo']['Type'] == '6'){
                    $data['auth_flag'] = '2';
                    $r['PayInfo']['Type'] = '0';
                }
                $map1 = array(
                    'node_id' => $info['node_id'], 
                    'pay_type' => $r['PayInfo']['Type']);
                if ($r['PayInfo']['Status'] == 1) { // 付满送模块有效期
                    $result = M('tzfb_offline_pay_info')->field(
                        'contact_phone,node_name')
                        ->where(
                        array(
                            'status' => array(
                                'in', 
                                '2,3')))
                        ->where($map1)
                        ->find();
                    
                    if (! empty($result)) {
                        if ($r['PayInfo']['Type'] == '0')
                            $typestr = '支付宝';
                        else if ($r['PayInfo']['Type'] == '1')
                            $typestr = '微信';
                        else if ($r['PayInfo']['Type'] == '2')
                            $typestr = '翼支付';
                        else if ($r['PayInfo']['Type'] == '4')
                            $typestr = '通联支付';
                        else if ($r['PayInfo']['Type'] == '5')
                            $typestr = 'QQ支付';
                        $content = "尊敬的" . $result['node_name'] . "，您申请的 " .
                             $typestr .
                             " 条码支付已完成开通；如需申请终端，可登录旺财平台在线申请；如有疑问，可联系客服：4008807005";
                        $rs = send_SMS('00000243', $result['contact_phone'], 
                            $content, '13101622783');
                        
                        // send_SMS('00000830', $result['contact_phone'],
                        // $content,'13120597564');
                    }
                }
                $flag = M('tzfb_offline_pay_info')->where($map1)->save($data);
                if ($flag === false) {
                    $this->_log(
                        "update tzfb_offline_pay_info fail![" . M()->_sql() . "]", 
                        'ERROR');
                } else {
                    $this->_log(
                        "update tzfb_offline_pay_info success! map: " .
                             print_r($map1, true) . " data:" .
                             print_r($data, true));
                }
            }
        }
        //#18044 将营帐系统部分收费项的资费同步给旺财等 start
        //处理（1）新申请的EPOS、ER6800的终端月租费（整月）；
        //（2）发送自用卡券、异业卡券、微信卡券的发码费（单条）；
        log_write('before synPrice:' . json_encode($r));
        import('@.Vendor.CommonConst') or die('include file fail.');
        foreach ($r as $key => $val) {
            if (in_array($key, array(
                    'EPOSPrice', 
                    'ER6800Price', 
                    'ZiYongPrice', 
                    'WeiXinPrice', 
                    'YiYePrice', 
                    'ZiYongDMPrice', 
                    'WeiXinDMPrice'
            ))) {
                $chargeId = '';//营长系统收费id（旺财平台填写在charge_info表里）
                switch ($key) {
                    case 'EPOSPrice':
                        $chargeId = CommonConst::CHARGE_ID_EPOS;
                    break;
                    case 'ER6800Price':
                        $chargeId = CommonConst::CHARGE_ID_E6800;
                    break;
                    case 'ZiYongPrice':
                        $chargeId = CommonConst::CHARGE_ID_ZIYONG;
                    break;
                    case 'WeiXinPrice':
                        $chargeId = CommonConst::CHARGE_ID_WEIXIN;
                    break;
                    case 'YiYePrice':
                        $chargeId = CommonConst::CHARGE_ID_YIYE;
                    break;
                    case 'ZiYongDMPrice':
                    $chargeId = CommonConst::CHARGE_ID_ZIYONG_DM;
                    break;
                    case 'WeiXinDMPrice':
                    $chargeId = CommonConst::CHARGE_ID_WEIXIN_DM;
                    break;
                }
                $chargeInfo = M('tcharge_info')->where(array('charge_id' => $chargeId))->find();
                $configData = array(
                    'node_id' => $info['node_id'],
                    'charge_id' => $chargeId, 
                    'name' => $chargeInfo['charge_name'], 
                    'price' => $val, 
                    'model' => '', 
                    'remark' => '营账同步的价格，时间：' . date('Y-m-d H:i:s', time())
                );
                $activityPayConfigModel = M('tactivity_pay_config');
                $hasConfig = $activityPayConfigModel->where(array('node_id' => $info['node_id'], 'charge_id' => $chargeId))->find();
                if (!$hasConfig) {
                    $synPayRes = $activityPayConfigModel->add($configData);
                } else {
                    $synPayRes = $activityPayConfigModel->where(array('id' => $hasConfig['id']))->save($configData);
                }
                
                if (false === $synPayRes) {
                    log_write('同步价格到旺财出错，node_id:' .  $info['node_id'] . '.price:' . $val . '.charge_id:' . $chargeId);
                }
            }
        }
        //#18044 将营帐系统部分收费项的资费同步给旺财等 end
    }
    
    
    
    log_write('旺财将会更新此商户' . $list[0]['node_id'] . '信息' . print_r($uData, true));
    $this->_response('0000', 
        '旺财将会更新此商户' . $list[0]['node_id'] . '信息' . print_r($uData, true));
}

// 计算旺财版本
private function get_wc_version($param, $map) {
    $node_type = $param['node_type'];
    $yzxt_client_level = $param['yzxt_client_level'];
    $node_check_status = $param['node_check_status'];
    
    $res = array();
    if ($node_type == '1') {
        // 签约用户，即付费用户
        $res['wc_version'] = 'v9';
        $res['pay_module'] = '';
        if (in_array('3050', $yzxt_client_level)) {
            $res['pay_module'] .= ',m0';
        }
        if (in_array('3004', $yzxt_client_level)) {
            $res['pay_module'] .= ',m1';
        }
        if (in_array('3003', $yzxt_client_level)) {
            $res['pay_module'] .= ',m2';
        }
        if (in_array('3011', $yzxt_client_level)) {
            $res['pay_module'] .= ',m2,m3';
        }
        if (in_array('3012', $yzxt_client_level)) {
            $res['pay_module'] .= ',m4';
        }
    } elseif ($node_type == '2') {
        // 注册用户，即免费用户
        if ($node_check_status == '2') {
            $res['wc_version'] = 'v0.5';
            $res['pay_module'] = '';
        } else {
            $res['wc_version'] = 'v0';
            $res['pay_module'] = '';
        }
    } else {
        $res['wc_version'] = 'v4';
        $res['pay_module'] = '';
    }
    
    return $res;
}
// 测试接口用，不用提交到生产环境
public function test() {
    $data = array(
        'SyncOrderStatusReq' => array(
            'TransactionID' => '20150824160845885434'));
    $url = 'http://test.wangcaio2o.com/index.php?g=YzNoticeServ&m=Index&a=index';
    $mac_key = C('YZ_MAC_KEY') or die('[YZ_MAC_KEY]参数未设置');
    $timeout = C('YZ_REQ_TIME') or $timeout = 30; // 超时秒数
    import('@.ORG.Util.Xml') or die('@.ORG.Util.Xml导入失败');
    $xml = new Xml();
    $str = $xml->getXMLFromArray($data, 'gbk');
    $mac_str = md5($mac_key . $str . $mac_key);
    $sendStr = http_build_query(
        array(
            'xml' => $str, 
            'mac' => $mac_str));
    $error = '';
    $result_xml = httpPost($url, $sendStr, $error, 
        array(
            'TIMEOUT' => $timeout));
    if (! $result_xml) {
        return array(
            'Status' => array(
                'StatusCode' => '-1', 
                'StatusText' => 'XML is null'));
    }
    $xml = new Xml();
    $result_xml = get_magic_quotes_gpc() ? stripslashes($result_xml) : $result_xml;
    
    $arr = $xml->parse($result_xml);
    $arr = $xml->getArrayNoRoot();
    if ($xml->error()) {
        return array(
            'Status' => array(
                'StatusCode' => '-1', 
                'StatusText' => $xml->error()));
    }
    // 转换成 utf-8 编码
    array_walk_recursive($arr, 'utf8Str');
    dump($arr);
}
}