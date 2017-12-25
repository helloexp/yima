<?php 
class SpringMonkeyModel extends Model{
	protected $tableName = '__NONE__';
	const WX_ACCOUNT_TYPE_CERTIFIED = '4';//已认证的服务号
	public function _initialize()
	{
		import('@.Vendor.CommonConst') or die('CommonConst is not found!');
	}
	
	public function saveMarketInfo($data,$nodeInfo)
	{
		// 处理空格
		foreach ($data as $key => $value) {
            $data[$key] = trim($value);
        }
		$model = M();
		$model->startTrans();
		$marketModel = M('tmarketingInfo');
		$readyData = array(
			'name'       => $data['act_name'],
			'node_id'    => $nodeInfo['node_id'],
			'node_name'  => $data['node_name'],
			'wap_info'   => $data['introduce'],
			'log_img'    => isset($data['node_logo']) ? get_upload_url($data['node_logo']) : '',
			'start_time' => $data['act_time_from'] . '000000',
			'end_time'   => $data['act_time_to'] . '235959',
			'add_time'   => date('YmdHis'),
			'status'     => '1',
			'batch_type' => CommonConst::BATCH_TYPE_SPRINGMONKEY,
			'bg_pic'     => get_val($data,'bg_pic',''),
			'is_show'    => '1',
			'is_cj'      => '1',
			'share_pic'  => get_val($data,'share_pic',''),
		);
		if (!get_val($data,'m_id')) {
			$readyData['pay_status']  = '0';
			$readyData['join_mode']   = '0';//默认选1，下一步再改
			$readyData['config_data'] = serialize(
				array(
					'share_descript' => get_val($data,'share_descript',''),
					'share_title'    => get_val($data,'share_title','')
					)
				);
			$data['m_id'] = $marketModel->add($readyData);
			if (!$data['m_id']) {
				$model->rollback();
				log_write($nodeInfo['node_id'].'金猴闹春-新增活动失败!');
				return array('status'=>0,'error'=>'新增活动失败');
			}
			// 如果是新增把默认的抽奖配置填上
			$ruleParam = array(
				'batch_type'        => CommonConst::BATCH_TYPE_SPRINGMONKEY,
				'batch_id'          => $data['m_id'],
				'jp_set_type'       => 2, // 1单奖品2多奖品
				'node_id'           => $nodeInfo['node_id'],
				'add_time'          => date('YmdHis'),
				'status'            => '1',
				'phone_total_count' => '',
				'phone_day_count'   => '',
				'phone_total_part'  => '',
				'phone_day_part'    => '',
				'cj_button_text'    => get_val($data,'cj_button_text',''),
				'cj_resp_text'      => '恭喜您！中奖了', // 中奖提示信息
				'param1'            => '',
				'no_award_notice'   => '很遗憾！未中奖'
			);
			$flag = M('tcj_rule')->add($ruleParam);
			if (!$flag) {
				$model->rollback();
				return array('status'=>0,'error'=>'新增默认抽奖失败');
			}
			$cateData = array(
				'batch_type' => CommonConst::BATCH_TYPE_SPRINGMONKEY, 
				'batch_id'   => $data['m_id'], 
				'node_id'    => $nodeInfo['node_id'], 
				'cj_rule_id' => $flag, 
				'name'       => '一等奖', 
				'add_time'   => date('YmdHis'), 
				'status'     => '1', 
				'sort'       => '1');
            $cat_id = M('tcj_cate')->add($cateData);
            if (!$cat_id) {
                $model->rollback();
                return array('status'=>0,'error'=>'新增奖项失败');
            }
		} else {
			//如果状态是付费中(不能让他修改时间);
			$isInPay = D('Order')->isInPay($nodeInfo['node_id'], $data['m_id']);
			if (!empty($isInPay)) {
				return array('status'=>0,'error'=>'订单已生成,活动时间不可更改。如果更改订单时间请取消订单。');
			}
			// 检查是否有没有超过购买的期限
			try {
				D('MarketCommon')->checkLimitDay($nodeInfo, $data['m_id'], $readyData['start_time'], $readyData['end_time']);
			} catch ( Exception $e ) {
				return array('status'=>0,'error'=>$e->getMessage());
			}
			$configDataArr['share_descript'] = get_val($data,'share_descript','');
			$configDataArr['share_title'] 	 = get_val($data,'share_title','');
			$configDataArr['wx_auth_type'] 	 = get_val($data,'wx_auth_type','');
			$readyData['config_data'] = serialize($configDataArr);
			$flag = $marketModel->where(array(
					'id' => $data['m_id']
			))->save($readyData);
			if (false === $flag) {
				$model->rollback();
				return array('status'=>0,'error'=>'保存活动失败!');
			}
			//抽奖开关的文字
			$cjRuleData['cj_button_text'] = get_val($data,'cj_button_text','');
			$cjRuleResult = M('tcj_rule')
			->where(array('batch_id' => $data['m_id'], 'status' => '1', 'node_id' => $nodeInfo['node_id']))
			->save($cjRuleData);
			if (false === $cjRuleResult) {
				$model->rollback();
				return array('status'=>0,'error'=>'保存抽奖按钮文字失败!');
			}
		}
		$model->commit();
		return array('status'=>1,'id'=>$data['m_id']);
	}

