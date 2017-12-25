<?php

/**
 *
 * @author lwb last edit Time 20151223
 */
class DrawLotteryAdminModel extends OrderActivityAdminModel {
    protected $tableName = '__NONE__';
    public $freeDuringDays = '45';

    /**
     * 创建默认免费门店
     *
     * @param string $nodeId
     * @param int $userId
     */
    public function createDefaultFreeStore($nodeId, $userId, $model) {
        $nodeInfo = M('tnode_info')->where(
            array(
                'node_id' => $nodeId))->find();
        $nodePovinceCode = substr($nodeInfo['node_citycode'], 0, 2);
        $nodeCityCode = substr($nodeInfo['node_citycode'], 2, 3);
        $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
        $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
        $req_arr['ISSPID'] = $nodeId;
        $req_arr['UserId'] = $userId;
        $req_arr['Url'] = '<![CDATA[旺财会员账户中心]]>';
        $req_arr['CustomNo'] = '';
        $req_arr['StoreName'] = '大转盘门店';
        $req_arr['StoreShortName'] = '大转盘门店';
        $req_arr['ContactName'] = $nodeInfo['contact_name'];
        $req_arr['ContactTel'] = $nodeInfo['contact_phone'];
        $req_arr['ContactEmail'] = $nodeInfo['contact_eml'] ? $nodeInfo['contact_eml'] : 'Email@mail.com';
        $req_arr['RegionInfo'] = array(
            'Province' => $nodePovinceCode, 
            'City' => $nodeCityCode, 
            'Town' => '', 
            'Address' => '请填写门店地址');
        $req_result = D('RemoteRequest', 'Service')->requestIssServ(
            array(
                'CreateStoreReq' => $req_arr));
        $respStatus = isset($req_result['CreateStoreRes']) ? $req_result['CreateStoreRes']['Status'] : $req_result['Status'];
        if ($respStatus['StatusCode'] != '0000') {
            $msg = $respStatus['StatusCode'] ? $respStatus['StatusCode'] .
                 $respStatus['StatusText'] : '创建门店失败';
            throw_exception($msg);
        }
        $respData = $req_result['CreateStoreRes'];
        $store_id = $respData['StoreId'];
        if (! $store_id) {
            throw_exception('创建支撑门店失败');
        }
        // 开始记录到门店表
        $data = array(
            'store_id' => $store_id, 
            'node_id' => $nodeId, 
            'store_name' => $req_arr['StoreName'], 
            'store_short_name' => $req_arr['StoreShortName'], 
            'store_desc' => $req_arr['StoreName'], 
            'address' => '', 
            'principal_name' => $req_arr['ContactName'], 
            'principal_tel' => $req_arr['ContactTel'], 
            'principal_email' => $req_arr['ContactEmail'], 
            'status' => 0,  // 0正常1停用2注销
            'add_time' => date('YmdHis'), 
            'store_phone' => $req_arr['ContactTel'], 
            'store_email' => $req_arr['ContactEmail'], 
            'pos_range' => '2',  // 全局可受理
            'type' => '2',  // 系统自建默认免费类型
            'province_code' => $nodePovinceCode, 
            'city_code' => $nodeCityCode);
        // 情况是:支撑同步先到了，旺财门店入库（主键重复）异常,所以先判断是否有
        $storeInfo = M('tstore_info')->where(
            array(
                'store_id' => $store_id))->find();
        if ($storeInfo) {
            $result = M('tstore_info')->where(
                array(
                    'store_id' => $store_id))->save($data);
        } else {
            $result = M('tstore_info')->add($data);
        }
        if (false === $result) {
            Log_write(print_r($data, true) . M()->getDbError(), 'DB ERROR');
            $model->rollback();
            throw_exception('创建门店失败');
        }
        node_log("【门店管理】门店添加，门店号：{$store_id}"); // 记录日志
        return $store_id;
    }

