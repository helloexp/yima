<?php

/**
 * 门店操作相关
 *
 * @author bao
 */
class StoresModel extends Model {

    protected $tableName = 'tstore_info';

    /**
     * 获取商户上线门店数据(目前每个商户只有一个线上门店)
     *
     * @param $nodeId 商户号
     * @param $flag 是否查询可用门店
     * @return 线上门店数据
     */
    public function getOnlineStore($nodeId, $flag = false) {
        $where = array();
        if ($flag == true) {
            $where = array(
                'node_id' => $nodeId, 
                'status' => '0', 
                'type' => 3);
        } else {
            $where = array(
                'node_id' => $nodeId, 
                'type' => 3);
        }
        $data = M('tstore_info')->where($where)->find();
        return $data;
    }

    /**
     * 获取卡券验证助手的门店
     *
     * @param $nodeId 商户号
     * @return
     *
     */
    public function getFvStore($nodeId, $nRet = false) {
        $data = M('tstore_info')->where(
            array(
                'node_id' => $nodeId, 
                'status' => '0', 
                'type' => 4))->find();
        if (empty($data)) {
            if ($nRet) {
                return 0;
            }
            $bRet = $this->zcCreateFvStore($nodeId);
            if (! $bRet) {
                return 0;
            } else {
                return $bRet;
            }
        }
        return $data;
    }

    /**
     * [getEpos 查询是否有可用的线上门店的epos]
     *
     * @param [type] $nodeId [机构号]
     * @return [type] [返回pos信息]
     */
    public function getEpos($nodeId) {
        $storeInfo = $this->getOnlineStore($nodeId, true);
        if (empty($storeInfo)) {
            log_write($nodeId . '没有可用的线上门店，却来开通epos，不合理');
            throw_exception('没有可用的线上门店');
        }
        $data = M('tpos_info')->where(
            array(
                'node_id' => $nodeId, 
                'store_id' => $storeInfo['store_id']))->find();
        return $data;
    }

    /**
     * [getFvPosId 获取卡券验证助手的posid]
     *
     * @param [type] $nodeId [机构号]
     * @return [type] [返回pos信息]
     */
    public function getFvPosId($nodeId, $noRes = false) {
        $storeInfo = $this->getFvStore($nodeId, $noRes);
        if (! $storeInfo) {
            return 0;
        }
        $data = M('tpos_info')->where(
            array(
                'node_id' => $nodeId, 
                'store_id' => $storeInfo['store_id']))->find();
        if (empty($data)) {
            if ($noRes) {
                Log::write('卡券验证助手：您尚未开通验证终端');
                return 0;
            } else {
                $arrOrderInfo = M('tactivity_order')->where(
                    array(
                        'node_id' => $nodeId, 
                        'order_type' => 4))->find();
                if (empty($arrOrderInfo)) {
                    return $this->zcCreateFvEpos($nodeId);
                } else {
                    return true;
                }
            }
        }
        return $data['pos_id'];
    }

    /**
     * [zcCreateStore 创建线上门店]
     *
     * @return [type] [description]
     */
    public function zcCreateOnlineStore($nodeId, $storeData = null) {
        // 线上门店数据
        if (! $storeData) {
            $nodeInfo = M('tnode_info')->getByNode_id($nodeId);
            $userInfo = M('tuser_info')->getByNode_id($nodeId);
            $cityInfo = $nodeInfo['node_citycode'];
            if (! empty($cityInfo)) {
                if (strlen(trim($cityInfo)) <= 5) {
                    $province_code = substr($cityInfo, 0, 2);
                    $city_code = substr($cityInfo, 2, 3);
                    $town_code = '001';
                } else {
                    $province_code = substr($cityInfo, 0, 2);
                    $city_code = substr($cityInfo, 2, 3);
                    $town_code = substr($cityInfo, 5, 3);
                }
            } else {
                $province_code = '09';
                $city_code = '021';
                $town_code = '001';
            }
            // 为空的情况是用来创建线上门店的
            $storeName = '线上门店' . mt_rand('10', '99') . $nodeId;
            $storeData = array(
                'node_id' => $nodeId, 
                'user_id' => $userInfo['user_id'], 
                'phone_no' => $nodeInfo['contact_phone'], 
                'custom_no' => '', 
                'store_name' => $storeName, 
                'store_short_name' => $storeName, 
                'contact_name' => $nodeInfo['contact_name'], 
                'contact_tel' => $nodeInfo['contact_tel'] ? $nodeInfo['contact_tel'] : $nodeInfo['contact_phone'], 
                'contact_email' => $nodeInfo['contact_eml'], 
                'province_code' => $province_code, 
                'city_code' => $city_code, 
                'town_code' => $town_code, 
                'address' => $nodeInfo['node_citycode'], 
                'store_desc' => '线上门店（提领）', 
                'type' => '3', 
                'store_introduce' => '线上门店（提领）');
        }
        
        // 创建支撑门店请求参数
        $TransactionID = time() . mt_rand('1000', '9999'); // 流水号
        $reqArr = array(
            'SystemID' => C('ISS_SYSTEM_ID'), 
            'TransactionID' => $TransactionID, 
            'ISSPID' => $storeData['node_id'], 
            'UserId' => $storeData['user_id'], 
            'Url' => '<![CDATA[旺财会员账户中心]]>', 
            'CustomNo' => $storeData['custom_no'], 
            'StoreName' => $storeData['store_name'], 
            'StoreShortName' => $storeData['store_short_name'], 
            'ContactName' => $storeData['contact_name'], 
            'ContactTel' => $storeData['contact_tel'], 
            'ContactEmail' => $storeData['contact_email'], 
            'RegionInfo' => array(
                'Province' => $storeData['province_code'], 
                'City' => $storeData['city_code'], 
                'Town' => $storeData['town_code'], 
                'Address' => $storeData['address']));
        // 创建支撑门店,请求返回
        $RemoteRequest = D('RemoteRequest', 'Service');
        $reqResult = $RemoteRequest->requestIssServ(
            array(
                'CreateStoreReq' => $reqArr));
        // 返回状态
        $respStatus = isset($reqResult['CreateStoreRes']) ? $reqResult['CreateStoreRes']['Status'] : $reqResult['Status'];
        // 返回是否成功
        if ($respStatus['StatusCode'] != '0000') {
            throw_exception("创建支撑门店失败");
        }
        // 成功时，返回的门店数据
        $respData = $reqResult['CreateStoreRes'];
        $storeId = $respData['StoreId'];
        $addStoreData = array(
            'store_id' => $storeId, 
            'node_id' => $storeData['node_id'], 
            'store_name' => $storeData['store_name'], 
            'store_short_name' => $storeData['store_short_name'], 
            'store_desc' => $storeData['store_desc'], 
            'province_code' => $storeData['province_code'], 
            'city_code' => $storeData['city_code'], 
            'town_code' => $storeData['town_code'], 
            'address' => $storeData['address'], 
            'post_code' => "", 
            'principal_name' => $storeData['contact_name'], 
            'principal_position' => "", 
            'principal_tel' => $storeData['contact_tel'], 
            'principal_phone' => $storeData['phone_no'], 
            'principal_email' => $storeData['contact_email'], 
            'custom_no' => $storeData['custom_no'], 
            'pos_range' => '2', 
            'memo' => "", 
            'status' => 0, 
            'add_time' => date('YmdHis'), 
            'store_phone' => "", 
            'store_email' => "", 
            'busi_time' => "", 
            'store_pic' => "", 
            'business_code' => "", 
            'type' => $storeData['type'], 
            'store_introduce' => $storeData['store_introduce']);
        // 听人说,支撑同步先到了，旺财门店入库（主键重复）异常，故先delete(这里也是人家原话)
        M('tstore_info')->where(array(
            'store_id' => $storeId))->delete();
        $result = M('tstore_info')->add($addStoreData);
        if ($result === false) {
            throw_exception("本地异常，门店创建失败");
        }
        
        // 成功则返回门店号
        return $storeId;
    }

