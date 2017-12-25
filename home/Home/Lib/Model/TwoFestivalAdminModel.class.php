<?php

/**
 *
 * @author lwb last edit Time 20151223
 */
class TwoFestivalAdminModel extends OrderActivityAdminModel {
    protected $tableName = '__NONE__';
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
     * @return string 购买的结束极限日期
     */
    public function getLimit($m_id, $nodeId,$tmp1=null,$tmp2=null) {
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
     * @return string
     */
    public function getLimitDuringTime($m_id, $nodeId,$tmp1=null,$tmp2=null) {
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
}