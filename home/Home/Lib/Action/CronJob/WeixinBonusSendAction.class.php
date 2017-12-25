<?php
class WeixinBonusSendAction extends Action{
    public function doBonusSend(){
        set_time_limit(0);
        // log_write(111);
        // 检查同步标志
        $sync_flag = M('tsystem_param')->where(
            "param_name ='WX_BONUS_SEND_FLAG'")->find();
        if (! $sync_flag) {
            log_write("get WX_BONUS_SEND_FLAG not exit");
            return;
        }
        
        if ($sync_flag['param_value'] != '1') {
            log_write("the WX_BONUS_SEND_FLAG is syncing");
            return;
        }
        // 更新同步标志
        $sync_flag_save['param_value'] = '2';
        $rs = M('tsystem_param')->where("param_name ='WX_BONUS_SEND_FLAG'")->save(
            $sync_flag_save);
        if ($rs === false) {
            log_write("update WX_BONUS_SEND_FLAG fail" . M()->_sql());
            return;
        }
        
        // 获取待发送批次
        $msg_batch_list = M('twx_bonus_send')->where(
            "status ='0' AND send_time < '" .
                 date('YmdHis') . "'")->select();
        if($msg_batch_list){
            foreach ($msg_batch_list as $msg_batch) {
                log_write("get m_id  :[" . $msg_batch['m_id'] . "]");
                $batchSendDetail = M('twx_bonus_send_detail')->where('bonus_send_id = '.$msg_batch['id'])->select();
                //插入发送流水表-微信红包
                foreach($batchSendDetail as $val){
                    $request_id = $this->award_reqid();
                    $sendAwardTrace['trans_type'] = '2';
                    $sendAwardTrace['node_id'] = $val['node_id'];
                    $sendAwardTrace['phone_no'] = $val['open_id'];
                    $sendAwardTrace['request_id'] = $request_id;
                    $sendAwardTrace['batch_info_id'] = $msg_batch['b_id'];
                    $sendAwardTrace['deal_flag'] = 1;
                    $sendAwardTrace['m_id'] = $msg_batch['m_id'];
                    $sendAwardTrace['add_time'] = date('YmdHis');
                    $rs = M('tsend_award_trace')->add($sendAwardTrace);
                    if (! $rs) {
                        log_write("进入发送流水表[tsend_award_trace]失败" . M()->_sql());
                    }else{
                        M('tbatch_info')->where("id=".$batchSendDetail['b_id'])->setDec('remain_num');
                    }
                }unset($val);
                M('twx_bonus_send')->where('id = '.$msg_batch['id'])->save(array('status'=>2));
            }unset($msg_batch);
        }
        // 更新完成恢复同步标志
        $sync_flag_save['param_value'] = '1';
        $rs = M('tsystem_param')->where("param_name ='WX_BONUS_SEND_FLAG'")->save(
            $sync_flag_save);
        if (! $rs) {
            log_write("update WX_BONUS_SEND_FLAG fail" . M()->_sql());
            return;
        }
    }
    
    function award_reqid(){
        $data = M()->query("SELECT _nextval('award_send_seq') as reqid FROM DUAL");
        if (! $data) {
            log_write('award_send_seq fail!');
            $req = rand(1, 999999);
        } else {
            $req = $data[0]['reqid'];
        }
        return date('YmdHis') . str_pad($req, 6, '0', STR_PAD_LEFT);
    }
}
?>