    /**
     * [zcCreateFvStore 创建卡券验证助手门店]
     *
     * @return [type] [description]
     */
    public function zcCreateFvStore($nodeId) {
        $nodeInfo = M('tnode_info')->getByNode_id($nodeId);
        $userInfo = M('tuser_info')->getByNode_id($nodeId);
        $cityInfo = $nodeInfo['node_citycode'];
        if (! empty($cityInfo)) {
            if (strlen(trim($cityInfo)) <= 5) {
                $province_code = substr($cityInfo, 0, 2);
                $city_code = substr($cityInfo, 2, 3);
                $town_code = '001';
            } else {
                $province_code = substr($cityInfo, 0, 2);
                $city_code = substr($cityInfo, 2, 3);
                $town_code = substr($cityInfo, 5, 3);
            }
        } else {
            $province_code = '09';
            $city_code = '021';
            $town_code = '001';
        }
        $storeData = array(
            'node_id' => $nodeId, 
            'user_id' => $userInfo['user_id'], 
            'phone_no' => $nodeInfo['contact_phone'], 
            'custom_no' => '', 
            'store_name' => '卡券验证助手' . $nodeId . mt_rand(1, 9), 
            'store_short_name' => '卡券验证助手' . $nodeId . mt_rand(1, 9), 
            'contact_name' => $nodeInfo['contact_name'], 
            'contact_tel' => $nodeInfo['contact_tel'] ? $nodeInfo['contact_tel'] : $nodeInfo['contact_phone'], 
            'contact_email' => $nodeInfo['contact_eml'], 
            'province_code' => $province_code, 
            'city_code' => $city_code, 
            'town_code' => $town_code, 
            'address' => $nodeInfo['node_citycode'], 
            'store_desc' => '卡券验证助手专用门店', 
            'type' => '4', 
            'store_introduce' => '卡券验证助手专用门店');
        // 创建支撑门店请求参数
        $TransactionID = time() . mt_rand('1000', '9999'); // 流水号
        $reqArr = array(
            'SystemID' => C('ISS_SYSTEM_ID'), 
            'TransactionID' => $TransactionID, 
            'ISSPID' => $storeData['node_id'], 
            'UserId' => $storeData['user_id'], 
            'Url' => '<![CDATA[旺财会员账户中心]]>', 
            'CustomNo' => $storeData['custom_no'], 
            'StoreName' => $storeData['store_name'], 
            'StoreShortName' => $storeData['store_short_name'], 
            'ContactName' => $storeData['contact_name'], 
            'ContactTel' => $storeData['contact_tel'], 
            'ContactEmail' => $storeData['contact_email'], 
            'RegionInfo' => array(
                'Province' => $storeData['province_code'], 
                'City' => $storeData['city_code'], 
                'Town' => $storeData['town_code'], 
                'Address' => $storeData['address']));
        // 创建支撑门店,请求返回
        $RemoteRequest = D('RemoteRequest', 'Service');
        $reqResult = $RemoteRequest->requestIssServ(
            array(
                'CreateStoreReq' => $reqArr));
        // 返回状态
        $respStatus = isset($reqResult['CreateStoreRes']) ? $reqResult['CreateStoreRes']['Status'] : $reqResult['Status'];
        // 返回是否成功
        if ($respStatus['StatusCode'] != '0000') {
            Log::write('卡券验证助手：创建支撑门店失败');
            return 0;
        }
        // 成功时，返回的门店数据
        $respData = $reqResult['CreateStoreRes'];
        $storeId = $respData['StoreId'];
        $addStoreData = array(
            'store_id' => $storeId, 
            'node_id' => $storeData['node_id'], 
            'store_name' => $storeData['store_name'], 
            'store_short_name' => $storeData['store_short_name'], 
            'store_desc' => $storeData['store_desc'], 
            'province_code' => $storeData['province_code'], 
            'city_code' => $storeData['city_code'], 
            'town_code' => $storeData['town_code'], 
            'address' => $storeData['address'], 
            'post_code' => "", 
            'principal_name' => $storeData['contact_name'], 
            'principal_position' => "", 
            'principal_tel' => $storeData['contact_tel'], 
            'principal_phone' => $storeData['phone_no'], 
            'principal_email' => $storeData['contact_email'], 
            'custom_no' => $storeData['custom_no'], 
            'pos_range' => '2', 
            'memo' => "", 
            'status' => 0, 
            'add_time' => date('YmdHis'), 
            'store_phone' => "", 
            'store_email' => "", 
            'busi_time' => "", 
            'store_pic' => "", 
            'business_code' => "", 
            'type' => $storeData['type'], 
            'store_introduce' => $storeData['store_introduce']);
        // 听人说,支撑同步先到了，旺财门店入库（主键重复）异常，故先delete(这里也是人家原话)
        M('tstore_info')->where(array(
            'store_id' => $storeId))->delete();
        $result = M('tstore_info')->add($addStoreData);
        if ($result === false) {
            Log::write('卡券验证助手：本地异常，门店创建失败');
            return 0;
        }
        
        // 成功则返回门店号
        return $addStoreData;
    }

