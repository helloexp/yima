<?php 
class MarketCommonModel extends Model{
    protected $tableName = '__NONE__';
	/**
	 * [checkMarketingName 校验活动名称是否重复]
	 * @param  [type] $name      [活动名称]
	 * @param  [type] $batchType [活动类型]
	 * @param  [type] $nodeId    [机构号]
	 * @param  [type] $mId       [活动id]
	 * @return [type]            [0 or 1]
	 */
	public function checkMarketingName($name,$batchType,$nodeId,$mId)
	{
		$MarketingInfoModel = M('TmarketingInfo');
        $condition = array(
			'name'       => $name,
			'batch_type' => $batchType,
			'node_id'    => $nodeId
        );
        if (!empty($mId)) {
            $condition['id'] = array('neq', $mId);
        }
        return $MarketingInfoModel->where($condition)->count('id');
	}

	/**
	 * [getMarketInfo 获取活动信息]
	 * @param  [type] $nodeId [机构号]
	 * @param  [type] $m_id   [活动id]
	 * @return [type]         [活动信息]
	 */
	public function getMarketInfo($nodeId,$m_id)
	{
        if(!$m_id)
        {
            return array();
        }
		$MarketingInfoModel = M('TmarketingInfo');
        $condition = array(
			'node_id' => $nodeId,
			'id'      => $m_id
        );
        return $MarketingInfoModel->where($condition)->find();
	}
    /**
     * [publishOne 发布到多乐互动专用渠道]
     * @return [type] [BOOL]
     */
    public function chPublish($nodeId,$m_id){
        if(!$nodeId || !$m_id)
        {
            die('参数错误');
        }
        // 判断该商户是否创建了多乐互动专用渠道
        $chMap = [
            'node_id'    => $nodeId,
            'type'       => '6',
            'sns_type'   => '63',     // 63为多乐互动专用渠道类型
            'status'     => '1',      // 状态正常
            'batch_type' => '-1'        // 表示不限活动类型
            ];
        $chInfo = M('tchannel')->where($chMap)->find();
        if(empty($chInfo))
        {
            $chMap['name']      = '多乐互动专用渠道';
            $chMap['add_time']  = date('YmdHis');
            $chId = M('tchannel')->add($chMap);
            if(!$chId){
                log_write('商户：'.$nodeId.'创建营销互动专用渠道失败');
                return false;
            }
        }else{
            $chId = $chInfo['id'];
        }
        // 将金猴闹新春发布到此渠道上
        $bchMap = [
            'batch_type' => M('tmarketing_info')->getFieldById($m_id,'batch_type'),  // 金猴闹春活动类型
            'batch_id'   => $m_id,   // 金猴闹春活动ID
            'channel_id' => $chId,                  // 多乐互动专用渠道id
            'node_id'    => $nodeId,   // 商户机构号
            'status'     => '1',                    // 当前渠道的状态
        ];
        $bchInfo = M('tbatch_channel')->where($bchMap)->find();
        if(empty($bchInfo))
        {
            $bchMap['add_time'] = date('YmdHis');
            $bchId = M('tbatch_channel')->add($bchMap);
            if(!$bchId){
                log_write('商户：'.$nodeId.'发布到营销互动专用渠道失败');
                return false;
            }
        }else{
            $bchId = $bchInfo['id'];
        }
        return $bchId;
    } 
	public function checkLimitDay($nodeInfo, $m_id, $actStartTime, $actEndTime) {

        // 当前活动订单的信息
        $currentOrderinfo = $this->getFreeUserOrderInfo($nodeInfo['node_id'], $m_id);
        // 判断是否是免费用户
        if(in_array($nodeInfo['wc_version'],array('v0','v0.5')))
        {
        	$isFreeUser = true;
        }else{
        	$isFreeUser = false;
        }
        //付了款的，并且当前时间超过活动开始时间时
        //活动已经开始时，就不能修改开始时间，判断结束时间是否在极限时间之内
        if ($isFreeUser && $currentOrderinfo && $currentOrderinfo['pay_status'] == '1') {
            $originStartTime = M('tmarketing_info')->where(array('id' => $m_id))->getField('start_time');
            if (time() > strtotime($originStartTime)) {
                //检查要保存的时间是不是极限时间之前
                $actStartTimeStamp = strtotime($originStartTime);
                $freeUseLimitTimeStamp = $actStartTimeStamp + ($currentOrderinfo['detail']['available_days']*24*60*60 - 1);
                $freeUseLimitDate = date('YmdHis', $freeUseLimitTimeStamp);
                if ($actEndTime > $freeUseLimitDate) {
                    throw_exception('超出保存时间范围!');
                }
            } else {
            	//活动未开始时，判断修改的活动时长是否在购买的时长范围内
                //$firstFreeActivity = $this->getFirstFreeActivity($nodeId);
                $limitDuringDay = $this->getLimitDuringTime($m_id, $nodeInfo['node_id']);
                $readyDuringDay = (int)((strtotime($actEndTime) - strtotime($actStartTime) + 1) / 86400);
                if ($limitDuringDay < $readyDuringDay) {
                    throw_exception('超出保存时间范围!');
                }
            }
        }
    }

