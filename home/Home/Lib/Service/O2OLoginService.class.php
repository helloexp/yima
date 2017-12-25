<?php

class O2OLoginService
{
    public function autoLoginPhone($nodeId, $phone, $channel_id, $batch_id)
    {
        $bcall = I('bcall');
        if ($bcall) {
            cookie('bcall', $bcall);
        }
        //压测使用
        // 记录session
        $ret = $this->_suppleSession($nodeId, $phone, $channel_id, $batch_id);
    }

    // 补充session
    private function _suppleSession($nodeId, $phone, $channel_id, $batch_id)
    {
        if (!$phone) {
            return false;
        }

        // 小店登录session
        session('groupPhone', $phone);
        session('cc_node_id', $nodeId);
        // 插入tmember_info_tmp会员表
        $userId = addMemberByO2o($phone, $nodeId, $channel_id, $batch_id);
        session('store_mem_id' . $nodeId, array('user_id' => $userId));
        //}
        // 补充全局cookie
        $global_phone = cookie('_global_user_mobile');
        if (!$global_phone) {
            cookie('_global_user_mobile', $phone, 3600 * 24 * 365);
        }
        return true;
    }
}

