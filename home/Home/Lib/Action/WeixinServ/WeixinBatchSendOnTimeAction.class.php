<?php
// 微信群发定时设置
class WeixinBatchSendOnTimeAction {

    public function doBatchSendOnTime() {
        set_time_limit(0);
        // 检查同步标志
        $sync_flag = M('tsystem_param')->where(
            "param_name ='WX_BATCH_SEND_FLAG'")->find();
        if (! $sync_flag) {
            $this->log("get WX_BATCH_SEND_FLAG not exit");
            return;
        }
        
        if ($sync_flag['param_value'] != '1') {
            $this->log("the WX_BATCH_SEND_FLAG is syncing");
            return;
        }
        
        // 更新同步标志
        $sync_flag_save['param_value'] = '2';
        $rs = M('tsystem_param')->where("param_name ='WX_BATCH_SEND_FLAG'")->save(
            $sync_flag_save);
        if ($rs === false) {
            $this->log("update WX_BATCH_SEND_FLAG fail" . M()->_sql());
            return;
        }
        // 获取待发送批次
        $msg_batch_list = M('twx_msgbatch')->where(
            "status ='0' AND send_mode = '2' AND send_on_time < '" .
                 date('YmdHis') . "'")->select();
        if(!$msg_batch_list) $msg_batch_list = array();
        foreach ($msg_batch_list as $msg_batch) {
            $this->log("get msg_batch  :[" . $msg_batch['batch_id'] . "]");
            $this->batch_send($msg_batch);
        }
        // 更新完成恢复同步标志
        $sync_flag_save['param_value'] = '1';
        $rs = M('tsystem_param')->where("param_name ='WX_BATCH_SEND_FLAG'")->save(
            $sync_flag_save);
        if (! $rs) {
            $this->log("update WX_BATCH_SEND_FLAG fail" . M()->_sql());
            return;
        }
    }

    private function batch_send($msg_batch) {
        $wx_send = D('WeiXinSend', 'Service');
        try {
            // 更改发送状态
            $msg_batch_update['status'] = '1';
            $rs = M('twx_msgbatch')->where(
                "batch_id = '{$msg_batch['batch_id']}'")->save($msg_batch_update);
            if ($rs === false) {
                log_write('更新twx_msgbatch 状态失败'.M()->_sql());
                return;
            }
            $wx_send->init($msg_batch['node_id']);
            $fans_list = explode(",", $msg_batch['openid_list']);
            if ($msg_batch['msg_type'] == 0 || $msg_batch['msg_type'] == 6) {
                $result = $wx_send->batch_send_text(
                    array(
                        'openids' => $fans_list, 
                        'content' => $msg_batch['msg_info'], 
                        'is_to_all' => $msg_batch['is_to_all']));
            } elseif ($msg_batch['msg_type'] == 2) {
                $result = $wx_send->batch_send(
                    array(
                        'material_id' => $msg_batch['msg_info'], 
                        'openids' => $fans_list, 
                        'is_to_all' => $msg_batch['is_to_all']));
            } elseif ($msg_batch['msg_type'] == 1) {
                $result = $wx_send->batch_send_image(
                    array(
                        'content' => $msg_batch['msg_info'], 
                        'openids' => $fans_list, 
                        'is_to_all' => $msg_batch['is_to_all']));
            } elseif ($msg_batch['msg_type'] == 4) {
                $result = $wx_send->batch_send_card(
                    array(
                        'card_id' => $msg_batch['msg_info'], 
                        'openids' => $fans_list, 
                        'is_to_all' => $msg_batch['is_to_all']));
            }
            if (! $result) {
                throw_exception('群发失败!');
            }
        } catch (Exception $e) {
            // M('twx_msgbatch')->where("batch_id =
            // '{$msg_batch['batch_id']}'")->delete();
            log_write('回复失败：' . $e->getMessage());
        }
        
        foreach ($result as $wx_msg_id) {
            $data = array(
                'batch_id' => $msg_batch['batch_id'], 
                'wx_batch_id' => $wx_msg_id);
            $flag = M('twx_msgbatch_resp')->add($data);
            
            if ($flag === false) {
                log_write('微信批量发送微信批次号入表失败！' . print_r($data, true));
            }
        }
    }
    // 记录日志
    protected function log($msg, $level = Log::INFO) {
        // trace('Log.'.$level.':'.$msg);
        log_write($msg);
    }
}