	public function handleMarketInfo($marketInfo,$nodeInfo)
	{
		$retInfo = array();

		if(!empty($marketInfo))
		{
			$configData = unserialize($marketInfo['config_data']);
			$cj_button_text = M('tcj_rule')->where(array('node_id' => $nodeInfo['node_id'], 'batch_id' => $marketInfo['id'], 'status' => '1'))->getField('cj_button_text');
			$retInfo = array(
				'act_name'      => $marketInfo['name'],
				'introduce'     => $marketInfo['wap_info'],
				'node_logo'     => $marketInfo['log_img'],
				'act_time_from' => substr($marketInfo['start_time'],0,8),
				'act_time_to'   => substr($marketInfo['end_time'],0,8),
				'cj_button_text'=> $cj_button_text
				);
			return array_merge($marketInfo,$configData,$retInfo);
		}
		
		if(empty($nodeInfo['head_photo']))
		{
			$retInfo['node_logo'] = '__PUBLIC__/Image/wap-logo-wc.png';
		}else{
			$retInfo['node_logo'] = get_upload_url($nodeInfo['head_photo']);
		}
		$retInfo['share_pic']	  = C('CURRENT_DOMAIN') .'Home/Public/Label/Image/20160208/icon1.png';
		$retInfo['node_name']     = $nodeInfo['node_name'];
		$retInfo['node_id']       = $nodeInfo['node_id'];
		$retInfo['act_name']      = '金猴闹新春';
		$retInfo['act_time_from'] = date('Ymd');
		$retInfo['act_time_to']   = date('Ymd',strtotime('+59 days'));

		return $retInfo;
	}
	public function handleOthers($retInfo)
	{
		// 判断参数是否存在，不存在则初始化
		$retInfo['id'] = get_val($retInfo,'id');
		$retInfo['start_time'] = get_val($retInfo,'start_time');
        // 设置页面头部
        $retInfo['isReEdit'] = $retInfo['id'] ? '1' : '0';
        $retInfo['stepBar']  = D('CjSet')->getActStepsBar(ACTION_NAME, $retInfo['id'], '', $retInfo['isReEdit']);

        $retInfo['needShowTips'] = D('OrderActivityAdmin')->needShowExTips($retInfo['node_id'],$retInfo['id']);

        $retInfo['type'] = 2;
        $currentOrderinfo = D('MarketCommon')->getFreeUserOrderInfo($retInfo['node_id'],$retInfo['id']);
        // 付了款的，并且当前时间超过活动开始时间
        if (!empty($currentOrderinfo) && $currentOrderinfo['pay_status'] == '1' && time() > strtotime($retInfo['start_time'])) 
        { 
            $actStartTimeStamp = strtotime($currentOrderinfo['detail']['act_start_time']);
            $freeUseLimit = $actStartTimeStamp + ($currentOrderinfo['detail']['available_days']*24*60*60 - 1);
            // 1表示付款了的,控件需要disabled
            $retInfo['type'] = 1;
            $retInfo['freeUseLimit'] = date('Y-m-d', $freeUseLimit);
        }
        // 未付款的时候,需要提示有没有超过60,超过部分要另外收费
        $paySettingJson = D('ActivityPayConfig')->getDefaultPayConfigModelByBatchType(CommonConst::BATCH_TYPE_SPRINGMONKEY);
        $paySetting = json_decode($paySettingJson, true);
        $retInfo['configOneActDays'] = get_val($paySetting,'duringTime','');
        $retInfo['exPrice'] = get_val($paySetting,'exPrice','');
        return $retInfo;
	}