    public function zcCreateEpos($nodeId, $userId, $email) {
        // type为3表示系统线上门店
        $storeInfo = $this->getOnlineStore($nodeId, true);
        if (! $storeInfo) {
            log_write($nodeId . '可用的线上提领门店不存在，导致epos无法创建！');
            throw_exception('申请失败');
        }
        $nodeInfo = M('tnode_info')->getByNode_id($nodeId);
        $nOrderId = date('YmdHis') . mt_rand('100000', '999999');
        $req_arr = array();
        $pos_name = '线上门店' . mt_rand('10', '99') . $nodeId;
        $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
        $req_arr['SystemID'] = C('YZ_SYSTEM_ID');
        $req_arr['ClientID'] = str_pad($nodeInfo['client_id'], 8, '0', 
            STR_PAD_LEFT);
        $req_arr['NodeID'] = $nodeId;
        $req_arr['ContractID'] = $nodeInfo['contract_no'];
        $req_arr['OrderID'] = $nOrderId;
        $req_arr['CreateTime'] = date('YmdHis');
        $req_arr['TotalMoney'] = 0;
        $req_arr['TotalFree'] = 0;
        $req_arr['OrderType'] = 1;
        $req_arr['PosInfoList']['StoreID'] = $storeInfo['store_id'];
        $req_arr['PosInfoList']['PosCode'] = "";
        $req_arr['PosInfoList']['PosFlag'] = 2;
        $req_arr['PosInfoList']['PosType'] = 3;
        $req_arr['PosInfoList']['RuleType'] = "28";
        $req_arr['PosInfoList']['ItemName'] = "线上门店提领";
        $req_arr['PosInfoList']['ItemNum'] = 1;
        $req_arr['PosInfoList']['AccountAmt'] = 0;
        $req_arr['PosInfoList']['GiftAmt'] = 0;
        $req_result = D('RemoteRequest', 'Service')->requestYzServ(
            array(
                'PosOrderReq' => $req_arr));
        $respStatus = isset($req_result['PosOrderRes']) ? $req_result['PosOrderRes']['Status'] : $req_result['Status'];
        log_write($nodeId . '线上门店提领开通终端-营帐返回：' . print_r($req_result, true));
        if ($respStatus['StatusCode'] != '0000') {
            throw_exception($respStatus['StatusText']);
        }
        return true;
    }

    /**
     * [zcCreateFvEpos description]
     *
     * @param [type] $nodeId [description]
     * @param [type] $userId [description]
     * @param [type] $email [description]
     * @return [type] [description]
     */
    public function zcCreateFvEpos($nodeId, $userId, $email) {
        // type为4表示卡券验证助手
        $storeInfo = $this->getFvStore($nodeId);
        $nodeInfo = M('tnode_info')->getByNode_id($nodeId);
        // 接收表单传值
        $nOrderId = date('YmdHis') . mt_rand('100000', '999999');
        $req_arr = array();
        $pos_name = '卡券验证' . $nodeId . mt_rand('1', '9');
        $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
        $req_arr['SystemID'] = C('YZ_SYSTEM_ID');
        $req_arr['ClientID'] = str_pad($nodeInfo['client_id'], 8, '0', 
            STR_PAD_LEFT);
        $req_arr['NodeID'] = $nodeId;
        $req_arr['ContractID'] = $nodeInfo['contract_no'];
        $req_arr['OrderID'] = $nOrderId;
        $req_arr['CreateTime'] = date('YmdHis');
        $req_arr['TotalMoney'] = 0;
        $req_arr['TotalFree'] = 0;
        $req_arr['OrderType'] = 1;
        $req_arr['PosInfoList']['StoreID'] = $storeInfo['store_id'];
        $req_arr['PosInfoList']['PosCode'] = "";
        $req_arr['PosInfoList']['PosFlag'] = 2;
        $req_arr['PosInfoList']['PosType'] = 3;
        $req_arr['PosInfoList']['RuleType'] = "28";
        $req_arr['PosInfoList']['ItemName'] = "卡券验证助手";
        $req_arr['PosInfoList']['ItemNum'] = 1;
        $req_arr['PosInfoList']['AccountAmt'] = 0;
        $req_arr['PosInfoList']['GiftAmt'] = 0;
        $req_result = D('RemoteRequest', 'Service')->requestYzServ(
            array(
                'PosOrderReq' => $req_arr));
        $respStatus = isset($req_result['PosOrderRes']) ? $req_result['PosOrderRes']['Status'] : $req_result['Status'];
        if ($respStatus['StatusCode'] != '0000') {
            Log::write('卡券验证助手：' . $respStatus['StatusText']);
            return 0;
        }
        return true;
    }

    public function makeActiveEnable($nodeId, $userId) {
        // 查询已停用的线上门店
        $storeInfo = M('tstore_info')->where(
            array(
                'node_id' => $nodeId, 
                'status' => '1', 
                'type' => '3'))->find();
        if (empty($storeInfo)) {
            throw_exception('您没有已停用的线上门店，无法启用');
        }
        $posInfo = M('tpos_info')->where(
            array(
                'node_id' => $nodeId, 
                'store_id' => $storeInfo['store_id']))->find();
        
        M()->startTrans();
        // 首先在本地将门店状态从停用修改成正常
        $res = M('tstore_info')->where(
            array(
                'store_id' => $storeInfo['store_id']))->save(
            array(
                'status' => '0'));
        if ($res === false) {
            M()->rollback();
            throw_exception('线上门店启用失败');
        }
        // 然后请求支撑，启用此门店的epos
        $reqArr = array(
            'PosStatusModifyReq' => array(
                'TransactionID' => date('YmdHis') . mt_rand(1000, 9999), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'NodeId' => $nodeId, 
                'StoreId' => $storeInfo['store_id'], 
                'PosId' => $posInfo['pos_id'], 
                'UserId' => $userId, 
                'EnablelFlag' => '1')); // 1表示启用，0表示停用
        
        $reqResult = D('RemoteRequest', 'Service')->requestIssForImageco(
            $reqArr);
        $codeResult = $reqResult['PosStatusModifyRes']['Status']['StatusCode'];
        log_write($nodeId . '启用门店时，支撑返回的信息:' . print_r($reqResult, true));
        if ($codeResult != '0000') {
            M()->rollback();
            throw_exception('启用失败');
        }
        M()->commit();
    }