    /**
     * 创建默认免费epos
     *
     * @param unknown $nodeId
     * @param unknown $storeId
     */
    public function createDefaultFreeEpos($nodeId, $storeId) {
        $req_arr = array();
        $pos_name = '免费EPOS';
        $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
        $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
        $req_arr['ISSPID'] = $nodeId;
        $req_arr['StoreID'] = $storeId;
        $req_arr['PosGroupID'] = '';
        $req_arr['PosFlag'] = 0;
        $req_arr['PosType'] = 3;
        $req_arr['PosName'] = $pos_name;
        $req_arr['PosShortName'] = $pos_name;
        $req_arr['PosPayFlag'] = 2;
        $req_arr['UserID'] = $this->userId;
        $req_result = D('RemoteRequest', 'Service')->requestIssServ(
            array(
                'PosCreateReq' => $req_arr));
        $respStatus = isset($req_result['PosCreateRes']) ? $req_result['PosCreateRes']['Status'] : $req_result;
        if ($respStatus['StatusCode'] != '0000') {
            log_write($respStatus['StatusText']);
            throw_exception($respStatus['StatusText']);
        }
        $respData = $req_result['PosCreateRes'];
        $pos_id = $respData['PosID'];
        if (! $pos_id) {
            log_write('创建支撑终端失败');
            throw_exception('创建支撑终端失败');
        }
        // 创建终端
        $data = array(
            'pos_id' => $pos_id, 
            'node_id' => $nodeId, 
            'pos_name' => $pos_name, 
            'pos_short_name' => $pos_name, 
            'pos_serialno' => $pos_id, 
            'store_id' => $storeId, 
            'store_name' => $pos_name, 
            'login_flag' => 0, 
            'pos_type' => '2', 
            'is_activated' => 0, 
            'pos_status' => 0, 
            'add_time' => date('YmdHis'), 
            'pay_type' => 2);
        $result = M('tpos_info')->add($data);
        if (! $result) {
            log_write(print_r($data, true) . M()->getDbError(), 'DB ERROR');
            throw_exception('创建终端失败,原因：' . M()->getDbError());
        }
        node_log("【门店管理】门店验证终端创建"); // 记录日志
    }

    /**
     * 是否有门店
     *
     * @param $nodeId
     * @return boolean
     */
    public function hasStore($nodeId) {
        $result = M('tstore_info')->where(
            array(
                'node_id' => $nodeId))->find();
        if ($result) {
            return true;
        }
        return false;
    }

    /**
     * 是否可以选择卡券作为奖品(是否活动是微信参与)
     *
     * @param string $nodeId
     * @param string $mId
     * @return boolean
     */
    public function canUseCard($nodeId, $mId) {
        $mInfo = M('tmarketing_info')->where(
            array(
                'id' => $mId, 
                'node_id' => $nodeId))->find();
        if (! $mInfo) {
            return false;
        }
        $canUseCard = true;
        if ($mInfo['join_mode'] == 0) {
            $canUseCard = false;
        }
        return $canUseCard;
    }


    /**
     * 是否创建过这种为c0c1定制的默认门店
     *
     * @param unknown $nodeId
     * @param unknown $mType
     * @return boolean
     */
    public function hasSpActivity($nodeId, $mType) {
        // 是否创建过这种默认门店
        $result = M('tstore_info')->where(
            array(
                'node_id' => $nodeId, 
                'type' => '2'))->find(); // 默认门店
        $hasFree = $result ? true : false;
        return $hasFree;
    }

    /**
     * 获取创建的免费活动
     *
     * @param string $nodeId
     * @return boolean
     */
    public function getFirstFreeActivity($nodeId) {
        // 是否有免费的订单，有免费的订单的就不是第一次//order_type为2表示免费
        $re = M('tactivity_order')->where(
            array(
                'node_id' => $nodeId, 
                'order_type' => CommonConst::ORDER_TYPE_FREE, 
                'batch_type' => CommonConst::BATCH_TYPE_WEEL))->find();
        return $re;
    }

