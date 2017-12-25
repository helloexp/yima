<?php 
/**
 * 多乐互动基类
 */
class MarketBaseAction extends BaseAction{

	public function _initialize() {
        parent::_initialize();

        $this->assign('left_menu_data',self::getLeftMenuData());
    }

    /**
     * [getLeftMenuData 设置左侧菜单栏数据]
     */
    private function getLeftMenuData(){
    	$data = cookie('left_menu_data'.$this->nodeId);
    	if(!empty($data) && ACTION_NAME != 'index'){
    		return $data;
    	}
    	$map = array();
    	$map['node_id']    = $this->nodeId;
        $map['batch_type'] =  array('in',implode(array_keys(self::getBatchType()), ','));

    	$field = "SUM(click_count) AS  pv,SUM(uv_count) AS uv,SUM(verify_count) AS iv";
    	
    	$list = M('tdaystat')->field($field)->where($map)->select();
    	
    	$map['status'] = '1';
    	if(!$this->hasPayModule('m1')){
	    	$map['pay_status'] = array('NEQ','0');
    	}
    	$map['start_time'] = array('ELT',date('Ymd000000'));
    	$map['end_time']   = array('EGT',date('Ymd235959'));

    	$marketCnt = M('tmarketing_info')->where($map)->count();

    	$data = array_merge($list[0],array('mCnt'=>$marketCnt));
    	// 存入cookie，防止重复查询
    	cookie('left_menu_data'.$this->nodeId,$data,360);

    	return $data;
    }

    // 获取活动类型
    // $flag = false 表示只获取活动类型及名称
    // $flag = 1 表示按照分类获取活动类型
    // $flag = 2 表示按照分类获取活动所有相关信息
    protected function getBatchType($flag = false){
    	if($flag == 1){
    		// 检查是否已查询过
	    	$batchType = session('batch_type_belong');
	    	if(!empty($batchType))
	    		return $batchType;
	    	// 查询活动类型
	        $map = array();
	        $map['status'] = '1';
	        $info = M('tmarketing_active')->field('batch_type,batch_name,batch_belongto,batch_order')->where($map)->order('batch_order asc')->select();
	        $batchType = array();
	        foreach ($info as $v) {
                $batch_belongto = $v['batch_belongto'] == '4'? 1:2;
	            $batchType[$batch_belongto][$v['batch_type']] = $v['batch_name'];
	            $batchType[0][$v['batch_type']] = $v['batch_name'];
	            // 只有翼码市场部的可以看到注册有礼
	            if($v['batch_type'] == '32' && $this->node_id != '00014056'){
                    unset($batchType[$batch_belongto][$v['batch_type']]);
	                unset($batchType[0][$v['batch_type']]);
	            }
	        }
	        session('batch_type_belong',$batchType); // 存入session，避免重复查询
	        return $batchType;
    	}elseif($flag == 2){
            // 检查是否已查询过
            $batchType = session('batch_type_belong_all');
            if(!empty($batchType))
                return $batchType;
            // 查询活动类型
            $map = array();
            $map['status'] = '1';
            $info = M('tmarketing_active')->where($map)->order('batch_order asc')->select();
            $batchType = array();
            foreach ($info as $v) {
                //每种活动使用的支付模型
                $payModel = D('ActivityPayConfig')->getDefaultPayConfigModelByBatchType($v['batch_type']);
                $v['pay_config'] = json_decode($payModel, true);
                //如果是大转盘活动或欧洲杯活动，有一个“首次免费”的提示
                if ($v['batch_type'] == CommonConst::BATCH_TYPE_WEEL) {
                    //是否有免费的大转盘订单
                    $hasFreeOrder = D('MarketActive')->hasFreeActivity($this->node_id, $v['batch_type']);
                    $v['show_free_tip'] = $hasFreeOrder ? '0' : '1';
                }
                $batch_belongto = $v['batch_belongto'] == '4'? 1:2;
                $batchType[$batch_belongto][$v['batch_type']] = $v;
                $batchType[0][$v['batch_type']] = $v;
                // 只有翼码市场部的可以看到注册有礼
                if($v['batch_type'] == '32' && $this->node_id != '00014056'){
                    unset($batchType[$batch_belongto][$v['batch_type']]);
                    unset($batchType[0][$v['batch_type']]);
                }
            }
            session('batch_type_belong_all',$batchType); // 存入session，避免重复查询
            return $batchType;
        }else{
	    	// 检查是否已查询过
	    	$batchType = session('batch_type');
	    	if(!empty($batchType))
	    		return $batchType;
	    	// 查询活动类型
	        $map = array();
	        $map['status'] = '1';
	        $info = M('tmarketing_active')->field('batch_type,batch_name,batch_order')->where($map)->order('batch_order asc')->select();
	        $batchType = array();
	        foreach ($info as $v) {
	            $batchType[$v['batch_type']] = $v['batch_name'];
	            // 只有翼码市场部的可以看到注册有礼
	            if($v['batch_type'] == '32' && $this->node_id != '00014056'){
	                unset($batchType[$v['batch_type']]);
	            }
	        }
	        session('batch_type',$batchType); // 存入session，避免重复查询
	        return $batchType;
    	}
    }
    /**
     * [getMarketName 获取所有的活动名称]
     * @return [type] [array]
     */
    protected function getMarketName(){
    	$data = S('batch_name_'.$this->nodeId);
    	if(!empty($data)){
    		return $data;
    	}
    	$map = array();
    	$map['node_id'] = $this->nodeId;
    	$map['batch_type'] = array('in',implode(array_keys(self::getBatchType()),','));
    	$res = M('tmarketing_info')->field('id,name')->where($map)->select();
    	$ret = array();
    	if(!empty($res)){
    		foreach ($res as $v) {
    			$ret[$v['id']] = $v['name'];
    		}
    	}
    	S('batch_name_'.$this->nodeId,$ret,0,360);
    	return $ret;
    }

    /**
     * [getDefaultCId 获取默认多乐互动渠道的id]
     * @return [type] [description]
     */
    protected function getDefaultCId(){
    	$channelId = session('default_channel_id'.$this->nodeId);
    	if(!empty($channelId)){
    		return $channelId;
    	}
    	$map = array(
			'node_id'  => $this->nodeId,
			'type'     => '6',
			'sns_type' => '63',
			'status'   => '1'
    		);
    	$channelId = M('tchannel')->where($map)->getField('id');
    	session('default_channel_id'.$this->nodeId,$channelId);
    	return $channelId;
    }

}