    public function stopOnline($nodeId, $userId) {
        // 查询已启用的线上门店
        $storeInfo = M('tstore_info')->where(
            array(
                'node_id' => $nodeId, 
                'status' => '0', 
                'type' => '3'))->find();
        if (empty($storeInfo)) {
            throw_exception('您没有已启用的线上门店，无法停用');
        }
        $posInfo = M('tpos_info')->where(
            array(
                'node_id' => $nodeId, 
                'store_id' => $storeInfo['store_id']))->find();
        
        M()->startTrans();
        // 先将线上门店的状态从启用开成停用
        $res = M('tstore_info')->where(
            array(
                'store_id' => $storeInfo['store_id']))->save(
            array(
                'status' => '1'));
        if ($res === false) {
            M()->rollback();
            throw_exception('线上门店停用失败');
        }
        // 请求支撑停用此门店的终端
        $reqArr = array(
            'PosStatusModifyReq' => array(
                'TransactionID' => date('YmdHis') . mt_rand(1000, 9999), 
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'NodeId' => $nodeId, 
                'StoreId' => $storeInfo['store_id'], 
                'PosId' => $posInfo['pos_id'], 
                'UserId' => $userId, 
                'EnablelFlag' => '0'));
        $reqResult = D('RemoteRequest', 'Service')->requestIssForImageco(
            $reqArr);
        $codeResult = $reqResult['PosStatusModifyRes']['Status']['StatusCode'];
        log_write($nodeId . '停用门店时，支撑返回的信息:' . print_r($reqResult, true));
        if ($codeResult != '0000') {
            M()->rollback();
            throw_exception('停用失败');
        }
        M()->commit();
    }

    /**
     * [getStoreCountWithPos 统计门店数据]
     *
     * @param [type] $nodeId [机构号]
     * @return [type] [description]
     */
    public function getStoreCountWithPos($nodeId) {
        $condition['a.node_id'] = $nodeId;
        $condition['a.status'] = 0;
        $condition['a.type'] = array(
            'NOT IN', 
            '3,4'); // 线上门店不统计
        
        $condition['c.pos_type'] = 2;
        $nRet = M()->table("tstore_info a")->field(
            array(
                'count(DISTINCT a.id)' => 'tp_count'))
            ->join('tpos_info c on c.store_id=a.store_id')
            ->where($condition)
            ->find();
        $res["eposCount"] = $nRet['tp_count'];
        
        $condition['c.pos_type'] = 1;
        $nRet = M()->table("tstore_info a")->field(
            array(
                'count(DISTINCT a.id)' => 'tp_count'))
            ->join('tpos_info c on c.store_id=a.store_id')
            ->where($condition)
            ->find();
        $res["pos6800Count"] = $nRet['tp_count'];
        return $res;
    }

    /**
     * [getAllStoreId 获取所有门店的id]
     *
     * @param [type] $nodeId [机构号]
     * @return [type] [门店总数]
     */
    public function getAllStoreId($nodeId) {
        $condition['node_id'] = $nodeId;
        $condition['status'] = 0;
        $condition['type'] = array(
            'NOT IN', 
            '3,4'); // 线上门店不统计
        $storeInfo = $this->field('store_id')
            ->where($condition)
            ->select();
        $aStoreId = array();
        if (! empty($storeInfo)) {
            foreach ($storeInfo as $k => $v) {
                $aStoreId[] = $v['store_id'];
            }
        }
        return $aStoreId;
    }

