<?php

/**
 * 号码百事通非标
 * author wangsong
 * connect skyshappiness@gmail.com or 454448499@qq.com
 */
class Fj114Service {
    private $shopname;
    public function __construct() {
        
    }

    /**
     * 检查字段是否为空或校验手机格式
     * @param type $checkVariable
     * @param type $configArray
     * @return boolean 
     */
    public function checkNotNull($checkVariable, $name, $configArray = NULL) {
        switch ($checkVariable) {
            case '0':
                $result = array();
                $result['resultCode'] = (int) 2001;
                $result['resultTxt'] = 'missing ' . $name;
                $resultString = json_encode($result);
                echo $resultString;
                exit;
                break;
            default:
                if ($name == 'telephone' || $name == 'isdn') {
                    if (!check_str($checkVariable, array('maxlen' => '20', 'minlen'=>'1'))) {
                        $result = array();
                        $result['resultCode'] = (int) 2002;
                        $result['resultTxt'] = 'Wrong mobile format';
                        $resultString = json_encode($result);
                        echo $resultString;
                        exit;
                    }
                }elseif($name == 'package'){
                    if(!in_array($checkVariable, $configArray)){
                        echo json_encode(array('resultCode'=>(int) 2003, 'resultTxt'=>'套餐类型不正确'));
                        exit;
                    }
                }
                return TRUE;
                break;
        }
    }

    /**
     * 创建 md5 秘钥
     *
     * @param string $strOne
     * @param string $strTwo
     * @param string $strThree
     * @param string $strFour
     * @param string $strFive
     * @param string $strSix
     *
     * @return string
     */
    public function createKey($strOne, $strTwo, $strThree, $strFour = null, $strFive = null, $strSix = null) {
        $resultStr = $strOne . $strTwo . $strThree;
        if ($strFour != NULL) {
            $resultStr .= $strFour;
        }
        if ($strFive != NULL) {
            $resultStr .= $strFive;
        }
        if ($strSix != NULL) {
            $resultStr .= $strSix;
        }
        $key = md5($resultStr);
        return $key;
    }

    /**
     * 校验 key 值
     * @param string $getKey
     * @param string $createKey
     */
    public function checkKey($getKey, $createKey) {
        if ($getKey !== $createKey) {
            $result = array();
            $result['resultCode'] = (int) 2003;
            $result['resultTxt'] = 'different key';
            //上线必须去除
            $result['createKey'] = $createKey;
            $resultString = json_encode($result);
            echo $resultString;
            exit;
        } else {
            return TRUE;
        }
    }

