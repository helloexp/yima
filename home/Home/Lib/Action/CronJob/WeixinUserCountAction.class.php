<?php

class WeixinUserCountAction extends Action {
    public $nodeId;
    public $node_wx_id;

    /**
     * @var WeiXinFansGroupService
     */
    public $WeiXinFansGroupService;
    public function _initialize()
    {
        $this->WeiXinFansGroupService = D('WeiXinFansGroup', 'Service');
    }
    
    public function set_twx_user_stat() {
        set_time_limit(0);
        $weixinInfo = M('tweixin_info')->where("auth_flag = 1 and account_type in(2,4)")->select();
        foreach($weixinInfo as $val){
            log_write('处理机构：'.$val['node_id']);
            $this->nodeId = $val['node_id'];
            $this->node_wx_id = $val['node_wx_id'];
            $this->WeiXinFansGroupService->init($this->nodeId);
            $result = $this->WeiXinFansGroupService->apiGetusersummary(strtotime('-1 day'),strtotime('-1 day'));
            $result2 = $this->WeiXinFansGroupService->apiGetusercumulate(strtotime('-1 day'),strtotime('-1 day'));
            // dump($result);exit;
            $arr = array_valtokey($result2['list'],'ref_date');
            foreach($result['list'] as $val){
                $arr[$val['ref_date']]['new_user'] += $val['new_user'];
                $arr[$val['ref_date']]['cancel_user'] += $val['cancel_user'];
            }
            foreach($arr as &$val){
                $val['node_id'] = $this->nodeId;
                $val['node_wx_id'] = $this->node_wx_id;
                $val['ref_date'] = dateformat($val['ref_date'],'Ymd');
                if(!isset($val['new_user'])){
                    $val['new_user'] = 0;
                    $val['cancel_user'] = 0;
                }
                M('twx_user_count')->add($val);
            }
        }
    }
}
