<?php
// EPos接口服务
class EposNewAction extends BaseAction {

    public $_authAccessMap = '*';

    public function getFreeEpos() {
        $email = I('email', '', 'trim');
        try {
            self::modStoreEmail($email);
            D('EposNew')->createFreeEpos($this->node_id, $this->user_id, $email);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        $this->success();
    }

    private function modStoreEmail($email) {
        $storeId = M('tstore_info')->where(
            array(
                'node_id' => $this->node_id, 
                'type' => '2'))->getField('store_id');
        $reqArr = array(
            'MotifyPosStoreReq' => array(
                'SystemID' => C('ISS_SYSTEM_ID'), 
                'TransactionID' => time() . mt_rand('1000', '9999'), 
                'ISSPID' => $this->node_id, 
                'UserId' => $this->userId, 
                'StoreID' => $storeId, 
                'PrincipalEmail' => $email));
        $reqResult = D('RemoteRequest', 'Service')->requestIssServ($reqArr);
        $respStatus = isset($reqResult['MotifyPosStoreRes']) ? $reqResult['MotifyPosStoreRes']['Status'] : $reqResult['Status'];
        if ($respStatus['StatusCode'] != '0000') {
            throw_exception("门店信息修改失败");
        }
    }
}