    public function getNeedPayMoney($nodeId, $arrStoreSess) {
        $nodeInfo = M('tnode_info')->getByNode_id($nodeId);
        
        $postype = $arrStoreSess['postype'];
        $funcType = $arrStoreSess['functype'];
        $buyerInfo = $arrStoreSess['buyerInfo'];
        // 0表示没有gprs这一项，1表示有并勾选了，2表示有但是没有勾选
        $gprs = $arrStoreSess['gprs'];
        $selectStoreId = explode(',', $arrStoreSess['storeCheckStatus']);
        
        $checkedStoreCount = count($selectStoreId); // 已选门店的数量
                                                    
        // 当月剩余天数计算
        $nRemainDayCount = date('Ymd', 
            strtotime(
                date('YmdHis', strtotime(date('Ym01000000') . ' +1 month')) .
                     ' -1 day')) - date('Ymd');
        // 获取申请终端费用
        $pm = D('PosPayPrice');
        $pm->SetSpecialPrice($nodeId);

        $nOrderId = date('YmdHis') . mt_rand('100000', '999999');
        $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
        $req_arr['SystemID'] = C('YZ_SYSTEM_ID');
        $req_arr['ClientID'] = str_pad($nodeInfo['client_id'], 8, '0', 
            STR_PAD_LEFT);
        $req_arr['NodeID'] = $nodeId;
        $req_arr['ContractID'] = $nodeInfo['contract_no'];
        $req_arr['OrderID'] = $nOrderId;
        $req_arr['CreateTime'] = date('YmdHis');
        $nTotalMoney = 0; // 总价
        $nSingelPrice = 0; // 单价
        $maxWbUse = 0; // 旺币最大的使用额度
        $xmlList = ""; // xml的详情
        $detail = ""; // 数据详情
        $data = array();
        if ($postype == 2) {
            $posTypeEx = 3;
            $RuleType = 28;
            if ($funcType == 1) {
                // 是不是很好奇这里的单价居然是一样的，那是因为以后可能会有差异哦^_^
                $nSingelPrice = ceil(
                    ($pm->epos_price / 30) * $nRemainDayCount);
                $posflag = 0;
            } else {
                $nSingelPrice = ceil(
                    ($pm->epos_price / 30) * $nRemainDayCount);
                $posflag = 1;
            }
            $nTotalMoney = $nSingelPrice * $checkedStoreCount;
            $req_arr['TotalMoney'] = $nTotalMoney;
            $maxWbUse = $nTotalMoney;
            $req_arr['TotalFree'] = 0;
            $req_arr['OrderType'] = 1;
            foreach ($selectStoreId as $k => $v) {
                $list = array();
                $list['StoreID'] = $v;
                $list['PosFlag'] = $posflag; // 标准资费
                $list['PosType'] = $posTypeEx; // pos类型
                $list['RuleType'] = $RuleType;
                $list['ItemName'] = 'EPOS开通服务';
                $list['ItemNum'] = 1;
                $list['AccountAmt'] = $nSingelPrice;
                $list['GiftAmt'] = 0;
                $xmlList[] = $list;
            }
            $data['amount'] = $nTotalMoney; // 总价
            $data['realAmount'] = 0; // 对账户余额的要求，不得小于这个数
            $data['storeCount'] = $checkedStoreCount; // 门店总数
            $data['stores_id'] = implode(',', $selectStoreId); // 门店id汇总
            $data['func_type'] = $funcType; // 功能详情，1是单向0是全部功能
            $data['posName'] = "EPOS"; // 开通的pos名称
        } else if ($postype == 1) {
            $posTypeEx = 2;
            $RuleType = 9;
            if ($funcType == 1) {
                $nSingelPrice = ceil(
                    ($pm->er6800_price / 30) *
                         $nRemainDayCount);
                $posflag = 0;
            } else {
                $nSingelPrice = ceil(
                    ($pm->er6800_price / 30) *
                         $nRemainDayCount);
                $posflag = 1;
            }
            $gprs_price = ($gprs == '1' ? $pm->gprs_price : 0);
            $nTotalMoney = ($nSingelPrice +
                            $pm->er6800_deposit + $gprs_price +
                            $pm->er6800_install) * $checkedStoreCount;
            $req_arr['TotalMoney'] = $nTotalMoney;
            $maxWbUse = $nTotalMoney -
                    $pm->er6800_deposit * $checkedStoreCount;
            $req_arr['TotalFree'] = 0;
            $req_arr['OrderType'] = 1;
            foreach ($selectStoreId as $k => $v) {
                $list = array();
                $list['StoreID'] = $v;
                $list['PosFlag'] = $posflag; // 标准资费
                $list['PosType'] = $posTypeEx; // pos类型
                $list['RuleType'] = $RuleType;
                $list['ItemName'] = '6800开通服务';
                $list['ItemNum'] = 1;
                $list['AccountAmt'] = $nSingelPrice;
                $list['GiftAmt'] = 0;
                $xmlList[] = $list;
            }
            $list = array();
            $list['StoreID'] = "";
            $list['PosFlag'] = ""; // 标准资费
            $list['PosType'] = ""; // pos类型
            $list['RuleType'] = 13;
            $list['ItemName'] = '6800押金';
            $list['ItemNum'] = $checkedStoreCount;
            $list['AccountAmt'] = $pm->er6800_deposit *
                 $checkedStoreCount;
            $list['GiftAmt'] = 0;
            $xmlList[] = $list;
            
            $list = array();
            $list['StoreID'] = "";
            $list['PosFlag'] = ""; // 标准资费
            $list['PosType'] = ""; // pos类型
            $list['RuleType'] = 59;
            $list['ItemName'] = '6800安装调试费';
            $list['ItemNum'] = $checkedStoreCount;
            $list['AccountAmt'] = $pm->er6800_install *
                 $checkedStoreCount;
            $list['GiftAmt'] = 0;
            $xmlList[] = $list;
            if ($gprs == '1') {
                $list = array();
                $list['StoreID'] = "";
                $list['PosFlag'] = ""; // 标准资费
                $list['PosType'] = ""; // pos类型
                $list['RuleType'] = 14;
                $list['ItemName'] = '6800-GPRS卡费';
                $list['ItemNum'] = $checkedStoreCount;
                $list['AccountAmt'] = $pm->gprs_price *
                     $checkedStoreCount;
                $list['GiftAmt'] = 0;
                $xmlList[] = $list;
            }
            
            $data['amount'] = $nTotalMoney;
            $data['realAmount'] = $pm->er6800_deposit *
                 $checkedStoreCount;
            $data['stores_id'] = implode(',', $selectStoreId);
            $data['storeCount'] = $checkedStoreCount;
            $data['func_type'] = $funcType;
            $data['posName'] = "ER6800";
        } else if ($postype == 3) {
            $posTypeEx = 3;
            $RuleType = 28;
            if ($funcType == 1) {
                $nSingelPrice = ceil(
                    ($pm->er1100_price / 30) *
                         $nRemainDayCount);
                $posflag = 0;
            } else {
                $nSingelPrice = ceil(
                    ($pm->er1100_price / 30) *
                         $nRemainDayCount);
                $posflag = 1;
            }
            $gprs_price = ($gprs == '1' ? $pm->gprs_price : 0);
            $nTotalMoney = ($nSingelPrice +
                            $pm->er1100_terminal + $gprs_price) *
                 $checkedStoreCount;
            $req_arr['TotalMoney'] = $nTotalMoney;
            $maxWbUse = $nTotalMoney -
                    $pm->er1100_terminal * $checkedStoreCount;
            $req_arr['TotalFree'] = 0;
            $req_arr['OrderType'] = 1;
            foreach ($selectStoreId as $k => $v) {
                $list = array();
                $list['StoreID'] = $v;
                $list['PosFlag'] = $posflag; // 标准资费
                $list['PosType'] = $posTypeEx; // pos类型
                $list['RuleType'] = $RuleType;
                $list['ItemName'] = 'ER1100开通服务';
                $list['ItemNum'] = 1;
                $list['AccountAmt'] = $nSingelPrice;
                $list['GiftAmt'] = 0;
                $xmlList[] = $list;
            }
            $list = array();
            $list['StoreID'] = "";
            $list['PosFlag'] = ""; // 标准资费
            $list['PosType'] = ""; // pos类型
            $list['RuleType'] = 41;
            $list['ItemName'] = 'ER1100销售';
            $list['ItemNum'] = $checkedStoreCount;
            $list['AccountAmt'] = $pm->er1100_terminal *
                 $checkedStoreCount;
            $list['GiftAmt'] = 0;
            $xmlList[] = $list;
            if ($gprs == '1') {
                $list = array();
                $list['StoreID'] = "";
                $list['PosFlag'] = ""; // 标准资费
                $list['PosType'] = ""; // pos类型
                $list['RuleType'] = 14;
                $list['ItemName'] = 'GPRS卡用费';
                $list['ItemNum'] = $checkedStoreCount;
                $list['AccountAmt'] = $pm->gprs_price *
                     $checkedStoreCount;
                $list['GiftAmt'] = 0;
                $xmlList[] = $list;
            }
            
            $data['amount'] = $nTotalMoney;
            $data['realAmount'] = $pm->er1100_terminal *
                 $checkedStoreCount;
            $data['storeCount'] = $checkedStoreCount;
            $data['stores_id'] = implode(',', $selectStoreId);
            $data['func_type'] = $funcType;
            $data['posName'] = "ER1100";
        }
        $req_arr['PosInfoList'] = $xmlList;
        $data['PosOrderReq'] = $req_arr;
        $data['buyerInfo'] = $buyerInfo;
        $data['gprs'] = $gprs;
        $data['maxWbUse'] = $maxWbUse;
        $data['user_name'] = M('tuser_info')->getFieldByNode_id($nodeId, 
            'true_name');
        $data['nRemainDayCount'] = $nRemainDayCount;
        return $data;
    }