	public function saveActConfig($data,$nodeInfo)
	{
		// 营销活动必须存在
		$batchInfo = M('tmarketing_info')
        	->where(array('id' => $data['m_id'], 'node_id' => $nodeInfo['node_id']))
        	->find();
        if (!$batchInfo) {
        	return array('status'=>0,'error'=>'未找到该营销活动!');
        }

        // 该活动有抽奖记录，则不允许变更参与方式
    	if($batchInfo['join_mode'] != $data['join_mode']){
            $cnt = M('tcj_trace')->where(array('batch_id' => $this->config['m_id']))->count();
            if(intval($cnt) > 0){
            	return array('status'=>0,'error'=>'营销活动已经有抽奖记录了，不允许变更参与方式!');
            }
        }
		// 总的限制和不限制的标识，初始值为不限
		$data['member_join_flag']   = 0;
        $data['member_batch_id']    = ($data['is_limit'] == 1) ? $data['member_batch_id'] : 0;
        $data['member_batch_id_zj'] = ($data['is_limit_zj'] == 1) ? $data['member_batch_id_zj'] : 0;
        // 如果参与方式为手机号码
        if ($data['join_mode'] == '0') {
            if ($data['is_limit'] == '1' && !empty($data['member_reg_mid'])) {
                $map = array(
					'm.node_id'    => $nodeInfo['node_id'],
					'm.batch_type' => CommonConst::BATCH_TYPE_RECRUIT,
					'm.status'     => '1',
					'm.re_type'    => '0',
					'm.start_time' => array('elt',date('YmdHis')),
					'm.end_time'   => array('egt',date('YmdHis')),
					'm.id'         => $data['member_reg_mid'],
					'c.channel_id' => array('exp','is not null'),
					'c.status'     => '1'
                	);
                $count =M()->table("tmarketing_info m")->join('tbatch_channel c ON m.id=c.batch_id')
                	->where($map)
                	->count();
                if (!$count) {
                	return array('status'=>0,'error'=>'无效的会员招募活动!'); 
                }
            }
            if ($data['member_batch_id'] != '' && $data['member_batch_id'] != '0') {
                $arr = explode($data['member_batch_id']);
                $map = array(
					'node_id' => $nodeInfo['node_id'],
					'status'  => '1',
					'id'      => array('in',$arr)
	                );
                $cnt = M('tmember_batch')->where($map)->count();
                if($cnt != count($arr)){
                	return array('status'=>0,'error'=>'无效的活动分组!');
                }
            }
            if($data['member_batch_id_zj'] != '' && $data['$member_batch_id_zj'] != '0'){
                $arr = explode($data['member_batch_id_zj']);
                $map = array(
					'node_id' => $nodeInfo['node_id'],
					'status'  => '1',
					'id'      => array('in',$arr)
	                );
                $cnt = M('tmember_batch')->where($map)->count();
                if($cnt != count($arr)){
                	return array('status'=>0,'error'=>'无效的中奖活动分组!');
                }
            }
            $data['fans_collect_url'] = '';
        } elseif ($data['join_mode'] == 1) {
        	//参与方式：微信号
			$data['member_reg_mid']    = '';
			$data['phone_total_count'] = 1;
			$data['phone_day_count']   = 1;
        }

        //由于微信分组的“未分组”的id为0，与原来数据库中member_batch_id为0时表示“不限制”重复，所以现在改为-1表示不限制，0表示微信分组的未分组，为了不影响其他页面传送过来的逻辑，在存数据库之前改这两个值
        if ($data['member_batch_id'] === 0) {
            $data['member_batch_id'] = -1;
        }
        if ($data['member_batch_id_zj'] === 0) {
            $data['member_batch_id_zj'] = -1;
        }
        //如果参与限制或中奖限制有一个有限制，总的member_join_flag就为有限制
        if($data['member_batch_id'] !== -1 || $data['member_batch_id_zj'] !== -1){
            $data['member_join_flag'] = 1;
        }

        //组装金猴闹春活动的config_data
        $configDataArr = unserialize($batchInfo['config_data']);
        $configDataArr['wx_auth_type'] = get_val($data,'wx_auth_type');
        $data['config_data'] = serialize($configDataArr);

        // 营销活动保存的数据
		$marketingData = array(
				'join_mode'             => $data['join_mode'], 
				'member_batch_id'       => $data['member_batch_id'],
				'member_join_flag'      => $data['member_join_flag'], 
				'member_reg_mid'        => $data['member_reg_mid'], 
				'member_batch_award_id' => $data['member_batch_id_zj'], 
				'fans_collect_url'      => $data['fans_collect_url'],
				'config_data'           => $data['config_data']
        );
        // 开始事务
        M()->startTrans();
        $flag = M('tmarketing_info')->where(array('id' => $data['m_id'], 'node_id' => $nodeInfo['node_id']))->save($marketingData);
        if(false === $flag){
            M()->rollback();
			return array('status'=>0,'error'=>'抽奖形式或粉丝专享内容更新失败!');
        }
        // 抽奖规则必须存在且为一条
        $ruleList = M('tcj_rule')->where(array('batch_id' => $data['m_id'], 'status' => '1'))->select();
        if(count($ruleList) != 1)
        {
        	M()->rollback();
			return array('status'=>0,'error'=>'抽奖规则必须存在且为一条！');
        }

        $ruleData = array(
			'phone_total_count' => $data['phone_total_count'],
			'phone_day_count'   => $data['phone_day_count'],
			'phone_total_part'  => $data['phone_total_part'],
			'phone_day_part'    => $data['phone_day_part']
        	);
        $flag = M('tcj_rule')->where(array('id' => $ruleList[0]['id']))->save($ruleData);
        if(false === $flag){
            M()->rollback();
            return array('status'=>0,'error'=>'参与和中奖次数更新失败');
        }
        M()->commit();
        return array('status'=>1,'error'=>'');
	}