    /**
     * 店铺数据处理
     * @param string $nodeId
     * @param string $telephone
     * @param string $ShopsRegion
     * @param string $ShopsAddr
     * @param string $package
     * @param string $storeId
     * @param string $type
     */
    public function saveData($nodeId, $telephone, $ShopsName, $ShopsRegion, $ShopsAddr, $package, $storeId, $type = 'new') {
        $storeInfoData = array();
        $storeInfoData['node_id'] = $nodeId;
        $storeInfoData['store_name'] = $ShopsName;
        $storeInfoData['store_short_name'] = $ShopsName;
        $storeInfoData['city_code'] = $ShopsRegion;
        $storeInfoData['address'] = $ShopsAddr;
        $storeInfoData['principal_phone'] = $telephone;
        $storeInfoData['type'] = '2';

        $saveData = array();
        $saveData['package'] = $package;
        $saveData['status'] = '0';

        M()->startTrans();
        if ($type == 'new') {
            $storeInfoData['principal_email'] = $telephone . '@guaji.com';
            $storeInfoData['store_id'] = $storeId;
            $storeInfoData['add_time'] = date('YmdHis');
            $storeInfoData['status'] = '0';
            M('tstore_info')->where(array('store_id' => $storeId))->delete();
            $newStoreId = M('tstore_info')->add($storeInfoData);
            if ($newStoreId) {
                $saveData['phone_no'] = $telephone;
                $saveData['add_time'] = date('YmdHis');
                $saveData['store_id'] = $storeId;
                $newShopId = M('tfb_fjguaji_shop_info')->add($saveData);
                if ($newShopId) {
                    //创建Epos终端
                    $createEposResult = $this->createFreeEpos($storeId, $nodeId);
                    
                    //创建默认卡券
                    $createGroupResult = $this->createTerminalGroup($nodeId, $createEposResult, $storeId, $telephone);
                    if(is_array($createGroupResult)){
                        echo json_encode($createGroupResult);
                        exit;
                    }else{
                        $cardName = $ShopsName.'优惠券';
                        $goodImage = '00004488/2016/01/11/569358f5d925e.jpg';
                        $printText = '感谢您致电【'.$ShopsName.'】，您的满意就是我们的追求，凭本券到店消费享受额外惊喜，数量有限先到先得';
                        $cardInfo = array('verify_end_time'=>365,'notice'=>$printText, 'cardType'=>'vouchers');
                        $createCardResult = $this->createContract($createGroupResult, $cardName, $goodImage, $printText, $cardInfo, $nodeId, $telephone, 'new', $newShopId);
                        echo json_encode($createCardResult);
                    }
                    M()->commit();
                    exit;
                }
            }

            M()->rollback();
            $result['resultCode'] = (int) 4001;
            $result['resultTxt'] = 'cannot create a shop';
            echo json_encode($result);
            exit;
        } else {
            $storeInfoData['update_time'] = date('YmdHis');
            $newStoreId = M('tstore_info')->where(array('store_id' => $storeId))->save($storeInfoData);
            if ($newStoreId != FALSE) {
                $newShopId = M('tfb_fjguaji_shop_info')->where(array('phone_no' => $telephone))->save($saveData);
                if ($newShopId !== FALSE) {
                    M()->commit();
                    $result['resultCode'] = (int) 0;
                    $result['resultTxt'] = 'success';
                    echo json_encode($result);
                    exit;
                }
            }

            M()->rollback();
            $result['resultCode'] = (int) 4002;
            $result['resultTxt'] = 'Modify failed';
            echo json_encode($result);
            exit;
        }
    }
    
    /**
     * 创建免费终端
     * @return boolean
     */
    public function createFreeEpos($storeId, $nodeId) {
        $shopInfo = M('tstore_info')->where(array('store_id' => $storeId))->field('store_short_name,pos_count')->find();
        $pos_name = $shopInfo['store_short_name'];
        $req_arr = array();
        $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
        $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
        $req_arr['ISSPID'] = $nodeId;
        $req_arr['StoreID'] = $storeId;
        $req_arr['PosGroupID'] = '';
        $req_arr['PosFlag'] = 2;
        $req_arr['PosType'] = 3;
        $req_arr['PosName'] = $pos_name;
        $req_arr['PosShortName'] = $pos_name;
        $req_result = D('RemoteRequest', 'Service')->requestIssServ(array('PosCreateReq' => $req_arr));
        log_write(print_r($req_result, TRUE));
        $respStatus = isset($req_result['PosCreateRes']) ? $req_result['PosCreateRes']['Status'] : $req_result;
        
        if ($respStatus['StatusCode'] != '0000') {
            echo json_encode(array('resultCode' => '1003', 'resultTxt' => $respStatus['StatusText']));
            exit;
        }

        $respData = $req_result['PosCreateRes'];
        $pos_id = $respData['PosID'];

        // 支撑会同步，先删掉，再创建终端
        M('tpos_info')->where(array('pos_id' => $pos_id))->delete();
        $data = array(
            'pos_id' => $pos_id,
            'node_id' => $nodeId,
            'pos_name' => $pos_name,
            'pos_short_name' => $pos_name,
            'pos_serialno' => $pos_id,
            'store_id' => $storeId,
            'store_name' => $pos_name,
            'login_flag' => 0,
            'pos_type' => '2', // epos
            'is_activated' => 0,
            'pos_status' => 0,
            'add_time' => date('YmdHis'),
            'pay_type' => '1'); // 免费

        $result = M('tpos_info')->add($data);
        if (!$result) {
            log_write(print_r($data, true) . M()->getDbError(), 'DB ERROR');
            echo json_encode(array('resultCode' => '1003', 'resultTxt' => 'Epos终端创建失败'));
            exit;
        }

        // 增加默认门店的终端数
        M('tstore_info')->where(array('node_id' => $nodeId, 'store_id' => $storeId))->save(array('pos_range' => '2', 'pos_count' => $shopInfo['pos_count']+1));
        return $pos_id;
    }