    public function sendEmail($postInfo) {
        $data = json_decode(urldecode($postInfo['REQUEST']['data']),true);
        log_write(print_r($data,true));
        $nodeId = $data[0]['nodeID'];
        $posInfoList = $data[0]['posInfoList'];
        log_write(print_r($posInfoList,true));

        $nodeInfo = get_node_info($nodeId);

        $send_email = "xietm@imageco.com.cn";
        $szContent = "";
        $arr = array(
            "商户名称：{$nodeInfo['node_name']}", 
            "商户旺号：{$nodeInfo['client_id']}", 
            "商户联系人: {$nodeInfo['contact_name']}", 
            "商户联系电话：{$nodeInfo['contact_phone']}", 
            "商户联系邮箱：{$nodeInfo['contact_eml']}", 
            "申请说明：申请终端，订单号：" .$data[0]['orderID'],
            "门店详细信息如下：", 
            "-----------------------------");
        $storeInfo = M('tstore_info')->where(array('node_id'=>$nodeId,'status'=>0))->select();
        $newStoreInfo = array();
        if(!empty($storeInfo)){
            foreach ($storeInfo as $k => $v) {
                $newStoreInfo[$v['store_id']] = $v;
            }
        }
        foreach ($posInfoList as $kk => $vv) {
            if(!empty($vv['storeID'])){
                $arrTmp = array(
                    "门店名称: " . $newStoreInfo[$vv['storeID']]['store_name'] . '(' . $vv['storeID'] . ')', 
                    "终端业务: " . $vv['itemName'], 
                    "门店联系人: " . $newStoreInfo[$vv['storeID']]['principal_name'], 
                    "门店联系电话：" . $newStoreInfo[$vv['storeID']]['principal_tel'], 
                    "门店联系邮箱：" . $newStoreInfo[$vv['storeID']]['principal_email'], 
                    "<br/>");
                $arr = array_merge($arr, $arrTmp);
            }
        }
        
        $data = array(
            'node_id' => $nodeId, 
            'type' => '9', 
            'add_time' => date('YmdHis'), 
            'status' => '1', 
            'comment' => implode(';', $arr));
        M('tmessage_apply')->add($data);
        $content = implode('<br>', $arr);
        $ps = array(
            "subject" => "终端申请,旺号：" . $nodeInfo['client_id'], 
            "content" => $content, 
            "email" => $send_email);
        send_mail($ps);
        $send_email = "chengs@imageco.com.cn";
        $ps = array(
            "subject" => "终端申请,旺号：" . $nodeInfo['client_id'], 
            "content" => $content, 
            "email" => $send_email);
        send_mail($ps);
    }

    public function raisePos($nodeId, $posId, $storeId) {
        $posInfo = M('tpos_info')->getByPos_id($posId);
        if ($posInfo['func_type'] != '1') {
            return "此pos已开通全部功能,不可升级!";
        }
        if (! in_array($posInfo['pos_type'], 
            array(
                '2', 
                '1'))) {
            return "此pos无法升级!";
        }
        // 当月剩余天数计算
        $nRemainDayCount = date('Ymd', 
            strtotime(
                date('YmdHis', strtotime(date('Ym01000000') . ' +1 month')) .
                     ' -1 day')) - date('Ymd');
        $nER6800Price = ceil((40 / 30) * $nRemainDayCount);
        $nEposPrice = ceil((10 / 30) * $nRemainDayCount);
        $nodeInfo = M('tnode_info')->getByNode_id($nodeId);
        $nOrderId = date('YmdHis') . mt_rand('100000', '999999');
        $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
        $req_arr['SystemID'] = C('YZ_SYSTEM_ID');
        $req_arr['ClientID'] = str_pad($nodeInfo['client_id'], 8, '0', 
            STR_PAD_LEFT);
        $req_arr['NodeID'] = $nodeId;
        $req_arr['ContractID'] = $nodeInfo['contract_no'];
        $req_arr['OrderID'] = $nOrderId;
        $req_arr['CreateTime'] = date('YmdHis');
        $req_arr['TotalMoney'] = ($posInfo['pos_type'] == '1' ? $nER6800Price : $nEposPrice);
        $req_arr['TotalFree'] = 0;
        $req_arr['OrderType'] = 2;
        $req_arr['PosInfoList']['StoreID'] = $storeId;
        $req_arr['PosInfoList']['PosCode'] = $posId;
        $req_arr['PosInfoList']['PosFlag'] = 1;
        
        $req_arr['PosInfoList']['PosType'] = ($posInfo['pos_type'] + 1);
        $req_arr['PosInfoList']['RuleType'] = ($posInfo['pos_type'] == '1' ? '9' : '28');
        $req_arr['PosInfoList']['ItemName'] = ($posInfo['pos_type'] == '1' ? '核验机具ER6800升级' : 'Epos升级');
        $req_arr['PosInfoList']['ItemNum'] = 1;
        $req_arr['PosInfoList']['AccountAmt'] = ($posInfo['pos_type'] == '1' ? $nER6800Price : $nEposPrice);
        $req_arr['PosInfoList']['GiftAmt'] = 0;
        $req_result = D('RemoteRequest', 'Service')->requestYzServ(
            array(
                'PosOrderReq' => $req_arr));
        $respStatus = isset($req_result['PosOrderRes']) ? $req_result['PosOrderRes']['Status'] : $req_result['Status'];
        if ($respStatus['StatusCode'] != '0000') {
            Log::write('epos升级:' . $respStatus['StatusText']);
            return $respStatus['StatusText'];
        }
        M('tpos_info')->where(
            array(
                'node_id' => $nodeId, 
                'pos_id' => $posId))->save(
            array(
                'func_type' => 2, 
                'pay_type' => 0));
        return false;
    }

    /**
     * 获取所有门店分组
     *
     * @param $nodeIn string 商户编号
     * @param $groupName string 分组名称
     * @param $isCount bool 是否请求总记录数
     * @param $firstRow string limit开始条件
     * @param $listRows string limit总条数
     * @return mixed 初始化页面时的分组信息 或者 like查询出来的分组信息
     */
    public function getAllStores($nodeIn, $groupName = '', $isCount = false,
        $firstRow = '', $listRows = '') {
        $nodeIn = str_replace("'", '', $nodeIn);
        if ($groupName) {
            $where = array(
                'g.node_id' => array('in',$nodeIn),
                'g.group_name' => array(
                    'like', 
                    '%' . $groupName . '%'));
        } else {
            $where = array(
                'g.node_id' => array('in',$nodeIn));
        }
        $join = ('i inner join tgroup_store_relation r on i.store_id=r.store_id right join tstore_group g on r.store_group_id=g.id');
        
        if ($isCount) {
            $count = $this->field(
                'g.id,g.group_name,g.add_time,count(i.id) num')
                ->where($where)
                ->join($join)
                ->group('g.id')
                ->group('g.id')
                ->select();
            return count($count);
        }
        
        // 兼容门店弹窗获取分组
        if (empty($listRows)) {
            $result = $this->field(
                'g.id,g.group_name,g.add_time,count(i.id) num')
                ->join($join)
                ->where($where)
                ->group('g.id')
                ->select();
        } else {
            $result = $this->field(
                'g.id,g.group_name,g.add_time,count(i.id) num')
                ->join($join)
                ->where($where)
                ->limit($firstRow, $listRows)
                ->group('g.id')
                ->select();
        }
        
        foreach ($result as $key => $value) {
            $result[$key]['add_time'] = date("Y-m-d", 
                strtotime($value['add_time']));
        }
        
        return $result;
    }