	public function handleConfigInfo($marketInfo,$nodeInfo)
	{
		// 会员参与限制的开关值
		$retInfo['member_batch_id_flag'] = $marketInfo['member_batch_id'] == -1 ? 0 : 1;
		// 会员中奖限制
		$retInfo['member_zj_flag'] = $marketInfo['member_batch_award_id'] == -1 ? 0 : 1;
		//是否绑定了会员招募活动的开关值
		$retInfo['phone_recruit'] = $marketInfo['member_reg_mid'] ? 1 : 0;
		//微信招募活动的开关值
		$retInfo['wx_recruit'] = $marketInfo['fans_collect_url'] ? 1 : 0;
		//是否是免费用户(暂时改成是否是c0用户,c0用户不能选会员分组限制)
		$retInfo['isFreeUser'] = $nodeInfo['wc_version'] == 'v0';
		//活动选择的微信授权方式
		$configData = unserialize($marketInfo['config_data']);
		$retInfo['wx_auth_type'] = get_val($configData,'wx_auth_type','')?$configData['wx_auth_type']:0;
		//选择的招募活动的名字
		$retInfo['regName'] = D('CjSet')->getBindedRecruitName($marketInfo['member_reg_mid'], $nodeInfo['node_id']);
		return array_merge($marketInfo,$retInfo);
		
	}

	public function handleOtherConfig($retInfo,$requestInfo)
	{
		// 会员分组
		$retInfo['mem_batch'] = D('CjSet')->getMemberBatch($retInfo['node_id']);
		// 微信分组
		$retInfo['user_wx_group'] = D('CjSet')->getWxGroup($retInfo['node_id']);
		// 参与和中奖的分组设定
		$retArr = D('CjSet')->getSelectedGroup(
			$retInfo['member_batch_id'], 
			$retInfo['member_batch_award_id'], 
			$retInfo['join_mode'], 
			$retInfo['mem_batch'], 
			$retInfo['user_wx_group']
			);
		//如果活动中设置过微信招募活动链接显示，否则显示旺财设置的微信引导页链接
		$retInfo['guidUrl'] = $retInfo['fans_collect_url'] ? $retInfo['fans_collect_url'] : D('TweixinInfo')->getGuidUrl($retInfo['node_id']);
		//未中奖提示语，日中奖，总中奖，日参数
		$cjRuleArr = M('tcj_rule')->where(array('node_id'=>$retInfo['node_id'],'batch_id'=>$retInfo['id'],'status'=>'1'))->find();
        $retInfo['no_award_notice'] = explode('|', $cjRuleArr['no_award_notice']);
        //是否绑定了微信认证服务号
        $retInfo['isWxBd'] = self::isBindWxServ($retInfo['node_id']);
        //是否已经选了微信卡券作为奖品，如果是的话参与方式不能改为手机
        $retInfo['isSelectCard'] = D('DrawLotteryAdmin')->isSelectCard($retInfo['node_id'], $retInfo['id']);
        //是否已经选了微信红包作为奖品，如果是的话参与方式不能改为手机
        $retInfo['isSelectWxHb'] = D('DrawLotteryAdmin')->isSelectWxHb($retInfo['node_id'], $retInfo['id']);
        //为了前端不用改，这里直接用isSelectCard统一判断
        $retInfo['isSelectCard'] = ($retInfo['isSelectCard'] || $retInfo['isSelectWxHb']) ? 1 : 0;
        //抽奖活动步骤条
        $retInfo['isReEdit'] = get_val($requestInfo,'isReEdit')=='0' ? 0 : 1;
		$retInfo['stepBar']  = D('CjSet')->getActStepsBar(ACTION_NAME, $retInfo['id'], '', $retInfo['isReEdit']);
		return array_merge($cjRuleArr,$retInfo,$retArr);
	}

