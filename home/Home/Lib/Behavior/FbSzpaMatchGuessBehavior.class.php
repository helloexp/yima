<?php

/**
 * 深圳非标
 */
class FbSzpaMatchGuessBehavior extends Behavior {
	protected $node_id;
    
    public function run(&$params) {
    	$this->node_id=C('szpa.node_id');
        if(!$this->checkNodeId($params['node_id'])){
            return true;
        }
        $res=$this->resetGroup($params);
  		return $res;
    }

    //深圳平安重置粉丝分组
    private function resetGroup($params)
    {
        if(empty($params['openid'])){
        	log_write('深圳平安欧洲杯绑定分组参数错误：'.var_export($params,true)."\n最后SQL:".M()->_sql());
        	return false;
        }
        $wxOpenid = $params['openid'];
        $where    = ['openid' => $wxOpenid, 'node_id' => $this->node_id, 'subscribe' => ['neq', '0']];
        $group_id = C('szpa.matchguess_group_id');
        $ch_where = ['node_id'=>$this->node_id,'id'=>$group_id];
        $check=M('twx_user_group')->where($ch_where)->count('id');
        if(!$check){
        	log_write('深圳平安欧洲杯绑定分组不存在：'.var_export($ch_where,true)."\n最后SQL:".M()->_sql());
        	return false;
        }
        $res=M('twx_user')->where($where)->setField('group_id',$group_id);
        if($res== false){
            log_write('深圳平安欧洲杯绑定分组失败：'.var_export($where,true)."\n最后SQL:".M()->_sql());
        }
        return true;
    }

    /**
     * 校验机构号
     * @param  [type] $node_id [description]
     * @return [type]          [description]
     */
    private function checkNodeId($node_id)
    {
    	return ($this->node_id == $node_id && is_numeric($node_id) && is_numeric($this->node_id) );
    }
}