    /**
     * 获取当前商户下未被分组的所有门店
     *
     * @param $nodeIn string 商户编号
     * @param $where bool 是否获取分组弹窗里的未被分组的门店
     * @param $joinTable mixed 所要关联的表
     * @return mixed
     */
    public function getUnGroupedAllStore($nodeIn, $where = false, $joinTable = false) {
        $nodeIn = str_replace("'", '', $nodeIn);
        $join = ('i inner join tgroup_store_relation r on i.store_id=r.store_id inner join tstore_group g on r.store_group_id=g.id');
        
        // 已被分组的
        $Already = $this->field('r.store_id')
            ->where("g.node_id in ({$nodeIn}) and i.type = 1")
            ->join($join)
            ->select();
        
        // 拿到所有的门店(store_id)
        $allInfoId = $this->field('store_id')
            ->where("node_id in ({$nodeIn}) and type = 1")
            ->select();
        
        $new = array();
        $all = array();
        
        foreach ($Already as $key => $value) {
            $new[] = $value['store_id'];
        }
        
        foreach ($allInfoId as $key => $value) {
            $all[] = $value['store_id'];
        }
        
        // 过滤掉已被分组的门店
        $reu = array_diff($all, $new);
        
        $field = 'store_id,store_name,province_code,city_code,town_code,province,city,town';
        if ($where) {
            $where = 'a.node_id in ('.$nodeIn.')'.
                 ' and ISNULL(b.store_group_id) and a.type not in (3,4) and ' .
                 $where;
            $field = ' a.store_id,a.store_name,a.province,a.city,a.town ';
            $join = ' a LEFT JOIN tgroup_store_relation b on a.store_id = b.store_id ';
            if ($joinTable) {
                $join .= 'left join ' . $joinTable .
                     ' userTable on a.store_id=userTable.store_id ';
            }
            $result = $this->field($field)
                ->where($where)
                ->join($join)
                ->group('a.store_id')
                ->select();
                if(empty($result)) $result = array();
            return (array) array_filter($result);
        }
        // 获取未被分组的门店
        $result = $this->field($field)
            ->where(
            array(
                'node_id' => array('in',$nodeIn),
                'store_id' => array(
                    'in', 
                    $reu)))
            ->select();
        return $result;
    }