    public function getFreeUserOrderInfo($nodeId, $mId) {
    	if(!$mId) return array();
        $result = M('tactivity_order')->where(array(
				'm_id'       => $mId,
				'node_id'    => $nodeId, 
				'pay_status' => '1'
        ))->field('id')->find();
        $orderInfo = false;
        if ($result) {
            $orderInfo = D('BindChannel')->getOrderInfo($result['id'], $nodeId);
        }
        return $orderInfo;
    }
    /**
     * 获得活动极限持续天数
     * @param unknown $firstFreeActMId
     * @param unknown $m_id
     * @param unknown $nodeId
     * @return string
     */
    public function getLimitDuringTime($m_id, $nodeId) {
        $freeUseLimit = ''; 
        if ($m_id) {
            $orderInfo = $this->getFreeUserOrderInfo($nodeId, $m_id);
            $freeUseLimit = $orderInfo['detail']['available_days'];
        }
        return $freeUseLimit;
    }
    public function checkIsPublish($nodeId,$m_id){
        $marketInfo = M('tmarketing_info')->where(array('node_id'=>$nodeId,'id'=>$m_id))->find();
        $nodeInfo = get_node_info($nodeId);
        $batch_type = $marketInfo['batch_type'];
        // 会员管理，需要认证
        if ($batch_type == '52') {
            if ($nodeInfo['wc_version'] == 'v0') {
                return false;
            }
        }
        
        // 如果开通了多宝电商权限，那么下面的活动都可以发布
        if (self::hasPayModule('m2', $nodeId) && in_array($batch_type, 
            array(
                '26', 
                '27', 
                '29', 
                '31', 
                '41', 
                '55'))) {
            return;
        }
        if (D('BindChannel')->isInFreeUserBuyList($batch_type)) {
            return true;
        }
        if ($nodeInfo['wc_version'] == 'v9' && self::hasPayModule('m1', $nodeId)) {
            return true;
        } else {
            $bRet = D('BindChannel')->isPaid($nodeId, $m_id);
            if (! $bRet) {
                return false;
            }
        }
        return true;
    }
    /**
     * [hasPayModule 是否有付费模块权限] [strModule 逗号隔开的模块,如'm0,m1,m2']
     *
     * @return boolean [description]
     */
    protected function hasPayModule($strModule, $node_id = null) {
        if ($node_id) {
            $this->nodeInfo = get_node_info($node_id);
            $this->wc_version = $this->nodeInfo['wc_version'];
        }
        if ($this->wc_version == 'v4') {
            return true;
        }
        $strModule = trim($strModule, ',');
        if (empty($strModule)) {
            $this->error('hasMudlePower:参数不得为空');
        }
        $arrModule = explode(',', $strModule);
        $payModule = explode(',', trim($this->nodeInfo['pay_module'], ','));
        if (empty($payModule)) {
            return false;
        }
        foreach ($arrModule as $k => $v) {
            if (! in_array($v, $payModule)) {
                return false;
            }
        }
        
        return true;
    }
}