    public function getFreeUseLimit($batchType) {
        // $free_use_day = M('tmarketing_active')->where(array('batch_type' =>
        // $batchType))->getField('free_use_day');
        $limitDay = date('Y-m-d', 
            strtotime('+' . $this->freeDuringDays . 'days'));
        return $limitDay;
    }

    public function checkPrizeLimit($nodeId, $mId) {
        $isFree = D('node')->getNodeVersion($nodeId);
        $inLimit = true;
        if ($isFree) {
            $re = $this->getFirstFreeActivity($nodeId);
            // $orderinfo = D('Order')->getOrderInfoByMid($nodeId, $mId);
            if (! $re) {
                $result = M('tbatch_info')->alias('a')
                    ->field('IFNULL(sum(a.storage_num), 0) as total_num')
                    ->where(
                    array(
                        'a.node_id' => $nodeId, 
                        'a.m_id' => $mId, 
                        'b.status' => '1'))
                    ->join('left join tcj_batch b on a.id = b.b_id')
                    ->select();
                
                if ($result[0]['total_num'] > 100) {
                    $inLimit = false;
                }
            }
        }
        return $inLimit;
    }

    /**
     * 是否能进行编辑
     *
     * @param unknown $nodeId
     * @param unknown $mId
     * @return boolean
     */
    public function canEdit($nodeId, $mId) {
        $mInfo = M('tmarketing_info')->where(
            array(
                'id' => $mId, 
                'node_id' => $nodeId))
            ->field('pay_status')
            ->find();
        $canEdit = true;
        if ($mInfo && $mInfo['pay_status'] === '1') {
            $canEdit = false;
        }
        return $canEdit;
    }

    /**
     * 获取用户订单的里够买的天数
     *
     * @param string $nodeId
     * @param string $mId
     */
    public function getFreeUserOrderInfo($nodeId, $mId) {
        $map['pay_status'] = '1';
        $map['order_type'] = '2';
        $map['_logic'] = 'or';
        $where['_complex'] = $map;
        $where['m_id'] = $mId;
        $where['node_id'] = $nodeId;
        $result = M('tactivity_order')->where(
            )
            ->field('id,order_type,pay_status')
            ->find();
        $orderInfo = false;
        if ($result) {
            if ($result['order_type'] == 2) {
                $orderInfo = $result;
            } else {
                $orderInfo = D('BindChannel')->getOrderInfo($result['id'], $nodeId);
            }
        }
        return $orderInfo;
    }

    /**
     * 获得购买的结束极限时间
     *
     * @param string $firstFreeActMId
     * @param string $m_id
     * @param string $nodeId
     * @return string 购买的结束极限日期
     */
    public function getLimit($firstFreeActMId, $m_id, $nodeId =null, $originStartTime=null) {
        $availableDays = $this->freeDuringDays;
        if ($m_id) {
            $orderInfo = $this->getFreeUserOrderInfo($nodeId, $m_id);
            if ($firstFreeActMId != $m_id && $orderInfo) {
                $availableDays = $orderInfo['detail']['available_days'];
            }
        }
        if (get_val($orderInfo,'pay_status') == 1 || get_val($orderInfo,'order_type') == '2') { // 有没有付过
            $actStartTime = strtotime($originStartTime);
        } else {
            $actStartTime = time();
        }
        $freeUseLimit = date('Y-m-d', 
            strtotime('+' . ($availableDays - 1) . ' days', $actStartTime));
        return $freeUseLimit;
    }

    /**
     * 获得活动极限持续天数
     *
     * @param string $firstFreeActMId
     * @param string $m_id
     * @param string $nodeId
     * @return string
     */
    public function getLimitDuringTime($firstFreeActMId, $m_id, $nodeId = null) {
        $freeUseLimit = $this->freeDuringDays; // 没创建免费订单时，限制时间45天
        if ($m_id && $firstFreeActMId && $firstFreeActMId != $m_id) {
            $orderInfo = $this->getFreeUserOrderInfo($nodeId, $m_id);
            $freeUseLimit = $orderInfo['detail']['available_days'];
        }
        return $freeUseLimit;
    }
}