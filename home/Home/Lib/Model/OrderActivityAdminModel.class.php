<?php

/**
 * 单独购买模块活动的设定
 *
 * @author lwb Time 20151223
 */
class OrderActivityAdminModel {

    public function _initialize() {
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    /**
     * 获取用户订单的信息
     *
     * @param unknown $nodeId
     * @param unknown $mId
     */
    public function getFreeUserOrderInfo($nodeId, $mId) {
        $result = M('tactivity_order')->where(
            array(
                'm_id' => $mId, 
                'node_id' => $nodeId, 
                'pay_status' => '1'))
            ->field('id')
            ->find();
        $orderInfo = false;
        if ($result) {
            $orderInfo = D('BindChannel')->getOrderInfo($result['id'], $nodeId);
        }
        return $orderInfo;
    }

    /**
     * 获得购买的结束极限时间
     *
     * @param unknown $firstFreeActMId
     * @param unknown $m_id
     * @param unknown $nodeId
     * @param $tmp1+$tmp2无用参数，千万别用
     * @return string 购买的结束极限日期
     */
    public function getLimit($m_id, $nodeId,$tmp1 = null,$tmp2 = null) {
        $freeUseLimit = '';
        if ($m_id) {
            $orderInfo = $this->getFreeUserOrderInfo($nodeId, $m_id);
            if ($orderInfo) {
                $availableDays = $orderInfo['detail']['available_days'];
                $actStartTime = strtotime(
                    $orderInfo['detail']['act_start_time']);
                $freeUseLimit = date('Y-m-d', 
                    strtotime('+' . ($availableDays - 1) . ' days', 
                        $actStartTime));
            }
        }
        return $freeUseLimit;
    }

    /**
     * 获得活动极限持续天数
     *
     * @param unknown $firstFreeActMId
     * @param unknown $m_id
     * @param unknown $nodeId
     * @param unknown $tmp 千万不要用
     * @return string
     */
    public function getLimitDuringTime($m_id, $nodeId,$tmp) {
        $freeUseLimit = '';
        if ($m_id) {
            $orderInfo = $this->getFreeUserOrderInfo($nodeId, $m_id);
            $freeUseLimit = $orderInfo['detail']['available_days'];
        }
        return $freeUseLimit;
    }

    public function checkLimitDay($nodeId, $isFreeUser, $m_id, $actStartTime, 
        $actEndTime) {
        // 当前活动订单的信息
        $currentOrderinfo = $this->getFreeUserOrderInfo($nodeId, $m_id);
        if ($isFreeUser && $currentOrderinfo &&
             $currentOrderinfo['pay_status'] == '1') { // 付了款的，并且当前时间超过活动开始时间时
                                                      // 活动已经开始时，就不能修改开始时间，判断结束时间是否在极限时间之内
            $originStartTime = M('tmarketing_info')->where(
                array(
                    'id' => $m_id))->getField('start_time');
            if (time() > strtotime($originStartTime)) {
                // 检查要保存的时间是不是极限时间之前
                $actStartTimeStamp = strtotime($originStartTime);
                $freeUseLimitTimeStamp = $actStartTimeStamp + ($currentOrderinfo['detail']['available_days'] *
                     24 * 60 * 60 - 1);
                $freeUseLimitDate = date('YmdHis', $freeUseLimitTimeStamp);
                if ($actEndTime > $freeUseLimitDate) {
                    throw_exception('超出保存时间范围!');
                }
            } else { // 活动未开始时，判断修改的活动时长是否在购买的时长范围内
                     // $firstFreeActivity =
                     // $this->getFirstFreeActivity($nodeId);
                $limitDuringDay = $this->getLimitDuringTime($m_id, $nodeId);
                $readyDuringDay = (int) ((strtotime($actEndTime) -
                     strtotime($actStartTime) + 1) / 86400);
                if ($limitDuringDay < $readyDuringDay) {
                    throw_exception('超出保存时间范围!');
                }
            }
        }
    }

    /**
     * 是否需要显示超出60天的提示
     *
     * @param string $nodeId 机构号
     * @param string $mId 活动id
     * @return bool
     */
    public function needShowExTips($nodeId, $mId) {
        $needTips = false;
        // 是否是需要付费
        $needPay = D('node')->getNodeVersion($nodeId);
        if ($needPay) {
            // 查询有没有付款完成的订单
            $order = M('tactivity_order')->where(
                array(
                    'node_id' => $nodeId,
                    'm_id' => $mId,
                    'pay_status' => '1',
                    'order_type' => '1'))->find();
            if (! $order) {
                $needTips = true;
            }
        }
        return intval($needTips);
    }
    
    /**
     * 是否选择了微信红包作为奖品
     *
     * @param string $nodeId
     * @param string $mId
     * @return boolean
     */
    public function isSelectWxHb($nodeId, $mId) {
        $map = array(
            'm_id' => $mId,
            'node_id' => $nodeId); // 0表示正常1表示停发
        $jp = M('tbatch_info')->where($map)->select();
        $hasCard = false; // 奖品中有没有正常可发放的微信红包
        $bIdArr = array();
        if ($jp) {
            foreach ($jp as $v) {
                if ($v['batch_type'] == 6 || $v['batch_type'] == 7) {
                    $bIdArr[] = $v['id'];
                }
            }
        }
        $cjBatchModel = M('tcj_batch');
        foreach ($bIdArr as $bId) {
            $re = $cjBatchModel->where(
                array(
                    'b_id' => $bId,
                    'status' => '1'))->find();
                if ($re) {
                    $hasCard = true;
                    break;
                }
        }
        return $hasCard;
    }
    
    
    /**
     * 是否选择了卡券作为奖品
     *
     * @param string $nodeId
     * @param string $mId
     * @return boolean
     */
    public function isSelectCard($nodeId, $mId) {
        $map = array(
            'm_id' => $mId,
            'node_id' => $nodeId); // 0表示正常1表示停发
        $jp = M('tbatch_info')->where($map)->select();
        $hasCard = false; // 奖品中有没有正常可发放的微信卡券
        $bIdArr = array();
        if ($jp) {
            foreach ($jp as $v) {
                if ($v['card_id']) {
                    $bIdArr[] = $v['id'];
                }
            }
        }
        $cjBatchModel = M('tcj_batch');
        foreach ($bIdArr as $bId) {
            $re = $cjBatchModel->where(
                array(
                    'b_id' => $bId,
                    'status' => '1'))->find();
                if ($re) {
                    $hasCard = true;
                    break;
                }
        }
        return $hasCard;
    }
    
    
    /**
     * 是否选择了微信红包作为奖品
     * 这个函数还没用到，本来是打算在选了微信红包的情况下，如果要修改公众号授权提示的时候用到
     * @param unknown $nodeId
     * @param unknown $mId
     * @return boolean
     */
    public function getJpBatchType($nodeId, $mId) {
        $map = array(
            'a.m_id' => $mId,
            'a.node_id' => $nodeId,
            'b.status' => '1'
        ); // 0表示正常1表示停发
        $jp = M()->table("tbatch_info a")
        ->field('a.batch_type as jp_source')
        ->where($map)
        ->join('tcj_batch b on b.b_id = a.id')
        ->select();
        return $jp;
    }
    
    /**
     * 获取时间限制的样式和时间
     * @param int $nodeId
     * @param int $mId
     * @return string[]
     */
    public function getLimitInfo($nodeId, $mId) {
        // 当前活动订单的信息
        $currentOrderinfo = $this->getFreeUserOrderInfo($nodeId, $mId);
        $basicInfo = M('tmarketing_info')->where(array('id' => $mId))->field('start_time')->find();
        $type = '2'; // 2表示普通没有限制时,时间选择的样子
        $freeUseLimit = '';
        if ($currentOrderinfo && $currentOrderinfo['pay_status'] == '1' &&
            time() > strtotime($basicInfo['start_time'])) { // 付了款的，并且当前时间超过活动开始时间时
                $type = '1'; // 表示付款了的,控件需要disabled
                $actStartTimeStamp = strtotime(
                    $currentOrderinfo['detail']['act_start_time']);
                $freeUseLimit = $actStartTimeStamp + ($currentOrderinfo['detail']['available_days'] *
                    24 * 60 * 60 - 1);
        }
        return array(
            'type' => $type, 
            'freeUseLimit' => date('Y-m-d', $freeUseLimit)
        );
    }
    
    /**
     * 奖品的结束时间如果小于活动的结束时间,保存活动时间的时候需要提示
     * @param unknown $mInfo
     * @return boolean[]|string[]
     */
    public function confirmActEndTimeWithPrize($mInfo) {
        // 奖品
        $where = "a.batch_id='" . $mInfo['id'] . "' and a.status='1'";
        $join = 'tbatch_info b on a.b_id=b.id';
        $field = 'b.verify_begin_date,b.verify_end_date,verify_begin_type';
        $prizeList = D('DrawLottery')->getCjBatchJoin($where, $join, $field);
        $inTime = true;
        $msg = '';
        foreach ($prizeList as $v) {
            if ($v['verify_end_date'] < $mInfo['end_time']) {
                $inTime = false;
                $msg = '“活动日期”超出“奖品有效期”会造成奖品无法领取，您可以去奖品配置页调整“奖品有效期”。是否继续？';
                break;
            }
        }
        return array(
            'inTime' => $inTime, 
            'msg' => $msg
        );
    }
}