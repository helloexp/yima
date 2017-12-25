<?php 
class SpringMonkeyVDT extends BaseVDT{

	public function _init(){
		import('@.Vendor.CommonConst') or die('CommonConst is not found!');
	}
	
	public function setActBasicInfo(){
		// 常规校验----------------
		$alias = array(
			'act_name'       =>'用户名',
			'act_time_from'  =>'活动开始时间',
			'act_time_to'    =>'活动结束时间',
			'introduce'      =>'活动说明',
			'node_name'      =>'商户名称',
			'cj_button_text' =>'抽奖按钮文字',
			'share_descript' =>'分享描述',
			'share_title'    =>'分享标题',
			);
		$rules = array(
			'act_name'       =>array('null' => false),
			'act_time_from'  =>array('null' => false,'strtype' => 'datetime','format' => 'Ymd'),
			'act_time_to'    =>array('null' => false,'strtype' => 'datetime','format' => 'Ymd'),
			'introduce'      =>array('null' => true, 'maxlen_cn' => '1000'),
			'node_name'      =>array('null' => true, 'maxlen_cn' => '15'),
			'cj_button_text' =>array('null' => false, 'maxlen_cn' => '6'),
			'share_descript' =>array('null' => true, 'maxlen_cn' => '140'),
			'share_title'    =>array('null' => true, 'maxlen_cn' => '6'),
			);
		// 防止换行出错
		$this->config['share_descript'] = str_replace('<br>', '', my_nl2br(get_val($this->config,'share_descript')));
		$this->setAlias($alias)->setRules($rules)->run();

		// 特殊校验->活动名称是否重复
        $ret = D('MarketCommon')->checkMarketingName(
        	get_val($this->config,'act_name'), 
        	CommonConst::BATCH_TYPE_SPRINGMONKEY, 
        	$this->nodeInfo['node_id'], 
        	get_val($this->config,'m_id'));
        if($ret)
        {
        	$this->errorInfo[] = '活动名称['.get_val($this->config,'act_name').']已存在';
        }

		return $this;
	}

	public function setActConfig(){
		// 常规校验----------------
		$alias = array(
			'join_mode'          =>'参与方式',
			'phone_total_count'  =>'每个手机总中奖次数',
			'phone_day_count'    =>'每个手机每天中奖次数',
			'phone_total_part'   =>'每个手机总抽奖次数',
			'phone_day_part'     =>'每个手机每天抽奖次数',
			'm_id'               =>'营销活动号',
			'is_limit'           =>'参与限制',
			'is_limit_zj'        =>'中奖限制',
			'fans_collect_url'   =>'微信粉丝招募活动url',
			'member_reg_mid'     =>'会员招募活动id',
			'member_batch_id'    =>'允许参加活动的会员分组id',
			'member_batch_id_zj' =>'允许中奖的会员分组id',
			'wx_auth_type'       =>'微信授权',
			);
		$rules = array(
			'join_mode'          =>array('null'=>true, 'strtype'=>'int', 'minval'=>'0','maxval'=>'1'),
			'phone_total_count'  =>array('null'=>false,'strtype'=>'int', 'minval'=>'0'),
			'phone_day_count'    =>array('null'=>false,'strtype'=>'int', 'minval'=>'0'),
			'phone_total_part'   =>array('null'=>false,'strtype'=>'int', 'minval'=>'0'),
			'phone_day_part'     =>array('null'=>false,'strtype'=>'int', 'minval'=>'0'),
			'm_id'               =>array('null'=>false,'strtype'=>'int'),
			'is_limit'           =>array('null'=>false,'strtype'=>'int','minval'=>'0','maxval'=>'1'),
			'is_limit_zj'        =>array('null'=>false,'strtype'=>'int','minval'=>'0','maxval'=>'1'),
			'fans_collect_url'   =>array('null'=>true, 'maxlen' => '250'),
			'member_reg_mid'     =>array('null'=>true),
			'member_batch_id'    =>array('null'=>true),
			'member_batch_id_zj' =>array('null'=>true),
			'wx_auth_type'       =>array('null'=>false,'strtype'=>'int','minval'=>'0','maxval'=>'1')
			);

		$this->setAlias($alias)->setRules($rules)->run();

		// 特殊校验----------------
        if (   $this->config['phone_day_count'] 	> 0 
        	&& $this->config['phone_total_count'] 	> 0 
        	&& $this->config['phone_day_count'] 	> $this->config['phone_total_count']
        	) 
        {
        	$this->errorInfo[] = '日中奖次数不能大于总中奖次数';
        }
        if (   $this->config['phone_day_part'] 		> 0 
        	&& $this->config['phone_total_part'] 	> 0 
        	&& $this->config['phone_day_part'] 		> $this->config['phone_total_part']
        	) 
        {
        	$this->errorInfo[] = '日参与次数不能大于总参与次数';
        }
        
		return $this;
	}
}