	public function saveActPrize($requestInfo,$nodeInfo){
        $data = [
			'cj_resp_text'    => get_val($requestInfo,'cj_resp_text',''), 
			'no_award_notice' => get_val($requestInfo,'no_award_notice',''), 
			'total_chance'    => get_val($requestInfo,'total_chance',''), 
			'sort'            => get_val($requestInfo,'sort',array()),  // 奖项排序
			'm_id'            => get_val($requestInfo,'m_id',''),
        ];
        try{
        	D('CjSet')->savePrizeConfig($nodeInfo['node_id'], $data);
        }catch(Exception $e){
        	return array('status'=>0,'error'=>$e->getMessage());
        }
        return array('status'=>1,'error'=>'');
	}
	public function handlePrizeInfo($marketInfo,$nodeInfo){
		//是否是免费用户(暂时改成是否是c0用户,c0用户不能选会员分组限制)
		$retInfo['isFreeUser'] = $nodeInfo['wc_version'] == 'v0';
		return array_merge($retInfo,$marketInfo);
	}
	public function handleOtherPrize($retInfo,$requestInfo){
		//免费用户第一次创建大转盘活动时
		$retInfo['firstCreateTips'] = $retInfo['isFreeUser'] && self::getFA($retInfo['node_id']);
        //获取抽奖配置信息
        $cjConfig = D('CjSet')->getCjConfig($retInfo['node_id'], $retInfo['id']);
        $retInfo['canShowAddBtn'] = count($cjConfig['cj_cate_arr']) < 7;
        //抽奖活动步骤条
        $retInfo['isReEdit'] = get_val($requestInfo,'isReEdit') == '0'?$requestInfo['isReEdit']:1;
		$retInfo['stepBar']  = D('CjSet')->getActStepsBar(ACTION_NAME, $retInfo['id'], '', get_val($requestInfo,'isReEdit'));
        return array_merge($cjConfig,$retInfo);


	}
	public function changeStatus($requestInfo,$nodeInfo)
	{
		if(!in_array($requestInfo['status'], ['1','2']))
		{
			return array('status'=>0,'error'=>'状态错误');
		}
		$map = array(
			'node_id'=>$nodeInfo['node_id'],
			'id'=>$requestInfo['batch_id']
			);
		$ret = M('tmarketing_info')->where($map)->save(['status'=>$requestInfo['status']]);
		if(false === $ret)
		{
			return array('status'=>0,'error'=>'修改失败');
		}
		return array('status'=>1,'error'=>'');
	}
	
	/**
     * 是否绑定了微信认证服务号
     * @param string $nodeId
     * @return boolean
     */
    public function isBindWxServ($nodeId) {
        $isBind = false;
        $weixinInfo = M('tweixin_info')->where(array('node_id' => $nodeId))->find();
        //微信已认证服务号并且状态正常的
        if ($weixinInfo && $weixinInfo['account_type'] == self::WX_ACCOUNT_TYPE_CERTIFIED && $weixinInfo['status'] == '0') {
            $isBind = true;
        }
        return $isBind;
    }
    
    public function isBindWxCard($node_id){
        $data = M('tweixin_info');
        //select count(*) from tweixin_info where node_id = 'xxx' and status = '0'  结果>0就通过，=0就不过
        $result = $data->where(
        array('node_id' => $node_id, 'status' => '0')
        )->count();
        return $result;

    }
    /**
     * 获取创建的免费活动
     *
     * @param unknown $nodeId
     * @return boolean
     */
    public function getFA($nodeId) {
        // 是否有免费的订单，有免费的订单的就不是第一次//order_type为2表示免费
        $ret = M('tactivity_order')->where(
            array(
                'node_id' => $nodeId, 
                'order_type' => CommonConst::ORDER_TYPE_FREE, 
                'batch_type' => CommonConst::BATCH_TYPE_SPRINGMONKEY))->find();
        return !empty($ret);
    }
}