    /**
     * 创建终端组
     * @param type $nodeId
     */
    public function createTerminalGroup($nodeId, $posId, $storeId, $phoneNo) {
        $result = array();
        $nodeInfo = M('tnode_info')
                ->field('node_name,client_id,node_service_hotline,posgroup_seq')
                ->where(array('node_id' => $nodeId))
                ->find();

        M()->startTrans();
        M('tnode_info')->where(array('node_id' => $nodeId))->setInc('posgroup_seq');
        $req_array = array(
            'CreatePosGroupReq' => array(
                'NodeId' => $nodeId,
                'GroupType' => '1',
                'GroupName' => str_pad($nodeInfo['client_id'], 6, '0', STR_PAD_LEFT) . $nodeInfo['posgroup_seq'],
                'GroupDesc' => '',
                'DataList' => $posId)
        );

        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['CreatePosGroupRes']['Status'];
        if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
            log_write("创建终端组失败，原因：{$ret_msg['StatusText']}");
            M('tfb_fjguaji_shop_info')->where(array('phone_no'=>$phoneNo))->delete();
            M()->rollback();
            $result['resultCode'] = '2001';
            $result['resultTxt'] = '创建终端组失败:' . $ret_msg['StatusText'];
            return $result;
        } else {
            $groupId = $resp_array['CreatePosGroupRes']['GroupID'];
            
            // 插入终端组信息
            $num = M('tpos_group')->where("group_id='{$groupId}' AND node_id='{$nodeId}'")->count();
            if ($num == '0') { // 不存在终端组去创建
                $groupData = array();
                $groupData['node_id'] = $nodeId;
                $groupData['group_id'] = $groupId;
                $groupData['group_name'] = $req_array['CreatePosGroupReq']['GroupName'];
                $groupData['group_type'] = '1';
                $groupData['status'] = '0';
                $result = M('tpos_group')->add($groupData);
                if (!$result) {
                    M('tfb_fjguaji_shop_info')->where(array('phone_no'=>$phoneNo))->delete();
                    M()->rollback();
                    $result['resultCode'] = '2002';
                    $result['resultTxt'] = '创建终端组失败';
                    return $result;
                } else {
                    M('tfb_fjguaji_shop_info')
                       ->where(array('phone_no' => $phoneNo))
                       ->save(array('pos_group' => $groupId));
                    $data_2 = array();
                    $data_2['group_id'] = $groupId;
                    $data_2['node_id'] = $nodeId;
                    $data_2['store_id'] = $storeId;
                    $data_2['pos_id'] = $posId;
                    $result = M('tgroup_pos_relation')->add($data_2);
                    if (!$result) {
                        M('tfb_fjguaji_shop_info')->where(array('phone_no'=>$phoneNo))->delete();
                        M()->rollback();
                        $result['resultCode'] = '2002';
                        $result['resultTxt'] = '创建终端组失败';
                        return $result;
                    }
                }
            }
            M()->commit();
            return $groupId;
        }
    }
	public function codeSendSetedtime($goodsId,$shopId){
		$id = M('tgoods_info')->field('id')->where(array('goods_id' => $goodsId))->find();
		$times = M('tfb_fjguaji_send_set')
                ->where(array('shop_id' => $shopId, 'is_delete'=>'0','g_id' => $id['id']))
                ->find();
			//echo	M("tfb_fjguaji_send_set")->getLastSql(); exit;
		return $times;		
	}
    /**
     * 创建合约
     */
    public function createContract($groupId, $cardName, $goodImage, $printText, $cardInfo, $nodeId, $phoneNo, $type = NULL, $shopId = NULL) {
        $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 流水号
        $req_array = array(
            'CreateTreatyReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'),
                'RequestSeq' => $TransactionID,
                'ShopNodeId' => $nodeId,
                'BussNodeId' => $nodeId,
                'TreatyName' => $cardName,
                'TreatyShortName' => $cardName,
                'StartTime' => date('YmdHis'),
                'EndTime' => '20301231235959',
                'GroupId' => $groupId,
                'GoodsName' => $cardName,
                'GoodsShortName' => $cardName,
                'SalePrice' => 0));

        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestIssForImageco($req_array);
        $ret_msg = $resp_array['CreateTreatyRes']['Status'];
        if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
            log_write("创建合约失败，原因：{$ret_msg['StatusText']}");
            $result['resultCode'] = '2003';
            $result['resultTxt'] = '创建合约失败:' . $ret_msg['StatusText'];
            return $result;
        }
        $treatyId = $resp_array['CreateTreatyRes']['TreatyId']; // 合约id
		$smilId = D('Goods')->getSmil($goodImage, $cardName, $nodeId);		
		
        if (!$smilId) {
            $result['resultCode'] = '2003';
            $result['resultTxt'] = '创建失败:' . D('Goods')->error;
            return $result;
        } else {
            // 创建活动
            $TransactionID = date("YmdHis") . mt_rand(100000, 999999); // 请求单号
            $req_array = array(
                'ActivityCreateReq' => array(
                    'SystemID' => C('ISS_SYSTEM_ID'),
                    'ISSPID' => $nodeId,
                    'RelationID' => $nodeId,
                    'TransactionID' => $TransactionID,
                    'SmilID' => '',
                    'ActivityInfo' => array(
                        'CustomNo' => '',
                        'ActivityName' => $cardName,
                        'ActivityShortName' => $cardName,
                        'BeginTime' => date('YmdHis'),
                        'EndTime' => '20301231235959',
                        'UseRangeID' => $groupId,
                        'SpecialTag' => ''),
                    'VerifyMode' => array(
                        'UseTimesLimit' => 1,
                        'UseAmtLimit' => 0),
                    'GoodsInfo' => array(
                        'pGoodsId' => $treatyId),
                    'DefaultParam' => array(
                        'PasswordTryTimes' => 3,
                        'PasswordType' => '',
                        'PrintText' => $printText)));

            $resp_array = $RemoteRequest->requestIssForImageco($req_array);
            $ret_msg = $resp_array['ActivityCreateRes']['Status'];
            log_write(print_r($resp_array, true));
            if (!$resp_array || ($ret_msg['StatusCode'] != '0000' && $ret_msg['StatusCode'] != '0001')) {
                $result['resultCode'] = '2003';
                $result['resultTxt'] = "活动创建失败:{$ret_msg['StatusText']}";
                return $result;
            }
            $batchNo = $resp_array['ActivityCreateRes']['Info']['ActivityID'];

            // tgoods_info数据添加
            $goodsId = get_goods_id();
            $data['goods_id'] = $goodsId;
            $data['batch_no'] = $batchNo;
            $data['node_id'] = $nodeId;
            $data['goods_type'] = 0;
            $data['storage_type'] = 0;
            $data['storage_num'] = -1;
            $data['remain_num'] = -1;
            $data['add_time'] = date('YmdHis');
            $data['p_goods_id'] = $treatyId;
            $data['pos_group'] = $groupId;
            $data['online_verify_flag'] = '0';
            $data['pos_group_type'] = '2';
            $data['print_text'] = $printText;
            $data['goods_name'] = $cardName;
            $data['goods_image'] = $goodImage;
            $data['config_data'] = json_encode($cardInfo);
//            M()->startTrans();
            $id = M('tgoods_info')->data($data)->add();
            if ($id) {
                log_write('goosid:'.$id);
                $goodsIdlist = D('Fj114')->getGoodsList($phoneNo);
                if($goodsIdlist == ''){
                    $goodsIdlist = (string) $id;
                }else{
                    $goodsIdlist.= ','.$id;
                }
                $saveResult = M('tfb_fjguaji_shop_info')
                    ->where(array('phone_no'=>$phoneNo))
                    ->data(array('goods_list'=>$goodsIdlist))
                    ->save();
                log_write('sql:'.M()->_sql());
                if($type == 'new'){
                    $defaultSendSendId = M('tfb_fjguaji_send_set')->add(array('shop_id'=> $shopId, 'g_id'=>$id, 'type'=>'2'));
                    log_write('lastSql:'.$defaultSendSendId);
                }
				
                return array('resultCode' => '0', 'resultTxt' => 'success', 'id'=> $id);
                exit;
            }
//            M()->rollback();
            return FALSE;
        }
    }
    
    /**
     * 删除创建失败数据
     * @param type $phone
     * @param type $nodeId
     * @param type $storeId
     */
    private function delCreatAccountFailedData($phone, $nodeId, $storeId = NULL){
        
    }

    /**
     * 校验存在验证终端
     * @param type $storeId
     * @param type $nodeId
     * @return type
     */
    public function checkTerminal($storeId, $nodeId) {
        $where = array();
        $where['s.store_id'] = $storeId;
        $where['s.pos_range'] = array('gt', '0');
        $where['s.node_id'] = $nodeId;

        $posId = M()->table('tstore_info s')
                ->join('tpos_info p ON s.store_id=p.store_id AND s.node_id=p.node_id')
                ->where($where)
                ->getfield('p.pos_id');

        return $posId;
    }

    /**
     * 校验发送优惠券设置日期
     * @param type $startTime
     * @param type $endTime
     */
    public function checkCodeDateRepeat($startTime, $endTime) {
        if ($startTime > $endTime) {
            return array('resultCode' => '1001', 'resultTxt' => '开始时间大于结束时间');
            exit;
        }
        
        $condition = array();
        $condition['shop_id'] = $_SESSION['Fj114']['id'];
        $condition['end_time'] = array('egt', $startTime);
        $condition['is_delete'] = '0';
        $isRepeat = M('tfb_fjguaji_send_set')->where($condition)->select();
        if(is_array($isRepeat)){
            foreach($isRepeat as $val){
                if($val['start_time'] < $endTime){
                    return array('resultCode' => '1002', 'resultTxt' => '设置日期内已存在优惠券活动');
                    exit;
                }
            }
        }
        return TRUE;
    }

    /**
     * 检查优惠券
     * @param type $goodsId
     */
    public function checkGoodId($goodsId, $phoneNo) {
        $goodsList = D('Fj114')->getGoodsList($phoneNo);
        $goodsIdArray = explode(',', $goodsList);
        if (in_array($goodsId,$goodsIdArray)) {
            return TRUE;
        } else {
            return array('resultCode' => '1001', 'resultTxt' => '请选择你的优惠券');
        }
    }

    /**
     * 创建发码数据
     * @param string $goodsId
     * @param string $nodeId
     * @return string
     */
    public function createMarketingId($goodsId, $nodeId) {
        $goodsData = M('tgoods_info')->where(array('id'=>$goodsId))->find();
        $goodsConfig = json_decode($goodsData['config_data'], TRUE);
        //校验是否已经存在
        $condition = array();
        $condition['tbi.node_id'] = $nodeId;
        $condition['tbi.goods_id'] = $goodsId;
        $condition['tmi.batch_type'] = '1007';

        $batchInfo =M()->table("tbatch_info tbi")
            ->where($condition)
            ->join('tmarketing_info tmi ON tmi.id=tmi.m_id')
            ->field('id, verify_end_date')->find();
        if(!empty($batchInfo)){
            if($batchInfo['verify_end_date'] != $goodsConfig['verify_end_time']){
                M('tbatch_info')->where(array('id'=>$batchInfo['id']))
                    ->save(array('verify_end_date'=>$goodsConfig['verify_end_time']));
            }
            return $batchInfo['id'];
        }else{
            // tmarketing_info插入数据 1007是发送到个人
            $market_data['name'] = $goodsData['goods_name'];
            $market_data['batch_type'] = '1007';
            $market_data['node_id'] = $nodeId;
            $market_data['start_time'] = date('YmdHis');
            $market_data['end_time'] = date('YmdHis', strtotime('+10 year'));
            $market_data['add_time'] = date('YmdHis');
            $m_id = M('tmarketing_info')->add($market_data);
            if (!$m_id) {
                return array('resultCode'=>'1001','resultTxt'=>'活动id创建失败');
            }

            // tbatch_info 插入数据
            $batch_info_data['batch_short_name'] = $goodsData['goods_name'];
            $batch_info_data['batch_no'] = $goodsData['batch_no'];
            $batch_info_data['node_id'] = $nodeId;
            $batch_info_data['batch_class'] = $goodsData['print_text'];
            $batch_info_data['use_rule'] = $goodsData['print_text'];
            $batch_info_data['batch_amt'] = $goodsData['goods_amt'];
            $batch_info_data['info_title'] = $goodsData['print_text'];
            $batch_info_data['print_text'] = $goodsData['print_text'];
            $batch_info_data['batch_desc'] = $goodsData['print_text'];
            $batch_info_data['begin_time'] = date('YmdHis');
            $batch_info_data['add_time'] = date('YmdHis');
            $batch_info_data['verify_begin_date'] = date('YmdHis');
            $dateStr = '+'.$goodsConfig['verify_end_time'].' days';
            $batch_info_data['verify_end_date'] = date('Ymd', strtotime($dateStr)).'235959';
            $batch_info_data['verify_begin_type'] = '0';
            $batch_info_data['verify_end_type'] = '0';
            $batch_info_data['end_time'] = date('YmdHis', strtotime('+10 year'));
            $batch_info_data['goods_id'] = $goodsData['goods_id'];
            $batch_info_data['m_id'] = $m_id;
            $batch_info_data['storage_type'] = '0';
            $batch_info_data['storage_num'] = -1;
            $batch_info_data['remain_num'] = -1;
            $b_id = M('tbatch_info')->add($batch_info_data);
            if(!$b_id){
                return array('resultCode'=>'1001','resultTxt'=>'活动id创建失败');
            }else{
                return $b_id;
            }
        }
        
    }

    /**
     * 发码
     */
    public function sendCode($nodeId, $batch_no, $batchInfoId, $activeFlag = NULL){
        $strurl = array();
        $strurl['node_id'] = $nodeId;
        $strurl['user_id'] = $nodeId;
        $strurl['phone_no'] = '13900000000';
        $strurl['batch_no'] = $batch_no;
        $strurl['request_id'] = get_request_id();
        $strurl['data_from'] = 'Fj114';
        $strurl['active_flag'] = $activeFlag;
        $strurl['batch_info_id'] = $batchInfoId;
        $strurl['channel_id'] = '';
        $strurl['is_wx_card'] = 'no';
        
        $req_data = '&'.http_build_query($strurl);
        
        $RemoteRequest = D('RemoteRequest', 'Service');
        $resp_array = $RemoteRequest->requestWcAppServ($req_data);
        if($resp_array['resp_id'] != '0000'){
            $result['resultCode'] = (int) 5001;
            $result['resultTxt'] = 'failed to create card';
            echo json_encode($result);
            exit;
        }
        log_write(print_r($resp_array, true));
        $shortUrl = $resp_array['resp_data']['short_url_info'];
        return $shortUrl;
    }
    
    /**
     * 卡券详情
     * @param type $goosId
     */
    public function cardDetail($goodsId = ''){
        if($goodsId != ''){			
            $cardInfo = M('tgoods_info')
                ->where(array('goods_id'=>$goodsId))
                ->field('goods_name, print_text, config_data, goods_image, id')->find();
            $cardInfo['config'] = json_decode($cardInfo['config_data'], TRUE);
            return $cardInfo;
        }
    }
    
    /**
     * 查询当月已发送条数
     * @param type $goodsList
     */
    public function checkCreatedCardCount($goodsList){
        $goodsIdArray = M('tgoods_info')->field('goods_id')->where(array('id'=>array('in', $goodsList)))->select();
        $goodsIdResultArray = array();
        foreach($goodsIdArray as $val){
            $goodsIdResultArray[] = $val['goods_id'];
        }
        $goodsIdstr = implode(',', $goodsIdResultArray);
        
        $condition = array();
        $condition['goods_id'] = array('in', $goodsIdstr);
        $startDate = date('Ym').'01000000';
        $endDate = date('YmdHis');
        $condition['trans_time'] = array('between', array($startDate, $endDate));
        $createdCardAccount = M('tbarcode_trace')->where($condition)->count();
        $createdCardAccount = get_val($createdCardAccount, '', 0);
        return $createdCardAccount;
    }
    
    /**
     * 修改门店联系邮箱
     * @param type $email
     * @return boolean
     */
    public function changeShopEmail($email) {
        $posId = M('tpos_info')->where(array('store_id'=>$_SESSION['Fj114']['store_id'], 'node_id'=>$_SESSION['Fj114']['node_id']))->getfield('pos_id');
        $reqArr = array(
            'MotifyPosStoreReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'),
                'TransactionID' => time() . mt_rand('1000', '9999'),
                'ISSPID' => $_SESSION['Fj114']['node_id'],
                'StoreID' => $_SESSION['Fj114']['store_id'],
                'PosType'=>'3',
                'PosID'=>$posId,
                'PrincipalEmail' => $email));
        $reqResult = D('RemoteRequest', 'Service')->requestIssServ($reqArr);
        $respStatus = isset($reqResult['MotifyPosStoreRes']) ? $reqResult['MotifyPosStoreRes']['Status'] : $reqResult['Status'];
        if ($respStatus['StatusCode'] != '0000') {
            return array('resultCode' => '1002', 'resultTxt' => '门店邮箱修改失败');
        } else {
            $condition = array();
            $condition['store_id'] = $_SESSION['Fj114']['store_id'];
            $condition['node_id'] = $_SESSION['Fj114']['node_id'];
            M('tstore_info')->where($condition)->save(array('principal_email' => $email, 'store_email' => $email));
            return TRUE;
        }
    }
    
    /**
     * 获取不重复的小店名字
     * @param type $nodeId
     * @param type $shopName
     */
    public function getShopName($nodeId, $shopName,$number = 0,  $phone=''){
        if($phone != ''){
            $existShopName = M()->table("tfb_fjguaji_shop_info tfsi")
                ->join('tstore_info tsi ON tsi.store_id = tfsi.store_id')
                ->where(array('tfsi.phone_no'=>$phone))
                ->getfield('tsi.store_short_name');
        }
        if(isset($existShopName)){
            return $existShopName;
        }else{
            $storeId = M('tstore_info')->where(array('node_id'=>$nodeId, 'store_short_name'=>$shopName))->getField('id');
            $storeId = get_val($storeId, '', '0');
            if($storeId != '0'){
                if($number == 0){
                    $this->shopname = $shopName;
                }
                $shopName = $this->shopname.$number;
                $number += 1;
                return $shopName = $this->getShopName($nodeId, $shopName, $number, $phone);
            }else{
                return $shopName;
            }
        }
    }
}
