<?php

/**
 *
 * @author lwb Time 20150826
 */
class EposNewModel extends Model {
    protected $tableName = '__NONE__';
    public function _initialize() {
        import('@.Vendor.CommonConst') or die('include file fail.');
    }

    public function createFreeEpos($nodeId, $userId, $email) {
        // type为2表示系统默认创建的门店
        $storeInfo = M('tstore_info')->where(
            array(
                'node_id' => $nodeId, 
                'type' => '2'))->find();
        if (! $storeInfo) {
            throw_exception('系统异常:之前的步骤没有为您创建默认门店');
        }
        // 查询是不是以前有免费的epos//pos_type 2 表示是epos,pos_status 0 表示正常, pay_type 2
        // 表示免费
        $count = M('tpos_info')->where(
            array(
                'pos_type' => '2', 
                'pos_status' => '0', 
                'pay_type' => '2', 
                'node_id' => $nodeId))->count();
        if ($count > 0) {
            throw_exception('已经有免费的epos了,不能重复申请');
        }
        // 接收表单传值
        $req_arr = array();
        $pos_name = '默认门店' . $nodeId;
        $req_arr['SystemID'] = C('ISS_SYSTEM_ID');
        $req_arr['TransactionID'] = time() . mt_rand('1000', '9999');
        $req_arr['ISSPID'] = $nodeId;
        $req_arr['StoreID'] = $storeInfo['store_id'];
        $req_arr['PosGroupID'] = '';
        $req_arr['PosFlag'] = 0;
        $req_arr['PosType'] = 3;
        $req_arr['PosName'] = $pos_name;
        $req_arr['PosShortName'] = $pos_name;
        $req_arr['PosPayFlag'] = 2;
        $req_arr['UserID'] = $userId;
        $req_result = D('RemoteRequest', 'Service')->requestIssServ(
            array(
                'PosCreateReq' => $req_arr));
        $respStatus = isset($req_result['PosCreateRes']) ? $req_result['PosCreateRes']['Status'] : $req_result;
        if ($respStatus['StatusCode'] != '0000') {
            throw_exception($respStatus['StatusText']);
        }
        $respData = $req_result['PosCreateRes'];
        $pos_id = $respData['PosID'];
        if (! $pos_id) {
            throw_exception('创建支撑终端失败');
        }
        // 支撑会同步，先删掉
        M('tpos_info')->where(array(
            'pos_id' => $pos_id))->delete();
        // 创建终端
        $data = array(
            'pos_id' => $pos_id, 
            'node_id' => $nodeId, 
            'pos_name' => $pos_name, 
            'pos_short_name' => $pos_name, 
            'pos_serialno' => $pos_id, 
            'store_id' => $storeInfo['store_id'], 
            'store_name' => $storeInfo['store_name'], 
            'login_flag' => 0, 
            'pos_type' => '2',  // epos
            'is_activated' => 0, 
            'pos_status' => 0, 
            'add_time' => date('YmdHis'), 
            'pay_type' => '2'); // 免费
        
        $result = M('tpos_info')->add($data);
        if (! $result) {
            log_write(print_r($data, true) . M()->getDbError(), 'DB ERROR');
            throw_exception('创建终端失败,原因：' . M()->getDbError());
        }
        // 增加默认门店的终端数
        M('tstore_info')->where(
            array(
                'node_id' => $nodeId, 
                'type' => '2'))->setInc('pos_count');
        node_log("创建活动用的有时长的免费epos成功"); // 记录日志
    }
}