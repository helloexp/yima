<?php
// 微信卡券库存变更处理后台任务
class WeixinCardStoreSyncJobAction {

    public function doCardStoreSyncJob() {
        set_time_limit(0);
        // 检查同步标志
        $sync_flag = M('tsystem_param')->where(
            "param_name ='WX_CARD_STORE_SYNC_FLAG'")->find();
        if (! $sync_flag) {
            $this->log("get WX_CARD_STORE_SYNC_FLAG not exit");
            return;
        }
        
        if ($sync_flag['param_value'] != '1') {
            $this->log("the WX_CARD_STORE_SYNC_FLAG is syncing");
            return;
        }
        
        // 更新同步标志
        $sync_flag_save['param_value'] = '2';
        $rs = M('tsystem_param')->where("param_name ='WX_CARD_STORE_SYNC_FLAG'")->save(
            $sync_flag_save);
        if ($rs === false) {
            $this->log("update WX_CARD_STORE_SYNC_FLAG fail" . M()->_sql());
            return;
        }
        // 获取待处理记录 条件 审核通过+ 未处理 + 库存变更数量 > 0
        $card_type_list = M('twx_card_type')->where(
            "auth_flag = '2' AND code_store_flag = '0' AND store_modify_num > 0")->select();
        if(!$card_type_list) $card_type_list = array();
        foreach ($card_type_list as $card_type) {
            $this->log("get card_type  :[" . $card_type['card_id'] . "]");
            $this->sync_store($card_type);
        }
        // 更新完成恢复同步标志
        $sync_flag_save['param_value'] = '1';
        $rs = M('tsystem_param')->where("param_name ='WX_CARD_STORE_SYNC_FLAG'")->save(
            $sync_flag_save);
        if ($rs === false) {
            $this->log("update WX_CARD_STORE_SYNC_FLAG fail" . M()->_sql());
            return;
        }
    }

    private function sync_store($card_type) {
        // 变更处理标志为处理中
        $card_type_update['code_store_flag'] = '1';
        $rs = M('twx_card_type')->where("id = " . $card_type['id'])->save(
            $card_type_update);
        if (! $rs) {
            $this->log(
                "update twx_card_type code_store_flag fail" . M()->_sql());
            return false;
        }
        $wxCardService = D('WeiXinCard', 'Service');
        $wxCardService->init_by_node_id($card_type['node_id']);
        if ($card_type['store_mode'] == '2') // 预存模式
            $rs = $wxCardService->batch_add_assist_number_for_create(
                $card_type['card_id'], $card_type['store_modify_num']);
        else // 普通投放模式
            $rs = $wxCardService->modify_stock($card_type['card_id'], 
                $card_type['store_modify_num']);
        
        if ($rs === true) {
            // 变更标志为处理结束 & 变更数量为0
            $card_type_update['code_store_flag'] = '2';
            $card_type_update['store_modify_num'] = 0;
            $rs = M('twx_card_type')->where("id = " . $card_type['id'])->save(
                $card_type_update);
            if (! $rs) {
                $this->log(
                    "update twx_card_type code_store_flag fail" . M()->_sql());
                return false;
            }
        }
        return true;
    }
    // 记录日志
    protected function log($msg, $level = Log::INFO) {
        // trace('Log.'.$level.':'.$msg);
        log_write($msg);
    }
}
