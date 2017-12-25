<?php

/**
 *
 * @author lwb Time 20150804
 */
class WcInterfaceModel extends Model {
    protected $tableName = '__NONE__';
    /**
     *
     * @param int $posId 终端号
     * @param int $postSid 待校验的sid
     * @return array('resp_id' => {resp_id}, 'resp_desc' => {resp_desc}, 'data'
     *         => array('zfb_pay_flag' => ?, 'weixin_pay_flag' => ?))
     */
    public function getPayStatus($posId, $postSid) {
        $sid = session_id();
        if ($sid != $postSid) {
            return array(
                'resp_id' => '1001', 
                'resp_desc' => 'sid失效');
        }
        $nodeId = M('tpos_info')->where(
            array(
                'pos_id' => $posId, 
                'pos_status' => '0'))->getField('node_id');
        if (! $nodeId) {
            return array(
                'resp_id' => '1002', 
                'resp_desc' => 'pos_id不正确或终端状态不正常');
        }
        $result = M('tzfb_offline_pay_info')->where(
            array(
                'node_id' => $nodeId, 
                'check_status' => '1', 
                'status' => '1'))
            ->Field('pay_type')
            ->select();
        $resp_id = '1006';
        $data = array(
            'zfb_pay_flag' => '1', 
            'weixin_pay_flag' => '1');
        $resp_desc = '未开通';
        if($result){
            foreach ($result as $v) {
                if ($v['pay_type'] === '0') {
                    $data['zfb_pay_flag'] = '2';
                    $resp_id = '0000';
                    $resp_desc = '支付宝支付已开通';
                }
                if ($v['pay_type'] === '1') {
                    $data['weixin_pay_flag'] = '2';
                    $resp_id = '0000';
                    $resp_desc = '微信支付已开通';
                }
            }
        }
        return array(
            'resp_id' => $resp_id, 
            'resp_desc' => $resp_desc, 
            'data' => $data);
    }

    public function getSessionId() {
        try {
            $sid = session_id();
            session('sid', $sid);
            return array(
                'resp_id' => '0000', 
                'resp_desc' => '成功', 
                'data' => array(
                    'sid' => $sid));
        } catch (Exception $e) {
            return array(
                'resp_id' => '1001', 
                'resp_desc' => '未知错误');
        }
    }
}