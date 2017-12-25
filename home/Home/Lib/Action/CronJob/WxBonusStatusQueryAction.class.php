<?php

class WxBonusStatusQueryAction extends Action{
    public $nodeId;
    
    public $status;

    /**
     * @var WxBonusStatusService
     */
    public $WxBonusStatusService;

    public function _initialize()
    {
        $this->WxBonusStatusService = D('WxBonusStatus', 'Service');
    }

    public function statusQuery(){
        set_time_limit(0);
        $wxBonusSendTrace = M('twx_bonus_send_trace bst')
                ->field('bst.id,bst.mch_billno,bsp.appid,bsp.mch_id,bst.node_id')
                ->join('tnode_wxpay_config bsp ON bsp.node_id=bst.node_id')
                ->where("bst.status in(0,3)")
                ->select();
        foreach ($wxBonusSendTrace as $val){

            $this->nodeId = $val['node_id'];
            $this->status = $val['status'];
            $this->WxBonusStatusService->init($val['node_id']);
                
            //dump($val['mch_billno']);exit;
            $result = $this->WxBonusStatusService->apiGethbinfo(
                    $val['mch_billno']
            );
            if ($result == false) {
                log_write($this->WxBonusStatusService->errCode . $this->WxBonusStatusService->error);
            } else {
                log_write('执行apiGethbinfo之后：'.var_export($result, true));

                switch ($result['status'] ) {
                    case "SENDING":
                        $result['status'] = '0';
                        break;
                    case "SENT":
                        $result['status'] = '3';
                        break;
                    case "FAILED":
                        $result['status'] = '1';
                        break;
                    case "RECEIVED":
                        $result['status'] = '4';
                        break;
                    case "REFUND":
                        $result['status'] = '5';
                        break;
                    default:
                        $result['status'] = $val['status'];
                }
                $rs = M('twx_bonus_send_trace')
                        ->where(array(
                                'id' => $val['id'],
                                'status' => array('neq', $result['status'])
                        ))
                        ->setField('status',$result['status']);
                if ($rs === false) {
                    log_write("update status fail" . M()->_sql());
                    return;
                }

            }
        }
    }

}
