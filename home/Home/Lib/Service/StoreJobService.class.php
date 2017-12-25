<?php

/**
 * 工单、
 *
 * @author zhengxd 2014年12月05日
 */
class StoreJobService {

    public $error = '';

    public $LoguserID;

    public function __construct() {
        $this->LoguserID = $_SESSION['userSessInfo']['user_id'];
    }

    public function jobAdd($nodeId, $arrJob) {
        $nodeInfo = get_node_info($nodeId);
        if (! $nodeInfo) {
            $this->error = "没找到该商户信息";
            return false;
        }
        $arr = array();
        $arr['TransactionID'] = $this->getGlide();
        $arr['NodeId'] = $nodeId;
        $arr['JobType'] = 0;
        $arr['OperateType'] = 2;
        $arr['JobAppliantID'] = $nodeId;
        $arr['JobAppliant'] = $nodeInfo['contact_name'];
        $arr['JobContacts'] = $nodeInfo['contact_phone'] . '|' .
             $nodeInfo['contact_tel'];
        $JobDesc = array();
        $strbook = '';
        if ($nodeInfo['wc_version'] == 'v0.5')
            $strbook .= '条码支付';
        foreach ($arrJob as $v) {
            $storeInfo = M()->table("tstore_info a")->where(
                array(
                    'a.store_id' => $v, 
                    'a.node_id' => $nodeId))->find();
            if (! $storeInfo) {
                $this->error = '没有找到门店信息[error]';
                return false;
            }
            $posInfo = M('tpos_info')->where(
                array(
                    'store_id' => $v, 
                    'node_id' => $nodeId, 
                    'pos_type' => '1', 
                    'pos_status' => '0'))->find();
            if ($posInfo) {
                $this->error = '该门店已开通此设备[error]';
                return false;
            }
            $cityInfo = M('tcity_code')->where(
                array(
                    'province_code' => $storeInfo['province_code'], 
                    'city_code' => $storeInfo['city_code'], 
                    'town_code' => $storeInfo['town_code'], 
                    'city_level' => 3))->find();
            $JobDesc[] = array(
                'shop_name' => $storeInfo['store_name'], 
                'province' => $cityInfo['province'], 
                'city' => $cityInfo['city'], 
                'town' => $cityInfo['town'], 
                'shop_address' => $storeInfo['address'], 
                'signature' => $storeInfo['principal_name'], 
                'shop_telephone' => $storeInfo['store_phone'], 
                'mobile' => $storeInfo['principal_tel'], 
                'email' => $storeInfo['principal_email'], 
                'op_type' => '不开通电话应急', 
                'device_type' => '终端设备', 
                'finish_date' => date('Ymd', strtotime('+7 days')), 
                'demo' => $storeInfo['store_id'] . "实体终端 $strbook");
        }
        $arr['JobDesc']['process_content'] = $JobDesc;
        
        $reqResult = D('RemoteRequest', 'Service')->requestIssForImageco(
            array(
                'JobApplyReq' => $arr));
        if ($reqResult['PosStatusModifyReq']['Status']['StatusCode'] == '0000') {
            $arr['TransactionID'] = $reqResult['PosStatusModifyReq']['JobID'];
            $arr['pos_id'] = 0;
            $this->iniJobTable($arr);
            return true;
        }
        $this->error = print_r($reqResult, true);
        return false;
    }

    public function jobApply($nodeId, $posId, $storeId) {
        $nodeInfo = get_node_info($nodeId);
        if (! $nodeInfo) {
            $this->error = "没找到该商户信息";
            return false;
        }
        $arr = array();
        $arr['TransactionID'] = $this->getGlide();
        $arr['NodeId'] = $nodeId;
        $arr['JobType'] = 0;
        $arr['OperateType'] = 3;
        $arr['JobAppliantID'] = $nodeId;
        $arr['JobAppliant'] = $nodeInfo['contact_name'];
        $arr['JobContacts'] = $nodeInfo['contact_phone'] . '|' .
             $nodeInfo['contact_tel'];
        $storeInfo = M()->table("tstore_info a")->where(
            array(
                'a.store_id' => $storeId, 
                'a.node_id' => $nodeId))->find();
        if (! $storeInfo) {
            $this->error = '没有找到门店信息[error]';
            return false;
        }
        $posInfo = M('tpos_info')->where(
            array(
                'store_id' => $storeId, 
                'node_id' => $nodeId, 
                'pos_id' => $posId, 
                'pos_type' => '1', 
                'pos_status' => '0'))->find();
        
        if (! $posInfo) {
            $this->error = '该门店无此设备[error]';
            return false;
        }
        
        $arr['JobDesc']['process_content'] = array(
            array(
                'shop_name' => $storeInfo['store_name'], 
                'shop_address' => $storeInfo['address'], 
                'signature' => $storeInfo['principal_name'], 
                'shop_telephone' => $storeInfo['principal_tel'], 
                'mobile' => $storeInfo['principal_tel'], 
                'serial_number' => $posInfo['pos_id'], 
                'finish_date' => date('Ymd', strtotime('+7 days'))));
        
        $reqResult = D('RemoteRequest', 'Service')->requestIssForImageco(
            array(
                'JobApplayReq' => $arr));
        if ($reqResult['JobApplyRes']['Status']['StatusCode'] == '0000') {
            $arr['TransactionID'] = $reqResult['JobApplyRes']['JobID'];
            $arr['pos_id'] = $posId;
            $this->iniJobTable($arr);
            return true;
        }
        $this->error = $reqResult['JobApplyRes']['Status']['StatusText'];
        return false;
    }

    private function getGlide() {
        $sql = "SELECT _nextval('sys_uni_seq') as u FROM DUAL";
        $fruit = M()->query($sql);
        $number = $fruit[0]['u'];
        
        return str_pad($number, 20, "0", STR_PAD_LEFT);
    }

    private function iniJobTable($arr) {
        $data = array();
        $data['apply_id'] = $arr['TransactionID'];
        $data['node_id'] = $arr['NodeId'];
        $data['job_type'] = $arr['JobType'];
        $data['operate_type'] = $arr['OperateType'];
        $data['apply_content'] = json_encode($arr['JobDesc']);
        $data['apply_user_id'] = $arr['NodeId'];
        $data['apply_time'] = date('YmdHis');
        $data['status'] = 0;
        $data['pos_id'] = $arr['pos_id'];
        $result = M('tjob_apply')->add($data);
        node_log("【门店管理】申请工单");
    }
}