    /**
     * 获取当前组内门店
     *
     * @param $nodeIn string 商户编号
     * @param $groupId number 分组ID号
     * @return mixed
     */
    public function getNowInGroupStore($nodeIn, $groupId) {
        $nodeIn = str_replace("'", '', $nodeIn);
        $join = ('i inner join tgroup_store_relation r on i.store_id=r.store_id right join tstore_group g on r.store_group_id=g.id');
        $where = array(
            'g.node_id' => array('in',$nodeIn),
            'g.id' => $groupId);
        
        $result = $this->field(
            'i.store_id,i.store_name,g.group_name,
                                i.province_code,i.city_code,i.town_code,
                                i.province as province_name,i.city as city_name,i.town as town_name
                               ')
            ->join($join)
            ->where($where)
            ->select();
        
        return array_filter($result);
    }

    /**
     * 获取未开通的门店
     *
     * @param $nodeIn string 商户全编号
     * @param $address array 省市区查询条件
     * @return mixed
     */
    public function getUnOpenedStores($nodeIn, $address = array()) {
        $where = "a.node_id in ($nodeIn) and a.gps_flag = 0";
        
        if ($address['province'] != '') {
            $where .= " and a.province_code = '" . $address['province'] . "'";
        }
        
        if ($address['city'] != '') {
            $where .= " and a.city_code = '" . $address['city'] . "'";
        }
        
        if ($address['town'] != '') {
            $where .= " and a.town_code = '" . $address['town'] . "'";
        }
        $field = 'a.store_short_name, a.store_id,a.lbs_x, a.lbs_y, a.province, a.city, a.town, a.province_code, a.city_code, a.town_code';
        
        $queryList = $this->join(
            'a left join tnode_info b on b.node_id=a.node_id')
            ->field($field)
            ->where($where)
            ->order('a.id desc')
            ->select();
        return $queryList;
    }

    /**
     * 获取已开通的门店
     *
     * @param $nodeIn string 商户全编号
     * @return mixed
     */
    public function getOpenedStores($nodeIn,$m_id) {
        $where = "a.node_id in ($nodeIn) and a.store_id in (select store_id from tstore_navigation_item where m_id=".$m_id.")";
        $storeList = $this->join(
            'a left join tnode_info b on b.node_id=a.node_id')
            ->field(
            'a.store_short_name, a.store_id, a.province, a.city, a.town,b.node_name')
            ->where($where)
            ->order('a.id desc')
            ->select();
        return $storeList;
    }

    /**
     * 开通门店
     *
     * @param $nodeIn string 商户全编号
     * @param $store_id string 门店id
     * @return mixed
     */
    public function openLocationStore($nodeIn, $store_id) {
        if ($store_id == 'allStores') {
            
            $getUnOpenedStores = $this->getUnOpenedStores($nodeIn);
            // 获取所有已定位但未开通的门店
            $allStores = array();
            foreach ($getUnOpenedStores as $key => $value) {
                if ($value['lbs_x'] != 0 && $value['lbs_y'] != 0) {
                    $allStores[] = $value['store_id'];
                }
            }
            $allStore = implode(',', $allStores);
            
            $where = "node_id in ($nodeIn) and store_id in ($allStore)";
        } else {
            $where = "node_id in ($nodeIn) and store_id in ($store_id)";
        }
        $result = $this->where($where)->setField('gps_flag', '1');
        return $result;
    }

    /**
     * 关闭门店
     * @param $nodeIn string 商户全编号
     * @param $store_id string 门店id
     *
     * @return mixed
     */
    public function closeLocationStore($nodeIn, $store_id) {
        $where = "node_id in ($nodeIn) and store_id in ($store_id)";
        $result = $this->where($where)->setField('gps_flag', '0');
        return $result;
    }

    /**
     * 获取省市区 过滤掉没有门店的省市区
     *
     * @param $nodeIn string 商户全编号
     * @param $areaType string 规定要获取 （省 | 市 | 区）的类型
     * @param $where array | string 查询省市区的额外条件
     * @return mixed
     */
    public function areaFilter($nodeIn, $areaType, $where = '') {
        $nodeIn = str_replace("'", '', $nodeIn);
        $field_str = '';
        $group = '';
        $join = '';
        $searchCondition = array();
        
        switch ($areaType) {
            case 'province': // 省
                $field_str = 'a.province_code,a.province';
                $group = 'b.province_code';
                $join = 'a.province_code=b.province_code';
                
                if (is_array($where)) {
                    $searchCondition['b.city_level'] = '1';
                    $searchCondition['a.node_id'] = array(
                        'in', 
                        $nodeIn);
                    $searchCondition['a.type'] = array(
                        'not in', 
                        '3,4');
                    $searchCondition = array_merge($searchCondition, $where);
                } elseif (empty($where)) {
                    $searchCondition = " b.city_level = 1 and a.node_id in ($nodeIn) and a.type not in (3,4)";
                } else {
                    if (! stristr($where, 'a.type')) {
                        $where .= " and a.type not in (3,4) ";
                    }
                    $searchCondition = $where .
                         " and b.city_level = 1 and a.node_id in ($nodeIn)";
                }
                
                break;
            case 'city': // 市
                $field_str = 'a.city_code,a.city';
                $group = 'a.city_code';
                $join = 'a.province_code=b.province_code';
                
                if (is_array($where)) {
                    $searchCondition['b.city_level'] = '2';
                    $searchCondition['b.province_code'] = I('province_code');
                    $searchCondition['a.node_id'] = array(
                        'in', 
                        $nodeIn);
                    $searchCondition['a.type'] = array(
                        'not in', 
                        '3,4');
                    $searchCondition = array_merge($searchCondition, $where);
                } elseif (empty($where)) {
                    $searchCondition = " b.city_level = 2  and b.province_code = " .
                         I('province_code') .
                         " and a.node_id in ($nodeIn) and a.type not in (3,4)";
                } else {
                    if (! stristr($where, 'a.type')) {
                        $where .= " and a.type not in (3,4) ";
                    }
                    $searchCondition = $where .
                         " and b.city_level = 2  and b.province_code = " .
                         I('province_code') . " and a.node_id in ($nodeIn)";
                }
                
                break;
            case 'town': // 区
                $field_str = 'a.town_code,a.town';
                $group = 'a.town_code';
                $join = 'a.city_code=b.city_code';
                
                if (is_array($where)) {
                    $searchCondition['b.city_level'] = '3';
                    $searchCondition['b.province_code'] = I('province_code');
                    $searchCondition['b.city_code'] = I('city_code');
                    $searchCondition['a.node_id'] = array(
                        'in', 
                        $nodeIn);
                    $searchCondition['a.type'] = array(
                        'not in', 
                        '3,4');
                    $searchCondition = array_merge($searchCondition, $where);
                } elseif (empty($where)) {
                    $searchCondition = " b.city_level = 3  and b.province_code = " .
                         I('province_code') . " and b.city_code = " .
                         I('city_code') .
                         " and a.node_id in ($nodeIn) and a.type not in (3,4)";
                } else {
                    if (! stristr($where, 'a.type')) {
                        $where .= " and a.type not in (3,4) ";
                    }
                    $searchCondition = $where .
                         " and b.city_level = 3  and b.province_code = " .
                         I('province_code') . " and b.city_code = " .
                         I('city_code') . " and a.node_id in ($nodeIn)";
                }
                
                break;
            case 'business': // 商圈
                $field_str = 'a.business_circle_code business_code,a.business_circle business';
                $group = 'a.business_circle_code';
                $join = 'a.business_circle_code=b.business_circle_code';
                
                if (is_array($where)) {
                    $searchCondition['b.city_level'] = '4';
                    $searchCondition['b.province_code'] = I('province_code');
                    $searchCondition['b.city_code'] = I('city_code');
                    $searchCondition['b.town_code'] = I('town_code');
                    $searchCondition['a.node_id'] = array(
                        'in', 
                        $nodeIn);
                    $searchCondition['a.type'] = array(
                        'not in', 
                        '3,4');
                    $searchCondition = array_merge($searchCondition, $where);
                } elseif (empty($where)) {
                    $searchCondition = " b.city_level = 4  and b.province_code = " .
                         I('province_code') . " and b.city_code = " .
                         I('city_code') . " and b.town_code = " . I('town_code') .
                         " and a.node_id in ($nodeIn) and a.type not in (3,4)";
                } else {
                    if (! stristr($where, 'a.type')) {
                        $where .= " and a.type not in (3,4) ";
                    }
                    $searchCondition = $where .
                         " and b.city_level = 4  and b.province_code = " .
                         I('province_code') . " and b.city_code = " .
                         I('city_code') . " and b.town_code = " . I('town_code') .
                         " and a.node_id in ($nodeIn)";
                }
                
                break;
        }
        
        $result = $this->field($field_str)
            ->join("a left join tcity_code b on $join")
            ->where($searchCondition)
            ->group($group)
            ->select();
        
        return array_filter($result);
    }

    /**
     * 获取所有门店
     *
     * @param $nodeIn string 商户全编号
     * @param $where array | string 查询门店的额外条件
     * @param $joinTable mixed 所要关联的表
     * @param $userField string 获取第二张表的字段信息
     * @return mixed
     */
    public function getAllStore($nodeIn, $where = '', $joinTable = false, $userField = false) {
        if (is_array($where)) {
            $searchCondition['a.node_id'] = array(
                'in', 
                str_replace("'", '', $nodeIn));
            $searchCondition['a.type'] = array(
                'not in', 
                '3,4');
            $searchCondition = array_merge($searchCondition, $where);
        } elseif (empty($where)) {
            $searchCondition = " a.node_id in ($nodeIn) and a.type not in (3,4) ";
        } else {
            if (! stristr($where, 'a.type')) {
                $where .= " and a.type not in (3,4) ";
            }
            $searchCondition = $where . " and a.node_id in ($nodeIn)";
        }
        
        $join = 'a left join tnode_info b on b.node_id=a.node_id';
        if ($joinTable) {
            $join = 'a left join tnode_info b on b.node_id=a.node_id left join ' .
                 $joinTable . ' userTable on a.store_id = userTable.store_id';
        }
        
        $field = 'a.*';
        if($userField){
            $field = $field.','.$userField;
        }

        $queryList = $this->join($join)
            ->field($field)
            ->where($searchCondition)
            ->group('a.id')
            ->order('a.id desc')
            ->select();
        return $queryList;
    }
    public function getStoresList($nodeIn,$type='') {
        $where = "a.node_id in ($nodeIn)";
        if($type == 1){
            $where .= "and TYPE NOT IN (3,4) AND a.lbs_x >0 AND a.lbs_y > 0";
        }
        $field = 'a.store_short_name, a.store_id,a.lbs_x, a.lbs_y, a.province, a.city, a.town, a.province_code, a.city_code, a.town_code';

        $queryList = $this->join(
            'a left join tnode_info b on b.node_id=a.node_id')
            ->field($field)
            ->where($where)
            ->order('a.id desc')
            ->select();
        return $queryList;
    }
}